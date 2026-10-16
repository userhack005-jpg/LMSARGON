<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<!-- Notification Toast -->
<div id="addon-toast" class="alert-banner is-hidden" role="alert"></div>

<div class="rbac-split-grid">

    <!-- LEFT COLUMN: ZIP Upload & 1-Click Starter Package Generator -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">MODULAR PLUGIN DEPLOYER</span>
                <h3 class="panel-title">Install Add-On (.zip)</h3>
            </div>
            <span class="req-badge">AUTO-UNZIP</span>
        </div>

        <form id="addon-upload-form" action="<?= base_url('addons/upload') ?>" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="field-group">
                <label for="addon_zip" class="field-label">
                    <span>Select Add-On Package (`.zip`)</span>
                    <span class="req-badge">MANIFEST REQUIRED</span>
                </label>
                <input type="file" id="addon_zip" name="addon_zip" class="field-input" accept=".zip" required>
                <span class="field-hint">Automatically extracts Controller, Model, View & runs 1-time DB migration.</span>
            </div>

            <button type="submit" id="btn-install-addon" class="btn-primary btn-full">
                <span id="install-addon-text">Upload, Unzip & Initialize Add-On</span>
                <span class="btn-arrow">⚡</span>
            </button>
        </form>

        <!-- Sample Payment Gateway Generator Box -->
        <div class="role-legend">
            <span class="mono-label">INSTANT STARTER PACKAGE (TEST NOW)</span>
            <p class="field-hint">
                Download a complete, ready-to-install <strong>Payment Gateway Add-On (.zip)</strong> containing Backend Config, Frontend Checkout, and Auto-DB Migration, then upload it above:
            </p>
            <a href="<?= base_url('addons/sample-gateway-zip') ?>" class="btn-secondary btn-sm btn-full">
                <span>⬇ Download Sample Payment Gateway (.zip)</span>
            </a>
        </div>

        <!-- Add-On ZIP Folder Blueprint Specification -->
        <div class="role-legend">
            <span class="mono-label">REQUIRED `.ZIP` STRUCTURE BLUEPRINT</span>
            <pre class="code-blueprint">my_addon.zip
├── addon.json          # Manifest & default config
├── Routes.php          # Auto-loaded Module Routes
└── payload/
    └── app/
        ├── Controllers/Addons/MyModule/...
        ├── Models/Addons/MyModule/...
        └── Views/addons/my_module/...</pre>
        </div>
    </aside>

    <!-- RIGHT COLUMN: Installed Add-Ons Directory -->
    <section class="panel-card">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">INSTALLED EXTENSIONS & GATEWAYS</span>
                <h3 class="panel-title">Active & Registered Add-On Modules</h3>
            </div>
            <span class="opt-badge"><?= count($addons) ?> INSTALLED</span>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Add-On Module</th>
                        <th>Category</th>
                        <th>Version</th>
                        <th>State</th>
                        <th class="text-right">Module Endpoints & Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($addons)): ?>
                    <tr>
                        <td colspan="5" class="muted-cell">
                            No add-ons installed yet. Download the Sample Payment Gateway ZIP on the left and upload it to see the engine in action!
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($addons as$a): ?>
                        <?php $isActive = ((int)$a['is_active'] === 1); ?>
                        <tr>
                            <td>
                                <div class="identity-name"><?= esc($a['name']) ?></div>
                                <div class="identity-email">slug: <?= esc($a['slug']) ?> // by <?= esc($a['author'] ?? 'System') ?></div>
                            </td>
                            <td>
                                <span class="role-badge role-admin"><?= esc($a['category']) ?></span>
                            </td>
                            <td class="mono-cell">v<?= esc($a['version']) ?></td>
                            <td>
                                <span class="status-indicator <?= $isActive ? 'status-active' : 'status-suspended' ?>" id="addon-badge-<?= esc((string) $a['id']) ?>">
                                    <?= $isActive ? 'ACTIVE' : 'DISABLED' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="action-btn-group">
                                    <?php if (! empty($a['admin_route']) &&$isActive): ?>
                                        <a href="<?= base_url(esc($a['admin_route'])) ?>" class="btn-action">⚙ Backend Config</a>
                                    <?php endif; ?>

                                    <?php if (! empty($a['frontend_route']) &&$isActive): ?>
                                        <a href="<?= base_url(esc($a['frontend_route'])) ?>" target="_blank" class="btn-action">↗ Frontend View</a>
                                    <?php endif; ?>

                                    <button type="button"
                                            class="btn-action js-toggle-addon"
                                            data-addon-id="<?= esc((string) $a['id']) ?>"
                                            data-action-url="<?= base_url('addons/toggle') ?>">
                                        <?= $isActive ? 'Disable' : 'Enable' ?>
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