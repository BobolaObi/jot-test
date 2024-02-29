<?php

class ABTestingTables extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `test_participants` (
            `id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `username` VARCHAR( 100 ) NOT NULL ,
            `test_name` VARCHAR( 100 ) NOT NULL ,
            `group_name` VARCHAR( 100 ) NOT NULL ,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            UNIQUE (
                    `username` ,
                    `test_name`
            )
        ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
        
     $this->execute("CREATE TABLE `test_goals` (
            `id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `username` VARCHAR( 100 ) NOT NULL ,
            `goal_name` VARCHAR( 100 ) NOT NULL ,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            INDEX ( `username` )
        ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;");

    } //up()

    public function down() { 
        $this->execute("");
    } //down()
}
