<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="las-main-grid las-section-gap">
        <section class="console-card">
            <div class="card-head las-flex-between">
                <div>
                    <h3 id="js-las-ws-form-title">Upload Course PDF Worksheet</h3>
                </div>
                <button type="button" class="btn-action is-hidden" id="js-las-cancel-ws-edit">✕ Reset</button>
            </div>
            <form action="<?= base_url('addons/lms-assessments/save-worksheet') ?>" method="post" enctype="multipart/form-data" id="js-las-ws-builder-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="las_ws_id" value="0">

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Select Course *</span></label>
                        <select name="node_id" id="las_ws_node" class="field-select" required>
                            <?php foreach (($leafNodesMap ?? []) as $nid => $nTitle): ?>
                                <option value="<?= (int) $nid ?>"><?= esc($nTitle) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Resource Category</span></label>
                        <select name="worksheet_type" id="las_ws_type" class="field-select">
                            <option value="PDF Practice Sheet">📄 PDF Practice Sheet</option>
                            <option value="Level Workbook">📘 Level Workbook</option>
                            <option value="Formula Guide">🧮 Formula &amp; Trick Guide</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Worksheet Title *</span></label>
                    <input type="text" name="title" id="las_ws_title" class="field-input" placeholder="e.g. Level 1 — 100 Sums Practice Sheet (Week 1)" required>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span class="text-emerald">Upload PDF from Computer</span></label>
                        <input type="file" name="worksheet_pdf" class="field-input" accept=".pdf,.zip,.png,.jpg,.jpeg,.doc,.docx">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Or External PDF Link</span></label>
                        <input type="text" name="file_url" id="las_ws_url" class="field-input mono-input" placeholder="https://.../worksheet.pdf">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Access After Level Promotion</span></label>
                        <select name="retention_mode" id="las_ws_retention" class="field-select">
                            <option value="permanent_after_upgrade">♾ Keep Downloadable After Promotion</option>
                            <option value="expire_on_batch_change">⏳ Hide After Promotion to Next Level</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Instructions for Students</span></label>
                        <input type="text" name="instructions" id="las_ws_instr" class="field-input" placeholder="Solve within 8 minutes...">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">📤 Save &amp; Publish Worksheet</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Published Course Worksheets (<?= count($worksheets ?? []) ?>)</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worksheet &amp; Course</th>
                            <th>File</th>
                            <th>Retention</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($worksheets ?? []) as $ws): ?>
                            <?php $wsEnabled = ((int) ($ws['is_enabled'] ?? 1) === 1); ?>
                            <tr>
                                <td>
                                    <div class="identity-name"><?= esc($ws['title']) ?></div>
                                    <div class="mono-cell text-emerald"><?= esc($ws['node_title']) ?></div>
                                </td>
                                <td>
                                    <a href="<?= esc($ws['resolved_dl_url']) ?>" target="_blank" class="mono-cell text-cyan">↗ Download PDF</a>
                                </td>
                                <td>
                                    <span class="role-badge">
                                        <?= ($ws['retention_mode'] ?? '') === 'expire_on_batch_change' ? 'Current Level Only' : 'Permanent Access' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <button type="button" class="btn-action js-las-edit-ws" data-ws="<?= esc(json_encode($ws), 'attr') ?>">✎ Edit</button>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-assessments/delete-worksheet') ?>" method="post" class="las-inline-form" onsubmit="return confirm('Delete this worksheet?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $ws['id'] ?>">
                                                <button type="submit" class="btn-action text-danger">✕</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
