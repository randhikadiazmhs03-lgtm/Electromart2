<?php
/**
 * ELECTROMART - Admin: Kelola Pesanan
 * FIX: POST handler dipindah SEBELUM include header (cegah "headers already sent")
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title  = 'Kelola Pesanan';
$active_menu = 'orders';

/* ── Handle status update ─── HARUS sebelum include header ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    requireAdmin(); // guard
    $oid    = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $valid  = ['pending','processing','shipped','delivered','cancelled'];
    if ($oid && in_array($status, $valid)) {
        $s = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $s->bind_param('si', $status, $oid);
        $s->execute();
        $s->close();
        setAlert('success', 'Status pesanan #ORD' . str_pad($oid,4,'0',STR_PAD_LEFT) . ' diperbarui menjadi: ' . ucfirst($status));
    }
    // Pertahankan ?id= agar detail tetap terbuka setelah redirect
    $back_id = (int)($_POST['back_order_id'] ?? 0);
    $qs      = $back_id ? '?id=' . $back_id : '';
    redirect(BASE_URL . '/admin/orders/index.php' . $qs);
}

/* ── Handle verifikasi pembayaran ─── HARUS sebelum include header ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    requireAdmin();
    $oid = (int)($_POST['order_id'] ?? 0);
    if ($oid) {
        $s = $conn->prepare("UPDATE orders SET payment_status = 'verified' WHERE id = ?");
        $s->bind_param('i', $oid);
        $s->execute();
        $s->close();
        setAlert('success', 'Pembayaran pesanan #ORD' . str_pad($oid,4,'0',STR_PAD_LEFT) . ' telah diverifikasi.');
    }
    redirect(BASE_URL . '/admin/orders/index.php?id=' . $oid);
}

include __DIR__ . '/../includes/header.php';

/* ── Filter ─────────────────────────────────────────────────── */
$filter_status  = $_GET['status'] ?? '';
$filter_payment = $_GET['payment_status'] ?? '';
$search         = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($filter_status)  $where .= " AND o.status = '" . $conn->real_escape_string($filter_status) . "'";
if ($filter_payment) $where .= " AND o.payment_status = '" . $conn->real_escape_string($filter_payment) . "'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (u.name LIKE '%$s%' OR u.email LIKE '%$s%' OR o.id = " . (intval($search) ?: 0) . ")";
}

$orders = $conn->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

