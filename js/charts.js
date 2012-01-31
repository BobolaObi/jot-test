var Charts = Class.create( {
	types : {
		"Pie" : "p",
		"Pie 2" : "p3",
		"Bar" : "bvs",
		"Bar 2" : "bhs",
		"Line" : "lc"
	},
	options : false,
	chart : false,
	/**
	 * @constructer
	 */
	initialize : function(options) {
		this.options = Object.extend({
			type : 'Pie',
			data : [ 0 ],
			labels : [ "Label" ],
			height : 250,
			width : 500,
			labelPrefix:'',
			title : "Chart",
			colors : [ 'DED288', 'BAA868', '757A62', '394452', '1E2D40' ],
			className : 'widget-chart',
			id : '',
			dynamic : true
		}, options || {});
	},

	encodeData : function(valueArray) {
		var simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		var maxValue = valueArray.max()
				+ (valueArray.max() / 10).round();

		var chartData = [ 's:' ];

		for ( var i = 0; i < valueArray.length; i++) {
			var currentValue = valueArray[i];
			if (!isNaN(currentValue) && currentValue >= 0) {
				chartData.push(simpleEncoding.charAt(Math.round((simpleEncoding.length - 1) * currentValue / maxValue)));
			} else {
				chartData.push('_');
			}
		}
		return chartData.join('');
	},

	parseParameters : function() {
		var $this = this;
		var options = $this.options;
		var legendValues = [];

		$A(options.labels).each(function(label, i) {
			legendValues.push(label + " (" + options.data[i] + ")");
		});

		var ctype = $this.types[options.type];

		var parameters = {
			cht : ctype, // Chart Type
			// chd: $this.encodeData(options.data), // Chart Data
			chd : "t:" + options.data.join(","),
			chl : $A(options.labels).map(function(x) {
				return options.labelPrefix + x.shorten(15);
			}).join('|'), // Chart Labels
			chs : options.width + "x" + options.height, // Chart Size
			chtt : " "+options.title.shorten(60), // Chart Title
			chco : options.colors.join(","), // Chart Colors
			chdl : $A(legendValues).map(function(x) {
				return options.labelPrefix + x;
			}).join("|"), // Chart Legends
			chma : '5,5,5,5|1000,30', // Chart Margin, Legend
										// Dimensions
			chdlp : 'b', // Chart Legend Position
			chbh : 'a' // Bar Chart auto resize each bar
		};
		
		parameters = $this.fixLabels(parameters);
		return parameters;
	},
	
	getMax: function(){
		return (this.options.data || [0]).map(function(v){ return parseInt(v, 10); }).max();
	},	
	
	getStep : function(max) {
        /*
		if (max < 10) {
			step = 2;
		} else if (max < 25) {
			step = 5;
		} else if (max < 50) {
			step = 10;
		} else if (max < 100) {
			step = 20;
		} else if (max < 250) {
			step = 50;
		} else if (max < 500) {
			step = 100;
		} else if (max < 1000) {
			step = 200;
        } else if (max > 10000) {
            step = 2000;
		} else {
			step = 500;
		}*/
       
		return Math.round(max/5);
	},

	fixLabels : function(parameters) {
		var options = this.options;
		var ctype = parameters.cht;
		var labels = $A(this.options.labels).map(function(x) {
			return options.labelPrefix + x.shorten(15);
		}).join('|');
		var max = this.getMax();
		var step = this.getStep(max);
		var overload = max + (max / 10).round();
		
		
		var ygrid = (step*100 / overload).toFixed(2), xgrid;
		
		if ( [ "bhs", "bvs" ].include(ctype)) {
			xgrid = (100 / (this.options.data.length)).toFixed(2);
			if (ctype == "bhs") {
				parameters.chxt = "x,y";
				parameters.chxl = "1:|" + (labels.split("|").reverse().join("|")) + "|";
				parameters.chg = ygrid+"," + xgrid;
				
			} else {
				parameters.chxt = "y,x";
				parameters.chxl = "1:|" + labels + "|";
				parameters.chg = xgrid+"," + ygrid;
			}

			parameters.chds = "0," + overload;
			parameters.chxr = "0,0," + overload + "," + step;
			parameters.chm = "N*f1*,000000,0,-1,11";
			parameters.chco = "";
			
			
			delete parameters.chl;

		} else if (ctype == "lc") {
			xgrid = (100 / (this.options.data.length-1)).toFixed(2);
			parameters.chm = "B,76A4FB,0,0,0";
			parameters.chco = "224499";
			parameters.chxt = "y";
			parameters.chds = "0," + overload;
			parameters.chxr = "0,0," + overload + "," + step;
			parameters.chg = xgrid+"," +ygrid;

		}else if(ctype == "p" || ctype == "p3"){
			parameters.chp = "5.23";
		}
		
		return parameters;
	},

	createChart : function() {

		var URL = "http://chart.apis.google.com/chart?";
		var $this = this;

		var parameters = $this.parseParameters($this.options);
		
		$this.chart = new Element("img", {
			src : URL + $H(parameters).toQueryString(),
			height : $this.options.dynamic ? "100%"
					: $this.options.height,
			width : $this.options.dynamic ? "100%"
					: $this.options.width,
			className : $this.options.className,
			id : $this.options.id
		});

		$this.chart.chart = {

			updateSize : function(width, height) {
				$this.options.width = width;
				$this.options.height = height;
				parameters = $this.parseParameters();
				$this.chart.src = URL + $H(parameters).toQueryString();
			},

			updateChart : function(opts) {
				$this.options = Object.extend($this.options, opts || {});
				parameters = $this.parseParameters();
				$this.chart.src = URL + $H(parameters).toQueryString();
			},

			changeType : function(type) {
				$this.options.type = type;
				parameters = $this.parseParameters();
				$this.chart.src = URL + $H(parameters).toQueryString();
			},
			
			getInstance: function(){
				return $this;
			}
		};

		return $this.chart;
	}
});