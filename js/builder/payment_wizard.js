var openPaymentWizard;
(function(){
    // Globals
    var selectedButton = false;
    var currentPage = false;
    var lastType = false;
    var paymentWizard = false;
    var optionsWizard = false;
    var activeItem = false;
    var iCount = false;
    var currentOptionPage = 'page1';
    var paymentElem = false;
    var optionDirty = true;
    // Edit an option or add a new one.
    // var editOptionMode = false; 
    
    // Defaults
    var currency = 'USD';
    var activeType = 'subscription';
    var multiple = true;
    var showTotal = true;
    var donationText = 'Donation'.locale();
    var donationAmount = '';
    
    // Titles of the wizard pages
    var pagetitles = {
        page1: 'Payment Wizard'.locale() + ': ' + 'Account Information'.locale(),
        page2: 'Payment Wizard'.locale() + ': ' + 'Payment Options'.locale(),
        page3_product: 'Payment Wizard'.locale() + ': ' + 'Products'.locale(),
        page3_subscription: 'Payment Wizard'.locale() + ': ' + 'Subscriptions'.locale(),
        page4: 'Payment Wizard'.locale() + ': ' + 'Thank you'.locale()
    };
    
    var productOptions = {
        quantity: {
            type: "quantity",
            name: "Quantity".locale(),
            properties: "1\n2\n3\n4\n5\n6\n7\n8\n9\n10"
        },
        predefined: [{
            type: "predefined",
            name: "Color".locale(),
            properties: "Red\nBlue\nGreen\nYellow\nMagenta"
        }, {
            type: "predefined",
            name: "T-Shirt Size".locale(),
            properties: "XS\nS\nM\nL\nXL\nXXL\nXXXL"
        }, {
            type: "predefined",
            name: "Print Size".locale(),
            properties: "A4\nA3\nA2\nA1"
        }, {
            type: "predefined",
            name: "Screen Resolution".locale(),
            properties: "1024x768\n1152x864\n1280×768\n1280×800\n1280×960\n1280×1024\n1366×768\n1440×900\n1600×1200\n1680×1050\n1920×1080\n1920×1200"
        }, {
            type: "predefined",
            name: "Shoe Size".locale(),
            properties: "8\n8.5\n9\n\9.5\10\n10.5\n11\n11.5\n12\n13\n14"
        }]
        // {type: "custom", name: "", properties: ""}
    };
    
    /**
     * Back button of the wizard
     * Case represents the the page you click the Back button
     */
    function paymentBack(){
        switch (currentPage) {
            case "page2":
                setCurrentPage('page1');
                paymentWizard.buttons.back.disable();
            break;
            case "page3":
                setCurrentPage('page2');
                $('product-error').hide();
                paymentWizard.buttons.addNew.hide();
            break;
            case "page4":
                if (paymentType == "control_clickbank" || paymentType == "control_onebip") {
                    setCurrentPage('page1');
                    paymentWizard.buttons.back.disable();
                } else if (activeType == 'donation') {
                    setCurrentPage('page2');
                } else {
                    setCurrentPage('page3');
                }
                paymentWizard.buttons.finish.hide();
                paymentWizard.buttons.next.show();
            break;
        }
    }
    /**
     * Next button of the wizard
     * Case represents the the page you click the Next button
     */
    function paymentNext(){
        switch (currentPage) {
            case "page1":
                var elem = getElementById(paymentElem);
                
                payment_props.each(function(p){
                    var k = p[0];
                    var v = p[1];
                    
                    if (v.validation) {
                    // Check validations here
                    }
                });
                // Set currency for this payment
                currency = $('payment_currency') ? $('payment_currency').getSelected().value : "USD";
                
                
                if (paymentType == "control_clickbank" || paymentType == "control_onebip") {
                    setCurrentPage('page4');
                    paymentWizard.buttons.finish.show();
                    paymentWizard.buttons.next.hide();
                    paymentWizard.buttons.back.enable();
                } else {
                    setCurrentPage('page2');
                    paymentWizard.buttons.back.enable();
                }
                
            break;
            case "page2":
                
                $('donation-text').setStyle({
                    border: ''
                });
                $('options-error').update();
                
                if (activeType == "donation") {
                
                    if (!$('donation-text').value) {
                        $('donation-text').setStyle({
                            border: '2px solid red'
                        });
                        $('options-error').update('Please enter a donation text.'.locale());
                        return false;
                    }
                    paymentWizard.buttons.finish.show();
                    paymentWizard.buttons.next.hide();
                    setCurrentPage('page4');
                } else {
                
                    if (activeType != lastType) {
                    
                        $$('#product-list .listed-product').invoke('remove');
                        if ($('pro-info').hidden) {
                            $('pro-info').show().hidden = false;
                        }
                        
                        $('product-menu').shift({
                            height: 0,
                            opacity: 0,
                            duration: 0.5
                        });
                        
                        clearProductForm();
                    }
                    paymentWizard.buttons.addNew.show();
                    
                    setCurrentPage('page3');
                }
            break;
            case "page3":
                
                if ($$('#product-list .listed-product').length < 1) {
                    if (activeType == 'product') {
                        $('product-error').update('You should at least add one product.'.locale()).show();
                    } else {
                        $('product-error').update('You should at least add one subscription.'.locale()).show();
                    }
                    return false;
                }
                
                setCurrentPage('page4');
                paymentWizard.buttons.finish.show();
                paymentWizard.buttons.next.hide();
                
            break;
        }
    }
    /**
     * Open and set the title of the requested page
     * @param {Object} id
     */
    function setCurrentPage(id){
        if (currentPage) {
            $(currentPage).hide();
        }
        $(id).show();
        currentPage = id;
        if (id == 'page3') {
            id += '_' + activeType;
        }
        paymentWizard.setTitle(pagetitles[id]);
    }
    /**
     * When user selects an option from the options page, change the payment type
     * payment texts according to the type and set the differences between payments
     * @param {Object} type
     */
    function setActiveType(type){
        $('donation-text').setStyle({
            border: ''
        });
        $('options-error').update();
        var elem = getElementById(paymentElem);
        
        if (activeType) {
            $(activeType + "-settings").hide();
        }
        
        $(type + "-settings").show();
        $(type).checked = true;
        
        
        $('recurring-item-allperiods', 'recurring-period-box', 'product-image-box', 'product-2coid-box').invoke('hide');
        
        if (type == "subscription") {
            $('add-text').update('Add New Subscription'.locale());
            paymentWizard.buttons.addNew.changeTitle('Add New Subscription'.locale());
            $('detail-text').update('Subscription Details'.locale());
            paymentWizard.buttons.save.changeTitle('Save Subscription'.locale());
            $('recurring-item-allperiods', 'recurring-period-box').invoke('show');
            if (paymentType == "control_2co") {
                $('product-2coid-box').show();
            }
            if (multiple) {
                $('subscription-multiple').checked = true;
                $('subscription-total-container').show();
            } else {
                $('subscription-single').checked = true;
                $('subscription-total-container').hide();
            }
            $('subscription-total').checked = showTotal;
        }
        
        if (type == "product") {
            $('add-text').update('Add New Product'.locale());
            $('product-image-box').show();
            paymentWizard.buttons.addNew.changeTitle('Add New Product'.locale());
            $('detail-text').update('Product Details'.locale());
            paymentWizard.buttons.save.changeTitle('Save Product'.locale());
            if (multiple) {
                $('product-multiple').checked = true;
                $('product-total-container').show();
            } else {
                $('product-single').checked = true;
                $('product-total-container').hide();
            }
            $('product-total').checked = showTotal;
        }
        
        if (type == "donation") {
            $('donation-text').value = donationText;
            $('donation-amount').value = donationAmount;
        }
        
        activeType = type;
    }
    
    var paymentType = "";
    
    // This function is global
    window.openPaymentWizard = function (id){
    
        var elem;
        
        /*if(selected){
         elem = selected.elem;//getElementById(id);
         }else{
         elem = pselected.elem;
         }
         id = elem.getProperty('qid');*/
        elem = getElementById(id);
        paymentElem = id;
        
        // Read the template file
        Utils.loadTemplate('wizards/paymentWizards.html', function(source){
        
            // Create wizard window with the template
            paymentWizard = document.window({
                title: 'Payment Wizard'.locale + ': ' + 'Account Information'.locale(),
                width: '800',
                content: source,
                contentPadding: 0,
                resizable: false,
                onInsert: function(w){
                    paymentWizard = w; // Set window to global
                    setCurrentPage('page1');
                    
                    setPaymentDefaults(elem);
                    
                    initPaymentWizard(elem);
                    
                    setActiveType(activeType);
                    
                    $('product-menu').setOpacity(0);
                    Locale.changeHTMLStrings();
                    
                    paymentType = elem.getProperty('type');
                    
                    var typeName = paymentType.split("_")[1];
                    
                    $(typeName + "_text") && $(typeName + "_text").show();
                    $(typeName + "_test") && $(typeName + "_test").show();
                    $(typeName + "_complete") && $(typeName + "_complete").show();
                    
                    if (paymentType == "control_authnet") {
                        $('product-trial-period').update();
                        $('product-trial-period').insert(new Element('option', {
                            value: 'None'
                        }).insert('None'.locale()));
                        $('product-trial-period').insert(new Element('option', {
                            value: 'Enabled'
                        }).insert('Enabled'.locale()));
                    } else if (paymentType == "control_googleco") {
                        $('product-trial-period').update();
                        $('product-trial-period').insert(new Element('option', {
                            value: 'None'
                        }).insert('None'.locale()));
                        $('product-trial-period').insert(new Element('option', {
                            value: 'Enabled'
                        }).insert('Enabled'.locale()));
                        $('product-trial-period').observe('change', function(){
                            if ($('product-trial-period').getSelected().value == "Enabled") {
                                $('product-setupfee').value = "0";
                            }
                        });
                        $('product-recurring-period').selectOption('Bi-Weekly').getSelected().remove();
                        $('product-recurring-period').selectOption('Semi-Yearly').getSelected().remove();
                        $('product-recurring-period').selectOption('Bi-Yearly').getSelected().remove();
                    }
                    
                    $('auth_test').observe('click', function(){
                        Utils.Request({
                            parameters: {
                                action: 'testAuthnetIntegration',
                                loginId: $('payment_apiLoginId').value,
                                paymentType: 'product',
                                transactionKey: $('payment_transactionKey').value
                            },
                            onSuccess: function(response){
                                Utils.alert('<img src="images/success.png" /> <h3>' + response.message + "</h3>");
                            },
                            onFail: function(response){
                                Utils.alert('<img align="left" src="images/warning.png" /> ' + response.error);
                            }
                        });
                    });
                    
                    $('paypalpro_test').observe('click', function(){
                        Utils.Request({
                            parameters: {
                                action: 'testPayPalProIntegration',
                                apiUsername: $('payment_username').value,
                                apiPassword: $('payment_password').value,
                                signature: $('payment_signature').value
                            },
                            onSuccess: function(response){
                                Utils.alert('<img src="images/success.png" /> <h3>' + response.message + "</h3>");
                            },
                            onFail: function(response){
                                var mess = response.error.split("::");
                                console.log(mess);
                                Utils.alert('<img align="left" src="images/warning.png" /> ' + mess[0] + '<br><br>', mess[1]);
                            }
                        });
                    });
                },
                // Clean the globals after user closes the wizard
                onClose: function(){
                    if ($('productOptions')) {
                        $('productOptions').remove();
                    }
                    iCount = false;
                },
                buttons: [{
                    title: 'Add New Subscription'.locale(),
                    name: 'addNew',
                    hidden: true,
                    icon: 'images/add.png',
                    align: 'left',
                    handler: addNewProduct
                }, {
                    title: 'Back'.locale(),
                    icon: 'images/back.png',
                    iconAlign: 'left',
                    disabled: true,
                    name: 'back',
                    handler: paymentBack
                }, {
                    title: 'Next'.locale(),
                    name: 'next',
                    icon: 'images/next.png',
                    iconAlign: 'right',
                    handler: paymentNext
                }, {
                    title: 'Finish'.locale(),
                    name: 'finish',
                    icon: 'images/tick.png',
                    iconAlign: 'right',
                    hidden: true,
                    handler: completeWizard
                }, {
                    title: 'Cancel'.locale(),
                    name: 'cancel',
                    hidden: true,
                    link: true,
                    //icon:'images/cancel.png',
                    align: 'right',
                    handler: function(){
                    } // Will be set dynamically
                }, {
                    title: 'Save Subscription'.locale(),
                    name: 'save',
                    hidden: true,
                    icon: 'images/saveItem.png',
                    align: 'right',
                    handler: function(){
                    } // Will be set dynamically
                }]
            });
        });
    }
    
    function completeWizard(){
        // Save All Changes
        var elem = getElementById(paymentElem);
        
        $$('#payment_defaults select, #payment_defaults input').each(function(input){
            var value = false;
            if (input.tagName == 'SELECT') {
                value = input.getSelected().value;
            } else {
                value = input.value;
            }
            var key = input.id.replace('payment_', '');
            elem.setProperty(key, value);
        });
        
        elem.setProperty('paymentType', activeType);
        
        if (activeType != "donation") {
            elem.setProperty('multiple', $(activeType + '-multiple').checked ? 'Yes' : 'No');
            elem.setProperty('showTotal', $(activeType + '-total').checked && multiple === true ? 'Yes' : 'No');
        }
        
        elem.setProperty('donationText', $('donation-text').value);
        elem.setProperty('suggestedDonation', $('donation-amount').value);
        
        var pros = [];
        if ($$('#product-list .listed-product').length < 1) {
            if (paymentType == "control_clickbank" || paymentType == "control_onebip") {
                pros = [{
                    pid: 1,
                    name: $('payment_productName').value,
                    price: $('payment_productPrice').value,
                    period: false,
                    setupfee: false,
                    trial: false,
                    icon: false,
                    options: [],
                    hasQuantity: false
                }];
            }
        } else {
            $$('#product-list .listed-product').each(function(product){
                pros.push(product.retrieve('settings'));
            });
        }
        
        
        form.setProperty('products', pros);
        
        onChange('Payment Wizard completed');
        renewElement(elem, elem.getReference('container'));
        // Save Products
        paymentWizard.close();
    }
    
    
    /**
     * Reset the product entry form
     */
    function clearProductForm(){
        $('product-name').value = '';
        $('product-2coid').value = '';
        $('product-price').value = '';
        $('product-recurring-period').selectOption('Monthly');
        $('product-setupfee').value = '';
        $('product-trial-period').selectOption('None');
        $('product-image').value = '';
    }
    
    function setPaymentDefaults(elem){
        activeType = elem.getProperty('paymentType');
        lastType = activeType;
        multiple = (elem.getProperty('multiple') == 'Yes'); // true or false
        showTotal = (elem.getProperty('showTotal') == 'Yes'); // true or false
        donationText = elem.getProperty('donationText');
        donationamount = elem.getProperty('donationAmount');
        
    }
    var payment_props = false;
    
    function initPaymentWizard(elem){
        try {
            elem = $(elem);
            // When user hit enter in the product form save the details
            $('product-menu').observe('keyup', function(e){
                e = document.getEvent(e);
                if (e.keyCode == 13) {
                    paymentWizard.buttons.save.click();
                }
            });
            
            // Functionality of the payment type selection page
            $A(['product', 'subscription', 'donation']).each(function(type){
                $(type).observe('click', function(){
                    setActiveType(type);
                    if(type == 'subscription'){ $('subscription-single').checked = true; }
                });
                $(type + "-settings").hide();
                if ($(type + '-single')) {
                    $(type + '-single').observe('click', function(){
                        $(type + '-total-container').hide();
                    });
                    $(type + '-multiple').observe('click', function(){
                        $(type + '-total-container').show();
                    });
                }
            });
            
            // Remove common properties and leave only gateway specific items
            var rm_keys = $H(default_payments_properties).keys().concat(['order', 'qid', 'type', 'getItem', 'name', 'bridge', 'sublabels']);
            payment_props = $H(elem.retrieve('properties')).reject(function(p){
                return rm_keys.include(p.key);
            });
            
            
            payment_props.each(function(p){
                var k = p[0];
                var v = p[1];
                
                var div = new Element('div');
                div.insert(new Element('label').insert(v.text));
                
                if (v.dropdown) {
                    var sel = new Element('select', {
                        id: 'payment_' + k
                    });
                    $A(v.dropdown).each(function(o){
                        sel.appendChild(new Element('option', {
                            value: o[0]
                        }).insert(o[1]));
                    });
                    sel.selectOption(v.value);
                    div.insert(sel);
                } else {
                    div.insert(new Element('input', {
                        id: 'payment_' + k,
                        value: v.value,
                        size: v.size || '20'
                    }));
                }
                $('payment_defaults').insert(div);
            });
            
            $A(recurring_periods).each(function(o){
                $('product-recurring-period').insert(new Element('option', {
                    value: o[0]
                }).insert(o[1]));
            });
            $('product-recurring-period').selectOption('Monthly');
            
            $A(trial_periods).each(function(o){
                $('product-trial-period').insert(new Element('option', {
                    value: o[0]
                }).insert(o[1]));
            });
            $('product-trial-period').selectOption('None');
            
            $('add-product').observe('click', addNewProduct);
            
            if (form.getProperty('products').length > 0) {
                $A(form.getProperty('products')).each(function(product){
                    addNewProduct(product, 'old');
                });
            }
            
        } catch (e) {
            console.error(e);
        }
    }
    
    /**
     * Add a new prduct into the list
     */
    function addNewProduct(settings, addOldProduct){
    
        lastType = activeType;
        clearProductForm();
        if (!$('pro-info').hidden) {
            $('pro-info').hide().hidden = true;
            $('product-error').hide();
        }
        
        if (addOldProduct != 'old') {
            paymentWizard.buttons.next.setOpacity(0).disable();
            paymentWizard.buttons.back.setOpacity(0).disable();
            paymentWizard.buttons.addNew.hide();
            paymentWizard.buttons.save.show();
            //paymentWizard.buttons.cancel.show();
            $('product-menu').shift({
                height: 133,
                opacity: 1,
                duration: 0.5
            }).opened = true;
        }
        
        var productLine = new Element('li', {
            className: 'listed-product'
        });
        var optionIcon = new Element('img', {
            src: 'images/block.png',
            align: 'left'
        }).hide().setStyle('margin-right:3px;');
        var productName = new Element('span');
        if (iCount === false) {
            iCount = $$('#product-list .listed-product').length;
        }
        iCount++;
        
        var subsName = "New Subscription".locale() + " " + iCount;
        var proName = "New Product".locale() + " " + iCount;
        var randPrice = '';//rand(2, 10)+'.'+rand(55, 99);
        if (addOldProduct != 'old') {
            if (activeType == "subscription") {
                productName.insert(BuildSource.makeProductText(subsName, randPrice, currency, 'Monthly', 0, 'None'));
                $('product-name').value = subsName;
                $('product-price').value = randPrice;
                $('product-setupfee').value = '0';
                productLine.store('settings', {
                    name: subsName,
                    price: randPrice,
                    period: 'Monthly',
                    setupfee: 0,
                    trial: 'None',
                    icon: ''
                });
            } else {
                productName.insert(BuildSource.makeProductText(proName, randPrice, currency, false, false, false));
                $('product-name').value = proName;
                $('product-price').value = randPrice;
                productLine.store('settings', {
                    name: proName,
                    price: randPrice,
                    period: false,
                    setupfee: false,
                    trial: false,
                    icon: ''
                });
            }
        } else {
            if (activeType == "subscription") {
                productName.insert(BuildSource.makeProductText(settings.name, settings.price, settings.currency, settings.period, settings.setupfee, settings.trial));
            } else {
                productName.insert(BuildSource.makeProductText(settings.name, settings.price, settings.currency, false, false, false));
            }
            $('product-name').value = settings.name;
            $('product-price').value = settings.price;
            $('product-setupfee').value = settings.setupfee;
            
            productLine.store('settings', settings);
        }
        
        $('product-name').select();
        
        //var productDelete = new Element('img', {src: 'images/cross.png', align: 'right'}).setStyle('cursor: pointer');
        var productAddOption = new Element('button', {
            className: 'big-button'
        }).insert('<img src="images/add.png" align="absmiddle" /> ' + 'Add New Option'.locale() + '&nbsp;').setStyle('float: right; padding: 0');
        var productDelete = new Element('button', {
            className: 'big-button'
        }).insert('<img src="images/blank.gif" class="index-cross" align="absmiddle" /> ' + 'Delete Product'.locale() + '&nbsp;').setStyle('float: right; padding: 0');
        var div = new Element('div').setStyle('padding:5px');
        var buttonContainer = new Element('div').setStyle('float: right').hide();
        productLine.buttons = buttonContainer;
        productLine.insert(div);
        div.insert(optionIcon);
        div.insert(productName);
        
        buttonContainer.insert(productDelete);
        if (activeType != "subscription") {
            buttonContainer.insert(productAddOption);
        }
        div.insert(buttonContainer);
        
        if (addOldProduct != 'old') {
            if (activeItem) {
                activeItem.buttons.hide();
                activeItem.select('li').invoke('hide');
                activeItem.setStyle({
                    background: '#F5F5F5',
                    border: '1px solid #CCC'
                });
            }
            activeItem = productLine.setStyle({
                background: '#DDD',
                border: '1px solid #666'
            });
            productLine.select('li').invoke('show');
            productLine.buttons.show();
        }
        
        productLine.hover(function(){
            buttonContainer.show();
        }, function(){
            if (productLine == activeItem) { return; }
            buttonContainer.hide();
        });
        /**
         * Update the options list of an item
         */
        var refreshOptionsLine = function(){
            var opts = productLine.retrieve('settings').options;
            productLine.select('li').invoke('remove');
            // Need to remember the index for later updating.
            var optionIndex = 0;
            $A(opts).each(function(o){
                var d = new Element('li').setStyle('margin:3px; margin-left:10px; padding:3px; width:97%');
                d.update(o.name + '  <span style="color:#999">' + o.properties.replace(/\n/gim, ', ').shorten(100) + '</span>');
                var edit = new Element('img', {
                    src: 'images/pencil.png',
                    align: 'right',
                    style: 'cursor: pointer'
                });
                var del = new Element('img', {
                    src: 'images/blank.gif',
                    className: 'index-cross',
                    align: 'right',
                    style: 'cursor: pointer'
                });
                
                edit.observe('click', (function(optionIndex, o){
                    return function(){
                        $('optionsPage1').hide();
                        $('optionsPage2').show();
                        var insertCallback = function(){
                            $('option-name').value = o.name;
                            $('option-items').value = o.properties;
                        };
                        // On page 2 now.
                        currentOptionPage = 'page2';
                        var nextHandler = function(w){
                            if (checkEmptyOption()) { return; }
                            var settings = productLine.retrieve('settings');
                            var oldOptions = settings.options;
                            oldOptions[optionIndex].name = $('option-name').value;
                            oldOptions[optionIndex].properties = $('option-items').value;
                            productLine.store('settings', settings);
                            refreshOptionsLine();
                            w.close();
                        };
                        options = {
                            title: 'Edit Option'.locale(),
                            insertCallback: insertCallback,
                            pageNumber: 2,
                            optionMode: 'edit',
                            nextHandler: nextHandler
                        };
                        optionsWizard = createProductOptionsWin(options);
                    };
                })(optionIndex, o));
                
                del.observe('click', (function(optionIndex){
                    return function(){
                        // Remove from the datastore.
                        var settings = productLine.retrieve('settings');
                        // Delete the element.
                        var deletedOption = settings.options.splice(optionIndex, 1);
                        // See if it was a quantity type
                        if (deletedOption[0].type == "quantity") {
                            // This one is for saving.
                            settings.hasQuantity = false;
                            // This is for current runtime.
                            productLine.hasQuantity = false;
                        }
                        productLine.store('settings', settings);
                        refreshOptionsLine();
                    };
                })(optionIndex, d));
                d.insert(del);
                d.insert(edit);
                
                productLine.insert(d);
                optionIndex++;
            });
            optionIcon.show();
        };
        
        if (addOldProduct == 'old' && settings.options && settings.options.length > 0) {
            refreshOptionsLine(); // Add Default Options
            productLine.select('li').invoke('hide');
        }
        
        /*
         * Returns the option type that has been selected.
         */
        function getSelectedOptionType(){
            if ($('option-quantity').checked) { return "quantity"; }
            else if ($('option-custom').checked) { return "custom"; }
            else { /* if ($('option-predefined').checked)*/ return "predefined"; }
        }
        
        function resetOptionsPage2(){
            $('option-name-div').setStyle({
                display: ''
            });
            $('option-select-div').hide();
        }
        
        function resetOptionsButtons(){
            currentOptionPage = 'page1';
            optionsWizard.buttons.next.changeTitle('Next'.locale());
            optionsWizard.buttons.back.disable();
            optionDirty = true;
        }
        
        function checkEmptyOption(){
            // Remove any previous error messages.
            $('option-name').setStyle({
                border: ""
            });
            $('page2-error').update("");
            $('option-items').setStyle({
                border: ""
            });
            $('page2-error').update("");
            if ($('option-name').value.strip() === "") {
                $('option-name').setStyle({
                    border: "2px solid red"
                });
                $('page2-error').update("Please enter an option name.".locale());
                $('option-name').focus();
                return true;
            }
            
            if ($('option-items').value.strip() === "") {
                $('option-items').setStyle({
                    border: "2px solid red"
                });
                $('page2-error').update("Please enter various properties for this option.".locale());
                $('option-items').focus();
                return true;
            }
            return false;
        }
        
        /**
         * Product options function
         */
        function optionPageNext(w){
            var selectedOptionType = getSelectedOptionType();
            switch (currentOptionPage) {
                case 'page2':
                    if (checkEmptyOption()) { return; }
                    oldOptions = productLine.retrieve('settings');
                    
                    var optionSettings = {
                        type: (selectedOptionType == 'quantity') ? 'quantity' : 'custom',
                        name: (selectedOptionType == 'predefined') ? $('option-select').value : $('option-name').value,
                        properties: $('option-items').value
                    };
                    
                    if (optionSettings.type == "quantity") {
                        productLine.hasQuantity = true;
                    }
                    
                    if (!oldOptions.options) {
                        oldOptions.options = [];
                    }
                    if (selectedOptionType == 'quantity') {
                        oldOptions.hasQuantity = true;
                    }
                    oldOptions.options.push(optionSettings);
                    productLine.store('settings', oldOptions);
                    refreshOptionsLine();
                    // Reset some settings.
                    resetOptionsPage2();
                    resetOptionsButtons();
                    w.close();
                break;
                    
                // case 'page1':
                default:
                    // Reset page2 if a different option type is checked.
                    if (optionDirty === true || optionDirty != selectedOptionType) {
                        resetOptionsPage2();
                        // Populate fields again since a new option type is selected.
                        if ($('option-quantity').checked) {
                            $('option-name').value = productOptions.quantity.name;
                            $('option-items').value = productOptions.quantity.properties;
                        } else if ($('option-custom').checked) {
                            $('option-name').value = "";
                            $('option-items').value = "";
                        } else { // if($('option-predefined').checked)
                            // Reset the select first to clear any previous options.
                            $('option-select').innerHTML = "";
                            $A(productOptions.predefined).each(function(option){
                                // Skip the empty custom option.
                                if (option.name === "") { return; }
                                $('option-select').insert(new Element('option', {
                                    value: option.name
                                }).insert(option.name));
                            });
                            $('option-select').observe('change', function(c){
                                $('option-name').value = $('option-select').value;
                                $('option-items').value = productOptions.predefined[c.target.selectedIndex].properties;
                            });
                            $('option-name-div').setStyle({
                                display: 'none'
                            });
                            $('option-select-div').setStyle({
                                display: ''
                            });
                            // Default values.
                            $('option-name').value = $('option-select').value;
                            $('option-items').value = productOptions.predefined[$('option-select').selectedIndex].properties;
                        }
                    }
                    optionsWizard.buttons.next.changeTitle('Finish');
                    optionsWizard.buttons.back.enable();
                    $('optionsPage1').hide();
                    $('optionsPage2').show();
                    currentOptionPage = 'page2';
                    break;
            }
        }
        
        function optionPageBack(){
            switch (currentOptionPage) {
                case 'page2':
                    $('optionsPage1').show();
                    $('optionsPage2').hide();
                    currentOptionPage = 'page1';
                    optionsWizard.buttons.next.changeTitle('Next');
                    optionsWizard.buttons.back.disable();
                    optionDirty = getSelectedOptionType();
                break;
            }
        }
        
        
        var createProductOptionsWin = function(vars){
            // options holds default variables.
            options = {
                title: 'Add New Option',
                pageNumber: 1,
                optionMode: 'new'
            };
            // vars must have insertCallback defined for this to work.
            Object.extend(options, vars ||
            {});
            
            // Show the div first.
            $('productOptions').show();
            return document.window({
                title: options.title.locale(),
                content: $('productOptions'),
                onInsert: options.insertCallback,
                onClose: function(){
                    $('productOptions').hide();
                    $(document.body).insert($('productOptions'));
                    // Show optionsPage1 again in case page2 is shown after a click.
                    if (Element.getStyle($('optionsPage1'), 'display') == 'none') {
                        $('optionsPage2').hide();
                        $('optionsPage1').show();
                        $('option-name').value = '';
                        $('option-name').enable();
                    }
                },
                buttons: [{
                    title: 'Back'.locale(),
                    icon: 'images/back.png',
                    iconAlign: 'left',
                    disabled: (options.pageNumber == 1 || options.optionMode == 'edit'),
                    name: 'back',
                    handler: optionPageBack
                }, {
                    title: (options.pageNumber != 2) ? 'Next'.locale() : 'Finish'.locale(),
                    name: 'next',
                    icon: 'images/next.png',
                    iconAlign: 'right',
                    handler: (options.optionMode == 'new') ? optionPageNext : options.nextHandler
                }]
            });
        };
        
        productAddOption.observe('click', function(){
            var insertCallback = function(w){
                if (productLine.retrieve('settings').hasQuantity || productLine.hasQuantity) {
                    $('qty-label').setStyle('text-decoration:line-through;');
                    $('option-quantity').disable();
                    $('option-custom').checked = true;
                } else {
                    // Select the quantity option by default.
                    $('option-quantity').checked = true;
                    $('qty-label').setStyle({
                        textDecoration: ''
                    });
                    $('option-quantity').enable();
                    $('option-quantity').checked = true;
                }
            };
            currentOptionPage = 'page1';
            // Open the lisste options wizard
            optionsWizard = createProductOptionsWin({
                title: 'Add New Option'.locale(),
                insertCallback: insertCallback
            });
        });
        
        var saveItem = function(){
            $('product-error2').update();
            $('product-price', 'product-name').invoke('removeClassName', 'error');
            if (!$('product-price').value.match(/^(\d+[\.\,]?)+$/g)) {
                $('product-price').addClassName('error');
                $('product-error2').update('Only numeric values are accepted'.locale());
                return false;
            }
            $('product-price').value = $('product-price').value.replace(/\,/gim, '.');
            
            
            if ($('product-name').value.empty()) {
                $('product-name').addClassName('error');
                $('product-error2').update('Enter a %s name'.locale(activeType));
                return false;
            }
            
            
            if (activeItem) {
                activeItem.buttons.hide();
                activeItem.select('li').invoke('hide');
                activeItem.setStyle({
                    background: '#F5F5F5',
                    border: '1px solid #CCC'
                });
            }
            
            $('product-menu').shift({
                height: 0,
                opacity: 0,
                duration: 0.5
            });
            paymentWizard.buttons.next.setOpacity(1).enable();
            paymentWizard.buttons.back.setOpacity(1).enable();
            paymentWizard.buttons.addNew.show();
            paymentWizard.buttons.save.hide();
            paymentWizard.buttons.cancel.hide();
            
            var name = $('product-name').value;
            var price = $('product-price').value;
            var period = $('product-recurring-period').getSelected().value;
            var setupfee = $('product-setupfee').value;
            var trial = $('product-trial-period').getSelected().value;
            var icon = $('product-image').value;
            var p = productLine.retrieve('settings');
            var productID = (parseInt(form.getProperty('maxProductID'), 10) || 1000) + 1;
            
            if (p.pid) {
                productID = p.pid;
            } else {
                form.setProperty('maxProductID', productID);
            }
            
            var settings = {
                pid: productID,
                name: name,
                price: price,
                period: period,
                setupfee: setupfee,
                trial: trial,
                icon: icon,
                options: activeItem.retrieve('settings').options,
                hasQuantity: activeItem.retrieve('settings').hasQuantity
            };
            var elem = getElementById(paymentElem);
            
            if (paymentType == "control_2co") {
                settings.productID = $('product-2coid').value;
            }
            
            productLine.store('settings', settings);
            activeItem = false;
            
            if (activeType == "subscription") {
                productName.update(BuildSource.makeProductText(name, price, currency, period, setupfee, trial));
            } else {
                productName.update(BuildSource.makeProductText(name, price, currency, false, false, false));
            }
            clearProductForm();
        };
        
        productLine.observe('click', function(){
            if (activeItem == productLine) { return; }
            if (activeItem) {
                activeItem.buttons.hide();
                activeItem.select('li').invoke('hide');
                activeItem.setStyle({
                    background: '#F5F5F5',
                    border: '1px solid #CCC'
                });
            }
            activeItem = productLine.setStyle({
                background: '#DDD',
                border: '1px solid #666'
            });
            productLine.select('li').invoke('show');
            productLine.buttons.show();
            
            clearProductForm();
            var psettings = productLine.retrieve('settings');
            if (!psettings) { return; }
            
            $('product-name').value = psettings.name;
            $('product-price').value = psettings.price;
            $('product-recurring-period').selectOption(psettings.period);
            $('product-setupfee').value = psettings.setupfee;
            $('product-trial-period').selectOption(psettings.trial);
            if (psettings.productID > 0) {
                $('product-2coid').value = psettings.productID;
            }
            $('product-image').value = psettings.icon;
            
            paymentWizard.buttons.next.setOpacity(0).disable();
            paymentWizard.buttons.back.setOpacity(0).disable();
            paymentWizard.buttons.addNew.hide();
            paymentWizard.buttons.save.show();
            //paymentWizard.buttons.cancel.show();
            $('product-menu').shift({
                height: 133,
                opacity: 1,
                duration: 0.5
            });
            
            paymentWizard.buttons.save.onclick = saveItem;
        });
        
        productDelete.observe('click', function(){
            productLine.remove();
        });
        
        paymentWizard.buttons.cancel.onclick = function(){
            if (activeItem) {
                activeItem.buttons.hide();
                activeItem.select('li').invoke('hide');
                activeItem.setStyle({
                    background: '#F5F5F5',
                    border: '1px solid #ccc'
                });
            }
            activeItem = false;
            
            $('product-menu').shift({
                height: 0,
                opacity: 0,
                duration: 0.5
            });
            
            paymentWizard.buttons.next.setOpacity(1).enable();
            paymentWizard.buttons.back.setOpacity(1).enable();
            paymentWizard.buttons.addNew.show();
            paymentWizard.buttons.save.hide();
            //paymentWizard.buttons.cancel.hide();
            clearProductForm();
        };
        
        paymentWizard.buttons.save.onclick = saveItem;
        
        $('product-list').insert(productLine);
        Sortable.create('product-list');
    }
    
    openPaymentWizard(Utils.useArgument);
})();