<?php
		
class MigrationDateColumn extends Ruckusing_BaseMigration {		
		
	public function up() {		
	    $this->execute("ALTER TABLE `users` ADD `last_migration` TIMESTAMP NOT NULL COMMENT 'Just to keep migration we will delete this later'");   
	}//up()		
		
        public function down() {		
	    $this->execute("ALTER TABLE `users` DROP `last_migration`");		
	}//down()		
}