/** 
 * New Jotform.js
 * Jotform Core 3.0
 * @version 3.0 
 */

var Utils = Utils || new Common();
var formID = false;
var savedform = {
    "form_title": "Untitled Form".locale(),
    "form_style": "Default",
    "form_alignment": "Left",
    "form_labelWidth": "150",
    "form_formWidth": "650",
    "form_theme": "Default",
    "form_background": "",
    "form_font": "Verdana",
    "form_fontsize": "12",
    "form_fontcolor": "Black",
    "form_header": "",
    "form_footer": "",
    "form_thankurl": "",
    "form_thanktext": "",
    "form_sendpostdata": "No",
    "form_sendEmail": "Yes"
};

var selectEvent = ('createTouch' in document)? 'touchstart' : 'mousedown';
var noAutoSave = false;
var undoStack = [];             // Undo array
var redoStack = [];             // Redo array
var changeFlag = false;
var formBuilderTop = 107;       // Actual value is in CSS 
var toolBoxTop = 175;
var flips_are_added = false;
var leftFlip, rightFlip;
var qid = 0;                    // Set Question id
var selected = false;           // Selected question of builder
var toolboxContents = {};       // Keep the toolbox content in temp value in order to place it back
var lastChange = {};            // Last change made to keep undo
var initialForm = {};           // initial state of form
var form = false;               // form itself
var formProps = false;          // form properties
var lastStyle = "form";
var lastTool = false;
var pt = Protoplus.Profiler;
var optionsCache = {};
var saving = false;
var showInfo = false;
var stopUnselect = false;
var submissionCounts={};

function getSavedForm(config, forceNew){
    if (config.success === true) {
        console.log("getFormProperties took " + config.duration + " on the server");
        savedform = config.form;
        submissionCounts = config.submissions;
        formID = savedform.form_id || false;
        if (forceNew === true) {
            formID = false;
        }
        // Get the maximum ID if NaN take 0;
        qid = getMaxID(savedform) || 0; //0;  // Question ID counter.
    } else {
        if (config.error == 'New Form') {
            showInfo = true;
            return;
        } // Create a new Form
        //Utils.alert("Form Not Found, continuing with a new form", "Error");
        //Utils.alert(config.error, "Error");
    }
}


function getCloneForm(config){
    getSavedForm(config, true);
    delete savedform.form_id;
}

/**
 * If there is a saved form then take max id for it.
 */
if ($H(savedform).keys().length > 0) {
    // Get the maximum ID if NaN take 0;
    qid = getMaxID(savedform) || 0; //0;  // Question ID counter.
}

/**
 * Get the max id from form properties
 * @param {Object} form_prop
 */
function getMaxID(form_prop){
    form_prop = form_prop ? form_prop : getAllProperties();
    var arr = $H(form_prop).map(function(p){
        if (p.key.match("qid")) {
            return p.value;
        }
    }).compact();
    if (arr.length < 1) {
        arr = [0];
    }
    return (Math.max.apply(Math, arr));
}

/**
 * Ask user for confirmation if anything changed on the page
 */
window.onbeforeunload = function(){
    if (changeFlag) {
        return "You have unsaved changes: Are you sure you want to discard them?".locale();
    }
};

/**
 * In order to prevent memory leaks
 */
window.onunload = function(){
    // Clear all freaking stuff here
    undoStack = undefined;
    redoStack = undefined;
    optionsCache = undefined;
    leftFlip = undefined;
    rightFlip = undefined;
    qid = undefined;
    selected = undefined;
    toolboxContents = undefined;
    lastChange = undefined;
    initialForm = undefined;
    CommonClass = undefined;
    Utils = undefined;
};

document.keyboardMap({
    "Up": { // Select previous question
        handler: function(e){
        
            Event.stop(e);
            if (selected) {
                if (selected.previousSibling) {
                    
                    $(selected.previousSibling).run(selectEvent).scrollInto();
                }
                return false;
            } else {
                ($($('list').lastChild) && $($('list').lastChild).run(selectEvent).scrollInto());
            }
        },
        disableOnInputs: true
    },
    "Down": { // Select next question
        handler: function(e){
        
            Event.stop(e);
            if (selected) {
                if (selected.nextSibling) {
                    $(selected.nextSibling).run(selectEvent).scrollInto();
                }
                return false;
            } else {
                ($($('list').firstChild) && $($('list').firstChild).run(selectEvent).scrollInto());
            }
        },
        disableOnInputs: true
    },
    "Left": { // Move question up
        handler: function(){
        
            if (selected) {
                var tmp = selected;
                var sibling = $(selected.previousSibling);
                if (!sibling) {
                    return;
                }
                $(selected.parentNode).replaceChild(selected.previousSibling, selected);
                sibling.insert({
                    before: tmp
                });
                onChange("Question moved");
                return false;
            }
        },
        disableOnInputs: true
    },
    "Right": { // Move question down
        handler: function(){
        
            if (selected) {
                var tmp = selected;
                var sibling = $(selected.nextSibling);
                if (!sibling) {
                    return;
                }
                $(selected.parentNode).replaceChild(selected.nextSibling, selected);
                sibling.insert({
                    after: tmp
                });
                onChange("Question moved");
                return false;
            }
        },
        disableOnInputs: true
    },
    "Delete": { // Delete selected question
        handler: function(){
        
            if (selected) {
                removeQuestion(selected, selected.getReference('elem'));
                return false;
            }
        },
        disableOnInputs: true
    },
    "Backspace": {
        handler: function(e){
        
            Event.stop(e);
            return false;
        },
        disableOnInputs: true
    },
    "Meta+S": { // Save form for MACs
        handler: function(e){
            Event.stop(e);
            save();
            return false;
        }
    },
    "Ctrl+S": { // Save form for WINs
        handler: function(e){
            Event.stop(e);
            save();
            return false;
        }
    },
    "Ctrl+Z": {
        handler: function(){
            undo();
        },
        disableOnInputs: true
    },
    "Ctrl+Y": {
        handler: function(){
            redo();
        },
        disableOnInputs: true
    },
    "Meta+Z": {
        handler: function(){
            undo();
        },
        disableOnInputs: true
    },
    "Meta+Y": {
        handler: function(){
            redo();
        },
        disableOnInputs: true
    },
    "Ctrl+Shift+I":{
        handler: function(e){
            Event.stop(e);
            displayQuestionInfo();
        }
    },
    "Meta+Shift+I":{
        handler: function(e){
            Event.stop(e);
            displayQuestionInfo();
        }
    }
});

function setImageSource(id, source, height, width){
    imageWizard.wiz.close();
    var elem = getElementById(id);
    elem.setProperty('height', height);
    elem.setProperty('width', width);
    updateValue("src", source, elem.getReference('container'), elem, elem.getProperty('src'));
}

/**
 * Short hand of creating element
 * @param {Object} tag
 */
function getElement(tag){
    return $(document.createElement(tag));
}

/**
 * Creates the complete question line, With label, edit and delete buttons
 * @param {Object} elem Li element to be replaced
 * @param {Object} oprop(Optional) Default property
 * @param {Object} noreplace(Optional) only creates the elem. doesn't replace it with the original
 */
function createDivLine(elem, oprop, noreplace){
    
    var type = elem.readAttribute('type'); // Read question type from <li></li> element as a custom attribute
    var prop = oprop ? oprop : Utils.deepClone(default_properties[type]); // Get the property to create the question line use default if not provided.
    if (!prop) {
        prop = Utils.deepClone(default_properties.control_hidden);
    }
    var p = elem.parentNode; // Parent node of the element. Will be used for replacing
    var id = -1;
    var title = prop.text ? prop.text.value : "";
    var cname = 'form-input';
    var lcname = 'form-label';
    var alignment = 'Left';
    var label = getElement('div').setStyle('z-index:100');
    var block = getElement('div').setStyle('display:inline-block;width:100%');
    
    if (!noreplace) {
        id = prop.qid ? prop.qid.value : getMaxID() + 1; // Question ID, use default if not provied
        qid = id;
    }
    
    var container = getElement('li');
    container.id = "id_" + id;
    container.writeAttribute('type', type);
    
    var clName = (['control_collapse', 'control_head', 'control_pagebreak'].include(type))? 'form-input-wide' : 'form-line';
    
    container.className = clName + ((prop.shrink && prop.shrink.value == 'Yes') ? ' form-line-column' : '') + ((prop.newLine && prop.newLine.value == 'Yes') ? ' form-line-column-clear' : '');
    
    container.style.cursor = "move";
    
    container.appendChild(block);
    
    // Manage label alignments
    if (form.getProperty('alignment') == 'Top') {
        cname = 'form-input-wide';
        lcname = 'form-label-top';
        label.setStyle('width:100%');
    } else {
        cname = 'form-input';
        lcname = 'form-label-' + form.getProperty('alignment').toLowerCase(); // form-label-left OR form-label-right
        label.setStyle('width:' + form.getProperty('labelWidth') + 'px');
    }
    
    // Question property overwrites the form property
    if (prop.labelAlign && prop.labelAlign.value != 'Auto') {
        if (prop.labelAlign.value == 'Top') {
            cname = 'form-input-wide';
            lcname = 'form-label-top';
            label.setStyle('width:100%');
        } else {
            cname = 'form-input';
            lcname = 'form-label-' + prop.labelAlign.value.toLowerCase(); // form-label-left OR form-label-right
            label.setStyle('width:' + form.getProperty('labelWidth') + 'px');
        }
    }
    
    // If nolabel is set then remove all settings
    cname = (prop.text.nolabel) ? 'form-input-wide' : cname;
    
    // Create label here
    label.className = lcname;
    label.innerHTML = (title == "....") ? "Click to edit".locale() : title;
    label.id = 'label_' + id;
    
    // Read the default or set alignment Use forms if set to Auto
    if (!('labelAlign' in prop) || prop.labelAlign.value == 'Auto') {
        alignment = form.getProperty('alignment');
    } else {
        alignment = prop.labelAlign.value;
    }
    
    var ne = createInput(type, id, prop, noreplace);
    
    ne.setReference('container', container);
    container.setReference('elem', ne);
    
    var inputBox = getElement('div');
    inputBox.className = cname;
    inputBox.appendChild(ne);
    
    // Overlay div
    /**
     * Better disable completely
     */
    if(false){
        var nfields = ['control_head','control_text','control_dropdown','control_radio',
                       'control_checkbox','control_matrix','control_autocomp','control_collapse'];
        if (!('sublabels' in prop) && !nfields.include(type)) {
            inputBox.insert(new Element('div').setStyle('position:absolute; top:0px; left:0px; height:100%; width:100%;'))
            .setStyle('position:relative;');
        }
    }
    
    if(('sublabels' in prop)){
        ne.observe('on:render', function(){
            ne.select('.form-sub-label').each(function sublabelsLoop(sl){
                if(sl.id){
                    sl.editable({
                        className: 'edit-sublabel',
                        onEnd: function(a, b, old, val){
                            if(old != val){
                                var sls = ne.getProperty('sublabels');
                                sls[sl.id.replace('sublabel_', '')] = val;
                                onChange('Sub Label Changed');
                            }
                        }
                    });                    
                }
            });
        });
    }
    
    if (!prop.text.nolabel) {
        //container.appendChild(label);
        block.appendChild(label);
        
        label.editable({
            onBeforeStart: function(){
                if("__justSelected" in container && container.__justSelected){ return false; }
                
                // Before editing the text remove the required value from the element.
                label.select('span').each(function (span){ span.remove(); });
            },
            className: 'edit-text',
            onEnd: function(a, b, old, val){
                ne.setProperty('name', makeQuestionName(val.strip(), id));
                updateValue('text', val.strip(), container, ne, old);
            }
        });
        
        // Place required mark
        if (prop.required) {
            if (prop.required.value == "Yes") {
            	var children = block.childElements();
            	if (children.length > 0){
            		var textElement = children[0];
            		textElement.appendChild(new Element('span', {className:"form-required"}).update("*"));
            	}
            }
        }
    }
    
    var buttonContainer = getElement('div');
    buttonContainer.addClassName('button-container');
    
    
    if (payment_fields.include(type) || type == 'control_image') {
        // Wizard Button
        var wandButton = getElement('img');
        
        wandButton.src = 'images/blank.gif';
        wandButton.className = 'form-wandbutton context-menu-wand';
        wandButton.title = 'Wizard'.locale();
        wandButton.preventInitDrag = true;
        wandButton.onclick = function(){
            if (payment_fields.include(type)) {
                Utils.loadScript('js/builder/payment_wizard.js', function(i){
                    openPaymentWizard(i);
                }, id);
            }
            
            if(type=='control_image'){
                Utils.loadScript('js/builder/image_wizard.js', function(i){
                    imageWizard.openWizard(i);
                }, id);
            }
        };
        
        buttonContainer.appendChild(wandButton);
    }
    
    if(type == 'control_text'){
        var editHTMLButton = getElement('button');
        
        editHTMLButton.className = 'big-button buttons buttons-red';
        editHTMLButton.style.cssText += (';' + 'margin:0; float:left; padding:3px 6px; margin:4px 0 0 4px');
        editHTMLButton.preventInitDrag = true;
        editHTMLButton.innerHTML = "Edit HTML".locale();
        
        
        var oldVal="";
        var openHTMLEdit = function(e){
            
            if(!editHTMLButton.editorOpen){
                oldVal = $("text_"+id).innerHTML;
                Editor.set("text_"+id, 'simple');
                editHTMLButton.innerHTML = "Complete".locale();
                container.observe('on:unselect', openHTMLEdit);
                editHTMLButton.editorOpen = true;
                ne.dblclick = function(){};
            }else{
                var html = Editor.getContent("text_"+id);
                Editor.remove("text_"+id);
                editHTMLButton.innerHTML = "Edit HTML".locale();
                editHTMLButton.editorOpen = false;
                ne.dblclick = openHTMLEdit;
                updateValue('text', html, container, ne, oldVal);
            }
        };
        ne.ondblclick = openHTMLEdit;
        editHTMLButton.observe('click', openHTMLEdit);
        buttonContainer.appendChild(editHTMLButton);
    }
    
    
    var propertiesButton = getElement('img');
    propertiesButton.src = 'images/gear.png';
    propertiesButton.className = 'form-propbutton';
    propertiesButton.title = 'Properties'.locale();
    propertiesButton.alt = "Props";
    propertiesButton.preventInitDrag = true;
    propertiesButton.observe('click', function(e){
         container.openMenu(e, true);
    });
    
    buttonContainer.appendChild(propertiesButton);
    
    
    // Delete Button
    var delButton = getElement('img');
    
    delButton.src = 'images/blank.gif';
    delButton.className = 'form-delbutton index-cross';
    delButton.title = 'Delete'.locale();
    delButton.alt = "X";
    delButton.preventInitDrag = true;
    delButton.onclick = function(e){
        removeQuestion(container, ne);
        Utils.poof(e);
    };
    buttonContainer.hide();
    buttonContainer.appendChild(delButton);
    container.delButton = buttonContainer;
    
    block.appendChild(inputBox);
    container.appendChild(buttonContainer);
    
    container.observe(selectEvent, function(e){
        
        //if(document._onedit){return;}
        $('accordion').show();
        $('style-menu').hide();
        
        if (selected != container) {
            if (selected && selected.parentNode) {
                (document._stopEdit && document._stopEdit());
                if(stopUnselect){ stopUnselect = false; return; }
                selected.removeClassName('question-selected');
                selected.picked = false;
                selected.delButton.hide();
                selected.fire('on:unselect');
                selected.select('.add-button').invoke('hide');
            }
            
            container.removeClassName('question-over');
            container.addClassName('question-selected');
            // If question is payment
            
            container.delButton.show();
            container.select('.add-button').invoke('setStyle', 'display:block');
            selected = container;
            container.picked = true;
            makeToolbar(ne, container);
            
            container.__justSelected = true;
            setTimeout(function(){
                container.__justSelected = false;
            }, 200);
            
            if (form.getProperty('stopHighlight') != 'Yes') {
                $$('#toolbar .big-button-text, #prop-legend').invoke('setStyle', {
                    color: '#000'
                }).invoke('shift', {
                    color: '#FFFFE0',
                    duration: 1,
                    easing: 'pulse'
                });
            }
            
        }
    });
    container.setContextMenu({
        title: prop.text.value.stripTags().shorten(20),
        onStart: function(){
            
            if (!container.hasClassName('question-selected')) {
                container.run(selectEvent);
            }
            // Handle move buttons
            if (!container.previousSibling) {
                container.disableButton('moveup');
                container.enableButton('movedown');
            } else if (!container.nextSibling) {
                container.disableButton('movedown');
                container.enableButton('moveup');
            } else {
                container.enableButton('movedown');
                container.enableButton('moveup');
            }
            
            if (['control_collapse', 'control_pagebreak', 'control_head'].include(type)) {
                container.disableButton('shrink');
            }
            if(type == 'control_captcha' || payment_fields.include(type)){
                container.disableButton('duplicate');
            }
        },
        onOpen: function(){
            
            if (ne.getProperty('required') == 'Yes') {
                container.getButton('required').addClassName('context-menu-check');
            } else if(ne.getProperty('required') == 'No') {
                container.getButton('required').removeClassName('context-menu-check');
            }else{
                container.getButton('required').hide();
            }
        },
        menuItems: [{
            title: 'Required'.locale(),
            name: 'required',
            icon: "images/blank.gif",
            iconClassName: "context-menu-required-small",
            handler: function(){
                if (ne.getProperty('required') == 'Yes') {
                    updateValue('required', "No", container, ne, "Yes");
                    container.getButton('required').addClassName('button-over');
                } else {
                    updateValue('required', "Yes", container, ne, "No");
                    container.getButton('required').removeClassName('button-over');
                }
            }
        }, {
            title: 'Move Up'.locale(),
            icon: "images/blank.gif",
            iconClassName: "context-menu-up",
            name: 'moveup',
            handler: function(){
                var tmp = container;
                var sibling = $(container.previousSibling);
                if (!sibling) {
                    return;
                }
                $(container.parentNode).replaceChild(container.previousSibling, container);
                sibling.insert({
                    before: tmp
                });
                onChange("Question moved");
            }
        }, {
            title: 'Move Down'.locale(),
            iconClassName: "context-menu-down",
            icon: "images/blank.gif",
            name: 'movedown',
            handler: function(){
                var tmp = container;
                var sibling = $(container.nextSibling);
                if (!sibling) {
                    return;
                }
                $(container.parentNode).replaceChild(container.nextSibling, container);
                sibling.insert({
                    after: tmp
                });
                onChange("Question moved");
            }
        }, '-', {
            title: 'Image Wizard'.locale(),
            hidden: type != 'control_image',
            name: 'imagewizard',
            icon: 'images/blank.gif',
            iconClassName: 'context-menu-wand',
            handler: function(){
                Utils.loadScript('js/builder/image_wizard.js', function(i){
                    imageWizard.openWizard(i);
                }, id);
            }
        }, {
            title: 'Payment Wizard'.locale(),
            hidden: !payment_fields.include(type),
            name: 'paymentwizard',
            icon: 'images/blank.gif',
            iconClassName: 'context-menu-wand',
            handler: function(){
                Utils.loadScript('js/builder/payment_wizard.js', function(i){
                    openPaymentWizard(i);
                }, id);
            }
        }, {
            title: container.hasClassName('form-line-column') ? 'Expand'.locale() : 'Shrink'.locale(),
            icon: "images/blank.gif",
    		iconClassName: container.hasClassName('form-line-column') ? "context-menu-expand" : "context-menu-shrink",
            name: 'shrink',
            handler: function(e){
                if (container.hasClassName('form-line-column')) {
                    container.removeClassName('form-line-column');
                    ne.setProperty('labelAlign', 'Auto');
                    updateValue('shrink', "No", container, ne, "Yes");
                } else {
                    
                    container.addClassName('form-line-column');
                    ne.setProperty('labelAlign', 'Top');
                    updateValue('shrink', "Yes", container, ne, "No");
                }
            }
        }, {
            title: container.hasClassName('form-line-column-clear') ? 'Merge to above line'.locale() : 'Move to a new line'.locale(),
            icon: "images/blank.gif",
        	iconClassName: container.hasClassName('form-line-column-clear') ? "context-menu-merge-line" : "context-menu-new-line",
            hidden: !container.hasClassName('form-line-column'),
            name: 'merge-line',
            handler: function(){
                if (container.hasClassName('form-line-column-clear')) {
                    container.removeClassName('form-line-column-clear');
                    updateValue('newLine', "No", container, ne, "Yes");
                } else {
                    container.addClassName('form-line-column-clear');
                    updateValue('newLine', "Yes", container, ne, "No");
                }
            }
        }, {
            title: "Duplicate".locale(),
            icon: "images/blank.gif",
            iconClassName: "context-menu-add",
            name: 'duplicate',
            handler: function(){
                var dprop = Utils.deepClone(ne.retrieve('properties'));
                var elem = new Element('li', {
                    type: type
                });
                dprop.qid.value = getMaxID() + 1;
                dprop.name.value = dprop.name.value.replace(/\d+/, '') + dprop.qid.value;
                  
                container.insert({
                    after: elem
                });
                createDivLine(elem, dprop);
                createList();
            }
        }, {
            title: 'Delete'.locale(),
            name: 'delete',
            iconClassName: 'context-menu-cross_shine',
            icon: "images/blank.gif",
            handler: function(e){
                removeQuestion(container, ne);
                Utils.poof(e);
            }
        }, '-', {
            title: 'Show Properties'.locale(),
            name: 'properties',
            icon: 'images/blank.gif',
            iconClassName: 'context-menu-gear',
            handler: function(){
                makeProperties(ne, container);
            }
        }]
    });
    /**
     * Highlights the selected line
     */
    container.hiLite = function(){
        setTimeout(function(){
            $(container, buttonContainer).invoke('shift', {
                backgroundColor: '#D2FEC7',
                easing: 'pulse',
                easingCustom: 2,
                duration: 2,
                onEnd: function(){
                    container.setStyle({
                        backgroundColor: ''
                    });
                }
            });
        }, 500);
    };
    
    container.hover(function(){
    
        if ($(container.parentNode).hasClassName('dragging')) {
            return;
        }
        
        if (container.picked) {
            return;
        }
        if (container.highlighting) {
            return;
        }
        container.addClassName('question-over');
    }, function(){
        if (container.picked) {
            return;
        }
        container.removeClassName('question-over');
    });
    
    if (!noreplace && p) {
        p.replaceChild(container, elem);
        ne.fire('on:render');
    }
    
    return {
        container: container,
        elem: ne
    };
}

