<?php

class PendingSubmissionsFk extends Ruckusing_BaseMigration {

	public function up() {
	    // This is needed to make sure both forms::id and pending_submissions::form_id have the same
	    // data type; or else the FK does not work.
        $this->execute("ALTER TABLE `pending_submissions` MODIFY `form_id` bigint(20) unsigned NOT NULL");
        $this->execute("ALTER TABLE `pending_submissions` ADD CONSTRAINT `pending_submissions_fk_id` " . 
                       "FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
	}//up()

	public function down() {
        $this->execute("ALTER TABLE `pending_submissions` DROP FOREIGN KEY `pending_submissions_fk_id`");
	}//down()
}
?>