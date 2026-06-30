# VILT Deployment Guide — ProcureX

**Stack:** Vue, Inertia, Laravel, Tailwind  
**Database:** SQLite  
**Domain:** https://procurex.site  
**VPS IP:** 153.75.247.227  
**OS:** Ubuntu 22.04 / 24.04

---

## Table of Contents

1. [GitHub Secrets](#a-github-secrets)
2. [Server Provisioning](#b-server-provisioning)
3. [Nginx Site Config](#c-nginx-site-config)
4. [First Manual Deploy](#d-first-manual-deploy)
5. [Supervisor — Queue Worker](#e-supervisor--queue-worker)
6. [Crontab — Scheduler](#f-crontab--scheduler)
7. [SSL (Certbot)](#g-ssl-certbot)
8. [Trigger Deploy](#h-trigger-deploy)

---

## A. GitHub Secrets

Set these in your repo **Settings → Secrets and variables → Actions**:

| Secret | Value |
|---|---|
| `VPS_HOST` | `153.75.247.227` |
| `VPS_USER` | `deploy` |
| `VPS_SSH_KEY` | Private SSH key of the `deploy` user |
| `VPS_PORT` | `22` |
| `VPS_DEPLOY_PATH` | `/var/www/procurex` |

---

## B. Server Provisioning

SSH in as **root**:

```bash
ssh root@153.75.247.227
```

### 1. System update & deploy user

```bash
apt update && apt upgrade -y
adduser deploy
usermod -aG sudo deploy
```

Re‑SSH as `deploy` for all remaining steps:

```bash
ssh deploy@153.75.247.227
```

### 2. PHP 8.3 + extensions

```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.3-fpm php8.3-cli php8.3-{mbstring,xml,curl,zip,sqlite3,intl,bcmath,gd} -y
sudo systemctl enable --now php8.3-fpm
```

### 3. Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

### 4. Node.js 22

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install nodejs -y
```

### 5. SQLite

```bash
sudo apt install sqlite3 -y
```

### 6. Nginx

```bash
sudo apt install nginx -y
sudo systemctl enable --now nginx
```

### 7. Supervisor

```bash
sudo apt install supervisor -y
sudo systemctl enable --now supervisor
```

### 8. Chromium (Puppeteer / PDF generation)

```bash
sudo apt install chromium-browser -y
```

---

## C. Nginx Site Config

```bash
sudo nano /etc/nginx/sites-available/procurex
```

```nginx
server {
    listen 80;
    server_name procurex.site www.procurex.site;
    root /var/www/procurex/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/procurex /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## D. First Manual Deploy

### Clone the repository

```bash
sudo mkdir -p /var/www/procurex
sudo chown deploy:deploy /var/www/procurex
cd /var/www/procurex
git clone <your-repo-url> .
```

### Environment

```bash
cp .env.example .env
touch database/database.sqlite
chmod 664 database/database.sqlite
```

Edit `.env`:

```bash
nano .env
```

Set these key values:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://procurex.site

DB_CONNECTION=sqlite

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### First build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan queue:table
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### Permissions

```bash
sudo chown -R deploy:www-data .
sudo chmod -R 755 storage bootstrap/cache
sudo chmod 664 database/database.sqlite
```

---

## E. Supervisor — Queue Worker

```bash
sudo nano /etc/supervisor/conf.d/procurex-queue.conf
```

```ini
[program:procurex-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/procurex/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/procurex/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Start the worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

Check status:

```bash
sudo supervisorctl status
```

---

## F. Crontab — Scheduler

```bash
crontab -e
```

Add:

```
* * * * * cd /var/www/procurex && php artisan schedule:run >> /dev/null 2>&1
```

---

## G. SSL (Certbot)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d procurex.site -d www.procurex.site
```

Verify auto‑renewal:

```bash
sudo certbot renew --dry-run
```

---

## H. Trigger Deploy

After all secrets are set and the server is provisioned, push to `main`:

```bash
git push origin main
```

GitHub Actions runs the deploy workflow (`.github/workflows/deploy.yml`) which:

1. SSHs into the VPS
2. Pulls the latest code
3. Installs PHP deps (`composer install --no-dev`)
4. Builds frontend (`npm ci && npm run build`)
5. Runs migrations (`php artisan migrate --force`)
6. Refreshes caches (`php artisan optimize`)
7. Ensures storage symlink
8. Restarts queue workers

---

## Troubleshooting

| Issue | Likely fix |
|---|---|
| Blank page / 500 | Check `storage/logs/laravel.log` |
| Vite manifest error | Missing `npm run build` — run it on server |
| SQLite permission denied | `chmod 664 database/database.sqlite`, owner must be `www-data` group |
| Queue jobs not processing | `sudo supervisorctl status` — ensure worker is running |
| File uploads fail | `chmod -R 775 storage` and verify `post_max_size` / `upload_max_filesize` in `php.ini` |
| Puppeteer / PDF blank | Install Chromium, set `puppeteer.chromium_path` in config |
