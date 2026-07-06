# Panduan Deployment ELECTROMART ke Render

## Step 1: Setup Database MySQL Gratis (Railway)

1. **Buka Railway:** https://railway.app/
2. **Login/Signup** dengan GitHub account Anda
3. **Buat project baru:**
   - Klik "Create New Project"
   - Pilih "MySQL"
   - Tunggu proses provisioning selesai

4. **Ambil credentials database:**
   - Di Railway dashboard, buka service MySQL
   - Tab "Connect", ambil informasi:
     - `DB_HOST` (biasanya ada format `containers-us-west-...railway.internal`)
     - `DB_PORT` (default 3306)
     - `DB_USER` (default `root`)
     - `DB_PASS` (lihat di tab Variables)
     - `DB_NAME` (buat database baru atau gunakan `railway`)

5. **Import database schema:**
   - Download file `sql/electromart.sql` dari repository
   - Di Railway, cari database tool atau gunakan SQL editor
   - Run script dari file `electromart.sql` untuk membuat tabel

**CATATAN:** Railway memberikan gratis 5 hari. Setelah itu, perlu upgrade akun.

---

## Step 2: Deploy ke Render

1. **Buka Render:** https://render.com/
2. **Login/Signup** dengan GitHub account
3. **Buat Web Service:**
   - Klik "New +"
   - Pilih "Web Service"
   - Pilih repository: `Electromart`
   - Build & Deploy

4. **Set Environment Variables di Render:**
   - Di halaman Web Service settings, cari "Environment"
   - Tambahkan variabel berikut dengan nilai dari Railway:
     ```
     DB_HOST=<railway_db_host>
     DB_PORT=3306
     DB_USER=root
     DB_PASS=<railway_db_password>
     DB_NAME=electromart
     BASE_URL=https://<your-render-url>.onrender.com
     ```

5. **Deploy:**
   - Render otomatis akan deploy saat Anda save environment variables
   - Tunggu hingga build selesai (cek di tab "Logs")

6. **Verifikasi:**
   - Buka URL aplikasi Anda: `https://<your-service-name>.onrender.com`
   - Coba login dengan akun:
     - Email: `admin@electromart.com`
     - Password: `admin123`

---

## Informasi Penting

### Render - Service Layers
- **Plan:** Free (Sleep Mode - aplikasi akan sleep jika tidak ada traffic 15 menit)
- **Waktu Startup:** ~30 detik setelah bangun dari sleep mode
- **Limitasi:** 100 jam/bulan gratis

### Railway - Database
- **Plan:** Free Trial (5 hari)
- **Alternatif Gratis:**
  - **Planetscale** (MySQL) - https://planetscale.com/ (3 database gratis)
  - Jika MySQL habis trial, upgrade plan atau pakai Planetscale

### Catatan Deployment
- Pastikan `config/db.php` sudah ter-update dengan environment variable loading
- File `.env` jangan di-upload ke repository (sudah di .gitignore)
- Upload gambar produk akan disimpan di `assets/images/products/`

---

## Troubleshooting

### Error 503 Service Unavailable
- Aplikasi sedang sleep di Render, tunggu ~30 detik

### Cannot connect to database
- Cek kembali DB credentials di environment variables
- Pastikan database sudah ter-import dengan file `electromart.sql`

### URL configuration error
- Update `BASE_URL` di environment variables sesuai dengan URL Render Anda

---

## Instruksi untuk saya (jika ingin otomatis):

Untuk deployment lebih cepat, saya bisa membantu:
- Deploy database ke Railway
- Configure environment variables
- Deploy ke Render
- Testing koneksi database

Hubungi saya untuk langkah berikutnya!
