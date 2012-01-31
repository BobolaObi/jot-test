<?php
    include_once "../lib/init.php";
    Session::checkAdminPages();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="../css/style.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/fancy.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/includes/admin.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="../css/calendarview.css" rel="stylesheet" type="text/css" media="screen" />
        <script type="text/javascript" src="../js/prototype.js"></script>
        <script type="text/javascript" src="../js/protoplus.js"></script>
        <script type="text/javascript" src="../js/protoplus-ui.js"></script>
        <script type="text/javascript" src="../js/calendarview.js"></script>
        <title>Metrics</title>
        <style>
            .data-table{
                border-collapse:collapse;
            }
            .data-table td, .data-table th{
                border:1px solid #aaa;
            }
            .data-table td{
                font-size:14px;
            }
            .percent{
                color:#666;
                font-size:10px;
            }
            .control-table{
                border-collapse:collapse;
                background:url(../images/grad4.png);
            }
            .control-table td{
                border:1px solid #aaa;
            }
            #loading-text{
                font-size:14px;
                text-align:center;
                padding:2px;
            }
            .data-table tr:nth-child(odd)  { background-color:#f5f5f5; }
            .data-table tr:nth-child(even) { background-color:#fff; }    
            .data-table tr:first-child     { background:url("../images/grad4.png"); }
            #date-fields div{
                text-align:left;
                display:inline-block;
            }
            #date-fields input{
                padding:2px;
            }
            #date-fields div:first-child{
                margin-bottom:14px;
            }
            #date-fields label{
                float:left;
                width:50px;
            }
        </style>
        <script type="text/javascript">
            
            var tests = {};
            function deleteDataRow(elem){
                $(elem.parentNode.parentNode).remove();
            }
            function initMetrics(response){
                Calendar.setup({
                    dateField:"start-date"
                });
                Calendar.setup({
                    dateField:"end-date"
                });
                $A(response.tests).each(function(test){
                    $('testnames').insert(new Element('option').update(test.name));
                    tests[test.name] = test;
                    var sortedGoals={};
                    $A($H(tests[test.name].goals).sortBy(function(p){ return parseInt(p.value, 10) }).reverse()).each(function(sorted){
                        sortedGoals[sorted[0]] = sorted[1];
                    });
                    tests[test.name].goals = sortedGoals;
                });
                
                $('testnames').observe('change', function(){
                    $('groupnames').update('<option value="all">Every</option>');
                    $H(tests[$('testnames').value].groups).each(function(group){
                        $('groupnames').insert(new Element('option').update(group.key));
                    });
                    $('groupnames').bigSelect();
                });
                
                $('testnames').run('change');
                $('testnames').bigSelect();
                
                $('addnow').observe('click', function(){
                    $('loading-text').update('Loading... Please Wait.');
                    var testname = $('testnames').value;
                    
                    if(! $('result-table-'+testname)){
                        var table='<table class="data-table" cellpadding="5" cellspacing="0" width="100%" id="result-table-'+testname+'">';
                        table += '<tr><th width="20" title="Group">Grp</td>';
                        $H(tests[testname].goals).each(function(goal){
                            table += '<th>'+goal.key+'</th>';
                        });
                        table += '<th width="20" title="Total Participant">TP</th><th width="20">Del</th></tr></table>';
                        $('result').update(table);                        
                    }
                    
                    new Ajax.Request('../server.php',{
                        parameters:{
                            action:'getGoalInfoByDate',
                            goals: $H(tests[testname].goals).keys().join(","),
                            group: $('groupnames').value,
                            test:  $('testnames').value,
                            start: $('start-date').value + " 00:00:00",
                            end:   $('end-date').value + " 24:00:00"
                        },
                        evalJSON:'force',
                        onComplete: function(t){
                            try {
                                var res = t.responseJSON;
                                if(res.success){
                                    var description = "Group: "+$('groupnames').value+", Dates Between: "+$('start-date').value+' AND: '+$('end-date').value
                                    var row = '<tr title="'+description+'"><td align="center">'+$('groupnames').value.toUpperCase()+'</td>';
                                    var lastrow = $('result-table-'+testname).select('tr:last-child')[0];
                                    
                                    var max = res.participantTotal; //$H(res.goals).max(function(r){ return parseInt(r.value, 10); });
                                    
                                    $A($H(tests[testname].goals).keys()).each(function(key){
                                        var total   = res.goals[key] !== undefined? res.goals[key] : 0;
                                        var percent = "";
                                        var perc = (total*100)/max;
                                        if(!perc){
                                            perc = 0;
                                        }
                                        row += '<td align="center">' + total + '<div class="percent">'+ (perc).toFixed(2) +'%</div> </td>';
                                    });
                                    row += '<td align="center">' + max+' </td>';
                                    row += '<td align="center"><img src="../images/delete.png" onclick="deleteDataRow(this)" /></td></tr>';
                                    $('loading-text').update('&nbsp;');
                                    lastrow.insert({after:row});
                                }else{
                                    alert(res.error);
                                }
                            } catch (e) {
                            	alert(e);
                            }
                        }
                    });
                });
            }
        </script>
    </head>
    <body>
        <div style="padding:10px;">
            <table width="100%" height="90" cellpadding="7" cellspacing="0" style="font-size:14px;" class="control-table">
                <tr>
                    <td align="center">
                        View Metrics For<br><br><select id="testnames"></select>
                    </td>
                    <td align="center">
                        <div>
                            Show Group<br><br><select id="groupnames"></select>
                        </div>
                    </td>
                    <td align="center">
                        
                        <div id="date-fields">
                            <div>
                                <label>From:</label><input id="start-date" size="12" value="<?=date('Y-m-d', strtotime('-1 Week'))?>">                                
                            </div>
                            <br>
                            <div>
                                <label>To:</label><input id="end-date" size="12" value="<?=date('Y-m-d')?>">
                            </div>
                        </div>
                        
                    </td>
                    <td width="100" align="center">
                        <button id="addnow" style="height:66px; width:100px">
                            <img src="../images/toolbar/myforms/new.png" />
                            <br>
                            Add To List
                        </button>
                    </td>
                </tr>
            </table>
            <div id="loading-text">&nbsp;</div>
            <div id="result">
                <div style="text-align:center; padding:50px;">
                    Select your options then click "Add To List" button to see results
                </div>
            </div>
        </div>
        <script src="../server.php?action=getAllTestInformation&callback=initMetrics"></script>
    </body>
</html>