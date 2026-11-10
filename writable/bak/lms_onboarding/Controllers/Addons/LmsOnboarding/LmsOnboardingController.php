<?php

namespace App\Controllers\Addons\LmsOnboarding;

use App\Controllers\BaseController;
use App\Models\Addons\LmsOnboarding\LmsOnboardingModel;

class LmsOnboardingController extends BaseController
{
    protected ?LmsOnboardingModel $onbModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/lms_onboarding';
    }

    private function getModel(): LmsOnboardingModel
    {
        if ($this->onbModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/LmsOnboarding/LmsOnboardingModel.php';
            if (! class_exists(LmsOnboardingModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->onbModel = new LmsOnboardingModel();
        }
        return $this->onbModel;
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
            $data['activeMenu'] = 'addon_lms_onboarding';
        }
        if (empty($data['user'])) {
            $data['user'] = session()->get() ?: [];
        }

        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonLmsOnboarding', $this->addonPath);
        $nsView = 'AddonLmsOnboarding\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonContext(string $activeSubTab, string $pageTitle): array
    {
        $model      = $this->getModel();
        $model->mirrorAssets(false);
        $loggedUser = $model->getLoggedInUserRecord();
        $isLoggedIn = ($loggedUser !== null);
        $uid        = $isLoggedIn ? (int) $loggedUser['id'] : 0;

        $effectiveRole = $isLoggedIn
            ? (function_exists('cms_get_effective_role') ? cms_get_effective_role() : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student'))))
            : 'guest';
        $teacherFilter = ($effectiveRole === 'teacher') ? $uid : 0;
        $existingKids  = ($isLoggedIn && $uid > 0) ? $model->getParentLinkedChildren($uid) : [];

        return [
            'pageTitle'      => $pageTitle,
            'activeMenu'     => 'addon_lms_onboarding',
            'activeNav'      => 'lms_onboarding',
            'activeSubTab'   => $activeSubTab,
            'settings'       => $model->getGlobalSiteSettings(),
            'user'           => $loggedUser ?: (session()->get() ?: []),
            'loggedUser'     => $loggedUser,
            'isLoggedIn'     => $isLoggedIn,
            'effectiveRole'  => $effectiveRole,
            'uid'            => $uid,
            'existingKids'   => $existingKids,
            'myAppliedDemos' => ($isLoggedIn && $uid > 0) ? $model->getAppliedDemosForUser($uid, (string) ($loggedUser['email'] ?? ''), array_keys($existingKids)) : [],
            'coursesMap'     => $model->getEnrollableCoursesMap(),
            'cohortsMap'     => $model->getActiveCohortsMap(),
            'teachersMap'    => $model->getTeachersMap(),
            'leads'          => $model->getEnrichedLeads($teacherFilter),
            'demoBatches'    => $model->getEnrichedDemoBatches($teacherFilter),
            'canViewLeads'   => $this->hasPermission('lms_onboarding.view'),
            'canManageDemos' => $this->hasPermission(['demos.manage', 'lms_onboarding.view']),
            'canTeacherDemo' => $this->hasPermission(['lms_onboarding.teacher_demos', 'demos.manage', 'lms_onboarding.view']),
            'canDelete'      => $this->hasPermission('lms_onboarding.delete'),
        ];
    }

    public function index()
    {
        $courseIdParam = (int) ($this->request->getGet('course_id') ?? 0);
        if (! session()->get('isLoggedIn') || ($courseIdParam > 0 && ! $this->hasPermission('lms_onboarding.view'))) {
            return $this->bookDemoPage($courseIdParam);
        }
        $effectiveRole = function_exists('cms_get_effective_role') ? cms_get_effective_role() : 'student';
        if ($effectiveRole === 'teacher') {
            return $this->teacherDemosView();
        }
        if ($this->hasPermission(['lms_onboarding.view', 'demos.manage'])) {
            return $this->leadsPipelineView();
        }
        return $this->bookDemoPage($courseIdParam);
    }

    public function leadsPipelineView()
    {
        if (! $this->hasPermission(['lms_onboarding.view', 'demos.manage'])) {
            return redirect()->to(base_url('demo/book'));
        }
        $data = $this->buildCommonContext('pipeline', 'Demo Bookings & Convert to Live Cohort');
        return $this->renderAddonView('addons/lms_onboarding/leads_view', $data);
    }

    public function demoSchedulerView()
    {
        if (! $this->hasPermission(['demos.manage', 'lms_onboarding.view'])) {
            return redirect()->to(base_url('demo/book'));
        }
        $data = $this->buildCommonContext('scheduler', 'Demo Class Slots & Teacher Assignment');
        return $this->renderAddonView('addons/lms_onboarding/scheduler_view', $data);
    }

    public function teacherDemosView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $data = $this->buildCommonContext('my_demos', 'My Assigned Demo Classes');
        return $this->renderAddonView('addons/lms_onboarding/teacher_demos_view', $data);
    }

    public function bookDemoPage($courseId = 0)
    {
        $cid  = (int) ($courseId ?: ($this->request->getGet('course_id') ?? 0));
        $data = $this->buildCommonContext('book', 'Book a Free Demo Class');
        $data['selectedCourseId'] = $cid;
        return $this->renderAddonView('addons/lms_onboarding/book_demo_view', $data);
    }

    public function submitDemoBooking()
    {
        $res = $this->getModel()->createNewDemoLead($this->request->getPost());
        $res['csrf_token'] = csrf_hash();
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($res);
        }
        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function assignLeadToDemoBatch()
    {
        if (! $this->hasPermission(['demos.manage', 'lms_onboarding.view'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->assignLeadToDemoSlot($this->request->getPost());
        return redirect()->back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function convertLeadToStudent()
    {
        if (! $this->hasPermission(['lms_onboarding.view', 'demos.manage'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $leadId   = (int) ($this->request->getPost('lead_id') ?? 0);
        $courseId = (int) ($this->request->getPost('course_node_id') ?? 0);
        $cohortId = (int) ($this->request->getPost('target_cohort_id') ?? 0);

        $res = $this->getModel()->convertDemoLeadToEnrolledStudent($leadId, $courseId, $cohortId);
        return redirect()->to(base_url('addons/lms-onboarding'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteLead()
    {
        if (! $this->hasPermission('lms_onboarding.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) $this->getModel()->delete($id);
        return redirect()->back()->with('success', 'Demo lead deleted.');
    }

    public function saveDemoBatch()
    {
        if (! $this->hasPermission(['demos.manage', 'lms_onboarding.view'])) return redirect()->back()->with('error', 'Access denied.');
        $res = $this->getModel()->saveOrUpdateDemoBatch($this->request->getPost());
        return redirect()->to(base_url('addons/lms-onboarding/scheduler'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteDemoBatch()
    {
        if (! $this->hasPermission('lms_onboarding.delete')) return redirect()->back()->with('error', 'Access denied.');
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) \Config\Database::connect()->table('lms_demo_batches')->where('id', $id)->delete();
        return redirect()->to(base_url('addons/lms-onboarding/scheduler'))->with('success', 'Demo slot deleted.');
    }

    public function teacherSubmitEvaluation()
    {
        if (! session()->get('isLoggedIn')) return redirect()->to(base_url('login'));
        $uid = (int) (session()->get('user_id') ?: 1);
        $res = $this->getModel()->saveTeacherDemoEvaluation($uid, $this->request->getPost());
        return redirect()->to(base_url('addons/lms-onboarding/my-demos'))->with($res['ok'] ? 'success' : 'error', $res['message']);
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
