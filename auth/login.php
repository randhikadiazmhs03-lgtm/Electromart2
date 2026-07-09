<?php
/**
 * ELECTROMART - Halaman Login
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, redirect sesuai role
if (isLoggedIn()) {
    redirect(isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/user/dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi
    if (empty($email))    $errors[] = 'Email wajib diisi.';
    if (empty($password)) $errors[] = 'Password wajib diisi.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && $user['password'] === md5($password)) {
            // Login berhasil
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'phone' => $user['phone'],
                'address' => $user['address'],
            ];
            session_regenerate_id(true);
            setAlert('success', 'Selamat datang kembali, ' . $user['name'] . '!');
            redirect($user['role'] === 'admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/user/dashboard.php');
        } else {
            $errors[] = 'Email atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login ke akun ELECTROMART Anda.">
    <title>Login | ELECTROMART</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>var BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="auth-logo">
            <div class="brand-icon">⚡</div>
            ELECTRO<span style="color:var(--primary)">MART</span>
        </a>

        <h1 class="auth-title">Selamat Datang</h1>
        <p class="auth-subtitle">Masuk ke akun ELECTROMART Anda</p>

        <?php showAlert(); ?>

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">✕</span>
            <div>
                <?php foreach ($errors as $e): ?>
                <div><?= sanitize($e) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" id="loginForm" novalidate>

            <div class="form-group">
                <label class="form-label required" for="email">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    placeholder="contoh@email.com"
                    value="<?= sanitize($email) ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label class="form-label required" for="password">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn" style="margin-top:8px">
                🔐 Masuk
            </button>
        </form>

        <!-- Demo accounts -->
        <div style="margin-top:24px;padding:16px;background:var(--gray-50);border-radius:var(--radius-md);border:1px solid var(--gray-200)">
            <p style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">🔑 Akun Demo</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <button onclick="fillDemo('admin@electromart.com','admin123')" class="btn btn-secondary btn-sm" style="font-size:12px">
                    🔧 Admin
                </button>
                <button onclick="fillDemo('user@electromart.com','user123')" class="btn btn-secondary btn-sm" style="font-size:12px">
                    👤 User
                </button>
            </div>
        </div>

        <p style="text-align:center;margin-top:24px;font-size:14px;color:var(--gray-500)">
            Belum punya akun?
            <a href="<?= BASE_URL ?>/auth/register.php" style="font-weight:600">Daftar sekarang →</a>
        </p>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
function fillDemo(email, pass) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = pass;
}
</script>
</body>
</html>
