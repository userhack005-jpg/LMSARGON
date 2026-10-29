<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="las-portal-wrapper">
    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/portal_nav.php'; ?>

    <!-- Earned Certificates Showcase Grid -->
    <?php if (! empty($myCerts)): ?>
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin:0 0 0.75rem;">🏆 Earned Certificates (<?= count($myCerts) ?>)</h4>
            <div class="las-cards-grid">
                <?php foreach ($myCerts as $mc): ?>
                    <article class="las-modern-card" style="border-color: rgba(16, 185, 129, 0.45);">
                        <div>
                            <span class="role-badge text-emerald">🏆 OFFICIAL ACHIEVEMENT</span>
                            <h3 style="margin:0.45rem 0 0.2rem;"><?= esc($mc['course_title']) ?></h3>
                            <div class="mono-cell text-cyan">Recipient: <strong><?= esc($mc['student_name']) ?></strong></div>
                            <div class="mono-cell muted-cell" style="margin-top:0.25rem;">
                                Serial: <code><?= esc($mc['certificate_code']) ?></code> | Accuracy: <strong><?= number_format((float) $mc['score_pct'], 1) ?>%</strong>
                            </div>
                        </div>
                        <a href="<?= base_url('assessments/certificate-pdf/' . (int) $mc['id']) ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">
                            ⬇ Download PDF Certificate
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Redesigned Attempt & Practice History Table -->
    <section class="console-card">
        <div class="card-head las-flex-between">
            <div>
                <h3>Practice &amp; Exam Attempt History</h3>
                <p class="field-hint" style="margin:0;">Track your accuracy scores and calculation speed across every practice set.</p>
            </div>
            <?php if (empty($isParentOverview)): ?>
                <a href="<?= base_url('assessments/portal') ?>" class="btn-primary btn-sm">🎯 Take Another Practice Set →</a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php if (! empty($isParentOverview)): ?>
                            <th>Child Name</th>
                        <?php endif; ?>
                        <th>Assessment Title</th>
                        <th>Accuracy &amp; Score</th>
                        <th>Time &amp; Speed</th>
                        <th>Completed On</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myAttempts)): ?>
                        <tr>
                            <td colspan="<?= ! empty($isParentOverview) ? '6' : '5' ?>" class="muted-cell" style="text-align:center; padding:2rem;">
                                No practice sets or exams completed yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myAttempts as $att): ?>
                            <?php $score = (float) ($att['score_pct'] ?? 0); ?>
                            <tr>
                                <?php if (! empty($isParentOverview)): ?>
                                    <td class="identity-name">🎓 <?= esc($att['student_name']) ?></td>
                                <?php endif; ?>
                                <td class="identity-name"><?= esc($att['exam_title']) ?></td>
                                <td>
                                    <div class="las-score-bar-wrap">
                                        <strong class="<?= $score >= 75 ? 'text-emerald' : 'text-cyan' ?>"><?= number_format($score, 1) ?>%</strong>
                                        <span class="mono-cell muted-cell">(<?= (int) $att['correct_answers'] ?> / <?= (int) $att['total_questions'] ?> Correct)</span>
                                    </div>
                                    <div class="las-mini-progress">
                                        <div class="las-mini-fill" style="width: <?= min(100, max(5, (int) $score)) ?>%;"></div>
                                    </div>
                                </td>
                                <td class="mono-cell text-cyan">
                                    ⏱ <?= (int) $att['time_taken_seconds'] ?>s <small class="muted-cell">(<?= number_format((float) $att['avg_sec_per_question'], 1) ?>s / Q)</small>
                                </td>
                                <td class="mono-cell muted-cell"><?= esc(substr((string) $att['created_at'], 0, 16)) ?></td>
                                <td>
                                    <?php if ((int) $att['passed'] === 1): ?>
                                        <span class="role-badge text-emerald">✓ PASSED</span>
                                        <?php if ((int) ($att['certificate_id'] ?? 0) > 0): ?>
                                            <a href="<?= base_url('assessments/certificate-pdf/' . (int) $att['certificate_id']) ?>" class="btn-secondary btn-sm" style="margin-left:0.3rem;">⬇ Cert</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="role-badge">PRACTICED</span>
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
