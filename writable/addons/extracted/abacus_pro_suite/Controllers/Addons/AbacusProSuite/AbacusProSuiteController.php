<?php

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
            $data['user'] = session()->get();
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
        $model->mirrorAssets(false);

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $uid            = (int) (session()->get('user_id') ?: 1);
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');

        $isParentOverview = ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0));
        $parentKidsMap    = [];
        $targetStudentIds = [];

        if ($isParentOverview) {
            $db = \Config\Database::connect();
            if ($db->tableExists('users')) {
                foreach ($db->table('users')->select('id, full_name')->where('parent_user_id', $uid)->orderBy('id', 'ASC')->get()->getResultArray() as $k) {
                    $kidId = (int) $k['id'];
                    $parentKidsMap[$kidId] = $k['full_name'] ?: 'Child';
                    $targetStudentIds[]    = $kidId;
                }
            }
        } elseif ($effectiveRole === 'student') {
            $targetStudentIds = [$activeStuId > 0 ? $activeStuId : $uid];
        }

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_abacus_pro_suite',
            'activeNav'        => 'abacus_pro_suite',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'abacusConfig'     => $model->getAbacusConfig(),
            'user'             => session()->get(),
            'effectiveRole'    => $effectiveRole,
            'uid'              => $uid,
            'activeStuId'      => $activeStuId > 0 ? $activeStuId : $uid,
            'isParentOverview' => $isParentOverview,
            'parentKidsMap'    => $parentKidsMap,
            'targetStudentIds' => $targetStudentIds,
            'canPractice'      => $this->hasPermission('abacus_pro_suite.practice'),
            'canViewAnalytics' => $this->hasPermission('abacus_pro_suite.view'),
            'canEditSettings'  => $this->hasPermission('abacus_pro_suite.edit'),
            'canDelete'        => $this->hasPermission('abacus_pro_suite.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ($this->hasPermission('abacus_pro_suite.view')) {
            return $this->batchAnalyticsView();
        }
        return $this->arenaView();
    }

    /**
     * Sub-View 1: Interactive 3D Soroban Abacus Lab (`/abacus/arena`)
     */
    public function arenaView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ((int) session()->get('lms_child_fee_locked') === 1) {
            return redirect()->to(base_url('finance/portal?locked=1'));
        }
        $data = $this->buildCommonContext('arena', '3D Soroban Abacus Lab');
        return $this->renderAddonView('addons/abacus_pro_suite/arena_view', $data);
    }

    /**
     * Sub-View 2: Flash Anzan & Voice Dictation Speed Arena (`/abacus/anzan`)
     */
    public function anzanView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ((int) session()->get('lms_child_fee_locked') === 1) {
            return redirect()->to(base_url('finance/portal?locked=1'));
        }
        $data = $this->buildCommonContext('anzan', 'Flash Anzan & Voice Dictation Speed Arena');
        return $this->renderAddonView('addons/abacus_pro_suite/anzan_view', $data);
    }

    /**
     * Sub-View 3: Student & Parent Speed, Accuracy & Gamified Badges (`/abacus/my-progress`)
     */
    public function myProgressView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $data     = $this->buildCommonContext('progress', 'My Abacus Speed & Achievement Badges');
        $progress = $this->getModel()->getStudentProgressAndBadges($data['targetStudentIds']);

        $data['progressData'] = $progress;
        return $this->renderAddonView('addons/abacus_pro_suite/progress_view', $data);
    }

    /**
     * Sub-View 4: Teacher & Admin Batch Practice Analytics (`/addons/abacus-pro-suite/analytics`)
     */
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

    /**
     * Sub-View 5: Admin Soroban Theme & Drill Settings (`/addons/abacus-pro-suite/settings`)
     */
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

    public function logAttemptAjax()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'message' => 'Unauthorized']);
        }

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $uid         = (int) (session()->get('user_id') ?: 1);
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
        return redirect()->to(base_url('addons/abacus-pro-suite/settings'))
            ->with('success', 'Soroban Theme & Default Drill Settings saved!');
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
            return $this->response->setStatusCode(404)->setBody('/* Asset not found */');
        }
        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody((string) file_get_contents($fullPath));
    }
}
