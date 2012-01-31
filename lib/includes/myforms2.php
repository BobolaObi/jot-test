<div id="tool_bar" class="index-grad4" style="">
     <div class="toolbar-set" id="group-myforms" style="margin-left:200px;">
        <button type="button" class="big-button" id="newButton" onclick="MyForms.newForm();">
            <img alt="" src="images/myforms/new/menu-new-form.png" align="top" /><br>
            <span class="big-button-text locale">New Form</span>
        </button>
        <button type="button" class="big-button" id="newFolderButton" onclick="MyForms.newFolder();">
            <img alt="" src="images/myforms/new/menu-new-folder.png" id="undoicon" align="top" /><br>
            <span class="big-button-text locale">New Folder</span>
        </button>
        <button type="button" class="big-button" id="trashButton" onclick="MyForms.toggleTrash();" style="display:none;">
            <img alt="" id="trashcan_icon" src="images/blank.gif" class="toolbar-myforms-trashcan_empty" align="top" /><br>
            <span class="big-button-text locale">Trash Can</span>
        </button>
    </div>
    
    <div class="toolbar-set" id="form-properties" style="position:relative; display:none">
        <div class="vline">&nbsp;</div>
        <button type="button" class="big-button" id="submissionButton" style="position:relative;" onclick="MyForms.openSubmissions();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-submissions" align="top" /><br>
            <span class="big-button-text locale">Submissions</span>
        </button>
        <button type="button" class="big-button" onclick="MyForms.openReports();" id="reportsButton" >
            <img alt="" src="images/blank.gif" class="toolbar-myforms-reports" align="top" /><br>
            <span class="big-button-text locale">Reports</span>
        </button>
    </div>
    
    <div class="toolbar-set" id="group-properties" style="position:relative; display:none">
	    <span style="display:none">
        <div class="vline">&nbsp;</div>
        <button type="button" class="big-button" id="previewButton" onclick="MyForms.previewForm();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-preview" id="previewIcon" align="top" /><br>
            <span class="big-button-text locale">Preview</span>
        </button>
	    <button type="button" class="big-button" onclick="MyForms.editForm();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-edit" align="top" /><br>
            <span class="big-button-text locale">Edit Form</span>
        </button>
        </span>
        <button type="button" class="big-button" id="undeleteButton" style="display:none" onclick="MyForms.undeleteForm();" >
            <img alt="" src="images/blank.gif" class="toolbar-myforms-undo" align="top"><br>
            <span class="big-button-text locale">Undelete Form</span>
        </button>
	
        <div class="vline" style="display:none">&nbsp;</div>
        <button type="button" class="big-button" id="deleteButton" onclick="MyForms.deleteForm();" style="display:none">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-delete" align="top"><br>
            <span class="big-button-text locale">Delete Form</span>
        </button>
    </div>  
    <div id="toolbox_handler" style="float:right; padding-top:50px;"></div>
    <div id="search-bar">
        <div class="big-button pressed locale" id="tab-main" style="width:64px;" onclick="MyForms.filter('main')">Main</div>
        <div class="vline"></div>
        <div style="float:left;" class="narrow-bar">
            <div class="big-button locale" id="tab-all" onclick="MyForms.filter('all')">All</div>
            <div class="big-button locale" id="tab-unread" onclick="MyForms.filter('unread')">Unread</div>
            <div class="big-button locale" id="tab-favs" onclick="MyForms.filter('favs')">Favorites</div>
            <div class="big-button locale" id="tab-trash" onclick="MyForms.filter('trash')">Trash</div>
        </div>
        <div style="">
            <div class="vline"></div>
            <div style="" >
                <input type="text" style="" id="myforms-searchbox">
            </div>
            <div class="vline"></div>
            <select id="myforms-sortby" style="float:left;">
                <option value="default" class="locale">Sort by</option>
                <option value="submission-date" class="locale">Last Submission</option>
                <option value="submission-count" class="locale">Submission Count</option>
                <option value="a-z" class="locale">Form Title A->Z</option>
                <option value="z-a" class="locale">Form Title Z->A</option>
                <option value="date" class="locale">Creation Date</option>
                <option value="last-edit" class="locale">Last Edit</option>
            </select>
        </div>
    </div>
</div>
<div style="clear:both;overflow:hidden;height:0px;">&nbsp;</div>

