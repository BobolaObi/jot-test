<?php 
include "../lib/init.php";
Session::checkAdminPages(true);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Search Database</title>
        <link href="../css/includes/admin.css?<?=VERSION?>" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/buttons.css?<?=VERSION?>" rel="stylesheet" type="text/css" media="screen" />
        <script src="../js/prototype.js?<?=VERSION?>" type="text/javascript"></script>
        <script src="../js/protoplus.js?<?=VERSION?>" type="text/javascript"></script>
        <script src="../js/admindetails.js?<?=VERSION?>" type="text/javascript">new AdminDetails();</script>
    </head>
    <body style="background: #eee;">
        <div id="adminDetails">
            <div id="dimmer">
                <img src="../images/loading_big.gif"/>
            </div>
            <div id="content" class="adminDetailsPage" style="display:none;">
                <?php
                $username = isset($_GET["username"]) ? $_GET["username"] : false;
                $keyword  = isset($_GET["keyword"])  ? $_GET["keyword"]  : false;
                $user = User::find($username);

                if (preg_match ('/^\d+$/', trim(''.$keyword))){
                    $isKeywordFormId =  true;
                }else{
                    $isKeywordFormId = false;
                }
                
                $formCount = 10;
                
                if ($user){

                    $referer = false;
                    if ($user->referer !== false && trim(''.$user->referer) ){
                        if(strlen($user->referer)>10){
                            $referer = substr($user->referer, 0, 25);
                            if(strlen($user->referer)>25){
                                $referer .= "...";
                            } 
                        }
                        $referer = "<a href='{$user->referer}' target='_blank'>$referer</a>";
                    }else{
                        $referer = "-";
                    }
                    
                    # Look if user is overlimited or scheduled to downgrade.
                    $res = DB::read("SELECT * FROM `scheduled_downgrades` WHERE `username` = ':s'", 
                                    $user->username);
                    if ($res->rows === 1){
                        $isScheduledToDonwgrade = ["date"=>substr($res->first['eot_time'], 0, 11), "reason"=>strtoupper($res->first['reason'])];
                    }else{
                        $isScheduledToDonwgrade = false;
                    }
                    
                    # If user is suspendend, autosuspended, deleted or overlimited, show activate user button.
                    if ($user->status !== "ACTIVE"){
                        $activateButton = true;
                        if (stristr($user->status, "suspend") === false){
                        	$buttonAction = "activateUser";
                        }else{
                            $buttonAction = "unsuspendUser";
                        }
                    }else{
                        $activateButton = false;
                    }
                    
                    ?>
                    <table>
                        <tr>
                            <td valign="top">
                                <table class="informationTable" id="userDetailsTable">
                                    <thead>
                                        <tr>
                                            <th colspan="2">
                                                <span style="font-size:20px;color:#000;"><?=$user->username?></span>
                                                <hr/>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($activateButton === true):
                                        ?>
                                        <tr id="activateWarning">
                                            <td colspan="2" style="padding-bottom:10px;">
                                                <span class="warning">
                                                    <button id="<?=$buttonAction?>" style="cursor:pointer;">
                                                        <img src="../images/gear.png" style="vertical-align:middle;"/> Activate User
                                                    </button><br/>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                        endif;
                                        ?>
                                    
                                        <?php
                                        if ($isScheduledToDonwgrade !== false):
                                        ?>
                                        <tr id="overlimitWarning">
                                            <td colspan="2" style="padding-bottom:10px;">
                                                <span class="warning">
                                                    <button id="removeFromScheduledDowngrade" style="cursor:pointer;">
                                                        <img src="../images/cross.png" style="vertical-align:middle;"/> Remove downgrade
                                                    </button><br/>
                                                    <span style="font-size:12px;">
                                                    User is scheduled to downgrade.<br/>
                                                    Date: <?=$isScheduledToDonwgrade["date"]?><br/>
                                                    Reason: <?=$isScheduledToDonwgrade["reason"]?>
                                                    </span> 
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                        endif;
                                        ?>
                                        <tr>
                                            <th>Account:</th>
                                            <td id="accountType"><?=$user->accountType?></td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <?php
                                            $setWarning = false;
                                            if ($user->status !== "ACTIVE"){
                                                $setWarning = "warning";
                                            }
                                            ?>
                                            <td id="status" class="<?=$setWarning?>"><?=$user->status?></td>
                                        </tr>
                                        <tr>
                                            <th>Created:</th>
                                            <td><?=substr($user->createdAt, 0, 11)?></td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td><?=wordwrap($user->email, 25, "<br/>", true)?></td>
                                        </tr>
                                        <tr>
                                            <th>LastIP:</th>
                                            <td>
                                                <?=trim(''.$user->ip) ? $user->ip : "-"?>
                                                <a style="color:#000;text-decoration:none;" href="<?=HTTP_URL."admin/?keyword=".urlencode(trim(''.$user->ip))?>" target="_blank">
                                                    <img border="0" src="../images/context-menu/preview.png" style="vertical-align:bottom;" />
                                                </a>
                                            </td>
                                        </tr>
                                        <!-- 
                                        <tr>
                                            <th>Browser:</th>
                                            <td><?=$user->getBrowser()?></td>
                                        </tr>
                                         -->
                                        <tr>
                                            <th>Referer:</th>
                                            <td><?=$referer?></td>
                                        </tr>
                                        <tr align="right">
                                            <td colspan="2">
                                                <button id="loginUsername">Login to <?=$user->username?></button>
                                            </td>
                                        </tr>
                                        <?php 
                                        if ($activateButton === false ):
                                        ?>
                                        <tr>
                                            <td colspan="2">
                                                <button id="suspendUser">Suspend <?=$user->username?></button>
                                            </td>
                                        </tr>
                                        <?php 
                                        endif;
                                        ?>
                                    </tbody>
                                </table>
                                
                                <?php 
                                $response = DB::read("SELECT * FROM `monthly_usage` WHERE `username`=':username'", $username);
                                if ($response->rows == 0) {
                                    $submissions = "0";
                                    $uploads = "0";
                                    $payments = "0";
                                    $ssl = "0";
                                    $support = "0";
                                }
                                
                                $montly = $response->first;
                                $submissions = $montly["submissions"];
                                $uploads = Utils::bytesToHuman((float) $montly["uploads"]);
                                $payments = $montly["payments"];
                                $ssl = $montly["ssl_submissions"];
                                $support = $montly["tickets"];
                                
                                $monthlyUsage = MonthlyUsage::find($user);
                                $overQuotes = $monthlyUsage->getOverQuota();
                                ?>
                                <table class="informationTable" id="monthlySubmissionsTable">
                                    <thead>
                                        <tr>
                                            <th colspan="2">
                                                <span>Limits</span>
                                                <hr/>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th>Submissions:</th>
                                            <td id='mu_submissions' class="<?=$overQuotes['submissions'] ? "warning" : ""?>"><?=$submissions?></td>
                                        </tr>
                                        <tr>
                                            <th>Uploads:</th>
                                            <td>
                                                <span id="upload-size" class="<?=$overQuotes['uploads'] ? "warning" : ""?>"><?=$uploads?></span>
                                                <img id="recalculateUploads" src="../images/arrow_refresh_small.png" height="12"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Payments:</th>
                                            <td id='mu_payments' class="<?=$overQuotes['payments'] ? "warning" : ""?>"><?=$payments?></td>
                                        </tr>
                                        <tr>
                                            <th>SSL:</th>
                                            <td id='mu_ssl_submissions' class="<?=$overQuotes['sslSubmissions'] ? "warning" : ""?>"><?=$ssl?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td valign="top">
                                <table class="informationTable" id="formsTable" >
                                    <thead>
                                        <tr>
                                            <th>
                                                <span>User Forms</span>
                                                <hr/>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <? 
                                        $countResponse = DB::read("SELECT count(*) as c ".
                                                        "FROM `forms` ".
                                                        "WHERE `username`=':username' ".
                                                        "AND `status` != 'DELETED' ".
                                                        "ORDER BY `updated_at`", $user->username);
                                        
                                        $totalFormCount = $countResponse->first['c'];
                                        
                                        $removeKeywordId = ($isKeywordFormId ? "AND `id` <> '".trim(''.$keyword)."' " : "");
                                        $response = DB::read("SELECT * ".
                                                 "FROM `forms` ".
                                                 "WHERE `username`=':username' ".
                                                 "AND `status` != 'DELETED' ".
                                                 $removeKeywordId . 
                                                 "ORDER BY `updated_at` DESC LIMIT 0, ".$formCount, $user->username);
                                        
                                        if ($isKeywordFormId){
                                            array_splice($response->result, 0, 0, [["id"=>$keyword]]);
                                        }
                                                 
                                        foreach ($response->result as $key => $line):
                                            $form = new Form($line['id']);
                                            
                                            # Get the style of the row. First determine if its odd or even,
                                            # and if its the searched form change the color to another thing.
                                            # Search form color has high priority.
                                            $style = ($key % 2) ? "even" : "odd";
                                            $style = (floatval($keyword) === $form->id) ? "searchedForm" : $style;
                                            
                                            # Create the phishing filter object for the form.
                                            $phishingFilter = new PhishingFilter($form->id);
                                            # Get the spam probability of the form and convert to it a human
                                            # readable format.
                                            $spamProb = number_format( $phishingFilter->getSpamProb() * 100, 2);
                                            
                                            # Keep the status of the form.
                                            # If form is whitelisted than status is whitelisted regardles the
                                            # form status.
                                            $formStatus = "";
                                            if ($phishingFilter->isWhiteListed()){
                                                $formStatus = "whitelisted";
                                            }else{
                                                $formStatus = "";
                                            }

                                            # If the spam probability is higher than .95
                                            # mark the form red.
                                            $spamHighClass = "";
                                            if ($spamProb > 95) {
                                                $spamHighClass = "highProb";
                                            }
                                        ?>
                                        <tr class="<?=$style?>">
                                            <td>
                                                <a style="color:#000;text-decoration:none;" href="<?=HTTP_URL?>form/<?=$form->id?>" target="_blank">
                                                    <img border="0" src="../images/context-menu/preview.png" style="vertical-align:bottom;" />
                                                    <b><?=$form->form['title']?></b>
                                                </a>                                                
                                                <br/>

                                                <? #if ($form->form['count'] > 0): ?>
                                                <div class="subDetail">Submissions: <b><?= $form->form['count']?></b>&nbsp;</div>
                                                <? #endif; ?>

                                                <?php
                                                $uploadRes = DB::read(  "SELECT count(*) as c FROM `upload_files` ".
                                                                        "WHERE `username` = ':s' AND `uploaded` = 1 ".
                                                                        "AND `form_id` = '#s'", $user->username, $form->id);
                                                #if (intval($uploadRes->first['c']) > 0):
                                                ?>
                                                <div class="subDetail">Uploads: <b><?=$uploadRes->first['c']?></b> &nbsp;</div>
                                                <?php
                                                #endif;
                                                ?>

                                                <div class="subDetail">
                                                    Phishing:<b><a style="color:#646464;text-decoration:none;" href="<?=HTTP_URL?>admin/checkPhishing.php?formID=<?=$form->id?>" target="_blank" class="<?=$spamHighClass?>">
                                                        <?=$spamProb?>%
                                                    </a></b>&nbsp;&nbsp;
                                                </div>
                                                
                                                <div class="subDetail">
                                                    Form ID: <b><?=$form->id?></b>&nbsp;&nbsp;
                                                </div>

                                                <div class="subDetail">
                                                Last Edit: <b><?=substr($form->form['updated_at'], 0, 11)?></b>
                                                </div>
                                                

                                                <div class="subDetail" style="float:right !important;text-align:right;">
                                                <?php
                                                if ($formStatus):
                                                ?>
                                                <?=$formStatus?>
                                                <?php 
                                                endif;
                                                ?>
                                                </div>
                                                
                                            </td>
                                        </tr>
                                        <?php 
                                        endforeach;
                                        if ($totalFormCount > $formCount):
                                        ?>  
                                        <tr>
                                            <td colspan="2">There are <?=($totalFormCount - $formCount)?> more forms.</td>
                                        </tr>
                                        <?php
                                        endif;
                                        if ( count($response->result) === 0):
                                        ?>
                                        <tr>
                                            <td>No form.</td>
                                        </tr>
                                        <?php
                                        endif;
                                        ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </table>


                    
                    <?php
                }else{
                    ?>
                    <div class="warning">Username is not found in DB.</div>
                    <?php
                }
                ?>
            </div>
        </div>
    </body>
</html>
