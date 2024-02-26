<?php

use Legacy\Jot\Configs;

include_once "../lib/init.php";
    Session::checkAdminPages();
    
    $root = ROOT;
    $subfolder = Configs::SUBFOLDER;
    $r = $_REQUEST;
    /**
     * Ajax request success propmt
     * @param  $msg
     * @return 
     */
    function s($msg){
        header("Content-Type: text/javascript; charset=utf-8");
        
        if(is_array($msg)){
            $arr = array_merge($msg, array('success'=>true, "message"=>'Successful'));
        }else{
            $arr = array('success'=>true, "message"=>$msg);
        }
        die(json_encode($arr));
    }
    
    /**
     * Ajax request error prompt
     * @param  $msg
     * @return 
     */
    function e($msg){
        header("Content-Type: text/javascript; charset=utf-8");
        if(is_array($msg)){
            $arr = array_merge(array('success'=>false, "error"=>'Failed'), $msg);
        }else{
            $arr = array('success'=>false, "error"=>$msg);
        }
        die(json_encode($arr));
    }
    
    /**
     * Retun the defined constants of a class
     * @param  $className
     * @return 
     */
    function get_class_constants($className){
        $reflect = new ReflectionClass($className);
        return $reflect->getConstants();
    }
    
    # If test parameter was sent, then this is an ajax request for testing parameters
    if(isset($r['mode'])){
        switch($r['mode']){
            case "ldapConfig":
            	try{
            	    # If LDAP is disabled don't do tests
            	    if($r['USELDAP'] === "0"){ s('skipped'); }
                	$l = new LDAPInterface();
                    $l->setOption('server', $r['LDAP_SERVER']);
                    $l->setOption('port', $r['LDAP_PORT']);
                    if(!$l->connectAndBind($r['LDAP_BIND_USER'], $r['LDAP_BIND_USER_PASS'])){
                        e("Cannot bind user please check configuration");                    
                    }
            	}catch(Exception $e){
            	    e($e->getMessage());
            	}
            break;
            case "checkDB":
                # Production
                if(isset($r['PRO_DB_HOST'])){
                    $hostname = $r['PRO_DB_HOST'];
                    $username = $r['PRO_DB_USER'];
                    $password = $r['PRO_DB_PASS'];
                    $database = $r['DBNAME'];
                }else{ # Development
                    $hostname = $r['DEV_DB_HOST'];
                    $username = $r['DEV_DB_USER'];
                    $password = $r['DEV_DB_PASS'];
                    $database = 'jotform_new';
                }
                
                if(@mysql_connect($hostname, $username, $password, true)){
                    if(!@mysql_select_db($database)){
                        e(array("error" => 'Cannot Select Database', "details"=> mysql_error()));
                    }
                }else{
                    e(array("error" => 'Cannot Connect to Database', "details"=> mysql_error()));
                }
            break;
            case "checkPaths":
                if(isset($r['LOGFOLDER'])){
                    $paths = array(array('CACHEPATH', 'cache'), array('UPLOADPATH', 'upload'), array('LOGFOLDER', 'log'));
                    foreach($paths as $path){
                        if(file_exists($r[$path[0]])){
                            if(!is_writable($r[$path[0]])){
                                e($path[1].' folder is not writable');
                            }
                        }else{
                            if(!@mkdir($r[$path[0]], 0777)){
                                e('Cannot create '.$path[1].' folder');
                            }
                        }
                    }
                }
            break;
            case "complete":
                $config = json_decode($r['config'], true);
                $newConfig = array_merge(get_class_constants('Configs'), $config);
               
                # remove admin user params
                unset($newConfig['USERNAME']);
                unset($newConfig['EMAIL']);
                unset($newConfig['PASSWORD']);
                
                # set app defaults
                $newConfig['USECDN'] = false;
                $newConfig['USEUFS'] = false;
                $newConfig['APP']    = true;
                
                # create class content
                
                $class  = "<?php\n\n";
                $class .= "# Generated on the installer\n";
                $class .= "class Configs {\n";
                
                $maxLength = 0;
                foreach($newConfig as $key => $value){
                    if(strlen($key) > $maxLength){
                        $maxLength = strlen($key);
                    }
                }
                
                
                foreach($newConfig as $key => $value){
                    if(is_bool($value)){
                        $value = $value? "true" : "false";
                    }else if(is_string($value)){
                        # In order to save boolean as boolean
                        if($value != "true" && $value != "false"){
                            # if value is not boolean then save it as string
                            $value = '"'.addslashes($value).'"';
                        }
                    }else if($key == 'CACHEPATH' || $key == 'UPLOADPATH' || $key == "LOGFOLDER"){
                        if(substr($value, -1) != '/'){
                             $value = $value."/";
                        }
                        $value = '"'.$value.'"'; 
                    }
                    
                    # Remove analytics code if it's an example code
                    if($key == 'ANALYTICS_CODE' && strpos($value, "XX") !== false){
                        $value = '""';
                    }
                    
                    $class .= "    const ".str_pad($key, $maxLength, " ").' = '.$value.";\n";
                }
               
                $class .= "\n    public static ".str_pad('$servers', $maxLength-8, " ")." = array();\n\n";
                $class .= "}\n"; 
                
                
                if(!file_put_contents($root."lib/ConfigsClass.php", $class)){
                    e('Cannot write configuration file.');
                }                
            break;
        }
        s('OK');
        # ---------- Ajax end here ----------- #
    }
    
    # Bring back the installer configurations but fill it with current settings
    include "installerConfig.php";
    $currentConfigs = get_class_constants('Configs');
    foreach($installerConfig as $groupName => $group){
        foreach($group['items'] as $key => $value){
            if(isset($currentConfigs[$key])){
                $installerConfig[$groupName]['items'][$key]['value'] = $currentConfigs[$key];                
            }
        }
    }
    
    # No need for admin user page
    unset($installerConfig['ADMIN_USER']);
    
    
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>JotForm Settings</title>
        <base href="<?=HTTP_URL?>" />
        <link href="css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <script src="js/prototype.js" type="text/javascript"></script>
        <script src="js/protoplus.js" type="text/javascript"></script>
        <script src="js/protoplus-ui.js" type="text/javascript"></script>
        <link href="css/buttons.css" rel="stylesheet" media="screen" />
        <script> document.APP = true; document.SUBFOLDER = '<?=Configs::SUBFOLDER?>'; </script>
        <style>
            html, body{
                height:100%;
                width:100%;
                padding:0px;
                margin:0px;                
            }
            body{
                font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
                font-size:12px;
            }
            .props{
                display:inline-block;
                border:2px solid #bbb;
                text-align:left;
                list-style:none;
                padding:20px;
                list-style-position:outside;
                background:#ffffff;
                -moz-box-shadow:0px 7px 17px rgba(0,0,0, 0.5);
                -webkit-box-shadow:0px 7px 17px rgba(0,0,0, 0.5);
            }
            .props label{
                float:left;
                width:150px;
                line-height:24px;
            }
            .props li{
                margin-bottom:10px;
                border-bottom:1px dashed #ccc;
                padding:2px;
            }
            
            .props input{
                border:2px solid #ccc;
                padding:4px;
            }
            
            .props select{
                border:2px solid #CCCCCC;
                padding:3px;
                width:160px;
            }
            
            .props input:focus{
                border-color:navy;
            }
            .desc{
                font-size:10px;
                color:#888;
                margin-top:6px;
            }
            .pages:first-child .back-button{
                display:none;
            }
            .last-child .next-button{
                display:none;
            }
            
            .finish-button{
                float:right;
                
            }
            .pages{
                display:inline-block;
            }
            
            .pages .back-button{
                float:left;
            }
            .pages .next-button{
                float:right;
            }
            .pages .error{
                color:#FFA500;
                float:left;
                font-size:11px;
                padding-top:7px;
                text-align:center;
                width:61%;
                max-width:220px;
            }
            .buttons{
                border-color:#888;
                -moz-box-shadow:0px 2px 4px rgba(0,0,0,0.5);
                -webkit-box-shadow:0px 2px 4px rgba(0,0,0,0.5);
                margin-left:5px;
            }
            .err{
                border:2px solid red !important;
            }
            h3{
                color:#FFFFFF;
                margin-bottom:0;
                padding-left:5px;
                text-align:left;
                text-shadow:0 0px 2px #000, 0 0 19px #FFFFFF;
            }
        </style>
        
        <script type="text/javascript">
            document.ready(function(){
                var pages = $$('.pages');
                
                pages.each(function(page, i){
                    if(i > 0){
                        page.hide();
                    }
                    
                    if(i == pages.length - 1){
                        page.addClassName('last-child');
                        var fbutton = new Element('input', {type:'button', value:'Finish', className:'big-button buttons buttons-green finish-button'});
                        page.insert(fbutton.observe('click',  function(){
                            nextPage(page, function(){
                                var parameters = {};
                                $$('.pages input, .pages select').each(function(e){
                                    if(e.name){
                                        if (e.readAttribute('type') == 'checkbox') {
                                            parameters[e.name] = e.checked ? "true" : "false";
                                        } else {
                                            parameters[e.name] = e.value.strip();
                                        }
                                    }
                                });
                                parameters.SUBFOLDER = $('subfolder').value;
                                new Ajax.Request(location.href, {
                                    parameters:{
                                        mode: 'complete',
                                        config: Object.toJSON(parameters)
                                    },
                                    evalJSON:'force',
                                    onComplete: function(t){
                                        var res = t.responseJSON;
                                        
                                        if(res.success){
                                            $('content').update('<h2>Settings Saved.</h2><p>Reload page to see changes.</p>');
                                        }else{
                                            error.update(res.error);
                                        }
                                    }
                                });
                            });
                        }));
                    }
                    
                    var back  = page.select('.back-button')[0];
                    var next  = page.select('.next-button')[0];
                    var error = page.select('.error')[0];
                    var nextPage = function(page, callback){
                        // clear errors
                        error.update();
                        error.onclick = null;
                        $$('.err').each(function(e){ e.removeClassName('err'); });
                        
                        // Check Required fiedls
                        var err = false;
                        page.select('input').each(function(e){
                            if(e.hasClassName('Required') && e.value.empty() && !e.disabled){
                                e.addClassName('err');
                                err = true;
                            }
                        });
                        // print required errors
                        if(err){
                            error.update('Marked fields are required.');
                            return false;
                        }
                        
                        // if there is a backand test defined go check values
                        if (page.readAttribute('test')) {
                            error.update('<img src="<?=$subfolder?>images/small-ajax-loader.gif" />');
                            var parameters = {};
                            page.select('input, select').each(function(e){
                                if(e.name){
                                    if(e.readAttribute('type') == 'checkbox'){
                                        parameters[e.name] = e.checked? "1" : "0";
                                    }else{
                                        parameters[e.name] = e.value.strip();
                                    }
                                }
                            });
                            
                            parameters.mode = page.readAttribute('test');
                            
                            // Also send DB parameters for creating admin account
                            if(parameters.mode == "createAdmin"){
                                parameters.DBNAME = $('DBNAME').value.strip();
                                parameters.PRO_DB_HOST = $('PRO_DB_HOST').value.strip();
                                parameters.PRO_DB_USER = $('PRO_DB_USER').value.strip();
                                parameters.PRO_DB_PASS = $('PRO_DB_PASS').value.strip();
                            }
                            
                            new Ajax.Request(location.href, {
                                parameters: parameters,
                                evalJSON: 'force',
                                onComplete: function(t){
                                    var res = t.responseJSON;
                                    if(res.success){
                                        callback();
                                        error.update();
                                    }else{
                                        error.update(res.error);
                                        if(res.details){
                                            error.onclick = function(){
                                                alert(res.details);
                                            }
                                        }
                                    }
                                }
                            });
                            
                        } else {
                            callback();
                            error.update();
                        }
                    }
                    
                    back.observe('click', function(){
                        page.hide().previous().show();
                    });
                    
                    next.observe('click', function(){
                        nextPage(page, function(){
                            page.hide().next().show();
                        });
                    });
                });
                
                if($('USELDAP')){
                    var setStatus = function(){
	                    $$('#LDAP_SETTINGS li input[type!="checkbox"]').each(function(e){
                            if(!$('USELDAP').checked){
                                e.disable();
                                e.up('li').setOpacity(0.5);
                            }else{
                                e.enable();
                                e.up('li').setOpacity(1);
                            }
                        });
                    }
                    setStatus();
                    $('USELDAP').observe('click', setStatus);
                }
            });
        </script>
    </head>
    <body>
        <table width="100%" height="100%">
            <tr>
                <td align="center" valign="top" id="content">
                    <?
                    foreach($installerConfig as $groupName => $group){
                        if(isset($group['hidden']) && $group['hidden'] === true){ continue; }
                            echo '<div class="pages" test="'.$group['tests'].'" id="'.$groupName.'"><h3>'.$group['title'].'</h3><ul class="props">';
                            foreach($group['items'] as $key => $prop){
                                if(isset($prop['hidden']) && $prop['hidden'] === true){ continue; }
                    ?>
                        <li>
                            <label for="<?=$key?>"><?=$prop['title']?></label>
                            <? if(isset($prop['dropdown'])){ ?>
                                <select id="<?=$key?>" name="<?=$key?>">
                                    <? foreach($prop['dropdown'] as $opt){
                                        $sel = $opt == $prop['value']? 'selected="selected"' : '';
                                        echo '<option '.$sel.' >'.$opt.'</option>';
                                    }?>
                                </select>
                            <? }else{
                                $checked = "";
                                if(isset($prop['type']) && $prop['type'] == 'checkbox'){
                                    if($prop['value'] == "1"){
                                        $checked='checked="checked"';
                                    }
                                }
                                
                                # print out the input field
                                echo strtr('<input type="[type]" [checked] class="[class]" id="[id]" name="[name]" value="[value]" />', array(
                                
                                    "[type]"    => (isset($prop['type'])? $prop['type'] : 'text'),
                                    "[checked]" => $checked,
                                    "[class]"   => ((isset($prop['required']) && $prop['required'])? 'Required' : ''),
                                    "[id]"      => $key,
                                    "[name]"    => $key,
                                    "[value]"   => $prop['value']
                                ));
                            } ?>
                            <div class="desc"><?=$prop['desc']?></div>
                        </li>        
                    <? }
                    
                    echo '</ul><br>
                    <input class="big-button buttons buttons-grey back-button" type="button" value="< Back">
                    <div class="error"></div>
                    <input class="big-button buttons buttons-grey next-button" type="button" value="Next >">
                    </div>';
                    
                    } ?>
                    </ul>
                    <input type="hidden" name="SUBFOLDER" value="<?=$subfolder?>" id="subfolder"/>
                    <br />
                </td>
            </tr>
        </table>
    </body>
</html>