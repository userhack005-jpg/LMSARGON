<?php
    $currSub = $activeSubTab ?? 'chat';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="lcm-compact-bar">
    <div>
        <h3 style="margin:0;">
            <?php if ($effectiveRole === 'teacher'): ?>
                👩‍🏫 Instructor Classroom Messenger &amp; Student Feedback
            <?php elseif (! empty($isParentReadOnly)): ?>
                👨‍👩‍👧‍👦 Family Classroom Chat Transcripts &amp; Teacher Feedback
            <?php elseif ($effectiveRole === 'student'): ?>
                🎓 My Classroom Teacher Chat &amp; Feedback
            <?php else: ?>
                🛡 Classroom Communication, Feedback &amp; DLP Moderation
            <?php endif; ?>
        </h3>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <?php if (! empty($canChat)): ?>
            <a href="<?= base_url('chat/portal') ?>" class="settings-tab <?= $currSub === 'chat' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>💬 1-to-1 Classroom Chat</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canFeedback)): ?>
            <a href="<?= base_url('chat/feedback') ?>" class="settings-tab <?= $currSub === 'feedback' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>⭐ Teacher Feedback Cards</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canModerate)): ?>
            <a href="<?= base_url('addons/lms-communication/moderation') ?>" class="settings-tab <?= $currSub === 'moderation' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🛡 Moderation Queue</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canFilters)): ?>
            <a href="<?= base_url('addons/lms-communication/dlp-filters') ?>" class="settings-tab <?= $currSub === 'filters' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🔒 Safety Filter Rules</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canAlerts)): ?>
            <a href="<?= base_url('addons/lms-communication/alerts') ?>" class="settings-tab <?= $currSub === 'alerts' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🔔 Email &amp; WhatsApp Logs</span>
            </a>
        <?php endif; ?>
    </div>
</div>
