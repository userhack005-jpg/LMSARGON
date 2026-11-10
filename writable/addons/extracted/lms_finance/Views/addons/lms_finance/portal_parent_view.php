<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="<?= base_url('addons/lms-finance/assets/lms_finance.js') ?>" defer></script>
<?php
    @date_default_timezone_set('Asia/Kolkata');
    $kidsMap      = $parentKidsMap ?? [];
    $hasMultiKids = count($kidsMap) > 1;
    $viewOnly     = ! empty($isChildViewOnly);
    $rzp          = $razorpayConfig ?? [];
    $paidByStu    = $latestPaidTxnByStu ?? [];
    $paidBySub    = $latestPaidTxnBySub ?? [];
?>
<div class="lfn-portal-wrapper" id="js-lfn-portal-root"
     data-order-url="<?= base_url('finance/create-razorpay-order') ?>"
     data-verify-url="<?= base_url('finance/verify-razorpay-payment') ?>" data-fail-url="<?= base_url('finance/log-failed-payment') ?>"
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
            <strong>Course Fee Due &mdash; Batch Access Paused:</strong>
            Please complete your due course fee payment below to activate/unlock live classes, practice labs, and exams immediately.
        </div>
    <?php endif; ?>

    <div class="lfn-compact-bar">
        <div>
            <h3 style="margin:0;">
                <?= $viewOnly ? 'My Course Fee Status &amp; Official Receipts' : 'Family Course Fee Subscriptions &amp; Official Receipts' ?>
            </h3>
            <p class="field-hint" style="margin:0;">
                <?= $viewOnly ? 'Switch to your Parent Account to make fee payments.' : 'Pay online via Razorpay or upload payment proof, and print or download your official PDF receipts anytime.' ?>
            </p>
        </div>

        <?php if ($hasMultiKids): ?>
            <div class="action-btn-group" id="js-lfn-child-filter-bar">
                <button type="button" class="btn-action is-active js-lfn-child-tab" data-child-id="all">
                    All Children (<?= count($kidsMap) ?>)
                </button>
                <?php foreach ($kidsMap as $kidId => $kidName): ?>
                    <button type="button" class="btn-action js-lfn-child-tab" data-child-id="<?= (int) $kidId ?>">
                        <?= esc($kidName) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 1. Active Subscriptions & Direct Receipt Access -->
    <div class="lfn-cards-grid" style="margin-bottom: 1.85rem;">
        <?php foreach (($mySubs ?? []) as $sub): ?>
            <?php
                $subId       = (int) $sub['id'];
                $sid         = (int) $sub['student_user_id'];
                $sName       = $allUsers[$sid]['display_name'] ?? 'Child';
                $cTitle      = \App\Models\Addons\LmsFinance\LmsFinanceModel::cleanText($nodesById[(int) $sub['node_id']] ?? 'Enrolled Course');
                $isCompleted = (($sub['status'] ?? '') === 'completed');
                $isLocked    = ((int) $sub['is_access_locked'] === 1);
                $bStart      = ! empty($sub['batch_start_date']) && $sub['batch_start_date'] !== '0000-00-00' ? $sub['batch_start_date'] : date('Y-m-01');
                $bEnd        = ! empty($sub['batch_end_date']) && $sub['batch_end_date'] !== '0000-00-00' ? $sub['batch_end_date'] : 'Ongoing';
                $nextDue     = ! empty($sub['due_date']) && $sub['due_date'] !== '0000-00-00' ? $sub['due_date'] : date('Y-m-01');
                $dueMonthLbl = date('M Y', strtotime($nextDue));
                $latestPaid  = $paidBySub[$subId] ?? ($paidByStu[$sid] ?? null);
            ?>
            <article class="lfn-modern-fee-card js-lfn-child-item" data-child-id="<?= $sid ?>">
                <div>
                    <div class="lfn-flex-between" style="margin-bottom:0.5rem;">
                        <span class="role-badge text-emerald"><?= esc($sName) ?></span>
                        <?php if ($isCompleted): ?>
                            <span class="role-badge text-emerald">Full Batch Paid</span>
                        <?php elseif ($isLocked): ?>
                            <span class="role-badge text-danger">Fee Due: <?= esc($nextDue) ?></span>
                        <?php else: ?>
                            <span class="role-badge text-cyan">Active &bull; Next Due: <?= esc($nextDue) ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 style="margin:0.25rem 0;"><?= esc($cTitle) ?></h3>
                    <div class="mono-cell muted-cell">
                        Batch Start: <strong><?= esc($bStart) ?></strong> &bull; Batch End: <strong><?= esc($bEnd) ?></strong>
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

                    <!-- Permanent Receipt Buttons on Subscription Card whenever a Paid Receipt Exists -->
                    <?php if ($latestPaid): ?>
                        <div style="margin-top:0.75rem; padding:0.65rem 0.85rem; border-radius:10px; background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.3); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                            <span class="mono-cell text-emerald">
                                <strong>Last Paid Receipt:</strong> <code><?= esc($latestPaid['txn_reference']) ?></code>
                            </span>
                            <div class="action-btn-group">
                                <a href="<?= base_url('finance/receipt-view/' . (int) $latestPaid['id']) ?>" class="btn-secondary btn-sm" style="text-decoration:none;">
                                    Print Receipt
                                </a>
                                <a href="<?= base_url('finance/receipt-view/' . (int) $latestPaid['id'] . '?download_pdf=1') ?>" class="lfn-btn lfn-btn-emerald lfn-btn-sm">
                                                ⬇ PDF
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (! $viewOnly && ! $isCompleted): ?>
                    <div class="lfn-pay-btn-group">
                        <?php if (! empty($rzp['is_enabled'])): ?>
                            <button type="button"
                                    class="btn-primary js-rzp-pay-btn"
                                    style="flex:1; justify-content:center;"
                                    data-student-id="<?= $sid ?>"
                                    data-student-name="<?= esc($sName) ?>"
                                    data-subscription-id="<?= $subId ?>"
                                    data-invoice-id="0"
                                    data-node-id="<?= (int) $sub['node_id'] ?>"
                                    data-title="<?= esc($cTitle . ' - ' . $dueMonthLbl . ' Fee') ?>"
                                    data-amount="<?= (float) $sub['fee_amount'] ?>"
                                    data-currency="<?= esc($sub['currency']) ?>">
                                <?= $isLocked ? 'Pay Now' : ('Pay Advance (' . esc($dueMonthLbl) . ')') ?> &mdash; <?= esc($sub['currency']) ?> <?= number_format((float) $sub['fee_amount'], 2) ?>
                            </button>
                        <?php endif; ?>

                        <button type="button"
                                class="btn-secondary js-open-offline-modal"
                                data-student-id="<?= $sid ?>"
                                data-student-name="<?= esc($sName) ?>"
                                data-subscription-id="<?= $subId ?>"
                                data-invoice-id="0"
                                data-node-id="<?= (int) $sub['node_id'] ?>"
                                data-title="<?= esc($cTitle . ' - ' . $dueMonthLbl . ' Fee') ?>"
                                data-amount="<?= (float) $sub['fee_amount'] ?>"
                                data-currency="<?= esc($sub['currency']) ?>">
                            Upload Screenshot
                        </button>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- 2. Clear Payment History & Permanent Receipt Download Table -->
    <section class="console-card">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="role-badge text-emerald">OFFICIAL BILLING &amp; RECEIPT LEDGER</span>
                <h3 style="margin-top:0.25rem;">All Payments, Status &amp; Instant Receipt Downloads (<?= count($myTxns ?? []) ?>)</h3>
            </div>
        </div>

        <?php if (empty($myTxns)): ?>
            <div class="panel-card" style="text-align:center; padding:2.5rem;">
                <h4 style="margin:0 0 0.35rem;">No Fee Transactions Recorded Yet</h4>
                <p class="muted-cell" style="margin:0;">Once you complete a payment above, your transaction status and Print / PDF Receipt buttons will always be available here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref &amp; Date (IST)</th>
                            <th>Child &amp; Course</th>
                            <th>Batch &amp; Next Due (1st)</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Official Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myTxns as $tx): ?>
                            <?php
                                $tid        = (int) ($tx['id'] ?? 0);
                                $sid        = (int) ($tx['student_user_id'] ?? 0);
                                $sName      = $allUsers[$sid]['display_name'] ?? ($tx['payer_name'] ?: 'Student');
                                $st         = strtolower(trim((string) ($tx['status'] ?? '')));
                                $prov       = strtolower(trim((string) ($tx['provider'] ?? '')));
                                if ($st === '') {
                                    $st = ($prov === 'offline_proof') ? 'pending_verification' : (strpos($prov, 'fail') !== false ? 'failed' : 'paid');
                                }
                                $isPaid     = in_array($st, ['paid', 'success', 'captured', 'verified', 'completed'], true);
                                $isPending  = ($st === 'pending_verification' || $st === 'pending');
                                $paidDateTm = ! empty($tx['created_at']) ? date('d M Y, h:i:s A', strtotime((string) $tx['created_at'])) . ' IST' : '-';
                                $bStart     = ! empty($tx['batch_start_date']) && $tx['batch_start_date'] !== '0000-00-00' ? $tx['batch_start_date'] : '-';
                                $bEnd       = ! empty($tx['batch_end_date']) && $tx['batch_end_date'] !== '0000-00-00' ? $tx['batch_end_date'] : '-';
                                $nextDue    = ! empty($tx['next_due_date']) && $tx['next_due_date'] !== '0000-00-00' ? $tx['next_due_date'] : '-';
                                $cleanTitle = \App\Models\Addons\LmsFinance\LmsFinanceModel::cleanText($tx['invoice_title'] ?: 'Course Tuition Fee');
                            ?>
                            <tr class="js-lfn-child-item" data-child-id="<?= $sid ?>">
                                <td class="mono-cell">
                                    <div><code><?= esc($tx['txn_reference']) ?></code></div>
                                    <div class="muted-cell" style="font-size:0.73rem;"><?= esc($paidDateTm) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($sName) ?></div>
                                    <div class="mono-cell text-emerald"><?= esc($cleanTitle) ?></div>
                                </td>
                                <td class="mono-cell">
                                    <div class="text-cyan"><strong>Next Due: <?= esc($nextDue) ?></strong></div>
                                    <div class="muted-cell" style="font-size:0.73rem;">Batch: <?= esc($bStart) ?> &rarr; <?= esc($bEnd) ?></div>
                                </td>
                                <td class="mono-cell text-emerald">
                                    <strong><?= esc($tx['currency']) ?> <?= number_format((float) $tx['amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <?php if ($isPaid): ?>
                                        <span class="lfn-badge lfn-badge-success"><span class="lfn-dot"></span> PAID</span>
                                    <?php elseif ($isPending): ?>
                                        <span class="lfn-badge lfn-badge-pending"><span class="lfn-dot"></span> PENDING</span>
                                    <?php else: ?>
                                        <span class="lfn-badge lfn-badge-danger"><span class="lfn-dot"></span> <?= strtoupper(esc($st)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isPaid): ?>
                                        <div class="action-btn-group">
                                            <a href="<?= base_url('finance/receipt-view/' . $tid) ?>" class="lfn-btn lfn-btn-outline lfn-btn-sm">
                                                🖨 Print Receipt
                                            </a>
                                            <a href="<?= base_url('finance/receipt-view/' . $tid . '?download_pdf=1') ?>" class="lfn-btn lfn-btn-emerald lfn-btn-sm">
                                                ⬇ PDF
                                            </a>
                                        </div>
                                    <?php elseif ($isPending): ?>
                                        <span class="mono-cell muted-cell">Unlocks after Admin approval</span>
                                    <?php else: ?>
                                        <span class="mono-cell text-danger">No receipt (Payment Failed)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                <button type="button" class="btn-action" id="js-off-modal-close">Close</button>
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

                <button type="submit" class="btn-primary btn-full">Submit Payment Proof for Verification</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
