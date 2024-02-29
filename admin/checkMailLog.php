<?php
    include_once "../lib/init.php";
    Session::checkAdminPages(true);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/buttons.css" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/prototype.js"></script>
        <script type="text/javascript" src="../js/protoplus.js"></script>
        <style>
            #main{
                border-collapse:collapse;
            }
            label{
                float:left;
                display:inline-block;
                margin:2px 10px 0px 0px;
            }
            #email, #getLast{
                padding:2px;
            }
            label span{
                display:block;
                font-size:10px;
                color:#666;
            }
            pre{
                white-space:pre-wrap;
            }
            .mark{
                color:blue;
                font-weight:bold;
            }
            .mark2{
                color:green;
                font-weight:bold;
            }
            .mark3{
                color:brown;
                font-weight:bold;
            }
        </style>
        <script>
            
            document.ready(function(){
                $('advanced').on('click', function(){
                    if($('advanced').checked){
                        $('details').show();
                    }else{
                        $('details').hide();
                    }
                });
                $('check').on('click', function(){
                    
                    if($('email').value.empty()){
                        $('output').update('<span style="color:red">Please Enter an E-mail first</span>');
                        return;
                    }
                    
                    if(!/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])/gim.test($('email').value)){
                        $('output').update('<span style="color:red">You must enter a valid e-mail address</span>');
                        return;
                    }
                    
                    if(!/\d+/.test($('getLast').value)){
                        $('output').update('<span style="color:red">Lines can only contain numbers</span>');
                        return;
                    }
                    function highLight(str){
                        str = str.escapeHTML();
                        str = str.replace(/(to\=)(\&lt\;.*?\&gt\;)/gim, '<span class="mark2">$1</span><span class="mark">$2</span>');
                        str = str.replace(/(\bsent\b.*$)/gim, '<span class="mark">$1</span>');
                        str = str.replace(/(\bstat\b)/gim, '<span class="mark2">$1</span>');
                        str = str.replace(/(\bstatus\b)/gim, '<span class="mark2">$1</span>');
                        // str = str.replace(/(\([\w\W]+\))/gim, '<span class="mark">$1</span>');
                        str = str.replace(/(^\w{3}\s+\d+\s+\d+\:\d+\:\d+\s)/gim, '<span class="mark2">$1</span>');
                        return str;
                    }
                    $('loader').show();
                    new Ajax.Request('../server.php',{
                        parameters:{
                            action:'grepMailLog',
                            email:$('email').value,
                            lines:$('getLast').value,
                            onlyErrors: $('onlyerror').checked,
                            toAll:true,
                            async:'no'
                        },
                        evalJSON:'force',
                        onComplete: function(t){
                            $('loader').hide();
                            var res = t.responseJSON;
                            
                            if(res.success){
                                // $('output').insert({ before: '<span class="mark3>Command:</span> ' + res.command + "\n\n" });
                                
                                var out = highLight(res.output || "No entry\n");
                                $('output').update('<span class="mark3">Current Server</span>: \n'+out);
                                
                                if(res.other_responses){
                                    $H(res.other_responses).each(function(pair){
                                        var out = highLight(pair.value.output || "No entry\n");
                                        $('output').insert('\n-----\n\n<span class="mark3">'+pair.key+"</span>: \n"+out);
                                    });
                                }
                                
                            }else{
                                $('output').update(res.error);
                            }
                        }
                    });
                });
            });
            
        </script>
        <title>Check Mail Log</title>
    </head>
    <body>
        <table width="100%" height="100%">
            <tr>
                <td align="center" valign="top" style="padding:30px">
                    
                    <table id="main" border="1" cellpadding="10" width="100%">
                        <tr>
                            <td>
                                <label><input id="email" type="text" value="<?=@$_GET['email']?>"><span>Email Address</span></label>
                                <span id="details" style="display:none;">
                                    <label><input id="getLast" type="text" size="3" value="1"><span>Lines</span></label>
                                    <label><input type="checkbox" id="onlyerror" /><span style="display:inline;">Only Errors</span> </label>
                                </span>
                                <input type="button" id="check" class="big-button buttons buttons-grey" value="Check Mail Log" />
                                <img src="../images/small-ajax-loader.gif" align="absmiddle" id="loader" style="display:none;" />
                                <label style="float:right"><input type="checkbox" id="advanced" /><span style="display:inline;">Advanced</span></label>
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#ffffff">
                                <pre id="output">
                                    
                                </pre>
                            </td>
                        </tr>
                    </table>
                    
                </td>
            </tr>
        </table>
    </body>
</html>