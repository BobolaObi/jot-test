<?php

use Legacy\Jot\SiteManagement\PageInfo;
use Legacy\Jot\UserManagement\AccountType;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Server;


/**
 * All site configurations here
 * @package JotForm_Site_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

/**
 * Create pages here
 */

PageInfo::setPage([
    "name" => "admin",
    "title" => "Admin",
    "content" => "lib/includes/admin.php",
    "css" => ["css/includes/admin.css", "sprite/toolbar-admin.css"],
    "js" => [
        "js/includes/admin.js"
    ]
]);

PageInfo::setPage([
    "name" => "signup",
    "title" => "Sign Up",
    "content" => "lib/includes/signup.php",
    "css" => ["css/includes/signup.css"],
    "js" => ["js/includes/signup.js"]
]);


PageInfo::setPage([
    "name" => "myforms2",
    "title" => "My Forms",
    "content" => "lib/includes/oldmyforms.php",
    "loginNeeded" => true,
    "css" => ["css/includes/oldmyforms.css", "sprite/toolbar-myforms.css"],
    "js" => [
        "js/effects.js",
        "js/dragdrop.js",
        "js/includes/oldmyforms.js",
        "server.php?action=getLoggedInUser&includeUsage=1&callback=Utils.setUserInfo",
        "server.php?action=getFormList&callback=MyForms.setForms"
    ]
]);

# For new myforms
PageInfo::setPage([
    "name" => "myforms",
    "title" => "My Forms",
    "content" => "lib/includes/myforms2.php",
    "loginNeeded" => true,
    "hasFullScreen" => true,
    "css" => [CSS_PATH . "/css/includes/myforms2.css", CSS_PATH . "/sprite/toolbar-myforms.css"],
    "js" => [
        CSS_PATH . "/js/effects.js",
        CSS_PATH . "/js/dragdrop.js",
        CSS_PATH . "/js/includes/myforms2.js",
        CSS_PATH . "/js/feedback.js",
        "server.php?action=getLoggedInUser&includeUsage=1&callback=Utils.setUserInfo",
        "server.php?action=getFormList&callback=MyForms.setForms"
    ]
]);

PageInfo::setPage([
    "name" => "reports",
    "title" => "Reports",
    "loginNeeded" => true,
    "content" => "lib/includes/reports.php",
    "css" => ["css/includes/reports.css", "sprite/controls.css"],
    "js" => [
        "js/charts.js",
        "js/includes/reports.js",
        "js/nicEdit.js",
        "server.php?action=getLoggedInUser&includeUsage=1&callback=Utils.setUserInfo",
        "server.php?action=getReportsData&formID=session&callback=Reports.getChartableElements",
        "server.php?action=getSavedForm&formID=session&callback=Reports.getFormProperties",
        "server.php?action=getSavedReport&reportID=session&formID=session&callback=Reports.retrieve"
    ]
]);

PageInfo::setPage([
    "name" => "myaccount",
    "title" => "Account Settings",
    "content" => "lib/includes/myaccount.php",
    "css" => ["css/includes/myaccount.css"],
    "js" => [
        "js/includes/myaccount.js"
    ]
]);

PageInfo::setPage([
    "name" => "passwordreset",
    "title" => "Reset Your Password",
    "content" => "lib/includes/password_reset.php",
    "css" => ["css/includes/password_reset.css"],
    "js" => [
        "js/includes/password_reset.js"
    ]
]);

PageInfo::setPage([
    "name" => "passwordresetexpired",
    "title" => "Password Reset Code Expired",
    "content" => "lib/includes/pass_reset_expired.php",
]);

PageInfo::setPage([
    "name" => "submissions",
    "title" => "Submissions",
    "loginNeeded" => true,
    "content" => "lib/includes/submissions.php",
    "css" => [
        "css/styles/form.css",
        "css/includes/submissions.css",
        (PROTOCOL === 'https://' || JOTFORM_ENV == 'DEVELOPMENT') ? "opt/extjs/css/ext-all.css" : "http://extjs.cachefly.net/ext-3.1.0/resources/css/ext-all.css",
        (PROTOCOL === 'https://' || JOTFORM_ENV == 'DEVELOPMENT') ? "opt/extjs/css/xtheme-gray.css" : "http://extjs.cachefly.net/ext-3.1.0/resources/css/xtheme-gray.css",

    ],
    "js" => [
        (PROTOCOL === 'https://' || JOTFORM_ENV == 'DEVELOPMENT') ? "opt/extjs/js/ext-prototype-adapter.js" : "http://extjs.cachefly.net/ext-3.1.0/adapter/prototype/ext-prototype-adapter.js",
        (PROTOCOL === 'https://' || JOTFORM_ENV == 'DEVELOPMENT') ? "opt/extjs/js/ext-all.js" : "http://extjs.cachefly.net/ext-3.1.0/ext-all.js",
        "js/Ext.ux.util.js",
        "js/Ext.ux.state.HttpProvider.js",
        "js/Ext.extend.js",
        "js/includes/submissions.js",
        "js/tiny_mce/tiny_mce.js",
        "js/googlemap.js",
        "server.php?action=getLoggedInUser&callback=Utils.setUserInfo",
        "server.php?action=getSavedForm&formID=session&callback=Submissions.getFormProperties&checkPublicity=yes",
        "server.php?action=getSetting&identifier=form&key=columnSetting&callback=Submissions.getColumnSettings",
        "http://maps.google.com/maps/api/js?sensor=true",
        "server.php?action=getExtGridStructure&callback=Submissions.initGrid&formID=session&type=submissions"
    ]
]);

