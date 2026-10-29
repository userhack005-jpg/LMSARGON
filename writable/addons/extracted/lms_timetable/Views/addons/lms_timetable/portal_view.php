<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $tTab         = $teacherTab ?? 'classes';
    $kidsMap      = $parentChildrenMap ?? [];
    $hasMultiKids = ! empty($isParentOverview) && count($kidsMap) > 1;
?>
<div class="ltt-wrapper" id="js-ltt-portal-root"
     data-start-url="<?= base_url('timetable/start-class') ?>"
     data-end-url="<?= base_url('timetable/end-class') ?>"
     data-signal-url="<?= base_url('assets/addons/lms_timetable/live_signal.json') ?>"
     data-viewer-role="<?= esc($effectiveRole ?? 'student') ?>"
     data-enrolled-student-ids=",<?= esc($enrolledStuIdsCsv ?? '') ?>,">

    <div id="js-ltt-live-toast" class="ltt-live-toast is-hidden"></div>

    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
        <div class="ltt-compact-bar" style="border-color: rgba(6, 182, 212, 0.45);">
            <div>
                <span class="mono-label text-cyan">ADMIN ROLE PORTAL PREVIEWER</span>
                <div class="muted-cell" style="font-size:0.8rem;">Switch between Teacher Console, Student Schedule, and Parent Family View:</div>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('timetable/portal?sim_role=teacher') ?>" class="btn-action <?= ! empty($isTeacherMode) ? 'is-active text-emerald' : '' ?>" style="text-decoration:none;">👩‍🏫 Teacher Console</a>
                <a href="<?= base_url('timetable/portal?sim_role=student') ?>" class="btn-action <?= empty($isTeacherMode) && empty($isParentOverview) ? 'is-active text-emerald' : '' ?>" style="text-decoration:none;">🎓 Student View</a>
                <a href="<?= base_url('timetable/portal?sim_role=parent') ?>" class="btn-action <?= ! empty($isParentOverview) ? 'is-active text-emerald' : '' ?>" style="text-decoration:none;">👨‍👩‍👧‍👦 Parent View</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- =====================================================================
         ROLE VIEW A: INSTRUCTOR COMMAND CENTER (`isTeacherMode === true`)
         ===================================================================== -->
    <?php if (! empty($isTeacherMode)): ?>
        <div id="js-ltt-sub-poll-root"
             data-poll-url="<?= base_url('timetable/live-sub-threads-json') ?>"
             data-reply-url="<?= base_url('timetable/reply-sub-request') ?>"
             data-is-admin="0"></div>

        <div class="ltt-teacher-hero">
            <div>
                <span class="role-badge text-emerald">👩‍🏫 INSTRUCTOR LIVE CLASSROOM</span>
                <h2 style="margin:0.25rem 0 0.15rem;">Welcome, <?= esc($user['full_name'] ?? ($user['name'] ?? 'Instructor')) ?></h2>
                <p class="muted-cell" style="margin:0; font-size:0.85rem;">Timezone: <strong><?= esc($userTz) ?></strong></p>
            </div>
            <div class="settings-tabs" style="margin:0;">
                <a href="<?= base_url('timetable/portal?tab=classes') ?>" class="settings-tab js-t-tab-btn <?= $tTab === 'classes' ? 'is-active' : '' ?>" data-target="t-pane-classes" style="text-decoration:none;">
                    <span>🎥 My Live Classes (<?= count($mySessions ?? []) ?>)</span>
                </a>
                <a href="<?= base_url('timetable/portal?tab=rosters') ?>" class="settings-tab js-t-tab-btn <?= $tTab === 'rosters' ? 'is-active' : '' ?>" data-target="t-pane-rosters" style="text-decoration:none;">
                    <span>🏫 My Batches (<?= count($myCohorts ?? []) ?>)</span>
                </a>
                <a href="<?= base_url('timetable/portal?tab=substitute') ?>" class="settings-tab js-t-tab-btn <?= $tTab === 'substitute' ? 'is-active' : '' ?>" data-target="t-pane-substitute" style="text-decoration:none;">
                    <span>📩 Leave &amp; Substitute</span>
                    <span class="ltt-unread-badge is-hidden" id="js-nav-sub-badge">NEW</span>
                </a>
            </div>
        </div>

        <!-- TEACHER TAB 1: LIVE CLASSES & 2-STEP START WIZARD -->
        <div id="t-pane-classes" class="js-t-pane <?= $tTab === 'classes' ? '' : 'is-hidden' ?>">
            <?php if (empty($mySessions)): ?>
                <section class="console-card" style="text-align:center; padding:3rem 1.5rem;">
                    <h3>No Live Class Sessions Assigned Yet</h3>
                    <p class="muted-cell">Your scheduled batch classes will appear here.</p>
                </section>
            <?php else: ?>
                <div class="ltt-session-cards-grid">
                    <?php foreach ($mySessions as $sess): ?>
                        <?php
                            $sid       = (int) $sess['id'];
                            $status    = strtolower((string) ($sess['status'] ?? 'scheduled'));
                            $isLive    = ($status === 'live');
                            $isDone    = ($status === 'completed');
                            $rosterArr = $sess['roster_for_rollcall'] ?? [];
                        ?>
                        <article class="ltt-session-card <?= $isLive ? 'is-live-card' : '' ?>" id="js-sess-card-<?= $sid ?>">
                            <div>
                                <div class="ltt-flex-between">
                                    <span class="role-badge <?= ! empty($sess['is_today']) ? 'text-emerald' : '' ?>">
                                        <?= ! empty($sess['is_today']) ? '🌟 TODAY\'S CLASS' : esc($sess['cohort_code']) ?>
                                    </span>
                                    <div id="js-sess-status-badge-<?= $sid ?>">
                                        <?php if ($isLive): ?>
                                            <span class="ltt-badge-live">🔴 LIVE IN PROGRESS</span>
                                        <?php elseif ($isDone): ?>
                                            <span class="role-badge text-emerald">✓ <?= esc($sess['teacher_attendance_status']) ?> (<?= (int) $sess['duration_minutes'] ?>m)</span>
                                        <?php else: ?>
                                            <span class="role-badge text-cyan">⏳ SCHEDULED</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <h3 class="ltt-scard-title"><?= esc($sess['cohort_title']) ?></h3>

                                <div class="ltt-scard-meta-box">
                                    <div>
                                        <span class="mono-label">LOCAL TIME (<?= esc($userTz) ?>)</span>
                                        <div class="identity-name text-cyan">⏰ <?= esc($sess['local_start_display']) ?></div>
                                    </div>
                                    <div>
                                        <span class="mono-label">STUDENTS</span>
                                        <div class="identity-name">🎓 <?= count($rosterArr) ?></div>
                                    </div>
                                </div>

                                <div class="ltt-scard-links" id="js-sess-links-<?= $sid ?>">
                                    <a href="<?= esc($sess['meeting_link']) ?>" target="_blank" class="mono-cell text-cyan" id="js-sess-meet-link-<?= $sid ?>">
                                        🎥 Classroom Link ↗
                                    </a>
                                    <?php if (! empty($sess['resolved_material_pdf'])): ?>
                                        <a href="<?= esc($sess['resolved_material_pdf']) ?>" target="_blank" class="mono-cell text-emerald">
                                            📄 Attached Study PDF ↗
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form action="<?= base_url('timetable/share-assignment') ?>" method="post" class="ltt-scard-hw-bar">
                                <?= csrf_field() ?>
                                <input type="hidden" name="session_id" value="<?= $sid ?>">
                                <input type="text" name="assignment_note" class="field-input" placeholder="Homework / practice note..." value="<?= esc($sess['assignment_note'] ?? '') ?>">
                                <button type="submit" class="btn-action">Save Note</button>
                            </form>

                            <div class="ltt-scard-footer" id="js-sess-action-wrap-<?= $sid ?>">
                                <?php if ($isLive): ?>
                                    <div style="display:flex; gap:0.6rem; width:100%;">
                                        <a href="<?= esc($sess['meeting_link']) ?>" target="_blank" class="btn-secondary" style="flex:1; justify-content:center;">🎥 Open Room ↗</a>
                                        <form action="<?= base_url('timetable/end-class') ?>" method="post" class="js-end-class-form" style="flex:1.2; margin:0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= $sid ?>">
                                            <button type="submit" class="ltt-btn-end-red">⏹ End Live Class</button>
                                        </form>
                                    </div>
                                <?php elseif ($isDone): ?>
                                    <div class="ltt-done-strip">
                                        <span>✓ Completed (<?= (int) $sess['duration_minutes'] ?>m)</span>
                                        <button type="button" class="btn-action js-open-start-wizard" data-session="<?= esc(json_encode($sess), 'attr') ?>">✎ Edit Attendance</button>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="ltt-btn-start-green js-open-start-wizard" data-session="<?= esc(json_encode($sess), 'attr') ?>">
                                        ▶ Start Live Class (Confirm Link &amp; Roll Call) →
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TEACHER TAB 2: MY CLASSROOM BATCHES -->
        <div id="t-pane-rosters" class="js-t-pane <?= $tTab === 'rosters' ? '' : 'is-hidden' ?>">
            <div class="grid-2-col">
                <?php foreach (($myCohorts ?? []) as $mc): ?>
                    <section class="console-card">
                        <div class="card-head ltt-flex-between">
                            <div>
                                <span class="mono-label text-emerald"><?= esc($mc['leaf_breadcrumb']) ?></span>
                                <h3><?= esc($mc['cohort_title']) ?></h3>
                            </div>
                            <span class="role-badge">🎓 <?= (int) $mc['enrolled_count'] ?> Students</span>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>Student Name</th><th>Email</th></tr></thead>
                                <tbody>
                                    <?php foreach (($mc['enrolled_students'] ?? []) as $es): ?>
                                        <tr>
                                            <td class="identity-name">🎓 <?= esc($es['student_name']) ?></td>
                                            <td class="mono-cell muted-cell"><?= esc($es['email']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TEACHER TAB 3: LEAVE & SUBSTITUTE REQUEST -->
        <div id="t-pane-substitute" class="js-t-pane <?= $tTab === 'substitute' ? '' : 'is-hidden' ?>">
            <div class="ltt-main-grid">
                <section class="console-card">
                    <div class="card-head"><h3>Request Substitute Teacher</h3></div>
                    <form action="<?= base_url('timetable/submit-sub-request') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="field-group">
                            <label class="field-label"><span>Select Upcoming Class(es) *</span></label>
                            <select name="session_ids[]" class="field-select" multiple size="5" required>
                                <?php foreach (($mySessions ?? []) as $sItem): ?>
                                    <?php if (($sItem['status'] ?? '') === 'completed') continue; ?>
                                    <option value="<?= (int) $sItem['id'] ?>"><?= esc($sItem['cohort_title']) ?> | <?= esc($sItem['local_start_display']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Reason &amp; Handover Note *</span></label>
                            <textarea name="reason_note" class="field-input" rows="3" required placeholder="Topics to cover..."></textarea>
                        </div>
                        <button type="submit" class="btn-primary btn-full">📩 Submit Request</button>
                    </form>
                </section>

                <section class="console-card">
                    <div class="card-head"><h3>My Requests &amp; Live Admin Replies</h3></div>
                    <div class="ltt-req-list" id="js-ltt-sub-threads-container">
                        <?php foreach (($mySubRequests ?? []) as $req): ?>
                            <div class="panel-card ltt-req-card" data-req-id="<?= (int) $req['id'] ?>">
                                <div class="ltt-flex-between">
                                    <strong>Substitute: <?= esc($req['substitute_name']) ?></strong>
                                    <span class="role-badge"><?= strtoupper(esc($req['status'])) ?></span>
                                </div>
                                <div class="ltt-thread-box" id="js-sub-thread-msgs-<?= (int) $req['id'] ?>">
                                    <?php foreach (($req['thread'] ?? []) as $msg): ?>
                                        <div class="ltt-thread-msg <?= ($msg['sender_role'] ?? '') === 'admin' ? 'is-admin-msg' : 'is-teacher-msg' ?>">
                                            <strong><?= esc($msg['sender_name']) ?>:</strong>
                                            <span><?= esc($msg['message_text']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <form action="<?= base_url('timetable/reply-sub-request') ?>" method="post" class="ltt-mini-row js-ajax-sub-reply-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                    <input type="text" name="message_text" class="field-input" placeholder="Reply to Admin..." required>
                                    <button type="submit" class="btn-secondary btn-sm">Send</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>

        <!-- 2-STEP START-CLASS WIZARD MODAL -->
        <div class="ltt-wizard-modal is-hidden" id="js-ltt-start-wizard-modal">
            <div class="ltt-wizard-dialog panel-card">
                <div class="ltt-wizard-head">
                    <div>
                        <span class="mono-label text-emerald" id="js-wiz-step-indicator">STEP 1 OF 2 // CONFIRM LINK &amp; PDF</span>
                        <h3 id="js-wiz-session-title" style="margin:0.2rem 0 0;">Launch Live Class</h3>
                    </div>
                    <button type="button" class="btn-action" id="js-wiz-close-btn">✕ Cancel</button>
                </div>

                <form action="<?= base_url('timetable/start-class') ?>" method="post" enctype="multipart/form-data" id="js-wiz-start-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="session_id" id="js-wiz-session-id" value="0">

                    <div id="js-wiz-step-1" class="ltt-wiz-body">
                        <div class="field-group">
                            <label class="field-label"><span class="text-emerald">1. Confirm or Update Today's Live Meeting Link *</span></label>
                            <input type="url" name="meeting_link" id="js-wiz-meeting-link" class="field-input mono-input" required>
                        </div>
                        <div class="grid-2-col">
                            <div class="field-group">
                                <label class="field-label"><span>2. Upload Today's Class PDF (Optional)</span></label>
                                <input type="file" name="material_pdf_file" class="field-input" accept=".pdf,.zip,.png,.jpg,.doc,.docx">
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Or Material Link</span></label>
                                <input type="text" name="material_pdf_url" id="js-wiz-pdf-url" class="field-input mono-input">
                                <div id="js-wiz-existing-pdf-box" style="margin-top:0.35rem;"></div>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Homework / Practice Note for Students</span></label>
                            <input type="text" name="assignment_note" id="js-wiz-hw-note" class="field-input">
                        </div>
                        <div class="ltt-wiz-footer">
                            <button type="button" class="btn-secondary" id="js-wiz-cancel-1">Cancel</button>
                            <button type="button" class="btn-primary" id="js-wiz-goto-step-2">Next: Student Roll Call (Step 2/2) →</button>
                        </div>
                    </div>

                    <div id="js-wiz-step-2" class="ltt-wiz-body is-hidden">
                        <div class="ltt-rollcall-top-bar">
                            <div>
                                <strong class="text-emerald">✓ All Enrolled Students Checked PRESENT by Default</strong>
                                <div class="field-hint">Uncheck any absent student below before launching the class.</div>
                            </div>
                            <button type="submit" class="ltt-btn-start-green js-wiz-final-submit" style="width:auto; padding:0.55rem 1.15rem;">
                                🚀 Submit Attendance &amp; Start Class →
                            </button>
                        </div>
                        <div class="ltt-rollcall-list" id="js-wiz-rollcall-list"></div>
                        <div class="ltt-wiz-footer">
                            <button type="button" class="btn-secondary" id="js-wiz-back-step-1">← Back</button>
                            <button type="submit" class="ltt-btn-start-green js-wiz-final-submit" style="width:auto; padding:0.65rem 1.4rem;">
                                🚀 Submit Attendance &amp; Start Class →
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <!-- =====================================================================
         ROLE VIEW B: STUDENT / PARENT MULTI-CHILD SCHEDULE VIEW
         ===================================================================== -->
    <?php else: ?>
        <section class="console-card">
            <div class="card-head ltt-flex-between">
                <div>
                    <h3><?= ! empty($isParentOverview) ? '👨‍👩‍👧‍👦 Family Live Class Schedule' : '🎓 My Live Class Schedule' ?></h3>
                    <p class="field-hint" style="margin:0;">Displayed in your local timezone: <strong><?= esc($userTz) ?></strong></p>
                </div>

                <?php if ($hasMultiKids): ?>
                    <div class="action-btn-group" id="js-parent-child-filter-bar">
                        <button type="button" class="btn-action is-active js-filter-child-sched" data-child-id="all">
                            👨‍👩‍👧‍👦 All Children (<?= count($kidsMap) ?>)
                        </button>
                        <?php foreach ($kidsMap as $kidId => $kidName): ?>
                            <button type="button" class="btn-action js-filter-child-sched" data-child-id="<?= (int) $kidId ?>">
                                🎓 <?= esc($kidName) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if (! empty($isParentOverview)): ?>
                                <th>Child</th>
                            <?php endif; ?>
                            <th>Classroom Batch</th>
                            <th>Teacher</th>
                            <th>Local Time (<?= esc($userTz) ?>)</th>
                            <th>UTC</th>
                            <th>Study PDF &amp; Note</th>
                            <th>Status / Join</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mySessions)): ?>
                            <tr><td colspan="<?= ! empty($isParentOverview) ? '7' : '6' ?>" class="muted-cell" style="text-align:center; padding:2rem;">No live class sessions scheduled yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($mySessions as $sess): ?>
                                <?php
                                    $sid         = (int) $sess['id'];
                                    $matchedKids = $sess['matched_children'] ?? [];
                                    $kidIdsCsv   = implode(',', array_keys($matchedKids));
                                ?>
                                <tr class="js-student-sched-row" id="js-stu-sess-row-<?= $sid ?>" data-session-id="<?= $sid ?>" data-child-ids=",<?= esc($kidIdsCsv) ?>,">
                                    <?php if (! empty($isParentOverview)): ?>
                                        <td>
                                            <?php foreach ($matchedKids as $kId => $kName): ?>
                                                <span class="role-badge text-emerald">🎓 <?= esc($kName) ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="identity-name"><?= esc($sess['cohort_title']) ?></td>
                                    <td class="identity-name">👩‍🏫 <?= esc($sess['assigned_teacher_name']) ?></td>
                                    <td class="text-emerald mono-cell"><strong><?= esc($sess['local_start_display']) ?></strong></td>
                                    <td class="muted-cell mono-cell"><?= esc($sess['scheduled_start_utc']) ?></td>
                                    <td>
                                        <?php if (! empty($sess['resolved_material_pdf'])): ?>
                                            <div><a href="<?= esc($sess['resolved_material_pdf']) ?>" target="_blank" class="mono-cell text-cyan">📄 Class PDF ↗</a></div>
                                        <?php endif; ?>
                                        <?php if (! empty($sess['assignment_note'])): ?>
                                            <div class="mono-cell text-emerald">📝 <?= esc($sess['assignment_note']) ?></div>
                                        <?php endif; ?>
                                        <?php if (empty($sess['resolved_material_pdf']) && empty($sess['assignment_note'])): ?>
                                            <span class="muted-cell">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td id="js-stu-sess-status-<?= $sid ?>">
                                        <?php if (($sess['status'] ?? '') === 'completed'): ?>
                                            <span class="role-badge text-emerald">✓ COMPLETED</span>
                                        <?php elseif (($sess['status'] ?? '') === 'live'): ?>
                                            <a href="<?= esc($sess['meeting_link']) ?>" target="_blank" class="ltt-btn-end-red" style="text-decoration:none; display:inline-flex; width:auto; padding:0.42rem 0.9rem;">
                                                🔴 LIVE NOW — Join ↗
                                            </a>
                                        <?php else: ?>
                                            <span class="role-badge">SCHEDULED</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Enrolled-Student-Only Instant "Class Started!" Popup Modal -->
        <div class="ltt-wizard-modal is-hidden" id="js-ltt-student-live-popup">
            <div class="ltt-wizard-dialog panel-card" style="max-width:500px; text-align:center; border:2px solid #10b981 !important;">
                <span class="ltt-badge-live" style="margin-bottom:0.75rem;">🔴 YOUR LIVE CLASS HAS STARTED!</span>
                <h2 id="js-live-pop-batch" style="margin:0.25rem 0;">Classroom Batch</h2>
                <p class="mono-cell text-cyan" id="js-live-pop-teacher" style="margin-bottom:1rem;"></p>
                <div id="js-live-pop-pdf-wrap" style="margin-bottom:1rem;"></div>
                <div style="display:flex; gap:0.75rem; justify-content:center;">
                    <a href="#" target="_blank" id="js-live-pop-join-btn" class="ltt-btn-start-green" style="text-decoration:none; width:auto; padding:0.75rem 1.6rem;">
                        🎥 Join Live Class Now ↗
                    </a>
                    <button type="button" class="btn-secondary" id="js-live-pop-dismiss">Dismiss</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
