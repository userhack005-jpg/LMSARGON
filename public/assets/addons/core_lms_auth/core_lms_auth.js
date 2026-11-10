(function () {
    'use strict';
    if (window.__CMS_CORE_LMS_AUTH_V70__) return;
    window.__CMS_CORE_LMS_AUTH_V70__ = true;

    function getBaseUrl() {
        var b = document.body ? document.body.getAttribute('data-base-url') : '';
        if (!b) b = window.location.origin + '/';
        return b.endsWith('/') ? b : b + '/';
    }

    function bindAdminHandlers() {
        var countrySel = document.getElementById('cla_country');
        var tzInput    = document.getElementById('cla_timezone');
        var leafSel    = document.getElementById('cla_leaf_node_id');
        var gradeInput = document.getElementById('cla_grade');

        if (countrySel && tzInput && !countrySel.dataset.bound) {
            countrySel.dataset.bound = '1';
            countrySel.addEventListener('change', function () {
                var opt = countrySel.options[countrySel.selectedIndex];
                if (opt && opt.getAttribute('data-tz')) {
                    tzInput.value = opt.getAttribute('data-tz');
                }
            });
        }

        if (leafSel && gradeInput && !leafSel.dataset.bound) {
            leafSel.dataset.bound = '1';
            leafSel.addEventListener('change', function () {
                var opt = leafSel.options[leafSel.selectedIndex];
                if (opt && opt.getAttribute('data-title')) {
                    gradeInput.value = opt.getAttribute('data-title');
                }
            });
        }

        var dirSearch = document.getElementById('js-cla-dir-search');
        if (dirSearch && !dirSearch.dataset.bound) {
            dirSearch.dataset.bound = '1';
            dirSearch.addEventListener('input', function () {
                var q = dirSearch.value.toLowerCase().trim();
                document.querySelectorAll('.js-cla-dir-row').forEach(function (row) {
                    var txt = row.getAttribute('data-search') || '';
                    row.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }
    }

    
    function mountGlobalStudentLiveClassPopupWatcher() {
        // Skip if already on /timetable/portal (which has its own watcher)
        if (document.getElementById('js-ltt-portal-root')) return;
        var seenLiveIds = {};
        var lastVer = null;

        function pollLiveSignal(isInit) {
            if (document.visibilityState && document.visibilityState !== 'visible') return;
            fetch(getBaseUrl() + 'assets/addons/lms_timetable/live_signal.json?t=' + Math.floor(Date.now() / 5000), { cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data || !data.sessions) return;
                    var v = parseInt(data.version_ts || 0, 10);
                    var changed = (lastVer !== null && v > lastVer);
                    lastVer = v;

                    Object.keys(data.sessions).forEach(function (k) {
                        var s = data.sessions[k];
                        if (!s || s.status !== 'live') return;
                        var sid = s.session_id;
                        if (isInit) {
                            seenLiveIds[sid] = true;
                            return;
                        }
                        if (changed && !seenLiveIds[sid]) {
                            seenLiveIds[sid] = true;
                            showGlobalLiveClassModal(s);
                        }
                    });
                })
                .catch(function () {});
        }

        function showGlobalLiveClassModal(s) {
            var existing = document.getElementById('js-global-live-class-modal');
            if (existing) existing.remove();
            var m = document.createElement('div');
            m.id = 'js-global-live-class-modal';
            m.style.cssText = 'position:fixed;inset:0;background:rgba(5,8,17,0.85);backdrop-filter:blur(6px);z-index:100099;display:flex;align-items:center;justify-content:center;padding:1rem;';
            m.innerHTML = '<div style="width:100%;max-width:460px;padding:1.65rem;border-radius:16px;background:var(--bg-surface,#111726);border:2px solid #10b981;color:var(--text-primary,#f8fafc);text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.6);">' +
                '<span style="display:inline-block;padding:0.25rem 0.75rem;border-radius:999px;background:rgba(244,63,94,0.2);color:#fb7185;border:1px solid #f43f5e;font-size:0.74rem;font-weight:800;margin-bottom:0.75rem;">🔴 YOUR LIVE CLASS HAS STARTED!</span>' +
                '<h2 style="margin:0.25rem 0 0.4rem;font-size:1.3rem;">' + (s.cohort_title || 'Live Classroom') + '</h2>' +
                '<p style="color:#06b6d4;font-size:0.9rem;margin:0 0 1.25rem;">👩‍🏫 Instructor: ' + (s.teacher_name || 'Teacher') + ' is live now!</p>' +
                '<div style="display:flex;gap:0.65rem;justify-content:center;flex-wrap:wrap;">' +
                '  <a href="' + (s.meeting_link || '/timetable/portal') + '" target="_blank" style="padding:0.7rem 1.4rem;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#04130d;font-weight:800;text-decoration:none;">🎥 Join Live Class Now ↗</a>' +
                '  <button type="button" id="js-close-global-live-modal" style="padding:0.7rem 1.1rem;border-radius:10px;background:transparent;border:1px solid #475569;color:inherit;cursor:pointer;font-weight:700;">Dismiss</button>' +
                '</div></div>';
            document.body.appendChild(m);
            document.getElementById('js-close-global-live-modal').onclick = function () { m.remove(); };
        }

        pollLiveSignal(true);
        setInterval(function () { pollLiveSignal(false); }, 6000);
    }

    function mountTopBarSiblingSwitcher() {
        var topBarRight = document.querySelector('.header-right');
        if (!topBarRight || document.getElementById('js-cla-topbar-switcher')) return;

        fetch(getBaseUrl() + 'addons/core-lms-auth/family-context-json', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data || data.status !== 'success' || !data.siblings || data.siblings.length === 0) {
                return;
            }

            var wrap = document.createElement('div');
            wrap.id = 'js-cla-topbar-switcher';
            wrap.className = 'cla-topbar-switcher';
            wrap.innerHTML = '<span>👨‍👩‍👧‍👦 Profile:</span>';

            var sel = document.createElement('select');
            sel.className = 'cla-topbar-select';

            var parentOpt = document.createElement('option');
            parentOpt.value = '0';
            parentOpt.textContent = '🏠 Parent Account';
            if (parseInt(data.active_student_id || 0, 10) === 0 || data.portal_view_mode !== 'student') {
                parentOpt.selected = true;
            }
            sel.appendChild(parentOpt);

            data.siblings.forEach(function (sib) {
                var opt = document.createElement('option');
                opt.value = String(sib.id);
                var sName = sib.full_name || sib.name || 'Child';
                opt.textContent = '🎓 ' + sName;
                if (data.portal_view_mode === 'student' && parseInt(sib.id, 10) === parseInt(data.active_student_id, 10)) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });

            sel.addEventListener('change', function () {
                var fd = new FormData();
                fd.append('student_id', sel.value);
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) fd.append('csrf_test_name', csrfMeta.getAttribute('content'));

                fetch(getBaseUrl() + 'addons/core-lms-auth/switch-student', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    window.location.href = (res && res.redirect_url) ? res.redirect_url : (getBaseUrl() + 'portal/family');
                })
                .catch(function () {
                    window.location.href = getBaseUrl() + 'portal/family';
                });
            });

            wrap.appendChild(sel);
            topBarRight.insertBefore(wrap, topBarRight.firstChild);
        })
        .catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindAdminHandlers(); mountTopBarSiblingSwitcher(); mountGlobalStudentLiveClassPopupWatcher(); });
    } else {
        bindAdminHandlers();
        mountTopBarSiblingSwitcher(); mountGlobalStudentLiveClassPopupWatcher();
    }
})();
