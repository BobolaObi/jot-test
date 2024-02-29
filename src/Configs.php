<?php

# Generated on the installer
namespace Legacy\Jot;

class Configs
{
    const COMPANY_TITLE = "The Auxilium Group";
    const COMPANY_LOGO = "";
    
    // Use environment variables with fallbacks
    const DBNAME              = "jotforms";
    const DEV_DB_HOST         = "mysqldb";
    const DEV_DB_USER         = "jotforms";
    const DEV_DB_PASS         = "access";
    const PRO_DB_HOST         = "mysqldb";
    const PRO_DB_USER         = "jotforms";
    const PRO_DB_PASS         = "access";
    
    const DB_USE_SSL = false; 
    const SSL_CIPHER = "";
    
    const LOGFOLDER = "/data/www/forms.datalynk.ca/logs/";
    const USECDN = false; 
    const USEUFS = false; 
    const NOREPLY = "no-reply@datalynk.ca";
    const NOREPLY_NAME = "Support Team";
    const NOREPLY_SUPPORT = "Support Team";
    const ANALYTICS_CODE = ""; 
    const DEFAULT_USER_TYPE = "PROFESSIONAL";
    const CLOUD_UPLOAD_ALIAS = "http://www.jotform.com/uploads/";
    const SUBFOLDER = "/";
    const CACHEPATH = "/data/www/forms.datalynk.ca/cache/";
    const UPLOADPATH = "/data/www/forms.datalynk.ca/uploads/";
    
    const APP = true; 
    const HAVESSL = false; 
    const SENDGRID_APIUSER = ""; 
    const SENDGRID_APIKEY = ""; 
    const DROPBOX_KEY = ""; 
    const DROPBOX_SECRET = ""; 
    const USELDAP = false; 
    const LDAP_SERVER = "";
    const LDAP_PORT = "";
    const LDAP_BIND_USER = "";
    const LDAP_BIND_USER_PASS = "";
    const LDAP_SEARH_DOMAIN = "";
    
    const USE_SENDGRID = false; 
    const USE_REDIS_SUBMSN = false; 

    const REDIS_HOST = "127.0.0.1"; 
    const REDIS_PORT = "6379"; 
    const REDIS_PASSWORD = ""; 

    public static $servers = [];
}