/**
 * Unselects a field
 */
function unselectField(){
    if(stopUnselect){ stopUnselect = false; return; }
    
    if (selected && selected.parentNode && !$('toolbar').editorIsOn && !document._onedit) {
        selected.removeClassName('question-selected');
        selected.picked = false;
        
        selected.delButton.hide();
        selected.fire('on:unselect');
        selected.select('.add-button').invoke('hide');
        selected = false;
        $('prop-legend').hide();
        $('group-formproperties').show();
        $('properties').update();
        makeTabOpen('form-property-legend');
        //makeToolbar(form);
        $('group-properties').hide();
    }
}

/**
 * Returns all regular input elements
 */
function getUsableElements(filter, inverse){
    var filters = {
        condition: ['control_matrix', 'control_grading', 'control_location'],
        email: ['control_textbox', 'control_textarea', 'control_dropdown', 'control_radio', 'control_checkbox', 'control_email', 'control_autocomp', 'control_hidden'],
        name: ['control_textbox', 'control_textarea', 'control_dropdown', 'control_radio', 'control_checkbox', 'control_fullname', 'control_autocomp', 'control_hidden']
    };
    var elems = [];
    $$("div.question-input"/*'#list div[order=0]'*/).each(function getUsableLoop(input){
        if (!$A(not_input).include(input.getProperty('type'))) {
            if (filter && filters[filter] && filters[filter].include(input.getProperty('type'))) {
                if (inverse) {
                    elems.push(input);
                }
                return;
            }
            if (!inverse) {
                elems.push(input);
            }
        }
    });
    return elems;
}

function getAllElements(){
    return $$('div.question-input');
}


/**
 * Returns all payment fields
 */
function getAllPayments(){
    var elems = [];
    $$("div.question-input"/*'#list div[order=0]'*/).each(function allPaymentsLoop(input){
        if ($A(payment_fields).include(input.getProperty('type'))) {
            elems.push(input);
        }
    });
    return elems.length > 0 ? elems : false;
}

/**
 * Check if the form has upload field in it
 */
function hasUpload(){
    if (getElementsByType('control_fileupload')) {
        return true;
    }
    return false;
}

/**
 * Returns elements by their type
 * @param {Object} type
 */
function getElementsByType(type){
    var elems = [];
    $$("div.question-input").each(function getByTypeLoop(input){
        if (input.getProperty('type') == type) {
            elems.push(input);
        }
    });
    return elems.length > 0 ? elems : false;
}

/**
 * returns the element with the given ID
 * @param {Object} id
 */
function getElementById(id){
    var res = $$('#id_' + id + " div.question-input");
    if (res.length > 0) {
        return res[0];
    }
    return false;
}

/**
 * Returns the element from form in given order
 * @param {Object} order
 */
function getElementByOrder(order){
    var rel;
    $$("div.question-input").each(function getByOrderLoop(el, i){
        if (order == ++i) {
            rel = el;
        }
        el.setProperty("order", i);
    });
    return rel;
}

/**
 * Returns the lement from form in given order range
 * @param {Object} order
 */
function getElementsByOrderRange(minOrder, maxOrder){
    var rel = [];
    $$("div.question-input").each(function getByRangeLoop(el, i){
        ++i;
        if (minOrder <= i && maxOrder >= i) {
            rel.push(el);
        }
        el.setProperty("order", i);
    });
    return rel;
}

/**
 * Remove Question
 * @param {Object} container
 * @param {Object} elem
 */
function removeQuestion(container, elem, nocheck){
    
    if(submissionCounts.total > 1 && nocheck !== true){
        Utils.confirm('<img src="images/warning.png" align="left" style="margin-right:10px;" />'+"If you delete this question, <u>you'll lose the associated data</u>.<br> Are you sure you want to proceed?".locale(), "Warning!!".locale(), function(but, value){
            if (value) {
                removeQuestion(container, elem, true)
            }
        });
        return false;
    }
    
    container.makeClipping();
    document._onedit = false;
    (document._stopEdit && document._stopEdit());
    
    if ($A(payment_fields).include(elem.getProperty('type'))) {
        if (form.getProperty('products')) {
            Utils.confirm("Would you like to <u><b>keep the products</b></u> to be used with another payment gateway?".locale(), "Confirm".locale(), function(but, value){
                if (!value) {
                    form.setProperty('products', false);
                    form.setProperty('productMaxID', 100);
                }
            });
        }
    }
    makeTabOpen('form-property-legend');
    $('prop-legend').hide();
    var clearSelected = false;
    if(container.nextSibling){
        $(container.nextSibling).run(selectEvent);
    }else if(container.previousSibling){
        container.previousSibling.run(selectEvent);
    }else{
        clearSelected = true;
    }
    
    container.shift({
        opacity: 0,
        height: 0,
        duration: 0.5,
        onEnd: function(){
            container.remove();
            onChange("Question Removed");
            Utils.updateBuildMenusize();
            Utils.fixBars();
            if(clearSelected){
                selected = false;
            }
        }
    });
}

/**
 * Add Questions from ul#list element.
 */
function addQuestions(){
    $$("#list .drags").each(function addQuestionsLoop(elem){
    
        // Don't allow user to add more than one payment tool
        if (payment_fields.include(elem.readAttribute('type')) && getAllPayments()) {
            Utils.alert('You cannot add more than one <u>payment</u> tool. Please delete the existing one first.'.locale());
            elem.remove();
            return;
        }
        
        // Don't allow user to add more than one capthca tool
        if (elem.readAttribute('type') == "control_captcha" && getElementsByType('control_captcha')) {
            Utils.alert('You cannot add more than one <u>captcha</u> tool. Please delete the existing one first.'.locale());
            elem.remove();
            return;
        }
        
        // Don't allow user to add more than one capthca tool
        if (elem.readAttribute('type') == "control_autoincrement" && getElementsByType('control_autoincrement')) {
            Utils.alert('You cannot add more than one <u>Unique ID</u>. Please delete the existing one first.'.locale());
            elem.remove();
            return;
        }
        
        var res = createDivLine(elem);
        createList();
        
        // Release element from rails
        elem.__onrails = undefined;
        
        
        // Automatically open the payment wizard.
        if (payment_fields.include(res.elem.getProperty('type'))) {
            Utils.loadScript('js/builder/payment_wizard.js', function(i){
                openPaymentWizard(i);
            }, res.elem.getProperty('qid'));
        }
        
        if(res.elem.getProperty('type') == 'control_image'){
            Utils.loadScript('js/builder/image_wizard.js', function(i){
                imageWizard.openWizard(i);
            }, res.elem.getProperty('qid'));
        }
        
        // If there are only two questions and one of them is button then select not button one.
        // because button added there automatically and we don't want to confuse users with a button came out of nowhere
        if ($$("#list li").length == 2 && $$("#list li")[1].readAttribute('type') == 'control_button') {
            var el = $$("#list li")[0];
            el.run(selectEvent);
            el.__justSelected = false; // to make click event run
            el.hiLite();
            if ($$("#list li div[id*=label]")[0]) {
                $$("#list li div[id*=label]")[0].run('click');
            }
            
        } else {
            res.container.run(selectEvent);
            res.container.__justSelected = false; // to make click event run
            res.container.hiLite();
            if (res.container.select('div[id*=label]')[0]) {
                res.container.select('div[id*=label]')[0].run('click');
            }
        }
        onChange('Questions added');
    });
}

/**
 * Re draw thew question
 * @param {Object} elem
 * @param {Object} cont
 */
function renewElement(elem, cont){
    var res = createDivLine(cont, elem.retrieve("properties"));
    createList();
    if(selected.id == res.container.id){
        res.container.run(selectEvent);
    }
    return res;
}

/**
 * Updates the field value
 * @param {Object} key
 * @param {Object} val
 * @param {Object} cont
 * @param {Object} elem
 * @param {Object} old
 */
function updateValue(key, val, cont, elem, old, callback){

    elem.setProperty(key, val);
    var res = {elem: elem, container: cont};
	
    var nobuildList = ["fontcolor", "background", "font", "fontsize", "styles", "formStrings"];
    if (elem.id == "stage") {
        applyFormProperties(false, nobuildList.include(key));
    } else {
        res = renewElement(elem, cont);
    }
    
    if (old != val) {
        onChange(key + " has changed from: '" + old + "' to: '" + val + "'");
    }
    
    if (callback) {
        callback(res);
    }
    return res;
}

