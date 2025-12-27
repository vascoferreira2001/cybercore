# CyberCore Deployment Guide

## Requirements
- PHP 8.1+ with `pdo_mysql`, `openssl`
- MySQL/MariaDB
- Web server: Apache (with .htaccess) or Nginx
- HTTPS enabled

## Structure
- Root: public website (index, hosting, solutions, pricing, contact, privacy, terms)
- `/manager`: Customer Area and Admin pages
- Assets: `/assets` (css/js/img), uploads: `/assets/uploads`

## Apache setup
- Ensure `.htaccess` is enabled (`AllowOverride All`).
- `.htaccess` includes:
  - Redirect legacy client/admin routes to `/manager`
  - Redirect `/website/*` to root equivalents
  - Custom 404 handler

## Nginx setup
- See `docs/NGINX_REWRITES.md` for matching rules.

## Config
- Database: edit `inc/db_credentials.php`
- App settings: `inc/config.php`

## Deploy steps (rsync)
1. Build manifest for integrity (optional but recommended):
   - See below "Manifest generation".
2. Sync files to server:
```
rsync -av --delete \
  --exclude='.git' --exclude='sql' --exclude='docs' \
  /path/to/cybercore/ user@server:/var/www/cybercore/
```
3. Update web root to point to deployed folder (Apache `DocumentRoot` or Nginx `root`).
4. Verify `.htaccess` or Nginx config is active.
5. Test URLs:
   - `/` (website)
   - `/manager/dashboard.php` (Customer Area)

## Manifest generation
- The `deploy/ftp-manifest.txt` contains `MD5 path` per file.
- Generate locally and upload to server for verification.

Command to generate manifest on macOS:
```
find . -type f \( -name '*.php' -o -name '*.css' -o -name '*.js' -o -name '*.svg' -o -name '.htaccess' \) \
  -not -path './.git/*' -not -path './sql/*' -not -path './docs/*' \
  -exec md5 -r {} \; | sed 's# \./# #' | sort > deploy/ftp-manifest.txt
```

## Verify deployment on server
- Open `/manager/admin/verify-deploy.php` to compare server hashes against the uploaded manifest.
- Mismatched or missing files will be listed for action.

## Post-deploy checks
- PHP error log clean
- Database connectivity working
- Auth/login flows working
- Admin menu accessible per role
 - Verify deploy integrity: `/manager/admin/verify-deploy.php`
 - System status: `/manager/admin/system-status.php`
 - SEO files: `/robots.txt` and `/sitemap.xml` present
