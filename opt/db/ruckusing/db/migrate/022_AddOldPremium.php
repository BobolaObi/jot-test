<?php
		
class AddOldPremium extends Ruckusing_BaseMigration {		
		
	public function up() {		
	  $this->execute("ALTER TABLE `users` CHANGE `account_type` `account_type` ENUM( 'FREE', 'OLDPREMIUM', 'PREMIUM', 'PROFESSIONAL', 'ADMIN', 'GUEST' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'FREE'");	
	}//up()		
		
        public function down() {		
	  $this->execute("ALTER TABLE `users` CHANGE `account_type` `account_type` ENUM( 'FREE', 'PREMIUM', 'PROFESSIONAL', 'ADMIN', 'GUEST' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'FREE'");		
	}//down()		
}