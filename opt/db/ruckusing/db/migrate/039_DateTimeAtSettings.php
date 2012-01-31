<?php

class DateTimeAtSettings extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `custom_settings` ADD `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    } //up()

    public function down() { 
        $this->execute("");
    } //down()
}
