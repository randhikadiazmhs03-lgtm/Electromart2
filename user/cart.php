<?php
/**
 * ELECTROMART - Keranjang Belanja
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (isAdmin()) redirect(BASE_URL . '/index.php');

$uid = (int)$_SESSION['user']['id'];

/* ── Handle POST actions ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $cart_id    = (int)($_POST['cart_id']    ?? 0);
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    if ($action === 'add' && $product_id) {
        // Cek stok
        $stk = $conn->query("SELECT stock FROM products WHERE id = $product_id")->fetch_row();
        if ($stk && $stk[0] > 0) {
            $qty = min($quantity, $stk[0]);
            $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $check->bind_param('ii', $uid, $product_id);
            $check->execute();
            $ex = $check->get_result()->fetch_assoc(); $check->close();
            if ($ex) {
                $nq = min($ex['quantity'] + $qty, $stk[0]);
                $s = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $s->bind_param('iii', $nq, $uid, $product_id); $s->execute(); $s->close();
            } else {
                $s = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)");
                $s->bind_param('iii', $uid, $product_id, $qty); $s->execute(); $s->close();
            }
            setAlert('success', 'Produk berhasil ditambahkan ke keranjang!');
        } else {
            setAlert('error', 'Stok produk tidak mencukupi.');
        }
    }

    elseif ($action === 'update' && $cart_id) {
        $cart_item = $conn->query("SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = $cart_id AND c.user_id = $uid")->fetch_assoc();
        if ($cart_item) {
            $qty = max(1, min($quantity, $cart_item['stock']));
            $s = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $s->bind_param('iii', $qty, $cart_id, $uid); $s->execute(); $s->close();
        }
    }

    elseif ($action === 'remove' && $cart_id) {
        $s = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $s->bind_param('ii', $cart_id, $uid); $s->execute(); $s->close();
        setAlert('success', 'Produk dihapus dari keranjang.');
    }

    elseif ($action === 'clear') {
        $conn->query("DELETE FROM cart WHERE user_id = $uid");
        setAlert('success', 'Keranjang berhasil dikosongkan.');
    }

    redirect(BASE_URL . '/user/cart.php');
}

/* ── Fetch cart items ────────────────────────────────── */
$cart_items = $conn->query("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id,
           p.name, p.price, p.image, p.brand, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $uid
    ORDER BY c.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart_items));
$shipping = ($subtotal > 0 && $subtotal < 500000) ? 25000 : 0;
$total    = $subtotal + $shipping;

$page_title = 'Keranjang Belanja';
include __DIR__ . '/../includes/header.php';
?>

<?php showAlert(); ?>

<div class="d-flex align-center justify-between" style="margin-bottom:28px">
    <div>
        <h1 style="font-size:24px;font-weight:800;margin-bottom:4px">🛒 Keranjang Belanja</h1>
        <p class="text-muted text-sm"><?= count($cart_items) ?> produk di keranjang Anda</p>
    </div>
    <div style="display:flex;gap:12px">
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-ghost btn-sm">🛍️ Lanjut Belanja</a>
        <?php if (!empty($cart_items)): ?>
        <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Kosongkan keranjang?')">🗑️ Kosongkan</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($cart_items)): ?>
<div class="card">
    <div class="empty-state" style="padding:80px 24px">
        <div class="empty-icon">🛒</div>
        <div class="empty-title">Keranjang masih kosong</div>
        <p class="empty-desc">Yuk, mulai belanja produk elektronik favorit Anda!</p>
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-primary btn-lg" style="margin-top:20px">
            🛍️ Mulai Belanja
        </a>
    </div>
</div>

<?php else: ?>
<div class="cart-layout">
    <!-- Cart Items -->
    <div class="card">
        <?php foreach ($cart_items as $item): ?>
        <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
            <!-- Image -->
            <img src="<?= getProductImage($item['image'], $item['name']) ?>"
                 alt="<?= sanitize($item['name']) ?>"
                 class="cart-item-img"
                 onerror="this.src='https://via.placeholder.com/88x88/EFF6FF/2563EB?text=IMG'">

            <!-- Info -->
            <div>
                <div class="cart-item-brand"><?= sanitize($item['brand'] ?? '') ?></div>
                <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $item['product_id'] ?>"
                   class="cart-item-name" style="color:var(--gray-900)">
                    <?= sanitize($item['name']) ?>
                </a>
                <div class="cart-item-price"><?= formatPrice($item['price']) ?> / unit</div>
                <div style="margin-top:10px;display:flex;align-items:center;gap:12px">
                    <!-- Qty update form -->
                    <form method="POST" style="display:flex;align-items:center;gap:8px" class="auto-submit-form">
                        <input type="hidden" name="action"  value="update">
                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                        <div class="qty-control" style="border-width:1px">
                            <button type="button" class="qty-btn" data-action="minus" style="width:30px;height:30px;font-size:16px">−</button>
                            <input type="number" name="quantity" class="qty-input cart-qty-input"
                                   value="<?= $item['quantity'] ?>"
                                   min="1" max="<?= $item['stock'] ?>"
                                   data-cart-id="<?= $item['cart_id'] ?>"
                                   style="width:44px;height:30px;font-size:13px">
                            <button type="button" class="qty-btn" data-action="plus" style="width:30px;height:30px;font-size:16px">+</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Price + Remove -->
            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:8px">
                <div style="font-size:16px;font-weight:800;color:var(--primary)">
                    <?= formatPrice($item['price'] * $item['quantity']) ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="action"  value="remove">
                    <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);padding:4px 8px">🗑️</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Summary -->
    <div class="cart-summary">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px">Ringkasan Pesanan</h3>

        <div class="summary-row">
            <span>Subtotal (<?= count($cart_items) ?> produk)</span>
            <span class="fw-600"><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="summary-row">
            <span>Ongkos Kirim</span>
            <span class="fw-600" style="color:<?= $shipping ? 'var(--gray-700)' : 'var(--success)' ?>">
                <?= $shipping ? formatPrice($shipping) : 'GRATIS' ?>
            </span>
        </div>
        <?php if ($shipping > 0): ?>
        <div style="font-size:11px;color:var(--gray-400);padding:4px 0">
            * Gratis ongkir untuk pembelian ≥ Rp 500.000
        </div>
        <?php endif; ?>

        <div class="summary-total">
            <span>Total</span>
            <span class="total-price"><?= formatPrice($total) ?></span>
        </div>

        <a href="<?= BASE_URL ?>/user/checkout.php" class="btn btn-primary btn-block btn-lg" id="checkoutBtn" style="margin-top:12px">
            ✓ Lanjut ke Checkout
        </a>
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-secondary btn-block" style="margin-top:8px">
            ← Lanjut Belanja
        </a>

        <div class="divider"></div>
        <div style="font-size:12px;color:var(--gray-400);text-align:center;line-height:1.6">
            🔒 Transaksi aman & terenkripsi<br>
            📦 Pengiriman ke seluruh Indonesia<br>
            🚚 Estimasi 2-5 hari kerja
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete modal (untuk remove cart) -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <h3 class="modal-title" id="modalTitle">Hapus Item</h3>
        <p  class="modal-desc"  id="modalDesc">Yakin ingin menghapus item ini dari keranjang?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancelDelete">Batal</button>
            <button class="btn btn-danger"    id="confirmDelete">Ya, Hapus</button>
        </div>
    </div>
</div>

</div>

<script>
document.querySelectorAll('.cart-qty-input').forEach(input => {
    input.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
