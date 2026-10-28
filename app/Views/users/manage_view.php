<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $currentUserId   = (int) ($user['user_id'] ?? (session()->get('user_id') ?? 0));
    $currentRoleSlug = strtolower((string) ($user['role_slug'] ?? (session()->get('role_slug') ?? 'student')));
    if ((int) ($user['role_id'] ?? session()->get('role_id')) === 1) {
        $currentRoleSlug = 'super_admin';
    }
    $canManage = in_array($currentRoleSlug, ['super_admin', 'admin'], true)
        || (function_exists('cms_has_permission') && cms_has_permission(['users.create', 'users.edit', 'users.delete']));
?>

<!-- Global Notification Toast -->
<div id="rbac-toast" class="alert-banner is-hidden" role="alert"></div>

<div class="rbac-split-grid">

    <?php if ($canManage): ?>
    <!-- LEFT COLUMN: Provision New User Card -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">IDENTITY PROVISIONING</span>
                <h3 class="panel-title">Create User & Assign Role</h3>
            </div>
            <span class="req-badge">RBAC SYNCED</span>
        </div>

        <form id="create-user-form" action="<?= base_url('users/create') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="field-group">
                <label for="u_full_name" class="field-label">
                    <span>Full Name</span>
                    <span class="req-badge">REQUIRED</span>
                </label>
                <input type="text" id="u_full_name" name="full_name" class="field-input" placeholder="e.g., Aarav Sharma" required>
            </div>

            <div class="field-group">
                <label for="u_email" class="field-label">
                    <span>Login Email Address</span>
                    <span class="req-badge">UNIQUE</span>
                </label>
                <input type="email" id="u_email" name="email" class="field-input mono-input" placeholder="user@domain.com" required>
            </div>

            <div class="field-group">
                <label for="u_password" class="field-label">
                    <span>Initial Password</span>
                    <button type="button" id="btn-gen-pass" class="link-action">Auto-Generate</button>
                </label>
                <input type="text" id="u_password" name="password" class="field-input mono-input" placeholder="Min. 6 characters" required>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label for="u_role_id" class="field-label"><span>Assign Role</span></label>
                    <select id="u_role_id" name="role_id" class="field-select" required>
                        <?php foreach (($roles ?? []) as $r): ?>
                            <?php if ($r['role_slug'] === 'super_admin' && $currentRoleSlug !== 'super_admin') continue; ?>
                            <option value="<?= (int) $r['id'] ?>" <?= ($r['role_slug'] === 'student') ? 'selected' : '' ?>>
                                <?= esc($r['role_name']) ?> (<?= esc($r['role_slug']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label for="u_status" class="field-label"><span>Account Status</span></label>
                    <select id="u_status" name="status" class="field-select">
                        <option value="active" selected>Active (Can Sign In)</option>
                        <option value="suspended">Suspended (Blocked)</option>
                    </select>
                </div>
            </div>

            <button type="submit" id="btn-create-user" class="btn-primary btn-full">
                <span id="create-user-btn-text">Provision User Account</span>
                <span class="btn-arrow">+</span>
            </button>
        </form>

        <div class="role-legend">
            <span class="mono-label">SYNCHRONIZED ROLE MATRIX</span>
            <?php foreach (($roles ?? []) as $r): ?>
                <div class="legend-item">
                    <span class="role-badge role-<?= esc($r['role_slug']) ?>"><?= esc($r['role_slug']) ?></span>
                    <span class="legend-desc"><?= esc($r['description']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>
    <?php endif; ?>

    <!-- RIGHT COLUMN: Directory & Real-Time Role Switcher -->
    <section class="panel-card">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">DIRECTORY & ACCESS CONTROL</span>
                <h3 class="panel-title">Registered Platform Users</h3>
            </div>

            <div class="filter-bar" role="tablist">
                <button type="button" class="filter-pill is-active" data-role-filter="all">All (<?= esc((string) ($stats['total'] ?? count($users ?? []))) ?>)</button>
                <?php foreach (($roles ?? []) as $r): ?>
                    <button type="button" class="filter-pill" data-role-filter="<?= esc($r['role_slug']) ?>">
                        <?= esc($r['role_name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="directory-toolbar">
            <input type="search" id="user-search-input" class="field-input" placeholder="Search by name, email, or role slug...">
        </div>

        <div class="role-table-wrapper">
            <table class="role-table" id="users-directory-table">
                <thead>
                    <tr>
                        <th>User Identity</th>
                        <th>Assigned Role</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        <?php if ($canManage): ?>
                            <th class="text-right">Quick Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($users ?? []) as $u): ?>
                    <?php
                        $isSelf = ((int) $u['id'] === $currentUserId);
                        $uSlug  = (string) ($u['role_slug'] ?? 'student');
                    ?>
                    <tr class="user-row" id="user-row-<?= (int) $u['id'] ?>" data-user-id="<?= esc((string) $u['id']) ?>" data-role-slug="<?= esc($uSlug) ?>" data-search-text="<?= esc(strtolower($u['full_name'] . ' ' . $u['email'] . ' ' . $uSlug)) ?>">
                        <td>
                            <div class="identity-cell">
                                <div class="avatar-circle">
                                    <?= esc(strtoupper(substr($u['full_name'], 0, 1))) ?>
                                </div>
                                <div>
                                    <div class="identity-name">
                                        <?= esc($u['full_name']) ?>
                                        <?php if ($isSelf): ?>
                                            <span class="self-tag">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="identity-email"><?= esc($u['email']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <?php if ($canManage && ! $isSelf && ! ($uSlug === 'super_admin' && $currentRoleSlug !== 'super_admin')): ?>
                                <select class="field-select field-select-sm js-role-switcher" data-user-id="<?= esc((string) $u['id']) ?>" data-action-url="<?= base_url('users/update-role') ?>">
                                    <?php foreach (($roles ?? []) as $r): ?>
                                        <?php if ($r['role_slug'] === 'super_admin' && $currentRoleSlug !== 'super_admin') continue; ?>
                                        <option value="<?= esc((string) $r['id']) ?>" <?= ((int) $u['role_id'] === (int) $r['id']) ? 'selected' : '' ?>>
                                            <?= esc($r['role_name']) ?> (<?= esc($r['role_slug']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <span class="role-badge role-<?= esc($uSlug) ?>"><?= esc($u['role_name'] ?: $uSlug) ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="status-indicator status-<?= esc($u['status']) ?>" id="status-badge-<?= esc((string) $u['id']) ?>">
                                <?= esc(strtoupper($u['status'])) ?>
                            </span>
                        </td>

                        <td class="mono-cell muted-cell">
                            <?= ! empty($u['last_login']) ? esc(date('d M Y, H:i', strtotime($u['last_login']))) : 'Never signed in' ?>
                        </td>

                        <?php if ($canManage): ?>
                        <td class="text-right">
                            <?php if ($isSelf || ((int) $u['id'] === 1)): ?>
                                <span class="mono-note">PROTECTED</span>
                            <?php else: ?>
                                <div class="action-btn-group">
                                    <button type="button"
                                            class="btn-action js-toggle-status"
                                            data-user-id="<?= esc((string) $u['id']) ?>"
                                            data-action-url="<?= base_url('users/toggle-status') ?>"
                                            title="Suspend or Activate User">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                    <button type="button"
                                            class="btn-action btn-action-danger js-delete-user"
                                            data-user-id="<?= esc((string) $u['id']) ?>"
                                            data-user-name="<?= esc($u['full_name']) ?>"
                                            data-action-url="<?= base_url('users/delete') ?>"
                                            title="Delete User">
                                        Delete
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<!-- Dedicated User Directory Interactive Handlers (Suspend, Delete, Role Switch, Filter & Search) -->
<script>
(function () {
    'use strict';
    if (window.__USER_DIRECTORY_HANDLERS_BOUND__) return;
    window.__USER_DIRECTORY_HANDLERS_BOUND__ = true;

    var toastBox = document.getElementById('rbac-toast');
    var activeFilter = 'all';

    function showRbacToast(msg, isSuccess) {
        if (!toastBox) return;
        toastBox.textContent = msg;
        toastBox.className = 'alert-banner ' + (isSuccess ? 'alert-success' : 'alert-error');
        clearTimeout(toastBox._timer);
        toastBox._timer = setTimeout(function () {
            toastBox.className = 'alert-banner is-hidden';
        }, 3500);
    }

    function getCsrfHeaders() {
        var tokenMeta  = document.querySelector('meta[name="csrf-token"]');
        var headerMeta = document.querySelector('meta[name="csrf-header"]');
        var headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
        if (tokenMeta) {
            headers[headerMeta ? headerMeta.getAttribute('content') : 'X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');
        }
        return headers;
    }

    function buildPostBody(paramsObj) {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfInput = document.querySelector('input[name^="csrf_"]');
        if (csrfInput && tokenMeta) {
            paramsObj[csrfInput.name] = tokenMeta.getAttribute('content');
        }
        return new URLSearchParams(paramsObj).toString();
    }

    // 1. Auto-Generate Password Button
    var genPassBtn = document.getElementById('btn-gen-pass');
    if (genPassBtn) {
        genPassBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$';
            var pass = '';
            for (var i = 0; i < 10; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            var passInp = document.getElementById('u_password');
            if (passInp) passInp.value = pass;
        });
    }

    // 2. Role Filter Pills & Live Search
    function applyDirectoryFilter() {
        var searchInput = document.getElementById('user-search-input');
        var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
        document.querySelectorAll('#users-directory-table tbody .user-row').forEach(function (row) {
            var rSlug = row.getAttribute('data-role-slug') || '';
            var sText = row.getAttribute('data-search-text') || '';
            var roleMatch = (activeFilter === 'all') || (rSlug === activeFilter);
            var textMatch = (q === '') || (sText.indexOf(q) !== -1);
            if (roleMatch && textMatch) {
                row.classList.remove('is-filtered-out');
            } else {
                row.classList.add('is-filtered-out');
            }
        });
    }

    document.querySelectorAll('.filter-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.filter-pill').forEach(function (p) { p.classList.remove('is-active'); });
            pill.classList.add('is-active');
            activeFilter = pill.getAttribute('data-role-filter') || 'all';
            applyDirectoryFilter();
        });
    });

    var searchBox = document.getElementById('user-search-input');
    if (searchBox) {
        searchBox.addEventListener('input', applyDirectoryFilter);
    }

    // 3. Suspend / Activate User & Delete User Click Dispatcher
    document.addEventListener('click', function (e) {
        // A. Suspend / Activate User
        var toggleBtn = e.target.closest('.js-toggle-status');
        if (toggleBtn) {
            e.preventDefault();
            if (toggleBtn.dataset.busy === '1') return;
            var userId = toggleBtn.getAttribute('data-user-id');
            var url    = toggleBtn.getAttribute('data-action-url');
            if (!userId || !url) return;

            toggleBtn.dataset.busy = '1';
            toggleBtn.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: getCsrfHeaders(),
                body: buildPostBody({ user_id: userId })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    var newSt = res.new_status || (toggleBtn.textContent.trim().toLowerCase() === 'suspend' ? 'suspended' : 'active');
                    var badge = document.getElementById('status-badge-' + userId);
                    if (badge) {
                        badge.textContent = newSt.toUpperCase();
                        badge.className = 'status-indicator status-' + newSt;
                    }
                    toggleBtn.textContent = (newSt === 'active') ? 'Suspend' : 'Activate';
                    showRbacToast(res.message || 'Account status updated!', true);
                } else {
                    showRbacToast(res.message || 'Could not update status.', false);
                }
            })
            .catch(function () {
                showRbacToast('Network error while updating status.', false);
            })
            .finally(function () {
                toggleBtn.dataset.busy = '0';
                toggleBtn.disabled = false;
            });
            return;
        }

        // B. Delete User
        var delBtn = e.target.closest('.js-delete-user');
        if (delBtn) {
            e.preventDefault();
            if (delBtn.dataset.busy === '1') return;
            var delUserId   = delBtn.getAttribute('data-user-id');
            var delUserName = delBtn.getAttribute('data-user-name') || ('User #' + delUserId);
            var delUrl      = delBtn.getAttribute('data-action-url');
            if (!delUserId || !delUrl) return;

            if (!window.confirm('Are you sure you want to permanently delete user "' + delUserName + '"?')) {
                return;
            }

            delBtn.dataset.busy = '1';
            delBtn.disabled = true;

            fetch(delUrl, {
                method: 'POST',
                headers: getCsrfHeaders(),
                body: buildPostBody({ user_id: delUserId })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    var row = document.getElementById('user-row-' + delUserId) || delBtn.closest('tr');
                    if (row) row.remove();
                    showRbacToast(res.message || ('User "' + delUserName + '" deleted.'), true);
                } else {
                    delBtn.dataset.busy = '0';
                    delBtn.disabled = false;
                    showRbacToast(res.message || 'Failed to delete user.', false);
                }
            })
            .catch(function () {
                delBtn.dataset.busy = '0';
                delBtn.disabled = false;
                showRbacToast('Network error while deleting user.', false);
            });
        }
    });

    // 4. Role Switcher Dropdown Change Handler
    document.addEventListener('change', function (e) {
        var roleSelect = e.target.closest('.js-role-switcher');
        if (!roleSelect) return;

        var userId = roleSelect.getAttribute('data-user-id');
        var url    = roleSelect.getAttribute('data-action-url');
        var roleId = roleSelect.value;
        if (!userId || !url || !roleId) return;

        roleSelect.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: getCsrfHeaders(),
            body: buildPostBody({ user_id: userId, role_id: roleId })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.status === 'success') {
                showRbacToast(res.message || 'User role updated!', true);
            } else {
                showRbacToast(res.message || 'Failed to update role.', false);
            }
        })
        .catch(function () {
            showRbacToast('Network error while updating role.', false);
        })
        .finally(function () {
            roleSelect.disabled = false;
        });
    });
})();
</script>
<?= $this->endSection() ?>