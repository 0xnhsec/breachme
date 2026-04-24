<?php
$pageTitle = 'Admin - Invoice';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$invoices = $conn->query("SELECT i.*, 
    b.username as buyer_name, s.username as seller_name, 
    p.name as product_name
    FROM invoices i 
    JOIN users b ON i.buyer_id = b.id 
    JOIN users s ON i.seller_id = s.id 
    JOIN products p ON i.product_id = p.id 
    ORDER BY i.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$total_tax = $conn->query("SELECT COALESCE(SUM(tax_amount),0) as c FROM invoices WHERE status = 'paid'")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount),0) as c FROM invoices WHERE status = 'paid'")->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight:800;"><i class="bi bi-file-text" style="color:var(--nh-green);"></i> Semua Invoice</h2>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-nhsec p-3 text-center">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">Total Transaksi</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:1.3rem;font-weight:700;color:var(--nh-primary);"><?= formatRupiah($total_revenue) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-nhsec p-3 text-center">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">Pajak Terkumpul (2%)</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:1.3rem;font-weight:700;color:var(--nh-green);"><?= formatRupiah($total_tax) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-nhsec p-3 text-center">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">Total Invoice</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:1.3rem;font-weight:700;color:var(--nh-warning);"><?= count($invoices) ?></div>
        </div>
    </div>
</div>

<div class="card card-nhsec p-4">
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>Invoice</th><th>Produk</th><th>Pembeli</th><th>Penjual</th><th>Amount</th><th>Tax</th><th>Total</th><th>Status</th><th>Tanggal</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.72rem;"><?= $inv['invoice_number'] ?></td>
                <td><?= htmlspecialchars($inv['product_name']) ?></td>
                <td class="text-muted"><?= $inv['buyer_name'] ?></td>
                <td class="text-muted"><?= $inv['seller_name'] ?></td>
                <td class="product-price"><?= formatRupiah($inv['amount']) ?></td>
                <td style="color:var(--nh-warning);font-family:'JetBrains Mono',monospace;font-size:0.78rem;"><?= formatRupiah($inv['tax_amount']) ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
