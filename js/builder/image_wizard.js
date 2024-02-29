/**
 * Creates the condition wizard and controls all of it's actions
 */
var imageWizard = {
    wiz: false,                     // Wizard window
    currentPage: 'page1',
    selectedType: false,
    qid: false,
    onClose: function(){
        this.currentPage = 'page1';
        this.selectedType = false;
        this.qid = false;
    },
    /**
     * Initiate the wizard, translate strings and set events
     */
    initWizard: function(){
        
        var $this = this;           // Object scope
        Locale.changeHTMLStrings(); // translate
        $('source-options').observe('click', function(e){
            if(e.target.name == 'source-type' && e.target.checked){
                $this.selectedType = e.target.id;
            }
        });
        
    },
    
    /**
     * 
     * @param {Object} w
     */
    nextPage: function(w){
        
        if(this.currentPage == 'page1'){
            $('source-error').update('&nbsp;');
            if(this.selectedType === false){
                $('source-error').update('Please select a type first');
                return false;
            }
            
            var h = 120, w = 450;
            
            if(this.selectedType == 'source-existing'){
                this.showLoading();
                h = 350; w = 450;
            }
            
            $('page2').update('<iframe allowtransparency="true" src="upload.php?qid='+this.qid+'&type='+this.selectedType+'" frameborder="0" style="width:'+w+'px; height:'+h+'px; border:none;" scrolling="auto"></iframe>');
            
            this.showButtons('backPage', 'finish');
            this.showPage('page2');
            this.wiz.setStyle('width:490px');
            this.wiz.reCenter();
        }
        
    },
    /**
     * 
     * @param {Object} w
     */
    backPage: function(w){
        this.showButtons('nextPage');
        this.showPage('page1');
        this.wiz.setStyle('width:350px');
        this.wiz.reCenter();
    },
    
    /**
     * 
     * @param {Object} w
     */
    finishWizard: function(w){
        
    },
    
    /**
     * 
     * @param {Object} buttonList
     */
    showButtons: function(){
        var buttonList = $A(arguments);
        $H(this.wiz.buttons).each(function(button){
            if(buttonList.include(button.key)){
                button.value.show();
            }else{
                button.value.hide();
            }
        });
    },
    /**
     * 
     * @param {Object} page
     */
    showPage: function(page){
        this.currentPage = page;
        $('page1', 'page2').invoke('hide');
        $(page).show();
        this.wiz.reCenter();
    },
    timer: false,
    showLoading: function(){
        $('wizard-loading').setOpacity(0.7);
        this.timer = setTimeout(function(){
            $('wizard-loading').show();
        }, 200);
    },
    hideLoading: function(){
        clearTimeout(this.timer);
        $('wizard-loading').hide();
    },
    /**
     * Create the wizard window
     */
    openWizard: function(qid){
        
        var $this = this;
        this.qid = qid; 
        Utils.loadTemplate('wizards/ImageWizard.html', function(source) {
            
            var div = new Element('div').insert(source);
            
            $this.wiz = document.window({
                title: 'Image Wizard'.locale(),
                width: 350,
                contentPadding: 0,
                content: div,
                dynamic: false,
                onInsert: $this.initWizard.bind($this),
                onClose: $this.onClose.bind($this),
                buttons: [{
                    title: 'Back',
                    name: 'backPage',
                    hidden: true,
                    handler: $this.backPage.bind($this)
                }, {
                    title: 'Next'.locale(),
                    name: 'nextPage',
                    handler: $this.nextPage.bind($this)
                }, {
                    title: 'Close'.locale(),
                    name: 'finish',
                    hidden: true,
                    handler: function(w){
                        w.close();
                    }
                }]
            });
            
        });
    }
};

// On load
imageWizard.openWizard(Utils.useArgument);