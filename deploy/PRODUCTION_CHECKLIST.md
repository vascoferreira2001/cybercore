# 🚀 Production Deployment Checklist - CyberCore Hosting Platform

## ✅ Pre-Deployment

### 1. Environment Configuration
- [ ] Create `.env` file from `.env.example`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate secure `APP_KEY` (32+ random characters)
- [ ] Configure database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
- [ ] Add Plesk API credentials (PLESK_API_URL, PLESK_API_KEY)
- [ ] Configure SMTP settings for email notifications
- [ ] Set correct `BASE_URL` (https://yourdomain.com)

### 2. Database Setup
```bash
# Import schema
mysql -u cybercore -p cybercore < sql/schema.sql

# Verify tables created
mysql -u cybercore -p -e "USE cybercore; SHOW TABLES;"

# Check table structure
mysql -u cybercore -p -e "USE cybercore; DESCRIBE users;"
```

### 3. File Permissions (Plesk)
```bash
# Navigate to httpdocs
cd /var/www/vhosts/yourdomain.com/httpdocs

# Set correct ownership
chown -R username:psacln .

# Directories: 755
find . -type d -exec chmod 755 {} \;

# Files: 644
find . -type f -exec chmod 644 {} \;

# Writable directories: 775
chmod 775 assets/uploads
chmod 775 logs

# Sensitive files: 600
chmod 600 .env
chmod 600 .user.ini
chmod 600 config/database.php
chmod 600 inc/db_credentials.php

# Executable scripts (if any)
# chmod 755 scripts/*.sh
```

### 4. Security Files
- [ ] Upload `.htaccess` (root directory)
- [ ] Upload `.user.ini` (root directory)
- [ ] Ensure `.env` is NOT in public repository
- [ ] Add `.env` to `.gitignore`

---

## 🔒 Security Hardening

### 1. File Protection
- [ ] Verify `.htaccess` is active (check for 403 on /sql/)
- [ ] Test that `/inc/` directory is blocked
- [ ] Verify `/config/` directory is blocked
- [ ] Test PHP execution disabled in `/assets/uploads/`
- [ ] Confirm `.env` returns 403/404

### 2. HTTPS Configuration
- [ ] SSL certificate installed in Plesk
- [ ] Force HTTPS redirect active in `.htaccess`
- [ ] Test HTTP → HTTPS redirect
- [ ] Verify SSL certificate validity
- [ ] Enable HSTS header (after testing)

### 3. Database Security
```sql
-- Create production database user with limited privileges
CREATE USER 'cybercore_prod'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

-- Grant only necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON cybercore.* TO 'cybercore_prod'@'localhost';

-- NO DROP, CREATE, ALTER in production
FLUSH PRIVILEGES;
```

### 4. Session Security
- [ ] Verify `session.cookie_secure = 1` in `.user.ini`
- [ ] Test session regeneration on login
- [ ] Verify CSRF tokens working
- [ ] Test logout functionality

---

## 🔧 Configuration Updates

### 1. Update Database Credentials
Edit `inc/db_credentials.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'cybercore_prod');
define('DB_PASS', 'YOUR_PRODUCTION_PASSWORD');
define('DB_NAME', 'cybercore');
```

### 2. Update Base URLs
Edit `config/config.php`:
```php
define('BASE_URL', 'https://yourdomain.com');
define('APP_ENV', 'production');
```

### 3. Error Logging Path
Update `.user.ini`:
```ini
error_log = /var/www/vhosts/yourdomain.com/logs/php_error.log
```

Create logs directory:
```bash
mkdir -p /var/www/vhosts/yourdomain.com/logs
chmod 775 /var/www/vhosts/yourdomain.com/logs
```

---

## 📧 Email Configuration

### 1. SMTP Settings in Database
```sql
INSERT INTO settings (setting_key, setting_value) VALUES
('smtp_host', 'smtp.yourdomain.com'),
('smtp_port', '587'),
('smtp_username', 'noreply@yourdomain.com'),
('smtp_password', 'YOUR_SMTP_PASSWORD'),
('smtp_encryption', 'tls'),
('from_email', 'noreply@yourdomain.com'),
('from_name', 'CyberCore');
```

### 2. Test Email Sending
- [ ] Test registration email
- [ ] Test password reset email
- [ ] Test ticket notification email
- [ ] Verify SPF/DKIM records configured

---

## 🧪 Testing

### 1. Functionality Tests
- [ ] User registration with email verification
- [ ] Login/logout functionality
- [ ] Password reset flow
- [ ] Service ordering
- [ ] Invoice creation
- [ ] Ticket system (open, reply, close)
- [ ] Admin panel access (correct roles)
- [ ] CSRF protection on all forms

### 2. Security Tests
- [ ] SQL injection attempts blocked
- [ ] XSS attempts blocked
- [ ] File upload restrictions working
- [ ] Directory listing disabled
- [ ] Sensitive files blocked (403)
- [ ] Admin pages require authentication

### 3. Performance Tests
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] OPcache enabled and working
- [ ] Gzip compression active
- [ ] Static assets cached

