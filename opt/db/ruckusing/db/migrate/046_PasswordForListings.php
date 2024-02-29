
<?php

class PasswordForListings extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `listings` ADD `password` VARCHAR( 64 ) NOT NULL COMMENT 'password for public listings'");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `listings` DROP `password`");
    } //down()
}
