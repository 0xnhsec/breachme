<?php
$pageTitle = 'Jual Produk';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category = $_POST['category'] ?? 'laptop';

    if (strlen($name) < 3) {
        $error = 'Nama produk minimal 3 karakter.';
    } elseif ($price <= 0) {
        $error = 'Harga harus lebih dari 0.';
    } elseif ($stock <= 0) {
        $error = 'Stok harus lebih dari 0.';
    } else {
        $image_filename = 'default.jpg';

        // Handle image upload
        // [VULN: UNRESTRICTED_UPLOAD] — only checks extension, not magic bytes intentional
        if (!empty($_FILES['image']['name'])) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_size = $_FILES['image']['size'];

            if (!in_array($file_ext, $allowed_ext)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
            } elseif ($file_size > 5 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 5MB.';
            } else {
                $upload_dir = '/var/www/html/uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $image_filename = uniqid('prod_', true) . '.' . $file_ext;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_filename)) {
                    $error = 'Gagal mengupload gambar. Coba lagi.';
                    $image_filename = 'default.jpg';
                }
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO products (seller_id, name, description, price, stock, image, category, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("issdiss", $_SESSION['user_id'], $name, $description, $price, $stock, $image_filename, $category);
            $stmt->execute();
            $success = 'Produk berhasil ditambahkan!';
        }
    }
}

// Get user's products
$stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$my_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4" style="font-weight: 800;"><i class="bi bi-plus-circle" style="color: var(--nh-primary);"></i> Jual Produk</h2>

<?php if ($error): ?>
<div class="alert" style="background:rgba(255,59,59,0.06);border:1px solid rgba(255,59,59,0.15);color:var(--nh-danger);font-size:0.82rem;"><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert" style="background:rgba(0,255,136,0.06);border:1px solid rgba(0,255,136,0.15);color:var(--nh-green);font-size:0.82rem;"><?= $success ?></div>
<?php endif; ?>

<!-- Add Product Form -->
<div class="card card-nhsec p-4 mb-4">
    <h5 class="fw-bold mb-3">Tambah Produk Baru</h5>
    <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">NAMA PRODUK</label>
                <input type="text" name="name" class="form-control" required placeholder="Nama produk...">
            </div>
            <div class="col-md-3">
                <label class="form-label">KATEGORI</label>
                <select name="category" class="form-select">
                    <option value="laptop">Laptop Gaming</option>
                    <option value="sticker">Stiker Waifu</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">STOK</label>
                <input type="number" name="stock" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-4">
                <label class="form-label">HARGA (RP)</label>
                <input type="number" name="price" class="form-control" step="1000" min="1000" placeholder="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">GAMBAR PRODUK</label>
                <input type="file" name="image" id="imageInput" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                <div style="font-size:0.7rem;color:#444;margin-top:4px;">JPG/PNG/GIF/WEBP · maks 5MB (opsional)</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">PREVIEW</label>
                <div id="imgPreviewWrap" style="display:none;width:80px;height:80px;border:1px solid var(--nh-border);border-radius:4px;overflow:hidden;">
                    <img id="imgPreview" src="" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div id="imgPreviewEmpty" style="font-size:0.72rem;color:#333;">—</div>
            </div>
            <div class="col-12">
                <label class="form-label">DESKRIPSI</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Deskripsi produk..."></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-nhsec mt-3"><i class="bi bi-upload"></i> Pasang Produk</button>
    </form>
    <script>
        document.getElementById('imageInput').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('imgPreview').src = e.target.result;
                    document.getElementById('imgPreviewWrap').style.display = 'block';
                    document.getElementById('imgPreviewEmpty').style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imgPreviewWrap').style.display = 'none';
                document.getElementById('imgPreviewEmpty').style.display = 'block';
            }
        });
    </script>
</div>

<!-- My Products -->
<div class="card card-nhsec p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Produk Saya <span class="badge bg-secondary"><?= count($my_products) ?></span></h5>
    <?php if (empty($my_products)): ?>
    <p class="text-muted text-center py-3">Belum ada produk. Mulai jual sekarang!</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-nhsec">
            <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($my_products as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><span class="product-category <?= $p['category']==='laptop'?'cat-laptop':'cat-sticker' ?>"><?= $p['category'] ?></span></td>
                <td class="product-price"><?= formatRupiah($p['price']) ?></td>
                <td><?= $p['stock'] ?></td>
                <td><span class="badge <?= $p['status']==='active'?'bg-success':($p['status']==='pending'?'bg-warning':'bg-danger') ?>"><?= $p['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