<div id="right-panel">
    
    
    <div id="tools-wrapper">
        <? if( ! Session::isBannerFree()): ?>
            <a href="pricing/?banner=myforms">
                <img id="banner-img" src="../../images/banners/last_days/banner-myforms-last-<?=Session::getLastDays();?>.png" style="left: -48px;position: absolute;top: -70px;z-index: 1000;" border="0" />
            </a>
            <div id="accordion" class="has-banner" style="margin-top:164px;">
        <? else: ?>
        <div id="accordion">
        <? endif; ?>
            <div class="panel">
                <div class="panel-bar index-grad6">
                   <img alt="" src="images/myforms/new/sidebar-account-status.png" align="left"/> 
                   <span class="locale">Account Status</span>
                </div>
                <div class="panel-content panel-content-open" style="height:auto;border-bottom:none;">
               
        			<? if(Session::getUser()->isLimitOver('70')){ ?>
                   	<div class="panel-limit-warning">
                    	<p>You're almost out of space!</p>
                    	<button id="upgradeNow" class="big-button">Upgrade to Premium</button>
                   	</div>
					<? } ?>

                    <div class="panel-content-inner" style="border-bottom:1px solid #DDDDDD;">
                        <div class="stats-table">
                        	<div class="stats-container" style="padding-top:0;border-top:none;">
                        		<img src="images/myforms/new/status-submissions.png" />
                        		<p class="locale">Submissions</p>
                                <div class="bar-out"><div class="bar-in" id="bar-submissions"></div></div>
                                <p><span id="usage-submissions">0</span> of <span id="limit-submissions">0</span> used</p>
                        	</div>
                        	<div class="stats-container">
                        		<img src="images/myforms/new/status-upload-space.png">
                        		<p class="locale">Upload space</p>
                                <div class="bar-out"><div class="bar-in" id="bar-uploads"></div></div>
                                <p><span id="usage-uploads">0</span> of <span id="limit-uploads">0</span> used</p>
                        	</div>
                        	<div class="stats-container">
                        		<img src="images/myforms/new/status-payments.png">
                        		<p class="locale">Payment submissions</p>
                                <div class="bar-out"><div class="bar-in" id="bar-payments"></div></div>
                                <p><span id="usage-payments">0</span> of <span id="limit-payments">0</span> used</p>
                        	</div>
                        	<div class="stats-container" style="padding-bottom:0;border-bottom:none;">
                        		<img src="images/myforms/new/status-ssl.png">
                        		<p class="locale">Secure SSL submissions</p>
                                <div class="bar-out"><div class="bar-in" id="bar-ssl"></div></div>
                                <p><span id="usage-sslSubmissions">0</span> of <span id="limit-sslSubmissions">0</span> used</p>
                        	</div>
						</div>
                        <!-- <table cellpadding="5" cellspacing="0" border="0" width="100%" class="stats-table" >
                            <tr>
                                <td width="26"><img src="images/myforms/new/status-submissions.png"></td>
                                <td>
                                    <span class="locale">Submissions</span>
                                    <div class="bar-out"><div class="bar-in" id="bar-payments"></div></div>
                                    <span><span id="usage-submissions">0</span> of <span id="limit-submissions">0</span> used</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><img src="images/myforms/new/sidebar-hline.gif"></td>
                            </tr>
                            <tr>
                                <td width="26"><img src="images/myforms/new/status-upload-space.png"></td>
                                <td>
                                    <span class="locale">Upload space</span>
                                    <div class="bar-out"><div class="bar-in" id="bar-uploads"></div></div>
                                    <span><span id="usage-uploads">0</span> of <span id="limit-uploads">0</span> used</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><img src="images/myforms/new/sidebar-hline.gif"></td>
                            </tr>
                            <tr>
                                <td width="26"><img src="images/myforms/new/status-payments.png"></td>
                                <td>
                                    <span class="locale">Payment submissions</span>
                                    <div class="bar-out"><div class="bar-in" id="bar-payments"></div></div>
                                    <span><span id="usage-payments">0</span> of <span id="limit-payments">0</span> used</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><img src="images/myforms/new/sidebar-hline.gif"></td>
                            </tr>
                            <tr>
                                <td width="26"><img src="images/myforms/new/status-ssl.png"></td>
                                <td>
                                    <span class="locale">Secure SSL Submissions</span>
                                    <div class="bar-out"><div class="bar-in" id="bar-ssl"></div></div>
                                    <span><span id="usage-sslSubmissions">0</span> of <span id="limit-sslSubmissions">0</span> used</span>
                                </td>
                            </tr>
                        </table>  -->
                    </div>
                    <? if(Session::$accountType == 'GUEST'): ?>
                    	<div class="account-notification-container">
                            <div id="account-notification">
                                You are using JotForm as a Guest User
                                <a href="signup/">Create a Free Account</a> or <a href="login/">Sign in</a> to your account. 
                            </div>
                    	</div>
                	<? endif; ?>
                </div>
            </div>
         </div>
         <? if(!APP){ ?>
         <div id="myforms-news">
             <div class="panel">                
             	<? if($t = Utils::getTweetStream()): ?>
                <div class="panel-bar index-grad6 tweetTitle">
                     <img alt="" src="images/twitter-black.png" align="left"/>
                     <span class="locale">What's new?</span>
                 </div>
                 <div class="panel-content panel-content-open" style="height:auto;border-bottom:none;">
                     <div class="panel-content-inner tweetPanel" style="text-align:center;">         
                         <div class="tweetPanel-inner">
                            <? echo $t; ?>
                         </div>
                         <div style="padding:12px 0 0" >
                         <a href="http://twitter.com/Jotform" class="big-button tweetFollowButton">Follow us on <img border="0" align="absmiddle" src="images/twitter-logotype2.png" alt="Follow Jotform on Twitter"/></a>
						 </div>
                 <? else: ?>
                 <div class="panel-bar index-grad6">
                     <img alt="" src="images/myforms/new/sidebar-recent-news.png" align="left"/>
                     <span class="locale">Recent News</span>
                 </div>
                 <div class="panel-content panel-content-open" style="height:auto;border-bottom:none;">
                     <div class="panel-content-inner tweetPanel" style="text-align:center;">
                        
                         <div style="padding:10px 0;">
                             <ul class="recent-news">
                                 <li>
                                     <a href="http://www.jotform.com/blog/23-Create-Forms-Faster-Templates-for-Your-Forms">
                                         <img src="images/myforms/new/templates-myforms-ad.jpg" border="0" alt="Form Templates" />
                                     </a>
                                 </li>
                                 <li>
                                     <a href="http://www.jotform.com/blog/11-JotForm-Reports-Turbocharged-">
                                         <img src="images/myforms/new/banner-reports.png" border="0" />
                                     </a>
                                 </li>
                                 <li>
                                     <a href="http://www.jotform.com/blog/7-How-to-Create-Facebook-Forms">
                                         <img src="images/myforms/new/banner-facebook-forms.png" border="0" />
                                     </a>
                                 </li>
                                 <li>
                                     <a href="http://www.jotform.com/blog/6-Story-of-a-Feature-JotForm-Feedback-Buttons-for-Web-Sites">
                                         <img src="images/myforms/new/banner-feedback.png" border="0" />
                                     </a>
                                 </li>
                             </ul>
                         </div>
                         <img src="images/myforms/new/sidebar-hline.gif">
						 <div style="padding:10px 0" >
                            <a href="http://twitter.com/Jotform"><img border="0" align="absmiddle" src="images/myforms/new/twitter-button-2.png" alt="Follow Jotform on Twitter"/></a>
						 </div>
                         <? endif; ?>
                     </div>
                 </div>
             </div>
         </div>
         <? } ?>
     </div>
