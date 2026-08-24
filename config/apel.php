<?php

return [
    'default_credit_hours' => 3,

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | When disabled, login completes immediately after the password and captcha
    | checks and no one-time code is generated or emailed. The verification
    | routes stay registered but redirect away, so an old link or a stale open
    | tab cannot strand a user on a dead page.
    |
    | Disabled by default at the project owner's request. Set APEL_2FA_ENABLED
    | to true to restore it - the flow is intact, not removed.
    |
    */
    'two_factor' => [
        'enabled' => env('APEL_2FA_ENABLED', false),

        // Minutes a successful verification is honoured before a code is
        // required again. Only consulted while two_factor.enabled is true.
        'remember_minutes' => env('APEL_2FA_REMEMBER_MINUTES', 30),

        // Minutes a freshly issued code remains valid.
        'code_lifetime_minutes' => env('APEL_2FA_CODE_LIFETIME', 10),
    ],
];
