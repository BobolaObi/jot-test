/**
 * Create question name from title
 * @param {Object} text
 * @param {Object} id
 */
function makeQuestionName(text, id){

    var name = text.stripTags().replace('&nbsp;', '');
    var tokens = name.split(/\s+/);
    name = ((tokens["1"]) ? tokens["0"].toLowerCase() + (tokens["1"].toLowerCase()).capitalize() : tokens["0"].toLowerCase()).fixUTF();
    name = name.replace(/\W/gim, '');
    
    return fixQuestionName(name, id);
}

var JotForm = {
    totalCounter: function(prices){
    
    }
};
var htmlCommentMatch = /\<![ \r\n\t]*(--([^\-]|[\r\n]|-[^\-])*--[ \r\n\t]*)\>/gim;
/**
 * Fixes the name for duplications
 * @param {Object} name
 * @param {Object} id
 */
function fixQuestionName(name, id){
    if (name.empty()) {
        return id;
    }
    var allProp = getAllProperties();
    
    var allNames = $H(allProp).map(function(pair){
        if (pair.key.match('_name')) {
            return pair.value;
        }
    }).compact();
    
    if (allNames.include(name)) {
        return name + id;
    }
    
    return name;
}

/**
 * Creates the input element by given question type
 * @param {Object} type
 * @param {Object} id
 * @param {Object} prop
 */
function createInput(type, id, prop, noreplace){
    
    var ne = getElement('div');
    if (!noreplace) {
        ne.className = "question-input";
    }
    var el = BuildSource.createInputHTML(type, id, prop, ne);
    ne.insert(el.html);
    
    ne.observe('on:render', function(){
        /*if (el.script) {
           // eval(el.script);
           var tmpFunc = new Function(el.script);
           tmpFunc(); 
        }*/
    });
    
    prop = el.prop;
    /**
     *
     * @param {Object} name
     */
    prop.getItem = function(name){
        return this[name].value;
    }.bind(prop);
    
    var name = prop.name ? prop.name.value : "";
    
    if (!prop.name) {
        // TODO: Move this code to label edit place
        name = makeQuestionName(prop.text.value, id);
        
        prop.name = {
            hidden: true,
            value: name
        };
        
    }
    
    // New Prototype.js functionality: Just when we need it :)
    // Store the proprties in elements DOM
    $(ne).store("properties", prop);
    
    /**
     * Reads the property of the element
     * @param {Object} key
     */
    ne.getProperty = function(key){
        try {
            return prop[key] ? prop[key].value : false;
        } 
        catch (e) {
            return false;
        }
    };
    
    /**
     * Sets a property to element
     * @param {Object} key
     * @param {Object} value
     */
    ne.setProperty = function(key, value){
        // Make undefined blank
        if(value === undefined){ value = ""; }
        
        var pr = prop; //$(ne).retrieve("properties");
        if (key == 'name' && pr[key].value) { // If question name is changing then change all question names used in emails
            fixQuestionNamesInEmails(pr[key].value, value);
        }
        
        // These values must be cleared for XSS attacks remove all scripts in these values
        // If it causes some problems in the frature we may remove HTML tags too
        if(['text', 'options', 'items', 'description', 'subHeader'].include(key)){
            value = value.stripScripts();
        }
        
        if(key == 'text'){
            value = value.replace(htmlCommentMatch, ''); // Remove All comments
            value = BuildSource.cleanWordFormat(value);  // Cleans up all unnecessary word format
        }
        
        if (!(key in pr)) { // If key doesn't exist in default property then create it as a hidden value
            pr[key] = {
                value: "",
                hidden: true
            };
        }
        
        // If a value (different than the old one) is set to currentIndex then mark it as changed
        if(key == 'currentIndex' && pr[key].value != value){
            form.setProperty('currentIndexChanged', 'yes');
        }
        
        BuildSource.config[id + '_' + key] = value;
        pr[key].value = value;
        $(ne).store("properties", pr); // re-store modified data
        return value;
    };
    
    ne.setProperty("qid", id); // Set questionID
    ne.setProperty("type", type); // Set questionType
    return ne;
}

function fixQuestionNamesInEmails(oldName, newName){
    var emails = form.getProperty('emails');
    $A(emails).each(function(email, i){
        emails[i].from = email.from ? email.from.replace(new RegExp('\\{' + oldName + '\\}', 'gim'), '{' + newName + '}') : false;
        emails[i].to = email.to ? email.to.replace(new RegExp('\\{' + oldName + '\\}', 'gim'), '{' + newName + '}') : false;
        emails[i].subject = email.subject ? email.subject.replace(new RegExp('\\{' + oldName + '\\}', 'gim'), '{' + newName + '}') : false;
        emails[i].body = email.body ? email.body.replace(new RegExp('\\{' + oldName + '\\}', 'gim'), '{' + newName + '}') : false;
    });
    form.setProperty('emails', emails);
}

/**
 * Creates the sub labels under the inputs
 * @param {Object} input
 * @param {Object} label
 * @param {Object} link
 */
