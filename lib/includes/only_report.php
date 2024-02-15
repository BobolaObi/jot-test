<?php

use Legacy\Jot\Report;
use Legacy\Jot\Utils\Utils;

# no idea why, but this doesn't work with the name RequestServer....


?>
<?
    $reportID = Utils::get('reportID');
    try{
        $report = new Report($reportID);
        $formID   = $report->formID;
    }catch(Exception $e){
        Utils::errorPage("This report has been deleted and cannot be found on our servers", "Report Not Found");
    }
    
    if(!empty($report->password)){
        session_start();
        if(Utils::get('logout') !== false){
            unset($_SESSION["passwordReport_".$report->id]);
            Utils::redirect(str_replace('?logout', '', Utils::path(SSL_URL.$_SERVER['REQUEST_URI'])));
        }
        
        if(isset($_SESSION["passwordReport_".$report->id]) && $_SESSION["passwordReport_".$report->id] != $report->password){
            unset($_SESSION["passwordReport_".$report->id]);
        }
    }
    
    if(!empty($report->password) && !isset($_SESSION["passwordReport_".$report->id])){
        if(Utils::get('passKey') && Utils::get('passKey') == $report->password){
            $_SESSION["passwordReport_".$report->id] = $report->password;
            Utils::redirect(Utils::path(SSL_URL.$_SERVER['REQUEST_URI']));
        }else{
            if(!IS_SECURE){
                Utils::redirect(Utils::path(SSL_URL.$_SERVER['REQUEST_URI']));                
            }
            $loginForm  = '<h4>Enter Password to Access Reports</h4>';
            $loginForm .= '<form method="post" action="'.Utils::path(SSL_URL.$_SERVER['REQUEST_URI']).'">';
            $loginForm .= '<label>Password: <input type="password" name="passKey" style="width:200px; font-size:16px; padding:5px;"></label><br>';
            # If there is a password and still seeing the ask password page
            # then it's a wrong password
            if(Utils::get('passKey') !== false){
                $loginForm .= '<div style="font-size:11px; color:red">Password did not match!!</div>';
            }
            $loginForm .= '<br><button style="padding:5px; font-size:14px;" type="submit">Show Report</button>';
            $loginForm .= '</form>';
            Utils::errorPage($loginForm, "Restricted Access", "Unauthorized access");
        }
    }
    
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Report Title</title>
        <base href="<?=HTTP_URL?>" />
        <link rel="stylesheet" type="text/css" href="css/includes/reports.css?v=3.0.<?=VERSION?>"/>
        <link rel="stylesheet" type="text/css" rel="stylesheet" media="print" href="css/print.css?v=3.0.<?=VERSION?>"/>
        <link rel="Shortcut Icon" href="/favicon.ico?12345&v=3.0.<?=VERSION?>" />
        
        <? if(Utils::get('embed') !== false){ ?>
            <style>
                body{
                    background:#fff;
                    font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
                    font-size:12px;
                }
                #report-stage{
                    border:none;
                    -moz-box-shadow:none;
                    -webkit-box-shadow:none;
                    box-shadow:none;
                }

            </style>
        <? }else{ ?>
            <style>
                body{
                    background:#eee;
                    font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
                    font-size:12px;
                }
            </style>
        <? } ?>
        
    </head>
    <body>
        <div id="report-stage" class="finished-mode"></div>
        <script src="js/prototype.js?v3&v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="js/protoplus.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="js/common.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        
        <!-- Translations scripts must be included after Prototype, English language should be included before other languages. -->
        <script src="js/locale/locale_en-US.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="js/locale/locale.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <!-- Protoplus requires localization therefore it's inculed after locale.js -->
        <script src="js/protoplus-ui.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="js/charts.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="js/includes/reports.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="js/nicEdit.js?v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="server.php?action=getLoggedInUser&includeUsage=1&callback=Utils.setUserInfo&v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="server.php?action=getReportsData&formID=<?=$formID?>&callback=Reports.getChartableElements&v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="server.php?action=getFormProperties&formID=<?=$formID?>&callback=Reports.getFormProperties&v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <script src="server.php?action=getSavedReport&reportID=<?=$reportID?>&formID=<?=$formID?>&callback=Reports.retrieve&v=3.0.<?=VERSION?>" type="text/javascript"></script>
        <?
            Utils::putAnalytics("UA-1170872-7");
        ?>        
    </body>
</html>


