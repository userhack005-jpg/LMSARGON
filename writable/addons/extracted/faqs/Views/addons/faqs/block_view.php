<?php
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
<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> cms-faq-section is-revealed"
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
