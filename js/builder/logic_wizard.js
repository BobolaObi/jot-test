/**
 * Creates the condition wizard and controls all of it's actions
 */
var LogicWizard = {
    wiz: false,                     // Wizard window
    selectedType: 'field',          // Main condition types which user selects on the first page, ('field', 'page', 'action')
    activeLine: false,              // Currently selected condition
    activeTermList: false,          // Currently selected terms list dropdown
    activeCondValue: false,         // Currently selected condition value
    activeFieldList: false,         // Currently selected field list
    activeDelButton: false,         // Currently selected lines delete button
    editMode: false,                // Inticates that we are currently editing a condition, so we can change the behaviour of back and save button
    currentIndex: false,            // Array Index of currently editing condition, we need this to replace condition in the conditions list 
    editorSet: false,               // Defines if the HTMLEditor created, we need this to prevent double creation
    terms: [{                       // Term to use in the dropdown according to the field type
        text: "Equals To".locale(), // Visual represantation of a term
        value: 'equals',            // programatic representation
        type: ['exact']             // to define which type of term is this
    }, {
        text: 'Not Equals To'.locale(),
        value: 'notEquals',
        type: ['exact']
    }, {
        text: 'Contains'.locale(),
        value: 'contains',
        type: ['partial']
    }, {
        text: 'Does not Contain'.locale(),
        value: 'notContains',
        type: ['partial']
    }, {
        text: 'Starts With'.locale(),
        value: 'startsWith',
        type: ['partial']
    }, {
        text: 'Ends With'.locale(),
        value: 'endsWith',
        type: ['partial']
    }, {
        text: 'Is Empty'.locale(),
        value: 'isEmpty',
        type: ['exact', 'value_check']
    }, {
        text: 'Is Filled'.locale(),
        value: 'isFilled',
        type: ['exact', 'value_check']
    }, {
        text: 'Less Than'.locale(),
        value: 'lessThan',
        type: ['numeric']
    }, {
        text: 'Greater Than'.locale(),
        value: 'greaterThan',
        type: ['numeric']
    }, {
        text: 'Before'.locale(),
        value: 'before',
        type: ['date']
    }, {
        text: 'After'.locale(),
        value: 'after',
        type: ['date']
    }],
    /**
     * Initiate the wizard, translate strings and set events
     */
    initWizard: function(){
        
        document._onedit = true;    // Set this to stop formbuilder from using key events, we need this for HTMLEditor
        Locale.changeHTMLStrings(); // translate
        var $this = this;           // Object scope
        
        // Set selected condition type
        $$('#condition-types input[name="cond"]').invoke('observe', 'click', function(e){
            
            $(e.target.parentNode.parentNode).select('.active-line').invoke('removeClassName', 'active-line');
            
            $(e.target.parentNode).addClassName('active-line');
            
            $this.selectedType = e.target.value;
        });
    },
    /**
     * create the terms dropdown by selecting the correct options for correct types
     * @param {Object} types
     */
    createTerms: function(types) {
        var $this = this;
        var dropDown = this.activeTermList;
        
        $this.activeCondValue.hide();
        dropDown.update(new Element('option', {value: 'none', selected: true}).insert('Please select'.locale()));
        
        this.terms.map(function(node){
            for (var i = 0; i < types.length; i++) {
                if (node.type.include(types[i])) {
                    return node;
                }
            }
        }).compact().each(function(option){
            dropDown.insert(new Element('option', {
                value: option.value
            }).insert(option.text));
        });
        
        dropDown.observe('change', function(){
            if(dropDown.value == 'isEmpty' || dropDown.value == 'isFilled' || dropDown.value == 'none'){
                $this.activeCondValue.hide();
            }else{
                $this.activeCondValue.show();
            }
        });
        
        dropDown.bigSelect();
    },
    /**
     * Set the active line
     * @param {Object} line
     */
    setActiveLine: function(line){
        
        if(this.activeLine){ this.activeLine.removeClassName('active-line'); }
        
        var field = line || $$('.cond-line')[0];
        this.activeLine      = field;
        this.activeTermList  = this.activeLine.select('.terms-list')[0];
        this.activeCondValue = this.activeLine.select('.cond-value')[0];
        this.activeFieldList = this.activeLine.select('.field-list')[0];
        this.activeValueInput = this.activeLine.select('.value-input')[0];
        
        this.activeDelButton = this.activeLine.select('.cond-del-button')[0];
        
        this.activeLine.addClassName('active-line');
        
        this.activeLine.onmousedown = function(){
            if(field.hasClassName('errored-line')){
                field.removeClassName('errored-line'); 
            }
            if(this.activeLine != field){
                this.setActiveLine(field);
            }
        }.bind(this);
        
    },
    /**
     * Make a validation for codition line
     */
    validateLines: function(){
        var valid=true;
        
        $$('.cond-line').each(function(line){
            if(!valid){ return; }
            line.removeClassName('errored-line');
            
            if(line.select('.field-list')[0].value == 'none'){
                line.addClassName('errored-line');
                valid = false;
                return;
            }
            
            if(line.select('.terms-list')[0].value == 'none'){
                line.addClassName('errored-line');
                valid = false;
                return;
            }
            
            if(!['isFilled', 'isEmpty'].include(line.select('.terms-list')[0].value) && !line.select('.value-input')[0].value){
                line.addClassName('errored-line');
                valid = false;
                return;
            }        
        });
        
        return valid;
    },
    /**
     * Create the (and | all) link select dropdown,
     * only show it when there are multiple conditions
     */
    handleLinkSelect: function(){
        if($$('.cond-line').length > 1){
            $('cond-single').hide();
            $('cond-multiple').show();
            $('cond-link').bigSelect({width:'4em'});
        }else{
            $('cond-single').show();
            $('cond-multiple').hide();
        }
    },
    /**
     * Create a new line
     */
    addNewLine: function(){
        if(this.validateLines()){
            if(!this.activeLine){
                this.setActiveLine();
            }
            var newLine = this.activeLine.cloneElem();
            
            $('cond-list').insert(newLine);
            newLine.select('.big-select').invoke('remove');
            newLine.shift({
                backgroundColor: '#ffffa0',
                delay: 0.5,
                easing: 'pulse',
                easingCustom: 3,
                duration: 2,
                onEnd: function(e){
                    e.setStyle({backgroundColor: ''});
                }
            });
            this.createConditionForm(newLine);
            this.handleLinkSelect();
        }
    },
    /**
     * Delete the current condition
     * @param {Object} e
     */
    deleteLine: function(e){
        
        if($$('.cond-line').length < 2){
            this.createConditionForm();
        }else{
            var line = $(e.target).up('.cond-line');
            if(line == this.activeLine){
                this.activeLine = false;
            }
            line.remove();
        }
        this.handleLinkSelect();
    },
    /**
     * Create the action area according to the selection on the first page
     */
    createActionSection: function(action){
        $('action-'+this.selectedType).show();
        
        if(this.selectedType == 'field'){
            $('show-hide').bigSelect({width:'6em'});
            var items = getAllElements();
            $('field-list').update('<option value="none">'+'Please Select'.locale()+'</option>');
            items.each(function(el){
                $('field-list').insert(new Element("option", {
                    value: el.getProperty('qid')
                }).insert(el.getProperty('text').stripTags().replace(/\&nbsp;/gim, '').shorten(20)));
            });
            $('field-list').bigSelect();
            if(action){
                $('show-hide').selectOption(action.visibility);
                $('field-list').selectOption(action.field);
            }
        }else if(this.selectedType == 'page'){
            var pages = getElementsByType('control_pagebreak');
            $('page-list').update('<option value="none">'+'Please Select'.locale()+'</option>');
            if(pages.length > 0){
                $('page-list').insert(new Element('option', {value:'page-1'}).insert('Page %d'.locale(1)));
                pages.each(function(p, i){
                    $('page-list').insert(new Element('option', {value:'page-'+(i+2)}).insert('Page %d'.locale(i+2)));
                });
            }
            $('page-list').bigSelect();
            if(action){
                $('page-list').selectOption(action.skipTo);
            }
        }else if(this.selectedType == 'email'){
            
            var emails = form.getProperty('emails');
            $('email-list').update('<option value="none">'+'Please Select'.locale()+'</option>');
            if(emails){
                emails.each(function(p, i){
                    $('email-list').insert(new Element('option', {value:'email-'+i}).insert(p.name));
                });
                
                $('email-list').onchange = function(){
                    $('email-to').value = emails[$('email-list').value.replace('email-', '')].to;
                };
            }
            
            $('email-list').bigSelect();
            if(action){
                $('email-list').selectOption(action.email);
                $('email-to').value = action.to;
            }
        }else if(this.selectedType == 'message'){
            var defaultMessage = "Thank you for your submission".locale();
            
            if(!this.editorSet){
                this.editorSet = true;
                if(action){
                    $('cond-message').value = action.message;
                }else{
                    $('cond-message').value = defaultMessage;
                }
                Editor.set('cond-message');
            }else{
                if(action){
                    Editor.setContent('cond-message', action.message);
                }else{
                    Editor.setContent('cond-message', defaultMessage);
                }
            }
        }else if(this.selectedType == 'url'){
            if(action){
                $('cond-url').value = action.redirect;
            }
        }    

    },
    /**
     * Convert a line ino usable condition box
     * @param {Object} el
     */
    createConditionForm: function(el){
        this.setActiveLine(el);
        var items = getUsableElements('condition');
        
        var valueInput;
        // Clean the default values for recreation
        this.activeTermList.update();
        this.activeCondValue.update();
        this.activeFieldList.update();
        
        var $this = this;
        
        $this.activeDelButton.observe('click', $this.deleteLine.bind(this));
        
        
    	$this.activeFieldList.insert(new Element('option', {
    		value: 'none',
    		selected: true
    	}).insert('Please select'.locale()));

    	items.each(function(el){
    		$this.activeFieldList.insert(new Element("option", {
                value: el.getProperty('qid')
            }).insert(el.getProperty('text').stripTags().replace(/\&nbsp;/gim, '').shorten(20)));
        });
    	
    	$this.activeFieldList.observe('change', function() {
            try {
            
                if ($this.activeFieldList.value == 'none') {
                    return;
                }
                var el = getElementById($this.activeFieldList.value);
                var options;
                switch (el.getProperty('type')) {
                    case 'control_checkbox':
                    case 'control_radio':
                    case 'control_dropdown':
                        $this.createTerms(['exact']);

                        valueInput = new Element('select', {className:'value-input'});
                        
                        $this.activeCondValue.update($this.activeValueInput = valueInput);
                        
                        if (el.getProperty('special') != 'None') {
                            options = special_options[el.getProperty('special')].value;
                        }else{
                            options = el.getProperty('options').split('|');
                        }
                        $A(options).each(function(op){
                            valueInput.insert(new Element('option', {value:op}).insert(op));
                        });
                        valueInput.bigSelect();
                        break;
                    case 'control_spinner':
                    case 'control_slider':
                    case 'control_rating':
                    case 'control_scale':
                    case 'control_number':
                        $this.activeCondValue.update($this.activeValueInput = new Element('input', {type: 'text', size: 5,className:'value-input'}));
                        $this.createTerms(['exact', 'numeric']);
                        break;
                    case 'control_range':
                    case 'control_fileupload':
                    case 'control_fullname':
                    case 'control_address':
                    case 'control_phone':
                        $this.createTerms(['value_check']);
                        break;
                    case "control_payment":
                    case 'control_paypal':
                    case 'control_paypalpro':
                    case 'control_clickbank':
                    case 'control_2co':
                    case 'control_worldpay':
                    case 'control_googleco':
                    case 'control_onebip':
                    case 'control_authnet':
                        $this.createTerms(['exact']);
                        
                        valueInput = new Element('select', {className:'value-input'});

                        $this.activeCondValue.update($this.activeValueInput = valueInput);
                        
                        options = $A(form.getProperty('products')).collect(function(p){ return [p.pid, p.name]; });
                        
                        $A(options).each(function(op){
                            valueInput.insert(new Element('option', {value:op[0]}).insert(op[1]));
                        });
                        
                        valueInput.bigSelect();
                        break;
                    case 'control_datetime':
                    case 'control_birthdate':
                        $this.activeCondValue.update($this.activeValueInput = new Element('input', {type: 'text',className:'value-input'}));
                        $this.activeValueInput.value = (new Date()).toString("yyyy-MM-dd");
                        $this.createTerms(['value_check', 'date']);
                        break;
                    default:
                        $this.activeCondValue.update($this.activeValueInput = new Element('input', {type: 'text',className:'value-input'}));
                        $this.createTerms(['exact', 'partial']);
                }
                
            } 
            catch (e) {
                console.error(e);
            }

    	});
    	
    	$this.activeFieldList.bigSelect();
        $this.activeTermList.bigSelect();
    },
    /**
     * 
     * @param {Object} w
     */
    nextPage: function(w){
        this.createConditionForm();
        this.createActionSection();
        // this.wiz.setStyle('width:605px');
        this.showButtons(['addnew', 'backPage', 'finish']);
        this.showPage('page2');
    },
    /**
     * 
     * @param {Object} w
     */
    backPage: function(w){
        if(this.editMode){
            this.clearConditionForm();
            this.createSavedConditionsList();
            this.editMode = false;
            this.showPage('page3');
        }else{
            $('action-'+this.selectedType).hide();
            $$('#cond-action .active-line').invoke('removeClassName', 'errored-line');            
            this.clearConditionForm();
            // this.wiz.setStyle('width:450px');
            this.showButtons(['saved', 'nextPage']);
            this.showPage('page1');
        }
    },
    /**
     * 
     */
    validateActions: function(){
        
        $$('#cond-action .active-line').invoke('removeClassName', 'errored-line').each(function(l){
            l.onmousedown = function(){
                l.removeClassName('errored-line');
            };
        });
        
        var markErrors = function(){
            $$('#cond-action .active-line').invoke('addClassName', 'errored-line');
        };
        
        switch(this.selectedType){
            case "field":
                if($('field-list').value == 'none'){
                    markErrors();
                    return false;
                }
            break;
            case "page":
                if($('page-list').value == 'none'){
                    markErrors();
                    return false;
                }
            break;
            case "email":
                if($('email-list').value == 'none' || (!$('email-to').value.include('{') && !Utils.checkEmailFormat($('email-to').value))){
                    markErrors();
                    return false;
                }
            break;
            case "url":
                if($('cond-url').value.length < 5){
                    markErrors();
                    return false;
                }
            break;
            case "message":
                if(!Editor.getContent('cond-message').stripTags().strip().replace(/\&nbsp;/gim, '')){
                    markErrors();
                    return false;
                }
            break;
        }
        return true;
    },
    /**
     * 
     * @param {Object} w
     */
    finishWizard: function(w){
        
        if (!this.validateLines() || !this.validateActions()) { return; }
        
        var condition = {
            type: this.selectedType,
            link: $('cond-link').value,
            terms: [],
            action: {}
        };
        var action = condition.action;
        
        switch(this.selectedType){
            case "field":
                action.field = $('field-list').value; 
                action.visibility = $('show-hide').value;
            break;
            case "page":
                action.skipTo = $('page-list').value;
            break;
            case "email":
                action.email= $('email-list').value;
                action.to = $('email-to').value;
            break;
            case "url":
                action.redirect = $('cond-url').value;
            break;
            case "message":
                action.message = Editor.getContent('cond-message');
            break;
            
        }
        
        $$('.cond-line').each(function(line){
            var cfield, coperator, cvalue; 
            cfield = line.select('.field-list')[0].value;
            
            coperator = line.select('.terms-list')[0].value;
            
            if(!['isFilled', 'isEmpty'].include(line.select('.terms-list')[0].value)){
                cvalue = line.select('.value-input')[0].value;
            }else{
                cvalue = false;
            }
            
            condition.terms.push({
                field: cfield,
                operator: coperator,
                value: (cvalue.strip? cvalue.strip() : cvalue)
            });
        });
        
        var conds = form.getProperty('conditions');
        
        if(this.editMode){
            conds.splice(this.currentIndex, 1, condition);
            this.backPage();
            onChange('Condition updated');
        }else{
            if(!conds){ conds = []; }
            conds.push(condition);
            form.setProperty('conditions', conds);
            onChange('Condition Added');
            this.editMode = true;
            this.backPage();
        }
    },
    /**
     * 
     */
    clearConditionForm: function(){
        $$('.cond-line').each(function(l, i){
            if(i>0){ l.remove(); }
        });
        this.handleLinkSelect();
        $$('.action-pages').invoke('hide');
    },
    /**
     * 
     * @param {Object} index
     */
    deleteCondition: function(index){
        var conds = form.getProperty('conditions');
        conds.splice(index, 1);
        this.createSavedConditionsList();
        onChange("Condition Deleted");
    },
    /**
     * 
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
     * 
     * @param {Object} page
     */
    showPage: function(page){
        $('page1', 'page2', 'page3').invoke('hide');
        $(page).show();
        this.wiz.reCenter();
    },
    /**
     * 
     * @param {Object} cond
     * @param {Object} index
     */
    editCondition: function(cond, index){
        this.currentIndex = index;  // Set index
        this.editMode = true;       // Set the editor in edit mode
        this.clearConditionForm();
        this.selectedType = cond.type;
        
        this.createActionSection(cond.action);
        var $this = this;
        var n = false;
        $A(cond.terms).each(function(term){
            if(n){
                $this.addNewLine();
            }else{
                $this.createConditionForm();
                n = true;
            }
            $this.activeFieldList.selectOption(term.field);
            $this.activeTermList.selectOption(term.operator);
            if($this.activeValueInput){
                if($this.activeValueInput.tagName == 'INPUT'){
                    $this.activeValueInput.value = term.value;
                }else{
                    $this.activeValueInput.selectOption(term.value);
                }                        
            }
        });
        $('cond-link').selectOption(cond.link);
        this.showButtons(['addnew', 'finish', 'backPage']);
        // this.wiz.setStyle('width:605px');
        this.showPage('page2');
    },
    /**
     * 
     */
    addCondition: function(){
        $('field-cond').checked = true;
        $('field-cond').run('click');
        this.editMode = false;
        this.showButtons(['saved', 'nextPage']);
        this.showPage('page1');
    },
    /**
     * 
     */
    createSavedConditionsList: function(){
        
        this.showButtons(['addCondition', 'close']);
        
        var $this = this;
        var conds = form.getProperty('conditions');
        if(conds.length < 1){
            $('saved-cond-list').update(
                '%s No Condition Here? %s Would you like to %s add a new one? %s'.locale(
                   '<div style="padding:20px;">',
                   '<br>',
                   '<a href="javascript:void(LogicWizard.addCondition())">',
                   '</a> </div>'
                )
            );
            $this.showPage('page3');
            return;
        }
        
        $('saved-cond-list').update();
        
        $A(conds).each(function(cond, index){
            
            var li = new Element('li', {className:'active-line saved-conds'});
            var span = new Element('span');
            
            var t = cond.terms[0];
            var termText = "";
            if(getElementById(t.field)){
                termText = 'When "%s" %s %s'.locale(
                    getElementById(t.field).getProperty('text'),
                    $A($this.terms).map(function(term){ if(term.value == t.operator){ return term.text; } }).compact()[0],
                    t.value? '"'+t.value+'"' : ''
                );
            }else{
                termText = 'This field is deleted'; 
            }
            if(cond.terms.length > 1){
                termText += '<br><i>'+'Also %s more..'.locale(cond.terms.length-1)+'</i>';
            }
            
            switch(cond.type){
                case "field":
                    if(getElementById(cond.action.field)){
                        span.insert(''+ cond.action.visibility.toUpperCase() +' '+ getElementById(cond.action.field).getProperty('text') + ' ' + 'field'.locale() +'.');
                    }else{
                        span.insert('This field is deleted.');
                    }
                break;
                case "page":
                    span.insert(''+'Skip to'.locale() + ' ' + cond.action.skipTo +'.');
                break;
                case "email":
                    var emails = form.getProperty('emails');
                    if(!emails[cond.action.email.replace('email-', '')]){
                        delete conds[index]; 
                        return;
                    }
                    var emailName = emails[cond.action.email.replace('email-', '')].name;
                    span.insert(''+'Send "%s" email to "%s".'.locale(
                        emailName,
                        cond.action.to
                    )+'');
                    termText += '<br><span style="color:red; font-size:11px;">'+'Note: This email will not be sent if the conditions are not matched'.locale()+'</span>';
                break;
                case "url":
                    span.insert(''+'Redirect to'.locale() + ' ' + cond.action.redirect +'.');
                break;
                case "message":
                    span.insert(''+'Display message'.locale()+' "'+ cond.action.message.stripTags().shorten(100)+'".');
                break;
            }
            
            var editCond = function(e){
                $this.editCondition(cond, index);
            };
            
            var deleteCond = function(){
                $this.deleteCondition(index);
            };
            
            
            
            var table, tbody, tr, td, td2;
            table = new Element('table', {cellpadding:0, cellspacing:0, border:0, width:'100%'});
            table.insert(tbody = new Element('tbody'));
            tbody.insert(tr = new Element('tr'));
            tr.insert(td = new Element('td'));
            tr.insert(td2 = new Element('td', {width:20, valign:'top', className:'buttons-td'}));
            
            
            var editImg = new Element('img', {src:'images/pencil.png'}).observe('click',function(e){ editCond(e); });
            var deleteImg = new Element('img', {src:'images/delete.png'}).observe('click',function(){ deleteCond(); });
            td2.insert(editImg);
            td2.insert(deleteImg);
            
            span.insert('<span class="term-text">'+termText+'</span>');
            td.insert(span).observe('click',function(e){ editCond(e); });
            
            li.insert(table);
            $('saved-cond-list').insert(li);
        });
        
        $this.showPage('page3');
    },
    /**
     * Create the wizard window
     */
    openWizard: function(){
        var $this = this;
        
        $('logic-img').src = 'images/loader-big.gif';
        $('logic-img').removeClassName('toolbar-cond');
        Utils.loadCSS("wizards/css/logicWizard.css");
        Utils.loadTemplate('wizards/logicWizard.html', function(source) {
            
            var div = new Element('div').insert(source);
            
            $('logic-img').src = 'images/blank.gif';
            $('logic-img').addClassName('toolbar-cond');

            $this.wiz = document.window({
                title: 'Conditions Wizard'.locale(),
                width: 605,
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
                    title:'Add New Rule'.locale(),
                    name:'addnew',
                    align:'left',
                    hidden:true,
                    handler:function(w){
                        $this.addNewLine();
                    }
                },{
                    title:'Add New Condition'.locale(),
                    name:'addCondition',
                    align:'left',
                    hidden:true,
                    handler:function(w){
                        $this.addCondition(w);
                    }
                },{
                    title:'Saved Conditions'.locale(),
                    name:'saved',
                    align:'left',
                    hidden: !form.getProperty('conditions') && !(form.getProperty('conditions').length > 0),
                    handler:$this.createSavedConditionsList.bind($this)
                },{
                    title:'Back'.locale(),
                    name:'backPage',
                    hidden:true,
                    handler:$this.backPage.bind($this)
                },/*{
                    title:'Cancel',
                    name:'cancel',
                    link:true,
                    handler: function(w){
                        w.close();
                    }
                },*/{
                    title:'Next'.locale(),
                    name:'nextPage',
                    handler:$this.nextPage.bind($this)
                },{
                    title:'Save'.locale(),
                    name:'finish',
                    hidden:true,
                    handler:$this.finishWizard.bind($this)
                }, {
                    title:'Close'.locale(),
                    name:'close',
                    hidden:true,
                    handler:function(w){
                        w.close();
                    }
                }]
            });
            
        });
    }
};

// On load
LogicWizard.openWizard();
