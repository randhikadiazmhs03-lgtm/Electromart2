<?php
/**
 * ELECTROMART - Public Footer
 */
?>
</div><!-- /container -->
</main><!-- /main-content -->

<!-- ===================== FOOTER ===================== -->
<footer class="footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand / About -->
            <div>
                <div class="footer-brand">
                    <div class="brand-icon" aria-hidden="true">⚡</div>
                    ELECTROMART
                </div>
                <p class="footer-desc">
                    Platform e-commerce elektronik terpercaya untuk civitas akademika.
                    Dapatkan laptop, gadget, dan aksesori berkualitas dengan harga terjangkau
                    langsung dari genggaman tangan Anda.
                </p>
            </div>

            <!-- Produk -->
            <div>
                <div class="footer-heading">Produk</div>
                <nav class="footer-links" aria-label="Kategori produk">
                    <a href="<?= BASE_URL ?>/products/index.php?cat=1">Laptop & Komputer</a>
                    <a href="<?= BASE_URL ?>/products/index.php?cat=2">Mouse & Keyboard</a>
                    <a href="<?= BASE_URL ?>/products/index.php?cat=3">Headset & Audio</a>
                    <a href="<?= BASE_URL ?>/products/index.php?cat=4">Smartphone</a>
                    <a href="<?= BASE_URL ?>/products/index.php?cat=7">Webcam</a>
                    <a href="<?= BASE_URL ?>/products/index.php?cat=8">Printer</a>
                </nav>
            </div>

            <!-- Akun -->
            <div>
                <div class="footer-heading">Akun</div>
                <nav class="footer-links" aria-label="Akun pengguna">
                    <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
                    <a href="<?= BASE_URL ?>/auth/register.php">Daftar</a>
                    <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a>
                    <a href="<?= BASE_URL ?>/user/orders.php">Riwayat Pesanan</a>
                    <a href="<?= BASE_URL ?>/user/profile.php">Profil Saya</a>
                </nav>
            </div>

            <!-- Info -->
            <div>
                <div class="footer-heading">Informasi</div>
                <nav class="footer-links" aria-label="Informasi">
                    <a href="<?= BASE_URL ?>/index.php">Beranda</a>
                    <a href="<?= BASE_URL ?>/products/index.php">Semua Produk</a>
                </nav>
                <div style="margin-top:24px">
                    <div class="footer-heading">Kontak</div>
                    <p style="font-size:13px;color:var(--gray-400);line-height:1.7">
                        📧 info@electromart.id<br>
                        📞 (021) 1234-5678<br>
                        📍 Kampus Universitas, Kota Pendidikan
                    </p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?= date('Y') ?> ELECTROMART. Hak cipta dilindungi.</span>
            <span>Dibuat dengan ❤️ untuk mahasiswa kampus</span>
        </div>
    </div>
</footer>
<!-- ============ END FOOTER ============ -->

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <h3 class="modal-title" id="modalTitle">Hapus Data</h3>
        <p  class="modal-desc"  id="modalDesc">Yakin ingin menghapus item ini?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancelDelete">Batal</button>
            <button class="btn btn-danger"    id="confirmDelete">Ya, Hapus</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
