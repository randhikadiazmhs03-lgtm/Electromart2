<?php
/**
 * ELECTROMART - Admin: Hapus Produk
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setAlert('error', 'ID produk tidak valid.');
    redirect(BASE_URL . '/admin/products/index.php');
}

$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) {
    setAlert('error', 'Produk tidak ditemukan.');
    redirect(BASE_URL . '/admin/products/index.php');
}

// Hapus file gambar lokal jika bukan URL eksternal
if ($product['image'] && !str_starts_with($product['image'], 'http')) {
    $file_path = ROOT_PATH . '/' . ltrim($product['image'], '/');
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
}

// Hapus dari database
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

setAlert('success', 'Produk "' . $product['name'] . '" berhasil dihapus.');
redirect(BASE_URL . '/admin/products/index.php');
