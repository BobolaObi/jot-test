<?php

class UserAccountTypeNotNull extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute("ALTER TABLE `users` MODIFY `account_type` enum('FREE','PREMIUM','ENTERPRISE','ADMIN','GUEST') COLLATE utf8_unicode_ci DEFAULT 'FREE' NOT NULL");
        $this->execute("UPDATE `users` SET `account_type` = 'FREE' WHERE `account_type` IS NULL OR `account_type` = ''");
	}//up()

	public function down() {
        $this->execute("ALTER TABLE `users` MODIFY `account_type` enum('FREE','PREMIUM','ENTERPRISE','ADMIN','GUEST') COLLATE utf8_unicode_ci DEFAULT NULL");
        // Active is for Status column. This resolves the bug I introduced.
        $this->execute("UPDATE `users` SET `account_type` = NULL WHERE `account_type` = 'ACTIVE' OR `account_type` = 'FREE'");  
	}//down()
}
?>