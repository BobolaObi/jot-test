<?php 

/**
 * Reminifies the group config folders and
 * update to AWS S3
 */
class DeployUpdateS3 extends DeployAction {
	
    /**
     * The name of the bucket
     */
	const BUCKET_NAME = 'jotform';
	
	/**
	 * s3 clean upload comment
	 * @const
	 */
	const CLEAN_S3 = 'S3CLEAN';

	/**
	 * @var AmazonS3
	 */
	private $s3;
	
	/**
	 * Minified groups
	 * @var array
	 */
	private $allGroups;
	
	/**
	 * Upload all files to amazon s3
	 * @var boolena
	 */
	private $uploadAllFiles = false;
	
	/**
	 * This files holds the files that are uploaded to 
	 * Amazon S3. Later those files will be invalidated.
	 * @var array
	 */
	private $invalidateFiles = array();
    
    /**
     * (non-PHPdoc)
     * @see lib/classes/deploy/DeployAction::execute()
     */
    public function execute(){
        
        if (!extension_loaded('curl') &&
            !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll')){
            # Check for CURL, exit if it does not exists.
            throw new Exception("ERROR: CURL extension not loaded\n\n");
        }
    	
        # The config folder that holds the minified groups
        require_once ( dirname(__FILE__) . "/../../../min/groupsConfig.php" );
        # The function  generated by Seyhun (There can be a problem in future)
        require_once ( dirname(__FILE__) . "/../../../min/generateMinUrl.php" );
        
        # Set the groups fetched from groupsConfig
        $this->allGroups = $allGroups;
        
        # Set if all files must be uploaded
        $this->uploadAllFiles = $this->isBuildForced() || $this->doesMessageContains(self::CLEAN_S3);
        
        # Create the minified objects
        $this->createMinifiers();
        
    	# Initialize S3 intance
    	$this->s3 = new AmazonS3();
    	
    	$this->checkBucket();
    	
    	$this->uploadMinifiedFiles();
    	
    	$this->uploadSvnFiles();
    	
    	$this->sendInvalidation();
    	
    }
    
    /**
     * (non-PHPdoc)
     * @see lib/classes/deploy/DeployAction::fallDown()
     */
    public function fallDown(){
    }
    
    
    /**
     * This function upload the svn files to amazon s3
     */
    private function uploadSvnFiles(){
        Profile::start("UploadSVNFiles");
        $svnFolders = array("sprite", "images/styles", "css");
        $files = array_merge(
            $this->getDirectory($this->deploy->getJotformDir(). "sprite"),
            $this->getDirectory($this->deploy->getJotformDir(). "images" . DIRECTORY_SEPARATOR . "styles"),
            $this->getDirectory($this->deploy->getJotformDir(). "css")
        );
        
        # Image files that are used in css files.
        $imageFiles = array(
            "bg.png",
            "logo.png",
            "blank.gif",
            "debug.png",
            "application-form.png",
            "myforms/not_back.png",
            "bar-back.png",
            "folder.png",
            "myforms/folder_open.png",
            "footer-bg.png",
            "ajax-loader.gif",
            "drag-back.png",
            "title-bg.png",
            "footer-bg.png",
            "cross.png",
            "tick.png",
            "gear.png",
            "not-selected.png",
            "selected.png",
            "document-excel.png",
            "document-word.png",
            "document-excel-csv.png",
            "document-pdf.png",
            "light-bulb.png",
            "tool_grad_over.png",
            "button-back.png",
            "button-back-red.png",
            "button-back-black.png",
            "tool_grad_over.png",
            "transparent.png",
            "grad3.png",
            "grad4.png",
            "navOver.png",
            "drag-back.png",
            "accept.png",
            "pencil.png",
            "footer-back.png",
            "grad5.png",
            "combo.png",
            "recaptcha-sample.png",
            "soft-grad.png",
            "arrow-open.png",
            "arrow-closed.png",
            "big-back.png",
            "grad.png",
            "loader-black.gif",
            "brushed.png",
            "small-ajax-loader.gif",
            "wrench.png",
            "shadow.png",
            "toolbar/recaptcha.png",
            "toolbar/general/print.png",
            "toolbar/general/edit.png",
            "toolbar/general/mail.png",
            "notification.png",
            "toolbar/general/wait.png",
            "toolbar/general/edit.png",
            "toolbar/general/delete.png",
            "toolbar/general/chart.png",
            "toolbar-admin/logout.png",
            "left.png",
            "right.png",
            "mail-auto.png",
            "dropbox.png",
            "success_small.png",
            "win2_foot_left.gif",
            "win2_foot_right.gif",
            "win2_foot.gif",
            "win2_left.gif",
            "win2_right.gif",
            "win2_title_left.gif",
            "win2_title_logo.gif",
            "win2_title_right.gif",
            "win2_title.gif",
            "myforms/new/filter-bg.gif",
            "myforms/new/form-selection-bg.gif",
            "dropdown-arrows.png",
            "dropdown-arrows-open.png",
            "dropdown-back.png",
            "button-back-black.png",
            "button-back-blood.png",
            "button-back-dark.png",
            "button-back-fire.png",
            "button-back-green.png",
            "button-back-grey.png",
            "button-back-red.png",
            "slide.gif",
            "slideHue.gif",
            "SatVal.png",
            "sharewiz2/wrench.png",
            "tile.png",
            "glowTop.png",
            "glowMid.png",
            "glowLow.png",
            "shadowTop.png",
            "shadowMid.png",
            "shadowLow.png",
            "themes/yellow-paint.jpg",
            "themes/red-wall.jpg",
            "themes/wood-planks.jpg",        
            "themes/black-mesh.jpg",
            "themes/blur-lights.jpg",
            "themes/blur-lights2.jpg",
            "themes/vintage.jpg",
            "themes/brown-leather.jpg",
            "themes/fall.jpg",
            "themes/grass.jpg",
            "themes/tile-black.jpg",
            "themes/tile-brown.jpg",
            "themes/tile-darkgreen.jpg",
            "themes/tile-darkyellow.jpg",
            "themes/tile-navyblue.jpg",
            "themes/tile-orange.jpg",
            "themes/tile-purple.jpg",
            "themes/tile-red.jpg",
            "themes/tile-turqoise.jpg",
            "themes/tile-yellow.jpg",
            "themes/black-leather.jpg",
            "themes/clouds.jpg",
            "themes/geopat.jpg",
            "themes/polka.jpg",
            "themes/woodboards.jpg",
            "themes/woodfloor.jpg",
            "themes/brickwall.jpg",
            "close-wiz.png",
            "close-wiz-over.png",
            "close-wiz-sprite.png", 
            "close-wiz-white.png",
            "close-wiz-black.png"
        );
        
        foreach ($imageFiles as $imageFile){
            array_push($files, $this->deploy->getJotformDir() . "images" .DIRECTORY_SEPARATOR . $imageFile);
        }
    
        array_push ($files, $this->deploy->getJotformDir() . "images" . DIRECTORY_SEPARATOR . "favicon.ico");
        
        foreach ($files as $uploadFile){
            $uploadFile = Utils::path($uploadFile, true);
            $filename = Utils::path(
                preg_replace(
                    "/" . preg_quote($this->deploy->getJotformDir(), '/') . "/",
                    "",
                    $uploadFile
                ), true
            );
            # Continue if the group is empty
            $action = $this->isInChangedFiles($filename);
            if (!$this->uploadAllFiles &&  $action === false ) { continue; }
            # Send the file which is not zipped.
            $headers = array_merge($this->getHeader($uploadFile));
            if ($action === "D"){
                # If action is delete, delete it from the S3
            	$this->deleteFileFromS3($filename);
            }else{
                # If action is Modify or Add, send it to S3
                $this->uploadFileToS3( $filename, $uploadFile, $headers);
            }
        }
        $this->deploy->comment("SVN Uploads: " . Profile::end("UploadSVNFiles"));
    }
    
