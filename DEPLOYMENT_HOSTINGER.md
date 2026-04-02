# ClinicOS — Hostinger Shared Hosting Deployment Guide

> **Hosting**: Hostinger Business/Premium Shared Hosting
> **Domain**: clinic0s.com
> **SSH**: 77.37.58.0:65002 (user: u618910819)
> **DB**: u618910819_clinicos

---

## Step 1: SSH Into Your Server

```bash
ssh -p 65002 u618910819@77.37.58.0
```

Enter your SSH password when prompted.

---

## Step 2: Check PHP Version

```bash
php -v
```

You need PHP 8.2+. If it shows an older version:
- Go to **hPanel → Advanced → PHP Configuration**
- Set PHP version to **8.2**

Also check Composer:
```bash
composer --version
```

If Composer is not found, install it:
```bash
cd ~
curl -sS https://getcomposer.org/installer | php
# Use as: php composer.phar install
```

---

## Step 3: Upload the Project

### Option A: Git Clone (Recommended)
```bash
cd ~
git clone https://github.com/BijjaSagar/clinicos.git
```

### Option B: Upload via File Manager
- Zip the project locally
- Upload via hPanel → File Manager → public_html
- Extract there

---

## Step 4: Set Document Root

The Laravel app's entry point is `backend/public/index.php`. On Hostinger shared hosting, the document root is `public_html/`.

### Method: Symlink (Recommended)

```bash
# Backup existing public_html (if anything there)
mv ~/public_html ~/public_html_backup

# Create symlink from public_html → clinicos backend public
ln -s ~/clinicos/backend/public ~/public_html
```

OR if the git repo is directly in the home folder:
```bash
ln -s ~/clinicos/backend/public ~/public_html
```

### Verify:
```bash
ls -la ~/public_html
# Should show: public_html -> /home/u618910819/clinicos/backend/public
```

---

## Step 5: Install Dependencies

```bash
cd ~/clinicos/backend
composer install --no-dev --optimize-autoloader
```

If `composer` command not found:
```bash
php ~/composer.phar install --no-dev --optimize-autoloader
```

---

## Step 6: Configure Environment

```bash
cd ~/clinicos/backend
cp .env.example .env
nano .env
```

Replace with these values:

```env
APP_NAME="ClinicOS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://clinic0s.com
APP_DOMAIN=clinic0s.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u618910819_clinicos
DB_USERNAME=u618910819_clinicos
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=support@clinic0s.com
MAIL_PASSWORD=?aL7E;cdc
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=support@clinic0s.com
MAIL_FROM_NAME="ClinicOS"
```

Then generate the app key:
```bash
php artisan key:generate
```

---

## Step 7: Set Permissions

```bash
cd ~/clinicos/backend
chmod -R 775 storage bootstrap/cache
```

---

## Step 8: Run Migrations

### If you have an EXISTING database:
```bash
# First, preview what will run (dry run)
php artisan migrate --pretend

# If it looks good, run it
php artisan migrate
```

### If FRESH database:
```bash
php artisan migrate
```

> All new HIMS tables (wards, beds, IPD, pharmacy, lab) are additive — they won't touch your existing patient/appointment/billing data.

---

## Step 9: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Step 10: Set Up Cron Job

Go to **hPanel → Advanced → Cron Jobs**

Add this cron (runs every minute):
```
* * * * * cd /home/u618910819/clinicos/backend && php artisan schedule:run >> /dev/null 2>&1
```

This handles:
- WhatsApp reminders (every 30 min)
- Razorpay reconciliation (daily 2 AM)
- Database backup (daily 3 AM)

---

## Step 11: SSL Certificate

Hostinger shared hosting usually auto-provisions Let's Encrypt SSL.

Go to **hPanel → Security → SSL** → Verify SSL is active for clinic0s.com.

---

## Step 12: Add Subdomains for Clients

For each new clinic (e.g., slug `apollo`):

1. **hPanel → Domains → Subdomains** → Create `apollo.clinic0s.com`
2. Point it to the SAME folder: `/home/u618910819/clinicos/backend/public`
3. The `SubdomainTenant` middleware will auto-resolve `apollo` → correct clinic

Alternatively in hPanel DNS Zone Editor:
- Add **A record**: `*.clinic0s.com` → `77.37.58.0`
- This may work on Hostinger Business plan (wildcard DNS)

---

## Step 13: Verify Everything

```bash
# Check health
curl https://clinic0s.com/health

# Check migrations ran
php artisan migrate:status

# Check routes
php artisan route:list | head -20

# Check logs for errors
tail -20 storage/logs/laravel.log
```

---

## Troubleshooting

### 500 Internal Server Error
```bash
# Check Laravel log
tail -50 ~/clinicos/backend/storage/logs/laravel.log

# Check permissions
chmod -R 775 storage bootstrap/cache

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### "Class not found" errors
```bash
composer dump-autoload --optimize
```

### .htaccess issues
Ensure `~/clinicos/backend/public/.htaccess` exists. If not:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Database connection refused
- Verify DB credentials in hPanel → Databases
- Hostinger uses `localhost` (not 127.0.0.1)
- DB username format: `u618910819_clinicos`

---

## Daily Operations

### Manual backup
```bash
cd ~/clinicos/backend && php artisan backup:database
```

### Deploy updates
```bash
cd ~/clinicos/backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Check backup files
```bash
ls -la ~/clinicos/backend/storage/app/backups/
```

---

## Adding a New Clinic (Step by Step)

1. Login as Super Admin at `https://clinic0s.com/admin`
2. Go to Clinics → Add Clinic
3. Fill: name, slug (e.g. `apollo`), facility type, licensed beds, HIMS features
4. In Hostinger hPanel → Subdomains → Add `apollo.clinic0s.com` → same folder
5. Done — client accesses `https://apollo.clinic0s.com`

---

*Last updated: April 2, 2026*
