<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<script src="<?= base_url('addons/lms-finance/assets/lms_finance.js') ?>" defer></script>
<div class="lfn-portal-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lfn-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-cyan">CUSTOM FEE REQUEST</span>
                    <h3 style="margin:0.3rem 0 0;">Send Custom Payment Link to Parent</h3>
                    <p class="field-hint" style="margin:0;">Currency auto-selects based on the Parent/Student's country, and you can also manually change it if needed.</p>
                </div>
            </div>

            <form action="<?= base_url('addons/lms-finance/save-custom-invoice') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>1. Select Parent (Auto-Filters Children &amp; Sets Default Currency)</span></label>
                    <select name="parent_user_id" id="js-cb-parent-select" class="field-select">
                        <option value="0">-- All Parents --</option>
                        <?php foreach (($parents ?? []) as $pid => $pRow): ?>
                            <option value="<?= (int) $pid ?>" data-currency="<?= esc($pRow['currency_code']) ?>" data-country="<?= esc($pRow['country_code']) ?>">
                                👪 <?= esc($pRow['display_name']) ?> (<?= esc($pRow['country_code']) ?>  |  <?= esc($pRow['currency_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>2. Select Child / Student *</span></label>
                    <select name="student_user_id" id="js-cb-student-select" class="field-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach (($students ?? []) as $sid => $sRow): ?>
                            <option value="<?= (int) $sid ?>"
                                    data-parent="<?= (int) ($sRow['parent_user_id'] ?? 0) ?>"
                                    data-currency="<?= esc($sRow['currency_code']) ?>"
                                    data-country="<?= esc($sRow['country_code']) ?>">
                                🎓 <?= esc($sRow['display_name']) ?> (<?= esc($sRow['country_code']) ?>  |  <?= esc($sRow['currency_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Invoice Title / Item Description *</span></label>
                    <input type="text" name="title" class="field-input" placeholder="e.g. Abacus Kit & Championship Exam Fee" required>
                </div>

                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span class="text-emerald">Currency (Auto + Editable) *</span></label>
                        <select name="currency" id="js-cb-currency" class="field-select mono-input">
                            <?php
                                $currOptions = ['INR' => 'INR (₹)', 'USD' => 'USD ($)', 'AED' => 'AED (د.إ)', 'GBP' => 'GBP (£)', 'EUR' => 'EUR (€)', 'CAD' => 'CAD ($)', 'AUD' => 'AUD ($)'];
                                foreach (($enabledCountries ?? []) as $cCode => $cInfo) {
                                    $cc = strtoupper(trim((string) ($cInfo['currency_code'] ?? '')));
                                    if ($cc !== '' && ! isset($currOptions[$cc])) {
                                        $currOptions[$cc] = $cc . ' (' . ($cInfo['name'] ?? $cCode) . ')';
                                    }
                                }
                            ?>
                            <?php foreach ($currOptions as $cKey => $cLabel): ?>
                                <option value="<?= esc($cKey) ?>"><?= esc($cLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="country_code" id="js-cb-country" value="IN">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Amount *</span></label>
                        <input type="number" step="0.01" name="subtotal_amount" id="js-cb-subtotal" class="field-input mono-input" placeholder="e.g. 500" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Tax %</span></label>
                        <input type="number" step="0.01" name="tax_percent" id="js-cb-tax" class="field-input mono-input" value="0">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Due Date</span></label>
                        <input type="date" name="due_date" class="field-input" value="<?= date('Y-m-01', strtotime('first day of next month')) ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Total Payable</span></label>
                        <div class="field-input mono-input text-emerald" id="js-cb-total-preview" style="font-weight:800;">INR 0.00</div>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">🧾 Send Custom Invoice to Parent Portal</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <h3>Generated Custom Invoices (<?= count($invoices ?? []) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Created On</th>
                            <th>Invoice #</th>
                            <th>Child</th>
                            <th>Title</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($invoices ?? []) as $inv): ?>
                            <?php
                                $sid   = (int) $inv['student_user_id'];
                                $cDate = ! empty($inv['created_at']) ? date('d M Y, h:i A', strtotime((string) $inv['created_at'])) : ' - ';
                            ?>
                            <tr>
                                <td class="mono-cell"><?= esc($cDate) ?></td>
                                <td class="mono-cell"><code><?= esc($inv['invoice_no']) ?></code></td>
                                <td class="identity-name">🎓 <?= esc($allUsers[$sid]['display_name'] ?? 'Student') ?></td>
                                <td><?= esc($inv['title']) ?></td>
                                <td class="mono-cell text-emerald"><strong><?= esc($inv['currency']) ?> <?= number_format((float) $inv['total_amount'], 2) ?></strong></td>
                                <td><span class="role-badge"><?= strtoupper(esc($inv['status'])) ?></span></td>
                                <td>
                                    <?php if (! empty($canDelete)): ?>
                                        <form action="<?= base_url('addons/lms-finance/delete-invoice') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
