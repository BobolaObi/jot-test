if (typeof JotformFeedbackDefaultSettings !== "object"){	
	var JotformFeedbackDefaultSettings = {
		buttonText: "Feedback",
		verticalTextPadding: 7,
		horizontalTextPadding: 15,
		buttonSpace: 35,
		height: 500,
		width: 700,
		fontFamily: "verdana",
		fontColor: "#FFFFFF",
		fontStyle: "bold",
		background: "#F59202 center no-repeat",
		hoverBackground: "#5C5C5C",
		hoverFontColor: "#FFFFFF",
		buttonSide: "right",
		buttonAlign: "center",
		margin: 50,
		base: "http://www.jotform.com/",
		formId: null,
		windowTitleBgColor: false,
		windowTitleFontColor: false,
		fontSize: "18px",
		inlineStyle: "",
		iframeParameters: {},
		setBorderRadius: true,
		type: 0,
		openOnLoad: false,
		reverseButtonText: false,
		reCalculate: true
	};
}
if (typeof JotformFeedbackManager !== "object"){
	var JotformFeedbackManager = {
		buttons: {},
		addNewButton: function (button, reCalculate){
			JotformFeedbackManager.reCalculate = reCalculate;
			// Add button according to the location of the button.
			var key = button.options.buttonSide + button.options.buttonAlign;
			if (!Object.isArray(JotformFeedbackManager.buttons[key])){
				JotformFeedbackManager.buttons[key] = new Array();
			}
			JotformFeedbackManager.buttons[key][JotformFeedbackManager.buttons[key].length] = button;
			// only set the part that button is added.
			JotformFeedbackManager.setButtons(key);
		},
		setButtons: function (key){
			// add the widths of the buttons.
			var total = 0;
			for( var i=0; i < JotformFeedbackManager.buttons[key].length; i++){
				var tempButton = JotformFeedbackManager.buttons[key][i];
				total += tempButton.getButtonWidth();
			}
			var current = 0;
			for( var i=0; i < JotformFeedbackManager.buttons[key].length; i++){
				var tempButton = JotformFeedbackManager.buttons[key][i];
				if (JotformFeedbackManager.reCalculate){
					tempButton.calculateButtonPosition(total, current);
				}else{
					tempButton.calculateButtonPosition(0, 0);
				}
				tempButton.showButton();
				current += tempButton.getButtonWidth();
			}
		}
	};
}

