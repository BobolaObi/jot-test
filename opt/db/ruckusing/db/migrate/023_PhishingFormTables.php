<?php
class PhishingFormTables extends Ruckusing_BaseMigration {

	public function up() {

		$this->execute("CREATE TABLE IF NOT EXISTS `deleted_forms` (
			  `id` bigint(20) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		$this->execute("CREATE TABLE IF NOT EXISTS `spam_filter` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `word` varchar(50) DEFAULT NULL,
			  `occurance_count` int(11) DEFAULT NULL,
			  `is_spam` tinyint(1) DEFAULT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `word` (`word`,`is_spam`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;");
		$this->execute("CREATE TABLE IF NOT EXISTS `spam_prob` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `form_id` bigint(20) DEFAULT NULL,
			  `spam_prob` float DEFAULT NULL,
			  `suspended` tinyint(1) DEFAULT NULL,
			  `status` varchar(50) NOT NULL DEFAULT 'NORMAL',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `form_id` (`form_id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		$this->execute("CREATE TABLE IF NOT EXISTS `whitelist` (
			  `form_id` bigint(20) NOT NULL,
			  PRIMARY KEY (`form_id`)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

	}//up()

	public function down() {
		$this->execute("DROP TABLE `deleted_forms`");
		$this->execute("DROP TABLE `spam_filter`");
		$this->execute("DROP TABLE `spam_prob`");
		$this->execute("DROP TABLE `whitelist`");
	}//down()
}