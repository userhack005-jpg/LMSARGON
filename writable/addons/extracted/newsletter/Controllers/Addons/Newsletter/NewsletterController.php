<?php

namespace App\Controllers\Addons\Newsletter;

use App\Controllers\BaseController;
use App\Models\Addons\Newsletter\NewsletterModel;

class NewsletterController extends BaseController
{
    protected NewsletterModel $nlModel;
    protected string $addonPath;

    public function __construct()
    {
        $this->addonPath = WRITEPATH . 'addons/extracted/newsletter';
        $modelFile       = $this->addonPath . '/Models/Addons/Newsletter/NewsletterModel.php';
        if (! class_exists(NewsletterModel::class) && is_file($modelFile)) {
            require_once $modelFile;
        }
        $this->nlModel = new NewsletterModel();
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

    private function renderAddonView(string $viewFile, array $data = []): string
    {
        \Config\Services::autoloader()->addNamespace('AddonNewsletter', $this->addonPath);
        \Config\Services::autoloader()->addNamespace('App', $this->addonPath);

        $fullViewPath = $this->addonPath . '/Views/' . ltrim($viewFile, '/') . '.php';
        if (is_file($fullViewPath)) {
            $renderer = \Config\Services::renderer($this->addonPath . '/Views', null, false);
            return $renderer->setData($data)->render($viewFile);
        }

        return view($viewFile, $data);
    }

    public function index()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('newsletter.view')) {
            return redirect()->to('/dashboard')->with('error', 'Access denied. You do not have permission to view Newsletter Leads.');
        }

        $tagFilter    = trim((string) ($this->request->getGet('tag') ?? 'all'));
        $statusFilter = trim((string) ($this->request->getGet('status') ?? 'all'));
        $searchQuery  = trim((string) ($this->request->getGet('q') ?? ''));

        $builder = $this->nlModel->builder();

        if ($tagFilter !== '' && strtolower($tagFilter) !== 'all') {
            $builder->where('lead_tag', $tagFilter);
        }
        if ($statusFilter !== '' && strtolower($statusFilter) !== 'all') {
            $builder->where('status', $statusFilter);
        }
        if ($searchQuery !== '') {
            $builder->groupStart()
                ->like('email', $searchQuery)
                ->orLike('full_name', $searchQuery)
                ->orLike('phone', $searchQuery)
                ->orLike('interest_topic', $searchQuery)
                ->orLike('lead_tag', $searchQuery)
                ->groupEnd();
        }

        $leads = $builder->orderBy('id', 'DESC')->get()->getResultArray();
        $stats = $this->nlModel->getStats();

