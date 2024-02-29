/**
 * @company Interlogy LLC
 * @author  Seyhun Sariyildiz
 */

var AdminDetails = Class.create({
	initialize: function (){
		// get the username, keyword and formId from the get variables.
		this.username = document.get.username;
		this.keyword = document.get.keyword;
		this.formId = /^\d+$/.test(this.keyword) ? this.keyword : false;

		// account type listener
        this.accountType = $('accountType');
        this.accountType.observe('dblclick', this.startAccountTypeChange.bind(this));

        // set status listener
        this.status = $('status');
        this.status.observe('dblclick', this.startStatusChange.bind(this));

        // montly usage listener
        if ($('monthlySubmissionsTable')){
            this.usageIdObj = {
                    'mu_submissions': 'submissions',
                    'upload-size': 'uploads',
                    'mu_payments': 'payments',
                    'mu_ssl_submissions': 'ssl_submissions',
                    'mu_tickets': 'tickets'
                };
            var monthlySubmissionsTable = $('monthlySubmissionsTable').select('tbody')[0];
            monthlySubmissionsTable.observe('dblclick', this.setMonthlyUsageListener.bind(this));
            // set recalculate uploads
            $('recalculateUploads').observe('click', this.recalculateUploads.bind(this));
        }
        
        // add listener to remove user.
        if ($('removeFromScheduledDowngrade')){
        	$('removeFromScheduledDowngrade').observe("click", this.deleteFromScheduleDowngrade.bind(this));
        }
        
        // add listener to actiate user
        if ($('activateUser')){
        	$('activateUser').observe("click", this.activateUser.bind(this));
        }
        if ($('unsuspendUser')){
        	$('unsuspendUser').observe("click", this.unsuspendUser.bind(this));
        }
        if ($('suspendUser')){
        	$('suspendUser').observe("click", this.suspendUser.bind(this));
        }
        
        // add click username click observe to login
        $('loginUsername').observe('click', this.loginTo.bind(this));
		if (window.parent && window.parent.Admin && !window.parent.Admin.iframeEnlarged){
			window.parent.Admin.togglePanelSize();
        }
		// show page
		this.showPage();
	},
	activateUser: function (){
		this.request({
			action: "activateUser"
		}, function (res){
			$('status').removeClassName("warning");
			$('activateUser').remove();
			$('status').update("ACTIVE");
		});
	},
	unsuspendUser: function (){
		this.request({
			action: "unsuspendUser"
		}, function (res){
			$('status').removeClassName("warning");
			$('unsuspendUser').remove();
			$('status').update("ACTIVE");
		});
	},
	suspendUser: function (){
		this.request({
			action: "suspendUser"
		}, function (res){
			$('status').addClassName("warning");
			$('suspendUser').remove();
			$('status').update("SUSPENDED");
		});
	},
	// this function removes the user from the scheduled downgrade list.
	deleteFromScheduleDowngrade: function (){
		this.request({
			action: "deleteUserFromScheduledDowngrade"
		}, function (res){
			$('overlimitWarning').remove();
		});
	},
	loginTo: function(){
		if (window.parent && window.parent.Admin){
	        if (/^\d+$/.test(this.keyword)) {
		        window.parent.Admin.loginToUserAccount(this.username, function (){
		        	window.parent.document.createCookie('last_form', this.keyword) 
		        }.bind(this));
	        }else{
		        window.parent.Admin.loginToUserAccount(this.username, false);
	        }
		}else{
			alert("Not inside admin panel.");
		}
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
	recalculateUploads: function (){
		$('upload-size').update("wait...");
		this.request({
				action: "recalculateUploads"
			},
			function (res){
				$('upload-size').update(res.byte);
			}.bind(this)
		);
	},
    setMonthlyUsageListener: function(e){
        var el = e.element();
        var usageType = this.usageIdObj[el.id];
        var firstUsage = false;
        var inputToInsert = false;
        
        if (!usageType) {
            return; // Somewhere else in the table was clicked. Not interested.
        }
        if (el.select('input').length == 0) {
            // Make this editable.
            firstUsage = parseInt(el.innerHTML, 10);
            inputToInsert = new Element('input', {
                value: firstUsage,
                size: 2
            });
            el.update(inputToInsert);
            inputToInsert.focus();
            inputToInsert.select();
            inputToInsert.observe('change', this.setMonthlyUsage.bind(this));
            inputToInsert.observe('blur', function(e){
                var el = e.element();
                el.parentNode.innerHTML = el.value;
            });
        }
    },
    setMonthlyUsage: function(e){
        var el = e.element();
        var usageValue = el.value;
        var usageType = this.usageIdObj[el.parentNode.id];
        this.request({
                action: "setMonthlyUsage",
                usageType: usageType,
                usageValue: usageValue
            });
    },
	/**
	 * Status change functions: START
	 */
	startStatusChange: function (){
		this.request ({action:"getStatus"}, this.setStatusTypes.bind(this));
	},
	setStatusTypes: function (res){
        var select = new Element('select');
        var status = res.status;
        select.insert(new Element('option'));
        for (i = 0; i < status.length; i++) {
            select.insert(new Element('option', {
                value: status[i]
            }).update(status[i]));
        }
        select.observe("change", this.setStatus.bind(this, select));
		this.status.update(select);
	},
	setStatus: function (select){
		var selectedValue = select.options[select.selectedIndex].value;
		this.request({
            action: "setStatus",
            status: selectedValue
        }, function (res){
        	this.status.update(selectedValue);
        }.bind(this));
	},
	/**
	 * Status change functions: END
	 */
	
	/**
	 * Account type change functions: START
	 */
	startAccountTypeChange: function (){
		this.request ({action:"getAccountTypes"}, this.setAccountTypes.bind(this));
	},
	setAccountTypes: function (res){
        var select = new Element('select');
        var accountTypes = res.accountTypes;
        select.insert(new Element('option'));
        for (i = 0; i < accountTypes.length; i++) {
            select.insert(new Element('option', {
                value: accountTypes[i]
            }).update(accountTypes[i]));
        }
        select.observe("change", this.setAccountType.bind(this, select));
		this.accountType.update(select);
	},
	setAccountType: function (select){
		var selectedValue = select.options[select.selectedIndex].value;
		this.request({
            action: "setAccountType",
            accountType: selectedValue
        }, function (res){
        	this.accountType.update(selectedValue);
        }.bind(this));
	},
	/**
	 * Account type change functions: END
	 */
	
	request: function(params, onComplete){
		params.username = this.username;
        new Ajax.Request("../server.php", {
            parameters: params,
            evalJSON: 'force',
            onComplete: function (transport){
        		var res = transport.responseJSON;
        		if (res.success){
        			onComplete(res);
        		}else{
        			alert(res.error);
        		}
        	}
        });
	},
	showPage: function (){
		$('dimmer').hide();
		$('content').show();
	}
});




document.ready(function (){
	var scripts = document.getElementsByTagName("script");
	eval( scripts[ scripts.length - 1 ].innerHTML );
});