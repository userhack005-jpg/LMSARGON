<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php
    $accent  = $tpl['accent_color'] ?? '#10b981';
    $bStart  = ! empty($txn['batch_start_date']) && $txn['batch_start_date'] !== '0000-00-00' ? $txn['batch_start_date'] : date('Y-m-01');
    $bEnd    = ! empty($txn['batch_end_date']) && $txn['batch_end_date'] !== '0000-00-00' ? $txn['batch_end_date'] : date('Y-m-t', strtotime($bStart . ' +3 months'));
    $nextDue = ! empty($txn['next_due_date']) && $txn['next_due_date'] !== '0000-00-00'
        ? $txn['next_due_date']
        : date('Y-m-01', strtotime('first day of next month'));
    $paidTs  = ! empty($txn['created_at']) ? date('d M Y, h:i:s A', strtotime((string) $txn['created_at'])) : date('d M Y, h:i A');
    $subAmt  = (float) ($txn['subtotal_amount'] ?? 0) > 0 ? (float) $txn['subtotal_amount'] : (float) ($txn['amount'] ?? 0);
    $taxAmt  = (float) ($txn['tax_amount'] ?? 0);
    $totAmt  = (float) ($txn['amount'] ?? 0);
    $curr    = (string) ($txn['currency'] ?? 'INR');
    $autoPdf = ((int) ($_GET['download_pdf'] ?? 0) === 1);
?>
<style>
@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
    body, html { background: #ffffff !important; margin: 0 !important; padding: 0 !important; }
    .app-sidebar, .app-topbar, .no-print { display: none !important; }
    .lfn-branded-receipt-sheet { box-shadow: none !important; border: 1px solid #cbd5e1 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; }
}
</style>

<div class="lfn-receipt-page-wrap">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success no-print" style="max-width:820px; margin:0 auto 1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="lfn-flex-between no-print" style="max-width:820px; margin:0 auto 1rem;">
        <a href="<?= base_url('finance/portal') ?>" class="btn-secondary btn-sm" style="text-decoration:none;">← Back to Family Fee Portal</a>
        <div class="action-btn-group">
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm">🖨 Print Receipt</button>
            <button type="button" id="js-download-identical-pdf" class="btn-primary btn-sm" data-filename="Receipt_<?= esc(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $txn['txn_reference'])) ?>.pdf">
                ⬇ Download Identical PDF
            </button>
        </div>
    </div>

    <article class="lfn-branded-receipt-sheet" id="js-printable-receipt-card">
        <header class="lfn-receipt-banner" style="background: <?= esc($accent) ?> !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
            <div class="lfn-receipt-brand">
                <?php if (! empty($tpl['logo_url'])): ?>
                    <img src="<?= esc($tpl['logo_url']) ?>" alt="Logo" class="lfn-receipt-logo" crossorigin="anonymous">
                <?php endif; ?>
                <div>
                    <h1><?= esc($tpl['academy_name'] ?? 'Online Abacus & Mental Math Academy') ?></h1>
                    <p><?= esc($tpl['address_line'] ?? '') ?> | <?= esc($tpl['support_email'] ?? '') ?> | <?= esc($tpl['support_phone'] ?? '') ?></p>
                    <?php if (! empty($tpl['tax_number'])): ?>
                        <small><?= esc($tpl['tax_label'] ?? 'Tax ID') ?>: <strong><?= esc($tpl['tax_number']) ?></strong></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lfn-receipt-badge-box">
                <span>OFFICIAL FEE RECEIPT</span>
                <strong><?= esc($txn['txn_reference']) ?></strong>
                <small>Paid On: <?= esc($paidTs) ?></small>
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
                    <p>Method: <?= strtoupper(esc(str_replace('_', ' ', (string) ($txn['provider'] ?? 'RAZORPAY')))) ?></p>
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
                    <span class="lfn-r-label">NEXT DUE (1ST OF MONTH)</span>
                    <strong style="color: <?= esc($accent) ?>;"><?= esc($nextDue) ?></strong>
                </div>
            </div>

            <table class="lfn-receipt-table">
                <thead>
                    <tr>
                        <th>Fee Description</th>
                        <th>Paid Date &amp; Time</th>
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
                        <td style="font-family:monospace; font-size:0.84rem;"><?= esc($paidTs) ?></td>
                        <td style="text-align:right; font-family:monospace; font-weight:700;">
                            <?= esc($curr) ?> <?= number_format($subAmt, 2) ?>
                        </td>
                    </tr>
                    <?php if ($taxAmt > 0): ?>
                    <tr>
                        <td>Tax / GST (<?= number_format((float) $txn['tax_percent'], 1) ?>%)</td>
                        <td> - </td>
                        <td style="text-align:right; font-family:monospace;">
                            <?= esc($curr) ?> <?= number_format($taxAmt, 2) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" style="text-align:right;">TOTAL AMOUNT PAID</th>
                        <th style="text-align:right; font-size:1.25rem; color: <?= esc($accent) ?>;">
                            <?= esc($curr) ?> <?= number_format($totAmt, 2) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>

            <footer class="lfn-receipt-footer">
                <p><?= esc($tpl['footer_note'] ?? 'Thank you for your payment! This is a computer-generated official fee receipt.') ?></p>
                <div class="lfn-sign-box">
                    <div class="lfn-sign-line"></div>
                    <strong><?= esc($tpl['signature_name'] ?? 'Authorized Signatory') ?></strong>
                </div>
            </footer>
        </div>
    </article>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var pdfBtn = document.getElementById('js-download-identical-pdf');
    var card   = document.getElementById('js-printable-receipt-card');
    function triggerIdenticalPdfDownload() {
        if (!card || typeof window.html2pdf !== 'function') { window.print(); return; }
        var fileName = pdfBtn ? (pdfBtn.getAttribute('data-filename') || 'Official_Fee_Receipt.pdf') : 'Official_Fee_Receipt.pdf';
        if (pdfBtn) { pdfBtn.disabled = true; pdfBtn.textContent = '⏳ Generating PDF...'; }
        window.html2pdf().set({
            margin: [8, 8, 8, 8],
            filename: fileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        }).from(card).save().then(function () {
            if (pdfBtn) { pdfBtn.disabled = false; pdfBtn.textContent = '⬇ Download Identical PDF'; }
        });
    }
    if (pdfBtn) pdfBtn.addEventListener('click', triggerIdenticalPdfDownload);
    <?php if ($autoPdf): ?>setTimeout(triggerIdenticalPdfDownload, 450);<?php endif; ?>
});
</script>
<?= $this->endSection() ?>
