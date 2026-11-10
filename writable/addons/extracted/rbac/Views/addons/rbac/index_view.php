<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- 1. Role Cards Overview Grid (Full Width, Clean Footer Actions) -->
    <section class="rbac-role-cards-grid" style="margin-bottom: 1.5rem;">
        <?php foreach (($roles ?? []) as $rRow): ?>
            <?php
                $rSlug      = (string) $rRow['role_slug'];
                $isSel      = ($rSlug === ($selectedRoleSlug ?? ''));
                $permsArr   = $rRow['permissions_array'] ?? [];
                $wsArr      = $rRow['workspace_array'] ?? [];
                $switcherOn = isset($wsArr['show_child_switcher']) ? ((int) $wsArr['show_child_switcher'] === 1) : ($rSlug === 'parent');
                $navCount   = count($rRow['compiled_sidebar'] ?? []);
                $isBackend  = (($rRow['portal_type'] ?? '') === 'backend');
            ?>
            <article class="console-card rbac-role-card <?= $isSel ? 'is-selected' : '' ?>">
                <div>
                    <div class="rbac-flex-between" style="margin-bottom:0.5rem;">
                        <span class="rbac-badge <?= $isBackend ? 'rbac-badge-cyan' : 'rbac-badge-emerald' ?>">
                            <?= $isBackend ? '🛡 STAFF / BACKEND' : '🎓 FAMILY / FRONTEND' ?>
                        </span>
                        <span class="rbac-badge rbac-badge-info">👥 <?= (int) ($rRow['users_count'] ?? 0) ?> Users</span>
                    </div>
                    <h3 style="margin:0.2rem 0 0.3rem; font-size:1.12rem;"><?= esc($rRow['role_name']) ?></h3>
                    <p class="muted-cell" style="margin:0 0 0.9rem; font-size:0.82rem; line-height:1.45;"><?= esc($rRow['description'] ?? '') ?></p>

                    <div class="rbac-spec-strip">
                        <div>
                            <small>PERMISSIONS</small>
                            <strong class="text-emerald"><?= ($rSlug === 'super_admin' || in_array('*', $permsArr, true)) ? 'ALL (*)' : count($permsArr) ?></strong>
                        </div>
                        <div>
                            <small>ACTIVE MENUS</small>
                            <strong class="text-cyan"><?= $navCount ?></strong>
                        </div>
                        <div>
                            <small>CHILD SWITCH</small>
                            <strong><?= $switcherOn ? 'ON' : 'OFF' ?></strong>
                        </div>
                    </div>
                </div>

                <div class="rbac-card-actions">
                    <a href="<?= base_url('addons/rbac?role=' . urlencode($rSlug)) ?>#js-sidebar-simulator" class="rbac-btn <?= $isSel ? 'rbac-btn-emerald' : 'rbac-btn-outline' ?> rbac-btn-sm">
                        👁 Preview
                    </a>
                    <a href="<?= base_url('addons/rbac/matrix?role=' . urlencode($rSlug)) ?>" class="rbac-btn rbac-btn-cyan rbac-btn-sm">
                        🛡 Permissions
                    </a>
                    <a href="<?= base_url('addons/rbac/workspace?role=' . urlencode($rSlug)) ?>" class="rbac-btn rbac-btn-outline rbac-btn-sm">
                        🎨 Rename
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- 2. Full-Width Live Sidebar Simulator for Selected Role -->
    <section class="console-card" id="js-sidebar-simulator">
        <div class="card-head rbac-flex-between" style="margin-bottom: 1rem;">
            <div>
                <span class="rbac-badge rbac-badge-emerald">⚡ LIVE 0MS SIDEBAR SIMULATOR</span>
                <h3 style="margin:0.35rem 0 0;">
                    Exact Sidebar Navigation Seen by: <span class="text-emerald"><?= esc($activeRole['role_name'] ?? '') ?></span>
                </h3>
                <p class="field-hint" style="margin:0.15rem 0 0;">
                    Only active add-ons and permitted sub-menus appear below. If you deactivate an add-on, its menu automatically disappears from this sidebar.
                </p>
            </div>
            <div class="rbac-action-bar">
                <a href="<?= base_url('addons/rbac/matrix?role=' . urlencode($selectedRoleSlug ?? 'student')) ?>" class="rbac-btn rbac-btn-cyan rbac-btn-sm">
                    🛡 Edit Permissions for <?= esc($activeRole['role_name'] ?? '') ?>
                </a>
                <a href="<?= base_url('addons/rbac/workspace?role=' . urlencode($selectedRoleSlug ?? 'student')) ?>" class="rbac-btn rbac-btn-emerald rbac-btn-sm">
                    🎨 Rename / Hide Menus →
                </a>
            </div>
        </div>

        <?php $compNav = $activeRole['compiled_sidebar'] ?? []; ?>
        <?php if (empty($compNav)): ?>
            <div class="panel-card" style="text-align:center; padding:2rem;">
                <p class="muted-cell" style="margin:0;">No sidebar menus are enabled for this role yet. Click <strong>"⚡ Auto-Configure Role Presets"</strong> at the top bar.</p>
            </div>
        <?php else: ?>
            <div class="rbac-sim-grid">
                <?php foreach ($compNav as $navKey => $navItem): ?>
                    <div class="rbac-sim-module-card">
                        <div class="rbac-sim-module-head">
                            <div style="display:flex; align-items:center; gap:0.55rem; min-width:0;">
                                <span class="rbac-sim-icon"><?= esc($navItem['icon'] ?? '⚡') ?></span>
                                <div style="min-width:0;">
                                    <div class="identity-name" style="margin:0;"><?= esc($navItem['display_label'] ?? '') ?></div>
                                    <div class="mono-cell text-emerald" style="font-size:0.72rem;">/<?= esc($navItem['route'] ?? '') ?></div>
                                </div>
                            </div>
                            <span class="rbac-badge rbac-badge-info"><?= count($navItem['sub_views'] ?? []) ?> Sub-Tabs</span>
                        </div>

                        <?php if (! empty($navItem['sub_views'])): ?>
                            <div class="rbac-sim-sublist">
                                <?php foreach ($navItem['sub_views'] as $sv): ?>
                                    <div class="rbac-sim-subitem">
                                        <span>↳ <?= esc($sv['icon'] ?? '•') ?> <strong><?= esc($sv['display_label'] ?? $sv['label']) ?></strong></span>
                                        <code class="rbac-route-tag">/<?= esc($sv['route'] ?? '') ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="muted-cell" style="font-size:0.76rem; padding-top:0.35rem;">Direct single-page module</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?= $this->endSection() ?>
