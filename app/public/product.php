<?php
// [VULN: XSS] - Review comments are NOT sanitized intentional
$pageTitle = 'Detail Produk';
require_once __DIR__ . '/../includes/functions.php';

$product_id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT p.*, u.username as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: /public/index.php');
    exit;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isset($_POST['comment'])) {
    $comment = $_POST['comment'] ?? '';  // [VULN: XSS] - no sanitization intentional
    $rating = (int)($_POST['rating'] ?? 5);

    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, comment, rating) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $product_id, $_SESSION['user_id'], $comment, $rating);
    $stmt->execute();

    header("Location: /public/product.php?id=$product_id#reviews");
    exit;
}

// Handle add to cart
if (isset($_POST['add_to_cart']) && isLoggedIn()) {
    $qty = (int)($_POST['quantity'] ?? 1);
    // [VULN: BUSINESS-LOGIC] - no negative quantity check intentional

    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $_SESSION['user_id'], $product_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        $new_qty = $existing['quantity'] + $qty;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $new_qty, $existing['id']);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $_SESSION['user_id'], $product_id, $qty);
        $insert->execute();
    }

    header("Location: /public/product.php?id=$product_id&added=1");
    exit;
}

// Handle direct buy
if (isset($_POST['buy_now']) && isLoggedIn()) {
    $qty = (int)($_POST['quantity'] ?? 1);
    $total_cost = $product['price'] * $qty;
    $tax = $total_cost * 0.02;
    $total_debit = $total_cost + $tax;
    $buyer_balance = getBalance($_SESSION['user_id']);

    if ($buyer_balance < $total_debit) {
        $buy_error = 'Saldo tidak mencukupi! Butuh ' . formatRupiah($total_debit);
    } elseif ($product['seller_id'] == $_SESSION['user_id']) {
        $buy_error = 'Tidak bisa membeli produk sendiri!';
    } elseif ($product['stock'] < $qty) {
        $buy_error = 'Stok tidak mencukupi!';
    } else {
        $invoice = processTransaction($_SESSION['user_id'], $product['seller_id'], $product_id, $qty, $product['price']);
        header("Location: /public/invoice.php?id=" . $invoice['id'] . "&success=1");
        exit;
    }
}

$reviews_stmt = $conn->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$avg_rating = 0;
if (count($reviews) > 0) {
    $total = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($total / count($reviews), 1);
}

