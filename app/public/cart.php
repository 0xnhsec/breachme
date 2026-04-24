<?php
// [VULN: BUSINESS-LOGIC] - No server-side validation on quantity (negative allowed) intentional
$pageTitle = 'Keranjang';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: /public/cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: /public/cart.php');
    exit;
}

$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.category, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight: 800;"><i class="bi bi-cart3" style="color: var(--nh-primary);"></i> Keranjang</h2>

<?php if (empty($cart_items)): ?>
<div class="text-center py-5">
    <i class="bi bi-cart-x" style="font-size: 5rem; color: var(--nh-text-muted);"></i>
    <h4 class="mt-3 text-muted">Keranjang kosong</h4>
    <a href="/public/index.php" class="btn btn-nhsec mt-2"><i class="bi bi-bag-heart"></i> Belanja</a>
</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <?php foreach ($cart_items as $item): ?>
        <div class="card card-nhsec p-3 mb-3">
            <div class="row align-items-center">
                <div class="col-2">
                    <div style="width:70px;height:70px;background:var(--nh-surface-2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-<?= $item['category']==='laptop'?'laptop':'stickies' ?>" style="font-size:1.8rem;color:var(--nh-text-muted);"></i>
                    </div>
                </div>
                <div class="col-3">
                    <h6 class="mb-0 fw-bold"><a href="/public/product.php?id=<?= $item['product_id'] ?>" class="text-decoration-none" style="color:var(--nh-text);"><?= htmlspecialchars($item['name']) ?></a></h6>
                </div>
                <div class="col-3">
                    <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" class="form-control form-control-sm" style="width:70px;">
                        <button type="submit" name="update_cart" value="1" class="btn btn-sm btn-nhsec-outline"><i class="bi bi-arrow-repeat"></i></button>
                    </form>
                </div>
                <div class="col-3 text-end">
                    <span class="product-price"><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
                </div>
                <div class="col-1 text-end">
                    <a href="/public/cart.php?remove=<?= $item['id'] ?>" class="text-danger"><i class="bi bi-trash3"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="col-lg-4">
        <div class="card card-nhsec p-4" style="position:sticky;top:100px;">
            <h5 class="mb-3 fw-bold"><i class="bi bi-receipt"></i> Ringkasan</h5>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span><?= formatRupiah($total) ?></span></div>
            <div class="d-flex justify-content-between mb-3"><span class="text-muted">Ongkir</span><span class="text-success">Gratis</span></div>
            <hr style="border-color:var(--nh-border);">
            <div class="d-flex justify-content-between mb-4"><strong>Total</strong><span class="product-price" style="font-size:1.3rem;"><?= formatRupiah($total) ?></span></div>
            <a href="/public/checkout.php" class="btn btn-accent w-100 btn-lg"><i class="bi bi-credit-card"></i> Checkout</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
