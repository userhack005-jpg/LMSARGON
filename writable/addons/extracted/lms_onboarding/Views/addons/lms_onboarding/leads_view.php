<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="onb-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card">
        <div class="card-head onb-flex-between" style="margin-bottom:0.85rem;">
            <div>
                <h3>Demo Class Requests, Slot Allocation &amp; Cohort Conversion (<span id="js-onb-leads-cnt"><?= count($leads ?? []) ?></span>)</h3>
                <p class="field-hint" style="margin:0;">Step 1: Create Demo Slots in "Demo Class Slots". Step 2: Assign a Demo Slot below. Step 3: After demo attendance, convert the student into a Live Batch (auto-syncs Fee Subscription).</p>
            </div>
            <a href="<?= base_url('addons/lms-onboarding/scheduler') ?>" class="onb-btn onb-btn-cyan onb-btn-sm">📅 + Create New Demo Slot</a>
        </div>

        <div class="onb-filter-pills" id="js-onb-leads-tabs" style="margin-bottom:0.85rem;">
            <button type="button" class="onb-filter-tab is-active" data-type="all">All (<?= count($leads ?? []) ?>)</button>
            <button type="button" class="onb-filter-tab" data-type="unassigned">⏳ Unassigned</button>
            <button type="button" class="onb-filter-tab" data-type="scheduled">📅 Scheduled / Live</button>
            <button type="button" class="onb-filter-tab" data-type="attended">✅ Demo Attended</button>
            <button type="button" class="onb-filter-tab" data-type="absent">❌ Absent / Reschedule</button>
            <button type="button" class="onb-filter-tab" data-type="converted">🚀 Converted</button>
        </div>

        <div class="onb-toolbar-strip">
            <div class="onb-toolbar-left">
                <input type="search" id="js-onb-leads-search" class="onb-search-input" placeholder="🔍 Instant search child, parent, email, phone, course or teacher...">
            </div>
            <div class="onb-toolbar-right">
                <label class="mono-cell muted-cell">Rows:</label>
                <select id="js-onb-leads-pagesize" class="onb-pagesize-select">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="9999">All</option>
                </select>
                <button type="button" id="js-onb-leads-csv" class="onb-btn onb-btn-outline onb-btn-sm">⬇ CSV</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="js-onb-leads-table">
                <thead>
                    <tr>
                        <th>Child &amp; Parent</th>
                        <th>Course &amp; Preferred Timing</th>
                        <th>Assign Demo Class Slot</th>
                        <th>Demo Attendance &amp; Teacher Remark</th>
                        <th>Convert &amp; Add to Cohort/Batch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr><td colspan="5" class="muted-cell" style="text-align:center; padding:2rem;">No demo requests yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leads as $ld): ?>
                            <?php
                                $lid         = (int) $ld['id'];
                                $assignedBid = (int) ($ld['demo_batch_id'] ?? 0);
                                $st          = strtolower((string) ($ld['status'] ?? 'new_lead'));
                                $attSt       = (string) ($ld['attendance_status'] ?? 'Pending');
                                $isConverted = ($st === 'converted');
                                $isAttended  = ($st === 'demo_attended' || $attSt === 'Present');
                                $isAbsent    = ($st === 'reschedule_needed' || $attSt === 'Absent');

                                if ($isConverted) {
                                    $filterGrp  = 'converted';
                                    $statusText = 'CONVERTED';
                                } elseif ($isAttended) {
                                    $filterGrp  = 'attended';
                                    $statusText = 'DEMO ATTENDED';
                                } elseif ($isAbsent) {
                                    $filterGrp  = 'absent';
                                    $statusText = 'ABSENT / RESCHEDULE';
                                } elseif ($assignedBid > 0 || $st === 'demo_scheduled' || $st === 'demo_live') {
                                    $filterGrp  = 'scheduled';
                                    $statusText = 'SCHEDULED';
                                } else {
                                    $filterGrp  = 'unassigned';
                                    $statusText = 'UNASSIGNED';
                                }
                            ?>
                            <tr class="js-onb-lead-row"
                                data-type="<?= esc($filterGrp) ?>"
                                data-csv-child="<?= esc($ld['child_name'] . ' (Age ' . (int) $ld['child_age'] . ')') ?>"
                                data-csv-parent="<?= esc($ld['parent_name']) ?>"
                                data-csv-email="<?= esc($ld['parent_email']) ?>"
                                data-csv-phone="<?= esc($ld['parent_phone'] ?? '') ?>"
                                data-csv-course="<?= esc($ld['course_title']) ?>"
                                data-csv-slot="<?= esc($assignedBid > 0 ? ($ld['demo_batch_title'] . ' (' . $ld['slot_date'] . ' ' . $ld['slot_time_ampm'] . ')') : $ld['preferred_time_slot']) ?>"
                                data-csv-status="<?= esc($statusText) ?>"
                                data-csv-remark="<?= esc($ld['teacher_eval_note'] ?? '') ?>">
                                <td>
                                    <div class="identity-name">🎓 <?= esc($ld['child_name']) ?> <small class="muted-cell">(Age <?= (int) $ld['child_age'] ?>)</small></div>
                                    <div class="mono-cell">👨‍👩‍👧‍👦 <?= esc($ld['parent_name']) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc($ld['parent_email']) ?><?= ! empty($ld['parent_phone']) ? (' • ' . esc($ld['parent_phone'])) : '' ?></div>
                                </td>
                                <td>
                                    <div class="identity-name text-emerald"><?= esc($ld['course_title']) ?></div>
                                    <div class="mono-cell text-cyan">⏰ Preferred: <?= esc($ld['preferred_time_slot']) ?></div>
                                </td>
                                <td>
                                    <?php if ($isConverted): ?>
                                        <span class="onb-badge onb-badge-emerald">✓ Converted to Student</span>
                                    <?php else: ?>
                                        <form action="<?= base_url('addons/lms-onboarding/assign-lead-demo') ?>" method="post" class="onb-inline-cell-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="lead_id" value="<?= $lid ?>">
                                            <select name="demo_batch_id" class="field-select onb-compact-select" required>
                                                <option value="">-- Select Demo Class Slot --</option>
                                                <?php foreach (($demoBatches ?? []) as $dbSlot): ?>
                                                    <?php if (($dbSlot['status'] ?? '') === 'completed' && (int) $dbSlot['id'] !== $assignedBid) continue; ?>
                                                    <option value="<?= (int) $dbSlot['id'] ?>" <?= $assignedBid === (int) $dbSlot['id'] ? 'selected' : '' ?>>
                                                        📅 <?= esc($dbSlot['batch_title']) ?> — 👩‍🏫 <?= esc($dbSlot['teacher_name']) ?> (<?= esc($dbSlot['demo_date']) ?> @ <?= esc($dbSlot['start_time_ampm']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="onb-btn onb-btn-outline onb-btn-sm">
                                                <?= $assignedBid > 0 ? '↻ Update Slot' : '📅 Assign Slot' ?>
                                            </button>
                                        </form>
                                        <?php if ($assignedBid > 0): ?>
                                            <div class="mono-cell text-cyan" style="margin-top:0.25rem; font-size:0.73rem;">
                                                ✓ Slot: <strong><?= esc($ld['demo_batch_title']) ?></strong> (👩‍🏫 <?= esc($ld['teacher_name']) ?> • <?= esc($ld['slot_date']) ?> <?= esc($ld['slot_time_ampm']) ?>)
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                        <div>
                                            <?php if ($isAttended): ?>
                                                <span class="onb-badge onb-badge-emerald">✅ Demo Attended (Present)</span>
                                            <?php elseif ($isAbsent): ?>
                                                <span class="onb-badge onb-badge-rose">❌ Absent / Reschedule Needed</span>
                                            <?php elseif ($st === 'demo_live'): ?>
                                                <span class="onb-badge onb-badge-cyan">🔴 Demo Class Live Now</span>
                                            <?php elseif ($assignedBid > 0): ?>
                                                <span class="onb-badge onb-badge-cyan">📅 Scheduled in Slot</span>
                                            <?php else: ?>
                                                <span class="onb-badge onb-badge-amber">⏳ Unassigned Request</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (! empty($ld['teacher_eval_note'])): ?>
                                            <div class="mono-cell muted-cell" style="font-size:0.75rem;">
                                                💬 Remark: <strong><?= esc($ld['teacher_eval_note']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="onb-action-bar">
                                        <?php if ($isConverted): ?>
                                            <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="onb-btn onb-btn-outline onb-btn-sm">🏫 View in Batch →</a>
                                        <?php else: ?>
                                            <form action="<?= base_url('addons/lms-onboarding/convert-lead') ?>" method="post" class="onb-inline-cell-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="lead_id" value="<?= $lid ?>">
                                                <input type="hidden" name="course_node_id" value="<?= (int) $ld['course_node_id'] ?>">
                                                <select name="target_cohort_id" class="field-select onb-compact-select">
                                                    <option value="0">-- Auto-Match Batch --</option>
                                                    <?php foreach (($cohortsMap ?? []) as $cid => $cRow): ?>
                                                        <option value="<?= (int) $cid ?>" <?= (int) $cRow['leaf_node_id'] === (int) $ld['course_node_id'] ? 'selected' : '' ?>>
                                                            🏫 <?= esc($cRow['cohort_title']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="onb-btn onb-btn-emerald onb-btn-sm">🚀 Convert &amp; Add</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-onboarding/delete-lead') ?>" method="post" onsubmit="return confirm('Delete this demo request?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $lid ?>">
                                                <button type="submit" class="onb-btn onb-btn-rose onb-btn-sm">✕</button>
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

        <div class="onb-pagination-bar" id="js-onb-leads-pager"></div>
    </section>
</div>
<?= $this->endSection() ?>
