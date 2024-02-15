<?php

# no idea why, but this doesn't work with the name RequestServer....


?>
<div id="passwordResetPage" style="padding: 0em 2em; margin-left: auto; margin-right: auto;">
    <div id="greetingHdr">
        <h2 class="locale">
        You can reset your password using this form:
        </h2>
    </div>
    <div id="passwordResetBox" style="margin-left: auto; margin-right: auto; padding: 1em;">
        <form id="passwordResetForm" onsubmit='return false;'>
            <div id="passwordField">
                <label for="newPassword" class="locale">
                    New Password
                </label>
                <input type="password" id="newPassword" name="newPassword" tabindex="1" autocomplete='off' />
                <div id="passwordFieldMessage" class="fieldMessage">
                    <div class="errorMessage">
                        <img src="images/blank.gif" class="validationImage index-cross" />
                        <span class="locale">
                            Password cannot be left blank.
                        </span>
                    </div>
                    <div class="correctMessage">
                        <img src="images/tick.png" class="validationImage" />
                        <span class="locale">
                            Password OK.
                        </span>
                    </div>
                </div>
            </div>
            <div id="passwordConfField">
                <label for="newPasswordConf" class="locale">
                    Verify Password
                </label>
                <input type="password" id="newPasswordConf" name="newPasswordConf" tabindex="2" autocomplete='off' />
                <div id="passwordConfFieldMessage" class="fieldMessage">
                    <div class="errorMessage">
                        <img src="images/blank.gif" class="validationImage index-cross" />
                        <span>
                        </span>
                    </div>
                    <div class="correctMessage">
                        <img src="images/tick.png" class="validationImage" />
                        <span class="locale">
                            Password Confirmation OK.
                        </span>
                    </div>
                </div>
            </div>
            <div id="passwordResetError" class="error">
            </div>
            <div id="formButtonDiv">
                <input id="passwordResetButton" class="locale" type="button" style="height: 2.5em; font-size: inherit;" tabindex="3" autocomplete='off' value="Reset My Password" />
            </div>
        </form>
    </div>
</div>