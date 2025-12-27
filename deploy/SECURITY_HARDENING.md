# 🔒 Security Hardening Guide - CyberCore Platform

## Critical Security Measures for Production

---

## 1. 🔐 Authentication & Sessions

### Password Policy
```php
// Enforce in registration/password reset
- Minimum 12 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
- At least 1 special character
```

### Session Configuration
```ini
# In .user.ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
session.use_only_cookies = 1
session.gc_maxlifetime = 7200  # 2 hours
```

### Brute Force Protection
Implement rate limiting on login attempts:

**Add to database (already in schema):**
```sql
CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255),
    attempted_at DATETIME NOT NULL,
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
);
```

**Implementation (add to login.php):**
```php
// Check login attempts
$stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts 
    WHERE ip_address = :ip AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
$stmt->execute(['ip' => $_SERVER['REMOTE_ADDR']]);
$attempts = $stmt->fetchColumn();

if ($attempts >= 5) {
    die('Too many login attempts. Please try again in 15 minutes.');
}

// Log failed attempt
if (!$login_success) {
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, email, attempted_at) 
        VALUES (:ip, :email, NOW())');
    $stmt->execute([
        'ip' => $_SERVER['REMOTE_ADDR'],
        'email' => $_POST['email']
    ]);
}
```

---

## 2. 🛡️ SQL Injection Prevention

✅ **Already Implemented:** All database queries use PDO prepared statements.

**Verify all queries follow this pattern:**
```php
// ✅ CORRECT
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// ❌ NEVER DO THIS
$query = "SELECT * FROM users WHERE email = '$email'";
```

**Audit Checklist:**
- [ ] All user input sanitized
- [ ] All queries use prepared statements
- [ ] No direct concatenation in SQL
- [ ] Integer inputs validated with `filter_var($id, FILTER_VALIDATE_INT)`

---

## 3. 🔒 XSS (Cross-Site Scripting) Prevention

**Output Escaping (Essential):**
```php
// Always escape output
<?php echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8'); ?>

// For HTML attributes
<?php echo htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>

// Never output raw user input
<?php echo $user_input; ?> // ❌ DANGEROUS
```

**Content Security Policy (Already in .htaccess):**
```apache
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;"
```

**Audit Checklist:**
- [ ] All user input escaped with `htmlspecialchars()`
- [ ] Rich text sanitized with HTML Purifier (if needed)
- [ ] JavaScript variables properly encoded
- [ ] CSP header active and tested

---

## 4. 🔐 CSRF Protection

✅ **Already Implemented:** All forms include CSRF tokens via `inc/bootstrap.php`.

**Verify all POST forms include:**
```php
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

// And validate on submission:
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    die('Invalid CSRF token');
}
```

**Audit Checklist:**
- [ ] All POST forms have CSRF token
- [ ] All POST handlers validate token
- [ ] AJAX requests include CSRF token
- [ ] Token regenerated on login

---

## 5. 📁 File Upload Security

**Secure Upload Configuration:**

Create `inc/upload_handler.php`:
```php
<?php
function cybercore_secure_upload($file, $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'])
{
    $errors = [];
    
    // Check file uploaded via HTTP POST
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Invalid upload';
        return ['success' => false, 'errors' => $errors];
    }
    
    // File size limit (10MB)
    if ($file['size'] > 10485760) {
        $errors[] = 'File too large (max 10MB)';
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        $errors[] = 'Invalid file type';
    }
    
    // Generate safe filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_name = bin2hex(random_bytes(16)) . '.' . $extension;
    
    // Upload directory (outside web root ideally)
    $upload_dir = __DIR__ . '/../assets/uploads/';
    $destination = $upload_dir . $safe_name;
    
    if (!$errors && move_uploaded_file($file['tmp_name'], $destination)) {
        chmod($destination, 0644);
        return ['success' => true, 'filename' => $safe_name];
    }
    
    return ['success' => false, 'errors' => $errors];
}
```

**Upload Directory Protection (Already in .htaccess):**
```apache
<Directory "/assets/uploads">
    php_flag engine off
    <FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
        Deny from all
    </FilesMatch>
</Directory>
```

---

## 6. 🔑 Password Security

✅ **Already Using:** PHP's `password_hash()` with bcrypt.

**Best Practices:**
```php
// Hashing (registration)
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verification (login)
if (password_verify($input_password, $stored_hash)) {
    // Login successful
    
    // Rehash if algorithm changed
    if (password_needs_rehash($stored_hash, PASSWORD_BCRYPT, ['cost' => 12])) {
        $new_hash = password_hash($input_password, PASSWORD_BCRYPT, ['cost' => 12]);
        // Update database
    }
}
```

**Password Requirements:**
- [ ] Minimum 12 characters enforced
- [ ] Check against common passwords list
- [ ] Password strength meter on frontend
- [ ] Force password change every 90 days (optional)

---

## 7. 🌐 HTTPS & Transport Security

**Verify HTTPS Configuration:**
```bash
# Test SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Check security headers
curl -I https://yourdomain.com
```

**Enable HSTS (after testing HTTPS works):**

In `.htaccess`:
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

**SSL Best Practices:**
- [ ] Use TLS 1.2 or higher
- [ ] Disable SSLv3, TLS 1.0, TLS 1.1
- [ ] Use strong cipher suites
- [ ] Enable OCSP stapling
- [ ] Redirect all HTTP to HTTPS

---

## 8. 📧 Email Security

