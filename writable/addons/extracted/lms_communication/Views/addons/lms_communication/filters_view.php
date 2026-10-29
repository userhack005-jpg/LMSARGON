<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="lcm-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <div class="lcm-feedback-layout">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Add DLP Safety Filter Rule</h3>
                    <p class="field-hint" style="margin:0;">Automatically shadow-block personal contact sharing or restricted keywords.</p>
                </div>
            </div>
            <form action="<?= base_url('addons/lms-communication/save-filter') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Rule Name *</span></label>
                    <input type="text" name="rule_name" class="field-input" placeholder="e.g. Social Media Handle Guard" required>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Match Type</span></label>
                        <select name="pattern_type" class="field-select">
                            <option value="keyword">Exact Keyword / Phrase</option>
                            <option value="regex">Regular Expression (Regex)</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Action Mode</span></label>
                        <select name="action_mode" class="field-select">
                            <option value="shadow_block">🔒 Shadow-Block (Hide from Recipient)</option>
                            <option value="flag_only">🚩 Flag for Admin Review Only</option>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Keyword or Regex Pattern *</span></label>
                    <input type="text" name="pattern_value" class="field-input mono-input" placeholder="e.g. instagram.com or @[a-z0-9_]+" required>
                </div>
                <button type="submit" class="btn-primary btn-full">+ Save Safety Rule</button>
            </form>
        </section>

        <section class="console-card">
            <div class="card-head">
                <div>
                    <h3>Active Safety Filter Rules (<?= count($dlpFilters ?? []) ?>)</h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rule Name</th>
                            <th>Type &amp; Pattern</th>
                            <th>Action</th>
                            <th>Status &amp; Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($dlpFilters ?? []) as $df): ?>
                            <?php $act = ((int) $df['is_active'] === 1); ?>
                            <tr>
                                <td class="identity-name"><?= esc($df['rule_name']) ?></td>
                                <td>
                                    <span class="role-badge"><?= strtoupper(esc($df['pattern_type'])) ?></span>
                                    <code class="mono-cell text-cyan"><?= esc($df['pattern_value']) ?></code>
                                </td>
                                <td><span class="role-badge text-emerald"><?= strtoupper(esc($df['action_mode'])) ?></span></td>
                                <td>
                                    <div class="action-btn-group">
                                        <form action="<?= base_url('addons/lms-communication/toggle-filter') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $df['id'] ?>">
                                            <button type="submit" class="btn-action <?= $act ? 'text-emerald' : 'muted-cell' ?>">
                                                <?= $act ? '● Active' : '○ Disabled' ?>
                                            </button>
                                        </form>
                                        <form action="<?= base_url('addons/lms-communication/delete-filter') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $df['id'] ?>">
                                            <button type="submit" class="btn-action text-danger">✕</button>
                                        </form>
                                    </div>
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
