<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$cartCount = getCartCount();
$user = getCurrentUser();
$notifCount = isLoggedIn() ? getUnreadNotifCount($_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="breachme Marketplace - Jual Beli Laptop Gaming & Stiker Waifu">
    <title>breachme <?= isset($pageTitle) ? "- $pageTitle" : '- Marketplace' ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =============================================
           breachme Design System — Clean Marketplace
           ============================================= */
        :root {
            --nh-primary: #00d4ff;
            --nh-primary-dim: rgba(0, 212, 255, 0.1);
            --nh-primary-border: rgba(0, 212, 255, 0.2);
            --nh-green: #00ff88;
            --nh-green-dim: rgba(0, 255, 136, 0.1);
            --nh-green-border: rgba(0, 255, 136, 0.2);
            --nh-danger: #ff3b3b;
            --nh-danger-dim: rgba(255, 59, 59, 0.1);
            --nh-warning: #ffb800;
            --nh-warning-dim: rgba(255, 184, 0, 0.1);
            --nh-orange: #ff6b2b;
            --nh-orange-dim: rgba(255, 107, 43, 0.1);
            --nh-surface: #0f0f0f;
            --nh-surface-2: #0a0a0a;
            --nh-bg: #050505;
            --nh-text: #e0e0e0;
            --nh-text-muted: #555;
            --nh-text-secondary: #888;
            --nh-border: rgba(255, 255, 255, 0.06);
            --nh-border-hover: rgba(255, 255, 255, 0.12);
        }

        * {
            font-family: 'Inter', -apple-system, sans-serif;
        }

        body {
            background: var(--nh-bg);
            color: var(--nh-text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- Navbar ---- */
        .navbar-nhsec {
            background: rgba(5, 5, 5, 0.8) !important;
            backdrop-filter: blur(24px) saturate(1.2);
            -webkit-backdrop-filter: blur(24px) saturate(1.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 0;
            height: 56px;
        }

        .navbar-brand {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--nh-text) !important;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .navbar-brand .brand-icon {
            color: var(--nh-primary);
            font-size: 1rem;
        }

        .nav-link {
            color: var(--nh-text-secondary) !important;
            font-weight: 500;
            font-size: 0.82rem;
            padding: 6px 12px !important;
            border-radius: 4px;
            transition: color 0.15s ease;
            letter-spacing: -0.2px;
        }

        .nav-link:hover {
            color: var(--nh-text) !important;
            background: transparent;
        }

        .nav-link.active {
            color: var(--nh-primary) !important;
            background: transparent;
        }

        /* ---- Cards ---- */
        .card-nhsec {
            background: var(--nh-surface);
            border: 1px solid var(--nh-border);
            border-radius: 8px;
            transition: border-color 0.2s ease;
            overflow: hidden;
        }

        .card-nhsec:hover {
            border-color: var(--nh-border-hover);
            transform: none;
            box-shadow: none;
        }

        /* ---- Buttons ---- */
        .btn-nhsec {
            background: var(--nh-primary);
            color: #050505;
            border: none;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 0.82rem;
            transition: opacity 0.15s ease;
            letter-spacing: -0.2px;
        }

        .btn-nhsec:hover {
            opacity: 0.85;
            color: #050505;
            transform: none;
            box-shadow: none;
        }

        .btn-nhsec-outline {
            background: transparent;
            color: var(--nh-text-secondary);
            border: 1px solid var(--nh-border);
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 500;
            font-size: 0.82rem;
            transition: all 0.15s ease;
        }

        .btn-nhsec-outline:hover {
            background: rgba(255, 255, 255, 0.04);
            color: var(--nh-text);
            border-color: var(--nh-border-hover);
        }

        .btn-accent {
            background: var(--nh-primary);
            color: #050505;
            border: none;
            border-radius: 4px;
            padding: 8px 18px;
            font-weight: 600;
            font-size: 0.82rem;
            transition: opacity 0.15s ease;
        }

        .btn-accent:hover {
            opacity: 0.85;
            color: #050505;
            transform: none;
            box-shadow: none;
        }

        /* ---- Product ---- */
        .product-img-wrapper {
            height: 180px;
            background: var(--nh-surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid var(--nh-border);
        }

        .product-img-wrapper i {
            font-size: 3rem;
            color: #333;
        }

        .product-price {
            color: var(--nh-primary);
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
            color: var(--nh-primary);
            border: 1px solid var(--nh-primary-border);
        }

        .cat-sticker {
            background: transparent;
            color: var(--nh-green);
            border: 1px solid var(--nh-green-border);
        }

        /* ---- Product Grid (fluid) ---- */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
        }

        /* ---- Forms ---- */
        .form-control, .form-select {
            background: var(--nh-surface) !important;
            border: 1px solid var(--nh-border) !important;
            color: var(--nh-text) !important;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--nh-primary) !important;
            box-shadow: 0 0 0 1px var(--nh-primary-border) !important;
        }

        .form-control::placeholder {
            color: #444 !important;
        }

        .form-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--nh-text-muted) !important;
        }

        /* ---- Cart badge ---- */
        .cart-badge {
            position: relative;
        }
        .cart-badge .badge {
            position: absolute;
            top: -4px;
            right: -8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6rem;
            background: var(--nh-danger);
            border-radius: 2px;
            padding: 1px 4px;
        }

        /* ---- Notification bell ---- */
        .notif-badge {
            position: relative;
            cursor: pointer;
        }
        .notif-badge .notif-count {
            position: absolute;
            top: -4px;
            right: -8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.55rem;
            font-weight: 700;
            background: var(--nh-danger);
            color: #fff;
            border-radius: 2px;
            padding: 1px 4px;
            min-width: 14px;
            text-align: center;
        }

        /* ---- Wallet badge ---- */
        .wallet-balance {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--nh-green);
            background: var(--nh-green-dim);
            border: 1px solid var(--nh-green-border);
            padding: 2px 8px;
            border-radius: 3px;
        }

        /* ---- Table ---- */
        .table-nhsec {
            --bs-table-bg: transparent;
            --bs-table-color: var(--nh-text);
            --bs-table-border-color: var(--nh-border);
            font-size: 0.83rem;
        }
        .table-nhsec thead th {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--nh-text-muted);
            font-weight: 500;
            border-bottom-width: 1px;
        }

        /* ---- Footer ---- */
        .footer-nhsec {
            background: var(--nh-surface);
            border-top: 1px solid var(--nh-border);
            padding: 2rem 0;
            margin-top: 5rem;
        }

        /* ---- Scrollbar ---- */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--nh-bg); }
        ::-webkit-scrollbar-thumb { background: #222; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #333; }

        /* Firefox scrollbar */
        * { scrollbar-width: thin; scrollbar-color: #222 var(--nh-bg); }

        /* ---- Animations ---- */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: fadeIn 0.35s ease forwards;
        }

        /* ---- Star rating ---- */
        .star-rating .bi-star-fill { color: var(--nh-warning); }
        .star-rating .bi-star { color: #333; }

        /* ---- Dot grid hero bg ---- */
        .hero-dots {
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* ---- Dropdown ---- */
        .dropdown-menu {
            background: var(--nh-surface) !important;
            border: 1px solid var(--nh-border) !important;
            border-radius: 6px !important;
            padding: 4px !important;
        }
        .dropdown-item {
            color: var(--nh-text-secondary) !important;
            font-size: 0.82rem;
            border-radius: 4px;
            padding: 6px 12px;
        }
        .dropdown-item:hover {
            background: rgba(255,255,255,0.04) !important;
            color: var(--nh-text) !important;
        }
        .dropdown-divider {
            border-color: var(--nh-border) !important;
        }

        /* ---- Alert overrides ---- */
        .alert {
            border-radius: 6px;
            font-size: 0.85rem;
        }

        /* ---- Badge overrides ---- */
        .badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            font-weight: 500;
            border-radius: 2px;
            letter-spacing: 0.5px;
        }

        /* ---- Status badges ---- */
        .badge.bg-success { background: var(--nh-green-dim) !important; color: var(--nh-green) !important; border: 1px solid var(--nh-green-border); }
        .badge.bg-warning { background: var(--nh-warning-dim) !important; color: var(--nh-warning) !important; border: 1px solid rgba(255,184,0,0.2); }
        .badge.bg-danger { background: var(--nh-danger-dim) !important; color: var(--nh-danger) !important; border: 1px solid rgba(255,59,59,0.2); }
        .badge.bg-info { background: var(--nh-primary-dim) !important; color: var(--nh-primary) !important; border: 1px solid var(--nh-primary-border); }
        .badge.bg-primary { background: var(--nh-primary-dim) !important; color: var(--nh-primary) !important; border: 1px solid var(--nh-primary-border); }
        .badge.bg-secondary { background: rgba(255,255,255,0.04) !important; color: var(--nh-text-secondary) !important; border: 1px solid var(--nh-border); }

        /* ---- Misc ---- */
        a { color: var(--nh-primary); text-decoration: none; }
        a:hover { color: var(--nh-primary); opacity: 0.8; }
        code {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(255,255,255,0.04);
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 0.8rem;
            color: var(--nh-primary);
        }
        hr { border-color: var(--nh-border); }
        .text-muted { color: var(--nh-text-muted) !important; }
        .breadcrumb-item a { color: var(--nh-text-secondary) !important; }
        .breadcrumb-item.active { color: var(--nh-text-muted) !important; }
        .input-group-text {
            background: var(--nh-surface) !important;
            border: 1px solid var(--nh-border) !important;
            color: #444 !important;
            border-radius: 4px;
        }

        /* ---- Seller tag ---- */
        .seller-tag {
            font-size: 0.7rem;
            color: var(--nh-text-muted);
        }
        .seller-tag i { color: var(--nh-primary); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-nhsec sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/public/index.php">
                <i class="bi bi-shop brand-icon"></i> breachme
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="/public/index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/index.php#products">Produk</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'sell' ? 'active' : '' ?>" href="/public/sell.php">Jual</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>" href="/public/orders.php">Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'invoices' ? 'active' : '' ?>" href="/public/invoices.php">Invoice</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Search bar -->
                <form class="d-flex mx-3" method="GET" action="/public/search.php" style="flex:1;max-width:280px;">
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="form-control"
                               placeholder="Cari produk..."
                               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                               style="background:rgba(255,255,255,.03);border-color:var(--nh-border);font-size:.8rem;">
                        <button type="submit" class="input-group-text" style="cursor:pointer;background:rgba(0,212,255,.08);border-color:var(--nh-border);">
                            <i class="bi bi-search" style="color:var(--nh-primary);font-size:.8rem;"></i>
                        </button>
                    </div>
                </form>

                <div class="d-flex align-items-center gap-3">
                    <?php if (isLoggedIn()): ?>
                    <!-- Wallet -->
                    <a href="/public/wallet.php" class="wallet-balance text-decoration-none">
                        <i class="bi bi-wallet2"></i> <?= formatRupiah($user['balance'] ?? 0) ?>
                    </a>

                    <!-- Notifications -->
                    <a href="/public/notifications.php" class="nav-link notif-badge">
                        <i class="bi bi-bell" style="font-size: 1.1rem;"></i>
                        <?php if ($notifCount > 0): ?>
                        <span class="notif-count"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Cart -->
                    <a href="/public/cart.php" class="nav-link cart-badge">
                        <i class="bi bi-bag" style="font-size: 1.1rem;"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="dropdown">
                        <?php
                        $navAvatar = avatarUrl($user);
                        $navName   = displayName($user);
                        $navInit   = strtoupper(substr($navName,0,1));
                        ?>
                        <button class="btn btn-sm btn-nhsec-outline dropdown-toggle d-flex align-items-center gap-2"
                                data-bs-toggle="dropdown" style="font-size:0.78rem;padding:4px 10px;">
                            <?php if($navAvatar): ?>
                            <img src="<?= $navAvatar ?>" style="width:22px;height:22px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                            <span style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--nh-primary),var(--nh-green));display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#050505;"><?= $navInit ?></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($navName) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text" style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;letter-spacing:1px;"><?= strtoupper($user['role'] ?? 'user') ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/public/profile.php"><i class="bi bi-person"></i> Profil Saya</a></li>
                            <li><a class="dropdown-item" href="/public/wallet.php"><i class="bi bi-wallet2"></i> E-Wallet</a></li>
                            <li><a class="dropdown-item" href="/public/orders.php"><i class="bi bi-receipt"></i> Pesanan</a></li>
                            <li><a class="dropdown-item" href="/public/invoices.php"><i class="bi bi-file-text"></i> Invoice</a></li>
                            <li><a class="dropdown-item" href="/public/notifications.php"><i class="bi bi-bell"></i> Notifikasi</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/public/logout.php" style="color:var(--nh-danger) !important;"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="/public/login.php" class="btn btn-nhsec btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
