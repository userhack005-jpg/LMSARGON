<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $accent    = $tpl['accent_color'] ?? '#10b981';
    $bStart    = ! empty($txn['batch_start_date']) ? $txn['batch_start_date'] : 'Active Batch';
    $bEnd      = ! empty($txn['batch_end_date']) ? $txn['batch_end_date'] : 'Ongoing';
    $nextDue   = ! empty($txn['next_due_date']) ? $txn['next_due_date'] : date('Y-m-d', strtotime('+30 days'));
    $subAmt    = (float) ($txn['subtotal_amount'] ?? 0) > 0 ? (float) $txn['subtotal_amount'] : (float) ($txn['amount'] ?? 0);
    $taxAmt    = (float) ($txn['tax_amount'] ?? 0);
    $totAmt    = (float) ($txn['amount'] ?? 0);
    $curr      = (string) ($txn['currency'] ?? 'INR');
?>
<div class="lfn-receipt-page-wrap">
    <div class="lfn-flex-between no-print" style="max-width:820px; margin:0 auto 1rem;">
        <a href="<?= base_url('finance/portal') ?>" class="btn-secondary btn-sm">← Back to Fee Portal</a>
        <div class="action-btn-group">
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm">🖨 Print / Save as PDF</button>
            <a href="<?= base_url('finance/receipt-pdf/' . (int) $txn['id']) ?>" class="btn-primary btn-sm">⬇ Download Direct PDF</a>
        </div>
    </div>

    <article class="lfn-branded-receipt-sheet">
        <header class="lfn-receipt-banner" style="background: <?= esc($accent) ?>;">
            <div class="lfn-receipt-brand">
                <?php if (! empty($tpl['logo_url'])): ?>
                    <img src="<?= esc($tpl['logo_url']) ?>" alt="Logo" class="lfn-receipt-logo">
                <?php endif; ?>
                <div>
                    <h1><?= esc($tpl['academy_name'] ?? 'Academy') ?></h1>
                    <p><?= esc($tpl['address_line'] ?? '') ?> | <?= esc($tpl['support_email'] ?? '') ?> | <?= esc($tpl['support_phone'] ?? '') ?></p>
                    <?php if (! empty($tpl['tax_number'])): ?>
                        <small><?= esc($tpl['tax_label'] ?? 'Tax ID') ?>: <strong><?= esc($tpl['tax_number']) ?></strong></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lfn-receipt-badge-box">
                <span>OFFICIAL FEE RECEIPT</span>
                <strong><?= esc($txn['txn_reference']) ?></strong>
                <small>Date: <?= esc(substr((string) $txn['created_at'], 0, 10)) ?></small>
            </div>
        </header>

        <div class="lfn-receipt-body">
            <div class="lfn-receipt-parties">
                <div>
                    <span class="lfn-r-label">STUDENT PROFILE</span>
                    <h3>🎓 <?= esc($studentName) ?></h3>
                    <p>Enrolled Course: <strong><?= esc($courseTitle) ?></strong></p>
                </div>
                <div>
                    <span class="lfn-r-label">BILLED TO (PARENT / GUARDIAN)</span>
                    <h3><?= esc($txn['payer_name'] ?: 'Parent') ?></h3>
                    <p><?= esc($txn['payer_email'] ?? '') ?></p>
                </div>
                <div>
                    <span class="lfn-r-label">PAYMENT STATUS</span>
                    <div class="lfn-paid-stamp">✓ PAID &amp; VERIFIED</div>
                    <p>Channel: <?= strtoupper(esc($txn['provider'] ?? 'ONLINE')) ?></p>
                </div>
            </div>

            <div class="lfn-receipt-dates-strip">
                <div>
                    <span class="lfn-r-label">BILLING PLAN</span>
                    <strong><?= esc($txn['billing_cycle'] ?? 'Monthly') ?></strong>
                </div>
                <div>
                    <span class="lfn-r-label">BATCH START DATE</span>
                    <strong><?= esc($bStart) ?></strong>
                </div>
                <div>
                    <span class="lfn-r-label">BATCH END DATE</span>
                    <strong><?= esc($bEnd) ?></strong>
                </div>
                <div>
                    <span class="lfn-r-label">NEXT FEE DUE DATE</span>
                    <strong style="color: <?= esc($accent) ?>;"><?= esc($nextDue) ?></strong>
                </div>
            </div>

            <table class="lfn-receipt-table">
                <thead>
                    <tr>
                        <th>Fee Description</th>
                        <th>Billing Cycle</th>
                        <th style="text-align:right;">Amount (<?= esc($curr) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?= esc($txn['invoice_title'] ?: ($courseTitle . ' Course Fee')) ?></strong>
                            <?php if (! empty($txn['verification_note'])): ?>
                                <div style="font-size:0.8rem; color:#64748b;"><?= esc($txn['verification_note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($txn['billing_cycle'] ?? 'Monthly') ?></td>
                        <td style="text-align:right; font-family:monospace; font-weight:700;">
                            <?= esc($curr) ?> <?= number_format($subAmt, 2) ?>
                        </td>
                    </tr>
                    <?php if ($taxAmt > 0): ?>
                    <tr>
                        <td>Tax / GST (<?= number_format((float) $txn['tax_percent'], 1) ?>%)</td>
                        <td>—</td>
                        <td style="text-align:right; font-family:monospace;">
                            <?= esc($curr) ?> <?= number_format($taxAmt, 2) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" style="text-align:right;">TOTAL PAID</th>
                        <th style="text-align:right; font-size:1.25rem; color: <?= esc($accent) ?>;">
                            <?= esc($curr) ?> <?= number_format($totAmt, 2) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>

            <footer class="lfn-receipt-footer">
                <p><?= esc($tpl['footer_note'] ?? 'Thank you for your payment!') ?></p>
                <div class="lfn-sign-box">
                    <div class="lfn-sign-line"></div>
                    <strong><?= esc($tpl['signature_name'] ?? 'Authorized Signatory') ?></strong>
                </div>
            </footer>
        </div>
    </article>
</div>
<?= $this->endSection() ?>
