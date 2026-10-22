<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="nl-admin-wrapper">
    <?php if (session()->getFlashdata('success') or session()->getFlashdata('nl_notice')): ?>
        <div class="alert-banner alert-success nl-admin-alert" role="alert"><?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('nl_notice')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error') or session()->getFlashdata('nl_error')): ?>
        <div class="alert-banner alert-error alert-danger nl-admin-alert" role="alert"><?= esc(session()->getFlashdata('error') ?: session()->getFlashdata('nl_error')) ?></div>
    <?php endif; ?>

    <div class="panel-card nl-admin-top-bar" style="margin-bottom: 1.25rem;">
        <div class="panel-header panel-header-wrap">
            <div>
                <span class="mono-label">ADD-ON MODULE // MULTI-FORM NEWSLETTER & LEAD CAPTURE</span>
                <h3 class="panel-title">Manage Form Templates & Captured Leads</h3>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('addons/page-builder') ?>" class="btn-secondary btn-sm">Open Page Builder</a>
                <a href="<?= base_url('addons/newsletter/export-csv' . (($formFilter ?? 'all') !== 'all' ? '?form_id=' . urlencode((string) $formFilter) : '')) ?>" class="btn-primary btn-sm">⬇ Export CSV</a>
            </div>
        </div>
    </div>

    <div class="grid-3-col nl-admin-kpi-grid">
        <div class="panel-card">
            <span class="mono-label">TOTAL CAPTURED LEADS</span>
            <h2 class="text-emerald nl-admin-kpi-val"><?= (int) ($stats['total'] ?? 0) ?></h2>
        </div>
        <div class="panel-card">
            <span class="mono-label">ACTIVE SUBSCRIBERS</span>
            <h2 class="text-emerald nl-admin-kpi-val"><?= (int) ($stats['subscribed'] ?? 0) ?></h2>
        </div>
        <div class="panel-card">
            <span class="mono-label">PRE-BUILT FORM TEMPLATES</span>
            <h2 class="text-cyan nl-admin-kpi-val"><?= count($formTemplates ?? []) ?></h2>
        </div>
    </div>

    <!-- SECTION 1: PRE-BUILT FORM TEMPLATES BUILDER -->
    <div class="nl-admin-main-grid nl-section-gap">
        <?php if (! empty($canCreate) or ! empty($canEdit)): ?>
        <section class="console-card panel-card">
            <div class="card-head panel-header">
                <div>
                    <span class="mono-label">MULTI-FORM TEMPLATE BUILDER</span>
                    <h3 id="nl-tpl-heading">Create / Edit Form Template</h3>
                </div>
            </div>

            <form action="<?= base_url('addons/newsletter/save-form') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="tpl_id" value="0">

                <div class="field-group">
                    <label class="field-label">Template Name *</label>
                    <input type="text" name="form_name" id="tpl_name" class="field-input" placeholder="e.g. WhatsApp Quick Enquiry" required>
                </div>

                <div class="panel-card nl-field-config-box">
                    <div class="nl-field-toggle-row">
                        <label class="nl-check-label">
                            <input type="checkbox" name="show_name" id="tpl_show_name" value="1">
                            <strong>Show Full Name Field</strong>
                        </label>
                        <label class="nl-check-label">
                            <input type="checkbox" name="req_name" id="tpl_req_name" value="1">
                            <span class="mono-label">Required</span>
                        </label>
                    </div>
                    <input type="text" name="name_placeholder" id="tpl_name_ph" class="field-input" value="Enter your full name..." placeholder="Name field placeholder...">
                </div>

                <div class="panel-card nl-field-config-box">
                    <div class="nl-field-toggle-row">
                        <label class="nl-check-label">
                            <input type="checkbox" name="show_phone" id="tpl_show_phone" value="1">
                            <strong>Show Mobile / WhatsApp Field</strong>
                        </label>
                        <label class="nl-check-label">
                            <input type="checkbox" name="req_phone" id="tpl_req_phone" value="1">
                            <span class="mono-label">Required</span>
                        </label>
                    </div>
                    <input type="text" name="phone_placeholder" id="tpl_phone_ph" class="field-input" value="WhatsApp / Mobile number..." placeholder="Phone/WhatsApp placeholder...">
                </div>

                <div class="panel-card nl-field-config-box">
                    <div class="nl-field-toggle-row">
                        <label class="nl-check-label">
                            <input type="checkbox" name="show_email" id="tpl_show_email" value="1" checked>
                            <strong>Show Email ID Field</strong>
                        </label>
                        <label class="nl-check-label">
                            <input type="checkbox" name="req_email" id="tpl_req_email" value="1" checked>
                            <span class="mono-label">Required</span>
                        </label>
                    </div>
                    <input type="text" name="email_placeholder" id="tpl_email_ph" class="field-input" value="Enter your email address..." placeholder="Email field placeholder...">
                </div>

                <div class="field-group">
                    <label class="field-label">Submit Button Label</label>
                    <input type="text" name="btn_text" id="tpl_btn_text" class="field-input" value="Subscribe →">
                </div>

                <div class="field-group">
                    <label class="field-label">Success Message After Submit</label>
                    <input type="text" name="success_msg" id="tpl_success_msg" class="field-input" value="Thank you! Your details have been submitted.">
                </div>

                <div class="field-group">
                    <label class="nl-check-label">
                        <input type="checkbox" name="is_default" id="tpl_is_default" value="1">
                        <strong>Set as Active Default Form Template on Website</strong>
                    </label>
                </div>

                <div class="action-btn-group nl-admin-form-actions">
                    <button type="submit" class="btn-primary">Save Form Template</button>
                    <button type="button" class="btn-secondary" id="js-tpl-reset-btn">Reset</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <section class="console-card panel-card">
            <div class="card-head panel-header">
                <div>
                    <span class="mono-label">CHOOSE DEFAULT FORM OR SELECT IN PAGE BUILDER DROPDOWN</span>
                    <h3>Pre-Built Form Templates (<?= count($formTemplates ?? []) ?>)</h3>
                </div>
            </div>

            <div class="table-responsive role-table-wrapper">
                <table class="data-table role-table">
                    <thead>
                        <tr>
                            <th>Form ID</th>
                            <th>Template Name</th>
                            <th>Active Fields</th>
                            <th>Default Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($formTemplates ?? []) as $ft): ?>
                            <tr>
                                <td>
                                    <span class="status-pill"><span class="mono-label">ID #<?= (int) $ft['id'] ?></span></span>
                                </td>
                                <td>
                                    <div class="identity-name"><?= esc($ft['form_name']) ?></div>
                                    <div class="identity-email">Button: <?= esc($ft['btn_text'] ?? 'Subscribe') ?></div>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <?php if ((int) ($ft['show_name'] ?? 0) === 1): ?>
                                            <span class="role-badge">👤 Name<?= (int) ($ft['req_name'] ?? 0) === 1 ? '*' : '' ?></span>
                                        <?php endif; ?>
                                        <?php if ((int) ($ft['show_phone'] ?? 0) === 1): ?>
                                            <span class="role-badge">📱 Phone/WhatsApp<?= (int) ($ft['req_phone'] ?? 0) === 1 ? '*' : '' ?></span>
                                        <?php endif; ?>
                                        <?php if ((int) ($ft['show_email'] ?? 0) === 1): ?>
                                            <span class="role-badge">✉ Email<?= (int) ($ft['req_email'] ?? 0) === 1 ? '*' : '' ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ((int) ($ft['is_default'] ?? 0) === 1): ?>
                                        <span class="status-pill"><span class="pulse-dot"></span><span class="mono-label">★ DEFAULT ACTIVE</span></span>
                                    <?php elseif (! empty($canEdit)): ?>
                                        <form action="<?= base_url('addons/newsletter/set-default-form') ?>" method="post" class="nl-inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $ft['id'] ?>">
                                            <button type="submit" class="btn-action">Set as Default</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <?php if (! empty($canEdit)): ?>
                                            <button type="button"
                                                    class="btn-action js-edit-nl-tpl"
                                                    data-id="<?= (int) $ft['id'] ?>"
                                                    data-name="<?= esc($ft['form_name'] ?? '') ?>"
                                                    data-show-name="<?= (int) ($ft['show_name'] ?? 0) ?>"
                                                    data-req-name="<?= (int) ($ft['req_name'] ?? 0) ?>"
                                                    data-name-ph="<?= esc($ft['name_placeholder'] ?? '') ?>"
                                                    data-show-phone="<?= (int) ($ft['show_phone'] ?? 0) ?>"
                                                    data-req-phone="<?= (int) ($ft['req_phone'] ?? 0) ?>"
                                                    data-phone-ph="<?= esc($ft['phone_placeholder'] ?? '') ?>"
                                                    data-show-email="<?= (int) ($ft['show_email'] ?? 0) ?>"
                                                    data-req-email="<?= (int) ($ft['req_email'] ?? 0) ?>"
                                                    data-email-ph="<?= esc($ft['email_placeholder'] ?? '') ?>"
                                                    data-btn="<?= esc($ft['btn_text'] ?? '') ?>"
                                                    data-msg="<?= esc($ft['success_msg'] ?? '') ?>"
                                                    data-default="<?= (int) ($ft['is_default'] ?? 0) ?>">
                                                Edit Fields
                                            </button>
                                        <?php endif; ?>
                                        <?php if (! empty($canDelete) && (int) $ft['id'] > 1): ?>
                                            <form action="<?= base_url('addons/newsletter/delete-form') ?>" method="post" class="nl-inline-form js-nl-delete-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $ft['id'] ?>">
                                                <button type="submit" class="btn-action btn-action-danger text-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- SECTION 2: CAPTURED LEADS DIRECTORY & MANUAL ENTRY -->
    <div class="nl-admin-main-grid">
        <?php if (! empty($canCreate) or ! empty($canEdit)): ?>
        <section class="console-card panel-card">
            <div class="card-head panel-header">
                <div>
                    <span class="mono-label">MANUAL LEAD ENTRY</span>
                    <h3 id="nl-form-heading">Add / Edit Lead</h3>
                </div>
            </div>

            <form action="<?= base_url('addons/newsletter/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="nl_id" value="0">

                <div class="field-group">
                    <label class="field-label">Assign to Form Template</label>
                    <select name="form_id" id="nl_form_id" class="field-input">
                        <?php foreach (($formTemplates ?? []) as $ft): ?>
                            <option value="<?= (int) $ft['id'] ?>">#<?= (int) $ft['id'] ?> — <?= esc($ft['form_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label">Full Name</label>
                    <input type="text" name="full_name" id="nl_name" class="field-input" placeholder="e.g. Aarav Sharma">
                </div>

                <div class="field-group">
                    <label class="field-label">Mobile / WhatsApp Number</label>
                    <input type="text" name="phone" id="nl_phone" class="field-input" placeholder="e.g. +91 98765 43210">
                </div>

                <div class="field-group">
                    <label class="field-label">Email Address</label>
                    <input type="email" name="email" id="nl_email" class="field-input" placeholder="e.g. parent@example.com">
                </div>

                <div class="field-group">
                    <label class="field-label">Status</label>
                    <select name="status" id="nl_status" class="field-input">
                        <option value="subscribed">Active / Subscribed</option>
                        <option value="unsubscribed">Muted / Unsubscribed</option>
                    </select>
                </div>

                <div class="action-btn-group nl-admin-form-actions">
                    <button type="submit" class="btn-primary">Save Lead</button>
                    <button type="button" class="btn-secondary" id="js-nl-reset-btn">Reset</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <section class="console-card panel-card">
            <div class="card-head panel-header nl-admin-table-head">
                <div>
                    <span class="mono-label">CAPTURED LEADS DATABASE</span>
                    <h3>Submitted Leads (<?= count($leads ?? []) ?>)</h3>
                </div>

                <form method="get" action="<?= base_url('addons/newsletter') ?>" class="nl-admin-filter-bar">
                    <input type="text" name="q" value="<?= esc($searchQuery ?? '') ?>" class="field-input nl-admin-search-input" placeholder="Search name, phone, email...">
                    <select name="form_id" class="field-input nl-admin-tag-select js-nl-auto-submit">
                        <option value="all">All Form Templates</option>
                        <?php foreach (($formTemplates ?? []) as $ft): ?>
                            <option value="<?= (int) $ft['id'] ?>" <?= ((string) ($formFilter ?? 'all') === (string) $ft['id']) ? 'selected' : '' ?>>
                                #<?= (int) $ft['id'] ?> <?= esc($ft['form_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-secondary btn-sm">Filter</button>
                </form>
            </div>

            <div class="table-responsive role-table-wrapper">
                <table class="data-table role-table">
                    <thead>
                        <tr>
                            <th>Name / Email</th>
                            <th>Mobile / WhatsApp</th>
                            <th>Form Template</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads)): ?>
                            <tr>
                                <td colspan="5" class="muted-cell nl-admin-empty-cell">No leads found matching your filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <tr>
                                    <td>
                                        <div class="identity-name"><?= esc(($lead['full_name'] ?? '') !== '' ? $lead['full_name'] : ($lead['email'] ?: 'Anonymous Lead')) ?></div>
                                        <?php if (! empty($lead['email'])): ?>
                                            <div class="identity-email"><?= esc($lead['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="mono-cell"><?= esc(($lead['phone'] ?? '') !== '' ? $lead['phone'] : '—') ?></td>
                                    <td>
                                        <span class="role-badge">#<?= (int) ($lead['form_id'] ?? 1) ?> <?= esc($lead['form_name'] ?? 'Form') ?></span>
                                    </td>
                                    <td>
                                        <?php if (($lead['status'] ?? 'subscribed') === 'subscribed'): ?>
                                            <span class="status-pill"><span class="pulse-dot"></span><span class="mono-label">ACTIVE</span></span>
                                        <?php else: ?>
                                            <span class="mono-label muted-cell">MUTED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btn-group">
                                            <?php if (! empty($canEdit)): ?>
                                                <button type="button"
                                                        class="btn-action js-edit-nl-lead"
                                                        data-id="<?= (int) $lead['id'] ?>"
                                                        data-form-id="<?= (int) ($lead['form_id'] ?? 1) ?>"
                                                        data-email="<?= esc($lead['email'] ?? '') ?>"
                                                        data-name="<?= esc($lead['full_name'] ?? '') ?>"
                                                        data-phone="<?= esc($lead['phone'] ?? '') ?>"
                                                        data-status="<?= esc($lead['status'] ?? 'subscribed') ?>">
                                                    Edit
                                                </button>

                                                <form action="<?= base_url('addons/newsletter/toggle') ?>" method="post" class="nl-inline-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
                                                    <button type="submit" class="btn-action">
                                                        <?= ($lead['status'] ?? 'subscribed') === 'subscribed' ? 'Mute' : 'Activate' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (! empty($canDelete)): ?>
                                                <form action="<?= base_url('addons/newsletter/delete') ?>" method="post" class="nl-inline-form js-nl-delete-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
                                                    <button type="submit" class="btn-action btn-action-danger text-danger">Delete</button>
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
    </div>
</div>
<?= $this->endSection() ?>
