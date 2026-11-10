<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- Clean 4-Card Admin KPI Summary -->
    <div class="lfn-kpi-grid" style="margin-bottom:1.35rem;">
        <div class="panel-card">
            <span class="mono-label text-emerald">TOTAL VERIFIED REVENUE (INR EQUIV)</span>
            <div class="lfn-big-price" style="margin-top:0.25rem;">â‚¹<?= number_format((float) ($revenueInr ?? 0), 2) ?></div>
        </div>
        <div class="panel-card">
            <span class="mono-label text-cyan">ACTIVE BATCH SUBSCRIPTIONS</span>
            <div class="lfn-big-price text-cyan" style="margin-top:0.25rem;"><?= count($subs ?? []) ?> Students</div>
        </div>
        <div class="panel-card">
            <span class="mono-label text-amber">PENDING OFFLINE UTR PROOFS</span>
            <div class="lfn-big-price text-amber" style="margin-top:0.25rem;"><?= count($pendingProofs ?? []) ?> Pending</div>
        </div>
        <div class="panel-card">
            <span class="mono-label">NET SURPLUS (AFTER PAYROLL)</span>
            <div class="lfn-big-price" style="margin-top:0.25rem;">â‚¹<?= number_format((float) ($netProfitInr ?? 0), 2) ?></div>
        </div>
    </div>

    <?php if (! empty($pendingProofs)): ?>
    <section class="console-card" style="margin-bottom:1.5rem; border:1px solid #f59e0b;">
        <div class="card-head">
            <div>
                <span class="role-badge text-amber">â³ ACTION REQUIRED: OFFLINE UTR &amp; SCREENSHOT QUEUE (<?= count($pendingProofs) ?>)</span>
                <h3 style="margin:0.25rem 0 0;">Verify Offline UPI / Bank Transfer Proofs</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Submitted Date &amp; Time</th>
                        <th>Student &amp; Parent</th>
                        <th>Course / Invoice</th>
                        <th>UTR &amp; Screenshot</th>
                        <th>Amount</th>
                        <th>Approve / Reject</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingProofs as $pp): ?>
                        <?php
                            $sid   = (int) $pp['student_user_id'];
                            $sName = $allUsers[$sid]['display_name'] ?? 'Student';
                            $pUrl  = ! empty($pp['proof_file_url']) ? base_url(ltrim((string) $pp['proof_file_url'], '/')) : '';
                            $tsStr = ! empty($pp['created_at']) ? date('d M Y, h:i A', strtotime((string) $pp['created_at'])) : 'â€”';
                        ?>
                        <tr>
                            <td class="mono-cell"><strong><?= esc($tsStr) ?></strong></td>
                            <td>
                                <div class="identity-name">ðŸŽ“ <?= esc($sName) ?></div>
                                <div class="mono-cell muted-cell">ðŸ‘¨â€ðŸ‘©â€ðŸ‘§â€ðŸ‘¦ <?= esc($pp['payer_name']) ?></div>
                            </td>
                            <td class="mono-cell text-emerald"><?= esc($pp['invoice_title']) ?></td>
                            <td class="mono-cell">
                                <div>UTR: <strong><?= esc($pp['txn_reference']) ?></strong></div>
                                <?php if ($pUrl !== ''): ?>
                                    <a href="<?= esc($pUrl) ?>" target="_blank" class="text-cyan">ðŸ–¼ View Screenshot â†—</a>
                                <?php endif; ?>
                            </td>
                            <td class="mono-cell"><strong><?= esc($pp['currency']) ?> <?= number_format((float) $pp['amount'], 2) ?></strong></td>
                            <td>
                                <form action="<?= base_url('addons/lms-finance/verify-offline-proof') ?>" method="post" style="display:flex; gap:0.4rem; align-items:center;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="txn_id" value="<?= (int) $pp['id'] ?>">
                                    <button type="submit" name="decision" value="approve" class="btn-primary btn-sm">âœ“ Approve &amp; Advance Due Date</button>
                                    <button type="submit" name="decision" value="reject" class="btn-action text-danger">âœ• Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <section class="console-card">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="role-badge text-emerald">COMPLETE PAYMENT LEDGER</span>
                <h3 style="margin:0.25rem 0 0;">All Student Fee Payments with Exact Date &amp; Time (<?= count($txns ?? []) ?>)</h3>
                <p class="field-hint" style="margin:0;">Shows exact payment timestamp (`dd M Y, hh:mm A`), student &amp; parent, course, batch start/end dates, and updated 1st-of-month next due date.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paid On (Date &amp; Time)</th>
                        <th>Student &amp; Parent</th>
                        <th>Course &amp; Covered Month</th>
                        <th>Batch Duration (Start âž” End)</th>
                        <th>Next Due (1st of Month)</th>
                        <th>Amount &amp; Ref</th>
                        <th>Status &amp; Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($txns)): ?>
                        <tr><td colspan="7" class="muted-cell" style="text-align:center; padding:2rem;">No fee transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($txns as $tx): ?>
                            <?php
                                $sid     = (int) $tx['student_user_id'];
                                $sName   = $allUsers[$sid]['display_name'] ?? 'Student';
                                $cName   = $nodesById[(int) ($tx['node_id'] ?? 0)] ?? 'Course';
                                $st      = strtolower((string) ($tx['status'] ?? 'paid'));
                                $paidAt  = ! empty($tx['created_at']) ? date('d M Y, h:i:s A', strtotime((string) $tx['created_at'])) : 'â€”';
                                $bStart  = ! empty($tx['batch_start_date']) && $tx['batch_start_date'] !== '0000-00-00' ? $tx['batch_start_date'] : 'â€”';
                                $bEnd    = ! empty($tx['batch_end_date']) && $tx['batch_end_date'] !== '0000-00-00' ? $tx['batch_end_date'] : 'â€”';
                                $nextDue = ! empty($tx['next_due_date']) && $tx['next_due_date'] !== '0000-00-00' ? $tx['next_due_date'] : 'â€”';
                            ?>
                            <tr>
                                <td class="mono-cell">
                                    <div class="text-emerald" style="font-weight:800;"><?= esc($paidAt) ?></div>
                                    <div class="muted-cell" style="font-size:0.72rem;">Method: <?= strtoupper(esc(str_replace('_', ' ', (string) $tx['provider']))) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name">ðŸŽ“ <?= esc($sName) ?></div>
                                    <div class="mono-cell muted-cell">ðŸ‘¨â€ðŸ‘©â€ðŸ‘§â€ðŸ‘¦ <?= esc($tx['payer_name']) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name text-emerald"><?= esc($cName) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc($tx['invoice_title']) ?></div>
                                </td>
                                <td class="mono-cell">
                                    <div>Start: <strong><?= esc($bStart) ?></strong></div>
                                    <div class="muted-cell">End: <strong><?= esc($bEnd) ?></strong></div>
                                </td>
                                <td class="mono-cell">
                                    <span class="role-badge text-cyan">ðŸ“… <?= esc($nextDue) ?></span>
                                </td>
                                <td class="mono-cell">
                                    <div class="text-emerald" style="font-weight:900; font-size:0.95rem;"><?= esc($tx['currency']) ?> <?= number_format((float) $tx['amount'], 2) ?></div>
                                    <div class="muted-cell" style="font-size:0.72rem;">Ref: <code><?= esc($tx['txn_reference']) ?></code></div>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <span class="role-badge <?= $st === 'paid' ? 'text-emerald' : ($st === 'pending_verification' ? 'text-amber' : 'text-danger') ?>">
                                            <?= strtoupper(esc($st)) ?>
                                        </span>
                                        <?php if ($st === 'paid'): ?>
                                            <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id']) ?>" class="btn-secondary btn-sm" style="text-decoration:none;">ðŸ–¨ Receipt</a>
                                            <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id'] . '?download_pdf=1') ?>" class="btn-primary btn-sm" style="text-decoration:none;">â¬‡ PDF</a>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-finance/delete-transaction') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this transaction?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $tx['id'] ?>">
                                                <button type="submit" class="btn-action text-danger">âœ•</button>
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
<?= $this->endSection() ?>
