OrangeBox = {
	HTTP_URL: "",
	title: null,
	formWindow: null,
	init: function() {
		document.observe('dom:loaded', function() {
				OrangeBox.setListeners();
				// Show the button.
				if (document.getElementById('feedback-tab')){
					document.getElementById('feedback-tab').style.display = 'block';
				}
        });

	},
	setListeners: function (){
		$$('.orangebox').each(function(link){
            if(link.__linkSet){ return; }
    		if (link.id == "feedback-tab-link"){
                link.__linkSet = true;
    			// calculate the box width
    			var innerText = link.innerHTML;
    			// calculate width and right.
    			var boxWidth = innerText.length*13;
    			// calculate the right
    			var right = 18 - (boxWidth/2);
    			var cssCode =	"#feedback-tab-link {"+
							    "   position: fixed;"+
							    "   top: 40%;"+
							    "   background-color: orange;"+
							    "   background-repeat: no-repeat;"+
							    "   background-position: center center;"+
							    "   display: block;"+
							    "   z-index: 100001;"+
							    "   right: "+right+"px;"+
							    "   border: none;"+
							    "   padding: 5px;"+
							    "   color: #fff;"+
							    "   font-style: bold;"+
							    "   font-size: 18px;"+
							    "   font-family: verdana;"+
							    "   height: 35px;"+
							    "   width: "+boxWidth+"px;"+
							    "   -moz-transform: rotate(90deg);"+
							    "   -webkit-transform: rotate(90deg);"+
							    "   -o-transform: rotate(90deg);"+
							    "   filter:  progid:DXImageTransform.Microsoft.BasicImage(rotation=1);"+
							    "   -ms-filter: \"progid:DXImageTransform.Microsoft.BasicImage(rotation=1)\";"+
							    "   transform: rotate(90deg);"+
							    "}"+
							    "#feedback-tab-link:hover {"+
							    "   background-color: #5C5C5C;"+
							    "   cursor: pointer;"+
							    "}";
				var ver = OrangeBox.getInternetExplorerVersion();
				if ( ver > -1 ){
					var newRight = right*2;
					cssCode += "#feedback-tab-link {right: " + newRight + "px;}";
					if (ver >= 7 && ver<8){
						cssCode += "#feedback-tab-link {right: 0px;}";
					}
				}
    			OrangeBox.addCss(cssCode);
    		}
    		link.setStyle({
    			cursor: 'pointer'
    		});
    		link.observe ('click', function(event){
    			OrangeBox.showWindow({
    				id: this.readAttribute('formid'),
    				title: this.readAttribute('title'),
    				width: this.readAttribute('width'),
    				height: this.readAttribute('height'),
    				base: this.readAttribute('base')
    			});
    		});
    	});		
	},
	getInternetExplorerVersion: function(){
	  var rv = -1; // Return value assumes failure.
	  if (navigator.appName == 'Microsoft Internet Explorer')
	  {
	    var ua = navigator.userAgent;
	    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
	    if (re.exec(ua) != null)
	      rv = parseFloat( RegExp.$1 );
	  }
	  return rv;
	},
	addCss: function(cssCode) {
		var styleElement = document.createElement("style");
		  styleElement.type = "text/css";
		  if (styleElement.styleSheet) {
		    styleElement.styleSheet.cssText = cssCode;
		  } else {
		    styleElement.appendChild(document.createTextNode(cssCode));
		  }
		  document.getElementsByTagName("head")[0].appendChild(styleElement);
	},
	showWindow: function(options){
		if (!options.base){
			options.base = "http://www.jotform.com/";
		}
		// Thema
	    Object.extend(document.windowDefaults, {
		    titleBackground: 'url('+options.base+'images/title-bg.png)',
		    buttonsBackground: '#fff url('+options.base+'images/footer-bg.png)',
		    background:'#f5f5f5',
		    borderWidth:6,
            titleTextColor: '#fff',
		    borderOpacity:0.5,
		    borderRadius: '10px',
            closeButton:'<button style="-moz-border-radius-bottomleft:6px !important;'+
            			'-moz-border-radius-bottomright:6px !important;'+
            			'-moz-border-radius-topleft:6px !important;'+
            			'-moz-border-radius-topright:6px !important;'+
            			'background-color:#222222;background-image:url('+options.base+'/images/button-back-black.png) !important;'+
            			'background-repeat:repeat-x;border:1px solid #444444;'+
            			'color:#DDDDDD;padding:5px 10px;text-shadow:0 1px 0 #000000 !important;"><img src="'+options.base+'images/cross.png" /></button>',
			dimColor: '#444',
			dimOpacity: 0.5,
			borderColor: '#000'
		});
        // open the window
        this.formWindow = document.window({
                title: options.title,
				width: options.width+"px",
				height: options.height+"px",
                content: "<iframe  allowtransparency=\"true\" src=\""+options.base+"form/"+options.id+"?feedback=true\" frameborder=\"0\" style=\"width:100%; height:465px; border:none;\"></iframe>"
        });
	}
};
OrangeBox.init();
try {
	OrangeBox.setListeners();
} catch(e){
	// do noting
}
