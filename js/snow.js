//Snow - http://www.btinternet.com/~kurt.grigg/javascript
var stopSnow = false;
if ((document.getElementById) &&
window.addEventListener ||
window.attachEvent) {

    (function(){
    
        //Configure here.
        
        var num = 30; //Number of flakes
        var timer = 30; //setTimeout speed. Varies on different comps
        var enableinNS6 = 1; //Enable script in NS6/Mozilla? Snow animation could be slow in those browsers. (1=yes, 0=no).
        //End.
        
        var y = [];
        var x = [];
        var fall = [];
        var theFlakes = [];
        var sfs = [];
        var step = [];
        var currStep = [];
        var h, w, r;
        var d = document;
        var pix = "px";
        var domWw = (typeof window.innerWidth == "number");
        var domSy = (typeof window.pageYOffset == "number");
        var idx = d.getElementsByTagName('div').length;
        
        if (d.documentElement.style &&
        typeof d.documentElement.style.MozOpacity == "string") {
            num = 12;
        }
        
        for (i = 0; i < num; i++) {
            sfs[i] = Math.round(1 + Math.random() * 1);
            
            document.write('<div class="flakes" id="flake' + (idx + i) + '" style="position:absolute;top:0px;left:0px;width:' + sfs[i] + 'px;height:' + sfs[i] + 'px;background-color:#ffffff;font-size:' + sfs[i] + 'px"><\/div>');
            
            currStep[i] = 0;
            fall[i] = (sfs[i] == 1) ? Math.round(2 + Math.random() * 2) : Math.round(3 + Math.random() * 2);
            step[i] = (sfs[i] == 1) ? 0.05 + Math.random() * 0.1 : 0.05 + Math.random() * 0.05;
        }
        
        
        if (domWw) {
            r = window;
        } else {
            if (d.documentElement && typeof d.documentElement.clientWidth == "number" && d.documentElement.clientWidth !== 0) {
                r = d.documentElement;
            } else {
                if (d.body && typeof d.body.clientWidth == "number") {
                    r = d.body;
                }
            }
        }
        
        window.winsize = function(){
            var oh, sy, ow, sx, rh, rw;
            if (domWw) {
                if (d.documentElement && d.defaultView &&
                typeof d.defaultView.scrollMaxY == "number") {
                    oh = d.documentElement.offsetHeight;
                    sy = 0;//d.defaultView.scrollMaxY;
                    ow = d.documentElement.offsetWidth;
                    sx = 0;//d.defaultView.scrollMaxX;
                    rh = oh - sy;
                    rw = ow - sx;
                } else {
                    rh = r.innerHeight;
                    rw = r.innerWidth;
                }
                h = rh - 2;
                w = rw - 2;
            } else {
                h = r.clientHeight - 2;
                w = r.clientWidth - 2;
            }
            return [h, w];
        };
        
        
        function scrl(yx){
            var y, x;
            if (domSy) {
                y = r.pageYOffset;
                x = r.pageXOffset;
            } else {
                y = r.scrollTop;
                x = r.scrollLeft;
            }
            return (yx === 0) ? y : x - 20; // Remove 20 pixels to prevent accidental scrollbars
        }
        
        
        function snow(){
            var dy, dx;
            if (stopSnow) { return; }
            for (i = 0; i < num; i++) {
                dy = fall[i];
                dx = fall[i] * Math.cos(currStep[i]);
                
                y[i] += dy;
                x[i] += dx;
                
                if (x[i] >= w || y[i] >= h) {
                    y[i] = -10;
                    x[i] = Math.round(Math.random() * w);
                    fall[i] = (sfs[i] == 1) ? Math.round(2 + Math.random() * 2) : Math.round(3 + Math.random() * 2);
                    step[i] = (sfs[i] == 1) ? 0.05 + Math.random() * 0.1 : 0.05 + Math.random() * 0.05;
                }
                
                theFlakes[i].top = y[i] + scrl(0) + pix;
                theFlakes[i].left = x[i] + scrl(1) + pix;
                
                currStep[i] += step[i];
            }
            setTimeout(snow, timer);
        }
        
        
        function init(){
            winsize();
            for (i = 0; i < num; i++) {
                theFlakes[i] = document.getElementById("flake" + (idx + i)).style;
                y[i] = Math.round(Math.random() * h);
                x[i] = Math.round(Math.random() * w);
            }
            snow();
            
            if ('Prototype' in window) {
                var stop = function(t){
                    if (stopSnow) { return; } // if already stopped don't waste your time
                    stopSnow = t;
                    $$('.flakes').invoke('shift', {opacity:0, duration:0.5});
                };
                
                var start = function(t){
                    
                    if(t == 'scroll' && stopSnow == 'blur'){ return true; }
                    
                    stopSnow = false;
                    $$('.flakes').invoke('shift', {opacity:1, duration:0.5});
                    snow();
                };
                
                var scrollT = false;
                Element.observe(window, 'scroll', function(){
                    stop('scroll');
                    clearTimeout(scrollT);
                    scrollT = setTimeout(function(){
                        start('scroll');
                    }, 400);
                });
                
                Element.observe(window, 'blur', function(){
                    stop('blur');
                });
                Element.observe(window, 'focus', function(){
                    start('focus');
                });
            }
        }
        
        
        if (window.addEventListener) {
            window.addEventListener("resize", winsize, false);
            window.addEventListener("load", init, false);
        } else if (window.attachEvent) {
            window.attachEvent("onresize", winsize);
            window.attachEvent("onload", init);
        }
        
    })();
}//End.