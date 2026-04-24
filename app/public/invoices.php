<?php
$pageTitle = 'Invoice Saya';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT i.*, 
    b.username as buyer_name, s.username as seller_name, 
    p.name as product_name
    FROM invoices i 
    JOIN users b ON i.buyer_id = b.id 
    JOIN users s ON i.seller_id = s.id 
    JOIN products p ON i.product_id = p.id 
    WHERE i.buyer_id = ? OR i.seller_id = ?
    ORDER BY i.created_at DESC");
$stmt->bind_param("ii", $uid, $uid);
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight: 800;"><i class="bi bi-file-text" style="color: var(--nh-primary);"></i> Invoice Saya</h2>

<?php if (empty($invoices)): ?>
<div class="text-center py-5">
    <i class="bi bi-file-earmark-x" style="font-size: 5rem; color: var(--nh-text-muted);"></i>
    <h4 class="mt-3 text-muted">Belum ada invoice</h4>
    <a href="/public/index.php" class="btn btn-nhsec mt-2"><i class="bi bi-bag-heart"></i> Mulai Belanja</a>
</div>
<?php else: ?>
<div class="card card-nhsec p-4">
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>No. Invoice</th><th>Produk</th><th>Peran</th><th>Total</th><th>Status</th><th>Tanggal</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;"><?= $inv['invoice_number'] ?></td>
                <td><?= htmlspecialchars($inv['product_name']) ?></td>
                <td>
                    <?php if ($inv['buyer_id'] == $uid): ?>
                    <span class="badge bg-info">Pembeli</span>
                    <?php else: ?>
                    <span class="badge bg-success">Penjual</span>
                    <?php endif; ?>
                </td>
                <td class="product-price"><?= formatRupiah($inv['total']) ?></td>
                <td><span class="badge <?= $inv['status']==='paid'?'bg-success':'bg-warning' ?>"><?= $inv['status'] ?></span></td>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($inv['created_at'])) ?></td>
                <td><a href="/public/invoice.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-nhsec-outline"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
