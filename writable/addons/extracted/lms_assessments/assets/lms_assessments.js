(function () {
    'use strict';
    if (window.__CMS_LMS_ASSESSMENTS_V80__) return;
    window.__CMS_LMS_ASSESSMENTS_V80__ = true;

    const escapeHtml = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    if (!window.CMS_ADDON_BLOCK_RENDERERS) {
        window.CMS_ADDON_BLOCK_RENDERERS = {};
    }
    window.CMS_ADDON_BLOCK_RENDERERS['certificate_verifier'] = function (blk, idx, isFooter, colIdx) {
        const d = blk.data ? blk.data : {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        return `
            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Top Badge Text</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge ?? 'VERIFY ACADEMIC CREDENTIALS')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Main Heading</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="heading" value="${escapeHtml(d.heading ?? 'Official Certificate Verification Portal')}">
                </div>
            </div>
            <div class="field-group">
                <label class="field-label"><span>Subheading Text</span></label>
                <input type="text" class="field-input ${clsField}" ${attr} data-key="subheading" value="${escapeHtml(d.subheading ?? 'Enter a student certificate serial code to verify completion and accuracy.')}">
            </div>
        `;
    };
    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'certificate_verifier' } }));

    function updateCsrfToken(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="csrf_test_name"]').forEach(function (inp) {
            inp.value = token;
        });
    }

    function bootQuestionBankEditor() {
        var qbTypeSel = document.getElementById('qb_type');
        var mcqBox    = document.getElementById('js-qb-mcq-options-box');
        var cancelQb  = document.getElementById('js-qbank-cancel-edit');

        if (qbTypeSel && mcqBox) {
            qbTypeSel.addEventListener('change', function () {
                mcqBox.classList.toggle('is-hidden', qbTypeSel.value === 'input');
            });
        }

        document.querySelectorAll('.js-edit-qbank').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var raw = btn.getAttribute('data-q');
                if (!raw) return;
                var q = JSON.parse(raw);
                document.getElementById('qb_id').value = q.id || '0';
                document.getElementById('qb_node_id').value = q.node_id || '0';
                document.getElementById('qb_type').value = q.question_type || 'mcq';
                document.getElementById('qb_difficulty').value = q.difficulty || 'medium';
                document.getElementById('qb_topic').value = q.topic_tag || '';
                document.getElementById('qb_text').value = q.question_text || '';
                document.getElementById('qb_opt_a').value = q.option_a || '';
                document.getElementById('qb_opt_b').value = q.option_b || '';
                document.getElementById('qb_opt_c').value = q.option_c || '';
                document.getElementById('qb_opt_d').value = q.option_d || '';
                document.getElementById('qb_correct').value = q.correct_answer || '';
                if (mcqBox) mcqBox.classList.toggle('is-hidden', q.question_type === 'input');
                var t = document.getElementById('js-qbank-form-title');
                if (t) t.textContent = 'Edit Question';
                if (cancelQb) cancelQb.classList.remove('is-hidden');
                document.getElementById('qb_text').focus();
            });
        });

        if (cancelQb && cancelQb.dataset.bound !== '1') {
            cancelQb.dataset.bound = '1';
            cancelQb.addEventListener('click', function () {
                document.getElementById('js-qbank-form').reset();
                document.getElementById('qb_id').value = '0';
                if (mcqBox) mcqBox.classList.remove('is-hidden');
                var t = document.getElementById('js-qbank-form-title');
                if (t) t.textContent = 'Add Question to Course Bank';
                cancelQb.classList.add('is-hidden');
            });
        }
    }

    function bootAdminEditors() {
        var selModeEl     = document.getElementById('las_selection_mode');
        var manualPicker  = document.getElementById('js-las-manual-qpicker');
        var examNodeSel   = document.getElementById('las_exam_node');
        var cancelExamBtn = document.getElementById('js-las-cancel-exam-edit');

        function syncSelectionModeUi() {
            if (!selModeEl || !manualPicker) return;
            var isManual = (selModeEl.value === 'manual_selected');
            manualPicker.classList.toggle('is-hidden', !isManual);

            if (examNodeSel) {
                var targetNid = parseInt(examNodeSel.value || '0', 10);
                document.querySelectorAll('.js-qpicker-row').forEach(function (row) {
                    var rNid = parseInt(row.getAttribute('data-node-id') || '0', 10);
                    var show = (targetNid === 0 || rNid === 0 || rNid === targetNid);
                    row.style.display = show ? '' : 'none';
                });
            }
        }

        if (selModeEl) selModeEl.addEventListener('change', syncSelectionModeUi);
        if (examNodeSel) examNodeSel.addEventListener('change', syncSelectionModeUi);
        syncSelectionModeUi();

        document.querySelectorAll('.js-las-edit-exam').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var raw = btn.getAttribute('data-exam');
                if (!raw) return;
                var ex = JSON.parse(raw);
                document.getElementById('las_exam_id').value = ex.id || '0';
                document.getElementById('las_exam_node').value = ex.node_id || '0';
                document.getElementById('las_exam_type').value = ex.exam_type || 'practice_test';
                document.getElementById('las_exam_title').value = ex.title || '';
                if (selModeEl) selModeEl.value = ex.selection_mode || 'random_from_bank';
                var qCntInp = document.getElementById('las_question_count');
                if (qCntInp) qCntInp.value = ex.question_count || '10';
                document.getElementById('las_unlock_mode').value = ex.unlock_mode || 'always_open';
                document.getElementById('las_unlock_session').value = ex.unlock_after_session_id || '0';
                document.getElementById('las_exam_dur').value = ex.duration_minutes || '5';
                document.getElementById('las_exam_pass').value = ex.pass_percentage || '75';
                document.getElementById('las_exam_cert').checked = parseInt(ex.award_certificate || 0, 10) === 1;

                var selArr = Array.isArray(ex.selected_qids_arr) ? ex.selected_qids_arr.map(Number) : [];
                document.querySelectorAll('.js-las-qid-cb').forEach(function (cb) {
                    cb.checked = selArr.indexOf(parseInt(cb.value, 10)) !== -1;
                });

                syncSelectionModeUi();
                var t = document.getElementById('js-las-exam-form-title');
                if (t) t.textContent = 'Edit Assessment: ' + ex.title;
                if (cancelExamBtn) cancelExamBtn.classList.remove('is-hidden');
                document.getElementById('las_exam_title').focus();
            });
        });

        if (cancelExamBtn && cancelExamBtn.dataset.bound !== '1') {
            cancelExamBtn.dataset.bound = '1';
            cancelExamBtn.addEventListener('click', function () {
                document.getElementById('js-las-exam-builder-form').reset();
                document.getElementById('las_exam_id').value = '0';
                syncSelectionModeUi();
                var t = document.getElementById('js-las-exam-form-title');
                if (t) t.textContent = 'Create Exam or Practice Drill';
                cancelExamBtn.classList.add('is-hidden');
            });
        }

        var cancelWsBtn = document.getElementById('js-las-cancel-ws-edit');
        document.querySelectorAll('.js-las-edit-ws').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var raw = btn.getAttribute('data-ws');
                if (!raw) return;
                var ws = JSON.parse(raw);
                document.getElementById('las_ws_id').value = ws.id || '0';
                document.getElementById('las_ws_node').value = ws.node_id || '0';
                document.getElementById('las_ws_type').value = ws.worksheet_type || 'PDF Practice Sheet';
                document.getElementById('las_ws_title').value = ws.title || '';
                document.getElementById('las_ws_url').value = ws.file_url || '';
                document.getElementById('las_ws_retention').value = ws.retention_mode || 'permanent_after_upgrade';
                document.getElementById('las_ws_instr').value = ws.instructions || '';
                var t = document.getElementById('js-las-ws-form-title');
                if (t) t.textContent = 'Edit Worksheet: ' + ws.title;
                if (cancelWsBtn) cancelWsBtn.classList.remove('is-hidden');
                document.getElementById('las_ws_title').focus();
            });
        });
    }

    function bootExamTimerAndAjaxSubmit() {
        var timerEl       = document.getElementById('js-las-timer');
        var examForm      = document.getElementById('js-las-exam-form');
        var timeInput     = document.getElementById('js-las-time-taken');
        var resBanner     = document.getElementById('js-las-instant-result');
        var submitBtn     = document.getElementById('js-las-submit-btn');
        var submitActions = document.getElementById('js-las-submit-actions');
        if (!timerEl || !examForm) return;

        var remainingSeconds = parseInt(timerEl.getAttribute('data-remaining-seconds') || '300', 10);
        var totalSeconds     = parseInt(timerEl.getAttribute('data-total-seconds') || '300', 10);
        var draftUrl         = timerEl.getAttribute('data-draft-url') || '';
        var isSubmitted      = false;

        var interval = setInterval(function () {
            if (isSubmitted) {
                clearInterval(interval);
                return;
            }
            remainingSeconds = Math.max(0, remainingSeconds - 1);
            var elapsed = Math.max(1, totalSeconds - remainingSeconds);
            if (timeInput) timeInput.value = String(elapsed);

            var m = Math.floor(remainingSeconds / 60);
            var s = remainingSeconds % 60;
            timerEl.textContent = '⏱ ' + (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);

            if (remainingSeconds <= 30) {
                timerEl.style.color = '#f43f5e';
                timerEl.style.borderColor = '#f43f5e';
            }

            if (remainingSeconds <= 0) {
                clearInterval(interval);
                examForm.querySelectorAll('[required]').forEach(function (el) { el.removeAttribute('required'); });
                examForm.requestSubmit();
            }
        }, 1000);

        examForm.addEventListener('change', function (e) {
            if (!e.target.classList.contains('js-las-ans-input') || !draftUrl || isSubmitted) return;
            fetch(draftUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(examForm)
            }).catch(function () {});
        });

        if (examForm.dataset.bound !== '1') {
            examForm.dataset.bound = '1';
            examForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (isSubmitted) return;
                isSubmitted = true;
                clearInterval(interval);
                if (submitBtn) submitBtn.disabled = true;

                fetch(examForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: new FormData(examForm)
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.csrf_token) updateCsrfToken(res.csrf_token);
                    if (resBanner) {
                        resBanner.className = 'alert-banner ' + (res.passed ? 'alert-success' : 'alert-error');
                        resBanner.innerHTML = '<div><strong>' + escapeHtml(res.message || 'Submitted!') + '</strong></div>' +
                            '<div style="margin-top:0.75rem; display:flex; gap:0.65rem; flex-wrap:wrap;">' +
                            (res.pdf_url ? ('<a href="' + escapeHtml(res.pdf_url) + '" class="btn-primary btn-sm">⬇ Download PDF Certificate</a>') : '') +
                            (res.next_set_url ? ('<a href="' + escapeHtml(res.next_set_url) + '" class="btn-secondary btn-sm">🔄 Start Next Practice Set →</a>') : '') +
                            (res.history_url ? ('<a href="' + escapeHtml(res.history_url) + '" class="btn-action" style="text-decoration:none;">🏆 View My Score History →</a>') : '') +
                            '</div>';
                        resBanner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }

                    if (submitActions && res.next_set_url) {
                        submitActions.innerHTML = '<a href="' + escapeHtml(res.next_set_url) + '" class="btn-primary" style="flex:1; justify-content:center; text-decoration:none;">🔄 Start Next Randomized Practice Set →</a>' +
                            '<a href="' + escapeHtml(res.history_url || '/assessments/portal/history') + '" class="btn-secondary" style="flex:1; justify-content:center; text-decoration:none;">🏆 View My Score History →</a>';
                    }
                })
                .catch(function () {
                    isSubmitted = false;
                    if (submitBtn) submitBtn.disabled = false;
                });
            });
        }
    }

    function bootCertificateVerifiers() {
        document.querySelectorAll('.las-verify-bar').forEach(function (bar) {
            if (bar.dataset.bound === '1') return;
            bar.dataset.bound = '1';

            var inp    = bar.querySelector('.js-las-cert-input');
            var btn    = bar.querySelector('.js-las-cert-btn');
            var resBox = bar.parentElement.querySelector('.js-las-cert-result');
            var url    = bar.getAttribute('data-verify-url');

            if (!btn || !inp || !url) return;
            btn.addEventListener('click', function () {
                var code = inp.value.trim();
                if (!code) return;
                fetch(url + '?code=' + encodeURIComponent(code), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!resBox) return;
                    if (data && data.ok && data.certificate) {
                        var c = data.certificate;
                        resBox.style.color = '#10b981';
                        resBox.innerHTML = '✅ VERIFIED: <strong>' + escapeHtml(c.student_name) + '</strong> — ' + escapeHtml(c.course_title) +
                            ' (' + escapeHtml(c.score_pct) + '% Accuracy) <a href="' + escapeHtml(data.pdf_url) + '" class="btn-secondary btn-sm" style="margin-left:8px;">⬇ Download PDF</a>';
                    } else {
                        resBox.style.color = '#f43f5e';
                        resBox.textContent = (data && data.message) ? data.message : 'Invalid certificate code.';
                    }
                })
                .catch(function () {});
            });
        });
    }

    function init() {
        bootQuestionBankEditor();
        bootAdminEditors();
        bootExamTimerAndAjaxSubmit();
        bootCertificateVerifiers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
