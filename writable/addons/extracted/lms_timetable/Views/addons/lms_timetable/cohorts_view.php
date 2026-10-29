<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="ltt-wrapper" id="js-ltt-sub-poll-root" data-poll-url="<?= base_url('addons/lms-timetable/live-sub-threads-json') ?>">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- ZERO DOUBLE WORK: Auto-Queue of Subscribed Students Waiting for a Live Batch -->
    <?php if (! empty($unassignedQueue) && ! empty($cohorts)): ?>
    <section class="console-card ltt-section-gap" style="border: 1px solid #10b981;">
        <div class="card-head ltt-flex-between">
            <div>
                <span class="role-badge text-emerald">⚡ AUTO-QUEUE: SUBSCRIBED STUDENTS WAITING FOR LIVE BATCH (<?= count($unassignedQueue) ?>)</span>
                <h3 style="margin-top:0.25rem;">1-Click Place Newly Enrolled Students into a Live Classroom Batch</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subscribed Student</th>
                        <th>Enrolled Course</th>
                        <th>Place into Live Classroom Batch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassignedQueue as $uq): ?>
                        <tr>
                            <td class="identity-name">🎓 <?= esc($uq['student_name']) ?></td>
                            <td class="mono-cell text-emerald"><?= esc($uq['course_title']) ?></td>
                            <td>
                                <form action="<?= base_url('addons/lms-timetable/assign-cohort-student') ?>" method="post" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="student_ids[]" value="<?= (int) $uq['student_id'] ?>">
                                    <select name="cohort_id" class="field-select" style="max-width:320px;" required>
                                        <?php foreach ($cohorts as $cid => $cInfo): ?>
                                            <option value="<?= (int) $cid ?>" <?= (int) $cInfo['leaf_node_id'] === (int) $uq['leaf_node_id'] ? 'selected' : '' ?>>
                                                <?= esc($cInfo['cohort_title']) ?> (👩‍🏫 <?= esc($cInfo['teacher_name']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-primary btn-sm">✓ Assign to Batch</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <div class="ltt-main-grid ltt-section-gap">
        <div class="ltt-stack">
            <section class="console-card ltt-card-gap">
                <div class="card-head ltt-flex-between">
                    <div>
                        <h3 id="js-cohort-form-title">Create Live Classroom Batch &amp; Auto-Schedule</h3>
                        <p class="field-hint" style="margin:0;">Creates a live classroom batch under any course and automatically generates its class schedule.</p>
                    </div>
                    <button type="button" class="btn-action is-hidden" id="js-cohort-reset-btn">✕ Reset</button>
                </div>

                <form action="<?= base_url('addons/lms-timetable/save-cohort') ?>" method="post" enctype="multipart/form-data" id="js-cohort-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="coh_id" value="0">

                    <div class="field-group">
                        <label class="field-label"><span>Select Course *</span></label>
                        <select name="leaf_node_id" id="coh_leaf_id" class="field-select" required>
                            <option value="">-- Choose Course --</option>
                            <?php foreach (($leafNodes ?? []) as $lid => $ln): ?>
                                <option value="<?= (int) $lid ?>"><?= esc($ln['breadcrumb_path']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Batch Name *</span></label>
                            <input type="text" name="cohort_title" id="coh_title" class="field-input" placeholder="e.g. Abacus Level 1 — Morning Batch A" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Assign Teacher *</span></label>
                            <select name="teacher_user_id" id="coh_teacher_id" class="field-select" required>
                                <option value="">-- Choose Teacher --</option>
                                <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                                    <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tRow['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Start Date *</span></label>
                            <input type="date" name="start_date" id="coh_start" class="field-input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>End Date *</span></label>
                            <input type="date" name="end_date" id="coh_end" class="field-input" value="<?= date('Y-m-d', strtotime('+60 days')) ?>" required>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Class Days</span></label>
                        <div class="ltt-weekday-Checks">
                            <?php foreach ([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $dNum => $dLbl): ?>
                                <label class="ltt-day-chip">
                                    <input type="checkbox" name="weekdays[]" value="<?= $dNum ?>" <?= in_array($dNum, [1,3,5], true) ? 'checked' : '' ?>>
                                    <span><?= $dLbl ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid-3-col">
                        <div class="field-group">
                            <label class="field-label"><span>Time (IST)</span></label>
                            <input type="time" name="start_time_ist" id="coh_time" class="field-input" value="17:00">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Duration (Mins)</span></label>
                            <input type="number" name="class_duration_min" id="coh_dur" class="field-input mono-input" value="60">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Max Students</span></label>
                            <input type="number" name="max_seats" id="coh_seats" class="field-input mono-input" value="20">
                        </div>
                    </div>

                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Default Meeting Link</span></label>
                            <input type="url" name="meeting_link" id="coh_link" class="field-input mono-input" value="https://meet.google.com/new">
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Batch Study PDF (Optional)</span></label>
                            <input type="file" name="material_pdf_file" class="field-input" accept=".pdf,.zip,.png,.jpg,.doc,.docx">
                            <input type="hidden" name="material_pdf_url" id="coh_pdf" value="">
                            <input type="hidden" name="cohort_code" id="coh_code" value="">
                        </div>
                    </div>

                    <div class="field-group">
                        <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                            <input type="checkbox" name="auto_generate_sessions" value="1" checked>
                            <strong class="text-emerald">📅 Automatically Generate Class Timetable for Selected Days</strong>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary btn-full">🏫 Save Live Batch &amp; Generate Timetable</button>
                </form>
            </section>

            <section class="console-card">
                <div class="card-head">
                    <div>
                        <h3>Add Students to Any Live Batch</h3>
                    </div>
                </div>
                <form action="<?= base_url('addons/lms-timetable/assign-cohort-student') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="field-group">
                        <label class="field-label"><span>Select Live Batch *</span></label>
                        <select name="cohort_id" class="field-select" required>
                            <?php foreach (($cohorts ?? []) as $cid => $cInfo): ?>
                                <option value="<?= (int) $cid ?>"><?= esc($cInfo['cohort_title']) ?> (👩‍🏫 <?= esc($cInfo['teacher_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <div class="ltt-flex-between" style="margin-bottom:0.4rem;">
                            <label class="field-label" style="margin:0;"><span>Tick Student(s) *</span></label>
                            <input type="search" class="field-input js-stu-filter-input" data-target="js-cohort-stu-list" placeholder="Search student..." style="width:160px; padding:0.25rem 0.55rem; font-size:0.78rem;">
                        </div>
                        <div class="ltt-stu-checkbox-box" id="js-cohort-stu-list">
                            <?php foreach (($students ?? []) as $sid => $sRow): ?>
                                <label class="ltt-stu-cb-row" data-search="<?= esc(strtolower($sRow['display_name'])) ?>">
                                    <input type="checkbox" name="student_ids[]" value="<?= (int) $sid ?>">
                                    <strong>🎓 <?= esc($sRow['display_name']) ?></strong>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-secondary btn-full">🎓 Add Checked Students to Batch</button>
                </form>
            </section>
        </div>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Active Live Classroom Batches (<?= count($cohorts ?? []) ?>)</h3>
                </div>
            </div>

            <?php if (empty($cohorts)): ?>
                <p class="muted-cell">No live classroom batches created yet.</p>
            <?php else: ?>
                <div class="ltt-req-list">
                    <?php foreach ($cohorts as $cid => $c): ?>
                        <div class="panel-card ltt-req-card">
                            <div class="ltt-flex-between">
                                <div>
                                    <span class="mono-label text-emerald"><?= esc($c['leaf_breadcrumb']) ?></span>
                                    <h3 style="margin:0.2rem 0;"><?= esc($c['cohort_title']) ?></h3>
                                    <div class="mono-cell muted-cell">
                                        👩‍🏫 Teacher: <strong class="text-cyan"><?= esc($c['teacher_name']) ?></strong> |
                                        📅 <?= esc($c['start_date']) ?> to <?= esc($c['end_date']) ?> (<?= esc($c['start_time_ist']) ?> IST)
                                    </div>
                                </div>
                                <div class="action-btn-group">
                                    <button type="button" class="btn-action js-edit-cohort" data-cohort="<?= esc(json_encode($c), 'attr') ?>">✎ Edit</button>
                                    <?php if (! empty($canDelete)): ?>
                                        <form action="<?= base_url('addons/lms-timetable/delete-cohort') ?>" method="post" class="ltt-inline-form" onsubmit="return confirm('Delete this Batch and all its scheduled classes?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $cid ?>">
                                            <button type="submit" class="btn-action text-danger">✕ Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-top:0.75rem; padding-top:0.65rem; border-top:1px dashed var(--border-subtle);">
                                <span class="mono-label">ENROLLED STUDENTS (<?= (int) $c['enrolled_count'] ?> / <?= (int) $c['max_seats'] ?>):</span>
                                <?php if (empty($c['enrolled_students'])): ?>
                                    <div class="muted-cell" style="font-size:0.82rem; margin-top:0.25rem;">No students assigned yet.</div>
                                <?php else: ?>
                                    <div class="ltt-guest-tags" style="margin-top:0.4rem;">
                                        <?php foreach ($c['enrolled_students'] as $es): ?>
                                            <form action="<?= base_url('addons/lms-timetable/remove-cohort-student') ?>" method="post" class="ltt-inline-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="cohort_id" value="<?= (int) $cid ?>">
                                                <input type="hidden" name="student_user_id" value="<?= (int) $es['student_id'] ?>">
                                                <span class="role-badge" style="gap:0.4rem;">
                                                    🎓 <?= esc($es['student_name']) ?>
                                                    <button type="submit" style="background:none;border:none;color:#f43f5e;cursor:pointer;font-weight:900;" title="Remove">×</button>
                                                </span>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
