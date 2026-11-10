<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lfn-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="grid-4-col lfn-kpi-grid">
        <div class="panel-card">
            <span class="mono-label">TOTAL FEE COLLECTION</span>
            <h2 class="text-emerald lfn-kpi-val">₹<?= number_format((float) ($pnl['revenue_inr'] ?? 0), 2) ?></h2>
            <p class="muted-cell lfn-sub-note"><?= (int) ($pnl['txn_count'] ?? 0) ?> Verified Receipts</p>
        </div>
        <div class="panel-card">
            <span class="mono-label">TEACHER SALARIES PAID</span>
            <h2 class="text-cyan lfn-kpi-val">₹<?= number_format((float) ($pnl['salaries_inr'] ?? 0), 2) ?></h2>
            <p class="muted-cell lfn-sub-note"><?= count($payouts ?? []) ?> Salary Slips</p>
        </div>
        <div class="panel-card">
            <span class="mono-label">OPERATIONAL EXPENSES</span>
            <h2 class="text-danger lfn-kpi-val">₹<?= number_format((float) ($pnl['expenses_inr'] ?? 0), 2) ?></h2>
            <p class="muted-cell lfn-sub-note"><?= count($expenses ?? []) ?> Logged Expenses</p>
        </div>
        <div class="panel-card lfn-pnl-highlight">
            <span class="mono-label">NET PROFIT / LOSS</span>
            <?php $netVal = (float) ($pnl['net_pnl_inr'] ?? 0); ?>
            <h2 class="<?= $netVal >= 0 ? 'text-emerald' : 'text-danger' ?> lfn-kpi-val">
                <?= $netVal >= 0 ? '+ ₹' : '- ₹' ?><?= number_format(abs($netVal), 2) ?>
            </h2>
            <p class="muted-cell lfn-sub-note">Razorpay Mode: <strong><?= strtoupper(esc($razorpayConfig['mode'] ?? 'SANDBOX')) ?></strong></p>
        </div>
    </div>

    <section class="console-card">
        <div class="card-head lfn-flex-between">
            <div>
                <h3>Recent Fee Transactions &amp; Branded Receipts</h3>
                <p class="field-hint" style="margin:0;">All verified online Razorpay payments, approved offline proofs, and admin-marked receipts.</p>
            </div>
            <a href="<?= base_url('addons/lms-finance/invoices') ?>" class="btn-primary btn-sm">✅ Manage Subscriptions &amp; Approvals →</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Receipt Ref &amp; Date</th>
                        <th>Student &amp; Parent</th>
                        <th>Description &amp; Cycle</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                        <th>Official Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" class="muted-cell">No transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                                $sid   = (int) ($tx['student_user_id'] ?? 0);
                                $sName = $allUsers[$sid]['display_name'] ?? 'Student';
                                $st    = strtolower((string) ($tx['status'] ?? 'paid'));
                            ?>
                            <tr>
                                <td>
                                    <div class="identity-name"><?= esc($tx['txn_reference']) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc(substr((string) $tx['created_at'], 0, 16)) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name">🎓 <?= esc($sName) ?></div>
                                    <div class="identity-email">👨‍👩‍👧‍👦 <?= esc($tx['payer_name']) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($tx['invoice_title'] ?: 'Course Fee') ?></div>
                                    <span class="role-badge"><?= esc($tx['billing_cycle']) ?></span>
                                </td>
                                <td class="mono-cell text-emerald">
                                    <strong><?= esc($tx['currency']) ?> <?= number_format((float) $tx['amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <?php if ($st === 'paid'): ?>
                                        <span class="role-badge text-emerald">✓ PAID</span>
                                    <?php elseif ($st === 'pending_verification'): ?>
                                        <span class="role-badge text-amber">⏳ PENDING APPROVAL</span>
                                    <?php else: ?>
                                        <span class="role-badge text-danger"><?= strtoupper(esc($st)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($st === 'paid'): ?>
                                        <div class="action-btn-group">
                                            <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id']) ?>" target="_blank" class="btn-secondary btn-sm">🖨 View / Print</a>
                                            <a href="<?= base_url('finance/receipt-pdf/' . (int) $tx['id']) ?>" class="btn-primary btn-sm">⬇ PDF</a>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?= base_url('addons/lms-finance/invoices') ?>" class="btn-action text-amber">Review Proof →</a>
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
<?= $this->endSection() ?>
