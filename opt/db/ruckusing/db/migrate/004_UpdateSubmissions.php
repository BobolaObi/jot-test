<?php

class UpdateSubmissions extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute('ALTER TABLE `submissions` ADD COLUMN `updated_at` timestamp NULL DEFAULT NULL');
        $this->execute(' CREATE TRIGGER update_submission BEFORE UPDATE ON submissions FOR EACH ROW SET NEW.updated_at = NOW()');
	}//up()

	public function down() {
        $this->execute('ALTER TABLE `submissions` DROP COLUMN `updated_at`');
        $this->execute('DROP TRIGGER update_submission');
	}//down()
}
?>
