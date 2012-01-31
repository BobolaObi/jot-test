var FTPWizard = {
    wiz:false,          // Wizard object, window
    connection: false,  // If FTP connection was successfull or not
    formID: false,      // Current form's ID
    username: false,    // Current user's name
    properties:{},      // FTP integration properties
    
    /**
     * Makes a connection test with given properties and sends the result in callback function
     * @param {Object} host
     * @param {Object} username
     * @param {Object} password
     * @param {Object} port
     * @param {Object} callback
     */
    testConnection: function(host, username, password, port, callback){
        Utils.Request({
            parameters:{
                action:'testFTPConnection',
                host:host,
                port:port || 21,
                username:username,
                password:password
            },
            onSuccess:function(r){
                callback(true, '<img src="images/success_small.png" align="absmiddle" style="width:16px;margin: 0 0 1px 2px;" />');
                this.connection = true;
            }.bind(this),
            onFail:function(r){
                callback(false, '<img src="images/cross2.png" align="absmiddle" style="margin: 0 2px;" />' + r.error);
                this.connection = false;
            }.bind(this)
        });
    },
    /**
     * When wizard template inserted to the documents
     */
    onInsert: function(w){
        var $this = this;
        
        var initials = function(){
            // If properties were send to wizard use them
            if(this.properties){
                $('ftp-host').value = this.properties.host;
                $('ftp-username').value = this.properties.username;
                // $('ftp-password').value = this.properties.password; // Password auto complete disabled for security reasons
                $('ftp-port').value = this.properties.port;
                $('ftp-path').value = this.properties.path || "/";
                this.connection = true;
                
                // If password was already written one time. Don't display it again
                var chbutton;
                $('ftp-password').hide();
                $('ftp-test-connection').hide();
                $('ftp-password').insert({after:chbutton = new Element('button', { className: 'big-button buttons buttons-grey' })});
                this.properties.password = false;
                chbutton.update('Change Password').observe('click', function(){
                    chbutton.hide();
                    $('ftp-password').show();
                    $('ftp-test-connection').show();
                    $this.connection = false;
                });
                this.nextPage(); // If integration is already completed, no need for the first page
            }
            
            /**
             * Functionality of test connection button
             */
            $('ftp-test-connection').on('click', function(){ $this.testButton(); });
        }.bind(this);
        
        
        if(!Utils.isSecure && !document.readCookie('ftp-unsecure')){
            var block = w.blockWindow();
            var warning = new Element('div', {className:'ftp-ssl-warning'}); 
            var warningTexts = new Element('div', {className:'ftp-ssl-warning-texts'});
            var warningButtonCont = new Element('div', {className:'ftp-ssl-button-cont'});
            warning.insert(warningTexts).insert(warningButtonCont);
            block.insert(warning);
            warningTexts.insert('<img src="images/secure-big.png" align="left" style="margin-right:15px;margin-bottom:20px;" />');
            warningTexts.insert('You are currently using an unsecure connection, your FTP password will be transferred unsecurely. Would you like to switch to a secure connection?<br /><span style="color:#999;font-size:9px;">PS: Page will refresh</span>');
            
            warningButtonCont.insert(new Element('button', {className:'big-buttons buttons buttons-grey'}).insert("Go Secure.").observe('click', function(){
                document.createCookie('open-ftp-wizard', 'yes');
                var httpsURL = location.href.replace('http://', 'https://');
                location.href = httpsURL;
            }));
            
            warningButtonCont.insert(new Element('button', {className:'big-buttons buttons buttons-grey'}).insert("Thanks, I'm fine").observe('click', function(){
                w.unblockWindow();
                warning.remove();
                document.createCookie('ftp-unsecure', 'yes');
                initials(); // Continue using wizard;
            }));
            block.insert({after:warning});
            return;
        }
        
        initials(); // Start wizard
    },
    /**
     * Test button functionality
     */
    testButton: function(callback){
        var $this = this;
        // Put please wait text
        $('ftp-test-result').update('<img src="images/loading.gif" align="absmiddle" />');
        
        // Call test connection function
        this.testConnection($('ftp-host').value, $('ftp-username').value, $('ftp-password').value, $('ftp-port').value, function(connectionOK, message){
            if(connectionOK){
                if(!$this.properties){ $this.properties = {}; }
                $this.properties.host     = $('ftp-host').value;
                $this.properties.username = $('ftp-username').value;
                $this.properties.password = $('ftp-password').value;
                $this.properties.port     = $('ftp-port').value;
                
                $('ftp-test-result').update(message).setStyle('color:green');
                (callback && callback());
            }else{
                $('ftp-test-result').update(message).setStyle('color:#FF3A1C;font-size:10px');
            }
        });
    },
    /**
     * Show/Hide wizard buttons
     * @param {Object} buttonList
     */
    showButtons: function(buttonList){
        var $this = this;
        $H(this.wiz.buttons).each(function(button){
            if($this.firstTime && button.key == 'remove'){ return; }
            if(buttonList.include(button.key)){
                button.value.show();
            }else{
                button.value.hide();
            }
        });
    },
    /**
     * Show hide wizard pages
     * @param {Object} page
     */
    showPage: function(page){
        $$('.ftp-page').invoke('hide');
        $(page).show();
        this.wiz.reCenter();
    },
    /**
     * Back page button
     */
    backPage: function(){
        if ($('page2').visible()) {
            this.showPage('page1');
            this.showButtons(['remove', 'next']);
        }
    },
    /**
     * Next page button
     */
    nextPage: function(){
        if($('page1').visible()){
            if(!this.connection){
                
                this.testButton(function(){
                    this.toFTPListPage();
                }.bind(this));
                
                return;
            }else{
                this.toFTPListPage();
            }
        }
    },
    /**
     * Does all the Job before going to second page
     */
    toFTPListPage: function(){
        // Save the connection parameters
        this.saveIntegration(function(){
            this.showPage('page2');
            this.showButtons(['remove', 'back', 'finish']);
            this.getFolders('/');
            $('ftp-files').softScroll(); // put beatiful soft scrools
            var dropdown = $('ftp-fields');
            dropdown.insert(new Element('option', {value:"none"}).update("Use Default - Submission ID"));
            dropdown.insert(new Element('option', {value:"nofolder"}).update("No Folder"));
            if('Submissions' in window){
                $H(Submissions.properties).each(function(prop){
                    if(prop.value.type && ['control_textbox', 'control_autocomp', 'control_email', 'control_radio', 'control_dropdown', 'control_fullname', 'control_hidden', 'control_autoincrement'].include(prop.value.type)){
                        dropdown.insert(new Element('option', {value:prop.value.qid}).update(prop.value.text.shorten('20')));
                    }
                });
            }
            dropdown.selectOption(this.properties.folder_field);
        }.bind(this));
    },    
    /**
     * Save the connection parameters for FTP
     */
    saveIntegration:function(callback){
        
        var props = {
            host: this.properties.host,
            username: this.properties.username,
            port: this.properties.port
        };
        
        if(this.properties.password){
            props.password = this.properties.password;
        }
        
        Utils.Request({
            parameters: {
                action:'setIntegrationProperties',
                formID: this.formID,
                username: this.username,
                type:'FTP',
                props: Object.toJSON(props)
            },
            onComplete:function(t){
                callback();
            }.bind(this)
        });
    },
    
    testPath: function(path, callback){
        Utils.Request({
            parameters:{
                action:'sendFileToFTP',
                username: this.username,
                formID: this.formID,
                filePath:'opt/FTPReadme.txt',
                basePath:path+'/README.txt'
            },
            onSuccess: function(){
                callback();
            },
            onFail: function(t){
                Utils.alert("We couldn't complete the integration with your server."+
                ' '+"This could be related to the file/folder permissions. Please check permissions and try again."+"<br><br><b>"+
                "Here is the error returned by the server:"+"</b><br>--<br>"+t.error, 'Problem on FTP', false, {
                    width:440,
                    noCenter:true
                });
            }
        });
    },
    
    finishWizard: function(w){
        // User may change it by hand
        this.properties.path = $('ftp-path').value;
        if(this.properties.path.strip() == "/"){
            Utils.alert('Please choose a path for your uploads.');
            return;
        }
        
        
        // Test if we can write to this path
        this.testPath(this.properties.path, function(){
            
            this.properties.folder_field = $('ftp-fields').value;
            Utils.Request({
                parameters: {
                    action:'setIntegrationProperties',
                    formID: this.formID,
                    username: this.username,
                    type:'FTP',
                    props: Object.toJSON({
                        path: this.properties.path,
                        folder_field: this.properties.folder_field
                    })
                },
                onComplete: function(t){
                    if($('ftpButton-check')){
                        $('ftpButton-check').addClassName('element-selected');
                        if('Submissions' in window){
                            Submissions.FTPIntegrated = true;
                            Submissions.FTPProps = {
                                folder_field: this.properties.folder_field,
                                host:       this.properties.host,
                                password:   this.properties.password,
                                path:       this.properties.path,
                                port:       this.properties.port,
                                username:   this.properties.username
                            };
                        }
                    }
                    w.close();
                }.bind(this)
            });
            
        }.bind(this));
    },
    removeIntegration: function(w){
        Utils.Request({
            parameters: {
                action: 'removeIntegration',
                type: 'FTP',
                username: this.username,
                formID: this.formID
            },
            onSuccess: function(){
                if($('ftpButton-check')){
                    $('ftpButton-check').removeClassName('element-selected');
                }
                
                if('Submissions' in window){
                    Submissions.FTPIntegrated = false;
                    Submissions.FTPProps = false;
                }
                w.close();                            
            }, onFail: function(res){
                Utils.alert(res.error, 'Error!');
            }
        });
    },
    /**
     * Gets the list of folders from FTP server
     * @param {Object} id
     */
    getFolders: function (id){
        var $this = this;
        id = id || "/";  // If id was not provided use the root insted
        var div = $(id); // Get container of this folder
        // List of public folders for visual guidence
        var webfolders = ["httpdocs", "htdocs", "public_html", "www", "documents", "Documents", "web", "CGI-Executables"];
        // Put a loading text inside folder first
        div.update('<img align="absmiddle" src="images/loading.gif" /> Please wait...');
        
        // Make a request to server to retrieve folder list for given path
        Utils.Request({
            parameters:{
                action:   'getFTPFolders',
                username: $this.username,
                formID:   $this.formID,
                folder:   id
            },
            // If successful
            onSuccess: function(t){
                div.update(); // clean up the please wait text
                
                if(t.dir.length === 0){
                    var emptyDiv = new Element('div', { className: "ftp-empty" }).insert("-- Empty");
                    div.insert(emptyDiv);
                    $('ftp-files').updateScrollSize();
                    return;
                }
                
                // loop through each folder
                $A(t.dir).each(function(folder){
                    if (folder.name.match(/^\./)) { return true; }
                    // Display files for visual guidence.
                    if (folder.type === "file") {
                        var filediv = new Element('div', { className: "ftp-file" }).insert(folder.name);
                        filediv.observe("onclick", function(){
                            // File do nothing on our wizard
                        });
                        div.insert(filediv);
                        return true;
                    }
                    
                    var folderdiv = new Element('div',  { className: ($A(webfolders).include(folder.name)) ? "ftp-folder-user" : "ftp-folder" });
                    var openbutton = new Element('div', { className: "ftp-folder-close" });
                    var filename = new Element('div',   { className: 'ftp-fname' }).insert(folder.name);
                    
                    if(folder.type == 'link'){
                        filename.setStyle('color:blue');
                        filename.title = 'This folder is a link to => '+(folder.path+"/"+folder.link+"/").replace(/\/+/gim, '/');
                    }
                    
                    folderdiv.insert(openbutton);
                    folderdiv.insert(filename);
                    
                    var d_id = folder.path + "/" + folder.name;
                    var expand = function(){
                        if (openbutton.expanded) {
                            $(d_id).hide();
                            openbutton.addClassName("ftp-folder-close");
                            openbutton.expanded = false;
                            $('ftp-files').updateScrollSize();
                        } else {
                            openbutton.addClassName("ftp-folder-open");
                            if (!openbutton.cache) {
                                $this.getFolders(d_id);
                                openbutton.cache = true;
                            } else {
                                $(d_id).show();
                                $('ftp-files').updateScrollSize();
                            }
                            openbutton.expanded = true;
                        }
                    };
                    
                    folderdiv.observe("dblclick", expand);
                    openbutton.observe("click", expand);
                    
                    filename.observe('click', function(){
                        if ($this.selectedFolder) {
                            $this.selectedFolder.removeClassName('ftp-folder-selected');
                        }
                        $this.selectedFolder = filename.addClassName('ftp-folder-selected');
                        
                        var p = folder.path+"/"+folder.name+"/";
                        if(!p.include('JotForm')){
                            p += "/JotForm/"; // IF selected folder does not have JotForm in it add JotForm folder automagically
                        }
                        
                        $this.properties.path = (p).replace(/\/+/gim, '/');
                        $('ftp-path').value = $this.properties.path;
                    })
                    div.insert(folderdiv);
                    div.insert(new Element("div", {
                        id: folder.path + "/" + folder.name,
                        className: "ftp-folder-items"
                    }));
                });
                $('ftp-files').updateScrollSize();
            },
            onFail: function(t){
                
            }
        });
    },
    
    /**
     * Initiate wizard object and set all events and stuff
     * @param {Object} formID
     * @param {Object} username
     * @param {Object} properties
     */
    openWizard: function(formID, username, properties){
        var $this       = this;
        this.formID     = formID;
        this.username   = username;
        this.properties = properties;
        // If no properties was set then it's the first time for this wizard.
        this.firstTime  = !properties;
         
        Utils.loadTemplate('wizards/FTPWizard.html', function(t){            
            $this.wiz = document.window({
                title:'FTP Integration Wizard',
                content:t,
                contentPadding:20,
                onClose:function(){
                    $this.selectedFolder = false;
                    $this.properties = {};
                    $this.connection = false;
                },
                onInsert:function(w){
                    $this.onInsert(w);
                },
                buttons:[{
                    title:'Remove integration',
                    name:'remove',
                    color:'blood',
                    align:'left',
                    hidden:true,
                    handler: function(w){
                        $this.removeIntegration(w);
                    }
                },{
                    title:'Back',
                    name:'back',
                    icon:'images/back.png',
                    hidden:true,
                    handler:function(w){
                        $this.backPage(w);
                    }
                },{
                    title:'Next',
                    name:'next',
                    icon:'images/next.png',
                    iconAlign:'right',
                    handler:function(w){
                        $this.nextPage(w);
                    }
                },{
                    title:'Finish',
                    name:'finish',
                    hidden:true,
                    color:'green',
                    handler:function(w){
                        $this.finishWizard(w);
                    }
                }]
            });
            
        });
    }
}