# Dokumen Arsitektur Sistem
**Tugas Besar Keamanan Server & Jaringan**
**Kelompok 03 - Topik A**

## 1. Topologi dan Jaringan

Sistem dibangun di atas sebuah Virtual Machine (Ubuntu Linux) dengan IP Utama `172.30.67.106` (berada di dalam jaringan kampus/Eduroam). Untuk mempermudah akses (remote access) dari luar jaringan kampus tanpa perlu terkoneksi ke Eduroam, server ini juga dihubungkan ke VPN *Tailscale* dengan IP `100.110.137.96`. 
Arsitektur jaringan dirancang dengan prinsip *defense in depth*, di mana akses dari luar dibatasi ketat oleh firewall dan aplikasi diisolasi di dalam *container* menggunakan *Docker Network*.

### Diagram Arsitektur

```text
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
    │     IP Asli: 172.30.67.106      │
    │     IP VPN: 100.110.137.96      │
    │                                 │
    │  ┌──── Docker Network ────────┐ │
    │  │                            │ │
    │  │  [frontend network]        │ │
    │  │  ┌─────────────────────┐   │ │
    │  │  │  reverse_proxy      │   │ │
 80 ──►──►│  nginx:alpine       │   │ │
443 ──►──►│  HTTPS + SSL        │   │ │
    │  │  └──────────┬──────────┘   │ │
    │  │             │ FastCGI:9000 │ │
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

## 2. Komponen Container (Docker)

Sistem memisahkan komponen ke dalam 3 *container* terpisah untuk meminimalisir dampak jika salah satu komponen diretas:

1. **`reverse_proxy` (Nginx Alpine)**
   - Bertindak sebagai gerbang masuk utama dari internet.
   - Mengelola SSL/TLS (HTTPS) dan melakukan *redirect* paksa dari HTTP (80) ke HTTPS (443).
   - Sertifikat SSL di-*mount* ke kontainer Nginx via *volume*.
   - Meneruskan *request* PHP ke `app_server` melalui FastCGI.
   - Berada di dalam *bridge network* `frontend`.

2. **`app_server` (PHP 8.3 FPM - Laravel)**
   - Menjalankan kode aplikasi Laravel.
   - Tidak memiliki port yang diekspos ke internet. Akses HTTP hanya dilayani oleh Nginx.
   - Berjalan menggunakan *user* non-root (`www-data`) untuk mencegah eskalasi hak istimewa (privilege escalation) jika terjadi celah keamanan pada aplikasi.
   - Terhubung ke *bridge network* `frontend` (untuk komunikasi dengan Nginx) dan *bridge network* `backend` (untuk komunikasi dengan Database).

3. **`db_server` (MySQL 8.0)**
   - Menyimpan data aplikasi (tabel `users` dan `audit_logs`).
   - Menggunakan *Docker bridge network yang terpisah per tier*, di mana kontainer ini berada di dalam *network* khusus `backend` berstatus `internal: true`. Artinya kontainer ini terisolasi total dari akses internet.
   - Kontainer database tidak boleh mengekspos port ke Host OS (Port 3306 tidak di-publish). Hanya `app_server` yang dapat mengakses database melalui jaringan internal Docker.

## 3. Manajemen Data & Kredensial (.env)

Sebagai standar *Container Security*, kode sumber tidak boleh mengandung kata sandi atau *credentials* secara langsung (*hardcoded*).
- **Penggunaan file `.env`**: Semua *credentials* database dan *application key* disimpan dengan aman di dalam file `.env`.
- **Permission Ketat**: Diatur *permission* ketat pada direktori dan file sensitif. File `.env` dikunci akses tulisnya (menggunakan `chmod 644`), dan *private key* sertifikat SSL dikunci akses bacanya hanya untuk pemilik (menggunakan `chmod 600`).

## 4. Komponen Keamanan Host (VM)

1. **UFW (Uncomplicated Firewall)**
   - Menggugurkan semua koneksi masuk yang tidak dikenali (`deny incoming`).
   - Hanya mengizinkan port `2222/tcp` (SSH), `80/tcp` (HTTP), dan `443/tcp` (HTTPS).

2. **SSH Hardening & Fail2Ban**
   - Port standar SSH (22) dipindahkan ke `2222` untuk mengurangi serangan *bot/scanner* massal.
   - Autentikasi menggunakan kata sandi dinonaktifkan (`PasswordAuthentication no`), mewajibkan penggunaan SSH Key.
   - Akses login langsung menggunakan akun `root` dinonaktifkan.
   - Fail2Ban memantau log `/var/log/auth.log` dan akan memblokir (banning) IP Address selama 1 jam jika terjadi kegagalan autentikasi SSH sebanyak 5 kali berturut-turut.
