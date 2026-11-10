<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <?php if ($activeRole): ?>
        <?php
            $ws          = $activeRole['workspace_array'] ?? [];
            $custLabels  = $ws['custom_labels'] ?? [];
            $hiddenItems = $ws['hidden_items'] ?? [];
            $showSwitch  = isset($ws['show_child_switcher']) ? (int) $ws['show_child_switcher'] : ($activeRole['role_slug'] === 'parent' ? 1 : 0);
            $dashWidgets = $ws['dashboard_widgets'] ?? ['kpi_overview', 'quick_modules'];
        ?>
        <form action="<?= base_url('addons/rbac/save-workspace') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="role_id" value="<?= (int) $activeRole['id'] ?>">
            <input type="hidden" name="role_slug" value="<?= esc($activeRole['role_slug']) ?>">

            <!-- 1. Top Settings & Dashboard Widgets Card -->
            <section class="console-card" style="margin-bottom: 1.35rem;">
                <div class="card-head rbac-flex-between" style="margin-bottom: 1rem;">
                    <div>
                        <span class="rbac-badge rbac-badge-cyan">1 // PORTAL BEHAVIOR &amp; DASHBOARD WIDGETS</span>
                        <h3 style="margin:0.3rem 0 0;">Workspace Settings for: <span class="text-emerald"><?= esc($activeRole['role_name']) ?></span></h3>
                    </div>
                    <button type="submit" class="rbac-btn rbac-btn-emerald">💾 Save Workspace Settings</button>
                </div>

                <div class="rbac-form-grid-2" style="margin-bottom: 1.15rem;">
                    <label class="rbac-perm-switch-card">
                        <div class="rbac-perm-card-text">
                            <div class="rbac-perm-title">Enable Top-Bar Child Profile Switcher</div>
                            <div class="muted-cell" style="font-size:0.78rem;">Turn ON for Parent accounts so they can switch into their children's learning dashboard.</div>
                        </div>
                        <span class="rbac-ios-switch">
                            <input type="checkbox" name="show_child_switcher" value="1" <?= $showSwitch === 1 ? 'checked' : '' ?>>
                            <span class="rbac-ios-slider"></span>
                        </span>
                    </label>

                    <div class="rbac-perm-switch-card" style="cursor:default;">
                        <div style="width:100%;">
                            <label class="field-label"><span>Default Landing Route After Login</span></label>
                            <input type="text" name="landing_route" class="field-input mono-input" value="<?= esc($ws['landing_route'] ?? 'dashboard') ?>" placeholder="dashboard or portal/family">
                        </div>
                    </div>
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
                <div class="rbac-perm-cards-grid">
                    <?php foreach ($availableWidgets as $wKey => $wLabel): ?>
                        <label class="rbac-perm-switch-card">
                            <div class="rbac-perm-card-text">
                                <div class="rbac-perm-title"><?= esc($wLabel) ?></div>
                                <code class="rbac-perm-code"><?= esc($wKey) ?></code>
                            </div>
                            <span class="rbac-ios-switch">
                                <input type="checkbox" name="dashboard_widgets[]" value="<?= esc($wKey) ?>" <?= in_array($wKey, $dashWidgets, true) ? 'checked' : '' ?>>
                                <span class="rbac-ios-slider"></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 2. Sidebar Menu & Sub-Menu Renamer Card (With 0ms Search & Filter) -->
            <section class="console-card">
                <div class="card-head rbac-flex-between" style="margin-bottom: 0.85rem;">
                    <div>
                        <span class="rbac-badge rbac-badge-emerald">2 // SIDEBAR MENU &amp; SUB-MENU ARCHITECT</span>
                        <h3 style="margin:0.3rem 0 0;">Rename or Hide Sidebar Menus for <span class="text-emerald"><?= esc($activeRole['role_name']) ?></span></h3>
                        <p class="field-hint" style="margin:0.15rem 0 0;">Enter a friendly custom label (e.g. "My Learning Hub" for Students) or toggle the switch to hide specific sub-menus.</p>
                    </div>
                    <button type="submit" class="rbac-btn rbac-btn-emerald">💾 Save Menu Names</button>
                </div>

                <div class="rbac-toolbar-strip">
                    <div class="rbac-toolbar-left">
                        <input type="search" id="js-rbac-menu-search" class="rbac-search-input" placeholder="🔍 Instant search menu name, sub-menu, or route...">
                    </div>
                    <div class="rbac-filter-pills" id="js-rbac-menu-tabs">
                        <button type="button" class="rbac-filter-tab is-active" data-filter="all">All Items</button>
                        <button type="button" class="rbac-filter-tab" data-filter="main">Main Modules Only</button>
                        <button type="button" class="rbac-filter-tab" data-filter="sub">Sub-Menus Only</button>
                        <button type="button" class="rbac-filter-tab" data-filter="hidden">Hidden Only</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width:220px;">Module / Sub-Menu</th>
                                <th>Route</th>
                                <th style="min-width:260px;">Custom Label Shown to <?= esc($activeRole['role_name']) ?></th>
                                <th style="width:150px;">Hide in Sidebar?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($navCatalog ?? []) as $itemKey => $navItem): ?>
                                <?php $isMainHidden = ! empty($hiddenItems[$itemKey]); ?>
                                <tr class="js-rbac-menu-row rbac-main-menu-row"
                                    data-kind="main"
                                    data-hidden="<?= $isMainHidden ? '1' : '0' ?>">
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.55rem;">
                                            <span class="rbac-sim-icon"><?= esc($navItem['icon']) ?></span>
                                            <div>
                                                <div class="identity-name"><?= esc($navItem['default_title']) ?></div>
                                                <span class="rbac-badge rbac-badge-emerald">MAIN MODULE</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono-cell text-cyan">/<?= esc($navItem['route']) ?></td>
                                    <td>
                                        <input type="text"
                                               name="custom_labels[<?= esc($itemKey) ?>]"
                                               value="<?= esc($custLabels[$itemKey] ?? '') ?>"
                                               class="field-input"
                                               placeholder="Default: <?= esc($navItem['default_title']) ?>">
                                    </td>
                                    <td>
                                        <label class="rbac-switch-inline">
                                            <span class="rbac-ios-switch">
                                                <input type="checkbox" name="hidden_items[]" value="<?= esc($itemKey) ?>" class="js-rbac-hide-cb" <?= $isMainHidden ? 'checked' : '' ?>>
                                                <span class="rbac-ios-slider is-danger-slider"></span>
                                            </span>
                                            <span class="mono-cell muted-cell">Hide</span>
                                        </label>
                                    </td>
                                </tr>
                                <?php foreach (($navItem['sub_views'] ?? []) as $sv): ?>
                                    <?php
                                        $svKey       = (string) ($sv['key'] ?? '');
                                        $isSubHidden = ! empty($hiddenItems[$svKey]);
                                    ?>
                                    <tr class="js-rbac-menu-row"
                                        data-kind="sub"
                                        data-hidden="<?= $isSubHidden ? '1' : '0' ?>">
                                        <td style="padding-left: 2.4rem;">
                                            <div class="identity-name" style="font-size:0.84rem;">
                                                ↳ <?= esc($sv['icon'] ?? '•') ?> <?= esc($sv['label']) ?>
                                            </div>
                                            <div class="mono-cell muted-cell">Parent: <?= esc($navItem['default_title']) ?></div>
                                        </td>
                                        <td class="mono-cell muted-cell">/<?= esc($sv['route'] ?? '') ?></td>
                                        <td>
                                            <input type="text"
                                                   name="custom_labels[<?= esc($svKey) ?>]"
                                                   value="<?= esc($custLabels[$svKey] ?? '') ?>"
                                                   class="field-input"
                                                   placeholder="Default: <?= esc($sv['label']) ?>">
                                        </td>
                                        <td>
                                            <label class="rbac-switch-inline">
                                                <span class="rbac-ios-switch">
                                                    <input type="checkbox" name="hidden_items[]" value="<?= esc($svKey) ?>" class="js-rbac-hide-cb" <?= $isSubHidden ? 'checked' : '' ?>>
                                                    <span class="rbac-ios-slider is-danger-slider"></span>
                                                </span>
                                                <span class="mono-cell muted-cell">Hide</span>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="rbac-sticky-save-bar">
                    <span class="mono-cell muted-cell">Empty custom labels automatically use the default module name.</span>
                    <button type="submit" class="rbac-btn rbac-btn-emerald rbac-btn-lg">
                        💾 Save Friendly Menu Names &amp; Widgets for <?= esc($activeRole['role_name']) ?>
                    </button>
                </div>
            </section>
        </form>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