</div>
<div id="stage">
    <img src="images/shadow.png" id="shadoww" onmousedown="return false;" onmousemove="return false;" style="position:absolute; float:left; left:-10px; top:0px; height:100%; width:10px;" alt="" />
    <div id="forms">
        
    </div>
    <div id="trash-container" style="display:none;">
        <div id="trash-bar">
            <span class="locale trash-text">Trash Can</span>
            
            <div style="float:right;">
                <button type="button" class="big-button buttons buttons-green locale" id="undelete-form" style="display:none" onclick="MyForms.undeleteForm();"> Restore Form </button>
                <button type="button" class="big-button buttons buttons-blood locale" id="permadelete-form" style="display:none" onclick="MyForms.deleteForm();"> Delete Form </button>
                <button type="button" class="big-button buttons buttons-dark locale" onclick="MyForms.emptyTrash();"> Empty Trash </button>
            </div>
        </div>
        <div id="forms-trash">
            
        </div>
    </div>
</div>
<? if(!APP){
   
    if(false){ # Disabled now
        $greetings = array(
            array(
                "link"   => "blog/26-Jotform-Now-Supports-Multiple-File-Uploads",
                "image"  => "images/banners/multiple-upload-not.png",
                "bottom" => "-250px"
            ), 
            array(
                "link"   => "blog/24-Upload-Spaces-Increased-10x-for-All-Users",
                "image"  => "images/banners/10xmorespace.png",
                "bottom" => "-260px"
            ),
            array(
                "link"   => "blog/28-Create-Dropbox-Forms-with-JotForm",
                "image"  => "images/banners/dropbox-notification-copy.png",
                "bottom" => "-290px"
            )
        );
        
        $cindex = Utils::getCookie('greetings') !== false? Utils::getCookie('greetings') : 0; 
        
        $index = ($cindex+1) % count($greetings);
        Utils::setCookie("greetings", $index, "+1 Month");
        
        $g = $greetings[$index];
        echo '<div id="greetings" style="bottom:'.$g['bottom'].';">';
        echo '<a href="'.$g['link'].'">';
        echo '<img src="'.$g['image'].'" border="0" />';
        echo '</a></div>';
    }
 } ?>
