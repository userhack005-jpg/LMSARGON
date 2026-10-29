(function () {
    'use strict';
    if (window.__CMS_ABACUS_PRO_SUITE_V85__) return;
    window.__CMS_ABACUS_PRO_SUITE_V85__ = true;

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Register BOTH Frontend Page Builder Blocks!
    if (!window.CMS_ADDON_BLOCK_RENDERERS) {
        window.CMS_ADDON_BLOCK_RENDERERS = {};
    }

    window.CMS_ADDON_BLOCK_RENDERERS['abacus_soroban_lab'] = function (blk, idx, isFooter, colIdx) {
        var d = blk.data ? blk.data : {};
        var cls = isFooter ? 'js-f-field' : 'js-blk-field';
        var attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        var rods = String(d.default_rods || '5');
        var thm  = String(d.theme || 'cyber_emerald');
        return '<div class="grid-3-col">' +
            '<div class="field-group"><label class="field-label"><span>Heading</span></label>' +
            '<input type="text" class="field-input ' + cls + '" ' + attr + ' data-key="heading" value="' + escapeHtml(d.heading || 'Try Our 3D Japanese Soroban Abacus Live') + '"></div>' +
            '<div class="field-group"><label class="field-label"><span>Default Rods (5–17)</span></label>' +
            '<select class="field-select ' + cls + '" ' + attr + ' data-key="default_rods">' +
            ['5','7','9','11','13','15','17'].map(function(r){ return '<option value="'+r+'" '+(rods===r?'selected':'')+'>'+r+' Rods</option>'; }).join('') +
            '</select></div>' +
            '<div class="field-group"><label class="field-label"><span>Theme</span></label>' +
            '<select class="field-select ' + cls + '" ' + attr + ' data-key="theme">' +
            '<option value="cyber_emerald" '+(thm==='cyber_emerald'?'selected':'')+'>Cyber Emerald</option>' +
            '<option value="classic_wood" '+(thm==='classic_wood'?'selected':'')+'>Classic Wood</option>' +
            '<option value="royal_gold" '+(thm==='royal_gold'?'selected':'')+'>Royal Gold</option>' +
            '</select></div></div>';
    };

    window.CMS_ADDON_BLOCK_RENDERERS['abacus_flash_anzan'] = function (blk, idx, isFooter, colIdx) {
        var d = blk.data ? blk.data : {};
        var cls = isFooter ? 'js-f-field' : 'js-blk-field';
        var attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        return '<div class="grid-2-col">' +
            '<div class="field-group"><label class="field-label"><span>Badge</span></label>' +
            '<input type="text" class="field-input ' + cls + '" ' + attr + ' data-key="badge" value="' + escapeHtml(d.badge || 'SPEED MENTAL MATH ARENA') + '"></div>' +
            '<div class="field-group"><label class="field-label"><span>Heading</span></label>' +
            '<input type="text" class="field-input ' + cls + '" ' + attr + ' data-key="heading" value="' + escapeHtml(d.heading || 'Flash Anzan & Voice Dictation Challenge') + '"></div>' +
            '</div>';
    };

    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'abacus_soroban_lab' } }));
    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'abacus_flash_anzan' } }));

    var audioCtx = null;
    function playBeadClick(freq, durationMs) {
        try {
            if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (!audioCtx) return;
            if (audioCtx.state === 'suspended') audioCtx.resume();
            var osc  = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq || 540, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.16, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + ((durationMs || 38) / 1000));
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + ((durationMs || 38) / 1000));
        } catch (e) {}
    }

    // Authentic Hindi Number Words (1 to 99) for Crisp Hindi Dictation!
    var HINDI_NUMS = {
        0:'शून्य', 1:'एक', 2:'दो', 3:'तीन', 4:'चार', 5:'पाँच', 6:'छह', 7:'सात', 8:'आठ', 9:'नौ', 10:'दस',
        11:'ग्यारह', 12:'बारह', 13:'तेरह', 14:'चौदह', 15:'पंद्रह', 16:'सोलह', 17:'सत्रह', 18:'अठारह', 19:'उन्नीस', 20:'बीस',
        21:'इक्कीस', 22:'बाईस', 23:'तेईस', 24:'चौबीस', 25:'पच्चीस', 26:'छब्बीस', 27:'सत्ताईस', 28:'अट्ठाईस', 29:'उनतीस', 30:'तीस',
        31:'इकतीस', 32:'बत्तीस', 33:'तैंतीस', 34:'चौंतीस', 35:'पैंतीस', 36:'छत्तीस', 37:'सैंतीस', 38:'अड़तीस', 39:'उनतालीस', 40:'चालीस',
        41:'इकतालीस', 42:'बयालीस', 43:'तैंतालीस', 44:'चवालीस', 45:'पैंतालीस', 46:'छियालीस', 47:'सैंतालीस', 48:'अड़तालीस', 49:'उनचास', 50:'पचास'
    };

    var cachedVoices = [];
    function loadVoices() {
        if ('speechSynthesis' in window) {
            cachedVoices = window.speechSynthesis.getVoices() || [];
        }
    }
    if ('speechSynthesis' in window) {
        loadVoices();
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }

    /**
     * Ultra-Smooth Fast Voice Dictation Engine (Supports 0.3s, 0.5s, 0.7s, 1.0s, 1.5s, 2.0s in Hindi & English!)
     */
    function speakCrispNumber(val, langCode, speedSec) {
        playBeadClick(val >= 0 ? 600 : 420, 32);
        try {
            if (!('speechSynthesis' in window)) return;
            var absVal = Math.abs(val);
            var isHindi = (langCode === 'hi-IN');
            var spokenText = '';

            if (isHindi) {
                var numWord = HINDI_NUMS[absVal] || String(absVal);
                spokenText = val < 0 ? ('माइनस ' + numWord) : numWord;
            } else {
                spokenText = val < 0 ? ('Minus ' + absVal) : String(absVal);
            }

            // Dynamic Rate Scaling so 0.3s / 0.5s / 0.7s finishes speaking BEFORE the next number flashes!
            var dynRate = 1.25;
            if (speedSec <= 0.35) dynRate = 2.85;
            else if (speedSec <= 0.55) dynRate = 2.25;
            else if (speedSec <= 0.8) dynRate = 1.75;
            else if (speedSec <= 1.1) dynRate = 1.35;

            window.speechSynthesis.cancel();
            var utter = new SpeechSynthesisUtterance(spokenText);
            utter.lang  = isHindi ? 'hi-IN' : 'en-IN';
            utter.rate  = dynRate;
            utter.pitch = 1.05;

            if (!cachedVoices.length) loadVoices();
            var matchVoice = null;
            for (var i = 0; i < cachedVoices.length; i++) {
                var v = cachedVoices[i];
                if (isHindi && (v.lang.indexOf('hi') === 0 || v.name.toLowerCase().indexOf('hindi') !== -1)) {
                    matchVoice = v;
                    break;
                }
                if (!isHindi && v.lang.indexOf('en') === 0) {
                    matchVoice = v;
                    break;
                }
            }
            if (matchVoice) utter.voice = matchVoice;
            window.speechSynthesis.speak(utter);
        } catch (e) {}
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
        .then(function (res) { if (typeof onDone === 'function') onDone(res); })
        .catch(function () {});
    }

    function initSorobanLab() {
        var rootEl = document.getElementById('js-aps-soroban-root');
        if (!rootEl || rootEl.dataset.mounted === '1') return;
        rootEl.dataset.mounted = '1';

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

        var rodsCount    = parseInt(rootEl.getAttribute('data-rods') || '5', 10) || 5;
        var soundEnabled = rootEl.getAttribute('data-sound') !== '0';
        var rodValues    = [];
        var targetNumber = null;
        var challStartTs = 0;

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
            if (currentValEl) currentValEl.textContent = Number(totalStr).toLocaleString('en-IN');

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
                        toastEl.textContent = '🎉 Target ' + solvedTarget + ' Solved in ' + elapsedSec + 's! ' + ((res && res.message) ? res.message : '');
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
            if (soundEnabled) playBeadClick(type === 'heaven' ? 660 : 490, 35);
            syncRodVisuals(rodIdx);
            updateTotalReadout();
        });

        if (rodsSel) {
            rodsSel.addEventListener('change', function () {
                buildSorobanRods(parseInt(rodsSel.value, 10) || 5);
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
                if (soundEnabled) playBeadClick(360, 50);
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
                var maxPow = Math.min(4, rodsCount);
                targetNumber = Math.floor(10 + Math.random() * (Math.pow(10, maxPow) - 15));
                challStartTs = Date.now();
                if (targetValEl) targetValEl.textContent = Number(targetNumber).toLocaleString('en-IN');
                if (toastEl) toastEl.classList.add('is-hidden');
            });
        }

        buildSorobanRods(rodsCount);
    }

    function initFlashAnzanArena() {
        var rootEl = document.getElementById('js-aps-anzan-root');
        if (!rootEl || rootEl.dataset.mounted === '1') return;
        rootEl.dataset.mounted = '1';

        var modeSel     = document.getElementById('js-anz-mode');
        var langSel     = document.getElementById('js-anz-lang');
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

        function generateSequence(digits, rows, allowSub) {
            var minVal  = Math.pow(10, digits - 1);
            var maxVal  = Math.pow(10, digits) - 1;
            var seq     = [];
            var running = 0;

            for (var i = 0; i < rows; i++) {
                var n = Math.floor(minVal + Math.random() * (maxVal - minVal + 1));
                if (allowSub && i > 0 && running > n && Math.random() < 0.42) {
                    n = -n;
                }
                running += n;
                seq.push(n);
            }
            expectedSum = running;
            return seq;
        }

        function startDrill() {
            clearTimeout(drillTimer);
            var mode     = modeSel ? modeSel.value : 'voice_dictation';
            var lang     = langSel ? langSel.value : 'hi-IN';
            var digits   = parseInt(digitsSel ? digitsSel.value : '1', 10) || 1;
            var rows     = parseInt(rowsSel ? rowsSel.value : '5', 10) || 5;
            var speedSec = parseFloat(speedSel ? speedSel.value : '1.0') || 1.0;
            var allowSub = !opsSel || opsSel.value === 'add_sub';
            var stepMs   = Math.max(250, Math.round(speedSec * 1000));

            sequence = generateSequence(digits, rows, allowSub);
            if (ansForm) ansForm.classList.add('is-hidden');
            if (resBanner) resBanner.classList.add('is-hidden');
            if (userAnsInp) userAnsInp.value = '';
            if (subHintEl) subHintEl.textContent = 'Calculate on your mental abacus...';

            var idx = 0;
            startTs = Date.now();

            function stepFlash() {
                if (idx >= sequence.length) {
                    bigNumEl.textContent = '?';
                    progPill.textContent = 'ENTER FINAL SUM';
                    if (mode === 'voice_dictation' && ('speechSynthesis' in window)) {
                        window.speechSynthesis.cancel();
                        var endU = new SpeechSynthesisUtterance(lang === 'hi-IN' ? 'उत्तर बताइए' : 'Answer');
                        endU.lang = lang;
                        endU.rate = 1.4;
                        window.speechSynthesis.speak(endU);
                    }
                    if (ansForm) {
                        ansForm.classList.remove('is-hidden');
                        if (userAnsInp) userAnsInp.focus();
                    }
                    return;
                }

                var val     = sequence[idx];
                var signStr = val >= 0 ? ('+' + val) : String(val);

                // Brief visual blanking (40ms) so consecutive identical numbers visibly flash!
                bigNumEl.style.opacity = '0.25';
                setTimeout(function () {
                    bigNumEl.textContent   = signStr;
                    bigNumEl.style.opacity = '1';
                    bigNumEl.style.color   = val >= 0 ? '#10b981' : '#f59e0b';
                }, 35);

                progPill.textContent  = 'ROW ' + (idx + 1) + ' / ' + sequence.length;
                timerPill.textContent = '⏱ ' + ((Date.now() - startTs) / 1000).toFixed(1) + 's';

                if (mode === 'voice_dictation') {
                    speakCrispNumber(val, lang, speedSec);
                } else {
                    playBeadClick(val >= 0 ? 600 : 420, 35);
                }

                idx++;
                drillTimer = setTimeout(stepFlash, stepMs);
            }

            stepFlash();
        }

        if (startBtn) startBtn.addEventListener('click', startDrill);
        if (replayBtn) replayBtn.addEventListener('click', startDrill);

        if (ansForm) {
            ansForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var userVal   = parseInt(userAnsInp.value || '0', 10);
                var isCorrect = (userVal === expectedSum);
                var elapsed   = Math.max(1, Math.round((Date.now() - startTs) / 1000));

                bigNumEl.style.color = isCorrect ? '#10b981' : '#f43f5e';
                bigNumEl.textContent = isCorrect ? ('✓ ' + expectedSum) : ('Ans: ' + expectedSum);
                if (isCorrect) playBeadClick(880, 150);

                sendAttemptToServer(logUrl, {
                    drill_mode: modeSel ? modeSel.value : 'voice_dictation',
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

    function initAll() {
        initSorobanLab();
        initFlashAnzanArena();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
