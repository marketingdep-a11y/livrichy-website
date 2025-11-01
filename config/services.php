<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'import_json' => [
        'url' => env('IMPORT_JSON_URL'),
        'timeout' => env('IMPORT_JSON_TIMEOUT', 10),
        'retries' => env('IMPORT_JSON_RETRIES', 3),
        'backoff' => env('IMPORT_JSON_BACKOFF', 2),
        'store' => env('IMPORT_JSON_STORE', 'imports'),
        'collection' => env('IMPORT_JSON_COLLECTION'),
        'collection_handle' => env('IMPORT_JSON_COLLECTION_HANDLE', 'properties'),
        'id_key' => env('IMPORT_JSON_ID_KEY', 'id'),
        'required_fields' => array_filter(array_map('trim', explode(',', env('IMPORT_JSON_REQUIRED_FIELDS', '')))),
        'status_key' => env('IMPORT_JSON_STATUS_KEY'),
        'active_statuses' => array_filter(array_map('trim', explode(',', env('IMPORT_JSON_ACTIVE_STATUSES', '')))),
        'website_enabled_key' => env('IMPORT_JSON_WEBSITE_ENABLE_KEY'),
        'website_enabled_values' => array_filter(array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            explode(',', env('IMPORT_JSON_WEBSITE_ENABLE_ON_VALUES', 'Y'))
        )),
    ],

    'crm_agents' => [
        'url' => env('CRM_AGENTS_URL'),
        'timeout' => env('CRM_AGENTS_TIMEOUT', 10),
        'departments' => array_values(array_filter(array_map(
            static fn ($value) => (int) trim((string) $value),
            explode(',', env('CRM_AGENTS_ALLOWED_DEPARTMENTS', '52,24,43,51,38'))
        ))),
    ],

    'google_sheets_agents' => [
        'spreadsheet_id' => env('GOOGLE_SHEETS_AGENTS_SPREADSHEET_ID'),
        'range' => env('GOOGLE_SHEETS_AGENTS_RANGE', 'Table1!A2:H'),
        'credentials_path' => env('GOOGLE_SHEETS_AGENTS_CREDENTIALS_PATH', storage_path('app/google-credentials.json')),
    ],


];
