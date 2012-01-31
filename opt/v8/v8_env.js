var window = {};
var optionsCache = {};

String.prototype.locale = function(){
    return this.toString();
};
String.prototype.sanitize = function(){
    var str = this;
    return (str+'').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
};

String.prototype.strip = function(){
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
};

String.prototype.stripScripts = function(){
    return this.replace(/<script[^>]*>([\\S\\s]*?)<\/script>/gim, '');
};

String.prototype.stripTags = function(){
    return this.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi, '');
};

String.prototype.nl2br = function(is_xhtml){
    var str = this;
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'');// +'\n'); Removed trailing new line
};

function print_r(obj, level){
    level = (level) ? level + 1 : 1;
    var add = "";
    for (var i = 1; i < level; i++) {
        add += "|    ";
    }
    
    for (var k in obj) {
        if (typeof obj[k] == 'string') {
            print(add + "|__" + k + " => " + obj[k]);
        } else if (typeof obj[k] == 'function') {
            print(add + "|__" + k + " => Function");
        } else {
            print_r(obj[k], level);
        }
    }
}   

function openStyleMenu(){
    
}