var Facebook = {
    get: function(id){ return document.getElementById(id); },
    map: function(array, block) {
        results = [];
        for (var i = 0, l = array.length; i < l; i++) { results.push(block(array[i])); }
        return results;
    },
    domCollect: function(element, matcher) {
        var $this = this, collection = [];
        var recurse = function(subelement) {
            var nodes = subelement.getChildNodes();
            $this.map(nodes, function(node) {
                if (matcher(node)) { collection.push(node); }
                if (node.getFirstChild()) { recurse(node); }
            });
        };
        recurse(element);
        return collection;
    },
    getByName: function (elementName) {
        var matcher = function(element) {
            return (element.getName() === elementName);
        };
        return this.domCollect(document.getRootElement(), matcher);
    },
    alert: function(msg, title) { new Dialog().showMessage(title, msg); },
    error: function(msg, liid) {
        this.get('id_' + liid).setStyle('backgroundColor', '#FFAAAA');
        this.alert(msg, "Error");
        return false;
    },
    validateEmail: function(lid) {
        var tvalue = this.get('input_' + lid).getValue();
        var email = /[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])/i;
        return tvalue.match(email);
    },
    checkElement: function(temp, etype, ename) {
        var templid = temp.split("_");
        var lid = templid[1];
        var tel = this.get(temp);
        var error = false;
        switch(etype){
            case "dropdown":
                error = this.get(temp).getSelectedIndex() === 0;
            break;
            case "textarea":
            case "fileupload":
                error = this.get(temp).getValue().length < 1;
            break;
			case "fullname":
                if(this.get('first_'+lid).getValue().length < 1 || this.get('last_'+lid).getValue().length < 1){
                    error = true;
                }
            break;
			case "birthdate":
                error = (this.get(temp+"_month").getSelectedIndex() === 0) || 
				(this.get(temp+"_day").getSelectedIndex() === 0) ||
				(this.get(temp+"_year").getSelectedIndex() === 0);
            break;
            case "email":
            case "textbox":
                if(this.get(temp).getValue().length < 1){
                    error = true;
                }else{
                    if(this.get(temp).getClassName().indexOf('validate') != -1 && this.get(temp).getClassName().indexOf('Email') != -1){
                        if( ! this.validateEmail(lid)){
                            error = "Please enter a valid email address";
                        }
                    }
                }
            break;
            case "radio":
            case "checkbox":
                tel = this.get(temp + "_0");
                var elt = this.getByName(tel.getName());
                var ll = 0;
                if (elt.length == 1) {
                    if (elt[0].getChecked() === false) { error = true; }
                } else {
                    ll = elt.length;
                    var chk = false;
                    for (j = 0; j < ll; j++) {
                        if (elt[j].getChecked() === true) { chk = true; break; }
                    }
                    if (chk === false) { error = true; }
                }
            break;
        }
        if(error !== false){
            this.error(((error === true)? ename+' is required' : error), lid);
            return false;
        }else{
            this.get('id_' + lid).setStyle('backgroundColor', 'transparent');
            return true;
        }
    },
    checkForm: function() {
        var tempo = this.get('reqids').getValue().split(",");
        for (var i = 0; i < tempo.length; i++) {
            var mixedel = tempo[i].split("*");
            if (this.checkElement(mixedel[0], mixedel[1], mixedel[2]) === false) {
                return false;
            }
        }
        return true;
    }
};