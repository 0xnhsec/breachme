<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/../includes/functions.php';

$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, u.username as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.status = 'active'";
$params = [];
$types = "";

if ($category_filter && in_array($category_filter, ['laptop', 'sticker'])) {
    $sql .= " AND p.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 mb-4 hero-dots" style="margin: -1.5rem -0.75rem 2rem; padding: 4rem 2rem; border-bottom: 1px solid var(--nh-border);">
    <div class="row align-items-center">
        <div class="col-lg-7 animate-in">
            <div style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:var(--nh-primary);text-transform:uppercase;letter-spacing:2px;margin-bottom:12px;">
                <i class="bi bi-shop"></i> MARKETPLACE
            </div>
            <h1 style="font-weight: 800; font-size: 2.8rem; line-height: 1.05; letter-spacing: -1.5px; color: var(--nh-text);">
                Temukan Produk<br>
                <span style="color: var(--nh-primary);">Terbaik dari Seller</span>
            </h1>
            <p style="color: #555; margin-top: 1rem; margin-bottom: 2rem; font-size: 0.9rem; max-width: 440px; line-height: 1.6;">
                Jual beli laptop gaming & stiker waifu dari seller terpercaya.
                Transaksi aman dengan e-wallet terintegrasi.
            </p>
            <div class="d-flex gap-2">
                <a href="#products" class="btn btn-nhsec">
                    Explore Products <i class="bi bi-arrow-right"></i>
                </a>
                <?php if (!isLoggedIn()): ?>
                <a href="/public/register.php" class="btn btn-nhsec-outline">Sign Up</a>
                <?php else: ?>
                <a href="/public/sell.php" class="btn btn-nhsec-outline">
                    <i class="bi bi-plus-lg"></i> Jual Produk
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0 animate-in" style="animation-delay: 0.1s;">
            <div style="background: var(--nh-surface); border: 1px solid var(--nh-border); border-radius: 8px; padding: 1.5rem;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">Platform Stats</span>
                </div>
                <?php
                $stat_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
                $stat_sellers = $conn->query("SELECT COUNT(DISTINCT seller_id) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
                $stat_transactions = $conn->query("SELECT COUNT(*) as c FROM invoices WHERE status='paid'")->fetch_assoc()['c'];
                ?>
                <div class="row g-3">
                    <div class="col-4 text-center">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--nh-primary);"><?= $stat_products ?></div>
                        <div style="font-size:0.7rem;color:#444;">Produk</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--nh-green);"><?= $stat_sellers ?></div>
                        <div style="font-size:0.7rem;color:#444;">Seller</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-family:'JetBrains Mono',monospace;font-size:1.5rem;font-weight:700;color:var(--nh-warning);"><?= $stat_transactions ?></div>
                        <div style="font-size:0.7rem;color:#444;">Transaksi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search & Filter -->
<section id="products" class="mb-4">
    <form method="GET" class="row g-2 mb-4 animate-in" style="animation-delay: 0.1s;">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select" onchange="this.form.submit()">
                <option value="">Semua kategori</option>
                <option value="laptop" <?= $category_filter === 'laptop' ? 'selected' : '' ?>>Laptop Gaming</option>
                <option value="sticker" <?= $category_filter === 'sticker' ? 'selected' : '' ?>>Stiker Waifu</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-nhsec-outline w-100"><i class="bi bi-search"></i> Cari</button>
        </div>
    </form>
</section>

<!-- Products Grid -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">
                <?= $category_filter ? ($category_filter === 'laptop' ? 'LAPTOP GAMING' : 'STIKER WAIFU') : 'SEMUA PRODUK' ?>
            </span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#333;margin-left:8px;"><?= count($products) ?> items</span>
        </div>
    </div>

    <?php if (empty($products)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #222;"></i>
        <p style="color: #444; margin-top: 1rem; font-size: 0.85rem;">Tidak ada produk ditemukan.</p>
    </div>
    <?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $i => $product): ?>
        <div class="animate-in" style="animation-delay: <?= $i * 0.03 ?>s;">
            <div class="card card-nhsec h-100">
                <div class="product-img-wrapper">
                    <?php if (!empty($product['image']) && $product['image'] !== 'default.jpg'): ?>
                    <img src="/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <i class="bi bi-<?= $product['category'] === 'laptop' ? 'laptop' : 'stickies' ?>"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="product-category <?= $product['category'] === 'laptop' ? 'cat-laptop' : 'cat-sticker' ?>">
                            <?= $product['category'] === 'laptop' ? 'LAPTOP' : 'STICKER' ?>
                        </span>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#333;">
                            stk:<?= $product['stock'] ?>
                        </span>
                    </div>
                    <h6 class="mb-1" style="font-weight: 600; font-size: 0.88rem;">
                        <a href="/public/product.php?id=<?= $product['id'] ?>" style="color: var(--nh-text); text-decoration: none;">
                            <?= htmlspecialchars($product['name']) ?>
                        </a>
                    </h6>
                    <div class="seller-tag mb-2">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($product['seller_name']) ?>
                    </div>
                    <p style="color: #444; font-size: 0.75rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 12px;">
                        <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="product-price"><?= formatRupiah($product['price']) ?></span>
                        <a href="/public/product.php?id=<?= $product['id'] ?>" class="btn btn-nhsec-outline" style="font-size:0.72rem;padding:4px 12px;">
                            Lihat <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
