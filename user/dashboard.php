<?php
/**
 * ELECTROMART - Dashboard User
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (isAdmin()) redirect(BASE_URL . '/admin/dashboard.php');

$uid = (int)$_SESSION['user']['id'];

// Stats
$total_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid")->fetch_row()[0];
$pending      = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid AND status = 'pending'")->fetch_row()[0];
$delivered    = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid AND status = 'delivered'")->fetch_row()[0];
$total_spent  = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id = $uid AND status != 'cancelled'")->fetch_row()[0];

// Recent orders
$recent_orders = $conn->query("
    SELECT * FROM orders WHERE user_id = $uid
    ORDER BY created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Cart count
$cart_count = getCartCount($conn);

$page_title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<?php showAlert(); ?>

<!-- Greeting -->
<div style="margin-bottom:28px">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:4px">
        👋 Halo, <?= sanitize(explode(' ', $_SESSION['user']['name'])[0]) ?>!
    </h1>
    <p class="text-muted">Selamat datang di dashboard ELECTROMART Anda.</p>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:32px">
    <div class="stat-card">
        <div class="stat-icon blue">📦</div>
        <div>
            <div class="stat-value"><?= $total_orders ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">⏳</div>
        <div>
            <div class="stat-value"><?= $pending ?></div>
            <div class="stat-label">Menunggu Proses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div>
            <div class="stat-value"><?= $delivered ?></div>
            <div class="stat-label">Selesai</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">💰</div>
        <div>
            <div class="stat-value" style="font-size:18px"><?= formatPrice($total_spent) ?></div>
            <div class="stat-label">Total Belanja</div>
        </div>
    </div>
</div>

<!-- Quick Links + Recent Orders -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;align-items:start">
    <!-- Quick Links -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">⚡ Aksi Cepat</h3></div>
        <div class="card-body" style="padding:12px">
            <a href="<?= BASE_URL ?>/products/index.php" class="dropdown-item" style="border-radius:8px;margin-bottom:2px;font-size:15px;padding:12px 16px">
                🛍️ Belanja Produk
            </a>
            <a href="<?= BASE_URL ?>/user/cart.php" class="dropdown-item" style="border-radius:8px;margin-bottom:2px;font-size:15px;padding:12px 16px">
                🛒 Keranjang
                <?php if ($cart_count > 0): ?>
                <span class="badge badge-danger" style="margin-left:auto"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/user/orders.php" class="dropdown-item" style="border-radius:8px;margin-bottom:2px;font-size:15px;padding:12px 16px">
                📦 Riwayat Pesanan
            </a>
            <a href="<?= BASE_URL ?>/user/profile.php" class="dropdown-item" style="border-radius:8px;font-size:15px;padding:12px 16px">
                👤 Edit Profil
            </a>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Pesanan Terkini</h3>
            <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <?php if (empty($recent_orders)): ?>
        <div class="empty-state" style="padding:40px">
            <div class="empty-icon">🛒</div>
            <div class="empty-title">Belum ada pesanan</div>
            <p class="empty-desc">Mulai belanja sekarang!</p>
            <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-primary" style="margin-top:16px">Belanja Sekarang</a>
        </div>
        <?php else: ?>
        <div class="table-wrapper" style="border:none;border-radius:0">
            <table>
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $o): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/user/orders.php?id=<?= $o['id'] ?>" style="font-weight:700;color:var(--primary)">#ORD<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?></a></td>
                        <td class="text-sm text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td class="fw-700"><?= formatPrice($o['total_price']) ?></td>
                        <td><?= getStatusBadge($o['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
