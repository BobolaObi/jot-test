<?php

/**
 * 
 * Users should not have null status values; it makes everything harder.
 * 
 */ 
class UserStatusNotNull extends Ruckusing_BaseMigration {

	public function up() {
	    // Below SQL generates warnings, NULL values are transformed into empty strings.
        $this->execute("ALTER TABLE `users` MODIFY `status` enum('ACTIVE', 'SUSPENDED','DELETED','AUTOSUSPENDED') COLLATE utf8_unicode_ci DEFAULT 'ACTIVE' NOT NULL");
        // This makes sure status is not an empty string.
        $this->execute("UPDATE `users` SET `status` = 'ACTIVE' WHERE `status` IS NULL OR `status` = ''");
	}//up()

	public function down() {
	    $this->execute("ALTER TABLE `users` MODIFY `status`  enum('SUSPENDED','DELETED','AUTOSUSPENDED') COLLATE utf8_unicode_ci DEFAULT NULL");
        $this->execute("UPDATE `users` SET `status` = NULL WHERE `status` = 'ACTIVE' OR `status` = ''");
	}//down()
}
?>