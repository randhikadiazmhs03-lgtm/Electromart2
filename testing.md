# Testing Documentation

## Informasi Project
- Nama Website: Electromart
- Versi: 1.0
- Tanggal Pengujian: 2026-07-10
- Penguji: [Nama Penguji]

## Tujuan Pengujian
Dokumen ini bertujuan untuk mencatat hasil pengujian website secara sistematis terhadap fitur-fitur utama, termasuk akses pengguna, manajemen data, form validation, serta responsivitas tampilan.

## Lingkungan Pengujian
- OS: Windows 11
- Browser: Google Chrome
- Database: MySQL
- Perangkat: Laptop/Desktop
- Server: Localhost / XAMPP / Laragon (sesuaikan jika diperlukan)

## Skenario Pengujian

| No | Fitur | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status |
|----|-------|---------------------|-------------------|------------------------|--------|
| 1 | Beranda | Menampilkan halaman utama dengan benar | Buka halaman utama website | Halaman beranda tampil lengkap dan tidak error | Pass / Fail |
| 2 | Login | Login dengan akun valid | Masukkan email dan password yang benar lalu klik login | User berhasil login dan diarahkan ke dashboard | Pass / Fail |
| 3 | Login | Login dengan akun tidak valid | Masukkan email/password salah | Sistem menolak login dan menampilkan pesan error | Pass / Fail |
| 4 | Registrasi | Registrasi akun baru | Isi form registrasi dengan data valid | Akun berhasil dibuat dan dapat login | Pass / Fail |
| 5 | Logout | Logout dari session aktif | Klik menu logout | User berhasil keluar dari sistem dan diarahkan ke halaman login | Pass / Fail |
| 6 | Navigasi Menu | Mengakses menu utama website | Klik menu pada navbar/header | Setiap menu terbuka sesuai halaman yang dituju | Pass / Fail |
| 7 | Pencarian | Mencari produk atau data | Masukkan kata kunci pada fitur pencarian | Hasil pencarian sesuai dengan kata kunci | Pass / Fail |
| 8 | Tambah Data | Menambahkan data baru | Masuk ke halaman admin, isi form, lalu simpan | Data berhasil tersimpan ke database | Pass / Fail |
| 9 | Edit Data | Mengubah data yang sudah ada | Buka data, edit isi, lalu simpan | Data berhasil diperbarui | Pass / Fail |
| 10 | Hapus Data | Menghapus data yang sudah ada | Pilih data lalu klik hapus | Data berhasil dihapus dan tidak tampil lagi | Pass / Fail |
| 11 | Validasi Form | Mengisi form dengan data tidak lengkap | Kosongkan field yang wajib diisi | Sistem menampilkan pesan validasi | Pass / Fail |
| 12 | Responsivitas Tampilan | Mengakses website di layar kecil | Buka website dari layar mobile / resize browser | Tampilan tetap rapi dan komponen tidak rusak | Pass / Fail |

## Catatan Bug dan Kendala
- Catat bug, error, atau kendala yang ditemukan selama pengujian.
- Contoh:
  - Bug: Form registrasi tidak memvalidasi email yang tidak valid.
  - Kendala: Halaman tidak sepenuhnya responsif pada layar kecil.
  - Catatan: Beberapa fitur hanya dapat diuji setelah login sebagai admin/user.
