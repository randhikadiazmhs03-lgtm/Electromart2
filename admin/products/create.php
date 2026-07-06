<?php
/**
 * ELECTROMART - Admin: Tambah Produk
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title  = 'Tambah Produk';
$active_menu = 'products';

include __DIR__ . '/../includes/header.php';

$errors = [];
$data   = ['name' => '', 'brand' => '', 'price' => '', 'stock' => '', 'description' => '', 'category_id' => '', 'is_featured' => 0, 'image_url' => ''];

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'        => trim($_POST['name']        ?? ''),
        'brand'       => trim($_POST['brand']       ?? ''),
        'price'       => trim($_POST['price']       ?? ''),
        'stock'       => trim($_POST['stock']       ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'image_url'   => trim($_POST['image_url']   ?? ''),
    ];

    // Validasi
    if (empty($data['name']))  $errors[] = 'Nama produk wajib diisi.';
    if (!is_numeric($data['price']) || $data['price'] < 0) $errors[] = 'Harga harus berupa angka positif.';
    if (!is_numeric($data['stock']) || $data['stock'] < 0) $errors[] = 'Stok harus berupa angka non-negatif.';

    // Handle image upload
    $image_path = $data['image_url']; // default: URL yang diinput

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['image'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format gambar harus JPG, PNG, GIF, atau WEBP.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 5MB.';
        } else {
            // Buat direktori jika belum ada
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            $filename   = uniqid('prod_') . '.' . $ext;
            $dest       = UPLOAD_DIR . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $image_path = 'assets/images/products/' . $filename;
            } else {
                $errors[] = 'Gagal mengupload gambar. Pastikan folder assets/images/products/ dapat ditulis.';
            }
        }
    }

    if (empty($errors)) {
        $price = (float)$data['price'];
        $stock = (int)$data['stock'];
        $cat   = $data['category_id'] ?: null;

        $stmt = $conn->prepare("
            INSERT INTO products (category_id, name, description, price, stock, image, brand, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('issdissi',
            $cat, $data['name'], $data['description'],
            $price, $stock, $image_path, $data['brand'], $data['is_featured']
        );

        if ($stmt->execute()) {
            $stmt->close();
            setAlert('success', 'Produk "' . $data['name'] . '" berhasil ditambahkan!');
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
        <h1>➕ Tambah Produk</h1>
        <p>Tambahkan produk elektronik baru ke katalog</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-secondary">← Kembali</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <span class="alert-icon">✕</span>
    <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="createProductForm">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start">

        <!-- Left: Main Info -->
        <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">📋 Informasi Produk</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required" for="name">Nama Produk</label>
                        <input type="text" name="name" id="name" class="form-control"
                               placeholder="Contoh: Logitech MX Master 3 Wireless Mouse"
                               value="<?= sanitize($data['name']) ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="brand">Merek / Brand</label>
                            <input type="text" name="brand" id="brand" class="form-control"
                                   placeholder="Contoh: Logitech"
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
                        <label class="form-label" for="description">Deskripsi Produk</label>
                        <textarea name="description" id="description" class="form-control" rows="5"
                                  placeholder="Jelaskan spesifikasi, keunggulan, dan detail produk..."><?= sanitize($data['description']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Harga & Stok -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">💰 Harga & Stok</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="price">Harga (Rp)</label>
                            <input type="number" name="price" id="price" class="form-control"
                                   placeholder="0" min="0" step="1000"
                                   value="<?= sanitize($data['price']) ?>" required>
                            <div class="form-hint">Masukkan harga dalam Rupiah, tanpa titik/koma.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="stock">Stok (unit)</label>
                            <input type="number" name="stock" id="stock" class="form-control"
                                   placeholder="0" min="0"
                                   value="<?= sanitize($data['stock']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Image & Options -->
        <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
                <div class="card-header"><h3 class="card-title">🖼️ Gambar Produk</h3></div>
                <div class="card-body">
                    <!-- Upload -->
                    <div class="form-group">
                        <label class="form-label" for="productImage">Upload Gambar</label>
                        <div class="image-upload-wrap" onclick="document.getElementById('productImage').click()">
                            <div style="font-size:32px;margin-bottom:8px">📷</div>
                            <div style="font-size:13px;font-weight:600;color:var(--gray-600)">Klik untuk upload gambar</div>
                            <div style="font-size:12px;color:var(--gray-400);margin-top:4px">JPG, PNG, WEBP · Maks 5MB</div>
                        </div>
                        <input type="file" name="image" id="productImage" accept="image/*" style="display:none">
                        <img id="imagePreview" class="image-preview" alt="Preview gambar">
                    </div>

                    <div class="divider" style="position:relative;text-align:center">
                        <span style="position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:white;padding:0 10px;font-size:12px;color:var(--gray-400)">atau</span>
                    </div>

                    <!-- URL -->
                    <div class="form-group" style="margin-bottom:0;margin-top:16px">
                        <label class="form-label" for="image_url">URL Gambar Eksternal</label>
                        <input type="url" name="image_url" id="image_url" class="form-control"
                               placeholder="https://example.com/image.jpg"
                               value="<?= sanitize($data['image_url']) ?>">
                        <div class="form-hint">Jika upload, URL ini akan diabaikan.</div>
                    </div>
                </div>
            </div>

            <!-- Pengaturan -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">⚙️ Pengaturan</h3></div>
                <div class="card-body">
                    <label class="form-check" style="padding:16px;background:var(--warning-light);border:1.5px solid var(--warning);border-radius:10px;cursor:pointer">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1"
                               <?= $data['is_featured'] ? 'checked' : '' ?>>
                        <div>
                            <div style="font-weight:700;color:#92400E">⭐ Produk Unggulan</div>
                            <div style="font-size:12px;color:#B45309;margin-top:2px">Tampil di halaman utama & ditandai bintang</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Action buttons -->
            <button type="submit" class="btn btn-primary btn-lg btn-block" id="submitProductBtn">
                ✅ Simpan Produk
            </button>
            <a href="<?= BASE_URL ?>/admin/products/index.php" class="btn btn-secondary btn-block">
                Batal
            </a>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
