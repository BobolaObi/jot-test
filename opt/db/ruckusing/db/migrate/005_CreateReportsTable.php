<?php

class CreateReportsTable extends Ruckusing_BaseMigration {

	public function up() {
        $this->execute('CREATE TABLE `reports` (
            `id` bigint(20) unsigned NOT NULL,
            `form_id` bigint(20) unsigned NOT NULL,
            `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `configuration` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            KEY `form_id` (`form_id`),
            CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
	}//up()

	public function down() {
        $this->execute('DROP TABLE reports');
	}//down()
}
