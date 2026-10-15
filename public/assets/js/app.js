document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 0. SIDEBAR MOBILE TOGGLE
    // ==========================================
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const appSidebar = document.getElementById('app-sidebar');

    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            appSidebar.classList.toggle('is-open');
        });
    }

    // ==========================================
    // 1. INSTALLER WIZARD LOGIC
    // ==========================================
    const installerForm = document.getElementById('installer-form');
    const btnToStep2 = document.getElementById('btn-to-step-2');
    const btnBackStep1 = document.getElementById('btn-back-step-1');
    const paneStep1 = document.getElementById('pane-step-1');
    const paneStep2 = document.getElementById('pane-step-2');
    const indStep1 = document.getElementById('ind-step-1');
    const indStep2 = document.getElementById('ind-step-2');
    const alertBox = document.getElementById('installer-alert');
    const siteTitleInput = document.getElementById('site_title');
    const submitBtn = document.getElementById('btn-submit-install');
    const submitBtnText = document.getElementById('submit-btn-text');

    const showAlert = (box, message, type = 'error') => {
        if (!box) return;
        box.textContent = message;
        box.className = `alert-banner ${type === 'success' ? 'alert-success' : 'alert-error'}`;
    };

    const hideAlert = (box) => {
        if (!box) return;
        box.className = 'alert-banner is-hidden';
    };

    document.querySelectorAll('.sr-file').forEach((input) => {
        input.addEventListener('change', (e) => {
            const targetId = e.target.getAttribute('data-preview-target');
            const labelEl = document.getElementById(targetId);
            if (labelEl && e.target.files && e.target.files.length > 0) {
                labelEl.textContent = e.target.files[0].name;
            }
        });
    });

    if (btnToStep2) {
        btnToStep2.addEventListener('click', () => {
            hideAlert(alertBox);
            if (!siteTitleInput || siteTitleInput.value.trim() === '') {
                showAlert(alertBox, 'Please enter your Website Title before proceeding to Step 2.');
                siteTitleInput.focus();
                return;
            }
            paneStep1.classList.remove('is-visible');
            paneStep2.classList.add('is-visible');
            indStep1.classList.remove('is-active');
            indStep2.classList.add('is-active');
        });
    }

    if (btnBackStep1) {
        btnBackStep1.addEventListener('click', () => {
            hideAlert(alertBox);
            paneStep2.classList.remove('is-visible');
            paneStep1.classList.add('is-visible');
            indStep2.classList.remove('is-active');
            indStep1.classList.add('is-active');
        });
    }

    if (installerForm) {
        installerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert(alertBox);

            submitBtn.disabled = true;
            submitBtnText.textContent = 'Provisioning Database & .env...';

            try {
                const formData = new FormData(installerForm);
                const response = await fetch(installerForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(alertBox, result.message, 'success');
                    submitBtnText.textContent = 'Completed! Redirecting...';
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 900);
                } else {
                    showAlert(alertBox, result.message || 'Installation failed.');
                    submitBtn.disabled = false;
                    submitBtnText.textContent = 'Deploy Schema & Lock Installer';
                }
            } catch (err) {
                showAlert(alertBox, 'Network or server error occurred while provisioning the database.');
                submitBtn.disabled = false;
                submitBtnText.textContent = 'Deploy Schema & Lock Installer';
            }
        });
    }

    // ==========================================
    // 2. USER & ROLE MANAGEMENT MODULE LOGIC
    // ==========================================
    const rbacToast = document.getElementById('rbac-toast');
    const createUserForm = document.getElementById('create-user-form');
    const btnCreateUser = document.getElementById('btn-create-user');
    const createUserBtnText = document.getElementById('create-user-btn-text');
    const btnGenPass = document.getElementById('btn-gen-pass');
    const passInput = document.getElementById('u_password');

    const notifyRbac = (msg, type = 'success') => {
        if (!rbacToast) return;
        showAlert(rbacToast, msg, type);
        setTimeout(() => hideAlert(rbacToast), 4500);
    };

    if (btnGenPass && passInput) {
        btnGenPass.addEventListener('click', () => {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$';
            let generated = '';
            for (let i = 0; i < 10; i++) {
                generated += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            passInput.value = generated;
        });
    }

    if (createUserForm) {
        createUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            btnCreateUser.disabled = true;
            createUserBtnText.textContent = 'Provisioning Account...';

            try {
                const formData = new FormData(createUserForm);
                const response = await fetch(createUserForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                if (result.status === 'success') {
                    notifyRbac(result.message, 'success');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    notifyRbac(result.message || 'Could not create user.', 'error');
                    btnCreateUser.disabled = false;
                    createUserBtnText.textContent = 'Provision User Account';
                }
            } catch (err) {
                notifyRbac('Server error while provisioning user.', 'error');
                btnCreateUser.disabled = false;
                createUserBtnText.textContent = 'Provision User Account';
            }
        });
    }

    document.querySelectorAll('.js-role-switcher').forEach((selectEl) => {
        selectEl.addEventListener('change', async (e) => {
            const userId = e.target.getAttribute('data-user-id');
            const actionUrl = e.target.getAttribute('data-action-url');
            const newRoleId = e.target.value;

            const body = new URLSearchParams({ user_id: userId, role_id: newRoleId });

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: body,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const row = e.target.closest('.user-row');
                    if (row && result.role_slug) {
                        row.setAttribute('data-role-slug', result.role_slug);
                    }
                    notifyRbac(result.message, 'success');
                } else {
                    notifyRbac(result.message || 'Role update failed.', 'error');
                }
            } catch (err) {
                notifyRbac('Network error while updating role.', 'error');
            }
        });
    });

    document.querySelectorAll('.js-toggle-status').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            const targetBtn = e.currentTarget;
            const userId = targetBtn.getAttribute('data-user-id');
            const actionUrl = targetBtn.getAttribute('data-action-url');

            const body = new URLSearchParams({ user_id: userId });

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: body,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const badge = document.getElementById(`status-badge-${userId}`);
                    if (badge) {
                        badge.textContent = result.new_status.toUpperCase();
                        badge.className = `status-indicator status-${result.new_status}`;
                    }
                    targetBtn.textContent = result.new_status === 'active' ? 'Suspend' : 'Activate';
                    notifyRbac(result.message, 'success');
                } else {
                    notifyRbac(result.message || 'Failed to change status.', 'error');
                }
            } catch (err) {
                notifyRbac('Network error while toggling status.', 'error');
            }
        });
    });

    document.querySelectorAll('.js-delete-user').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            const targetBtn = e.currentTarget;
            const userId = targetBtn.getAttribute('data-user-id');
            const userName = targetBtn.getAttribute('data-user-name');
            const actionUrl = targetBtn.getAttribute('data-action-url');

            if (!window.confirm(`Permanently delete user "${userName}"?`)) {
                return;
            }

            const body = new URLSearchParams({ user_id: userId });

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: body,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const row = targetBtn.closest('.user-row');
                    if (row) row.remove();
                    notifyRbac(result.message, 'success');
                } else {
                    notifyRbac(result.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                notifyRbac('Network error while deleting user.', 'error');
            }
        });
    });

    const filterPills = document.querySelectorAll('.filter-pill');
    const searchInput = document.getElementById('user-search-input');
    const userRows = document.querySelectorAll('.user-row');
    let activeRoleFilter = 'all';

    const applyDirectoryFilters = () => {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        userRows.forEach((row) => {
            const rowRole = row.getAttribute('data-role-slug') || '';
            const searchBlob = row.getAttribute('data-search-text') || '';

            const matchesRole = (activeRoleFilter === 'all') || (rowRole === activeRoleFilter);
            const matchesSearch = (query === '') || searchBlob.includes(query);

            if (matchesRole && matchesSearch) {
                row.classList.remove('is-filtered-out');
            } else {
                row.classList.add('is-filtered-out');
            }
        });
    };

    filterPills.forEach((pill) => {
        pill.addEventListener('click', () => {
            filterPills.forEach((p) => p.classList.remove('is-active'));
            pill.classList.add('is-active');
            activeRoleFilter = pill.getAttribute('data-role-filter') || 'all';
            applyDirectoryFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyDirectoryFilters);
    }
});