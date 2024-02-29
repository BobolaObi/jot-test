<?php 

class BouncedList extends Ruckusing_BaseMigration {
    public function up() { 
        $this->execute("CREATE TABLE `bounced_emails` (
                        `email` VARCHAR( 250 ) NOT NULL ,
                         PRIMARY KEY ( `email` )
                        ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci 
                        COMMENT = 'Contains addresses that we should not send emails to.'");
    } //up()
    
    public function down() {
        $this->execute("DROP TABLE `bounced_emails`");
    } //down()
}
