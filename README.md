# Dokumentasi Program: ELECTROMART

## 1. Deskripsi Aplikasi
**ELECTROMART** adalah sebuah aplikasi *E-Commerce* (Toko Online) berbasis web yang dirancang khusus untuk memenuhi kebutuhan perangkat elektronik civitas akademika (mahasiswa). Aplikasi ini menyediakan berbagai produk seperti laptop, aksesori komputer, gadget, dan alat elektronik lainnya dengan harga bersahabat dan antarmuka yang ramah pengguna.

## 2. Teknologi yang Digunakan
- **Bahasa Pemrograman Utama:** PHP (Native / Tanpa Framework)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, dan JavaScript (Native)
- **Web Server:** Apache (umumnya dijalankan via XAMPP)

## 3. Struktur Direktori dan File Utama
- `/admin/` : Berisi halaman dashboard dan panel manajemen khusus untuk Administrator (mengelola produk, kategori, pesanan, dan pengguna).
- `/assets/` : Menyimpan aset statis seperti file CSS, JavaScript, dan gambar. Terdapat folder `/assets/images/products/` yang digunakan untuk menampung *file upload* gambar produk.
- `/auth/` : Menangani proses otentikasi pengguna, seperti login (`login.php`), registrasi (`register.php`), dan logout (`logout.php`).
- `/config/` : Berisi file konfigurasi sistem. File utamanya adalah `db.php` yang mengatur koneksi database dan mendefinisikan konstanta *Base URL* serta *Path*.
- `/includes/` : Berisi potongan kode (komponen) antar muka dan logika pendukung yang digunakan berulang kali di berbagai halaman, seperti `header.php`, `footer.php`, dan kumpulan fungsi pendukung di `functions.php`.
- `/products/` : Berisi halaman katalog produk secara umum (`index.php`) dan halaman detail spesifik untuk tiap produk.
- `/sql/` : Menyimpan file ekspor (*dump*) database (`electromart.sql`) yang memuat struktur tabel (DDL) serta data awal bawaan (*seeder*).
- `/user/` : Berisi halaman khusus yang diakses oleh pengguna biasa yang sudah login. Meliputi dashboard, keranjang belanja (`cart.php`), proses *checkout* (`checkout.php`), metode pembayaran (`payment.php`), riwayat pesanan (`orders.php`), dan manajemen profil (`profile.php`).
- `index.php` : Merupakan halaman utama (Beranda/Landing Page) yang menampilkan *Hero Section*, daftar kategori unggulan, produk unggulan, dan statistik singkat platform.

## 4. Struktur Database
Database yang digunakan bernama `electromart`. Sistem ini dirancang menggunakan relasi tabel (*Relational Database*) yang terdiri dari 6 tabel utama:
1. **users** : Menyimpan data autentikasi dan profil pengguna, mencakup *role* (`admin` atau `user`), nama, email, password (di-hash dengan MD5), nomor telepon, dan alamat.
2. **categories** : Menyimpan daftar kategori pengelompokan produk beserta ikonnya (misal: Laptop, Mouse, dsb).
3. **products** : Menyimpan master data barang yang dijual, berelasi ke tabel `categories`. Mencakup harga, sisa stok, gambar, merek, dan status unggulan (*featured*).
4. **cart** : Menyimpan data sementara keranjang belanja (*Add to Cart*) setiap pengguna sebelum diproses ke halaman pembayaran. Berelasi dengan tabel `users` dan `products`.
5. **orders** : Menyimpan data master transaksi/pesanan. Mencakup total harga pesanan, metode pembayaran (COD, Transfer BCA, QRIS, dsb), status pesanan (*pending*, *shipped*, *delivered*, dll), serta alamat pengiriman akhir pengguna.
6. **order_items** : Menyimpan data rinci untuk setiap produk yang ada di dalam sebuah pesanan/order (relasi antar tabel `orders` dan `products`), termasuk jumlah barang dan harga beli barang pada saat itu.

