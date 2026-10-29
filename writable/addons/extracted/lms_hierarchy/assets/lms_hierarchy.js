(function () {
    'use strict';
    if (window.__CMS_LMS_HIERARCHY_V80__) return;
    window.__CMS_LMS_HIERARCHY_V80__ = true;

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
    window.CMS_ADDON_BLOCK_RENDERERS['course_hierarchy_explorer'] = function (blk, idx, isFooter, colIdx) {
        const d = blk.data ? blk.data : {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        const dMode = String(d.display_mode ?? 'drilldown_tree');
        const showDemo = String(d.show_demo_badge ?? '1');

        return `
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Top Badge</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge ?? 'ACADEMIC PROGRAMS')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Main Heading</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="heading" value="${escapeHtml(d.heading ?? 'Explore Our Courses & Learning Levels')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Display Mode</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="display_mode">
                        <option value="drilldown_tree" ${dMode === 'drilldown_tree' ? 'selected' : ''}>Step-by-Step Level Explorer</option>
                        <option value="selected_ids" ${dMode === 'selected_ids' ? 'selected' : ''}>Specific Course IDs (Comma-Separated)</option>
                        <option value="leaf_batches_only" ${dMode === 'leaf_batches_only' ? 'selected' : ''}>All Enrollable Courses Directly</option>
                    </select>
                </div>
            </div>
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Comma-Separated Course IDs (Optional)</span></label>
                    <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="selected_node_ids" value="${escapeHtml(d.selected_node_ids ?? '')}" placeholder="e.g. 1, 2, 3">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Subheading</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="subheading" value="${escapeHtml(d.subheading ?? '')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Show Demo Callout Badge?</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="show_demo_badge">
                        <option value="1" ${showDemo === '1' ? 'selected' : ''}>Yes</option>
                        <option value="0" ${showDemo === '0' ? 'selected' : ''}>No</option>
                    </select>
                </div>
            </div>
        `;
    };
    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'course_hierarchy_explorer' } }));

    function bootNodeBuilderRichText() {
        const editor = document.getElementById('js-lhr-rte-editor');
        const hidden = document.getElementById('js-lhr-desc-input');
        const form   = document.getElementById('js-lhr-node-form');
        if (!editor || !hidden) return;

        document.querySelectorAll('.js-lhr-rte-cmd').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const cmd = btn.getAttribute('data-cmd');
                const val = btn.getAttribute('data-val') || null;
                if (cmd === 'createLink') {
                    const url = window.prompt('Enter URL:', 'https://');
                    if (url) document.execCommand('createLink', false, url);
                } else {
                    document.execCommand(cmd, false, val);
                }
                hidden.value = editor.innerHTML;
            });
        });

        editor.addEventListener('input', function () {
            hidden.value = editor.innerHTML;
        });

        if (form) {
            form.addEventListener('submit', function () {
                hidden.value = editor.innerHTML;
            });
        }
    }

    function bootHierarchyExplorers() {
        document.querySelectorAll('.lhr-explorer-block').forEach(function (section) {
            if (section.dataset.mounted === '1') return;
            section.dataset.mounted = '1';

            const payloadEl     = section.querySelector('.js-lhr-tree-payload');
            const stageEl       = section.querySelector('.js-lhr-cards-stage');
            const crumbBar      = section.querySelector('.js-lhr-breadcrumb-bar');
            const detectUrl     = section.getAttribute('data-detect-country-url') || '';
            const courseViewUrl = (section.getAttribute('data-course-view-url') || '/courses/view').replace(/\/$/, '');
            const displayMode   = section.getAttribute('data-display-mode') || 'drilldown_tree';
            const selectedIdsStr= section.getAttribute('data-selected-ids') || '';
            const showDemo      = section.getAttribute('data-show-demo') !== '0';

            if (!payloadEl || !stageEl) return;

            let allNodes = [];
            try { allNodes = JSON.parse(payloadEl.textContent || '[]'); } catch (e) { allNodes = []; }

            const nodesMap = {};
            allNodes.forEach(function (n) { nodesMap[parseInt(n.id, 10)] = n; });

            const selectedIds = selectedIdsStr
                .split(',')
                .map(function (s) { return parseInt(s.trim(), 10); })
                .filter(function (n) { return !isNaN(n) && n > 0; });

            let resolvedCountryCode = null;
            let breadcrumbTrail     = [];

            function getInitialEntryNodes() {
                if (selectedIds.length > 0) {
                    return selectedIds.map(function (id) { return nodesMap[id]; }).filter(Boolean);
                }
                if (displayMode === 'leaf_batches_only') {
                    return allNodes.filter(function (n) { return parseInt(n.is_leaf_node, 10) === 1; });
                }
                return allNodes.filter(function (n) { return parseInt(n.parent_id, 10) === 0; });
            }

            function renderSingleCountryPriceHtml(node) {
                if (parseInt(node.is_leaf_node, 10) !== 1) {
                    const childCount = (node.children_ids || []).length;
                    return '<span class="mono-cell text-cyan">' + childCount + ' Level(s)</span>';
                }
                if (!resolvedCountryCode) {
                    return '<span class="lhr-price-slot is-detecting"></span>';
                }
                const pMap = node.pricing_by_country || {};
                const pRow = pMap[resolvedCountryCode] || null;
                if (!pRow || parseFloat(pRow.monthly_fee || 0) <= 0) {
                    return '<span class="mono-cell text-emerald">View Course Details</span>';
                }
                return '<span class="lhr-price-tag">' + escapeHtml(pRow.formatted_monthly) + '<small class="mono-cell muted-cell"> / mo</small></span>';
            }

            function renderBreadcrumbs() {
                if (!crumbBar) return;
                if (breadcrumbTrail.length === 0) {
                    crumbBar.classList.add('is-hidden');
                    crumbBar.innerHTML = '';
                    return;
                }
                crumbBar.classList.remove('is-hidden');
                let html = '<button type="button" class="btn-action js-lhr-crumb-step" data-step="-1">← Back</button>';
                breadcrumbTrail.forEach(function (cNode, idx) {
                    const isLast = (idx === breadcrumbTrail.length - 1);
                    html += '<span class="lhr-crumb-sep">›</span>' +
                        '<button type="button" class="btn-action ' + (isLast ? 'is-active' : '') + ' js-lhr-crumb-step" data-step="' + idx + '">' +
                        escapeHtml(cNode.title) +
                        '</button>';
                });
                crumbBar.innerHTML = html;
            }

            function renderCurrentStage() {
                renderBreadcrumbs();

                let visibleNodes = [];
                if (breadcrumbTrail.length === 0) {
                    visibleNodes = getInitialEntryNodes();
                } else {
                    const activeParent = breadcrumbTrail[breadcrumbTrail.length - 1];
                    const pid = parseInt(activeParent.id, 10);
                    visibleNodes = allNodes.filter(function (n) { return parseInt(n.parent_id, 10) === pid; });
                }

                if (visibleNodes.length === 0) {
                    stageEl.innerHTML = '<div class="panel-card" style="grid-column:1/-1; text-align:center;"><p class="muted-cell">No courses available in this category yet.</p></div>';
                    return;
                }

                stageEl.innerHTML = visibleNodes.map(function (node) {
                    const isLeaf = parseInt(node.is_leaf_node, 10) === 1;
                    const hasChildren = (node.children_ids || []).length > 0;
                    const thumbHtml = node.resolved_thumbnail
                        ? '<img src="' + escapeHtml(node.resolved_thumbnail) + '" alt="' + escapeHtml(node.title) + '" class="lhr-card-thumb" loading="lazy">'
                        : '';
                    const demoBadgeHtml = (showDemo && parseInt(node.allow_demo_booking, 10) === 1)
                        ? '<span class="role-badge text-cyan" style="align-self:flex-start;">🎯 ' + escapeHtml(node.demo_note || 'Free Demo Available') + '</span>'
                        : '';

                    return '<article class="lhr-node-card js-lhr-card-click" data-node-id="' + parseInt(node.id, 10) + '">' +
                        thumbHtml +
                        '<div class="lhr-card-body">' +
                        '  <h3 style="margin:0.15rem 0;">' + escapeHtml(node.title) + '</h3>' +
                        '  <p class="muted-cell" style="font-size:0.88rem; margin:0;">' + escapeHtml(node.short_summary || '') + '</p>' +
                        demoBadgeHtml +
                        '</div>' +
                        '<div class="lhr-card-footer">' +
                        '  <div>' + renderSingleCountryPriceHtml(node) + '</div>' +
                        '  <span class="btn-action text-emerald">' +
                        (isLeaf && !hasChildren ? 'View Details →' : 'Explore Levels →') +
                        '  </span>' +
                        '</div>' +
                        '</article>';
                }).join('');
            }

            stageEl.addEventListener('click', function (e) {
                const card = e.target.closest('.js-lhr-card-click');
                if (!card) return;
                const nid  = parseInt(card.getAttribute('data-node-id'), 10);
                const node = nodesMap[nid];
                if (!node) return;

                const hasChildren = (node.children_ids || []).length > 0;
                const isLeaf      = parseInt(node.is_leaf_node, 10) === 1;

                if (hasChildren && !isLeaf) {
                    breadcrumbTrail.push(node);
                    renderCurrentStage();
                } else {
                    window.location.href = courseViewUrl + '/' + nid;
                }
            });

            if (crumbBar) {
                crumbBar.addEventListener('click', function (e) {
                    const stepBtn = e.target.closest('.js-lhr-crumb-step');
                    if (!stepBtn) return;
                    const stepIdx = parseInt(stepBtn.getAttribute('data-step'), 10);
                    if (stepIdx < 0) {
                        breadcrumbTrail = [];
                    } else {
                        breadcrumbTrail = breadcrumbTrail.slice(0, stepIdx + 1);
                    }
                    renderCurrentStage();
                });
            }

            // Silently resolve visitor country in background (Zero debug banners!)
            let tz = '';
            try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) {}
            fetch(detectUrl + '?tz=' + encodeURIComponent(tz), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.country_code) {
                        resolvedCountryCode = String(res.country_code).toUpperCase();
                        renderCurrentStage();
                    }
                })
                .catch(function () {
                    resolvedCountryCode = 'IN';
                    renderCurrentStage();
                });

            renderCurrentStage();
        });
    }

    function initAll() {
        bootNodeBuilderRichText();
        bootHierarchyExplorers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
