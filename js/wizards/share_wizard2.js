/**
 * ShareWizard: Class for creating main wizard window.
 */
var ShareWizard = Class.create({
    /**
     * Create the share wizard window.
     */
    initialize: function(options){
        /**
         * The options that are send as a parameter is merged with the
         * default values of the object.
         * (options send with parameter will override the default options)
         */
        this.defaultOptions = {
            type: "share", // The type of the wizard.
            templateName: "wizards/shareWizard/index.html", // The template that will be loaded in wizard.
            cssFileNames: ["wizards/css/sharewiz.css", "sprite/sharewiz2.css"], // The name of the css files that will be loaded.
            wizardTitle: "Embed Form Wizard".locale() // The title of the wizard window.
        };
        this.options = Object.extend(this.defaultOptions, options || {});
        
        /**
         * The wizardWin object that will be initialized later is defined.
         */
        this.wizardWin = null; // The wizard window object
        this.loadCSS(); // Load the CSS files.
        /**
         * Initialize the share options manager.
         */
        this.shareOptionsManager = null;
        /**
         * Load the template that will be used in the wizard window,
         * and after loading the template open the wizard.
         */
        this.loadTemplate(this.openWizard.bind(this)); // Load template & open wizard on call back.
    },
    /**
     * Loads the css files defined in the options property of the object.
     * "this.options.cssFileNames" is defined at the initialize function as an array.
     */
    loadCSS: function(){
        this.options.cssFileNames.each(function(cssFile){
            Utils.loadCSS(cssFile);
        });
    },
    /**
     * Loads the template defined in the options property of the object.
     * "this.options.templateName" is defined at the initialize function.
     *
     * callbackFunc (function): call back function that will be triggered
     * after load is completed.
     */
    loadTemplate: function(callbackFunc){
        // load template.. on call back function openWizard
        Utils.loadTemplate(this.options.templateName, callbackFunc);
    },
    /**
     * Opens the wizard.
     */
    openWizard: function(source){
        /**
         * Wizard window options.
         */
        var wizardOptions = {
            title: this.options.wizardTitle,
            width: 430,
            contentPadding: 0,
            content: source,
            dynamic: true,
            onInsert: this.wizardOnInsert.bind(this), // function that will
            // work after wizard is
            // opened
            onClose: this.wizardOnClose.bind(this),
            buttons: [{
                title: 'Go Back',
                name: 'back',
                hidden: true,
                icon: 'images/back.png',
                // color:'green',
                handler: function(){
                    $('closeButton').run('click');
                }
            }, {
                title: "Close".locale(),
                name: "close",
                handler: function(window){
                    window.close();
                }
            }]
        };
        // launch the wizard
        this.wizardWin = document.window(wizardOptions);
    },
    /**
     * Runs after the wizard is closed.
     * Complete the stuff that must be done at close here.
     */
    wizardOnClose: function(){
        // call the close function of share options manager.
        this.shareOptionsManager.close();
    },
    /**
     * Runs when the wizard window is opened.
     */
    wizardOnInsert: function(wizardWin){
        // initialize custom option.
        var customUrl = new CustomUrl($("customUrlButton"), $("customUrl"), $("url"), $("customUrlError"), $("secureFormCheckbox"), $("customButton"), $("customUrlPop"));
        /**
         * Get the options of the share form wizard and the instruction area
         * by a css selector and initialize the ShareOptionManager.
         */
        // initialize parameters
        var shareOptions = $$('#shareOptions a.option');
        var shareOptionInstructions = $("shareOptionInstructions");
        // initialize ShareOptionManager
        this.shareOptionsManager = new ShareOptionManager(shareOptions, // share option elements
            shareOptionInstructions,  // instruction area element
            false,                    // automatically open the first option
            $("secureFormCheckbox"),  // the ssl chechbox that will trigger to generate code again.
            this
        );
        // set the listener of the options
        this.shareOptionsManager.setListeners();
        
        // Don't delete this. this is needed for xara
        $('mainSource').value = BuildSource.getCode({
            type: 'xara'
        });
        setTimeout(function(){
            wizardWin.reCenter();
        }, 1000);
    }
});

