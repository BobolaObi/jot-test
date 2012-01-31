<?php
    # Full Structure of the installer interface
    $installerConfig = array(
        "GENERAL" => array(
            "title" => "General Settings",
            "tests" => false,
            "items" => array(
                "COMPANY_TITLE" => array(
                    "title" => 'Site Title',
                    "desc"  => 'Main title for Form Builder. Used in page titles and headers.',
                    "value" => 'MyCompany Forms',
                    "required" => true
                ),
                "COMPANY_LOGO" => array(
                    "title" => 'Site Logo',
                    "desc"  => 'Logo of your company (165 x 55). Optional.',
                    "value" => ''
                ),
                "NOREPLY" => array(
                    "title" => 'Sender Email Address',
                    "desc"  => '"From" email address on emails.',
                    "value" => 'noreply@mydomain.com',
                    "required" => true
                ),
                "NOREPLY_NAME" => array(
                    "title" => 'Sender Email Name',
                    "desc"  => '"From" name on emails.',
                    "value" => 'MyCompany Forms',
                    "required" => true
                ),
                "NOREPLY_SUPPORT" => array(
                    "title" => 'Support Email Name',
                    "desc"  => 'Name on registration and lost password emails.',
                    "value" => 'MyCompany',
                    "required" => true
                )
            )
        ),
        "DATABASE" => array(
            "title" => "Mysql Database Settings",
            "tests" => "checkDB",
            # "hidden" => true,
            "items" => array(
                "DBNAME" => array(
                    "title" => 'Database Name',
                    "desc"  => 'Mysql Database Name',
                    "value" => 'jotform',
                    "required" => true
                ),
                "PRO_DB_HOST" => array(
                    "title" => 'Database Hostname',
                    "desc"  => 'Mysql Database Hostname.',
                    "value" => 'localhost',
                    "required" => true
                ),
                "PRO_DB_USER" => array(
                    "title" => 'Database Username',
                    "desc"  => 'Mysql Database Username',
                    "value" => 'root',
                    "required" => true
                ),
                "PRO_DB_PASS" => array(
                    "title" => 'Database Password',
                    "desc"  => 'Mysql Database Password',
                    "type"  => 'password',
                    "value" => ''
                )
            )
        ),
        "DEVDATABASE" => array(
            "title" => "Development Database",
            "tests" => "checkDB",
            "hidden" => true,
            "items" => array(
                "DEV_DB_HOST" => array(
                    "title" => 'Dev DB Host',
                    "desc"  => 'Database Host Name for development mode',
                    "value" => 'localhost',
                    "required" => true
                ),
                "DEV_DB_USER" => array(
                    "title" => 'Dev DB Username',
                    "desc"  => 'Database Username for Development Mode',
                    "value" => 'root',
                    "required" => true
                ),
                "DEV_DB_PASS" => array(
                    "title" => 'Dev Database Password',
                    "desc"  => 'Development Mode Database Password',
                    "type"  => 'password',
                    "value" => ''
                )
            )
        ),
        "ADMIN" => array(
            "title" => "Administration",
            "tests" => "checkPaths",
            "items" => array(
                "DEFAULT_USER_TYPE" => array(
                    "title" => 'Default User Type',
                    "desc"  => 'Select the default user type for account created.',
                    "dropdown" => array('GUEST', 'FREE', 'PREMIUM', 'PROFESSIONAL', 'ADMIN'),
                    "value" => 'PROFESSIONAL'
                ),
                "CLOUD_UPLOAD_ALIAS" => array(
                    "title" => '',
                    "desc"  => '',
                    "value" => '',
                    "hidden" => true
                ),
                "CACHEPATH" => array(
                    "title" => 'Cache Folder',
                    "desc"  => 'Specify a folder for jotform form cache files. ',
                    "value" => $root."cache/",
                    "required" => true
                ),
                "UPLOADPATH" => array(
                    "title" => 'Upload Folder',
                    "desc"  => 'Specify a folder for jotform uploaded files.',
                    "value" => $root."uploads/",
                    "required" => true
                ),
                "LOGFOLDER" => array(
                    "title" => 'Log Folder',
                    "desc"  => 'Specify a folder for jotform logs.',
                    "value" => '/tmp/logs/',
                    "required" => true
                ),
                "ANALYTICS_CODE" => array(
                    "title" => 'Analytics Code',
                    "desc"  => 'If you have Google Analytics, put the tracking number here.',
                    "value" => 'UA-XXXXXXX-X'
                ),
                "USECDN" => array(
                    "title" => 'Use CDN',
                    "desc"  => 'Use Amazon CloudFront Content Delivery Networks for site files.',
                    "type"  => "checkbox",
                    "value" => false,
                    "hidden"=> true
                ),
                "USEUFS" => array(
                    "title" => 'Use UFS',
                    "desc"  => 'Copy uploaded files to Amazon S3 Server.',
                    "type"  => "checkbox",
                    "value" => false,
                    "hidden"=> true
                )
            )
        ),
        "ADMIN_USER" => array(
            "title" => "Admin User",
            "tests" => "createAdmin",
            "items" => array(
                "USERNAME" => array(
                    "title" => "Username",
                    "desc"  => "Username for admin account.",
                    "required" => true,
                    "value" => ""                    
                ),
                "EMAIL" => array(
                    "title" => "E-Mail",
                    "desc"  => "E-mail address for admin account",
                    "required" => true,
                    "value" => ""                    
                ),
                "PASSWORD" => array(
                    "title" => "Password",
                    "desc"  => "Password for admin account",
                    "type"  => 'password',
                    "required" => true,
                    "value" => ""
                )
            )
        ),
        "LDAP_SETTINGS" => array(
            "title" => "LDAP Intergration",
            "tests" => "ldapConfig",
            "items" => array(
                "USELDAP" => array(
                    "title" => "Enable LDAP",
                    "desc"  => "Use LDAP integration with JotForm",
                    "type"  => "checkbox",
                    "value" => false
                ),
                "LDAP_SERVER" => array(
                    "title" => "LDAP Server Address",
                    "desc"  => "LDAP server name or address such as: <b>127.0.0.1</b>",
                    "required" => true,
                    "value" => "127.0.0.1"
                ),
                "LDAP_PORT" => array(
                    "title" => "LDAP Server port",
                    "desc"  => "Default is 389",
                    "required" => true,
                    "value" => "389"
                ),
                "LDAP_BIND_USER" => array(
                    "title" => "Bind user DN",
                    "desc"  => "Enter full dn, such as \"cn=admin,dn=example,dn=com\"",
                    "required" => true,
                    "value" => "cn=Admin,o=Directory"
                ),
                "LDAP_BIND_USER_PASS" => array(
                    "title" => "Bind User Password",
                    "desc"  => "Password",
                    "required" => true,
                    "type" => "password",
                    "value" => ""
                ),
                "LDAP_SEARH_DOMAIN" => array(
                    "title" => "User Search Domain",
                    "desc"  => "Domain information to look for users",
                    "required" => true,
                    "value" => "o=Directory"
                )
            )
        )
    );
