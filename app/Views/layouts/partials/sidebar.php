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

    <!-- Sidebar Bottom Status -->
    <div class="sidebar-footer">
        <div class="lock-indicator">
            <span class="pulse-dot"></span>
            <span class="mono-label">INSTALLER: LOCKED</span>
        </div>
    </div>
</aside>