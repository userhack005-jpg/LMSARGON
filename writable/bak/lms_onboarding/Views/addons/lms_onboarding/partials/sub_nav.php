<?php
    $currSub = $activeSubTab ?? 'pipeline';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="onb-compact-bar">
    <div class="onb-stat-pills">
        <span class="role-badge text-emerald">🎯 Total Demo Requests: <strong><?= count($leads ?? []) ?></strong></span>
    </div>
    <div class="settings-tabs" style="margin:0;">
        <?php if (! empty($canViewLeads)): ?>
            <a href="<?= base_url('addons/lms-onboarding') ?>" class="settings-tab <?= $currSub === 'pipeline' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🎯 Demo Bookings &amp; Convert to Batch</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canManageDemos)): ?>
            <a href="<?= base_url('addons/lms-onboarding/scheduler') ?>" class="settings-tab <?= $currSub === 'scheduler' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📅 Demo Class Slots</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canTeacherDemo)): ?>
            <a href="<?= base_url('addons/lms-onboarding/my-demos') ?>" class="settings-tab <?= $currSub === 'my_demos' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>👩‍🏫 Teacher Demo Console</span>
            </a>
        <?php endif; ?>
    </div>
</div>
