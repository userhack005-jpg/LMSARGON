<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $db            = \Config\Database::connect();
    $role          = $effectiveRole ?? (function_exists('cms_get_effective_role') ? cms_get_effective_role() : strtolower(trim((string) (session()->get('role_slug') ?: 'student'))));
    $userName      = $user['full_name'] ?? ($user['name'] ?? (session()->get('full_name') ?: 'User'));
    $uid           = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));
    $activeStuId   = (int) (session()->get('active_student_id') ?: 0);
    $viewMode      = (string) (session()->get('portal_view_mode') ?: 'default');
    $isChildMode   = ($viewMode === 'student' && $activeStuId > 0);
    $userTz        = (string) (session()->get('user_timezone') ?: 'Asia/Kolkata');

    // Discover active add-ons dynamically so no disabled add-on ever shows a dead/blank card!
    $activeAddons = [];
    if ($db->tableExists('addons')) {
        foreach ($db->table('addons')->select('slug')->where('is_active', 1)->get()->getResultArray() as $aRow) {
            $activeAddons[trim((string) $aRow['slug'])] = true;
        }
    }
    $hasTimetable   = isset($activeAddons['lms_timetable']);
    $hasAssessments = isset($activeAddons['lms_assessments']);
    $hasAbacus      = isset($activeAddons['abacus_pro_suite']);
    $hasComm        = isset($activeAddons['lms_communication']);
    $hasFinance     = isset($activeAddons['lms_finance']);
    $hasOnboarding  = isset($activeAddons['lms_onboarding']);
    $hasHierarchy   = isset($activeAddons['lms_hierarchy']);
    $hasCoreAuth    = isset($activeAddons['core_lms_auth']);

    // Helper: Fetch live Demo Applications for given student IDs or parent ID
    $fetchDemosForStudents = static function (array $stuIds, int $parentUid = 0, string $parentEmail = '') use ($db): array {
        if (! $db->tableExists('lms_demo_leads')) {
            return ['all' => [], 'by_student' => []];
        }
        $courses = [];
        if ($db->tableExists('lms_hierarchy_nodes')) {
            foreach ($db->table('lms_hierarchy_nodes')->select('id, title')->get()->getResultArray() as $n) {
                $courses[(int) $n['id']] = $n['title'];
            }
        }
        $batches = [];
        if ($db->tableExists('lms_demo_batches')) {
            foreach ($db->table('lms_demo_batches')->get()->getResultArray() as $b) {
                $batches[(int) $b['id']] = $b;
            }
        }
        $teachers = [];
        if ($db->tableExists('users')) {
            foreach ($db->table('users')->select('id, full_name')->where('role_id', 3)->orWhere('role_slug', 'teacher')->get()->getResultArray() as $t) {
                $teachers[(int) $t['id']] = $t['full_name'];
            }
        }

        $qb = $db->table('lms_demo_leads')->orderBy('id', 'DESC');
        $qb->groupStart();
        $hasCond = false;
        if (! empty($stuIds)) {
            $qb->orWhereIn('converted_student_id', array_map('intval', $stuIds));
            $hasCond = true;
        }
        if ($parentUid > 0) {
            $qb->orWhere('converted_parent_id', $parentUid);
            $hasCond = true;
        }
        if ($parentEmail !== '') {
            $qb->orWhere('parent_email', strtolower(trim($parentEmail)));
            $hasCond = true;
        }
        $qb->groupEnd();
        if (! $hasCond) {
            return ['all' => [], 'by_student' => []];
        }

        $rows = $qb->get()->getResultArray();
        $byStu = [];
        foreach ($rows as &$r) {
            $sid   = (int) ($r['converted_student_id'] ?? 0);
            $cid   = (int) ($r['course_node_id'] ?? 0);
            $bid   = (int) ($r['demo_batch_id'] ?? 0);
            $bInfo = $batches[$bid] ?? null;
            $tid   = (int) ($bInfo['teacher_user_id'] ?? ($r['assigned_teacher_id'] ?? 0));

            $r['course_title']   = $courses[$cid] ?? 'Course Demo';
            $r['teacher_name']   = $teachers[$tid] ?? '';
            $r['slot_title']     = $bInfo['batch_title'] ?? '';
            $r['slot_date']      = $bInfo && ! empty($bInfo['demo_date']) ? $bInfo['demo_date'] : ($r['preferred_date'] ?? '');
            $rawTime             = $bInfo && ! empty($bInfo['start_time_ist']) ? $bInfo['start_time_ist'] : '';
            $r['slot_time_ampm'] = $rawTime !== '' ? date('h:i A', strtotime('2026-01-01 ' . $rawTime)) : ($r['preferred_time_slot'] ?? '');
            $r['slot_status']    = $bInfo ? strtolower((string) ($bInfo['status'] ?? 'scheduled')) : 'unassigned';
            $r['live_link']      = $bInfo && ! empty($bInfo['meeting_link']) ? $bInfo['meeting_link'] : ($r['meeting_link'] ?? '');
            if ($sid > 0) {
                $byStu[$sid][] = $r;
            }
        }
        unset($r);
        return ['all' => $rows, 'by_student' => $byStu];
    };
