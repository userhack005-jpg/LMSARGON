#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import zipfile

def write_file_set(base_dir, file_map):
    for rel_path, content in file_map.items():
        dst = os.path.join(base_dir, rel_path.replace("/", os.sep))
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        with open(dst, "w", encoding="utf-8", newline="\n") as f:
            f.write(content.replace("\r\n", "\n").strip() + "\n")

def build_zip_and_mirror(zip_name, addon_slug, file_map, roots):
    if os.path.exists(zip_name):
        os.remove(zip_name)
    with zipfile.ZipFile(zip_name, "w", zipfile.ZIP_DEFLATED) as zf:
        for rel_path, content in file_map.items():
            zf.writestr(rel_path, content.replace("\r\n", "\n").strip() + "\n")
    print(f"[ZIP CREATED] {os.path.abspath(zip_name)}")

    for r in roots:
        extr = os.path.join(r, "writable", "addons", "extracted", addon_slug)
        if os.path.isdir(os.path.join(r, "app")):
            write_file_set(extr, file_map)
            # Mirror public assets immediately
            for asset_rel, asset_content in file_map.items():
                if asset_rel.startswith("assets/"):
                    pub_dst = os.path.join(r, "public", "assets", "addons", addon_slug, os.path.basename(asset_rel))
                    os.makedirs(os.path.dirname(pub_dst), exist_ok=True)
                    with open(pub_dst, "w", encoding="utf-8", newline="\n") as pf:
                        pf.write(asset_content.replace("\r\n", "\n").strip() + "\n")
            print(f"[DISK UPDATED] {extr}")

