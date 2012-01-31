<?php 
	include_once "lib/init.php";
    
    if(!isset($_GET["formID"])){
        Utils::errorPage("No ID was provided", "Oops");
    }
    
    
    $formID = $_GET["formID"];
    
    
    $listID = isset($_GET["listID"])? $_GET["listID"] :  "";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="chrome=1"/>
        <meta http-equiv="X-UA-Compatible" content="ie=8"/>
        <title>JotForm &middot; Grid</title>
        <style>
            html, body{
                width:100%;
                height:100%;
            }
            .x-grid3-row-table{
                height:auto;
            }
            .x-grid3-row{
                height:auto;
            }
            .x-grid3-cell-inner{
                white-space:normal !important;
                overflow-y:auto !important;
                max-height:100px;
            }
        </style>
        <link rel="stylesheet" type="text/css" href="http://extjs.cachefly.net/ext-3.0.0/resources/css/ext-all.css" />
        <link rel="stylesheet" type="text/css" href="http://extjs.cachefly.net/ext-3.1.0/resources/css/xtheme-gray.css" />
    </head>
    <body>
        <!--div id="submissions-grid" style="width:100%;height:100%;"></div-->
        <script type="text/javascript">
            var listID = "<?=$listID?>";
        </script>
        <script src="<?=HTTP_URL?>js/prototype.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>js/protoplus.js" type="text/javascript"></script>
		<script src="<?=HTTP_URL?>js/locale/locale_en-US.js" type="text/javascript"></script>
        <?php 
            echo Translations::getJsInclude();
        ?>
        <script src="<?=HTTP_URL?>js/locale/locale.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>js/protoplus-ui.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>js/common.js" type="text/javascript"></script>
        <script src="http://extjs.cachefly.net/ext-3.0.0/adapter/prototype/ext-prototype-adapter.js" type="text/javascript"></script>
        <script src="http://extjs.cachefly.net/ext-3.0.0/ext-all.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>js/Ext.ux.util.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>js/Ext.ux.state.HttpProvider.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>js/includes/submissions.js" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>server.php?action=getSetting&identifier=<?=$formID?>&key=columnSetting&callback=Submissions.getColumnSettings" type="text/javascript"></script>
        <script src="<?=HTTP_URL?>server.php?action=getExtGridStructure&callback=Submissions.initGrid&formID=<?=$formID?>&listID=<?=$listID?>&type=<?= empty($listID)? 'reports' : 'listing' ?>" type="text/javascript"></script>
        <script>
            var timeout = false;
            Event.observe(window, 'resize', function(){
                if(timeout){
                    clearTimeout(timeout);
                }
                timeout = setTimeout(function(){
                    var rpp = Math.floor((document.viewport.getDimensions().height - 22 - 27) / 22);
                    var start = 0;
                    try{
                        if(Submissions.grid.getStore() && Submissions.grid.getStore().lastOptions && Submissions.grid.getStore().lastOptions.params){
                            start = Submissions.grid.getStore().lastOptions.params.start || 0;
                        }
                        Submissions.grid.getStore().load({params: {start:start,  limit: rpp}})
                    }catch(e){
                        
                    }
                }, 500);
            });
        </script>
    </body>
</html>