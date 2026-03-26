# ClinicOS — Hostinger Deployment Guide

## Prerequisites
- Hostinger Business/Cloud hosting (PHP 8.2+)
- MySQL 8.0 database created in Hostinger hPanel
- SSH access or File Manager access
- Composer installed on your local machine

---

## Folder Structure on Hostinger

Your Hostinger File Manager should look like this after deployment:

```
/home/username/
├── public_html/          ← LARAVEL PUBLIC FOLDER CONTENTS GO HERE
│   ├── index.php
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   └── favicon.ico
└── clinicos/             ← REST OF LARAVEL PROJECT GOES HERE
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    └── artisan
```

---

## Step-by-Step Deployment

### 1. Prepare Files Locally

```bash
cd backend/
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Upload to Hostinger

- Upload **ALL contents** of `backend/` **EXCEPT the `public/` folder** to `/home/username/clinicos/`
- Upload **contents** of `backend/public/` to `/home/username/public_html/`

You can use:
- Hostinger File Manager (zip upload then extract)
- FTP client (FileZilla, Cyberduck)
- SSH + rsync (fastest)

```bash
# Via rsync (SSH)
rsync -avz --exclude='public' backend/ username@yourhost.hostinger.com:/home/username/clinicos/
rsync -avz backend/public/ username@yourhost.hostinger.com:/home/username/public_html/
```

### 3. Update index.php on the Server

Edit `/home/username/public_html/index.php` and change these two lines:

```php
// BEFORE (default Laravel paths — relative to public/)
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

To:

```php
// AFTER (point to clinicos/ folder one level up from public_html)
require __DIR__.'/../clinicos/vendor/autoload.php';
$app = require_once __DIR__.'/../clinicos/bootstrap/app.php';
```

Also update the maintenance mode check line:

```php
// AFTER
if (file_exists($maintenance = __DIR__.'/../clinicos/storage/framework/maintenance.php')) {
```

### 4. Configure .env

Copy `.env.example` to `.env` in `/home/username/clinicos/` and fill in:

```env
APP_NAME="ClinicOS"
APP_ENV=production
APP_KEY=                          # Generated in step 6
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="ClinicOS"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=

RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=

SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

### 5. Set Permissions (via SSH)

```bash
chmod -R 755 /home/username/clinicos/
chmod -R 775 /home/username/clinicos/storage/
chmod -R 775 /home/username/clinicos/bootstrap/cache/
```

### 6. Generate App Key

```bash
cd /home/username/clinicos/
php artisan key:generate
```

### 7. Run Migrations + Seeders

```bash
cd /home/username/clinicos/
php artisan migrate --force
php artisan db:seed --force
```

### 8. Set up Storage Link

```bash
php artisan storage:link
```

This creates a symlink from `public_html/storage` → `clinicos/storage/app/public`.

### 9. Clear and Re-cache Everything

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Hostinger PHP Settings (via hPanel > PHP Configuration)

Add these to your `php.ini` or Custom PHP Settings:

```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
```

Required PHP extensions (enable in hPanel):
- `pdo_mysql`
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`
- `gd` or `imagick`

---

## Domain Configuration

1. In hPanel > Domains, point your domain's **document root** to `public_html/`
2. Enable **SSL certificate** (Let's Encrypt — free in hPanel)
3. Enable **Force HTTPS** redirect

---

## Cron Jobs (for scheduled tasks)

In hPanel > Advanced > Cron Jobs, add:

```
* * * * * /usr/local/bin/php /home/username/clinicos/artisan schedule:run >> /dev/null 2>&1
```

This powers:
- Appointment reminders (WhatsApp/SMS)
- Queue processing (when using `database` driver)
- Activity log cleanup

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| **500 Error** | Check `clinicos/storage/logs/laravel.log`, verify storage/ permissions |
| **White page / blank** | Set `APP_DEBUG=true` temporarily, check logs |
| **Database connection error** | Verify DB credentials in `.env`, check DB host (usually `localhost`) |
| **Class not found** | Run `composer dump-autoload` |
| **Views not loading** | Run `php artisan view:clear && php artisan view:cache` |
| **Routes returning 404** | Run `php artisan route:cache`, verify `.htaccess` is uploaded |
| **CORS errors** | Verify `SANCTUM_STATEFUL_DOMAINS` in `.env` |
| **File upload fails** | Check `upload_max_filesize` in PHP settings, verify `storage/` is writable |
| **Token mismatch** | Run `php artisan config:clear && php artisan cache:clear` |

---

## Updating the Application

```bash
# Local — rebuild optimised assets
composer install --optimize-autoloader --no-dev

# Upload changed files via FTP/rsync

# On server (via SSH)
cd /home/username/clinicos/
php artisan down                 # Enable maintenance mode
php artisan migrate --force      # Run new migrations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up                   # Disable maintenance mode
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is set and unique
- [ ] `.env` file is NOT accessible from the web (`.htaccess` blocks it)
- [ ] `vendor/`, `storage/`, `bootstrap/cache/` are outside `public_html/`
- [ ] SSL certificate is active and HTTPS is forced
- [ ] Database user has only the necessary privileges (no SUPER/GRANT)
- [ ] `composer.json` is NOT accessible from web
- [ ] Razorpay keys are production keys, not test keys
