var ThemeSelector = {

    body: $$('body')[0],
    
    themes: [{
        title: 'Default',
        name: 'default',
        thumb: 'images/bg.png'
    },{
        title: 'Black',
        name:  'tile-black',
        thumb: 'images/themes/tile-black.jpg'
    }, {
        title: 'Brown',
        name:  'tile-brown',
        thumb: 'images/themes/tile-brown.jpg'
    }, {
        title: 'Dark Green',
        name:  'tile-darkgreen',
        thumb: 'images/themes/tile-darkgreen.jpg'
    }, {
        title: 'Dark Yellow',
        name:  'tile-darkyellow',
        thumb: 'images/themes/tile-darkyellow.jpg'
    }, {
        title: 'Navy Blue',
        name:  'tile-navyblue',
        thumb: 'images/themes/tile-navyblue.jpg'
    }, {
        title: 'Orange',
        name:  'tile-orange',
        thumb: 'images/themes/tile-orange.jpg'
    }, {
        title: 'Purple',
        name:  'tile-purple',
        thumb: 'images/themes/tile-purple.jpg'
    }, {
        title: 'Crimson',
        name:  'tile-red',
        thumb: 'images/themes/tile-red.jpg'
    }, {
        title: 'Teal',
        name:  'tile-turqoise',
        thumb: 'images/themes/tile-turqoise.jpg'
    }, {
        title: 'Yellow',
        name:  'tile-yellow',
        thumb: 'images/themes/tile-yellow.jpg'
    }, {
        title: 'Red Wall',
        name:  'redwall',
        thumb: 'images/themes/red-wall-thumb.jpg',
        info: {
            artist: 'themacguy2k',
            source: 'http://www.flickr.com/photos/themacguy2k/3280287208/in/set-72157613142821712/'
        }
    }, {
        title: 'Yellow Paint',
        name:  'yellow-wall',
        thumb: 'images/themes/yellow-paint-thumb.jpg',
        info: {
            artist: 'themacguy2k',
            source: 'http://www.flickr.com/photos/themacguy2k/3238434866/in/set-72157613142821712/'
        }
    }, {
    	title: 'Brick Wall',
        name:  'brickwall',
        thumb: 'images/themes/brickwall-thumb.jpg',
        info: {
	    	 artist: 'WebTreats',
	         source: 'http://webtreats.mysitemyway.com/'
        }
    }, {
    	title: 'V-Day',
        name:  'v-day',
        thumb: 'images/themes/v-day2-thumb.jpg',
        info: {
	    	 artist: 'WebTreats',
	         source: 'http://webtreats.mysitemyway.com/'
        }
    }, {
        title: 'Wood Planks',
        name:  'wood',
        thumb: 'images/themes/woodboards-thumb.jpg',
        info: {
            artist: 'Naomi Rubin',
            source: 'http://www.flickr.com/photos/naomiyaki/4583474627/'
        }
    }, {
    	 title: 'Wood Floor',
         name:  'woodfloor',
         thumb: 'images/themes/woodfloor-thumb.jpg',
         info: {
             artist: 'Henri Liriani',
             source: 'http://www.henriliriani.com/'
         }
     }, {
        
        title: 'Blur Lights',
        name:  'lights',
        thumb: 'images/themes/blur-lights-thumb.jpg',
        info: {
            artist: 'calebkimbrough',
            source: 'http://www.flickr.com/photos/calebkimbrough/3204910819/'
        }
    }, {
        title: 'Blur Lights 2',
        name:  'lights2',
        thumb: 'images/themes/blur-lights2-thumb.jpg',
        info: {
            artist: 'calebkimbrough',
            source: 'http://www.flickr.com/photos/calebkimbrough/3204911727/'
        }
    }, {
    	title: 'Polka Dots',
        name:  'polka',
        thumb: 'images/themes/polka-thumb.jpg',
        info: {
	    	 artist: 'WebTreats',
	         source: 'http://webtreats.mysitemyway.com/'
        }
    }, {
    	 title: 'Geometric',
         name:  'geometric',
         thumb: 'images/themes/geopat-thumb.jpg',
         info: {
	    	 artist: 'WebTreats',
	         source: 'http://webtreats.mysitemyway.com/'
         }
     }, {
        title: 'Vintage Wall',
        name:  'pattern',
        thumb: 'images/themes/vintage-thumb.jpg',
        info: {
            artist: 'calebkimbrough',
            source: 'http://www.flickr.com/photos/calebkimbrough/3204869371/in/set-72157612647618019/'
        }
    }, {
    	title: 'Black Mesh',
        name:  'mesh',
        thumb: 'images/themes/black-mesh.jpg',
        info: {
            artist: 'Jessica Timko',
            source: 'http://www.my1uplife.com/2008/11/free-seamless-tile-backgrounds-for-websites'
        }
    }, {
        title: 'Brown Leather',
        name:  'oldleader',
        thumb: 'images/themes/brown-leather-thumb.jpg',
        info: {
            artist: 'calebkimbrough',
            source: 'http://www.flickr.com/photos/calebkimbrough/3204863883/sizes/l/in/set-72157612647618019/'
        }
    }, {
        title: 'Black Leather',
        name:  'blackleather',
        thumb: 'images/themes/black-leather-thumb.jpg',
        info: {
            artist: 'WebTreats',
            source: 'http://webtreats.mysitemyway.com/'
        }
    }, {
    	title: 'Clouds',
        name:  'clouds',
        thumb: 'images/themes/clouds-thumb.jpg',
        info: {
            artist: 'WebTreats',
            source: 'http://webtreats.mysitemyway.com/'
        }
    }, {
        title: 'Fall',
        name:  'fall',
        thumb: 'images/themes/fall-thumb.jpg',
        info: {
            artist: 'calebkimbrough',
            source: 'http://www.flickr.com/photos/calebkimbrough/3205756464/in/set-72157612647618019/'
        }
    }, {
        title: 'Grass',
        name:  'grass',
        thumb: 'images/themes/grass-thumb.jpg',
        info: {
            artist: 'PS Interface',
            source: 'http://psinterface.com/green_texture.html'
        }
    }],
    /**
     * Create a wizard window for theme selection
     */
    openWizard: function(){        
        var $this = this;
        // Overall container for all themes
        var content = new Element('div').setStyle('padding:10px; margin-top:5px; margin-bottom:5px; display: inline-block; height: 277px; position:relative;');
        
        // Loop through all themes and place them in content
        $A(this.themes).each(function(t){
            // Individual theme container
            var cont = new Element('div', { className: 'theme-cont' });
            // Thumbnail of the theme
            var img  = new Element('div', { className: 'theme-img' }).setStyle({ background: "url(" + t.thumb + ")" });
            // Text container for theme
            var text = new Element('div', { className: 'theme-text' }).update(t.title);
            // If there is an information about a theme place an info icon
            var info = new Element('img', { className: 'theme-info', src: 'images/s-info.png' });
            // insert the element
            cont.insert(img).insert(text);
            // Only insert info, if there is an information about this theme
            if ('info' in t) { cont.insert(info); }
            
            // When a theme thumb is clicked
            cont.observe('click', function(e){
                // Get clicked element
                var el = Event.element(e);
                // if info item is clicked
                if (el.hasClassName('theme-info')) {
                    // If info was already clicked hide the information
                    if (cont.select('.theme-details')[0]) {
                        
                        cont.select('.theme-details')[0].remove();
                        
                    } else {
                        // If this is the first time info is clicked then create information box
                        var details = new Element('div', { className: 'theme-details' });
                        details.update('<b>Artist:</b> ' + t.info.artist + '<br><a href="' + t.info.source + '" target="_blank">Image Source</a>');
                        cont.insert(details);
                        // Place info box in the right place
                        details.setStyle({ top: 10 + "px", left: 0 + "px", zIndex: "1" });
                    }
                } else {
                    // If a theme is selected, remove previously selected item
                    $$('.theme-selected').invoke('removeClassName', 'theme-selected');
                    cont.addClassName('theme-selected'); // Make current selected
                    $this.body.className = t.name; // Switch theme for body element
                    // Send server a request and make this selection static
                    Utils.Request({
                        parameters: {
                            action: 'setTheme',
                            theme: t.name
                        }
                    });
                }
            });
            // Check body for current style and automatically select it in the list
            if ($this.body.hasClassName(t.name)) {
                cont.addClassName('theme-selected');
            }
            content.insert(cont);
        });
        content.insert('<br>');
        document.window({
            title: 'Themes',
            width: 390,
            content: content,
            contentPadding: 0,
            dimOpacity: 0,
            onInsert: function(){
                content.softScroll();
                /*var fadeTop = new Element('img', {src:'images/themes/theme-fade-top.png', className:'theme-fade-top'});
                var fadeBottom = new Element('img', {src:'images/themes/theme-fade-bottom.png', className:'theme-fade-bottom'});
                fadeTop.onmousemove = fadeTop.onmousedown = fadeBottom.onmousemove = fadeBottom.onmousedown = function(){ return false; }; // Disable browser drag on images
                $(content.parentNode).insert(fadeTop).insert(fadeBottom);*/
            },
            // buttonsAlign:'center',
            buttons: [{
                title: 'Close',
                name: 'close',
                color:'green',
                handler: function(w){
                    w.close();
                }
            }]
        });
    }
};
ThemeSelector.openWizard();