$pageTitle = $product['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['added'])): ?>
<div class="alert" style="background:rgba(0,255,136,0.06);border:1px solid rgba(0,255,136,0.15);color:var(--nh-green);font-size:0.82rem;">
    Added to cart. <a href="/public/cart.php">View cart →</a>
</div>
<?php endif; ?>

<?php if (isset($buy_error)): ?>
<div class="alert" style="background:rgba(255,59,59,0.06);border:1px solid rgba(255,59,59,0.15);color:var(--nh-danger);font-size:0.82rem;">
    <?= $buy_error ?>
</div>
<?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb mb-0" style="background:transparent;font-size:0.8rem;">
        <li class="breadcrumb-item"><a href="/public/index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="/public/index.php?category=<?= $product['category'] ?>"><?= $product['category'] === 'laptop' ? 'Laptop' : 'Sticker' ?></a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
    </ol>
</nav>

<!-- Product Detail -->
<div class="row g-4 mb-5 animate-in">
    <div class="col-lg-5">
        <div class="card-nhsec" style="padding: 1rem;">
            <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: var(--nh-surface-2); border-radius: 4px; border: 1px solid var(--nh-border); overflow: hidden;">
                <?php if (!empty($product['image']) && $product['image'] !== 'default.jpg'): ?>
                <img src="/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;height:100%;object-fit:contain;">
                <?php else: ?>
                <i class="bi bi-<?= $product['category'] === 'laptop' ? 'laptop' : 'stickies' ?>" style="font-size: 5rem; color: #222;"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card-nhsec p-4">
            <span class="product-category <?= $product['category'] === 'laptop' ? 'cat-laptop' : 'cat-sticker' ?> mb-3" style="display:inline-block;">
                <?= $product['category'] === 'laptop' ? 'LAPTOP GAMING' : 'STIKER WAIFU' ?>
            </span>

            <h1 style="font-weight: 700; font-size: 1.5rem; letter-spacing: -0.5px; margin-top: 8px;"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="seller-tag mb-2">
                <i class="bi bi-person-circle"></i> Dijual oleh <strong><?= htmlspecialchars($product['seller_name']) ?></strong>
            </div>

            <div class="d-flex align-items-center gap-2 mb-3" style="margin-top:8px;">
                <div class="star-rating" style="font-size:0.85rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= round($avg_rating) ? '-fill' : '' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.68rem;color:#444;"><?= $avg_rating ?> · <?= count($reviews) ?> reviews</span>
            </div>

            <div class="mb-3">
                <span class="product-price" style="font-size: 1.6rem;"><?= formatRupiah($product['price']) ?></span>
            </div>

            <div class="mb-3" style="font-size:0.82rem;">
                <span style="color:#555;">Stock:</span>
                <span style="color:<?= $product['stock'] > 0 ? 'var(--nh-green)' : 'var(--nh-danger)' ?>;font-family:'JetBrains Mono',monospace;font-size:0.78rem;">
                    <?= $product['stock'] > 0 ? $product['stock'] : 'OUT OF STOCK' ?>
                </span>
            </div>

            <div class="mb-4">
                <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#333;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:6px;">Deskripsi</div>
                <p style="color:#888;font-size:0.82rem;line-height:1.7;"><?= htmlspecialchars($product['description']) ?></p>
            </div>

            <?php if (isLoggedIn()): ?>
            <form method="POST" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label">QTY</label>
                    <!-- [VULN: BUSINESS-LOGIC] - no min/negative validation intentional -->
                    <input type="number" name="quantity" value="1" class="form-control" style="width: 80px;" id="product-qty">
                </div>
                <button type="submit" name="add_to_cart" value="1" class="btn btn-nhsec-outline" id="add-to-cart">
                    <i class="bi bi-bag-plus"></i> Keranjang
                </button>
                <button type="submit" name="buy_now" value="1" class="btn btn-nhsec" id="buy-now">
                    <i class="bi bi-lightning"></i> Beli Langsung
                </button>
            </form>
            <?php else: ?>
            <a href="/public/login.php" class="btn btn-nhsec">Sign in to purchase</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reviews -->
<section id="reviews" class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">REVIEWS · <?= count($reviews) ?></span>
    </div>

    <?php if (isLoggedIn()): ?>
    <div class="card-nhsec p-4 mb-4">
        <!-- [VULN: XSS] - form input is not sanitized intentional -->
        <form method="POST" id="review-form">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">RATING</label>
                    <select name="rating" class="form-select" id="review-rating">
                        <option value="5">5</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">KOMENTAR</label>
                    <input type="text" name="comment" class="form-control" placeholder="Tulis review..." required id="review-comment">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-nhsec w-100" id="review-submit">Kirim</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
    <div style="text-align:center;padding:2rem;color:#333;font-family:'JetBrains Mono',monospace;font-size:0.75rem;">Belum ada review</div>
    <?php else: ?>
    <?php foreach ($reviews as $review): ?>
    <div class="card-nhsec p-3 mb-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width:28px;height:28px;border-radius:4px;background:var(--nh-surface-2);border:1px solid var(--nh-border);display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:0.65rem;font-weight:600;color:#666;">
                    <?= strtoupper(substr($review['username'], 0, 1)) ?>
                </div>
                <div>
                    <span style="font-size:0.82rem;font-weight:600;"><?= htmlspecialchars($review['username']) ?></span>
                    <div class="star-rating" style="font-size:0.7rem;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#333;"><?= timeAgo($review['created_at']) ?></span>
        </div>
        <!-- [VULN: XSS] - comment output is NOT escaped intentional -->
        <div style="font-size:0.82rem;color:#999;line-height:1.6;">
            <?= $review['comment'] ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
