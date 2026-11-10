<?php
    $currSub = $activeSubTab ?? 'ledger';
    $pendCnt = count($pendingProofs ?? []);
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="lfn-compact-bar">
    <div class="lfn-stat-pills">
        <span class="role-badge text-emerald">💰 Verified Revenue: <strong>₹<?= number_format((float) ($revenueInr ?? 0), 0) ?></strong></span>
        <span class="role-badge text-amber">⏳ Pending Approvals: <strong><?= $pendCnt ?></strong></span>
        <span class="role-badge text-cyan">💵 Net Surplus: <strong>₹<?= number_format((float) ($netProfitInr ?? 0), 0) ?></strong></span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <?php if (! empty($canCollect)): ?>
            <a href="<?= base_url('addons/lms-finance') ?>" class="settings-tab <?= $currSub === 'ledger' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>💳 Transactions &amp; Approvals <?= $pendCnt > 0 ? '(' . $pendCnt . ')' : '' ?></span>
            </a>
            <a href="<?= base_url('addons/lms-finance/invoices') ?>" class="settings-tab <?= $currSub === 'invoices' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🧾 Subscriptions &amp; Locks</span>
            </a>
            <a href="<?= base_url('addons/lms-finance/custom-billing') ?>" class="settings-tab <?= $currSub === 'custom_billing' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>➕ Custom Parent Billing</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canExpenses)): ?>
            <a href="<?= base_url('addons/lms-finance/payouts') ?>" class="settings-tab <?= $currSub === 'payouts' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>💵 Payroll &amp; Expenses</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canSettings)): ?>
            <a href="<?= base_url('addons/lms-finance/settings') ?>" class="settings-tab <?= $currSub === 'settings' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>⚙ Razorpay &amp; Receipt</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('finance/portal') ?>" class="settings-tab <?= $currSub === 'portal' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>👪 Family Fee Portal</span>
        </a>
    </div>
</div>
