<?php
    if (! class_exists(\App\Models\Addons\Faqs\FaqModel::class)) {
        $modelPath = WRITEPATH . 'addons/extracted/faqs/Models/Addons/Faqs/FaqModel.php';
        if (is_file($modelPath)) {
            require_once $modelPath;
        }
    }

    $fAccCol   = $data['accent_color'] ?? '#10b981';
    $fBdrCol   = $data['border_color'] ?? '#232738';
    $expFirst  = ($data['expand_first'] ?? '1') === '1';
    $boxPos    = $data['box_position'] ?? 'center';
    $txtAlign  = $data['text_align'] ?? 'left';
    $catFilter = trim((string) ($data['category'] ?? 'all'));
    $customBg  = $data['_bg_color'] ?? '';
    $bgMode    = $data['_bg_mode'] ?? 'default';

    $faqModel = new \App\Models\Addons\Faqs\FaqModel();
    $dbFaqs   = $faqModel->getByCategory($catFilter);
?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> cms-faq-section"
         data-accent-color="<?= esc($fAccCol) ?>"
         data-border-color="<?= esc($fBdrCol) ?>"
         data-custom-bg="<?= ($bgMode === 'custom' && $customBg !== '') ? esc($customBg) : '' ?>">
    <div class="cms-inner-container">
        <div class="cms-faq-wrapper box-pos-<?= esc($boxPos) ?> txt-align-<?= esc($txtAlign) ?>">
            <div class="cms-section-head">
                <?php if (! empty($data['badge'])): ?>
                    <div class="status-pill"><span class="mono-label"><?= esc($data['badge']) ?></span></div>
                <?php endif; ?>
                <h2><?= esc($data['section_title'] ?? 'Frequently Asked Questions') ?></h2>
                <?php if (! empty($data['subtitle'])): ?>
                    <p class="muted-cell"><?= esc($data['subtitle']) ?></p>
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

    <?php if (! empty($dbFaqs)): ?>
        <?php
            $schemaEntities = [];
            foreach ($dbFaqs as $fq) {
                $schemaEntities[] = [
                    '@type'          => 'Question',
                    'name'           => $fq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $fq['answer'],
                    ],
                ];
            }
        ?>
        <script type="application/ld+json">
        <?= json_encode([
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $schemaEntities,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
        </script>
    <?php endif; ?>
</section>