/**
 * 
 * @param {Object} but
 */
function emailList(but){
    
    updateEmails();
    
    var button = $(but);
    if (button.menuList) {
        button.menuList.closeMenu();
        return;
    }
    if (closeActiveButton(button) === false) {
        return false;
    }
    lastTool = button;
    button.addClassName('button-over');
    
    var menuContainer = new Element('div');
    
    menuContainer.setStyle({
        width: '250px',
        zIndex: 10000,
        padding: '5px'
    });
    menuContainer.addClassName('edit-box');
    var top = button.cumulativeOffset().top + button.getHeight() + 3;
    var left = button.cumulativeOffset().left;
    menuContainer.setStyle({
        top: top + 'px',
        left: left + 'px'
    });
    menuContainer.insert('<b style="font-size:14px; color:#333">' + 
        'Email List'.locale() +
        '</b><br>');
    
    menuContainer.closeMenu = function(){
        menuContainer.remove();
        button.removeClassName('button-over');
        button.menuList = false;
        lastTool = false;
    };
    
    button.menuList = menuContainer;
    
    var list = new Element('div');
    list.setStyle('border:1px solid #aaa; background:#fff; width:99%; list-style:none; list-style-position:outside; margin:5px 0px;');
    
    var econds = [];
    $A(form.getProperty('conditions')).each(function(c){
        if(c.type == 'email'){
            econds.push(c.action.email);
        }
    });
    
    
    $A(form.getProperty('emails')).each(function emailsLoop(email, index){
        var emailLi = new Element('li');
        
        var icon = 'images/mail' + ((email.type == 'autorespond') ? '-auto' : '') + '.png';
        
        if(econds.include('email-'+index)){
            icon = 'images/cond_small.png';
        }
        
        var emailIcon = new Element('img', {
            src: icon,  // Chhose the apprpriate icon for email
            align: 'absmiddle',
            title: ((email.type == 'autorespond') ? 'Auto Responder' : 'Notification')     // Put email type in the title
        }).setStyle('margin-right:5px');
        
        emailLi.insert(emailIcon);
        
        emailLi.insert(new Element('span', {
            title: (email.name.length > 30 ? email.name : '') // Put full name in the title
        }).update(email.name.shorten(30)));                   // Shorten email name to fit in the box
        
        if (econds.include('email-' + index)) {
            emailLi.insert('<span class="its-conditional" title="'+
            'This email is conditional and will not be sent until all conditions are matched.'.locale()+
            '"> Conditional </span>');
        }
        
        emailLi.setStyle({
            margin: '3px',
            border: '1px solid #ccc',
            background: '#eee',
            padding: '3px',
            cursor: 'pointer',
            position:'relative'
        });
        
        emailLi.mouseEnter(function(){
            emailLi.setStyle({
                background: '#ddd',
                border: '1px solid #aaa'
            });
        }, function(){
            emailLi.setStyle({
                background: '#eee',
                border: '1px solid #ccc'
            });
        });
        
        emailLi.observe('click', function(){
            
            Utils.loadScript('js/builder/email_wizard.js', function(ind){
                EmailWizard.openWizard(ind);
            }, index);
            
            menuContainer.closeMenu();
        });
        
        list.insert(emailLi);
    });
    
    menuContainer.insert(list);
    
    var addNewButton = new Element('button', {
        type: 'button',
        className: 'big-button buttons'
    }).setStyle({
        cssFloat: 'right',
        fontSize: '14px'
    });
    addNewButton.insert('<img src="images/add.png" align="top" > ' + 'Add New Email'.locale());
    menuContainer.insert(addNewButton);
    addNewButton.observe('click', function(){
        Utils.loadScript('js/builder/email_wizard.js', function(){
            EmailWizard.openWizard();
        });
    });
    
    $(document.body).insert(menuContainer);
    menuContainer.positionFixed({
        offset: 68
    });
    menuContainer.updateTop(formBuilderTop + (Utils.isFullScreen? 0 : 68));
    menuContainer.updateScroll();
}

/**
 * Opens the property tab
 * @param {Object} id
 */
function makeTabOpen(id){
    if ($('button_form_styles') && $('button_form_styles').hasClassName('button-over')) {
        openStyleMenu(false, $('button_form_styles'));
    }
    $$('.tab-legend-open').invoke('removeClassName', 'tab-legend-open');
    $$('.index-tab-legend-image').invoke('removeClassName', 'index-tab-legend-image');
    $(id).addClassName('tab-legend-open');
    $(id).addClassName('index-tab-legend-image');
    
    switch (id.id || id) {
        case "form-property-legend":
            $('group-setup').hide();
            $('group-formproperties').show();
            $('group-properties').hide();
            break;
        case "form-setup-legend":
            $('group-setup').show();
            $('group-formproperties').hide();
            $('group-properties').hide();
            break;
        case "prop-legend":
            $('group-setup').hide();
            $('group-formproperties').hide();
            $('group-properties').show();
            break;
    }
    closeActiveButton();
}

/**
 * It was gone for a while but it's back now! :)
 * @param {Object} elem
 * @param {Object} cont
 */
function makeProperties(elem, cont){
    var isForm = elem.id == 'stage';
    var tables = {}, tbodies = {};
    
    $H($(elem).retrieve("properties")).each(function propertiesLoop(pair){
        var tab = ("tab" in pair.value)? pair.value.tab : 'general';
        
        if(!(tab in tables)){
            tbodies[tab] = new Element('tbody');
            tables[tab] = new Element("table", {
                cellpadding: 4,
                cellspacing: 0,
                tabName: tab,
                id:'table-'+tab,
                className: 'prop-table'
            }).insert(tbodies[tab]);
        }
        
        // Don't show hidden values unless it's on debug mode
        if (pair.value.hidden === true && document.DEBUG !== true) {            
            if (!(elem.getProperty('inputType') == "Drop Down" && pair.key == "dropdown")) {
                return;
            }
        }
        // Skip function values
        if (typeof pair.value == "function") {
            return;
        }
        
        if(pair.key == "status" && pair.value.value === ""){
            pair.value.value = "Enabled";
        }
        
        // Define necessary vars
        var tr = new Element('tr'), labelTD, infoTD, valueTD, valueDIV;
        
        // Insert labels
        tr.insert(labelTD = new Element('td', {
            valign: 'top',
            className: 'prop-table-label',
            nowrap: true
        }).insert(pair.value.text || pair.key));
        /*
        tr.insert(infoTD = new Element('td',{
            width:16,
            align:'left',
            valign: 'top'
        }).setStyle('padding:0; padding-top:6px; background:#fff'));
        */
        // If there is a tip defined for thisproperty use it for display 
        if (pair.key in tips) {
            var tip = tips[pair.key];
            // Insert an icon for tips
            labelTD.insert(new Element('span', {
                className:'prop-table-detail'
            }).update(tip.tip));
        }
        
        // If it's in debug mode and hidden values are displayed then warn user that these are debug values
        if (pair.value.hidden === true && document.DEBUG === true) {
            labelTD.insert({
                top: new Element('img', {
                    src: 'images/debug.png',
                    width: 12,
                    height: 12,
                    className: 'debug-icon',
                    align: 'right'
                }).tooltip("Debug Mode hidden values")
            });
        }
        var skipEditable = false;
        if (pair.value.splitter) { // If property has a split value then it should be treated as a textarea
            valueDIV = new Element('div', {
                className: 'valueDiv-long'
            }).insert(pair.value.value.replace(/\|/gim, "<br>"));
            
            tr.insert(valueTD = new Element('td', {
                valign: 'top',
                className: 'prop-table-value'
            }).insert(valueDIV));
        } else {
        	var valueDivInsert;
        	if (pair.key == 'emails') {
                if(pair.value.value[0]){
            		valueDivInsert = pair.value.value[0].name;
                }
                skipEditable = true;
        	} else {
        		valueDivInsert = pair.value.value;
        	}
            
            if(pair.key == 'conditions'){
                valueDivInsert = "<button>Conditions</button>";
                skipEditable = true;
            }
            if(pair.key == 'formStrings'){
                valueDivInsert = '<button class="big-button buttons buttons-grey" onclick="editFormTexts();">Edit Form Warnings</button>';
                skipEditable = true;
            }
            valueDIV = new Element('div', {
                className: pair.value.textarea ? 'valueDiv-long' : 'valueDiv'
            }).insert(valueDivInsert);
            
            tr.insert(valueTD = new Element('td', {
                valign: 'top',
                className: 'prop-table-value'
            }).insert(valueDIV));
            if(skipEditable){
                tbodies[tab].insert(tr);
                return;
            }
        }
        
        if (pair.value.textarea) {
            // If the property has a splitter value then it means this is a list value
            // It should be splitted and displayed in the textarea
            if (pair.value.splitter) {
                valueDIV.editable({
                    className: 'edit-textarea',
                    labelEl: labelTD,
                    type: 'textarea',
                    escapeHTML: false,
                    defaultText: 'Click to edit'.locale(),
                    onStart: function(){
                        valueDIV.removeClassName('valueDiv-long');
                    },
                    processBefore: function(text){
                        return text.replace(/\<br\>/gim, "\n");
                    },
                    processAfter: function(text){
                        return text.replace(/\n/gim, "<br>");
                    },
                    onEnd: function(e, edited, old, val){
                        edited = edited.replace(/\<br\>/gim, "|").replace(/\|+/g, "|").replace(/^\|+|\|+$/g, "");
                        var newEl = updateValue(pair.key, edited, cont, elem, old);
						elem = newEl.elem;
                        cont = newEl.container;
                    }
                });
            } else {
                valueDIV.editable({
                    className: 'edit-textarea',
                    type: 'textarea',
                    labelEl: labelTD,
                    defaultText: 'Click to edit'.locale(),
                    onStart: function(){
                        valueDIV.removeClassName('valueDiv-long');
                    },
                    onEnd: function(e, eht, old, val){
                        var newEl = updateValue(pair.key, val, cont, elem, old);
						elem = newEl.elem;
                        cont = newEl.container;
                    }
                });
            }
        } else if (pair.value.dropdown) {
        
            var opts = pair.value.dropdown;
            var useValue = pair.value.value;
            if (pair.value.dropdown == "options") {
                opts = elem.getProperty('options').split('|');
                
                if (elem.getProperty('special') != 'None') {
                    opts = Utils.deepClone(special_options[elem.getProperty('special')].value);
                }
                if (opts[0] !== '') {
                    opts.splice(0, 0, '');
                }
            } else {
                opts = $A(opts).map(function(n){
                    if(Object.isString(n)){
                        return {
                            text: n,
                            value: n
                        };
                    }
                    // First Display the corret value for dropdown
                    if(useValue == n[0]){
                        useValue = n[1];
                    }
                    return {
                        text: n[1],
                        value: n[0]
                    };
                });
            }
            valueDIV.update(useValue);
            valueDIV.editable({
                className: 'edit-dropdown',
                type: 'dropdown',
                labelEl: labelTD,
                options: opts,
                onEnd: function(e, sel_value, old, val){
                    var newEl = updateValue(pair.key, (val.value || val.text) || sel_value, cont, elem, old);
					elem = newEl.elem;
                    cont = newEl.container;
                }
            });
            
        } else {
            var onStart = Prototype.K;
            if (pair.value.color) { // Check colorpicker somehow
                onStart = function(el, val, input){
                    input.colorPicker2({
                        onComplete: function(){
                            el.finishEdit();
                        }
                    });
                    //input.focus();
                    input.run('click');
                };
            }
            
            valueDIV.editable({
                className: 'edit-text',
                onStart: onStart,
                labelEl: labelTD,
                defaultText: 'Click to edit'.locale(),
                onEnd: function(e, eht, old, val){
                    var newEl = updateValue(pair.key, val, cont, elem, old);
					elem = newEl.elem;
					cont = newEl.container;
                }
            });
        }
        tbodies[tab].insert(tr);
    });
    
    var tableCont = new Element('div', {id:"prop-" + elem.id}).setStyle('display:inline-block');
    var tabs = new Element('div', {className:'prop-tabs-cont'});
    var tabCont = new Element('div', {className:'prop-tabs-table'});
    var createdTabs = [];
    var pwindow;
    $H(tables).each(function(p){
        var t = p.value;
        var tinfo = t.readAttribute('tabName');
        if(!(tinfo in createdTabs)){
            var tab = new Element('div', {className:(tinfo == 'general'? 'prop-tab prop-tab-open' : 'prop-tab'), id:'tab-'+tinfo}).update(preferenceTabs[tinfo]);
            tab.observe('click', function(){
                $$('.prop-tab-open').invoke('removeClassName', 'prop-tab-open');
                tab.addClassName('prop-tab-open');
                tabCont.select('.prop-table').invoke('hide');
                $('table-'+tinfo).show();
                if(tinfo == 'formStyle'){
                    pwindow.reCenter();
                }
            });
            tabs.insert(tab);
            createdTabs.push(tinfo);
        }
        tabCont.insert(t);
        if(tinfo != 'general'){
            t.hide();
        }
    });
    tableCont.insert(tabs).insert(tabCont);
    
    if ($("prop-" + elem.id)) {
        $($("prop-" + elem.id).parentNode).update(tableCont);
    } else {
        pwindow = document.window({
            title: isForm ? 'Preferences'.locale() : 'Properties'.locale(),
            content: tableCont,
            modal: false,
            width:520,
            buttonsAlign: 'center',
            onInsert: function(){
                if(!isForm){
                    tableCont.insert({after:'<div class="hidden-field-warning">'+
                    '<img src="images/light-bulb.png" align="top">'+
                    'Tip: Did you know, you can also change the properties from the toolbar?'.locale()+
                    '</div>'});
                }
            },
            buttons: [{
                title: 'Close Settings'.locale(),
                name: 'ok',
                color:'green',
                handler: function(w){
                    w.close();
                }
            }]
        });
    }
}

function editFormTexts(){
    var win = document.window({
        title: "Edit Form Warnings",
        width: 400,
        contentPadding:0,
        content: '<div class="string-edit-hint"><img align="right" src="images/required-example.png?v2" style="margin-left:5px;" />  Change warning messages on your form validations. Click on them to change their values.</div><ul id="form-string"></ul>',
        onInsert: function(w){
            w.select('.window-content-wrapper')[0].setStyle('background:#eee');
            var ul = $('form-string');
            var texts = form.getProperty('formStrings')[0];
            
            $H(default_properties.form.formStrings.value[0]).each(function(pair){
                var li = new Element('li');
                var not = '<div class="edit-hover">Click To Edit</div>';
                
                li.writeAttribute('data-name', pair.key);
                li.update(texts[pair.key]);
                li.insert(not);
                li.observe('click', function(){
                    li.select('.edit-hover').invoke('remove');
                    var currentValue = li.innerHTML.strip();
                    if(li.hasClassName('now-editing')){return;}
                    li.addClassName('now-editing');
                    var textarea, save, cancel, buttonDiv;
                    li.update('<div class="original">Original: ' + pair.value + '</div>');
                    li.insert(textarea = new Element('textarea').setValue(currentValue));
                    li.insert(buttonDiv = new Element('div').setStyle('text-align:right;'));
                    buttonDiv.insert(save = new Element('button', {className:'big-button buttons buttons-grey'}).update('Ok'));
                    buttonDiv.insert(cancel = new Element('button', {className:'big-button buttons buttons-grey'}).update('Cancel'));
                    textarea.select();
                    try {
	                save.observe('click', function(){
                        li.update(textarea.value.stripScripts().stripTags());
                        setTimeout(function(){
                            li.insert(not);
                            li.removeClassName('now-editing');
                        }, 1000);
                    });
                    cancel.observe('click', function(){
                        li.update(currentValue);
                        setTimeout(function(){
                            li.insert(not);
                            li.removeClassName('now-editing');
                        }, 1000);
                    });
                    } catch (e) {
                    	console.error(e);
                    }
                });
                ul.insert(li);
            });
        },
        buttons:[{
            title:'Save',
            color:'green',
            handler: function(w){
                var t;
                if((t = $$('#form-string textarea')[0])){
                    t.addClassName('error');
                    Utils.alert('There are uncompleted properties');
                    return;
                }
                var properties = {};
                $$('#form-string li').each(function(li){
                    var key   = li.readAttribute('data-name');
                    li.select('.edit-hover').invoke('remove');
                    var value = li.innerHTML;
                    properties[key] = value;
                });
                form.setProperty('formStringsChanged', 'Yes');
                updateValue('formStrings', [properties], form, form, form.getProperty('formStrings'));
                // form.setProperty('formStrings', [properties]);
                w.close();
            }
        },{
            title:'Cancel',
            handler: function(w){
                w.close();
            }
        }]
    });
}


