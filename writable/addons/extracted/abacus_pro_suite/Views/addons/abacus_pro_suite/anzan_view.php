<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $cfg      = $abacusConfig ?? [];
    $defDig   = (int) ($cfg['default_digits'] ?? 1);
    $defRows  = (int) ($cfg['default_rows'] ?? 5);
    $defSpd   = (float) ($cfg['default_speed_sec'] ?? 1.0);
    $voiceOn  = (int) ($cfg['enable_voice'] ?? 1) === 1;
?>
<div class="aps-wrapper" id="js-aps-anzan-root" data-log-url="<?= base_url('abacus/log-attempt') ?>">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <div class="aps-anzan-layout">
        <!-- Left: Speed & Dictation Configurator -->
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">SPEED MENTAL MATH CONFIG</span>
                    <h3 style="margin-top:0.3rem;">Flash Anzan &amp; Voice Dictation</h3>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label"><span>Drill Mode</span></label>
                <select id="js-anz-mode" class="field-select">
                    <option value="flash_anzan">⚡ Visual Flash Anzan (Screen Flash)</option>
                    <option value="voice_dictation" <?= $voiceOn ? '' : '' ?>>🎙 Audio Voice Dictation + Flash</option>
                </select>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Number of Digits</span></label>
                    <select id="js-anz-digits" class="field-select">
                        <?php foreach ([1 => '1 Digit (1-9)', 2 => '2 Digits (10-99)', 3 => '3 Digits (100-999)', 4 => '4 Digits'] as $dVal => $dLbl): ?>
                            <option value="<?= $dVal ?>" <?= $defDig === $dVal ? 'selected' : '' ?>><?= $dLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Total Rows (Numbers)</span></label>
                    <select id="js-anz-rows" class="field-select">
                        <?php foreach ([3, 5, 8, 10, 12, 15, 20] as $rCnt): ?>
                            <option value="<?= $rCnt ?>" <?= $defRows === $rCnt ? 'selected' : '' ?>><?= $rCnt ?> Rows</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Flash Speed (Seconds)</span></label>
                    <select id="js-anz-speed" class="field-select">
                        <?php foreach (['2.0' => '2.0s (Beginner)', '1.5' => '1.5s (Easy)', '1.0' => '1.0s (Standard)', '0.7' => '0.7s (Fast)', '0.5' => '0.5s (Pro)', '0.3' => '0.3s (Master)'] as $sVal => $sLbl): ?>
                            <option value="<?= $sVal ?>" <?= abs($defSpd - (float) $sVal) < 0.05 ? 'selected' : '' ?>><?= $sLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Operations</span></label>
                    <select id="js-anz-ops" class="field-select">
                        <option value="add_sub">➕➖ Addition &amp; Subtraction</option>
                        <option value="add_only">➕ Addition Only</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn-primary btn-full" id="js-anz-start-btn" style="justify-content:center; padding:0.85rem;">
                ▶ Launch Speed Drill Now
            </button>
        </section>

        <!-- Right: Giant Neon Flash Screen & Answer Input Stage -->
        <section class="aps-anzan-screen-card">
            <div class="aps-anzan-hud">
                <span class="role-badge" id="js-anz-progress-pill">READY TO START</span>
                <span class="mono-cell text-cyan" id="js-anz-timer-pill">⏱ 0.0s</span>
            </div>

            <div class="aps-anzan-display-box" id="js-anz-display-box">
                <div class="aps-anzan-big-num" id="js-anz-big-num">READY</div>
                <p class="muted-cell" id="js-anz-sub-hint">Click "▶ Launch Speed Drill Now" on the left to begin.</p>
            </div>

            <!-- Answer Submission Bar (Appears automatically after the last number flashes!) -->
            <form id="js-anz-answer-form" class="aps-anzan-answer-bar is-hidden">
                <input type="number" id="js-anz-user-ans" class="field-input mono-input" placeholder="Enter Final Sum..." autocomplete="off" required>
                <button type="submit" class="btn-primary">✓ Check Answer</button>
                <button type="button" class="btn-secondary" id="js-anz-replay-btn">↻ Next Set</button>
            </form>

            <div id="js-anz-result-banner" class="alert-banner is-hidden" style="margin-top:1rem;"></div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
