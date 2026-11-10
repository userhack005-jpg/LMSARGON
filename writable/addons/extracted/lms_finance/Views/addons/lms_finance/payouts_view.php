<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">TEACHER PAYROLL &amp; EXPENSES</span>
                    <h3 style="margin:0.3rem 0 0;">Record Salary Slip or Expense</h3>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-payout') ?>" method="post">
                <?= csrf_field() ?>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Entry Type</span></label>
                        <select name="expense_type" class="field-select">
                            <option value="teacher_payout">👩‍🏫 Teacher Salary / Payout</option>
                            <option value="operational_expense">🏢 Operational Expense</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Select Teacher (For Salary Slip)</span></label>
                        <select name="teacher_user_id" class="field-select">
                            <option value="0">-- General Expense --</option>
                            <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                                <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tRow['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Title / Description *</span></label>
                    <input type="text" name="title" class="field-input" placeholder="e.g. Monthly Instructor Payout — October 2026" required>
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span>Month / Period</span></label>
                        <input type="text" name="month_year" class="field-input" value="<?= date('M Y') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Classes Taught</span></label>
                        <input type="number" name="classes_taught" class="field-input mono-input" value="20">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Amount (INR) *</span></label>
                        <input type="number" step="0.01" name="amount" class="field-input mono-input" placeholder="e.g. 15000" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">💵 Save Salary Slip / Expense</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <h3>Recorded Payouts &amp; Expenses (<?= count($expenses ?? []) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title &amp; Instructor</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Ref</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($expenses ?? []) as $ex): ?>
                            <?php $tid = (int) $ex['teacher_user_id']; ?>
                            <tr>
                                <td>
                                    <div class="identity-name"><?= esc($ex['title']) ?></div>
                                    <?php if ($tid > 0 && isset($allUsers[$tid])): ?>
                                        <div class="mono-cell text-cyan">👩‍🏫 <?= esc($allUsers[$tid]['display_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="mono-cell"><?= esc($ex['month_year']) ?></td>
                                <td class="mono-cell text-emerald"><strong><?= esc($ex['currency']) ?> <?= number_format((float) $ex['amount'], 2) ?></strong></td>
                                <td class="mono-cell"><code><?= esc($ex['reference_no']) ?></code></td>
                                <td>
                                    <?php if (! empty($canDelete)): ?>
                                        <form action="<?= base_url('addons/lms-finance/delete-payout') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">✕</button>
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
