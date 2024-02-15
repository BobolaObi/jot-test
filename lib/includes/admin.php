<?php

use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\Utils\Server;
use Legacy\Jot\Utils\Settings;

# no idea why, but this doesn't work with the name RequestServer....


?>
<?
    if(APP){
        include "appadmin.php";
    }else{
?>
<style>
    body{
        background-position:0 0;
    }
    .tools li{
        cursor:default;
        padding-left:0px !important;
    }
    .tools button{
        width:180px;
    }
    .tools li:hover{
        border:1px solid transparent;
    }
    #tool_bar .big-button{
        float:left;
    }
    
    .search_box{
        float:left;
        height:33px;
        
        border:1px solid #999;
        border-top:1px solid #666;
        background:#fff;
        font-size:17px;
        width:180px;
        margin:10px 10px;
        padding:5px;
        text-shadow:none;
    }
    #page{
        border:none;
        width:699px;
        height:650px;
    }
</style>

<?php 
if (Session::isSupport()):
?>
<style>
#stage{
    float: none;
}
#shadoww{
    display: none;
}
#page{
    width:899px;
}
</style>
<?php
endif;
?>

<script>
    document.getElementById('form-title').innerHTML += ': <?=Server::whoAmI();?>';
</script>
<div id="tool_bar" class="index-grad4" style="">
    
    <div class="toolbar-set" id="action-properties">
        <input type="text" autocomplete="off" id="searchbox" class="search_box">
        <button class="big-button" onclick="Admin.makeSearch()">
            <img alt="" src="images/blank.gif" class="toolbar-preview" align="top" /><br>
            <span class="big-button-text locale">Search</span>
        </button>
    </div>
    
    <div class="toolbar-set" id="action-properties">
        <div class="vline"></div>
        <button class="big-button" onclick="Utils.redirect('/admin/checkPhishing.php',{target:'_blank'} );">
            <img alt="" src="images/blank.gif" class="toolbar-admin-spam" align="top" /><br>
            <span class="big-button-text locale">Check Phishing</span>
        </button>
        <?php 
        if (Session::isAdmin()):
        ?>
        <button class="big-button" onclick="Admin.openPage('metrics');">
            <img alt="" src="images/toolbar/general/chart.png" align="top" /><br>
            <span class="big-button-text locale">Metrics</span>
        </button>
        <button class="big-button" onclick="Admin.openPage('createUser');">
            <img alt="" src="images/blank.gif" class="toolbar-admin-user" align="top" /><br>
            <span class="big-button-text locale">Create A User</span>
        </button>
        <button class="big-button" onclick="Admin.openPage('codeEditor');">
            <img alt="" src="images/blank.gif" class="toolbar-admin-terminal" align="top" /><br>
            <span class="big-button-text locale">Code Editor</span>
        </button>
        <button class="big-button" onclick="Admin.openPage('ruckusing');">
            <img alt="" src="images/blank.gif" class="toolbar-admin-db" align="top" /><br>
            <span class="big-button-text locale">Ruckusing</span>
        </button>
        <button class="big-button" onclick="Admin.openPage('cssSprite');">
            <img alt="" src="images/blank.gif" class="toolbar-admin-csssprite" align="top" /><br>
            <span class="big-button-text locale">CSS Sprite</span>
        </button>
        <button class="big-button" id="admin-debug-mode" onclick="Admin.toggleDebugMode(this);">
            <img alt="" src="images/blank.gif" class="toolbar-admin-debug" align="top" /><br>
            <span class="big-button-text locale">Debug Mode</span>
        </button>
        <button class="big-button" id="enlarge-button" onclick="Admin.togglePanelSize();">
            <img src="../images/arrow_undo.png" style="vertical-align:bottom"/><br/>
            Enlarge
        </button>
        <div class="vline"></div>
        <?php else: ?>
        <button class="big-button" id="checllog-button" onclick="Admin.openPage('checkMailLog');">
            <img src="../images/toolbar-myforms/submissions.png" style="vertical-align:bottom"/><br/>
            <span class="big-button-text">Check Mail Log</span>
        </button>
        <? endif; ?>
        <button class="big-button" onclick="Admin.logout();">
            <img alt="" src="images/blank.gif" class="toolbar-admin-logout" align="top" /><br>
            <span class="big-button-text locale">Logout</span>
        </button>
    </div>
               
    <div id="toolbox_handler" style="float:right; padding-top:50px;"></div>
</div>
<div style="clear:both;"> </div>

<div id="right-panel">
    <div id="tools-wrapper">
        <div id="accordion">
            
            <?php if (Session::isAdmin()): ?>
	        
            <div class="panel">
                <div class="panel-bar index-grad6">
                   <img alt="" src="images/wrench.png" align="left"/> 
                   <span class="locale">System Actions</span>
                </div>
                <div class="panel-content">
                    <div class="panel-content-inner">
                        <ul id="toolbox" class="tools">
                            <li>
                               <button class="big-button buttons" id="clear-cache-button" onclick="Admin.clearAllCache();">Clear All Cache</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.buildNow();">Deploy a New version</button>
                            </li>
                            <li>
                                <button class="big-button buttons" onclick="Admin.getServerList();">Server List</button>
                            </li>
                            <li>
                                <button class="big-button buttons" onclick="Admin.getApplication();">Download Application</button>
                            </li>
                            <li>
                                <button class="big-button buttons" onclick="Admin.openPage('checkMailLog');">Check Mail Log</button>
                            </li>
                            <li>
                                <button class="big-button buttons" onclick="Admin.openPage('supportManagement');">Support Management</button>
                            </li>
                        </ul>
                        <div style="text-align:center;display:none;" id="last-cache-date">
                            <?
                                $date = Settings::getSetting("admin", "lastCacheClearDate");
                                if($date){ echo "Last cache clear date is<br> ".$date['value']; }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-bar index-grad6">
                   <img alt="" src="images/wrench.png" align="left"/> 
                   <span class="locale">User Actions</span>
                </div>
                <div class="panel-content">
                    <div class="panel-content-inner">
                        <ul id="toolbox" class="tools">
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('crawlusers')">Crawl Users</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('useroperations')">User Operations</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('migrateall')">Migrate All</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('migrate')">Migrate User</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('migrateallsubmissions')">Migrate All Submissions</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('complete_pending_uploads')">Complete Pending Uploads</button>
                            </li>
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('export_sql_user')">Export SQL of User</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-bar index-grad6">
                   <img alt="" src="images/wrench.png" align="left"/> 
                   <span class="locale">Translations</span>
                </div>
                <div class="panel-content">
                    <div class="panel-content-inner">
                        <ul id="toolbox" class="tools">
                            <li>
                               <button class="big-button buttons">Check suggestions</button>
                            </li>
                            <li>
                               <button class="big-button buttons">Release a translation</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-bar index-grad6">
                   <img alt="" src="images/wrench.png" align="left"/> 
                   <span class="locale">Payment Scripts</span>
                </div>
                <div class="panel-content">
                    <div class="panel-content-inner">
                        <ul id="toolbox" class="tools">
                            <li>
                               <button class="big-button buttons" onclick="Admin.openPage('payment_scripts/downgrade_script');">Downgrade Scheduled Users</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
         </div>
     </div>               
</div>
<div id="stage">
    <img src="images/shadow.png" id="shadoww" onmousedown="return false;" onmousemove="return false;" style="position:absolute; float:left; left:-10px; top:0px; height:100%; width:10px;" alt="" />
    <div >
        <iframe src="admin/welcome.php" id="page" frameborder="0" allowtransparency="yes"></iframe>
    </div>
</div>
<? } ?>