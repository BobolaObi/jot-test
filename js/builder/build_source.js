var BuildSource = {
    type: null,
    config: null,
    isSecure: false,
    pagecode: null,
    qscript: null,
    baseURL: null,
    options: null,
    config: null,
    standAlone: !('$' in window),
    HTTP_URL: null,
    SSL_URL:  null,
    username: null,
    debug: false,
    formID: null,
    VERSION: '3.1.110',
    checkURL: 'http://www.jotform.com/',
    init: function(config){
        this.config = config;
        this.formID = config.form_id;
        if(!this.standAlone){
            this.config.form_height = $('stage').getHeight();
            this.HTTP_URL = Utils.HTTP_URL;
            this.SSL_URL = Utils.SSL_URL;
            this.username = Utils.user.username;
            this.debug = document.DEBUG;
            this.debugOptions = document.debugOptions || {};
            this.isSecure = Utils.isSecure;
            this.gzip = true;
        }else{
            this.HTTP_URL = V8Config.HTTP_URL;
            this.SSL_URL = V8Config.SSL_URL;
            this.debug = DEBUGMODE;
            this.debugOptions = {decompressForm:true};
            this.isSecure = /^\bhttps\b\:\/\//.test(V8Config.HTTP_URL);
            this.gzip = ("GZIP" in V8Config)? V8Config.GZIP : true;
        }
    },
    
    toJSON: function(obj){
        
        if(this.standAlone){
            return JSON.stringify(obj);
        }
        
        /*if(Object.isArray(obj)){
            return Object.toJSON(obj);
        }*/
        
        return Object.toJSON(obj);
    },
    /**
     * removes escape slashes
     * @param {Object} str
     */
    stripslashes: function(str) {
    
        return (str+'').replace(/\\(.?)/g, function (s, n1) {
            switch (n1) {
                case '\\':
                    return '\\';
                case '0':
                    return '\u0000';
                case '':
                    return '';
                default:
                    return n1;
            }
        });
    },
    fbcode: false,
    getFaceBookCode: function(){
        if(this.standAlone){
            return "";
        }else{
            if(this.fbcode){
                return this.fbcode;
            }
            var code = "";
            Utils.Request({
                server: 'js/widgets/facebook.js',
                method:'get',
                asynchronous:false,
                evalJSON: false,
                onComplete: function(t){
                    code = t.message;
                }
            });
            this.fbcode = code;
            return code;
        }
    },
    
    /**
     * Removes the MS word formatting
     * @param {Object} str
     */
    cleanWordFormat: function (str){
        // get rid of unnecessary tag spans (comments and title)
        str = str.replace(/\<\!--(\w|\W)+?--\>/gim, '');
        str = str.replace(/\<title\>(\w|\W)+?\<\/title\>/gim, '');
        
        // Get rid of classes and styles
        str = str.replace(/\s?class=\w+/gim, '');
        // Because of below line, HTML editor was not able to make text bold; style was removed.
        // str = str.replace(/\s+style=\'[^\']+\'/gim, '');
        
        // Get rid of unnecessary tags
        str = str.replace(/<(meta|link|\/?o:|\/?style|\/?st\d|\/?head|\/?html|body|\/?body|!\[)[^>]*?>/gim, '');
        
        // Get rid of empty paragraph tags
        str = str.replace(/(<[^>]+>)+&nbsp;(<\/\w+>)/gim, '');
        
        // remove bizarre v: element attached to <img> tag
        str = str.replace(/\s+v:\w+=""[^""]+""/gim, '');
        
        // remove extra lines
        str = str.replace(/"(\n\r){2,}/gim, '');
        
        // Fix entites
        str = str.replace("&ldquo;", "\"");
        str = str.replace("&rdquo;", "\"");
        str = str.replace("&mdash;", "–");
        
        // Removes XML stuff. ex. <?xml:namespace prefix=o ns="urn:schemas-microsoft-com:office:office" />
        str = str.replace(/<\?xml.* \/>/gim, '');
        
        return str;
    },
    /**
     * 
     * @param {Object} str
     */
    capitalize: function(str){
        return str.charAt(0).toUpperCase() + str.substring(1).toLowerCase();
    },
    /**
     * Removes unneceserry marks and gets the plain number
     * @param {Object} number
     */
    fixNumbers: function(number){
        if(typeof number == "string"){
            return number.replace(/\D/gim, '');
        }
        return number;
    },
    
    fixUTF: function(str){
        var lowerCase={"a":"00E1:0103:01CE:00E2:00E4:0227:1EA1:0201:00E0:1EA3:0203:0101:0105:1D8F:1E9A:00E5:1E01:2C65:00E3:0251:1D90","b":"1E03:1E05:0253:1E07:1D6C:1D80:0180:0183","c":"0107:010D:00E7:0109:0255:010B:0188:023C","d":"010F:1E11:1E13:0221:1E0B:1E0D:0257:1E0F:1D6D:1D81:0111:0256:018C","e":"00E9:0115:011B:0229:00EA:1E19:00EB:0117:1EB9:0205:00E8:1EBB:0207:0113:2C78:0119:1D92:0247:1EBD:1E1B","f":"1E1F:0192:1D6E:1D82","g":"01F5:011F:01E7:0123:011D:0121:0260:1E21:1D83:01E5","h":"1E2B:021F:1E29:0125:2C68:1E27:1E23:1E25:0266:1E96:0127","i":"0131:00ED:012D:01D0:00EE:00EF:1ECB:0209:00EC:1EC9:020B:012B:012F:1D96:0268:0129:1E2D","j":"01F0:0135:029D:0249","k":"1E31:01E9:0137:2C6A:A743:1E33:0199:1E35:1D84:A741","l":"013A:019A:026C:013E:013C:1E3D:0234:1E37:2C61:A749:1E3B:0140:026B:1D85:026D:0142:0269:1D7C","m":"1E3F:1E41:1E43:0271:1D6F:1D86","n":"0144:0148:0146:1E4B:0235:1E45:1E47:01F9:0272:1E49:019E:1D70:1D87:0273:00F1","o":"00F3:014F:01D2:00F4:00F6:022F:1ECD:0151:020D:00F2:1ECF:01A1:020F:A74B:A74D:2C7A:014D:01EB:00F8:00F5","p":"1E55:1E57:A753:01A5:1D71:1D88:A755:1D7D:A751","q":"A759:02A0:024B:A757","r":"0155:0159:0157:1E59:1E5B:0211:027E:0213:1E5F:027C:1D72:1D89:024D:027D","s":"015B:0161:015F:015D:0219:1E61:1E63:0282:1D74:1D8A:023F","t":"0165:0163:1E71:021B:0236:1E97:2C66:1E6B:1E6D:01AD:1E6F:1D75:01AB:0288:0167","u":"00FA:016D:01D4:00FB:1E77:00FC:1E73:1EE5:0171:0215:00F9:1EE7:01B0:0217:016B:0173:1D99:016F:0169:1E75:1D1C:1D7E","v":"2C74:A75F:1E7F:028B:1D8C:2C71:1E7D","w":"1E83:0175:1E85:1E87:1E89:1E81:2C73:1E98","x":"1E8D:1E8B:1D8D","y":"00FD:0177:00FF:1E8F:1EF5:1EF3:01B4:1EF7:1EFF:0233:1E99:024F:1EF9","z":"017A:017E:1E91:0291:2C6C:017C:1E93:0225:1E95:1D76:1D8E:0290:01B6:0240","ae":"00E6:01FD:01E3","dz":"01F3:01C6","3":"0292:01EF:0293:1D9A:01BA:01B7:01EE"};
        var upperCase={"A":"00C1:0102:01CD:00C2:00C4:0226:1EA0:0200:00C0:1EA2:0202:0100:0104:00C5:1E00:023A:00C3","B":"1E02:1E04:0181:1E06:0243:0182","C":"0106:010C:00C7:0108:010A:0187:023B","D":"010E:1E10:1E12:1E0A:1E0C:018A:1E0E:0110:018B","E":"00C9:0114:011A:0228:00CA:1E18:00CB:0116:1EB8:0204:00C8:1EBA:0206:0112:0118:0246:1EBC:1E1A","F":"1E1E:0191","G":"01F4:011E:01E6:0122:011C:0120:0193:1E20:01E4:0262:029B","H":"1E2A:021E:1E28:0124:2C67:1E26:1E22:1E24:0126","I":"00CD:012C:01CF:00CE:00CF:0130:1ECA:0208:00CC:1EC8:020A:012A:012E:0197:0128:1E2C:026A:1D7B","J":"0134:0248","K":"1E30:01E8:0136:2C69:A742:1E32:0198:1E34:A740","L":"0139:023D:013D:013B:1E3C:1E36:2C60:A748:1E3A:013F:2C62:0141:029F:1D0C","M":"1E3E:1E40:1E42:2C6E","N":"0143:0147:0145:1E4A:1E44:1E46:01F8:019D:1E48:0220:00D1","O":"00D3:014E:01D1:00D4:00D6:022E:1ECC:0150:020C:00D2:1ECE:01A0:020E:A74A:A74C:014C:019F:01EA:00D8:00D5","P":"1E54:1E56:A752:01A4:A754:2C63:A750","Q":"A758:A756","R":"0154:0158:0156:1E58:1E5A:0210:0212:1E5E:024C:2C64","S":"015A:0160:015E:015C:0218:1E60:1E62","T":"0164:0162:1E70:021A:023E:1E6A:1E6C:01AC:1E6E:01AE:0166","U":"00DA:016C:01D3:00DB:1E76:00DC:1E72:1EE4:0170:0214:00D9:1EE6:01AF:0216:016A:0172:016E:0168:1E74","V":"A75E:1E7E:01B2:1E7C","W":"1E82:0174:1E84:1E86:1E88:1E80:2C72","X":"1E8C:1E8A","Y":"00DD:0176:0178:1E8E:1EF4:1EF2:01B3:1EF6:1EFE:0232:024E:1EF8","Z":"0179:017D:1E90:2C6B:017B:1E92:0224:1E94:01B5","AE":"00C6:01FC:01E2","DZ":"01F1:01C4"};
        str = str.toString();
        
        for(var lk in lowerCase){
            var lvalue = '\\u'+lowerCase[lk].split(':').join('|\\u');
            str = str.replace(new RegExp(lvalue, 'gm'), lk);
        }
        
        for(var uk in upperCase){
            var uvalue = '\\u'+upperCase[uk].split(':').join('|\\u');
            str = str.replace(new RegExp(uvalue, 'gm'), uk);
        }
        
        return str;
    },
    
    htmlDecode: function(string, quote_style) {
           
        var optTemp = 0, i = 0, noquotes= false;
        if (typeof quote_style === 'undefined') {
            quote_style = 2;
        }
        string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        var OPTS = {
            'ENT_NOQUOTES': 0,
            'ENT_HTML_QUOTE_SINGLE' : 1,
            'ENT_HTML_QUOTE_DOUBLE' : 2,
            'ENT_COMPAT': 2,
            'ENT_QUOTES': 3,
            'ENT_IGNORE' : 4
        };
        if (quote_style === 0) {
            noquotes = true;
        }                         
        if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
            quote_style = [].concat(quote_style);
            for (i=0; i < quote_style.length; i++) {
                // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
                if (OPTS[quote_style[i]] === 0) {
                    noquotes = true;
                }
                else if (OPTS[quote_style[i]]) {
                    optTemp = optTemp | OPTS[quote_style[i]];
                }
            }
            quote_style = optTemp;
        }
        if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
            string = string.replace(/&#0*39;/g, "'"); // PHP doesn't currently escape if more than one 0, but it should
            // string = string.replace(/&apos;|&#x0*27;/g, "'"); // This would also be useful here, but not a part of PHP
        }
        if (!noquotes) {
            string = string.replace(/&quot;/g, '"');
        }
        // Put this in last place to avoid escape being double-decoded
        string = string.replace(/&amp;/g, '&');
    
        return string;
        
    },
    /**
     * PHP's number format function
     * @param {Object} number
     * @param {Object} decimals
     * @param {Object} dec_point
     * @param {Object} thousands_sep
     */
    numberFormat: function(number, decimals, dec_point, thousands_sep){
        var n = number, prec = decimals;
        var toFixedFix = function(n, prec){
            var k = Math.pow(10, prec);
            return (Math.round(n * k) / k).toString();
        };
        n = !isFinite(+n) ? 0 : +n;
        prec = !isFinite(+prec) ? 0 : Math.abs(prec);
        var sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
        var dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
        var s = (prec > 0) ? toFixedFix(n, prec) : toFixedFix(Math.round(n), prec);
        var abs = toFixedFix(Math.abs(n), prec);
        var _, i;
        if (abs >= 1000) {
            _ = abs.split(/\D/);
            i = _[0].length % 3 || 3;
            _[0] = s.slice(0, i + (n < 0)) + _[0].slice(i).replace(/(\d{3})/g, sep + '$1');
            s = _.join(dec);
        } else {
            s = s.replace('.', dec);
        }
        
        if (s.indexOf(dec) === -1 && prec > 1) {
            s += dec + new Array(prec).join(0) + '0';
        } else if (s.indexOf(dec) == s.length - 2) { // incorrect: 2.7, correct: 2.70
            s += '0';
        }
        return s;
    },
    /**
     * Formats the prices and currencies
     * @param {Object} amount amount of the money
     * @param {Object} curr Currency of the money
     * @param {Object} id specific id of the span wrapper of money, so that you can change it dynamically
     */
    formatPrice: function (amount, curr, id, nofree){
        if(!curr){ curr = 'USD'; }
        id = id || "";
        if(parseFloat(amount) == 0 && nofree !== true){ return 'Free'; }
        amount = this.numberFormat( amount, 2, '.' , ','); // Format the number for money currency
        switch(curr){
            case "USD": return "$<span id=\""+id+"\">"+amount + '</span> USD';
            case "EUR": return "&euro;<span id=\""+id+"\">"+amount + '</span> EUR';
            case "GBP": return "&pound;<span id=\""+id+"\">"+amount + '</span> GBP';
            case "BRL": return "R$ <span id=\""+id+"\">"+amount + '</span>';
            case "AUD": return "$<span id=\""+id+"\">"+amount+"</span> "+curr;
            case "CAD": return "$<span id=\""+id+"\">"+amount+"</span> "+curr;
            case "NZD": return "$<span id=\""+id+"\">"+amount+"</span> "+curr;
            case "SGD": return "$<span id=\""+id+"\">"+amount+"</span> "+curr;
            case "HKD": return "$<span id=\""+id+"\">"+amount+"</span> "+curr;
            default:    return "<span id=\""+id+"\">"+amount+"</span> "+curr;
        }
    },
    addZeros: function(n, totalDigits){
        n = n.toString();
        var pd = '';
        if (totalDigits > n.length) {
            for (var i = 0; i < (totalDigits - n.length); i++) {
                pd += '0';
            }
        }
        return pd + n.toString();
    },
    isArray: function(obj){
        return Object.prototype.toString.call(obj) == '[object Array]';
    },
    deepClone: function(obj){
        if (typeof obj !== 'object' || obj === null) {
            return obj;
        }
        var clone = this.isArray(obj)? [] : {};
        
        for (var i in obj) {
            var node = obj[i];
            if (typeof node == 'object') {
                if (this.isArray(node)) {
                    clone[i] = [];
                    for (var j = 0; j < node.length; j++) {
                        if (typeof node[j] != 'object') {
                            clone[i].push(node[j]);
                        } else {
                            clone[i].push(this.deepClone(node[j]));
                        }
                    }
                } else {
                    clone[i] = this.deepClone(node);
                }
            } else {
                clone[i] = node;
            }
        }
        return clone;
    },
    convertSavedToProp: function (arr){
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
                if (!('formProps' in window) || !formProps) { // if fromProp is empty
                    formProps = this.deepClone(default_properties.form);
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
                    prop[key] = this.deepClone(default_properties[type]);
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
        return prop;
    },
    /**
     * Makes formatted text for products. Just like paypal
     * @param {Object} name
     * @param {Object} price
     * @param {Object} curr
     * @param {Object} duration
     * @param {Object} setupfee
     * @param {Object} trial
     */
    makeProductText: function (name, price, curr, duration, setupfee, trial){
        var text      = ''; 
        var fprice    = '<b>' + this.formatPrice(price || 0, curr) + '</b>'; // Get formatted price
        var fsetupfee = '<b>' + this.formatPrice(Number(setupfee) || 0, curr) + '</b>'; // Get calculated and formatted setupfee
        var setuptext = ""; // setupfee text will be here
        var trialText = setupfee > 0? fsetupfee : "Free";
        
        if(duration && trial && trial != 'None' && trial != 'Enabled'){ // If trial period is set
            
            if(trial == 'One Day'){ // Special text for 1 day trial
                text += trialText + " for the first day then, ";
            }else{
                text += trialText + ' for the first <u>' + ( trial.toLowerCase() ) + '</u> then, ';
            }
        }
        
        if(trial == 'Enabled'){
            fsetupfee = 'Free';
        }
        
        setuptext = fsetupfee+" for the <u>first payment</u> then, ";
        
        if(duration){ // If recurring duration is set
            switch(duration){
                case "Daily":
                    setuptext = fsetupfee+" for the <u>first day</u> then, ";
                    text += fprice + " for each <u>day</u>.";
                break;
                case "Weekly":
                    setuptext = fsetupfee+" for the <u>first week</u> then, ";
                    text += fprice + " for each <u>week</u>.";
                break;
                case "Bi-Weekly":
                    text += fprice + " for each <u>two weeks</u>.";
                break;
                case "Monthly":
                    setuptext = fsetupfee+" for the <u>first month</u> then, ";
                    text += fprice + " for each <u>month</u>.";
                break;
                case "Bi-Monthly":
                    text += fprice + " for each <u>two months</u>.";
                break;
                case "Quarterly":
                    text += fprice + " for each <u>three months</u>.";
                break;
                case "Semi-Yearly":
                    text += fprice + " for each <u>six months</u>.";
                break;
                case "Yearly":
                    setuptext = fsetupfee+" for the <u>first year</u> then, ";
                    text += fprice + " for each <u>year</u>.";
                break;
                case "Bi-Yearly":
                    text += fprice + " for each <u>two years</u>.";
                break;
                default: // Default for monthly
                    setuptext = fsetupfee+" for the <u>first month</u> then, ";
                    text += fprice + " for each <u>month</u>.";
            }
            
            if((!trial || trial == 'None') && setupfee > 0 || trial == "Enabled"){ // Trial overwrites the setupfee
                text = setuptext + text; // Add setupfee text if no trial was set
            }
            
            text = '('+text+')'; // Put long text in parenthesis
        }else{
            text += fprice; // Default price.
        }
        
        // Finish the sentence here
        // Place product name to first then, wrap details in span for easier styling
        return (name || '') + ' <span class="form-product-details">' + text + "</span>";
    },
    
    getProperty: function(prop, id){
        id = id || 'form';
        return this.config[id+'_'+prop] || false;
    },
    
    hasUpload: function(){
        for(var key in this.config){
            var value = this.config[key];
            if(key.match(/_type/g) && value == 'control_fileupload'){
                return true;
            }
        }
        return false;
    },
    
    getCode: function (options){
        // get those parameters as one object later.
        BuildSource.options = {
			type: 'jsembed',
			isSSL: false
		};

        for (attrname in options) { BuildSource.options[attrname] = options[attrname]; }

        BuildSource.qscript = "";
        BuildSource.baseURL = BuildSource.options.isSSL ? this.SSL_URL : this.HTTP_URL;
        // read the type and return the code.
        switch(BuildSource.options.type){
            case "iframe":
            case "blogger":
			case "facebook":
                return BuildSource.createIFrame();
            case "url":
                return BuildSource.createURL();
            case "secureUrl":
                return BuildSource.createSecureURL();
            case "shortUrl":
            case "twitter":
                return BuildSource.createShortURL();
            case "customUrl":
                return BuildSource.createCustomURL();
            case "jsembed":
            case "default":
            case "wordpress":
            case "typePad":
            case "typepad":
            case "liveJournal":
            case "livejournal":
            case "vox":
            case "tumblr":
            case "yola":
            case "webs":
            case "geocities":
            case "drupal":
            case "joomla":
            case "joomla2":
            case "memberkit":
            case "designerPro":
            case "xara":
            case "webDesigner":
                return BuildSource.createJSEmbed();
            case "css":
            case "source":
            case "dreamweaver":
            case "dreamWeaver":
            case "frontPage":
            case "frontpage":
            case "iweb":
            case "iWeb":
            case "expression":
            case "expressionWeb":
                return BuildSource.createFullCode();
            /*case "facebook":
                return BuildSource.createFacebookCode(true);*/
            case  "pdfCode":
                return BuildSource.createPdfCode();
            case "zip":
                // change the base url
                BuildSource.baseURL = "";
                return BuildSource.createZipURL();
            case "lightbox":
                return BuildSource.createLightBoxCode();
            case "lightbox2":
            	return BuildSource.createLightBoxCode(true);
            case "popupBox":
            case "popup":
                return BuildSource.createPopupBoxCode();
            case "googleSites":
            case "googlesites":
                return "http://hosting.gmodules.com/ig/gadgets/file/102235888454881850738/jotform.xml";
            case "feedbackBox":
                return BuildSource.createFeedbackCode();
            case "feedback2":
                return BuildSource.createFeedbackCode(true);
            case "email":
            	return "Hi,<br/>Please click on the link below to complete this form.<br/><a href=\""+BuildSource.baseURL+"form/"+form.getProperty('id')+"\">"+BuildSource.baseURL+"form/"+form.getProperty('id')+"</a><br/><br/>Thank you!";
            default:
                return "Not Implemented yet";
        }
        
    },
    createFeedbackCode: function (isNew){
    	if (typeof isNew !== "undefined"){
            return "<script src=\"" + BuildSource.baseURL + "min/g=feedback\" type=\"text/javascript\">\n" +
            	"new JotformFeedback({\n" +
                "formId: \"" + this.getProperty("id") + "\",\n" +
                "buttonText: \"" + this.getProperty("feedbackButtonText") + "\",\n" +
                "base: \"" + BuildSource.baseURL + "\",\n" +
				"background:'" + this.getProperty("feedbackBackgroundColor") + "',\n" +
				"fontColor:'" + this.getProperty("feedbackFontColor") + "',\n" +
                "buttonSide: \"" + this.getProperty("feedbackButtonSide") + "\",\n" +
                "buttonAlign: \"" + this.getProperty("feedbackButtonAlign") + "\",\n" +
				"type:" + this.getProperty("feedbackStyle") + ",\n" +
                "width: " + this.getProperty("feedbackWidth") + "," + 
                "height: " + this.getProperty("feedbackHeight") +
            "});\n</script>";
    	}else{
            return "<script src=\"" + BuildSource.baseURL + 
            "min/g=orangebox\" type=\"text/javascript\"></script>\n" + "<div id=\"feedback-tab\" style=\"display:none\">" +
            "<button boxwidth=\"100\" class=\"orangebox\" id=\"feedback-tab-link\" formID=\"" + 
            this.getProperty('id') + "\" base=\"" + this.HTTP_URL + "\" height=\"500\" width=\"700\" title=\""+ 
            this.getProperty('feedbackButtonLabel') +"\">"+ this.getProperty('feedbackButtonLabel') +
            "</button></div>";
    	}
    },
    createPopupBoxCode: function (){
        return "<a href=\"javascript:void( window.open('"+BuildSource.baseURL+"form/"+this.getProperty('id')+"', 'blank','scrollbars=yes,toolbar=no,width=700,height=500'))\">"+this.getProperty('title')+"</a>";
    },
    createLightBoxCode: function (isNew){
    	var code = "";
    	if (typeof isNew !== "undefined"){
    		code = "<"+"script src=\"" + BuildSource.baseURL.sanitize() + "min/g=feedback\" type=\"text/javascript\">\n" +
            		"new JotformFeedback({\n" +
            		"formId:'" + this.getProperty('id') + "',\n" +
        			"base:'" + this.HTTP_URL.sanitize() + "',\n" +
    				"windowTitle:'" + this.getProperty("lightboxTitle").sanitize() + "',\n" +
    				"background:'" + this.getProperty("lightboxBackgroundColor") + "',\n" +
    				"fontColor:'" + this.getProperty("lightboxFontColor") + "',\n" +
					"type:" + this.getProperty("lightboxStyle") + ",\n" +
					"height:" + this.getProperty("lightboxHeight") + ",\n" +
        			"width:" + this.getProperty("lightboxWidth") + "\n" +
        			"});\n" +
            		"<"+"/script>\n" +
            		"<a id=\"lightbox-" + this.getProperty('id') + "\" style=\"cursor:pointer;color:blue;text-decoration:underline;\">" +
            		this.getProperty('title') +
            		"</a>";
    	}else{
            code = "<"+"script src=\"" + BuildSource.baseURL + "min/g=orangebox\" type=\"text/javascript\"><"+"/script>\n" +
            		"<a class=\"orangebox\" formID=\"" + this.getProperty('id') + "\" base=\"" + this.HTTP_URL + "\" height=\"500\" " +
            		"width=\"700\" title=\"" + this.getProperty('title') + "\" style=\"color:blue;text-decoration:underline;\">" +
            		this.getProperty('title') + "</a>";
    	}
    	return code;
    },
    createZipURL: function (){
        var $this = this;
        Utils.Request({
            parameters:{
                action:'getFormSourceZip',
                id: $this.getProperty('id'),
                source: BuildSource.createFullCode('zip')
            },
            onSuccess:function(res){
                location.href = res.zipURL;
            },
            onFail: function(res){
                Utils.alert("Cannot create zip file.", "Error");                
            }
            
        });
    },
    createIFrame: function (){
        var url = this.baseURL == this.checkURL? 'http://form.jotform.com/' : this.baseURL;
        return '<'+'iframe allowtransparency="true" src="'+url+'form/'+this.getProperty('id')+'" frameborder="0" style="width:100%; height:'+this.getProperty('height')+'px; border:none;" scrolling="no">\n'+
        '<'+'/iframe>';
    },
    createURL : function (){
        var url = this.baseURL == this.checkURL? 'http://form.jotform.com/' : this.baseURL;
        return url+'form/'+this.getProperty('id');
    },
    createSecureURL : function (){
        return this.SSL_URL+'form/'+this.getProperty('id');
    },
    createShortURL : function (){
        if(this.getProperty('hash')){
            return "http://jotfor.ms/"+this.getProperty('hash');
        }
        return "";
    },
    createCustomURL : function (){
        var slugName = "Title_Me";
        if (this.getProperty('slug') && this.getProperty('slug') != this.getProperty('id')){
            slugName = this.getProperty('slug');
        }
        return (BuildSource.baseURL + this.username + "/" + slugName).replace(/\s+/gim, "_");
    },
    createJSEmbed: function(){
        var url = this.baseURL == this.checkURL? 'http://form.jotform.com/' : this.baseURL;
        return "<"+"script src=\""+url+"jsform/"+this.getProperty('id')+"\"><"+"/script>";
    },
    createFullCode: function createCSS(mode){
        
        this.isZip = mode == "zip";
        
        var source = this.createHTMLCode();
        
        var mobile  = '<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />\n';
            mobile += '<meta name="HandheldFriendly" content="true" />\n';
                
        var style  = this.createCSSCode();
        var script = this.createJSCode();
        
        var page = "";
        page += '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">\n';
        page += '<html><head>\n';
        page += '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n';
        page += mobile;
        // We have changed this because this was not used in the old version and
        // People used the titles in a different way so we cannot use them in page titles
        // page += '<title>'+this.getProperty('title')+'</title>\n';
        
        page += '<title>Form</title>\n';
        
        page += style;
        page += script;
        page += '</head>\n';
        page += '<body>\n';
        page += source;
        
        page +='</body>\n';
        page +='</html>';
        
        
        if(this.isZip || this.options.pagecode){
            this.isZip = false;
            return page;
        }
        this.isZip = false;
        source = script + style /*+ "<!-- Move above lines to <head> and below lines to <body> section -->\n\n"*/ + source; // Join all
        return source;
    },
    
    createFacebookCode: function() {
        var style  = this.createCSSCode(true);
        var source = this.createHTMLCode(true);
        var fullSource = style + source;
        return fullSource;
    }, 
    
    createPdfCode: function() {
        var page = '';
        page += '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">\n';
        page += '<html><head>\n';
        page += '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n';
        page += '<title>Form</title>\n';
        page += '<body>\n';
        page += BuildSource.createFacebookCode();
        page += '</body>\n</html>';
        return page;
    },
    
    /**
     * Create script here
     */
    createJSCode: function (){
        var script ="";
        var debug = "";
        if(this.isZip || (this.debug && this.debugOptions.decompressForm)){
            script = '<'+'script src="'+BuildSource.baseURL+'js/prototype.js?v='+this.VERSION+'" type="text/javascript"><'+'/script>\n'+
            '<'+'script src="'+BuildSource.baseURL+'js/protoplus.js?v='+this.VERSION+'" type="text/javascript"><'+'/script>\n'+
            '<'+'script src="'+BuildSource.baseURL+'js/protoplus-ui.js?v='+this.VERSION+'" type="text/javascript"><'+'/script>\n'+
            '<'+'script src="'+BuildSource.baseURL+'js/jotform.js?v='+this.VERSION+'" type="text/javascript"><'+'/script>\n'+
            '<'+'script src="'+BuildSource.baseURL+'js/calendarview.js?v='+this.VERSION+'" type="text/javascript"><'+'/script>\n';
            if(this.debug){
                debug = '\n   JotForm.debug = true;';
            }
        }else{
            
            var ext = this.gzip? "jgz" : "js";
            
        	if (BuildSource.options.isSSL || this.isSecure){
                script = '<'+'script src="https://d3mc0rm5ezl95j.cloudfront.net/jotform.'+ext+'?'+this.VERSION+'" type="text/javascript"><'+'/script>\n';
        	}else{
                script = '<'+'script src="http://cdn.jotfor.ms/jotform.'+ext+'?'+this.VERSION+'" type="text/javascript"><'+'/script>\n';
        	}
        }
        
        script += '<'+'script type="text/javascript">';
        
        if(this.getProperty('highlightLine') && this.getProperty('highlightLine') == 'Disabled'){
        	BuildSource.qscript += '      JotForm.highlightInputs = false;\n';
        }
        
        if(BuildSource.options.JSFORM){
            // Stupid PHP code
            script += '\n var jsTime = setInterval(function(){try{'+
            '\n   JotForm.jsForm = true;\n';
        }
        
        if(this.getProperty('conditions')){
            var conds = this.deepClone(this.getProperty('conditions'));
            var newConds = [];
            for(var c=0; c < conds.length; c++){
                if(conds[c].type == 'page' || conds[c].type == 'field'){
                    newConds.push(conds[c]);
                }
            }
            if(newConds.length > 0){
                script += '\n   JotForm.setConditions('+this.toJSON(newConds)+');';
            }
        }
        
        if(this.getProperty('formStringsChanged') !== false){
            var strs = this.getProperty('formStrings')[0];
            strs = this.toJSON(strs);
            this.qscript += '      JotForm.alterTexts('+strs+');\n';
        }
        
        // Add debug parameter
        script += debug;
        
        if(BuildSource.qscript){
            script += '\n   JotForm.init(function(){\n'+
                 BuildSource.qscript+
            '   });\n';
        }else{
            script += '\n   JotForm.init();\n';
        }
        
        if(BuildSource.options.JSFORM){
            script += '\n   clearInterval(jsTime);\n }catch(e){}}, 1000);\n';
        }
        
        script += '<'+'/script>\n';
        
        return script;
    },
    
    /**
     * This is for replacing relative URLs with absolute ones for use on the share wizard. For full source code etc.
     */
    fixBackgroundURL: function(background){
        if(background){
            return background.replace(/url\("(\.\.\/)*images/, 'url("'+this.HTTP_URL+'images');
        }
        return background;
    },
    /**
     * Directly returns the first item of the hash
     * @param {Object} hash
     */
    hashGetFirst: function(hash){
        for(var x in hash){
            return hash[x];
        }
    },
    
    createCSSCode: function (forFacebook){
        
        var formCustoms = "";
        var style = "";
        var font = this.getProperty('font') ? this.getProperty('font') : "default" ;
        var family = (font.match(/\s/g))? '"' + font + '"' : font;
        var styles = this.getProperty('styles');
        var labelWidth = parseInt(this.getProperty('labelWidth'), 10);
        
        var fullURL = (this.isZip)? "" : this.baseURL;
        
        if (this.getProperty('background')){ formCustoms += '        background:'+this.fixBackgroundURL(this.getProperty('background'))+';\n'; }
        if (this.getProperty('fontcolor')) { formCustoms += '        color:'+this.getProperty('fontcolor')+' !important;\n';       }
        if (this.getProperty('font'))      { formCustoms += '        font-family:'+family+';\n';      }
        if (this.getProperty('fontsize'))  { formCustoms += '        font-size:'+parseInt(this.getProperty('fontsize'), 10)+'px;\n';  }
        
        if (forFacebook) {
            style += '<link type="text/css" rel="stylesheet" href="' + fullURL + 'formstyle/' + this.getProperty('id') + '.css?v=' + this.VERSION + '"/>\n';
        }
        
        
        if(this.isZip || (this.debug && this.debugOptions.decompressForm)){
            style += '<link type="text/css" rel="stylesheet" href="' + fullURL + 'css/styles/form.css?v' + this.VERSION + '"/>\n';
            style += '<link href="'+fullURL+'css/calendarview.css?v'+this.VERSION+'" rel="stylesheet" type="text/css" />\n';
        }else{
            var ext = this.gzip? "cssgz" : "css";
        	if (this.options.isSSL || this.isSecure){
        		style += '<link type="text/css" rel="stylesheet" href="https://d3mc0rm5ezl95j.cloudfront.net/jotform.'+ext+'?' + this.VERSION + '"/>\n';
        	}else{
                if(forFacebook){ //Facebook has problems with gz format css.
					style += '<link href="http://cdn.jotfor.ms/jotform.css'+'?v='+this.VERSION+'" rel="stylesheet" type="text/css" />\n';
				}else{
					style += '<link href="http://cdn.jotfor.ms/jotform.'+ext+'?'+this.VERSION+'" rel="stylesheet" type="text/css" />\n';	
				}				
        	}
        }
                
        if (styles && styles != 'form' ){
            style += '<link type="text/css" rel="stylesheet" href="'+fullURL+'css/styles/' + styles + '.css" />\n';
        }
        
        var paddingTop = 20;
        var prop = this.convertSavedToProp(this.config);
        if(this.hashGetFirst(prop) && this.hashGetFirst(prop).type.value == 'control_head'){
            paddingTop = 0;
        }
        
        if( !forFacebook ){
            style += '<style type="text/css">\n' +
            // Form Label class
            '    .form-label{\n' +
            '        width:' + labelWidth + 'px !important;\n' + // Default for align top    
            '    }\n' +
            
            // Form label left align
            '    .form-label-left{\n' +
            '        width:' + labelWidth + 'px !important;\n' + // Default for align top
            '    }\n';
            
            if (this.getProperty('lineSpacing')) {
                style +=  // Form Line Spacing
                '    .form-line{\n' +
                '        padding:' + parseInt(this.getProperty('lineSpacing'), 10) + 'px;\n' +
                '    }\n';
            }
            
            // Form label right align
            style += '    .form-label-right{\n' + 
            '        width:' + labelWidth + 'px !important;\n' + // Default for align top
            '    }\n';
            
            if (BuildSource.options.pagecode) {
                style += '    body, html{\n' +
                '        margin:0;\n' +
                '        padding:0;\n' +
                '        background:' + this.fixBackgroundURL(this.getProperty('background')) + ';\n' +
                '    }\n' +
                '\n';
            }
            
            // Form Wrapper class
            style += '    .form-all{\n';
			
            if (BuildSource.options.pagecode) {
                style += '        margin:0px auto;\n';
                style += '        padding-top:' + paddingTop + 'px;\n';
            }
            
            style += '        width:' + parseInt(this.getProperty('formWidth'), 10) + 'px;\n' + formCustoms + '    }\n';
           
			
            if (this.getProperty('injectCSS')) {
                style += '    /* Injected CSS Code */\n';
                style += this.getProperty('injectCSS');
                style += '\n    /* Injected CSS Code */\n';
            }
            
            style += '</style>\n\n';
        }
      
        return style;
    },
    
    needSecure: function(){
        return false;
    },
    
    createHTMLCode: function(forFacebook){
        var source = "";
        var hiddenFields = "";
        var multipart = "";
        
        if(this.hasUpload()){
            multipart = 'enctype="multipart/form-data"';
        }
        
        /*if(!BuildSource.options.config){
            config = getAllProperties();
        }*/
      
        var formID = this.getProperty('id') || "{formID}";
        
        if(forFacebook){
            source += '<script type="text/javascript">\n'+this.getFaceBookCode()+'\n</script>';
        }
        
        var onFacebookSubmit="";
        if(forFacebook){
            onFacebookSubmit=' onsubmit="return Facebook.checkForm();"';
        } 
        
        var submitURL = this.HTTP_URL;
        
        if(this.needSecure()){
            submitURL = this.SSL_URL;
        }
        
        source += '<form class="jotform-form"' + onFacebookSubmit + ' action="' + submitURL + 'submit.php" method="post" ' + multipart + ' name="form_' + formID + '" id="' + formID + '" accept-charset="utf-8">';
        
        source += '<input type="hidden" name="formID" value="' + formID + '" />';
        //source += '<input type="hidden" name="check_spm" id="check_spm" value="' + formID + '" />';
        
        var savedProp = this.convertSavedToProp(this.config);
        
        if(forFacebook === true){
            var reqhidden='';
            for (var key in savedProp) {
                var prop = savedProp[key];
                var id = "input_"+key.replace('id_', '');
                if(prop.required && prop.required.value == "Yes"){
                    var etype=prop.type.value.split("_")[1];
                    if(!prop.text.nolabel){ // there is label
                        if(reqhidden == ''){
                            reqhidden += id+'*'+etype+'*'+this.stripslashes(prop.text.value);                            
                        } else{
                            reqhidden += ','+id+'*'+etype+'*'+this.stripslashes(prop.text.value);                            
                        }
                    } else { //there isn't a label
                        if(reqhidden == ''){
                            reqhidden += id+'*'+etype+'*'+etype;                            
                        } else{
                            reqhidden += ','+id+'*'+etype+'*'+etype;                            
                        }
                    }
                }
            }
            source += '<input type="hidden" id="reqids" name="requireds" value="'+ reqhidden+'">';
        }
        
        source += '<div class="form-all" >';
        source += '<ul class="form-section">';
        for(var key in savedProp){
            var prop = savedProp[key];
            var id = key.replace('id_', '');
            var line_id = key;
            
            var input = this.createInputHTML(prop.type.value, id, prop, false, forFacebook);
            
            var html = input.html;
            var cname = 'form-input';
            var lcname = 'form-label';
            var tag_type = 'div';
            var hide = "";
            
            if (forFacebook != true && input.script) {
                BuildSource.qscript += input.script;
            }
            
            if(prop.type.value == "control_hidden" || prop.type.value == "control_autoincrement" || input.hidden === true){
                hiddenFields += html;
                continue;
            }
            
            if(prop.hasCondition && prop.hasCondition.value == "Yes"){
                hide = 'style="display:none;" ';
            }
            
            // Check if question is collapse of paging use different line wrapper
            if(prop.type.value == "control_collapse" || prop.type.value == "control_pagebreak" || prop.type.value == "control_head"){
                if(prop.type.value == "control_collapse"){
                    source += '</ul><ul class="form-section'+((prop.status && prop.status.value == 'Closed')? '-closed' : '')+'" id="section_'+id+'">';
                }
                tag_type = 'li';
            }else{
                var shrink = ((prop.shrink && prop.shrink.value == 'Yes')? ' form-line-column' : '');
                if(shrink && prop.newLine && prop.newLine.value == 'Yes'){
                    shrink += ' form-line-column-clear';
                }
                source += '<li class="form-line' + shrink + '" '+hide+'id="'+line_id+'" >'; // Default line wrapper
            }
            
            // Manage label alignments
            if(this.getProperty('alignment') == 'Top'){
                cname = 'form-input-wide';
                lcname = 'form-label-top';
            }else{
                var lalign = 'left';
                if(this.getProperty('alignment')){
                    lalign = this.getProperty('alignment');
                }
                cname = 'form-input';
                lcname = 'form-label-'+lalign.toLowerCase(); // form-label-left OR form-label-right
            }
            
            // Question property overwrites the form property
            if(prop.labelAlign && prop.labelAlign.value != 'Auto'){
                if(prop.labelAlign.value == 'Top'){
                    cname = 'form-input-wide';
                    lcname = 'form-label-top';
                }else{
                    cname = 'form-input';
                    lcname = 'form-label-'+prop.labelAlign.value.toLowerCase();  // form-label-left OR form-label-right
                }
            }

            cname = (prop.text.nolabel)? 'form-input-wide' : cname; // If nolabel is set then remove all settings
            
            var requiredStar = "";
            if(prop.required && prop.required.value == "Yes"){
                requiredStar ='<span class="form-required">*</span>';
                prop.text.value += requiredStar;
            }
            
            if(!prop.text.nolabel){ // Create label
                source += '<label class="'+lcname+'" id="label_'+id+'" for="input_'+id+'"> '+this.stripslashes(prop.text.value)+' </label>';
            }
            
            // Input wrapper
            source += '<' + tag_type + ' id="cid_' + id + '" class="' + cname + '"> '+html+' </' +tag_type+ '>';
            
            // If line wrapper was created, close the tag
            if(/* not */ !(prop.type.value == "control_collapse" || prop.type.value == "control_pagebreak" || prop.type.value == "control_head")){
                source += '</li>';
            }else{
                if(prop.type.value == "control_pagebreak"){
                    source += '</ul><ul class="form-section">';
                }
            }
            
            // Special Scripts (These may change later, There must be a better way)
            if(prop.hint && prop.hint.value && (prop.hint.value != " ")){
                BuildSource.qscript += "      $('input_"+prop.qid.value+"').hint('" + prop.hint.value.sanitize() + "');\n";
            }
            if(prop.description && prop.description.value){
                BuildSource.qscript += "      JotForm.description('input_"+prop.qid.value+"', '" + (this.stripslashes(prop.description.value).sanitize().nl2br()) + "');\n";
            }
        }
        
        // This is for spam cheks DO NOT DELETE
        source += '<li style="display:none">Should be Empty: <input type="text" name="website" value="" /></li>';
        // Close the form source
        source += '</ul></div>';
        if (forFacebook != true) {
            source += '<input type="hidden" id="simple_spc" name="simple_spc" value="'+formID+'"/>';
            source += '<script type="text/javascript">document.getElementById("si"+"mple"+"_spc").value = "'+formID+'-'+formID+'";</script>';
        }
        else{
            source += '<input type="hidden" id="simple_spc" name="simple_spc" value="'+formID+'"/>';
            source += '<script>document.getElementById("si"+"mple"+"_spc").setValue("'+formID+'-'+formID+'");</script>';
        }
        source += hiddenFields;
        source += "</form>";    
        
        /**
         * Format source and join all pieces together
         */
        source = BuildSource.styleHTML(source, 4, ' ', 1000);
        source = source.replace(/<textarea(.*?)>\n?\s+(.*?)\s+\n?<\/textarea>/gim , '<textarea$1>$2</textarea>');    
        source = source.replace(/(<(option|label).*?\>)\n?\s+(.*?)\s+\n?(<\/\2\>)/gim, '$1 $3 $4');
        source = source.replace(/\s*\<span(.*?)\>\s*/gim, '<span$1>');
        source = source.replace(/\s*\<\/span\>/gim, '</span>');
        
        return source;
    },
    /**
     * Adds the validation marks to the class name
     * @param {Object} name
     * @param {Object} prop
     */
    addValidation: function (name, prop, additional){
        var val = [];
        if(prop.required && prop.required.value == "Yes"){
            val.push("required");
        }
        if(prop.validation && prop.validation.value != "None"){
            val.push(prop.validation.value);
        }
        
        if(additional){
            val.push(additional);
        }
        
        if(val.length > 0){
            name += " validate["+val.join(", ")+"]";
        }
        
        return name;
    },

    jsBeautify : function (js_source_text,options){var input,output,token_text,last_type,last_text,last_word,current_mode,modes,indent_string;var whitespace,wordchar,punct,parser_pos,line_starters,in_case;var prefix,token_type,do_block_just_closed,var_line,var_line_tainted,if_line_flag;var indent_level;options=options||{};var opt_indent_size=options.indent_size||4;var opt_indent_char=options.indent_char||' ';var opt_preserve_newlines=typeof options.preserve_newlines==='undefined'?true:options.preserve_newlines;var opt_indent_level=options.indent_level||0;function trim_output(){while(output.length&&(output[output.length-1]===' '||output[output.length-1]===indent_string)){output.pop();}}function print_newline(ignore_repeated){ignore_repeated=typeof ignore_repeated==='undefined'?true:ignore_repeated;if_line_flag=false;trim_output();if(!output.length){return;}if(output[output.length-1]!=="\n"||!ignore_repeated){output.push("\n");}for(var i=0;i<indent_level;i++){output.push(indent_string);}}function print_space(){var last_output=' ';if(output.length){last_output=output[output.length-1];}if(last_output!==' '&&last_output!=='\n'&&last_output!==indent_string){output.push(' ');}}function print_token(){output.push(token_text);}function indent(){indent_level++;}function unindent(){if(indent_level){indent_level--;}}function remove_indent(){if(output.length&&output[output.length-1]===indent_string){output.pop();}}function set_mode(mode){modes.push(current_mode);current_mode=mode;}function restore_mode(){do_block_just_closed=current_mode==='DO_BLOCK';current_mode=modes.pop();}function in_array(what,arr){for(var i=0;i<arr.length;i++){if(arr[i]===what){return true;}}return false;}function get_next_token(){var n_newlines=0;if(parser_pos>=input.length){return['','TK_EOF'];}var c=input.charAt(parser_pos);parser_pos+=1;while(in_array(c,whitespace)){if(parser_pos>=input.length){return['','TK_EOF'];}if(c==="\n"){n_newlines+=1;}c=input.charAt(parser_pos);parser_pos+=1;}var wanted_newline=false;if(opt_preserve_newlines){if(n_newlines>1){for(var i=0;i<2;i++){print_newline(i===0);}}wanted_newline=(n_newlines===1);}if(in_array(c,wordchar)){if(parser_pos<input.length){while(in_array(input.charAt(parser_pos),wordchar)){c+=input.charAt(parser_pos);parser_pos+=1;if(parser_pos===input.length){break;}}}if(parser_pos!==input.length&&c.match(/^[0-9]+[Ee]$/)&&(input.charAt(parser_pos)==='-'||input.charAt(parser_pos)==='+')){var sign=input.charAt(parser_pos);parser_pos+=1;var t=get_next_token(parser_pos);c+=sign+t[0];return[c,'TK_WORD'];}if(c==='in'){return[c,'TK_OPERATOR'];}if(wanted_newline&&last_type!=='TK_OPERATOR'&&!if_line_flag){print_newline();}return[c,'TK_WORD'];}if(c==='('||c==='['){return[c,'TK_START_EXPR'];}if(c===')'||c===']'){return[c,'TK_END_EXPR'];}if(c==='{'){return[c,'TK_START_BLOCK'];}if(c==='}'){return[c,'TK_END_BLOCK'];}if(c===';'){return[c,'TK_SEMICOLON'];}if(c==='/'){var comment='';if(input.charAt(parser_pos)==='*'){parser_pos+=1;if(parser_pos<input.length){while(!(input.charAt(parser_pos)==='*'&&input.charAt(parser_pos+1)&&input.charAt(parser_pos+1)==='/')&&parser_pos<input.length){comment+=input.charAt(parser_pos);parser_pos+=1;if(parser_pos>=input.length){break;}}}parser_pos+=2;return['/*'+comment+'*/','TK_BLOCK_COMMENT'];}if(input.charAt(parser_pos)==='/'){comment=c;while(input.charAt(parser_pos)!=="\x0d"&&input.charAt(parser_pos)!=="\x0a"){comment+=input.charAt(parser_pos);parser_pos+=1;if(parser_pos>=input.length){break;}}parser_pos+=1;if(wanted_newline){print_newline();}return[comment,'TK_COMMENT'];}}if(c==="'"||c==='"'||(c==='/'&&((last_type==='TK_WORD'&&last_text==='return')||(last_type==='TK_START_EXPR'||last_type==='TK_START_BLOCK'||last_type==='TK_END_BLOCK'||last_type==='TK_OPERATOR'||last_type==='TK_EOF'||last_type==='TK_SEMICOLON')))){var sep=c;var esc=false;var resulting_string='';if(parser_pos<input.length){while(esc||input.charAt(parser_pos)!==sep){resulting_string+=input.charAt(parser_pos);if(!esc){esc=input.charAt(parser_pos)==='\\';}else{esc=false;}parser_pos+=1;if(parser_pos>=input.length){break;}}}parser_pos+=1;resulting_string=sep+resulting_string+sep;if(sep=='/'){while(parser_pos<input.length&&in_array(input.charAt(parser_pos),wordchar)){resulting_string+=input.charAt(parser_pos);parser_pos+=1;}}return[resulting_string,'TK_STRING'];}if(in_array(c,punct)){while(parser_pos<input.length&&in_array(c+input.charAt(parser_pos),punct)){c+=input.charAt(parser_pos);parser_pos+=1;if(parser_pos>=input.length){break;}}return[c,'TK_OPERATOR'];}return[c,'TK_UNKNOWN'];}indent_string='';while(opt_indent_size--){indent_string+=opt_indent_char;}indent_level=opt_indent_level;input=js_source_text;last_word='';last_type='TK_START_EXPR';last_text='';output=[];do_block_just_closed=false;var_line=false;var_line_tainted=false;whitespace="\n\r\t ".split('');wordchar='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_$'.split('');punct='+ - * / % & ++ -- = += -= *= /= %= == === != !== > < >= <= >> << >>> >>>= >>= <<= && &= | || ! !! , : ? ^ ^= |= ::'.split(' ');line_starters='continue,try,throw,return,var,if,switch,case,default,for,while,break,function'.split(',');current_mode='BLOCK';modes=[current_mode];parser_pos=0;in_case=false;while(true){var t=get_next_token(parser_pos);token_text=t[0];token_type=t[1];if(token_type==='TK_EOF'){break;}switch(token_type){case'TK_START_EXPR':var_line=false;set_mode('EXPRESSION');if(last_text===';'){print_newline();}else if(last_type==='TK_END_EXPR'||last_type==='TK_START_EXPR'){}else if(last_type!=='TK_WORD'&&last_type!=='TK_OPERATOR'){print_space();}else if(in_array(last_word,line_starters)&&last_word!=='function'){print_space();}print_token();break;case'TK_END_EXPR':print_token();restore_mode();break;case'TK_START_BLOCK':if(last_word==='do'){set_mode('DO_BLOCK');}else{set_mode('BLOCK');}if(last_type!=='TK_OPERATOR'&&last_type!=='TK_START_EXPR'){if(last_type==='TK_START_BLOCK'){print_newline();}else{print_space();}}print_token();indent();break;case'TK_END_BLOCK':if(last_type==='TK_START_BLOCK'){trim_output();unindent();}else{unindent();print_newline();}print_token();restore_mode();break;case'TK_WORD':if(do_block_just_closed){print_space();print_token();print_space();do_block_just_closed=false;break;}if(token_text==='case'||token_text==='default'){if(last_text===':'){remove_indent();}else{unindent();print_newline();indent();}print_token();in_case=true;break;}prefix='NONE';if(last_type==='TK_END_BLOCK'){if(!in_array(token_text.toLowerCase(),['else','catch','finally'])){prefix='NEWLINE';}else{prefix='SPACE';print_space();}}else if(last_type==='TK_SEMICOLON'&&(current_mode==='BLOCK'||current_mode==='DO_BLOCK')){prefix='NEWLINE';}else if(last_type==='TK_SEMICOLON'&&current_mode==='EXPRESSION'){prefix='SPACE';}else if(last_type==='TK_STRING'){prefix='NEWLINE';}else if(last_type==='TK_WORD'){prefix='SPACE';}else if(last_type==='TK_START_BLOCK'){prefix='NEWLINE';}else if(last_type==='TK_END_EXPR'){print_space();prefix='NEWLINE';}if(last_type!=='TK_END_BLOCK'&&in_array(token_text.toLowerCase(),['else','catch','finally'])){print_newline();}else if(in_array(token_text,line_starters)||prefix==='NEWLINE'){if(last_text==='else'){print_space();}else if((last_type==='TK_START_EXPR'||last_text==='=')&&token_text==='function'){}else if(last_type==='TK_WORD'&&(last_text==='return'||last_text==='throw')){print_space();}else if(last_type!=='TK_END_EXPR'){if((last_type!=='TK_START_EXPR'||token_text!=='var')&&last_text!==':'){if(token_text==='if'&&last_type==='TK_WORD'&&last_word==='else'){print_space();}else{print_newline();}}}else{if(in_array(token_text,line_starters)&&last_text!==')'){print_newline();}}}else if(prefix==='SPACE'){print_space();}print_token();last_word=token_text;if(token_text==='var'){var_line=true;var_line_tainted=false;}if(token_text==='if'||token_text==='else'){if_line_flag=true;}break;case'TK_SEMICOLON':print_token();var_line=false;break;case'TK_STRING':if(last_type==='TK_START_BLOCK'||last_type==='TK_END_BLOCK'||last_type=='TK_SEMICOLON'){print_newline();}else if(last_type==='TK_WORD'){print_space();}print_token();break;case'TK_OPERATOR':var start_delim=true;var end_delim=true;if(var_line&&token_text!==','){var_line_tainted=true;if(token_text===':'){var_line=false;}}if(var_line&&token_text===','&&current_mode==='EXPRESSION'){var_line_tainted=false;}if(token_text===':'&&in_case){print_token();print_newline();break;}if(token_text==='::'){print_token();break;}in_case=false;if(token_text===','){if(var_line){if(var_line_tainted){print_token();print_newline();var_line_tainted=false;}else{print_token();print_space();}}else if(last_type==='TK_END_BLOCK'){print_token();print_newline();}else{if(current_mode==='BLOCK'){print_token();print_newline();}else{print_token();print_space();}}break;}else if(token_text==='--'||token_text==='++'){if(last_text===';'){start_delim=true;end_delim=false;}else{start_delim=false;end_delim=false;}}else if(token_text==='!'&&last_type==='TK_START_EXPR'){start_delim=false;end_delim=false;}else if(last_type==='TK_OPERATOR'){start_delim=false;end_delim=false;}else if(last_type==='TK_END_EXPR'){start_delim=true;end_delim=true;}else if(token_text==='.'){start_delim=false;end_delim=false;}else if(token_text===':'){if(last_text.match(/^\d+$/)){start_delim=true;}else{start_delim=false;}}if(start_delim){print_space();}print_token();if(end_delim){print_space();}break;case'TK_BLOCK_COMMENT':print_newline();print_token();print_newline();break;case'TK_COMMENT':print_space();print_token();print_newline();break;case'TK_UNKNOWN':print_token();break;}last_type=token_type;last_text=token_text;}return output.join('');},
    styleHTML : function(html_source,indent_size,indent_character,max_char){var multi_parser;function Parser(){this.pos=0;this.token='';this.current_mode='CONTENT';this.tags={parent:'parent1',parentcount:1,parent1:''};this.tag_type='';this.token_text=this.last_token=this.last_text=this.token_type='';this.Utils={whitespace:"\n\r\t ".split(''),single_token:'br,input,link,meta,!doctype,basefont,base,area,hr,wbr,param,img,isindex,?xml,embed'.split(','),extra_liners:'head,body,/html'.split(','),in_array:function(what,arr){for(var i=0;i<arr.length;i++){if(what===arr[i]){return true;}}return false;}};this.get_content=function(){var input_char='';var content=[];var space=false;while(this.input.charAt(this.pos)!=='<'){if(this.pos>=this.input.length){return content.length?content.join(''):['','TK_EOF'];}input_char=this.input.charAt(this.pos);this.pos++;this.line_char_count++;if(this.Utils.in_array(input_char,this.Utils.whitespace)){if(content.length){space=true;}this.line_char_count--;continue;}else if(space){if(this.line_char_count>=this.max_char){content.push('\n');for(var i=0;i<this.indent_level;i++){content.push(this.indent_string);}this.line_char_count=0;}else{content.push(' ');this.line_char_count++;}space=false;}content.push(input_char);}return content.length?content.join(''):'';};this.get_script=function(){var input_char='';var content=[];var reg_match=new RegExp('\<\/script'+'\>','igm');reg_match.lastIndex=this.pos;var reg_array=reg_match.exec(this.input);var end_script=reg_array?reg_array.index:this.input.length;while(this.pos<end_script){if(this.pos>=this.input.length){return content.length?content.join(''):['','TK_EOF'];}input_char=this.input.charAt(this.pos);this.pos++;content.push(input_char);}return content.length?content.join(''):'';};this.record_tag=function(tag){if(this.tags[tag+'count']){this.tags[tag+'count']++;this.tags[tag+this.tags[tag+'count']]=this.indent_level;}else{this.tags[tag+'count']=1;this.tags[tag+this.tags[tag+'count']]=this.indent_level;}this.tags[tag+this.tags[tag+'count']+'parent']=this.tags.parent;this.tags.parent=tag+this.tags[tag+'count'];};this.retrieve_tag=function(tag){if(this.tags[tag+'count']){var temp_parent=this.tags.parent;while(temp_parent){if(tag+this.tags[tag+'count']===temp_parent){break;}temp_parent=this.tags[temp_parent+'parent'];}if(temp_parent){this.indent_level=this.tags[tag+this.tags[tag+'count']];this.tags.parent=this.tags[temp_parent+'parent'];}delete this.tags[tag+this.tags[tag+'count']+'parent'];delete this.tags[tag+this.tags[tag+'count']];if(this.tags[tag+'count']==1){delete this.tags[tag+'count'];}else{this.tags[tag+'count']--;}}};this.get_tag=function(){var input_char='';var content=[];var space=false;do{if(this.pos>=this.input.length){return content.length?content.join(''):['','TK_EOF'];}input_char=this.input.charAt(this.pos);this.pos++;this.line_char_count++;if(this.Utils.in_array(input_char,this.Utils.whitespace)){space=true;this.line_char_count--;continue;}if(input_char==="'"||input_char==='"'){if(!content[1]||content[1]!=='!'){input_char+=this.get_unformatted(input_char);space=true;}}if(input_char==='='){space=false;}if(content.length&&content[content.length-1]!=='='&&input_char!=='>'&&space){if(this.line_char_count>=this.max_char){this.print_newline(false,content);this.line_char_count=0;}else{content.push(' ');this.line_char_count++;}space=false;}content.push(input_char);}while(input_char!=='>');var tag_complete=content.join('');var tag_index;if(tag_complete.indexOf(' ')!=-1){tag_index=tag_complete.indexOf(' ');}else{tag_index=tag_complete.indexOf('>');}var tag_check=tag_complete.substring(1,tag_index).toLowerCase();if(tag_complete.charAt(tag_complete.length-2)==='/'||this.Utils.in_array(tag_check,this.Utils.single_token)){this.tag_type='SINGLE';}else if(tag_check==='script'){this.record_tag(tag_check);this.tag_type='SCRIPT';}else if(tag_check==='style'){this.record_tag(tag_check);this.tag_type='STYLE';}else if(tag_check.charAt(0)==='!'){if(tag_check.indexOf('[if')!=-1){if(tag_complete.indexOf('!IE')!=-1){var comment=this.get_unformatted('-->',tag_complete);content.push(comment);}this.tag_type='START';}else if(tag_check.indexOf('[endif')!=-1){this.tag_type='END';this.unindent();}else if(tag_check.indexOf('[cdata[')!=-1){comment=this.get_unformatted(']]>',tag_complete);content.push(comment);this.tag_type='SINGLE';}else{comment=this.get_unformatted('-->',tag_complete);content.push(comment);this.tag_type='SINGLE';}}else{if(tag_check.charAt(0)==='/'){this.retrieve_tag(tag_check.substring(1));this.tag_type='END';}else{this.record_tag(tag_check);this.tag_type='START';}if(this.Utils.in_array(tag_check,this.Utils.extra_liners)){this.print_newline(true,this.output);}}return content.join('');};this.get_unformatted=function(delimiter,orig_tag){if(orig_tag&&orig_tag.indexOf(delimiter)!=-1){return'';}var input_char='';var content='';var space=true;do{input_char=this.input.charAt(this.pos);this.pos++;if(this.Utils.in_array(input_char,this.Utils.whitespace)){if(!space){this.line_char_count--;continue;}if(input_char==='\n'||input_char==='\r'){content+='\n';for(var i=0;i<this.indent_level;i++){content+=this.indent_string;}space=false;this.line_char_count=0;continue;}}content+=input_char;this.line_char_count++;space=true;}while(content.indexOf(delimiter)==-1);return content;};this.get_token=function(){var token;if(this.last_token==='TK_TAG_SCRIPT'){var temp_token=this.get_script();if(typeof temp_token!=='string'){return temp_token;}token=BuildSource.jsBeautify(temp_token,{indent_size:this.indent_size,indent_char:this.indent_character,indent_level:this.indent_level});return[token,'TK_CONTENT'];}if(this.current_mode==='CONTENT'){token=this.get_content();if(typeof token!=='string'){return token;}else{return[token,'TK_CONTENT'];}}if(this.current_mode==='TAG'){token=this.get_tag();if(typeof token!=='string'){return token;}else{var tag_name_type='TK_TAG_'+this.tag_type;return[token,tag_name_type];}}};this.printer=function(js_source,indent_character,indent_size,max_char){this.input=js_source||'';this.output=[];this.indent_character=indent_character||' ';this.indent_string='';this.indent_size=indent_size||2;this.indent_level=0;this.max_char=max_char||70;this.line_char_count=0;for(var i=0;i<this.indent_size;i++){this.indent_string+=this.indent_character;}this.print_newline=function(ignore,arr){this.line_char_count=0;if(!arr||!arr.length){return;}if(!ignore){while(this.Utils.in_array(arr[arr.length-1],this.Utils.whitespace)){arr.pop();}}arr.push('\n');for(var i=0;i<this.indent_level;i++){arr.push(this.indent_string);}};this.print_token=function(text){this.output.push(text);};this.indent=function(){this.indent_level++;};this.unindent=function(){if(this.indent_level>0){this.indent_level--;}};};return this;}multi_parser=new Parser();multi_parser.printer(html_source,indent_character,indent_size,max_char);while(true){var t=multi_parser.get_token();multi_parser.token_text=t[0];multi_parser.token_type=t[1];if(multi_parser.token_type==='TK_EOF'){break;}switch(multi_parser.token_type){case'TK_TAG_START':case'TK_TAG_SCRIPT':case'TK_TAG_STYLE':multi_parser.print_newline(false,multi_parser.output);multi_parser.print_token(multi_parser.token_text);multi_parser.indent();multi_parser.current_mode='CONTENT';break;case'TK_TAG_END':multi_parser.print_newline(true,multi_parser.output);multi_parser.print_token(multi_parser.token_text);multi_parser.current_mode='CONTENT';break;case'TK_TAG_SINGLE':multi_parser.print_newline(false,multi_parser.output);multi_parser.print_token(multi_parser.token_text);multi_parser.current_mode='CONTENT';break;case'TK_CONTENT':if(multi_parser.token_text!==''){multi_parser.print_newline(false,multi_parser.output);multi_parser.print_token(multi_parser.token_text);}multi_parser.current_mode='TAG';break;}multi_parser.last_token=multi_parser.token_type;multi_parser.last_text=multi_parser.token_text;}return multi_parser.output.join('');}
};
