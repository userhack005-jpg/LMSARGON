<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $selNodeId  = (int) ($selectedNodeId ?? 0);
    $nodePrices = $pricingByNode[$selNodeId] ?? [];
?>
<div class="lhr-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card">
        <div class="card-head lhr-flex-between">
            <div>
                <h3>Country-Wise Course Fee Plans</h3>
                <p class="field-hint" style="margin:0;">Visitors automatically see only their own country's fee on the course page.</p>
            </div>
            <form method="get" action="<?= base_url('addons/lms-hierarchy/pricing') ?>" style="display:flex; gap:0.5rem; align-items:center;">
                <select name="node_id" class="field-select" onchange="this.form.submit()">
                    <?php foreach (($leafBatchesMap ?? []) as $lid => $lTitle): ?>
                        <option value="<?= (int) $lid ?>" <?= $selNodeId === (int) $lid ? 'selected' : '' ?>><?= esc($lTitle) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($selNodeId <= 0): ?>
            <p class="muted-cell">No enrollable courses found. Mark at least one course as "Enrollable Course" first.</p>
        <?php else: ?>
            <form action="<?= base_url('addons/lms-hierarchy/save-pricing') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="node_id" value="<?= $selNodeId ?>">

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Active</th>
                                <th>Country</th>
                                <th>Currency</th>
                                <th>Monthly Fee</th>
                                <th>Quarterly Fee</th>
                                <th>Full Course Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($countries ?? []) as $cCode => $cInfo): ?>
                                <?php
                                    $pRow  = $nodePrices[$cCode] ?? [];
                                    $isAct = isset($pRow['is_active']) ? ((int) $pRow['is_active'] === 1) : true;
                                    $curr  = $pRow['currency_code'] ?? ($cInfo['currency_code'] ?? 'USD');
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="active_<?= esc($cCode) ?>" value="1" <?= $isAct ? 'checked' : '' ?>></td>
                                    <td class="identity-name"><?= esc($cInfo['flag'] ?? '🌍') ?> <?= esc($cInfo['name'] ?? $cCode) ?></td>
                                    <td><input type="text" name="curr_<?= esc($cCode) ?>" class="field-input mono-input" value="<?= esc($curr) ?>" style="width:90px;"></td>
                                    <td><input type="number" step="0.01" name="monthly_<?= esc($cCode) ?>" class="field-input mono-input" value="<?= esc((string) ($pRow['monthly_fee'] ?? '0.00')) ?>"></td>
                                    <td><input type="number" step="0.01" name="quarterly_<?= esc($cCode) ?>" class="field-input mono-input" value="<?= esc((string) ($pRow['quarterly_fee'] ?? '0.00')) ?>"></td>
                                    <td><input type="number" step="0.01" name="full_<?= esc($cCode) ?>" class="field-input mono-input" value="<?= esc((string) ($pRow['full_course_fee'] ?? '0.00')) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="action-btn-group" style="margin-top: 1.25rem;">
                    <button type="submit" class="btn-primary">💾 Save Course Fee Plans</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
<?= $this->endSection() ?>
