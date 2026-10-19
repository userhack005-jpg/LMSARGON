import os
import re
import shutil
import zipfile

def sanitize_code(text):
    text = text.replace("\\vert", "||")
    text = text.replace("\x0b", "\\v").replace("\x0c", "\\f").replace("\u00a0", " ")
    text = re.sub(r"[\x00-\x08\x0b\x0c\x0e-\x1f]", "", text)
    return text.replace("\r\n", "\n")

def write_to_path(full_path, content):
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, "w", encoding="utf-8", newline="\n") as f:
        f.write(sanitize_code(content))

print("================================================================")
print("UPGRADING EXISTING ADD-ONS (FAQs & Announcements -> Dual Theme v2.3.0)")
print("================================================================")

# ==============================================================================
# 1. UPGRADE & RE-ZIP: FAQs Add-On
# ==============================================================================
faq_dir = os.path.join("writable", "addons", "extracted", "faqs")
if os.path.exists(faq_dir):
    # Update addon.json config defaults for FAQs
    faq_json_path = os.path.join(faq_dir, "addon.json")
    if os.path.exists(faq_json_path):
        with open(faq_json_path, "r", encoding="utf-8") as f:
            jdata = f.read()
        if "color_mode" not in jdata:
            jdata_new = jdata.replace(
                '"default_config": {',
                '"default_config": {\n        "color_mode": "auto",\n        "card_bg_dark": "#171a26",\n        "text_dark": "#f8fafc",\n        "accent_dark": "#10b981",\n        "card_bg_light": "#ffffff",\n        "text_light": "#0f172a",\n        "accent_light": "#059669",'
            )
            with open(faq_json_path, "w", encoding="utf-8", newline="\n") as f:
                f.write(sanitize_code(jdata_new))
            print("[UPGRADED] FAQ addon.json config schema.")

    # Update FAQs block_view.php with Dual Theme data attributes
    faq_block_view = os.path.join(faq_dir, "Views", "addons", "faqs", "block_view.php")
    faq_block_code = r"""<?php
    $modelPath = WRITEPATH . 'addons/extracted/faqs/Models/Addons/Faqs/FaqModel.php';
    if (! class_exists(\App\Models\Addons\Faqs\FaqModel::class) && is_file($modelPath)) {
        require_once $modelPath;
    }

    $fd          = $data ?? [];
    $fAccCol     = ! empty($fd['accent_color']) ? $fd['accent_color'] : '#10b981';
    $fBdrCol     = ! empty($fd['border_color']) ? $fd['border_color'] : '#232738';
    $expFirst    = ($fd['expand_first'] ?? '1') === '1';
    $boxPos      = ! empty($fd['box_position']) ? $fd['box_position'] : 'center';
    $txtAlign    = ! empty($fd['text_align']) ? $fd['text_align'] : 'left';
    $catFilter   = trim((string) ($fd['category'] ?? 'all'));
    if ($catFilter === '') {
        $catFilter = 'all';
    }
    $colorMode   = ($fd['color_mode'] ?? 'auto') === 'dual' ? 'dual' : 'auto';
    $cardBgDark  = ! empty($fd['card_bg_dark']) ? $fd['card_bg_dark'] : '#171a26';
    $textDark    = ! empty($fd['text_dark']) ? $fd['text_dark'] : '#f8fafc';
    $accentDark  = ! empty($fd['accent_dark']) ? $fd['accent_dark'] : $fAccCol;
    $cardBgLight = ! empty($fd['card_bg_light']) ? $fd['card_bg_light'] : '#ffffff';
    $textLight   = ! empty($fd['text_light']) ? $fd['text_light'] : '#0f172a';
    $accentLight = ! empty($fd['accent_light']) ? $fd['accent_light'] : '#059669';
    $customBg    = $fd['_bg_color'] ?? '';
    $bgMode      = $fd['_bg_mode'] ?? 'default';

    $faqModel = new \App\Models\Addons\Faqs\FaqModel();
    $dbFaqs   = $faqModel->getByCategory($catFilter);
    if (empty($dbFaqs) && strtolower($catFilter) !== 'all') {
        $dbFaqs = $faqModel->getByCategory('all');
    }
?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> cms-faq-section is-revealed"
         data-color-mode="<?= esc($colorMode) ?>"
         data-card-bg-dark="<?= esc($cardBgDark) ?>"
         data-text-dark="<?= esc($textDark) ?>"
         data-accent-dark="<?= esc($accentDark) ?>"
         data-card-bg-light="<?= esc($cardBgLight) ?>"
         data-text-light="<?= esc($textLight) ?>"
         data-accent-light="<?= esc($accentLight) ?>"
         data-border-color="<?= esc($fBdrCol) ?>"
         data-custom-bg="<?= ($bgMode === 'custom' && $customBg !== '') ? esc($customBg) : '' ?>">
    <div class="cms-inner-container">
        <div class="cms-faq-wrapper box-pos-<?= esc($boxPos) ?> txt-align-<?= esc($txtAlign) ?>">
            <div class="cms-section-head">
                <?php if (! empty($fd['badge'])): ?>
                    <div class="status-pill"><span class="mono-label"><?= esc($fd['badge']) ?></span></div>
                <?php endif; ?>
                <h2><?= esc(! empty($fd['section_title']) ? $fd['section_title'] : 'Frequently Asked Questions') ?></h2>
                <?php if (! empty($fd['subtitle'])): ?>
                    <p class="muted-cell"><?= esc($fd['subtitle']) ?></p>
                <?php endif; ?>
            </div>

            <div class="cms-faq-list">
                <?php if (empty($dbFaqs)): ?>
                    <div class="panel-card"><p class="muted-cell">No FAQs found for category "<?= esc($catFilter) ?>".</p></div>
                <?php else: ?>
                    <?php foreach ($dbFaqs as $fIdx => $fq): ?>
                        <?php $isOpen = ($expFirst && $fIdx === 0); ?>
                        <div class="cms-faq-item <?= $isOpen ? 'is-open' : '' ?>">
                            <button type="button" class="cms-faq-trigger js-faq-toggle">
                                <span class="cms-faq-q-text"><?= esc($fq['question']) ?></span>
                                <span class="cms-faq-icon"><?= $isOpen ? '−' : '+' ?></span>
                            </button>
                            <div class="cms-faq-body">
                                <p><?= nl2br(esc($fq['answer'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
"""
    write_to_path(faq_block_view, faq_block_code)

    # Update FAQ assets/faq.css with Dual Theme variables
    faq_css_path = os.path.join(faq_dir, "assets", "faq.css")
    faq_css_code = r"""/* DYNAMIC FAQ ADD-ON EXTERNAL STYLESHEET (assets/faq.css) */
.faq-addon-workspace, .faq-addon-form { overflow: visible !important; height: auto !important; width: 100%; box-sizing: border-box; }
.faq-addon-form { margin-bottom: 1.5rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.faq-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; width: 100%; }
.faq-table-scroll { width: 100%; overflow-x: auto; }
.faq-fluid-table { width: 100%; border-collapse: collapse; table-layout: auto; }
.faq-fluid-table .faq-col-order { width: 8%; }
.faq-fluid-table .faq-col-cat { width: 15%; }
.faq-fluid-table .faq-col-main { width: 47%; }
.faq-fluid-table .faq-col-status { width: 12%; }
.faq-fluid-table .faq-col-actions { width: 18%; }
.faq-fluid-table .faq-cell-wrap { white-space: normal !important; word-break: break-word; line-height: 1.5; }
.faq-inline-form { display: inline-flex; margin: 0; }

.cms-faq-section {
    --faq-card-bg-dark: #171a26;
    --faq-text-dark: #f8fafc;
    --faq-accent-dark: #10b981;
    --faq-card-bg-light: #ffffff;
    --faq-text-light: #0f172a;
    --faq-accent-light: #059669;

    --faq-active-bg: var(--bg-elevated, #171a26);
    --faq-active-text: var(--text-primary, #f8fafc);
    --faq-active-accent: #10b981;
    --faq-border: #232738;
}

.cms-faq-section[data-color-mode="dual"] {
    --faq-active-bg: var(--faq-card-bg-dark);
    --faq-active-text: var(--faq-text-dark);
    --faq-active-accent: var(--faq-accent-dark);
}

html[data-theme="light"] .cms-faq-section[data-color-mode="dual"],
body[data-theme="light"] .cms-faq-section[data-color-mode="dual"],
body.light-theme .cms-faq-section[data-color-mode="dual"] {
    --faq-active-bg: var(--faq-card-bg-light);
    --faq-active-text: var(--faq-text-light);
    --faq-active-accent: var(--faq-accent-light);
}

.cms-faq-wrapper { max-width: 54rem; width: 100%; }
.cms-faq-wrapper.box-pos-left { margin-left: 0; margin-right: auto; }
.cms-faq-wrapper.box-pos-center { margin-left: auto; margin-right: auto; }
.cms-faq-wrapper.box-pos-right { margin-left: auto; margin-right: 0; }
.cms-faq-wrapper.txt-align-left .cms-section-head, .cms-faq-wrapper.txt-align-left .cms-faq-q-text, .cms-faq-wrapper.txt-align-left .cms-faq-body { text-align: left; }
.cms-faq-wrapper.txt-align-center .cms-section-head, .cms-faq-wrapper.txt-align-center .cms-faq-q-text, .cms-faq-wrapper.txt-align-center .cms-faq-body { text-align: center; }
.cms-faq-wrapper.txt-align-right .cms-section-head, .cms-faq-wrapper.txt-align-right .cms-faq-q-text, .cms-faq-wrapper.txt-align-right .cms-faq-body { text-align: right; }

.cms-faq-list { display: flex; flex-direction: column; gap: 0.85rem; width: 100%; }
.cms-faq-item { border: 1px solid var(--faq-border); border-radius: 0.75rem; background: var(--faq-active-bg); color: var(--faq-active-text); overflow: hidden; }
.cms-faq-trigger { width: 100%; background: transparent; border: none; padding: 1.1rem 1.25rem; color: var(--faq-active-text); font-size: 1rem; font-weight: 600; display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
.cms-faq-icon { color: var(--faq-active-accent); font-size: 1.25rem; font-weight: 700; }
.cms-faq-body { display: none; padding: 0 1.25rem 1.15rem 1.25rem; color: var(--faq-active-text); opacity: 0.85; line-height: 1.65; }
.cms-faq-item.is-open .cms-faq-body { display: block; }
"""
    write_to_path(faq_css_path, faq_css_code)

    # Package faqs_addon.zip
    shutil.make_archive("faqs_addon", "zip", faq_dir)
    print("[SUCCESS] Upgraded & re-zipped faqs_addon.zip")


# ==============================================================================
# 2. UPGRADE & RE-ZIP: Announcements Add-On
# ==============================================================================
ann_dir = os.path.join("writable", "addons", "extracted", "announcements")
if os.path.exists(ann_dir):
    ann_json_path = os.path.join(ann_dir, "addon.json")
    if os.path.exists(ann_json_path):
        with open(ann_json_path, "r", encoding="utf-8") as f:
            jdata = f.read()
        if "color_mode" not in jdata:
            jdata_new = jdata.replace(
                '"default_config": {',
                '"default_config": {\n        "color_mode": "auto",\n        "bg_dark": "#10b981",\n        "text_dark": "#04130d",\n        "bg_light": "#059669",\n        "text_light": "#ffffff",'
            )
            with open(ann_json_path, "w", encoding="utf-8", newline="\n") as f:
                f.write(sanitize_code(jdata_new))
            print("[UPGRADED] Announcements addon.json config schema.")

    # Package announcements_addon.zip
    shutil.make_archive("announcements_addon", "zip", ann_dir)
    print("[SUCCESS] Re-zipped announcements_addon.zip")

print("================================================================")
print("ALL EXISTING ADD-ONS SUCCESSFULLY UPGRADED & ZIPPED!")
print("================================================================")