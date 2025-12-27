#!/bin/bash

# ==========================================
# CyberCore Files Backup Script
# ==========================================
# Description: Automated file backup with compression
# Usage: ./backup-files.sh
# Cron: 0 3 * * 0 /path/to/backup-files.sh >> /var/log/cybercore-backup.log 2>&1

set -e

# Configuration
BACKUP_DIR="/var/backups/cybercore/files"
SOURCE_DIR="/var/www/vhosts/yourdomain.com/httpdocs"  # Update this!
RETENTION_DAYS=7
DATE=$(date +%Y%m%d_%H%M%S)
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Log start
echo "[$TIMESTAMP] Starting files backup..."

# Perform backup
BACKUP_FILE="$BACKUP_DIR/cybercore_files_${DATE}.tar.gz"

if tar -czf "$BACKUP_FILE" \
    --exclude='logs' \
    --exclude='*.log' \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    -C "$(dirname $SOURCE_DIR)" "$(basename $SOURCE_DIR)" 2>/dev/null; then
    
    # Get file size
    FILESIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "[$TIMESTAMP] ✓ Files backup completed: $BACKUP_FILE ($FILESIZE)"
    
    # Remove old backups
    echo "[$TIMESTAMP] Cleaning up file backups older than $RETENTION_DAYS days..."
    find "$BACKUP_DIR" -name "cybercore_files_*.tar.gz" -mtime +$RETENTION_DAYS -delete
    
    # Count remaining backups
    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "cybercore_files_*.tar.gz" | wc -l)
    echo "[$TIMESTAMP] Total file backups: $BACKUP_COUNT"
    
    # Optional: Upload to remote server
    # rsync -avz "$BACKUP_FILE" user@remote-server:/backups/cybercore/
    
    exit 0
else
    echo "[$TIMESTAMP] ✗ Files backup failed!"
    exit 1
fi
