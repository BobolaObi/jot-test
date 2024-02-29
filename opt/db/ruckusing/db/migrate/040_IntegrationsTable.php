<?php

class IntegrationsTable extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `integrations` (
                        `partner` VARCHAR( 32 ) NOT NULL ,
                        `username` VARCHAR( 32 ) NOT NULL ,
                        `form_id` BIGINT( 20 ) NOT NULL ,
                        `key` VARCHAR( 50 ) NOT NULL ,
                        `value` TEXT NOT NULL ,
                        `updated_at` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                        PRIMARY KEY ( `partner` , `username` , `form_id` , `key` )
                        ) ENGINE = InnoDB;");
    } //up()

    public function down() { 
        $this->execute("");
    } //down()
}
