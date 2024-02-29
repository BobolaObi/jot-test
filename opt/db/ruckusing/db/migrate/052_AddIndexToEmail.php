<?php

class AddIndexToEmail extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `users` ADD INDEX ( `email` )");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `users` DROP INDEX `email`");
    } //down()
}
