<?
    $p = true; // If localhost then set to false
    $d = $p? "" : "/..";
    $_SERVER['HTTP_HOST']   = $p? "yang.interlogy.com" : "localhost";
    $_SERVER['SERVER_ADDR'] = $p? "10.202.1.216" : '192.168.1.223';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).$d); // __DIR__ came in PHP 5.3
    $_SERVER['HTTPS'] = "off";
    $_COOKIE = [];
    include_once "lib/init.php";
    Submission::completeSlowSubmissions();
   
    exit;
?>
