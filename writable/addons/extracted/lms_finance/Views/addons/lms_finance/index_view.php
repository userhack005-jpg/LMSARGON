<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<?php
    @date_default_timezone_set('Asia/Kolkata');
    $failedCnt = 0;
    $paidCnt   = 0;
    foreach (($txns ?? []) as $tRow) {
        $stCheck = strtolower(trim((string) ($tRow['status'] ?? '')));
        $prCheck = strtolower(trim((string) ($tRow['provider'] ?? '')));
        if ($stCheck === '') {
            $stCheck = ($prCheck === 'offline_proof') ? 'pending_verification' : (strpos($prCheck, 'fail') !== false ? 'failed' : 'paid');
        }
        if ($stCheck === 'failed' || $stCheck === 'rejected') {
            $failedCnt++;
        } elseif (in_array($stCheck, ['paid', 'success', 'completed', 'captured', 'verified'], true)) {
            $paidCnt++;
        }
    }
?>
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-kpi-grid">
        <div class="lfn-kpi-card">
            <div>
                <span class="mono-label text-emerald">VERIFIED REVENUE (<?= $paidCnt ?> PAID)</span>
                <div class="lfn-big-price" style="margin-top:0.25rem;">Rs. <?= number_format((float) ($revenueInr ?? 0), 2) ?></div>
            </div>
            <div class="lfn-kpi-icon">💰</div>
        </div>
        <div class="lfn-kpi-card is-amber">
            <div>
                <span class="mono-label text-amber">PENDING OFFLINE PROOFS</span>
                <div class="lfn-big-price text-amber" style="margin-top:0.25rem;"><?= count($pendingProofs ?? []) ?> Queue</div>
            </div>
            <div class="lfn-kpi-icon">⏳</div>
        </div>
        <div class="lfn-kpi-card is-rose">
            <div>
                <span class="mono-label text-danger">FAILED / REJECTED</span>
                <div class="lfn-big-price text-danger" style="margin-top:0.25rem;"><?= $failedCnt ?> Attempts</div>
            </div>
            <div class="lfn-kpi-icon">⚠️</div>
        </div>
        <div class="lfn-kpi-card is-cyan">
            <div>
                <span class="mono-label text-cyan">ACTIVE SUBSCRIPTIONS</span>
                <div class="lfn-big-price text-cyan" style="margin-top:0.25rem;"><?= count($subs ?? []) ?> Students</div>
            </div>
            <div class="lfn-kpi-icon">🎓</div>
        </div>
    </div>

    <?php if (! empty($pendingProofs)): ?>
    <section class="console-card" style="margin-bottom:1.35rem; border:1.5px solid #f59e0b !important;">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="lfn-badge lfn-badge-pending"><span class="lfn-dot"></span> OFFLINE UTR &amp; PROOF QUEUE (<?= count($pendingProofs) ?>)</span>
                <h3 style="margin:0.35rem 0 0;">Verify Parent Uploaded Payment Proofs (Image / PDF)</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time (IST)</th>
                        <th>Student &amp; Parent</th>
                        <th>Course &amp; UTR Reference</th>
                        <th>Amount &amp; Proof</th>
                        <th>Admin Decision &amp; Reject Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingProofs as $pp): ?>
                        <?php
                            $sid   = (int) $pp['student_user_id'];
                            $sName = $allUsers[$sid]['display_name'] ?? 'Student';
                            $pUrl  = ! empty($pp['proof_file_url']) ? base_url(ltrim((string) $pp['proof_file_url'], '/')) : '';
                            $isPdf = ($pUrl !== '' && preg_match('/\.pdf(\?.*)?$/i', $pUrl));
                            $tsStr = ! empty($pp['created_at']) ? date('d M Y, h:i A', strtotime((string) $pp['created_at'])) : '-';
                        ?>
                        <tr>
                            <td class="mono-cell"><strong><?= esc($tsStr) ?></strong></td>
                            <td>
                                <div class="identity-name"><?= esc($sName) ?></div>
                                <div class="mono-cell muted-cell">Parent: <?= esc($pp['payer_name']) ?></div>
                            </td>
                            <td>
                                <div class="identity-name text-emerald"><?= esc(\App\Models\Addons\LmsFinance\LmsFinanceModel::cleanText($pp['invoice_title'])) ?></div>
                                <div class="mono-cell">UTR: <strong><code><?= esc($pp['txn_reference']) ?></code></strong></div>
                            </td>
                            <td class="mono-cell">
                                <div class="text-emerald" style="font-weight:900; font-size:0.95rem;"><?= esc($pp['currency']) ?> <?= number_format((float) $pp['amount'], 2) ?></div>
                                <?php if ($pUrl !== ''): ?>
                                    <button type="button"
                                            class="lfn-btn lfn-btn-cyan lfn-btn-sm js-lfn-open-proof"
                                            data-file="<?= esc($pUrl) ?>"
                                            data-type="<?= $isPdf ? 'pdf' : 'image' ?>"
                                            data-title="UTR: <?= esc($pp['txn_reference']) ?> — <?= esc($sName) ?>"
                                            style="margin-top:0.35rem;">
                                        <?= $isPdf ? '📄 View PDF Proof' : '🖼 View Screenshot' ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="<?= base_url('addons/lms-finance/verify-offline-proof') ?>" method="post" class="lfn-action-bar js-lfn-verify-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="txn_id" value="<?= (int) $pp['id'] ?>">
                                    <input type="hidden" name="decision" value="approve" class="js-lfn-decision-input">
                                    <button type="submit" data-decision="approve" class="lfn-btn lfn-btn-emerald lfn-btn-sm js-lfn-decision-btn">✓ Approve &amp; Unlock</button>
                                    <input type="text" name="rejection_reason" class="lfn-inline-reason-input js-lfn-reason-field" placeholder="Reject comment (optional)">
                                    <button type="submit" data-decision="reject" class="lfn-btn lfn-btn-rose lfn-btn-sm js-lfn-decision-btn">✕ Reject</button>
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
                <span class="lfn-badge lfn-badge-success"><span class="lfn-dot"></span> COMPLETE TRANSACTIONS LEDGER</span>
                <h3 style="margin:0.35rem 0 0;">All Payment Transactions &amp; Status History (<span id="js-lfn-visible-cnt"><?= count($txns ?? []) ?></span>)</h3>
            </div>
            <div class="lfn-filter-pills" id="js-admin-txn-filter-bar">
                <button type="button" class="lfn-filter-tab is-active js-admin-txn-tab" data-status="all">All (<?= count($txns ?? []) ?>)</button>
                <button type="button" class="lfn-filter-tab js-admin-txn-tab" data-status="paid">Paid (<?= $paidCnt ?>)</button>
                <button type="button" class="lfn-filter-tab js-admin-txn-tab" data-status="pending_verification">Pending (<?= count($pendingProofs ?? []) ?>)</button>
                <button type="button" class="lfn-filter-tab js-admin-txn-tab" data-status="failed">Failed (<?= $failedCnt ?>)</button>
            </div>
        </div>

        <div class="lfn-toolbar-strip">
            <div class="lfn-toolbar-group">
                <input type="search" id="js-lfn-ledger-search" class="lfn-search-input" placeholder="🔍 Instant search student, parent, course, UTR or ref...">
                <input type="date" id="js-lfn-date-from" class="lfn-date-input" title="From Date">
                <input type="date" id="js-lfn-date-to" class="lfn-date-input" title="To Date">
                <button type="button" id="js-lfn-clear-filters" class="lfn-btn lfn-btn-outline lfn-btn-sm">Reset</button>
            </div>
            <button type="button" id="js-lfn-export-csv" class="lfn-btn lfn-btn-cyan lfn-btn-sm">⬇ Export CSV</button>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="js-lfn-ledger-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time (IST)</th>
                        <th>Student &amp; Parent</th>
                        <th>Course &amp; Batch Cycle</th>
                        <th>Amount &amp; Reference</th>
                        <th>Payment Status</th>
                        <th>Actions &amp; Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($txns)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2.25rem;">No transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($txns as $tx): ?>
                            <?php
                                $sid       = (int) $tx['student_user_id'];
                                $sName     = $allUsers[$sid]['display_name'] ?? 'Student';
                                $cName     = $nodesById[(int) ($tx['node_id'] ?? 0)] ?? 'Course';
                                $rawSt     = strtolower(trim((string) ($tx['status'] ?? '')));
                                $prov      = strtolower(trim((string) ($tx['provider'] ?? '')));
                                if ($rawSt === '') {
                                    $rawSt = ($prov === 'offline_proof') ? 'pending_verification' : (strpos($prov, 'fail') !== false ? 'failed' : 'paid');
                                }
                                $isPaid    = in_array($rawSt, ['paid', 'success', 'completed', 'captured', 'verified'], true);
                                $isPending = ($rawSt === 'pending_verification' || $rawSt === 'pending');
                                $isFailed  = ($rawSt === 'failed' || $rawSt === 'rejected');
                                $filterGrp = $isPaid ? 'paid' : ($isPending ? 'pending_verification' : 'failed');

                                $paidAt    = ! empty($tx['created_at']) ? date('d M Y, h:i A', strtotime((string) $tx['created_at'])) : '-';
                                $isoDate   = ! empty($tx['created_at']) ? date('Y-m-d', strtotime((string) $tx['created_at'])) : '';
                                $bStart    = ! empty($tx['batch_start_date']) && $tx['batch_start_date'] !== '0000-00-00' ? $tx['batch_start_date'] : '-';
                                $bEnd      = ! empty($tx['batch_end_date']) && $tx['batch_end_date'] !== '0000-00-00' ? $tx['batch_end_date'] : '-';
                                $nextDue   = ! empty($tx['next_due_date']) && $tx['next_due_date'] !== '0000-00-00' ? $tx['next_due_date'] : '-';
                                $pUrl      = ! empty($tx['proof_file_url']) ? base_url(ltrim((string) $tx['proof_file_url'], '/')) : '';
                                $isPdf     = ($pUrl !== '' && preg_match('/\.pdf(\?.*)?$/i', $pUrl));
                                $cleanInv  = \App\Models\Addons\LmsFinance\LmsFinanceModel::cleanText($tx['invoice_title']);
                            ?>
                            <tr class="js-admin-txn-row"
                                data-status="<?= esc($filterGrp) ?>"
                                data-date="<?= esc($isoDate) ?>"
                                data-csv-date="<?= esc($paidAt) ?>"
                                data-csv-student="<?= esc($sName) ?>"
                                data-csv-parent="<?= esc($tx['payer_name']) ?>"
                                data-csv-course="<?= esc($cName) ?>"
                                data-csv-amount="<?= esc($tx['currency'] . ' ' . number_format((float) $tx['amount'], 2, '.', '')) ?>"
                                data-csv-ref="<?= esc($tx['txn_reference']) ?>"
                                data-csv-status="<?= esc(strtoupper($rawSt)) ?>">
                                <td class="mono-cell">
                                    <div style="font-weight:800;"><?= esc($paidAt) ?></div>
                                    <div class="muted-cell" style="font-size:0.72rem;"><?= strtoupper(esc(str_replace('_', ' ', $prov ?: 'ONLINE'))) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($sName) ?></div>
                                    <div class="mono-cell muted-cell">Parent: <?= esc($tx['payer_name']) ?></div>
                                </td>
                                <td>
                                    <div class="identity-name text-emerald"><?= esc($cName) ?></div>
                                    <div class="mono-cell muted-cell"><?= esc($cleanInv) ?></div>
                                    <div class="mono-cell" style="font-size:0.73rem; margin-top:0.15rem;">
                                        Batch: <?= esc($bStart) ?> &rarr; <?= esc($bEnd) ?>
                                        <?php if ($isPaid): ?>
                                            | <span class="text-cyan">Next Due: <strong><?= esc($nextDue) ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="mono-cell">
                                    <div class="text-emerald" style="font-weight:900; font-size:0.95rem;"><?= esc($tx['currency']) ?> <?= number_format((float) $tx['amount'], 2) ?></div>
                                    <div class="muted-cell" style="font-size:0.72rem;">Ref: <code><?= esc($tx['txn_reference']) ?></code></div>
                                    <?php if ($pUrl !== ''): ?>
                                        <button type="button"
                                                class="lfn-btn lfn-btn-cyan lfn-btn-sm js-lfn-open-proof"
                                                data-file="<?= esc($pUrl) ?>"
                                                data-type="<?= $isPdf ? 'pdf' : 'image' ?>"
                                                data-title="Ref: <?= esc($tx['txn_reference']) ?> — <?= esc($sName) ?>"
                                                style="margin-top:0.25rem;">
                                            <?= $isPdf ? '📄 PDF Proof' : '🖼 Screenshot' ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isPaid): ?>
                                        <span class="lfn-badge lfn-badge-success"><span class="lfn-dot"></span> PAID</span>
                                    <?php elseif ($isPending): ?>
                                        <span class="lfn-badge lfn-badge-pending"><span class="lfn-dot"></span> PENDING</span>
                                    <?php elseif ($rawSt === 'rejected'): ?>
                                        <span class="lfn-badge lfn-badge-danger"><span class="lfn-dot"></span> REJECTED</span>
                                    <?php else: ?>
                                        <span class="lfn-badge lfn-badge-danger"><span class="lfn-dot"></span> FAILED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="lfn-action-bar">
                                        <?php if ($isPaid): ?>
                                            <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id']) ?>" class="lfn-btn lfn-btn-outline lfn-btn-sm">🖨 Receipt</a>
                                            <a href="<?= base_url('finance/receipt-view/' . (int) $tx['id'] . '?download_pdf=1') ?>" class="lfn-btn lfn-btn-emerald lfn-btn-sm">⬇ PDF</a>
                                        <?php elseif ($isPending): ?>
                                            <form action="<?= base_url('addons/lms-finance/verify-offline-proof') ?>" method="post" class="js-lfn-verify-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="txn_id" value="<?= (int) $tx['id'] ?>">
                                                <input type="hidden" name="decision" value="approve" class="js-lfn-decision-input">
                                                <button type="submit" data-decision="approve" class="lfn-btn lfn-btn-emerald lfn-btn-sm js-lfn-decision-btn">✓ Approve</button>
                                                <input type="text" name="rejection_reason" class="lfn-inline-reason-input js-lfn-reason-field" placeholder="Reject comment">
                                                <button type="submit" data-decision="reject" class="lfn-btn lfn-btn-rose lfn-btn-sm js-lfn-decision-btn">✕ Reject</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-finance/delete-transaction') ?>" method="post" onsubmit="return confirm('Delete this transaction record?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $tx['id'] ?>">
                                                <button type="submit" class="lfn-btn lfn-btn-rose lfn-btn-sm">Delete</button>
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

