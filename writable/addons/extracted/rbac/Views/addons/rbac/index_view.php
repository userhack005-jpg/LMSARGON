<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="rbac-admin-wrapper">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success rbac-alert"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-danger rbac-alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Top KPI Row -->
    <div class="grid-3-col rbac-kpi-grid">
        <div class="panel-card">
            <span class="mono-label">ACTIVE ROLES (BACKEND & FRONTEND)</span>
            <h2 class="text-emerald rbac-kpi-val"><?= count($roles ?? []) ?></h2>
        </div>
        <div class="panel-card">
            <span class="mono-label">REGISTERED PERMISSION KEYS</span>
            <?php
                $totalPerms = 0;
                foreach (($groupedFeatures ?? []) as $gList) {
                    $totalPerms += count($gList);
                }
            ?>
            <h2 class="text-cyan rbac-kpi-val"><?= $totalPerms ?></h2>
        </div>
        <div class="panel-card rbac-sync-card">
            <div>
                <span class="mono-label">AUTO-DISCOVER ADD-ON PERMISSIONS</span>
                <p class="muted-cell rbac-sync-sub">Scan all installed add-ons & rebuild 0ms JSON cache</p>
            </div>
            <?php if (! empty($canEdit)): ?>
                <form action="<?= base_url('addons/rbac/sync-addons') ?>" method="post" class="rbac-inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-primary btn-sm">↻ Sync Add-ons & Cache</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 1: ROLE DIRECTORY & VISUAL PERMISSION MATRIX -->
    <div class="rbac-main-grid rbac-section-gap">
        <!-- Left Column: Create / Edit Role + Custom Feature Key -->
        <div class="rbac-left-stack">
            <?php if (! empty($canCreate) or ! empty($canEdit)): ?>
            <section class="console-card rbac-card-gap">
                <div class="card-head">
                    <div>
                        <span class="mono-label">CUSTOM ROLE BUILDER</span>
                        <h3 id="rbac-role-form-title">Create / Edit Role</h3>
                    </div>
                </div>

                <form action="<?= base_url('addons/rbac/save-role') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="role_id" value="0">

                    <div class="field-group">
                        <label class="field-label">Role Name *</label>
                        <input type="text" name="role_name" id="role_name" class="field-input" placeholder="e.g. Branch Coordinator" required>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Role Slug (Unique Key)</label>
                        <input type="text" name="role_slug" id="role_slug" class="field-input" placeholder="e.g. branch_coordinator">
                    </div>

                    <div class="field-group">
                        <label class="field-label">Target Portal / Access Scope</label>
                        <select name="portal_type" id="role_portal" class="field-input">
                            <option value="backend">Backend Admin / Staff Panel (Admin, Teacher, Manager)</option>
                            <option value="frontend">Frontend Website Portal (Student, Parent, Website User)</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Role Description</label>
                        <input type="text" name="description" id="role_desc" class="field-input" placeholder="Short summary of responsibilities...">
                    </div>

                    <div class="field-group">
                        <label class="rbac-check-label">
                            <input type="checkbox" name="is_default_front" id="role_def_front" value="1">
                            <span>Set as Default Role for New Website Signups</span>
                        </label>
                    </div>

                    <div class="action-btn-group rbac-form-actions">
                        <button type="submit" class="btn-primary">Save Role</button>
                        <button type="button" class="btn-secondary" id="js-rbac-role-reset">Reset</button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <?php if (! empty($canCreate)): ?>
            <section class="console-card">
                <div class="card-head">
                    <div>
                        <span class="mono-label">REGISTER WEBSITE FEATURE PERMISSION</span>
                        <h3>Add Custom Permission Key</h3>
                    </div>
                </div>
                <form action="<?= base_url('addons/rbac/save-feature') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="field-group">
                        <label class="field-label">Permission Key * (module.action)</label>
                        <input type="text" name="perm_key" class="field-input" placeholder="e.g. worksheets.download" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Feature Label *</label>
                        <input type="text" name="perm_label" class="field-input" placeholder="e.g. Download PDF Worksheets" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Module Group Name</label>
                        <input type="text" name="module_group" class="field-input" value="Custom Website Features" placeholder="e.g. Abacus Portal">
                    </div>
                    <button type="submit" class="btn-secondary">Add to Matrix</button>
                </form>
            </section>
            <?php endif; ?>
        </div>

        <!-- Right Column: Roles Selector Tabs + Interactive Permission Matrix -->
        <section class="console-card">
            <div class="card-head rbac-matrix-head">
                <div>
                    <span class="mono-label">GRANULAR PERMISSION MATRIX (0ms CACHE ENGINE)</span>
                    <h3>Configure Permissions for: <span class="text-emerald"><?= esc($activeRole['role_name'] ?? 'Role') ?></span></h3>
                </div>
                <div class="action-btn-group">
                    <button type="button" class="btn-secondary btn-sm" id="js-rbac-check-all">Select All</button>
                    <button type="button" class="btn-secondary btn-sm" id="js-rbac-uncheck-all">Clear All</button>
                </div>
            </div>

            <!-- Role Switcher Pills -->
            <div class="rbac-role-tabs">
                <?php foreach (($roles ?? []) as $rItem): ?>
                    <?php $isAct = ($rItem['role_slug'] === ($selectedRoleSlug ?? '')); ?>
                    <div class="rbac-role-pill <?= $isAct ? 'is-active' : '' ?>">
                        <a href="<?= base_url('addons/rbac?role=' . urlencode($rItem['role_slug'])) ?>" class="rbac-role-tab-link">
                            <strong><?= esc($rItem['role_name']) ?></strong>
                            <span class="mono-label rbac-portal-tag"><?= strtoupper(esc($rItem['portal_type'] ?? 'frontend')) ?></span>
                            <?php if ((int) ($rItem['is_default_front'] ?? 0) === 1): ?>
                                <span class="role-badge">DEFAULT FRONT</span>
                            <?php endif; ?>
                        </a>
                        <div class="rbac-role-mini-actions">
                            <?php if (! empty($canEdit)): ?>
                                <button type="button"
                                        class="btn-action js-edit-rbac-role"
                                        data-id="<?= (int) $rItem['id'] ?>"
                                        data-name="<?= esc($rItem['role_name']) ?>"
                                        data-slug="<?= esc($rItem['role_slug']) ?>"
                                        data-portal="<?= esc($rItem['portal_type'] ?? 'frontend') ?>"
                                        data-desc="<?= esc($rItem['description'] ?? '') ?>"
                                        data-def="<?= (int) ($rItem['is_default_front'] ?? 0) ?>">
                                    Edit
                                </button>
                            <?php endif; ?>
                            <?php if (! empty($canDelete) && (int) ($rItem['is_system'] ?? 0) === 0): ?>
                                <form action="<?= base_url('addons/rbac/delete-role') ?>" method="post" class="rbac-inline-form js-rbac-confirm-del">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $rItem['id'] ?>">
                                    <button type="submit" class="btn-action text-danger">×</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($activeRole): ?>
                <?php
                    $activePerms = $activeRole['permissions_array'] ?? [];
                    $isSuper     = ($activeRole['role_slug'] === 'super_admin' || in_array('*', $activePerms, true));
                ?>
                <form action="<?= base_url('addons/rbac/save-matrix') ?>" method="post" id="rbac-matrix-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="role_id" value="<?= (int) $activeRole['id'] ?>">
                    <input type="hidden" name="role_slug" value="<?= esc($activeRole['role_slug']) ?>">

                    <?php if ($activeRole['role_slug'] === 'super_admin'): ?>
                        <div class="alert-banner alert-success rbac-alert">
                            <strong>Super Administrator</strong> has wildcard (<code>*</code>) root access to all current and future modules automatically.
                        </div>
                    <?php endif; ?>

                    <div class="rbac-groups-container">
                        <?php foreach (($groupedFeatures ?? []) as $groupTitle => $features): ?>
                            <div class="panel-card rbac-group-card">
                                <div class="rbac-group-header">
                                    <span class="mono-label text-emerald"><?= esc($groupTitle) ?></span>
                                    <button type="button" class="btn-action js-toggle-group">Toggle Group</button>
                                </div>
                                <div class="rbac-perm-grid">
                                    <?php foreach ($features as $feat): ?>
                                        <?php
                                            $pKey    = (string) $feat['perm_key'];
                                            $checked = $isSuper || in_array($pKey, $activePerms, true);
                                        ?>
                                        <div class="rbac-perm-item">
                                            <label class="rbac-check-label">
                                                <input type="checkbox"
                                                       name="permissions[]"
                                                       value="<?= esc($pKey) ?>"
                                                       class="js-rbac-perm-cb"
                                                       <?= $checked ? 'checked' : '' ?>
                                                       <?= $activeRole['role_slug'] === 'super_admin' ? 'disabled' : '' ?>>
                                                <div>
                                                    <div class="identity-name"><?= esc($feat['perm_label']) ?></div>
                                                    <div class="mono-cell rbac-key-code"><?= esc($pKey) ?></div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (! empty($canEdit) && $activeRole['role_slug'] !== 'super_admin'): ?>
                        <div class="action-btn-group rbac-matrix-footer">
                            <button type="submit" class="btn-primary">💾 Save Permissions for <?= esc($activeRole['role_name']) ?></button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <!-- SECTION 2: USER ROLE & PARENT-STUDENT LINKING DIRECTORY -->
    <?php if (! empty($usersList)): ?>
    <section class="console-card">
        <div class="card-head">
            <div>
                <span class="mono-label">USER ROLE ASSIGNMENT & STUDENT-PARENT LINKING</span>
                <h3>Website & Backend Users (<?= count($usersList) ?>)</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User ID & Name</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Assign Role & Optional Parent Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $uRow): ?>
                        <?php
                            $uId       = (int) ($uRow['id'] ?? 0);
                            $uName     = $uRow['full_name'] ?? ($uRow['name'] ?? ($uRow['username'] ?? 'User #' . $uId));
                            $uEmail    = $uRow['email'] ?? '—';
                            $uRoleSlug = $uRow['role_slug'] ?? ($uRow['role'] ?? 'student');
                            $uParentId = (int) ($uRow['parent_user_id'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <div class="identity-name">#<?= $uId ?> — <?= esc($uName) ?></div>
                            </td>
                            <td class="mono-cell"><?= esc($uEmail) ?></td>
                            <td>
                                <span class="role-badge"><?= esc($uRoleSlug) ?></span>
                                <?php if ($uParentId > 0): ?>
                                    <span class="mono-label muted-cell">Parent ID: #<?= $uParentId ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (! empty($canEdit)): ?>
                                    <form action="<?= base_url('addons/rbac/assign-user-role') ?>" method="post" class="rbac-user-assign-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= $uId ?>">
                                        <select name="role_slug" class="field-input rbac-select-sm">
                                            <?php foreach (($roles ?? []) as $rOpt): ?>
                                                <option value="<?= esc($rOpt['role_slug']) ?>" <?= $uRoleSlug === $rOpt['role_slug'] ? 'selected' : '' ?>>
                                                    <?= esc($rOpt['role_name']) ?> (<?= esc($rOpt['portal_type']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number"
                                               name="parent_user_id"
                                               class="field-input rbac-input-sm"
                                               value="<?= $uParentId > 0 ? $uParentId : '' ?>"
                                               placeholder="Parent User ID (Optional)">
                                        <button type="submit" class="btn-secondary btn-sm">Update</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
