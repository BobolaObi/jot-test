<?php

use Legacy\Jot\Utils\Utils;


?>
<?
    $reportID = Utils::getCurrentID('report');
?>
<div id="tool_bar" class="index-grad4" style="">
    <div class="toolbar-set" id="report-save-group">
        <button class="big-button" id="saveButton" onclick="Reports.save();">
            <img alt="" src="images/blank.gif" class="toolbar-save" align="top" id="saveButton-icon" />
            <br>
            <span class="big-button-text locale" id="saveButton-text">Save Report</span>
        </button>
        <button class="big-button" id="printButton" onclick="Reports.print();">
            <img alt="" src="images/toolbar/general/print.png" align="top" />
            <br>
            <span class="big-button-text locale">Print Report</span>
        </button>
        <button class="big-button" id="printButton" onclick="Reports.share();" style="max-width:100px;">
            <img alt="" src="images/toolbar/share_form_world.png" align="top" />
            <br>
            <span class="big-button-text locale">Share Report</span>
        </button>
    </div>
    <div class="toolbar-set" id="report-group" style="float:right">
        <div class="vline">
        </div>
        <button class="big-button" id="editButton" onclick="Reports.toggleEdit();">
            <img alt="" src="images/toolbar/general/edit.png" align="top" />
            <br>
            <span class="big-button-text locale">Edit Report</span>
        </button>
    </div>
    <div id="toolbox_handler" style="float:right; padding-top:50px;">
    </div>
</div>
<div style="clear:both;">
</div>
<div id="reports-content">
    <div id="report-stage" class="finished-mode">
    </div>
</div>
<div id="report-tools" style="display:none">
    <ul>
        <li class="drag locale" value="header">
            <img src="images/blank.gif" class="controls-header" align="absmiddle" /> Header
        </li>
        <li class="drag locale" value="text">
            <img src="images/blank.gif" class="controls-text" align="absmiddle" /> Text
        </li>
        <li class="drag locale" value="arrow">
            <img src="images/blank.gif" class="controls-arrow" align="absmiddle" /> Arrow
        </li>
        <li class="drag locale" value="image">
            <img src="images/blank.gif" class="controls-image" align="absmiddle" /> Image
        </li>
        <li class="drag locale" value="chart">
            <img src="images/blank.gif" class="controls-charts" align="absmiddle" /> Charts
        </li>
        <li class="drag locale" value="grid">
            <img src="images/blank.gif" class="controls-matrix" align="absmiddle" /> Grid Listing
        </li>
    </ul>
</div>
