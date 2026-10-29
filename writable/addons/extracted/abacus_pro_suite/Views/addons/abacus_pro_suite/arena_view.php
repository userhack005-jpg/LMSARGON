<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $cfg          = $abacusConfig ?? [];
    $defaultRods  = (int) ($cfg['default_rods'] ?? 13);
    $defaultTheme = (string) ($cfg['default_theme'] ?? 'cyber_emerald');
    $soundOn      = (int) ($cfg['enable_sound'] ?? 1) === 1;
?>
<div class="aps-wrapper" id="js-aps-soroban-root"
     data-log-url="<?= base_url('abacus/log-attempt') ?>"
     data-rods="<?= $defaultRods ?>"
     data-theme="<?= esc($defaultTheme) ?>"
     data-sound="<?= $soundOn ? '1' : '0' ?>">

    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <!-- Top Interactive Control Deck & Digital Readout -->
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
            <select id="js-soroban-rods-select" class="field-select" style="width:130px;">
                <?php foreach ([7, 9, 11, 13, 15, 17] as $rCnt): ?>
                    <option value="<?= $rCnt ?>" <?= $defaultRods === $rCnt ? 'selected' : '' ?>><?= $rCnt ?> Rods</option>
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
                ↺ Reset Abacus (0)
            </button>
        </div>
    </div>

    <div id="js-soroban-challenge-toast" class="alert-banner alert-success is-hidden" style="margin-bottom:1rem;"></div>

    <!-- Main Interactive 3D Soroban Frame -->
    <section class="aps-soroban-stage theme-<?= esc($defaultTheme) ?>" id="js-soroban-frame-wrap">
        <div class="aps-soroban-outer-frame">
            <div class="aps-soroban-rods-container" id="js-soroban-rods-container"></div>
        </div>
        <div class="aps-soroban-helper-strip">
            <span>💡 <strong>How to Use:</strong> Click any <strong>Upper Bead (Value = 5)</strong> to move it down toward the reckoning bar, or click any <strong>Lower Bead (Value = 1)</strong> to slide it up.</span>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
