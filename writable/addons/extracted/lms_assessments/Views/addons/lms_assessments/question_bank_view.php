<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="las-main-grid las-section-gap">
        <section class="console-card">
            <div class="card-head las-flex-between">
                <div>
                    <h3 id="js-qbank-form-title">Add Question to Course Bank</h3>
                    <p class="field-hint" style="margin:0;">Add questions one by one to any course level.</p>
                </div>
                <button type="button" class="btn-action is-hidden" id="js-qbank-cancel-edit">✕ Reset</button>
            </div>

            <form action="<?= base_url('addons/lms-assessments/save-question') ?>" method="post" id="js-qbank-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="qb_id" value="0">

                <div class="field-group">
                    <label class="field-label"><span>Select Course / Level *</span></label>
                    <select name="node_id" id="qb_node_id" class="field-select" required>
                        <?php foreach (($leafNodesMap ?? []) as $nid => $nTitle): ?>
                            <option value="<?= (int) $nid ?>" <?= (int) ($defaultAddNodeId ?? 2) === (int) $nid ? 'selected' : '' ?>>
                                <?= esc($nTitle) ?> (<?= (int) ($qbankCountsMap[$nid] ?? 0) ?> Qs)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span>Format</span></label>
                        <select name="question_type" id="qb_type" class="field-select">
                            <option value="mcq">Multiple Choice (4 Options)</option>
                            <option value="input">Direct Answer Input</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Difficulty</span></label>
                        <select name="difficulty" id="qb_difficulty" class="field-select">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Topic Tag</span></label>
                        <input type="text" name="topic_tag" id="qb_topic" class="field-input" placeholder="e.g. Small Friends">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Question / Math Sum *</span></label>
                    <textarea name="question_text" id="qb_text" class="field-input mono-input" rows="2" placeholder="e.g. 45 + 38 - 19 = ?" required></textarea>
                </div>

                <div id="js-qb-mcq-options-box">
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Option A</span></label>
                            <input type="text" name="option_a" id="qb_opt_a" class="field-input mono-input" placeholder="e.g. 62">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Option B</span></label>
                            <input type="text" name="option_b" id="qb_opt_b" class="field-input mono-input" placeholder="e.g. 64">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Option C</span></label>
                            <input type="text" name="option_c" id="qb_opt_c" class="field-input mono-input" placeholder="e.g. 66">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Option D</span></label>
                            <input type="text" name="option_d" id="qb_opt_d" class="field-input mono-input" placeholder="e.g. 68">
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span class="text-emerald">Correct Answer * (Value or A/B/C/D)</span></label>
                    <input type="text" name="correct_answer" id="qb_correct" class="field-input mono-input" placeholder="e.g. 64 (or B)" required>
                </div>

                <button type="submit" class="btn-primary btn-full">➕ Save Question to Bank</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head las-flex-between">
                <div>
                    <h3>Course Questions (<?= count($questionBank ?? []) ?>)</h3>
                </div>
                <form method="get" action="<?= base_url('addons/lms-assessments/question-bank') ?>">
                    <select name="filter_node_id" class="field-select las-select-xs" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        <?php foreach (($leafNodesMap ?? []) as $nid => $nTitle): ?>
                            <option value="<?= (int) $nid ?>" <?= (int) ($selectedFilterNodeId ?? -1) === (int) $nid ? 'selected' : '' ?>>
                                <?= esc($nTitle) ?> (<?= (int) ($qbankCountsMap[$nid] ?? 0) ?> Qs)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="table-responsive" style="max-height: 540px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Question &amp; Choices</th>
                            <th>Answer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($questionBank)): ?>
                            <tr><td colspan="4" class="muted-cell">No questions in this course bank yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($questionBank as $qRow): ?>
                                <tr>
                                    <td>
                                        <div class="mono-cell text-emerald"><?= esc($qRow['node_title']) ?></div>
                                        <span class="role-badge"><?= strtoupper(esc($qRow['difficulty'] ?? 'MEDIUM')) ?></span>
                                    </td>
                                    <td>
                                        <div class="identity-name"><?= esc($qRow['question_text']) ?></div>
                                        <?php if (! empty($qRow['options'])): ?>
                                            <div class="mono-cell muted-cell">Choices: <?= esc(implode(' | ', $qRow['options'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="mono-cell text-emerald"><strong>✓ <?= esc($qRow['correct_answer']) ?></strong></td>
                                    <td>
                                        <div class="action-btn-group">
                                            <button type="button" class="btn-action js-edit-qbank" data-q="<?= esc(json_encode($qRow), 'attr') ?>">✎ Edit</button>
                                            <?php if (! empty($canDelete)): ?>
                                                <form action="<?= base_url('addons/lms-assessments/delete-question') ?>" method="post" class="las-inline-form" onsubmit="return confirm('Delete this question?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $qRow['id'] ?>">
                                                    <button type="submit" class="btn-action text-danger">✕</button>
                                                </form>
                                            <?php endif; ?>
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
</div>
<?= $this->endSection() ?>
