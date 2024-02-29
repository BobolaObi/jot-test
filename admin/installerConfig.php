<?php
    # Full Structure of the installer interface
    $installerConfig = [
        "GENERAL" => [
            "title" => "General Settings",
            "tests" => false,
            "items" => [
                "COMPANY_TITLE" => [
                    "title" => 'Site Title',
                    "desc"  => 'Main title for Form Builder. Used in page titles and headers.',
                    "value" => 'MyCompany Forms',
                    "required" => true
                ],
                "COMPANY_LOGO" => [
                    "title" => 'Site Logo',
                    "desc"  => 'Logo of your company (165 x 55). Optional.',
                    "value" => ''
                ],
                "NOREPLY" => [
                    "title" => 'Sender Email Address',
                    "desc"  => '"From" email address on emails.',
                    "value" => 'noreply@mydomain.com',
                    "required" => true
                ],
                "NOREPLY_NAME" => [
                    "title" => 'Sender Email Name',
                    "desc"  => '"From" name on emails.',
                    "value" => 'MyCompany Forms',
                    "required" => true
                ],
                "NOREPLY_SUPPORT" => [
                    "title" => 'Support Email Name',
                    "desc"  => 'Name on registration and lost password emails.',
                    "value" => 'MyCompany',
                    "required" => true
                ]
            ]
        ],
        "DATABASE" => [
            "title" => "Mysql Database Settings",
            "tests" => "checkDB",
            # "hidden" => true,
            "items" => [
                "DBNAME" => [
                    "title" => 'Database Name',
                    "desc"  => 'Mysql Database Name',
                    "value" => 'jotform',
                    "required" => true
                ],
                "PRO_DB_HOST" => [
                    "title" => 'Database Hostname',
                    "desc"  => 'Mysql Database Hostname.',
                    "value" => 'localhost',
                    "required" => true
                ],
                "PRO_DB_USER" => [
                    "title" => 'Database Username',
                    "desc"  => 'Mysql Database Username',
                    "value" => 'root',
                    "required" => true
                ],
                "PRO_DB_PASS" => [
                    "title" => 'Database Password',
                    "desc"  => 'Mysql Database Password',
                    "type"  => 'password',
                    "value" => ''
                ]
            ]
        ],
        "DEVDATABASE" => [
            "title" => "Development Database",
            "tests" => "checkDB",
            "hidden" => true,
            "items" => [
                "DEV_DB_HOST" => [
                    "title" => 'Dev DB Host',
                    "desc"  => 'Database Host Name for development mode',
                    "value" => 'localhost',
                    "required" => true
                ],
                "DEV_DB_USER" => [
                    "title" => 'Dev DB Username',
                    "desc"  => 'Database Username for Development Mode',
                    "value" => 'root',
                    "required" => true
                ],
                "DEV_DB_PASS" => [
                    "title" => 'Dev Database Password',
                    "desc"  => 'Development Mode Database Password',
                    "type"  => 'password',
                    "value" => ''
                ]
            ]
        ],
        "ADMIN" => [
            "title" => "Administration",
            "tests" => "checkPaths",
            "items" => [
                "DEFAULT_USER_TYPE" => [
                    "title" => 'Default User Type',
                    "desc"  => 'Select the default user type for account created.',
                    "dropdown" => ['GUEST', 'FREE', 'PREMIUM', 'PROFESSIONAL', 'ADMIN'],
                    "value" => 'PROFESSIONAL'
                ],
                "CLOUD_UPLOAD_ALIAS" => [
                    "title" => '',
                    "desc"  => '',
                    "value" => '',
                    "hidden" => true
                ],
                "CACHEPATH" => [
                    "title" => 'Cache Folder',
                    "desc"  => 'Specify a folder for jotform form cache files. ',
                    "value" => $root."cache/",
                    "required" => true
                ],
                "UPLOADPATH" => [
                    "title" => 'Upload Folder',
                    "desc"  => 'Specify a folder for jotform uploaded files.',
                    "value" => $root."uploads/",
                    "required" => true
                ],
                "LOGFOLDER" => [
                    "title" => 'Log Folder',
                    "desc"  => 'Specify a folder for jotform logs.',
                    "value" => '/tmp/logs/',
                    "required" => true
                ],
                "ANALYTICS_CODE" => [
                    "title" => 'Analytics Code',
                    "desc"  => 'If you have Google Analytics, put the tracking number here.',
                    "value" => 'UA-XXXXXXX-X'
                ],
                "USECDN" => [
                    "title" => 'Use CDN',
                    "desc"  => 'Use Amazon CloudFront Content Delivery Networks for site files.',
                    "type"  => "checkbox",
                    "value" => false,
                    "hidden"=> true
                ],
                "USEUFS" => [
                    "title" => 'Use UFS',
                    "desc"  => 'Copy uploaded files to Amazon S3 Server.',
                    "type"  => "checkbox",
                    "value" => false,
                    "hidden"=> true
                ]
            ]
        ],
        "ADMIN_USER" => [
            "title" => "Admin User",
            "tests" => "createAdmin",
            "items" => [
                "USERNAME" => [
                    "title" => "Username",
                    "desc"  => "Username for admin account.",
                    "required" => true,
                    "value" => ""
                ],
                "EMAIL" => [
                    "title" => "E-Mail",
                    "desc"  => "E-mail address for admin account",
                    "required" => true,
                    "value" => ""
                ],
                "PASSWORD" => [
                    "title" => "Password",
                    "desc"  => "Password for admin account",
                    "type"  => 'password',
                    "required" => true,
                    "value" => ""
                ]
            ]
        ],
        "LDAP_SETTINGS" => [
            "title" => "LDAP Intergration",
            "tests" => "ldapConfig",
            "items" => [
                "USELDAP" => [
                    "title" => "Enable LDAP",
                    "desc"  => "Use LDAP integration with JotForm",
                    "type"  => "checkbox",
                    "value" => false
                ],
                "LDAP_SERVER" => [
                    "title" => "LDAP Server Address",
                    "desc"  => "LDAP server name or address such as: <b>127.0.0.1</b>",
                    "required" => true,
                    "value" => "127.0.0.1"
                ],
                "LDAP_PORT" => [
                    "title" => "LDAP Server port",
                    "desc"  => "Default is 389",
                    "required" => true,
                    "value" => "389"
                ],
                "LDAP_BIND_USER" => [
                    "title" => "Bind user DN",
                    "desc"  => "Enter full dn, such as \"cn=admin,dn=example,dn=com\"",
                    "required" => true,
                    "value" => "cn=Admin,o=Directory"
                ],
                "LDAP_BIND_USER_PASS" => [
                    "title" => "Bind User Password",
                    "desc"  => "Password",
                    "required" => true,
                    "type" => "password",
                    "value" => ""
                ],
                "LDAP_SEARH_DOMAIN" => [
                    "title" => "User Search Domain",
                    "desc"  => "Domain information to look for users",
                    "required" => true,
                    "value" => "o=Directory"
                ]
            ]
        ]
    ];
