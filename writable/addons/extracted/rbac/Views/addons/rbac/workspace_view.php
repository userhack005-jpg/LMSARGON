<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <?php if ($activeRole): ?>
        <?php
            $ws          = $activeRole['workspace_array'] ?? [];
            $custLabels  = $ws['custom_labels'] ?? [];
            $hiddenItems = $ws['hidden_items'] ?? [];
            $showSwitch  = isset($ws['show_child_switcher']) ? (int) $ws['show_child_switcher'] : ($activeRole['role_slug'] === 'parent' ? 1 : 0);
            $dashWidgets = $ws['dashboard_widgets'] ?? ['kpi_overview', 'quick_modules'];
        ?>
        <section class="console-card rbac-section-gap">
            <div class="card-head rbac-matrix-head">
                <div>
                    <span class="mono-label text-emerald">ROLE WORKSPACE &amp; MENU RENAMER</span>
                    <h3>Customizing Sidebar &amp; Dashboard for: <span class="text-emerald"><?= esc($activeRole['role_name']) ?></span></h3>
                </div>
            </div>

            <form action="<?= base_url('addons/rbac/save-workspace') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="role_id" value="<?= (int) $activeRole['id'] ?>">
                <input type="hidden" name="role_slug" value="<?= esc($activeRole['role_slug']) ?>">

                <div class="panel-card rbac-group-card" style="margin-bottom: 1.25rem;">
                    <div class="rbac-group-header">
                        <span class="mono-label text-cyan">1. TOP-BAR &amp; LANDING ROUTE FOR <?= strtoupper(esc($activeRole['role_name'])) ?></span>
                    </div>
                    <div class="grid-2-col" style="align-items:center;">
                        <label class="rbac-switch-row" style="padding:0.5rem 0;">
                            <div>
                                <strong>Enable Top-Bar Child Profile Switcher</strong>
                                <div class="field-hint">Turn ON for Parent accounts so they can switch between their children.</div>
                            </div>
                            <span class="rbac-ios-switch">
                                <input type="checkbox" name="show_child_switcher" value="1" <?= $showSwitch === 1 ? 'checked' : '' ?>>
                                <span class="rbac-ios-slider"></span>
                            </span>
                        </label>
                        <div class="field-group" style="margin:0;">
                            <label class="field-label"><span>Default Page After Login</span></label>
                            <input type="text" name="landing_route" class="field-input mono-input" value="<?= esc($ws['landing_route'] ?? 'dashboard') ?>" placeholder="dashboard or portal/family">
                        </div>
                    </div>
                </div>

                <div class="panel-card rbac-group-card" style="margin-bottom: 1.25rem;">
                    <div class="rbac-group-header">
                        <span class="mono-label text-emerald">2. DASHBOARD BLOCKS VISIBLE TO <?= strtoupper(esc($activeRole['role_name'])) ?></span>
                    </div>
                    <?php
                        $availableWidgets = [
                            'kpi_overview'     => 'Academy Account & Revenue Summary Cards [Admin]',
                            'quick_modules'    => 'Assigned Module Quick Launch Cards [All Roles]',
                            'parent_children'  => 'My Enrolled Children & Fee Status Cards [Parent]',
                            'student_arena'    => 'My Next Live Class, 3D Abacus & Practice Hub [Student]',
                            'teacher_schedule' => 'My Assigned Batches, Students & Today\'s Classes [Teacher]',
                            'mod_flagged_chat' => 'Safety Moderation & Substitute Alerts [Admin]',
                        ];
                    ?>
                    <div class="rbac-perm-grid">
                        <?php foreach ($availableWidgets as $wKey => $wLabel): ?>
                            <div class="rbac-perm-item">
                                <label class="rbac-switch-row">
                                    <div class="rbac-switch-meta">
                                        <div class="identity-name"><?= esc($wLabel) ?></div>
                                        <div class="mono-cell rbac-key-code"><?= esc($wKey) ?></div>
                                    </div>
                                    <span class="rbac-ios-switch">
                                        <input type="checkbox" name="dashboard_widgets[]" value="<?= esc($wKey) ?>" <?= in_array($wKey, $dashWidgets, true) ? 'checked' : '' ?>>
                                        <span class="rbac-ios-slider"></span>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel-card rbac-group-card">
                    <div class="rbac-group-header">
                        <span class="mono-label text-emerald">3. SIDEBAR MENU &amp; SUB-MENU RENAMER FOR <?= strtoupper(esc($activeRole['role_name'])) ?></span>
                    </div>
                    <p class="field-hint" style="margin-bottom: 1rem;">
                        Customize the exact user-friendly Sidebar Menu Name shown to <strong><?= esc($activeRole['role_name']) ?></strong> or hide specific sub-menus.
                    </p>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Module / Sub-Menu</th>
                                    <th>Default Name</th>
                                    <th>Friendly Name Shown to <?= esc($activeRole['role_name']) ?></th>
                                    <th>Hide from Sidebar?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($navCatalog ?? []) as $itemKey => $navItem): ?>
                                    <tr style="background: rgba(16, 185, 129, 0.05);">
                                        <td>
                                            <strong><?= esc($navItem['icon']) ?> <?= esc($navItem['default_title']) ?></strong>
                                        </td>
                                        <td><?= esc($navItem['default_title']) ?></td>
                                        <td>
                                            <input type="text"
                                                   name="custom_labels[<?= esc($itemKey) ?>]"
                                                   value="<?= esc($custLabels[$itemKey] ?? '') ?>"
                                                   class="field-input"
                                                   placeholder="<?= esc($navItem['default_title']) ?>">
                                        </td>
                                        <td>
                                            <label class="rbac-check-label">
                                                <input type="checkbox" name="hidden_items[]" value="<?= esc($itemKey) ?>" <?= ! empty($hiddenItems[$itemKey]) ? 'checked' : '' ?>>
                                                <span>Hide Menu</span>
                                            </label>
                                        </td>
                                    </tr>
                                    <?php foreach (($navItem['sub_views'] ?? []) as $sv): ?>
                                        <?php $svKey = $sv['key'] ?? ''; ?>
                                        <tr>
                                            <td style="padding-left: 2.2rem;">
                                                <span>↳ <?= esc($sv['icon'] ?? '•') ?> <?= esc($sv['label']) ?></span>
                                            </td>
                                            <td><?= esc($sv['label']) ?></td>
                                            <td>
                                                <input type="text"
                                                       name="custom_labels[<?= esc($svKey) ?>]"
                                                       value="<?= esc($custLabels[$svKey] ?? '') ?>"
                                                       class="field-input"
                                                       placeholder="<?= esc($sv['label']) ?>">
                                            </td>
                                            <td>
                                                <label class="rbac-check-label">
                                                    <input type="checkbox" name="hidden_items[]" value="<?= esc($svKey) ?>" <?= ! empty($hiddenItems[$svKey]) ? 'checked' : '' ?>>
                                                    <span>Hide Sub-Menu</span>
                                                </label>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="action-btn-group rbac-matrix-footer">
                    <button type="submit" class="btn-primary">💾 Save Friendly Menu Names &amp; Widgets for <?= esc($activeRole['role_name']) ?></button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
