<?php
/**
 * ELECTROMART - Halaman Registrasi
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Sudah login → redirect
if (isLoggedIn()) {
    redirect(isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/user/dashboard.php');
}

$errors = [];
$data   = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']  = trim($_POST['name']  ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['phone'] = trim($_POST['phone'] ?? '');
    $password      = trim($_POST['password']         ?? '');
    $confirm       = trim($_POST['confirm_password'] ?? '');
    $agree         = isset($_POST['agree']);

    // Validasi
    if (empty($data['name']))  $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($data['email'])) $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (empty($password)) $errors[] = 'Password wajib diisi.';
    elseif (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';
    if (!$agree) $errors[] = 'Anda harus menyetujui syarat dan ketentuan.';

    if (empty($errors)) {
        // Cek email sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $data['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
            $stmt->close();
        } else {
            $stmt->close();

            // Simpan user
            $hash = md5($password);
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'user', ?)"
            );
            $stmt->bind_param('ssss', $data['name'], $data['email'], $hash, $data['phone']);

            if ($stmt->execute()) {
                $stmt->close();
                setAlert('success', 'Pendaftaran berhasil! Silakan login dengan akun Anda.');
                redirect(BASE_URL . '/auth/login.php');
            } else {
                $errors[] = 'Terjadi kesalahan server. Silakan coba lagi.';
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buat akun ELECTROMART baru dan mulai belanja elektronik.">
    <title>Daftar | ELECTROMART</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:480px">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="auth-logo">
            <div class="brand-icon">⚡</div>
            ELECTRO<span style="color:var(--primary)">MART</span>
        </a>

        <h1 class="auth-title">Buat Akun Baru</h1>
        <p class="auth-subtitle">Bergabung dan mulai belanja elektronik terjangkau</p>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">✕</span>
            <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>

            <!-- Nama -->
            <div class="form-group">
                <label class="form-label required" for="name">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="form-control"
                       placeholder="John Doe"
                       value="<?= sanitize($data['name']) ?>" required autocomplete="name">
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label required" for="email">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control"
                       placeholder="john@kampus.ac.id"
                       value="<?= sanitize($data['email']) ?>" required autocomplete="email">
            </div>

            <!-- Telepon -->
            <div class="form-group">
                <label class="form-label" for="phone">Nomor Telepon</label>
                <input type="tel" name="phone" id="phone" class="form-control"
                       placeholder="08xxxxxxxxxx"
                       value="<?= sanitize($data['phone']) ?>" autocomplete="tel">
                <div class="form-hint">Opsional — digunakan untuk konfirmasi pesanan</div>
            </div>

            <!-- Password -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required" for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Min. 6 karakter" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label required" for="confirm_password">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                           placeholder="Ulangi password" required autocomplete="new-password">
                </div>
            </div>

            <!-- Agree -->
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="agree" id="agree" required>
                    <span>Saya menyetujui <a href="#" style="color:var(--primary)">syarat & ketentuan</a> ELECTROMART</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="registerBtn">
                🚀 Buat Akun
            </button>
        </form>

        <p style="text-align:center;margin-top:24px;font-size:14px;color:var(--gray-500)">
            Sudah punya akun?
            <a href="<?= BASE_URL ?>/auth/login.php" style="font-weight:600">Masuk di sini →</a>
        </p>
    </div>
</div>
</body>
</html>
