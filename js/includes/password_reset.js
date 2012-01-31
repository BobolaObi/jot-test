(function() {
    var Utils = new Common();
	var newPasswordEl = $('newPassword');
	var newPasswordConfEl = $('newPasswordConf');
	var e = $('passwordResetError');
	
	function resetPassword() {
		if (!(checkPassword() && checkPasswordConf())) {
			e.update('There are error(s) on the form.'.locale());
			return false;
		}
		
		// Remove any previous error messages.
        e.update('');
        
        var toServer = {
        	action: 'resetPassword',
        	username: document.get.username,
        	password: newPasswordEl.value,
        	token: document.get.token
        };
        
        $('passwordResetButton').disable();
        
        Utils.Request({
            parameters: toServer,
            onSuccess: function(response) {
                
                var callback = function() {
                    window.location = Utils.HTTP_URL;
                };

                var Utils = Utils || new Common();
                Utils.alert("Your new password is saved.".locale() + "<br /><br />" + "You can now continue using JotForm.".locale(), 
                            "Congratulations".locale(), callback);
                            
                $('passwordResetButton').enable();
            },
            onFail: function(response){
                e.update(response.error);
            }
        });
	}
	
	function checkPassword(event) {
		var password = newPasswordEl.value;
		var passwordConf = newPasswordConfEl.value;
		if (event && password.blank()) {
            $('passwordField').className = '';
            return false;
        }
        if (password.blank()) {
            $('passwordField').className = 'error';
            return false;
        }
        if (!passwordConf.blank()) {
            // check to see if the passwords do match.
            checkPasswordConf();
        }
        $('passwordField').className = 'correct';
        return true;
	}
	
	function checkPasswordConf(event) {
		var password = newPasswordEl.value;
		var passwordConf = newPasswordConfEl.value;
        if (event && passwordConf.blank()) {
            $('passwordConfField').className = '';
            return false;
        } else if (event && password.blank()) {
            $('passwordField').className = '';
            return false;
        } 
        
        if (passwordConf.blank() && !password.blank()) {
            $('passwordConfField').className = 'error';
            $$('#passwordConfFieldMessage span')[0].update('Please enter your password again.'.locale());
            return false;
        } else if (password.blank()) {
            checkPassword();
            return false;
        } else if (password != passwordConf) {
            $('passwordConfField').className = 'error';
            $$('#passwordConfFieldMessage span')[0].update('Passwords do not match.'.locale());
            return false;
        }
        $('passwordConfField').className = 'correct';
        $$('#passwordConfFieldMessage span')[1].update('Password Confirmation OK.'.locale());
        return true;
	}
	
	function checkKeypress(event) {
        event = document.getEvent(event);
        var keyCode = event.keyCode;
        // If it is not the Return key, we are not interested.
        if (keyCode != Event.KEY_RETURN) {
            return;
        }
        // Check to verify the form.
        
        resetPassword();
    }
	
	document.observe('dom:loaded', function() {
		Element.observe('newPassword', 'keypress', checkKeypress);
		Element.observe('newPassword', 'blur', checkPassword);
        Element.observe('newPasswordConf', 'keypress', checkKeypress);
        Element.observe('newPasswordConf', 'blur', checkPasswordConf);
		Element.observe('passwordResetButton', 'click', resetPassword);
	});
})();