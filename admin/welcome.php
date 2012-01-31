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
            #status {
                display:none; /* Hide tracking code until we need it again */
                background:#f5f5f5;
                background:rgba(255, 255, 255, 0.3);
                background: #f7fbfc; /* old browsers */
                background: -moz-linear-gradient(top, #f7fbfc 0%, #d9edf2 40%, #add9e4 100%); /* firefox */
                background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f7fbfc), color-stop(40%,#d9edf2), color-stop(100%,#add9e4)); /* webkit */               
                text-align:left;
                width:310px;
                border:2px solid #aaa;
                -moz-border-radius:5px 5px;
                -webkit-border-radius:5px 5px;
                font-size:11px;
                padding-top:4px;
                display:inline-block;
                zoom: 1.5;
                -moz-transform: scale(1.5);
            }
            #status span{
                display: inline-block;
                text-indent: 8px;
            }
            #status > div{
                display:block;
                padding-left:5px;
                line-height:17px;
            }
            /*#status > div:nth-child(2n+1){
                background:#fff;
            }
            #status > div:nth-child(2n){
                background:#eee;
            }*/
            #status > div label{
                float:left;
                width:100px;
            }
            #status input{
                margin:5px;
            }
            .bar{
                -moz-border-radius: 10px 10px 10px 10px;
                -webkit-border-radius: 10px 10px 10px 10px;
                -moz-box-shadow:0 3px 4px rgba(164, 164, 164, 0.5) inset;
                -webkit-box-shadow:0 3px 4px rgba(164, 164, 164, 0.5) inset;
                background: none repeat scroll 0 0 #EEEEEE;
                border: 1px solid #AAAAAA;
                display: inline-block;
                margin: 3px;
                padding: 0;
                position: relative;
                top: 0;
                width: 190px;
            }
            .inner-bar{
                -moz-border-radius: 5px 5px 5px 5px;
                -webkit-border-radius: 5px 5px 5px 5px;
                background: -moz-linear-gradient(center top , #3998E3 11%, #2268C6 68%, #2268C6 100%) repeat scroll 0 0 transparent;
                background: -webkit-gradient(linear, left top, left bottom, color-stop(11%,#3998E3), color-stop(68%,#2268C6), color-stop(100%,#2268C6));
                color: white;
                font-size: 9px;
                height: 10px;
                line-height: 9px;
                text-align: center;
                text-indent: 4px;
                text-shadow: 0 0 1px #000000;
                width: 0;
                min-width:10px; /* to show zero length bar properly we have to sacrifice this */
            }
            #status {
                /* display:none; /* Hide tracking code until we need it again */
            }
        </style>
        <script>
            function secsToHuman(s){
                var str = "";
                if(s <= 0){
                    return "0 Seconds";
                }
                
                if(s < 1){
                    return Math.round(s*1000) + " Milliseconds";
                }
                
                s = Math.round(s);
                var d = parseInt(s/86400, 10);
                s -= d*86400;
            
                var h = parseInt(s/3600, 10);
                s -= h*3600;
            
                var  m = parseInt(s/60, 10);
                s -= m*60;
            
                if (d) str  = d + ' Days ';
                if (h) str += h + ' Hr. ';
                if (m) str += m + ' Min. ';
                if (s) str += s + ' Sec.';
            
                return str;
            }
            function getArraySum(arr){
                var sum=0;
                for(var x=0; x < arr.length; x++){
                    sum += arr[x];
                }
                return sum;
            }
            
            var stopInterval = false;
            var userTimePool = [];
            var userPoll = [];
            function checkStatus(){
                
                if(stopInterval){
                    stopInterval = false;                    
                    return;
                }
                setTimeout(function(){
                    var url = 'http://checkStatus.jotform.com/server.php';
                    
                    if(location.href.include("serkan")){
                        //url = "http://localhost/jotform3/server.php";
                    }
                    
                    // Add explanation
                    new Ajax.Jsonp(url, {
                        parameters: {
                            action:'getCrawlStatus'
                        },
                        force:true,
                        evalJSON:'force',
                        async: true,
                        onComplete: function(t){
                            var s = t.responseJSON;
                            if(!s){
                                console.log(s);
                                return;
                            }
                            try{
                                if(s.success){
                                                                        
                                    // This user still going on.
                                    if($A(userPoll).include(s.username)){
                                        checkStatus();                                        
                                        return;
                                    }
                                    userPoll.push(s.username);
                                    
                                    var userLeft = s.totalUsers - (parseInt(s.chunkStart, 10) + parseInt(s.index, 10));
                                    $('total-users').update(s.totalUsers+" Users / "+userLeft+" Left");
                                    $('current-user').update("<b>"+s.username+"</b>");
                                    
                                    var overall = Math.ceil(((parseInt(s.chunkStart, 10) + parseInt(s.index, 10)) * 100) / parseInt(s.totalUsers, 10)); 
                                    var chunk   = Math.ceil((parseInt(s.index, 10) * 100) / (parseInt(s.chunkEnd, 10) - parseInt(s.chunkStart, 10)));
                                    
                                    userTimePool.push(parseFloat(s.spend));
                                    var userAvg = getArraySum(userTimePool) / userTimePool.length;
                                    
                                    // Estimated time of arrival for chunk
                                    var chunkSize = s.chunkEnd - s.chunkStart;
                                    var cETA = (chunkSize - s.index) * userAvg;                                    
                                    var chunkETA = secsToHuman(cETA);
                                    $('chunk-etc').update(chunkETA);
                                    
                                    // Estimated time of arrival for overall process
                                    var oETA = userLeft * userAvg;
                                    var overallETA = secsToHuman(oETA);
                                    $('overall-etc').update(overallETA);
                                    
                                    
                                    $('usrTime').update(secsToHuman(userAvg));
                                    $('overall').shift({width:overall+'%', duration:0.5}).update(overall+'%');
                                    $('current-chunk').shift({width:chunk+'%', duration:0.5}).update(chunk+'%');
                                }
                            }catch(e){
                                console.error(e);
                            }
                            checkStatus();
                        }
                    });
                }, 1000);
            }
            function start(){
                $$('#status div').invoke('setOpacity', 1);
                stopInterval = false;
                checkStatus();
            }
            function stop(){
                $$('#status div').invoke('setOpacity', 0.5);
                stopInterval = true;                
            }
            document.ready(stop);
        </script>
		<title>Welcome to admin panel</title>
	</head>
	<body>
	    <div style="padding:100px;text-align:center;">
	        <h2>Welcome to JotForm Admin panel</h2>
	    </div>
        <? if( !APP && Session::isAdmin() ): ?>
        <center>
            <div id="status">
                <div><label>Current User:</label><span id="current-user">-</span></div>
                <div><label>Total Users:</label> <span id="total-users">0 Users</span></div>
                <div><label>User AVG:</label>    <span id="usrTime">0 Seconds</span></div>
                <div><label>Chunk ETA:</label>   <span id="chunk-etc">0 Seconds</span></div>
                <div><label>Overall ETA:</label> <span id="overall-etc">0 Seconds</span></div>
                <div><label>Over All Status:</label>
                    <div class="bar"><div class="inner-bar" id="overall" style="width:0%">0%</div></div>
                </div>
                <div><label>Current Chunk:</label>
                    <div class="bar"><div class="inner-bar" id="current-chunk" style="width:0%">0%</div></div>
                </div>
                <input type="button" class="big-button buttons buttons-grey" value="Start Tracking" style="float:left;" onclick="start();">
                <input type="button" class="big-button buttons buttons-grey" value="Stop Tracking" style="float:right;" onclick="stop();">
            </div>
        </center>
        <? endif; ?>
	</body>
</html>