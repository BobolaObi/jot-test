<?php

class PasswordForReports extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `reports` ADD `password` VARCHAR( 64 ) NOT NULL COMMENT 'password for public reports'");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `reports` DROP `password` ");
    } //down()
}
