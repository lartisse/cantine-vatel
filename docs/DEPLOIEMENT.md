# Déploiement — Cantine de Vatel

Cible : un VPS (par exemple LWS) sous Ubuntu 22.04/24.04, avec Nginx, PHP 8.2 et MySQL 8.
Une procédure de lancement local de secours est donnée à la fin (démo sans Internet).

## 1. Paquets

```bash
sudo apt update
sudo apt install -y nginx mysql-server git unzip curl \
  php8.2-fpm php8.2-cli php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-intl php8.2-bcmath
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node.js 20 (compilation des assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
```

Extensions PHP indispensables : `gd` (recadrage et WebP), `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`.
Dans `php.ini` : `upload_max_filesize = 6M` et `post_max_size = 12M` (les photos sont limitées à 5 Mo côté application).

## 2. Base de données

```sql
CREATE DATABASE cantine_vatel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cantine'@'localhost' IDENTIFIED BY 'mot-de-passe-solide';
GRANT ALL PRIVILEGES ON cantine_vatel.* TO 'cantine'@'localhost';
```

## 3. Code et configuration

```bash
cd /var/www
sudo git clone https://github.com/lartisse/cantine-vatel.git cantine-vatel
sudo chown -R $USER:www-data cantine-vatel && cd cantine-vatel
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```

`.env` de production (extraits) :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cantine.example.ci
APP_LOCALE=fr
APP_TIMEZONE=Africa/Abidjan

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=cantine_vatel
DB_USERNAME=cantine
DB_PASSWORD=mot-de-passe-solide

CANTINE_CUTOFF=10:00
CANTINE_TRASH_DAYS=30
PAYMENT_ENV=live          # « test » tant que les clés de production n'ont pas été testées en S5
UPLOAD_MAX_KB=5120

# Mode démo : TOUJOURS désactivé en production (barre de démo + horloge figée)
CANTINE_DEMO=false
CANTINE_DEMO_NOW=
```

> Les clés des fournisseurs de paiement ne vont **jamais** dans `.env` : le super administrateur les saisit dans l'écran S5, où elles sont chiffrées avec `APP_KEY`. Conservez `APP_KEY` précieusement : la changer rend les clés enregistrées illisibles.

```bash
php artisan migrate --force          # ajouter --seed uniquement pour une instance de démonstration
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 4. Nginx

```nginx
server {
    listen 80;
    server_name cantine.example.ci;
    root /var/www/cantine-vatel/public;
    index index.php;
    client_max_body_size 12M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
    location = /sw.js { add_header Cache-Control "no-cache"; }
    location ~ /\.(?!well-known) { deny all; }
}
```

(Apache : `DocumentRoot /var/www/cantine-vatel/public` + `AllowOverride All`, le fichier `public/.htaccess` de Laravel suffit.)

## 5. HTTPS (obligatoire pour la caméra)

Les navigateurs n'autorisent la caméra (scan du QR au comptoir A11 et liaison parent P2) **qu'en HTTPS** (ou sur `localhost`). Le service worker (mode hors ligne) l'exige aussi.

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d cantine.example.ci
```

Sans HTTPS, le comptoir reste utilisable avec la saisie du code de secours à 6 chiffres.

## 6. Planificateur (purge de la corbeille)

Une seule ligne cron pour l'utilisateur `www-data` :

```cron
* * * * * cd /var/www/cantine-vatel && php artisan schedule:run >> /dev/null 2>&1
```

Tâche planifiée : `purge-corbeille` (chaque nuit à minuit), supprime définitivement les comptes en corbeille depuis plus de `CANTINE_TRASH_DAYS` jours (modifiable en S3). Vérification : `php artisan schedule:list`.

## 7. Mise à jour

```bash
cd /var/www/cantine-vatel && git pull
composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan migrate --force && php artisan optimize
```

## 8. Lancement local de secours (démo sans serveur)

Sur n'importe quel ordinateur avec PHP 8.2 (extensions `gd`, `pdo_sqlite`, `fileinfo`, `intl`, `zip`) et Composer :

```bash
git clone https://github.com/lartisse/cantine-vatel.git cantine-vatel && cd cantine-vatel
composer install
cp .env.example .env && php artisan key:generate
# .env : DB_CONNECTION=sqlite, CANTINE_DEMO=true, CANTINE_DEMO_NOW="2026-11-24 09:40"
touch database/database.sqlite          # Windows : type nul > database\database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve                       # http://127.0.0.1:8000
```

Les CSS et bibliothèques JS sont servis depuis `public/` : aucune connexion Internet n'est nécessaire pendant la démo. `npm run build` n'est utile que si l'on modifie `resources/css`.
En dernier recours, la maquette `maquette/index.html` s'ouvre directement dans un navigateur.
