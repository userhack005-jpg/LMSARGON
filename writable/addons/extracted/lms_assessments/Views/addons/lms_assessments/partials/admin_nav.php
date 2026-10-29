<?php
    $currSub = $activeSubTab ?? 'qbank';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="las-compact-bar">
    <div class="las-stat-pills">
        <span class="role-badge text-emerald">🗃 Question Bank: <strong><?= count($questionBank ?? []) ?></strong></span>
        <span class="role-badge text-cyan">📝 Active Exams: <strong><?= count($exams ?? []) ?></strong></span>
        <span class="role-badge">📄 PDF Worksheets: <strong><?= count($worksheets ?? []) ?></strong></span>
        <span class="role-badge text-emerald">🏆 Graded Attempts: <strong><?= count($attempts ?? []) ?></strong></span>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
            <a href="<?= base_url('addons/lms-assessments/question-bank') ?>" class="settings-tab <?= $currSub === 'qbank' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🗃 Question Bank</span>
            </a>
            <a href="<?= base_url('addons/lms-assessments/exams') ?>" class="settings-tab <?= $currSub === 'exams' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📝 Exam Builder</span>
            </a>
            <a href="<?= base_url('addons/lms-assessments/worksheets') ?>" class="settings-tab <?= $currSub === 'worksheets' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📤 Upload Worksheets</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canView)): ?>
            <a href="<?= base_url('addons/lms-assessments/analytics') ?>" class="settings-tab <?= $currSub === 'analytics' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📊 Student Scores &amp; Certs</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('assessments/portal') ?>" class="settings-tab <?= in_array($currSub, ['portal_exams', 'portal_ws', 'portal_hist'], true) ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🎓 Student View</span>
        </a>
    </div>
</div>
