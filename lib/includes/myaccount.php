<?php
if ( (!Session::isLoggedIn() && isset($_GET['upgraded'])) || (isset($_GET['username']) && Session::$username !== $_GET['username']) ):
?>
<div id="myAccountPage" style="padding: 0em 2em; margin-left: auto; margin-right: auto;">
	<div style="border:5px double #FFFFFF;line-height:21px;color:#000000;text-align:center;font-size:16px;margin:45px 0 30px;padding:40px 0 50px;background-color:rgba(50, 200, 0, 0.5);">
		<div style="padding-bottom:15px;"><b style="font-size:24px;">Thank You!</b><br/></div>
		Your Account <?=$_GET['username']?> has been upgraded to JotForm Professional successfully.<br/>
		You will be charged for the new amount when your current term ends.<br/>
	</div>
</div>
<?php
else:
?>
<?php 
$accountType = AccountType::find(Session::$accountType)
?>
<div id="myAccountPage" style="padding: 0em 2em; margin-left: auto; margin-right: auto;">
	<?php
	if (isset($_GET['upgraded'])):
	?>
	<div style="border:5px double #FFFFFF;line-height:21px;color:#000000;text-align:center;font-size:16px;margin:45px 0 30px;padding:40px 0 50px;background-color:rgba(50, 200, 0, 0.5);">
		<div style="padding-bottom:15px;"><b style="font-size:24px;">Thank You!</b><br/></div>
		Your Account has been upgraded to JotForm Professional successfully.<br/>
		You will be charged for the new amount when your current term ends.<br/>
	</div>
    <?php
    endif;
	?>
	<div id="accountBox">
        <h2>Account Status</h2>
            <div id="usernameField">
                <label for="suUsername" class="locale formLabel">
                    Username
                </label>
                <div id="usernameFieldMessage" class="answer">
				    <?=Session::$username ?>
                </div>
            </div>
            <div id="accountTypeField">
                <label for="suAccountType" class="locale formLabel">
                    Account Type
                </label>
                <div id="accountTypeMessage" class="answer">
				    <?= $accountType->prettyName ?>
		            <?php
		            if ($accountType->name === "FREE"):
		            ?>
		            <form action="/pricing" method="get" style="display:inline-block;">
		                <input type="submit" value="Upgrade To Premium" autocomplete="off" style="font-size: inherit;"
		                    class=" big-button buttons" id="upgradeButton" />
		            </form>
		            <?php
		            endif;
		            ?>
                </div>
            </div>
            <?php
            $warn = "";
            $cancel = false;
            $resubscribe = false;
            if (($info = JotFormSubscriptions::getExpireDate(Session::$username)) !== false ):
                if ($info['type'] !== "expire"){
                	$text = "Expire Date";
                    if ($info['remain'] < 7){
                        $warn = "warn";
                        $resubscribe = true;
                    }
                }else{
                    $text = "Next Billing";
                    $cancel = true;
                }
            ?>
            <div id="accountTypeField" class="<?=$warn?>">
                <label for="suAccountType" class="locale formLabel">
                    <?=$text?>
                </label>
                <div id="accountTypeMessage" class="answer">
                    <?=$info['date']?>
                    <?php
                    # Show cancel button if there is an active subscription.
                    if ($cancel === true):
	                    # Get the payment type of the logged in user.
						$username = Session::$username;
						
						$subscriptions = new JotFormSubscriptions();
						$subscriptions->setUser($username);
						$info = $subscriptions->getLastPaymentType();
                        if ($info['gateway'] === "PAYPAL"):
					?>
                        <form action="https://www.paypal.com/cgi-bin/webscr" style="display:inline-block;" method="get">
                            <input type="hidden" name="cmd" value="_subscr-find" />
                            <input type="hidden" name="alias" value="billing%40interlogy.com" />
                            <input type="submit" value="Cancel" autocomplete="off" style="font-size: inherit;"
                                class=" big-button buttons" id="cancelBut" />
                        </form>
					<?php
    					elseif ($info['gateway'] === "PLIMUS"):
					?>
                        <form action="https://secure.plimus.com/jsp/account_login.jsp" method="get" style="display:inline-block;">
                            <input type="hidden" name="username" value="<?=$info['plimusUsername']?>" />
                            <input type="hidden" name="p" value="cancel" />
                            <input type="submit" value="Cancel" autocomplete="off" style="font-size: inherit;"
                                class=" big-button buttons" id="cancelBut" />
                        </form>
					<?php
	       				endif;
					?>
                    <?php
                    endif;
                    # Show re-subscribe button if account is being expire.
                    if ($resubscribe === true):
                    ?>
	                    <form action="/pricing" style="display:inline-block;">
		                    <input type="submit" value="Re-subscribe" autocomplete="off" style="font-size: inherit;"
		                        class=" big-button buttons" id="reSubscribeBut" />
	                    </form>
                    <?php
                    endif;
                    ?>
                </div>
            </div>
            <?php
            endif;
            ?>
        <h2>Change Your Password</h2>
        <form name="accountPassForm" id="accountPassForm" onsubmit='return false;'>
            <!-- div id="oldPassField">
                <label class="locale formLabel" for="suPass">Old Password</label>
                <div>
                    <input type="password" id="suPass" name="suPass" autocomplete="off" />
                </div>
                <div id="oldPassFieldMessage" class="fieldMessage">
                    <div id="errorMessage"></div>
                    <div id="correctMessage"></div>
                    &nbsp;
                </div>
            </div-->
            <div id="newPassField">
                <label class="locale formLabel" for="suNewPass">New Password</label>
                <div>
                    <input type="password" id="suNewPass" name="suNewPass" autocomplete="off" />
                </div>
                <div id="newPassFieldMessage" class="fieldMessage">
                    <div id="p1-errorMessage"></div>
                    <div id="p1-correctMessage"></div>
                    &nbsp;
                </div>
            </div>
            <div id="newPassField2">
                <label class="locale formLabel" for="suNewPass2">New Password Again</label>
                <div>
                    <input type="password" id="suNewPass2" name="suNewPass2" autocomplete="off" />
                </div>
                <div id="newPassField2Message" class="fieldMessage">
                    <div id="p2-errorMessage"></div>
                    <div id="p2-correctMessage"></div>
                    &nbsp;
                </div>
            </div>
            <div>
                <input id="accountPassBut" class="locale big-button buttons" type="button" style="font-size: inherit;" tabindex="5" autocomplete='off' value="Change My Password" />
            </div>
            <div id="accountPassError" class="error"></div>
        </form>
    </div>
    <div id="passBox">
	    <h2 class="locale">Update Account Info</h2>
        <form name="accountInfoForm" onsubmit='return false;'>
            
            <div id="emailField">
                <label for="suEmail" class="locale formLabel">
                    Email
                </label>
                <div>
                    <input type="text" id="suEmail" name="suEmail" tabindex="1" autocomplete='off' value="<?= Session::$email? Session::$email : "" ?>" />
                </div>
                <div id="emailFieldMessage" class="fieldMessage">
                    <div id="e-errorMessage"></div>
                    <div id="e-correctMessage"></div>
                    &nbsp;
                </div>
            </div>
            <div id="nameField">
                <label for="suName" class="locale formLabel">
                    Your Name
                </label>
                <div>
                    <input type="text" id="suName" name="suName" tabindex="1" autocomplete='off' value="<?= Session::$name? Session::$name : "" ?>" />
                </div>
            </div>
            <div id="websiteField">
                <label for="suWebsite" class="locale formLabel">
                    Web Site
                </label>
                <div>
                    <input type="text" id="suWebsite" name="suWebsite" tabindex="1" autocomplete='off' value="<?= Session::$website? Session::$website : "" ?>" />
                </div>
            </div>
            <div id="timeZoneField">
                <label for="suTimeZone" class="locale formLabel">
                    Time Zone
                </label>
                <div>
                    <select id="suTimeZone" name="suTimeZone" tabindex="1" />
                        <?=TimeZone::createDropdownOptions(Session::$timeZone); ?>
                    </select>
                </div>
            </div>
            <div>
                <input id="accountInfoBut" class="locale big-button buttons" type="button" style="font-size: inherit;" tabindex="5" autocomplete='off' value="Update My Account" />
            </div>
            <div id="accountInfoError" class="error">
            </div>
        </form>
    </div>
</div>
<?php
endif;
?>
