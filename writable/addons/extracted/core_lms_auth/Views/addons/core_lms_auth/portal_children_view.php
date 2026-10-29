<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $activeChild     = $portalCtx['active_student'] ?? null;
    $activeStudentId = (int) ($portalCtx['active_student_id'] ?? 0);
    $portalViewMode  = (string) ($portalCtx['portal_view_mode'] ?? 'parent');
    $isChildMode     = ($portalViewMode === 'student' && $activeStudentId > 0);
    $activeMeta      = $activeChild['meta'] ?? [];
    $siblings        = $portalCtx['siblings'] ?? [];
    $comp            = $companions ?? [];
?>
<div class="cla-portal-wrapper">
    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/portal_nav.php'; ?>

    <?php if (! $isChildMode): ?>
        <!-- =================================================================
             PARENT VIEW: Clean, Modern Child Profile Cards (Zero Clutter!)
             ================================================================= -->
        <div class="cla-flex-between" style="margin-bottom: 1.15rem;">
            <h3 style="margin:0;">My Enrolled Children</h3>
            <div class="action-btn-group">
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary btn-sm">➕ Add Child Profile</a>
                <?php if (! empty($comp['lms_finance'])): ?>
                    <a href="<?= base_url('finance/portal') ?>" class="btn-secondary btn-sm">💳 Fee Portal &amp; Receipts</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($siblings)): ?>
            <section class="console-card" style="text-align:center; padding: 3rem 1.5rem;">
                <h3>No Child Profiles Added Yet</h3>
                <p class="muted-cell" style="max-width:460px; margin:0.4rem auto 1.25rem;">
                    Add your child's profile to enroll them in courses, access live classes, and track their practice progress.
                </p>
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary">➕ Add Your First Child Profile</a>
            </section>
        <?php else: ?>
            <div class="cla-children-cards-grid">
                <?php foreach ($siblings as $sib): ?>
                    <?php
                        $sId   = (int) $sib['id'];
                        $sName = $sib['full_name'] ?? ($sib['name'] ?? 'Child');
                        $sMeta = $sib['meta'] ?? [];
                    ?>
                    <article class="cla-modern-child-card">
                        <div class="cla-mcc-top">
                            <div class="cla-child-cell">
                                <span class="cla-avatar-lg" style="background: <?= esc($sMeta['avatar_color'] ?? '#10b981') ?>;">
                                    <?= strtoupper(substr(trim((string) $sName), 0, 1)) ?>
                                </span>
                                <div>
                                    <h3 style="margin:0; font-size:1.15rem;"><?= esc($sName) ?></h3>
                                    <span class="muted-cell" style="font-size:0.82rem;">Age <?= (int) ($sMeta['child_age'] ?? 8) ?> Years</span>
                                </div>
                            </div>
                            <span class="role-badge text-emerald">Active</span>
                        </div>

                        <div class="cla-mcc-course-box">
                            <span class="mono-label">ENROLLED COURSE</span>
                            <div class="identity-name text-emerald" style="margin-top:0.2rem;"><?= esc($sMeta['grade_level'] ?? 'Foundation Course') ?></div>
                        </div>

                        <div class="cla-mcc-metrics">
                            <div class="cla-mcc-metric-item">
                                <span class="mono-label">ATTENDANCE</span>
                                <strong class="text-emerald"><?= number_format((float) ($sMeta['attendance_pct'] ?? 100), 1) ?>%</strong>
                            </div>
                            <div class="cla-mcc-metric-item">
                                <span class="mono-label">AVG SCORE</span>
                                <strong class="text-cyan"><?= number_format((float) ($sMeta['test_score_avg'] ?? 0), 1) ?>%</strong>
                            </div>
                            <div class="cla-mcc-metric-item">
                                <span class="mono-label">CERTIFICATES</span>
                                <strong>🏆 <?= (int) ($sMeta['certificates_count'] ?? 0) ?></strong>
                            </div>
                        </div>

                        <div class="cla-mcc-actions">
                            <form action="<?= base_url('portal/switch-student') ?>" method="post" style="flex:1; margin:0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= $sId ?>">
                                <button type="submit" class="btn-primary btn-full" style="justify-content:center;">
                                    🎓 Enter <?= esc($sName) ?>'s Learning Hub →
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- =================================================================
             STUDENT / CHILD LEARNING HUB (Distraction-Free Student Cards!)
             ================================================================= -->
        <div class="cla-children-cards-grid">
            <?php if (! empty($comp['lms_timetable'])): ?>
            <article class="cla-modern-child-card">
                <div>
                    <span class="role-badge text-emerald">🎥 LIVE CLASSROOM</span>
                    <h3 style="margin:0.65rem 0 0.3rem;">My Live Class Schedule</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.88rem;">Join your interactive batch classes, download class PDFs, and check homework notes.</p>
                </div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">
                    🎥 Open My Class Schedule →
                </a>
            </article>
            <?php endif; ?>

            <?php if (! empty($comp['lms_assessments'])): ?>
            <article class="cla-modern-child-card">
                <div>
                    <span class="role-badge text-cyan">📄 WORKSHEETS &amp; EXAMS</span>
                    <h3 style="margin:0.65rem 0 0.3rem;">Practice Drills &amp; Assessments</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.88rem;">Take timed speed practice sets, download PDF worksheets, and earn certificates.</p>
                </div>
                <a href="<?= base_url('assessments/portal') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">
                    🏆 Start Practice &amp; Exams →
                </a>
            </article>
            <?php endif; ?>

            <?php if (! empty($comp['abacus_pro_suite'])): ?>
            <article class="cla-modern-child-card">
                <div>
                    <span class="role-badge text-emerald">🧮 3D SOROBAN LAB</span>
                    <h3 style="margin:0.65rem 0 0.3rem;">3D Abacus &amp; Flash Anzan</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.88rem;">Practice on the interactive 3D Soroban abacus and high-speed mental math flash cards.</p>
                </div>
                <a href="<?= base_url('abacus/arena') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">
                    🧮 Launch 3D Abacus Arena →
                </a>
            </article>
            <?php endif; ?>

            <?php if (! empty($comp['lms_communication'])): ?>
            <article class="cla-modern-child-card">
                <div>
                    <span class="role-badge text-cyan">💬 CLASSROOM CHAT</span>
                    <h3 style="margin:0.65rem 0 0.3rem;">Ask My Batch Teacher</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.88rem;">Have a doubt in your worksheet? Message your assigned batch instructor directly.</p>
                </div>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">
                    💬 Open Teacher Chat →
                </a>
            </article>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
