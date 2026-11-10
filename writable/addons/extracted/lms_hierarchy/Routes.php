<?php
/** @var \CodeIgniter\Router\RouteCollection $routes */

$addonDir = __DIR__;
if (is_file($addonDir . '/Models/Addons/LmsHierarchy/LmsHierarchyModel.php')) {
    require_once $addonDir . '/Models/Addons/LmsHierarchy/LmsHierarchyModel.php';
}
if (is_file($addonDir . '/Controllers/Addons/LmsHierarchy/LmsHierarchyController.php')) {
    require_once $addonDir . '/Controllers/Addons/LmsHierarchy/LmsHierarchyController.php';
}

\Config\Services::autoloader()->addNamespace('AddonLmsHierarchy', $addonDir);

if (! defined('LMS_HIERARCHY_SHORTCODE_BOUND')) {
    define('LMS_HIERARCHY_SHORTCODE_BOUND', true);

    \CodeIgniter\Events\Events::on('post_system', static function () {
        if (is_cli()) {
            return;
        }

        // Never run shortcode replacement on Admin / Backend routes
        $reqUri = strtolower(trim((string) strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?'), '/'));
        if (strpos($reqUri, 'addons/') !== false) {
            return;
        }

        $response = service('response');
        $body     = (string) $response->getBody();
        if ($body === '' || stripos($body, '</html>') === false) {
            return;
        }

        $hasShortcode = (strpos($body, '[lms_course') !== false);
        $hasEmbedDiv  = (strpos($body, 'lhr-embed-courses') !== false);
        if (! $hasShortcode && ! $hasEmbedDiv) {
            return;
        }

        if ($hasShortcode) {
            // Only replace shortcodes in text nodes (never inside HTML tag attributes or <code>/<textarea>)
            $body = preg_replace_callback(
                '/(?<![="\'])\[lms_course\s+([^\]]+)\]/i',
                static function (array $m): string {
                    $attrStr = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                    $ids     = '';
                    $layout  = 'split';
                    if (preg_match('/\bids?\s*=\s*["\']?([0-9,\s]+)["\']?/i', $attrStr, $idMatch)) {
                        $ids = preg_replace('/\s+/', '', $idMatch[1]);
                    }
                    if (preg_match('/\blayout\s*=\s*["\']?([a-z0-9_\-]+)["\']?/i', $attrStr, $lyMatch)) {
                        $layout = strtolower(trim($lyMatch[1]));
                    }
                    return '<div class="lhr-embed-courses" data-ids="' . esc($ids) . '" data-layout="' . esc($layout) . '"></div>';
                },
                $body
            );
        }

        $hrModel  = new \App\Models\Addons\LmsHierarchy\LmsHierarchyModel();
        $treeData = $hrModel->getFullHierarchyData();
        $nodesArr = array_values($treeData['nodes_by_id'] ?? []);
        $defCode  = $hrModel->detectVisitorCountryCode();

        $cssUrl  = base_url('assets/addons/lms_hierarchy/lms_hierarchy.css?v=8.5');
        $jsUrl   = base_url('assets/addons/lms_hierarchy/lms_hierarchy.js?v=8.5');
        $demoUrl = base_url('demo/book');
        $enrUrl  = base_url('finance/enroll-checkout');
        $viewUrl = base_url('courses/view');
        $jsonStr = json_encode($nodesArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $inject = '';
        if (strpos($body, 'lms_hierarchy.css') === false) {
            $inject .= '<link rel="stylesheet" href="' . $cssUrl . '">' . "\n";
        }
        if (strpos($body, 'id="js-lhr-global-embed-payload"') === false) {
            $inject .= '<script type="application/json" id="js-lhr-global-embed-payload" '
                . 'data-default-country="' . esc($defCode) . '" '
                . 'data-demo-url="' . esc($demoUrl) . '" '
                . 'data-enroll-url="' . esc($enrUrl) . '" '
                . 'data-view-url="' . esc($viewUrl) . '">' . $jsonStr . '</script>' . "\n";
        }
        if (strpos($body, 'lms_hierarchy.js') === false) {
            $inject .= '<script src="' . $jsUrl . '" defer></script>' . "\n";
        }

        if ($inject !== '') {
            $body = str_ireplace('</body>', $inject . '</body>', $body);
        }
        $response->setBody($body);
    });
}

$routes->group('addons/lms-hierarchy', ['namespace' => 'App\Controllers\Addons\LmsHierarchy'], static function ($routes) {
    $routes->get('/', 'LmsHierarchyController::index');
    $routes->get('builder', 'LmsHierarchyController::treeBuilder');
    $routes->get('pricing', 'LmsHierarchyController::pricingMatrix');
    $routes->get('enrollments', 'LmsHierarchyController::enrollmentsManager');
    $routes->get('catalog', 'LmsHierarchyController::catalogExplorer');

    $routes->post('save-node', 'LmsHierarchyController::saveNode');
    $routes->post('delete-node', 'LmsHierarchyController::deleteNode');
    $routes->post('save-pricing', 'LmsHierarchyController::savePricing');
    $routes->post('enroll-student', 'LmsHierarchyController::enrollStudent');
    $routes->post('upgrade-student', 'LmsHierarchyController::upgradeStudent');
    $routes->post('delete-enrollment', 'LmsHierarchyController::deleteEnrollment');
    $routes->get('node-details-json/(:num)', 'LmsHierarchyController::nodeDetailsJson/$1');
    $routes->get('detect-country-json', 'LmsHierarchyController::detectVisitorCountryJson');
    $routes->get('assets/(:any)', 'LmsHierarchyController::asset/$1');
});

$routes->get('courses/catalog', '\App\Controllers\Addons\LmsHierarchy\LmsHierarchyController::catalogExplorer');
$routes->get('courses/view/(:num)', '\App\Controllers\Addons\LmsHierarchy\LmsHierarchyController::courseDetailPage/$1');
$routes->get('courses/tree-json', '\App\Controllers\Addons\LmsHierarchy\LmsHierarchyController::publicTreeJson');
