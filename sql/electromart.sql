-- ============================================================
--  ELECTROMART - Database Schema & Seed Data
--  E-Commerce Elektronik untuk Mahasiswa Kampus
--  Cara import: buka phpMyAdmin > Import > pilih file ini
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Buat dan gunakan database
CREATE DATABASE IF NOT EXISTS `electromart`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE `electromart`;

-- ============================
-- TABEL: users
-- ============================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100)    NOT NULL,
    `email`      VARCHAR(100)    NOT NULL,
    `password`   VARCHAR(255)    NOT NULL,
    `role`       ENUM('admin','user') NOT NULL DEFAULT 'user',
    `phone`      VARCHAR(20)     DEFAULT NULL,
    `address`    TEXT            DEFAULT NULL,
    `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- TABEL: categories
-- ============================
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id`   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(10)  DEFAULT '📦'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- TABEL: products
-- ============================
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id`          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED   DEFAULT NULL,
    `name`        VARCHAR(200)   NOT NULL,
    `description` TEXT           DEFAULT NULL,
    `price`       DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    `stock`       INT            NOT NULL DEFAULT 0,
    `image`       VARCHAR(255)   DEFAULT NULL,
    `brand`       VARCHAR(100)   DEFAULT NULL,
    `is_featured` TINYINT(1)     NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- TABEL: cart
-- ============================
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity`   INT          NOT NULL DEFAULT 1,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- TABEL: orders
-- ============================
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id`               INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT UNSIGNED   NOT NULL,
    `total_price`      DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    `status`           ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method`   ENUM('cod','transfer_bca','transfer_bri','transfer_mandiri','transfer_bni','qris') NOT NULL DEFAULT 'cod',
    `payment_status`   ENUM('unpaid','paid','verified') NOT NULL DEFAULT 'paid',
    `payment_proof`    VARCHAR(255)   DEFAULT NULL,
    `shipping_address` TEXT           DEFAULT NULL,
    `notes`            TEXT           DEFAULT NULL,
    `created_at`       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- TABEL: order_items
-- ============================
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id`           INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `order_id`     INT UNSIGNED   NOT NULL,
    `product_id`   INT UNSIGNED   DEFAULT NULL,
    `product_name` VARCHAR(200)   NOT NULL,
    `quantity`     INT            NOT NULL DEFAULT 1,
    `price`        DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Akun default (Password: admin123 dan user123 — MD5 hashed)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`, `address`) VALUES
('Administrator',  'admin@electromart.com', MD5('admin123'), 'admin', '08123456789',  'Jl. Kampus Raya No. 1, Kota Pendidikan'),
('Budi Santoso',   'user@electromart.com',  MD5('user123'),  'user',  '08987654321',  'Jl. Mahasiswa Blok B No. 5, Asrama Kampus'),
('Siti Rahayu',    'siti@example.com',      MD5('user123'),  'user',  '08112233445',  'Jl. Pahlawan No. 12, Kota Belajar');

-- Kategori
INSERT INTO `categories` (`name`, `icon`) VALUES
('Laptop & Komputer',  '💻'),
('Mouse & Keyboard',   '🖱️'),
('Headset & Audio',    '🎧'),
('Smartphone',         '📱'),
('Tablet',             '📟'),
('Charger & Aksesori', '🔌'),
('Webcam',             '📷'),
('Printer & Scanner',  '🖨️');

-- Produk (gambar placeholder via picsum.photos)
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `stock`, `image`, `brand`, `is_featured`) VALUES

