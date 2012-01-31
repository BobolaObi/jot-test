<?php
    include "../lib/init.php";
    Session::checkAdminPages(true);
    $keyword = $_GET["keyword"];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Search Database</title>
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <style>
            ul{
                list-style:none;
            }
            li{
                margin:2px;
                font-size:14px;
            }
            #pleasewait{
                display:none;
            }
            .listUsers>a{
                color: black;
                text-decoration: none;
            }
            .listUsers>a>div{
                font-size:18px;
            }
            .listUsers>a>span{
                color: #646464;
                font-size: 10px;
            }
        </style>
	</head>
	<body>
	    
        <h3 id="pleasewait">Please wait while searching...</h3>
	    <? ob_flush(); flush(); ob_start(); ?>
        <?
            $page = "details.php";
            if($keyword){
                # Don't search guest users in APP mode
                $result = User::searchUsers($keyword, APP, 20);
                if ( count($result) == 1 ){
                    Utils::redirect($page, array(
                        "username" => $result[0]['username'],
                        "keyword"  => $keyword
                    ));
                }else if(empty($result)){
                    echo "<style>#pleasewait{display:none;}</style><h3 style='padding:20px;margin:0px;'>No User Found..</h3>";
                }else{
        ?>
    	    <div id="admin-content">
    	        <ul>
    	        <?php 
    	        $makeSelected = "";
    	        // if there is only one result go to that user
    	        foreach ($result as $user):
    	        ?>
                    <li class="listUsers">
                        <a href="<?=$page?>?username=<?=urlencode($user['username'])?>&keyword=<?=urlencode($keyword)?>">
                            <div><?=$user['username']?></div>
                            <span><i>Account Type:</i><b><?=$user["account_type"]?></b><span>
                            &#8226;
                            <span><i>Status:</i><b><?=$user["status"]?></b></span>
                        </a>
                    </li>
                <?php
                endforeach;
                ?>
                </ul>
    	    </div>
        <?
                }
            }
        ?>
	</body>
</html>
