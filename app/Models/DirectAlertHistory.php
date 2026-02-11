<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectAlertHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'direct_alert_history';

    /**
     * The attributes that are mass assignable.
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
        'optin_cell_sms' => 'datetime',
        'optin_cell_call' => 'datetime',
        'optin_home_call' => 'datetime',
        'optin_work_call' => 'datetime',
        'optin_emergency_email' => 'datetime',
        'optin_email' => 'datetime',
    ];
}
