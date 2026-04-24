<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($username) < 3) {
        $error = 'Username min 3 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error = 'Password min 4 characters.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Username already taken.';
        } else {
            // Use bcrypt for new users (secure hashing)
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, balance) VALUES (?, ?, ?, 'user', 5000000.00)");
            $stmt->bind_param("sss", $username, $email, $hashed);
            $stmt->execute();

            $new_user_id = $conn->insert_id;
            // Welcome notification
            createNotification($new_user_id, 'info', 'Selamat datang di nhsec Marketplace! Saldo awal Rp 5.000.000 sudah ditambahkan.', '/public/wallet.php');

            $success = 'Account created with Rp 5.000.000 balance. Sign in now.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-4">
        <div class="animate-in">
            <div class="mb-4">
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">REGISTRATION</span>
                <h3 class="mt-2" style="font-weight: 700; font-size: 1.5rem; letter-spacing: -0.5px;">Create account</h3>
            </div>

            <?php if ($error): ?>
            <div class="alert" style="background:rgba(255,59,59,0.06);border:1px solid rgba(255,59,59,0.15);color:var(--nh-danger);font-size:0.82rem;"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert" style="background:rgba(0,255,136,0.06);border:1px solid rgba(0,255,136,0.15);color:var(--nh-green);font-size:0.82rem;"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" id="register-form">
                <div class="mb-3">
                    <label class="form-label">USERNAME</label>
                    <input type="text" name="username" id="reg-username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">EMAIL</label>
                    <input type="email" name="email" id="reg-email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">PASSWORD</label>
                    <input type="password" name="password" id="reg-password" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">CONFIRM PASSWORD</label>
                    <input type="password" name="confirm_password" id="reg-confirm" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-nhsec w-100 mb-3" id="register-submit">
                    Create account <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p style="color:#444;font-size:0.8rem;text-align:center;">
                Already have an account? <a href="/public/login.php">Sign in</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
