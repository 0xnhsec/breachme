<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/functions.php';

// [VULN: BAC] - uses requireAdmin() but bypassable via session manipulation intentional
requireAdmin();

// Stats
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$total_invoices = $conn->query("SELECT COUNT(*) as c FROM invoices")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total),0) as c FROM orders WHERE status != 'cancelled'")->fetch_assoc()['c'];
$total_tax = $conn->query("SELECT COALESCE(SUM(tax_amount),0) as c FROM invoices WHERE status = 'paid'")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'")->fetch_assoc()['c'];
$blocked_count = $conn->query("SELECT COUNT(*) as c FROM blocked_ips WHERE unblocked_at IS NULL")->fetch_assoc()['c'];
$recent_orders = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-nhsec p-4 text-center">
            <i class="bi bi-people" style="font-size: 2rem; color: var(--adm-primary);"></i>
            <h3 class="mt-2 mb-0 fw-bold"><?= $total_users ?></h3>
            <span class="text-muted small">Total Users</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-nhsec p-4 text-center">
            <i class="bi bi-box-seam" style="font-size: 2rem; color: var(--adm-warning);"></i>
            <h3 class="mt-2 mb-0 fw-bold"><?= $total_products ?></h3>
            <span class="text-muted small">Total Produk</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-nhsec p-4 text-center">
            <i class="bi bi-file-text" style="font-size: 2rem; color: var(--adm-green);"></i>
            <h3 class="mt-2 mb-0 fw-bold"><?= $total_invoices ?></h3>
            <span class="text-muted small">Total Invoice</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-nhsec p-4 text-center">
            <i class="bi bi-cash-coin" style="font-size: 2rem; color: #10b981;"></i>
            <h3 class="mt-2 mb-0 fw-bold" style="font-size: 1.2rem;"><?= formatRupiah($total_tax) ?></h3>
            <span class="text-muted small">Pajak Terkumpul (2%)</span>
        </div>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-nhsec p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:8px;background:var(--adm-warning-dim);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-clock-history" style="font-size:1.3rem;color:var(--adm-warning);"></i>
                </div>
                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--adm-warning);"><?= $pending_orders ?></div>
                    <div style="font-size:0.75rem;color:#444;">Pesanan Pending</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-nhsec p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:8px;background:var(--adm-danger-dim);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-shield-x" style="font-size:1.3rem;color:var(--adm-danger);"></i>
                </div>
                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--adm-danger);"><?= $blocked_count ?></div>
                    <div style="font-size:0.75rem;color:#444;">IP Diblokir</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-nhsec p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:8px;background:var(--adm-primary-dim);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-receipt" style="font-size:1.3rem;color:var(--adm-primary);"></i>
                </div>
                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--adm-primary);"><?= $total_orders ?></div>
                    <div style="font-size:0.75rem;color:#444;">Total Pesanan</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SIEM Link Card -->
<div class="card card-nhsec p-4 mb-4" style="border-color: rgba(0,212,255,0.15);">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:8px;background:var(--adm-primary-dim);display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-shield-fill-check" style="font-size:1.3rem;color:var(--adm-primary);"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold">SIEM Dashboard</h6>
                <span class="text-muted small">Monitor traffic, alerts, dan attack timeline</span>
            </div>
        </div>
        <a href="/siem/dashboard.php" class="btn btn-nhsec"><i class="bi bi-arrow-right"></i> Open SIEM</a>
    </div>
</div>

<!-- Recent Orders -->
<div class="card card-nhsec p-4">
    <h5 class="mb-3 fw-bold"><i class="bi bi-clock-history"></i> Pesanan Terbaru</h5>
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>#</th><th>User</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
            <tbody>
            <?php foreach ($recent_orders as $o): ?>
            <tr>
                <td><?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['username']) ?></td>
                <td class="product-price"><?= formatRupiah($o['total']) ?></td>
                <td><?= getStatusBadge($o['status']) ?></td>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
