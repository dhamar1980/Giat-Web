<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | ID proyek Firebase dari Firebase Console (Project Settings -> General).
    | Contoh: "giat-app-production" atau "giat-health-312"
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Web API Key
    |--------------------------------------------------------------------------
    |
    | Web API Key dari Firebase Console (Project Settings -> General -> Web API Key).
    | Digunakan untuk otentikasi REST API (Native Email/Password Sign-In, Sign-Up, dll).
    |
    */
    'api_key' => env('FIREBASE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Credentials File
    |--------------------------------------------------------------------------
    |
    | Lokasi file JSON Service Account Firebase jika Anda mendownloadnya
    | dari Firebase Console -> Project Settings -> Service Accounts.
    | Contoh: storage_path('app/firebase/service-account.json')
    |
    */
    'credentials_file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Direct Credentials (Optional)
    |--------------------------------------------------------------------------
    |
    | Alternatif file JSON: kredensial service account langsung via environment variables.
    |
    */
    'client_email' => env('FIREBASE_CLIENT_EMAIL', ''),
    'private_key' => env('FIREBASE_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Auth Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi default role saat pengguna mendaftar melalui Firebase / Google.
    |
    */
    'default_role' => env('FIREBASE_DEFAULT_ROLE', 'pasien'),

    /*
    |--------------------------------------------------------------------------
    | Mock / Development Mode
    |--------------------------------------------------------------------------
    |
    | Jika diaktifkan (true) pada lingkungan local saat API Key belum dipasang,
    | FirebaseService dapat memberikan respons simulasi terstruktur untuk kemudahan testing.
    |
    */
    'dev_mock_allowed' => env('FIREBASE_DEV_MOCK', false),
];
