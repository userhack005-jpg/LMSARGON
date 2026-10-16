<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="pb-toast" class="alert-banner is-hidden" role="alert"></div>

<div class="rbac-split-grid">

    <!-- LEFT: Database-Driven Component Registry (Enable/Disable Widgets) -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">DB COMPONENT REGISTRY</span>
                <h3 class="panel-title">Builder Widgets (`cms_components`)</h3>
            </div>
            <span class="req-badge">MODULAR</span>
        </div>

        <p class="field-hint" style="margin-bottom: 1rem;">
            Enable or disable components available inside the Drag & Drop Page Editor. Any future module can register its own widget in this table!
        </p>

        <div class="telemetry-list">
            <?php foreach ($components as $c): ?>
            <?php $enabled = ((int) $c['is_enabled'] === 1); ?>
            <div class="telemetry-item">
                <div>
                    <div class="identity-name">
                        <span class="text-emerald"><?= esc($c['component_icon']) ?></span>
                        <span><?= esc($c['component_name']) ?></span>
                    </div>
                    <div class="identity-email">key: <?= esc($c['component_key']) ?> // <?= esc($c['category']) ?></div>
                </div>
                <button type="button"
                        class="btn-action js-pb-toggle-comp"
                        data-comp-id="<?= esc((string) $c['id']) ?>"
                        data-action-url="<?= base_url('addons/page-builder/toggle-component') ?>">
                    <?= $enabled ? '✓ Enabled' : '✕ Disabled' ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- RIGHT: Dynamic Pages Directory -->
    <section class="panel-card">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">DYNAMIC FRONTEND ROUTING</span>
                <h3 class="panel-title">Website Pages (`cms_pages`)</h3>
            </div>
            <a href="<?= base_url('addons/page-builder/editor') ?>" class="btn-primary btn-sm">
                <span>+ Create New Dynamic Page</span>
            </a>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>Custom URL Slug</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pages)): ?>
                        <tr>
                            <td colspan="5" class="muted-cell">No dynamic pages created yet. Click "+ Create New Dynamic Page" to launch the Visual Builder!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pages as $p): ?>
                        <tr>
                            <td>
                                <div class="identity-name">
                                    <?= esc($p['title']) ?>
                                    <?php if ((int) $p['is_homepage'] === 1): ?>
                                        <span class="self-tag">HOMEPAGE (/)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="identity-email"><?= esc($p['meta_title'] ?: 'Default SEO Title') ?></div>
                            </td>
                            <td class="mono-cell">
                                <a href="<?= base_url(esc($p['slug'])) ?>" target="_blank" class="text-emerald">
                                    /<?= esc($p['slug']) ?> ↗
                                </a>
                            </td>
                            <td>
                                <span class="status-indicator <?= $p['status'] === 'published' ? 'status-active' : 'status-suspended' ?>">
                                    <?= esc(strtoupper($p['status'])) ?>
                                </span>
                            </td>
                            <td class="mono-cell muted-cell"><?= esc(date('d M Y, H:i', strtotime($p['updated_at']))) ?></td>
                            <td class="text-right">
                                <div class="action-btn-group">
                                    <a href="<?= base_url(esc($p['slug'])) ?>" target="_blank" class="btn-action">↗ View Live</a>
                                    <a href="<?= base_url('addons/page-builder/editor/' . $p['id']) ?>" class="btn-action">✎ Visual Builder</a>
                                    <button type="button"
                                            class="btn-action btn-action-danger js-pb-delete-page"
                                            data-page-id="<?= esc((string) $p['id']) ?>"
                                            data-page-title="<?= esc($p['title']) ?>"
                                            data-action-url="<?= base_url('addons/page-builder/delete-page') ?>">
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