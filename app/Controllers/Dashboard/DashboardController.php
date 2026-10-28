<?php

namespace App\Controllers\Dashboard;

use App\Controllers\BaseController;
use App\Models\Settings\SettingModel;
use App\Models\Users\UserModel;
use Config\Database;
use Throwable;

class DashboardController extends BaseController
{
    protected SettingModel $settingModel;
    protected UserModel $userModel;
    protected string $lockFile;

    public function __construct()
    {
        helper(['url']);
        $this->settingModel = new SettingModel();
        $this->userModel    = new UserModel();
        $this->lockFile     = WRITEPATH . 'installed.lock';
    }

    public function index()
    {
        if (! is_file($this->lockFile)) {
            return redirect()->to(base_url('install'));
        }

        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        // 1. Handle "Back to Parent Profile" (Exit Child Sandbox Mode)
        if ($this->request->getGet('exit_child_mode') === '1') {
            $baseRole = strtolower(trim((string) (session()->get('role_slug') ?: 'parent')));
            session()->set([
                'active_student_id'   => 0,
                'portal_view_mode'    => 'parent',
                'effective_role_slug' => $baseRole,
                '_rbac_sync_key'      => '',
            ]);
            return redirect()->to(base_url('dashboard'))
                ->with('success', 'Returned to Parent Main Profile.');
        }

        // 2. Ensure `roles`, `cms_roles`, and `users` (`role_id` + `role_slug`) are 100% synced
        $this->userModel->ensureRolesAndUsersIntegrity();

        // 3. Resolve Effective Role (Enforces 'student' sandbox when Parent switches into a Child profile)
        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $roleWorkspace = function_exists('cms_get_role_workspace')
            ? cms_get_role_workspace($effectiveRole)
            : $this->userModel->getRolePermissionsData($effectiveRole);

        $perms           = $roleWorkspace['permissions'] ?? [];
        $wsConfig        = $roleWorkspace['workspace'] ?? [];
        $compiledSidebar = $roleWorkspace['compiled_sidebar'] ?? [];

        session()->set([
            'effective_role_slug' => $effectiveRole,
            'permissions'         => $perms,
            'role_permissions'    => $perms,
            'workspace_config'    => $wsConfig,
        ]);

        // 4. If role has a custom default landing route configured (and didn't explicitly click Dashboard), redirect cleanly
        $landingRoute = trim((string) ($wsConfig['landing_route'] ?? 'dashboard'), '/');
        if ($landingRoute !== '' && $landingRoute !== 'dashboard' && $this->request->getGet('stay') !== '1') {
            return redirect()->to(base_url($landingRoute));
        }

        $settings = $this->settingModel->getSystemSettings();
        $roles    = $this->userModel->getAvailableRoles();
        $stats    = $this->userModel->getRoleDistributionStats();

        // 5. Gather contextual LMS summary safely if tables exist
        $uid         = (int) (session()->get('user_id') ?: 1);
        $activeStuId = (int) (session()->get('active_student_id') ?: $uid);
        $lmsSummary  = [
            'upcoming_classes' => [],
            'recent_attempts'  => [],
            'subscriptions'    => [],
            'flagged_chats'    => 0,
        ];

        try {
            $db = Database::connect();
            if ($db->tableExists('lms_class_sessions')) {
                $lmsSummary['upcoming_classes'] = $db->table('lms_class_sessions')
                    ->orderBy('scheduled_start_utc', 'ASC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();
            }
            if ($db->tableExists('lms_abacus_exam_attempts')) {
                $lmsSummary['recent_attempts'] = $db->table('lms_abacus_exam_attempts')
                    ->where('student_user_id', $activeStuId)
                    ->orderBy('id', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();
            }
            if ($db->tableExists('lms_student_subscriptions')) {
                $lmsSummary['subscriptions'] = $db->table('lms_student_subscriptions')
                    ->groupStart()
                        ->where('student_user_id', $activeStuId)
                        ->orWhere('parent_user_id', $uid)
                    ->groupEnd()
                    ->get()
                    ->getResultArray();
            }
            if ($db->tableExists('lms_chat_messages')) {
                $lmsSummary['flagged_chats'] = (int) $db->table('lms_chat_messages')
                    ->where('is_flagged', 1)
                    ->where('is_hidden', 1)
                    ->countAllResults();
            }
        } catch (Throwable $e) {
        }

        return view('dashboard/index_view', [
            'activeMenu'      => 'dashboard',
            'settings'        => $settings,
            'roles'           => $roles,
            'stats'           => $stats,
            'lmsSummary'      => $lmsSummary,
            'effectiveRole'   => $effectiveRole,
            'roleWorkspace'   => $roleWorkspace,
            'compiledSidebar' => $compiledSidebar,
            'user'            => session()->get(),
            'pageTitle'       => ($settings['site_title'] ?? 'System') . ' — Workspace Overview',
        ]);
    }
}