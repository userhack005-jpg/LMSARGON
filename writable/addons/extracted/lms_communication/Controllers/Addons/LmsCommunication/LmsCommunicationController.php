<?php

namespace App\Controllers\Addons\LmsCommunication;

use App\Controllers\BaseController;
use App\Models\Addons\LmsCommunication\LmsCommunicationModel;

class LmsCommunicationController extends BaseController
{
    protected ?LmsCommunicationModel $commModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/lms_communication';
    }

    private function getModel(): LmsCommunicationModel
    {
        if ($this->commModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/LmsCommunication/LmsCommunicationModel.php';
            if (! class_exists(LmsCommunicationModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->commModel = new LmsCommunicationModel();
        }
        return $this->commModel;
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
            $data['activeMenu'] = 'addon_lms_communication';
        }
        if (empty($data['user'])) {
            $data['user'] = session()->get();
        }

        $cleanView = ltrim($viewRelPath, '/');
        if (is_file(APPPATH . 'Views/' . $cleanView . '.php')) {
            return view($cleanView, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonLmsCommunication', $this->addonPath);
        $nsView = 'AddonLmsCommunication\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildRoleContext(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->mirrorAssets(false);

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $uid            = (int) (session()->get('user_id') ?: 1);
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');

        $isParentReadOnly = ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0));
        $childIds         = [];
        $parentKidsMap    = [];

        if ($isParentReadOnly) {
            $db = \Config\Database::connect();
            if ($db->tableExists('users')) {
                foreach ($db->table('users')->select('id, full_name, email')->where('parent_user_id', $uid)->get()->getResultArray() as $k) {
                    $kidId = (int) $k['id'];
                    $childIds[] = $kidId;
                    $parentKidsMap[$kidId] = $k['full_name'] ?: 'Child';
                }
            }
        } elseif ($activeStuId > 0) {
            $childIds = [$activeStuId];
        } else {
            $childIds = [$uid];
        }

        $pairs = $model->getValidStudentTeacherPairs($effectiveRole, $uid, $activeStuId);

        $batchesFilterMap = [];
        foreach ($pairs as $p) {
            if (! empty($p['is_past_batch'])) {
                continue;
            }
            $bKey = (int) ($p['cohort_id'] ?: $p['node_id']);
            if (! isset($batchesFilterMap[$bKey])) {
                $batchesFilterMap[$bKey] = [
                    'batch_key'   => $bKey,
                    'batch_title' => $p['batch_title'],
                    'batch_code'  => $p['batch_code'],
                    'count'       => 0,
                ];
            }
            $batchesFilterMap[$bKey]['count']++;
        }

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_lms_communication',
            'activeNav'        => 'lms_communication',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'isProdMode'       => $model->isProductionMode(),
            'user'             => session()->get(),
            'effectiveRole'    => $effectiveRole,
            'uid'              => $uid,
            'activeStuId'      => $activeStuId,
            'isParentReadOnly' => $isParentReadOnly,
            'parentKidsMap'    => $parentKidsMap,
            'childIds'         => $childIds,
            'pairs'            => $pairs,
            'batchesFilterMap' => $batchesFilterMap,
            'canChat'          => $this->hasPermission('lms_communication.chat'),
            'canFeedback'      => $this->hasPermission('lms_communication.feedback'),
            'canModerate'      => $this->hasPermission('lms_communication.moderate'),
            'canFilters'       => $this->hasPermission('lms_communication.filters'),
            'canAlerts'        => $this->hasPermission('lms_communication.alerts'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if ($this->hasPermission('lms_communication.moderate')) {
            return $this->moderationQueueView();
        }
        return $this->chatPortal();
    }

    public function chatPortal()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $data  = $this->buildRoleContext('chat', '1-to-1 Classroom Chat');
        $model = $this->getModel();

        $activePairs = array_values(array_filter($data['pairs'], static fn($p) => empty($p['is_past_batch'])));
        $data['activePairs'] = $activePairs;

        $selPairKey = trim((string) ($this->request->getGet('pair') ?? ''));
        $activePair = null;
        foreach ($activePairs as $p) {
            if ($p['pair_key'] === $selPairKey) {
                $activePair = $p;
                break;
            }
        }
        if (! $activePair && ! empty($activePairs)) {
            $activePair = $activePairs[0];
        }

        $messages = [];
        if ($activePair) {
            $messages = $model->getConversationMessages(
                (int) $activePair['student_id'],
                (int) $activePair['teacher_id'],
                (int) $activePair['node_id'],
                (int) $data['uid'],
                (bool) $data['canModerate']
            );
        }

        $data['activePair'] = $activePair;
        $data['messages']   = $messages;

        return $this->renderAddonView('addons/lms_communication/chat_portal_view', $data);
    }

    public function feedbackPortal()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $data  = $this->buildRoleContext('feedback', 'Teacher Feedback & Reviews');
        $model = $this->getModel();

        $feedbackCards = $model->getFeedbackCardsForRole(
            $data['effectiveRole'],
            (int) $data['uid'],
            $data['childIds']
        );

        $totalCards  = count($feedbackCards);
        $positiveCnt = 0;
        $starsSum    = 0;
        foreach ($feedbackCards as $fc) {
            if (($fc['sentiment_type'] ?? 'positive') === 'positive') {
                $positiveCnt++;
            }
            $starsSum += (int) ($fc['rating_stars'] ?? 5);
        }

        $data['feedbackCards'] = $feedbackCards;
        $data['totalFeedback'] = $totalCards;
        $data['positivePct']   = $totalCards > 0 ? round(($positiveCnt / $totalCards) * 100, 1) : 100.0;
        $data['avgStars']      = $totalCards > 0 ? round($starsSum / $totalCards, 1) : 5.0;

        return $this->renderAddonView('addons/lms_communication/feedback_view', $data);
    }

    public function submitTeacherFeedback()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $uid         = (int) (session()->get('user_id') ?: 1);
        $activeStuId = (int) (session()->get('active_student_id') ?: 0);
        $studentId   = (int) ($this->request->getPost('student_user_id') ?: ($activeStuId > 0 ? $activeStuId : $uid));
        $parentId    = ($activeStuId > 0 || (string) session()->get('role_slug') === 'parent') ? $uid : 0;

        $res = $this->getModel()->submitImmutableTeacherFeedback($studentId, $parentId, $this->request->getPost());
        return redirect()->to(base_url('chat/feedback'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function moderationQueueView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission('lms_communication.moderate')) {
            return redirect()->to(base_url('chat/portal'));
        }

        $data = $this->buildRoleContext('moderation', 'Shadow-Blocked & Flagged Chat Moderation');
        $data['flaggedMessages'] = $this->getModel()->getFlaggedAndBlockedMessages();
        return $this->renderAddonView('addons/lms_communication/moderation_view', $data);
    }

    public function dlpFiltersView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_communication.filters', 'lms_communication.moderate'])) {
            return redirect()->to(base_url('chat/portal'));
        }

        $data = $this->buildRoleContext('filters', 'DLP Safety Filter Rules');
        $data['dlpFilters'] = $this->getModel()->getAllDlpFilters();
        return $this->renderAddonView('addons/lms_communication/filters_view', $data);
    }

    public function alertsLogView()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_communication.alerts', 'lms_communication.moderate'])) {
            return redirect()->to(base_url('chat/portal'));
        }

        $data = $this->buildRoleContext('alerts', 'Email & WhatsApp Alert Logs');
        $data['alertLogs'] = $this->getModel()->getNotificationLogs();
        return $this->renderAddonView('addons/lms_communication/alerts_view', $data);
    }

    public function sendMessageAjax()
    {
        if (! session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'message' => 'Please log in.']);
        }

        $effectiveRole  = function_exists('cms_get_effective_role') ? cms_get_effective_role() : 'student';
        $uid            = (int) (session()->get('user_id') ?: 1);
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');

        if ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0)) {
            return $this->response->setJSON([
                'ok'      => false,
                'message' => 'Parent View is Read-Only. Switch into your Child\'s Learning Hub to message the teacher.',
            ]);
        }

        $senderUid  = ($effectiveRole === 'student' && $activeStuId > 0) ? $activeStuId : $uid;
        $senderRole = ($effectiveRole === 'teacher') ? 'teacher' : 'student';

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $model = $this->getModel();
        $res   = $model->sendOneToOneMessage($senderUid, $senderRole, $this->request->getPost());

        $studentId = (int) ($this->request->getPost('student_user_id') ?? 0);
        $teacherId = (int) ($this->request->getPost('teacher_user_id') ?? 0);
        $nodeId    = (int) ($this->request->getPost('node_id') ?? 0);

        $res['messages']   = $model->getConversationMessages($studentId, $teacherId, $nodeId, $senderUid, false);
        $res['csrf_token'] = csrf_hash();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($res);
        }
        return redirect()->back();
    }

    public function pollMessagesJson()
    {
        if (! session()->get('isLoggedIn')) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            return $this->response->setJSON(['ok' => false, 'messages' => []]);
        }

        $uid         = (int) (session()->get('user_id') ?: 1);
        $activeStuId = (int) (session()->get('active_student_id') ?: 0);
        $viewerUid   = $activeStuId > 0 ? $activeStuId : $uid;
        $isAdmin     = $this->hasPermission('lms_communication.moderate');

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $studentId = (int) ($this->request->getGet('student_id') ?? 0);
        $teacherId = (int) ($this->request->getGet('teacher_id') ?? 0);
        $nodeId    = (int) ($this->request->getGet('node_id') ?? 0);

        $messages = $this->getModel()->getConversationMessages($studentId, $teacherId, $nodeId, $viewerUid, $isAdmin);
        return $this->response->setJSON([
            'ok'       => true,
            'messages' => $messages,
        ]);
    }

    public function widgetContextJson()
    {
        if (! session()->get('isLoggedIn')) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }
            return $this->response->setJSON(['allow_widget' => false]);
        }

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));
        $uid         = (int) (session()->get('user_id') ?: 1);
        $activeStuId = (int) (session()->get('active_student_id') ?: 0);

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        if ($effectiveRole !== 'student') {
            return $this->response->setJSON(['allow_widget' => false]);
        }

        $pairs = $this->getModel()->getValidStudentTeacherPairs('student', $uid, $activeStuId);
        $activePairs = array_values(array_filter($pairs, static fn($p) => empty($p['is_past_batch'])));

        if (empty($activePairs)) {
            return $this->response->setJSON(['allow_widget' => false]);
        }

        return $this->response->setJSON([
            'allow_widget' => true,
            'student_id'   => $activeStuId > 0 ? $activeStuId : $uid,
            'pairs'        => $activePairs,
        ]);
    }

    public function approveFlaggedMessage()
    {
        if (! $this->hasPermission('lms_communication.moderate')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $mCols = array_flip(\Config\Database::connect()->getFieldNames('lms_chat_messages'));
            $upd   = [
                'is_flagged'        => 0,
                'is_hidden'         => 0,
                'is_blocked'        => 0,
                'moderation_status' => 'approved',
                'updated_at'        => gmdate('Y-m-d H:i:s'),
            ];
            $this->getModel()->update($id, array_intersect_key($upd, $mCols));
        }
        return redirect()->to(base_url('addons/lms-communication/moderation'))->with('success', 'Message approved and released to recipient!');
    }

    public function deleteFlaggedMessage()
    {
        if (! $this->hasPermission('lms_communication.moderate')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $this->getModel()->delete($id);
        }
        return redirect()->to(base_url('addons/lms-communication/moderation'))->with('success', 'Message permanently deleted.');
    }

    public function saveDlpFilter()
    {
        if (! $this->hasPermission(['lms_communication.filters', 'lms_communication.moderate'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $name = trim((string) ($this->request->getPost('rule_name') ?? ''));
        $type = in_array($this->request->getPost('pattern_type') ?? '', ['keyword', 'regex'], true) ? $this->request->getPost('pattern_type') : 'keyword';
        $val  = trim((string) ($this->request->getPost('pattern_value') ?? ''));
        $mode = in_array($this->request->getPost('action_mode') ?? '', ['shadow_block', 'flag_only'], true) ? $this->request->getPost('action_mode') : 'shadow_block';

        if ($name !== '' && $val !== '') {
            $now = gmdate('Y-m-d H:i:s');
            \Config\Database::connect()->table('lms_dlp_filters')->insert([
                'rule_name'     => $name,
                'pattern_type'  => $type,
                'pattern_value' => $val,
                'action_mode'   => $mode,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
        return redirect()->to(base_url('addons/lms-communication/dlp-filters'))->with('success', 'Safety Filter Rule saved!');
    }

    public function toggleDlpFilter()
    {
        if (! $this->hasPermission(['lms_communication.filters', 'lms_communication.moderate'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id  = (int) ($this->request->getPost('id') ?? 0);
        $db  = \Config\Database::connect();
        $row = $db->table('lms_dlp_filters')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $newState = ((int) $row['is_active'] === 1) ? 0 : 1;
            $db->table('lms_dlp_filters')->where('id', $id)->update(['is_active' => $newState, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }
        return redirect()->to(base_url('addons/lms-communication/dlp-filters'))->with('success', 'Rule status updated.');
    }

    public function deleteDlpFilter()
    {
        if (! $this->hasPermission(['lms_communication.filters', 'lms_communication.moderate'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            \Config\Database::connect()->table('lms_dlp_filters')->where('id', $id)->delete();
        }
        return redirect()->to(base_url('addons/lms-communication/dlp-filters'))->with('success', 'Rule deleted.');
    }

    public function triggerTestAlert()
    {
        if (! $this->hasPermission(['lms_communication.alerts', 'lms_communication.moderate'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $uid = (int) (session()->get('user_id') ?: 1);
        \Config\Database::connect()->table('lms_notification_logs')->insert([
            'event_type'         => 'Class Reminder & Homework Notification',
            'channel'            => 'email_and_whatsapp',
            'recipient_user_id'  => $uid,
            'recipient_timezone' => (string) (session()->get('user_timezone') ?: 'Asia/Kolkata'),
            'formatted_message'  => 'Upcoming Live Class in 30 minutes. Join link & worksheet ready on your portal.',
            'status'             => 'sent',
            'created_at'         => gmdate('Y-m-d H:i:s'),
        ]);
        return redirect()->to(base_url('addons/lms-communication/alerts'))->with('success', 'Sample Notification logged!');
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