PageInfo::setPage([
    "name" => "login",
    "title" => "Login",
    "content" => "lib/includes/login.php",
    "loginNeeded" => false,
    "css" => [CSS_PATH . "/css/includes/login.css"],
    "js" => [
        CSS_PATH . "/js/includes/loginForm.js",
        CSS_PATH . "/js/common.js"
    ]
]);

PageInfo::setPage([
    "name" => "cancel",
    "title" => "Downgrade Instructions",
    "content" => "lib/includes/cancel.php",
    "loginNeeded" => true,
    "css" => ["css/includes/cancel.css"]
]);

// Limit types (submissions, payments etc. are defined in the MonthlyUsage class.
// Types not there will not be set and will be silently ignored.
// Add account types.

// Guest account:
AccountType::create(['name' => 'GUEST', "prettyName" => 'Guest', 'limits' => [
    'submissions' => 20,
    'sslSubmissions' => 2,
    'payments' => 2,
    'uploads' => 100 * MB, // 100MB
    'tickets' => 0
]]);

// Free account:
AccountType::create(['name' => 'FREE', "prettyName" => 'Free', 'limits' => [
    'submissions' => 100,
    'sslSubmissions' => 10,
    'payments' => 10,
    'uploads' => 100 * MB, // 100MB
    'tickets' => 0
]]);

// Premium account:
AccountType::create(['name' => 'PREMIUM', "prettyName" => 'Premium', 'limits' => [
    'submissions' => 1000,
    'sslSubmissions' => 1000,
    'payments' => 1000,
    'uploads' => 10241 * MB, // 10GB
    'tickets' => 3
]]);

// Old Premium account:
AccountType::create(['name' => 'OLDPREMIUM', "prettyName" => 'Premium', 'limits' => [
    'submissions' => 1000000,
    'sslSubmissions' => 1000000,
    'payments' => 1000000,
    'uploads' => 10241 * MB, // 10GB
    'tickets' => 3
]]);

// Enterprise account:
AccountType::create(['name' => 'PROFESSIONAL', "prettyName" => 'Professional', 'limits' => [
    'submissions' => 1000000,
    'sslSubmissions' => 1000000,
    'payments' => 1000000,
    'uploads' => 1048577 * MB, // 1TB
    'tickets' => 10
]]);

// Admin account for users with super powers.
AccountType::create(['name' => 'ADMIN', "prettyName" => 'Administrator', 'limits' => [
    'submissions' => 10000,
    'sslSubmissions' => 10000,
    'payments' => 10000,
    'uploads' => 1048577 * MB, // 1TB
    'tickets' => 10
]]);

// Admin account for users with super powers.
AccountType::create(['name' => 'SUPPORT', "prettyName" => 'Support', 'limits' => [
    'submissions' => 10000,
    'sslSubmissions' => 10000,
    'payments' => 10000,
    'uploads' => 1048577 * MB, // 1TB
    'tickets' => 10
]]);

/**
 * Add the following line to the php.ini file so that an exception like the following is not thrown:
 * date.timezone = "America/Los_Angeles"
 *
 * >> $a->log( 'Mesaj')
 * exception 'Exception' with message '/Users/tayfun/progz/Log-1.11.5/Log/file.php:294
 * strftime(): It is not safe to rely on the system's timezone settings. You are *required* to use the date.timezone setting or the date_default_timezone_set() function. In case you used any of those methods and you are still getting this warning, you most likely misspelled the timezone identifier. We selected 'Europe/Helsinki' for 'EET/2.0/no DST' instead'
 *
 */

// Below used for sending an e-mail to the developers when an unhandled exception occurs.
// set_exception_handler('Console::exceptionHandler');
// Set email addresses exception messages will be sent to.
Console::setEmailAddresses(["agreganti@ourtrainingroom.com"]);

# Set up which database will be used. It will be localhost for local
# environment and goby everywhere else.

#DB::setConnection('submissions', DB_NAME, DB_USER, DB_PASS, Server::$servers->db->local->goby);
DB::setConnection('submissions', DB_NAME, DB_USER, DB_PASS, DB_HOST);
DB::setConnection('new', DB_NAME, DB_USER, DB_PASS, DB_HOST);

if (Server::isHost('salmon')) {
    DB::setConnection('main', 'jotform_main', DB_USER, DB_PASS, 'localhost');
} else {
    //DB::setConnection('main', 'jotform_main', DB_USER, DB_PASS, Server::$servers->db->local->dolphin);
    DB::setConnection('main', 'jotform_main', DB_USER, DB_PASS, DB_HOST);
}
# Set the defaul database to new
DB::useConnection('new');

# disabled, replaced with default user
# Remembers users from cookie or starts current session
# Session::rememberLogin();
