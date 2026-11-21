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

# Sessions : cookies chiffrés, envoyés uniquement en HTTPS, inaccessibles au JavaScript
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
BCRYPT_ROUNDS=12
```

En production (`APP_ENV=production`), l'application force aussi les liens en HTTPS, active les cookies sécurisés si `SESSION_SECURE_COOKIE` n'est pas renseigné, et exige des mots de passe d'au moins 10 caractères (lettres et chiffres) absents des fuites connues (vérification anonymisée auprès de *Have I Been Pwned*). Chaque réponse porte des en-têtes de sécurité : `Content-Security-Policy` (aucune source externe), `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` (caméra limitée au site) et `Strict-Transport-Security` en HTTPS.

> Les clés des fournisseurs de paiement ne vont **jamais** dans `.env` : le super administrateur les saisit dans l'écran S5, où elles sont chiffrées avec `APP_KEY`. Conservez `APP_KEY` précieusement : la changer rend les clés enregistrées illisibles.
>
> **Pas de données de démonstration en production** : le seeder crée des comptes au mot de passe connu (`password`), il refuse donc de s'exécuter quand `APP_ENV=production`. Le premier super administrateur se crée avec `php artisan cantine:super-admin` ; il crée ensuite l'équipe dans *Super admin › Équipe* (mot de passe provisoire à changer à la première connexion).
>
> Le serveur d'envoi des e-mails (SMTP) se configure de la même façon, dans **Super admin › Paramètres de l'application** : une fois activée, cette configuration remplace les variables `MAIL_*` du `.env` (qui restent la valeur de secours). Le mot de passe SMTP est chiffré avec `APP_KEY`. Le bouton « Envoyer un e-mail de test » vérifie la connexion.

```bash
php artisan migrate --force
php artisan cantine:super-admin direction@exemple.ci --prenom=Prénom --nom=Nom   # premier super administrateur (mot de passe demandé, masqué)
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
# .env : DB_CONNECTION=sqlite (valeur par défaut de .env.example)
touch database/database.sqlite          # Windows : type nul > database\database.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve                       # http://127.0.0.1:8000
```

Les CSS et bibliothèques JS sont servis depuis `public/` : aucune connexion Internet n'est nécessaire pendant la démo. `npm run build` n'est utile que si l'on modifie `resources/css`.
En dernier recours, la maquette `maquette/index.html` s'ouvre directement dans un navigateur.

## 9. Hébergement mutualisé LWS (formule Starter) — `cantinevatel.lartisse.com`

Sur LWS, le dossier d'un sous-domaine est fixe (`htdocs/cantinevatel.lartisse.com/`) et ne peut pas pointer vers `public/`. Le fichier **`.htaccess` à la racine du projet** (fourni dans le dépôt) renvoie donc tout le site vers `public/`, force le HTTPS et refuse l'accès direct aux fichiers sensibles (`.env`, `composer.json`, `artisan`…). La formule Starter inclut l'accès SSH (avec Composer et Git), le choix de la version PHP et les tâches cron.

### 9.1 Dans le panneau LWS (une seule fois)

1. **Sous-domaine** : créer `cantinevatel.lartisse.com` (son dossier `htdocs/cantinevatel.lartisse.com/` est créé automatiquement).
2. **Certificat SSL** : l'activer pour `cantinevatel.lartisse.com` **avant** de déployer — le `.htaccess` redirige tout vers `https://` (sinon, commenter temporairement les 3 lignes « Forcer HTTPS »).
3. **PHP** : *Bases de données & PHP › Configuration PHP* → **PHP 8.3 ou plus** pour ce sous-domaine ; vérifier `gd`, `intl`, `pdo_mysql`, `mbstring`, `fileinfo`, `zip` ; `upload_max_filesize` ≥ 6M, `post_max_size` ≥ 12M.
4. **Base MySQL** : en créer une, noter hôte, nom, utilisateur, mot de passe.
5. **SSH** : *Logiciels › Accès SSH* → ajouter sa clé publique, cocher « Activer », durée « Illimitée », « Authentification par clé uniquement ». Le panneau affiche l'hôte et l'identifiant.

### 9.2 Premier déploiement (en SSH)

```bash
cd htdocs/cantinevatel.lartisse.com
pwd                                   # notez ce chemin complet : il servira pour la tâche cron
ls -la                                # le dossier doit être vide (supprimez la page par défaut de LWS s'il y en a une)
git clone https://github.com/lartisse/cantine-vatel.git .
cp .env.example .env
nano .env                             # voir les valeurs ci-dessous
bash scripts/deploy-lws.sh            # vérifie PHP, installe, migre la base, crée les caches
php artisan cantine:super-admin direction@lartisse.com --prenom=Prénom --nom=Nom
```

Valeurs du `.env` à renseigner :

```dotenv
APP_NAME="Cantine de Vatel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cantinevatel.lartisse.com
APP_TIMEZONE=Africa/Abidjan

DB_CONNECTION=mysql
DB_HOST=…            # fourni par le panneau LWS
DB_PORT=3306
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
PAYMENT_ENV=test
```

`APP_KEY` est générée automatiquement par le script. Les e-mails (SMTP), les moyens de paiement, les liens de l'application mobile et les classes se règlent ensuite dans **Super admin › Paramètres de l'application** et **Moyens de paiement**.

### 9.3 Tâche planifiée (purge de la corbeille)

*Bases de données & PHP › Tâches cron* → nouvelle tâche **quotidienne à 00:00** (remplacer le chemin par celui affiché par `pwd`) :

```bash
php /chemin/affiché/par/pwd/artisan schedule:run
```

Une exécution par jour suffit : la seule tâche planifiée est la purge quotidienne. LWS limite une tâche à 240 s, ce qui est largement suffisant.

### 9.4 Mettre à jour

```bash
cd htdocs/cantinevatel.lartisse.com && bash scripts/deploy-lws.sh
```

Le script fait `git pull`, `composer install --no-dev`, les migrations et recrée les caches.

### 9.5 Instance de démonstration pour le jury (facultatif)

Le seeder refuse de créer les comptes de démonstration (mot de passe `password`) en production. Pour une instance **réservée à la démo**, on peut le forcer, puis remettre la vraie configuration ensuite :

```bash
ALLOW_DEMO_SEED=true php artisan migrate:fresh --seed --force   # efface toute la base !
```

Ne laissez jamais ces comptes sur le site réel : supprimez-les ou relancez `php artisan migrate:fresh --force` puis `php artisan cantine:super-admin`.

### 9.6 En cas de problème

| Symptôme | Cause probable |
| --- | --- |
| Erreur 500 dès l'accueil | `.env` absent ou `APP_KEY` vide, droits d'écriture sur `storage/` ou `bootstrap/cache/` — voir `storage/logs/laravel.log` |
| Page blanche ou liste des fichiers | Réécriture désactivée : vérifier que le `.htaccess` racine a bien été copié (`ls -la`) |
| Boucle de redirection ou « site inaccessible » | Certificat SSL pas encore actif : l'activer, ou commenter les lignes « Forcer HTTPS » du `.htaccess` |
| Photos non affichées | Lien `public/storage` absent : `php artisan storage:link` |
| Caméra du comptoir refusée | Le site doit être ouvert en `https://` |
