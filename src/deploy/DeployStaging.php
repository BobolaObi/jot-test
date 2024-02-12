<?php
/**
 * 
 */
class DeployStaging extends DeployAction {
	
	const STAGING_PATH = '/opt/jotform/';
	
	public function execute(){
		$stagingPath = self::STAGING_PATH;
		$projectPath = Deploy::PROJECT_PATH;
		
		$this->deploy->comment("Preparing a new staging codebase locally.");
		
		exec("rm -rf {$stagingPath}staging.tar.gz");
        exec("rm -rf {$stagingPath}staging");
        exec("cp -rp {$projectPath}workspace/jotform3/ {$stagingPath}staging");
        exec("find {$stagingPath}staging/ -name .svn | xargs rm -rf");
        
        # Change the number in init file
        if ($this->deploy->getBuildNumber() === null){
            throw new Exception ("Cannot fetch build number for " . __CLASS__ . "::" . __METHOD__);
        }

        exec("sed  -i -r -e \"s/3\.([0-9]+)\.REV/3\.\1.{$this->deploy->getBuildNumber()}/\" {$stagingPath}staging/lib/init.php");
        
        if($this->deploy->getSvnNumber() !== null){
            exec("sed  -i -r -e \"s/3\.([0-9]+)\.SVN_REV/3\.\1.{$this->deploy->getSvnNumber()}/\" {$stagingPath}staging/lib/init.php");
        }
        		
		exec("chmod -R 777 {$stagingPath}staging/opt/db/ruckusing/logs");
		
		# This will overcome permission problems.
		exec("chmod -R 777 {$stagingPath}staging");
		exec("chmod -R 777 {$stagingPath}staging/opt/db/ruckusing/db/migrate");
		
		# So that v8 config file could be created.
		exec("chmod -R 777 {$stagingPath}staging/opt/v8");
		
		# Zip does not preserve folder permissions given above. So use tar.
		exec("cd {$stagingPath}; tar -cpzf staging.tar.gz staging");
		
		$this->deploy->comment("Distributing the latest version to the servers");
		foreach($this->deploy->getServers() as $name => $ip){
		    
		    $owner = "atank";
		    $auth = "";
		    if(strpos($name, "ec2") !== false || strpos($name, "dolphin") !== false){
		        chdir("/root/system/ec2/.ec2");
		        $auth  =  "-i shark.pem";
		        $owner = "ubuntu"; # ubuntu or root
		        if(!$this->ping($ip)){ $this->deploy->comment("{$ip} was offline, therefore skipped."); continue; }
		    }
		    
		    $this->deploy->comment("Updating staging for ".$name." (".$ip.")");
		    
		    exec("scp {$auth} -o StrictHostKeyChecking=no /opt/jotform/staging.tar.gz {$owner}@{$ip}:/www/v3/");
		    
		    $this->deploy->comment(".");
		    
		    // No need to delete staging folder.
		    // print `ssh $auth@$ip 'rm -rf /www/v3/staging'`;
		    // But we need to delete the old /src/staging folder.
		    exec("ssh -o StrictHostKeyChecking=no {$auth} {$owner}@{$ip} 'rm -rf /www/v3/src/staging'");
		    $this->deploy->comment(".");
		    exec("ssh -o StrictHostKeyChecking=no {$auth} {$owner}@{$ip} 'cd /www/v3/; tar -C src -xpzf staging.tar.gz > /dev/null 2>&1; rm staging.tar.gz; cp -prfu src/staging/* staging/; cp -prfu src/staging/.htaccess staging/'");
		    $this->deploy->comment(".");
		    
		    $this->deploy->comment("\nChecking pages for corruption");
		    $pages = array("index.php", "page.php", "page.php?p=myforms", "page.php?p=signup", "page.php?p=submissions&formID=Copyright");
		    foreach($pages as $page){
		        # Place IP instead
		        $url = "http://".$name."v3staging.interlogy.com/".$page;
		        $res = join("", file($url));
		        if(!strpos($res, "Copyright")){
		            trigger_error("PAGE ERROR: ".$url, E_USER_ERROR);
		        }
		        
		        # Check if app parameter is on
		        if(strpos($res, "::JotForm Application::")){
		            throw new Exception("This Seems to be an Application", E_USER_ERROR);
		        }
		        
		        $this->deploy->comment(".");
		    }
		    $this->deploy->comment("\nAll pages were OK");
		}
		throw new Exception("Stopped for testing.");
		
	}
	
	public function fallDown(){
	
	}
	
	/**
	 * @param $url
	 * @return boolean
	 */
	private function ping($url){
	    if(@file("http://".$url."/alive.php")){
	        return true;
	    };
	    return false;
	}
	
	
}