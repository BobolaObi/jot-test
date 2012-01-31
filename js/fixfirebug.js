var DebugConsole = Class.create({
    
    initialize: function(no_console){
        
        if(('__SERVERSIDE__' in window) || no_console){
            this.noconsole = true;
            return;
        }
        var div = new Element('div');
        
        div.setOpacity(0.3);
        div.setStyle('background:#f5f5f5; border-top:1px solid #aaa; position:fixed; bottom:0px; left:0px; width:100%;z-index:100000; height:20px; overflow:hidden');
        
        div.mouseEnter(function(el){
            el.shift({opacity:1, duration:0.5, link:'queue'});
        }, function(el){
            el.shift({opacity:0.3, duration:0.5, link:'queue'});
        })
        
        var title = new Element('div');
        title.setStyle('height:18px; padding:2px 5px 0px 5px; border-bottom:1px solid #aaa; background:#eee url(images/grad.png); text-shadow:#fff 0px 1px 0px;');
        title.insert('Debug Console');
        title.onclick = function(){
            if(this.closed){
                this.openConsole();
            }else{
                this.closeConsole();
            }
        }.bind(this);
        
        var close = new Element('img', {src:'images/arrow_up.png'}).setStyle('position:absolute; right:10px;top:2px;');
        this.closeImage = close;
        
        var console = new Element('div').setStyle('background:#fff; height:110px;overflow:auto;list-style:none;list-style-position:outside;');
        var runBar = new Element('div').setStyle('background:#fff; height:19px; border-top:1px solid #aaa;');
        var clearButton = new Element('input', {type:'button', value:'Clear'}).setStyle('border:none; background:none; overflow:hidden');
        clearButton.onclick = function(){
            this.clearConsole();
        }.bind(this);
        
        var codeInput = new Element('input', {type:'text'}).setStyle('border:none; height:19px;width:80%;');
        codeInput.observe('keyup', function(e){
            if(e.keyCode == 13){
                if(!codeInput.value){ return; }
                try{
                    this.runCode(codeInput.value);
                }catch(er){
                    this.addToConsole(er, 'error');
                }
                codeInput.value = "";
            }
        }.bind(this));
        
        var emptyLi = new Element('li').setStyle('margin:5px;').insert('&nbsp;');
        this.emptyLi = emptyLi;
        console.insert(emptyLi);
        title.insert(close);
        div.insert(title);
        div.insert(console);
        runBar.insert(clearButton);
        runBar.insert('<span style="color:blue;font-size:10px;">&gt;&gt;&gt; </span> ');
        
        runBar.insert(codeInput);
        div.insert(runBar);
        
        $(document.body).insert(div);
        this.console = console;
        this.closed=true;
        this.body = div;
    },
    openConsole:function(){
        this.body.shift({height:150, duration:0.2, link:'queue'});
        this.closeImage.src='images/arrow_down.png';
        this.closed=false;
    },
    closeConsole: function(){
        this.body.shift({height:20, duration:0.2, link:'queue'});
        this.closeImage.src='images/arrow_up.png';
        this.closed=true;
    },
    runCode: function(code){
        this.addToConsole(eval(code), 'log');
    },
    clearConsole: function(){
        this.console.update();
    },
    addToConsole: function(log, type){
        if(this.noconsole){
            return;
        }
        log = this.fixInput(log);
        
        var li = new Element('li').setStyle('padding:3px;border-top:1px solid #eee;');
        
        switch(type){
            case "error":
                li.setStyle('background:lightyellow; color:red; font-weight:bold;');
                li.insert('<img align="top" src="images/cross-circle.png"> ');                
            break;
            case "info":
                li.insert('<img align="top" src="images/information.png"> ');
            break;
            case "warn":
                li.insert('<img align="top" src="images/exclamation.png"> ');
            break;
        }
        li.insert(log);
        this.emptyLi.insert({before: li});
    },
    fixInput: function(args){
        var first = args[0];
        args = $A(args).splice(1, args.length);
        if(typeof first == "string"){
            first = first.printf.apply(first, args);
        }else if (Object.isArray(first)){
            first = "<i style='color:navy;cursor:default;''>"+Object.toJSON(first).escapeHTML()+"</i>";
        }else if (Object.isHash(first)){
            first = "<i style='color:navy;cursor:default;''>"+Object.toJSON(first).escapeHTML()+"</i>";
        }else if (Object.isElement(first)){
            var i = new Element('i').setStyle('color:navy; cursor:default;');
            var el = first;
            i.hover(function(){
                el.style.outline = '2px solid navy';
            }, function(){
                el.style.outline = '';
            });
            first = i.insert(Element.inspect(first).escapeHTML());
        }
        this.console.scrollTop=this.console.scrollHeight
        return first;
    },
    /**
     * Console API
     * @param {Object} log
     */
    log:function(){
        this.addToConsole(arguments, 'log');
    },
    error:function(){
        this.addToConsole(arguments, 'error');
    },
    info: function(){
        this.addToConsole(arguments, 'info');
    },
    warn: function(){
        this.addToConsole(arguments, 'warn');
    }
})