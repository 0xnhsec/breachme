<?php
// [VULN: IDOR] - No ownership check on invoice, any logged-in user can view any invoice intentional
$pageTitle = 'Invoice';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$inv_id = (int)($_GET['id'] ?? 0);

// [VULN: IDOR] - query does NOT filter by buyer_id or seller_id intentional
$stmt = $conn->prepare("SELECT i.*, 
    b.username as buyer_name, b.email as buyer_email,
    s.username as seller_name, s.email as seller_email,
    p.name as product_name, p.category as product_category
    FROM invoices i 
    JOIN users b ON i.buyer_id = b.id 
    JOIN users s ON i.seller_id = s.id 
    JOIN products p ON i.product_id = p.id 
    WHERE i.id = ?");
$stmt->bind_param("i", $inv_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    header('Location: /public/invoices.php');
    exit;
}

// [VULN: LFI] — template parameter diinclude tanpa sanitasi path intentional
// Eksploit: /invoice.php?id=1&template=../../../etc/passwd
// Chain: LFI + Unrestricted Upload = RCE (include uploaded shell)
$template = $_GET['template'] ?? '';
if ($template !== '') {
    // Tidak ada path traversal check — bisa include file sembarang
    $template_path = __DIR__ . '/../../templates/invoice_' . $template . '.php';
    if (file_exists($template_path)) {
        include $template_path;
        exit;
    }
    // Fallback: include path mentah (lebih vuln)
    // [VULN: LFI] include($template); // uncomment untuk LFI penuh
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    @media print {
        .navbar-nhsec, .footer-nhsec, .no-print { display: none !important; }
        body { background: #fff !important; color: #000 !important; }
        .card-nhsec { background: #fff !important; border: 1px solid #ddd !important; }
        .print-invoice { box-shadow: none !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 style="font-weight: 800;"><i class="bi bi-file-text" style="color: var(--nh-primary);"></i> Invoice</h2>
    <button onclick="window.print()" class="btn btn-nhsec-outline"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="card card-nhsec p-4 print-invoice">
    <!-- Invoice Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--nh-primary);">
                <i class="bi bi-shop"></i> breachme
            </h4>
            <div style="font-size:0.8rem;color:var(--nh-text-secondary);">Marketplace Platform</div>
        </div>
        <div class="text-end">
            <div style="font-family:'JetBrains Mono',monospace;font-size:1rem;font-weight:700;color:var(--nh-text);">
                <?= htmlspecialchars($invoice['invoice_number']) ?>
            </div>
            <div style="font-size:0.8rem;color:var(--nh-text-secondary);">
                <?= date('d M Y, H:i', strtotime($invoice['created_at'])) ?>
            </div>
            <span class="badge <?= $invoice['status']==='paid'?'bg-success':'bg-warning' ?> mt-1"><?= strtoupper($invoice['status']) ?></span>
        </div>
    </div>

    <hr>

    <!-- Buyer & Seller -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;">PEMBELI</div>
            <div style="font-weight:600;"><?= htmlspecialchars($invoice['buyer_name']) ?></div>
            <div style="font-size:0.8rem;color:var(--nh-text-secondary);"><?= htmlspecialchars($invoice['buyer_email']) ?></div>
        </div>
        <div class="col-md-6">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;">PENJUAL</div>
            <div style="font-weight:600;"><?= htmlspecialchars($invoice['seller_name']) ?></div>
            <div style="font-size:0.8rem;color:var(--nh-text-secondary);"><?= htmlspecialchars($invoice['seller_email']) ?></div>
        </div>
    </div>

    <!-- Product Detail -->
    <div class="table-responsive mb-4">
        <table class="table table-nhsec">
            <thead><tr><th>Produk</th><th>Kategori</th><th>Qty</th><th>Harga</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            <tr>
                <td><strong><?= htmlspecialchars($invoice['product_name']) ?></strong></td>
                <td><span class="product-category <?= $invoice['product_category']==='laptop'?'cat-laptop':'cat-sticker' ?>"><?= $invoice['product_category'] ?></span></td>
                <td><?= $invoice['quantity'] ?></td>
                <td class="product-price"><?= formatRupiah($invoice['amount'] / $invoice['quantity']) ?></td>
                <td class="text-end product-price"><?= formatRupiah($invoice['amount']) ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="row justify-content-end">
        <div class="col-md-5">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <span><?= formatRupiah($invoice['amount']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Pajak Platform (2%)</span>
                <span><?= formatRupiah($invoice['tax_amount']) ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <strong style="font-size:1.1rem;">Total</strong>
                <span class="product-price" style="font-size:1.3rem;"><?= formatRupiah($invoice['total']) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
