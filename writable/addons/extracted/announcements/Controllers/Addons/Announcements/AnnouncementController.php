<?php

namespace App\Controllers\Addons\Announcements;

use App\Controllers\BaseController;
use App\Models\Addons\Announcements\AnnouncementModel;
use App\Models\Settings\SettingModel;

class AnnouncementController extends BaseController
{
    protected AnnouncementModel $annModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->annModel     = new AnnouncementModel();
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
        $nsView = 'AddonAnnouncements\\Views\\' . str_replace('/', '\\', $viewRelPath);
        return view($nsView, $data);
    }

    private function respondOrRedirect(array $payload)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($payload);
        }
        $flashKey = ($payload['status'] ?? 'success') === 'success' ? 'ann_notice' : 'ann_error';
        return redirect()->to(base_url('addons/announcements'))->with($flashKey, $payload['message'] ?? 'Operation completed.');
    }

    public function asset(string $filename = '')
    {
        $safeName  = basename($filename);
        $assetPath = WRITEPATH . 'addons/extracted/announcements/assets/' . $safeName;

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
        if (! $this->hasPermission('announcements.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Access Denied: Missing announcements.view permission.');
        }

        return $this->renderAddonView('addons/announcements/index_view', [
            'activeMenu'    => 'addon_announcements',
            'settings'      => $this->settingModel->getSystemSettings(),
            'announcements' => $this->annModel->getAll(false),
            'categories'    => $this->annModel->getCategories(),
            'canCreate'     => $this->hasPermission('announcements.create'),
            'canEdit'       => $this->hasPermission('announcements.edit'),
            'canDelete'     => $this->hasPermission('announcements.delete'),
            'user'          => session()->get(),
            'pageTitle'     => 'Dynamic Announcements Manager',
        ]);
    }

    public function save()
    {
        $id           = (int) $this->request->getPost('id');
        $requiredPerm = ($id > 0) ? 'announcements.edit' : 'announcements.create';

        if (! $this->hasPermission($requiredPerm)) {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Permission Denied: Missing ' . $requiredPerm . ' permission.',
            ]);
        }

        $msg = trim((string) $this->request->getPost('message'));
        if ($msg === '') {
            return $this->respondOrRedirect([
                'status'  => 'error',
                'message' => 'Announcement message is required.',
            ]);
        }

        $payload = [
            'category'  => trim((string) $this->request->getPost('category')) ?: 'General',
            'message'   => $msg,
            'link_text' => trim((string) $this->request->getPost('link_text')),
            'link_url'  => trim((string) $this->request->getPost('link_url')),
        ];

        $this->annModel->saveRecord($payload, $id);

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => $id > 0 ? 'Announcement #' . $id . ' updated successfully!' : 'New Announcement created!',
        ]);
    }

    public function toggle()
    {
        if (! $this->hasPermission('announcements.edit')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }
        $this->annModel->toggleStatus((int) $this->request->getPost('id'));
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'Announcement status updated.']);
    }

    public function delete()
    {
        if (! $this->hasPermission('announcements.delete')) {
            return $this->respondOrRedirect(['status' => 'error', 'message' => 'Permission Denied.']);
        }
        $this->annModel->deleteRecord((int) $this->request->getPost('id'));
        return $this->respondOrRedirect(['status' => 'success', 'message' => 'Announcement deleted permanently.']);
    }
}
