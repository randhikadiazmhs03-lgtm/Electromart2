<?php
/**
 * ELECTROMART - Admin: Kelola Pengguna
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title  = 'Kelola Pengguna';
$active_menu = 'users';

include __DIR__ . '/../includes/header.php';

$search   = trim($_GET['q'] ?? '');
$role_filter = $_GET['role'] ?? '';

$where = "WHERE 1=1";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$s%' OR email LIKE '%$s%')";
}
if ($role_filter) $where .= " AND role = '" . $conn->real_escape_string($role_filter) . "'";

$users = $conn->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS order_count,
           (SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id = u.id AND status != 'cancelled') AS total_spent
    FROM users u
    $where
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$total_admins = $conn->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetch_row()[0];
$total_users  = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
?>

<div class="page-header">
    <div>
        <h1>👥 Kelola Pengguna</h1>
        <p><?= count($users) ?> pengguna ditemukan</p>
    </div>
</div>

<?php showAlert(); ?>

<!-- Summary -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div>
            <div class="stat-value"><?= $total_admins + $total_users ?></div>
            <div class="stat-label">Total Pengguna</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">👤</div>
        <div>
            <div class="stat-value"><?= $total_users ?></div>
            <div class="stat-label">User Aktif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">🔧</div>
        <div>
            <div class="stat-value"><?= $total_admins ?></div>
            <div class="stat-label">Administrator</div>
        </div>
    </div>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar">
    <div class="search-bar">
        <span class="icon">🔍</span>
        <input type="text" name="q" placeholder="Cari nama / email…"
               value="<?= sanitize($search) ?>">
    </div>
    <select name="role" class="form-control" style="width:auto;font-size:14px;padding:9px 36px 9px 12px" onchange="this.form.submit()">
        <option value="">Semua Role</option>
        <option value="user"  <?= $role_filter === 'user'  ? 'selected' : '' ?>>👤 User</option>
        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>🔧 Admin</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
    <?php if ($search || $role_filter): ?>
    <a href="<?= BASE_URL ?>/admin/users/index.php" class="btn btn-ghost btn-sm">✕ Reset</a>
    <?php endif; ?>
</form>

<?php if (empty($users)): ?>
<div class="card">
    <div class="empty-state" style="padding:64px">
        <div class="empty-icon">👥</div>
        <div class="empty-title">Tidak ada pengguna ditemukan</div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Telepon</th>
                    <th>Total Pesanan</th>
                    <th>Total Belanja</th>
                    <th>Bergabung</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px">
                            <div style="width:38px;height:38px;background:<?= $u['role'] === 'admin' ? 'var(--primary)' : 'var(--gray-200)' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:<?= $u['role'] === 'admin' ? 'white' : 'var(--gray-600)' ?>;flex-shrink:0">
                                <?= strtoupper(mb_substr($u['name'],0,2)) ?>
                            </div>
                            <div>
                                <div class="fw-600"><?= sanitize($u['name']) ?></div>
                                <div class="text-xs text-muted"><?= sanitize($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge badge-primary">🔧 Admin</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">👤 User</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-muted"><?= sanitize($u['phone'] ?? '-') ?></td>
                    <td>
                        <span class="fw-700"><?= $u['order_count'] ?></span>
                        <span class="text-muted text-xs">pesanan</span>
                    </td>
                    <td class="fw-700 text-sm" style="color:var(--primary)"><?= formatPrice($u['total_spent']) ?></td>
                    <td class="text-xs text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
