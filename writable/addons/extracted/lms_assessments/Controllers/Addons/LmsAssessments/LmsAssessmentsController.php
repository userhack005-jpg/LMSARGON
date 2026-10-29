<?php

namespace App\Controllers\Addons\LmsAssessments;

use App\Controllers\BaseController;
use App\Models\Addons\LmsAssessments\LmsAssessmentsModel;

class LmsAssessmentsController extends BaseController
{
    protected ?LmsAssessmentsModel $assModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->addonPath = WRITEPATH . 'addons/extracted/lms_assessments';
    }

    private function getModel(): LmsAssessmentsModel
    {
        if ($this->assModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/LmsAssessments/LmsAssessmentsModel.php';
            if (! class_exists(LmsAssessmentsModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->assModel = new LmsAssessmentsModel();
        }
        return $this->assModel;
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
        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }

        \Config\Services::autoloader()->addNamespace('AddonLmsAssessments', $this->addonPath);
        $nsView = 'AddonLmsAssessments\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonAdminData(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->syncPageBuilderComponent();
        $model->mirrorAssets(false);

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $uid             = (int) (session()->get('user_id') ?: 1);
        $teacherIdFilter = ($effectiveRole === 'teacher') ? $uid : 0;

        return [
            'pageTitle'      => $pageTitle,
            'activeMenu'     => 'addon_lms_assessments',
            'activeNav'      => 'lms_assessments',
            'activeSubTab'   => $activeSubTab,
            'settings'       => $model->getGlobalSiteSettings(),
            'isProdMode'     => $model->isProductionMode(),
            'user'           => session()->get(),
            'effectiveRole'  => $effectiveRole,
            'leafNodesMap'   => $model->getLeafNodesMap($teacherIdFilter),
            'sessionsMap'    => $model->getClassSessionsMap($teacherIdFilter),
            'questionBank'   => $model->getQuestionBankList(-1, $teacherIdFilter),
            'qbankCountsMap' => $model->getQuestionBankCountsByNode(),
            'exams'          => $model->getAllExamsWithQuestions($teacherIdFilter),
            'worksheets'     => $model->getAllWorksheets($teacherIdFilter),
            'attempts'       => $model->getAttemptsWithDetails([], $teacherIdFilter),
            'certificates'   => $model->getCertificatesList([], $teacherIdFilter),
            'canView'        => $this->hasPermission('lms_assessments.view'),
            'canCreate'      => $this->hasPermission('lms_assessments.create'),
            'canEdit'        => $this->hasPermission('lms_assessments.edit'),
            'canDelete'      => $this->hasPermission('lms_assessments.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ($this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return $this->questionBankManager();
        }
        if ($this->hasPermission('lms_assessments.view')) {
            return $this->attemptsAndAnalytics();
        }
        return $this->studentPortalExams();
    }

    public function questionBankManager()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return redirect()->to(base_url('assessments/portal'));
        }

        $data            = $this->buildCommonAdminData('qbank', 'Course Question Bank');
        $filterNodeId    = $this->request->getGet('filter_node_id');
        $selectedNodeId  = ($filterNodeId !== null && $filterNodeId !== '') ? (int) $filterNodeId : -1;
        $teacherIdFilter = ($data['effectiveRole'] === 'teacher') ? (int) (session()->get('user_id') ?: 1) : 0;

        if ($selectedNodeId >= 0) {
            $data['questionBank'] = $this->getModel()->getQuestionBankList($selectedNodeId, $teacherIdFilter);
        }
        $data['selectedFilterNodeId'] = $selectedNodeId;
        $data['defaultAddNodeId']     = ($selectedNodeId >= 0) ? $selectedNodeId : (int) ($this->request->getGet('node_id') ?? 2);

        return $this->renderAddonView('addons/lms_assessments/question_bank_view', $data);
    }

    public function examsBuilder()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return redirect()->to(base_url('assessments/portal'));
        }

        $data = $this->buildCommonAdminData('exams', 'Exam & Practice Drill Builder');
        return $this->renderAddonView('addons/lms_assessments/exams_view', $data);
    }

    public function worksheetsManager()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return redirect()->to(base_url('assessments/portal'));
        }

        $data = $this->buildCommonAdminData('worksheets', 'Upload Course PDF Worksheets');
        return $this->renderAddonView('addons/lms_assessments/worksheets_view', $data);
    }

    public function attemptsAndAnalytics()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('lms_assessments.view')) {
            return redirect()->to(base_url('assessments/portal'));
        }

        $data = $this->buildCommonAdminData('analytics', 'Student Scores & Issued Certificates');
        return $this->renderAddonView('addons/lms_assessments/analytics_view', $data);
    }

    /**
     * Shared Student/Parent Context Resolver for the 3 Dedicated Portal Sub-Views
     */
    private function buildStudentPortalContext(string $portalTab, string $pageTitle): array
    {
        $data           = $this->buildCommonAdminData($portalTab, $pageTitle);
        $model          = $this->getModel();
        $effectiveRole  = $data['effectiveRole'];
        $uid            = (int) (session()->get('user_id') ?: 1);
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');
        $isProdMode     = $data['isProdMode'];

        $isParentOverview = ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId === 0));
        $linkedChildIds   = [];

        if ($isParentOverview) {
            $db = \Config\Database::connect();
            if ($db->tableExists('users')) {
                $kids = $db->table('users')->select('id, full_name')->where('parent_user_id', $uid)->get()->getResultArray();
                foreach ($kids as $k) {
                    $linkedChildIds[] = (int) $k['id'];
                }
            }
            if (empty($linkedChildIds)) {
                $linkedChildIds[] = -1;
            }
        } else {
            if (! $isProdMode && $this->hasPermission('lms_assessments.edit') && $this->request->getGet('sim_student_id')) {
                $activeStuId = (int) $this->request->getGet('sim_student_id');
            } elseif ($activeStuId <= 0) {
                $activeStuId = $uid;
            }
            $linkedChildIds = [$activeStuId];
        }

        $data['isParentOverview']  = $isParentOverview;
        $data['activeStuId']       = $activeStuId;
        $data['linkedChildIds']    = $linkedChildIds;
        $data['studentExams']      = $isParentOverview ? [] : $model->getExamsForStudent($activeStuId);
        $data['studentWorksheets'] = $model->getWorksheetsForStudents($linkedChildIds);
        $data['myAttempts']        = $model->getAttemptsWithDetails($linkedChildIds, 0);
        $data['myCerts']           = $model->getCertificatesList($linkedChildIds, 0);
        $data['canManageAdmin']    = $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit']);

        return $data;
    }

    /**
     * Student/Parent Sub-View 1: Practice & Timed Exams (`/assessments/portal`)
     * - NEVER starts an exam automatically on page load!
     * - Only starts/loads the runner if an active attempt already exists OR if `$startNow === true` (`?take_exam_id=X&start=1`)!
     */
    public function studentPortalExams()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ((int) session()->get('lms_child_fee_locked') === 1) {
            return redirect()->to(base_url('finance/portal?locked=1'));
        }

        $data  = $this->buildStudentPortalContext('portal_exams', 'Practice Drills & Timed Exams');
        $model = $this->getModel();

        // If Parent is in Main Overview Mode, route them to the Family Scores & Certificates view
        if ($data['isParentOverview']) {
            return $this->studentPortalHistory();
        }

        $reqExamId = (int) ($this->request->getGet('take_exam_id') ?? 0);
        $startNow  = ((int) ($this->request->getGet('start') ?? 0) === 1);

        $selectedExam  = null;
        $activeAttempt = null;

        if ($reqExamId > 0) {
            foreach ($data['studentExams'] as $ex) {
                if ((int) $ex['id'] === $reqExamId && ! empty($ex['is_unlocked'])) {
                    $selectedExam = $ex;
                    break;
                }
            }
            if ($selectedExam) {
                // Only creates a new attempt if `$startNow === true`; otherwise checks if an `in_progress` attempt exists!
                $activeAttempt = $model->getOrStartActiveAttempt((int) $data['activeStuId'], $selectedExam, $startNow);
            }
        }

        $data['selectedExam']  = $selectedExam;
        $data['activeAttempt'] = $activeAttempt;

        return $this->renderAddonView('addons/lms_assessments/portal_exams_view', $data);
    }

    /**
     * Explicit POST handler when student clicks "▶ Start Exam / Practice Now"
     */
    public function startExamAttempt()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $examId = (int) ($this->request->getPost('exam_id') ?? ($this->request->getGet('exam_id') ?? 0));
        if ($examId <= 0) {
            return redirect()->to(base_url('assessments/portal'));
        }
        return redirect()->to(base_url('assessments/portal?take_exam_id=' . $examId . '&start=1'));
    }

    /**
     * Student/Parent Sub-View 2: Downloadable PDF Worksheets (`/assessments/portal/worksheets`)
     */
    public function studentPortalWorksheets()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ((int) session()->get('lms_child_fee_locked') === 1) {
            return redirect()->to(base_url('finance/portal?locked=1'));
        }

        $data = $this->buildStudentPortalContext('portal_ws', 'My Course PDF Worksheets');
        return $this->renderAddonView('addons/lms_assessments/portal_worksheets_view', $data);
    }

    /**
     * Student/Parent Sub-View 3: My Scores, Speed History & Certificates (`/assessments/portal/history`)
     */
    public function studentPortalHistory()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        $data = $this->buildStudentPortalContext('portal_hist', 'My Exam Scores & Certificates');
        return $this->renderAddonView('addons/lms_assessments/portal_history_view', $data);
    }

    public function saveQuestion()
    {
        if (! $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $uid = (int) (session()->get('user_id') ?: 1);
        $res = $this->getModel()->saveOrUpdateQuestionBankItem($this->request->getPost(), $uid);

        $redirectNode = (int) ($res['node_id'] ?? 0);
        return redirect()->to(base_url('addons/lms-assessments/question-bank?node_id=' . $redirectNode))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function toggleQuestionStatus()
    {
        if (! $this->hasPermission(['lms_assessments.edit', 'lms_assessments.create'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id  = (int) ($this->request->getPost('id') ?? 0);
        $db  = \Config\Database::connect();
        $row = $db->table('lms_question_bank')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $newState = ((int) ($row['is_active'] ?? 1) === 1) ? 0 : 1;
            $db->table('lms_question_bank')->where('id', $id)->update(['is_active' => $newState, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }
        return redirect()->back()->with('success', 'Question status updated.');
    }

    public function deleteQuestion()
    {
        if (! $this->hasPermission('lms_assessments.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_question_bank')->where('id', $id)->delete();
        }
        return redirect()->back()->with('success', 'Question deleted.');
    }

    public function saveGlobalSettings()
    {
        if (! $this->hasPermission('lms_assessments.edit')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $db = \Config\Database::connect();
        $defRet = in_array($this->request->getPost('default_retention') ?? '', ['permanent_after_upgrade', 'expire_on_batch_change'], true)
            ? $this->request->getPost('default_retention')
            : 'permanent_after_upgrade';
        $db->query("
            INSERT INTO `settings` (`setting_key`, `setting_group`, `setting_value`)
            VALUES ('lms_assessments__default_retention', 'lms_assessments', ?)
            ON DUPLICATE KEY UPDATE `setting_group` = 'lms_assessments', `setting_value` = VALUES(`setting_value`)
        ", [$defRet]);
        return redirect()->back()->with('success', 'Settings updated!');
    }

    public function saveExam()
    {
        if (! $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->saveExamData($this->request->getPost());
        return redirect()->to(base_url('addons/lms-assessments/exams'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function toggleExamStatus()
    {
        if (! $this->hasPermission(['lms_assessments.edit', 'lms_assessments.create'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id  = (int) ($this->request->getPost('id') ?? 0);
        $row = $this->getModel()->find($id);
        if ($row) {
            $newState = ((int) ($row['is_enabled'] ?? 1) === 1) ? 0 : 1;
            $this->getModel()->update($id, ['is_enabled' => $newState, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }
        return redirect()->back()->with('success', 'Assessment status updated.');
    }

    public function deleteExam()
    {
        if (! $this->hasPermission('lms_assessments.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $this->getModel()->delete($id);
        }
        return redirect()->back()->with('success', 'Assessment deleted.');
    }

    public function saveWorksheet()
    {
        if (! $this->hasPermission(['lms_assessments.create', 'lms_assessments.edit'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $uid          = (int) (session()->get('user_id') ?: 1);
        $uploadedFile = $this->request->getFile('worksheet_pdf');
        $res          = $this->getModel()->saveOrUpdateWorksheet($this->request->getPost(), $uploadedFile, $uid);

        return redirect()->to(base_url('addons/lms-assessments/worksheets'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function toggleWorksheetStatus()
    {
        if (! $this->hasPermission(['lms_assessments.edit', 'lms_assessments.create'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id  = (int) ($this->request->getPost('id') ?? 0);
        $db  = \Config\Database::connect();
        $row = $db->table('lms_worksheets')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $newState = ((int) ($row['is_enabled'] ?? 1) === 1) ? 0 : 1;
            $db->table('lms_worksheets')->where('id', $id)->update(['is_enabled' => $newState, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }
        return redirect()->back()->with('success', 'Worksheet visibility updated.');
    }

    public function deleteWorksheet()
    {
        if (! $this->hasPermission('lms_assessments.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_worksheets')->where('id', $id)->delete();
        }
        return redirect()->back()->with('success', 'Worksheet deleted.');
    }

    public function saveDraftAnswers()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->response->setJSON(['ok' => false]);
        }
        $attemptId = (int) ($this->request->getPost('attempt_id') ?? 0);
        $studentId = (int) ($this->request->getPost('student_user_id') ?: (session()->get('active_student_id') ?: session()->get('user_id')));
        $answers   = $this->request->getPost('answers') ?? [];
        if (! is_array($answers)) {
            $answers = [];
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $this->getModel()->saveAttemptDraftAnswers($studentId, $attemptId, $answers);
        return $this->response->setJSON(['ok' => true]);
    }

    public function submitExamAttempt()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(401)->setJSON(['ok' => false, 'message' => 'Unauthorized'])
                : redirect()->to(base_url('login'));
        }

        $effectiveRole  = function_exists('cms_get_effective_role') ? cms_get_effective_role() : 'student';
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');

        if ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0)) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'Please switch into a Child Profile first to take an exam.'])
                : redirect()->back()->with('error', 'Switch into a Child Profile first to take an exam.');
        }

        $studentId = (int) ($this->request->getPost('student_user_id') ?: ($activeStuId ?: (session()->get('user_id') ?: 1)));
        $res       = $this->getModel()->gradeAndRecordAttempt($studentId, $this->request->getPost());
        $res['csrf_token'] = csrf_hash();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($res);
        }
        return redirect()->to(base_url('assessments/portal/history'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function downloadCertificatePdf($certId = 0)
    {
        $db   = \Config\Database::connect();
        $cert = $db->table('lms_certificates')->where('id', (int) $certId)->get()->getRowArray();
        if (! $cert) {
            return redirect()->back()->with('error', 'Certificate not found.');
        }

        $pdfBytes = $this->getModel()->generateCertificatePdfBinary($cert);
        $fileName = 'Certificate_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $cert['certificate_code']) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody($pdfBytes);
    }

    public function verifyCertificateJson()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $code = strtoupper(trim((string) ($this->request->getGet('code') ?? '')));
        if ($code === '') {
            return $this->response->setJSON(['ok' => false, 'message' => 'Enter a valid Certificate Code.']);
        }

        $db   = \Config\Database::connect();
        $cert = $db->table('lms_certificates')->where('certificate_code', $code)->get()->getRowArray();
        if (! $cert) {
            return $this->response->setJSON(['ok' => false, 'message' => 'No certificate found matching code: ' . $code]);
        }

        return $this->response->setJSON([
            'ok'          => true,
            'certificate' => $cert,
            'pdf_url'     => base_url('assessments/certificate-pdf/' . (int) $cert['id']),
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
