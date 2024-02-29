<?php

class UploadedFilesTable extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("CREATE TABLE `upload_files` (
                 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                 `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
                 `type` varchar(31) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
                 `size` int(10) unsigned NOT NULL,
                 `username` varchar(31) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
                 `form_id` bigint(20) unsigned NOT NULL DEFAULT '0',
                 `submission_id` bigint(20) unsigned NOT NULL DEFAULT '0',
                 PRIMARY KEY (`id`),
                 KEY `submission_id` (`submission_id`),
                 KEY `form_id` (`form_id`),
                 KEY `username` (`username`),
                 CONSTRAINT `upload_files_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
                 CONSTRAINT `upload_files_ibfk_2` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB AUTO_INCREMENT=1");
    } //up()

    public function down() { 
        $this->execute("DROP TABLE `upload_files`");
    } //down()
}
