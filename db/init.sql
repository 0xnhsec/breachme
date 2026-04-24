-- nhsec Vulnerable Lab - Database Initialization
-- WARNING: This database contains intentionally weak security for educational purposes
-- MODEL: Marketplace platform (Tokopedia-style)

USE nhsec;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,  -- [VULN: WEAK_HASH] admin uses MD5, users use bcrypt
    role ENUM('user', 'admin') DEFAULT 'user',
    balance DECIMAL(15,2) DEFAULT 5000000.00,
    ip_address VARCHAR(45) DEFAULT NULL,
    is_blocked TINYINT(1) DEFAULT 0,
    display_name VARCHAR(50) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: products (marketplace — seller_id = user)
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255) DEFAULT 'default.jpg',
    category ENUM('laptop', 'sticker') NOT NULL,
    status ENUM('active', 'pending', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: reviews
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,  -- [VULN: XSS] - not sanitized intentional
    rating INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: cart
-- ============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,  -- [VULN: BUSINESS-LOGIC] - no negative check intentional
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: orders
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    voucher_code VARCHAR(50) DEFAULT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: order_items
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: vouchers
-- ============================================
CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percent INT NOT NULL,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: request_logs (SIEM)
-- ============================================
CREATE TABLE IF NOT EXISTS request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45),
    method VARCHAR(10),
    endpoint VARCHAR(500),
    params TEXT,
    body TEXT,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp),
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: alerts (SIEM)
-- ============================================
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id INT NOT NULL,
    rule_name VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT,
    is_true_positive TINYINT(1) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (log_id) REFERENCES request_logs(id) ON DELETE CASCADE,
    INDEX idx_rule (rule_name),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: voucher_usage
-- ============================================
CREATE TABLE IF NOT EXISTS voucher_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    voucher_code VARCHAR(50) NOT NULL,
    order_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: wallets (e-wallet transaction history)
