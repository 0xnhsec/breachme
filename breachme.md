# breachme — Vulnerable Marketplace Platform

## Context
Intentionally vulnerable marketplace web app "breachme"
(tema: jual beli laptop gaming & stiker waifu) untuk edukasi
keamanan web secara lokal. Inspired by DVWA but more realistic.
Model marketplace Tokopedia: user = pembeli + penjual, admin = pengelola platform.

## Tech Stack
### Frontend
- HTML5 + CSS3 (manual, no Tailwind)
- Bootstrap 5.3 (CDN)
- Vanilla JavaScript (no framework)
- Google Fonts: Inter (body) + JetBrains Mono (code/badge/price)

### Backend
- PHP 8.1 Native — NO framework (bukan Laravel/CodeIgniter)
- Apache (mod_php) sebagai web server
- Session PHP native untuk autentikasi

### Database
- MySQL 8.0
- Akses via mysqli (procedural)

### DevOps
- Docker + docker-compose (3 container: web, db, phpmyadmin)

### UI/UX Direction
- Dark theme corporate cybersecurity (referensi: Cloudflare, CrowdStrike)
- Background #050505, accent electric blue #00d4ff
- Sharp corners (border-radius max 8px), bukan rounded pill
- Grid produk: CSS Grid auto-fill minmax(240px, 1fr)
- ZERO vuln indicator di halaman publik — tampilan realistis seperti toko nyata

---

## Prinsip UI (PENTING)
- Tampilan marketplace HARUS realistis seperti toko online nyata
- ZERO vuln indicator di halaman publik (tidak ada badge, label, watermark)
- SIEM dashboard hanya untuk admin (role check ketat)
- Tidak ada toggle Attacker/Defender di navbar publik
- Grid produk fluid dan merata saat zoom in/out (CSS Grid auto-fill)
- Referensi: Tokopedia/Shopee untuk product listing

## UI Requirements

### E-commerce (app/)
- Tampilan HARUS seperti e-commerce nyata — tidak ada vuln badge/label di UI
- Tidak ada indikator "MODE: VULNERABLE" di halaman publik
- Grid produk responsive: fluid columns, merata saat zoom in/out
- Toggle Attacker/Defender HILANGKAN dari navbar publik

### SIEM Dashboard (siem/)
- Hanya accessible oleh user dengan role=admin
- Non-admin redirect ke /public/index.php
- Tidak ada link ke SIEM dari navbar publik
- Admin bisa akses via /admin/ panel saja

### Error Pages (WAJIB ADA)
Buat custom error pages di app/public/errors/ dengan dark theme yang sama:
- 404.php — "Halaman tidak ditemukan" (Not Found)
- 403.php — "Akses ditolak" (Forbidden) — muncul saat user coba akses /admin/ tanpa login
- 500.php — "Terjadi kesalahan server" (Internal Server Error)
- 503.php — "Layanan tidak tersedia" (Service Unavailable)

Konfigurasi di .htaccess:
```apache
ErrorDocument 404 /public/errors/404.php
ErrorDocument 403 /public/errors/403.php
ErrorDocument 500 /public/errors/500.php
ErrorDocument 503 /public/errors/503.php
```

Setiap error page:
- Pakai layout/header yang sama dengan web (dark theme, navbar minimal)
- Tampilkan kode error + pesan singkat + tombol "Kembali ke Beranda"
- Untuk 403: JANGAN tunjukkan path yang dicoba diakses (security best practice sekaligus realistis)
- Untuk 500: tampilkan error ID acak (misal ERR-a3f9c) bukan stack trace

---

