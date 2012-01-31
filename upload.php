<?
    /**
     * TODO: Ask & learn how upload.php and FileUpload.php files are used to Serkan. (Added by Seyhun)
     */
    include "lib/init.php";
    
    $username  = Session::$username;
    $formFiles = UPLOAD_FOLDER.$username."/form_files/";
    $baseName  = $username."/form_files/";
    
    if(Utils::get('url')){
        $dim = Utils::getImageDimensions(Utils::get('url'));
        die('<script>window.parent.setImageSource("'.Utils::get('qid').'", "'.Utils::get('url').'", "'.$dim['height'].'", "'.$dim['width'].'");</script>');
    }
    
    if(isset($_FILES['fileUpload'])){
        $file = $_FILES['fileUpload'];
        try{
            if(strlen($file['name']) < 3){
                throw new Exception('Please select a file first');
            }
            $dim = Utils::getImageDimensions($file['tmp_name']);
            $upload = new FileUpload($file);
            $url = $upload->uploadFile($baseName);
            die('<script>window.parent.setImageSource("'.Utils::get('qid').'", "'.$url.'", "'.$dim['height'].'", "'.$dim['width'].'");</script>');
        }catch(Exception $e){
            echo "<h3>".$e->getMessage()."</h3>";
            exit;
        }
    }
    
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Upload a File</title>
        <style>
            body, html{
                height:100%;
                width:100%;
                margin:0;
                padding:0;
            }
            body{
                font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
                font-size:12px;
                background:none;
            }
            .images{
                border:2px solid #ccc;
                margin:5px;
                padding:5px;
            }
            .images:hover{
                border:2px solid #bbb;
            }
            .selectedImage{
                border:2px solid #666;
                background:#ffffff;
                margin:5px;
                padding:5px;
            }
            
            h3{
                margin:3px;
                font-size:13px;
            }
            .imageSelectionBox{
                -moz-box-shadow:0px 2px 5px rgba(0, 0, 0, 0.3) inset;
                -webkit-box-shadow:0px 2px 5px rgba(0, 0, 0, 0.3) inset;
                border:1px solid #999999;
                padding:5px;
                background:none repeat scroll 0 0 #F5F5F5;
                max-height:248px;
                overflow:auto;
                margin-bottom:5px;
            }
        </style>
        
        <link href="<?=HTTP_URL?>css/buttons.css" rel="stylesheet" media="screen" />
         
        <script type="text/javascript">
            var qid = '<?=Utils::get("qid")?>';
            var selectedImage = false;
            function selectImage(img){
                if(selectedImage){
                    selectedImage.className = 'images';
                }
                
                selectedImage = img;
                selectedImage.className = 'selectedImage';
            }
            
            function useURL(){
                var val = document.getElementById('img-url').value
                document.getElementById('img-error').innerHTML = '&nbsp;';
                if((!val || val.length < 5) || !/^http/.test(val)){
                    document.getElementById('img-error').innerHTML = 'Please enter a correct URL.';
                    return;
                }
                
                location.href = '?qid='+qid+'&url='+encodeURIComponent(val);
            }
            
            function getRealImageDimensions(img){
                var cl = img.cloneNode(true);
                cl.removeAttribute('height');
                cl.removeAttribute('width');
                cl.style.width   = '';
                cl.style.height  = '';
                cl.style.padding = '';
                cl.style.margin  = '';
                cl.style.border  = '';
                document.body.appendChild(cl);
                var height = cl.clientHeight;
                var width  = cl.clientWidth;
                cl.parentNode.removeChild(cl);
                
                return {height: height, width: width};
            }
            
            function showLoad(){
                window.parent.imageWizard.showLoading();
            }
            
            function hideLoad(){
                window.parent.imageWizard.hideLoading();
            }
            
            function useSelected(){
                if(!selectedImage){
                    alert('Selected an image first');
                    return;
                }
                var dims = getRealImageDimensions(selectedImage);
                window.parent.setImageSource(qid, selectedImage.src, dims.height, dims.width);
            }
            
        </script>
    </head>
    <body>
    
        <table width="100%" height="100%" cellpadding="4">
            <? if(Utils::get('type') == 'source-url'){ ?>
            <tr>
                <td align="center">
                    <div style="text-align:left; display:inline-block; width:400px;">
                        <h3>Enter URL</h3>
                        <input id="img-url" type="text" size="36"> <input type="button" onclick="useURL()" class="big-button buttons buttons-red" value="Save Image">
                        <span style="font-size:10px; color:#777;margin-top:3px; display:inline-block;">
                            Enter URL of an image on the web.
                        </span>
                        <div id="img-error" style="color:red; text-align:right; font-size:10px;">&nbsp;</div>
                    </div>
                </td>
            </tr>
            
            <? } else if(Utils::get('type') == 'source-upload'){ ?>
            <tr>
                <td align="center">
                    <div style="text-align:left; display:inline-block; width:400px;">
                        <h3>Upload Image File</h3>
                        <form action="<?=$_SERVER['PHP_SELF'] ?>" onsubmit="showLoad()" accept-charset="utf-8" method="post" enctype="multipart/form-data">
                            <input type="file" name="fileUpload" size="31">&nbsp;&nbsp;
                            <input type="hidden" name="qid" value="<?=Utils::get('qid')?>">
                            <input type="submit" class="big-button buttons buttons-red" value="Upload File">
                        </form>
                        <span style="font-size:10px; color:#777;margin-top:3px; display:inline-block;">
                            Upload a file to use it in your form. It will be accessible from all forms.
                        </span>
                    </div>
                </td>
            </tr>
            <?
            }else{
                $upload = new FileUpload();
                $files = $upload->getUploadedFiles($baseName);
                if(count($files) > 0){
            ?>
            <tr>
                <td>
                    <script>hideLoad();</script>
                    <table width="100%" height="100%">
                        <tr>
                            <td align="center" valign="top">
                                <div style="text-align:left; display:inline-block; width:400px">
                                    <h3>Choose Image</h3>
                                    <span style="font-size:10px; color:#777;margin-top:3px; display:inline-block;">
                                        Select one of the images previously uploaded:
                                    </span>
                                    <br>
                                    <div class="imageSelectionBox">
                                    <? foreach($files as $file){ ?>
                                        <img height="40" onclick="selectImage(this);" src="<?=$file?>" class="images" />
                                    <? }?>
                                    </div>
                                    <input type="button" class="big-button buttons buttons-red" value="Use Selected Image" onclick="useSelected()" />
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <? }else{ ?>
            <tr>
                <td align="center">
                    <script>hideLoad();</script>
                    <h3>No Images Uploaded Yet</h3>
                </td>
            </tr>
            <? } } ?>
        </table>
        
    </body>
</html>
