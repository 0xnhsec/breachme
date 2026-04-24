# IDOR (Insecure Direct Object Reference)

## Deskripsi
IDOR terjadi ketika aplikasi mengekspos referensi langsung ke objek internal (misal ID order) tanpa validasi kepemilikan. User bisa mengakses data milik user lain dengan mengubah parameter ID.

## Endpoint Vulnerable
- **URL:** `/public/order.php?id={ORDER_ID}`
- **Method:** GET
- **Parameter:** `id`
- **Masalah:** Query tidak memfilter berdasarkan `user_id` yang sedang login

## Cara Eksploitasi

1. Login sebagai `budi:budi123`
2. Lihat pesanan sendiri di `/public/orders.php`
3. Perhatikan URL order detail: `/public/order.php?id=1`
4. Ganti parameter `id` menjadi order milik user lain: `/public/order.php?id=3`
5. Budi bisa melihat detail order milik Sari

## Lokasi di Kode
File: `app/public/order.php`
```php
// [VULN: IDOR] - query does NOT filter by user_id intentional
$stmt = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
```

## Cara Fix
```php
// Tambahkan filter user_id:
$stmt = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
```

## SIEM Detection
Rule `IDOR` mendeteksi ketika user mengakses order yang bukan miliknya (membandingkan `user_id` session dengan `user_id` order).
