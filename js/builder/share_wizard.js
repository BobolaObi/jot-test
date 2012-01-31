var sourceLink = false;
function shareWizard (srcLnk) {
    
    // Define if the wizard triggered from source button or share button
    sourceLink = srcLnk === true;
	// wizard properties
	this.templateName = 'wizards/shareWizard.html',
	this.windowTitle = 'Embed Form'.locale(),
	this.width = 700,
	this.contentPadding = 0,
	this.dynamic = false,
	this.wizardWin = null,
	// close button properties
	this.closeButtonTitle = 'Close'.locale(),
	this.closeButtonName = 'close',
	this.HTMLEditor = null,
	this.closeButton = {
		title:this.closeButtonTitle,
		name:this.closeButtonName,
		handler:function(window){
			window.close();
		}
	},
	// Advanced button properties
	this.advancedButtonTitle = 'Advanced Options'.locale(),
	this.advancedButtonName = 'advanced',
	this.advancedButton = {
		id:'advancedButton',
		title:this.advancedButtonTitle,
		name:this.advancedButtonName,
		align:'left',
		handler:function(win, but){
            win.close();
            sourceOptions('share');
			/*if(!but.opened){
			    $('shareWizardWindow').hide();
			    $('shareWizardAdvanced').show();
			    but.update('Hide Advanced'.locale()).opened = true;
			}else{
			    $('shareWizardAdvanced').hide();
			    $('shareWizardWindow').show();
			    but.update('Advanced Options'.locale()).opened = false;
			}
			win.reCenter();
			*/
		}
	},
	// wizard on insert function
	this.onInsert = shareWizardAccordionManager,
	// initialize function => load the template
	this.init = function (){
		// load the template..
		Utils.loadTemplate( this.templateName, function(source) {
			// lots of these must be in onInsert function of the wizard window.
			var div = new Element("div");
			div.innerHTML = source;
			this.openWizard(div);
			// set the main source value.
			$('mainURLSource').value = BuildSource.getCode({type: 'url'});
			Utils.addClipboard("mainURLSourceDiv", "mainURLSource");
			$('formid').update = form.getProperty("id");
			$('mainSource').value = BuildSource.getCode({type: 'jsembed'});
			// set the custom url part
			if ( form.getProperty('slug') && form.getProperty('slug') != form.getProperty('id') ){
				$('customUrlFileName').value = form.getProperty('slug');
			}else{
				$('customUrlFileName').value = "Title-Me";
			}
            
            $('page-code').observe('click', function(){
                $('cssSource').value = BuildSource.getCode({type: "css", pagecode: $('page-code').checked});
            });
            
			// set event handler of save button
			$('customUrlButton').observe('click', this.saveCustomUrl);
	        // set the event handler of custom URL part.
	        $('customUrlFileName').observe( 'keyup', this.validateCustomURL );
			// control if the hash property in enabled.
	        if(false && !form.getProperty('hash')){ // disable this for now. its not working in IE
	        	// update form id directly
	        	this.updateFormID();
	        }
	        // feedback button and field properties
	        form.setProperty('feedbackButtonLabel', 'Feedback');
	        $('feedbackButtonName').value = form.getProperty('feedbackButtonLabel');
	        $('feedbackButtonName').observe('keyup', this.updateFeedbackCode);
	        $('feedbackButtonTest').observe('click', this.demoFeedbackCode);
	        // pop up demo
	        $('popUpTest').observe('click', this.demoPopup);
	        // light box demo
	        this.setPropertiesOfLightBoxDemo();
	        // set the accordion
		    Utils.setAccordion($('share_options'), {openIndex: 1, height: 200});
			// Load orange box.
			Utils.loadScript('js/orangebox.js', function (){OrangeBox.setListeners();});
			// disable shourcuts
			document._onedit = true;
			// set the default message
			$('emailSource').value = "Hi,<br/>Please click on the link below to complete this form.<br/><a href=\""+Utils.HTTP_URL+"form/"+form.getProperty('id')+"\">"+Utils.HTTP_URL+"form/"+form.getProperty('id')+"</a><br/><br/>Thank you!";
			// set the form
			$('fromField').value = getUserEmail();
			// set the htmleditor
            this.HTMLEditor = Editor.set('emailSource', 'small');
            // set sending email button
            $('sendMessageButton').observe('click', Utils.sendEmail );
		});
	},
	this.setPropertiesOfLightBoxDemo = function (){
		// set attributes of the button for the wizard
		var demoButton = $('lightBoxTest');
		demoButton.setAttribute("base", Utils.HTTP_URL);
		demoButton.setAttribute("formid", form.getProperty('id'));
		demoButton.setAttribute("height", 500);
		demoButton.setAttribute("width", 700);
		demoButton.setAttribute("title",form.getProperty("title"));
	},
	this.demoPopup = function (){
		window.open(Utils.HTTP_URL+"form/"+form.getProperty('id'), 'blank','scrollbars=yes,toolbar=no,width=700,height=500');
	},
	this.demoFeedbackCode = function (){
		Utils.Request({
            server:"wizards/testfeedbackbutton.php",
			asynchronous: false,
			parameters: {
				scriptSource: $('feedbackBoxSource').value
			},
            evalJSON:false,
			onComplete: function(res, responseText) {
				var myWindow = window.open("", "blank", "toolbar=no,location=no,width=800,height=600");
				myWindow.document.open();
				myWindow.document.write(responseText);
				myWindow.document.close();
			}
		});
	},
	this.updateFeedbackCode = function (){
        form.setProperty('feedbackButtonLabel', $('feedbackButtonName').value);
		$('feedbackBoxSource').value = BuildSource.getCode({type:'feedbackBox'});
	},
	this.updateFormID = function (){
        if(value){
            Utils.Request({
                parameters:{
                    action:'renewFormID',
                    formID:form.getProperty('id')
                },
                onSuccess:function(res){
                    location.href = Utils.HTTP_URL;
                },
                onFail: function(res){
                    Utils.alert(res.error);
                }
            });
        }
	},
	this.validateCustomURL = function (){
		$('customUrlButton').update('Save');
		var customUrl = $('customUrlFileName');
		var customUrlSource = $('customUrlSource');
		customUrlSource.value = (Utils.HTTP_URL + Utils.user.username + "/"+customUrl.value).replace(/\s+/gim, "_");
    	Utils.fireEvent(customUrlSource, 'change');
    	// validate
    	Utils.Request({
            parameters:{
                action:'checkSlugAvailable',
                id: form.getProperty('id'),
                slugName: $('customUrlFileName').value
            },
            onComplete:function(res){
            	
            	if (res.success && $('customUrlFileName').value ){
            		$('customUrlFileName').setStyle({
            			border: ""
            		});
            		$('customUrlButton').disabled = false;
            		$('customUrlError').update();
            	}else{
            		if ($('customUrlFileName').value != form.getProperty('slug')){
            			var msg = "";
            			if (!$('customUrlFileName').value){
                    		msg = "Custom URL cannot be empty.";
            			}else{
                    		msg = "This name is being used by another form of you. Please choose another name.";
            			}
                		$('customUrlFileName').setStyle({
                			border: "2px solid red"
                		});
                		$('customUrlButton').disabled = true;
                		$('customUrlError').update(msg);
            		}
            	}
            }
        });
	},
	this.saveCustomUrl = function(){
    	Utils.Request({
            parameters:{
                action:'saveSlug',
                id: form.getProperty('id'),
                slugName: $('customUrlFileName').value
            },
            onComplete:function(res){
            	if (res.success){
            		form.setProperty('slug', $('customUrlFileName').value);
            		$('customUrlFileName').setStyle({
            			border: ""
            		});
            		$('customUrlButton').update('Saved');
            	} else{
            		$('customUrlFileName').setStyle({
            			border: "2px solid red"
            		});
            	}
        		$('customUrlButton').disabled = true;
            }
        });
	},
	this.onClose = function (){
        sourceLink = false;
		document._onedit = false;
	},
	this.openWizard = function (div){
        BuildSource.init(getAllProperties());
	    this.wizardWin = document.window({
	        title: windowTitle,
	        width: this.width,
	        contentPadding: this.contentPadding,
	        content: div,
	        dynamic: this.dynamic,
	        onInsert: this.onInsert,
	        onClose: this.onClose,
	        buttons:[this.advancedButton, this.closeButton]
	    });
	    this.wizardWin.reCenter();
	},
	this.init();
};

