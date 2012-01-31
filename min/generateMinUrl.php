<?php
define('MINIFY_MIN_DIR', dirname(__FILE__));

function  generateMinUrl (){
    
    // load config
    require MINIFY_MIN_DIR . '/config.php';
    
    // setup include path
    set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());
    
    require_once 'Minify.php';
    
    Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
    Minify::setCache(
        isset($min_cachePath) ? $min_cachePath : ''
        ,$min_cacheFileLocking
    );
    
    if ($min_documentRoot) {
        $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
    } elseif (0 === stripos(PHP_OS, 'win')) {
        Minify::setDocRoot(); // IIS may need help
    }
    
    $min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
    
    if ($min_allowDebugFlag && isset($_GET['debug'])) {
        $min_serveOptions['debug'] = true;
    }
    
    if ($min_errorLogger) {
        require_once 'Minify/Logger.php';
        if (true === $min_errorLogger) {
            require_once 'FirePHP.php';
            Minify_Logger::setLogger(FirePHP::getInstance(true));
        } else {
            Minify_Logger::setLogger($min_errorLogger);
        }
    }
    
    // check for URI versioning
    if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
        $min_serveOptions['maxAge'] = 31536000;
    }
    
    // well need groups config
    $min_serveOptions['minApp']['groups'] = (require MINIFY_MIN_DIR . '/groupsConfig.php');
    
    return Minify::generateCacheId( 'MinApp', $min_serveOptions);
}



