<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $en        = $editNode ?? null;
    $editId    = (int) ($en['id'] ?? 0);
    $parentId  = (int) ($en['parent_id'] ?? ($defaultParentId ?? 0));
    $isLeaf    = (int) ($en['is_leaf_node'] ?? 1);
    $sortOrder = (int) ($en['sort_order'] ?? 10);
    $allowDemo = isset($en['allow_demo_booking']) ? (int) $en['allow_demo_booking'] : 1;
    $descHtml  = $en['description_html'] ?? '<p>Write curriculum overview, learning outcomes, and key highlights here...</p>';
?>
<div class="lhr-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card">
        <div class="card-head lhr-flex-between">
            <div>
                <h3><?= $editId > 0 ? ('Edit Course #' . $editId . ': ' . esc($en['title'])) : 'Create New Course or Program Level' ?></h3>
            </div>
            <?php if ($editId > 0): ?>
                <div class="lhr-action-bar">
                    <button type="button" class="lhr-btn lhr-btn-cyan lhr-btn-sm js-lhr-row-copy-sc" data-node-id="<?= $editId ?>">📋 Copy Split Shortcode (#<?= $editId ?>)</button>
                    <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm">+ Create New Course</a>
                </div>
            <?php endif; ?>
        </div>

        <form action="<?= base_url('addons/lms-hierarchy/save-node') ?>" method="post" enctype="multipart/form-data" id="js-lhr-node-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editId ?>">

            <div class="lhr-form-grid-3">
                <div class="field-group">
                    <label class="field-label"><span>Parent Program / Category</span></label>
                    <select name="parent_id" class="field-select">
                        <?php foreach (($branchParentsMap ?? []) as $pid => $pLabel): ?>
                            <?php if ($editId > 0 && (int) $pid === $editId) continue; ?>
                            <option value="<?= (int) $pid ?>" <?= $parentId === (int) $pid ? 'selected' : '' ?>><?= esc($pLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Course / Level Title *</span></label>
                    <input type="text" name="title" class="field-input" value="<?= esc($en['title'] ?? '') ?>" placeholder="e.g. Abacus Level 1 — Foundation" required>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Display Order</span></label>
                    <input type="number" name="sort_order" class="field-input mono-input" value="<?= $sortOrder ?>" step="1">
                </div>
            </div>

            <div class="lhr-form-grid-2">
                <div class="field-group">
                    <label class="field-label"><span class="text-emerald">Cover Image — Upload from Computer</span></label>
                    <input type="file" name="thumbnail_file" class="field-input" accept="image/*">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Or Cover Image URL</span></label>
                    <input type="text" name="thumbnail_url" class="field-input mono-input" value="<?= esc($en['thumbnail_url'] ?? '') ?>" placeholder="uploads/lms_hierarchy/... or https://...">
                    <?php if (! empty($en['resolved_thumbnail'])): ?>
                        <div style="margin-top:0.35rem; display:flex; align-items:center; gap:0.5rem;">
                            <img src="<?= esc($en['resolved_thumbnail']) ?>" alt="Preview" style="height:34px; border-radius:6px;" loading="lazy">
                            <span class="mono-cell text-emerald">Current Cover Image Active</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Short Summary (Shown on Split/Grid Product Cards)</span></label>
                <input type="text" name="short_summary" class="field-input" value="<?= esc($en['short_summary'] ?? '') ?>" placeholder="1-2 line description shown on course & product cards...">
            </div>

            <div class="field-group">
                <div class="lhr-flex-between" style="margin-bottom:0.4rem;">
                    <label class="field-label" style="margin:0;"><span>Full Course Details &amp; Curriculum (Advanced Rich Text Studio)</span></label>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm" id="js-lhr-rte-source-toggle">&lt;/&gt; HTML Source</button>
                </div>
                <div class="rte-toolbar lhr-adv-rte-bar" id="js-lhr-rte-toolbar" style="display:flex;flex-wrap:wrap;gap:0.35rem;padding:0.55rem;background:var(--bg-canvas);border:1px solid var(--border-subtle);border-bottom:none;border-radius:10px 10px 0 0;">
                    <select class="field-select js-lhr-rte-select" data-cmd="formatBlock" style="width:auto;height:30px;padding:0 0.45rem;font-size:0.76rem;">
                        <option value="P">Paragraph</option>
                        <option value="H1">Heading 1 (H1)</option>
                        <option value="H2">Heading 2 (H2)</option>
                        <option value="H3">Heading 3 (H3)</option>
                        <option value="H4">Heading 4 (H4)</option>
                        <option value="BLOCKQUOTE">Quote Block</option>
                    </select>
                    <select class="field-select js-lhr-rte-select" data-cmd="fontSize" style="width:auto;height:30px;padding:0 0.45rem;font-size:0.76rem;">
                        <option value="3">Normal Size</option>
                        <option value="2">Small</option>
                        <option value="4">Medium-Large</option>
                        <option value="5">Large</option>
                        <option value="6">Extra Large</option>
                    </select>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="bold" title="Bold"><b>B</b></button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="italic" title="Italic"><i>I</i></button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="underline" title="Underline"><u>U</u></button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="strikeThrough" title="Strikethrough"><s>S</s></button>
                    <label class="lhr-btn lhr-btn-outline lhr-btn-sm" title="Text Color" style="cursor:pointer;gap:0.25rem;">
                        <span>A</span>
                        <input type="color" class="js-lhr-rte-color" data-cmd="foreColor" value="#10b981" style="width:18px;height:18px;border:none;padding:0;background:transparent;cursor:pointer;">
                    </label>
                    <label class="lhr-btn lhr-btn-outline lhr-btn-sm" title="Highlight Background" style="cursor:pointer;gap:0.25rem;">
                        <span>🖍</span>
                        <input type="color" class="js-lhr-rte-color" data-cmd="hiliteColor" value="#fef08a" style="width:18px;height:18px;border:none;padding:0;background:transparent;cursor:pointer;">
                    </label>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="justifyLeft" title="Align Left">⇤</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="justifyCenter" title="Align Center">⇔</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="justifyRight" title="Align Right">⇥</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="insertUnorderedList" title="Bullet List">• List</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="insertOrderedList" title="Numbered List">1. List</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="createLink" title="Insert Link">🔗 Link</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-custom" data-action="image" title="Insert Image URL">🖼 Image</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-custom" data-action="table" title="Insert Curriculum Table">⊞ Table</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-custom" data-action="callout" title="Insert Highlight Box">✨ Callout</button>
                    <button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-rte-cmd" data-cmd="removeFormat" title="Clear Formatting">⌫ Clear</button>
                </div>
                <div class="rte-editable" id="js-lhr-rte-editor" contenteditable="true" style="min-height:260px;padding:1rem;border:1px solid var(--border-subtle);border-radius:0 0 10px 10px;background:var(--bg-canvas);color:var(--text-primary);line-height:1.65;outline:none;"><?= $descHtml ?></div>
                <textarea name="description_html" id="js-lhr-desc-input" class="field-input mono-input is-hidden" rows="10" style="min-height:260px;border-radius:0 0 10px 10px;"><?= esc($descHtml) ?></textarea>
            </div>

            <div class="panel-card" style="margin: 1.15rem 0; border-color: rgba(16, 185, 129, 0.35);">
                <div class="lhr-form-grid-3" style="align-items:center;">
                    <label style="display:flex; align-items:center; gap:0.65rem; cursor:pointer;">
                        <input type="checkbox" name="is_leaf_node" value="1" <?= $isLeaf === 1 ? 'checked' : '' ?>>
                        <div>
                            <strong class="text-emerald">🎓 Enrollable Product / Course</strong>
                            <div class="field-hint">Uncheck only if this is a parent folder containing sub-levels.</div>
                        </div>
                    </label>

                    <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                        <input type="checkbox" name="allow_demo_booking" value="1" <?= $allowDemo === 1 ? 'checked' : '' ?>>
                        <div>
                            <strong class="text-cyan">🎯 Enable "Book Free Demo" Button</strong>
                            <div class="field-hint">If unchecked, only the single "Enroll Now" button will appear.</div>
                        </div>
                    </label>

                    <div class="field-group" style="margin:0;">
                        <label class="field-label"><span>Demo Button Text</span></label>
                        <input type="text" name="demo_note" class="field-input" value="<?= esc($en['demo_note'] ?? 'Book Free Live Demo') ?>">
                    </div>
                </div>
            </div>

            <div class="lhr-action-bar">
                <button type="submit" class="lhr-btn lhr-btn-emerald lhr-btn-lg">💾 Save Course</button>
                <a href="<?= base_url('addons/lms-hierarchy') ?>" class="lhr-btn lhr-btn-outline lhr-btn-lg">← Back to Courses</a>
            </div>
        </form>
    </section>
</div>
<script type="application/ld+json">
<?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'Course',
    'name'        => ($cNode['title'] ?? ($en['title'] ?? 'Course')),
    'description' => ($cNode['short_summary'] ?? ($en['short_summary'] ?? '')) ?: strip_tags((string) ($cNode['description_html'] ?? ($en['description_html'] ?? ''))),
    'provider'    => [
        '@type' => 'Organization',
        'name'  => $settings['site_title'] ?? 'MomoMinds Academy',
        'sameAs'=> base_url('/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<?= $this->endSection() ?>
