<?php
/**
 * FTP Library
 * @package JotForm_Utils
 * @copyright Copyright (c) 2010, Interlogy LLC 
 */

namespace Legacy\Jot\Utils;

class FTPLib{
    
    private $hostname, $username, $password, $port, $conn;
    /**
     * Contains all the tools to connect FTP Servers
     * @param object $hostname
     * @param object $username
     * @param object $password
     * @return 
     */
    public function __construct($hostname, $username, $password, $port = 21){
        
        if (!extension_loaded('ftp')) {
            throw new Exception("FTP Extension is not loaded");
        }
        
        $this->password = $password;
        $this->hostname = $hostname;
        $this->username = $username;
        $this->port     = $port;
    }
    
    /**
     * Connect to the FTP Server
     * @return connection ID
     */
    public function connect(){
        # set up basic connection
        $this->conn = @ftp_connect($this->hostname, $this->port);
        
        if(!$this->conn){
            throw new Exception('Wrong Host Name');
        }
        
        # login with username and password
        $login_result = @ftp_login($this->conn, $this->username, $this->password); 
        
        if(!$login_result){
            throw new Exception('Wrong Login Information');
        }
        
        // check connection
        if ((!$this->conn) || (!$login_result)) { 
            throw new Exception("Can't connect: ".$this->hostname.", ".$this->username);
        }
        
        return $this->conn;
    }
    
    /**
     * Parses the size value of folder
     * @param $size Object
     * @return Human readible version of size string
     */
    public function getSize($size){
         if ($size < 1024){
              return round($size,2).' Byte';
         }else if ($size < (1024*1024)){
              return round(($size/1024),2).' MB';
         } else if ($size < (1024*1024*1024)){
              return round((($size/1024)/1024),2).' GB';
         }else if ($size < (1024*1024*1024*1024)){
              return round(((($size/1024)/1024)/1024),2).' TB';
         }
    }
    
    /**
     * Converts unix mode string to chmod num
     * @param $mode Object
     * @return 
     */
    function chmodNum($mode) {
       $realmode = "";
       $legal =  array("","w","r","x","-");
       $attarray = preg_split("//",$mode);
       for($i=0;$i<count($attarray);$i++){
           if($key = array_search($attarray[$i],$legal)){
               $realmode .= $legal[$key];
           }
       }
       $mode = str_pad($realmode,9,'-');
       $trans = array('-'=>'0','r'=>'4','w'=>'2','x'=>'1');
       $mode = strtr($mode,$trans);
       $newmode = '';
       $newmode .= $mode[0]+$mode[1]+$mode[2];
       $newmode .= $mode[3]+$mode[4]+$mode[5];
       $newmode .= $mode[6]+$mode[7]+$mode[8];
       return $newmode;
    }
    
    /**
     * Collects all details of a file on FTP
     * @param $folder Object
     * @return 
     */
    public function getFileDetails($folder){
        $struc = array();
        $current = preg_split("/[\s]+/", $folder, 9);
       
        $struc['perms']   = $current[0];
        $struc['permsn']  = $this->chmodNum($current[0]);
        $struc['number']  = $current[1];
        $struc['owner']   = $current[2];
        $struc['group']   = $current[3];
        $struc['size']    = $this->getSize($current[4]);
        $struc['month']   = $current[5];
        $struc['day']     = $current[6];
        $struc['time']    = $current[7];
        $struc['name']    = str_replace('//','',$current[8]);
        return $struc;
    }
    
    /**
     * Gets the file type from list string.
     * @param $perms Object
     * @return file, folder or link
     */
    public function getFileType($perms){
        if (substr($perms, 0, 1) == "d"){
            return 'folder';
        } else if (substr($perms, 0, 1) == "l"){
            return 'link';
        }else{
            return 'file';
        }
    }
    
