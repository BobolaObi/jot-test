/**
 * Includes a Form with javascript
 * @param {Object} formId
 * @param {Object} initialHeight
 * @param {Object} iframeCode
 */
function FrameBuilder (formId, initialHeight, iframeCode){
    this.formId = formId;
    this.initialHeight = initialHeight;
    this.iframeCode = iframeCode;
    this.frame = null;
    this.timeInterval= 200;
    
    // initialize function for object
    this.init = function(){
        this.createFrame();
        this.addFrameContent(this.iframeCode);
    };
    
    // Create the frame
    this.createFrame = function(){
        var htmlCode = "<"+"iframe src=\"\" allowtransparency=\"true\" frameborder=\"0\" name=\""+this.formId+"\" id=\""+this.formId+"\" style=\"width:100%; height:"+this.initialHeight+"px; border:none;\" scrolling=\"no\"></if"+"rame>";
        document.write(htmlCode);
        // also get the frame for future use.
        this.frame = document.getElementById(this.formId);
        // set the time on the on load event of the frame
        this.addEvent (this.frame, 'load', this.bindMethod(this.setTimer, this));
    };
    
    // add event function for different browsers
    this.addEvent = function( obj, type, fn ) {
        if ( obj.attachEvent ) {
            obj["e"+type+fn] = fn;
            obj[type+fn] = function() { obj["e"+type+fn]( window.event ); };
            obj.attachEvent( "on"+type, obj[type+fn] );
        }
        else{
            obj.addEventListener( type, fn, false );   
        }
    };
    
    this.addFrameContent = function (string){
        string = string.replace(new RegExp('src\=\"[^"]*captcha.php\"><\/scr'+'ipt>', 'gim'), 'src="http://api.recaptcha.net/js/recaptcha_ajax.js"></scr'+'ipt><'+'div id="recaptcha_div"><'+'/div>'+
                '<'+'style>#recaptcha_logo{ display:none;} #recaptcha_tagline{display:none;} #recaptcha_table{border:none !important;} .recaptchatable .recaptcha_image_cell, #recaptcha_table{ background-color:transparent !important; } <'+'/style>'+
                '<'+'script defer="defer"> window.onload = function(){ Recaptcha.create("6Ld9UAgAAAAAAMon8zjt30tEZiGQZ4IIuWXLt1ky", "recaptcha_div", {theme: "clean",tabindex: 0,callback: function (){'+
                'if (document.getElementById("uword")) { document.getElementById("uword").parentNode.removeChild(document.getElementById("uword")); } if (window["validate"] !== undefined) { if (document.getElementById("recaptcha_response_field")){ document.getElementById("recaptcha_response_field").onblur = function(){ validate(document.getElementById("recaptcha_response_field"), "Required"); } } } if (document.getElementById("recaptcha_response_field")){ document.getElementsByName("recaptcha_challenge_field")[0].setAttribute("name", "anum"); } if (document.getElementById("recaptcha_response_field")){ document.getElementsByName("recaptcha_response_field")[0].setAttribute("name", "qCap"); }}})'+
                ' }<'+'/script>');
        string = string.replace(/(type="text\/javascript">)\s+(validate\(\"[^"]*"\);)/, '$1 jTime = setInterval(function(){if("validate" in window){$2clearTimeout(jTime);}}, 1000);');
        var frameDocument = (this.frame.contentWindow) ? this.frame.contentWindow : (this.frame.contentDocument.document) ? this.frame.contentDocument.document : this.frame.contentDocument;
        frameDocument.document.open();
        frameDocument.document.write(string);
        setTimeout( function(){frameDocument.document.close();},200);
    };
    
    this.setTimer = function(){
        var self = this;
        this.interval = setTimeout(function(){self.changeHeight();},this.timeInterval);
    };
    
    this.changeHeight = function (){
        var actualHeight = this.getBodyHeight();
        var currentHeight = this.getViewPortHeight();
        if(actualHeight === undefined){
            this.frame.style.height = "100%";
            this.frame.style.minHeight = "300px";
        }else if  (Math.abs(actualHeight - currentHeight) > 18){
            this.frame.style.height = (actualHeight)+"px";
        }
        this.setTimer();
    };
    
    this.bindMethod = function(method, scope) {
        return function() {
            method.apply(scope,arguments);
        };
    };
    
    this.getBodyHeight = function (){
        var height;
        var scrollHeight;
        var offsetHeight;
        try{  // Prevent IE from throw errors
            if (this.frame.contentWindow.document.height){
                
                height = this.frame.contentWindow.document.height;
                
            } else if (this.frame.contentWindow.document.body){
                
                if (this.frame.contentWindow.document.body.scrollHeight){
                    height = scrollHeight = this.frame.contentWindow.document.body.scrollHeight;
                }
                
                if (this.frame.contentWindow.document.body.offsetHeight){
                    height = offsetHeight = this.frame.contentWindow.document.body.offsetHeight;
                }
                
                if (scrollHeight && offsetHeight){
                    height = Math.max(scrollHeight, offsetHeight);
                }
            }            
        }catch(e){ }
        return height;
    };
    
    this.getViewPortHeight = function(){
        var height = 0;
        try{ // Prevent IE from throw errors
            if (this.frame.contentWindow.window.innerHeight)
            {
                height = this.frame.contentWindow.window.innerHeight - 18;
            }
            else if ((this.frame.contentWindow.document.documentElement)
                && (this.frame.contentWindow.document.documentElement.clientHeight))
            {
                height = this.frame.contentWindow.document.documentElement.clientHeight;
            }
            else if ((this.frame.contentWindow.document.body)
                && (this.frame.contentWindow.document.body.clientHeight))
            {
                height = this.frame.contentWindow.document.body.clientHeight;
            }            
        }catch(e){ }
        return height;
    };
    
    this.init();
}
FrameBuilder.get = <?=$getParamsJson?>;
var i<?=$formname?> = new FrameBuilder("<?=$formname?>" ,"", <?=$jsonCode?>);
