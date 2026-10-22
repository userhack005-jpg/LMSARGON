/**
 * DYNAMIC NEWSLETTER & MULTI-FORM LEAD CAPTURE SCRIPT (assets/newsletter.js)
 */
(function () {
    'use strict';

    if (window.__CMS_NEWSLETTER_V25_BOOTED__) return;
    window.__CMS_NEWSLETTER_V25_BOOTED__ = true;

    const escapeHtml = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    // Default fallback templates (used immediately before AJAX finishes, then live-updated from DB)
    let nlTemplatesList = [
        { id: 1, form_name: 'Email Newsletter Only', show_name: 0, show_phone: 0, show_email: 1, is_default: 0 },
        { id: 2, form_name: 'WhatsApp / Mobile Only', show_name: 0, show_phone: 1, show_email: 0, is_default: 0 },
        { id: 3, form_name: 'Name + Mobile Callback', show_name: 1, show_phone: 1, show_email: 0, is_default: 0 },
        { id: 4, form_name: 'Full Lead Form (Name + WhatsApp + Email)', show_name: 1, show_phone: 1, show_email: 1, is_default: 1 }
    ];

    const getBaseUrl = function () {
        if (document.body) {
            const b = document.body.getAttribute('data-base-url');
            if (b) return b.endsWith('/') ? b : b + '/';
        }
        const scripts = document.querySelectorAll('script[src*="addons/newsletter/"]');
        if (scripts.length > 0) {
            const src = scripts[0].getAttribute('src') || '';
            const base = src.split('addons/newsletter/')[0];
            if (base) return base.endsWith('/') ? base : base + '/';
        }
        return window.location.origin + '/';
    };

    const formatTemplateOptionLabel = function (t) {
        const parts = [];
        if (parseInt(t.show_name || 0, 10) === 1) parts.push('Name');
        if (parseInt(t.show_phone || 0, 10) === 1) parts.push('Phone/WhatsApp');
        if (parseInt(t.show_email || 0, 10) === 1) parts.push('Email');
        const defStar = parseInt(t.is_default || 0, 10) === 1 ? ' ★' : '';
        return 'ID #' + t.id + ' — ' + t.form_name + (parts.length ? ' [' + parts.join(' + ') + ']' : '') + defStar;
    };

    const buildTemplateOptionsHtml = function (selectedVal) {
        const selStr = String(selectedVal || 'default');
        let html = `<option value="default" ${selStr === 'default' ? 'selected' : ''}>★ Use Active Default Template (Set in Newsletter Admin)</option>`;
        nlTemplatesList.forEach(function (t) {
            const tid = String(t.id);
            const isSel = (selStr === tid) ? 'selected' : '';
            html += `<option value="${escapeHtml(tid)}" ${isSel}>${escapeHtml(formatTemplateOptionLabel(t))}</option>`;
        });
        return html;
    };

    if (!window.CMS_ADDON_BLOCK_RENDERERS) {
        window.CMS_ADDON_BLOCK_RENDERERS = {};
    }

    // Standard Page Builder Block Renderer (100% matched with announcements.js standard)
    window.CMS_ADDON_BLOCK_RENDERERS['newsletter_cta'] = function (blk, idx, isFooter, colIdx) {
        const d = blk.data ? blk.data : {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');

        let selectedTpl = d.template_id ? String(d.template_id) : 'default';
        if (selectedTpl === 'default' && d.placeholder) {
            const m = String(d.placeholder).match(/#(?:form)?(\d+)/i);
            if (m) selectedTpl = m[1];
        }

        const isFloating = String(d.is_floating ?? '0');
        const colorMode = d.color_mode ? String(d.color_mode) : 'auto';

        return `
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Form Template (Category)</span><span class="req-badge">TEMPLATE</span></label>
                    <select class="field-select ${clsField} js-nl-pb-tpl-select" ${attr} data-key="template_id" data-selected="${escapeHtml(selectedTpl)}">
                        ${buildTemplateOptionsHtml(selectedTpl)}
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Placement Mode</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="is_floating">
                        <option value="0" ${isFloating === '0' ? 'selected' : ''}>Inline Section (Inside Page Flow)</option>
                        <option value="1" ${isFloating === '1' ? 'selected' : ''}>Floating Overlap Card (Above Footer)</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Pinned Badge Text</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge ?? 'FREE STUDY KIT & UPDATES')}">
                </div>
            </div>
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Main Heading</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="heading" value="${escapeHtml(d.heading ?? 'Master Abacus & Mental Math Faster')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Subheading Text</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="subheading" value="${escapeHtml(d.subheading ?? 'Get free weekly practice worksheets, speed-math tricks, and scholarship alerts straight to your inbox.')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Button Text Override (Optional)</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="btn_text" value="${escapeHtml(d.btn_text ?? '')}" placeholder="Leave empty for Template Default">
                </div>
            </div>
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Privacy Note Below Form</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="privacy_note" value="${escapeHtml(d.privacy_note ?? '🔒 100% Privacy Guaranteed. Unsubscribe anytime.')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Theme Color Mode</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="color_mode">
                        <option value="auto" ${colorMode === 'auto' ? 'selected' : ''}>Auto (Follow Global Theme)</option>
                        <option value="dark" ${colorMode === 'dark' ? 'selected' : ''}>Force Dark Emerald Palette</option>
                        <option value="light" ${colorMode === 'light' ? 'selected' : ''}>Force Light Emerald Palette</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Card, Border, Text & Button Colors</span></label>
                    <div class="color-picker-row">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="bg_color" value="${escapeHtml(d.bg_color ? d.bg_color : '#0d1f1a')}" title="Box Background">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="border_color" value="${escapeHtml(d.border_color ? d.border_color : '#10b981')}" title="Border Color">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="text_color" value="${escapeHtml(d.text_color ? d.text_color : '#f3f4f6')}" title="Text Color">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="btn_bg" value="${escapeHtml(d.btn_bg ? d.btn_bg : '#10b981')}" title="Button Background">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="btn_text_col" value="${escapeHtml(d.btn_text_col ? d.btn_text_col : '#04130d')}" title="Button Text Color">
                    </div>
                </div>
            </div>
            <span class="field-hint">Managed by <strong>Dynamic Newsletter & Multi-Form Lead Capture Add-On</strong> (/addons/newsletter).</span>
        `;
    };

    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'newsletter_cta' } }));

    // Refresh any rendered template dropdowns with live DB templates
    const refreshLiveTemplateDropdowns = function () {
        document.querySelectorAll('.js-nl-pb-tpl-select').forEach(function (sel) {
            const currentVal = sel.value || sel.getAttribute('data-selected') || 'default';
            sel.innerHTML = buildTemplateOptionsHtml(currentVal);
            sel.value = currentVal;
        });
    };

    // Fallback injector if Page Builder uses a legacy hardcoded inspector for newsletter_cta
    const syncLegacyInspectorDropdowns = function () {
        document.querySelectorAll('[data-key="is_floating"], [name="is_floating"]').forEach(function (floatEl) {
            const container = floatEl.closest('.grid-3-col')?.parentElement || floatEl.closest('.panel-card, .console-card, .asset-box');
            if (!container || container.querySelector('.js-nl-pb-tpl-select')) return;

            const isFooter = floatEl.classList.contains('js-f-field');
            const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
            const idx = floatEl.getAttribute('data-idx');
            const col = floatEl.getAttribute('data-col');
            const bidx = floatEl.getAttribute('data-bidx');
            const phEl = container.querySelector('[data-key="placeholder"], [name="placeholder"]');

            let selectedVal = 'default';
            if (phEl && phEl.value) {
                const m = String(phEl.value).match(/#(?:form)?(\d+)/i);
                if (m) selectedVal = m[1];
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'field-group nl-pb-tpl-box';
            wrapper.innerHTML = `
                <label class="field-label"><span>Form Template (Category)</span><span class="req-badge">TEMPLATE</span></label>
                <select class="field-select ${clsField} js-nl-pb-tpl-select"
                        ${isFooter ? `data-col="${escapeHtml(col)}" data-bidx="${escapeHtml(bidx)}"` : `data-idx="${escapeHtml(idx)}"`}
                        data-key="template_id"
                        data-selected="${escapeHtml(selectedVal)}">
                    ${buildTemplateOptionsHtml(selectedVal)}
                </select>
            `;

            const selectEl = wrapper.querySelector('select');
            if (selectEl) {
                selectEl.addEventListener('change', function () {
                    selectEl.setAttribute('data-selected', selectEl.value);
                    if (phEl) {
                        const cleanPh = String(phEl.value || '').replace(/\s*#(?:form)?\d+/gi, '').trim();
                        phEl.value = (selectEl.value !== 'default') ? ((cleanPh || 'Enter your details...') + ' #form' + selectEl.value) : (cleanPh || 'Enter your email address...');
                        phEl.dispatchEvent(new Event('input', { bubbles: true }));
                        phEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }

            container.insertBefore(wrapper, container.firstChild);
        });
    };

    const loadLiveTemplatesFromServer = function () {
        fetch(getBaseUrl() + 'addons/newsletter/templates-json', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && Array.isArray(res.templates) && res.templates.length > 0) {
                nlTemplatesList = res.templates;
                refreshLiveTemplateDropdowns();
                document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'newsletter_cta' } }));
            }
        })
        .catch(function () {});
    };

    const applyNewsletterColors = function () {
        document.querySelectorAll('.cms-newsletter-section, .cms-newsletter-box').forEach(function (el) {
            const attrMap = {
                'data-bg-color': '--nl-bg',
                'data-border-color': '--nl-border',
                'data-text-color': '--nl-text',
                'data-btn-bg': '--nl-btn-bg',
                'data-btn-text-col': '--nl-btn-txt',
                'data-card-bg-light': '--cms-card-bg-light',
                'data-text-light': '--cms-text-light'
            };
            Object.keys(attrMap).forEach(function (k) {
                const v = el.getAttribute(k);
                if (v) el.style.setProperty(attrMap[k], v);
            });
        });
    };

    // Event Delegation for Clicks (Admin Edit / Reset & Page Builder Sync)
    document.addEventListener('click', function (e) {
        setTimeout(syncLegacyInspectorDropdowns, 40);

        const editTplBtn = e.target.closest('.js-edit-nl-tpl');
        if (editTplBtn) {
            e.preventDefault();
            const idEl = document.getElementById('tpl_id');
            if (!idEl) return;
            idEl.value = editTplBtn.getAttribute('data-id') || '0';
            document.getElementById('tpl_name').value = editTplBtn.getAttribute('data-name') || '';
            document.getElementById('tpl_show_name').checked = editTplBtn.getAttribute('data-show-name') === '1';
            document.getElementById('tpl_req_name').checked = editTplBtn.getAttribute('data-req-name') === '1';
            document.getElementById('tpl_name_ph').value = editTplBtn.getAttribute('data-name-ph') || '';
            document.getElementById('tpl_show_phone').checked = editTplBtn.getAttribute('data-show-phone') === '1';
            document.getElementById('tpl_req_phone').checked = editTplBtn.getAttribute('data-req-phone') === '1';
            document.getElementById('tpl_phone_ph').value = editTplBtn.getAttribute('data-phone-ph') || '';
            document.getElementById('tpl_show_email').checked = editTplBtn.getAttribute('data-show-email') === '1';
            document.getElementById('tpl_req_email').checked = editTplBtn.getAttribute('data-req-email') === '1';
            document.getElementById('tpl_email_ph').value = editTplBtn.getAttribute('data-email-ph') || '';
            document.getElementById('tpl_btn_text').value = editTplBtn.getAttribute('data-btn') || 'Subscribe →';
            document.getElementById('tpl_success_msg').value = editTplBtn.getAttribute('data-msg') || '';
            const defCheck = document.getElementById('tpl_is_default');
            if (defCheck) defCheck.checked = editTplBtn.getAttribute('data-default') === '1';
            const h = document.getElementById('nl-tpl-heading');
            if (h) h.textContent = 'Edit Form Template #' + idEl.value;
            if (idEl.form) idEl.form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const tplResetBtn = e.target.closest('#js-tpl-reset-btn');
        if (tplResetBtn) {
            e.preventDefault();
            document.getElementById('tpl_id').value = '0';
            document.getElementById('tpl_name').value = '';
            document.getElementById('tpl_show_name').checked = false;
            document.getElementById('tpl_req_name').checked = false;
            document.getElementById('tpl_name_ph').value = 'Enter your full name...';
            document.getElementById('tpl_show_phone').checked = false;
            document.getElementById('tpl_req_phone').checked = false;
            document.getElementById('tpl_phone_ph').value = 'WhatsApp / Mobile number...';
            document.getElementById('tpl_show_email').checked = true;
            document.getElementById('tpl_req_email').checked = true;
            document.getElementById('tpl_email_ph').value = 'Enter your email address...';
            document.getElementById('tpl_btn_text').value = 'Subscribe →';
            document.getElementById('tpl_success_msg').value = 'Thank you! Your details have been submitted.';
            const defCheck = document.getElementById('tpl_is_default');
            if (defCheck) defCheck.checked = false;
            const h = document.getElementById('nl-tpl-heading');
            if (h) h.textContent = 'Create / Edit Form Template';
            return;
        }

        const editLeadBtn = e.target.closest('.js-edit-nl-lead');
        if (editLeadBtn) {
            e.preventDefault();
            const idEl = document.getElementById('nl_id');
            if (!idEl) return;
            idEl.value = editLeadBtn.getAttribute('data-id') || '0';
            const fIdEl = document.getElementById('nl_form_id');
            if (fIdEl) fIdEl.value = editLeadBtn.getAttribute('data-form-id') || '1';
            document.getElementById('nl_email').value = editLeadBtn.getAttribute('data-email') || '';
            document.getElementById('nl_name').value = editLeadBtn.getAttribute('data-name') || '';
            document.getElementById('nl_phone').value = editLeadBtn.getAttribute('data-phone') || '';
            document.getElementById('nl_status').value = editLeadBtn.getAttribute('data-status') || 'subscribed';
            const h = document.getElementById('nl-form-heading');
            if (h) h.textContent = 'Edit Lead #' + idEl.value;
            if (idEl.form) idEl.form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const leadResetBtn = e.target.closest('#js-nl-reset-btn');
        if (leadResetBtn) {
            e.preventDefault();
            document.getElementById('nl_id').value = '0';
            document.getElementById('nl_email').value = '';
            document.getElementById('nl_name').value = '';
            document.getElementById('nl_phone').value = '';
            document.getElementById('nl_status').value = 'subscribed';
            const h = document.getElementById('nl-form-heading');
            if (h) h.textContent = 'Add / Edit Lead';
        }
    });

    document.addEventListener('change', function (e) {
        const autoSub = e.target.closest('.js-nl-auto-submit');
        if (autoSub && autoSub.form) {
            autoSub.form.submit();
        }
    });

    // Event Delegation for Form Submissions (Frontend Subscribe + Admin Delete Confirm)
    document.addEventListener('submit', function (e) {
        const delForm = e.target.closest('.js-nl-delete-form');
        if (delForm) {
            if (!window.confirm('Are you sure you want to delete this record?')) {
                e.preventDefault();
            }
            return;
        }

        const subForm = e.target.closest('.js-cms-newsletter-form');
        if (subForm) {
            e.preventDefault();
            e.stopPropagation();

            const btn = subForm.querySelector('button[type="submit"]');
            const lbl = subForm.querySelector('.js-nl-btn-label') || btn;
            const feedback = subForm.querySelector('.js-lead-feedback');
            const origText = lbl ? lbl.textContent : 'Subscribe →';

            if (btn) btn.disabled = true;
            if (lbl) lbl.textContent = 'Sending...';
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'js-lead-feedback field-hint cms-nl-feedback';
            }

            const formData = new FormData(subForm);
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const actionUrl = subForm.getAttribute('action') || (getBaseUrl() + 'addons/newsletter/subscribe');

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.csrf_token) {
                    if (csrfMeta) csrfMeta.setAttribute('content', data.csrf_token);
                    document.querySelectorAll('input[name^="csrf_"]').forEach(function (inp) {
                        inp.value = data.csrf_token;
                    });
                }
                const isOk = data && (data.status === 'success' || data.ok === true);
                if (feedback) {
                    feedback.textContent = (data && data.message) ? data.message : (isOk ? 'Submitted!' : 'Submission failed.');
                    feedback.className = 'js-lead-feedback field-hint cms-nl-feedback ' + (isOk ? 'is-ok' : 'is-err');
                }
                if (isOk) {
                    subForm.reset();
                }
            })
            .catch(function () {
                if (feedback) {
                    feedback.textContent = 'Unable to submit right now. Please try again.';
                    feedback.className = 'js-lead-feedback field-hint cms-nl-feedback is-err';
                }
            })
            .finally(function () {
                if (btn) btn.disabled = false;
                if (lbl) lbl.textContent = origText;
            });
        }
    }, true);

    const initAll = function () {
        applyNewsletterColors();
        loadLiveTemplatesFromServer();
        syncLegacyInspectorDropdowns();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
