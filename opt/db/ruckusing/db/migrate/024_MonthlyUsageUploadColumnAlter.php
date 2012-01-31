<?php

class MonthlyUsageUploadColumnAlter extends Ruckusing_BaseMigration {
    public function up() {
       $this->execute("ALTER TABLE `monthly_usage` CHANGE `uploads` `uploads` VARCHAR( 20 ) NOT NULL");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `monthly_usage` CHANGE `uploads` `uploads` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'");
    } //down()
}
