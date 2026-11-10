<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lhr-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card" style="margin-bottom: 1.25rem;">
        <div class="card-head lhr-flex-between">
            <div>
                <span class="lhr-badge lhr-badge-emerald">🎓 QUICK ENROLL STUDENT</span>
                <h3 style="margin:0.3rem 0 0;">Enroll Student in Course &amp; Auto-Sync Subscription</h3>
            </div>
        </div>
        <form action="<?= base_url('addons/lms-hierarchy/enroll-student') ?>" method="post">
            <?= csrf_field() ?>
            <div class="lhr-assign-grid">
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
                            <option value="<?= (int) $lid ?>">#<?= (int) $lid ?> — <?= esc($lTitle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

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

                <div class="field-group">
                    <button type="submit" class="lhr-btn lhr-btn-emerald lhr-btn-lg" style="width:100%;">🎓 Enroll Student</button>
                </div>
            </div>
        </form>
    </section>

    <section class="console-card">
        <div class="card-head lhr-flex-between">
            <div>
                <h3>Enrolled Students &amp; Level Promotion (<span id="js-lhr-enr-cnt"><?= count($enrollments ?? []) ?></span>)</h3>
            </div>
        </div>

        <div class="lhr-toolbar-strip">
            <div class="lhr-toolbar-left">
                <input type="search" id="js-lhr-enr-search" class="lhr-search-input" placeholder="🔍 Instant search student name, email, course or fee plan...">
            </div>
            <div class="lhr-toolbar-right">
                <label class="mono-cell muted-cell">Rows:</label>
                <select id="js-lhr-enr-pagesize" class="lhr-pagesize-select">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="9999">All</option>
                </select>
                <button type="button" id="js-lhr-enr-csv" class="lhr-btn lhr-btn-outline lhr-btn-sm">⬇ CSV</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="js-lhr-enr-table">
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
                        <tr><td colspan="5" class="muted-cell" style="text-align:center; padding:2rem;">No active student enrollments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $enr): ?>
                            <tr class="js-lhr-enr-row"
                                data-csv-student="<?= esc($enr['student_name']) ?>"
                                data-csv-email="<?= esc($enr['student_email']) ?>"
                                data-csv-course="<?= esc($enr['breadcrumb_path']) ?>"
                                data-csv-fee="<?= esc($enr['formatted_fee']) ?>">
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
                                    <form action="<?= base_url('addons/lms-hierarchy/upgrade-student') ?>" method="post" class="lhr-action-bar">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="enrollment_id" value="<?= (int) $enr['id'] ?>">
                                        <select name="target_leaf_node_id" class="field-select" style="width:auto; min-width:170px; height:32px; padding:0 0.55rem; font-size:0.78rem;">
                                            <?php foreach (($leafBatchesMap ?? []) as $lid => $lTitle): ?>
                                                <option value="<?= (int) $lid ?>" <?= (int) $enr['leaf_node_id'] === (int) $lid ? 'selected' : '' ?>><?= esc($lTitle) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="lhr-btn lhr-btn-cyan lhr-btn-sm">🚀 Promote</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="<?= base_url('addons/lms-hierarchy/delete-enrollment') ?>" method="post" onsubmit="return confirm('Remove this enrollment?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="enrollment_id" value="<?= (int) $enr['id'] ?>">
                                        <button type="submit" class="lhr-btn lhr-btn-rose lhr-btn-sm">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="lhr-pagination-bar" id="js-lhr-enr-pager"></div>
    </section>
</div>
<?= $this->endSection() ?>
