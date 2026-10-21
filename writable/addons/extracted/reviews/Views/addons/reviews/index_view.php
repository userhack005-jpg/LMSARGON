<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('rev_notice')): ?>
    <div class="alert-banner alert-success" role="alert"><?= esc(session()->getFlashdata('rev_notice')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('rev_error')): ?>
    <div class="alert-banner alert-error" role="alert"><?= esc(session()->getFlashdata('rev_error')) ?></div>
<?php endif; ?>

<section class="panel-card rev-addon-workspace">
    <div class="panel-header panel-header-wrap">
        <div>
            <span class="mono-label">ADD-ON MODULE // STUDENT REVIEWS & SOCIAL PROOF</span>
            <h3 class="panel-title">Manage Verified Reviews & Star Ratings (<?= count($reviews ?? []) ?>)</h3>
        </div>
        <div class="action-btn-group">
            <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">Open Page Builder</a>
        </div>
    </div>

    <?php if (! empty($canCreate) or ! empty($canEdit)): ?>
    <form id="addon-rev-form" action="<?= base_url('addons/reviews/save') ?>" method="post" class="asset-box rev-addon-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="rev_id" value="0">

        <div class="panel-header panel-header-wrap">
            <span class="mono-label text-emerald" id="rev-form-heading">+ ADD NEW STUDENT / PARENT REVIEW</span>
            <button type="button" id="rev-cancel-edit" class="btn-action is-hidden js-cancel-rev-edit">Cancel Edit</button>
        </div>

        <div class="rev-form-grid">
            <div class="field-group">
                <label class="field-label" for="rev_name"><span>Student / Parent Name</span><span class="req-badge">REQUIRED</span></label>
                <input type="text" name="person_name" id="rev_name" class="field-input" placeholder="e.g. Sarah Jenkins" required>
            </div>
            <div class="field-group">
                <label class="field-label" for="rev_role"><span>Role / Location Subtitle</span></label>
                <input type="text" name="person_role" id="rev_role" class="field-input" placeholder="e.g. Parent of Grade 5 Student (UK)" value="Verified Student">
            </div>
            <div class="field-group">
                <label class="field-label" for="rev_category"><span>Review Category</span><span class="opt-badge">FILTER</span></label>
                <input type="text" name="category" id="rev_category" class="field-input" list="rev-cat-list" value="Parents" required>
                <datalist id="rev-cat-list">
                    <?php foreach (($categories ?? []) as $cat): ?>
                        <option value="<?= esc($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div class="rev-form-grid">
            <div class="field-group">
                <label class="field-label" for="rev_rating"><span>Star Rating (1 to 5)</span></label>
                <select name="rating" id="rev_rating" class="field-select">
                    <option value="5">★★★★★ (5 Stars - Excellent)</option>
                    <option value="4">★★★★☆ (4 Stars - Very Good)</option>
                    <option value="3">★★★☆☆ (3 Stars - Good)</option>
                    <option value="2">★★☆☆☆ (2 Stars - Fair)</option>
                    <option value="1">★☆☆☆☆ (1 Star)</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label" for="rev_avatar"><span>Avatar Image URL (Optional)</span></label>
                <input type="text" name="avatar_url" id="rev_avatar" class="field-input mono-input" placeholder="https://... (Leave blank for auto-initials)">
            </div>
            <div class="field-group">
                <label class="field-label" for="rev_sort"><span>Display Sort Order</span></label>
                <input type="number" name="sort_order" id="rev_sort" class="field-input mono-input" value="1" min="1">
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="rev_quote"><span>Testimonial / Review Quote</span><span class="req-badge">REQUIRED</span></label>
            <textarea name="quote" id="rev_quote" rows="3" class="field-input" placeholder="Write the student or parent testimonial here..." required></textarea>
        </div>

        <div class="action-btn-group">
            <button type="submit" id="rev-submit-btn" class="btn-primary">+ Save Review</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="role-table-wrapper rev-table-scroll">
        <table class="role-table rev-fluid-table">
            <thead>
                <tr>
                    <th class="rev-col-order">Order</th>
                    <th class="rev-col-cat">Category</th>
                    <th class="rev-col-person">Reviewer & Rating</th>
                    <th class="rev-col-quote">Testimonial Quote</th>
                    <th class="rev-col-status">Status</th>
                    <th class="rev-col-actions text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="6" class="muted-cell">No student reviews added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rv): ?>
                    <?php $stars = str_repeat('★', max(1, min(5, (int) ($rv['rating'] ?? 5)))); ?>
                    <tr>
                        <td class="mono-cell" data-label="Order">#<?= esc((string) ($rv['sort_order'] ?? 1)) ?></td>
                        <td data-label="Category"><span class="role-badge role-admin"><?= esc($rv['category'] ?? 'General') ?></span></td>
                        <td data-label="Reviewer">
                            <div class="identity-name"><?= esc($rv['person_name']) ?></div>
                            <div class="identity-email"><?= esc($rv['person_role']) ?></div>
                            <div class="rev-star-badge"><?= esc($stars) ?></div>
                        </td>
                        <td class="rev-cell-wrap" data-label="Quote">
                            <span>"<?= esc($rv['quote']) ?>"</span>
                        </td>
                        <td data-label="Status">
                            <span class="status-indicator <?= ((int) $rv['is_approved'] === 1) ? 'status-active' : 'status-suspended' ?>">
                                <?= ((int) $rv['is_approved'] === 1) ? 'APPROVED' : 'HIDDEN' ?>
                            </span>
                        </td>
                        <td class="text-right rev-cell-actions" data-label="Actions">
                            <div class="action-btn-group">
                                <?php if (! empty($canEdit)): ?>
                                    <button type="button"
                                            class="btn-action js-edit-rev-row"
                                            data-id="<?= esc((string) $rv['id']) ?>"
                                            data-category="<?= esc($rv['category'] ?? 'General') ?>"
                                            data-name="<?= esc($rv['person_name']) ?>"
                                            data-role="<?= esc($rv['person_role']) ?>"
                                            data-rating="<?= esc((string) ($rv['rating'] ?? 5)) ?>"
                                            data-quote="<?= esc($rv['quote']) ?>"
                                            data-avatar="<?= esc($rv['avatar_url'] ?? '') ?>"
                                            data-sort="<?= esc((string) ($rv['sort_order'] ?? 1)) ?>">
                                        Edit
                                    </button>
                                    <form action="<?= base_url('addons/reviews/toggle') ?>" method="post" class="rev-inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $rv['id']) ?>">
                                        <button type="submit" class="btn-action"><?= ((int) $rv['is_approved'] === 1) ? 'Hide' : 'Approve' ?></button>
                                    </form>
                                <?php endif; ?>

                                <?php if (! empty($canDelete)): ?>
                                    <form action="<?= base_url('addons/reviews/delete') ?>" method="post" class="rev-inline-form js-rev-delete-form" data-confirm="Delete this review permanently?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $rv['id']) ?>">
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
