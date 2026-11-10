<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $role          = $effectiveRole ?? 'student';
    $userName      = $user['full_name'] ?? ($user['name'] ?? 'User');
    $widgets       = $wsWidgets ?? [];
?>
<style>
.dsh-wrap { width: 100%; max-width: 100%; box-sizing: border-box; }
.dsh-hero {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
    padding: 1.25rem 1.5rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    margin-bottom: 1.35rem; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}
.dsh-kpi-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1.15rem; margin-bottom: 1.5rem;
}
.dsh-kpi-card {
    padding: 1.2rem 1.35rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    display: flex; flex-direction: column; justify-content: space-between; gap: 0.55rem;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12); transition: transform 0.15s ease, border-color 0.15s ease;
}
.dsh-kpi-card:hover { transform: translateY(-2px); border-color: var(--accent-emerald, #10b981); }
.dsh-kpi-num { font-family: var(--font-mono, monospace); font-size: 1.65rem; font-weight: 900; margin: 0.15rem 0; }
.dsh-cards-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(295px, 1fr));
    gap: 1.2rem; margin-bottom: 1.5rem;
}
.dsh-action-card {
    padding: 1.3rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    display: flex; flex-direction: column; justify-content: space-between; gap: 1rem;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12); transition: transform 0.15s ease, border-color 0.15s ease;
}
.dsh-action-card:hover { transform: translateY(-2px); border-color: var(--accent-emerald, #10b981); }
.dsh-child-top { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
.dsh-avatar {
    width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.15rem; color: #04130d; flex-shrink: 0;
}
.dsh-child-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.45rem;
    padding: 0.65rem; border-radius: 10px; background: var(--bg-canvas, #090a0f);
    border: 1px solid var(--border-subtle, #232738); text-align: center;
}
.dsh-child-stats strong { display: block; font-size: 0.95rem; margin-top: 0.15rem; font-family: var(--font-mono, monospace); }
</style>

<div class="dsh-wrap">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- =====================================================================
         ROLE 1: STUDENT / CHILD LEARNING HUB DASHBOARD
         ===================================================================== -->
    <?php if ($role === 'student'): ?>
        <?php $sh = $studentHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">🎓 STUDENT LEARNING HUB</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome back, <?= esc($sh['student_name'] ?? $userName) ?>!</h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Enrolled Program: <strong class="text-emerald"><?= esc($sh['course_title'] ?? 'Foundation Course') ?></strong></p>
            </div>

            <?php if (! empty($isChildSandbox)): ?>
                <form action="<?= base_url('portal/switch-student') ?>" method="post" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="0">
                    <button type="submit" class="btn-secondary btn-sm">🏠 Return to Parent Account</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- 4 Countable Student KPI Cards -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">CLASS ATTENDANCE</span>
                <div class="dsh-kpi-num text-emerald"><?= number_format((float) ($sh['attendance_pct'] ?? 100), 1) ?>%</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= esc($sh['course_title'] ?? 'Active Batch') ?></span>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">NEXT LIVE CLASS</span>
                <?php if (! empty($sh['next_class'])): ?>
                    <div class="identity-name text-cyan" style="margin:0.25rem 0;"><?= esc($sh['next_class']['local_time']) ?></div>
                    <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">🎥 Open Class Schedule →</a>
                <?php else: ?>
                    <div class="identity-name" style="margin:0.25rem 0;">View Weekly Schedule</div>
                    <a href="<?= base_url('timetable/portal') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">📅 Open Timetable →</a>
                <?php endif; ?>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">EXAM &amp; PRACTICE SCORE</span>
                <div class="dsh-kpi-num text-cyan"><?= number_format((float) ($sh['avg_exam_score'] ?? 0), 1) ?>%</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= (int) ($sh['exams_done_cnt'] ?? 0) ?> Practice Sets Completed</span>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">ABACUS DRILLS &amp; BADGES</span>
                <div class="dsh-kpi-num">🏆 <?= (int) ($sh['certs_count'] ?? 0) ?> Certs</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= (int) ($sh['abacus_drills'] ?? 0) ?> Speed Drills Logged</span>
            </div>
        </div>

        <!-- Student Interactive Learning Launchers -->
        <div class="dsh-cards-grid">
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🧮 3D SOROBAN LAB</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Interactive 3D Abacus</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Practice bead movements and solve target number challenges with audio feedback.</p>
                </div>
                <a href="<?= base_url('abacus/arena') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">🧮 Open 3D Soroban Lab →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">⚡ MENTAL MATH SPEED</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Flash Anzan &amp; Voice Drill</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Boost mental calculation speed with screen flash numbers and voice dictation.</p>
                </div>
                <a href="<?= base_url('abacus/anzan') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">⚡ Start Flash Anzan →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🎯 EXAMS &amp; WORKSHEETS</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Practice Drills &amp; PDF Sheets</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Take timed practice sets, download PDF worksheets, and earn certificates.</p>
                </div>
                <a href="<?= base_url('assessments/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">📄 Open Exams &amp; Worksheets →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">💬 CLASSROOM SUPPORT</span>
                    <h3 style="margin:0.55rem 0 0.25rem;">Ask My Batch Teacher</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Message your batch instructor 1-to-1 or share feedback on your classes.</p>
                </div>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">💬 Open Teacher Chat →</a>
            </article>
        </div>

    <!-- =====================================================================
         ROLE 2: PARENT FAMILY OVERVIEW DASHBOARD
         ===================================================================== -->
    <?php elseif ($role === 'parent'): ?>
        <?php $ph = $parentHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-cyan">👨‍👩‍👧‍👦 FAMILY LEARNING PORTAL</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Select any child below to enter their learning hub, or manage fee subscriptions and profile settings.</p>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary btn-sm" style="text-decoration:none;">➕ Add Child Profile</a>
                <a href="<?= base_url('portal/family/profile') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">⚙ Profile &amp; Password</a>
            </div>
        </div>

        <!-- 4 Countable Parent KPI Cards -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">MY ENROLLED CHILDREN</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($ph['children_count'] ?? 0) ?> Kids</div>
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">+ Add Another Child →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">ACTIVE COURSES</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ph['active_courses'] ?? 0) ?> Programs</div>
                <a href="<?= base_url('courses/catalog') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">📖 View Enrolled Courses →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">FEE &amp; SUBSCRIPTIONS</span>
                <div class="identity-name text-emerald" style="margin:0.25rem 0;"><?= esc($ph['next_due_str'] ?? 'Active') ?></div>
                <a href="<?= base_url('finance/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">💳 Open Fee &amp; Receipts →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">EARNED CERTIFICATES</span>
                <div class="dsh-kpi-num">🏆 <?= (int) ($ph['total_certs'] ?? 0) ?></div>
                <a href="<?= base_url('assessments/portal/history') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">📄 View Score Reports →</a>
            </div>
        </div>

        <!-- Clean Children Profile Cards -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.9rem;">
            <h3 style="margin:0;">My Children's Learning Profiles</h3>
            <a href="<?= base_url('timetable/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">📅 View Kids Class Schedule →</a>
        </div>

        <?php if (empty($ph['children'])): ?>
            <section class="console-card" style="text-align:center; padding:2.5rem;">
                <h3>No Child Profiles Linked Yet</h3>
                <p class="muted-cell">Click below to add your child's profile and start their learning journey.</p>
                <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary">➕ Add Your First Child Profile</a>
            </section>
        <?php else: ?>
            <div class="dsh-cards-grid">
                <?php foreach ($ph['children'] as $ch): ?>
                    <article class="dsh-action-card">
                        <div>
                            <div class="dsh-child-top">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <span class="dsh-avatar" style="background: <?= esc($ch['avatar_color']) ?>;">
                                        <?= strtoupper(substr(trim((string) $ch['full_name']), 0, 1)) ?>
                                    </span>
                                    <div>
                                        <h3 style="margin:0; font-size:1.12rem;"><?= esc($ch['full_name']) ?></h3>
                                        <span class="muted-cell" style="font-size:0.8rem;">Age <?= (int) $ch['age'] ?> Years</span>
                                    </div>
                                </div>
                                <span class="role-badge text-emerald">Active</span>
                            </div>

                            <div style="margin:0.85rem 0; padding:0.65rem 0.85rem; border-radius:10px; background:var(--bg-canvas, #090a0f); border:1px solid var(--border-subtle, #232738);">
                                <span class="mono-label">ENROLLED COURSE</span>
                                <div class="identity-name text-emerald" style="margin-top:0.15rem;"><?= esc($ch['course_title']) ?></div>
                            </div>

                            <div class="dsh-child-stats">
                                <div>
                                    <span class="mono-label">ATTENDANCE</span>
                                    <strong class="text-emerald"><?= number_format((float) $ch['attendance_pct'], 1) ?>%</strong>
                                </div>
                                <div>
                                    <span class="mono-label">AVG SCORE</span>
                                    <strong class="text-cyan"><?= number_format((float) $ch['test_score_avg'], 1) ?>%</strong>
                                </div>
                                <div>
                                    <span class="mono-label">CERTS</span>
                                    <strong>🏆 <?= (int) $ch['certs_count'] ?></strong>
                                </div>
                            </div>
                        </div>

                        <form action="<?= base_url('portal/switch-student') ?>" method="post" style="margin:0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="student_id" value="<?= (int) $ch['id'] ?>">
                            <button type="submit" class="btn-primary btn-full" style="justify-content:center;">
                                🎓 Enter <?= esc($ch['full_name']) ?>'s Learning Hub →
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- =====================================================================
         ROLE 3: TEACHER / INSTRUCTOR DASHBOARD
         ===================================================================== -->
    <?php elseif ($role === 'teacher'): ?>
        <?php $th = $teacherHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">👩‍🏫 INSTRUCTOR WORKSPACE</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Manage your live classroom batches, student attendance, question banks, and 1-to-1 student chat.</p>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="text-decoration:none;">🎥 Live Class Console →</a>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">💬 Student Chat</a>
            </div>
        </div>

        <!-- 4 Countable Teacher KPI Cards -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">MY ASSIGNED BATCHES</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($th['batches_count'] ?? 0) ?> Batches</div>
                <a href="<?= base_url('timetable/portal?tab=rosters') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">🏫 View Batch Rosters →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">ENROLLED STUDENTS</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($th['students_count'] ?? 0) ?> Students</div>
                <a href="<?= base_url('addons/abacus-pro-suite/analytics') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">📊 Batch Abacus Progress →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">UPCOMING LIVE CLASSES</span>
                <div class="dsh-kpi-num"><?= (int) ($th['upcoming_count'] ?? 0) ?> Classes</div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">▶ Start Class Console →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">STUDENT FEEDBACK</span>
                <div class="dsh-kpi-num text-emerald">★ <?= number_format((float) ($th['avg_rating'] ?? 5.0), 1) ?></div>
                <a href="<?= base_url('chat/feedback') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">⭐ <?= (int) ($th['feedback_count'] ?? 0) ?> Reviews →</a>
            </div>
        </div>

        <div class="dsh-cards-grid">
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🎥 LIVE CLASSROOM</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Start / End Class &amp; Roll Call</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Confirm meeting link, share study PDFs, mark student attendance, or request a substitute teacher.</p>
                </div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">🎥 Open Live Class Console →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">🗃 QUESTION BANK &amp; EXAMS</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Course Questions &amp; Worksheets</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Add math questions to your assigned courses, build practice drills, and upload PDF worksheets.</p>
                </div>
                <a href="<?= base_url('addons/lms-assessments/question-bank') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">📝 Manage Question Bank →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">💬 STUDENT MESSENGER</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">1-to-1 Batch Student Chat</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Filter students by batch and solve their worksheet or abacus doubts in real time.</p>
                </div>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">💬 Open Classroom Chat →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">🎯 DEMO EVALUATIONS &amp; PAYROLL</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Assigned Demos (<?= (int) ($th['assigned_demos'] ?? 0) ?>) &amp; Salary Slips</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.86rem;">Conduct assigned trial classes or view your monthly credited salary slips.</p>
                </div>
                <div class="action-btn-group">
                    <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="btn-primary btn-sm" style="flex:1; justify-content:center; text-decoration:none;">👩‍🏫 My Demos</a>
                    <a href="<?= base_url('finance/my-payouts') ?>" class="btn-secondary btn-sm" style="flex:1; justify-content:center; text-decoration:none;">💵 Salary Slips</a>
                </div>
            </article>
        </div>

    <!-- =====================================================================
         ROLE 4: SUPER ADMIN & ADMIN EXECUTIVE COMMAND DASHBOARD
         ===================================================================== -->
    <?php else: ?>
        <?php $ah = $adminHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">🛡 ACADEMY COMMAND CENTER</span>
                <h2 style="margin:0.3rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.86rem;">Real-time countable metrics across students, live batches, fee revenue, demo admissions, and safety moderation.</p>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="btn-primary btn-sm" style="text-decoration:none;">➕ Create Course</a>
                <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">🏫 Create Live Batch</a>
                <a href="<?= base_url('addons/rbac/workspace') ?>" class="btn-action" style="text-decoration:none;">🎨 Customize Menus</a>
            </div>
        </div>

        <!-- 8 Countable Executive KPI Blocks -->
        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">ENROLLED STUDENTS &amp; PARENTS</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($ah['students_count'] ?? 0) ?> Students</div>
                <a href="<?= base_url('addons/core-lms-auth/directory') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">👨‍👩‍👧‍👦 <?= (int) ($ah['parents_count'] ?? 0) ?> Parents Directory →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">TEACHERS &amp; LIVE BATCHES</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ah['cohorts_count'] ?? 0) ?> Batches</div>
                <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">👩‍🏫 <?= (int) ($ah['teachers_count'] ?? 0) ?> Active Teachers →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">VERIFIED FEE REVENUE</span>
                <div class="dsh-kpi-num text-emerald">₹<?= number_format((float) ($ah['revenue_inr'] ?? 0), 0) ?></div>
                <a href="<?= base_url('addons/lms-finance') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">🧾 <?= (int) ($ah['receipts_count'] ?? 0) ?> Paid Receipts →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-amber">OFFLINE FEE APPROVALS</span>
                <div class="dsh-kpi-num <?= (int) ($ah['pending_approvals'] ?? 0) > 0 ? 'text-amber' : '' ?>"><?= (int) ($ah['pending_approvals'] ?? 0) ?> Pending</div>
                <a href="<?= base_url('addons/lms-finance') ?>" class="btn-action text-amber" style="align-self:flex-start; text-decoration:none;">✅ Review Screenshots →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">DEMO INQUIRIES PIPELINE</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ah['new_demo_leads'] ?? 0) ?> Leads</div>
                <a href="<?= base_url('addons/lms-onboarding') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">🚀 Convert to Students →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">SUBSTITUTE REQUESTS</span>
                <div class="dsh-kpi-num <?= (int) ($ah['pending_substitutes'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= (int) ($ah['pending_substitutes'] ?? 0) ?> Pending</div>
                <a href="<?= base_url('addons/lms-timetable/substitutes') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">⇄ Open Substitute Inbox →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label">CHAT SAFETY MODERATION</span>
                <div class="dsh-kpi-num <?= (int) ($ah['flagged_chats'] ?? 0) > 0 ? 'text-danger' : 'text-emerald' ?>"><?= (int) ($ah['flagged_chats'] ?? 0) ?> Flagged</div>
                <a href="<?= base_url('addons/lms-communication/moderation') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">🛡 Moderation Queue →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">COURSES &amp; EXAMS</span>
                <div class="dsh-kpi-num"><?= (int) ($ah['courses_count'] ?? 0) ?> Courses</div>
                <a href="<?= base_url('addons/lms-assessments/exams') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">📝 <?= (int) ($ah['exams_count'] ?? 0) ?> Active Exams →</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
