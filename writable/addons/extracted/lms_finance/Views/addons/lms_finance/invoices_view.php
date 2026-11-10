<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<?php
    $lockedCnt   = 0;
    $unlockedCnt = 0;
    foreach (($subs ?? []) as $sItem) {
        if ((int) ($sItem['is_access_locked'] ?? 0) === 1) {
            $lockedCnt++;
        } else {
            $unlockedCnt++;
        }
    }
?>
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- 1. TOP FULL-WIDTH HORIZONTAL SUBSCRIPTION BUILDER CARD (Never cramped by Admin Sidebar) -->
    <section class="console-card" style="margin-bottom: 1.35rem;">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="lfn-badge lfn-badge-success"><span class="lfn-dot"></span> ASSIGN NEW SUBSCRIPTION</span>
                <h3 style="margin:0.35rem 0 0;">Create Student Batch Subscription</h3>
            </div>
            <span class="field-hint" style="margin:0;">Batch activates after 1st payment &bull; Recurring cycle falls on the 1st of the month</span>
        </div>

        <form action="<?= base_url('addons/lms-finance/save-subscription') ?>" method="post">
            <?= csrf_field() ?>
            <div class="lfn-assign-form-grid">
                <div class="field-group">
                    <label class="field-label"><span>Select Student *</span></label>
                    <select name="student_user_id" class="field-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach (($students ?? []) as $sid => $sRow): ?>
                            <option value="<?= (int) $sid ?>"><?= esc($sRow['display_name']) ?> (<?= esc($sRow['country_code']) ?> &bull; <?= esc($sRow['currency_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Select Course *</span></label>
                    <select name="node_id" class="field-select" required>
                        <?php foreach (($nodesById ?? []) as $nid => $nTitle): ?>
                            <option value="<?= (int) $nid ?>"><?= esc($nTitle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Billing Plan *</span></label>
                    <select name="billing_cycle" class="field-select">
                        <option value="Monthly">Monthly Plan</option>
                        <option value="Quarterly">Quarterly Plan</option>
                        <option value="Yearly">Yearly Plan</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Grace Days</span></label>
                    <input type="number" name="grace_period_days" class="field-input mono-input" value="5" min="0" max="30">
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Batch Start Date</span></label>
                    <input type="date" name="batch_start_date" class="field-input" value="<?= date('Y-m-01') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Batch End Date</span></label>
                    <input type="date" name="batch_end_date" class="field-input" value="<?= date('Y-m-t', strtotime('+3 months')) ?>">
                </div>

                <div class="field-group">
                    <button type="submit" class="lfn-btn lfn-btn-emerald lfn-btn-lg" style="width:100%;">💾 Save Subscription</button>
                </div>
            </div>
        </form>
    </section>

    <!-- 2. FULL-WIDTH ACTIVE SUBSCRIPTIONS & ACCESS LOCK TABLE -->
    <section class="console-card">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="lfn-badge lfn-badge-info"><span class="lfn-dot"></span> SUBSCRIPTIONS &amp; ACCESS CONTROL</span>
                <h3 style="margin:0.35rem 0 0;">Active Student Subscriptions &amp; Locks (<span id="js-lfn-sub-visible-cnt"><?= count($subs ?? []) ?></span>)</h3>
            </div>
            <div class="lfn-filter-pills" id="js-lfn-sub-filter-bar">
                <button type="button" class="lfn-filter-tab is-active js-lfn-sub-tab" data-lock="all">All (<?= count($subs ?? []) ?>)</button>
                <button type="button" class="lfn-filter-tab js-lfn-sub-tab" data-lock="unlocked">🔓 Active (<?= $unlockedCnt ?>)</button>
                <button type="button" class="lfn-filter-tab js-lfn-sub-tab" data-lock="locked">🔒 Locked (<?= $lockedCnt ?>)</button>
            </div>
        </div>

        <div class="lfn-toolbar-strip">
            <div class="lfn-toolbar-group">
                <input type="search" id="js-lfn-sub-search" class="lfn-search-input" placeholder="🔍 Instant search student name, course, billing plan or due date...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student &amp; Cycle Status</th>
                        <th>Course &amp; Plan</th>
                        <th>Fee Amount</th>
                        <th>Batch &amp; Due Date</th>
                        <th>Access Status &amp; Quick Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subs)): ?>
                        <tr><td colspan="5" class="muted-cell" style="text-align:center; padding:2.25rem;">No active subscriptions.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subs as $s): ?>
                            <?php
                                $sid     = (int) $s['student_user_id'];
                                $sName   = $allUsers[$sid]['display_name'] ?? 'Student';
                                $cTitle  = $nodesById[(int) $s['node_id']] ?? 'Course';
                                $locked  = ((int) $s['is_access_locked'] === 1);
                                $paidCnt = (int) ($s['paid_cycles_count'] ?? 0);
                            ?>
                            <tr class="js-lfn-sub-row" data-lock="<?= $locked ? 'locked' : 'unlocked' ?>">
                                <td>
                                    <div class="identity-name"><?= esc($sName) ?></div>
                                    <?php if ($paidCnt === 0): ?>
                                        <span class="lfn-badge lfn-badge-pending" style="margin-top:0.2rem;"><span class="lfn-dot"></span> Awaiting 1st Fee</span>
                                    <?php else: ?>
                                        <span class="lfn-badge lfn-badge-success" style="margin-top:0.2rem;"><span class="lfn-dot"></span> <?= $paidCnt ?> Cycle(s) Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="identity-name text-emerald"><?= esc($cTitle) ?></div>
                                    <div class="mono-cell text-cyan"><?= esc($s['billing_cycle']) ?> (Grace: <?= (int) ($s['grace_period_days'] ?? 5) ?>d)</div>
                                </td>
                                <td class="mono-cell">
                                    <strong style="font-size:0.95rem;"><?= esc($s['currency']) ?> <?= number_format((float) $s['fee_amount'], 2) ?></strong>
                                </td>
                                <td class="mono-cell">
                                    <div class="text-cyan"><strong>Due: <?= esc($s['due_date']) ?></strong></div>
                                    <div class="muted-cell" style="font-size:0.73rem;"><?= esc($s['batch_start_date'] ?: '-') ?> &rarr; <?= esc($s['batch_end_date'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <div class="lfn-action-bar">
                                        <form action="<?= base_url('addons/lms-finance/toggle-lock') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="lfn-btn <?= $locked ? 'lfn-btn-rose' : 'lfn-btn-emerald' ?> lfn-btn-sm">
                                                <?= $locked ? '🔒 Locked (Click to Unlock)' : '🔓 Active (Click to Lock)' ?>
                                            </button>
                                        </form>
                                        <?php if (! empty($canDelete)): ?>
                                            <form action="<?= base_url('addons/lms-finance/delete-subscription') ?>" method="post" onsubmit="return confirm('Delete this subscription?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                                <button type="submit" class="lfn-btn lfn-btn-outline lfn-btn-sm text-danger">Delete</button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var activeLock = 'all';
    var bar        = document.getElementById('js-lfn-sub-filter-bar');
    var searchInp  = document.getElementById('js-lfn-sub-search');
    var visCntEl   = document.getElementById('js-lfn-sub-visible-cnt');
    var rows       = Array.prototype.slice.call(document.querySelectorAll('.js-lfn-sub-row'));

    rows.forEach(function (r) {
        r._searchCache = (r.textContent || '').toLowerCase();
    });

    function filterSubscriptions() {
        var q   = searchInp ? searchInp.value.trim().toLowerCase() : '';
        var vis = 0;
        for (var i = 0; i < rows.length; i++) {
            var r    = rows[i];
            var lk   = r.getAttribute('data-lock') || '';
            var show = true;
            if (activeLock !== 'all' && lk !== activeLock) show = false;
            if (show && q !== '' && r._searchCache.indexOf(q) === -1) show = false;
            r.style.display = show ? '' : 'none';
            if (show) vis++;
        }
        if (visCntEl) visCntEl.textContent = String(vis);
    }

    if (bar) {
        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-lfn-sub-tab');
            if (!btn) return;
            bar.querySelectorAll('.js-lfn-sub-tab').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            activeLock = btn.getAttribute('data-lock') || 'all';
            filterSubscriptions();
        });
    }
    if (searchInp) searchInp.addEventListener('input', filterSubscriptions);
});
</script>
<?= $this->endSection() ?>
