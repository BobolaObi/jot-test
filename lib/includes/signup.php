<?php

use Legacy\Jot\Utils\Utils;


?>
<?php 

if(APP && !defined('ALLOWSIGNUP')){
    Utils::errorPage("In order to create an acccount please contact to site administration", "Creating account is not allowed");
}

if (isset($_POST['registrationType'] ) ){
    $go_to = $_POST['registrationType'];
    ?>
    <input type="hidden" value="<?=$go_to?>" id="login_referer" />
    <?php
}
?>
<div id="signupPage" style="padding: 0em 2em; margin-left: auto; margin-right: auto;">
    <? if(!APP){ ?>
    <div id="greetingHdr">
        <h1 class="locale">Sign Up to JotForm!</h1>
    </div>
    <div id="greeting-subhdr">
        <h3 class="locale">Signing up is quick and easy and in about 10 seconds you'll be on your way to publishing your forms.</h3>
    </div>
    <? }else{ ?>
        <div id="greetingHdr">
            <h1 class="locale">Create Account</h1>
        </div>
        <style>
            #registrationBox{
                padding:0 !important;
            }
        </style>
    <? } ?>
    <div id="registrationBox" style="margin-left: auto; margin-right: auto; padding: 1em;">
        <form id="registrationForm" onsubmit='return false;'>
            <div id="usernameField">
                <div class="formLabel">
                    <label for="suUsername" class="locale">
                        Username
                    </label>
                </div>
                <input type="text" id="suUsername" name="suUsername" autocomplete='off' />
                <div id="usernameFieldMessage" class="fieldMessage">
                    <div class="errorMessage error">
                        <img src="images/blank.gif" class="validationImage index-cross" />
                        <span>
                        </span>
                    </div>
                    <div class="correctMessage correct">
                        <img src="images/blank.gif" class="validationImage index-tick" />
                        <span>
                        </span>
                    </div>
                    <div class="neutralMessage neutral">
                        <img src="images/ajax-loader.gif" class="validationImage" />
                        <span class="locale">
                            Checking if available...
                        </span>
                    </div>
                </div>
            </div>
            <div id="emailField">
                <div class="formLabel">
                    <label for="suEmail" class="locale">
                        Email
                    </label>
                </div>
                <input type="text" id="suEmail" name="suEmail" autocomplete='off' />
                <div id="emailFieldMessage" class="fieldMessage">
                    <div class="errorMessage">
                        <img src="images/blank.gif" class="validationImage index-cross" />
                        <span>
                        </span>
                    </div>
                    <div class="correctMessage">
                        <img src="images/blank.gif" class="validationImage index-tick" />
                        <span>
                        </span>
                    </div>
                    <div class="neutralMessage neutral">
                        <img src="images/ajax-loader.gif" class="validationImage" />
                        <span class="locale">
                            Checking if available...
                        </span>
                    </div>
                </div>
            </div>
            <div id="passwordField">
                <div class="formLabel">
                    <label for="suPassword" class="locale">
                        Password
                    </label>
                </div>
                <input type="password" id="suPassword" name="suPassword" autocomplete='off' />
                <div id="passwordFieldMessage" class="fieldMessage">
                    <div class="errorMessage">
                        <img src="images/blank.gif" class="validationImage index-cross" />
                        <span class="locale">
                            Password cannot be left blank.
                        </span>
                    </div>
                    <div class="correctMessage">
                        <img src="images/blank.gif" class="validationImage index-tick" />
                        <span class="locale">
                            Password OK.
                        </span>
                    </div>
                </div>
            </div>
            <div id="passwordConfField">
                <div class="formLabel" style="margin-top: -0.5em;">
                    <label for="suPasswordConf" class="locale">
                        Confirm Password
                    </label>
                </div>
                <input type="password" id="suPasswordConf" name="suPasswordConf" autocomplete='off' />
                <div id="passwordConfFieldMessage" class="fieldMessage">
                    <div class="errorMessage">
                        <img src="images/blank.gif" class="validationImage index-cross" />
                        <span>
                        </span>
                    </div>
                    <div class="correctMessage">
                        <img src="images/blank.gif" class="validationImage index-tick" />
                        <span class="locale">
                            Password Confirmation OK.
                        </span>
                    </div>
                </div>
            </div>
            <div id="registrationError" class="error">
            </div>
            <div>
                <input id="signupButton" class="locale-button big-button buttons" type="button" autocomplete='off' value="Create My Account" />
            </div>
        </form>
    </div>
</div>