-- Laptop & Komputer (cat 1)
(1, 'Acer Aspire 5 Intel Core i5-1235U',
 'Laptop performa tinggi dengan prosesor Intel Core i5 generasi ke-12, RAM 8GB DDR4, SSD 512GB NVMe. Cocok untuk mahasiswa teknik, coding, dan desain grafis. Layar FHD IPS 15.6 inci anti-glare, baterai 50Wh tahan ±7 jam pemakaian normal.',
 8500000, 15, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&h=400&fit=crop', 'Acer', 1),

(1, 'ASUS VivoBook 14 Ryzen 5 5500U',
 'Laptop ringan dan bertenaga dengan AMD Ryzen 5 5500U, RAM 8GB, SSD 512GB. Baterai tahan lama hingga 10 jam. Ideal untuk kuliah sehari-hari dan pengerjaan tugas berat. Bobot hanya 1.5 kg, mudah dibawa ke kampus.',
 7200000, 20, 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600&h=400&fit=crop', 'ASUS', 1),

(1, 'Lenovo IdeaPad 3 Intel Core i3-1115G4',
 'Laptop entry-level handal untuk mahasiswa dengan Intel Core i3 Gen 11, RAM 4GB (upgradeable ke 8GB), HDD 1TB. Desain slim dan ringan 1.7 kg. Cocok untuk tugas kuliah, browsing, dan multimedia ringan.',
 5500000, 25, 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=600&h=400&fit=crop', 'Lenovo', 0),

(1, 'HP Pavilion 14 Intel Core i5-1135G7',
 'Laptop HP dengan desain modern, prosesor Intel Core i5 Gen 11, RAM 8GB, SSD 256GB + HDD 1TB. Layar IPS Full HD 14 inci. GPU discrete MX450 untuk grafis ringan dan multimedia.',
 7800000, 12, 'https://images.unsplash.com/photo-1588702547923-7408eb8cd69d?w=600&h=400&fit=crop', 'HP', 0),

(1, 'Apple MacBook Air M2 8GB/256GB Silver',
 'Laptop premium Apple dengan chip M2 revolusioner, 8GB Unified Memory, SSD 256GB. Performa luar biasa dengan efisiensi daya terbaik. Layar Liquid Retina 13.6 inci, desain tipis 11.3mm, tanpa kipas (fanless), baterai hingga 18 jam.',
 17500000, 8, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&h=400&fit=crop', 'Apple', 1),

-- Mouse & Keyboard (cat 2)
(2, 'Logitech M331 Silent Plus Wireless Mouse',
 'Mouse wireless silent dengan teknologi SilentTouch (90% lebih sains). DPI 1000, jangkauan 10 meter, receiver USB nano. Baterai AA tahan hingga 24 bulan. Desain ergonomis untuk tangan kanan.',
 280000, 50, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=600&h=400&fit=crop', 'Logitech', 1),

(2, 'Rexus Daxa M72 RGB Gaming Mouse',
 'Mouse gaming dengan sensor optical presisi 3200 DPI adjustable, 6 tombol programmable, lampu LED RGB 16.8 juta warna. Desain ergonomis anti-slip, kabel braided 1.8 meter. Cocok untuk gaming dan desain.',
 185000, 40, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600&h=400&fit=crop', 'Rexus', 0),

(2, 'Keychron K2 Wireless Mechanical Keyboard',
 'Keyboard mechanical tenkeyless premium dengan switch Gateron Red/Brown/Blue (pilihan). Layout 75% kompak, koneksi Bluetooth 5.1 + USB-C wired. Frame aluminium, backlight LED putih. Kompatibel Mac & Windows.',
 950000, 20, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&h=400&fit=crop', 'Keychron', 1),

-- Headset & Audio (cat 3)
(3, 'Sony WH-1000XM5 Wireless Noise Cancelling',
 'Headphone flagship Sony dengan Active Noise Cancelling industri terbaik. Baterai 30 jam, koneksi multipoint ke 2 perangkat sekaligus, Hi-Res Audio. Mikrofon AI 8-capsule untuk panggilan jernih di kebisingan kampus.',
 4200000, 10, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=400&fit=crop', 'Sony', 1),

(3, 'Logitech H390 USB Computer Headset',
 'Headset USB plug & play tanpa driver. Mikrofon noise-cancelling yang dapat dilipat, earcup nyaman dengan busa tebal. Cocok untuk Zoom/Google Meet kuliah online dan presentasi. Kontrol volume inline di kabel.',
 350000, 35, 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&h=400&fit=crop', 'Logitech', 0),

(3, 'JBL Tune 510BT Wireless On-Ear',
 'Headphone wireless Bluetooth 5.0 dengan baterai luar biasa 40 jam. Suara JBL Pure Bass yang jernih dan bertenaga. Desain lipat ringkas mudah dibawa ke kampus. Pengisian cepat 5 menit = 2 jam pemakaian.',
 480000, 30, 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=600&h=400&fit=crop', 'JBL', 1),

-- Smartphone (cat 4)
(4, 'Samsung Galaxy A55 5G 8/256GB',
 'Smartphone 5G terjangkau dengan layar Super AMOLED 6.6 inci 120Hz, Exynos 1480 Octa-core, RAM 8GB, storage 256GB. Kamera utama 50MP OIS + 12MP ultrawide + 5MP macro. Baterai 5000mAh 25W fast charge.',
 5200000, 18, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&h=400&fit=crop', 'Samsung', 1),

(4, 'Xiaomi Redmi Note 13 Pro+ 12/256GB',
 'Smartphone dengan Dimensity 7200 Ultra, RAM 12GB, storage 256GB expandable. Kamera periskop 200MP dengan OIS, layar AMOLED 120Hz, IP68 water resistant. Charger HyperCharge 120W tercepat di kelasnya.',
 4200000, 22, 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?w=600&h=400&fit=crop', 'Xiaomi', 0),

-- Tablet (cat 5)
(5, 'Apple iPad 10th Gen 64GB Wi-Fi Blue',
 'Tablet Apple terbaru dengan chip A14 Bionic, layar Liquid Retina 10.9 inci True Tone, baterai 10 jam. Mendukung Apple Pencil gen 1 dan Magic Keyboard Folio. Cocok untuk mahasiswa desain, catatan kuliah, dan multimedia.',
 7500000, 10, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=600&h=400&fit=crop', 'Apple', 1),

(5, 'Samsung Galaxy Tab A8 Wi-Fi 4/64GB',
 'Tablet Android layar lebar 10.5 inci TFT 1920x1200. RAM 4GB, storage 64GB (expandable 1TB). Baterai 7040mAh. 4 speaker Dolby Atmos. Ideal untuk baca e-book materi kuliah, streaming video, dan browsing.',
 2800000, 15, 'https://images.unsplash.com/photo-1527698266440-12104e498b76?w=600&h=400&fit=crop', 'Samsung', 0),

-- Charger & Aksesori (cat 6)
(6, 'Anker PowerCore 20000mAh Power Bank',
 'Power bank kapasitas besar 20000mAh dengan PowerIQ 3.0 + VoltageBoost. Port: 2× USB-A + 1× USB-C (input/output). Bisa mengisi laptop ultrabook. LED indicator kapasitas 4 level. Garansi 18 bulan.',
 420000, 60, 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=600&h=400&fit=crop', 'Anker', 1),

(6, 'Anker USB-C to USB-C Cable 100W 2m',
 'Kabel USB-C premium mendukung fast charging 100W, transfer data 480Mbps. Panjang 2 meter. Kompatibel laptop, tablet, smartphone USB-C. Nylon braided tahan lama, konektor zinc alloy anti-korosi.',
 85000, 100, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&h=400&fit=crop', 'Anker', 0),

-- Webcam (cat 7)
(7, 'Logitech C920s HD Pro Webcam 1080p',
 'Webcam Full HD 1080p/30fps dengan lensa Carl Zeiss premium. Mikrofon stereo dual built-in dengan noise cancellation. Autofocus tetap tajam saat bergerak. Field of view 78°. Wajib punya untuk kuliah online dan presentasi akademik.',
 950000, 25, 'https://images.unsplash.com/photo-1563170351-be54b54f2339?w=600&h=400&fit=crop', 'Logitech', 1),

-- Printer (cat 8)
(8, 'Epson EcoTank L3250 All-in-One Wi-Fi',
 'Printer all-in-one (print, scan, copy) dengan tangki tinta isi ulang berkapasitas besar. Koneksi Wi-Fi dan USB. Hemat biaya cetak sangat rendah (1 set tinta = 4500 halaman hitam / 7500 halaman warna). Wajib untuk mencetak tugas, makalah, dan skripsi.',
 2350000, 12, 'https://images.unsplash.com/photo-1612198188060-c7c2a3b66eae?w=600&h=400&fit=crop', 'Epson', 0);

SET FOREIGN_KEY_CHECKS = 1;
