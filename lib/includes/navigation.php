<?php

use Legacy\Jot\UserManagement\Session;



?>
<?
    if(isset($_GET['p'])){
        $pressed[$_GET['p']] = "navPressed";
    }
    $folder = INST_FOLDER;
?>
<ul id="nav" class="index-navbg">
    <li style="position:relative;" class="navItem <?= (isset($pressed['myforms'])? $pressed['myforms'] : '');?>"><a href="<?=$folder?>myforms/" class="locale">My Forms</a>
        <!--
        <div onclick="$('box-details').visible()? $('box-details').hide() : $('box-details').show();" style="-moz-box-shadow:1px 0 0 #585858 inset;border-left:1px solid #393636;float:right;height:22px;margin-left:4px;margin-right:-6px;margin-top:-10px;padding-top:14px;width:14px;"><img src="images/down-arrow.png" /></div>
        <div id="box-details" style="display:none;-moz-border-radius:5px 1px 5px 5px;-moz-box-shadow:0 7px 13px rgba(0, 0, 0, 0.8);background:none repeat scroll 0 0 #3E3E3E;border:1px solid #5B5454;color:#FFFFFF;height:159px;position:absolute;right:-2px;top:36px;width:174px;z-index:100000000;">
            Details.
        </div>
        -->
    </li>
    <? if(!APP){ ?>
    <li class="navItem <?= (isset($pressed['faq'])? $pressed['faq'] : '');?>"><a href="<?=$folder?>faq/" class="locale">FAQ</a></li>
    <li class="navItem <?= (isset($pressed['help'])? $pressed['help'] : '');?>"><a href="<?=$folder?>help/" class="locale">User Guide</a></li>
    <li class="navItem <?= (isset($pressed['forum'])? $pressed['forum'] : '');?>"><a href="<?=$folder?>answers/" class="locale">Forum</a></li>
    <li class="navItem <?= (isset($pressed['blog'])? $pressed['blog'] : '');?>"><a href="<?=$folder?>blog/" class="locale">Blog</a></li>
    
    <? if(false && Session::$accountType == 'PREMIUM'): ?>
        <li class="navItem navPremium <?= (isset($pressed['upgrade'])? $pressed['upgrade'] : '');?>" style="width:146px;position:relative;">
            <a href="<?=$folder?>upgrade_professional/?banner=premium_nav" class="locale">Pricing
                <!-- <img border="0" id="pricing-banner" align="center" src="images/banners/sale-button.png" />
                <img border="0" id="pricing-banner-hover" align="center" src="images/banners/sale-button-hover.png"/> -->
            </a>
        </li>
    <? elseif(Session::isBannerFree()): ?>
        <li class="navItem navPremium <?= (isset($pressed['upgrade'])? $pressed['upgrade'] : '');?>"><a href="<?=$folder?>pricing/" class="locale">Pricing</a></li>
    <? else: ?>
        <li class="navItem navPremium <?= (isset($pressed['upgrade'])? $pressed['upgrade'] : '');?>" style="width:146px;position:relative;">
            <a href="<?=$folder?>pricing/?banner=nav" class="locale">Pricing
		    <!-- <img border="0" id="pricing-banner" align="center" src="images/banners/sale-button.png" />
                 <img border="0" id="pricing-banner-hover" align="center" src="images/banners/sale-button-hover.png"/> -->
            </a>
        </li>
    <? endif; ?>
    
    <? } ?>
    <? if(Session::isAdmin() || Session::isSupport()){ ?>    
        <? if(!APP){ ?>
        <li class="navItem <?= (isset($pressed['ticket'])? $pressed['ticket'] : '');?>" id="tickets-link"><a href="ticket/" class="locale">Tickets</a></li>
        <? } ?>        
        <li class="navItem <?= (isset($pressed['admin'])? $pressed['admin'] : '');?>" id="admin-link"><a href="admin/" class="locale">Admin</a></li>        
    <? } ?>
</ul>