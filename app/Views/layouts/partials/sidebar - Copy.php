<?php
    $currentMenu = $activeMenu ?? 'dashboard';
?>
<aside class="app-sidebar" id="app-sidebar">
    <!-- Brand Identity Top -->
    <div class="sidebar-brand">
        <?php if (! empty($settings['site_logo'])): ?>
            <img src="<?= base_url(esc($settings['site_logo'])) ?>" alt="Logo" class="brand-logo-sm">
        <?php else: ?>
            <div class="brand-monogram-sm">
                <?= esc(strtoupper(substr($settings['site_title'] ?? 'S', 0, 2))) ?>
            </div>
        <?php endif; ?>
        <div class="sidebar-brand-meta">
            <h2 class="sidebar-title"><?= esc($settings['site_title'] ?? 'System') ?></h2>
            <span class="mono-label">CORE ARCHITECTURE</span>
        </div>
    </div>

    <!-- Navigation Menu Links -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">MAIN WORKSPACE</div>

        <a href="<?= base_url('dashboard') ?>" class="sidebar-link <?= $currentMenu === 'dashboard' ? 'is-active' : '' ?>">
            <span class="nav-icon">⊞</span>
            <span class="nav-text">Dashboard Overview</span>
        </a>

        <div class="nav-section-label">ACCESS & RBAC MODULES</div>

        <a href="<?= base_url('users') ?>" class="sidebar-link <?= $currentMenu === 'users' ? 'is-active' : '' ?>">
            <span class="nav-icon">◈</span>
            <span class="nav-text">User & Role Manager</span>
            <span class="nav-pill">RBAC</span>
        </a>
    </nav>


<?php if (in_array(($user['role_slug'] ?? ''), ['super_admin', 'admin'], true)): ?>
        <div class="nav-section-label">CONFIGURATION & SEO</div>

        <a href="<?= base_url('settings') ?>" class="sidebar-link <?= $currentMenu === 'settings' ? 'is-active' : '' ?>">
            <span class="nav-icon">⚙</span>
            <span class="nav-text">Site & SEO Settings</span>
            <span class="nav-pill">GLOBAL</span>
        </a>
        <?php endif; ?>
		
<?php if (in_array(($user['role_slug'] ?? ''), ['super_admin', 'admin'], true)): ?>
        <a href="<?= base_url('addons') ?>" class="sidebar-link <?= $currentMenu === 'addons' ? 'is-active' : '' ?>">
            <span class="nav-icon">❖</span>
            <span class="nav-text">Add-On Manager</span>
            <span class="nav-pill">ZIP</span>
        </a>
       <?php
	    // Dynamically list all Active Add-Ons that have a Backend Admin Route

            $activeAddonsList = (new \App\Models\Addons\AddonModel())->getActiveAddons();
        ?>
        <?php if (! empty($activeAddonsList)): ?>
            <div class="nav-section-label">INSTALLED ADD-ON MODULES</div>
            <?php foreach ($activeAddonsList as $addonItem): ?>
                <?php if (! empty($addonItem['admin_route'])): ?>
                <a href="<?= base_url(esc($addonItem['admin_route'])) ?>" class="sidebar-link <?= $currentMenu === ('addon_' . $addonItem['slug']) ? 'is-active' : '' ?>">
                    <span class="nav-icon">⚡</span>
                    <span class="nav-text"><?= esc($addonItem['name']) ?></span>
                </a>
                <?php if ($addonItem['slug'] === 'page_builder'): ?>
                    <a href="<?= base_url('addons/page-builder/footer') ?>" class="sidebar-link <?= $currentMenu === 'addon_pb_footer' ? 'is-active' : '' ?>" style="padding-left: 2.2rem; font-size: 0.82rem;">
                        <span class="nav-icon">⊞</span>
                        <span class="nav-text">Footer Architect</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
		
		
		
        <?php endif; ?>
		
		
		
    <!-- Sidebar Bottom Status -->
    <div class="sidebar-footer">
        <div class="lock-indicator">
            <span class="pulse-dot"></span>
            <span class="mono-label">INSTALLER: LOCKED</span>
        </div>
    </div>
</aside>

