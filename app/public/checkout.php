<?php
// [VULN: CLICKJACKING] - No X-Frame-Options header intentional
// [VULN: BUSINESS-LOGIC] - Voucher reusable, no stock check intentional
$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Get cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.stock, p.category FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    header('Location: /public/cart.php');
    exit;
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = 0;
$voucher_msg = '';
$voucher_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucher_code = $_POST['voucher_code'] ?? '';
    
    if (isset($_POST['apply_voucher']) && $voucher_code) {
        // [VULN: BUSINESS-LOGIC] - No check if voucher already used intentional
        $v = $conn->prepare("SELECT * FROM vouchers WHERE code = ? AND active = 1");
        $v->bind_param("s", $voucher_code);
        $v->execute();
        $voucher = $v->get_result()->fetch_assoc();
        
        if ($voucher) {
            $discount = $subtotal * ($voucher['discount_percent'] / 100);
            $voucher_msg = "Voucher {$voucher_code} berhasil! Diskon {$voucher['discount_percent']}%";
            $_SESSION['checkout_voucher'] = $voucher_code;
            $_SESSION['checkout_discount'] = $discount;
        } else {
            $voucher_msg = "Voucher tidak valid atau sudah expired!";
        }
    }
    
    if (isset($_POST['place_order'])) {
        $discount = $_SESSION['checkout_discount'] ?? 0;
        $voucher_code = $_SESSION['checkout_voucher'] ?? null;
        $final_total = $subtotal - $discount;
        
        // [VULN: BUSINESS-LOGIC] - No server-side stock check intentional
        $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total, voucher_code, status) VALUES (?, ?, ?, 'pending')");
        $order_stmt->bind_param("ids", $_SESSION['user_id'], $final_total, $voucher_code);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        
        foreach ($cart_items as $item) {
            $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $oi->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $oi->execute();
        }
        
        // Track voucher usage
        if ($voucher_code) {
            $vu = $conn->prepare("INSERT INTO voucher_usage (user_id, voucher_code, order_id) VALUES (?, ?, ?)");
            $vu->bind_param("isi", $_SESSION['user_id'], $voucher_code, $order_id);
            $vu->execute();
        }
        
        // Clear cart
        $conn->prepare("DELETE FROM cart WHERE user_id = ?")->bind_param("i", $_SESSION['user_id']);
        $conn->query("DELETE FROM cart WHERE user_id = " . $_SESSION['user_id']);
        
        unset($_SESSION['checkout_voucher'], $_SESSION['checkout_discount']);
        
        header("Location: /public/order.php?id=$order_id&success=1");
        exit;
    }
}

$discount = $_SESSION['checkout_discount'] ?? 0;
$final_total = $subtotal - $discount;

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight: 800;"><i class="bi bi-credit-card" style="color: var(--nh-accent);"></i> Checkout</h2>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-nhsec p-4 mb-4">
            <h5 class="mb-3 fw-bold"><i class="bi bi-bag-check"></i> Item Pesanan</h5>
            <?php foreach ($cart_items as $item): ?>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid var(--nh-border);">
                <div>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                    <div class="text-muted small"><?= formatRupiah($item['price']) ?> × <?= $item['quantity'] ?></div>
                </div>
                <span class="fw-semibold"><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Voucher -->
        <div class="card card-nhsec p-4">
            <h5 class="mb-3 fw-bold"><i class="bi bi-tag"></i> Voucher Diskon</h5>
            <?php if ($voucher_msg): ?>
            <div class="alert <?= $discount > 0 ? 'alert-success' : 'alert-danger' ?> small" style="border-radius: 10px;">
                <?= $voucher_msg ?>
            </div>
            <?php endif; ?>
            <form method="POST" class="d-flex gap-2">
                <input type="text" name="voucher_code" class="form-control" placeholder="Masukkan kode voucher" value="<?= htmlspecialchars($voucher_code) ?>" id="voucher-input">
                <button type="submit" name="apply_voucher" value="1" class="btn btn-nhsec-outline" id="apply-voucher">Terapkan</button>
            </form>
            <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle"></i> Coba: DISKON10</p>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="card card-nhsec p-4" style="position: sticky; top: 100px;">
            <h5 class="mb-3 fw-bold"><i class="bi bi-receipt"></i> Ringkasan Pembayaran</h5>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span><?= formatRupiah($subtotal) ?></span></div>
            <?php if ($discount > 0): ?>
            <div class="d-flex justify-content-between mb-2"><span class="text-success">Diskon</span><span class="text-success">-<?= formatRupiah($discount) ?></span></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Ongkir</span><span class="text-success">Gratis</span></div>
            <hr style="border-color: var(--nh-border);">
            <div class="d-flex justify-content-between mb-4"><strong style="font-size: 1.1rem;">Total</strong><span class="product-price" style="font-size: 1.4rem;"><?= formatRupiah($final_total) ?></span></div>
            
            <form method="POST">
                <input type="hidden" name="voucher_code" value="<?= htmlspecialchars($_SESSION['checkout_voucher'] ?? '') ?>">
                <button type="submit" name="place_order" value="1" class="btn btn-accent w-100 btn-lg" id="place-order">
                    <i class="bi bi-bag-check"></i> Buat Pesanan
                </button>
            </form>
            <p class="text-muted small text-center mt-2 mb-0">
                <i class="bi bi-shield-check"></i> Pembayaran simulasi (no real payment)
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
