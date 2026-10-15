<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<!-- Role Counter Matrix (5 Cards) -->
<div class="metrics-grid-5">
    <div class="metric-card">
        <span class="mono-label">TOTAL ACCOUNTS</span>
        <strong class="metric-val"><?= esc((string) ($stats['total'] ?? 0)) ?></strong>
        <span class="metric-sub">Provisioned identities</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">SUPER ADMINS</span>
        <strong class="metric-val text-emerald"><?= esc((string) ($stats['super_admin'] ?? 0)) ?></strong>
        <span class="metric-sub">Root system authority</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">ADMINISTRATORS</span>
        <strong class="metric-val text-cyan"><?= esc((string) ($stats['admin'] ?? 0)) ?></strong>
        <span class="metric-sub">Operations & staff control</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">TEACHERS</span>
        <strong class="metric-val text-amber"><?= esc((string) ($stats['teacher'] ?? 0)) ?></strong>
        <span class="metric-sub">Instructors & evaluators</span>
    </div>
    <div class="metric-card">
        <span class="mono-label">STUDENTS</span>
        <strong class="metric-val text-violet"><?= esc((string) ($stats['student'] ?? 0)) ?></strong>
        <span class="metric-sub">Enrolled learners</span>
    </div>
</div>

<!-- System Telemetry & Pre-Configured Roles Overview -->
<div class="grid-2-col">
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">PLATFORM CONFIGURATION</span>
                <h3 class="panel-title">System Identity & Security Status</h3>
            </div>
            <span class="req-badge">HEALTHY</span>
        </div>

        <div class="telemetry-list">
            <div class="telemetry-item">
                <span class="mono-label">WEBSITE TITLE</span>
                <strong><?= esc($settings['site_title']) ?></strong>
            </div>
            <div class="telemetry-item">
                <span class="mono-label">BRAND LOGO</span>
                <span><?= ! empty($settings['site_logo']) ? 'Custom Logo Uploaded' : 'Using Default Monogram' ?></span>
            </div>
            <div class="telemetry-item">
                <span class="mono-label">FAVICON STATUS</span>
                <span><?= ! empty($settings['site_favicon']) ? 'Custom Favicon Active' : 'Optional (Not Set)' ?></span>
            </div>
            <div class="telemetry-item">
                <span class="mono-label">INSTALLER LOCK</span>
                <span class="text-emerald">LOCKED (writable/installed.lock)</span>
            </div>
        </div>

        <div class="console-footer">
            <span class="mono-note">QUICK NAVIGATION</span>
            <a href="<?= base_url('users') ?>" class="btn-primary btn-sm">
                <span>Open User & Role Manager</span>
                <span class="btn-arrow">→</span>
            </a>
        </div>
    </section>

    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">RBAC SCHEMA</span>
                <h3 class="panel-title">Active Role Definitions</h3>
            </div>
            <span class="opt-badge">4 ROLES</span>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $r): ?>
                    <tr>
                        <td>
                            <span class="role-badge role-<?= esc($r['role_slug']) ?>">
                                <?= esc($r['role_name']) ?>
                            </span>
                        </td>
                        <td class="muted-cell"><?= esc($r['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>