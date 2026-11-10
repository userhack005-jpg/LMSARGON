<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-finance/assets/lms_finance.css') ?>">
<div class="lfn-portal-wrapper">
    <section class="console-card">
        <div class="card-head">
            <div>
                <span class="role-badge text-emerald">👩‍🏫 INSTRUCTOR PAYROLL</span>
                <h3 style="margin:0.3rem 0 0;">My Credited Salary Slips (<?= count($myPayouts ?? []) ?>)</h3>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Title</th>
                        <th>Period</th>
                        <th>Classes Taught</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myPayouts)): ?>
                        <tr><td colspan="6" class="muted-cell">No salary slips recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($myPayouts as $mp): ?>
                            <tr>
                                <td class="mono-cell"><code><?= esc($mp['reference_no']) ?></code></td>
                                <td class="identity-name"><?= esc($mp['title']) ?></td>
                                <td class="mono-cell"><?= esc($mp['month_year']) ?></td>
                                <td class="mono-cell"><?= (int) $mp['classes_taught'] ?> Classes</td>
                                <td class="mono-cell text-emerald"><strong><?= esc($mp['currency']) ?> <?= number_format((float) $mp['amount'], 2) ?></strong></td>
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
