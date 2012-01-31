var Utils = Utils || new Common();
var Admin = {
    iframeEnlarged: false,
    openPage: function(page, parameters){
        var par = "";
        if(parameters){
            par = "?"+$H(parameters).toQueryString();
        }
        $('page').src = "admin/"+page+".php"+par;
    },
    makeSearch: function(username){
    	if (typeof username == "undefined"){
    		$('searchbox').value = $('searchbox').value.strip();
    		username = $('searchbox').value; 
    	}else{
    		$('searchbox').value = username;
    		$('searchbox').setStyle({color:"#000"});
    	}
    	
		if (!Admin.iframeEnlarged){
    		Admin.togglePanelSize();
		}

		this.openPage("search",{
            keyword: username
        });
    },
    togglePanelSize: function (){
    	var button = $('enlarge-button');
    	var className = "button-over";
    	if (button){
        	switch (Admin.iframeEnlarged){
	        	case true:
		    		button.removeClassName(className);
		    		Admin.collapsePanel();
	    		break;
		    	case false:
		    		button.addClassName(className);
		    		Admin.enlargePanel();
	    		break;
        	}
    	}
    },
    enlargePanel: function (){
    	// change the properties
    	$('stage').setStyle({
    		'float':'none'
    	});
    	$('shadoww').hide();
    	$('page').setStyle({
    		'width':'899px'
    	});
    	Admin.iframeEnlarged = true;
    },
    collapsePanel: function (){
    	// change the properties
    	$('stage').setStyle({
    		'float':'right'
    	});
    	$('shadoww').show();
    	$('page').setStyle({
    		'width':'699px'
    	});
    	Admin.iframeEnlarged = false;
    },
    logout: function(){
        document.eraseCookie('admin');
        document.eraseCookie('support');
        location.href="index.php";
    },
    commitDatabase: function(){
        Utils.Request({
            parameters:{
                action:'commitDatabaseSchema'
            },
            onComplete: function(res){
                if(res.success){
                    Utils.alert(res.message);
                }else{
                    Utils.alert(res.error, "Error");
                }
            }
        });
    },
    clearAllCache: function() {
        if(!confirm('Are you sure you want to clear ALL caches?')){
            return;
        }
        Utils.Request({
            parameters:{
                action:'clearCache',
                type: 'all'
            },
            onComplete: function(res){
                if(!res.success){
                    Utils.alert(res.error, "Error");
                }else{
                    Utils.alert("All caches are cleared", "Success");
                }
            }
        });
    },
    
    loginToUserAccount: function(username, callback){
        Utils.Request({
            parameters: {
                action:'loginToAccount',
                username: username
            },
            onComplete: function(res){
                if(res.success){
                    callback && callback();
                    Utils.redirect(Utils.HTTP_URL+"page.php", {
                        parameters:{
                            p:'myforms'
                        },
                        target:'_blank'
                    });
                }else{
                    Utils.alert(res.error, 'Error');
                }
            }
        })
    },
    
    crawlUsers: function(options){
        Utils.Request({
            parameters: {
                action: 'crawlUsers',
                className: options.className,
				chunkSize: options.chunkSize,
                chunk: options.chunk
            },
            onComplete: function(res){
                options.callback(res);
            }
        });
    },
    
    operateUser: function(options){
        Utils.Request({
            parameters: {
                action: 'userOperations',
                className: options.className,
				username: options.username
            },
            onComplete: function(res){
                options.callback(res);
            }
        });
    },
    
    migrateUser: function(options){
        Utils.Request({
            parameters: {
                action:'migrateUser',
                addPrefix: options.addPrefix,
                merge: options.mergeAccount,
                username: options.username
            },
            onComplete: function(res){
                options.callback(res);
            }
        });
    },
    
    migrateAll: function(options){
        Utils.Request({
            parameters: {
                action:'migrateAllUsers',
                chunk: options.chunk
            },
            onComplete: function(res){
                options.callback(res);
            }
        });
    }, 
    migrateAllSubmissions: function(options){
        Utils.Request({
            parameters: {
                action:'migrateAllSubmissions',
                chunk: options.chunk
            },
            onComplete: function(res){
                options.callback(res);
            }
        });
    },
    
    toggleDebugMode: function(button){
        button = $(button);
        this.openDebugOptions(function(value){
            if(value){
                button.addClassName('button-over');
                document.createCookie('DEBUG', 'debug=yes');
            }else{
                button.removeClassName('button-over');
                document.eraseCookie('DEBUG');
            }
        });
    },
    
    openDebugOptions: function(callback){
        var options = document.readCookie('debug_options');
        try{
            if(options){
                options = options.evalJSON();
            }else{
                options = {};
            }
        }catch(e){
            options = {};
        }
        
        var defaultOptions = {
            stopSubmission:{
                text:'Stop the submissions and display details',
                desc: 'Display a brief summary on submit page. Do not complete the submission',
                checked: false
            },
            useSandbox:{
                text:'Use sandbox accounts for payments',
                desc:'Enables the sandbox URLS for payment gateways, must use sandbox credentials',
                checked: false
            },
            decompressPage: {
                text:'Disable compression on the page',
                desc: 'Disable the minifier and CDN on the JotForm site.',
                checked:false
            },
            decompressForm: {
                text:'Disable compression on the forms',
                desc: 'Use uncompressed jotform.js file, enable error tracking and condition logging',
                checked:false
            },
            stopValidations: {
                text:'Disable form validations',
                desc: 'In order to easyly submit the biggest forms. Validations will be shown but submission will not stop',
                checked:false
            },
            disableFormPropertyCache: {
                text:'Disable Form Property Cache',
                desc:'This is already disabled by default. this option is deprecated',
                checked:false
            },
            useCDN: {
            	text:'Use CDN',
                desc: 'Force JotForm to use CDN URls. @completed',
            	checked:false
            },
            uploadToAmazonS3: {
            	text:'Upload files to Amazon S3',
                desc: 'Force JotForm to use S3 services for uploads. @completed',
            	checked:false
            },
            useBanners: {
                text: 'Display Banners on the site',
                desc:'Enable the Session::isBannerFree() function to display banners according to user type',
                checked: false
            },
            useNewEmailWizard: {
            	text: 'Display new email wizard page',
            	desc: 'This is a temprory debug mode',
            	checked: false
            }
        };
        
        var boxes = '<ul class="debug-options-list" style="text-align:left">';
        $H(defaultOptions).each(function(opt){
            var checked = opt.value.checked;
            var text    = opt.value.text;
            if(opt.key in options){
                checked = options[opt.key];
            }
            var ch = "", cl ="";
            if(checked){
                ch = 'checked="checked"';
                cl = ' class="selected-option"';
            }
            
            boxes += '<li' + cl + '><label><input type="checkbox" ' + ch + ' class="debug-options" value="' + opt.key + '" /> ' + text + '</label>';
            if(opt.value.desc){
                boxes += '<div class="option-description">' + opt.value.desc + '</div>';
            }
            boxes += '</li>';
            
        });
        boxes += "<ul>";
        
        document.window({
            title: "Please select debug parameters",
            content: boxes,
            width:550,
            onClose: function(w, key){
                if(key == 'ESC' || key == 'CROSS'){ return; }
                var sOptions = {};
                $$('.debug-options').each(function(inp){
                    sOptions[inp.value] = inp.checked;
                });
                // There is a problem with this on safari
                document.createCookie('debug_options', Object.toJSON(sOptions));
            },
            onInsert: function(){
                $$('.debug-options').each(function(el){
                    el.observe('click', function(){
                        if(el.checked){
                            el.up('li').addClassName('selected-option');
                        }else{
                            el.up('li').removeClassName('selected-option');
                        }
                    });
                });
            },
            contentPadding:30,
            buttons: [{
                title:"Close Debug Mode",
                name:"closeMode",
                icon:'images/cross-circle.png',
                iconAlign:'left',
                className: 'big-button buttons buttons-grey',
                handler: function(w){
                    callback(false);
                    w.close();
                }
            },{
                title: "Set Debug Mode",
                name: 'setMode',
                icon:'images/debug.png',
                iconAlign:'left',
                className: 'big-button buttons buttons-green',
                handler: function(w){
                    callback(true);
                    w.close();
                }
            }]
        });
    },    
    reCalculateUploadUsage: function(username, callback){
        Utils.Request({
            parameters: {
                action: 'calculateDiskUsage',
                deep: true,
                username: username
            },
            onSuccess: function(res){
                callback(res.newSize);
            }
        });
    },
    
    getApplication: function(){
        Utils.Request({
            parameters:{
                action:'getAppPackage'
            },
            onSuccess: function(res){
                Utils.alert('<div style="text-align:left;"><b>Download Link</b><br><input type="text" readonly value="'+
                    res.url+'" style="padding:5px;width:95.8%"><br><b>Details:</b><div style="background:none repeat scroll 0 0 white;border:1px solid #888888;overflow-x:auto;overflow-y:hidden;padding:5px;white-space:nowrap;">'+
                    res.scriptResult+'</div></div>', 'Application Download', false, {
                        width:400
                    });
            },
            onFail: function(res){
                Utils.alert('<div style="text-align:left;">There was a problem on zip generation<br><b>Details:</b><div style="padding:5px; background:white; border:1px solid #888;">'+
                    res.details+'</div></div>', 'Application Download', false, {
                        width:400
                    });
            }
        });
    },
    
    buildNow: function(){
        Utils.Request({
            parameters: {
                action: 'deployServers'
            },
            onComplete: function(res){
                if(res.success){
                    Utils.alert(res.message);
                }else{
                    Utils.alert(res.error, "Error");
                }
            }
        });
    },
    deleteServer: function(name, id){
        Utils.confirm("Are you sure you want to delete this server?<hr>Server Name:<b>"+name+"</b>", "Easy there tiger!!.", function(button, value){
            if(value){
                $(id).remove();
            }
        })
    },
    getServerList: function(){
        Utils.Request({
            parameters: {
                action: 'listServers'
            },
            onComplete: function(res){
                if(res.success){
                    var cont = '<table cellpadding="10" cellspacing="0" width="350">';
                    cont += '<tr><th>Server Name</th><th width="100">Public IP</th><th width="20">Del?</th></tr>';
                    $H(res.serverList.remote).each(function(server, i){
                        var name = server.key;
                        var ip = server.value;
                        var bg = i%2 == 0? 'background:#fff' : '';
                        
                        cont += '<tr id="server-'+i+'" style="'+bg+'">';
                        cont += "<td title='"+name+"'>"+name.shorten(20) + "</td>";
                        cont += '<td align="center"><a href="http://'+ip+'/" target="_blank">'+ ip +"</a></td>";
                        cont += '<td align="center"><img src="images/cross.png" onclick="Admin.deleteServer(\'' + name + '\', \'server-'+i+'\');" /></td>';
                        cont += "</tr>"
                    });
                    
                    cont += "</table>";
                    
                    document.window({
                        title:'Server List',
                        width:350,
                        contentPadding:0,
                        content: cont,
                        buttons:[{
                            title:'Close',
                            handler: function(w){
                                w.close();
                            }
                        }]
                    });
                    
                }else{
                    Utils.alert(res.error, "Error");
                }
            }
        });
    }
};

document.ready(function(){
    $('action-properties').cleanWhitespace();
    $('searchbox').hint("Search Users",{hintColor:'#eee'}).observe('keyup', function(e){
        e = document.getEvent(e);
        if(e.keyCode == 13){
            Admin.makeSearch();
        }
    });
    
    if(document.readCookie('DEBUG') == 'debug=yes'){
        $('admin-debug-mode').addClassName('button-over');
    }
    if($('last-cache-date').innerHTML.strip()){
        $('clear-cache-button').tooltip($('last-cache-date').innerHTML, {width:150});
    }
    Utils.setToolbarFloat();
    Utils.setToolboxFloat();
    Utils.setAccordion($('tools-wrapper'), {openIndex: 0} );
    // username is send from get search the user :)
    var username = document.get.username;
    if (typeof username != "undefined"){
    	Admin.makeSearch(username);
    }
    var keyword = document.get.keyword;
    if (typeof keyword != "undefined"){
    	Admin.makeSearch(keyword);
    }
});
