# Clickjacking

## Deskripsi
Clickjacking terjadi ketika halaman web bisa di-embed dalam `<iframe>` oleh situs lain. Attacker membuat halaman palsu dengan overlay transparan, sehingga user mengklik elemen di halaman korban tanpa sadar.

## Endpoint Vulnerable
- **URL:** `/public/checkout.php`
- **Masalah:** Tidak ada header `X-Frame-Options` atau `Content-Security-Policy: frame-ancestors`

## Demo
Buka `/public/demo/clickjack.html` untuk melihat demonstrasi serangan clickjacking.

## Cara Eksploitasi

### 1. Buat halaman HTML attacker
```html
<iframe src="http://localhost:8080/public/checkout.php" 
        style="opacity: 0; position: absolute; width: 100%; height: 100%;">
</iframe>
<button style="position: relative; z-index: -1;">Klaim Hadiah!</button>
```

### 2. User mengklik "Klaim Hadiah" tapi sebenarnya mengklik "Buat Pesanan"

## Lokasi di Kode
File: `app/public/checkout.php`
```php
// [VULN: CLICKJACKING] - No X-Frame-Options header intentional
```

## Cara Fix
```php
// Tambahkan di awal file checkout.php:
header('X-Frame-Options: DENY');
header('Content-Security-Policy: frame-ancestors \'none\'');
```

## SIEM Detection
Clickjacking tidak terdeteksi oleh SIEM karena serangan terjadi di sisi client (browser korban). Pencegahan harus dilakukan di level response header.
