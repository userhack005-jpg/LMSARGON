(function () {
    'use strict';
    if (window.__CMS_RBAC_V75_BOOTED__) return;
    window.__CMS_RBAC_V75_BOOTED__ = true;

    function bootRbacAdmin() {
        // 1. Toggle Collapsible Role Builder Drawer
        var toggleBuilderBtn = document.getElementById('js-rbac-toggle-builder');
        var builderDrawer    = document.getElementById('js-rbac-builder-drawer');
        if (toggleBuilderBtn && builderDrawer) {
            toggleBuilderBtn.addEventListener('click', function () {
                builderDrawer.classList.toggle('is-hidden');
            });
        }

        // 2. Permission Matrix: 0ms Search + State Filter (`all`, `enabled`, `disabled`)
        var permSearchInp = document.getElementById('js-rbac-perm-search');
        var permFilterBar = document.getElementById('js-rbac-perm-filter-tabs');
        var permCards     = Array.prototype.slice.call(document.querySelectorAll('.js-rbac-perm-card'));
        var permGroups    = Array.prototype.slice.call(document.querySelectorAll('.js-rbac-module-group'));
        var activePermSt  = 'all';

        function filterPermissionsZeroMs() {
            var q = permSearchInp ? permSearchInp.value.toLowerCase().trim() : '';
            permCards.forEach(function (card) {
                var txt = card.getAttribute('data-search') || '';
                var cb  = card.querySelector('.js-rbac-perm-cb');
                var chk = cb ? cb.checked : false;
                var ok  = true;

                if (q !== '' && txt.indexOf(q) === -1) ok = false;
                if (ok && activePermSt === 'enabled' && !chk) ok = false;
                if (ok && activePermSt === 'disabled' && chk) ok = false;

                card.style.display = ok ? '' : 'none';
            });

            // Hide empty module boxes automatically when filtering
            permGroups.forEach(function (grp) {
                var visibleInGrp = grp.querySelectorAll('.js-rbac-perm-card:not([style*="display: none"])').length;
                grp.style.display = visibleInGrp > 0 ? '' : 'none';
            });
        }

        if (permSearchInp) {
            permSearchInp.addEventListener('input', filterPermissionsZeroMs);
        }
        if (permFilterBar) {
            permFilterBar.addEventListener('click', function (e) {
                var tab = e.target.closest('.rbac-filter-tab');
                if (!tab) return;
                permFilterBar.querySelectorAll('.rbac-filter-tab').forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                activePermSt = tab.getAttribute('data-state') || 'all';
                filterPermissionsZeroMs();
            });
        }

        var checkAllBtn = document.getElementById('js-rbac-check-all');
        if (checkAllBtn) {
            checkAllBtn.addEventListener('click', function () {
                permCards.forEach(function (card) {
                    if (card.style.display === 'none') return;
                    var cb = card.querySelector('.js-rbac-perm-cb:not(:disabled)');
                    if (cb) cb.checked = true;
                });
                filterPermissionsZeroMs();
            });
        }

        var uncheckAllBtn = document.getElementById('js-rbac-uncheck-all');
        if (uncheckAllBtn) {
            uncheckAllBtn.addEventListener('click', function () {
                permCards.forEach(function (card) {
                    if (card.style.display === 'none') return;
                    var cb = card.querySelector('.js-rbac-perm-cb:not(:disabled)');
                    if (cb) cb.checked = false;
                });
                filterPermissionsZeroMs();
            });
        }

        document.querySelectorAll('.js-toggle-group').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var grp = btn.closest('.js-rbac-module-group');
                if (!grp) return;
                var boxes = grp.querySelectorAll('.js-rbac-perm-cb:not(:disabled)');
                var allChecked = true;
                boxes.forEach(function (cb) { if (!cb.checked) allChecked = false; });
                boxes.forEach(function (cb) { cb.checked = !allChecked; });
                filterPermissionsZeroMs();
            });
        });

        // 3. Menu Renamer Table: 0ms Instant Search & Filter (`all`, `main`, `sub`, `hidden`)
        var menuSearchInp = document.getElementById('js-rbac-menu-search');
        var menuTabsBar   = document.getElementById('js-rbac-menu-tabs');
        var menuRows      = Array.prototype.slice.call(document.querySelectorAll('.js-rbac-menu-row'));
        var activeMenuFlt = 'all';

        menuRows.forEach(function (r) {
            r._searchText = (r.textContent || '').toLowerCase();
        });

        function filterMenuRowsZeroMs() {
            var q = menuSearchInp ? menuSearchInp.value.toLowerCase().trim() : '';
            menuRows.forEach(function (r) {
                var kind   = r.getAttribute('data-kind') || 'sub';
                var hideCb = r.querySelector('.js-rbac-hide-cb');
                var isHid  = hideCb ? hideCb.checked : (r.getAttribute('data-hidden') === '1');
                var inpEl  = r.querySelector('input[type="text"]');
                var custom = inpEl ? inpEl.value.toLowerCase() : '';
                var ok     = true;

                if (activeMenuFlt === 'main' && kind !== 'main') ok = false;
                if (activeMenuFlt === 'sub' && kind !== 'sub') ok = false;
                if (activeMenuFlt === 'hidden' && !isHid) ok = false;
                if (ok && q !== '' && r._searchText.indexOf(q) === -1 && custom.indexOf(q) === -1) ok = false;

                r.style.display = ok ? '' : 'none';
            });
        }

        if (menuSearchInp) {
            menuSearchInp.addEventListener('input', filterMenuRowsZeroMs);
        }
        if (menuTabsBar) {
            menuTabsBar.addEventListener('click', function (e) {
                var tab = e.target.closest('.rbac-filter-tab');
                if (!tab) return;
                menuTabsBar.querySelectorAll('.rbac-filter-tab').forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                activeMenuFlt = tab.getAttribute('data-filter') || 'all';
                filterMenuRowsZeroMs();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootRbacAdmin);
    } else {
        bootRbacAdmin();
    }
})();
