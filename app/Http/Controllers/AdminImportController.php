<?php

namespace App\Http\Controllers;

use App\Models\DirectAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminImportController extends Controller
{
    /**
     * Show the admin import page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.import');
    }

    /**
     * Handle the CSV file upload and import.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        $file = $request->file('csv_file');
        $imported = [];
        $duplicates = [];
        $failed = [];

        // Read CSV file with proper quote handling
        $handle = fopen($file->getPathname(), 'r');
        $delimiter = ',';
        $enclosure = '"';
        $escape = '\\';

        // Read and validate header row
        $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
        if ($headers === false) {
            fclose($handle);
            return back()
                ->withErrors(['csv_file' => 'Failed to read CSV header row'])
                ->withInput();
        }

        // Trim whitespace from headers
        $headers = array_map('trim', $headers);
        $requiredColumns = ['PERSON', 'ACCT_ID', 'POSTAL'];

        // Check for missing columns
        $missingColumns = array_diff($requiredColumns, $headers);
        if (!empty($missingColumns)) {
            fclose($handle);
            return back()
                ->withErrors(['csv_file' => 'Missing required columns: ' . implode(', ', $missingColumns)])
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
            if (empty(array_filter($row, fn($cell) => trim($cell) !== ''))) {
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

        // Extract account numbers for duplicate checking
        $accountNumbers = array_column($data, 'account_number');
        $duplicates = [];
        $batchSize = 100;

        // Check for duplicates in batches
        foreach (array_chunk($accountNumbers, $batchSize) as $batch) {
            $existingAccounts = DirectAlert::whereIn('account_number', $batch)->pluck('account_number')->toArray();
            foreach ($data as $row) {
                if (in_array($row['account_number'], $existingAccounts)) {
                    $duplicates[] = $row; // Store the full data item
                }
            }
        }

        // Filter out duplicates from the data array
        $data = array_filter($data, fn($row) => !in_array($row['account_number'], array_column($duplicates, 'account_number')));

        // Validate and prepare data for bulk insert
        $imported = [];
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
                    'error' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                ];
                continue;
            }

            $imported[] = [
                'account_name' => $row['account_name'],
                'account_number' => $row['account_number'],
                'zip_code' => $row['zip_code'],
            ];
        }

        // Perform bulk insert for valid rows
        if (!empty($imported)) {
            DirectAlert::insert($imported);
        }

        // Return the results view
        return view('admin.import-results', compact('imported', 'duplicates', 'failed'));
    }
}