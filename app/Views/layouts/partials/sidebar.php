<?php
    $currentMenu   = $activeMenu ?? 'dashboard';
    $effectiveRole = function_exists('cms_get_effective_role')
        ? cms_get_effective_role()
        : strtolower(trim((string) (session()->get('role_slug') ?: 'student')));

    $cacheFile = WRITEPATH . 'addons/rbac_permissions_cache.json';
    if (! is_file($cacheFile)) {
        $rbacModelFile = WRITEPATH . 'addons/extracted/rbac/Models/Addons/Rbac/RbacModel.php';
        if (is_file($rbacModelFile)) {
            require_once $rbacModelFile;
            if (class_exists('\App\Models\Addons\Rbac\RbacModel')) {
                (new \App\Models\Addons\Rbac\RbacModel())->rebuildStaticCache();
            }
        }
    }

    $roleWorkspace   = function_exists('cms_get_role_workspace') ? cms_get_role_workspace($effectiveRole) : [];
    $compiledSidebar = $roleWorkspace['compiled_sidebar'] ?? [];

    $groupedSections = [];
    foreach ($compiledSidebar as $itemKey => $navItem) {
        $secName = $navItem['section'] ?? 'WORKSPACE';
        $groupedSections[$secName][$itemKey] = $navItem;
    }

    $currentUriPath = trim((string) service('uri')->getPath(), '/');
    if (strpos($currentUriPath, 'index.php/') === 0) {
        $currentUriPath = trim(substr($currentUriPath, strlen('index.php/')), '/');
    }
    $currentQuery = $_SERVER['QUERY_STRING'] ?? '';
    $fullRelUri   = $currentUriPath . ($currentQuery !== '' ? ('?' . $currentQuery) : '');
?>
<style>
    /* Smooth Modern SaaS Sidebar Accordion & Mobile Backdrop */
    .cms-nav-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 2px;
    }
    .cms-nav-caret {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 5px;
        font-size: 0.72rem;
        color: var(--text-muted);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.15s ease, color 0.15s ease;
        margin-left: auto;
        flex-shrink: 0;
    }
    .cms-nav-caret:hover {
        background: rgba(16, 185, 129, 0.14);
        color: var(--accent-emerald);
    }
    .cms-nav-group.is-expanded .cms-nav-caret {
        transform: rotate(180deg);
        color: var(--accent-emerald);
    }
    .cms-nav-sublist {
        display: none;
        flex-direction: column;
        gap: 2px;
        margin: 4px 0 6px 1.35rem;
        padding-left: 0.65rem;
        border-left: 1.5px solid var(--border-subtle);
    }
    .cms-nav-group.is-expanded .cms-nav-sublist {
        display: flex;
    }
    .cms-sub-link {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.46rem 0.7rem;
        border-radius: 7px;
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.81rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }
    .cms-sub-link:hover {
        background: var(--bg-elevated);
        color: var(--text-primary);
    }
    .cms-sub-link.is-sub-active {
        background: var(--accent-emerald-glow);
        color: var(--accent-emerald);
        font-weight: 600;
    }
    .cms-sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(5, 6, 10, 0.55);
        backdrop-filter: blur(3px);
        z-index: 15;
    }
    @media (max-width: 860px) {
        .app-sidebar.is-open + .cms-sidebar-backdrop {
            display: block;
        }
    }
</style>

<aside class="app-sidebar" id="app-sidebar">
    <!-- Brand Identity Top -->
    <div class="sidebar-brand">
        <?php if (! empty($settings['site_logo'])): ?>
            <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="Logo" class="brand-logo-sm" loading="lazy" decoding="async">
        <?php else: ?>
            <div class="brand-monogram-sm">
                <?= esc(strtoupper(substr($settings['site_title'] ?? 'S', 0, 2))) ?>
            </div>
        <?php endif; ?>
        <div class="sidebar-brand-meta">
            <h2 class="sidebar-title"><?= esc($settings['site_title'] ?? 'System') ?></h2>
            <span class="mono-label">ROLE: <?= esc(strtoupper($effectiveRole)) ?></span>
        </div>
    </div>

    <!-- 100% RBAC & Workspace-Compiled Navigation Menu -->
    <nav class="sidebar-nav">
        <?php if (empty($groupedSections)): ?>
            <div class="nav-section-label">WORKSPACE</div>
            <div class="muted-cell" style="padding: 0.75rem 0.85rem; font-size: 0.8rem;">
                No sidebar menus enabled for this role.
            </div>
        <?php else: ?>
            <?php foreach ($groupedSections as $sectionTitle => $items): ?>
                <div class="nav-section-label"><?= esc($sectionTitle) ?></div>

                <?php foreach ($items as $itemKey => $item): ?>
                    <?php
                        $slug      = $item['slug'] ?? '';
                        $itemRoute = ltrim((string) ($item['route'] ?? ''), '/');
                        $subViews  = $item['sub_views'] ?? [];
                        $hasSubs   = ! empty($subViews);

                        $isMainActive = ($currentMenu === $slug)
                            || ($currentMenu === ('addon_' . $slug))
                            || ($currentUriPath === $itemRoute)
                            || ($itemRoute !== 'dashboard' && $itemRoute !== '' && strpos($currentUriPath, explode('?', $itemRoute)[0]) === 0);
                    ?>
                    <div class="cms-nav-group <?= $isMainActive ? 'is-expanded' : '' ?>">
                        <a href="<?= base_url(esc($itemRoute)) ?>"
                           class="sidebar-link <?= $isMainActive ? 'is-active' : '' ?>">
                            <span class="nav-icon"><?= esc($item['icon'] ?? '⚡') ?></span>
                            <span class="nav-text"><?= esc($item['display_label'] ?? 'Module') ?></span>
                            <?php if ($hasSubs): ?>
                                <span class="cms-nav-caret js-sidebar-sub-toggle" role="button" tabindex="0" title="Expand / Collapse Sub-Menu">▾</span>
                            <?php endif; ?>
                        </a>

                        <?php if ($hasSubs): ?>
                            <div class="cms-nav-sublist">
                                <?php foreach ($subViews as $sv): ?>
                                    <?php
                                        $svRoute  = ltrim((string) ($sv['route'] ?? ''), '/');
                                        $isSubAct = ($fullRelUri === $svRoute) || ($currentUriPath === $svRoute);
                                    ?>
                                    <a href="<?= base_url(esc($svRoute)) ?>"
                                       class="cms-sub-link <?= $isSubAct ? 'is-sub-active' : '' ?>">
                                        <span><?= esc($sv['icon'] ?? '•') ?></span>
                                        <span><?= esc($sv['display_label'] ?? ($sv['label'] ?? 'Sub-View')) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Bottom Status -->
    <div class="sidebar-footer">
        <div class="lock-indicator">
            <span class="pulse-dot"></span>
            <span class="mono-label">RBAC WORKSPACE // SYNCED</span>
        </div>
    </div>
</aside>
<div class="cms-sidebar-backdrop" id="js-sidebar-backdrop"></div>

<script>
(function () {
    document.querySelectorAll('.js-sidebar-sub-toggle').forEach(function (caret) {
        if (caret.dataset.bound === '1') return;
        caret.dataset.bound = '1';
        caret.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var grp = caret.closest('.cms-nav-group');
            if (grp) grp.classList.toggle('is-expanded');
        });
    });

    var backdrop = document.getElementById('js-sidebar-backdrop');
    var sidebar  = document.getElementById('app-sidebar');
    if (backdrop && sidebar) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('is-open');
        });
    }
})();
</script>