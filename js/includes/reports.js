var Reports = {
    questions: false,	// Form questions to be used in charts
    properties: {},		// Form properties to get form title height and such
    toolbox: false,		// Toolbox element reference
    chartable: {nochart:true},		// Chartable fields list
    selected: false,	// Currently selected widget
    isEdit: false,		// Edit mode toggle
    widgetID: 0,		// Auto increment widget id
    nicEditor: false,		// nicEditor instance
    id: "",
	title: 'Report'.locale(),
    password: false,
    hasPassword: false,
    config: {
        "widget-1": {
            "height": "16px",
            "width": "16px",
            "top": "30px",
            "left": "730px",
            "type": "arrow",
            "direction": "ne"
        },
        "widget-2": {
            "height": "18px",
            "width": "366px",
            "top": "50px",
            "left": "380px",
            "type": "text",
            "body": "<b>Click \"Edit Report\" button to start creating your report.<br><\/b>"
        }
    },
    /**
     * gets the form properties from server then places in properties array in a fashionable way :)
     */
    getFormProperties: function(response){
        var $this = this;
        $H(response.form).each(function(prop){
            var qid = prop.key.split("_")[0];
            var key = prop.key.split("_")[1];
            var value = prop.value;
            if(!$this.properties[qid]){ 
                $this.properties[qid] = {};
            }
            $this.properties[qid][key] = value;
        });
    },    
    /**
     * Create the report back from the saved configuration
     * @param {Object} config
     */
    retrieve: function(response){
        
		if(response.success === false){
            
            this.id = 'session';
            $('report-stage').clearTuts = true;
            var defaultConfig = {
                "widget-1": {
                    "height": "67px",
                    "width": "395px",
                    "top": "20px",
                    "left": "190px",
                    "type": "header",
                    "header": this.properties.form.title,
                    "subheader": ""//"Reports-for this "+this.properties.form.title
                }
            };
            
            if(this.chartable && !this.chartable.nochart){
                
                $H(this.chartable).each(function(el, i){
                    if(i > 1){ throw $break; }
                    
                    var chartOptions = ["Pie", "Bar"];
                    
                    defaultConfig['widget-'+(i+2)] = {
                        "height": "250px",
                        "width": "500px",
                        "top": (145+(280*(i)))+"px",
                        "left": "140px",
                        "type": "chart",
                        "chartOptions": {
                            "chartType": chartOptions[i % 2],
                            "dataField": el.key,
                            "height": 250,
                            "width": 500,
                            "entryLimit": 5
                        }
                    };
                });
                
            }else{
                defaultConfig['widget-2'] = {
                    "height": "709px",
                    "width": "775px",
                    "top": "80px",
                    "left": "10px",
                    "type": "grid"
                };
            }
            
            this.config = defaultConfig;
        }else{
    		this.config = response.config;
            this.id = response.id;
            this.title = response.title;
            this.hasPassword = response.hasPassword;
            ($('form-title') && $('form-title').update(this.title));
        }
        var ids = [];
        $H(this.config).each(function(pair){
            
            var id = pair.key;
            var o = pair.value;
            
            ids.push(parseInt(id.replace('widget-', ''), 10));
            var el = new Element('div', {className:'widget', id:id});
            
            /*
            alert({
                height: o.height,
                width:  o.width,
                top:    o.top,
                left:   o.left,
                position:'absolute'
            });
            */
            
            el.setStyle({
                height: o.height,
                width:  o.width,
                top: 	o.top,
                left:   o.left,
                position:'absolute'
            });
            $('report-stage').insert(el);
            this.createWidget(el, o);
            el.hideHandlers && el.hideHandlers();
        }.bind(this));
        
        this.widgetID = ids.max()+1;
    },
    /**
     * Gets the pure dimensions of the form, remove paddings, margins and border widths from the element
     * @param {Object} el
     */
    getPureDimensions: function(el){
        try{
            var oldD = {
                padding: el.getStyle('padding'),
                margin:  el.getStyle('margin'),
                border:  el.getStyle('border')
            };
            
            el.setStyle({margin:'', padding:'', border:''});
            
            var newD = {
                top:    el.getStyle('top'),
                left:   el.getStyle('left'),
                height: el.getStyle('height'),
                width:  el.getStyle('width')
            }; 
            
            el.setStyle(oldD);
            
        }catch(e){
            return {
                top: el.getStyle('top'),
                left:   el.getStyle('left'),
                height: el.getStyle('height'),
                width:  el.getStyle('width')
            };
        }
        
        return newD;
    },
    
    /**
     * Show share options for this report
     */
    share: function(){
        
        var $this = this;
        
        this.save(function(){
            var div = new Element('div');
            
            div.insert('<h3>Sharing Options:</h3>');
            
            div.insert('Share By URL:');
            div.insert('<input onclick="this.select();" type="text" readonly value="'+Utils.HTTP_URL+'report/'+$this.id+'" style="font-size:14px; width:98%; text-align:center; padding:5px; margin:4px 0 0px; background:white; display:inline-block;border:1px solid #ccc;">');
            div.insert('<label><input type="checkbox" id="rep-password"> Password Protect</label><br><br>');
            div.insert('Embed to your site:');
            div.insert('<textarea onclick="this.select();" wrap="off" readonly style="font-size:13px; width:98%; height:100px; padding:5px; margin:4px 0 14px; background:white; display:inline-block;border:1px solid #ccc;" >'+
                '<iframe\n   allowtransparency="true"\n   src="'+Utils.HTTP_URL+'report/'+$this.id+'?embed"\n   frameborder="0"\n   style="width:100%; height:810px; border:none;"\n   scrolling="no">\n</iframe>'.replace(/\"/g, '&quot;')+
            '</textarea>');
            
            document.window({
                title:'Share Report',
                width:560,
                content: div,
                buttons:[{
                    title:'Close',
                    handler: function(w){
                        w.close();
                    }
                }],
                onInsert: function(){
                    if($this.hasPassword){
                        $('rep-password').checked = true;
                    }
                    
                    $('rep-password').observe('click', function(){
                        if($('rep-password').checked){
                            Utils.prompt('Select a Password', 'Enter Password', 'Password Protect', function(value, but, ok){
                                if(ok){
                                    $this.password = value;
                                    $this.hasPassword = true;
                                }else{
                                    $('rep-password').checked = false;
                                    $this.hasPassword = false;
                                }
                            });
                        }else{
                            $this.password = '%%removepassword%%';
                            $this.hasPassword = false;
                        }
                    });
                },
                onClose: function(){
                    $this.save();
                }
            });
        });
    },
    
    /**
     * Finally Save the report
     */
    save: function(callback){
        var config = {};
        var $this = this;
        if($this.nicEditor){
            $this.nicEditor.closeCurrent();
        }
        
        $$('.widget').each(function(widget){
            var pure = $this.getPureDimensions(widget);
            
            var type = widget.readAttribute('type');
            
            config[widget.id] = {
                height: pure.height,
                width: pure.width,
                top: pure.top,
                left: pure.left,
                type: type 
            };
            
            switch(type){
                case "image":
                    config[widget.id].src = widget.select('.widget-image')[0].src;
                    break;
                case "grid":
                    // Nothing to save now 
                    break;
                case "arrow":
                    var dir = 'ne';
                    var m = widget.select('.widget-arrow')[0].src.match(/.*?small_arrows\/arrow\-(.*?)\.png.*?/);
                    if(m){
                        dir = m[1];
                    }
                    config[widget.id].direction = dir;
                    break;
                case "header":
                    config[widget.id].header = widget.select('.widget-header')[0].innerHTML;
                    config[widget.id].subheader = widget.select('.widget-subheader')[0].innerHTML;
                    break;
                case "text":
                    config[widget.id].body = widget.select('.widget-text')[0].innerHTML;
                    break;
                case "chart":
                    config[widget.id].chartOptions = widget.select('.widget-chart')[0].opts;
                    break;
            }
        });
        
		$('saveButton-icon').src = "images/loader-big.gif";
		
		Utils.Request({
			parameters:{
				action:'saveReport',
				configuration: Object.toJSON(config),
				reportID: this.id,
				formID: this.properties.form.id,
				title: this.title,
                password: this.password
			},
			onSuccess: function(res){
				$this.id = res.id;
				$('saveButton-icon').src = "images/blank.gif";
				$('saveButton-icon').className = "toolbar-save";
                callback && callback();
			},
            onFail: function(res){
                Utils.alert(res.error);
            }
		});
		
        return config;
    },
    
    /**
     * Open/Close Widgets toolbox and edit markers
     */
    toggleEdit: function(){
        if(this.toolbox.visible()){
			$('form-title').removeClassName('edit-title-active');
            $('report-stage').addClassName('finished-mode');
            this.isEdit = false;
            document.stopEditables = true;
            if(this.nicEditor){
                this.nicEditor.closeCurrent();
            }
            $$('.grid-dims').invoke('hide');
            this.selected && this.selected.removeClassName('widget-selected');
            $('editButton').removeClassName('button-over');
            this.toolbox.hide();
            // Make sure widget borders are not shown, widgets are immutable.
            $$('.widget').each(function(widget){
                widget.hideHandlers && widget.hideHandlers();
            });
        }else{
            
            /*if($('report-stage').clearTuts){
                $('report-stage').clearTuts = false;
                $('report-stage').update();
            }*/
            
			$('form-title').addClassName('edit-title-active');
            $('report-stage').removeClassName('finished-mode');
            this.isEdit = true;
            document.stopEditables = false;
            $$('.grid-dims').invoke('show');
            $('editButton').addClassName('button-over');
            this.toolbox.show();
            // Go back to the edit mode.
            $$('.finished-mode').each(function(widget){
                widget.removeClassName("finished-mode");
            });
            $$('.widget').each(function(widget){
                widget.showHandlers && widget.showHandlers();  
            });
        }
    },
    /**
     * Create the toolbox
     */
    makeToolBox: function(){
        var div = new Element('div', {className:'report-tools'});
        var title = new Element('div', {className:'report-tools-title'});
        title.insert('Widgets'.locale());
        // Close button
        title.insert('<button class="big-button buttons buttons-black" style="float:right; padding:1px 3px;" onclick="Reports.toggleEdit()"><img src="images/blank.gif" class="index-cross" /></button>');
        
        var content = new Element('div', {className:'report-tools-content'});
        content.insert($('report-tools').show());
        
        div.insert(title).insert(content);
        
        div.setDraggable({
            handler: title
        });
        this.toolbox = div.hide();
        $(document.body).insert(div);
    },
	
    /**
     * Get the cratable fields from server
     */
    getChartableElements: function(response){
        if(response.success){
            this.chartable = response.data;
        }
    },
	
    /**
     * Checks if the element is stage or is in stage
     * @param {Object} element
     */
    isStage: function(element){
        element = $(element);
        if(!element.parentNode){
            return false;
        }
        if(element && element.tagName == "BODY"){
            return false;
        }
        if(element.id == "report-stage"){
            return $(element);
        }
        return this.isStage(element.parentNode);
    },
    
    /**
     * Initiate reports page
     */
    init: function(){
        
        if(document.readCookie('savedReport')){
            this.retrieve(document.readCookie('savedReport').evalJSON()); 
        }
		
        if($('form-title')){            
    		$('form-title').editable({
    			className:'edit-title',
    			onEnd: function(el, val){
    				this.title = val;
    			}.bind(this)
    		});
        }
		
        document.stopEditables = true;
        this.makeToolBox();
        var $this = this;
        
        $$('.drag').each(function(d){ // For each widget tool
            d.setUnselectable();
            d.setDraggable({
                clone:true,
                snap: [15, 5],
                // offset: [15, 15], // We don't need this now because we have dimming div in grid and it fixes the focus problem
                revert:{
                    opacity:0,
                    remove:true,
                    height:0,
                    width:0,
                    fontSize:0
                },
                onEnd: function(drag, h, e){
                    drag.hide();
                    var el = document.getUnderneathElement(e);
                    
                    // If the element dropped on the stage then calculate the 
                    // relative positions and place it in the stage
                    if($this.isStage(el)){
                        drag.show();
                        var off = $('report-stage').cumulativeOffset();
                        var doff = drag.cumulativeOffset();
                        var top = doff.top-off.top;
                        var left = doff.left-off.left;
                        drag.setStyle({left: left+'px', top: top+'px'});
                        $('report-stage').insert(drag);
                        
                        return false;
                    }
                    drag.show();
                },
                changeClone: function(el, x, y){
                    var div = new Element('div', {className:'widget'});
                    if(el.readAttribute('value') == 'chart' && $H(Reports.chartable).toArray().length < 1){
                        div.update('There is no chartable question');
                        return div;
                    }
                    //return $this.createInitialWidget(div, el.readAttribute('value'), el.innerHTML);
                    return $this.createWidget(div, {
                        type: el.readAttribute('value')
                    });
                }
            });
        });
        
        // When click on the stage remove the last selected
        $('report-stage').observe('mousedown', function(e){
            if($this.selected && e.target.id == "report-stage"){
               $this.selected.makeUnSelected();
            }
        });
    },
    
    print_id:0,			// Print frame ID
    /**
     * Prints the stage in a new page
     */
    print: function(){
        if(this.isEdit){
            this.toggleEdit();
        }
        var template = "";
        template += '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        template += '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>';
        template += '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        template += '<style>html, body{ height:100%; width:100%; margin:0px; padding:0px;overflow: visible; }</style>';
        template += '<link rel="stylesheet" type="text/css" href="css/styles/form.css"/>';
        template += '<link rel="stylesheet" type="text/css" href="css/includes/reports.css"/>';
        template += '</head><body class="finished-mode">';
        template += '<style>html, body{height:100%;width:100%;margin:0px;padding:0px;}body{font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;font-size:12px;}</style>';
        template += $('report-stage').innerHTML;
        template += '</body></html>';
        
        var iframe = new Element('iframe', {name:'print_frame_'+this.print_id, id:'print_frame_'+this.print_id}).setStyle({height:'1px', width:'1px', border:'none'});
        $(document.body).insert(iframe);
        var frame = window.frames["print_frame_"+this.print_id++];
        frame.document.open();
        frame.document.write(template);
        setTimeout(function(){
            frame.window.print();
            iframe.remove();
        }, 3000);
        frame.document.close();
        
    },
    /**
     * Gets the N top values, Such as max 10 entries
     * @param {Array} answers Array of values
     * @param {Int} n  Number to slice the array
     */
    createTopList: function(answers, n) {
        var sorted = $H(answers).toArray(); /*.sortBy(function(s) {
            return s.value;
        }).reverse();*/
        
        if(sorted.length == n){ n = n+1; }
        n = n-1;
        
        var top = sorted.slice(0, n);
        var other = sorted.slice(n);
        var otherTotal = 0;
        $A(other).each(function(node) {
            otherTotal += parseInt(node[1], 10);
        });
        var data = [], labels = []; 
        $A(top).each(function(node){
            data.push(node[1]);
            labels.push(node[0] || "N/A".locale());
        });
        
        if(otherTotal > 0){
            data.push(otherTotal);
            labels.push("Other".locale());
        }
        
        return { data: data, labels: labels };
    },
    
    createWidgetControls: function(widget, options){
        options = options || {};
        var $this = this;
        var title = new Element('div', {className: 'widget-title'});
        
        title.setStyle({minWidth:options.items? '80px' : '60px'});
        
        var moveImage = new Element('img', {src:'images/move1.png'}).setStyle("float:left;cursor:default; margin:0 6px;");
        moveImage.onmousemove = moveImage.onmousedown = function(){ return false; }; // Disable default drag effect of browsers on images
        
        var menuButton = new Element('img', {src:'images/gear.png'}).setStyle("float:left;cursor:default; margin:0; margin-right:6px;");
        var deleteButton = new Element('img', {src:'images/blank.gif', className:'index-cross'}).setStyle('float:left;cursor:default; margin:0 6px;');
        var menuContent = new Element('div', {className:'menu-content'}).hide();
        
        widget.menuContent = menuContent;
        widget.observe("click", function() {
            if(!$this.isEdit){ return; } // Return if it's not in editmode
            if($this.selected && $this.selected != widget){
                $this.selected.makeUnSelected();
            }
			
            widget.makeSelected();
        });
        
		widget.makeSelected = function(){
			$this.selected = widget.addClassName('widget-selected'); // Make current element selected
            if(widget.dim){
                widget.dim.hide();
            }
			return widget;
		};
		
        widget.makeUnSelected = function(){
			
			if($this.nicEditor){
                $this.nicEditor.closeCurrent();
            }
            
            if(widget.dim){
                widget.dim.show();
            }
            
            widget.menuContent.close();
            widget.removeClassName('widget-selected'); // Remove last selected Item
            widget.setStyle({zIndex:''});
			$this.selected = false;
			return widget;
		};
		
        menuContent.close = function(){
            title.removeClassName('menu-open');
            menuContent.hide();
            widget.showHandlers && widget.showHandlers();
        };
        
        menuContent.open = function(){
            title.addClassName('menu-open');
            menuContent.show();
            widget.hideHandlers && widget.hideHandlers();
        };
        /**
         * Menu button click
         */
        menuButton.observe('click', function(){
            try{
                if(menuContent.visible()){
                    menuContent.close();
                }else{
                    menuContent.open();
                }
            }catch(e){ console.error(e); }
        });
        
        if(!options.items){
            menuButton.hide();
        }
        
        deleteButton.observe('click', function(){ widget.remove(); });
        // Insert the title buttons
        title.insert(moveImage).insert(menuButton).insert('<div class="vline"></div>').insert(deleteButton);
        widget.insert(menuContent).insert(title);
        
        widget.setDraggable({
            handler: moveImage,
            dynamic: false,
            constrainParent: true,
            snap:10
        });
        
        // Insert menu header which will be shared across every widget's menu.
        menuHeader = new Element("div", { className: "widget-menu-header"}).insert("Configuration".locale());
        menuContent.insert(menuHeader);
        if(options.resizableOptions !== false){
            widget.resizable(Object.extend({ constrainViewport: false, constrainParent: true }, options.resizableOptions || {}));
        }
        
        var fields = {};
        
        $A(options.items).each(function(item){
            var line = new Element("div", { className: "widget-menu-item"});
            if(item.label){
                line.insert(new Element('label').update(item.label));
            }
            
            if(item.type){
                var input = new Element('input', {type:item.type, size: item.size || 20});
                
                if(input.type == "button"){
                    input.addClassName('big-button buttons');
                }
                
                input.value = item.value;
                line.insert(input);
                fields[item.name] = input;
                if(item.handler){
                    input.observe('click', function(){
                        item.handler(menuContent, fields);
                    });      			
                }
            }else if (item.field){
                var field = item.field(menuContent);
                fields[item.name || item.label] = input;
                line.insert(field);
            }
            
            menuContent.insert(line);
        });
    },
    
    createWidget: function(container, options){
        try{
        var $this = this;
        
        container.id = "widget-"+this.widgetID++;
        container.writeAttribute('type', options.type);
        
        switch(options.type){
        
            case "grid":
                
                var iframe = new Element('iframe', {
                    className:'widget-grid',
                    src:'grid.php?formID='+this.properties.form.id,
                    border:0,
                    allowtransparency:true
                }).setStyle('border:none; height:100%; width:100%');
                
                container.insert(iframe);
                var dim = new Element('div', {className:'grid-dims'}).setStyle('background:; position:absolute; top:0;left:0; height:100%; width:100%');
				if(!this.isEdit){
					dim.hide();
				}
                container.insert(dim);
                container.dim = dim;
                this.createWidgetControls(container);
                container.setStyle('width:'+ (options.width ||  700) + 'px; height:'+ (options.height || 285) + 'px;');
            break;
            case "image":    			
                var img = new Element('img', {className:'widget-image', src: options.src || "images/logo.png", height:'100%', width:'100%'});
                container.setStyle('padding:10px');
                this.createWidgetControls(container, {
                    items:[{
                        label:'Image Source'.locale(),
                        name:'source',
                        value: img.src,
                        type:'text',
                        size:'25'
                    },{
                        label: false,
                        type:'button',
                        value:'Save'.locale(),
                        handler: function(menu, fields){
                            img.src = fields.source.value;
                            menu.close();
                        }
                    }]
                });
                
                
                container.insert(img);
            break;
            case "header":
                var head = new Element('h2', {className:'widget-header'}).setStyle('margin:0px;padding:0px;');
                var subhead = new Element('span', {className:'widget-subheader'});
                
                head.update( options.header || "Click to edit this text...".locale() );
                subhead.update( options.subheader || '<span class="subheader-edit-message">'+"Click to edit sub header...".locale()+"</span>" );
                
                head.editable({className:'edit-header'});
                subhead.editable();
                
                container.setStyle('background:#fff;padding:5px;');
                
                this.createWidgetControls(container);
                container.insert(head);
                container.insert("<hr>");
                container.insert(subhead);
            break;
            case "arrow":
                var arrow = new Element('img', {className:'widget-arrow', src:"images/controls/small_arrows/arrow-"+(options.direction || "ne")+".png"});
                container.setStyle('padding:10px;');
                this.createWidgetControls(container, {
                    items:[{
                        label:'Arrow Direction',
                        field: function(menu){
                            var select = new Element('select');
                            var directions = [['n', 'North'], ['e', 'East'], ['s', 'South'], ['w', 'West'], ['ne', 'North East'], ['nw', 'North West'], ['se', 'South East'], ['sw', 'South West']];
                            $A(directions).each(function(opt){
                                select.insert(new Element('option', {value:opt[0], selected: ((options.direction || "ne") == opt[0])}).update(opt[1]));
                            });
                            select.observe('change', function(){
                                arrow.src = "images/controls/small_arrows/arrow-"+select.value+".png";
                                menu.close();
                            });
                            return select;
                        }
                    }],
                    resizableOptions: false
                });
                container.insert(arrow);
            break;
            case "text":
                
                var dummy = "<b>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</b><br />Praesent augue elit, pulvinar id vestibulum nec, congue id ligula. Donec id nisi orci. Quisque sed augue nisi. Morbi eu viverra metus. Ut quis nisi et dolor vulputate pellentesque. Maecenas molestie augue nisi, pulvinar commodo lorem. Aenean sollicitudin iaculis tortor, at hendrerit ipsum egestas ut. Phasellus lacinia feugiat lectus. Sed tellus tortor, eleifend id vestibulum et, eleifend eget leo. Mauris sollicitudin pharetra mauris eu tincidunt. Pellentesque nec neque odio, eu iaculis dui.";
                var text = new Element('div', {className:'widget-text', id:'editor_'+($this.widgetID-1)});
				options.width = !options.width? "380px" : options.width;
                container.setStyle('padding:10px; width:'+options.width);
                if(options.body){
                    text.insert(options.body);
                }else{
                    text.insert(dummy);
                }
                
                container.insert(text);
                
                text.onclick = function(){
                    if(!$this.isEdit){ return; }
                    container.setStyle('padding:0; height:auto; width:'+(container.getWidth()-2)+'px; background:#fff');
                    var ok = new Element('button', {className:'big-button buttons buttons-red'}).insert('Complete').setStyle('position:absolute; top:-23px; right:70px; padding:2px 6px');
                    text.insert({after:ok});
                    
                    if($this.nicEditor){
                        $this.nicEditor.closeCurrent();
                    }
                    
                    $this.nicEditor = new nicEditor({iconsPath:'images/nicEditorIcons.gif', 
                        buttonList :['bold','italic','underline','strikeThrough','left','center','right','justify','subscript','superscript','fontSize', 'forecolor', 'bgcolor']
                    }).panelInstance(text.id, {hasPanel : true});
                    
                    ok.onclick = function(){
                        $this.nicEditor.removeInstance(text.id);
                        container.setStyle('padding:10px;width:'+ (container.getWidth()-22) + "px" ).setStyle({background:''});
                        container.showHandlers && container.showHandlers();
                        ok.remove();
                        $this.nicEditor = false;
                    };
                    
                    $this.nicEditor.closeCurrent = ok.onclick;
                    
                    container.hideHandlers && container.hideHandlers();
                };
                this.createWidgetControls(container);
                
            break;
            case "chart":
                
                var opts = Object.extend({
                    chartType: 'Pie',
                    dataField: false,
                    height: 250,
                    width: 500,
                    entryLimit: 5  // Default limit for data to be shown on charts
                }, options.chartOptions || {});

                container.setStyle({height:opts.height+'px', width:opts.width+'px'});
                // Get first chartable element as a default chart
                var question = $H(this.chartable).toArray()[0][1];
                
                
                if(opts.dataField !== false){
                    question = this.chartable[opts.dataField];
                }else{
                    opts.dataField = question.qid;
                }
                
                var topList = this.createTopList(question.answers, opts.entryLimit);  // get the top N entries from answers
                
                // Create chart instance
                var ch = new Charts({
                    data : topList.data,
                    labels : topList.labels,
                    title : question.text,
                    type : opts.chartType,
                    height: opts.height,
                    width: opts.width
                });
                
                // Draw chart
                var pie = ch.createChart();
                
                pie.opts = opts; // Place the options in chart object to retrieve them in save proccess 
                
                // Insert chart into document
                container.insert(pie);
                
                this.createWidgetControls(container, {
                    items:[{
                        label: 'Chart Data'.locale(),
                        field: function(menu){
                        
                            var dataFields = new Element('select');
                            $H($this.chartable).each(function(question){	// Create the field dropdown here
                                dataFields.insert(new Element('option', { value:question.key, type:question.value.type }).insert(question.value.text));
                            });
                            
                            dataFields.observe('change', function(){
                                var sel = dataFields.getSelected();
                                question = $this.chartable[sel.value];
                                opts.dataField = sel.value;
                                var topList = $this.createTopList(question.answers, opts.entryLimit);
                                var prefix = "";
                                
                                // If the value is only an integer and we know it
                                // then place Rated or such text in front of it
                                switch(sel.readAttribute('type')){
                                    case "control_scale":
                                    case "control_star":
                                    case "control_rating":
                                    case "control_grading":
                                        prefix = "Rated".locale()+" ";
                                    break;
                                }
                                
                                pie.chart.updateChart({
                                    data : topList.data,
                                    labels : topList.labels,
                                    title : question.text,
                                    type : opts.chartType,
                                    labelPrefix: prefix
                                });
                                menu.close();
                            });
                            
                            return dataFields;
                        }
                    }, {
                        label: 'Chart Type'.locale(),
                        field: function(menu){
                        
                            var chartTypes = new Element('select');
                            $H(ch.types).each(function(type){
                                chartTypes.insert(new Element('option', { selected: type.key == opts.chartType, value: type.key }).insert(type.key));
                            });
                            chartTypes.observe('change', function(){
                                opts.chartType = chartTypes.value;
                                pie.chart.changeType(chartTypes.value);
                                menu.close();
                            });
                            
                            return chartTypes;
                        }
                    }, {
                        label:'Entry Limit'.locale(),
                        name:'limit',
                        type:'text',
                        size: 3,
                        value: opts.entryLimit
                    }, {
                        label:false,
                        type:'button',
                        value : 'OK'.locale(),
                        handler: function(menu, fields){
                            opts.entryLimit = parseInt(fields.limit.value, 10) || 5;
                            fields.limit.value = opts.entryLimit;
                            
                            var topList = $this.createTopList(question.answers, opts.entryLimit);
                            pie.chart.updateChart({
                                data : topList.data,
                                labels : topList.labels
                            });                        
                            menu.close();
                        }
                    }],
                    resizableOptions:{
                        maxArea: 300000,
                        constrainViewport: false,
                        constrainParent: true,    
                        onResizeEnd: function(height, width){
                            opts.height = height-2;
                            opts.width = width-2;
                            pie.chart.updateSize(width-2, height-2); // Update the chart size when widget is resized
                        },
                        imagePath: '../../images/resize.png'
                    } 
                });
            break;
        }
        }catch(e){
            console.error(e);
        }
        return container;
    }    
};

document.ready(function(){ Reports.init(); });
var Utils = Utils || new Common();