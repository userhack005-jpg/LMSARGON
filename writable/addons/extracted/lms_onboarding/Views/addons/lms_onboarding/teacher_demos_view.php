<?= $this->extend('layouts/master_layout') ?>
<?= $this->section('content') ?>
<div class="onb-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card">
        <div class="card-head">
            <div>
                <span class="onb-badge onb-badge-emerald">👩‍🏫 INSTRUCTOR LIVE DEMO CLASS CONSOLE</span>
                <h3 style="margin:0.3rem 0 0;">My Assigned Demo Classes (<?= count($teacherDemoBatches ?? []) ?>)</h3>
                <p class="field-hint" style="margin:0;">Only Demo Class Slots with assigned students appear here. Enter your meeting link to start the class, mark attendance anytime while live, and confirm final attendance &amp; remarks when ending the class.</p>
            </div>
        </div>

        <?php if (empty($teacherDemoBatches)): ?>
            <div class="panel-card" style="text-align:center; padding:2.5rem;">
                <h4 style="margin:0 0 0.35rem;">No Assigned Demo Classes With Students Yet</h4>
                <p class="muted-cell" style="margin:0;">Once Admin assigns demo request students to your Demo Class Slot, the slot, date/time, and student list will appear here.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1.35rem;">
                <?php foreach ($teacherDemoBatches as $slot): ?>
                    <?php
                        $bid           = (int) $slot['id'];
                        $slotStatus    = strtolower((string) ($slot['status'] ?? 'scheduled'));
                        $isScheduled   = ($slotStatus === 'scheduled');
                        $isLive        = ($slotStatus === 'live');
                        $isCompleted   = ($slotStatus === 'completed');
                        $assignedLeads = $slot['assigned_leads'] ?? [];
                        $mLink         = trim((string) ($slot['meeting_link'] ?? ''));
                    ?>
                    <article class="panel-card onb-teacher-slot-card" style="border: 2px solid <?= $isLive ? '#06b6d4' : ($isCompleted ? '#10b981' : 'var(--onb-border)') ?> !important;">
                        <div class="onb-flex-between" style="padding-bottom:0.85rem; margin-bottom:1rem; border-bottom:1px solid var(--onb-border);">
                            <div>
                                <div class="onb-action-bar" style="margin-bottom:0.35rem;">
                                    <?php if ($isLive): ?>
                                        <span class="onb-badge onb-badge-cyan">🔴 LIVE DEMO IN PROGRESS</span>
                                    <?php elseif ($isCompleted): ?>
                                        <span class="onb-badge onb-badge-emerald">✅ DEMO COMPLETED</span>
                                    <?php else: ?>
                                        <span class="onb-badge onb-badge-amber">⏳ SCHEDULED — READY TO START</span>
                                    <?php endif; ?>
                                    <span class="onb-badge onb-badge-info">📅 Date: <strong><?= esc($slot['demo_date']) ?></strong></span>
                                    <span class="onb-badge onb-badge-cyan">⏰ Time: <strong><?= esc($slot['start_time_ampm']) ?> IST</strong> (<?= (int) ($slot['duration_min'] ?? 30) ?> mins)</span>
                                    <span class="onb-badge onb-badge-emerald">🎓 <?= count($assignedLeads) ?> Assigned Student(s)</span>
                                </div>
                                <h3 style="margin:0.2rem 0 0; font-size:1.18rem;"><?= esc($slot['batch_title']) ?></h3>
                                <div class="mono-cell text-emerald"><?= esc($slot['course_title']) ?></div>
                            </div>

                            <?php if ($isLive && $mLink !== ''): ?>
                                <div class="onb-action-bar">
                                    <a href="<?= esc($mLink) ?>" target="_blank" class="onb-btn onb-btn-emerald onb-btn-sm">
                                        🎥 Open Live Meeting Room ↗
                                    </a>
                                    <button type="button" class="onb-btn onb-btn-outline onb-btn-sm js-onb-copy-link" data-link="<?= esc($mLink) ?>">
                                        📋 Copy Link
                                    </button>
                                    <button type="button" class="onb-btn onb-btn-rose onb-btn-sm js-open-end-demo-panel" data-slot-id="<?= $bid ?>">
                                        ⏹ End Demo Class
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($isScheduled): ?>
                            <form action="<?= base_url('addons/lms-onboarding/teacher-start-demo') ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="demo_batch_id" value="<?= $bid ?>">

                                <div style="margin-bottom:0.85rem;">
                                    <span class="mono-label text-cyan">ASSIGNED STUDENTS IN THIS DEMO CLASS (MARK INITIAL ATTENDANCE IF JOINING):</span>
                                    <div class="onb-students-auto-grid" style="margin-top:0.45rem;">
                                        <?php foreach ($assignedLeads as $stLead): ?>
                                            <?php $lid = (int) $stLead['id']; ?>
                                            <div class="panel-card onb-student-att-box">
                                                <div>
                                                    <strong>🎓 <?= esc($stLead['child_name']) ?></strong> <small class="muted-cell">(Age <?= (int) $stLead['child_age'] ?>)</small>
                                                    <div class="mono-cell muted-cell">Parent: <?= esc($stLead['parent_name']) ?></div>
                                                </div>
                                                <select name="attendance[<?= $lid ?>]" class="field-select" style="width:125px; height:32px; padding:0 0.5rem; font-size:0.8rem;">
                                                    <option value="Present" selected>✅ Present</option>
                                                    <option value="Absent">❌ Absent</option>
                                                </select>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="panel-card" style="padding:0.95rem 1.1rem !important; background:var(--onb-canvas-bg); border-color:rgba(16,185,129,0.4);">
                                    <div class="onb-form-grid-2" style="align-items:end;">
                                        <div class="field-group" style="margin:0;">
                                            <label class="field-label"><span class="text-emerald">Enter Live Demo Class Meeting Link (Google Meet / Zoom URL) *</span></label>
                                            <input type="url" name="meeting_link" class="field-input mono-input" value="<?= esc($mLink ?: 'https://meet.google.com/new') ?>" placeholder="https://meet.google.com/..." required>
                                        </div>
                                        <div>
                                            <button type="submit" class="onb-btn onb-btn-emerald onb-btn-lg" style="width:100%;">
                                                ▶ Start Demo Class &amp; Send Link to Students
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        <?php elseif ($isLive): ?>
                            <form action="<?= base_url('addons/lms-onboarding/teacher-mark-attendance') ?>" method="post" style="margin-bottom:1rem;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="demo_batch_id" value="<?= $bid ?>">

                                <div class="onb-flex-between" style="margin-bottom:0.5rem;">
                                    <span class="mono-label text-emerald">LIVE STUDENT ATTENDANCE (UPDATE PRESENT / ABSENT ANYTIME DURING CLASS):</span>
                                    <button type="submit" class="onb-btn onb-btn-outline onb-btn-sm">💾 Update Live Attendance</button>
                                </div>

                                <div class="onb-students-auto-grid">
                                    <?php foreach ($assignedLeads as $stLead): ?>
                                        <?php
                                            $lid     = (int) $stLead['id'];
                                            $currAtt = $stLead['attendance_status'] ?? 'Present';
                                        ?>
                                        <div class="panel-card onb-student-att-box">
                                            <div>
                                                <strong>🎓 <?= esc($stLead['child_name']) ?></strong> <small class="muted-cell">(Age <?= (int) $stLead['child_age'] ?>)</small>
                                                <div class="mono-cell muted-cell">Parent: <?= esc($stLead['parent_name']) ?></div>
                                            </div>
                                            <select name="attendance[<?= $lid ?>]" class="field-select" style="width:125px; height:32px; padding:0 0.5rem; font-size:0.8rem;">
                                                <option value="Present" <?= $currAtt !== 'Absent' ? 'selected' : '' ?>>✅ Present</option>
                                                <option value="Absent" <?= $currAtt === 'Absent' ? 'selected' : '' ?>>❌ Absent</option>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </form>

                            <div id="js-end-demo-panel-<?= $bid ?>" class="panel-card is-hidden" style="background:var(--onb-canvas-bg); border: 2px solid #f59e0b !important; padding:1.15rem !important;">
                                <div class="onb-flex-between" style="margin-bottom:0.75rem;">
                                    <div>
                                        <span class="onb-badge onb-badge-amber">⏹ FINAL ATTENDANCE &amp; REMARKS CONFIRMATION</span>
                                        <h4 style="margin:0.25rem 0 0;">Confirm Final Demo Attendance &amp; Submit Remarks to Admin</h4>
                                        <p class="field-hint" style="margin:0;">Verify one last time who attended the demo class (`Present` = Demo Attended) and add optional remarks for Admin.</p>
                                    </div>
                                </div>

                                <form action="<?= base_url('addons/lms-onboarding/teacher-end-demo') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="demo_batch_id" value="<?= $bid ?>">

                                    <div style="display:flex; flex-direction:column; gap:0.65rem; margin-bottom:1rem;">
                                        <?php foreach ($assignedLeads as $stLead): ?>
                                            <?php
                                                $lid     = (int) $stLead['id'];
                                                $currAtt = $stLead['attendance_status'] ?? 'Present';
                                            ?>
                                            <div class="panel-card onb-end-confirm-grid">
                                                <div>
                                                    <strong>🎓 <?= esc($stLead['child_name']) ?></strong> <small class="muted-cell">(Age <?= (int) $stLead['child_age'] ?>)</small>
                                                    <div class="mono-cell muted-cell"><?= esc($stLead['course_title']) ?></div>
                                                </div>
                                                <div>
                                                    <select name="attendance[<?= $lid ?>]" class="field-select">
                                                        <option value="Present" <?= $currAtt !== 'Absent' ? 'selected' : '' ?>>✅ Present (Demo Attended)</option>
                                                        <option value="Absent" <?= $currAtt === 'Absent' ? 'selected' : '' ?>>❌ Absent (Did Not Attend)</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <input type="text" name="remarks[<?= $lid ?>]" class="field-input" value="<?= esc($stLead['teacher_eval_note'] ?? '') ?>" placeholder="Optional remark for Admin (e.g. Good speed, ready for Level 1)">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="onb-action-bar" style="justify-content:flex-end;">
                                        <button type="button" class="onb-btn onb-btn-outline onb-btn-sm js-close-end-demo-panel" data-slot-id="<?= $bid ?>">Cancel</button>
                                        <button type="submit" class="onb-btn onb-btn-emerald">✓ Confirm Final Attendance &amp; Complete Demo Class</button>
                                    </div>
                                </form>
                            </div>

                        <?php else: ?>
                            <div class="onb-students-auto-grid">
                                <?php foreach ($assignedLeads as $stLead): ?>
                                    <?php $isPres = (($stLead['attendance_status'] ?? '') === 'Present' || ($stLead['status'] ?? '') === 'demo_attended'); ?>
                                    <div class="panel-card" style="padding:0.8rem 1rem !important; background:var(--onb-canvas-bg);">
                                        <div class="onb-flex-between">
                                            <strong>🎓 <?= esc($stLead['child_name']) ?> (Age <?= (int) $stLead['child_age'] ?>)</strong>
                                            <span class="onb-badge <?= $isPres ? 'onb-badge-emerald' : 'onb-badge-rose' ?>">
                                                <?= $isPres ? '✅ Demo Attended' : '❌ Absent' ?>
                                            </span>
                                        </div>
                                        <?php if (! empty($stLead['teacher_eval_note'])): ?>
                                            <div class="mono-cell muted-cell" style="margin-top:0.35rem;">💬 Remark: <?= esc($stLead['teacher_eval_note']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?= $this->endSection() ?>
