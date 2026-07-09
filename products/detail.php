<?php
/**
 * ELECTROMART - Detail Produk
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setAlert('error', 'Produk tidak ditemukan.');
    redirect(BASE_URL . '/products/index.php');
}

// Fetch product
$stmt = $conn->prepare("
    SELECT p.*, c.name AS cat_name, c.id AS cat_id
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) {
    setAlert('error', 'Produk tidak ditemukan.');
    redirect(BASE_URL . '/products/index.php');
}

// Related products
$related = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = {$p['cat_id']} AND p.id != $id AND p.stock > 0
    ORDER BY RAND()
    LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    requireLogin();
    $qty     = max(1, min((int)$_POST['quantity'], $p['stock']));
    $user_id = (int)$_SESSION['user']['id'];

    // Cek apakah sudah ada di cart
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param('ii', $user_id, $id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        $new_qty = min($existing['quantity'] + $qty, $p['stock']);
        $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $upd->bind_param('iii', $new_qty, $user_id, $id);
        $upd->execute(); $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $ins->bind_param('iii', $user_id, $id, $qty);
        $ins->execute(); $ins->close();
    }

    setAlert('success', '"' . $p['name'] . '" berhasil ditambahkan ke keranjang!');
    redirect(BASE_URL . '/products/detail.php?id=' . $id);
}

$page_title = $p['name'];
include __DIR__ . '/../includes/header.php';
?>

<?php showAlert(); ?>

<!-- Breadcrumb -->
<nav style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-500);margin-bottom:28px" aria-label="Breadcrumb">
    <a href="<?= BASE_URL ?>/index.php" style="color:var(--gray-500)">Beranda</a>
    <span>/</span>
    <a href="<?= BASE_URL ?>/products/index.php" style="color:var(--gray-500)">Produk</a>
    <?php if ($p['cat_name']): ?>
    <span>/</span>
    <a href="<?= BASE_URL ?>/products/index.php?cat=<?= $p['cat_id'] ?>" style="color:var(--gray-500)"><?= sanitize($p['cat_name']) ?></a>
    <?php endif; ?>
    <span>/</span>
    <span style="color:var(--gray-900);font-weight:600"><?= sanitize(truncate($p['name'], 40)) ?></span>
</nav>

<!-- Product Detail -->
<div class="product-detail-grid">
    <!-- Image Column -->
    <div class="product-detail-image">
        <div class="product-detail-img-main">
            <img src="<?= getProductImage($p['image'], $p['name']) ?>"
                 alt="<?= sanitize($p['name']) ?>"
                 onerror="this.src='https://via.placeholder.com/400x400/EFF6FF/2563EB?text=No+Image'">
        </div>
        <!-- Badge row -->
        <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
            <?php if ($p['is_featured']): ?><span class="badge badge-warning">⭐ Unggulan</span><?php endif; ?>
            <?php if ($p['stock'] > 0): ?><span class="badge badge-success">✓ Tersedia</span><?php else: ?><span class="badge badge-danger">Habis</span><?php endif; ?>
            <?php if ($p['brand']): ?><span class="badge badge-secondary"><?= sanitize($p['brand']) ?></span><?php endif; ?>
        </div>
    </div>

    <!-- Info Column -->
    <div>
        <?php if ($p['cat_name']): ?>
        <div class="product-detail-brand"><?= sanitize($p['cat_name']) ?></div>
        <?php endif; ?>

        <h1 class="product-detail-name"><?= sanitize($p['name']) ?></h1>

        <div class="product-detail-price"><?= formatPrice($p['price']) ?></div>

        <!-- Stock info -->
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:14px">
            <?php if ($p['stock'] > 10): ?>
            <span style="color:var(--success);font-weight:600">✓ Stok tersedia</span>
            <span class="text-muted">(<?= $p['stock'] ?> unit)</span>
            <?php elseif ($p['stock'] > 0): ?>
            <span style="color:var(--warning);font-weight:600">⚠ Stok terbatas</span>
            <span class="text-muted">(sisa <?= $p['stock'] ?> unit)</span>
            <?php else: ?>
            <span style="color:var(--danger);font-weight:600">✕ Stok habis</span>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <?php if ($p['description']): ?>
        <div style="margin-bottom:28px">
            <h4 style="font-size:15px;margin-bottom:10px">Deskripsi Produk</h4>
            <p class="product-detail-desc"><?= nl2br(sanitize($p['description'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Add to Cart Form -->
        <?php if ($p['stock'] > 0): ?>
        <?php if (isLoggedIn() && !isAdmin()): ?>
        <form method="POST" action="" id="addToCartForm">
            <input type="hidden" name="add_to_cart" value="1">

            <div style="margin-bottom:20px">
                <label style="display:block;font-size:14px;font-weight:600;color:var(--gray-700);margin-bottom:10px">Jumlah</label>
                <div class="qty-control">
                    <button type="button" class="qty-btn" data-action="minus" aria-label="Kurangi">−</button>
                    <input type="number" name="quantity" class="qty-input" id="qtyInput"
                           value="1" min="1" max="<?= $p['stock'] ?>" aria-label="Jumlah">
                    <button type="button" class="qty-btn" data-action="plus" aria-label="Tambah">+</button>
                </div>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary btn-lg" id="addToCartBtn" style="flex:1;min-width:180px">
                    🛒 Tambah ke Keranjang
                </button>
                <a href="<?= BASE_URL ?>/user/cart.php" class="btn btn-secondary btn-lg" style="flex:1;min-width:140px">
                    Lihat Keranjang →
                </a>
            </div>
        </form>

        <?php elseif (!isLoggedIn()): ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ</span>
            <span>Silakan <a href="<?= BASE_URL ?>/auth/login.php" style="font-weight:700">login</a> atau <a href="<?= BASE_URL ?>/auth/register.php" style="font-weight:700">daftar</a> untuk membeli produk ini.</span>
        </div>
        <div style="display:flex;gap:12px">
            <a href="<?= BASE_URL ?>/auth/login.php"    class="btn btn-primary btn-lg" style="flex:1" id="detailLoginBtn">Masuk</a>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-secondary btn-lg" style="flex:1" id="detailRegisterBtn">Daftar</a>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ</span>
            <span>Admin tidak dapat membeli produk. Gunakan akun user.</span>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="alert alert-danger">
            <span class="alert-icon">✕</span>
            <span>Maaf, stok produk ini sedang habis. Silakan cek kembali nanti.</span>
        </div>
        <?php endif; ?>

        <!-- Additional info -->
        <div style="margin-top:28px;padding:16px;background:var(--gray-50);border-radius:var(--radius-lg);border:1px solid var(--gray-200)">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px">
                <?php if ($p['brand']): ?>
                <div><span style="color:var(--gray-400)">Merek:</span> <strong><?= sanitize($p['brand']) ?></strong></div>
                <?php endif; ?>
                <?php if ($p['cat_name']): ?>
                <div><span style="color:var(--gray-400)">Kategori:</span> <strong><?= sanitize($p['cat_name']) ?></strong></div>
                <?php endif; ?>
                <div><span style="color:var(--gray-400)">Stok:</span> <strong><?= $p['stock'] ?> unit</strong></div>
                <div><span style="color:var(--gray-400)">Kode:</span> <strong>#PRD<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if (!empty($related)): ?>
<section style="margin-top:64px">
    <div class="section-header">
        <div>
            <h2 class="section-title">Produk Serupa</h2>
            <p class="section-sub">Dari kategori <?= sanitize($p['cat_name'] ?? '') ?></p>
        </div>
    </div>
    <div class="products-grid">
        <?php foreach ($related as $r): ?>
        <div class="product-card">
            <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $r['id'] ?>" style="text-decoration:none;display:contents">
                <div class="product-card-image">
                    <img src="<?= getProductImage($r['image'], $r['name']) ?>"
                         alt="<?= sanitize($r['name']) ?>" loading="lazy">
                </div>
                <div class="product-card-body">
                    <div class="product-brand"><?= sanitize($r['brand'] ?? '') ?></div>
                    <div class="product-name"><?= sanitize($r['name']) ?></div>
                    <div class="product-price"><?= formatPrice($r['price']) ?></div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
