(function () {
    'use strict';
    if (window.__CMS_LMS_TIMETABLE_V90__) return;
    window.__CMS_LMS_TIMETABLE_V90__ = true;

    function getBaseUrl() {
        var b = document.body ? document.body.getAttribute('data-base-url') : '';
        if (!b) b = window.location.origin + '/';
        return b.endsWith('/') ? b : b + '/';
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function updateCsrfToken(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="csrf_test_name"]').forEach(function (inp) { inp.value = token; });
    }

    function showLiveToast(msg) {
        var toast = document.getElementById('js-ltt-live-toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.remove('is-hidden');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { toast.classList.add('is-hidden'); }, 4000);
    }

    var shownLivePopupIds = {};

    function applyLiveSessionSignalToStudentDom(sInfo, allowPopup) {
        if (!sInfo || !sInfo.session_id) return;
        var portalRoot = document.getElementById('js-ltt-portal-root');
        if (!portalRoot) return;

        var viewerRole = (portalRoot.getAttribute('data-viewer-role') || '').toLowerCase();
        // STRICT ARCHITECTURAL GUARD: Never show student popup to super_admin, admin, or teacher!
        if (viewerRole !== 'student' && viewerRole !== 'parent') return;

        var sid        = parseInt(sInfo.session_id, 10);
        var row        = document.getElementById('js-stu-sess-row-' + sid);
        var statusCell = document.getElementById('js-stu-sess-status-' + sid);

        // Only update/popup if this session row exists on this student/parent's schedule!
        if (!row) return;

        if (statusCell) {
            if (sInfo.status === 'live') {
                statusCell.innerHTML = '<a href="' + escapeHtml(sInfo.meeting_link || '#') + '" target="_blank" class="ltt-btn-end-red" style="text-decoration:none; display:inline-flex; width:auto; padding:0.42rem 0.9rem;">🔴 LIVE NOW — Join ↗</a>';
            } else if (sInfo.status === 'completed') {
                statusCell.innerHTML = '<span class="role-badge text-emerald">✓ COMPLETED</span>';
            }
        }

        if (allowPopup && sInfo.status === 'live' && !shownLivePopupIds[sid]) {
            shownLivePopupIds[sid] = true;
            var popModal = document.getElementById('js-ltt-student-live-popup');
            if (!popModal) return;

            document.getElementById('js-live-pop-batch').textContent = sInfo.cohort_title || 'Live Class';
            document.getElementById('js-live-pop-teacher').textContent = '👩‍🏫 Instructor: ' + (sInfo.teacher_name || 'Teacher') + ' has started the class!';
            document.getElementById('js-live-pop-join-btn').href = sInfo.meeting_link || '#';
            var pWrap = document.getElementById('js-live-pop-pdf-wrap');
            if (pWrap) {
                pWrap.innerHTML = sInfo.pdf_url ? ('<a href="' + escapeHtml(sInfo.pdf_url) + '" target="_blank" class="btn-secondary btn-sm">📄 Download Study PDF ↗</a>') : '';
            }
            var dBtn = document.getElementById('js-live-pop-dismiss');
            if (dBtn && !dBtn.dataset.bound) {
                dBtn.dataset.bound = '1';
                dBtn.addEventListener('click', function () { popModal.classList.add('is-hidden'); });
            }
            popModal.classList.remove('is-hidden');
        }
    }

    function bootZeroLoadStudentLiveSignalWatcher() {
        var portalRoot = document.getElementById('js-ltt-portal-root');
        if (!portalRoot) return;
        var viewerRole = (portalRoot.getAttribute('data-viewer-role') || '').toLowerCase();
        if (viewerRole !== 'student' && viewerRole !== 'parent') return;

        var signalUrl     = portalRoot.getAttribute('data-signal-url') || (getBaseUrl() + 'assets/addons/lms_timetable/live_signal.json');
        var lastVersionTs = null;

        function checkStaticSignal(isInitialBoot) {
            if (document.visibilityState && document.visibilityState !== 'visible') return;
            fetch(signalUrl + '?t=' + Math.floor(Date.now() / 5000), { cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.sessions) return;
                    var vTs = parseInt(data.version_ts || 0, 10);
                    var hasChanged = (lastVersionTs !== null && vTs > lastVersionTs);
                    lastVersionTs = vTs;

                    Object.keys(data.sessions).forEach(function (k) {
                        var sInfo = data.sessions[k];
                        if (isInitialBoot && sInfo.status === 'live') {
                            shownLivePopupIds[parseInt(sInfo.session_id, 10)] = true;
                        }
                        applyLiveSessionSignalToStudentDom(sInfo, hasChanged);
                    });
                })
                .catch(function () {});
        }

        checkStaticSignal(true);
        setInterval(function () { checkStaticSignal(false); }, 6000);
    }

    function initAdminHandlers() {
        var makeupModeSel = document.getElementById('js-makeup-mode-select');
        var existingBox   = document.getElementById('js-makeup-existing-box');
        var newMakeupBox  = document.getElementById('js-makeup-new-box');
        if (makeupModeSel && existingBox && newMakeupBox) {
            makeupModeSel.addEventListener('change', function () {
                var isNew = (makeupModeSel.value === 'new_makeup_session');
                existingBox.classList.toggle('is-hidden', isNew);
                newMakeupBox.classList.toggle('is-hidden', !isNew);
            });
        }

        // Batch Filter on Master Schedule
        var batchFilter = document.getElementById('js-admin-sess-batch-filter');
        if (batchFilter) {
            batchFilter.addEventListener('change', function () {
                var val = batchFilter.value;
                document.querySelectorAll('.js-admin-sess-row').forEach(function (row) {
                    row.style.display = (val === 'all' || row.getAttribute('data-cohort-id') === val) ? '' : 'none';
                });
            });
        }

        // 1-Click Reschedule Modal
        var resModal = document.getElementById('js-reschedule-modal');
        var resClose = document.getElementById('js-resched-close');
        document.querySelectorAll('.js-open-reschedule').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!resModal) return;
                document.getElementById('resched_id').value      = btn.getAttribute('data-id') || '0';
                document.getElementById('resched_date').value    = btn.getAttribute('data-ymd') || '';
                document.getElementById('resched_time').value    = btn.getAttribute('data-hm') || '17:00';
                document.getElementById('resched_teacher').value = btn.getAttribute('data-teacher') || '';
                document.getElementById('resched_link').value    = btn.getAttribute('data-link') || '';
                document.getElementById('js-resched-title').textContent = 'Reschedule: ' + (btn.getAttribute('data-title') || 'Class');
                resModal.classList.remove('is-hidden');
            });
        });
        if (resClose && resModal) {
            resClose.addEventListener('click', function () { resModal.classList.add('is-hidden'); });
        }

        document.querySelectorAll('.js-stu-filter-input').forEach(function (inp) {
            inp.addEventListener('input', function () {
                var q = inp.value.toLowerCase().trim();
                var box = document.getElementById(inp.getAttribute('data-target'));
                if (!box) return;
                box.querySelectorAll('.ltt-stu-cb-row').forEach(function (row) {
                    var txt = row.getAttribute('data-search') || '';
                    row.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        });

        var resetCohortBtn = document.getElementById('js-cohort-reset-btn');
        document.querySelectorAll('.js-edit-cohort').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var c = JSON.parse(btn.getAttribute('data-cohort') || '{}');
                document.getElementById('coh_id').value         = c.id || '0';
                document.getElementById('coh_leaf_id').value    = c.leaf_node_id || '';
                document.getElementById('coh_title').value      = c.cohort_title || '';
                document.getElementById('coh_code').value       = c.cohort_code || '';
                document.getElementById('coh_teacher_id').value = c.teacher_user_id || '';
                document.getElementById('coh_seats').value      = c.max_seats || '20';
                document.getElementById('coh_start').value      = c.start_date || '';
                document.getElementById('coh_end').value        = c.end_date || '';
                document.getElementById('coh_time').value       = c.start_time_ist || '17:00';
                document.getElementById('coh_dur').value        = c.class_duration_min || '60';
                document.getElementById('coh_link').value       = c.meeting_link || 'https://meet.google.com/new';
                document.getElementById('coh_pdf').value        = c.material_pdf_url || '';
                document.getElementById('js-cohort-form-title').textContent = 'Edit Batch: ' + c.cohort_title;
                if (resetCohortBtn) resetCohortBtn.classList.remove('is-hidden');
                document.getElementById('coh_title').focus();
            });
        });

        if (resetCohortBtn) {
            resetCohortBtn.addEventListener('click', function () {
                document.getElementById('js-cohort-form').reset();
                document.getElementById('coh_id').value = '0';
                document.getElementById('js-cohort-form-title').textContent = 'Create Live Classroom Batch & Auto-Schedule';
                resetCohortBtn.classList.add('is-hidden');
            });
        }
    }

    function initTeacherWizardAndTabs() {
        document.querySelectorAll('.js-t-tab-btn').forEach(function (tabBtn) {
            tabBtn.addEventListener('click', function (e) {
                var targetId = tabBtn.getAttribute('data-target');
                var targetEl = targetId ? document.getElementById(targetId) : null;
                if (!targetEl) return;
                e.preventDefault();
                document.querySelectorAll('.js-t-tab-btn').forEach(function (b) { b.classList.remove('is-active'); });
                document.querySelectorAll('.js-t-pane').forEach(function (p) { p.classList.add('is-hidden'); });
                tabBtn.classList.add('is-active');
                targetEl.classList.remove('is-hidden');
            });
        });

        var filterBar = document.getElementById('js-parent-child-filter-bar');
        if (filterBar) {
            filterBar.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-filter-child-sched');
                if (!btn) return;
                filterBar.querySelectorAll('.js-filter-child-sched').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                var targetChild = btn.getAttribute('data-child-id') || 'all';
                document.querySelectorAll('.js-student-sched-row').forEach(function (row) {
                    var csv = row.getAttribute('data-child-ids') || '';
                    row.style.display = (targetChild === 'all' || csv.indexOf(',' + targetChild + ',') !== -1) ? '' : 'none';
                });
            });
        }

        var modal     = document.getElementById('js-ltt-start-wizard-modal');
        var wizForm   = document.getElementById('js-wiz-start-form');
        var step1Box  = document.getElementById('js-wiz-step-1');
        var step2Box  = document.getElementById('js-wiz-step-2');
        var stepLabel = document.getElementById('js-wiz-step-indicator');
        var rollList  = document.getElementById('js-wiz-rollcall-list');

        if (!modal || !wizForm) return;

        function showStep(n) {
            step1Box.classList.toggle('is-hidden', n !== 1);
            step2Box.classList.toggle('is-hidden', n !== 2);
            stepLabel.textContent = n === 1
                ? 'STEP 1 OF 2 // CONFIRM LINK & PDF'
                : 'STEP 2 OF 2 // STUDENT ROLL CALL (DEFAULT PRESENT)';
        }

        document.getElementById('js-wiz-close-btn').addEventListener('click', function () { modal.classList.add('is-hidden'); });
        document.getElementById('js-wiz-cancel-1').addEventListener('click', function () { modal.classList.add('is-hidden'); });
        document.getElementById('js-wiz-goto-step-2').addEventListener('click', function () { showStep(2); });
        document.getElementById('js-wiz-back-step-1').addEventListener('click', function () { showStep(1); });

        document.addEventListener('click', function (e) {
            var startBtn = e.target.closest('.js-open-start-wizard');
            if (!startBtn) return;
            e.preventDefault();
            var sess = JSON.parse(startBtn.getAttribute('data-session') || '{}');

            document.getElementById('js-wiz-session-id').value   = sess.id || '0';
            document.getElementById('js-wiz-session-title').textContent = sess.cohort_title || 'Live Class';
            document.getElementById('js-wiz-meeting-link').value = sess.meeting_link || 'https://meet.google.com/new';
            document.getElementById('js-wiz-pdf-url').value      = sess.raw_material_pdf || '';
            document.getElementById('js-wiz-hw-note').value      = sess.assignment_note || '';

            var roster = Array.isArray(sess.roster_for_rollcall) ? sess.roster_for_rollcall : [];
            if (!roster.length) {
                rollList.innerHTML = '<div class="panel-card"><p class="muted-cell" style="margin:0;">No students enrolled in this batch yet. You can launch the class directly.</p></div>';
            } else {
                rollList.innerHTML = roster.map(function (stu) {
                    var sid = parseInt(stu.student_id, 10);
                    var st  = stu.status || 'Present';
                    return '<div class="ltt-rollcall-row">' +
                        '  <div style="display:flex;align-items:center;gap:0.55rem;">' +
                        '    <input type="checkbox" class="js-rc-quick-cb" data-stu="' + sid + '" ' + (st !== 'Absent' ? 'checked' : '') + '>' +
                        '    <strong>🎓 ' + escapeHtml(stu.student_name) + '</strong>' +
                        '  </div>' +
                        '  <div class="ltt-rollcall-radios">' +
                        '    <label class="ltt-rc-pill text-emerald"><input type="radio" name="attendance_status[' + sid + ']" value="Present" ' + (st === 'Present' ? 'checked' : '') + '> ✓ Present</label>' +
                        '    <label class="ltt-rc-pill text-danger"><input type="radio" name="attendance_status[' + sid + ']" value="Absent" ' + (st === 'Absent' ? 'checked' : '') + '> ✕ Absent</label>' +
                        '    <label class="ltt-rc-pill text-cyan"><input type="radio" name="attendance_status[' + sid + ']" value="Late" ' + (st === 'Late' ? 'checked' : '') + '> ⏱ Late</label>' +
                        '  </div>' +
                        '</div>';
                }).join('');
            }
            showStep(1);
            modal.classList.remove('is-hidden');
        });

        rollList.addEventListener('change', function (e) {
            if (!e.target.classList.contains('js-rc-quick-cb')) return;
            var sid = e.target.getAttribute('data-stu');
            var val = e.target.checked ? 'Present' : 'Absent';
            var rad = rollList.querySelector('input[name="attendance_status[' + sid + ']"][value="' + val + '"]');
            if (rad) rad.checked = true;
        });

        wizForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var submitBtns = wizForm.querySelectorAll('.js-wiz-final-submit');
            submitBtns.forEach(function (b) { b.disabled = true; });

            fetch(wizForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(wizForm)
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.csrf_token) updateCsrfToken(res.csrf_token);
                if (res && res.ok) {
                    var sid        = res.session_id;
                    var card       = document.getElementById('js-sess-card-' + sid);
                    var badgeWrap  = document.getElementById('js-sess-status-badge-' + sid);
                    var actionWrap = document.getElementById('js-sess-action-wrap-' + sid);

                    if (card) card.classList.add('is-live-card');
                    if (badgeWrap) badgeWrap.innerHTML = '<span class="ltt-badge-live">🔴 LIVE IN PROGRESS</span>';
                    if (actionWrap) {
                        var rootEl   = document.getElementById('js-ltt-portal-root');
                        var endUrl   = rootEl ? rootEl.getAttribute('data-end-url') : '/timetable/end-class';
                        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        var csrfVal  = csrfMeta ? csrfMeta.getAttribute('content') : '';

                        actionWrap.innerHTML = '<div style="display:flex; gap:0.6rem; width:100%;">' +
                            '<a href="' + escapeHtml(res.meeting_link || '#') + '" target="_blank" class="btn-secondary" style="flex:1; justify-content:center;">🎥 Open Room ↗</a>' +
                            '<form action="' + escapeHtml(endUrl) + '" method="post" class="js-end-class-form" style="flex:1.2; margin:0;">' +
                            '  <input type="hidden" name="csrf_test_name" value="' + escapeHtml(csrfVal) + '">' +
                            '  <input type="hidden" name="session_id" value="' + parseInt(sid, 10) + '">' +
                            '  <button type="submit" class="ltt-btn-end-red">⏹ End Live Class</button>' +
                            '</form></div>';
                    }
                    modal.classList.add('is-hidden');
                    showLiveToast('🔴 Live Class Started!');
                }
            })
            .finally(function () {
                submitBtns.forEach(function (b) { b.disabled = false; });
            });
        });
    }

    function initAll() {
        initAdminHandlers();
        initTeacherWizardAndTabs();
        bootZeroLoadStudentLiveSignalWatcher();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
