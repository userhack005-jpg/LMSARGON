<?php

namespace App\Controllers\Addons\LmsHierarchy;

use App\Controllers\BaseController;
use App\Models\Addons\LmsHierarchy\LmsHierarchyModel;

class LmsHierarchyController extends BaseController
{
    protected ?LmsHierarchyModel $hrModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/lms_hierarchy';
    }

    private function getModel(): LmsHierarchyModel
    {
        if ($this->hrModel === null) {
            $modelFile = $this->addonPath . '/Models/Addons/LmsHierarchy/LmsHierarchyModel.php';
            if (! class_exists(LmsHierarchyModel::class) && is_file($modelFile)) {
                require_once $modelFile;
            }
            $this->hrModel = new LmsHierarchyModel();
        }
        return $this->hrModel;
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

        \Config\Services::autoloader()->addNamespace('AddonLmsHierarchy', $this->addonPath);
        $nsView = 'AddonLmsHierarchy\\Views\\' . str_replace('/', '\\', $cleanView);
        return view($nsView, $data);
    }

    private function buildCommonAdminData(string $activeSubTab, string $pageTitle): array
    {
        $model = $this->getModel();
        $model->syncPageBuilderComponent();
        $model->mirrorAssets(false);

        $treeData    = $model->getFullHierarchyData();
        $students    = $model->getStudentsDirectory();
        $countries   = $model->getServicingCountriesCatalog();
        $companions  = $model->getCompanionAddonsStatus();
        $enrollments = $model->getEnrichedEnrollments();

        return [
            'pageTitle'        => $pageTitle,
            'activeMenu'       => 'addon_lms_hierarchy',
            'activeNav'        => 'lms_hierarchy',
            'activeSubTab'     => $activeSubTab,
            'settings'         => $model->getGlobalSiteSettings(),
            'user'             => session()->get(),
            'companions'       => $companions,
            'nodesById'        => $treeData['nodes_by_id'],
            'branchParentsMap' => $treeData['branch_parents_map'],
            'leafBatchesMap'   => $treeData['leaf_batches_map'],
            'studentsMap'      => $students,
            'countries'        => $countries,
            'pricingByNode'    => $model->getAllPricingByNode(),
            'enrollments'      => $enrollments,
            'canView'          => $this->hasPermission('lms_hierarchy.view'),
            'canCreate'        => $this->hasPermission('lms_hierarchy.create'),
            'canPricing'       => $this->hasPermission(['lms_hierarchy.pricing', 'lms_hierarchy.create']),
            'canEdit'          => $this->hasPermission('lms_hierarchy.edit'),
            'canDelete'        => $this->hasPermission('lms_hierarchy.delete'),
        ];
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_hierarchy.view', 'lms_hierarchy.create', 'lms_hierarchy.edit', 'lms_hierarchy.pricing'])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access denied.');
        }

        if (! $this->hasPermission(['lms_hierarchy.create', 'lms_hierarchy.pricing', 'lms_hierarchy.edit'])) {
            return $this->catalogExplorer();
        }

        $data = $this->buildCommonAdminData('tree', 'Courses & Curriculum Structure');
        return $this->renderAddonView('addons/lms_hierarchy/index_view', $data);
    }

    public function treeBuilder()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_hierarchy.create', 'lms_hierarchy.edit'])) {
            return redirect()->to(base_url('addons/lms-hierarchy'))->with('error', 'Access denied.');
        }

        $data     = $this->buildCommonAdminData('builder', 'Add / Edit Course & Curriculum Level');
        $editId   = (int) ($this->request->getGet('edit_id') ?? 0);
        $parentId = (int) ($this->request->getGet('parent_id') ?? 0);

        $data['editNode']        = ($editId > 0 && isset($data['nodesById'][$editId])) ? $data['nodesById'][$editId] : null;
        $data['defaultParentId'] = $parentId;

        return $this->renderAddonView('addons/lms_hierarchy/builder_view', $data);
    }

    public function pricingMatrix()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_hierarchy.pricing', 'lms_hierarchy.create', 'lms_hierarchy.edit'])) {
            return redirect()->to(base_url('addons/lms-hierarchy'))->with('error', 'Access denied.');
        }

        $data           = $this->buildCommonAdminData('pricing', 'Course Fee Plans');
        $selectedNodeId = (int) ($this->request->getGet('node_id') ?? 0);
        if ($selectedNodeId <= 0 && ! empty($data['leafBatchesMap'])) {
            $selectedNodeId = (int) array_key_first($data['leafBatchesMap']);
        }
        $data['selectedNodeId'] = $selectedNodeId;

        return $this->renderAddonView('addons/lms_hierarchy/pricing_view', $data);
    }

    public function enrollmentsManager()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }
        if (! $this->hasPermission(['lms_hierarchy.edit', 'lms_hierarchy.create'])) {
            return redirect()->to(base_url('addons/lms-hierarchy'))->with('error', 'Access denied.');
        }

        $data = $this->buildCommonAdminData('enrollments', 'Student Course Enrollments & Level Promotion');
        return $this->renderAddonView('addons/lms_hierarchy/enrollments_view', $data);
    }

    /**
     * Role-Adaptive My Courses Portal (`/courses/catalog`):
     * - Teacher: Sees strictly ONLY their assigned courses & classroom batches (Zero global catalog clutter!).
     * - Parent: Sees strictly their linked children's active enrolled courses (Zero IDs or marketing clutter!).
     * - Student: Sees their active enrolled courses + optional "Explore More Programs" tab.
     */
    public function catalogExplorer()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $data  = $this->buildCommonAdminData('catalog', 'My Courses & Academic Programs');
        $model = $this->getModel();

        $effectiveRole = function_exists('cms_get_effective_role')
            ? cms_get_effective_role()
            : strtolower(trim((string) (session()->get('effective_role_slug') ?: session()->get('role_slug') ?: 'student')));

        $uid            = (int) (session()->get('user_id') ?: 1);
        $activeStuId    = (int) (session()->get('active_student_id') ?: 0);
        $portalViewMode = (string) (session()->get('portal_view_mode') ?: 'default');

        $myEnrollments  = [];
        $teacherCourses = [];
        $teacherCohorts = [];

        if ($effectiveRole === 'parent' && ($portalViewMode !== 'student' || $activeStuId <= 0)) {
            $childIds = [];
            $db       = \Config\Database::connect();
            if ($db->tableExists('users')) {
                foreach ($db->table('users')->select('id')->where('parent_user_id', $uid)->get()->getResultArray() as $k) {
                    $childIds[] = (int) $k['id'];
                }
            }
            $myEnrollments = $model->getEnrichedEnrollments($childIds, $uid, []);
        } elseif ($effectiveRole === 'teacher') {
            $tData          = $model->getTeacherAssignedCoursesAndCohorts($uid);
            $teacherCourses = $tData['courses'];
            $teacherCohorts = $tData['cohorts'];
            $leafIds        = array_keys($teacherCourses);
            $myEnrollments  = ! empty($leafIds) ? $model->getEnrichedEnrollments([], 0, $leafIds) : [];
        } else {
            $targetStudentId = $activeStuId > 0 ? $activeStuId : $uid;
            $myEnrollments   = $model->getEnrichedEnrollments([$targetStudentId], 0, []);
        }

        $data['effectiveRole']  = $effectiveRole;
        $data['myEnrollments']  = $myEnrollments;
        $data['teacherCourses'] = $teacherCourses;
        $data['teacherCohorts'] = $teacherCohorts;
        $data['canManageTree']  = $this->hasPermission(['lms_hierarchy.create', 'lms_hierarchy.edit']);

        return $this->renderAddonView('addons/lms_hierarchy/catalog_view', $data);
    }

    /**
     * Dedicated Course / Level Detail Page (`/courses/view/(:num)`)
     * When a visitor or student clicks a Course Card, it opens its own clean page!
     * - If the node has sub-levels inside it, displays its sub-level cards cleanly.
     * - If the node is a final course (`is_leaf_node = 1`), displays the full rich-text curriculum,
     *   auto-detected local fee, and "Book Free Demo" / "Enroll Now" buttons!
     */
    public function courseDetailPage($nodeId = 0)
    {
        $nid      = (int) $nodeId;
        $model    = $this->getModel();
        $treeData = $model->getFullHierarchyData();
        $nodes    = $treeData['nodes_by_id'];

        if (! isset($nodes[$nid])) {
            return redirect()->to(base_url('/'))->with('error', 'Course not found.');
        }

        $courseNode   = $nodes[$nid];
        $childNodes   = [];
        foreach (($courseNode['children_ids'] ?? []) as $cid) {
            if (isset($nodes[$cid]) && ($nodes[$cid]['status'] ?? 'active') === 'active') {
                $childNodes[] = $nodes[$cid];
            }
        }

        // Build ancestor breadcrumb links for step-by-step navigation
        $ancestors = [];
        $currPid   = (int) ($courseNode['parent_id'] ?? 0);
        $guard     = 0;
        while ($currPid > 0 && isset($nodes[$currPid]) && $guard < 10) {
            array_unshift($ancestors, $nodes[$currPid]);
            $currPid = (int) ($nodes[$currPid]['parent_id'] ?? 0);
            $guard++;
        }

        $visitorCountry = $model->detectVisitorCountryCode();
        $priceInfo      = $courseNode['pricing_by_country'][$visitorCountry] ?? reset($courseNode['pricing_by_country'] ?? []) ?: null;
        $companions     = $model->getCompanionAddonsStatus();

        return $this->renderAddonView('addons/lms_hierarchy/course_detail_view', [
            'pageTitle'      => $courseNode['title'],
            'settings'       => $model->getGlobalSiteSettings(),
            'user'           => session()->get(),
            'courseNode'     => $courseNode,
            'childNodes'     => $childNodes,
            'ancestors'      => $ancestors,
            'priceInfo'      => $priceInfo,
            'companions'     => $companions,
            'isLoggedIn'     => (bool) session()->get('isLoggedIn'),
        ]);
    }

    public function saveNode()
    {
        if (! $this->hasPermission(['lms_hierarchy.create', 'lms_hierarchy.edit'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $uploadedThumb = $this->request->getFile('thumbnail_file');
        $res           = $this->getModel()->saveOrUpdateNode($this->request->getPost(), $uploadedThumb);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'     => $res['ok'] ? 'success' : 'error',
                'node_id'    => $res['node_id'] ?? 0,
                'message'    => $res['message'],
                'csrf_token' => csrf_hash(),
            ]);
        }

        if (! $res['ok']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->to(base_url('addons/lms-hierarchy'))
            ->with('success', $res['message']);
    }

    public function deleteNode()
    {
        if (! $this->hasPermission('lms_hierarchy.delete')) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $id  = (int) ($this->request->getPost('id') ?? 0);
        $res = $this->getModel()->deleteNodeAndChildren($id);
        return redirect()->to(base_url('addons/lms-hierarchy'))->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function savePricing()
    {
        if (! $this->hasPermission(['lms_hierarchy.pricing', 'lms_hierarchy.create', 'lms_hierarchy.edit'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $nodeId = (int) ($this->request->getPost('node_id') ?? 0);
        $res    = $this->getModel()->saveLeafPricingMatrix($this->request->getPost());

        return redirect()->to(base_url('addons/lms-hierarchy/pricing?node_id=' . $nodeId))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function enrollStudent()
    {
        if (! $this->hasPermission(['lms_hierarchy.edit', 'lms_hierarchy.create'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $res = $this->getModel()->enrollStudentInLeaf($this->request->getPost());
        return redirect()->to(base_url('addons/lms-hierarchy/enrollments'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function upgradeStudent()
    {
        if (! $this->hasPermission(['lms_hierarchy.edit', 'lms_hierarchy.create'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $enrId  = (int) ($this->request->getPost('enrollment_id') ?? 0);
        $leafId = (int) ($this->request->getPost('target_leaf_node_id') ?? 0);
        $note   = trim((string) ($this->request->getPost('upgrade_note') ?? ''));

        $res = $this->getModel()->upgradeStudentBatch($enrId, $leafId, $note);
        return redirect()->to(base_url('addons/lms-hierarchy/enrollments'))
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function deleteEnrollment()
    {
        if (! $this->hasPermission(['lms_hierarchy.edit', 'lms_hierarchy.delete'])) {
            return redirect()->back()->with('error', 'Access denied.');
        }
        $enrId = (int) ($this->request->getPost('enrollment_id') ?? 0);
        if ($enrId > 0) {
            \Config\Database::connect()->table('lms_hierarchy_enrollments')->where('id', $enrId)->delete();
        }
        return redirect()->to(base_url('addons/lms-hierarchy/enrollments'))->with('success', 'Student enrollment removed.');
    }

    public function detectVisitorCountryJson()
    {
        $tzHint   = trim((string) ($this->request->getGet('tz') ?? ''));
        $simCode  = trim((string) ($this->request->getGet('country') ?? ''));
        $model    = $this->getModel();
        $detected = $model->detectVisitorCountryCode($tzHint, $simCode);

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $countries = $model->getServicingCountriesCatalog();
        $cInfo     = $countries[$detected] ?? ['code' => $detected, 'name' => $detected, 'flag' => '🌍', 'currency_code' => 'INR', 'currency_sym' => '₹'];

        return $this->response->setJSON([
            'status'        => 'success',
            'country_code'  => $detected,
            'country_name'  => $cInfo['name'] ?? $detected,
            'country_flag'  => $cInfo['flag'] ?? '🌍',
            'currency_code' => $cInfo['currency_code'] ?? 'INR',
            'currency_sym'  => $cInfo['currency_sym'] ?? '₹',
        ]);
    }

    public function nodeDetailsJson($nodeId = 0)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $nid      = (int) $nodeId;
        $treeData = $this->getModel()->getFullHierarchyData();
        $node     = $treeData['nodes_by_id'][$nid] ?? null;
        if (! $node) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Course not found']);
        }
        return $this->response->setJSON(['status' => 'success', 'node' => $node]);
    }

    public function publicTreeJson()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        $treeData = $this->getModel()->getFullHierarchyData();
        return $this->response->setJSON([
            'status'      => 'success',
            'nodes_by_id' => $treeData['nodes_by_id'],
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
