# ==============================================================================
# Diagnostic + safe patch for CI4 frontend FAQ/add-on block rendering
# Run from the CI4 project root, with this script saved in that root.
# Creates timestamped .bak files and PHP-lints staged changes when `php` is on PATH.
# ==============================================================================

$ErrorActionPreference = 'Stop'
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
$phpExe = if ($phpCommand -and $phpCommand.Source) { $phpCommand.Source } else { $null }
# XAMPP commonly keeps PHP outside PATH; use it when available so linting is not skipped.
if (-not $phpExe -and (Test-Path -LiteralPath 'C:\xampp\php\php.exe' -PathType Leaf)) {
    $phpExe = 'C:\xampp\php\php.exe'
}

function Read-TextFile {
    param([Parameter(Mandatory = $true)][string] $Path)
    return [System.IO.File]::ReadAllText($Path)
}

function Add-Candidate {
    param(
        # The list is intentionally empty when the first candidate is added.
        # Mandatory parameters reject empty collections unless this attribute is set.
        [Parameter(Mandatory = $true)]
        [AllowEmptyCollection()]
        [System.Collections.Generic.List[object]] $List,
        [Parameter(Mandatory = $true)][string] $Path,
        [Parameter(Mandatory = $true)][string] $Content,
        [Parameter(Mandatory = $true)][string] $Label
    )

    $oldContent = Read-TextFile -Path $Path
    if ($oldContent -cne $Content) {
        $List.Add([pscustomobject]@{
            Path    = $Path
            Content = $Content
            Label   = $Label
            Backup  = "$Path.bak.$timestamp"
        })
    } else {
        Write-Host "[UNCHANGED] $Path" -ForegroundColor DarkGray
    }
}

function Test-PhpSyntax {
    param(
        [Parameter(Mandatory = $true)][string] $Path,
        [Parameter(Mandatory = $true)][string] $Content
    )

    $tempPath = "$Path.lint.$timestamp.php"
    try {
        [System.IO.File]::WriteAllText($tempPath, $Content, $script:utf8NoBom)
        $lintOutput = & $script:phpExe -l $tempPath 2>&1
        $lintExitCode = $LASTEXITCODE
        if ($lintExitCode -ne 0) {
            throw "PHP syntax validation failed for '$Path':`n$($lintOutput -join [Environment]::NewLine)"
        }
        Write-Host "[PHP LINT OK] $Path" -ForegroundColor Green
    } finally {
        if (Test-Path -LiteralPath $tempPath) {
            Remove-Item -LiteralPath $tempPath -Force -ErrorAction SilentlyContinue
        }
    }
}

$projectRoot = $PSScriptRoot
$extractedBlock = Join-Path -Path $projectRoot -ChildPath 'writable\addons\extracted\faqs\Views\addons\faqs\block_view.php'
$extractedModel = Join-Path -Path $projectRoot -ChildPath 'writable\addons\extracted\faqs\Models\Addons\Faqs\FaqModel.php'

$fePaths = @(
    (Join-Path -Path $projectRoot -ChildPath 'app\Views\addons\page_builder\frontend_page_view.php'),
    (Join-Path -Path $projectRoot -ChildPath 'writable\addons\extracted\page_builder\Views\addons\page_builder\frontend_page_view.php')
)

Write-Host '--- STEP 1: CHECKING EXTRACTED FAQ FILES ---' -ForegroundColor Yellow
if (Test-Path -LiteralPath $extractedBlock -PathType Leaf) {
    Write-Host "[FOUND] $extractedBlock" -ForegroundColor Green
} else {
    Write-Host "[MISSING] $extractedBlock (FAQ block view will not be rewritten.)" -ForegroundColor Red
}

if (Test-Path -LiteralPath $extractedModel -PathType Leaf) {
    Write-Host "[FOUND] $extractedModel" -ForegroundColor Green
} else {
    Write-Host "[MISSING] $extractedModel (The block will render a visible diagnostic if the model cannot be autoloaded.)" -ForegroundColor Yellow
}

$candidates = New-Object 'System.Collections.Generic.List[object]'
$preflightErrors = New-Object 'System.Collections.Generic.List[string]'

