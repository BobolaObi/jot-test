<?php

use Legacy\Jot\UserManagement\Session;

# no idea why, but this doesn't work with the name RequestServer....


?>
<div id="signinFieldset" class="fieldsetAccount">
    <!-- label class="locale" style="padding:4px;">Welcome <b title="<?=Session::$username?>">Guest</b>, Sign In to Your Account</label-->
    <fieldset class="accountField">
        <legend>
            <span class="locale">Welcome</span> 
            <b class="locale" title="<?=Session::$username?>">Guest</b>, 
            <span class="locale">Sign In to Your Account</span>
        </legend>
        <div class="login-form">
            <div class="account-line">
                <label class="locale" for="username">Username</label>
                <input type="text" class="login-field index-login-back" name="username" size="13" id="username" tabindex="6" />
                <div id="usernameErrorDiv" class="error-div"></div>
            </div>
            
            <div class="account-line">
                <label class="locale" for="password">Password</label>
                <input type="password" class="login-field index-login-back" size="13" name="password" id="password" tabindex="7" />
                <div id="passwordErrorDiv" class="error-div"></div>
            </div>
            <div id="error-box" class="error-div"></div>
            <div style="float:left; margin:8px;">
                <input type="checkbox" id="remember" checked="checked" tabindex="8" /><label class="locale" for="remember">Remember Me</label>
            </div>
            <div class="account-line account-line-nolabel" style="float: right;padding-right: 7px;">
                <input id="loginButton" type="button" class="big-button buttons buttons-dark locale-button login-button" value="Sign-In" tabindex="9" />
            
                <input id="forgotPasswordButton" type="button" class="big-button buttons buttons-dark locale-button login-button" value="Forgot Password?" tabindex="10" />            
            </div>
        </div>
    </fieldset>
</div>

<div id="forgotPasswordFieldset" class="fieldsetAccount">
    <fieldset class="accountField">
    <legend>Forgot Password?</legend>
    <div id="forgotPasswordDiv" class="login-form">
        <div class="account-line">
            <label class="locale" for="resetData">Remember your Username or E-mail?</label>
            <input type="text" class="login-field index-login-back" id="resetData" tabindex="11" size="15" />
        </div>
        <div  class="account-line account-line-nolabel">
            <input type="button" id="passResetButton" class="big-button buttons buttons-dark locale-button login-button" value="Send Reset Instructions" tabindex="13" />
            <input type="button" id="returnLoginBox" class="big-button buttons buttons-dark locale-button login-button" value="Go Back" tabindex="12" />
        </div>

        <div style="clear: both"></div>
    </div>
    </fieldset>
</div>

<? if(!APP){ ?>
<div id="signUpFieldset" class="fieldsetAccount">
    <div style="clear:both;"></div>
    <!--label style="padding:4px;" class="locale">New Here?</label-->
    <fieldset class="accountField">
    <legend class="locale">New Here?</legend>
    <div style="text-align:center; padding:5px; margin-top:5px;">
        <input onclick="location.href='page.php?p=signup';" type="button" id="createAccount" class="big-button buttons buttons-red locale-button create-button" value="Create an Account - It's Free!!" style="font-size: 1.2em;" tabindex="13" />
    </div>
    </fieldset>

	<br/><br/>
</div>
<? } ?>