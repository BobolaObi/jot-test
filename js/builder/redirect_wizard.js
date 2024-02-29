RedirectWizard = {
    wizardWin: false,
    custEditor: false,
    pageNumber: 1,
    
    getSelectedRedirect: function(){
    	if ($('redirect-option-url').checked) {
    		return "thankurl";
    	}
    	else if ($('redirect-option-custom').checked) {
    		return "thanktext";
    	}
    	else { // if ($('redirect-option-none').checked) {
    		return "default";
    	}
    },
    
    setSelectedRedirect: function(){
    	var activeRedirect = form.getProperty('activeRedirect');
    	if (activeRedirect == "thankurl") {
    		$('redirect-option-url').checked = true;
    	}
    	else if (activeRedirect == "thanktext") {
    		$('redirect-option-custom').checked = true;
    	}
    	else { // if (activeRedirect == "default") {
    		$('redirect-option-none').checked = true;
    	}
    },
    redirectPageNext: function(){
    	switch(RedirectWizard.pageNumber) {
    		case 1:
    		    $('redirect-options-div').hide();
    			if ($('redirect-option-url').checked) {
    				$("redirect-url-div").show();
    			} else if ($('redirect-option-custom').checked) {
    				$("redirect-custom-div").show();
    				if (!RedirectWizard.custEditor) {
    					var tt = form.getProperty('thanktext');
    					if(!tt){
    						tt = " <br><div style=\"text-align: center;\"><h1>Thank You!</h1>Your submission has been received.<br/></div>";	
    					}
    					$('redirect-custom-editor').value = tt;
    					RedirectWizard.custEditor = Editor.set('redirect-custom-editor', 'advanced');
    				}
    				RedirectWizard.wizardWin.setStyle({ width: "700px" }).reCenter();
                    
    			} else if ($('redirect-option-none').checked) {
    				$("redirect-none-div").show();
    			}
    			RedirectWizard.wizardWin.buttons.back.enable();
    			RedirectWizard.wizardWin.buttons.next.changeTitle("Finish".locale());
    			RedirectWizard.wizardWin.buttons.next.updateImage({
    				icon: 'images/accept.png'
    			});
    			RedirectWizard.pageNumber = 2;
    			break;
    		case 2:
    			if ($('redirect-option-url').checked && !RedirectWizard.checkUrlValidation()) {
    				return;
    			}
    			form.setProperty('activeRedirect', RedirectWizard.getSelectedRedirect());
    			form.setProperty('thankurl', $('redirect-url').getValue());
    			if (RedirectWizard.custEditor) {
    				form.setProperty('thanktext', Editor.getContent('redirect-custom-editor'));
    			}
    			//form.setProperty('sendpostdata', ($('redirect-post').checked)? 'Yes':'No');
                onChange('Redirect Page Changed');
    			RedirectWizard.wizardWin.close();
    			RedirectWizard.pageNumber = 1;
    			break;
    	}
    },
    
    checkUrlValidation: function() {
    	var url = $("redirect-url").value;
    	if (url.blank()) {
    		$("redirect-url-error").update('Please enter a URL to redirect the user after a form submission.');
    		$("redirect-url").addClassName('error');
    		return false;
    	}
	    /*
    	if ($('redirect-post').checked) {
    		var postRegex = /\/(.+)(?:php|asp(?:x)?|jsp|do|cgi|pl|py|rb)$/;
    		if (!postRegex.test(url)) {
    			$("redirect-url-error").update("Application receiving POST data is not supported.<br />Contact JotForm Support if you think this is an error.");
    			$("redirect-url").addClassName('error');
    			return false;
    		}
    	}*/
    	return true;
    },
    
    redirectPageBack: function(){
    	switch (RedirectWizard.pageNumber) {
    		case 2:
    			$("redirect-url-div").hide();
    			$("redirect-custom-div").hide();
    			$("redirect-none-div").hide();
    			$('redirect-options-div').show();
    			RedirectWizard.wizardWin.setStyle({
    				width: "500px"
    			}).reCenter();
    			RedirectWizard.wizardWin.buttons.next.changeTitle("Next".locale());
    			RedirectWizard.wizardWin.buttons.next.updateImage({
    				icon: 'images/next.png'
    			});
    			RedirectWizard.wizardWin.buttons.back.disable();
    			RedirectWizard.pageNumber = 1;
    			break;
    	}
    },
    
    prepopulateRedirectionData: function(){
        RedirectWizard.setSelectedRedirect();
        $('redirect-url').value = form.getProperty('thankurl');
        //$('redirect-post').checked = (form.getProperty('sendpostdata') == "Yes")?  true : false;
    },
    switchFinish: function (){
    	if (this.id == 'redirect-option-none'){
    		$('nextButton').changeTitle("Finish".locale());
    		$('nextButton').updateImage({
    			icon: 'images/accept.png'
    		});
    		RedirectWizard.pageNumber = 2;
    	}else{
    		$('nextButton').changeTitle("Next".locale());
    		$('nextButton').updateImage({
    			icon: 'images/next.png'
    		});
    		RedirectWizard.pageNumber = 1;
		}
	},
    init: function(but) {
        $('redirect-img').src = 'images/loader-big.gif';
    	Utils.loadTemplate('wizards/redirectWizards.html', function(source){
    		var containerDiv = new Element('div', {'id': 'redirect-container'});
            containerDiv.innerHTML = source;
            
            $('redirect-img').src = 'images/blank.gif';
            $('redirect-img').className = 'toolbar-thank_page';
            
            RedirectWizard.wizardWin = document.window({
                title: 'Thank You Page Wizard'.locale(),
                width: 500,
    			// height: 150,
                contentPadding: 0,
                content: containerDiv,
                dynamic: false,
                onInsert: function(){
					Locale.changeHTMLStrings();
					document._onedit = true;
					
					// TODO: need refactoring
			    	var activeRedirect = form.getProperty('activeRedirect');
			    	if (activeRedirect == "thankurl") {
			    		$('redirect-option-url').checked = true;
			    		$('nextButton').changeTitle("Next".locale());
			    		$('nextButton').updateImage({
			    			icon: 'images/next.png'
			    		});
			    		RedirectWizard.pageNumber = 1;
			    	}
			    	else if (activeRedirect == "thanktext") {
			    		$('redirect-option-custom').checked = true;
			    		$('nextButton').changeTitle("Next".locale());
			    		RedirectWizard.pageNumber = 1;
			    		$('nextButton').updateImage({
			    			icon: 'images/next.png'
			    		});
			    	}
			    	else { // if (activeRedirect == "default") {
			    		$('redirect-option-none').checked = true;
			    		$('nextButton').changeTitle("Finish".locale());
			    		RedirectWizard.pageNumber = 2;
			    		$('nextButton').updateImage({
			    			icon: 'images/accept.png'
			    		});
			    	}

			    	var none = $('redirect-option-none');
					none.observe('click', RedirectWizard.switchFinish );
					var custom = $('redirect-option-url');
					custom.observe('click', RedirectWizard.switchFinish);
					var message = $('redirect-option-custom');
					message.observe('click', RedirectWizard.switchFinish );
					// up to here
                },
                onClose: function(){
    				RedirectWizard.wizardWin = false;	
    				RedirectWizard.custEditor = false;
                    document._onedit = false; 
    			},
    			onDisplay: function(w){
    				RedirectWizard.prepopulateRedirectionData();
    			},
    			buttons:[{
                    title:'Back'.locale(),
                    icon:'images/back.png',
                    id: 'backButton',
                    iconAlign:'left',
                    disabled: true,
                    name:'back',
                    handler: RedirectWizard.redirectPageBack
                },{
                    title:'Next'.locale(),
                    name:'next',
                    id: 'nextButton',
                    icon:'images/next.png',
                    iconAlign:'right',
                    handler: RedirectWizard.redirectPageNext
                }]
    		});
    	});
    }
}

RedirectWizard.init();