/**
 * Closes the currently opened Tool
 * @param {Object} tool
 */
function closeActiveButton(tool){
    if (lastTool) {
    
        lastTool.open = false;
        lastTool.removeClassName("button-over");
        if (lastTool.div) {
            lastTool.div.remove();
            if(lastTool.colorPickerEnabled){ lastTool.colorPickerEnabled=false; }
        }
        if (lastTool.menuList) {
            lastTool.menuList.closeMenu();
        }
        $('accordion').show();
        $('style-menu').hide();
        if(lastTool.id == 'button_form_styles'){
            lastTool.select('.big-button-text')[0].update('Themes'.locale());
        }
        
        if (tool && lastTool == tool) {
            lastTool = false;
            return false;
        }
    }
    return true;
}

/**
 * Creates the properties toolbar with small icons for each
 * @param {Object} elem
 * @param {Object} cont
 */
function makeToolbar(elem, cont){

    var isForm = (elem.id == 'stage'); // Is it form properties?
    var toolBarEl = isForm ? 'group-formproperties' : 'toolbar';
    var id = elem.getProperty('qid') || 'form';
    var allowedFormProps = ['alignment', 'font', 'fontcolor', 'fontsize', 'background', 'labelWidth', 'formWidth', 'styles'];
    var highlightConf = {
        background: '#FFFFE0',
        duration: 1,
        easing: 'pulse',
        easingCustom: 2,
        link: 'ignore'
    };
    $$('.edit-box').invoke('remove');
    if ($('button_form_styles') && $('button_form_styles').hasClassName('button-over')) {
        openStyleMenu(false, $('button_form_styles'));
    }
    $(toolBarEl).update();
    
    var tmp_div = new Element('div').setStyle('position:relative; height:68px;max-width:698px');
    /*
    // We don't seem to need these right now. but we may later so just let them stay here.
    
    if (!flips_are_added) {
    
        leftFlip = new Element('div').insert('<img style="position:absolute; top:25px; left:-2px;" src="images/toolbar/flipleft.png" align="absmiddle" />');
        leftFlip.insert('<img src="images/shadow_right.png" style="position: absolute; top: 0px; right: -5px;height:100%; width:5px;">');
        $('group-properties').insert(leftFlip);
        rightFlip = new Element('div').insert('<img style="position:absolute; top:25px; left:-2px;" src="images/toolbar/flipright.png" align="middle" />');
        $('group-properties').insert(rightFlip);
        rightFlip.insert('<img src="images/shadow.png" style="position: absolute; top: 0px; left: -5px;height:100%; width:5px;">');
        flips_are_added = true;
        
        leftFlip.setStyle('position:absolute; left:-10px;  font-size:8px; width:10px; background:#ccc url(images/grad4.png); border-right:1px solid #999;');
        rightFlip.setStyle('position:absolute; right:0px; font-size:8px; width:10px; background:#ccc url(images/grad4.png); border-left:1px solid #999;');
        
        // Dirty browser hack for flippers sizes
        if (Prototype.Browser.Gecko) {
            leftFlip.setStyle('top:0px; height:68px;');
            rightFlip.setStyle('top:0px; height:68px;');
        } else if (Prototype.Browser.WebKit) {
            leftFlip.setStyle('top:5px; height:67px;');
            rightFlip.setStyle('top:5px; height:67px;');
        } else if (Prototype.Browser.IE) {
            leftFlip.setStyle('top:0px; height:50px;');
            rightFlip.setStyle('top:0px; height:50px;');
        } else {
            leftFlip.setStyle('top:6px; height:51px;');
            rightFlip.setStyle('top:6px; height:51px;');
        }
    }
    rightFlip.hide();
    leftFlip.hide();
    */
    
    var toolCount = 0;
    if (!isForm) {
        $('group-properties').show();
        $('prop-legend').update("Properties".locale() + ((elem.getProperty('text')) ? ": " + elem.getProperty('text') : ": Form").stripTags().shorten(70)).show();
        makeTabOpen('prop-legend');
        $('group-formproperties').hide();
        $('group-setup').hide();
    }
    
    $(toolBarEl).insert(tmp_div);
    $H(elem.retrieve('properties')).each(function toolbarLoop(prop){
        
        if ((prop.key == 'text' && !prop.value.forceDisplay) || prop.key == 'getItem') {
            return;
        } // If question label then skip it
        
        if (prop.value.toolbar === false) {
            return;
        } // If value was not meant to display on toolbar
        
        if (prop.value.hidden === true) {
            return;
        } // If value was not meant to display on toolbar
        
        if (isForm && !allowedFormProps.include(prop.key)) {
            return;
        }
        
        var item = toolbarItems[prop.key]; // Take item description from default_toolbar items
        // if there is prop value text use that.
        if (!item) { // if it's not defined, make it default
            item = {};
            if (prop.value.hidden) {
                return;
            }

            if ( prop.value.text ){
    			item.text = prop.value.text;
    		}
            
            if (prop.value.dropdown) {
                item.type = 'dropdown';
                item.values = prop.value.dropdown;
            }
            if (prop.value.textarea) {
                item.type = 'textarea';
            }
        }
        
        if (!item.icon || item.icon === "") {
            item.icon = 'images/toolbar/settings.png';//default.png';  
        }
        
        if ( prop.value.type ){
            item.type = prop.value.type;
        }
        
        if ( prop.value.hint ){
            item.hint = prop.value.hint;
        }
        
        if ( prop.value.handler ){
            item.handler = prop.value.handler;
        }
        
        if (prop.value.icon) {
            item.icon = prop.value.icon;
        }
        
        if (prop.value.iconClassName) {
            item.iconClassName = prop.value.iconClassName;
        }
        
        /** Create the button */
        var tool = new Element('button', {
            type: 'button',
            className: 'big-button',
            id: "button_" + id + "_" + prop.key
        });
        var span = new Element('span', {
            className: 'big-button-text'
        }).insert(item.text);
        
        if (!item.iconClassName){
        	item.iconClassName = "";
        }
        
        var button_icon = new Element('img', {
            align: 'top',
            className: item.iconClassName,
            src: item.icon /*width:24, height:24*/
        }).setStyle('min-height:24px;min-width:24px;');
        
        tool.insert(button_icon);
        tool.insert('<br>').insert(span);
        var itemOffset = 69;
        var editBoxTop = (Utils.isFullScreen ? 0 : itemOffset);
        if (prop.value.disabled === true) {
            tool.disable();
        }
        
        var getDim = function(tool){
            var dim = {}; // Dimensions 
            if (tool.hasFixedContainer()) {
                dim = tool.cumulativeOffset();
                dim.top = $(document.body).cumulativeScrollOffset().top;
            } else {
                dim = tool.cumulativeOffset();
            }
            return dim;
        };
        
        tool.observe('click', function(){
            // Close highlights when user notices toolbar
            form.setProperty('stopHighlight', 'Yes');
        });
        
        /* Do type specific thing here */
        if (item.type == 'toggle') {
            
            // Toggle is cool
            tool.currentVal = prop.value.value;
            tool.observe('mousedown', function(){
                var val = (tool.toggled) ? item.values[0][0] : item.values[1][0];
                var old = tool.currentVal;
                updateValue(prop.key, val, cont, elem, old);
            });
            
            var bval = '';
            
            if (item.values[1][0] == prop.value.value) {
                tool.addClassName('button-over');
                button_icon.setStyle({
                    opacity: 1
                });
                bval = 'ON'.locale() + ' ';
                tool.toggled = true;
            }
            button_icon.insert({ before: '<span style="font-size:9px;">' + bval + '</span>' });
        } else if(item.type == 'textarea-combined'){
            tool.observe('mousedown', function(e){
            
                if (closeActiveButton(tool) === false) {
                    return false;
                }
                
                lastTool = tool;
                
                
                tool.addClassName("button-over");
                if (!tool.open) {
                    tool.open = true;
                    var div = new Element('div', {
                        className: 'edit-box'
                    });
                    lastTool.div = div;
                }
                
                var oldVal = prop.value.value.split("-");
                
                var dropdown = new Element('select').setStyle('outline: medium none; border: 1px solid rgb(170, 170, 170);');
                $A(prop.value.values).each(function(v){
                    dropdown.insert(new Element('option', {value:v[0]}).update(v[1]));
                });
                dropdown.selectOption(oldVal[0]);
                var input = new Element('input', {type:'text', size:4})
                    .setStyle('outline: medium none; border: 1px solid rgb(170, 170, 170); padding: 4px;margin-right:5px;');
                input.value = oldVal[1];
                
                var complete = function(){
                    var val = dropdown.value+"-"+input.value;
                    var old = prop.value.value;
                    updateValue(prop.key, val, cont, elem, old);
                    div.remove();
                    tool.removeClassName("button-over");
                    tool.open = false;
                    lastTool = false;
                };
                
                div.insert(input).insert(dropdown).insert(new Element('input', {
                    type: 'button',
                    value: 'OK'.locale(),
                    className: 'big-button buttons buttons-green'
                }).observe('click', complete));
                
                
                if (item.hint) {
                    div.insert('<div class="edit-hint">' + item.hint + '</div>');
                }
                var dim = getDim(tool);
                div.setStyle({
                    position: 'absolute',
                    top: dim.top + editBoxTop + 'px',
                    left: dim.left + 'px',
                    zIndex: 100000
                });
                    
                $(document.body).insert(div);
                    
                div.positionFixed({
                    offset: itemOffset
                });
                div.updateTop(formBuilderTop + editBoxTop);
                div.updateScroll();
            });
        } else if (item.type == 'colorpicker') {
            // Colorpicker is cool
            var onEnd = function(val){
                var old = prop.value.value;
                updateValue(prop.key, val, cont, elem, old);
            };
            
            var dim = getDim(tool);
            
            tool.__colorvalue = prop.value.value
            
            tool.colorPicker2({
                title: item.hint || item.text,
                className: 'edit-box',
                buttonClass: 'big-button buttons buttons-green',
                onStart: function(){
                    if (closeActiveButton(tool) === false) {
                        return false;
                    }
                    lastTool = tool;
                    tool.addClassName("button-over");
                    
                    return true;
                },
                onEnd: function(el, table){
                    table.positionFixed({
                        offset: itemOffset
                    });
                    table.updateTop(formBuilderTop + editBoxTop);
                    table.updateScroll();
                    lastTool.div = table;
                },
                onPicked: onEnd,
                onComplete: function(v){
                    tool.removeClassName("button-over");
                    lastTool = false;
                    onEnd(v);
                }
            });
        } else if (item.type == 'textarea') {
        
            tool.observe('mousedown', function(e){
            
                if (closeActiveButton(tool) === false) {
                    return false;
                }
                
                lastTool = tool;
                
                
                tool.addClassName("button-over");
                if (!tool.open) {
                    tool.open = true;
                    var div = new Element('div', {
                        className: 'edit-box'
                    });
                    lastTool.div = div;
                    var input = new Element('textarea', {
                        cols: 40,
                        rows: 6
                    }).setStyle({
                        outline: 'none',
                        border: '1px solid #aaa'
                    });
                    if (prop.value.splitter) {
                        var reg = new RegExp('\\' + prop.value.splitter + '', 'gim');
                        input.value = prop.value.value.replace(reg, '\n');
                    } else {
                        input.value = prop.value.value;
                    }
                    
                    var complete = function(){
                        var val = input.value;
                        if (prop.value.splitter) {
                            val = val.replace(/\n\r|\n|\r\n|\r/g, '\n').replace(/^\n|\n$/g, '').replace(/\n/g, prop.value.splitter);
                        }
                        var old = prop.value.value;
                        old = prop.value.value;
                        updateValue(prop.key, val, cont, elem, old);
                        div.remove();
                        tool.removeClassName("button-over");
                        tool.open = false;
                        lastTool = false;
                    };
                    if (item.hint) {
                        div.insert('<div class="edit-hint">' + item.hint + '</div>');
                    }
                    var dim = getDim(tool);
                    div.insert(input);
                    div.insert(' ').insert(new Element('input', {
                        type: 'button',
                        value: 'OK'.locale(),
                        className: 'big-button buttons buttons-green'
                    }).observe('click', complete));
                    div.setStyle({
                        position: 'absolute',
                        top: dim.top + editBoxTop + 'px',
                        left: dim.left + 'px',
                        zIndex: 100000
                    });
                    
                    $(document.body).insert(div);
                    
                    div.positionFixed({
                        offset: itemOffset
                    });
                    div.updateTop(formBuilderTop + editBoxTop);
                    div.updateScroll();
                    input.focus();
                }
            });
        } else if (item.type == 'dropdown') {
        
            var opts = prop.value.dropdown;
            if (prop.value.dropdown == "options") {
                opts = elem.getProperty('options').split('|');
                if (elem.getProperty('special') != 'None') {
                    opts = Utils.deepClone(special_options[elem.getProperty('special')].value);
                }
                if (opts[0] !== '') {
                    opts.splice(0, 0, '');
                }
            }
            
            var useList = (opts.length < 10);
            
            
            tool.observe('mousedown', function(e){
            
                if (closeActiveButton(tool) === false) {
                    return false;
                }
                
                lastTool = tool;
                
                if (!tool.open) {
                    tool.open = true;
                    tool.addClassName("button-over");
                    var div = new Element('div', {
                        className: 'edit-box'
                    });
                    lastTool.div = div;
                    
                    var dim = getDim(tool);
                    
                    if(useList){
                        var list = new Element('ul');
                        $A(opts).each(function dropdownListLoop(o, i){
                            var t = typeof o == "string" ? o : o[1];
                            var v = typeof o == "string" ? o : o[0];
                            
                            var op = new Element('input', {type:'radio',
                                className:'input_field', 
                                checked: prop.value.value == v, 
                                name:'rad_'+prop.key, 
                                value:v, id:'rad_'+i}).setStyle('margin-left:0; margin-right:5px;');
                                
                            var lab = new Element('label', {htmlFor:'rad_'+i}).update(op).setStyle({marginLeft:0});
                            lab.insert(t);
                            var li = new Element('li').setStyle('margin:0');
                            li.insert(lab);
                            list.insert(li);
                        });
                        div.insert(list);
                        
                        list.select('.input_field').each(function inputListLoop(field){
                            field.onclick = function(){
                                tool.removeClassName("button-over");
                                var val = field.value;
                                var old = prop.value.value;
                                updateValue(prop.key, val, cont, elem, old);
                                div.remove();
                                tool.open = false;
                                lastTool = false;
                            };
                        });
                        
                    }else{
                        var input = new Element('select').setStyle({
                            outline: 'none',
                            border: '1px solid #aaa'
                        });
                        
                        $A(opts).each(function(o){
                            var t = typeof o == "string" ? o : o[1];
                            var v = typeof o == "string" ? o : o[0];
                            var op = $(new Option()).setText(t);
                            op.value = v;
                            input.appendChild(op);
                        });
                        
                        div.insert(input);
                        input.selectOption(prop.value.value);
                                                
                        var complete = function(){
                            tool.removeClassName("button-over");
                            var s = input.getSelected();
                            var val = s.value;
                            var old = prop.value.value;
                            updateValue(prop.key, val, cont, elem, old);
                            div.remove();
                            tool.open = false;
                            lastTool = false;
                        };
                        div.insert(' ').insert(new Element('input', {
                            type: 'button',
                            value: 'OK'.locale(),
                            className: 'big-button buttons buttons-green'
                        }).observe('click', complete));
                        
                        input.observe('keyup', function(e){
                            e = document.getEvent(e);
                            if (e.keyCode == 13) {
                                complete();
                            }
                        });
                        
                        input.observe('change', function(e){
                            complete();
                        });
                        
                        input.focus();
                    }
                    
                    if (item.hint) {
                        div.insert('<span class="edit-hint">' + item.hint + '</span><br>');
                    }
                    
                    div.setStyle({
                        position: 'absolute',
                        top: dim.top + editBoxTop + 'px',
                        left: dim.left + 'px',
                        zIndex: 100000
                    });
                    
                    $(document.body).insert(div);
                    div.positionFixed({
                        offset: itemOffset
                    });
                    
                    div.updateTop(formBuilderTop + editBoxTop);
                    div.updateScroll();
                    
                }
            });
        } else if (item.type == 'menu') {
        
            var itemOptions = item.values;
            
            tool.observe('mousedown', function(e){
            
                if (closeActiveButton(tool) === false) {
                    return false;
                }
                
                lastTool = tool;
                
                if (!tool.open) {
                    tool.open = true;
                    tool.addClassName("button-over");
                    var div = new Element('div', {
                        className: 'edit-box'
                    });
                    lastTool.div = div;
                    
                    var apply = function(){
                        group.select('input[type=radio]').each(function(r){
                            if (r.checked) {
                                var val = r.value;
                                var old = prop.value.value;
                                if (val != old) {
                                    updateValue(prop.key, val, cont, elem, old);
                                }
                                /*div.remove();
                                 tool.open = false;
                                 lastTool = false;
                                 */
                                closeActiveButton(tool);
                            }
                        });
                    };
                    
                    var group = new Element('div').setStyle('list-style:none; list-style-position:outside;');
                    
                    $A(itemOptions).each(function(o, i){
                    
                        var elID = i + '_el_id';
                        var li = new Element('li'); // list container
                        var lb = new Element('label', {
                            htmlFor: elID
                        });
                        
                        if (o.icon) {
                            var ic = new Element('img', {
                                align: 'absmiddle',
                                src: o.icon
                            }).setStyle('margin-right:5px;'); // Icon
                            lb.insert(ic);
                        }
                        
                        lb.insert(o.text);
                        
                        if (prop.key == 'font') {
                            lb.setStyle('font-family:' + o.text);
                        }
                        
                        var rd = new Element('input', {
                            type: 'radio',
                            id: elID,
                            name: 'sp_menu_item',
                            value: o.value
                        }).setStyle('margin:0px; margin-top:2px; padding:0px;');
                        
                        rd.onclick = apply;
                        li.insert(rd).insert(lb);
                        
                        rd.checked = (prop.value.value == o.value); // Make it checked
                        group.insert(li);
                    });
                    
                    var complete = function(){
                        console.error("You have found a place to use this complete");
                    };
                    
                    if (item.hint) {
                        div.insert('<span class="edit-hint">' + item.hint + '</span><br>');
                    }
                    
                    var dim = getDim(tool);
                    div.insert(group);
                    
                    div.setStyle({
                        position: 'absolute',
                        top: dim.top + editBoxTop + 'px',
                        left: dim.left + 'px',
                        zIndex: 100000
                    });
                    $(document.body).insert(div);
                    div.positionFixed({
                        offset: itemOffset
                    });
                    div.updateTop(formBuilderTop + editBoxTop);
                    div.updateScroll();
                }
            });
        } else if (item.type == "handler") {
            tool.observe('mousedown', function(e){
                item.handler(item, tool);
            });
            
        } else { // type == "text"
            tool.observe('mousedown', function(e){
            
                if (lastTool) {
                    lastTool.open = false;
                    lastTool.removeClassName("button-over");
                    if (lastTool.div) {
                        lastTool.div.remove();
                        if(lastTool.colorPickerEnabled){ lastTool.colorPickerEnabled = false; }
                    }
                    $('accordion').show();
                    $('style-menu').hide();
                    if (lastTool == tool) {
                        if (prop.key == "formWidth") {
                            $('list').setStyle({
                                borderRight: '',
                                width: prop.value.value + 'px'
                            });
                            
                        }
                        if (prop.key == "labelWidth") {
                            $$('.form-label-left, .form-label-right').each(function(e){
                                e.setStyle({
                                    outline: '',
                                    width: prop.value.value + 'px'
                                });
                            });
                        }
                        lastTool = false;
                        return true;
                    }
                }
                lastTool = tool;
                
                if (!tool.open) {
                    tool.addClassName("button-over");
                    tool.open = true;
                    var div = new Element('div', {
                        className: 'edit-box'
                    });
                    lastTool.div = div;
                    var input = new Element('input', {
                        type: 'text',
                        size: item.size || 25
                    }).setStyle({
                        outline: 'none',
                        border:  '1px solid #aaa',
                        padding: '4px'
                    });
                    input.value = prop.value.value;
                    
                    var complete = function(){
                        tool.removeClassName("button-over");
                        var val = input.value;
                        var old = prop.value.value;
                        updateValue(prop.key, val, cont, elem, old);
                        if (prop.key == "formWidth") {
                            $('list').setStyle({
                                borderRight: ''
                            });
                        }
                        if (prop.key == "labelWidth") {
                            $$('.form-label-left, .form-label-right').each(function(e){
                                e.setStyle({
                                    outline: ''
                                });
                            });
                        }
                        div.remove();
                        tool.open = false;
                        lastTool = false;
                    };
                    if (item.hint) {
                        div.insert('<span class="edit-hint">' + item.hint + '</span><br>');
                    }
                    var dim = getDim(tool);
                    div.insert(input);
                    div.insert(' ').insert(new Element('input', {
                        type: 'button',
                        value: 'OK'.locale(),
                        className: 'big-button buttons buttons-green'
                    }).observe('click', complete));
                    input.observe('keyup', function(e){
                        e = document.getEvent(e);
                        if (e.keyCode == 13) {
                            complete();
                        }
                    });
                    if (item.type == "spinner") {
                        input.spinner({
                            cssFloat: 'left',
                            addAmount: 1,
                            size: item.size || 6,
                            width: 70,
                            onChange: function(val){
                                if (prop.key == "font") {
                                    $('list').setStyle({ fontFamily: val });
                                }
                                if (prop.key == "fontsize") {
                                    val = parseInt(val, 10);
                                    $('list').setStyle({ fontSize: val + 'px' });
                                }
                                if (prop.key == "formWidth") {
                                    val = parseFloat(val);
                                    $('list').setStyle({ width: val + 'px' });
                                }
                                if (prop.key == "labelWidth") {
                                    $$('.form-label-left, .form-label-right').each(function(e){
                                        e.setStyle({ width: val + 'px' });
                                    });
                                }
                            }
                        });
                        
                        if (prop.key == "formWidth") {
                            $('list').setStyle('border-right:2px dashed #ccc;');
                        }
                        if (prop.key == "labelWidth") {
                            $$('.form-label-left, .form-label-right').each(function(e){
                                e.setStyle({
                                    outline: '2px dashed #ccc'
                                });
                            });
                        }
                    }
                    div.setStyle({
                        position: 'absolute',
                        top: dim.top + 46 + 'px',
                        left: (dim.left) + 'px',
                        zIndex: 100000
                    });
                    $(document.body).insert(div);
                    div.positionFixed({
                        offset: itemOffset
                    });
                    div.updateTop(formBuilderTop + editBoxTop);
                    div.updateScroll();
                    input.focus();
                    input.select();
                }
            });
        }
        
        tmp_div.insert(tool);//.insert('&nbsp;');
        if (prop.key in tips) {
            var tip = tips[prop.key];
            /*tip = Object.extend({
             shadow:true,
             fadeIn:{duration:0.5},
             delay:1,
             duration:3
             }, tip || {});
             tool.tooltip(tip.tip, tip);*/
            buttonToolTips(tool, {
                message: tip.tip,
                offsetTop: 10,
                offsetLeft: 200,
                arrowPosition: 'top',
                parent: $('tool_bar')//$('group-properties')
            });
        }
        
        if (false) {
            toolCount++;
            if (toolCount == 4) {
                tmp_div.insert('<hr style="background:none; padding:0px; margin:2px; border:0px; height:1px; border-top:1px dotted #aaa;">');
                toolCount = 0;
            }
        }
    });
    /*
    // We don't seem to need these right now. but we may later so just let them stay here.
    
    if (tmp_div.isOverflow() && tmp_div.isOverflow().left) {
        rightFlip.show();
        var scrollAmount = tmp_div.scrollWidth - $('group-properties').getWidth();
        var scrollSpeed = 0.6;
        rightFlip.onclick = function(){
            tmp_div.shift({
                scrollLeft: scrollAmount,
                duration: scrollSpeed,
                onEnd: function(){
                    rightFlip.hide();
                    leftFlip.show();//.setStyle({left:scrollAmount+'px'});
                    leftFlip.onclick = function(){
                        tmp_div.shift({
                            scrollLeft: 0,
                            duration: scrollSpeed,
                            onEnd: function(){
                                leftFlip.hide();
                                rightFlip.show();
                            }
                        });
                    };
                }
            });
        };
    }
    */
    if ($(toolBarEl).lastChild && $(toolBarEl).lastChild.tagName == 'HR') {
        $($(toolBarEl).lastChild).remove();
    }
    
}

