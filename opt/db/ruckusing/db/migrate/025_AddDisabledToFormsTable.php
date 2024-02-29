<?php

class AddDisabledToFormsTable extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `forms` CHANGE `status` `status` ENUM( 'ENABLED', 'DISABLED', 'SUSPENDED', 'DELETED', 'AUTOSUSPENDED' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `forms` CHANGE `status` `status` ENUM( 'ENABLED', 'SUSPENDED', 'DELETED', 'AUTOSUSPENDED' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ");
    } //down()
}
