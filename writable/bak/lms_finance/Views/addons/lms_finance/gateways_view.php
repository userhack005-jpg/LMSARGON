<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $rzp = $gatewaysConfig['razorpay'] ?? [];
    $tpl = $receiptTemplate ?? [];
?>
<div class="lfn-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head lfn-flex-between">
                <div>
                    <span class="mono-label text-emerald">MODULAR MULTI-GATEWAY ENGINE</span>
                    <h3>Razorpay Checkout (Sandbox / Live Mode)</h3>
                </div>
                <span class="role-badge <?= ($rzp['mode'] ?? 'sandbox') === 'live' ? 'text-emerald' : 'text-amber' ?>">
                    <?= strtoupper(esc($rzp['mode'] ?? 'SANDBOX')) ?> MODE
                </span>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-gateway-config') ?>" method="post">
                <?= csrf_field() ?>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="lfn-check-label" style="margin-top:0.5rem;">
                            <input type="checkbox" name="rzp_enabled" value="1" <?= ! empty($rzp['is_enabled']) ? 'checked' : '' ?>>
                            <strong>Enable Razorpay Online Checkout</strong>
                        </label>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Gateway Environment Mode *</span></label>
                        <select name="rzp_mode" class="field-select">
                            <option value="sandbox" <?= ($rzp['mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>🧪 Sandbox / Test Mode (For Testing &amp; Demos)</option>
                            <option value="live" <?= ($rzp['mode'] ?? '') === 'live' ? 'selected' : '' ?>>🚀 Live Production Mode (Real Payments)</option>
                        </select>
                    </div>
                </div>

                <div class="panel-card" style="margin-bottom:1rem; border-color:rgba(245, 158, 11, 0.4);">
                    <span class="mono-label text-amber">SANDBOX / TEST KEYS (FOR TESTING MODE)</span>
                    <div class="grid-2-col" style="margin-top:0.5rem;">
                        <div class="field-group" style="margin:0;">
                            <label class="field-label"><span>Razorpay Test Key ID</span></label>
                            <input type="text" name="rzp_test_key_id" class="field-input mono-input" value="<?= esc($rzp['test_key_id'] ?? '') ?>" placeholder="rzp_test_...">
                        </div>
                        <div class="field-group" style="margin:0;">
                            <label class="field-label"><span>Razorpay Test Key Secret</span></label>
                            <input type="password" name="rzp_test_key_secret" class="field-input mono-input" value="<?= esc($rzp['test_key_secret'] ?? '') ?>" placeholder="Optional for client checkout">
                        </div>
                    </div>
                </div>

                <div class="panel-card" style="margin-bottom:1rem; border-color:rgba(16, 185, 129, 0.4);">
                    <span class="mono-label text-emerald">LIVE PRODUCTION KEYS (FOR LIVE MODE)</span>
                    <div class="grid-2-col" style="margin-top:0.5rem;">
                        <div class="field-group" style="margin:0;">
                            <label class="field-label"><span>Razorpay Live Key ID</span></label>
                            <input type="text" name="rzp_live_key_id" class="field-input mono-input" value="<?= esc($rzp['live_key_id'] ?? '') ?>" placeholder="rzp_live_...">
                        </div>
                        <div class="field-group" style="margin:0;">
                            <label class="field-label"><span>Razorpay Live Key Secret</span></label>
                            <input type="password" name="rzp_live_key_secret" class="field-input mono-input" value="<?= esc($rzp['live_key_secret'] ?? '') ?>" placeholder="Live Secret Key">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">💾 Save Razorpay Gateway Settings</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="mono-label text-cyan">CUSTOMIZABLE RECEIPT ARCHITECT</span>
                    <h3>Official Fee Receipt &amp; PDF Template Designer</h3>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-receipt-template') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Academy / Brand Name on Receipt *</span></label>
                        <input type="text" name="academy_name" class="field-input" value="<?= esc($tpl['academy_name'] ?? '') ?>" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Receipt Header Accent Color</span></label>
                        <input type="color" name="accent_color" class="field-input" value="<?= esc($tpl['accent_color'] ?? '#10b981') ?>" style="height:40px; padding:4px;">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Upload Receipt Logo</span></label>
                        <input type="file" name="logo_file" class="field-input" accept="image/*">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Or Logo Image URL</span></label>
                        <input type="text" name="logo_url" class="field-input mono-input" value="<?= esc($tpl['logo_url'] ?? '') ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Tax / Registration Label</span></label>
                        <input type="text" name="tax_label" class="field-input" value="<?= esc($tpl['tax_label'] ?? 'GST / Tax Reg No.') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Tax / GST Number</span></label>
                        <input type="text" name="tax_number" class="field-input mono-input" value="<?= esc($tpl['tax_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Support Email on Receipt</span></label>
                        <input type="email" name="support_email" class="field-input" value="<?= esc($tpl['support_email'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Support Phone</span></label>
                        <input type="text" name="support_phone" class="field-input" value="<?= esc($tpl['support_phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Authorized Signatory Title</span></label>
                        <input type="text" name="signature_name" class="field-input" value="<?= esc($tpl['signature_name'] ?? 'Authorized Signatory') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Footer Thank-You Note</span></label>
                        <input type="text" name="footer_note" class="field-input" value="<?= esc($tpl['footer_note'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">🎨 Save Receipt Template Design</button>
            </form>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
