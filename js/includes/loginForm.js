// Don't wanna mess with the global namespace.
(function() {
    
    if('loginIncluded' in window){
        return; // Login.js was already included
    }
    
    window.loginIncluded = true;
    
    var Utils = window.Utils || new Common();
    
    var confirmCallback = function(msg, success) {
        if (success) {
            callServer(true);
        }
    };
    
    var callServer = function(forceDeleted) {
        Utils.Request({
            parameters: {
                action: "login",
                username: $('username').value,
                password: $('password').value,
                remember: $('remember').checked,
                includeUsage: 'MyForms' in window, // If user is on myforms page then bring usage info with login
                forceDeleted: (forceDeleted? 1: 0)
            },
            onSuccess: function(response){
                // if there is a referer hidden input, than redirect
                // to the referer after login.
                if ( $('login_referer') && $('login_referer').value ){
                    
                    // If referrer was login page then don't comeback here
                    if($('login_referer').value.endsWith('login/') || $('login_referer').value.endsWith('login')){
                        Utils.redirect(Utils.HTTP_URL + 'myforms/');
                    }else{
                        Utils.redirect($('login_referer').value);
                    }
                    return;
                }
                
                // TODO: If the person is on the registration page, redirect
                // to the My Forms page.
                
                if(location.href.include('/login')){
                    // This means we are on the login page after login we must be redirected to myforms
                    $('myaccount').update('<h3>'+'Login Successful!'+'</h3>'+'Please wait wile redirecting...'.locale());
                    setTimeout(function(){
                        Utils.redirect(Utils.HTTP_URL + 'myforms/');
                    }, 100);
                    return;
                }
                // Just wait a little to let browser know what's going on
                $('myaccount').update(response.accountBox);
                
                if(document.readCookie('no-translate')){
                    if($('total-translate-container')){
                        $('total-translate-container').remove();
                    }
                }
                if(window.Utils){
                    window.Utils.user = response.user;
                    window.Utils.user.usage = response.usage;
                    if(response.user.theme){
                        $(document.body).className = response.user.theme;
                    }
                }
                if(response.user.accountType == 'ADMIN' || response.user.accountType == 'SUPPORT'){
                    if($('nav')){
                        if(!$('tickets-link') && !document.APP){
                            $('nav').insert('<li class="navItem" id="tickets-link"><a href="ticket">Tickets</a></li>');
                        }
                        if(!$('admin-link')){
                            $('nav').insert('<li class="navItem" id="admin-link"><a href="admin">Admin</a></li>');                        
                        }
                    }
                }
                
                Locale.changeHTMLStrings();
                if('MyForms' in window){
                    $('account-notification').hide();
                    window.scroll(0,0);
                    MyForms.updatePage();
                }
            },
            
            onFail: function(response){
                // Check what the errors are returned.
                if (response.error == "DELETED") {
                    // Ask the user if they want to re-enable their account.
                    var deletedMsg = "This account has been deleted by the owner.<br><br>Do you want to enable it?";
                    Utils.confirm(deletedMsg, "Enable Account?", confirmCallback);
                }
                else if (response.error == "AUTOSUSPENDED") {
                    // The user was suspected of phishing.
                    var autoSuspensionMsg = "Due to <a href=\"http://www.jotform.com/terms\" target=\"blank\"><b><u>terms of use</u></b></a> "+
                             "violation, your account has been "+
                             "suspended.<br>It was suspected to be used for phishing. If you think this is a mistake please "+
                             "<a href=\"http://www.interlogy.com/contact.html?jotform=yes\" target=\"blank\"><b><u>contact us</u></b></a> "+
                             "to resolve this issue.";
                    Utils.alert(autoSuspensionMsg, "Account Suspended");
                }
                else if (response.error == "SUSPENDED") {
                    // The user is suspended by administrators due to something.
                    var suspensionMsg = "Due to <a href=\"http://www.jotform.com/terms\" target=\"blank\"><b><u>terms of use</u></b></a> "+
                             "violation, your account has been "+
                             "suspended. If you think this is a mistake please "+
                             "<a href=\"http://www.interlogy.com/contact.html?jotform=yes\" target=\"blank\"><b><u>contact us</u></b></a> "+
                             "to resolve this issue.";
                     Utils.alert(suspensionMsg, "Account Suspended");
                }
                else {
                    // Generic error message. Show the content here.
                    $('error-box').update(response.error);
                    $('error-box').show();
                }
            }
        });
    };
    
    function login() {
        $$('.error-div').each(function(el) { el.update(""); });

        $('username', 'password').invoke('removeClassName', 'error');

        if(!$('username').value){
            $('username').addClassName('error').focus();
            $('usernameErrorDiv').update('Username cannot be blank'.locale());
            $('usernameErrorDiv').show();
            return;
        }
        
        if(!$('password').value){
            $('password').addClassName('error').focus();
            $('passwordErrorDiv').update('Password cannot be blank'.locale());
            $('passwordErrorDiv').show();
            return;
        }
        
        // Validations passed, call the server.
        callServer();        
    }
        
    function openResetBox() {
        $('myaccount').removeClassName('signin');
        $('myaccount').addClassName('forgotPassword');
    }
    
    function remindSuccessCallback() {
        $('myaccount').addClassName('signin');
        $('myaccount').removeClassName('forgotPassword');
    }
    
    function sendPasswordReset() {
        var userResetData = $('resetData').value;
        
        $('passResetButton').disable();

        Utils.Request({
            parameters: {
                action: 'sendPasswordReset',
                resetData: userResetData
            },
            onSuccess: function(response) {
                $('passResetButton').enable();
                $('passResetButton').value = "Instructions Sent";
                Utils.alert("Information needed to reset your password has been sent to your e-mail address.<br /><br />Please check your email and follow the instructions.".locale(), 
                            "Password Reset".locale(), remindSuccessCallback);
            },
            
            onFail: function(response){
                Utils.alert(response.error.locale(), 
                                "Password Reset".locale());
            }
        });      
    }
    
    function checkKeypress(event) {
        event = document.getEvent(event);
        var keyCode = event.keyCode;
        // If it is not the Return key, we are not interested.
        if (keyCode != Event.KEY_RETURN) {
            return;
        }
        // If this is the forgot password button:
        if (this.id == "resetData") {
        	//sendPasswordReset();
           return;
        }
        // Otherwise, try to login.
        login();
    }
    
    // Observe key presses and submit on an enter.
    document.observe('dom:loaded', function() {
        if($('username')){
            Element.observe('username', 'keypress', checkKeypress);
            Element.observe('password', 'keypress', checkKeypress);
            Element.observe('resetData', 'keypress', checkKeypress);
            Element.observe('loginButton', 'click', login);
            Element.observe('forgotPasswordButton', 'click', openResetBox);
            Element.observe('passResetButton', 'click', sendPasswordReset);
            Element.observe('returnLoginBox', 'click', remindSuccessCallback);
        }
    });
})();
