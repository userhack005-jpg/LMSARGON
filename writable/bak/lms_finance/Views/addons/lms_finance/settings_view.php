<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<?php
    $rzp = $razorpayConfig ?? [];
    $tpl = $receiptTemplate ?? [];
?>
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="grid-2-col">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">100% REAL RAZORPAY API INTEGRATION</span>
                    <h3 style="margin:0.3rem 0 0;">Razorpay Test &amp; Live Gateway Credentials</h3>
                    <p class="field-hint" style="margin:0;">Orders are created via <code>https://api.razorpay.com/v1/orders</code> and verified strictly with HMAC-SHA256 signature matching.</p>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-razorpay-settings') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                        <input type="checkbox" name="is_enabled" value="1" <?= ! empty($rzp['is_enabled']) ? 'checked' : '' ?>>
                        <strong>⚡ Enable Razorpay Online Checkout on Enrollment &amp; Parent Portal</strong>
                    </label>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Gateway Mode (`test` or `live`) *</span></label>
                    <select name="mode" class="field-select">
                        <option value="test" <?= ($rzp['mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>🧪 Test Mode (Uses test_key_id &amp; test_key_secret)</option>
                        <option value="live" <?= ($rzp['mode'] ?? '') === 'live' ? 'selected' : '' ?>>🔒 Live Mode (Uses live_key_id &amp; live_key_secret)</option>
                    </select>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span class="text-cyan">Test Key ID (`test_key_id`)</span></label>
                        <input type="text" name="test_key_id" class="field-input mono-input" value="<?= esc($rzp['test_key_id'] ?? '') ?>" placeholder="rzp_test_...">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span class="text-cyan">Test Key Secret (`test_key_secret`)</span></label>
                        <input type="password" name="test_key_secret" class="field-input mono-input" value="<?= esc($rzp['test_key_secret'] ?? '') ?>" placeholder="Enter Test Key Secret">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span class="text-emerald">Live Key ID (`live_key_id`)</span></label>
                        <input type="text" name="live_key_id" class="field-input mono-input" value="<?= esc($rzp['live_key_id'] ?? '') ?>" placeholder="rzp_live_...">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span class="text-emerald">Live Key Secret (`live_key_secret`)</span></label>
                        <input type="password" name="live_key_secret" class="field-input mono-input" value="<?= esc($rzp['live_key_secret'] ?? '') ?>" placeholder="Enter Live Key Secret">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Webhook Secret (`webhook_secret` — Optional)</span></label>
                    <input type="password" name="webhook_secret" class="field-input mono-input" value="<?= esc($rzp['webhook_secret'] ?? '') ?>" placeholder="Optional Razorpay Webhook Secret">
                </div>

                <button type="submit" class="btn-primary btn-full">💾 Save Razorpay API Configuration</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-cyan">OFFICIAL RECEIPT DESIGNER</span>
                    <h3 style="margin:0.3rem 0 0;">Receipt Branding &amp; Signatory</h3>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-receipt-template') ?>" method="post">
                <?= csrf_field() ?>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Academy / Organization Name</span></label>
                        <input type="text" name="academy_name" class="field-input" value="<?= esc($tpl['academy_name'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Header Accent Color</span></label>
                        <input type="text" name="accent_color" class="field-input mono-input" value="<?= esc($tpl['accent_color'] ?? '#10b981') ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Address / Tagline</span></label>
                    <input type="text" name="address_line" class="field-input" value="<?= esc($tpl['address_line'] ?? '') ?>">
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Support Email</span></label>
                        <input type="text" name="support_email" class="field-input" value="<?= esc($tpl['support_email'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Support Phone</span></label>
                        <input type="text" name="support_phone" class="field-input" value="<?= esc($tpl['support_phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Tax / GST Number (Optional)</span></label>
                        <input type="text" name="tax_number" class="field-input mono-input" value="<?= esc($tpl['tax_number'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Authorized Signatory Title</span></label>
                        <input type="text" name="signature_name" class="field-input" value="<?= esc($tpl['signature_name'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">🎨 Save Official Receipt Branding</button>
            </form>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
