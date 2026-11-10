<?= $this->extend('layouts/frontend_layout') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.css') ?>">
<script src="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.js') ?>" defer></script>
<?php
    $selCid    = (int) ($selectedCourseId ?? ($_GET['course_id'] ?? 0));
    $cMap      = is_array($coursesMap ?? null) ? $coursesMap : [];
    $selCourse = ($selCid > 0 && isset($cMap[$selCid])) ? $cMap[$selCid] : null;
?>
<div class="onb-standalone-wrap">
    <div class="onb-top-back-bar">
        <a href="javascript:history.back()" class="btn-secondary btn-sm" style="text-decoration:none;">← Back to Course</a>
        <?php if ($selCid > 0): ?>
            <a href="<?= base_url('finance/enroll-checkout/' . $selCid) ?>" class="btn-primary btn-sm" style="text-decoration:none;">
                🎓 Direct Admission in <?= esc($selCourse['title'] ?? 'Course') ?> →
            </a>
        <?php endif; ?>
    </div>

    <?php
        $data = [
            'badge'              => $selCourse ? ($selCourse['breadcrumb_path'] ?? $selCourse['title']) : 'FREE DEMO BOOKING',
            'heading'            => $selCourse ? ('Book Free Demo — ' . $selCourse['title']) : 'Book a Free Demo Class',
            'subheading'         => 'Select an existing child from your account or add one or more children below.',
            'selected_course_id' => $selCid,
        ];
        include __DIR__ . '/block_view.php';
    ?>
</div>
<?= $this->endSection() ?>
