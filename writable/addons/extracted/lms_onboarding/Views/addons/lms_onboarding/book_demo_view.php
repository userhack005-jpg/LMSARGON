<?php $bookLayout = ! empty($isLoggedIn) ? 'layouts/master_layout' : 'layouts/frontend_layout'; ?>
<?= $this->extend($bookLayout) ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('assets/addons/lms_onboarding/lms_onboarding.css?v=12.1') ?>">
<script src="<?= base_url('assets/addons/lms_onboarding/lms_onboarding.js?v=12.1') ?>" defer></script>
<?php
    $selCid    = (int) ($selectedCourseId ?? ($_GET['course_id'] ?? 0));
    $cMap      = is_array($coursesMap ?? null) ? $coursesMap : [];
    $selCourse = ($selCid > 0 && isset($cMap[$selCid])) ? $cMap[$selCid] : null;
?>
<div class="onb-standalone-wrap">
    <div class="onb-top-back-bar" style="margin-bottom: 1.15rem;">
        <a href="javascript:history.back()" class="onb-btn onb-btn-outline onb-btn-sm">← Back to Course</a>
        <?php if ($selCid > 0): ?>
            <a href="<?= base_url('finance/enroll-checkout/' . $selCid) ?>" class="onb-btn onb-btn-emerald onb-btn-sm">
                🎓 Direct Admission in <?= esc($selCourse['title'] ?? 'Course') ?> →
            </a>
        <?php endif; ?>
    </div>

    <?php
        $data = [
            'badge'              => $selCourse ? ($selCourse['breadcrumb_path'] ?? $selCourse['title']) : 'FREE DEMO BOOKING',
            'heading'            => $selCourse ? ('Book Free Demo — ' . $selCourse['title']) : 'Book a Free Demo Class',
            'subheading'         => 'Each child can book 1 free demo per course level. You can also add another child or book a demo for a different course level.',
            'selected_course_id' => $selCid,
        ];
        include __DIR__ . '/block_view.php';
    ?>
</div>
<?= $this->endSection() ?>
