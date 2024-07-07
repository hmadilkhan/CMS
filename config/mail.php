<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover"
    |
    */

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    // 'from' => [
    //     'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    //     'name' => env('MAIL_FROM_NAME', 'Example'),
    // ],

    'mailers' => [
        // This is the default configuration
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME','dealreview@testsolencrm.com'),
            'password' => env('MAIL_PASSWORD','Deal@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'dealreview@testsolencrm.com'),
                'name' => env('MAIL_FROM_NAME', 'Solen Energy Co - Deal Review'),
            ],
        ],

        // This is the second configuration, used to send info messages
        'info' => [
            'transport' => 'smtp',
            'host' => env('INFO_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('INFO_MAIL_PORT', 587),
            'encryption' => env('INFO_MAIL_ENCRYPTION', 'tls'),
            'username' => env('INFO_MAIL_USERNAME','sitesurvey@testsolencrm.com'),
            'password' => env('INFO_MAIL_PASSWORD','Site@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('INFO_MAIL_FROM_ADDRESS', 'sitesurvey@testsolencrm.com'),
                'name' => env('INFO_MAIL_FROM_NAME', 'Site Survey'),
            ],
        ],

        'dealreview' => [
            'transport' => 'smtp',
            'host' => env('DEALREVIEW_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('DEALREVIEW_MAIL_PORT', 587),
            'encryption' => env('DEALREVIEW_MAIL_ENCRYPTION', 'tls'),
            'username' => env('DEALREVIEW_MAIL_USERNAME','dealreview@solaroperations.info'),
            'password' => env('DEALREVIEW_MAIL_PASSWORD','Deal@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('DEALREVIEW_MAIL_FROM_ADDRESS', 'dealreview@solaroperations.info'),
                'name' => env('DEALREVIEW_MAIL_FROM_NAME', 'Deal Review'),
            ],
        ],

        'sitesurvey' => [
            'transport' => 'smtp',
            'host' => env('SITESURVEY_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('SITESURVEY_MAIL_PORT', 587),
            'encryption' => env('SITESURVEY_MAIL_ENCRYPTION', 'tls'),
            'username' => env('SITESURVEY_MAIL_USERNAME','sitesurvey@solaroperations.info'),
            'password' => env('SITESURVEY_MAIL_PASSWORD','Site@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('SITESURVEY_MAIL_FROM_ADDRESS', 'sitesurvey@solaroperations.info'),
                'name' => env('SITESURVEY_MAIL_FROM_NAME', 'Site Survey'),
            ],
        ],

        'engineering' => [
            'transport' => 'smtp',
            'host' => env('ENGINEERING_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('ENGINEERING_MAIL_PORT', 587),
            'encryption' => env('ENGINEERING_MAIL_ENCRYPTION', 'tls'),
            'username' => env('ENGINEERING_MAIL_USERNAME','engineering@solaroperations.info'),
            'password' => env('ENGINEERING_MAIL_PASSWORD','Engr@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('ENGINEERING_MAIL_FROM_ADDRESS', 'engineering@solaroperations.info'),
                'name' => env('ENGINEERING_MAIL_FROM_NAME', 'Engineering'),
            ],
        ],

        'permitting' => [
            'transport' => 'smtp',
            'host' => env('PERMITTING_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('PERMITTING_MAIL_PORT', 587),
            'encryption' => env('PERMITTING_MAIL_ENCRYPTION', 'tls'),
            'username' => env('PERMITTING_MAIL_USERNAME','permitting@solaroperations.info'),
            'password' => env('PERMITTING_MAIL_PASSWORD','Permit@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('PERMITTING_MAIL_FROM_ADDRESS', 'permitting@solaroperations.info'),
                'name' => env('PERMITTING_MAIL_FROM_NAME', 'Permitting'),
            ],
        ],

        'installation' => [
            'transport' => 'smtp',
            'host' => env('INSTALLATION_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('INSTALLATION_MAIL_PORT', 587),
            'encryption' => env('INSTALLATION_MAIL_ENCRYPTION', 'tls'),
            'username' => env('INSTALLATION_MAIL_USERNAME','installation@solaroperations.info'),
            'password' => env('INSTALLATION_MAIL_PASSWORD','Install@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('INSTALLATION_MAIL_FROM_ADDRESS', 'installation@solaroperations.info'),
                'name' => env('INSTALLATION_MAIL_FROM_NAME', 'Installation'),
            ],
        ],

        'inspection' => [
            'transport' => 'smtp',
            'host' => env('INSPECTION_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('INSPECTION_MAIL_PORT', 587),
            'encryption' => env('INSPECTION_MAIL_ENCRYPTION', 'tls'),
            'username' => env('INSPECTION_MAIL_USERNAME','inspection@solaroperations.info'),
            'password' => env('INSPECTION_MAIL_PASSWORD','Insp@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('INSPECTION_MAIL_FROM_ADDRESS', 'inspection@solaroperations.info'),
                'name' => env('INSPECTION_MAIL_FROM_NAME', 'Inspection'),
            ],
        ],

        'pto' => [
            'transport' => 'smtp',
            'host' => env('PTO_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('PTO_MAIL_PORT', 587),
            'encryption' => env('PTO_MAIL_ENCRYPTION', 'tls'),
            'username' => env('PTO_MAIL_USERNAME','pto@solaroperations.info'),
            'password' => env('PTO_MAIL_PASSWORD','Pto@@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('PTO_MAIL_FROM_ADDRESS', 'pto@solaroperations.info'),
                'name' => env('PTO_MAIL_FROM_NAME', 'PTO'),
            ],
        ],

        'coc' => [
            'transport' => 'smtp',
            'host' => env('COC_MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('COC_MAIL_PORT', 587),
            'encryption' => env('COC_MAIL_ENCRYPTION', 'tls'),
            'username' => env('COC_MAIL_USERNAME','coc@solaroperations.info'),
            'password' => env('COC_MAIL_PASSWORD','Coc@@247'),
            'timeout' => null,
            'auth_mode' => null,
            'from' => [
                'address' => env('COC_MAIL_FROM_ADDRESS', 'coc@solaroperations.info'),
                'name' => env('OOC_MAIL_FROM_NAME', 'COC'),
            ],
        ],
        // add more configurations if needed
    ],


    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
