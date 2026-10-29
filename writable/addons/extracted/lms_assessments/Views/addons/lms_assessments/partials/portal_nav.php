<?php
    $currSub = $activeSubTab ?? 'portal_exams';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="las-compact-bar">
    <div>
        <h3 style="margin:0;">
            <?= ! empty($isParentOverview) ? '👨‍👩‍👧‍👦 Family Worksheets, Exam Scores & Certificates' : '🎓 My Practice Drills, Worksheets & Certificates' ?>
        </h3>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <?php if (empty($isParentOverview)): ?>
            <a href="<?= base_url('assessments/portal') ?>" class="settings-tab <?= $currSub === 'portal_exams' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🎯 Practice &amp; Timed Exams (<?= count($studentExams ?? []) ?>)</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('assessments/portal/worksheets') ?>" class="settings-tab <?= $currSub === 'portal_ws' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>📄 PDF Worksheets (<?= count($studentWorksheets ?? []) ?>)</span>
        </a>
        <a href="<?= base_url('assessments/portal/history') ?>" class="settings-tab <?= $currSub === 'portal_hist' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🏆 Scores &amp; Certificates (<?= count($myAttempts ?? []) ?>)</span>
        </a>
    </div>
</div>
