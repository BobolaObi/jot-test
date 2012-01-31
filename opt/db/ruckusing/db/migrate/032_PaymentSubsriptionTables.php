
<?php

class PaymentSubsriptionTables extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute( "CREATE TABLE IF NOT EXISTS `scheduled_downgrades` (
  `username` varchar(31) NOT NULL,
  `eot_time` datetime NOT NULL,
  `gateway` varchar(31) NOT NULL,
  `reason` varchar(31) NOT NULL,
  PRIMARY KEY (`username`),
  KEY `eot_time` (`eot_time`)
)" );
$this->execute("CREATE TABLE IF NOT EXISTS `jotform_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_time` datetime NOT NULL,
  `operation_date` datetime NOT NULL,
  `action` varchar(31) NOT NULL,
  `gateway` varchar(31) NOT NULL,
  `username` varchar(31) NOT NULL,
  `total` float NOT NULL,
  `period` varchar(31) NOT NULL,
  `currency` varchar(31) NOT NULL,
  `payment_status` varchar(31) NOT NULL,
  `subscription_id` varchar(200) NOT NULL,
  `note` text NOT NULL,
  `ip` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_time` (`date_time`),
  KEY `username` (`username`)
) ");
    } //up()

    public function down() { 
        $this->execute("DROP TABLE `jotform_payments`");
        $this->execute("DROP TABLE `scheduled_downgrades`");
    } //down()
}
