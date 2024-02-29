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
        <script type="text/javascript" src="../js/protoplus.js"></script>
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
            #status {
                background:#f5f5f5;
                background:rgba(255, 255, 255, 0.3);
                position:absolute;
                top:5px;
                right:10px;
                width:298px;
                height:143px;
                border:2px solid #aaa;
                -moz-border-radius:5px 5px;
                -webkit-border-radius:5px 5px;
                font-size:11px;
                padding-top:4px;
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
            #status > div:nth-child(2n+1){
                background:#fff;
            }
            #status > div:nth-child(2n){
                background:#DAFCFF;
            }
            #status > div label{
                float:left;
                width:93px;
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
        </style>
        <script>
            var stopNow = false;
            var stopInterval = false;
            function stopIt(noset){
                if(noset !== false){
                    stopNow = true;
                }
                $('stop-button').disabled = true;
                $('crawl-button').disabled = false;
                $('classNames').disabled = false;
            }
            
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
                
                if(str == ""){
                    console.log(s, "Cannot Calculate");
                    return "Cannot calculate";
                }
                return str;
            }
            
            var userTimePool = [];
            var userPoll = [];
            function checkStatus(){
                
                if(stopInterval){
                    stopInterval = false;
                    $('s-pause').setStyle('background:lightBlue');
                    $('s-start').setStyle('background:none');                    
                    return;
                }
                setTimeout(function(){
                    var url = 'http://checkStatus.jotform.com/server.php';
                    
                    if(location.href.include("serkan")){
                        url = "http://localhost/jotform3/server.php";
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
                            
                            $('s-pause').setStyle('background:none');
                            $('s-start').setStyle('background:lightBlue');
                            
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
                                    
                                    // How many users left to complete
                                    var userLeft = s.totalUsers - (parseInt(s.chunkStart, 10) + parseInt(s.index, 10));
                                    
                                    // User counts
                                    $('current-user').update("<b>"+s.username+"</b>");
                                    $('total-users').update(s.totalUsers+" Users / "+userLeft+" Left");
                                    
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
            
            function crawl(nocheck){
                
                $('classNames').removeClassName('error');
                $('error').update();
                
                if($('classNames').value == 'none'){
                    $('classNames').addClassName('error');
                    $('error').update('Please select an operation');
                    return;
                }
                
                if(stopNow){
                    stopNow = false;
                    stopInterval = true;
                    return;
                }
                $('classNames').disabled = true;
                $('crawl-button').disabled = true;
                $('stop-button').disabled = false;
                
                $('error').update();
                localStorage.currentOperation = $('classNames').value; 
                localStorage.currentChunk = parseInt($('chunk').value, 10);
				localStorage.chunkSize = parseInt($('chunkSize').value, 10);
				
                $('avgTime').update(secsToHuman(calculateAvg()));
                
                if(!nocheck){
                    checkStatus();
                }
                
                window.parent.Admin.crawlUsers({
                    chunk:     localStorage.currentChunk,
					chunkSize: localStorage.chunkSize,
                    className: localStorage.currentOperation,
                    callback: function(response){
                        if (response.success) {
                            $('chunk').value = parseInt(localStorage.currentChunk, 10) + parseInt(localStorage.chunkSize, 10);
                            var div = new Element('div');
                            div.innerHTML = "Previous crawl took: "+ response.duration +"ms<br>Currently crawling: " + $('chunk').value+" => "+(parseInt(localStorage.currentChunk, 10) + localStorage.chunkSize*2)+"...";
                            div.innerHTML += '<hr>';
                            $('response').insert({top:div});
                            localStorage.log = $('response').innerHTML;
                            
                            if(response.completed !== true){
                                crawl(true);
                            }else{
                                if(response.complete_message){
                                    window.parent.Utils.alert(response.complete_message);
                                }else{
                                    window.parent.Utils.alert('Completed');
                                }
                                stopIt(true);
                            }
                        } else {
                            $('error').update(response.error);
                            stopIt(true);
                        }
                    }
                });
            }
            
            function resetPage(nocheck){
                if(nocheck === true || confirm("Are you sure you want to reset the page? You will lose all saved process")){
                    $('response').innerHTML = "";
                    $('chunk').value = 0;
                    localStorage.log = "";
                    localStorage.currentChunk = 0;
					localStorage.chunkSize = 25;
                    localStorage.currentOperation = 'none';
                    $('classNames').value = 'none';
				    $('chunkSize').value = 25;
                    $('classNames').disabled = false;
                    $('crawl-button').disabled = false;
                    $('stop-button').disabled = true;
                    $('error').update();
                    stopNow = false;
                    stopInterval = false;
                }
            }
            
            function getArraySum(arr){
                var sum=0;
                for(var x=0; x < arr.length; x++){
                    sum += parseFloat(arr[x]);
                }
                return sum;
            }
            
            function calculateAvg() {
                var sum = 0;
                var timeArr = $A(localStorage.log.match(/\s(\d+\.\d+)ms/gim)).map(function(numStr) { return parseFloat(numStr); });
                $A(timeArr).each(function(t) { sum += t; });
                var avg = sum / timeArr.length;
                if(!avg){
                    avg = 0;
                }
                
                userTimePool = [((avg / parseInt(localStorage.chunkSize, 10)) || 0).toFixed(2)];
                
                return avg.toFixed(2);
            }
            
            document.observe('dom:loaded', function(){
                
                if (!("log" in localStorage)){
                	resetPage(true);
                }
                
                $('classNames').value       = localStorage.currentOperation || "none";
                $('response').innerHTML     = localStorage.log || "";
                $('chunk').value            = localStorage.currentChunk || 0;
				$('chunkSize').value        = localStorage.chunkSize || 25;
                $('classNames').disabled    = false;
                $('stop-button').disabled   = true;
                $('crawl-button').disabled  = false;
            });
            
        </script>
	</head>
	<body>
	    <div style="padding:10px; position:relative;">
            <div class="options" >
    	        <div>
                    <label>
        	            Enter a chunk point to start <input type="text" id="chunk" value="0">
                        <span id="error"></span>
        	        </label>
    	        </div>
				<div>
                    <label>
                        How many users should be crawled at each chunk? <input type="text" id="chunkSize" value="25" size="5">
                    </label>
                </div>
                <div>
                    <label>
                        Run operations from
                        <select id="classNames">
                            <option value="none">Please Select</option>
                            <option value="UploadToS3">Carry uploads to Amazon S3</option>
                            <option value="Mailing">Send Announcement Emails</option>
                            <option value="MailingSingle">Send Upload Emails</option>
                        </select>
                    </label>
                </div>
                <div>
                    <button type="button" class="big-button buttons" id="crawl-button" onclick="crawl()">Crawl Users ➡</button>
                    <button type="button" class="big-button buttons" id="stop-button" onclick="stopIt(this)">Stop ■</button>
                    <button type="button" style="position:relative; right:-153px" class="big-button buttons buttons-red" onclick="resetPage()">Reset</button>
                </div>
            </div>
            <div id="status">
                
                <img src="../images/control-pause.png" id="s-pause" onclick="stopInterval=true;" title="Stop Tracking" style="position:absolute; top:5px; right:20px;" />
                
                <img src="../images/control.png" id="s-start" onclick="stopInterval=false; checkStatus();" title="Start Tracking"  style="position:absolute; top:5px; right:2px;" />
                
                <div><label>Current User:</label><span id="current-user">-</span></div>
                <div><label>Total Users:</label> <span id="total-users">0 Users</span></div>
                <div><label>User AVG:</label>    <span id="usrTime">0 Seconds</span></div>
                <div><label>Chunk AVG:</label>   <span id="avgTime">0 Seconds</span></div>
                <div><label>Chunk ETA:</label>   <span id="chunk-etc">0 Seconds</span></div>
                <div><label>Overall ETA:</label> <span id="overall-etc">0 Seconds</span></div>
                <div><label>Over All Status:</label>
                    <div class="bar"><div class="inner-bar" id="overall" style="width:0%">0%</div></div>
                </div>
                <div><label>Current Chunk:</label>
                    <div class="bar"><div class="inner-bar" id="current-chunk" style="width:0%">0%</div></div>
                </div>
            </div>
            <div id="response"></div>
	    </div>
	</body>
</html>