## 5. Fitur Utama

### A. Pengguna (User / Pembeli)
- Pendaftaran akun baru (*Register*) dan akses masuk (*Login*).
- Penjelajahan katalog produk secara bebas, serta dapat disaring berdasarkan Kategori.
- Peninjauan detail produk secara komprehensif, mencakup harga, stok riil, dan spesifikasi.
- Sistem Keranjang Belanja (*Shopping Cart*) untuk menyimpan calon pembelian produk secara agregat.
- Proses *Checkout* dengan dukungan berbagai metode pembayaran, mulai dari *Cash on Delivery* (COD) hingga transfer antar bank atau QRIS.
- Manajemen Profil Pengguna, untuk mengatur atau memutakhirkan nomor telepon dan alamat utama pengiriman.
- Pemantauan status riwayat pesanan secara berkala.

### B. Administrator (Admin / Pengelola Toko)
- **Dashboard Admin:** Menyajikan ringkasan statistik komprehensif terkait total pesanan yang masuk dan pergerakan penjualan.
- **Manajemen Kategori:** Admin dapat mengatur dan mengubah daftar kategori produk yang dijual.
- **Manajemen Produk:** Memberikan otoritas untuk menambah entri produk baru (dilengkapi proses unggah gambar), menyunting informasi harga dan ketersediaan stok, hingga menetapkan suatu produk sebagai rekomendasi unggulan.
- **Manajemen Pesanan:** Admin dapat meninjau semua pesanan masuk dari setiap pengguna, mengevaluasi bukti pembayaran, serta memperbarui status dari pemrosesan pesanan tersebut (Misal: Diproses -> Dikirim -> Selesai).
- **Manajemen Pengguna:** Menampilkan log registrasi pengguna di dalam platform.

## 6. Cara Instalasi dan Menjalankan Proyek

1. **Persiapan Lingkungan Server:** Pastikan *web server* lokal seperti XAMPP sudah terpasang dan berjalan dengan modul **Apache** dan **MySQL** dalam keadaan aktif.
2. **Penempatan Direktori:** Pindahkan seluruh *source code* (folder `UASWeb`) ke dalam direktori *document root* pada web server lokal Anda. Jika menggunakan XAMPP di Windows, umumnya ditempatkan di `C:\xampp\htdocs\UASWeb` (atau *drive* tempat XAMPP diinstal seperti `d:\XAMPPNew\htdocs\UASWeb`).
3. **Persiapan Database:**
   - Buka browser dan jalankan utilitas **phpMyAdmin** dengan mengakses `http://localhost/phpmyadmin`.
   - Buat sebuah basis data baru dengan nama `electromart`.
   - Lakukan impor database dengan memilih opsi **Import**, lalu pilih *file* `electromart.sql` yang terletak pada sub-folder `/sql/`.
4. **Penyesuaian Konfigurasi (Opsional):** Jika konfigurasi kredensial MySQL lokal Anda bukan *default* (menggunakan *password* dll), buka *file* `/config/db.php` menggunakan *text editor* dan sesuaikan variabel konfigurasi berikut:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root'); // Sesuaikan username database MySQL
   define('DB_PASS', '');     // Sesuaikan password database MySQL
   define('DB_NAME', 'electromart');
   define('BASE_URL', 'http://localhost/UASWeb'); // Sesuaikan struktur sub-direktori URL Anda
   ```
5. **Menjalankan Aplikasi:** Buka browser pilihan Anda dan akses alamat `http://localhost/UASWeb` untuk membuka beranda aplikasi ELECTROMART.

### Data Login / Kredensial Pengujian (Seeder)
Sistem sudah ditanam dengan beberapa data percobaan untuk memudahkan peninjauan fungsi:
- **Akun Admin:**
  - Email: `admin@electromart.com`
  - Password: `admin123`
- **Akun User (Pembeli Biasa):**
  - Email: `user@electromart.com`
  - Password: `user123`
