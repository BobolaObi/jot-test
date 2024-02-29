<?php

class FingerPrintTests extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `fingerprint` (
                          `username` varchar(255) collate utf8_unicode_ci NOT NULL,
                          `info` text collate utf8_unicode_ci NOT NULL,
                          PRIMARY KEY (`username`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    } //up()

    public function down() { 
        $this->execute("DROP `fingerprint`");
    } //down()
}
