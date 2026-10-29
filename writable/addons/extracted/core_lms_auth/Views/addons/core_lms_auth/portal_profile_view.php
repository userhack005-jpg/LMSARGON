<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $currUser = $portalCtx['current_user'] ?? ($user ?? []);
    $uMeta    = $currUser['meta'] ?? [];
    $currTz   = $portalCtx['active_timezone'] ?? ($uMeta['timezone'] ?? 'Asia/Kolkata');
    $currCtry = $uMeta['country_code'] ?? 'IN';
?>
<div class="cla-portal-wrapper">
    <?php include __DIR__ . '/partials/portal_nav.php'; ?>

    <section class="console-card" style="max-width: 680px; margin: 0 auto;">
        <div class="card-head">
            <div>
                <h3>My Profile &amp; Security Settings</h3>
                <p class="field-hint" style="margin:0;">Update your name, email address, local timezone, or change your password.</p>
            </div>
        </div>

        <form action="<?= base_url('portal/family/update-profile') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Full Name *</span></label>
                    <input type="text" name="full_name" class="field-input" value="<?= esc($currUser['full_name'] ?? ($currUser['name'] ?? '')) ?>" required>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Email Address *</span></label>
                    <input type="email" name="email" class="field-input" value="<?= esc($currUser['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Country</span></label>
                    <select name="country_code" id="cla_country" class="field-select">
                        <?php foreach (($enabledCountries ?? []) as $cCode => $cInfo): ?>
                            <option value="<?= esc($cCode) ?>"
                                    data-tz="<?= esc($cInfo['default_tz'] ?? 'Asia/Kolkata') ?>"
                                    <?= $currCtry === $cCode ? 'selected' : '' ?>>
                                <?= esc($cInfo['flag'] ?? '') ?> <?= esc($cInfo['name'] ?? $cCode) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Preferred Local Timezone</span></label>
                    <select name="timezone" id="cla_timezone" class="field-select">
                        <?php foreach (($enabledCountries ?? []) as $cCode => $cInfo): ?>
                            <?php foreach (($cInfo['timezones'] ?? [$cInfo['default_tz']]) as $tzOpt): ?>
                                <option value="<?= esc($tzOpt) ?>" <?= $currTz === $tzOpt ? 'selected' : '' ?>>
                                    <?= esc($cInfo['flag'] ?? '') ?> <?= esc($cInfo['name'] ?? $cCode) ?> — <?= esc($tzOpt) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>New Password (Leave blank to keep current password)</span></label>
                <input type="password" name="new_password" class="field-input" placeholder="Enter new password (min 6 characters)" autocomplete="new-password">
            </div>

            <div class="action-btn-group cla-footer-bar">
                <button type="submit" class="btn-primary">💾 Save Profile Changes</button>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