function buttonToolTips(button, options){

    // Disable tooltips for translations
    if(Utils.lang != "en" && Utils.lang != "tr"){
	    return;        
    }

    options = Object.extend({
        offsetTop: 0,
        offsetLeft: 0,
        parent: $(button.parentNode),
        arrowPosition: 'top'
    }, options || {});
    
    var create = function(){
        var bubble = new Element('div', {className: 'form-description'});
        var arrow = new Element('div', {className: 'form-description-arrow-' + options.arrowPosition});
        var arrowsmall = new Element('div', {className: 'form-description-arrow-' + options.arrowPosition + '-small'});
        var title = new Element('div', {className: 'form-description-title'});
        var content = new Element('div', {className: 'form-description-content'});
        
        if (options.width) {
            bubble.setStyle({
                width: options.width + 'px',
                minWidth: options.width + 'px',
                maxWidth: 'none'
            });
        }
        
        content.insert(options.message);
        if (options.title) {
            title.insert(options.title);
            arrowsmall.setStyle('border-bottom-color:#ddd');
        } else {
            title.hide();
        }
        
        bubble.insert(arrow).insert(arrowsmall).insert(title).insert(content).hide();
        
        options.parent.insert(bubble);
        
        switch (options.arrowPosition) {
            case "top":
                var w = ((bubble.getWidth() - 6) / 2) - 10;
                arrow.setStyle('left:' + w + 'px');
                arrowsmall.setStyle('left:' + (w + 3) + 'px');
                h = button.positionedOffset()[1] + button.getHeight() + options.offsetTop;
                var l = (button.positionedOffset()[0] + button.getWidth() / 2) - (bubble.getWidth() / 2) + options.offsetLeft;
                bubble.setStyle('top:' + (h + 25) + 'px; left:' + l + 'px; opacity:0;');
                break;
        }
        return bubble;
    };
    
    var h;
    var dtime;
    button.bubblebox = false;
    var clickStop = function(){
        if (dtime) {
            clearTimeout(dtime);
        }
        (button.bubblebox &&
        button.bubblebox.shift({
            link: 'ignore',
            top: h + 25,
            opacity: 0,
            duration: 0.3,
            onEnd: function(e){
                button.bubblebox.remove();
                button.bubblebox = false;
            }
        }));
    };
    
    $(options.trigger ? options.trigger : button).mouseEnter(function(){
        if (button.hasClassName('dragging') || (button.hasClassName('drags') && button.style.position == 'absolute')) {
            clickStop();
            return;
        }
        dtime = setTimeout(function(){
            if (button.bubblebox !== false) {
                return;
            }
            if (button.hasClassName('button-over')) {
                return;
            }
            if(!button.parentNode || !button.parentNode.parentNode){
                return;
            }
            button.bubblebox = create();
            button.bubblebox.observe('click', clickStop);
            button.bubblebox.show().shift({
                link: 'ignore',
                top: h,
                opacity: 1,
                duration: 0.3
            });
            
            
            setTimeout(function(){
                clearTimeout(dtime);
                (button.bubblebox &&
                button.bubblebox.shift({
                    top: h + 25,
                    opacity: 0,
                    duration: 0.3,
                    onEnd: function(e){
                        button.bubblebox.remove();
                        button.bubblebox = false;
                    }
                }));
            }, 10000);
        }, options.delay ? options.delay * 1000 : 1000);
        
    }, function(el, e){
        
        if (dtime) {
            clickStop();
            clearTimeout(dtime);
        }
        (button.bubblebox &&
        button.bubblebox.shift({
            top: h + 25,
            opacity: 0,
            duration: 0.3,
            onEnd: function(e){
                button.bubblebox.remove();
                button.bubblebox = false;
            }
        }));
    });
    
    $(button).observe('click', clickStop);
}


function openStyleMenu(item, button){
    
    if (closeActiveButton(button) === false) {
        
        return false;
    }
    
    lastTool = button;
    
    button.addClassName('button-over');
    button.select('.big-button-text')[0].update('Close<br>Themes'.locale());
    
    $('accordion').hide();
    buildStyleMenu();
    $('style-menu').show();
}

function buildStyleMenu(button){
    $('style-content').setStyle('height:400px; overflow:auto;').update();
    
    $A(styles).each(function buildStyleLoop(style){
        var cont = new Element('div').setStyle('text-align:center; margin:4px; padding:2px; cursor:pointer;');
        cont.mouseEnter(function(){
            cont.setStyle('background:#ddd;');
        }, function(){
            cont.setStyle('background:transparent;');
        });
        cont.observe('click', function(){
            var old = form.getProperty('styles');
            if (old != style.value) {
                justSelectedATheme = true;
                form.setProperty('styles', style.value);
                updateValue('styles', style.value, false, form, old);
            }
        });
        var sshot = new Element('img', {
            align: 'absmiddle',
            src: style.image
        }).setStyle('border:1px solid #aaa;');
        
        cont.insert(sshot).insert('<br>').insert(style.text);
        $('style-content').insert(cont);
    });
    Utils.updateBuildMenusize();
}

