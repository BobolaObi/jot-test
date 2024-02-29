<?php

class LDAPColumn extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE `users` ADD `LDAP` INT NOT NULL COMMENT 'If this user account migrated from LDAP or not'");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `users` DROP `LDAP`");
    } //down()
}
