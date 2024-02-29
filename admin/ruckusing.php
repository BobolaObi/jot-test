<?php
    include "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Manage Database</title>
        <base href="<?=HTTP_URL?>" />
        <link href="css/style.css" type="text/css" rel="stylesheet" />
        <link href="css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="css/includes/ruckusing.css" type="text/css" rel="stylesheet" />
        <script type="text/javascript" src="js/prototype.js"></script>
        <script type="text/javascript" src="js/protoplus.js"></script>
        <script type="text/javascript" src="js/protoplus-ui.js"></script>
        <!-- Translations scripts must be included after Prototype, English language should be included before other languages. -->
        <script src="js/locale/locale_en-US.js" type="text/javascript"></script>
        <?php 
            echo Translations::getJsInclude();
        ?>
        <script src="js/locale/locale.js" type="text/javascript"></script>
        <script type="text/javascript" src="js/common.js"></script>
        <script src="js/includes/ruckusing.js"></script>
        <link type="text/css" href="opt/codepress/languages/php.css" rel="stylesheet" id="cp-lang-style" />
        <script type="text/javascript" src="opt/codepress/codepress.js"></script>
        <script type="text/javascript">
        CodePress.language = 'php';
        </script>
    </head>
    <body>
        <div id="admin-content">
	       <div id="admin-content-menu">
	            <h2>Manage the DB</h2>
	            <h3>What would you like to do today?</h3>
	            <button id="add-migration" class="big-button buttons ruckusing-bt">New Migration</button>
	            <button id="migrate-latest" class="big-button buttons ruckusing-bt">Migrate to Latest</button>
	            <button id="migrate-version" class="big-button buttons ruckusing-bt">Migrate to a Version</button>
            </div>
            <div id="cp1-div" style="height: 0px;">
                <textarea id="cp1" class="codepress php" wrap="off" style="width: 650px;height: 255px;"></textarea><br />
                <button id="save-migration" class="big-button buttons ruckusing-bt">Save this Migration</button>
                <button id="cancel-migration" class="big-button buttons ruckusing-bt">Cancel Migration</button>
            </div>
            <fieldset id="response-fieldset">
                <legend>Response</legend>
                <div id="response-div">
                    
                </div>
            </fieldset>
        </div>
    </body>
</html>
