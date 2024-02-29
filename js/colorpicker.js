var ColorPickerUtils = {
	$S: function (o) { o=$(o); if(o) return(o.style); },
	abPos: function (o) { var o=(typeof(o)=='object'?o:$(o)), z={X:0,Y:0}; while(o!=null) { z.X+=o.offsetLeft; z.Y+=o.offsetTop; o=o.offsetParent; }; return(z); },
	agent: function (v) { return(Math.max(navigator.userAgent.toLowerCase().indexOf(v),0)); },
	toggle: function (v) { ColorPickerUtils.$S(v).display=(ColorPickerUtils.$S(v).display=='none'?'block':'none'); },
	within: function (v,a,z) { return((v>=a && v<=z)?true:false); },
	XY: function (e,v) { var o=ColorPickerUtils.agent('msie')?{'X':event.clientX+document.documentElement.scrollLeft,'Y':event.clientY+document.documentElement.scrollTop}:{'X':e.pageX,'Y':e.pageY}; return(v?o[v]:o); },
	zero: function (v) { v=parseInt(v); return(!isNaN(v)?v:0); }
};

/* COLOR PICKER */

var ColorPicker = Class.create({
	initialize: function ( container ){
		// set the container
		this.container = container;
	
		this.maxValue = {'H':360,'S':100,'V':100}, HSV={H:360, S:100, V:100};
		this.slideHSV = {H:360, S:100, V:100};
		this.zINDEX = 15;
		this.stop = 1;
		this.initialColorCode = "FFFFFF";
		this.closeText = "X";
		
		// create the elements
		this.createElements();
		// load the SV
		this.loadSV();

		this.HSVupdate({H:0, S:0, V:20});
	},
	createElements: function (){
		// create and add colorspy
		this.colorspy = new Element("div");
		this.container.insert(this.colorspy);
		
		// create and add plugin
		this.plugin = new Element("div", {
			style: "BACKGROUND: #0d0d0d; COLOR: #AAA; CURSOR: move; FONT-FAMILY: arial; FONT-SIZE: 11px; line-height: 1em; PADDING: 7px 10px 11px 10px; _PADDING-RIGHT: 0; Z-INDEX: 1; POSITION: absolute; WIDTH: 199px; _width: 210px; _padding-right: 0px;"
		});
		// TODO: add event to plugin
		this.colorspy.appendChild(this.plugin);
		
		// create and add plugCUR
		this.plugCUR = new Element("div", {
			style: "float: left; width: 10px; height: 10px; font-size: 1px; background: #FFF; margin-right: 3px;"
		});
		this.plugin.appendChild(this.plugCUR);
		
		// create and add plugHEX
		this.plugHEX = new Element("div", {
			style: "FLOAT: left; position: relative; top: -1px;"
		}).update(this.initialColorCode);
		this.plugin.appendChild(this.plugHEX);

		// create and add plugCLOSE
		this.plugCLOSE = new Element("div",{
			style:"FLOAT: right; cursor: pointer; MARGIN: 0 8px 3px; _MARGIN-RIGHT: 10px; COLOR: #FFF; -moz-user-select: none; -khtml-user-select: none; user-select: none;"
		}).update(this.closeText);
		this.plugin.appendChild(this.plugCLOSE);

		// create and add SV
		this.SV = new Element("div", {
			style:"background: #FF0000 url('http://www.colorjack.com/software/media/SatVal.png'); _BACKGROUND: #FF0000; POSITION: relative; CURSOR: crosshair; FLOAT: left; HEIGHT: 166px; WIDTH: 167px; _WIDTH: 166px; MARGIN-RIGHT: 10px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='media/SatVal.png', sizingMethod='scale'); -moz-user-select: none; -khtml-user-select: none; user-select: none;"
		});
		this.plugin.appendChild(this.SV);
		
		// create and add SV
		this.SVslide = new Element("div", {
			style: "BACKGROUND: url('http://www.colorjack.com/software/media/slide.gif'); HEIGHT: 9px; WIDTH: 9px; POSITION: absolute; _font-size: 1px; line-height: 1px;"
		});
		this.SV.appendChild(this.SVslide);
		
		// create and add S
		this.H = new Element("form", {
			"title": "Hue"
		});
		this.plugin.appendChild(this.H, {
			style: "BORDER: 1px solid #000; CURSOR: crosshair; FLOAT: left; HEIGHT: 154px; POSITION: relative; WIDTH: 19px; PADDING: 0; TOP: 4px; -moz-user-select: none; -khtml-user-select: none; user-select: none;"
		});
		
		// create and add Hslide
		this.Hslide = new Element("div", {
			style: "float:left;BACKGROUND: url('http://www.colorjack.com/software/media/slideHue.gif'); HEIGHT: 5px; WIDTH: 33px; POSITION: absolute; _font-size: 1px; line-height: 1px;"
		});
		this.H.appendChild(this.Hslide);
		
		// create and add Hmodel
		this.Hmodel = new Element("div", {
			style: "float:left;top:4px;POSITION: relative; TOP: -5px;"
		});
		this.H.appendChild(this.Hmodel);
		
	},
	HSVslide: function (d,o,e) {
		function tXY(e) { tY=ColorPickerUtils.XY(e).Y-ab.Y; tX=ColorPickerUtils.XY(e).X-ab.X; }
		function mkHSV(a,b,c) { return(Math.min(a,Math.max(0,Math.ceil((parseInt(c)/b)*a)))); }
		function ckHSV(a,b) { if(ColorPickerUtils.within(a,0,b)) return(a); else if(a>b) return(b); else if(a<0) return('-'+oo); }
		function drag(e) { if(!stop) { if(d!='drag') tXY(e);
		
			if(d=='SVslide') { ds.left=ckHSV(tX-oo,162)+'px'; ds.top=ckHSV(tY-oo,162)+'px';
	
				slideHSV.S=mkHSV(100,162,ds.left); slideHSV.V=100-mkHSV(100,162,ds.top); HSVupdate();
	
			}
			else if(d=='Hslide') { var ck=ckHSV(tY-oo,163), r=['H','S','V'], z={};
			
				ds.top=(ck-5)+'px'; slideHSV.H=mkHSV(360,163,ck);
	
				for(var i in r) { i=r[i]; z[i]=(i=='H')?maxValue[i]-mkHSV(maxValue[i],163,ck):HSV[i]; }
	
				HSVupdate(z); ColorPickerUtils.$S('SV').backgroundColor='#'+color.HSV_HEX({H:HSV.H, S:100, V:100});
	
			}
			else if(d=='drag') { ds.left=ColorPickerUtils.XY(e).X+oX-eX+'px'; ds.top=ColorPickerUtils.XY(e).Y+oY-eY+'px'; }
	
		}}
	
		if(stop) { stop=''; var ds=ColorPickerUtils.$S(d!='drag'?d:o);
	
			if(d=='drag') { var oX=parseInt(ds.left), oY=parseInt(ds.top), eX=ColorPickerUtils.XY(e).X, eY=ColorPickerUtils.XY(e).Y; ColorPickerUtils.$S(o).zIndex=zINDEX++; }
	
			else { var ab=ColorPickerUtils.abPos($(o)), tX, tY, oo=(d=='Hslide')?2:4; ab.X+=10; ab.Y+=22; if(d=='SVslide') slideHSV.H=HSV.H; }
	
			document.onmousemove=drag; document.onmouseup=function(){ stop=1; document.onmousemove=''; document.onmouseup=''; }; drag(e);
	
		}
	},
	HSVupdate: function (v) {
		// get the color value
		var v=color.HSV_HEX(HSV=v?v:slideHSV);
		// update the text
		this.plugHEX.update=v;
		// update the background color
		this.plugCUR.setStyle({
			background: '#' + v
		});
	},
	loadSV: function () {
		var z='';
		for(var i=165; i>=0; i--) { z+="<div style=\"BACKGROUND: #"+color.HSV_HEX({H:Math.round((360/165)*i), S:100, V:100})+";HEIGHT: 1px; WIDTH: 19px; font-size: 1px; line-height: 1px; MARGIN: 0; PADDING: 0;\"><br /><\/div>"; }
		this.Hmodel.update(z);
	}
});


