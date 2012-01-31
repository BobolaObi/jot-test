<div id="tool_bar" class="index-grad4" style="">
     <div class="toolbar-set" id="group-myforms">
        <button type="button" class="big-button" id="newButton" onclick="MyForms.newForm();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-new" align="top" /><br>
            <span class="big-button-text locale">New Form</span>
        </button>
        <button type="button" class="big-button" id="newFormButton" onclick="MyForms.newFolder();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-new_folder" id="undoicon" align="top" /><br>
            <span class="big-button-text locale">New Folder</span>
        </button>
        <button type="button" class="big-button" id="trashButton" onclick="MyForms.toggleTrash();">
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
	
        <div class="vline">&nbsp;</div>
        <button type="button" class="big-button" id="previewButton" onclick="MyForms.previewForm();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-preview" id="previewIcon" align="top" /><br>
            <span class="big-button-text locale">Preview</span>
        </button>
	    <button type="button" class="big-button" onclick="MyForms.editForm();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-edit" align="top" /><br>
            <span class="big-button-text locale">Edit Form</span>
        </button>
        <button type="button" class="big-button" id="undeleteButton" style="display:none" onclick="MyForms.undeleteForm();" >
            <img alt="" src="images/blank.gif" class="toolbar-myforms-undo" align="top"><br>
            <span class="big-button-text locale">Undelete Form</span>
        </button>
	
        <div class="vline">&nbsp;</div>
        <button type="button" class="big-button" id="deleteButton" onclick="MyForms.deleteForm();">
            <img alt="" src="images/blank.gif" class="toolbar-myforms-delete" align="top"><br>
            <span class="big-button-text locale">Delete Form</span>
        </button>
    </div>  
    <div id="toolbox_handler" style="float:right; padding-top:50px;"></div>
</div>
<div style="clear:both;overflow:hidden;height:0px;">&nbsp;</div>

<div id="right-panel">
    <div id="tools-wrapper">
        <div id="accordion">
            <div class="panel">
                <div class="panel-bar index-grad6">
                   <img alt="" src="images/wrench.png" align="left"/> 
                   <span class="locale">Account Status</span>
                </div>
                <div class="panel-content panel-content-open" style="height:auto;">
                    
                    <div class="panel-content-inner">
                       
                       <div class="bar-text"><span class="locale">Account Type:</span> <b><span id="account-type"></span></b></div>
                       
                       <? if(Session::getUser()->isLimitOver('70')){ ?>
                           <div style="padding:5px;text-align:center;color:red;">You are reaching monthly limit.</div>
                           <button id="upgradeNow" class="big-button buttons buttons-red" style="width:100%" type="button">Upgrade Your Account Now!</button>
                       <? }else{ ?>
                           <div id="account-notification"></div>
                           <br>
                       <? } ?>
                       
                       <div class="bar-container">
                           <div class="bar-text locale">Submissions</div>
                           <div class="bar-outer">
                               <div class="bar-inner" id="submissions"></div>
                               <div class="bar-stats" id="submissions-limit"></div>
                           </div>
                       </div>
                       <div class="bar-container">
                           <div class="bar-text locale">Upload Space</div>
                           <div class="bar-outer">
                               <div class="bar-inner" id="uploads"></div>
                               <div class="bar-stats" id="uploads-limit"></div>
                           </div>
                       </div>
                       <div class="bar-container">
                           <div class="bar-text locale">Payments</div>
                           <div class="bar-outer">
                               <div class="bar-inner" id="payments"></div>
                               <div class="bar-stats" id="payments-limit"></div>
                           </div>
                       </div>
                       <div class="bar-container">
                           <div class="bar-text locale">SSL Submissions</div>
                           <div class="bar-outer">
                               <div class="bar-inner" id="ssl">
                               </div>
                               <div class="bar-stats" id="sslSubmissions-limit"></div>
                           </div>
                       </div>
                       <div id="myforms-search" style="display:none">
                            <input type="text" id="search-forms" />
                       </div>
                    </div>
                </div>
            </div>
         </div>
         <? if(!APP){ ?>
         <div id="myforms-news">
             <div class="panel">
                 <div class="panel-bar index-grad6">
                     <img alt="" src="images/light-bulb.png" align="left"/><span class="locale">Recent News</span>
                 </div>
                 <div class="panel-content panel-content-open" style="height:auto;">
                     <div class="panel-content-inner">
                         <div style="padding:10px 0;">
				<a href="http://www.jotform.com/blog/11-JotForm-Reports-Turbocharged-">JotForm Reports, Turbocharged!</a>
			     <br /><br />
                             Cool new ways to use JotForm:
                             <ul class="recent-news">
                                 <li>
                                     <a href="http://www.jotform.com/blog/7-How-to-Create-Facebook-Forms">Create Facebook Forms</a>
                                 </li>
                                 <li>
                                     <a href="http://www.jotform.com/blog/6-Story-of-a-Feature-JotForm-Feedback-Buttons-for-Web-Sites">Add Feedback Button to your site</a>
                                 </li>
                             </ul>
                         </div>
						 <div >
                            <? if(false){ ?>
                                <iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.jotform.com&layout=button_count&show_faces=true&width=450&action=recommend&font=verdana&colorscheme=light&height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:21px;" allowTransparency="true"></iframe>
                            <? }else{ ?>
							    <div style="text-align:right">
                                    Follow us on <a href="http://www.twitter.com/Jotform"><img border="0" align="absmiddle" src="http://twitter-badges.s3.amazonaws.com/twitter-b.png" alt="Follow Jotform on Twitter"/></a>
							    </div>
                            <? } ?>
						 </div>
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
                <button type="button" class="big-button buttons buttons-black locale" id="undelete-form" style="display:none" onclick="MyForms.undeleteForm();"> Restore Form </button>
                <button type="button" class="big-button buttons buttons-black locale" id="permadelete-form" style="display:none" onclick="MyForms.deleteForm();"> Delete Form </button>
                <button type="button" class="big-button buttons buttons-black locale" onclick="MyForms.emptyTrash();"> Empty Trash </button>
            </div>
        </div>
        <div id="forms-trash">
            
        </div>
    </div>
</div>
