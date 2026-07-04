<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AdminAuditLogService
{
    /**
     * Log an action in the admin audit log.
     *
     * @param  string  $action  The action performed (login, logout, register, two_factor_verify, two_factor_resend, import, export, purge)
     * @param  bool  $wasSuccessful  Whether the action was successful
     * @param  int  $recordsAffected  Number of records affected
     * @param  int  $recordsSkipped  Number of records skipped
     * @param  int  $recordsFailed  Number of records failed
     * @param  string|null  $errorMessage  Error message if applicable
     * @param  int|null  $authUserId  Explicit acting user id, for events where Auth::id() isn't the right actor (e.g. a failed login attempt)
     * @return AdminAuditLog The created audit log entry
     */
    public function log(
        string $action,
        bool $wasSuccessful,
        int $recordsAffected = 0,
        int $recordsSkipped = 0,
        int $recordsFailed = 0,
        ?string $errorMessage = null,
        ?int $authUserId = null
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'auth_user_id' => $authUserId ?? Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => $action,
            'was_successful' => $wasSuccessful,
            'records_affected' => $recordsAffected,
            'records_skipped' => $recordsSkipped,
            'records_failed' => $recordsFailed,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get the most recent export date for the current user.
     *
     * @return string|null The date of the last export, or null if none found
     */
    public function getLastExportDate(): ?string
    {
        $lastExport = AdminAuditLog::where('auth_user_id', Auth::id())
            ->where('action', 'export')
            ->where('was_successful', true)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastExport ? $lastExport->created_at->toIso8601String() : null;
    }

    /**
     * Get export history for the current user.
     *
     * @param  int  $limit  Number of records to retrieve
     * @return Collection
     */
    public function getExportHistory(int $limit = 10)
    {
        return AdminAuditLog::where('auth_user_id', Auth::id())
            ->where('action', 'export')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get import history for the current user.
     *
     * @param  int  $limit  Number of records to retrieve
     * @return Collection
     */
    public function getImportHistory(int $limit = 10)
    {
        return AdminAuditLog::where('auth_user_id', Auth::id())
            ->where('action', 'import')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
