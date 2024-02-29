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
                font-size: 12px;
                margin: 0px;
                padding: 0px;
                height: 100%;
                width: 100%;
            }
        </style>
    </head>
    <body>
        <table bgcolor="#f7f9fc" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td height="30">
                    &nbsp;
                </td>
            </tr>
            <tr>
                <td align="center">
                    <table width="800" border="0" cellspacing="0" cellpadding="0" bgcolor="#eeeeee">
                        <tr>
                            <td width="13" height="30" background="<?php echo $url; ?>images/win2_title_left.gif">
                            </td>
                            <td align="left" background="<?php echo $url; ?>images/win2_title.gif" valign="bottom">
                                <img style="float:left" src="<?php echo $url; ?>images/win2_title_logo.gif" width="63" height="26" alt="JotForm.com" />
                            </td>
                            <td width="14" background="<?php echo $url; ?>images/win2_title_right.gif">
                            </td>
                        </tr>
                    </table>
                    <table width="800" border="0" cellspacing="0" cellpadding="0" bgcolor="#eeeeee">
                        <tr>
                            <td width="4" background="<?php echo $url; ?>images/win2_left.gif">
                            </td>
                            <td align="center" bgcolor="#FFFFFF">
                                <table width="100%" border="0" cellspacing="0" cellpadding="5">
                                    <tr>
                                        <td bgcolor="#f9f9f9" width="170" style="text-decoration:underline; padding:5px !important;">
                                            <b>Question</b>
                                        </td>
                                        <td bgcolor="#f9f9f9" style="text-decoration:underline; padding:5px !important;">
                                            <b>Answer</b>
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
                                         
                                        if ($qProp["type"] == "control_fileupload") {
                                            // It is a file upload, make sure you add the URL to download it.
                                            $answer = '<a href="'.Utils::getUploadURL($username, $formID, $sID, $answer).'">'.$answer.'</a>';
                                        }
                                         
                                        $alt = ($x % 2 != 0) ? "#f9f9f9" : "white";
                                         
                                        echo "<tr>
                                                <td bgcolor='".$alt."' style='padding:5px !important;' width=170>".$question."</td>
                                                <td bgcolor='".$alt."' style='padding:5px !important;'>".nl2br(stripslashes($answer))."</td>
                                        </tr>";
                                        $x++;
                                    }
                                    ?>
                                </table>
                            </td>
                            <td width="4" background="<?php echo $url; ?>images/win2_right.gif">
                            </td>
                        </tr>
                        <tr>
                            <td height="4" background="<?php echo $url; ?>images/win2_foot_left.gif">
                            </td>
                            <td background="<?php echo $url; ?>images/win2_foot.gif">
                            </td>
                            <td background="<?php echo $url; ?>images/win2_foot_right.gif">
                            </td>
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
