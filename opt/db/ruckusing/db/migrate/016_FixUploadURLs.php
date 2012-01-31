<?php 

include_once "lib/init.php";

class FixUploadURLs extends Ruckusing_BaseMigration {

    public function up() {
        $result = $this->select_all("SELECT * FROM `answers`");
        
        $uploadValues = array();
        foreach($result as $line){
            if(Utils::startsWith($line["value"], UPLOAD_URL)){
                $line["value"] = Utils::getFileName($line["value"]);
                $uploadValues[] = $line;
            }
        }
        
        foreach($uploadValues as $answer){
            DB::write("UPDATE `answers` SET `value`=':value' WHERE `form_id`=#id AND `submission_id`=':sid' AND `question_id`=#qid AND `item_name`=':itemname'",
                $answer["value"],
                $answer["form_id"],
                $answer["submission_id"],
                $answer["question_id"],
                $answer["item_name"]
            );
        }
    }
    
    public function down() {
        
    }
}
