<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php $cfg = $abacusConfig ?? []; ?>
<div class="aps-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card" style="max-width: 720px; margin: 0 auto;">
        <div class="card-head">
            <div>
                <h3>Soroban Theme, Rods (5–17) &amp; Voice Settings</h3>
            </div>
        </div>

        <form action="<?= base_url('addons/abacus-pro-suite/save-settings') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Default Soroban Theme</span></label>
                    <select name="default_theme" class="field-select">
                        <option value="cyber_emerald" <?= ($cfg['default_theme'] ?? '') === 'cyber_emerald' ? 'selected' : '' ?>>✨ Cyber Emerald</option>
                        <option value="classic_wood" <?= ($cfg['default_theme'] ?? '') === 'classic_wood' ? 'selected' : '' ?>>🪵 Classic Rosewood</option>
                        <option value="royal_gold" <?= ($cfg['default_theme'] ?? '') === 'royal_gold' ? 'selected' : '' ?>>👑 Royal Gold</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Default Abacus Rods (5–17)</span></label>
                    <select name="default_rods" class="field-select">
                        <?php foreach ([5 => '5 Rods (Beginner)', 7 => '7 Rods', 9 => '9 Rods', 11 => '11 Rods', 13 => '13 Rods (Standard)', 15 => '15 Rods', 17 => '17 Rods'] as $rCnt => $rLbl): ?>
                            <option value="<?= $rCnt ?>" <?= (int) ($cfg['default_rods'] ?? 5) === $rCnt ? 'selected' : '' ?>><?= $rLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Default Voice Language</span></label>
                    <select name="default_lang" class="field-select">
                        <option value="hi-IN" <?= ($cfg['default_lang'] ?? 'hi-IN') === 'hi-IN' ? 'selected' : '' ?>>🇮🇳 हिन्दी वॉइस (Hindi)</option>
                        <option value="en-IN" <?= ($cfg['default_lang'] ?? '') === 'en-IN' ? 'selected' : '' ?>>🌐 English Voice</option>
                    </select>
                </div>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Default Anzan Digits</span></label>
                    <input type="number" name="default_digits" class="field-input mono-input" value="<?= (int) ($cfg['default_digits'] ?? 1) ?>" min="1" max="5">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Default Anzan Rows</span></label>
                    <input type="number" name="default_rows" class="field-input mono-input" value="<?= (int) ($cfg['default_rows'] ?? 5) ?>" min="2" max="25">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Default Flash Speed (Sec)</span></label>
                    <input type="number" step="0.1" name="default_speed_sec" class="field-input mono-input" value="<?= (float) ($cfg['default_speed_sec'] ?? 1.0) ?>" min="0.2" max="5.0">
                </div>
            </div>

            <div class="grid-2-col" style="margin: 0.75rem 0 1.25rem;">
                <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                    <input type="checkbox" name="enable_sound" value="1" <?= ! empty($cfg['enable_sound']) ? 'checked' : '' ?>>
                    <strong>🔊 Enable Soroban Bead Click Audio</strong>
                </label>
                <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                    <input type="checkbox" name="enable_voice" value="1" <?= ! empty($cfg['enable_voice']) ? 'checked' : '' ?>>
                    <strong>🎙 Enable Hindi &amp; English Voice Dictation</strong>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-full">💾 Save Abacus Configuration</button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
