<?php
		
class ApiIphoneTableAdded extends Ruckusing_BaseMigration {		
		
	public function up() {		
	   $this->execute("
CREATE TABLE IF NOT EXISTS `api_iphone` (
  `username` varchar(31) collate utf8_unicode_ci NOT NULL,
  `iphone_id` varchar(512) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
		
	}//up()		
		
  public function down() {		
		$this->execute("drop table `api_iphone`");	
	}//down()		
}