# Nginx Rewrite Guide for CyberCore

Use these rules to mirror the Apache `.htaccess` redirects, splitting the public website at the root and the Client Area under `/manager`.

## Server Block Example

```
server {
  listen 80;
  server_name dominio.pt www.dominio.pt;
  root /var/www/cybercore; # adjust path
  index index.php;

  # Serve assets and static files
  location ~* ^/(assets|uploads)/ { try_files $uri $uri/ =404; }

  # Website pages at root
  location = / { try_files $uri $uri/ /index.php?$args; }
  location = /index.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/index.php; fastcgi_pass php-fpm; }
  location = /hosting.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/hosting.php; fastcgi_pass php-fpm; }
  location = /solutions.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/solutions.php; fastcgi_pass php-fpm; }
  location = /pricing.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/pricing.php; fastcgi_pass php-fpm; }
  location = /contact.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/contact.php; fastcgi_pass php-fpm; }
  location = /contact_submit.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/contact_submit.php; fastcgi_pass php-fpm; }
  location = /privacy.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/privacy.php; fastcgi_pass php-fpm; }
  location = /terms.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/terms.php; fastcgi_pass php-fpm; }

  # Redirect legacy client routes to /manager
  location ~ ^/(dashboard|services|domains|domains_edit|finance|support|logs|updates|login|logout|register|register-step1|register-step2|verify_email|forgot_password|reset_password|registration_success)\.php$ {
    return 302 /manager/$1.php?$args;
  }

  # Redirect legacy admin routes to /manager/admin
  location ~ ^/admin/(.*)\.php$ {
    return 302 /manager/admin/$1.php?$args;
  }

  # Redirect /website/* pages to root equivalents
  location = /website/index.php { return 302 /?$args; }
  location = /website/hosting.php { return 302 /hosting.php?$args; }
  location = /website/solutions.php { return 302 /solutions.php?$args; }
  location = /website/pricing.php { return 302 /pricing.php?$args; }
  location = /website/contact.php { return 302 /contact.php?$args; }
  location = /website/contact_submit.php { return 302 /contact_submit.php?$args; }

  # Client Area under /manager
  location ^~ /manager/ {
    try_files $uri $uri/ /manager/index.php?$args;
  }

  # PHP fallback
  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass php-fpm; # adjust to your upstream (e.g., unix:/run/php/php8.2-fpm.sock)
  }
}
```

Notes:
- Update `root` and `fastcgi_pass` to your environment.
- The `return 302` redirects ensure old URLs are SEO-friendly and users reach `/manager`.
- Static directories are served directly; PHP fallback handles remaining scripts.
