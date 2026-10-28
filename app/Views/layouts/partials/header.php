<?php
    $baseRoleSlug    = strtolower((string) (session()->get('role_slug') ?: 'student'));
    if ((int) session()->get('role_id') === 1) {
        $baseRoleSlug = 'super_admin';
    }
    $effectiveRole   = function_exists('cms_get_effective_role') ? cms_get_effective_role() : $baseRoleSlug;
    $activeStudentId = (int) (session()->get('active_student_id') ?: 0);
    $portalViewMode  = (string) (session()->get('portal_view_mode') ?: 'default');
    $isInChildMode   = ($portalViewMode === 'student' && $activeStudentId > 0 && $baseRoleSlug !== 'super_admin');

    $baseWorkspace     = function_exists('cms_get_role_workspace') ? cms_get_role_workspace($baseRoleSlug) : [];
    $allowChildSwitch  = (int) ($baseWorkspace['workspace']['show_child_switcher'] ?? ($baseRoleSlug === 'parent' ? 1 : 0)) === 1;
?>
<style>
    /* Fix White-on-White Topbar Child Switcher in Light & Dark Modes */
    .cla-topbar-switcher {
        background: var(--bg-elevated, #171a26) !important;
        color: var(--text-primary, #f3f4f6) !important;
        border: 1px solid var(--accent-emerald, #10b981) !important;
        padding: 0.3rem 0.75rem !important;
        border-radius: 8px !important;
    }
    .cla-topbar-select {
        background-color: var(--bg-surface, #11131c) !important;
        color: var(--text-primary, #f3f4f6) !important;
        font-weight: 700 !important;
        padding: 0.2rem 0.45rem !important;
        border-radius: 6px !important;
    }
    .cla-topbar-select option {
        background-color: var(--bg-surface, #11131c) !important;
        color: var(--text-primary, #f3f4f6) !important;
    }
</style>

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
        <?php if ($isInChildMode): ?>
            <!-- Visible when Parent has switched into a Child's Student Profile -->
            <a href="<?= base_url('dashboard?exit_child_mode=1') ?>"
               class="btn-primary btn-sm"
               style="background:#10b981; color:#04130d; font-weight:700;">
                🏠 ← Back to Parent Profile (Child #<?= $activeStudentId ?> Active)
            </a>
        <?php endif; ?>

        <div class="user-chip">
            <span class="role-badge role-<?= esc($effectiveRole) ?>"><?= esc(strtoupper($effectiveRole)) ?></span>
            <span class="user-name"><?= esc($user['user_name'] ?? ($user['full_name'] ?? 'User')) ?></span>
        </div>
        <a href="<?= base_url('logout') ?>" class="btn-secondary btn-sm">Sign Out</a>
    </div>
</header>

<?php if (! $allowChildSwitch): ?>
<script>
    // Ensure old cached core_lms_auth.js never mounts child switcher for roles where show_child_switcher is OFF
    document.addEventListener('DOMContentLoaded', function () {
        var sw = document.getElementById('js-cla-topbar-switcher');
        if (sw) sw.remove();
    });
</script>
<?php endif; ?>