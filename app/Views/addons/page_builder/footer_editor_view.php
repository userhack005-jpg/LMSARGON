<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div id="pb-editor-toast" class="alert-banner is-hidden" role="alert"></div>

<form id="pb-footer-form" action="<?= base_url('addons/page-builder/save-footer') ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="footer_id" value="<?= esc((string) ($current['id'] ?? 1)) ?>">
    <input type="hidden" name="current_active" value="<?= esc((string) ($current['is_active'] ?? 0)) ?>">
    <textarea name="sections_json" id="pb_footer_sections_json" class="is-hidden"><?= esc($current['sections_json'] ?? '[[],[],[]]') ?></textarea>

    <!-- TOP BAR: Select Pre-built Footer Style & Column Count -->
    <section class="panel-card" style="margin-bottom: 1.25rem;">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">MULTI-SECTION FOOTER ARCHITECT</span>
                <h3 class="panel-title">Customize & Activate Website Footer</h3>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">← Back to Pages</a>
                <button type="submit" id="btn-save-footer" class="btn-primary btn-sm">
                    <span id="btn-save-footer-text">Save Footer Template ✓</span>
                </button>
            </div>
        </div>

       <!-- 4 Pre-Built Template Switcher Links (No JS preventDefault blocking!) -->
        <div class="filter-bar" style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-subtle);">
            <?php foreach ($footers as $f): ?>
                <a href="<?= base_url('addons/page-builder/footer?template_id=' . $f['id']) ?>"
                   class="filter-pill <?= ((int) $f['id'] === (int) $current['id']) ? 'is-active' : '' ?>"
                   style="text-decoration:none; padding: 0.55rem 0.95rem; display:inline-flex; align-items:center; gap:0.5rem;">
                    <?php if ((int) $f['is_active'] === 1): ?>
                        <span class="req-badge">★ LIVE</span>
                    <?php else: ?>
                        <span class="opt-badge">TEMPLATE</span>
                    <?php endif; ?>
                    <span><?= esc($f['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="grid-3-col">
          <div class="field-group">
                <label class="field-label"><span>Footer Layout Sections (1 / 2 / 3 / 4 Columns)</span></label>
                <select name="columns_count" id="pb_footer_cols" class="field-select">
                    <?php $cols = (int) ($current['columns_count'] ?? 3); ?>
                    <option value="1" <?= $cols === 1 ? 'selected' : '' ?>>1 Section (100% Single Full-Width Column)</option>
                    <option value="2" <?= $cols === 2 ? 'selected' : '' ?>>2 Sections (50% / 50% Split)</option>
                    <option value="3" <?= $cols === 3 ? 'selected' : '' ?>>3 Sections (33% / 33% / 33% Grid)</option>
                    <option value="4" <?= $cols === 4 ? 'selected' : '' ?>>4 Sections (25% Enterprise Matrix)</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label"><span>Bottom Copyright Text</span></label>
                <input type="text" name="copyright_text" class="field-input" value="<?= esc($current['copyright_text'] ?? '') ?>">
            </div>

            <div class="field-group" style="display:flex; align-items:center; padding-top:1.45rem;">
                <label style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer; font-weight:600; color:var(--accent-emerald);">
                    <input type="checkbox" name="set_active" value="1" <?= ((int) ($current['is_active'] ?? 0) === 1) ? 'checked' : '' ?>>
                    <span>Activate this Footer Template on Live Website</span>
                </label>
            </div>
        </div>
    </section>

    <!-- SPLIT WORKSPACE: Left Content Blocks Palette + Right Multi-Column Footer Dropzones -->
    <div class="rbac-split-grid">
        <aside class="panel-card">
            <div class="panel-header">
                <div>
                    <span class="mono-label">AVAILABLE CONTENT BLOCKS</span>
                    <h3 class="panel-title">Drag or Click to Add</h3>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Target Section for "+ Add" Click</span></label>
                <select id="pb_target_footer_col" class="field-select">
                    <option value="0">Section / Column 01</option>
                    <option value="1">Section / Column 02</option>
                    <option value="2">Section / Column 03</option>
                    <option value="3">Section / Column 04</option>
                </select>
            </div>

            <div class="pb-palette-list">
                <?php foreach ($components as $comp): ?>
                <button type="button"
                        class="pb-palette-item js-pb-add-block"
                        draggable="true"
                        data-comp-key="<?= esc($comp['component_key']) ?>"
                        data-comp-name="<?= esc($comp['component_name']) ?>"
                        data-comp-schema="<?= esc($comp['default_schema_json']) ?>">
                    <span class="pb-palette-icon"><?= esc($comp['component_icon']) ?></span>
                    <div class="pb-palette-meta">
                        <strong><?= esc($comp['component_name']) ?></strong>
                        <span>DRAG TO ANY SECTION</span>
                    </div>
                    <span class="pb-palette-plus">+ Add</span>
                </button>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="panel-card">
            <div class="panel-header">
                <div>
                    <span class="mono-label">FOOTER SECTION DROPZONES</span>
                    <h3 class="panel-title">Drag Blocks into Section 1, 2, 3, or 4</h3>
                </div>
            </div>

            <div id="pb-footer-columns-wrap" class="pb-footer-builder-grid"></div>
        </section>
    </div>
</form>
<?= $this->endSection() ?>