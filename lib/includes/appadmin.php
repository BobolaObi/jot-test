<?php

use Legacy\Jot\Utils\Settings;

# no idea why, but this doesn't work with the name RequestServer....


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
</style>
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
        <button class="big-button" onclick="Admin.makeSearch('*')">
            <img alt="" src="images/toolbar/admin/allusers.png" align="top" /><br />
            <span class="big-button-text locale">Show All Users</span>
        </button>
        <button class="big-button" onclick="Admin.openPage('createUser');">
            <img alt="" src="images/blank.gif" class="toolbar-admin-user" align="top" /><br>
            <span class="big-button-text locale">Create A User</span>
        </button>
        <button class="big-button" onclick="Admin.openPage('settings');">
            <img alt="" src="images/toolbar/gear.png" align="top" /><br>
            <span class="big-button-text locale">Settings</span>
        </button>
        <div class="vline"></div>
        <button class="big-button" onclick="Admin.openPage('codeEditor');">
            <img alt="" src="images/blank.gif" class="toolbar-admin-terminal" align="top" /><br>
            <span class="big-button-text locale">Code Editor</span>
        </button>
        <button class="big-button" id="admin-debug-mode" onclick="Admin.toggleDebugMode(this);">
            <img alt="" src="images/blank.gif" class="toolbar-admin-debug" align="top" /><br>
            <span class="big-button-text locale">Debug Mode</span>
        </button>
        <div class="vline"></div>
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
         </div>
     </div>               
</div>
<div id="stage">
    <img src="images/shadow.png" id="shadoww" onmousedown="return false;" onmousemove="return false;" style="position:absolute; float:left; left:-10px; top:0px; height:100%; width:10px;" alt="" />
    <div >
        <iframe src="admin/welcome.php" id="page" frameborder="0" allowtransparency="yes" style="border:none; width:699px; height:650px;"></iframe>
    </div>
</div>
