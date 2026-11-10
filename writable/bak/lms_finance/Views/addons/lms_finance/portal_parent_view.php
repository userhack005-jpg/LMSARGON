<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="<?= base_url('addons/lms-finance/assets/lms_finance.js') ?>" defer></script>
<?php
    $kidsMap      = $parentKidsMap ?? [];
    $hasMultiKids = count($kidsMap) > 1;
    $viewOnly     = ! empty($isChildViewOnly);
    $rzp          = $razorpayConfig ?? [];
?>
<div class="lfn-portal-wrapper" id="js-lfn-portal-root"
     data-order-url="<?= base_url('finance/create-razorpay-order') ?>"
     data-verify-url="<?= base_url('finance/verify-razorpay-payment') ?>"
     data-rzp-mode="<?= esc($rzp['mode'] ?? 'test') ?>"
     data-rzp-key="<?= esc($rzp['key_id'] ?? '') ?>">

    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (! empty($isLockedBanner)): ?>
        <div class="alert-banner alert-error" style="margin-bottom:1.25rem;">
            <strong>ðŸ”’ Current Month Fee Due â€” Course Access Paused:</strong>
            Your monthly fee (due on the 1st of the month) is pending. Complete your payment below to unlock live classes, practice labs, and exams immediately.
        </div>
    <?php endif; ?>

    <div class="lfn-compact-bar">
        <div>
            <h3 style="margin:0;">
                <?= $viewOnly ? 'ðŸŽ“ My Course Fee Status & Official Receipts' : 'ðŸ‘¨â€ðŸ‘©â€ðŸ‘§â€ðŸ‘¦ Family Course Fee Subscriptions & Official Receipts' ?>
            </h3>
            <p class="field-hint" style="margin:0;">
                <?= $viewOnly ? 'Switch to your Parent Account to make fee payments.' : 'Monthly fees are due on the 1st of each month until Batch End Date. Any advance payment automatically advances your Next Due Date by +1 month.' ?>
            </p>
        </div>

        <?php if ($hasMultiKids): ?>
            <div class="action-btn-group" id="js-lfn-child-filter-bar">
                <button type="button" class="btn-action is-active js-lfn-child-tab" data-child-id="all">
                    ðŸ‘¨â€ðŸ‘©â€ðŸ‘§â€ðŸ‘¦ All Children (<?= count($kidsMap) ?>)
                </button>
                <?php foreach ($kidsMap as $kidId => $kidName): ?>
                    <button type="button" class="btn-action js-lfn-child-tab" data-child-id="<?= (int) $kidId ?>">
                        ðŸŽ“ <?= esc($kidName) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="lfn-cards-grid" style="margin-bottom: 1.85rem;">
        <?php foreach (($mySubs ?? []) as $sub): ?>
            <?php
                $sid         = (int) $sub['student_user_id'];
                $sName       = $allUsers[$sid]['display_name'] ?? 'Child';
                $cTitle      = $nodesById[(int) $sub['node_id']] ?? 'Enrolled Course';
                $isCompleted = (($sub['status'] ?? '') === 'completed');
                $isLocked    = ((int) $sub['is_access_locked'] === 1);
                $bStart      = ! empty($sub['batch_start_date']) && $sub['batch_start_date'] !== '0000-00-00' ? $sub['batch_start_date'] : date('Y-m-01');
                $bEnd        = ! empty($sub['batch_end_date']) && $sub['batch_end_date'] !== '0000-00-00' ? $sub['batch_end_date'] : 'Ongoing';
                $nextDue     = ! empty($sub['due_date']) && $sub['due_date'] !== '0000-00-00' ? $sub['due_date'] : date('Y-m-01');
                $dueMonthLbl = date('M Y', strtotime($nextDue));
            ?>
            <article class="lfn-modern-fee-card js-lfn-child-item" data-child-id="<?= $sid ?>">
                <div>
                    <div class="lfn-flex-between" style="margin-bottom:0.5rem;">
                        <span class="role-badge text-emerald">ðŸŽ“ <?= esc($sName) ?></span>
                        <?php if ($isCompleted): ?>
                            <span class="role-badge text-emerald">âœ… Full Batch Paid</span>
                        <?php elseif ($isLocked): ?>
                            <span class="role-badge text-danger">ðŸ”’ Due Now: <?= esc($nextDue) ?></span>
                        <?php else: ?>
                            <span class="role-badge text-cyan">ðŸ“… Next Due (1st): <?= esc($nextDue) ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 style="margin:0.25rem 0;"><?= esc($cTitle) ?></h3>
                    <div class="mono-cell muted-cell">
                        Batch Start: <strong><?= esc($bStart) ?></strong> â€¢ Batch End: <strong><?= esc($bEnd) ?></strong>
                    </div>

                    <div class="lfn-fee-price-box">
                        <div>
                            <span class="mono-label"><?= strtoupper(esc($sub['billing_cycle'])) ?> FEE (<?= esc($dueMonthLbl) ?>)</span>
                            <div class="lfn-big-price"><?= esc($sub['currency']) ?> <?= number_format((float) $sub['fee_amount'], 2) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <span class="mono-label">NEXT DUE DATE (1ST)</span>
                            <div class="identity-name text-cyan"><?= esc($nextDue) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (! $viewOnly && ! $isCompleted): ?>
                    <div class="lfn-pay-btn-group">
                        <?php if (! empty($rzp['is_enabled'])): ?>
                            <button type="button"
                                    class="btn-primary js-rzp-pay-btn"
                                    style="flex:1; justify-content:center;"
                                    data-student-id="<?= $sid ?>"
                                    data-student-name="<?= esc($sName) ?>"
                                    data-subscription-id="<?= (int) $sub['id'] ?>"
                                    data-invoice-id="0"
                                    data-node-id="<?= (int) $sub['node_id'] ?>"
                                    data-title="<?= esc($cTitle . ' â€” ' . $dueMonthLbl . ' Fee') ?>"
                                    data-amount="<?= (float) $sub['fee_amount'] ?>"
                                    data-currency="<?= esc($sub['currency']) ?>">
                                âš¡ <?= $isLocked ? 'Pay Current Month' : ('Pay Advance (' . esc($dueMonthLbl) . ')') ?> â€” <?= esc($sub['currency']) ?> <?= number_format((float) $sub['fee_amount'], 2) ?>
                            </button>
                        <?php endif; ?>

                        <button type="button"
                                class="btn-secondary js-open-offline-modal"
                                data-student-id="<?= $sid ?>"
                                data-student-name="<?= esc($sName) ?>"
                                data-subscription-id="<?= (int) $sub['id'] ?>"
                                data-invoice-id="0"
                                data-node-id="<?= (int) $sub['node_id'] ?>"
                                data-title="<?= esc($cTitle . ' â€” ' . $dueMonthLbl . ' Fee') ?>"
                                data-amount="<?= (float) $sub['fee_amount'] ?>"
                                data-currency="<?= esc($sub['currency']) ?>">
                            ðŸ“¤ Upload Screenshot
                        </button>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php foreach (($myInvoices ?? []) as $inv): ?>
            <?php
                if (($inv['status'] ?? '') === 'paid' || ($inv['invoice_type'] ?? '') !== 'custom_request') continue;
                $sid   = (int) $inv['student_user_id'];
                $sName = $allUsers[$sid]['display_name'] ?? 'Child';
            ?>
            <article class="lfn-modern-fee-card js-lfn-child-item" data-child-id="<?= $sid ?>" style="border-color: rgba(6, 182, 212, 0.45);">
                <div>
                    <div class="lfn-flex-between" style="margin-bottom:0.5rem;">
                        <span class="role-badge text-emerald">ðŸŽ“ <?= esc($sName) ?></span>
                        <span class="role-badge text-cyan">ðŸ§¾ Custom Fee Request</span>
                    </div>
                    <h3 style="margin:0.25rem 0;"><?= esc($inv['title']) ?></h3>
                    <?php if (! empty($inv['remarks'])): ?>
                        <p class="muted-cell" style="margin:0; font-size:0.84rem;"><?= esc($inv['remarks']) ?></p>
                    <?php endif; ?>

                    <div class="lfn-fee-price-box">
                        <div>
                            <span class="mono-label">TOTAL AMOUNT DUE</span>
                            <div class="lfn-big-price"><?= esc($inv['currency']) ?> <?= number_format((float) $inv['total_amount'], 2) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <span class="mono-label">DUE DATE</span>
                            <div class="identity-name text-amber"><?= esc($inv['due_date']) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (! $viewOnly && ($inv['status'] ?? '') === 'unpaid'): ?>
                    <div class="lfn-pay-btn-group">
                        <button type="button"
                                class="btn-primary js-rzp-pay-btn"
                                style="flex:1; justify-content:center;"
                                data-student-id="<?= $sid ?>"
                                data-student-name="<?= esc($sName) ?>"
                                data-subscription-id="0"
                                data-invoice-id="<?= (int) $inv['id'] ?>"
                                data-node-id="0"
                                data-title="<?= esc($inv['title']) ?>"
                                data-amount="<?= (float) $inv['total_amount'] ?>"
                                data-currency="<?= esc($inv['currency']) ?>">
                            âš¡ Pay via Razorpay
                        </button>
                        <button type="button"
                                class="btn-secondary js-open-offline-modal"
                                data-student-id="<?= $sid ?>"
                                data-student-name="<?= esc($sName) ?>"
                                data-subscription-id="0"
                                data-invoice-id="<?= (int) $inv['id'] ?>"
                                data-node-id="0"
                                data-title="<?= esc($inv['title']) ?>"
                                data-amount="<?= (float) $inv['total_amount'] ?>"
                                data-currency="<?= esc($inv['currency']) ?>">
                            ðŸ“¤ Upload Screenshot
                        </button>
                    </div>
                <?php elseif (($inv['status'] ?? '') === 'pending_verification'): ?>
                    <span class="role-badge text-amber" style="align-self:flex-start;">â³ Screenshot Submitted â€” Awaiting Admin Approval</span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="console-card">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="role-badge text-emerald">ðŸ§¾ OFFICIAL BILLING LEDGER</span>
                <h3 style="margin-top:0.25rem;">Payment History &amp; Official Fee Receipts (<?= count($myTxns ?? []) ?>)</h3>
            </div>
        </div>

        <?php if (empty($myTxns)): ?>
            <div class="panel-card" style="text-align:center; padding:2.5rem;">
                <h4 style="margin:0 0 0.35rem;">No Fee Receipts Recorded Yet</h4>
                <p class="muted-cell" style="margin:0;">Once you complete a fee payment above, your official color receipt will appear here for instant print &amp; PDF download.</p>
            </div>
        <?php else: ?>
            <div class="lfn-receipt-ledger-stack">
                <?php foreach ($myTxns as $tx): ?>
                    <?php
                        $sid        = (int) ($tx['student_user_id'] ?? 0);
                        $sName      = $allUsers[$sid]['display_name'] ?? 'Child';
                        $st         = strtolower((string) ($tx['status'] ?? 'paid'));
                        $paidDateTm = ! empty($tx['created_at']) ? date('d M Y, h:i:s A', strtotime((string) $tx['created_at'])) : 'â€”';
                        $bStart     = ! empty($tx['batch_start_date']) && $tx['batch_start_date'] !== '0000-00-00' ? $tx['batch_start_date'] : 'â€”';
                        $bEnd       = ! empty($tx['batch_end_date']) && $tx['batch_end_date'] !== '0000-00-00' ? $tx['batch_end_date'] : 'â€”';
                        $nextDue    = ! empty($tx['next_due_date']) && $tx['next_due_date'] !== '0000-00-00' ? $tx['next_due_date'] : 'â€”';
                    ?>
                    <article class="lfn-fintech-receipt-row js-lfn-child-item" data-child-id="<?= $sid ?>">
                        <div class="lfn-fr-left">
                            <div class="lfn-fr-icon <?= $st === 'paid' ? 'is-paid' : 'is-pending' ?>">
                                <?= $st === 'paid' ? 'âœ“' : 'â³' ?>
                            </div>
                            <div>
                                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.25rem;">
                                    <span class="role-badge text-emerald">ðŸŽ“ <?= esc($sName) ?></span>
                                    <span class="mono-cell muted-cell">Ref: <code><?= esc($tx['txn_reference']) ?></code></span>
                                    <span class="mono-cell text-cyan">â€¢ Paid: <strong><?= esc($paidDateTm) ?></strong></span>
                                </div>
                                <h4 style="margin:0; font-size:1.02rem;"><?= esc($tx['invoice_title'] ?: 'Course Tuition Fee') ?></h4>
                            </div>
                        </div>

                        <div class="lfn-fr-dates">
                            <div class="lfn-fr-date-pill">
                                <span class="mono-label">BATCH START</span>
                                <strong><?= esc($bStart) ?></strong>
                            </div>
                            <div class="lfn-fr-date-pill">
                                <span class="mono-label">BATCH END</span>
                                <strong><?= esc($bEnd) ?></strong>
                            </div>
                            <div class="lfn-fr-date-pill is-due-highlight">
                                <span class="mono-label text-cyan">NEXT DUE (1ST)</span>
                                <strong class="text-cyan"><?= esc($nextDue) ?></strong>
                            </div>
                        </div>

                        <div class="lfn-fr-right">
                            <div style="text-align:right;">
                                <div class="lfn-fr-amount"><?= esc($tx['currency']) ?> <?= number_format((float) $tx['amount'], 2) ?></div>
                                <?php if ($st === 'paid'): ?>
                                    <span class="role-badge text-emerald">âœ“ VERIFIED &amp; PAID</span>
                                <?php elseif ($st === 'pending_verification'): ?>
                                    <span class="role-badge text-amber">â³ PENDING APPROVAL</span>
                                <?php else: ?>
                                    <span class="role-badge text-danger"><?= strtoupper(esc($st)) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($st === 'paid'): ?>
                                <div class="action-btn-group">
                                    <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id']) ?>" class="btn-secondary btn-sm" style="text-decoration:none;">ðŸ–¨ View / Print</a>
                                    <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id'] . '?download_pdf=1') ?>" class="btn-primary btn-sm" style="text-decoration:none;">â¬‡ PDF</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <form action="<?= base_url('finance/verify-razorpay-payment') ?>" method="post" id="js-rzp-verify-form" class="is-hidden">
        <?= csrf_field() ?>
        <input type="hidden" name="student_user_id" id="rzp_stu_id">
        <input type="hidden" name="subscription_id" id="rzp_sub_id">
        <input type="hidden" name="invoice_id" id="rzp_inv_id">
        <input type="hidden" name="node_id" id="rzp_node_id">
        <input type="hidden" name="amount" id="rzp_amount">
        <input type="hidden" name="currency" id="rzp_currency">
        <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
        <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
        <input type="hidden" name="razorpay_signature" id="rzp_signature">
    </form>

    <div class="lfn-modal is-hidden" id="js-offline-proof-modal">
        <div class="lfn-modal-dialog panel-card">
            <div class="lfn-flex-between" style="padding-bottom:0.75rem; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle);">
                <div>
                    <span class="mono-label text-emerald">MANUAL / UPI / BANK TRANSFER PROOF</span>
                    <h3 id="js-off-modal-title" style="margin:0.2rem 0 0;">Upload Payment Screenshot</h3>
                </div>
                <button type="button" class="btn-action" id="js-off-modal-close">âœ• Close</button>
            </div>

            <form action="<?= base_url('finance/submit-offline-proof') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="student_user_id" id="off_stu_id">
                <input type="hidden" name="subscription_id" id="off_sub_id">
                <input type="hidden" name="invoice_id" id="off_inv_id">
                <input type="hidden" name="node_id" id="off_node_id">

                <div class="field-group">
                    <label class="field-label"><span>Transaction / UTR Reference Number (Compulsory) *</span></label>
                    <input type="text" name="txn_reference" class="field-input mono-input" placeholder="Enter 12-digit UTR or Bank Reference No." required>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Upload Payment Screenshot (PNG, JPG, PDF) *</span></label>
                    <input type="file" name="proof_file" class="field-input" accept=".png,.jpg,.jpeg,.webp,.pdf" required>
                </div>

                <button type="submit" class="btn-primary btn-full">ðŸ“¤ Submit Payment Proof for Verification</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
