/**
 * Creates the condition wizard and controls all of it's actions
 */
var PublishWizard = {
    wiz: false,                     // Wizard window
    currentPage: 'page1',
    selectedEmailIndex: false,
    
    onClose: function(){
        this.selectedEmailIndex = false;
        this.currentPage = 'page1';
    },
    /**
     * Initiate the wizard, translate strings and set events
     */
    initWizard: function(){
        
        var $this = this;           // Object scope
        Locale.changeHTMLStrings(); // translate
        var emails = form.getProperty('emails');
        if(emails && emails.length > 0){
            $('email-correct').show();
            $('email-fail').hide();
            $A(emails).each(function(email, i){
                $this.selectedEmailIndex = i;
                if(email.type == 'notification'){
                    $('publish-email').value = email.to;
                }
            });
        }else{
            $('email-correct').hide();
            $('email-fail').show();
        }
        $('publish-email').select();
        
        $('publish-source').value = BuildSource.getCode({type:'jsembed'});
    },
    
    /**
     * 
     * @param {Object} w
     */
    nextPage: function(w){
        
        if(this.currentPage == 'page1'){
            
            $('publish-email').removeClassName('error');
            $('publish-email-error').update('&nbsp;');
            
            if (!Utils.checkEmailFormat($('publish-email').value)) {
                $('publish-email').addClassName('error');
                $('publish-email-error').update('Enter a valid E-Mail address');
                return;
            }

            if(this.selectedEmailIndex !== false){
                var emails = form.getProperty('emails');
                emails[this.selectedEmailIndex].to = $('publish-email').value;
            }else{
                var defEmail = {
                    type: "notification",
                    name: 'Notification',
                    from: 'default',
                    to: $('publish-email').value,
                    subject: "New submission: {form_title}".locale(),
                    html: true,
                    body: Utils.defaultEmail()
                };
                form.setProperty('emails', [defEmail]);
            }
            this.showButtons('finish');
            this.showPage('page2');
        }
        
    },
    /**
     * 
     * @param {Object} w
     */
    backPage: function(w){
        
    },
    
    /**
     * 
     * @param {Object} w
     */
    finishWizard: function(w){
        
    },
    
    /**
     * 
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
    },
    /**
     * 
     * @param {Object} page
     */
    showPage: function(page){
        this.currentPage = page;
        $('page1', 'page2', 'page3').invoke('hide');
        $(page).show();
        this.wiz.reCenter();
    },
    
    /**
     * Create the wizard window
     */
    openWizard: function(){
        var $this = this;
        
        // $('logic-img').src = 'images/loader-big.gif';
        // $('logic-img').removeClassName('toolbar-cond');
        
        Utils.loadTemplate('wizards/publishWizard.html', function(source) {
            
            var div = new Element('div').insert(source);
            
            //$('logic-img').src = 'images/blank.gif';
            //$('logic-img').addClassName('toolbar-cond');

            $this.wiz = document.window({
                title: 'Publish Wizard: Emails'.locale(),
                width: '',
                contentPadding: 0,
                content: div,
                dynamic: false,
                onInsert: $this.initWizard.bind($this),
                onClose:  $this.onClose.bind($this),
                buttons:[{
                    title:'Next'.locale(),
                    name:'nextPage',
                    handler:$this.nextPage.bind($this)
                },{
                    title:'Finish'.locale(),
                    name:'finish',
                    hidden: true,
                    handler:function(w){
                        w.close();
                        save();
                    }
                }]
            });
            
        });
    }
};

// On load
PublishWizard.openWizard();