    /**
     * @param $filename
     */
    private function deleteFileFromS3($filename){
        $this->deploy->comment("Deleting file: " . $filename);
    	$res = delete_object ( self::BUCKET_NAME, $filename);
        if ($res->isOK()){
        	$this->invalidateFiles[] = $filename;
        }else{
            throw new Exception("Cannot delete object from S3: " . $filename);
        }
    }

    /**
     * Checks the jotform site bucket, if it does not exists tries to create.
     * @exception throws exception if it cannot create the bucket.
     */
    private function checkBucket(){
    	Profile::start( "CheckBucket" );
        if ( count($this->s3->get_bucket_list('/^' . self::BUCKET_NAME . '$/')) === 0 ){
        	$response = $this->s3->create_bucket(
                self::BUCKET_NAME, AmazonS3::REGION_US_W1,
                AmazonS3::ACL_PUBLIC
            );
        	if (!$response->isOK()){
        		throw new Exception("Cannot create the bucket: " . self::BUCKET_NAME);
        	}
        }
        $this->deploy->comment( "Check bucket: " . Profile::end("CheckBucket") );
    }
    
    /**
     * Create the minified files.
     * @throws Exception curl
     */
    private function createMinifiers(){
    	Profile::start("Minify");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch,  CURLOPT_NOBODY, true);
        
        foreach($this->allGroups as $groupName => $filesArr) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/min/g=' . $groupName;
            curl_setopt($ch, CURLOPT_URL, $url);
            
