<?php

class FormsUsernameSlugIndex extends Ruckusing_BaseMigration {

	public function up() {
		$this->execute('UPDATE `forms` set slug = id where slug = ""');
		$this->execute('CREATE UNIQUE INDEX `forms_username_slug_index` ON `forms` (`username`, `slug`)');

	}//up()

	public function down() {
		$this->execute('DROP INDEX `forms_username_slug_index` ON `forms`');
	}//down()
}
?>
