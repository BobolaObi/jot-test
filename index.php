<?php

use Legacy\Jot\Configs;

use Legacy\Jot\Form;
use Legacy\Jot\SiteManagement\Translations;
use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\Utils\Utils;

include_once './lib/init.php';

Form::handleSlugURLs();
header("Content-type: text/html; charset=utf-8");

$formID = Utils::getCurrentID("form");
$fullscreen = Utils::getCookie("fullscreen");
$hideRedo = "";

/*
Funnel::assignUser("Funnel");
Funnel::setGoal("First Visit", "Funnel");
*/

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=Translations::getShortLanguageCode() ?>" lang="<?=Translations::getShortLanguageCode() ?>">
<head>
    <title class="locale"><?=Configs::COMPANY_TITLE?> &middot; Easiest Form Builder</title>
    <?php Utils::put('meta_tags'); ?>
    <base href="<?=HTTP_URL?>"/><!-- Styles -->
    <?php if (!COMPRESS_PAGE) { ?>
        <link rel="stylesheet" type="text/css" href="css/style.css?v3"/>
        <link rel="stylesheet" type="text/css" href="css/fancy.css?v3"/>
        <link rel="stylesheet" type="text/css" href="css/styles/form.css?v3" />
        <link type="text/css" rel="stylesheet" href="sprite/context-menu.css" />
        <link type="text/css" rel="stylesheet" href="sprite/toolbar.css" />
        <link type="text/css" rel="stylesheet" href="sprite/controls.css" />
        <link type="text/css" rel="stylesheet" href="sprite/index.css" />
    <?php } else { // (JOTFORM_ENV == PRODUCTION) ?>
        <link type="text/css" rel="stylesheet" href="min/g=indexCss" />
    <?php } ?>
    <?php if(APP){ ?>
        <link rel="stylesheet" type="text/css" href="css/application.css?v3"/>
    <?php } ?>
    <link rel="stylesheet" type="text/css" id="formcss" href="css/styles/blank.css" />
    <link rel="Shortcut Icon" href="/favicon.ico?123456" />
    <!--[if lt IE 9]>
    <link rel="stylesheet" type="text/css" href="css/IE.css?v3"/>
    <![endif]-->
    <!--[if IE 9]>
    <link rel="stylesheet" type="text/css" href="css/IE9.css?v3"/>
    <![endif]-->
    <!--[if IE 7]>
    <link rel="stylesheet" type="text/css" href="css/IE7.css?v3"/>
    <![endif]-->
    <!--[if lt IE 7]->
        <link rel="stylesheet" type="text/css" href="css/IE6.css?v3" />
    <![endif]-->
    <?php Utils::usageTracking("head"); ?>
