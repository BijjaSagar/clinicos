# ClinicOS Deployment Guide for Hostinger PHP Server

## Domain: https://clinic0s.com

---

## Step 1: Prepare Your Local Files

### Build React Frontend
```bash
cd /Users/akash/Downloads/ClinicBill/web
npm install
npm run build
```
This creates a `dist/` folder with your production-ready React app.

---

## Step 2: Upload Files to Hostinger

### Option A: Using File Manager (Easiest)

1. **Login to Hostinger hPanel**
2. Go to **Files → File Manager**
3. Navigate to `public_html/`

#### Upload React Frontend:
- Upload all contents of `web/dist/` directly to `public_html/`
  - `index.html`
  - `assets/` folder

#### Upload Laravel Backend:
- Create folder `public_html/api/`
- Upload the entire `backend/` folder contents to `public_html/api/`

### Option B: Using FTP
Use FileZilla or similar FTP client with your Hostinger FTP credentials.

---

## Step 3: Configure Laravel Backend

### 3.1 Create/Edit `.env` file in `public_html/api/`

```env
APP_NAME=ClinicOS
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://clinic0s.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# DATABASE - Get these from Hostinger hPanel → Databases
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_clinicos
DB_USERNAME=u123456789_clinicos
DB_PASSWORD=YOUR_DATABASE_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=clinic0s.com,www.clinic0s.com
```

### 3.2 Run Laravel Commands via SSH or Hostinger Terminal

1. Go to **Hostinger hPanel → Advanced → SSH Access**
2. Enable SSH and connect
3. Run these commands:

```bash
cd public_html/api

# Install dependencies
php composer.phar install --no-dev --optimize-autoloader

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 4: Configure .htaccess Files

### 4.1 Main `.htaccess` in `public_html/.htaccess`

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # API requests go to Laravel
    RewriteRule ^api/(.*)$ api/public/index.php [L]
    
    # Don't rewrite existing files
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]
    
    # React SPA - send all other requests to index.html
    RewriteRule ^ index.html [L]
</IfModule>
```

### 4.2 Laravel `.htaccess` in `public_html/api/public/.htaccess`

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## Step 5: Database Setup

### 5.1 Create Database in Hostinger
1. Go to **Hostinger hPanel → Databases → MySQL Databases**
2. Click **Create New Database**
3. Note down:
   - Database name
   - Username
   - Password

### 5.2 Import Schema (Optional - if not using migrations)
1. Go to **Databases → phpMyAdmin**
2. Select your database
3. Click **Import**
4. Upload `database/clinicos_schema.sql`

---

## Step 6: Set Folder Permissions

Via SSH or File Manager, set these permissions:

```bash
cd public_html/api
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

---

## Step 7: Test Your Deployment

1. **Frontend:** https://clinic0s.com
2. **API Health Check:** https://clinic0s.com/api/v1/health
3. **Login Page:** https://clinic0s.com/login

---

## Troubleshooting

### Common Issues:

1. **500 Internal Server Error**
   - Check `.env` file exists
   - Check folder permissions
   - Check PHP version (needs 8.1+)

2. **API Not Working**
   - Check `.htaccess` files
   - Verify database connection

3. **CORS Errors**
   - Add to `api/config/cors.php`:
   ```php
   'allowed_origins' => ['https://clinic0s.com'],
   ```

4. **Blank Page**
   - Clear browser cache
   - Check browser console for errors

---

## File Structure After Deployment

```
public_html/
├── .htaccess                 ← Main routing rules
├── index.html                ← React app entry
├── assets/
│   ├── index-xxxxx.js
│   └── index-xxxxx.css
└── api/
    ├── app/
    ├── config/
    ├── database/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env                  ← Laravel environment
    ├── artisan
    ├── composer.json
    └── public/
        ├── .htaccess         ← Laravel routing
        └── index.php         ← Laravel entry point
```

---

## Quick Reference

| URL | Purpose |
|-----|---------|
| https://clinic0s.com | React Dashboard |
| https://clinic0s.com/login | Login Page |
| https://clinic0s.com/api/v1/* | Laravel API |
| https://clinic0s.com/api/v1/health | API Health Check |
