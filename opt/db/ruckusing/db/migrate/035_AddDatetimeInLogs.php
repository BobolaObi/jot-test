<?php

class AddDatetimeInLogs extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `payment_data_log` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `form_id`");
        $this->execute("ALTER TABLE `pending_submissions` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `submission_id` ,
ADD `type` VARCHAR( 50 ) NOT NULL AFTER `created_at`");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `payment_data_log` DROP `created_at`");
        $this->execute("ALTER TABLE `pending_submissions` DROP `created_at`,DROP `type`;");
    } //down()
}
