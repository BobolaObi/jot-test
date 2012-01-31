
<?php

class RefererColumn extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `users` ADD `referer` VARCHAR( 500 ) NOT NULL COMMENT 'Origin of the user'");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `users` DROP `referer`");
    } //down()
}