/**
 *
 * @param {Object} id
 * @param {Object} index
 */
function openOptionEdit(id, index){
    if ($('label_input_' + id + '_' + index)) {
        $('label_input_' + id + '_' + index).run('click');
    } else if ($('label_input_' + id + '_' + (index - 1))) {
        $('label_input_' + id + '_' + (index - 1)).run('click');
    }
    
    $('id_'+id).run(selectEvent);
    
}


/**
 * Sets the options editable
 * @param {Object} id
 */
function setOptionsEditable(id, type){

    var sp = new Element('button', {
        type: 'button',
        className: 'big-button buttons add-button'
    }).setStyle({
        padding: '4px',
        margin: '15px 0px 0',
        cursor: 'pointer'
    });
    var addImg = new Element('img', {
        src: "images/add.png",
        align: "absmiddle"
    });
    sp.insert(addImg);
    sp.insert('Add New Option'.locale());
    var ne = getElementById(id);//$(l.parentNode.parentNode);
    if (!ne) {
        return;
    }
    
    var addNewOption = function(){
        setTimeout(function(){
            stopUnselect = true;
            var ops = (ne.getProperty('special') != 'None') ? Utils.deepClone(special_options[ne.getProperty('special')].value) : ne.getProperty('options').split('|');
            var old = ops.join('|');
            
            ops.push('Option'.locale() + (ops.length + 1));
            
            var val = ops.join('|');
            
            if (ne.getProperty('special') != 'None') {
                ne.setProperty('special', 'None');
            }
            document._onedit = false;
            
            var res = updateValue('options', val, $('id_' + id), ne, old, function(){
                openOptionEdit(id, ops.length);
            });
        }, 200);
    };
    
    sp.observe('mousedown', addNewOption);
    sp.hide();
    ne.insert(sp);
    
    // Get all option labels for this element
    $$('#id_' + id + ' .form-' + type + '-item label').each(function optionsLabelLoop(l, i){
        var parent = $(l.parentNode);
        
        l.editable({ // Make the label editable
            className: 'edit-option',
            //type:'textarea',
            //defaultText: l.innerHTML,
            processAfter: function(val, outer, value, oldValue){
                if(!val){
                    return oldValue;
                }
                return value;
            },
            onStart: function(el, val, inp){ // Add remove button
                inp.setStyle('width:100px; padding:0px; margin:0px;');
                
                var del = new Element('img', {
                    src: 'images/delete.png',
                    align: 'absmiddle'
                }).setStyle('margin-left:3px;');
                
                del.observe('mousedown', function(){
                    inp.value = " "; // Set the value empty before removing
                    inp.run('blur');
                    setTimeout(function(){ // Wait for 'editable' to complete it's job, then remove field
                        $(parent).remove();
                        onChange('option removed');
                    }, 20);
                });
                
                l.insert(del); // Insert del button
                
                if (inp.value.match(/Option\s+\d+/)) {
                    inp.value = "";
                }
            },
            onEnd: function(el, val, old, value){ // When value changed, read all options and update the value
            
                but = false;
                var values = [];
                $$('#id_' + id + ' .form-' + type + '-item label').each(function(rad){
                    var val = rad.innerHTML.strip();
                    if (val) {
                        values.push(val);
                    }
                });
                var ne = $(parent.parentNode.parentNode);
                old = ne.getProperty('options');
                if (ne.getProperty('special') != 'None') { // If user selected a special value, It should be disabled when user edits it.
                    ne.setProperty('special', 'None');
                }
                ne.setProperty('options', values.join('|'));
            }
        });
    });
}

/**
 * Create the Draggable Questions From Toolbar
 */
function createControls(){
    $$('.tools').each(function createControlsLoop(toolbox){
    
        Sortable.create(toolbox, {
            constraint: '',
            containment: [],
            revert: true,
            ghosting: true,
            starteffect: function(){return false;},
            scroll: window,
            onDrag: function(obj, e){
                if(!obj.element.__onrails && obj.element.hasClassName('dragging')){
                    obj.options.constraint = 'vertical';
                    obj.element.style.left = '0';
                    obj.element.__onrails = true;
                }
            },
            onChange: function(el){
                if(Sortable._emptyPlaceMarker){
                    Sortable._emptyPlaceMarker.setStyle({width:form.getProperty('formWidth')+"px"});
                }
                el.addClassName("dragging");
                el.setStyle({width:form.getProperty('formWidth')+"px"});
                return true;
                /*
                
                if (Sortable._guide) {
                    Sortable._guide.tools = true;
                }
                // Create a clone of the question when dragging 
                var t = el.readAttribute('type');
                
                var res = createDivLine(el, Utils.deepClone(default_properties[t]), true);
                el.update(res.container);
                
                res.elem.fire('on:render');
                
                el.setStyle({
                    padding: '10px',
                    width: parseInt(form.getProperty('formWidth'), 10) + 'px',
                    background: '#eee',
                    height: res.container.getHeight() + "px"
                });
                if (Sortable._guide) {
                    $(Sortable._guide).setStyle({
                        padding: '10px',
                        width: parseInt(form.getProperty('formWidth'), 10) + 'px',
                        //height: res.container.getHeight() + "px"
                    });
                }
                
                  
                el.addClassName('dragging');
                el.setStyle('width:' + parseInt(form.getProperty('formWidth'), 10) + "px");
                // */
            },
            onUpdate: function(){
                
	            if (Sortable._guide) {
                    Sortable._guide.tools = false;
                }
                handleNoSubmit();
                addQuestions();
                Sortable.destroy(toolbox);
                toolbox.innerHTML = toolboxContents[toolbox.id];
                createControls();
                setClicks();
                setControlTooltips();
              
            }
        });
        
    });
}

/**
 * Runs when toolbox items clicked twice
 */
function setClicks(){

    $$(".drags").each(function clicksLoop(el){
    
        if (el.clickSet) {
            return true;
        }
        
        var dblclick = false;
        el.observe("click", function(e){
            if (dblclick) { return; }
            
			if(e.element().hasClassName('info')){
				return;
			}
			
            dblclick = true;
            var cl = el.cloneNode(true);
            $('list').cleanWhitespace();
            if (selected) {
                $(selected).insert({after: cl});
            } else {
                if ($$('#list li:last-child')[0] && $$('#list li:last-child')[0].readAttribute('type') == 'control_button') {
                    $$('#list li:last-child')[0].insert({
                        before: cl
                    });
                } else {
                    $('list').insert(cl);
                }
            }
            
            handleNoSubmit();
            addQuestions();
            
            setTimeout(function(){
                dblclick = false;
            }, 1000);
        });
        el.clickSet = true;
    });
}

function handleNoSubmit(){

    if ($$('#list > li.form-line, #list > li.form-input-wide').length < 1 && !$$('#list .drags').collect(function(el){
        return el.readAttribute('type') == 'control_button';
    }).any()) {
        $('list').insert({
            bottom: new Element('li', {
                className: 'drags',
                type: 'control_button'
            })
        });
    }
}

/**
 * Update Question Orders
 * @param {Object} changetype
 */
function updateOrders(changetype){
    $$("div.question-input"/*"#list div[order=0]"*/).each(function updateOrdersLoop(el, i){
        el.setProperty("order", ++i);
    });
    if (changetype != "nochange") {
        onChange("Question order changed or new question added", true);
    }
}

/**
 * Collect all properties from questions in the form stage
 */
function getAllProperties(markDefaults){

    var allprop = {}, proparray = {};
    
    updateOrders("nochange");
    
    $$("div.question-input").each(function getAllPropertiesLoop(el, i){
        allprop[el.getProperty("order") + "-" + el.getProperty("type")] = $(el).retrieve("properties");
    });
    
    
    $H(allprop).each(function getAllPropertiesLoop2(prop){
        var type = prop.key.split('-')[1];
        $H(prop.value).each(function(kv){
        
            if (markDefaults && default_properties[type][kv.key] && default_properties[type][kv.key].value == kv.value.value) {
                // Place a default marker when a value is default. 
                // We don't want to fill database with stupid default values.
                proparray[prop.value.qid.value + "_" + kv.key] = '%%default%%';
            } else {
                proparray[prop.value.qid.value + "_" + kv.key] = kv.value.value;
            }
            
        });
    });
    
    $H(form.retrieve('properties')).each(function getAllPropertiesLoop3(prop){
    
        // TODO: put a default marker instead of the value if it's default
        if (markDefaults && default_properties.form[prop.key] && default_properties.form[prop.key].value === prop.value.value) {
            proparray["form_" + prop.key] = '%%default%%';
        } else {
            proparray["form_" + prop.key] = prop.value.value;
        }
    });
    
    proparray = Object.extend({
        form_height: $('stage').getHeight()
    }, proparray);
    
    return proparray;
}

/**
 * Run when something changed on the form
 * @param {Object} log
 * @param {Object} order
 */
function onChange(log, order){
    if (Utils.isFullScreen) {
        Utils.updateBarHeightInFullScreen();
    }
    changeFlag = true;
    $('log').setText(log);
    if (undoStack.length === 0) {
        undoStack.push(initialForm);
    } else {
        undoStack.push(lastChange);
    }
    redoStack = [];
    lastChange = {
        log: log,
        undo: getAllProperties()
    };
    $('stage').disableButton('redo');
    $('redoButton').disable(); // Redo button initially disabled

    $('redoicon').src = 'images/blank.gif';
    $('redoicon').className = 'toolbar-redo_disabled';
    
    $('stage').enableButton('undo');
    $('undoButton').enable();
    
    $('undoicon').src = 'images/blank.gif';
    $('undoicon').className = 'toolbar-undo';
    
    $('saveButton').shift({
        opacity: 1,
        duration: 0.5
    });
    $('save_button_text').innerHTML = "Save".locale();
    $('save_button_text').saved = false;
}

/**
 * Create the sortable form questions list.
 */
function createList(){
    var cont = $$('.tools').map(function toolsMapLoop(e){
        return e.id;
    });
    cont.push('list');
    
    Sortable.create("list", {
        containment: cont,
        constraint: 'vertical',
        starteffect: false,
        markDropZone: true,
        delay: Prototype.Browser.IE? false: 200,
        scroll:window,
        dropZoneCss: 'dropZone',
        dropOnEmpty: true, //$('list').select('li').length < 1 ? true : false,
        onDrag: function(obj){
            if(!obj.element.__onrails && obj.element.hasClassName('form-line-column')){
                obj.element.__onrails = true;
                obj.options.constraint = '';
            }else if(!obj.element.__onrails){
                obj.options.constraint = 'vertical';
            }
        },
        onChange: function(){
            if (!Sortable._guide.tools && Sortable._guide.style.width != '1px') {
                $(Sortable._guide).setStyle({
                    width: '1px',
                    height: '0px'
                });
            }
        },
        onUpdate: function(){
            updateOrders();
        }
    });
}

function getUserEmail(){
    if (Utils.user.email) {
        return Utils.user.email;
    }
    
    var emailFound = false;
    $A(form.getProperty('emails')).each(function getEmailsLoop(email){
    
        if (Utils.checkEmailFormat(email.from)) {
            emailFound = Utils.checkEmailFormat(email.from)[0];
            
            throw $break; // Email found
        }
        if (Utils.checkEmailFormat(email.to)) {
            emailFound = Utils.checkEmailFormat(email.to)[0];
            throw $break; // Email found
        }
    });
    
    return emailFound;
}

function updateEmails(){
    
    if ( form.getProperty('defaultEmailAssigned') != "Yes" && Utils.user.email && (!form.getProperty('emails') || form.getProperty('emails').length < 1)) {    
        var defEmail = {
            type: "notification",
            name: 'Notification',
            from: 'default',
            to: Utils.user.email,
            subject: "New submission: {form_title}".locale(),
            html: true,
            body: Utils.defaultEmail()
        };
        
        form.setProperty('emails', [defEmail]);
        form.setProperty('defaultEmailAssigned', 'Yes');
    }
    
    var emails = form.getProperty('emails');
    $A(emails).each(function updateEmailsLoop(email){
        if(!email.dirty && email.type == 'notification'){
            email.body = Utils.defaultEmail(email.type, !email.html);
        }
        
        /*if(!email.dirty && email.type == 'autorespond'){
            email.body = "Thank you for your submission.";
        }*/
    });
}

/**
 * Displays a quick login page with only a password
 * @param {Object} email
 * @param {Object} callback
 */
