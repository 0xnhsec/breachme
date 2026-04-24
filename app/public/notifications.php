<?php
$pageTitle = 'Notifikasi';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = " . $_SESSION['user_id']);
    header('Location: /public/notifications.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 style="font-weight: 800;"><i class="bi bi-bell" style="color: var(--nh-warning);"></i> Notifikasi</h2>
    <?php if (!empty($notifications)): ?>
    <a href="/public/notifications.php?mark_read=1" class="btn btn-nhsec-outline btn-sm"><i class="bi bi-check-all"></i> Tandai Semua Dibaca</a>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
<div class="text-center py-5">
    <i class="bi bi-bell-slash" style="font-size: 5rem; color: var(--nh-text-muted);"></i>
    <h4 class="mt-3 text-muted">Tidak ada notifikasi</h4>
</div>
<?php else: ?>
<div class="d-flex flex-column gap-2">
    <?php foreach ($notifications as $n): ?>
    <<?= $n['link'] ? 'a href="' . htmlspecialchars($n['link']) . '"' : 'div' ?> class="card card-nhsec p-3 text-decoration-none" style="<?= !$n['is_read'] ? 'border-left: 3px solid var(--nh-primary);' : '' ?>">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-3">
                <div style="width:36px;height:36px;border-radius:6px;display:flex;align-items:center;justify-content:center;
                    background:<?= $n['type']==='sale' ? 'var(--nh-green-dim)' : ($n['type']==='purchase' ? 'var(--nh-primary-dim)' : 'var(--nh-warning-dim)') ?>;
                    color:<?= $n['type']==='sale' ? 'var(--nh-green)' : ($n['type']==='purchase' ? 'var(--nh-primary)' : 'var(--nh-warning)') ?>;">
                    <i class="bi bi-<?= $n['type']==='sale' ? 'cash-coin' : ($n['type']==='purchase' ? 'bag-check' : 'info-circle') ?>"></i>
                </div>
                <div>
                    <div style="font-size:0.85rem;color:var(--nh-text);"><?= htmlspecialchars($n['message']) ?></div>
                    <div style="font-size:0.7rem;color:#444;margin-top:2px;"><?= timeAgo($n['created_at']) ?></div>
                </div>
            </div>
            <?php if (!$n['is_read']): ?>
            <span style="width:8px;height:8px;border-radius:50%;background:var(--nh-primary);flex-shrink:0;margin-top:4px;"></span>
            <?php endif; ?>
        </div>
    </<?= $n['link'] ? 'a' : 'div' ?>>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
