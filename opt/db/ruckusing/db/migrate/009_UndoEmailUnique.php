<?php

class UndoEmailUnique extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute("ALTER IGNORE TABLE `users` DROP index `users_email_unique`");
	}//up()

	public function down() {
        $this->execute("ALTER IGNORE TABLE `users` ADD UNIQUE KEY `users_email_unique` (`email`)");
	}//down()
}
?>