<?php $cd = $data ?? []; ?>
<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> las-verifier-block is-revealed">
    <div class="cms-inner-container panel-card las-verifier-card">
        <?php if (! empty($cd['badge'])): ?>
            <span class="req-badge"><?= esc($cd['badge']) ?></span>
        <?php endif; ?>
        <h2><?= esc($cd['heading'] ?? 'Official Certificate Verification Portal') ?></h2>
        <p class="muted-cell"><?= esc($cd['subheading'] ?? 'Enter a student certificate serial code below to verify authenticity.') ?></p>

        <div class="las-verify-bar" data-verify-url="<?= base_url('addons/lms-assessments/verify-code-json') ?>">
            <input type="text" class="field-input js-las-cert-input" placeholder="e.g. CERT-2026-A1B2C3">
            <button type="button" class="btn-primary js-las-cert-btn">Verify Certificate →</button>
        </div>
        <div class="js-las-cert-result las-cert-result"></div>
    </div>
</section>
