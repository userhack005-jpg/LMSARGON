<?php
    $currSub = $activeSubTab ?? 'overview';
    $pendCnt = (int) ($pnl['pending_count'] ?? 0);
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="lfn-compact-bar">
    <div class="lfn-stat-pills">
        <span class="role-badge text-emerald">💰 Fee Collected: <strong>₹<?= number_format((float) ($pnl['revenue_inr'] ?? 0), 2) ?></strong></span>
        <span class="role-badge text-cyan">💸 Teacher Salaries: <strong>₹<?= number_format((float) ($pnl['salaries_inr'] ?? 0), 2) ?></strong></span>
        <span class="role-badge">📉 Expenses: <strong>₹<?= number_format((float) ($pnl['expenses_inr'] ?? 0), 2) ?></strong></span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <a href="<?= base_url('addons/lms-finance') ?>" class="settings-tab <?= $currSub === 'overview' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>📊 Overview</span>
        </a>
        <?php if (! empty($canCollect)): ?>
            <a href="<?= base_url('addons/lms-finance/invoices') ?>" class="settings-tab <?= $currSub === 'invoices' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>✅ Subscriptions &amp; Approvals <?= $pendCnt > 0 ? '(' . $pendCnt . ' Pending)' : '' ?></span>
            </a>
            <a href="<?= base_url('addons/lms-finance/custom-billing') ?>" class="settings-tab <?= $currSub === 'custom_billing' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🧾 Custom Parent Billing</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canPayroll)): ?>
            <a href="<?= base_url('addons/lms-finance/payroll') ?>" class="settings-tab <?= $currSub === 'payroll' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>💸 Teacher Salary</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canExpenses)): ?>
            <a href="<?= base_url('addons/lms-finance/expenses') ?>" class="settings-tab <?= $currSub === 'expenses' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📉 Expenses</span>
            </a>
            <a href="<?= base_url('addons/lms-finance/gateways') ?>" class="settings-tab <?= $currSub === 'gateways' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>⚙ Razorpay &amp; Receipt Designer</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('finance/portal') ?>" class="settings-tab <?= $currSub === 'portal' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>💳 Family Fee Portal</span>
        </a>
    </div>
</div>
