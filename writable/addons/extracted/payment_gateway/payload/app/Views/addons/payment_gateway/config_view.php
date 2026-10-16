<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php if (request()->getGet('saved') === '1'): ?>
    <div class="alert-banner alert-success">Payment Gateway configuration saved successfully!</div>
<?php endif; ?>

<div class="rbac-split-grid">
    <!-- Left: Gateway API Credentials Config -->
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">ADD-ON BACKEND // V<?= esc($addon['version']) ?></span>
                <h3 class="panel-title"><?= esc($addon['name']) ?></h3>
            </div>
            <a href="<?= base_url('pay-checkout') ?>" target="_blank" class="btn-secondary btn-sm">Open Frontend Checkout ↗</a>
        </div>

        <form action="<?= base_url('addons/payment-gateway/save') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Active Gateway</span></label>
                    <select name="active_provider" class="field-select">
                        <?php $prov =$config['active_provider'] ?? 'razorpay'; ?>
                        <option value="razorpay" <?= $prov === 'razorpay' ? 'selected' : '' ?>>Razorpay (India & Global)</option>
                        <option value="stripe" <?= $prov === 'stripe' ? 'selected' : '' ?>>Stripe (Cards & Apple Pay)</option>
                        <option value="paypal" <?= $prov === 'paypal' ? 'selected' : '' ?>>PayPal Express</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Environment Mode</span></label>
                    <select name="environment" class="field-select">
                        <?php $env =$config['environment'] ?? 'sandbox'; ?>
                        <option value="sandbox" <?= $env === 'sandbox' ? 'selected' : '' ?>>Sandbox / Test Mode</option>
                        <option value="live" <?= $env === 'live' ? 'selected' : '' ?>>Live Production Mode</option>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Default Currency ISO (e.g., INR, USD, AED, GBP)</span></label>
                <input type="text" name="currency" class="field-input mono-input" value="<?= esc($config['currency'] ?? 'INR') ?>">
            </div>

            <div class="section-divider"><span class="mono-label">RAZORPAY CREDENTIALS</span></div>
            <div class="field-group">
                <label class="field-label"><span>Razorpay Key ID</span></label>
                <input type="text" name="razorpay_key_id" class="field-input mono-input" value="<?= esc($config['razorpay_key_id'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label class="field-label"><span>Razorpay Key Secret</span></label>
                <input type="password" name="razorpay_secret" class="field-input mono-input" value="<?= esc($config['razorpay_secret'] ?? '') ?>">
            </div>

            <div class="section-divider"><span class="mono-label">STRIPE CREDENTIALS</span></div>
            <div class="field-group">
                <label class="field-label"><span>Stripe Publishable Key</span></label>
                <input type="text" name="stripe_pub_key" class="field-input mono-input" value="<?= esc($config['stripe_pub_key'] ?? '') ?>">
            </div>
            <div class="field-group">
                <label class="field-label"><span>Stripe Secret Key</span></label>
                <input type="password" name="stripe_sec_key" class="field-input mono-input" value="<?= esc($config['stripe_sec_key'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-primary btn-full">Save Gateway Configuration ✓</button>
        </form>
    </section>

    <!-- Right: Live Transactions Table (Auto-Created by Add-on Model) -->
    <section class="panel-card">
        <div class="panel-header">
            <div>
                <span class="mono-label">ADD-ON DATABASE TABLE // `payment_transactions`</span>
                <h3 class="panel-title">Recent Gateway Transactions</h3>
            </div>
            <span class="req-badge">AUTO-MIGRATED</span>
        </div>

        <div class="role-table-wrapper">
            <table class="role-table">
                <thead>
                    <tr>
                        <th>TXN Reference</th>
                        <th>Provider</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" class="muted-cell">No transactions yet. Click "Open Frontend Checkout" to test a payment!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as$t): ?>
                        <tr>
                            <td class="mono-cell"><strong><?= esc($t['txn_reference']) ?></strong></td>
                            <td><span class="role-badge role-admin"><?= esc($t['provider']) ?></span></td>
                            <td>
                                <div><?= esc($t['payer_name']) ?></div>
                                <div class="identity-email"><?= esc($t['payer_email']) ?></div>
                            </td>
                            <td class="mono-cell"><strong><?= esc($t['currency']) ?> <?= esc(number_format((float)$t['amount'], 2)) ?></strong></td>
                            <td><span class="status-indicator status-active"><?= esc(strtoupper($t['status'])) ?></span></td>
                            <td class="mono-cell muted-cell"><?= esc($t['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>