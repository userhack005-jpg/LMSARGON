<?php
    $currSub = $activeSubTab ?? 'directory';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="cla-compact-bar">
    <div class="cla-stat-pills">
        <span class="role-badge">👨‍👩‍👧‍👦 Parents: <strong><?= count($familyDir['parents'] ?? []) ?></strong></span>
        <span class="role-badge text-emerald">🎓 Students: <strong><?= count($familyDir['students'] ?? []) ?></strong></span>
        <span class="role-badge text-cyan">🌍 Active Countries: <strong><?= count($enabledCountries ?? []) ?></strong></span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <a href="<?= base_url('addons/core-lms-auth/directory') ?>" class="settings-tab <?= $currSub === 'directory' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>📋 Family Directory</span>
        </a>
        <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
            <a href="<?= base_url('addons/core-lms-auth/linker') ?>" class="settings-tab <?= $currSub === 'linker' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🔗 Assign Child &amp; Course</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canEdit)): ?>
            <a href="<?= base_url('addons/core-lms-auth/role-mapper') ?>" class="settings-tab <?= $currSub === 'role_mapper' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🛡 Role Mapper</span>
            </a>
            <a href="<?= base_url('addons/core-lms-auth/countries') ?>" class="settings-tab <?= $currSub === 'countries' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🌍 Servicing Countries</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('portal/family') ?>" class="settings-tab <?= in_array($currSub, ['family', 'add_child', 'profile'], true) ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>👨‍👩‍👧‍👦 Family Hub</span>
        </a>
    </div>
</div>
