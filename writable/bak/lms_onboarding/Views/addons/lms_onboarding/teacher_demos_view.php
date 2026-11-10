<?= $this->extend('layouts/master_layout') ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.css') ?>">
<div class="onb-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>
    <section class="console-card">
        <h3>My Assigned Demo Classes (<?= count($leads ?? []) ?>)</h3>
        <div class="grid-2-col">
            <?php foreach (($leads ?? []) as $ld): ?>
                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                        <strong>🎓 <?= esc($ld['child_name']) ?> (Age <?= (int) $ld['child_age'] ?>)</strong>
                        <a href="<?= esc($ld['meeting_link']) ?>" target="_blank" class="btn-primary btn-sm" style="text-decoration:none;">🎥 Join Demo ↗</a>
                    </div>
                    <div class="mono-cell text-emerald" style="margin-bottom:0.75rem;"><?= esc($ld['course_title']) ?></div>
                    <form action="<?= base_url('addons/lms-onboarding/teacher-submit-eval') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="lead_id" value="<?= (int) $ld['id'] ?>">
                        <div class="field-group">
                            <select name="status" class="field-select">
                                <option value="demo_attended">✅ Demo Attended — Ready to Convert</option>
                                <option value="reschedule_needed">⏳ Reschedule Needed</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <input type="text" name="teacher_eval_note" class="field-input" value="<?= esc($ld['teacher_eval_note'] ?? '') ?>" placeholder="Teacher evaluation note..." required>
                        </div>
                        <button type="submit" class="btn-secondary btn-full">💾 Save Evaluation</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
