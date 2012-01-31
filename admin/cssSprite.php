<?php
    include "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>CSS Sprite</title>
        <base href="<?=HTTP_URL?>" />
        <link href="css/style.css" type="text/css" rel="stylesheet" />
        <link href="css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript" src="js/prototype.js"></script>
        <script type="text/javascript" src="js/protoplus.js"></script>
        <script type="text/javascript" src="js/protoplus-ui.js"></script>
        <script type="text/javascript" src="js/common.js"></script>
        <script type="text/javascript" src="js/includes/sprite.js"></script>
    </head>
    <body>
        <div id="admin-content">
            <div id="admin-content-menu">
                <h2>CSS Sprite</h2>
                <button id="create-css-sprite" class="big-button buttons">Create CSS Sprite</button>
            </div>
        </div>
    </body>
</html>