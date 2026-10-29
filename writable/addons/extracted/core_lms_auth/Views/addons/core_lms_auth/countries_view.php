<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="cla-admin-wrapper">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <section class="console-card">
        <div class="card-head">
            <div>
                <h3>Servicing Countries &amp; Currencies</h3>
                <p class="field-hint" style="margin:0;">Enable the countries and currencies your academy serves.</p>
            </div>
        </div>

        <form action="<?= base_url('addons/core-lms-auth/save-countries') ?>" method="post">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Active</th>
                            <th>Country</th>
                            <th>Currency</th>
                            <th>Primary Timezone</th>
                            <th>Current Local Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($countries ?? []) as $cCode => $cInfo): ?>
                            <?php $isEnabled = ((int) ($cInfo['is_enabled'] ?? 0) === 1); ?>
                            <tr>
                                <td>
                                    <label class="cla-switch">
                                        <input type="checkbox" name="enabled_countries[]" value="<?= esc($cCode) ?>" <?= $isEnabled ? 'checked' : '' ?>>
                                        <span class="cla-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($cInfo['flag'] ?? '') ?> <?= esc($cInfo['name'] ?? $cCode) ?></div>
                                </td>
                                <td>
                                    <input type="text" name="curr_<?= esc($cCode) ?>" value="<?= esc($cInfo['currency_code'] ?? 'USD') ?>" class="field-input cla-input-xs">
                                </td>
                                <td>
                                    <select name="tz_<?= esc($cCode) ?>" class="field-select cla-select-tz">
                                        <?php foreach (($cInfo['timezones'] ?? [$cInfo['default_tz']]) as $tzOpt): ?>
                                            <option value="<?= esc($tzOpt) ?>" <?= ($cInfo['default_tz'] ?? '') === $tzOpt ? 'selected' : '' ?>><?= esc($tzOpt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="identity-name text-cyan"><?= esc($cInfo['live_local_time'] ?? '') ?></div>
                                    <div class="mono-cell muted-cell"><?= esc($cInfo['utc_offset'] ?? '') ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="action-btn-group cla-footer-bar">
                <button type="submit" class="btn-primary">💾 Save Servicing Countries</button>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
