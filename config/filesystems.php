<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        /*
        | 'serve' registers GET /storage/{path} — with no middleware whatsoever
        | — to stream this disk's contents. Nothing in this application calls
        | Storage::disk('local')->url(), so the route bought nothing and only
        | offered an unauthenticated way into storage/app/private.
        */
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | private
        |----------------------------------------------------------------------
        |
        | Everything an applicant uploads: payment receipts, portfolio evidence,
        | answer scripts, and the assessment papers evaluators publish.
        |
        | These used to go on the 'public' disk and were linked with
        | asset('storage/...'), which put them on an unauthenticated URL. Every
        | ownership check in the controllers was bypassable by anyone holding
        | the link — from a Referer header, a shared screenshot or browser
        | history. There is no public URL for this disk; files are served only
        | through the /files/{...} route, which re-runs the same authorisation
        | as the page that links to them.
        |
        | The root is deliberately NOT storage/app/private: Laravel's default
        | 'local' disk points there with 'serve' => true, which registers an
        | unauthenticated GET /storage/{path} route over the whole directory.
        | Sharing that root would have handed out every file this disk holds.
        |
        */
        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/secure'),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
