<?php
/**
 * ELECTROMART - Checkout (dengan metode pembayaran cashless)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (isAdmin()) redirect(BASE_URL . '/index.php');

$uid = (int)$_SESSION['user']['id'];

// Fetch cart
$cart_items = $conn->query("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id,
           p.name, p.price, p.image, p.brand, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $uid
")->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    setAlert('warning', 'Keranjang Anda kosong. Silakan tambahkan produk terlebih dahulu.');
    redirect(BASE_URL . '/user/cart.php');
}

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart_items));
$shipping = ($subtotal < 500000) ? 25000 : 0;
$total    = $subtotal + $shipping;

$user_data = $_SESSION['user'];
$errors    = [];

$payment_methods = [
    'cod'              => ['label' => 'Bayar di Tempat (COD)',    'icon' => '💵', 'desc' => 'Bayar saat paket diterima kurir'],
    'transfer_bca'     => ['label' => 'Transfer Bank BCA',        'icon' => '🏦', 'desc' => 'No. Rek: 1234567890 a.n. ELECTROMART'],
    'transfer_bri'     => ['label' => 'Transfer Bank BRI',        'icon' => '🏦', 'desc' => 'No. Rek: 0987654321 a.n. ELECTROMART'],
    'transfer_mandiri' => ['label' => 'Transfer Bank Mandiri',    'icon' => '🏦', 'desc' => 'No. Rek: 1122334455 a.n. ELECTROMART'],
    'transfer_bni'     => ['label' => 'Transfer Bank BNI',        'icon' => '🏦', 'desc' => 'No. Rek: 5544332211 a.n. ELECTROMART'],
    'qris'             => ['label' => 'QRIS (Scan & Pay)',         'icon' => '📱', 'desc' => 'Bayar via QRIS — semua dompet digital'],
];

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address        = trim($_POST['shipping_address'] ?? '');
    $notes          = trim($_POST['notes']            ?? '');
    $payment_method = $_POST['payment_method']        ?? 'cod';

    if (empty($address)) $errors[] = 'Alamat pengiriman wajib diisi.';
    if (!array_key_exists($payment_method, $payment_methods)) $errors[] = 'Metode pembayaran tidak valid.';

    if (empty($errors)) {
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                $errors[] = "Stok produk \"{$item['name']}\" tidak mencukupi (sisa {$item['stock']}).";
            }
        }
    }

    if (empty($errors)) {
        // payment_status: COD langsung 'paid', cashless mulai 'unpaid'
        $pay_status = ($payment_method === 'cod') ? 'paid' : 'unpaid';

        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, total_price, payment_method, payment_status, shipping_address, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('idssss', $uid, $total, $payment_method, $pay_status, $address, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // Insert order items + kurangi stok
        foreach ($cart_items as $item) {
            $s = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?,?,?,?,?)");
            $s->bind_param('iisid', $order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']);
            $s->execute(); $s->close();
            $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
        }

        // Kosongkan keranjang
        $conn->query("DELETE FROM cart WHERE user_id = $uid");

        if ($payment_method === 'cod') {
            setAlert('success', "🎉 Pesanan #ORD" . str_pad($order_id,4,'0',STR_PAD_LEFT) . " berhasil dibuat! Kami akan segera memprosesnya.");
            redirect(BASE_URL . '/user/orders.php');
        } else {
            // Arahkan ke halaman upload bukti pembayaran
            redirect(BASE_URL . '/user/payment.php?order_id=' . $order_id);
        }
    }
}

$page_title = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>

<?php showAlert(); ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <span class="alert-icon">✕</span>
    <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<!-- Header + Back -->
<div class="d-flex align-center" style="gap:12px;margin-bottom:28px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>/user/cart.php" class="btn btn-secondary btn-sm">← Keranjang</a>
    <a href="<?= BASE_URL ?>/index.php"     class="btn btn-ghost btn-sm">🏠 Beranda</a>
    <div>
        <h1 style="font-size:24px;font-weight:800;margin-bottom:2px">✅ Checkout</h1>
        <p class="text-muted text-sm">Konfirmasi pesanan dan pilih metode pembayaran</p>
    </div>
</div>

<form method="POST" action="" id="checkoutForm">
<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

    <!-- Left: Form -->
    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Shipping Address -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">📍 Alamat Pengiriman</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required" for="shipping_address">Alamat Lengkap</label>
                    <textarea name="shipping_address" id="shipping_address" class="form-control" rows="4"
                              placeholder="Masukkan alamat lengkap, kode pos, kota, dan provinsi..."
                              required><?= sanitize($user_data['address'] ?? '') ?></textarea>
                    <div class="form-hint">Pastikan alamat lengkap dan dapat ditemukan oleh kurir.</div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="notes">Catatan untuk Penjual</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"
                              placeholder="Contoh: tolong dibungkus dengan aman (opsional)"></textarea>
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">💳 Metode Pembayaran</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">

                <?php foreach ($payment_methods as $key => $pm): ?>
                <label class="payment-option" id="label-<?= $key ?>" style="
                    display:flex;align-items:flex-start;gap:14px;padding:14px 16px;
                    border:2px solid var(--gray-200);border-radius:12px;cursor:pointer;
                    transition:all .2s;position:relative;
                ">
                    <input type="radio" name="payment_method" value="<?= $key ?>"
                           <?= $key === 'cod' ? 'checked' : '' ?>
                           class="payment-radio"
                           style="width:18px;height:18px;accent-color:var(--primary);flex-shrink:0;margin-top:2px">
                    <div style="flex:1">
                        <div style="font-weight:700;font-size:14px;color:var(--gray-900)">
                            <?= $pm['icon'] ?> <?= $pm['label'] ?>
                        </div>
                        <div style="font-size:12px;color:var(--gray-500);margin-top:2px"><?= $pm['desc'] ?></div>
                        <?php if ($key !== 'cod'): ?>
                        <div class="payment-info" id="info-<?= $key ?>" style="display:none;margin-top:10px;padding:10px;background:var(--primary-light);border-radius:8px;font-size:12px;color:var(--primary)">
                            <?php if ($key === 'qris'): ?>
                            <div style="font-weight:700;margin-bottom:6px">📱 Scan QR Code berikut:</div>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=ELECTROMART-PAYMENT&color=2563EB&bgcolor=EFF6FF"
                                 alt="QRIS ELECTROMART"
                                 style="width:140px;height:140px;border-radius:8px;display:block;border:2px solid white">
                            <?php else: ?>
                            <div style="font-weight:700;margin-bottom:4px">🏦 Informasi Rekening:</div>
                            <div>Bank: <strong><?= str_replace(['transfer_', '_'], ['', ' '], strtoupper($key)) ?></strong></div>
                            <?php
                            $acc = ['transfer_bca' => '1234567890', 'transfer_bri' => '0987654321', 'transfer_mandiri' => '1122334455', 'transfer_bni' => '5544332211'];
                            ?>
                            <div>No. Rekening: <strong><?= $acc[$key] ?? '-' ?></strong></div>
                            <div>Atas Nama: <strong>ELECTROMART</strong></div>
                            <?php endif; ?>
                            <div style="margin-top:8px;padding:8px;background:white;border-radius:6px;font-weight:600">
                                ⚠️ Setelah transfer, upload bukti pembayaran di langkah berikutnya.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block" id="placeOrderBtn">
            🎉 Buat Pesanan Sekarang
        </button>
        <a href="<?= BASE_URL ?>/user/cart.php" class="btn btn-secondary btn-block">← Kembali ke Keranjang</a>
    </div>

    <!-- Right: Summary -->
    <div>
        <div class="cart-summary">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:16px">📋 Ringkasan Pesanan</h3>

            <div style="margin-bottom:16px;max-height:260px;overflow-y:auto">
                <?php foreach ($cart_items as $item): ?>
                <div style="display:flex;gap:12px;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100)">
                    <img src="<?= getProductImage($item['image'], $item['name']) ?>"
                         alt="<?= sanitize($item['name']) ?>"
                         style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--gray-200);flex-shrink:0"
                         onerror="this.src='https://via.placeholder.com/44/EFF6FF/2563EB?text=IMG'">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sanitize($item['name']) ?></div>
                        <div style="font-size:12px;color:var(--gray-500)"><?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                    </div>
                    <div style="font-size:13px;font-weight:700;color:var(--primary);flex-shrink:0"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span class="fw-600"><?= formatPrice($subtotal) ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkos Kirim</span>
                <span class="fw-600" style="color:<?= $shipping ? 'var(--gray-700)' : 'var(--success)' ?>">
                    <?= $shipping ? formatPrice($shipping) : 'GRATIS' ?>
                </span>
            </div>
            <div class="summary-total">
                <span>Total Bayar</span>
                <span class="total-price"><?= formatPrice($total) ?></span>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-body" style="padding:16px;font-size:13px;color:var(--gray-600);line-height:1.8">
                📅 <strong>Estimasi</strong>: 2–5 hari kerja<br>
                🔒 <strong>Aman</strong>: Data terenkripsi<br>
                📞 <strong>CS</strong>: (021) 1234-5678
            </div>
        </div>
    </div>
</div>
</form>

<style>
.payment-option:has(input:checked) {
    border-color: var(--primary);
    background: var(--primary-light);
}
</style>

<script>
document.querySelectorAll('.payment-radio').forEach(radio => {
    radio.addEventListener('change', function () {
        // Reset semua border
        document.querySelectorAll('.payment-option').forEach(el => {
            el.style.borderColor = 'var(--gray-200)';
            el.style.background  = 'white';
        });
        // Aktifkan label yang dipilih
        const label = document.getElementById('label-' + this.value);
        if (label) { label.style.borderColor = 'var(--primary)'; label.style.background = 'var(--primary-light)'; }
        // Toggle info cashless
        document.querySelectorAll('.payment-info').forEach(el => el.style.display = 'none');
        const info = document.getElementById('info-' + this.value);
        if (info) info.style.display = 'block';
    });
});
// Set initial active
const checked = document.querySelector('.payment-radio:checked');
if (checked) checked.dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
