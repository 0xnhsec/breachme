<?php
$pageTitle = 'Kelola Users';
require_once __DIR__ . '/../includes/functions.php';
// [VULN: BAC] - uses requireAdmin() intentional
requireAdmin();

// Handle block/unblock
if (isset($_GET['block']) && (int)$_GET['block'] !== 1) {
    $uid = (int)$_GET['block'];
    $conn->query("UPDATE users SET is_blocked = 1 WHERE id = $uid");
    createNotification($uid, 'warning', 'Akun kamu telah diblokir oleh admin.');
    header('Location: /admin/users.php');
    exit;
}
if (isset($_GET['unblock'])) {
    $uid = (int)$_GET['unblock'];
    $conn->query("UPDATE users SET is_blocked = 0 WHERE id = $uid");
    header('Location: /admin/users.php');
    exit;
}

if (isset($_GET['delete']) && (int)$_GET['delete'] !== 1) {
    $conn->query("DELETE FROM users WHERE id = " . (int)$_GET['delete']);
    header('Location: /admin/users.php');
    exit;
}

$users = $conn->query("SELECT id, username, email, role, balance, ip_address, is_blocked, created_at FROM users ORDER BY id")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../includes/admin_header.php';
?>

<h4 class="mb-4 fw-bold"><i class="bi bi-people" style="color:var(--adm-primary);"></i> Kelola Users</h4>

<div class="card card-nhsec p-4">
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Balance</th><th>IP</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['role']==='admin'?'bg-danger':'bg-primary' ?>"><?= $u['role'] ?></span></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:<?= $u['balance'] >= 0 ? 'var(--adm-green)' : 'var(--adm-danger)' ?>;"><?= formatRupiah($u['balance']) ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:#666;"><?= $u['ip_address'] ?: '-' ?></td>
                <td>
                    <?php if ($u['is_blocked']): ?>
                    <span class="badge bg-danger">BLOCKED</span>
                    <?php else: ?>
                    <span class="badge bg-success">Active</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['id'] !== 1): ?>
                    <?php if ($u['is_blocked']): ?>
                    <a href="/admin/users.php?unblock=<?= $u['id'] ?>" class="btn btn-sm btn-nhsec-outline" title="Unblock"><i class="bi bi-unlock"></i></a>
                    <?php else: ?>
                    <a href="/admin/users.php?block=<?= $u['id'] ?>" class="btn btn-sm btn-nhsec-outline" title="Block" onclick="return confirm('Blokir user ini?')"><i class="bi bi-lock"></i></a>
                    <?php endif; ?>
                    <a href="/admin/users.php?delete=<?= $u['id'] ?>" class="btn btn-sm text-danger" onclick="return confirm('Hapus user ini?')"><i class="bi bi-trash3"></i></a>
                    <?php else: ?>
                    <span class="text-muted small">protected</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
