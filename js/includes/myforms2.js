var MyForms = {
    selected: false, // Selected form
    selectedFolder: false, // Selected Folder
    folderCount: 1000, // Folder ID start
    folderColors: ['#68ACDF', '#888888', '#E66D17', '#A3CC29', '#862DB3 ', '#E6C62E', '#1F4799', '#CD2822', '#2E992E', '#E65CB7', '#938137', '#0F8299', '#6e451a', '#8BB5CF', '#CF928B', '#962F7E', '#8BCF91', '#CF8BCA', '#527052', '#720c0c'],
    response: false, // Form list from server response
    forms: {}, // Corrected form lists
    syncInterval: false,
    currentPanel: 'tab-main',
    defaultSort: false,
    filterType: 'main',
    oldFilter: false,
    lastSearch: '',
    stopSync: false,
    /**
     * Initialize the MyForms page
     */
    initialize: function(){
        //try {
        var $this = this;
        
        Protoplus.Profiler.start('MyForms');
        $('forms').cleanWhitespace();
        
        $('myforms-sortby').bigSelect();
        $('myforms-sortby').observe('change', function(){
            $this.sortBy($('myforms-sortby').value);
        });
        $('myforms-searchbox').makeSearchBox({
            defaultText: 'Search'.locale()
        });
        
        if (this.lastSearch) {
            $('myforms-searchbox').focus();
            $('myforms-searchbox').value = this.lastSearch;
            this.searchForms($('myforms-searchbox').value);
        }
        
        $('group-myforms', 'group-properties').invoke('cleanWhitespace');
        Utils.setToolbarFloat(); // ToolbAR
        Utils.setToolboxFloat(); // ToolbOX
        this.setSearchForm();
        
        if (location.hash.match('#reports-')) {
            setTimeout(function(){
                var reportFormID = location.hash.replace('#reports-', '');
                if ($('form_' + reportFormID)) {
                    if ($('form_' + reportFormID) != $this.selected) {
                        $this.selectForm($('form_' + reportFormID));
                    }
                    $('reportsButton').click();
                }
            }, 800);
        }
        
        if ($('upgradeNow')) {
            $('upgradeNow').observe('click', function(){
                location.href = 'pricing/';
            });
        }
        
        $$('#search-bar .big-button').each(function(e){
            e.setUnselectable('');
            e.observe('click', function(){
                $this.currentPanel = e.id;
            });
        });
        
        setTimeout(this.setBars.bind(this), 200);
        
        // Set Events to stop sync'in when window does not have focus
        Element.observe(window, 'blur', function(){
            $this.stopSync = (new Date()).getTime();
        });
        
        // Restart sync when browser get the focus back. And make a quick sync if browser was inactive more than 30 seconds
        Element.observe(window, 'focus', function(){
            var t = $this.stopSync;
            $this.stopSync = false;
            if(((new Date()).getTime() - t) > 30000){
                $this.syncPage();
            }
        });
        
        // Check the page every minute
        this.syncInterval = setInterval(this.syncPage.bind(this), 30000);
        
        $('stage').setContextMenu({
            title: 'MyForms',
            menuItems: [{
                title: 'Expand All'.locale(),
                name: 'expand',
                icon: 'images/toggle.png',
                handler: function(){
                    $$('.folder ul').invoke('openFolder');
                }
            }, {
                title: 'Collapse All'.locale(),
                name: 'collapse',
                icon: 'images/toggle-collapse.png',
                handler: function(){
                    $$('.folder ul').invoke('closeFolder');
                }
            }]
        });
        
        Protoplus.Profiler.end('MyForms');
        twitterIntent();
        /*
        if (!$('jotform-feedback-3140758192')) {
            // feedback button
            new JotformFeedback({
                formId: "3140758192",
                buttonText: '<b>The New My Forms</b><br /><span style=\'font-size:12px; line-height:15px\'>Give us feedback, tell us what you think about the new My Forms.</span><img style="position:absolute; top:14px; right:14px; width:10px; padding:3px;" src="images/arrow_down_white.png" id="cls-button" />',
                base: "http://www.jotform.com/",
                buttonSide: "bottom",
                buttonAlign: "right",
                width: Prototype.Browser.IE ? 400 : 370,
                height: Prototype.Browser.IE ? 230 : 210,
                inlineStyle: 'bottom:0px;', // Firefox fix
                windowTitle: "<span style='font-size:14px;'>The New My Forms Feedback</span>",
                iframeParameters: $H({
                    accountType: Utils.user.accountType,
                    username: Utils.user.username,
                    email: Utils.user.email
                })
            });
            
            if (document.readCookie('feedback') == 'closed') {
                $('jotform-feedback-3140758192').setStyle('bottom:-42px;').__closed = true;
                $('cls-button').src = 'images/arrow_up_white.png';
            }
            
            $('cls-button').observe('click', function(e){
                Event.stop(e);
                MyForms.toggleFeedBack();
            });
        }*/
        
        /* } catch (e) {
         console.error(e);
        }*/
        if($('greetings')){
            var b = $('greetings').getStyle('bottom');
            $(document.body).insert($('greetings')); 
            $('greetings').mouseEnter(function(e){
                e.shift({bottom:0, duration:0.5});
            }, function(e){
                e.shift({bottom:b, duration:0.5});
            });
        }
    },
    toggleFeedBack: function(e){
        if ($('jotform-feedback-3140758192').__closed) {
            $('jotform-feedback-3140758192').shift({
                bottom: '0px',
                duration: 0.1
            }).__closed = false;
            $('cls-button').src = 'images/arrow_down_white.png';
            document.createCookie('feedback', 'open');
        } else {
            $('jotform-feedback-3140758192').shift({
                bottom: '-42px',
                duration: 0.1
            }).__closed = true;
            $('cls-button').src = 'images/arrow_up_white.png';
            document.createCookie('feedback', 'closed');
        }
    },
    /**
     * Set the account status bars and give colors by percentage
     */
    setBars: function(noAnimation){
    
        var colors = {
            "40": '#3DCF3C',
            "60": '#FFED00',
            "80": '#FFA900',
            "100": '#FF3B00'
        };
        
        var borderColors = {
            "40": '#028C00',
            "60": '#AA9E00',
            "80": '#C18000',
            "100": '#992400'
        };
        
        method = (noAnimation === true) ? "setStyle" : "shift";
        
        var getColor = function(num){
            for (var i in colors) {
                if (num <= i && num > 0 ) { return colors[i]; }
            }
            return "transparent";
        };

        var getBorderColor = function(num){
            for (var i in colors) {
                if (num <= i && num > 0 ) { return borderColors[i]; }
            }
            return "transparent";
        };

        function onEnd(el, options){
            if(el.getWidth() < 4){
                el.setStyle({
                    background: 'transparent',
                    borderColor: 'transparent'
                });
            } 
        }
        var easing = "swingTo", duration = 1.25;
        
        $('bar-submissions')[method]({
            width: this.getPercent('submissions') + "%",
            backgroundColor: getColor(this.getPercent('submissions')),
            borderColor: getBorderColor(this.getPercent('submissions')),
            transparentColor:'#028C00',
            easing: easing,
            duration: duration,
            onEnd: onEnd
        });
        $('bar-uploads')[method]({
            width: this.getPercent('uploads') + "%",
            backgroundColor: getColor(this.getPercent('uploads')),
            borderColor: getBorderColor(this.getPercent('uploads')),
            transparentColor:'#028C00',
            easing: easing,
            duration: duration,
            onEnd: onEnd
        });
        $('bar-payments')[method]({
            width: this.getPercent('payments') + "%",
            backgroundColor: getColor(this.getPercent('payments')),
            borderColor: getBorderColor(this.getPercent('payments')),
            transparentColor:'#028C00',
            easing: easing,
            duration: duration,
            onEnd: onEnd
        });
        $('bar-ssl')[method]({
            width: this.getPercent('sslSubmissions') + "%",
            backgroundColor: getColor(this.getPercent('sslSubmissions')),
            borderColor: getBorderColor(this.getPercent('sslSubmissions')),
            transparentColor:'#028C00',
            easing: easing,
            duration: duration,
            onEnd: onEnd
        });

    },
    /**
     * Initiate the search box
     */
    setSearchForm: function(){
        var $this = this;
        $('myforms-searchbox').observe('keyup', function(){
            $this.searchForms($('myforms-searchbox').value);
        });
    },
    /**
     * Return all forms to default sort
     */
    toDefaultSort: function(){
        this.sorted = false;
        $('myforms-sortby').selectOption('default');
        $('forms').removeClassName('exploded');
        var def = this.defaultSort;
        if (Object.isString(this.defaultSort)) {
            def = this.defaultSort.evalJSON();
        }
        this.setForms({ folderConfig: def });
    },
    /**
     * Sorts the forms by given function
     * @param {Object} by
     * @param {Object} reverse
     */
    sort: function(by, reverse){
        this.sorted = true;
        var $this = this;
        // Never sort forms in main tab
        if (this.filterType == 'main') {
            this.saveFormList();
            this.filter('all');
        }
        // Get the serialized list of forms
        var serialized = this.serializeFormList('forms');
        
        // Sort the list by given function
        var sorted = $A(serialized).sortBy(function(item){
            if (item.type == 'folder') { return "-1"; } // Skip folders
            // give complete form configuration as a sort parameter
            return by($this.forms[item.id.replace('form_', '')]);
        });
        // Create a temp list to put forms before sort
        var temp = $(document.createElement('ul')).hide();
        document.body.appendChild(temp);
        // Ascending / Descending order
        if (reverse) {
            sorted = sorted.reverse();
        }
        // Move all forms to tmp list
        $$('#forms .forms').each(function(form){
            temp.appendChild(form);
        });
        // Get forms back from temp list in sorted order
        $A(sorted).each(function(item){
            if (item.type == 'folder') { return "0"; } // Again skip folders
            $('forms').appendChild($(item.id));
        });
    },
    /**
     * Convert MySQL Date string to JavaScript Date for sorting
     * @param {Object} str
     */
    strToDate: function(str){
        var result = str.match(/(\d+)\-(\d+)\-(\d+)\s(\d+)\:(\d+)\:(\d+)/);
        if (!result) { return false; }
        return new Date(result[1], result[2], result[3], result[4], result[5], result[6]);
    },
    /**
     * Predefined sort types
     * @param {Object} by
     */
    sortBy: function(by){
        switch (by) {
            case "a-z":
                this.sort(function(form){
                    return form.title.toLowerCase();
                });
            break;
            case "z-a":
                this.sort(function(form){
                    return form.title.toLowerCase();
                }, true);
            break;
            case "date":
                this.sort(function(form){
                    return MyForms.strToDate(form.created_at);
                }, true);
            break;
            case "submission-count":
                this.sort(function(form){
                    return parseInt(form.count, 10);
                }, true);
            break;
            case "submission-date":
                this.sort(function(form){
                    return MyForms.strToDate(form.updated_at);
                }, true);
            break;
            case "last-edit":
                this.sort(function(form){
                    return MyForms.strToDate(form.created_at);
                }, true);
            break;
        }
        // Save last sort here
        this.saveFormList(true, false, by);
    },
    /**
     * Returns the currently visible forms
     */
    getVisibleForms: function(){
        var forms = $$('.forms');
        if (forms.length === 0) { return false; }
        return forms.collect(function(e){ return e.visible() ? e : null; }).compact();
    },
    
    /**
     * Apply a filter on forms
     * @param {Object} type
     * @param {Object} value
     */
    filter: function(type, value, nosave){
        this.toTop();
        $$('.forms').invoke('show');
        $$('.warning').invoke('remove');
        ($('filter-notification') && $('filter-notification').remove());
        $('forms').removeClassName('folder-view');
        
        if (this.filterType == 'trash') {
            this.closeTrash();
        }
        var search = false;
        if (value == '__fromSearch') {
            search = true;
            value = '';
        }
        
        // Clean search box
        if (!search) {
            $('myforms-searchbox').value = '';
            $('myforms-searchbox').run('blur');
            document.eraseCookie('last-search');
        }
        
        $$('.pressed').invoke('removeClassName', 'pressed');
        
        $('tab-' + type) && $('tab-' + type).addClassName('pressed');
        
        $('newFolderButton').disable();
        this.filterType = type;
        switch (type) {
            case "all":
                this.explodeFolders();
            break;
            case "main":
                $('newFolderButton').enable();
                if (this.sorted) {
                    this.toDefaultSort();
                } else {
                    this.implodeFolders();
                }
            break;
            case "listfolder":
                this.explodeFolders();
                
                $('search-bar').insert(new Element('div', {
                    id: 'filter-notification'
                }));
                $('filter-notification').update("<span>" + "Displaying Folder:".locale() + " <b>" + $(value).select('.folder-name')[0].innerHTML + '</b></span><img onclick="MyForms.filter(\'all\');" src="images/cross3.png" align="right" />')
                
                $('forms').addClassName('folder-view');
                $$('.forms').each(function(f){
                    if (f.readAttribute('folder-id') == value) {
                        f.show();
                    } else {
                        f.hide();
                    }
                });
            break;
            case "unread":
                this.explodeFolders();
                $$('.forms').each(function(f){
                    if (f.select('.new')[0].visible()) {
                        f.show();
                    } else {
                        f.hide();
                    }
                });
            break;
            case "favs":
                this.explodeFolders();
                $$('.forms').invoke('hide');
                $$('.fav').invoke('show');
            break;
            case "trash":
                this.openTrash();
            break;
        }
        var res = this.getVisibleForms();
        if (res !== false && res.length < 1) {
            if (!$('filter-warning')) {
                $('stage').insert(new Element('div', {
                    id: 'filter-warning',
                    className: 'warning'
                }).update('No items to display'));
            }
        }
        
        if (nosave !== true) {
            this.saveFormList(true, value);
        }
    },
    /**
     * Move scrool to top
     */
    toTop: function(){
        $(document.body).shift({
            scrollTop: 0,
            duration: 0.5
        });
    },
    /**
     * Returns the very outer parent and names of the all other parents
     * @param {Object} element
     */
    getParentFolder: function(element, parents){
        parents = parents || [];
        var folder = this.getFolder(element);
        
        if (folder === false) {
            if (element.hasClassName('folder')) { return {
                folder: element,
                parents: parents
            }; }
            return false;
        }
        
        parents.push({
            id: folder.id,
            name: folder.select('.folder-name')[0].innerHTML
        });
        var res = this.getParentFolder(folder, parents);
        if (res === false) { return false; }
        
        return {
            folder: res.folder,
            parents: res.parents
        };
    },
    
    /**
     * Get parent folder if found. Otherwise return false
     * @param {Object} element
     */
    getFolder: function(element){
        if (element.parentNode.id == 'forms' || element.parentNode.id == 'trash') { return false; }
        
        return element.up('.folder');
    },
    
    /**
     * Explodes folders extracts all it's forms and tags the with foldername and ID
     */
    explodeFolders: function(){
        var $this = this;
        if ($('forms').hasClassName('exploded')) { return; }
        $('forms').addClassName('exploded');
        
        $$('.folder').each(function(folder){
            var folder_name = folder.select('.folder-name')[0].innerHTML.strip();
            var folderTags = [];
            
            $$('#' + folder.id.replace('folder_', 'folderlist_') + ' > .forms').each(function(form){
                if ((outfolder = $this.getParentFolder(folder))) {
                    outfolder.folder.insert({
                        before: form
                    });
                    folderTags = $A(outfolder.parents).reverse();
                } else {
                    folder.insert({
                        before: form
                    });
                }
                
                folderTags.push({
                    id: folder.id,
                    name: folder_name
                });
                
                $A(folderTags).each(function(tag){
                    var index = parseFloat(tag.id.replace('folder_', '')) - 1000;
                    var color;
                    if ($this.folderColors[index]) {
                        color = $this.folderColors[index];
                    } else {
                        color = $this.folderColors[0];
                    }
                    form.select('.details')[0].insert(' <span class="folder-tag" onclick="MyForms.filter(\'listfolder\', \'' + tag.id +
                    '\');" style="background:' +
                    color +
                    '" title="Go To: ' +
                    tag.name +
                    '">' +
                    tag.name.shorten(15) +
                    '</span>');
                });
                form.writeAttribute('folder-id', folder.id);
                form.addClassName('infolder');
            });
            folder.hide();
        });
    },
    /**
     * Implodes all extracted forms back in to folders
     */
    implodeFolders: function(){
    
        $('forms').removeClassName('exploded');
        $$('.folder').invoke('show');
        $$('.folder-tag').invoke('remove');
        $$('.infolder').each(function(form){
            var folderID = form.readAttribute('folder-id').replace('folder_', 'folderlist_');
            form.writeAttribute('folder-id', '');
            form.removeClassName('infolder');
            $(folderID).insert(form);
        });
    },
    /**
     * Make a search in the forms
     * @param {Object} keyword
     */
    searchForms: function(keyword){
        this.toTop();
        document.createCookie('last-search', keyword);
        if (!$('filter-notification')) {
            $('search-bar').insert(new Element('div', {
                id: 'filter-notification'
            }));
            $('forms').addClassName('folder-view');
        }
        
        $('filter-notification').update("<span>" + "Searching forms for:".locale() + " <b>" + keyword + '</b></span><img onclick="MyForms.filter(\'all\');" src="images/cross3.png" align="right" />')
        
        // If there is a warning remove it
        $$('.warning').invoke('remove');
        
        this.oldFilter = this.filterType;
        if (this.filterType == 'main') {
            this.filter('all', '__fromSearch');
        }
        
        this.explodeFolders();
        if (!keyword) {
            this.filter(this.oldFilter, '__fromSearch');
            return;
        }
        var c = 0;
        $$('.forms').each(function(f){
            if (!f.innerHTML.stripTags().toLowerCase().include(keyword.toLowerCase()) && !f.id.toLowerCase().include(keyword.toLowerCase())) {
                f.hide();
            } else {
                c++;
                f.show();
            }
        });
        
        if (c == 0) {
            if (!$('search-warning')) {
                $('stage').insert(new Element('div', {
                    id: 'search-warning',
                    className: 'warning'
                }).update('No forms found for: "' + keyword + '"'));
            }
        }
    },
    /**
     * Returns the currently selected forms ID
     */
    getSelectedID: function(){
        return this.selected.id.replace("form_", "");
    },
    /**
     * returns the all configurations of currently selected form
     */
    getSelectedForm: function(){
        return this.forms[this.getSelectedID()];
    },
    /**
     * Open the submissions page for selected form
     */
    openSubmissions: function(){
        if(Utils.checkSubmissionsEnabled()){
            Utils.redirect("submissions/" + this.getSelectedID());
        }
    },
    
    /**
     * Previews the selected Report
     */
    previewReport: function(index){
        var formID = this.getSelectedID();
        var report = this.forms[formID].reports[index];
        
        var url = Utils.HTTP_URL +  (report.type == 'cal'? 'calendar' : (report.type == 'visual'? 'report' : report.type)) + "/" + report.id + "/";
        Utils.redirect(url, {
            target:'_blank'
        });
    },
    
    /**
     * Deletes a given report 
     */
    deleteReport: function(index){
        var formID = this.getSelectedID();
        var report = this.forms[formID].reports[index];
        Utils.Request({
            parameters: {
                action: 'deleteReport',
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
        if (!Utils.checkSubmissionsEnabled()) {
            return;
        }
        // Hide reports list if visible on the page
        if ($('reportsBox')) {
            $('reportsButton').removeClassName('button-over')
            $('reportsBox').remove();
            return;
        }
        // Create and show the reports list if it was newer created
        $('reportsButton').addClassName('button-over')
        var box = new Element('div', {
            className: 'edit-box',
            id: 'reportsBox'
        });
        var off = $('reportsButton').cumulativeOffset();
        var dim = $('reportsButton').getDimensions();
        
        box.setStyle({
            position: 'absolute',
            top: (off.top + dim.height) + 'px',
            left: (off.left) + 'px',
            width: '200px',
            zIndex: 1
        });
        var list = new Element('div').setStyle('background:#FFFFFF; border:1px solid #AAAAAA; list-style:none outside none; margin:5px 0;');
        
        var addNew = new Element('button', {
            className: 'big-button buttons'
        }).setStyle('float:right; font-size:14px;').insert('<img align="top" src="images/add.png" /> ' + 'Add New Report'.locale());
        
        box.insert('<b style="font-size: 14px; color: #333;">' + 'Report List'.locale() + '</b>');
        box.insert(list);
        box.insert(addNew);
        var formID = this.getSelectedID();
        $(document.body).insert(box);
        
        box.positionFixed({
            offset: 69
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
            
            if (reports.length < 1) {
            
                list.update('<div style="padding:5px;">' + 'You have no reports created for this form.'.locale() + '</div>');
                
            } else {
                $A(reports).each(function(report, i){
                    var reportLi = new Element('li', {className: 'report-list-item'});
                    
                    reportLi.insert('<img src="images/myforms/' + report.type + '.png" align="absmiddle" /> ' + report.title + '<img onclick="MyForms.previewReport(' + i + ')" class="report-list-item-preview" align="right" src="images/myforms/new/form-preview.png" />' + '<img onclick="MyForms.deleteReport(' + i + ')" class="report-list-item-delete index-cross" align="right" src="images/blank.gif" />');
                    
                    reportLi.observe('click', function(e){
                        
                        if ($(e.target) && $(e.target).hasClassName('report-list-item-delete')) { return; }
                        if ($(e.target) && $(e.target).hasClassName('report-list-item-preview')) { return; }
                        
                        if (report.type == "visual") {
                            Utils.redirect('page.php', {
                                parameters: {
                                    p: 'reports',
                                    formID: formID,
                                    reportID: report.id
                                }
                            });
                        } else {
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
            parameters: {
                formID: this.getSelectedID()
            }
        });
    },
    
    getReports: function(id, callback){
        var $this = this;
        if ($this.forms[id].reports) {
            callback($this.forms[id].reports);
        } else {
            Utils.Request({
                parameters: {
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
                action: 'cloneForm',
                formID: formID || this.getSelectedID()
            },
            onSuccess: function(response){
                Utils.redirect(Utils.HTTP_URL, {
                    parameters: {
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
        var url = Utils.HTTP_URL+'pdf/'+(formID || this.getSelectedID());
        Utils.redirect(url);
    },
    /**
     * Open Trash folder
     */
    openTrash: function(){
        this.selected && this.selected.run('mousedown');
        //$('undeleteButton').show();
        // $('deleteButton').hide();
        $('trashButton').addClassName('button-over');
        $('trash-container').show();
        $('forms').hide();
    },
    /**
     * Close Trash Folder
     */
    closeTrash: function(){
        this.selected && this.selected.run('mousedown');
        //$('undeleteButton').hide();
        //$('deleteButton').show();
        $('trashButton').removeClassName('button-over');
        $('trash-container').hide();
        $('forms').show();
    },
    
    /**
     * Shows or hides the trash can
     */
    toggleTrash: function(){
        if ($('trash-container').visible()) {
            this.closeTrash();
        } else {
            this.openTrash();
        }
    },
    /**
     * Updates the trashcan icon for full or empty icons
     */
    updateTrashIcon: function(){
        if ($('forms-trash').select('.forms').length < 1) {
            $('trashcan_icon').className = "toolbar-myforms-trashcan_empty";
        } else {
            $('trashcan_icon').className = "toolbar-myforms-trashcan_full";
        }
    },
    
    emptyTrash: function(){
        Utils.confirm("<b>This action will permanently delete all of the forms listed in the trash can.</b><hr>This action cannot be undone, all your <u>submissions</u>, <u>reports</u> and other stuff related to these forms will be gone forever.<br><hr> Are you sure you want to proceed?".locale(), "Caution!!".locale(), function(but, value){
            if (!value) { return; }
            
            Utils.Request({
                parameters: {
                    action: 'emptyTrash'
                },
                onSuccess: function(response){
                    $("forms-trash").update();
                    this.updateTrashIcon();
                    this.syncPage();
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
        if (this.selected.parentNode.id == "forms-trash") {
            Utils.confirm("<b>This action will permanently delete this form.</b><hr>This action cannot be undone, all your <u>submissions</u>, <u>reports</u> and other stuff related to this form will be gone forever.<br><hr> Are you sure you want to proceed?".locale(), "Caution!!".locale(), function(but, value){
                if (!value) { return; }
                
                Utils.Request({
                    parameters: {
                        action: 'deleteForm',
                        formID: this.getSelectedID()
                    },
                    
                    onComplete: function(response){
                        this.selected.remove();
                        this.updateTrashIcon();
                        Utils.updateToolbars();
                        this.syncPage();
                    }.bind(this),
                    
                    onFail: function(response){
                        Utils.alert(response.error, "Error");
                    }
                });
            }.bind(this));
        } else {
            Utils.confirm("Are you sure you want to delete this form?".locale(), "Confirm".locale(), function(but, value){
                if (!value) { return; }
                
                Utils.Request({
                    parameters: {
                        action: 'markDeleted',
                        formID: this.getSelectedID()
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
                action: 'unDelete',
                formID: this.getSelectedID()
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
        if (this.forms[ID].slug) {
            window.open(Utils.HTTP_URL + this.forms[ID].username + '/' + this.forms[ID].slug, 'view', 'width=750,height=' + (parseInt(this.forms[ID].height, 10) || "650") + ',status=0,location=1,toolbar=0,scrollbars=1,resizable=1');
        } else {
            window.open(Utils.HTTP_URL + "form/" + ID + "&forceDisplay=1&prev", 'view', 'width=750,height=650,status=0,location=1,toolbar=0,scrollbars=1,resizable=1');
        }
    },
    /**
     * Get the correct rrecord from response array by given ID
     * @param {Object} id
     */
    getFormByIdFromResponse: function(id){
        var f;
        for (var x in this.response) {
            f = this.response[x];
            if (f.id == id) { return f; // found
 }
        }
        return {}; // not found
    },
    
    getFolderItemList: function(li){
        var conf = {};
        var $this = this;
        
        var putInPlace = function(arr, fid, id, item){
            for(var i in arr){
                if(i == fid){
                    if(!arr[i].items){
                        arr[i].items = {};
                    }
                    arr[i].items[id] = item;
                    return true;
                }else if(arr[i].items){
                    if(putInPlace(arr[i].items, fid, id, item) === true){
                        return true;
                    }
                }
            }
        }
        var folders = $$('.folder');
		
        folders.each(function(f){
            
            if($this.isUnderFolder(f)){
                var fid = $this.getFolder(f).id;
                putInPlace(conf, fid, f.id, {
                    title:f.select('.folder-name')[0].innerHTML,
                    icon:'images/myforms/new/folder-closed.png',
                    handler: function(){
                        $("folderlist_"+(f.id.replace('folder_', ''))).insert(li);
                        $this.manageEmptyMarkers();
                        $this.saveFormList(true);
                    }
                })
            }else{
                conf[f.id] = {
                    title:f.select('.folder-name')[0].innerHTML,
                    icon:'images/myforms/new/folder-closed.png',
                    handler: function(){
                        $("folderlist_"+(f.id.replace('folder_', ''))).insert(li);
                        $this.manageEmptyMarkers();
                        $this.saveFormList(true);
                    }
                }
            }
        });
        
        var flatten = function(arr){
            arr = $H(arr).values();
            for(var i=0; i < arr.length; i++){
                if(arr[i].items){
                    arr[i].items = flatten(arr[i].items);
                }
            }
            return arr;
        }
        
        conf = flatten(conf);
        if(folders.length > 0){
			conf.push("-");
		}
		// Provide user an option to create a new folder
		conf.push({
			title:'Create a New Folder'.locale(),
			icon:'images/add.png',
			handler: function(){
				$this.newFolder(li);
			}
		});
        return conf;                            
    },
    
    /**
     * Creates the form list by given arguments
     * @param {Object} forms
     * @param {Object} container
     */
    createFormList: function(forms, container){
        var $this = this;
        if (container == 'forms' && forms.length < 1) {
            $(container).insert(new Element('div', {
                className: 'noforms'
            }).insert(new Element('a', {
                href: 'index.php?new'
            }).insert('<img src="images/myforms/new/menu-new-form.png" border="0" align="left" style="margin:8px;" /> ' + 'Hey, you have no forms!<br>Why not create one right now?'.locale())));
            return;
        }
        
        /**
         * Create an individual form
         * @param {Object} form
         */
        var createItem = function(form){
            if (form === undefined) { return; }
            var fav = form.fav;
            // additional config. ignore
            if ("additional" in form) { return; }
            
            if (form.type == 'folder') {
                var folder = $this.createFolder(form.name, form.collapsed);
                $this.createFormList(form.items, folder.list);
                $(container).appendChild(folder.element);
                return; // continue;
            }
            var tmp_id = form.id;
            // Always get the form details from database result
            form = $this.getFormByIdFromResponse(form.id.replace('form_', ''));
            
            // get form ID
            if (!("id" in form)) {
                console.log("Form not found.", Object.toJSON(form));
                // Delete Miss configured form from list
                for (var x in $this.defaultSort) {
                    if ($this.defaultSort[x].id == tmp_id) {
                        delete $this.defaultSort[x];
                    }
                }
                return;
            }
            
            form.id = form.id.replace("form_", "");
            // Add form to list of forms
            $this.forms[form.id] = form;
            
            var li = $(document.createElement('li'));
            li.id = "form_" + form.id;
            li.className = 'forms';
            
            var prevButton = '<span class="selected-links" onclick="MyForms.previewForm();" style="display:none;">';
            prevButton += '<img src="images/myforms/new/form-preview.png" align="absmiddle" />';
            prevButton += 'Preview'.locale();
            prevButton += '</span>';
            
            var editButton = '<span class="selected-links" onclick="MyForms.editForm();" style="display:none;">';
            editButton += '<img src="images/myforms/new/form-edit.png" align="absmiddle" />';
            editButton += 'Edit'.locale();
            editButton += '</span>';
            
            var delButton = '<span class="selected-links" onclick="MyForms.deleteForm();" style="display:none;">';
            delButton += '<img src="images/myforms/new/form-delete.png" align="absmiddle" />';
            delButton += 'Delete'.locale();
            delButton += '</span>';
            
            li.innerHTML += (delButton + prevButton + editButton);
            
            setTimeout(function(){ // It's not urgent do this later
                Protoplus.ui.setContextMenu(li, {
                    title: form.title.shorten(20),
                    onStart: function(){
                        if (form['new'] > 0) {
                            li.enableButton('allread');
                        } else {
                            li.disableButton('allread');
                        }
                        
                        if (form['count'] > 0) {
                            li.enableButton('alldelete');
                        } else {
                            li.disableButton('alldelete');
                        }
                        
                        if($this.filterType == 'main'){
                            li.showButton('moveit');
                        }else{
                            li.hideButton('moveit');
                        }
                    },
                    onOpen: function(){
                        if (!$this.selected || $this.selected.id != li.id) {
                            $this.selectForm(li);
                        }
                    },
                    menuItems: [{
                        title: 'Mark All as Read'.locale(),
                        name: 'allread',
                        icon: 'images/myforms/new/toend.png',
                        disabled: true,
                        handler: function(){
                            Utils.Request({
                                parameters: {
                                    action: 'markAllRead',
                                    formID: li.id.replace('form_', '')
                                },
                                onSuccess: function(res){
                                    form['new'] = 0;
                                    $this.forms[form.id]['new'] = 0;
                                    li.select('.new').invoke('hide');
                                    $('inot').remove();
                                },
                                onFail: function(res){
                                    Utils.alert(res.error, 'Error');
                                }
                            });
                        }
                    }, {
                        title:'Clear All Submissions'.locale(),
                        name:'alldelete',
                        icon:'images/delete.png',
                        disabled:true,
                        handler:function(){
                            Utils.prompt('<img align="left" src="images/warning.png" style="margin:10px;"><div style="padding:16px 0 0;"><h3 style="font-size: 13px;margin: 0;padding: 0;">'+
                                'You are about to delete all submissions.'.locale()+
                                '</h3>'+
                                'Please enter your password to proceed'.locale()+
                                '</div>', "", "Delete All Submissions".locale(), function(value, button, clicked){
                                if(clicked){
                                    Utils.Request({
                                        parameters:{
                                            password:value,
                                            action:'deleteAllSubmissions',
                                            formID:li.id.replace('form_', '')
                                        },
                                        onSuccess: function(){
                                            form['new'] = 0;
                                            $this.forms[form.id]['new'] = 0;
                                            li.select('.new').invoke('hide');
                                            li.select('.s-count').invoke('update', '0');
                                            $('inot').remove();
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
                                okText:'Delete All Submissions'.locale()
                            });
                        }
                    },{
                        title: 'Edit Form'.locale(),
                        name: 'edit',
                        icon: 'images/blank.gif',
                        iconClassName: 'context-menu-pencil',
                        handler: function(){
                            $this.editForm();
                        }
                    }, {
                        title: 'Preview Form'.locale(),
                        name: 'preview',
                        icon: 'images/blank.gif',
                        iconClassName: 'context-menu-preview',
                        handler: function(){
                            $this.previewForm();
                        }
                    }, {
                        title: 'Clone Form'.locale(),
                        name: 'clone',
                        icon: 'images/blank.gif',
                        iconClassName: 'context-menu-add',
                        handler: function(){
                            $this.cloneForm();
                        }
                    }, {
                        title: 'Create PDF Form'.locale(),
                        name: 'export',
                        icon: 'images/document-pdf.png',
                        // hidden: !document.DEBUG,
                        iconClassName: 'context-menu-add',
                        handler: function(){
                            $this.exportPDF();
                        }
                    }, {
                        title:'Move To a Folder'.locale(),
                        name:'moveit',
                        icon:'images/myforms/new/folder-opened.png',
                        // iconClassName:'myforms-folder',
                        items:function(){
                            return $this.getFolderItemList(li);
                        }
                    }, '-', {
                        title: 'Delete Form'.locale(),
                        name: 'delete',
                        iconClassName: "context-menu-cross_shine",
                        icon: "images/blank.gif",
                        handler: function(){
                            $this.deleteForm();
                        }
                    }]
                });
            }, 300);
            
            
            var src = 'images/myforms/new/star-off.png';
            if (fav === true) {
                src = 'images/myforms/new/star-on.png';
                li.addClassName('fav');
            }
            
            var title = '<div class="star"><img id="' + form.id + '-star" src="' + src + '" align="absmiddle" /></div> ';
            
            title += '<div class="form-title" title="' + form.title + '">' + form.title + "</div>";
            title += ' <span class="details"><b class="s-count">' + form.count + '</b> ' + 'Submissions'.locale() + '.';
            if (form['new'] > 0) {
                title += ' <span class="new">' + form['new'] + ' ' + 'New'.locale() + '!</span> ';
            } else {
                title += ' <span class="new" style="display:none"></span>';
            }
            title += ' Created on ' + form.created_date;
            if (form.status == 'DISABLED') {
                title += ' <span style="color:red">(' + 'Disabled'.locale() + ')</span>';
                li.setOpacity(0.5);
            }
            title += '</span>';
            
            li.innerHTML += title;
            
            $((form.status == "DELETED") ? "forms-trash" : container).appendChild(li);
            
            setTimeout(function(){
                $(form.id + "-star").observe('click', function(){
                    var s = $(form.id + "-star").src;
                    if (s.include("star-off")) {
                        $(form.id + "-star").src = s.replace('star-off', 'star-on');
                        li.addClassName('fav');
                    } else {
                        $(form.id + "-star").src = s.replace('star-on', 'star-off');
                        li.removeClassName('fav');
                    }
                    $this.saveFormList(true, 'favorite');
                });
            }, 250);
            
        };
        
        $A(forms).each(createItem);
        
        // Complete the form list with the missing items
        // never let any form to be missing
        if (container == 'forms') {
            $A($this.response).each(function(form){
                if ($this.forms[form.id] === undefined) {
                    createItem(form);
                }
            });
        }
    },
    /**
     * Updates the page with new information
     */
    updatePage: function(){
        Utils.Request({
            parameters: {
                action: 'getFormList'
            },
            onSuccess: function(res){
                this.setForms(res);
                // Utils.user.usage.accountType && $('account-type').update(Utils.user.usage.accountType.prettyName.locale());
                this.setBars();
                this.initialize();
            }.bind(this)
        });
    },
    
    /**
     * Syncs the page with latest information
     */
    syncPage: function(){
        
        if(this.stopSync !== false){
            return;
        }
        
        var $this = this;
        Utils.Request({
            parameters: {
                action: 'updateMyForms',
                username: Utils.user.username
            },
            onSuccess: function(res){
                $H(res.forms).each(function(pair){
                    var id = pair.key;
                    if (!pair.value.split) { return; }
                    var newCount = pair.value.split(":")[0];
                    var count = pair.value.split(":")[1];
                    
                    $this.forms[id]['new'] = newCount;
                    $this.forms[id]['count'] = count;
                    if (newCount > 0) {
                        var ncount = $('form_' + id).select('.new')[0];
                        if (ncount) {
                            ncount.show();
                            ncount.update(newCount + ' ' + 'New'.locale() + '!');
                        }
                    } else {
                        $('form_' + id).select('.new').invoke('hide');
                    }
                    
                    if (count > 0) {
                        var scount = $('form_' + id).select('.s-count')[0];
                        if (scount) {
                            scount.show();
                            scount.update(count);
                        }
                    } else {
                        $('form_' + id).select('.s-count').invoke('update', '0');
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
     * Check curent user config and try to match with database response
     * @param {Object} config
     */
    cleanUpUserConfig: function(config){
        for (var i = 0; i < config.length; i++) {
            if (config[i].type == 'folder') {
                config[i].items = this.cleanUpUserConfig(config[i].items);
                continue;
            }
            if ("additional" in config[i]) {
                continue;
            }
            
            var form = config[i];
            
            var res = this.getFormByIdFromResponse(form.id.replace('form_', ''));
            if (!("id" in res)) { // This means form is no longer in the database
                console.info('deleted', config[i]);
                delete config[i];
                continue;
            }
        }
        return config;
    },
    
    /**
     * When we recieve the form list from server this function prints them on the page with the folders and correct order
     * @param {Object} response
     */
    setForms: function(response){
        var $this = this;
        console.log('Bringing forms took ' + response.duration);
        Protoplus.Profiler.start('setForms');
        
        if (response.forms) {
            this.response = response.forms;
        }
        
        $('forms').update();
        var config = this.response;
        
        if (response.folderConfig) {
            config = response.folderConfig;
        } else if (Utils.user.folderConfig && Utils.user.folderConfig.length > 0) {
            config = $A(Utils.user.folderConfig).compact();
        }
        
        this.defaultSort = config;
        
        this.createFormList(config, 'forms');
        this.createList();
        
        var additional = this.getAdditional(this.defaultSort);
        this.lastSearch = document.readCookie('last-search');
        if (additional.lastFilter && this.filterType != additional.lastFilter) {
            this.filter(additional.lastFilter, additional.filterValue, true);
        }
        if ("sortBy" in additional && additional.sortBy != 'none') {
            $('myforms-sortby').selectOption(additional.sortBy);
        }
        
        this.updateTrashIcon();
        
        // Set click events
        $$('.forms').each(function(li){
            li.observe('mousedown', function(e){
                var el = Event.element(e);
                if (el.nodeName == 'IMG' || el.hasClassName('selected-links')) { return; }
                $this.selectForm(li);
                if(el.hasClassName('new')){
                    $this.openSubmissions();
                }
            });
        });
        
        // Set folder events
        $$('.folder').each(function(li){
            li.observe('mousedown', function(){
                $this.selectFolder(li);
            });
        });
        
        // Select last edited question
        if (document.readCookie('last_form')) {
            setTimeout(function(){
                
                if(document.readCookie('move-to-folder')){
                    var mform = document.readCookie('move-to-folder-formID');
                    var mfolder = document.readCookie('move-to-folder');
                    if($(mfolder) && $('form_'+mform)){
                        
                        if($this.filterType == 'main'){
                            $(mfolder).insert($('form_'+mform));
                            $this.manageEmptyMarkers();
                            $this.saveFormList();
                        }else{
                            // folder-id
                        }
                    }
                    document.eraseCookie('move-to-folder');
                    document.eraseCookie('move-to-folder-formID');
                }
                
                $('form_' + document.readCookie('last_form')) && $('form_' + document.readCookie('last_form')).run('mousedown').scrollInto();
            }, 500);
        }
        
        Protoplus.Profiler.end('setForms');
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
        $this = this;
        
        collapsed = !!collapsed;
        
        var li = $(document.createElement('li'));
        li.className = 'folder folder-open';
        li.id = 'folder_' + (++$this.folderCount)
        
        var listContainer = $(document.createElement('div'));
        var collapseButton = $(document.createElement('img'))
        collapseButton.src = 'images/myforms/new/folder-opened.png';
        collapseButton.addClassName("folder-icon");
        
        var folderName = $(document.createElement('div'))
        folderName.className = 'folder-name';
        folderName.innerHTML = name;
        
        var folderDelete = $(document.createElement('div'));
        folderDelete.className = 'folder-delete';
        folderDelete.innerHTML = '<img src="images/myforms/new/form-delete.png" align="absmiddle" /> ' + 'Delete'.locale();
        folderDelete.onclick = function(){
            deleteFolder();
        }
        
        var folderList = $(document.createElement('ul'));
        folderList.id = 'folderlist_' + $this.folderCount;
        
        
        var empty = $(document.createElement('li'));
        empty.className = 'empty_place';
        empty.innerHTML = '&nbsp;';
        
        folderList.appendChild(empty);
        
        listContainer.appendChild(collapseButton);
        listContainer.appendChild(folderName);
        listContainer.appendChild(folderList);
        listContainer.appendChild(folderDelete);
        li.appendChild(listContainer);
        
        
        var openFolder = function(){
            li.collapsed = false;
            li.addClassName('folder-open');
            collapseButton.src = 'images/myforms/new/folder-opened.png';
            folderList.show();
            return folderList;
        }
        
        var closeFolder = function(){
            li.collapsed = true;
            li.removeClassName('folder-open');
            collapseButton.src = 'images/myforms/new/folder-closed.png';
            folderList.hide();
            return folderList;
        }
        
        var toggleCollapse = function(nosave, force){
            if (folderList.visible()) {
                closeFolder();
            } else {
                openFolder();
            }
            
            if (!(nosave === true)) {
                $this.saveFormList();
            }
        };
        var deleteFolder = function(e){
            Utils.confirm('Are you sure you want to delete this folder?<br>Note that only the folder will be deleted, forms underneath will be kept.'.locale(), 'Confirm'.locale(), function(but, value){
                if (value) {
                    folderList.immediateDescendants().each(function(elem){
                        li.insert({
                            after: elem
                        });
                    });
                    li.remove();
                    $this.saveFormList();
                }
            });
        }
        
        folderList.toggleFolder = toggleCollapse;
        folderList.openFolder = openFolder;
        folderList.closeFolder = closeFolder;
        
        if (collapsed) {
            toggleCollapse(true);
        }
        setTimeout(function(){
            li.setContextMenu({
                title: name.shorten(25),
                
                onOpen: function(){
                
                },
                
                menuItems: [{
                    title:'Add New Form',
                    icon:'images/application_form_add.png',
                    handler:function(){
                        $this.newForm();
                        document.createCookie('move-to-folder', folderList.id);
                    }
                },{
                    title: 'Toggle Folder'.locale(),
                    name: 'toggle',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-toggle',
                    handler: toggleCollapse
                }, {
                    title: 'Rename'.locale(),
                    name: 'rename',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-pencil',
                    handler: function(){
                        Utils.prompt('Enter a new name for your folder'.locale(), name, 'Rename Folder'.locale(), function(value, button, isOK){
                            if (isOK) {
                                name = value;
                                folderName.innerHTML = name;
                                $this.saveFormList();
                            }
                        });
                    }
                }, '-', {
                    title: 'Delete Folder'.locale(),
                    name: 'delete',
                    iconClassName: "context-menu-cross_shine",
                    icon: "images/blank.gif",
                    handler: deleteFolder
                }]
            });
        }, 200);
        
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
        folderName.observe('click', toggleCollapse);
        collapseButton.observe('click', toggleCollapse);
        
        return {
            element: li,
            list: folderList
        };
    },
    /**
     * Deletes or adds empty list items in the lists
     */
    manageEmptyMarkers: function(){
        $$('ul').each(function(e){
            if (e.descendants().length < 1) {
                e.insert(new Element('li', {
                    className: 'empty_place'
                }).insert('&nbsp;'));
            }
        });
        $$('.empty_place').each(function(el){
            if (el.previousSibling || el.nextSibling) {
                el.remove();
            }
        });
    },
    
    /**
     * Creates a new folder at the top of the list by prompting user the folder name
     */
    newFolder: function(item){
        Utils.prompt('Enter a name for new folder:'.locale(), 'New Folder'.locale(), 'Create a New Folder'.locale(), function(value, button, isOK){
            if (isOK) {
            
                var folder = this.createFolder(value);
                $('forms').insert({
                    top: folder.element
                });
				
                if(item && Object.isElement(item)){
					folder.list.insert(item);
					this.manageEmptyMarkers();
				}
				
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
            constraint: 'vertical',
            tree: true,
            delay: 200,
            markDropZone: true,
            scroll: window,
            dropZoneCss: 'dropZone',
            markEmptyPlace: true,
            dropOnEmpty: true,
            onUpdate: function(){
                this.manageEmptyMarkers();
                this.saveFormList();
                this.createList(); // Kinda slows down. but it's the only way to solve ampty folder problem
            }.bind(this)
        });
        this.manageEmptyMarkers();
    },
    /**
     * Retrieves the additional config
     * @param {Object} config
     */
    getAdditional: function(config){
        config = config || this.defaultSort;
        for (var i in config) {
            if ("additional" in config[i]) { return config[i].additional; }
        }
        return {};
    },
    /**
     * Adds or updates the additional config
     * @param {Object} config
     * @param {Object} obj
     */
    addAdditional: function(config, obj){
        for (var i in config) {
            if ("additional" in config[i]) {
                config[i] = {
                    additional: obj
                };
                return;
            }
        }
        config.push({
            additional: obj
        });
    },
    /**
     * Updates the favorites infomation without changing the sort order
     * @param {Object} config
     */
    updateStars: function(config){
        var i = 0;
        while (i < config.length) {
            var item = config[i++];
            if (item === undefined) {
                continue;
            }
            if ("additional" in item) {
                continue;
            }
            if (item.type == 'folder') {
                item.items = this.updateStars(item.items);
            } else {
                var id = "form_" + (item.id.replace('form_', ''));
                if ($(id) && $(id).hasClassName('fav')) {
                    item.fav = true;
                } else {
                    delete item.fav;
                }
            }
        }
        return config;
    },
    
    /**
     * Save the new order of the form list in database for later use
     */
    saveFormList: function(onlyFilters, additionalValue, sort){
        if (onlyFilters !== true && this.filterType != 'main') { return; }
        var config;
        if (onlyFilters) {
            if (additionalValue == 'favorite') {
                config = this.updateStars(this.defaultSort);
            } else {
                config = this.defaultSort;
            }
        } else {
            config = this.serializeFormList('forms');
        }
        
        var add = this.getAdditional(this.defaultSort);
        
        this.addAdditional(config, {
            lastFilter: this.filterType,
            filterValue: additionalValue || add.filterValue,
            sortBy: sort || 'none'
        });
        
        setTimeout(function(){
            this.defaultSort = config;
            Utils.Request({
                parameters: {
                    action: 'saveFolderConfig',
                    config: Object.toJSON(config)
                },
                onFail: function(res){
                    Utils.alert(res.error);
                }
            });
        }.bind(this), 100); // Lazy ajax
    },
    /**
     *
     * @param {Object} elem
     */
    selectFolder: function(elem){
    
        if (this.selectedFolder != elem) {
            this.selectedFolder = elem;
        } else {
            this.selectedFolder = false;
        }
    },
    
    /**
     * Form selection action
     * @param {Object} elem
     */
    selectForm: function(elem){
        if (this.selected) {
            this.selected.removeClassName('selected');
            this.selected.select('.selected-links').invoke('hide');
        }
        if ($('reportsBox')) {
            $('reportsButton').removeClassName('button-over').setStyle('height:68px;');
            $('reportsBox').remove();
        }
        if (this.selected != elem) {
            this.selected = elem.addClassName('selected');
            
            if (this.selected.parentNode.id != "forms-trash") {
                this.selected.select('.selected-links').invoke('show');
                $('inot') && $('inot').remove();
                if (this.forms[elem.id.replace("form_", "")]['new'] > 0) {
                    $('submissionButton').insert(new Element('span', {
                        className: 'notify',
                        id: 'inot'
                    }).insert('<span class="arrow"></span>' + this.forms[elem.id.replace("form_", "")]['new']));
                }
                $('group-properties').show();
                $('form-properties').show();
            } else {
                $('undelete-form').show();
                $('permadelete-form').show();
            }
        } else {
            $('group-properties').hide();
            $('form-properties').hide();
            if (this.selected.parentNode.id == "forms-trash") {
                $('undelete-form').hide();
                $('permadelete-form').hide();
            }
            this.selected = false;
        }
    },
    /**
     * Demonstrates the bar chart color and animations
     */
    demo: function(reset){
    	if(reset !== true){
	    	Utils.user.usage.submissions = 25 * Utils.user.usage.accountType.limits.submissions / 100;
	        Utils.user.usage.uploads = 55 * Utils.user.usage.accountType.limits.uploads / 100;
	        Utils.user.usage.payments = 75 * Utils.user.usage.accountType.limits.payments / 100;
	        Utils.user.usage.sslSubmissions = 95 * Utils.user.usage.accountType.limits.sslSubmissions / 100;
    	} else {
	    	Utils.user.usage.submissions = 0;
	        Utils.user.usage.uploads = 0;
	        Utils.user.usage.payments = 0;
	        Utils.user.usage.sslSubmissions = 0;
    	}
        this.setBars();
    },
    
    
    /**
     * Gets the percentage of the usage and updates the bar content
     * @param {Object} type
     */
    getPercent: function(type){
        var avg = (Utils.user.usage[type] || 0) / (Utils.user.usage.accountType.limits[type] || 0) * 100;
        if (type == "uploads") {
            $('usage-' + type).update(Utils.bytesToHuman(Utils.user.usage[type]).replace('.00', ''));
            $('limit-' + type).update(Utils.bytesToHuman(Utils.user.usage.accountType.limits[type]).replace('.00', ''));
            //$(type+'-limit').update("%s/%s (%s%)".locale(Utils.bytesToHuman(Utils.user.usage[type]).replace('.00', ''), Utils.bytesToHuman(Utils.user.usage.accountType.limits[type]).replace('.00', ''), avg.toFixed(2)));
        } else {
            $('usage-' + type).update(Utils.user.usage[type]);
            $('limit-' + type).update(Utils.user.usage.accountType.limits[type]);
            //$(type+'-limit').update("%s/%s (%s%)".locale(Utils.user.usage[type], Utils.user.usage.accountType.limits[type], (avg.toFixed(2).toString().replace(".00", ""))));
        }
        return avg >= 100 ? 100 : avg;
    },
    /**
     * Will serialize form list then save it to database
     * Then we will re-create the list based on this object
     * @param {Object} list
     */
    serializeFormList: function(list){
        var formList = [];
        $(list).immediateDescendants().each(function(element){
            if (element.hasClassName('forms')) {
                var c = {
                    type: 'form',
                    id: element.id
                };
                
                if (element.hasClassName('fav')) {
                    c.fav = true;
                }
                
                formList.push(c);
            }
            
            if (element.hasClassName('folder')) {
                formList.push({
                    type: "folder",
                    collapsed: !!element.collapsed,
                    name: element.select('.folder-name')[0].innerHTML,
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
    newForm: function(){
        Utils.loadScript("js/builder/newform_wizard.js", function(){
            openNewFormWizard();
        });
    },
    /**
     * Key actions for this page
     */
    keys: {
        Up: { // Select previous question
            handler: function(e){
                Event.stop(e);
                var checkPreviousSibling = function(p){
                    if (!p) { return; }
                    if (p.hasClassName('forms')) {
                        p.scrollInto({
                            direction: 'top',
                            offset: [100, 120]
                        });
                        p.run('mousedown');
                    } else if (p.hasClassName('folder')) {
                        var fr = p.select('.forms').last();
                        var ul = p.select('ul')[0];
                        if (fr) {
                            if (!ul.visible()) {
                                ul.openFolder();
                            }
                        }
                        fr.run('mousedown');
                    }
                }
                
                if (MyForms.selected && MyForms.selected.previousSibling) {
                    checkPreviousSibling(MyForms.selected.previousSibling);
                } else if (MyForms.selected && MyForms.isUnderFolder(MyForms.selected)) {
                    checkPreviousSibling(MyForms.selected.up('li').previousSibling);
                } else if (!MyForms.selected && $('forms').lastChild) { // "!MyForms.selected &&" disable the wrap around list feature. it's a bit buggy
                    checkPreviousSibling($('forms').lastChild);
                }
                return false;
            },
            disableOnInputs: true
        },
        Down: { // Select next question
            handler: function(e){
                Event.stop(e);
                var checkNextSibling = function(n){
                    if (!n) { return; }
                    if (n.hasClassName('forms')) {
                        n.scrollInto({
                            direction: 'bottom'
                        });
                        n.run('mousedown');
                    } else if (n.hasClassName('folder')) {
                        var fr = n.select('.forms')[0];
                        var ul = n.select('ul')[0];
                        if (fr) {
                            if (!ul.visible()) {
                                ul.openFolder();
                            }
                        }
                        fr.run('mousedown');
                    }
                }
                
                if (MyForms.selected && MyForms.selected.nextSibling) {
                    checkNextSibling(MyForms.selected.nextSibling);
                } else if (MyForms.selected && MyForms.isUnderFolder(MyForms.selected)) {
                    checkNextSibling(MyForms.selected.up('li').nextSibling);
                } else if (!MyForms.selected && $('forms').firstChild) { // "!MyForms.selected &&" disable the wrap around list feature. it's a bit buggy
                    checkNextSibling($('forms').firstChild);
                }
                return false;
            },
            disableOnInputs: true
        },
        Enter: {
            handler: function(){
                if (MyForms.selected) {
                    MyForms.editForm();
                    return false;
                }
            },
            disableOnInputs: true
        },
        Delete: { // Delete selected question
            handler: function(){
                if (MyForms.selected) {
                    MyForms.deleteForm();
                    return false;
                }
            },
            disableOnInputs: true
        },
        Backspace: {
            handler: function(e){
                Event.stop(e);
                return false;
            },
            disableOnInputs: true
        }
    }
};

var Utils = Utils || new Common();
document.ready(function(){
    MyForms.initialize();
    Utils.fullScreenListener();
});
document.keyboardMap(MyForms.keys);
