<?php

namespace App\Http\Controllers;

use App\Models\DirectAlert;
use App\Support\DirectAlertCrypto;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /**
     * Show the account verification form.
     *
     * @return View
     */
    public function showVerificationForm()
    {
        return view('welcome');
    }

    /**
     * Look up an account by its (plaintext) account_number via the blind index.
     */
    private function findByAccountNumber(string $accountNumber): ?DirectAlert
    {
        return DirectAlert::where('account_number_hash', DirectAlertCrypto::blindIndex($accountNumber))->first();
    }

    /**
     * Verify the account number and last name.
     *
     * @return RedirectResponse
     */
    public function verifyAccount(Request $request)
    {
        // Validate the request data
        $request->validate([
            'account_number' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $accountNumber = trim($request->input('account_number'));
        $lastName = trim($request->input('last_name'));

        // account_number is encrypted, so we look up via its deterministic
        // blind-index hash, then compare the decrypted account_name in PHP
        // (case-insensitive) - either an exact match, or the last-name part
        // up to the comma (account_name is stored as "LAST, FIRST").
        $account = $this->findByAccountNumber($accountNumber);
        $normalizedLastName = mb_strtoupper($lastName);

        $matches = $account && (
            mb_strtoupper($account->account_name) === $normalizedLastName
            || str_starts_with(mb_strtoupper($account->account_name), $normalizedLastName.',')
        );

        if ($matches) {
            // Account found, save account_number and full account_name to a cookie
            $cookie = Cookie::make('current_account', json_encode([
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
            ]), 60); // Cookie valid for 60 minutes

            // Redirect to the update information page with the cookie
            return Redirect::to('/update-information')->withCookie($cookie);

        } else {
            // Account not found, redirect back with an error message
            return Redirect::back()->withInput()->with('error', "Couldn't verify your information. Please check your information and submit again");
        }
    }

    /**
     * Show the contact information update form.
     *
     * @return View|RedirectResponse
     */
    public function showUpdateInformationForm(Request $request)
    {
        $accountData = json_decode($request->cookie('current_account'), true);

        if (! $accountData || ! isset($accountData['account_number']) || ! isset($accountData['account_name'])) {
            // Cookie is missing or invalid, redirect back to verification
            return Redirect::to('/')->with('error', 'Please verify your account information to proceed.');
        }

        $account = $this->findByAccountNumber($accountData['account_number']);

        if (! $account || $account->account_name !== $accountData['account_name']) {
            // Account not found in the database, redirect back to verification
            return Redirect::to('/')->with('error', 'Account not found. Please verify your information again.');
        }

        return view('update-information', compact('account'));
    }

    /**
     * Update the contact information for the account.
     *
     * @return RedirectResponse
     */
    public function updateInformation(Request $request)
    {
        $accountData = json_decode($request->cookie('current_account'), true);

        if (! $accountData || ! isset($accountData['account_number']) || ! isset($accountData['account_name'])) {
            // Cookie is missing or invalid, redirect back to verification
            return Redirect::to('/')->with('error', 'Please verify your account information to proceed.');
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'home_phone' => 'nullable', // Basic US phone format (e.g., (123) 456-7890 or 1234567890)
            'work_phone' => 'nullable',
            'cell_phone' => 'nullable',
            'optin_emergency_email' => 'nullable|boolean',
            'optin_home_call' => 'nullable|boolean',
            'optin_work_call' => 'nullable|boolean',
            'optin_cell_call' => 'nullable|boolean',
            'optin_cell_sms' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $account = $this->findByAccountNumber($accountData['account_number']);

        if (! $account || $account->account_name !== $accountData['account_name']) {
            // Account not found in the database, redirect back to verification
            return Redirect::to('/')->with('error', 'Account not found. Please verify your information again.');
        }

        // Check if any of the fields have changed
        $fieldsToCheck = [
            'email',
            'home_phone',
            'work_phone',
            'cell_phone',
        ];

        $hasChanges = false;

        foreach ($fieldsToCheck as $field) {
            if ($request->input($field) != $account->$field) {
                $hasChanges = true;
                break;
            }
        }

        // Check opt-in fields for changes between null and non-null
        if (! $hasChanges) {
            $optinFields = [
                'optin_emergency_email',
                'optin_home_call',
                'optin_work_call',
                'optin_cell_call',
                'optin_cell_sms',
            ];

            foreach ($optinFields as $field) {
                $currentValue = $account->$field;
                $newValue = $request->has($field) ? Carbon::now() : null;

                if (is_null($currentValue) !== is_null($newValue)) {
                    $hasChanges = true;
                    break;
                }
            }
        }

        if ($hasChanges) {
            // Copy current record to history
            $historyRecord = $account->replicate(['exported_at']);
            $historyRecord->setTable('direct_alert_history'); // Set the table name for the history model
            $historyRecord->save();

            // Update the existing record
            $account->email = $request->input('email');
            $account->home_phone = $request->input('home_phone');
            $account->work_phone = $request->input('work_phone');
            $account->cell_phone = $request->input('cell_phone');

            // Update opt-in timestamps
            $account->optin_emergency_email = $request->has('optin_emergency_email') ? Carbon::now() : null;
            $account->optin_home_call = $request->has('optin_home_call') ? Carbon::now() : null;
            $account->optin_work_call = $request->has('optin_work_call') ? Carbon::now() : null;
            $account->optin_cell_call = $request->has('optin_cell_call') ? Carbon::now() : null;
            $account->optin_cell_sms = $request->has('optin_cell_sms') ? Carbon::now() : null;

            // This contact info has never been exported - clear any stale
            // exported_at so a later purge (scoped to already-exported rows)
            // can't wipe data the vendor never actually received.
            $account->exported_at = null;
        }

        $account->save();

        // Redirect to the thank you page
        return Redirect::to('/thanks');
    }

    /**
     * Show the thank you page and delete the cookie.
     *
     * @return View|RedirectResponse
     */
    public function showThanksPage()
    {
        // Delete the cookie
        $cookie = Cookie::forget('current_account');

        // Return the thank you view with the cookie to be deleted
        return response()->view('thanks')->withCookie($cookie);
    }
}
