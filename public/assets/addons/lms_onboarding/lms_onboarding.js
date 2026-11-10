(function () {
    'use strict';
    if (window.__CMS_LMS_ONBOARDING_V121__) return;
    window.__CMS_LMS_ONBOARDING_V121__ = true;

    function syncChildRowDropdown(row) {
        if (!row) return;
        var sel       = row.querySelector('.js-existing-kid-select');
        var courseEl  = row.querySelector('.js-row-course-select');
        var newBox    = row.querySelector('.js-new-kid-fields');
        var nameInp   = row.querySelector('.js-new-kid-name');
        var activeCid = courseEl ? String(courseEl.value || '0') : '0';

        if (sel) {
            Array.from(sel.options).forEach(function (opt) {
                var val = String(opt.value || '0');
                if (val === '0') return;
                var baseLbl    = opt.getAttribute('data-base-label') || opt.textContent;
                var appliedCsv = ',' + (opt.getAttribute('data-applied-courses') || '') + ',';
                var isApplied  = (activeCid !== '0' && appliedCsv.indexOf(',' + activeCid + ',') !== -1);
                opt.disabled    = isApplied;
                opt.textContent = isApplied ? (baseLbl + ' — ✓ Already Applied for this Course Demo') : baseLbl;
            });

            if (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].disabled) {
                var fallbackVal = '0';
                Array.from(sel.options).forEach(function (opt) {
                    if (!opt.disabled && fallbackVal === '0' && String(opt.value) !== '0') {
                        fallbackVal = opt.value;
                    }
                });
                sel.value = fallbackVal;
            }
        }

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

    document.addEventListener('change', function (e) {
        var row = e.target.closest('.js-onb-child-row');
        if (row && (e.target.classList.contains('js-existing-kid-select') || e.target.classList.contains('js-row-course-select'))) {
            syncChildRowDropdown(row);
        }
    });

    document.addEventListener('click', function (e) {
        var copyBtn = e.target.closest('.js-onb-copy-link');
        if (copyBtn) {
            e.preventDefault();
            var link = copyBtn.getAttribute('data-link') || '';
            if (link && navigator.clipboard) {
                navigator.clipboard.writeText(link).then(function () {
                    var orig = copyBtn.textContent;
                    copyBtn.textContent = '✓ Copied!';
                    setTimeout(function () { copyBtn.textContent = orig; }, 1400);
                });
            }
            return;
        }

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
            rmBtn.className = 'onb-btn onb-btn-rose onb-btn-sm js-rm-kid-btn';
            rmBtn.textContent = '✕ Remove';
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
            return;
        }

        var openEndBtn = e.target.closest('.js-open-end-demo-panel');
        if (openEndBtn) {
            e.preventDefault();
            var slotId = openEndBtn.getAttribute('data-slot-id');
            var panel  = document.getElementById('js-end-demo-panel-' + slotId);
            if (panel) {
                panel.classList.remove('is-hidden');
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            return;
        }

        var closeEndBtn = e.target.closest('.js-close-end-demo-panel');
        if (closeEndBtn) {
            e.preventDefault();
            var sId   = closeEndBtn.getAttribute('data-slot-id');
            var pnlEl = document.getElementById('js-end-demo-panel-' + sId);
            if (pnlEl) {
                pnlEl.classList.add('is-hidden');
            }
        }
    });

    function initSmartTable(cfg) {
        var rows = Array.prototype.slice.call(document.querySelectorAll(cfg.rowSelector));
        if (rows.length === 0) return;

        var searchInp  = document.getElementById(cfg.searchId);
        var pageSizeEl = document.getElementById(cfg.pageSizeId);
        var pagerEl    = document.getElementById(cfg.pagerId);
        var countEl    = document.getElementById(cfg.countId);
        var tabsBar    = cfg.tabsId ? document.getElementById(cfg.tabsId) : null;
        var csvBtn     = cfg.csvBtnId ? document.getElementById(cfg.csvBtnId) : null;

        var activeFilter = 'all';
        var currentPage  = 1;

        rows.forEach(function (r) {
            r._searchText = (r.textContent || '').toLowerCase();
        });

        function renderPage() {
            var q        = searchInp ? searchInp.value.trim().toLowerCase() : '';
            var pageSize = pageSizeEl ? parseInt(pageSizeEl.value, 10) || 25 : 25;
            var matched  = [];

            for (var i = 0; i < rows.length; i++) {
                var r    = rows[i];
                var rTyp = r.getAttribute('data-type') || 'all';
                var ok   = true;
                if (activeFilter !== 'all' && rTyp !== activeFilter) ok = false;
                if (ok && q !== '' && r._searchText.indexOf(q) === -1) ok = false;
                if (ok) matched.push(r);
                else r.style.display = 'none';
            }

            var total      = matched.length;
            var totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (currentPage > totalPages) currentPage = totalPages;
            var startIdx   = (currentPage - 1) * pageSize;
            var endIdx     = startIdx + pageSize;

            for (var j = 0; j < matched.length; j++) {
                matched[j].style.display = (j >= startIdx && j < endIdx) ? '' : 'none';
            }

            if (countEl) countEl.textContent = String(total);
            if (pagerEl) {
                if (total <= pageSize) {
                    pagerEl.innerHTML = '<span class="muted-cell">Showing all ' + total + ' record(s)</span><span></span>';
                } else {
                    var btns = '';
                    for (var p = 1; p <= totalPages; p++) {
                        btns += '<button type="button" class="onb-btn ' + (p === currentPage ? 'onb-btn-emerald' : 'onb-btn-outline') + ' onb-btn-sm js-onb-pg" data-page="' + p + '">' + p + '</button>';
                    }
                    pagerEl.innerHTML = '<span class="muted-cell">Showing ' + (startIdx + 1) + '–' + Math.min(endIdx, total) + ' of ' + total + '</span>' +
                        '<div class="onb-action-bar">' + btns + '</div>';
                }
            }
        }

        if (searchInp) searchInp.addEventListener('input', function () { currentPage = 1; renderPage(); });
        if (pageSizeEl) pageSizeEl.addEventListener('change', function () { currentPage = 1; renderPage(); });
        if (pagerEl) {
            pagerEl.addEventListener('click', function (e) {
                var b = e.target.closest('.js-onb-pg');
                if (!b) return;
                currentPage = parseInt(b.getAttribute('data-page'), 10) || 1;
                renderPage();
            });
        }
        if (tabsBar) {
            tabsBar.addEventListener('click', function (e) {
                var tb = e.target.closest('.onb-filter-tab');
                if (!tb) return;
                tabsBar.querySelectorAll('.onb-filter-tab').forEach(function (x) { x.classList.remove('is-active'); });
                tb.classList.add('is-active');
                activeFilter = tb.getAttribute('data-type') || 'all';
                currentPage  = 1;
                renderPage();
            });
        }
        if (csvBtn && cfg.csvCols) {
            csvBtn.addEventListener('click', function () {
                var csvRows = [cfg.csvHeaders];
                rows.forEach(function (r) {
                    if (r.style.display === 'none') return;
                    csvRows.push(cfg.csvCols.map(function (attr) { return r.getAttribute(attr) || ''; }));
                });
                var csvStr = csvRows.map(function (cols) {
                    return cols.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(',');
                }).join('\r\n');
                var blob = new Blob(['\uFEFF' + csvStr], { type: 'text/csv;charset=utf-8;' });
                var url  = URL.createObjectURL(blob);
                var a    = document.createElement('a');
                a.href = url;
                a.download = cfg.csvFilename + '_' + new Date().toISOString().slice(0, 10) + '.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }

        renderPage();
    }

    function initAll() {
        document.querySelectorAll('.js-onb-child-row').forEach(syncChildRowDropdown);

    // Instant AJAX Email Existence Check on Demo Booking Form
    document.querySelectorAll('input[name="parent_email"]').forEach(function (emailInp) {
        if (emailInp.type === 'hidden' || emailInp.dataset.ajaxBound === '1') return;
        emailInp.dataset.ajaxBound = '1';

        var warnBox = document.createElement('div');
        warnBox.className = 'onb-alert onb-alert-error is-hidden';
        warnBox.style.marginTop = '0.45rem';
        emailInp.parentElement.appendChild(warnBox);

        function checkEmailNow() {
            var val = emailInp.value.trim();
            if (!val || val.indexOf('@') === -1) {
                warnBox.classList.add('is-hidden');
                return;
            }
            fetch('/demo/check-email-json?email=' + encodeURIComponent(val), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var form = emailInp.closest('form');
                var submitBtn = form ? form.querySelector('.js-onb-submit-btn') : null;
                if (res && res.exists) {
                    warnBox.innerHTML = '⚠️ <strong>Account Already Exists:</strong> This email ID is already registered. ' +
                        '<a href="' + (res.login_url || '/login') + '" class="onb-btn onb-btn-emerald onb-btn-sm" style="margin-left:0.5rem;">🔑 Click Here to Login →</a>';
                    warnBox.classList.remove('is-hidden');
                    if (submitBtn) submitBtn.disabled = true;
                } else {
                    warnBox.classList.add('is-hidden');
                    if (submitBtn) submitBtn.disabled = false;
                }
            })
            .catch(function () {});
        }

        emailInp.addEventListener('blur', checkEmailNow);
        emailInp.addEventListener('change', checkEmailNow);
    });

        try {
            var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Kolkata';
            document.querySelectorAll('.js-onb-auto-tz').forEach(function (el) { el.value = tz; });
        } catch (err) {}

        initSmartTable({
            rowSelector: '.js-onb-lead-row',
            searchId: 'js-onb-leads-search',
            pageSizeId: 'js-onb-leads-pagesize',
            pagerId: 'js-onb-leads-pager',
            countId: 'js-onb-leads-cnt',
            tabsId: 'js-onb-leads-tabs',
            csvBtnId: 'js-onb-leads-csv',
            csvHeaders: ['Child', 'Parent', 'Email', 'Phone', 'Course', 'Slot / Timing', 'Status', 'Teacher Remark'],
            csvCols: ['data-csv-child', 'data-csv-parent', 'data-csv-email', 'data-csv-phone', 'data-csv-course', 'data-csv-slot', 'data-csv-status', 'data-csv-remark'],
            csvFilename: 'lms_demo_bookings'
        });

        initSmartTable({
            rowSelector: '.js-onb-slot-row',
            searchId: 'js-onb-slots-search',
            pageSizeId: 'js-onb-slots-pagesize',
            pagerId: 'js-onb-slots-pager',
            countId: 'js-onb-slots-cnt',
            tabsId: 'js-onb-slots-tabs'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
