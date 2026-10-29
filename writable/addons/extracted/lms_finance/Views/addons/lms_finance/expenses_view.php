<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lfn-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Log Operational Expense</h3>
                </div>
            </div>
            <form action="<?= base_url('addons/lms-finance/save-expense') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Expense Category</span></label>
                    <select name="expense_category" class="field-select">
                        <option value="Marketing & Ads">Marketing &amp; Ads</option>
                        <option value="Software & Cloud">Software &amp; Cloud</option>
                        <option value="Rent & Utilities">Rent &amp; Utilities</option>
                        <option value="Misc Operations">Misc Operations</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Expense Title *</span></label>
                    <input type="text" name="title" class="field-input" placeholder="e.g. Zoom Pro / Cloud Server / Meta Ads" required>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Amount (INR ₹) *</span></label>
                        <input type="number" step="0.01" name="amount_inr" class="field-input mono-input" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Expense Date</span></label>
                        <input type="date" name="expense_date" class="field-input" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-full">+ Add Expense</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Operational Expenses History (<?= count($expenses ?? []) ?>)</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date &amp; Category</th>
                            <th>Title</th>
                            <th>Amount (INR)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr><td colspan="4" class="muted-cell">No expenses logged yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $exRow): ?>
                                <tr>
                                    <td>
                                        <div class="mono-cell"><?= esc($exRow['expense_date']) ?></div>
                                        <span class="role-badge"><?= esc($exRow['expense_category']) ?></span>
                                    </td>
                                    <td class="identity-name"><?= esc($exRow['title']) ?></td>
                                    <td class="mono-cell text-danger">- ₹<?= number_format((float) $exRow['amount_inr'], 2) ?></td>
                                    <td>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-finance/delete-expense') ?>" method="post" class="lfn-inline-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $exRow['id'] ?>">
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
