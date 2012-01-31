
<?php

class UploadFiles extends Ruckusing_BaseMigration {
    public function up() {
        $this->execute("ALTER  TABLE  `upload_files`  ADD  `uploaded` BOOL NOT  NULL");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `upload_files` DROP `uploaded`");
    } //down()
}
