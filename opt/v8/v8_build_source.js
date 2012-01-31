//try {
	var form_id = arguments[0];
    var configFile  = arguments[1];
    
    var DEBUGMODE = false;
    if(arguments[2] == "debug"){
        DEBUGMODE = true;
    }
    
    load("v8_env.js");
    
    function getSavedForm(config){
        BuildSource.init(config.form);
    }
    if(!configFile){
        configFile = "v8_config";
    }
    
    load( configFile + '.js');
    
    load('../../js/builder/build_source.js');
    load('../../js/builder/question_definitions.js');
    load('../../js/builder/question_properties.js');
    
    load(V8Config.CACHEPATH+form_id+'.js');
    
    print(BuildSource.getCode( {
    	type : 'css',
    	pagecode : true,
    	JSFORM : ('JSFORM' in V8Config) ? V8Config.JSFORM : false
    }));
/*} catch (e) {
	print(e);
    print_r(V8Config);
}*/

