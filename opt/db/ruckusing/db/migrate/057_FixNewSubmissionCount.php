<?php

class FixNewSubmissionCount extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE  `forms` CHANGE  `new`  `new` INT( 11 ) NULL DEFAULT NULL");
        $this->execute("UPDATE `forms` SET `new`=-1 WHERE `new`=127");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE  `forms` CHANGE  `new`  `new` TINYINT( 1 ) NULL DEFAULT NULL");
    } //down()
}
