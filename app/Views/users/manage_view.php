<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $currentUserId   = (int) ($user['user_id'] ?? 0);
    $currentRoleSlug = (string) ($user['role_slug'] ?? 'student');
    $canManage       = in_array($currentRoleSlug, ['super_admin', 'admin'], true);
?>

<!-- Global Notification Toast -->
<div id="rbac-toast" class="alert-banner is-hidden" role="alert"></div>

<!-- Main Split Grid: Left (Create User & Assign Role) + Right (Live User Directory) -->
<div class="rbac-split-grid">

    <?php if ($canManage): ?>
    <!-- LEFT COLUMN: Provision New User Card -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">IDENTITY PROVISIONING</span>
                <h3 class="panel-title">Create User & Assign Role</h3>
            </div>
            <span class="req-badge">RBAC</span>
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
                <input type="email" id="u_email" name="email" class="field-input mono-input" placeholder="teacher@domain.com" required>
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
                        <?php foreach ($roles as $r): ?>
                            <?php if ($r['role_slug'] === 'super_admin' && $currentRoleSlug !== 'super_admin') continue; ?>
                            <option value="<?= esc((string) $r['id']) ?>" <?= $r['role_slug'] === 'teacher' ? 'selected' : '' ?>>
                                <?= esc($r['role_name']) ?>
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

        <!-- Role Capability Reference -->
        <div class="role-legend">
            <span class="mono-label">PRE-CONFIGURED ROLE MATRIX</span>
            <?php foreach ($roles as $r): ?>
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

            <!-- Role Filter Pills -->
            <div class="filter-bar" role="tablist">
                <button type="button" class="filter-pill is-active" data-role-filter="all">All (<?= esc((string) ($stats['total'] ?? 0)) ?>)</button>
                <button type="button" class="filter-pill" data-role-filter="super_admin">Super Admin</button>
                <button type="button" class="filter-pill" data-role-filter="admin">Admin</button>
                <button type="button" class="filter-pill" data-role-filter="teacher">Teacher</button>
                <button type="button" class="filter-pill" data-role-filter="student">Student</button>
            </div>
        </div>

        <!-- Search Input -->
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
                    <?php foreach ($users as $u): ?>
                    <?php
                        $isSelf = ((int) $u['id'] === $currentUserId);
                        $uSlug  = (string) ($u['role_slug'] ?? 'student');
                    ?>
                    <tr class="user-row" data-user-id="<?= esc((string) $u['id']) ?>" data-role-slug="<?= esc($uSlug) ?>" data-search-text="<?= esc(strtolower($u['full_name'] . ' ' . $u['email'] . ' ' . $uSlug)) ?>">
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
                                    <?php foreach ($roles as $r): ?>
                                        <?php if ($r['role_slug'] === 'super_admin' && $currentRoleSlug !== 'super_admin') continue; ?>
                                        <option value="<?= esc((string) $r['id']) ?>" <?= ((int) $u['role_id'] === (int) $r['id']) ? 'selected' : '' ?>>
                                            <?= esc($r['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <span class="role-badge role-<?= esc($uSlug) ?>"><?= esc($u['role_name']) ?></span>
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
                            <?php if ($isSelf): ?>
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
<?= $this->endSection() ?>