**Prevent Email Header Injection:**
```php
function validate_email_safe($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // Prevent header injection
    if (preg_match("/[\r\n]/", $email)) {
        return false;
    }
    return $email;
}
```

**SPF, DKIM, DMARC Records:**
```dns
; SPF Record
yourdomain.com. IN TXT "v=spf1 mx a ip4:YOUR_SERVER_IP ~all"

; DKIM Record (generate key with OpenDKIM)
default._domainkey.yourdomain.com. IN TXT "v=DKIM1; k=rsa; p=YOUR_PUBLIC_KEY"

; DMARC Record
_dmarc.yourdomain.com. IN TXT "v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com"
```

---

## 9. 🚫 Input Validation

**Server-Side Validation (Critical):**

Never trust client-side validation. Always validate on server.

```php
// Example validation helper
function validate_domain($domain) {
    $domain = strtolower(trim($domain));
    
    // Length check
    if (strlen($domain) < 4 || strlen($domain) > 255) {
        return false;
    }
    
    // Pattern check
    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z]{2,})+$/', $domain)) {
        return false;
    }
    
    // Check DNS (optional)
    if (!checkdnsrr($domain, 'A') && !checkdnsrr($domain, 'AAAA')) {
        return false;
    }
    
    return $domain;
}

// Validate all user input
$domain = validate_domain($_POST['domain'] ?? '');
if (!$domain) {
    $errors[] = 'Invalid domain name';
}
```

---

## 10. 🔍 Security Headers Checklist

**Verify all headers active:**
```bash
curl -I https://yourdomain.com
```

Expected headers:
```
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'...
Strict-Transport-Security: max-age=31536000
```

---

## 11. 🗄️ Database Security

**Production User Privileges:**
```sql
-- Create limited user
CREATE USER 'cybercore_prod'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';

-- Grant only necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON cybercore.* TO 'cybercore_prod'@'localhost';

-- NO DROP, CREATE, ALTER in production!
FLUSH PRIVILEGES;
```

**Connection Security:**
```php
// Force SSL for database connections (if available)
$options = [
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];
```

**Sensitive Data Encryption:**
```sql
-- Encrypt sensitive columns (optional)
-- Store NIF, credit card numbers encrypted
-- Use AES_ENCRYPT() / AES_DECRYPT()
```

---

## 12. 📝 Logging & Monitoring

**Security Event Logging:**

Create `inc/security_logger.php`:
```php
<?php
function log_security_event($event_type, $details, $user_id = null)
{
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('INSERT INTO logs (user_id, type, message, ip_address, user_agent, created_at) 
        VALUES (:user_id, :type, :message, :ip, :ua, NOW())');
    
    $stmt->execute([
        'user_id' => $user_id,
        'type' => $event_type,
        'message' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

// Usage:
log_security_event('login_failed', "Failed login attempt for: {$email}");
log_security_event('suspicious_activity', "SQL injection attempt detected");
log_security_event('admin_access', "Admin panel accessed", $user_id);
```

**Monitor for:**
- [ ] Failed login attempts
- [ ] SQL injection attempts
- [ ] File upload attempts
- [ ] Admin panel access
- [ ] Password reset requests
- [ ] Privilege escalation attempts

---

## 13. 🔄 Regular Security Tasks

### Daily
- [ ] Review error logs for anomalies
- [ ] Check failed login attempts
- [ ] Monitor disk space

### Weekly
- [ ] Review access logs
- [ ] Check backup integrity
- [ ] Update dependencies (composer update)

### Monthly
- [ ] Security audit
- [ ] Update PHP version
- [ ] Review user permissions
- [ ] Test backup restoration

### Quarterly
- [ ] Penetration testing
- [ ] Password rotation (admin accounts)
- [ ] Security policy review
- [ ] Certificate renewal check

---

## 14. 🚨 Incident Response Plan

**If security breach detected:**

1. **Immediate Actions:**
   - Enable maintenance mode
   - Block suspicious IP addresses
   - Revoke compromised sessions
   - Change all passwords

2. **Investigation:**
   - Review logs (access, error, security)
   - Identify attack vector
   - Assess data exposure

3. **Recovery:**
   - Restore from clean backup if needed
   - Patch vulnerability
   - Update security measures

4. **Notification:**
   - Notify affected users (if data breach)
   - Report to authorities (if required by GDPR)
   - Document incident

---

## ✅ Security Audit Checklist

Before going live, verify:

### Application
- [ ] All user input validated and sanitized
- [ ] All output escaped (XSS prevention)
- [ ] CSRF tokens on all forms
- [ ] SQL injection prevention (prepared statements)
- [ ] File upload restrictions
- [ ] Session security configured
- [ ] Password hashing with bcrypt
- [ ] Rate limiting on authentication

### Server
- [ ] HTTPS enforced
- [ ] Security headers active
- [ ] PHP errors disabled (display_errors=Off)
- [ ] Sensitive files protected
- [ ] Directory listing disabled
- [ ] File permissions correct (644/755)
- [ ] Database user has minimal privileges

### Monitoring
- [ ] Error logging enabled
- [ ] Security event logging
- [ ] Backup system active
- [ ] Monitoring alerts configured

### Compliance
- [ ] GDPR compliance (if applicable)
- [ ] Cookie consent implemented
- [ ] Privacy policy published
- [ ] Terms of service published
- [ ] Data processing agreement

---

## 📚 Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Mozilla Observatory](https://observatory.mozilla.org/)
- [SSL Labs Test](https://www.ssllabs.com/ssltest/)

---

**⚠️ Security is an ongoing process, not a one-time task!**
