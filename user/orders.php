<?php
/**
 * ELECTROMART - Riwayat Pesanan User
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (isAdmin()) redirect(BASE_URL . '/index.php');

$uid = (int)$_SESSION['user']['id'];

// Fetch orders
$orders = $conn->query("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    WHERE o.user_id = $uid
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Fetch detail order jika diminta
$detail_order = null;
$detail_items = [];
if (isset($_GET['id'])) {
    $oid = (int)$_GET['id'];
    $detail_order = $conn->query("SELECT * FROM orders WHERE id = $oid AND user_id = $uid")->fetch_assoc();
    if ($detail_order) {
        $detail_items = $conn->query("
            SELECT oi.*, p.image, p.brand
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = $oid
        ")->fetch_all(MYSQLI_ASSOC);
    }
}

$page_title = 'Riwayat Pesanan';
include __DIR__ . '/../includes/header.php';
?>

<?php showAlert(); ?>

<div class="d-flex align-center justify-between" style="margin-bottom:28px">
    <div>
        <h1 style="font-size:24px;font-weight:800;margin-bottom:4px">📦 Riwayat Pesanan</h1>
        <p class="text-muted text-sm"><?= count($orders) ?> pesanan ditemukan</p>
    </div>
    <div style="display:flex;gap:12px">
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-ghost btn-sm">🏠 Beranda</a>
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-primary btn-sm">🛍️ Belanja Lagi</a>
    </div>
</div>

<?php if (!empty($detail_order)): ?>
<!-- ORDER DETAIL VIEW -->
<div style="margin-bottom:28px">
    <div class="d-flex align-center" style="gap:12px;margin-bottom:20px">
        <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-ghost btn-sm">← Semua Pesanan</a>
        <a href="<?= BASE_URL ?>/index.php"       class="btn btn-ghost btn-sm">🏠 Beranda</a>
        <h2 style="font-size:18px;font-weight:700">
            Detail Pesanan #ORD<?= str_pad($detail_order['id'],4,'0',STR_PAD_LEFT) ?>
        </h2>
        <?= getStatusBadge($detail_order['status']) ?>
        <?php 
            $ps = $detail_order['payment_status'] ?? 'paid';
            $ps_labels = [
                'unpaid'   => ['label' => 'Belum Bayar',   'class' => 'badge-danger'],
                'paid'     => ['label' => 'Sudah Bayar',   'class' => 'badge-warning'],
                'verified' => ['label' => 'Terverifikasi', 'class' => 'badge-success'],
            ];
            $ps_badge = $ps_labels[$ps] ?? ['label' => $ps, 'class' => 'badge-secondary'];
        ?>
        <span class="badge <?= $ps_badge['class'] ?>">💳 <?= $ps_badge['label'] ?></span>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        <!-- Items -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">🛒 Item Pesanan</h3></div>
            <div>
                <?php foreach ($detail_items as $item): ?>
                <div style="display:flex;gap:16px;align-items:center;padding:16px;border-bottom:1px solid var(--gray-100)">
                    <img src="<?= getProductImage($item['image'] ?? null, $item['product_name']) ?>"
                         alt="<?= sanitize($item['product_name']) ?>"
                         style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid var(--gray-200);flex-shrink:0"
                         onerror="this.src='https://via.placeholder.com/64/EFF6FF/2563EB?text=IMG'">
                    <div style="flex:1">
                        <div style="font-weight:600;color:var(--gray-900);margin-bottom:4px"><?= sanitize($item['product_name']) ?></div>
                        <div style="font-size:13px;color:var(--gray-500)"><?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                    </div>
                    <div style="font-weight:800;color:var(--primary)"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer" style="display:flex;justify-content:space-between;font-weight:700">
                <span>Total</span>
                <span style="color:var(--primary);font-size:18px"><?= formatPrice($detail_order['total_price']) ?></span>
            </div>
        </div>

        <!-- Info -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">📋 Info Pesanan</h3></div>
                <div class="card-body">
                    <div style="font-size:13px;line-height:2;color:var(--gray-600)">
                        <div><strong>ID:</strong> #ORD<?= str_pad($detail_order['id'],4,'0',STR_PAD_LEFT) ?></div>
                        <div><strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($detail_order['created_at'])) ?></div>
                        <div><strong>Status Kirim:</strong> <?= getStatusBadge($detail_order['status']) ?></div>
                        <div>
                            <strong>Pembayaran:</strong> 
                            <span class="badge <?= $ps_badge['class'] ?>"><?= $ps_badge['label'] ?></span>
                            (<?= strtoupper(str_replace('_', ' ', $detail_order['payment_method'] ?? 'cod')) ?>)
                        </div>
                    </div>
                    
                    <?php if (($detail_order['payment_method'] ?? 'cod') !== 'cod'): ?>
                    <div style="margin-top:16px">
                        <?php if ($ps === 'unpaid'): ?>
                        <div class="alert alert-warning" style="margin-bottom:12px;padding:8px 12px">
                            <span class="alert-icon">⚠</span>
                            <span>Segera lakukan pembayaran.</span>
                        </div>
                        <a href="<?= BASE_URL ?>/user/payment.php?order_id=<?= $detail_order['id'] ?>" class="btn btn-primary btn-sm btn-block">💳 Bayar & Upload Bukti</a>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/user/payment.php?order_id=<?= $detail_order['id'] ?>" class="btn btn-secondary btn-sm btn-block">Lihat / Ubah Bukti Pembayaran</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">📍 Alamat Kirim</h3></div>
                <div class="card-body">
                    <p style="font-size:13px;line-height:1.7"><?= nl2br(sanitize($detail_order['shipping_address'])) ?></p>
                </div>
            </div>
            <?php if ($detail_order['notes']): ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title">📝 Catatan</h3></div>
                <div class="card-body">
                    <p style="font-size:13px"><?= nl2br(sanitize($detail_order['notes'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ORDERS LIST -->
<?php if (empty($orders)): ?>
<div class="card">
    <div class="empty-state" style="padding:80px 24px">
        <div class="empty-icon">📦</div>
        <div class="empty-title">Belum ada pesanan</div>
        <p class="empty-desc">Mulai belanja dan pesanan Anda akan tampil di sini.</p>
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-primary" style="margin-top:20px">Mulai Belanja</a>
    </div>
</div>

<?php else: ?>
<div>
    <?php foreach ($orders as $o): ?>
    <div class="order-card">
        <div class="order-header">
            <div>
                <div class="order-id">#ORD<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?></div>
                <div class="order-date">
                    <?= date('d M Y, H:i', strtotime($o['created_at'])) ?> ·
                    <?= $o['item_count'] ?> produk
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <?php 
                    $ops = $o['payment_status'] ?? 'paid';
                    if ($ops === 'unpaid' && ($o['payment_method'] ?? 'cod') !== 'cod') {
                        echo '<span class="badge badge-danger">💳 Belum Bayar</span>';
                    } elseif ($ops === 'verified') {
                        echo '<span class="badge badge-success">✅ Lunas</span>';
                    }
                ?>
                <?= getStatusBadge($o['status']) ?>
                <span style="font-size:17px;font-weight:800;color:var(--primary)"><?= formatPrice($o['total_price']) ?></span>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php
            $steps = ['pending','processing','shipped','delivered'];
            $step_labels = ['Menunggu','Diproses','Dikirim','Selesai'];
            $current_step = array_search($o['status'], $steps);
            $is_cancelled = $o['status'] === 'cancelled';
        ?>
        <?php if (!$is_cancelled && $current_step !== false): ?>
        <div style="margin-bottom:16px">
            <div style="display:flex;align-items:center;position:relative">
                <?php foreach ($steps as $i => $s): ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;position:relative;z-index:1">
                    <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
                                background:<?= $i <= $current_step ? 'var(--primary)' : 'var(--gray-200)' ?>;
                                color:<?= $i <= $current_step ? 'white' : 'var(--gray-400)' ?>">
                        <?= $i < $current_step ? '✓' : ($i + 1) ?>
                    </div>
                    <div style="font-size:10px;margin-top:6px;color:<?= $i <= $current_step ? 'var(--primary)' : 'var(--gray-400)' ?>;font-weight:<?= $i === $current_step ? '700' : '400' ?>">
                        <?= $step_labels[$i] ?>
                    </div>
                </div>
                <?php if ($i < count($steps) - 1): ?>
                <div style="position:absolute;left:<?= (100 / count($steps)) * ($i + 0.5) ?>%;width:<?= 100 / count($steps) ?>%;height:2px;top:14px;background:<?= $i < $current_step ? 'var(--primary)' : 'var(--gray-200)' ?>;z-index:0"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($is_cancelled): ?>
        <div class="alert alert-danger" style="margin-bottom:12px;padding:10px 14px">
            <span class="alert-icon">✕</span>
            <span>Pesanan ini telah dibatalkan.</span>
        </div>
        <?php endif; ?>

        <div style="display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:13px;color:var(--gray-500)">
                Dikirim ke: <?= sanitize(truncate($o['shipping_address'] ?? '-', 60)) ?>
            </div>
            <div style="display:flex;gap:8px">
                <?php if (($o['payment_status'] ?? 'paid') === 'unpaid' && ($o['payment_method'] ?? 'cod') !== 'cod'): ?>
                <a href="<?= BASE_URL ?>/user/payment.php?order_id=<?= $o['id'] ?>" class="btn btn-primary btn-sm">
                    💳 Bayar Sekarang
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/user/orders.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary btn-sm">
                    Lihat Detail →
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