---

## 💾 Backup Strategy

### 1. Database Backups
```bash
# Create backup script
cat > /root/scripts/backup-cybercore-db.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/cybercore"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u cybercore_prod -p'YOUR_PASSWORD' cybercore | gzip > $BACKUP_DIR/cybercore_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "cybercore_*.sql.gz" -mtime +30 -delete

echo "Backup completed: cybercore_$DATE.sql.gz"
EOF

chmod +x /root/scripts/backup-cybercore-db.sh
```

**Setup Daily Cron:**
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /root/scripts/backup-cybercore-db.sh >> /var/log/cybercore-backup.log 2>&1
```

### 2. File Backups
```bash
# Backup script for files
cat > /root/scripts/backup-cybercore-files.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/cybercore"
DATE=$(date +%Y%m%d_%H%M%S)
SOURCE="/var/www/vhosts/yourdomain.com/httpdocs"

tar -czf $BACKUP_DIR/cybercore_files_$DATE.tar.gz \
    --exclude='logs' \
    --exclude='*.log' \
    --exclude='.git' \
    -C $(dirname $SOURCE) $(basename $SOURCE)

# Keep only last 7 days
find $BACKUP_DIR -name "cybercore_files_*.tar.gz" -mtime +7 -delete

echo "File backup completed: cybercore_files_$DATE.tar.gz"
EOF

chmod +x /root/scripts/backup-cybercore-files.sh
```

**Setup Weekly Cron:**
```bash
# Add weekly backup on Sundays at 3 AM
0 3 * * 0 /root/scripts/backup-cybercore-files.sh >> /var/log/cybercore-backup.log 2>&1
```

### 3. Backup Verification
- [ ] Test database restore from backup
- [ ] Test file restore from backup
- [ ] Verify backup file integrity
- [ ] Ensure backups stored off-site (optional: rsync to remote server)

---

## 🔍 Monitoring

### 1. Error Monitoring
```bash
# Monitor PHP errors
tail -f /var/www/vhosts/yourdomain.com/logs/php_error.log

# Monitor Apache/Nginx errors
tail -f /var/www/vhosts/yourdomain.com/logs/error_log
```

### 2. Log Rotation
Create `/etc/logrotate.d/cybercore`:
```
/var/www/vhosts/yourdomain.com/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 username psacln
}
```

### 3. Alerts
- [ ] Setup disk space alerts (80% threshold)
- [ ] Setup email alerts for PHP errors
- [ ] Monitor database size
- [ ] Track failed login attempts

---

## 🚀 Go-Live

### 1. Pre-Launch
- [ ] Complete all checklist items above
- [ ] Run full security scan
- [ ] Verify all tests passing
- [ ] Create full backup
- [ ] Document admin credentials securely

### 2. DNS Configuration
- [ ] Point domain A record to server IP
- [ ] Verify DNS propagation (24-48 hours)
- [ ] Test HTTPS certificate working

### 3. Post-Launch
- [ ] Monitor error logs for 24 hours
- [ ] Test all critical functions
- [ ] Verify email delivery
- [ ] Check performance metrics
- [ ] Create first admin user

---

## 📋 Quick Commands Reference

```bash
# Check PHP version
php -v

# Test PHP configuration
php -i | grep "display_errors"

# Verify .htaccess is active
curl -I https://yourdomain.com/sql/

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Monitor real-time logs
tail -f /var/www/vhosts/yourdomain.com/logs/error_log

# Check disk space
df -h

# Check database size
mysql -u root -p -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'cybercore' GROUP BY table_schema;"

# Clear OPcache (if needed)
echo "<?php opcache_reset(); echo 'OPcache cleared';" > /tmp/clear_opcache.php && php /tmp/clear_opcache.php && rm /tmp/clear_opcache.php
```

---

## 🆘 Emergency Rollback

If issues occur:

1. **Restore Database:**
```bash
gunzip < /var/backups/cybercore/cybercore_YYYYMMDD_HHMMSS.sql.gz | mysql -u cybercore_prod -p cybercore
```

2. **Restore Files:**
```bash
cd /var/www/vhosts/yourdomain.com
tar -xzf /var/backups/cybercore/cybercore_files_YYYYMMDD_HHMMSS.tar.gz
```

3. **Enable Maintenance Mode:**
Create `maintenance.php` and redirect all traffic temporarily.

---

## ✅ Final Sign-Off

- [ ] All checklist items completed
- [ ] Security audit passed
- [ ] Performance benchmarks met
- [ ] Backups verified
- [ ] Monitoring active
- [ ] Documentation updated
- [ ] Team notified

**Deployment Date:** _______________

**Deployed By:** _______________

**Production URL:** https://_______________

---

## 📞 Support Contacts

- **Hosting Support:** Plesk Control Panel
- **Database Issues:** Check `/var/www/vhosts/yourdomain.com/logs/`
- **Emergency:** Review error logs and rollback if needed

**⚠️ Keep this checklist updated with each deployment!**
