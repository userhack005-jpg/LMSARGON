<?php

namespace App\Controllers\Addons\PageBuilder;

use App\Controllers\BaseController;
use App\Models\Addons\PageBuilder\PageBuilderModel;
use App\Models\Settings\SettingModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PageBuilderController extends BaseController
{
    protected PageBuilderModel $pbModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        helper(['url', 'form', 'filesystem']);
        $this->pbModel      = new PageBuilderModel();
        $this->settingModel = new SettingModel();
    }

    private function canManagePages(): bool
    {
        if (! session()->get('isLoggedIn')) {
            return false;
        }
        return in_array((string) session()->get('role_slug'), ['super_admin', 'admin'], true);
    }

    /**
     * Admin Dashboard: List All Dynamic Pages & Toggle DB-Driven Components
     */
    public function index()
    {
        if (! $this->canManagePages()) {
            return redirect()->to(base_url('login'));
        }

        $pages      = $this->pbModel->getAllPages();
        $components = $this->pbModel->getAllComponents(false);
        $settings   = $this->settingModel->getSystemSettings();

        return view('addons/page_builder/pages_list_view', [
            'activeMenu' => 'addon_page_builder',
            'settings'   => $settings,
            'pages'      => $pages,
            'components' => $components,
            'user'       => session()->get(),
            'pageTitle'  => 'Visual Page Builder & Component Registry',
        ]);
    }

    /**
     * Visual Drag & Drop Page Editor Workspace
     */
    public function editor(?int $pageId = null)
    {
        if (! $this->canManagePages()) {
            return redirect()->to(base_url('login'));
        }

        $page = $pageId ? $this->pbModel->getPageById($pageId) : null;
        if (! $page) {
            $page = [
                'id'               => 0,
                'title'            => '',
                'slug'             => '',
                'blocks_json'      => '[]',
                'meta_title'       => '',
                'meta_description' => '',
                'status'           => 'published',
                'is_homepage'      => 0,
                'show_in_nav'      => 1,
            ];
        }

        $enabledComponents = $this->pbModel->getAllComponents(true);
        $settings          = $this->settingModel->getSystemSettings();

        return view('addons/page_builder/editor_view', [
            'activeMenu' => 'addon_page_builder',
            'settings'   => $settings,
            'page'       => $page,
            'components' => $enabledComponents,
            'user'       => session()->get(),
            'pageTitle'  => ($page['id'] > 0 ? 'Edit Page: ' . $page['title'] : 'Create New Dynamic Page'),
        ]);
    }

    /**
     * AJAX Endpoint: Save Page & Blocks JSON
     */
    public function savePage()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug  = trim((string) $this->request->getPost('slug'));

        if ($title === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Page Title is required.']);
        }

        if ($slug === '') {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
        }

        $result = $this->pbModel->savePageData([
            'id'               => (int) $this->request->getPost('page_id'),
            'title'            => $title,
            'slug'             => $slug,
            'blocks_json'      => (string) $this->request->getPost('blocks_json'),
            'meta_title'       => trim((string) $this->request->getPost('meta_title')),
            'meta_description' => trim((string) $this->request->getPost('meta_description')),
            'status'           => (string) ($this->request->getPost('status') ?: 'published'),
            'is_homepage'      => (int) ($this->request->getPost('is_homepage') ? 1 : 0),
            'show_in_nav'      => (int) ($this->request->getPost('show_in_nav') ? 1 : 0),
        ]);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX Endpoint: Delete Dynamic Page
     */
    public function deletePage()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $id = (int) $this->request->getPost('page_id');
        $ok = $this->pbModel->deletePageById($id);

        return $this->response->setJSON([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Page deleted permanently.' : 'Unable to delete page.',
        ]);
    }

    /**
     * AJAX Endpoint: Enable / Disable a Builder Component in Database
     */
    public function toggleComponent()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $compId = (int) $this->request->getPost('component_id');
        return $this->response->setJSON($this->pbModel->flipComponentState($compId));
    }

    /**
     * AJAX Endpoint: Upload Image Directly from Page Builder
     */
    public function uploadMedia()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $imgFile = $this->request->getFile('media_file');
        if (! $imgFile || ! $imgFile->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No valid image selected.']);
        }

        $ext = strtolower($imgFile->getClientExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Only PNG, JPG, WEBP, GIF, and SVG images are allowed.']);
        }

        $uploadDir = FCPATH . 'uploads/cms/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newName = 'cms_' . time() . '_' . $imgFile->getRandomName();
        $imgFile->move($uploadDir, $newName);

        return $this->response->setJSON([
            'status'    => 'success',
            'image_url' => base_url('uploads/cms/' . $newName),
            'message'   => 'Image uploaded!',
        ]);
    }

    /**
     * Frontend Dynamic Page Renderer (Matches `/about-us`, `/p/about-us`, or Homepage)
     */
    public function renderFrontend(string $slug = '')
    {
        $page = $this->pbModel->getPageBySlug($slug);
        if (! $page) {
            throw PageNotFoundException::forPageNotFound('The requested page "/' . esc($slug) . '" was not found.');
        }

        $settings = $this->settingModel->getSystemSettings();
        $navPages = $this->pbModel->getPublishedNavPages();
        $blocks   = json_decode((string) $page['blocks_json'], true) ?: [];

        // Override SEO Meta if defined on the page
        if (! empty($page['meta_title'])) {
            $settings['meta_title'] = $page['meta_title'];
        }
        if (! empty($page['meta_description'])) {
            $settings['meta_description'] = $page['meta_description'];
        }

        return view('addons/page_builder/frontend_page_view', [
            'settings'  => $settings,
            'page'      => $page,
            'blocks'    => $blocks,
            'navPages'  => $navPages,
            'pageTitle' => $page['meta_title'] ?: ($page['title'] . ' — ' . ($settings['site_title'] ?? 'Website')),
        ]);
    }
}