<?php
    include "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Sync Database</title>
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <script src="../js/prototype.js" type="text/javascript" ></script>
        <script src="../js/protoplus.js" type="text/javascript" ></script>
        <style>
            #changes{
                list-style:none;
                list-style-position:outside;
            }
            #changes li{
                margin:5px;
                border:1px solid #ccc;
                background:#f5f5f5;
                padding:5px;
            }
        </style>
        <script>
            
            function showChanges(response){
                if(response.success){
                    if(response.changes.length > 0){
                       $A(response.changes).each(function(query){
                           $('changes').insert('<li>'+query+'</li>');
                       });
                        $('confirm').show()
                       
                    }else{
                        $('changes').update("No new changes found on database");
                    }
                }else{
                    window.parent.Utils? window.parent.Utils.alert(response.error) : alert(response.error);                    
                }
            }
            
            function syncNow(){
                new Ajax.Request('../server.php', {
                    parameters: {
                        action:'syncDatabase'
                    },
                    evalJSON:'force',
                    onComplete: function(t){
                        var response = t.responseJSON;
                        if(response.success){
                            window.parent.Utils? window.parent.Utils.alert(response.message) : alert(response.message);
                            location.reload();
                        }else{
                            window.parent.Utils? window.parent.Utils.alert(response.error) : alert(response.error);
                        }
                    }
                });
            }
        </script>
	</head>
	<body>
	    <div id="admin-content">
            <div id="changes">
                
            </div>
            <div id="confirm" style="display:none; text-align:center; margin-top:50px;">
                These are the changes found on database are you sure you want to apply them on your database?
                <br><br>
                <button onclick="syncNow()">Sync Database</button>
            </div>
	    </div>
        <script src="../server.php?action=getDatabaseChanges&callback=showChanges"></script>
	</body>
</html>
