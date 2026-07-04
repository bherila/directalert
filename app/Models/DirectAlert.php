<?php

namespace App\Models;

use App\Casts\AccountBoundEncrypted;
use App\Support\DirectAlertCrypto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectAlert extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'direct_alert';

    /**
     * The attributes that are mass assignable.
     *
     * Order matters: account_number must be assigned before account_name so
     * the AccountBoundEncrypted cast has account_number available when it
     * encrypts account_name.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_number',
        'account_name',
        'zip_code',
        'cell_phone',
        'home_phone',
        'work_phone',
        'email',
        'optin_cell_sms',
        'optin_cell_call',
        'optin_home_call',
        'optin_work_call',
        'optin_emergency_email',
        'optin_email',
        'alternate_phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'account_number' => 'encrypted',
        'account_name' => AccountBoundEncrypted::class,
        'cell_phone' => 'encrypted',
        'home_phone' => 'encrypted',
        'work_phone' => 'encrypted',
        'alternate_phone' => 'encrypted',
        'email' => 'encrypted',
        'optin_cell_sms' => 'datetime',
        'optin_cell_call' => 'datetime',
        'optin_home_call' => 'datetime',
        'optin_work_call' => 'datetime',
        'optin_emergency_email' => 'datetime',
        'optin_email' => 'datetime',
        'exported_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (DirectAlert $account) {
            if ($account->isDirty('account_number')) {
                $account->account_number_hash = DirectAlertCrypto::blindIndex($account->account_number);
            }
        });
    }
}
