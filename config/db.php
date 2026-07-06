<?php
/**
 * ELECTROMART - Konfigurasi Database
 * Ubah DB_USER / DB_PASS sesuai pengaturan XAMPP Anda
 */

// Load environment variables dari .env jika ada
if (file_exists(__DIR__ . '/../.env')) {
    $env_lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2) + ['', ''];
        $_ENV[trim($key)] = trim($value);
    }
}

// Gunakan environment variable atau fallback ke default
define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'electromart');

// Base URL aplikasi (tanpa trailing slash)
define('BASE_URL', $_ENV['BASE_URL'] ?? getenv('BASE_URL') ?? 'http://localhost/UASWeb');

// Path absolut root project
define('ROOT_PATH', dirname(__DIR__));

// Direktori dan URL untuk upload gambar produk
define('UPLOAD_DIR', ROOT_PATH . '/assets/images/products/');
define('UPLOAD_URL', BASE_URL . '/assets/images/products/');

// Buat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Tangani error koneksi
if ($conn->connect_error) {
    $err = htmlspecialchars($conn->connect_error);
    die('
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Database Error — ELECTROMART</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            *{box-sizing:border-box;margin:0;padding:0}
            body{font-family:Inter,sans-serif;background:#F9FAFB;display:flex;align-items:center;justify-content:center;min-height:100vh}
            .box{background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:48px 40px;max-width:500px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,.08)}
            .icon{font-size:56px;margin-bottom:20px}
            h2{font-size:22px;color:#111827;margin-bottom:12px}
            p{font-size:14px;color:#6B7280;line-height:1.7;margin-bottom:8px}
            code{background:#F3F4F6;padding:2px 8px;border-radius:4px;font-size:13px;color:#374151}
            .steps{text-align:left;background:#EFF6FF;border-radius:10px;padding:16px 20px;margin-top:20px}
            .steps li{font-size:13px;color:#1E40AF;margin-bottom:6px;padding-left:4px}
        </style>
    </head>
    <body>
        <div class="box">
            <div class="icon">⚠️</div>
            <h2>Koneksi Database Gagal</h2>
            <p>Tidak dapat terhubung ke database MySQL.</p>
            <p><strong>Error:</strong> <code>' . $err . '</code></p>
            <ol class="steps">
                <li>Pastikan XAMPP MySQL sudah <strong>berjalan</strong></li>
                <li>Buka phpMyAdmin dan buat database <code>electromart</code></li>
                <li>Import file <code>sql/electromart.sql</code></li>
                <li>Sesuaikan <code>DB_USER</code> / <code>DB_PASS</code> di <code>config/db.php</code></li>
            </ol>
        </div>
    </body>
    </html>
    ');
}

$conn->set_charset('utf8mb4');