-- ============================================
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_wallet (user_id),
    INDEX idx_created_wallet (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: invoices (transaction receipts)
-- ============================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    amount DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    status ENUM('paid', 'pending', 'cancelled') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_invoice_num (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: notifications
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notif (user_id),
    INDEX idx_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: blocked_ips
-- ============================================
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    blocked_by INT NOT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unblocked_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- Users
-- [VULN: WEAK_HASH] ALL seed passwords stored as MD5 intentional
-- New users registering via app will get bcrypt via password_hash()
INSERT INTO users (username, email, password, role, balance, ip_address) VALUES
('admin', 'admin@nhsec.local', MD5('admin123'), 'admin', 10000000.00, '192.168.1.1'),
('budi', 'budi@nhsec.local', MD5('budi123'), 'user', 5000000.00, '192.168.1.50'),
('sari', 'sari@nhsec.local', MD5('sari123'), 'user', 5000000.00, '10.0.0.23'),
('noshiro', 'noshiro@nhsec.local', MD5('noshiro123'), 'user', 5000000.00, '203.0.113.45'),
('eka', 'eka@nhsec.local', MD5('eka123'), 'user', 5000000.00, '172.16.0.10'),
('dimas', 'dimas@nhsec.local', MD5('dimas123'), 'user', 5000000.00, '10.10.10.5');

-- Products: Gaming Laptops (seller: budi, id=2)
INSERT INTO products (seller_id, name, description, price, stock, image, category) VALUES
(2, 'ASUS ROG Strix G16', 'Laptop gaming ASUS ROG Strix G16 dengan Intel Core i9-13980HX, RTX 4070, RAM 16GB DDR5, SSD 1TB. Keyboard RGB per-key, layar 165Hz QHD. Cocok untuk gaming AAA dan content creation.', 28999000.00, 15, 'rog_strix.jpg', 'laptop'),
(2, 'MSI Raider GE78 HX', 'MSI Raider GE78 HX 13VH - Intel i9-13950HX, NVIDIA RTX 4080, 32GB RAM, 2TB SSD. Layar 17.3" UHD+ 120Hz Mini LED. Desain premium dengan Mystic Light RGB.', 45999000.00, 8, 'msi_raider.jpg', 'laptop'),
(4, 'Lenovo Legion Pro 7i', 'Lenovo Legion Pro 7i Gen 8 - i9-13900HX, RTX 4080, 32GB DDR5, 1TB SSD. Layar 16" WQXGA 240Hz, Tobii eye-tracking, ColdFront 5.0 thermal system.', 39999000.00, 10, 'legion_pro.jpg', 'laptop'),
(5, 'Acer Predator Helios 18', 'Acer Predator Helios 18 - Intel i9-13900HX, RTX 4080, 32GB RAM, 2TB SSD RAID 0. Layar 18" WQXGA 250Hz, 3D AeroBlade Fan, PredatorSense.', 42500000.00, 6, 'predator_helios.jpg', 'laptop'),
(6, 'HP OMEN 17', 'HP OMEN 17 - AMD Ryzen 9 7945HX, RTX 4070, 16GB DDR5, 1TB SSD. Layar 17.3" QHD 165Hz, OMEN Tempest Cooling, Bang & Olufsen audio.', 26999000.00, 12, 'hp_omen.jpg', 'laptop');

-- Products: Waifu Stickers (seller: sari, id=3; noshiro, id=4)
INSERT INTO products (seller_id, name, description, price, stock, image, category) VALUES
(3, 'Stiker Waifu Zero Two Pack', 'Paket 10 lembar stiker Zero Two (Darling in the Franxx). Bahan vinyl waterproof anti luntur. Ukuran 5-8cm. Cocok untuk laptop, botol minum, helm.', 35000.00, 200, 'zero_two.jpg', 'sticker'),
(3, 'Stiker Waifu Rem & Ram Set', 'Set stiker Rem dan Ram (Re:Zero) isi 8 lembar. Desain chibi kawaii, bahan glossy waterproof. Ukuran 4-7cm.', 30000.00, 150, 'rem_ram.jpg', 'sticker'),
(4, 'Stiker Waifu Makima Collection', 'Koleksi stiker Makima (Chainsaw Man) isi 12 lembar. Mixed design: cool, chibi, dan action pose. Vinyl premium tahan air.', 40000.00, 180, 'makima.jpg', 'sticker'),
(3, 'Stiker Waifu Yor Forger Bundle', 'Bundle stiker Yor Forger (Spy x Family) isi 15 lembar. Termasuk Thorn Princess ver. Bahan matte finish premium.', 45000.00, 120, 'yor_forger.jpg', 'sticker'),
(4, 'Stiker Waifu Mixed Anime Pack', 'Mega pack 25 lembar stiker berbagai waifu populer: Nezuko, Marin, Chika, Miku, dll. Bahan vinyl waterproof campur glossy & matte.', 55000.00, 300, 'mixed_waifu.jpg', 'sticker');

-- Voucher
INSERT INTO vouchers (code, discount_percent, active) VALUES
('DISKON10', 10, 1),
('GAMER20', 20, 1),
('WAIFU50', 50, 0);

-- Sample completed transactions (orders)
INSERT INTO orders (user_id, total, voucher_code, status) VALUES
(3, 28999000.00, NULL, 'delivered'),
(5, 67500.00, 'DISKON10', 'delivered'),
(4, 45999000.00, NULL, 'delivered'),
(6, 75000.00, NULL, 'processing');

-- Order Items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 28999000.00),
(2, 6, 1, 35000.00),
(2, 7, 1, 30000.00),
(3, 2, 1, 45999000.00),
(4, 8, 1, 40000.00),
(4, 6, 1, 35000.00);

-- Invoices for completed transactions
INSERT INTO invoices (invoice_number, buyer_id, seller_id, product_id, quantity, amount, tax_amount, total, status) VALUES
('INV-20260420-00001', 3, 2, 1, 1, 28999000.00, 579980.00, 29578980.00, 'paid'),
('INV-20260420-00002', 5, 3, 6, 1, 35000.00, 700.00, 35700.00, 'paid'),
('INV-20260420-00003', 5, 3, 7, 1, 30000.00, 600.00, 30600.00, 'paid'),
('INV-20260421-00004', 4, 2, 2, 1, 45999000.00, 919980.00, 46918980.00, 'paid');

-- Wallet entries for completed transactions
-- sari (id=3) bought laptop from budi (id=2) — Rp 28,999,000
INSERT INTO wallets (user_id, type, amount, balance_after, description, reference_id) VALUES
(3, 'debit', 29578980.00, -24578980.00, 'Pembelian: ASUS ROG Strix G16 + pajak 2%', 1),
(2, 'credit', 28999000.00, 33999000.00, 'Penjualan: ASUS ROG Strix G16', 1),
(1, 'credit', 579980.00, 10579980.00, 'Pajak platform 2%: INV-20260420-00001', 1);

-- eka (id=5) bought stickers from sari (id=3) — 2 items
INSERT INTO wallets (user_id, type, amount, balance_after, description, reference_id) VALUES
(5, 'debit', 35700.00, 4964300.00, 'Pembelian: Stiker Waifu Zero Two Pack + pajak 2%', 2),
(3, 'credit', 35000.00, -24543980.00, 'Penjualan: Stiker Waifu Zero Two Pack', 2),
(1, 'credit', 700.00, 10580680.00, 'Pajak platform 2%: INV-20260420-00002', 2),
(5, 'debit', 30600.00, 4933700.00, 'Pembelian: Stiker Waifu Rem & Ram Set + pajak 2%', 3),
(3, 'credit', 30000.00, -24513980.00, 'Penjualan: Stiker Waifu Rem & Ram Set', 3),
(1, 'credit', 600.00, 10581280.00, 'Pajak platform 2%: INV-20260420-00003', 3);

-- noshiro (id=4) bought laptop from budi (id=2) — Rp 45,999,000
INSERT INTO wallets (user_id, type, amount, balance_after, description, reference_id) VALUES
(4, 'debit', 46918980.00, -41918980.00, 'Pembelian: MSI Raider GE78 HX + pajak 2%', 4),
(2, 'credit', 45999000.00, 79998000.00, 'Penjualan: MSI Raider GE78 HX', 4),
(1, 'credit', 919980.00, 11501260.00, 'Pajak platform 2%: INV-20260421-00004', 4);

-- Update user balances to reflect transactions
UPDATE users SET balance = 11501260.00 WHERE id = 1;   -- admin (tax income)
UPDATE users SET balance = 79998000.00 WHERE id = 2;   -- budi (sold 2 laptops)
UPDATE users SET balance = -24513980.00 WHERE id = 3;  -- sari (bought laptop, sold stickers)
UPDATE users SET balance = -41918980.00 WHERE id = 4;  -- noshiro (bought laptop)
UPDATE users SET balance = 4933700.00 WHERE id = 5;    -- eka (bought stickers)

-- Notifications
INSERT INTO notifications (user_id, type, message, is_read, link) VALUES
(2, 'sale', 'Produk kamu "ASUS ROG Strix G16" terjual ke sari!', 1, '/public/invoice.php?id=1'),
(3, 'purchase', 'Pembelian ASUS ROG Strix G16 berhasil!', 1, '/public/invoice.php?id=1'),
(2, 'sale', 'Produk kamu "MSI Raider GE78 HX" terjual ke noshiro!', 0, '/public/invoice.php?id=4'),
(4, 'purchase', 'Pembelian MSI Raider GE78 HX berhasil!', 0, '/public/invoice.php?id=4'),
(3, 'sale', 'Produk kamu "Stiker Waifu Zero Two Pack" terjual ke eka!', 0, '/public/invoice.php?id=2'),
(3, 'sale', 'Produk kamu "Stiker Waifu Rem & Ram Set" terjual ke eka!', 0, '/public/invoice.php?id=3'),
(5, 'purchase', 'Pembelian Stiker Waifu Zero Two Pack berhasil!', 0, '/public/invoice.php?id=2'),
(5, 'purchase', 'Pembelian Stiker Waifu Rem & Ram Set berhasil!', 0, '/public/invoice.php?id=3'),
(6, 'info', 'Selamat datang di nhsec Marketplace! Saldo awal Rp 5.000.000 sudah ditambahkan.', 0, '/public/wallet.php');

-- Sample Reviews
INSERT INTO reviews (product_id, user_id, comment, rating) VALUES
(1, 3, 'Laptop mantap! ROG emang ga ada lawan. FPS stabil 144 di Valorant.', 5),
(1, 4, 'Build quality bagus, tapi agak berat buat dibawa kemana-mana.', 4),
(2, 4, 'MSI Raider ini beast banget. Rendering video 4K lancar jaya!', 5),
(6, 5, 'Stikernya lucu banget! Zero Two best waifu ❤️', 5),
(6, 6, 'Kualitas cetaknya bagus, warnanya cerah. Recommended!', 4),
(8, 5, 'Makima-sama! Desainnya keren semua, bahan stikernya tebal.', 5);

-- Voucher usage tracking
INSERT INTO voucher_usage (user_id, voucher_code, order_id) VALUES
(5, 'DISKON10', 2);

-- Sample request logs with different IPs
INSERT INTO request_logs (ip, method, endpoint, params, body, user_id, session_id, user_agent) VALUES
('192.168.1.1', 'GET', '/admin/', '{}', '{}', 1, 'sess_admin_001', 'Mozilla/5.0 Admin Browser'),
('192.168.1.50', 'GET', '/public/index.php', '{}', '{}', 2, 'sess_budi_001', 'Mozilla/5.0 Chrome/120'),
('10.0.0.23', 'POST', '/public/product.php?id=1', '{"id":"1"}', '{"comment":"test","rating":"5"}', 3, 'sess_sari_001', 'Mozilla/5.0 Firefox/121'),
('203.0.113.45', 'GET', '/public/order.php?id=1', '{"id":"1"}', '{}', 4, 'sess_noshiro_001', 'Mozilla/5.0 Chrome/120'),
('172.16.0.10', 'GET', '/public/index.php', '{}', '{}', 5, 'sess_eka_001', 'Mozilla/5.0 Safari/17'),
('10.10.10.5', 'GET', '/public/index.php', '{}', '{}', 6, 'sess_dimas_001', 'Mozilla/5.0 Edge/120');
