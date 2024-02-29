var EmailWizard = {
    wiz: false,                     // Wizard object
    currentFocus: false,            // Checks if the :HTML editor has the focus
    emailType: 'notification',      // Selected type
    isHTMLOn: false,                // is HTML editor on
    editor: false,                  // nic Editor Object
    email: false,
    unChangedBody: '',
    /**
     * İnitiate the wizard
     */
    initWizard: function(w){
        
        document._onedit = true;    // Set this to stop formbuilder from using key events, we need this for HTMLEditor
        Locale.changeHTMLStrings(); // translate
        this.wiz = w;
        if(this.update){
            setTimeout(function(){
            
                var emails = form.getProperty('emails');
                this.email = emails[this.index];
                this.emailType = this.email.type || 'notification';
                
                $('email-'+this.emailType).checked = true;
                this.nextPage();
                $('email-'+this.emailType+'-name').value = this.email.name;
                $('email-'+this.emailType+'-from-name').value    = this.email.from? this.email.from.split('|')[1] : '';
                $('email-'+this.emailType+'-from-name').run('change');
                $('email-'+this.emailType+'-from-address').value = this.email.from? this.email.from.split('|')[0] : '';
                $('email-'+this.emailType+'-from-address').run('change');
                $('email-'+this.emailType+'-to-address').value   = this.email.to;
                $('email-'+this.emailType+'-to-address').run('change');
                this.isHTMLOn = this.email.html;
                $('email-body').value = this.email.body;
                $('email-subject').value = this.email.subject;
                $('email-disabled').checked = !!(this.email.disabled);
                this.openComposePage(true);
            }.bind(this), 10);
        }
        // initialize the tab button.
        $('email-body').observe("keydown", EmailWizard.handleTab );
        
	    if($('show-options')){
            $('show-options').observe('click', function(){
                if($('show-options').checked){
                    $('compose-options').show();
                }else{
                    $('compose-options').hide();
                }
            });
        }
        if(document.DEBUG){
            // Show options only on debug mode
            $('compose-options').show();
        }

        buttonToolTips($('dtext'), {
            arrowPosition: 'top',
            parent:$('page3'),
            offsetTop:20,
            message:'When disabled, you can trigger this email with a condition.'
        });
    },
    /**
     * Display the selected buttons and hide others
     * @param {Object} buttonList
     */
    showButtons: function(buttonList){
        $H(this.wiz.buttons).each(function(button){
            if(buttonList.include(button.key)){
                button.value.show();
            }else{
                button.value.hide();
            }
        });
        if(this.update){
            this.wiz.buttons.finish.show();
            this.wiz.buttons.deleteEmail.show();
        }
    },
    /**
     * Create default email template with filled tags
     */
    createDefaultEmailTemplate: function (type){
        
        return Utils.defaultEmail(this.emailType);
        
    },
    /**
     * Opens selected page and hides the others
     * @param {Object} page
     */
    showPage: function(page){
        $('page1', 'page2', 'page3').invoke('hide');
        $(page).show();
        this.wiz.reCenter();            
    },
    
    /**
     * Sets the e-mail names inside the e-mail wizard.
     */ 
    setEmailName: function() {
        
        if(!this.update){
            var emailList = form.getProperty('emails'); // Get emails
            var notifNumber = 1;    // Start counting from 1
            var autoRespNumber = 1;
            // Loop through emails and get the sum of them
            emailList.each(function(email){
                if (email.type == 'notification') {
                    notifNumber++;
                } else if (email.type == 'autorespond') {
                    autoRespNumber++;
                }
            });
            
            // Place correct names for emails
            $('email-autorespond-name').value = "Auto Responder".locale() + " " + autoRespNumber;
            $('email-notification-name').value = "Notification".locale() + " " + notifNumber;
        }
        
        // Give focus to the selected email name
        // In order to make user understand that this place is editable
        $('email-'+this.emailType+'-name').select();
    },
    /**
     * get the cursor position
     * @param {Object} ctrl
     */
    doGetCaretPosition: function (ctrl) {
        var CaretPos = 0;
        if (document.selection) {
            ctrl.focus();
            var Sel = document.selection.createRange();
            Sel.moveStart('character', -ctrl.value.length);
            diff = Sel.text.indexOf(ctrl.value.substring(0,2));
            CaretPos = (diff > 0)? (Sel.text.length - diff) : 0;
        }else if (ctrl.selectionStart || ctrl.selectionStart == '0'){
            CaretPos = ctrl.selectionStart;
        }
        return (CaretPos);
    },
    /**
     * set the cursor position
     * @param {Object} ctrl
     * @param {Object} pos
     */
    setCaretPosition: function (ctrl, pos){
        if(ctrl.setSelectionRange){
            ctrl.focus();
            ctrl.setSelectionRange(pos,pos);
        }else if (ctrl.createTextRange) {
            var range = ctrl.createTextRange();
            range.collapse(true);
            range.moveEnd('character', pos);
            range.moveStart('character', pos);
            range.select();
        }
    },
    /**
     * add thext into inputs
     * @param {Object} li
     * @param {Object} area
     */
    add_text: function (name, area){
         
        area = area || $('email-body');
        cpos = this.doGetCaretPosition(area); 
        temp1 = area.value.substring(0, cpos);
        temp2 = area.value.substring(cpos, area.value.length);
        
        var value = "{" + name + "}";
        
        newvalue = temp1 + value + temp2;
        area.value = newvalue;
        this.setCaretPosition(area, (temp1.length+value.length));
    },
    /**
     * Stuff we should do when wizard is about to close
     */
    closeWizard: function(){
        document._onedit = false;
        this.editorSet = false;
        this.index = undefined;
        this.update = false;
        this.email = false;
        this.isHTMLOn = false;
        this.currentFocus = false;
        this.editor = false;
        this.unChangedBody = '';
    },
    /**
     * Handles clicking the back button
     */
    backPage: function(){
        this.wiz.setStyle({width:'500px'});
        this.showButtons(['next']);
        this.showPage('page1');
        this.wiz.setTitle('Email Wizard'.locale());
    },
    
    /**
     * Open Email settings page aka envelopes
     */
    nextPage: function(){
        
        
        // Fill dropdowns for email field selection
        $A(getUsableElements('email', true)).each(function(el){
                        
            $('email-notification-from-address').insert(new Element("option", {
                value: "{"+el.getProperty('name')+"}"
            }).insert(el.getProperty('text').stripTags().replace(/\&nbsp;/gim, '').shorten(20)));
            
            $('email-autorespond-to-address').insert(new Element("option", {
                value: "{"+el.getProperty('name')+"}"
            }).insert(el.getProperty('text').stripTags().replace(/\&nbsp;/gim, '').shorten(20)));
        });
        // Add Default options
        $('email-notification-from-address').insert(new Element("option", {value: "default"}).insert('noreply@jotform.com'.locale()));
        $('email-autorespond-to-address').insert(new Element("option", {value: "default"}).insert('noreply@jotform.com'.locale()));

        // Fill dropdowns for name field selection
        $A(getUsableElements('name', true)).each(function(el){
            $('email-notification-from-name').insert(new Element("option", {
                value: "{"+el.getProperty('name')+"}"
            }).insert(el.getProperty('text').stripTags().replace(/\&nbsp;/gim, '').shorten(20)));
        });
        // Add Default option
        $('email-notification-from-name').insert(new Element("option", {value: "default"}).insert('JotForm'.locale()));
        
        if(!this.update){

            // Get selected email type
            if($('email-notification').checked){
                this.emailType = 'notification';
                if(Utils.user && Utils.user.email){
                    $('email-notification-to-address').value = Utils.user.email;
                }else{
                    $('email-notification-to-address').value = 'Your E-Mail'.locale();
                }
            }else{
                this.emailType = 'autorespond';
                
                if (Utils.user && Utils.user.name && Utils.user.accountType != 'GUEST') {
                    $('email-autorespond-from-name').value = Utils.user.name;
                }else{
                    $('email-autorespond-from-name').value = 'Your Name'.locale();
                }
                
                if (Utils.user && Utils.user.email) {
                    $('email-autorespond-from-address').value = Utils.user.email;
                }else{
                    $('email-autorespond-from-address').value = 'Your E-Mail'.locale();
                }
            }
            // Will cover email address in turkish, french, italian, spanish and english and their derivatives
            var mailRegExp = /mail|correo|courrier|post|cotte|corriere|malla|cartas/gim;
            
            // Will cover name and company in turkish, french, italian, spanish and english and their derivatives
            // @TODO: Should encode unicode chars with \x086 kind of modifiers
            var nameRegExp = /name|company|adınız|isim|şirket|nombre|designación|titulo|compañía|empresa|società|compagnia|nome|firma|prénom|compagnie|gesellschaft/gim;
            // Try to select mail fields
            $('email-notification-from-address').selectOption(mailRegExp);
            $('email-autorespond-to-address').selectOption(mailRegExp);
            
            $('email-notification-from-name').selectOption(nameRegExp);
        }
        
        // Open the correct envelope for selected type
        $$('.email-setting-pages').invoke('hide');
        $('email-'+this.emailType+'-settings').show();
        
        // Convert dropdowns into fashonable ones
        $('email-notification-from-address', 'email-autorespond-to-address', 'email-notification-from-name').invoke('bigSelect');
        
        // Count number of notifications
        this.wiz.setStyle({height:'auto', width:'700px'});
        this.wiz.setTitle('Provide Sender and Recipient Details'.locale());
        this.showButtons(['back', 'compose']);
        this.showPage('page2');
        this.setEmailName();
    },
    /**
     * Returns back to settings page from compose page
     */
    backToSettings: function(){
        this.wiz.setTitle('Provide Sender and Recipient Details'.locale());
        this.wiz.setStyle({height:'auto', width:'700px'});
        if(this.update){
            this.showButtons(['compose']);
        }else{
            this.showButtons(['back', 'compose']);
        }
        this.showPage('page2');
    },
    /**
     * Cleans up the HTML tags but places \n and \t whitespace instead
     * @param {Object} string
     */
    stripHTML: function(string){
        var v = string;
        v = v.replace(/\s+/gim, ' ');
        v = v.replace(/<th(.*?)>(.*?)<\/th>/gim, "<th> $2 </th>\t\t");
        v = v.replace(/<\/td>/gim, '</td>\t\t');
        v = v.replace(/<\/tr>/gim, '</tr>\n');
        v = v.replace(/<\/li>/gim, '</li>\n');
        v = v.replace(/<\/div>/gim, '</div>\n');
        v = v.replace(/\&nbsp\;/gim, ' ');
        v = v.replace(/\<br.*?\>/gim, '\n');
        v = v.stripTags();
        v = v.replace(/^\s+$/gim, '');
        v = v.replace(/^[ \t]+(.*)/gim, '$1');
        return v;
    },
    
    /**
     * Clean up the editor contents
     */
    toggleEditor: function(button){
        var $this = this;
        // If editor was already open
        if(this.isHTMLOn){
            this.isHTMLOn = false;
            button.innerHTML = 'Switch to HTML Mode'.locale();
            Editor.remove('email-body');// Remove HTML editor
            $('email-body').value = this.stripHTML($('email-body').value);  // Clean the HTML tags from Email source
            
            // Put field list into correct position 
            $('email-body').onfocus = function(){   // Set the onfocus event
                $this.currentFocus = $('email-body');
                $('fields-box').shift({top:60, duration:0.5});
            };
            $('email-body').focus();
        }else{
            this.isHTMLOn = true;
            button.innerHTML = 'Use Text E-Mail'.locale();
            $('email-body').value = $('email-body').value.replace(/\n/gim, '<br>'); // Convert all newlines to BR
            // Add HTML editor
            this.editor = Editor.set('email-body', 'advanced');
            
            // Put field list box at correct position
            Editor.addEvent('email-body', 'focus', function(){
                $this.currentFocus = true;
                $('fields-box').shift({top:100, duration:0.5});
            });
            Editor.focus('email-body');
        }
    },
    /**
     * Put default email into the editor
     */
    useDefault: function(){
        if(this.isHTMLOn){
            Editor.setContent('email-body', this.createDefaultEmailTemplate());
        }else{
            $('email-body').value = this.stripHTML(this.createDefaultEmailTemplate());
        }
        if(this.update){
            this.email.dirty = false;
        }
    },
    /**
     * Validates the Settings page
     */
    validateSettings: function(){
        
        $$('.envelope-error').invoke('update');
        
        if(this.emailType == 'notification'){
            
            if($('email-notification-to-address').removeClassName('error').value.blank() || $('email-notification-to-address').value == 'Your E-Mail'.locale()){
                $('email-notification-to-address').addClassName('error');
                $('email-notification-error').update('Enter your e-mail address to receive notifications.'.locale());
                return false;
            }
            
            if(!Utils.checkEmailFormat($('email-notification-to-address').removeClassName('error').value)){
                $('email-notification-to-address').addClassName('error');
                $('email-notification-error').update('E-mail address should be valid.'.locale());
                return false;
            }
            
            if($('email-notification-from-name').value == 'none'){
                $('email-notification-from-name').selectOption('default');
            }
            if($('email-notification-from-address').value == 'none'){
                $('email-notification-from-address').selectOption('default');
            }
            
        }else{
            if($('email-autorespond-from-name').removeClassName('error').value.blank() || $('email-autorespond-from-name').value == 'Your Name'.locale()){
                $('email-autorespond-from-name').addClassName('error');
                $('email-autorespond-error').update('Enter your name to be used in auto responds.'.locale());
                return false;
            }
            
            if($('email-autorespond-from-address').removeClassName('error').value.blank() || $('email-autorespond-from-address').value == 'Your E-Mail'.locale()){
                $('email-autorespond-from-address').addClassName('error');
                $('email-autorespond-error').update('Enter your e-mail address to be used in auto responds.'.locale());
                return false;
            }
            
            if(!Utils.checkEmailFormat($('email-autorespond-from-address').removeClassName('error').value)){
                $('email-autorespond-from-address').addClassName('error');
                $('email-autorespond-error').update('E-mail address should be valid.'.locale());
                return false;
            }
            
            if($('email-autorespond-to-address').value == 'none'){
                $('email-autorespond-to-address').selectOption('default');
            }
        }
        
        
        if($('email-'+this.emailType+'-name').removeClassName('error').value.blank() || $('email-'+this.emailType+'-name').value == 'Your E-Mail'.locale()){
            $('email-'+this.emailType+'-name').addClassName('error');
            $('email-'+this.emailType+'-error').update('Enter a name for this e-mail.'.locale());
            return false;
        }
        return true;
    },
    /**
     * Checks if the email content has been changed or not
     */
    checkBodyChanged: function(){
        var body    = this.getBody();
        var oldBody = this.unChangedBody;
        
        // Normalize values
        body    = body.replace(/\&nbsp\;|\s+/gim, "");
        oldBody = oldBody.replace(/\&nbsp\;|\s+/gim, "");
        
        return (body != oldBody);
    },
    /**
     * Opens the compose page, creates editors and field lists
     */
    openComposePage: function(novalidate) {
        
        if(novalidate !== true && !this.validateSettings()){
            return;
        }
        
        var $this = this;
        $this.wiz.setTitle('Compose Email'.locale());   // Set title
        $this.wiz.setStyle({width:'850px'});            // Set size
        
        
        if(!this.update){
            // Be sure to make the first page checked when opening 
            // an already existing e-mail.
            if($('email-subject').value.blank()){
                $('email-subject').value  = $('email-' + $this.emailType + '-name').value;
            }
            $('email-body').value     = $this.createDefaultEmailTemplate();
            $('email-disabled').checked = false;
        }
        
        // if editor was already created then skip these steps
        if(!this.editorSet){
            
            if(this.email && !$this.email.html){
                this.isHTMLOn = false;
                $('email-toggle').innerHTML = 'Switch to HTML Mode'.locale();
                
                // Put field list into correct position 
                $('email-body').onfocus = function(){   // Set the onfocus event
                    $this.currentFocus = $('email-body');
                    $('fields-box').shift({top:60, duration:0.5});
                };
            }else{
                this.editorSet = true;
                this.isHTMLOn = true;
                // Create HTML Editor
                this.editor = Editor.set('email-body', 'advanced', function(){
                    $this.unChangedBody = $this.getBody();
                });
                
                // Set focus event for field list to follow HTML editor
                Editor.addEvent('email-body', 'focus', function(){
                    $this.currentFocus = true;
                    $('fields-box').shift({top:100, duration:0.5});
                });
            }
            
            // Set focus event for field list to follow Email subject
            $('email-subject').observe('focus', function(){
                $this.currentFocus = $('email-subject');
                $('fields-box').shift({top:20, duration:0.5});
            });
            
            
            // Create the field list
            $A(getUsableElements()).each(function(el){
                var textVal = el.getProperty('text').stripTags().replace(/\&nbsp;/gim, '');
                var name = el.getProperty('name');
                $this.addFieldToFieldList(textVal, name);
                if(el.getProperty('type') == 'control_fileupload'){
                    $this.addFieldToFieldList(textVal+" Image", "IMG:"+name, 
                    "<b>Upload Image Tag</b><br>Use this to directly add your upload as an image into "+
                    "your email. <hr> You can also specify the HEIGTH and WIDTH "+
                    "of the image by providing them as arguments. Such as:<br>"+
                    "{IMG:fieldName:width:height}<br><b>Examples</b>"+
                    "<hr>{IMG:uploadField:200:150} => Creates an 200X150 image"+
                    "<hr>{IMG:uploadField::150} => Creates an image with only 150px height"+
                    "<hr>{IMG:uploadField:200} => Creates an image with only 200px width");
                }
            });

            // add title to the field list
            this.addFieldToFieldList("<hr/>", "");

            // add id to the field list.
            this.addFieldToFieldList("Unique ID", "id");
            
            // add title to the field list
            this.addFieldToFieldList("Form Title", "form_title");

            // add edit link to the field list.
            this.addFieldToFieldList("Edit Link", "edit_link");

            // add ip to the field list.
            this.addFieldToFieldList("IP Address", "ip");
        }
        
        $('field-info').tooltip('You can add these fields to your email by clicking on them. They will be populated with real values while we are sending you the email.');
        $this.showButtons(['settings', 'finish', 'sendtest']);
        $this.showPage('page3');
        $('email-subject').select();
    },
    /**
     * Add text field to field list
     * Seyhun:	Removed this function to outside for using again and again.
     * 			And removed $this with EmailWizard inside this function.
     */
    addFieldToFieldList: function (textVal, name, tip){
        var li = new Element('li');
        li.insert(textVal.shorten(20));
        if (!name.empty()){
            li.observe('click', function(e){
                if(e.target.nodeName == 'IMG'){return;}
                var val = "{" + name + "}";
                if(EmailWizard.currentFocus === true){ // If editor has focus then place the tags in it
                    Editor.focus('email-body');
                    Editor.insertContent('email-body', val);
                }else{
                	EmailWizard.add_text(name, EmailWizard.currentFocus); // otherwise place the tags in subject or textarea
                }
            });
            
            if(tip){
                li.insert('<img src="images/information-small.png" align="right">');
                buttonToolTips(li, {
                    title:'Tag Info',
                    parent:$('fields-list'), // Because it's not yet inserted
                    trigger:li.select('img')[0],
                    offsetTop:20,
                    width:250,
                    message:tip
                });
            }else{
                li.title = textVal;
            }
            
            li.setUnselectable(); // Make list items unselectable so we don't loose the cursor position
        }
        $('fields-list').insert(li);
    },
    /**
     * get the body value
     */
    getBody: function(){
        var bodyValue = "";
        
        if(this.isHTMLOn === true){
            bodyValue = Editor.getContent('email-body');
        }else{
            bodyValue = $('email-body').value;
        }
        return bodyValue;
    },
    /**
     * Will send test email
     */
    sendTestEmail: function(){
        var $this = this;
        var userEmail = Utils.user.email || "";
        Utils.prompt("Enter an E-mail address to send a test email:".locale(), userEmail, "Please Enter your email".locale(), function(value){
            if(value){
                
                var parameters = {
                    action:'testEmail',
                    from: userEmail, // Take this from user properties
                    to: value,
                    subject: $('email-subject').value,
                    body: $this.getBody(),
                    html: $this.isHTMLOn
                };
                
                Utils.Request({ 
                    parameters:parameters,
                    onComplete: function(res){
                        Utils.alert("E-mail sent".locale());
                    },
                    onFail: function(res){
                        Utils.alert(res.error);
                    }
                });
            }
        });
    },
    /**
     * Completes the wizard collects all email information filled in the forms then saves into emails
     */
    finishWizard: function(){
        var $this = this;
        
        var dirty = this.update && this.email.dirty? true : this.checkBodyChanged();
        
        
        var email = {
            type: this.emailType,
            name: $('email-'+this.emailType+'-name').value,
            from: $('email-'+this.emailType+'-from-address').value+'|'+$('email-'+this.emailType+'-from-name').value,
            to: $('email-'+this.emailType+'-to-address').value,
            subject: $('email-subject').value || "",
            disabled: $('email-disabled').checked,
            html: !!this.isHTMLOn,
            dirty: dirty,
            body: this.getBody()
        };
        
        var emails = form.getProperty('emails');
        
        if(emails){
            if(this.update){
                emails[this.index] = email;
            }else{
                emails.push(email);
            }
        }else{
            emails = [email];
        }
        form.setProperty('emails', emails);
        onChange('E-mail Saved');
        
        this.wiz.close();
    },
    
    deleteEmail: function(){
        var $this = this;
        Utils.confirm('Are you sure you want to delete this email'.locale(), 'Confirm'.locale(), function(but, flag){
           if(flag){
               var allemails = form.getProperty('emails');
               delete allemails[$this.index];
               allemails = $A(allemails).compact();
               form.setProperty('emails', allemails);
               onChange('Email removed');
               $this.wiz.close();
           }
        });
    },
    
    /**
     * Open and initiate wizard
     */
    openWizard: function(index){
        var $this = this;
        
        if(index >= 0){
            this.update = true;
            this.index = index;
        }
        
        Utils.loadCSS('wizards/css/emailWizard.css');
        
        closeActiveButton(); // Close the email list menu
        // Load the wizard template
        var page = 'wizards/emailWizard.html';
        if (document.DEBUG && document.debugOptions.useNewEmailWizard){
        	page = 'wizards/emailWizardNew.html';
        }
        
        Utils.loadTemplate(page, function(source) {
            
            var div = new Element('div').insert(source);
            
            $this.wiz = document.window({
                title: 'Email Wizard'.locale(),
                width: 500,
                contentPadding: 0,
                content: div,
                dynamic: true,
                onInsert: $this.initWizard.bind($this),
                onClose: $this.closeWizard.bind($this),
                buttons:[{
                    title:'Delete E-Mail'.locale(),
                    name:'deleteEmail',
                    hidden:true,
                    align:'left',
                    handler: $this.deleteEmail.bind($this)
                },{
                    title:'Close'.locale(),
                    name:'close',
                    hidden:true,
                    handler:function(w){
                        w.close();
                    }
                },{
                    title:'Back'.locale(),
                    name:'back',
                    icon:'images/back.png',
                    iconAlign:'left',
                    hidden:true,
                    handler: $this.backPage.bind($this)
                },{
                    title:'Next'.locale(),
                    name:'next',
                    icon:'images/next.png',
                    iconAlign:'right',
                    handler: $this.nextPage.bind($this)
                },{
                    title:'Reply-To and Recipient Settings'.locale(),
                    name:'settings',
                    icon:'images/back.png',
                    iconAlign:'left',
                    hidden:true,
                    handler: $this.backToSettings.bind($this)
                },{
                    title:'Test Email'.locale(),
                    name:'sendtest',
                    hidden:true,
                    handler: $this.sendTestEmail.bind($this)
                },{
                    title:'Next'.locale(),
                    name:'compose',
                    icon:'images/next.png',
                    iconAlign:'right',
                    hidden: true,
                    handler: $this.openComposePage.bind($this)
                },{
                    title:'Finish'.locale(),
                    name:'finish',
                    icon:'images/accept.png',
                    iconAlign:'left',
                    hidden:true,
                    handler: $this.finishWizard.bind($this)
                }]
            });
            
        });
    },
    handleTab: function(e){
	  if (!e && event.keyCode == 9){
	    event.returnValue = false;
	    EmailWizard.insertAtCursor(this, "\t");
	  }
	  else if (e.keyCode == 9){
	    e.preventDefault();
	    EmailWizard.insertAtCursor(this, "\t");
	  }
    },
	insertAtCursor: function(myField, myValue) {
		//IE support
		if (document.selection) {
			var temp;
			myField.focus();
			sel = document.selection.createRange();
			temp = sel.text.length;
			sel.text = myValue;
			if (myValue.length == 0) {
				sel.moveStart('character', myValue.length);
				sel.moveEnd('character', myValue.length);
			} else {
				sel.moveStart('character', -myValue.length + temp);
			}
		}
		else if (myField.selectionStart || myField.selectionStart == '0') {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
			myField.selectionStart = startPos + myValue.length;
			myField.selectionEnd = startPos + myValue.length;
		} else {
			myField.value += myValue;
		}
    }
};

EmailWizard.openWizard(Utils.useArgument);
