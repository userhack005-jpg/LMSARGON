<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lfn-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Record Teacher Monthly Salary</h3>
                    <p class="field-hint" style="margin:0;">Enter the teacher's monthly salary directly. The teacher will see this salary slip in their View-Only Salary Portal.</p>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-salary') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Select Teacher *</span></label>
                    <select name="teacher_user_id" class="field-select" required>
                        <option value="">-- Choose Teacher --</option>
                        <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                            <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tRow['display_name']) ?> (<?= esc($tRow['email'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Salary Month / Period *</span></label>
                        <input type="text" name="pay_period_label" class="field-input" value="<?= date('F Y') ?>" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Base Salary Amount (INR ₹) *</span></label>
                        <input type="number" step="0.01" name="fixed_base_salary" class="field-input mono-input" value="25000" required>
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label text-emerald"><span>+ Incentive / Bonus (INR ₹)</span></label>
                        <input type="number" step="0.01" name="manual_bonus" class="field-input mono-input" value="0">
                    </div>
                    <div class="field-group">
                        <label class="field-label text-danger"><span>- Deduction (INR ₹)</span></label>
                        <input type="number" step="0.01" name="manual_deduction" class="field-input mono-input" value="0">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Bank / UPI Reference No.</span></label>
                        <input type="text" name="payment_reference" class="field-input mono-input" placeholder="e.g. UTR-98234123">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Remarks for Teacher</span></label>
                        <input type="text" name="admin_remarks" class="field-input" placeholder="e.g. October monthly salary credited">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">💸 Save &amp; Publish Salary Slip</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Disbursed Teacher Salaries (<?= count($payouts ?? []) ?>)</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Month</th>
                            <th>Base / Bonus / Deduct</th>
                            <th>Net Salary Paid</th>
                            <th>Reference &amp; Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payouts)): ?>
                            <tr><td colspan="6" class="muted-cell">No teacher salaries recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payouts as $p): ?>
                                <?php
                                    $tid   = (int) $p['teacher_user_id'];
                                    $tName = $allUsers[$tid]['display_name'] ?? 'Teacher';
                                ?>
                                <tr>
                                    <td class="identity-name">👩‍🏫 <?= esc($tName) ?></td>
                                    <td><span class="role-badge"><?= esc($p['pay_period_label']) ?></span></td>
                                    <td class="mono-cell">
                                        ₹<?= number_format((float) $p['fixed_base_salary'], 0) ?>
                                        + ₹<?= number_format((float) $p['manual_bonus'], 0) ?>
                                        - ₹<?= number_format((float) $p['manual_deduction'], 0) ?>
                                    </td>
                                    <td class="mono-cell text-emerald">
                                        <strong>₹<?= number_format((float) $p['net_payout_inr'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <div class="mono-cell text-cyan"><?= esc($p['payment_reference']) ?></div>
                                        <div class="muted-cell" style="font-size:0.8rem;"><?= esc($p['admin_remarks']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-finance/delete-salary') ?>" method="post" class="lfn-inline-form" onsubmit="return confirm('Delete this salary record?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                <button type="submit" class="btn-action text-danger">✕</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
