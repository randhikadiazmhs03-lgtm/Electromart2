<?php
/**
 * ELECTROMART - Profil User
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (isAdmin()) redirect(BASE_URL . '/admin/dashboard.php');

$uid    = (int)$_SESSION['user']['id'];
$errors = [];
$tab    = $_GET['tab'] ?? 'profile';

/* ── Fetch fresh user data ──────────────────────────── */
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

/* ── Handle POST ────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name    = trim($_POST['name']    ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) $errors[] = 'Nama lengkap wajib diisi.';

        if (empty($errors)) {
            $s = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $s->bind_param('sssi', $name, $phone, $address, $uid);
            $s->execute(); $s->close();

            // Update session
            $_SESSION['user']['name']    = $name;
            $_SESSION['user']['phone']   = $phone;
            $_SESSION['user']['address'] = $address;

            setAlert('success', 'Profil berhasil diperbarui!');
            redirect(BASE_URL . '/user/profile.php?tab=profile');
        }
    }

    elseif ($action === 'change_password') {
        $current = trim($_POST['current_password'] ?? '');
        $new     = trim($_POST['new_password']     ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (empty($current)) $errors[] = 'Password saat ini wajib diisi.';
        if (empty($new))     $errors[] = 'Password baru wajib diisi.';
        elseif (strlen($new) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        if ($new !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';

        if (empty($errors)) {
            if ($user['password'] !== md5($current)) {
                $errors[] = 'Password saat ini salah.';
            } else {
                $hash = md5($new);
                $s = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $s->bind_param('si', $hash, $uid); $s->execute(); $s->close();
                setAlert('success', 'Password berhasil diubah!');
                redirect(BASE_URL . '/user/profile.php?tab=password');
            }
        }
        $tab = 'password';
    }

    // Refresh user data after update
    $user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
}

$page_title = 'Profil Saya';
include __DIR__ . '/../includes/header.php';
?>

<?php showAlert(); ?>

<div style="max-width:720px;margin:0 auto">

    <div class="d-flex align-center justify-between" style="margin-bottom:28px">
        <div>
            <h1 style="font-size:24px;font-weight:800;margin-bottom:4px">👤 Profil Saya</h1>
            <p class="text-muted text-sm">Kelola informasi akun ELECTROMART Anda</p>
        </div>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-ghost btn-sm">🏠 Beranda</a>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:4px;border-bottom:2px solid var(--gray-200);margin-bottom:28px">
        <a href="?tab=profile"
           style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;
                  <?= $tab === 'profile' ? 'border-color:var(--primary);color:var(--primary)' : 'color:var(--gray-500)' ?>">
            📋 Data Profil
        </a>
        <a href="?tab=password"
           style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;
                  <?= $tab === 'password' ? 'border-color:var(--primary);color:var(--primary)' : 'color:var(--gray-500)' ?>">
            🔒 Ubah Password
        </a>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">✕</span>
        <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
    </div>
    <?php endif; ?>

    <?php if ($tab === 'profile'): ?>
    <!-- PROFILE TAB -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Informasi Pribadi</h3>
        </div>
        <div class="card-body">
            <!-- Avatar -->
            <div style="display:flex;align-items:center;gap:20px;margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid var(--gray-100)">
                <div style="width:72px;height:72px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:white;flex-shrink:0">
                    <?= strtoupper(mb_substr($user['name'],0,2)) ?>
                </div>
                <div>
                    <div style="font-size:20px;font-weight:800"><?= sanitize($user['name']) ?></div>
                    <div style="font-size:14px;color:var(--gray-500)"><?= sanitize($user['email']) ?></div>
                    <div style="margin-top:6px"><span class="badge badge-primary">👤 User</span></div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label class="form-label required" for="name">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="<?= sanitize($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email_disp">Email</label>
                    <input type="email" id="email_disp" class="form-control"
                           value="<?= sanitize($user['email']) ?>" disabled>
                    <div class="form-hint">Email tidak dapat diubah.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Nomor Telepon</label>
                    <input type="tel" name="phone" id="phone" class="form-control"
                           placeholder="08xxxxxxxxxx"
                           value="<?= sanitize($user['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Alamat Pengiriman Default</label>
                    <textarea name="address" id="address" class="form-control" rows="3"
                              placeholder="Masukkan alamat lengkap Anda..."><?= sanitize($user['address'] ?? '') ?></textarea>
                    <div class="form-hint">Alamat ini akan digunakan secara otomatis saat checkout.</div>
                </div>

                <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Account Info -->
    <div class="card" style="margin-top:20px">
        <div class="card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
            <div style="text-align:center;padding:16px;background:var(--gray-50);border-radius:10px">
                <div style="font-size:22px;font-weight:800;color:var(--primary)">
                    <?= $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid")->fetch_row()[0] ?>
                </div>
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">Total Pesanan</div>
            </div>
            <div style="text-align:center;padding:16px;background:var(--gray-50);border-radius:10px">
                <div style="font-size:22px;font-weight:800;color:var(--success)">
                    <?= $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid AND status = 'delivered'")->fetch_row()[0] ?>
                </div>
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">Pesanan Selesai</div>
            </div>
            <div style="text-align:center;padding:16px;background:var(--gray-50);border-radius:10px">
                <div style="font-size:12px;font-weight:700;color:var(--gray-700)">
                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                </div>
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">Bergabung Sejak</div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- PASSWORD TAB -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">🔒 Ubah Password</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label required" for="current_password">Password Saat Ini</label>
                    <input type="password" name="current_password" id="current_password"
                           class="form-control" placeholder="Masukkan password saat ini" required>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="new_password">Password Baru</label>
                    <input type="password" name="new_password" id="new_password"
                           class="form-control" placeholder="Min. 6 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password"
                           class="form-control" placeholder="Ulangi password baru" required>
                </div>

                <div class="alert alert-info" style="margin-bottom:20px">
                    <span class="alert-icon">ℹ</span>
                    <span>Password baru minimal 6 karakter. Gunakan kombinasi huruf dan angka untuk keamanan.</span>
                </div>

                <button type="submit" class="btn btn-primary" id="changePassBtn">
                    🔒 Ubah Password
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top:24px;text-align:center">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm" style="color:var(--danger)">
            🚪 Logout dari Akun
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
