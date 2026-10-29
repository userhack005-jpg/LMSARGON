(function () {
    'use strict';
    if (window.__CMS_ABACUS_PRO_SUITE_V80__) return;
    window.__CMS_ABACUS_PRO_SUITE_V80__ = true;

    var audioCtx = null;

    function playBeadClick(freq, durationMs) {
        try {
            if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (!audioCtx) return;
            var osc  = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq || 520, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.14, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + ((durationMs || 45) / 1000));
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + ((durationMs || 45) / 1000));
        } catch (e) {}
    }

    function updateCsrfToken(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="csrf_test_name"]').forEach(function (inp) { inp.value = token; });
    }

    function sendAttemptToServer(logUrl, payload, onDone) {
        if (!logUrl) return;
        var fd = new FormData();
        Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) fd.append('csrf_test_name', csrfMeta.getAttribute('content'));

        fetch(logUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.csrf_token) updateCsrfToken(res.csrf_token);
            if (typeof onDone === 'function') onDone(res);
        })
        .catch(function () {});
    }

    /**
     * 1. Interactive 3D Soroban Abacus Lab (`/abacus/arena`)
     */
    function initSorobanLab() {
        var rootEl = document.getElementById('js-aps-soroban-root');
        if (!rootEl) return;

        var rodsContainer = document.getElementById('js-soroban-rods-container');
        var currentValEl  = document.getElementById('js-soroban-current-val');
        var targetValEl   = document.getElementById('js-soroban-target-val');
        var rodsSel       = document.getElementById('js-soroban-rods-select');
        var themeSel      = document.getElementById('js-soroban-theme-select');
        var soundBtn      = document.getElementById('js-soroban-sound-btn');
        var newChallBtn   = document.getElementById('js-soroban-new-challenge');
        var clearBtn      = document.getElementById('js-soroban-clear-btn');
        var frameWrap     = document.getElementById('js-soroban-frame-wrap');
        var toastEl       = document.getElementById('js-soroban-challenge-toast');
        var logUrl        = rootEl.getAttribute('data-log-url') || '';

        var rodsCount     = parseInt(rootEl.getAttribute('data-rods') || '13', 10);
        var soundEnabled  = rootEl.getAttribute('data-sound') !== '0';
        var rodValues     = [];
        var targetNumber  = null;
        var challStartTs  = 0;

        function buildSorobanRods(count) {
            rodsCount = count;
            rodValues = new Array(count).fill(0);
            var html  = '';
            for (var i = 0; i < count; i++) {
                var showDot = ((count - 1 - i) % 3 === 0);
                html += '<div class="aps-rod-col" data-rod="' + i + '">' +
                    '<div class="aps-rod-line"></div>' +
                    '<div class="aps-heaven-deck">' +
                    '  <div class="aps-bead aps-heaven-bead" data-rod="' + i + '" data-type="heaven"></div>' +
                    '</div>' +
                    '<div class="aps-reckoning-beam">' + (showDot ? '<span class="aps-unit-dot"></span>' : '') + '</div>' +
                    '<div class="aps-earth-deck">' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="1"></div>' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="2"></div>' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="3"></div>' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="4"></div>' +
                    '</div>' +
                    '<div class="aps-rod-digit-box" id="js-rod-dig-' + i + '">0</div>' +
                    '</div>';
            }
            rodsContainer.innerHTML = html;
            updateTotalReadout();
        }

        function syncRodVisuals(rodIdx) {
            var val    = rodValues[rodIdx] || 0;
            var heaven = val >= 5 ? 1 : 0;
            var earth  = val % 5;
            var colEl  = rodsContainer.querySelector('.aps-rod-col[data-rod="' + rodIdx + '"]');
            if (!colEl) return;

            var hBead = colEl.querySelector('.aps-heaven-bead');
            if (hBead) hBead.classList.toggle('is-active', heaven === 1);

            colEl.querySelectorAll('.aps-earth-bead').forEach(function (eb) {
                var idx = parseInt(eb.getAttribute('data-idx'), 10);
                eb.classList.toggle('is-active', idx <= earth);
            });

            var digBox = document.getElementById('js-rod-dig-' + rodIdx);
            if (digBox) digBox.textContent = String(val);
        }

        function computeTotalAbacusValue() {
            var str = rodValues.map(function (v) { return String(v || 0); }).join('').replace(/^0+/, '');
            return str === '' ? '0' : str;
        }

        function updateTotalReadout() {
            var totalStr = computeTotalAbacusValue();
            if (currentValEl) {
                currentValEl.textContent = Number(totalStr).toLocaleString('en-IN');
            }

            if (targetNumber !== null && String(targetNumber) === totalStr) {
                var elapsedSec = Math.max(1, Math.round((Date.now() - challStartTs) / 1000));
                var solvedTarget = targetNumber;
                targetNumber = null;
                if (soundEnabled) playBeadClick(880, 140);

                sendAttemptToServer(logUrl, {
                    drill_mode: 'soroban_challenge',
                    digits_count: String(solvedTarget).length,
                    rows_count: 1,
                    speed_seconds: elapsedSec,
                    total_questions: 1,
                    correct_answers: 1,
                    time_taken_sec: elapsedSec
                }, function (res) {
                    if (toastEl) {
                        toastEl.textContent = (res && res.message)
                            ? ('🎉 Target ' + solvedTarget + ' Matched in ' + elapsedSec + 's! ' + res.message)
                            : ('🎉 Target ' + solvedTarget + ' Matched in ' + elapsedSec + 's!');
                        toastEl.classList.remove('is-hidden');
                    }
                });
            }
        }

        rodsContainer.addEventListener('click', function (e) {
            var bead = e.target.closest('.aps-bead');
            if (!bead) return;

            var rodIdx   = parseInt(bead.getAttribute('data-rod'), 10);
            var type     = bead.getAttribute('data-type');
            var currVal  = rodValues[rodIdx] || 0;
            var hasHeav  = currVal >= 5;
            var earthVal = currVal % 5;

            if (type === 'heaven') {
                hasHeav = !hasHeav;
            } else {
                var clickedIdx = parseInt(bead.getAttribute('data-idx'), 10);
                earthVal = (earthVal >= clickedIdx) ? (clickedIdx - 1) : clickedIdx;
            }

            rodValues[rodIdx] = (hasHeav ? 5 : 0) + earthVal;
            if (soundEnabled) playBeadClick(type === 'heaven' ? 640 : 480, 40);
            syncRodVisuals(rodIdx);
            updateTotalReadout();
        });

        if (rodsSel) {
            rodsSel.addEventListener('change', function () {
                buildSorobanRods(parseInt(rodsSel.value, 10) || 13);
            });
        }

        if (themeSel && frameWrap) {
            themeSel.addEventListener('change', function () {
                frameWrap.className = 'aps-soroban-stage theme-' + themeSel.value;
            });
        }

        if (soundBtn) {
            soundBtn.addEventListener('click', function () {
                soundEnabled = !soundEnabled;
                soundBtn.textContent = soundEnabled ? '🔊 Sound: ON' : '🔇 Sound: OFF';
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                for (var i = 0; i < rodsCount; i++) {
                    rodValues[i] = 0;
                    syncRodVisuals(i);
                }
                if (soundEnabled) playBeadClick(360, 60);
                updateTotalReadout();
            });
        }

        if (newChallBtn) {
            newChallBtn.addEventListener('click', function () {
                for (var i = 0; i < rodsCount; i++) {
                    rodValues[i] = 0;
                    syncRodVisuals(i);
                }
                updateTotalReadout();
                targetNumber = Math.floor(100 + Math.random() * 9899);
                challStartTs = Date.now();
                if (targetValEl) targetValEl.textContent = Number(targetNumber).toLocaleString('en-IN');
                if (toastEl) toastEl.classList.add('is-hidden');
            });
        }

        buildSorobanRods(rodsCount);
    }

    /**
     * 2. Flash Anzan & Voice Dictation Speed Arena (`/abacus/anzan`)
     */
    function initFlashAnzanArena() {
        var rootEl = document.getElementById('js-aps-anzan-root');
        if (!rootEl) return;

        var modeSel     = document.getElementById('js-anz-mode');
        var digitsSel   = document.getElementById('js-anz-digits');
        var rowsSel     = document.getElementById('js-anz-rows');
        var speedSel    = document.getElementById('js-anz-speed');
        var opsSel      = document.getElementById('js-anz-ops');
        var startBtn    = document.getElementById('js-anz-start-btn');
        var replayBtn   = document.getElementById('js-anz-replay-btn');
        var progPill    = document.getElementById('js-anz-progress-pill');
        var timerPill   = document.getElementById('js-anz-timer-pill');
        var bigNumEl    = document.getElementById('js-anz-big-num');
        var subHintEl   = document.getElementById('js-anz-sub-hint');
        var ansForm     = document.getElementById('js-anz-answer-form');
        var userAnsInp  = document.getElementById('js-anz-user-ans');
        var resBanner   = document.getElementById('js-anz-result-banner');
        var logUrl      = rootEl.getAttribute('data-log-url') || '';

        var sequence    = [];
        var expectedSum = 0;
        var drillTimer  = null;
        var startTs     = 0;

        function speakText(text) {
            try {
                if (!('speechSynthesis' in window)) return;
                window.speechSynthesis.cancel();
                var utter = new SpeechSynthesisUtterance(text);
                utter.rate = 1.15;
                window.speechSynthesis.speak(utter);
            } catch (e) {}
        }

        function generateSequence(digits, rows, allowSub) {
            var minVal = Math.pow(10, digits - 1);
            var maxVal = Math.pow(10, digits) - 1;
            var seq    = [];
            var running = 0;

            for (var i = 0; i < rows; i++) {
                var n = Math.floor(minVal + Math.random() * (maxVal - minVal + 1));
                if (allowSub && i > 0 && running > n && Math.random() < 0.4) {
                    n = -n;
                }
                running += n;
                seq.push(n);
            }
            expectedSum = running;
            return seq;
        }

        function startDrill() {
            clearInterval(drillTimer);
            var mode     = modeSel ? modeSel.value : 'flash_anzan';
            var digits   = parseInt(digitsSel ? digitsSel.value : '1', 10) || 1;
            var rows     = parseInt(rowsSel ? rowsSel.value : '5', 10) || 5;
            var speedSec = parseFloat(speedSel ? speedSel.value : '1.0') || 1.0;
            var allowSub = !opsSel || opsSel.value === 'add_sub';

            sequence = generateSequence(digits, rows, allowSub);
            if (ansForm) ansForm.classList.add('is-hidden');
            if (resBanner) resBanner.classList.add('is-hidden');
            if (userAnsInp) userAnsInp.value = '';
            if (subHintEl) subHintEl.textContent = 'Watch carefully and calculate on your mental abacus...';

            var idx = 0;
            startTs = Date.now();

            function stepFlash() {
                if (idx >= sequence.length) {
                    clearInterval(drillTimer);
                    bigNumEl.textContent = '?';
                    progPill.textContent = 'ENTER YOUR ANSWER';
                    if (mode === 'voice_dictation') speakText('Answer please');
                    if (ansForm) {
                        ansForm.classList.remove('is-hidden');
                        if (userAnsInp) userAnsInp.focus();
                    }
                    return;
                }

                var val      = sequence[idx];
                var signStr  = val >= 0 ? ('+' + val) : String(val);
                bigNumEl.textContent = signStr;
                progPill.textContent = 'ROW ' + (idx + 1) + ' OF ' + sequence.length;
                timerPill.textContent = '⏱ ' + ((Date.now() - startTs) / 1000).toFixed(1) + 's';

                if (mode === 'voice_dictation') {
                    speakText(val >= 0 ? ('Plus ' + val) : ('Minus ' + Math.abs(val)));
                } else {
                    playBeadClick(val >= 0 ? 580 : 420, 45);
                }

                idx++;
            }

            stepFlash();
            drillTimer = setInterval(stepFlash, Math.max(200, Math.round(speedSec * 1000)));
        }

        if (startBtn) startBtn.addEventListener('click', startDrill);
        if (replayBtn) replayBtn.addEventListener('click', startDrill);

        if (ansForm) {
            ansForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var userVal   = parseInt(userAnsInp.value || '0', 10);
                var isCorrect = (userVal === expectedSum);
                var elapsed   = Math.max(1, Math.round((Date.now() - startTs) / 1000));

                bigNumEl.textContent = isCorrect ? ('✓ ' + expectedSum) : ('Ans: ' + expectedSum);
                if (isCorrect) playBeadClick(880, 150);

                sendAttemptToServer(logUrl, {
                    drill_mode: modeSel ? modeSel.value : 'flash_anzan',
                    digits_count: digitsSel ? digitsSel.value : 1,
                    rows_count: rowsSel ? rowsSel.value : 5,
                    speed_seconds: speedSel ? speedSel.value : 1.0,
                    total_questions: 1,
                    correct_answers: isCorrect ? 1 : 0,
                    time_taken_sec: elapsed
                }, function (res) {
                    if (!resBanner) return;
                    resBanner.className = 'alert-banner ' + (isCorrect ? 'alert-success' : 'alert-error');
                    resBanner.textContent = (isCorrect ? '🎉 Correct! ' : ('✕ Your answer: ' + userVal + ' (Correct Sum: ' + expectedSum + '). ')) +
                        ((res && res.message) ? res.message : '');
                });
            });
        }
    }

    function initParentChildFilter() {
        var bar = document.getElementById('js-aps-child-filter-bar');
        if (!bar) return;
        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-aps-child-tab');
            if (!btn) return;
            bar.querySelectorAll('.js-aps-child-tab').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            var cid = btn.getAttribute('data-child-id') || 'all';
            document.querySelectorAll('.js-aps-child-row').forEach(function (row) {
                row.style.display = (cid === 'all' || row.getAttribute('data-child-id') === cid) ? '' : 'none';
            });
        });
    }

    function initAll() {
        initSorobanLab();
        initFlashAnzanArena();
        initParentChildFilter();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
