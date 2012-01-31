<?php

class UploadFilesTable2 extends Ruckusing_BaseMigration {
    public function up(){
        $this->execute("ALTER  TABLE  `upload_files`  ADD  `date` DATETIME NOT  NULL");
    } //up()

    public function down() { 
        $this->execute("ALTER TABLE `upload_files` DROP `date`");
    } //down()
}
