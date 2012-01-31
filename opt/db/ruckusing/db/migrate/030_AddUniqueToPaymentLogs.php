<?php

class AddUniqueToPaymentLogs extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `payment_data_log` ADD UNIQUE (
                         `form_id` ,
                         `submission_id` ,
                         `gateway` ,
                         `log_type` ,
                         `log_name`
                        );");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `payment_data_log` DROP INDEX `form_id_2`");
    } //down()
}
