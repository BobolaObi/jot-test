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
        <link type="text/css" href="opt/codepress/languages/php.css" rel="stylesheet" id="cp-lang-style" />
        <!--script type="text/javascript" src="https://bespin.mozillalabs.com/bookmarklet/bookmarklet.js"></script-->
    	<script type="text/javascript" src="opt/codepress/codepress.js"></script>
        <style>
            body{
                background-position: 0 -55px;
            }
            #response{
                background:#fff;
                border:1px solid #666;
                width:650px;
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
                margin:0 auto;
                width:650px;
            }
        </style>
    	<script type="text/javascript">
    		CodePress.language = 'php';
            
            var resC = 1;
            function runCode(){
                var code = cp1.getCode().replace(/\&nbsp\;/gim, '');
                
                if('localStorage' in window){
                    localStorage.code = code;
                }
                
                new Ajax.Request('server.php',{
                    parameters: {
                        action: 'evalCode',
                        code: code
                    },
                    
                    onComplete: function(t){
                        var res = t.responseText;
                        $('response').insert({top: new Element('li').insert((resC++)+":"+res)});
                    }
                });
            }
            function clearConsole(){
                resC = 1;
                $('response').update();
            }
            
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
    	    <textarea id="cp1" class="codepress php" style="width:650px;height:350px;" wrap="off">&lt;?php
    
    
    
    
?&gt;</textarea>
            <script>
                if('localStorage' in window){
                    if(localStorage.code){
                        $('cp1').innerHTML = localStorage.code;
                    }
                }
            </script>
            
            <button class="big-button buttons" onclick="runCode();">Run the code</button>&nbsp;&nbsp;
            <button  class="big-button buttons buttons-red" onclick="clearConsole()">Clear Console</button>
            <div style="float:right">
                Paste the code here for clean-up:<br>
                <textarea cols="30" rows="2"></textarea>
            </div>
            <fieldset style="border:none;margin:0; padding:0px;">
                <legend>Response</legend>
                <div id="response">
                    
                </div>
            </fieldset>
        </div>
	</body>
</html>
