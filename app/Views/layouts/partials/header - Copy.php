<?php
    $currentRoleSlug = (string) ($user['role_slug'] ?? 'student');
?>
<header class="app-header">
    <div class="header-left">
        <button type="button" id="sidebar-toggle-btn" class="btn-icon-mobile" aria-label="Toggle Navigation">
            ☰
        </button>
        <div>
            <span class="mono-label">ACTIVE CONTROL PLANE</span>
            <h1 class="header-page-title"><?= esc($pageTitle ?? 'Workspace') ?></h1>
        </div>
    </div>

    <div class="header-right">
        <div class="user-chip">
            <span class="role-badge role-<?= esc($currentRoleSlug) ?>"><?= esc(strtoupper($currentRoleSlug)) ?></span>
            <span class="user-name"><?= esc($user['user_name'] ?? 'Admin') ?></span>
        </div>
        <a href="<?= base_url('logout') ?>" class="btn-secondary btn-sm">Sign Out</a>
    </div>
</header>