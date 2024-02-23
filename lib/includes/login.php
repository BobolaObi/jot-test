<?php

use Legacy\Jot\Utils\Utils;



?>
<div id="myaccount" class="signin">
	<div class="barePage">
		<h1 class="locale">Login to JotForm</h1>
		<div id="loginBox">
		<?php
		$redirectPage = Utils::get('rp') !== false ? Utils::get('rp') :
		      ( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false );
		  
		if ( $redirectPage !== false ){
			?>
			<input type="hidden" value="<?=$redirectPage?>" id="login_referer" />
			<?php
		}
		?>
		<table class="loginForm" id="signinFieldset">
			<tbody>
				<tr>
					<th>
		                <label class="loginLabel locale" for="username">Username</label>
					</th>
					<td>
		                <input type="text" class="loginField" size="13" id="username"/>
		                <div id="usernameErrorDiv" class="error-div"></div>
					</td>
				</tr>
				<tr>
					<th>
		                <label class="loginLabel locale" for="password">Password</label>
					</th>
					<td>
                        <input type="password" class="loginField" size="13" id="password"/>
		                <div id="passwordErrorDiv" class="error-div"></div>
                        <div id="error-box" class="error-div"></div>
					</td>
				</tr>
				<tr class="lastRow">
					<td>
		                <input type="checkbox" id="remember" checked="checked"/>
		                <label class="locale" for="remember">Remember Me</label>
		            </td>
					<td align="right">
		                <input id="loginButton" type="button" class="big-button buttons locale-button" value="Sign-In"/>
					    <input type="button" value="Forgot Password?" class="big-button buttons locale-button"
                            id="forgotPasswordButton">
					</td>
				</tr>
            </tbody>
        </table>
        <table id="forgotPasswordFieldset" class="loginForm">
            <tr>
                <td>
			        <fieldset class="accountField">
			        <legend class="locale">Forgot Password?</legend>
			        <div id="forgotPasswordDiv" class="loginForm">
			            <div class="account-line">
			                <div class="locale" for="resetData" style="padding-bottom:5px;">Remember your Username or E-mail?</div>
			                <input type="text" class="loginField" id="resetData" tabindex="11" size="15" />
			            </div>
			            <div  class="account-line account-line-nolabel">
			                <input type="button" id="passResetButton" class="big-button buttons locale-button" value="Send Reset Instructions" tabindex="13" />
			                <input type="button" id="returnLoginBox" class="big-button buttons locale-button" value="Go Back" tabindex="12" />
			            </div>
			    
			            <div style="clear: both"></div>
			        </div>
			        </fieldset>
                </td>
            </tr>
        </table>
        <? if(!APP){ ?>
        <table class="loginForm">
            <tbody>
                <tr>
                    <td>
                        <div id="signUpFieldset" class="fieldsetAccount">
					        <div style="clear:both;">
					        </div>
					        <fieldset class="accountField">
						        <legend class="locale">New Here?</legend>
						        <div style="text-align:center; padding:5px; margin-top:5px;">
						            <input onclick="location.href='page.php?p=signup';" type="button" id="createAccount" class="big-button buttons buttons-red locale-button create-button" value="Create an Account - It's Free!!" style="font-size: 1.2em" tabindex="13" />
						        </div>
					        </fieldset>
					    </div>
				    </td>
				</tr>
			</tbody>
		</table>
        <? } ?>
		</div>
	</div>
</div>