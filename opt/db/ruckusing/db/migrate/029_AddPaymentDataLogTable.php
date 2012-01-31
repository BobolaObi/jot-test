<?php

class AddPaymentDataLogTable extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `payment_data_log` (
                        `form_id` BIGINT( 20 ) NOT NULL ,
                        `submission_id` BIGINT( 20 ) NOT NULL ,
                        `gateway` VARCHAR( 30 ) NOT NULL ,
                        `log_type` VARCHAR( 30 ) NOT NULL COMMENT 'type of the log, (posted data or IPN response or such)',
                        `log_name` VARCHAR( 30 ) NOT NULL COMMENT 'identifier of the log data',
                        `log_data` TEXT NOT NULL COMMENT 'content of the log, searchable text',
                        
                       INDEX ( `form_id` , `submission_id` )
                       ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = 'Keeps the any data transferred between gateways and jotform'");
    } //up()

    public function down() { 
        $this->execute("DROP TABLE `payment_data_log`");
    } //down()
}
