<?php

class SessionIdForSubmissions extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `pending_submissions` ADD `session_id` VARCHAR( 90 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Unique with formID, use for pagination', ADD INDEX ( `session_id` )");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `pending_submissions` DROP `session_id`");
    } //down()
}
