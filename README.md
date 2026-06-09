# Implementasi Web Server Aman Berbasis Docker
### Tugas Besar — Keamanan Server & Jaringan

**Kelompok 03 | Topik A: Web Application Server dengan Reverse Proxy dan Database**

| Informasi | Detail |
|---|---|
| **Mata Kuliah** | Keamanan Server & Jaringan |
| **Topik** | Implementasi Web Server dengan Docker, Nginx, SSL/TLS, UFW, dan Fail2Ban |
| **IP Server (Jaringan Kampus)** | `172.30.67.106` — diakses via koneksi Wi-Fi Eduroam |
| **IP Server (Remote Access)** | `100.110.137.96` — diakses via VPN Tailscale |
| **SSH Port** | `2222` (non-default, telah dikonfigurasi) |

> **Keterangan Jaringan:** Server VM di-*deploy* pada jaringan lokal kampus (`172.30.67.106`) yang hanya dapat diakses melalui koneksi Wi-Fi Eduroam. Untuk keperluan akses remote, konfigurasi, dan pengujian dari luar lingkungan kampus, diimplementasikan VPN peer-to-peer **Tailscale** (`100.110.137.96`). Kedua alamat IP tersebut merujuk pada server fisik yang sama dengan konfigurasi sistem yang identik.

---

## Arsitektur Sistem

```
           INTERNET / CLIENT
               │
       ┌───────▼────────┐
       │  UFW Firewall  │  ← Default Policy: deny incoming
       │ Allow: 80, 443,│
       │          2222  │
       └───────┬────────┘
               │
    ┌──────────▼──────────────────────┐
    │     Ubuntu VM (Host)            │
    │  172.30.67.106 / 100.110.137.96 │
    │                                 │
    │  ┌──── Docker Engine ─────────┐ │
    │  │                            │ │
    │  │  [frontend network]        │ │
    │  │  ┌─────────────────────┐   │ │
    │  │  │  reverse_proxy      │   │ │
 80 ──►──►│  nginx:alpine       │   │ │
443 ──►──►│  SSL/TLS Termination│   │ │
    │  │  └──────────┬──────────┘   │ │
    │  │             │ FastCGI:9000  │ │
    │  │  ┌──────────▼──────────┐   │ │
    │  │  │  app_server         │   │ │
    │  │  │  PHP 8.3-FPM Laravel│   │ │
    │  │  │  user: www-data     │   │ │
    │  │  └──────────┬──────────┘   │ │
    │  │  [backend network]         │ │
    │  │  (internal: true)          │ │
    │  │  ┌──────────▼──────────┐   │ │
    │  │  │  db_server          │   │ │
    │  │  │  MySQL 8.0          │   │ │
    │  │  │  Port tidak diekspos│   │ │
    │  │  └─────────────────────┘   │ │
    │  └────────────────────────────┘ │
    │                                 │
    │  [Fail2Ban] ← /var/log/auth.log │
    └─────────────────────────────────┘
```

### Tabel Stack Teknologi

| Komponen | Teknologi | Peran & Konfigurasi Keamanan |
|---|---|---|
| Reverse Proxy | Nginx (Alpine) | Menangani SSL/TLS termination, redirect HTTP→HTTPS (301) |
| Application Server | PHP 8.3-FPM + Laravel | Dijalankan sebagai user non-root (`www-data`) |
| Database Server | MySQL 8.0 | Beroperasi di jaringan `backend` yang terisolasi; port tidak diekspos ke host |
| Orkestrasi Kontainer | Docker + Docker Compose | Mendefinisikan 3 kontainer terpisah dengan isolasi jaringan |
| Firewall Host | UFW (Uncomplicated Firewall) | Membatasi akses hanya pada port 80, 443, dan 2222 |
| Proteksi Brute-Force | Fail2Ban | Pemblokiran otomatis IP setelah 5 kali kegagalan autentikasi SSH |
| Enkripsi Transport | Self-Signed SSL/TLS Certificate | Memastikan seluruh komunikasi terenkripsi melalui HTTPS (port 443) |
| Sistem Operasi | Ubuntu Linux | Platform dasar Virtual Machine |

