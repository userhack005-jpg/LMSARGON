<?php
    $baseRoleSlug    = strtolower((string) (session()->get('role_slug') ?: 'student'));
    if ((int) session()->get('role_id') === 1) {
        $baseRoleSlug = 'super_admin';
    }
    $effectiveRole   = function_exists('cms_get_effective_role') ? cms_get_effective_role() : $baseRoleSlug;
    $activeStudentId = (int) (session()->get('active_student_id') ?: 0);
    $portalViewMode  = (string) (session()->get('portal_view_mode') ?: 'default');
    $isInChildMode   = ($portalViewMode === 'student' && $activeStudentId > 0 && $baseRoleSlug !== 'super_admin');

    $baseWorkspace    = function_exists('cms_get_role_workspace') ? cms_get_role_workspace($baseRoleSlug) : [];
    $allowChildSwitch = (int) ($baseWorkspace['workspace']['show_child_switcher'] ?? ($baseRoleSlug === 'parent' ? 1 : 0)) === 1;
?>
<style>
    .app-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 0.75rem !important;
        padding: 0.75rem 1.25rem !important;
        min-height: 64px !important;
        height: auto !important;
        box-sizing: border-box !important;
        max-width: 100% !important;
    }
    .header-left {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        min-width: 0 !important;
        flex: 1 1 200px !important;
    }
    .header-page-title {
        font-size: clamp(1rem, 2vw, 1.25rem) !important;
        margin: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 380px;
    }
    .header-right {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
        min-width: 0 !important;
    }
    .cla-topbar-switcher {
        background: var(--bg-elevated, #171a26) !important;
        color: var(--text-primary, #f3f4f6) !important;
        border: 1px solid var(--accent-emerald, #10b981) !important;
        padding: 0.22rem 0.6rem !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        font-size: 0.78rem !important;
        max-width: 100% !important;
    }
    .cla-topbar-select {
        background-color: var(--bg-surface, #11131c) !important;
        color: var(--text-primary, #f3f4f6) !important;
        font-weight: 700 !important;
        padding: 0.18rem 0.45rem !important;
        border-radius: 6px !important;
        font-size: 0.78rem !important;
        max-width: 170px !important;
    }
    @media (max-width: 768px) {
        .header-page-title { max-width: 200px; }
        .header-right { width: 100%; justify-content: space-between; }
    }
</style>

<header class="app-header">
    <div class="header-left">
        <button type="button" id="sidebar-toggle-btn" class="btn-icon-mobile" aria-label="Toggle Navigation">
            ☰
        </button>
        <div style="min-width:0;">
            <span class="mono-label" style="font-size:0.65rem;">ACTIVE WORKSPACE</span>
            <h1 class="header-page-title"><?= esc($pageTitle ?? 'Workspace') ?></h1>
        </div>
    </div>

    <div class="header-right">
        <?php if ($isInChildMode): ?>
            <form action="<?= base_url('portal/switch-student') ?>" method="post" style="margin:0;display:inline-flex;">
                <?= csrf_field() ?>
                <input type="hidden" name="student_id" value="0">
                <button type="submit" class="btn-primary btn-sm" style="height:32px;padding:0 0.75rem;font-size:0.76rem;white-space:nowrap;">
                    🏠 ← Parent Account
                </button>
            </form>
        <?php endif; ?>

        <div class="user-chip" style="padding:0.25rem 0.65rem;">
            <span class="role-badge role-<?= esc($effectiveRole) ?>" style="font-size:0.68rem;"><?= esc(strtoupper($effectiveRole)) ?></span>
            <span class="user-name" style="font-size:0.82rem;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($user['user_name'] ?? ($user['full_name'] ?? 'User')) ?></span>
        </div>
        <a href="<?= base_url('logout') ?>" class="btn-secondary btn-sm" style="height:32px;padding:0 0.7rem;font-size:0.76rem;">Sign Out</a>
    </div>
</header>

<?php if (! $allowChildSwitch): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sw = document.getElementById('js-cla-topbar-switcher');
        if (sw) sw.remove();
    });
</script>
<?php endif; ?>