<!-- Smart Proof Lightbox Modal (Auto-Detects Image vs PDF Document) -->
<div id="js-lfn-proof-lightbox" class="lfn-lightbox-backdrop">
    <div class="lfn-lightbox-box">
        <div class="lfn-flex-between">
            <strong id="js-lfn-lightbox-title" style="font-size:0.92rem;">Payment Proof Preview</strong>
            <div class="lfn-action-bar">
                <a id="js-lfn-lightbox-newtab" href="#" target="_blank" class="lfn-btn lfn-btn-cyan lfn-btn-sm">↗ Open / Download File</a>
                <button type="button" id="js-lfn-lightbox-close" class="lfn-btn lfn-btn-rose lfn-btn-sm">✕ Close</button>
            </div>
        </div>
        <div class="lfn-lightbox-media-wrap">
            <img id="js-lfn-lightbox-img" src="" alt="Uploaded Payment Proof" style="display:none;">
            <iframe id="js-lfn-lightbox-pdf" class="lfn-lightbox-pdf-frame" src="" style="display:none;"></iframe>
            <div id="js-lfn-lightbox-fallback" style="display:none; padding:2rem; text-align:center;">
                <p style="margin:0 0 0.85rem; color:var(--lfn-muted);">Preview could not be rendered inline for this file format.</p>
                <a id="js-lfn-lightbox-fallback-link" href="#" target="_blank" class="lfn-btn lfn-btn-emerald">↗ Click Here to Open Proof in New Tab</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Reliable Approve / Reject + Enter-Key Handler on Offline Proof Forms
    document.querySelectorAll('.js-lfn-verify-form').forEach(function (form) {
        var decInput    = form.querySelector('.js-lfn-decision-input');
        var reasonField = form.querySelector('.js-lfn-reason-field');

        form.querySelectorAll('.js-lfn-decision-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (decInput) {
                    decInput.value = btn.getAttribute('data-decision') || 'approve';
                }
            });
        });

        if (reasonField && decInput) {
            reasonField.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    decInput.value = 'reject';
                }
            });
        }
    });

    // 2. 0ms Instant Search, Date Range & Status Filter
    var activeStatus = 'all';
    var bar          = document.getElementById('js-admin-txn-filter-bar');
    var searchInp    = document.getElementById('js-lfn-ledger-search');
    var dateFromInp  = document.getElementById('js-lfn-date-from');
    var dateToInp    = document.getElementById('js-lfn-date-to');
    var clearBtn     = document.getElementById('js-lfn-clear-filters');
    var exportBtn    = document.getElementById('js-lfn-export-csv');
    var visibleCntEl = document.getElementById('js-lfn-visible-cnt');
    var rows         = Array.prototype.slice.call(document.querySelectorAll('.js-admin-txn-row'));

    rows.forEach(function (r) {
        r._searchCache = (r.textContent || '').toLowerCase();
    });

    function applyLedgerFilters() {
        var q    = searchInp ? searchInp.value.trim().toLowerCase() : '';
        var from = dateFromInp ? dateFromInp.value : '';
        var to   = dateToInp ? dateToInp.value : '';
        var vis  = 0;

        for (var i = 0; i < rows.length; i++) {
            var row   = rows[i];
            var st    = row.getAttribute('data-status') || '';
            var rDate = row.getAttribute('data-date') || '';
            var show  = true;

            if (activeStatus !== 'all' && st !== activeStatus) show = false;
            if (show && q !== '' && row._searchCache.indexOf(q) === -1) show = false;
            if (show && from !== '' && rDate !== '' && rDate < from) show = false;
            if (show && to !== '' && rDate !== '' && rDate > to) show = false;

            row.style.display = show ? '' : 'none';
            if (show) vis++;
        }
        if (visibleCntEl) visibleCntEl.textContent = String(vis);
    }

    if (bar) {
        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-admin-txn-tab');
            if (!btn) return;
            bar.querySelectorAll('.js-admin-txn-tab').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            activeStatus = btn.getAttribute('data-status') || 'all';
            applyLedgerFilters();
        });
    }

    if (searchInp) searchInp.addEventListener('input', applyLedgerFilters);
    if (dateFromInp) dateFromInp.addEventListener('change', applyLedgerFilters);
    if (dateToInp) dateToInp.addEventListener('change', applyLedgerFilters);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInp) searchInp.value = '';
            if (dateFromInp) dateFromInp.value = '';
            if (dateToInp) dateToInp.value = '';
            activeStatus = 'all';
            if (bar) {
                bar.querySelectorAll('.js-admin-txn-tab').forEach(function (b, idx) {
                    b.classList.toggle('is-active', idx === 0);
                });
            }
            applyLedgerFilters();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var csvRows = [['Date (IST)', 'Student', 'Parent', 'Course', 'Amount', 'Reference / UTR', 'Status']];
            rows.forEach(function (r) {
                if (r.style.display === 'none') return;
                csvRows.push([
                    r.getAttribute('data-csv-date') || '',
                    r.getAttribute('data-csv-student') || '',
                    r.getAttribute('data-csv-parent') || '',
                    r.getAttribute('data-csv-course') || '',
                    r.getAttribute('data-csv-amount') || '',
                    r.getAttribute('data-csv-ref') || '',
                    r.getAttribute('data-csv-status') || ''
                ]);
            });
            var csvString = csvRows.map(function (cols) {
                return cols.map(function (val) {
                    var s = String(val).replace(/"/g, '""');
                    return '"' + s + '"';
                }).join(',');
            }).join('\r\n');

            var blob = new Blob(['\uFEFF' + csvString], { type: 'text/csv;charset=utf-8;' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href     = url;
            a.download = 'lms_finance_transactions_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    // 3. Smart Proof Lightbox Handler (Auto-Detects Image vs PDF + Fallback)
    var lbModal      = document.getElementById('js-lfn-proof-lightbox');
    var lbImg        = document.getElementById('js-lfn-lightbox-img');
    var lbPdf        = document.getElementById('js-lfn-lightbox-pdf');
    var lbFallback   = document.getElementById('js-lfn-lightbox-fallback');
    var lbFallLink   = document.getElementById('js-lfn-lightbox-fallback-link');
    var lbTitle      = document.getElementById('js-lfn-lightbox-title');
    var lbNewTab     = document.getElementById('js-lfn-lightbox-newtab');
    var lbClose      = document.getElementById('js-lfn-lightbox-close');

    function closeProofLightbox() {
        if (!lbModal) return;
        lbModal.classList.remove('is-open');
        if (lbImg) { lbImg.src = ''; lbImg.style.display = 'none'; }
        if (lbPdf) { lbPdf.src = ''; lbPdf.style.display = 'none'; }
        if (lbFallback) { lbFallback.style.display = 'none'; }
    }

    if (lbImg) {
        lbImg.addEventListener('error', function () {
            if (!lbModal || !lbModal.classList.contains('is-open')) return;
            var currentUrl = lbImg.getAttribute('src') || '';
            if (!currentUrl) return;
            lbImg.style.display = 'none';
            // If file lacks extension but is actually a PDF, try loading in PDF frame or show direct button
            if (lbFallback) {
                lbFallback.style.display = 'block';
            }
        });
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-lfn-open-proof');
        if (!trigger || !lbModal) return;
        e.preventDefault();

        var fileUrl  = trigger.getAttribute('data-file') || trigger.getAttribute('data-img') || '';
        var fileType = (trigger.getAttribute('data-type') || '').toLowerCase();
        var title    = trigger.getAttribute('data-title') || 'Payment Proof Preview';

        if (!fileUrl) return;

        var isPdf = (fileType === 'pdf') || /\.pdf(\?.*)?$/i.test(fileUrl);

        if (lbTitle) lbTitle.textContent = title;
        if (lbNewTab) lbNewTab.href = fileUrl;
        if (lbFallLink) lbFallLink.href = fileUrl;
        if (lbFallback) lbFallback.style.display = 'none';

        if (isPdf) {
            if (lbImg) { lbImg.src = ''; lbImg.style.display = 'none'; }
            if (lbPdf) {
                lbPdf.src = fileUrl;
                lbPdf.style.display = 'block';
            }
        } else {
            if (lbPdf) { lbPdf.src = ''; lbPdf.style.display = 'none'; }
            if (lbImg) {
                lbImg.style.display = 'inline-block';
                lbImg.src = fileUrl;
            }
        }

        lbModal.classList.add('is-open');
    });

    if (lbClose && lbModal) {
        lbClose.addEventListener('click', closeProofLightbox);
        lbModal.addEventListener('click', function (e) {
            if (e.target === lbModal) closeProofLightbox();
        });
    }
});
</script>
<?= $this->endSection() ?>
