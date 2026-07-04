<?php

namespace App\Http\Controllers;

use App\Mail\AdminOperationNotification;
use App\Models\DirectAlert;
use App\Services\AdminAuditLogService;
use App\Support\DirectAlertCrypto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminImportController extends Controller
{
    /**
     * Show the admin import page.
     *
     * @return View
     */
    public function index()
    {
        $auditService = new AdminAuditLogService;
        $importHistory = $auditService->getImportHistory(20);

        return view('admin.import', compact('importHistory'));
    }

    /**
     * Handle the CSV file upload and import.
     *
     * @return View|RedirectResponse
     */
    public function import(Request $request)
    {
        $auditService = new AdminAuditLogService;

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $imported = [];
        $duplicates = [];
        $failed = [];

        try {
            // Read CSV file with proper quote handling
            $handle = fopen($file->getPathname(), 'r');
            $delimiter = ',';
            $enclosure = '"';
            $escape = '\\';

            // Read and validate header row
            $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
            if ($headers === false) {
                fclose($handle);

                $auditService->log(
                    action: 'import',
                    wasSuccessful: false,
                    errorMessage: 'Failed to read CSV header row'
                );

                return back()
                    ->withErrors(['csv_file' => 'Failed to read CSV header row'])
                    ->withInput();
            }

            // Trim whitespace from headers
            $headers = array_map('trim', $headers);
            $requiredColumns = ['PERSON', 'ACCT_ID', 'POSTAL'];

            // Check for missing columns
            $missingColumns = array_diff($requiredColumns, $headers);
            if (! empty($missingColumns)) {
                fclose($handle);

                $errorMessage = 'Missing required columns: '.implode(', ', $missingColumns);
                $auditService->log(
                    action: 'import',
                    wasSuccessful: false,
                    errorMessage: $errorMessage
                );

                return back()
                    ->withErrors(['csv_file' => $errorMessage])
                    ->withInput();
            }

            // Create a map of CSV columns to database fields
            $headerMap = array_flip($headers);

            // Parse the CSV file into memory
            $data = [];
            $rowNumber = 1; // Start after header row
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                $rowNumber++;
                $row = array_map('trim', $row);

                // Skip empty rows
                if (empty(array_filter($row, fn ($cell) => trim($cell) !== ''))) {
                    continue;
                }

                $data[] = [
                    'row_number' => $rowNumber,
                    'account_name' => $row[$headerMap['PERSON']] ?? null,
                    'account_number' => $row[$headerMap['ACCT_ID']] ?? null,
                    'zip_code' => $row[$headerMap['POSTAL']] ?? null,
                    'raw_data' => implode(', ', $row),
                ];
            }
            fclose($handle);

            // account_number is stored encrypted, so duplicate detection has to
            // go through its deterministic blind-index hash rather than the
            // plaintext value.
            foreach ($data as &$row) {
                $row['account_number_hash'] = $row['account_number'] !== null
                    ? DirectAlertCrypto::blindIndex($row['account_number'])
                    : null;
            }
            unset($row);

            $accountNumberHashes = array_filter(array_column($data, 'account_number_hash'));
            $duplicates = [];
            $batchSize = 100;

            // Check for duplicates in batches
            foreach (array_chunk($accountNumberHashes, $batchSize) as $batch) {
                $existingHashes = DirectAlert::whereIn('account_number_hash', $batch)->pluck('account_number_hash')->toArray();
                foreach ($data as $row) {
                    if ($row['account_number_hash'] !== null && in_array($row['account_number_hash'], $existingHashes)) {
                        $duplicates[] = $row; // Store the full data item
                    }
                }
            }

            // Filter out duplicates from the data array
            $duplicateHashes = array_column($duplicates, 'account_number_hash');
            $data = array_filter($data, fn ($row) => ! in_array($row['account_number_hash'], $duplicateHashes));

            // Validate and prepare data for bulk insert. $imported stays
            // plaintext (it's rendered back to the admin on the results page),
            // $insertRows holds the pre-encrypted values actually written to
            // the database.
            $imported = [];
            $insertRows = [];
            $failed = [];
            foreach ($data as $row) {
                $validator = Validator::make($row, [
                    'account_name' => 'required|string|max:255',
                    'account_number' => 'required|string|max:255',
                    'zip_code' => 'required|string|max:10',
                ]);

                if ($validator->fails()) {
                    $failed[] = [
                        'row' => $row['row_number'],
                        'data' => $row['raw_data'],
                        'error' => 'Validation failed: '.implode(', ', $validator->errors()->all()),
                    ];

                    continue;
                }

                $imported[] = [
                    'account_name' => $row['account_name'],
                    'account_number' => $row['account_number'],
                    'zip_code' => $row['zip_code'],
                ];

                $insertRows[] = [
                    'account_name' => DirectAlertCrypto::encryptBoundName($row['account_number'], $row['account_name']),
                    'account_number' => DirectAlertCrypto::encryptAccountNumber($row['account_number']),
                    'account_number_hash' => $row['account_number_hash'],
                    'zip_code' => $row['zip_code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Perform bulk insert for valid rows (pre-encrypted, so this bypasses
            // Eloquent casts/events for bulk-insert performance while producing
            // the exact same stored format DirectAlertCrypto/AccountBoundEncrypted
            // would - see DirectAlertCrypto for the shared logic).
            if (! empty($insertRows)) {
                DirectAlert::insert($insertRows);
            }

            // Log successful import
            $auditService->log(
                action: 'import',
                wasSuccessful: true,
                recordsAffected: count($imported),
                recordsSkipped: count($duplicates),
                recordsFailed: count($failed)
            );

            // Send email notification (don't fail if email fails)
            try {
                $user = Auth::user();
                Mail::to('ben@herila.net')->send(new AdminOperationNotification(
                    operation: 'import',
                    wasSuccessful: true,
                    recordsAffected: count($imported),
                    recordsSkipped: count($duplicates),
                    recordsFailed: count($failed),
                    userName: $user->name ?? $user->email
                ));
            } catch (\Exception $e) {
                // Log the error but don't fail the operation
                Log::error('Failed to send import notification email: '.$e->getMessage());
            }

            // Return the results view
            return view('admin.import-results', compact('imported', 'duplicates', 'failed'));

        } catch (\Exception $e) {
            // Log failed import
            $auditService->log(
                action: 'import',
                wasSuccessful: false,
                errorMessage: $e->getMessage()
            );

            return back()
                ->withErrors(['csv_file' => 'Import failed: '.$e->getMessage()])
                ->withInput();
        }
    }
}
