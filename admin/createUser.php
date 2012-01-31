<?php
    include "../lib/init.php";
    Session::checkAdminPages();
    define('ALLOWSIGNUP', true);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Create User</title>
        <base href="<?=HTTP_URL?>" />
        <link href="css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="css/includes/signup.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="css/buttons.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="sprite/index.css" rel="stylesheet" type="text/css" media="screen" />
        <script> document.APP = true; document.SUBFOLDER = '<?=Configs::SUBFOLDER?>'; </script>
    </head>
    <body>
        <div id="admin-content">
        <? include ROOT."lib/includes/signup.php"; ?>
        </div>
        <script src="js/prototype.js" type="text/javascript"></script>
        <script src="js/protoplus.js" type="text/javascript"></script>
        
        <script src="js/common.js" type="text/javascript"></script>
        <!-- Translations scripts must be included after Prototype, English language should be included before other languages. -->
        <script src="js/locale/locale_en-US.js" type="text/javascript"></script>
        <script src="js/locale/locale.js" type="text/javascript"></script>
        <!-- Protoplus requires localization therefore it's inculed after locale.js -->
        <script src="js/protoplus-ui.js" type="text/javascript"></script>
        <script src="js/includes/signup.js" type="text/javascript"></script>
    </body>
</html>