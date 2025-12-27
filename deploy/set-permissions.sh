#!/bin/bash

# ==========================================
# CyberCore File Permissions Script
# ==========================================
# Description: Set correct file permissions for Plesk hosting
# Usage: ./set-permissions.sh
# Run as: Website system user (not root)

set -e

echo "🔒 Setting CyberCore file permissions..."

# Configuration
APP_DIR="/var/www/vhosts/yourdomain.com/httpdocs"  # Update this!
WEB_USER="username"  # Update this!
WEB_GROUP="psacln"   # Default Plesk group

# Check if running in correct directory
if [ ! -f "$APP_DIR/index.php" ]; then
    echo "❌ Error: index.php not found. Are you in the correct directory?"
    echo "Current directory: $(pwd)"
    echo "Expected: $APP_DIR"
    exit 1
fi

cd "$APP_DIR"

echo "📁 Setting directory permissions to 755..."
find . -type d -exec chmod 755 {} \;

echo "📄 Setting file permissions to 644..."
find . -type f -exec chmod 644 {} \;

echo "🔐 Setting sensitive file permissions to 600..."
[ -f .env ] && chmod 600 .env && echo "  ✓ .env"
[ -f .user.ini ] && chmod 600 .user.ini && echo "  ✓ .user.ini"
[ -f config/database.php ] && chmod 600 config/database.php && echo "  ✓ config/database.php"
[ -f inc/db_credentials.php ] && chmod 600 inc/db_credentials.php && echo "  ✓ inc/db_credentials.php"

echo "📝 Setting writable directory permissions to 775..."
[ -d assets/uploads ] && chmod 775 assets/uploads && echo "  ✓ assets/uploads/"
[ -d logs ] && chmod 775 logs && echo "  ✓ logs/"

echo "🛡️ Protecting sensitive directories..."
[ -d sql ] && chmod 700 sql && echo "  ✓ sql/"
[ -d scripts ] && chmod 700 scripts && echo "  ✓ scripts/"

echo "👤 Setting ownership to $WEB_USER:$WEB_GROUP..."
# Uncomment if you have sudo access
# sudo chown -R $WEB_USER:$WEB_GROUP .

echo "✅ Permissions set successfully!"
echo ""
echo "Verify with:"
echo "  ls -la .env"
echo "  ls -la assets/uploads"
echo "  curl -I https://yourdomain.com/sql/ (should return 403)"
