<?php

namespace App\Http\Controllers;

use App\Services\AdminAuditLogService;
use Illuminate\Http\Request;

class AdminExportController extends Controller
{
    /**
     * Show the admin export page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $auditService = new AdminAuditLogService();
        $exportHistory = $auditService->getExportHistory(20);
        $lastExportDate = $auditService->getLastExportDate();
        
        return view('admin.export', compact('exportHistory', 'lastExportDate'));
    }
}