</head>
<body class="<?=Session::getTheme()?>">
<?php if(!APP){?>
    <div id="feedback-tab" style="display:none">
        <a id="feedback-tab-link">Feedback</a>
    </div>
<?php } ?>
<div class="page<?=(DEBUGMODE? ' debug' : '')?> ">
    <div id="main" class="<?=($fullscreen? 'fullscreen' : 'main')?>">
        <div id="header">
            <?php if( ! Session::isBannerFree()){ ?>
                <div id="logo-banner-cont" style="padding-top:7px;position:absolute;top:-55px;left:0px;">
                    <a href="pricing/?banner=logo">
                        <img src="images/banners/banner-logo.png" title="End of year sale 50% off" align="left" border="0">
                    </a>
                </div>
            <?php } ?>
            <div id="logo-cont" style="padding-top:7px;float:left;position:relative">
                <a href="<?=HTTP_URL?>" title="Go to JotForm Home" style="text-decoration: none;">
                    <?php if(!APP){?>
                        <?php
                        $logo  = "images/logo.png?v3";
                        $alt   = "Jotform - Easiest Form Builder";
                        $title = "Form Builder";
                        $newYear = 'Happy New Year';
                        /*
                        if(Utils::getCookie("logo") == "1"){
                            $logo  = "images/special_logos/logo-newyear-1.png";
                            $title = $newYear;
                            Utils::setCookie("logo", "2", "+1 Month");
                        }else if(Utils::getCookie("logo") == "2"){
                            $logo = "images/special_logos/logo-newyear-2.png";
                            $title = $newYear;
                            Utils::setCookie("logo", "3", "+1 Month");
                        }else if(Utils::getCookie("logo") == "3"){
                            $logo = "images/special_logos/logo-newyear-3.png";
                            $title = $newYear;
                            Utils::setCookie("logo", "1", "+1 Month");
                        }else{
                            $logo = "images/special_logos/logo-newyear-1.png";
                            $title = $newYear;
                            Utils::setCookie("logo", "2", "+1 Month");
                        }
                        */
                        ?>

                        <img alt="<?=$alt?>" id="logo-img" title="<?=$title?>" src="<?=$logo?>" align="left" style="border: none;"/>
                    <?php }else{ ?>
                        <!-- ::JotForm Application:: -->
                        <?php if(Configs::COMPANY_LOGO){ ?>
                            <div class="app-logo app-img"><img src="<?=Configs::COMPANY_LOGO?>" alt="<?=Configs::COMPANY_TITLE?>" align="left" style="border: none;" /></div>
                        <?php }else{ ?>
                            <div class="app-logo"><?=Configs::COMPANY_TITLE?></div>
                        <?php } ?>
                    <?php } ?>
                </a>
                <?php if(DEBUGMODE){ ?>
                    <img title="Debug Mode" class="ladybug" src="images/debug.png" />
                <?php } ?>
            </div>
            <?php
            Utils::put("navigation");
            ?>
        </div>
        <div id="content">
            <div class="glow">
                <div class="glow-top">&nbsp;</div>
                <div class="glow-mid" id="glow-mid">&nbsp;</div>
                <div class="glow-bottom">&nbsp;</div>
            </div>
            <div class="title-bar index-title-bg" style="height:40px;">
                <div class="title-cont">
                    <span id="form-title">&nbsp; </span>
                    <span id="title-hint" style="display:none"><img src="images/pencil.png" alt="" /> <span class="locale">Click title to edit.</span></span>

                </div>
                <img class="locale-img index-fs1" id="fullscreen-button" src="images/blank.gif" style="cursor:pointer;float:right;margin-right:7px;" onclick="Utils.screenToggle(this);" align="left" alt="&lowast;" title="Go Full Screen" />
                <button type="button" class="big-button buttons buttons-black myforms-button" onclick="location.href='/myforms/';">My Forms</button>
            </div>
            <div id="tool_bar" class="index-grad4" style="z-index:1000">
                <!--div style="background:url(images/ribbon.png); position:absolute; top:0px; height:90px; width:40px; left:-22px;" ></div>
                <div style="background:url(images/ribbon-right.png); position:absolute; top:0px; height:90px; width:40px; right:-22px;" ></div-->
                <div class="toolbar-set" id="group-form">
                    <button type="button" class="big-button" onclick="save();" id="saveButton" style="opacity:1;filter:alpha(opacity=100);" >
                        <img alt="" src="images/blank.gif" class="toolbar-save" id="saveIcon" align="top" />
                        <br/>
                        <span id="save_button_text" class="big-button-text locale">Save</span>
                    </button>
                    <button type="button" class="big-button" onclick="preview(this);" id="previewButton">
                        <img alt="" src="images/blank.gif" class="toolbar-preview" id="previewIcon" align="top" />
                        <br/>
                        <span class="big-button-text locale">Preview</span>
                    </button>

                    <?php
                    if(Utils::getTestVersion() == "a"){
                        $hideRedo = ' style="display:none"';
                        ?>
                        <button type="button" class="big-button" onclick="publishWizard();" id="">
                            <img alt="" src="images/success_small.png" id="" align="top" />
                            <br/>
                            <span class="big-button-text locale">Publish</span>
                        </button>
                    <?php } ?>

                    <?php
                    if(Utils::getTestVersion() == "s"){
                        $hideRedo = ' style="display:none"';
                        ?>
                        <button type="button" class="big-button" onclick="finishWizard();" id="">
                            <img alt="" src="images/right.png" dclass="toolbar-undo" id="" align="top" />
                            <br/>
                            <span class="big-button-text locale">Finish</span>
                        </button>
                    <?php } ?>

                    <button type="button" class="big-button" onclick="undo();" id="undoButton">
                        <img alt="" src="images/blank.gif" class="toolbar-undo" id="undoicon" align="top" />
                        <br/>
                        <span class="big-button-text locale">Undo</span>
                    </button>
                    <button type="button" class="big-button" onclick="redo();" id="redoButton" <?=$hideRedo?>>
                        <img alt="" src="images/blank.gif" class="toolbar-redo" id="redoicon" align="top" />
                        <br/>
                        <span class="big-button-text locale">Redo</span>
                    </button>
                    <div class="vline">
                        &nbsp;
                    </div>
                </div>
                <div style="position:relative; display:inline-block;">
                    <div class="toolbar-set" id="group-setup" style="display:none">
                        <button type="button" class="big-button" onclick="emailList(this);" id="emailButton">
                            <img alt="" src="images/blank.gif" class="toolbar-email_alerts" id="emailIcon" align="top" />
                            <br/>
                            <span class="big-button-text locale">Email Alerts</span>
                        </button>
                        <button type="button" class="big-button" onclick="Utils.loadScript('js/builder/redirect_wizard.js', function(){ RedirectWizard.init(); });" id="thanksButton">
                            <img id="redirect-img" alt="" src="images/blank.gif" class="toolbar-thank_page" align="top" />
                            <br/>
                            <span class="big-button-text locale">Thank You</span>
                        </button>
                        <?php if(Utils::getTestVersion() == 'sy'){ ?>
                            <button type="button" class="big-button" onclick="finishWizard('share');" id="embedButton">
                                <img alt="" src="images/blank.gif" class="toolbar-share_form_world" align="top" />
                                <br/>
                                <span class="big-button-text locale">Embed Form</span>
                            </button>
                        <?php }else{ ?>
                            <button type="button" class="big-button" onclick="sourceOptions('share');" id="embedButton">
                                <img alt="" src="images/blank.gif" class="toolbar-share_form_world" align="top" />
                                <br/>
                                <span class="big-button-text locale">Embed Form</span>
                            </button>
                        <?php }?>
                        <button type="button" class="big-button" onclick="sourceOptions('code');" id="sourceButton2" style="display:none">
                            <img alt="" src="images/blank.gif" class="toolbar-share_form_world" align="top" />
                            <br/>
                            <span class="big-button-text locale">Share Form</span>
                        </button>
                        <button type="button" class="big-button" onclick="sourceOptions('code');" id="sourceButton">
                            <img alt="" src="images/blank.gif" class="toolbar-code" align="top" />
                            <br/>
                            <span class="big-button-text locale">Source&nbsp;Code</span>
                        </button>
                        <button type="button" class="big-button" onclick="makeProperties($('stage'));" id="propButton">
                            <img alt="" src="images/blank.gif" class="toolbar-gear" align="top" />
                            <br/>
                            <span class="big-button-text locale">Preferences</span>
                        </button>
                        <button type="button" class="big-button" onclick="Utils.loadScript('js/builder/logic_wizard.js', function(){ LogicWizard.openWizard(); });" id="condButton">
                            <img id="logic-img" alt="" src="images/blank.gif" class="toolbar-cond" align="top" />
                            <br/>
                            <span class="big-button-text locale">Conditions</span>
                        </button>
                    </div>
                    <div class="toolbar-set" id="group-formproperties">
                    </div>
                    <div class="toolbar-set" id="group-properties" style="position:relative; display:none">
                        <div id="toolbar">
                        </div>
                    </div>
                    <div id="prop-tabs" class="index-tab-back-grad">
                        <div class="tab-legend tab-legend-open index-tab-legend-image locale" id="form-property-legend" onclick="makeTabOpen(this);">Form Style</div>
                        <div class="tab-legend locale" id="form-setup-legend" onclick="makeTabOpen(this);">Setup &amp; Embed</div>
                        <div class="tab-legend locale" id="prop-legend" style="display:none" onclick="makeTabOpen(this);">Question Properties</div>
                        <div id="toolbox_handler" style="float:right; padding-top:2px;"> </div>
                    </div>
                </div>
            </div>
            <div style="clear:both;overflow:hidden;height:0px;">
                &nbsp;
            </div>
            <div id="right-panel">
                <div id="tools-wrapper" style="">
                    <div id="accordion">
                        <div class="panel">
                            <div class="panel-bar index-grad6">
                                <img alt="" src="images/blank.gif" class="controls-tool_box" align="left"/><span class="locale">Form Tools</span>
                            </div>
                            <div class="panel-content panel-content-open" style="height:233px;">
                                <div class="panel-content-inner">
                                    <ul id="toolbox" class="tools">
                                        <li class="drags" type="control_head">
                                            <img alt="" src="images/blank.gif" class="controls-header" align="left" /><span class="locale">Heading</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_textbox" id="ctrl_text">
                                            <img alt="" src="images/blank.gif" class="controls-textbox" align="left" /><span class="locale">Text Box</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_textarea">
                                            <img alt="" src="images/blank.gif" class="controls-textarea" align="left" /><span class="locale">Text Area</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_dropdown">
                                            <img alt="" src="images/blank.gif" class="controls-dropdown" align="left" /><span class="locale">Drop Down</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_radio">
                                            <img alt="" src="images/blank.gif" class="controls-radiobutton" align="left" /><span class="locale">Radio Button</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_checkbox">
                                            <img alt="" src="images/blank.gif" class="controls-checkbox" align="left" /><span class="locale">Check Box</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_fileupload">
                                            <img alt="" src="images/blank.gif" class="controls-upload" align="left" /><span class="locale">File Upload</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_button">
                                            <img alt="" src="images/blank.gif" class="controls-button" align="left" /><span class="locale">Submit Button</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-bar index-grad6">
                                <img alt="" src="images/blank.gif" class="controls-quick_tools" align="left"/><span class="locale">Quick Tools</span>
                            </div>
                            <div class="panel-content">
                                <div class="panel-content-inner">
                                    <ul id="orgtools" class="tools">
                                        <li class="drags" type="control_fullname">
                                            <img alt="" align="left" src="images/blank.gif" class="controls-fullname" /><span class="locale">Full Name</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_email">
                                            <img alt="" align="left" src="images/blank.gif" class="controls-email" /><span class="locale">E-mail</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_address">
                                            <img alt="" align="left" src="images/blank.gif" class="controls-address" /><span class="locale">Address</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_phone">
                                            <img alt="" align="left" src="images/blank.gif" class="controls-phone" /><span class="locale">Phone</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_birthdate">
                                            <img alt="" align="left" src="images/blank.gif" class="controls-cake" /><span class="locale">Birth Date Picker</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_number">
                                            <img alt="" align="left" src="images/blank.gif" class="controls-number" /><span class="locale">Number</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>


                        <div class="panel">
                            <div class="panel-bar index-grad6">
                                <img alt="" src="images/blank.gif" class="controls-survey_tools" align="left"/><span class="locale">Survey Tools</span>
                            </div>
                            <div class="panel-content">
                                <div class="panel-content-inner">
                                    <ul id="surveytools" class="tools">
                                        <li class="drags" type="control_rating">
                                            <img alt="" src="images/blank.gif" class="controls-rating" align="left" /><span class="locale">Star Rating</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_scale">
                                            <img alt="" src="images/blank.gif" class="controls-scale" align="left" /><span class="locale">Scale Rating</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_grading">
                                            <img alt="" src="images/blank.gif" class="controls-grading" align="left" /><span class="locale">Grading</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_slider">
                                            <img alt="" src="images/blank.gif" class="controls-slider" align="left" /><span class="locale">Slider</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_range">
                                            <img alt="" src="images/blank.gif" class="controls-range" align="left" /><span class="locale">Range</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_spinner">
                                            <img alt="" src="images/blank.gif" class="controls-spinner" align="left" /><span class="locale">Spinner</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_matrix">
                                            <img alt="" src="images/blank.gif" class="controls-matrix" align="left" /><span class="locale">Matrix</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_collapse">
                                            <img alt="" src="images/blank.gif" class="controls-formcollapse" align="left" /><span class="locale">Form Collapse</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_pagebreak">
                                            <img alt="" src="images/blank.gif" class="controls-pagebreak" align="left" /><span class="locale">Page Break</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-bar index-grad6">
                                <img alt="" src="images/blank.gif" class="controls-power_tools" align="left"/><span class="locale">Power Tools</span>
                            </div>
                            <div class="panel-content">
                                <div class="panel-content-inner">
                                    <ul id="powertools" class="tools">
                                        <li class="drags" type="control_text">
                                            <img alt="" src="images/blank.gif" class="controls-text" align="left" /><span class="locale">Free Text</span> (HTML)
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_datetime">
                                            <img alt="" src="images/blank.gif" class="controls-datetime" align="left" /><span class="locale">DateTime</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_passwordbox">
                                            <img alt="" src="images/blank.gif" class="controls-password" align="left" /><span class="locale">Password Box</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_hidden">
                                            <img alt="" src="images/blank.gif" class="controls-hidden" align="left" /><span class="locale">Hidden Box</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_autoincrement">
                                            <img alt="" src="images/controls/autoincrement.png" class="controls-autoincrement" align="left" /><span class="locale">Unique ID</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>

                                        <li class="drags" type="control_captcha">
                                            <img alt="" src="images/blank.gif" class="controls-captcha" align="left" /><span class="locale">Captcha</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_image">
                                            <img alt="" src="images/blank.gif" class="controls-image" align="left" /><span class="locale">Image</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_autocomp">
                                            <img alt="" src="images/blank.gif" class="controls-autocomplete" align="left" /><span class="locale">Auto Complete</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-bar index-grad6">
                                <img alt="" src="images/blank.gif" class="controls-payment_tools" align="left"/><span class="locale">Payment Tools</span>
                            </div>
                            <div class="panel-content">
                                <div class="panel-content-inner">
                                    <ul id="paymenttools" class="tools">
                                        <li class="drags" type="control_paypal">
                                            <img src="images/blank.gif" class="controls-paypal" align="left" alt="PayPal" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_paypalpro">
                                            <img src="images/blank.gif" class="controls-paypalpro" align="left" alt="PayPalPro" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_authnet">
                                            <img src="images/blank.gif" class="controls-authnet" align="left" alt="Authorize.Net" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_googleco">
                                            <img src="images/blank.gif" class="controls-gcheckout" align="left" alt="Google Checkout" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_2co">
                                            <img src="images/blank.gif" class="controls-2co" align="left" alt="2 Checkout" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_clickbank">
                                            <img src="images/blank.gif" class="controls-clickbank" align="left" alt="ClickBank" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_worldpay">
                                            <img src="images/blank.gif" class="controls-worldpay" align="left" alt="WorldPay" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_onebip">
                                            <img src="images/blank.gif" class="controls-onebip" align="left" alt="OneBip" /><img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                        <li class="drags" type="control_payment">
                                            <span class="locale" style="padding-left:7px;color:#2B489C;font-size:12px;font-style:italic;font-weight:bold;text-shadow:0 1px 0 #FFFFFF;">Purchase Order</span>
                                            <img class="info locale-img toolbar-info_grey" src="images/blank.gif" alt="" align="right" />
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="style-menu" style="display:none">
                        <div class="panel">
                            <div class="panel-bar index-grad6">
                                <img class="controls-rgb" alt="" src="images/blank.gif" align="left"/><span class="locale">Themes</span>
                            </div>
                            <div class="panel-content" style="height:100%; z-index:10;">
                                <div class="panel-content-inner" id="style-content">
                                    Style here
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="properties">

                    </div>
                </div>
            </div>
            <div id="stage" class="form-all">
                <img src="images/blank.gif" class="index-shadow" id="shadoww" onmousedown="return false;" onmousemove="return false;" style="position:absolute; float:left; left:-10px; top:0px; height:100%; width:10px;" alt="" />
                <ul id="list" class="form-all form-section" style="min-height: 410px !important; overflow:visible !important;float:left;">
                    <li id="load-bar" style="display:none" class="locale">Loading.. Please wait.</li>
                </ul>
            </div>
        </div>
    </div>
    <div id="footer">
        <?php if((Session::isAdmin() || Session::isSupport()) && !APP): ?>
            <div id="themes-selector">
                <span class="locale">Themes</span> <img src="images/block.png" align="top" />
            </div>
        <?php endif; ?>
        <div class="footer-cont">
            <?php include "lib/includes/big-ribbon.php"; ?>
            <?php Translations::helpText(); ?>
            <?php Utils::put("footer_content"); ?>
        </div>
    </div>
