<?php
$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nhsec Admin — <?= isset($pageTitle) ? $pageTitle : 'Dashboard' ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =============================================
           nhsec Admin Panel — Separate Layout
           ============================================= */
        :root {
            --adm-bg: #050505;
            --adm-sidebar: #0a0a0a;
            --adm-surface: #0f0f0f;
            --adm-surface-2: #141414;
            --adm-primary: #00d4ff;
            --adm-primary-dim: rgba(0, 212, 255, 0.1);
            --adm-primary-border: rgba(0, 212, 255, 0.2);
            --adm-green: #00ff88;
            --adm-green-dim: rgba(0, 255, 136, 0.1);
            --adm-green-border: rgba(0, 255, 136, 0.2);
            --adm-danger: #ff3b3b;
            --adm-danger-dim: rgba(255, 59, 59, 0.1);
            --adm-warning: #ffb800;
            --adm-warning-dim: rgba(255, 184, 0, 0.1);
            --adm-orange: #ff6b2b;
            --adm-text: #e0e0e0;
            --adm-text-muted: #555;
            --adm-text-secondary: #888;
            --adm-border: rgba(255, 255, 255, 0.06);
            --adm-border-hover: rgba(255, 255, 255, 0.12);
        }

        * { font-family: 'Inter', -apple-system, sans-serif; }

        body {
            background: var(--adm-bg);
            color: var(--adm-text);
            min-height: 100vh;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- Admin Layout: Sidebar + Content ---- */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ---- Sidebar ---- */
        .admin-sidebar {
            width: 240px;
            background: var(--adm-sidebar);
            border-right: 1px solid var(--adm-border);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 16px 20px;
            border-bottom: 1px solid var(--adm-border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sidebar-brand-icon {
            color: var(--adm-primary);
            font-size: 1.1rem;
        }
        .sidebar-brand-text {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--adm-text);
        }
        .sidebar-brand-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.55rem;
            font-weight: 600;
            color: var(--adm-primary);
            background: var(--adm-primary-dim);
            border: 1px solid var(--adm-primary-border);
            padding: 1px 6px;
            border-radius: 2px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-section {
            padding: 16px 12px 8px;
        }
        .sidebar-section-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.55rem;
            font-weight: 500;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 0 8px;
            margin-bottom: 6px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-nav-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 4px;
            color: var(--adm-text-secondary);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            transition: all 0.15s ease;
            margin: 1px 4px;
        }
        .sidebar-nav-item a:hover {
            color: var(--adm-text);
            background: rgba(255, 255, 255, 0.04);
        }
        .sidebar-nav-item a.active {
            color: var(--adm-primary);
            background: var(--adm-primary-dim);
        }
        .sidebar-nav-item a i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-divider {
            border-top: 1px solid var(--adm-border);
            margin: 8px 16px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 12px;
            border-top: 1px solid var(--adm-border);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            background: rgba(255,255,255,0.02);
        }
        .sidebar-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background: var(--adm-primary-dim);
            border: 1px solid var(--adm-primary-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--adm-primary);
        }
        .sidebar-user-info {
            flex: 1;
            min-width: 0;
        }
        .sidebar-user-name {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--adm-text);
        }
        .sidebar-user-role {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.55rem;
            color: var(--adm-danger);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ---- Main Content ---- */
        .admin-main {
            margin-left: 240px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-topbar {
            height: 48px;
            background: rgba(5,5,5,0.8);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--adm-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .admin-topbar-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--adm-text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .admin-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .admin-topbar-actions a {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            color: var(--adm-text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border: 1px solid var(--adm-border);
            border-radius: 3px;
            transition: all 0.15s;
        }
        .admin-topbar-actions a:hover {
            background: rgba(255,255,255,0.04);
            color: var(--adm-text);
        }

        .admin-content {
            padding: 24px;
            flex: 1;
        }

        /* ---- Reuse card/table/badge/btn styles ---- */
        .card-nhsec {
            background: var(--adm-surface);
            border: 1px solid var(--adm-border);
            border-radius: 8px;
            transition: border-color 0.2s ease;
            overflow: hidden;
        }
        .card-nhsec:hover {
            border-color: var(--adm-border-hover);
        }

        .btn-nhsec {
            background: var(--adm-primary);
            color: #050505;
            border: none;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 0.82rem;
            transition: opacity 0.15s ease;
        }
        .btn-nhsec:hover { opacity: 0.85; color: #050505; }

        .btn-nhsec-outline {
            background: transparent;
            color: var(--adm-text-secondary);
            border: 1px solid var(--adm-border);
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 500;
            font-size: 0.82rem;
            transition: all 0.15s ease;
        }
        .btn-nhsec-outline:hover {
            background: rgba(255,255,255,0.04);
            color: var(--adm-text);
            border-color: var(--adm-border-hover);
        }

        .table-nhsec {
            --bs-table-bg: transparent;
            --bs-table-color: var(--adm-text);
            --bs-table-border-color: var(--adm-border);
            font-size: 0.83rem;
        }
        .table-nhsec thead th {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--adm-text-muted);
            font-weight: 500;
            border-bottom-width: 1px;
        }

        .form-control, .form-select {
            background: var(--adm-surface) !important;
            border: 1px solid var(--adm-border) !important;
            color: var(--adm-text) !important;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 0.85rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--adm-primary) !important;
            box-shadow: 0 0 0 1px var(--adm-primary-border) !important;
        }
        .form-control::placeholder { color: #444 !important; }
        .form-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--adm-text-muted) !important;
        }

        .product-price {
            color: var(--adm-primary);
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .product-category {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 3px 8px;
            border-radius: 2px;
        }
        .cat-laptop {
            background: transparent;
            color: var(--adm-primary);
            border: 1px solid var(--adm-primary-border);
        }
        .cat-sticker {
            background: transparent;
            color: var(--adm-green);
            border: 1px solid var(--adm-green-border);
        }

        .badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            font-weight: 500;
            border-radius: 2px;
            letter-spacing: 0.5px;
        }
        .badge.bg-success { background: var(--adm-green-dim) !important; color: var(--adm-green) !important; border: 1px solid var(--adm-green-border); }
        .badge.bg-warning { background: var(--adm-warning-dim) !important; color: var(--adm-warning) !important; border: 1px solid rgba(255,184,0,0.2); }
        .badge.bg-danger { background: var(--adm-danger-dim) !important; color: var(--adm-danger) !important; border: 1px solid rgba(255,59,59,0.2); }
        .badge.bg-info { background: var(--adm-primary-dim) !important; color: var(--adm-primary) !important; border: 1px solid var(--adm-primary-border); }
        .badge.bg-primary { background: var(--adm-primary-dim) !important; color: var(--adm-primary) !important; border: 1px solid var(--adm-primary-border); }
        .badge.bg-secondary { background: rgba(255,255,255,0.04) !important; color: var(--adm-text-secondary) !important; border: 1px solid var(--adm-border); }

        a { color: var(--adm-primary); text-decoration: none; }
        a:hover { color: var(--adm-primary); opacity: 0.8; }
        hr { border-color: var(--adm-border); }
        .text-muted { color: var(--adm-text-muted) !important; }

        .alert { border-radius: 6px; font-size: 0.85rem; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--adm-bg); }
        ::-webkit-scrollbar-thumb { background: #222; border-radius: 3px; }
        * { scrollbar-width: thin; scrollbar-color: #222 var(--adm-bg); }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar { width: 60px; }
            .admin-sidebar .sidebar-brand-text,
            .admin-sidebar .sidebar-brand-label,
            .admin-sidebar .sidebar-section-title,
            .admin-sidebar .sidebar-nav-item a span,
            .admin-sidebar .sidebar-user-info { display: none; }
            .admin-sidebar .sidebar-nav-item a { justify-content: center; padding: 10px; }
            .admin-main { margin-left: 60px; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-shield-fill-check sidebar-brand-icon"></i>
                <span class="sidebar-brand-text">nhsec</span>
                <span class="sidebar-brand-label">Admin</span>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Menu Utama</div>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="/admin/" class="<?= $currentAdminPage === 'index' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/users.php" class="<?= $currentAdminPage === 'users' ? 'active' : '' ?>">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/products.php" class="<?= $currentAdminPage === 'products' ? 'active' : '' ?>">
                            <i class="bi bi-box-seam"></i>
                            <span>Produk</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/orders.php" class="<?= $currentAdminPage === 'orders' ? 'active' : '' ?>">
                            <i class="bi bi-receipt"></i>
                            <span>Pesanan</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/invoices.php" class="<?= $currentAdminPage === 'invoices' ? 'active' : '' ?>">
                            <i class="bi bi-file-text"></i>
                            <span>Invoice</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-divider"></div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Keamanan</div>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="/admin/ip-management.php" class="<?= $currentAdminPage === 'ip-management' ? 'active' : '' ?>">
                            <i class="bi bi-shield-exclamation"></i>
                            <span>IP Management</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/siem/dashboard.php" class="<?= false /* never active here */ ? 'active' : '' ?>">
                            <i class="bi bi-shield-fill-check"></i>
                            <span>SIEM Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-divider"></div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Lainnya</div>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="/public/index.php">
                            <i class="bi bi-shop"></i>
                            <span>Lihat Toko</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User info at bottom -->
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        <?= strtoupper(substr($user['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?= htmlspecialchars($user['username'] ?? 'Admin') ?></div>
                        <div class="sidebar-user-role">Administrator</div>
                    </div>
                    <a href="/public/logout.php" style="color:var(--adm-text-muted);font-size:1rem;" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="admin-main">
            <div class="admin-topbar">
                <span class="admin-topbar-title"><?= isset($pageTitle) ? $pageTitle : 'Admin Panel' ?></span>
                <div class="admin-topbar-actions">
                    <a href="/public/index.php"><i class="bi bi-shop"></i> Marketplace</a>
                    <a href="/public/logout.php" style="color:var(--adm-danger);border-color:rgba(255,59,59,0.15);"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>

            <div class="admin-content">
