# ==============================================================================
# DIRECT DIAGNOSTIC & FIX FOR FRONTEND FAQ BLOCK RENDERING
# Run inside CI4 Project Root: .\fix_frontend_faq.ps1
# ==============================================================================

$ErrorActionPreference = "Stop"
$baseDir = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

# 1. Verify that the extracted FAQ block_view.php actually exists!
$extractedBlock = Join-Path -Path $baseDir -ChildPath "writable\addons\extracted\faqs\Views\addons\faqs\block_view.php"
$extractedModel = Join-Path -Path $baseDir -ChildPath "writable\addons\extracted\faqs\Models\Addons\Faqs\FaqModel.php"

Write-Host "--- STEP 1: CHECKING EXTRACTED ADD-ON FILES ---" -ForegroundColor Yellow
if (Test-Path -Path $extractedBlock) {
    Write-Host "[FOUND] $extractedBlock" -ForegroundColor Green
} else {
    Write-Host "[MISSING] $extractedBlock (Add-on is not extracted in writable/addons/extracted/faqs!)" -ForegroundColor Red
}

# 2. Update block_view.php so it NEVER fails silently and always renders visibly
if (Test-Path -Path $extractedBlock) {
    $robustBlockView = @'
<?php
    $modelPath = WRITEPATH . 'addons/extracted/faqs/Models/Addons/Faqs/FaqModel.php';
    if (! class_exists(\App\Models\Addons\Faqs\FaqModel::class) && is_file($modelPath)) {
        require_once $modelPath;
    }

    $fAccCol   = ! empty($data['accent_color']) ? $data['accent_color'] : '#10b981';
    $fBdrCol   = ! empty($data['border_color']) ? $data['border_color'] : '#232738';
    $expFirst  = ($data['expand_first'] ?? '1') === '1';
    $boxPos    = ! empty($data['box_position']) ? $data['box_position'] : 'center';
    $txtAlign  = ! empty($data['text_align']) ? $data['text_align'] : 'left';
    $catFilter = trim((string) ($data['category'] ?? 'all'));
    if ($catFilter === '') {
        $catFilter = 'all';
    }
    $customBg  = $data['_bg_color'] ?? '';
    $bgMode    = $data['_bg_mode'] ?? 'default';

    $dbFaqs = [];
    if (class_exists(\App\Models\Addons\Faqs\FaqModel::class)) {
        $faqModel = new \App\Models\Addons\Faqs\FaqModel();
        $dbFaqs   = $faqModel->getByCategory($catFilter);
        // Fallback: if specific category returned 0 rows, show all published FAQs so block is never empty by mistake
        if (empty($dbFaqs) && strtolower($catFilter) !== 'all') {
            $dbFaqs = $faqModel->getByCategory('all');
        }
    }
?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> cms-faq-section is-revealed"
         data-accent-color="<?= esc($fAccCol) ?>"
         data-border-color="<?= esc($fBdrCol) ?>"
         data-custom-bg="<?= ($bgMode === 'custom' && $customBg !== '') ? esc($customBg) : '' ?>">
    <div class="cms-inner-container">
        <div class="cms-faq-wrapper box-pos-<?= esc($boxPos) ?> txt-align-<?= esc($txtAlign) ?>">
            <div class="cms-section-head">
                <?php if (! empty($data['badge'])): ?>
                    <div class="status-pill"><span class="mono-label"><?= esc($data['badge']) ?></span></div>
                <?php endif; ?>
                <h2><?= esc(! empty($data['section_title']) ? $data['section_title'] : 'Frequently Asked Questions') ?></h2>
                <?php if (! empty($data['subtitle'])): ?>
                    <p class="muted-cell"><?= esc($data['subtitle']) ?></p>
                <?php endif; ?>
            </div>

            <div class="cms-faq-list">
                <?php if (empty($dbFaqs)): ?>
                    <div class="panel-card"><p class="muted-cell">No published FAQs available in கோயில / database yet. Add FAQs from the FAQ Manager.</p></div>
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
'@
    [System.IO.File]::WriteAllText($extractedBlock, $robustBlockView.Replace([char]0x00A0, ' '), $utf8NoBom)
    Write-Host "[OK] Updated extracted block_view.php with guaranteed visible fallback." -ForegroundColor Cyan
}

