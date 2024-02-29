<?php

# Generated on the installer
namespace Legacy\Jot;

class Configs
{
    const COMPANY_TITLE = "The Auxilium Group";
    const COMPANY_LOGO = "";
    
    // Use environment variables with fallbacks
    const DBNAME = getenv('DB_NAME') ?: "jotforms";
    const DEV_DB_HOST = getenv('MYSQL_HOST') ?: "localhost";
    const DEV_DB_USER = getenv('DB_USER') ?: "jotforms";
    const DEV_DB_PASS = getenv('DB_PASS') ?: "access";
    const PRO_DB_HOST = getenv('MYSQL_HOST') ?: "mysqlrouter:6446";
    const PRO_DB_USER = getenv('DB_USER') ?: "jotforms";
    const PRO_DB_PASS = getenv('DB_PASS') ?: "access";
    
    const DB_USE_SSL = false; // Assuming SSL is not used in your Docker setup
    const SSL_CIPHER = "";
    
    const LOGFOLDER = "/data/www/forms.datalynk.ca/logs/";
    const USECDN = false; // CDN usage as per your setup
    const USEUFS = false; // User File System (UFS) usage
    const NOREPLY = "no-reply@datalynk.ca";
    const NOREPLY_NAME = "Support Team";
    const NOREPLY_SUPPORT = "Support Team";
    const ANALYTICS_CODE = ""; // Google Analytics or other tracking code
    const DEFAULT_USER_TYPE = "PROFESSIONAL";
    const CLOUD_UPLOAD_ALIAS = "http://www.jotform.com/uploads/";
    const SUBFOLDER = "/";
    
    // Paths should match your volume mounts and configurations in docker-compose
    const CACHEPATH = "/data/www/forms.datalynk.ca/cache/";
    const UPLOADPATH = "/data/www/forms.datalynk.ca/uploads/";
    
    const APP = true; // Assuming this is a specific application flag
    const HAVESSL = false; // Assuming SSL is not configured in your Docker setup
    const SENDGRID_APIUSER = ""; // Your SendGrid API user, if applicable
    const SENDGRID_APIKEY = ""; // Your SendGrid API key, if applicable
    const DROPBOX_KEY = ""; // Your Dropbox key, if used
    const DROPBOX_SECRET = ""; // Your Dropbox secret, if used
    const USELDAP = false; // LDAP usage flag
    // LDAP configuration, if used
    const LDAP_SERVER = "";
    const LDAP_PORT = "";
    const LDAP_BIND_USER = "";
    const LDAP_BIND_USER_PASS = "";
    const LDAP_SEARH_DOMAIN = "";
    
    const USE_SENDGRID = false; // Flag to use SendGrid for emails
    const USE_REDIS_SUBMSN = false; // Assuming Redis is for submission caching or similar
    
    // Redis configuration should match your Docker setup
    const REDIS_HOST = "redis"; // Matches service name in docker-compose
    const REDIS_PORT = "6379"; // Default Redis port
    const REDIS_PASSWORD = ""; // Redis password, if set

    public static $servers = [];
}