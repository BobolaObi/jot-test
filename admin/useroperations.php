<?php
    include_once "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="../css/style.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/prototype.js"></script>
        <title>Crawl Users</title>
        <style>
            #response{
                background:none repeat scroll 0 0 #FFFFFF;
                border:1px solid #CCCCCC;
                height:450px;
                overflow:auto;
                padding:10px;
                margin-top:15px;
            }
            #chunk{
                width:40px;
                padding:3px;
                font-size:12px;
            }
            #error{
                white-space:pre-wrap;
            }
            #classNames{
                padding:2px;
                font-size:12px;
            }
            .options > div{
                margin:10px 0;
            }
        </style>
        <script type="text/javascript">
        Event.observe(window, 'load', function(){
            var startButton = $('startOperate');
            Event.observe(startButton, 'click', function (){
                if ($('classNames').value.empty()){
                    alert("Please select operation.");
                    return;
                }
                if ($('username').value.empty()){
                    alert("Please enter username.");
                    return;
                }
            	window.parent.Admin.operateUser({
            	    className: $('classNames').value,
            	    username: $('username').value,
            	    callback: function(response){
            		   console.log(response);
            	    }
                });
            });
        });
        </script>
    </head>
    <body>
        <div style="padding:10px;">
            <div class="options">
                <div>
                    <label>
                        Username:
                        <input type="text" id="username" />
                    </label>
                </div>
                <div>
                    <label>
                        Run operations from
                        <select id="classNames">
                            <option value="">Please Select</option>
                            <option value="UploadToS3">Carry uploads to Amazon S3</option>
                            <option value="SyncAmazonUploads">Correct  the uploads to Amazon S3</option>
                        </select>
                        <button type="button" class="big-button buttons" id="startOperate">
                            Run Script
                        </button>
                    </label>
                </div>
            </div>
            <div id="response"></div>
        </div>
    </body>
</html>