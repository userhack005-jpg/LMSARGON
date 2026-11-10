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
        $loggedUser = $model->getLoggedInUserRecord();
        $isLoggedIn = ($loggedUser !== null);
        $uid        = $isLoggedIn ? (int) $loggedUser['id'] : 0;

        $effectiveRole = $isLoggedIn
            ? (function_exists('cms_get_effective_role') ? cms_get_effective_role() : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student'))))
            : 'guest';
        $isAdmin       = $this->hasPermission(['lms_onboarding.view', 'demos.manage']);
        $teacherFilter = ($effectiveRole === 'teacher' && ! $isAdmin) ? $uid : 0;

        $isBookTab      = ($activeSubTab === 'book');
        $isPipelineTab  = ($activeSubTab === 'pipeline');
        $isSchedulerTab = ($activeSubTab === 'scheduler');
        $isTeacherTab   = ($activeSubTab === 'my_demos');

        $existingKids   = ($isBookTab && $isLoggedIn && $uid > 0) ? $model->getParentLinkedChildren($uid) : [];
        $myAppliedDemos = ($isBookTab && $isLoggedIn && $uid > 0) ? $model->getAppliedDemosForUser($uid, (string) ($loggedUser['email'] ?? ''), array_keys($existingKids)) : [];

        $leads              = $isPipelineTab ? $model->getEnrichedLeads($teacherFilter) : [];
        $demoBatches        = ($isPipelineTab || $isSchedulerTab) ? $model->getEnrichedDemoBatches($teacherFilter, false) : [];
        $teacherDemoBatches = $isTeacherTab ? $model->getEnrichedDemoBatches($teacherFilter, true) : [];
        $quickCounts        = (! $isBookTab) ? $model->getQuickCounts($teacherFilter) : ['leads_count' => 0, 'batches_count' => 0];

        return [
            'pageTitle'          => $pageTitle,
            'activeMenu'         => 'addon_lms_onboarding',
            'activeNav'          => 'lms_onboarding',
            'activeSubTab'       => $activeSubTab,
            'settings'           => $model->getGlobalSiteSettings(),
            'user'               => $loggedUser ?: (session()->get() ?: []),
            'loggedUser'         => $loggedUser,
            'isLoggedIn'         => $isLoggedIn,
            'effectiveRole'      => $effectiveRole,
            'uid'                => $uid,
            'existingKids'       => $existingKids,
            'myAppliedDemos'     => $myAppliedDemos,
            'coursesMap'         => $model->getEnrollableCoursesMap(true),
            'allCoursesMap'      => ($isSchedulerTab || $isPipelineTab) ? $model->getEnrollableCoursesMap(false) : [],
            'cohortsMap'         => $isPipelineTab ? $model->getActiveCohortsMap() : [],
            'teachersMap'        => $isSchedulerTab ? $model->getTeachersMap() : [],
            'leads'              => $leads,
            'demoBatches'        => $demoBatches,
            'teacherDemoBatches' => $teacherDemoBatches,
            'totalLeadsCount'    => $isPipelineTab ? count($leads) : $quickCounts['leads_count'],
            'totalBatchesCount'  => ($isPipelineTab || $isSchedulerTab) ? count($demoBatches) : $quickCounts['batches_count'],
            'canViewLeads'       => $this->hasPermission('lms_onboarding.view'),
            'canManageDemos'     => $this->hasPermission(['demos.manage', 'lms_onboarding.view']),
            'canTeacherDemo'     => $this->hasPermission(['lms_onboarding.teacher_demos', 'demos.manage', 'lms_onboarding.view']),
            'canDelete'          => $this->hasPermission('lms_onboarding.delete'),
        ];
    }

    public function index()
    {
        $courseIdParam = (int) ($this->request->getGet('course_id') ?? 0);
        if (! session()->get('isLoggedIn') || ($courseIdParam > 0 && ! $this->hasPermission('lms_onboarding.view'))) {
            return $this->bookDemoPage($courseIdParam);
        }
        $effectiveRole = function_exists('cms_get_effective_role') ? cms_get_effective_role() : 'student';
        if ($effectiveRole === 'teacher' && ! $this->hasPermission('lms_onboarding.view')) {
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
        $data = $this->buildCommonContext('pipeline', 'Demo Bookings, Slot Assignment & Cohort Convert');
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
        if (! $this->hasPermission(['lms_onboarding.teacher_demos', 'demos.manage', 'lms_onboarding.view'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }
        $data = $this->buildCommonContext('my_demos', 'My Assigned Demo Classes Console');
        return $this->renderAddonView('addons/lms_onboarding/teacher_demos_view', $data);
    }

    public function bookDemoPage($courseId = 0)
    {
        $cid  = (int) ($courseId ?: ($this->request->getGet('course_id') ?? 0));
        $data = $this->buildCommonContext('book', 'Book a Free Demo Class');
        $data['selectedCourseId'] = $cid;
        return $this->renderAddonView('addons/lms_onboarding/book_demo_view', $data);
    }

    public function checkEmailExistsJson()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $email = strtolower(trim((string) ($this->request->getGet('email') ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['exists' => false]);
        }
        $db  = \Config\Database::connect();
        $row = $db->tableExists('users') ? $db->table('users')->select('id')->where('email', $email)->get()->getRowArray() : null;
        return $this->response->setJSON([
            'exists'    => ! empty($row),
            'login_url' => base_url('login'),
            'message'   => ! empty($row) ? 'This email account is already registered. Please log in to book a demo or add a child.' : '',
        ]);
    }

    public function submitDemoBooking()
    {
        $res = $this->getModel()->createNewDemoLead($this->request->getPost());
        $res['csrf_token'] = csrf_hash();
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($res);
        }
        if (! empty($res['require_login'])) { return redirect()->to(base_url('login'))->with('error', $res['message']); }
        return redirect()->to($res['ok'] ? base_url('portal/family') : previous_url())->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function assignLeadToDemoBatch()
    {
        if (! $this->hasPermission(['demos.manage', 'lms_onboarding.view'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->assignLeadToDemoSlot($this->request->getPost());
        return redirect()->to(base_url('addons/lms-onboarding'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function teacherStartDemoBatch()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_onboarding.teacher_demos', 'demos.manage', 'lms_onboarding.view'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }
        $uid     = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $isAdmin = $this->hasPermission(['demos.manage', 'lms_onboarding.view']);
        $res     = $this->getModel()->startTeacherDemoBatch($uid, $this->request->getPost(), $isAdmin);
        return redirect()->to(base_url('addons/lms-onboarding/my-demos'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function teacherMarkLiveAttendance()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_onboarding.teacher_demos', 'demos.manage', 'lms_onboarding.view'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }
        $uid     = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $isAdmin = $this->hasPermission(['demos.manage', 'lms_onboarding.view']);
        $res     = $this->getModel()->updateMidClassDemoAttendance($uid, $this->request->getPost(), $isAdmin);
        return redirect()->to(base_url('addons/lms-onboarding/my-demos'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function teacherEndDemoBatch()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_onboarding.teacher_demos', 'demos.manage', 'lms_onboarding.view'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }
        $uid     = (int) (session()->get('user_id') ?: (session()->get('id') ?: 1));
        $isAdmin = $this->hasPermission(['demos.manage', 'lms_onboarding.view']);
        $res     = $this->getModel()->finalizeAndEndDemoBatch($uid, $this->request->getPost(), $isAdmin);
        return redirect()->to(base_url('addons/lms-onboarding/my-demos'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
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
        if (! $this->hasPermission('lms_onboarding.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $this->getModel()->delete($id);
        }
        return redirect()->back()->with('success', 'Demo lead deleted.');
    }

    public function saveDemoBatch()
    {
        if (! $this->hasPermission(['demos.manage', 'lms_onboarding.view'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->saveOrUpdateDemoBatch($this->request->getPost());
        return redirect()->to(base_url('addons/lms-onboarding/scheduler'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteDemoBatch()
    {
        if (! $this->hasPermission('lms_onboarding.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $db = \Config\Database::connect();
            $db->table('lms_demo_batches')->where('id', $id)->delete();
            $db->table('lms_demo_leads')->where('demo_batch_id', $id)->update(['demo_batch_id' => 0, 'status' => 'new_lead']);
        }
        return redirect()->to(base_url('addons/lms-onboarding/scheduler'))->with('success', 'Demo slot deleted.');
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
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($fullPath));
    }
}
