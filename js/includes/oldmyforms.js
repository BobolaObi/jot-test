var MyForms = {
    selected: false,       // Selected form
    selectedFolder: false, // Selected Folder
    folderCount: 1024,     // Folder ID start {@deprecated}
    response: false,       // Form list from server response
    forms: {},             // Corrected form lists
    syncInterval: false,
    /**
     * Initialize the MyForms page
     */
    initialize: function(){
        try{
            $('forms').cleanWhitespace();
            
            if(!Utils.user){
            	$('account-type').update("Guest".locale());
                if($('account-notification')){
                	$('account-notification').update('You are currently not logged in. <a href="/page.php?p=login">Click here to log in.</a>');
                }
                return;
            }
            if (Utils.user.usage.accountType) {
            	$('account-type').update(Utils.user.usage.accountType.prettyName.locale());
            	if (Utils.user.accountType == "GUEST") {
                    if($('account-notification')){
                		$('account-notification').update('You are currently not logged in. <a href="/page.php?p=login">Click here to log in.</a>');
                    }
            	}
            }
            
            $('group-myforms', 'group-properties').invoke('cleanWhitespace');
            Utils.setToolbarFloat();
            Utils.setToolboxFloat();
            
            $$('.forms').each(function(li){
                li.observe('mousedown', function(e){
                    if(e.target.nodeName == 'IMG'){return;}
                    this.selectForm(li);
                }.bind(this));
            }.bind(this));
            
            $$('.folder').each(function(li){
                li.observe('mousedown', function(){
                    this.selectFolder(li);
                }.bind(this));
            }.bind(this));
            
            this.setSearchForm();
            
            if(location.hash.match('#reports-')){
                setTimeout(function(){
                    var reportFormID = location.hash.replace('#reports-', '');
                    if($('form_'+reportFormID)){
                        if($('form_' + reportFormID) != this.selected){
                            this.selectForm($('form_'+reportFormID));
                        }
                        $('reportsButton').click();
                    }
                }.bind(this), 800);
            }
            
            if($('upgradeNow')){
                $('upgradeNow').observe('click', function(){
                    location.href = 'pricing/';
                });
            }
            
            setTimeout(this.setBars.bind(this), 200);
            // Check the page every minute
            this.syncInterval = setInterval(this.syncPage.bind(this), 30000);
        }catch(e){
            console.error(e);
        }
    },
    /**
     * Set the account status bars and give colors by percentage
     */
    setBars: function(noAnimation){
        
        var colors = {
            "40" : Protoplus.Colors.colorNames.LightGreen,
            "60" : Protoplus.Colors.colorNames.MediumSeaGreen,
            "80" : Protoplus.Colors.colorNames.Orange,
            "100" : Protoplus.Colors.colorNames.OrangeRed
        };
        
        method = noAnimation? "setStyle" : "shift";
        
        var getColor = function(num){
            for(var i in colors){
                if(num <= i){
                    return colors[i]; 
                }
            }
            return "#FF0000";
        };
        
        $$('.bar-inner').invoke('setStyle', {width:'0%', background:'#FFFFFF'});
        
        $('submissions')[method]({width: this.getPercent('submissions')+"%", backgroundColor:getColor(this.getPercent('submissions'))  });
        $('uploads')[method]({width:this.getPercent('uploads')+"%", backgroundColor:getColor(this.getPercent('uploads'))});
        $('payments')[method]({width:this.getPercent('payments')+"%", backgroundColor:getColor(this.getPercent('payments'))});
        $('ssl')[method]({width:this.getPercent('sslSubmissions')+"%", backgroundColor:getColor(this.getPercent('sslSubmissions'))});
    },
    
    searchCross: false,
    setSearchForm: function(){
        var $this = this;
        $('search-forms').observe('keyup', function(){
            $this.searchForms($('search-forms').value);
        });
	    $('search-forms').hint('Search In Form Titles'.locale());
        this.searchCross = new Element('img', {src:'images/blank.gif', className:'index-cross'});
        this.searchCross.setStyle('position:absolute;bottom:15px;right:25px;cursor:pointer;');
        $('myforms-search').insert(this.searchCross);
        this.searchCross.hide();
        this.searchCross.observe('click', function(){
            $('search-forms').hintClear();
            $this.searchForms();
        });
        
        if($H(this.forms).keys().length >= 20){
            $('myforms-search').show();
        }

    },
    
    searchForms: function(keyword){
        
        if(!keyword){
            $$('.forms').invoke('show');
            this.searchCross.hide();
            return;
        }
        this.searchCross.show();
        
        $$('.forms').each(function(f){
            if (!f.innerHTML.toLowerCase().include(keyword.toLowerCase()) && !f.id.toLowerCase().include(keyword.toLowerCase())) {
                f.hide();
            } else {
                f.show();
            }
        });
    },
    
    getSelectedID: function(){
        return this.selected.id.replace("form_", "");
    },
    
    getSelectedForm: function(){
        return this.forms[this.getSelectedID()];
    },
    
    /**
     * Open the submissions page for selected form
     */
    openSubmissions: function(){
        
        /*if(this.getSelectedForm().count < 1){
            Utils.alert('This form has no submissions to view.'.locale());
            return;
        }*/
        
        Utils.redirect("submissions/"+this.getSelectedID());
    },
    /**
     * Deletes a given report
     * @param {Object} id
     * @param {Object} type
     */
    deleteReport: function(index){
        var formID = this.getSelectedID();
        var report = this.forms[formID].reports[index];
        
        Utils.Request({
            parameters:{
                action:'deleteReport',
                id: report.id,
                type: report.type
            },
            onSuccess: function(res){
                delete MyForms.forms[formID].reports[index];
                MyForms.forms[formID].reports = $A(MyForms.forms[formID].reports).compact();
                $('reportsButton').removeClassName('button-over')
    	        $('reportsBox').remove();
                MyForms.openReports();
            },
            onFail: function(res){
                Utils.alert(res.error, "Error");
            }
        });
    },
    
    /**
     * Open the reports page for selected form
     */
    openReports: function(){
        
		/*if(this.getSelectedForm().count < 1){
            Utils.alert('This form has no submissions to report.'.locale());
            return;
        }*/
		// Hide reports list if visible on the page
		if($('reportsBox')){
			$('reportsButton').removeClassName('button-over')
			$('reportsBox').remove();
			return;
		}
		// Create and show the reports list if it was newer created
		$('reportsButton').addClassName('button-over')
		var box = new Element('div', {className:'edit-box', id:'reportsBox'});
		var off = $('reportsButton').cumulativeOffset();
		var dim = $('reportsButton').getDimensions();
		
		box.setStyle({position:'absolute', top:(off.top + dim.height)+'px', left:(off.left)+'px', width:'200px', zIndex:1});
		var list = new Element('div').setStyle('background:#FFFFFF; border:1px solid #AAAAAA; list-style:none outside none; margin:5px 0;'); 
		
		var addNew = new Element('button', {className:'big-button buttons'}).setStyle('float:right; font-size:14px;').insert('<img align="top" src="images/add.png" /> ' + 'Add New Report'.locale());
		
		box.insert('<b style="font-size: 14px; color: #333;">' + 'Report List'.locale() + '</b>');
		box.insert(list);
		box.insert(addNew);
		var formID = this.getSelectedID();
		$(document.body).insert(box);
        
        box.positionFixed({
            offset:69
        });
        box.updateTop(176);
        box.updateScroll();
		
        this.getReports(formID, function(reports){
            
    		addNew.observe('click', function(e){
                
                $('reportsButton').removeClassName('button-over')
                $('reportsBox').remove();
                Utils.loadScript('js/includes/reports_wizard.js', function(){
                    ReportWizard.openWizard();
                });
    		});
    		
    		if(reports.length < 1){
    			
    			list.update('<div style="padding:5px;">'+'You have no reports created for this form.'.locale()+'</div>');
    			
    		}else{
    			$A(reports).each(function(report, i){
    				var reportLi = new Element('li', {className:'report-list-item'});
    		        reportLi.insert('<img src="images/myforms/'+report.type+'.png" align="absmiddle" /> ' + report.title + '<img onclick="MyForms.deleteReport('+i+')" class="report-list-item-delete" align="right" src="images/blank.gif" class="index-cross" />');
    		        
    		        reportLi.setStyle({margin:'3px', border:'1px solid #ccc', background:'#eee', padding:'3px', cursor:'pointer'});
    		        
    		        reportLi.mouseEnter(function(){
    		            reportLi.setStyle({background:'#ddd', border:'1px solid #aaa'});            
    		        }, function(){
    		            reportLi.setStyle({background:'#eee', border:'1px solid #ccc'});
    		        });
    		        
    		        reportLi.observe('click', function(e){
    		            if($(e.target) && $(e.target).hasClassName('report-list-item-delete')){
                            return;
                        }
                        if(report.type == "visual"){
                            Utils.redirect('page.php', {
                                parameters:{
                                    p:'reports',
                                    formID: formID,
                                    reportID: report.id
                                }
                            });
                        }else{
                            Utils.loadScript('js/includes/reports_wizard.js', function(r){
                                ReportWizard.openWizard(r);
                            }, report);
                        }
                        
                        
    	                $('reportsButton').removeClassName('button-over').setStyle('height:68px;');
    	                $('reportsBox').remove();
    		        });
    		        
    		        list.insert(reportLi);
    			});
    		}
            
        });
		
    },
    /**
     * Edit the selected form in formbuilder
     */
    editForm: function(){
        Utils.redirect(Utils.HTTP_URL, {
            parameters:{
                formID:this.getSelectedID()
            }
        });
    },
    
    getReports: function(id, callback){
        var $this = this;
        if($this.forms[id].reports){
            callback($this.forms[id].reports);
        }else{
            Utils.Request({
                parameters:{
                    action: 'getReports',
                    formID: id
                },
                onSuccess: function(res){
                    $this.forms[id].reports = res.reports;
                    callback(res.reports);
                },
                onFail: function(res){
                    Utils.alert('Cannot read the reports, please try again later.');
                }
            })
        }
    },
    
    /**
     * Clones the selected form in to this account 
     */
    cloneForm: function(formID){
        
        Utils.Request({
            parameters: {
                action:'cloneForm',
                formID: formID || this.getSelectedID()
            },
            onSuccess: function(response){
                Utils.redirect(Utils.HTTP_URL, {
                    parameters:{
                        formID: response.newID
                    }
                });
            },
            onFail: function(response){
                Utils.alert(response.error, "Error");
            }
        });
    },
    
    /**
     * Class for exporting form to its PDF format.
     */
    exportPDF: function(formID){
        Utils.redirect('server.php', {
            parameters: {
                action:'exportPDF',
                formID: formID || this.getSelectedID()
            }
        });
    },
    /**
     * Shows or hides the trash can
     */
    toggleTrash: function(){
        this.selected && this.selected.run('mousedown');
        if($('trash-container').visible()){
            
            $('undeleteButton').hide();
            $('deleteButton').show();
            $('trashButton').removeClassName('button-over');
            $('trash-container').hide();
            $('forms').show();
        }else{
            $('undeleteButton').show();
            $('deleteButton').hide();
            $('trashButton').addClassName('button-over');
            $('trash-container').show();
            $('forms').hide();
        }
    },
    /**
     * Updates the trashcan icon for full or empty icons
     */
    updateTrashIcon: function(){
        if($('forms-trash').select('.forms').length < 1){
            $('trashcan_icon').className="toolbar-myforms-trashcan_empty";
        }else{
            $('trashcan_icon').className="toolbar-myforms-trashcan_full";
        }
    },
    
    emptyTrash: function(){
        Utils.confirm("<b>This action will permanently delete all of the forms listed in the trash can.</b><hr>This action cannot be undone, all your <u>submissions</u>, <u>reports</u> and other stuff related to these forms will be gone forever.<br><hr> Are you sure you want to proceed?".locale(), "Caution!!".locale(), function(but, value){
            if(!value){ return; }

            Utils.Request({
                parameters: {
                    action:'emptyTrash'
                },
                onSuccess: function(response){
                    $("forms-trash").update();
                    this.updateTrashIcon();
                }.bind(this),
                
                onFail: function(response){
                    Utils.alert(response.error, "Error");
                }
            });            
        }.bind(this));
    },
    
    /**
     * Delete the selected form
     */
    deleteForm: function(){
        
        /**
         * If the form is in trash then permanently delete this form
         */
        if(this.selected.parentNode.id == "forms-trash"){ 
            Utils.confirm("<b>This action will permanently delete this form.</b><hr>This action cannot be undone, all your <u>submissions</u>, <u>reports</u> and other stuff related to this form will be gone forever.<br><hr> Are you sure you want to proceed?".locale(), "Caution!!".locale(), function(but, value){
                if(!value){ return; }
    
                Utils.Request({
                    parameters: {
                        action:'deleteForm',
                        formID:this.getSelectedID()
                    },
                    
                    onComplete: function(response){
                        this.selected.remove();
                        this.updateTrashIcon();
                        Utils.updateToolbars();
                    }.bind(this),
                    
                    onFail: function(response){
                        Utils.alert(response.error, "Error");
                    }
                });            
            }.bind(this));        
        }else{
            Utils.confirm("Are you sure you want to delete this form?".locale(), "Confirm".locale(), function(but, value){
                if(!value){ return; }
    
                Utils.Request({
                    parameters: {
                        action:'markDeleted',
                        formID:this.getSelectedID()
                    },
                    onComplete: function(response){
                        $('forms-trash').insert(this.selected);
                        this.updateTrashIcon();
                        Utils.updateToolbars();
                    }.bind(this),
                    
                    onFail: function(response){
                        Utils.alert(response.error, "Error");
                    }
                });            
            }.bind(this));
        }
        
    },
    /**
     * Delete the selected form
     */
    undeleteForm: function(){
        
        Utils.Request({
            parameters: {
                action:'unDelete',
                formID:this.getSelectedID()
            },
            onSuccess: function(response){
                $('forms').insert(this.selected);
                this.updateTrashIcon();
            }.bind(this),
            
            onFail: function(response){
                Utils.alert(response.error, "Error");
            }
        });            
    },
    /**
     * Open the preview page for selected form
     */
    previewForm: function(){
        var ID = this.getSelectedID();
        if(this.forms[ID].slug){
            window.open(Utils.HTTP_URL + this.forms[ID].username + '/' + this.forms[ID].slug, 'view', 'width=750,height=' + (parseInt(this.forms[ID].height, 10) || "650") + ',status=0,location=1,toolbar=0,scrollbars=1,resizable=1');
        }else{
            window.open(Utils.HTTP_URL + "form/"+ ID + "&forceDisplay=1&prev", 'view', 'width=750,height=650,status=0,location=1,toolbar=0,scrollbars=1,resizable=1');
        }
    },
    /**
     * Get the correct rrecord from response array by given ID
     * @param {Object} id
     */
    getFormByIdFromResponse: function(id){
        return this.response.collect(function(f){ if(f.id == id){ return f; } }).compact()[0];
    },
    /**
     * Creates the form list by given arguments
     * @param {Object} forms
     * @param {Object} container
     */
    createFormList: function(forms, container){
        
        if(container == 'forms' && forms.length < 1){
            $(container).insert(new Element('div').insert(new Element('a', {
                href:'index.php?new'
            }).setStyle('text-decoration:none; color:blue;').insert('Hey, you have no forms!<br>Why not create one right now?'.locale())).setStyle({
                textAlign:'center',
                border:'1px solid #ccc',
                background:'#f5f5f5',
                position:'relative',
                top: '120px',
                width:'350px',
                margin:'0 auto', 
                padding:'10px',
                fontSize:'14px'
            }));
            return;
        }
        
        var createItem = function(form){ 
            
            if ("additional" in form) {
                return;
            }
            
            if(!('title' in form)){
                if(form.type == 'folder'){
                    
                    var folder = this.createFolder(form.name, form.collapsed);
                    this.createFormList(form.items, folder.list);
                    $(container).insert(folder.element);
                    return; // continue;
                }
                form = this.getFormByIdFromResponse(form.id.replace('form_', ''));
            }
            if(!form){return;}            
            
            form.id = form.id.replace("form_", "");
            
            this.forms[form.id] = form;
            
            
            var li = new Element('li', {id:"form_"+form.id, className:'forms'});
            var editButton = new Element('img', {className:'big-button buttons', src:'images/pencil.png', align:'right'}).setStyle('margin:-5px 2px;').hide();
            li.setReference('editButton', editButton);
            var delButton = new Element('img', {className:'big-button buttons', src:'images/blank.gif', className:'index-cross' , align:'right'}).setStyle('margin:-5px 2px;').hide();
            li.setReference('delButton', delButton);
            delButton.title = 'Delete Form'.locale();
            editButton.title = 'Edit Form'.locale();
            
            delButton.observe('mouseup', function(){
                this.deleteForm();
            }.bind(this));
            
            editButton.observe('mouseup', function(){
                this.editForm();
            }.bind(this));
            
            li.insert(delButton);
            li.insert(editButton);
            
            li.setContextMenu({
                title:form.title.shorten(20),
                onOpen: function(){
                    if(!this.selected || this.selected.id != li.id){
                        this.selectForm(li);
                    }
                }.bind(this),
                menuItems: [{
                    title:'Edit Form'.locale(),
                    name:'edit',
                    icon:'images/blank.gif',
                    iconClassName: 'context-menu-pencil',
                    handler: this.editForm.bind(this)
                },{
                    title:'Preview Form'.locale(),
                    name:'preview',
                    icon:'images/blank.gif',
                    iconClassName: 'context-menu-preview',
                    handler: this.previewForm.bind(this)
                },{
                    title:'Clone Form'.locale(),
                    name:'clone',
                    icon:'images/blank.gif',
                    iconClassName: 'context-menu-add',
                    handler: function(){
                        this.cloneForm();
                    }.bind(this)
                },{
                    title:'Export PDF'.locale(),
                    name:'export',
                    icon:'images/document-pdf.png',
                    // hidden: !document.DEBUG,
                    iconClassName: 'context-menu-add',
                    handler: function(){
                        this.exportPDF();
                    }.bind(this)
                }, '-', {
                    title: 'Delete Form'.locale(),
                    name:'delete',
                    iconClassName: "context-menu-cross_shine",
                    icon: "images/blank.gif",
                    handler: this.deleteForm.bind(this)
                }]
            });
            var title = form.title;
            if (form.count > 0) {
                title += ' <span class="scount">(' + form.count + ')</span>';
            }
            
            if(form.status == 'DISABLED'){
                title += ' <span class="scount" style="color:red">(' + 'Disabled'.locale() + ')</span>';
            }
            
            $((form.status == "DELETED")? "forms-trash" : container).insert(li.insert(title));
        }.bind(this);
        
        $A(forms).each(createItem);
        
        if(container == 'forms'){
            $A(this.response).each(function(form){
                if(this.forms[form.id] === undefined){
                    createItem(form);
                }
            }.bind(this));
        }
    },
    /**
     * Updates the page with new information
     */
    updatePage: function(){
        Utils.Request({
            parameters:{
                action:'getFormList'
            },
            onSuccess: function(res){
                this.setForms(res);
                Utils.user.usage.accountType && $('account-type').update(Utils.user.usage.accountType.prettyName.locale());
                this.setBars();
                this.initialize();
            }.bind(this)
        });
    },
    
    /**
     * Syncs the page with latest information
     */
    syncPage: function(){
        var $this = this;
        Utils.Request({
            parameters:{
                action:'updateMyForms',
                username:Utils.user.username
            },
            onSuccess: function(res){
                $H(res.forms).each(function(pair){
                    var id = pair.key;
                    if(!pair.value.split){ return; }
                    var newCount = pair.value.split(":")[0];
                    var count = pair.value.split(":")[1];
                    
                    $this.forms[id]['new'] = newCount;
                    $this.forms[id]['count'] = count;
                    if (count > 0) {
                        var scount = $('form_' + id).select('.scount')[0];
                        if (scount) {
                            scount.update('(' + count + ')');
                        }
                        else {
                            $('form_' + id).insert(new Element('span', {
                                className: 'scount'
                            }).update('(' + count + ')'));
                        }
                    }
                    else {
                        $('form_' + id).select('.scount').invoke('remove');
                    }
                    
                    var tmpSel = $this.selected;
                    if (tmpSel) {
                        $this.selectForm(tmpSel); // Unselect
                        $this.selectForm(tmpSel); // Select back
                    }
                    Utils.user.usage = res.usage;
                    $this.setBars(true);
                });
            },
            onFail: function(){
                clearInterval($this.syncInterval);
            }
        });
    },
    
    /**
     * When we recieve the form list from server this function prints them on the page with the folders and correct order
     * @param {Object} response
     */
    setForms: function(response){
        
        if(response.success){
            this.response = response.forms;
            $('forms').update();
            var config = this.response;
            if(Utils.user.folderConfig && Utils.user.folderConfig.length > 0){
                config = Utils.user.folderConfig;
            }
            
            this.createFormList(config, 'forms');
            this.createList();
            this.updateTrashIcon();
            
            // Select last edited question
            if(document.readCookie('last_form')){
                setTimeout(function(){
                    $('form_'+document.readCookie('last_form')) && $('form_'+document.readCookie('last_form')).run('mousedown');
                }, 500);
            }
        }
    },
    /**
     * Checks if the form is under folder or not
     * @param {Object} element
     */
    isUnderFolder: function(element){
        return element.parentNode.id != 'forms';
    },
    
    
    /**
     * Creates a new folder element by given name
     * @param {Object} name
     */
    createFolder: function(name, collapsed){
        collapsed = !!collapsed;
        var li = new Element('li', {className:'folder', id:'folder_'+(++this.folderCount)});
        var listContainer = new Element('div');
        var collapseButton = new Element('img', {src:'images/toggle-small-collapse.png', align:'left'}).setStyle('margin:2px');
        var folderName = new Element('span', {className:'folder-name'}).insert(name);
        var folderList = new Element('ul', {id:'folderlist_'+this.folderCount});
        folderList.insert(new Element('li', {className: 'empty_place'}).insert('&nbsp;'));
        
        li.insert(collapseButton);
        listContainer.insert(folderName).insert(folderList);
        li.insert(listContainer);
        
        var toggleCollapse = function(nosave){
            
            if(folderList.visible()){
                li.collapsed = true;
                collapseButton.src = 'images/toggle-small.png';
                folderList.hide();
            }else{
                li.collapsed = false;
                collapseButton.src = 'images/toggle-small-collapse.png';
                folderList.show();
            }
            
            if(!(nosave === true)){
                this.saveFormList();
            }
        }.bind(this);
        
        folderList.toggleFolder = toggleCollapse;
        
        if(collapsed){
            toggleCollapse(true);
        }
        
        li.setContextMenu({
            title: name.shorten(25),
            
            onOpen: function(){
                
            }.bind(this),
            
            menuItems: [{
                title:'Toggle Folder'.locale(),
                name:'toggle',
                icon:'images/blank.gif',
                iconClassName: 'context-menu-toggle',
                handler: toggleCollapse
            },{
                title:'Rename'.locale(),
                name:'rename',
                icon:'images/blank.gif',
                iconClassName: 'context-menu-pencil',
                handler: function(){
                    Utils.prompt('Enter a new name for your folder'.locale(), name, 'Rename Folder'.locale(), function(value, button, isOK){
                        if(isOK){
                            name = value;
                            folderName.innerHTML = name;
                            this.saveFormList();
                        }
                    }.bind(this));
                }.bind(this)
            }, '-', {
                title: 'Delete Folder'.locale(),
                name:'delete',
                iconClassName: "context-menu-cross_shine",
                icon: "images/blank.gif",
                handler: function(e){
                    Utils.confirm('Are you sure you want to delete this folder?<br>Note that only the folder will be deleted, forms underneath will be kept.'.locale(), 'Confirm'.locale(), function(but, value){
                        if(value){
                            folderList.immediateDescendants().each(function(elem){
                                li.insert({after:elem});
                            });
                            li.remove();
                            this.saveFormList();
                        }
                    }.bind(this));
                }.bind(this)
            }]
        });
        
        /*
        Droppables.add(listContainer, {
            hoverclass:'folder-drop',
            accept:'forms',
            onDrop: function(drag, drop){
                if(drag.parentNode.parentNode != drop){
                    drop.select('ul')[0].insert(drag);
                    this.createList();
                }
            }.bind(this)
        });
        */
        folderName.observe('mousedown', toggleCollapse);
        collapseButton.observe('mousedown', toggleCollapse);
        
        return {element: li, list: folderList};
    },
    /**
     * Deletes or adds empty list items in the lists
     */
    manageEmptyMarkers: function(){
        $$('ul').each(function(e){
            if(e.descendants().length < 1){
                e.insert(new Element('li', {className: 'empty_place'}).insert('&nbsp;'));
            }
        });
        $$('.empty_place').each(function(el){
            if(el.previousSibling || el.nextSibling){
                el.remove();
            }
        });
    },
    
    /**
     * Creates a new folder at the top of the list by prompting user the folder name
     */
    newFolder: function(){
        Utils.prompt('Enter a name for new folder:'.locale(), 'New Folder'.locale(), 'Create a New Folder'.locale(), function(value, button, isOK){
            if(isOK){
                
                var folder = this.createFolder(value);
                $('forms').insert({top: folder.element});
                
                this.createList();
                this.saveFormList();
            }
        }.bind(this));
    },
    /**
     * Make form list sortable
     */
    createList: function(){
        Sortable.create('forms', {
            constraint: false,
            tree: true,
            markDropZone: true,
            dropZoneCss: 'dropZone',
            markEmptyPlace: true,
            dropOnEmpty: true,
            onUpdate: function(){
                this.manageEmptyMarkers();
                this.saveFormList();
            }.bind(this)
        });
        this.manageEmptyMarkers();
    },
    /**
     * Save the new order of the form list in database for later use
     */
    saveFormList: function(){
        setTimeout(function(){
            var config = Object.toJSON(this.serializeFormList('forms'));
            Utils.Request({
                parameters: {
                    action:'saveFolderConfig',
                    config: config 
                },
                onFail:function(res){
                    Utils.alert(res.error);
                }
            });
        }.bind(this), 200); // Lazy ajax
    },
    /**
     * 
     * @param {Object} elem
     */
    selectFolder: function(elem){
        
        if (this.selectedFolder != elem) {
            this.selectedFolder = elem;
        }else{
            this.selectedFolder = false;
        }
    },
    
    /**
     * Form selection action
     * @param {Object} elem
     */
    selectForm: function(elem){
        if(this.selected){
            this.selected.removeClassName('selected');
            this.selected.getReference('delButton').hide();
            this.selected.getReference('editButton').hide();
        }
        if($('reportsBox')){
            $('reportsButton').removeClassName('button-over').setStyle('height:68px;');
            $('reportsBox').remove();            
        }
        if(this.selected != elem){
            this.selected = elem.addClassName('selected');
            
            if(this.selected.parentNode.id != "forms-trash"){
                this.selected.getReference('delButton').show();
                this.selected.getReference('editButton').show();                
                $$('#inot').invoke('remove');
                if(this.forms[elem.id.replace("form_", "")]['new'] > 0){
                    $('submissionButton').insert(new Element('span', { className:'inot overImage', id:'inot' }).insert(this.forms[elem.id.replace("form_", "")]['new']));
                }
                $('group-properties').show();
                $('form-properties').show();
            }else{
                $('undelete-form').show();
                $('permadelete-form').show();
            }
        }else{
            $('group-properties').hide();
            $('form-properties').hide();
            if(this.selected.parentNode.id == "forms-trash"){
                $('undelete-form').hide();
                $('permadelete-form').hide();
            }
            this.selected = false;
        }
    },
    /**
     * Demonstrates the bar chart color and animations
     */
    demo: function(){
        Utils.user.usage.submissions = 35 * Utils.user.usage.accountType.limits.submissions / 100;
        Utils.user.usage.uploads     = 55 * Utils.user.usage.accountType.limits.uploads / 100;
        Utils.user.usage.payments    = 75 * Utils.user.usage.accountType.limits.payments / 100;
        Utils.user.usage.sslSubmissions = 95 * Utils.user.usage.accountType.limits.sslSubmissions / 100;
        
        this.setBars();
    },
    /**
     * Gets the percentage of the usage and updates the bar content
     * @param {Object} type
     */
    getPercent: function(type){
        var avg = (Utils.user.usage[type] || 0) / (Utils.user.usage.accountType.limits[type] || 0) * 100;
        
        if(type == "uploads"){
            $(type+'-limit').update("%s/%s (%s%)".locale(Utils.bytesToHuman(Utils.user.usage[type]).replace('.00', ''), Utils.bytesToHuman(Utils.user.usage.accountType.limits[type]).replace('.00', ''), avg.toFixed(2)));
        }else{
            $(type+'-limit').update("%s/%s (%s%)".locale(Utils.user.usage[type], Utils.user.usage.accountType.limits[type], (avg.toFixed(2).toString().replace(".00", ""))));
        }
        return avg >= 100? 100 : avg;
    },
    /**
     * Will serialize form list then save it to database
     * Then we will re-create the list based on this object
     * @param {Object} list
     */
    serializeFormList: function(list){
        var formList = [];
        $(list).immediateDescendants().each(function(element){
            if(element.hasClassName('forms')){
                formList.push({
                    type:'form',
                    id:element.id
                });
            }
            
            if (element.hasClassName('folder')) {
                formList.push({
                    type:"folder",
                    collapsed: !!element.collapsed,
                    name:element.select('.folder-name')[0].innerHTML,
                    items: this.serializeFormList(element.select('ul')[0])
                });
            }
            
        }.bind(this));
        
        return formList;
    },
    /**
     * Create a new form
     * TODO: New form wizard 
     */
    newForm: function (){
        Utils.loadScript("js/builder/newform_wizard.js", function(){ openNewFormWizard(); });
    },
    /**
     * Key actions for this page
     */
    keys: {
        Up: { // Select previous question
            handler: function(e){
                Event.stop(e);
                
                if(MyForms.selected){
                    var sibling =  $(MyForms.selected.previousSibling);
                    if(sibling){
                        if(sibling.hasClassName('folder')){
                            
                        }else{
                            sibling.run('mousedown');
                        }
                    }
                    return false;
                }else{
                    $($('forms').lastChild).run('mousedown');
                }
            },
            disableOnInputs:true
        },
        Down: { // Select next question
            handler:function(e){
                Event.stop(e);
                if(MyForms.selected){
                    if(MyForms.selected.nextSibling){
                        $(MyForms.selected.nextSibling).run('mousedown');
                    }
                    return false;
                }else{
                    $($('forms').firstChild).run('mousedown');
                }
            },
            disableOnInputs:true
        },
        Enter: {
            handler: function(){
                if(MyForms.selected){
                    MyForms.editForm();
                    return false;
                }
            },
            disableOnInputs: true
        },
        Delete:{ // Delete selected question
            handler: function(){
                if(MyForms.selected){
                    MyForms.deleteForm();
                    return false;
                }
            },
            disableOnInputs: true
        },
        Backspace:{
            handler:function(e){
                Event.stop(e);
                return false;
            },
            disableOnInputs: true
        }
    }
};

var Utils = Utils || new Common();
document.ready(function(){MyForms.initialize();});
document.keyboardMap(MyForms.keys);