?>
<style>
.dsh-wrap { width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; }
.dsh-hero {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
    padding: 1.15rem 1.35rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    margin-bottom: 1.25rem; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}
.dsh-kpi-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
    gap: 1rem; margin-bottom: 1.35rem;
}
.dsh-kpi-card {
    padding: 1.1rem 1.25rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    display: flex; flex-direction: column; justify-content: space-between; gap: 0.45rem;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.1);
}
.dsh-kpi-num { font-family: var(--font-mono, monospace); font-size: 1.55rem; font-weight: 900; margin: 0.1rem 0; }
.dsh-cards-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 290px), 1fr));
    gap: 1.15rem; margin-bottom: 1.35rem;
}
.dsh-action-card {
    padding: 1.25rem; border-radius: 14px;
    background: var(--bg-surface, #11131c); border: 1px solid var(--border-subtle, #232738);
    display: flex; flex-direction: column; justify-content: space-between; gap: 0.9rem;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.1);
}
.dsh-child-top { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
.dsh-avatar {
    width: 42px; height: 42px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.1rem; color: #04130d; flex-shrink: 0;
}
.dsh-child-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem;
    padding: 0.6rem; border-radius: 10px; background: var(--bg-canvas, #090a0f);
    border: 1px solid var(--border-subtle, #232738); text-align: center;
}
.dsh-child-stats strong { display: block; font-size: 0.92rem; margin-top: 0.15rem; font-family: var(--font-mono, monospace); }
.dsh-demo-pill-box {
    margin-top: 0.65rem; padding: 0.65rem 0.85rem; border-radius: 10px;
    background: rgba(6, 182, 212, 0.08); border: 1px solid rgba(6, 182, 212, 0.35);
    font-size: 0.8rem; display: flex; flex-direction: column; gap: 0.3rem;
}
</style>

<div class="dsh-wrap">
    <!-- =====================================================================
         ROLE 1: STUDENT / CHILD LEARNING HUB DASHBOARD
         ===================================================================== -->
    <?php if ($role === 'student'): ?>
        <?php
            $sh    = $studentHub ?? [];
            $stuId = $activeStuId > 0 ? $activeStuId : $uid;
            $sName = $sh['student_name'] ?? $userName;
            if ($stuId > 0 && $db->tableExists('users')) {
                $uRow = $db->table('users')->select('full_name, parent_user_id')->where('id', $stuId)->get()->getRowArray();
                if (! empty($uRow['full_name'])) {
                    $sName = $uRow['full_name'];
                }
            }
            $demoBundle = $hasOnboarding ? $fetchDemosForStudents([$stuId], 0, '') : ['all' => [], 'by_student' => []];
            $myDemos    = $demoBundle['by_student'][$stuId] ?? $demoBundle['all'];
        ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">🎓 STUDENT LEARNING HUB</span>
                <h2 style="margin:0.25rem 0 0.1rem;">Welcome back, <?= esc($sName) ?>!</h2>
                <p class="muted-cell" style="margin:0; font-size:0.85rem;">Enrolled Program: <strong class="text-emerald"><?= esc($sh['course_title'] ?? 'Active Course') ?></strong></p>
            </div>
        </div>

        <?php if (! empty($myDemos)): ?>
            <section class="console-card" style="margin-bottom:1.25rem; border:1.5px solid #06b6d4 !important;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.75rem;">
                    <div>
                        <span class="role-badge text-cyan">🎯 MY DEMO CLASS STATUS</span>
                        <h3 style="margin:0.25rem 0 0;font-size:1.05rem;">Your Trial / Demo Class Schedule &amp; Updates</h3>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:0.6rem;">
                    <?php foreach ($myDemos as $dm): ?>
                        <?php
                            $st       = strtolower((string) ($dm['status'] ?? 'new_lead'));
                            $isLive   = ($st === 'demo_live' || ($dm['slot_status'] ?? '') === 'live');
                            $isDone   = ($st === 'demo_attended' || ($dm['attendance_status'] ?? '') === 'Present');
                            $isAbs    = ($st === 'reschedule_needed' || ($dm['attendance_status'] ?? '') === 'Absent');
                            $isConv   = ($st === 'converted');
                            $isSched  = ((int) ($dm['demo_batch_id'] ?? 0) > 0 || $st === 'demo_scheduled');
                        ?>
                        <div class="panel-card" style="padding:0.85rem 1rem !important;background:var(--bg-canvas);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                            <div>
                                <div style="font-weight:800;font-size:0.94rem;">
                                    📚 <?= esc($dm['course_title']) ?>
                                    <?php if (! empty($dm['slot_title'])): ?>
                                        <span class="mono-cell text-cyan">(<?= esc($dm['slot_title']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mono-cell muted-cell" style="margin-top:0.2rem;">
                                    <?php if ($isSched && ! empty($dm['slot_date'])): ?>
                                        📅 Demo Date: <strong class="text-emerald"><?= esc($dm['slot_date']) ?></strong> •
                                        ⏰ Time: <strong class="text-cyan"><?= esc($dm['slot_time_ampm']) ?> IST</strong>
                                        <?php if (! empty($dm['teacher_name'])): ?>
                                            • 👩‍🏫 Teacher: <strong><?= esc($dm['teacher_name']) ?></strong>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        ⏰ Requested Slot: <strong><?= esc($dm['preferred_time_slot']) ?></strong>
                                    <?php endif; ?>
                                    <?php if (! empty($dm['teacher_eval_note'])): ?>
                                        <br>💬 Teacher Remark: <strong><?= esc($dm['teacher_eval_note']) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                <?php if ($isLive && ! empty($dm['live_link'])): ?>
                                    <a href="<?= esc($dm['live_link']) ?>" target="_blank" class="btn-primary btn-sm" style="text-decoration:none;">🔴 LIVE NOW — Join Demo Class ↗</a>
                                <?php elseif ($isConv): ?>
                                    <span class="role-badge text-emerald">✅ Converted to Regular Batch</span>
                                <?php elseif ($isDone): ?>
                                    <span class="role-badge text-emerald">✅ Demo Completed (Present)</span>
                                <?php elseif ($isAbs): ?>
                                    <span class="role-badge text-danger">❌ Absent — Reschedule Pending</span>
                                <?php elseif ($isSched): ?>
                                    <span class="role-badge text-cyan">📅 Scheduled (<?= esc($dm['slot_date']) ?>)</span>
                                    <?php if (! empty($dm['live_link'])): ?>
                                        <a href="<?= esc($dm['live_link']) ?>" target="_blank" class="btn-secondary btn-sm" style="text-decoration:none;">🎥 Room Link ↗</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="role-badge text-amber">⏳ Awaiting Admin Slot Assignment</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">CLASS ATTENDANCE</span>
                <div class="dsh-kpi-num text-emerald"><?= number_format((float) ($sh['attendance_pct'] ?? 100), 1) ?>%</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= esc($sh['course_title'] ?? 'Active Batch') ?></span>
            </div>

            <?php if ($hasTimetable): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">NEXT LIVE CLASS</span>
                <?php if (! empty($sh['next_class'])): ?>
                    <div class="identity-name text-cyan" style="margin:0.2rem 0;"><?= esc($sh['next_class']['local_time']) ?></div>
                <?php else: ?>
                    <div class="identity-name" style="margin:0.2rem 0;">View Weekly Schedule</div>
                <?php endif; ?>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">🎥 Open Class Schedule →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasAssessments): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label">EXAM &amp; PRACTICE SCORE</span>
                <div class="dsh-kpi-num text-cyan"><?= number_format((float) ($sh['avg_exam_score'] ?? 0), 1) ?>%</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= (int) ($sh['exams_done_cnt'] ?? 0) ?> Practice Sets Completed</span>
            </div>
            <?php endif; ?>

            <?php if ($hasAbacus || $hasAssessments): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">CERTIFICATES &amp; DRILLS</span>
                <div class="dsh-kpi-num">🏆 <?= (int) ($sh['certs_count'] ?? 0) ?> Certs</div>
                <span class="muted-cell" style="font-size:0.8rem;"><?= (int) ($sh['abacus_drills'] ?? 0) ?> Abacus Speed Drills</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="dsh-cards-grid">
            <?php if ($hasAbacus): ?>
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🧮 3D SOROBAN LAB</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Interactive 3D Abacus</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Practice bead movements and solve target number challenges with audio feedback.</p>
                </div>
                <a href="<?= base_url('abacus/arena') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">🧮 Open 3D Soroban Lab →</a>
            </article>

            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">⚡ MENTAL MATH SPEED</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Flash Anzan &amp; Voice Drill</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Boost mental calculation speed with screen flash numbers and Hindi/English voice dictation.</p>
                </div>
                <a href="<?= base_url('abacus/anzan') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">⚡ Start Flash Anzan →</a>
            </article>
            <?php endif; ?>

            <?php if ($hasAssessments): ?>
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🎯 EXAMS &amp; WORKSHEETS</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Practice Drills &amp; PDF Sheets</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Take timed practice sets, download PDF worksheets, and earn certificates.</p>
                </div>
                <a href="<?= base_url('assessments/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">📄 Open Exams &amp; Worksheets →</a>
            </article>
            <?php endif; ?>

            <?php if ($hasComm): ?>
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">💬 CLASSROOM SUPPORT</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Ask My Batch Teacher</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Message your batch instructor 1-to-1 or share feedback on your classes.</p>
                </div>
                <a href="<?= base_url('chat/portal') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">💬 Open Teacher Chat →</a>
            </article>
            <?php endif; ?>
        </div>

    <!-- =====================================================================
         ROLE 2: PARENT FAMILY OVERVIEW DASHBOARD (WITH PER-CHILD DEMO STATUS)
         ===================================================================== -->
    <?php elseif ($role === 'parent'): ?>
        <?php
            $ph      = $parentHub ?? [];
            $kidIds  = [];
            foreach (($ph['children'] ?? []) as $cItem) {
                $kidIds[] = (int) $cItem['id'];
            }
            $demoBundle = $hasOnboarding ? $fetchDemosForStudents($kidIds, $uid, (string) (session()->get('email') ?: '')) : ['all' => [], 'by_student' => []];
            $demosByKid = $demoBundle['by_student'];
            $allDemos   = $demoBundle['all'];
        ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-cyan">👨‍👩‍👧‍👦 FAMILY LEARNING PORTAL</span>
                <h2 style="margin:0.25rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.85rem;">Select any child below to enter their learning hub, check their demo class status, or manage fee subscriptions.</p>
            </div>
            <div class="action-btn-group">
                <?php if ($hasCoreAuth): ?>
                    <a href="<?= base_url('portal/family/add-child') ?>" class="btn-primary btn-sm" style="text-decoration:none;">➕ Add Child Profile</a>
                    <a href="<?= base_url('portal/family/profile') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">⚙ Profile &amp; Password</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="dsh-kpi-grid">
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">MY ENROLLED CHILDREN</span>
                <div class="dsh-kpi-num text-emerald"><?= count($ph['children'] ?? []) ?> Kids</div>
                <?php if ($hasCoreAuth): ?>
                    <a href="<?= base_url('portal/family/add-child') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">+ Add Another Child →</a>
                <?php endif; ?>
            </div>

            <?php if ($hasOnboarding): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">DEMO CLASS REQUESTS</span>
                <div class="dsh-kpi-num text-cyan"><?= count($allDemos) ?> Demos</div>
                <a href="<?= base_url('demo/book') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">🎯 Book New Demo →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasFinance): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label">FEE &amp; SUBSCRIPTIONS</span>
                <div class="identity-name text-emerald" style="margin:0.2rem 0;"><?= esc($ph['next_due_str'] ?? 'Active') ?></div>
                <a href="<?= base_url('finance/portal') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">💳 Open Fee &amp; Receipts →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasAssessments): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label">EARNED CERTIFICATES</span>
                <div class="dsh-kpi-num">🏆 <?= (int) ($ph['total_certs'] ?? 0) ?></div>
                <a href="<?= base_url('assessments/portal/history') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">📄 View Score Reports →</a>
            </div>
            <?php endif; ?>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.9rem;">
            <h3 style="margin:0;">My Children's Learning &amp; Demo Profiles</h3>
            <?php if ($hasTimetable): ?>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">📅 View Kids Class Schedule →</a>
            <?php endif; ?>
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
                    <?php
                        $cid       = (int) $ch['id'];
                        $kidDemos  = $demosByKid[$cid] ?? [];
                    ?>
                    <article class="dsh-action-card">
                        <div>
                            <div class="dsh-child-top">
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <span class="dsh-avatar" style="background: <?= esc($ch['avatar_color']) ?>;">
                                        <?= strtoupper(substr(trim((string) $ch['full_name']), 0, 1)) ?>
                                    </span>
                                    <div>
                                        <h3 style="margin:0; font-size:1.1rem;"><?= esc($ch['full_name']) ?></h3>
                                        <span class="muted-cell" style="font-size:0.78rem;">Age <?= (int) $ch['age'] ?> Years</span>
                                    </div>
                                </div>
                                <span class="role-badge text-emerald">Active</span>
                            </div>

                            <div style="margin:0.75rem 0; padding:0.6rem 0.8rem; border-radius:10px; background:var(--bg-canvas, #090a0f); border:1px solid var(--border-subtle, #232738);">
                                <span class="mono-label">ENROLLED COURSE</span>
                                <div class="identity-name text-emerald" style="margin-top:0.15rem;"><?= esc($ch['course_title']) ?></div>
                            </div>

                            <?php if (! empty($kidDemos)): ?>
                                <?php foreach ($kidDemos as $kd): ?>
                                    <?php
                                        $kSt     = strtolower((string) ($kd['status'] ?? 'new_lead'));
                                        $kLive   = ($kSt === 'demo_live' || ($kd['slot_status'] ?? '') === 'live');
                                        $kDone   = ($kSt === 'demo_attended' || ($kd['attendance_status'] ?? '') === 'Present');
                                        $kAbs    = ($kSt === 'reschedule_needed' || ($kd['attendance_status'] ?? '') === 'Absent');
                                        $kConv   = ($kSt === 'converted');
                                        $kSched  = ((int) ($kd['demo_batch_id'] ?? 0) > 0 || $kSt === 'demo_scheduled');
                                    ?>
                                    <div class="dsh-demo-pill-box">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.4rem;flex-wrap:wrap;">
                                            <strong class="text-cyan">🎯 Demo: <?= esc($kd['course_title']) ?></strong>
                                            <?php if ($kLive): ?>
                                                <span class="role-badge text-danger">🔴 LIVE NOW</span>
                                            <?php elseif ($kConv): ?>
                                                <span class="role-badge text-emerald">✅ Enrolled in Batch</span>
                                            <?php elseif ($kDone): ?>
                                                <span class="role-badge text-emerald">✅ Demo Attended</span>
                                            <?php elseif ($kAbs): ?>
                                                <span class="role-badge text-danger">❌ Absent / Reschedule</span>
                                            <?php elseif ($kSched): ?>
                                                <span class="role-badge text-cyan">📅 Scheduled</span>
                                            <?php else: ?>
                                                <span class="role-badge text-amber">⏳ Slot Pending</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mono-cell muted-cell" style="font-size:0.74rem;">
                                            <?php if ($kSched && ! empty($kd['slot_date'])): ?>
                                                📅 <?= esc($kd['slot_date']) ?> @ <?= esc($kd['slot_time_ampm']) ?> IST
                                                <?= ! empty($kd['teacher_name']) ? (' • 👩‍🏫 ' . esc($kd['teacher_name'])) : '' ?>
                                            <?php else: ?>
                                                ⏰ Preferred: <?= esc($kd['preferred_time_slot']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($kLive && ! empty($kd['live_link'])): ?>
                                            <a href="<?= esc($kd['live_link']) ?>" target="_blank" class="btn-primary btn-sm" style="margin-top:0.25rem;justify-content:center;text-decoration:none;">🎥 Join Live Demo Now ↗</a>
                                        <?php elseif ($kSched && ! empty($kd['live_link'])): ?>
                                            <a href="<?= esc($kd['live_link']) ?>" target="_blank" class="btn-secondary btn-sm" style="margin-top:0.25rem;justify-content:center;text-decoration:none;">🎥 Open Demo Link ↗</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="dsh-child-stats" style="margin-top:0.75rem;">
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
                            <input type="hidden" name="student_id" value="<?= $cid ?>">
                            <button type="submit" class="btn-primary btn-full" style="justify-content:center;">
                                🎓 Enter <?= esc($ch['full_name']) ?>'s Learning Hub →
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- =====================================================================
         ROLE 3: TEACHER / INSTRUCTOR DASHBOARD (ACCURATE COUNTS + TOP DEMO BOX)
         ===================================================================== -->
    <?php elseif ($role === 'teacher'): ?>
        <?php
            $th = $teacherHub ?? [];
            $nowUtc = gmdate('Y-m-d H:i:s');

            // 1. Accurate Teacher Batches & Enrolled Students Count directly from DB
            $tBatchIds = [];
            if ($hasTimetable && $db->tableExists('lms_classroom_batches')) {
                foreach ($db->table('lms_classroom_batches')->select('id')->where('teacher_user_id', $uid)->where('status', 'active')->get()->getResultArray() as $bRow) {
                    $tBatchIds[] = (int) $bRow['id'];
                }
            }
            $accurateBatchesCnt = count($tBatchIds);

            $accurateStudentsCnt = 0;
            if (! empty($tBatchIds) && $db->tableExists('lms_classroom_batch_students')) {
                $accurateStudentsCnt = (int) $db->table('lms_classroom_batch_students')
                    ->select('COUNT(DISTINCT student_user_id) AS cnt')
                    ->whereIn('cohort_id', $tBatchIds)
                    ->where('status', 'active')
                    ->get()->getRowArray()['cnt'] ?? 0;
            }

            // 2. Accurate Upcoming Live Classes Count (Only non-completed sessions >= today UTC or currently live)
            $accurateUpcomingCnt = 0;
            if ($hasTimetable && $db->tableExists('lms_class_sessions')) {
                $todayStartUtc = gmdate('Y-m-d 00:00:00');
                $accurateUpcomingCnt = (int) $db->table('lms_class_sessions')
                    ->where('assigned_teacher_id', $uid)
                    ->whereIn('status', ['scheduled', 'live'])
                    ->where('scheduled_start_utc >=', $todayStartUtc)
                    ->countAllResults();
            }

            // 3. Accurate Student Feedback Count & Average Rating (0.0 when 0 reviews!)
            $accurateFbCnt  = 0;
            $accurateAvgRat = 0.0;
            if ($hasComm && $db->tableExists('lms_teacher_feedback')) {
                $fbRows = $db->table('lms_teacher_feedback')->select('rating_stars')->where('teacher_user_id', $uid)->get()->getResultArray();
                $accurateFbCnt = count($fbRows);
                if ($accurateFbCnt > 0) {
                    $sumStars = 0;
                    foreach ($fbRows as $fr) {
                        $sumStars += (int) ($fr['rating_stars'] ?? 5);
                    }
                    $accurateAvgRat = round($sumStars / $accurateFbCnt, 1);
                }
            }

            // 4. Accurate Assigned Demo Classes (Only demo slots that have students assigned!)
            $assignedDemoSlots = [];
            if ($hasOnboarding && $db->tableExists('lms_demo_batches') && $db->tableExists('lms_demo_leads')) {
                $rawSlots = $db->table('lms_demo_batches')
                    ->where('teacher_user_id', $uid)
                    ->whereIn('status', ['scheduled', 'live'])
                    ->orderBy('demo_date', 'ASC')
                    ->get()->getResultArray();
                foreach ($rawSlots as $sl) {
                    $sBid = (int) $sl['id'];
                    $stuCnt = (int) $db->table('lms_demo_leads')->where('demo_batch_id', $sBid)->countAllResults();
                    if ($stuCnt > 0) {
                        $sl['students_count'] = $stuCnt;
                        $assignedDemoSlots[]  = $sl;
                    }
                }
            }
            $accurateDemosCnt = count($assignedDemoSlots);
        ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">👩‍🏫 INSTRUCTOR WORKSPACE</span>
                <h2 style="margin:0.25rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.85rem;">Manage your live classroom batches, assigned demo classes, question banks, and 1-to-1 student chat.</p>
            </div>
            <div class="action-btn-group">
                <?php if ($hasOnboarding): ?>
                    <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="btn-primary btn-sm" style="text-decoration:none;">🎯 My Demo Classes (<?= $accurateDemosCnt ?>)</a>
                <?php endif; ?>
                <?php if ($hasTimetable): ?>
                    <a href="<?= base_url('timetable/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">🎥 Live Class Console →</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- TOP HIGH-PRIORITY ASSIGNED DEMO CLASSES BANNER FOR TEACHER -->
        <?php if ($hasOnboarding && ! empty($assignedDemoSlots)): ?>
            <section class="console-card" style="margin-bottom:1.25rem; border:2px solid #10b981 !important;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                    <div>
                        <span class="role-badge text-emerald">🎯 ASSIGNED LIVE DEMO CLASSES (<?= $accurateDemosCnt ?> ACTIVE)</span>
                        <h3 style="margin:0.25rem 0 0;font-size:1.08rem;">You Have Upcoming Trial / Demo Classes Assigned by Admin</h3>
                    </div>
                    <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="btn-primary btn-sm" style="text-decoration:none;">▶ Open Demo Class Console →</a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:0.75rem;margin-top:0.85rem;">
                    <?php foreach ($assignedDemoSlots as $dsl): ?>
                        <div class="panel-card" style="padding:0.8rem 1rem !important;background:var(--bg-canvas);">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:0.5rem;">
                                <strong><?= esc($dsl['batch_title']) ?></strong>
                                <span class="role-badge text-cyan">🎓 <?= (int) $dsl['students_count'] ?> Kids</span>
                            </div>
                            <div class="mono-cell text-emerald" style="margin-top:0.25rem;">
                                📅 <?= esc($dsl['demo_date']) ?> • ⏰ <?= esc(date('h:i A', strtotime('2026-01-01 ' . ($dsl['start_time_ist'] ?? '17:00')))) ?> IST
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 4 Countable Teacher KPI Cards -->
        <div class="dsh-kpi-grid">
            <?php if ($hasOnboarding): ?>
            <div class="dsh-kpi-card" style="border-color:rgba(16,185,129,0.45);">
                <span class="mono-label text-emerald">ASSIGNED DEMO CLASSES</span>
                <div class="dsh-kpi-num text-emerald"><?= $accurateDemosCnt ?> Demos</div>
                <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="btn-primary btn-sm" style="align-self:flex-start; text-decoration:none;">👩‍🏫 Start / Manage Demos →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasTimetable): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">UPCOMING LIVE CLASSES</span>
                <div class="dsh-kpi-num text-cyan"><?= $accurateUpcomingCnt ?> Classes</div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">🎥 Open Class Schedule →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">MY BATCHES &amp; STUDENTS</span>
                <div class="dsh-kpi-num"><?= $accurateBatchesCnt ?> Batches <small class="mono-cell text-cyan">(<?= $accurateStudentsCnt ?> Kids)</small></div>
                <a href="<?= base_url('timetable/portal?tab=rosters') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">🏫 View Batch Rosters →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasComm): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">STUDENT FEEDBACK</span>
                <div class="dsh-kpi-num text-emerald">
                    <?= $accurateFbCnt > 0 ? ('★ ' . number_format($accurateAvgRat, 1)) : '0.0 ★' ?>
                </div>
                <a href="<?= base_url('chat/feedback') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">
                    ⭐ <?= $accurateFbCnt ?> Review(s) →
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="dsh-cards-grid">
            <?php if ($hasOnboarding): ?>
            <article class="dsh-action-card" style="border-color:rgba(16,185,129,0.4);">
                <div>
                    <span class="role-badge text-emerald">🎯 LIVE DEMO CONSOLE</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Assigned Demo Classes (<?= $accurateDemosCnt ?>)</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Launch live trial sessions, mark demo attendance, and submit evaluation remarks to Admin.</p>
                </div>
                <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">👩‍🏫 Open My Demo Classes →</a>
            </article>
            <?php endif; ?>

            <?php if ($hasTimetable): ?>
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">🎥 LIVE CLASSROOM</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Start / End Class &amp; Roll Call</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Confirm meeting link, share study PDFs, mark student attendance, or request a substitute teacher.</p>
                </div>
                <a href="<?= base_url('timetable/portal') ?>" class="btn-primary btn-full" style="justify-content:center; text-decoration:none;">🎥 Open Live Class Console →</a>
            </article>
            <?php endif; ?>

            <?php if ($hasAssessments): ?>
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-emerald">🗃 COURSE QUESTION BANK</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">Add Single or Bulk CSV Questions</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Add math questions to your assigned courses or bulk-import questions via CSV.</p>
                </div>
                <a href="<?= base_url('addons/lms-assessments/question-bank') ?>" class="btn-secondary btn-full" style="justify-content:center; text-decoration:none;">🗃 Open Question Bank →</a>
            </article>
            <?php endif; ?>

            <?php if ($hasComm || $hasFinance): ?>
            <article class="dsh-action-card">
                <div>
                    <span class="role-badge text-cyan">💬 CHAT &amp; SALARY SLIPS</span>
                    <h3 style="margin:0.5rem 0 0.25rem;">1-to-1 Student Chat &amp; Payroll</h3>
                    <p class="muted-cell" style="margin:0; font-size:0.85rem;">Answer student doubts in real time or view your credited monthly salary slips.</p>
                </div>
                <div class="action-btn-group">
                    <?php if ($hasComm): ?>
                        <a href="<?= base_url('chat/portal') ?>" class="btn-primary btn-sm" style="flex:1; justify-content:center; text-decoration:none;">💬 Student Chat</a>
                    <?php endif; ?>
                    <?php if ($hasFinance): ?>
                        <a href="<?= base_url('finance/my-payouts') ?>" class="btn-secondary btn-sm" style="flex:1; justify-content:center; text-decoration:none;">💵 Salary Slips</a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endif; ?>
        </div>

    <!-- =====================================================================
         ROLE 4: SUPER ADMIN & ADMIN EXECUTIVE COMMAND DASHBOARD
         ===================================================================== -->
    <?php else: ?>
        <?php $ah = $adminHub ?? []; ?>
        <div class="dsh-hero">
            <div>
                <span class="role-badge text-emerald">🛡 ACADEMY COMMAND CENTER</span>
                <h2 style="margin:0.25rem 0 0.1rem;">Welcome, <?= esc($userName) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.85rem;">Real-time metrics across active add-ons: students, live batches, fee revenue, demo admissions, and safety moderation.</p>
            </div>
            <div class="action-btn-group">
                <?php if ($hasHierarchy): ?>
                    <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="btn-primary btn-sm" style="text-decoration:none;">➕ Create Course</a>
                <?php endif; ?>
                <?php if ($hasTimetable): ?>
                    <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">🏫 Create Live Batch</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="dsh-kpi-grid">
            <?php if ($hasCoreAuth): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">ENROLLED STUDENTS &amp; PARENTS</span>
                <div class="dsh-kpi-num text-emerald"><?= (int) ($ah['students_count'] ?? 0) ?> Students</div>
                <a href="<?= base_url('addons/core-lms-auth/directory') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">👨‍👩‍👧‍👦 <?= (int) ($ah['parents_count'] ?? 0) ?> Parents Directory →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasTimetable): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">TEACHERS &amp; LIVE BATCHES</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ah['cohorts_count'] ?? 0) ?> Batches</div>
                <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">👩‍🏫 <?= (int) ($ah['teachers_count'] ?? 0) ?> Active Teachers →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasFinance): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">VERIFIED FEE REVENUE</span>
                <div class="dsh-kpi-num text-emerald">₹<?= number_format((float) ($ah['revenue_inr'] ?? 0), 0) ?></div>
                <a href="<?= base_url('addons/lms-finance') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">🧾 <?= (int) ($ah['receipts_count'] ?? 0) ?> Paid Receipts →</a>
            </div>

            <div class="dsh-kpi-card">
                <span class="mono-label text-amber">OFFLINE FEE APPROVALS</span>
                <div class="dsh-kpi-num <?= (int) ($ah['pending_approvals'] ?? 0) > 0 ? 'text-amber' : '' ?>"><?= (int) ($ah['pending_approvals'] ?? 0) ?> Pending</div>
                <a href="<?= base_url('addons/lms-finance') ?>" class="btn-action text-amber" style="align-self:flex-start; text-decoration:none;">✅ Review Proofs →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasOnboarding): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-cyan">DEMO INQUIRIES PIPELINE</span>
                <div class="dsh-kpi-num text-cyan"><?= (int) ($ah['new_demo_leads'] ?? 0) ?> Leads</div>
                <a href="<?= base_url('addons/lms-onboarding') ?>" class="btn-action text-cyan" style="align-self:flex-start; text-decoration:none;">🚀 Convert to Students →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasTimetable): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label">SUBSTITUTE REQUESTS</span>
                <div class="dsh-kpi-num <?= (int) ($ah['pending_substitutes'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= (int) ($ah['pending_substitutes'] ?? 0) ?> Pending</div>
                <a href="<?= base_url('addons/lms-timetable/substitutes') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">⇄ Substitute Inbox →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasComm): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label">CHAT SAFETY MODERATION</span>
                <div class="dsh-kpi-num <?= (int) ($ah['flagged_chats'] ?? 0) > 0 ? 'text-danger' : 'text-emerald' ?>"><?= (int) ($ah['flagged_chats'] ?? 0) ?> Flagged</div>
                <a href="<?= base_url('addons/lms-communication/moderation') ?>" class="btn-action" style="align-self:flex-start; text-decoration:none;">🛡 Moderation Queue →</a>
            </div>
            <?php endif; ?>

            <?php if ($hasHierarchy || $hasAssessments): ?>
            <div class="dsh-kpi-card">
                <span class="mono-label text-emerald">COURSES &amp; EXAMS</span>
                <div class="dsh-kpi-num"><?= (int) ($ah['courses_count'] ?? 0) ?> Courses</div>
                <a href="<?= base_url('addons/lms-assessments/exams') ?>" class="btn-action text-emerald" style="align-self:flex-start; text-decoration:none;">📝 <?= (int) ($ah['exams_count'] ?? 0) ?> Active Exams →</a>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
