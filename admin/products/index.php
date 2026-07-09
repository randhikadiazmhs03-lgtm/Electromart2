<?php
/**
 * ELECTROMART - Admin: Daftar Produk
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title  = 'Kelola Produk';
$active_menu = 'products';

include __DIR__ . '/../includes/header.php';

// Filter
$search  = trim($_GET['q']    ?? '');
$cat_id  = (int)($_GET['cat'] ?? 0);

$where = "WHERE 1=1";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (p.name LIKE '%$s%' OR p.brand LIKE '%$s%')";
}
if ($cat_id) $where .= " AND p.category_id = $cat_id";

$products = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <div>
        <h1>📦 Kelola Produk</h1>
        <p><?= count($products) ?> produk ditemukan</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/products/create.php" class="btn btn-primary" id="addProductBtn">
        + Tambah Produk
    </a>
</div>

<?php showAlert(); ?>

<!-- Filter bar -->
<form method="GET" class="filter-bar">
    <div class="search-bar">
        <span class="icon">🔍</span>
        <input type="text" name="q" id="productSearch" placeholder="Cari produk…"
               value="<?= sanitize($search) ?>" autocomplete="off">
    </div>
    <select name="cat" id="categoryFilter" class="form-control" style="width:auto;padding:9px 36px 9px 12px;font-size:14px">
        <option value="0">Semua Kategori</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>>
            <?= $c['icon'] ?> <?= sanitize($c['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $cat_id): ?>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-ghost btn-sm">✕ Reset</a>
    <?php endif; ?>
</form>

<?php if (empty($products)): ?>
<div class="card">
    <div class="empty-state" style="padding:64px">
        <div class="empty-icon">📦</div>
        <div class="empty-title">Tidak ada produk</div>
        <a href="<?= BASE_URL ?>/admin/products/create.php" class="btn btn-primary" style="margin-top:16px">Tambah Produk Pertama</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrapper" style="border:none;border-radius:0">
        <table>
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Unggulan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr id="product-row-<?= $p['id'] ?>">
                    <td>
                        <img src="<?= getProductImage($p['image'], $p['name']) ?>"
                             alt="<?= sanitize($p['name']) ?>"
                             class="table-img"
                             onerror="this.src='https://via.placeholder.com/48/EFF6FF/2563EB?text=IMG'">
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--gray-900);max-width:220px"><?= sanitize($p['name']) ?></div>
                        <div style="font-size:12px;color:var(--gray-400)"><?= sanitize($p['brand'] ?? '') ?></div>
                    </td>
                    <td><span class="badge badge-secondary"><?= sanitize($p['cat_name'] ?? 'Tanpa Kategori') ?></span></td>
                    <td class="fw-700 nowrap"><?= formatPrice($p['price']) ?></td>
                    <td>
                        <?php if ($p['stock'] == 0): ?>
                        <span class="badge badge-danger">Habis</span>
                        <?php elseif ($p['stock'] <= 5): ?>
                        <span class="badge badge-warning"><?= $p['stock'] ?> unit</span>
                        <?php else: ?>
                        <span class="text-sm fw-600"><?= $p['stock'] ?> unit</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                            <input type="checkbox"
                                   class="featured-toggle"
                                   data-id="<?= $p['id'] ?>"
                                   <?= $p['is_featured'] ? 'checked' : '' ?>
                                   style="width:16px;height:16px;accent-color:var(--warning)">
                            <span style="font-size:12px;color:var(--gray-500)"><?= $p['is_featured'] ? '⭐' : '' ?></span>
                        </label>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="<?= BASE_URL ?>/admin/products/edit.php?id=<?= $p['id'] ?>"
                               class="btn btn-warning btn-sm" title="Edit">✏️</a>
                            <a href="<?= BASE_URL ?>/admin/products/delete.php?id=<?= $p['id'] ?>"
                               class="btn btn-danger btn-sm"
                               data-confirm-delete
                               data-name="<?= sanitize($p['name']) ?>"
                               data-item-type="Produk"
                               title="Hapus">🗑️</a>
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
