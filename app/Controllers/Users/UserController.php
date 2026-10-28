<?php

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
        return redirect()->to(base_url('users'))->with($flashKey, $payload['message'] ?? 'Operation completed.');
    }

    /**
     * Full Cascade Cleanup across all LMS Add-On Tables when a Student or Parent is deleted!
     */
    private function cascadeDeleteStudentRecords(int $studentId): void
    {
        if ($studentId <= 1) {
            return;
        }
        $db = \Config\Database::connect();
        $studentTables = [
            'lms_hierarchy_enrollments'      => 'student_user_id',
            'lms_student_subscriptions'      => 'student_user_id',
            'lms_classroom_batch_students'   => 'student_user_id',
            'lms_session_student_attendance' => 'student_user_id',
            'lms_assessment_attempts'        => 'student_user_id',
            'lms_certificates'               => 'student_user_id',
            'lms_chat_messages'              => 'student_user_id',
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
        ]);
    }

    public function createUser()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.create')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }

        $res = $this->userModel->createNewUser($this->request->getPost());
        return $this->respondOrRedirect($res);
    }

    public function updateUserRole()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }

        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $roleId = (int) ($this->request->getPost('role_id') ?? 0);
        $res    = $this->userModel->assignRole($userId, $roleId);

        // Sync `role_slug` in `users` table if present
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('roles') && $db->tableExists('users')) {
                $rRow = $db->table('roles')->where('id', $roleId)->get()->getRowArray();
                if ($rRow && ! empty($rRow['role_slug'])) {
                    $rSlug = strtolower(trim((string) $rRow['role_slug']));
                    $upd   = ['role_slug' => $rSlug];
                    if (in_array($rSlug, ['super_admin', 'admin', 'teacher', 'parent'], true)) {
                        $upd['parent_user_id'] = null;
                    }
                    $db->table('users')->where('id', $userId)->update($upd);
                }
            }
        } catch (Throwable $e) {
        }

        return $this->respondOrRedirect($res);
    }

    public function toggleUserStatus()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }

        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $res    = $this->userModel->toggleStatus($userId);
        return $this->respondOrRedirect($res);
    }

    /**
     * Deletes a User Account AND performs full Cascade Deletion:
     * - If the deleted user is a Parent (or has linked children where `parent_user_id = $userId`),
     *   deletes all linked Child Student accounts AND all their enrollments, subscriptions, attempts & attendance!
     * - If the deleted user is a Student, deletes all their enrollments, subscriptions, attempts & attendance!
     */
    public function deleteUser()
    {
        if (function_exists('cms_has_permission') && ! cms_has_permission('users.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Access denied.']);
        }

        $userId = (int) ($this->request->getPost('user_id') ?? ($this->request->getPost('id') ?? 0));
        if ($userId <= 1) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Root Super Admin account cannot be deleted.']);
        }

        $db = \Config\Database::connect();
        $deletedChildrenCount = 0;

        try {
            // 1. Find any linked children (`parent_user_id = $userId`) and cascade-delete their data + user accounts
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

            // 2. Clean any parent-referenced rows in enrollments & subscriptions
            foreach (['lms_hierarchy_enrollments', 'lms_student_subscriptions', 'payment_transactions'] as $pTbl) {
                if ($db->tableExists($pTbl)) {
                    $db->table($pTbl)->where('parent_user_id', $userId)->delete();
                }
            }

            // 3. Clean student records if the deleted user was a Student themselves
            $this->cascadeDeleteStudentRecords($userId);
        } catch (Throwable $e) {
        }

        $res = $this->userModel->removeUser($userId);
        if (($res['status'] ?? '') === 'success' && $deletedChildrenCount > 0) {
            $res['message'] = 'Parent account and ' . $deletedChildrenCount . ' linked student profile(s) (with all batch enrollments & records) deleted.';
        }

        return $this->respondOrRedirect($res);
    }
}