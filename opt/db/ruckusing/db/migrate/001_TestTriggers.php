<?php

class TestTriggers extends Ruckusing_BaseMigration {

	public function up() {
		$this->execute("
CREATE TRIGGER bootstrap_monthly_usage AFTER INSERT ON users
    FOR EACH ROW BEGIN
        INSERT INTO monthly_usage SET username = NEW.username;
    END;
");

	}//up()

	public function down() {
		$this->execute("drop trigger bootstrap_monthly_usage;");
	}//down()
}
?>
