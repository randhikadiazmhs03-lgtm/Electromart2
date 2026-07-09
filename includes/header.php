<?php
/**
 * ELECTROMART - Public Header / Navbar
 * Di-include oleh semua halaman publik dan user
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/functions.php';

$cart_count  = isLoggedIn() ? getCartCount($conn) : 0;
$auth_user   = $_SESSION['user'] ?? null;
$page_title  = $page_title ?? 'ELECTROMART';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ELECTROMART — Platform belanja elektronik terpercaya untuk mahasiswa kampus. Laptop, gadget, dan aksesori dengan harga terbaik.">
    <title><?= sanitize($page_title) ?> | ELECTROMART</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>var BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="container navbar-inner">

        <!-- Brand -->
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand" aria-label="ELECTROMART Home">
            <div class="brand-icon" aria-hidden="true">⚡</div>
            ELECTRO<span class="brand-em">MART</span>
        </a>

        <!-- Search bar -->
        <div class="navbar-search" id="navbarSearch">
            <form action="<?= BASE_URL ?>/products/index.php" method="GET" role="search">
                <span class="search-icon" aria-hidden="true">🔍</span>
                <input
                    type="search"
                    name="q"
                    id="mainSearch"
                    placeholder="Cari laptop, mouse, headset…"
                    value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>"
                    autocomplete="off"
                    aria-label="Cari produk"
                >
            </form>
        </div>

        <!-- Actions -->
        <div class="navbar-actions">

            <!-- Mobile search toggle -->
            <button class="btn btn-ghost" id="searchToggle" aria-label="Toggle pencarian" style="display:flex;padding:8px">
                🔍
            </button>

            <!-- Cart -->
            <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/user/cart.php" class="cart-btn" id="cartBtn" aria-label="Keranjang belanja">
                🛒
                <?php if ($cart_count > 0): ?>
                <span class="cart-badge" aria-label="<?= $cart_count ?> item"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <!-- User menu or Auth buttons -->
            <?php if ($auth_user): ?>
            <div class="user-menu" id="userMenuWrap">
                <button class="user-menu-toggle" id="userMenuToggle" aria-expanded="false" aria-haspopup="true">
                    <div class="user-avatar" aria-hidden="true"><?= strtoupper(mb_substr($auth_user['name'], 0, 2)) ?></div>
                    <span><?= sanitize(explode(' ', $auth_user['name'])[0]) ?></span>
                    <span aria-hidden="true">▾</span>
                </button>
                <div class="user-dropdown" id="userDropdown" role="menu">
                    <div class="dropdown-header">👋 Halo, <?= sanitize(explode(' ', $auth_user['name'])[0]) ?>!</div>

                    <?php if (isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="dropdown-item" role="menuitem">🏠 Dashboard Admin</a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="dropdown-item" role="menuitem">🏠 Dashboard</a>
                    <a href="<?= BASE_URL ?>/user/orders.php"    class="dropdown-item" role="menuitem">📦 Pesanan Saya</a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/user/profile.php" class="dropdown-item" role="menuitem">👤 Profil Saya</a>
                    <div class="dropdown-divider" role="separator"></div>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item danger" role="menuitem">🚪 Logout</a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php"    class="btn-auth btn-login"    id="loginBtn">Masuk</a>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn-auth btn-register" id="registerBtn">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- ============ END NAVBAR ============ -->

<main class="main-content">
<div class="container">
