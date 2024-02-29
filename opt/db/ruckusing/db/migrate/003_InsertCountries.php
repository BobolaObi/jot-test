<?php

class InsertCountries extends Ruckusing_BaseMigration {

	public function up() {
             $adapter = $this->get_adapter();
             system("mysql -u root -h ".$adapter->db_info["host"]." jotform_new < ../countries.sql");
	}//up()

	public function down() {

	}//down()
}
?>