# A. Robust FAQ block view. Keep the original FAQ settings, category behavior,
# first-item expansion, and HTML classes; add safe error reporting for load failures.
if (Test-Path -LiteralPath $extractedBlock -PathType Leaf) {
    $robustBlockView = @'
<?php
    $faqModelClass = \App\Models\Addons\Faqs\FaqModel::class;
    $modelPath = WRITEPATH . 'addons/extracted/faqs/Models/Addons/Faqs/FaqModel.php';
    $faqConfig = is_array($data ?? null) ? $data : [];

    $fAccCol = ! empty($faqConfig['accent_color']) ? $faqConfig['accent_color'] : '#10b981';
    $fBdrCol = ! empty($faqConfig['border_color']) ? $faqConfig['border_color'] : '#232738';
    $expFirst = (string) ($faqConfig['expand_first'] ?? '1') === '1';
    $boxPos = ! empty($faqConfig['box_position']) ? $faqConfig['box_position'] : 'center';
    $txtAlign = ! empty($faqConfig['text_align']) ? $faqConfig['text_align'] : 'left';
    $catFilter = trim((string) ($faqConfig['category'] ?? 'all'));
    if ($catFilter === '' || strtolower($catFilter) === 'all') {
        $catFilter = 'all';
    }
    $customBg = $faqConfig['_bg_color'] ?? '';
    $bgMode = $faqConfig['_bg_mode'] ?? 'default';

    $dbFaqs = [];
    $faqLoadError = false;

    try {
        if (! class_exists($faqModelClass) && is_file($modelPath)) {
            require_once $modelPath;
        }

        if (! class_exists($faqModelClass)) {
            $faqLoadError = true;
            if (function_exists('log_message')) {
                log_message('error', 'FAQ block: FaqModel class could not be loaded. Expected model path: ' . $modelPath);
            }
        } else {
            $faqModel = new \App\Models\Addons\Faqs\FaqModel();
            if (! method_exists($faqModel, 'getByCategory')) {
                throw new \RuntimeException('FaqModel does not implement getByCategory().');
            }

            $dbFaqs = $faqModel->getByCategory($catFilter);
            if ($dbFaqs instanceof \Traversable) {
                $dbFaqs = iterator_to_array($dbFaqs);
            }
            if (! is_array($dbFaqs)) {
                throw new \UnexpectedValueException('FaqModel::getByCategory() must return an array or Traversable.');
            }

            // Keep the existing fallback: if a selected category has no rows,
            // retry with all published FAQs.
            if (empty($dbFaqs) && strtolower($catFilter) !== 'all') {
                $dbFaqs = $faqModel->getByCategory('all');
                if ($dbFaqs instanceof \Traversable) {
                    $dbFaqs = iterator_to_array($dbFaqs);
                }
                if (! is_array($dbFaqs)) {
                    throw new \UnexpectedValueException('FaqModel::getByCategory() fallback must return an array or Traversable.');
                }
            }
        }
    } catch (\Throwable $e) {
        $dbFaqs = [];
        $faqLoadError = true;
        if (function_exists('log_message')) {
            log_message('error', 'FAQ block failed to load FAQs: ' . $e->getMessage());
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
                <?php if (! empty($faqConfig['badge'])): ?>
                    <div class="status-pill"><span class="mono-label"><?= esc($faqConfig['badge']) ?></span></div>
                <?php endif; ?>
                <h2><?= esc(! empty($faqConfig['section_title']) ? $faqConfig['section_title'] : 'Frequently Asked Questions') ?></h2>
                <?php if (! empty($faqConfig['subtitle'])): ?>
                    <p class="muted-cell"><?= esc($faqConfig['subtitle']) ?></p>
                <?php endif; ?>
            </div>

            <div class="cms-faq-list">
                <?php if ($faqLoadError): ?>
                    <div class="panel-card"><p class="muted-cell">FAQs could not be loaded. Check the application logs and FAQ add-on installation.</p></div>
                <?php elseif (empty($dbFaqs)): ?>
                    <div class="panel-card"><p class="muted-cell">No published FAQs available in the database yet. Add FAQs from the FAQ Manager.</p></div>
                <?php else: ?>
                    <?php $isFirstFaq = true; ?>
                    <?php foreach ($dbFaqs as $fq): ?>
                        <?php
                            $isOpen = ($expFirst && $isFirstFaq);
                            $isFirstFaq = false;
                            $question = is_array($fq) ? ($fq['question'] ?? '') : (is_object($fq) ? ($fq->question ?? '') : '');
                            $answer = is_array($fq) ? ($fq['answer'] ?? '') : (is_object($fq) ? ($fq->answer ?? '') : '');
                        ?>
                        <div class="cms-faq-item <?= $isOpen ? 'is-open' : '' ?>">
                            <button type="button" class="cms-faq-trigger js-faq-toggle">
                                <span class="cms-faq-q-text"><?= esc($question) ?></span>
                                <span class="cms-faq-icon"><?= $isOpen ? '−' : '+' ?></span>
                            </button>
                            <div class="cms-faq-body">
                                <p><?= nl2br(esc($answer)) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
'@
    Add-Candidate -List $candidates -Path $extractedBlock -Content $robustBlockView -Label 'FAQ block view'
}

# B. The loader body replaces only the old wrapper contents up to the split_hero
# branch. The opening wrapper tag is retained (including all pre-existing classes).
$hookMarker = 'FAQ_ADDON_HOOK_V2'
$universalAddonHookBody = @'
<?php
    // FAQ_ADDON_HOOK_V2 - modular extracted add-on block loader.
    $addonMap = [
        'faq_accordion'      => 'faqs',
        'testimonial_slider' => 'reviews',
        'newsletter_cta'     => 'newsletter',
        'announcement_bar'   => 'announcements',
    ];
    $targetAddonSlug = $addonMap[$type] ?? ($componentMap[$type]['addon_slug'] ?? 'page_builder');
    $extractedAddonBlockFile = WRITEPATH . 'addons/extracted/' . $targetAddonSlug . '/Views/addons/' . $targetAddonSlug . '/block_view.php';

    // Keep compatibility with installations using is_active, is_enabled, status,
    // or no add-ons table at all.
    $isAddonEnabled = true;
    if ($targetAddonSlug !== 'page_builder') {
        try {
            $dbCheck = \Config\Database::connect();
            if ($dbCheck->tableExists('addons')) {
                $addonRow = $dbCheck->table('addons')->where('slug', $targetAddonSlug)->get()->getRowArray();
                if (! $addonRow) {
                    $isAddonEnabled = false;
                } else {
                    foreach (['is_active', 'is_enabled'] as $flagColumn) {
                        if (array_key_exists($flagColumn, $addonRow) && $addonRow[$flagColumn] !== null && (int) $addonRow[$flagColumn] === 0) {
                            $isAddonEnabled = false;
                        }
                    }
                    if (array_key_exists('status', $addonRow) && $addonRow['status'] !== null && $addonRow['status'] !== '') {
                        $statusValue = strtolower(trim((string) $addonRow['status']));
                        if (! in_array($statusValue, ['active', 'enabled', '1', 'true', 'on'], true)) {
                            $isAddonEnabled = false;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Preserve the previous behavior on DB-check errors, but log the reason.
            if (function_exists('log_message')) {
                log_message('error', 'Frontend add-on loader could not check add-on status for ' . $targetAddonSlug . ': ' . $e->getMessage());
            }
            $isAddonEnabled = true;
        }
    }

    if ($targetAddonSlug !== 'page_builder' && $isAddonEnabled && ! is_file($extractedAddonBlockFile) && function_exists('log_message')) {
        log_message('error', 'Frontend add-on loader could not find block view: ' . $extractedAddonBlockFile);
    }
?>
<?php if ($targetAddonSlug !== 'page_builder'): ?>
    <?php if ($isAddonEnabled && is_file($extractedAddonBlockFile)): ?>
        <?php include $extractedAddonBlockFile; ?>
    <?php elseif ($isAddonEnabled && defined('ENVIRONMENT') && ENVIRONMENT !== 'production'): ?>
        <div class="panel-card" role="status"><p class="muted-cell">Add-on block view not found: <?= esc($targetAddonSlug) ?></p></div>
    <?php endif; ?>
<?php elseif ($type === 'split_hero'): ?>
'@

Write-Host '--- STEP 2: PREPARING FRONTEND PAGE VIEW PATCHES ---' -ForegroundColor Yellow
$pattern = '(?is)(?<open><div\s+class\s*=\s*"<\?=\s*\$wrapperClass\s*\?>[^"]*">).*?(?<marker><\?php\s*(?:if|elseif)\s*\(\s*\$type\s*===\s*''split_hero''\s*\)\s*:\s*\?>)'

foreach ($fePath in $fePaths) {
    if (-not (Test-Path -LiteralPath $fePath -PathType Leaf)) {
        Write-Host "[MISSING] $fePath (skipped; no file to patch)" -ForegroundColor DarkYellow
        continue
    }

    $content = Read-TextFile -Path $fePath
    if ($content.Contains($hookMarker)) {
        Write-Host "[ALREADY PATCHED] $fePath" -ForegroundColor Green
        continue
    }

    $match = [regex]::Match($content, $pattern)
    if (-not $match.Success) {
        $preflightErrors.Add("Could not locate the wrapper-to-split_hero region in '$fePath'. No changes will be written. Inspect this file's actual markup before patching.")
        continue
    }

    $openingTag = $match.Groups['open'].Value
    if (-not [regex]::IsMatch($openingTag, '(?i)\bis-revealed\b')) {
        $openingTag = [regex]::Replace($openingTag, '">$', ' is-revealed">')
    }

    $replacement = $openingTag + $universalAddonHookBody
    $newContent = $content.Substring(0, $match.Index) + $replacement + $content.Substring($match.Index + $match.Length)

    # The universal loader now owns FAQ add-on rendering. Disable the old FAQ
    # elseif branch so it cannot become a second rendering path.
    $newContent = [regex]::Replace(
        $newContent,
        '(?i)\belseif\s*\(\s*\$type\s*===\s*''faq_accordion''\s*\)\s*:',
        'elseif ($type === ''faq_accordion_legacy_disabled''):'
    )

    Add-Candidate -List $candidates -Path $fePath -Content $newContent -Label 'frontend page view'
}

if ($preflightErrors.Count -gt 0) {
    Write-Host '--- PREFLIGHT FAILED: NOTHING WAS WRITTEN ---' -ForegroundColor Red
    foreach ($message in $preflightErrors) {
        Write-Host "[ERROR] $message" -ForegroundColor Red
    }
    throw 'Frontend template structure did not match safely. No candidate files were written.'
}

if ($candidates.Count -eq 0) {
    Write-Host '[NO CHANGES] All target files are already patched or unchanged.' -ForegroundColor Green
    if (-not $phpExe) {
        Write-Host '[NOTE] PHP CLI not found on PATH; PHP syntax lint was not run.' -ForegroundColor Yellow
    }
    exit 0
}

# Validate every staged candidate before changing any source file.
if ($phpExe) {
    Write-Host '--- STEP 3: PHP SYNTAX VALIDATION ---' -ForegroundColor Yellow
    foreach ($candidate in $candidates) {
        Test-PhpSyntax -Path $candidate.Path -Content $candidate.Content
    }
} else {
    Write-Host '[WARNING] PHP CLI not found on PATH; syntax validation will be skipped.' -ForegroundColor Yellow
}

# Apply as a small transaction: create backups first; roll back written files if a write fails.
$written = New-Object 'System.Collections.Generic.List[object]'
try {
    foreach ($candidate in $candidates) {
        Copy-Item -LiteralPath $candidate.Path -Destination $candidate.Backup -ErrorAction Stop
        $written.Add($candidate)
        [System.IO.File]::WriteAllText($candidate.Path, $candidate.Content, $utf8NoBom)
        Write-Host "[PATCHED] $($candidate.Path)" -ForegroundColor Green
        Write-Host "          Backup: $($candidate.Backup)" -ForegroundColor DarkGray
    }
} catch {
    Write-Host '[ERROR] A write failed. Rolling back files already written by this run...' -ForegroundColor Red
    foreach ($candidate in $written) {
        if (Test-Path -LiteralPath $candidate.Backup -PathType Leaf) {
            Copy-Item -LiteralPath $candidate.Backup -Destination $candidate.Path -Force
            Write-Host "[ROLLED BACK] $($candidate.Path)" -ForegroundColor Yellow
        }
    }
    throw
}

Write-Host '================================================================' -ForegroundColor Green
Write-Host 'PATCH COMPLETE. Refresh the frontend with Ctrl+F5 and check CI4 logs if FAQs still do not load.' -ForegroundColor Green
Write-Host 'Timestamped .bak files were created for every changed source file.' -ForegroundColor Green
Write-Host '================================================================' -ForegroundColor Green
