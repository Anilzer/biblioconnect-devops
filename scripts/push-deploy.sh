
#!/usr/bin/env bash
set -euo pipefail

BRANCH="feature/ci-cd"
MAX_ATTEMPTS=4

echo "======================================="
echo " BIBLIOCONNECT - PUSH & DEPLOIEMENT"
echo "======================================="

# Vérifier la branche actuelle
CURRENT_BRANCH=$(git branch --show-current)

if [[ "$CURRENT_BRANCH" != "$BRANCH" ]]; then
  echo "ERREUR : tu dois être sur $BRANCH"
  exit 1
fi

# Ne pas cacher silencieusement des modifications locales
if [[ -n "$(git status --porcelain)" ]]; then
  echo "ERREUR : des fichiers ne sont pas commites."
  echo "Termine ton commit avant de lancer ce script."
  git status --short
  exit 1
fi

# Synchronisation Git et tentative de publication
for attempt in $(seq 1 "$MAX_ATTEMPTS"); do

  echo ""
  echo "Tentative $attempt/$MAX_ATTEMPTS"

  echo "1. Recuperation des commits distants..."
  git fetch origin "$BRANCH"

  echo "2. Rebase sur GitHub..."
  if ! git rebase "origin/$BRANCH"; then
    echo "Conflit Git : intervention manuelle necessaire."
    echo "Resous le conflit, puis execute git rebase --continue."
    exit 1
  fi

  echo "3. Publication du commit..."
  if git push origin "HEAD:$BRANCH"; then
    echo ""
    echo "======================================="
    echo " PUSH REUSSI"
    echo "======================================="
    echo "Commit : $(git rev-parse HEAD)"
    echo "GitHub Actions va traiter ce push."
    echo "https://github.com/Anilzer/biblioconnect-devops/actions"
    exit 0
  fi

  echo "La branche distante a peut-etre change."
  echo "Nouvelle tentative de synchronisation..."
  sleep 2
done

echo "ECHEC : trop de conflits de publication."
echo "Verifier le workflow update-gitops."
exit 1