var ChangeOptionsClassName = Class.create({
    initialize: function(options){
        this.options = options;
        this.activeOption = null;
        $A(this.options).each(this.setListener.bind(this));
    },
    setListener: function(item, i){
        item.observe("mouseover", this.mouseOver.bind(this, item));
        item.observe("mouseout", this.mouseOut.bind(this, item));
    },
    mouseOver: function(item){
        if (item === this.activeOption) { return; }
        var images = item.select("img");
        if (images.length === 1) {
            this.removeBlackWhite(images[0]);
        }
    },
    mouseOut: function(item){
        if (item === this.activeOption) { return; }
        var images = item.select("img");
        if (images.length === 1) {
            this.addBlackWhite(images[0]);
        }
    },
    removeBlackWhite: function(image){
        image.className = image.className.replace(/_bw$/g, "" /*function(match, key, value) { return ""; }*/);
    },
    addBlackWhite: function(image){
        if (image.className.substring(image.className.length - 3, image.className.length) !==
        "_bw") {
            image.className += "_bw";
        }
    },
    setSelected: function(selected){
        if (this.activeOption !== null) {
            this.addBlackWhite(this.activeOption.select("img")[0]);
        }
        this.activeOption = selected;
        if (this.activeOption !== null) {
            this.removeBlackWhite(this.activeOption.select("img")[0]);
        }
    }
});

/**
 * Controls the options initialize and load.
 */
