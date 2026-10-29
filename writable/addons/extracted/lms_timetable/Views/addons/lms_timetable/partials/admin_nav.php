<?php
    $currSub = $activeSubTab ?? 'cohorts';
    $pendingSubCnt = 0;
    foreach (($subRequests ?? []) as $sr) {
        if (($sr['status'] ?? '') === 'pending') $pendingSubCnt++;
    }
?>
<div id="js-ltt-live-toast" class="ltt-live-toast is-hidden"></div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="ltt-compact-bar">
    <div class="ltt-stat-pills">
        <span class="role-badge text-emerald">🏫 Live Batches: <strong><?= count($cohorts ?? []) ?></strong></span>
        <span class="role-badge text-cyan">📅 Scheduled Classes: <strong><?= count($sessions ?? []) ?></strong></span>
        <span class="role-badge">⚡ Waiting for Batch: <strong><?= count($unassignedQueue ?? []) ?></strong></span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
            <a href="<?= base_url('addons/lms-timetable/cohorts') ?>" class="settings-tab <?= $currSub === 'cohorts' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🏫 Live Batches &amp; Auto-Queue</span>
            </a>
            <a href="<?= base_url('addons/lms-timetable/sessions') ?>" class="settings-tab <?= $currSub === 'sessions' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📅 Master Schedule &amp; Makeup</span>
            </a>
            <a href="<?= base_url('addons/lms-timetable/substitutes') ?>" class="settings-tab <?= $currSub === 'substitutes' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>⇄ Substitute Inbox</span>
                <span class="ltt-unread-badge <?= $pendingSubCnt > 0 ? '' : 'is-hidden' ?>" id="js-nav-sub-badge"><?= $pendingSubCnt ?></span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('timetable/portal') ?>" class="settings-tab <?= $currSub === 'portal' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🎥 Role Class Portal</span>
        </a>
    </div>
</div>
