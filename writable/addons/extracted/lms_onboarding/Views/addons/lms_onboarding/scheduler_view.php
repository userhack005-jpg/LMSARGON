<?= $this->extend('layouts/master_layout') ?>
<?= $this->section('content') ?>
<div class="onb-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card" style="margin-bottom: 1.25rem;">
        <div class="card-head onb-flex-between">
            <div>
                <span class="onb-badge onb-badge-emerald">STEP 1 // CREATE DEMO CLASS SLOT</span>
                <h3 style="margin:0.3rem 0 0;">Create New Demo Class Slot &amp; Assign Teacher</h3>
            </div>
            <span class="field-hint" style="margin:0;">When the Teacher starts the Demo Class from their console, they will enter the live Meeting Link</span>
        </div>

        <form action="<?= base_url('addons/lms-onboarding/save-demo-batch') ?>" method="post">
            <?= csrf_field() ?>
            <div class="onb-assign-grid">
                <div class="field-group">
                    <label class="field-label"><span>Demo Slot Title *</span></label>
                    <input type="text" name="batch_title" class="field-input" placeholder="e.g. Abacus L1 — Evening Slot" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Course / Level (Optional)</span></label>
                    <select name="course_node_id" class="field-select">
                        <option value="0">-- Any / General Demo Slot --</option>
                        <?php foreach (($allCoursesMap ?? []) as $cid => $cInfo): ?>
                            <option value="<?= (int) $cid ?>"><?= esc($cInfo['breadcrumb_path']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Assign Teacher *</span></label>
                    <select name="teacher_user_id" class="field-select" required>
                        <option value="">-- Choose Teacher --</option>
                        <?php foreach (($teachersMap ?? []) as $tid => $tInfo): ?>
                            <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tInfo['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Demo Date *</span></label>
                    <input type="date" name="demo_date" class="field-input" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Start Time (IST) *</span></label>
                    <input type="time" name="start_time_ist" class="field-input" value="17:00" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Duration (Mins)</span></label>
                    <input type="number" name="duration_min" class="field-input mono-input" value="30" min="15" max="120">
                </div>
                <div class="field-group">
                    <button type="submit" class="onb-btn onb-btn-emerald onb-btn-lg" style="width:100%;">📅 Save Demo Slot</button>
                </div>
            </div>
        </form>
    </section>

    <section class="console-card">
        <div class="card-head onb-flex-between">
            <div>
                <h3>Created Demo Class Slots (<span id="js-onb-slots-cnt"><?= count($demoBatches ?? []) ?></span>)</h3>
            </div>
            <div class="onb-filter-pills" id="js-onb-slots-tabs">
                <button type="button" class="onb-filter-tab is-active" data-type="all">All (<?= count($demoBatches ?? []) ?>)</button>
                <button type="button" class="onb-filter-tab" data-type="scheduled">⏳ Scheduled</button>
                <button type="button" class="onb-filter-tab" data-type="live">🔴 Live</button>
                <button type="button" class="onb-filter-tab" data-type="completed">✅ Completed</button>
            </div>
        </div>

        <div class="onb-toolbar-strip">
            <div class="onb-toolbar-left">
                <input type="search" id="js-onb-slots-search" class="onb-search-input" placeholder="🔍 Instant search slot title, course, teacher, or date...">
            </div>
            <div class="onb-toolbar-right">
                <label class="mono-cell muted-cell">Rows:</label>
                <select id="js-onb-slots-pagesize" class="onb-pagesize-select">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="9999">All</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Demo Slot &amp; Course</th>
                        <th>Teacher</th>
                        <th>Date &amp; Time (IST)</th>
                        <th>Assigned Kids</th>
                        <th>Status &amp; Meeting Link</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($demoBatches)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2rem;">No demo slots created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($demoBatches as $dbRow): ?>
                            <?php
                                $bSt   = strtolower((string) ($dbRow['status'] ?? 'scheduled'));
                                $mLink = trim((string) ($dbRow['meeting_link'] ?? ''));
                            ?>
                            <tr class="js-onb-slot-row" data-type="<?= esc($bSt) ?>">
                                <td>
                                    <div class="identity-name"><?= esc($dbRow['batch_title']) ?></div>
                                    <div class="mono-cell text-emerald"><?= esc($dbRow['course_title']) ?></div>
                                </td>
                                <td>👩‍🏫 <?= esc($dbRow['teacher_name']) ?></td>
                                <td class="mono-cell">
                                    <div>📅 <strong><?= esc($dbRow['demo_date']) ?></strong></div>
                                    <div class="text-cyan">⏰ <?= esc($dbRow['start_time_ampm']) ?> (<?= (int) ($dbRow['duration_min'] ?? 30) ?>m)</div>
                                </td>
                                <td class="mono-cell">
                                    <span class="onb-badge onb-badge-info">🎓 <?= (int) ($dbRow['students_count'] ?? 0) ?> Student(s)</span>
                                </td>
                                <td>
                                    <div class="onb-action-bar">
                                        <span class="onb-badge <?= $bSt === 'completed' ? 'onb-badge-emerald' : ($bSt === 'live' ? 'onb-badge-cyan' : 'onb-badge-amber') ?>">
                                            <?= strtoupper(esc($bSt)) ?>
                                        </span>
                                        <?php if ($mLink !== ''): ?>
                                            <button type="button" class="onb-btn onb-btn-outline onb-btn-sm js-onb-copy-link" data-link="<?= esc($mLink) ?>">📋 Copy Link</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (! empty($canDelete)): ?>
                                        <form action="<?= base_url('addons/lms-onboarding/delete-demo-batch') ?>" method="post" onsubmit="return confirm('Delete this demo slot?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $dbRow['id'] ?>">
                                            <button type="submit" class="onb-btn onb-btn-rose onb-btn-sm">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="onb-pagination-bar" id="js-onb-slots-pager"></div>
    </section>
</div>
<?= $this->endSection() ?>
