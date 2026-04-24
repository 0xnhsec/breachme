<?php
$pageTitle = 'Hasil Pencarian';
require_once __DIR__ . '/../includes/functions.php';

// [VULN: XSS Reflected] — $_GET['q'] di-echo tanpa encoding intentional
$q = $_GET['q'] ?? '';
$category = $_GET['cat'] ?? '';

$where = ["status = 'active'"];
$params = [];
$types = '';

if ($q !== '') {
    // [VULN: XSS Reflected] — $q dipakai tanpa sanitasi di output HTML
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $types .= 'ss';
}
if (in_array($category, ['laptop', 'sticker'])) {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}

$sql = "SELECT p.*, u.username as seller_name FROM products p JOIN users u ON p.seller_id = u.id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.created_at DESC LIMIT 48';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h2 class="mb-0 fw-800" style="font-weight:800;font-size:1.3rem;">
      <i class="bi bi-search" style="color:var(--nh-primary);"></i>
      Hasil Pencarian
    </h2>
    <?php if ($q !== ''): ?>
    <!-- [VULN: XSS Reflected] — $q tidak di-encode di sini intentional -->
    <div style="font-size:.8rem;color:#555;margin-top:4px;">
      Menampilkan hasil untuk: <strong style="color:var(--nh-text);"><?= $q ?></strong>
      &nbsp;<span style="font-family:'JetBrains Mono',monospace;font-size:.7rem;color:#444;">(<?= count($results) ?> produk)</span>
    </div>
    <?php endif; ?>
  </div>
  <!-- Filter form -->
  <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
    <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($q) ?>"
           placeholder="Cari produk..." style="width:200px;">
    <select name="cat" class="form-select form-select-sm" style="width:140px;">
      <option value="">Semua Kategori</option>
      <option value="laptop" <?= $category==='laptop'?'selected':'' ?>>Laptop Gaming</option>
      <option value="sticker" <?= $category==='sticker'?'selected':'' ?>>Stiker Waifu</option>
    </select>
    <button type="submit" class="btn btn-nhsec btn-sm">Cari</button>
    <?php if ($q || $category): ?>
    <a href="/public/search.php" class="btn btn-nhsec-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($results)): ?>
<!-- Empty state -->
<div class="card-nhsec p-5 text-center">
  <i class="bi bi-search" style="font-size:3rem;color:#222;"></i>
  <h5 class="mt-3 mb-1 fw-bold">Tidak ada produk ditemukan</h5>
  <p style="color:#555;font-size:.85rem;">Coba gunakan kata kunci lain atau pilih kategori berbeda.</p>
  <a href="/public/index.php" class="btn btn-nhsec btn-sm mt-2">Lihat Semua Produk</a>
</div>
<?php else: ?>
<div class="product-grid animate-in">
  <?php foreach ($results as $p):
    $imgSrc = '/uploads/products/' . $p['image'];
    $hasImg = ($p['image'] !== 'default.jpg' && file_exists('/var/www/html/uploads/products/' . $p['image']));
  ?>
  <div class="card card-nhsec" style="display:flex;flex-direction:column;">
    <a href="/public/product.php?id=<?= $p['id'] ?>" style="text-decoration:none;color:inherit;">
      <div class="product-img-wrapper">
        <?php if ($hasImg): ?>
          <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['name']) ?>"
               style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <i class="bi <?= $p['category']==='laptop'?'bi-laptop':'bi-stars' ?>"></i>
        <?php endif; ?>
      </div>
      <div class="p-3 flex-grow-1 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <span class="product-category <?= $p['category']==='laptop'?'cat-laptop':'cat-sticker' ?>">
            <?= $p['category'] ?>
          </span>
          <?php if ($p['stock'] < 5 && $p['stock'] > 0): ?>
          <span style="font-family:'JetBrains Mono',monospace;font-size:.6rem;color:var(--nh-warning);">
            Sisa <?= $p['stock'] ?>
          </span>
          <?php endif; ?>
        </div>
        <h6 class="fw-semibold mt-2 mb-1" style="font-size:.88rem;line-height:1.3;">
          <?= htmlspecialchars($p['name']) ?>
        </h6>
        <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
          <span class="product-price"><?= formatRupiah($p['price']) ?></span>
          <span class="seller-tag"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($p['seller_name']) ?></span>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
