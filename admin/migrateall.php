<?php
    include_once "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/prototype.js"></script>
		<title>Migrate All</title>
        <style>
            #response{
                background:none repeat scroll 0 0 #FFFFFF;
                border:1px solid #CCCCCC;
                height:410px;
                overflow:auto;
                padding:10px;
            }
        </style>
        <script>
            function migrateAll(){

                localStorage.currentChunk = parseInt($('chunk').value, 10);
                $('avgTime').update("&nbsp;&nbsp;Average Time: " + calculateAvg() + "ms");
                window.parent.Admin.migrateAll({
                    chunk: localStorage.currentChunk,
                    callback: function(response){
                        if (response.success) {
                            $('chunk').value = parseInt(localStorage.currentChunk, 10) + 100;
                            var div = new Element('div');
                            div.innerHTML = "Previous migrate took: "+ response.duration +"ms<br>Currently migrating: " + $('chunk').value+" => "+(parseInt(localStorage.currentChunk, 10) + 200)+"...";
                            div.innerHTML += '<hr>';
                            
                            
                            $('response').insert({top:div});
                            localStorage.log = $('response').innerHTML;
                            migrateAll();
                        } else {
                            $('error').insert("<pre>"+response.error+"</pre>");
                        }
                    }
                });
            }
            
            function calculateAvg() {
                var sum = 0;
                var timeArr = $A(localStorage.log.match(/\s(\d+\.\d+)ms/gim)).map(function(numStr) { return parseFloat(numStr); });
                $A(timeArr).each(function(t) { sum += t; });
                var avg = sum / timeArr.length;
                return avg.toFixed(2);
            }
            
            document.observe('dom:loaded', function(){
                $('response').innerHTML = localStorage.log;
                $('chunk').value = localStorage.currentChunk;
            })
            
        </script>
	</head>
	<body>
	    <div style="padding:10px;">
            <div>
    	        Enter a chunk point to start <input type="text" id="chunk" value="0">
                <button type="button" class="big-button buttons" onclick="migrateAll()">Migrate</button>
                <span id="avgTime"></span>
            </div>
            <div id="error"></div>
            <div id="response"></div>
	    </div>
	</body>
</html>