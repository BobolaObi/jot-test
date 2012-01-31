Ext.apply(Ext.form.VTypes, {
    daterange : function(val, field) {
        var date = field.parseDate(val);
 
        if(!date){
            return;
        }
        if (field.startDateField && (!this.dateRangeMax || (date.getTime() != this.dateRangeMax.getTime()))) {
            var start = Ext.getCmp(field.startDateField);
            start.setMaxValue(date);
            //start.validate();
            this.dateRangeMax = date;
        } 
        else if (field.endDateField && (!this.dateRangeMin || (date.getTime() != this.dateRangeMin.getTime()))) {
            var end = Ext.getCmp(field.endDateField);
            end.setMinValue(date);
            //end.validate();
            this.dateRangeMin = date;
        }
        /*
         * Always return true since we're only using this vtype to set the
         * min/max allowed values (these are tested for after the vtype test)
         */
        return true;
    }
});

/**
 * Fix the prototype adapter
 * Use protoplus animations instead of scriptaculous.
 * because we don't include it everywhere
 */
Ext.lib.Anim = function(){
    var i = {
        easeOut: function(k){
            return 1 - Math.pow(1 - k, 2)
        },
        easeIn: function(k){
            return 1 - Math.pow(1 - k, 2)
        }
    };
    var j = function(k, l){
        return {
            stop: function(m){
                this.effect.cancel()
            },
            isAnimated: function(){
                return this.effect.state == "running"
            },
            proxyCallback: function(){
                Ext.callback(k, l)
            }
        }
    };
    return {
        scroll: function(n, l, p, q, k, m){
            var o = j(k, m);
            n = Ext.getDom(n);
            if (typeof l.scroll.to[0] == "number") {
                n.scrollLeft = l.scroll.to[0]
            }
            if (typeof l.scroll.to[1] == "number") {
                n.scrollTop = l.scroll.to[1]
            }
            o.proxyCallback();
            return o
        },
        motion: function(n, l, o, p, k, m){
            return this.run(n, l, o, p, k, m)
        },
        color: function(n, l, o, p, k, m){
            return this.run(n, l, o, p, k, m)
        },
        run: function(m, v, r, u, n, x, w){
            var l = {};
            for (var q in v) {
                switch (q) {
                    case "points":
                        var t, z, s = Ext.fly(m, "_animrun");
                        s.position();
                        if (t = v.points.by) {
                            var y = s.getXY();
                            z = s.translatePoints([y[0] + t[0], y[1] + t[1]])
                        } else {
                            z = s.translatePoints(v.points.to)
                        }
                        l.left = z.left + "px";
                        l.top = z.top + "px";
                        break;
                    case "width":
                        l.width = v.width.to + "px";
                        break;
                    case "height":
                        l.height = v.height.to + "px";
                        break;
                    case "opacity":
                        l.opacity = String(v.opacity.to);
                        break;
                    default:
                        l[q] = String(v[q].to);
                        break
                }
            }
            var p = j(n, x);
            
            l.duration = r
            l.onEnd = p.proxyCallback,
            l.easing = i[u]
            $(Ext.id(m)).shift(l);
            return p
        }
    }
}();