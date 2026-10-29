<?php

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
