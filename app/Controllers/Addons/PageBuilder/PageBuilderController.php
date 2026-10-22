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
     * Helper: Returns JSON if AJAX request, otherwise cleanly redirects back (Never shows raw JSON page!)
     */
    private function respondOrRedirect(array $payload, string $redirectUrl)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($payload);
        }
        return redirect()->to($redirectUrl)->with('hub_notice', $payload['message'] ?? 'Updated!');
    }

    public function index()
    {
        if (! $this->canManagePages()) {
            return redirect()->to(base_url('login'));
        }

        return view('addons/page_builder/pages_list_view', [
            'activeMenu' => 'addon_page_builder',
            'settings'   => $this->settingModel->getSystemSettings(),
            'pages'      => $this->pbModel->getAllPages(),
            'components' => $this->pbModel->getAllComponents(false),
            'user'       => session()->get(),
            'pageTitle'  => 'Visual Page Builder & Component Registry',
        ]);
    }

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

        return view('addons/page_builder/editor_view', [
            'activeMenu' => 'addon_page_builder',
            'settings'   => $this->settingModel->getSystemSettings(),
            'page'       => $page,
            'components' => $this->pbModel->getAllComponents(true),
            'user'       => session()->get(),
            'pageTitle'  => ($page['id'] > 0 ? 'Edit Page: ' . $page['title'] : 'Create New Dynamic Page'),
        ]);
    }

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

        return $this->respondOrRedirect($result, base_url('addons/page-builder'));
    }

    public function deletePage()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $id = (int) $this->request->getPost('page_id');
        $ok = $this->pbModel->deletePageById($id);

        return $this->respondOrRedirect([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Page deleted permanently.' : 'Unable to delete page.',
        ], base_url('addons/page-builder'));
    }

    public function toggleComponent()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $compId = (int) $this->request->getPost('component_id');
        return $this->respondOrRedirect($this->pbModel->flipComponentState($compId), base_url('addons/page-builder'));
    }

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
     * Visual Footer Builder Workspace (Safe against both old & new views!)
     */
    public function footerEditor()
    {
        if (! $this->canManagePages()) {
            return redirect()->to(base_url('login'));
        }

        $footers      = $this->pbModel->getAllFooters();
        $footersByKey = [];
        $activeRow    = null;

        foreach ($footers as $f) {
            $sKey = $f['style_key'] ?? ('style_' . $f['id']);
            $footersByKey[$sKey] = $f;
            if ((int) ($f['is_active'] ?? 0) === 1) {
                $activeRow = $f;
            }
        }

        $requestedStyle = trim((string) $this->request->getGet('style'));
        $templateId     = (int) $this->request->getGet('template_id');
        $current        = null;

        if ($templateId > 0) {
            foreach ($footers as $f) {
                if ((int) $f['id'] === $templateId) {
                    $current = $f;
                    break;
                }
            }
        } elseif ($requestedStyle !== '' && isset($footersByKey[$requestedStyle])) {
            $current = $footersByKey[$requestedStyle];
        } elseif ($requestedStyle !== '') {
            $colsMap = [
                'style_1col'           => 1,
                'style_2col'           => 2,
                'style_3col'           => 3,
                'style_4col'           => 4,
                'style_highlight_3col' => 3,
            ];
            $cCount  = $colsMap[$requestedStyle] ?? 1;
            $current = [
                'id'             => 0,
                'name'           => strtoupper($requestedStyle),
                'style_key'      => $requestedStyle,
                'columns_count'  => $cCount,
                'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                'is_active'      => 0,
                'sections_json'  => json_encode([
                    [['id' => 'f_init_1', 'type' => 'rich_text', 'data' => ['html' => '<h3>Single Full-Width Footer</h3><p>Customize this footer block or drag more widgets here.</p>']]],
                    [],
                    [],
                    [],
                ]),
            ];
        }

        if (! $current) {
            $current = $activeRow ?: ($footers[0] ?? [
                'id'             => 0,
                'name'           => 'Default Footer',
                'style_key'      => 'style_3col',
                'columns_count'  => 3,
                'copyright_text' => '© ' . date('Y') . ' All Rights Reserved.',
                'is_active'      => 1,
                'sections_json'  => '[[],[],[],[]]',
            ]);
        }

        return view('addons/page_builder/footer_editor_view', [
            'activeMenu'   => 'addon_pb_footer',
            'settings'     => $this->settingModel->getSystemSettings(),
            'footers'      => $footers,      // Passed for backward compatibility
            'footersByKey' => $footersByKey, // Passed for 5 preset tabs
            'current'      => $current,
            'components'   => $this->pbModel->getAllComponents(true),
            'user'         => session()->get(),
            'pageTitle'    => 'Visual Footer Builder & Templates',
        ]);
    }

    public function saveFooter()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $styleKey = trim((string) $this->request->getPost('style_key')) ?: 'style_3col';
        $ok       = $this->pbModel->saveFooterTemplate([
            'footer_id'      => (int) $this->request->getPost('footer_id'),
            'style_key'      => $styleKey,
            'columns_count'  => (int) ($this->request->getPost('columns_count') ?: 3),
            'sections_json'  => (string) $this->request->getPost('sections_json'),
            'copyright_text' => trim((string) $this->request->getPost('copyright_text')),
            'set_active'     => (int) ($this->request->getPost('set_active') ? 1 : 0),
            'current_active' => (int) $this->request->getPost('current_active'),
        ]);

        return $this->respondOrRedirect([
            'status'  => $ok ? 'success' : 'error',
            'message' => $ok ? 'Footer template saved & updated across frontend!' : 'Failed to save footer.',
        ], base_url('addons/page-builder/footer?style=' . $styleKey));
    }

    /**
     * Dedicated Content Hub Page (Announcements, FAQs & Reviews)
     */
    public function contentHub()
    {
        return redirect()->to(base_url('addons/page-builder'))
            ->with('pb_notice', 'Announcements, FAQs, and Reviews are now managed via their dedicated independent Add-On modules.');
    }

    public function saveAnnouncement()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $id  = (int) $this->request->getPost('id');
        $msg = trim((string) $this->request->getPost('message'));
        if ($msg !== '') {
            $this->pbModel->saveAnnouncementRecord([
                'category'  => trim((string) $this->request->getPost('category')) ?: 'General',
                'message'   => $msg,
                'link_text' => trim((string) $this->request->getPost('link_text')),
                'link_url'  => trim((string) $this->request->getPost('link_url')),
                'is_active' => 1,
            ], $id);
        }

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => $id > 0 ? 'Announcement #' . $id . ' updated!' : 'New Announcement added!',
        ], base_url('addons/page-builder/content-hub?tab=announcements'));
    }

    public function deleteAnnouncement()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error']);
        }
        $this->pbModel->deleteAnnouncementRecord((int) $this->request->getPost('id'));

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => 'Announcement deleted permanently.',
        ], base_url('addons/page-builder/content-hub?tab=announcements'));
    }


   

    public function saveReview()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $id   = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('reviewer_name'));
        $text = trim((string) $this->request->getPost('review_text'));
        if ($name !== '' && $text !== '') {
            $this->pbModel->addReviewRecord([
                'reviewer_name' => $name,
                'designation'   => trim((string) $this->request->getPost('designation')) ?: 'Verified Student',
                'rating'        => max(1, min(5, (int) ($this->request->getPost('rating') ?: 5))),
                'review_text'   => $text,
                'is_approved'   => 1,
            ], $id);
        }

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => $id > 0 ? 'Review #' . $id . ' updated!' : 'Student Review added!',
        ], base_url('addons/page-builder/content-hub?tab=reviews'));
    }

    public function deleteReview()
    {
        if (! $this->canManagePages()) {
            return $this->response->setJSON(['status' => 'error']);
        }
        $this->pbModel->deleteReviewRecord((int) $this->request->getPost('id'));

        return $this->respondOrRedirect([
            'status'  => 'success',
            'message' => 'Review deleted permanently.',
        ], base_url('addons/page-builder/content-hub?tab=reviews'));
    }

    public function subscribeLead()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        }
        return $this->response->setJSON($this->pbModel->saveLeadEmail($email));
    }

    public function renderFrontend(string $slug = '')
    {   
        $page = $this->pbModel->getPageBySlug($slug);
        if (! $page) {
            throw PageNotFoundException::forPageNotFound('Page not found: ' . esc($slug));
        }

        $settings = $this->settingModel->getSystemSettings();
        if (! empty($page['meta_title'])) {
            $settings['meta_title'] = $page['meta_title'];
        }
        if (! empty($page['meta_description'])) {
            $settings['meta_description'] = $page['meta_description'];
        }

        return view('addons/page_builder/frontend_page_view', [
            'settings'        => $settings,
            'page'            => $page,
            'blocks'          => json_decode((string) $page['blocks_json'], true) ?: [],
            'navPages'        => $this->pbModel->getPublishedNavPages(),
            'activeFooter'    => $this->pbModel->getActiveFooter(),
            'pageTitle'       => $page['meta_title'] ?: ($page['title'] . ' — ' . ($settings['site_title'] ?? 'Website')),
        ]);
    }
}