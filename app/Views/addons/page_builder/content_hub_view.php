<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $activeTab = request()->getGet('tab') ?: 'announcements';
?>

<?php if (session()->getFlashdata('hub_notice')): ?>
    <div class="alert-banner alert-success" role="alert">
        <?= esc(session()->getFlashdata('hub_notice')) ?>
    </div>
<?php endif; ?>

<section class="panel-card">
    <div class="panel-header panel-header-wrap">
        <div>
            <span class="mono-label">DYNAMIC WIDGET DATASETS</span>
            <h3 class="panel-title">Content Hub: Announcements, FAQs & Student Reviews</h3>
        </div>
        <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">← Back to Page Builder</a>
    </div>

    <!-- 3 Clean Navigation Tabs -->
    <div class="settings-tabs">
        <button type="button" class="settings-tab <?= $activeTab === 'announcements' ? 'is-active' : '' ?>" data-tab-target="hub-tab-announcements">
            <span class="step-index">01</span>
            <span>Top Bar Announcements (<?= count($announcements ?? []) ?>)</span>
        </button>
        <button type="button" class="settings-tab <?= $activeTab === 'faqs' ? 'is-active' : '' ?>" data-tab-target="hub-tab-faqs">
            <span class="step-index">02</span>
            <span>FAQ Accordion Items (<?= count($faqs ?? []) ?>)</span>
        </button>
        <button type="button" class="settings-tab <?= $activeTab === 'reviews' ? 'is-active' : '' ?>" data-tab-target="hub-tab-reviews">
            <span class="step-index">03</span>
            <span>Student Reviews / Testimonials (<?= count($reviews ?? []) ?>)</span>
        </button>
    </div>

    <!-- ==========================================
         TAB 01: ANNOUNCEMENTS MANAGER (CREATE + EDIT + DELETE)
         ========================================== -->
    <div class="settings-pane <?= $activeTab === 'announcements' ? 'is-visible' : '' ?>" id="hub-tab-announcements">
        <form id="pb-add-ann-form" action="<?= base_url('addons/page-builder/save-announcement') ?>" method="post" class="asset-box" style="margin-bottom: 1.5rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="ann_id" value="0">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span class="mono-label text-emerald" id="ann-form-heading">+ CREATE NEW ANNOUNCEMENT ITEM</span>
                <button type="button" id="ann-cancel-edit" class="btn-action is-hidden">✕ Cancel Edit</button>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Category Name</span><span class="req-badge">GROUP</span></label>
                    <input type="text" name="category" id="ann_category" class="field-input" placeholder="e.g. Admissions, Offers" value="Admissions" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Inline Link Text (Optional)</span><span class="opt-badge">MERGED</span></label>
                    <input type="text" name="link_text" id="ann_link_text" class="field-input" placeholder="e.g. Claim Offer → (Blank = No Link)">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Inline Link URL (Optional)</span></label>
                    <input type="text" name="link_url" id="ann_link_url" class="field-input mono-input" placeholder="e.g. /login">
                </div>
            </div>

            <div class="field-group" style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:240px;">
                    <label class="field-label"><span>Announcement Message Text</span><span class="req-badge">REQUIRED</span></label>
                    <input type="text" name="message" id="ann_message" class="field-input" placeholder="🚀 Write your announcement text here..." required>
                </div>
                <button type="submit" id="ann-submit-btn" class="btn-primary">+ Save Announcement</button>
            </div>
        </form>

        <!-- Fixed-Width Responsive Table so Edit/Delete Buttons NEVER overflow right! -->
        <div class="role-table-wrapper">
            <table class="role-table" style="width:100%; table-layout:fixed;">
                <thead>
                    <tr>
                        <th style="width:110px;">ID (Single)</th>
                        <th style="width:140px;">Category</th>
                        <th>Announcement Text & Merged Link</th>
                        <th style="width:170px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr><td colspan="4" class="muted-cell">No announcements created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                        <tr>
                            <td class="mono-cell"><span class="req-badge">ID #<?= esc((string) $ann['id']) ?></span></td>
                            <td><span class="role-badge role-admin"><?= esc($ann['category']) ?></span></td>
                            <td style="white-space:normal; word-break:break-word; line-height:1.5;">
                                <span><?= esc($ann['message']) ?></span>
                                <?php if (! empty($ann['link_text'])): ?>
                                    <a href="<?= esc($ann['link_url'] ?: '#') ?>" target="_blank" class="text-emerald" style="margin-left:0.4rem; font-weight:700; text-decoration:underline; display:inline-block;">
                                        <?= esc($ann['link_text']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="opt-badge" style="margin-left:0.4rem;">NO LINK</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <div class="action-btn-group" style="justify-content:flex-end;">
                                    <button type="button"
                                            class="btn-action js-hub-edit-ann"
                                            data-id="<?= esc((string) $ann['id']) ?>"
                                            data-category="<?= esc($ann['category']) ?>"
                                            data-message="<?= esc($ann['message']) ?>"
                                            data-link-text="<?= esc($ann['link_text'] ?? '') ?>"
                                            data-link-url="<?= esc($ann['link_url'] ?? '') ?>">
                                        ✎ Edit
                                    </button>
                                    <form action="<?= base_url('addons/page-builder/delete-announcement') ?>" method="post" class="js-hub-del-form" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $ann['id']) ?>">
                                        <button type="submit" class="btn-action btn-action-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         TAB 02: UNLIMITED FAQS MANAGER (CREATE + EDIT + DELETE)
         ========================================== -->
    <div class="settings-pane <?= $activeTab === 'faqs' ? 'is-visible' : '' ?>" id="hub-tab-faqs">
        <form id="pb-add-faq-form" action="<?= base_url('addons/page-builder/save-faq') ?>" method="post" class="asset-box" style="margin-bottom: 1.5rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="faq_id" value="0">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span class="mono-label text-emerald" id="faq-form-heading">+ ADD NEW FAQ QUESTION & ANSWER</span>
                <button type="button" id="faq-cancel-edit" class="btn-action is-hidden">✕ Cancel Edit</button>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Question</span></label>
                    <input type="text" name="question" id="faq_question" class="field-input" placeholder="Enter question..." required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Category</span></label>
                    <input type="text" name="category" id="faq_category" class="field-input" value="General" required>
                </div>
            </div>
            <div class="field-group" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <input type="text" name="answer" id="faq_answer" class="field-input" style="flex:1; min-width:240px;" placeholder="Detailed FAQ Answer..." required>
                <button type="submit" id="faq-submit-btn" class="btn-primary" style="flex-shrink:0;">+ Add FAQ</button>
            </div>
        </form>

        <div class="telemetry-list">
            <?php foreach (($faqs ?? []) as $fq): ?>
            <div class="telemetry-item" style="gap:1rem;">
                <div style="flex:1; min-width:0; word-break:break-word;">
                    <div class="identity-name">
                        <span class="role-badge role-admin"><?= esc($fq['category']) ?></span>
                        <span><?= esc($fq['question']) ?></span>
                    </div>
                    <div class="identity-email" style="white-space:normal; margin-top:0.25rem;"><?= esc($fq['answer']) ?></div>
                </div>
                <div class="action-btn-group" style="flex-shrink:0;">
                    <button type="button"
                            class="btn-action js-hub-edit-faq"
                            data-id="<?= esc((string) $fq['id']) ?>"
                            data-category="<?= esc($fq['category']) ?>"
                            data-question="<?= esc($fq['question']) ?>"
                            data-answer="<?= esc($fq['answer']) ?>">
                        ✎ Edit
                    </button>
                    <form action="<?= base_url('addons/page-builder/delete-faq') ?>" method="post" class="js-hub-del-form" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= esc((string) $fq['id']) ?>">
                        <button type="submit" class="btn-action btn-action-danger">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ==========================================
         TAB 03: STUDENT REVIEWS MANAGER (CREATE + EDIT + DELETE)
         ========================================== -->
    <div class="settings-pane <?= $activeTab === 'reviews' ? 'is-visible' : '' ?>" id="hub-tab-reviews">
        <form id="pb-add-review-form" action="<?= base_url('addons/page-builder/save-review') ?>" method="post" class="asset-box" style="margin-bottom: 1.5rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="rev_id" value="0">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span class="mono-label text-emerald" id="rev-form-heading">+ ADD NEW STUDENT TESTIMONIAL</span>
                <button type="button" id="rev-cancel-edit" class="btn-action is-hidden">✕ Cancel Edit</button>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Student / Parent Name</span></label>
                    <input type="text" name="reviewer_name" id="rev_name" class="field-input" placeholder="e.g. Aarav Verma" required>
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Role / Batch / Country</span></label>
                    <input type="text" name="designation" id="rev_designation" class="field-input" placeholder="e.g. Dubai, UAE">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Star Rating</span></label>
                    <select name="rating" id="rev_rating" class="field-select">
                        <option value="5">★★★★★ (5 Stars)</option>
                        <option value="4">★★★★☆ (4 Stars)</option>
                        <option value="3">★★★☆☆ (3 Stars)</option>
                    </select>
                </div>
            </div>
            <div class="field-group" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <input type="text" name="review_text" id="rev_text" class="field-input" style="flex:1; min-width:240px;" placeholder="Write student review or feedback..." required>
                <button type="submit" id="rev-submit-btn" class="btn-primary" style="flex-shrink:0;">+ Add Review</button>
            </div>
        </form>

        <div class="telemetry-list">
            <?php foreach (($reviews ?? []) as $rev): ?>
            <div class="telemetry-item" style="gap:1rem;">
                <div style="flex:1; min-width:0; word-break:break-word;">
                    <div class="identity-name">
                        <span><?= esc($rev['reviewer_name']) ?></span>
                        <span class="text-amber"><?= str_repeat('★', (int) $rev['rating']) ?></span>
                    </div>
                    <div class="identity-email" style="white-space:normal; margin-top:0.25rem;"><?= esc($rev['designation']) ?> — "<?= esc($rev['review_text']) ?>"</div>
                </div>
                <div class="action-btn-group" style="flex-shrink:0;">
                    <button type="button"
                            class="btn-action js-hub-edit-rev"
                            data-id="<?= esc((string) $rev['id']) ?>"
                            data-name="<?= esc($rev['reviewer_name']) ?>"
                            data-designation="<?= esc($rev['designation']) ?>"
                            data-rating="<?= esc((string) $rev['rating']) ?>"
                            data-text="<?= esc($rev['review_text']) ?>">
                        ✎ Edit
                    </button>
                    <form action="<?= base_url('addons/page-builder/delete-review') ?>" method="post" class="js-hub-del-form" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= esc((string) $rev['id']) ?>">
                        <button type="submit" class="btn-action btn-action-danger">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Self-Contained Hub Script (Works 100% even if external JS is cached!) -->
