<?php

class DropFingerPrint extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("DROP TABLE `fingerprint`");
    } //up()

    public function down() { 
        $this->execute("");
    } //down()
}
