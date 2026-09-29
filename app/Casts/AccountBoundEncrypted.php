<?php

namespace App\Casts;

use App\Support\DirectAlertCrypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Encrypts an attribute (account_name) bound to its row's account_number,
 * via DirectAlertCrypto. Requires account_number to already be assigned on
 * the model before this attribute is set (mass-assignment call sites must
 * list account_number before account_name).
 *
 * @implements CastsAttributes<string, string>
 */
class AccountBoundEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return DirectAlertCrypto::decryptBoundName($value, (string) $model->account_number);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($model->account_number === null) {
            throw new LogicException("Cannot set {$key}: account_number must be assigned first.");
        }

        return DirectAlertCrypto::encryptBoundName($model->account_number, $value);
    }
}