</div>
<div id="loading-indicator" style="bottom:-40px;">
    <img src="images/small-ajax-loader.gif" align="top" alt="..." style="margin-top:2px;" /> <span id="load-text" class="locale">Working...</span>
</div>
<?php if(!APP){ Utils::put('intro2'); } ?>

<?php Utils::put('modes'); ?>

<!-- Javascript loaded at the bottom for better performance -->
<!-- Libraries -->
<?php if (!COMPRESS_PAGE) { ?>
    <script src="js/prototype.js?v3" type="text/javascript"></script>
    <script src="js/protoplus.js" type="text/javascript"></script>
    <script src="js/common.js" type="text/javascript"></script>
    <script src="js/effects.js" type="text/javascript"></script>
    <script src="js/dragdrop.js?v3" type="text/javascript"></script>
    <!-- Translations scripts must be included after Prototype, English language should be included before other languages. -->
    <script src="js/locale/locale_en-US.js" type="text/javascript"></script>
<?php
echo Translations::getJsInclude();
?>
    <script src="js/locale/locale.js" type="text/javascript"></script>
    <!-- Protoplus requires localization therefore it's inculed after locale.js -->
    <script src="js/protoplus-ui.js" type="text/javascript"></script>
    <!-- Will come from database, Must be loaded before formBuilder.js -->
    <!-- Necessary -->
    <script src="js/builder/formBuilder.js" type="text/javascript"></script>

    <script src="server.php?action=getSavedForm&formID=<?= $formID ?? "session" ?>&callback=getSavedForm" type="text/javascript"></script>
<?php /*
        <script src="http://www.jotform.com/server.php?action=getSavedForm&formID=3173415451&callback=getCloneForm" type="text/javascript"></script>
        */?>
    <script src="server.php?action=getLoggedInUser&callback=Utils.setUserInfo" type="text/javascript"></script>
    <!-- Not really necessary find a way to load them on demand -->
    <script src="js/builder/build_source.js" type="text/javascript"></script>
    <!-- All Configurations and Definitions -->
    <script src="js/builder/question_properties.js" type="text/javascript"></script>
    <script src="js/builder/question_definitions.js" type="text/javascript"></script>

    <!-- if the user has not logged in, include the loginForm.js as well. -->
<?php if (!Session::isLoggedIn()) { ?>
    <script src="js/includes/loginForm.js" type="text/javascript"></script>
<?php } ?>
    <script src="js/widgets/feedbackwidget.js" type="text/javascript"> </script>
    <script src="js/feedback.js" type="text/javascript"> </script>
<?php } else { ?>
    <script src="min/g=formBuilder_<?php echo Translations::getLanguageCode(); ?>" type="text/javascript"></script>

    <script src="server.php?action=getSavedForm&formID=<?= $formID ?? "session" ?>  &callback=getSavedForm" type="text/javascript"></script>

    <script src="server.php?action=getLoggedInUser&callback=Utils.setUserInfo" type="text/javascript"></script>
<?php if (!Session::isLoggedIn()) { ?>
    <script src="min/g=formBuilder3login" type="text/javascript"></script>
<?php } else { // No one logged in. ?>
    <script src="min/g=formBuilder3" type="text/javascript"></script>
<?php } ?>
    <script src="js/feedback.js" type="text/javascript"> </script>
<?php } ?>
<script src="js/tiny_mce/tiny_mce.js" type="text/javascript"></script>
<?php Utils::putAnalytics(Configs::ANALYTICS_CODE); ?>
<?php Utils::usageTracking("body"); ?>
</body>
</html>
<?php
Translations::translatePage();
ob_flush();
?>
