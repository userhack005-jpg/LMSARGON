<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lfn-portal-wrapper">
    <?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
    <?php endif; ?>

    <section class="console-card">
        <div class="card-head">
            <div>
                <span class="role-badge text-emerald">👩‍🏫 FACULTY PAYROLL PORTAL (VIEW-ONLY)</span>
                <h3 style="margin-top:0.3rem;">My Monthly Salary Slips</h3>
                <p class="field-hint" style="margin:0;">View your monthly salary disbursements credited by the administration.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Salary Month</th>
                        <th>Credited Amount</th>
                        <th>Payment Reference</th>
                        <th>Credited On</th>
                        <th>Remarks</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myPayouts)): ?>
                        <tr><td colspan="6" class="muted-cell" style="text-align:center; padding:2rem;">No salary slips published for your account yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($myPayouts as $p): ?>
                            <tr>
                                <td><span class="role-badge text-cyan"><?= esc($p['pay_period_label']) ?></span></td>
                                <td class="mono-cell text-emerald" style="font-size:1.05rem;">
                                    <strong>₹<?= number_format((float) $p['net_payout_inr'], 2) ?></strong>
                                </td>
                                <td class="mono-cell"><code><?= esc($p['payment_reference']) ?></code></td>
                                <td class="mono-cell muted-cell"><?= esc(substr((string) ($p['paid_at'] ?: $p['created_at']), 0, 10)) ?></td>
                                <td><?= esc($p['admin_remarks'] ?: 'Monthly Salary Credited') ?></td>
                                <td><span class="role-badge text-emerald">✓ CREDITED</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
