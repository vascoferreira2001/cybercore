#!/bin/bash

# ==========================================
# CyberCore Database Backup Script
# ==========================================
# Description: Automated MySQL backup with compression and retention
# Usage: ./backup-database.sh
# Cron: 0 2 * * * /path/to/backup-database.sh >> /var/log/cybercore-backup.log 2>&1

set -e

# Configuration
BACKUP_DIR="/var/backups/cybercore/database"
DB_NAME="cybercore"
DB_USER="cybercore_prod"
DB_PASS="YOUR_DATABASE_PASSWORD"  # Update this!
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Log start
echo "[$TIMESTAMP] Starting database backup..."

# Perform backup
BACKUP_FILE="$BACKUP_DIR/cybercore_${DATE}.sql.gz"

if mysqldump -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    "$DB_NAME" | gzip > "$BACKUP_FILE"; then
    
    # Get file size
    FILESIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "[$TIMESTAMP] ✓ Backup completed successfully: $BACKUP_FILE ($FILESIZE)"
    
    # Remove old backups
    echo "[$TIMESTAMP] Cleaning up backups older than $RETENTION_DAYS days..."
    find "$BACKUP_DIR" -name "cybercore_*.sql.gz" -mtime +$RETENTION_DAYS -delete
    
    # Count remaining backups
    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "cybercore_*.sql.gz" | wc -l)
    echo "[$TIMESTAMP] Total backups: $BACKUP_COUNT"
    
    # Optional: Upload to remote server
    # rsync -avz "$BACKUP_FILE" user@remote-server:/backups/cybercore/
    
    exit 0
else
    echo "[$TIMESTAMP] ✗ Backup failed!"
    exit 1
fi