    /**
     * Returns the array of files and details.
     * @param $folder Object
     * @param $ftp Object
     * @return 
     */
    function getFilesArray($folder = "."){
        $list = ftp_rawlist($this->conn, $folder);
        if(is_array($list)){
            foreach($list as $path){
                
                $details = $this->getFileDetails($path);
                $type = $this->getFileType($details[perms]);
                if(!is_array($tree[$type])){
                    $tree[$type] = array();
                }
                if($type == "link"){
                    $n = explode(" -> ", $details['name']);
                    $details['name'] = $n[0];
                    $link = $n[1];
                }
                
                if(strpos($details['name'], ".") === 0){ continue; }
                array_push($tree[$type], array("path" => $folder, "name" => $details['name'], "type" => $this->getFileType($details['perms']), "link"=>$link, "permission" => $details['permsn']));
            }
        }
        
        $result = array();
        if(is_array($tree['folder'])){
            foreach($tree['folder'] as $folder){
                array_push($result, $folder);
            }
        }
        if(is_array($tree['link'])){
            foreach($tree['link'] as $link){
                array_push($result, $link);
            }
        }
        if(is_array($tree['file'])){
            foreach($tree['file'] as $file){
                array_push($result, $file);
            }
        }
        return $result;
    }
    
    /**
     * Returns the list of local files
     * @param object $start_dir [optional]
     * @return 
     */
    public function getLocalFiles($start_dir='.') {
        $files = array();
        $folders = array();
        if (is_dir($start_dir)) {
            $fh = opendir($start_dir);
            while (($file = readdir($fh)) !== false) {
                # loop through the files, skipping . and .., and recursing if necessary
                #if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0 || strcmp($file, '.svn') == 0 || strcmp($file, '.DS_Store') == 0) continue;
                if (preg_match("/^\./", $file)) continue; 
                $filepath = preg_replace("/\/\//", "/", $start_dir . "/" . $file);
             
                if ( is_dir($filepath) ){
                    $r = $this->getLocalFiles($filepath);
                    $files = array_merge($files, $r["files"]);
                    $folders = array_merge($folders, $r["folders"]);
                    array_push($folders, $filepath);
                }else{
                    array_push($files, $filepath);
                }
            }
            closedir($fh);
         
        } else {
            # false if the function was called with an invalid non-directory argument
            $files = false;
        }
        return array("files" => $files,"folders" => $folders);
    }
    
    /**
     * Sets the permissions for a file on FTP Server
     * @param object $file
     * @param object $mod
     * @return 
     */
    public function changeMod($file, $mod){
        if(!@ftp_site($this->conn, "CHMOD ".$mod." ".$file)){
            throw Exception("Can't change mod for ".$file);
        }
    }
    
    /**
     * Create Folder
     * @param object $folder
     * @return 
     */
    public function createFolder($folder){
        if(@ftp_mkdir($this->conn, $folder) === false){
            throw new Exception('Cannot Create Folder: '.$folder);
        }
    }
    
    /**
     * Creates all folders in the path
     * @param object $path
     * @param object $mode [optional]
     * @return 
     */
    private function createFilePath($filePath, $mode = 0777){
        $path = $filePath;
        $dir = explode("/", $path);
        array_pop($dir); // Remove filename
        $path="";
        $ret = true;

        for ($i=0; $i < count($dir); $i++){
            $path .= "/".$dir[$i];
            if(!@ftp_chdir($this->conn, $path)) {
                @ftp_chdir($this->conn,"/");
                if(!@ftp_mkdir($this->conn, $path)){
                    $ret=false;
                    break;
                } else {
                    @ftp_chmod($this->conn, $mode, $path);
                }
            }
        }
        return $ret;
    }
    
    /**
     * Deletes a folder on FTP Server
     * @param object $folder
     * @return 
     */
    public function deleteFolder($folder){
        if(!@ftp_rmdir($this->conn, $folder)){
            throw New Exception("Cannot delete: ".$folder);
        }
    }
    
    /**
     * Puts a file to FTP Server
     * @param object $localPath
     * @param object $remotePath
     * @return 
     */
    public function putFile($localPath, $remotePath){
        $this->createFilePath($remotePath);
        
        if(!@ftp_put($this->conn, $remotePath, $localPath, FTP_BINARY)){
            $e = error_get_last();
            $because = ".";
            if(!empty($e['message'])){
                $because = ".<br>Because: ".$e['message'];
            }
            throw new Exception('Cannot move file to server: '.$remotePath.$because);
        }
        return true;
    }
    
    /**
     * Deletes a file from FTP Server
     * @param object $remotePath
     * @return 
     */
    public function removeFile($remotePath){
        if(!@ftp_delete($this->conn, $remotePath)){
            throw New Exception("Cannot delete: ".$remotePath);
        }
        return true;
    }
}