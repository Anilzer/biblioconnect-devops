# BiblioConnect — Industrialisation CI/CD, GitOps et déploiement OVHcloud

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white) ![Symfony](https://img.shields.io/badge/Symfony-7.4_LTS-black?logo=symfony&logoColor=white) ![CI/CD](https://img.shields.io/badge/CI%2FCD-GitHub_Actions-2088FF?logo=githubactions&logoColor=white) ![Kubernetes](https://img.shields.io/badge/Kubernetes-K3s-326CE5?logo=kubernetes&logoColor=white) ![GitOps](https://img.shields.io/badge/GitOps-Argo_CD-EF7B4D) ![Production](https://img.shields.io/badge/Production-OVHcloud-0050D7)

**Projet :** application de gestion de bibliothèque (catalogue, authentification et rôles, réservation de livres, gestion bibliothécaire).  
**Auteurs / soutenance :** Andil ABAYOMI et Thomas VALLET.  
**Dépôt initial conservé :** https://github.com/Anilzer/biblioconnect  
**Dépôt DevOps :** https://github.com/Anilzer/biblioconnect-devops — branche de travail `feature/ci-cd`  
**Production :** https://biblio.andilabayomi.fr/login

> **Périmètre :** ce dépôt permet de construire, tester, analyser, publier et déployer BiblioConnect. La production OVHcloud utilise Docker Compose et Traefik ; la démonstration GitOps locale utilise K3s/k3d et Argo CD. Les deux cibles emploient des images Docker versionnées, mais pas le même orchestrateur.

## 1. Objectif et résultats

Industrialiser la livraison de l'application Symfony **sans modifier le dépôt initial**, avec des contrôles automatisés et des procédures de déploiement/retour arrière reproductibles.

| Capacité | Mise en œuvre | Vérification disponible |
|---|---|---|
| Tests applicatifs | PHPUnit et PostgreSQL de test | 4 tests, 5 assertions réussies lors du contrôle connu |
| Qualité | SonarQube via runner Windows auto-hébergé | Analyse intégrée au pipeline |
| Sécurité | `composer audit` et Trivy | Vérifications CI ; aucun audit ne garantit l'absence totale de vulnérabilités |
| Packaging | PHP 8.2 / Symfony 7.4 / FrankenPHP | Dockerfile et image GHCR |
| GitOps local | K3s via k3d, manifests dans `k8s/`, Argo CD | Déploiement et rollback V1/V2 démontrés |
| Production | VPS Ubuntu OVHcloud, Docker Compose, Traefik, HTTPS | Déploiements V1/V2 et rollback démontrés |
| Données de démonstration | PostgreSQL **de production**, isolé du local | 10 livres, 10 auteurs, 3 catégories, 2 langues insérés et visibles dans le catalogue |
| Déploiement par push unique | Job VPS ajouté à `ci-cd.yml` | **À confirmer par une exécution globale réussie des cinq jobs** |

## 2. Architecture

```text
PC développeur — git push origin feature/ci-cd
                       |
                       v
                 GitHub Actions
           +-----------+------------+
           | PHP / Composer / PHPUnit|
           | SonarQube (runner local)|
           | Docker build + Trivy   |
           +-----------+------------+
                       |
                       v
            GHCR — image taggée SHA Git
                       |
             +---------+---------+
             |                   |
             v                   v
       CD LOCAL / GitOps       CD VPS / OVHcloud
       update-gitops          job deploy-vps (CI)
       k8s/app.yaml            SSH restreint
       commit [skip ci]        deploy.sh
       Argo CD                 Docker Compose
       K3s / k3d              Traefik + Let's Encrypt
       Symfony + PG16          Symfony + PG16 dédié
       :8090/login             https://biblio.andilabayomi.fr/login
```

**Condition d'automatisation :** le job `deploy-vps` doit être présent sur `feature/ci-cd`, dépendre de la publication GHCR et des vérifications requises, et réussir dans GitHub Actions. Le runner SonarQube local doit être **en ligne** ; le cluster k3d doit être disponible pour le résultat GitOps local. Un push Git accepté n'est pas, à lui seul, une preuve de livraison.

## 3. Pile technique

| Fonction | Technologie |
|---|---|
| Application | Symfony 7.4 LTS, PHP 8.2, Twig, Symfony UX / AssetMapper |
| Données | PostgreSQL 16, Doctrine ORM et migrations PostgreSQL dédiées |
| Tests | PHPUnit 9.6.38, base de test indépendante |
| CI | GitHub Actions (`php-tests`, `sonar-analysis`, `docker-ghcr`) |
| Qualité et sécurité | SonarQube, Composer audit, Trivy |
| Image | Docker / FrankenPHP, GitHub Container Registry |
| GitOps | `update-gitops`, Argo CD |
| Local | Docker Desktop, k3d/K3s, Kubernetes, PVC `local-path` 2 Gi |
| Production | VPS OVHcloud Ubuntu, Docker Compose, Traefik, Let's Encrypt |
| Publication de production | GitHub Actions + SSH à commande forcée + script serveur `deploy.sh` |

## 4. Organisation des fichiers utiles

```text
.github/workflows/
  ci-cd.yml                 # Tests, qualité, sécurité, GHCR, GitOps, job VPS ajouté
  deploy-vps.yml            # Déploiement OVHcloud manuel (workflow_dispatch)
argocd/biblioconnect.yaml   # Application GitOps, branche feature/ci-cd, chemin k8s/
k8s/app.yaml               # Deployment Symfony et initContainer de migrations
k8s/postgres.yaml          # PostgreSQL StatefulSet, Service et PVC
k8s/service.yaml           # Service HTTP, NodePort 30090
migrations_postgresql/     # Migrations adaptées au PostgreSQL du TP
src/Controller/            # Logique applicative, dont historique des réservations
templates/                 # Vues Twig
Dockerfile                 # Application FrankenPHP
sonar-project.properties   # Analyse SonarQube
```

**Sur le VPS (non versionné dans Git) :** `/home/ubuntu/deployments/biblioconnect/compose.prod.yaml`, `.env`, `.env.app`, `.env.db`, `deploy.sh`, sauvegardes sous `backups/`. Ces fichiers et secrets sont propres au serveur. **Ne jamais committer les `.env` ni les clés SSH.** Le script local `scripts/push-deploy.sh`, s'il a été ajouté au dépôt, simplifie `fetch → rebase → push` ; il ne déploie pas directement sur le VPS.

## 5. Démarrer et vérifier l'environnement local

**Prérequis :** Docker Desktop, k3d et Git installés, runner GitHub Actions Windows disponible, SonarQube local accessible, cluster `java-demo-cluster` existant. Ne pas recréer ni supprimer le cluster contenant d'autres applications.

Dans PowerShell :

```powershell
k3d cluster list
# Uniquement si le cluster existant est arrêté :
# k3d cluster start java-demo-cluster

docker exec k3d-java-demo-cluster-server-0 kubectl get nodes
docker exec k3d-java-demo-cluster-server-0 kubectl -n argocd get applications
docker exec k3d-java-demo-cluster-server-0 kubectl -n biblioconnect get pods,svc,pvc
docker ps -a --filter "name=java-demo-argocd-relay"
docker ps -a --filter "name=biblioconnect-relay"
```

- **Application locale :** http://127.0.0.1:8090/login (si son relais est actif).
- **Argo CD local :** https://localhost:8086 (certificat interne pouvant déclencher un avertissement navigateur).

**Dépannage d'accès Argo CD connu :** le conteneur `java-demo-argocd-relay` peut rester `Up` alors que le port-forward interne vers `argocd-server` est arrêté. Après vérification que le service existe et qu'aucun transfert équivalent n'est actif :

```powershell
docker exec -d k3d-java-demo-cluster-server-0 kubectl -n argocd port-forward --address 0.0.0.0 svc/argocd-server 18443:443
```

Cette commande ne doit pas être lancée répétitivement : vérifier l'existence du processus et du port si elle échoue. Si `kubectl` Windows ne joint pas l'API (`127.0.0.1:63247`), `docker exec ... kubectl` permet de vérifier le cluster depuis son conteneur. Un pod `argocd-applicationset-controller` a déjà présenté un `CrashLoopBackOff` sans bloquer le serveur Argo CD ; ne pas le supprimer pour résoudre un simple problème de navigateur.

### Kubernetes et GitOps

L'application occupe le namespace `biblioconnect` et utilise : `Deployment/biblioconnect`, `StatefulSet/biblio-postgres`, un Secret non committé `biblio-secrets`, un PVC 2 Gi et un Service NodePort 30090. Argo CD surveille la branche `feature/ci-cd`, répertoire `k8s/`.

**`Healthy`** signifie que les ressources sont opérationnelles ; **`Synced`** que l'état Kubernetes correspond à Git. Si `OutOfSync`, examiner **DIFF** avant une synchronisation. Une différence déjà observée concerne la stratégie `RollingUpdate` (`25%/25%` dans le cluster contre `maxSurge: 1`, `maxUnavailable: 0` dans Git). Ne jamais utiliser `PRUNE`/`FORCE` à l'aveugle.

## 6. CI : cinq jobs visés

Sur un push vers `feature/ci-cd`, la configuration comporte les jobs suivants :

1. **`php-tests`** : Composer, PostgreSQL de test, migrations, PHPUnit.
2. **`sonar-analysis`** : SonarScanner sur runner Windows auto-hébergé, relié à SonarQube local.
3. **`docker-ghcr`** : construction de l'image, scan Trivy, publication GHCR avec tag SHA.
4. **`update-gitops`** : mise à jour des références d'image dans `k8s/app.yaml`, commit `[skip ci]`, synchronisation effectuée ensuite par Argo CD.
5. **`deploy-vps`** : appel SSH à commande restreinte pour livrer cette image sur OVHcloud. **Vérifier sa réussite effective avant de déclarer le push unique validé.**

**Exécution :** https://github.com/Anilzer/biblioconnect-devops/actions

La CI échoue ou reste en attente si le runner Windows/SonarQube n'est pas disponible. Les avertissements Symfony de dépréciation constatés lors des tests ne constituaient pas des échecs (`4 tests`, `5 assertions`).

## 7. Publication et versions démontrées

Image GHCR : `ghcr.io/anilzer/biblioconnect-devops:<SHA_GIT>`.

| Version | SHA Git | Preuve métier/visuelle |
|---|---|---|
| V1 | `fdceea7f291ffb354c5d3cd1c3ccf67aae5bbad3` | Connexion |
| V2 | `cbcf246e3d4ee37f6e8ed057787cd826036a850b` | Connexion 2 |

Les changements V1 → V2 et V2 → V1 ont été validés en local et sur le VPS. Une opération de rollback d'image **ne restaure pas** automatiquement les migrations ni les données PostgreSQL.

## 8. Production OVHcloud

**URL :** https://biblio.andilabayomi.fr/login  
**Domaine :** enregistrement DNS A vers le VPS ; TLS via Let's Encrypt sur le Traefik **déjà en fonctionnement**.  
**Réseaux Docker :** `proxy` partagé avec Traefik ; réseau interne isolé pour la base.  
**Services propres à BiblioConnect :** `biblioconnect-prod-app-1`, `biblioconnect-prod-postgres-1`, service ponctuel de migrations.  
**Base :** PostgreSQL 16 dédié, volume Docker persistant, port 5432 **non exposé publiquement**.

Le VPS héberge également des applications et outils préexistants. Le déploiement BiblioConnect ne doit **ni redémarrer Traefik, ni supprimer des volumes, ni lancer `docker system prune`**, ni toucher aux applications déjà présentes (notamment Furious Ducks).

### Vérifier les services sur le VPS

```bash
cd ~/deployments/biblioconnect
sudo docker compose -f compose.prod.yaml ps -a
sudo docker inspect biblioconnect-prod-app-1 \
  --format 'IMAGE={{.Config.Image}} HEALTH={{.State.Health.Status}}'
curl -I https://biblio.andilabayomi.fr/login
```

Une réponse HTTPS `200` et un conteneur `healthy` valident l'accessibilité et la santé technique, pas tous les parcours métier.

### Déploiement sécurisé et rollback

Le workflow GitHub Actions utilise quatre secrets : `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` et `VPS_KNOWN_HOSTS`. La clé SSH dédiée est restreinte sur le VPS à une commande `deploy <SHA>`. Le script serveur :

- valide le SHA Git et télécharge l'image GHCR correspondante ;
- crée une sauvegarde PostgreSQL avant migrations ;
- exécute les migrations avec l'image ciblée ;
- remplace **uniquement** le conteneur applicatif BiblioConnect ;
- attend l'état `healthy`, vérifie HTTPS et mémorise la nouvelle version ;
- restaure l'image précédente en cas d'échec applicatif, mais **pas le schéma SQL**.

**Incident résolu :** `sudo` ne transmettait pas la variable `BIBLIO_IMAGE` à Docker Compose ; Compose conservait l'image V1 malgré le téléchargement V2. La correction passe explicitement la variable avec `sudo env BIBLIO_IMAGE=... docker compose ...` et effectue un contrôle d'image et de santé.

**Déploiement manuel de secours :** `.github/workflows/deploy-vps.yml` sur GitHub Actions, avec saisie d'un SHA Git complet d'image déjà validée. Ne pas confondre ce workflow manuel avec le nouveau job `deploy-vps` du pipeline `ci-cd.yml`.

### Sauvegardes et exploitation

Exemple de sauvegarde propre à BiblioConnect :

```bash
cd ~/deployments/biblioconnect
mkdir -p backups && chmod 700 backups
sudo docker compose -f compose.prod.yaml exec -T postgres \
  pg_dump -U biblio -d biblioconnect -Fc \
  > "backups/biblioconnect-$(date -u +%Y%m%d-%H%M%S).dump"
chmod 600 backups/*.dump
```

Une sauvegarde conservée sur le **même VPS** ne remplace pas une sauvegarde externe restaurable et testée.

## 9. Démonstration métier : données en production seulement

La base PostgreSQL **de production** contient un catalogue de démonstration de 10 livres, 10 auteurs, 3 catégories et 2 langues. Des comptes client et bibliothécaire permettent de distinguer les droits d'accès, et une réservation du livre *Le Petit Prince* a été constatée en base.

| Parcours | Adresse |
|---|---|
| Inscription | https://biblio.andilabayomi.fr/register |
| Connexion | https://biblio.andilabayomi.fr/login |
| Catalogue client | https://biblio.andilabayomi.fr/profile/livres |
| Gestion bibliothécaire | https://biblio.andilabayomi.fr/librarian/livres |
| Ajout d'un livre | https://biblio.andilabayomi.fr/librarian/livre/new |
| Historique des réservations | https://biblio.andilabayomi.fr/librarian/reservation/history |

Les rôles existants comprennent `ROLE_USER`, `ROLE_LIBRARIAN` et `ROLE_ADMIN`. Un correctif du contrôleur d'historique a été committé pour permettre au bibliothécaire de consulter les réservations des clients ; **sa présence effective sur le VPS doit être vérifiée après le déploiement automatique**.

Pour consulter les données sans exposer PostgreSQL :

```bash
cd ~/deployments/biblioconnect
sudo docker compose -f compose.prod.yaml exec postgres \
  psql -U biblio -d biblioconnect
```

```sql
SELECT COUNT(*) AS livres FROM livre;
SELECT COUNT(*) AS reservations FROM reservation;
```

**Aucune fixture ni insertion métier ne doit être exécutée dans la base PostgreSQL locale** pour la démonstration demandée.

## 10. Git : publication et conflits GitOps

Le job `update-gitops` publie des commits automatisés sur **la même branche** que les commits applicatifs. Cela a provoqué des refus `fetch first` ou `cannot lock ref` quand la branche distante changeait entre le `fetch` et le `push`.

Procédure sûre après un commit, si l'arbre de travail est propre :

```bash
git fetch origin
git rebase origin/feature/ci-cd
git push origin feature/ci-cd
```

Si le script local `scripts/push-deploy.sh` a été créé et committé, il automatise cette séquence et réessaie en cas de concurrence. **Aucun `git push --force` n'est nécessaire.** En cas de conflit de rebase, le résoudre avant de pousser.

**Amélioration durable recommandée :** isoler les manifests GitOps dans une branche ou un dépôt dédié, puis adapter la source suivie par Argo CD. Cela supprimerait la concurrence directe entre les commits applicatifs et les commits du bot.

## 11. Démonstration et preuves de soutenance

1. Montrer la branche `feature/ci-cd`, l'organisation du dépôt et le Dockerfile.
2. Montrer les jobs et les rapports de qualité/sécurité dans GitHub Actions.
3. Ouvrir Argo CD et démontrer `Synced / Healthy` après vérification du `DIFF` si nécessaire.
4. Ouvrir BiblioConnect local sur `:8090/login`.
5. Ouvrir la production en HTTPS et le catalogue de livres.
6. Montrer la distinction client/bibliothécaire et, après validation de la correction, la réservation client dans l'interface bibliothécaire.
7. Montrer une évolution d'image puis le rollback sans supprimer les données de production.
8. Montrer le résultat du job VPS et l'image réellement démarrée sur le serveur.

**Attention :** ne pas présenter les cinq jobs comme tous validés tant qu'un run complet post-ajout de `deploy-vps` n'a pas été contrôlé. Les captures doivent provenir des environnements réellement exécutés et ne pas contenir de clés SSH, de mots de passe ou de tokens.

## 12. Répartition prévue pour la présentation

| Présentation principale | Domaines |
|---|---|
| **Andil ABAYOMI** | Développement applicatif, Kubernetes/K3s, Argo CD/GitOps, déploiement VPS et rollback |
| **Thomas VALLET** | CI et qualité, conteneurisation, documentation et préparation de la démonstration |

Cette répartition indique les **parties attribuées pour la soutenance** ; elle ne doit pas être utilisée comme preuve de contribution individuelle au code sans vérification des réalisations effectives.

## 13. Limites et perspectives

- Augmenter la couverture des tests, particulièrement les autorisations, réservations et scénarios HTTP de bout en bout.
- Fiabiliser le port-forward local et le démarrage du runner Windows/SonarQube.
- Traiter la dérive `OutOfSync` du Deployment et le `CrashLoopBackOff` du contrôleur ApplicationSet si leur résolution est nécessaire.
- Renforcer la protection de la production : approbations, déploiement progressif, sauvegarde externalisée et restauration testée.
- Séparer la branche GitOps de celle du développement pour prévenir les conflits de push.
- Vérifier et documenter le premier **run global réussi** du push unique jusqu'au VPS.

## 14. Liens

- Dépôt DevOps : https://github.com/Anilzer/biblioconnect-devops
- Code et manifests : https://github.com/Anilzer/biblioconnect-devops/tree/feature/ci-cd
- GitHub Actions : https://github.com/Anilzer/biblioconnect-devops/actions
- Application de production : https://biblio.andilabayomi.fr/login
- Argo CD local : https://localhost:8086 (disponible uniquement sur le PC de démonstration)
- Application locale : http://127.0.0.1:8090/login (disponible uniquement sur le PC de démonstration)
