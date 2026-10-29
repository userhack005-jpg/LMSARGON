<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php
    $kidsMap      = $parentKidsMap ?? [];
    $hasMultiKids = count($kidsMap) > 1;
    $viewOnly     = ! empty($isChildViewOnly);
    $rzp          = $razorpayConfig ?? [];
?>
<div class="lfn-portal-wrapper" id="js-lfn-portal-root"
     data-order-url="<?= base_url('finance/create-razorpay-order') ?>"
     data-verify-url="<?= base_url('finance/verify-razorpay-payment') ?>">

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
            <strong>🔒 Course Access Temporarily Paused:</strong>
            Your course fee is past the grace period. Complete your payment below to unlock live classes and practice drills immediately.
        </div>
    <?php endif; ?>

    <div class="lfn-compact-bar">
        <div>
            <h3 style="margin:0;">
                <?= $viewOnly ? '🎓 My Course Fee Status & Receipts (View-Only)' : '👨‍👩‍👧‍👦 Family Course Fee & Receipts Portal' ?>
            </h3>
            <p class="field-hint" style="margin:0;">
                <?= $viewOnly ? 'Switch to your Parent Account to make fee payments.' : 'View your children\'s enrolled course fees, pay online via Razorpay or upload payment proof, and download official receipts.' ?>
            </p>
        </div>

        <?php if ($hasMultiKids): ?>
            <div class="action-btn-group" id="js-lfn-child-filter-bar">
                <button type="button" class="btn-action is-active js-lfn-child-tab" data-child-id="all">
                    👨‍👩‍👧‍👦 All Children (<?= count($kidsMap) ?>)
                </button>
                <?php foreach ($kidsMap as $kidId => $kidName): ?>
                    <button type="button" class="btn-action js-lfn-child-tab" data-child-id="<?= (int) $kidId ?>">
                        🎓 <?= esc($kidName) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="lfn-cards-grid" style="margin-bottom: 1.75rem;">
        <?php foreach (($mySubs ?? []) as $sub): ?>
            <?php
                $sid      = (int) $sub['student_user_id'];
                $sName    = $allUsers[$sid]['display_name'] ?? 'Child';
                $cTitle   = $nodesById[(int) $sub['node_id']] ?? 'Enrolled Course';
                $isLocked = ((int) $sub['is_access_locked'] === 1);
            ?>
            <article class="lfn-modern-fee-card js-lfn-child-item" data-child-id="<?= $sid ?>">
                <div>
                    <div class="lfn-flex-between" style="margin-bottom:0.5rem;">
                        <span class="role-badge text-emerald">🎓 <?= esc($sName) ?></span>
                        <span class="role-badge <?= $isLocked ? 'text-danger' : 'text-cyan' ?>">
                            <?= $isLocked ? '🔒 Overdue' : ('Due: ' . esc($sub['due_date'])) ?>
                        </span>
                    </div>

                    <h3 style="margin:0.25rem 0;"><?= esc($cTitle) ?></h3>
                    <div class="mono-cell muted-cell">
                        Batch Dates: <?= esc($sub['batch_start_date'] ?: 'Active') ?> to <?= esc($sub['batch_end_date'] ?: 'Ongoing') ?>
                    </div>

                    <div class="lfn-fee-price-box">
                        <div>
                            <span class="mono-label"><?= strtoupper(esc($sub['billing_cycle'])) ?> FEE</span>
                            <div class="lfn-big-price"><?= esc($sub['currency']) ?> <?= number_format((float) $sub['fee_amount'], 2) ?></div>
                        </div>
                        <div style="text-align:right;">
                            <span class="mono-label">NEXT DUE DATE</span>
                            <div class="identity-name text-cyan"><?= esc($sub['due_date']) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (! $viewOnly): ?>
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
                                    data-title="<?= esc($cTitle . ' — ' . $sub['billing_cycle'] . ' Fee') ?>"
                                    data-amount="<?= (float) $sub['fee_amount'] ?>"
                                    data-currency="<?= esc($sub['currency']) ?>">
                                ⚡ Pay <?= esc($sub['currency']) ?> <?= number_format((float) $sub['fee_amount'], 2) ?> Online
                            </button>
                        <?php endif; ?>

                        <button type="button"
                                class="btn-secondary js-open-offline-modal"
                                data-student-id="<?= $sid ?>"
                                data-student-name="<?= esc($sName) ?>"
                                data-subscription-id="<?= (int) $sub['id'] ?>"
                                data-invoice-id="0"
                                data-node-id="<?= (int) $sub['node_id'] ?>"
                                data-title="<?= esc($cTitle . ' — ' . $sub['billing_cycle'] . ' Fee') ?>"
                                data-amount="<?= (float) $sub['fee_amount'] ?>"
                                data-currency="<?= esc($sub['currency']) ?>">
                            📤 Upload Payment Screenshot
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
                        <span class="role-badge text-emerald">🎓 <?= esc($sName) ?></span>
                        <span class="role-badge text-cyan">🧾 Custom Fee Request</span>
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
                            ⚡ Pay Online
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
                            📤 Upload Screenshot
                        </button>
                    </div>
                <?php elseif (($inv['status'] ?? '') === 'pending_verification'): ?>
                    <span class="role-badge text-amber" style="align-self:flex-start;">⏳ Screenshot Submitted — Awaiting Admin Approval</span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="console-card">
        <div class="card-head">
            <div>
                <h3>Payment History &amp; Official Fee Receipts</h3>
                <p class="field-hint" style="margin:0;">Download or print official branded fee receipts for each child.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Child Name</th>
                        <th>Receipt Ref &amp; Date</th>
                        <th>Description &amp; Batch Dates</th>
                        <th>Amount Paid</th>
                        <th>Next Due Date</th>
                        <th>Status &amp; Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myTxns)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2rem;">No fee transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($myTxns as $tx): ?>
                            <?php
                                $sid   = (int) ($tx['student_user_id'] ?? 0);
                                $sName = $allUsers[$sid]['display_name'] ?? 'Child';
                                $st    = strtolower((string) ($tx['status'] ?? 'paid'));
                            ?>
                            <tr class="js-lfn-child-item" data-child-id="<?= $sid ?>">
                                <td class="identity-name">🎓 <?= esc($sName) ?></td>
                                <td>
                                    <div class="identity-name"><?= esc($tx['txn_reference']) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc(substr((string) $tx['created_at'], 0, 16)) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($tx['invoice_title'] ?: 'Course Fee') ?></div>
                                    <?php if (! empty($tx['batch_start_date'])): ?>
                                        <div class="mono-cell muted-cell">Batch: <?= esc($tx['batch_start_date']) ?> to <?= esc($tx['batch_end_date'] ?: 'Ongoing') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="mono-cell text-emerald">
                                    <strong><?= esc($tx['currency']) ?> <?= number_format((float) $tx['amount'], 2) ?></strong>
                                </td>
                                <td class="mono-cell text-cyan"><?= esc($tx['next_due_date'] ?: '—') ?></td>
                                <td>
                                    <?php if ($st === 'paid'): ?>
                                        <div class="action-btn-group">
                                            <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id']) ?>" target="_blank" class="btn-secondary btn-sm">🖨 View Receipt</a>
                                            <a href="<?= base_url('finance/receipt-pdf/' . (int) $tx['id']) ?>" class="btn-primary btn-sm">⬇ PDF</a>
                                        </div>
                                    <?php elseif ($st === 'pending_verification'): ?>
                                        <span class="role-badge text-amber">⏳ Awaiting Admin Verification</span>
                                    <?php else: ?>
                                        <span class="role-badge text-danger"><?= strtoupper(esc($st)) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
                <button type="button" class="btn-action" id="js-off-modal-close">✕ Close</button>
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

                <button type="submit" class="btn-primary btn-full">📤 Submit Payment Proof for Verification</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
