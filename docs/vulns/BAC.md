# BAC (Broken Access Control)

## Deskripsi
Broken Access Control terjadi ketika aplikasi tidak memvalidasi role/permission user dengan benar. Di nhsec, halaman admin hanya mengecek apakah user sudah login, tapi TIDAK mengecek apakah user adalah admin.

## Endpoint Vulnerable
- **URL:** `/admin/index.php`, `/admin/products.php`, `/admin/users.php`, `/admin/orders.php`
- **Masalah:** Menggunakan `requireLoginOnly()` bukan `isAdmin()` check

## Cara Eksploitasi

1. Login sebagai user biasa: `budi:budi123`
2. Akses langsung ke `/admin/` 
3. User biasa bisa melihat admin dashboard, CRUD produk, manage users, dan manage orders

## Lokasi di Kode
File: `app/admin/index.php` (dan semua file admin lainnya)
```php
// [VULN: BAC] - Only checks login, not admin role intentional
requireLoginOnly();  // seharusnya cek isAdmin()
```

File: `app/includes/functions.php`
```php
// [VULN: BAC] - this function only checks login, not admin role intentional
function requireLoginOnly() {
    if (!isLoggedIn()) { header('Location: /public/login.php'); exit; }
}
```

## Cara Fix
```php
// Ganti requireLoginOnly() di semua file admin dengan:
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        die('Access Denied: Admin only');
    }
}
```

## SIEM Detection
Rule `BAC` mendeteksi non-admin user yang mengakses endpoint `/admin/*`.
