/**
 * Locale library. For managing translations inside the user interface.
 */

/**
 * Localization Function
 */
(function() {
	// RegEx to trim whitespaces around strings to be translated.
	Locale.trimRexp = /^\s+|\s+$/g;
	Locale.notTranslated = [];
	Locale.currentTranslation = {};
    
    function stretch(str, length){
       if(str.length > length){ return str; }
       var slength = str.length-1, diff = Math.ceil(length / slength), sum = 0, newWord = [], r;
       for(var x = 0; x < slength; x++){ r = rand(1, diff); sum += r; newWord[x] = r; }
       newWord[x] = (length - sum); stretched = "";
       for(var i=0; i < newWord.length; i++){ for(j=0; j < newWord[i]; j++){ stretched += str[i]; } }
       return stretched;
    }
    
	// String.locale() is the main function that translates a string. It is 
	// added to the prototype so that any String variable can access it.
	String.prototype.locale = function (){
	    var word = this;
	    if ('language' in Locale) {
            // Zombie converter
            // if(Locale.language['langCode'] == 'zb-ZB'){ return stretch("BRAINS!", word.length); } 
	        word = word.toString().replace(Locale.trimRexp, ''); // Trim String
	        if (word in Locale.language) { // Check language file
	            word = Locale.language[word];
	        }else{
	            if(!Locale.notTranslated.include(word)){
	                Locale.notTranslated.push(word);
	            }
	        }
	    }
        
	    if(arguments.length > 0){
	        return word.printf.apply(word, arguments);// Place the arguments
	    }else{
	        return word;
	    }
	};
	// Extend the English language with the custom language here.
	if ('language' in Locale) {
		Locale.language = Object.extend(Locale.languageEn, Locale.language);
	}
	else {
		// This is faster than extending :)
		Locale.language = Locale.languageEn;
	}
	
	/**
	 * Translates the hard coded strings in html templates
	 */
	Locale.changeHTMLStrings = function(){
	    $$('.locale').each(function(l){ l.removeClassName('locale');  l.innerHTML = l.innerHTML.locale(); });
	    $$('.locale-img').each(function(l){ l.removeClassName('locale-img'); if (l.alt) l.alt = l.alt.locale(); if (l.title) l.title = l.title.locale(); });
	    $$('.locale-button').each(function(l){ l.removeClassName('locale-button'); l.value = l.value.locale(); });
	    // Change document title as well.
	    
	    document.title = document.title && document.title.locale();
	}
	
	/**
	 * Translated languages list
	 */
	/*
	var languages = {
	    'de-DE': {
	        name:'German'.locale(),
	        flag:'images/flags/de.png'
	    },
	    'en-US': {
	        name:'English'.locale(),
	        flag:'images/flags/us.png'
	    },
	    'es-ES': {
	        name:'Spanish'.locale(),
	        flag:'images/flags/es.png'
	    },
		'fr-FR': {
	        name:'French'.locale(),
	        flag:'images/flags/fr.png'
	    },
		'it-IT': {
	        name:'Italian'.locale(),
	        flag:'images/flags/it.png'
	    },
		'pt-PT': {
	        name:'Portuguese'.locale(),
	        flag:'images/flags/pt.png'
	    }
	}
	*/
	
	/**
	 * Creates the dropdown values from languages list
	 */
	/*
	function langDropdown(){
	    return $H(languages).map(function(pair){return {text:pair.value.name, value:pair.key}});
	}
	*/
	
	// Since we moved the script at the bottom now we don't need document.ready anymore
	//document.ready(function(){
	    
	Locale.changeHTMLStrings(); // Translate hard coded values first
	$('language-box') && $('language-box').observe('change', function() {
        Utils.Request({
            parameters: {
                action:'setCookie',
                name: 'language',
                value: $('language-box').value,
                expire: '+1 Month'
            },
            onSuccess: function(res){
        		location.reload();
            }
        });
		//document.createCookie('language', $('language-box').value);
        
	});
	
		
	    // Convert language menu to the current language
	    /*
	    var lcode = languageCust.langCode;
	    $('language').update(languages[lcode].name);
	    $('flag').src = languages[lcode].flag;
	
	    // Make langugae manu an editable item
	    $('language').editable({type:'dropdown', options:langDropdown(), 
		
		onEnd:function(el, html, old, val){
	        $('flag').src = languages[val.value].flag;
	        document.createCookie('language', val.value);
	        setTimeout(function(){
	            location.reload();
	        }, 100);
	    }
		});
	    */
	    
	//})
}());