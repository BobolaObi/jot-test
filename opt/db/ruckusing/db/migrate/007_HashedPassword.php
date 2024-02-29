<?php

class HashedPassword extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute("ALTER TABLE `users` MODIFY `password` char(40) COLLATE utf8_unicode_ci DEFAULT NULL");
	}//up()

	public function down() {
	    // Won't enable down method since it will truncate the passwords irrevocably.
        // $this->execute("ALTER TABLE `users` MODIFY `password` varhar(31) COLLATE utf8_unicode_ci DEFAULT NULL");
	}//down()
}
?>