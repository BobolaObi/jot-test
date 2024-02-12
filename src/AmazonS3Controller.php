<?php

class AmazonS3Controller extends UFS {

	private static $s3 = false;    # The S3 instance.
	private $bucketName;    # Name of the bucket
	private $awsAccessKey;  # Access key for AmazonS3
	private $awsSecretKey;  # Secret key for AmazonS3
	private $serverAddr = false;    # The address that supress request will be send.

	/**
	 * Contruct the object by default upload bucket
	 * change bucket function.
	 * @param $bucketName
	 */
	public function setProperties ( $bucketName   = false, $awsAccessKey = false, $awsSecretKey = false  ){
		if ($bucketName === false){
			$this->bucketName   = AmazonS3Config::uploadBucketName;
		}
		if ($awsAccessKey === false){
			$this->awsAccessKey = AmazonS3Config::awsAccessKey;
		}
		if ($awsSecretKey === false){
			$this->awsSecretKey = AmazonS3Config::awsSecretKey;
		}
		# Contol the curl extension.
		if (!extension_loaded('curl')){
		    if (!function_exists("dl") || !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll') ){
                # Throw exception about curl extension.
                throw new JotFormException("ERROR: CURL extension not loaded.");
		    }
		}
		
		# Control if bucket name is empty throw an exception.
		if ( empty($this->bucketName) ){
			throw new JotFormException("The bucket name is empty: " . $this->bucketName);
		}
        if (self::$s3 === false){
            # Initialize S3 intance
	        self::$s3 = new S3($this->awsAccessKey, $this->awsSecretKey);
	
	        # Set bucket name
	        $this->setBucketName($this->bucketName);
        }
        
        # If enviroment is local environment and ENABLE_UFS is enabled, use local address.
        if (JOTFORM_ENV === "DEVELOPMENT"){
            if (ENABLE_UFS){
                # The local server ip.
                $this->serverAddr = $_SERVER['HTTP_HOST'];
	        }
        }else{
            # The local server ip.
            $this->serverAddr = Server::getLocalIP();
        }
	}

	/**
	 * Checks the bucket name and creates if it does not exists.
	 * @param $bucketName
	 * @return unknown_type
	 */
	private function setBucketName($bucketName){
		# Set the bucketName
		$this->bucketName = $bucketName;

		# Get the list of buckets.
		$bucketList = (self::$s3->listBuckets());

		# Check if bucketName is in.
		if ( !in_array($this->bucketName, $bucketList) && !self::$s3->putBucket($this->bucketName) ){
			throw new JotFormException("Cannot create bucket: " . $bucketName);
		}
	}

	public function uploadFile(){

        # if file type is transferred transfer directly.
		if (!$this->fileTmpName){ # This part is used for file syncronization
	        # This is the address of FileController
	        $transferFile = UPLOAD_FOLDER . "/" . $this->username . "/" . $this->formID . "/" . $this->submissionID . "/" . $this->fileName;
	        # Set base name
	        $baseName = $this->username . "/" . $this->formID . "/" . $this->submissionID . "/" . Utils::fixUploadName($this->fileName);
        
			# Change the file location.
			$this->suppressUpload($transferFile, $baseName);
		}else{
	        # This is the address of FileController
	        $transferFile = UPLOAD_FOLDER . "/" . $this->username . "/" . $this->formID . "/" . $this->submissionID . "/" . Utils::fixUploadName($this->fileName);
	        # Set base name
	        $baseName = $this->username . "/" . $this->formID . "/" . $this->submissionID . "/" . Utils::fixUploadName($this->fileName);
        
			if ($this->serverAddr !== false ) {
				$request = array(
	                "action"    => "sendFileToAmazonS3",
	                "filePath"  => urlencode($transferFile),
	                "baseName"  => urlencode($baseName),
	                "formID"    => urlencode($this->insertID)
				);
				Utils::suppressRequest($this->serverAddr."/server.php", $request);
			}else{
				throw new JotFormException("Cannot find local ip of server.");
			}
		}
	}

	/**
	 * Execute a shell script to upload the file.
	 * @return unknown_type
	 */
	public function suppressUpload($filePath, $baseName, $id = false){
		 
		if ($id !== false){
			$this->insertID = $id;
		}

		$res = self::$s3->putObjectFile($filePath, $this->bucketName, $baseName, S3::ACL_PUBLIC_READ);

		if ($res && $this->insertID !== false){
        	parent::completeUpload();
        }else{
        	# Send email if upload is broken.
            mail('seyhun@interlogy.com,atank@interlogy.com', 'BROKEN UPLOAD FILE', "File Path: {$filePath}\nUpload ID: {$this->insertID}");
        }
        
		return $res;
	}

	public function suppressDelete($filePath){
		 
		$submissionFiles = self::$s3->getBucket($this->bucketName, $filePath);
		foreach ($submissionFiles as $file){
			$object = self::$s3->deleteObject($this->bucketName, $file['name']);
		} 
	}

	public function deleteSubmissionFiles(){
		 
		# Set base name
		$path = $this->username . "/" . $this->formID . "/" . $this->submissionID ."/";

		if ($this->serverAddr !== false ) {
			$request = array(
                "action"    => "deleteSubmissionFromAmazonS3",
                "filePath"  => $path
			);
			Utils::suppressRequest($this->serverAddr."/server.php", $request);
		}else{
			throw new JotFormException("Cannot find local ip of server.");
		}
	}
    
	public function fileExists($bucket, $uri){
		return self::$s3->getObjectInfo($bucket, $uri);
    }
    
	public static function getUploadUrl( $username, $formID, $submissionID, $fileName){
		return  UPLOAD_URL . $username . "/" . $formID . "/" . $submissionID . "/" . Utils::fixUploadName($fileName);
		# This part is commented because reverse proxy is used.
		# return  AmazonS3Config::amazonBaseUrl . AmazonS3Config::uploadBucketName . "/" . $username . "/" .
		# $formID . "/" . $submissionID . "/" . Utils::fixUploadName($fileName) ;
	}

}