def main():
    roots = [r"C:\xampp\htdocs\LMSARGON", os.getcwd()]
    active_roots = [r for r in roots if os.path.isdir(os.path.join(r, "app"))]
    if not active_roots:
        active_roots = [os.getcwd()]

    # =========================================================================
    # PART 1: FIX CORE `/users` (`UserModel.php`, `UserController.php`, `index_view.php`)
    # =========================================================================
    core_users_files = {}

    core_users_files["app/Models/Users/UserModel.php"] = r'''<?php

namespace App\Models\Users;

use CodeIgniter\Model;
use Throwable;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'role_id', 'role_slug', 'parent_user_id', 'full_name', 'name',
        'username', 'email', 'password_hash', 'status', 'created_at', 'updated_at',
    ];

    public function getAllRoles(): array
    {
        $roles = [];
        try {
            if ($this->db->tableExists('cms_roles')) {
                $roles = $this->db->table('cms_roles')->orderBy('id', 'ASC')->get()->getResultArray();
            } elseif ($this->db->tableExists('roles')) {
                $roles = $this->db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();
            }
        } catch (Throwable $e) {
        }
        return $roles;
    }

    public function getAllUsersWithRoles(): array
    {
        if (! $this->db->tableExists('users')) {
            return [];
        }
        $rolesBySlug = [];
        $rolesById   = [];
        foreach ($this->getAllRoles() as $r) {
            $rid   = (int) ($r['id'] ?? 0);
            $rSlug = strtolower(trim((string) ($r['role_slug'] ?? ($r['name'] ?? 'student'))));
            $rName = $r['role_name'] ?? ($r['name'] ?? ucfirst($rSlug));
            $rolesById[$rid]     = ['slug' => $rSlug, 'name' => $rName];
            $rolesBySlug[$rSlug] = ['id' => $rid, 'name' => $rName];
        }

        $rows = $this->db->table('users')->orderBy('id', 'ASC')->get()->getResultArray();
        $byId = [];
        foreach ($rows as $u) {
            $byId[(int) $u['id']] = $u['full_name'] ?? ($u['name'] ?? ($u['email'] ?? 'User'));
        }

        foreach ($rows as &$u) {
            $rid   = (int) ($u['role_id'] ?? 4);
            $rSlug = strtolower(trim((string) ($u['role_slug'] ?? ($rolesById[$rid]['slug'] ?? 'student'))));
            if ((int) $u['id'] === 1) {
                $rSlug = 'super_admin';
            }
            $u['full_name']   = $u['full_name'] ?? ($u['name'] ?? ($u['username'] ?? 'User'));
            $u['role_slug']   = $rSlug;
            $u['role_name']   = $rolesBySlug[$rSlug]['name'] ?? ($rolesById[$rid]['name'] ?? ucfirst(str_replace('_', ' ', $rSlug)));
            $pid              = (int) ($u['parent_user_id'] ?? 0);
            $u['parent_name'] = ($pid > 0 && isset($byId[$pid])) ? $byId[$pid] : '';
        }
        unset($u);
        return $rows;
    }

    public function createNewUser(array $post): array
    {
        $fullName = trim((string) ($post['full_name'] ?? ($post['name'] ?? '')));
        $email    = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $roleSlug = strtolower(trim((string) ($post['role_slug'] ?? 'student')));
        $roleId   = (int) ($post['role_id'] ?? 0);

        if ($fullName === '' || $email === '' || strlen($password) < 4) {
            return ['status' => 'error', 'message' => 'Full Name, Email, and Password (min 4 chars) are required.'];
        }

        $dup = $this->db->table('users')->where('email', $email)->get()->getRowArray();
        if ($dup) {
            return ['status' => 'error', 'message' => 'An account with this email already exists.'];
        }

        foreach ($this->getAllRoles() as $r) {
            $rs = strtolower(trim((string) ($r['role_slug'] ?? '')));
            if (($roleId > 0 && (int) $r['id'] === $roleId) || ($rs !== '' && $rs === $roleSlug)) {
                $roleId   = (int) $r['id'];
                $roleSlug = $rs ?: $roleSlug;
                break;
            }
        }
        if ($roleId <= 0) {
            $roleId = 4;
        }

        $now   = gmdate('Y-m-d H:i:s');
        $uCols = array_flip($this->db->getFieldNames('users'));
        $row   = array_intersect_key([
            'role_id'       => $roleId,
            'role_slug'     => $roleSlug,
            'full_name'     => $fullName,
            'name'          => $fullName,
            'username'      => explode('@', $email)[0],
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status'        => 'active',
            'created_at'    => $now,
            'updated_at'    => $now,
        ], $uCols);

        $this->db->table('users')->insert($row);
        return ['status' => 'success', 'message' => 'User "' . $fullName . '" created successfully!'];
    }

    public function assignRole(int $userId, int $roleId, string $roleSlug = ''): array
    {
        if ($userId <= 1) {
            return ['status' => 'error', 'message' => 'Root Super Admin role cannot be modified.'];
        }
        foreach ($this->getAllRoles() as $r) {
            $rs = strtolower(trim((string) ($r['role_slug'] ?? '')));
            if (($roleId > 0 && (int) $r['id'] === $roleId) || ($roleSlug !== '' && $rs === $roleSlug)) {
                $roleId   = (int) $r['id'];
                $roleSlug = $rs ?: $roleSlug;
                break;
            }
        }

        $uCols = array_flip($this->db->getFieldNames('users'));
        $upd   = ['updated_at' => gmdate('Y-m-d H:i:s')];
        if ($roleId > 0 && isset($uCols['role_id'])) $upd['role_id'] = $roleId;
        if ($roleSlug !== '' && isset($uCols['role_slug'])) $upd['role_slug'] = $roleSlug;
        if (in_array($roleSlug, ['super_admin', 'admin', 'teacher', 'parent'], true) && isset($uCols['parent_user_id'])) {
            $upd['parent_user_id'] = null;
        }

        $this->db->table('users')->where('id', $userId)->update($upd);
        return ['status' => 'success', 'message' => 'User role updated successfully!'];
    }

    public function toggleStatus(int $userId): array
    {
        if ($userId <= 1) {
            return ['status' => 'error', 'message' => 'Root Super Admin cannot be deactivated.'];
        }
        $u = $this->find($userId);
        if (! $u) {
            return ['status' => 'error', 'message' => 'User not found.'];
        }
        $newStatus = (($u['status'] ?? 'active') === 'active') ? 'inactive' : 'active';
        $this->db->table('users')->where('id', $userId)->update(['status' => $newStatus]);
        return ['status' => 'success', 'message' => 'User status set to ' . strtoupper($newStatus) . '.'];
    }

    public function removeUser(int $userId): array
    {
        if ($userId <= 1) {
            return ['status' => 'error', 'message' => 'Root Super Admin cannot be deleted.'];
        }
        $this->db->table('users')->where('id', $userId)->delete();
        return ['status' => 'success', 'message' => 'User account deleted.'];
    }
}
'''

    core_users_files["app/Controllers/Users/UserController.php"] = r'''<?php

namespace App\Controllers\Users;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use App\Models\Users\UserModel;
use Throwable;

class UserController extends BaseController
{
    protected UserModel $userModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->userModel    = new UserModel();
        $this->settingModel = new SettingModel();
    }

    private function respondOrRedirect(array $payload)
    {
        if ($this->request->isAJAX() || strpos((string) $this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response->setJSON($payload);
        }
        $flashKey = (($payload['status'] ?? '') === 'success') ? 'success' : 'error';
        return redirect()->to(base_url('users'))->with($flashKey, $payload['message'] ?? 'Done.');
    }

    private function cascadeDeleteStudentRecords(int $studentId): void
    {
        if ($studentId <= 1) {
            return;
        }
        $db = \Config\Database::connect();
        $studentTables = [
            'lms_hierarchy_enrollments'      => 'student_user_id',
            'lms_student_subscriptions'      => 'student_user_id',
            'lms_finance_invoices'           => 'student_user_id',
            'lms_classroom_batch_students'   => 'student_user_id',
            'lms_session_student_attendance' => 'student_user_id',
            'lms_assessment_attempts'        => 'student_user_id',
            'lms_abacus_attempts'            => 'student_user_id',
            'lms_certificates'               => 'student_user_id',
            'lms_chat_messages'              => 'student_user_id',
            'lms_teacher_feedback'           => 'student_user_id',
            'payment_transactions'           => 'student_user_id',
            'cms_user_meta'                  => 'user_id',
        ];
        foreach ($studentTables as $table => $col) {
            try {
                if ($db->tableExists($table)) {
                    $db->table($table)->where($col, $studentId)->delete();
                }
            } catch (Throwable $e) {
            }
        }
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (function_exists('cms_has_permission') && ! cms_has_permission(['users.view', 'users.create', 'users.edit', 'users.delete'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }

        return view('users/index_view', [
            'pageTitle'  => 'Users & Role Directory',
            'activeMenu' => 'users',
            'settings'   => $this->settingModel->getSystemSettings(),
            'users'      => $this->userModel->getAllUsersWithRoles(),
            'roles'      => $this->userModel->getAllRoles(),
            'user'       => session()->get(),
            'canCreate'  => ! function_exists('cms_has_permission') || cms_has_permission('users.create'),
            'canEdit'    => ! function_exists('cms_has_permission') || cms_has_permission('users.edit'),
            'canDelete'  => ! function_exists('cms_has_permission') || cms_has_permission('users.delete'),
        ]);
    }

    public function createUser()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.create')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }
        return $this->respondOrRedirect($this->userModel->createNewUser($this->request->getPost()));
    }

    public function updateUserRole()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }
        $userId   = (int) ($this->request->getPost('user_id') ?? 0);
        $roleId   = (int) ($this->request->getPost('role_id') ?? 0);
        $roleSlug = trim((string) ($this->request->getPost('role_slug') ?? ''));
        return $this->respondOrRedirect($this->userModel->assignRole($userId, $roleId, $roleSlug));
    }

    public function toggleUserStatus()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }
        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        return $this->respondOrRedirect($this->userModel->toggleStatus($userId));
    }

    public function deleteUser()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }
        $userId = (int) ($this->request->getPost('user_id') ?? ($this->request->getPost('id') ?? 0));
        if ($userId <= 1) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Root Super Admin cannot be deleted.']);
        }

        $db = \Config\Database::connect();
        $deletedChildrenCount = 0;
        try {
            if ($db->tableExists('users') && in_array('parent_user_id', $db->getFieldNames('users'), true)) {
                $children = $db->table('users')->select('id')->where('parent_user_id', $userId)->get()->getResultArray();
                foreach ($children as $ch) {
                    $childId = (int) ($ch['id'] ?? 0);
                    if ($childId > 1) {
                        $this->cascadeDeleteStudentRecords($childId);
                        $db->table('users')->where('id', $childId)->delete();
                        $deletedChildrenCount++;
                    }
                }
            }
            foreach (['lms_hierarchy_enrollments', 'lms_student_subscriptions', 'lms_finance_invoices', 'payment_transactions'] as $pTbl) {
                if ($db->tableExists($pTbl)) {
                    $db->table($pTbl)->where('parent_user_id', $userId)->delete();
                }
            }
            $this->cascadeDeleteStudentRecords($userId);
        } catch (Throwable $e) {
        }

        $res = $this->userModel->removeUser($userId);
        if (($res['status'] ?? '') === 'success' && $deletedChildrenCount > 0) {
            $res['message'] = 'Parent account and ' . $deletedChildrenCount . ' linked student profile(s) (with all enrollments & records) deleted.';
        }
        return $this->respondOrRedirect($res);
    }
}
'''

    core_users_files["app/Views/users/index_view.php"] = r'''<?= $this->extend('layouts/master_layout') ?>

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
'''

    for r in active_roots:
        write_file_set(r, core_users_files)
        print(f"[CORE USERS FIXED] {r}")

    # =========================================================================
    # PART 2: `abacus_pro_suite` v8.5
    # - 2 Separate Page Builder Frontend Blocks (3D Soroban Block + Flash Anzan Block)
    # - Guest / Non-Logged-In Public Play Support (Zero Login Redirect for Guests!)
    # - 5 to 17 Rods (`[5, 7, 9, 11, 13, 15, 17]`)
    # - Smooth 0.3s / 0.5s / 0.7s Hindi & English Voice Dictation + Flash Engine
    # =========================================================================
    aps_files = {}

    aps_files["addon.json"] = r'''{
    "slug": "abacus_pro_suite",
    "name": "3D Abacus & Flash Anzan Lab",
    "category": "core_lms",
    "version": "8.5.0",
    "author": "Core Architecture Team",
    "description": "5-to-17 Rod 3D Soroban Abacus Lab, Ultra-Smooth 0.3s-2.0s Hindi & English Voice Dictation Flash Anzan, 2 Frontend Page Builder Blocks (Guest Play Enabled) & Strict Student-Only DB Logging.",
    "admin_route": "addons/abacus-pro-suite/analytics",
    "frontend_route": "abacus/arena",
    "init_model": "App\\Models\\Addons\\AbacusProSuite\\AbacusProSuiteModel",
    "assets": {
        "css": ["addons/abacus-pro-suite/assets/abacus_pro_suite.css"],
        "js": ["addons/abacus-pro-suite/assets/abacus_pro_suite.js"]
    },
    "default_config": {
        "default_rods": "5",
        "default_theme": "cyber_emerald",
        "default_speed_sec": "1.0",
        "enable_sound": "1",
        "enable_voice": "1"
    },
    "permissions": [
        { "key": "abacus_pro_suite.practice", "label": "Access 3D Soroban Lab, Flash Anzan & My Speed Badges [Student + Parent + Teacher]" },
        { "key": "abacus_pro_suite.view",     "label": "View Batch Student Abacus Practice Analytics [Teacher + Admin]" },
        { "key": "abacus_pro_suite.edit",     "label": "Configure Soroban Themes, Rods & Default Drill Settings [Super Admin + Admin]" },
        { "key": "abacus_pro_suite.delete",   "label": "Delete Practice Attempt Logs [Super Admin + Admin]" }
    ],
    "sub_views": [
        { "key": "aps.arena",     "label": "3D Soroban Abacus Lab",      "route": "abacus/arena",                      "icon": "🧮", "permission": "abacus_pro_suite.practice" },
        { "key": "aps.anzan",     "label": "Flash Anzan & Voice Drill",  "route": "abacus/anzan",                      "icon": "⚡", "permission": "abacus_pro_suite.practice" },
        { "key": "aps.progress",  "label": "My Speed & Badges",          "route": "abacus/my-progress",                "icon": "🏆", "permission": "abacus_pro_suite.practice" },
        { "key": "aps.analytics", "label": "Batch Practice Analytics",   "route": "addons/abacus-pro-suite/analytics", "icon": "📊", "permission": "abacus_pro_suite.view" },
        { "key": "aps.settings",  "label": "Abacus Theme & Settings",    "route": "addons/abacus-pro-suite/settings",  "icon": "⚙",  "permission": "abacus_pro_suite.edit" }
    ]
}
'''

    aps_files["Routes.php"] = r'''<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/AbacusProSuite/AbacusProSuiteModel.php')) {
    require_once $addonDir . '/Models/Addons/AbacusProSuite/AbacusProSuiteModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/AbacusProSuite/AbacusProSuiteController.php')) {
    require_once $addonDir . '/Controllers/Addons/AbacusProSuite/AbacusProSuiteController.php';
}

\Config\Services::autoloader()->addNamespace('AddonAbacusProSuite', $addonDir);

$routes->group('addons/abacus-pro-suite', ['namespace' => 'App\Controllers\Addons\AbacusProSuite'], static function ($routes) {
    $routes->get('/', 'AbacusProSuiteController::index');
    $routes->get('analytics', 'AbacusProSuiteController::batchAnalyticsView');
    $routes->get('settings', 'AbacusProSuiteController::settingsView');
    $routes->get('arena', 'AbacusProSuiteController::arenaView');
    $routes->get('anzan', 'AbacusProSuiteController::anzanView');
    $routes->get('my-progress', 'AbacusProSuiteController::myProgressView');

    $routes->post('save-settings', 'AbacusProSuiteController::saveSettings');
    $routes->post('delete-attempt', 'AbacusProSuiteController::deleteAttempt');
    $routes->post('log-attempt', 'AbacusProSuiteController::logAttemptAjax');
    $routes->get('assets/(:any)', 'AbacusProSuiteController::asset/$1');
});

// Public / Guest & Student Practice Routes (Works WITH or WITHOUT Login!)
$routes->group('abacus', ['namespace' => 'App\Controllers\Addons\AbacusProSuite'], static function ($routes) {
    $routes->get('/', 'AbacusProSuiteController::arenaView');
    $routes->get('arena', 'AbacusProSuiteController::arenaView');
    $routes->get('anzan', 'AbacusProSuiteController::anzanView');
    $routes->get('my-progress', 'AbacusProSuiteController::myProgressView');
    $routes->post('log-attempt', 'AbacusProSuiteController::logAttemptAjax');
});
'''

    aps_files["Models/Addons/AbacusProSuite/AbacusProSuiteModel.php"] = r'''<?php

namespace App\Models\Addons\AbacusProSuite;

use CodeIgniter\Model;
use Throwable;

class AbacusProSuiteModel extends Model
{
    protected $table            = 'lms_abacus_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_user_id', 'node_id', 'cohort_id', 'drill_mode',
        'digits_count', 'rows_count', 'speed_seconds', 'total_questions',
        'correct_answers', 'accuracy_pct', 'time_taken_sec', 'created_at',
    ];

    protected static ?array $globalSettingsCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchemaInitialized(): void
    {
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        try {
            if (! $this->db->tableExists('lms_abacus_attempts')) {
                $this->db->query("
                    CREATE TABLE IF NOT EXISTS `lms_abacus_attempts` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `student_user_id` INT UNSIGNED NOT NULL,
                        `node_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `cohort_id` INT UNSIGNED NOT NULL DEFAULT 0,
                        `drill_mode` VARCHAR(40) NOT NULL DEFAULT 'flash_anzan',
                        `digits_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                        `rows_count` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
                        `speed_seconds` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                        `total_questions` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                        `correct_answers` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                        `accuracy_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                        `time_taken_sec` INT UNSIGNED NOT NULL DEFAULT 0,
                        `created_at` DATETIME NULL,
                        KEY `idx_stu_created` (`student_user_id`, `created_at`),
                        KEY `idx_cohort_mode` (`cohort_id`, `drill_mode`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }

            $this->cleanupNonStudentAndOrphanedAttempts();
            $this->syncPageBuilderComponents();

            $lockFile = WRITEPATH . 'addons/schema_addon_abacus_pro_suite_v85.lock';
            if (is_file($lockFile)) {
                return;
            }

            if ($this->db->tableExists('addons')) {
                $this->db->table('addons')->where('slug', 'abacus_pro_suite')->update(['name' => '3D Abacus & Flash Anzan Lab']);
            }

            $this->syncRbacPermissionsAndAutoEnableRoles();
            $this->mirrorAssets(true);

            @is_dir(dirname($lockFile)) || @mkdir(dirname($lockFile), 0775, true);
            @file_put_contents($lockFile, date('c'));
        } catch (Throwable $e) {
            log_message('error', 'AbacusProSuiteModel schema error: ' . $e->getMessage());
        }
    }

    /**
     * Registers TWO Distinct Frontend Page Builder Components in `cms_components`
     * so Admin can drop 3D Soroban Abacus OR Flash Anzan (or both) on any public page for Non-Registered Visitors!
     */
    public function syncPageBuilderComponents(): void
    {
        try {
            if (! $this->db->tableExists('cms_components')) {
                return;
            }
            $components = [
                [
                    'key'   => 'abacus_soroban_lab',
                    'name'  => '3D Soroban Abacus Interactive Block (5-17 Rods)',
                    'icon'  => '🧮',
                    'order' => 14,
                    'data'  => [
                        'badge'        => 'INTERACTIVE 3D SOROBAN LAB',
                        'heading'      => 'Try Our 3D Japanese Soroban Abacus Live',
                        'subheading'   => 'Slide the beads or click New Target Challenge to test your mental math skills right now — no login required!',
                        'widget_mode'  => 'soroban_only',
                        'default_rods' => '5',
                        'theme'        => 'cyber_emerald',
                    ],
                ],
                [
                    'key'   => 'abacus_flash_anzan',
                    'name'  => 'Flash Anzan & Hindi/English Voice Dictation Block',
                    'icon'  => '⚡',
                    'order' => 15,
                    'data'  => [
                        'badge'        => 'SPEED MENTAL MATH ARENA',
                        'heading'      => 'Flash Anzan & Voice Dictation Challenge',
                        'subheading'   => 'Test your calculation speed from 2.0s down to 0.3s with Hindi & English voice dictation!',
                        'widget_mode'  => 'anzan_only',
                        'default_rods' => '5',
                        'theme'        => 'cyber_emerald',
                    ],
                ],
            ];

            foreach ($components as $c) {
                $json = json_encode($c['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->db->query("
                    INSERT INTO `cms_components`
                        (`component_key`, `component_name`, `component_icon`, `category`, `addon_slug`, `default_schema_json`, `is_enabled`, `sort_order`)
                    VALUES (?, ?, ?, 'interactive', 'abacus_pro_suite', ?, 1, ?)
                    ON DUPLICATE KEY UPDATE
                        `component_name`      = VALUES(`component_name`),
                        `component_icon`      = VALUES(`component_icon`),
                        `addon_slug`          = 'abacus_pro_suite',
                        `default_schema_json` = VALUES(`default_schema_json`),
                        `is_enabled`          = 1
                ", [$c['key'], $c['name'], $c['icon'], $json, $c['order']]);
            }
        } catch (Throwable $e) {
        }
    }

    public function cleanupNonStudentAndOrphanedAttempts(): void
    {
        try {
            if (! $this->db->tableExists('users') || ! $this->db->tableExists($this->table)) {
                return;
            }
            $this->db->query("
                DELETE a FROM `lms_abacus_attempts` a
                LEFT JOIN `users` u ON u.id = a.student_user_id
                WHERE u.id IS NULL
                   OR u.role_id IN (1, 2, 3)
                   OR u.role_slug IN ('super_admin', 'admin', 'teacher', 'parent')
            ");
        } catch (Throwable $e) {
        }
    }

    public function syncRbacPermissionsAndAutoEnableRoles(): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            if ($this->db->tableExists('cms_rbac_features')) {
                $perms = [
                    ['abacus_pro_suite.practice', 'Access 3D Soroban Lab, Flash Anzan & My Speed Badges [Student + Parent + Teacher]'],
                    ['abacus_pro_suite.view',     'View Batch Student Abacus Practice Analytics [Teacher + Admin]'],
                    ['abacus_pro_suite.edit',     'Configure Soroban Themes, Rods & Default Drill Settings [Super Admin + Admin]'],
                    ['abacus_pro_suite.delete',   'Delete Practice Attempt Logs [Super Admin + Admin]'],
                ];
                foreach ($perms as $p) {
                    $this->db->query("
                        INSERT INTO `cms_rbac_features` (`perm_key`, `perm_label`, `module_group`, `source_type`, `created_at`)
                        VALUES (?, ?, 'Add-on: 3D Abacus & Flash Anzan Lab', 'addon', ?)
                        ON DUPLICATE KEY UPDATE `perm_label` = VALUES(`perm_label`), `module_group` = VALUES(`module_group`)
                    ", [$p[0], $p[1], $now]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    public function mirrorAssets(bool $forceTouch = false): void
    {
        $srcDir = WRITEPATH . 'addons/extracted/abacus_pro_suite/assets';
        $dstDir = FCPATH . 'assets/addons/abacus_pro_suite';
        if (is_dir($srcDir)) {
            @is_dir($dstDir) || @mkdir($dstDir, 0775, true);
            foreach (['abacus_pro_suite.css', 'abacus_pro_suite.js'] as $f) {
                $sFile = $srcDir . '/' . $f;
                $dFile = $dstDir . '/' . $f;
                if (is_file($sFile) && ($forceTouch || ! is_file($dFile) || md5_file($sFile) !== md5_file($dFile))) {
                    @copy($sFile, $dFile);
                    @touch($dFile, time());
                }
            }
        }
    }

    public function getGlobalSiteSettings(): array
    {
        if (self::$globalSettingsCache !== null) {
            return self::$globalSettingsCache;
        }
        self::$globalSettingsCache = ['site_title' => 'Academy Portal', 'active_theme_mode' => 'dark', 'system_environment_mode' => 'testing'];
        try {
            if ($this->db->tableExists('settings')) {
                foreach ($this->db->table('settings')->get()->getResultArray() as $r) {
                    self::$globalSettingsCache[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Throwable $e) {
        }
        return self::$globalSettingsCache;
    }

    public function getAbacusConfig(): array
    {
        $s   = $this->getGlobalSiteSettings();
        $raw = $s['abacus_pro_suite__config_json'] ?? '';
        $cfg = $raw !== '' ? (json_decode((string) $raw, true) ?: []) : [];

        return array_merge([
            'default_rods'      => 5,
            'default_theme'     => 'cyber_emerald',
            'default_digits'    => 1,
            'default_rows'      => 5,
            'default_speed_sec' => 1.0,
            'default_lang'      => 'hi-IN',
            'enable_sound'      => 1,
            'enable_voice'      => 1,
        ], $cfg);
    }

    public function saveAbacusConfig(array $post): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }
        $cfg = [
            'default_rods'      => in_array((int) ($post['default_rods'] ?? 5), [5, 7, 9, 11, 13, 15, 17], true) ? (int) $post['default_rods'] : 5,
            'default_theme'     => in_array($post['default_theme'] ?? '', ['cyber_emerald', 'classic_wood', 'royal_gold'], true) ? $post['default_theme'] : 'cyber_emerald',
            'default_digits'    => max(1, min(5, (int) ($post['default_digits'] ?? 1))),
            'default_rows'      => max(2, min(25, (int) ($post['default_rows'] ?? 5))),
            'default_speed_sec' => max(0.2, min(5.0, (float) ($post['default_speed_sec'] ?? 1.0))),
            'default_lang'      => in_array($post['default_lang'] ?? '', ['hi-IN', 'en-IN'], true) ? $post['default_lang'] : 'hi-IN',
            'enable_sound'      => ! empty($post['enable_sound']) ? 1 : 0,
            'enable_voice'      => ! empty($post['enable_voice']) ? 1 : 0,
        ];

        $this->db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES ('abacus_pro_suite__config_json', 'abacus_pro_suite', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'abacus_pro_suite', `setting_value` = VALUES(`setting_value`)
        ", [json_encode($cfg, JSON_UNESCAPED_UNICODE)]);
        self::$globalSettingsCache = null;
    }

    public function logStudentAttemptStrict(string $effectiveRole, int $targetStudentId, array $post): array
    {
        $mode     = in_array($post['drill_mode'] ?? '', ['soroban_challenge', 'flash_anzan', 'voice_dictation'], true) ? $post['drill_mode'] : 'flash_anzan';
        $digits   = max(1, min(6, (int) ($post['digits_count'] ?? 1)));
        $rowsCnt  = max(1, min(30, (int) ($post['rows_count'] ?? 5)));
        $speedSec = max(0.1, min(10.0, (float) ($post['speed_seconds'] ?? 1.0)));
        $totalQ   = max(1, (int) ($post['total_questions'] ?? 1));
        $correctQ = max(0, min($totalQ, (int) ($post['correct_answers'] ?? 0)));
        $timeSec  = max(1, (int) ($post['time_taken_sec'] ?? round($rowsCnt * $speedSec)));
        $accPct   = round(($correctQ / $totalQ) * 100, 2);

        $isGenuineStudent = false;
        if ($effectiveRole === 'student' && $targetStudentId > 0 && $this->db->tableExists('users')) {
            $uRow = $this->db->table('users')->where('id', $targetStudentId)->get()->getRowArray();
            if ($uRow) {
                $rId   = (int) ($uRow['role_id'] ?? 4);
                $rSlug = strtolower(trim((string) ($uRow['role_slug'] ?? 'student')));
                if (! in_array($rId, [1, 2, 3], true) && ! in_array($rSlug, ['super_admin', 'admin', 'teacher', 'parent'], true)) {
                    $isGenuineStudent = true;
                }
            }
        }

        if (! $isGenuineStudent) {
            return [
                'ok'           => true,
                'saved_to_db'  => false,
                'accuracy_pct' => $accPct,
                'message'      => 'Score ' . $accPct . '%! (Log in with your Student account to save scores to your badge history).',
            ];
        }

        $cohortId = 0;
        $nodeId   = 0;
        if ($this->db->tableExists('lms_classroom_batch_students')) {
            $cs = $this->db->table('lms_classroom_batch_students')
                ->where('student_user_id', $targetStudentId)
                ->where('status', 'active')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
            if ($cs) {
                $cohortId = (int) $cs['cohort_id'];
                $nodeId   = (int) $cs['leaf_node_id'];
            }
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->db->table($this->table)->insert([
            'student_user_id' => $targetStudentId,
            'node_id'         => $nodeId,
            'cohort_id'       => $cohortId,
            'drill_mode'      => $mode,
            'digits_count'    => $digits,
            'rows_count'      => $rowsCnt,
            'speed_seconds'   => $speedSec,
            'total_questions' => $totalQ,
            'correct_answers' => $correctQ,
            'accuracy_pct'    => $accPct,
            'time_taken_sec'  => $timeSec,
            'created_at'      => $now,
        ]);

        return [
            'ok'           => true,
            'saved_to_db'  => true,
            'accuracy_pct' => $accPct,
            'message'      => ($accPct >= 100
                ? '🎉 100% Accuracy! Saved to your Student Progress & Badges!'
                : 'Practice saved (' . $accPct . '% Accuracy)!'),
        ];
    }

    public function getStudentProgressAndBadges(array $studentIds): array
    {
        $this->cleanupNonStudentAndOrphanedAttempts();
        $cleanIds = array_values(array_filter(array_map('intval', $studentIds), static fn($v) => $v > 0));
        if (empty($cleanIds)) {
            return ['total_drills' => 0, 'avg_accuracy' => 0.0, 'best_speed' => 0.0, 'perfect_cnt' => 0, 'badges' => [], 'attempts' => []];
        }

        $users = [];
        if ($this->db->tableExists('users')) {
            foreach ($this->db->table('users')->whereIn('id', $cleanIds)->get()->getResultArray() as $u) {
                $users[(int) $u['id']] = $u['full_name'] ?: ($u['name'] ?: 'Student');
            }
        }

        $rows = $this->db->table($this->table)->whereIn('student_user_id', $cleanIds)->orderBy('id', 'DESC')->limit(100)->get()->getResultArray();
        $totalDrills = count($rows);
        $accSum      = 0.0;
        $bestSpeed   = 99.0;
        $perfectCnt  = 0;

        foreach ($rows as &$r) {
            $sid = (int) $r['student_user_id'];
            $acc = (float) $r['accuracy_pct'];
            $spd = (float) $r['speed_seconds'];
            $r['student_name'] = $users[$sid] ?? 'Learner';
            $accSum += $acc;
            if ($acc >= 100.0) $perfectCnt++;
            if ($acc >= 80.0 && $spd > 0 && $spd < $bestSpeed) $bestSpeed = $spd;
        }
        unset($r);
        if ($bestSpeed >= 99.0) $bestSpeed = 0.0;
        $avgAcc = $totalDrills > 0 ? round($accSum / $totalDrills, 1) : 0.0;

        $badges = [
            ['icon' => '🧮', 'title' => 'Soroban Explorer',       'desc' => 'Complete your first Abacus or Anzan drill',     'unlocked' => $totalDrills >= 1],
            ['icon' => '🎯', 'title' => 'Sharp Shooter (100%)',   'desc' => 'Achieve 100% accuracy in 3+ speed drills',     'unlocked' => $perfectCnt >= 3],
            ['icon' => '⚡', 'title' => 'Sub-Second Speedster',   'desc' => 'Solve Flash Anzan at 0.7s speed or faster',    'unlocked' => ($bestSpeed > 0 && $bestSpeed <= 0.7)],
            ['icon' => '🔥', 'title' => 'Grandmaster Calculator', 'desc' => 'Complete 15+ drills with 80%+ avg accuracy',   'unlocked' => ($totalDrills >= 15 && $avgAcc >= 80.0)],
        ];

        return [
            'total_drills' => $totalDrills,
            'avg_accuracy' => $avgAcc,
            'best_speed'   => $bestSpeed,
            'perfect_cnt'  => $perfectCnt,
            'badges'       => $badges,
            'attempts'     => $rows,
        ];
    }

    public function getBatchAnalyticsForRole(int $teacherIdFilter = 0, int $selectedCohortId = 0): array
    {
        $this->cleanupNonStudentAndOrphanedAttempts();
        $cohortsMap = [];
        if ($this->db->tableExists('lms_classroom_batches')) {
            $cBuilder = $this->db->table('lms_classroom_batches')->orderBy('id', 'DESC');
            if ($teacherIdFilter > 0) $cBuilder->where('teacher_user_id', $teacherIdFilter);
            foreach ($cBuilder->get()->getResultArray() as $c) {
                $cohortsMap[(int) $c['id']] = $c;
            }
        }

        $allowedStudentIds = [];
        if ($teacherIdFilter > 0) {
            $cIds = array_keys($cohortsMap);
            if (! empty($cIds) && $this->db->tableExists('lms_classroom_batch_students')) {
                foreach ($this->db->table('lms_classroom_batch_students')->whereIn('cohort_id', $cIds)->get()->getResultArray() as $cs) {
                    $allowedStudentIds[(int) $cs['student_user_id']] = (int) $cs['cohort_id'];
                }
            }
            if (empty($allowedStudentIds)) {
                return ['cohorts' => $cohortsMap, 'leaderboard' => [], 'attempts' => []];
            }
        }

        $users = [];
        if ($this->db->tableExists('users')) {
            foreach ($this->db->table('users')->get()->getResultArray() as $u) {
                $users[(int) $u['id']] = $u['full_name'] ?: ($u['name'] ?: 'Student');
            }
        }

        $aBuilder = $this->db->table($this->table)->orderBy('id', 'DESC')->limit(150);
        if ($teacherIdFilter > 0) $aBuilder->whereIn('student_user_id', array_keys($allowedStudentIds));
        if ($selectedCohortId > 0) $aBuilder->where('cohort_id', $selectedCohortId);

        $attempts    = $aBuilder->get()->getResultArray();
        $leaderboard = [];
        foreach ($attempts as &$at) {
            $sid = (int) $at['student_user_id'];
            $cid = (int) ($at['cohort_id'] ?: ($allowedStudentIds[$sid] ?? 0));
            $at['student_name'] = $users[$sid] ?? 'Student';
            $at['batch_title']  = isset($cohortsMap[$cid]) ? $cohortsMap[$cid]['cohort_title'] : 'Enrolled Batch';

            if (! isset($leaderboard[$sid])) {
                $leaderboard[$sid] = ['student_id' => $sid, 'student_name' => $at['student_name'], 'batch_title' => $at['batch_title'], 'drills_count' => 0, 'acc_sum' => 0.0, 'best_speed' => 99.0];
            }
            $leaderboard[$sid]['drills_count']++;
            $leaderboard[$sid]['acc_sum'] += (float) $at['accuracy_pct'];
            $spd = (float) $at['speed_seconds'];
            if ((float) $at['accuracy_pct'] >= 80.0 && $spd > 0 && $spd < $leaderboard[$sid]['best_speed']) {
                $leaderboard[$sid]['best_speed'] = $spd;
            }
        }
        unset($at);

        foreach ($leaderboard as &$lb) {
            $lb['avg_accuracy'] = $lb['drills_count'] > 0 ? round($lb['acc_sum'] / $lb['drills_count'], 1) : 0.0;
            if ($lb['best_speed'] >= 99.0) $lb['best_speed'] = 0.0;
        }
        unset($lb);

        return ['cohorts' => $cohortsMap, 'leaderboard' => array_values($leaderboard), 'attempts' => $attempts];
    }
}
'''

    aps_files["Controllers/Addons/AbacusProSuite/AbacusProSuiteController.php"] = r'''<?php

namespace App\Controllers\Addons\AbacusProSuite;

use App\Controllers\BaseController;
use App\Models\Addons\AbacusProSuite\AbacusProSuiteModel;

class AbacusProSuiteController extends BaseController
{
    protected ?AbacusProSuiteModel $apsModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/abacus_pro_suite';
    }

    private function getModel(): AbacusProSuiteModel
    {
        if ($this->apsModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/AbacusProSuite/AbacusProSuiteModel.php';
            if (! class_exists(AbacusProSuiteModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->apsModel = new AbacusProSuiteModel();
        }
        return $this->apsModel;
    }

    private function hasPermission($permissionKeys): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('cms_has_permission')) {
            return cms_has_permission($permissionKeys);
        }
        return (int) session()->get('role_id') === 1 || (string) session()->get('role_slug') === 'super_admin';
    }

    private function renderAddonView(string $viewRelPath, array $data = []): string
    {
        if (! isset($data['settings']) || ! is_array($data['settings'])) {
            $data['settings'] = $this->getModel()->getGlobalSiteSettings();
        }
        if (empty($data['activeMenu'])) {
            $data['activeMenu'] = 'addon_abacus_pro_suite';
        }
        if (empty($data['user'])) {
            $data['user'] = session()->get() ?: [];
        }

        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonAbacusProSuite', $this->addonPath);
        $nsView = 'AddonAbacusProSuite\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonContext(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->syncPageBuilderComponents();
        $model->mirrorAssets(false);

        $isLoggedIn    = (bool) session()->get('isLoggedIn');
        $effectiveRole = $isLoggedIn
            ? (function_exists('cms_get_effective_role') ? cms_get_effective_role() : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student'))))
            : 'guest';

        $uid              = (int) (session()->get('user_id') ?: 0);
        $activeStuId      = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode   = (string) (session()->get('portal_view_mode') ?: 'default');
        $isParentOverview = ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0));
        $parentKidsMap    = [];
        $targetStudentIds = [];

        if ($isParentOverview && $uid > 0) {
            $db = \Config\Database::connect();
            if ($db->tableExists('users')) {
                foreach ($db->table('users')->select('id, full_name')->where('parent_user_id', $uid)->orderBy('id', 'ASC')->get()->getResultArray() as $k) {
                    $kidId = (int) $k['id'];
                    $parentKidsMap[$kidId] = $k['full_name'] ?: 'Child';
                    $targetStudentIds[]    = $kidId;
                }
            }
        } elseif ($effectiveRole === 'student' && ($activeStuId > 0 || $uid > 0)) {
            $targetStudentIds = [$activeStuId > 0 ? $activeStuId : $uid];
        }

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_abacus_pro_suite',
            'activeNav'        => 'abacus_pro_suite',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'abacusConfig'     => $model->getAbacusConfig(),
            'user'             => session()->get() ?: [],
            'isLoggedIn'       => $isLoggedIn,
            'effectiveRole'    => $effectiveRole,
            'uid'              => $uid,
            'activeStuId'      => $activeStuId > 0 ? $activeStuId : $uid,
            'isParentOverview' => $isParentOverview,
            'parentKidsMap'    => $parentKidsMap,
            'targetStudentIds' => $targetStudentIds,
            'canPractice'      => true,
            'canViewAnalytics' => $this->hasPermission('abacus_pro_suite.view'),
            'canEditSettings'  => $this->hasPermission('abacus_pro_suite.edit'),
            'canDelete'        => $this->hasPermission('abacus_pro_suite.delete'),
        ];
    }

    public function index()
    {
        if ($this->hasPermission('abacus_pro_suite.view')) {
            return $this->batchAnalyticsView();
        }
        return $this->arenaView();
    }

    /**
     * Works for both Logged-in Users AND Non-Logged-in Public Visitors!
     */
    public function arenaView()
    {
        if (session()->get('isLoggedIn') && (int) session()->get('lms_child_fee_locked') === 1) {
            return redirect()->to(base_url('finance/portal?locked=1'));
        }
        $data = $this->buildCommonContext('arena', '3D Soroban Abacus Lab');
        return $this->renderAddonView('addons/abacus_pro_suite/arena_view', $data);
    }

    /**
     * Works for both Logged-in Users AND Non-Logged-in Public Visitors!
     */
    public function anzanView()
    {
        if (session()->get('isLoggedIn') && (int) session()->get('lms_child_fee_locked') === 1) {
            return redirect()->to(base_url('finance/portal?locked=1'));
        }
        $data = $this->buildCommonContext('anzan', 'Flash Anzan & Voice Dictation Speed Arena');
        return $this->renderAddonView('addons/abacus_pro_suite/anzan_view', $data);
    }

    public function myProgressView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $data = $this->buildCommonContext('progress', 'My Abacus Speed & Achievement Badges');
        $data['progressData'] = $this->getModel()->getStudentProgressAndBadges($data['targetStudentIds']);
        return $this->renderAddonView('addons/abacus_pro_suite/progress_view', $data);
    }

    public function batchAnalyticsView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['abacus_pro_suite.view', 'abacus_pro_suite.edit'])) {
            return redirect()->to(base_url('abacus/arena'));
        }
        $data            = $this->buildCommonContext('analytics', 'Batch Student Abacus Practice Analytics');
        $teacherFilter   = ($data['effectiveRole'] === 'teacher') ? (int) $data['uid'] : 0;
        $selectedCohort  = (int) ($this->request->getGet('cohort_id') ?? 0);
        $analyticsBundle = $this->getModel()->getBatchAnalyticsForRole($teacherFilter, $selectedCohort);

        $data['cohortsMap']       = $analyticsBundle['cohorts'];
        $data['leaderboard']      = $analyticsBundle['leaderboard'];
        $data['batchAttempts']    = $analyticsBundle['attempts'];
        $data['selectedCohortId'] = $selectedCohort;

        return $this->renderAddonView('addons/abacus_pro_suite/analytics_view', $data);
    }

    public function settingsView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('abacus_pro_suite.edit')) {
            return redirect()->to(base_url('abacus/arena'));
        }
        $data = $this->buildCommonContext('settings', 'Abacus Theme & Default Drill Settings');
        return $this->renderAddonView('addons/abacus_pro_suite/settings_view', $data);
    }

    /**
     * Works for Guests (Non-Logged-In) AND Logged-In Students!
     * Guests get instant score calculation (`saved_to_db: false`), while logged-in Students save to DB!
     */
    public function logAttemptAjax()
    {
        $isLoggedIn    = (bool) session()->get('isLoggedIn');
        $effectiveRole = $isLoggedIn
            ? (function_exists('cms_get_effective_role') ? cms_get_effective_role() : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student'))))
            : 'guest';
        $uid         = (int) (session()->get('user_id') ?: 0);
        $activeStuId = (int) (session()->get('active_student_id') ?: 0);
        $targetStuId = $activeStuId > 0 ? $activeStuId : $uid;

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $res = $this->getModel()->logStudentAttemptStrict($effectiveRole, $targetStuId, $this->request->getPost());
        $res['csrf_token'] = csrf_hash();
        return $this->response->setJSON($res);
    }

    public function saveSettings()
    {
        if (! $this->hasPermission('abacus_pro_suite.edit')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $this->getModel()->saveAbacusConfig($this->request->getPost());
        return redirect()->to(base_url('addons/abacus-pro-suite/settings'))->with('success', 'Settings saved!');
    }

    public function deleteAttempt()
    {
        if (! $this->hasPermission('abacus_pro_suite.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $this->getModel()->delete($id);
        }
        return redirect()->back()->with('success', 'Practice log removed.');
    }

    public function asset($file = '')
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('');
        }
        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        return $this->response->setHeader('Content-Type', $mime)->setHeader('Cache-Control', 'no-cache, must-revalidate')->setBody((string) file_get_contents($fullPath));
    }
}
'''

    aps_files["Views/addons/abacus_pro_suite/partials/sub_nav.php"] = r'''<?php
    $currSub = $activeSubTab ?? 'arena';
?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="aps-compact-bar">
    <div>
        <h3 style="margin:0;">
            <?php if (($effectiveRole ?? '') === 'student'): ?>
                🧮 My 3D Soroban &amp; Mental Math Speed Arena
            <?php elseif (! empty($isParentOverview)): ?>
                👨‍👩‍👧‍👦 Family Abacus Lab &amp; Children's Speed Progress
            <?php else: ?>
                🧮 3D Soroban Lab (5–17 Rods) &amp; Flash Anzan Speed Arena
            <?php endif; ?>
        </h3>
    </div>

    <div class="settings-tabs" style="margin:0;">
        <a href="<?= base_url('abacus/arena') ?>" class="settings-tab <?= $currSub === 'arena' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>🧮 3D Soroban Lab (5–17 Rods)</span>
        </a>
        <a href="<?= base_url('abacus/anzan') ?>" class="settings-tab <?= $currSub === 'anzan' ? 'is-active' : '' ?>" style="text-decoration:none;">
            <span>⚡ Flash Anzan &amp; Hindi/Eng Voice</span>
        </a>
        <?php if (! empty($isLoggedIn) && in_array($effectiveRole ?? '', ['student', 'parent'], true)): ?>
            <a href="<?= base_url('abacus/my-progress') ?>" class="settings-tab <?= $currSub === 'progress' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>🏆 My Speed &amp; Badges</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canViewAnalytics)): ?>
            <a href="<?= base_url('addons/abacus-pro-suite/analytics') ?>" class="settings-tab <?= $currSub === 'analytics' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>📊 Batch Analytics</span>
            </a>
        <?php endif; ?>
        <?php if (! empty($canEditSettings)): ?>
            <a href="<?= base_url('addons/abacus-pro-suite/settings') ?>" class="settings-tab <?= $currSub === 'settings' ? 'is-active' : '' ?>" style="text-decoration:none;">
                <span>⚙ Theme &amp; Settings</span>
            </a>
        <?php endif; ?>
    </div>
</div>
'''

    aps_files["Views/addons/abacus_pro_suite/arena_view.php"] = r'''<?php
    $layoutToExtend = ! empty($isLoggedIn) ? 'layouts/master_layout' : 'layouts/frontend_layout';
?>
<?= $this->extend($layoutToExtend) ?>

<?= $this->section('content') ?>
<?php
    $cfg          = $abacusConfig ?? [];
    $defaultRods  = (int) ($cfg['default_rods'] ?? 5);
    $defaultTheme = (string) ($cfg['default_theme'] ?? 'cyber_emerald');
    $soundOn      = (int) ($cfg['enable_sound'] ?? 1) === 1;
?>
<div class="aps-wrapper" id="js-aps-soroban-root"
     data-log-url="<?= base_url('abacus/log-attempt') ?>"
     data-rods="<?= $defaultRods ?>"
     data-theme="<?= esc($defaultTheme) ?>"
     data-sound="<?= $soundOn ? '1' : '0' ?>">

    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <div class="aps-soroban-toolbar">
        <div class="aps-readout-group">
            <div>
                <span class="mono-label">CURRENT ABACUS VALUE</span>
                <div class="aps-digital-readout" id="js-soroban-current-val">0</div>
            </div>

            <div class="aps-target-box" id="js-soroban-target-box">
                <span class="mono-label text-cyan">🎯 TARGET CHALLENGE</span>
                <div class="aps-target-val" id="js-soroban-target-val">—</div>
            </div>
        </div>

        <div class="aps-controls-group">
            <select id="js-soroban-rods-select" class="field-select" style="width:155px;">
                <?php foreach ([5 => '5 Rods (Beginner)', 7 => '7 Rods (Jr Level)', 9 => '9 Rods (Intermediate)', 11 => '11 Rods', 13 => '13 Rods (Standard)', 15 => '15 Rods', 17 => '17 Rods (Master)'] as $rCnt => $rLbl): ?>
                    <option value="<?= $rCnt ?>" <?= $defaultRods === $rCnt ? 'selected' : '' ?>><?= $rLbl ?></option>
                <?php endforeach; ?>
            </select>

            <select id="js-soroban-theme-select" class="field-select" style="width:165px;">
                <option value="cyber_emerald" <?= $defaultTheme === 'cyber_emerald' ? 'selected' : '' ?>>✨ Cyber Emerald</option>
                <option value="classic_wood" <?= $defaultTheme === 'classic_wood' ? 'selected' : '' ?>>🪵 Classic Rosewood</option>
                <option value="royal_gold" <?= $defaultTheme === 'royal_gold' ? 'selected' : '' ?>>👑 Royal Gold</option>
            </select>

            <button type="button" class="btn-secondary btn-sm" id="js-soroban-sound-btn">
                <?= $soundOn ? '🔊 Sound: ON' : '🔇 Sound: OFF' ?>
            </button>

            <button type="button" class="btn-primary btn-sm" id="js-soroban-new-challenge">
                🎯 New Target Challenge
            </button>

            <button type="button" class="btn-secondary btn-sm" id="js-soroban-clear-btn">
                ↺ Reset (0)
            </button>
        </div>
    </div>

    <div id="js-soroban-challenge-toast" class="alert-banner alert-success is-hidden" style="margin-bottom:1rem;"></div>

    <section class="aps-soroban-stage theme-<?= esc($defaultTheme) ?>" id="js-soroban-frame-wrap">
        <div class="aps-soroban-outer-frame">
            <div class="aps-soroban-rods-container" id="js-soroban-rods-container"></div>
        </div>
        <div class="aps-soroban-helper-strip">
            <span>💡 <strong>Tip:</strong> Click any <strong>Upper Bead (Value = 5)</strong> to toggle it down, or click any <strong>Lower Bead (Value = 1–4)</strong> to slide it up. Starts from <strong>5 Rods</strong> for beginner learners up to <strong>17 Rods</strong>.</span>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
'''

    aps_files["Views/addons/abacus_pro_suite/anzan_view.php"] = r'''<?php
    $layoutToExtend = ! empty($isLoggedIn) ? 'layouts/master_layout' : 'layouts/frontend_layout';
?>
<?= $this->extend($layoutToExtend) ?>

<?= $this->section('content') ?>
<?php
    $cfg     = $abacusConfig ?? [];
    $defDig  = (int) ($cfg['default_digits'] ?? 1);
    $defRows = (int) ($cfg['default_rows'] ?? 5);
    $defSpd  = (float) ($cfg['default_speed_sec'] ?? 1.0);
    $defLang = (string) ($cfg['default_lang'] ?? 'hi-IN');
?>
<div class="aps-wrapper" id="js-aps-anzan-root" data-log-url="<?= base_url('abacus/log-attempt') ?>">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <div class="aps-anzan-layout">
        <section class="console-card">
            <div class="card-head">
                <div>
                    <span class="role-badge text-emerald">ULTRA-SMOOTH FLASH &amp; VOICE</span>
                    <h3 style="margin-top:0.3rem;">Flash Anzan &amp; Hindi/Eng Dictation</h3>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Drill Mode</span></label>
                    <select id="js-anz-mode" class="field-select">
                        <option value="voice_dictation">🎙 Voice Dictation + Flash</option>
                        <option value="flash_anzan">⚡ Silent Flash + Beep</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span class="text-emerald">Voice Language</span></label>
                    <select id="js-anz-lang" class="field-select">
                        <option value="hi-IN" <?= $defLang === 'hi-IN' ? 'selected' : '' ?>>🇮🇳 हिन्दी वॉइस (Hindi)</option>
                        <option value="en-IN" <?= $defLang === 'en-IN' ? 'selected' : '' ?>>🌐 English Voice</option>
                    </select>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span>Number of Digits</span></label>
                    <select id="js-anz-digits" class="field-select">
                        <?php foreach ([1 => '1 Digit (1-9)', 2 => '2 Digits (10-99)', 3 => '3 Digits (100-999)', 4 => '4 Digits'] as $dVal => $dLbl): ?>
                            <option value="<?= $dVal ?>" <?= $defDig === $dVal ? 'selected' : '' ?>><?= $dLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Total Rows (Numbers)</span></label>
                    <select id="js-anz-rows" class="field-select">
                        <?php foreach ([3, 5, 8, 10, 12, 15, 20] as $rCnt): ?>
                            <option value="<?= $rCnt ?>" <?= $defRows === $rCnt ? 'selected' : '' ?>><?= $rCnt ?> Rows</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2-col">
                <div class="field-group">
                    <label class="field-label"><span class="text-cyan">Flash Speed (0.3s – 2.0s)</span></label>
                    <select id="js-anz-speed" class="field-select">
                        <?php foreach (['2.0' => '2.0s (Beginner)', '1.5' => '1.5s (Easy)', '1.0' => '1.0s (Standard)', '0.7' => '0.7s (Fast)', '0.5' => '0.5s (Turbo)', '0.3' => '0.3s (Grandmaster)'] as $sVal => $sLbl): ?>
                            <option value="<?= $sVal ?>" <?= abs($defSpd - (float) $sVal) < 0.05 ? 'selected' : '' ?>><?= $sLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Operations</span></label>
                    <select id="js-anz-ops" class="field-select">
                        <option value="add_sub">➕➖ Addition &amp; Subtraction</option>
                        <option value="add_only">➕ Addition Only</option>
                    </select>
                </div>
            </div>

            <button type="button" class="btn-primary btn-full" id="js-anz-start-btn" style="justify-content:center; padding:0.85rem;">
                ▶ Start Speed Drill Now
            </button>
        </section>

        <section class="aps-anzan-screen-card">
            <div class="aps-anzan-hud">
                <span class="role-badge" id="js-anz-progress-pill">READY TO START</span>
                <span class="mono-cell text-cyan" id="js-anz-timer-pill">⏱ 0.0s</span>
            </div>

            <div class="aps-anzan-display-box" id="js-anz-display-box">
                <div class="aps-anzan-big-num" id="js-anz-big-num">READY</div>
                <p class="muted-cell" id="js-anz-sub-hint">Select Hindi or English voice &amp; speed on the left, then click Start.</p>
            </div>

            <form id="js-anz-answer-form" class="aps-anzan-answer-bar is-hidden">
                <input type="number" id="js-anz-user-ans" class="field-input mono-input" placeholder="Enter Final Sum..." autocomplete="off" required>
                <button type="submit" class="btn-primary">✓ Check Answer</button>
                <button type="button" class="btn-secondary" id="js-anz-replay-btn">↻ Next Set</button>
            </form>

            <div id="js-anz-result-banner" class="alert-banner is-hidden" style="margin-top:1rem;"></div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
'''

    # NEW: Frontend Page Builder Block View (`block_view.php`) — Supports Both 3D Soroban Block AND Flash Anzan Block for Guests!
    aps_files["Views/addons/abacus_pro_suite/block_view.php"] = r'''<?php
    $bd         = $data ?? [];
    $compType   = $blockType ?? ($bd['component_key'] ?? 'abacus_soroban_lab');
    $widgetMode = $bd['widget_mode'] ?? ($compType === 'abacus_flash_anzan' ? 'anzan_only' : 'soroban_only');
    $defRods    = (int) ($bd['default_rods'] ?? 5);
    if (! in_array($defRods, [5, 7, 9, 11, 13, 15, 17], true)) {
        $defRods = 5;
    }
    $defTheme   = $bd['theme'] ?? 'cyber_emerald';
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/abacus_pro_suite/abacus_pro_suite.css') ?>">
<script src="<?= base_url('assets/addons/abacus_pro_suite/abacus_pro_suite.js') ?>" defer></script>

<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> aps-frontend-block is-revealed" style="padding: 2.5rem 0;">
    <div class="cms-inner-container">
        <?php if (! empty($bd['heading']) || ! empty($bd['badge'])): ?>
            <div style="text-align:center; margin-bottom:1.5rem;">
                <?php if (! empty($bd['badge'])): ?>
                    <span class="role-badge text-emerald"><?= esc($bd['badge']) ?></span>
                <?php endif; ?>
                <?php if (! empty($bd['heading'])): ?>
                    <h2 style="margin:0.4rem 0;"><?= esc($bd['heading']) ?></h2>
                <?php endif; ?>
                <?php if (! empty($bd['subheading'])): ?>
                    <p class="muted-cell" style="max-width:640px; margin:0 auto;"><?= esc($bd['subheading']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($widgetMode === 'anzan_only'): ?>
            <div class="aps-wrapper" id="js-aps-anzan-root" data-log-url="<?= base_url('abacus/log-attempt') ?>">
                <div class="aps-anzan-layout">
                    <div class="console-card">
                        <div class="grid-2-col">
                            <div class="field-group">
                                <label class="field-label"><span>Mode</span></label>
                                <select id="js-anz-mode" class="field-select">
                                    <option value="voice_dictation">🎙 Voice + Flash</option>
                                    <option value="flash_anzan">⚡ Silent Flash</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Voice Language</span></label>
                                <select id="js-anz-lang" class="field-select">
                                    <option value="hi-IN">🇮🇳 हिन्दी वॉइस</option>
                                    <option value="en-IN">🌐 English Voice</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3-col">
                            <div class="field-group">
                                <label class="field-label"><span>Digits</span></label>
                                <select id="js-anz-digits" class="field-select">
                                    <option value="1">1 Digit</option>
                                    <option value="2">2 Digits</option>
                                    <option value="3">3 Digits</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Rows</span></label>
                                <select id="js-anz-rows" class="field-select">
                                    <option value="3">3 Rows</option>
                                    <option value="5" selected>5 Rows</option>
                                    <option value="8">8 Rows</option>
                                    <option value="10">10 Rows</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label"><span>Speed</span></label>
                                <select id="js-anz-speed" class="field-select">
                                    <option value="1.5">1.5s</option>
                                    <option value="1.0" selected>1.0s</option>
                                    <option value="0.7">0.7s</option>
                                    <option value="0.5">0.5s</option>
                                    <option value="0.3">0.3s</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" id="js-anz-ops" value="add_sub">
                        <button type="button" class="btn-primary btn-full" id="js-anz-start-btn" style="justify-content:center;">▶ Start Speed Drill</button>
                    </div>

                    <div class="aps-anzan-screen-card">
                        <div class="aps-anzan-hud">
                            <span class="role-badge" id="js-anz-progress-pill">READY</span>
                            <span class="mono-cell text-cyan" id="js-anz-timer-pill">⏱ 0.0s</span>
                        </div>
                        <div class="aps-anzan-display-box" id="js-anz-display-box">
                            <div class="aps-anzan-big-num" id="js-anz-big-num">READY</div>
                            <p class="muted-cell" id="js-anz-sub-hint">Click Start to test your mental math speed!</p>
                        </div>
                        <form id="js-anz-answer-form" class="aps-anzan-answer-bar is-hidden">
                            <input type="number" id="js-anz-user-ans" class="field-input mono-input" placeholder="Final Sum..." required>
                            <button type="submit" class="btn-primary">✓ Check</button>
                            <button type="button" class="btn-secondary" id="js-anz-replay-btn">↻ Next</button>
                        </form>
                        <div id="js-anz-result-banner" class="alert-banner is-hidden" style="margin-top:0.85rem;"></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="aps-wrapper" id="js-aps-soroban-root"
                 data-log-url="<?= base_url('abacus/log-attempt') ?>"
                 data-rods="<?= $defRods ?>"
                 data-theme="<?= esc($defTheme) ?>"
                 data-sound="1">
                <div class="aps-soroban-toolbar">
                    <div class="aps-readout-group">
                        <div>
                            <span class="mono-label">ABACUS VALUE</span>
                            <div class="aps-digital-readout" id="js-soroban-current-val">0</div>
                        </div>
                        <div class="aps-target-box" id="js-soroban-target-box">
                            <span class="mono-label text-cyan">🎯 TARGET</span>
                            <div class="aps-target-val" id="js-soroban-target-val">—</div>
                        </div>
                    </div>
                    <div class="aps-controls-group">
                        <select id="js-soroban-rods-select" class="field-select" style="width:145px;">
                            <?php foreach ([5 => '5 Rods (Beginner)', 7 => '7 Rods', 9 => '9 Rods', 11 => '11 Rods', 13 => '13 Rods', 15 => '15 Rods', 17 => '17 Rods'] as $rCnt => $rLbl): ?>
                                <option value="<?= $rCnt ?>" <?= $defRods === $rCnt ? 'selected' : '' ?>><?= $rLbl ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="js-soroban-theme-select" class="field-select" style="width:155px;">
                            <option value="cyber_emerald">✨ Cyber Emerald</option>
                            <option value="classic_wood">🪵 Classic Wood</option>
                            <option value="royal_gold">👑 Royal Gold</option>
                        </select>
                        <button type="button" class="btn-secondary btn-sm" id="js-soroban-sound-btn">🔊 Sound: ON</button>
                        <button type="button" class="btn-primary btn-sm" id="js-soroban-new-challenge">🎯 Challenge</button>
                        <button type="button" class="btn-secondary btn-sm" id="js-soroban-clear-btn">↺ Reset</button>
                    </div>
                </div>
                <div id="js-soroban-challenge-toast" class="alert-banner alert-success is-hidden" style="margin-bottom:1rem;"></div>
                <div class="aps-soroban-stage theme-<?= esc($defTheme) ?>" id="js-soroban-frame-wrap">
                    <div class="aps-soroban-outer-frame">
                        <div class="aps-soroban-rods-container" id="js-soroban-rods-container"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
'''

    aps_files["Views/addons/abacus_pro_suite/settings_view.php"] = r'''<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php $cfg = $abacusConfig ?? []; ?>
<div class="aps-wrapper">
    <?php include __DIR__ . '/partials/sub_nav.php'; ?>

    <section class="console-card" style="max-width: 720px; margin: 0 auto;">
        <div class="card-head">
            <div>
                <h3>Soroban Theme, Rods (5–17) &amp; Voice Settings</h3>
            </div>
        </div>

        <form action="<?= base_url('addons/abacus-pro-suite/save-settings') ?>" method="post">
            <?= csrf_field() ?>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Default Soroban Theme</span></label>
                    <select name="default_theme" class="field-select">
                        <option value="cyber_emerald" <?= ($cfg['default_theme'] ?? '') === 'cyber_emerald' ? 'selected' : '' ?>>✨ Cyber Emerald</option>
                        <option value="classic_wood" <?= ($cfg['default_theme'] ?? '') === 'classic_wood' ? 'selected' : '' ?>>🪵 Classic Rosewood</option>
                        <option value="royal_gold" <?= ($cfg['default_theme'] ?? '') === 'royal_gold' ? 'selected' : '' ?>>👑 Royal Gold</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Default Abacus Rods (5–17)</span></label>
                    <select name="default_rods" class="field-select">
                        <?php foreach ([5 => '5 Rods (Beginner)', 7 => '7 Rods', 9 => '9 Rods', 11 => '11 Rods', 13 => '13 Rods (Standard)', 15 => '15 Rods', 17 => '17 Rods'] as $rCnt => $rLbl): ?>
                            <option value="<?= $rCnt ?>" <?= (int) ($cfg['default_rods'] ?? 5) === $rCnt ? 'selected' : '' ?>><?= $rLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Default Voice Language</span></label>
                    <select name="default_lang" class="field-select">
                        <option value="hi-IN" <?= ($cfg['default_lang'] ?? 'hi-IN') === 'hi-IN' ? 'selected' : '' ?>>🇮🇳 हिन्दी वॉइस (Hindi)</option>
                        <option value="en-IN" <?= ($cfg['default_lang'] ?? '') === 'en-IN' ? 'selected' : '' ?>>🌐 English Voice</option>
                    </select>
                </div>
            </div>

            <div class="grid-3-col">
                <div class="field-group">
                    <label class="field-label"><span>Default Anzan Digits</span></label>
                    <input type="number" name="default_digits" class="field-input mono-input" value="<?= (int) ($cfg['default_digits'] ?? 1) ?>" min="1" max="5">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Default Anzan Rows</span></label>
                    <input type="number" name="default_rows" class="field-input mono-input" value="<?= (int) ($cfg['default_rows'] ?? 5) ?>" min="2" max="25">
                </div>
                <div class="field-group">
                    <label class="field-label"><span>Default Flash Speed (Sec)</span></label>
                    <input type="number" step="0.1" name="default_speed_sec" class="field-input mono-input" value="<?= (float) ($cfg['default_speed_sec'] ?? 1.0) ?>" min="0.2" max="5.0">
                </div>
            </div>

            <div class="grid-2-col" style="margin: 0.75rem 0 1.25rem;">
                <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                    <input type="checkbox" name="enable_sound" value="1" <?= ! empty($cfg['enable_sound']) ? 'checked' : '' ?>>
                    <strong>🔊 Enable Soroban Bead Click Audio</strong>
                </label>
                <label style="display:flex; align-items:center; gap:0.55rem; cursor:pointer;">
                    <input type="checkbox" name="enable_voice" value="1" <?= ! empty($cfg['enable_voice']) ? 'checked' : '' ?>>
                    <strong>🎙 Enable Hindi &amp; English Voice Dictation</strong>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-full">💾 Save Abacus Configuration</button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
'''

    aps_files["assets/abacus_pro_suite.js"] = r'''(function () {
    'use strict';
    if (window.__CMS_ABACUS_PRO_SUITE_V85__) return;
    window.__CMS_ABACUS_PRO_SUITE_V85__ = true;

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Register BOTH Frontend Page Builder Blocks!
    if (!window.CMS_ADDON_BLOCK_RENDERERS) {
        window.CMS_ADDON_BLOCK_RENDERERS = {};
    }

    window.CMS_ADDON_BLOCK_RENDERERS['abacus_soroban_lab'] = function (blk, idx, isFooter, colIdx) {
        var d = blk.data ? blk.data : {};
        var cls = isFooter ? 'js-f-field' : 'js-blk-field';
        var attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        var rods = String(d.default_rods || '5');
        var thm  = String(d.theme || 'cyber_emerald');
        return '<div class="grid-3-col">' +
            '<div class="field-group"><label class="field-label"><span>Heading</span></label>' +
            '<input type="text" class="field-input ' + cls + '" ' + attr + ' data-key="heading" value="' + escapeHtml(d.heading || 'Try Our 3D Japanese Soroban Abacus Live') + '"></div>' +
            '<div class="field-group"><label class="field-label"><span>Default Rods (5–17)</span></label>' +
            '<select class="field-select ' + cls + '" ' + attr + ' data-key="default_rods">' +
            ['5','7','9','11','13','15','17'].map(function(r){ return '<option value="'+r+'" '+(rods===r?'selected':'')+'>'+r+' Rods</option>'; }).join('') +
            '</select></div>' +
            '<div class="field-group"><label class="field-label"><span>Theme</span></label>' +
            '<select class="field-select ' + cls + '" ' + attr + ' data-key="theme">' +
            '<option value="cyber_emerald" '+(thm==='cyber_emerald'?'selected':'')+'>Cyber Emerald</option>' +
            '<option value="classic_wood" '+(thm==='classic_wood'?'selected':'')+'>Classic Wood</option>' +
            '<option value="royal_gold" '+(thm==='royal_gold'?'selected':'')+'>Royal Gold</option>' +
            '</select></div></div>';
    };

    window.CMS_ADDON_BLOCK_RENDERERS['abacus_flash_anzan'] = function (blk, idx, isFooter, colIdx) {
        var d = blk.data ? blk.data : {};
        var cls = isFooter ? 'js-f-field' : 'js-blk-field';
        var attr = isFooter ? ('data-col="' + colIdx + '" data-bidx="' + idx + '"') : ('data-idx="' + idx + '"');
        return '<div class="grid-2-col">' +
            '<div class="field-group"><label class="field-label"><span>Badge</span></label>' +
            '<input type="text" class="field-input ' + cls + '" ' + attr + ' data-key="badge" value="' + escapeHtml(d.badge || 'SPEED MENTAL MATH ARENA') + '"></div>' +
            '<div class="field-group"><label class="field-label"><span>Heading</span></label>' +
            '<input type="text" class="field-input ' + cls + '" ' + attr + ' data-key="heading" value="' + escapeHtml(d.heading || 'Flash Anzan & Voice Dictation Challenge') + '"></div>' +
            '</div>';
    };

    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'abacus_soroban_lab' } }));
    document.dispatchEvent(new CustomEvent('cms:addon-renderer-ready', { detail: { type: 'abacus_flash_anzan' } }));

    var audioCtx = null;
    function playBeadClick(freq, durationMs) {
        try {
            if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (!audioCtx) return;
            if (audioCtx.state === 'suspended') audioCtx.resume();
            var osc  = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq || 540, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.16, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + ((durationMs || 38) / 1000));
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + ((durationMs || 38) / 1000));
        } catch (e) {}
    }

    // Authentic Hindi Number Words (1 to 99) for Crisp Hindi Dictation!
    var HINDI_NUMS = {
        0:'शून्य', 1:'एक', 2:'दो', 3:'तीन', 4:'चार', 5:'पाँच', 6:'छह', 7:'सात', 8:'आठ', 9:'नौ', 10:'दस',
        11:'ग्यारह', 12:'बारह', 13:'तेरह', 14:'चौदह', 15:'पंद्रह', 16:'सोलह', 17:'सत्रह', 18:'अठारह', 19:'उन्नीस', 20:'बीस',
        21:'इक्कीस', 22:'बाईस', 23:'तेईस', 24:'चौबीस', 25:'पच्चीस', 26:'छब्बीस', 27:'सत्ताईस', 28:'अट्ठाईस', 29:'उनतीस', 30:'तीस',
        31:'इकतीस', 32:'बत्तीस', 33:'तैंतीस', 34:'चौंतीस', 35:'पैंतीस', 36:'छत्तीस', 37:'सैंतीस', 38:'अड़तीस', 39:'उनतालीस', 40:'चालीस',
        41:'इकतालीस', 42:'बयालीस', 43:'तैंतालीस', 44:'चवालीस', 45:'पैंतालीस', 46:'छियालीस', 47:'सैंतालीस', 48:'अड़तालीस', 49:'उनचास', 50:'पचास'
    };

    var cachedVoices = [];
    function loadVoices() {
        if ('speechSynthesis' in window) {
            cachedVoices = window.speechSynthesis.getVoices() || [];
        }
    }
    if ('speechSynthesis' in window) {
        loadVoices();
        window.speechSynthesis.onvoiceschanged = loadVoices;
    }

    /**
     * Ultra-Smooth Fast Voice Dictation Engine (Supports 0.3s, 0.5s, 0.7s, 1.0s, 1.5s, 2.0s in Hindi & English!)
     */
    function speakCrispNumber(val, langCode, speedSec) {
        playBeadClick(val >= 0 ? 600 : 420, 32);
        try {
            if (!('speechSynthesis' in window)) return;
            var absVal = Math.abs(val);
            var isHindi = (langCode === 'hi-IN');
            var spokenText = '';

            if (isHindi) {
                var numWord = HINDI_NUMS[absVal] || String(absVal);
                spokenText = val < 0 ? ('माइनस ' + numWord) : numWord;
            } else {
                spokenText = val < 0 ? ('Minus ' + absVal) : String(absVal);
            }

            // Dynamic Rate Scaling so 0.3s / 0.5s / 0.7s finishes speaking BEFORE the next number flashes!
            var dynRate = 1.25;
            if (speedSec <= 0.35) dynRate = 2.85;
            else if (speedSec <= 0.55) dynRate = 2.25;
            else if (speedSec <= 0.8) dynRate = 1.75;
            else if (speedSec <= 1.1) dynRate = 1.35;

            window.speechSynthesis.cancel();
            var utter = new SpeechSynthesisUtterance(spokenText);
            utter.lang  = isHindi ? 'hi-IN' : 'en-IN';
            utter.rate  = dynRate;
            utter.pitch = 1.05;

            if (!cachedVoices.length) loadVoices();
            var matchVoice = null;
            for (var i = 0; i < cachedVoices.length; i++) {
                var v = cachedVoices[i];
                if (isHindi && (v.lang.indexOf('hi') === 0 || v.name.toLowerCase().indexOf('hindi') !== -1)) {
                    matchVoice = v;
                    break;
                }
                if (!isHindi && v.lang.indexOf('en') === 0) {
                    matchVoice = v;
                    break;
                }
            }
            if (matchVoice) utter.voice = matchVoice;
            window.speechSynthesis.speak(utter);
        } catch (e) {}
    }

    function sendAttemptToServer(logUrl, payload, onDone) {
        if (!logUrl) return;
        var fd = new FormData();
        Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) fd.append('csrf_test_name', csrfMeta.getAttribute('content'));

        fetch(logUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        })
        .then(function (r) { return r.json(); })
        .then(function (res) { if (typeof onDone === 'function') onDone(res); })
        .catch(function () {});
    }

    function initSorobanLab() {
        var rootEl = document.getElementById('js-aps-soroban-root');
        if (!rootEl || rootEl.dataset.mounted === '1') return;
        rootEl.dataset.mounted = '1';

        var rodsContainer = document.getElementById('js-soroban-rods-container');
        var currentValEl  = document.getElementById('js-soroban-current-val');
        var targetValEl   = document.getElementById('js-soroban-target-val');
        var rodsSel       = document.getElementById('js-soroban-rods-select');
        var themeSel      = document.getElementById('js-soroban-theme-select');
        var soundBtn      = document.getElementById('js-soroban-sound-btn');
        var newChallBtn   = document.getElementById('js-soroban-new-challenge');
        var clearBtn      = document.getElementById('js-soroban-clear-btn');
        var frameWrap     = document.getElementById('js-soroban-frame-wrap');
        var toastEl       = document.getElementById('js-soroban-challenge-toast');
        var logUrl        = rootEl.getAttribute('data-log-url') || '';

        var rodsCount    = parseInt(rootEl.getAttribute('data-rods') || '5', 10) || 5;
        var soundEnabled = rootEl.getAttribute('data-sound') !== '0';
        var rodValues    = [];
        var targetNumber = null;
        var challStartTs = 0;

        function buildSorobanRods(count) {
            rodsCount = count;
            rodValues = new Array(count).fill(0);
            var html  = '';
            for (var i = 0; i < count; i++) {
                var showDot = ((count - 1 - i) % 3 === 0);
                html += '<div class="aps-rod-col" data-rod="' + i + '">' +
                    '<div class="aps-rod-line"></div>' +
                    '<div class="aps-heaven-deck">' +
                    '  <div class="aps-bead aps-heaven-bead" data-rod="' + i + '" data-type="heaven"></div>' +
                    '</div>' +
                    '<div class="aps-reckoning-beam">' + (showDot ? '<span class="aps-unit-dot"></span>' : '') + '</div>' +
                    '<div class="aps-earth-deck">' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="1"></div>' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="2"></div>' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="3"></div>' +
                    '  <div class="aps-bead aps-earth-bead" data-rod="' + i + '" data-type="earth" data-idx="4"></div>' +
                    '</div>' +
                    '<div class="aps-rod-digit-box" id="js-rod-dig-' + i + '">0</div>' +
                    '</div>';
            }
            rodsContainer.innerHTML = html;
            updateTotalReadout();
        }

        function syncRodVisuals(rodIdx) {
            var val    = rodValues[rodIdx] || 0;
            var heaven = val >= 5 ? 1 : 0;
            var earth  = val % 5;
            var colEl  = rodsContainer.querySelector('.aps-rod-col[data-rod="' + rodIdx + '"]');
            if (!colEl) return;

            var hBead = colEl.querySelector('.aps-heaven-bead');
            if (hBead) hBead.classList.toggle('is-active', heaven === 1);

            colEl.querySelectorAll('.aps-earth-bead').forEach(function (eb) {
                var idx = parseInt(eb.getAttribute('data-idx'), 10);
                eb.classList.toggle('is-active', idx <= earth);
            });

            var digBox = document.getElementById('js-rod-dig-' + rodIdx);
            if (digBox) digBox.textContent = String(val);
        }

        function computeTotalAbacusValue() {
            var str = rodValues.map(function (v) { return String(v || 0); }).join('').replace(/^0+/, '');
            return str === '' ? '0' : str;
        }

        function updateTotalReadout() {
            var totalStr = computeTotalAbacusValue();
            if (currentValEl) currentValEl.textContent = Number(totalStr).toLocaleString('en-IN');

            if (targetNumber !== null && String(targetNumber) === totalStr) {
                var elapsedSec = Math.max(1, Math.round((Date.now() - challStartTs) / 1000));
                var solvedTarget = targetNumber;
                targetNumber = null;
                if (soundEnabled) playBeadClick(880, 140);

                sendAttemptToServer(logUrl, {
                    drill_mode: 'soroban_challenge',
                    digits_count: String(solvedTarget).length,
                    rows_count: 1,
                    speed_seconds: elapsedSec,
                    total_questions: 1,
                    correct_answers: 1,
                    time_taken_sec: elapsedSec
                }, function (res) {
                    if (toastEl) {
                        toastEl.textContent = '🎉 Target ' + solvedTarget + ' Solved in ' + elapsedSec + 's! ' + ((res && res.message) ? res.message : '');
                        toastEl.classList.remove('is-hidden');
                    }
                });
            }
        }

        rodsContainer.addEventListener('click', function (e) {
            var bead = e.target.closest('.aps-bead');
            if (!bead) return;
            var rodIdx   = parseInt(bead.getAttribute('data-rod'), 10);
            var type     = bead.getAttribute('data-type');
            var currVal  = rodValues[rodIdx] || 0;
            var hasHeav  = currVal >= 5;
            var earthVal = currVal % 5;

            if (type === 'heaven') {
                hasHeav = !hasHeav;
            } else {
                var clickedIdx = parseInt(bead.getAttribute('data-idx'), 10);
                earthVal = (earthVal >= clickedIdx) ? (clickedIdx - 1) : clickedIdx;
            }

            rodValues[rodIdx] = (hasHeav ? 5 : 0) + earthVal;
            if (soundEnabled) playBeadClick(type === 'heaven' ? 660 : 490, 35);
            syncRodVisuals(rodIdx);
            updateTotalReadout();
        });

        if (rodsSel) {
            rodsSel.addEventListener('change', function () {
                buildSorobanRods(parseInt(rodsSel.value, 10) || 5);
            });
        }
        if (themeSel && frameWrap) {
            themeSel.addEventListener('change', function () {
                frameWrap.className = 'aps-soroban-stage theme-' + themeSel.value;
            });
        }
        if (soundBtn) {
            soundBtn.addEventListener('click', function () {
                soundEnabled = !soundEnabled;
                soundBtn.textContent = soundEnabled ? '🔊 Sound: ON' : '🔇 Sound: OFF';
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                for (var i = 0; i < rodsCount; i++) {
                    rodValues[i] = 0;
                    syncRodVisuals(i);
                }
                if (soundEnabled) playBeadClick(360, 50);
                updateTotalReadout();
            });
        }
        if (newChallBtn) {
            newChallBtn.addEventListener('click', function () {
                for (var i = 0; i < rodsCount; i++) {
                    rodValues[i] = 0;
                    syncRodVisuals(i);
                }
                updateTotalReadout();
                var maxPow = Math.min(4, rodsCount);
                targetNumber = Math.floor(10 + Math.random() * (Math.pow(10, maxPow) - 15));
                challStartTs = Date.now();
                if (targetValEl) targetValEl.textContent = Number(targetNumber).toLocaleString('en-IN');
                if (toastEl) toastEl.classList.add('is-hidden');
            });
        }

        buildSorobanRods(rodsCount);
    }

    function initFlashAnzanArena() {
        var rootEl = document.getElementById('js-aps-anzan-root');
        if (!rootEl || rootEl.dataset.mounted === '1') return;
        rootEl.dataset.mounted = '1';

        var modeSel     = document.getElementById('js-anz-mode');
        var langSel     = document.getElementById('js-anz-lang');
        var digitsSel   = document.getElementById('js-anz-digits');
        var rowsSel     = document.getElementById('js-anz-rows');
        var speedSel    = document.getElementById('js-anz-speed');
        var opsSel      = document.getElementById('js-anz-ops');
        var startBtn    = document.getElementById('js-anz-start-btn');
        var replayBtn   = document.getElementById('js-anz-replay-btn');
        var progPill    = document.getElementById('js-anz-progress-pill');
        var timerPill   = document.getElementById('js-anz-timer-pill');
        var bigNumEl    = document.getElementById('js-anz-big-num');
        var subHintEl   = document.getElementById('js-anz-sub-hint');
        var ansForm     = document.getElementById('js-anz-answer-form');
        var userAnsInp  = document.getElementById('js-anz-user-ans');
        var resBanner   = document.getElementById('js-anz-result-banner');
        var logUrl      = rootEl.getAttribute('data-log-url') || '';

        var sequence    = [];
        var expectedSum = 0;
        var drillTimer  = null;
        var startTs     = 0;

        function generateSequence(digits, rows, allowSub) {
            var minVal  = Math.pow(10, digits - 1);
            var maxVal  = Math.pow(10, digits) - 1;
            var seq     = [];
            var running = 0;

            for (var i = 0; i < rows; i++) {
                var n = Math.floor(minVal + Math.random() * (maxVal - minVal + 1));
                if (allowSub && i > 0 && running > n && Math.random() < 0.42) {
                    n = -n;
                }
                running += n;
                seq.push(n);
            }
            expectedSum = running;
            return seq;
        }

        function startDrill() {
            clearTimeout(drillTimer);
            var mode     = modeSel ? modeSel.value : 'voice_dictation';
            var lang     = langSel ? langSel.value : 'hi-IN';
            var digits   = parseInt(digitsSel ? digitsSel.value : '1', 10) || 1;
            var rows     = parseInt(rowsSel ? rowsSel.value : '5', 10) || 5;
            var speedSec = parseFloat(speedSel ? speedSel.value : '1.0') || 1.0;
            var allowSub = !opsSel || opsSel.value === 'add_sub';
            var stepMs   = Math.max(250, Math.round(speedSec * 1000));

            sequence = generateSequence(digits, rows, allowSub);
            if (ansForm) ansForm.classList.add('is-hidden');
            if (resBanner) resBanner.classList.add('is-hidden');
            if (userAnsInp) userAnsInp.value = '';
            if (subHintEl) subHintEl.textContent = 'Calculate on your mental abacus...';

            var idx = 0;
            startTs = Date.now();

            function stepFlash() {
                if (idx >= sequence.length) {
                    bigNumEl.textContent = '?';
                    progPill.textContent = 'ENTER FINAL SUM';
                    if (mode === 'voice_dictation' && ('speechSynthesis' in window)) {
                        window.speechSynthesis.cancel();
                        var endU = new SpeechSynthesisUtterance(lang === 'hi-IN' ? 'उत्तर बताइए' : 'Answer');
                        endU.lang = lang;
                        endU.rate = 1.4;
                        window.speechSynthesis.speak(endU);
                    }
                    if (ansForm) {
                        ansForm.classList.remove('is-hidden');
                        if (userAnsInp) userAnsInp.focus();
                    }
                    return;
                }

                var val     = sequence[idx];
                var signStr = val >= 0 ? ('+' + val) : String(val);

                // Brief visual blanking (40ms) so consecutive identical numbers visibly flash!
                bigNumEl.style.opacity = '0.25';
                setTimeout(function () {
                    bigNumEl.textContent   = signStr;
                    bigNumEl.style.opacity = '1';
                    bigNumEl.style.color   = val >= 0 ? '#10b981' : '#f59e0b';
                }, 35);

                progPill.textContent  = 'ROW ' + (idx + 1) + ' / ' + sequence.length;
                timerPill.textContent = '⏱ ' + ((Date.now() - startTs) / 1000).toFixed(1) + 's';

                if (mode === 'voice_dictation') {
                    speakCrispNumber(val, lang, speedSec);
                } else {
                    playBeadClick(val >= 0 ? 600 : 420, 35);
                }

                idx++;
                drillTimer = setTimeout(stepFlash, stepMs);
            }

            stepFlash();
        }

        if (startBtn) startBtn.addEventListener('click', startDrill);
        if (replayBtn) replayBtn.addEventListener('click', startDrill);

        if (ansForm) {
            ansForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var userVal   = parseInt(userAnsInp.value || '0', 10);
                var isCorrect = (userVal === expectedSum);
                var elapsed   = Math.max(1, Math.round((Date.now() - startTs) / 1000));

                bigNumEl.style.color = isCorrect ? '#10b981' : '#f43f5e';
                bigNumEl.textContent = isCorrect ? ('✓ ' + expectedSum) : ('Ans: ' + expectedSum);
                if (isCorrect) playBeadClick(880, 150);

                sendAttemptToServer(logUrl, {
                    drill_mode: modeSel ? modeSel.value : 'voice_dictation',
                    digits_count: digitsSel ? digitsSel.value : 1,
                    rows_count: rowsSel ? rowsSel.value : 5,
                    speed_seconds: speedSel ? speedSel.value : 1.0,
                    total_questions: 1,
                    correct_answers: isCorrect ? 1 : 0,
                    time_taken_sec: elapsed
                }, function (res) {
                    if (!resBanner) return;
                    resBanner.className = 'alert-banner ' + (isCorrect ? 'alert-success' : 'alert-error');
                    resBanner.textContent = (isCorrect ? '🎉 Correct! ' : ('✕ Your answer: ' + userVal + ' (Correct Sum: ' + expectedSum + '). ')) +
                        ((res && res.message) ? res.message : '');
                });
            });
        }
    }

    function initAll() {
        initSorobanLab();
        initFlashAnzanArena();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
'''

    # Keep existing progress_view.php, analytics_view.php, and abacus_pro_suite.css intact
    for r in active_roots:
        extr_aps = os.path.join(r, "writable", "addons", "extracted", "abacus_pro_suite")
        for keep_rel in ["Views/addons/abacus_pro_suite/progress_view.php", "Views/addons/abacus_pro_suite/analytics_view.php", "assets/abacus_pro_suite.css"]:
            kp = os.path.join(extr_aps, keep_rel.replace("/", os.sep))
            if os.path.isfile(kp) and keep_rel not in aps_files:
                with open(kp, "r", encoding="utf-8") as kf:
                    aps_files[keep_rel] = kf.read()

    build_zip_and_mirror("abacus_pro_suite_addon.zip", "abacus_pro_suite", aps_files, active_roots)

    # =========================================================================
    # PART 3: `lms_timetable` v9.5
    # - Multi-Teacher 2-Pane Split Substitute Inbox (`substitutes_view.php`)
    # - Role Class Portal (`/timetable/portal`) Admin Role & Teacher/Student Switcher Fix
    # =========================================================================
    ltt_files = {}

    for r in active_roots:
        extr_ltt = os.path.join(r, "writable", "addons", "extracted", "lms_timetable")
        if os.path.isdir(extr_ltt):
            for root_d, _, f_names in os.walk(extr_ltt):
                for fn in f_names:
                    full_p = os.path.join(root_d, fn)
                    rel_p  = os.path.relpath(full_p, extr_ltt).replace("\\", "/")
                    with open(full_p, "r", encoding="utf-8") as lf:
                        ltt_files[rel_p] = lf.read()
            break

    # Upgrade `LmsTimetableController.php` so `/timetable/portal` supports Admin Role/User Preview Switcher (`sim_role`, `sim_teacher_id`, `sim_student_id`)!
    ltt_files["Controllers/Addons/LmsTimetable/LmsTimetableController.php"] = r'''<?php

namespace App\Controllers\Addons\LmsTimetable;

use App\Controllers\BaseController;
use App\Models\Addons\LmsTimetable\LmsTimetableModel;

class LmsTimetableController extends BaseController
{
    protected ?LmsTimetableModel $ttModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/lms_timetable';
    }

    private function getModel(): LmsTimetableModel
    {
        if ($this->ttModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/LmsTimetable/LmsTimetableModel.php';
            if (! class_exists(LmsTimetableModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->ttModel = new LmsTimetableModel();
        }
        return $this->ttModel;
    }

    private function hasPermission($permissionKeys): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('cms_has_permission')) {
            return cms_has_permission($permissionKeys);
        }
        return (int) session()->get('role_id') === 1 || (string) session()->get('role_slug') === 'super_admin';
    }

    private function renderAddonView(string $viewRelPath, array $data = []): string
    {
        if (! isset($data['settings']) || ! is_array($data['settings'])) {
            $data['settings'] = $this->getModel()->getGlobalSiteSettings();
        }
        if (empty($data['activeMenu'])) {
            $data['activeMenu'] = 'addon_lms_timetable';
        }
        if (empty($data['user'])) {
            $data['user'] = session()->get();
        }

        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonLmsTimetable', $this->addonPath);
        $nsView = 'AddonLmsTimetable\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonAdminData(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->mirrorAssets(false);
        $model->cleanupOrphanedAndDuplicateSessions();

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $roleUsers = $model->getRoleUsers();
        $userTz    = (string) (session()->get('user_timezone') ?: 'Asia/Kolkata');

        return [
            'pageTitle'          => $pageTitle,
            'activeMenu'         => 'addon_lms_timetable',
            'activeNav'          => 'lms_timetable',
            'activeSubTab'       => $activeSubTab,
            'settings'           => $model->getGlobalSiteSettings(),
            'isProdMode'         => $model->isProductionMode(),
            'effectiveRole'      => $effectiveRole,
            'minDurationMinutes' => $model->getMinDurationMinutes(),
            'leafNodes'          => $model->getLeafNodesWithPaths(),
            'cohorts'            => $model->getEnrichedCohorts(),
            'unassignedQueue'    => $model->getUnassignedSubscribedStudents(),
            'teachers'           => $roleUsers['teachers'],
            'students'           => $roleUsers['students'],
            'parents'            => $roleUsers['parents'],
            'sessions'           => $model->getEnrichedSessions($userTz),
            'subRequests'        => $model->getSubstituteRequestsWithThreads(),
            'userTz'             => $userTz,
            'canView'            => $this->hasPermission('lms_timetable.view'),
            'canCreate'          => $this->hasPermission('lms_timetable.create'),
            'canEdit'            => $this->hasPermission('lms_timetable.edit'),
            'canTeacherPanel'    => $this->hasPermission('lms_timetable.teacher_panel'),
            'canDelete'          => $this->hasPermission('lms_timetable.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        if ($this->hasPermission(['lms_timetable.create', 'lms_timetable.edit'])) return $this->cohortsManager();
        return $this->portalTimetable();
    }

    public function cohortsManager()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        if (! $this->hasPermission(['lms_timetable.create', 'lms_timetable.edit'])) return redirect()->to(base_url('timetable/portal'));
        return $this->renderAddonView('addons/lms_timetable/cohorts_view', $this->buildCommonAdminData('cohorts', 'Live Classroom Batches & Student Auto-Queue'));
    }

    public function sessionsManager()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        if (! $this->hasPermission(['lms_timetable.create', 'lms_timetable.edit'])) return redirect()->to(base_url('timetable/portal'));
        return $this->renderAddonView('addons/lms_timetable/sessions_view', $this->buildCommonAdminData('sessions', 'Master Class Schedule, Reschedule & Makeup Classes'));
    }

    public function substitutesInbox()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        if (! $this->hasPermission(['lms_timetable.edit', 'lms_timetable.create'])) return redirect()->to(base_url('timetable/portal'));
        $data = $this->buildCommonAdminData('substitutes', 'Multi-Teacher Leave & Substitute Allocation Desk');
        $selReqId = (int) ($this->request->getGet('req_id') ?? 0);
        $activeReq = null;
        foreach ($data['subRequests'] as $sr) {
            if ((int) $sr['id'] === $selReqId) {
                $activeReq = $sr;
                break;
            }
        }
        if (! $activeReq && ! empty($data['subRequests'])) {
            $activeReq = $data['subRequests'][0];
        }
        $data['activeSubReq'] = $activeReq;
        return $this->renderAddonView('addons/lms_timetable/substitutes_view', $data);
    }

    public function portalTimetable()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        if ((int) session()->get('lms_child_fee_locked') === 1) return redirect()->to(base_url('finance/portal?locked=1'));

        $data            = $this->buildCommonAdminData('portal', 'My Live Class Schedule');
        $model           = $this->getModel();
        $effectiveRole   = $data['effectiveRole'];
        $uid             = (int) (session()->get('user_id') ?: 1);
        $activeStuId     = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode  = (string) (session()->get('portal_view_mode') ?: 'default');
        $userTz          = $data['userTz'];
        $canManageAdmin  = $this->hasPermission(['lms_timetable.create', 'lms_timetable.edit']);

        // Default Admin / Super Admin visiting `/timetable/portal` to Teacher Console Preview (with ability to switch!)
        $simRole = strtolower(trim((string) ($this->request->getGet('sim_role') ?? '')));
        if ($canManageAdmin && in_array($effectiveRole, ['super_admin', 'admin'], true) && $simRole === '') {
            $simRole = 'teacher';
        }

        $isTeacherMode    = ($effectiveRole === 'teacher') || ($canManageAdmin && $simRole === 'teacher');
        $isParentOverview = ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0)) || ($canManageAdmin && $simRole === 'parent');
        $teacherTab       = trim((string) ($this->request->getGet('tab') ?? 'classes')) ?: 'classes';

        $parentChildrenMap = [];
        $enrolledStuIds    = [];
        $simTeacherId      = (int) ($this->request->getGet('sim_teacher_id') ?? 0);
        $simStudentId      = (int) ($this->request->getGet('sim_student_id') ?? 0);

        if ($isTeacherMode) {
            $targetTid = ($effectiveRole === 'teacher') ? $uid : $simTeacherId;
            $myCohorts  = $model->getEnrichedCohorts($targetTid, []);
            $mySessions = $model->getEnrichedSessions($userTz, $targetTid, []);
            if (empty($mySessions) && $canManageAdmin && $targetTid === 0) {
                $myCohorts  = $data['cohorts'];
                $mySessions = $data['sessions'];
            }
            $data['myCohorts']     = $myCohorts;
            $data['mySessions']    = $mySessions;
            $data['mySubRequests'] = $model->getSubstituteRequestsWithThreads($targetTid);
        } elseif ($isParentOverview) {
            $db = \Config\Database::connect();
            if ($db->tableExists('users')) {
                $q = $db->table('users')->select('id, full_name')->orderBy('id', 'ASC');
                if ($effectiveRole === 'parent') {
                    $q->where('parent_user_id', $uid);
                } else {
                    $q->where('parent_user_id >', 0);
                }
                foreach ($q->get()->getResultArray() as $k) {
                    $kidId = (int) $k['id'];
                    $enrolledStuIds[] = $kidId;
                    $parentChildrenMap[$kidId] = $k['full_name'] ?: 'Child';
                }
            }
            $data['myCohorts']     = ! empty($enrolledStuIds) ? $model->getEnrichedCohorts(0, $enrolledStuIds) : [];
            $data['mySessions']    = ! empty($enrolledStuIds) ? $model->getEnrichedSessions($userTz, 0, $enrolledStuIds) : [];
            $data['mySubRequests'] = [];
        } else {
            $targetStuId = $simStudentId > 0 ? $simStudentId : ($activeStuId > 0 ? $activeStuId : $uid);
            if ($canManageAdmin && ! isset($data['students'][$targetStuId]) && ! empty($data['students'])) {
                $targetStuId = (int) array_key_first($data['students']);
            }
            $enrolledStuIds        = [$targetStuId];
            $data['myCohorts']     = $model->getEnrichedCohorts(0, [$targetStuId]);
            $data['mySessions']    = $model->getEnrichedSessions($userTz, 0, [$targetStuId]);
            $data['mySubRequests'] = [];
        }

        $data['isTeacherMode']     = $isTeacherMode;
        $data['isParentOverview']  = $isParentOverview;
        $data['simRole']           = $simRole;
        $data['simTeacherId']      = $simTeacherId;
        $data['simStudentId']      = $simStudentId;
        $data['parentChildrenMap'] = $parentChildrenMap;
        $data['enrolledStuIdsCsv'] = implode(',', $enrolledStuIds);
        $data['teacherTab']        = $teacherTab;
        $data['canManageAdmin']    = $canManageAdmin;

        return $this->renderAddonView('addons/lms_timetable/portal_view', $data);
    }

    public function liveSubThreadsJson()
    {
        if (! session()->get('isLoggedIn')) {
            if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();
            return $this->response->setJSON(['status' => 'guest', 'requests' => []]);
        }
        $effectiveRole = function_exists('cms_get_effective_role') ? cms_get_effective_role() : 'student';
        $uid           = (int) (session()->get('user_id') ?: 1);
        $canEdit       = $this->hasPermission('lms_timetable.edit');
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();

        $filterTid    = ($effectiveRole === 'teacher' && ! $canEdit) ? $uid : 0;
        $reqs         = $this->getModel()->getSubstituteRequestsWithThreads($filterTid);
        $pendingCount = 0;
        $maxMsgId     = 0;
        foreach ($reqs as $rq) {
            if (($rq['status'] ?? '') === 'pending') $pendingCount++;
            if ((int) ($rq['latest_msg_id'] ?? 0) > $maxMsgId) $maxMsgId = (int) $rq['latest_msg_id'];
        }

        return $this->response->setJSON([
            'status'        => 'success',
            'pending_count' => $pendingCount,
            'max_msg_id'    => $maxMsgId,
            'requests'      => $reqs,
            'csrf_token'    => csrf_hash(),
        ]);
    }

    public function saveSettings()
    {
        if (! $this->hasPermission('lms_timetable.edit')) return redirect()->back()->with('error', 'Access denied.');
        $mins = (int) ($this->request->getPost('min_class_duration_minutes') ?? 30);
        $this->getModel()->saveMinDurationSetting($mins);
        return redirect()->back()->with('success', 'Minimum Class Duration Rule updated to ' . $mins . ' mins!');
    }

    public function saveCohortBatch()
    {
        if (! $this->hasPermission(['lms_timetable.create', 'lms_timetable.edit'])) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->saveOrUpdateCohortBatch($this->request->getPost(), $this->request->getFile('material_pdf_file'));
        return redirect()->to(base_url('addons/lms-timetable/cohorts'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function assignStudentToCohort()
    {
        if (! $this->hasPermission(['lms_timetable.create', 'lms_timetable.edit'])) return redirect()->back()->with('error', 'Access denied.');
        $cohortId   = (int) ($this->request->getPost('cohort_id') ?? 0);
        $studentIds = $this->request->getPost('student_ids') ?? [];
        if (! is_array($studentIds)) $studentIds = [$studentIds];
        $res = $this->getModel()->assignStudentsToCohortBatch($cohortId, $studentIds);
        return redirect()->to(base_url('addons/lms-timetable/cohorts'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function removeStudentFromCohort()
    {
        if (! $this->hasPermission(['lms_timetable.edit', 'lms_timetable.delete'])) return redirect()->back()->with('error', 'Access denied.');
        $cohortId  = (int) ($this->request->getPost('cohort_id') ?? 0);
        $studentId = (int) ($this->request->getPost('student_user_id') ?? 0);
        if ($cohortId > 0 && $studentId > 0) {
            \Config\Database::connect()->table('lms_classroom_batch_students')->where('cohort_id', $cohortId)->where('student_user_id', $studentId)->delete();
            $this->getModel()->writeStaticLiveSignalFile();
        }
        return redirect()->to(base_url('addons/lms-timetable/cohorts'))->with('success', 'Student removed from batch.');
    }

    public function deleteCohortBatch()
    {
        if (! $this->hasPermission('lms_timetable.delete')) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->deleteCohortWithCascade((int) ($this->request->getPost('id') ?? 0));
        return redirect()->to(base_url('addons/lms-timetable/cohorts'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function cleanupOrphanedSessions()
    {
        if (! $this->hasPermission(['lms_timetable.edit', 'lms_timetable.delete'])) return redirect()->back()->with('error', 'Access denied.');
        $stats = $this->getModel()->cleanupOrphanedAndDuplicateSessions();
        $this->getModel()->writeStaticLiveSignalFile();
        return redirect()->back()->with('success', 'Cleaned ' . (int) $stats['removed_orphans'] . ' orphaned & ' . (int) $stats['removed_duplicates'] . ' duplicate sessions!');
    }

    public function generateSchedule()
    {
        if (! $this->hasPermission('lms_timetable.create')) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->generateSessionsFromBuilder($this->request->getPost(), $this->request->getFile('material_pdf_file'));
        return redirect()->to(base_url('addons/lms-timetable/sessions'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function rescheduleSession()
    {
        if (! $this->hasPermission(['lms_timetable.edit', 'lms_timetable.create'])) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->rescheduleSingleSession($this->request->getPost());
        return redirect()->to(base_url('addons/lms-timetable/sessions'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function startClassWithWizard()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->request->isAJAX() ? $this->response->setStatusCode(401)->setJSON(['ok' => false]) : redirect()->to(base_url('login'));
        }
        $sessionId = (int) ($this->request->getPost('session_id') ?? 0);
        $teacherId = (int) (session()->get('user_id') ?: 1);
        $res       = $this->getModel()->executeStartClassWizard($sessionId, $teacherId, $this->request->getPost(), $this->request->getFile('material_pdf_file'));
        $res['csrf_token'] = csrf_hash();

        if ($this->request->isAJAX()) return $this->response->setJSON($res);
        return redirect()->to(base_url('timetable/portal?tab=classes'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function endClass()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->request->isAJAX() ? $this->response->setStatusCode(401)->setJSON(['ok' => false]) : redirect()->to(base_url('login'));
        }
        $sessionId = (int) ($this->request->getPost('session_id') ?? 0);
        $res       = $this->getModel()->logTeacherEndClass($sessionId);
        $res['csrf_token'] = csrf_hash();

        if ($this->request->isAJAX()) return $this->response->setJSON($res);
        return redirect()->to(base_url('timetable/portal?tab=classes'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function shareAssignment()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        $sessionId = (int) ($this->request->getPost('session_id') ?? 0);
        $note      = trim((string) ($this->request->getPost('assignment_note') ?? ''));
        if ($sessionId > 0) {
            $this->getModel()->update($sessionId, ['assignment_note' => $note, 'updated_at' => gmdate('Y-m-d H:i:s')]);
            $this->getModel()->writeStaticLiveSignalFile();
        }
        return redirect()->back()->with('success', 'Homework / class note shared with batch!');
    }

    public function markStudentAttendance()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        $sessionId = (int) ($this->request->getPost('session_id') ?? 0);
        $studentId = (int) ($this->request->getPost('student_user_id') ?? 0);
        $status    = in_array($this->request->getPost('attendance_status') ?? '', ['Present', 'Absent', 'Late'], true) ? $this->request->getPost('attendance_status') : 'Present';
        $teacherId = (int) (session()->get('user_id') ?: 1);

        if ($sessionId > 0 && $studentId > 0) {
            $db  = \Config\Database::connect();
            $ex  = $db->table('lms_session_student_attendance')->where('session_id', $sessionId)->where('student_user_id', $studentId)->get()->getRowArray();
            $now = gmdate('Y-m-d H:i:s');
            if ($ex) {
                $db->table('lms_session_student_attendance')->where('id', $ex['id'])->update(['attendance_status' => $status, 'marked_by_teacher_id' => $teacherId, 'updated_at' => $now]);
            } else {
                $db->table('lms_session_student_attendance')->insert(['session_id' => $sessionId, 'student_user_id' => $studentId, 'attendance_status' => $status, 'marked_by_teacher_id' => $teacherId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        return redirect()->back()->with('success', 'Attendance updated.');
    }

    public function submitSubstituteRequest()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        $teacherId = (int) (session()->get('user_id') ?: 1);
        $model     = $this->getModel();
        $res       = $model->createSubstituteRequest($teacherId, $this->request->getPost());
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $res['ok'], 'message' => $res['message'], 'requests' => $model->getSubstituteRequestsWithThreads($teacherId), 'csrf_token' => csrf_hash()]);
        }
        return redirect()->to(base_url('timetable/portal?tab=substitute'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function replySubstituteRequest()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        $reqId      = (int) ($this->request->getPost('request_id') ?? 0);
        $msg        = (string) ($this->request->getPost('message_text') ?? '');
        $senderId   = (int) (session()->get('user_id') ?: 1);
        $senderRole = (string) (session()->get('role_slug') ?: 'teacher');
        $canEdit    = $this->hasPermission('lms_timetable.edit');

        $model = $this->getModel();
        $res   = $model->addReplyToSubstituteRequest($reqId, $senderId, $senderRole, $msg);
        if ($this->request->isAJAX()) {
            $filterTid = ($senderRole === 'teacher' && ! $canEdit) ? $senderId : 0;
            return $this->response->setJSON(['ok' => $res['ok'], 'message' => $res['message'], 'requests' => $model->getSubstituteRequestsWithThreads($filterTid), 'csrf_token' => csrf_hash()]);
        }
        return redirect()->to(base_url('addons/lms-timetable/substitutes?req_id=' . $reqId))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function assignSubstituteTeacher()
    {
        if (! $this->hasPermission('lms_timetable.edit')) return redirect()->back()->with('error', 'Access denied.');
        $reqId        = (int) ($this->request->getPost('request_id') ?? 0);
        $subTeacherId = (int) ($this->request->getPost('substitute_teacher_id') ?? 0);
        $model        = $this->getModel();
        $res          = $model->assignSubstituteToSessions($reqId, $subTeacherId, []);
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $res['ok'], 'message' => $res['message'], 'requests' => $model->getSubstituteRequestsWithThreads(0), 'csrf_token' => csrf_hash()]);
        }
        return redirect()->to(base_url('addons/lms-timetable/substitutes?req_id=' . $reqId))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function addGuestStudent()
    {
        if (! $this->hasPermission(['lms_timetable.create', 'lms_timetable.edit'])) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->saveMakeupClassOrGuestSlots($this->request->getPost());
        return redirect()->to(base_url('addons/lms-timetable/sessions'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteSession()
    {
        if (! $this->hasPermission('lms_timetable.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_session_student_attendance')->where('session_id', $id)->delete();
            $this->getModel()->delete($id);
            $this->getModel()->writeStaticLiveSignalFile();
        }
        return redirect()->back()->with('success', 'Session deleted.');
    }

    public function asset($file = '')
    {
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();
        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) return $this->response->setStatusCode(404)->setBody('');
        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        return $this->response->setHeader('Content-Type', $mime)->setHeader('Cache-Control', 'no-cache, must-revalidate')->setBody((string) file_get_contents($fullPath));
    }
}
'''

    # Redesign `substitutes_view.php` into a Multi-Teacher 2-Pane Split Helpdesk Inbox!
    ltt_files["Views/addons/lms_timetable/substitutes_view.php"] = r'''<?= $this->extend('layouts/master_layout') ?>

<?= $this->section('content') ?>
<?php
    $activeReq   = $activeSubReq ?? null;
    $sessionsMap = [];
    foreach (($sessions ?? []) as $s) {
        $sessionsMap[(int) $s['id']] = $s;
    }
?>
<div class="ltt-wrapper" id="js-ltt-sub-poll-root"
     data-poll-url="<?= base_url('addons/lms-timetable/live-sub-threads-json') ?>"
     data-reply-url="<?= base_url('addons/lms-timetable/reply-sub-request') ?>"
     data-assign-url="<?= base_url('addons/lms-timetable/assign-substitute') ?>"
     data-is-admin="1">
    <?php include __DIR__ . '/partials/admin_nav.php'; ?>

    <!-- Status Filter Strip for Multi-Teacher Requests -->
    <div class="ltt-compact-bar" style="margin-bottom: 1rem;">
        <div class="action-btn-group" id="js-sub-status-filter">
            <button type="button" class="btn-action is-active js-sub-filter-btn" data-status="all">
                📋 All Teacher Requests (<?= count($subRequests ?? []) ?>)
            </button>
            <button type="button" class="btn-action js-sub-filter-btn" data-status="pending">
                🔴 Pending Allocation
            </button>
            <button type="button" class="btn-action js-sub-filter-btn" data-status="approved_substituted">
                ✓ Substitute Assigned
            </button>
        </div>
        <input type="search" id="js-sub-teacher-search" class="field-input" placeholder="Search teacher or reason..." style="width:230px; padding:0.35rem 0.65rem;">
    </div>

    <?php if (empty($subRequests)): ?>
        <section class="console-card" style="text-align:center; padding:3rem 1.5rem;">
            <h3>No Teacher Leave or Substitute Requests Yet</h3>
            <p class="muted-cell">When any instructor requests a substitute teacher for their batch, their ticket and live chat thread will appear here.</p>
        </section>
    <?php else: ?>
        <!-- 2-Pane Multi-Teacher Split Desk -->
        <div style="display:grid; grid-template-columns: 350px minmax(0, 1fr); gap:1.25rem; align-items:start;">
            <!-- Left Sidebar: All Teacher Request Tickets -->
            <aside class="console-card" style="padding:0.75rem !important; max-height:72vh; overflow-y:auto;">
                <div style="display:flex; flex-direction:column; gap:0.5rem;" id="js-sub-tickets-list">
                    <?php foreach ($subRequests as $req): ?>
                        <?php
                            $rid     = (int) $req['id'];
                            $isSel   = ($activeReq && (int) $activeReq['id'] === $rid);
                            $isPend  = (($req['status'] ?? '') === 'pending');
                            $msgCnt  = count($req['thread'] ?? []);
                            $lastMsg = $msgCnt > 0 ? $req['thread'][$msgCnt - 1]['message_text'] : $req['reason_note'];
                            $srch    = strtolower($req['teacher_name'] . ' ' . $req['substitute_name'] . ' ' . $req['reason_note']);
                        ?>
                        <a href="<?= base_url('addons/lms-timetable/substitutes?req_id=' . $rid) ?>"
                           class="panel-card js-sub-ticket-item"
                           data-status="<?= esc($req['status']) ?>"
                           data-search="<?= esc($srch) ?>"
                           style="text-decoration:none; color:inherit; padding:0.85rem !important; border: <?= $isSel ? '2px solid #10b981' : '1px solid var(--border-subtle)' ?> !important; background: <?= $isSel ? 'rgba(16,185,129,0.1)' : 'var(--bg-canvas)' ?>;">
                            <div class="ltt-flex-between" style="margin-bottom:0.35rem;">
                                <strong>👩‍🏫 <?= esc($req['teacher_name']) ?></strong>
                                <span class="role-badge <?= $isPend ? 'text-amber' : 'text-emerald' ?>">
                                    <?= $isPend ? '⏳ PENDING' : '✓ ASSIGNED' ?>
                                </span>
                            </div>
                            <div class="mono-cell text-cyan" style="font-size:0.76rem; margin-bottom:0.3rem;">
                                <?= count($req['session_ids'] ?? []) ?> Class Session(s) • Sub: <strong><?= esc($req['substitute_name']) ?></strong>
                            </div>
                            <div class="muted-cell" style="font-size:0.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                💬 <?= esc($lastMsg) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <!-- Right Stage: Selected Teacher's Request Details, Affected Classes, 1-Click Substitute Assigner & Live Chat -->
            <?php if ($activeReq): ?>
                <section class="console-card" data-req-id="<?= (int) $activeReq['id'] ?>">
                    <div class="card-head ltt-flex-between">
                        <div>
                            <span class="role-badge <?= ($activeReq['status'] ?? '') === 'pending' ? 'text-amber' : 'text-emerald' ?>">
                                <?= ($activeReq['status'] ?? '') === 'pending' ? '⏳ AWAITING SUBSTITUTE TEACHER' : '✓ SUBSTITUTE TEACHER ASSIGNED' ?>
                            </span>
                            <h3 style="margin:0.3rem 0 0;">Request by 👩‍🏫 <?= esc($activeReq['teacher_name']) ?></h3>
                        </div>
                        <div style="text-align:right;">
                            <span class="mono-label">ASSIGNED SUBSTITUTE</span>
                            <div class="identity-name text-cyan"><?= esc($activeReq['substitute_name']) ?></div>
                        </div>
                    </div>

                    <!-- Affected Class Sessions List -->
                    <div class="panel-card" style="margin-bottom:1rem; background:var(--bg-canvas);">
                        <span class="mono-label text-emerald">AFFECTED BATCH SESSIONS REQUESTED FOR COVER:</span>
                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.45rem;">
                            <?php foreach (($activeReq['session_ids'] ?? []) as $sid): ?>
                                <?php $sInfo = $sessionsMap[(int) $sid] ?? null; ?>
                                <span class="role-badge">
                                    <?php if ($sInfo): ?>
                                        🏫 <?= esc($sInfo['cohort_title']) ?> — ⏰ <?= esc($sInfo['local_start_display']) ?>
                                    <?php else: ?>
                                        📅 Class Session #<?= (int) $sid ?>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 1-Click Assign Substitute Teacher Bar (Excludes the requesting teacher automatically!) -->
                    <?php if (! empty($canEdit)): ?>
                        <form action="<?= base_url('addons/lms-timetable/assign-substitute') ?>" method="post" class="panel-card js-ajax-sub-assign-form" style="margin-bottom:1rem; border-color:rgba(16,185,129,0.4); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= (int) $activeReq['id'] ?>">
                            <div>
                                <strong class="text-emerald">1-Click Assign Substitute Instructor</strong>
                                <div class="field-hint">Replaces <?= esc($activeReq['teacher_name']) ?> on the above session(s) and notifies them in chat.</div>
                            </div>
                            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                                <select name="substitute_teacher_id" class="field-select" style="min-width:220px;" required>
                                    <option value="">-- Choose Substitute Teacher --</option>
                                    <?php foreach (($teachers ?? []) as $tid => $tRow): ?>
                                        <?php if ((int) $tid === (int) $activeReq['teacher_user_id']) continue; ?>
                                        <option value="<?= (int) $tid ?>" <?= (int) $activeReq['substitute_teacher_id'] === (int) $tid ? 'selected' : '' ?>>
                                            👩‍🏫 <?= esc($tRow['display_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-primary">✓ Assign Substitute</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Live Conversation Thread with Selected Teacher -->
                    <span class="mono-label">LIVE CONVERSATION WITH <?= strtoupper(esc($activeReq['teacher_name'])) ?>:</span>
                    <div class="ltt-thread-box" id="js-sub-thread-msgs-<?= (int) $activeReq['id'] ?>" style="max-height:290px;">
                        <?php foreach (($activeReq['thread'] ?? []) as $msg): ?>
                            <div class="ltt-thread-msg <?= ($msg['sender_role'] ?? '') === 'admin' ? 'is-admin-msg' : 'is-teacher-msg' ?>">
                                <strong><?= esc($msg['sender_name']) ?> (<?= strtoupper(esc($msg['sender_role'])) ?>):</strong>
                                <span><?= esc($msg['message_text']) ?></span>
                                <small class="muted-cell"><?= esc(substr((string) $msg['created_at'], 0, 16)) ?> UTC</small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form action="<?= base_url('addons/lms-timetable/reply-sub-request') ?>" method="post" class="ltt-mini-row js-ajax-sub-reply-form" style="margin-top:0.75rem;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int) $activeReq['id'] ?>">
                        <input type="text" name="message_text" class="field-input" placeholder="Reply to <?= esc($activeReq['teacher_name']) ?>..." autocomplete="off" required style="flex:1;">
                        <button type="submit" class="btn-secondary">💬 Send Live Reply</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterBar = document.getElementById('js-sub-status-filter');
    var searchInp = document.getElementById('js-sub-teacher-search');
    var currSt    = 'all';

    function filterTickets() {
        var q = searchInp ? searchInp.value.toLowerCase().trim() : '';
        document.querySelectorAll('.js-sub-ticket-item').forEach(function (el) {
            var st  = el.getAttribute('data-status') || '';
            var txt = el.getAttribute('data-search') || '';
            var mSt = (currSt === 'all' || st === currSt);
            var mQ  = (q === '' || txt.indexOf(q) !== -1);
            el.style.display = (mSt && mQ) ? '' : 'none';
        });
    }
    if (filterBar) {
        filterBar.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-sub-filter-btn');
            if (!btn) return;
            filterBar.querySelectorAll('.js-sub-filter-btn').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            currSt = btn.getAttribute('data-status') || 'all';
            filterTickets();
        });
    }
    if (searchInp) searchInp.addEventListener('input', filterTickets);
});
</script>
<?= $this->endSection() ?>
'''

    # Inject Admin Role & User Preview Switcher Bar at the top of `portal_view.php` so Admin can test Teacher / Student / Parent views seamlessly!
    if "Views/addons/lms_timetable/portal_view.php" in ltt_files:
        old_portal = ltt_files["Views/addons/lms_timetable/portal_view.php"]
        admin_switcher_snippet = r'''<?php if (! empty($canManageAdmin)): ?>
        <?php include __DIR__ . '/partials/admin_nav.php'; ?>
        <div class="ltt-compact-bar" style="border-color: rgba(6, 182, 212, 0.45);">
            <div>
                <span class="mono-label text-cyan">ADMIN ROLE PORTAL PREVIEWER</span>
                <div class="muted-cell" style="font-size:0.8rem;">Switch between Teacher Console, Student Schedule, and Parent Family View:</div>
            </div>
            <div class="action-btn-group">
                <a href="<?= base_url('timetable/portal?sim_role=teacher') ?>" class="btn-action <?= ! empty($isTeacherMode) ? 'is-active text-emerald' : '' ?>" style="text-decoration:none;">👩‍🏫 Teacher Console</a>
                <a href="<?= base_url('timetable/portal?sim_role=student') ?>" class="btn-action <?= empty($isTeacherMode) && empty($isParentOverview) ? 'is-active text-emerald' : '' ?>" style="text-decoration:none;">🎓 Student View</a>
                <a href="<?= base_url('timetable/portal?sim_role=parent') ?>" class="btn-action <?= ! empty($isParentOverview) ? 'is-active text-emerald' : '' ?>" style="text-decoration:none;">👨‍👩‍👧‍👦 Parent View</a>
            </div>
        </div>
    <?php endif; ?>'''
        ltt_files["Views/addons/lms_timetable/portal_view.php"] = old_portal.replace(
            "<?php if (! empty($canManageAdmin)): ?>\n        <?php include __DIR__ . '/partials/admin_nav.php'; ?>\n    <?php endif; ?>",
            admin_switcher_snippet
        )

    build_zip_and_mirror("lms_timetable_addon.zip", "lms_timetable", ltt_files, active_roots)
    print("\n[ALL DONE] Core /users, abacus_pro_suite (v8.5), and lms_timetable (v9.5) are updated!")

if __name__ == "__main__":
    main()