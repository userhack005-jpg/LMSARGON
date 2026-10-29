<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="ltt-wrapper" id="js-ltt-sub-poll-root" data-poll-url="<?= base_url('addons/lms-timetable/live-sub-threads-json') ?>">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="ltt-main-grid ltt-section-gap">
        <!-- Left: Dual-Mode Multi-Student Makeup Class Builder + Duration Rule -->
        <div class="ltt-stack">
            <section class="console-card ltt-card-gap">
                <div class="card-head">
                    <div>
                        <span class="role-badge text-cyan">MAKEUP &amp; GUEST CLASS BUILDER</span>
                        <h3 style="margin-top:0.3rem;">Schedule Makeup Class for Missed Students</h3>
                    </div>
                </div>
                <form action="<?= base_url('addons/lms-timetable/add-guest-student') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="field-group">
                        <label class="field-label"><span>Makeup Class Mode *</span></label>
                        <select name="makeup_mode" id="js-makeup-mode-select" class="field-select">
                            <option value="existing_session">📌 Mode A: Join an Existing Scheduled Class</option>
                            <option value="new_makeup_session">✨ Mode B: Create New Standalone Makeup Class &amp; Assign Teacher</option>
                        </select>
                    </div>

                    <div id="js-makeup-existing-box" class="field-group">
                        <label class="field-label"><span>Select Existing Class Session *</span></label>
                        <select name="session_id" class="field-select">
                            <?php foreach (($sessions ?? []) as $sItem): ?>
                                <option value="<?= (int) $sItem['id'] ?>">
                                    <?= esc($sItem['cohort_title']) ?> | <?= esc($sItem['local_start_display']) ?> (👩‍🏫 <?= esc($sItem['assigned_teacher_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="js-makeup-new-box" class="panel-card is-hidden" style="margin-bottom:0.85rem; border-color:rgba(6,182,212,0.4);">
                        <div class="grid-2-col">
                            <div class="field-group">
                                <label class="field-label"><span>Assign Teacher *</span></label>
                                <select name="makeup_teacher_id" class="field-select">
                                    <option value="">-- Choose Teacher --</option>
                                    <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                                        <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tRow['display_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Link Under Batch</span></label>
                                <select name="makeup_cohort_id" class="field-select">
                                    <option value="0">-- General Makeup Class --</option>
                                    <?php foreach (($cohorts ?? []) as $cid => $cInfo): ?>
                                        <option value="<?= (int) $cid ?>"><?= esc($cInfo['cohort_title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3-col">
                            <div class="field-group">
                                <label class="field-label"><span>Date *</span></label>
                                <input type="date" name="makeup_date" class="field-input" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Time (IST) *</span></label>
                                <input type="time" name="makeup_time_ist" class="field-input" value="18:00">
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Duration (m)</span></label>
                                <input type="number" name="makeup_duration_min" class="field-input mono-input" value="45">
                            </div>
                        </div>
                        <div class="field-group" style="margin:0;">
                            <label class="field-label"><span>Meeting Link</span></label>
                            <input type="url" name="makeup_meeting_link" class="field-input mono-input" value="https://meet.google.com/new">
                        </div>
                    </div>

                    <div class="field-group">
                        <div class="ltt-flex-between" style="margin-bottom:0.4rem;">
                            <label class="field-label" style="margin:0;"><span>Tick Student(s) for Makeup *</span></label>
                            <input type="search" class="field-input js-stu-filter-input" data-target="js-makeup-stu-list" placeholder="Search student..." style="width:150px; padding:0.25rem 0.5rem; font-size:0.78rem;">
                        </div>
                        <div class="ltt-stu-checkbox-box" id="js-makeup-stu-list">
                            <?php foreach (($students ?? []) as $sid => $sRow): ?>
                                <label class="ltt-stu-cb-row" data-search="<?= esc(strtolower($sRow['display_name'])) ?>">
                                    <input type="checkbox" name="student_ids[]" value="<?= (int) $sid ?>">
                                    <strong>🎓 <?= esc($sRow['display_name']) ?></strong>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Makeup Reason Note</span></label>
                        <input type="text" name="guest_reason" class="field-input" value="Missed regular class — Makeup Slot">
                    </div>

                    <button type="submit" class="btn-primary btn-full">➕ Assign Checked Student(s) to Makeup Class</button>
                </form>
            </section>

            <section class="console-card">
                <div class="card-head">
                    <div>
                        <h3>Teacher Attendance Duration Rule</h3>
                    </div>
                </div>
                <form action="<?= base_url('addons/lms-timetable/save-settings') ?>" method="post" class="ltt-inline-rule-form">
                    <?= csrf_field() ?>
                    <span class="mono-cell">Minimum Class Duration:</span>
                    <input type="number" name="min_class_duration_minutes" value="<?= (int) ($minDurationMinutes ?? 30) ?>" class="field-input ltt-input-xs" min="5" max="240">
                    <span class="mono-cell">mins</span>
                    <button type="submit" class="btn-secondary btn-sm">Save Rule</button>
                </form>
            </section>
        </div>

        <!-- Right: Master Class Schedule with Batch Filter & 1-Click Reschedule -->
        <section class="console-card">
            <div class="card-head ltt-flex-between">
                <div>
                    <h3>Master Class Schedule &amp; Attendance Audit</h3>
                    <p class="field-hint" style="margin:0;">Filter by batch or click "📅 Reschedule" on any class to change its date, time, or teacher.</p>
                </div>
                <select id="js-admin-sess-batch-filter" class="field-select" style="width:220px;">
                    <option value="all">All Classroom Batches</option>
                    <?php foreach (($cohorts ?? []) as $cid => $cInfo): ?>
                        <option value="<?= (int) $cid ?>"><?= esc($cInfo['cohort_title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Classroom Batch</th>
                            <th>Local Time (<?= esc($userTz) ?>)</th>
                            <th>Teacher</th>
                            <th>Status &amp; Attendance</th>
                            <th>Reschedule / Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr><td colspan="5" class="muted-cell">No sessions scheduled yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $sess): ?>
                                <?php $sid = (int) $sess['id']; ?>
                                <tr class="js-admin-sess-row" data-cohort-id="<?= (int) ($sess['cohort_id'] ?? 0) ?>">
                                    <td>
                                        <div class="identity-name"><?= esc($sess['cohort_title']) ?></div>
                                        <?php if (! empty($sess['students_list'])): ?>
                                            <div class="ltt-guest-tags">
                                                <?php foreach ($sess['students_list'] as $stA): ?>
                                                    <span class="role-badge">
                                                        <?= (int) $stA['is_guest_makeup'] === 1 ? '🎟 Makeup: ' : '🎓 ' ?>
                                                        <?= esc($stA['student_name']) ?> (<?= esc($stA['attendance_status']) ?>)
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="identity-name text-emerald"><?= esc($sess['local_start_display']) ?></div>
                                        <div class="mono-cell muted-cell">UTC: <?= esc($sess['scheduled_start_utc']) ?></div>
                                    </td>
                                    <td>
                                        <div class="identity-name">👩‍🏫 <?= esc($sess['assigned_teacher_name']) ?></div>
                                        <?php if ((int) $sess['is_substituted'] === 1): ?>
                                            <span class="role-badge text-cyan">⇄ Substituted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($sess['status'] ?? '') === 'live'): ?>
                                            <span class="ltt-badge-live">🔴 LIVE IN PROGRESS</span>
                                        <?php elseif (($sess['status'] ?? '') === 'completed'): ?>
                                            <span class="role-badge text-emerald">✓ <?= esc($sess['teacher_attendance_status']) ?> (<?= (int) $sess['duration_minutes'] ?>m)</span>
                                        <?php else: ?>
                                            <span class="role-badge">⏳ SCHEDULED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btn-group">
                                            <button type="button"
                                                    class="btn-action text-cyan js-open-reschedule"
                                                    data-id="<?= $sid ?>"
                                                    data-title="<?= esc($sess['cohort_title']) ?>"
                                                    data-ymd="<?= esc($sess['local_ymd']) ?>"
                                                    data-hm="<?= esc($sess['local_hm']) ?>"
                                                    data-teacher="<?= (int) $sess['assigned_teacher_id'] ?>"
                                                    data-link="<?= esc($sess['meeting_link']) ?>">
                                                📅 Reschedule
                                            </button>
                                            <?php if (! empty($canDelete)): ?>
                                                <form action="<?= base_url('addons/lms-timetable/delete-session') ?>" method="post" class="ltt-inline-form" onsubmit="return confirm('Delete this class session?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $sid ?>">
                                                    <button type="submit" class="btn-action text-danger">✕</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- 1-Click Single Class Reschedule Modal -->
    <div class="ltt-wizard-modal is-hidden" id="js-reschedule-modal">
        <div class="ltt-wizard-dialog panel-card" style="max-width:500px;">
            <div class="ltt-wizard-head">
                <div>
                    <span class="mono-label text-cyan">1-CLICK CLASS RESCHEDULER</span>
                    <h3 id="js-resched-title" style="margin:0.2rem 0 0;">Reschedule Class</h3>
                </div>
                <button type="button" class="btn-action" id="js-resched-close">✕ Close</button>
            </div>
            <form action="<?= base_url('addons/lms-timetable/reschedule-session') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" id="resched_id" value="0">
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>New Date *</span></label>
                        <input type="date" name="reschedule_date" id="resched_date" class="field-input" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>New Time (IST) *</span></label>
                        <input type="time" name="reschedule_time_ist" id="resched_time" class="field-input" required>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Assigned Teacher</span></label>
                    <select name="assigned_teacher_id" id="resched_teacher" class="field-select">
                        <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                            <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tRow['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Meeting Link</span></label>
                    <input type="url" name="meeting_link" id="resched_link" class="field-input mono-input">
                </div>
                <button type="submit" class="btn-primary btn-full">💾 Save New Date &amp; Time</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
