<?php

class ListingFiledListAltered extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `listings` CHANGE `fields` `fields` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
    } //up()

    public function down() { 
        $this->execute("");
    } //down()
}
