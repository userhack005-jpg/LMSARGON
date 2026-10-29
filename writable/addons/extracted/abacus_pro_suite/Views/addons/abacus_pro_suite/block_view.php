<?php
    $bd         = $data ?? [];
    $compType   = $blockType ?? ($bd['component_key'] ?? 'abacus_soroban_lab');
    $widgetMode = $bd['widget_mode'] ?? ($compType === 'abacus_flash_anzan' ? 'anzan_only' : 'soroban_only');
    $defRods    = (int) ($bd['default_rods'] ?? 5);
    if (! in_array($defRods, [5, 7, 9, 11, 13, 15, 17], true)) {
        $defRods = 5;
    }
    $defTheme   = $bd['theme'] ?? 'cyber_emerald';
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/abacus_pro_suite/abacus_pro_suite.css') ?>">
<script src="<?= base_url('assets/addons/abacus_pro_suite/abacus_pro_suite.js') ?>" defer></script>

<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> aps-frontend-block is-revealed" style="padding: 2.5rem 0;">
    <div class="cms-inner-container">
        <?php if (! empty($bd['heading']) || ! empty($bd['badge'])): ?>
            <div style="text-align:center; margin-bottom:1.5rem;">
                <?php if (! empty($bd['badge'])): ?>
                    <span class="role-badge text-emerald"><?= esc($bd['badge']) ?></span>
                <?php endif; ?>
                <?php if (! empty($bd['heading'])): ?>
                    <h2 style="margin:0.4rem 0;"><?= esc($bd['heading']) ?></h2>
                <?php endif; ?>
                <?php if (! empty($bd['subheading'])): ?>
                    <p class="muted-cell" style="max-width:640px; margin:0 auto;"><?= esc($bd['subheading']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($widgetMode === 'anzan_only'): ?>
            <div class="aps-wrapper" id="js-aps-anzan-root" data-log-url="<?= base_url('abacus/log-attempt') ?>">
                <div class="aps-anzan-layout">
                    <div class="console-card">
                        <div class="grid-2-col">
                            <div class="field-group">
                                <label class="field-label"><span>Mode</span></label>
                                <select id="js-anz-mode" class="field-select">
                                    <option value="voice_dictation">🎙 Voice + Flash</option>
                                    <option value="flash_anzan">⚡ Silent Flash</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Voice Language</span></label>
                                <select id="js-anz-lang" class="field-select">
                                    <option value="hi-IN">🇮🇳 हिन्दी वॉइस</option>
                                    <option value="en-IN">🌐 English Voice</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3-col">
                            <div class="field-group">
                                <label class="field-label"><span>Digits</span></label>
                                <select id="js-anz-digits" class="field-select">
                                    <option value="1">1 Digit</option>
                                    <option value="2">2 Digits</option>
                                    <option value="3">3 Digits</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Rows</span></label>
                                <select id="js-anz-rows" class="field-select">
                                    <option value="3">3 Rows</option>
                                    <option value="5" selected>5 Rows</option>
                                    <option value="8">8 Rows</option>
                                    <option value="10">10 Rows</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Speed</span></label>
                                <select id="js-anz-speed" class="field-select">
                                    <option value="1.5">1.5s</option>
                                    <option value="1.0" selected>1.0s</option>
                                    <option value="0.7">0.7s</option>
                                    <option value="0.5">0.5s</option>
                                    <option value="0.3">0.3s</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" id="js-anz-ops" value="add_sub">
                        <button type="button" class="btn-primary btn-full" id="js-anz-start-btn" style="justify-content:center;">▶ Start Speed Drill</button>
                    </div>

                    <div class="aps-anzan-screen-card">
                        <div class="aps-anzan-hud">
                            <span class="role-badge" id="js-anz-progress-pill">READY</span>
                            <span class="mono-cell text-cyan" id="js-anz-timer-pill">⏱ 0.0s</span>
                        </div>
                        <div class="aps-anzan-display-box" id="js-anz-display-box">
                            <div class="aps-anzan-big-num" id="js-anz-big-num">READY</div>
                            <p class="muted-cell" id="js-anz-sub-hint">Click Start to test your mental math speed!</p>
                        </div>
                        <form id="js-anz-answer-form" class="aps-anzan-answer-bar is-hidden">
                            <input type="number" id="js-anz-user-ans" class="field-input mono-input" placeholder="Final Sum..." required>
                            <button type="submit" class="btn-primary">✓ Check</button>
                            <button type="button" class="btn-secondary" id="js-anz-replay-btn">↻ Next</button>
                        </form>
                        <div id="js-anz-result-banner" class="alert-banner is-hidden" style="margin-top:0.85rem;"></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="aps-wrapper" id="js-aps-soroban-root"
                 data-log-url="<?= base_url('abacus/log-attempt') ?>"
                 data-rods="<?= $defRods ?>"
                 data-theme="<?= esc($defTheme) ?>"
                 data-sound="1">
                <div class="aps-soroban-toolbar">
                    <div class="aps-readout-group">
                        <div>
                            <span class="mono-label">ABACUS VALUE</span>
                            <div class="aps-digital-readout" id="js-soroban-current-val">0</div>
                        </div>
                        <div class="aps-target-box" id="js-soroban-target-box">
                            <span class="mono-label text-cyan">🎯 TARGET</span>
                            <div class="aps-target-val" id="js-soroban-target-val">—</div>
                        </div>
                    </div>
                    <div class="aps-controls-group">
                        <select id="js-soroban-rods-select" class="field-select" style="width:145px;">
                            <?php foreach ([5 => '5 Rods (Beginner)', 7 => '7 Rods', 9 => '9 Rods', 11 => '11 Rods', 13 => '13 Rods', 15 => '15 Rods', 17 => '17 Rods'] as $rCnt => $rLbl): ?>
                                <option value="<?= $rCnt ?>" <?= $defRods === $rCnt ? 'selected' : '' ?>><?= $rLbl ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="js-soroban-theme-select" class="field-select" style="width:155px;">
                            <option value="cyber_emerald">✨ Cyber Emerald</option>
                            <option value="classic_wood">🪵 Classic Wood</option>
                            <option value="royal_gold">👑 Royal Gold</option>
                        </select>
                        <button type="button" class="btn-secondary btn-sm" id="js-soroban-sound-btn">🔊 Sound: ON</button>
                        <button type="button" class="btn-primary btn-sm" id="js-soroban-new-challenge">🎯 Challenge</button>
                        <button type="button" class="btn-secondary btn-sm" id="js-soroban-clear-btn">↺ Reset</button>
                    </div>
                </div>
                <div id="js-soroban-challenge-toast" class="alert-banner alert-success is-hidden" style="margin-bottom:1rem;"></div>
                <div class="aps-soroban-stage theme-<?= esc($defTheme) ?>" id="js-soroban-frame-wrap">
                    <div class="aps-soroban-outer-frame">
                        <div class="aps-soroban-rods-container" id="js-soroban-rods-container"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