var ShareOptionManager = Class.create({
    initialize: function(options, instructionArea, openFirst, sslCheckbox, shareWizard){
        this.shareWizard = shareWizard;
        this.options = options; // the options
        this.instructionArea = instructionArea; // the area that instructions will be loaded
        this.activeOption = null; // the option that is opened.
        this.options.invoke("setUnselectable"); // make the options not selectable
        // the sslCheckbox (it will be used while updating the source).
        this.sslCheckbox = sslCheckbox;
        // if sslCheckbox is click update the source code.
        this.sslCheckbox.observe("click", this.updateSourceCode.bind(this));
        /**
         * The close button that will be appear when an option is opened.
         * switchOption is observed to the click event. If the clicked option and the
         * activeOption is same, switchOption closes the activeOption: this is the reason
         * that active option is send as a parameter.
         */
        this.closeButton = $("closeButton");
        this.closeButton.observe("click", this.switchOption.bind(this, this.activeOption));
        // initialize the slide div and scroll it to initial point.
        this.slideTable = $("slideDiv");
        this.slideTable.shift({
            scrollLeft: 0,
            duration: 0
        });
        
        /**
         * ChangeOptionsClassName controls the mouse hover effect of the options.
         * The logos of the options change from blackwhite to colored version.
         */
        this.changeOptionsClassName = new ChangeOptionsClassName(this.options);
        // if open first is send, open the first option.
        if (openFirst) {
            this.switchOption(this.options[0]); // select the first option.
        }
        
    },
    /**
     * The close function that will be triggered by the close function of the ShareWizard object.
     */
    close: function(){
    },
    /**
     * switchOption function is observed by the each function
     */
    setListeners: function(){
        for (var i = 0, l = this.options.length; i < l; i++) {
            var option = this.options[i];
            option.observe("click", this.switchOption.bind(this, option));
        }
    },
    /**
     * The function that will be triggered after an option is clicked.
     */
    switchOption: function(option, e){
        if (!option || this.activeOption === option) { // if the option is empty (this means openning a new option) OR
            // the option is itself (this means close the option activated before)
            // Hide go back button when it's not needed
            this.shareWizard.wizardWin.buttons.back.fade(); // Does this job with an animation
            // this.shareWizard.wizardWin.buttons.back.hide(); 
            
            // slide to the options view
            this.slideTable.shift({
                scrollLeft: 0,
                duration: 1,
                onEnd: this.clearOptionInstructions.bind(this) // the function that is trigger when none option is selected.
            });
        } else {
            // Make back button appear
            this.shareWizard.wizardWin.buttons.back.appear(); // Make it with animation
            // this.shareWizard.wizardWin.buttons.back.show();
            
            // load the html template and make the neccessary modifications by
            // calling loadTemplate after the template loads.
            Utils.loadTemplate("wizards/shareWizard/" + option.id +
            ".html", this.loadTemplate.bind(this, option));
            // slide to  the option instructions
            this.slideTable.shift({
                scrollLeft: 390,
                duration: 1
            });
        }
    },
    /**
     * Work when an option is deselected.
     */
    clearOptionInstructions: function(){
        this.instructionArea.update(); // clean teh instruction area
        this.activeOption = null; // make the active option null
        if (this.changeOptionsClassName !== null) {
            // remove the class name from the logo of the option.
            this.changeOptionsClassName.setSelected(this.activeOption);
        }
    },
    loadTemplate: function(option, source){
        if (this.activeOption !== null) {
            this.activeOption.removeClassName("active");
        }
        this.activeOption = option;
        this.activeOption.addClassName("active");
        this.instructionArea.update(source);
        Locale.changeHTMLStrings();
        // run the javascript code to fill the fields
        switch (this.activeOption.id) {
            case "default":
                this.fillDefaultTemplate();
            break;
            case "source":
                this.fillSourceTemplate();
            break;
            case "iframe":
                this.filliFrameTemplate();
            break;
            case "lightbox2":
                this.fillLightBox();
            break;
            case "popup":
                this.fillPopupBox();
            break;
            case "feedback2":
                this.fillFeedback();
            break;
            case "email":
                this.fillEmail();
            break;
            case "googlesites":
                this.fillGoogleSites();
            break;
        }
        this.updateSourceCode();
        // selected bw and normal image
        if (this.changeOptionsClassName !== null) {
            this.changeOptionsClassName.setSelected(this.activeOption);
        }
    },
    fillGoogleSites: function(){
        $('googleGadgetURL').observe("click", Utils.selectAll);
    },
    fillEmail: function(){
        // set the form
        $('fromField').value = getUserEmail();
        // set the htmleditor
        this.HTMLEditor = Editor.set('emailSource ', 'tiny');
        // set sending email button
        $('sendMessageButton').observe('click', Utils.sendEmail);
    },
    updateSourceCode: function(){
        if (!this.activeOption) { return; }
        var valueHolder = $(this.activeOption.id + "Source");
        if ($(this.activeOption.id + "Source")) {
            valueHolder.value = this.generateSourceCode();
            valueHolder.observe("click", Utils.selectAll);
        }
    },
    generateSourceCode: function(){
        return BuildSource.getCode({
            type: this.activeOption.id,
            isSSL: this.sslCheckbox.checked
        });
    },
    fillDefaultTemplate: function(){
        $("defaultDemo").observe("click", this.demo.bind(this, "Test Embed Code"));
    },
    demo: function(title){
        var code = this.generateSourceCode();
        Utils.Request({
            server: "wizards/shareWizard/test.php",
            asynchronous: false,
            parameters: {
                title: title,
                scriptSource: code
            },
            evalJSON: false,
            onComplete: function(res, responseText){
                myWindow = window.open();
                myWindow.document.open();
                myWindow.document.write(responseText);
                myWindow.document.close();
            }
        });
    },
    fillSourceTemplate: function(){
        $("zipCode").observe("click", BuildSource.getCode.bind(null, {
            type: "zip"
        }));
        $("sourceDemo").observe("click", this.demo.bind(this, "Test Source Code"));
    },
    filliFrameTemplate: function(){
        $("iframeDemo").observe("click", this.demo.bind(this, "Test iFrame Code"));
    },
    /**
     * lightbox functions
     */
    fillLightBox: function(){
        // Show hide customize buttons
        $('lightboxCustomize').setStyle({
            height: "0px"
        });
        $("lightboxCustomizeButton").observe("click", this.slideOptions.bind($('lightboxCustomize'), 245, $("lightboxCustomizeButton")));
        $("lightboxCustomizeButton").setUnselectable();
        // update colorboxes
        $("lightboxBackgroundColor").value = form.getProperty("lightboxBackgroundColor") ? form.getProperty("lightboxBackgroundColor") : "#FFA500";
        $('lightboxBackgroundColor').colorPicker2({
            trigger: $('lightboxBackgroundColorBox'),
            onPicked: this.updateColorField.bind(this, $('lightboxBackgroundColor'), this.updateLightBox.bind(this), $("lightboxBackgroundColorBox")),
            hideOnBlur: true,
            container: this.slideTable
        });
        $('lightboxBackgroundColorBox').setStyle({
            backgroundColor: $("lightboxBackgroundColor").value
        });
        
        $("lightboxFontColor").value = form.getProperty("lightboxFontColor") ? form.getProperty("lightboxFontColor") : "#FFFFFF";
        $("lightboxFontColor").colorPicker2({
            trigger: $("lightboxFontColorBox"),
            onPicked: this.updateColorField.bind(this, $('lightboxFontColor'), this.updateLightBox.bind(this), $("lightboxFontColorBox")),
            hideOnBlur: true
        });
        $('lightboxFontColorBox').setStyle({
            backgroundColor: $("lightboxFontColor").value
        });
        
        // update title
        $("lightboxTitle").value = form.getProperty("lightboxTitle") ? form.getProperty("lightboxTitle") : form.getProperty("title");
        // update window height
        $("lightboxHeight").value = form.getProperty("lightBoxHeight") ? form.getProperty("lightBoxHeight") : "500";
        // update window width
        $("lightboxWidth").value = form.getProperty("lightboxWidth") ? form.getProperty("lightboxWidth") : "700";
        // demo code
        $("lightboxDemo").observe("click", this.demo.bind(this, "Test LightBox"));
        $("lightboxDemo").setUnselectable();
        // update source
        [$("lightboxTitle"), $("lightboxHeight"), $("lightboxWidth"), $("lightboxFontColor"), $("lightboxBackgroundColor")].invoke("observe", "keyup", this.updateLightBox.bind(this));
        // style selector
        var styleSelector = new StyleSelector($$('#lightboxCustomize div.styleBrowser'), "lightboxStyle", form.getProperty("lightboxStyle"), this.updateLightBox.bind(this));
        this.updateLightBox();
    },
    updateColorField: function(field, upCodeFunc, colorBox, v){
        field.value = v;
        colorBox.setStyle({
            backgroundColor: v
        });
        upCodeFunc();
    },
    updateLightBox: function(){
        // update lightbox background color
        form.setProperty("lightboxBackgroundColor", $("lightboxBackgroundColor").value);
        form.setProperty("lightboxFontColor", $("lightboxFontColor").value);
        // update source
        form.setProperty("lightboxTitle", $("lightboxTitle").value);
        // update window height
        form.setProperty("lightboxHeight", $("lightboxHeight").value);
        // update window width
        form.setProperty("lightboxWidth", $("lightboxWidth").value);
        this.updateSourceCode();
    },
    /**
     * popup functions
     */
    fillPopupBox: function(){
        $("popupTestButton").observe("click", this.demo.bind(this, "Test Popup"));
    },
    /**
     * feedback functions
     */
    fillFeedback: function(){
        $('feedbackCustomize').setStyle({
            height: "0px"
        });
        // fill initial values and observe demo button action.
        // update colorboxes
        $("feedbackBackgroundColor").value = form.getProperty("feedbackBackgroundColor") ? form.getProperty("feedbackBackgroundColor") : "#F59202";
        $("feedbackBackgroundColor").colorPicker2({
            trigger: $("feedbackBackgroundColorBox"),
            onPicked: this.updateColorField.bind(this, $('feedbackBackgroundColor'), this.updateFeedback.bind(this), $("feedbackBackgroundColorBox")),
            hideOnBlur: true
        });
        $('feedbackBackgroundColorBox').setStyle({
            backgroundColor: $("feedbackBackgroundColor").value
        });
        
        $("feedbackFontColor").value = form.getProperty("feedbackFontColor") ? form.getProperty("feedbackFontColor") : "#FFFFFF";
        $("feedbackFontColor").colorPicker2({
            trigger: $("feedbackFontColorBox"),
            onPicked: this.updateColorField.bind(this, $('feedbackFontColor'), this.updateFeedback.bind(this), $("feedbackFontColorBox")),
            hideOnBlur: true
        });
        
        $('feedbackFontColorBox').setStyle({
            backgroundColor: $("feedbackFontColor").value
        });
        document.selectRadioOption($$('#shareWizard input[name="buttonSide"]'), form.getProperty("feedbackButtonSide") ? form.getProperty("feedbackButtonSide") : "bottom");
        document.selectRadioOption($$('#shareWizard input[name="buttonAlign"]'), form.getProperty("feedbackButtonAlign") ? form.getProperty("feedbackButtonAlign") : "right");
        this.setFeedbackAligns(form.getProperty("feedbackButtonSide") ? form.getProperty("feedbackButtonSide") : "top");
        $('feedbackButtonText').value = form.getProperty("feedbackButtonText") ? form.getProperty("feedbackButtonText") : form.getProperty("title");
        $('feedbackWidth').value = form.getProperty("feedbackWidth") ? form.getProperty("feedbackWidth") : "700";
        $('feedbackHeight').value = form.getProperty("feedbackHeight") ? form.getProperty("feedbackHeight") : "500";
        $("feedbackDemo").observe("click", this.demo.bind(this, "Test Feedback"));
        $("feedbackDemo").setUnselectable();
        $("feedbackCustomizeButton").observe("click", this.slideOptions.bind($('feedbackCustomize'), 370, $("feedbackCustomizeButton")));
        $("feedbackCustomizeButton").setUnselectable();
        [$('feedbackFontColor'), $('feedbackBackgroundColor'), $('feedbackButtonText'), $('feedbackWidth'), $('feedbackHeight')].invoke("observe", "keyup", this.updateFeedback.bind(this));
        $$('#shareWizard input[name="buttonSide"]').invoke("observe", "click", this.updateFeedback.bind(this));
        $$('#shareWizard input[name="buttonAlign"]').invoke("observe", "click", this.updateFeedback.bind(this));
        $$('#shareWizard input[name="buttonSide"]').invoke("observe", "click", this.setFeedbackAligns);
        // style selector
        var styleSelector = new StyleSelector($$('#feedbackCustomize div.styleBrowser'), "feedbackStyle", form.getProperty("feedbackStyle"), this.updateFeedback.bind(this));
        this.updateFeedback();
    },
    updateFeedback: function(){
        form.setProperty("feedbackBackgroundColor", $("feedbackBackgroundColor").value);
        form.setProperty("feedbackFontColor", $("feedbackFontColor").value);
        form.setProperty("feedbackButtonText", $("feedbackButtonText").value);
        form.setProperty("feedbackWidth", $("feedbackWidth").value);
        form.setProperty("feedbackHeight", $("feedbackHeight").value);
        form.setProperty("feedbackButtonAlign", document.readRadioOption($$('#shareWizard input[name="buttonAlign"]')));
        form.setProperty("feedbackButtonSide", document.readRadioOption($$('#shareWizard input[name="buttonSide"]')));
        this.updateSourceCode();
    },
    slideOptions: function(height, button){
        if (this.offsetHeight !== height) {
            Protoplus.utils.emile(this, 'height:' + height + 'px', {
                duration: 1500
            });
            button.update("Close Customize");
        } else {
            Protoplus.utils.emile(this, 'height:0px', {
                duration: 1500
            });
            button.update("Customize");
        }
    },
    setFeedbackAligns: function(value){
        value = typeof value === "object" ? this.value : value;
        if (value === "left" || value === "right") {
            $('alignmentLegend').update("Vertical Alignment");
            $$('.horizontalOpt').invoke("hide");
            $$('.verticalOpt').invoke("show");
        } else {
            $('alignmentLegend').update("Horizontal Alignment");
            $$('.horizontalOpt').invoke("show");
            $$('.verticalOpt').invoke("hide");
        }
    }
});

