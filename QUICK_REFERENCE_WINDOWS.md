# 🚀 QUICK REFERENCE - Windows Server Deployment

**CyberCore on IIS - 5-Minute Setup Guide**

---

## 📋 Pre-Deployment Checklist

```bash
✓ web.config copied to root
✓ web.config copied to /assets/uploads/
✓ .user.ini created in root
✓ .env configured with all credentials
✓ Database imported (mysqldump)
✓ Logs directory created (D:\logs)
✓ IIS app pool restarted
```

---

## ⚡ 5-Minute Deployment

### Step 1: Upload Files (2 min)
```batch
REM Via SFTP or FTP:
REM Copy entire project to: C:\inetpub\vhosts\yourdomain.com\httpdocs\
```

### Step 2: Create Directories (1 min)
```batch
REM Create logs folder
mkdir D:\logs

REM Create backups folder
mkdir D:\backups\cybercore\database
mkdir D:\backups\cybercore\files
```

### Step 3: Verify Installation (1 min)
```bash
# Test PHP
php -v

# Test database
mysql -u cybercore_prod -p cybercore -e "SHOW TABLES;"

# Test security audit
php C:\inetpub\vhosts\yourdomain.com\httpdocs\security_check.php
```

### Step 4: Create Admin (1 min)
```sql
-- In MySQL, create admin user
mysql -u cybercore_prod -p cybercore

INSERT INTO users 
(identifier, email, password_hash, first_name, last_name, role, email_verified, created_at, updated_at) 
VALUES 
('CYC000001', 'admin@yourdomain.com', '$2y$12$YOUR_BCRYPT_HASH_HERE', 'Admin', 'User', 'Gestor', 1, NOW(), NOW());
```

### Step 5: Go Live!
```
✓ Visit https://yourdomain.com
✓ Admin login: https://yourdomain.com/admin/
✓ Monitor logs: D:\logs\php_error.log
```

---

## 🔑 Essential Files

| File | Purpose | Status |
|------|---------|--------|
| `web.config` | IIS security rules | ✅ Ready |
| `assets/uploads/web.config` | Upload folder protection | ✅ Ready |
| `.user.ini` | PHP settings | ✅ Ready |
| `.env` | Environment variables | ⚠️ Create from .env.example |
| `security_check.php` | Verify security | ✅ Ready |
| `inc/rate_limit.php` | Brute force protection | ✅ Ready |

---

## 🔐 Critical Settings

**In `.env`:**
```bash
APP_ENV=production
APP_DEBUG=false
HTTPS_ONLY=true
DB_HOST=127.0.0.1
DB_NAME=cybercore
DB_USER=cybercore_prod
DB_PASS=YOUR_STRONG_PASSWORD
PLESK_API_KEY=your_key_here
SMTP_HOST=localhost
```

**In `.user.ini`:**
```ini
display_errors = Off
allow_url_include = Off
session.cookie_secure = 1
session.cookie_httponly = 1
date.timezone = "Europe/Lisbon"
```

**In `web.config`:**
```xml
<!-- HTTPS redirect -->
<rule name="ForceHTTPS" enabled="true">
  <action type="Redirect" url="https://{HTTP_HOST}..." redirectType="Permanent" />
</rule>
```

---

## ✅ Verification Commands

```bash
# Check HTTPS
curl -I https://yourdomain.com
# Expected: 301 Moved Permanently (if accessing via HTTP)

# Check security headers
curl -I https://yourdomain.com | grep -E "(X-Frame|X-Content|Strict)"
# Expected: All headers present

# Check .env is blocked
curl -I https://yourdomain.com/.env
# Expected: 403 Forbidden or 404 Not Found

# Run security audit
php security_check.php
# Expected: All checks PASS

# Test database
mysql -u cybercore_prod -p cybercore -e "SELECT COUNT(*) FROM users;"
# Expected: 1 (admin user)
```

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| **500 Internal Error** | Check `D:\logs\php_error.log` |
| **403 Forbidden** | Check web.config rules and directory permissions |
| **Database not connecting** | Verify `.env` credentials match MySQL user |
| **PHP not running** | Verify FastCGI handler in IIS |
| **Uploads not working** | Check `/assets/uploads/` is writable |
| **Email not sending** | Verify SMTP settings in `.env` |
| **Rate limiting not working** | Ensure `login_attempts` table exists |

---

## 📊 Performance Baseline

After deployment, verify:
- Homepage load time: < 2 seconds
- Admin dashboard: < 1 second
- Database queries: < 100ms average
- Memory usage: < 256MB

---

## 🔄 Daily Operations

```bash
# Monitor error logs
tail -f D:\logs\php_error.log

# Check database size
mysql -u cybercore_prod -p cybercore -e "
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb 
FROM information_schema.TABLES WHERE table_schema = 'cybercore';"

# Backup database (automated in cron, but manual)
mysqldump -u cybercore_prod -p cybercore | gzip > D:\backups\cybercore\database\cybercore_$(date +%Y%m%d).sql.gz

# Check IIS app pool status
appcmd list apppool
```

---

## 📞 Support Contacts

- **Plesk Admin:** https://your-server:8443
- **IIS Manager:** `inetmgr.exe`
- **MySQL Admin:** `mysql -u root -p`
- **PHP Info:** Create `info.php` with `<?php phpinfo(); ?>`

---

## 🚨 Emergency Procedures

**If system goes down:**
1. Check IIS app pool status: `appcmd list apppool`
2. Restart app pool: `appcmd recycle apppool /apppool.name:YourAppPool`
3. Check PHP errors: `D:\logs\php_error.log`
4. Verify database: `mysql -u root -p -e "SHOW PROCESSLIST;"`
5. Restore from backup if needed

**Restore database:**
```bash
gunzip < D:\backups\cybercore\database\cybercore_YYYYMMDD.sql.gz | mysql -u cybercore_prod -p cybercore
```

---

## ✨ Going Forward

**Weekly:**
- [ ] Review error logs
- [ ] Monitor disk space
- [ ] Check backup integrity

**Monthly:**
- [ ] Security update check
- [ ] Database optimization
- [ ] Plesk maintenance

**Quarterly:**
- [ ] Full security audit
- [ ] Performance review
- [ ] Disaster recovery test

---

## 📚 Documentation

Detailed docs available in:
- `PRODUCTION_GO_LIVE.md` - 170+ item checklist
- `WINDOWS_IIS_FINALIZATION.md` - Complete guide
- `deploy/SECURITY_HARDENING.md` - Security details
- `deploy/PRODUCTION_CHECKLIST.md` - Original checklist

---

**Deployment Status: ✅ READY FOR PRODUCTION**

🚀 CyberCore is live!
