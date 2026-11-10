<?= $this->extend('layouts/frontend_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="<?= base_url('addons/lms-finance/assets/lms_finance.js') ?>" defer></script>
<?php
    $q            = $courseQuote ?? [];
    $rzp          = $razorpayConfig ?? [];
    $selNid       = (int) ($q['node_id'] ?? 0);
    $cTitle       = (string) ($q['course_title'] ?? 'Course Program');
    $activeCtry   = (string) ($q['country_code'] ?? 'IN');
    $currCode     = (string) ($q['currency'] ?? 'INR');
    $currSym      = (string) ($q['symbol'] ?? '₹');
    $unitFee      = (float) ($q['amount'] ?? 1000.00);
    $isLogged     = ! empty($isLoggedIn);
    $uName        = trim((string) ($loggedUser['display_name'] ?? ($user['full_name'] ?? ($user['name'] ?? ''))));
    $uEmail       = trim((string) ($loggedUser['email'] ?? ($user['email'] ?? '')));
    $existingKids = is_array($existingKids ?? null) ? $existingKids : [];
?>
<div class="lfn-standalone-wrap"
     id="js-lfn-enroll-root"
     data-order-url="<?= base_url('finance/create-razorpay-order') ?>"
     data-rzp-mode="<?= esc($rzp['mode'] ?? 'test') ?>"
     data-rzp-key="<?= esc($rzp['key_id'] ?? '') ?>"
     data-currency="<?= esc($currCode) ?>"
     data-symbol="<?= esc($currSym) ?>"
     data-unit-fee="<?= $unitFee ?>"
     data-course-title="<?= esc($cTitle) ?>">

    <div class="lfn-flex-between" style="margin-bottom:1.15rem;">
        <a href="javascript:history.back()" class="btn-secondary btn-sm" style="text-decoration:none;">← Back to <?= esc($cTitle) ?></a>
        <span class="role-badge text-emerald">🎓 Selected Course: <?= esc($cTitle) ?></span>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <div id="js-lfn-rzp-error-banner" class="alert-banner alert-error is-hidden" style="margin-bottom:1rem;"></div>

    <form action="<?= base_url('finance/process-enroll-checkout') ?>" method="post" enctype="multipart/form-data" id="js-direct-enroll-form">
        <?= csrf_field() ?>
        <input type="hidden" name="node_id" value="<?= $selNid ?>">
        <input type="hidden" name="country_code" value="<?= esc($activeCtry) ?>">
        <input type="hidden" name="razorpay_payment_id" id="js-enroll-rzp-pay-id" value="">
        <input type="hidden" name="razorpay_order_id" id="js-enroll-rzp-ord-id" value="">
        <input type="hidden" name="razorpay_signature" id="js-enroll-rzp-sig" value="">

        <div class="lfn-enroll-grid">
            <!-- Left Column: Parent Account (Auto-Detected if Logged In) + Child Profile(s) -->
            <section class="console-card">
                <?php if ($isLogged): ?>
                    <input type="hidden" id="js-enroll-parent-name" name="parent_name" value="<?= esc($uName ?: 'Parent') ?>">
                    <input type="hidden" id="js-enroll-parent-email" name="parent_email" value="<?= esc($uEmail) ?>">
                    <input type="hidden" id="js-enroll-parent-phone" name="parent_phone" value="">

                    <div class="panel-card" style="margin-bottom:1.25rem; padding:0.9rem 1.1rem !important; background:var(--bg-canvas); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem; border-color:rgba(16,185,129,0.4);">
                        <div>
                            <span class="mono-label text-emerald">LOGGED-IN PARENT ACCOUNT</span>
                            <div style="font-weight:800; font-size:1rem; margin-top:0.15rem;">
                                👨‍👩‍👧‍👦 <?= esc($uName ?: 'Parent Account') ?>
                                <?php if ($uEmail !== ''): ?>
                                    <small class="muted-cell">(<?= esc($uEmail) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="role-badge text-emerald">✓ Verified Session</span>
                    </div>
                <?php else: ?>
                    <div class="card-head lfn-flex-between">
                        <div>
                            <span class="role-badge text-emerald">STEP 1 // PARENT ACCOUNT DETAILS</span>
                            <h3 style="margin:0.3rem 0 0;">Create Your Parent Account</h3>
                        </div>
                        <a href="<?= base_url('login') ?>" class="btn-action text-cyan" style="text-decoration:none;">Already have an account? Login →</a>
                    </div>

                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Parent / Guardian Full Name *</span></label>
                            <input type="text" name="parent_name" id="js-enroll-parent-name" class="field-input" placeholder="Your Full Name" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Parent Email Address (Login ID) *</span></label>
                            <input type="email" name="parent_email" id="js-enroll-parent-email" class="field-input" placeholder="parent@example.com" required>
                        </div>
                    </div>

                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>WhatsApp / Mobile Number *</span></label>
                            <input type="text" name="parent_phone" id="js-enroll-parent-phone" class="field-input" placeholder="Mobile Number" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span class="text-emerald">Create Login Password *</span></label>
                            <input type="password" name="parent_password" id="js-enroll-parent-pass" class="field-input" placeholder="Choose your password (min 4 chars)" minlength="4" required>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin:0.75rem 0 0.65rem; padding-top:0.75rem; border-top:1px solid var(--border-subtle);">
                    <span class="mono-label text-cyan">CHILD PROFILE(S) ENROLLING IN: <?= strtoupper(esc($cTitle)) ?></span>
                </div>

                <!-- Child Rows Container (Supports Existing Child Dropdown OR Adding New Children!) -->
                <div id="js-enroll-children-list" style="display:flex; flex-direction:column; gap:0.85rem;">
                    <div class="panel-card js-enroll-child-row" style="padding:0.95rem !important; background:var(--bg-canvas, #090a0f);">
                        <div class="lfn-flex-between" style="margin-bottom:0.55rem;">
                            <strong class="text-emerald js-enroll-kid-label">🎓 Child #1</strong>
                            <span class="mono-cell text-cyan"><?= esc($cTitle) ?> (<?= esc($currSym) ?><?= number_format($unitFee, 2) ?>)</span>
                        </div>
                        <input type="hidden" name="children[0][node_id]" value="<?= $selNid ?>">

                        <?php if (! empty($existingKids)): ?>
                            <div class="field-group" style="margin-bottom:0.65rem;">
                                <label class="field-label"><span>Select Child From Your Account or Add a New Child *</span></label>
                                <select name="children[0][existing_student_id]" class="field-select js-enroll-existing-kid-select">
                                    <?php foreach ($existingKids as $kId => $kInfo): ?>
                                        <option value="<?= (int) $kId ?>" data-name="<?= esc($kInfo['full_name']) ?>">
                                            🎓 <?= esc($kInfo['full_name']) ?> (Age <?= (int) $kInfo['age'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="0">➕ Add a New Child to My Account...</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="children[0][existing_student_id]" value="0">
                        <?php endif; ?>

                        <div class="grid-2-col js-enroll-new-kid-fields <?= ! empty($existingKids) ? 'is-hidden' : '' ?>">
                            <div class="field-group" style="margin:0;">
                                <label class="field-label"><span>Child's Full Name *</span></label>
                                <input type="text" name="children[0][name]" class="field-input js-enroll-kid-name" placeholder="Child's Full Name" <?= empty($existingKids) ? 'required' : '' ?>>
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label class="field-label"><span>Child's Age (Years) *</span></label>
                                <input type="number" name="children[0][age]" class="field-input mono-input" value="8" min="4" max="20">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- `➕ Add Another Child` Button Placed at the BOTTOM -->
                <div style="margin-top: 0.9rem;">
                    <button type="button" class="btn-secondary btn-sm" id="js-enroll-add-child-btn">➕ Add Another Child</button>
                </div>
            </section>

            <!-- Right Column: Live Order Summary & Real Razorpay Gateway -->
            <aside class="console-card" style="border: 2px solid #10b981 !important; position: sticky; top: 1.25rem;">
                <div class="lfn-flex-between" style="margin-bottom:0.65rem;">
                    <span class="role-badge text-emerald">🧾 ORDER SUMMARY</span>
                    <span class="role-badge <?= ($rzp['mode'] ?? 'test') === 'live' ? 'text-emerald' : 'text-cyan' ?>">
                        <?= ($rzp['mode'] ?? 'test') === 'live' ? '🔒 RAZORPAY LIVE' : '🧪 RAZORPAY TEST' ?>
                    </span>
                </div>

                <div id="js-enroll-summary-items" style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1rem;"></div>

                <div class="lfn-fee-price-box" style="margin-bottom:1.15rem;">
                    <div>
                        <span class="mono-label">TOTAL PAYABLE NOW</span>
                        <div class="lfn-big-price" id="js-enroll-grand-total"><?= esc($currSym) ?><?= number_format($unitFee, 2) ?> <small><?= esc($currCode) ?></small></div>
                    </div>
                    <div style="text-align:right;">
                        <span class="mono-label">BILLING CYCLE</span>
                        <div class="identity-name text-cyan">Monthly (30 Days)</div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Payment Method *</span></label>
                    <select name="payment_method" id="js-enroll-pay-method" class="field-select">
                        <option value="razorpay">⚡ Pay Online via Razorpay (UPI, Cards, Netbanking)</option>
                        <option value="offline_proof">📤 Upload Manual UPI / Bank Transfer Screenshot &amp; UTR</option>
                    </select>
                </div>

                <div id="js-enroll-offline-fields" class="panel-card is-hidden" style="margin-bottom:1rem; background:var(--bg-canvas);">
                    <div class="field-group">
                        <label class="field-label"><span>UTR / Reference Number (Compulsory) *</span></label>
                        <input type="text" name="txn_reference" id="js-enroll-off-utr" class="field-input mono-input" placeholder="Enter 12-digit UTR / Ref No.">
                    </div>
                    <div class="field-group" style="margin:0;">
                        <label class="field-label"><span>Upload Payment Screenshot *</span></label>
                        <input type="file" name="proof_file" id="js-enroll-off-file" class="field-input" accept=".png,.jpg,.jpeg,.webp,.pdf">
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full" id="js-enroll-submit-btn" style="justify-content:center; padding:0.95rem; font-size:0.96rem;">
                    🔒 Pay Now via Razorpay &amp; Enroll →
                </button>
            </aside>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
