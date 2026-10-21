<?php

namespace App\Controllers\Addons\Faqs;

use App\Controllers\BaseController;
use App\Models\Addons\Faqs\FaqModel;
use App\Models\Settings\SettingModel;

class FaqController extends BaseController
{
    protected FaqModel $faqModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->faqModel     = new FaqModel();
        $this->settingModel = new SettingModel();
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
            return in_array($permissionKey, $perms, true) || in_array('*', $perms, true);
        }
        return $roleSlug === 'admin';
    }

    private function renderAddonView(string $viewRelPath, array $data = []): string
    {
        if (is_file(APPPATH . 'Views/' . $viewRelPath . '.php')) {
            return view($viewRelPath, $data);
        }
        return view('AddonFaqs\Views\\' . str_replace('/', '\\', $viewRelPath), $data);
    }

    private function respondOrRedirect(array $payload)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($payload);
        }
        $flashKey = ($payload['status'] ?? 'success') === 'success' ? 'faq_notice' : 'faq_error';
        return redirect()->to(base_url('addons/faqs'))->with($flashKey, $payload['message'] ?? 'Operation completed.');
    }

    public function asset(string $filename = '')
    {
        $safeName  = basename($filename);
        $assetPath = WRITEPATH . 'addons/extracted/faqs/assets/' . $safeName;

        if (! is_file($assetPath)) {
            return $this->response->setStatusCode(404)->setBody('/* Asset not found */');
        }

        $ext  = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : (($ext === 'js') ? 'application/javascript; charset=UTF-8' : 'text/plain');

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($assetPath));
    }

    public function index()
    {
        if (! $this->hasPermission('faqs.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access Denied: Missing faqs.view permission.');
        }

        return $this->renderAddonView('addons/faqs/index_view', [
            'activeMenu' => 'addon_faqs',
            'settings'   => $this->settingModel->getSystemSettings(),
            'faqs'       => $this->faqModel->getAll(false),
            'categories' => $this->faqModel->getCategories(),
            'canCreate'  => $this->hasPermission('faqs.create'),
            'canEdit'    => $this->hasPermission('faqs.edit'),
            'canDelete'  => $this->hasPermission('faqs.delete'),
            'user'       => session()->get(),
            'pageTitle'  => 'Dynamic FAQ Accordion Manager',
        ]);
    }

    public function save()
    {
        $id           = (int) $this->request->getPost('id');
        $requiredPerm = ($id > 0) ? 'faqs.edit' : 'faqs.create';

        if (! $this->hasPermission($requiredPerm)) {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Permission Denied: Missing ' . $requiredPerm . ' permission.',
            ]);
        }

        $q = trim((string) $this->request->getPost('question'));
        $a = trim((string) $this->request->getPost('answer'));

        if ($q === '' || $a === '') {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Both Question and Answer are required.',
            ]);
        }

        $payload = [
            'category'   => trim((string) $this->request->getPost('category')) ?: 'General',
            'question'   => $q,
            'answer'     => $a,
            'sort_order' => max(1, (int) ($this->request->getPost('sort_order') ?: 1)),
        ];

        $this->faqModel->saveRecord($payload, $id);

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => $id > 0 ? 'FAQ #' . $id . ' updated successfully!' : 'New FAQ item added!',
        ]);
    }

    public function toggle()
    {
        if (! $this->hasPermission('faqs.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }
        $this->faqModel->toggleStatus((int) $this->request->getPost('id'));
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'FAQ visibility updated.']);
    }

    public function delete()
    {
        if (! $this->hasPermission('faqs.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }
        $this->faqModel->deleteRecord((int) $this->request->getPost('id'));
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'FAQ deleted permanently.']);
    }
}