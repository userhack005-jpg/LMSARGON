<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $pendingOffline = [];
    foreach (($transactions ?? []) as $tx) {
        if (($tx['status'] ?? '') === 'pending_verification') {
            $pendingOffline[] = $tx;
        }
    }
?>
<div class="lfn-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <?php if (! empty($pendingOffline)): ?>
    <section class="console-card lfn-section-gap" style="border: 1px solid #f59e0b;">
        <div class="card-head">
            <div>
                <span class="role-badge text-amber">⏳ AWAITING VERIFICATION (<?= count($pendingOffline) ?>)</span>
                <h3 style="margin-top:0.3rem;">Offline Payment Screenshot &amp; UTR Approvals</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student &amp; Parent</th>
                        <th>Fee Description</th>
                        <th>Amount</th>
                        <th>Submitted UTR / Ref</th>
                        <th>Uploaded Screenshot</th>
                        <th>Approve / Reject</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOffline as $ptx): ?>
                        <?php
                            $sid      = (int) $ptx['student_user_id'];
                            $sName    = $allUsers[$sid]['display_name'] ?? 'Student';
                            $proofUrl = trim((string) ($ptx['proof_file_url'] ?? ''));
                            if ($proofUrl !== '' && strpos($proofUrl, 'http') !== 0) {
                                $proofUrl = base_url(ltrim($proofUrl, '/'));
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="identity-name">🎓 <?= esc($sName) ?></div>
                                <div class="identity-email">👨‍👩‍👧‍👦 <?= esc($ptx['payer_name']) ?></div>
                            </td>
                            <td><?= esc($ptx['invoice_title'] ?: 'Course Fee') ?></td>
                            <td class="mono-cell text-emerald"><strong><?= esc($ptx['currency']) ?> <?= number_format((float) $ptx['amount'], 2) ?></strong></td>
                            <td class="mono-cell text-cyan"><code><?= esc($ptx['txn_reference']) ?></code></td>
                            <td>
                                <?php if ($proofUrl !== ''): ?>
                                    <a href="<?= esc($proofUrl) ?>" target="_blank" class="btn-secondary btn-sm">🖼 View Screenshot ↗</a>
                                <?php else: ?>
                                    <span class="muted-cell">No File</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btn-group">
                                    <form action="<?= base_url('addons/lms-finance/approve-offline-txn') ?>" method="post" class="lfn-inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="txn_id" value="<?= (int) $ptx['id'] ?>">
                                        <button type="submit" class="btn-primary btn-sm">✓ Approve &amp; Issue Receipt</button>
                                    </form>
                                    <form action="<?= base_url('addons/lms-finance/reject-offline-txn') ?>" method="post" class="lfn-inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="txn_id" value="<?= (int) $ptx['id'] ?>">
                                        <button type="submit" class="btn-action text-danger">✕ Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <section class="console-card lfn-section-gap">
        <div class="card-head lfn-flex-between">
            <div>
                <h3>Student Course Subscriptions &amp; Direct Mark-As-Paid</h3>
                <p class="field-hint" style="margin:0;">Invoices auto-generate <?= (int) $autoInvoiceDays ?> days before each student's cycle due date. Admin can also mark any student paid in 1 click.</p>
            </div>
            <form action="<?= base_url('addons/lms-finance/save-settings') ?>" method="post" class="lfn-grace-bar">
                <?= csrf_field() ?>
                <span class="mono-cell">Auto-Invoice:</span>
                <input type="number" name="auto_invoice_days" value="<?= (int) $autoInvoiceDays ?>" class="field-input lfn-input-xs" min="1" max="30">
                <span class="mono-cell">d before due | Grace:</span>
                <input type="number" name="grace_period_days" value="<?= (int) $graceDays ?>" class="field-input lfn-input-xs" min="0" max="60">
                <span class="mono-cell">d</span>
                <label class="lfn-check-label">
                    <input type="checkbox" name="auto_lock_overdue" value="1" <?= ! empty($autoLockOn) ? 'checked' : '' ?>>
                    <span>Auto-Lock Overdue</span>
                </label>
                <button type="submit" class="btn-secondary btn-sm">Save Rule</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student &amp; Parent</th>
                        <th>Enrolled Course &amp; Batch Dates</th>
                        <th>Cycle Fee</th>
                        <th>Next Due Date</th>
                        <th>Access Status</th>
                        <th>Admin Direct Mark Paid / Reminder</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscriptions)): ?>
                        <tr><td colspan="6" class="muted-cell">No active student course subscriptions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subscriptions as $sub): ?>
                            <?php
                                $sid      = (int) $sub['student_user_id'];
                                $pid      = (int) $sub['parent_user_id'];
                                $sName    = $allUsers[$sid]['display_name'] ?? 'Student';
                                $pName    = $allUsers[$pid]['display_name'] ?? 'Parent';
                                $bTitle   = $nodesById[(int) $sub['node_id']] ?? 'Course';
                                $isLocked = ((int) $sub['is_access_locked'] === 1);
                            ?>
                            <tr>
                                <td>
                                    <div class="identity-name">🎓 <?= esc($sName) ?></div>
                                    <div class="identity-email">👨‍👩‍👧‍👦 <?= esc($pName) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name text-emerald"><?= esc($bTitle) ?></div>
                                    <div class="mono-cell muted-cell">
                                        Batch: <?= esc($sub['batch_start_date'] ?: 'Active') ?> to <?= esc($sub['batch_end_date'] ?: 'Ongoing') ?>
                                    </div>
                                </td>
                                <td class="mono-cell">
                                    <strong><?= esc($sub['currency']) ?> <?= number_format((float) $sub['fee_amount'], 2) ?></strong>
                                    <div class="muted-cell"><?= esc($sub['billing_cycle']) ?></div>
                                </td>
                                <td class="mono-cell">
                                    <strong><?= esc($sub['due_date']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($isLocked): ?>
                                        <span class="role-badge text-danger">🔒 LOCKED (OVERDUE)</span>
                                    <?php else: ?>
                                        <span class="role-badge text-emerald">🔓 ACTIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <form action="<?= base_url('addons/lms-finance/mark-paid-direct') ?>" method="post" class="lfn-inline-form" onsubmit="return confirm('Mark this cycle as PAID for <?= esc($sName) ?> and advance due date?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="subscription_id" value="<?= (int) $sub['id'] ?>">
                                            <input type="hidden" name="student_user_id" value="<?= $sid ?>">
                                            <input type="hidden" name="node_id" value="<?= (int) $sub['node_id'] ?>">
                                            <button type="submit" class="btn-primary btn-sm">✓ Mark as Paid</button>
                                        </form>

                                        <form action="<?= base_url('addons/lms-finance/send-reminder') ?>" method="post" class="lfn-inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="subscription_id" value="<?= (int) $sub['id'] ?>">
                                            <button type="submit" class="btn-action">🔔 Send Reminder</button>
                                        </form>
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
<?= $this->endSection() ?>
