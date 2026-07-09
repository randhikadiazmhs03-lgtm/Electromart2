<?php
/**
 * ELECTROMART - Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
   AUTH HELPERS
===================================================== */

function isLoggedIn(): bool {
    return isset($_SESSION['user']['id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user']['role'] === 'admin');
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setAlert('warning', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setAlert('error', 'Akses ditolak. Halaman ini hanya untuk Administrator.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/* =====================================================
   FLASH MESSAGES
===================================================== */

function setAlert(string $type, string $message): void {
    $_SESSION['alert'] = compact('type', 'message');
}

function showAlert(): void {
    if (!isset($_SESSION['alert'])) return;
    $alert   = $_SESSION['alert'];
    $type    = $alert['type'];
    $message = htmlspecialchars($alert['message']);
    unset($_SESSION['alert']);

    $icons = [
        'success' => '✓', 'error' => '✕', 'danger' => '✕',
        'warning' => '⚠', 'info' => 'ℹ',
    ];
    $icon      = $icons[$type] ?? 'ℹ';
    $typeClass = ($type === 'error') ? 'danger' : $type;

    echo "<div class='alert alert-{$typeClass}' role='alert'>"
       . "<span class='alert-icon'>{$icon}</span>"
       . "<span>{$message}</span>"
       . "<button onclick='this.parentElement.remove()' class='alert-close' aria-label='Tutup'>×</button>"
       . "</div>";
}

/* =====================================================
   FORMATTING
===================================================== */

function formatPrice(float|int $price): string {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}

function truncate(string $text, int $length = 120): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'Baru saja';
    if ($diff < 3600)    return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)   return floor($diff / 3600) . ' jam lalu';
    if ($diff < 172800)  return 'Kemarin';
    if ($diff < 604800)  return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}

function getProductImage(?string $image, string $name = 'Produk'): string {
    if (empty($image)) {
        return 'https://via.placeholder.com/400x300/EFF6FF/2563EB?text=' . urlencode($name);
    }
    // URL eksternal
    if (str_starts_with($image, 'http')) {
        return htmlspecialchars($image);
    }
    // File upload lokal
    return BASE_URL . '/' . ltrim(htmlspecialchars($image), '/');
}

/* =====================================================
   STATUS BADGE
===================================================== */

function getStatusBadge(string $status): string {
    $map = [
        'pending'    => ['Menunggu',   'badge-warning'],
        'processing' => ['Diproses',   'badge-info'],
        'shipped'    => ['Dikirim',    'badge-primary'],
        'delivered'  => ['Selesai',    'badge-success'],
        'cancelled'  => ['Dibatalkan', 'badge-danger'],
    ];
    [$label, $class] = $map[$status] ?? [ucfirst($status), 'badge-secondary'];
    return "<span class='badge {$class}'>{$label}</span>";
}

/* =====================================================
   CART
===================================================== */

function getCartCount($conn): int {
    if (!isLoggedIn()) return 0;
    $uid  = (int) $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['total'] ?? 0);
}

/* =====================================================
   REDIRECT
===================================================== */

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
