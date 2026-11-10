<?php
    $currSub    = $activeSubTab ?? 'pipeline';
    $leadsCnt   = (int) ($totalLeadsCount ?? count($leads ?? []));
    $batchesCnt = (int) ($totalBatchesCount ?? count($demoBatches ?? []));
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/lms_onboarding/lms_onboarding.css?v=12.1') ?>">
<script src="<?= base_url('assets/addons/lms_onboarding/lms_onboarding.js?v=12.1') ?>" defer></script>

<?php if (session()->getFlashdata('success')): ?>
    <div class="onb-alert onb-alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="onb-alert onb-alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="onb-compact-bar">
    <div class="onb-stat-pills">
        <span class="onb-badge onb-badge-emerald">🎯 Demo Requests: <strong><?= $leadsCnt ?></strong></span>
        <span class="onb-badge onb-badge-cyan">📅 Created Demo Slots: <strong><?= $batchesCnt ?></strong></span>
    </div>
    <div class="onb-nav-tabs">
        <?php if (! empty($canViewLeads)): ?>
            <a href="<?= base_url('addons/lms-onboarding') ?>" class="onb-nav-tab <?= $currSub === 'pipeline' ? 'is-active' : '' ?>">
                🎯 Demo Bookings &amp; Convert to Batch
            </a>
        <?php endif; ?>
        <?php if (! empty($canManageDemos)): ?>
            <a href="<?= base_url('addons/lms-onboarding/scheduler') ?>" class="onb-nav-tab <?= $currSub === 'scheduler' ? 'is-active' : '' ?>">
                📅 Demo Class Slots
            </a>
        <?php endif; ?>
        <?php if (! empty($canTeacherDemo)): ?>
            <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="onb-nav-tab <?= $currSub === 'my_demos' ? 'is-active' : '' ?>">
                👩‍🏫 My Assigned Demo Classes
            </a>
        <?php endif; ?>
    </div>
</div>
