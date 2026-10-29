<?php

namespace App\Controllers\Addons\CoreLmsAuth;

use App\Controllers\BaseController;
use App\Models\Addons\CoreLmsAuth\CoreLmsAuthModel;

class CoreLmsAuthController extends BaseController
{
    protected ?CoreLmsAuthModel $authModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/core_lms_auth';
    }

    private function getModel(): CoreLmsAuthModel
    {
        if ($this->authModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/CoreLmsAuth/CoreLmsAuthModel.php';
            if (! class_exists(CoreLmsAuthModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->authModel = new CoreLmsAuthModel();
        }
        return $this->authModel;
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
            $data['activeMenu'] = 'addon_core_lms_auth';
        }
        if (empty($data['user'])) {
            $data['user'] = session()->get();
        }

        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonCoreLmsAuth', $this->addonPath);
        $nsView = 'AddonCoreLmsAuth\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonData(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->mirrorAssets(false);
        $uid = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_core_lms_auth',
            'activeNav'        => 'core_lms_auth',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'isProdMode'       => $model->isProductionMode(),
            'companions'       => $model->getCompanionAddonsStatus(),
            'availableRoles'   => $model->getAllAvailableRoles(),
            'roleMap'          => $model->getBehaviorRoleMap(),
            'countries'        => $model->getCountriesCatalog(),
            'enabledCountries' => $model->getEnabledCountriesCatalog(),
            'leafBatchesMap'   => $model->getDynamicLeafBatchesMap(),
            'familyDir'        => $model->getFamilyDirectory(),
            'portalCtx'        => $model->getUnifiedPortalContext($uid),
            'canView'          => $this->hasPermission('core_lms_auth.view'),
            'canCreate'        => $this->hasPermission('core_lms_auth.create'),
            'canEdit'          => $this->hasPermission('core_lms_auth.edit'),
            'canDelete'        => $this->hasPermission('core_lms_auth.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['core_lms_auth.edit', 'core_lms_auth.create'])) {
            return redirect()->to(base_url('portal/family'));
        }
        return $this->familyDirectoryView();
    }

    /**
     * Admin View 1: Linked Family Directory Table ONLY (`directory_view.php`)
     */
    public function familyDirectoryView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['core_lms_auth.edit', 'core_lms_auth.create'])) {
            return redirect()->to(base_url('portal/family'));
        }
        $data = $this->buildCommonData('directory', 'Family & Student Directory');
        return $this->renderAddonView('addons/core_lms_auth/directory_view', $data);
    }

    /**
     * Admin View 2: Parent ↔ Child & Dynamic Course Linker Form ONLY (`linker_view.php`)
     */
    public function childLinkerView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['core_lms_auth.create', 'core_lms_auth.edit'])) {
            return redirect()->to(base_url('portal/family'));
        }
        $data     = $this->buildCommonData('linker', 'Assign Child to Parent & Course');
        $editStu  = (int) ($this->request->getGet('student_id') ?? 0);
        $data['editStudent'] = ($editStu > 0 && isset($data['familyDir']['students'][$editStu]))
            ? $data['familyDir']['students'][$editStu]
            : null;
        return $this->renderAddonView('addons/core_lms_auth/linker_view', $data);
    }

    /**
     * Admin View 3: 5-Behavior Role Mapper ONLY (`role_mapper_view.php`)
     */
    public function roleMapperView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('core_lms_auth.edit')) {
            return redirect()->to(base_url('portal/family'));
        }
        $data = $this->buildCommonData('role_mapper', '5-Behavior Role Mapper');
        return $this->renderAddonView('addons/core_lms_auth/role_mapper_view', $data);
    }

    /**
     * Admin View 4: Servicing Countries & Timezone Engine ONLY (`countries_view.php`)
     */
    public function countriesView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('core_lms_auth.edit')) {
            return redirect()->to(base_url('portal/family'));
        }
        $data = $this->buildCommonData('countries', 'Servicing Countries & Currencies');
        return $this->renderAddonView('addons/core_lms_auth/countries_view', $data);
    }

    /**
     * Portal View 1: My Children & Learning Hub (`/portal/family` -> `portal_children_view.php`)
     */
    public function portalDashboard()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        if ($effectiveRole === 'teacher') {
            return redirect()->to(base_url('dashboard'));
        }

        $data  = $this->buildCommonData('family', 'My Family & Learning Hub');
        $model = $this->getModel();
        $uid   = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));

        $simUserId = 0;
        if (! $data['isProdMode'] && $this->hasPermission('core_lms_auth.edit') && $this->request->getGet('sim_user_id')) {
            $simUserId = (int) $this->request->getGet('sim_user_id');
        }

        $data['portalCtx']      = $model->getUnifiedPortalContext($uid, $simUserId);
        $data['simUserId']      = $simUserId;
        $data['canManageAdmin'] = $this->hasPermission(['core_lms_auth.edit', 'core_lms_auth.create']);
        $data['canSimulate']    = (! $data['isProdMode'] && $this->hasPermission('core_lms_auth.edit'));

        return $this->renderAddonView('addons/core_lms_auth/portal_children_view', $data);
    }

    /**
     * Portal View 2: Self-Service Add Child Profile (`/portal/family/add-child` -> `portal_add_child_view.php`)
     */
    public function portalAddChildView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $data = $this->buildCommonData('add_child', 'Add Child Profile');
        $data['canManageAdmin'] = $this->hasPermission(['core_lms_auth.edit', 'core_lms_auth.create']);
        return $this->renderAddonView('addons/core_lms_auth/portal_add_child_view', $data);
    }

    public function portalCreateChild()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $uid = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $res = $this->getModel()->createNewChildUnderParent($uid, $this->request->getPost());

        return redirect()->to(base_url($res['ok'] ? 'portal/family' : 'portal/family/add-child'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    /**
     * Portal View 3: My Profile & Password Settings (`/portal/family/profile` -> `portal_profile_view.php`)
     */
    public function portalProfileView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $data = $this->buildCommonData('profile', 'My Profile & Account Settings');
        $data['canManageAdmin'] = $this->hasPermission(['core_lms_auth.edit', 'core_lms_auth.create']);
        return $this->renderAddonView('addons/core_lms_auth/portal_profile_view', $data);
    }

    public function portalUpdateProfile()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $uid = (int) (session()->get('active_student_id') ?: (session()->get('user_id') ?: 1));
        $res = $this->getModel()->updateUserProfileAndSettings($uid, $this->request->getPost());

        return redirect()->to(base_url('portal/family/profile'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function saveRoleMap()
    {
        if (! $this->hasPermission('core_lms_auth.edit')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $this->getModel()->saveBehaviorRoleMap($this->request->getPost());
        return redirect()->to(base_url('addons/core-lms-auth/role-mapper'))->with('success', 'Role Mapping saved successfully!');
    }

    public function syncFkRoles()
    {
        if (! $this->hasPermission('core_lms_auth.edit')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $stats = $this->getModel()->syncRolesAndForeignKeys();
        $this->getModel()->rebuildStaticCache();
        return redirect()->back()->with('success', 'Roles & User Accounts synchronized (' . (int) $stats['users'] . ' aligned).');
    }

    public function saveCountries()
    {
        if (! $this->hasPermission('core_lms_auth.edit')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $this->getModel()->saveCountriesSettings($this->request->getPost());
        return redirect()->to(base_url('addons/core-lms-auth/countries'))->with('success', 'Servicing Countries updated!');
    }

    public function saveChildLink()
    {
        if (! $this->hasPermission(['core_lms_auth.create', 'core_lms_auth.edit'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->saveChildLinkAndMeta($this->request->getPost());
        return redirect()->to(base_url('addons/core-lms-auth/directory'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteChildLink()
    {
        if (! $this->hasPermission('core_lms_auth.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $this->getModel()->unlinkChildFromParent((int) ($this->request->getPost('student_user_id') ?? 0));
        return redirect()->to(base_url('addons/core-lms-auth/directory'))->with('success', 'Child unlinked from Parent.');
    }

    public function switchActiveStudent()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $studentId = (int) ($this->request->getPost('student_id') ?? ($this->request->getGet('student_id') ?? 0));
        $baseRole  = strtolower(trim((string) (session()->get('role_slug') ?: 'parent')));
        $family    = $this->getModel()->getFamilyDirectory();

        if ($studentId > 0) {
            $stuInfo   = $family['students'][$studentId] ?? null;
            $childName = $stuInfo['full_name'] ?? 'Child';
            session()->set([
                'active_student_id'   => $studentId,
                'portal_view_mode'    => 'student',
                'effective_role_slug' => ($baseRole === 'super_admin') ? 'super_admin' : 'student',
                '_rbac_sync_key'      => '',
            ]);
            if (isset($stuInfo['meta']['timezone'])) {
                session()->set('user_timezone', $stuInfo['meta']['timezone']);
                session()->set('user_currency', $stuInfo['meta']['currency_code'] ?? 'INR');
                session()->set('user_country', $stuInfo['meta']['country_code'] ?? 'IN');
            }
            $msg = 'Switched to ' . $childName . '\'s Learning Hub!';
        } else {
            session()->set([
                'active_student_id'   => 0,
                'portal_view_mode'    => 'parent',
                'effective_role_slug' => $baseRole,
                '_rbac_sync_key'      => '',
            ]);
            $msg = 'Returned to Parent Account!';
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'            => 'success',
                'ok'                => true,
                'active_student_id' => $studentId,
                'portal_view_mode'  => $studentId > 0 ? 'student' : 'parent',
                'redirect_url'      => base_url('portal/family'),
                'csrf_token'        => csrf_hash(),
            ]);
        }
        return redirect()->to(base_url('portal/family'))->with('success', $msg);
    }

    public function familyContextJson()
    {
        if (! session()->get('isLoggedIn')) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            return $this->response->setJSON(['status' => 'guest', 'allow_switcher' => false, 'siblings' => []]);
        }

        $baseRoleSlug = strtolower(trim((string) (session()->get('role_slug') ?: 'student')));
        if ((int) session()->get('role_id') === 1) {
            $baseRoleSlug = 'super_admin';
        }
        $uid = (int) (session()->get('user_id') ?: (session()->get('id') ?: 0));

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $allowSwitcher = ($baseRoleSlug === 'parent');
        if (function_exists('cms_get_role_workspace')) {
            $wsData        = cms_get_role_workspace($baseRoleSlug);
            $allowSwitcher = ((int) ($wsData['workspace']['show_child_switcher'] ?? ($baseRoleSlug === 'parent' ? 1 : 0)) === 1);
        }

        if (! $allowSwitcher) {
            return $this->response->setJSON([
                'status'         => 'disabled',
                'allow_switcher' => false,
                'siblings'       => [],
            ]);
        }

        $ctx = $this->getModel()->getUnifiedPortalContext($uid);
        if (empty($ctx['siblings'])) {
            return $this->response->setJSON([
                'status'         => 'no_children',
                'allow_switcher' => false,
                'siblings'       => [],
            ]);
        }

        return $this->response->setJSON([
            'status'            => 'success',
            'allow_switcher'    => true,
            'active_student_id' => (int) $ctx['active_student_id'],
            'portal_view_mode'  => (string) $ctx['portal_view_mode'],
            'active_student'    => $ctx['active_student'],
            'active_timezone'   => $ctx['active_timezone'],
            'active_currency'   => $ctx['active_currency'],
            'siblings'          => array_values($ctx['siblings']),
        ]);
    }

    public function asset($file = '')
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('/* Not found */');
        }
        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody((string) file_get_contents($fullPath));
    }
}
