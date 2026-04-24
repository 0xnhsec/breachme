<?php
$pageTitle = 'Import Produk';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = $success = '';
$imported = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [VULN: SSRF] — URL gambar produk di-fetch tanpa validasi host intentional
    $image_url = trim($_POST['image_url'] ?? '');

    if (!empty($image_url)) {
        // SSRF: tidak ada whitelist domain, attacker bisa fetch internal URL
        // misal: http://127.0.0.1:8081 (phpMyAdmin), http://metadata.internal
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $image_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'breachme-importer/1.0');
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($response !== false && $http_code === 200) {
            // Hanya simpan jika image content-type (tapi SSRF tetap terjadi sebelum cek ini)
            if (strpos($content_type, 'image/') !== false) {
                $ext = 'jpg';
                if (strpos($content_type, 'png') !== false) $ext = 'png';
                if (strpos($content_type, 'gif') !== false) $ext = 'gif';
                if (strpos($content_type, 'webp') !== false) $ext = 'webp';

                $filename = 'import_' . uniqid() . '.' . $ext;
                $upload_dir = '/var/www/html/uploads/products/';
                file_put_contents($upload_dir . $filename, $response);
                $success = "Gambar berhasil diimport: $filename";
                $_SESSION['imported_image'] = $filename;
            } else {
                // [VULN: SSRF] — response dari internal service bisa dibaca di sini
                // Untuk debug (intentional — attacker bisa lihat response internal)
                $error = "URL bukan gambar. Content-Type: " . htmlspecialchars($content_type);
                // Jika mau full SSRF, response internal ditampilkan:
                // $error .= "<br><pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            }
        } else {
            $error = "Gagal mengambil gambar dari URL tersebut. HTTP: $http_code";
        }
    } else {
        $error = 'Masukkan URL gambar.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-cloud-download" style="color:var(--nh-primary);font-size:1.4rem;"></i>
  <h2 class="mb-0" style="font-weight:800;">Import Gambar Produk dari URL</h2>
</div>

<?php if($error):?>
<div class="alert mb-3" style="background:rgba(255,59,59,.06);border:1px solid rgba(255,59,59,.2);color:var(--nh-danger);">
  <i class="bi bi-exclamation-triangle me-2"></i><?=$error?>
</div>
<?php endif;?>
<?php if($success):?>
<div class="alert mb-3" style="background:rgba(0,255,136,.06);border:1px solid rgba(0,255,136,.2);color:var(--nh-green);">
  <i class="bi bi-check-circle me-2"></i><?=$success?>
</div>
<?php endif;?>

<div class="card card-nhsec p-4">
  <p style="font-size:.85rem;color:#666;margin-bottom:1.5rem;">
    Masukkan URL gambar produk dari internet untuk diimport langsung ke server.
    Format: JPG, PNG, GIF, WEBP.
  </p>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">URL GAMBAR PRODUK</label>
      <input type="text" name="image_url" class="form-control"
             placeholder="https://example.com/gambar-produk.jpg"
             value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>">
      <div style="font-size:.72rem;color:#444;margin-top:4px;">
        Gambar akan diunduh dan disimpan di server.
      </div>
    </div>
    <button type="submit" class="btn btn-nhsec"><i class="bi bi-download"></i> Import Gambar</button>
    <a href="/public/sell.php" class="btn btn-nhsec-outline ms-2">Kembali ke Jual</a>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
