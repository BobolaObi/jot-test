var ReportWizard = {
    wiz: false,
    formID: false,
    selectedType: false,
    hasDateField: false,
    selectedFields: [],
    report: false,
    currentPage: "page1",
    password: false,
    initWizard: function(){
        var $this = this; 
        document._onedit = true;    // Set this to stop formbuilder from using key events
        
        Locale.changeHTMLStrings(); // translate
        
        // Check the questions if any of them are date fields
        this.hasDateField = $A(this.questions).collect(function(q){ return (q.type == 'control_datetime' || q.type == 'control_birthdate'); }).any();
        if (!this.hasDateField) {
            $('calendar-list').hide();
        }
        
        $$('.report-inputs').each(function(inp){
            inp.onclick = function(){
                $('error').update('&nbsp;');
                $$('.report-selected').invoke('removeClassName', 'report-selected');
                if(inp.checked){
                    $(inp.parentNode.parentNode).addClassName('report-selected');
                    $this.selectedType = inp.value;
                }
            }
        });
        
        $('report-checkall').onclick = function() {
            $$("#fieldlist input[type='checkbox']").each(function(el){
                el.checked = !$('report-checkall').checkall;    
            });
            $('report-checkall').checkall = !$('report-checkall').checkall;
        };
        
        $$('.result-fields').each(function(a){
            a.onclick = function(){
                a.select();
            }
        });
        this.selectedFields = this.getLastSelected();
        
        $('report-password').observe('click', function(){
            if($('report-password').checked){
                Utils.prompt('<div id="password-question">'+'Please enter a password for your report'.locale()+'</div>', 'Enter Password'.locale(), 'Choose Password'.locale(), function(value){
                    if(!value){
                        $('report-password').checked = false;
                        return;
                    }
                    
                    if(value.length > 6/* && /\d/.test(value) && /[A-Z]/.test(value) && /[-*!@#$%^&+=]/.test(value)*/){
                        this.password = value;
                    }else{
                        if(!$('error-prompted')){
                            $($('password-question').parentNode).insert('<div style="color:red; font-size:9px;" id="error-prompted">'+
                                '- Your password should be at least 6 chars long.<br>'+
                                //'- Must contain upper case letters [A-Z].<br>'+
                                //'- Must contain numbers.<br>'+
                                //'- Must contain special chars such as: "-*!@#$%^&+="'+
                            '</div>');
                        }
                        return false;
                    }
                }.bind(this));
            }else{
                this.password = "%%removepassword%%";
            }
        }.bind(this));
        
        if(this.report){
            if (this.report.type == 'cal') {
                
                var dateField; 
                var titleField;
                $A(this.report.configuration).each(function(f){
                    if(f.include("datetime")){
                        dateField = f.replace("datetime:", "");
                    }else if(f.include("title")){
                        titleField = f.replace("title:", "");
                    }
                });
                
                this.selectedFields = [dateField, titleField];                
            } else {
                this.selectedFields = this.report.configuration;
            }
            $('report-title').value = this.report.title;
            this.selectedType = this.report.type;          
            $$('.report-inputs[value="'+this.report.type+'"]')[0].click();
            
            if(this.report.hasPassword){
                $('report-password').checked = true;
            }
            
            this.nextPage();
        }        
    },
    
    showPage: function(page){
        $$('.report-pages').invoke('hide');
        $(page).show();
        this.currentPage = page;
    },
    
    saveLastSelected: function(){
        var name = this.formID+"_selected_fields";
        if("localStorage" in window){
            localStorage[name] = this.selectedFields.join(",");
        }else{
            document.createCookie(name, this.selectedFields.join(","), 365);
        }
    },
    
    getLastSelected: function(){
        var name = this.formID+"_selected_fields";
        if("localStorage" in window){
            return localStorage[name]? localStorage[name].split(",") : [];
        }else{
            return document.readCookie(name)? document.readCookie(name).split(",") : [];
        }
    },
    
    showButtons: function(buttonList){
        $H(this.wiz.buttons).each(function(button){
            if(buttonList.include(button.key)){
                button.value.show();
            }else{
                button.value.hide();
            }
        });
    },
    //
    nextPage: function() {
        var $this = this;
        switch(this.currentPage){
            case "page1":
                if (this.selectedType == 'cal') {
                    $('report-checkall').hide();
                }
                if(!this.selectedType){ $('error').update('Select a report type first'.locale()); return; }
                
                
                if(this.selectedType=='visual'){
                    document.eraseCookie('last_report');
                    Utils.redirect('page.php', {
                        parameters:{
                            p:'reports',
                            formID: this.formID
                        }
                    });
                    return;
                }
                
                if(this.selectedType != 'rss'){
                    $('ask-password').show();
                }else{
                    $('ask-password').hide();
                }
                
                if(!this.report){
                    var word = this.selectedType == "cal"? "Calendar".locale() : this.selectedType.ucFirst();
                    $('report-title').value = "%s Report".locale(word);
                }
                
                var additional = {
                    "ip": "IP Address",
                    "dt": "Submission Date"
                };
                
                var checkHTML = "";
                if(this.selectedType != 'cal'){
                    $H(additional).each(function(q, i){
                        var selected = ($A($this.selectedFields).include(q.key))? 'checked="checked"': '';
                        var style = (i%2 !== 1)? 'style="clear:left" ' : "";
                        
                        checkHTML += '<label class="form-fields" %s><input type="checkbox" %s value="%s" /> <span>%s</span></label>'.printf(style, selected, q.key, q.value);
                    });
                }
                var ftype = "checkbox";
                
                if(this.selectedType == 'cal'){
                    ftype = "radio";
                    checkHTML += '<b>' + 'Choose a Date Field:'.locale() + '</b><hr>';
                    var y =0;
                    $A(this.questions).each(function(q) {
                        
                        if(q.type != 'control_datetime' && q.type != 'control_birthdate'){ return; /* continue; */ }
                        
                        var selected = ($A($this.selectedFields).include(q.qid))? 'checked="checked"': '';
                        var style = (y%2 !== 1)? 'style="clear:left" ' : "";
                        if(!q.text){ q.text = "..."; }
                        checkHTML += '<label class="form-fields-cal-date" %s><input name="date-fields" type="%s" %s value="%s" /> %s</label>'.printf(style, ftype, selected, q.qid, q.text.shorten(35));
                        y++;             
                    });
                    
                    checkHTML += '<br><br><b style="float:left;">' + 'Choose a Title:'.locale() + '</b><br><hr>';
                }
                
                var x = 0;
                $A(this.questions).each(function(q) {
                    
                    if($this.selectedType == "cal" && (q.type == 'control_datetime' || q.type == 'control_birthdate')){ return; /* continue; */ }
                    
                    var selected = ($A($this.selectedFields).include(q.qid))? 'checked="checked"': '';
                    var style = (x%2 !== 1)? 'style="clear:left" ' : "";
                    if(!q.text){ q.text = "..."; }
                    checkHTML += '<label class="form-fields" %s><input name="fields" type="%s" %s value="%s" /> %s</label>'.printf(style, ftype, selected, q.qid, q.text.shorten(35));
                    x++;
                });
                
                $('fieldlist').update(checkHTML);

                this.showPage('page2');
                this.showButtons(['backPage', 'nextPage']);
            break;
            case "page2":
                $('field-error').update();
                
                if(!$('report-title').value){
                    $('field-error').update('You should enter a title for your report'.locale());
                    $('report-title').focus();
                    return;
                }
                
                if(this.selectedType == 'cal' && !$$('.form-fields-cal-date input').collect(function(f){ return f.checked; }).any()){
                    $('field-error').update('You should select at least one date field'.locale());
                    return;
                }
                
                var warning = "column";
                if(this.selectedType == 'cal') {
                    warning = "title"; 
                }
                
                if(!$$('.form-fields input').collect(function(f){ return f.checked; }).any()){
                    $('field-error').update(('You should select at least one ' + warning).locale());
                    return;
                }
                
                if(this.selectedType == 'grid' || this.selectedType == 'cal' || this.selectedType == 'table'){
                    $('iframe-container').show();
                }else{
                    $('iframe-container').hide();
                }
                
                if (this.selectedType != 'cal') {
                    this.selectedFields = $$('.form-fields input').collect(function(f){
                        if (f.checked) {
                            return f.value;
                        }
                    }).compact();
                } else {
                    var dateField = $$('.form-fields-cal-date input').collect(function(f){
                        if (f.checked) {
                            return f.value;
                        }
                    }).compact();
                    var titleField = $$('.form-fields input').collect(function(f){
                        if (f.checked) {
                            return f.value;
                        }
                    }).compact();
                    this.selectedFields = ['datetime:' + dateField[0], 'title:' + titleField[0]];
                }
                
                Utils.Request({
                    parameters: {
                        action:   this.report? 'updateListing' : 'createListing',
                        title:    $('report-title').value,
                        fields:   this.selectedFields.join(','),
                        type:     this.selectedType,
                        formID:   this.formID,
                        listID:   this.report? this.report.id : false,
                        password: this.password
                    },
                    onSuccess: function(res){
                        var url = Utils.HTTP_URL + "" + (this.selectedType=="cal"? "calendar" : this.selectedType) + '/' + res.id;
                        $('report-url').value = url;
                        $('report-iframe').value = "<"+'iframe src="'+url+'" frameborder="0" style="width:100%; height:100%; min-height:500px; border:none;" scrolling="'+ (this.selectedType=="table"? "auto" : "no") +'"></'+"iframe>";
                        this.showPage('page3');
                        this.showButtons(['finish']);
                        
                        var newConf = {
                            "id": res.id,
                            "title": $('report-title').value,
                            "configuration": this.selectedFields,
                            "type": this.selectedType,
                            "hasPassword": !(!this.password || this.password == '%%removepassword%%')  
                        };
                        
                        if(this.report){
                            $A(MyForms.forms[this.formID].reports).each(function(r, i){
                                if(r.id == $this.report.id){
                                    MyForms.forms[$this.formID].reports[i] = newConf;
                                }
                            });
                        }else{
                            MyForms.forms[this.formID].reports.push(newConf);
                        }
                        if (this.selectedType != 'cal') {
                            this.saveLastSelected();
                        }
                    }.bind(this),
                    /**
                     * On fail
                     * @param {Object} res
                     */
                    onFail: function(res){
                        Utils.alert(res.error);
                    }
                })
            break;
        }
    },
    
    backPage: function(){
        switch(this.currentPage){
            case "page3":
                this.showPage('page2');
                this.showButtons(['backPage', 'nextPage']);
            break;
            case "page2":
                this.showPage('page1');
                this.showButtons(['nextPage']);
            break;
        }
    },
    
    finishWizard: function(){
        this.wiz.close();
    },
    
    openWizard: function(report){
        this.formID = MyForms.getSelectedID();
        var $this = this;
        
        if(report){
            this.report = report;
        }
        
        Utils.Request({
            parameters:{
                action:'getQuestions',
                onlyDataFields: true,
                formID: this.formID
            },
            onComplete: function(res){
                
                $this.questions = res.questions;
                
                Utils.loadTemplate('wizards/reportWizard.html', function(source){
                
                    var div = new Element('div').insert(source);
                    
                    $this.wiz = document.window({
                        title: 'Reports Wizard'.locale(),
                        width: 600,
                        contentPadding: 0,
                        content: div,
                        dynamic: false,
                        onInsert: $this.initWizard.bind($this),
                        onClose: function(){
                            document._onedit = false;
                            $this.selectedType = false;
                            $this.currentPage = 'page1';
                            $this.report = false;
                            $this.password = false;
                        },
                        buttons: [{
                            title:'Back'.locale(),
                            name:'backPage',
                            handler: $this.backPage.bind($this),
                            hidden:true
                        },{
                            title: 'Next'.locale(),
                            name: 'nextPage',
                            handler: $this.nextPage.bind($this)
                        },{
                            title: 'Finish'.locale(),
                            name: 'finish',
                            hidden: true,
                            handler: $this.finishWizard.bind($this)
                        }]
                    });
                });
            }       
        });
    }
};

ReportWizard.openWizard(Utils.useArgument);