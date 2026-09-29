<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blind Index Pepper
    |--------------------------------------------------------------------------
    |
    | A secret, separate from APP_KEY, used to compute a deterministic HMAC
    | of account_number for exact-match lookups against the encrypted
    | direct_alert table. Kept separate from APP_KEY so that rotating the
    | encryption key doesn't also invalidate every blind-index lookup.
    |
    */

    'blind_index_pepper' => env('DIRECT_ALERT_BLIND_INDEX_PEPPER'),

    /*
    |--------------------------------------------------------------------------
    | Contact Info Retention
    |--------------------------------------------------------------------------
    |
    | How many days after being exported a direct_alert row's contact info
    | (phone numbers, email, opt-ins) is eligible for purging. Not currently
    | enforced automatically anywhere - purging is a manual, admin-triggered
    | action - this just holds the configured value for that action to use.
    |
    */

    'contact_retention_days' => (int) env('DIRECT_ALERT_CONTACT_RETENTION_DAYS', 30),

];
