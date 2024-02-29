<?php

class FixListingStatus extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `listings` CHANGE `status` `status` ENUM( 'ENABLED', 'SUSPENDED', 'DELETED', 'AUTOSUSPENDED', 'DISABLED' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'ENABLED'");
        $this->execute("UPDATE `listings` SET `status`='ENABLED' WHERE `status` IS NULL OR `status`=''");
    } //up()

    public function down() { 
        $this->execute("");
    } //down()
}
