<?php

namespace Legacy\Jot;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Utils;

class Report{
	
	public $id;
	public $form;
	public $formID;
	public $title;
    public $hasPassword;
	public $newReport = false;
	
	/**
	 * Creates a report instance
	 * @constructer
	 * @param int $id  if $new flag is true gets the form ID for this report to be saved else gets the report ID
	 * @param boolean $new [optional] Defines if this is a new or old report, decides to save or update
	 */
	public function __construct($id, $new = false){
	    
        if($id == 'session'){
            $id = Utils::getCurrentID('report');
        }
        
		if($new){
			$this->newReport = true;
			$this->formID = $id;
			$this->id = ID::generate();
			
		}else{
			
			$this->id = $id;
			
			$response = DB::read("SELECT * FROM `reports` WHERE `id`=#id", $this->id);
			if($response->rows < 1){ throw new SoftException('Report not found'); }
			$this->formID = $response->first['form_id'];
			$this->hasPassword = !empty($response->first['password']);
            $this->password    = $response->first['password'];
			$this->config = json_decode($response->first['configuration']);
            $this->title = $response->first['title'];
		}
	}
	
	/**
	 * Returns the owner form
	 * @return 
	 */
	public function getForm(){
		$this->form = new Form($reposne->first['form_id']);
		return $this->form;
	}
	
	/**
	 * Saves or updates the report on database
	 * @param string $title Title of the report
	 * @param json_string $config configuration of the report
	 * @return \\ int ID of the saved report
	 */
	public function save($title, $config, $password = false){
		
		if(is_array($config)){
			$config = json_encode($config);
		}
		
		if($this->newReport){
			DB::write("INSERT INTO `reports` (`id`, `form_id`, `title`, `configuration`) VALUES(#id, #form_id, ':title', ':configuration')", 
					 $this->id, 
					 $this->formID,
					 $title,
					 $config);
		}else{
			DB::write("UPDATE `reports` SET `title`=':title', `configuration`=':config' WHERE `id`=#id", $title, $config, $this->id);
		}
        
        if($password !== false){
            
            if($password == '%%removepassword%%'){
                $password = ''; // Removed
            }
            
            DB::write("UPDATE `reports` SET `password`=':password' WHERE `id`=#id", $password, $this->id);
        }
        
        Utils::setCurrentID("report", $this->id);
		return $this->id;
	}
	
	/**
	 * Bri,ngs all reports of the given form
	 * @param int $formID parent forms ID
	 */
	public static function getAllByFormID($formID, $noConfig = false){
		$res = DB::read("SELECT * FROM `reports` WHERE `form_id` = #id", $formID);
		$reports = array();
		foreach($res->result as $line){
		    
		    $conf = json_decode($line["configuration"]);
			$reports[] = array(
			     "id" => $line["id"],
			     "title" => $line["title"],
				 "configuration" => $noConfig? "" : $conf,
                 "hasPassword"   => !empty($line['password']), 
                 "type" => "visual"
			);
		}
        
        
		return $reports;
	}
}