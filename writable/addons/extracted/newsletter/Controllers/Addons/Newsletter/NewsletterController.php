<?php

namespace App\Controllers\Addons\Newsletter;

use App\Controllers\BaseController;
use App\Models\Addons\Newsletter\NewsletterModel;
use App\Models\Settings\SettingModel;

class NewsletterController extends BaseController
{
    protected NewsletterModel $nlModel;
    protected ?SettingModel $settingModel = null;
    protected string $addonPath;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->addonPath = WRITEPATH . 'addons/extracted/newsletter';

        $modelFile = $this->addonPath . '/Models/Addons/Newsletter/NewsletterModel.php';
        if (! class_exists(NewsletterModel::class) && is_file($modelFile)) {
            require_once $modelFile;
        }
        $this->nlModel = new NewsletterModel();

        if (class_exists(SettingModel::class)) {
            $this->settingModel = new SettingModel();
        }
    }

    private function getModel(): NewsletterModel
    {
        return $this->nlModel;
    }

    private function hasPermission(string $permissionKey): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        if (function_exists('has_permission')) {
            return (bool) has_permission($permissionKey);
        }
        $roleSlug = (string) session()->get('role_slug');
        if ($roleSlug === 'super_admin') {
            return true;
        }
        $perms = session()->get('permissions') ?? session()->get('role_permissions');
        if (is_array($perms)) {
            if (in_array($permissionKey, $perms, true)) {
                return true;
            }
            if (in_array('*', $perms, true)) {
                return true;
            }
            return false;
        }
        return $roleSlug === 'admin';
    }

    private function renderAddonView(string $viewRelPath, array $data = []): string
    {
        if (is_file(APPPATH . 'Views/' . $viewRelPath . '.php')) {
            return view($viewRelPath, $data);
        }
        \Config\Services::autoloader()->addNamespace('AddonNewsletter', $this->addonPath);
        $nsView = 'AddonNewsletter\\Views\\' . str_replace('/', '\\', $viewRelPath);
        return view($nsView, $data);
    }

    private function respondOrRedirect(array $payload)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($payload);
        }
        $isSuccess = ($payload['status'] ?? 'success') === 'success';
        $flashKey  = $isSuccess ? 'success' : 'error';
        return redirect()->to(base_url('addons/newsletter'))->with($flashKey, $payload['message'] ?? 'Operation completed.');
    }

    public function index()
    {
        if (! $this->hasPermission('newsletter.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access Denied: Missing newsletter.view permission.');
        }

        $model = $this->getModel();
        $model->rebuildFormsCache();

        $formFilter   = trim((string) ($this->request->getGet('form_id') ?? 'all'));
        $statusFilter = trim((string) ($this->request->getGet('status') ?? 'all'));
        $searchQuery  = trim((string) ($this->request->getGet('q') ?? ''));

        $builder = $model->builder();

        if ($formFilter !== '' && strtolower($formFilter) !== 'all') {
            $builder->where('form_id', (int) $formFilter);
        }
        if ($statusFilter !== '' && strtolower($statusFilter) !== 'all') {
            $builder->where('status', $statusFilter);
        }
        if ($searchQuery !== '') {
            $builder->groupStart()
                ->like('email', $searchQuery)
                ->orLike('full_name', $searchQuery)
                ->orLike('phone', $searchQuery)
                ->orLike('form_name', $searchQuery)
                ->groupEnd();
        }

        $leads         = $builder->orderBy('id', 'DESC')->get()->getResultArray();
        $formTemplates = $model->getAllFormTemplates();
        $stats         = $model->getStats();

        return $this->renderAddonView('addons/newsletter/index_view', [
            'pageTitle'     => 'Newsletter & Multi-Form Lead Manager',
            'activeMenu'    => 'addon_newsletter',
            'activeNav'     => 'newsletter',
            'settings'      => $this->settingModel ? $this->settingModel->getSystemSettings() : [],
            'user'          => session()->get(),
            'leads'         => $leads,
            'formTemplates' => $formTemplates,
            'stats'         => $stats,
            'formFilter'    => $formFilter,
            'statusFilter'  => $statusFilter,
            'searchQuery'   => $searchQuery,
            'canCreate'     => $this->hasPermission('newsletter.create'),
            'canEdit'       => $this->hasPermission('newsletter.edit'),
            'canDelete'     => $this->hasPermission('newsletter.delete'),
        ]);
    }

    public function templatesJson()
    {
        $model     = $this->getModel();
        $templates = $model->getAllFormTemplates();
        $defaultId = 1;
        foreach ($templates as $t) {
            if ((int) ($t['is_default'] ?? 0) === 1) {
                $defaultId = (int) $t['id'];
                break;
            }
        }

        return $this->response->setJSON([
            'status'     => 'success',
            'default_id' => $defaultId,
            'templates'  => $templates,
        ]);
    }

    public function saveFormTemplate()
    {
        $id           = (int) ($this->request->getPost('id') ?? 0);
        $requiredPerm = ($id > 0) ? 'newsletter.edit' : 'newsletter.create';

        if (! $this->hasPermission($requiredPerm)) {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Permission Denied: Missing ' . $requiredPerm . ' permission.',
            ]);
        }

        $savedId = $this->getModel()->saveFormTemplateData($this->request->getPost());
        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => 'Form Template #' . $savedId . ' saved & synced!',
        ]);
    }

    public function setDefaultFormTemplate()
    {
        if (! $this->hasPermission('newsletter.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }

        $id = max(1, (int) ($this->request->getPost('id') ?? 1));
        $this->getModel()->setDefaultTemplate($id);
        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => 'Form Template #' . $id . ' is now set as Active Default on Frontend!',
        ]);
    }

    public function deleteFormTemplate()
    {
        if (! $this->hasPermission('newsletter.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 1) {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Default Form Template #1 cannot be deleted.',
            ]);
        }
        $this->getModel()->deleteFormTemplateById($id);
        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => 'Form Template deleted.',
        ]);
    }

    public function save()
    {
        $id           = (int) ($this->request->getPost('id') ?? 0);
        $requiredPerm = ($id > 0) ? 'newsletter.edit' : 'newsletter.create';

        if (! $this->hasPermission($requiredPerm)) {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Permission Denied: Missing ' . $requiredPerm . ' permission.',
            ]);
        }

        $model    = $this->getModel();
        $formId   = max(1, (int) ($this->request->getPost('form_id') ?? 1));
        $tpl      = $model->getFormTemplateById($formId);
        $formName = $tpl['form_name'] ?? 'Manual Entry';

        $email    = strtolower(trim((string) ($this->request->getPost('email') ?? '')));
        $fullName = trim((string) ($this->request->getPost('full_name') ?? ''));
        $phone    = trim((string) ($this->request->getPost('phone') ?? ''));
        $status   = trim((string) ($this->request->getPost('status') ?? 'subscribed'));

        if ($fullName === '' && $email === '' && $phone === '') {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Please enter at least Name, Email, or Phone.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        if ($id > 0) {
            $model->update($id, [
                'form_id'    => $formId,
                'form_name'  => $formName,
                'full_name'  => $fullName,
                'email'      => $email,
                'phone'      => $phone,
                'status'     => $status,
                'updated_at' => $now,
            ]);
            return $this->respondOrRedirect([
                'status'  => 'success',
                'message' => 'Lead #' . $id . ' updated successfully.',
            ]);
        }

        $model->upsertLead([
            'form_id'     => $formId,
            'form_name'   => $formName,
            'full_name'   => $fullName,
            'email'       => $email,
            'phone'       => $phone,
            'source_page' => 'admin_manual',
            'ip_address'  => (string) $this->request->getIPAddress(),
        ]);

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => 'Lead added successfully.',
        ]);
    }

    public function toggle()
    {
        if (! $this->hasPermission('newsletter.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }

        $model = $this->getModel();
        $id    = (int) ($this->request->getPost('id') ?? 0);
        $row   = $model->find($id);
        if ($row) {
            $newStatus = ($row['status'] ?? 'subscribed') === 'subscribed' ? 'unsubscribed' : 'subscribed';
            $model->update($id, [
                'status'     => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'Lead status updated.']);
    }

    public function delete()
    {
        if (! $this->hasPermission('newsletter.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $this->getModel()->delete($id);
        }
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'Lead deleted.']);
    }

    public function subscribe()
    {
        $honeypot = trim((string) ($this->request->getPost('website_hp') ?? ''));
        if ($honeypot !== '') {
            return $this->response->setJSON([
                'status'     => 'success',
                'ok'         => true,
                'message'    => 'Thank you!',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $result = $this->getModel()->upsertLead([
            'form_id'     => (int) ($this->request->getPost('form_id') ?? 0),
            'form_name'   => trim((string) ($this->request->getPost('form_name') ?? '')),
            'full_name'   => trim((string) ($this->request->getPost('full_name') ?? '')),
            'email'       => trim((string) ($this->request->getPost('email') ?? '')),
            'phone'       => trim((string) ($this->request->getPost('phone') ?? '')),
            'source_page' => trim((string) ($this->request->getPost('source_page') ?? '')),
            'ip_address'  => (string) $this->request->getIPAddress(),
        ]);

        if ($this->request->isAJAX() or strpos((string) $this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response->setJSON([
                'status'     => $result['ok'] ? 'success' : 'error',
                'ok'         => $result['ok'],
                'message'    => $result['message'],
                'csrf_token' => csrf_hash(),
            ]);
        }

        if ($result['ok']) {
            return redirect()->back()->with('success', $result['message']);
        }
        return redirect()->back()->with('error', $result['message']);
    }

    public function exportCsv()
    {
        if (! $this->hasPermission('newsletter.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access Denied.');
        }

        $formFilter = trim((string) ($this->request->getGet('form_id') ?? 'all'));
        $builder    = $this->getModel()->builder();
        if ($formFilter !== '' && strtolower($formFilter) !== 'all') {
            $builder->where('form_id', (int) $formFilter);
        }
        $rows     = $builder->orderBy('id', 'DESC')->get()->getResultArray();
        $filename = 'newsletter_leads_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Form ID', 'Form Template Name', 'Full Name', 'Mobile / WhatsApp', 'Email Address', 'Source Page', 'Status', 'Created At']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'] ?? '',
                $r['form_id'] ?? '',
                $r['form_name'] ?? '',
                $r['full_name'] ?? '',
                $r['phone'] ?? '',
                $r['email'] ?? '',
                $r['source_page'] ?? '',
                $r['status'] ?? '',
                $r['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function asset(string $filename = '')
    {
        $safeFile = basename($filename);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('/* Asset not found */');
        }

        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : (($ext === 'js') ? 'application/javascript; charset=UTF-8' : 'text/plain');

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($fullPath));
    }
}
