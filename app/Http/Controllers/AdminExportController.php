<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogService;
use Illuminate\View\View;

class AdminExportController extends Controller
{
    /**
     * Show the admin export page.
     *
     * @return View
     */
    public function index()
    {
        $auditService = new AdminAuditLogService;
        $exportHistory = $auditService->getExportHistory(20);
        $lastExportDate = $auditService->getLastExportDate();

        return view('admin.export', compact('exportHistory', 'lastExportDate'));
    }
}
