<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div class="nl-admin-wrapper">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success nl-admin-alert"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-danger nl-admin-alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="grid-3-col nl-admin-kpi-grid">
        <div class="panel-card">
            <span class="mono-label">TOTAL CAPTURED LEADS</span>
            <h2 class="text-emerald nl-admin-kpi-val"><?= (int) ($stats['total'] ?? 0) ?></h2>
        </div>
        <div class="panel-card">
            <span class="mono-label">ACTIVE SUBSCRIBERS</span>
            <h2 class="text-cyan nl-admin-kpi-val"><?= (int) ($stats['subscribed'] ?? 0) ?></h2>
        </div>
        <div class="panel-card nl-admin-export-card">
            <div>
                <span class="mono-label">EXPORT AUDIENCE</span>
                <p class="muted-cell nl-admin-export-sub">Download all leads as CSV for campaigns</p>
            </div>
            <a href="<?= base_url('addons/newsletter/export-csv') ?>" class="btn-primary btn-sm">⬇ Export CSV</a>
        </div>
    </div>

    <div class="nl-admin-main-grid">
        <?php if (! empty($canCreate) or ! empty($canEdit)): ?>
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="mono-label">MANUAL CRM ENTRY</span>
                    <h3 id="nl-form-heading">Add / Edit Subscriber</h3>
                </div>
            </div>

            <form action="<?= base_url('addons/newsletter/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="nl_id" value="0">

                <div class="field-group">
                    <label class="field-label">Email Address *</label>
                    <input type="email" name="email" id="nl_email" class="field-input" placeholder="parent@example.com" required>
                </div>

                <div class="field-group">
                    <label class="field-label">Full Name</label>
                    <input type="text" name="full_name" id="nl_name" class="field-input" placeholder="e.g. Aarav Sharma">
                </div>

                <div class="field-group">
                    <label class="field-label">Phone / WhatsApp</label>
                    <input type="text" name="phone" id="nl_phone" class="field-input" placeholder="+971 50 000 0000">
                </div>

                <div class="field-group">
                    <label class="field-label">Course / Interest Topic</label>
                    <input type="text" name="interest_topic" id="nl_topic" class="field-input" placeholder="e.g. Abacus Level 1">
                </div>

                <div class="field-group">
                    <label class="field-label">Lead Tag / Segment</label>
                    <input type="text" name="lead_tag" id="nl_tag" class="field-input" value="Website Lead Magnet" placeholder="e.g. Free Study Kit">
                </div>

                <div class="field-group">
                    <label class="field-label">Status</label>
                    <select name="status" id="nl_status" class="field-input">
                        <option value="subscribed">Subscribed (Active)</option>
                        <option value="unsubscribed">Unsubscribed</option>
                    </select>
                </div>

                <div class="action-btn-group nl-admin-form-actions">
                    <button type="submit" class="btn-primary">Save Lead</button>
                    <button type="button" class="btn-secondary" id="js-nl-reset-btn">Reset</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <section class="console-card">
            <div class="card-head nl-admin-table-head">
                <div>
                    <span class="mono-label">SUBSCRIBER DIRECTORY</span>
                    <h3>Captured Leads (<?= count($leads ?? []) ?>)</h3>
                </div>

                <form method="get" action="<?= base_url('addons/newsletter') ?>" class="nl-admin-filter-bar">
                    <input type="text" name="q" value="<?= esc($searchQuery ?? '') ?>" class="field-input nl-admin-search-input" placeholder="Search name, email, phone...">
                    <select name="tag" class="field-input nl-admin-tag-select js-nl-auto-submit">
                        <option value="all">All Segments</option>
                        <?php foreach (($stats['tags'] ?? []) as $tRow): ?>
                            <?php $tName = $tRow['lead_tag'] ?? ''; ?>
                            <option value="<?= esc($tName) ?>" <?= ($tagFilter ?? '') === $tName ? 'selected' : '' ?>>
                                <?= esc($tName) ?> (<?= (int) ($tRow['cnt'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-secondary btn-sm">Filter</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subscriber</th>
                            <th>Phone / Topic</th>
                            <th>Segment Tag</th>
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
                                        <div class="identity-name"><?= esc($lead['email'] ?? '') ?></div>
                                        <?php if (! empty($lead['full_name'])): ?>
                                            <div class="identity-email"><?= esc($lead['full_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="mono-cell"><?= esc($lead['phone'] ?: '—') ?></div>
                                        <?php if (! empty($lead['interest_topic'])): ?>
                                            <div class="identity-email text-emerald"><?= esc($lead['interest_topic']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="role-badge"><?= esc($lead['lead_tag'] ?? 'General') ?></span>
                                    </td>
                                    <td>
                                        <?php if (($lead['status'] ?? 'subscribed') === 'subscribed'): ?>
                                            <span class="status-pill"><span class="pulse-dot"></span><span class="mono-label">ACTIVE</span></span>
                                        <?php else: ?>
                                            <span class="mono-label muted-cell">UNSUBSCRIBED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btn-group">
                                            <?php if (! empty($canEdit)): ?>
                                                <button type="button"
                                                        class="btn-action js-edit-nl-lead"
                                                        data-id="<?= (int) $lead['id'] ?>"
                                                        data-email="<?= esc($lead['email'] ?? '') ?>"
                                                        data-name="<?= esc($lead['full_name'] ?? '') ?>"
                                                        data-phone="<?= esc($lead['phone'] ?? '') ?>"
                                                        data-topic="<?= esc($lead['interest_topic'] ?? '') ?>"
                                                        data-tag="<?= esc($lead['lead_tag'] ?? '') ?>"
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
                                                    <button type="submit" class="btn-action text-danger">Delete</button>
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
