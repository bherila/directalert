<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * Encryption and blind-index helpers for direct_alert PII columns, shared
 * between the Eloquent casts (single-row read/write) and the bulk
 * import/backfill paths (which write pre-encrypted values directly via
 * query builder inserts, bypassing Eloquent casts for performance).
 */
class DirectAlertCrypto
{
    public static function blindIndex(string $accountNumber): string
    {
        return hash_hmac('sha256', $accountNumber, self::pepper());
    }

    /**
     * Plain (unbound) string encryption - used for account_number and the
     * other PII fields (phones, email) that don't need AAD-style binding.
     */
    public static function encryptString(string $value): string
    {
        return Crypt::encryptString($value);
    }

    public static function decryptString(string $ciphertext): string
    {
        return Crypt::decryptString($ciphertext);
    }

    public static function encryptAccountNumber(string $accountNumber): string
    {
        return self::encryptString($accountNumber);
    }

    public static function decryptAccountNumber(string $ciphertext): string
    {
        return self::decryptString($ciphertext);
    }

    /**
     * Encrypt account_name bound to its row's account_number, so a ciphertext
     * copied into a different row fails to decrypt cleanly instead of
     * silently returning a different account's name.
     */
    public static function encryptBoundName(string $accountNumber, string $accountName): string
    {
        return Crypt::encryptString($accountNumber.'|'.$accountName);
    }

    /**
     * Decrypt an account_name ciphertext, verifying it was encrypted for the
     * given account_number.
     */
    public static function decryptBoundName(string $ciphertext, string $expectedAccountNumber): string
    {
        $decrypted = Crypt::decryptString($ciphertext);

        [$boundAccountNumber, $name] = array_pad(explode('|', $decrypted, 2), 2, null);

        if ($name === null || ! hash_equals($expectedAccountNumber, $boundAccountNumber)) {
            throw new RuntimeException('account_name ciphertext is not bound to the expected account_number - possible tampering or row mismatch.');
        }

        return $name;
    }

    private static function pepper(): string
    {
        $pepper = config('direct-alert.blind_index_pepper');

        if (! is_string($pepper) || $pepper === '') {
            throw new RuntimeException('DIRECT_ALERT_BLIND_INDEX_PEPPER is not configured.');
        }

        return $pepper;
    }
}