/* COLOR LIBRARY */

color={};

color.cords=function(W) {

	var W2=W/2, rad=(hsv.H/360)*(Math.PI*2), hyp=(hsv.S+(100-hsv.V))/100*(W2/2);

	ColorPickerUtils.$S('mCur').left=Math.round(Math.abs(Math.round(Math.sin(rad)*hyp)+W2+3))+'px';
	ColorPickerUtils.$S('mCur').top=Math.round(Math.abs(Math.round(Math.cos(rad)*hyp)-W2-21))+'px';

};

color.HEX=function(o) { o=Math.round(Math.min(Math.max(0,o),255));

    return("0123456789ABCDEF".charAt((o-o%16)/16)+"0123456789ABCDEF".charAt(o%16));

};

color.RGB_HEX=function(o) { var fu=color.HEX; return(fu(o.R)+fu(o.G)+fu(o.B)); };

color.HSV_RGB=function(o) {
    
    var R, G, A, B, C, S=o.S/100, V=o.V/100, H=o.H/360;

    if(S>0) { if(H>=1) H=0;

        H=6*H; F=H-Math.floor(H);
        A=Math.round(255*V*(1-S));
        B=Math.round(255*V*(1-(S*F)));
        C=Math.round(255*V*(1-(S*(1-F))));
        V=Math.round(255*V); 

        switch(Math.floor(H)) {

            case 0: R=V; G=C; B=A; break;
            case 1: R=B; G=V; B=A; break;
            case 2: R=A; G=V; B=C; break;
            case 3: R=A; G=B; B=V; break;
            case 4: R=C; G=A; B=V; break;
            case 5: R=V; G=A; B=B; break;

        }

        return({'R':R?R:0, 'G':G?G:0, 'B':B?B:0, 'A':1});

    }
    else return({'R':(V=Math.round(V*255)), 'G':V, 'B':V, 'A':1});

};

color.HSV_HEX=function(o) { return(color.RGB_HEX(color.HSV_RGB(o))); }
