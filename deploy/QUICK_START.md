# 🚀 Quick Deployment Guide - CyberCore on Plesk

## Pre-Deployment Setup (5 minutes)

### 1. Configure Environment
```bash
# Copy and edit .env
cp .env.example .env
nano .env

# Update these critical values:
APP_ENV=production
APP_DEBUG=false
DB_PASS=your_secure_password
PLESK_API_URL=https://your-plesk:8443
PLESK_API_KEY=your_api_key
```

### 2. Import Database
```bash
# Create database in Plesk first, then:
mysql -u cybercore -p cybercore < sql/schema.sql

# Verify
mysql -u cybercore -p -e "USE cybercore; SHOW TABLES;"
```

### 3. Set File Permissions
```bash
# Run the permission script
cd /var/www/vhosts/yourdomain.com/httpdocs
bash deploy/set-permissions.sh
```

### 4. Setup Backups
```bash
# Edit backup scripts with your credentials
nano deploy/backup-database.sh  # Update DB_PASS
nano deploy/backup-files.sh     # Update SOURCE_DIR

# Add to crontab
crontab -e

# Add these lines:
0 2 * * * /path/to/backup-database.sh >> /var/log/cybercore-backup.log 2>&1
0 3 * * 0 /path/to/backup-files.sh >> /var/log/cybercore-backup.log 2>&1
```

---

## Security Verification (2 minutes)

```bash
# Test HTTPS redirect
curl -I http://yourdomain.com  # Should return 301

# Verify protected directories
curl -I https://yourdomain.com/sql/  # Should return 403
curl -I https://yourdomain.com/inc/  # Should return 403

# Check security headers
curl -I https://yourdomain.com | grep -E "(X-Frame|X-XSS|Content-Security)"

# Verify .env is blocked
curl -I https://yourdomain.com/.env  # Should return 403
```

---

## Post-Deployment Tasks (5 minutes)

### 1. Create Admin User
```sql
mysql -u cybercore -p cybercore

INSERT INTO users (identifier, email, password_hash, first_name, last_name, role, email_verified, created_at, updated_at) 
VALUES ('CYC000001', 'admin@yourdomain.com', '$2y$12$HASH_HERE', 'Admin', 'User', 'Gestor', 1, NOW(), NOW());
```

Generate password hash:
```bash
php -r "echo password_hash('YourSecurePassword', PASSWORD_BCRYPT, ['cost' => 12]);"
```

### 2. Test Core Functions
- [ ] Register new user
- [ ] Email verification
- [ ] Login/logout
- [ ] Order service
- [ ] Create invoice
- [ ] Open ticket
- [ ] Admin panel access

### 3. Monitor Logs
```bash
# Watch for errors
tail -f /var/www/vhosts/yourdomain.com/logs/php_error.log
tail -f /var/www/vhosts/yourdomain.com/logs/error_log
```

---

## Quick Reference

**Admin Panel:** https://yourdomain.com/admin/dashboard.php

**Database:** cybercore (user: cybercore_prod)

**Logs:** `/var/www/vhosts/yourdomain.com/logs/`

**Backups:** `/var/backups/cybercore/`

**Config Files:**
- `.htaccess` - Security & routing
- `.user.ini` - PHP settings
- `.env` - Environment variables

---

## Troubleshooting

**500 Error:**
- Check logs: `tail -f logs/error_log`
- Verify `.env` exists with correct values
- Check database connection

**403 Error:**
- File permissions: `ls -la`
- Check `.htaccess` syntax

**Database Connection Failed:**
- Verify credentials in `.env`
- Test: `mysql -u cybercore -p`

**Emails Not Sending:**
- Check SMTP settings in `.env`
- Verify SPF/DKIM records
- Test with: `php -r "mail('test@example.com', 'Test', 'Body');"`

---

For full checklist, see: [deploy/PRODUCTION_CHECKLIST.md](deploy/PRODUCTION_CHECKLIST.md)
