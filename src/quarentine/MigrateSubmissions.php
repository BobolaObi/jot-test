<?php

use Legacy\Jot\JotErrors;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Utils;

# no idea why, but this doesn't work with the name RequestServer....


class MigrateSubmissions {
    
    public $username;
    public $forms;
    public $migrationStartDate;
    public $lastMigrationDate;
    
    public function __construct($username){
       $this->username = $username;
       $this->migrationStartDate = date("Y-m-d H:i:s");
       
       $this->getLastMigrationDate();
       $this->getForms();
       $this->moveSubmissions();
       $this->setLastMigrationDate();
    }
    
    public function __destruct(){
        
    }
    
    public function getLastMigrationDate() {
        # Make sure we are on the new database
        DB::useConnection('new');
        $userResult = DB::read("SELECT `submission_last_migration` FROM `users` WHERE `username` = ':username'", $this->username);
        
        if($userResult->rows < 1) {
            throw new RecordNotFoundException( JotErrors::$MIGRATION_USER_NOT_FOUND );
        }
        
        if(isset($userResult->first['submission_last_migration']) && Utils::startsWith($userResult->first['submission_last_migration'], "0000")){
            # User seems to be not migrated yet so ignore merge property and do regular migration
            return;
        }
        Console::log("Was migrated before: ".$userResult->first['submission_last_migration']);
        $this->lastMigrationDate = $userResult->first['submission_last_migration'];
    }
    
    public function setLastMigrationDate() {
        # Make sure we are on the new database
        DB::useConnection('new');
        DB::write("UPDATE `users` SET `submission_last_migration`=':startDate' WHERE `username` = ':username'", $this->migrationStartDate, $this->username);
    }
    
    public function getForms(){
        DB::useConnection('main');
        $res = DB::read("SELECT * FROM `forms` WHERE `username`=':username'", $this->username);
        $this->forms = $res->result;
        unset($res);
    }
    
    /**
     * Checks if the form exists or not
     * @param object $id
     * @return 
     */
    public function isFormExist($id){
        DB::useConnection('new');
        $res = DB::read('SELECT `id` FROM `forms` WHERE `id`=#id', $id);
        return !($res->rows < 1);
    }
    
    
    public function moveSubmissions(){
        foreach($this->forms as $form) {
            
            if(!$this->isFormExist($form['id'])){ continue; }
            
            DB::useConnection('main');
            
            
            if($this->lastMigrationDate){
                $WHERE = "WHERE
                           `form_id`=#id
                            AND (`status` is NULL OR `status` != 'DELETED')
                            AND (`date_time` >= ':lastMigration')";

            }else{
                
                $WHERE = "WHERE
                           `form_id`=#id
                            AND (`status` is NULL OR `status` != 'DELETED')";
                                       
            }
            
            $res = DB::write("REPLACE INTO `jotform_new`.`submissions` 
                          (`id`, `form_id`, `ip` , `created_at`, `status`, `new`, `flag`, `notes`, `updated_at`) 
                       SELECT `id`, `form_id`, `ip`, `date_time`, `status`, 0, 0, '', NOW()
                       FROM   `jotform_main`.`submissions` ".$WHERE, 
                       
                      $form['id'], $this->lastMigrationDate);
            Console::log($res->rows." submission moved");
            
            $submissionsRes = DB::read('SELECT `id` FROM `submissions` '.$WHERE, $form['id'], $this->lastMigrationDate);
            foreach($submissionsRes->result as $submission){
                $res = DB::write("INSERT INTO `jotform_new`.`answers` (`form_id`, `submission_id`, `question_id`, `item_name`, `value`)
                           SELECT `form_id`, `submission_id`, `question_id`, '', `value`
                           FROM   `jotform_main`.`answers`
                           WHERE
                                   `form_id`=#id
                           AND
                                   `submission_id`=':sid'
                                   ", $form['id'], $submission['id']);
            }
            
        }
    }
    
}
