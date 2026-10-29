(function () {
    'use strict';
    if (window.__CMS_RBAC_V70_BOOTED__) return;
    window.__CMS_RBAC_V70_BOOTED__ = true;

    function bootRbacAdmin() {
        document.querySelectorAll('.js-edit-rbac-role').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var idEl = document.getElementById('role_id');
                if (!idEl) return;
                idEl.value = btn.getAttribute('data-id') || '0';
                document.getElementById('role_name').value = btn.getAttribute('data-name') || '';
                document.getElementById('role_slug').value = btn.getAttribute('data-slug') || '';
                document.getElementById('role_portal').value = btn.getAttribute('data-portal') || 'frontend';
                document.getElementById('role_desc').value = btn.getAttribute('data-desc') || '';
                var defCb = document.getElementById('role_def_front');
                if (defCb) defCb.checked = btn.getAttribute('data-def') === '1';
                var title = document.getElementById('rbac-role-form-title');
                if (title) title.textContent = 'Edit Role: ' + (btn.getAttribute('data-name') || '');
                idEl.form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });

        var resetBtn = document.getElementById('js-rbac-role-reset');
        if (resetBtn && !resetBtn.dataset.bound) {
            resetBtn.dataset.bound = '1';
            resetBtn.addEventListener('click', function () {
                document.getElementById('role_id').value = '0';
                document.getElementById('role_name').value = '';
                document.getElementById('role_slug').value = '';
                document.getElementById('role_portal').value = 'frontend';
                document.getElementById('role_desc').value = '';
                var defCb = document.getElementById('role_def_front');
                if (defCb) defCb.checked = false;
                var title = document.getElementById('rbac-role-form-title');
                if (title) title.textContent = 'Custom Role Builder';
            });
        }

        var checkAllBtn = document.getElementById('js-rbac-check-all');
        if (checkAllBtn && !checkAllBtn.dataset.bound) {
            checkAllBtn.dataset.bound = '1';
            checkAllBtn.addEventListener('click', function () {
                document.querySelectorAll('.js-rbac-perm-cb:not(:disabled)').forEach(function (cb) { cb.checked = true; });
            });
        }

        var uncheckAllBtn = document.getElementById('js-rbac-uncheck-all');
        if (uncheckAllBtn && !uncheckAllBtn.dataset.bound) {
            uncheckAllBtn.dataset.bound = '1';
            uncheckAllBtn.addEventListener('click', function () {
                document.querySelectorAll('.js-rbac-perm-cb:not(:disabled)').forEach(function (cb) { cb.checked = false; });
            });
        }

        document.querySelectorAll('.js-toggle-group').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var card = btn.closest('.rbac-group-card');
                if (!card) return;
                var boxes = card.querySelectorAll('.js-rbac-perm-cb:not(:disabled)');
                var allChecked = true;
                boxes.forEach(function (cb) { if (!cb.checked) allChecked = false; });
                boxes.forEach(function (cb) { cb.checked = !allChecked; });
            });
        });

        var permSearch = document.getElementById('js-rbac-perm-search');
        if (permSearch && !permSearch.dataset.bound) {
            permSearch.dataset.bound = '1';
            permSearch.addEventListener('input', function () {
                var q = permSearch.value.toLowerCase().trim();
                document.querySelectorAll('.js-rbac-perm-card').forEach(function (card) {
                    var txt = card.getAttribute('data-search') || '';
                    card.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }

        document.querySelectorAll('.js-rbac-confirm-del').forEach(function (frm) {
            if (frm.dataset.bound === '1') return;
            frm.dataset.bound = '1';
            frm.addEventListener('submit', function (e) {
                if (!window.confirm('Are you sure you want to delete this custom role?')) {
                    e.preventDefault();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootRbacAdmin);
    } else {
        bootRbacAdmin();
    }
})();
