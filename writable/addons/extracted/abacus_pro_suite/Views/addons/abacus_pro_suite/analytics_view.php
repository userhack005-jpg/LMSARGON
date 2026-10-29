<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="aps-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card" style="margin-bottom: 1.5rem;">
        <div class="card-head aps-flex-between">
            <div>
                <h3>Student Abacus Mastery Leaderboard</h3>
                <p class="field-hint" style="margin:0;">
                    <?= $effectiveRole === 'teacher' ? 'Showing practice analytics strictly for students in your assigned classroom batches.' : 'Showing student speed and accuracy analytics across all classroom batches.' ?>
                </p>
            </div>

            <?php if (! empty($cohortsMap)): ?>
                <form method="get" action="<?= base_url('addons/abacus-pro-suite/analytics') ?>">
                    <select name="cohort_id" class="field-select" onchange="this.form.submit()">
                        <option value="0">All Assigned Batches</option>
                        <?php foreach ($cohortsMap as $cid => $cInfo): ?>
                            <option value="<?= (int) $cid ?>" <?= (int) ($selectedCohortId ?? 0) === (int) $cid ? 'selected' : '' ?>>
                                🏫 <?= esc($cInfo['cohort_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Classroom Batch</th>
                        <th>Completed Drills</th>
                        <th>Avg Accuracy</th>
                        <th>Best Flash Speed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaderboard)): ?>
                        <tr><td colspan="5" class="muted-cell" style="text-align:center; padding:2rem;">No student practice attempts recorded for this batch yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leaderboard as $lb): ?>
                            <tr>
                                <td class="identity-name">🎓 <?= esc($lb['student_name']) ?></td>
                                <td class="mono-cell text-emerald">🏫 <?= esc($lb['batch_title']) ?></td>
                                <td class="mono-cell"><strong><?= (int) $lb['drills_count'] ?> Sets</strong></td>
                                <td class="mono-cell text-cyan"><strong><?= number_format((float) $lb['avg_accuracy'], 1) ?>%</strong></td>
                                <td class="mono-cell text-emerald">
                                    <?= (float) $lb['best_speed'] > 0 ? ('⚡ ' . number_format((float) $lb['best_speed'], 2) . 's / Row') : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="console-card">
        <div class="card-head">
            <div>
                <h3>Detailed Student Drill Submissions (<?= count($batchAttempts ?? []) ?>)</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student &amp; Batch</th>
                        <th>Mode</th>
                        <th>Difficulty &amp; Speed</th>
                        <th>Accuracy</th>
                        <th>Date</th>
                        <?php if (! empty($canDelete)): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($batchAttempts ?? []) as $at): ?>
                        <tr>
                            <td>
                                <div class="identity-name">🎓 <?= esc($at['student_name']) ?></div>
                                <div class="mono-cell muted-cell"><?= esc($at['batch_title']) ?></div>
                            </td>
                            <td><span class="role-badge"><?= strtoupper(str_replace('_', ' ', esc($at['drill_mode']))) ?></span></td>
                            <td class="mono-cell"><?= (int) $at['digits_count'] ?>D • <?= (int) $at['rows_count'] ?> Rows @ <?= number_format((float) $at['speed_seconds'], 1) ?>s</td>
                            <td class="mono-cell text-emerald"><strong><?= number_format((float) $at['accuracy_pct'], 1) ?>%</strong></td>
                            <td class="mono-cell muted-cell"><?= esc(substr((string) $at['created_at'], 0, 16)) ?></td>
                            <?php if (! empty($canDelete)): ?>
                                <td>
                                    <form action="<?= base_url('addons/abacus-pro-suite/delete-attempt') ?>" method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $at['id'] ?>">
                                        <button type="submit" class="btn-action text-danger">✕</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
