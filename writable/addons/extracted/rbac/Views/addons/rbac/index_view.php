<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="rbac-main-grid rbac-section-gap">
        <!-- Left: Interactive Live Sidebar Simulator for Selected Role -->
        <section class="console-card">
            <div class="card-head rbac-matrix-head">
                <div>
                    <span class="role-badge text-emerald">LIVE SIDEBAR SIMULATOR</span>
                    <h3 style="margin-top:0.3rem;">Sidebar Seen by: <span class="text-emerald"><?= esc($activeRole['role_name'] ?? '') ?></span></h3>
                </div>
                <a href="<?= base_url('addons/rbac/workspace?role=' . urlencode($selectedRoleSlug ?? 'student')) ?>" class="btn-secondary btn-sm">✎ Rename Menus →</a>
            </div>
            <p class="field-hint" style="margin-bottom: 1rem;">
                This is the exact sidebar navigation pre-compiled in 0ms cache for <strong><?= esc($activeRole['role_name'] ?? '') ?></strong>. Click any role pill above to preview how their sidebar adapts.
            </p>

            <?php $compNav = $activeRole['compiled_sidebar'] ?? []; ?>
            <?php if (empty($compNav)): ?>
                <div class="panel-card" style="text-align:center;">
                    <p class="muted-cell">No sidebar items visible for this role yet. Click "⚡ Apply Friendly Role Presets" above.</p>
                </div>
            <?php else: ?>
                <div class="rbac-preview-sidebar">
                    <?php foreach ($compNav as $navKey => $navItem): ?>
                        <div class="rbac-preview-item">
                            <div class="rbac-preview-main">
                                <span><?= esc($navItem['icon'] ?? '⚡') ?> <strong><?= esc($navItem['display_label'] ?? '') ?></strong></span>
                                <span class="mono-cell muted-cell">/<?= esc($navItem['route'] ?? '') ?></span>
                            </div>
                            <?php if (! empty($navItem['sub_views'])): ?>
                                <div class="rbac-preview-subs">
                                    <?php foreach ($navItem['sub_views'] as $sv): ?>
                                        <div class="rbac-preview-sub-row">
                                            <span>↳ <?= esc($sv['icon'] ?? '•') ?> <?= esc($sv['display_label'] ?? $sv['label']) ?></span>
                                            <span class="mono-cell text-cyan">/<?= esc($sv['route'] ?? '') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Right: Visual Role Cards Grid -->
        <div>
            <div class="rbac-role-cards-grid">
                <?php foreach (($roles ?? []) as $rRow): ?>
                    <?php
                        $rSlug      = (string) $rRow['role_slug'];
                        $permsArr   = $rRow['permissions_array'] ?? [];
                        $wsArr      = $rRow['workspace_array'] ?? [];
                        $switcherOn = isset($wsArr['show_child_switcher']) ? ((int) $wsArr['show_child_switcher'] === 1) : ($rSlug === 'parent');
                        $navCount   = count($rRow['compiled_sidebar'] ?? []);
                    ?>
                    <article class="rbac-visual-role-card <?= $rSlug === ($selectedRoleSlug ?? '') ? 'is-selected-card' : '' ?>">
                        <div>
                            <div class="rbac-matrix-head" style="margin-bottom:0.45rem;">
                                <span class="role-badge <?= ($rRow['portal_type'] ?? '') === 'backend' ? 'text-cyan' : 'text-emerald' ?>">
                                    <?= strtoupper(esc($rRow['portal_type'] ?? 'PORTAL')) ?>
                                </span>
                                <span class="mono-cell">👥 <?= (int) ($rRow['users_count'] ?? 0) ?> Users</span>
                            </div>
                            <h3 style="margin:0.2rem 0;"><?= esc($rRow['role_name']) ?></h3>
                            <p class="muted-cell" style="font-size:0.84rem; margin:0 0 0.85rem;"><?= esc($rRow['description'] ?? '') ?></p>

                            <div class="rbac-rc-specs">
                                <div>
                                    <span class="mono-label">PERMISSIONS</span>
                                    <strong class="text-emerald">
                                        <?= ($rSlug === 'super_admin' || in_array('*', $permsArr, true)) ? 'All (*)' : count($permsArr) ?>
                                    </strong>
                                </div>
                                <div>
                                    <span class="mono-label">SIDEBAR MENUS</span>
                                    <strong class="text-cyan"><?= $navCount ?> Menus</strong>
                                </div>
                                <div>
                                    <span class="mono-label">CHILD SWITCHER</span>
                                    <strong><?= $switcherOn ? '● ON' : '○ OFF' ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="action-btn-group" style="margin-top:1rem; padding-top:0.85rem; border-top:1px solid var(--border-subtle);">
                            <a href="<?= base_url('addons/rbac?role=' . urlencode($rSlug)) ?>" class="btn-action" style="text-decoration:none;">👁 Preview Sidebar</a>
                            <a href="<?= base_url('addons/rbac/matrix?role=' . urlencode($rSlug)) ?>" class="btn-secondary btn-sm" style="text-decoration:none;">🛡 Permissions</a>
                            <a href="<?= base_url('addons/rbac/workspace?role=' . urlencode($rSlug)) ?>" class="btn-primary btn-sm" style="text-decoration:none;">🎨 Rename Menus</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
