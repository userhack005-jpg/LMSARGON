<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lfn-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Create Custom Payment Request for Parent &amp; Child</h3>
                    <p class="field-hint" style="margin:0;">Select a Parent and Child below. The currency automatically adapts to the Parent's registered country (e.g. INR for India, AED for UAE, USD for US).</p>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/create-custom-invoice') ?>" method="post" id="js-custom-billing-form">
                <?= csrf_field() ?>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>1. Select Parent Account *</span></label>
                        <select name="parent_user_id" id="js-cb-parent-select" class="field-select" required>
                            <option value="">-- Choose Parent --</option>
                            <?php foreach (($parents ?? []) as $pid => $pRow): ?>
                                <option value="<?= (int) $pid ?>"
                                        data-country="<?= esc($pRow['country_code'] ?? 'IN') ?>"
                                        data-currency="<?= esc($pRow['currency_code'] ?? 'INR') ?>">
                                    👨‍👩‍👧‍👦 <?= esc($pRow['display_name']) ?> (<?= esc($pRow['country_code'] ?? 'IN') ?> — <?= esc($pRow['currency_code'] ?? 'INR') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>2. Select Linked Child *</span></label>
                        <select name="student_user_id" id="js-cb-student-select" class="field-select" required>
                            <option value="">-- Choose Child --</option>
                            <?php foreach (($students ?? []) as $sid => $sRow): ?>
                                <option value="<?= (int) $sid ?>"
                                        data-parent="<?= (int) ($sRow['parent_user_id'] ?? 0) ?>"
                                        data-country="<?= esc($sRow['country_code'] ?? 'IN') ?>"
                                        data-currency="<?= esc($sRow['currency_code'] ?? 'INR') ?>">
                                    🎓 <?= esc($sRow['display_name']) ?> (<?= esc($sRow['currency_code'] ?? 'INR') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Payment Title / Purpose *</span></label>
                    <input type="text" name="title" class="field-input" placeholder="e.g. Abacus Starter Kit & Books / Championship Exam Fee / Transport" required>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Remarks / Details for Parent</span></label>
                    <input type="text" name="remarks" class="field-input" placeholder="Optional note shown on Parent's invoice & receipt...">
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span class="text-emerald">Auto-Detected Currency</span></label>
                        <input type="text" name="currency" id="js-cb-currency" class="field-input mono-input" value="INR" readonly>
                        <input type="hidden" name="country_code" id="js-cb-country" value="IN">
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Base Amount *</span></label>
                        <input type="number" step="0.01" name="subtotal_amount" id="js-cb-subtotal" class="field-input mono-input" placeholder="e.g. 500" required>
                    </div>

                    <div class="field-group">
                        <label class="field-label"><span>Tax / GST (%)</span></label>
                        <input type="number" step="0.01" name="tax_percent" id="js-cb-tax" class="field-input mono-input" value="0">
                    </div>
                </div>

                <div class="grid-2-col" style="align-items:center;">
                    <div class="field-group">
                        <label class="field-label"><span>Payment Due Date *</span></label>
                        <input type="date" name="due_date" class="field-input" value="<?= date('Y-m-d', strtotime('+5 days')) ?>" required>
                    </div>
                    <div class="panel-card" style="padding:0.75rem 1rem;">
                        <span class="mono-label">TOTAL PAYABLE BY PARENT</span>
                        <div class="identity-name text-emerald" id="js-cb-total-preview" style="font-size:1.25rem;">INR 0.00</div>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">🧾 Send Payment Request to Parent Portal</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Generated Invoices &amp; Custom Requests (<?= count($invoices ?? []) ?>)</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice &amp; Student</th>
                            <th>Title &amp; Remark</th>
                            <th>Total Amount</th>
                            <th>Due Date &amp; Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="5" class="muted-cell">No invoices generated yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <?php
                                    $sid   = (int) $inv['student_user_id'];
                                    $sName = $allUsers[$sid]['display_name'] ?? 'Student';
                                    $st    = strtolower((string) $inv['status']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="mono-cell text-cyan"><?= esc($inv['invoice_no']) ?></div>
                                        <div class="identity-name">🎓 <?= esc($sName) ?></div>
                                    </td>
                                    <td>
                                        <div class="identity-name"><?= esc($inv['title']) ?></div>
                                        <?php if (! empty($inv['remarks'])): ?>
                                            <div class="muted-cell" style="font-size:0.8rem;"><?= esc($inv['remarks']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="mono-cell text-emerald">
                                        <strong><?= esc($inv['currency']) ?> <?= number_format((float) $inv['total_amount'], 2) ?></strong>
                                        <?php if ((float) $inv['tax_percent'] > 0): ?>
                                            <div class="muted-cell">Incl. <?= number_format((float) $inv['tax_percent'], 1) ?>% Tax</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="mono-cell"><?= esc($inv['due_date']) ?></div>
                                        <span class="role-badge <?= $st === 'paid' ? 'text-emerald' : 'text-amber' ?>"><?= strtoupper(esc($st)) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btn-group">
                                            <?php if ($st !== 'paid'): ?>
                                                <form action="<?= base_url('addons/lms-finance/mark-paid-direct') ?>" method="post" class="lfn-inline-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="invoice_id" value="<?= (int) $inv['id'] ?>">
                                                    <input type="hidden" name="student_user_id" value="<?= $sid ?>">
                                                    <button type="submit" class="btn-primary btn-sm">✓ Mark Paid</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (! empty($canDelete)): ?>
                                                <form action="<?= base_url('addons/lms-finance/delete-invoice') ?>" method="post" class="lfn-inline-form" onsubmit="return confirm('Delete this invoice?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
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
</div>
<?= $this->endSection() ?>
