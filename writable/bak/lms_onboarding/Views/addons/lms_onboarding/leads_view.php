<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.css') ?>">
<div class="onb-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card">
        <div class="card-head">
            <div>
                <h3>Demo Bookings, Teacher Assignment &amp; 1-Click Cohort Conversion</h3>
                <p class="field-hint" style="margin:0;">Assign a teacher &amp; date to schedule the demo class, then select a Live Cohort/Batch to convert the student directly into that batch.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Child &amp; Parent</th>
                        <th>Course &amp; Preferred Timing</th>
                        <th>Assign Demo Class (Teacher &amp; Date)</th>
                        <th>Status</th>
                        <th>Convert &amp; Add to Cohort/Batch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr><td colspan="5" class="muted-cell" style="text-align:center; padding:2rem;">No demo bookings yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leads as $ld): ?>
                            <?php
                                $lid         = (int) $ld['id'];
                                $st          = strtolower((string) ($ld['status'] ?? 'new_lead'));
                                $isConverted = ($st === 'converted');
                            ?>
                            <tr>
                                <td>
                                    <div class="identity-name">🎓 <?= esc($ld['child_name']) ?> <small class="muted-cell">(Age <?= (int) $ld['child_age'] ?>)</small></div>
                                    <div class="mono-cell">👨‍👩‍👧‍👦 <?= esc($ld['parent_name']) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc($ld['parent_email']) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name text-emerald"><?= esc($ld['course_title']) ?></div>
                                    <div class="mono-cell text-cyan">⏰ Preferred: <?= esc($ld['preferred_time_slot']) ?></div>
                                </td>
                                <td>
                                    <?php if ((int) $ld['assigned_teacher_id'] > 0): ?>
                                        <div class="identity-name">👩‍🏫 <?= esc($ld['teacher_name']) ?></div>
                                        <div class="mono-cell text-cyan">📅 Date: <?= esc($ld['preferred_date'] ?: 'Scheduled') ?></div>
                                    <?php else: ?>
                                        <form action="<?= base_url('addons/lms-onboarding/assign-lead-demo') ?>" method="post" style="display:flex; gap:0.35rem; flex-wrap:wrap; align-items:center;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="lead_id" value="<?= $lid ?>">
                                            <select name="assigned_teacher_id" class="field-select" style="width:145px; padding:0.3rem 0.5rem; font-size:0.78rem;" required>
                                                <option value="">Select Teacher...</option>
                                                <?php foreach (($teachersMap ?? []) as $tid => $tInfo): ?>
                                                    <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tInfo['display_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="date" name="demo_date" class="field-input" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" style="width:135px; padding:0.3rem 0.5rem; font-size:0.78rem;" required>
                                            <button type="submit" class="btn-secondary btn-sm">Schedule Demo</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="role-badge <?= $isConverted ? 'text-emerald' : 'text-cyan' ?>"><?= strtoupper(esc($st)) ?></span>
                                </td>
                                <td>
                                    <?php if ($isConverted): ?>
                                        <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">🏫 View in Batch →</a>
                                    <?php else: ?>
                                        <form action="<?= base_url('addons/lms-onboarding/convert-lead') ?>" method="post" style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="lead_id" value="<?= $lid ?>">
                                            <input type="hidden" name="course_node_id" value="<?= (int) $ld['course_node_id'] ?>">
                                            <select name="target_cohort_id" class="field-select" style="width:185px; padding:0.3rem 0.5rem; font-size:0.78rem;">
                                                <option value="0">-- Auto-Match Batch --</option>
                                                <?php foreach (($cohortsMap ?? []) as $cid => $cRow): ?>
                                                    <option value="<?= (int) $cid ?>" <?= (int) $cRow['leaf_node_id'] === (int) $ld['course_node_id'] ? 'selected' : '' ?>>
                                                        🏫 <?= esc($cRow['cohort_title']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-primary btn-sm">🚀 Convert &amp; Add to Batch</button>
                                        </form>
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
