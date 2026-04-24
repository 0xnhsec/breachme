<?php
http_response_code(500);
// Generate random error ID — tidak expose stack trace
$error_id = 'ERR-' . substr(md5(uniqid('', true)), 0, 5);
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>breachme — 500 Internal Server Error</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{font-family:'Inter',sans-serif;margin:0;padding:0;box-sizing:border-box;}
body{background:#050505;color:#e0e0e0;min-height:100vh;display:flex;flex-direction:column;}
.navbar{background:rgba(5,5,5,.8);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.05);height:56px;display:flex;align-items:center;padding:0 2rem;}
.brand{font-family:'JetBrains Mono',monospace;font-weight:700;font-size:1.05rem;color:#e0e0e0;text-decoration:none;display:flex;align-items:center;gap:6px;}
.brand i{color:#00d4ff;}
.main{flex:1;display:flex;align-items:center;justify-content:center;padding:4rem 2rem;}
.error-box{text-align:center;max-width:480px;}
.error-code{font-family:'JetBrains Mono',monospace;font-size:6rem;font-weight:700;line-height:1;background:linear-gradient(135deg,#ffb800,#ff6b2b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.error-icon{font-size:3rem;color:rgba(255,184,0,.3);margin:1rem 0;}
.error-title{font-size:1.3rem;font-weight:700;margin:.5rem 0;color:#e0e0e0;}
.error-msg{font-size:.87rem;color:#666;line-height:1.6;margin-bottom:1.5rem;}
.error-id{display:inline-block;font-family:'JetBrains Mono',monospace;font-size:.72rem;color:#555;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:4px 12px;margin-bottom:1.5rem;}
.btn-home{display:inline-flex;align-items:center;gap:8px;background:#00d4ff;color:#050505;padding:10px 24px;border-radius:6px;font-weight:700;font-size:.85rem;text-decoration:none;transition:opacity .15s;}
.btn-home:hover{opacity:.85;color:#050505;}
.footer{border-top:1px solid rgba(255,255,255,.05);padding:1.5rem;text-align:center;font-family:'JetBrains Mono',monospace;font-size:.65rem;color:#333;}
</style>
</head>
<body>
<nav class="navbar">
  <a class="brand" href="/public/index.php"><i class="bi bi-shop"></i> breachme</a>
</nav>
<main class="main">
  <div class="error-box">
    <div class="error-code">500</div>
    <div class="error-icon"><i class="bi bi-exclamation-triangle"></i></div>
    <h1 class="error-title">Terjadi Kesalahan Server</h1>
    <p class="error-msg">
      Server mengalami masalah internal dan tidak dapat menyelesaikan permintaanmu.
      Tim kami telah diberitahu. Silakan coba lagi beberapa saat.
    </p>
    <div class="error-id"><?= $error_id ?></div><br>
    <a href="/public/index.php" class="btn-home"><i class="bi bi-house"></i> Kembali ke Beranda</a>
  </div>
</main>
<footer class="footer">breachme marketplace &nbsp;·&nbsp; ERROR 500</footer>
</body>
</html>
