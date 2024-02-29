/**
 * Creates the condition wizard and controls all of it's actions
 */
var PendingWizard = {
    wiz: false,                     // Wizard window
    pendingSubmissions: false,      // 
    searchCross: false,
    submissions: false,
    current: false,
    
    /**
     * Initiate the wizard, translate strings and set events
     */
    initWizard: function(){        
        document._onedit = true;    // Set this to stop formbuilder from using key events, we need this for HTMLEditor
        Locale.changeHTMLStrings(); // translate
        var $this = this;           // Object scope
        this.setSearchForm();
        var template = new Template('<table height="100" width="100%" cellpadding="0" cellspacing="0">'+
            '<tr><td height="20"><div class="submission-date">#{date}</div></td></tr>'+
            '<tr><td height="" valign="top">'+
                '<div class="submission-content">#{content}</div>'+
            '</td></tr>'+
            '<tr><td class="button-cont">'+
                '<div class="submission-buttons">'+
                    '<button class="big-button buttons has-left-icon" onclick="PendingWizard.deletePending(\'#{id}\');"><img src="images/cross2.png" align="absmiddle"/> '+'Delete'.locale()+'</button>'+
                    '<button class="big-button buttons has-left-icon" onclick="PendingWizard.showDetails(\'#{id}\');"><img src="images/magnifier.png" align="absmiddle"/> '+'Details'.locale()+'</button>'+
                    '<button class="big-button buttons has-left-icon" onclick="PendingWizard.process(\'#{id}\');"><img src="images/go.png" align="absmiddle"/> '+'Complete Submission'.locale()+'</button>'+                                        
                '</div>'+
            '</td></tr>'+
        '</table>');
        
        
        if(!this.pendingSubmissions){
            Utils.Request({
                parameters:{
                    action:'getPendingSubmissions',
                    formID:Submissions.formID,
                    type:'PAYMENT'
                },
                onSuccess: function(res){
                    try {
                       $this.submissions = res.submissions;
                       $H($this.submissions).each(function(sub){
                            var li = new Element('li', {className:"pendings", id:sub.key});
                            var dump = $H(sub.value.questions).collect(function(a){ if (a.value.answer) { return a.value.answer.stripTags(); }
                            }).compact().join(' ');
                            li.update(template.evaluate({id: sub.key, date:sub.value.date, content:dump }));
                            $('pending-list').insert(li);
                        });
                    } catch (e) {
                    	console.error(e);
                    }
                }
            });
        }
    },
    /**
     * Initiate search functionality
     */
    setSearchForm: function(){
        var $this = this;
        $('search-pendings').observe('keyup', function(){
            $this.searchSubmissions($('search-pendings').value);
        });
        $('search-pendings').hint('Search In Submissions');
        this.searchCross = new Element('img', {src:'images/blank.gif', className:'index-cross'});
        this.searchCross.setStyle('position:absolute;bottom:5px;right:25px;cursor:pointer;');
        $('search-wrapper').insert(this.searchCross);
        this.searchCross.hide();
        this.searchCross.observe('click', function(){
            $('search-pendings').hintClear();
            $this.searchSubmissions();
        });
    },
    /**
     * Go find the results
     * @param {Object} keyword
     */
    searchSubmissions: function(keyword){
        
        if(!keyword){
            $$('.pendings').invoke('show');
            this.searchCross.hide();
            return;
        }
        this.searchCross.show();
        
        $$('.pendings').each(function(f){
            if (!f.innerHTML.toLowerCase().include(keyword.toLowerCase()) && !f.id.toLowerCase().include(keyword.toLowerCase())) {
                f.hide();
            } else {
                f.show();
            }
        });
    },
    /**
     * Displays the details of selected submission
     * @param {Object} id
     */
    showDetails: function(id){
        var data = '<table class="data-table" cellpadding="5" cellspacing="0" width="100%">';
        this.current = id;
        $H(this.submissions[id].questions).each(function(e){
            var line = e.value;
            
            if(!line.answer){ return; }
            
            data += '<tr><td width="150" class="data-question">';
            data += line.text;
            data += '</td><td class="data-answer">';
            data += line.answer.stripScripts();
            data += '</td></tr>';
        });
        data += '</table>';
        
        $('detail-pane').update(data);
        this.showPage('page2');
        this.showButtons(['close', 'back', 'complete']);
    },
    
    /**
     * Completes the pending submission
     * @param {Object} id
     */
    process: function(id){
        var $this = this;
        Utils.confirm('Are you sure you want to complete this payment?'.locale(), 'Complete Submission'.locale(), function(but, val){
            if(val){
                var old_content = $(id).innerHTML; 
                $(id).innerHTML = "<h2>"+'Processing Please Wait..'.locale()+"</h2>";
                Utils.Request({
                    parameters: {
                        action:'completePending',
                        id:id
                    },
                    onSuccess: function(res){
                        $(id).innerHTML = "<h2>"+'Completed'.locale()+"</h2>";
                        setTimeout(function(){
                            $this.current = false;
                            $(id).remove();
                            Submissions.getPendingCount();
                            Submissions.bbar.doRefresh();
                        }, 500);
                    },
                    onFail: function(res){
                        $(id).innerHTML = old_content;
                        Utils.alert('An error occured during submission'.locale());
                    }
                });
            }
        });
    },
    /**
     * Deletes the pending submission
     * @param {Object} id
     */
    deletePending: function(id){
        var $this = this;
        Utils.confirm('Are you sure you want to delete this payment?'.locale(), 'Delete Submission'.locale(), function(but, val){
            if(val){
                Utils.Request({
                    parameters: {
                        action:'deletePending',
                        id:id
                    },
                    onComplete: function(res){
                        if(!res.success){
                            Utils.alert(res.error.locale());
                            return;
                        }
                        $this.current = false;
                        $(id).remove();
                        Submissions.getPendingCount();
                    }
                });
            }
        });
    },
    /**
     * Decide what buttons to show
     * @param {Object} buttonList
     */
    showButtons: function(buttonList){
        $H(this.wiz.buttons).each(function(button){
            if(buttonList.include(button.key)){
                if(button.key == 'saved' && (!form.getProperty('conditions') || form.getProperty('conditions').length < 1) ){ return; /* continue; */ }
                button.value.show();
            }else{
                button.value.hide();
            }
        });
    },
    /**
     * Shows a page and hides the others
     * @param {Object} page
     */
    showPage: function(page){
        $('page1', 'page2').invoke('hide');
        $(page).show();
        this.wiz.reCenter();
    },

    /**
     * Create the wizard window
     */
    openWizard: function(){
        var $this = this;
        
        Utils.loadTemplate('wizards/pendingWizard.html', function(source) {
            
            var div = new Element('div').insert(source);
            
            $this.wiz = document.window({
                title: 'Pending Submissions'.locale(),
                width: 800,
                contentPadding: 0,
                content: div,
                dynamic: false,
                onInsert: $this.initWizard.bind($this),
                onClose: function(){
                    $this.editorSet = false;
                    $this.editMode = false;
                    document._onedit = false;
                    $this.selectedType = 'field';
                },
                buttons:[{
                    title: 'Back'.locale(),
                    name:'back',
                    hidden:true,
                    handler:function(){
                        $this.showPage('page1');
                        $this.showButtons('close');
                    }
                },{
                    title:'Complete Submission'.locale(),
                    name:'complete',
                    hidden:true,
                    handler: function(){
                        $this.process($this.current);
                        $this.showPage('page1');
                        $this.showButtons('close');
                    }
                },{
                    title:'Close'.locale(),
                    name:'close',
                    handler:function(w){
                        w.close();
                    }
                }]
            });
            
        });
    }
};

// On load
PendingWizard.openWizard();