var JotformFeedback = Class.create({
	/**
	 * initialize and add button to the browser.
	 */
	initialize: function (options){
		// if buttonText does not exists, assume its a link.
		options.isLink = !("buttonText" in options);
		// Extend the default options and set properties
		this.options = Object.extend( Object.deepClone(JotformFeedbackDefaultSettings), options);

		this.ieVer = Protoplus.getIEVersion();	// IE version. -1 for other browsers
		this.ghostButton = null;				// Invisible button for IE browsers. (Patch)
		this.formWindow = null;
		
		// those default values should be setled more logically
		this.options.windowTitle = this.options.windowTitle === undefined ? this.options.buttonText 
				: this.options.windowTitle;
		
		this.options.windowTitleBgColor = this.options.windowTitleBgColor === false ? this.options.background
				: this.options.windowTitleBgColor;
		this.options.windowTitleFontColor = this.options.windowTitleFontColor === false ? this.options.fontColor
				: this.options.windowTitleFontColor;

		this.documanLoaded= document.readyState == 'complete' || (this.jsForm && document.readyState == undefined);
		
		if (this.options.isLink){
			if (this.documanLoaded){
				this.setLinkListener();
			}else{
				document.ready(this.setLinkListener.bind(this));
			}
		}else{
			if (this.documanLoaded){
				this.setButtons();
			}else{
				document.ready(this.setButtons.bind(this));
			}
		}
		if (this.options.openOnLoad === true){
			if (this.documanLoaded){
				this.showWindow();
			}else{
				document.ready(this.showWindow.bind(this));
			}
		}
	},
	setButtons: function (){
		// create the button
		this.createButton();

		// create ghost button if neccessary
		if ( this.needGhostButton() ){
			this.createGhostButton();
		}
		JotformFeedbackManager.addNewButton(this, this.options.reCalculate);
	},
	setLinkListener: function (){
		var linkId = 'lightbox-'+this.options.formId;
		if ($('lightbox-'+this.options.formId)){
			$('lightbox-'+this.options.formId).observe ('click', this.showWindow.bind(this));
		}
	},
	needGhostButton: function (){
		return this.ieVer > 0 && this.options.buttonSide === "right";
	},
	createGhostButton: function (){
		// in ie put hidden button.
		this.ghostButton = new Element ("input",{
			type: 	"button",
			style:  "position: fixed;" +
					"right: 0px;" +
					"width: " + this.getButtonHeight() + "px;" +
					"height: " + this.getButtonWidth() + "px;" + 
					"cursor: pointer;" +
					"z-index: 998;" + 
					"border: 0;"
		});
		// hover button properties.
		this.setHoverProperties(this.ghostButton);
		// Add button to the document
		this.embedButton(this.ghostButton);
		// Set the listeners of the button
		this.setButtonListeners(this.ghostButton);
	},
	setHoverProperties: function(button){
		button.onmouseover  = this.mouseOver.bind(this);
	    button.onmouseout  = this.mouseOut.bind(this);
	},
	mouseOver: function (){
		this.button.setStyle({
			"background": this.options.hoverBackground,
			"color": this.options.hoverFontColor,
		    textShadow: "0px 0px 10px rgba(255,255,255,0.6)"
		});
	},
	mouseOut: function(){
		this.button.setStyle({
			"background": this.options.background,
			"color": this.options.fontColor,
			textShadow: "none"
		});
	},
	getButtonWidth: function(){
		if (this.ieVer > -1 && (this.options.buttonSide === "right" || this.options.buttonSide === "left") ){
			return this.button.offsetHeight;
		}else{
			return this.button.offsetWidth;
		}
	},
	getButtonHeight: function(){
		if (this.ieVer > -1 && (this.options.buttonSide === "right" || this.options.buttonSide === "left") ){
			return this.button.offsetWidth;
		}else{
			return this.button.offsetHeight;
		}
	},
	/**
	 * Create the link.
	 */
	createButton: function (){
		var style = this.setTransform() +
					"padding:" + this.options.verticalTextPadding + "px " + this.options.horizontalTextPadding + "px;" +
					"position:fixed;" +
					this.options.buttonSide + ":0;" +
					"visibility:hidden;" +
					"font-family:" + this.options.fontFamily + ";" +
					"font-size:" + this.options.fontSize + ";" +
					"color:" + this.options.fontColor + ";" +
					"background:" + this.options.background + ";" +
					"font-style: " + this.options.fontStyle + ";" +
					"z-index: 999;" + 
					"cursor:pointer;" +
					"text-align: center;" +
					"text-decoration: none;" +
					this.options.inlineStyle + ";";
		
		if (this.options.setBorderRadius){
			style +=  this.generateBoxRadius();
		}
		
		this.button = linkElement = new Element("a", {
            className:'jotform-feedback-link',
            id:'jotform-feedback-'+this.options.formId,
			style:  style
		}).update(this.options.buttonText);
		// hover button properties.
		this.setHoverProperties(this.button);
		// Add button to the document
		this.embedButton(this.button);
		// Set the listeners of the button
		this.setButtonListeners(this.button);
	},
	generateBoxRadius: function(){
		var radiusValues = "";
		var shadowValues = "0 0 5px";

		switch (this.options.buttonSide){
			case "bottom":
				radiusValues = "5px 5px 0 0";
				break;
			case "top":
				radiusValues = "0 0 5px 5px";
				break;
			case "right":
				if (this.options.reverseButtonText){
					radiusValues = "0 0 5px 5px";
				}else{
					radiusValues = "5px 5px 0 0";
				}
				shadowValues = "";
				break;
			case "left":
				if (this.options.reverseButtonText){
					radiusValues = "5px 5px 0 0";
				}else{
					radiusValues = "0 0 5px 5px";
				}
				shadowValues = "";
				break;
		}
		return  "-moz-border-radius: " + radiusValues + ";" +
				"-webkit-border-radius: " + radiusValues + ";" +
				"border-radius: " + radiusValues + ";" +
				"-moz-box-shadow: " + shadowValues + " rgba(0,0,0,0.3);" +
				"-webkit-box-shadow: " + shadowValues + " rgba(0,0,0,0.3);" +
				"box-shadow: " + shadowValues + " rgba(0,0,0,0.3);";
	},
	setTransform: function (){
		if (this.options.buttonSide === "left" || this.options.buttonSide === "right"){
			var degree = -90;
			var ieDegree = 3;
			if ( this.options.reverseButtonText ){
				degree = 90;
				ieDegree = 1;
			}
			return "-moz-transform:rotate("+degree.toString()+"deg);" +
					"transform: rotate ("+degree.toString()+"deg);" +
					"-o-transform:rotate("+degree.toString()+"deg);" +
					"-webkit-transform:rotate("+degree.toString()+"deg);" +
					"-ms-transform:rotate("+degree.toString()+"deg);" +
					"filter: progid:DXImageTransform.Microsoft.BasicImage(rotation="+ieDegree.toString()+");";
		}else{
			return "";
		}
	},
	/**
	 * Add button to the browser
	 */
	embedButton: function (button){
		document.body.appendChild(button);
	},
	/**
	 * 
	 */
	setButtonListeners: function(button){
		button.observe ('click', this.showWindow.bind(this));
	},
	/**
	 * Show the button again.
	 */
	showButton: function (){
		this.button.setStyle({
			visibility: ""
		});
	},
	setOriginPointAfterRotation: function (){
		switch (this.options.buttonSide ){
			case "left":
				// complete this part.
				if (this.ieVer === -1){  // this is for browsers other than IE
					this.button.setStyle({
						marginLeft:-1 * (this.getButtonWidth()/2 - this.getButtonHeight()/2) + "px"
					});
				}else if (this.ieVer === 7){
					
				}
				break;
			case "right":
				if (this.ieVer === -1){  // this is for browsers other than IE
					this.button.setStyle({
						marginRight:-1 * (this.getButtonWidth()/2 - this.getButtonHeight()/2) + "px"
					});
				}else if (this.ieVer === 8){	// for IE
					this.button.setStyle({
						marginRight: -1 * (this.getButtonWidth() - this.getButtonHeight()) + "px"
					});
				}

				break;
			default:
				// no rotation
				break;
		}
	},
	calculateButtonPosition: function (total, current){
		this.setOriginPointAfterRotation();
		switch (this.options.buttonAlign){
			case "top":
				if (this.ieVer === -1){  // this is for browsers other than IE
					this.button.setStyle({
						top:this.options.buttonSpace + current + "px",
						marginTop: (this.getButtonWidth()/2 - this.getButtonHeight()/2) + "px"
					});
				}else{
					this.button.setStyle({
						top:this.options.buttonSpace + current + "px" 
					});
					if (this.needGhostButton()){
						this.ghostButton.setStyle({
							top:this.options.buttonSpace + current + "px" 
						});
					}
				}
				break;
			case "bottom":
				if (this.ieVer === -1){  // this is for browsers other than IE
					this.button.setStyle({
						bottom: this.options.buttonSpace + current + "px",
						marginBottom: (this.getButtonWidth()/2 - this.getButtonHeight()/2) + "px"
					});
				}else if (this.ieVer === 8 ){
					this.button.setStyle({
						bottom: this.options.buttonSpace + current + "px",
						marginBottom: (this.getButtonWidth() - this.getButtonHeight()) + "px"
					});
					if (this.needGhostButton()){
						this.ghostButton.setStyle({
							bottom: this.options.buttonSpace + current + "px"
						});
					}
				}else if(this.ieVer === 7){
					this.button.setStyle({
						bottom: this.options.buttonSpace + current + "px"
					});
					if (this.needGhostButton()){
						this.ghostButton.setStyle({
							bottom: this.options.buttonSpace + current + "px"
						});
					}
				}
				break;
			case "left":
				this.button.setStyle({left: (this.options.buttonSpace + current) + "px" });
				break;
			case "right":
				this.button.setStyle({right: (this.options.buttonSpace + current) + "px" });
				break;
			case "center":
				if (this.options.buttonSide === "left" || this.options.buttonSide === "right"){

					var marginValue = 0;
					
					if (this.ieVer === -1){  // this is for browsers other than IE
						marginValue = -1 * (total/2 - this.getButtonWidth()/2 ) + -1 * this.getButtonHeight()/2 
						+ current;
					}else{
						marginValue = -1 * total/2 + current;
					}
					this.button.setStyle({
						top: this.options.margin + "%",
						marginTop: marginValue + "px"
					});
					if (this.needGhostButton()){
						this.ghostButton.setStyle({
							top: this.options.margin + "%",
							marginTop: marginValue + "px"
						});
					}
				}else{
					this.button.setStyle({
						left: this.options.margin + "%",
						marginLeft: (-1*total/2 + current) + "px"
					});
				}
				break;
		}
	},
	/**
	 * Show window function is carried from the old system.
	 */
	showWindow: function (){
		var settings = {
			    titleBackground: this.generateBackgroundForWindow(),
			    buttonsBackground: '#fff url(' + this.options.base + 'images/footer-bg.png)',
			    background:'#f5f5f5',
			    borderWidth:6,
			    borderOpacity:0.5,
			    borderRadius: '10px',
			    contentPadding: 0,
	            closeButton:'<div style="width:22px; height:19px; background:url(' +
	        				this.options.base + '/images/close-wiz.png) no-repeat !important;">' +
	            			'</div>',
				dimColor: '#444',
				dimOpacity: 0.5,
				borderColor: '#000',
				titleTextColor: this.options.windowTitleFontColor
				
		};

		var types = [{}, {
            borderWidth: 9,
            borderOpacity: 1,
            borderColor: '#fff',
            hideTitle: true,
            closeButton: '<img src="' + this.options.base + 'images/close-wiz-white.png" style="position:relative;top:-11px;right:-11px;" />'
        }, {
            borderWidth: 9,
            borderOpacity: 0.8,
            hideTitle: true,
            closeButton: '<img src="' + this.options.base + 'images/close-wiz-black.png" style="position:relative;top:-11px;right:-11px;" />',
            borderColor: '#000'
        }];
        
		Object.extend(settings, types[this.options.type]);
		Object.extend(document.windowDefaults, settings);
        // open the window
        this.formWindow = document.window({
                title: this.options.windowTitle,
				width: this.options.width + "px",
				height: this.options.height + "px",
                content:"<iframe  allowtransparency=\"true\" src=\"" + this.options.base + "form/" + this.options.formId + 
                	"?" + $H(this.options.iframeParameters).toQueryString() + "\" frameborder=\"0\" style=\"width:100%;" + 
                	"height:" + this.options.height + "px; border:none;\"></iframe>"
        });
	},
	generateBackgroundForWindow: function (){
		if (this.options.windowTitleBgColor === false){
			return 'url(' + this.options.base + 'images/title-bg.png)';
		}else{
			return this.options.windowTitleBgColor;
		}
	}
});

var scripts = document.getElementsByTagName("script");
eval( scripts[ scripts.length - 1 ].innerHTML );