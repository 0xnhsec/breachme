<?php
$pageTitle = 'Admin - Produk';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header('Location: /admin/products.php?msg=deleted');
    exit;
}

// Handle status change
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE products SET status = 'active' WHERE id = $id");
    header('Location: /admin/products.php?msg=approved');
    exit;
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $p = $conn->query("SELECT seller_id, name FROM products WHERE id = $id")->fetch_assoc();
    $conn->query("UPDATE products SET status = 'rejected' WHERE id = $id");
    if ($p) {
        createNotification($p['seller_id'], 'warning', 'Admin memblokir produkmu "' . $p['name'] . '"');
    }
    header('Location: /admin/products.php?msg=rejected');
    exit;
}

$products = $conn->query("SELECT p.*, u.username as seller_name FROM products p JOIN users u ON p.seller_id = u.id ORDER BY p.id DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight:800;"><i class="bi bi-box-seam" style="color:var(--nh-primary);"></i> Kelola Produk</h2>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success" style="border-radius:6px;"><i class="bi bi-check-circle"></i> Operasi berhasil!</div>
<?php endif; ?>

<div class="card card-nhsec p-4">
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>ID</th><th>Nama</th><th>Seller</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($p['seller_name']) ?></td>
                <td><span class="product-category <?= $p['category']==='laptop'?'cat-laptop':'cat-sticker' ?>"><?= $p['category'] ?></span></td>
                <td class="product-price"><?= formatRupiah($p['price']) ?></td>
                <td><?= $p['stock'] ?></td>
                <td><span class="badge <?= $p['status']==='active'?'bg-success':($p['status']==='pending'?'bg-warning':'bg-danger') ?>"><?= $p['status'] ?></span></td>
                <td>
                    <?php if ($p['status'] !== 'active'): ?>
                    <a href="/admin/products.php?approve=<?= $p['id'] ?>" class="btn btn-sm btn-nhsec-outline" title="Approve"><i class="bi bi-check-lg"></i></a>
                    <?php endif; ?>
                    <?php if ($p['status'] !== 'rejected'): ?>
                    <a href="/admin/products.php?reject=<?= $p['id'] ?>" class="btn btn-sm btn-nhsec-outline" title="Reject"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                    <a href="/admin/products.php?delete=<?= $p['id'] ?>" class="btn btn-sm text-danger" onclick="return confirm('Hapus produk ini?')"><i class="bi bi-trash3"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
