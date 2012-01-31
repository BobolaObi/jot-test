<?php 
include_once "lib/init.php";
$sID = $_GET["sid"];

if (!is_numeric($sID)) {
    Utils::errorPage("We couldn't find the submission you are looking for on our servers."); // irregular sID was given.
}
try {
    $answers = Form::getSubmissionResult($sID);
} catch(Exception $e) {
    Utils::errorPage("We couldn't find the submission you are looking for on our servers."); // submission does not exist.
}

if ( empty($answers)) {
    Utils::errorPage("We couldn't find the submission you are looking for on our servers."); // submission does not exist.
}
$url = HTTP_URL;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Submission Details</title>
        <style>
            body {
                font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
                font-size: 14px;
                margin: 0px;
                padding: 0px;
                height: 100%;
                width: 100%;
            }
            #alltable{
                -moz-box-shadow:0px 4px 16px rgba(0,0,0,0.5);
                -webkit-box-shadow:0px 4px 16px rgba(0,0,0,0.5);
                box-shadow:0px 4px 16px rgba(0,0,0,0.5);
            }
        </style>
    </head>
    <body>
        <table bgcolor="#ffffff" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td height="30">
                    &nbsp;
                </td>
            </tr>
            <tr>
                <td align="center">
                    <table width="95%" border="0" cellspacing="0" cellpadding="0" id="alltable">
                        <tr>
                            <td width="4"></td>
                            <td align="center" bgcolor="#FFFFFF">
                                <table width="100%" border="0" cellspacing="0" cellpadding="5">
                                    <tr>
                                        <td bgcolor="#f9f9f9" width="200" style="text-decoration:underline; padding:5px !important;">
                                        </td>
                                        <td bgcolor="#f9f9f9" width="600" style="text-decoration:underline; padding:5px !important;">
                                        </td>
                                    </tr>
                                    <?php 
                                    $x = 0;
                                    $formID = FALSE;
                                    $username = FALSE;
                                    
                                    foreach ($answers as $qid=>$qProp) {
                                        if ($qProp["type"] == "control_fileupload" && $formID === false) {
                                            list($username, $formID) = RSSHelper::getSubmissionDetails($sID);
                                        }
                                        $question = isset($qProp["text"]) ? $qProp["text"] : "";
                                        $answer = isset($qProp["value"]) ? $qProp["value"] : "";
                                        
                                        if ($qProp["type"] == "control_fileupload" && !Utils::contains('<a href', $answer)) {
                                            // It is a file upload, make sure you add the URL to download it.
                                            $answer = '<a href="'.Utils::getUploadURL($username, $formID, $sID, basename($answer)).'">'.basename($answer).'</a>';
                                        }
                                         
                                        $alt = "";//($x % 2 != 0) ? "#eee" : "white";
                                         
                                        echo "<tr>
                                                <td valign=\"top\"  bgcolor='#eeeeee".$alt."' style='font-weight:bold;padding:5px !important;'>".$question."</td>
                                                <td bgcolor='".$alt."' style='padding:5px !important;'>".nl2br(stripslashes($answer))."</td>
                                        </tr>";
                                        echo '<tr>
                                             <td bgcolor="#eeeeee">
                                                <div style="border-top:1px dotted #bbb"></div>
                                             </td>
                                             <td>
                                                <div style="border-top:1px dotted #bbb"></div>
                                            </td>
                                            </tr>';
                                        $x++;
                                    }
                                    ?>
                                </table>
                            </td>
                            <td width="4" ></td>
                        </tr>
                        <tr>
                            <td height="4"> </td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td height="30">
                    &nbsp;
                </td>
            </tr>
        </table>
    </body>
</html>
