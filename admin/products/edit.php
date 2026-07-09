<?php
/**
 * ELECTROMART - Admin: Edit Produk
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title  = 'Edit Produk';
$active_menu = 'products';

include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setAlert('error', 'Produk tidak ditemukan.');
    redirect(BASE_URL . '/admin/products/index.php');
}

// Fetch product
$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) {
    setAlert('error', 'Produk tidak ditemukan.');
    redirect(BASE_URL . '/admin/products/index.php');
}

$errors     = [];
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Pre-fill data from product
$data = [
    'name'        => $product['name'],
    'brand'       => $product['brand']       ?? '',
    'price'       => $product['price'],
    'stock'       => $product['stock'],
    'description' => $product['description'] ?? '',
    'category_id' => $product['category_id'] ?? 0,
    'is_featured' => $product['is_featured'],
    'image'       => $product['image']       ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'        => trim($_POST['name']        ?? ''),
        'brand'       => trim($_POST['brand']       ?? ''),
        'price'       => trim($_POST['price']       ?? ''),
        'stock'       => trim($_POST['stock']       ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'image'       => $product['image'], // default: existing image
    ];

    $new_image_url = trim($_POST['image_url'] ?? '');

    if (empty($data['name']))  $errors[] = 'Nama produk wajib diisi.';
    if (!is_numeric($data['price']) || $data['price'] < 0) $errors[] = 'Harga harus berupa angka positif.';
    if (!is_numeric($data['stock']) || $data['stock'] < 0) $errors[] = 'Stok harus berupa angka non-negatif.';

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['image'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format gambar harus JPG, PNG, GIF, atau WEBP.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 5MB.';
        } else {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $filename = uniqid('prod_') . '.' . $ext;
            $dest     = UPLOAD_DIR . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Hapus gambar lama jika bukan URL eksternal
                if ($data['image'] && !str_starts_with($data['image'], 'http') && file_exists(ROOT_PATH . '/' . $data['image'])) {
                    @unlink(ROOT_PATH . '/' . $data['image']);
                }
                $data['image'] = 'assets/images/products/' . $filename;
            } else {
                $errors[] = 'Gagal mengupload gambar.';
            }
        }
    } elseif (!empty($new_image_url)) {
        $data['image'] = $new_image_url;
    }

    if (empty($errors)) {
        $price = (float)$data['price'];
        $stock = (int)$data['stock'];
        $cat   = $data['category_id'] ?: null;

        $stmt = $conn->prepare("
            UPDATE products
            SET category_id=?, name=?, description=?, price=?, stock=?, image=?, brand=?, is_featured=?
            WHERE id=?
        ");
        $stmt->bind_param('issdissii',
            $cat, $data['name'], $data['description'],
            $price, $stock, $data['image'], $data['brand'],
            $data['is_featured'], $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            setAlert('success', 'Produk "' . $data['name'] . '" berhasil diperbarui!');
            redirect(BASE_URL . '/admin/products/index.php');
        } else {
            $errors[] = 'Terjadi kesalahan database: ' . $conn->error;
            $stmt->close();
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>✏️ Edit Produk</h1>
        <p>ID: #PRD<?= str_pad($id,4,'0',STR_PAD_LEFT) ?> — <?= sanitize($product['name']) ?></p>
    </div>
    <div style="display:flex;gap:12px">
        <a href="<?= BASE_URL ?>/products/detail.php?id=<?= $id ?>" class="btn btn-secondary btn-sm" target="_blank">👁️ Lihat</a>
        <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-secondary">← Kembali</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <span class="alert-icon">✕</span>
    <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="editProductForm">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start">

        <!-- Left -->
        <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">📋 Informasi Produk</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="name">Nama Produk</label>
                        <input type="text" name="name" id="name" class="form-control"
                               value="<?= sanitize($data['name']) ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="brand">Merek / Brand</label>
                            <input type="text" name="brand" id="brand" class="form-control"
                                   value="<?= sanitize($data['brand']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="category_id">Kategori</label>
                            <select name="category_id" id="category_id" class="form-control">
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $data['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                    <?= $c['icon'] ?> <?= sanitize($c['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="form-control" rows="5"><?= sanitize($data['description']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">💰 Harga & Stok</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="price">Harga (Rp)</label>
                            <input type="number" name="price" id="price" class="form-control"
                                   min="0" step="1000" value="<?= $data['price'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="stock">Stok (unit)</label>
                            <input type="number" name="stock" id="stock" class="form-control"
                                   min="0" value="<?= $data['stock'] ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right -->
        <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">🖼️ Gambar Produk</h3></div>
                <div class="card-body">
                    <!-- Current image -->
                    <?php if ($data['image']): ?>
                    <div style="margin-bottom:16px;text-align:center">
                        <img src="<?= getProductImage($data['image']) ?>"
                             alt="Gambar saat ini"
                             style="max-width:100%;max-height:160px;object-fit:contain;border-radius:10px;border:1px solid var(--gray-200)"
                             id="imagePreview"
                             onerror="this.style.display='none'">
                        <div style="font-size:12px;color:var(--gray-400);margin-top:6px">Gambar saat ini</div>
                    </div>
                    <?php else: ?>
                    <img id="imagePreview" class="image-preview" alt="Preview">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="productImage">Ganti Gambar (Opsional)</label>
                        <div class="image-upload-wrap" onclick="document.getElementById('productImage').click()">
                            <div style="font-size:24px;margin-bottom:6px">📷</div>
                            <div style="font-size:13px;font-weight:600;color:var(--gray-600)">Klik untuk pilih gambar baru</div>
                            <div style="font-size:12px;color:var(--gray-400);margin-top:2px">JPG, PNG, WEBP · Maks 5MB</div>
                        </div>
                        <input type="file" name="image" id="productImage" accept="image/*" style="display:none">
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="image_url">atau Ganti dengan URL</label>
                        <input type="url" name="image_url" id="image_url" class="form-control"
                               placeholder="https://example.com/image.jpg"
                               value="<?= !str_starts_with($data['image'], 'http') ? '' : sanitize($data['image']) ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">⚙️ Pengaturan</h3></div>
                <div class="card-body">
                    <label class="form-check" style="padding:16px;background:var(--warning-light);border:1.5px solid var(--warning);border-radius:10px;cursor:pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= $data['is_featured'] ? 'checked' : '' ?>>
                        <div>
                            <div style="font-weight:700;color:#92400E">⭐ Produk Unggulan</div>
                            <div style="font-size:12px;color:#B45309;margin-top:2px">Tampil di halaman utama</div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" id="updateProductBtn">
                💾 Simpan Perubahan
            </button>
            <a href="<?= BASE_URL ?>/admin/products/delete.php?id=<?= $id ?>"
               class="btn btn-danger btn-block"
               data-confirm-delete
               data-name="<?= sanitize($product['name']) ?>"
               data-item-type="Produk">
               🗑️ Hapus Produk
            </a>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