        return $this->renderAddonView('addons/newsletter/index_view', [
            'pageTitle'    => 'Newsletter & Lead Magnet CRM',
            'activeNav'    => 'newsletter',
            'user'         => session()->get(),
            'leads'        => $leads,
            'stats'        => $stats,
            'tagFilter'    => $tagFilter,
            'statusFilter' => $statusFilter,
            'searchQuery'  => $searchQuery,
            'canCreate'    => $this->hasPermission('newsletter.create'),
            'canEdit'      => $this->hasPermission('newsletter.edit'),
            'canDelete'    => $this->hasPermission('newsletter.delete'),
        ]);
    }

    public function save()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            if (! $this->hasPermission('newsletter.edit')) {
                return redirect()->to('/addons/newsletter')->with('error', 'Access denied. You do not have permission to edit subscribers.');
            }
        } else {
            if (! $this->hasPermission('newsletter.create')) {
                return redirect()->to('/addons/newsletter')->with('error', 'Access denied. You do not have permission to create subscribers.');
            }
        }

        $email    = strtolower(trim((string) ($this->request->getPost('email') ?? '')));
        $fullName = trim((string) ($this->request->getPost('full_name') ?? ''));
        $phone    = trim((string) ($this->request->getPost('phone') ?? ''));
        $topic    = trim((string) ($this->request->getPost('interest_topic') ?? ''));
        $leadTag  = trim((string) ($this->request->getPost('lead_tag') ?? 'Manual Entry'));
        $status   = trim((string) ($this->request->getPost('status') ?? 'subscribed'));

        if ($email === '' or ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/addons/newsletter')->with('error', 'Valid email address is required.');
        }

        $now = date('Y-m-d H:i:s');
        if ($id > 0) {
            $this->nlModel->update($id, [
                'full_name'      => $fullName,
                'email'          => $email,
                'phone'          => $phone,
                'interest_topic' => $topic,
                'lead_tag'       => $leadTag ?: 'Manual Entry',
                'status'         => $status,
                'updated_at'     => $now,
            ]);
            return redirect()->to('/addons/newsletter')->with('success', 'Subscriber updated successfully.');
        }

        $this->nlModel->upsertLead([
            'full_name'      => $fullName,
            'email'          => $email,
            'phone'          => $phone,
            'interest_topic' => $topic,
            'lead_tag'       => $leadTag ?: 'Manual Entry',
            'source_page'    => 'admin_manual',
            'ip_address'     => (string) $this->request->getIPAddress(),
        ]);

        return redirect()->to('/addons/newsletter')->with('success', 'Subscriber added successfully.');
    }

    public function toggle()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('newsletter.edit')) {
            return redirect()->to('/addons/newsletter')->with('error', 'Access denied. You do not have permission to edit subscribers.');
        }

        $id  = (int) ($this->request->getPost('id') ?? 0);
        $row = $this->nlModel->find($id);
        if ($row) {
            $newStatus = ($row['status'] ?? 'subscribed') === 'subscribed' ? 'unsubscribed' : 'subscribed';
            $this->nlModel->update($id, [
                'status'     => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('/addons/newsletter')->with('success', 'Subscriber status updated.');
    }

    public function delete()
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('newsletter.delete')) {
            return redirect()->to('/addons/newsletter')->with('error', 'Access denied. You do not have permission to delete subscribers.');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $this->nlModel->delete($id);
        }
        return redirect()->to('/addons/newsletter')->with('success', 'Lead deleted.');
    }

    public function subscribe()
    {
        $email    = trim((string) ($this->request->getPost('email') ?? ''));
        $fullName = trim((string) ($this->request->getPost('full_name') ?? ''));
        $phone    = trim((string) ($this->request->getPost('phone') ?? ''));
        $topic    = trim((string) ($this->request->getPost('interest_topic') ?? ''));
        $leadTag  = trim((string) ($this->request->getPost('lead_tag') ?? 'Website Lead Magnet'));
        $srcPage  = trim((string) ($this->request->getPost('source_page') ?? ''));
        $honeypot = trim((string) ($this->request->getPost('website_hp') ?? ''));

        if ($honeypot !== '') {
            return $this->response->setJSON([
                'status'     => 'success',
                'ok'         => true,
                'message'    => 'Thank you for subscribing!',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $result = $this->nlModel->upsertLead([
            'email'          => $email,
            'full_name'      => $fullName,
            'phone'          => $phone,
            'interest_topic' => $topic,
            'lead_tag'       => $leadTag,
            'source_page'    => $srcPage,
            'ip_address'     => (string) $this->request->getIPAddress(),
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
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        if (! $this->hasPermission('newsletter.view')) {
            return redirect()->to('/dashboard')->with('error', 'Access denied.');
        }

        $rows = $this->nlModel->orderBy('id', 'DESC')->findAll();
        $filename = 'newsletter_leads_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Full Name', 'Email', 'Phone / WhatsApp', 'Course / Topic', 'Lead Tag', 'Source Page', 'Status', 'Subscribed At']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'] ?? '',
                $r['full_name'] ?? '',
                $r['email'] ?? '',
                $r['phone'] ?? '',
                $r['interest_topic'] ?? '',
                $r['lead_tag'] ?? '',
                $r['source_page'] ?? '',
                $r['status'] ?? '',
                $r['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function asset($file = '')
    {
        $safeFile = basename((string) $file);
        $fullPath = $this->addonPath . '/assets/' . $safeFile;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('/* Asset not found */');
        }

        $ext  = strtolower((string) pathinfo($safeFile, PATHINFO_EXTENSION));
        $mime = ($ext === 'css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody((string) file_get_contents($fullPath));
    }
}
