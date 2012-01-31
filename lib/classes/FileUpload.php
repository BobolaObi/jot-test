<?php

class FileUpload{
    
    private $uploadType; /* "cloud" or "local"  */
    
    private $neverAllow = array('php', 'pl', 'rb', 'asp', 'aspx', 'exe', 'scr', 'dll', 'msi', 'vbs', 'bat', 'com', 'pif', 'cmd', 'vxd', 'cpl');
    private $file;
    
    /**
     * Initiate the object
     * @param object $type [optional] cloud or local
     * @param object $fileProperties [optional] $_FILES configuration for the file
     * @return 
     */
    function __construct( $fileProperties = null){
    	$this->uploadType = (JOTFORM_ENV != 'PRODUCTION' || !ENABLE_UFS || APP) ? 'local' : 'cloud';
        $this->file = $fileProperties;
    }
    
    /**
     * Copy file to the destination
     * @param object $destination Path where the file will be saved
     * @return 
     */
    public function uploadFile($destination){
        
        $extension = Utils::getFileExtension($this->file['name']);
        if(in_array($extension, $this->neverAllow)){
            throw new Exception('File type is not allowed');
        }
        $this->file['name'] = Utils::fixUploadName($this->file['name']);
        
        if($this->uploadType == 'cloud'){
            Utils::cacheDelete("S3::".$destination);
            $amaz = new AmazonS3Controller();
            $amaz->setProperties();
            $amaz->suppressUpload($this->file['tmp_name'], $destination.$this->file['name']);
            $url = CLOUD_UPLOAD_URL;
        }else{
            
            $ndestination = UPLOAD_FOLDER.$destination;
            $url = UPLOAD_URL;
            if(!file_exists($ndestination)){
                if(!Utils::recursiveMkdir($ndestination, 0777)){
                    throw new Exception("Error creating upload folder. <br>".$ndestination);
                }
            }
            
            if(!move_uploaded_file($this->file['tmp_name'], $ndestination.$this->file['name'])){
                throw new Exception("Error when moving the uploaded file");
            }
            
        }
        
        return $url.$destination.$this->file['name'];
    }
    /**
     * Delete the uploaded file
     * @param object $filePath
     * @return 
     */
    public function deleteUploadedFile($filePath){
        if($this->uploadType == 'cloud'){
            Utils::cacheDelete("S3::".$destination);
            $amaz = new AmazonS3Controller();
            $amaz->setProperties();
            $amaz->suppressDelete($filePath);
        }else{
            if(!unlink($filePath)){
                throw new Exception('File cannot be deleted');
            }
        }
    }
    
    /**
     * Return the list of uploaded files
     * @param object $path
     * @return 
     */
    public function getUploadedFiles($path){
        $files = array();
        if($this->uploadType == 'cloud'){
            
            if( ! ($s3Files = Utils::cacheGet("S3::".$path)) ){
                $s3 = new S3(AmazonS3Config::awsAccessKey, AmazonS3Config::awsSecretKey);
                $s3Files = $s3->getBucket(AmazonS3Config::uploadBucketName, $path);
                Utils::cacheStore("S3::".$path, $s3Files);
            }
            
            foreach($s3Files as $fileName => $fileDetails){
                $files[] = CLOUD_UPLOAD_URL.$fileName;
            }
        }else{
            foreach(glob(UPLOAD_FOLDER.$path."*.*") as $filename){
                $files[] = str_replace(UPLOAD_FOLDER, UPLOAD_URL, $filename);
            }    
        }
        # return array();
        return $files;
    }
}