## Struktur Folder
```
breachme/
├── app/
│   ├── public/
│   │   ├── errors/        # Custom error pages (404, 403, 500, 503)
│   │   ├── demo/          # Attack demo pages
│   │   └── ...            # Semua halaman user
│   ├── admin/             # Admin panel (terpisah dari user)
│   ├── uploads/
│   │   ├── products/      # Upload gambar produk (777) [VULN: writable + no mime check]
│   │   └── avatars/       # Upload foto profil (777) [VULN: writable]
│   ├── config/
│   │   └── database.php   # DB connection + APP_MODE
│   └── includes/
│       ├── functions.php  # Core functions + SIEM logger
│       ├── header.php     # Navbar + CSS design system
│       └── footer.php     # Footer + Bootstrap JS
├── siem/
│   ├── dashboard.php      # Defender dashboard (admin only)
│   └── api/               # SIEM API endpoints
├── db/
│   └── init.sql           # Schema + seed data
├── docs/vulns/            # Dokumentasi tiap vuln
├── docker-compose.yml
├── Dockerfile
├── .htaccess              # Error page routing
└── CLAUDE.md
```

---

## Konsep Platform
- Admin = pengelola platform (bukan penjual)
- User = bisa jadi pembeli DAN penjual
- Setiap transaksi kena pajak 2% masuk ke admin
- E-wallet terintegrasi untuk semua transaksi
- Model C2C seperti Tokopedia

## Fitur Marketplace

### User (/public/)
- Register/Login/Logout
- Browse & search produk
- Detail produk + review/komentar
- Jual produk (upload listing + gambar)
- Add to cart + checkout via e-wallet
- Beli Langsung (direct buy)
- Lihat order history
- Lihat invoice/struk pribadi
- E-wallet: saldo + riwayat transaksi
- Notifikasi platform (bell icon + unread count)
- Edit profil + upload avatar + ganti password

### Admin (/admin/) — TERPISAH TOTAL
- Dashboard overview platform
- Kelola user (ban, label, lihat detail)
- Kelola produk (approve/reject listing)
- Lihat semua invoice + data transaksi
- Pajak: 2% tiap transaksi masuk admin wallet
- IP management: lihat IP aktif, blokir/buka blokir
- Link ke SIEM dashboard

### SIEM (/siem/) — admin only
- Real-time request log
- Alert rules per vuln type
- True Positive / False Positive toggle
- Attack timeline
- Traffic monitoring + top IPs
- IP block dari dashboard

---

## Product Image Upload
- Seller upload gambar saat pasang produk
- Accepted types: jpg, jpeg, png, gif, webp
- Max size: 5MB
- Saved to: /uploads/products/{uniqid}.{ext}
- Fallback: Bootstrap icon jika default.jpg
- [VULN: UNRESTRICTED_UPLOAD] validasi hanya ekstensi, bukan magic bytes

## Database Tables
- users (balance, ip_address, is_blocked)
- products (seller_id, status, image)
- reviews, cart, orders, order_items
- vouchers, voucher_usage
- wallets (riwayat e-wallet)
- invoices (struk + pajak 2%)
- notifications (per user)
- blocked_ips
- request_logs, alerts (SIEM engine)

---

## Role Separation

### User Flow
```
Guest → browse produk
Login → beli, jual, wallet, invoice, notif, profil
User TIDAK BISA akses /admin/* atau /siem/*
403 page muncul jika direct URL ke /admin/
(kecuali berhasil bypass via IDOR/BAC — itu memang vuln-nya)
```

### Admin Flow
```
Login admin → /admin/dashboard.php
Admin BISA akses semua: /admin/* dan /siem/*
Admin tidak muncul sebagai seller di marketplace
```

---

## Vulnerabilities

### Phase 1 (sudah diimplementasi)
1. **XSS Stored** — review produk tidak disanitasi
2. **Clickjacking** — checkout.php tanpa X-Frame-Options header
3. **BAC** — /admin/ip-management.php pakai requireLoginOnly() bukan requireAdmin()
4. **IDOR** — /order.php?id=X dan /invoice.php?id=X tanpa ownership check
5. **Business Logic** — voucher unlimited + qty negatif tidak dicek
6. **Unrestricted File Upload** — validasi ekstensi saja, bukan magic bytes

