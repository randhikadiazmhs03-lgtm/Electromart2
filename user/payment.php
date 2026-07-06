<?php
/**
 * ELECTROMART - Upload Bukti Pembayaran
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
if (isAdmin()) redirect(BASE_URL . '/index.php');

$uid = (int)$_SESSION['user']['id'];
$oid = (int)($_GET['order_id'] ?? 0);

if (!$oid) {
    redirect(BASE_URL . '/user/orders.php');
}

// Fetch order
$order = $conn->query("
    SELECT * FROM orders 
    WHERE id = $oid AND user_id = $uid
")->fetch_assoc();

if (!$order) {
    setAlert('error', 'Pesanan tidak ditemukan.');
    redirect(BASE_URL . '/user/orders.php');
}

if ($order['payment_method'] === 'cod') {
    setAlert('info', 'Metode pembayaran COD tidak memerlukan bukti transfer.');
    redirect(BASE_URL . '/user/orders.php?id=' . $oid);
}

if ($order['payment_status'] === 'verified') {
    setAlert('success', 'Pembayaran untuk pesanan ini sudah diverifikasi.');
    redirect(BASE_URL . '/user/orders.php?id=' . $oid);
}

$errors = [];

/* ── Handle Upload ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['proof'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format gambar harus JPG, PNG, atau WEBP.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 5MB.';
        } else {
            $upload_dir = ROOT_PATH . '/assets/images/payments/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $filename = uniqid('pay_') . '_' . $oid . '.' . $ext;
            $dest     = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $proof_path = 'assets/images/payments/' . $filename;
                
                $s = $conn->prepare("UPDATE orders SET payment_proof = ?, payment_status = 'paid' WHERE id = ?");
                $s->bind_param('si', $proof_path, $oid);
                $s->execute(); $s->close();
                
                setAlert('success', 'Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
                redirect(BASE_URL . '/user/orders.php?id=' . $oid);
            } else {
                $errors[] = 'Gagal mengupload gambar. Silakan coba lagi.';
            }
        }
    } else {
        $errors[] = 'Silakan pilih file gambar bukti pembayaran terlebih dahulu.';
    }
}

$page_title = 'Pembayaran Pesanan';
include __DIR__ . '/../includes/header.php';
?>

<div style="max-width:600px;margin:0 auto">
    
    <div class="d-flex align-center" style="gap:12px;margin-bottom:28px">
        <a href="<?= BASE_URL ?>/user/orders.php" class="btn btn-secondary btn-sm">← Kembali</a>
        <a href="<?= BASE_URL ?>/index.php"       class="btn btn-ghost btn-sm">🏠 Beranda</a>
    </div>

    <div style="text-align:center;margin-bottom:28px">
        <h1 style="font-size:24px;font-weight:800;margin-bottom:8px">💳 Pembayaran Pesanan</h1>
        <p class="text-muted">Selesaikan pembayaran untuk pesanan #ORD<?= str_pad($oid,4,'0',STR_PAD_LEFT) ?></p>
    </div>

    <?php showAlert(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">✕</span>
        <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:24px">
        <div class="card-body" style="text-align:center">
            <div style="font-size:14px;color:var(--gray-500);margin-bottom:4px">Total Tagihan</div>
            <div style="font-size:32px;font-weight:800;color:var(--primary);margin-bottom:20px">
                <?= formatPrice($order['total_price']) ?>
            </div>
            
            <div style="background:var(--gray-50);padding:16px;border-radius:12px;border:1px solid var(--gray-200);text-align:left">
                <?php if ($order['payment_method'] === 'qris'): ?>
                <div style="text-align:center">
                    <div style="font-weight:700;margin-bottom:12px;font-size:16px">📱 Scan QRIS</div>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=ELECTROMART-PAYMENT-<?= $oid ?>&color=2563EB&bgcolor=EFF6FF"
                         alt="QRIS ELECTROMART"
                         style="width:200px;height:200px;border-radius:12px;display:inline-block;border:2px solid white;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)">
                    <div style="font-size:13px;color:var(--gray-500);margin-top:12px">Buka aplikasi dompet digital atau m-banking Anda, lalu scan QR code di atas.</div>
                </div>
                <?php else: 
                    $bank = strtoupper(str_replace('transfer_', '', $order['payment_method']));
                    $accs = ['BCA' => '1234567890', 'BRI' => '0987654321', 'MANDIRI' => '1122334455', 'BNI' => '5544332211'];
                ?>
                <div style="display:flex;align-items:center;gap:16px">
                    <div style="width:48px;height:48px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;border:1px solid var(--gray-200)">
                        🏦
                    </div>
                    <div>
                        <div style="font-size:13px;color:var(--gray-500)">Transfer ke Bank <?= $bank ?></div>
                        <div style="font-size:18px;font-weight:800;font-family:monospace;margin:4px 0;letter-spacing:1px"><?= $accs[$bank] ?? '-' ?></div>
                        <div style="font-size:13px;font-weight:600">a.n. ELECTROMART</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">📎 Upload Bukti Pembayaran</h3></div>
        <div class="card-body">
            <?php if ($order['payment_status'] === 'paid'): ?>
            <div class="alert alert-warning" style="margin-bottom:16px">
                <span class="alert-icon">⏳</span>
                <span>Anda sudah mengupload bukti pembayaran. Menunggu verifikasi dari admin. Jika ingin mengganti, silakan upload ulang di bawah.</span>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label required" for="proofImage">Pilih Gambar</label>
                    <div class="image-upload-wrap" onclick="document.getElementById('proofImage').click()">
                        <div style="font-size:32px;margin-bottom:8px">📸</div>
                        <div style="font-size:14px;font-weight:600;color:var(--gray-700)">Klik untuk memilih foto/screenshot</div>
                        <div style="font-size:12px;color:var(--gray-400);margin-top:4px">JPG, PNG, WEBP · Maks 5MB</div>
                    </div>
                    <input type="file" name="proof" id="proofImage" accept="image/*" style="display:none" required>
                    <img id="imagePreview" class="image-preview" alt="Preview bukti" style="margin-top:16px">
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:24px">
                    🚀 Kirim Bukti Pembayaran
                </button>
            </form>
        </div>
    </div>

</div>

<script>
document.getElementById('proofImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        preview.src = '';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
