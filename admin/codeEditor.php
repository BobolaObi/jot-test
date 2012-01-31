<?php
  include "../lib/init.php";
  Session::checkAdminPages();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Run some code on the server</title>
        <base href="<?=HTTP_URL?>" ></base>
        <link type="text/css" href="css/style.css" rel="stylesheet" />
        <link type="text/css" href="css/fancy.css" rel="stylesheet" />
        <script type="text/javascript" src="js/prototype.js"></script>
        <script type="text/javascript" src="js/protoplus.js"></script>
        <script type="text/javascript" src="js/protoplus-ui.js"></script>
        <script src="opt/codemirror/js/codemirror.js" type="text/javascript"></script>
        <style>
            body{
                background-position: 0 -55px;
            }
            #response{
                background:#fff;
                border:1px solid #666;
                
                min-height:150px;
                list-style:none;
            }
            #response li{
                border:1px solid #ccc;
                margin:5px;
                padding:5px;
            }
            #content-all{
                position:relative;
                margin:30px;
                
            }
            #code{
                width:100%;
                height:350px;
            }
            .CodeMirror-line-numbers{
                background: none repeat scroll 0 0 #EFEFEF;
                color: #AAAAAA;
                font-size: 10pt;
                font-family:monospace;
                padding: 5px 4px;
                border-right:1px solid #ccc;
            }
            #cleanup{
                height: 50px;
                padding: 5px;
                width: 300px;
            }
            #predefined-cont{
                bottom: 5px;
                position: absolute;
                right: 17px;
            }
            #predefined-cont label{
                color:#AAAAAA;
                font-size:10px;
            }
            .code-container{
                border: 1px solid black;
                padding: 0px;
                background-color: #F8F8F8;
                margin:10px 0;
                position:relative;
            }
            .error-line{
                background:#ff5544;
                color:#fff;                
            }
        </style>
        <script type="text/javascript">
            
            var resC = 1;
            var emptyCode = '<'+'?php\n\n    phpinfo();\n\n?>';
            
            var preDefinedCodes = {
                "Clear Cache by Search":    '<'+'?php\n\n    // Searches all JS cache files and deletes all matches\n\n    Form::clearCache("search", "control_datetime");\n\n?>',
                "Who Am I?":                '<'+'?php\n\n    // Returns the current servers name\n\n    echo Server::whoAmI();\n\n?>',
                "Read Logs":                '<'+'?php\n\n    // Read Last 1000 Lines from error log\n\n    echo Console::readConsole(1000, "error");\n\n?>',
				"Clear S3 Cache": 			'<'+'?php\n\n    if (!defined("distributionID")){\n      define("distributionID", "E1E8T7SGZ48U50");\n    }\n\n'+
											'    $cdn = new AmazonCloudFront();\n    $response = $cdn->create_invalidation(\n        distributionID,\n        "aws-php-sdk-test". time(),\n'+
											'        array("jotform.css",\n              "jotform.cssgz",\n              "jotform.js",\n              "jotform.jgz"\n             )\n    );\n\n'+
											'    if($response->isOK()){\n      echo "OK";\n    } else {\n      echo "FAIL";\n    }\n\n?>',
                "Clone Form to an Account": '<'+'?php\n\n    // Create a clone of given form on given users account\n\n    $form = new Form("123456789");\n    $form->cloneForm("username");\n\n?>',
                "Move form to an Account":  '<'+'?php\n\n    // Move given form to given users account\n\n    Form::assignOwner("123456789", "username");\n\n?>',
                "Delete All Submissions":   '<'+'?php\n\n    // Deletes All submissions of given form\n\n    $form = new Form("123456789");\n    $form->deleteAllSubmissions();\n\n?>',
                "Clear Form Cache":         '<'+'?php\n\n    // Clear Given forms cache on all servers\n\n    Form::clearCache("id", "123456789");\n\n?>',
                "Clear User Cache":         '<'+'?php\n\n    // Clear all caches on all servers belongs to this user\n\n    User::clearCache("Username");\n\n?>',
                "Delete User":              '<'+'?php\n\n    // Completely Deletes a User\n\n    User::reallyDelete("Username");\n\n?>'
            };
            
            
            function runCode(){
                var code = editor.getCode().replace(/\&nbsp\;/gim, '');
                
                if('localStorage' in window){
                    localStorage.code = code;
                }
                
                new Ajax.Request('server.php',{
                    parameters: {
                        action: 'evalCode',
                        code: code
                    },
                    evalJSON:'force',
                    onComplete: function(t){
                        var res = t.responseText;
                        
                        if(t.responseJSON){
                            res = t.responseJSON;
                            if(res.success){
                                $('response').insert({top: new Element('li').insert((resC++)+": "+res.error)});
                            }else{
                                $('response').insert({top: new Element('li', {className:'error-line'}).insert((resC++)+": Error: "+res.error)});
                            }
                        }else{
                            
                            if(res.include(': eval()\'d code on line') || res.strip().startsWith('Fatal error:')){
                                $('response').insert({top: new Element('li', {className:'error-line'}).insert((resC++)+": Error: "+res)});
                            }else{
                                $('response').insert({top: new Element('li').insert((resC++)+": "+res)});
                            }
                            
                        }
                    }
                });
            }
            
            function clearCode(){
                if(confirm("This will remove the code in editor. Are you sure?")){
                    editor.setCode(emptyCode);
                    if('localStorage' in window){
                        localStorage.code = emptyCode;
                    }
                }
            }
            
            function clearConsole(){
                resC = 1;
                $('response').update();
            }
            
            document.ready(function(){
                try{
                    $('cleanup').hint("Paste the code here for clean-up mark-ups:");
                    $H(preDefinedCodes).each(function(p){
                        $('predefined').insert(new Element('option').update(p.key));
                    });
                    $('predefined').observe('change', function(){
                        var fun = $('predefined').value;
                        if(fun in preDefinedCodes){
                            editor.setCode(preDefinedCodes[fun]);
                        }
                    });
                }catch(e){
                    console.error(e);
                }
            });
            
            document.keyboardMap({
                "Meta+S":{ // Save form for MACs
                    handler: function(e){
                        Event.stop(e);
                        runCode();
                        return false;
                    }
                },
                "Ctrl+S":{ // Save form for WINs
                    handler: function(e){
                        Event.stop(e);
                        runCode();
                        return false;
                    }
                },
                "backspace":{
                    handler:function(e){
                        Event.stop(e);
                    },
                    disableOnInputs: true
                }
            });
        </script>
    </head>
    <body style="position:relative">
        <br>
        <div id="content-all">
            <div class="code-container">
                <textarea spellcheck="false" id="code" wrap="off"></textarea>
                <div id="predefined-cont">
                    <label>
                        Predefined Codes: 
                        <select id="predefined">
                            <option>Please Select</option>
                        </select>
                    </label>
                </div>
            </div>
            <script>
                if('localStorage' in window){
                    if(localStorage.code){
                        $('code').innerHTML = localStorage.code;
                    }
                }
                
                if(!$('code').innerHTML){
                    $('code').innerHTML = emptyCode;
                }
                var base = $$('base')[0].href;
                var editor = CodeMirror.fromTextArea('code', {
                    height: "350px",
                    parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js",
                                 "../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js",
                                 "../contrib/php/js/parsephphtmlmixed.js"],
                    stylesheet: [
						base+"/opt/codemirror/css/xmlcolors.css",
						base+"/opt/codemirror/css/jscolors.css",
						base+"/opt/codemirror/css/csscolors.css",
						base+"/opt/codemirror/contrib/php/css/phpcolors.css"
					],
                    path: base+"/opt/codemirror/js/",
                    lineNumbers: true,
					indentUnit: 4,
                    continuousScanning: 500
                });
            </script>
            <div style="height:65px;">
                <button class="big-button buttons buttons-grey" onclick="runCode();">Run the code</button>
                <button class="big-button buttons buttons-blood" onclick="clearCode()">Clear Editor</button>
                <button class="big-button buttons buttons-fire" onclick="clearConsole()">Clear Console</button>
                <div style="float:right">
                    <textarea id="cleanup"></textarea>
                </div>
            </div>
           
            <fieldset style="border:none;margin:0; padding:0px;clear: both;">
                <legend>Response</legend>
                <div id="response">
                    
                </div>
            </fieldset>
        </div>
    </body>
</html>
