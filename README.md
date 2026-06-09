# 📦 kel-03-topik-a — Proyek Tugas Besar KSJ

**Kelompok 03 | Topik A: Web App + Database + Reverse Proxy**  
**Mata Kuliah:** Keamanan Server & Jaringan  
**Server:** `172.30.67.106` (Eduroam) / `100.110.137.96` (Tailscale) | SSH Port: `2222`

> **Catatan Jaringan:** VM Server di-deploy di jaringan lokal kampus yang mengharuskan penggunaan koneksi Wi-Fi Eduroam (`172.30.67.106`). Untuk mempermudah akses remote, konfigurasi, dan pengujian dari jaringan luar kampus secara aman, kami mengimplementasikan VPN **Tailscale** (`100.110.137.96`). Kedua IP tersebut mengarah ke server yang sama dengan environment yang identik.

## 🏗️ Arsitektur Sistem

```
           INTERNET
               │
       ┌───────▼────────┐
       │  UFW Firewall  │  ← deny incoming default
       │ Allow: 2222,   │
       │         80, 443│
       └───────┬────────┘
               │
    ┌──────────▼──────────────────────┐
    │     Ubuntu VM (Host)            │
    │       100.110.137.96            │
    │                                 │
    │  ┌──── Docker Network ────────┐ │
    │  │                            │ │
    │  │  [frontend network]        │ │
    │  │  ┌─────────────────────┐   │ │
    │  │  │  reverse_proxy      │   │ │
 80 ──►──►│  nginx:alpine       │   │ │
443 ──►──►│  HTTPS + SSL        │   │ │
    │  │  └──────────┬──────────┘   │ │
    │  │             │ FastCGI:9000  │ │
    │  │  ┌──────────▼──────────┐   │ │
    │  │  │  app_server         │   │ │
    │  │  │  php:8.3-fpm-alpine │   │ │
    │  │  │  user: www-data     │   │ │
    │  │  └──────────┬──────────┘   │ │
    │  │  [backend network]         │ │
    │  │  (internal: true)          │ │
    │  │  ┌──────────▼──────────┐   │ │
    │  │  │  db_server          │   │ │
    │  │  │  mysql:8.0          │   │ │
    │  │  │  NO port exposed    │   │ │
    │  │  └─────────────────────┘   │ │
    │  └────────────────────────────┘ │
    │                                 │
    │  [Fail2Ban] ← /var/log/auth.log │
    └─────────────────────────────────┘
```

### Stack Teknologi

| Komponen | Teknologi | Keterangan |
|---|---|---|
| Reverse Proxy | Nginx (Alpine) | HTTPS, SSL Termination, HTTP→HTTPS redirect |
| App Server | PHP 8.3-FPM + Laravel | Berjalan sebagai `www-data` (non-root) |
| Database | MySQL 8.0 | Internal network, tidak expose port ke host |
| Containerisasi | Docker + Docker Compose | 3 kontainer terpisah |
| Firewall | UFW | Hanya port 2222, 80, 443 yang terbuka |
| Anti-Brute Force | Fail2Ban | Ban otomatis setelah 5x gagal login SSH |
| SSL/TLS | Self-Signed Certificate | HTTPS wajib, port 443 |
| OS | Ubuntu Linux | Basis VM |

---

## 🚀 Panduan Instalasi & Deploy

### Prasyarat
- Ubuntu Server (VM sudah disediakan)
- SSH key sudah terdaftar di server
- Akses ke VM via: `ssh ubuntu@100.110.137.96 -p 2222`

### Langkah 1: Clone Repository
```bash
ssh ubuntu@100.110.137.96 -p 2222
git clone https://github.com/andrefarizi/kel-03-topik-a-.git
cd kel-03-topik-a-
```

### Langkah 2: Jalankan Script Setup Otomatis
```bash
chmod +x scripts/setup.sh
sudo ./scripts/setup.sh
```

Script ini akan melakukan:
- ✅ Update sistem Ubuntu
- ✅ Install Docker Engine
- ✅ Tambah user `ubuntu` ke group `docker`
- ✅ Konfigurasi SSH Hardening (port 2222, disable root login)
- ✅ Setup Firewall UFW (allow 2222, 80, 443)
- ✅ Konfigurasi Fail2Ban (anti brute-force SSH)
- ✅ Generate sertifikat SSL self-signed
- ✅ Deploy semua container via Docker Compose

### Langkah 3: Setup Environment (Manual)
```bash
cp .env.example .env
nano .env   # Edit nilai password dan konfigurasi
```

