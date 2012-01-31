<?php
    include "../lib/init.php";
    Session::checkAdminPages(true);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Check Phishing</title>
        <link href="../css/includes/admin.css?<?=VERSION?>" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/includes/phishing.css?<?=VERSION?>" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/prototype.js"></script>
        <script type="text/javascript" src="../js/protoplus.js"></script>
        <script type="text/javascript" src="../js/protoplus-ui.js"></script>
        <script type="text/javascript">
	        var url = "<?=HTTP_URL?>";
	        var pre_open = "<?=isset($_GET['formID']) ? $_GET['formID'] : 'no' ?>";
        </script>
        <script type="text/javascript" src="../js/phishing.js?<?=VERSION?>"></script>
    </head>
    <body>
        <div id="admin-content">
	        <center>
	        <table width="1000">
	            <tr>
	                <td align="left">
	                    <h3>
	                       <img src="/images/phishing/judge.png" align="absmiddle">Phishing Detector
	                    </h3>
	                    <hr/>
	                </td>
	            </tr>
	        </table>
	        <table width="1000">
	            <tr>
	                <td align="left" width="100">
	                    <form name="querytypeform" onchange="submitFormSpam();" style="margin:0px;">
	                        <select class="big-select" name="querytype" id="querytype">
	                           <!-- 
	                            <option value="Today">Today</option>
	                            <option value="ThisWeek">This Week</option>
	                            <option value="Random">Random</option>
	                            <option value="Suspicious">Suspicious</option> -->
	                            <option value="Undecided" selected="yes">Undecided</option>
	                        </select>
	                    </form>
	                </td>
	                <td align="left">
	                    <input class="big-box" type="text" id="formID" value="Form ID" size="12" />
	                </td>
	                <td align="center">
	                    <div id="pbar-wrap">
	                        <span id="spamProb" ></span>
	                        <div id="pbar-bar" style="width:0%">&nbsp;</div>
	                    </div>
	                </td>
	                <td align="right">
	
	                    <button class="big-button" id="spamButton" onclick="submitFormSpam('Spam');">
	                        <img src="/images/phishing/spam.png" /><br />
	                        Spam
	                    </button>
	                    <button class="big-button" id="notspamButton" onclick="submitFormSpam('NotSpam');">
	                        <img src="/images/phishing/notspam.png" /><br />
	                        Good
	                    </button>
	                    <button class="big-button" id="ignoreButton" onclick="submitFormSpam('Ignore');">
	                        <img src="/images/phishing/ignore.png"><br>
	                        Skip
	                    </button>
	                </td>
	            </tr>
	        </table>
	        <div id="form">
	            <table width="1000">
	                <tr>
	                    <td>
	                        <fieldset style="background:#fff; position:relative">
	                            <legend id="formtitle"> </legend>
	                            <div id="progress_bar">
	                                Working... 
	                            </div>
	                            <iframe id="formframeurl" src="" frameborder="0" style="width:100%; height:480px; border:none; background:#fff;" scrolling="auto">
	                            </iframe>
	                            <div id="responseTD">&nbsp;</div>
	                        </fieldset>
	                    </td>
	                </tr>
	            </table>
	        </div>
	        <table width="1000">
	            <tr>
	                <td align="center">
	                    <button class="big-button" onclick="whitelistUser();">
	                        <img id="whitelist_icon" src="/images/phishing/whitelist.png"><br>
	                        Whitelist User
	                    </button>
	                
	
	                    <button class="big-button" onclick="location.href='detect_spam.php'">
	                        <img src="/images/phishing/detect.png"><br>
	                        Detect Spam
	                    </button>
	                
	                    <button onclick="suspendForms();" class="big-button">
	                        <img id="suspend_icon" src="/images/phishing/suspend.png"><br>
	                        Suspend Forms
	                    </button>
	                </td>
	            </tr>
	        </table>
	        </center>
        </div>
    </body>
</html>
