# Business Logic Vulnerabilities

## Deskripsi
Business logic vulnerabilities terjadi ketika logika bisnis aplikasi tidak diimplementasi dengan benar di sisi server. Di nhsec ada 2 business logic flaws:

1. **Voucher unlimited reuse** — Voucher bisa dipakai berkali-kali oleh user yang sama
2. **Negative quantity** — User bisa memasukkan quantity negatif untuk mendapat "uang kembali"

---

## Vuln 1: Voucher Unlimited Reuse

### Endpoint
- **URL:** `/public/checkout.php`
- **Method:** POST  
- **Parameter:** `voucher_code`

### Cara Eksploitasi
1. Login sebagai `budi:budi123`
2. Tambahkan produk ke cart
3. Di halaman checkout, masukkan voucher `DISKON10`
4. Checkout dan buat pesanan
5. Ulangi langkah 2-4 — voucher `DISKON10` tetap bisa dipakai!

### Lokasi di Kode
```php
// [VULN: BUSINESS-LOGIC] - No check if voucher already used intentional
$v = $conn->prepare("SELECT * FROM vouchers WHERE code = ? AND active = 1");
// Tidak ada pengecekan ke tabel voucher_usage
```

### Cara Fix
```php
// Cek apakah voucher sudah pernah digunakan oleh user ini
$check = $conn->prepare("SELECT COUNT(*) as cnt FROM voucher_usage WHERE user_id = ? AND voucher_code = ?");
$check->bind_param("is", $_SESSION['user_id'], $voucher_code);
$check->execute();
$used = $check->get_result()->fetch_assoc()['cnt'];
if ($used > 0) {
    $voucher_msg = "Voucher sudah pernah digunakan!";
}
```

---

## Vuln 2: Negative Quantity

### Endpoint
- **URL:** `/public/product.php?id={ID}` dan `/public/cart.php`
- **Method:** POST
- **Parameter:** `quantity`

### Cara Eksploitasi
1. Di halaman produk, ubah quantity menjadi `-5`
2. Klik "Tambah ke Keranjang"
3. Total harga menjadi negatif
4. Checkout dengan total negatif (seolah-olah mendapat uang)

### Lokasi di Kode
```php
// [VULN: BUSINESS-LOGIC] - no negative quantity check intentional
$qty = (int)($_POST['quantity'] ?? 1);
// Tidak ada validasi $qty > 0
```

### Cara Fix
```php
$qty = max(1, (int)($_POST['quantity'] ?? 1));
// Atau validasi eksplisit:
if ($qty < 1) { die('Quantity harus minimal 1'); }
```

---

## SIEM Detection
Rule `BUSINESS_LOGIC` mendeteksi penggunaan voucher >1x oleh user yang sama.
