<?php
// nhsec - Shared Functions
// Includes authentication, session management, request logging, alert detection,
// wallet, invoice, and notification helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ============================================
// AUTH FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit;
    }
}

// [VULN: BAC] - intentional: this function only checks login, not admin role
function requireLoginOnly() {
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit;
    }
}

// [VULN: BAC] - Middleware checks session role but can be bypassed
// by direct URL access to endpoints that forget to call this,
// or by session manipulation
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit;
    }
    // [VULN: BAC] - Only checks session variable, which could be manipulated
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /public/index.php');
        exit;
    }
}

function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    $stmt = $conn->prepare("SELECT id, username, email, role, balance, display_name, bio, avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function avatarUrl($user) {
    if (!empty($user['avatar'])) {
        $path = __DIR__ . '/../uploads/avatars/' . $user['avatar'];
        if (file_exists($path)) return '/uploads/avatars/' . $user['avatar'];
    }
    return null; // null = render initials
}

function displayName($user) {
    return !empty($user['display_name']) ? $user['display_name'] : ($user['username'] ?? 'User');
}

function getCartCount() {
    global $conn;
    if (!isLoggedIn()) return 0;
    $uid = $_SESSION['user_id'];
    $result = $conn->query("SELECT SUM(quantity) as cnt FROM cart WHERE user_id = $uid");
    $row = $result->fetch_assoc();
    return $row['cnt'] ?? 0;
}

// ============================================
// WALLET FUNCTIONS
// ============================================

function getBalance($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (float)$row['balance'] : 0;
}

function createWalletEntry($user_id, $type, $amount, $description, $reference_id = null) {
    global $conn;
    $balance = getBalance($user_id);
    if ($type === 'debit') {
        $balance -= $amount;
    } else {
        $balance += $amount;
    }
    // Update user balance
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param("di", $balance, $user_id);
    $stmt->execute();
    // Insert wallet record
    $stmt = $conn->prepare("INSERT INTO wallets (user_id, type, amount, balance_after, description, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isddsi", $user_id, $type, $amount, $balance, $description, $reference_id);
    $stmt->execute();
    return $balance;
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

function getUnreadNotifCount($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['cnt'];
}

function createNotification($user_id, $type, $message, $link = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $type, $message, $link);
    $stmt->execute();
}

// ============================================
// INVOICE FUNCTIONS
// ============================================

function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function createInvoice($buyer_id, $seller_id, $product_id, $quantity, $amount) {
    global $conn;
    $tax = $amount * 0.02; // 2% platform tax
    $total = $amount + $tax;
    $inv_num = generateInvoiceNumber();
    $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, buyer_id, seller_id, product_id, quantity, amount, tax_amount, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiiddd", $inv_num, $buyer_id, $seller_id, $product_id, $quantity, $amount, $tax, $total);
    $stmt->execute();
    return ['id' => $conn->insert_id, 'invoice_number' => $inv_num, 'tax' => $tax, 'total' => $total];
}

// ============================================
// TRANSACTION PROCESSING
// ============================================

function processTransaction($buyer_id, $seller_id, $product_id, $quantity, $price) {
    global $conn;
    $amount = $price * $quantity;
    $tax = $amount * 0.02;
    $total_debit = $amount + $tax;

    // Create invoice
    $invoice = createInvoice($buyer_id, $seller_id, $product_id, $quantity, $amount);
    $inv_id = $invoice['id'];

    // Debit buyer (amount + tax)
    createWalletEntry($buyer_id, 'debit', $total_debit, "Pembelian produk + pajak 2%", $inv_id);

    // Credit seller (amount only, no tax)
    createWalletEntry($seller_id, 'credit', $amount, "Penjualan produk", $inv_id);

    // Credit admin/platform (tax) — admin is always user_id=1
    createWalletEntry(1, 'credit', $tax, "Pajak platform 2%: " . $invoice['invoice_number'], $inv_id);

    // Get product name for notifications
    $pstmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $pstmt->bind_param("i", $product_id);
    $pstmt->execute();
    $product_name = $pstmt->get_result()->fetch_assoc()['name'];

    // Get buyer username
    $bstmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $bstmt->bind_param("i", $buyer_id);
    $bstmt->execute();
    $buyer_name = $bstmt->get_result()->fetch_assoc()['username'];

    // Notifications
    createNotification($seller_id, 'sale', "Produk kamu \"$product_name\" terjual ke $buyer_name!", "/public/invoice.php?id=$inv_id");
    createNotification($buyer_id, 'purchase', "Pembelian $product_name berhasil!", "/public/invoice.php?id=$inv_id");

    // Update stock
    $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");

    return $invoice;
}

// ============================================
// REQUEST LOGGING (SIEM)
// ============================================

