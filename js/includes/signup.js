// To stop the creation of globals. using currying.
Signup = {
    usernameKeyCount: 0,
    emailKeyCount: 0,
    initialize: function() {
        // This is the form in question.
        this.registrationForm = $('registrationForm');
        // This is the error div just above the form submit button.
        this.registrationError = $('registrationError');
        this.suUsernameEl = $('suUsername');
        this.suEmailEl = $('suEmail');
        // Allow these in usernames.
        this.usernameAllowedCharsRegex = /[a-zA-Z0-9_]/;
        this.suUsernameField = $('usernameField');
        this.suEmailField = $('emailField');
        // This is how many miliseconds should be waited until a new key is pressed.
        this.KEY_BETWEEN_PAUSE = 500;
        Utils = window.Utils || new Common();
        
        // Start observing the events after the DOM is loaded.
        document.observe('dom:loaded', function() {
            // Give the focus to the first field (the username).
            Signup.suUsernameEl.focus();
            // Add the validation events.
            Element.observe('suUsername', 'keypress', Signup.checkKeypress );
            Element.observe('suUsername', 'keydown', Signup.checkBackspace );
            Element.observe('suUsername', 'blur', Signup.checkUsername );
            
            Element.observe('suEmail', 'blur', Signup.checkEmail);
            Element.observe('suEmail', 'keypress', Signup.checkEnter);
            
            Element.observe('suPassword', 'blur', Signup.checkPassword);
            Element.observe('suPassword', 'keypress', Signup.checkEnter);

            Element.observe('suPasswordConf', 'blur', Signup.checkPasswordConf);
            Element.observe('suPasswordConf', 'keypress', Signup.checkEnter);
            
            Element.observe('signupButton', 'click', Signup.signup);
        });
    },
    
    checkUsername: function(e) {
        var username = Signup.suUsernameEl.value;
        if (e && username.blank()) {
            return;
        }
        // If a username availibility is being done wait a sec.
        if (Signup.suUsernameField.hasClassName('neutral')) {
            setTimeout(Signup.checkUsername, Signup.KEY_BETWEEN_PAUSE);
            return;
        }
        if (username.blank()) {
        	Signup.suUsernameField.className = 'error';
            $$('#usernameFieldMessage span')[0].update('Username cannot be left blank.'.locale());
            return false;
        } else if (Signup.suUsernameField.hasClassName('error')) {
            return false;
        }
        
        Signup.suUsernameField.className = 'correct';
        $$('#usernameFieldMessage span')[1].update('Username OK.'.locale());
        return true;
    },
    
    checkEnter: function(e) {
	    var keyCode = e.keyCode;
	    if (!e.isChar && keyCode == Event.KEY_RETURN) {
	    Signup.signup();
	    }
	    return;
	},
    
    checkKeypress: function(e) {
        var charPressed = "";
        if (e && e.which == null) {
            charPressed = String.fromCharCode(e.keyCode); // IE
        } else if (e && e.which != 0 && e.which != Event.KEY_TAB && e.charCode != 0) {
            charPressed = String.fromCharCode(e.which); // Others
        } else if(e) {
            // The pressed key is a special key.
            if (e.keyCode == Event.KEY_RETURN) {
                // Key codes corresponding to non printable characters. 
                // 8 -> BACKSPACE, 9 -> TAB, 13 -> ENTER. Using Prototype definitions here.
                Signup.signup();
            }
            return;
        }

        if (charPressed.match(Signup.usernameAllowedCharsRegex) === null) {
            // There are characters not allowed here.
            e.stop();
            return;
        }
        // Put a spinner to show we are working on it.
        Signup.suUsernameField.className = 'neutral';
        // Wait to see if there is another event, ie. if the user is 
        // typing continuously.
        Signup.usernameKeyCount++;
        setTimeout(function() { Signup.checkAvailibility(e); }, Signup.KEY_BETWEEN_PAUSE); // Wait half a sec.
    },
    
    /**
     * This method is only for Apple's Safari, backspace there generates a 
     * keydown event only, not keypress as Gecko does.
     * @param {Object} event
     */
    checkBackspace: function(e) {
        // Not interested in keys other than backspace.
        if (e.keyCode != Event.KEY_BACKSPACE) {
            return;
        }
        // Put a spinner to show we are working on it.
        Signup.suUsernameField.className = 'neutral';
        // Wait to see if there is another event, ie. if the user is 
        // typing continuously.
        Signup.usernameKeyCount++;
        setTimeout(function() { Signup.checkAvailibility(e); }, Signup.KEY_BETWEEN_PAUSE); // Wait half a sec.
    },
    
    checkAvailibility: function(e) {
        Signup.usernameKeyCount--;
        if (Signup.usernameKeyCount !== 0) {
            // There's a newer event out there. Let him handle this.
            return;
        }
        
        var username = Signup.suUsernameEl.value;
        if (username.blank()) { 
            Signup.suUsernameField.className = '';
            return; 
        }
        // Go check using Ajax.
        Utils.Request({
            parameters: {
                action: 'checkUsernameAvailable',
                username: username
            },
            onSuccess: function(response) {
                Signup.suUsernameField.className = 'correct';
                $$('#usernameFieldMessage span')[1].update('Username Available.'.locale());
            },
            onFail: function(){
                Signup.suUsernameField.className = 'error';
                $$('.errorMessage span')[0].update('Username Not Available.'.locale());
            }
        });
    },
    
    checkEmail: function(e) {
        var email = Signup.suEmailEl.value, syncRequest = false;
        if (e && email.blank()) {
            $('emailField').className = '';
            return false;
        }
        if (email.blank()) {
            $('emailField').className = 'error';
            $$('#emailFieldMessage span')[0].update('Email cannot be left blank.'.locale());
            return false;
        }
        if (!CommonClass.checkEmailFormat(email))  {
            // Doesn't look like an e-mail address to me.
            $('emailField').className = 'error';
            $$('#emailFieldMessage span')[0].update('Please provide a valid e-mail address.'.locale());
            return false;
        }
        
        // See if it was an event fired.
        if (typeof e != "undefined") {
            // Show a spinner - we are working on it.
            Signup.suEmailField.className = 'neutral';
            // Check that this e-mail has not been registered before; using Ajax.
            
            Utils.Request({
                parameters: {
                    action: 'checkEmailAvailable',
                    email: email
                },
                onSuccess: function(response){                    
                    Signup.suEmailField.className = 'correct';
                    $$('#emailFieldMessage span')[1].update('Email OK.'.locale());
                },
                onFail: function(){
                    Signup.suEmailField.className = 'error';
                    $$('#emailFieldMessage span')[0].update('This email has registered before. Please use a new one.'.locale());
                }
            });
        } else if(Signup.suEmailField.hasClassName('error')) { 
        	// No event was fired, there are no changes to the e-mail field.
        	// Return any previous errors.
        	return false;
        }
        return true;
    },
    
    checkPassword: function(e) {
        var password = Signup.registrationForm.suPassword.value, passwordConf = Signup.registrationForm.suPasswordConf.value;
        if (e && password.blank()) {
            $('passwordField').className = '';
            return false;
        }
        if (password.blank()) {
            $('passwordField').className = 'error';
            return false;
        }
        if (!passwordConf.blank()) {
            // check to see if the passwords do match.
            Signup.checkPasswordConf();
        }
        $('passwordField').className = 'correct';
        return true;
    },
    
    checkPasswordConf: function(e) {
        var password = Signup.registrationForm.suPassword.value, passwordConf = Signup.registrationForm.suPasswordConf.value;
        if (e && passwordConf.blank()) {
            $('passwordConfField').className = '';
            return false;
        } else if (e && password.blank()) {
            $('passwordField').className = '';
            return false;
        } 
        
        if (passwordConf.blank() && !password.blank()) {
            $('passwordConfField').className = 'error';
            $$('#passwordConfFieldMessage span')[0].update('Please enter your password again.'.locale());
            return false;
        } else if (password.blank()) {
            Signup.checkPassword();
            return false;
        } else if (password != passwordConf) {
            $('passwordConfField').className = 'error';
            $$('#passwordConfFieldMessage span')[0].update('Passwords do not match.'.locale());
            return false;
        }
        $('passwordConfField').className = 'correct';
        $$('#passwordConfFieldMessage span')[1].update('Password Confirmation OK.'.locale());
        return true;
    },
    
    signup: function(e) {
        // If there is an error, do not create the user.
        if (!(Signup.checkUsername(e) && Signup.checkEmail() && Signup.checkPassword(e) && Signup.checkPasswordConf(e))) {
            Signup.registrationError.update('There are error(s) on the form.'.locale());
            return false;
        }
		
		$('registrationForm').hide();
		$('registrationForm').insert({before: new Element('h3', {id:'please-wait-text'}).update('<img src="'+Utils.HTTP_URL+'images/loader-big.gif" /><br />'+'Please Wait...'.locale()).setStyle('text-align:center;')});
        // Remove any previous error messages.
        Signup.registrationError.update('');
        
        var username = Signup.suUsernameEl.value, email = Signup.suEmailEl.value, password = Signup.registrationForm.suPassword.value;
        
        $('signupButton').disable();
        
        Utils.Request({
            parameters: {
                action: 'registerNewUser',
                username: username,
                email: email,
                password: password
            },
            asynchronous: false,
            onSuccess: function(response) {
                $('signupButton').enable();
                $('please-wait-text').update("Congratulations!".locale() + '<br />' + "Your account has been created.".locale() + "<br /><br />");
                if(!document.APP){
                    $('please-wait-text').insert(new Element('button', {
                        type: 'button',
                        className: 'big-button buttons'
                    }).update('Start Creating Forms'.locale()).setStyle('font-size:18px').observe('click', function() {
                        window.location.replace(Utils.HTTP_URL);
                    }));                    
                }else{
                    $('please-wait-text').insert(new Element('button', {
                        type: 'button',
                        className: 'big-button buttons'
                    }).update('Create another account'.locale()).setStyle('font-size:18px').observe('click', function() {
                        location.reload(true);
                    }));
                }
				
                // if there is a referer hidden input, than redirect
                // to the referer after login.
                if ( $('login_referer') && $('login_referer').value ){
                	Utils.redirect($('login_referer').value);
                	return;
                }
				/*
                Utils.alert("Your account has been created.".locale() + "<br /><br />" + "You can now create new forms.".locale(), 
                            "Congratulations".locale(), callback);
                            */
            },
            onFail: function(response){
				$('registrationForm').show();
				$('please-wait-text').remove();
                Signup.registrationError.update(response.error);
            }
        });
    }
};

Signup.initialize();

