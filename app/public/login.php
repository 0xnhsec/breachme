<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Fetch user to check password format
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $authenticated = false;

        // [VULN: WEAK_HASH] - Admin uses MD5, check both formats intentional
        if (strlen($user['password']) === 32 && $user['password'] === md5($password)) {
            // MD5 match (legacy/admin)
            $authenticated = true;
        } elseif (password_verify($password, $user['password'])) {
            // bcrypt match (new users)
            $authenticated = true;
        }

        if ($authenticated) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: /admin/');
            } else {
                header('Location: /public/index.php');
            }
            exit;
        }
    }
    $error = 'Invalid credentials.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-4">
        <div class="animate-in">
            <div class="mb-4">
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:1.5px;">AUTHENTICATION</span>
                <h3 class="mt-2" style="font-weight: 700; font-size: 1.5rem; letter-spacing: -0.5px;">Sign in</h3>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger" style="background:var(--nh-danger-dim);border:1px solid rgba(255,59,59,0.2);color:var(--nh-danger);font-size:0.82rem;">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="login-form">
                <div class="mb-3">
                    <label class="form-label">USERNAME</label>
                    <input type="text" name="username" id="username" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">PASSWORD</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-nhsec w-100 mb-3" id="login-submit">
                    Sign in <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p style="color:#444;font-size:0.8rem;text-align:center;margin-bottom:1.5rem;">
                No account? <a href="/public/register.php">Register</a>
            </p>

            <div style="border-top:1px solid var(--nh-border);padding-top:1rem;">
                <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#333;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;">Demo Credentials</div>
                <div class="d-flex gap-2 flex-wrap">
                    <code style="font-size:0.7rem;">admin:admin123</code>
                    <code style="font-size:0.7rem;">budi:budi123</code>
                    <code style="font-size:0.7rem;">sari:sari123</code>
                    <code style="font-size:0.7rem;">noshiro:noshiro123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