### BAC Detail
- Semua /admin/* pakai requireAdmin() — terlihat aman
- /admin/ip-management.php sengaja pakai requireLoginOnly() — BYPASS POINT
- Session role tidak di-reverifikasi dari DB per request

### IDOR Detail
- /public/order.php?id=X — query tanpa filter user_id
- /public/invoice.php?id=X — tanpa cek buyer_id/seller_id

### Password Hashing
- Admin seed: MD5 // [VULN: WEAK_HASH] intentional
- User baru: password_hash() bcrypt
- Login support keduanya

### Phase 2 (belum diimplementasi)
7. **CSRF** — form ganti password tanpa token
8. **LFI** — /invoice.php?template= tanpa path sanitization
9. **SSRF** — fitur preview gambar dari URL eksternal
10. **XXE** — import wishlist via XML upload
11. **XSS Reflected** — search parameter di-echo langsung

### Phase 3 (advanced)
12. **XSS DOM-based** — filter via URL hash location.hash
13. **OS Command Injection** — shell_exec() di fitur resize gambar
14. **JWT Attack** — /api/auth endpoint, alg:none + weak secret
15. **API Testing** — mass assignment, BOLA, no rate limit
16. **Weak Hash** — admin MD5 crackable via hashcat/CrackStation

---

## SIEM Mode

### Fix WAJIB (path error aktif sekarang)
```php
// siem/dashboard.php line 3 — GANTI INI:
require_once('../app/config/database.php');  // SALAH — relative path gagal di Docker

// MENJADI:
require_once __DIR__ . '/../app/config/database.php';  // BENAR — absolute path
```
Error aktif: `Failed to open stream: No such file or directory in /var/www/html/siem/dashboard.php on line 3`

### Alert Rules
- XSS: deteksi `<script>` atau `onerror=` di parameter
- BAC: akses /admin oleh non-admin user
- IDOR: user akses order/invoice bukan miliknya
- Business Logic: voucher dipakai >1x user sama
- File Upload: ekstensi .php/.phtml/.phar diupload
- LFI: deteksi `../` atau `/etc/` (Phase 2)
- SSRF: deteksi request ke 127.0.0.1/localhost (Phase 2)
- XXE: deteksi `<!ENTITY` atau `SYSTEM` di XML (Phase 2)

---

## Seed Data
- 6 users: admin, budi, sari, noshiro, eka, dimas
- Balance Rp 5.000.000 per user
- IP berbeda per user (simulasi)
- 10 produk: 5 laptop gaming + 5 stiker waifu
- 4 transaksi selesai + invoice
- Wallet entries, notifikasi, review samples
- 3 voucher: DISKON10 (active), GAMER20 (active), WAIFU50 (inactive)

## APP_MODE
- `APP_MODE=vulnerable` (default) — semua vuln aktif
- `APP_MODE=secure` — semua fix diterapkan (untuk compare)
Diset di docker-compose.yml, dibaca di config/database.php

---

## CHECKLIST PENGEMBANGAN breachme

> [ ] = belum | [x] = selesai | [~] = partial/ada bug
> Update setiap task selesai diverifikasi.

---

### FASE 0 — Infrastruktur

- [x] Docker + docker-compose berjalan
- [x] Container web PHP 8.1 + Apache aktif port 8080
- [x] Container MySQL 8.0 aktif port 3306
- [x] Container phpMyAdmin aktif port 8081
- [x] Database terbuat otomatis dari init.sql
- [x] Semua tabel terbuat
- [x] Seed data 6 user
- [x] Seed data 10 produk
- [x] CLAUDE.md dibuat
- [x] README.md dibuat
- [ ] README.md nama diganti dari "nhsec" → "breachme"
- [x] APP_MODE=vulnerable di docker-compose.yml
- [ ] APP_MODE=secure tersedia (untuk compare fix)
- [x] /uploads/products/ ada + writable (777)
- [x] /uploads/avatars/ ada + writable (777)
- [x] .htaccess custom error pages terkonfigurasi

---

### FASE 1 — UI & Halaman

#### Error Pages (BELUM ADA — kerjakan sebelum Phase 2 vuln)
- [x] app/public/errors/404.php — dark theme, tombol kembali ke beranda
- [x] app/public/errors/403.php — dark theme, tidak expose path yang dicoba
- [x] app/public/errors/500.php — dark theme, tampilkan error ID acak (ERR-xxxxx)
- [x] app/public/errors/503.php — dark theme
- [x] .htaccess routing ke semua error pages

#### Halaman User
- [x] index.php — homepage marketplace
- [x] login.php
- [x] register.php
- [x] product.php — detail + review [VULN: XSS]
- [x] sell.php — upload produk + gambar
- [x] cart.php
- [x] checkout.php [VULN: Clickjacking]
- [x] order.php [VULN: IDOR]
- [x] orders.php
- [x] invoice.php [VULN: IDOR]
- [x] invoices.php
- [x] wallet.php
- [x] notifications.php
- [x] profile.php
- [x] search.php — halaman dedicated hasil pencarian
- [ ] seller.php — profil penjual publik

#### Halaman Admin
- [x] admin/index.php — dashboard
- [x] admin/users.php
- [x] admin/products.php
- [x] admin/invoices.php
- [x] admin/ip-management.php [VULN: BAC bypass point]
- [ ] admin/reports.php — laporan pendapatan pajak

#### SIEM
- [x] siem/dashboard.php — path fix sudah __DIR__
- [ ] siem/api/logs.php
- [x] siem/api/alerts.php
- [ ] siem/api/stats.php

#### UI Quality
- [x] Dark theme corporate diterapkan
- [x] Navbar + bell notifikasi + unread count
- [x] Navbar + search bar
- [x] Grid produk fluid CSS auto-fill
- [x] Zero vuln badge di halaman publik
- [x] Print-friendly invoice (CSS @media print)
- [ ] Empty state proper di setiap halaman list

---

### FASE 2 — Autentikasi & Role

- [x] Login dengan session PHP
- [x] Register dengan bcrypt
- [x] Legacy MD5 support untuk admin [VULN: WEAK_HASH]
- [x] Role separation: user vs admin
- [x] requireAdmin() di admin pages
- [x] requireLogin() di user pages
- [x] 403 redirect untuk user yang akses /admin/ (kecuali bypass via vuln)
- [ ] Session timeout (2 jam idle)
- [x] Logout bersihkan session

---

### FASE 3 — Marketplace Core

#### E-Wallet
- [x] Saldo awal Rp 5.000.000
- [x] Debit buyer saat checkout
- [x] Kredit seller saat transaksi
- [x] Pajak 2% ke admin wallet
- [x] Riwayat wallet tersimpan
- [ ] Validasi saldo tidak negatif — SENGAJA TIDAK ADA [VULN: BUSINESS-LOGIC]

#### Produk
- [x] User upload listing baru
- [x] Upload gambar produk [VULN: UNRESTRICTED_UPLOAD]
- [x] Admin approve/reject
- [x] Status: pending, active, rejected
- [ ] Edit produk oleh seller
- [ ] Hapus produk oleh seller/admin
- [ ] Pagination produk (per 12 item)

#### Transaksi
- [x] Add to cart
- [x] Checkout dari cart
- [x] Beli langsung
- [x] Voucher [VULN: unlimited usage]
- [x] Qty negatif tidak dicek [VULN: BUSINESS-LOGIC]
- [x] Invoice otomatis INV-YYYYMMDD-XXXXX
- [ ] Order status update oleh seller (pending → shipped → done)
- [ ] Dispute/komplain order (admin mediasi)

#### Notifikasi
- [x] "Produk terjual" → seller
- [x] "Pembelian berhasil" → buyer
- [x] Bell icon + unread count
- [ ] "Produk diapprove/direject" → seller
- [ ] Mark all as read

---

### FASE 4 — Vulnerabilities

#### Phase 1 — Verifikasi Manual (semua harus [x] sebelum Phase 2)
- [x] **XSS Stored** implemented
  - [x] Verified: `<h1>ayam</h1>` render di halaman
  - [ ] Verified: `<script>alert(1)</script>` muncul popup
  - [ ] Verified: cookie stealing PoC berhasil
  - [x] docs/vulns/XSS.md ada
- [~] **Clickjacking** implemented
  - [ ] Verified: demo/clickjack.html berhasil embed checkout
  - [x] docs/vulns/Clickjacking.md ada
- [~] **BAC** implemented
  - [ ] Verified: user biasa akses /admin/ip-management.php langsung
  - [ ] Verified: catat endpoint lain yang masih bypassable
  - [x] docs/vulns/BAC.md ada
- [~] **IDOR** implemented
  - [ ] Verified: login budi → akses /order.php?id=[order milik sari]
  - [ ] Verified: login budi → akses /invoice.php?id=[invoice milik noshiro]
  - [x] docs/vulns/IDOR.md ada
- [~] **Business Logic** implemented
  - [ ] Verified: voucher DISKON10 dipakai 2x+ oleh user yang sama
  - [ ] Verified: checkout dengan qty = -1 menambah saldo
  - [x] docs/vulns/BusinessLogic.md ada
- [~] **Unrestricted File Upload** implemented
  - [ ] Verified: upload file shell.php berhasil tersimpan
  - [ ] Verified: akses /uploads/products/shell.php → PHP executed
  - [x] docs/vulns/UnrestrictedUpload.md ada

#### Phase 2 — Implementasi + Verifikasi
- [ ] **CSRF** — profile.php ganti password/email tanpa CSRF token
  - [x] Implemented
  - [x] Demo PoC: demo/csrf_demo.html
  - [ ] Verified: request dari domain lain berhasil ganti password
  - [x] docs/vulns/CSRF.md
- [ ] **LFI** — /invoice.php?template= tanpa path sanitization
  - [x] Implemented
  - [ ] Verified: ?template=../../../etc/passwd membaca file
  - [ ] Verified: LFI + uploaded PHP = RCE chain
  - [x] docs/vulns/LFI.md
- [ ] **SSRF** — fitur "import gambar produk dari URL"
  - [x] Implemented (import_image.php)
  - [ ] Verified: URL http://127.0.0.1:8081 berhasil di-fetch
  - [x] docs/vulns/SSRF.md
- [ ] **XXE** — import wishlist via XML upload
  - [x] Implemented (wishlist_import.php)
  - [ ] Verified: payload <!ENTITY baca /etc/passwd
  - [x] docs/vulns/XXE.md
- [ ] **XSS Reflected** — /search.php?q= di-echo tanpa encoding
  - [x] Implemented
  - [ ] Verified: payload muncul tanpa encoding di halaman
  - [x] docs/vulns/XSSReflected.md (update)

#### Phase 3 — Advanced
- [ ] **XSS DOM-based** — location.hash tanpa sanitasi
  - [ ] docs/vulns/XSS.md (update)
- [ ] **OS Command Injection** — shell_exec() di resize gambar
  - [ ] docs/vulns/CMDi.md
- [ ] **JWT Attack** — /api/auth endpoint, alg:none + weak secret
  - [ ] docs/vulns/JWT.md
- [ ] **API Testing** — mass assignment, BOLA, no rate limit
  - [ ] docs/vulns/API.md
- [ ] **Weak Hash** — crack admin MD5 via hashcat
  - [ ] docs/vulns/WeakHash.md

---

### FASE 5 — SIEM

- [~] siem/dashboard.php accessible admin
- [x] FIX PATH ERROR: `require_once __DIR__ . '/../app/config/database.php'`
- [ ] Real-time log ditampilkan (polling atau SSE)
- [x] Alert rule: XSS aktif
- [x] Alert rule: BAC aktif
- [x] Alert rule: IDOR aktif
- [x] Alert rule: Business Logic aktif
- [ ] Alert rule: File Upload aktif
- [ ] Alert rule: LFI aktif (Phase 2)
- [ ] Alert rule: SSRF aktif (Phase 2)
- [ ] Alert rule: XXE aktif (Phase 2)
- [x] TP/FP toggle per alert berfungsi
- [x] Attack timeline view
- [x] Top IPs monitoring
- [x] IP blocking dari SIEM trigger blocked_ips table
- [ ] Traffic chart request per waktu

---

### FASE 6 — Dokumentasi Vuln

> Format tiap .md: deskripsi → endpoint → langkah eksploitasi → expected output → cara fix

- [x] docs/vulns/XSS.md
- [x] docs/vulns/Clickjacking.md
- [x] docs/vulns/BAC.md
- [x] docs/vulns/IDOR.md
- [x] docs/vulns/BusinessLogic.md
- [x] docs/vulns/UnrestrictedUpload.md
- [x] docs/vulns/CSRF.md
- [x] docs/vulns/LFI.md
- [x] docs/vulns/SSRF.md
- [x] docs/vulns/XXE.md
- [x] docs/vulns/XSSReflected.md
- [ ] docs/vulns/CMDi.md
- [ ] docs/vulns/JWT.md
- [ ] docs/vulns/API.md
- [ ] docs/vulns/WeakHash.md
- [ ] docs/ATTACK_CHAINS.md — kombinasi vuln (LFI+FileUpload=RCE, XSS+CSRF, dll)

---

### FASE 7 — Testing

#### Fungsional (non-vuln)
- [ ] Register user baru berhasil
- [ ] Login + logout berhasil
- [ ] Upload produk + gambar tampil di listing
- [ ] Checkout + saldo buyer berkurang
- [ ] Saldo seller bertambah setelah produk terjual
- [ ] Invoice terbuat (INV-xxx)
- [ ] Notifikasi masuk buyer + seller
- [ ] Admin approve/reject produk
- [ ] Admin blokir IP
- [ ] SIEM menampilkan log request
- [ ] Error 404 muncul untuk URL tidak dikenal
- [ ] Error 403 muncul untuk user akses /admin/
- [ ] Error 500 tidak expose stack trace

#### Vulnerability (Burp Suite manual)
- [ ] Semua Phase 1 ditest + confirmed
- [ ] Semua Phase 2 ditest + confirmed
- [ ] Setiap eksploitasi trigger alert di SIEM dashboard

---

### FASE 8 — Polish & Final

- [ ] README.md nama diupdate: nhsec → breachme di semua baris
- [ ] Semua credentials dummy terdokumentasi di README
- [ ] `docker-compose down -v && docker-compose up -d --build` = fresh state bersih
- [ ] Zero PHP warning/notice di halaman manapun (kecuali yang intentional)
- [ ] Zero broken link yang tidak disengaja
- [ ] APP_MODE=secure mengaktifkan semua perbaikan
- [ ] Custom error pages konsisten dengan dark theme

---

### PROGRESS SUMMARY

| Fase | Status | Keterangan |
|------|--------|------------|
| 0 — Infrastruktur | 95% | APP_MODE=secure belum |
| 1 — UI & Halaman | 90% | seller.php missing, SIEM fix sudah |
| 2 — Auth & Role | 85% | Session timeout belum |
| 3 — Marketplace | 75% | Edit/hapus produk, order status belum |
| 4 — Vulnerabilities | 65% | Phase 1 implemented, Phase 2 implemented belum verified |
| 5 — SIEM | 60% | Path fix done, alert rules Phase 1 aktif |
| 6 — Dokumentasi | 75% | Phase 1+2 docs ada, Phase 3 belum |
| 7 — Testing | 10% | XSS Stored partial saja |
| 8 — Polish | 50% | README diupdate, error pages done |

Vuln Implemented: 11/16
Vuln Verified: 1/16

---

### NEXT PRIORITY (urutan kerja Claude Code)

```
STEP 1 — Fix SIEM path error (1 baris)
  siem/dashboard.php line 3:
  require_once __DIR__ . '/../app/config/database.php'

STEP 2 — Buat custom error pages + .htaccess
  app/public/errors/404.php
  app/public/errors/403.php
  app/public/errors/500.php
  app/public/errors/503.php
  .htaccess di root app/

STEP 3 — Update README nama nhsec → breachme

STEP 4 — Verify Phase 1 (semua 6 vuln, manual via Burp Suite)
  Update checklist [x] untuk yang confirmed

STEP 5 — Implementasi Phase 2
  CSRF → LFI → SSRF → XXE → XSS Reflected
  Masing-masing: implement + demo page + docs/vulns/
```