(function () {
    'use strict';
    if (window.__CMS_LMS_ONBOARDING_V110__) return;
    window.__CMS_LMS_ONBOARDING_V110__ = true;

    function syncChildRowDropdown(row) {
        if (!row) return;
        var sel     = row.querySelector('.js-existing-kid-select');
        var newBox  = row.querySelector('.js-new-kid-fields');
        var nameInp = row.querySelector('.js-new-kid-name');
        if (!sel || !newBox) return;

        var isNew = (String(sel.value) === '0');
        newBox.classList.toggle('is-hidden', !isNew);
        if (nameInp) {
            nameInp.required = isNew;
        }
    }

    function renumberChildRows(container) {
        if (!container) return;
        var rows = container.querySelectorAll('.js-onb-child-row');
        rows.forEach(function (row, idx) {
            var badge = row.querySelector('.js-child-badge-label');
            if (badge) {
                badge.textContent = '🎓 Child #' + (idx + 1);
            }
            row.querySelectorAll('input, select').forEach(function (inp) {
                var nm = inp.getAttribute('name') || '';
                if (nm.indexOf('children[') !== -1) {
                    inp.setAttribute('name', nm.replace(/children\[\d+\]/, 'children[' + idx + ']'));
                }
            });
        });
    }

    // 1. Document-Level Change Listener for Existing Child Dropdown
    document.addEventListener('change', function (e) {
        var sel = e.target.closest('.js-existing-kid-select');
        if (sel) {
            syncChildRowDropdown(sel.closest('.js-onb-child-row'));
        }
    });

    // 2. Document-Level Click Listener for `➕ Add Another Child` and `✕ Remove`
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.js-onb-add-child-btn');
        if (addBtn) {
            e.preventDefault();
            var form      = addBtn.closest('form');
            var container = form ? form.querySelector('.js-onb-children-container') : null;
            if (!container) return;

            var rows    = container.querySelectorAll('.js-onb-child-row');
            var nextIdx = rows.length;
            var clone   = rows[0].cloneNode(true);

            var badge     = clone.querySelector('.js-child-badge-label');
            var headerBar = badge ? badge.parentElement : clone;
            var oldRm     = headerBar.querySelector('.js-rm-kid-btn');
            if (oldRm) oldRm.remove();

            var rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'btn-action text-danger js-rm-kid-btn';
            rmBtn.textContent = '✕ Remove';
            rmBtn.style.fontSize = '0.75rem';
            headerBar.appendChild(rmBtn);

            clone.querySelectorAll('input, select').forEach(function (inp) {
                var nm = inp.getAttribute('name') || '';
                inp.setAttribute('name', nm.replace(/children\[\d+\]/, 'children[' + nextIdx + ']'));
                if (inp.tagName === 'INPUT' && inp.type === 'text') {
                    inp.value = '';
                }
                if (inp.classList.contains('js-existing-kid-select')) {
                    inp.value = '0';
                }
            });

            container.appendChild(clone);
            syncChildRowDropdown(clone);
            renumberChildRows(container);
            return;
        }

        var rmBtnClick = e.target.closest('.js-rm-kid-btn');
        if (rmBtnClick) {
            e.preventDefault();
            var row       = rmBtnClick.closest('.js-onb-child-row');
            var container = row ? row.parentElement : null;
            if (row && container && container.querySelectorAll('.js-onb-child-row').length > 1) {
                row.remove();
                renumberChildRows(container);
            }
        }
    });

    function initExistingRows() {
        document.querySelectorAll('.js-onb-child-row').forEach(syncChildRowDropdown);
        try {
            var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Kolkata';
            document.querySelectorAll('.js-onb-auto-tz').forEach(function (el) { el.value = tz; });
        } catch (err) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExistingRows);
    } else {
        initExistingRows();
    }
})();
