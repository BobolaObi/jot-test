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
		<title>Migrate User</title>
        <script>
            function migrate(){
                $('response').update("Migrating now...");
                window.parent.Admin.migrateUser({
                    username: $('username').value,
                    addPrefix: $('addPrefix').checked,
                    mergeAccount: $('mergeAccount').checked,
                    callback: function(response){
                        if (response.success) {
                            $('response').update(response.message + " In:" + response.duration + "ms");
                        } else {
                            $('response').update("<pre>"+response.error+"</pre>");
                        }
                    }
                });
            }
        </script>
	</head>
	<body>
	    <div style="padding:10px;">
            <div>
    	        Enter a user name to migrate <input type="text" id="username">
            </div>
            <div>
                <label for="addPrefix">Add Debug Prefix (migrated_username)</label><input type="checkbox" id="addPrefix"><br>
                <label for="mergeAccount">Changes only Migration</label><input type="checkbox" checked="checked" id="mergeAccount"><br>
                <button type="button" class="big-button buttons" onclick="migrate()">Migrate</button>
            </div>
            <div id="response"></div>
	    </div>
	</body>
</html>