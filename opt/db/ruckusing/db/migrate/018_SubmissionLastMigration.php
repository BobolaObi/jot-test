<?php
		
class SubmissionLastMigration extends Ruckusing_BaseMigration {		
		
	public function up() {		
	    $this->execute("ALTER TABLE `users` ADD `submission_last_migration` TIMESTAMP NOT NULL COMMENT 'Just to keep migration we will delete this later'");   
	}//up()
		
        public function down() {		
	    $this->execute("ALTER TABLE `users` DROP `submission_last_migration`");		
	}//down()		
}