function shareWizardAccordionManager(windowDiv){
	Locale.changeHTMLStrings();
	this.windowDiv = windowDiv,
	this.initialSelection = $('jsembed'),
	this.selectedDiv = null,
	this.init = function (){
		// for each section..
		var options = this.windowDiv.select('.drags');

		for (var i=0; i<options.length; i++){
			var option = options[i];
			Utils.addClipboard(option.id+"ClipboardDiv", option.id+"Source");
			// set on click functions..
			option.observe('click', this.switchTo.bind(this, option));
		}
		// Hide the div now because accordion cannot be initialized otherwise..
		$('shareWizardAdvanced').hide();
		// Switch to initial one.. simple embed code
		this.switchTo(this.initialSelection);
	}
    
    if(sourceLink){
        $('share-url').hide();
    }
    
	this.setBuildedCode = function (type){
		var source = $(type+'Source');
		if (source){
			var buildedCode = BuildSource.getCode({type: type});
			switch (type){
				case "zip":
					source.src = buildedCode;
					break;
				default:
					source.value = buildedCode;
					// select the source
					source.select();
					source.observe('click', function(){
						source.select();
					});
					// fire change event..
					Utils.fireEvent($(type+'Source'), 'change');
					if(type == "joomla")
						this.setBuildedCode("joomla2");
					break;
			}
		}
	},
	this.switchTo = function (option){
		this.setBuildedCode(option.id);
		if (this.selectedDiv){
			this.selectedDiv.removeClassName('button-press');
			this.selectedDiv.hide();
		}
		// Change the selected div
		this.selectedDiv = $('page_'+option.id);
		// show the page
		if (this.selectedDiv){
			this.selectedDiv.setStyle({display:'block'});
		}
	},
	this.init();
}

shareWizard(Utils.useArgument);