/* ── Detail view ─────────────────────────────────────────────── */
$detail_order = null;
$detail_items = [];
if (isset($_GET['id'])) {
    $oid = (int)$_GET['id'];
    $detail_order = $conn->query("
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM orders o JOIN users u ON o.user_id = u.id
        WHERE o.id = $oid
    ")->fetch_assoc();
    if ($detail_order) {
        $detail_items = $conn->query("
            SELECT oi.*, p.image, p.brand
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = $oid
        ")->fetch_all(MYSQLI_ASSOC);
    }
}

$status_options         = ['pending','processing','shipped','delivered','cancelled'];
$payment_method_labels  = [
    'cod'             => ['label' => 'Bayar di Tempat (COD)', 'icon' => '💵', 'color' => 'var(--gray-600)'],
    'transfer_bca'    => ['label' => 'Transfer BCA',          'icon' => '🏦', 'color' => '#005BAC'],
    'transfer_bri'    => ['label' => 'Transfer BRI',          'icon' => '🏦', 'color' => '#00529C'],
    'transfer_mandiri'=> ['label' => 'Transfer Mandiri',      'icon' => '🏦', 'color' => '#003087'],
    'transfer_bni'    => ['label' => 'Transfer BNI',          'icon' => '🏦', 'color' => '#E97820'],
    'qris'            => ['label' => 'QRIS',                  'icon' => '📱', 'color' => '#E31E24'],
];
$payment_status_labels  = [
    'unpaid'   => ['label' => 'Belum Bayar',   'class' => 'badge-danger'],
    'paid'     => ['label' => 'Sudah Bayar',   'class' => 'badge-warning'],
    'verified' => ['label' => 'Terverifikasi', 'class' => 'badge-success'],
];
?>

<div class="page-header">
    <div>
        <h1>🛒 Kelola Pesanan</h1>
        <p><?= count($orders) ?> pesanan ditemukan</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<?php showAlert(); ?>

<!-- Filter -->
<form method="GET" class="filter-bar">
    <div class="search-bar">
        <span class="icon">🔍</span>
        <input type="text" name="q" placeholder="Nama pelanggan / ID…"
               value="<?= sanitize($search) ?>">
    </div>
    <select name="status" class="form-control" style="width:auto;font-size:14px;padding:9px 36px 9px 12px" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach ($status_options as $s): ?>
        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="payment_status" class="form-control" style="width:auto;font-size:14px;padding:9px 36px 9px 12px" onchange="this.form.submit()">
        <option value="">Semua Pembayaran</option>
        <option value="unpaid"   <?= $filter_payment === 'unpaid'   ? 'selected' : '' ?>>💳 Belum Bayar</option>
        <option value="paid"     <?= $filter_payment === 'paid'     ? 'selected' : '' ?>>✓ Sudah Bayar</option>
        <option value="verified" <?= $filter_payment === 'verified' ? 'selected' : '' ?>>✅ Terverifikasi</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($filter_status || $search || $filter_payment): ?>
    <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-ghost btn-sm">✕ Reset</a>
    <?php endif; ?>
</form>

<!-- Detail View -->
<?php if ($detail_order): ?>
<div style="margin-bottom:28px">
    <div class="d-flex align-center" style="gap:12px;margin-bottom:20px;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-secondary btn-sm">← Semua Pesanan</a>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-ghost btn-sm">🏠 Dashboard</a>
        <h2 style="font-size:18px;font-weight:700">
            Detail #ORD<?= str_pad($detail_order['id'],4,'0',STR_PAD_LEFT) ?>
        </h2>
        <?= getStatusBadge($detail_order['status']) ?>
        <?php
            $ps = $detail_order['payment_status'] ?? 'paid';
            $psl = $payment_status_labels[$ps] ?? ['label' => $ps, 'class' => 'badge-secondary'];
        ?>
        <span class="badge <?= $psl['class'] ?>">💳 <?= $psl['label'] ?></span>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        <!-- Items -->
        <div>
            <div class="card" style="margin-bottom:16px">
                <div class="card-header"><h3 class="card-title">🛒 Item Pesanan</h3></div>
                <?php foreach ($detail_items as $item): ?>
                <div style="display:flex;gap:16px;align-items:center;padding:16px;border-bottom:1px solid var(--gray-100)">
                    <img src="<?= getProductImage($item['image'] ?? null, $item['product_name']) ?>"
                         alt="<?= sanitize($item['product_name']) ?>"
                         style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid var(--gray-200);flex-shrink:0"
                         onerror="this.src='https://via.placeholder.com/56/EFF6FF/2563EB?text=IMG'">
                    <div style="flex:1">
                        <div style="font-weight:600;color:var(--gray-900)"><?= sanitize($item['product_name']) ?></div>
                        <div style="font-size:13px;color:var(--gray-500)"><?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                    </div>
                    <div style="font-weight:800;color:var(--primary)"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>
                <div class="card-footer" style="display:flex;justify-content:space-between;font-weight:700">
                    <span>Total Pesanan</span>
                    <span style="color:var(--primary);font-size:18px"><?= formatPrice($detail_order['total_price']) ?></span>
                </div>
            </div>

            <!-- Bukti Pembayaran -->
            <?php if (!empty($detail_order['payment_proof'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📎 Bukti Pembayaran</h3>
                    <?php if (($detail_order['payment_status'] ?? 'paid') === 'paid'): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="verify_payment" value="1">
                        <input type="hidden" name="order_id" value="<?= $detail_order['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm" id="verifyPayBtn">✅ Verifikasi</button>
                    </form>
                    <?php else: ?>
                    <span class="badge badge-success">✅ Sudah Diverifikasi</span>
                    <?php endif; ?>
                </div>
                <div class="card-body" style="text-align:center">
                    <?php
                        $proof = $detail_order['payment_proof'];
                        $proof_url = str_starts_with($proof, 'http') ? $proof : BASE_URL . '/' . ltrim($proof, '/');
                    ?>
                    <img src="<?= htmlspecialchars($proof_url) ?>"
                         alt="Bukti Pembayaran"
                         style="max-width:100%;max-height:400px;object-fit:contain;border-radius:10px;border:1px solid var(--gray-200)"
                         onerror="this.parentElement.innerHTML='<p style=\'color:var(--gray-400)\'>Gambar tidak dapat ditampilkan</p>'">
                    <div style="margin-top:12px">
                        <a href="<?= htmlspecialchars($proof_url) ?>" target="_blank" class="btn btn-secondary btn-sm">
                            🔍 Buka Penuh
                        </a>
                    </div>
                </div>
            </div>
            <?php elseif (($detail_order['payment_method'] ?? 'cod') !== 'cod'): ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title">📎 Bukti Pembayaran</h3></div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <span class="alert-icon">⚠</span>
                        <span>Pelanggan belum mengupload bukti pembayaran.</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info + Actions -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <!-- Customer -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">👤 Pelanggan</h3></div>
                <div class="card-body" style="font-size:13px;line-height:2">
                    <strong><?= sanitize($detail_order['user_name']) ?></strong><br>
                    <?= sanitize($detail_order['user_email']) ?><br>
                    <?= sanitize($detail_order['user_phone'] ?? '-') ?>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">💳 Metode Pembayaran</h3></div>
                <div class="card-body">
                    <?php
                        $pm  = $detail_order['payment_method'] ?? 'cod';
                        $pmi = $payment_method_labels[$pm] ?? ['label' => $pm, 'icon' => '💳', 'color' => 'var(--gray-700)'];
                    ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--gray-50);border-radius:8px;border:1px solid var(--gray-200)">
                        <span style="font-size:22px"><?= $pmi['icon'] ?></span>
                        <div>
                            <div style="font-weight:700;color:<?= $pmi['color'] ?>"><?= $pmi['label'] ?></div>
                            <div style="font-size:12px;margin-top:2px">
                                <span class="badge <?= $psl['class'] ?>"><?= $psl['label'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">📍 Alamat Kirim</h3></div>
                <div class="card-body">
                    <p style="font-size:13px;line-height:1.7"><?= nl2br(sanitize($detail_order['shipping_address'])) ?></p>
                    <?php if ($detail_order['notes']): ?>
                    <div class="divider"></div>
                    <p style="font-size:12px;color:var(--gray-500)"><strong>Catatan:</strong> <?= sanitize($detail_order['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Update Status -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">⚙️ Update Status</h3></div>
                <div class="card-body">
                    <form method="POST" id="updateStatusForm">
                        <input type="hidden" name="update_status"   value="1">
                        <input type="hidden" name="order_id"        value="<?= $detail_order['id'] ?>">
                        <input type="hidden" name="back_order_id"   value="<?= $detail_order['id'] ?>">
                        <div class="form-group">
                            <label class="form-label" for="statusSelect">Status Pengiriman</label>
                            <select name="status" id="statusSelect" class="form-control">
                                <?php foreach ($status_options as $s): ?>
                                <option value="<?= $s ?>" <?= $detail_order['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" id="updateStatusBtn">
                            💾 Perbarui Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Orders Table -->
<?php if (empty($orders)): ?>
<div class="card">
    <div class="empty-state" style="padding:64px">
        <div class="empty-icon">🛒</div>
        <div class="empty-title">Tidak ada pesanan ditemukan</div>
        <a href="<?= BASE_URL ?>/admin/orders/index.php" class="btn btn-secondary" style="margin-top:16px">Reset Filter</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
        <table>
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Item</th>
                    <th>Total</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o):
                    $ps2  = $o['payment_status'] ?? 'paid';
                    $psl2 = $payment_status_labels[$ps2] ?? ['label' => $ps2, 'class' => 'badge-secondary'];
                    $pm2  = $o['payment_method'] ?? 'cod';
                    $pmi2 = $payment_method_labels[$pm2] ?? ['label' => $pm2, 'icon' => '💳', 'color' => 'var(--gray-700)'];
                ?>
                <tr>
                    <td>
                        <a href="?id=<?= $o['id'] ?>" style="font-weight:700;color:var(--primary)">
                            #ORD<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?>
                        </a>
                    </td>
                    <td>
                        <div class="fw-600"><?= sanitize($o['user_name']) ?></div>
                        <div class="text-xs text-muted"><?= sanitize($o['user_email']) ?></div>
                    </td>
                    <td class="text-muted"><?= $o['item_count'] ?> produk</td>
                    <td class="fw-700"><?= formatPrice($o['total_price']) ?></td>
                    <td>
                        <div style="font-size:12px"><?= $pmi2['icon'] ?> <?= $pmi2['label'] ?></div>
                        <span class="badge <?= $psl2['class'] ?>" style="margin-top:4px"><?= $psl2['label'] ?></span>
                    </td>
                    <td><?= getStatusBadge($o['status']) ?></td>
                    <td class="text-xs text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <a href="?id=<?= $o['id'] ?>" class="btn btn-secondary btn-sm">Detail</a>
                            <!-- Quick Status Change -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="order_id"      value="<?= $o['id'] ?>">
                                <input type="hidden" name="back_order_id" value="0">
                                <select name="status" class="form-control"
                                        style="padding:6px 28px 6px 8px;font-size:12px;height:auto"
                                        onchange="this.form.submit()">
                                    <?php foreach ($status_options as $s): ?>
                                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
