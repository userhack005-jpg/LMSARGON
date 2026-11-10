<?php
    $currSub     = $activeSubTab ?? 'tree';
    $totalNodes  = count($nodesById ?? []);
    $totalLeaves = count($leafBatchesMap ?? []);
    $totalEnr    = (int) ($totalEnrCount ?? count($enrollments ?? []));
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/lms_hierarchy/lms_hierarchy.css?v=8.5') ?>">
<script src="<?= base_url('assets/addons/lms_hierarchy/lms_hierarchy.js?v=8.5') ?>" defer></script>

<?php if (session()->getFlashdata('success')): ?>
    <div class="lhr-alert lhr-alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="lhr-alert lhr-alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="lhr-compact-bar">
    <div class="lhr-stat-pills">
        <span class="lhr-badge lhr-badge-info">📚 Total Levels: <strong><?= $totalNodes ?></strong></span>
        <span class="lhr-badge lhr-badge-emerald">🎓 Enrollable Courses: <strong><?= $totalLeaves ?></strong></span>
        <span class="lhr-badge lhr-badge-cyan">👥 Active Enrollments: <strong><?= $totalEnr ?></strong></span>
    </div>

    <div class="lhr-nav-tabs">
        <a href="<?= base_url('addons/lms-hierarchy') ?>" class="lhr-nav-tab <?= $currSub === 'tree' ? 'is-active' : '' ?>">
            📚 All Courses &amp; Levels
        </a>
        <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
            <a href="<?= base_url('addons/lms-hierarchy/builder') ?>" class="lhr-nav-tab <?= $currSub === 'builder' ? 'is-active' : '' ?>">
                ➕ Add / Edit Course
            </a>
        <?php endif; ?>
        <?php if (! empty($canPricing)): ?>
            <a href="<?= base_url('addons/lms-hierarchy/pricing') ?>" class="lhr-nav-tab <?= $currSub === 'pricing' ? 'is-active' : '' ?>">
                💳 Course Fee Plans
            </a>
        <?php endif; ?>
        <?php if (! empty($canEdit)): ?>
            <a href="<?= base_url('addons/lms-hierarchy/enrollments') ?>" class="lhr-nav-tab <?= $currSub === 'enrollments' ? 'is-active' : '' ?>">
                🎓 Student Enrollments
            </a>
        <?php endif; ?>
        <a href="<?= base_url('courses/catalog') ?>" class="lhr-nav-tab <?= $currSub === 'catalog' ? 'is-active' : '' ?>">
            📖 My Courses View
        </a>
    </div>
</div>