            $output = curl_exec($ch);
            if ($output === false) {
                throw new Exception("cURL Error: " . curl_error($ch));
            }
        }
        
        curl_close($ch);
        $this->deploy->comment("Minify: " . Profile::end("Minify")); 
    }
    
    /** 
     * Fetch the minified files.
     */
    private function uploadMinifiedFiles(){
        Profile::start("UploadMinifyFiles");
    	
        # Upload minified folders under min folder.
	    $uploadPath = "js/minified/";   # Path in our server.
	    $folderPath = "min/";         # Path in the Amazon S3
        
        foreach ($this->allGroups as $groupName => $files){
        	# Continue if the group is empty
        	if ( count($files) <= 0  || !isset($files[0])) { continue; }
        	# Check if the files have been changed and complete publish is sended.
        	if ( !$this->uploadAllFiles &&  $this->isInChangedFiles($files) === false ){ continue; }
            
        	$_GET['g'] = $groupName;
            $_GET[VERSION] = "";
            $cacheId = str_replace( ".gz", "", generateMinUrl() ); # Generate cache ID.
            
            # Send the file which is not zipped.
            $uploadFile = Utils::path($this->deploy->getJotformDir() . $uploadPath . $cacheId, true );
            $innerUploadFile = Utils::path($this->deploy->getJotformDir().$files[0]);
            $filePath = $folderPath . $cacheId;
            $headers = array_merge($this->getHeader($uploadFile), $this->getHeader($innerUploadFile));
            $this->uploadFileToS3( $filePath, $uploadFile, $headers);
            
            # Send the zipped file.
            $uploadFile = Utils::path(
                $this->deploy->getJotformDir() . $uploadPath . $cacheId . ".gz",
                true
            );
            $innerUploadFile = Utils::path($this->deploy->getJotformDir().$files[0]);
            $filePath = $folderPath . $cacheId . ".jgz";
            $headers = array_merge($this->getHeader($uploadFile), $this->getHeader($innerUploadFile));
            $this->uploadFileToS3( $filePath, $uploadFile, $headers);
        }
        $this->deploy->comment("Minify uploads: " . Profile::end("UploadMinifyFiles"));
    }
    
    /**
     * 
     * @param $fileArray This can be either an array or directly one file name.
     * @return boolean
     */
    private function isInChangedFiles($fileArray){
    	if ( !is_array($fileArray) ){
    		$fileArray = array($fileArray);
    	}
    	$changedFiles = $this->log->getChangedFiles();
    	foreach ($fileArray as $fileName){
    		$fileName = Utils::path("/" . $fileName, true);
    	    if ( isset($changedFiles[$fileName]) ){
	        	return $changedFiles[$fileName];
	        }
    	}
    	return false;
    }
    
    private function getHeader($filePath){
        $headers = array();
        $fileInfo = pathinfo(Utils::path($filePath,true));
        switch ($fileInfo['extension']){
            case 'js':
                $headers["Content-Type"] = "application/x-javascript";
                break;
            case 'css':
                $headers["Content-Type"] = "text/css; charset=utf-8";
                break;
            case 'gz':
            	$headers["Content-Encoding"] = "gzip";
            	break;
            case 'png':
                $header ['Content-Type'] = "image/png";
                break;
            case 'jpg':
            case 'jpeg':
                $header ['Content-Type'] = "image/jpeg";
                break;
        }
        $headers["Cache-Control"] = "max-age";
        $expires = 2*12*30*24*60*60; 
        $headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT';
        return $headers; 
    }
    
    private function uploadFileToS3 ($filename, $uploadname, $headers = array()){
        $this->deploy->comment("Sending file: " . $filename);
    	$res = $this->s3->create_object( self::BUCKET_NAME, $filename, array(
            "fileUpload" => $uploadname,
            "acl" => AmazonS3::ACL_PUBLIC,
            "headers" => $headers,
            "length" => filesize($uploadname)
        ));
        if ($res->isOK()){
            # add to uploaded files for sending invalidation
        	$this->invalidateFiles[] = $filename;
        }else{
            throw new Exception("'$filename' ERROR in creating file in Amazon S3.");
        }
    }
    
    /**
     * Send invalition for the uploaded files.
     */
    private function sendInvalidation(){
    	if(count($this->invalidateFiles) <= 0 ){
    		return;
    	}else{
    		$this->deploy->comment("Invalidating files: " . print_r( $this->invalidateFiles, true) );
    	}
        $cdn = new AmazonCloudFront();
        $response = $cdn->create_invalidation(
            distributionID,
            "aws-php-sdk-test". time(),
            $this->invalidateFiles
        );
        if(!$response->isOK()){
            echo "FAIL";
        }
    }
    	
	/**
	 * Get the changed or all files according to allFiles variable
	 * @param $path     The path that we will loop
	 * @return $fileList The fileList array that will be filled by files that will be sync.
	 */
	private function getDirectory($path = '.'){
	    # File list array.
	    $fileList = array();
	    
	    # Ignore file list.
	    $ignore = array( 'cgi-bin', '.', '..', '.svn', '.DS_Store' );
	    
	    $dh = @opendir( $path );
	    
	    while( false !== ( $file = readdir( $dh ) ) ){
	
	        if( !in_array( $file, $ignore ) ){
	            $fullPath = $path . DIRECTORY_SEPARATOR . $file;
	
	            if( is_dir( $fullPath ) ){
	                $fileList = array_merge ($fileList, $this->getDirectory($fullPath));
	            } else {
	                array_push($fileList, $fullPath);
	            }
	        }
	    }
	    closedir( $dh );
	    
	    # Return the array.
	    return $fileList;
	} 
    
}
