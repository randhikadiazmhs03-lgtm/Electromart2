/* ELECTROMART — Main JavaScript */
'use strict';

document.addEventListener('DOMContentLoaded', function () {

    /* ── User dropdown ──────────────────────────────── */
    const toggle   = document.getElementById('userMenuToggle');
    const dropdown = document.getElementById('userDropdown');
    if (toggle && dropdown) {
        toggle.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('show'); });
        document.addEventListener('click', e => { if (!toggle.contains(e.target)) dropdown.classList.remove('show'); });
    }

    /* ── Search toggle (mobile) ─────────────────────── */
    const searchToggle = document.getElementById('searchToggle');
    const navSearch    = document.getElementById('navbarSearch');
    if (searchToggle && navSearch) {
        searchToggle.addEventListener('click', () => {
            navSearch.classList.toggle('show');
            if (navSearch.classList.contains('show')) navSearch.querySelector('input')?.focus();
        });
    }

    /* ── Quantity controls ──────────────────────────── */
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.closest('.qty-control').querySelector('.qty-input');
            const min   = parseInt(input.min  || 1);
            const max   = parseInt(input.max  || 9999);
            let   val   = parseInt(input.value) || 1;
            val = this.dataset.action === 'minus' ? Math.max(min, val - 1) : Math.min(max, val + 1);
            input.value = val;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    /* ── Delete confirmation modal ──────────────────── */
    const modal   = document.getElementById('deleteModal');
    const mTitle  = document.getElementById('modalTitle');
    const mDesc   = document.getElementById('modalDesc');
    const mCancel = document.getElementById('cancelDelete');
    const mConfirm= document.getElementById('confirmDelete');

    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const name = btn.dataset.name || 'item ini';
            const type = btn.dataset.itemType || 'Data';
            const href = btn.getAttribute('href') || btn.dataset.href;
            if (mTitle)  mTitle.textContent = `Hapus ${type}`;
            if (mDesc)   mDesc.textContent  = `Yakin ingin menghapus "${name}"? Tindakan ini tidak bisa dibatalkan.`;
            if (mConfirm && href) mConfirm.onclick = () => { window.location.href = href; };
            modal?.classList.add('show');
        });
    });
    mCancel?.addEventListener('click',  () => modal?.classList.remove('show'));
    modal  ?.addEventListener('click',  e => { if (e.target === modal) modal.classList.remove('show'); });

    /* ── Auto dismiss alerts (5 s) ──────────────────── */
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            if (!alert.parentElement) return;
            alert.style.transition = 'opacity .3s, transform .3s';
            alert.style.opacity    = '0';
            alert.style.transform  = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    /* ── Category filter auto-submit ────────────────── */
    document.getElementById('categoryFilter')?.addEventListener('change', function () {
        this.closest('form')?.submit();
    });

    /* ── Admin sidebar toggle (mobile) ─────────────── */
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar  = document.getElementById('adminSidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', () => adminSidebar.classList.toggle('open'));
        document.addEventListener('click', e => {
            if (window.innerWidth <= 768 &&
                !adminSidebar.contains(e.target) &&
                !sidebarToggle.contains(e.target)) {
                adminSidebar.classList.remove('open');
            }
        });
    }

    /* ── Image upload preview ───────────────────────── */
    const imgInput   = document.getElementById('productImage');
    const imgPreview = document.getElementById('imagePreview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => { imgPreview.src = e.target.result; imgPreview.style.display = 'block'; };
            reader.readAsDataURL(file);
        });
    }

    /* ── Featured toggle (AJAX) ─────────────────────── */
    document.querySelectorAll('.featured-toggle').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const id    = this.dataset.id;
            const val   = this.checked ? 1 : 0;
            const self  = this;
            fetch(BASE_URL + '/admin/products/toggle_featured.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id, is_featured: val })
            })
            .then(r => r.json())
            .then(d => { if (!d.success) self.checked = !self.checked; })
            .catch(() => self.checked = !self.checked);
        });
    });
});
