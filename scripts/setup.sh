set -e  # Hentikan script jika ada error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' 

log() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

echo "============================================================"
echo "  Setup VM - Kelompok 03 Topik A (KSJ)"
echo "  $(date)"
echo "============================================================"

# ============================================================
# 1. UPDATE SISTEM
# ============================================================
log "Memperbarui paket sistem..."
apt-get update -y && apt-get upgrade -y
log "Sistem berhasil diperbarui."

# ============================================================
# 2. INSTALL DEPENDENSI DASAR
# ============================================================
log "Menginstal dependensi dasar..."
apt-get install -y \
    curl \
    wget \
    git \
    ufw \
    fail2ban \
    openssl \
    net-tools \
    ca-certificates \
    gnupg \
    lsb-release
log "Dependensi dasar berhasil diinstal."

# ============================================================
# 3. INSTALL DOCKER ENGINE
# ============================================================
log "Menginstal Docker Engine..."
if ! command -v docker &> /dev/null; then
    # Tambah Docker GPG key resmi
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
        gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg

    # Tambah repository Docker
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
        https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | \
        tee /etc/apt/sources.list.d/docker.list > /dev/null

    apt-get update -y
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    log "Docker berhasil diinstal."
else
    log "Docker sudah terinstal, lewati."
fi

# Tambah user ubuntu ke group docker
usermod -aG docker ubuntu
log "User ubuntu ditambahkan ke group docker."

# Aktifkan Docker service
systemctl enable docker
systemctl start docker
log "Docker service diaktifkan."

# ============================================================
# 4. KONFIGURASI SSH HARDENING
# ============================================================
log "Mengkonfigurasi SSH Hardening..."

# Backup konfigurasi SSH asli
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup.$(date +%Y%m%d)

# Set konfigurasi SSH yang aman
cat > /etc/ssh/sshd_config.d/99-hardening.conf << 'EOF'
# SSH Hardening - Kelompok 03 KSJ
Port 2222
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
ChallengeResponseAuthentication no
UsePAM yes
X11Forwarding no
PrintMotd no
AcceptEnv LANG LC_*
Subsystem sftp /usr/lib/openssh/sftp-server
MaxAuthTries 3
LoginGraceTime 30
ClientAliveInterval 300
ClientAliveCountMax 2
EOF


systemctl restart sshd
log "SSH Hardening berhasil dikonfigurasi."
warn "PENTING: Pastikan SSH key sudah terpasang sebelum menonaktifkan PasswordAuthentication!"

# ============================================================
# 5. KONFIGURASI FIREWALL UFW
# ============================================================
log "Mengkonfigurasi Firewall UFW..."

# Set policy default
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Izinkan port yang diperlukan
ufw allow 2222/tcp comment 'SSH custom port'
ufw allow 80/tcp comment 'HTTP (redirect ke HTTPS)'
ufw allow 443/tcp comment 'HTTPS'

# Aktifkan UFW
ufw --force enable
log "UFW berhasil dikonfigurasi dan diaktifkan."

# Tampilkan status UFW
ufw status verbose

# ============================================================
# 6. KONFIGURASI FAIL2BAN
# ============================================================
log "Mengkonfigurasi Fail2Ban..."

# Buat konfigurasi jail lokal
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5
backend  = systemd

[sshd]
enabled  = true
port     = 2222
filter   = sshd
logpath  = /var/log/auth.log
maxretry = 5
bantime  = 3600
EOF

# Aktifkan dan restart Fail2Ban
systemctl enable fail2ban
systemctl restart fail2ban
log "Fail2Ban berhasil dikonfigurasi."

# Tampilkan status Fail2Ban
fail2ban-client status

# ============================================================
# 7. GENERATE SERTIFIKAT SSL SELF-SIGNED
# ============================================================
log "Generate sertifikat SSL self-signed..."

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CERT_DIR="$APP_DIR/docker/nginx"

if [ -f "$CERT_DIR/server.crt" ] && [ -f "$CERT_DIR/server.key" ]; then
    warn "Sertifikat SSL sudah ada, lewati."
else
    mkdir -p "$CERT_DIR"
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$CERT_DIR/server.key" \
        -out "$CERT_DIR/server.crt" \
        -subj "/C=ID/ST=Indonesia/L=Bandung/O=Kelompok03KSJ/OU=IT/CN=100.110.137.96"
    
    # Set permission yang aman
    chmod 644 "$CERT_DIR/server.crt"
    chmod 600 "$CERT_DIR/server.key"
    chown ubuntu:ubuntu "$CERT_DIR/server.crt" "$CERT_DIR/server.key"
    log "Sertifikat SSL berhasil digenerate."
fi

# ============================================================
# 8. SETUP ENVIRONMENT FILE
# ============================================================
log "Menyiapkan file environment..."

LARAVEL_DIR="$APP_DIR/docker/app"

if [ ! -f "$LARAVEL_DIR/.env" ]; then
    if [ -f "$APP_DIR/.env.example" ]; then
        cp "$APP_DIR/.env.example" "$LARAVEL_DIR/.env"
        chmod 644 "$LARAVEL_DIR/.env"
        warn "File .env dibuat di docker/app/.env dari .env.example. EDIT nilainya sebelum deploy!"
    else
        warn "File .env.example tidak ditemukan!"
    fi
else
    chmod 644 "$LARAVEL_DIR/.env"
    log "File .env sudah ada, lewati. (Permission diset ke 644)"
fi

# ============================================================
# 9. DEPLOY APLIKASI DOCKER
# ============================================================
log "Menjalankan Docker Compose..."

cd "$APP_DIR/docker" || error "Direktori docker tidak ditemukan!"

# Build dan jalankan container
docker compose up -d --build

# Tunggu container berjalan
sleep 5

# Tampilkan status container
log "Status container:"
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'

log "Status network Docker:"
docker network ls

# ============================================================
# SELESAI
# ============================================================
echo ""
echo "============================================================"
echo -e "${GREEN}  Setup berhasil diselesaikan!${NC}"
echo "============================================================"
echo ""
echo "  Akses aplikasi:"
echo "  → HTTP  : http://100.110.137.96 (redirect ke HTTPS)"
echo "  → HTTPS : https://100.110.137.96"
echo ""
echo "  Verifikasi manual:"
echo "  → sudo ufw status verbose"
echo "  → sudo fail2ban-client status sshd"
echo "  → docker ps"
echo "  → ss -tlnp"
echo "============================================================"