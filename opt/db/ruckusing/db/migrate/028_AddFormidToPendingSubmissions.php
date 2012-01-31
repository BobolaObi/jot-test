<?php

class AddFormidToPendingSubmissions extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `pending_submissions` ADD `form_id` BIGINT( 20 ) NOT NULL AFTER `submission_id` , ADD INDEX ( form_id )");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `pending_submissions` DROP `form_id`");
    } //down()
}
