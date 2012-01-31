var Submissions = {
    excludeColumns: ["ip", "autoHide", "id"], // Fields to be hidden on submission display
    grid:false,
    properties: {},
    bbar:false,
    data:false,
    formID: false,
    lastPageNum:false,
    currentPageNum: 0,
    publicListing: false,
    /**
     * Sets the submissin viewer for print mode
     */
    print: function(){
        var template = "";
        template += '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        template += '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>';
        template += '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        template += '<style>html, body{ height:100%; width:100%; margin:0px; padding:0px;overflow: visible; }</style>';
        template += '<base href="'+Utils.HTTP_URL+'" />';
        template += '<link rel="stylesheet" type="text/css" href="css/styles/form.css"/>';
        template += '<link rel="stylesheet" type="text/css" media="print" href="css/styles/form.css"/>';
        template += '<style>.form-section{overflow: visible !important; }</style>';
        template += '</head><body>';
        template += $('sub-content').innerHTML;
        template += '</body></html>';
        var iframe = new Element('iframe', {name:'print_frame', id:'print_frame'}).setStyle({height:'0px', width:'0px', border:'none'});
        $(document.body).insert(iframe);
        var frame = window.frames.print_frame;
        frame.document.open();
        frame.document.write(template);
        frame.document.close();
        frame.window.print();
        
        if($(frame).remove){
            $(frame).remove();
        }
    },
    /**
     * Make submission flagged and update the image
     * @param {Object} id
     * @param {Object} img
     */
    flag: function(id, img){
        img.src = "images/flag.png";
        img.onclick = function(){ this.unflag(id, img); }.bind(this);
        this.setFlag(id, 1);
    },
    /**
     * Make submission un-flagged and update the image
     * @param {Object} id
     * @param {Object} img
     */
    unflag: function(id, img){
        img.src = "images/flag-disable.png";
        img.onclick = function(){ this.flag(id, img); }.bind(this);
        this.setFlag(id, 0);
    },
    /**
     * Save the flag status onto database
     * @param {Object} id
     * @param {Object} value
     */
    setFlag: function(id, value){
        if(this.publicListing){return;}
        Utils.Request({
            parameters: {
                action:'setSubmissionFlag',
                sid: id,
                value: value
            }
        });
    },
    /**
     * convert submission HTML to email value
     * @param {Object} source
     */
    convertToEmail: function(source){
        source = source.replace(/class=\"form-line\"/gim, 'style="clear:both;padding: 5px;margin: 10px;"');
        source = source.replace(/class=\"form-all\"/gim, 'style="margin: 0px;margin-top:5px !important;"');
        source = source.replace(/class=\"form-section\"/gim, 'style="list-style: none;list-style-position: outside;padding:0;margin:0;"');
        source = source.replace(/class=\"form-input\"/gim, 'style="display:inline-block;"');
        source = source.replace(/class=\"form-label-left\"/gim, 'style="width: 150px;float: left;text-align: left;padding: 3px;"');
        source = source.replace(/src=\"images/gim, 'src="'+Utils.HTTP_URL+"images");
        source = source.replace(/class=\"form-matrix-row-headers\"/gim, 'style="border:1px solid #ccc;background:#ddd;padding:4px;"');
        source = source.replace(/class=\"form-matrix-column-headers\"/gim, 'style="border:1px solid #ccc;background:#ddd;padding:4px;"');
        source = source.replace(/class=\"form-matrix-values\"/gim, 'style="border:1px solid #ccc;background:#f5f5f5;padding:4px;"');
        source = source.replace(/class=\"form-matrix-table\"/gim, 'style="border-collapse:collapse;font-size:10px;"');
        return source;
    },
    /**
     * Mark submission as unread
     * @param {Object} id
     * @param {Object} img
     */
    makeUnread: function(id, img){
        img.src = "images/mail.png";
        img.onclick = function(){ /*this.makeRead(id, img)*/ }.bind(this);
        this.readStatus(id, 1);
    },
    /**
     * Mark submission as read
     * @param {Object} id
     * @param {Object} img
     */
    makeRead: function(id, img){
        img.src = "images/mail-open.png";
        img.onclick = function(){ this.makeUnread(id, img); }.bind(this);
        this.readStatus(id, 0);
    },
    /**
     * Update the submission status on database
     * @param {Object} id
     * @param {Object} value
     */
    readStatus: function(id, value){
        if(this.publicListing){return;}
        Utils.Request({
            parameters: {
                action:'setReadStatus',
                sid: id,
                formID: this.data.formID,
                value: value
            }
        });
    },
    /**
     * Cancel Edit
     */
    cancelEdit: function(){
        $('edit-button', 'delete-button').invoke('show');
        $('cancel-button').hide();
        $$('#group-submissions button').invoke('enable');
        this.displayRowData(this.getSelected());
    },
    /**
     * Open selected form in edit mode.
     */
    editForm: function(){
        if(this.publicListing){return;}
        //sc.insert(' <a href="'+ Utils.HTTP_URL+"form.php?mode=edit&formID="+$this.data.formID+"&sid="+selected.data.submission_id+'" target="_blank">Edit link</a> ');
        var form = $$('.form-all')[0];
        
        var height = $('sub-content').getHeight() - 30;
        form.hide();
        $('edit-button', 'delete-button').invoke('hide');
        $('cancel-button').show();
        var iframe = new Element('iframe', {src: Utils.HTTP_URL +"form.php?mode=inlineEdit&formID="+ this.data.formID +"&sid="+ this.getSelected().data.submission_id, frameborder:0 });
        iframe.setStyle({height: height+"px", width:'100%', border:'none' });
        form.insert({before: iframe});
        $$('#group-submissions button').invoke('disable');
    },
    
    /**
     * Delete the submission by confirming the user
     */
    deleteSubmission: function(submission_id){
        if(this.publicListing){return;}
        var $this = this;
        
        var deleteSub = function(){
            Utils.Request({
                parameters:{
                    action:'deleteSubmission',
                    sid: (submission_id)? submission_id : $this.getSelected().data.submission_id,
                    formID: $this.data.formID
                },
                onSuccess: function(res){
                    $this.bbar.doRefresh();
                },
                onFail: function(res){
                    Utils.alert(res.error, "Error");
                }
            });
        };
        
        // If user selected to no dialog then don't show a dialog
        if(document.readCookie('dontShowDialog') == 'yes'){
            deleteSub();
            return;
        }
        
        // Display a dialog box by default
        Utils.confirm('<span style="font-size:16px;">'+
                          'Are you sure you want to delete this submission.'.locale() + 
                      '</span><hr><span style="color:#555; font-size:11px;"> '+
                          'Be careful, this process cannot be undone.'.locale() +
                      '</span><div style="margin-top:10px"> <label><input type="checkbox" id="dontshow"> ' + 
                          "Don't show this message again.".locale() + 
                      ' </label></div>', 
            "Confirm".locale(),
            function(but, value){
                if(value){
                    if($('dontshow').checked){
                        document.createCookie('dontShowDialog', 'yes');
                    }
                    deleteSub();
                }
            }
        );
        
    },
    /**
     * Save the shown or hiddden field information to database
     */
    saveColumnSettings: function(){
        
        Utils.Request({
            parameters:{
                action:'setSetting',
                identifier: this.data.formID,
                key:'columnSetting',
                value: Object.toJSON($A(this.excludeColumns).compact())
            },
            onFail: function(res){
                Utils.alert(res.error, "Error".locale());
            }
        });
    },
    /**
     * Read the setting from database
     * @param {Object} response
     */
    getColumnSettings: function(response){
        if(response.success){
            if(response.value && Object.isArray(response.value)){
                this.excludeColumns = response.value;
            }
        }
    },
    
    /**
     * Reads the properties of the form then converts it into an object
     * @param {Object} response
     */
    getFormProperties: function(response){
        var $this = this;
        
        // See if this document is viewing by public or not
        this.publicListing = document.publicListing;
        
        if(response.success === false && document.readCookie('try-reload') !== 'yes'){
            document.createCookie('try-reload', 'yes');
            setTimeout(function(){ location.reload(true); }, 10);
        }else if(response.success === false){
            Utils.alert('Cannot read form information. Try reloading the page.');
        }else{
            document.eraseCookie('try-reload');
        }
        
        if(this.publicListing){
            $('settings').hide();
        }
        
        $H(response.form).each(function(prop){
            var qid = prop.key.split("_")[0];
            var key = prop.key.split("_")[1];
            var value = prop.value;
            qid = qid == "form"? qid : "q_"+qid;
            if(!$this.properties[qid]){ 
                $this.properties[qid] = {};
            }
            
            $this.properties[qid][key] = value;
        });
        
        this.formID = this.properties.form.id;
        document.title += (": "+this.properties.form.title);
        $('form-title').innerHTML += (": "+this.properties.form.title);
        
        if(this.hasPayment() && !this.publicListing){
            $('pendingButton').show().disable().observe('click', this.openPendingSubmissions.bind(this));
            this.getPendingCount();
        }
        
    },
    /**
     * Gets the pending submission count for this form 
     */
    getPendingCount: function(){
        
        Utils.Request({
            parameters:{
                action:'getPendingCount',
                formID:this.formID,
                type:'PAYMENT'
            },
            onSuccess: function(res){
                if(res.total > 0){
                    $('pendingButton').enable().select('.button-img-wrap')[0].insert(
                        new Element('div', { className:'notify' }).insert('<div class="arrow"></div>').insert(res.total)
                    );
                }else{
                    $('pendingButton').disable().select('.notify').invoke('remove');
                }
            }
        });
        
    },
    
    /**
     * Creates pending submissions wizard
     */
    openPendingSubmissions: function(){
        Utils.loadScript('js/includes/pending_wizard.js', function(){ PendingWizard.openWizard(); });
    },
    
    /**
     * Check if the form has payment or not
     */
    hasPayment: function(){
        var has = false;
        $H(this.properties).each(function(pair){
            if(pair.value.type && [/* no pending for offline payment 'control_payment',*/ 'control_paypal', 'control_paypalpro', 'control_clickbank', 'control_2co', 'control_worldpay', 'control_googleco', 'control_onebip', 'control_authnet'].include(pair.value.type)){
                has = true;
                throw $break;
            }
        });
        return has;
    },
    /**
     * Check if the form has uploads or not
     */
    hasUpload: function(){
        var has = false;
        $H(this.properties).each(function(pair){
            if(pair.value.type && pair.value.type == 'control_fileupload'){
                has = true;
                throw $break;
            }
        });
        return has;
    },
    /**
     * Cheks if the form has given type of control
     * @param {Object} type
     */
    hasQuestion: function(type){
        var has = false;
        var arr = Object.isArray(type);
        $H(this.properties).each(function(pair){
            if(arr){
                if(pair.value.type && type.include(pair.value.type)){
                    has = true;
                    throw $break;
                }
            }else{
                if(pair.value.type && pair.value.type == 'control_'+type){
                    has = true;
                    throw $break;
                }
            }
            
        });
        return has;
    },
    /**
     * Opens or closes the public setting for this page
     * @param {Object} status
     */
    togglePublicSettings: function(status, callback){
        var $this = this;
        if(status == 'open'){
            Utils.prompt(
                'In order to make this page public you must first set a password.',
                'Enter a password',
                'Set Password',
                function(value, but, ok){
                    if(ok){
                        Utils.Request({
                            parameters:{
                                action:'submissionPublicPassword',
                                type:'add',
                                formID: $this.data.formID,
                                password:value
                            },
                            onSuccess: function(){
                                
                                Utils.alert(
                                    '<b>This page is public now.</b><br><br>'+
                                    'You can share this page with your friends or colleagues.<br>'+
                                    '<input onclick="this.select();" type="text" readonly value="'+location.href+'" style="font-size:14px; width:98%; text-align:center; padding:5px; margin:14px 0 0; background:white; display:inline-block;border:1px solid #ccc;">', 'Public URL', false, {
                                        width:450
                                    });
                                callback();
                                
                            }, onFail: function(res){
                                Utils.alert(res.error, 'Error');
                            }
                        });
                    }
                }, {
                    //fieldType:'password',
                    width:400
                }
            );
        }else{
            Utils.confirm('Are you sure you want to remove public password?', 'Are You Sure?', function(button, val){
                if(val){
                    Utils.Request({
                        parameters: {
                            action:'submissionPublicPassword',
                            type:'remove',
                            formID: $this.data.formID
                        },
                        onSuccess: function(){
                            callback();
                        }, onFail: function(res){
                            Utils.alert(res.error, 'Error');
                        }
                    });
                    
                }
            })
        }
    },
    /**
     * 
     * @param {Object} dataIndex
     * @param {Object} value
     */
    hideShowGridColumn: function(dataIndex, value){
    	try{
	        var cm = this.grid.getColumnModel();
	        var index = cm.findColumnIndex(dataIndex);
			if(index == -1){return;}
	        cm.setHidden(index, value);
    	}catch(e){}
    },
    
    logout: function(){
        location.href = location.href+"&logout";
    },
    /**
     * Open/close the settings page
     */
    toggleSettings: function(){
        var $this = this;
        
        if ($('setting-menu')) {
            $('setting-menu').remove();
            $this.saveColumnSettings();
            return;
        }
        
        var div = new Element('div', {id:'setting-menu'});

        div.insert('<b style="display:block;padding:4px;color:#E19913">' + 'Time Frame:'.locale() + '</b>');
        
        var custom = $this.getCustomDate();
        customText = 'Custom Time Frame'.locale();
        if(custom){
            customText = "Custom: %s - %s".locale(custom.start, custom.end);
        }
        
        var selected = $this.getDateRange();
        
        var tf = new Element('select', {id:'time-frame'});
        var tf_options = $H({'all':'All Time'.locale(), 'today':'Today'.locale(), 'week':'This Week (Mon-Sun)'.locale(), 'month':'This Month (1st-31st)'.locale(), 'year':'This Year(Jan 1st-dec 31st)'.locale(), 'custom': customText});
        
        tf_options.each(function(el){
            tf.insert(new Element("option", {
              value: el.key,
              selected: el.key == selected
            }).insert(el.value));
        });
        
        tf.onchange = function(){
            if(tf.value == "custom"){
                Utils.alert(
                
                    // Contents of the window
                    '<table class="date-range-table" height="100%" width="320"><tr><th colspan="2">'+
                    'Select a date range for form responses'.locale()+
                    '</th></tr><tr><td>'+'Start Date'.locale()+':</td><td>'+'End Date'.locale()+':</td></tr>'+
                    '<tr><td><div id="fromdate"></div></td>'+
                    '<td><div id="todate"></div></td>'+
                    '</tr></table><div id="date-error">&nbsp;</div>',
                    
                    // Title of the window
                    'Select a Date Range'.locale(),
                    
                    // Function to run when OK button is clicked
                    function(){
                        $('date-error').update("&nbsp;");
                        
                        var startdate = Ext.get('startdt').getValue(); 
                        var enddate   = Ext.get('enddt').getValue();
                        if(startdate === ""){
                            $('date-error').update("Please select a <b>start</b> date");
                            return false;
                        }
                        if (enddate === "") {
                            $('date-error').update("Please select an <b>end</b> date");
                            return false;
                        }
                        
                        var frametext = "Custom: %s - %s".locale(startdate, enddate);
                        
                        tf.getSelected().text = frametext;
                        tf.bigSelect();
                        $this.saveCustomDate(startdate, enddate);
                        $this.setDateRange(startdate, enddate);
                    }, 
                    
                    // Options for prompt window
                    {
                        width:340,
                        onInsert: function(){
                            if(custom){
                                $this.createRangePicker(custom.start, custom.end);
                            }else{
                                $this.createRangePicker();
                            }
                            
                        }
                    }
                );
            }else{
                $this.setDateRange(tf.value);
            }
        };
        
        div.insert(tf);
        
        tf.bigSelect();
        
        div.insert('<b style="display:block;padding:4px;color:#E19913">' + 'Fields:'.locale() + '</b>');
        
        var list = new Element('div', {className:'field-list'});
        
        $A($this.data.columns).each(function(column){
            
            if(["flag", "new", "del"].include(column.dataIndex)){ return; /* continue; */  }
            
            var li = new Element('li', {className: 'list-element'});
            
            if(!$this.excludeColumns.include(column.dataIndex)){
                li.addClassName('element-selected');
            }
            
            li.insert(column.header.stripTags().shorten(25));
            li.onclick = function(){
                if(!li.hasClassName('element-selected')){
                    li.addClassName('element-selected');
                    $this.excludeColumns = $this.excludeColumns.without(column.dataIndex);
                }else{
                    li.removeClassName('element-selected');
                    $this.excludeColumns.push(column.dataIndex);
                }                
                $this.displayRowData($this.getSelected());
            };
            list.insert(li);
        });
        div.insert(list);
		list.softScroll();
        div.insert('<b style="display:block;padding:4px;color:#E19913;margin-top:4px;">' + 'Options:'.locale() + '</b>');
        var optionsList = new Element('div', {className:'options-list'});
        
        
        var autoHide = new Element('li', {className: 'list-element'});
        if ($this.excludeColumns.include('autoHide')) {
            autoHide.addClassName('element-selected');
        }
        
        autoHide.insert("Auto Hide Empty Fields".locale());//.setStyle('border-bottom:1px solid #aaa;border-top:1px solid #aaa;padding-top:3px;padding-bottom:3px;');
        
        optionsList.insert(autoHide);
        autoHide.onclick = function(){
            if($this.excludeColumns.include('autoHide')){
                autoHide.removeClassName('element-selected');
                $this.excludeColumns = $this.excludeColumns.without("autoHide");
            }else{
                autoHide.addClassName('element-selected');
                $this.excludeColumns.push("autoHide");
            }
            $this.displayRowData($this.getSelected());
        };
        
		if($this.hasQuestion(['control_head', 'control_collapse', 'control_text', 'control_image'])){
    		var showNonInputs = new Element('li', {className: 'list-element'});
            if ($this.excludeColumns.include('showNonInputs')) {
                showNonInputs.addClassName('element-selected');
            }
            
            showNonInputs.insert("Show Headers and Texts".locale());
            
            optionsList.insert(showNonInputs);
            showNonInputs.onclick = function(){
                if($this.excludeColumns.include('showNonInputs')){
                    showNonInputs.removeClassName('element-selected');
                    $this.excludeColumns = $this.excludeColumns.without("showNonInputs");
                }else{
                    showNonInputs.addClassName('element-selected');
                    $this.excludeColumns.push("showNonInputs");
                }
                $this.displayRowData($this.getSelected());
            };
        }
        
        if($this.hasQuestion('address')){
            var noMaps = new Element('li', {className: 'list-element'});
            if (!$this.excludeColumns.include('noMaps')) {
                noMaps.addClassName('element-selected');
            }
            
            noMaps.insert("Show Addresses On Map".locale());
            optionsList.insert(noMaps);
            noMaps.onclick = function(){
                if(!$this.excludeColumns.include('noMaps')){
                    noMaps.removeClassName('element-selected');
                    $this.excludeColumns.push("noMaps");
                }else{
                    noMaps.addClassName('element-selected');
                    $this.excludeColumns = $this.excludeColumns.without("noMaps");
                }
                $this.displayRowData($this.getSelected());
            };
        }
        
        
        var publicList = new Element('li', {className: 'list-element'});
        if ($this.excludeColumns.include('publicList')) {
            publicList.addClassName('element-selected');
        }
        
        publicList.insert("Make This Page Public".locale());
        
        optionsList.insert(publicList);
        publicList.onclick = function(){
            if($this.excludeColumns.include('publicList')){
                $this.togglePublicSettings('close', function(){
                    publicList.removeClassName('element-selected');
                    $this.excludeColumns = $this.excludeColumns.without("publicList");
                    $this.saveColumnSettings();
                })
            }else{
                $this.togglePublicSettings('open', function(){
                    publicList.addClassName('element-selected');
                    $this.excludeColumns.push("publicList");
                    $this.saveColumnSettings();
                })
            }
        };
        
        
        var ftpButton = new Element('li', {className: 'list-element', id:'ftpButton-check'}).update('' + 'Send Uploads to FTP');
        if(this.FTPIntegrated){
            ftpButton.addClassName('element-selected');
        }
        
        if (!$this.hasUpload()) {
            ftpButton.setStyle('text-decoration:line-through; opacity:0.5');
            ftpButton.title = "Upload field is needed";
        }
        
        ftpButton.onclick = function(){
            if(!$this.hasUpload()){
                Utils.alert("An upload field is required for this integration.", "Notification!".locale());
            }else{
                Submissions.FTPIntegration();
            }
        };
        optionsList.insert(ftpButton);
        
        
        if(!('APP' in document) || !document.APP !== true){
            var dropbox = new Element('li', {className: 'list-element', id:'dropbox-check'}).update(
                '<img src="images/dropbox.png" align="absmiddle"> ' + 
                    '<span class="unselected-text">Send Submissions to <a href="http://www.dropbox.com/" target="_blank">DropBox</a></span>' +
                    '<span class="selected-text"> Manage Integration. </span>'
                    
            );
            if(this.dropboxIntegrated){
                dropbox.addClassName('element-selected');
            }
            
            /*if (!$this.hasUpload()) {
                dropbox.setStyle('text-decoration:line-through; opacity:0.5');
                dropbox.title = "Upload field is needed";
            }*/
            dropbox.onclick = function(){
                /*if(!$this.hasUpload()){
                    Utils.alert("An upload field is required for this integration.", "Notification!".locale());
                }else{*/
                    if(!dropbox.hasClassName('element-selected')){
                        window.open(Utils.HTTP_URL+"api/dropbox", 'view', 'width=920,height=750,status=0,location=1,toolbar=0,scrollbars=1,resizable=1');
                    }else{
                        Submissions.dropboxOptions(false);
                    }
                // }
            };
            optionsList.insert(dropbox);
        }
        
        div.insert(optionsList);
        $('settings').insert(div);
    },
    
    FTPIntegration: function(){
        var $this = this;
        Utils.loadCSS('css/wizards/FTP_wizard.css');
        Utils.require('js/wizards/FTP_wizard.js', function(){
            FTPWizard.openWizard($this.data.formID, Utils.user.username, $this.FTPProps);
        });
    },
    
    /**
     * Opens dropbox options wizard.
     */
    dropboxOptions: function(first){
        var $this = this;
        var div = new Element('div');
        
        if(first){
            div.setStyle('text-align:center;');
            div.insert('<h2 style="line-height:30px; font-size:14px;"><img src="images/success_small.png" align="absmiddle" /> Dropbox Integration Completed</h2>');
        }else{
            div.insert('<p><span style="font-size:13px;">Select a field to name your upload folders.</span>'
                       +' <br> '+
                       '<span style="color:#777; font-size:10px;">This will let you easily find/organize the uploads on your dropbox folder.</span></p><br>');
            
            var dropdown = new Element('select');
            dropdown.insert(new Element('option', {value:"none"}).update("Use Default - Submission ID"));
            dropdown.insert(new Element('option', {value:"nofolder"}).update("No Folder"));
            
            $H(this.properties).each(function(prop){
                if(prop.value.type && ['control_textbox', 'control_autocomp', 'control_email', 'control_radio', 'control_dropdown', 'control_fullname', 'control_hidden', 'control_autoincrement'].include(prop.value.type)){
                    dropdown.insert(new Element('option', {value:prop.value.qid}).update(prop.value.text.shorten('20')));
                }
            });
            
            dropdown.selectOption($this.dropboxFolderField);
            var dddiv = new Element('div');
            dddiv.insert(new Element('label').insert('Folder Name: ').insert(dropdown));
            
            div.insert(dddiv);
        }
        
        document.window({
            title: 'Configure Dropbox',
            content: div,
            width:400,
            modal: true,
            contentPadding: 15,
            buttons: [{
                title: "Remove Integration",
                hidden: first,
                align:'left',
                handler: function(w){
                        Utils.Request({
                            parameters: {
                                action: 'removeIntegration',
                                type: 'dropbox',
                                username: Utils.user.username,
                                formID: $this.data.formID
                            },
                            onSuccess: function(){
                                if($('dropbox-check')){
                                    $('dropbox-check').removeClassName('element-selected');
                                }
                                $this.dropboxFolderField = false;
                                $this.dropboxIntegrated = false;
                                w.close();                            
                            }, onFail: function(res){
                                Utils.alert(res.error, 'Error!');
                            }
                        });
                }
            },{
                title:'Complete'.locale(),
                handler: function(w){
                    if(first === false){
                        Utils.Request({
                            parameters:{
                                action:'setIntegrationProperty',
                                type:'dropbox',
                                username: Utils.user.username,
                                formID: $this.data.formID,
                                key: 'folder_field',
                                value: dropdown.value 
                            },
                            onSuccess: function(res){
                                $this.dropboxFolderField = dropdown.value;
                                w.close();
                            },
                            onFail: function(res){
                                Utils.alert(res.error, 'Error:');
                            }
                        });
                    }else{
                        w.close();
                    }
                }
            }]
        });
    },
    /**
     * Complete the dropbox integration
     * @param {Object} status
     */
    dropbox: function(status){
        if(status){
            this.dropboxIntegrated = true;
            this.dropboxOptions(true);
            $('dropbox-check').addClassName('element-selected');
        }
    },
    /**
     * Display previous submission and update the grid
     */
    prevRow: function (){
        var sm = this.grid.getSelectionModel();
        if(!sm.hasSelection()){
            sm.selectLastRow();
        }else{
            if (sm.hasPrevious()) {
                sm.selectPrevious();
            } else {
                if(this.bbar.getPageData().activePage != 1){
                    this.bbar.movePrevious();
                } 
            }
        }
        Submissions.scrollPreviewToTop();
    },
    /**
     * Scroll preview on top when selected
     */
    scrollPreviewToTop: function() {
        if (Prototype.Browser.Gecko) {
            window.scroll(0,60);
        } else {
            setTimeout(function() { 
                window.scroll(0,60); 
            }, 10);
        }
    },
    
    /**
     * Display next submission and update the grid
     */
    nextRow: function (){
        var sm = this.grid.getSelectionModel();
        if(!sm.hasSelection()){
            sm.selectFirstRow();
        }else{
            if(sm.hasNext()){
                sm.selectNext();
            }else{
                if(this.bbar.getPageData().pages != this.bbar.getPageData().activePage){
                    this.bbar.moveNext();
                }
            }
        }
        Submissions.scrollPreviewToTop();
    },
    /**
     * Get the header by question ID
     * @param {Object} key
     */
    getHeader: function (key){
        var head = $A(this.data.columns).collect(function(v){ if(v.dataIndex == key){ return v; } }).compact()[0];       
        return head && head.header;
    },
    /**
     * Cleans the value from XSS and display problems
     * @param {Object} value
     */
    cleanValue: function (value){
        value = value || "";
        value = value.stripScripts();
        value = value.replace(/\n/gim, "<br>");
        value = value.stripslashes();
        return value;
    },
	
	getColumn: function(id){
		var c = this.data.columns;
		for(var x=0; x<c.length; x++){
			if(c[x].dataIndex == id){
				return c[x];
			}
		}
	},
    /**
     * Displays the currently selected row in display area
     * @param {Object} selected
     */
    displayRowData: function (selected) {
        // If nothing is selected do nothing.
        if(!selected.data){ return; }
        
        $('edit-button', 'delete-button').invoke('show');
        $('cancel-button').hide();
        var $this = this;
        var sc = $('sub-content').update(); 
        var emails = [], ul;
        
        var hideEmpty = $this.excludeColumns.include("autoHide");
        var showMaps  = !$this.excludeColumns.include("noMaps");
        var showNonInputs = $this.excludeColumns.include("showNonInputs");
		var nonInput = false;
        
        if($('img-'+selected.data.submission_id).src.include("mail.png")){
            $this.makeRead(selected.data.submission_id, $('img-'+selected.data.submission_id));
        }
        
        sc.insert(new Element('div', {className:'form-all'}).insert(ul = new Element('ul', {className:'form-section'})));
        
		
		/**
		 * Will process each field and display on the page
		 * @param {Object} qprop
		 */
		var processField = function(qprop){
            nonInput = false;
			var column;
			if(Object.isString(qprop)){
				column = $this.getColumn(qprop); 
			}else{
				if(qprop.key == "form"){return;}
				column = $this.getColumn(qprop.value.qid);
				if(!column){
                    nonInput = true;
					column = {
						dataIndex: qprop.value.qid,
						header: qprop.value.text || ""
					}
				}
			}
			
            var qprop = $this.properties["q_"+column.dataIndex];
			
            var key   = column.dataIndex;
            var value = selected.data[key] || "";
            var type  = selected.json[key+"_type"] || (qprop && qprop.type) || "";
            var head  = column.header.stripslashes();
            
            var items = selected.json[key+"_items"];
             
            if(["control_pagebreak", "control_button", "control_captcha"].include(type)){ return /* continue; */ }
            if(!nonInput && hideEmpty && value.strip() === ""){ return; /* continue; */ }
            
            
            if(Utils.checkEmailFormat(value)){
                $A(Utils.checkEmailFormat(value)).each(function(e){
                    emails.push(e);
                });
            }
            
            if(["flag", "new", "del"].include(key)){ return; /* continue;*/  }
            var lineClass = 'form-line';
            var labelClass = 'form-label-left';
            var inputClass = 'form-input';
            var labelWidth = 'style="width:150px" ';
            
            if($this.properties.form.labelWidth){
                labelWidth = 'style="width:'+$this.properties.form.labelWidth+'px;" ';
            }
            
            if($this.properties.form.alignment == 'Top'){
                labelClass = 'form-label-top';
                inputClass = 'form-input-wide';
            }
            
            if($this.properties.form.alignment == 'Right'){
                labelClass = 'form-label-right';
                inputClass = 'form-input';
            }
            
            if(qprop){
                if(qprop.labelAlign != 'Auto'){
                    if(qprop.labelAlign == 'Top'){
                        labelClass = 'form-label-top';
                        inputClass = 'form-input-wide';
                    }
                    
                    if(qprop.labelAlign == 'Right'){
                        labelClass = 'form-label-right';
                        inputClass = 'form-input';
                    }
                    
                    if(qprop.labelAlign == 'Left'){
                        labelClass = 'form-label-left';
                        inputClass = 'form-input';
                    }
                }
            }
            if(labelClass == 'form-label-top'){
                labelWidth = ''; 
            }
			
            if($this.excludeColumns.include(key)){ $this.hideShowGridColumn(key, true);  return; /* continue;*/  }
            $this.hideShowGridColumn(key, false);
            
            switch(type){
				case "control_collapse":
				case "control_head":
					if(!showNonInputs){return; /* continue; */}
					var ht = "h2";
					if (qprop.headerType == "Large") {
		                ht = 'h1';
		            } else if (qprop.headerType == "Small") {
		                ht = 'h3';
		            }
					var h = '<li class="form-input-wide"><div class="form-header-group">';
					h += '<'+ht+' class="form-header">' + head + "</"+ht+">";
					if(qprop.subHeader){
						h += '<div class="form-subHeader">'+qprop.subHeader+'</div>';
					}
					h += '</div></li>';
					
					ul.insert(h);
				break;
				case "control_image":
					if(!showNonInputs){return; /* continue; */}
					var html = "";
					var imgAlt = "";
					var src = qprop.src;
					html += '<img alt="" ' + imgAlt + ' class="form-image" border="0" src="' + src + '" height="' + qprop.height + '" width="' + qprop.width + '" />';
		            
		            if (qprop.align == "Center") {
		                html = '<div style="text-align:center;">' + html + '</div>';
		            }
		            if (qprop.align == "Right") {
		                html = '<div style="text-align:right;">' + html + '</div>';
		            }

					ul.insert('<li class="form-line">'+html+'</li>');
				break;
				case "control_text":
					if(!showNonInputs){return; /* continue; */}
					ul.insert('<li class="form-line"><div class="form-input-wide">'+qprop.text+'</div></div></li>');
				break;
                case "control_checkbox":
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = []; 
                    }
                    var val = "<ul style='list-style: disc inside'><li>"+ (items.join("</li><li>")).stripslashes() +"</li><ul>";
                    if(items.length == 0){ val = "-"; }
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+ val +'</div></li>');
                break;
                case "control_datetime":
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = {}; 
                    }
                    var d = items;
                    var date;
                    var format = "yyyy-MM-ddTHH:mm:ssZ";
                    var convertFormat = "dddd, MMMM dd, yyyy h:mm:ss tt";
                    
                    // If date object is other than usual
                    if(!('year' in d && 'month' in d)){
                        d = {};
                    }
                    
                    if("ampm" in d){
                        date = d.year+"-"+d.month+"-"+d.day+"T"+Utils.convert12to24(d.hour+":"+d.min, d.ampm)+":00Z";
                    }else if("hour" in d){
                        convertFormat = "dddd, MMMM dd, yyyy h:mm:ss";
                        date = d.year+"-"+d.month+"-"+d.day+"T"+d.hour+":"+d.min+":00Z";
                    }else{
                        format = "yyyy-MM-dd";
                        convertFormat = "dddd, MMMM dd, yyyy";
                        date = d.year+"-"+d.month+"-"+d.day;
                    }
                    var parsed = Date.parseExact(date, format)? Date.parseExact(date, format).toString(convertFormat) : "";
                    if(!parsed && value != ""){
                    	parsed = value;
                    }
                    
                    var date_answer = '<img src="images/calendar.png" align="top" /> '+parsed;
                    
                    
                    if(!parsed){
                        date_answer = "-";
                    }
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+date_answer+'</div></li>');
                break;
                case "control_phone":
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = {}; 
                    }
                    var p = items;
                    if(!p.phone){
                        ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">-</div></li>');
                    }else{
                        ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'"><img src="images/telephone.png" align="top" /> ('+p.area+')-'+p.phone+'</div></li>');
                    }
                break;
                case "control_rating":
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label>'+
                    '<div class="'+inputClass+'"><div id="star_rating-'+qprop.qid+'" disabled="true" stars="'+(qprop.stars)+'" value="'+value+'"></div></div></li>');
                    var stars = 'images/stars.png';
                    switch(qprop.starStyle){
                        case "Hearts": stars = "hearts"; break;
                        case "Stars": stars = "stars"; break;
                        case "Stars 2": stars = "stars2"; break;
                        case "Lightnings": stars = "lightnings"; break;
                        case "Light Bulps": stars = "bulps"; break;
                        case "Shields": stars = "shields"; break;
                        case "Flags": stars = "flags"; break;
                        case "Pluses": stars = "pluses"; break;
                        default: stars = "stars";
                    }
                    $('star_rating-'+qprop.qid).rating({imagePath:"images/"+stars+".png"});
                break;
                case "control_scale":
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+($this.cleanValue(value) || 0)+'/'+qprop.scaleAmount+'</div></li>');
                break;
                case "control_slider":
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+($this.cleanValue(value) || 0)+'/'+qprop.maxValue+'</div></li>');
                break;
                case "control_range":
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = {}; 
                    }
                    var range  = "From:".locale() + " " + ($this.cleanValue(items.from) || 0)+"<br>";
                        range += "To:".locale() + " " + ($this.cleanValue(items.to) || 0)+"<br>";
                        range += "Difference: "+($this.cleanValue(items.to)-$this.cleanValue(items.from))+"<br>";
                    
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+range+'</div></li>');
                break;
                case "control_grading":
                    var grading = "";
                    var opts = qprop.options.split("|");
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = []; 
                    }
                    for(var x=0; x<opts.length; x++){
                        grading += opts[x] +": "+ ($this.cleanValue(items[x]) || 0) +"<br>";
                    }
                    if(items == []){
                        grading += "Total: 0";
                    }else{
                        grading += "Total: "+qprop.total;
                    }
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+grading+'</div></li>');
                break;
                case "control_matrix":
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = {}; 
                    }
                    html  = '<table summary="" cellpadding="4" cellspacing="0" class="form-matrix-table"><tr>';
                    html += '<th style="border:none">&nbsp;</th>';
                    var cols = qprop.mcolumns.split('|');
                    var colWidth = (100/cols.length+2)+"%";
                    $A(cols).each(function(col){
                        html +=  '<th class="form-matrix-column-headers" style="width:'+colWidth+'">' + col + '</th>';
                    });
                    html += '</tr>';
                    
                    $A(qprop.mrows.split('|')).each(function(row, ri){
                        html += '<tr>';
                        html += '<th align="left" class="form-matrix-row-headers" nowrap="nowrap">'+row+'</th>';
                        $A(qprop.mcolumns.split('|')).each(function(col, ci){
                            var input = "-";
                            if(!(ri in items)) { items[ri]=''; }
                            switch(qprop.inputType){
                                case "Radio Button":
                                    input = (items[ri] == col.sanitize())? '<img src="images/tick.png" align="top" />' : "-";
                                break;
                                case "Check Box":
                                    input = (items[ri].include(col.sanitize()))? '<img src="images/tick.png" align="top" />' : "-";
                                break;
                                case "Text Box":
                                    input = items[ri][ci] || "-";
                                break;
                                case "Drop Down":
                                    input = items[ri][ci] || "-";
                                break;
                            }
                            html += '<td align="center" class="form-matrix-values" >'+input+'</td>';
                        });
                        html += "</tr>";
                    });
                    html += "</table>";
                    
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+html+'</div></li>');
                break;
                case "control_fileupload":
                    var values = value.split(/\<br\s\/\>/gim);
                    var htmlContent = '';
                    $A(values).each(function(value){
                        var link = value.match(/href=\"(.*?)\"/);
    					if (!link) {
    						link = "";
    					} else { link = link[1]; }
                        var ext = Utils.getFileExtension(link);
                        if(Utils.imageFiles.include(ext.toLowerCase())){
                            htmlContent += '<div style="height:100px; width:100px; overflow:hidden;border:1px solid #ccc;"><img height="100" src="'+link+'" /></div>'+value;
                        }else{
                            if(value == ""){ value = "-"; }
                            htmlContent += value;
                        }
                        
                        htmlContent += '<br><br>';
                    });
                    
                    var li = '<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">' + htmlContent + '</div></li>';
                    
                    ul.insert(li);
                break;
                case "control_address":
                    if(!items){ 
                        if (hideEmpty) {
                            return; /* continue; */ 
                        }
                        items = {}; 
                    }
                    
                    if(value == ""){ value = "-"; }
                    
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'"> '+$this.cleanValue(value)+'<div id="mapContainer'+key+'" style="display:none;min-height:270px;"><div id="mapCanvas'+key+'" style="width:300px;height:250px;margin-top:10px;border:1px solid #000"></div></div></div></li>');
                    
                    if ("https:" != document.location.protocol && showMaps == true) {
                        GoogleMap.setMap(key, $this.cleanValue($H(items).values().join(" ")));
                    }
                    break;
                
                default:
                    if(value == ""){ value = "-"; }
                    ul.insert('<li class="'+lineClass+'"><label '+labelWidth+'class="'+labelClass+'"><b>'+head+'</b></label> <div class="'+inputClass+'">'+$this.cleanValue(value)+'</div></li>');
            }
        }
		
		
        // $A($this.data.columns).each(function(column){
		$A(['id', 'created_at', 'ip']).each(processField);
		$H($this.properties).each(processField);
        
        if(emails.length > 0){
            var email = emails.join(",");
            $('replyButton').enable().onclick = function(){
                $this.sendEmail(email, $this.convertToEmail(sc.innerHTML), 'reply');
            }; 
        }else{
            $('replyButton').disable(); 
        }
        
        $('forwardButton').onclick = function(){
            $this.sendEmail(email, $this.convertToEmail(sc.innerHTML), 'forward');
        }; 
        


    },
    /**
     * Opens a wizard for sending emails
     * @param {Object} email
     * @param {Object} submission
     * @param {Object} type
     */
    sendEmail: function (email, submission, type){
        var $this = this;
        var sline = [];
        $A(submission.split("<br>")).each(function(line){
            if(!line.strip()){ return; /* continue; */}
            sline.push(""+line);
        }); 
        
        var forward;
        
        if(type == "forward"){
            forward = "<br>"+sline.join("<br>");
        }else{
            forward = "<br><br><br><br><br>On "+$this.getSelected().data.created_at+", &lt;"+email+"&gt; submitted:<br><br><div style='color:#1c4dae; margin-left:10px; border-left:2px solid #1c4dae;padding-left:10px;'>"+sline.join("<br>")+"</div>";
        }
        
        
        var div = new Element('div'), toField, fromField, messageField, textDiv;
        div.insert(new Element('label').insert('<b>' + '[email_from]'.locale() + '</b>').setStyle('float:left; width:100px;'));
        div.insert(fromField = new Element('input', {type:'text'}).setStyle('width:500px'));
        div.insert('<br><br>');
        div.insert(new Element('label').insert('<b>' + '[email_to]'.locale() + '</b>').setStyle('float:left; width:100px;'));
        div.insert(toField = new Element('input', {type:'text'}).setStyle('width:500px'));
        div.insert('<br><br>');
        div.insert(new Element('label').insert('<b>' + '[email_subject]'.locale() + '</b>').setStyle('float:left; width:100px;'));    
        div.insert(subjectField = new Element('input', {type:'text'}).setStyle('width:500px'));
        div.insert('<br><br>');
        //div.insert(new Element('label').insert('<b>' + '[email_body]' + '</b>'));
        //div.insert('<br>');
        div.insert(textDiv = new Element('div').setStyle('background:#fff'));
        textDiv.insert(messageField = new Element('textarea', {id: "email-body"}).setStyle('width:600px;height:300px;'));
        
        toField.value = type == "forward"? "" : email;
        fromField.value = Utils.user.email || "Enter Your Email Address".locale();
        subjectField.value = type == "forward"? "Fwd: Submission".locale() : "Re: Your Submission".locale();
        
        messageField.value = forward;
        
        var emailWizard = document.window({
            title:type == "forward"? "Forward Submission".locale() : "Reply Submission".locale(),
            content: div,
            modal:true,
            width:'640',
            dimZindex: 10012,
            winZindex: 10013,
            contentPadding:'15',
            buttons:[{
                title:type == "forward"? 'Forward Submission'.locale() : 'Send Reply'.locale(),
                name:'send',
                handler:function(w){
                    $(fromField, toField).invoke("removeClassName", "error");
                    if (!Utils.checkEmailFormat(fromField.value)) {
                        fromField.addClassName('error');
                        return;
                    }
                    if (!Utils.checkEmailFormat(toField.value)) {
                        toField.addClassName('error');
                        return;
                    }
                    Utils.Request({
                       parameters: {
                           action: "sendEmail",
                           from: fromField.value,
                           to: toField.value,
                           subject: subjectField.value,
                           body: Editor.getContent(messageField.id)
                       },
                       onSuccess: function(res){
                           w.close();
                           Utils.alert("Email sent successfully.".locale());
                       },
                       onFail: function(res){
                           Utils.alert(res.error, "Error");
                       }
                    });
                }
            }],
            onInsert: function(w){
                Locale.changeHTMLStrings();
                Editor.set(messageField.id);
            }
        });
        
    },
    currentSelection: 0,
    /**
     * Gets the currently selected row from the grid
     */
    getSelected: function(){
        return this.grid.getSelectionModel().getSelected() || false;
    },
    
    /**
     * Formats given date in mysql format
     * @param {Object} date
     */
    mySQLFormat: function(date){
        if(!date){
            return null;
        }
        return date.toString('yyyy-MM-dd HH:mm:ss');
    },
    
    startDate: '',
    endDate: '',
    
    /**
     * Sets grid into selected range
     * @param {Object} start
     * @param {Object} end
     */
    setDateRange: function(start, end){
        var startDate, endDate;
        endDate = Date.today()._add(1).days();
        var type = start;
        
        switch(start){
            case "today":
                startDate = Date.today();
            break;
            case "week":
                if(Date.parse('today').getDayName() == "Monday"){
                    startDate = Date.today();
                }else{
                    startDate = Date.parse('last monday');
                }
            break;
            case "month":
                startDate = Date.today().moveToFirstDayOfMonth();
            break;
            case "year":
                startDate = new Date((new Date(new Date(Date.today().setMonth(0))).setDate(1)));
            break;
            case "all":
                startDate = null;
                endDate = null;
            break;
            default:
                type = "custom";
                startDate = Date.parse(start);
                endDate   = Date.parse(end);
        }
        
        if(type !== 'all'){
            var query = "";
            query = "Only This "+type.capitalize();
            if(type == 'today'){
                query = 'Only Today';
            }
            if(type == 'custom'){
                query = 'Dates between '+start+' and '+end+'';
            }
            if($('notification')){
                $('notification').update('<img src="images/information.png" align="top" /> Displaying submissions: <b>'+ query+'</b>').show();
            }
        }else{
            if($('notification')){
                $('notification').update().hide();
            }
        }
        
        this.saveDateRange(type);
        var store = this.grid.getStore();
        
        this.startDate = this.mySQLFormat(startDate);
        this.endDate = this.mySQLFormat(endDate);
        
        store.setBaseParam('startDate', this.startDate);
        store.setBaseParam('endDate', this.endDate);
        
        if(this.bbar.doRefresh){
            this.bbar.doRefresh();
        }
        this.saveColumnSettings();
    },
    /**
     * First Checks all duplicate options if found removes all and re-places first found instance
     * Basically cleans up the configuration array.
     * This is needed because of a previous bug. we have mistakenly places the same configuration in the array many times
     * So this array needs to be cleaned up for all users. Patch applied  06 / Dec / 2010
     */
    checkDuplicates: function(){
        var $this = this;
        this.excludeColumns = $A(this.excludeColumns).uniq();
        var found = 0;
        var firstOption = "";
        $A(this.excludeColumns).each(function(v, i){
            if(v.include && v.include('rangeType:')){
                if(firstOption === ""){
                    firstOption = v;
                }
                found++;
            }
        });
        
        if(found > 1){
            $A(this.excludeColumns).each(function(v, i){
                if(v.include && v.include('rangeType:')){
                    delete $this.excludeColumns[i];
                }
            });
            this.excludeColumns.push(firstOption);
            this.excludeColumns = $A(this.excludeColumns).compact();
        }
        
    },
    
    
    /**
     * Saves the selected range type into DB
     * @param {Object} type
     */
    saveDateRange: function(type){
        
        this.checkDuplicates();
        
        var savedindex = false;
        $A(this.excludeColumns).each(function(v, i){
            if(v.include && v.include('rangeType:')){
                savedindex = i;
                throw $break;
            }
        });
        
        var data = "rangeType:"+type;
        if(savedindex !== false){
            this.excludeColumns[savedindex] = data;
        }else{
            this.excludeColumns.push(data);
        }
    },
    
    /**
     * Gets last the saved range type
     */
    getDateRange: function(){
        var type = "all";
        $A(this.excludeColumns).each(function(v, i){
            
            if(v.include && v.include('rangeType:')){
                type = v.split(":")[1];
                throw $break;
            }
        });
        
        return type;
    },
    
    /**
     * Saves the custom date range in columns settings
     * @param {Object} start
     * @param {Object} end
     */
    saveCustomDate: function(start, end){
        var savedindex = false;
        $A(this.excludeColumns).each(function(v, i){
            if(v.include && v.include('custom:')){
                savedindex = i;
                throw $break;
            }
        });
        
        if(!start || !end){
            if (savedindex !== false) {
                delete this.excludeColumns[savedindex];
                this.excludeColumns = $A(this.excludeColumns).compact();
            }
            return;
        }
        
        var data = "custom:"+start+","+end;
        if(savedindex !== false){
            this.excludeColumns[savedindex] = data;
        }else{
            this.excludeColumns.push(data);
        }
    },
    
    /**
     * Gets the selected custom date from columns settings
     */
    getCustomDate: function(){
        var range = false;
        $A(this.excludeColumns).each(function(v, i){
            if(v.include && v.include('custom:')){
                range = v;
                throw $break;
            }
        });
        
        if(range){
            var rawdates = range.split(":")[1];
            var dates = rawdates.split(",");
            
            return {"start": dates[0], "end":dates[1]};
        }
        return false;
    },
    
    
    /**
     * Creates the date range fields
     */
    createRangePicker: function(start, end){
        // Date picker              
        var fromdate = new Ext.form.DateField({
            format: 'Y-m-d', //YYYY-mm-DD
            fieldLabel: '',
            id: 'startdt',
            name: 'startdt',
            width:140,
            allowBlank:false,
            vtype: 'daterange',
            endDateField: 'enddt'// id of the 'To' date field
        });
 
        var todate = new Ext.form.DateField({
            format: 'Y-m-d', //YYYY-mm-DD
            fieldLabel: '',
            id: 'enddt',
            name: 'enddt',
            width:140,
            allowBlank:false,
            vtype: 'daterange',
            startDateField: 'startdt'// id of the 'From' date field
        });
        
        
        fromdate.render('fromdate');
        todate.render('todate');
        
        fromdate.setRawValue(start || "");
        todate.setRawValue(end || "");
    },
    
    /**
     * Initiates the grid on load
     * @param {Object} response
     */
    initGrid: function (response){
        
        try{
        var $this = this;
        $this.data = response;
        var form = $this.data.formID;
        var itemname = "Submissions".locale();
        var standAlone = !$('submissions-grid');
        var rpp = 14;
        
        if($('settings')){
            $('settings').setUnselectable();
            $('settings').observe('click', function(e){
                if(e.target.id == "settings"){
                    $this.toggleSettings();                    
                }
            });
        }
        
        if(standAlone){
            rpp = Math.floor((document.viewport.getDimensions().height - 22 - 27) / 22);
        }
        
        var searchfield = new Ext.form.TextField({
            emptyText:"Search In Submissions".locale(),
            id:"search",
            width:210,
            listeners:{
                render: function(f){
                    f.el.on('keydown', function(e, el){
                        store.load({params: { start: 0, limit: rpp, keyword: el.value}});
                    }, f, {buffer: 350});
                }
            }
        });
        
        if(!("listID" in window)) {
            listID = "";
        }
        
        var store = new Ext.data.Store({
            proxy: new Ext.data.ScriptTagProxy({
                url: Utils.HTTP_URL+'server.php?action=getExtGridSubmissions&formID=' + form + '&listID=' + listID
            }),
            reader: new Ext.data.JsonReader({
                root: 'data',
                totalProperty: 'totalCount',
                id: 'id',
                fields: $this.data.fields
            }),
            remoteSort: true,
            listeners:{
                beforeload: function(store, options){
                    var keyword = searchfield.getValue();
                    store.setBaseParam('keyword', keyword);
                },
                load:function(){
                    
                    setTimeout(function(){
                        if (Submissions.selectedSubmissionIndex) {
                            Submissions.selectedSubmissionIndex = undefined;
                            return;
                        }
                        if($this.keepLastSelection){
                            $this.grid.getSelectionModel().selectRow($this.currentSelection);
                            $this.keepLastSelection = false;
                            return;
                        }
                        var page = $this.bbar.getPageData().activePage;
                        $this.lastPageNum = $this.currentPageNum;
                        $this.currentPageNum = page;
                        if($this.currentPageNum > $this.lastPageNum){
                            $this.grid.getSelectionModel().selectFirstRow();
                        }else{
                            $this.grid.getSelectionModel().selectLastRow();
                        }
                    }, 100);
                },
                loadexception: function(){
                    
                    Submissions.grid.getStore().removeAll();
                    if($('sub-content')){
                        $('sub-content').update('<table width="100%" height="100%" id="no-result"> <tr> <td align="center"> <h2>'+
                        
                        'No results to display.'.locale() +
                        
                        '</h2> ' +
                        
                        'Check your search query or time frame options.'.locale() +
                        
                        '</td></tr></table>');
                    }
                }
            }
        });
        
        // This code adds a row numbers to grid however you need to set 
        // new and flag markers again to fix confusion
        // $this.data.columns.unshift(new Ext.grid.RowNumberer());
        
        var cm = new Ext.grid.ColumnModel($this.data.columns);
        if(!standAlone){
            
            cm.setRenderer(2, function(value, obj, row){
                return '<img src="images/blank.gif" class="delimg index-cross" id="img-'+row.data.submission_id+'" onclick="Submissions.deleteSubmission(\''+row.data.submission_id+'\', this)" />';
            });
            
            cm.setRenderer(0, function(value, obj, row){
                if(value == "0"  || !value){
                    return '<img src="images/mail-open.png" id="img-'+row.data.submission_id+'" onclick="Submissions.makeUnread(\''+row.data.submission_id+'\', this)" />';
                }
                return '<img src="images/mail.png" id="img-'+row.data.submission_id+'" />';
            });
            
            cm.setRenderer(1, function(value, obj, row){
                if(value == "0" || !value){
                    return '<img src="images/flag-disable.png" onclick="Submissions.flag(\''+row.data.submission_id+'\', this)" />';
                }
                return '<img src="images/flag.png" onclick="Submissions.unflag(\''+row.data.submission_id+'\', this)" />';
            });
        }
        
        var slowLimit = 10000;
        
        var tbar = {
            items:['Download as:'.locale()+" ", {
                text:'Excel',
                iconCls: 'excelButton',
                handler: function(){
                    var length = $this.grid.getStore().totalLength;
                    
                    if(length < 1){
                        Utils.alert('There is no data to export');
                        return;
                    }
                    
                    if(length > slowLimit){
                        Utils.alert('<img src="images/redirect-loader.gif" align="left" style="margin:10px;"/><span style="line-height:20px;">'+
                        'You have %s submissions. Creating this report may take some time. Please wait until report is completed. Thanks.'.locale(length)+
                        '</span>', false, false, {
                            width:360
                        });
                    }
                    
                    Utils.redirect(Utils.HTTP_URL+'server.php', {
                        method:'post',
                        encode:true,
                        parameters: {
                            action:'getExcel',
                            formID:form,
                            excludeList:$this.excludeColumns.join(','),
                            startDate: $this.startDate || "",
                            endDate: $this.endDate || ""
                        }
                    });
                    
                }
            },/*{
                text:'Word',
                iconCls: 'wordButton',
                handler: function(){}
            },{
                text:'PDF',
                iconCls: 'pdfButton',
                handler: function(){}
            },*/{
                text:'CSV',
                iconCls: 'csvButton',
                handler: function(){
                    var length = $this.grid.getStore().totalLength;
                    
                    if(length < 1){
                        Utils.alert('There is no data to export');
                        return;
                    }
                    
                    if(length > slowLimit){
                        Utils.alert('<img src="images/redirect-loader.gif" align="left" style="margin:10px;"/><span style="line-height:20px;">'+
                        'You have %s submissions. Creating this report may take some time. Please wait until report is completed. Thanks.'.locale(length)+
                        '</span>', false, false, {
                            width:360
                        });
                    }
                    
                    Utils.redirect(Utils.HTTP_URL+'server.php', {
                        method:'post',
                        encode:true,
                        parameters: {
                            action:'getCSV',
                            formID:form,
                            excludeList:$this.excludeColumns.join(','),
                            startDate: $this.startDate || "",
                            endDate: $this.endDate || ""
                        }
                    });
                    
                }
            }, '->',{
                text:'Delete All',
                iconCls: 'deleteAll',
                handler: function(){
                    Utils.prompt('<img align="left" src="images/warning.png" style="margin:10px;"><div style="padding:16px 0 0;"><h3 style="font-size: 13px;margin: 0;padding: 0;">You are about to delete all submissions.</h3>Please enter your password to proceed</div>', "", "Delete All Submissions", function(value, button, clicked){
                        if(clicked){
                            Utils.Request({
                                parameters:{
                                    password:value,
                                    action:'deleteAllSubmissions',
                                    formID:form
                                },
                                onSuccess: function(){
                                    $this.bbar.doRefresh();
                                },
                                onFail: function(t){
                                    Utils.alert(t.error, 'Problem');
                                }
                            })
                        }else{
                        }
                    }, {
                        width:400,
                        fieldType:'password',
                        okText:'Delete All submissions'
                    });
                }
            },'-', {
                text:'Larger Grid',
                enableToggle : true,
                iconCls: 'largerGrid',
                toggleHandler: function(but, state){
                    
                    if(state){
                        $('submissions-grid').setStyle('height:980px');
                        $this.grid.setHeight(980);
                        $this.bbar.pageSize = 35;
                    }else{
                        $('submissions-grid').setStyle('height:450px');
                        $this.grid.setHeight(450);
                        $this.bbar.pageSize = 14;
                    }
                    $this.bbar.doRefresh();
                }
            }]
        };
        
        if(standAlone){
            tbar = false;
        }
        
        cm.defaultSortable = true;
        var lastRowBody = false;
        var stateID = form + ((standAlone)? '-reports' : '-submissions');
        
        if (listID === '' && !$this.publicListing) {
            Ext.state.Manager.setProvider(new Ext.ux.state.HttpProvider({
                id: stateID,
                readUrl: 'server.php',
                saveUrl: 'server.php',
                readBaseParams: {
                    action: 'getSetting',
                    key: 'extGridState'
                },
                saveBaseParams: {
                    action: 'setSetting',
                    key: 'extGridState'
                },
                paramNames: {
                    id: 'identifier',
                    data: 'value',
                    name: 'grid-state',
                    value: 'value',
                    name: 'name'
                },
                autoRead:false
            }));
            if ($this.data && $this.data.extGridState) {
                try{
                   Ext.state.Manager.getProvider().initState(Ext.decode($this.data.extGridState.value));
                }catch(e){
                   Ext.state.Manager.getProvider().initState([]);
                }
            }
        }
        
        $this.grid = new Ext.grid.GridPanel({
            renderTo: standAlone? false : 'submissions-grid',
            width: !standAlone? 900 : "",
            height: !standAlone? 450 : "",
            //autoHeight: true,
            //autoScroll:true,
            store: store,
            stateId: stateID,
            //layout:'fit',
            //enableColumnMove:true,
            stateful: (listID === '' && !$this.publicListing),
            //forceLayout:true,
            clicksToEdit:1,
            trackMouseOver:true,
            stripeRows: true,
            viewConfig: {
                forceFit:false,
                alternate:true,
                enableRowBody:true,
                resizable:true
            },
            listeners:{
                render: function(e){
                    (!standAlone && $('content-wrapper').show());
                }
            },
            cm: cm,
            loadMask: true,
            sm: new Ext.grid.RowSelectionModel({
                listeners:{
                    rowselect: function(sm, index, selected){
                        (!standAlone && $('content-wrapper') && $this.displayRowData(selected));
                        //window.scrollTo(0,document.body.scrollHeight - 810);
                        //alert(document.body.scrollHeight);
                        $this.currentSelection = index;
                        if(standAlone){
                            /*
                            if(lastRowBody){ lastRowBody.hide(); }
                            
                            var rowBody = $(Submissions.grid.getView().getRow(index)).select('.x-grid3-row-body')[0];
                            
                            var dataDump = "<div style='padding:10px; overflow:auto; max-height:100px;'>";
                            $H(selected.data).each(function(pair){
                                var head = $this.getHeader(pair.key);
                                
                                if(!head){return; }
                                if(pair.key == "ip" || pair.key == "created_at"){ return; }
                                
                                //if(pair.value.length < 100){ return; }
                                
                                
                                dataDump+="<div style='margin:5px;' ><label style='clear:left;float:left; width:150px;'><b>"+head+"</b></label><div>"+pair.value+"</div></div>";
                            });
                            dataDump +="</div>";
                            lastRowBody = rowBody.update(dataDump).show();
                            */
                        }
                    }
                }
            }),
            tbar: tbar,
            bbar: $this.bbar = new Ext.PagingToolbar({
                pageSize: rpp,
                store: store,
                displayInfo: true,
                displayMsg: $this.data.itemname+' {0} - {1} of {2}',
                emptyMsg: "No %s to display".locale($this.data.itemname),
                items:['-',searchfield]
            })
        });
        
        if($this.publicListing){
            $this.hideShowGridColumn('new', true);
            $this.hideShowGridColumn('flag', true);
            $this.hideShowGridColumn('del', true);
        }
        
        store.setDefaultSort('created_at', 'desc');
        $A($this.data.columns).each(function(e){ e.width = parseInt(e.width, 10); });
        
        var dateRange = this.getDateRange();
        if(dateRange != "custom") {
            this.setDateRange(dateRange);
        }else{
            var custom = this.getCustomDate();
            this.setDateRange(custom.start, custom.end);
        }

        if(standAlone){
            new Ext.Viewport({
                layout: 'fit',
                items: $this.grid 
            });        	
        }else{
            $this.grid.render();
        }

        var startRow = 0;
        // If there's submission ID in the URL, load that submission.
        if (document.get.submissionID) {
            this.getSubmissionIndex();
            startRow = Math.floor(Submissions.selectedSubmissionIndex / rpp) * rpp;
        }
        
        store.load({
            params: {
                start: startRow, 
                limit: rpp
            }, 
            callback: function() {
                var sid;
                if ((sid = document.get.submissionID)) {
                    var sm = Submissions.grid.getSelectionModel();
                    var submissionRow = Submissions.grid.store.find('submission_id', sid);
                    sm.selectRow(submissionRow);
                }
            }
        });
        
        $this.checkDropboxIntegration();
        $this.checkFTPIntegration(function(){
            if(document.readCookie('open-ftp-wizard') == 'yes'){
                $this.toggleSettings(); // First open preferences page
                $this.FTPIntegration(); // then open FTP wizard
                document.eraseCookie('open-ftp-wizard'); // then remove cookie to stop this behaviour
            }
        });
        }catch(e){
            console.error(e);
        }
    },
    checkFTPIntegration: function(callback){
        Utils.Request({
            parameters:{
                action:'getIntegration',
                type:'FTP',
                username: Utils.user.username,
                formID: this.data.formID,
                keys:'host,username,port,path,folder_field' 
            },
            onComplete: function(res){
                if(res.success){
                    Submissions.FTPIntegrated = true;
                    Submissions.FTPProps = res.values;
                }else{
                    Submissions.FTPIntegrated = false;
                }
                (callback && callback());
            }
        });
    },
    checkDropboxIntegration: function(){
        Utils.Request({
            parameters:{
                action:'getIntegration',
                type:'dropbox',
                username: Utils.user.username,
                formID: this.data.formID,
                keys:'folder_field' 
            },
            onComplete: function(res){
                if(res.success){
                    Submissions.dropboxIntegrated = true;
                    Submissions.dropboxFolderField = res.values.folder_field;
                }else{
                    Submissions.dropboxIntegrated = false;
                }
            }
        });
    },
    getSubmissionIndex: function() {
        var ss = Submissions.grid.store.getSortState();
        Utils.Request({
            server: Utils.HTTP_URL+"server.php",
            parameters: {
                action: "getSubmissionIndex",
                submissionID: document.get.submissionID,
                formID: this.data.formID,
                sortDir: ss.direction,
                sortField: ss.field
            },
            asynchronous: false,
            onSuccess: function(res){
                Submissions.selectedSubmissionIndex = parseInt(res.subIndex, 10);
            }
        });
    }
};
var Utils = Utils || new Common();