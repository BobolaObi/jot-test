<?php

class FormsDefaultSlugTrigger extends Ruckusing_BaseMigration {

	public function up() {
		$this->execute('DROP TRIGGER `insert_form`');

		$this->execute('
CREATE TRIGGER forms_before_insert BEFORE INSERT ON forms
FOR EACH ROW BEGIN
	IF NEW.slug = "" THEN
		SET NEW.slug = NEW.id;
	END IF;
	SET NEW.updated_at = NOW();
END;
');
	}//up()

	public function down() {
		$this->execute("DROP TRIGGER `forms_before_insert`");
		$this->execute("CREATE trigger insert_form BEFORE INSERT ON forms FOR EACH ROW SET NEW.updated_at = NOW()");
	}//down()
}
?>