var StyleSelector = Class.create({
    initialize: function(options, storeValueKey, selectedValue, invokeFunction){
        this.options = options;
        this.storeValueKey = storeValueKey;
        this.selectedValue = selectedValue !== false ? selectedValue : 0;
        this.selectedOption = false;
        this.invokeFunction = invokeFunction;
        $A(this.options).each(this.setListener.bind(this));
    },
    setListener: function(option, i){
        option.observe("click", this.switchOption.bind(this, option, i));
        if (i === this.selectedValue) {
            this.switchOption(option, i);
        }
    },
    switchOption: function(option, selectedValue){
        if (this.selectedOption === option) { return; }
        if (this.selectedOption !== false) {
            this.selectedOption.removeClassName("selectedStyle");
        }
        this.selectedOption = option;
        this.selectedOption.addClassName("selectedStyle");
        form.setProperty(this.storeValueKey, selectedValue, this.invokeFunction());
        this.invokeFunction();
    }
});

var CustomUrl = Class.create({
    initialize: function(saveButton, slugField, urlField, errorDiv, sslCheckbox, customButton, customUrlPop){
        // initialize variables
        this.saveButton = saveButton;
        this.slugField = slugField;
        this.urlField = urlField;
        this.slugValue = form.getProperty('slug');
        this.errorDiv = errorDiv;
        this.formid = form.getProperty('id');
        // ssl checkbox element and event listener
        this.sslCheckbox = sslCheckbox;
        this.sslCheckbox.observe("click", this.setUrl.bind(this));
        // hide save buton
        this.saveButton.hide();
        this.saveButton.setUnselectable();
        // set the initial url and slug value
        this.setUrl(true);
        // update the link which is initially loaded
        this.setSlugFieldListener();
        this.urlField.observe("click", Utils.selectAll);
        this.saveButton.observe("click", this.saveSlug.bind(this));
        // variables and functions for opening custom button
        this.customButton = customButton;
        this.customUrlPop = customUrlPop;
        this.customButtonOpened = false;
        this.customUrlPop.hide();
        this.customButton.observe("click", this.openCloseCustomURL.bind(this));
        this.customButton.setUnselectable();
    },
    closeCustomURL: function(e){
        var element = Event.findElement(e, "#" + this.customUrlPop.id +
        ",#" +
        this.customButton.id);
        if (element !== this.customUrlPop &&
        element !== this.customButton) {
            this.openCloseCustomURL();
            this.customButtonStop.stop();
        }
    },
    openCloseCustomURL: function(){
        var buttonImage = this.customButton.select("img")[0];
        if (this.customButtonOpened) {
            this.customUrlPop.hide();
            this.customButton.removeClassName("active");
            buttonImage.removeClassName("sharewiz2-wrench_white");
            buttonImage.addClassName("sharewiz2-wrench");
            this.customButtonStop.stop();
        } else {
            this.customUrlPop.show();
            this.customButton.addClassName("active");
            buttonImage.removeClassName("sharewiz2-wrench");
            buttonImage.addClassName("sharewiz2-wrench_white");
            this.customButtonStop = Element.on(document, "click", this.closeCustomURL.bind(this));
        }
        this.customButtonOpened = !this.customButtonOpened;
    },
    saveSlug: function(){
        var slugValue = this.slugField.value;
        if (this.slugField.value.strip() !== "") {
            slugValue = form.getProperty('id');
        }
        Utils.Request({
            parameters: {
                action: 'saveSlug',
                id: form.getProperty('id'),
                slugName: this.slugField.value
            },
            onComplete: this.handleSlugSave.bind(this)
        });
    },
    handleSlugSave: function(res){
        if (res.success) {
            form.setProperty('slug', this.slugField.value);
            this.slugField.setStyle({
                border: "1px solid",
                borderColor: "#111111 #555555 #A0A0A0"
            });
            this.saveButton.update('Saved');
            this.slugValue = form.getProperty('slug');
        } else {
            this.slugField.setStyle({
                border: "1px solid red"
            });
        }
        this.saveButton.disabled = true;
    },
    setSlugFieldListener: function(){
        // custom-url initialize
        this.slugField.observe("keyup", this.updateUrlWithSlug.bind(this));
    },
    updateUrlWithSlug: function(){
        this.urlField.value = (Utils.HTTP_URL + Utils.user.username +
        "/" +
        this.slugField.value).replace(/\s+/gim, "_");
        // validate
        Utils.Request({
            parameters: {
                action: 'checkSlugAvailable',
                id: form.getProperty('id'),
                slugName: this.slugField.value
            },
            onComplete: this.handleSlugChange.bind(this)
        });
    },
    handleSlugChange: function(res){
        if (res.success && this.slugField.value) {
            this.slugField.setStyle({
                border: "1px solid",
                borderColor: "#111111 #555555 #A0A0A0"
            });
            this.saveButton.update("Save")
            this.saveButton.show();
            this.errorDiv.update();
        } else {
            if (this.slugField.value != this.slugValue) {
                var msg = "";
                if (!this.slugField.value) {
                    this.setUrl();
                    this.saveButton.update("Save");
                    this.saveButton.show();
                } else {
                    this.slugField.setStyle({
                        border: "1px solid red"
                    });
                    this.errorDiv.update("This name is in use.");
                    this.saveButton.hide();
                }
            }
        }
    },
    setUrl: function(initial){
        if ((this.slugValue.toString() !== this.formid.toString()) &&
        ((this.slugValue && initial === true) || this.slugField.value)) {
            if (!this.slugField.value) {
                this.slugField.value = this.slugValue;
            }
            var url = this.isSSL() ? Utils.SSL_URL : Utils.HTTP_URL;
            this.urlField.value = (url + Utils.user.username + "/" + this.slugField.value).replace(/\s+/gim, "_");
        } else {
            this.urlField.value = BuildSource.getCode({
                type: 'url',
                isSSL: this.isSSL()
            });
        }
    },
    isSSL: function(){
        return this.sslCheckbox.checked;
    }
});

new ShareWizard(Utils.useArgument);
