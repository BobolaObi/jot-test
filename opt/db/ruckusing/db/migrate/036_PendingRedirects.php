<?php

class PendingRedirects extends Ruckusing_BaseMigration {
    
    public function up() {
        $this->execute("CREATE TABLE `pending_redirects` (
             `form_id` bigint(20) NOT NULL,
             `submission_id` bigint(20) NOT NULL,
             `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
             `value` text COLLATE utf8_unicode_ci NOT NULL,
             PRIMARY KEY (`form_id`,`submission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    } //up()

    public function down() { 
        $this->execute("DROP TABLE `pending_redirects`");
    } //down()
}