---

## Panduan Instalasi dan Deployment

### Prasyarat

- Virtual Machine Ubuntu Server yang telah disediakan
- SSH Public Key yang telah terdaftar pada server tujuan
- Akses ke VM melalui perintah: `ssh ubuntu@100.110.137.96 -p 2222`

### Langkah 1: Akses Server dan Clone Repository

```bash
ssh ubuntu@100.110.137.96 -p 2222
git clone https://github.com/andrefarizi/kel-03-topik-a-.git
cd kel-03-topik-a-
```

### Langkah 2: Eksekusi Skrip Konfigurasi Otomatis

```bash
chmod +x scripts/setup.sh
sudo ./scripts/setup.sh
```

Skrip `setup.sh` secara otomatis menjalankan serangkaian proses konfigurasi berikut:

- Pembaruan paket sistem Ubuntu
- Instalasi Docker Engine dan Docker Compose Plugin
- Penambahan user `ubuntu` ke grup `docker`
- Konfigurasi SSH Hardening (perpindahan ke port 2222, penonaktifan root login dan autentikasi password)
- Konfigurasi Firewall UFW (mengizinkan port 80, 443, dan 2222)
- Konfigurasi Fail2Ban sebagai proteksi terhadap serangan brute-force
- Pembuatan sertifikat SSL Self-Signed menggunakan OpenSSL
- Deployment seluruh kontainer melalui Docker Compose

### Langkah 3: Konfigurasi Environment Variables

```bash
cp .env.example .env
nano .env   # Sesuaikan nilai kredensial dan parameter konfigurasi
```

### Langkah 4: Menjalankan Docker Compose

```bash
cd docker/
docker compose up -d --build
docker ps   # Memverifikasi status ketiga kontainer
```

---

## Verifikasi Konfigurasi Keamanan

### SSH Hardening

```bash
# Menampilkan konfigurasi SSH yang aktif
sudo sshd -T | grep -E '^(port|permitrootlogin|passwordauthentication)'
```

### Status Firewall UFW

```bash
sudo ufw status verbose
```

### Status Proteksi Fail2Ban

```bash
sudo fail2ban-client status
sudo fail2ban-client status sshd
```

### Status Kontainer dan Isolasi Jaringan Docker

```bash
# Menampilkan daftar kontainer yang berjalan beserta detail port
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'

# Menampilkan daftar Docker network
docker network ls

# Memverifikasi bahwa database tidak mengekspos port ke host (output seharusnya kosong)
docker port db_server

# Memverifikasi bahwa aplikasi berjalan sebagai user non-root
docker exec app_server whoami   # Output yang diharapkan: www-data
```

### Verifikasi Port yang Aktif pada Host

```bash
ss -tlnp
# Port yang seharusnya aktif: 2222 (SSH), 80 (HTTP), 443 (HTTPS)
```

### Verifikasi SSL/TLS

```bash
# Menguji koneksi HTTPS
curl -k https://localhost -I

# Menguji fungsionalitas redirect HTTP ke HTTPS
curl -v http://localhost 2>&1 | grep -E 'Location|< HTTP'

# Menampilkan informasi detail sertifikat SSL
docker exec reverse_proxy openssl x509 \
  -in /etc/ssl/certs/server.crt -text -noout \
  | grep -E 'Subject|Not After'
```

---

## Struktur Direktori

