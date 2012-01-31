<?php

class PendingEmails extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `jotform_new`.`pending_emails` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`email_config` TEXT NOT NULL ,
INDEX ( `created_at` )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
    } //up()

    public function down() { 
        $this->execute("DROP TABLE `pending_emails`");
    } //down()
}
