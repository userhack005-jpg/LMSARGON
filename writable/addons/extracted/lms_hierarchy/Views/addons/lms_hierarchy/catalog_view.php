<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $role         = $effectiveRole ?? 'student';
    $comp         = $companions ?? [];
    $hasFinance   = ! empty($comp['lms_finance']);
    $appliedDemos = $myAppliedDemos ?? [];
?>
<div class="lhr-wrapper">
    <?php if (! empty($canManageTree)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php else: ?>
        <link rel="stylesheet" href="<?= base_url('assets/addons/lms_hierarchy/lms_hierarchy.css?v=8.5') ?>">
    <?php endif; ?>

    <?php if (! empty($appliedDemos)): ?>
        <section class="console-card" style="margin-bottom: 1.5rem; border-color: rgba(6, 182, 212, 0.45);">
            <div class="card-head lhr-flex-between">
                <div>
                    <span class="lhr-badge lhr-badge-cyan">🎯 MY APPLIED DEMO CLASSES</span>
                    <h3 style="margin:0.25rem 0 0;">Demo Class Applications &amp; Schedule Status</h3>
                </div>
            </div>
            <div class="lhr-cards-auto-grid">
                <?php foreach ($appliedDemos as $ad): ?>
                    <?php
                        $st     = strtolower((string) ($ad['status'] ?? 'new_lead'));
                        $isLive = ($st === 'demo_live');
                        $isSch  = ($isLive || $st === 'demo_scheduled' || (int) ($ad['assigned_teacher_id'] ?? 0) > 0);
                        $isConv = ($st === 'converted');
                    ?>
                    <div class="panel-card lhr-clean-course-card">
                        <div class="lhr-flex-between">
                            <span class="lhr-badge lhr-badge-emerald">🎓 <?= esc($ad['child_name']) ?></span>
                            <?php if ($isConv): ?>
                                <span class="lhr-badge lhr-badge-emerald">✅ Converted to Batch</span>
                            <?php elseif ($isLive): ?>
                                <span class="lhr-badge lhr-badge-cyan">🔴 LIVE NOW</span>
                            <?php elseif ($isSch): ?>
                                <span class="lhr-badge lhr-badge-cyan">📅 Demo Scheduled</span>
                            <?php else: ?>
                                <span class="lhr-badge lhr-badge-amber">⏳ Awaiting Schedule</span>
                            <?php endif; ?>
                        </div>
                        <h4 style="margin:0.45rem 0 0.2rem;"><?= esc($ad['course_title']) ?></h4>
                        <div class="mono-cell muted-cell">Preferred Timing: <strong><?= esc($ad['preferred_time_slot']) ?></strong></div>
                        <?php if ($isSch): ?>
                            <div class="lhr-flex-between" style="margin-top:0.75rem; padding-top:0.65rem; border-top:1px solid var(--lhr-border);">
                                <span class="mono-cell text-emerald">👩‍🏫 Teacher: <?= esc($ad['teacher_name'] ?: 'Assigned') ?></span>
                                <?php if (! empty($ad['meeting_link'])): ?>
                                    <a href="<?= esc($ad['meeting_link']) ?>" target="_blank" class="lhr-btn lhr-btn-emerald lhr-btn-sm">🎥 Join Demo Class ↗</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($role === 'parent'): ?>
        <section class="console-card">
            <div class="card-head lhr-flex-between">
                <div>
                    <h3>My Children's Enrolled Courses</h3>
                    <p class="field-hint" style="margin:0;">Active programs and monthly fee plans for your linked children.</p>
                </div>
                <?php if ($hasFinance): ?>
                    <a href="<?= base_url('finance/portal') ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">💳 Fee Portal &amp; Receipts →</a>
                <?php endif; ?>
            </div>

            <?php if (empty($myEnrollments)): ?>
                <p class="muted-cell">No active course enrollments found for your children yet.</p>
            <?php else: ?>
                <div class="lhr-cards-auto-grid">
                    <?php foreach ($myEnrollments as $enr): ?>
                        <div class="panel-card lhr-clean-course-card">
                            <div class="lhr-flex-between">
                                <span class="lhr-badge lhr-badge-emerald">🎓 <?= esc($enr['student_name']) ?></span>
                                <span class="mono-cell text-cyan"><strong><?= esc($enr['formatted_fee']) ?></strong></span>
                            </div>
                            <h3 style="margin:0.4rem 0 0.2rem;"><?= esc($enr['batch_title']) ?></h3>
                            <p class="mono-cell muted-cell" style="margin:0 0 0.85rem;"><?= esc($enr['breadcrumb_path']) ?></p>
                            <div class="lhr-flex-between" style="padding-top:0.75rem; border-top:1px solid var(--lhr-border);">
                                <span class="mono-cell muted-cell">Next Due: <?= esc($enr['next_due_date'] ?? 'Active') ?></span>
                                <div class="lhr-action-bar">
                                    <a href="<?= base_url('courses/view/' . (int) $enr['leaf_node_id']) ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm">📖 Course Details</a>
                                    <?php if ($hasFinance): ?>
                                        <a href="<?= base_url('finance/portal') ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">Pay Fee →</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php elseif ($role === 'teacher'): ?>
        <section class="console-card">
            <div class="card-head lhr-flex-between">
                <div>
                    <h3>My Assigned Courses &amp; Classroom Batches</h3>
                    <p class="field-hint" style="margin:0;">Courses and live classroom cohorts assigned to you.</p>
                </div>
                <?php if (! empty($comp['lms_timetable'])): ?>
                    <a href="<?= base_url('timetable/portal') ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">🎥 Open Live Class Console →</a>
                <?php endif; ?>
            </div>

            <?php if (empty($teacherCohorts) && empty($teacherCourses)): ?>
                <p class="muted-cell">No courses or live classroom batches have been assigned to your account yet.</p>
            <?php else: ?>
                <div class="lhr-cards-auto-grid">
                    <?php foreach (($teacherCohorts ?? []) as $tc): ?>
                        <div class="panel-card lhr-clean-course-card">
                            <span class="lhr-badge lhr-badge-emerald"><?= esc($tc['cohort_code'] ?? 'BATCH') ?></span>
                            <h3 style="margin:0.45rem 0 0.2rem;"><?= esc($tc['cohort_title']) ?></h3>
                            <div class="mono-cell text-cyan"><?= esc($tc['breadcrumb_path']) ?></div>
                            <div class="mono-cell muted-cell" style="margin-top:0.4rem;">
                                Dates: <?= esc($tc['start_date']) ?> to <?= esc($tc['end_date']) ?> | Time: <?= esc($tc['start_time_ist']) ?> IST
                            </div>
                            <div style="margin-top:0.85rem;">
                                <a href="<?= base_url('courses/view/' . (int) $tc['leaf_node_id']) ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm">📖 View Curriculum</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <section class="console-card" style="margin-bottom: 1.5rem;">
            <div class="card-head lhr-flex-between">
                <div>
                    <h3>My Enrolled Courses</h3>
                    <p class="field-hint" style="margin:0;">Your active learning programs and progress.</p>
                </div>
            </div>

            <?php if (empty($myEnrollments)): ?>
                <p class="muted-cell">You are not enrolled in an active course yet.</p>
            <?php else: ?>
                <div class="lhr-cards-auto-grid">
                    <?php foreach ($myEnrollments as $enr): ?>
                        <div class="panel-card lhr-clean-course-card">
                            <span class="mono-label text-cyan"><?= esc($enr['breadcrumb_path']) ?></span>
                            <h3 class="text-emerald" style="margin:0.35rem 0;"><?= esc($enr['batch_title']) ?></h3>
                            <div class="lhr-action-bar" style="margin-top:0.75rem;">
                                <span class="lhr-badge lhr-badge-emerald">Attendance: <?= number_format((float) $enr['attendance_pct'], 1) ?>%</span>
                                <span class="lhr-badge lhr-badge-info">Avg Score: <?= number_format((float) $enr['test_score_avg'], 1) ?>%</span>
                            </div>
                            <div class="lhr-action-bar" style="margin-top:1rem;">
                                <a href="<?= base_url('courses/view/' . (int) $enr['leaf_node_id']) ?>" class="lhr-btn lhr-btn-outline lhr-btn-sm">📖 View Curriculum</a>
                                <?php if (! empty($comp['lms_assessments'])): ?>
                                    <a href="<?= base_url('assessments/portal') ?>" class="lhr-btn lhr-btn-emerald lhr-btn-sm">📄 Worksheets &amp; Exams →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
