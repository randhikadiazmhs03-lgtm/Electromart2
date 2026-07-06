<?php
/**
 * ELECTROMART - Admin Dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title  = 'Dashboard Admin';
$active_menu = 'dashboard';

include __DIR__ . '/includes/header.php';

/* ── Stats ──────────────────────────────────────────── */
$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$total_orders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_users    = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
$low_stock      = $conn->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetch_row()[0];

/* ── Orders by status ───────────────────────────────── */
$status_counts = $conn->query("
    SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status
")->fetch_all(MYSQLI_ASSOC);
$status_map = array_column($status_counts, 'cnt', 'status');

/* ── Recent orders ──────────────────────────────────── */
$recent_orders = $conn->query("
    SELECT o.*, u.name AS user_name
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

/* ── Low stock products ─────────────────────────────── */
$low_stock_products = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= 5
    ORDER BY p.stock ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>📊 Dashboard Admin</h1>
        <p>Ringkasan aktivitas toko ELECTROMART hari ini</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/products/create.php" class="btn btn-primary">
        + Tambah Produk
    </a>
</div>

<?php showAlert(); ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📦</div>
        <div>
            <div class="stat-value"><?= $total_products ?></div>
            <div class="stat-label">Total Produk</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">🛒</div>
        <div>
            <div class="stat-value"><?= $total_orders ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">👥</div>
        <div>
            <div class="stat-value"><?= $total_users ?></div>
            <div class="stat-label">Pengguna</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div>
            <div class="stat-value" style="font-size:18px"><?= formatPrice($total_revenue) ?></div>
            <div class="stat-label">Total Pendapatan</div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($pending_orders > 0): ?>
<div class="alert alert-warning" style="margin-bottom:24px">
    <span class="alert-icon">⚠</span>
    <span>Ada <strong><?= $pending_orders ?> pesanan baru</strong> yang menunggu diproses.
    <a href="<?= BASE_URL ?>/admin/orders/index.php?status=pending" style="font-weight:700">Proses sekarang →</a></span>
    <button onclick="this.parentElement.remove()" class="alert-close">×</button>
</div>
<?php endif; ?>
<?php if ($low_stock > 0): ?>
<div class="alert alert-danger" style="margin-bottom:24px">
    <span class="alert-icon">✕</span>
    <span><strong><?= $low_stock ?> produk</strong> memiliki stok ≤ 5 unit.
    <a href="<?= BASE_URL ?>/admin/products/index.php" style="font-weight:700">Kelola stok →</a></span>
    <button onclick="this.parentElement.remove()" class="alert-close">×</button>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🛒 Pesanan Terbaru</h3>
            <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <div class="table-wrapper" style="border:none;border-radius:0">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $o): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/admin/orders/index.php?id=<?= $o['id'] ?>"
                               style="font-weight:700;color:var(--primary)">
                                #ORD<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <td><?= sanitize($o['user_name']) ?></td>
                        <td class="fw-700"><?= formatPrice($o['total_price']) ?></td>
                        <td><?= getStatusBadge($o['status']) ?></td>
                        <td class="text-muted text-xs"><?= timeAgo($o['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Status Summary -->
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><h3 class="card-title">📈 Status Pesanan</h3></div>
            <div class="card-body">
                <?php
                $status_info = [
                    'pending'    => ['label' => 'Menunggu',   'color' => 'var(--warning)', 'icon' => '⏳'],
                    'processing' => ['label' => 'Diproses',   'color' => 'var(--info)',    'icon' => '⚙️'],
                    'shipped'    => ['label' => 'Dikirim',    'color' => 'var(--primary)', 'icon' => '🚚'],
                    'delivered'  => ['label' => 'Selesai',    'color' => 'var(--success)', 'icon' => '✅'],
                    'cancelled'  => ['label' => 'Dibatalkan', 'color' => 'var(--danger)',  'icon' => '✕'],
                ];
                foreach ($status_info as $key => $info):
                    $cnt = $status_map[$key] ?? 0;
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100)">
                    <div style="display:flex;align-items:center;gap:8px;font-size:14px">
                        <span><?= $info['icon'] ?></span>
                        <span><?= $info['label'] ?></span>
                    </div>
                    <span style="font-weight:700;color:<?= $info['color'] ?>;font-size:16px"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <?php if (!empty($low_stock_products)): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title" style="color:var(--danger)">⚠️ Stok Menipis</h3></div>
            <div class="card-body" style="padding:12px">
                <?php foreach ($low_stock_products as $lp): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px;border-radius:8px;margin-bottom:4px;background:var(--danger-light)">
                    <div style="font-size:13px;font-weight:600;color:var(--gray-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px">
                        <?= sanitize($lp['name']) ?>
                    </div>
                    <span class="badge badge-danger"><?= $lp['stock'] ?> sisa</span>
                </div>
                <?php endforeach; ?>
                <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-sm btn-block btn-danger" style="margin-top:8px">Kelola Stok</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
