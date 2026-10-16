(function () {
    const initPortalApp = () => {
        // ==========================================
        // COMMON HELPER FUNCTIONS
        // ==========================================
        const showAlert = (box, message, type = 'error') => {
            if (!box) return;
            box.textContent = message;
            box.className = `alert-banner ${type === 'success' ? 'alert-success' : 'alert-error'}`;
        };

        const hideAlert = (box) => {
            if (!box) return;
            box.className = 'alert-banner is-hidden';
        };

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
        // FILE UPLOAD PREVIEW (NO CLICK HIJACKING!)
        // Native HTML <label> + overlay input opens dialog natively.
        // JS only listens to 'change' event to show filename & preview!
        // ==========================================
        document.addEventListener('change', (e) => {
            const fileInput = e.target;
            if (!fileInput || fileInput.tagName !== 'INPUT' || fileInput.type !== 'file') return;
            if (!fileInput.files || fileInput.files.length === 0) return;

            const selectedFile = fileInput.files[0];
            const tile = fileInput.closest('.upload-tile');
            const targetId = fileInput.getAttribute('data-preview-target');
            const labelEl = targetId
                ? document.getElementById(targetId)
                : (tile ? tile.querySelector('.upload-title') : null);

            if (labelEl) {
                labelEl.textContent = `Selected: ${selectedFile.name}`;
            }

            if (tile) {
                tile.style.borderColor = '#10b981';
            }

            // Live Image Preview inside Settings Page (.asset-box)
            const assetBox = fileInput.closest('.asset-box');
            if (assetBox && selectedFile.type.startsWith('image/')) {
                const previewContainer = assetBox.querySelector('.asset-current-preview');
                if (previewContainer) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const isSmall = fileInput.id === 'site_favicon';
                        previewContainer.innerHTML = `<img src="${ev.target.result}" alt="Preview" class="${isSmall ? 'preview-img-sm' : 'preview-img'}">`;
                    };
                    reader.readAsDataURL(selectedFile);
                }
            }
        });

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

        // ==========================================
        // 3. GLOBAL SITE SETTINGS & SEO MODULE LOGIC
        // (Delegated Tab Click — Guaranteed to work!)
        // ==========================================
        document.addEventListener('click', (e) => {
			
            const tabBtn = e.target.closest('.settings-tab');
            if (!tabBtn) return;

            e.preventDefault();
            const targetId = tabBtn.getAttribute('data-tab-target');
            if (!targetId) return;

            document.querySelectorAll('.settings-tab').forEach((t) => t.classList.remove('is-active'));
            document.querySelectorAll('.settings-pane').forEach((p) => p.classList.remove('is-visible'));

            tabBtn.classList.add('is-active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add('is-visible');
            }
        });

        const settingsForm = document.getElementById('global-settings-form');
        const settingsToast = document.getElementById('settings-toast');
        const btnSaveSettings = document.getElementById('btn-save-settings');
        const saveSettingsText = document.getElementById('save-settings-text');
        const btnReinitSchema = document.getElementById('btn-reinit-schema');

        if (settingsForm) {
            settingsForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                hideAlert(settingsToast);

                if (btnSaveSettings) btnSaveSettings.disabled = true;
                if (saveSettingsText) saveSettingsText.textContent = 'Saving Configuration...';

                try {
                    const formData = new FormData(settingsForm);
                    const response = await fetch(settingsForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        showAlert(settingsToast, result.message, 'success');
                        if (saveSettingsText) saveSettingsText.textContent = 'Saved! Refreshing...';
                        setTimeout(() => window.location.reload(), 900);
                    } else {
                        showAlert(settingsToast, result.message || 'Failed to save settings.', 'error');
                        if (btnSaveSettings) btnSaveSettings.disabled = false;
                        if (saveSettingsText) saveSettingsText.textContent = 'Save Global Configuration';
                    }
                } catch (err) {
                    showAlert(settingsToast, 'Server error while saving global settings.', 'error');
                    if (btnSaveSettings) btnSaveSettings.disabled = false;
                    if (saveSettingsText) saveSettingsText.textContent = 'Save Global Configuration';
                }
            });
        }

        if (btnReinitSchema) {
            btnReinitSchema.addEventListener('click', async () => {
                const url = btnReinitSchema.getAttribute('data-action-url');
                btnReinitSchema.disabled = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    showAlert(settingsToast, result.message, result.status === 'success' ? 'success' : 'error');
                } catch (err) {
                    showAlert(settingsToast, 'Could not sync database schema.', 'error');
                } finally {
                    btnReinitSchema.disabled = false;
                }
            });
        }
    };

    // Run immediately if DOM is already parsed, otherwise wait for DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPortalApp);
    } else {
        initPortalApp();
    }
	
	// ==========================================
    // 4. ADD-ON ZIP UPLOAD & TOGGLE HANDLERS
    // ==========================================
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (form && form.id === 'addon-upload-form') {
            event.preventDefault();
            const toast = document.getElementById('addon-toast');
            const btn = document.getElementById('btn-install-addon');
            const btnText = document.getElementById('install-addon-text');

            if (toast) toast.className = 'alert-banner is-hidden';
            if (btn) btn.disabled = true;
            if (btnText) btnText.textContent = 'Unzipping & Running DB Migration...';

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    if (toast) {
                        toast.textContent = res.message;
                        toast.className = 'alert-banner alert-success';
                    }
                    if (btnText) btnText.textContent = 'Deployed! Reloading...';
                    setTimeout(function () { window.location.reload(); }, 900);
                } else {
                    if (toast) {
                        toast.textContent = res.message || 'Add-on installation failed.';
                        toast.className = 'alert-banner alert-error';
                    }
                    if (btn) btn.disabled = false;
                    if (btnText) btnText.textContent = 'Upload, Unzip & Initialize Add-On';
                }
            })
            .catch(function () {
                if (toast) {
                    toast.textContent = 'Server error while extracting ZIP package.';
                    toast.className = 'alert-banner alert-error';
                }
                if (btn) btn.disabled = false;
                if (btnText) btnText.textContent = 'Upload, Unzip & Initialize Add-On';
            });
        }
    });

    // Enable/Disable & Delete Add-On Click Handlers
    document.addEventListener('click', function (event) {
        // 1. Toggle Enable / Disable Add-On
        const toggleBtn = event.target.closest('.js-toggle-addon');
        if (toggleBtn) {
            event.preventDefault();
            const addonId = toggleBtn.getAttribute('data-addon-id');
            const actionUrl = toggleBtn.getAttribute('data-action-url');
            toggleBtn.disabled = true;
            toggleBtn.textContent = '...';

            fetch(actionUrl, {
                method: 'POST',
                body: new URLSearchParams({ addon_id: addonId }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    window.alert(res.message || 'Could not toggle add-on.');
                    toggleBtn.disabled = false;
                }
            });
            return;
        }

        // 2. Uninstall & Delete Add-On
        const delAddonBtn = event.target.closest('.js-delete-addon');
        if (delAddonBtn) {
            event.preventDefault();
            const addonId = delAddonBtn.getAttribute('data-addon-id');
            const addonName = delAddonBtn.getAttribute('data-addon-name');
            const actionUrl = delAddonBtn.getAttribute('data-action-url');

            if (!window.confirm('Uninstall "' + addonName + '" and remove its module files?')) {
                return;
            }

            delAddonBtn.disabled = true;
            delAddonBtn.textContent = 'Removing...';

            fetch(actionUrl, {
                method: 'POST',
                body: new URLSearchParams({ addon_id: addonId }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    window.alert(res.message || 'Could not delete add-on.');
                    delAddonBtn.disabled = false;
                    delAddonBtn.textContent = 'Delete';
                }
            });
        }
    });
	
	
	// ==========================================
    // 5. VISUAL DRAG & DROP PAGE BUILDER ENGINE
    // ==========================================
    const pbCanvas = document.getElementById('pb-canvas-dropzone');
    const pbBlocksInput = document.getElementById('pb_blocks_json');
    const pbTitleInput = document.getElementById('pb_title');
    const pbSlugInput = document.getElementById('pb_slug');
    const slugPreviewText = document.getElementById('slug-preview-text');
    const pbSaveForm = document.getElementById('pb-save-form');

    let pbBlocks = [];

    // Auto-generate slug when typing title (if slug hasn't been manually locked)
    if (pbTitleInput && pbSlugInput) {
        pbTitleInput.addEventListener('input', function () {
            if (!pbSlugInput.dataset.manualEdit) {
                const autoSlug = pbTitleInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\/]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                pbSlugInput.value = autoSlug;
                if (slugPreviewText) slugPreviewText.textContent = autoSlug || 'page-slug';
            }
        });

        pbSlugInput.addEventListener('input', function () {
            pbSlugInput.dataset.manualEdit = '1';
            if (slugPreviewText) slugPreviewText.textContent = pbSlugInput.value || 'page-slug';
        });
    }

    const syncBlocksToHiddenInput = function () {
        if (!pbBlocksInput) return;
        pbBlocksInput.value = JSON.stringify(pbBlocks);
    };

    const escapeHtml = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    const renderBuilderCanvas = function () {
        if (!pbCanvas) return;
        syncBlocksToHiddenInput();

        if (pbBlocks.length === 0) {
            pbCanvas.innerHTML = '<div class="muted-cell" style="text-align:center; padding:3rem 1rem;">Canvas is empty. Click or Drag any component from the left palette to start building your page!</div>';
            return;
        }

        pbCanvas.innerHTML = pbBlocks.map(function (blk, idx) {
            const d = blk.data || {};
            let fieldsHtml = '';

            if (blk.type === 'hero_banner') {
                fieldsHtml = `
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Top Badge Text</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="badge" value="${escapeHtml(d.badge)}">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Heading Title</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="heading" value="${escapeHtml(d.heading)}">
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Subheading Description</span></label>
                        <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="subheading" value="${escapeHtml(d.subheading)}">
                    </div>
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>CTA Button Label</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="btn_text" value="${escapeHtml(d.btn_text)}">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>CTA Button URL</span></label>
                            <input type="text" class="field-input mono-input js-blk-field" data-idx="${idx}" data-key="btn_url" value="${escapeHtml(d.btn_url)}">
                        </div>
                    </div>
                `;
            } else if (blk.type === 'rich_text') {
                fieldsHtml = `
                    <div class="rte-toolbar">
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="bold"><b>B</b></button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="italic"><i>I</i></button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="underline"><u>U</u></button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="H2">H2</button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="H3">H3</button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="P">Paragraph</button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="insertUnorderedList">• List</button>
                        <button type="button" class="rte-btn js-rte-cmd" data-cmd="createLink">🔗 Link</button>
                    </div>
                    <div class="rte-editable js-rte-area" contenteditable="true" data-idx="${idx}">${d.html || '<p>Type rich formatted content here...</p>'}</div>
                `;
            } else if (blk.type === 'image_media') {
                fieldsHtml = `
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Upload Image from Computer</span></label>
                            <input type="file" class="field-input js-pb-media-upload" data-idx="${idx}" accept="image/*">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Image URL (Auto-filled on upload)</span></label>
                            <input type="text" class="field-input mono-input js-blk-field" data-idx="${idx}" data-key="image_url" value="${escapeHtml(d.image_url)}">
                        </div>
                    </div>
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Image Caption</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="caption" value="${escapeHtml(d.caption)}">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Max Width (e.g. 100%, 600px)</span></label>
                            <input type="text" class="field-input mono-input js-blk-field" data-idx="${idx}" data-key="width" value="${escapeHtml(d.width || '100%')}">
                        </div>
                    </div>
                `;
            } else if (blk.type === 'cta_buttons') {
                fieldsHtml = `
                    <div class="field-group">
                        <label class="field-label"><span>Call-to-Action Banner Heading</span></label>
                        <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="title" value="${escapeHtml(d.title)}">
                    </div>
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Primary Button Text</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="primary_text" value="${escapeHtml(d.primary_text)}">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Primary Button Link</span></label>
                            <input type="text" class="field-input mono-input js-blk-field" data-idx="${idx}" data-key="primary_url" value="${escapeHtml(d.primary_url)}">
                        </div>
                    </div>
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Secondary Button Text</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="secondary_text" value="${escapeHtml(d.secondary_text)}">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Secondary Button Link</span></label>
                            <input type="text" class="field-input mono-input js-blk-field" data-idx="${idx}" data-key="secondary_url" value="${escapeHtml(d.secondary_url)}">
                        </div>
                    </div>
                `;
            } else if (blk.type === 'interactive_quiz') {
                fieldsHtml = `
                    <div class="field-group">
                        <label class="field-label"><span>Quiz Question</span></label>
                        <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="question" value="${escapeHtml(d.question)}">
                    </div>
                    <div class="grid-2-col">
                        <div class="field-group"><label class="field-label"><span>Option A</span></label><input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="option_a" value="${escapeHtml(d.option_a)}"></div>
                        <div class="field-group"><label class="field-label"><span>Option B</span></label><input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="option_b" value="${escapeHtml(d.option_b)}"></div>
                        <div class="field-group"><label class="field-label"><span>Option C</span></label><input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="option_c" value="${escapeHtml(d.option_c)}"></div>
                        <div class="field-group"><label class="field-label"><span>Option D</span></label><input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="option_d" value="${escapeHtml(d.option_d)}"></div>
                    </div>
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Correct Option (A, B, C, or D)</span></label>
                            <input type="text" class="field-input mono-input js-blk-field" data-idx="${idx}" data-key="correct_index" value="${escapeHtml(d.correct_index || 'A')}">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Answer Explanation</span></label>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="explanation" value="${escapeHtml(d.explanation)}">
                        </div>
                    </div>
                `;
            } else if (blk.type === 'feature_grid') {
                fieldsHtml = `
                    <div class="field-group">
                        <label class="field-label"><span>Section Heading</span></label>
                        <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="section_title" value="${escapeHtml(d.section_title)}">
                    </div>
                    <div class="grid-3-col">
                        <div>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="card1_title" value="${escapeHtml(d.card1_title)}" placeholder="Card 1 Title" style="margin-bottom:0.4rem;">
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="card1_desc" value="${escapeHtml(d.card1_desc)}" placeholder="Card 1 Description">
                        </div>
                        <div>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="card2_title" value="${escapeHtml(d.card2_title)}" placeholder="Card 2 Title" style="margin-bottom:0.4rem;">
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="card2_desc" value="${escapeHtml(d.card2_desc)}" placeholder="Card 2 Description">
                        </div>
                        <div>
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="card3_title" value="${escapeHtml(d.card3_title)}" placeholder="Card 3 Title" style="margin-bottom:0.4rem;">
                            <input type="text" class="field-input js-blk-field" data-idx="${idx}" data-key="card3_desc" value="${escapeHtml(d.card3_desc)}" placeholder="Card 3 Description">
                        </div>
                    </div>
                `;
            } else {
                fieldsHtml = `
                    <div class="field-group">
                        <label class="field-label"><span>Raw HTML / Custom Embed Snippet</span></label>
                        <textarea rows="4" class="field-input code-editor js-blk-field" data-idx="${idx}" data-key="raw_code">${escapeHtml(d.raw_code)}</textarea>
                    </div>
                `;
            }

            return `
                <div class="pb-block-card" draggable="true" data-block-idx="${idx}">
                    <div class="pb-block-header">
                        <div>
                            <span class="role-badge role-admin">0${idx + 1} // ${escapeHtml(blk.type.toUpperCase())}</span>
                        </div>
                        <div class="action-btn-group">
                            <button type="button" class="btn-action js-pb-move-up" data-idx="${idx}">↑ Up</button>
                            <button type="button" class="btn-action js-pb-move-down" data-idx="${idx}">↓ Down</button>
                            <button type="button" class="btn-action btn-action-danger js-pb-remove-block" data-idx="${idx}">✕ Remove</button>
                        </div>
                    </div>
                    ${fieldsHtml}
                </div>
            `;
        }).join('');
    };

    // Initialize existing blocks on Editor load
    if (pbCanvas && pbBlocksInput) {
        try {
            pbBlocks = JSON.parse(pbBlocksInput.value || '[]');
        } catch (e) {
            pbBlocks = [];
        }
        renderBuilderCanvas();
    }

    // Input syncing for standard fields & Rich Text areas
    document.addEventListener('input', function (event) {
        const field = event.target.closest('.js-blk-field');
        if (field) {
            const idx = parseInt(field.getAttribute('data-idx'), 10);
            const key = field.getAttribute('data-key');
            if (pbBlocks[idx] && key) {
                pbBlocks[idx].data[key] = field.value;
                syncBlocksToHiddenInput();
            }
            return;
        }

        const rteArea = event.target.closest('.js-rte-area');
        if (rteArea) {
            const idx = parseInt(rteArea.getAttribute('data-idx'), 10);
            if (pbBlocks[idx]) {
                pbBlocks[idx].data.html = rteArea.innerHTML;
                syncBlocksToHiddenInput();
            }
        }
    });

    // Click Handlers for Adding/Moving/Removing Blocks, RTE Toolbar & Frontend Quiz
    document.addEventListener('click', function (event) {
        // Add Block from Palette
        const addBtn = event.target.closest('.js-pb-add-block');
        if (addBtn && pbCanvas) {
            const key = addBtn.getAttribute('data-comp-key');
            let defaultData = {};
            try {
                defaultData = JSON.parse(addBtn.getAttribute('data-comp-schema') || '{}');
            } catch (e) {}

            pbBlocks.push({
                id: 'blk_' + Date.now(),
                type: key,
                data: defaultData
            });
            renderBuilderCanvas();
            return;
        }

        // Move Block Up
        const upBtn = event.target.closest('.js-pb-move-up');
        if (upBtn) {
            const idx = parseInt(upBtn.getAttribute('data-idx'), 10);
            if (idx > 0) {
                const temp = pbBlocks[idx - 1];
                pbBlocks[idx - 1] = pbBlocks[idx];
                pbBlocks[idx] = temp;
                renderBuilderCanvas();
            }
            return;
        }

        // Move Block Down
        const downBtn = event.target.closest('.js-pb-move-down');
        if (downBtn) {
            const idx = parseInt(downBtn.getAttribute('data-idx'), 10);
            if (idx < pbBlocks.length - 1) {
                const temp = pbBlocks[idx + 1];
                pbBlocks[idx + 1] = pbBlocks[idx];
                pbBlocks[idx] = temp;
                renderBuilderCanvas();
            }
            return;
        }

        // Remove Block
        const rmBtn = event.target.closest('.js-pb-remove-block');
        if (rmBtn) {
            const idx = parseInt(rmBtn.getAttribute('data-idx'), 10);
            pbBlocks.splice(idx, 1);
            renderBuilderCanvas();
            return;
        }

        // Clear All Blocks
        const clearBtn = event.target.closest('#btn-pb-clear-all');
        if (clearBtn) {
            if (window.confirm('Clear all blocks from this page canvas?')) {
                pbBlocks = [];
                renderBuilderCanvas();
            }
            return;
        }

        // Rich Text WYSIWYG Toolbar Commands
        const rteBtn = event.target.closest('.js-rte-cmd');
        if (rteBtn) {
            event.preventDefault();
            const cmd = rteBtn.getAttribute('data-cmd');
            const val = rteBtn.getAttribute('data-val') || null;
            if (cmd === 'createLink') {
                const url = window.prompt('Enter hyperlink URL:', 'https://');
                if (url) document.execCommand('createLink', false, url);
            } else {
                document.execCommand(cmd, false, val);
            }
            return;
        }

        // Toggle Component Enabled/Disabled in Registry
        const compToggle = event.target.closest('.js-pb-toggle-comp');
        if (compToggle) {
            const compId = compToggle.getAttribute('data-comp-id');
            const url = compToggle.getAttribute('data-action-url');
            fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ component_id: compId }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function () { window.location.reload(); });
            return;
        }

        // Delete Page from List
        const delPageBtn = event.target.closest('.js-pb-delete-page');
        if (delPageBtn) {
            const pageId = delPageBtn.getAttribute('data-page-id');
            const title = delPageBtn.getAttribute('data-page-title');
            const url = delPageBtn.getAttribute('data-action-url');
            if (!window.confirm('Delete dynamic page "' + title + '"?')) return;

            fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ page_id: pageId }),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function () { window.location.reload(); });
            return;
        }

        // Public Frontend Interactive Quiz Checker
        const quizOpt = event.target.closest('.js-quiz-answer');
        if (quizOpt) {
            const card = quizOpt.closest('.cms-quiz-card');
            const chosen = quizOpt.getAttribute('data-choice');
            const correct = card.getAttribute('data-correct');
            const explanation = card.getAttribute('data-explanation');
            const feedback = card.querySelector('.js-quiz-feedback');

            if (feedback) {
                if (chosen === correct) {
                    feedback.textContent = '✓ Correct! ' + explanation;
                    feedback.className = 'alert-banner alert-success js-quiz-feedback';
                } else {
                    feedback.textContent = '✕ Not quite! Try another option.';
                    feedback.className = 'alert-banner alert-error js-quiz-feedback';
                }
            }
        }
    });

    // Direct Image Upload inside Image Media Block
    document.addEventListener('change', function (event) {
        const mediaInput = event.target.closest('.js-pb-media-upload');
        if (!mediaInput || !mediaInput.files || mediaInput.files.length === 0) return;

        const idx = parseInt(mediaInput.getAttribute('data-idx'), 10);
        const uploadUrl = pbSaveForm ? pbSaveForm.getAttribute('data-upload-url') : '';
        if (!uploadUrl) return;

        const formData = new FormData();
        formData.append('media_file', mediaInput.files[0]);

        fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.status === 'success' && pbBlocks[idx]) {
                pbBlocks[idx].data.image_url = res.image_url;
                renderBuilderCanvas();
            } else {
                window.alert(res.message || 'Image upload failed.');
            }
        });
    });

    // Save Page Form Submission
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (form && form.id === 'pb-save-form') {
            event.preventDefault();
            syncBlocksToHiddenInput();

            const toast = document.getElementById('pb-editor-toast');
            const btn = document.getElementById('btn-pb-save');
            const btnText = document.getElementById('btn-pb-save-text');

            if (btn) btn.disabled = true;
            if (btnText) btnText.textContent = 'Saving Page...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    if (toast) {
                        toast.textContent = res.message;
                        toast.className = 'alert-banner alert-success';
                    }
                    if (btnText) btnText.textContent = 'Published!';
                    setTimeout(function () {
                        window.location.href = '/addons/page-builder/editor/' + res.page_id;
                    }, 800);
                } else {
                    if (toast) {
                        toast.textContent = res.message || 'Error saving page.';
                        toast.className = 'alert-banner alert-error';
                    }
                    if (btn) btn.disabled = false;
                    if (btnText) btnText.textContent = 'Save & Publish Page ✓';
                }
            });
        }
    });
	
	// ==========================================
    // 6. HTML5 DRAG & DROP + FOOTER BUILDER ENGINE
    // ==========================================
    let draggedPaletteData = null;
    let draggedCanvasIdx = null;

    // A. Start Dragging from Left Palette OR Existing Canvas Block
    document.addEventListener('dragstart', function (e) {
        const paletteItem = e.target.closest('.pb-palette-item');
        if (paletteItem) {
            draggedCanvasIdx = null;
            draggedPaletteData = {
                type: paletteItem.getAttribute('data-comp-key'),
                schema: paletteItem.getAttribute('data-comp-schema') || '{}'
            };
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', draggedPaletteData.type);
            return;
        }

        const blockCard = e.target.closest('.pb-block-card');
        if (blockCard && blockCard.hasAttribute('data-block-idx')) {
            draggedPaletteData = null;
            draggedCanvasIdx = parseInt(blockCard.getAttribute('data-block-idx'), 10);
            blockCard.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(draggedCanvasIdx));
        }
    });

    document.addEventListener('dragend', function (e) {
        const blockCard = e.target.closest('.pb-block-card');
        if (blockCard) blockCard.classList.remove('is-dragging');
        document.querySelectorAll('.is-drag-over').forEach(function (el) {
            el.classList.remove('is-drag-over');
        });
    });

    // B. Allow Drop on Page Canvas & Footer Section Columns
    document.addEventListener('dragover', function (e) {
        const dropZone = e.target.closest('#pb-canvas-dropzone, .pb-footer-col-dropzone');
        if (!dropZone) return;
        e.preventDefault();
        dropZone.classList.add('is-drag-over');
    });

    document.addEventListener('dragleave', function (e) {
        const dropZone = e.target.closest('#pb-canvas-dropzone, .pb-footer-col-dropzone');
        if (dropZone && !dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('is-drag-over');
        }
    });

    // C. Handle Drop Event (Page Canvas Drop + Reorder + Footer Column Drop)
    document.addEventListener('drop', function (e) {
        const pageCanvas = e.target.closest('#pb-canvas-dropzone');
        const footerColZone = e.target.closest('.pb-footer-col-dropzone');

        if (!pageCanvas && !footerColZone) return;
        e.preventDefault();

        document.querySelectorAll('.is-drag-over').forEach(function (el) {
            el.classList.remove('is-drag-over');
        });

        // 1. Dropped onto Main Page Builder Canvas
        if (pageCanvas && typeof renderBuilderCanvas === 'function') {
            const targetCard = e.target.closest('.pb-block-card');
            const targetIdx = targetCard ? parseInt(targetCard.getAttribute('data-block-idx'), 10) : pbBlocks.length;

            if (draggedPaletteData) {
                let defaultData = {};
                try { defaultData = JSON.parse(draggedPaletteData.schema); } catch (err) {}
                const newBlk = { id: 'blk_' + Date.now(), type: draggedPaletteData.type, data: defaultData };
                pbBlocks.splice(targetIdx, 0, newBlk);
                renderBuilderCanvas();
            } else if (draggedCanvasIdx !== null && !isNaN(draggedCanvasIdx)) {
                const movedItem = pbBlocks.splice(draggedCanvasIdx, 1)[0];
                const insertAt = isNaN(targetIdx) ? pbBlocks.length : targetIdx;
                pbBlocks.splice(insertAt, 0, movedItem);
                renderBuilderCanvas();
            }
            draggedPaletteData = null;
            draggedCanvasIdx = null;
            return;
        }

        // 2. Dropped onto a Footer Builder Section Column
        if (footerColZone && draggedPaletteData) {
            const colIndex = parseInt(footerColZone.getAttribute('data-col-idx'), 10);
            addBlockToFooterCol(colIndex, draggedPaletteData.type, draggedPaletteData.schema);
            draggedPaletteData = null;
        }
    });
	// ==========================================
    // D. MULTI-SECTION FOOTER BUILDER RENDERER (ALL 7 BLOCK FORMS SUPPORTED)
    // ==========================================
    const footerWrap = document.getElementById('pb-footer-columns-wrap');
    const footerJsonInput = document.getElementById('pb_footer_sections_json');
    const footerColsSelect = document.getElementById('pb_footer_cols');
    let footerSections = [[], [], [], []];

    const syncFooterJson = function () {
        if (footerJsonInput) footerJsonInput.value = JSON.stringify(footerSections);
    };

    // Generates the exact same full form fields for all 7 component types inside Footer Columns
    const buildFooterBlockFieldsHtml = function (blk, c, bIdx) {
        const d = blk.data || {};
        const attr = `data-col="${c}" data-bidx="${bIdx}"`;

        if (blk.type === 'hero_banner') {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Top Badge Text</span></label>
                    <input type="text" class="field-input js-f-field" ${attr} data-key="badge" value="${escapeHtml(d.badge)}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Heading Title</span></label>
                    <input type="text" class="field-input js-f-field" ${attr} data-key="heading" value="${escapeHtml(d.heading)}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Subheading Description</span></label>
                    <input type="text" class="field-input js-f-field" ${attr} data-key="subheading" value="${escapeHtml(d.subheading)}">
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>CTA Button Label</span></label>
                        <input type="text" class="field-input js-f-field" ${attr} data-key="btn_text" value="${escapeHtml(d.btn_text)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>CTA Button URL</span></label>
                        <input type="text" class="field-input mono-input js-f-field" ${attr} data-key="btn_url" value="${escapeHtml(d.btn_url)}">
                    </div>
                </div>
            `;
        } else if (blk.type === 'rich_text') {
            return `
                <div class="rte-toolbar">
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="bold"><b>B</b></button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="italic"><i>I</i></button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="underline"><u>U</u></button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="H3">H3</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="P">P</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="insertUnorderedList">• List</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="createLink">🔗 Link</button>
                </div>
                <div class="rte-editable js-f-rte" contenteditable="true" ${attr}>${d.html || '<p>Type rich formatted content here...</p>'}</div>
            `;
        } else if (blk.type === 'image_media') {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Upload Image from Computer</span></label>
                    <input type="file" class="field-input js-f-media-upload" ${attr} accept="image/*">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Image URL (Auto-filled on upload)</span></label>
                    <input type="text" class="field-input mono-input js-f-field" ${attr} data-key="image_url" value="${escapeHtml(d.image_url)}">
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Caption</span></label>
                        <input type="text" class="field-input js-f-field" ${attr} data-key="caption" value="${escapeHtml(d.caption)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Max Width</span></label>
                        <input type="text" class="field-input mono-input js-f-field" ${attr} data-key="width" value="${escapeHtml(d.width || '100%')}">
                    </div>
                </div>
            `;
        } else if (blk.type === 'cta_buttons') {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Banner Heading</span></label>
                    <input type="text" class="field-input js-f-field" ${attr} data-key="title" value="${escapeHtml(d.title)}">
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Primary Button Text</span></label>
                        <input type="text" class="field-input js-f-field" ${attr} data-key="primary_text" value="${escapeHtml(d.primary_text)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Primary Button Link</span></label>
                        <input type="text" class="field-input mono-input js-f-field" ${attr} data-key="primary_url" value="${escapeHtml(d.primary_url)}">
                    </div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Secondary Button Text</span></label>
                        <input type="text" class="field-input js-f-field" ${attr} data-key="secondary_text" value="${escapeHtml(d.secondary_text)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Secondary Button Link</span></label>
                        <input type="text" class="field-input mono-input js-f-field" ${attr} data-key="secondary_url" value="${escapeHtml(d.secondary_url)}">
                    </div>
                </div>
            `;
        } else if (blk.type === 'interactive_quiz') {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Quiz Question</span></label>
                    <input type="text" class="field-input js-f-field" ${attr} data-key="question" value="${escapeHtml(d.question)}">
                </div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Option A</span></label><input type="text" class="field-input js-f-field" ${attr} data-key="option_a" value="${escapeHtml(d.option_a)}"></div>
                    <div class="field-group"><label class="field-label"><span>Option B</span></label><input type="text" class="field-input js-f-field" ${attr} data-key="option_b" value="${escapeHtml(d.option_b)}"></div>
                    <div class="field-group"><label class="field-label"><span>Option C</span></label><input type="text" class="field-input js-f-field" ${attr} data-key="option_c" value="${escapeHtml(d.option_c)}"></div>
                    <div class="field-group"><label class="field-label"><span>Option D</span></label><input type="text" class="field-input js-f-field" ${attr} data-key="option_d" value="${escapeHtml(d.option_d)}"></div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Correct Option (A/B/C/D)</span></label>
                        <input type="text" class="field-input mono-input js-f-field" ${attr} data-key="correct_index" value="${escapeHtml(d.correct_index || 'A')}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Explanation</span></label>
                        <input type="text" class="field-input js-f-field" ${attr} data-key="explanation" value="${escapeHtml(d.explanation)}">
                    </div>
                </div>
            `;
        } else if (blk.type === 'feature_grid') {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Section Heading</span></label>
                    <input type="text" class="field-input js-f-field" ${attr} data-key="section_title" value="${escapeHtml(d.section_title)}">
                </div>
                <div class="field-group">
                    <input type="text" class="field-input js-f-field" ${attr} data-key="card1_title" value="${escapeHtml(d.card1_title)}" placeholder="Card 1 Title" style="margin-bottom:0.35rem;">
                    <input type="text" class="field-input js-f-field" ${attr} data-key="card1_desc" value="${escapeHtml(d.card1_desc)}" placeholder="Card 1 Description">
                </div>
                <div class="field-group">
                    <input type="text" class="field-input js-f-field" ${attr} data-key="card2_title" value="${escapeHtml(d.card2_title)}" placeholder="Card 2 Title" style="margin-bottom:0.35rem;">
                    <input type="text" class="field-input js-f-field" ${attr} data-key="card2_desc" value="${escapeHtml(d.card2_desc)}" placeholder="Card 2 Description">
                </div>
                <div class="field-group">
                    <input type="text" class="field-input js-f-field" ${attr} data-key="card3_title" value="${escapeHtml(d.card3_title)}" placeholder="Card 3 Title" style="margin-bottom:0.35rem;">
                    <input type="text" class="field-input js-f-field" ${attr} data-key="card3_desc" value="${escapeHtml(d.card3_desc)}" placeholder="Card 3 Description">
                </div>
            `;
        } else {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Raw HTML / Custom Embed Snippet</span></label>
                    <textarea rows="4" class="field-input code-editor js-f-field" ${attr} data-key="raw_code">${escapeHtml(d.raw_code || '')}</textarea>
                </div>
            `;
        }
    };

    const renderFooterBuilder = function () {
        if (!footerWrap || !footerColsSelect) return;
        const colCount = parseInt(footerColsSelect.value || '3', 10);
        syncFooterJson();

        let html = '';
        for (let c = 0; c < colCount; c++) {
            if (!Array.isArray(footerSections[c])) footerSections[c] = [];
            const colBlocks = footerSections[c];

            const blocksHtml = colBlocks.map(function (blk, bIdx) {
                return `
                    <div class="pb-block-card">
                        <div class="pb-block-header">
                            <span class="role-badge role-admin">0${bIdx + 1} // ${escapeHtml(blk.type.toUpperCase())}</span>
                            <div class="action-btn-group">
                                <button type="button" class="btn-action js-f-move-up" data-col="${c}" data-bidx="${bIdx}">↑</button>
                                <button type="button" class="btn-action js-f-move-down" data-col="${c}" data-bidx="${bIdx}">↓</button>
                                <button type="button" class="btn-action btn-action-danger js-f-rm-blk" data-col="${c}" data-bidx="${bIdx}">✕</button>
                            </div>
                        </div>
                        ${buildFooterBlockFieldsHtml(blk, c, bIdx)}
                    </div>
                `;
            }).join('');

            html += `
                <div class="pb-footer-col-dropzone" data-col-idx="${c}">
                    <div class="panel-header" style="margin-bottom:0.5rem; padding-bottom:0.5rem;">
                        <span class="mono-label">FOOTER SECTION 0${c + 1}</span>
                        <span class="opt-badge">${colBlocks.length} BLOCKS</span>
                    </div>
                    ${blocksHtml || '<div class="muted-cell" style="font-size:0.8rem; text-align:center; padding:1.5rem 0;">Drag & Drop any block here</div>'}
                </div>
            `;
        }
		footerWrap.className = 'pb-footer-builder-grid cols-' + colCount;
        footerWrap.innerHTML = html;
    };

    const addBlockToFooterCol = function (colIdx, type, schemaStr) {
        let defaultData = {};
        try { defaultData = JSON.parse(schemaStr || '{}'); } catch (e) {}
        if (!Array.isArray(footerSections[colIdx])) footerSections[colIdx] = [];
        footerSections[colIdx].push({ id: 'fblk_' + Date.now(), type: type, data: defaultData });
        renderFooterBuilder();
    };

    if (footerWrap && footerJsonInput) {
        try {
            const parsed = JSON.parse(footerJsonInput.value || '[[],[],[]]');
            footerSections = [parsed[0] || [], parsed[1] || [], parsed[2] || [], parsed[3] || []];
        } catch (e) {
            footerSections = [[], [], [], []];
        }
        renderFooterBuilder();
        if (footerColsSelect) {
            footerColsSelect.addEventListener('change', renderFooterBuilder);
        }
    }

    // Click "+ Add", Move Up/Down, or Remove inside Footer Builder
    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.js-pb-add-block');
        if (addBtn && footerWrap) {
            const targetColSel = document.getElementById('pb_target_footer_col');
            const colIdx = targetColSel ? parseInt(targetColSel.value || '0', 10) : 0;
            addBlockToFooterCol(colIdx, addBtn.getAttribute('data-comp-key'), addBtn.getAttribute('data-comp-schema'));
            return;
        }

        const fUpBtn = e.target.closest('.js-f-move-up');
        if (fUpBtn) {
            const c = parseInt(fUpBtn.getAttribute('data-col'), 10);
            const b = parseInt(fUpBtn.getAttribute('data-bidx'), 10);
            if (footerSections[c] && b > 0) {
                const tmp = footerSections[c][b - 1];
                footerSections[c][b - 1] = footerSections[c][b];
                footerSections[c][b] = tmp;
                renderFooterBuilder();
            }
            return;
        }

        const fDownBtn = e.target.closest('.js-f-move-down');
        if (fDownBtn) {
            const c = parseInt(fDownBtn.getAttribute('data-col'), 10);
            const b = parseInt(fDownBtn.getAttribute('data-bidx'), 10);
            if (footerSections[c] && b < footerSections[c].length - 1) {
                const tmp = footerSections[c][b + 1];
                footerSections[c][b + 1] = footerSections[c][b];
                footerSections[c][b] = tmp;
                renderFooterBuilder();
            }
            return;
        }

        const rmFooterBlk = e.target.closest('.js-f-rm-blk');
        if (rmFooterBlk) {
            const c = parseInt(rmFooterBlk.getAttribute('data-col'), 10);
            const b = parseInt(rmFooterBlk.getAttribute('data-bidx'), 10);
            if (footerSections[c]) {
                footerSections[c].splice(b, 1);
                renderFooterBuilder();
            }
        }
    });

    // Direct Image Upload inside Footer Builder Image Media Block
    document.addEventListener('change', function (event) {
        const fMediaInput = event.target.closest('.js-f-media-upload');
        if (!fMediaInput || !fMediaInput.files || fMediaInput.files.length === 0) return;

        const c = parseInt(fMediaInput.getAttribute('data-col'), 10);
        const b = parseInt(fMediaInput.getAttribute('data-bidx'), 10);
        const baseUrl = document.body.getAttribute('data-base-url') || '/';
        const uploadUrl = baseUrl.replace(/\/$/, '') + '/addons/page-builder/upload-media';

        const formData = new FormData();
        formData.append('media_file', fMediaInput.files[0]);

        fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.status === 'success' && footerSections[c] && footerSections[c][b]) {
                footerSections[c][b].data.image_url = res.image_url;
                renderFooterBuilder();
            } else {
                window.alert(res.message || 'Image upload failed.');
            }
        });
    });

    document.addEventListener('input', function (e) {
        const fField = e.target.closest('.js-f-field');
        if (fField) {
            const c = parseInt(fField.getAttribute('data-col'), 10);
            const b = parseInt(fField.getAttribute('data-bidx'), 10);
            const k = fField.getAttribute('data-key');
            if (footerSections[c] && footerSections[c][b]) {
                footerSections[c][b].data[k] = fField.value;
                syncFooterJson();
            }
            return;
        }

        const fRte = e.target.closest('.js-f-rte');
        if (fRte) {
            const c = parseInt(fRte.getAttribute('data-col'), 10);
            const b = parseInt(fRte.getAttribute('data-bidx'), 10);
            if (footerSections[c] && footerSections[c][b]) {
                footerSections[c][b].data.html = fRte.innerHTML;
                syncFooterJson();
            }
        }
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.id === 'pb-footer-form') {
            e.preventDefault();
            syncFooterJson();
            const toast = document.getElementById('pb-editor-toast');
            const btnText = document.getElementById('btn-save-footer-text');
            if (btnText) btnText.textContent = 'Saving Footer...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (toast) {
                    toast.textContent = res.message;
                    toast.className = 'alert-banner ' + (res.status === 'success' ? 'alert-success' : 'alert-error');
                }
                if (btnText) btnText.textContent = 'Save Footer Template ✓';
                if (res.status === 'success') {
                    setTimeout(function () { window.location.reload(); }, 700);
                }
            });
        }
    });
	
	
})();