function quickLogin(email, callback){
    Utils.prompt("Hey! It seems that you already have an account with this email address. Login now and we will move these forms to your account.".locale()+
    "<br><br><b>"+"Enter your password to login:".locale()+"</b>", "", 
    "We seem to know you!".locale(), 
    function(password, c, button, passWin){
        if (button) {
            if (!password) {
                passWin.inputBox.addClassName('error');
                return false;
            }
            passWin.inputBox.removeClassName('error');
            
            Utils.Request({
                parameters: {
                    action: 'login',
                    username: email,
                    password: password
                },
                onComplete: function(res){
                    try {
                        if (res.success) {
                            $('myaccount').update(res.accountBox);
                            Utils.user = res.user;
                            Utils.user.usage = res.usage;
                            Locale.changeHTMLStrings();
                            callback(true);
                            passWin.close();
                        } else {
                            if (!passWin.inputBox.hasClassName('error')) {
                                passWin.inputBox.addClassName('error');
                                if (!passWin.inputBox.nextSibling) {
                                    passWin.inputBox.insert({
                                        after: new Element('span').insert(res.error.locale()).setStyle('font-size:9px; color:red;')
                                    });
                                }
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            });
            return false;
        } else {
            callback(false);
            return true;
        }
        return false;
    }, {
        okText: 'Login Now'.locale(),
        cancelText: 'Login Later'.locale(),
        fieldType: 'password',
        width: 400
    });
}

function publishWizard(){
    save(function(){
        Utils.loadScript('js/builder/publish_wizard.js', function(){ PublishWizard.openWizard(); });
    });
}

function finishWizard(type){
    save(function(){        
        var content = '<div style="text-align:center" id="complete-page">';
        content += '<h2>Your form is almost ready!</h2>';
        content += '<button class="big-button" id="open-emails"><img src="images/notification.png" /><br>Setup Email Notifications</button>';
        content += '<button class="big-button" id="open-thankyou"><img src="images/toolbar/thank_page.png" /><br>Setup Thank You Page</button>';
        content += '<button class="big-button" id="open-source"><img src="images/toolbar/code.png" /><br>Add It To Your Site</button>';
        content += '<br>';
        content += '<button class="big-button" id="open-submission"><img src="images/toolbar/myforms/submissions.png" /><br>See Your Submissions</button>';
        content += '<button class="big-button" id="open-reports"><img src="images/toolbar/myforms/reports.png" /><br>Create & Share Reports</button>';
        content += '</div>';
        document.window({
            title:'Finish Building Your Form',
            content: content,
            width: 500,
            height: 270,
            buttonsAlign:'center',
            onInsert: function(w){
                $('open-emails').observe('click', function(){
                    w.close();
                    makeTabOpen('form-setup-legend');
                    setTimeout(function(){
                        $('emailButton').click();
                    }, 500);
                });
                
                $('open-thankyou').observe('click', function(){
                    w.close();
                    setTimeout(function(){
                        $('thanksButton').click();
                    }, 500);
                });
                
                $('open-source').observe('click', function(){
                    w.close();
                    setTimeout(function(){
                        $('sourceButton').click();
                    }, 500);
                });
                
                $('open-submission').observe('click', function(){
                    w.close();
                    setTimeout(function(){
                        location.href = "submissions/"+formID;
                    }, 500);
                });
                
                $('open-reports').observe('click', function(){
                    w.close();
                    setTimeout(function(){
                        location.href = "myforms/#reports-"+formID;
                    }, 500);
                });
            },
            buttons: [{
                title:'Close',
                handler: function(w){
                    w.close();
                }
            }]
        });
    });
}


/**
 * Saves the form
 * @param {Object} callback function to run after save, if already saved then directly runs the function
 * @param {Object} auto is this auto save or what
 */
function save(callback, auto){

    // Already saving now
    if (saving) {
        return;
    }
    saving = true;
    
    if (!auto && !getUserEmail() && form.getProperty('emailAsked') != 'Yes') {
        Utils.prompt("<div style='line-height:11px;'><img src='images/notification.png' align='left' style='margin:5px 10px 0px 0px' /><br/>"+
                    "To receive responses for this form, enter your e-mail address:".locale()+"</div>",
                    "Enter your email here!".locale(), "Receive Form Responses by Email".locale(),
        function(email, a, b, win){
            if (email) {
                if (!Utils.checkEmailFormat(email)) {
                    if (!win.inputBox.hasClassName('error')) {
                        win.inputBox.addClassName('error');
                        win.inputBox.insert({
                            after: new Element('span').insert('Please enter a valid address'.locale()).setStyle('font-size:9px; color:red;')
                        });
                    }
                    return false;
                }
                var emails = form.getProperty('emails');
                if (emails[0]) {
                    emails[0].to = email;
                    emails[1].from = email;
                    // Put them back                    
                }
                
                Utils.user.email = email;
                Utils.Request({
                    parameters: {
                        action: 'setGuestEmail',
                        email: email
                    },
                    onComplete: function(res){                        
                        if (res.success) {
                            if (res.hasAccount) {
                                quickLogin(email, function(loggedin){
                                    form.setProperty('emailAsked', 'Yes');
                                    saving = false;
                                    save(callback);
                                });
                            } else {
                                form.setProperty('emailAsked', 'Yes');
                                saving = false;
                                save(callback);
                            }
                            // Utils.setGoal('Email Entered', 'Funnel');
                        } else {
                            Utils.alert(res.error, 'Error!!');
                        }
                    }
                });
                
            } else {
                form.setProperty('emailAsked', 'Yes');
                saving = false;
                save(callback);
            }
        }, {
            okText: 'Save E-mail Address'.locale(),
            cancelText: 'Do not send notifications'.locale(),
            width: 450
        });
        saving = false;
        return false;
    }
    
    // If it's an auto save but there is no question then don't save
    if(auto && $$('.form-line, .form-input-wide').length < 1){
        //(callback && callback());
        saving = false;
        return;
    }
    
    updateEmails();
    
    if ($('save_button_text').saved && callback && formID) {
        try {
            callback();
        }catch(e){ console.error(e); }
        saving = false;
        return; // Already saved no need to save again
    }
    
    if (!form.getProperty('slug') && formID) {
        form.setProperty('slug', formID);
    }
    var prop = getAllProperties();
    BuildSource.init(prop);
    
    $('saveIcon').src = 'images/loader-big.gif';
    $('saveIcon').removeClassName('toolbar-save');
    Utils.Request({
        method: 'post',
        parameters: {
            action: 'saveForm',
            formID: formID,
            source: BuildSource.getCode({
                type: 'css',
                config: false,
                pagecode: true
            }),
            properties: Object.toJSON(prop)
        },
        onComplete: function(res){
            
	        changeFlag = false;
            $('saveIcon').src = 'images/blank.gif';
            $('saveIcon').className = 'toolbar-save';
            
            if (!res.success) {
                Utils.alert(res.error, "Error on save".locale());
                return;
            }
            // Set the id of newly created form
            formID = res.id;
            form.setProperty('id', formID);
            
            if(form.getProperty('currentIndexChanged') !== false){
                // if auto increment field is here and current index property was set.
                // set it to no after save
                form.setProperty('currentIndexChanged', 'no');
            }
            
            $('saveButton').shift({
                opacity: 0.5,
                duration: 0.5
            });
            
            $('save_button_text').innerHTML = "Saved".locale();
            $('save_button_text').saved = true;
            
            if (callback) {
                try {
                    callback(res);
                }catch(e){ console.error(e); }
            }
            //Utils.setGoal('Form Save','Funnel');
        }
    });
    saving = false;
}

function openInTab(){
    Utils.redirect(Utils.HTTP_URL + "form/" + formID, {
        target: '_blank'
    });
}
// Share, code, preview
function sourceOptions(type){
    
	
    if(type == "preview"){
        $('preview-close').onclick();
    } 
    if (type === "share"){
	    return save(function(){
	        Utils.loadScript('js/wizards/share_wizard2.js', function(a){
	            new ShareWizard(a);
	        }, {type: type});
	    });
    } else if(type === "full"){
        return save(function(){
            var s = BuildSource.getCode({type:'css'});
            // Include the code mirror script on the page
            Utils.require('opt/codemirror/js/codemirror.js', function(){
                // Create a window
                document.window({
                    title: 'Source Code'.locale(),
                    
                    content:'<img src="images/icon_share_code.gif" align="left" style="margin-left: -5px;margin-right: 9px;" />'+
                    '<h3 style="margin:4px 0 0;">'+'Here is the source code for of form.'.locale()+'</h3>'+
                    '<p style="margin:0px;font-size:10px;">'+'If you want to embed this form to your site you should check out <a>Share Wizard</a>:'.locale()+'</p>'+
                    '<br clear="all"/>'+
                    '<div style="background:#fff;border:1px solid #000;">'+
                        // '<input onclick="this.select();" type="text" id="mainURLSource" value="'+ s.escapeHTML().replace(/\"/gim, '&quot;') +'" style="width:430px; padding:2px; font-size:11px;" readonly="readonly" />'+
                        '<textarea id="full-source" id="code" wrap="off"></textarea>'+
                    '</div>',
                    
                    width:700,
                    contentPadding:30,
                    onInsert: function(){
                        // When code included
                        var base = Utils.HTTP_URL;
                        $('full-source').value = s;
                        var ceditor = CodeMirror.fromTextArea('full-source', {
                            height: "350px",
                            
                            parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js",
                                         "../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js",
                                         "../contrib/php/js/parsephphtmlmixed.js"],
                            stylesheet: [
                                base+"/opt/codemirror/css/xmlcolors.css",
                                base+"/opt/codemirror/css/jscolors.css",
                                base+"/opt/codemirror/css/csscolors.css",
                                base+"/opt/codemirror/contrib/php/css/phpcolors.css"
                            ],
                            path: base+"/opt/codemirror/js/",
                            readOnly:true,
                            lineNumbers: true,
                            textWrapping:false,
                            indentUnit: 4,
                            continuousScanning: 500
                        });
                    },
                    buttons:[{
                        title:'Close'.locale(),
                        align:"right",
                        handler:function(w){
                            w.close();
                        }
                    },{
                        title:'Embed Options'.locale(),
                        align:'left',
                        color:'green',
                        handler:function(w){
                            w.close();
                            sourceOptions('share');
                        }
                    }]
                });
            });
        });
    
	}else{
	    return save(function(){
            var s = BuildSource.getCode('jsform');
            
            document.window({
                content:'<img src="images/icon_share_code.gif" align="left" style="margin-left: -5px;margin-right: 9px;" />'+
                '<h3 style="margin:4px 0 0;">'+'Paste this code on your web site'.locale()+'</h3>'+
                '<p style="margin:0px;font-size:10px;">'+'Copy following code and paste it into your web page to show your form:'.locale()+'</p>'+
                '<br clear="all"/>'+
                '<div>'+
                    '<input onclick="this.select();" type="text" id="mainURLSource" value="'+ s.escapeHTML().replace(/\"/gim, '&quot;') +'" style="width:430px; padding:2px; font-size:11px;" readonly="readonly" />'+
                '</div>',
                title: 'Source Code'.locale(),
                width:500,
                contentPadding:30,
                buttons:[{
                    title:'Close'.locale(),
                    align:"right",
                    handler:function(w){
                        w.close();
                    }
                },{
                    title:'Embed Options'.locale(),
                    align:'left',
                    color:'green',
                    handler:function(w){
                        w.close();
                        sourceOptions('share');
                    }
                }]
            });
	    });
	}
}

/**
 * Save and preview the form
 */
function preview(button){
    
    save(function(){
        if(!formID && $$('#stage li').length < 1){
            Utils.alert("Please add a question to preview your form.");
            return;
        }
        
        var formBackground = form.getProperty('background');
        if (!formBackground) {
            formBackground = 'white';
        }
        var formWidth = (Number(form.getProperty('formWidth')) + 31); //31 works best for both IE and FF
        var formHeight = $('stage').getHeight();
        var preview = new Element('div');
        preview.insert('<h3 style="margin-bottom:2px;display:block;max-width:200px">'+'Preview'.locale()+
                       '</h3><label>'+'Form URL'.locale()+
                       '</label><input onfocus="this.select()" onclick="this.select()" size="40" readonly='+
                       '"readonly" style="margin-bottom:5px;margin-right:5px;" type="text" value="' + Utils.HTTP_URL + 'form/' + formID + '" />');
        preview.insert('<button class="big-button buttons" style="margin-left:0px;margin-right:5px;" onclick="openInTab()">'+'Open in new tab'.locale()+'</button>');
        preview.insert('<button class="big-button buttons" style="" onclick="sourceOptions(\'preview\')">'+'Form Source'.locale()+'</button>');
        
        var iframe = new Element('iframe', {
            name: 'preview_frame',
            id: 'preview_frame',
            src: 'form/' + formID + '?prev',
            allowtransparency: true,
            frameborder: 0
        }).setStyle({
            //border: '1px solid #aaa',
            border: '1px solid #aaa',
            width: formWidth + 'px',
            height: '500px',
            background: formBackground
        });
        
        preview.insert(iframe);
	    Utils.lightWindow({
            content: preview,
            width: formWidth,
            height: formHeight,
            onReCenter: function(height, width){
                iframe.setStyle({
                    height: height - 90 + 'px'
                });
            },
            onClose: function(){
                var frame = window.frames.preview_frame;
                // You might not be able to access the iframe if it is on a different domain.
                // Just remove the container div in that case.
                try {
                if (frame && frame.document) {
                    frame.document.close();
                }
                } catch(e) {}
            }
        });
    });
}

/**
 * Get saved array then convert it to new form builders way.
 * @param {Object} arr
 */
function convertSavedToProp_old(arr){
    pt.start("Convert Properties");
    var prop = {};
    var ps, id, pname, pvalue, key, type;
    
    for (var k in arr) {
        ps = k.split("_");
        id = ps[0];
        pname = ps[1];
        pvalue = arr[k];
        key = "id_" + id;
        type = arr[id + "_type"];
        
        if (id == "form") {
            if (!formProps) { // if fromProp is empty
                formProps = Utils.deepClone(default_properties.form);
            }
            if (!(pname in formProps)) { // if current property is not default
                formProps[pname] = {
                    hidden: true,
                    value: pvalue
                }; // create it as hidden
                continue;
            } else {
                formProps[pname].value = pvalue; // change the default with current value
                continue;
            }
        } else {
            if (!(key in prop)) { // if no id was presented create one
                prop[key] = Utils.deepClone(default_properties[type]);
            }
            
            if (!prop[key]) {
                continue;
            }
            
            if (!(pname in prop[key])) { // if current property is not default
                prop[key][pname] = {
                    hidden: true,
                    value: pvalue
                }; // create it as hidden
                continue;
            } else {
                prop[key][pname].value = pvalue; // change the default with current value
                continue;
            }
        }
    }
    pt.end("Convert Properties");
    return prop;
}

/**
 * Create the saved questions with their properties
 * @param {Object} config
 */
function buildQuestions(config){
    pt.start('Build Questions');
    
    // sort element by order then create questions 
    $H(config)./*sortBy(function(p){ //  We don't need to sort question anymore
        return parseInt(p.value ? p.value.order.value : 0, 10);
    }).*/each(function buildQuestionsLoop(pair){
        var li = getElement('li');
        li.className = 'drags';
        
        li.writeAttribute('type', pair.value ? pair.value.type.value : "");
        
        $('list').appendChild(li);
        createDivLine(li, pair.value);
    });
    createList();
    pt.end('Build Questions');
}

/**
 * Undo action
 */
function undo(){
    // If there is nothing to undo then do nothing
    if (undoStack.length === 0) {
        return;
    }
    
    // If this was the last Item then disable the button
    if (undoStack.length == 1) {
        $('stage').disableButton('undo');
        $('undoButton').disable();

        $('undoicon').src = 'images/blank.gif';
        $('undoicon').className = 'toolbar-undo_disabled';
    }
    
    // Get last change 
    var undoInfo = undoStack.pop();
    // Convert to usable element
    var undo_val = BuildSource.convertSavedToProp(undoInfo.undo);
    
    // Add current form into the redo stack
    redoStack.push({
        log: 'Redo',
        undo: getAllProperties()
    });
    
    // Re enable the redo button
    $('stage').enableButton('redo');
    $('redoButton').enable();
    
    $('redoicon').src = 'images/blank.gif';
    $('redoicon').className = 'toolbar-redo';
    
    // Set undo log in screen
    $('log').setText(undoInfo.log);
    
    // Apply the undo chnages
    $("list").innerHTML = "";
    applyFormProperties(undo_val);
}

/**
 * Redo action
 */
function redo(){
    // If there is nothing to redo then do nothing
    if (redoStack.length === 0) {
        return;
    }
    
    // If this was the last Item then disable the button
    if (redoStack.length == 1) {
        $('stage').disableButton('redo');
        $('redoButton').disable();

        $('redoicon').src = 'images/blank.gif';
        $('redoicon').className = 'toolbar-redo_disabled';
    }
    // Get latest change from redo
    var redoInfo = redoStack.pop();
    
    // Convert changes to usable config
    var redo_val = BuildSource.convertSavedToProp(redoInfo.undo);
    
    // Add current form to undoStack
    undoStack.push({
        log: 'Undo',
        undo: getAllProperties()
    });
    
    // Enable undo button again
    $('stage').enableButton('undo');
    $('undoButton').enable(); // undo button initially disabled

    $('undoicon').src = 'images/blank.gif';
    $('undoicon').className = 'toolbar-undo';
    
    // Print redo information on the screen
    $('log').setText(redoInfo.log);
    
    // Apply the changes 
    $("list").innerHTML = "";
    applyFormProperties(redo_val);
}

/**
 * Opens the form properties
 */
function formProperties(){
    makeToolbar(form, false);
    if (selected && selected.parentNode) {
        selected.removeClassName('question-selected');
        selected.picked = false;
        
        selected.select('.add-button').invoke('hide');
        selected.delButton.hide();
        selected = false;
    }
}

/**
 * Sets the properties and methods of the form
 */
function setForm(){
    form = $('stage');
    var p = formProps || Utils.deepClone(default_properties.form);
    form.store('properties', p);
    
    $('form-title').innerHTML = p.title.value;
    
    
    /**
     * Reads the property of the element
     * @param {Object} key
     */
    form.getProperty = function(key){
        try {
            var pr = form.retrieve("properties");
            if (pr[key] && typeof pr[key].value == "string") {
                var unit = pr[key].unit || "";
                if(unit){
                    return parseInt(pr[key].value, 10);
                }
                return pr[key].value;
            }
            return pr[key] ? pr[key].value : false;
        } 
        catch (e) {
            //console.error(e);
            return false;
        }
    };
    /**
     * Sets a property to element
     * @param {Object} key
     * @param {Object} value
     */
    form.setProperty = function(key, value){
        
        if(key == 'injectCSS'){
            value = value.stripTags();
        }
        
        var pr = form.retrieve("properties");
        if (!(key in pr)) { // If key doesn't exist in default property then create it as a hidden value
            pr[key] = {
                value: "",
                hidden: true
            };
        }
        var unit = pr[key].unit || "";
        if (typeof value == "string") {
            if(unit){
                pr[key].value = parseInt(value, 10) + unit;
            }
            pr[key].value = value;
        } else {
            pr[key].value = value;
        }
        
        // If form collapse is set to hidden then it should be set to open
        if(key == 'visibility' && value == 'Hidden'){
            pr.status.value = 'Open';
        }
        
        BuildSource.config['form_' + key] = value;
        form.store("properties", pr); // re-store modified data
        return value;
    };
}

var justSelectedATheme = false;
/**
 * Applys the form properties to the form when they changed
 * @param {Object} prop     The property that must be changed
 * @param {Object} noBuild  This comes true when the thing that
 *                          will change is about style.
 */
function applyFormProperties(prop, noBuild){
    
    // what build searching for this!
    if (!noBuild) {
        if (!prop) {
            prop = BuildSource.convertSavedToProp(getAllProperties());
        }
        $("list").innerHTML = "";
        buildQuestions(prop);
    }
    
    // get all properties
    var props = form.retrieve('properties');
    
    // change the form title. why here why?????
    $('form-title').innerHTML = props.title.value;
    
    // Styles
    // add double quotes to the name of the family if its more than one words (mean if there is a space
    // than add double quotes.)
    // But why have to we check this. Can't we just put double quotes in every value. 
    var family = (props.font.value.match(/\s/g)) ? '"' + props.font.value + '"' : props.font.value;
    var list = $('list');
    var main = $('stage');
    // if its not the last style
    if (props.styles.value != lastStyle) {
    
        $('formcss').href = Utils.HTTP_URL + "css/styles/" + props.styles.value + ".css?" + Utils.session;
        lastStyle = props.styles.value;
        // Remove Old styles
        list.removeAttribute('style');
        // comment out by Seyhun. Please contact me if you want to enable this again.
        // I have commented out this because while changing thema in full screen this was
        // removing the height and width of the main panel.
        //main.removeAttribute('style');
        
        Utils.cssloaded = false;
        // Find the onload or something to 100% make sure CSS is loaded
        Utils.tryCSSLoad(function(){
            // Wait until css applied then change the style menu size
            setTimeout(function(){
                Utils.updateBuildMenusize();
            }, 200);
            setTimeout(function(){
                var s = Utils.getStyleBySelector('.form-all');
                if (s && justSelectedATheme) {
                    // console.log(s.fontFamily, s.fontSize, s.color, "dd--", s.background);
                    form.setProperty('font', s.fontFamily.replace(/\"/g, ""));
                    form.setProperty('fontsize', s.fontSize);
                    form.setProperty('fontcolor', s.color);
                    form.setProperty('background', s.background);
                    main.setStyle({ background: s.background });
                    justSelectedATheme = false;
                }
            }, 300); // Wait until all CSS is updated
        });
        
        list.setStyle({
            fontSize: parseInt(props.fontsize.value, 10) + "px",
            width: parseInt(props.formWidth.value, 10) + 'px'
        });
        
    } else {
        // changing font size and that stuffs are here.
        list.setStyle({
            fontFamily: family,
            fontSize: parseInt(props.fontsize.value, 10) + "px",
            color: props.fontcolor.value,
            background: props.background.value,
            width: parseInt(props.formWidth.value, 10) + 'px'
        });
    }
    
    var lc = form.getProperty('lineSpacing');
    var lc_margin, lc_padding;

    if(lc < 4){
        lc_margin  = 0;
        lc_padding = lc;
    }else{
        lc_margin  = Math.floor((lc - 2)/2);
        lc_padding = lc-lc_margin;
    }
    
    Utils.createCSS(".form-line", 
        'padding-top: '+lc+'px !important;'+
        'padding-bottom: '+lc+'px !important;'
        //+
        //'margin: '+lc_margin+'px !important;'+
        // In order to fix margin collapse between lines
        //'margin-top: '+(lc_margin*2)+'px !important;'+
        //'margin-bottom: '+(lc_margin*2)+'px !important;'
    );
    
    $$('style[id*=stage]').invoke('remove');
    
    if(props.injectCSS.value){
        var cssArr = Utils.getCSSArray(props.injectCSS.value);
        $H(cssArr).each(function(pair){
            Utils.createCSS("#stage "+pair.key,  pair.value);
        });
    }
    
    if (props.background.value) {
        main.setStyle({
            background: props.background.value
        });
    }
}

/**
 * Toggles the setup menu
 */
function toggleSetupMenu(button){
    if ($('group-setup').style.display == "none") {
    
        if (selected) {
            selected.run(selectEvent);
        }
        if (Object.isElement(button)) {
            button.addClassName('button-over');
        }
        $('group-setup').show();
    } else {
        if (Object.isElement(button)) {
            button.removeClassName('button-over');
        }
        $('group-setup').hide();
    }
}

function setControlTooltips(){
    $$('#accordion .drags').each(function tooltipsLoop(el){
        
        var type = el.readAttribute('type');
        
        if(!(type in control_tooltips)){
            return;
        }
        
        if (el.tooltipset) {
            return;
        }
        var type = el.readAttribute('type');
        
        var tooltip = Object.extend({
            image: type + '.png',
            title: el.select('span')[0] ? el.select('span')[0].innerHTML : 'Not Found'
        }, control_tooltips[type]);
        
        
        el.tooltipset = true;
        
        buttonToolTips(el, {
            offsetTop: 10,
            //delay:1,
            trigger: el.select('.info')[0],
            message: '<div class="control-tooltip-text">' +
                tooltip.tip +
            '</div><div style="margin-top:4px;">' +
                'Example'.locale() +
            ':<br><img class="tooltip-preview" src="' + Utils.HTTP_URL + 'images/tool_previews/' + 
            tooltip.image + '?2" /></div>',
            arrowPosition: 'top',
            width: 210,
            title: tooltip.title
        });
    });
}
var loadTimer = false;
loadTimer = setTimeout(function(){
    ($('load-bar') && $('load-bar').update("OK. This is taking a while. Please hang on!".locale()));
    loadTimer = setTimeout(function(){
        ($('load-bar') && $('load-bar').update("Wow! This must be a huge form. We are almost there.".locale()));
        loadTimer = setTimeout(function(){
            ($('load-bar') && $('load-bar').update("Ok! I give up. I wasn't able to load this form. Please try it later.".locale()));
        }, 30000);
    }, 10000);
}, 5000);

($('load-bar') && $('load-bar').show());

function initiate(){
    
    if (!navigator.cookieEnabled) {
        Utils.alert('In order to use JotForm, you must enable <b>cookies</b> otherwise your work cannot be saved and you will lose all your changes.'.locale() + '</br>' +
        '<a target="_blank" href="http://www.google.com/support/websearch/bin/answer.py?hl=en&answer=35851"> ' +
        'For more information.'.locale() +
        '</a>');
    }
    
    try {
        pt.start('Initialize');
        //console.profile('init');
        BuildSource.init(savedform);
        
        // If this was a saved form the open form setup initially
        if (formID) {
            $('form-setup-legend').run('click');
        }
        
        pt.start('Form Show');
        // Convert saved data to form builder data
        var convertedProp = BuildSource.convertSavedToProp(savedform);
        // Set form properties and methods on form
        setForm(); // Then create the saved questions
        // Apply the saved form settings to form
        applyFormProperties(convertedProp);
        clearTimeout(loadTimer);
        // Now that the form is created. Display the hidden bars
        makeToolbar(form);
        pt.end('Form Show');
        
        // Collect the default contents of the toolboxes 
        $$('.tools').each(function toolsLoop(toolbox){
            toolboxContents[toolbox.id] = toolbox.innerHTML;
        });
        
        // Set initial form state for undo stack
        initialForm = {
            log: "Initial form",
            undo: savedform
        };
        //console.profileEnd('init');
        setTimeout(function(){
            createControls(); // Create draggable questions on toolboxes
            setClicks(); // Add duble click insertion ability on questions
            // Undo button initially disabled
            $('undoButton').disable();
            
            $('undoicon').src = 'images/blank.gif';
            $('undoicon').className = 'toolbar-undo_disabled';
            
            // Redo button initially disabled
            $('redoButton').disable();
            
            $('redoicon').src = 'images/blank.gif';
            $('redoicon').className = 'toolbar-redo_disabled';
            
            Utils.setAccordion($('accordion'), {
                openIndex: 0
            }); // Set accordion functionality to toolbox. Make first bar open (0)
            Utils.setToolbarFloat();
            Utils.setToolboxFloat();
            
            Utils.fullScreenListener();
            
            $('group-form', 'group-setup', 'accordion').invoke('cleanWhitespace');
            $$('.big-button').invoke('cleanWhitespace');
            
            Protoplus.ui.setContextMenu('stage', {
                title: 'Form Actions'.locale(),
                //others:[ $('stage') ],
                onOpen: function(){
                    if(noAutoSave){
                        $('stage').getButton('easave').show();
                        $('stage').getButton('dasave').hide();
                    }else{
                        $('stage').getButton('easave').hide();
                        $('stage').getButton('dasave').show();
                    }
                },
                menuItems: [{
                    title: 'New Form'.locale(),
                    icon: 'images/blank.gif',
                    iconClassName: "context-menu-add",
                    name: 'newForm',
                    handler: function(){
                        Utils.loadScript("js/builder/newform_wizard.js", function(){
                            openNewFormWizard();
                        });
                    }
                }, {
                    title: 'Save'.locale(),
                    icon: 'images/blank.gif',
                    iconClassName: "context-menu-disk",
                    name: 'save',
                    handler: function(){
                        save();
                    }
                }, {
                    title: 'Enable Auto Save'.locale(),
                    icon: 'images/context-menu/auto-on.png',
                    name: 'easave',
                    hiden:true,
                    handler: function(){
                        document.eraseCookie('no-auto-save');
                        noAutoSave = false;
                    }
                },{
                    title: 'Disable Auto Save'.locale(),
                    icon: 'images/context-menu/auto-off.png',
                    name: 'dasave',
                    handler: function(){
                        noAutoSave = true;
                        document.createCookie('no-auto-save', 'yes');
                    }
                }, {
                    title: 'Preview'.locale(),
                    name: 'preview',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-preview',
                    handler: function(){
                        preview();
                    }
                }, {
                    title: 'Show Transparency'.locale(),
                    name: 'transback',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-transparent',
                    handler: function(){
                        if ($('stage').hasClassName('trans-back')) {
                            $('stage').removeClassName('trans-back');
                            this.changeButtonText('transback', 'Show Transparency'.locale());
                        } else {
                            $('stage').addClassName('trans-back');
                            this.changeButtonText('transback', 'Hide Transparency'.locale());
                        }
                    }
                }, '-', {
                    title: 'Undo'.locale(),
                    name: 'undo',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-undo',
                    disabled: true,
                    handler: function(){
                        undo();
                    }
                }, {
                    title: 'Redo'.locale(),
                    name: 'redo',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-redo',
                    disabled: true,
                    handler: function(){
                        redo();
                    }
                }, '-', {
                    title: 'Setup E-mails'.locale(),
                    name: 'emails',
                    iconClassName: "context-menu-emails",
                    icon: 'images/blank.gif',
                    handler: function(){
                        emailList($('emailButton'));
                    }
                }, {
                    title: 'Embed Form'.locale(),
                    name: 'share',
                    icon: 'images/blank.gif',
                    iconClassName: 'context-menu-share',
                    handler: function(){
                        save(function(){
                        	sourceOptions('share');
                        });
                    }
                }]
            });
            
            // Set form title to be editable
            Protoplus.ui.editable('form-title', {
                className: 'edit-title',
                processBefore: function(val){
                    $('title-hint').hide();
                    return val;
                },
                onEnd: function(el, new_val, old_val){
                    // See if the title changed.
                    if (new_val != old_val) {
                        form.setProperty('title', new_val);
                        onChange('Form Title changed');
                    }
                }
            }).mouseEnter(function(){
                if (document._onedit) {
                    $('title-hint').hide();
                } else {
                    $('title-hint').show();
                }
            }, function(){
                $('title-hint').hide();
            });
            
            $('title-hint').onclick = function(){
                $('title-hint').hide();
                $('form-title').run('click');
            };
            
            pt.end('Initialize');
            
            // Disable Auto Save
            if(document.readCookie('no-auto-save') == 'yes'){
                noAutoSave = true;
            }
            
            // Auto save form automatically in every 60 sec.
            setInterval(function(){
                if(noAutoSave == true){
                    return true;
                }
                if (!$('save_button_text').saved) {
                    //if (getUserEmail() || form.getProperty('emailAsked') == 'Yes' || Utils.user.accountType != 'GUEST') {
                    save(function(){
                        $('save_button_text').update('Auto Saved'.locale());
                        $('save_button_text').saved = true;
                    }, true);
                    //}
                }
            }, 60000);
            
            $('stage', 'list').invoke('observe', 'click', function(e){
                if (e.target.id != 'stage' && e.target.id != 'list') {
                    return;
                }
                unselectField();
            });
            
            if (document.readCookie('formIDRenewed')) {
                document.eraseCookie('formIDRenewed');
                Utils.alert("Your form ID is successfully renewed. Please open Share Wizard again to update your form source and for your new Form URL");
            }
            
            setControlTooltips();
            
            // @TODO: set the tips here (ask serkan to fix this later)
            var tempButtons = [{
                button: $('emailButton'),
                message: 'Send notification and confirmation emails on submissions'.locale()
            }, {
                button: $('thanksButton'),
                message: 'Redirect user to a page after submission'.locale()
            }, {
                button: $('sourceButton'),
                message: 'Add form to your website or send to others'.locale()
            }, {
                button: $('propButton'),
                message: 'Update Form Settings'.locale()
            }, {
                button: $('condButton'),
                message: 'Setup Conditional Fields'.locale()
            }];
            
            for (var i = 0, l = tempButtons.length; i < l; i++) {
                var pair = tempButtons[i];
                buttonToolTips(pair.button, {
                    message: pair.message,
                    arrowPosition: 'top',
                    offset: 10
                });
            }
        }, 0);
        
    } 
    catch (e) {
        // Hidden element for selenium
        $(document.body).insert(new Element('div', {
            id: 'error-div'
        }));
        console.error(e);
    }
}

/**
 * Display list of question names in a table
 */

function displayQuestionInfo(){
    var qs = getUsableElements();
    table = '<table width="100%" class="prop-table" cellspacing="0" cellpadding="4">';
    qs.each(function(q){
        table += ('<tr><td class="prop-table-label">'+q.getProperty('text')+'</td><td class="prop-table-value">{'+q.getProperty('name')+'}</td></tr>');
    });
    table += '</table>';
    document.window({
        title:'Question Names',
        content: table,
        modal: false,
        buttonsAlign:'center',
        buttons:[{
            title:'Close',
            handler: function(w){
                w.close()
            }
        }]
    })
}
/**
 * Start everything when the document is ready
 */
document.ready(initiate);
