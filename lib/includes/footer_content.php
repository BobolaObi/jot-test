<?php

use Legacy\Jot\SiteManagement\Translations;
use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\Utils\Utils;



?>
<div class="footer-box index-footer-back">
    <? if(!APP){ ?>
    <div class="footer-panel" id="footer-nav">
        <h4 class="footer-title locale">How It Works:</h4>
        <ol style="font-size:10px;list-style:inside decimal; margin:0px; padding:0px">
            <li class="locale">Drag &amp; drop fields to form</li>
            <!-- li class="locale">Save your form</li -->
            <li class="locale">Copy HTML to your web site</li>
            <li class="locale">Get responses by email</li>
        </ol>
        <div style="padding-top:4px">
            <button type="button" style="padding:2px; font-size:12px; width:100%;" class="big-button buttons buttons-red" onclick="Utils.openMovie()">
                <div style="line-height:20px;">
                    <img src="images/blank.gif" class="index-control_play_blue" align="top" alt="play" /> <span class="locale">Watch Movie</span>
                </div>
            </button>
        </div><h4 class="footer-title locale">No crippleware. No ads.</h4>
        <div class="locale">Unlike similar services JotForm does NOT <b>cripple features, show ads or show a logo</b> on your form. All features are available to Free users. You only need to upgrade to Premium version if you receive a lot of submissions on your forms.</div>
    </div>
    <div class="footer-panel" id="footer-info">
        
        <h3 class="footer-title locale">Why JotForm?</h3>
        <br/>
        <h4 class="footer-title locale">First and Always Ahead.</h4>
        <div class="locale">There was a time once when creating web forms was a big pain for webmasters. Then came JotForm, first web based WYSIWYG form builder, and turned into a joy. Today JotForm has <b>over 350,000 users</b>.</div>
        <h4 class="footer-title locale">Free.</h4>
        <div class="locale">JotForm is a free web form builder. For basic usage, upto 100 submissions/month, you can use JotForm fully. <b>No crippleware. No ads.</b> Just what you need.</div>
        <h4 class="footer-title locale">Fast.</h4>
        <div class="locale">When you need to create forms quickly, JotForm is your best friend. We don't waste your time with things like registration. Just point your browser to jotform.com, create your form and post it on your web site. Literally in minutes!</div>
        
        <!---
        <h4 class="footer-title locale">Reliable.</h4>
        <div class="locale">JotForm runs on cluster of load balanced servers. We spend a lot of time making sure your forms are served quickly and without any interruption. We are crazy paranoid when it comes to security of your data.</div>
        -->
    </div>
    <? }else{ ?>
        <div style="float:left;width:530px;">
            <div style="padding:20px;"><!-- Footer Content here --></div>
        </div>
    <? } ?>
    <div class="footer-panel" id="footer-account">
        <div id="myaccount" class="signin">
            <? Session::putLoginForm(); ?>
        </div>
        <div id="lang-bar">
            <!-- <label id="language-desc" class="locale">Language:</label> -->
            <?php echo Translations::getLangSelect(); ?>
        </div>
    </div>
</div>
<div style="visibility:hidden;">
    <span id="log">&nbsp;&nbsp;&nbsp;</span>
    <span id="diag">&nbsp;&nbsp;&nbsp;</span>
</div>
<?php 
Utils::put("footerLinks");
?>
