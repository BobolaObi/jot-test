<?php

class BlockEmailBanners extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `block_email_banners` (
                `username` VARCHAR( 31 ) NOT NULL, PRIMARY KEY ( `username` )
               ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = 'These users will not see banners on their emails'");
    } //up()

    public function down() { 
        $this->execute("DROP TABLE `block_email_banners`");
    } //down()
}
