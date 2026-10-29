<?php
    $currSub = $activeSubTab ?? 'overview';
    $selSlug = $selectedRoleSlug ?? 'student';
    $totalPerms = 0;
    foreach (($groupedFeatures ?? []) as $gList) {
        $totalPerms += count($gList);
    }
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="rbac-compact-bar">
    <div class="rbac-stat-pills">
        <span class="role-badge text-emerald">🛡 Roles: <strong><?= count($roles ?? []) ?></strong></span>
        <span class="role-badge text-cyan">🔑 Active Permissions: <strong><?= $totalPerms ?></strong></span>
        <span class="role-badge">⚡ 0ms Static Cache Active</span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <a href="<?= base_url('addons/rbac?role=' . urlencode($selSlug)) ?>"
           class="settings-tab <?= $currSub === 'overview' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>📊 Roles &amp; Live Sidebar Preview</span>
        </a>
        <a href="<?= base_url('addons/rbac/matrix?role=' . urlencode($selSlug)) ?>"
           class="settings-tab <?= $currSub === 'matrix' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🛡 Permission Switch Matrix</span>
        </a>
        <a href="<?= base_url('addons/rbac/workspace?role=' . urlencode($selSlug)) ?>"
           class="settings-tab <?= $currSub === 'workspace' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🎨 Menu Renamer &amp; Widgets</span>
        </a>
    </div>

    <?php if (! empty($canEdit)): ?>
        <div class="action-btn-group">
            <form action="<?= base_url('addons/rbac/sync-addons') ?>" method="post" class="rbac-inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn-secondary btn-sm" title="Scan active add-ons and rebuild 0ms cache">↻ Sync Cache</button>
            </form>
            <form action="<?= base_url('addons/rbac/apply-presets') ?>" method="post" class="rbac-inline-form" onsubmit="return confirm('Apply friendly menu names, recommended permissions & dashboard widgets for Student, Parent, Teacher & Admin?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn-primary btn-sm">⚡ Apply Friendly Role Presets</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Role Switcher Pills -->
<div class="rbac-role-tabs">
    <?php
        $baseRoute = 'addons/rbac';
        if ($currSub === 'matrix') $baseRoute = 'addons/rbac/matrix';
        elseif ($currSub === 'workspace') $baseRoute = 'addons/rbac/workspace';
    ?>
    <?php foreach (($roles ?? []) as $rItem): ?>
        <?php $isAct = ($rItem['role_slug'] === $selSlug); ?>
        <a href="<?= base_url($baseRoute . '?role=' . urlencode($rItem['role_slug'])) ?>"
           class="rbac-role-pill <?= $isAct ? 'is-active' : '' ?>">
            <strong><?= esc($rItem['role_name']) ?></strong>
            <span class="role-badge"><?= (int) ($rItem['users_count'] ?? 0) ?> Users</span>
        </a>
    <?php endforeach; ?>
</div>
