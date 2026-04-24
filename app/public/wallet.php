<?php
$pageTitle = 'E-Wallet';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$balance = getBalance($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT * FROM wallets WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight: 800;"><i class="bi bi-wallet2" style="color: var(--nh-green);"></i> E-Wallet</h2>

<!-- Balance Card -->
<div class="card card-nhsec p-4 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;">SALDO ANDA</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:2.2rem;font-weight:700;color:<?= $balance >= 0 ? 'var(--nh-green)' : 'var(--nh-danger)' ?>;">
                <?= formatRupiah($balance) ?>
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="/public/index.php" class="btn btn-nhsec"><i class="bi bi-bag"></i> Belanja</a>
            <a href="/public/sell.php" class="btn btn-nhsec-outline"><i class="bi bi-plus-lg"></i> Jual Produk</a>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="card card-nhsec p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-clock-history"></i> Riwayat Transaksi</h5>
    <?php if (empty($transactions)): ?>
    <p class="text-muted text-center py-3">Belum ada transaksi.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>Waktu</th><th>Keterangan</th><th>Jumlah</th><th>Saldo</th></tr></thead>
            <tbody>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($t['description']) ?></td>
                <td>
                    <span style="font-family:'JetBrains Mono',monospace;font-weight:600;color:<?= $t['type']==='credit' ? 'var(--nh-green)' : 'var(--nh-danger)' ?>;">
                        <?= $t['type']==='credit' ? '+' : '-' ?><?= formatRupiah($t['amount']) ?>
                    </span>
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:var(--nh-text-secondary);">
                    <?= formatRupiah($t['balance_after']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
