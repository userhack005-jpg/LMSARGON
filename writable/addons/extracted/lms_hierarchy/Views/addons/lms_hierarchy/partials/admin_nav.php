<?php
    $currSub     = $activeSubTab ?? 'tree';
    $totalNodes  = count($nodesById ?? []);
    $totalLeaves = count($leafBatchesMap ?? []);
    $totalEnr    = count($enrollments ?? []);
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="lhr-compact-bar">
    <div class="lhr-stat-pills">
        <span class="role-badge">📚 Total Levels: <strong><?= $totalNodes ?></strong></span>
        <span class="role-badge text-emerald">🎓 Enrollable Courses: <strong><?= $totalLeaves ?></strong></span>
        <span class="role-badge text-cyan">👥 Active Enrollments: <strong><?= $totalEnr ?></strong></span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <a href="<?= base_url('addons/lms-hierarchy') ?>" class="settings-tab <?= $currSub === 'tree' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>📚 All Courses &amp; Levels</span>
        </a>
        <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
            <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="settings-tab <?= $currSub === 'builder' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>➕ Add / Edit Course</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canPricing)): ?>
            <a href="<?= base_url('addons/lms-hierarchy/pricing') ?>" class="settings-tab <?= $currSub === 'pricing' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>💳 Course Fee Plans</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canEdit)): ?>
            <a href="<?= base_url('addons/lms-hierarchy/enrollments') ?>" class="settings-tab <?= $currSub === 'enrollments' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🎓 Student Enrollments</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('courses/catalog') ?>" class="settings-tab <?= $currSub === 'catalog' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>📖 My Courses View</span>
        </a>
    </div>
</div>
