<?php
/**
 * ELECTROMART - Daftar Produk
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- Filter parameters ---
$search   = trim($_GET['q']    ?? '');
$cat_id   = (int)($_GET['cat'] ?? 0);
$sort     = $_GET['sort'] ?? 'latest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

// --- Build WHERE clause ---
$where  = ["p.stock >= 0"];
$params = [];
$types  = '';

if ($search) {
    $where[]  = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $like     = "%{$search}%";
    $params[] = &$like; $params[] = &$like; $params[] = &$like;
    $types   .= 'sss';
}
if ($cat_id > 0) {
    $where[]  = "p.category_id = ?";
    $params[] = &$cat_id;
    $types   .= 'i';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// --- Sort ---
$order_map = [
    'latest'     => 'p.created_at DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
];
$order_sql = $order_map[$sort] ?? 'p.created_at DESC';

// --- Count total ---
$count_sql  = "SELECT COUNT(*) FROM products p $where_sql";
$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$stmt_count->bind_result($total_products);
$stmt_count->fetch();
$stmt_count->close();
$total_pages = max(1, ceil($total_products / $per_page));

// --- Fetch products ---
$sql  = "SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_sql ORDER BY $order_sql LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

$limit_param  = $per_page;
$offset_param = $offset;

if (!empty($params)) {
    $new_types  = $types . 'ii';
    $new_params = array_values($params);
    // Re-build params without references for final bind
    $final_params = array_merge(array_map(fn($p) => $p, [
        ...array_map(fn($k) => $_GET[$k] ?? '', ['q','q','q']),
    ], []), [$limit_param, $offset_param]);
}
// Simpler approach: direct query with sprintf for safety
$clean_search = $conn->real_escape_string($search);
$where_direct = "WHERE 1=1";
if ($clean_search) $where_direct .= " AND (p.name LIKE '%{$clean_search}%' OR p.brand LIKE '%{$clean_search}%' OR p.description LIKE '%{$clean_search}%')";
if ($cat_id > 0) $where_direct .= " AND p.category_id = {$cat_id}";

$products = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $where_direct
    ORDER BY $order_sql
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Recount with cleaned where
$total_products = $conn->query("SELECT COUNT(*) FROM products p $where_direct")->fetch_row()[0];
$total_pages    = max(1, ceil($total_products / $per_page));

// --- Categories for filter ---
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// --- Current category name ---
$current_cat_name = '';
if ($cat_id > 0) {
    foreach ($categories as $c) {
        if ($c['id'] == $cat_id) { $current_cat_name = $c['name']; break; }
    }
}

// Build URL for pagination
function buildUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    unset($params['page']); // page handled separately
    return '?' . http_build_query(array_filter($params));
}

$page_title = $search ? "Hasil: \"$search\"" : ($current_cat_name ?: 'Semua Produk');
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-center justify-between gap-4" style="margin-bottom:24px">
    <div>
        <h1 style="font-size:24px;margin-bottom:4px">
            <?= $search ? '🔍 Hasil pencarian: "' . sanitize($search) . '"' : ($current_cat_name ? $categories[array_search($cat_id, array_column($categories,'id'))]['icon'] . ' ' . sanitize($current_cat_name) : '🛍️ Semua Produk') ?>
        </h1>
        <p class="text-muted text-sm"><?= $total_products ?> produk ditemukan</p>
    </div>
    <!-- Sort -->
    <form method="GET" id="sortForm" style="display:flex;align-items:center;gap:8px">
        <?php if ($search): ?><input type="hidden" name="q" value="<?= sanitize($search) ?>"><?php endif; ?>
        <?php if ($cat_id):  ?><input type="hidden" name="cat" value="<?= $cat_id ?>"><?php endif; ?>
        <label for="sortSelect" class="text-sm text-muted fw-600">Urutkan:</label>
        <select name="sort" id="sortSelect" class="form-control" style="width:auto;padding:8px 32px 8px 12px;font-size:13px" onchange="this.form.submit()">
            <option value="latest"     <?= $sort === 'latest'     ? 'selected' : '' ?>>Terbaru</option>
            <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Harga Terendah</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
            <option value="name"       <?= $sort === 'name'       ? 'selected' : '' ?>>Nama A-Z</option>
        </select>
    </form>
</div>

<div class="products-layout">
    <!-- Sidebar Filter -->
    <aside class="filter-sidebar" aria-label="Filter produk">
        <form method="GET" id="filterForm">
            <?php if ($search): ?><input type="hidden" name="q" value="<?= sanitize($search) ?>"><?php endif; ?>
            <input type="hidden" name="sort" value="<?= sanitize($sort) ?>">

            <div class="filter-title">🔧 Filter</div>

            <!-- Kategori -->
            <div class="filter-group">
                <div class="filter-group-label">Kategori</div>
                <label class="filter-option">
                    <input type="radio" name="cat" value="0" <?= $cat_id == 0 ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
                    <span>Semua Kategori</span>
                </label>
                <?php foreach ($categories as $c): ?>
                <label class="filter-option">
                    <input type="radio" name="cat" value="<?= $c['id'] ?>"
                           <?= $cat_id == $c['id'] ? 'checked' : '' ?>
                           onchange="document.getElementById('filterForm').submit()">
                    <span><?= $c['icon'] ?> <?= sanitize($c['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Reset -->
            <?php if ($search || $cat_id): ?>
            <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-secondary btn-sm btn-block">✕ Reset Filter</a>
            <?php endif; ?>
        </form>
    </aside>

    <!-- Products Area -->
    <div>
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <div class="empty-title">Produk tidak ditemukan</div>
            <p class="empty-desc">Coba kata kunci lain atau hapus filter.</p>
            <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-primary" style="margin-top:16px">Lihat Semua Produk</a>
        </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card" id="product-<?= $p['id'] ?>">
                <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $p['id'] ?>" style="text-decoration:none;display:contents">
                    <div class="product-card-image">
                        <img src="<?= getProductImage($p['image'], $p['name']) ?>"
                             alt="<?= sanitize($p['name']) ?>"
                             loading="lazy"
                             onerror="this.src='https://via.placeholder.com/400x300/EFF6FF/2563EB?text=No+Image'">
                        <?php if ($p['is_featured']): ?><span class="product-badge featured">Unggulan</span><?php endif; ?>
                        <?php if ($p['stock'] == 0): ?><span class="product-badge" style="background:var(--danger)">Habis</span><?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <div class="product-brand"><?= sanitize($p['brand'] ?? '') ?></div>
                        <div class="product-name"><?= sanitize($p['name']) ?></div>
                        <div class="product-price"><?= formatPrice($p['price']) ?></div>
                        <div class="product-stock">
                            <?php if ($p['stock'] > 10): ?>
                            <span class="stock-dot"></span> Stok <?= $p['stock'] ?>
                            <?php elseif ($p['stock'] > 0): ?>
                            <span class="stock-dot low"></span> Sisa <?= $p['stock'] ?>
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
                        <button type="submit" class="btn btn-primary btn-block btn-sm">🛒 Tambah</button>
                    </form>
                    <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block btn-sm">🛒 Beli</a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-block btn-sm">Detail</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <button class="btn btn-secondary btn-block btn-sm" disabled>Stok Habis</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="pagination" aria-label="Navigasi halaman">
            <?php if ($page > 1): ?>
            <a href="<?= buildUrl() ?>&page=<?= $page - 1 ?>" class="page-link" aria-label="Sebelumnya">‹</a>
            <?php else: ?>
            <span class="page-link disabled">‹</span>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="<?= buildUrl() ?>&page=<?= $i ?>"
               class="page-link <?= $i === $page ? 'active' : '' ?>"
               aria-label="Halaman <?= $i ?>"
               <?= $i === $page ? 'aria-current="page"' : '' ?>>
               <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <a href="<?= buildUrl() ?>&page=<?= $page + 1 ?>" class="page-link" aria-label="Selanjutnya">›</a>
            <?php else: ?>
            <span class="page-link disabled">›</span>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
