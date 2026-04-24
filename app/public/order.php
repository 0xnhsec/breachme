<?php
// [VULN: IDOR] - No ownership validation, any user can view any order intentional
$pageTitle = 'Detail Pesanan';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$order_id = (int)($_GET['id'] ?? 0);

// [VULN: IDOR] - query does NOT filter by user_id intentional
$stmt = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: /public/orders.php');
    exit;
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, p.name, p.category FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success" style="border-radius: 6px;">
    <i class="bi bi-check-circle-fill"></i> <strong>Pesanan berhasil dibuat!</strong> Terima kasih sudah berbelanja di nhsec.
</div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/public/orders.php" style="color: var(--nh-primary); text-decoration: none;">Pesanan</a></li>
        <li class="breadcrumb-item active text-muted">Order #<?= $order_id ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-nhsec p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fw-bold">Order #<?= $order_id ?></h4>
                <?= getStatusBadge($order['status']) ?>
            </div>
            <div class="text-muted small mb-3">
                <i class="bi bi-calendar"></i> <?= date('d M Y, H:i:s', strtotime($order['created_at'])) ?>
                &nbsp;|&nbsp; <i class="bi bi-person"></i> <?= htmlspecialchars($order['username']) ?>
            </div>

            <h6 class="fw-bold mt-4 mb-3">Item Pesanan</h6>
            <?php foreach ($items as $item): ?>
            <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom: 1px solid var(--nh-border);">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:var(--nh-surface-2);border-radius:6px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-<?= $item['category']==='laptop'?'laptop':'stickies' ?>" style="color:var(--nh-text-muted);"></i>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                        <div class="text-muted small"><?= formatRupiah($item['price']) ?> × <?= $item['quantity'] ?></div>
                    </div>
                </div>
                <span class="fw-semibold"><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-nhsec p-4">
            <h5 class="mb-3 fw-bold"><i class="bi bi-receipt"></i> Pembayaran</h5>
            <?php if ($order['voucher_code']): ?>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Voucher</span>
                <span class="badge bg-success"><i class="bi bi-tag"></i> <?= htmlspecialchars($order['voucher_code']) ?></span>
            </div>
            <?php endif; ?>
            <hr style="border-color: var(--nh-border);">
            <div class="d-flex justify-content-between">
                <strong>Total</strong>
                <span class="product-price" style="font-size: 1.3rem;"><?= formatRupiah($order['total']) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
