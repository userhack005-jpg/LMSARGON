<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('faq_notice')): ?>
    <div class="alert-banner alert-success" role="alert"><?= esc(session()->getFlashdata('faq_notice')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('faq_error')): ?>
    <div class="alert-banner alert-error" role="alert"><?= esc(session()->getFlashdata('faq_error')) ?></div>
<?php endif; ?>

<section class="panel-card faq-addon-workspace">
    <div class="panel-header panel-header-wrap">
        <div>
            <span class="mono-label">ADD-ON MODULE // FAQ ACCORDION ENGINE</span>
            <h3 class="panel-title">Manage Unlimited FAQs & Categories (<?= count($faqs ?? []) ?>)</h3>
        </div>
        <div class="action-btn-group">
            <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">Open Page Builder</a>
        </div>
    </div>

    <?php if (! empty($canCreate) || ! empty($canEdit)): ?>
    <form id="addon-faq-form" action="<?= base_url('addons/faqs/save') ?>" method="post" class="asset-box faq-addon-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="faq_id" value="0">

        <div class="panel-header panel-header-wrap">
            <span class="mono-label text-emerald" id="faq-form-heading">+ ADD NEW FAQ QUESTION & ANSWER</span>
            <button type="button" id="faq-cancel-edit" class="btn-action is-hidden js-cancel-faq-edit">✕ Cancel Edit</button>
        </div>

        <div class="faq-form-grid">
            <div class="field-group">
                <label class="field-label" for="faq_question"><span>Question</span><span class="req-badge">REQUIRED</span></label>
                <input type="text" name="question" id="faq_question" class="field-input" placeholder="e.g. How do I enroll in a live batch?" required>
            </div>
            <div class="field-group">
                <label class="field-label" for="faq_category"><span>Category</span><span class="opt-badge">FILTER</span></label>
                <input type="text" name="category" id="faq_category" class="field-input" list="faq-cat-list" value="General" required>
                <datalist id="faq-cat-list">
                    <?php foreach (($categories ?? []) as $cat): ?>
                        <option value="<?= esc($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="field-group">
                <label class="field-label" for="faq_sort"><span>Display Sort Order</span></label>
                <input type="number" name="sort_order" id="faq_sort" class="field-input mono-input" value="1" min="1">
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="faq_answer"><span>Detailed Answer</span><span class="req-badge">REQUIRED</span></label>
            <textarea name="answer" id="faq_answer" rows="3" class="field-input" placeholder="Write clear, helpful answer..." required></textarea>
        </div>

        <div class="action-btn-group">
            <button type="submit" id="faq-submit-btn" class="btn-primary">+ Save FAQ</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="role-table-wrapper faq-table-scroll">
        <table class="role-table faq-fluid-table">
            <thead>
                <tr>
                    <th class="faq-col-order">Order</th>
                    <th class="faq-col-cat">Category</th>
                    <th class="faq-col-main">Question & Answer</th>
                    <th class="faq-col-status">Status</th>
                    <th class="faq-col-actions text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($faqs)): ?>
                    <tr><td colspan="5" class="muted-cell">No FAQs added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($faqs as $fq): ?>
                    <tr>
                        <td class="mono-cell" data-label="Order">#<?= esc((string) $fq['sort_order']) ?></td>
                        <td data-label="Category"><span class="role-badge role-admin"><?= esc($fq['category']) ?></span></td>
                        <td class="faq-cell-wrap" data-label="Question & Answer">
                            <div class="identity-name"><?= esc($fq['question']) ?></div>
                            <div class="identity-email"><?= esc($fq['answer']) ?></div>
                        </td>
                        <td data-label="Status">
                            <span class="status-indicator <?= ((int) $fq['is_published'] === 1) ? 'status-active' : 'status-suspended' ?>">
                                <?= ((int) $fq['is_published'] === 1) ? 'PUBLISHED' : 'HIDDEN' ?>
                            </span>
                        </td>
                        <td class="text-right faq-cell-actions" data-label="Actions">
                            <div class="action-btn-group">
                                <?php if (! empty($canEdit)): ?>
                                    <button type="button"
                                            class="btn-action js-edit-faq-row"
                                            data-id="<?= esc((string) $fq['id']) ?>"
                                            data-category="<?= esc($fq['category']) ?>"
                                            data-sort="<?= esc((string) $fq['sort_order']) ?>"
                                            data-question="<?= esc($fq['question']) ?>"
                                            data-answer="<?= esc($fq['answer']) ?>">
                                        ✎ Edit
                                    </button>
                                    <form action="<?= base_url('addons/faqs/toggle') ?>" method="post" class="faq-inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $fq['id']) ?>">
                                        <button type="submit" class="btn-action"><?= ((int) $fq['is_published'] === 1) ? 'Hide' : 'Show' ?></button>
                                    </form>
                                <?php endif; ?>

                                <?php if (! empty($canDelete)): ?>
                                    <form action="<?= base_url('addons/faqs/delete') ?>" method="post" class="faq-inline-form js-faq-delete-form" data-confirm="Delete this FAQ permanently?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $fq['id']) ?>">
                                        <button type="submit" class="btn-action btn-action-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>