# breachme — Vulnerable E-Commerce Lab 🛡️

> Intentionally vulnerable e-commerce web app untuk edukasi keamanan web.  
> Tema: Toko Laptop Gaming & Stiker Waifu.  
> Inspired by DVWA, but more realistic.

![PHP](https://img.shields.io/badge/PHP-8.1-blue) ![MySQL](https://img.shields.io/badge/MySQL-8.0-orange) ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple) ![Docker](https://img.shields.io/badge/Docker-Ready-green)

---

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Port **8080**, **8081**, dan **3306** harus bebas

### Setup
```bash
# Clone / masuk ke direktori project
cd breachme

# Build image & jalankan semua container (web + db + phpmyadmin)
docker-compose up -d --build

# Tunggu ~30 detik untuk MySQL initialization
# Cek status container
docker ps
```

> ⚠️ **Jangan gunakan** `docker run breachme-web` langsung!  
> App membutuhkan koneksi ke container MySQL (`breachme-db`).  
> Selalu gunakan `docker-compose up` agar semua service terkoneksi dalam satu network.

### Port Mapping

| Container | Port Host → Container | Keterangan |
|-----------|----------------------|------------|
| `nhsec-web` | `8080 → 80` | Apache PHP app |
| `nhsec-pma` | `8081 → 80` | phpMyAdmin |
| `nhsec-db`  | `3306 → 3306` | MySQL 8.0 |

### Akses
| Service | URL | Keterangan |
|---------|-----|------------|
| **breachme App** | http://localhost:8080 | E-commerce utama |
| **Profil User** | http://localhost:8080/public/profile.php | Pengaturan akun |
| **SIEM Dashboard** | http://localhost:8080/siem/dashboard.php | Defender mode |
| **phpMyAdmin** | http://localhost:8081 | Database admin |
| **Clickjacking Demo** | http://localhost:8080/public/demo/clickjack.html | Demo serangan |

### Dummy Credentials
| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Admin |
| `budi` | `budi123` | User |
| `sari` | `sari123` | User |
| `noshiro` | `noshiro123` | User |
| `eka` | `eka123` | User |
| `dimas` | `dimas123` | User |

---

## 🛠️ Troubleshooting

### ❌ Web tidak muncul / tidak bisa diakses

**1. Pastikan container berjalan:**
```bash
docker ps
# Harus ada: nhsec-web, nhsec-db, nhsec-pma dengan status "Up"
```

**2. Jangan pakai `docker run` langsung:**
```bash
# ❌ SALAH — app tidak bisa konek ke database
docker run -p 3000:3000 breachme-web

# ✅ BENAR — gunakan docker-compose
docker-compose up -d --build
```

**3. Port sudah terpakai (port conflict):**
```bash
sudo ss -tlnp | grep -E '8080|8081|3306'
docker-compose down
docker-compose up -d --build
```

**4. Rebuild ulang dari awal (jika ada perubahan kode):**
```bash
docker-compose down -v
docker-compose up -d --build
```

**5. Lihat log error:**
```bash
docker-compose logs -f web
```

---

## 🏗️ Arsitektur & Model Sistem

### Stack Teknologi

| Layer | Teknologi | Keterangan |
|-------|-----------|------------|
| **Frontend** | HTML5, Bootstrap 5.3, Vanilla JS | Dark theme, responsive |
| **Backend** | PHP 8.1 (Apache mod_php) | Procedural, no framework |
| **Database** | MySQL 8.0 | Relational, InnoDB engine |
| **Runtime** | Docker + Docker Compose | 3 container terisolasi |
| **Font** | Inter + JetBrains Mono | Google Fonts |

### Model Sistem: Marketplace C2C

```
breachme menggunakan model Consumer-to-Consumer (C2C) seperti Tokopedia:

  [User/Buyer]  ──buy──►  [Platform (breachme)]  ──sell──►  [User/Seller]
                                   │
                              2% Platform Tax
                              dikirim ke Admin wallet
```

**Aktor dalam sistem:**
- **Guest** — hanya bisa browse produk
- **User** — bisa beli, jual produk, kelola akun
- **Admin** — akses penuh + SIEM dashboard + terima pajak platform

### Flow Sistem (User Journey)

```
Register/Login
     │
     ▼
Browse Produk ──► Product Detail ──► Add to Cart
                                          │
                                          ▼
                                     Checkout
                                    (apply voucher)
                                          │
                                          ▼
                                   Debit Buyer Wallet
                                   Credit Seller Wallet
                                   2% Tax → Admin Wallet
                                          │
                                          ▼
                                   Order Created
                                   Invoice Generated
                                   Notifikasi dikirim
```

### Skema Database (ERD Ringkas)

```
users ─────┬──< products (seller_id)
           ├──< orders   (user_id)
           │       └──< order_items (product_id)
           ├──< cart     (user_id, product_id)
           ├──< wallets  (user_id)
           ├──< invoices (buyer_id, seller_id, product_id)
           ├──< reviews  (user_id, product_id)
           ├──< notifications (user_id)
           └──< voucher_usage (user_id)

request_logs ──< alerts (SIEM engine)
blocked_ips
```

### Alur Autentikasi

```
Login Form ──► MD5 check (seed users) │ bcrypt verify (registered users)
                    │
             Session: user_id, role
                    │
         ┌──────────┴──────────┐
      role=user            role=admin
    /public/*          /admin/* + /siem/*
```

---

## 👤 Fitur User Profile

Akses: **http://localhost:8080/public/profile.php** (atau dropdown navbar)

| Fitur | Keterangan |
|-------|------------|
| **Info Profil** | Edit display name & bio (maks 300 karakter) |
| **Foto Profil** | Upload avatar (JPG/PNG/GIF/WEBP, maks 2MB) |
| **Ganti Password** | Verifikasi password lama + strength indicator |
| **Hapus Akun** | Konfirmasi password, hapus permanen semua data |

Avatar disimpan di `app/uploads/avatars/` dan tampil di navbar serta halaman profil.

---

## 🎯 Vulnerabilities

### Phase 1 (Sudah Diimplementasi)

| # | Vulnerability | Endpoint | Severity |
|---|---------------|----------|----------|
| 1 | **XSS (Stored)** | `/public/product.php` — review komentar | High |
| 2 | **Clickjacking** | `/public/checkout.php` — no X-Frame-Options | Medium |
| 3 | **BAC** | `/admin/ip-management.php` — hanya cek login | Critical |
| 4 | **IDOR** | `/public/order.php?id=X` — no ownership check | High |
| 5 | **Business Logic** | Voucher unlimited + negative qty | Medium |
| 6 | **Unrestricted Upload** | `/public/sell.php` — ekstensi saja, bukan magic bytes | High |

### Phase 2 (Akan Datang)
- CSRF, LFI, SSRF, XXE, XSS Reflected

### Phase 3 (Advanced)
- DOM XSS, OS Command Injection, JWT Attack, API Testing, Weak Hash

Dokumentasi lengkap tiap vulnerability ada di `docs/vulns/`.

---

## 🔍 SIEM Mode

Akses di: `/siem/dashboard.php` — **Admin only**

### Fitur SIEM:
- Real-time HTTP request logging
- Alert rules: XSS, BAC, IDOR, Business Logic, File Upload
- True Positive / False Positive classification
- Attack timeline view
- Top IPs monitoring
- Auto-refresh setiap 15 detik

---

## 📁 Struktur Project

```
breachme/
├── app/
│   ├── public/
│   │   ├── errors/         # Custom error pages (404, 403, 500, 503)
│   │   ├── demo/           # Attack demo pages
│   │   ├── index.php       # Homepage marketplace
│   │   ├── login.php / register.php / logout.php
│   │   ├── profile.php     # User profile & settings
│   │   ├── product.php     # Detail produk [VULN: XSS]
│   │   ├── sell.php        # Jual produk [VULN: Unrestricted Upload]
│   │   ├── cart.php / checkout.php [VULN: Clickjacking]
│   │   ├── order.php [VULN: IDOR] / orders.php
│   │   ├── invoice.php [VULN: IDOR] / invoices.php
│   │   ├── wallet.php / notifications.php
│   ├── admin/              # Admin panel [VULN: BAC]
│   │   ├── index.php       # Dashboard
│   │   ├── users.php / products.php / orders.php / invoices.php
│   │   └── ip-management.php [VULN: BAC bypass — requireLoginOnly()]
│   ├── config/database.php
│   ├── includes/
│   │   ├── functions.php   # Core functions + SIEM engine
│   │   ├── header.php      # Navbar + CSS design system
│   │   └── footer.php
│   ├── uploads/
│   │   ├── products/       # Gambar produk (777) [VULN: writable]
│   │   └── avatars/        # Foto profil (777)
│   └── .htaccess           # Error routing + security headers
├── siem/
│   ├── dashboard.php       # Defender console (admin only)
│   └── api/                # SIEM API endpoints
├── db/init.sql             # Schema + seed data
├── docs/vulns/             # Dokumentasi vulnerability
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## 📝 Seed Data

- **6 Users:** admin, budi, sari, noshiro, eka, dimas
- **10 Produk:** 5 laptop gaming + 5 stiker waifu
- **4 Orders** dengan berbagai status
- **6 Reviews** (termasuk XSS demo data)
- **3 Vouchers:** DISKON10 (active), GAMER20 (active), WAIFU50 (inactive)

> **Catatan password:** Seed users memakai MD5 (intentional weak hash vuln).  
> User yang register via form menggunakan bcrypt (`password_hash()`).

---

## 🛑 Stop & Cleanup

```bash
# Stop containers (data tetap ada)
docker-compose down

# Stop + hapus semua data (fresh start)
docker-compose down -v

# Fresh rebuild total
docker-compose down -v && docker-compose up -d --build
```
