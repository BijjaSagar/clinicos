# ClinicOS - Hostinger Deployment Guide

## Overview

This guide explains how to deploy the ClinicOS Laravel application to Hostinger shared hosting.

## Prerequisites

- Hostinger hosting account (Business Web Hosting or higher recommended)
- SSH access enabled
- PHP 8.2+ on your Hostinger server
- MySQL database created

---

## Step 1: Prepare Your Local Files

Before uploading, run these commands locally:

```bash
cd /path/to/ClinicBill/backend

# Install PHP dependencies (if you have Composer locally)
composer install --optimize-autoloader --no-dev

# Generate application key (if not done)
php artisan key:generate
```

---

## Step 2: Database Setup on Hostinger

1. Log in to Hostinger hPanel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database:
   - Database name: `u123456789_clinicos` (Hostinger adds prefix)
   - Username: `u123456789_clinicuser`
   - Password: Generate a strong password
4. Note down these credentials

---

## Step 3: Configure .env File

Edit `backend/.env` with your Hostinger MySQL credentials:

```env
APP_NAME=ClinicOS
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://clinic0s.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_clinicos
DB_USERNAME=u123456789_clinicuser
DB_PASSWORD=your_secure_password

SANCTUM_STATEFUL_DOMAINS=clinic0s.com,www.clinic0s.com
```

---

## Step 4: Upload Files via Hostinger File Manager or FTP

### Option A: Using File Manager (Recommended for first upload)

1. Go to Hostinger hPanel → **Files** → **File Manager**
2. Navigate to `public_html/`
3. Upload the entire `backend/` folder contents here

### Option B: Using FTP

```
Host: ftp.clinic0s.com (or your FTP host)
Username: Your Hostinger FTP username
Password: Your FTP password
Port: 21
```

Upload `backend/` folder contents to `public_html/`

### Final Structure on Server

```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← Contents moved to root (see Step 5)
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
├── composer.json
└── ...
```

---

## Step 5: Configure Public Directory

Hostinger serves from `public_html/`, but Laravel expects `public/`.

**Option 1: Copy public contents to root (Recommended)**

1. Copy all files from `public_html/public/` to `public_html/`
2. Delete the now-empty `public/` folder
3. Edit `public_html/index.php`:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// CHANGE THESE PATHS - point to parent directory since we're in public_html root
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

**Option 2: Use subdomain/folder for Laravel (Alternative)**

Point your domain to `public_html/public/` via Hostinger domain settings.

---

## Step 6: Set Permissions via SSH

Connect to your server via SSH:

```bash
ssh u123456789@ssh.clinic0s.com -p 65002
```

Then run:

```bash
cd ~/public_html

# Set correct permissions
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create storage link
php artisan storage:link

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

---

## Step 7: Create .htaccess (if needed)

The `public/.htaccess` should already exist, but verify it contains:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## Step 8: Seed Initial Data (Optional)

```bash
php artisan db:seed
```

---

## Troubleshooting

### 500 Internal Server Error
```bash
# Check Laravel logs
cat storage/logs/laravel.log | tail -100

# Check permissions
chmod -R 775 storage bootstrap/cache
```

### Cannot Connect to Database
- Verify credentials in `.env`
- On Hostinger shared hosting, DB_HOST is usually `localhost`
- Check if MySQL user has proper privileges

### Routes Not Working (404)
- Ensure `.htaccess` exists in document root
- Enable mod_rewrite in Hostinger (usually enabled by default)

### Session Issues
```bash
php artisan config:clear
php artisan cache:clear
php artisan session:table
php artisan migrate
```

---

## Quick Reference

| Setting | Value |
|---------|-------|
| Domain | https://clinic0s.com |
| API URL | https://clinic0s.com/api/v1 |
| Health Check | https://clinic0s.com/api/v1/health |
| Admin Login | https://clinic0s.com/login |

---

## SSL Certificate

Hostinger provides free SSL via Let's Encrypt:
1. hPanel → **SSL/TLS** → **SSL**
2. Install for your domain
3. Enable "Force HTTPS"

---

## Cron Jobs (Optional)

For Laravel scheduled tasks, add to Hostinger cron:

```
* * * * * cd /home/u123456789/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Support

For issues, check:
1. `storage/logs/laravel.log`
2. Hostinger error logs in hPanel
3. Browser developer console for frontend issues
