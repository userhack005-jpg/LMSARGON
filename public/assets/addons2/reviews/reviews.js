/**
 * STUDENT REVIEWS ADD-ON EXTERNAL SCRIPT (assets/reviews.js)
 * Features: Auto-Scroll Toggle, Smooth Scroll (RTL/LTR), Fade-In/Out, Dual Dark/Light Colors
 */
(function () {
    'use strict';

    if (window.__REV_ADDON_LOADED__) return;
    window.__REV_ADDON_LOADED__ = true;

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

    // 1. Register Testimonial Slider Block Settings in Page Builder
    window.CMS_ADDON_BLOCK_RENDERERS['testimonial_slider'] = function (blk, idx, isFooter, colIdx) {
        const d = blk.data ? blk.data : {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');

        const cols = d.columns ? String(d.columns) : '3';
        const autoScroll = String(d.auto_scroll ?? '1');
        const animStyle = d.anim_style ? String(d.anim_style) : 'smooth_scroll';
        const scrollDir = d.scroll_dir ? String(d.scroll_dir) : 'rtl';
        const colorMode = d.color_mode ? String(d.color_mode) : 'auto';

        return `
            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Badge Text</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge ?? 'WALL OF LOVE')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Section Heading</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="section_title" value="${escapeHtml(d.section_title ?? 'What Parents & Students Say')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Filter by Category</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="category" value="${escapeHtml(d.category ? d.category : 'all')}" placeholder="all, Parents, Students">
                </div>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Auto-Scroll / Animation</span><span class="req-badge">MOTION</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="auto_scroll">
                        <option value="1" ${autoScroll === '1' ? 'selected' : ''}>Enabled (Animated Carousel / Scroll)</option>
                        <option value="0" ${autoScroll === '0' ? 'selected' : ''}>Disabled (Static Multi-Column Grid)</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Animation Style</span><span class="req-badge">EFFECT</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="anim_style">
                        <option value="smooth_scroll" ${animStyle === 'smooth_scroll' ? 'selected' : ''}>Smooth Continuous Scroll</option>
                        <option value="fade" ${animStyle === 'fade' ? 'selected' : ''}>Fade-In / Fade-Out Spotlight</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Scroll Direction (If Smooth Scroll)</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="scroll_dir">
                        <option value="rtl" ${scrollDir === 'rtl' ? 'selected' : ''}>← Right to Left (RTL)</option>
                        <option value="ltr" ${scrollDir === 'ltr' ? 'selected' : ''}>→ Left to Right (LTR)</option>
                    </select>
                </div>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Speed / Fade Interval (Seconds)</span></label>
                    <input type="number" class="field-input mono-input ${clsField}" ${attr} data-key="scroll_speed" value="${escapeHtml(d.scroll_speed ? d.scroll_speed : '28')}" min="4" max="120">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Static Grid Columns (When Disabled)</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="columns">
                        <option value="3" ${cols === '3' ? 'selected' : ''}>3 Columns Grid</option>
                        <option value="2" ${cols === '2' ? 'selected' : ''}>2 Columns Grid</option>
                        <option value="1" ${cols === '1' ? 'selected' : ''}>1 Column Centered</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Theme Color Mode</span><span class="req-badge">DARK/LIGHT</span></label>
                    <select class="field-select ${clsField}" ${attr} data-key="color_mode">
                        <option value="auto" ${colorMode === 'auto' ? 'selected' : ''}>Auto Adaptive (Uses Site Theme)</option>
                        <option value="dual" ${colorMode === 'dual' ? 'selected' : ''}>Custom Dark + Light Theme Colors</option>
                    </select>
                </div>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Subtitle Description</span></label>
                    <input type="text" class="field-input ${clsField}" ${attr} data-key="subtitle" value="${escapeHtml(d.subtitle ?? '')}">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>🌙 Dark Theme (Card / Text / Star)</span></label>
                    <div class="color-picker-row">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="card_bg_dark" value="${escapeHtml(d.card_bg_dark ? d.card_bg_dark : '#171a26')}" title="Dark Mode Card BG">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="text_dark" value="${escapeHtml(d.text_dark ? d.text_dark : '#f8fafc')}" title="Dark Mode Text">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="star_dark" value="${escapeHtml(d.star_dark ? d.star_dark : '#f59e0b')}" title="Dark Mode Stars">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>☀️ Light Theme (Card / Text / Star)</span></label>
                    <div class="color-picker-row">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="card_bg_light" value="${escapeHtml(d.card_bg_light ? d.card_bg_light : '#ffffff')}" title="Light Mode Card BG">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="text_light" value="${escapeHtml(d.text_light ? d.text_light : '#0f172a')}" title="Light Mode Text">
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="star_light" value="${escapeHtml(d.star_light ? d.star_light : '#d97706')}" title="Light Mode Stars">
                    </div>
                </div>
            </div>
            <span class="field-hint">★ Managed by <strong>Student Reviews Add-On</strong> (/addons/reviews).</span>
        `;
    };

    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'testimonial_slider' } }));

    // 2. Apply Custom CSS Variables & Boot Fade-In/Fade-Out Carousels
    const initFrontendReviews = function () {
        document.querySelectorAll('.cms-reviews-section').forEach(function (sec) {
            if (sec.dataset.revInitialized === '1') return;
            sec.dataset.revInitialized = '1';

            // Bind Dark & Light Theme Custom Variables
            const cDark = sec.getAttribute('data-card-bg-dark');
            const tDark = sec.getAttribute('data-text-dark');
            const sDark = sec.getAttribute('data-star-dark');
            const cLight = sec.getAttribute('data-card-bg-light');
            const tLight = sec.getAttribute('data-text-light');
            const sLight = sec.getAttribute('data-star-light');
            const spd = sec.getAttribute('data-scroll-speed');

            if (cDark) sec.style.setProperty('--rev-card-bg-dark', cDark);
            if (tDark) sec.style.setProperty('--rev-text-dark', tDark);
            if (sDark) sec.style.setProperty('--rev-star-dark', sDark);
            if (cLight) sec.style.setProperty('--rev-card-bg-light', cLight);
            if (tLight) sec.style.setProperty('--rev-text-light', tLight);
            if (sLight) sec.style.setProperty('--rev-star-light', sLight);
            if (spd) sec.style.setProperty('--rev-speed', spd + 's');

            // Boot Fade-In / Fade-Out Auto-Carousel if active
            const isAuto = sec.getAttribute('data-auto-scroll') === '1';
            const animStyle = sec.getAttribute('data-anim-style');
            if (isAuto && animStyle === 'fade') {
                const slides = sec.querySelectorAll('.cms-review-fade-slide');
                const dots = sec.querySelectorAll('.cms-rev-dot');
                if (slides.length > 1) {
                    let currentIdx = 0;
                    const intervalMs = Math.max(3, parseInt(spd ? spd : '5', 10)) * 1000;

                    const showSlide = function (nextIdx) {
                        slides.forEach(function (sl, i) {
                            sl.classList.toggle('is-active', i === nextIdx);
                        });
                        dots.forEach(function (dt, i) {
                            dt.classList.toggle('is-active', i === nextIdx);
                        });
                        currentIdx = nextIdx;
                    };

                    setInterval(function () {
                        const next = (currentIdx + 1) % slides.length;
                        showSlide(next);
                    }, intervalMs);
                }
            }
        });
    };

    initFrontendReviews();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFrontendReviews);
    }

    // 3. Click Delegation: Fade Dots + Backend Edit / Cancel Edit
    document.addEventListener('click', function (e) {
        const dotBtn = e.target.closest('.js-rev-fade-dot');
        if (dotBtn) {
            e.preventDefault();
            const stage = dotBtn.closest('.cms-reviews-fade-stage');
            const targetIdx = parseInt(dotBtn.getAttribute('data-target') ?? '0', 10);
            if (stage) {
                stage.querySelectorAll('.cms-review-fade-slide').forEach(function (sl, i) {
                    sl.classList.toggle('is-active', i === targetIdx);
                });
                stage.querySelectorAll('.cms-rev-dot').forEach(function (dt, i) {
                    dt.classList.toggle('is-active', i === targetIdx);
                });
            }
            return;
        }

        const editBtn = e.target.closest('.js-edit-rev-row');
        if (editBtn) {
            e.preventDefault();
            const fId = document.getElementById('rev_id');
            const fCat = document.getElementById('rev_category');
            const fName = document.getElementById('rev_name');
            const fRole = document.getElementById('rev_role');
            const fRating = document.getElementById('rev_rating');
            const fQuote = document.getElementById('rev_quote');
            const fAvatar = document.getElementById('rev_avatar');
            const fSort = document.getElementById('rev_sort');
            const heading = document.getElementById('rev-form-heading');
            const subBtn = document.getElementById('rev-submit-btn');
            const cancelBtn = document.getElementById('rev-cancel-edit');

            if (fId) fId.value = editBtn.getAttribute('data-id') ?? '0';
            if (fCat) fCat.value = editBtn.getAttribute('data-category') ?? 'General';
            if (fName) fName.value = editBtn.getAttribute('data-name') ?? '';
            if (fRole) fRole.value = editBtn.getAttribute('data-role') ?? '';
            if (fRating) fRating.value = editBtn.getAttribute('data-rating') ?? '5';
            if (fQuote) fQuote.value = editBtn.getAttribute('data-quote') ?? '';
            if (fAvatar) fAvatar.value = editBtn.getAttribute('data-avatar') ?? '';
            if (fSort) fSort.value = editBtn.getAttribute('data-sort') ?? '1';
            if (heading) heading.textContent = 'EDITING REVIEW #' + editBtn.getAttribute('data-id');
            if (subBtn) subBtn.textContent = 'Update Review';
            if (cancelBtn) cancelBtn.classList.remove('is-hidden');

            if (fName) fName.focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const cancelBtn = e.target.closest('.js-cancel-rev-edit');
        if (cancelBtn) {
            e.preventDefault();
            const form = document.getElementById('addon-rev-form');
            if (form) form.reset();
            const fId = document.getElementById('rev_id');
            if (fId) fId.value = '0';
            const heading = document.getElementById('rev-form-heading');
            if (heading) heading.textContent = '+ ADD NEW STUDENT / PARENT REVIEW';
            const subBtn = document.getElementById('rev-submit-btn');
            if (subBtn) subBtn.textContent = '+ Save Review';
            cancelBtn.classList.add('is-hidden');
        }
    });

    document.addEventListener('submit', function (e) {
        const delForm = e.target.closest('.js-rev-delete-form');
        if (delForm) {
            const msg = delForm.getAttribute('data-confirm') ?? 'Delete this review permanently?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        }
    });
})();
