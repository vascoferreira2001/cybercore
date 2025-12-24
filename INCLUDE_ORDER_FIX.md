# Include Order Fix - Verification Checklist

## Database Configuration Chain

```
config.php (loads credentials)
  └─ db.php (creates PDO connection)
    └─ All other files use getDB()
```

## Files Fixed - Include Order Verification

### ✓ Core Helper Files
- [x] **inc/config.php** - Load order: 1) db_credentials.php, 2) env vars, 3) defaults
- [x] **inc/db.php** - Load order: config.php → PDO connection
- [x] **inc/auth.php** - Load order: config.php → db.php (FIXED: added config.php)
- [x] **inc/mailer.php** - Load order: config.php → db.php → settings.php

### ✓ Public Pages (Root Level)
- [x] login.php - Load order: config.php → db.php → csrf → settings
- [x] register.php - Load order: config.php → db.php → (other includes)
- [x] forgot_password.php - Load order: config.php → db.php → (other includes)
- [x] reset_password.php - Load order: config.php → db.php → (other includes)
- [x] hosting.php - Load order: config.php → db.php (FIXED)
- [x] services.php - Load order: config.php → db.php (FIXED)
- [x] servers.php - Load order: config.php → db.php (FIXED)
- [x] dashboard.php - Load order: auth.php (which loads config.php) → db.php
- [x] domains.php - Load order: auth.php (which loads config.php) → db.php
- [x] domains_edit.php - Load order: auth.php (which loads config.php) → db.php
- [x] updates.php - Load order: auth.php (which loads config.php) → db.php
- [x] finance.php - Load order: auth.php (which loads config.php)
- [x] logs.php - Load order: auth.php (which loads config.php)
- [x] manage_users.php - Load order: auth.php (which loads config.php)
- [x] support.php - Load order: auth.php (which loads config.php)

### ✓ Admin Panel Pages (admin/)
All 23 admin files follow the pattern:
- [x] Load order: auth.php (which loads config.php) → other includes

Files verified:
- admin/dashboard.php, admin/customers.php, admin/settings.php, admin/tickets.php
- admin/expenses.php, admin/updates.php, admin/payment-warnings.php
- admin/payments.php, admin/services.php, admin/alerts.php, admin/contracts.php
- admin/documents.php, admin/knowledge-base.php, admin/licenses.php
- admin/live-chat.php, admin/manage_users.php, admin/notes.php
- admin/quotes.php, admin/reports.php, admin/schedule.php
- admin/system-logs.php, admin/tasks.php, admin/team.php

### ✓ Cron and Scripts
- [x] cron.php - Load order: config.php → db.php → settings.php (already correct)
- [x] scripts/migrate.php - Load order: config.php → db.php (FIXED)
- [x] scripts/sample_users.php - Load order: config.php → db.php (FIXED)

## Testing Steps

### 1. Diagnostic Test
```
URL: https://cybercore.cyberworld.pt/test_db.php
Expected: "All tests passed!"
Tests:
  ✓ config.php loads and defines DB_* constants
  ✓ PDO connection created successfully
  ✓ Query executed (SELECT COUNT(*) FROM users)
  ✓ getSetting() function works
```

### 2. Login Test
```
URL: https://cybercore.cyberworld.pt/login.php
Expected: Login form displays (no connection errors)
Test login with valid credentials
```

### 3. Admin Dashboard Test
```
URL: https://cybercore.cyberworld.pt/admin/dashboard.php
Expected: Dashboard loads (after successful login)
Verifies auth.php fix is working
```

### 4. Settings Page Test
```
URL: https://cybercore.cyberworld.pt/admin/settings.php
Expected: Settings page loads with current values
Tests getSetting() function via admin panel
```

## Error Diagnosis

### If "using password: NO" still appears:

1. **Check credentials file exists on server:**
   ```bash
   ls -la /var/www/cybercore/inc/db_credentials.php
   # Should exist with proper permissions
   ```

2. **Verify credentials are readable:**
   ```bash
   cat /var/www/cybercore/inc/db_credentials.php
   # Should contain: define('DB_HOST', ...); define('DB_USER', ...); etc.
   ```

3. **Test MySQL connection directly:**
   ```bash
   mysql -h 127.0.0.1 -u cybercore -p'RPd3knB&ofbh8g9_' cybercore
   # Should connect successfully
   ```

4. **Check PHP error logs:**
   ```bash
   tail -50 /var/log/php-fpm/error.log
   # or: tail -50 /var/log/apache2/error.log
   ```

5. **Verify include_path in PHP config:**
   ```bash
   php -i | grep include_path
   # Should include current directory or absolute paths
   ```

## Database Credentials Setup

### Server Setup (One-time)

1. Create credentials file on server (not in Git):
```bash
ssh user@cybercore.cyberworld.pt
cd /var/www/cybercore

cat > inc/db_credentials.php << 'EOF'
<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cybercore');
define('DB_USER', 'cybercore');
define('DB_PASS', 'RPd3knB&ofbh8g9_');
define('SITE_URL', 'https://cybercore.cyberworld.pt');
define('SITE_NAME', 'CyberCore - Área de Cliente');
?>
EOF

chmod 600 inc/db_credentials.php
```

2. Verify it's in .gitignore:
```bash
grep 'db_credentials.php' .gitignore
# Should return: inc/db_credentials.php
```

3. Import schema (one-time):
```bash
mysql -u cybercore -p'RPd3knB&ofbh8g9_' cybercore < sql/schema.sql
```

4. Create sample users (optional):
```bash
php scripts/sample_users.php
```

## Security Checklist

- [x] Database credentials in `inc/db_credentials.php` (not in Git)
- [x] Fallback to environment variables configured
- [x] CSRF protection implemented
- [x] Session cookies hardened (HttpOnly, SameSite=Strict)
- [x] Prepared statements used throughout
- [x] Password hashing with bcrypt
- [x] File permissions set correctly (600 for db_credentials.php)

## Deployment Notes

When deploying to production:

1. Never commit `inc/db_credentials.php` to Git
2. Create it manually on the server with production credentials
3. Or use environment variables (DB_HOST, DB_NAME, DB_USER, DB_PASS)
4. Verify `.gitignore` includes:
   ```
   inc/db_credentials.php
   assets/uploads/*
   ```

## Changes Summary

Total files modified: **7 core files + 1 diagnostic**
- 1 core include file fix (auth.php)
- 5 page/script fixes (hosting.php, services.php, servers.php, migrate.php, sample_users.php)
- 1 diagnostic script created (test_db.php)

All 23 admin files automatically fixed via auth.php
All 13 other public pages verified as correct