function logRequest() {
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $endpoint = $_SERVER['REQUEST_URI'] ?? '/';
    $params = json_encode($_GET);
    $body = json_encode($_POST);
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("INSERT INTO request_logs (ip, method, endpoint, params, body, user_id, session_id, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiss", $ip, $method, $endpoint, $params, $body, $user_id, $session_id, $user_agent);
    $stmt->execute();

    $log_id = $conn->insert_id;

    // Update user IP if logged in
    if ($user_id) {
        $conn->query("UPDATE users SET ip_address = '$ip' WHERE id = $user_id");
    }

    // Run alert detection
    detectAlerts($log_id, $endpoint, $params, $body, $user_id);

    return $log_id;
}

// ============================================
// ALERT DETECTION ENGINE
// ============================================

function detectAlerts($log_id, $endpoint, $params, $body, $user_id) {
    // Rule 1: XSS Detection
    detectXSS($log_id, $params, $body);

    // Rule 2: BAC Detection
    detectBAC($log_id, $endpoint, $user_id);

    // Rule 3: IDOR Detection
    detectIDOR($log_id, $endpoint, $user_id);

    // Rule 4: Business Logic - Voucher abuse
    detectVoucherAbuse($log_id, $user_id);
}

function detectXSS($log_id, $params, $body) {
    global $conn;
    $xss_patterns = ['<script', '</script', 'onerror=', 'onload=', 'javascript:', 'onclick=', 'onmouseover=', 'onfocus=', 'eval(', 'alert('];

    $all_input = strtolower($params . $body);

    foreach ($xss_patterns as $pattern) {
        if (strpos($all_input, $pattern) !== false) {
            $stmt = $conn->prepare("INSERT INTO alerts (log_id, rule_name, severity, description) VALUES (?, 'XSS', 'high', ?)");
            $desc = "XSS pattern detected: '$pattern' found in request parameters";
            $stmt->bind_param("is", $log_id, $desc);
            $stmt->execute();
            break;
        }
    }
}

function detectBAC($log_id, $endpoint, $user_id) {
    global $conn;
    if (strpos($endpoint, '/admin') !== false && $user_id) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $user['role'] !== 'admin') {
            $stmt2 = $conn->prepare("INSERT INTO alerts (log_id, rule_name, severity, description) VALUES (?, 'BAC', 'critical', ?)");
            $desc = "Non-admin user (ID: $user_id) attempted to access admin endpoint: $endpoint";
            $stmt2->bind_param("is", $log_id, $desc);
            $stmt2->execute();
        }
    }
}

function detectIDOR($log_id, $endpoint, $user_id) {
    global $conn;
    // Detect IDOR on orders
    if (strpos($endpoint, 'order.php') !== false && isset($_GET['id']) && $user_id) {
        $order_id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if ($order && $order['user_id'] != $user_id) {
            $stmt2 = $conn->prepare("INSERT INTO alerts (log_id, rule_name, severity, description) VALUES (?, 'IDOR', 'high', ?)");
            $desc = "User $user_id accessed order #$order_id belonging to user {$order['user_id']}";
            $stmt2->bind_param("is", $log_id, $desc);
            $stmt2->execute();
        }
    }
    // Detect IDOR on invoices
    if (strpos($endpoint, 'invoice.php') !== false && isset($_GET['id']) && $user_id) {
        $inv_id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT buyer_id, seller_id FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $inv_id);
        $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc();

        if ($inv && $inv['buyer_id'] != $user_id && $inv['seller_id'] != $user_id) {
            $stmt2 = $conn->prepare("INSERT INTO alerts (log_id, rule_name, severity, description) VALUES (?, 'IDOR', 'high', ?)");
            $desc = "User $user_id accessed invoice #$inv_id (buyer: {$inv['buyer_id']}, seller: {$inv['seller_id']})";
            $stmt2->bind_param("is", $log_id, $desc);
            $stmt2->execute();
        }
    }
}

function detectVoucherAbuse($log_id, $user_id) {
    global $conn;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voucher_code']) && $user_id) {
        $code = $_POST['voucher_code'];
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM voucher_usage WHERE user_id = ? AND voucher_code = ?");
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['cnt'] > 0) {
            $stmt2 = $conn->prepare("INSERT INTO alerts (log_id, rule_name, severity, description) VALUES (?, 'BUSINESS_LOGIC', 'medium', ?)");
            $desc = "User $user_id reused voucher '$code' (used {$result['cnt']} time(s) before)";
            $stmt2->bind_param("is", $log_id, $desc);
            $stmt2->execute();
        }
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning text-dark',
        'processing' => 'bg-info',
        'shipped' => 'bg-primary',
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    $class = $badges[$status] ?? 'bg-secondary';
    return "<span class='badge $class'>" . ucfirst($status) . "</span>";
}

// Log every request
logRequest();
