<?php

namespace Quarantine;;

use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use UFSController;
use UploadControllers;
use UserCrawler;

class UploadToS3 extends UserCrawler
{

    const connectionLimit = 90;

    public function setProperties()
    {
        $this->limit = 10;
        $this->userFrequency = 1;
        $this->executeDelay = 0;
    }

    /**
     * This function is executed after each user.
     */
    public function execute($username = false)
    {

        # Get username
        # If username is send from parameters, work for that user.
        if ($username === false) {
            $username = $this->userDetails['username'];
        }

        if (!trim('' . $username)) {
            return;
        }

        /**
         * Those usernames cannot be handles.
         */
        if (in_array($username, array(".", ".."))) {
            return;
        }

        # Loop through all form upload folders
        $uploadFolders = glob(preg_quote(UPLOAD_FOLDER . $username) . "/*");

        foreach ($uploadFolders as $formFolder) {

            # Get formID
            $folder = explode("/", $formFolder);
            $formID = array_pop($folder); # Get the formID from folder name

            # Check given form on the database
            $res = DB::read("SELECT `id` FROM `forms` WHERE `id`=#id", $formID);

            # If form exists.
            if ($res->rows > 0) {
                # For each submission
                $submissionFolders = glob(preg_quote(UPLOAD_FOLDER . $username . "/" . $formID, DIRECTORY_SEPARATOR) . "/*");

                foreach ($submissionFolders as $submissionFolder) {

                    if (is_file('/tmp/stop')) {
                        exit;
                    }

                    $folder = explode("/", $submissionFolder);
                    $sid = array_pop($folder);

                    $res = DB::read("SELECT `id` FROM `submissions` WHERE `id`=':id'", $sid);
                    if ($res->rows > 0) {
                        if ($handle = opendir($submissionFolder)) {
                            while (false !== ($file = readdir($handle))) {
                                if ($file != "." && $file != "..") {

                                    $filePath = $submissionFolder . "/" . $file;
                                    $fileProperties = array(
                                        "name" => $file,
                                        "type" => "transferred",
                                        "tmp_name" => false,
                                        "size" => filesize($filePath)
                                    );
                                    $this->log("File: " . $fileProperties["name"] . "Form id: " . $formID . "Submission id: " . $submissionFolder);
                                    $ufsc = new UFSController ($username, $formID, $sid, $fileProperties, array(UploadControllers::AmazonS3Controller));
                                    if ($ufsc->fileUploaded() === false) {

                                        $this->checkServer();

                                        $ufsc->uploadFile();
                                        # Wait 1 second after each upload
                                        usleep(0.2 * 1000000);
                                    }
                                    $key++;
                                }
                            }
                            closedir($handle);
                        }
                    }
                }
                # End of foreach submission.
            }
        }
        # End of foreach form.
    }

    # End of method.

    private function isConnectionCritical($printLoad = false)
    {
        $connectionCount = intval(@shell_exec("netstat -an | grep 72.21 | wc -l"));
        if ($printLoad) {
            Console::log("Connection count is: " . $connectionCount);
        }
        return $connectionCount > self::connectionLimit;
    }

    private function checkServer()
    {
        $printLoad = true;
        while ($this->isConnectionCritical($printLoad) || $this->isLoadCritical($printLoad)) {
            sleep(5);
            $printLoad = false;
            # Wait while connection is critical.
            if (is_file('/tmp/stop')) {
                exit;
            }
        }
    }
}