### Langkah 4: Jalankan Docker Compose
```bash
cd docker/
docker compose up -d --build
docker ps   # Verifikasi 3 container berjalan
```

---

## 🔐 Verifikasi Keamanan

### Cek SSH Hardening
```bash
grep -E 'Port|PermitRootLogin|PasswordAuthentication|PubkeyAuthentication' \
  /etc/ssh/sshd_config | grep -v '^#'
```

### Cek Firewall UFW
```bash
sudo ufw status verbose
```

### Cek Fail2Ban
```bash
sudo fail2ban-client status
sudo fail2ban-client status sshd
```

### Cek Docker Container & Network
```bash
# Container yang berjalan
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'

# Network Docker
docker network ls

# Verifikasi DB tidak expose port
docker port db_server   # Harus kosong (tidak ada output)

# Verifikasi app berjalan sebagai non-root
docker exec app_server whoami   # Harus: www-data
```

### Cek Port Terbuka
```bash
ss -tlnp
# Harus hanya ada: 2222, 80, 443 (dan 53 untuk DNS lokal)
```

### Cek SSL/TLS
```bash
# Test HTTPS
curl -k https://localhost -I

# Test redirect HTTP→HTTPS
curl -v http://localhost 2>&1 | grep -E 'Location|< HTTP'

# Cek detail sertifikat
docker exec reverse_proxy openssl x509 \
  -in /etc/ssl/certs/server.crt -text -noout \
  | grep -E 'Subject|Not After'
```

---

## 🗂️ Struktur Direktori

```
kel-03-topik-a/
├── docs/
│   ├── arsitektur.pdf      ← Diagram arsitektur sistem
│   ├── laporan.pdf         ← Laporan teknis lengkap
│   └── uji-keamanan.pdf    ← Hasil nmap, nikto, dll.
├── docker/
│   ├── docker-compose.yaml ← Definisi 3 container
│   ├── nginx/
│   │   ├── default.conf    ← Konfigurasi reverse proxy + SSL
│   │   ├── server.crt      ← Sertifikat SSL
│   │   └── server.key      ← Private key SSL
│   ├── app/
│   │   ├── Dockerfile      ← Build image PHP-FPM Laravel
│   │   └── ...             ← Source code Laravel
│   └── db/
│       └── init.sql        ← Script inisialisasi database
├── scripts/
│   ├── setup.sh            ← Setup awal VM
│   └── backup.sh           ← Backup otomatis database & app
├── README.md               ← Panduan ini
└── .env.example            ← Template environment variables
```

---

## 💾 Backup Otomatis

```bash
# Jalankan backup manual
chmod +x scripts/backup.sh
./scripts/backup.sh

# Setup cron backup otomatis setiap hari jam 02:00
crontab -e
# Tambahkan baris:
# 0 2 * * * /home/ubuntu/kel-03-topik-a-/scripts/backup.sh >> /var/log/backup.log 2>&1
```

Backup tersimpan di `/home/ubuntu/backups/` dan otomatis dihapus setelah 7 hari.

---

## 🎥 Video Demo

> **Link Video:** [Google Drive](https://drive.google.com/drive/folders/1PmBP27V_x76xFAErYr0DlkslZt4TAyHh?usp=sharing)

Video menampilkan:
- Output `docker ps`, `docker network ls`
- Output `ufw status verbose`
- Output `fail2ban-client status sshd`
- Akses web via browser (HTTP → HTTPS redirect)
- Sertifikat SSL di browser

---

## 📋 Keamanan yang Diterapkan

| No | Aspek Keamanan | Implementasi | Status |
|---|---|---|---|
| 1 | SSH Key Authentication | `PasswordAuthentication no` | ✅ |
| 2 | Ganti Port SSH Default | Port `2222` | ✅ |
| 3 | Disable Root Login | `PermitRootLogin no` | ✅ |
| 4 | Firewall UFW | Allow hanya 2222, 80, 443 | ✅ |
| 5 | Anti Brute-Force | Fail2Ban (5 retry = ban 1 jam) | ✅ |
| 6 | Kontainer Terpisah | 3 container: proxy, app, db | ✅ |
| 7 | Network Isolation | Backend network `internal: true` | ✅ |
| 8 | DB Tidak Expose Port | Port 3306 tidak di-publish | ✅ |
| 9 | Non-Root Container | App berjalan sebagai `www-data` | ✅ |
| 10 | HTTPS Wajib | SSL + redirect HTTP→HTTPS | ✅ |
| 11 | Env Variables | Kredensial di file `.env` | ✅ |

---

*Kelompok 03 — Keamanan Server & Jaringan | 2026*
