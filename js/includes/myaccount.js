// To stop the creation of globals. using currying.
var MyAccount = {
    // This is the error div just above the form submit button.
    accountInfoError: $('accountInfoError'),
    accountPassError: $('accountPassError'),
    
    checkEnter: function(e, formType) {
        var keyCode = e.keyCode;
        if (!e.isChar && keyCode == Event.KEY_RETURN) {
        	if (formType == 'accountPass') {
        		MyAccount.updateAccountPass();
        	} else {
        		MyAccount.updateAccountInfo();
        	}
        }
        return;
    },
    
    checkEmail: function(e) {
        var email = document.accountInfoForm.suEmail.value;
        if (e && email.blank()) {
            $('emailField').className = '';
            return false;
        }
        if (email.blank()) {
            $('emailField').className = 'error';
            $('e-errorMessage').update('Email cannot be left blank.'.locale());
            return false;
        }
        if (!CommonClass.checkEmailFormat(email))  {
            // Doesn't look like an e-mail address to me.
            $('emailField').className = 'error';
            $('e-errorMessage').update('Please provide a valid e-mail address.'.locale());
            return false;
        }
        $('emailField').className = 'correct';
        
        $('e-correctMessage').update('Email OK.'.locale());
        return true;
    },

    updateAccountInfo: function() {
    	if (!MyAccount.checkEmail()) {
    		MyAccount.accountInfoError.update('There are error(s) on the form.'.locale());
            return false;
        }
    	
    	// Remove any previous error messages.
    	MyAccount.accountInfoError.update('');
        
        var email = document.accountInfoForm.suEmail.value, name = document.accountInfoForm.suName.value, 
        	website = document.accountInfoForm.suWebsite.value, timeZone = $('suTimeZone').options[$('suTimeZone').selectedIndex].value;
        
        $('accountInfoBut').disable();

        Utils.Request({
            parameters: {
                action: 'updateUserAccount',
                email: email,
                name: name,
                website: website,
                timeZone:timeZone
            },
            onSuccess: function(response) {
                var Utils = Utils || new Common();
                Utils.alert("Your account has been updated.<br />".locale(), 
                            "Congratulations".locale());
                $('accountInfoBut').enable();
            },
            onFail: function(response){
                MyAccount.accountInfoError.update(response.error);
                $('accountInfoBut').enable();
            }
        });
    },
    
    checkOldPass: function(e) {
        var suPass = document.accountPassForm.suPass.value;
        
        if (e && suPass.blank()) {
            $('oldPassField').className = '';
            return false;
        }
        if (suPass.blank()) {
            $('oldPassField').className = 'error';
            return false;
        }
        $('oldPassField').className = 'correct';
        return true;
    },
    
    checkPass: function(e) {
        var pass = document.accountPassForm.suNewPass.value, 
        	passConf = document.accountPassForm.suNewPass2.value;
        if (e && pass.blank()) {
            $('newPassField').className = '';
            return false;
        }
        if (pass.blank()) {
        	$('p1-errorMessage').update('Password field cannot be left blank.'.locale());
            $('newPassField').className = 'error';
            return false;
        }
        if (!passConf.blank()) {
            // check to see if the passwords do match.
        	MyAccount.checkPassConf();
        }
        $('newPassField').className = 'correct';
        MyAccount.accountPassError.update('');
        return true;
    },
    
    checkPassConf: function(e) {
        var pass = document.accountPassForm.suNewPass.value, 
        	passConf = document.accountPassForm.suNewPass2.value;
        if (e && passConf.blank()) {
            $('newPassField2').className = '';
            return false;
        } else if (e && pass.blank()) {
            $('newPassField').className = '';
            return false;
        } 
        
        if (passConf.blank() && !pass.blank()) {
            $('newPassField2').className = 'error';
            $('p2-errorMessage').update('Please enter your password again.'.locale());
            return false;
        } else if (pass.blank()) {
            MyAccount.checkPass();
            return false;
        } else if (pass != passConf) {
            $('newPassField2').className = 'error';
            $('p2-errorMessage').update('Passwords do not match.'.locale());
            return false;
        }
        $('newPassField2').className = 'correct';
        $('p2-correctMessage').update('Password Confirmation OK.'.locale());
        MyAccount.accountPassError.update('');
        return true;
    },
    
    updateAccountPass: function() {
    	if (!MyAccount.checkPass() || !MyAccount.checkPassConf()) {
    		MyAccount.accountPassError.update('There are error(s) on the form.'.locale());
            return false;
        }
    	
    	// Remove any previous error messages.
    	MyAccount.accountPassError.update('');
        
        var pass = document.accountPassForm.suNewPass.value;

        $('accountPassBut').disable();

        Utils.Request({
            parameters: {
                action: 'updateUserAccount',
                password: pass
            },
            onSuccess: function(response) {
                
                var Utils = Utils || new Common();
                Utils.alert("Your password has been updated.<br />".locale(), 
                            "Congratulations".locale());
                $('accountPassBut').enable();
            },
            onFail: function(response){
                MyAccount.accountPassError.update(response.error);
                $('accountPassBut').enable();
            }
        });
    },
    
    cancel: function () {
    	window.location = "page.php?p=" + this.page;
    },
    
    initialize: function() {
	    // Start observing the events after the DOM is loaded.
	    document.observe('dom:loaded', function() {
	        // Only the e-mail field needs to be checked, all the others are optional.
	        Element.observe('suEmail', 'blur', MyAccount.checkEmail);
	        Element.observe(document.accountInfoForm.accountInfoBut, 'click', MyAccount.updateAccountInfo);
	        Element.observe('suEmail', 'keypress', MyAccount.checkEnter);
	        Element.observe('suName', 'keypress', MyAccount.checkEnter);
	        Element.observe('suWebsite', 'keypress', MyAccount.checkEnter);
	        Element.observe('suTimeZone', 'keypress', MyAccount.checkEnter);
	        
	        Element.observe('suNewPass', 'keypress', function(e) { MyAccount.checkEnter(e, 'accountPass'); });
	        Element.observe('suNewPass2', 'keypress', function(e) { MyAccount.checkEnter(e, 'accountPass'); });
	        Element.observe('suNewPass', 'blur', MyAccount.checkPass);
	        Element.observe('suNewPass2', 'blur', MyAccount.checkPassConf);
	        Element.observe(document.accountPassForm.accountPassBut, 'click', MyAccount.updateAccountPass);
	        //Element.observe( $('cancelBut'), 'click', MyAccount.redirect.bind({page: 'cancel'}) );
	    });
    }
};

MyAccount.initialize();
var Utils = new Common();
