<?php
/**
 * ELECTROMART - Homepage
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Beranda';

// Ambil kategori
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Ambil produk unggulan
$featured_products = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_featured = 1 AND p.stock > 0
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Ambil semua produk terbaru
$latest_products = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

// Hitung statistik
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM products)  AS total_products,
        (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users,
        (SELECT COUNT(*) FROM categories) AS total_categories
")->fetch_assoc();

include __DIR__ . '/includes/header.php';
?>

<?php showAlert(); ?>

<!-- ===== HERO ===== -->
<section class="hero" style="margin: -40px -24px 0; padding-left: 24px; padding-right: 24px;">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Elektronik <em>Terbaik</em> untuk Mahasiswa Kampus</h1>
                <p>
                    ELECTROMART hadir sebagai solusi belanja elektronik untuk civitas akademika.
                    Temukan laptop, gadget, dan aksesori berkualitas dengan harga bersahabat — langsung dari genggaman!
                </p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-primary btn-lg" id="shopNowBtn">
                        🛍️ Mulai Belanja
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-secondary btn-lg" id="heroRegisterBtn">
                        Daftar Gratis →
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= $stats['total_products'] ?>+</div>
                        <div class="hero-stat-label">Produk</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= $stats['total_categories'] ?></div>
                        <div class="hero-stat-label">Kategori</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= $stats['total_users'] ?>+</div>
                        <div class="hero-stat-label">Pengguna</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number">100%</div>
                        <div class="hero-stat-label">Terpercaya</div>
                    </div>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                💻
            </div>
        </div>
    </div>
</section>

<!-- ===== CATEGORIES ===== -->
<section class="section" style="padding-top: 60px;">
    <div class="section-header">
        <div>
            <h2 class="section-title">Kategori Produk</h2>
            <p class="section-sub">Temukan produk sesuai kebutuhan Anda</p>
        </div>
        <a href="<?= BASE_URL ?>/products/index.php" class="section-link">Lihat Semua →</a>
    </div>
    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>/products/index.php?cat=<?= $cat['id'] ?>"
           class="category-card"
           id="cat-<?= $cat['id'] ?>"
           aria-label="Kategori <?= sanitize($cat['name']) ?>">
            <span class="category-icon" aria-hidden="true"><?= $cat['icon'] ?></span>
            <span class="category-name"><?= sanitize($cat['name']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===== FEATURED PRODUCTS ===== -->
<section class="section" style="padding-top: 0;">
    <div class="section-header">
        <div>
            <h2 class="section-title">⭐ Produk Unggulan</h2>
            <p class="section-sub">Pilihan terbaik dari tim ELECTROMART</p>
        </div>
        <a href="<?= BASE_URL ?>/products/index.php" class="section-link">Lihat Semua →</a>
    </div>

    <?php if (empty($featured_products)): ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <div class="empty-title">Belum ada produk unggulan</div>
    </div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($featured_products as $p): ?>
        <div class="product-card" id="product-<?= $p['id'] ?>">
            <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $p['id'] ?>" style="text-decoration:none;display:contents">
                <div class="product-card-image">
                    <img src="<?= getProductImage($p['image'], $p['name']) ?>"
                         alt="<?= sanitize($p['name']) ?>"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/400x300/EFF6FF/2563EB?text=No+Image'">
                    <span class="product-badge featured">Unggulan</span>
                </div>
                <div class="product-card-body">
                    <div class="product-brand"><?= sanitize($p['brand'] ?? '') ?></div>
                    <div class="product-name"><?= sanitize($p['name']) ?></div>
                    <div class="product-price"><?= formatPrice($p['price']) ?></div>
                    <div class="product-stock">
                        <?php if ($p['stock'] > 10): ?>
                        <span class="stock-dot"></span> Stok tersedia (<?= $p['stock'] ?>)
                        <?php elseif ($p['stock'] > 0): ?>
                        <span class="stock-dot low"></span> Stok terbatas (<?= $p['stock'] ?>)
                        <?php else: ?>
                        <span class="stock-dot empty"></span> Habis
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <div class="product-card-actions">
                <?php if ($p['stock'] > 0): ?>
                <?php if (isLoggedIn() && !isAdmin()): ?>
                <form method="POST" action="<?= BASE_URL ?>/user/cart.php">
                    <input type="hidden" name="action"     value="add">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="quantity"   value="1">
                    <button type="submit" class="btn btn-primary btn-block" id="addcart-<?= $p['id'] ?>">
                        🛒 Tambah ke Keranjang
                    </button>
                </form>
                <?php elseif (!isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block">
                    🛒 Beli Sekarang
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-block">
                    👁️ Lihat Detail
                </a>
                <?php endif; ?>
                <?php else: ?>
                <button class="btn btn-secondary btn-block" disabled>Stok Habis</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- ===== WHY ELECTROMART ===== -->
<section class="section" style="padding-top: 0; margin: 0 -24px; padding-left: 24px; padding-right: 24px; background: white; border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200);">
    <div style="max-width: var(--container-max); margin: 0 auto;">
        <div class="section-header" style="margin-bottom:40px">
            <div>
                <h2 class="section-title">Mengapa ELECTROMART?</h2>
                <p class="section-sub">Keunggulan kami untuk mahasiswa</p>
            </div>
        </div>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">💰</div>
                <div class="benefit-title">Harga Mahasiswa</div>
                <p class="benefit-desc">Harga transparan dan kompetitif, dirancang khusus untuk budget mahasiswa kampus.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">✅</div>
                <div class="benefit-title">Produk Terpilih</div>
                <p class="benefit-desc">Setiap produk dikurasi dan direkomendasikan oleh tim ahli elektronik kami.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🚀</div>
                <div class="benefit-title">Proses Cepat</div>
                <p class="benefit-desc">Pemesanan mudah dan cepat, status pesanan dapat dipantau real-time.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🔒</div>
                <div class="benefit-title">Belanja Aman</div>
                <p class="benefit-desc">Sistem keamanan terjamin. Data dan transaksi Anda selalu terlindungi.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA BANNER ===== -->
<?php if (!isLoggedIn()): ?>
<section style="margin: 40px 0; background: linear-gradient(135deg, var(--primary), #1D4ED8); border-radius: var(--radius-xl); padding: 48px 40px; text-align: center; color: white;">
    <h2 style="color:white;font-size:26px;margin-bottom:12px">Mulai Belanja Sekarang — Gratis!</h2>
    <p style="color:rgba(255,255,255,.8);font-size:16px;margin-bottom:28px">
        Daftar dalam 30 detik dan dapatkan akses ke ratusan produk elektronik terbaik.
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-lg" style="background:white;color:var(--primary);font-weight:700" id="ctaRegisterBtn">
            🚀 Daftar Gratis
        </a>
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-lg btn-outline-primary" style="border-color:rgba(255,255,255,.5);color:white" id="ctaBrowseBtn">
            Lihat Produk →
        </a>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
