/**
 * DYNAMIC FAQ ADD-ON EXTERNAL SCRIPT (assets/faq.js)
 */
(function () {
    'use strict';

    if (window.__FAQ_ADDON_LOADED__) return;
    window.__FAQ_ADDON_LOADED__ = true;

    const escapeHtml = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    // 1. Register FAQ Block Settings Form into Page Builder's Plug-and-Play Registry
    window.CMS_ADDON_BLOCK_RENDERERS = window.CMS_ADDON_BLOCK_RENDERERS || {};
    window.CMS_ADDON_BLOCK_RENDERERS['faq_accordion'] = function (blk, idx, isFooter, colIdx) {
        const d = blk.data || {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        const boxPos = d.box_position || 'center';
        const txtAlign = d.text_align || 'left';

        return `
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Section Title</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="section_title" value="${escapeHtml(d.section_title)}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>FAQ Tabs Box Position</span><span class="req-badge">BOX POS</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="box_position">
                        <option value="left" ${boxPos === 'left' ? 'selected' : ''}>⇤ Left Positioned Tabs</option>
                        <option value="center" ${boxPos === 'center' ? 'selected' : ''}>⇿ Center Positioned Tabs</option>
                        <option value="right" ${boxPos === 'right' ? 'selected' : ''}>⇥ Right Positioned Tabs</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Inside Text Alignment</span><span class="req-badge">TEXT</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="text_align">
                        <option value="left" ${txtAlign === 'left' ? 'selected' : ''}>Text: Left Aligned</option>
                        <option value="center" ${txtAlign === 'center' ? 'selected' : ''}>Text: Center Aligned</option>
                        <option value="right" ${txtAlign === 'right' ? 'selected' : ''}>Text: Right Aligned</option>
                    </select>
                </div>
            </div>
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Subtitle Description</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="subtitle" value="${escapeHtml(d.subtitle)}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Filter by Category</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="category" value="${escapeHtml(d.category || 'all')}" placeholder="all">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Border & Accent Color</span></label>
                    <div class="color-picker-row">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="border_color" value="${escapeHtml(d.border_color || '#232738')}">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="accent_color" value="${escapeHtml(d.accent_color || '#10b981')}">
                    </div>
                </div>
            </div>
            <span class="field-hint">❓ Managed by <strong>Dynamic FAQ Accordion Add-On</strong> (/addons/faqs).</span>
        `;
    };

    // Dispatch event so Page Builder re-renders existing blocks if app.js booted before this deferred script!
    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'faq_accordion' } }));

    // 2. Apply Frontend Custom Properties (Colors)
    const applyFaqCustomVars = function () {
        document.querySelectorAll('.cms-faq-section').forEach(function (sec) {
            const acc = sec.getAttribute('data-accent-color');
            const bdr = sec.getAttribute('data-border-color');
            const bg = sec.getAttribute('data-custom-bg');
            if (acc) sec.style.setProperty('--faq-accent', acc);
            if (bdr) sec.style.setProperty('--faq-border', bdr);
            if (bg) sec.style.backgroundColor = bg;
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyFaqCustomVars);
    } else {
        applyFaqCustomVars();
    }

    // 3. Click Delegation: Backend Edit / Cancel Edit + Frontend Accordion Toggle
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.js-edit-faq-row');
        if (editBtn) {
            e.preventDefault();
            const fId = document.getElementById('faq_id');
            const fCat = document.getElementById('faq_category');
            const fSort = document.getElementById('faq_sort');
            const fQ = document.getElementById('faq_question');
            const fA = document.getElementById('faq_answer');
            const heading = document.getElementById('faq-form-heading');
            const subBtn = document.getElementById('faq-submit-btn');
            const cancelBtn = document.getElementById('faq-cancel-edit');

            if (fId) fId.value = editBtn.getAttribute('data-id') || '0';
            if (fCat) fCat.value = editBtn.getAttribute('data-category') || 'General';
            if (fSort) fSort.value = editBtn.getAttribute('data-sort') || '1';
            if (fQ) fQ.value = editBtn.getAttribute('data-question') || '';
            if (fA) fA.value = editBtn.getAttribute('data-answer') || '';
            if (heading) heading.textContent = '✎ EDITING FAQ #' + editBtn.getAttribute('data-id');
            if (subBtn) subBtn.textContent = 'Update FAQ ✓';
            if (cancelBtn) cancelBtn.classList.remove('is-hidden');

            if (fQ) fQ.focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const cancelBtn = e.target.closest('.js-cancel-faq-edit');
        if (cancelBtn) {
            e.preventDefault();
            const form = document.getElementById('addon-faq-form');
            if (form) form.reset();
            const fId = document.getElementById('faq_id');
            if (fId) fId.value = '0';
            const heading = document.getElementById('faq-form-heading');
            if (heading) heading.textContent = '+ ADD NEW FAQ QUESTION & ANSWER';
            const subBtn = document.getElementById('faq-submit-btn');
            if (subBtn) subBtn.textContent = '+ Save FAQ';
            cancelBtn.classList.add('is-hidden');
            return;
        }

        const faqToggle = e.target.closest('.js-faq-toggle');
        if (faqToggle) {
            e.preventDefault();
            const item = faqToggle.closest('.cms-faq-item');
            const icon = faqToggle.querySelector('.cms-faq-icon');
            if (item) {
                const isOpen = item.classList.toggle('is-open');
                if (icon) icon.textContent = isOpen ? '−' : '+';
            }
        }
    });

    // 4. Delete Confirmation Delegation
    document.addEventListener('submit', function (e) {
        const delForm = e.target.closest('.js-faq-delete-form');
        if (delForm) {
            const msg = delForm.getAttribute('data-confirm') || 'Delete this FAQ permanently?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        }
    });
})();