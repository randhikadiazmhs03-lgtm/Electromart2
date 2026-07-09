<?php
/**
 * ELECTROMART - Admin Layout Footer
 */
?>
        </main><!-- /admin-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

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
