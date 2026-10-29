<?php
    $layoutToExtend = ! empty($isLoggedIn) ? 'layouts/master_layout' : 'layouts/frontend_layout';
?>
<?= $this->extend($layoutToExtend) ?>

<?= $this->section('content') ?>
<?php
    $cfg          = $abacusConfig ?? [];
    $defaultRods  = (int) ($cfg['default_rods'] ?? 5);
    $defaultTheme = (string) ($cfg['default_theme'] ?? 'cyber_emerald');
    $soundOn      = (int) ($cfg['enable_sound'] ?? 1) === 1;
?>
<div class="aps-wrapper" id="js-aps-soroban-root"
     data-log-url="<?= base_url('abacus/log-attempt') ?>"
     data-rods="<?= $defaultRods ?>"
     data-theme="<?= esc($defaultTheme) ?>"
     data-sound="<?= $soundOn ? '1' : '0' ?>">

    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <div class="aps-soroban-toolbar">
        <div class="aps-readout-group">
            <div>
                <span class="mono-label">CURRENT ABACUS VALUE</span>
                <div class="aps-digital-readout" id="js-soroban-current-val">0</div>
            </div>

            <div class="aps-target-box" id="js-soroban-target-box">
                <span class="mono-label text-cyan">🎯 TARGET CHALLENGE</span>
                <div class="aps-target-val" id="js-soroban-target-val">—</div>
            </div>
        </div>

        <div class="aps-controls-group">
            <select id="js-soroban-rods-select" class="field-select" style="width:155px;">
                <?php foreach ([5 => '5 Rods (Beginner)', 7 => '7 Rods (Jr Level)', 9 => '9 Rods (Intermediate)', 11 => '11 Rods', 13 => '13 Rods (Standard)', 15 => '15 Rods', 17 => '17 Rods (Master)'] as $rCnt => $rLbl): ?>
                    <option value="<?= $rCnt ?>" <?= $defaultRods === $rCnt ? 'selected' : '' ?>><?= $rLbl ?></option>
                <?php endforeach; ?>
            </select>

            <select id="js-soroban-theme-select" class="field-select" style="width:165px;">
                <option value="cyber_emerald" <?= $defaultTheme === 'cyber_emerald' ? 'selected' : '' ?>>✨ Cyber Emerald</option>
                <option value="classic_wood" <?= $defaultTheme === 'classic_wood' ? 'selected' : '' ?>>🪵 Classic Rosewood</option>
                <option value="royal_gold" <?= $defaultTheme === 'royal_gold' ? 'selected' : '' ?>>👑 Royal Gold</option>
            </select>

            <button type="button" class="btn-secondary btn-sm" id="js-soroban-sound-btn">
                <?= $soundOn ? '🔊 Sound: ON' : '🔇 Sound: OFF' ?>
            </button>

            <button type="button" class="btn-primary btn-sm" id="js-soroban-new-challenge">
                🎯 New Target Challenge
            </button>

            <button type="button" class="btn-secondary btn-sm" id="js-soroban-clear-btn">
                ↺ Reset (0)
            </button>
        </div>
    </div>

    <div id="js-soroban-challenge-toast" class="alert-banner alert-success is-hidden" style="margin-bottom:1rem;"></div>

    <section class="aps-soroban-stage theme-<?= esc($defaultTheme) ?>" id="js-soroban-frame-wrap">
        <div class="aps-soroban-outer-frame">
            <div class="aps-soroban-rods-container" id="js-soroban-rods-container"></div>
        </div>
        <div class="aps-soroban-helper-strip">
            <span>💡 <strong>Tip:</strong> Click any <strong>Upper Bead (Value = 5)</strong> to toggle it down, or click any <strong>Lower Bead (Value = 1–4)</strong> to slide it up. Starts from <strong>5 Rods</strong> for beginner learners up to <strong>17 Rods</strong>.</span>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
