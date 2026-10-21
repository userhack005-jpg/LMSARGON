<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('ann_notice')): ?>
    <div class="alert-banner alert-success" role="alert"><?= esc(session()->getFlashdata('ann_notice')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('ann_error')): ?>
    <div class="alert-banner alert-error" role="alert"><?= esc(session()->getFlashdata('ann_error')) ?></div>
<?php endif; ?>

<section class="panel-card ann-addon-workspace">
    <div class="panel-header panel-header-wrap">
        <div>
            <span class="mono-label">ADD-ON MODULE // TOP BAR ANNOUNCEMENTS</span>
            <h3 class="panel-title">Manage Announcements & Categories (<?= count($announcements ?? []) ?>)</h3>
        </div>
        <div class="action-btn-group">
            <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">Open Page Builder</a>
        </div>
    </div>

    <?php if (! empty($canCreate) or ! empty($canEdit)): ?>
    <form id="addon-ann-form" action="<?= base_url('addons/announcements/save') ?>" method="post" class="asset-box ann-addon-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="ann_id" value="0">

        <div class="panel-header panel-header-wrap">
            <span class="mono-label text-emerald" id="ann-form-heading">+ CREATE NEW ANNOUNCEMENT ITEM</span>
            <button type="button" id="ann-cancel-edit" class="btn-action is-hidden js-cancel-ann-edit">Cancel Edit</button>
        </div>

        <div class="ann-form-grid">
            <div class="field-group">
                <label class="field-label" for="ann_category"><span>Category Name</span><span class="req-badge">GROUP</span></label>
                <input type="text" name="category" id="ann_category" class="field-input" list="ann-cat-list" value="Admissions" required>
                <datalist id="ann-cat-list">
                    <?php foreach (($categories ?? []) as $cat): ?>
                        <option value="<?= esc($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="field-group">
                <label class="field-label" for="ann_link_text"><span>Inline Link Text (Optional)</span><span class="opt-badge">MERGED</span></label>
                <input type="text" name="link_text" id="ann_link_text" class="field-input" placeholder="e.g. Claim Offer ->">
            </div>
            <div class="field-group">
                <label class="field-label" for="ann_link_url"><span>Inline Link URL (Optional)</span></label>
                <input type="text" name="link_url" id="ann_link_url" class="field-input mono-input" placeholder="e.g. /login">
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="ann_message"><span>Announcement Message Text</span><span class="req-badge">REQUIRED</span></label>
            <input type="text" name="message" id="ann_message" class="field-input" placeholder="Write your announcement text here..." required>
        </div>

        <div class="action-btn-group">
            <button type="submit" id="ann-submit-btn" class="btn-primary">+ Save Announcement</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="role-table-wrapper ann-table-scroll">
        <table class="role-table ann-fluid-table">
            <thead>
                <tr>
                    <th class="ann-col-id">ID #</th>
                    <th class="ann-col-cat">Category</th>
                    <th class="ann-col-main">Announcement Text & Merged Link</th>
                    <th class="ann-col-status">Status</th>
                    <th class="ann-col-actions text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="5" class="muted-cell">No announcements created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($announcements as $ann): ?>
                    <tr>
                        <td class="mono-cell" data-label="ID">#<?= esc((string) $ann['id']) ?></td>
                        <td data-label="Category"><span class="role-badge role-admin"><?= esc($ann['category']) ?></span></td>
                        <td class="ann-cell-wrap" data-label="Announcement">
                            <span><?= esc($ann['message']) ?></span>
                            <?php if (! empty($ann['link_text'])): ?>
                                <a href="<?= esc($ann['link_url'] ?: '#') ?>" target="_blank" class="text-emerald ann-merged-preview-link">
                                    <?= esc($ann['link_text']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status">
                            <span class="status-indicator <?= ((int) $ann['is_active'] === 1) ? 'status-active' : 'status-suspended' ?>">
                                <?= ((int) $ann['is_active'] === 1) ? 'ACTIVE' : 'HIDDEN' ?>
                            </span>
                        </td>
                        <td class="text-right ann-cell-actions" data-label="Actions">
                            <div class="action-btn-group">
                                <?php if (! empty($canEdit)): ?>
                                    <button type="button"
                                            class="btn-action js-edit-ann-row"
                                            data-id="<?= esc((string) $ann['id']) ?>"
                                            data-category="<?= esc($ann['category']) ?>"
                                            data-message="<?= esc($ann['message']) ?>"
                                            data-link-text="<?= esc($ann['link_text'] ?? '') ?>"
                                            data-link-url="<?= esc($ann['link_url'] ?? '') ?>">
                                        Edit
                                    </button>
                                    <form action="<?= base_url('addons/announcements/toggle') ?>" method="post" class="ann-inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $ann['id']) ?>">
                                        <button type="submit" class="btn-action"><?= ((int) $ann['is_active'] === 1) ? 'Hide' : 'Show' ?></button>
                                    </form>
                                <?php endif; ?>

                                <?php if (! empty($canDelete)): ?>
                                    <form action="<?= base_url('addons/announcements/delete') ?>" method="post" class="ann-inline-form js-ann-delete-form" data-confirm="Delete Announcement permanently?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $ann['id']) ?>">
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
