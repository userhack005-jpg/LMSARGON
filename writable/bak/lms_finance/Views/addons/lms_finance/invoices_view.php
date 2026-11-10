<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">ASSIGN BATCH SUBSCRIPTION</span>
                    <h3 style="margin:0.3rem 0 0;">Create Student Batch Fee Subscription</h3>
                    <p class="field-hint" style="margin:0;">Batch Start &amp; End dates auto-sync from assigned Classroom Batches. Due dates are strictly anchored to the 1st of every month.</p>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-subscription') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Select Student *</span></label>
                    <select name="student_user_id" class="field-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach (($students ?? []) as $sid => $sRow): ?>
                            <option value="<?= (int) $sid ?>">ðŸŽ“ <?= esc($sRow['display_name']) ?> (<?= esc($sRow['country_code']) ?> â€¢ <?= esc($sRow['currency_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Select Course *</span></label>
                    <select name="node_id" class="field-select" required>
                        <?php foreach (($nodesById ?? []) as $nid => $nTitle): ?>
                            <option value="<?= (int) $nid ?>"><?= esc($nTitle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Batch Start Date (Optional Override)</span></label>
                        <input type="date" name="batch_start_date" class="field-input" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Batch End Date (Optional Override)</span></label>
                        <input type="date" name="batch_end_date" class="field-input" value="<?= date('Y-m-t', strtotime('+3 months')) ?>">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">ðŸ’¾ Create Batch Subscription (1st-of-Month Due)</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <h3>Active Student Subscriptions &amp; Monthly Access Locks (<?= count($subs ?? []) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Monthly Fee</th>
                            <th>Batch Start âž” Batch End</th>
                            <th>Next Due (1st of Month)</th>
                            <th>Access Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($subs ?? []) as $s): ?>
                            <?php
                                $sid    = (int) $s['student_user_id'];
                                $sName  = $allUsers[$sid]['display_name'] ?? 'Student';
                                $cTitle = $nodesById[(int) $s['node_id']] ?? 'Course';
                                $locked = ((int) $s['is_access_locked'] === 1);
                            ?>
                            <tr>
                                <td class="identity-name">ðŸŽ“ <?= esc($sName) ?></td>
                                <td class="mono-cell text-emerald"><?= esc($cTitle) ?></td>
                                <td class="mono-cell"><strong><?= esc($s['currency']) ?> <?= number_format((float) $s['fee_amount'], 2) ?></strong></td>
                                <td class="mono-cell">
                                    <?= esc($s['batch_start_date'] ?: 'â€”') ?> âž” <?= esc($s['batch_end_date'] ?: 'â€”') ?>
                                </td>
                                <td class="mono-cell"><strong class="text-cyan">ðŸ“… <?= esc($s['due_date']) ?></strong></td>
                                <td>
                                    <form action="<?= base_url('addons/lms-finance/toggle-lock') ?>" method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" class="btn-action <?= $locked ? 'text-danger' : 'text-emerald' ?>">
                                            <?= $locked ? 'ðŸ”’ Locked (Click to Unlock)' : 'ðŸ”“ Active (Click to Lock)' ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <?php if (! empty($canDelete)): ?>
                                        <form action="<?= base_url('addons/lms-finance/delete-subscription') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">âœ•</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
