<?php

class SpamProbRelations extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER TABLE  `spam_prob` CHANGE  `form_id`  `form_id` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL");
        $this->execute("ALTER TABLE `spam_prob` ADD CONSTRAINT `spam_prob_form_fk` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE  `spam_prob` DROP FOREIGN KEY  `spam_prob_ibfk_1`");
        $this->execute("ALTER TABLE  `spam_prob` CHANGE  `form_id`  `form_id` BIGINT( 20 ) NULL DEFAULT NULL");
    } //down()
}


