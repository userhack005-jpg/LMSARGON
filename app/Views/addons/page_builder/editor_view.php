<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="pb-editor-toast" class="alert-banner is-hidden" role="alert"></div>

<form id="pb-save-form"
      action="<?= base_url('addons/page-builder/save-page') ?>"
      method="post"
      data-upload-url="<?= base_url('addons/page-builder/upload-media') ?>"
      novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="page_id" id="pb_page_id" value="<?= esc((string) $page['id']) ?>">
    <textarea name="blocks_json" id="pb_blocks_json" class="is-hidden"><?= esc($page['blocks_json'] ?: '[]') ?></textarea>

    <!-- TOP ACTION BAR -->
    <section class="panel-card" style="margin-bottom: 1.25rem;">
        <div class="panel-header panel-header-wrap" style="margin-bottom: 1rem;">
            <div>
                <span class="mono-label">VISUAL DRAG & DROP PAGE BUILDER</span>
                <h3 class="panel-title"><?= $page['id'] > 0 ? 'Editing: ' . esc($page['title']) : 'New Unrestricted Dynamic Page' ?></h3>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">← All Pages</a>
                <?php if (! empty($page['slug'])): ?>
                    <a href="<?= base_url(esc($page['slug'])) ?>" target="_blank" id="pb-live-link" class="btn-secondary btn-sm">↗ View /<?= esc($page['slug']) ?></a>
                <?php endif; ?>
                <button type="submit" id="btn-pb-save" class="btn-primary btn-sm">
                    <span id="btn-pb-save-text">Save & Publish Page ✓</span>
                </button>
            </div>
        </div>

        <!-- Page Title, Custom Slug & Options -->
        <div class="grid-3-col">
            <div class="field-group">
                <label class="field-label"><span>Page Title</span><span class="req-badge">REQUIRED</span></label>
                <input type="text" name="title" id="pb_title" class="field-input" value="<?= esc($page['title']) ?>" placeholder="e.g., About Us" required>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Custom URL Slug (Dynamic URL)</span><span class="req-badge">CUSTOMIZABLE</span></label>
                <input type="text" name="slug" id="pb_slug" class="field-input mono-input" value="<?= esc($page['slug']) ?>" placeholder="about-us">
                <span class="field-hint">Live URL: <strong><?= base_url() ?><span id="slug-preview-text"><?= esc($page['slug'] ?: 'about-us') ?></span></strong></span>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Publish Status</span></label>
                <select name="status" class="field-select">
                    <option value="published" <?= ($page['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                </select>
            </div>
        </div>

        <div class="grid-3-col">
            <div class="field-group">
                <label class="field-label"><span>SEO Meta Title (Optional)</span></label>
                <input type="text" name="meta_title" class="field-input" value="<?= esc($page['meta_title'] ?? '') ?>" placeholder="Custom browser title">
            </div>
            <div class="field-group">
                <label class="field-label"><span>SEO Meta Description (Optional)</span></label>
                <input type="text" name="meta_description" class="field-input" value="<?= esc($page['meta_description'] ?? '') ?>" placeholder="Summary for Google search">
            </div>
            <div class="field-group" style="display:flex; align-items:center; gap:1.25rem; padding-top:1.4rem;">
                <label style="display:inline-flex; align-items:center; gap:0.45rem; cursor:pointer; font-size:0.84rem;">
                    <input type="checkbox" name="is_homepage" value="1" <?= ((int) ($page['is_homepage'] ?? 0) === 1) ? 'checked' : '' ?>>
                    <span>Set as Root Homepage (`/`)</span>
                </label>
                <label style="display:inline-flex; align-items:center; gap:0.45rem; cursor:pointer; font-size:0.84rem;">
                    <input type="checkbox" name="show_in_nav" value="1" <?= ((int) ($page['show_in_nav'] ?? 1) === 1) ? 'checked' : '' ?>>
                    <span>Show in Top Navbar</span>
                </label>
            </div>
        </div>
    </section>

    <!-- BUILDER SPLIT WORKSPACE: Left Palette (DB Components) + Right Drag & Drop Canvas -->
    <div class="rbac-split-grid">

        <!-- LEFT PALETTE: Click or Drag to Add Component -->
        <aside class="panel-card">
            <div class="panel-header">
                <div>
                    <span class="mono-label">COMPONENT PALETTE</span>
                    <h3 class="panel-title">Add Content Blocks</h3>
                </div>
                <span class="req-badge"><?= count($components) ?> ACTIVE</span>
            </div>

            <p class="field-hint" style="margin-bottom: 0.9rem;">
                Click any component below (or drag it) to append it to the visual page canvas:
            </p>

            <div class="pb-palette-list">
                <?php foreach ($components as$comp): ?>
                <button type="button"
                        class="pb-palette-item js-pb-add-block"
                        draggable="true"
                        data-comp-key="<?= esc($comp['component_key']) ?>"
                        data-comp-name="<?= esc($comp['component_name']) ?>"
                        data-comp-schema="<?= esc($comp['default_schema_json']) ?>">
                    <span class="pb-palette-icon"><?= esc($comp['component_icon']) ?></span>
                    <div class="pb-palette-meta">
                        <strong><?= esc($comp['component_name']) ?></strong>
                        <span><?= esc(strtoupper($comp['category'])) ?></span>
                    </div>
                    <span class="pb-palette-plus">+ Add</span>
                </button>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- RIGHT CANVAS: Live Reorderable Block Stack -->
        <section class="panel-card">
            <div class="panel-header">
                <div>
                    <span class="mono-label">LIVE PAGE CANVAS (DRAG ↑↓ TO REORDER)</span>
                    <h3 class="panel-title">Page Structure & Rich Content Blocks</h3>
                </div>
                <button type="button" id="btn-pb-clear-all" class="btn-secondary btn-sm">Clear Canvas</button>
            </div>

            <!-- Dynamic Blocks Render Here via JS -->
            <div id="pb-canvas-dropzone" class="pb-canvas-container"></div>
        </section>

    </div>
</form>
<?= $this->endSection() ?>