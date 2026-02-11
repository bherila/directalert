<?php

namespace App\Http\Controllers;

use App\Models\DirectAlert; // Import the DirectAlert model
use App\Services\AdminAuditLogService;
use App\Mail\AdminOperationNotification;
use Carbon\Carbon; // Import Carbon for date handling
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Response; // Import Response facade
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DirectAlertDumpController extends Controller
{
    /**
     * Dump DirectAlert data as CSV within a date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dumpCsv(Request $request)
    {
        $auditService = new AdminAuditLogService();
        
        // Check if the user is authenticated and has the admin role
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            $auditService->log(
                action: 'export',
                wasSuccessful: false,
                errorMessage: 'Unauthorized access attempt'
            );
            return Response::make('Unauthorized: ' . json_encode($user), 401);
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
                errorMessage: 'Invalid or missing date parameters: ' . $e->getMessage()
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
            Log::error('Failed to send export notification email: ' . $e->getMessage());
        }

        // Generate CSV content
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="direct_alert_dump_' . $startDate->format('Ymd') . '_to_' . $endDate->format('Ymd') . '.csv"',
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
                'wantCellSMS'
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
                    $row->optin_email ? 'yes' : '',
                    $row->home_phone,
                    $row->optin_home_call ? 'yes' : '',
                    $row->work_phone,
                    $row->optin_work_call ? 'yes' : '',
                    $row->cell_phone,
                    $row->optin_cell_call ? 'yes' : '',
                    $row->optin_cell_sms ? 'yes' : ''
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
