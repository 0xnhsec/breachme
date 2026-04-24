<?php
$pageTitle = 'Import Wishlist';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = $success = '';
$parsed_items = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['wishlist_xml'])) {
    $file = $_FILES['wishlist_xml'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $xml_content = file_get_contents($file['tmp_name']);

        // [VULN: XXE] — libxml external entities TIDAK dinonaktifkan intentional
        // Seharusnya: libxml_disable_entity_loader(true); SEBELUM simplexml_load_string()
        $old = libxml_use_internal_errors(true);

        // XXE: attacker bisa inject <!DOCTYPE> dengan <!ENTITY> untuk baca file
        $xml = simplexml_load_string($xml_content, 'SimpleXMLElement', LIBXML_NOENT);

        if ($xml === false) {
            $error = 'File XML tidak valid.';
        } else {
            foreach ($xml->item as $item) {
                $product_name = (string)$item->name;
                $price        = (float)$item->price;
                $parsed_items[] = [
                    'name'  => $product_name,
                    'price' => $price,
                ];
            }
            if (empty($parsed_items)) {
                $error = 'Tidak ada item ditemukan di XML. Pastikan format benar.';
            } else {
                $success = count($parsed_items) . ' item berhasil diparse dari wishlist.';
            }
        }
        libxml_use_internal_errors($old);
    } else {
        $error = 'Gagal upload file XML.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-file-earmark-code" style="color:var(--nh-primary);font-size:1.4rem;"></i>
  <h2 class="mb-0" style="font-weight:800;">Import Wishlist (XML)</h2>
</div>

<?php if($error):?>
<div class="alert mb-3" style="background:rgba(255,59,59,.06);border:1px solid rgba(255,59,59,.2);color:var(--nh-danger);">
  <i class="bi bi-exclamation-triangle me-2"></i><?=htmlspecialchars($error)?>
</div>
<?php endif;?>
<?php if($success):?>
<div class="alert mb-3" style="background:rgba(0,255,136,.06);border:1px solid rgba(0,255,136,.2);color:var(--nh-green);">
  <i class="bi bi-check-circle me-2"></i><?=htmlspecialchars($success)?>
</div>
<?php endif;?>

<div class="card card-nhsec p-4 mb-4">
  <h5 class="fw-bold mb-3">Upload Wishlist XML</h5>
  <p style="font-size:.85rem;color:#666;margin-bottom:1.5rem;">
    Upload file XML berisi daftar produk yang ingin kamu cari di marketplace.
    Format yang diterima:
  </p>
  <pre style="background:rgba(255,255,255,.02);border:1px solid var(--nh-border);border-radius:4px;padding:1rem;font-size:.75rem;color:#888;margin-bottom:1.5rem;">&lt;wishlist&gt;
  &lt;item&gt;
    &lt;name&gt;ASUS ROG Strix G16&lt;/name&gt;
    &lt;price&gt;25000000&lt;/price&gt;
  &lt;/item&gt;
  &lt;item&gt;
    &lt;name&gt;Stiker Waifu Zero Two&lt;/name&gt;
    &lt;price&gt;35000&lt;/price&gt;
  &lt;/item&gt;
&lt;/wishlist&gt;</pre>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">FILE WISHLIST (.xml)</label>
      <input type="file" name="wishlist_xml" class="form-control" accept=".xml,text/xml">
    </div>
    <button type="submit" class="btn btn-nhsec"><i class="bi bi-upload"></i> Import Wishlist</button>
  </form>
</div>

<?php if (!empty($parsed_items)): ?>
<div class="card card-nhsec p-4">
  <h5 class="fw-bold mb-3"><i class="bi bi-list-check"></i> Item Ditemukan</h5>
  <div class="table-responsive">
    <table class="table table-nhsec">
      <thead><tr><th>Nama Produk</th><th>Budget (Rp)</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach($parsed_items as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['name']) ?></td>
        <td class="product-price"><?= formatRupiah($item['price']) ?></td>
        <td>
          <a href="/public/search.php?q=<?= urlencode($item['name']) ?>" class="btn btn-nhsec-outline btn-sm">
            <i class="bi bi-search"></i> Cari
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