<script>
document.addEventListener('click', function (e) {
    // 1. Edit Announcement
    const editAnn = e.target.closest('.js-hub-edit-ann');
    if (editAnn) {
        document.getElementById('ann_id').value = editAnn.getAttribute('data-id');
        document.getElementById('ann_category').value = editAnn.getAttribute('data-category');
        document.getElementById('ann_message').value = editAnn.getAttribute('data-message');
        document.getElementById('ann_link_text').value = editAnn.getAttribute('data-link-text');
        document.getElementById('ann_link_url').value = editAnn.getAttribute('data-link-url');
        document.getElementById('ann-form-heading').textContent = '✎ EDITING ANNOUNCEMENT ID #' + editAnn.getAttribute('data-id');
        document.getElementById('ann-submit-btn').textContent = 'Update Announcement ✓';
        document.getElementById('ann-cancel-edit').classList.remove('is-hidden');
        document.getElementById('ann_message').focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // 2. Edit FAQ
    const editFaq = e.target.closest('.js-hub-edit-faq');
    if (editFaq) {
        document.getElementById('faq_id').value = editFaq.getAttribute('data-id');
        document.getElementById('faq_category').value = editFaq.getAttribute('data-category');
        document.getElementById('faq_question').value = editFaq.getAttribute('data-question');
        document.getElementById('faq_answer').value = editFaq.getAttribute('data-answer');
        document.getElementById('faq-form-heading').textContent = '✎ EDITING FAQ #' + editFaq.getAttribute('data-id');
        document.getElementById('faq-submit-btn').textContent = 'Update FAQ ✓';
        document.getElementById('faq-cancel-edit').classList.remove('is-hidden');
        document.getElementById('faq_question').focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // 3. Edit Review
    const editRev = e.target.closest('.js-hub-edit-rev');
    if (editRev) {
        document.getElementById('rev_id').value = editRev.getAttribute('data-id');
        document.getElementById('rev_name').value = editRev.getAttribute('data-name');
        document.getElementById('rev_designation').value = editRev.getAttribute('data-designation');
        document.getElementById('rev_rating').value = editRev.getAttribute('data-rating');
        document.getElementById('rev_text').value = editRev.getAttribute('data-text');
        document.getElementById('rev-form-heading').textContent = '✎ EDITING REVIEW #' + editRev.getAttribute('data-id');
        document.getElementById('rev-submit-btn').textContent = 'Update Review ✓';
        document.getElementById('rev-cancel-edit').classList.remove('is-hidden');
        document.getElementById('rev_text').focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
});

// Single Confirm on Delete Forms
document.addEventListener('submit', function (e) {
    if (e.target.classList.contains('js-hub-del-form')) {
        if (!window.confirm('Are you sure you want to delete this item permanently?')) {
            e.preventDefault();
        }
    }
});
</script>
<?= $this->endSection() ?>