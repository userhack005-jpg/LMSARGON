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
                <h3><?= $editId > 0 ? ('Edit Course: ' . esc($en['title'])) : 'Create New Course or Program Level' ?></h3>
            </div>
            <?php if ($editId > 0): ?>
                <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="btn-secondary btn-sm">+ Create New Course</a>
            <?php endif; ?>
        </div>

        <form action="<?= base_url('addons/lms-hierarchy/save-node') ?>" method="post" enctype="multipart/form-data" id="js-lhr-node-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editId ?>">

            <div class="grid-3-col">
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

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span class="text-emerald">Cover Image — Upload from Computer</span></label>
                    <input type="file" name="thumbnail_file" class="field-input" accept="image/*">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Or Cover Image URL</span></label>
                    <input type="text" name="thumbnail_url" class="field-input mono-input" value="<?= esc($en['thumbnail_url'] ?? '') ?>" placeholder="uploads/lms_hierarchy/... or https://...">
                    <?php if (! empty($en['resolved_thumbnail'])): ?>
                        <div style="margin-top:0.35rem; display:flex; align-items:center; gap:0.5rem;">
                            <img src="<?= esc($en['resolved_thumbnail']) ?>" alt="Preview" style="height:34px; border-radius:6px;">
                            <span class="mono-cell text-emerald">Current Cover Image Active</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Short Summary (Shown on Course Cards)</span></label>
                <input type="text" name="short_summary" class="field-input" value="<?= esc($en['short_summary'] ?? '') ?>" placeholder="1-line description shown on course cards...">
            </div>

            <div class="field-group">
                <label class="field-label"><span>Full Course Details &amp; Curriculum (Rich Text Editor)</span></label>
                <div class="rte-toolbar" id="js-lhr-rte-toolbar">
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="bold"><b>B</b></button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="italic"><i>I</i></button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="underline"><u>U</u></button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="formatBlock" data-val="H2">H2</button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="formatBlock" data-val="H3">H3</button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="formatBlock" data-val="P">Paragraph</button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="insertUnorderedList">• Bullet List</button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="insertOrderedList">1. Numbered List</button>
                    <button type="button" class="rte-btn js-lhr-rte-cmd" data-cmd="createLink">🔗 Link</button>
                </div>
                <div class="rte-editable" id="js-lhr-rte-editor" contenteditable="true"><?= $descHtml ?></div>
                <textarea name="description_html" id="js-lhr-desc-input" class="is-hidden"><?= esc($descHtml) ?></textarea>
            </div>

            <div class="panel-card" style="margin: 1.15rem 0; border-color: rgba(16, 185, 129, 0.35);">
                <div class="grid-3-col" style="align-items:center;">
                    <label style="display:flex; align-items:center; gap:0.65rem; cursor:pointer;">
                        <input type="checkbox" name="is_leaf_node" value="1" <?= $isLeaf === 1 ? 'checked' : '' ?>>
                        <div>
                            <strong class="text-emerald">🎓 Enrollable Course (Has Fee Plans)</strong>
                            <div class="field-hint">Uncheck only if this is a parent category folder that contains sub-levels.</div>
                        </div>
                    </label>

                    <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                        <input type="checkbox" name="allow_demo_booking" value="1" <?= $allowDemo === 1 ? 'checked' : '' ?>>
                        <div>
                            <strong class="text-cyan">🎯 Enable "Book Free Demo" Button</strong>
                            <div class="field-hint">Shows Demo Booking CTA on the Course Page.</div>
                        </div>
                    </label>

                    <div class="field-group" style="margin:0;">
                        <label class="field-label"><span>Demo Button Text</span></label>
                        <input type="text" name="demo_note" class="field-input" value="<?= esc($en['demo_note'] ?? 'Book Free Live Demo') ?>">
                    </div>
                </div>
            </div>

            <div class="action-btn-group">
                <button type="submit" class="btn-primary">💾 Save Course</button>
                <a href="<?= base_url('addons/lms-hierarchy') ?>" class="btn-secondary">← Back to Courses</a>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
