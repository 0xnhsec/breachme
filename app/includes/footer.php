    </main>

    <!-- Footer -->
    <footer class="footer-nhsec">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:0.95rem;color:var(--nh-text);margin-bottom:12px;">
                        <i class="bi bi-shop" style="color:var(--nh-primary);"></i> nhsec
                    </div>
                    <p style="color:#444;font-size:0.78rem;line-height:1.6;">
                        Marketplace jual beli laptop gaming & stiker waifu.
                        Temukan produk terbaik dari seller terpercaya.
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;">Navigasi</div>
                    <ul class="list-unstyled" style="font-size:0.8rem;">
                        <li class="mb-1"><a href="/public/index.php" style="color:#555;text-decoration:none;">Beranda</a></li>
                        <li class="mb-1"><a href="/public/index.php#products" style="color:#555;text-decoration:none;">Produk</a></li>
                        <?php if (isLoggedIn()): ?>
                        <li class="mb-1"><a href="/public/sell.php" style="color:#555;text-decoration:none;">Jual Produk</a></li>
                        <li class="mb-1"><a href="/public/wallet.php" style="color:#555;text-decoration:none;">E-Wallet</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <div style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;">Bantuan</div>
                    <ul class="list-unstyled" style="font-size:0.8rem;">
                        <li class="mb-1"><span style="color:#555;">Syarat & Ketentuan</span></li>
                        <li class="mb-1"><span style="color:#555;">Kebijakan Privasi</span></li>
                        <li class="mb-1"><span style="color:#555;">Pusat Bantuan</span></li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="text-center mb-0" style="color:#333;font-size:0.7rem;font-family:'JetBrains Mono',monospace;letter-spacing:0.5px;">
                &copy; 2026 nhsec Marketplace &mdash; educational use only
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
