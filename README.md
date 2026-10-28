# BiblioConnect — Industrialisation CI/CD et GitOps

![CI/CD](https://img.shields.io/badge/CI%2FCD-GitHub_Actions-2088FF?logo=githubactions&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4_LTS-black?logo=symfony&logoColor=white)
![Kubernetes](https://img.shields.io/badge/Kubernetes-K3s-326CE5?logo=kubernetes&logoColor=white)
![GitOps](https://img.shields.io/badge/GitOps-Argo_CD-EF7B4D)

## 1. Contexte et objectif

**BiblioConnect** est une application web de gestion de bibliothèque développée avec **Symfony, Twig et PostgreSQL**. Dans le cadre d'un TP DevOps, l'objectif est d'industrialiser sa livraison sans perturber le dépôt applicatif d'origine.

Le travail est réalisé dans un dépôt séparé, sur la branche **`feature/ci-cd`** :

- Application d'origine : <https://github.com/Anilzer/biblioconnect>
- Dépôt DevOps : <https://github.com/Anilzer/biblioconnect-devops>

**Objectifs couverts :** intégration continue, tests automatisés, analyse qualité, audit de sécurité, construction et publication d'image Docker, déploiement GitOps avec Argo CD et démonstration d'un rollback.

## 2. Architecture technique

| Couche | Technologies / rôle |
|---|---|
| Application | PHP 8.2, Symfony 7.4 LTS, Twig, Symfony UX / AssetMapper |
| Base de données | PostgreSQL 16 |
| Tests | PHPUnit 9.6, tests unitaires et d'intégration |
| CI/CD | GitHub Actions |
| Qualité | SonarQube, analysé par un runner Windows self-hosted |
| Sécurité | `composer audit`, Trivy (image Docker) |
| Packaging | Docker, FrankenPHP |
| Registre | GitHub Container Registry (GHCR) |
| Orchestration | K3s via k3d |
| GitOps | Argo CD, synchronisation de `k8s/` depuis `feature/ci-cd` |
| Stockage | PersistentVolumeClaim de 2 Gi, StorageClass `local-path` |

### Chaîne de livraison

```text
Développeur
    |
    | git push origin feature/ci-cd
    v
GitHub Actions
    |
    +--> 1. Symfony / Composer / PostgreSQL / PHPUnit
    |
    +--> 2. SonarQube (runner Windows self-hosted)
    |
    +--> 3. Docker build -> Trivy -> GHCR
    |
    +--> 4. Mise à jour automatique des images dans k8s/app.yaml
                        |
                        v
                 Commit GitOps [skip ci]
                        |
                        v
                 Argo CD (auto-sync)
                        |
                        v
              K3s : Symfony + PostgreSQL
                        |
                        v
           http://127.0.0.1:8090/login
```

La publication utilise un **tag correspondant au SHA Git**. Le fichier `k8s/app.yaml` référence la même version d'image pour le conteneur applicatif et le conteneur d'initialisation des migrations.

## 3. Organisation du dépôt

```text
.github/workflows/ci-cd.yml    # Pipeline de tests, qualité, sécurité, publication et GitOps
argocd/biblioconnect.yaml      # Définition de l'application Argo CD
k8s/app.yaml                  # Deployment Symfony et migrations Doctrine
k8s/postgres.yaml             # Service + StatefulSet + volume PostgreSQL
k8s/service.yaml              # Service HTTP / NodePort 30090
migrations_postgresql/        # Migrations compatibles PostgreSQL
sonar-project.properties      # Configuration de SonarQube
Dockerfile                    # Image applicative FrankenPHP
.dockerignore                 # Exclusions du contexte Docker
```

Le dépôt initial comportait des migrations propres à MySQL. Des migrations PostgreSQL distinctes ont été générées pour les environnements du TP, sans remplacer les migrations d'origine.

## 4. Intégration continue (CI)

Le workflow `.github/workflows/ci-cd.yml` s'exécute sur les mises à jour de la branche `feature/ci-cd` (selon les déclencheurs configurés dans le workflow).

1. **Tests Symfony :** installation Composer, démarrage d'un service PostgreSQL de test, création de la base, migrations et exécution de PHPUnit.
2. **Qualité SonarQube :** analyse du code PHP, des templates et des ressources applicatives depuis un runner Windows disposant de SonarScanner et d'un accès au serveur SonarQube local.
3. **Docker, Trivy et GHCR :** construction de l'image, scan de vulnérabilités puis publication seulement si les contrôles réussissent.
4. **GitOps :** après publication, remplacement des deux références d'image dans `k8s/app.yaml`, puis commit automatique vers `feature/ci-cd`.

Le commit GitOps est créé avec le marqueur **`[skip ci]`**, afin d'éviter de relancer inutilement toute la chaîne de construction.

### Contrôles validés pendant le TP

- `composer audit` : aucune alerte de vulnérabilité connue lors du contrôle final local.
- PHPUnit : **4 tests réussis, 5 assertions**.
- Symfony : **7.4 LTS** ; PHPUnit : **9.6.38** au moment de la validation locale.
- GitHub Actions : **4 jobs réussis** (tests, SonarQube, Docker/Trivy/GHCR, mise à jour GitOps).
- Une image corrigée a été reconstruite sur une base Debian 13, après la détection de vulnérabilités dans l'ancienne base Debian 12 et les anciennes dépendances Composer.

> Les tests démontrent la réussite du scénario du TP, mais leur nombre reste limité : ils ne constituent pas une couverture complète des fonctionnalités métier.

## 5. Déploiement Kubernetes avec Argo CD

L'application est isolée dans le namespace `biblioconnect` du cluster k3d existant. Les applications Java et Quiz utilisent leurs propres ressources et ne sont pas modifiées par ce déploiement.

### Ressources

- `StatefulSet/biblio-postgres` : PostgreSQL 16, une instance.
- `PersistentVolumeClaim/postgres-data-biblio-postgres-0` : 2 Gi, `local-path`.
- `Deployment/biblioconnect` : Symfony servi avec FrankenPHP.
- `initContainer/database-migrations` : exécution des migrations Doctrine avant le démarrage du conteneur applicatif.
- `Service/biblioconnect` : port HTTP 80, NodePort `30090`.
- `Application/biblioconnect` : synchronisation Argo CD de la branche `feature/ci-cd`, dossier `k8s/`.

Le déploiement requiert un Secret Kubernetes nommé `biblio-secrets`, **créé hors du dépôt Git**, contenant au minimum `POSTGRES_PASSWORD`, `DATABASE_URL` et `APP_SECRET`.

### Vérifier le déploiement

Dans **Git Bash sous Windows** :

```bash
docker exec k3d-java-demo-cluster-server-0 kubectl get applications -n argocd
docker exec k3d-java-demo-cluster-server-0 kubectl get pods,svc,pvc -n biblioconnect
docker exec k3d-java-demo-cluster-server-0 kubectl -n biblioconnect get deployment biblioconnect -o jsonpath='{.spec.template.spec.containers[0].image}'
echo
```

L'état attendu après synchronisation est **`Synced / Healthy`** pour Argo CD, **`1/1 Running`** pour PostgreSQL et Symfony, et **`Bound`** pour le PVC.

### Accès à l'application

Depuis le poste de développement, un relais Docker local expose le service Kubernetes sur :

**<http://127.0.0.1:8090/login>**

La route `/login` est celle qui a été validée dans le navigateur. La route `/` peut répondre `404` ; le statut HTTP de `/` n'est donc pas suffisant pour conclure à une panne de l'application.

L'interface Argo CD est accessible, dans l'environnement du TP, via **<https://localhost:8086>** lorsque le relais Docker prévu est actif.

## 6. Démonstration du Continuous Deployment

Le fonctionnement de bout en bout a été validé avec une modification visible de la page de connexion :

1. La **V1** affichait « Connexion ».
2. Un changement du template Twig a permis de produire la **V2** affichant « Connexion 2 ».
3. Un `git push` sur `feature/ci-cd` a déclenché les quatre jobs.
4. GHCR a reçu une nouvelle image versionnée.
5. Le job GitOps a modifié `k8s/app.yaml` et créé un commit automatique.
6. Argo CD a synchronisé le manifeste et K3s a démarré la nouvelle image.
7. La V2 a été constatée dans le navigateur sur `/login`.

**Exemple de SHA de l'image V2 :** `cbcf246e3d4ee37f6e8ed057787cd826036a850b`.

## 7. Rollback GitOps — V2 vers V1

Le retour arrière est réalisé en modifiant **uniquement les références d'image** dans `k8s/app.yaml`, puis en poussant ce changement sur Git. Argo CD réapplique l'ancienne version sans recréer PostgreSQL.

Versions utilisées lors de la démonstration :

| Version | SHA de l'image GHCR | Texte affiché |
|---|---|---|
| V1 | `fdceea7f291ffb354c5d3cd1c3ccf67aae5bbad3` | Connexion |
| V2 | `cbcf246e3d4ee37f6e8ed057787cd826036a850b` | Connexion 2 |

Exemple de commande de rollback, **à ne lancer que si la V2 est effectivement la version déployée** :

```bash
sed -i 's/cbcf246e3d4ee37f6e8ed057787cd826036a850b/fdceea7f291ffb354c5d3cd1c3ccf67aae5bbad3/g' k8s/app.yaml

grep 'image: ghcr.io/' k8s/app.yaml

git add k8s/app.yaml
git commit -m 'gitops: rollback BiblioConnect V2 to V1 [skip ci]'
git push origin feature/ci-cd
```

Contrôle :

```bash
docker exec k3d-java-demo-cluster-server-0 kubectl get applications -n argocd
docker exec k3d-java-demo-cluster-server-0 kubectl get pods -n biblioconnect
```

**Résultat de la démonstration :** retour de « Connexion 2 » à « Connexion » dans le navigateur.

> **Limite importante :** un rollback d'image ne restaure ni le schéma ni les données PostgreSQL. Les migrations doivent rester rétrocompatibles, ou être accompagnées d'une stratégie de restauration de base. Le test V1/V2 du TP n'impliquait qu'une modification d'interface.

## 8. Sécurité et précautions

- Les mots de passe PostgreSQL et la clé `APP_SECRET` ne doivent jamais être committés.
- La publication de l'image dépend de la réussite de l'analyse Trivy prévue dans le workflow.
- Les images versionnées évitent les déploiements ambigus liés au seul tag `latest`.
- Le stockage PostgreSQL reste indépendant du cycle de vie des pods Symfony.
- Le serveur SonarQube et le runner self-hosted doivent être accessibles pour exécuter l'ensemble du pipeline.
- La synchronisation Argo CD et le déploiement Kubernetes sont indépendants d'une session ouverte dans l'interface web Argo CD.
- Le statut `Healthy` est une indication d'état des ressources, pas une garantie exhaustive du bon fonctionnement métier.

## 9. Limites et perspectives

- Augmenter la couverture des tests, notamment les scénarios métier et les parcours HTTP de bout en bout.
- Ajouter des tests applicatifs après déploiement et une supervision des métriques et logs.
- Renforcer la gestion des secrets (par exemple avec un gestionnaire de secrets dédié).
- Encadrer les migrations SQL afin de garantir un rollback réellement sûr.
- Passer d'un environnement de démonstration local à un environnement de préproduction disposant de sauvegardes, de haute disponibilité et d'un accès HTTPS approprié.
- Évaluer et traiter séparément les avertissements de dépréciation signalés par PHPUnit/Symfony.

## 10. Bilan

Ce TP a permis d'industrialiser la livraison d'une application Symfony existante avec une chaîne complète : **tests → qualité → sécurité → image Docker → registre → GitOps → Kubernetes**.

La démonstration a validé **une mise en production simulée automatique (V1 → V2)** ainsi qu'un **rollback maîtrisé (V2 → V1)**, sans impacter les autres applications du cluster et sans supprimer la base de données PostgreSQL persistante.
