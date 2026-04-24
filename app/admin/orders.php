<?php
$pageTitle = 'Admin - Pesanan';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $oid);
    $stmt->execute();
    header('Location: /admin/orders.php');
    exit;
}

$orders = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight:800;"><i class="bi bi-receipt" style="color:var(--nh-warning);"></i> Kelola Pesanan</h2>

<div class="card card-nhsec p-4">
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>#</th><th>User</th><th>Total</th><th>Voucher</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['username']) ?></td>
                <td class="product-price"><?= formatRupiah($o['total']) ?></td>
                <td><?= $o['voucher_code'] ? '<span class="badge bg-success">'.$o['voucher_code'].'</span>' : '-' ?></td>
                <td><?= getStatusBadge($o['status']) ?></td>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                <td>
                    <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="status" class="form-select form-select-sm" style="width:130px;">
                            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" value="1" class="btn btn-sm btn-nhsec-outline"><i class="bi bi-check"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
