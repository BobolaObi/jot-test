<?php
    include "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Translations</title>
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
	</head>
	<body>
	    <div id="admin-content">
            Display user submitted translations, percentage of completeness and some buttons to releas a new language
	    </div>
	</body>
</html>
