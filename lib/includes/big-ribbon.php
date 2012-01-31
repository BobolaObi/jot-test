<? 

    if(!Session::isBannerFree()){
        
?>
<div id="total-banner-container">
    <!--[if gt IE 7]>
    <style>
    #footer{
        height:450px;
    }
    #main{
        padding-bottom:450px;
    }
    .footer-box {
        height:300px;
    }
    .footer-box > div {
        padding:10px 0;
    }
    
    </style>
    <![endif]-->
    <style type="text/css">
        
        #footer {
            height: 400px !important;
        }
        
        .main, #footer-content {
            padding-bottom: 410px !important;
        }
        
        #banner-text a {
            text-decoration: underline;
            color: white;
        }
        
        #remove-messsage {
            margin: 5px;
            font-size: 12px;
            cursor: pointer;
            top: 2px;
            right: 2px;
            position: absolute;
            display: none;
        }
        
        #banner-text:hover #remove-messsage {
            display: block;
        }
        
        #banner-text {
            position: relative;
            height: 90px !important;
            margin: 0 auto -15px;
            text-align: center;
            top: 0;
        }
        #bann-2{
            display:none;
        }
        #banner-link:hover #bann-1{
            display:none;
        }
        #banner-link:hover #bann-2{
            display:inline;
        }
    </style>
    <div id="banner-text" class="footer-box index-footer-back">
        <a href="pricing/?banner=footer" id="banner-link">
            <img id="bann-1" src="images/banners/last_days/banner-<?=Session::getLastDays();?>.png" style="margin-top:23px;" border="0" />
            <img id="bann-2" src="images/banners/last_days/banner-<?=Session::getLastDays();?>-hover.png" style="margin-top:23px;" border="0" />
        </a>
    </div>
</div>
<?  } ?>