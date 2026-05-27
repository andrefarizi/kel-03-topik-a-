set -e

# ============================================================
# KONFIGURASI
# ============================================================
BACKUP_DIR="/home/ubuntu/backups"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7  # Hapus backup lebih dari 7 hari

# Container names
DB_CONTAINER="db_server"
APP_CONTAINER="app_server"

# Database config 
if [ -f "$APP_DIR/.env" ]; then
    source "$APP_DIR/.env"
fi

DB_NAME="${DB_DATABASE:-tubes_keamanan}"
DB_USER="${DB_USERNAME:-kel03user}"
DB_PASS="${DB_PASSWORD:-rahasia}"

# Warna output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log()  { echo -e "${GREEN}[$(date '+%H:%M:%S')] INFO:${NC} $1"; }
warn() { echo -e "${YELLOW}[$(date '+%H:%M:%S')] WARN:${NC} $1"; }
error(){ echo -e "${RED}[$(date '+%H:%M:%S')] ERROR:${NC} $1"; exit 1; }

# ============================================================
# DIREKTORI BACKUP
# ============================================================
mkdir -p "$BACKUP_DIR/database"
mkdir -p "$BACKUP_DIR/app"

log "============================================================"
log "Mulai proses backup: $TIMESTAMP"
log "============================================================"

# ============================================================
# BACKUP DATABASE 
# ============================================================
log "Backup database MySQL dari container '$DB_CONTAINER'..."

DB_BACKUP_FILE="$BACKUP_DIR/database/db_backup_$TIMESTAMP.sql.gz"

if docker ps --format '{{.Names}}' | grep -q "^${DB_CONTAINER}$"; then
    docker exec "$DB_CONTAINER" \
        mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        2>/dev/null | gzip > "$DB_BACKUP_FILE"
    
    log "Database backup berhasil: $(basename $DB_BACKUP_FILE)"
    log "Ukuran file: $(du -sh $DB_BACKUP_FILE | cut -f1)"
else
    warn "Container '$DB_CONTAINER' tidak berjalan. Lewati backup database."
fi

# ============================================================
# BACKUP APP FILES (Storage & Config)
# ============================================================
log "Backup file aplikasi..."

APP_BACKUP_FILE="$BACKUP_DIR/app/app_backup_$TIMESTAMP.tar.gz"

# Backup folder storage Laravel dan file .env
tar -czf "$APP_BACKUP_FILE" \
    -C "$APP_DIR/docker" \
    --exclude="app/vendor" \
    --exclude="app/node_modules" \
    --exclude="app/.git" \
    app/ \
    2>/dev/null || warn "Beberapa file tidak bisa dibackup (izin)"

log "App backup berhasil: $(basename $APP_BACKUP_FILE)"
log "Ukuran file: $(du -sh $APP_BACKUP_FILE | cut -f1)"

# ============================================================
# HAPUS BACKUP LAMA (RETENTION POLICY)
# ============================================================
log "Menghapus backup lebih dari $RETENTION_DAYS hari..."

DELETED_DB=$(find "$BACKUP_DIR/database" -name "*.sql.gz" -mtime +$RETENTION_DAYS -print -delete 2>/dev/null | wc -l)
DELETED_APP=$(find "$BACKUP_DIR/app" -name "*.tar.gz" -mtime +$RETENTION_DAYS -print -delete 2>/dev/null | wc -l)

log "Backup lama dihapus: $DELETED_DB database, $DELETED_APP app"

# ============================================================
# TAMPILKAN RINGKASAN BACKUP
# ============================================================
echo ""
log "============================================================"
log "Backup selesai: $TIMESTAMP"
log "============================================================"
echo ""
echo "  Lokasi backup:"
echo "  → Database : $BACKUP_DIR/database/"
echo "  → App      : $BACKUP_DIR/app/"
echo ""
echo "  Daftar backup database:"
ls -lh "$BACKUP_DIR/database/" 2>/dev/null || echo "  (kosong)"
echo ""
echo "  Daftar backup aplikasi:"
ls -lh "$BACKUP_DIR/app/" 2>/dev/null || echo "  (kosong)"
echo ""
log "============================================================"