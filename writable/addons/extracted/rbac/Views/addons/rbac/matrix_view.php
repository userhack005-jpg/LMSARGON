<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- 1. Top Compact Role Management & Clone Strip -->
    <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
    <section class="console-card" style="margin-bottom: 1.35rem;">
        <div class="rbac-flex-between">
            <div>
                <span class="rbac-badge rbac-badge-cyan">⚙ ROLE BUILDER &amp; WORKSPACE CLONER</span>
                <h4 style="margin:0.3rem 0 0;">Create Custom Role, Edit Role Details, or Clone Permissions</h4>
            </div>
            <div class="rbac-action-bar">
                <button type="button" class="rbac-btn rbac-btn-outline rbac-btn-sm" id="js-rbac-toggle-builder">
                    ➕ Create / Edit Custom Role
                </button>
            </div>
        </div>

        <!-- Collapsible Role Builder Form -->
        <div id="js-rbac-builder-drawer" class="is-hidden" style="margin-top:1rem; padding-top:1rem; border-top:1px solid var(--rbac-border);">
            <form action="<?= base_url('addons/rbac/save-role') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="role_id" value="0">
                <div class="rbac-builder-grid">
                    <div class="field-group">
                        <label class="field-label"><span>Role Name *</span></label>
                        <input type="text" name="role_name" id="role_name" class="field-input" placeholder="e.g. Academic Coordinator" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Role Key (Slug)</span></label>
                        <input type="text" name="role_slug" id="role_slug" class="field-input mono-input" placeholder="e.g. coordinator">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Portal Type</span></label>
                        <select name="portal_type" id="role_portal" class="field-select">
                            <option value="backend">Staff / Backend Panel</option>
                            <option value="frontend">Learner / Family Portal</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Description</span></label>
                        <input type="text" name="description" id="role_desc" class="field-input" placeholder="Short role summary...">
                    </div>
                    <div class="field-group">
                        <button type="submit" class="rbac-btn rbac-btn-emerald" style="width:100%;">💾 Save Role</button>
                    </div>
                </div>
            </form>

            <?php if ($activeRole && $activeRole['role_slug'] !== 'super_admin'): ?>
                <div class="rbac-flex-between" style="margin-top:1rem; padding-top:0.85rem; border-top:1px dashed var(--rbac-border);">
                    <form action="<?= base_url('addons/rbac/clone-role-config') ?>" method="post" class="rbac-action-bar">
                        <?= csrf_field() ?>
                        <input type="hidden" name="target_role_id" value="<?= (int) $activeRole['id'] ?>">
                        <input type="hidden" name="target_role_slug" value="<?= esc($activeRole['role_slug']) ?>">
                        <span class="mono-cell muted-cell">Clone into <strong><?= esc($activeRole['role_name']) ?></strong> from:</span>
                        <select name="source_role_slug" class="field-select" style="width:auto; min-width:180px; height:32px; padding:0 0.6rem; font-size:0.78rem;">
                            <?php foreach (($roles ?? []) as $rOpt): ?>
                                <?php if ($rOpt['role_slug'] === $activeRole['role_slug']) continue; ?>
                                <option value="<?= esc($rOpt['role_slug']) ?>"><?= esc($rOpt['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="rbac-btn rbac-btn-cyan rbac-btn-sm">⎘ Clone Permissions &amp; Menus</button>
                    </form>

                    <?php if (! empty($canDelete) && (int) ($activeRole['is_system'] ?? 0) === 0): ?>
                        <form action="<?= base_url('addons/rbac/delete-role') ?>" method="post" onsubmit="return confirm('Delete this custom role?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $activeRole['id'] ?>">
                            <button type="submit" class="rbac-btn rbac-btn-rose rbac-btn-sm">✕ Delete Custom Role</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- 2. Full-Width Switch-Toggle Permission Matrix -->
    <?php if ($activeRole): ?>
        <?php
            $activePerms = $activeRole['permissions_array'] ?? [];
            $isSuper     = ($activeRole['role_slug'] === 'super_admin' || in_array('*', $activePerms, true));
        ?>
        <section class="console-card">
            <form action="<?= base_url('addons/rbac/save-matrix') ?>" method="post" id="rbac-matrix-form">
                <?= csrf_field() ?>
                <input type="hidden" name="role_id" value="<?= (int) $activeRole['id'] ?>">
                <input type="hidden" name="role_slug" value="<?= esc($activeRole['role_slug']) ?>">

                <div class="card-head rbac-flex-between" style="margin-bottom: 0.85rem;">
                    <div>
                        <span class="rbac-badge rbac-badge-emerald">🛡 PERMISSION SWITCH MATRIX</span>
                        <h3 style="margin:0.3rem 0 0;">
                            Configuring Capabilities for: <span class="text-emerald"><?= esc($activeRole['role_name']) ?></span>
                        </h3>
                    </div>
                    <div class="rbac-action-bar">
                        <?php if (! $isSuper): ?>
                            <button type="button" class="rbac-btn rbac-btn-outline rbac-btn-sm" id="js-rbac-check-all">✓ Enable All Visible</button>
                            <button type="button" class="rbac-btn rbac-btn-outline rbac-btn-sm" id="js-rbac-uncheck-all">✕ Disable All Visible</button>
                            <?php if (! empty($canEdit)): ?>
                                <button type="submit" class="rbac-btn rbac-btn-emerald">💾 Save Permissions</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 0ms Search & State Filter Bar -->
                <div class="rbac-toolbar-strip" style="margin-bottom: 1.15rem;">
                    <div class="rbac-toolbar-left">
                        <input type="search" id="js-rbac-perm-search" class="rbac-search-input" placeholder="🔍 Instant search permission name, module, or key (e.g. finance, exam, demo)...">
                    </div>
                    <div class="rbac-filter-pills" id="js-rbac-perm-filter-tabs">
                        <button type="button" class="rbac-filter-tab is-active" data-state="all">All Capabilities</button>
                        <button type="button" class="rbac-filter-tab" data-state="enabled">✓ Enabled Only</button>
                        <button type="button" class="rbac-filter-tab" data-state="disabled">○ Disabled Only</button>
                    </div>
                </div>

                <?php if ($isSuper): ?>
                    <div class="rbac-alert rbac-alert-success">
                        <strong>Super Administrator</strong> automatically has unrestricted root access (<code>*</code>) to all current and future add-ons.
                    </div>
                <?php endif; ?>

                <div class="rbac-groups-stack">
                    <?php foreach (($groupedFeatures ?? []) as $groupTitle => $features): ?>
                        <div class="rbac-module-box js-rbac-module-group">
                            <div class="rbac-module-box-head">
                                <div>
                                    <strong class="text-emerald" style="font-size:0.92rem;"><?= esc($groupTitle) ?></strong>
                                    <span class="rbac-badge rbac-badge-info" style="margin-left:0.4rem;"><?= count($features) ?> keys</span>
                                </div>
                                <?php if (! $isSuper): ?>
                                    <button type="button" class="rbac-btn rbac-btn-outline rbac-btn-sm js-toggle-group">Toggle Module</button>
                                <?php endif; ?>
                            </div>
                            <div class="rbac-perm-cards-grid">
                                <?php foreach ($features as $feat): ?>
                                    <?php
                                        $pKey    = (string) $feat['perm_key'];
                                        $checked = $isSuper || in_array($pKey, $activePerms, true);
                                    ?>
                                    <label class="rbac-perm-switch-card js-rbac-perm-card"
                                           data-search="<?= esc(strtolower($feat['perm_label'] . ' ' . $pKey . ' ' . $groupTitle)) ?>">
                                        <div class="rbac-perm-card-text">
                                            <div class="rbac-perm-title"><?= esc($feat['perm_label']) ?></div>
                                            <code class="rbac-perm-code"><?= esc($pKey) ?></code>
                                        </div>
                                        <span class="rbac-ios-switch">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="<?= esc($pKey) ?>"
                                                   class="js-rbac-perm-cb"
                                                   <?= $checked ? 'checked' : '' ?>
                                                   <?= $isSuper ? 'disabled' : '' ?>>
                                            <span class="rbac-ios-slider"></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (! empty($canEdit) && ! $isSuper): ?>
                    <div class="rbac-sticky-save-bar">
                        <span class="mono-cell muted-cell">Changes rebuild the 0ms static cache immediately upon saving.</span>
                        <button type="submit" class="rbac-btn rbac-btn-emerald rbac-btn-lg">
                            💾 Save Permissions for <?= esc($activeRole['role_name']) ?>
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
