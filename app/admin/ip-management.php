<?php
// [VULN: BAC] - This page intentionally does NOT call requireAdmin()
// Direct URL access works for any logged-in user — bypassable middleware
$pageTitle = 'IP Management';
require_once __DIR__ . '/../includes/functions.php';

// [VULN: BAC] - Only checks login, not admin role intentional
requireLoginOnly();

// Handle block IP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_ip'])) {
    $ip = $_POST['ip'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $stmt = $conn->prepare("INSERT INTO blocked_ips (ip, reason, blocked_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $ip, $reason, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: /admin/ip-management.php?msg=blocked');
    exit;
}

// Handle unblock
if (isset($_GET['unblock'])) {
    $bid = (int)$_GET['unblock'];
    $conn->query("UPDATE blocked_ips SET unblocked_at = NOW() WHERE id = $bid");
    header('Location: /admin/ip-management.php?msg=unblocked');
    exit;
}

// Active IPs from request logs
$active_ips = $conn->query("SELECT ip, COUNT(*) as request_count, 
    MAX(timestamp) as last_seen,
    GROUP_CONCAT(DISTINCT user_id) as user_ids
    FROM request_logs 
    WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY ip 
    ORDER BY request_count DESC 
    LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Blocked IPs
$blocked_ips = $conn->query("SELECT bi.*, u.username as blocked_by_name 
    FROM blocked_ips bi 
    JOIN users u ON bi.blocked_by = u.id 
    ORDER BY bi.blocked_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight:800;"><i class="bi bi-shield-exclamation" style="color:var(--nh-danger);"></i> IP Management</h2>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success" style="border-radius:6px;"><i class="bi bi-check-circle"></i> Operasi berhasil!</div>
<?php endif; ?>

<!-- Block IP Form -->
<div class="card card-nhsec p-4 mb-4">
    <h5 class="fw-bold mb-3">Blokir IP</h5>
    <form method="POST" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">IP ADDRESS</label>
            <input type="text" name="ip" class="form-control" placeholder="192.168.1.x" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">ALASAN</label>
            <input type="text" name="reason" class="form-control" placeholder="Alasan pemblokiran...">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="block_ip" value="1" class="btn btn-nhsec w-100"><i class="bi bi-shield-x"></i> Blokir</button>
        </div>
    </form>
</div>

<!-- Active IPs -->
<div class="card card-nhsec p-4 mb-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-activity"></i> IP Aktif (24 Jam Terakhir)</h5>
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>IP Address</th><th>Requests</th><th>Last Seen</th><th>User IDs</th></tr></thead>
            <tbody>
            <?php foreach ($active_ips as $ip): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;"><?= htmlspecialchars($ip['ip']) ?></td>
                <td><span class="badge bg-info"><?= $ip['request_count'] ?></span></td>
                <td class="text-muted small"><?= $ip['last_seen'] ? date('H:i:s', strtotime($ip['last_seen'])) : '-' ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:#666;"><?= $ip['user_ids'] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Blocked IPs -->
<div class="card card-nhsec p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-shield-x"></i> IP Diblokir</h5>
    <?php if (empty($blocked_ips)): ?>
    <p class="text-muted text-center py-3">Tidak ada IP yang diblokir.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>IP</th><th>Alasan</th><th>Diblokir Oleh</th><th>Tanggal Blokir</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($blocked_ips as $b): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem;"><?= htmlspecialchars($b['ip']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($b['reason'] ?: '-') ?></td>
                <td><?= htmlspecialchars($b['blocked_by_name']) ?></td>
                <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($b['blocked_at'])) ?></td>
                <td>
                    <?php if ($b['unblocked_at']): ?>
                    <span class="badge bg-success">Unblocked</span>
                    <?php else: ?>
                    <span class="badge bg-danger">Blocked</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$b['unblocked_at']): ?>
                    <a href="/admin/ip-management.php?unblock=<?= $b['id'] ?>" class="btn btn-sm btn-nhsec-outline"><i class="bi bi-unlock"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