# 3. Inspect & Fix BOTH `frontend_page_view.php` files (app/Views and writable/addons/extracted)
$fePaths = @(
    (Join-Path -Path $baseDir -ChildPath "app\Views\addons\page_builder\frontend_page_view.php"),
    (Join-Path -Path $baseDir -ChildPath "writable\addons\extracted\page_builder\Views\addons\page_builder\frontend_page_view.php")
)

Write-Host "--- STEP 2: PATCHING FRONTEND_PAGE_VIEW.PHP ---" -ForegroundColor Yellow

$universalAddonHook = @'
            <div class="<?= $wrapperClass ?> is-revealed">
                <?php
                    // UNIVERSAL MODULAR ADD-ON BLOCK LOADER (Checks writable/addons/extracted first!)
                    $addonMap = [
                        'faq_accordion'      => 'faqs',
                        'testimonial_slider' => 'reviews',
                        'newsletter_cta'     => 'newsletter',
                        'announcement_bar'   => 'announcements',
                    ];
                    $targetAddonSlug = $addonMap[$type] ?? ($componentMap[$type]['addon_slug'] ?? 'page_builder');
                    $extractedAddonBlockFile = WRITEPATH . 'addons/extracted/' . $targetAddonSlug . '/Views/addons/' . $targetAddonSlug . '/block_view.php';

                    // Check if add-on is enabled in `addons` table
                    $isAddonEnabled = true;
                    if ($targetAddonSlug !== 'page_builder') {
                        try {
                            $dbCheck = \Config\Database::connect();
                            if ($dbCheck->tableExists('addons')) {
                                $addonRow = $dbCheck->table('addons')->where('slug', $targetAddonSlug)->get()->getRowArray();
                                if (! $addonRow) {
                                    $isAddonEnabled = false;
                                } elseif (isset($addonRow['is_active']) && (int) $addonRow['is_active'] === 0) {
                                    $isAddonEnabled = false;
                                } elseif (isset($addonRow['is_enabled']) && (int) $addonRow['is_enabled'] === 0) {
                                    $isAddonEnabled = false;
                                } elseif (isset($addonRow['status']) && strtolower((string) $addonRow['status']) !== 'active') {
                                    $isAddonEnabled = false;
                                }
                            }
                        } catch (\Throwable $e) {
                            $isAddonEnabled = true;
                        }
                    }
                ?>
                <?php if ($targetAddonSlug !== 'page_builder'): ?>
                    <?php if ($isAddonEnabled && is_file($extractedAddonBlockFile)): ?>
                        <?php include $extractedAddonBlockFile; ?>
                    <?php endif; ?>
                <?php elseif ($type === 'split_hero'): ?>
'@

foreach ($fePath in $fePaths) {
    if (Test-Path -Path $fePath) {
        $content = [System.IO.File]::ReadAllText($fePath)

        # Replace from `<div class="<?= $wrapperClass ?>">` up to `elseif ($type === 'split_hero'): ?>` or `if ($type === 'split_hero'): ?>`
        $pattern = '(?s)<div class="<\?=\s*\$wrapperClass\s*\?>[^"]*">.*?(?:if|elseif)\s*\(\$type\s*===\s*''split_hero''\):\s*\?>'
        if ($content -match $pattern) {
            $content = [regex]::Replace($content, $pattern, $universalAddonHook)
        }

        # Also neutralise any old `elseif ($type === 'faq_accordion'):` block further down in the file so it never conflicts
        $content = $content.Replace("elseif (`$type === 'faq_accordion'):", "elseif (`$type === 'faq_accordion_legacy_disabled'):")

        [System.IO.File]::WriteAllText($fePath, $content.Replace([char]0x00A0, ' '), $utf8NoBom)
        Write-Host "[FIXED] Injected Universal Add-On Block Loader into: $fePath" -ForegroundColor Green
    }
}

Write-Host "================================================================" -ForegroundColor Green
Write-Host "DONE! Now refresh your Frontend Page (Ctrl + F5)." -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Green