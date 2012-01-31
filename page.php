<?php
    include_once 'lib/init.php';
    if(($requestedPage = Utils::get("p")) === false){
        $requestedPage = "";        
    }
    # make a language file download if the given page provides one
    Translations::downloadLanguageFile($requestedPage);
    
    header("Content-type: text/html; charset=utf-8");
    
    Session::handleIE6();
    
    if ($requestedPage == "signup" && Session::isLoggedIn()) {
        // Don't show the sign up page if the user is already logged in. Redirect
        // to myforms page.
        $requestedPage = "myforms";
    } else if ($requestedPage == "myaccount" && !Session::isLoggedIn() && !isset($_GET['upgraded'])) {
        // If he is a Guest user, do not shoe myaccount page.
    	Utils::redirect(HTTP_URL);
        $requestedPage = "myforms";	
    }
    
    $page = new Page($requestedPage);
    
    $formID = Utils::getCurrentID('form');
    $fullscreen = false;
    if($page->hasFullScreen()){
        $fullscreen = Utils::getCookie("fullscreen");
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=Translations::getShortLanguageCode() ?>" lang="<?=Translations::getShortLanguageCode() ?>">
    <head>
        <title><?=Configs::COMPANY_TITLE?> &middot; <? echo $page->getTitle(); ?></title>
        <? Utils::put("meta_tags"); ?>
        <base href="<?=HTTP_URL?>" />
        <!-- Styles -->
        <?php if (!COMPRESS_PAGE) { ?>
        <link rel="stylesheet" type="text/css" href="css/style.css?v3"/>
        <link rel="stylesheet" type="text/css" href="css/fancy.css?v3"/>
        <link rel="stylesheet" type="text/css" href="css/styles/form.css?v3" />
        <link type="text/css" rel="stylesheet" href="sprite/context-menu.css" />
        <link type="text/css" rel="stylesheet" href="sprite/toolbar.css" />
        <link type="text/css" rel="stylesheet" href="sprite/controls.css" />
        <link type="text/css" rel="stylesheet" href="sprite/index.css" />
        <link type="text/css" rel="stylesheet" href="sprite/toolbar-myforms.css" />
        <?php } else { // (JOTFORM_ENV == PRODUCTION) ?>
        <link type="text/css" rel="stylesheet" href="min/g=indexCss" />
        <?php } ?>
        <? if(APP){?>
        <link rel="stylesheet" type="text/css" href="css/application.css?v3"/>
        <? } ?>
        <style type="text/css"> #tool_bar{ z-index:1000; } </style>
        <?
            $page->putCSSIncludes();
        ?>
        <link rel="Shortcut Icon" href="/favicon.ico?12345" /> 
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
        <? Utils::usageTracking("head"); ?>
    </head>
    <body class="<?=Session::getTheme()?>">
        <div class="page<?=(DEBUGMODE? ' debug' : '')?> ">
        <div id="main" class="<?=($fullscreen? 'fullscreen' : 'main')?>">
            <div id="header">
                <div style="padding-top:7px;float:left;">
                    <a href="<?=HTTP_URL?>" title="Go to JotForm Home" style="text-decoration: none;">
                        <? if(!APP){ ?>
                            
                            <?
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
                                }*/
                            ?>
                            
                            <img alt="<?=$alt?>" id="logo-img" title="<?=$title?>" src="<?=$logo?>" align="left" style="border: none;"/>
                            
                        <? }else{ ?>
                            <!-- ::JotForm Application:: -->                                
                            <? if(Configs::COMPANY_LOGO){ ?>
                                <div class="app-logo app-img"><img src="<?=Configs::COMPANY_LOGO?>" alt="<?=Configs::COMPANY_TITLE?>" align="left" style="border: none;" /></div>
                            <? }else{ ?>
                                <div class="app-logo"><?=Configs::COMPANY_TITLE?></div>
                            <? } ?> 
                        <? } ?>
                    </a>
                    <? if(DEBUGMODE){ ?>
                        <img title="Debug Mode" class="ladybug" src="images/debug.png" />
                    <? } ?>
                </div>
                
                <?php
                    Utils::put("navigation");
                ?>
            </div>
            <? /*include "lib/includes/big-ribbon.php";*/ ?>
            <div id="content">
                <div class="glow">
                    <div class="glow-top"></div>
                    <div class="glow-mid" id="glow-mid"></div>
                    <div class="glow-bottom"></div>
                </div>
                <div class="title-bar index-title-bg" style="height:40px;">
                    <div class="title-cont">
                        <span id="form-title" class="locale"> <? echo $page->getTitle(); ?> </span>
                    </div>
                    <? if($page->hasFullScreen()): ?>
                    <img class="locale-img index-fs1" id="fullscreen-button" src="images/blank.gif" style="cursor:pointer;float:right;margin-right:7px;" onclick="Utils.screenToggle(this);" align="left" alt="&lowast;" title="Go Full Screen" />
                    <? endif; ?>
                </div>
                <!--
                
                <div id="tool_bar" style="">
                    
                    <div id="toolbox_handler" style="float:right; padding-top:50px;"></div>
                </div>
                <div style="clear:all;"> </div>
                
                <div id="right-panel">
                    
                </div>
                
                <div id="stage">
                    <img src="images/blank.gif" class="index-shadow" id="shadoww" onmousedown="return false;" onmousemove="return false;" style="position:absolute; float:left; left:-10px; top:0px; height:100%; width:10px;" alt="" />
                    -->
                    
                    <?
                        $page->putContent();
                    ?>

                <!--</div>-->
            </div>
        </div>
        <div id="footer">
            <? if((Session::isAdmin() || Session::isSupport()) && !APP): ?>
                <div id="themes-selector">
                    <span class="locale">Themes</span> <img src="images/block.png" align="top" />
                </div>
            <? endif; ?>
            <div class="footer-cont">
                <? include "lib/includes/big-ribbon.php"; ?>
                <?
                if (Utils::get('p') === "login" ){
                    Utils::put("footerLinks");
                }else{
                    Utils::put("footer_content");
                }
                ?>
            </div>
        </div>
        </div>
        <div id="loading-indicator" style="bottom:-40px;">
            <img src="images/small-ajax-loader.gif" align="absmiddle"> <span id="load-text" class="locale">Working...</span>
        </div>
        <? Utils::put('modes'); ?>
        <!-- Javascript loaded at the bottom for better performance -->
        <!-- Libraries -->
        <script src="js/prototype.js?v3" type="text/javascript"></script>
        <script src="js/protoplus.js" type="text/javascript"></script>
        <script src="js/common.js" type="text/javascript"></script>
        <!-- Translations scripts must be included after Prototype, English language should be included before other languages. -->
        <script src="js/locale/locale_en-US.js" type="text/javascript"></script>
        <?php 
            echo Translations::getJsInclude();
        ?>
        <script src="js/locale/locale.js" type="text/javascript"></script>
        <!-- Protoplus requires localization therefore it's inculed after locale.js -->
        <script src="js/protoplus-ui.js" type="text/javascript"></script>
        <?
            $page->putJSIncludes();
        ?>
        <? Utils::putAnalytics(Configs::ANALYTICS_CODE); ?>
        <? Utils::usageTracking("body"); ?>
        <script type="text/javascript">
            if (!navigator.cookieEnabled) {
            	CommonClass.alert('In order to use JotForm, you must enable <b>cookies</b> otherwise your work cannot be saved and you will lose all your changes.'.locale() + '</br>' +
                '<a target="_blank" href="http://www.google.com/support/websearch/bin/answer.py?hl=en&answer=35851"> ' +
                'For more information.'.locale() +
                '</a>');
            }
        </script>
    </body>
</html>
<?    
    Translations::translatePage();
?>
