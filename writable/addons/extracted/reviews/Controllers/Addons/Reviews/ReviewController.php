<?php

namespace App\Controllers\Addons\Reviews;

use App\Controllers\BaseController;
use App\Models\Addons\Reviews\ReviewModel;
use App\Models\Settings\SettingModel;

class ReviewController extends BaseController
{
    protected ReviewModel $revModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->revModel     = new ReviewModel();
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
        $nsView = 'AddonReviews\\Views\\' . str_replace('/', '\\', $viewRelPath);
        return view($nsView, $data);
    }

    private function respondOrRedirect(array $payload)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($payload);
        }
        $flashKey = ($payload['status'] ?? 'success') === 'success' ? 'rev_notice' : 'rev_error';
        return redirect()->to(base_url('addons/reviews'))->with($flashKey, $payload['message'] ?? 'Operation completed.');
    }

    public function asset(string $filename = '')
    {
        $safeName  = basename($filename);
        $assetPath = WRITEPATH . 'addons/extracted/reviews/assets/' . $safeName;

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
        if (! $this->hasPermission('reviews.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access Denied: Missing reviews.view permission.');
        }

        return $this->renderAddonView('addons/reviews/index_view', [
            'activeMenu' => 'addon_reviews',
            'settings'   => $this->settingModel->getSystemSettings(),
            'reviews'    => $this->revModel->getAll(false),
            'categories' => $this->revModel->getCategories(),
            'canCreate'  => $this->hasPermission('reviews.create'),
            'canEdit'    => $this->hasPermission('reviews.edit'),
            'canDelete'  => $this->hasPermission('reviews.delete'),
            'user'       => session()->get(),
            'pageTitle'  => 'Student Reviews & Testimonials Manager',
        ]);
    }

    public function save()
    {
        $id           = (int) $this->request->getPost('id');
        $requiredPerm = ($id > 0) ? 'reviews.edit' : 'reviews.create';

        if (! $this->hasPermission($requiredPerm)) {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Permission Denied: Missing ' . $requiredPerm . ' permission.',
            ]);
        }

        $name  = trim((string) $this->request->getPost('person_name'));
        $quote = trim((string) $this->request->getPost('quote'));

        if ($name === '' or $quote === '') {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Student/Parent Name and Review Quote are required.',
            ]);
        }

        $rating = max(1, min(5, (int) ($this->request->getPost('rating') ?: 5)));

        $payload = [
            'category'    => trim((string) $this->request->getPost('category')) ?: 'General',
            'person_name' => $name,
            'person_role' => trim((string) $this->request->getPost('person_role')) ?: 'Verified Student',
            'rating'      => $rating,
            'quote'       => $quote,
            'avatar_url'  => trim((string) $this->request->getPost('avatar_url')),
            'sort_order'  => max(1, (int) ($this->request->getPost('sort_order') ?: 1)),
        ];

        $this->revModel->saveRecord($payload, $id);

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => $id > 0 ? 'Review #' . $id . ' updated successfully!' : 'New Student Review added!',
        ]);
    }

    public function toggle()
    {
        if (! $this->hasPermission('reviews.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }
        $this->revModel->toggleStatus((int) $this->request->getPost('id'));
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'Review approval status updated.']);
    }

    public function delete()
    {
        if (! $this->hasPermission('reviews.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }
        $this->revModel->deleteRecord((int) $this->request->getPost('id'));
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'Review deleted permanently.']);
    }
}
