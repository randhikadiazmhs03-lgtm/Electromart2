<?php
/**
 * ELECTROMART - Logout Handler
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Hapus semua session data
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Mulai session baru hanya untuk flash message
session_start();
setAlert('success', 'Anda telah berhasil logout. Sampai jumpa! 👋');

header('Location: ' . BASE_URL . '/auth/login.php');
exit;
