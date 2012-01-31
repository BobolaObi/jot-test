<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Update JotForm</title>
        <style>
            body{
                font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
                font-size:14px;
                background:#666;
                color:white;
                text-shadow:1px 1px 2px #000000;
            }
            
        </style>
    </head>
    <body>
        <h2>Please wait while updating</h2>
        <p>JotForm is preparing itself for the first time.<br>This may take a while.</p>
        <?
            $result = RuckusingWrapper::migrateLatest();
            if (!$result['success']) {
                Utils::errorPage("We are unable to update your database to the latest version");
            }
            echo "--DB Updated<br>";            
            file_get_contents(HTTP_URL."/min/g=jotform&3.1.5");
            echo "--Form scripts minified.<br>";
            Utils::deleteCookie('INSTALLCOMPLETE');
            Utils::redirect(HTTP_URL);
        ?>
    </body>
</html>