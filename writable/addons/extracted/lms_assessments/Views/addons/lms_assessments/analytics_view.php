<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card las-section-gap">
        <div class="card-head">
            <div>
                <h3>Student Exam &amp; Practice Results</h3>
                <p class="field-hint" style="margin:0;">Completed practice sets, accuracy scores, speed timings, and issued certificates.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Assessment Title</th>
                        <th>Accuracy Score</th>
                        <th>Time &amp; Speed</th>
                        <th>Completed On</th>
                        <th>Status &amp; Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2rem;">No completed exam or practice attempts yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $att): ?>
                            <?php $score = (float) ($att['score_pct'] ?? 0); ?>
                            <tr>
                                <td class="identity-name">🎓 <?= esc($att['student_name']) ?></td>
                                <td>
                                    <div class="identity-name"><?= esc($att['exam_title']) ?></div>
                                </td>
                                <td>
                                    <div class="las-score-bar-wrap">
                                        <strong class="<?= $score >= 75 ? 'text-emerald' : 'text-cyan' ?>"><?= number_format($score, 1) ?>%</strong>
                                        <span class="mono-cell muted-cell">(<?= (int) $att['correct_answers'] ?>/<?= (int) $att['total_questions'] ?>)</span>
                                    </div>
                                </td>
                                <td class="mono-cell text-cyan">
                                    ⏱ <?= (int) $att['time_taken_seconds'] ?>s <small class="muted-cell">(<?= number_format((float) $att['avg_sec_per_question'], 1) ?>s/Q)</small>
                                </td>
                                <td class="mono-cell muted-cell"><?= esc(substr((string) $att['created_at'], 0, 16)) ?></td>
                                <td>
                                    <?php if ((int) $att['passed'] === 1): ?>
                                        <span class="role-badge text-emerald">✓ PASSED</span>
                                        <?php if ((int) $att['certificate_id'] > 0): ?>
                                            <a href="<?= base_url('assessments/certificate-pdf/' . (int) $att['certificate_id']) ?>" class="btn-primary btn-sm" style="margin-left:0.35rem;">⬇ Certificate</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="role-badge">COMPLETED</span>
                                    <?php endif; ?>
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