```
kel-03-topik-a/
├── docs/
│   ├── Arsitektur_Kelompok 3.pdf       ← Diagram dan penjelasan arsitektur sistem
│   ├── Laporan_Kelompok 3.pdf          ← Laporan teknis implementasi lengkap
│   └── Uji-Keamanan Sistem - Kelompok 03.pdf  ← Hasil pengujian keamanan (Nmap, Nikto, dll.)
├── docker/
│   ├── docker-compose.yaml             ← Definisi dan orkestrasi 3 kontainer
│   ├── nginx/
│   │   ├── default.conf                ← Konfigurasi Nginx reverse proxy dan SSL
│   │   ├── server.crt                  ← Sertifikat SSL (Self-Signed)
│   │   └── server.key                  ← Private Key SSL
│   ├── app/
│   │   ├── Dockerfile                  ← Definisi image PHP-FPM untuk Laravel
│   │   └── ...                         ← Source code aplikasi Laravel
│   └── db/
│       └── init.sql                    ← Skrip inisialisasi skema database
├── scripts/
│   ├── setup.sh                        ← Skrip konfigurasi dan deployment awal VM
│   └── backup.sh                       ← Skrip backup terjadwal database dan aplikasi
├── README.md                           ← Dokumentasi proyek ini
└── .env.example                        ← Template konfigurasi environment variables
```

---

## Mekanisme Backup Otomatis

```bash
# Eksekusi backup secara manual
chmod +x scripts/backup.sh
./scripts/backup.sh

# Konfigurasi backup terjadwal otomatis setiap hari pukul 02.00 WIB
crontab -e
# Tambahkan entri cron berikut:
# 0 2 * * * /home/ubuntu/kel-03-topik-a-/scripts/backup.sh >> /var/log/backup.log 2>&1
```

Hasil backup disimpan pada direktori `/home/ubuntu/backups/` dan secara otomatis dihapus setelah masa retensi 7 hari.

---

## Video Demonstrasi

> **Tautan Video:** [Google Drive](https://drive.google.com/drive/folders/1PmBP27V_x76xFAErYr0DlkslZt4TAyHh?usp=sharing)

Video demonstrasi menampilkan:
- Output perintah `docker ps` dan `docker network ls`
- Output perintah `ufw status verbose`
- Output perintah `fail2ban-client status sshd`
- Akses aplikasi web melalui browser (termasuk proses redirect HTTP → HTTPS)
- Verifikasi sertifikat SSL melalui browser

---

## Ringkasan Implementasi Keamanan

| No. | Aspek Keamanan | Implementasi Teknis | Status |
|---|---|---|---|
| 1 | Autentikasi SSH Berbasis Kunci | `PasswordAuthentication no` | ✅ Diterapkan |
| 2 | Perubahan Port SSH Default | Port `2222` (dari default `22`) | ✅ Diterapkan |
| 3 | Penonaktifan Root Login | `PermitRootLogin no` | ✅ Diterapkan |
| 4 | Firewall Berbasis UFW | Mengizinkan hanya port 80, 443, dan 2222 | ✅ Diterapkan |
| 5 | Proteksi Brute-Force | Fail2Ban (blokir 1 jam setelah 5x gagal) | ✅ Diterapkan |
| 6 | Isolasi Kontainer | 3 kontainer terpisah: proxy, app, dan database | ✅ Diterapkan |
| 7 | Isolasi Jaringan Docker | Backend network dikonfigurasi `internal: true` | ✅ Diterapkan |
| 8 | Pembatasan Eksposur Port Database | Port 3306 tidak dipublikasikan ke host | ✅ Diterapkan |
| 9 | Kontainer Berbasis Prinsip Least Privilege | Aplikasi berjalan sebagai user `www-data` (non-root) | ✅ Diterapkan |
| 10 | Enkripsi HTTPS Wajib | SSL/TLS aktif dengan redirect paksa HTTP→HTTPS | ✅ Diterapkan |
| 11 | Manajemen Kredensial | Seluruh kredensial disimpan dalam file `.env` | ✅ Diterapkan |

---

*Kelompok 03 — Tugas Besar Keamanan Server & Jaringan | 2026*
