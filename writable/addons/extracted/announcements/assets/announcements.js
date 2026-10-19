/**
 * DYNAMIC ANNOUNCEMENTS EXTERNAL SCRIPT (assets/announcements.js)
 */
(function () {
    'use strict';

    if (window.__ANN_ADDON_LOADED__) return;
    window.__ANN_ADDON_LOADED__ = true;

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

    window.CMS_ADDON_BLOCK_RENDERERS['announcement_bar'] = function (blk, idx, isFooter, colIdx) {
        const d = blk.data ? blk.data : {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        const filterMode = d.filter_mode ? d.filter_mode : 'category';
        const scrollMode = d.scroll_mode ? d.scroll_mode : 'rtl';

        return `
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Pinned Badge Text</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="badge_text" value="${escapeHtml(d.badge_text ?? 'NEW')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Filter Mode</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="filter_mode">
                        <option value="category" ${filterMode === 'category' ? 'selected' : ''}>By Category Name</option>
                        <option value="single_id" ${filterMode === 'single_id' ? 'selected' : ''}>Single Announcement ID #</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Category Name (or ID #)</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="category" value="${escapeHtml(d.category ? d.category : 'all')}" placeholder="all, Admissions, or ID #">
                </div>
            </div>
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Scroll Direction</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="scroll_mode">
                        <option value="rtl" ${scrollMode === 'rtl' ? 'selected' : ''}>← Right-to-Left Marquee (RTL)</option>
                        <option value="ltr" ${scrollMode === 'ltr' ? 'selected' : ''}>→ Left-to-Right Marquee (LTR)</option>
                        <option value="static" ${scrollMode === 'static' ? 'selected' : ''}>Static Centered Text</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Scroll Speed (Seconds)</span></label>
                    <input type="number" class="field-input mono-input ${clsField}" ${attr} data-key="scroll_speed" value="${escapeHtml(d.scroll_speed ? d.scroll_speed : '22')}" min="8" max="120">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Bar & Badge Colors</span></label>
                    <div class="color-picker-row">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="bg_color" value="${escapeHtml(d.bg_color ? d.bg_color : '#10b981')}" title="Bar Background">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="text_color" value="${escapeHtml(d.text_color ? d.text_color : '#04130d')}" title="Bar Text Color">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="badge_bg" value="${escapeHtml(d.badge_bg ? d.badge_bg : '#04130d')}" title="Badge Background">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="badge_color" value="${escapeHtml(d.badge_color ? d.badge_color : '#10b981')}" title="Badge Text Color">
                    </div>
                </div>
            </div>
            <span class="field-hint">Managed by <strong>Dynamic Announcements Add-On</strong> (/addons/announcements).</span>
        `;
    };

    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'announcement_bar' } }));

    const applyAnnouncementCustomVars = function () {
        document.querySelectorAll('.cms-announcement-bar').forEach(function (bar) {
            const bg = bar.getAttribute('data-bg-color');
            const txt = bar.getAttribute('data-text-color');
            const bBg = bar.getAttribute('data-badge-bg');
            const bCol = bar.getAttribute('data-badge-color');
            const spd = bar.getAttribute('data-scroll-speed');
            if (bg) bar.style.setProperty('--ann-bg', bg);
            if (txt) bar.style.setProperty('--ann-text', txt);
            if (bBg) bar.style.setProperty('--ann-badge-bg', bBg);
            if (bCol) bar.style.setProperty('--ann-badge-color', bCol);
            if (spd) bar.style.setProperty('--ann-speed', spd + 's');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyAnnouncementCustomVars);
    } else {
        applyAnnouncementCustomVars();
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.js-edit-ann-row');
        if (editBtn) {
            e.preventDefault();
            const fId = document.getElementById('ann_id');
            const fCat = document.getElementById('ann_category');
            const fMsg = document.getElementById('ann_message');
            const fText = document.getElementById('ann_link_text');
            const fUrl = document.getElementById('ann_link_url');
            const heading = document.getElementById('ann-form-heading');
            const subBtn = document.getElementById('ann-submit-btn');
            const cancelBtn = document.getElementById('ann-cancel-edit');

            if (fId) fId.value = editBtn.getAttribute('data-id') ?? '0';
            if (fCat) fCat.value = editBtn.getAttribute('data-category') ?? 'General';
            if (fMsg) fMsg.value = editBtn.getAttribute('data-message') ?? '';
            if (fText) fText.value = editBtn.getAttribute('data-link-text') ?? '';
            if (fUrl) fUrl.value = editBtn.getAttribute('data-link-url') ?? '';
            if (heading) heading.textContent = 'EDITING ANNOUNCEMENT #' + editBtn.getAttribute('data-id');
            if (subBtn) subBtn.textContent = 'Update Announcement';
            if (cancelBtn) cancelBtn.classList.remove('is-hidden');

            if (fMsg) fMsg.focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const cancelBtn = e.target.closest('.js-cancel-ann-edit');
        if (cancelBtn) {
            e.preventDefault();
            const form = document.getElementById('addon-ann-form');
            if (form) form.reset();
            const fId = document.getElementById('ann_id');
            if (fId) fId.value = '0';
            const heading = document.getElementById('ann-form-heading');
            if (heading) heading.textContent = '+ CREATE NEW ANNOUNCEMENT ITEM';
            const subBtn = document.getElementById('ann-submit-btn');
            if (subBtn) subBtn.textContent = '+ Save Announcement';
            cancelBtn.classList.add('is-hidden');
            return;
        }

        const closeAnn = e.target.closest('.js-close-ann');
        if (closeAnn) {
            e.preventDefault();
            const bar = closeAnn.closest('.cms-announcement-bar');
            if (bar) bar.classList.add('is-hidden');
        }
    });

    document.addEventListener('submit', function (e) {
        const delForm = e.target.closest('.js-ann-delete-form');
        if (delForm) {
            const msg = delForm.getAttribute('data-confirm') ?? 'Delete Announcement permanently?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        }
    });
})();
