<?php

class UsersStatusOverlimit extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute("ALTER TABLE `users` MODIFY `status` enum('ACTIVE', 'SUSPENDED','DELETED','AUTOSUSPENDED', 'OVERLIMIT') COLLATE utf8_unicode_ci DEFAULT 'ACTIVE' NOT NULL");
	}//up()

	public function down() {
        $this->execute("ALTER TABLE `users` MODIFY `status` enum('ACTIVE', 'SUSPENDED','DELETED','AUTOSUSPENDED') COLLATE utf8_unicode_ci DEFAULT 'ACTIVE' NOT NULL");
	}//down()
}
?>