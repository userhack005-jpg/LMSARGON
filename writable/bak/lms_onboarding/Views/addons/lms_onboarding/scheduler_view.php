<?= $this->extend('layouts/master_layout') ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.css') ?>">
<div class="onb-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>
    <div class="grid-2-col">
        <section class="console-card">
            <h3>Create Demo Class Slot</h3>
            <form action="<?= base_url('addons/lms-onboarding/save-demo-batch') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Demo Slot Title *</span></label>
                    <input type="text" name="batch_title" class="field-input" placeholder="e.g. Evening Trial Slot" required>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Assign Teacher *</span></label>
                        <select name="teacher_user_id" class="field-select" required>
                            <?php foreach (($teachersMap ?? []) as $tid => $tInfo): ?>
                                <option value="<?= (int) $tid ?>">👩‍🏫 <?= esc($tInfo['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Demo Date *</span></label>
                        <input type="date" name="demo_date" class="field-input" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Time (IST) *</span></label>
                        <input type="time" name="start_time_ist" class="field-input" value="17:00" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Meeting Link</span></label>
                        <input type="url" name="meeting_link" class="field-input mono-input" value="https://meet.google.com/new">
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-full">📅 Save Demo Slot</button>
            </form>
        </section>
        <section class="console-card">
            <h3>Scheduled Demo Slots (<?= count($demoBatches ?? []) ?>)</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Teacher</th><th>Date &amp; Time</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach (($demoBatches ?? []) as $dbRow): ?>
                            <tr>
                                <td class="identity-name"><?= esc($dbRow['batch_title']) ?></td>
                                <td>👩‍🏫 <?= esc($dbRow['teacher_name']) ?></td>
                                <td class="mono-cell"><?= esc($dbRow['demo_date']) ?> @ <?= esc($dbRow['start_time_ist']) ?></td>
                                <td>
                                    <form action="<?= base_url('addons/lms-onboarding/delete-demo-batch') ?>" method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $dbRow['id'] ?>">
                                        <button type="submit" class="btn-action text-danger">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
