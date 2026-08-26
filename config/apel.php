<?php

return [
    'default_credit_hours' => 3,

    /*
    |--------------------------------------------------------------------------
    | APEL A entry rules
    |--------------------------------------------------------------------------
    |
    | These were hard-coded in two places that could disagree: the submission
    | check in Student\ApplicationController and the scorecard in
    | ApelDecisionSupportService. Both now read from here, through
    | App\Domain\Apel\Eligibility.
    |
    | minimum_qualification is a floor, not an exact match. The previous rule
    | required the candidate's highest qualification to literally begin with
    | the word "Diploma", which rejected anyone holding a Bachelor's or higher.
    |
    */
    'eligibility' => [
        'minimum_age' => env('APEL_A_MINIMUM_AGE', 30),
        'minimum_qualification' => env('APEL_A_MINIMUM_QUALIFICATION', 'diploma'),
    ],

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
