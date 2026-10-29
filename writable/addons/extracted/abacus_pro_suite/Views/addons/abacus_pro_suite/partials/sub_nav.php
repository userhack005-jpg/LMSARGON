<?php
    $currSub = $activeSubTab ?? 'arena';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="aps-compact-bar">
    <div>
        <h3 style="margin:0;">
            <?php if ($effectiveRole === 'student'): ?>
                🧮 My 3D Soroban &amp; Mental Math Speed Arena
            <?php elseif (! empty($isParentOverview)): ?>
                👨‍👩‍👧‍👦 Family Abacus Lab &amp; Children's Speed Progress
            <?php else: ?>
                🧮 3D Soroban Lab, Flash Anzan &amp; Batch Analytics
            <?php endif; ?>
        </h3>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <a href="<?= base_url('abacus/arena') ?>" class="settings-tab <?= $currSub === 'arena' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🧮 3D Soroban Lab</span>
        </a>
        <a href="<?= base_url('abacus/anzan') ?>" class="settings-tab <?= $currSub === 'anzan' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>⚡ Flash Anzan &amp; Voice Drill</span>
        </a>
        <?php if (in_array($effectiveRole, ['student', 'parent'], true)): ?>
            <a href="<?= base_url('abacus/my-progress') ?>" class="settings-tab <?= $currSub === 'progress' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🏆 My Speed &amp; Badges</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canViewAnalytics)): ?>
            <a href="<?= base_url('addons/abacus-pro-suite/analytics') ?>" class="settings-tab <?= $currSub === 'analytics' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📊 Batch Analytics</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canEditSettings)): ?>
            <a href="<?= base_url('addons/abacus-pro-suite/settings') ?>" class="settings-tab <?= $currSub === 'settings' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>⚙ Theme &amp; Settings</span>
            </a>
        <?php endif; ?>
    </div>
</div>
