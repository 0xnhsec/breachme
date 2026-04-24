<?php
$pageTitle = 'Pesanan Saya';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stmt = $conn->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight: 800;"><i class="bi bi-receipt" style="color: var(--nh-primary);"></i> Pesanan Saya</h2>

<?php if (empty($orders)): ?>
<div class="text-center py-5">
    <i class="bi bi-inbox" style="font-size: 5rem; color: var(--nh-text-muted);"></i>
    <h4 class="mt-3 text-muted">Belum ada pesanan</h4>
    <a href="/public/index.php" class="btn btn-nhsec mt-2"><i class="bi bi-bag-heart"></i> Mulai Belanja</a>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($orders as $order): ?>
    <div class="col-12">
        <a href="/public/order.php?id=<?= $order['id'] ?>" class="text-decoration-none">
            <div class="card card-nhsec p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold" style="color: var(--nh-text);">Order #<?= $order['id'] ?></h6>
                        <span class="text-muted small"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?> — <?= $order['item_count'] ?> item</span>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><?= getStatusBadge($order['status']) ?></div>
                        <span class="product-price"><?= formatRupiah($order['total']) ?></span>
                        <?php if ($order['voucher_code']): ?>
                        <div><span class="badge bg-success small"><i class="bi bi-tag"></i> <?= htmlspecialchars($order['voucher_code']) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
