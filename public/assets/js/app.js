/**
 * Portal Core & Visual Page/Footer Builder Engine (Complete Master File)
 */
(function () {
    'use strict';

    if (window.__PORTAL_APP_INITIALIZED__) {
        return;
    }
    window.__PORTAL_APP_INITIALIZED__ = true;

    // ==========================================
    // 1. COMMON HELPERS
    // ==========================================
    const showAlert = function (box, message, type) {
        if (!box) return;
        box.textContent = message;
        box.className = 'alert-banner ' + (type === 'success' ? 'alert-success' : 'alert-error');
    };

    const hideAlert = function (box) {
        if (!box) return;
        box.className = 'alert-banner is-hidden';
    };

    const escapeHtml = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    // ==========================================
    // 2. BUILDER STATE & SYNC HELPERS
    // ==========================================
    let pbBlocks = [];
    let footerSections = [[], [], [], []];
    let draggedPaletteData = null;
    let draggedCanvasIdx = null;
    let activeRoleFilter = 'all';

    const syncBlocksToHiddenInput = function () {
        const pbBlocksInput = document.getElementById('pb_blocks_json');
        if (pbBlocksInput) {
            pbBlocksInput.value = JSON.stringify(pbBlocks);
        }
    };

    const syncFooterJson = function () {
        const footerJsonInput = document.getElementById('pb_footer_sections_json');
        if (footerJsonInput) {
            footerJsonInput.value = JSON.stringify(footerSections);
        }
    };

    // ==========================================
    // 3. UNIFIED COMPONENT FORM GENERATOR (ALL 14 COMPONENTS)
    // ==========================================
    const buildBlockFieldsHtml = function (blk, idx, isFooter, colIdx) {
        const d = blk.data || {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const clsRte = isFooter ? 'js-f-rte' : 'js-rte-area';
        const clsUpload = isFooter ? 'js-f-media-upload' : 'js-pb-media-upload';
        const attr = isFooter ? `data-col="${colIdx}" data-bidx="${idx}"` : `data-idx="${idx}"`;

        if (blk.type === 'announcement_bar') {
            const filterMode = d.filter_mode || 'category';
            const scrollMode = d.scroll_mode || 'rtl';
            const speedSec   = String(d.scroll_speed || '22');
            return `
                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span>Left Fixed Label Badge</span></label>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="badge_text" value="${escapeHtml(d.badge_text ?? 'NEW')}" placeholder="e.g. NEW or LIVE">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Data Source Mode</span><span class="req-badge">DB DRIVEN</span></label>
                        <select class="field-select ${clsField}" ${attr} data-key="filter_mode">
                            <option value="category" ${filterMode === 'category' ? 'selected' : ''}>📂 By Category (Multiple Loop)</option>
                            <option value="single_id" ${filterMode === 'single_id' ? 'selected' : ''}>🎯 Single Announcement by ID</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Category Name OR Single ID #</span></label>
                        <div style="display:flex; gap:0.4rem;">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="category" value="${escapeHtml(d.category || 'all')}" placeholder="Category (e.g. all, Admissions)">
                            <input type="number" class="field-input mono-input ${clsField}" ${attr} data-key="announcement_id" value="${escapeHtml(d.announcement_id || '')}" placeholder="ID #" style="max-width:95px;">
                        </div>
                    </div>
                </div>
                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span>Scroll Direction / Mode</span><span class="req-badge">MOTION</span></label>
                        <select class="field-select ${clsField}" ${attr} data-key="scroll_mode">
                            <option value="rtl" ${scrollMode === 'rtl' ? 'selected' : ''}>← Scroll Right-to-Left (Infinite Loop)</option>
                            <option value="ltr" ${scrollMode === 'ltr' ? 'selected' : ''}>→ Scroll Left-to-Right (Infinite Loop)</option>
                            <option value="static" ${scrollMode === 'static' ? 'selected' : ''}>⊡ Static Mode (No Scroll, Merged Link)</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Scroll Speed & Close (✕) Button</span></label>
                        <div style="display:flex; gap:0.4rem;">
                            <select class="field-select ${clsField}" ${attr} data-key="scroll_speed">
                                <option value="14" ${speedSec === '14' ? 'selected' : ''}>Fast (14s)</option>
                                <option value="22" ${speedSec === '22' ? 'selected' : ''}>Normal (22s)</option>
                                <option value="32" ${speedSec === '32' ? 'selected' : ''}>Slow (32s)</option>
                            </select>
                            <select class="field-select ${clsField}" ${attr} data-key="dismissible">
                                <option value="1" ${d.dismissible !== '0' ? 'selected' : ''}>Show ✕ Button</option>
                                <option value="0" ${d.dismissible === '0' ? 'selected' : ''}>Hide ✕ Button</option>
                            </select>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Colors: Bar BG / Text / Badge BG / Badge Text</span></label>
                        <div class="color-picker-row">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="bg_color" value="${escapeHtml(d.bg_color || '#10b981')}" title="Bar Background">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="text_color" value="${escapeHtml(d.text_color || '#04130d')}" title="Bar Text Color">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="badge_bg" value="${escapeHtml(d.badge_bg || '#04130d')}" title="Badge Background">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="badge_color" value="${escapeHtml(d.badge_color || '#10b981')}" title="Badge Text Color">
                        </div>
                    </div>
                </div>
                <span class="field-hint">📢 Add or Edit announcements inside <strong>Sidebar → Announcements, FAQs & Reviews</strong>. Links automatically merge and scroll with their announcement text!</span>
            `;
        } else if (blk.type === 'split_hero') {
            return `
                <div class="grid-3-col">
                    <div class="field-group"><label class="field-label"><span>Top Badge</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge)}"></div>
                    <div class="field-group"><label class="field-label"><span>Main Heading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="heading" value="${escapeHtml(d.heading)}"></div>
                    <div class="field-group"><label class="field-label"><span>Highlight Word & Accent Color</span></label>
                        <div class="color-picker-row">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="highlight_word" value="${escapeHtml(d.highlight_word)}" placeholder="Word to colorize">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="accent_color" value="${escapeHtml(d.accent_color || '#10b981')}">
                        </div>
                    </div>
                </div>
                <div class="field-group"><label class="field-label"><span>Subheading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="subheading" value="${escapeHtml(d.subheading)}"></div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Primary Button (Text & URL)</span></label>
                        <div style="display:flex; gap:0.4rem;">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="primary_text" value="${escapeHtml(d.primary_text)}">
                            <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="primary_url" value="${escapeHtml(d.primary_url)}">
                        </div>
                    </div>
                    <div class="field-group"><label class="field-label"><span>Right Visual Image (Upload or URL)</span></label>
                        <div style="display:flex; gap:0.4rem;">
                            <input type="file" class="field-input ${clsUpload}" ${attr} accept="image/*">
                            <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="image_url" value="${escapeHtml(d.image_url)}" placeholder="Image URL">
                        </div>
                    </div>
                </div>
                <div class="grid-3-col">
                    <div class="field-group"><label class="field-label"><span>Floating Stat Badge 1</span></label>
                        <div style="display:flex; gap:0.4rem;">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="stat1_val" value="${escapeHtml(d.stat1_val)}" placeholder="15,000+">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="stat1_label" value="${escapeHtml(d.stat1_label)}" placeholder="Active Learners">
                        </div>
                    </div>
                    <div class="field-group"><label class="field-label"><span>Floating Stat Badge 2</span></label>
                        <div style="display:flex; gap:0.4rem;">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="stat2_val" value="${escapeHtml(d.stat2_val)}" placeholder="4.9 ★">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="stat2_label" value="${escapeHtml(d.stat2_label)}" placeholder="Global Rating">
                        </div>
                    </div>
                    <div class="field-group"><label class="field-label"><span>Trust Footer Line</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="trust_text" value="${escapeHtml(d.trust_text)}"></div>
                </div>
            `;
        } else if (blk.type === 'video_hero') {
            return `
                <div class="grid-3-col">
                    <div class="field-group"><label class="field-label"><span>Badge</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge)}"></div>
                    <div class="field-group"><label class="field-label"><span>Section Heading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="title" value="${escapeHtml(d.title)}"></div>
                    <div class="field-group"><label class="field-label"><span>YouTube Embed URL</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="video_url" value="${escapeHtml(d.video_url)}" placeholder="https://www.youtube.com/embed/..."></div>
                </div>
                <div class="grid-3-col">
                    <div class="field-group"><label class="field-label"><span>Subtitle</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="subtitle" value="${escapeHtml(d.subtitle)}"></div>
                    <div class="field-group"><label class="field-label"><span>Play Button Text & Color</span></label>
                        <div class="color-picker-row">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="btn_text" value="${escapeHtml(d.btn_text)}">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="pulse_color" value="${escapeHtml(d.pulse_color || '#10b981')}">
                        </div>
                    </div>
                    <div class="field-group"><label class="field-label"><span>Stage Background Color</span></label>
                        <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="overlay_color" value="${escapeHtml(d.overlay_color || '#11131c')}">
                    </div>
                </div>
            `;
        } else if (blk.type === 'card_grid') {
            let cardsHtml = '';
            for (let n = 1; n <= 4; n++) {
                cardsHtml += `
                    <div class="asset-box" style="padding:0.75rem; gap:0.45rem;">
                        <span class="mono-label">CARD 0${n}</span>
                        <div style="display:flex; gap:0.4rem;">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="c${n}_tag" value="${escapeHtml(d['c' + n + '_tag'])}" placeholder="Badge Tag">
                            <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="c${n}_price" value="${escapeHtml(d['c' + n + '_price'])}" placeholder="Price / Meta">
                        </div>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="c${n}_title" value="${escapeHtml(d['c' + n + '_title'])}" placeholder="Card ${n} Title (Leave blank to hide)">
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="c${n}_desc" value="${escapeHtml(d['c' + n + '_desc'])}" placeholder="Description">
                        <div style="display:flex; gap:0.4rem;">
                            <input type="text" class="field-input ${clsField}" ${attr} data-key="c${n}_btn" value="${escapeHtml(d['c' + n + '_btn'])}" placeholder="Button Text">
                            <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="c${n}_url" value="${escapeHtml(d['c' + n + '_url'])}" placeholder="URL">
                        </div>
                    </div>
                `;
            }
            return `
                <div class="grid-3-col">
                    <div class="field-group"><label class="field-label"><span>Section Title</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="section_title" value="${escapeHtml(d.section_title)}"></div>
                    <div class="field-group"><label class="field-label"><span>Grid Columns</span></label>
                        <select class="field-select ${clsField}" ${attr} data-key="columns">
                            <option value="2" ${d.columns === '2' ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${d.columns !== '2' && d.columns !== '4' ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${d.columns === '4' ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    <div class="field-group"><label class="field-label"><span>Card BG, Border & Accent Colors</span></label>
                        <div class="color-picker-row">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="card_bg" value="${escapeHtml(d.card_bg || '#171a26')}">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="border_color" value="${escapeHtml(d.border_color || '#232738')}">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="accent_color" value="${escapeHtml(d.accent_color || '#10b981')}">
                        </div>
                    </div>
                </div>
                <div class="grid-2-col">${cardsHtml}</div>
            `;
        } else if (blk.type === 'testimonial_slider') {
            const autoSec = String(d.autoplay_sec ?? '4');
            return `
                <div class="grid-3-col">
                    <div class="field-group">
                        <label class="field-label"><span>Badge</span></label>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Section Heading</span></label>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="section_title" value="${escapeHtml(d.section_title)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Auto-Scroll Speed</span><span class="req-badge">CAROUSEL</span></label>
                        <select class="field-select ${clsField}" ${attr} data-key="autoplay_sec">
                            <option value="0" ${autoSec === '0' ? 'selected' : ''}>⏸ Disabled (Manual Only)</option>
                            <option value="3" ${autoSec === '3' ? 'selected' : ''}>⚡ Fast (Every 3 Seconds)</option>
                            <option value="4" ${autoSec === '4' ? 'selected' : ''}>▶ Normal (Every 4 Seconds)</option>
                            <option value="6" ${autoSec === '6' ? 'selected' : ''}>🐢 Relaxed (Every 6 Seconds)</option>
                            <option value="8" ${autoSec === '8' ? 'selected' : ''}>⏱ Slow (Every 8 Seconds)</option>
                        </select>
                    </div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Subtitle Description</span></label>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="subtitle" value="${escapeHtml(d.subtitle)}">
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Card BG & Star Color</span></label>
                        <div class="color-picker-row">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="card_bg" value="${escapeHtml(d.card_bg || '#171a26')}">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="star_color" value="${escapeHtml(d.star_color || '#f59e0b')}">
                        </div>
                    </div>
                </div>
                <span class="field-hint">★ Manage reviews inside <strong>Sidebar → Announcements, FAQs & Reviews</strong>.</span>
            `;
        } else if (blk.type === 'faq_accordion') {
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
                <span class="field-hint">❓ Manage FAQs inside <strong>Sidebar → Announcements, FAQs & Reviews</strong>.</span>
            `;
        } else if (blk.type === 'newsletter_cta') {
            return `
                <div class="grid-3-col">
                    <div class="field-group"><label class="field-label"><span>Heading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="heading" value="${escapeHtml(d.heading)}"></div>
                    <div class="field-group"><label class="field-label"><span>Button Label</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="btn_text" value="${escapeHtml(d.btn_text)}"></div>
                    <div class="field-group"><label class="field-label"><span>Placement Mode (Edustray Floating)</span></label>
                        <select class="field-select ${clsField}" ${attr} data-key="is_floating">
                            <option value="1" ${String(d.is_floating) === '1' ? 'selected' : ''}>★ Floating Overlap Above Footer</option>
                            <option value="0" ${String(d.is_floating) !== '1' ? 'selected' : ''}>Inline Section in Page Flow</option>
                        </select>
                    </div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Subheading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="subheading" value="${escapeHtml(d.subheading)}"></div>
                    <div class="field-group"><label class="field-label"><span>Box BG, Border & Button Colors</span></label>
                        <div class="color-picker-row">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="bg_color" value="${escapeHtml(d.bg_color || '#0d1f1a')}">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="border_color" value="${escapeHtml(d.border_color || '#10b981')}">
                            <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="btn_bg" value="${escapeHtml(d.btn_bg || '#10b981')}">
                        </div>
                    </div>
                </div>
            `;
        } else if (blk.type === 'hero_banner') {
            return `
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Top Badge Text</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="badge" value="${escapeHtml(d.badge)}"></div>
                    <div class="field-group"><label class="field-label"><span>Heading Title</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="heading" value="${escapeHtml(d.heading)}"></div>
                </div>
                <div class="field-group"><label class="field-label"><span>Subheading Description</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="subheading" value="${escapeHtml(d.subheading)}"></div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>CTA Button Label</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="btn_text" value="${escapeHtml(d.btn_text)}"></div>
                    <div class="field-group"><label class="field-label"><span>CTA Button URL</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="btn_url" value="${escapeHtml(d.btn_url)}"></div>
                </div>
            `;
        } else if (blk.type === 'rich_text') {
            return `
                <div class="rte-toolbar">
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="bold"><b>B</b></button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="italic"><i>I</i></button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="underline"><u>U</u></button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="H2">H2</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="H3">H3</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="formatBlock" data-val="P">P</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="insertUnorderedList">• List</button>
                    <button type="button" class="rte-btn js-rte-cmd" data-cmd="createLink">🔗 Link</button>
                </div>
                <div class="rte-editable ${clsRte}" contenteditable="true" ${attr}>${d.html || '<p>Type rich formatted content here...</p>'}</div>
            `;
        } else if (blk.type === 'image_media') {
            return `
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Upload Image from Computer</span></label><input type="file" class="field-input ${clsUpload}" ${attr} accept="image/*"></div>
                    <div class="field-group"><label class="field-label"><span>Image URL (Auto-filled on upload)</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="image_url" value="${escapeHtml(d.image_url)}"></div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Image Caption</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="caption" value="${escapeHtml(d.caption)}"></div>
                    <div class="field-group"><label class="field-label"><span>Max Width (e.g. 100%, 600px)</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="width" value="${escapeHtml(d.width || '100%')}"></div>
                </div>
            `;
        } else if (blk.type === 'cta_buttons') {
            return `
                <div class="field-group"><label class="field-label"><span>Call-to-Action Banner Heading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="title" value="${escapeHtml(d.title)}"></div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Primary Button Text</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="primary_text" value="${escapeHtml(d.primary_text)}"></div>
                    <div class="field-group"><label class="field-label"><span>Primary Button Link</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="primary_url" value="${escapeHtml(d.primary_url)}"></div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Secondary Button Text</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="secondary_text" value="${escapeHtml(d.secondary_text)}"></div>
                    <div class="field-group"><label class="field-label"><span>Secondary Button Link</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="secondary_url" value="${escapeHtml(d.secondary_url)}"></div>
                </div>
            `;
        } else if (blk.type === 'interactive_quiz') {
            return `
                <div class="field-group"><label class="field-label"><span>Quiz Question</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="question" value="${escapeHtml(d.question)}"></div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Option A</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="option_a" value="${escapeHtml(d.option_a)}"></div>
                    <div class="field-group"><label class="field-label"><span>Option B</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="option_b" value="${escapeHtml(d.option_b)}"></div>
                    <div class="field-group"><label class="field-label"><span>Option C</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="option_c" value="${escapeHtml(d.option_c)}"></div>
                    <div class="field-group"><label class="field-label"><span>Option D</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="option_d" value="${escapeHtml(d.option_d)}"></div>
                </div>
                <div class="grid-2-col">
                    <div class="field-group"><label class="field-label"><span>Correct Option (A, B, C, or D)</span></label><input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="correct_index" value="${escapeHtml(d.correct_index || 'A')}"></div>
                    <div class="field-group"><label class="field-label"><span>Answer Explanation</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="explanation" value="${escapeHtml(d.explanation)}"></div>
                </div>
            `;
        } else if (blk.type === 'feature_grid') {
            return `
                <div class="field-group"><label class="field-label"><span>Section Heading</span></label><input type="text" class="field-input ${clsField}" ${attr} data-key="section_title" value="${escapeHtml(d.section_title)}"></div>
                <div class="grid-3-col">
                    <div>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="card1_title" value="${escapeHtml(d.card1_title)}" placeholder="Card 1 Title" style="margin-bottom:0.4rem;">
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="card1_desc" value="${escapeHtml(d.card1_desc)}" placeholder="Card 1 Description">
                    </div>
                    <div>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="card2_title" value="${escapeHtml(d.card2_title)}" placeholder="Card 2 Title" style="margin-bottom:0.4rem;">
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="card2_desc" value="${escapeHtml(d.card2_desc)}" placeholder="Card 2 Description">
                    </div>
                    <div>
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="card3_title" value="${escapeHtml(d.card3_title)}" placeholder="Card 3 Title" style="margin-bottom:0.4rem;">
                        <input type="text" class="field-input ${clsField}" ${attr} data-key="card3_desc" value="${escapeHtml(d.card3_desc)}" placeholder="Card 3 Description">
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="field-group">
                    <label class="field-label"><span>Raw HTML / Custom Embed Snippet</span></label>
                    <textarea rows="4" class="field-input code-editor ${clsField}" ${attr} data-key="raw_code">${escapeHtml(d.raw_code || '')}</textarea>
                </div>
            `;
        }
    };

    // ==========================================
    // 4. CANVAS RENDERERS (FIXED BUTTON CLICKS vs DRAG HANDLE)
    // ==========================================
    const renderBuilderCanvas = function () {
        const pbCanvas = document.getElementById('pb-canvas-dropzone');
        if (!pbCanvas) return;
        syncBlocksToHiddenInput();

        if (pbBlocks.length === 0) {
            pbCanvas.innerHTML = '<div class="muted-cell" style="text-align:center; padding:3rem 1rem;">Canvas is empty. Click "+ Add" or Drag any component from the left palette to start building your page!</div>';
            return;
        }

        pbCanvas.innerHTML = pbBlocks.map(function (blk, idx) {
            const d = blk.data || {};
            const gapMode = d._gap || 'normal';
            const widthMode = d._width || 'default';
            const bgMode = d._bg_mode || 'default';
            const bgCustom = d._bg_color || '#11131c';

            // Notice: draggable="true" is placed ONLY on the badge drag-handle so buttons (Remove/Up/Down) & inputs NEVER get hijacked by HTML5 drag!
            return `
                <div class="pb-block-card ${gapMode === 'none' ? 'pb-merge-next' : ''}" data-block-idx="${idx}">
                    <div class="pb-block-header" style="flex-wrap: wrap; gap: 0.6rem;">
                        <div class="pb-drag-handle" draggable="true" data-drag-idx="${idx}" title="Drag to reorder block" style="cursor:grab; display:inline-flex; align-items:center; gap:0.4rem;">
                            <span class="role-badge role-admin">⠿ 0${idx + 1} // ${escapeHtml(blk.type.toUpperCase())}</span>
                        </div>
                        <div class="pb-layout-controls">
                            <select class="field-select field-select-xs js-blk-field" data-idx="${idx}" data-key="_gap">
                                <option value="normal" ${gapMode === 'normal' ? 'selected' : ''}>↕ Gap: Normal</option>
                                <option value="none" ${gapMode === 'none' ? 'selected' : ''}>🔗 No Gap (Merge Next)</option>
                            </select>
                            <select class="field-select field-select-xs js-blk-field" data-idx="${idx}" data-key="_width">
                                <option value="default" ${widthMode === 'default' ? 'selected' : ''}>↔ Width: Global Default</option>
                                <option value="boxed" ${widthMode === 'boxed' ? 'selected' : ''}>⊡ Centered (Boxed)</option>
                                <option value="full" ${widthMode === 'full' ? 'selected' : ''}>⇿ 100% Full-Width</option>
                            </select>
                            <select class="field-select field-select-xs js-blk-field" data-idx="${idx}" data-key="_bg_mode">
                                <option value="default" ${bgMode === 'default' ? 'selected' : ''}>🎨 BG: Global Default</option>
                                <option value="seamless" ${bgMode === 'seamless' ? 'selected' : ''}>✨ Seamless (Unified Page BG)</option>
                                <option value="card" ${bgMode === 'card' ? 'selected' : ''}>▣ Boxed Card Surface</option>
                                <option value="custom" ${bgMode === 'custom' ? 'selected' : ''}>🖌 Custom Color</option>
                            </select>
                            <input type="color"
                                   class="color-swatch-xs js-blk-field ${bgMode === 'custom' ? '' : 'is-hidden'}"
                                   data-idx="${idx}"
                                   data-key="_bg_color"
                                   value="${escapeHtml(bgCustom)}">
                        </div>
                        <div class="action-btn-group">
                            <button type="button" class="btn-action js-pb-move-up" data-idx="${idx}">↑ Up</button>
                            <button type="button" class="btn-action js-pb-move-down" data-idx="${idx}">↓ Down</button>
                            <button type="button" class="btn-action btn-action-danger js-pb-remove-block" data-idx="${idx}">✕ Remove</button>
                        </div>
                    </div>
                    ${buildBlockFieldsHtml(blk, idx, false, 0)}
                </div>
            `;
        }).join('');
    };

    const renderFooterBuilder = function () {
        const footerWrap = document.getElementById('pb-footer-columns-wrap');
        const footerColsSelect = document.getElementById('pb_footer_cols');
        if (!footerWrap || !footerColsSelect) return;

        const colCount = parseInt(footerColsSelect.value || '3', 10);
        syncFooterJson();

        let html = '';
        for (let c = 0; c < colCount; c++) {
            if (!Array.isArray(footerSections[c])) footerSections[c] = [];
            const colBlocks = footerSections[c];

            const blocksHtml = colBlocks.map(function (blk, bIdx) {
                return `
                    <div class="pb-block-card">
                        <div class="pb-block-header">
                            <span class="role-badge role-admin">0${bIdx + 1} // ${escapeHtml(blk.type.toUpperCase())}</span>
                            <div class="action-btn-group">
                                <button type="button" class="btn-action js-f-move-up" data-col="${c}" data-bidx="${bIdx}">↑</button>
                                <button type="button" class="btn-action js-f-move-down" data-col="${c}" data-bidx="${bIdx}">↓</button>
                                <button type="button" class="btn-action btn-action-danger js-f-rm-blk" data-col="${c}" data-bidx="${bIdx}">✕</button>
                            </div>
                        </div>
                        ${buildBlockFieldsHtml(blk, bIdx, true, c)}
                    </div>
                `;
            }).join('');

            html += `
                <div class="pb-footer-col-dropzone" data-col-idx="${c}">
                    <div class="panel-header" style="margin-bottom:0.5rem; padding-bottom:0.5rem;">
                        <span class="mono-label">FOOTER SECTION 0${c + 1}</span>
                        <span class="opt-badge">${colBlocks.length} BLOCKS</span>
                    </div>
                    ${blocksHtml || '<div class="muted-cell" style="font-size:0.8rem; text-align:center; padding:1.5rem 0;">Drag & Drop any block here</div>'}
                </div>
            `;
        }
        footerWrap.className = 'pb-footer-builder-grid cols-' + colCount;
        footerWrap.innerHTML = html;
    };

    const addBlockToFooterCol = function (colIdx, type, schemaStr) {
        let defaultData = {};
        try { defaultData = JSON.parse(schemaStr || '{}'); } catch (e) {}
        if (!Array.isArray(footerSections[colIdx])) footerSections[colIdx] = [];
        footerSections[colIdx].push({ id: 'fblk_' + Date.now(), type: type, data: defaultData });
        renderFooterBuilder();
    };

    // Smooth Carousel Transition Helper
    const rotateCarousel = function (track, direction) {
        if (!track || track.classList.contains('is-sliding')) return;
        track.classList.add('is-sliding');

        setTimeout(function () {
            if (direction === 'next') {
                track.appendChild(track.firstElementChild);
            } else {
                track.insertBefore(track.lastElementChild, track.firstElementChild);
            }
            track.classList.remove('is-sliding');
        }, 280);
    };

    // ==========================================
    // 5. UNIFIED CLICK DISPATCHER
    // ==========================================
    document.addEventListener('click', function (event) {
        // Sidebar Mobile Toggle
        const sidebarToggle = event.target.closest('#sidebar-toggle-btn');
        if (sidebarToggle) {
            const sidebar = document.getElementById('app-sidebar');
            if (sidebar) sidebar.classList.toggle('is-open');
            return;
        }

        // Settings & Content Hub Tabs
        const tabBtn = event.target.closest('.settings-tab');
        if (tabBtn) {
            const targetId = tabBtn.getAttribute('data-tab-target');
            if (!targetId) return;
            event.preventDefault();
            document.querySelectorAll('.settings-tab').forEach(function (t) { t.classList.remove('is-active'); });
            document.querySelectorAll('.settings-pane').forEach(function (p) { p.classList.remove('is-visible'); });
            tabBtn.classList.add('is-active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) targetPane.classList.add('is-visible');
            return;
        }

        // Add Block from Palette (+ Add Click)
        const addBlkBtn = event.target.closest('.js-pb-add-block');
        if (addBlkBtn) {
            event.preventDefault();
            const key = addBlkBtn.getAttribute('data-comp-key');
            const schemaStr = addBlkBtn.getAttribute('data-comp-schema') || '{}';

            if (document.getElementById('pb-canvas-dropzone')) {
                let defaultData = {};
                try { defaultData = JSON.parse(schemaStr); } catch (e) {}
                pbBlocks.push({ id: 'blk_' + Date.now(), type: key, data: defaultData });
                renderBuilderCanvas();
            } else if (document.getElementById('pb-footer-columns-wrap')) {
                const targetColSel = document.getElementById('pb_target_footer_col');
                const colIdx = targetColSel ? parseInt(targetColSel.value || '0', 10) : 0;
                addBlockToFooterCol(colIdx, key, schemaStr);
            }
            return;
        }

        // Page Builder Remove Block (100% Reliable Click!)
        const rmBtn = event.target.closest('.js-pb-remove-block');
        if (rmBtn) {
            event.preventDefault();
            event.stopPropagation();
            const idx = parseInt(rmBtn.getAttribute('data-idx'), 10);
            if (!isNaN(idx) && idx >= 0 && idx < pbBlocks.length) {
                pbBlocks.splice(idx, 1);
                renderBuilderCanvas();
            }
            return;
        }

        // Page Builder Move Up
        const upBtn = event.target.closest('.js-pb-move-up');
        if (upBtn) {
            event.preventDefault();
            event.stopPropagation();
            const idx = parseInt(upBtn.getAttribute('data-idx'), 10);
            if (idx > 0) {
                const tmp = pbBlocks[idx - 1];
                pbBlocks[idx - 1] = pbBlocks[idx];
                pbBlocks[idx] = tmp;
                renderBuilderCanvas();
            }
            return;
        }

        // Page Builder Move Down
        const downBtn = event.target.closest('.js-pb-move-down');
        if (downBtn) {
            event.preventDefault();
            event.stopPropagation();
            const idx = parseInt(downBtn.getAttribute('data-idx'), 10);
            if (idx < pbBlocks.length - 1) {
                const tmp = pbBlocks[idx + 1];
                pbBlocks[idx + 1] = pbBlocks[idx];
                pbBlocks[idx] = tmp;
                renderBuilderCanvas();
            }
            return;
        }

        // Clear All Blocks
        const clearBtn = event.target.closest('#btn-pb-clear-all');
        if (clearBtn) {
            event.preventDefault();
            if (window.confirm('Clear all blocks from this page canvas?')) {
                pbBlocks = [];
                renderBuilderCanvas();
            }
            return;
        }

        // Footer Builder Up / Down / Remove
        const fUpBtn = event.target.closest('.js-f-move-up');
        if (fUpBtn) {
            const c = parseInt(fUpBtn.getAttribute('data-col'), 10);
            const b = parseInt(fUpBtn.getAttribute('data-bidx'), 10);
            if (footerSections[c] && b > 0) {
                const tmp = footerSections[c][b - 1];
                footerSections[c][b - 1] = footerSections[c][b];
                footerSections[c][b] = tmp;
                renderFooterBuilder();
            }
            return;
        }

        const fDownBtn = event.target.closest('.js-f-move-down');
        if (fDownBtn) {
            const c = parseInt(fDownBtn.getAttribute('data-col'), 10);
            const b = parseInt(fDownBtn.getAttribute('data-bidx'), 10);
            if (footerSections[c] && b < footerSections[c].length - 1) {
                const tmp = footerSections[c][b + 1];
                footerSections[c][b + 1] = footerSections[c][b];
                footerSections[c][b] = tmp;
                renderFooterBuilder();
            }
            return;
        }

        const rmFooterBlk = event.target.closest('.js-f-rm-blk');
        if (rmFooterBlk) {
            const c = parseInt(rmFooterBlk.getAttribute('data-col'), 10);
            const b = parseInt(rmFooterBlk.getAttribute('data-bidx'), 10);
            if (footerSections[c]) {
                footerSections[c].splice(b, 1);
                renderFooterBuilder();
            }
            return;
        }

        // ==========================================
        // CONTENT HUB EDIT & CANCEL EDIT HANDLERS (ANNOUNCEMENTS, FAQS, REVIEWS)
        // ==========================================
        const editAnnBtn = event.target.closest('.js-edit-ann');
        if (editAnnBtn) {
            document.getElementById('ann_id').value = editAnnBtn.getAttribute('data-id');
            document.getElementById('ann_category').value = editAnnBtn.getAttribute('data-category');
            document.getElementById('ann_message').value = editAnnBtn.getAttribute('data-message');
            document.getElementById('ann_link_text').value = editAnnBtn.getAttribute('data-link-text');
            document.getElementById('ann_link_url').value = editAnnBtn.getAttribute('data-link-url');
            document.getElementById('ann-form-heading').textContent = '✎ EDITING ANNOUNCEMENT ID #' + editAnnBtn.getAttribute('data-id');
            document.getElementById('ann-submit-btn').textContent = 'Update Announcement ✓';
            document.getElementById('ann-cancel-edit').classList.remove('is-hidden');
            document.getElementById('ann_message').focus();
            return;
        }

        if (event.target.closest('#ann-cancel-edit')) {
            document.getElementById('pb-add-ann-form').reset();
            document.getElementById('ann_id').value = '0';
            document.getElementById('ann-form-heading').textContent = '+ CREATE NEW ANNOUNCEMENT ITEM';
            document.getElementById('ann-submit-btn').textContent = '+ Save Announcement';
            document.getElementById('ann-cancel-edit').classList.add('is-hidden');
            return;
        }

        const editFaqBtn = event.target.closest('.js-edit-faq');
        if (editFaqBtn) {
            document.getElementById('faq_id').value = editFaqBtn.getAttribute('data-id');
            document.getElementById('faq_category').value = editFaqBtn.getAttribute('data-category');
            document.getElementById('faq_question').value = editFaqBtn.getAttribute('data-question');
            document.getElementById('faq_answer').value = editFaqBtn.getAttribute('data-answer');
            document.getElementById('faq-form-heading').textContent = '✎ EDITING FAQ #' + editFaqBtn.getAttribute('data-id');
            document.getElementById('faq-submit-btn').textContent = 'Update FAQ ✓';
            document.getElementById('faq-cancel-edit').classList.remove('is-hidden');
            document.getElementById('faq_question').focus();
            return;
        }

        if (event.target.closest('#faq-cancel-edit')) {
            document.getElementById('pb-add-faq-form').reset();
            document.getElementById('faq_id').value = '0';
            document.getElementById('faq-form-heading').textContent = '+ ADD NEW FAQ QUESTION & ANSWER';
            document.getElementById('faq-submit-btn').textContent = '+ Add FAQ';
            document.getElementById('faq-cancel-edit').classList.add('is-hidden');
            return;
        }

        const editRevBtn = event.target.closest('.js-edit-rev');
        if (editRevBtn) {
            document.getElementById('rev_id').value = editRevBtn.getAttribute('data-id');
            document.getElementById('rev_name').value = editRevBtn.getAttribute('data-name');
            document.getElementById('rev_designation').value = editRevBtn.getAttribute('data-designation');
            document.getElementById('rev_rating').value = editRevBtn.getAttribute('data-rating');
            document.getElementById('rev_text').value = editRevBtn.getAttribute('data-text');
            document.getElementById('rev-form-heading').textContent = '✎ EDITING REVIEW #' + editRevBtn.getAttribute('data-id');
            document.getElementById('rev-submit-btn').textContent = 'Update Review ✓';
            document.getElementById('rev-cancel-edit').classList.remove('is-hidden');
            document.getElementById('rev_text').focus();
            return;
        }

        if (event.target.closest('#rev-cancel-edit')) {
            document.getElementById('pb-add-review-form').reset();
            document.getElementById('rev_id').value = '0';
            document.getElementById('rev-form-heading').textContent = '+ ADD NEW STUDENT TESTIMONIAL';
            document.getElementById('rev-submit-btn').textContent = '+ Add Review';
            document.getElementById('rev-cancel-edit').classList.add('is-hidden');
            return;
        }

        // Rich Text WYSIWYG Commands
        const rteBtn = event.target.closest('.js-rte-cmd');
        if (rteBtn) {
            event.preventDefault();
            const cmd = rteBtn.getAttribute('data-cmd');
            const val = rteBtn.getAttribute('data-val') || null;
            if (cmd === 'createLink') {
                const url = window.prompt('Enter hyperlink URL:', 'https://');
                if (url) document.execCommand('createLink', false, url);
            } else {
                document.execCommand(cmd, false, val);
            }
            return;
        }

        // Toggle Component in Registry
        const compToggle = event.target.closest('.js-pb-toggle-comp');
        if (compToggle) {
            const compId = compToggle.getAttribute('data-comp-id');
            const url = compToggle.getAttribute('data-action-url');
            fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ component_id: compId }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function () { window.location.reload(); });
            return;
        }

        // Delete Dynamic Page
        const delPageBtn = event.target.closest('.js-pb-delete-page');
        if (delPageBtn) {
            event.preventDefault();
            if (delPageBtn.dataset.busy === '1') return;
            const pageId = delPageBtn.getAttribute('data-page-id');
            const title = delPageBtn.getAttribute('data-page-title');
            const url = delPageBtn.getAttribute('data-action-url');
            if (!window.confirm('Delete dynamic page "' + title + '"?')) return;

            delPageBtn.dataset.busy = '1';
            fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ page_id: pageId }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function () { window.location.reload(); });
            return;
        }

        // Delete Announcement, FAQ, or Review DB Item
        const delDbBtn = event.target.closest('.js-del-db-item');
        if (delDbBtn) {
            if (!window.confirm('Delete this item permanently?')) return;
            const id = delDbBtn.getAttribute('data-id');
            const url = delDbBtn.getAttribute('data-url');
            fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ id: id }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function () { window.location.reload(); });
            return;
        }

        // Toggle Add-On Enable/Disable
        const toggleAddonBtn = event.target.closest('.js-toggle-addon');
        if (toggleAddonBtn) {
            event.preventDefault();
            const addonId = toggleAddonBtn.getAttribute('data-addon-id');
            const actionUrl = toggleAddonBtn.getAttribute('data-action-url');
            fetch(actionUrl, {
                method: 'POST',
                body: new URLSearchParams({ addon_id: addonId }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function () { window.location.reload(); });
            return;
        }

        // Delete Add-On
        const delAddonBtn = event.target.closest('.js-delete-addon');
        if (delAddonBtn) {
            event.preventDefault();
            if (delAddonBtn.dataset.busy === '1') return;
            const addonId = delAddonBtn.getAttribute('data-addon-id');
            const addonName = delAddonBtn.getAttribute('data-addon-name');
            const actionUrl = delAddonBtn.getAttribute('data-action-url');
            if (!window.confirm('Uninstall "' + addonName + '" and remove its module files?')) return;

            delAddonBtn.dataset.busy = '1';
            fetch(actionUrl, {
                method: 'POST',
                body: new URLSearchParams({ addon_id: addonId }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function () { window.location.reload(); });
            return;
        }

        // Frontend Interactions: Close Announcement, FAQ Toggle, Video Modal, Testimonial Slider, Quiz
        const closeAnn = event.target.closest('.js-close-ann');
        if (closeAnn) {
            const bar = closeAnn.closest('.cms-announcement-bar');
            if (bar) bar.remove();
            return;
        }

        const faqBtn = event.target.closest('.js-faq-toggle');
        if (faqBtn) {
            const item = faqBtn.closest('.cms-faq-item');
            const icon = faqBtn.querySelector('.cms-faq-icon');
            if (item) {
                const isOpen = item.classList.toggle('is-open');
                if (icon) icon.textContent = isOpen ? '−' : '+';
            }
            return;
        }

        const playBtn = event.target.closest('.js-open-video-modal');
        if (playBtn) {
            const videoUrl = playBtn.getAttribute('data-video-url');
            const modal = document.getElementById('cms-video-modal');
            const container = document.getElementById('cms-video-iframe-container');
            if (modal && container && videoUrl) {
                const sep = videoUrl.indexOf('?') === -1 ? '?' : '&';
                container.innerHTML = '<iframe src="' + videoUrl + sep + 'autoplay=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
                modal.classList.remove('is-hidden');
            }
            return;
        }

        if (event.target.closest('.js-close-video-modal') || event.target.id === 'cms-video-modal') {
            const modal = document.getElementById('cms-video-modal');
            const container = document.getElementById('cms-video-iframe-container');
            if (modal) modal.classList.add('is-hidden');
            if (container) container.innerHTML = '';
            return;
        }

        const nextSlide = event.target.closest('.js-carousel-next');
        const prevSlide = event.target.closest('.js-carousel-prev');
        if (nextSlide || prevSlide) {
            const carousel = event.target.closest('.cms-testimonial-carousel');
            const track = carousel ? carousel.querySelector('.cms-testimonial-track') : null;
            if (track && track.children.length > 1) {
                rotateCarousel(track, nextSlide ? 'next' : 'prev');
            }
            return;
        }

        const quizOpt = event.target.closest('.js-quiz-answer');
        if (quizOpt) {
            const card = quizOpt.closest('.cms-quiz-card');
            const chosen = quizOpt.getAttribute('data-choice');
            const correct = card.getAttribute('data-correct');
            const explanation = card.getAttribute('data-explanation');
            const feedback = card.querySelector('.js-quiz-feedback');
            if (feedback) {
                feedback.textContent = (chosen === correct) ? ('✓ Correct! ' + explanation) : '✕ Not quite! Try another option.';
                feedback.className = 'alert-banner ' + (chosen === correct ? 'alert-success' : 'alert-error') + ' js-quiz-feedback';
            }
        }
    });

    // ==========================================
    // 6. UNIFIED INPUT & CHANGE DISPATCHERS
    // ==========================================
    document.addEventListener('input', function (event) {
        if (event.target.id === 'pb_title') {
            const pbSlugInput = document.getElementById('pb_slug');
            const slugPreviewText = document.getElementById('slug-preview-text');
            if (pbSlugInput && !pbSlugInput.dataset.manualEdit) {
                const autoSlug = event.target.value.toLowerCase().trim().replace(/[^a-z0-9\/]+/g, '-').replace(/^-+|-+$/g, '');
                pbSlugInput.value = autoSlug;
                if (slugPreviewText) slugPreviewText.textContent = autoSlug || 'page-slug';
            }
            return;
        }

        if (event.target.id === 'pb_slug') {
            event.target.dataset.manualEdit = '1';
            const slugPreviewText = document.getElementById('slug-preview-text');
            if (slugPreviewText) slugPreviewText.textContent = event.target.value || 'page-slug';
            return;
        }

        const blkField = event.target.closest('.js-blk-field');
        if (blkField) {
            const idx = parseInt(blkField.getAttribute('data-idx'), 10);
            const key = blkField.getAttribute('data-key');
            if (pbBlocks[idx] && key) {
                pbBlocks[idx].data[key] = blkField.value;
                syncBlocksToHiddenInput();
            }
            return;
        }

        const rteArea = event.target.closest('.js-rte-area');
        if (rteArea) {
            const idx = parseInt(rteArea.getAttribute('data-idx'), 10);
            if (pbBlocks[idx]) {
                pbBlocks[idx].data.html = rteArea.innerHTML;
                syncBlocksToHiddenInput();
            }
            return;
        }

        const fField = event.target.closest('.js-f-field');
        if (fField) {
            const c = parseInt(fField.getAttribute('data-col'), 10);
            const b = parseInt(fField.getAttribute('data-bidx'), 10);
            const k = fField.getAttribute('data-key');
            if (footerSections[c] && footerSections[c][b]) {
                footerSections[c][b].data[k] = fField.value;
                syncFooterJson();
            }
            return;
        }

        const fRte = event.target.closest('.js-f-rte');
        if (fRte) {
            const c = parseInt(fRte.getAttribute('data-col'), 10);
            const b = parseInt(fRte.getAttribute('data-bidx'), 10);
            if (footerSections[c] && footerSections[c][b]) {
                footerSections[c][b].data.html = fRte.innerHTML;
                syncFooterJson();
            }
        }
    });

    document.addEventListener('change', function (event) {
        const target = event.target;

        if (target.classList.contains('js-blk-field')) {
            const key = target.getAttribute('data-key');
            const idx = parseInt(target.getAttribute('data-idx'), 10);
            if (pbBlocks[idx] && key) {
                pbBlocks[idx].data[key] = target.value;
                syncBlocksToHiddenInput();
            }
            if (key === '_bg_mode' || key === '_gap') {
                renderBuilderCanvas();
                return;
            }
        }

        if (target.id === 'pb_footer_cols') {
            renderFooterBuilder();
            return;
        }

        if (target.classList.contains('js-pb-media-upload') || target.classList.contains('js-f-media-upload')) {
            if (!target.files || target.files.length === 0) return;
            const isFooter = target.classList.contains('js-f-media-upload');
            const baseUrl = (document.body.getAttribute('data-base-url') || '/').replace(/\/$/, '');
            const uploadUrl = baseUrl + '/addons/page-builder/upload-media';

            const formData = new FormData();
            formData.append('media_file', target.files[0]);

            fetch(uploadUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status === 'success') {
                        if (isFooter) {
                            const c = parseInt(target.getAttribute('data-col'), 10);
                            const b = parseInt(target.getAttribute('data-bidx'), 10);
                            if (footerSections[c] && footerSections[c][b]) {
                                footerSections[c][b].data.image_url = res.image_url;
                                renderFooterBuilder();
                            }
                        } else {
                            const idx = parseInt(target.getAttribute('data-idx'), 10);
                            if (pbBlocks[idx]) {
                                pbBlocks[idx].data.image_url = res.image_url;
                                renderBuilderCanvas();
                            }
                        }
                    } else {
                        window.alert(res.message || 'Image upload failed.');
                    }
                });
        }
    });

    // ==========================================
    // 7. UNIFIED FORM SUBMIT DISPATCHER
    // ==========================================
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form || form.tagName !== 'FORM') return;

        if (form.id === 'pb-add-ann-form' || form.id === 'pb-add-review-form' || form.id === 'pb-add-faq-form') {
            event.preventDefault();
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function () { window.location.reload(); });
            return;
        }

        if (form.classList.contains('js-cms-lead-form')) {
            event.preventDefault();
            const feedback = form.querySelector('.js-lead-feedback');
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (feedback) {
                        feedback.textContent = res.message;
                        feedback.style.color = res.status === 'success' ? '#34d399' : '#fca5a5';
                    }
                    if (res.status === 'success') form.reset();
                });
            return;
        }

        const handledIds = ['installer-form', 'create-user-form', 'global-settings-form', 'addon-upload-form', 'pb-save-form', 'pb-footer-form'];
        if (!handledIds.includes(form.id)) return;

        event.preventDefault();
        if (form.dataset.isSubmitting === '1') return;
        form.dataset.isSubmitting = '1';

        if (form.id === 'pb-save-form') syncBlocksToHiddenInput();
        if (form.id === 'pb-footer-form') syncFooterJson();

        const toastMap = {
            'installer-form': 'installer-alert',
            'create-user-form': 'rbac-toast',
            'global-settings-form': 'settings-toast',
            'addon-upload-form': 'addon-toast',
            'pb-save-form': 'pb-editor-toast',
            'pb-footer-form': 'pb-editor-toast'
        };
        const toast = document.getElementById(toastMap[form.id]);
        hideAlert(toast);

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                form.dataset.isSubmitting = '0';
                if (submitBtn) submitBtn.disabled = false;

                if (res.status === 'success') {
                    showAlert(toast, res.message, 'success');
                    if (form.id === 'pb-save-form' && res.page_id) {
                        const pageIdInput = document.getElementById('pb_page_id');
                        if (pageIdInput) pageIdInput.value = res.page_id;
                        const baseUrl = (document.body.getAttribute('data-base-url') || '/').replace(/\/$/, '');
                        setTimeout(function () { window.location.href = baseUrl + '/addons/page-builder/editor/' + res.page_id; }, 600);
                    } else {
                        setTimeout(function () { window.location.reload(); }, 700);
                    }
                } else {
                    showAlert(toast, res.message || 'Operation failed.', 'error');
                }
            })
            .catch(function () {
                form.dataset.isSubmitting = '0';
                if (submitBtn) submitBtn.disabled = false;
                showAlert(toast, 'Server network error occurred.', 'error');
            });
    });

    // ==========================================
    // 8. HTML5 DRAG & DROP ENGINE (USES .pb-drag-handle)
    // ==========================================
    document.addEventListener('dragstart', function (e) {
        const paletteItem = e.target.closest('.pb-palette-item');
        if (paletteItem) {
            draggedCanvasIdx = null;
            draggedPaletteData = {
                type: paletteItem.getAttribute('data-comp-key'),
                schema: paletteItem.getAttribute('data-comp-schema') || '{}'
            };
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', draggedPaletteData.type);
            return;
        }

        const dragHandle = e.target.closest('.pb-drag-handle');
        if (dragHandle && dragHandle.hasAttribute('data-drag-idx')) {
            draggedPaletteData = null;
            draggedCanvasIdx = parseInt(dragHandle.getAttribute('data-drag-idx'), 10);
            const card = dragHandle.closest('.pb-block-card');
            if (card) card.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(draggedCanvasIdx));
        }
    });

    document.addEventListener('dragend', function () {
        document.querySelectorAll('.is-dragging').forEach(function (el) { el.classList.remove('is-dragging'); });
        document.querySelectorAll('.is-drag-over').forEach(function (el) { el.classList.remove('is-drag-over'); });
    });

    document.addEventListener('dragover', function (e) {
        const dropZone = e.target.closest('#pb-canvas-dropzone, .pb-footer-col-dropzone');
        if (!dropZone) return;
        e.preventDefault();
        dropZone.classList.add('is-drag-over');
    });

    document.addEventListener('dragleave', function (e) {
        const dropZone = e.target.closest('#pb-canvas-dropzone, .pb-footer-col-dropzone');
        if (dropZone && !dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('is-drag-over');
        }
    });

    document.addEventListener('drop', function (e) {
        const pageCanvas = e.target.closest('#pb-canvas-dropzone');
        const footerColZone = e.target.closest('.pb-footer-col-dropzone');
        if (!pageCanvas && !footerColZone) return;
        e.preventDefault();

        document.querySelectorAll('.is-drag-over').forEach(function (el) { el.classList.remove('is-drag-over'); });

        if (pageCanvas) {
            const targetCard = e.target.closest('.pb-block-card');
            const targetIdx = targetCard ? parseInt(targetCard.getAttribute('data-block-idx'), 10) : pbBlocks.length;

            if (draggedPaletteData) {
                let defaultData = {};
                try { defaultData = JSON.parse(draggedPaletteData.schema); } catch (err) {}
                pbBlocks.splice(targetIdx, 0, { id: 'blk_' + Date.now(), type: draggedPaletteData.type, data: defaultData });
                renderBuilderCanvas();
            } else if (draggedCanvasIdx !== null && !isNaN(draggedCanvasIdx)) {
                const movedItem = pbBlocks.splice(draggedCanvasIdx, 1)[0];
                pbBlocks.splice(isNaN(targetIdx) ? pbBlocks.length : targetIdx, 0, movedItem);
                renderBuilderCanvas();
            }
            draggedPaletteData = null;
            draggedCanvasIdx = null;
            return;
        }

        if (footerColZone && draggedPaletteData) {
            const colIndex = parseInt(footerColZone.getAttribute('data-col-idx'), 10);
            addBlockToFooterCol(colIndex, draggedPaletteData.type, draggedPaletteData.schema);
            draggedPaletteData = null;
        }
    });

    // ==========================================
    // 9. BOOT CANVASES, TOP LOADER, AUTO-SCROLL & LAZY REVEAL
    // ==========================================
    const bootCanvases = function () {
        const pbBlocksInput = document.getElementById('pb_blocks_json');
        if (document.getElementById('pb-canvas-dropzone') && pbBlocksInput) {
            try { pbBlocks = JSON.parse(pbBlocksInput.value || '[]'); } catch (e) { pbBlocks = []; }
            renderBuilderCanvas();
        }

        const footerJsonInput = document.getElementById('pb_footer_sections_json');
        if (document.getElementById('pb-footer-columns-wrap') && footerJsonInput) {
            try {
                const parsed = JSON.parse(footerJsonInput.value || '[[],[],[],[]]');
                footerSections = [parsed[0] || [], parsed[1] || [], parsed[2] || [], parsed[3] || []];
            } catch (e) {
                footerSections = [[], [], [], []];
            }
            renderFooterBuilder();
        }

        // Edustray Top Loading Bar
        const topLoader = document.getElementById('edustray-top-loader');
        if (topLoader) {
            topLoader.style.width = '75%';
            window.addEventListener('load', function () {
                topLoader.style.width = '100%';
                setTimeout(function () { topLoader.style.opacity = '0'; }, 350);
            });
        }

        // Testimonial Auto-Scroll with Smooth Fade/Slide
        document.querySelectorAll('.cms-testimonial-carousel').forEach(function (carousel) {
            const sec = parseInt(carousel.getAttribute('data-autoplay') || '0', 10);
            const track = carousel.querySelector('.cms-testimonial-track');
            if (!track || sec <= 0 || track.children.length <= 1) return;

            let isPaused = false;
            carousel.addEventListener('mouseenter', function () { isPaused = true; });
            carousel.addEventListener('mouseleave', function () { isPaused = false; });

            setInterval(function () {
                if (!isPaused && track.children.length > 1) {
                    rotateCarousel(track, 'next');
                }
            }, sec * 1000);
        });

        // Lazy Section Reveal
        if ('IntersectionObserver' in window) {
            const revealObserver = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });
            document.querySelectorAll('.js-lazy-reveal').forEach(function (el) { revealObserver.observe(el); });
        } else {
            document.querySelectorAll('.js-lazy-reveal').forEach(function (el) { el.classList.add('is-revealed'); });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootCanvases);
    } else {
        bootCanvases();
    }
})();