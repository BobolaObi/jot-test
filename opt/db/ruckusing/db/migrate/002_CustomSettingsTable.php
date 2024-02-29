<?php

class CustomSettingsTable extends Ruckusing_BaseMigration {

	public function up() {
            $this->execute("CREATE TABLE `custom_settings` (
`identifier` VARCHAR( 50 ) NOT NULL COMMENT 'any identifier to get unique property',
`key` VARCHAR( 50 ) NOT NULL COMMENT 'setting name',
`value` TEXT NOT NULL ,
UNIQUE (
`identifier` ,
`key`
)
) ENGINE = INNODB ");
	}//up()

	public function down() {
            $this->execute("DROP TABLE `custom_settings`");
	}//down()
}
?>
