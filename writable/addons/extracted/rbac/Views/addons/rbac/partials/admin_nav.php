<?php
    $currSub    = $activeSubTab ?? 'overview';
    $selSlug    = $selectedRoleSlug ?? 'student';
    $actAddons  = (int) ($activeAddonsCnt ?? 9);
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/rbac/rbac.css?v=7.5') ?>">
<script src="<?= base_url('assets/addons/rbac/rbac.js?v=7.5') ?>" defer></script>

<?php if (session()->getFlashdata('success')): ?>
    <div class="rbac-alert rbac-alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="rbac-alert rbac-alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="rbac-top-console-bar">
    <div class="rbac-nav-tabs">
        <a href="<?= base_url('addons/rbac?role=' . urlencode($selSlug)) ?>"
           class="rbac-nav-tab <?= $currSub === 'overview' ? 'is-active' : '' ?>">
            📊 1. Roles &amp; Live Sidebar Preview
        </a>
        <a href="<?= base_url('addons/rbac/matrix?role=' . urlencode($selSlug)) ?>"
           class="rbac-nav-tab <?= $currSub === 'matrix' ? 'is-active' : '' ?>">
            🛡 2. Permission Switch Matrix
        </a>
        <a href="<?= base_url('addons/rbac/workspace?role=' . urlencode($selSlug)) ?>"
           class="rbac-nav-tab <?= $currSub === 'workspace' ? 'is-active' : '' ?>">
            🎨 3. Menu Renamer &amp; Widgets
        </a>
    </div>

    <div class="rbac-action-bar">
        <span class="rbac-badge rbac-badge-info">❖ Active Add-Ons: <strong><?= $actAddons ?></strong></span>
        <?php if (! empty($canEdit)): ?>
            <form action="<?= base_url('addons/rbac/sync-addons') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="rbac-btn rbac-btn-outline rbac-btn-sm" title="Scan active/deactivated add-ons and rebuild 0ms cache">
                    ↻ Sync Active Add-Ons
                </button>
            </form>
            <form action="<?= base_url('addons/rbac/apply-presets') ?>" method="post" onsubmit="return confirm('Auto-configure role-scoped permissions and friendly menu names for all active add-ons?');">
                <?= csrf_field() ?>
                <button type="submit" class="rbac-btn rbac-btn-emerald rbac-btn-sm">
                    ⚡ Auto-Configure Role Presets
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Role Switcher Strip -->
<div class="rbac-role-strip">
    <span class="mono-label muted-cell" style="margin-right:0.35rem;">SELECT ROLE TO CONFIGURE:</span>
    <?php
        $baseRoute = 'addons/rbac';
        if ($currSub === 'matrix') {
            $baseRoute = 'addons/rbac/matrix';
        } elseif ($currSub === 'workspace') {
            $baseRoute = 'addons/rbac/workspace';
        }
    ?>
    <?php foreach (($roles ?? []) as $rItem): ?>
        <?php $isAct = ($rItem['role_slug'] === $selSlug); ?>
        <a href="<?= base_url($baseRoute . '?role=' . urlencode($rItem['role_slug'])) ?>"
           class="rbac-role-pill <?= $isAct ? 'is-active' : '' ?>">
            <span><?= esc($rItem['role_name']) ?></span>
            <span class="rbac-pill-count"><?= (int) ($rItem['users_count'] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>
