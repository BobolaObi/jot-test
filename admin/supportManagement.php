<?php 
	include "../lib/init.php";
	Session::checkAdminPages(true);
	$users = User::getAdminAndSupportUsers();
	$totalData = array();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Support Management</title>
        <link href="../css/includes/admin.css?<?=VERSION?>" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/buttons.css?<?=VERSION?>" rel="stylesheet" type="text/css" media="screen" />
        <script src="../js/prototype.js?<?=VERSION?>" type="text/javascript"></script>
        <script src="../js/protoplus.js?<?=VERSION?>" type="text/javascript"></script>
        <script src="../js/common.js?<?=VERSION?>" type="text/javascript"></script>
        <style type="text/css">
            .removeButton{
                background-color: red;
            }
        </style>
        <script type="text/javascript">
        var Utils = Utils || new Common();
        function downgradeSupporter(username){
            Utils.Request({
                server: "../jcm/jcm_server.php",
                parameters:{
                    action: "downgradeSupporter",
                    username: username
                },
                onComplete: function (res){
                    Utils.Request({
                        server: "../server.php",
                        parameters: {
                            action: 'setAccountType',
	                        username: username,
	                        accountType: 'FREE'
                        }
                    });
                }
            });
        }
        function addSupporter(){
        	var username = prompt("Please enter the username: ", "");
        	Utils.Request({
                server: "../jcm/jcm_server.php",
                parameters:{
                    action: "addSupporter",
                    username: username
                },
                onComplete: function (res){
                    Utils.Request({
                        server: "../server.php",
                        parameters: {
                            action: 'setAccountType',
	                        username: username,
	                        accountType: 'SUPPORT'
                        }
                    });
                }
            });
        }
        </script>
    </head>
    <body style="background: #eee;">
        <table>
            <thead>
                <tr>
	                <th>Username</th>
	                <th>Posts last 24 Hours</th>
	                <th>Posts last 1 Week</th>
	                <th>Total Posts</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($users as $user):
                $totalData = array_merge_recursive($totalData, $user->userData);
                ?>
                <tr>
                    <td><?=$user->username?></td>
                    <td><?=$user->userData['lastDayPostCount']?></td>
                    <td><?=$user->userData['lastWeekPostCount']?></td>
                    <td><?=$user->userData['totalCount']?></td>
                    <td>
                    <?php if($user->accountType === "SUPPORT"): ?>
                    <button class="removeButton" onclick="downgradeSupporter('<?=addslashes($user->username)?>');">Remove Supporter</button>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td><?=array_sum($totalData['lastDayPostCount'])?></td>
                    <td><?=array_sum($totalData['lastWeekPostCount'])?></td>
                    <td><?=array_sum($totalData['totalCount'])?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="5"><button class="addButton" onclick="addSupporter();">Add New Supporter</button></td>
                </tr>
            </tfoot>
        </table>
    </body>
</html>
