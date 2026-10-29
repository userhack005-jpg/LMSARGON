<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<div style="width:100%; max-width:100%; box-sizing:border-box;">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap:1.35rem; align-items:start;">
        <!-- Left: Create User Form -->
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">CREATE ACCOUNT</span>
                    <h3 style="margin-top:0.3rem;">Add New User</h3>
                    <p class="field-hint" style="margin:0;">Create a Teacher, Parent, Student, or Staff account.</p>
                </div>
            </div>

            <form action="<?= base_url('users/create') ?>" method="post">
                <?= csrf_field() ?>
                <div class="field-group">
                    <label class="field-label"><span>Full Name *</span></label>
                    <input type="text" name="full_name" class="field-input" placeholder="e.g. Dr. Neha Verma" required>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Email Address *</span></label>
                    <input type="email" name="email" class="field-input" placeholder="user@academy.com" required>
                </div>

                <div class="grid-2-col">
                    <div class="field-group">
                        <label class="field-label"><span>Assign Role *</span></label>
                        <select name="role_slug" class="field-select" required>
                            <?php foreach (($roles ?? []) as $r): ?>
                                <?php $rSlug = $r['role_slug'] ?? ($r['name'] ?? 'student'); ?>
                                <option value="<?= esc($rSlug) ?>" <?= $rSlug === 'student' ? 'selected' : '' ?>>
                                    <?= esc($r['role_name'] ?? ucfirst($rSlug)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label"><span>Password *</span></label>
                        <input type="password" name="password" class="field-input" placeholder="Min 6 chars" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">➕ Create User Account</button>
            </form>
        </section>

        <!-- Right: Users Directory Table -->
        <section class="console-card">
            <div class="card-head" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
                <div>
                    <h3>Users &amp; Role Directory (<?= count($users ?? []) ?>)</h3>
                    <p class="field-hint" style="margin:0;">Deleting a Parent automatically cascade-deletes their linked student profiles and course records.</p>
                </div>
                <input type="search" id="js-users-filter" class="field-input" placeholder="Search name, email or role..." style="width:220px; padding:0.4rem 0.7rem;">
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User Details</th>
                            <th>Assigned Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($users ?? []) as $uRow): ?>
                            <?php
                                $uid     = (int) $uRow['id'];
                                $isRoot  = ($uid === 1);
                                $isAct   = (($uRow['status'] ?? 'active') === 'active');
                                $srchTxt = strtolower(($uRow['full_name'] ?? '') . ' ' . ($uRow['email'] ?? '') . ' ' . ($uRow['role_name'] ?? ''));
                            ?>
                            <tr class="js-user-dir-row" data-search="<?= esc($srchTxt) ?>">
                                <td>
                                    <div class="identity-name"><?= esc($uRow['full_name']) ?></div>
                                    <div class="identity-email"><?= esc($uRow['email']) ?></div>
                                    <?php if (! empty($uRow['parent_name'])): ?>
                                        <span class="role-badge text-cyan">👨‍👩‍👧‍👦 Parent: <?= esc($uRow['parent_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isRoot): ?>
                                        <span class="role-badge text-emerald">🛡 Super Administrator</span>
                                    <?php else: ?>
                                        <form action="<?= base_url('users/update-role') ?>" method="post" style="display:flex; gap:0.35rem; align-items:center;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                                            <select name="role_id" class="field-select" style="padding:0.3rem 0.5rem; font-size:0.8rem;" onchange="this.form.submit()">
                                                <?php foreach (($roles ?? []) as $r): ?>
                                                    <?php
                                                        $rId   = (int) ($r['id'] ?? 0);
                                                        $rSlug = $r['role_slug'] ?? '';
                                                        $sel   = ($rSlug !== '' && $rSlug === ($uRow['role_slug'] ?? '')) || ($rId === (int) ($uRow['role_id'] ?? 0));
                                                    ?>
                                                    <option value="<?= $rId ?>" <?= $sel ? 'selected' : '' ?>><?= esc($r['role_name'] ?? $rSlug) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isRoot): ?>
                                        <span class="role-badge text-emerald">● ACTIVE</span>
                                    <?php else: ?>
                                        <form action="<?= base_url('users/toggle-status') ?>" method="post" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                                            <button type="submit" class="btn-action <?= $isAct ? 'text-emerald' : 'text-danger' ?>">
                                                <?= $isAct ? '● Active' : '○ Inactive' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (! $isRoot): ?>
                                        <form action="<?= base_url('users/delete') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this account and all linked records?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                                            <button type="submit" class="btn-action text-danger">✕ Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted-cell">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var inp = document.getElementById('js-users-filter');
    if (inp) {
        inp.addEventListener('input', function () {
            var q = inp.value.toLowerCase().trim();
            document.querySelectorAll('.js-user-dir-row').forEach(function (r) {
                var t = r.getAttribute('data-search') || '';
                r.style.display = (q === '' || t.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
