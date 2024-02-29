<?php

class SetAllEmptyFormStatusesToENABLED extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `forms` CHANGE `status` `status` ENUM( 'ENABLED', 'DISABLED', 'SUSPENDED', 'DELETED', 'AUTOSUSPENDED' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'ENABLED'"); 
        $this->execute("UPDATE `forms` SET `status`='ENABLED' WHERE `status` IS NULL OR `status` = ''");
    } //up()

    public function down() { 
        // $this->execute("");
    } //down()
}
