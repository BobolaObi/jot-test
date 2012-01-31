<?php 
    include_once "../../lib/init.php";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?=$_POST['title']?></title>
        <style>
            body{
                font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
                background:#fcfcfc;
            }
        </style>
    </head>
    <body>
    <div style="width: 30em; height: 10em; position: absolute; top: 50%; left: 50%; margin: -5em 0 0 -15em;">
        <h1><?=$_POST['title']?></h1>
        <?php
        if (isset($_POST['scriptSource'])) {
            echo $_POST['scriptSource'];
        }
        ?>
    </div>
    </body>
</html>
