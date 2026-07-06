<?php
/**
 * ELECTROMART - Admin Layout Header
 * Di-include di setiap halaman admin
 *
 * Variabel yang harus di-set sebelum include:
 *   $page_title   string  judul halaman
 *   $active_menu  string  key menu aktif (dashboard|products|orders|users)
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/db.php';
}
require_once __DIR__ . '/../../includes/functions.php';

// Pastikan user sudah login dan adalah admin
requireAdmin();

$admin_user  = $_SESSION['user'];
$page_title  = $page_title ?? 'Admin';
$active_menu = $active_menu ?? '';

// Breadcrumb mapping
$breadcrumb_map = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => '📊'],
    'products'  => ['label' => 'Produk',    'icon' => '📦'],
    'orders'    => ['label' => 'Pesanan',   'icon' => '🛒'],
    'users'     => ['label' => 'Pengguna',  'icon' => '👥'],
];
$current_bc = $breadcrumb_map[$active_menu] ?? ['label' => $page_title, 'icon' => '⚙️'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($page_title) ?> | Admin — ELECTROMART</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>var BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>
<div class="admin-wrapper">

    <!-- ===== SIDEBAR ===== -->
    <aside class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="Admin navigation">

        <div class="sidebar-logo">
            <div class="sidebar-logo-icon" aria-hidden="true">⚡</div>
            <div>
                <div class="sidebar-logo-name">ELECTROMART</div>
                <div class="sidebar-logo-sub">Admin Panel</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <!-- Menu Utama -->
            <div class="sidebar-nav-section">
                <span class="sidebar-nav-title">Menu Utama</span>

                <a href="<?= BASE_URL ?>/admin/dashboard.php"
                   class="sidebar-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>"
                   aria-current="<?= $active_menu === 'dashboard' ? 'page' : 'false' ?>">
                    <span class="sidebar-link-icon">📊</span> Dashboard
                </a>

                <a href="<?= BASE_URL ?>/admin/products/index.php"
                   class="sidebar-link <?= $active_menu === 'products' ? 'active' : '' ?>"
                   aria-current="<?= $active_menu === 'products' ? 'page' : 'false' ?>">
                    <span class="sidebar-link-icon">📦</span> Produk
                </a>

                <a href="<?= BASE_URL ?>/admin/orders/index.php"
                   class="sidebar-link <?= $active_menu === 'orders' ? 'active' : '' ?>"
                   aria-current="<?= $active_menu === 'orders' ? 'page' : 'false' ?>">
                    <span class="sidebar-link-icon">🛒</span> Pesanan
                </a>

                <a href="<?= BASE_URL ?>/admin/users/index.php"
                   class="sidebar-link <?= $active_menu === 'users' ? 'active' : '' ?>"
                   aria-current="<?= $active_menu === 'users' ? 'page' : 'false' ?>">
                    <span class="sidebar-link-icon">👥</span> Pengguna
                </a>
            </div>

            <!-- Lainnya -->
            <div class="sidebar-nav-section">
                <span class="sidebar-nav-title">Lainnya</span>
                <a href="<?= BASE_URL ?>/index.php" class="sidebar-link" target="_blank">
                    <span class="sidebar-link-icon">🌐</span> Lihat Toko
                </a>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link" style="color:#F87171">
                    <span class="sidebar-link-icon">🚪</span> Logout
                </a>
            </div>
        </nav>

        <!-- User Info -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= strtoupper(mb_substr($admin_user['name'], 0, 2)) ?></div>
                <div style="overflow:hidden">
                    <div class="sidebar-user-name"><?= sanitize($admin_user['name']) ?></div>
                    <div class="sidebar-user-role">Administrator</div>
                </div>
            </div>
        </div>

    </aside>
    <!-- ===== END SIDEBAR ===== -->

    <!-- ===== MAIN AREA ===== -->
    <div class="admin-main">

        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <!-- Mobile sidebar toggle -->
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <span></span><span></span><span></span>
                </button>
                <!-- Breadcrumb -->
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a>
                    <span class="sep" aria-hidden="true">/</span>
                    <span class="current"><?= $current_bc['icon'] ?> <?= sanitize($current_bc['label']) ?></span>
                </nav>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:13px;color:var(--gray-500)">
                    <?= date('d M Y, H:i') ?> WIB
                </span>
                <div style="display:flex;align-items:center;gap:8px;padding:6px 12px;background:var(--gray-50);border-radius:var(--radius-full);border:1px solid var(--gray-200)">
                    <div style="width:26px;height:26px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white">
                        <?= strtoupper(mb_substr($admin_user['name'], 0, 2)) ?>
                    </div>
                    <span style="font-size:13px;font-weight:600;color:var(--gray-700)"><?= sanitize(explode(' ', $admin_user['name'])[0]) ?></span>
                </div>
            </div>
        </header>
        <!-- End Topbar -->

        <!-- Admin Content -->
        <main class="admin-content" id="adminContent">
