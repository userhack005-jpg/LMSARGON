<?php
    $layoutToExtend = ! empty($isLoggedIn) ? 'layouts/master_layout' : 'layouts/frontend_layout';
?>
<?= $this->extend($layoutToExtend) ?>

<?= $this->section('content') ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php
    $q   = $courseQuote ?? [];
    $rzp = $razorpayConfig ?? [];
?>
<div style="max-width: 760px; margin: 2.5rem auto; padding: 0 1.25rem;">
    <section class="console-card">
        <div class="card-head lfn-flex-between">
            <div>
                <span class="role-badge text-emerald">🎓 DIRECT COURSE ENROLLMENT</span>
                <h2 style="margin:0.35rem 0 0;"><?= esc($q['course_title'] ?? 'Academic Course') ?></h2>
            </div>
            <div style="text-align:right;">
                <span class="mono-label">LOCALIZED COURSE FEE</span>
                <div class="lfn-big-price"><?= esc($q['symbol'] ?? '₹') ?><?= number_format((float) ($q['amount'] ?? 0), 2) ?> <small><?= esc($q['currency'] ?? 'INR') ?></small></div>
            </div>
        </div>

        <form action="<?= base_url('finance/process-enroll-checkout') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="node_id" value="<?= (int) ($q['node_id'] ?? 0) ?>">

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Your Country *</span></label>
                    <select name="country_code" class="field-select" onchange="window.location.href='<?= base_url('finance/enroll-checkout/' . (int) ($q['node_id'] ?? 0)) ?>?country=' + this.value">
                        <?php foreach (($enabledCountries ?? []) as $cCode => $cInfo): ?>
                            <option value="<?= esc($cCode) ?>" <?= ($q['country_code'] ?? 'IN') === $cCode ? 'selected' : '' ?>>
                                <?= esc($cInfo['flag'] ?? '') ?> <?= esc($cInfo['name'] ?? $cCode) ?> (<?= esc($cInfo['currency_code'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Parent / Guardian Name *</span></label>
                    <input type="text" name="parent_name" class="field-input" value="<?= esc($user['full_name'] ?? '') ?>" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Parent Email Address *</span></label>
                    <input type="email" name="parent_email" class="field-input" value="<?= esc($user['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Child / Student Full Name *</span></label>
                    <input type="text" name="child_name" class="field-input" placeholder="e.g. Aarav Sharma" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Child Age (Years) *</span></label>
                    <input type="number" name="child_age" class="field-input mono-input" value="8" min="4" max="20" required>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Choose Payment Method *</span></label>
                <select name="payment_method" id="js-enroll-pay-method" class="field-select">
                    <option value="razorpay">⚡ Pay Online Instantly via Razorpay (<?= esc($q['currency']) ?> <?= number_format((float) $q['amount'], 2) ?>)</option>
                    <option value="offline_proof">📤 Upload Offline / UPI Payment Screenshot &amp; UTR</option>
                </select>
            </div>

            <div id="js-enroll-offline-fields" class="panel-card is-hidden" style="margin-bottom:1rem;">
                <div class="grid-2-col">
                    <div class="field-group" style="margin:0;">
                        <label class="field-label"><span>Transaction / UTR Reference No. *</span></label>
                        <input type="text" name="txn_reference" class="field-input mono-input" placeholder="Enter Reference No.">
                    </div>
                    <div class="field-group" style="margin:0;">
                        <label class="field-label"><span>Upload Payment Screenshot *</span></label>
                        <input type="file" name="proof_file" class="field-input" accept=".png,.jpg,.jpeg,.webp,.pdf">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary btn-full" style="justify-content:center; padding:0.85rem;">
                🎓 Complete Enrollment &amp; Pay <?= esc($q['currency']) ?> <?= number_format((float) $q['amount'], 2) ?> →
            </button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
