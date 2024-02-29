<?php
		
class DeleteOldEmails extends Ruckusing_BaseMigration {		
		
	public function up() {
	    // VERY VERY Dangerous 		
	    // $this->execute("DELETE FROM `form_properties` WHERE `type`='emails'");
	}//up()		
		
        public function down() {		
			
	}//down()		
}
