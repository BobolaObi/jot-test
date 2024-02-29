
<?php
		
class PasswordFieldLength extends Ruckusing_BaseMigration {		
		
	public function up() {		
	   $this->execute('ALTER TABLE `users` CHANGE `password` `password` CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL');		
	}//up()		
		
  public function down() {		
	   $this->execute('ALTER TABLE `users` CHANGE `password` `password` CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL');		
	}//down()		
}