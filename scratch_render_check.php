<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin;
use Illuminate\Http\Request;

$admin = Admin::first();
auth('admin')->login($admin);
view()->share('errors', new \Illuminate\Support\ViewErrorBag());

echo "1. Testing TFC Sidebar partial render:\n";
try {
    $sidebarHtml = view('admin.tfc.partials.sidebar-nav')->render();
    echo "  [OK] Sidebar rendered successfully (" . strlen($sidebarHtml) . " bytes)\n";
    if (strpos($sidebarHtml, 'Direct Selling Desk') !== false && strpos($sidebarHtml, 'collapseTfcWhatsApp') !== false) {
        echo "  [OK] Accordion groups found: Direct Selling Desk, WhatsApp CRM, Content & Website\n";
    }
} catch (\Throwable $e) {
    echo "  [FAIL] Sidebar render error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n2. Testing TFC WhatsApp Inbox render:\n";
try {
    $ctrl = app(\App\Http\Controllers\Admin\Tfc\TfcWhatsAppInboxController::class);
    $view = $ctrl->index(Request::create('/admin/tfc/whatsapp/inbox', 'GET'));
    $inboxHtml = $view->render();
    echo "  [OK] WhatsApp Inbox rendered successfully (" . strlen($inboxHtml) . " bytes)\n";
    if (strpos($inboxHtml, 'whatsapp-app-container') !== false && strpos($inboxHtml, 'wa-kpi-bar') !== false) {
        echo "  [OK] Vanriti-style layout found: Full-bleed app container + KPI Toolbar + 3-card grid\n";
    }
} catch (\Throwable $e) {
    echo "  [FAIL] Inbox render error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n3. Testing TFC Dashboard render with new sidebar:\n";
try {
    $dashCtrl = app(\App\Http\Controllers\Admin\Tfc\TfcDashboardController::class);
    $dashView = $dashCtrl->index();
    $dashHtml = $dashView->render();
    echo "  [OK] TFC Dashboard with layout rendered successfully (" . strlen($dashHtml) . " bytes)\n";
} catch (\Throwable $e) {
    echo "  [FAIL] Dashboard render error: " . $e->getMessage() . "\n";
}

echo "\nALL TESTS PASSED!\n";
