<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SchoolShare — Application Configuration
    | Developed by Mahi Zeki Mukhtar / EthioNext (ethionext.com.et)
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    |
    | max_file_mb:    Maximum size of a single uploaded file (in megabytes)
    | max_storage_gb: Total storage quota per user (in gigabytes)
    |
    */
    'max_file_mb'    => (int) env('SCHOOLSHARE_MAX_FILE_MB', 100),
    'max_storage_gb' => (float) env('SCHOOLSHARE_MAX_STORAGE_GB', 1),

    /*
    |--------------------------------------------------------------------------
    | Computed Byte Limits
    |--------------------------------------------------------------------------
    */
    'max_file_bytes'    => (int) env('SCHOOLSHARE_MAX_FILE_MB', 100) * 1024 * 1024,
    'max_storage_bytes' => (float) env('SCHOOLSHARE_MAX_STORAGE_GB', 1) * 1024 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Free Plan Limits
    |--------------------------------------------------------------------------
    */
    'free_plan' => [
        'max_projects'      => 3,
        'max_collaborators' => 5,
        'storage_gb'        => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pro Plan Limits
    |--------------------------------------------------------------------------
    */
    'pro_plan' => [
        'max_projects'      => 20,
        'max_collaborators' => 20,
        'storage_gb'        => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding Configuration
    |
    | mode: "community" — EthioNext branding is displayed (required by license)
    |        "whitelabel" — branding hidden (requires valid Commercial License key)
    |
    | IMPORTANT: Changing this to "whitelabel" without a valid license key
    | is a violation of the SchoolShare Community License (SCL v1.0).
    | Purchase a license at: https://ethionext.com.et/pricing
    | Contact: mahizeki037@gmail.com | +251 992 194 042
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'mode'         => env('SCHOOLSHARE_BRANDING_MODE', 'community'),
        'license_key'  => env('SCHOOLSHARE_LICENSE_KEY', null),
        'app_name'     => 'SchoolShare',
        'company_name' => 'EthioNext',
        'company_url'  => 'https://ethionext.com.et',
        'developer'    => 'Mahi Zeki Mukhtar',
        'contact_email'=> 'mahizeki037@gmail.com',
        'contact_phone'=> '+251 992 194 042',
        'github_url'   => 'https://github.com/mz-mukhtar/School-Share',
        'footer_text'  => 'SchoolShare by EthioNext · ethionext.com.et',
        'header_value' => 'EthioNext-SchoolShare', // X-Powered-By header value
        'zip_notice'   => 'SCHOOLSHARE_BY_ETHIONEXT.txt', // filename in ZIP downloads
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Tags
    |
    | Available subject tags for projects.
    |--------------------------------------------------------------------------
    */
    'subject_tags' => [
        'Mathematics',
        'English',
        'Science',
        'Biology',
        'Chemistry',
        'Physics',
        'History',
        'Geography',
        'Economics',
        'Civics',
        'Art & Design',
        'Music',
        'Computer Science',
        'Physical Education',
        'Amharic',
        'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Viewer Configuration
    |--------------------------------------------------------------------------
    |
    | Maps MIME type patterns to viewer types used in FileViewController.
    |
    */
    'viewer_types' => [
        // CodeMirror text/code viewer (editable)
        'codemirror' => [
            'text/plain', 'text/html', 'text/css', 'text/javascript',
            'text/markdown', 'text/xml', 'application/json',
            'application/javascript', 'application/x-php',
            'application/x-python', 'text/x-python', 'text/x-php',
        ],

        // PDF.js viewer
        'pdfjs' => [
            'application/pdf',
        ],

        // Google Docs Viewer (Office files)
        'google_docs' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
        ],

        // Native image viewer
        'image' => [
            'image/jpeg', 'image/png', 'image/gif',
            'image/webp', 'image/svg+xml', 'image/bmp',
        ],

        // HTML5 media player
        'media' => [
            'video/mp4', 'video/webm', 'video/ogg',
            'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings (in seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'checkpoint_list' => 300,   // 5 minutes
        'project_stats'   => 300,   // 5 minutes
        'explore_page'    => 600,   // 10 minutes
        'user_storage'    => 60,    // 1 minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'upload_per_minute'    => 10,
        'download_per_minute'  => 30,
        'file_view_per_minute' => 60,
        'login_per_minute'     => 5,
    ],

];
