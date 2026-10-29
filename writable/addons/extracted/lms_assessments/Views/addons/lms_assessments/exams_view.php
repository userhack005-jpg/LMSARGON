<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="las-main-grid las-section-gap">
        <section class="console-card">
            <div class="card-head las-flex-between">
                <div>
                    <h3 id="js-las-exam-form-title">Create Exam or Practice Drill</h3>
                </div>
                <button type="button" class="btn-action is-hidden" id="js-las-cancel-exam-edit">✕ Reset</button>
            </div>
            <form action="<?= base_url('addons/lms-assessments/save-exam') ?>" method="post" id="js-las-exam-builder-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="las_exam_id" value="0">

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Select Course *</span></label>
                        <select name="node_id" id="las_exam_node" class="field-select" required>
                            <?php foreach (($leafNodesMap ?? []) as $nid => $nTitle): ?>
                                <option value="<?= (int) $nid ?>">
                                    <?= esc($nTitle) ?> (Bank: <?= (int) ($qbankCountsMap[$nid] ?? 0) ?> Qs)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Assessment Type *</span></label>
                        <select name="exam_type" id="las_exam_type" class="field-select">
                            <option value="practice_test">⚡ Unlimited Rotating Practice Drill</option>
                            <option value="weekly_exam">📅 Weekly Assessment</option>
                            <option value="monthly_exam">📊 Monthly Milestone Test</option>
                            <option value="final_exam">🏆 Final Certification Exam</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Assessment Title *</span></label>
                    <input type="text" name="title" id="las_exam_title" class="field-input" placeholder="e.g. Level 1 — Speed Practice Set (10 Questions)" required>
                </div>

                <div class="panel-card" style="margin-bottom: 0.95rem; border-color: rgba(16, 185, 129, 0.4);">
                    <div class="grid-2-col">
                        <div class="field-group" style="margin-bottom:0;">
                            <label class="field-label"><span>Question Selection Mode *</span></label>
                            <select name="selection_mode" id="las_selection_mode" class="field-select">
                                <option value="random_from_bank">🎲 Randomize from Course Question Bank</option>
                                <option value="manual_selected">☑️ Manually Pick Specific Questions</option>
                            </select>
                        </div>
                        <div class="field-group" id="js-las-qcount-wrap" style="margin-bottom:0;">
                            <label class="field-label"><span>Questions per Set *</span></label>
                            <input type="number" name="question_count" id="las_question_count" class="field-input mono-input" value="10" min="1" max="200" required>
                        </div>
                    </div>

                    <div id="js-las-manual-qpicker" class="is-hidden" style="margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px dashed var(--border-subtle);">
                        <div class="las-qpicker-scroll">
                            <?php foreach (($questionBank ?? []) as $qb): ?>
                                <label class="las-qpicker-item js-qpicker-row" data-node-id="<?= (int) $qb['node_id'] ?>">
                                    <input type="checkbox" name="selected_qids[]" value="<?= (int) $qb['id'] ?>" class="js-las-qid-cb">
                                    <div>
                                        <strong><?= esc($qb['question_text']) ?></strong>
                                        <span class="mono-cell text-emerald"> (Ans: <?= esc($qb['correct_answer']) ?>)</span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Availability Rule</span></label>
                        <select name="unlock_mode" id="las_unlock_mode" class="field-select">
                            <option value="always_open">🔓 Always Open for Enrolled Students</option>
                            <option value="after_class_session">⏱ Unlock After Specific Live Class Completes</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Prerequisite Live Class</span></label>
                        <select name="unlock_after_session_id" id="las_unlock_session" class="field-select">
                            <option value="0">-- None --</option>
                            <?php foreach (($sessionsMap ?? []) as $sid => $sInfo): ?>
                                <option value="<?= (int) $sid ?>"><?= esc($sInfo['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span>Time Limit (Minutes) *</span></label>
                        <input type="number" name="duration_minutes" id="las_exam_dur" class="field-input mono-input" value="5" min="1" max="180" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Pass Score (%) *</span></label>
                        <input type="number" step="0.1" name="pass_percentage" id="las_exam_pass" class="field-input mono-input" value="75" min="1" max="100" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Certificate</span></label>
                        <label class="las-check-label" style="margin-top:0.55rem;">
                            <input type="checkbox" name="award_certificate" id="las_exam_cert" value="1" checked>
                            <span>🏆 Issue Certificate on Pass</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">💾 Save &amp; Publish Assessment</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Published Exams &amp; Practice Drills (<?= count($exams ?? []) ?>)</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Assessment &amp; Course</th>
                            <th>Questions &amp; Mode</th>
                            <th>Timer &amp; Pass</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($exams ?? []) as $ex): ?>
                            <?php
                                $enabled = ((int) ($ex['is_enabled'] ?? 1) === 1);
                                $sMode   = $ex['selection_mode'] ?? 'random_from_bank';
                            ?>
                            <tr>
                                <td>
                                    <div class="identity-name"><?= esc($ex['title']) ?></div>
                                    <div class="mono-cell text-emerald"><?= esc($ex['node_title']) ?></div>
                                </td>
                                <td>
                                    <span class="role-badge">
                                        <?= $sMode === 'manual_selected' ? '☑️ Fixed Set' : '🎲 Randomized' ?>
                                    </span>
                                    <div class="mono-cell text-cyan"><?= (int) ($ex['question_count'] ?? 5) ?> Qs per attempt</div>
                                </td>
                                <td class="mono-cell">
                                    ⏱ <?= (int) $ex['duration_minutes'] ?>m (Pass: <?= number_format((float) $ex['pass_percentage'], 0) ?>%)
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <button type="button" class="btn-action js-las-edit-exam" data-exam="<?= esc(json_encode($ex), 'attr') ?>">✎ Edit</button>
                                        <form action="<?= base_url('addons/lms-assessments/toggle-exam') ?>" method="post" class="las-inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                                            <button type="submit" class="btn-action <?= $enabled ? 'text-emerald' : 'muted-cell' ?>">
                                                <?= $enabled ? '● Active' : '○ Hidden' ?>
                                            </button>
                                        </form>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-assessments/delete-exam') ?>" method="post" class="las-inline-form" onsubmit="return confirm('Delete this assessment?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
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
