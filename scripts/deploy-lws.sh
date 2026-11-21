#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Déploiement / mise à jour de Cantine de Vatel sur un hébergement mutualisé LWS.
# À lancer en SSH depuis le dossier du site, par exemple :
#   cd ~/htdocs/cantinevatel.lartisse.com && bash scripts/deploy-lws.sh
# Premier déploiement : créer d'abord le fichier .env (voir docs/DEPLOIEMENT.md, section LWS).
# ---------------------------------------------------------------------------
set -euo pipefail
cd "$(dirname "$0")/.."

echo "== Vérifications"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || { echo "PHP 8.2 minimum requis (actuel : $(php -r 'echo PHP_VERSION;')). Changez la version dans le panneau LWS."; exit 1; }
missing=""
for ext in gd intl pdo_mysql mbstring openssl fileinfo zip; do
  php -m | grep -qi "^${ext}$" || missing="$missing $ext"
done
[ -z "$missing" ] || { echo "Extensions PHP manquantes :$missing (à activer dans la configuration PHP du panneau LWS)."; exit 1; }
[ -f .env ] || { echo "Fichier .env absent : copiez .env.example en .env et renseignez-le (voir docs/DEPLOIEMENT.md)."; exit 1; }
grep -q '^APP_ENV=production' .env || echo "Attention : APP_ENV n'est pas « production » dans .env."
grep -q '^APP_DEBUG=false' .env || echo "Attention : APP_DEBUG doit valoir false en production."

if [ -d .git ]; then
  echo "== Récupération du code (git pull)"
  git pull --ff-only
fi

echo "== Dépendances PHP"
composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
  echo "== Clé de chiffrement de l'application"
  php artisan key:generate --force
fi

echo "== Base de données"
php artisan migrate --force

echo "== Fichiers téléversés (lien public/storage)"
[ -e public/storage ] || php artisan storage:link

echo "== Caches de production"
php artisan optimize:clear
php artisan optimize

echo "== Terminé. Vérifiez https://$(grep '^APP_URL=' .env | cut -d/ -f3)"
