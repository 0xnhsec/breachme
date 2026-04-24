# CLAUDE.md — breachme Project Notes

## Tujuan
Aplikasi ini dibuat untuk **edukasi keamanan web secara lokal**. Semua kerentanan yang ada di dalam kode bersifat **intentional** (disengaja) dan diberi label komentar khusus.

> ⚠️ **JANGAN DEPLOY DI SERVER PUBLIK!**

## Konsep Platform
breachme adalah **marketplace** (model Tokopedia):
- **Admin** = pengelola platform (pajak 2%, kelola user/produk/IP)
- **User** = bisa jadi pembeli DAN penjual
- Transaksi menggunakan **e-wallet** terintegrasi

## Konvensi Labeling Vulnerability

Setiap vulnerability di kode diberi komentar dengan format:
```php
// [VULN: NAMA] - deskripsi singkat intentional
```

Contoh:
```php
// [VULN: XSS] - no sanitization intentional
// [VULN: BAC] - Only checks login, not admin role intentional
// [VULN: BAC] - uses requireAdmin() but bypassable intentional
// [VULN: IDOR] - query does NOT filter by user_id intentional
// [VULN: BUSINESS-LOGIC] - no negative quantity check intentional
// [VULN: CLICKJACKING] - No X-Frame-Options header intentional
// [VULN: WEAK_HASH] - admin password stored as MD5 intentional
```

## Password Hashing
- **Admin/legacy users**: MD5 hash — intentionally weak (`// [VULN: WEAK_HASH]`)
- **New users**: `password_hash()` with bcrypt
- Login logic supports BOTH formats (checks MD5 first, then bcrypt)

## Tidak Ada Real Payment Gateway
Semua transaksi checkout menggunakan **e-wallet simulasi**. Saldo awal setiap user Rp 5.000.000. Pajak 2% dipotong otomatis dan masuk ke saldo admin.

## Data User
Semua data user (username, email, password) adalah **dummy/fictional**:
- admin / admin123 (role: admin, MD5)
- budi / budi123 (role: user, bcrypt)
- sari / sari123 (role: user, bcrypt)
- noshiro / noshiro123 (role: user, bcrypt)
- eka / eka123 (role: user, bcrypt)
- dimas / dimas123 (role: user, bcrypt)

## APP_MODE

Environment variable `APP_MODE` mengontrol perilaku aplikasi:
- `APP_MODE=vulnerable` (default) — Semua vulnerability aktif
- `APP_MODE=secure` — Untuk perbandingan ketika fix diterapkan (future)

Diset di `docker-compose.yml` dan dibaca di `app/config/database.php`.

## Stack
- PHP 8.1 Native (no framework)
- MySQL 8.0
- Bootstrap 5.3 (CDN)
- Docker + docker-compose

## Product Image Upload
- Seller upload gambar saat pasang produk (`sell.php`)
- Disimpan di `app/uploads/products/{uniqid}.{ext}`
- Tampil di grid produk (index.php) dan halaman detail (product.php)
- Fallback ke ikon Bootstrap jika `image = 'default.jpg'` atau kosong
- `[VULN: UNRESTRICTED_UPLOAD]` — hanya cek ekstensi, bukan magic bytes (intentional)

## UI Requirements
- Tampilan HARUS seperti e-commerce nyata — zero vuln badge/indicator di halaman publik
- Grid produk fluid: CSS Grid `auto-fill minmax(240px,1fr)`
- SIEM hanya accessible via admin panel, non-admin redirect ke /public/index.php
- Nama aplikasi: **breachme** (bukan nhsec)

## Dokumentasi Vulnerability
Setiap vulnerability didokumentasikan di `docs/vulns/`:
- `XSS.md` — Stored XSS di review
- `Clickjacking.md` — Missing X-Frame-Options
- `BAC.md` — Broken Access Control (bypassable middleware)
- `IDOR.md` — Insecure Direct Object Reference (order + invoice)
- `BusinessLogic.md` — Voucher abuse + negative quantity
- `UnrestrictedUpload.md` — Product image upload (extension-only validation)
