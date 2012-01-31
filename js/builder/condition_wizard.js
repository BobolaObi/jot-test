var wizardWin = false;
function openConditionWizard(elem){
    
    Utils.loadTemplate('wizards/conditionWizards.html', function(source) {
        var div = new Element('div');
        
        div.innerHTML = source;
        
        wizardWin = document.window({
            title: 'Conditions Wizard'.locale(),
            width: 400,
            contentPadding: 0,
            content: div,
            dynamic: false,
            onClose: function(){
                document._onedit = false;
                // delete elem if necessary
                if (elem.hasClassName('drags')) {
                    elem.remove();
                }
            },
            onInsert: function(w){
            	Locale.changeHTMLStrings();
                document._onedit = true;
                var qid = false;
                var sel = $('fields');
                var field = ""; // qid
                var type = elem.readAttribute('type');
                var conds = [];
                type = type == "conditional_field"? "control_dropdown" : type;
                setTimeout(function(){
                    if(!elem.hasClassName('drags')){ // Then it's already existing element
                        w.buttons.next.run('click');
                        w.buttons.removeConditions.show();
                        $("existing").checked = true;
                        qid = elem.id.replace("id_", "");
                        conds = elem.getReference('elem').getProperty('conditions') || [];
                    }
                    
                    $("cond_fields").selectOption(type);
                    
                    getUsableElements().each(function(el){
                        sel.insert(new Element("option", { value: el.getProperty('qid'), selected: (qid == el.getProperty('qid'))? 'selected': false}).insert(el.getProperty('text').shorten(20)));
                    });
                    
                    $('condition-box').update(conditionSelect("email", conds, 'show this field'.locale()));
                }, 10);
            },
            buttons:[{
                name:'removeConditions',
                title:'Remove Conditions'.locale(),
                hidden:true,
                handler:function(w){
                    var res = false;
                    if(!$('create').checked){ // Create a new one is selected
                        elem = getElementById($('fields').getSelected().value);
                        elem.setProperty("hasCondition", "No");
                        res = renewElement(elem, elem.getReference('container'));
                    }
                    res.container.hiLite();
                    wizardWin.close();
                }
            },{
                name:'back',
                title:'Back'.locale(),
                disabled:true,
                handler:function(w){
                    console.log(w, "back");
                    $('page1').show();
                    $('page2').hide();
                    w.buttons.next.enable();
                    w.buttons.removeConditions.hide();
                    w.buttons.back.disable();
                    w.setStyle('width:400px');
                    w.reCenter();
                }
            },{
                name:'next',
                title:'Next'.locale(),
                handler:function(w){
                    console.log(w, "next");
                    $('page1').hide();
                    $('page2').show();
                    w.buttons.next.disable();
                    w.buttons.back.enable();
                    
                    // If create is not selected and existing field already has a condition user should be able to remove them
                    if(!$('create').checked && getElementById($('fields').getSelected().value).getProperty("hasCondition") == "Yes"){
                        w.buttons.removeConditions.show();
                    }
                    
                    w.setStyle('width:700px');
                    w.reCenter();
                }
            },
            {
                name:'complete',
                title: 'Complete'.locale(),
                handler:function(){
                    
                    var conds = collectConditions();
                    
                    var res = false;
                    if($('create').checked){ // Create a new one is selected
                        elem.writeAttribute('type', $('cond_fields').getSelected().value);
                        res = createDivLine(elem, false, false, true);
                        res.container.run('click');
                        createList();
                        
                    }else{ // "Use an existing one" is selected
                        
                         if (elem.hasClassName('drags')) {
                             elem.remove();
                         }
                        
                        elem = getElementById($('fields').getSelected().value);
                        elem.setProperty("hasCondition", "Yes");
                        res = renewElement(elem, elem.getReference('container'));
                    }
                    res.elem.setProperty("conditions", conds);
                    res.container.hiLite();
                    wizardWin.close();
                }
            },{
                name:'cancel',
                title: 'Cancel',
                link:true,
                handler:function(){
                    wizardWin.close(); 
                }
            }]
        });
    });
    
}

openConditionWizard(Utils.useArgument);
