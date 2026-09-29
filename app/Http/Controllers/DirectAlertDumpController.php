<?php

namespace App\Http\Controllers;

use App\Mail\AdminOperationNotification; // Import the DirectAlert model
use App\Models\DirectAlert;
use App\Services\AdminAuditLogService;
use Carbon\Carbon; // Import Carbon for date handling
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // Import Auth facade
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Import Response facade
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;

class DirectAlertDumpController extends Controller
{
    /**
     * Dump DirectAlert data as CSV within a date range.
     *
     * @return \Illuminate\Http\Response
     */
    public function dumpCsv(Request $request)
    {
        $auditService = new AdminAuditLogService;

        // Check if the user is authenticated and has the admin role
        $user = Auth::user();
        if (! $user || $user->role !== 'admin') {
            $auditService->log(
                action: 'export',
                wasSuccessful: false,
                errorMessage: 'Unauthorized access attempt'
            );

            return Response::make('Unauthorized', 401);
        }

        $start = $request->input('start');
        $end = $request->input('end');
        $dateFormat = $request->input('date_format', 'default');

        // Validate start and end dates
        try {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);

            // Ensure end date is not before start date
            if ($startDate->greaterThan($endDate)) {
                $auditService->log(
                    action: 'export',
                    wasSuccessful: false,
                    errorMessage: 'Invalid date range: start date is after end date'
                );

                return Response::make('Invalid date range: start date is after end date.', 400);
            }

        } catch (\Exception $e) {
            // Return 400 if dates are invalid or missing
            $auditService->log(
                action: 'export',
                wasSuccessful: false,
                errorMessage: 'Invalid or missing date parameters: '.$e->getMessage()
            );

            return Response::make('Invalid or missing date parameters. Please provide valid ISO timestamps for "start" and "end".', 400);
        }

        // Query data within the date range
        $data = DirectAlert::whereBetween('updated_at', [$startDate, $endDate->endOfDay()]) // Include the whole end day
            ->get();

        if ($data->isEmpty()) {
            $auditService->log(
                action: 'export',
                wasSuccessful: false,
                errorMessage: 'No data found for the specified date range'
            );

            return Response::make('No data found for the specified date range.', 404);
        }

        $recordCount = $data->count();

        // Mark these rows as exported so an admin can later purge their
        // contact info via purgeExportedContactInfo() - a plain query builder
        // update (not touching the encrypted columns) so exported_at gets set
        // without re-encrypting anything.
        DB::table('direct_alert')->whereIn('id', $data->pluck('id'))->update(['exported_at' => now()]);

        // Log successful export
        $auditService->log(
            action: 'export',
            wasSuccessful: true,
            recordsAffected: $recordCount
        );

        // Send email notification (don't fail if email fails)
        try {
            Mail::to('ben@herila.net')->send(new AdminOperationNotification(
                operation: 'export',
                wasSuccessful: true,
                recordsAffected: $recordCount,
                userName: $user->name ?? $user->email
            ));
        } catch (\Exception $e) {
            // Log the error but don't fail the operation
            Log::error('Failed to send export notification email: '.$e->getMessage());
        }

        // Generate CSV content
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="direct_alert_dump_'.$startDate->format('Ymd').'_to_'.$endDate->format('Ymd').'.csv"',
        ];

        $callback = function () use ($data, $dateFormat) {
            $file = fopen('php://output', 'w');

            // Add friendly CSV header row
            $headerRow = [
                'Name',
                'Account #',
                'Zip',
                'Updated',
                'email',
                'wantEmail',
                'homePhone',
                'wantHomeCall',
                'workPhone',
                'wantWorkCall',
                'cellPhone',
                'wantCellCall',
                'wantCellSMS',
            ];
            fputcsv($file, $headerRow);

            // Add data rows with friendly column mapping
            foreach ($data as $row) {
                $updatedAt = $row->updated_at ?? $row->created_at;

                // Format the date based on the selected format
                switch ($dateFormat) {
                    case 'excel':
                        $formattedDate = $updatedAt ? $updatedAt->format('m/d/Y H:i:s') : '';
                        break;
                    case 'iso_with_seconds':
                        $formattedDate = $updatedAt ? $updatedAt->format('Y-m-d\TH:i:s') : '';
                        break;
                    case 'iso_without_seconds':
                        $formattedDate = $updatedAt ? $updatedAt->format('Y-m-d\TH:i') : '';
                        break;
                    case 'default':
                    default:
                        $formattedDate = $updatedAt;
                        break;
                }

                fputcsv($file, [
                    $row->account_name,
                    $row->account_number,
                    str_pad((string) $row->zip_code, 5, '0', STR_PAD_LEFT), // Add single quote to preserve leading zeros
                    $formattedDate,
                    $row->email,
                    $row->optin_emergency_email ? 'yes' : '',
                    $row->home_phone,
                    $row->optin_home_call ? 'yes' : '',
                    $row->work_phone,
                    $row->optin_work_call ? 'yes' : '',
                    $row->cell_phone,
                    $row->optin_cell_call ? 'yes' : '',
                    $row->optin_cell_sms ? 'yes' : '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Manually purge contact info (phones, email, opt-ins) for direct_alert
     * rows that have already been exported and fall within the given
     * exported_at range. account_number/account_name/zip_code are left
     * intact so citizens can keep verifying and re-registering their contact
     * info. Not automatic/scheduled - an admin runs this on demand, after
     * downloading and verifying an export.
     *
     * @return RedirectResponse
     */
    public function purgeExportedContactInfo(Request $request)
    {
        $auditService = new AdminAuditLogService;

        $user = Auth::user();
        if (! $user || $user->role !== 'admin') {
            $auditService->log(
                action: 'purge',
                wasSuccessful: false,
                errorMessage: 'Unauthorized access attempt'
            );

            return redirect()->back()->with('error', 'Unauthorized.');
        }

        try {
            $startDate = Carbon::parse($request->input('purge_start'));
            $endDate = Carbon::parse($request->input('purge_end'));

            if ($startDate->greaterThan($endDate)) {
                $auditService->log(
                    action: 'purge',
                    wasSuccessful: false,
                    errorMessage: 'Invalid date range: start date is after end date'
                );

                return redirect()->back()->with('error', 'Invalid date range: start date is after end date.');
            }
        } catch (\Exception $e) {
            $auditService->log(
                action: 'purge',
                wasSuccessful: false,
                errorMessage: 'Invalid or missing date parameters: '.$e->getMessage()
            );

            return redirect()->back()->with('error', 'Invalid or missing dates. Please provide a valid range.');
        }

        $recordCount = DB::table('direct_alert')
            ->whereNotNull('exported_at')
            ->whereBetween('exported_at', [$startDate, $endDate->endOfDay()])
            ->update([
                'cell_phone' => null,
                'home_phone' => null,
                'work_phone' => null,
                'alternate_phone' => null,
                'email' => null,
                'optin_cell_sms' => null,
                'optin_cell_call' => null,
                'optin_home_call' => null,
                'optin_work_call' => null,
                'optin_emergency_email' => null,
                'optin_email' => null,
            ]);

        $auditService->log(
            action: 'purge',
            wasSuccessful: true,
            recordsAffected: $recordCount
        );

        return redirect()->back()->with('success', "Purged contact info for {$recordCount} already-exported record(s).");
    }
}
