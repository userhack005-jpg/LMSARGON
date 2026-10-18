<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="pb-toast" class="alert-banner is-hidden" role="alert"></div>
<a href="<?= base_url('addons/page-builder/footer') ?>" class="btn-secondary btn-sm">⊞ Customize Website Footer</a>
<div class="rbac-split-grid">

    <!-- LEFT: Database-Driven Component Registry (Enable/Disable Widgets) -->
    <aside class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">DB COMPONENT REGISTRY</span>
                <h3 class="panel-title">Builder Widgets </h3>
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

<!-- ==========================================
     DYNAMIC REVIEWS & UNLIMITED FAQS DATABASE MANAGER
     ========================================== -->
<div class="grid-2-col" style="margin-top: 1.5rem;">

    <!-- 1. Student Reviews / Testimonials DB Manager -->
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">DYNAMIC SLIDER DATA // `cms_reviews`</span>
                <h3 class="panel-title">Manage Student Testimonials (<?= count($reviews ?? []) ?>)</h3>
            </div>
            <span class="req-badge">LIVE CAROUSEL</span>
        </div>

        <form id="pb-add-review-form" action="<?= base_url('addons/page-builder/save-review') ?>" method="post" style="margin-bottom: 1.25rem;">
            <?= csrf_field() ?>
            <div class="grid-3-col">
                <div class="field-group">
                    <input type="text" name="reviewer_name" class="field-input" placeholder="Student / Parent Name" required>
                </div>
                <div class="field-group">
                    <input type="text" name="designation" class="field-input" placeholder="Role / Location (e.g. Dubai, UAE)">
                </div>
                <div class="field-group">
                    <select name="rating" class="field-select">
                        <option value="5">★★★★★ (5 Stars)</option>
                        <option value="4">★★★★☆ (4 Stars)</option>
                    </select>
                </div>
            </div>
            <div class="field-group" style="display:flex; gap:0.6rem;">
                <input type="text" name="review_text" class="field-input" placeholder="Write student testimonial or success story..." required>
                <button type="submit" class="btn-primary btn-sm" style="flex-shrink:0;">+ Add Review</button>
            </div>
        </form>

        <div class="telemetry-list" style="max-height: 260px; overflow-y: auto;">
            <?php foreach (($reviews ?? []) as $rev): ?>
            <div class="telemetry-item">
                <div>
                    <div class="identity-name">
                        <span><?= esc($rev['reviewer_name']) ?></span>
                        <span class="text-amber"><?= str_repeat('★', (int) $rev['rating']) ?></span>
                    </div>
                    <div class="identity-email"><?= esc($rev['designation']) ?> — "<?= esc($rev['review_text']) ?>"</div>
                </div>
                <button type="button" class="btn-action btn-action-danger js-del-db-item" data-id="<?= $rev['id'] ?>" data-url="<?= base_url('addons/page-builder/delete-review') ?>">✕</button>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 2. Unlimited Collapsible FAQs DB Manager -->
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">DYNAMIC ACCORDION DATA // `cms_faqs`</span>
                <h3 class="panel-title">Manage Unlimited FAQs (<?= count($faqs ?? []) ?>)</h3>
            </div>
            <span class="req-badge">DB DRIVEN</span>
        </div>

        <form id="pb-add-faq-form" action="<?= base_url('addons/page-builder/save-faq') ?>" method="post" style="margin-bottom: 1.25rem;">
            <?= csrf_field() ?>
            <div class="grid-2-col">
                <div class="field-group">
                    <input type="text" name="question" class="field-input" placeholder="FAQ Question..." required>
                </div>
                <div class="field-group">
                    <input type="text" name="category" class="field-input" placeholder="Category (e.g. General, Pricing)" value="General">
                </div>
            </div>
            <div class="field-group" style="display:flex; gap:0.6rem;">
                <input type="text" name="answer" class="field-input" placeholder="Detailed FAQ Answer..." required>
                <button type="submit" class="btn-primary btn-sm" style="flex-shrink:0;">+ Add FAQ</button>
            </div>
        </form>

        <div class="telemetry-list" style="max-height: 260px; overflow-y: auto;">
            <?php foreach (($faqs ?? []) as $fq): ?>
            <div class="telemetry-item">
                <div>
                    <div class="identity-name">
                        <span class="role-badge role-admin"><?= esc($fq['category']) ?></span>
                        <span><?= esc($fq['question']) ?></span>
                    </div>
                    <div class="identity-email"><?= esc($fq['answer']) ?></div>
                </div>
                <button type="button" class="btn-action btn-action-danger js-del-db-item" data-id="<?= $fq['id'] ?>" data-url="<?= base_url('addons/page-builder/delete-faq') ?>">✕</button>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>
<?= $this->endSection() ?>