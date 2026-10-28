/**
 * Portal Core & Visual Page/Footer Builder Engine (Complete Master File + Dual-Theme Design Studio)
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

    const hexToRgba = function (hex, alpha) {
        let h = String(hex || '#090a0f').replace('#', '').trim();
        if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        if (h.length !== 6) return 'rgba(9, 10, 15, ' + alpha + ')';
        const r = parseInt(h.substring(0, 2), 16) || 9;
        const g = parseInt(h.substring(2, 4), 16) || 10;
        const b = parseInt(h.substring(4, 6), 16) || 15;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    };

    // ==========================================
    // 2. BUILDER STATE & SYNC HELPERS
    // ==========================================
    let pbBlocks = [];
    let footerSections = [[], [], [], []];
    let draggedPaletteData = null;
    let draggedCanvasIdx = null;

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
    // 2B. UNIVERSAL DUAL-THEME (DARK + LIGHT PALETTE) DESIGN STUDIO DRAWER
    // ==========================================
    const buildDesignStudioDrawerHtml = function (d, idx, isFooter, colIdx) {
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const clsBgUpload = isFooter ? 'js-f-bg-upload' : 'js-pb-bg-upload';
        const attr = isFooter ? `data-col="${colIdx}" data-bidx="${idx}"` : `data-idx="${idx}"`;

        const colorMode  = String(d.color_mode || 'auto');
        const shapeStyle = String(d._shape_style || 'default');
        const divTop     = String(d._divider_top || 'none');
        const divBot     = String(d._divider_bottom || 'none');
        const gradDir    = String(d._bg_grad_dir || '135deg');

        // Dark Theme Palette Values (with fallback to legacy keys)
        const dBgCol     = String(d._dark_bg_color || d._bg_color || '#11131c');
        const dGradStart = String(d._dark_grad_start || d._bg_grad_start || '#0d1f1a');
        const dGradEnd   = String(d._dark_grad_end || d._bg_grad_end || '#11131c');
        const dOvCol     = String(d._dark_overlay_color || d._bg_overlay_color || '#090a0f');
        const dOvOp      = String(d._dark_overlay_opacity !== undefined ? d._dark_overlay_opacity : (d._bg_overlay_opacity !== undefined ? d._bg_overlay_opacity : '55'));
        const dDivCol    = String(d._dark_divider_color || d._divider_color || '#090a0f');
        const dBdrCol    = String(d._dark_bdr_color || d._bdr_color || '#232738');
        const dShadowCol = String(d._dark_shadow_color || d._shadow_color || '#10b981');
        const dTxtHead   = String(d._dark_txt_heading || d._txt_heading_color || '');
        const dTxtBody   = String(d._dark_txt_body || d.text_dark || d._txt_body_color || '');
        const dTxtAcc    = String(d._dark_txt_accent || d._txt_accent_color || '');
        const dCardBg    = String(d.card_bg_dark || d.card_bg || '#171a26');

        // Light Theme Palette Values
        const lBgCol     = String(d._light_bg_color || '#ffffff');
        const lGradStart = String(d._light_grad_start || '#ecfdf5');
        const lGradEnd   = String(d._light_grad_end || '#ffffff');
        const lOvCol     = String(d._light_overlay_color || '#ffffff');
        const lOvOp      = String(d._light_overlay_opacity !== undefined ? d._light_overlay_opacity : '40');
        const lDivCol    = String(d._light_divider_color || '#f8fafc');
        const lBdrCol    = String(d._light_bdr_color || '#e2e8f0');
        const lShadowCol = String(d._light_shadow_color || '#10b981');
        const lTxtHead   = String(d._light_txt_heading || '');
        const lTxtBody   = String(d._light_txt_body || d.text_light || '');
        const lTxtAcc    = String(d._light_txt_accent || '');
        const lCardBg    = String(d.card_bg_light || '#f8fafc');

        // Shared Geometry & Image Fit Settings
        const bgImgUrl   = String(d._bg_img_url || '');
        const bgImgSize  = String(d._bg_img_size || 'cover');
        const bgImgPos   = String(d._bg_img_pos || 'center center');
        const bgImgRep   = String(d._bg_img_repeat || 'no-repeat');
        const bdrWidth   = String(d._bdr_width || '0');
        const bdrStyle   = String(d._bdr_style || 'default');
        const bdrRadius  = String(d._bdr_radius || 'default');
        const shadowPre  = String(d._shadow_preset || 'default');

        const txtFont    = String(d._txt_font || 'default');
        const txtAlign   = String(d._txt_align || 'default');
        const txtScale   = String(d._txt_scale || 'default');
        const txtTrans   = String(d._txt_transform || 'default');
        const padY       = String(d._pad_y || 'default');
        const padX       = String(d._pad_x || 'default');

        const isOpen = d._studio_open === '1';

        return `
            <div class="pb-design-studio-drawer ${isOpen ? '' : 'is-hidden'}">
                <!-- 1. THEME MODE, CONTAINER SHAPE & WAVE DIVIDERS -->
                <div class="pb-studio-group">
                    <div class="mono-label text-emerald" style="margin-bottom:0.5rem;">1. COMPONENT THEME MODE, CONTAINER SHAPE & WAVE DIVIDERS</div>
                    <div class="grid-4-col">
                        <div class="field-group"><label class="field-label"><span>Component Theme Mode</span><span class="req-badge">DUAL THEME</span></label>
                            <select class="field-select ${clsField}" ${attr} data-key="color_mode">
                                <option value="auto" ${colorMode === 'auto' ? 'selected' : ''}>🌗 Auto (Follow Global Dark/Light)</option>
                                <option value="dark" ${colorMode === 'dark' ? 'selected' : ''}>🌙 Force Dark Palette Always</option>
                                <option value="light" ${colorMode === 'light' ? 'selected' : ''}>☀ Force Light Palette Always</option>
                            </select>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Container Box Shape</span></label>
                            <select class="field-select ${clsField}" ${attr} data-key="_shape_style">
                                <option value="default" ${shapeStyle === 'default' ? 'selected' : ''}>Default Card</option>
                                <option value="square" ${shapeStyle === 'square' ? 'selected' : ''}>Sharp Square (0px)</option>
                                <option value="rounded" ${shapeStyle === 'rounded' ? 'selected' : ''}>Soft Rounded (18px)</option>
                                <option value="pill_curve" ${shapeStyle === 'pill_curve' ? 'selected' : ''}>Super-Curved Pill (42px)</option>
                                <option value="leaf_asym" ${shapeStyle === 'leaf_asym' ? 'selected' : ''}>Organic Leaf Curve</option>
                                <option value="arch_top" ${shapeStyle === 'arch_top' ? 'selected' : ''}>Modern Arch Top</option>
                                <option value="diagonal_slant" ${shapeStyle === 'diagonal_slant' ? 'selected' : ''}>Angled Slant Cut</option>
                            </select>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Top Wave / Shape Divider</span></label>
                            <select class="field-select ${clsField}" ${attr} data-key="_divider_top">
                                <option value="none" ${divTop === 'none' ? 'selected' : ''}>None</option>
                                <option value="wave_smooth" ${divTop === 'wave_smooth' ? 'selected' : ''}>🌊 Smooth Ocean Wave</option>
                                <option value="wave_layered" ${divTop === 'wave_layered' ? 'selected' : ''}>🌊 Multi-Layer SaaS Wave</option>
                                <option value="curve_in" ${divTop === 'curve_in' ? 'selected' : ''}>◡ Rounded Scoop Curve</option>
                                <option value="tilt_slant" ${divTop === 'tilt_slant' ? 'selected' : ''}>📐 Diagonal Slant</option>
                                <option value="triangle_asym" ${divTop === 'triangle_asym' ? 'selected' : ''}>▲ Asymmetric Peak</option>
                            </select>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Bottom Wave / Shape Divider</span></label>
                            <select class="field-select ${clsField}" ${attr} data-key="_divider_bottom">
                                <option value="none" ${divBot === 'none' ? 'selected' : ''}>None</option>
                                <option value="wave_smooth" ${divBot === 'wave_smooth' ? 'selected' : ''}>🌊 Smooth Ocean Wave</option>
                                <option value="wave_layered" ${divBot === 'wave_layered' ? 'selected' : ''}>🌊 Multi-Layer SaaS Wave</option>
                                <option value="curve_in" ${divBot === 'curve_in' ? 'selected' : ''}>◡ Rounded Scoop Curve</option>
                                <option value="tilt_slant" ${divBot === 'tilt_slant' ? 'selected' : ''}>📐 Diagonal Slant</option>
                                <option value="triangle_asym" ${divBot === 'triangle_asym' ? 'selected' : ''}>▲ Asymmetric Peak</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. DARK THEME PALETTE (APPLIES WHEN SITE OR BLOCK IS IN DARK MODE) -->
                <div class="pb-studio-group" style="background:rgba(9,10,15,0.45); padding:0.75rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <div class="mono-label text-emerald" style="margin-bottom:0.5rem;">2. 🌙 DARK THEME PALETTE (BG, GRADIENT, BORDERS, CARDS & TEXT IN DARK MODE)</div>
                    <div class="grid-4-col">
                        <div class="field-group"><label class="field-label"><span>Dark Solid BG & Inner Card BG</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_bg_color" value="${escapeHtml(dBgCol)}" title="Dark Section BG">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="card_bg_dark" value="${escapeHtml(dCardBg)}" title="Dark Inner Card BG">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Dark Gradient (Start & End)</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_grad_start" value="${escapeHtml(dGradStart)}">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_grad_end" value="${escapeHtml(dGradEnd)}">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Dark Border, Wave & Glow</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_bdr_color" value="${escapeHtml(dBdrCol)}" title="Dark Border Color">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_divider_color" value="${escapeHtml(dDivCol)}" title="Dark Wave Divider Fill">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_shadow_color" value="${escapeHtml(dShadowCol)}" title="Dark Glow Color">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Dark BG Image Overlay & %</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_dark_overlay_color" value="${escapeHtml(dOvCol)}">
                                <input type="number" class="field-input mono-input ${clsField}" ${attr} data-key="_dark_overlay_opacity" value="${escapeHtml(dOvOp)}" min="0" max="95" step="5">
                            </div>
                        </div>
                    </div>
                    <div class="grid-3-col" style="margin-top:0.35rem;">
                        <div class="field-group"><label class="field-label"><span>Dark Heading Color</span></label>
                            <div class="color-picker-row">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_dark_txt_heading" value="${escapeHtml(dTxtHead)}" placeholder="#f3f4f6 (Auto)">
                                <input type="color" class="color-swatch-input js-color-sync" value="${escapeHtml(dTxtHead || '#f3f4f6')}">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Dark Body Text Color</span></label>
                            <div class="color-picker-row">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_dark_txt_body" value="${escapeHtml(dTxtBody)}" placeholder="#9ca3af (Auto)">
                                <input type="color" class="color-swatch-input js-color-sync" value="${escapeHtml(dTxtBody || '#9ca3af')}">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Dark Button / Accent Color</span></label>
                            <div class="color-picker-row">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_dark_txt_accent" value="${escapeHtml(dTxtAcc)}" placeholder="#10b981 (Auto)">
                                <input type="color" class="color-swatch-input js-color-sync" value="${escapeHtml(dTxtAcc || '#10b981')}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. LIGHT THEME PALETTE (APPLIES WHEN SITE OR BLOCK IS IN LIGHT MODE) -->
                <div class="pb-studio-group" style="background:rgba(255,255,255,0.03); padding:0.75rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <div class="mono-label text-cyan" style="margin-bottom:0.5rem;">3. ☀ LIGHT THEME PALETTE (BG, GRADIENT, BORDERS, CARDS & TEXT IN LIGHT MODE)</div>
                    <div class="grid-4-col">
                        <div class="field-group"><label class="field-label"><span>Light Solid BG & Inner Card BG</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_bg_color" value="${escapeHtml(lBgCol)}" title="Light Section BG">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="card_bg_light" value="${escapeHtml(lCardBg)}" title="Light Inner Card BG">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Light Gradient (Start & End)</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_grad_start" value="${escapeHtml(lGradStart)}">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_grad_end" value="${escapeHtml(lGradEnd)}">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Light Border, Wave & Glow</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_bdr_color" value="${escapeHtml(lBdrCol)}" title="Light Border Color">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_divider_color" value="${escapeHtml(lDivCol)}" title="Light Wave Divider Fill">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_shadow_color" value="${escapeHtml(lShadowCol)}" title="Light Glow Color">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Light BG Image Overlay & %</span></label>
                            <div class="color-picker-row">
                                <input type="color" class="color-swatch-input ${clsField}" ${attr} data-key="_light_overlay_color" value="${escapeHtml(lOvCol)}">
                                <input type="number" class="field-input mono-input ${clsField}" ${attr} data-key="_light_overlay_opacity" value="${escapeHtml(lOvOp)}" min="0" max="95" step="5">
                            </div>
                        </div>
                    </div>
                    <div class="grid-3-col" style="margin-top:0.35rem;">
                        <div class="field-group"><label class="field-label"><span>Light Heading Color</span></label>
                            <div class="color-picker-row">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_light_txt_heading" value="${escapeHtml(lTxtHead)}" placeholder="#0f172a (Auto)">
                                <input type="color" class="color-swatch-input js-color-sync" value="${escapeHtml(lTxtHead || '#0f172a')}">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Light Body Text Color</span></label>
                            <div class="color-picker-row">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_light_txt_body" value="${escapeHtml(lTxtBody)}" placeholder="#334155 (Auto)">
                                <input type="color" class="color-swatch-input js-color-sync" value="${escapeHtml(lTxtBody || '#334155')}">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Light Button / Accent Color</span></label>
                            <div class="color-picker-row">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_light_txt_accent" value="${escapeHtml(lTxtAcc)}" placeholder="#059669 (Auto)">
                                <input type="color" class="color-swatch-input js-color-sync" value="${escapeHtml(lTxtAcc || '#059669')}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. BACKGROUND IMAGE, BORDERS, SHADOWS & TYPOGRAPHY GEOMETRY -->
                <div class="pb-studio-group" style="border-bottom:none; padding-bottom:0;">
                    <div class="mono-label text-emerald" style="margin-bottom:0.5rem;">4. BACKGROUND IMAGE UPLOAD, BORDER GEOMETRY, SHADOWS & TYPOGRAPHY</div>
                    <div class="grid-4-col">
                        <div class="field-group">
                            <label class="field-label"><span>BG Image (Upload or URL)</span></label>
                            <div style="display:flex; gap:0.4rem;">
                                <input type="file" class="field-input ${clsBgUpload}" ${attr} accept="image/*">
                                <input type="text" class="field-input mono-input ${clsField}" ${attr} data-key="_bg_img_url" value="${escapeHtml(bgImgUrl)}" placeholder="Image URL">
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>BG Fit & Position</span></label>
                            <div style="display:flex; gap:0.35rem;">
                                <select class="field-select ${clsField}" ${attr} data-key="_bg_img_size">
                                    <option value="cover" ${bgImgSize === 'cover' ? 'selected' : ''}>Cover</option>
                                    <option value="contain" ${bgImgSize === 'contain' ? 'selected' : ''}>Contain</option>
                                    <option value="100% 100%" ${bgImgSize === '100% 100%' ? 'selected' : ''}>Stretch</option>
                                </select>
                                <select class="field-select ${clsField}" ${attr} data-key="_bg_img_pos">
                                    <option value="center center" ${bgImgPos === 'center center' ? 'selected' : ''}>Center</option>
                                    <option value="top center" ${bgImgPos === 'top center' ? 'selected' : ''}>Top</option>
                                    <option value="bottom center" ${bgImgPos === 'bottom center' ? 'selected' : ''}>Bottom</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>BG Repeat & Gradient Angle</span></label>
                            <div style="display:flex; gap:0.35rem;">
                                <select class="field-select ${clsField}" ${attr} data-key="_bg_img_repeat">
                                    <option value="no-repeat" ${bgImgRep === 'no-repeat' ? 'selected' : ''}>No Repeat</option>
                                    <option value="repeat" ${bgImgRep === 'repeat' ? 'selected' : ''}>Tile</option>
                                    <option value="fixed" ${bgImgRep === 'fixed' ? 'selected' : ''}>Parallax</option>
                                </select>
                                <select class="field-select ${clsField}" ${attr} data-key="_bg_grad_dir">
                                    <option value="135deg" ${gradDir === '135deg' ? 'selected' : ''}>135°</option>
                                    <option value="90deg" ${gradDir === '90deg' ? 'selected' : ''}>90°</option>
                                    <option value="180deg" ${gradDir === '180deg' ? 'selected' : ''}>180°</option>
                                    <option value="radial" ${gradDir === 'radial' ? 'selected' : ''}>Radial</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Border Style & Width (px)</span></label>
                            <div style="display:flex; gap:0.35rem;">
                                <select class="field-select ${clsField}" ${attr} data-key="_bdr_style">
                                    <option value="default" ${bdrStyle === 'default' ? 'selected' : ''}>Default</option>
                                    <option value="none" ${bdrStyle === 'none' ? 'selected' : ''}>None</option>
                                    <option value="solid" ${bdrStyle === 'solid' ? 'selected' : ''}>Solid</option>
                                    <option value="dashed" ${bdrStyle === 'dashed' ? 'selected' : ''}>Dashed</option>
                                </select>
                                <input type="number" class="field-input mono-input ${clsField}" ${attr} data-key="_bdr_width" value="${escapeHtml(bdrWidth)}" min="0" max="16" style="width:70px;">
                            </div>
                        </div>
                    </div>

                    <div class="grid-4-col">
                        <div class="field-group"><label class="field-label"><span>Corner Radius & Shadow</span></label>
                            <div style="display:flex; gap:0.35rem;">
                                <select class="field-select ${clsField}" ${attr} data-key="_bdr_radius">
                                    <option value="default" ${bdrRadius === 'default' ? 'selected' : ''}>Default</option>
                                    <option value="0px" ${bdrRadius === '0px' ? 'selected' : ''}>0px</option>
                                    <option value="16px" ${bdrRadius === '16px' ? 'selected' : ''}>16px</option>
                                    <option value="32px" ${bdrRadius === '32px' ? 'selected' : ''}>32px</option>
                                    <option value="48px" ${bdrRadius === '48px' ? 'selected' : ''}>48px</option>
                                </select>
                                <select class="field-select ${clsField}" ${attr} data-key="_shadow_preset">
                                    <option value="default" ${shadowPre === 'default' ? 'selected' : ''}>Default</option>
                                    <option value="none" ${shadowPre === 'none' ? 'selected' : ''}>No Shadow</option>
                                    <option value="soft" ${shadowPre === 'soft' ? 'selected' : ''}>Soft</option>
                                    <option value="deep" ${shadowPre === 'deep' ? 'selected' : ''}>Deep 3D</option>
                                    <option value="glow" ${shadowPre === 'glow' ? 'selected' : ''}>✨ Glow</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Font Family</span></label>
                            <select class="field-select ${clsField}" ${attr} data-key="_txt_font">
                                <option value="default" ${txtFont === 'default' ? 'selected' : ''}>Inherit Global Font</option>
                                <option value="Inter" ${txtFont === 'Inter' ? 'selected' : ''}>Inter</option>
                                <option value="Poppins" ${txtFont === 'Poppins' ? 'selected' : ''}>Poppins</option>
                                <option value="Plus Jakarta Sans" ${txtFont === 'Plus Jakarta Sans' ? 'selected' : ''}>Plus Jakarta Sans</option>
                                <option value="Outfit" ${txtFont === 'Outfit' ? 'selected' : ''}>Outfit</option>
                                <option value="Space Grotesk" ${txtFont === 'Space Grotesk' ? 'selected' : ''}>Space Grotesk</option>
                                <option value="Playfair Display" ${txtFont === 'Playfair Display' ? 'selected' : ''}>Playfair Display</option>
                                <option value="JetBrains Mono" ${txtFont === 'JetBrains Mono' ? 'selected' : ''}>JetBrains Mono</option>
                            </select>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Text Align & Scale</span></label>
                            <div style="display:flex; gap:0.35rem;">
                                <select class="field-select ${clsField}" ${attr} data-key="_txt_align">
                                    <option value="default" ${txtAlign === 'default' ? 'selected' : ''}>Default</option>
                                    <option value="left" ${txtAlign === 'left' ? 'selected' : ''}>Left</option>
                                    <option value="center" ${txtAlign === 'center' ? 'selected' : ''}>Center</option>
                                    <option value="right" ${txtAlign === 'right' ? 'selected' : ''}>Right</option>
                                </select>
                                <select class="field-select ${clsField}" ${attr} data-key="_txt_scale">
                                    <option value="default" ${txtScale === 'default' ? 'selected' : ''}>1.0x</option>
                                    <option value="0.88" ${txtScale === '0.88' ? 'selected' : ''}>0.88x</option>
                                    <option value="1.15" ${txtScale === '1.15' ? 'selected' : ''}>1.15x</option>
                                    <option value="1.28" ${txtScale === '1.28' ? 'selected' : ''}>1.28x</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-group"><label class="field-label"><span>Padding (Vertical & Horizontal)</span></label>
                            <div style="display:flex; gap:0.35rem;">
                                <select class="field-select ${clsField}" ${attr} data-key="_pad_y">
                                    <option value="default" ${padY === 'default' ? 'selected' : ''}>Pad Y: Auto</option>
                                    <option value="1.25rem" ${padY === '1.25rem' ? 'selected' : ''}>Compact</option>
                                    <option value="2.5rem" ${padY === '2.5rem' ? 'selected' : ''}>Medium</option>
                                    <option value="4.25rem" ${padY === '4.25rem' ? 'selected' : ''}>Spacious</option>
                                </select>
                                <select class="field-select ${clsField}" ${attr} data-key="_pad_x">
                                    <option value="default" ${padX === 'default' ? 'selected' : ''}>Pad X: Auto</option>
                                    <option value="1.25rem" ${padX === '1.25rem' ? 'selected' : ''}>Compact</option>
                                    <option value="2.5rem" ${padX === '2.5rem' ? 'selected' : ''}>Medium</option>
                                    <option value="4rem" ${padX === '4rem' ? 'selected' : ''}>Wide</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    // ==========================================
    // 3. UNIFIED COMPONENT FORM GENERATOR (ALL 14 COMPONENTS — 100% ORIGINAL)
    // ==========================================
    const buildBlockFieldsHtml = function (blk, idx, isFooter, colIdx) {
        if (window.CMS_ADDON_BLOCK_RENDERERS && typeof window.CMS_ADDON_BLOCK_RENDERERS[blk.type] === 'function') {
            return window.CMS_ADDON_BLOCK_RENDERERS[blk.type](blk, idx, isFooter, colIdx);
        }
        const d = blk.data || {};
        const clsField = isFooter ? 'js-f-field' : 'js-blk-field';
        const clsRte = isFooter ? 'js-f-rte' : 'js-rte-area';
        const clsUpload = isFooter ? 'js-f-media-upload' : 'js-pb-media-upload';
        const attr = isFooter ? `data-col="${colIdx}" data-bidx="${idx}"` : `data-idx="${idx}"`;

        if (blk.type === 'split_hero') {
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
    // 4. CANVAS RENDERERS (ORIGINAL + DUAL-THEME DESIGN STUDIO BUTTON)
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
            const bgDarkCustom  = d._dark_bg_color || d._bg_color || '#11131c';
            const bgLightCustom = d._light_bg_color || '#ffffff';

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
                                <option value="custom" ${bgMode === 'custom' ? 'selected' : ''}>🖌 Custom Dual Color (Dark/Light)</option>
                                <option value="gradient" ${bgMode === 'gradient' ? 'selected' : ''}>🌈 Dual Gradient (Dark/Light)</option>
                                <option value="image" ${bgMode === 'image' ? 'selected' : ''}>🖼 BG Image + Dual Overlay</option>
                            </select>
                            <input type="color"
                                   class="color-swatch-xs js-blk-field ${bgMode === 'custom' ? '' : 'is-hidden'}"
                                   data-idx="${idx}"
                                   data-key="_dark_bg_color"
                                   title="Dark Mode BG Color"
                                   value="${escapeHtml(bgDarkCustom)}">
                            <input type="color"
                                   class="color-swatch-xs js-blk-field ${bgMode === 'custom' ? '' : 'is-hidden'}"
                                   data-idx="${idx}"
                                   data-key="_light_bg_color"
                                   title="Light Mode BG Color"
                                   value="${escapeHtml(bgLightCustom)}">
                            <button type="button" class="btn-action js-toggle-design-studio" data-idx="${idx}">🎨 Dual-Theme Studio ▾</button>
                        </div>
                        <div class="action-btn-group">
                            <button type="button" class="btn-action js-pb-move-up" data-idx="${idx}">↑ Up</button>
                            <button type="button" class="btn-action js-pb-move-down" data-idx="${idx}">↓ Down</button>
                            <button type="button" class="btn-action btn-action-danger js-pb-remove-block" data-idx="${idx}">✕ Remove</button>
                        </div>
                    </div>
                    ${buildDesignStudioDrawerHtml(d, idx, false, 0)}
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
                const d = blk.data || {};
                return `
                    <div class="pb-block-card">
                        <div class="pb-block-header" style="flex-wrap:wrap; gap:0.4rem;">
                            <span class="role-badge role-admin">0${bIdx + 1} // ${escapeHtml(blk.type.toUpperCase())}</span>
                            <div class="action-btn-group">
                                <button type="button" class="btn-action js-toggle-f-studio" data-col="${c}" data-bidx="${bIdx}">🎨 Dual-Theme Style</button>
                                <button type="button" class="btn-action js-f-move-up" data-col="${c}" data-bidx="${bIdx}">↑</button>
                                <button type="button" class="btn-action js-f-move-down" data-col="${c}" data-bidx="${bIdx}">↓</button>
                                <button type="button" class="btn-action btn-action-danger js-f-rm-blk" data-col="${c}" data-bidx="${bIdx}">✕</button>
                            </div>
                        </div>
                        ${buildDesignStudioDrawerHtml(d, bIdx, true, c)}
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
        const sidebarToggle = event.target.closest('#sidebar-toggle-btn');
        if (sidebarToggle) {
            const sidebar = document.getElementById('app-sidebar');
            if (sidebar) sidebar.classList.toggle('is-open');
            return;
        }

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

        const studioBtn = event.target.closest('.js-toggle-design-studio');
        if (studioBtn) {
            event.preventDefault();
            event.stopPropagation();
            const idx = parseInt(studioBtn.getAttribute('data-idx'), 10);
            const card = studioBtn.closest('.pb-block-card');
            const drawer = card ? card.querySelector('.pb-design-studio-drawer') : null;
            if (drawer && pbBlocks[idx]) {
                const willOpen = drawer.classList.contains('is-hidden');
                drawer.classList.toggle('is-hidden');
                pbBlocks[idx].data._studio_open = willOpen ? '1' : '0';
                syncBlocksToHiddenInput();
            }
            return;
        }

        const fStudioBtn = event.target.closest('.js-toggle-f-studio');
        if (fStudioBtn) {
            event.preventDefault();
            event.stopPropagation();
            const c = parseInt(fStudioBtn.getAttribute('data-col'), 10);
            const b = parseInt(fStudioBtn.getAttribute('data-bidx'), 10);
            const card = fStudioBtn.closest('.pb-block-card');
            const drawer = card ? card.querySelector('.pb-design-studio-drawer') : null;
            if (drawer && footerSections[c] && footerSections[c][b]) {
                const willOpen = drawer.classList.contains('is-hidden');
                drawer.classList.toggle('is-hidden');
                footerSections[c][b].data._studio_open = willOpen ? '1' : '0';
                syncFooterJson();
            }
            return;
        }

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

        const clearBtn = event.target.closest('#btn-pb-clear-all');
        if (clearBtn) {
            event.preventDefault();
            if (window.confirm('Clear all blocks from this page canvas?')) {
                pbBlocks = [];
                renderBuilderCanvas();
            }
            return;
        }

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

        const closeAnn = event.target.closest('.js-close-ann');
        if (closeAnn) {
            const bar = closeAnn.closest('.cms-announcement-bar');
            if (bar) bar.remove();
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

        const colorSync = event.target.closest('.js-color-sync');
        if (colorSync && colorSync.previousElementSibling) {
            const txtInp = colorSync.previousElementSibling;
            txtInp.value = colorSync.value;
            txtInp.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }

        const blkField = event.target.closest('.js-blk-field');
        if (blkField) {
            const idx = parseInt(blkField.getAttribute('data-idx'), 10);
            const key = blkField.getAttribute('data-key');
            if (pbBlocks[idx] && key) {
                pbBlocks[idx].data[key] = blkField.value;
                if (key === '_dark_bg_color') {
                    pbBlocks[idx].data._bg_color = blkField.value;
                }
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
                if (k === '_dark_bg_color') {
                    footerSections[c][b].data._bg_color = fField.value;
                }
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

        if (target.classList.contains('sr-file') && target.files && target.files[0]) {
            const tile = target.closest('.upload-tile');
            const titleEl = tile ? tile.querySelector('.upload-title') : null;
            if (titleEl) titleEl.textContent = target.files[0].name;
        }

        if (target.classList.contains('js-blk-field')) {
            const key = target.getAttribute('data-key');
            const idx = parseInt(target.getAttribute('data-idx'), 10);
            if (pbBlocks[idx] && key) {
                pbBlocks[idx].data[key] = target.value;
                if (key === '_dark_bg_color') {
                    pbBlocks[idx].data._bg_color = target.value;
                }
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

        if (
            target.classList.contains('js-pb-media-upload') ||
            target.classList.contains('js-f-media-upload') ||
            target.classList.contains('js-pb-bg-upload') ||
            target.classList.contains('js-f-bg-upload')
        ) {
            if (!target.files || target.files.length === 0) return;
            const isFooter = target.classList.contains('js-f-media-upload') || target.classList.contains('js-f-bg-upload');
            const isBgStudio = target.classList.contains('js-pb-bg-upload') || target.classList.contains('js-f-bg-upload');
            const baseUrl = (document.body.getAttribute('data-base-url') || '/').replace(/\/$/, '');
            const uploadUrl = baseUrl + '/addons/page-builder/upload-media';

            const formData = new FormData();
            formData.append('media_file', target.files[0]);

            fetch(uploadUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    const uploadedUrl = res.image_url || res.file_url || '';
                    if (res.status === 'success' && uploadedUrl) {
                        if (isFooter) {
                            const c = parseInt(target.getAttribute('data-col'), 10);
                            const b = parseInt(target.getAttribute('data-bidx'), 10);
                            if (footerSections[c] && footerSections[c][b]) {
                                if (isBgStudio) {
                                    footerSections[c][b].data._bg_img_url = uploadedUrl;
                                    footerSections[c][b].data._bg_mode = 'image';
                                    footerSections[c][b].data._studio_open = '1';
                                } else {
                                    footerSections[c][b].data.image_url = uploadedUrl;
                                }
                                renderFooterBuilder();
                            }
                        } else {
                            const idx = parseInt(target.getAttribute('data-idx'), 10);
                            if (pbBlocks[idx]) {
                                if (isBgStudio) {
                                    pbBlocks[idx].data._bg_img_url = uploadedUrl;
                                    pbBlocks[idx].data._bg_mode = 'image';
                                    pbBlocks[idx].data._studio_open = '1';
                                } else {
                                    pbBlocks[idx].data.image_url = uploadedUrl;
                                }
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
    // 8. HTML5 DRAG & DROP ENGINE
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
    // 9. BOOT CANVASES, TOP LOADER, LAZY BG IMAGES & REVEAL
    // ==========================================
    document.addEventListener('cms:addon-renderer-ready', function () {
        if (document.getElementById('pb-canvas-dropzone')) {
            renderBuilderCanvas();
        }
        if (document.getElementById('pb-footer-columns-wrap')) {
            renderFooterBuilder();
        }
    });

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

        const topLoader = document.getElementById('edustray-top-loader');
        if (topLoader) {
            topLoader.style.width = '75%';
            window.addEventListener('load', function () {
                topLoader.style.width = '100%';
                setTimeout(function () { topLoader.style.opacity = '0'; }, 350);
            });
        }

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

        // Theme-Aware Lazy Below-the-Fold Background Image Loader
        const applyLazyBg = function (node) {
            const imgUrl = node.getAttribute('data-bg-lazy');
            if (!imgUrl) return;
            node.removeAttribute('data-bg-lazy');

            const currentTheme = (document.documentElement.getAttribute('data-theme') || 'dark').toLowerCase();
            const ovCol = node.getAttribute('data-bg-overlay') || (currentTheme === 'light' ? '#ffffff' : '#090a0f');
            const ovOp  = parseInt(node.getAttribute('data-bg-opacity') || '55', 10);
            const target = node.classList.contains('cms-block') || node.classList.contains('cms-footer-block-box')
                ? node
                : (node.querySelector('.cms-block') || node);
            const safeUrl = imgUrl.replace(/['"();]/g, '');
            if (ovOp > 0) {
                const rgba = hexToRgba(ovCol, Math.max(0, Math.min(1, ovOp / 100)));
                target.style.setProperty('background-image', 'linear-gradient(' + rgba + ', ' + rgba + '), url("' + safeUrl + '")', 'important');
            } else {
                target.style.setProperty('background-image', 'url("' + safeUrl + '")', 'important');
            }
        };

        if ('IntersectionObserver' in window) {
            const revealObserver = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        if (entry.target.hasAttribute('data-bg-lazy')) {
                            applyLazyBg(entry.target);
                        }
                        obs.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '200px 0px', threshold: 0.05 });

            document.querySelectorAll('.js-lazy-reveal, [data-bg-lazy]').forEach(function (el) {
                revealObserver.observe(el);
            });
        } else {
            document.querySelectorAll('.js-lazy-reveal').forEach(function (el) { el.classList.add('is-revealed'); });
            document.querySelectorAll('[data-bg-lazy]').forEach(applyLazyBg);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootCanvases);
    } else {
        bootCanvases();
    }
})();