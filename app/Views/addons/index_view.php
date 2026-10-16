<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="addon-toast" class="alert-banner is-hidden" role="alert"></div>

<div class="rbac-split-grid">

    <!-- LEFT: Clean ZIP Uploader -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">PLUGIN DEPLOYER</span>
                <h3 class="panel-title">Install Add-On (.zip)</h3>
            </div>
            <span class="req-badge">AUTO-CLEAN ZIP</span>
        </div>

        <form id="addon-upload-form" action="<?= base_url('addons/upload') ?>" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <div class="field-group">
                <label for="addon_zip" class="field-label">
                    <span>Select Package (`.zip`)</span>
                    <span class="req-badge">REQUIRED</span>
                </label>
                <input type="file" id="addon_zip" name="addon_zip" class="field-input" accept=".zip" required>
                <span class="field-hint">Extracts module, initializes DB, and automatically deletes the `.zip` archive.</span>
            </div>

            <button type="submit" id="btn-install-addon" class="btn-primary btn-full">
                <span id="install-addon-text">Upload & Install Add-On</span>
                <span class="btn-arrow">⚡</span>
            </button>
        </form>

        <div class="role-legend">
            <span class="mono-label">REQUIRED `.ZIP` STRUCTURE</span>
            <pre class="code-blueprint">module.zip
├── addon.json
├── Routes.php
└── payload/
    └── app/ (Controllers, Models, Views)</pre>
        </div>
    </aside>

    <!-- RIGHT: Installed Add-Ons Table with Enable/Disable & Delete -->
    <section class="panel-card">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">EXTENSION REGISTRY</span>
                <h3 class="panel-title">Installed Add-On Modules</h3>
            </div>
            <span class="opt-badge"><?= count($addons) ?> MODULES</span>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Add-On Module</th>
                        <th>Category</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($addons)): ?>
                    <tr>
                        <td colspan="5" class="muted-cell">No add-ons installed. Upload a `.zip` package on the left.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($addons as$a): ?>
                        <?php $isActive = ((int)$a['is_active'] === 1); ?>
                        <tr>
                            <td>
                                <div class="identity-name"><?= esc($a['name']) ?></div>
                                <div class="identity-email">slug: <?= esc($a['slug']) ?></div>
                            </td>
                            <td><span class="role-badge role-admin"><?= esc($a['category']) ?></span></td>
                            <td class="mono-cell">v<?= esc($a['version']) ?></td>
                            <td>
                                <span class="status-indicator <?= $isActive ? 'status-active' : 'status-suspended' ?>">
                                    <?= $isActive ? 'ACTIVE' : 'DISABLED' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="action-btn-group">
                                    <?php if (! empty($a['admin_route']) &&$isActive): ?>
                                        <a href="<?= base_url(esc($a['admin_route'])) ?>" class="btn-action">⚙ Manage</a>
                                    <?php endif; ?>

                                    <?php if (! empty($a['frontend_route']) &&$isActive): ?>
                                        <a href="<?= base_url(esc($a['frontend_route'])) ?>" target="_blank" class="btn-action">↗ View</a>
                                    <?php endif; ?>

                                    <button type="button"
                                            class="btn-action js-toggle-addon"
                                            data-addon-id="<?= esc((string) $a['id']) ?>"
                                            data-action-url="<?= base_url('addons/toggle') ?>">
                                        <?= $isActive ? 'Disable' : 'Enable' ?>
                                    </button>

                                    <button type="button"
                                            class="btn-action btn-action-danger js-delete-addon"
                                            data-addon-id="<?= esc((string) $a['id']) ?>"
                                            data-addon-name="<?= esc($a['name']) ?>"
                                            data-action-url="<?= base_url('addons/delete') ?>">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
<?= $this->endSection() ?>