(function () {
    'use strict';
    if (window.__CMS_LMS_HIERARCHY_V85__) return;
    window.__CMS_LMS_HIERARCHY_V85__ = true;

    var escapeHtml = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    function detectCountryZeroMs(serverDefault) {
        try {
            var cached = sessionStorage.getItem('lhr_visitor_country');
            if (cached) return cached;
            var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
            var tzMap = {
                'Asia/Kolkata': 'IN',
                'Asia/Calcutta': 'IN',
                'Asia/Dubai': 'AE',
                'Asia/Muscat': 'AE',
                'Europe/London': 'GB',
                'Africa/Lagos': 'NG',
                'America/New_York': 'US',
                'America/Chicago': 'US',
                'America/Los_Angeles': 'US'
            };
            if (tz && tzMap[tz]) {
                return tzMap[tz];
            }
        } catch (e) {}
        return serverDefault || 'IN';
    }

    function resolveNodePrice(node, countryCode) {
        var pMap = (node && node.pricing_by_country) ? node.pricing_by_country : {};
        return pMap[countryCode] || pMap['IN'] || Object.values(pMap)[0] || null;
    }

    function collectLeafProductsForIds(idList, nodesMap) {
        var out  = [];
        var seen = {};

        function addLeafOrChildren(nid) {
            var node = nodesMap[nid];
            if (!node) return;
            var isLeaf   = parseInt(node.is_leaf_node, 10) === 1;
            var children = node.children_ids || [];
            if (isLeaf && children.length === 0) {
                if (!seen[nid]) {
                    seen[nid] = true;
                    out.push(node);
                }
            } else {
                children.forEach(function (cid) { addLeafOrChildren(parseInt(cid, 10)); });
                if (children.length === 0 && !seen[nid]) {
                    seen[nid] = true;
                    out.push(node);
                }
            }
        }

        idList.forEach(function (id) { addLeafOrChildren(id); });
        return out;
    }

    /**
     * Renders Direct Product Cards in 3 Customizable Layouts:
     * - `split` (Default): 2-Part Split Box (Left Half Full Image, Right Half Title + Summary + Price + Compact Demo/Enroll Buttons)
     * - `grid`           : Vertical Card Grid (Top Image, Bottom Details & Buttons)
     * - `row`            : Compact Horizontal Row Strip
     */
    function renderDirectProductCardsHtml(products, countryCode, demoBaseUrl, enrollBaseUrl, viewBaseUrl, layoutMode) {
        if (!products || products.length === 0) {
            return '<div class="panel-card" style="text-align:center; padding:1.25rem;"><span class="muted-cell">No matching course product found.</span></div>';
        }

        var mode = String(layoutMode || 'split').toLowerCase();

        // LAYOUT 1: 2-PART SPLIT CARD (`split`)
        if (mode === 'split' || mode === 'auto' || mode === 'horizontal') {
            return '<div class="lhr-embed-split-list">' + products.map(function (node) {
                var cid       = parseInt(node.id, 10);
                var pRow      = resolveNodePrice(node, countryCode);
                var hasImg    = Boolean(node.resolved_thumbnail);
                var allowDemo = parseInt(node.allow_demo_booking, 10) === 1;
                var summary   = node.short_summary || '';
                if (!summary && node.description_html) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = node.description_html;
                    summary = (tmp.textContent || '').trim().slice(0, 140);
                }

                var mediaColHtml = hasImg
                    ? '<a href="' + escapeHtml(viewBaseUrl + '/' + cid) + '" class="lhr-split-media-col">' +
                      '  <img src="' + escapeHtml(node.resolved_thumbnail) + '" alt="' + escapeHtml(node.title) + '" loading="lazy" decoding="async">' +
                      '</a>'
                    : '';

                var priceBadgeHtml = (pRow && parseFloat(pRow.monthly_fee || 0) > 0)
                    ? '<span class="lhr-price-tag">' + escapeHtml(pRow.formatted_monthly) + ' <small class="mono-cell muted-cell">/ mo</small></span>'
                    : '<span class="lhr-badge lhr-badge-emerald">🎓 Open for Enrollment</span>';

                var demoBtn = allowDemo
                    ? '<a href="' + escapeHtml(demoBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-cyan lhr-btn-sm">🎯 ' +
                      escapeHtml(node.demo_note || 'Book Demo') + '</a>'
                    : '';

                var enrollBtn = '<a href="' + escapeHtml(enrollBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-emerald lhr-btn-sm">🎓 Enroll Now →</a>';

                return '<article class="lhr-split-product-card ' + (hasImg ? '' : 'no-image') + '">' +
                    mediaColHtml +
                    '<div class="lhr-split-content-col">' +
                    '  <div>' +
                    '    <div class="lhr-split-top-meta">' +
                    '      <span class="lhr-badge lhr-badge-emerald">🎓 COURSE</span>' +
                    priceBadgeHtml +
                    '    </div>' +
                    '    <h3 class="lhr-split-title"><a href="' + escapeHtml(viewBaseUrl + '/' + cid) + '">' + escapeHtml(node.title) + '</a></h3>' +
                    (summary ? '<p class="lhr-split-summary">' + escapeHtml(summary) + '</p>' : '') +
                    '  </div>' +
                    '  <div class="lhr-split-bottom-bar">' +
                    '    <div class="lhr-embed-cta-stack">' + demoBtn + enrollBtn + '</div>' +
                    '  </div>' +
                    '</div>' +
                    '</article>';
            }).join('') + '</div>';
        }

        // LAYOUT 3: COMPACT ROW STRIP (`row` or `list`)
        if (mode === 'row' || mode === 'list') {
            return '<div class="lhr-embed-row-list">' + products.map(function (node) {
                var cid       = parseInt(node.id, 10);
                var pRow      = resolveNodePrice(node, countryCode);
                var allowDemo = parseInt(node.allow_demo_booking, 10) === 1;
                var thumb     = node.resolved_thumbnail
                    ? '<img src="' + escapeHtml(node.resolved_thumbnail) + '" alt="' + escapeHtml(node.title) + '" class="lhr-row-thumb" loading="lazy" decoding="async">'
                    : '<div class="lhr-tree-icon" style="width:54px;height:54px;">🎓</div>';

                var priceHtml = (pRow && parseFloat(pRow.monthly_fee || 0) > 0)
                    ? '<div class="lhr-price-tag" style="font-size:1rem;">' + escapeHtml(pRow.formatted_monthly) + ' <small class="mono-cell muted-cell">/ mo</small></div>'
                    : '';

                var demoBtn = allowDemo
                    ? '<a href="' + escapeHtml(demoBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-cyan lhr-btn-sm">🎯 ' + escapeHtml(node.demo_note || 'Book Demo') + '</a>'
                    : '';
                var enrollBtn = '<a href="' + escapeHtml(enrollBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-emerald lhr-btn-sm">🎓 Enroll Now →</a>';

                return '<article class="lhr-row-product-card">' +
                    '<div class="lhr-row-product-left">' +
                    thumb +
                    '  <div style="min-width:0;">' +
                    '    <h3 style="margin:0; font-size:1.05rem;"><a href="' + escapeHtml(viewBaseUrl + '/' + cid) + '" style="color:inherit;text-decoration:none;">' + escapeHtml(node.title) + '</a></h3>' +
                    (node.short_summary ? '<p class="muted-cell" style="margin:0.15rem 0 0; font-size:0.82rem;">' + escapeHtml(node.short_summary) + '</p>' : '') +
                    priceHtml +
                    '  </div>' +
                    '</div>' +
                    '<div class="lhr-action-bar">' + demoBtn + enrollBtn + '</div>' +
                    '</article>';
            }).join('') + '</div>';
        }

        // LAYOUT 2: VERTICAL CARD GRID (`grid`)
        return '<div class="lhr-embed-grid">' + products.map(function (node) {
            var cid       = parseInt(node.id, 10);
            var pRow      = resolveNodePrice(node, countryCode);
            var allowDemo = parseInt(node.allow_demo_booking, 10) === 1;
            var thumb     = node.resolved_thumbnail
                ? '<a href="' + escapeHtml(viewBaseUrl + '/' + cid) + '" style="display:block;"><img src="' + escapeHtml(node.resolved_thumbnail) + '" alt="' + escapeHtml(node.title) + '" class="lhr-card-thumb" loading="lazy" decoding="async"></a>'
                : '';

            var priceHtml = (pRow && parseFloat(pRow.monthly_fee || 0) > 0)
                ? '<div class="lhr-product-embed-price-row">' +
                  '  <span class="mono-cell muted-cell">Course Fee</span>' +
                  '  <span class="lhr-price-tag">' + escapeHtml(pRow.formatted_monthly) + ' <small class="mono-cell muted-cell">/ mo</small></span>' +
                  '</div>'
                : '';

            var demoBtn = allowDemo
                ? '<a href="' + escapeHtml(demoBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-cyan lhr-btn-sm">🎯 ' +
                  escapeHtml(node.demo_note || 'Book Demo') + '</a>'
                : '';

            var enrollBtn = '<a href="' + escapeHtml(enrollBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-emerald lhr-btn-sm">🎓 Enroll Now →</a>';

            return '<article class="lhr-product-embed-card">' +
                thumb +
                '<div class="lhr-product-embed-body">' +
                '  <h3 style="margin:0; font-size:1.1rem;"><a href="' + escapeHtml(viewBaseUrl + '/' + cid) + '" style="color:inherit; text-decoration:none;">' + escapeHtml(node.title) + '</a></h3>' +
                (node.short_summary ? '<p class="muted-cell" style="margin:0; font-size:0.85rem; line-height:1.5;">' + escapeHtml(node.short_summary) + '</p>' : '') +
                priceHtml +
                '  <div class="lhr-embed-cta-stack">' + demoBtn + enrollBtn + '</div>' +
                '</div>' +
                '</article>';
        }).join('') + '</div>';
    }

    // 1. Page Builder Block Studio Renderer
    if (!window.CMS_ADDON_BLOCK_RENDERERS) {
        window.CMS_ADDON_BLOCK_RENDERERS = {};
    }
    window.CMS_ADDON_BLOCK_RENDERERS['course_hierarchy_explorer'] = function (blk, idx, isFooter, colIdx) {
        var d        = blk.data ? blk.data : {};
        var clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        var attr     = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        var dMode    = String(d.display_mode ?? 'drilldown_tree');
        var cLayout  = String(d.card_layout ?? 'split');
        var showDemo = String(d.show_demo_badge ?? '1');

        return '<div class="lhr-form-grid-3">' +
            '<div class="field-group"><label class="field-label"><span>Top Badge</span></label>' +
            '<input type="text" class="field-input ' + clsField + '" ' + attr + ' data-key="badge" value="' + escapeHtml(d.badge ?? 'ACADEMIC PROGRAMS') + '"></div>' +
            '<div class="field-group"><label class="field-label"><span>Main Heading</span></label>' +
            '<input type="text" class="field-input ' + clsField + '" ' + attr + ' data-key="heading" value="' + escapeHtml(d.heading ?? 'Explore Our Courses & Learning Levels') + '"></div>' +
            '<div class="field-group"><label class="field-label"><span>Display Mode</span></label>' +
            '<select class="field-select ' + clsField + '" ' + attr + ' data-key="display_mode">' +
            '<option value="drilldown_tree" ' + (dMode === 'drilldown_tree' ? 'selected' : '') + '>Step-by-Step Level Explorer</option>' +
            '<option value="direct_products" ' + (dMode === 'direct_products' ? 'selected' : '') + '>Direct Product Cards (No Header Clutter)</option>' +
            '<option value="selected_ids" ' + (dMode === 'selected_ids' ? 'selected' : '') + '>Specific Course IDs (Comma-Separated)</option>' +
            '<option value="leaf_batches_only" ' + (dMode === 'leaf_batches_only' ? 'selected' : '') + '>All Enrollable Courses Directly</option>' +
            '</select></div></div>' +
            '<div class="lhr-form-grid-3">' +
            '<div class="field-group"><label class="field-label"><span>Course ID(s) (e.g. 15 or 14,15,17)</span></label>' +
            '<input type="text" class="field-input mono-input ' + clsField + '" ' + attr + ' data-key="selected_node_ids" value="' + escapeHtml(d.selected_node_ids ?? '') + '" placeholder="e.g. 15 or 14,15"></div>' +
            '<div class="field-group"><label class="field-label"><span>Direct Card Style</span></label>' +
            '<select class="field-select ' + clsField + '" ' + attr + ' data-key="card_layout">' +
            '<option value="split" ' + (cLayout === 'split' ? 'selected' : '') + '>Split Box (Half Image + Half Content)</option>' +
            '<option value="grid" ' + (cLayout === 'grid' ? 'selected' : '') + '>Vertical Card Grid</option>' +
            '<option value="row" ' + (cLayout === 'row' ? 'selected' : '') + '>Compact Horizontal Row</option>' +
            '</select></div>' +
            '<div class="field-group"><label class="field-label"><span>Show Demo Button/Badge?</span></label>' +
            '<select class="field-select ' + clsField + '" ' + attr + ' data-key="show_demo_badge">' +
            '<option value="1" ' + (showDemo === '1' ? 'selected' : '') + '>Yes</option>' +
            '<option value="0" ' + (showDemo === '0' ? 'selected' : '') + '>No</option>' +
            '</select></div></div>';
    };
    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'course_hierarchy_explorer' } }));

    // 2. Mount Standalone HTML Embeds (`<div class="lhr-embed-courses" data-ids="15" data-layout="split"></div>`)
    function bootStandaloneHtmlEmbeds() {
        var embedEls = document.querySelectorAll('.lhr-embed-courses');
        if (embedEls.length === 0) return;

        var globalPayloadEl = document.getElementById('js-lhr-global-embed-payload') || document.querySelector('.js-lhr-tree-payload');
        var demoUrl   = '/demo/book';
        var enrollUrl = '/finance/enroll-checkout';
        var viewUrl   = '/courses/view';
        var defCode   = 'IN';

        if (globalPayloadEl) {
            demoUrl   = (globalPayloadEl.getAttribute('data-demo-url') || demoUrl).replace(/\/$/, '');
            enrollUrl = (globalPayloadEl.getAttribute('data-enroll-url') || enrollUrl).replace(/\/$/, '');
            viewUrl   = (globalPayloadEl.getAttribute('data-view-url') || viewUrl).replace(/\/$/, '');
            defCode   = globalPayloadEl.getAttribute('data-default-country') || 'IN';
        }

        var countryCode = detectCountryZeroMs(defCode);

        function hydrateWithNodes(allNodes) {
            var nodesMap = {};
            allNodes.forEach(function (n) { nodesMap[parseInt(n.id, 10)] = n; });

            embedEls.forEach(function (box) {
                if (box.dataset.hydrated === '1') return;
                box.dataset.hydrated = '1';
                var rawIds     = String(box.getAttribute('data-ids') || box.getAttribute('data-id') || '');
                var layoutMode = String(box.getAttribute('data-layout') || 'split');
                var idList     = rawIds.split(',').map(function (s) { return parseInt(s.trim(), 10); }).filter(function (n) { return !isNaN(n) && n > 0; });
                var products   = idList.length > 0
                    ? collectLeafProductsForIds(idList, nodesMap)
                    : allNodes.filter(function (n) { return parseInt(n.is_leaf_node, 10) === 1; });

                box.innerHTML = renderDirectProductCardsHtml(products, countryCode, demoUrl, enrollUrl, viewUrl, layoutMode);
            });
        }

        if (globalPayloadEl) {
            try {
                var parsed = JSON.parse(globalPayloadEl.textContent || '[]');
                hydrateWithNodes(parsed);
                return;
            } catch (e) {}
        }

        fetch('/courses/tree-json', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.nodes_by_id) {
                    hydrateWithNodes(Object.values(res.nodes_by_id));
                }
            })
            .catch(function () {});
    }

    // 3. Mount Interactive Course Explorers (`.lhr-explorer-block`)
    function bootHierarchyExplorers() {
        document.querySelectorAll('.lhr-explorer-block').forEach(function (section) {
            if (section.dataset.mounted === '1') return;
            section.dataset.mounted = '1';

            var payloadEl      = section.querySelector('.js-lhr-tree-payload');
            var stageEl        = section.querySelector('.js-lhr-cards-stage');
            var crumbBar       = section.querySelector('.js-lhr-breadcrumb-bar');
            var serverDefCode  = section.getAttribute('data-default-country') || 'IN';
            var demoBookUrl    = (section.getAttribute('data-demo-book-url') || '/demo/book').replace(/\/$/, '');
            var enrollBaseUrl  = (section.getAttribute('data-enroll-url') || '/finance/enroll-checkout').replace(/\/$/, '');
            var viewBaseUrl    = (section.getAttribute('data-course-view-url') || '/courses/view').replace(/\/$/, '');
            var displayMode    = section.getAttribute('data-display-mode') || 'drilldown_tree';
            var cardLayout     = section.getAttribute('data-card-layout') || 'split';
            var selectedIdsStr = section.getAttribute('data-selected-ids') || '';
            var showDemo       = section.getAttribute('data-show-demo') !== '0';

            if (!payloadEl || !stageEl) return;

            var allNodes = [];
            try { allNodes = JSON.parse(payloadEl.textContent || '[]'); } catch (e) { allNodes = []; }

            var nodesMap = {};
            allNodes.forEach(function (n) { nodesMap[parseInt(n.id, 10)] = n; });

            var selectedIds = selectedIdsStr
                .split(',')
                .map(function (s) { return parseInt(s.trim(), 10); })
                .filter(function (n) { return !isNaN(n) && n > 0; });

            var resolvedCountryCode = detectCountryZeroMs(serverDefCode);
            var breadcrumbTrail     = [];
            var activeDetailNode    = null;

            if (displayMode === 'direct_products') {
                var directList = selectedIds.length > 0
                    ? collectLeafProductsForIds(selectedIds, nodesMap)
                    : allNodes.filter(function (n) { return parseInt(n.is_leaf_node, 10) === 1; });
                stageEl.style.display = 'block';
                stageEl.innerHTML = renderDirectProductCardsHtml(directList, resolvedCountryCode, demoBookUrl, enrollBaseUrl, viewBaseUrl, cardLayout);
                return;
            }

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
                    var childCount = (node.children_ids || []).length;
                    return '<span class="mono-cell text-cyan">' + childCount + ' Level(s)</span>';
                }
                var pRow = resolveNodePrice(node, resolvedCountryCode);
                if (!pRow || parseFloat(pRow.monthly_fee || 0) <= 0) {
                    return '<span class="mono-cell text-emerald">View Course Details</span>';
                }
                return '<span class="lhr-price-tag">' + escapeHtml(pRow.formatted_monthly) + '<small class="mono-cell muted-cell"> / mo</small></span>';
            }

            function renderBreadcrumbs() {
                if (!crumbBar) return;
                if (breadcrumbTrail.length === 0 && !activeDetailNode) {
                    crumbBar.classList.add('is-hidden');
                    crumbBar.innerHTML = '';
                    return;
                }
                crumbBar.classList.remove('is-hidden');
                var html = '<button type="button" class="lhr-btn lhr-btn-outline lhr-btn-sm js-lhr-crumb-step" data-step="-1">← All Courses</button>';
                breadcrumbTrail.forEach(function (cNode, idx) {
                    var isLast = (idx === breadcrumbTrail.length - 1) && !activeDetailNode;
                    html += '<span class="lhr-crumb-sep">›</span>' +
                        '<button type="button" class="lhr-btn ' + (isLast ? 'lhr-btn-emerald' : 'lhr-btn-outline') + ' lhr-btn-sm js-lhr-crumb-step" data-step="' + idx + '">' +
                        escapeHtml(cNode.title) + '</button>';
                });
                if (activeDetailNode) {
                    html += '<span class="lhr-crumb-sep">›</span>' +
                        '<span class="mono-cell text-emerald"><strong>' + escapeHtml(activeDetailNode.title) + '</strong></span>';
                }
                crumbBar.innerHTML = html;
            }

            function renderInlineCourseDetail(node) {
                renderBreadcrumbs();
                var cid        = parseInt(node.id, 10);
                var pInfo      = resolveNodePrice(node, resolvedCountryCode);
                var priceBlock = (pInfo && parseFloat(pInfo.monthly_fee || 0) > 0)
                    ? '<div><span class="mono-label">COURSE FEE PLAN</span><div class="lhr-price-tag" style="font-size:1.45rem;">' +
                      escapeHtml(pInfo.formatted_monthly) + ' <small class="mono-cell muted-cell">/ month</small></div></div>'
                    : '';

                var demoBtn = parseInt(node.allow_demo_booking, 10) === 1
                    ? '<a href="' + escapeHtml(demoBookUrl + '/' + cid) + '" class="lhr-btn lhr-btn-cyan lhr-btn-lg">🎯 ' +
                      escapeHtml(node.demo_note || 'Book Free Demo') + '</a>'
                    : '';

                var enrollBtn = '<a href="' + escapeHtml(enrollBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-emerald lhr-btn-lg">🎓 Enroll in This Course →</a>';

                var thumbCol = node.resolved_thumbnail
                    ? '<div><img src="' + escapeHtml(node.resolved_thumbnail) + '" alt="' + escapeHtml(node.title) + '" loading="lazy" decoding="async" style="width:100%; border-radius:14px; object-fit:cover; max-height:350px; border:1px solid var(--lhr-border);"></div>'
                    : '';

                stageEl.innerHTML = '<div class="console-card" style="grid-column: 1 / -1;">' +
                    '<div class="lhr-course-detail-grid">' +
                    '  <div>' +
                    '    <span class="mono-label text-emerald">' + escapeHtml(node.breadcrumb_path || '') + '</span>' +
                    '    <h2 style="margin:0.35rem 0 0.75rem; font-size:clamp(1.45rem, 3vw, 2.1rem);">' + escapeHtml(node.title) + '</h2>' +
                    (node.short_summary ? '<p class="muted-cell" style="font-size:0.98rem; margin-bottom:1.1rem;">' + escapeHtml(node.short_summary) + '</p>' : '') +
                    '    <div class="cms-prose lhr-course-prose">' + (node.description_html || '') + '</div>' +
                    '    <div class="lhr-detail-cta-bar">' +
                    priceBlock +
                    '      <div class="lhr-embed-cta-stack">' + demoBtn + enrollBtn + '</div>' +
                    '    </div>' +
                    '  </div>' +
                    thumbCol +
                    '</div>' +
                    '</div>';
            }

            function renderCurrentStage() {
                if (activeDetailNode) {
                    renderInlineCourseDetail(activeDetailNode);
                    return;
                }
                renderBreadcrumbs();

                var visibleNodes = [];
                if (breadcrumbTrail.length === 0) {
                    visibleNodes = getInitialEntryNodes();
                } else {
                    var activeParent = breadcrumbTrail[breadcrumbTrail.length - 1];
                    var pid = parseInt(activeParent.id, 10);
                    visibleNodes = allNodes.filter(function (n) { return parseInt(n.parent_id, 10) === pid; });
                }

                if (visibleNodes.length === 0) {
                    stageEl.innerHTML = '<div class="panel-card" style="grid-column:1/-1; text-align:center;"><p class="muted-cell">No courses available in this category yet.</p></div>';
                    return;
                }

                stageEl.innerHTML = visibleNodes.map(function (node) {
                    var cid         = parseInt(node.id, 10);
                    var isLeaf      = parseInt(node.is_leaf_node, 10) === 1;
                    var hasChildren = (node.children_ids || []).length > 0;
                    var thumbHtml   = node.resolved_thumbnail
                        ? '<img src="' + escapeHtml(node.resolved_thumbnail) + '" alt="' + escapeHtml(node.title) + '" class="lhr-card-thumb" loading="lazy" decoding="async">'
                        : '';
                    var demoBadgeHtml = (showDemo && parseInt(node.allow_demo_booking, 10) === 1)
                        ? '<span class="lhr-badge lhr-badge-cyan" style="align-self:flex-start;">🎯 ' + escapeHtml(node.demo_note || 'Free Demo Available') + '</span>'
                        : '';

                    var directActionsHtml = '';
                    if (isLeaf && !hasChildren) {
                        var dBtn = parseInt(node.allow_demo_booking, 10) === 1
                            ? '<a href="' + escapeHtml(demoBookUrl + '/' + cid) + '" class="lhr-btn lhr-btn-cyan lhr-btn-sm js-lhr-stop-prop">🎯 Free Demo</a>'
                            : '';
                        var eBtn = '<a href="' + escapeHtml(enrollBaseUrl + '/' + cid) + '" class="lhr-btn lhr-btn-emerald lhr-btn-sm js-lhr-stop-prop">🎓 Enroll Now</a>';
                        directActionsHtml = '<div class="lhr-embed-cta-stack" style="margin-top:0.5rem;">' + dBtn + eBtn + '</div>';
                    }

                    return '<article class="lhr-node-card js-lhr-card-click" data-node-id="' + cid + '">' +
                        thumbHtml +
                        '<div class="lhr-card-body">' +
                        '  <h3 style="margin:0.15rem 0;">' + escapeHtml(node.title) + '</h3>' +
                        '  <p class="muted-cell" style="font-size:0.86rem; margin:0;">' + escapeHtml(node.short_summary || '') + '</p>' +
                        demoBadgeHtml +
                        directActionsHtml +
                        '</div>' +
                        '<div class="lhr-card-footer">' +
                        '  <div>' + renderSingleCountryPriceHtml(node) + '</div>' +
                        '  <span class="lhr-btn lhr-btn-outline lhr-btn-sm">' +
                        (isLeaf && !hasChildren ? 'View Details →' : 'Explore Levels →') +
                        '  </span>' +
                        '</div>' +
                        '</article>';
                }).join('');
            }

            stageEl.addEventListener('click', function (e) {
                if (e.target.closest('.js-lhr-stop-prop')) return;
                var card = e.target.closest('.js-lhr-card-click');
                if (!card) return;
                var nid  = parseInt(card.getAttribute('data-node-id'), 10);
                var node = nodesMap[nid];
                if (!node) return;

                var hasChildren = (node.children_ids || []).length > 0;
                var isLeaf      = parseInt(node.is_leaf_node, 10) === 1;

                if (hasChildren && !isLeaf) {
                    activeDetailNode = null;
                    breadcrumbTrail.push(node);
                    renderCurrentStage();
                } else {
                    activeDetailNode = node;
                    renderCurrentStage();
                }
            });

            if (crumbBar) {
                crumbBar.addEventListener('click', function (e) {
                    var stepBtn = e.target.closest('.js-lhr-crumb-step');
                    if (!stepBtn) return;
                    var stepIdx = parseInt(stepBtn.getAttribute('data-step'), 10);
                    activeDetailNode = null;
                    breadcrumbTrail  = stepIdx < 0 ? [] : breadcrumbTrail.slice(0, stepIdx + 1);
                    renderCurrentStage();
                });
            }

            renderCurrentStage();
        });
    }

    // 4. Admin Rich Text Editor, Clean JS Shortcode Builder, 0ms Table Search & Pagination
    function bootAdminTools() {
        var editor    = document.getElementById('js-lhr-rte-editor');
        var hidden    = document.getElementById('js-lhr-desc-input');
        var form      = document.getElementById('js-lhr-node-form');
        var srcToggle = document.getElementById('js-lhr-rte-source-toggle');
        if (editor && hidden) {
            var inSourceMode = false;
            document.querySelectorAll('.js-lhr-rte-cmd').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (inSourceMode) return;
                    editor.focus();
                    var cmd = btn.getAttribute('data-cmd');
                    var val = btn.getAttribute('data-val') || null;
                    if (cmd === 'createLink') {
                        var url = window.prompt('Enter Link URL:', 'https://');
                        if (url) document.execCommand('createLink', false, url);
                    } else {
                        document.execCommand(cmd, false, val);
                    }
                    hidden.value = editor.innerHTML;
                });
            });
            document.querySelectorAll('.js-lhr-rte-select').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    if (inSourceMode) return;
                    editor.focus();
                    document.execCommand(sel.getAttribute('data-cmd'), false, sel.value);
                    hidden.value = editor.innerHTML;
                });
            });
            document.querySelectorAll('.js-lhr-rte-color').forEach(function (colInp) {
                colInp.addEventListener('input', function () {
                    if (inSourceMode) return;
                    editor.focus();
                    document.execCommand(colInp.getAttribute('data-cmd'), false, colInp.value);
                    hidden.value = editor.innerHTML;
                });
            });
            document.querySelectorAll('.js-lhr-rte-custom').forEach(function (cBtn) {
                cBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (inSourceMode) return;
                    editor.focus();
                    var act = cBtn.getAttribute('data-action');
                    if (act === 'image') {
                        var imgUrl = window.prompt('Enter Image URL:', 'https://');
                        if (imgUrl) {
                            document.execCommand('insertHTML', false, '<img src="' + escapeHtml(imgUrl) + '" alt="Course Image" style="max-width:100%;border-radius:10px;margin:0.5rem 0;">');
                        }
                    } else if (act === 'table') {
                        var tblHtml = '<table style="width:100%;border-collapse:collapse;margin:0.75rem 0;border:1px solid #334155;">' +
                            '<thead><tr style="background:rgba(16,185,129,0.12);"><th style="padding:8px;border:1px solid #334155;text-align:left;">Module / Week</th><th style="padding:8px;border:1px solid #334155;text-align:left;">Topics Covered</th><th style="padding:8px;border:1px solid #334155;text-align:left;">Outcome</th></tr></thead>' +
                            '<tbody><tr><td style="padding:8px;border:1px solid #334155;">Module 1</td><td style="padding:8px;border:1px solid #334155;">Core Concepts & Practice</td><td style="padding:8px;border:1px solid #334155;">Speed Mastery</td></tr></tbody></table><p><br></p>';
                        document.execCommand('insertHTML', false, tblHtml);
                    } else if (act === 'callout') {
                        var boxHtml = '<div style="padding:1rem 1.2rem;border-left:4px solid #10b981;background:rgba(16,185,129,0.08);border-radius:8px;margin:0.75rem 0;"><strong>💡 Key Highlight:</strong> Write important course takeaway here...</div><p><br></p>';
                        document.execCommand('insertHTML', false, boxHtml);
                    }
                    hidden.value = editor.innerHTML;
                });
            });
            if (srcToggle) {
                srcToggle.addEventListener('click', function () {
                    inSourceMode = !inSourceMode;
                    if (inSourceMode) {
                        hidden.value = editor.innerHTML;
                        editor.classList.add('is-hidden');
                        hidden.classList.remove('is-hidden');
                        srcToggle.textContent = '👁 Visual Editor';
                    } else {
                        editor.innerHTML = hidden.value;
                        hidden.classList.add('is-hidden');
                        editor.classList.remove('is-hidden');
                        srcToggle.textContent = '</> HTML Source';
                    }
                });
            }
            editor.addEventListener('input', function () { hidden.value = editor.innerHTML; });
            hidden.addEventListener('input', function () { editor.innerHTML = hidden.value; });
            if (form) {
                form.addEventListener('submit', function () {
                    if (!inSourceMode) hidden.value = editor.innerHTML;
                });
            }
        }

        // Custom IDs + Layout Shortcode & HTML Embed Generator
        var customIdsInp    = document.getElementById('js-lhr-custom-ids');
        var customLayoutSel = document.getElementById('js-lhr-custom-layout');
        var scPrev          = document.getElementById('js-lhr-sc-preview');
        var htmlPrev        = document.getElementById('js-lhr-html-preview');
        var copyScBtn       = document.getElementById('js-lhr-copy-shortcode-btn');
        var copyHtmlBtn     = document.getElementById('js-lhr-copy-html-btn');

        function cleanIdsVal() {
            return customIdsInp ? customIdsInp.value.replace(/[^0-9,]/g, '').replace(/^,+|,+$/g, '') || '15' : '15';
        }
        function currentLayoutVal() {
            return customLayoutSel ? (customLayoutSel.value || 'split') : 'split';
        }
        function updateEmbedPreviews() {
            var ids = cleanIdsVal();
            var ly  = currentLayoutVal();
            if (scPrev) scPrev.textContent = '[lms_course ids="' + ids + '" layout="' + ly + '"]';
            if (htmlPrev) htmlPrev.textContent = '<div class="lhr-embed-courses" data-ids="' + ids + '" data-layout="' + ly + '"></div>';
        }
        if (customIdsInp) customIdsInp.addEventListener('input', updateEmbedPreviews);
        if (customLayoutSel) customLayoutSel.addEventListener('change', updateEmbedPreviews);

        function copyToClipboardWithFeedback(btn, text) {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(function () {
                var orig = btn.textContent;
                btn.textContent = '✓ Copied!';
                setTimeout(function () { btn.textContent = orig; }, 1400);
            });
        }
        if (copyScBtn) {
            copyScBtn.addEventListener('click', function () {
                copyToClipboardWithFeedback(copyScBtn, '[lms_course ids="' + cleanIdsVal() + '" layout="' + currentLayoutVal() + '"]');
            });
        }
        if (copyHtmlBtn) {
            copyHtmlBtn.addEventListener('click', function () {
                copyToClipboardWithFeedback(copyHtmlBtn, '<div class="lhr-embed-courses" data-ids="' + cleanIdsVal() + '" data-layout="' + currentLayoutVal() + '"></div>');
            });
        }

        // Row-level Copy Shortcode & Copy HTML Buttons (Constructed in JS so zero raw shortcode in HTML attributes!)
        document.addEventListener('click', function (e) {
            var scBtn = e.target.closest('.js-lhr-row-copy-sc');
            if (scBtn) {
                e.preventDefault();
                var nid = scBtn.getAttribute('data-node-id') || '15';
                var ly  = currentLayoutVal();
                copyToClipboardWithFeedback(scBtn, '[lms_course ids="' + nid + '" layout="' + ly + '"]');
                return;
            }
            var htBtn = e.target.closest('.js-lhr-row-copy-html');
            if (htBtn) {
                e.preventDefault();
                var nid2 = htBtn.getAttribute('data-node-id') || '15';
                var ly2  = currentLayoutVal();
                copyToClipboardWithFeedback(htBtn, '<div class="lhr-embed-courses" data-ids="' + nid2 + '" data-layout="' + ly2 + '"></div>');
            }
        });

        // Reusable 0ms Table Search + Filter + Pagination + CSV Setup
        function initSmartTable(cfg) {
            var rows = Array.prototype.slice.call(document.querySelectorAll(cfg.rowSelector));
            if (rows.length === 0) return;

            var searchInp  = document.getElementById(cfg.searchId);
            var pageSizeEl = document.getElementById(cfg.pageSizeId);
            var pagerEl    = document.getElementById(cfg.pagerId);
            var countEl    = document.getElementById(cfg.countId);
            var tabsBar    = cfg.tabsId ? document.getElementById(cfg.tabsId) : null;
            var csvBtn     = document.getElementById(cfg.csvBtnId);

            var activeFilter = 'all';
            var currentPage  = 1;

            rows.forEach(function (r) {
                r._searchText = (r.textContent || '').toLowerCase();
            });

            function renderTablePage() {
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
                            btns += '<button type="button" class="lhr-btn ' + (p === currentPage ? 'lhr-btn-emerald' : 'lhr-btn-outline') + ' lhr-btn-sm js-pg-num" data-page="' + p + '">' + p + '</button>';
                        }
                        pagerEl.innerHTML = '<span class="muted-cell">Showing ' + (startIdx + 1) + '–' + Math.min(endIdx, total) + ' of ' + total + '</span>' +
                            '<div class="lhr-action-bar">' + btns + '</div>';
                    }
                }
            }

            if (searchInp) searchInp.addEventListener('input', function () { currentPage = 1; renderTablePage(); });
            if (pageSizeEl) pageSizeEl.addEventListener('change', function () { currentPage = 1; renderTablePage(); });
            if (pagerEl) {
                pagerEl.addEventListener('click', function (e) {
                    var b = e.target.closest('.js-pg-num');
                    if (!b) return;
                    currentPage = parseInt(b.getAttribute('data-page'), 10) || 1;
                    renderTablePage();
                });
            }
            if (tabsBar) {
                tabsBar.addEventListener('click', function (e) {
                    var tb = e.target.closest('.lhr-filter-tab');
                    if (!tb) return;
                    tabsBar.querySelectorAll('.lhr-filter-tab').forEach(function (x) { x.classList.remove('is-active'); });
                    tb.classList.add('is-active');
                    activeFilter = tb.getAttribute('data-type') || 'all';
                    currentPage  = 1;
                    renderTablePage();
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

            renderTablePage();
        }

        initSmartTable({
            rowSelector: '.js-lhr-tree-row',
            searchId: 'js-lhr-tree-search',
            pageSizeId: 'js-lhr-tree-pagesize',
            pagerId: 'js-lhr-tree-pager',
            countId: 'js-lhr-tree-cnt',
            tabsId: 'js-lhr-tree-tabs',
            csvBtnId: 'js-lhr-tree-csv',
            csvHeaders: ['ID', 'Title', 'Breadcrumb Path', 'Type'],
            csvCols: ['data-csv-id', 'data-csv-title', 'data-csv-path', 'data-csv-type'],
            csvFilename: 'lms_courses_hierarchy'
        });

        initSmartTable({
            rowSelector: '.js-lhr-enr-row',
            searchId: 'js-lhr-enr-search',
            pageSizeId: 'js-lhr-enr-pagesize',
            pagerId: 'js-lhr-enr-pager',
            countId: 'js-lhr-enr-cnt',
            csvBtnId: 'js-lhr-enr-csv',
            csvHeaders: ['Student', 'Email', 'Course', 'Fee Plan'],
            csvCols: ['data-csv-student', 'data-csv-email', 'data-csv-course', 'data-csv-fee'],
            csvFilename: 'lms_student_enrollments'
        });
    }

    function initAll() {
        bootAdminTools();
        bootHierarchyExplorers();
        bootStandaloneHtmlEmbeds();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
