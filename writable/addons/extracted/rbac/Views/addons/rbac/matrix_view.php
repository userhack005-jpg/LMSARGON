<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="rbac-main-grid rbac-section-gap">
        <!-- Left Column: Custom Role Builder + 1-Click Clone -->
        <div class="rbac-left-stack">
            <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
            <section class="console-card rbac-card-gap">
                <div class="card-head">
                    <div>
                        <span class="mono-label text-emerald">CREATE OR EDIT ROLE</span>
                        <h3 id="rbac-role-form-title">Custom Role Builder</h3>
                    </div>
                </div>

                <form action="<?= base_url('addons/rbac/save-role') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="role_id" value="0">

                    <div class="field-group">
                        <label class="field-label"><span>Role Name *</span></label>
                        <input type="text" name="role_name" id="role_name" class="field-input" placeholder="e.g. Academic Coordinator" required>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Role Key (Slug)</span></label>
                        <input type="text" name="role_slug" id="role_slug" class="field-input mono-input" placeholder="e.g. academic_coordinator">
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Portal Scope</span></label>
                        <select name="portal_type" id="role_portal" class="field-select">
                            <option value="backend">Staff / Backend Panel</option>
                            <option value="frontend">Learner / Family Portal</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Short Description</span></label>
                        <input type="text" name="description" id="role_desc" class="field-input" placeholder="Responsibilities summary...">
                    </div>

                    <div class="field-group">
                        <label class="rbac-check-label">
                            <input type="checkbox" name="is_default_front" id="role_def_front" value="1">
                            <span>Set as Default Role for New Signups</span>
                        </label>
                    </div>

                    <div class="action-btn-group rbac-form-actions">
                        <button type="submit" class="btn-primary">💾 Save Role</button>
                        <button type="button" class="btn-secondary" id="js-rbac-role-reset">Reset</button>
                    </div>
                </form>

                <div style="margin-top: 1.15rem; padding-top: 0.9rem; border-top: 1px solid var(--border-subtle);">
                    <span class="mono-label">EXISTING ROLES:</span>
                    <div style="display:flex; flex-direction:column; gap:0.4rem; margin-top:0.45rem;">
                        <?php foreach (($roles ?? []) as $rItem): ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; font-size:0.83rem;">
                                <span><strong><?= esc($rItem['role_name']) ?></strong></span>
                                <div class="action-btn-group">
                                    <button type="button"
                                            class="btn-action js-edit-rbac-role"
                                            data-id="<?= (int) $rItem['id'] ?>"
                                            data-name="<?= esc($rItem['role_name']) ?>"
                                            data-slug="<?= esc($rItem['role_slug']) ?>"
                                            data-portal="<?= esc($rItem['portal_type'] ?? 'frontend') ?>"
                                            data-desc="<?= esc($rItem['description'] ?? '') ?>"
                                            data-def="<?= (int) ($rItem['is_default_front'] ?? 0) ?>">
                                        ✎ Edit
                                    </button>
                                    <?php if (! empty($canDelete) && (int) ($rItem['is_system'] ?? 0) === 0): ?>
                                        <form action="<?= base_url('addons/rbac/delete-role') ?>" method="post" class="rbac-inline-form js-rbac-confirm-del">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $rItem['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if (! empty($canEdit) && $activeRole && $activeRole['role_slug'] !== 'super_admin'): ?>
            <section class="console-card">
                <div class="card-head">
                    <div>
                        <span class="mono-label text-cyan">1-CLICK CLONE WORKSPACE</span>
                        <h3>Copy Into <span class="text-emerald"><?= esc($activeRole['role_name']) ?></span></h3>
                    </div>
                </div>
                <form action="<?= base_url('addons/rbac/clone-role-config') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_role_id" value="<?= (int) $activeRole['id'] ?>">
                    <input type="hidden" name="target_role_slug" value="<?= esc($activeRole['role_slug']) ?>">
                    <div class="field-group">
                        <label class="field-label"><span>Copy From Role *</span></label>
                        <select name="source_role_slug" class="field-select" required>
                            <?php foreach (($roles ?? []) as $rOpt): ?>
                                <?php if ($rOpt['role_slug'] === $activeRole['role_slug']) continue; ?>
                                <option value="<?= esc($rOpt['role_slug']) ?>"><?= esc($rOpt['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary btn-full">⎘ Clone Permissions &amp; Menu Names</button>
                </form>
            </section>
            <?php endif; ?>
        </div>

        <!-- Right Column: iOS-Style Switch-Toggle Permission Matrix -->
        <section class="console-card">
            <?php if ($activeRole): ?>
                <?php
                    $activePerms = $activeRole['permissions_array'] ?? [];
                    $isSuper     = ($activeRole['role_slug'] === 'super_admin' || in_array('*', $activePerms, true));
                ?>
                <div class="card-head rbac-matrix-head">
                    <div>
                        <span class="mono-label text-emerald">SWITCH-TOGGLE PERMISSION MATRIX</span>
                        <h3>Capabilities for: <span class="text-emerald"><?= esc($activeRole['role_name']) ?></span></h3>
                    </div>
                    <div class="action-btn-group">
                        <input type="search" id="js-rbac-perm-search" class="field-input" placeholder="Search capabilities..." style="width:200px; padding:0.35rem 0.65rem;">
                        <button type="button" class="btn-secondary btn-sm" id="js-rbac-check-all">Enable All</button>
                        <button type="button" class="btn-secondary btn-sm" id="js-rbac-uncheck-all">Disable All</button>
                    </div>
                </div>

                <form action="<?= base_url('addons/rbac/save-matrix') ?>" method="post" id="rbac-matrix-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="role_id" value="<?= (int) $activeRole['id'] ?>">
                    <input type="hidden" name="role_slug" value="<?= esc($activeRole['role_slug']) ?>">

                    <?php if ($activeRole['role_slug'] === 'super_admin'): ?>
                        <div class="alert-banner alert-success rbac-alert">
                            <strong>Super Administrator</strong> has unrestricted root access to all current and future modules automatically.
                        </div>
                    <?php endif; ?>

                    <div class="rbac-groups-container">
                        <?php foreach (($groupedFeatures ?? []) as $groupTitle => $features): ?>
                            <div class="panel-card rbac-group-card">
                                <div class="rbac-group-header">
                                    <span class="mono-label text-emerald"><?= esc($groupTitle) ?> (<?= count($features) ?>)</span>
                                    <button type="button" class="btn-action js-toggle-group">Toggle Module</button>
                                </div>
                                <div class="rbac-perm-grid">
                                    <?php foreach ($features as $feat): ?>
                                        <?php
                                            $pKey    = (string) $feat['perm_key'];
                                            $checked = $isSuper || in_array($pKey, $activePerms, true);
                                        ?>
                                        <div class="rbac-perm-item js-rbac-perm-card" data-search="<?= esc(strtolower($feat['perm_label'] . ' ' . $pKey)) ?>">
                                            <label class="rbac-switch-row">
                                                <div class="rbac-switch-meta">
                                                    <div class="identity-name"><?= esc($feat['perm_label']) ?></div>
                                                    <div class="mono-cell rbac-key-code"><?= esc($pKey) ?></div>
                                                </div>
                                                <span class="rbac-ios-switch">
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="<?= esc($pKey) ?>"
                                                           class="js-rbac-perm-cb"
                                                           <?= $checked ? 'checked' : '' ?>
                                                           <?= $activeRole['role_slug'] === 'super_admin' ? 'disabled' : '' ?>>
                                                    <span class="rbac-ios-slider"></span>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (! empty($canEdit) && $activeRole['role_slug'] !== 'super_admin'): ?>
                        <div class="action-btn-group rbac-matrix-footer">
                            <button type="submit" class="btn-primary">💾 Save Permissions for <?= esc($activeRole['role_name']) ?></button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
