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
        document.addEventListener('DOMContentLoaded', function () { bindAdminHandlers(); mountTopBarSiblingSwitcher(); });
    } else {
        bindAdminHandlers();
        mountTopBarSiblingSwitcher();
    }
})();
