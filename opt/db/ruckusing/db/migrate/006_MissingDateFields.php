<?php

class MissingDateFields extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute("ALTER TABLE `reports` ADD `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP , ADD `updated_at` timestamp NULL DEFAULT NULL");
		$this->execute("CREATE TRIGGER update_report BEFORE UPDATE ON `reports` FOR EACH ROW SET NEW.updated_at = NOW()");
	}//up()

	public function down() {
        $this->execute("ALTER TABLE `reports` DROP `created_at`, DROP `updated_at`;");
		$this->execute("DROP TRIGGER update_report");
	}//down()
}
?>