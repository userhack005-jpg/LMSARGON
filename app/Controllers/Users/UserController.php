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