BuildSource.subLabel = function(input, label, link, labelName){
    var htmlFor = "";
    var htmlId  = "";
    
    var html = '<span class="form-sub-label-container">';
    html += input;
    
    
    if(label !== undefined && typeof label !== "string" && label.text){
        htmlId = ' id="sublabel_'+label.id+'"';
        label  = label.text;
    }
    
    html += " "+(link || "")+" ";
    var id = input.match(/.*?id=\"(.*?)\".*?/);
    if(id){
        htmlFor = ' for="'+id[1]+'"';
    }
    
    html += '<label class="form-sub-label"'+htmlFor+htmlId+'>'+(label || "&nbsp;&nbsp;&nbsp;")+'</label>';
    html += '</span>';
    
    return html;
};

BuildSource.escapeValue = function(str){
    return str.replace(/\"/gim, '&quot;');
};

BuildSource.makeQuestionName = function(text){
    
    var name = text.replace('&nbsp;', '');
    var tokens = name.split(/\s+/);
    name = this.fixUTF((tokens["1"]) ? tokens["0"].toLowerCase() + this.capitalize(tokens["1"].toLowerCase()) : tokens["0"].toLowerCase());
    name = name.replace(/\W/gim, '');
    return name;
};


/**
 * Creates the HTML of input element by given question type
 * @param {Object} type
 * @param {Object} id
 * @param {Object} prop
 * @param {Object} passive Defines if the generated code will be used with builde or live form
 */
BuildSource.createInputHTML = function(type, id, prop, passive, forFacebook){
    var html = "";
    var script = "";
    var qname = "q" + id + '_' + this.makeQuestionName(prop.text.value); //prop.text.value.stripScripts().stripTags().replace(/\W/gim, "").substr(0, 20);
    
    if (prop.name && prop.name.value) {
        qname = "q" + id + '_' + prop.name.value;
    }
    
    var sublabel = function(key){
        if(prop.sublabels && prop.sublabels.value[key]){
            return { text: prop.sublabels.value[key], id: key };
        }
        return "";
    }
    
    var qid = "input_" + id;
    var classNames = {
        textbox:    "form-textbox",
        password:   "form-password",
        radio:      "form-radio",
        checkbox:   "form-checkbox",
        textarea:   "form-textarea",
        upload:     "form-upload",
        mupload:    "form-upload-multiple",
        dropdown:   "form-dropdown",
        list:       "form-list"
    };
    
    switch (type) { // Check question type
        case "control_text":
            // prop.text.value = this.cleanWordFormat(prop.text.value); // Clean stupid word code from the source.
            html = '<div id="text_' + id + '" class="form-html">' + this.htmlDecode(this.stripslashes(prop.text.value)) + '</div>';
            if (passive) {
                
            }
            break;
        case "control_head":
            
            var head = 'h2';
            if (prop.headerType.value == "Large") {
                head = 'h1';
            } else if (prop.headerType.value == "Small") {
                head = 'h3';
            }
            html += '<div class="form-header-group">';
            html += "<" + head;
            html += ' id="header_' + id + '"';
			
			/*
			 *  Facebook defines h1,h2,h3,h4,h5,h6 tags according to itself 
			 *  When they are used they don't get bigger so
			 *  I need to set the form header font-size with inline css 
			 *  As I can not access the form's font-size from here so 
			 *  I made the header size default 24 px in facebook 
			 */
			if(forFacebook)
				html += ' style="font-size:24px;"';
				
            html += ' class="form-header">';
            html += prop.text.value;
            html += '</' + head + '>';
            if (prop.subHeader.value &&  prop.subHeader.value != "Click to edit sub heading...") {
                html += '<div id="subHeader_' + id + '" class="form-subHeader">' + prop.subHeader.value + '</div>';
            }
            html += "</div>";
            if (passive) {
                Element.observe(passive, 'on:render', function(){
                    if ($('subHeader_' + id)) {
                        Protoplus.ui.editable('subHeader_' + id, {
                            className: 'subHeader-edit',
                            onEnd: function(a, b, old, val){
                                val = val.strip();
                                passive.setProperty("subHeader", val);
                                
                                if (old != val) {
                                    onChange("Label changed from: '" + old + "' to: '" + val + "'");
                                }
                            }
                        });
                    }
                    Protoplus.ui.editable('header_' + id, {
                        className: 'header-edit',
                        onKeyUp: function(e){
                            var old = $('form-title').innerHTML;
                            if (form.getProperty('title') == 'Untitled Form'.locale() || form.getProperty('title') == 'Title Me'.locale()) {
                                $('form-title').update(e.target.value);
                            }
                        },
                        onEnd: function(a, b, old, val){
                        
                            if (form.getProperty('title') == 'Untitled Form'.locale() || form.getProperty('title') == 'Title Me'.locale()) {
                                form.setProperty('title', val);
                            }
                            
                            val = val.strip();
                            passive.setProperty("text", val);

                            if (old != val) {
                                onChange("Label changed from: '" + old + "' to: '" + val + "'");
                            }
                        }
                    });
                });
            }
            
            break;
        case "control_passwordbox": // They are practically the same
        case "control_hidden":
        case "control_autocomp":
        case "control_textbox":
        case "control_email":
        case "control_number":
        case "control_autoincrement":
            
            var inputType = "text";
            
            if(type == "control_passwordbox"){
                inputType = "password";
            }else if(type == "control_hidden" || type == "control_autoincrement"){
                inputType = "hidden";
            }else if(type == "control_number"){
                inputType = "number";
            }else if(type == "control_email"){
                inputType = "email";
            }
            
            html = '<input type="' + inputType + '" ';
            html += 'class="' + this.addValidation(classNames.textbox, prop) + '"';
            html += 'id="' + qid + '" ';
            html += 'name="' + qname + '" ';
            
            if (type == 'control_autocomp') {
                html += 'autocomplete="off" ';
            }
            
            if (prop.size) {
                html += 'size="' + prop.size.value + '" ';
            }
            
            if (prop.defaultValue && prop.defaultValue.value) {
                var v = prop.defaultValue.value;
                
                html += 'value="' + v + '" ';
            }
            
            if (prop.maxsize && prop.maxsize.value && prop.maxsize.value > 0) {
                html += 'maxlength="' + prop.maxsize.value + '" ';
            }
            
            html += ' />';
            
            if ((type == "control_hidden" || type == "control_autoincrement") && passive) {
                Element.observe(passive, 'on:render', function(){ Element.setOpacity('hidden_' + id, 0.8); });
                var v = prop.defaultValue && prop.defaultValue.value;
                if(type == "control_autoincrement"){
                    v = prop.currentIndex.value;
                    if(prop.idPadding && prop.idPadding.value){
                        v = Utils.strPad(v, prop.idPadding.value, "0", 'STR_PAD_LEFT'); 
                    }
                    if(prop.idPrefix && prop.idPrefix.value){
                        v = prop.idPrefix.value+v;
                    }
                }
                html = '<input type="text" readonly="readonly" value="' + v + '" style="border:1px dashed #ccc" id="hidden_' + id + '" />';
                html += '<br><div class="hidden-field-warning"><img src="images/information-middle.png" align="top" /> This field will not be seen on the form</div>';
            } else if (type == "control_hidden" || type == "control_autoincrement") {
                html = '<input type="hidden" class="form-hidden" value="' + (prop.defaultValue? prop.defaultValue.value : prop.currentIndex.value) + '" id="' + qid + '" name="' + qname + '" />';
            }
            
            if (prop.hint && prop.hint.value && passive) {
                
                Element.observe(passive, 'on:render', function(){
                    Protoplus.ui.hint(qid, prop.hint.value);
                });
            
            }else if(prop.hint && prop.hint.value == ""){
                prop.hint.value = " "; // in order to prevent default from work
            }
            
            if(prop.subLabel && prop.subLabel.value){
                html = this.subLabel(html, prop.subLabel.value);
            }
            
            if (type == 'control_autocomp') {
                script += "      JotForm.autoCompletes['" + qid + "'] = '" + (prop.items.value.replace(/\'/gim, "\\'")) + "';\n";
                if (passive) {
                    Element.observe(passive, 'on:render', function(){
                        var img = getElement('img');
                        img.src = 'images/dropdown-properties.png';
                        img.className = 'dropdown-edit';
                        img.align = 'absmiddle'; 
                        img.onclick = function(){
                            $('button_' + id + '_items').run('mousedown');
                        };
                        passive.appendChild(img);
                    });
                }
            }
            
            break;
        case "control_textarea":
            
            html = '<textarea ';
            html += 'id="' + qid + '" ';
            html += 'class="' + this.addValidation(classNames.textarea, prop) + '" ';
            html += 'name="' + qname + '" ';
            html += 'cols="' + prop.cols.value + '" ';
            html += 'rows="' + prop.rows.value + '" ';
            
            if (prop.maxsize && prop.maxsize.value && prop.maxsize.value > 0) {
                html += 'maxlength="' + prop.maxsize.value + '" ';
            }
            html += '>' + prop.defaultValue.value + '</textarea>';
            
            if(prop.entryLimit && prop.entryLimit.value){
                var l = prop.entryLimit.value.split('-');
                if (l[0] != 'None' && l[1] > 1) {
                    var textarea = html;
                    html = '<div class="form-textarea-limit"><span>';
                    html += textarea;
                    html += '<div class="form-textarea-limit-indicator">';
                    if (prop.subLabel && prop.subLabel.value) {
                        html += '<label for="' + qid + '" style="float:left">' + prop.subLabel.value + '</label>';
                    }
                    html += '<span type="' + l[0] + '" limit="' + l[1] + '" id="' + qid + '-limit">0/' + l[1] + '</span>';
                    html += '</div>';
                    html += '</span></div>';
                } else if (prop.subLabel && prop.subLabel.value) {
                    html = this.subLabel(html, prop.subLabel.value);
                }
            }else if(prop.subLabel && prop.subLabel.value){
                html = this.subLabel(html, prop.subLabel.value);
            }
            
            break;
        case "control_dropdown":
            var dropwidth = "";
            if (prop.width && (prop.width.value != 'auto' || !prop.width.value)) {
                dropwidth = ' style="width:' + parseInt(prop.width.value, 10) + 'px"';
            }
            
            var cl = classNames.dropdown;
            if (prop.size.value > 1) {
                cl = classNames.list;
            }
            
            
            html = '<select class="' + this.addValidation(cl, prop) + '"' + dropwidth + ' id="' + qid + '" name="' + qname; // Close name tag later
            if (prop.size.value > 1) {
                html += '[]" size="' + prop.size.value + '" multiple="multiple'; // Close this tag later
            }
            html += '" >';
            
            opts = prop.options.value.split("|");
            html += '<option></option>';
            
            if (prop.special.value != "None") {
                prop.options.disabled = true;
                opts = this.deepClone(special_options[prop.special.value].value);
            } else {
                prop.options.disabled = false;
            }
            
            /*
             // Property caching disabled because of classic cache problems
             if(prop.special.value != "None" && prop.special.value in optionsCache){
             
             html += optionsCache[prop.special.value];
             
             }else{
             
             */
            var ddop = "";
            var groupOpen = false;
            for (var d = 0; d < opts.length; d++) {
                var selec = (prop.selected.value == opts[d]) ? ' selected="selected"' : '';
                var option_value = opts[d];
                var option_group = option_value.match(/^\[\[(.*?)\]\]$/);
                if(option_group){
                    if(groupOpen){
                        ddop += '</optgroup>';
                    }
                    ddop += '<optgroup label="'+this.escapeValue(option_group[1])+'">';
                    groupOpen = true;
                }else{
                    ddop += '<option' + selec + ' value="'+this.escapeValue(option_value)+'">' + option_value + '</option>';
                }
            }
            if(groupOpen){
                ddop += '</optgroup>';
            }
            if (prop.special.value != "None" && !(prop.special.value in optionsCache)) {
                optionsCache[prop.special.value] = ddop;
            }
            html += ddop;
            // }
            html += '</select>';
            
            if(prop.subLabel && prop.subLabel.value){
                html = this.subLabel(html, prop.subLabel.value);
            }
            
            if (passive) {
                Element.observe(passive, 'on:render', function(){
                    
                    var img = getElement('img');
                        img.src = 'images/dropdown-properties.png';
                        img.className = 'dropdown-edit';
                        img.align = 'absmiddle'; 
                        img.onclick = function(){
                            if($('button_' + id + '_options').disabled){
                                $('button_' + id + '_special').run('mousedown');
                            }else{
                                $('button_' + id + '_options').run('mousedown');
                            }
                        };
                    passive.appendChild(img);
                });
            }
            
            break;
        case "control_checkbox":
        case "control_radio":
            opts = prop.options.value.split("|");
            if (prop.special.value != "None") {
                opts = this.deepClone(special_options[prop.special.value].value);
            }
            var inputType = type.replace('control_', '');
            
            if(inputType == "checkbox"){
                qname += "[]"; 
            }
            
            var col = (prop.spreadCols.value > 1) ? prop.spreadCols.value : 0;
            var colCount = 0;
            html += '<div class="'+(col < 2? 'form-single-column' : 'form-multiple-column')+'">';
            for (var r = 0; r < opts.length; r++) {
                var strippedVal = opts[r].strip();
                var rd_selected = (prop.selected.value.strip() == strippedVal) ? ' checked="checked"' : '';
                var rinp = '<input type="'+inputType+'" class="' + this.addValidation(classNames[inputType], prop) + '" id="' + qid + '_' + r + '" name="' + qname + '" ' + rd_selected + ' value="' + this.escapeValue(strippedVal) + '" />';
                var rlab = '<label '+ ( passive? 'id="label_' + qid + '_' + r + '"' : 'for="' + qid + '_' + r + '"' ) +'>' + strippedVal + '</label>';
                var clear = '';
                colCount++;
                if (colCount > col) {
                    clear += ' style="clear:left;"';
                    colCount = 1;
                }
                
                html += '<span class="form-'+inputType+'-item"'+clear+'>' + rinp + rlab + '</span>';
                // Adding clear fix for ie.
                html += '<span class="clearfix"></span>';
            }
            
            if (prop.allowOther && prop.allowOther.value == "Yes") {
                var otherRadio, otherInput;
                html += '<span class="form-radio-item" style="clear:left">';
                html += '<input type="radio" class="form-radio-other ' + this.addValidation(classNames.radio, prop) + '" name="' + qname + '" id="other_' + id + '" />';
                html += '<input type="text" class="form-radio-other-input" name="' + qname + '[other]" size="15" id="' + qid + '" disabled="disabled" />';
                html += '<br />';
                html += '</span>';
                if(passive){
                    Element.observe(passive, 'on:render', function(){
                        Protoplus.ui.hint(qid, 'Other');
                    });
                }
            }
            
            html += '</div>';
            if (passive) {
                Element.observe(passive, 'on:render', function(){
                    setOptionsEditable(id, inputType);
                });
            }
            break;
        case "control_datetime":
            
            var icon = '<img alt="' + 'Pick a Date'.locale() + '" id="' + qid + '_pick" src="' + this.HTTP_URL + 'images/calendar.png" align="absmiddle" />';
            
            var date = new Date();
            var month = this.addZeros(date.getMonth() + 1, 2);
            var day = this.addZeros(date.getDate(), 2);
            var year = date.getYear() < 1000 ? date.getYear() + 1900 : date.getYear();
            var hour = this.addZeros(date.getHours(), 2);
            var min = this.addZeros(date.getMinutes(), 2);
            
            //html = '<div style="padding-bottom: 15px;">';
            var noDefault = "";
            if(prop.defaultTime.value != 'Yes'){
                month = day = year = hour = min = "";
                noDefault = "noDefault ";
            }

            // Date
            var dd = '<input class="'+noDefault + this.addValidation(classNames.textbox, prop) + '" id="day_' + id + '" name="' + qname + '[day]" type="text" size="2" maxlength="2" value="' + day + '" />';
            var mm = '<input class="' + this.addValidation(classNames.textbox, prop) + '" id="month_' + id + '" name="' + qname + '[month]" type="text" size="2" maxlength="2" value="' + month + '" />';
            var yy = '<input class="' + this.addValidation(classNames.textbox, prop) + '" id="year_' + id + '" name="' + qname + '[year]" type="text" size="4" maxlength="4" value="' + year + '" />';
            
            // Time
            var hh = '<input class="' + this.addValidation(classNames.textbox, prop) + '" id="hour_' + id + '" name="' + qname + '[hour]" type="text" size="2" maxlength="2" value="' + hour + '" />';
            var ii = '<input class="' + this.addValidation(classNames.textbox, prop) + '" id="min_' + id + '" name="' + qname + '[min]" type="text" size="2" maxlength="2" value="' + min + '" />';
            
            
            // Hour Format
            var ampm = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" id="ampm_' + id + '" name="' + qname + '[ampm]"><option value="AM">AM</option><option value="PM">PM</option></select>';
            var at = 'at';
            if (prop.allowTime.value != 'Yes') {
                at='';
            }
            
            dd = this.subLabel(dd, sublabel('day'), '-');
            mm = this.subLabel(mm, sublabel('month'), '-');
            yy = this.subLabel(yy, sublabel('year'), at);
            hh = this.subLabel(hh, sublabel('hour'), '/');
            ii = this.subLabel(ii, sublabel('minutes'));
            ampm = this.subLabel(ampm, '');
            // Date Format
            if (prop.format.value == "mmddyyyy") {
                html += mm;
                html += dd;
            } else {
                html += dd;
                html += mm;
            }
            // Year
            html += yy;
            if (prop.allowTime.value == 'Yes') {
                html += hh;
                html += ii;
                if (prop.timeFormat.value == "AM/PM") {
                    html += ampm;
                }
            }
            html += this.subLabel(icon);
            if (!passive) {
                /*script += '      Calendar.setup({\n';
                script += '          triggerElement:"' + qid + '_pick",\n';
                script += '          dateField:"year_' + id + '",\n';
                script += '          selectHandler:JotForm.formatDate\n';
                script += '      });\n';*/
               
               script += '      JotForm.setCalendar("'+id+'");\n';
            }
            
            //html += '</div>';
            break;
        case "control_fileupload":
            var isMultiple = prop.allowMultiple && prop.allowMultiple.value == 'Yes';
            
            html += '<input class="' + this.addValidation(isMultiple? classNames.mupload : classNames.upload, prop) + '" type="file" id="' + qid + '"';
            if(isMultiple){
                html += 'name="' + qname + '[]"';
                html += ' multiple="multiple"';
            }else{
                html += 'name="' + qname + '"';
            }
            html += ' file-accept="' + prop.extensions.value + '"'; // Breaks uploads on Chrome: http://drupal.org/node/939962
	        html += ' file-maxsize="' + prop.maxFileSize.value + '"';
	        html += ' />';
            
            if(passive && isMultiple){
                html = '<div class="qq-uploader"><div class="qq-upload-button">Upload a file</div></div>';
            }
            
            if(prop.subLabel && prop.subLabel.value){
                html = this.subLabel(html, prop.subLabel.value);
            }
            
            break;
        case "control_rating":
            var stars = "stars";
            switch (prop.starStyle.value) {
                case "Hearts":
                    stars = "hearts";
                    break;
                case "Stars":
                    stars = "stars";
                    break;
                case "Stars 2":
                    stars = "stars2";
                    break;
                case "Lightnings":
                    stars = "lightnings";
                    break;
                case "Light Bulps":
                    stars = "bulps";
                    break;
                case "Shields":
                    stars = "shields";
                    break;
                case "Flags":
                    stars = "flags";
                    break;
                case "Pluses":
                    stars = "pluses";
                    break;
                default:
                    stars = "stars";
            }
            
            html += '<div id="' + qid + '" name="' + qname + '">';
            html += '<select name="' + qname + '">';
            for(var s = 1; s <= prop.stars.value; s++){
                html += '<option value="'+s+'">'+s+'</option>';
            }
            
            html += '</select>';
            html += '</div>';
            
            script += "      $('" + qid + "').rating({stars:'" + prop.stars.value + "', inputClassName:'"+this.addValidation(classNames.textbox, prop)+"', imagePath:'" + this.HTTP_URL + "images/" + stars + ".png', cleanFirst:true, value:'" + prop.defaultValue.value + "'});\n";
            
            if(passive){
                Element.observe(passive, 'on:render', function(){
                    Protoplus.ui.rating(qid, {stars:prop.stars.value, imagePath:"images/" + stars + ".png", cleanFirst:true, value:prop.defaultValue.value});
                });
            }
            
            break;
        case "control_captcha":
            
            if(prop.useReCaptcha.value == 'Yes'){
                if(passive){
                    html += '<img src="images/recaptcha-sample.png">';
                }else{
                    if(this.isSecure){
                        html += '<script type="text/javascript" src="https://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>';
                    }else{
                        html += '<script type="text/javascript" src="http://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>';
                    }
                    html += '<div id="recaptcha_' + qid + '"></div>';
                    html += '<script type="text/javascript">';
                    html += 'Recaptcha.create("6Ld9UAgAAAAAAMon8zjt30tEZiGQZ4IIuWXLt1ky","recaptcha_' + qid + '",{theme: "clean", callback: Recaptcha.focus_response_field});';
                    html += '</script>';
                }
            }else{
                html += '<div class="form-captcha">';
                html += '<label for="' + qid + '">';
                if (passive) {
                    html += '<img alt="Captcha - Reload if it\'s not displayed" class="form-captcha-image" src="../cimg/38.png" width="150" />';
                } else {
                    html += '<img alt="Captcha - Reload if it\'s not displayed" id="' + qid + '_captcha" class="form-captcha-image" style="background:url(' + this.HTTP_URL + 'images/loader-big.gif) no-repeat center;" src="' + this.HTTP_URL + 'images/blank.gif" width="150" height="41" />';
                }
                
                html += '</label>';
                html += '<div style="white-space:nowrap;"><input type="text" id="' + qid + '" name="captcha" style="width:130px;" />';
                if (passive) {
                    html += '<img src="images/reload.png" alt="Reload" align="absmiddle" style="cursor:pointer" />';
                } else {
                    html += '<img src="' + this.HTTP_URL + 'images/reload.png" alt="Reload" align="absmiddle" style="cursor:pointer" onclick="JotForm.reloadCaptcha(\'' + qid + '\');" />';
                    script += "      JotForm.initCaptcha('" + qid + "');\n";
                }
                html += '<input type="hidden" name="captcha_id" id="' + qid + '_captcha_id" value="0">';
                html += '</div>';
                html += '</div>';
                script += "      $('" + qid + "').hint('" + "Type the above text".locale() + "');\n";
            }
            
            break;
        case "control_image":
            if (prop.link.value) {
                if(!prop.link.value.match(/^http/)){
                    prop.link.value = "http://"+prop.link.value;
                }
                html += '<a href="' + prop.link.value + '" target="_blank">';
            }
            var imgAlt = '';
            var src = prop.src.value;
            
            if(this.isSecure && (src.match("http://www.jotform.com") !== null)){
                src = src.replace("http://www.jotform.com", "https://www.jotform.com");
            }
            
            html += '<img alt="" ' + imgAlt + ' class="form-image" border="0" src="' + src + '" height="' + prop.height.value + '" width="' + prop.width.value + '" />';
            
            if (prop.link.value) {
                html += "</a>";
            }
            if (prop.align.value == "Center") {
                html = '<div style="text-align:center;">' + html + '</div>';
            }
            if (prop.align.value == "Right") {
                html = '<div style="text-align:right;">' + html + '</div>';
            }
            break;
        case "control_button":
            var buttonAlign = 'text-align:' + prop.buttonAlign.value.toLowerCase();
            
            if (prop.buttonAlign.value.toLowerCase() == 'auto') {
                var pad = 0;
                try {
                    pad = parseInt(Utils.getStyleBySelector('.form-label-left').padding, 10);
                } 
                catch (e) {
                }
                buttonAlign = 'margin-left:' + (parseInt(this.getProperty('labelWidth'), 10) + (pad ? pad * 2 : 6)) + 'px';
            }
            
            html = '<div style="' + buttonAlign + '" class="form-buttons-wrapper">';
            
            if (forFacebook) {
            	html += '<input id="input_' + id + '" type="submit" value="' + (prop.text.value || 'Submit Form'.locale()) + '" class="form-submit-button">';
            } else {
                if(prop.useImage.value){
                    html += '<button id="input_' + id + '" type="' + (passive ? 'button' : 'submit') + '" class="form-submit-button form-submit-button-img" >'+
                        '<img src="'+prop.useImage.value+'"  alt="'+(prop.text.value || 'Submit Form'.locale())+'" />' +
                    '</button>';
                }else{
    	            html += '<button id="input_' + id + '" type="' + (passive ? 'button' : 'submit') + '" class="form-submit-button" >' + (prop.text.value || 'Submit Form'.locale()) + '</button>';
                }
                
	            
	            if (prop.clear.value == "Yes") {
	                html += ' &nbsp; <button id="input_reset_' + id + '" type="' + (passive ? 'button' : 'reset') + '" class="form-submit-reset">' + 'Clear Form' + '</button>';
	            }
	            
	            if (prop.print.value == "Yes") {
	                html += ' &nbsp; <button id="input_print_' + id + '" style="margin-left:25px;" class="form-submit-print" type="button" ><img src="'+this.HTTP_URL+'images/printer.png" align="absmiddle" /> ' + ' Print Form' + '</button>';
	            }
            }
            
            html += '</div>';
            break;
        case "control_slider":
            html += '<input type="range" class="'+this.addValidation(classNames.textbox, prop)+'" id="' + qid + '" name="' + qname + '" />';
            script += "      $('" + qid + "').slider({ width: '" + prop.width.value + "', maxValue: '" + prop.maxValue.value + "', value: '" + prop.defaultValue.value + "'});\n";
            if(passive){
                Element.observe(passive, 'on:render', function(){
                    Protoplus.ui.slider(qid, {
                        width: prop.width.value,
                        maxValue: prop.maxValue.value,
                        value: prop.defaultValue.value,
                        buttonBack:'url("images/ball.png") no-repeat scroll 0px 0px transparent'
                    });
                });
            }
            break;
        case "control_spinner":
            
            html = '<input type="number" id="' + qid + '" name="' + qname + '" />';
            script += "      $('" + qid + "').spinner({ imgPath:'" + this.HTTP_URL + "images/', width: '" + prop.width.value + "', maxValue:'" + this.fixNumbers(prop.maxValue.value) + "', minValue:'" + this.fixNumbers(prop.minValue.value) + "', allowNegative: " + (prop.allowMinus.value == 'Yes' ? 'true' : 'false') + ", addAmount: " + this.fixNumbers(prop.addAmount.value) + ", value:'" + this.fixNumbers(prop.defaultValue.value) + "' });\n";
            if(passive){
                Element.observe(passive, 'on:render', function(){
                    
                    Protoplus.ui.spinner(qid, {
                        imgPath: "images/",
                        width: prop.width.value,
                        maxValue: prop.maxValue.value,
                        minValue: prop.minValue.value,
                        allowNegative: (prop.allowMinus.value == 'Yes'),
                        addAmount: prop.addAmount.value,
                        value: prop.defaultValue.value
                    });
                    
                });
            }
            break;
        case "control_range":
            
            html  = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="number" id="' + qid + '_from" name="' + qname + '[from]" />', sublabel('from'));
            html += this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="number" id="' + qid + '_to" name="' + qname + '[to]" />', sublabel('to'));
            
            //script += "      $('" + qid + "_from').miniLabel('From', {nobr:true});\n";
            //script += "      $('" + qid + "_to').miniLabel('To', {nobr:true});\n";
            
            script += "      $('" + qid + "_to').spinner({ imgPath:'" + this.HTTP_URL + "images/', width: '60', allowNegative: " + (prop.allowMinus.value == 'Yes' ? 'true' : 'false') + ", addAmount: " + this.fixNumbers(prop.addAmount.value) + ", value:'" + this.fixNumbers(prop.defaultTo.value) + "' });\n";
            script += "      $('" + qid + "_from').spinner({ imgPath:'" + this.HTTP_URL + "images/', width: '60', allowNegative: " + (prop.allowMinus.value == 'Yes' ? 'true' : 'false') + ", addAmount: " +this.fixNumbers(prop.addAmount.value) + ", value:'" + this.fixNumbers(prop.defaultFrom.value) + "' });\n";
            if(passive){
                Element.observe(passive, 'on:render', function(){
                    Protoplus.ui.spinner(qid+'_to',   { imgPath: "images/", width: '60', allowNegative: prop.allowMinus.value == 'Yes', addAmount: prop.addAmount.value, value:prop.defaultTo.value });
                    Protoplus.ui.spinner(qid+'_from', { imgPath: "images/", width: '60', allowNegative: prop.allowMinus.value == 'Yes', addAmount: prop.addAmount.value, value: prop.defaultFrom.value });
                });
            }
            break;
        case "control_fullname":
            if (prop.prefix.value == 'Yes') {
                html += this.subLabel('<input class="' + classNames.textbox + '" type="text" name="' + qname + '[prefix]" size="4" id="pre_' + id + '" />', sublabel('prefix'));
            }
            
            html += this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" size="10" name="' + qname + '[first]" id="first_' + id + '" />', sublabel('first'));
            
            if (prop.middle.value == 'Yes') {
                html += this.subLabel('<input class="' + classNames.textbox + '" type="text" size="10" name="' + qname + '[middle]" id="middle_' + id + '" />', sublabel('middle'));
            }
            
            html += this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" size="15" name="' + qname + '[last]" id="last_' + id + '" />', sublabel('last'));
            
            if (prop.suffix.value == "Yes") {
                html += this.subLabel('<input class="' + classNames.textbox + '" type="text" size="4" name="' + qname + '[suffix]" id="suf_' + id + '" />', sublabel('suffix'));
            }
            break;
        case "control_grading":
            
            //$A(prop.options.value.split("|")).each(function(option, i){
            var gradingOptions = prop.options.value.split("|");
            for (var i = 0; i < gradingOptions.length; i++) {
                var option = gradingOptions[i];
                var gbox = '<input class="form-grading-input ' + this.addValidation(classNames.textbox, prop) + '" type="text" size="3" id="' + qid + '_' + i + '" name="' + qname + '[]" /> ';
                var glabel = '<label class="form-grading-label" for="' + qid + '">' + option + '</label>';
                
                html += '<div class="form-grading-item">';
                
                if (prop.boxAlign.value == "Left") {
                    html += gbox + glabel;
                } else {
                    html += glabel + gbox;
                }
                
                html += '</div>';
            }
            
            // Zero is unlimited
            if (prop.total.value != "0") {
                html += '<div> ' + 'Total'.locale() + ': <span id="grade_point_' + id + '">0</span> / <span id="grade_total_' + id + '">' + prop.total.value + '</span> <span class="form-grading-error" id="grade_error_' + id + '"></span>  </div>';
            }
            
            if (passive) {
                Element.observe(passive, 'on:render', function(){
                    var img = getElement('img');
                        img.src = 'images/dropdown-properties.png';
                        img.className = 'dropdown-edit';
                        img.align = 'absmiddle';
                        img.setStyle('position:absolute; bottom:0px; right:-20px;'); 
                        img.onclick = function(){
                            $('button_' + id + '_options').run('mousedown');
                        };
                    passive.appendChild(img);
                });
            }
            
            break;
        case "control_matrix":
            
            html = '<table summary="" cellpadding="4" cellspacing="0" class="form-matrix-table"><tr>';
            html += '<th style="border:none">&nbsp;</th>';
            
            var cols = prop.mcolumns.value.split('|');
            var colWidth = (100 / cols.length + 2) + "%";
            
            //$A(cols).each(function(col){
            for (var coli = 0; coli < cols.length; coli++) {
                html += '<th class="form-matrix-column-headers" style="width:' + colWidth + '">' + cols[coli] + '</th>';
            }
            
            html += '</tr>';
            var mrows = prop.mrows.value.split('|');
            var mcolumns = prop.mcolumns.value.split('|');
            //$A(prop.mrows.value.split('|')).each(function(row, ri){
            for (var ri = 0; ri < mrows.length; ri++) {
                var row = mrows[ri];
                html += '<tr>';
                html += '<th align="left" class="form-matrix-row-headers" nowrap="nowrap">' + row + '</th>';
                //$A(prop.mcolumns.value.split('|')).each(function(col){
                for (var j = 0; j < mcolumns.length; j++) {
                    var mcol = mcolumns[j];
                    var input;
                    switch (prop.inputType.value) {
                        case "Radio Button":
                            input = '<input class="' + this.addValidation(classNames.radio, prop) + '" type="radio" name="' + qname + '[' + ri/*+'_'+row.replace(/\s+/gim, "")*/ + ']" value="' + mcol.sanitize() + '" />';
                            break;
                        case "Check Box":
                            input = '<input class="' + this.addValidation(classNames.checkbox, prop) + '" type="checkbox" name="' + qname + '[' + ri +/*'_'+row.replace(/\s+/gim, "")+*/ '][]" value="' + mcol.sanitize() + '" />';
                            break;
                        case "Text Box":
                            input = '<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" size="5" name="' + qname + '[' + ri +/*'_'+row.replace(/\s+/gim, "")+*/ '][]" />';
                            break;
                        case "Drop Down":
                            input = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[' + ri +/*'_'+row.replace(/\s+/gim, "")+*/ '][]"><option></option>';
                            //$A(prop.dropdown.value.split('|')).each(function(op){
                            var dp = prop.dropdown.value.split('|');
                            for (var dpd = 0; dpd < dp.length; dpd++) {
                                input += '<option value="'+this.escapeValue(dp[dpd])+'">' + dp[dpd] + '</option>';
                            }
                            //});
                            input += '</select>';
                            break;
                    }
                    html += '<td align="center" class="form-matrix-values" >' + input + '</td>';
                }
                html += "</tr>";
            }
            html += "</table>";
            break;
        case "control_collapse":
            
            var im = (prop.status.value == "Closed") ? "hide" : "show";
            var hidden = ((prop.visibility.value == "Hidden") ? ' form-collapse-hidden' : '');
            
            if(passive){
                hidden = '';
            }
            
            html += '<div class="form-collapse-table' + hidden + '" id="collapse_' + id + '">';
            html += '<span class="form-collapse-mid" id="collapse-text_' + id + '">' + prop.text.value + '</span>';
            html += '<span class="form-collapse-right form-collapse-right-' + im + '">&nbsp;</span>';
            html += '</div>';
            
            if (passive) {
                Element.observe(passive, 'on:render', function(){
                    Protoplus.ui.editable('collapse-text_' + id, {
                        className: 'edit-text',
                        onEnd: function(a, b, old, val){
                            val = val.strip();
                            updateValue('text', val, passive.getReference('container'), passive, old);
                        }
                    });
                    
                    if (prop.visibility.value == 'Hidden') {
                        Element.setOpacity('collapse_' + id, 0.5);
                    }
                });
            }
            break;
        case "control_pagebreak":
            var pagingButtonAlign = '';
            if (passive) {
                // -14 is the padding of the box. this value must change with the themes selected but style selector throws error
                pagingButtonAlign = ' style="width:' + (form.getProperty('labelWidth') - 14) + 'px"';
            }
            html += '<div class="form-pagebreak" >';
            html += '<div class="form-pagebreak-back-container form-label-left"' + pagingButtonAlign + '>';
            html += '<button type="button" class="form-pagebreak-back" id="form-pagebreak-back_' + id + '">' + 'Back'.locale() + '</button>';
            html += '</div>';
            html += '<div class="form-pagebreak-next-container">';
            html += '<button type="button" class="form-pagebreak-next" id="form-pagebreak-next_' + id + '">' + 'Next'.locale() + '</button>';
            html += '</div>';
            html += '</div>';
            
            break;
        case "control_birthdate":
            var bmonth, bmontho = "", bday, bdayo = "", byear, byearo = "", cyear;
            var bdate = new Date();
            // get current year
            cyear = ((bdate.getYear() < 1000) ? bdate.getYear() + 1900 : bdate.getYear()) + 4;
            
            //html  = '<div style="height:32px;">';
            bmonth = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[month]" id="' + qid + '_month">';
            bmonth += '<option></option>';
            
            if (optionsCache.months) {
                bmontho += optionsCache.months;
            } else {
                //$A(special_options.Months.value).each(function(m){
                for (var mi = 0; mi < special_options.Months.value.length; mi++) {
                    bmontho += '<option value="'+special_options.Months.value[mi]+'">' + special_options.Months.value[mi] + '</option>';
                }
                optionsCache.months = bmontho;
            }
            
            bmonth += bmontho;
            
            bmonth += '</select>';
            
            bday = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[day]" id="' + qid + '_day">';
            bday += '<option></option>';
            
            if (optionsCache.days) {
                bdayo += optionsCache.days;
            } else {
                for (var dayn = 31; dayn >= 1; dayn--) {
                    bdayo += '<option value="'+dayn+'">' + dayn + '</option>';
                }
                optionsCache.days = bdayo;
            }
            
            bday += bdayo;
            bday += '</select>';
            
            byear = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[year]" id="' + qid + '_year">';
            byear += '<option></option>';
            
            if (optionsCache.years) {
                byearo += optionsCache.years;
            } else {
                for (var yearn = cyear; yearn >= 1920; yearn--) {
                    byearo += '<option value="'+yearn+'">' + yearn + '</option>';
                }
                optionsCache.years = byearo;
            }
            byear += byearo;
            byear += '</select>';
            
            bmonth = this.subLabel(bmonth, sublabel('month'));
            bday   = this.subLabel(bday, sublabel('day'));
            byear  = this.subLabel(byear, sublabel('year'));
            
            if (prop.format.value == "mmddyyyy") {
                html += bmonth + bday + byear;
            } else {
                html += bday + bmonth + byear;
            }
            
            
            //html += '</div>';
            break;
        case "control_phone":
            html += this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="tel" name="' + qname + '[area]" id="' + qid + '_area" size="3">', sublabel('area'), '-');
            html += this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="tel" name="' + qname + '[phone]" id="' + qid + '_phone" size="8">', sublabel('phone'));
            break;
            
        /**
         * @deprecated We have removed this feature it's not used anymore
         */
        case "control_location":
            var lcountry = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" id="' + qid + '_country" name="' + qname + '[country]" ><option selected>Please Select</option>';
            
            for (var lci = 0; lci < special_options.LocationCountries.value.length; lci++) {
                lcountry += '<option value="' + (++lci) + '">' + special_options.LocationCountries.value[lci] + '</option>';
            }
            
            lcountry += '<option value="other">Other</option></select>';
            
            var lstate = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" id="' + qid + '_state" name="' + qname + '[state]"><option>Any</option></select>';
            var lcity  = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" id="' + qid + '_city" name="' + qname + '[city]"><option>Any</option></select>';
            
            lcountry = this.subLabel(lcountry, 'Country:');
            lstate   = this.subLabel(lstate, 'State:');
            lcity    = this.subLabel(lcity, 'City / Province:');
            html += lcountry + lstate + lcity;
            script += "      setLocationEvents($('" + qid + "_country'), $('" + qid + "_state'), $('" + qid + "_city'));\n";
            break;
        case "control_scale":
            html += '<table summary="" cellpadding="4" cellspacing="0" class="form-scale-table"><tr>';
            html += '<th>&nbsp;</th>';
            
            for (x = 1; x <= prop.scaleAmount.value; x++) {
                html += '<th align="center"><label for="' + qid + '_' + x + '">' + x + '</label></th>';
            }
            
            html += '<th>&nbsp;</th></tr>';
            html += '<tr>';
            html += '<td><label for="' + qid + '_1" >' + prop.fromText.value + '</label></td>';
            
            for (x = 1; x <= prop.scaleAmount.value; x++) {
                html += '<td align="center"><input class="' + this.addValidation(classNames.radio, prop) + '" type="radio" name="' + qname + '" value="' + x + '" title="' + x + '" id="' + qid + '_' + x + '" /></td>';
            }
            
            html += '<td><label for="' + qid + '_' + (x - 1) + '">' + prop.toText.value + '</label></td>';
            html += '</tr></table>';
            break;
        case "control_payment":
        case "control_paypal":
        case "control_paypalpro":
        case "control_clickbank":
        case "control_2co":
        case "control_googleco":
        case "control_worldpay":
        case "control_onebip":
        case "control_authnet":
            html += '';
            
            if(prop.sublabels && typeof prop.sublabels.value == "string"){
                prop.sublabels.value = this.deepClone(default_properties[type].sublabels.value);
            }
            
            if (prop.paymentType.value == "donation") {
                
                html += this.subLabel('<input type="text" class="' + this.addValidation(classNames.textbox, prop, 'Numeric') + '" size="4" id="' + qid + '_donation" name="'+qname+'[price]" value="' + prop.suggestedDonation.value + '" >', prop.donationText.value, prop.currency.value);
                
            } else {
                opts = this.getProperty('products'); //prop.products.value;
                var ptype = prop.multiple.value == "Yes" ? 'checkbox' : 'radio';
                var totalCounter = {};
                var hasOptions = false;
                
                if (opts === false) {
                
                    if (passive) {
                        html += "<p style='margin-top:4px;'><img src='images/exclamation.png' align='top'> ";
                        html += "This integration has not yet been configured".locale() + ", <br>";
                        html += "Run the wizard for configurations.".locale() + "</p>";
                    } else {
                        return {
                            html: '',
                            hidden: true
                        };
                    }
                    
                } else {
                    
                    if((opts.length < 2) && (type != "control_authnet" && type != "control_paypalpro") && (!opts[0].options || (opts[0].options && opts[0].options.length < 1))){
                        if(passive){
                            html += '<p class="hidden-field-warning"><img src="images/information-middle.png" align="top"> ';
                            html += "Since there is only one product, this item will not be seen on the form.".locale() + " <br>";
                            html += "Run wizard to update payment details.".locale()+"<p>";
                            // Do not return here to display only one item on the preview
                        }else{
                            return {html:'<input type="hidden" name="'+qname+'[][id]" value="'+opts[0].pid+'" />', hidden: true};
                        }
                    }
                    
                    for (var pc = 0; pc < opts.length; pc++) {
                        var p = opts[pc];
                        
                        html += '<span class="form-product-item">';
                        html += '<input class="' + this.addValidation(classNames[ptype], prop) + '" type="' + ptype + '" id="' + qid + '_' + p.pid + '" name="' + qname + '[][id]" value="' + p.pid + '" />';
                        
                        totalCounter[qid + '_' + p.pid] = {
                            "price": p.price
                        };
                        
                        html += '<label for="' + qid + '_' + p.pid + '"> ';
                        
                        if (prop.paymentType.value == 'product') {
                            html += this.makeProductText(p.name, p.price, prop.currency.value, false, false, false);
                        } else {
                            html += this.makeProductText(p.name, p.price, prop.currency.value, p.period, p.setupfee, p.trial);
                        }
                        
                        html += '</label>';
                        
                        if (p.options && p.options.length > 0) {
                            html += '<br /><br />';
                        }
                        
                        if (p.options && p.options.length > 0) {
                            hasOptions = true;
                            for (var po = 0; po < p.options.length; po++) {
                                var opt = p.options[po];
                                
                                var sid = qid + '_' + opt.type + '_' + p.pid + '_' + po;
                                var opthtml = "";
                                if (opt.type == "quantity") {
                                    totalCounter[qid + '_' + p.pid].quantityField = sid;
                                }
                                
                                opthtml += '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[special_' + p.pid + '][item_' + po + ']" id="' + sid + '">';
                                
                                // html += '<option></option>'; // User must select an option, otherwise we will have to add required option for these fields
                                
                                var sopts = opt.properties.split('\n');
                                for (var v = 0; v < sopts.length; v++) {
                                    opthtml += '<option value="'+this.escapeValue(sopts[v])+'">' + sopts[v] + '</option>';
                                }
                                opthtml += '</select> ';
                                
                                opthtml = this.subLabel(opthtml, opt.name);
                                
                                html += opthtml;
                            
                            }
                            
                            // html += '<br clear="left" />';
                        }
                        if (p.icon) {
                            var imgCls = hasOptions? "form-product-image-with-options" : "form-product-image";
                            html += '<img src="' + p.icon + '" class="'+imgCls+'" height="50" width="50" align="absmiddle" />';
                        }
                        html += '</span><br />';
                    }
                    script += "      JotForm.totalCounter(" + this.toJSON(totalCounter) + ");\n";
                    if (prop.showTotal.value == 'Yes') {
                        html += '<br /><b>' + 'Total'.locale() + ':&nbsp; <span>' + this.formatPrice(0, prop.currency.value, "payment_total", true) + '</span></b>';
                    }
                }
            }
            
            if (type != "control_authnet" && type != "control_paypalpro") {
                break;
            } else {
                html += "<hr>";
            }
        case "control_authnet":
        case "control_paypalpro":
        case "control_address":
            var tableStyle = "", tableId = "";
            if (type == "control_paypalpro") {
                html += '<table summary="" class="form-address-table" border="0" cellpadding="4" cellspacing="0">';
                html += '<tr><th colspan="2" align="left">Payment Method</th></tr>';
                html += '<tr><td valign="middle">';
                html += '<input type="radio" class="paymentTypeRadios" id="' + qid + '_paymentType_credit" name="' + qname + '[paymentType]" value="credit"> <label for="' + qid + '_paymentType_credit" ><img align="absmiddle" src="' + this.HTTP_URL + 'images/credit-card-logo.png"></label>';
                html += '</td><td align="right">';
                html += '<input type="radio" class="paymentTypeRadios" id="' + qid + '_paymentType_express" name="' + qname + '[paymentType]" checked="checked" value="express"> <label for="' + qid + '_paymentType_express" ><img align="absmiddle" src="' + this.HTTP_URL + 'images/paypal_logo.png"></label>';
                html += '</td></tr></table>';
                tableStyle = 'style="display:none"';
                tableId = 'id="creditCardTable"';
            }
            
            
            html += '<table summary="" ' + tableStyle + ' ' + tableId + ' class="form-address-table" border="0" cellpadding="0" cellspacing="0">';
            
            if (type == "control_authnet" || type == "control_paypalpro") {
                
                var cc_firstName = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" name="' + qname + '[cc_firstName]" id="' + qid + '_cc_firstName" size="20" />', sublabel('cc_firstName'));
                var cc_lastName  = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" name="' + qname + '[cc_lastName]" id="' + qid + '_cc_lastName" size="20" />', sublabel('cc_lastName'));
                var cc_number    = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" name="' + qname + '[cc_number]" id="' + qid + '_cc_number" size="35" />', sublabel('cc_number'), '-');
                var cc_ccv       = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + '" type="text" name="' + qname + '[cc_ccv]" id="' + qid + '_cc_ccv" size="6" />', sublabel('cc_ccv'));
                
                var cc_exp_month = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[cc_exp_month]" id="' + qid + '_cc_exp_month" >';
                cc_exp_month += "<option></option>";
                for(var m=0; m < special_options.Months.value.length; m++){
                    cc_exp_month += '<option value="'+special_options.Months.nonLocale[m]+'">' + special_options.Months.value[m] + '</option>';
                }
                cc_exp_month += '</select>';
                cc_exp_month = this.subLabel(cc_exp_month, sublabel('cc_exp_month'), '/');
                
                var cc_exp_year  = '<select class="' + this.addValidation(classNames.dropdown, prop) + '" name="' + qname + '[cc_exp_year]" id="' + qid + '_cc_exp_year" >';
                var dyear = (new Date()).getYear() < 1000 ? (new Date()).getYear() + 1900 : (new Date()).getYear();
                cc_exp_year += "<option></option>";
                for(var y = dyear; y<(dyear+10); y++){
                    cc_exp_year += '<option value="'+y+'">' + y + '</option>';
                }
                cc_exp_year += '</select>';
                cc_exp_year = this.subLabel(cc_exp_year, sublabel('cc_exp_year'));
                
                // Payment form
                html += '<tr><th colspan="2" align="left">Credit Card</th></tr>';
                html += '<tr><td width="50%">';
                
                html += cc_firstName;
                
                html += '</td><td width="50%">';
                
                html += cc_lastName;
                
                html += '</td></tr><td colspan="2">';
                
                html += cc_number;
                
                html += cc_ccv;
                
                
                html += '</td></tr>';
                html += '<tr><td colspan="2">';
                
                html += cc_exp_month;
                
                html += cc_exp_year;
                                
                html += '</td></tr>';
                html += '<tr><th colspan="2" align="left">Billing Address</th></tr>';
            }
            
            var addr_line1   = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + ' form-address-line" type="text" name="' + qname + '[addr_line1]" id="' + qid + '_addr_line1" />', sublabel('addr_line1'));
            var addr_line2   = this.subLabel('<input class="' + classNames.textbox + ' form-address-line" type="text" name="' + qname + '[addr_line2]" id="' + qid + '_addr_line2" size="46" />', sublabel('addr_line2'));
            var addr_city    = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + ' form-address-city" type="text"  name="' + qname + '[city]" id="' + qid + '_city" size="21" />', sublabel('city'));
            var addr_state   = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + ' form-address-state" type="text"  name="' + qname + '[state]" id="' + qid + '_state" size="22" />', sublabel('state'));
            var addr_zip     = this.subLabel('<input class="' + this.addValidation(classNames.textbox, prop) + ' form-address-postal" type="text" name="' + qname + '[postal]" id="' + qid + '_postal" size="10" />', sublabel('postal'));
            var addr_country = '<select class="' + this.addValidation(classNames.dropdown, prop) + ' form-address-country" name="' + qname + '[country]" id="' + qid + '_country" >';
            addr_country += '<option selected>'+'Please Select'.locale()+'</option>';
            var locCountries = special_options.Countries.value;
            for (var loc = 0; loc < locCountries.length; loc++) {
        		var selec = "";
        		if ( prop.selectedCountry && (prop.selectedCountry.value == locCountries[loc]) ){
        			selec = ' selected="selected"';	
        		}
                addr_country += '<option '+selec+' value="' + locCountries[loc] + '">' + locCountries[loc] + '</option>';
            }
            addr_country += '<option value="other">'+'Other'.locale()+'</option></select>'; // Country
            addr_country = this.subLabel(addr_country, sublabel('country'));
            
            
            html += '<tr><td colspan="2">';
            html += addr_line1;
            html += '</td></tr><tr><td colspan="2">';
            html += addr_line2;
            html += '</td></tr><tr><td width="50%">';
            html += addr_city;
            html += '</td><td>';
            html += addr_state;
            html += '</td></tr><tr><td width="50%">';
            html += addr_zip;
            html += '</td><td>';
            html += addr_country;
            html += '</td></tr></table>';
            
            break;
        default: // If question is not defiend here
            html = "<b>Question is not defined,</b> should be defiend at <i>createInputHTML()</i> function";
    }
    
    return {
        html: html,
        script: script,
        prop: prop
    };
};
