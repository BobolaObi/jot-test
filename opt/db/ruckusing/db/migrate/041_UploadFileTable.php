
<?php

class UploadFileTable extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute(" ALTER TABLE `upload_files` ADD UNIQUE upload_entry (
`name`,`username`,`form_id`,`submission_id`)");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `upload_files` DROP INDEX `upload_entry`");
    } //down()
}
