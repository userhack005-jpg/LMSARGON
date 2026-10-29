<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lhr-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <div class="lhr-main-grid">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Enroll Student in Course</h3>
                </div>
            </div>
            <form action="<?= base_url('addons/lms-hierarchy/enroll-student') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Select Student *</span></label>
                    <select name="student_user_id" class="field-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach (($studentsMap ?? []) as $sid => $sInfo): ?>
                            <option value="<?= (int) $sid ?>"><?= esc($sInfo['full_name']) ?> (<?= esc($sInfo['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Select Course *</span></label>
                    <select name="leaf_node_id" class="field-select" required>
                        <?php foreach (($leafBatchesMap ?? []) as $lid => $lTitle): ?>
                            <option value="<?= (int) $lid ?>"><?= esc($lTitle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Billing Country *</span></label>
                        <select name="country_code" class="field-select">
                            <?php foreach (($countries ?? []) as $cCode => $cInfo): ?>
                                <option value="<?= esc($cCode) ?>"><?= esc($cInfo['flag'] ?? '') ?> <?= esc($cInfo['name'] ?? $cCode) ?> (<?= esc($cInfo['currency_code'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Billing Plan</span></label>
                        <select name="billing_cycle" class="field-select">
                            <option value="monthly">Monthly Plan</option>
                            <option value="quarterly">Quarterly Plan</option>
                            <option value="full">Full Course Plan</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">🎓 Enroll Student</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Enrolled Students &amp; Level Promotion</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Enrolled Course</th>
                            <th>Fee Plan</th>
                            <th>Promote to Next Level</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrollments)): ?>
                            <tr><td colspan="5" class="muted-cell">No active student enrollments found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($enrollments as $enr): ?>
                                <tr>
                                    <td>
                                        <div class="identity-name"><?= esc($enr['student_name']) ?></div>
                                        <div class="mono-cell muted-cell"><?= esc($enr['student_email']) ?></div>
                                    </td>
                                    <td>
                                        <div class="identity-name text-emerald"><?= esc($enr['batch_title']) ?></div>
                                        <div class="mono-cell muted-cell"><?= esc($enr['breadcrumb_path']) ?></div>
                                    </td>
                                    <td class="mono-cell">
                                        <strong><?= esc($enr['formatted_fee']) ?></strong>
                                    </td>
                                    <td>
                                        <form action="<?= base_url('addons/lms-hierarchy/upgrade-student') ?>" method="post" style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="enrollment_id" value="<?= (int) $enr['id'] ?>">
                                            <select name="target_leaf_node_id" class="field-select field-select-sm">
                                                <?php foreach (($leafBatchesMap ?? []) as $lid => $lTitle): ?>
                                                    <option value="<?= (int) $lid ?>" <?= (int) $enr['leaf_node_id'] === (int) $lid ? 'selected' : '' ?>><?= esc($lTitle) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-secondary btn-sm">🚀 Promote</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form action="<?= base_url('addons/lms-hierarchy/delete-enrollment') ?>" method="post" onsubmit="return confirm('Remove this enrollment?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="enrollment_id" value="<?= (int) $enr['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
