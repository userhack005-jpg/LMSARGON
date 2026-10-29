<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $role       = $effectiveRole ?? 'student';
    $comp       = $companions ?? [];
    $hasFinance = ! empty($comp['lms_finance']);
?>
<div class="lhr-wrapper">
    <?php if (! empty($canManageTree)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <!-- =====================================================================
         ROLE 1: PARENT VIEW (Clean Children's Enrolled Courses — Zero Clutter!)
         ===================================================================== -->
    <?php if ($role === 'parent'): ?>
        <section class="console-card">
            <div class="card-head lhr-flex-between">
                <div>
                    <h3>My Children's Enrolled Courses</h3>
                    <p class="field-hint" style="margin:0;">Active programs and monthly fee plans for your linked children.</p>
                </div>
                <?php if ($hasFinance): ?>
                    <a href="<?= base_url('finance/portal') ?>" class="btn-primary btn-sm">💳 Fee Portal &amp; Receipts →</a>
                <?php endif; ?>
            </div>

            <?php if (empty($myEnrollments)): ?>
                <p class="muted-cell">No active course enrollments found for your children yet.</p>
            <?php else: ?>
                <div class="grid-2-col">
                    <?php foreach ($myEnrollments as $enr): ?>
                        <div class="panel-card lhr-clean-course-card">
                            <div class="lhr-flex-between">
                                <span class="role-badge text-emerald">🎓 <?= esc($enr['student_name']) ?></span>
                                <span class="mono-cell text-cyan"><strong><?= esc($enr['formatted_fee']) ?></strong></span>
                            </div>
                            <h3 style="margin:0.4rem 0 0.2rem;"><?= esc($enr['batch_title']) ?></h3>
                            <p class="mono-cell muted-cell" style="margin:0 0 0.85rem;"><?= esc($enr['breadcrumb_path']) ?></p>
                            <div class="lhr-flex-between" style="padding-top:0.75rem; border-top:1px solid var(--border-subtle);">
                                <span class="mono-cell muted-cell">Next Due: <?= esc($enr['next_due_date'] ?? 'Active') ?></span>
                                <div class="action-btn-group">
                                    <a href="<?= base_url('courses/view/' . (int) $enr['leaf_node_id']) ?>" class="btn-secondary btn-sm">📖 Course Details</a>
                                    <?php if ($hasFinance): ?>
                                        <a href="<?= base_url('finance/portal') ?>" class="btn-primary btn-sm">Pay Fee →</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <!-- =====================================================================
         ROLE 2: TEACHER VIEW (Strictly ONLY Assigned Courses & Classroom Batches!)
         ===================================================================== -->
    <?php elseif ($role === 'teacher'): ?>
        <section class="console-card">
            <div class="card-head lhr-flex-between">
                <div>
                    <h3>My Assigned Courses &amp; Classroom Batches</h3>
                    <p class="field-hint" style="margin:0;">Courses and live classroom cohorts assigned to you.</p>
                </div>
                <?php if (! empty($comp['lms_timetable'])): ?>
                    <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm">🎥 Open Live Class Console →</a>
                <?php endif; ?>
            </div>

            <?php if (empty($teacherCohorts) && empty($teacherCourses)): ?>
                <p class="muted-cell">No courses or live classroom batches have been assigned to your account yet.</p>
            <?php else: ?>
                <div class="grid-2-col">
                    <?php foreach (($teacherCohorts ?? []) as $tc): ?>
                        <div class="panel-card lhr-clean-course-card">
                            <span class="role-badge text-emerald"><?= esc($tc['cohort_code'] ?? 'BATCH') ?></span>
                            <h3 style="margin:0.45rem 0 0.2rem;"><?= esc($tc['cohort_title']) ?></h3>
                            <div class="mono-cell text-cyan"><?= esc($tc['breadcrumb_path']) ?></div>
                            <div class="mono-cell muted-cell" style="margin-top:0.4rem;">
                                Dates: <?= esc($tc['start_date']) ?> to <?= esc($tc['end_date']) ?> | Time: <?= esc($tc['start_time_ist']) ?> IST
                            </div>
                            <div style="margin-top:0.85rem;">
                                <a href="<?= base_url('courses/view/' . (int) $tc['leaf_node_id']) ?>" class="btn-secondary btn-sm">📖 View Curriculum</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <!-- =====================================================================
         ROLE 3: STUDENT / ADMIN VIEW (Clean Enrolled Courses!)
         ===================================================================== -->
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
                <div class="grid-2-col">
                    <?php foreach ($myEnrollments as $enr): ?>
                        <div class="panel-card lhr-clean-course-card">
                            <span class="mono-label text-cyan"><?= esc($enr['breadcrumb_path']) ?></span>
                            <h3 class="text-emerald" style="margin:0.35rem 0;"><?= esc($enr['batch_title']) ?></h3>
                            <div style="display:flex; gap:0.5rem; margin-top:0.75rem; flex-wrap:wrap;">
                                <span class="role-badge text-emerald">Attendance: <?= number_format((float) $enr['attendance_pct'], 1) ?>%</span>
                                <span class="role-badge">Avg Score: <?= number_format((float) $enr['test_score_avg'], 1) ?>%</span>
                            </div>
                            <div class="action-btn-group" style="margin-top:1rem;">
                                <a href="<?= base_url('courses/view/' . (int) $enr['leaf_node_id']) ?>" class="btn-secondary btn-sm">📖 View Curriculum</a>
                                <?php if (! empty($comp['lms_assessments'])): ?>
                                    <a href="<?= base_url('assessments/portal') ?>" class="btn-primary btn-sm">📄 Worksheets &amp; Exams →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (! empty($canManageTree)): ?>
            <?php
                $data = [
                    'badge'             => 'COURSE CATALOG PREVIEW',
                    'heading'           => 'Explore Our Courses & Learning Levels',
                    'subheading'        => 'Click any course card below to open its dedicated detail page or sub-levels.',
                    'display_mode'      => 'drilldown_tree',
                    'selected_node_ids' => '',
                    'show_demo_badge'   => '1',
                ];
                include __DIR__ . '/block_view.php';
            ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
