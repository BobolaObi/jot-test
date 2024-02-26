<?php

namespace Quarantine;;

use Legacy\Jot\Exceptions\JotFormException;
use Legacy\Jot\RequestServer;
use Legacy\Jot\Utils\Utils;
use UFS;

Legacy\Jot\Exceptions\JotFormException;


class FileController extends UFS
{

    public function uploadFile()
    {

        # Set base name
        $uploadTarget = UPLOAD_FOLDER . "/" . $this->username . "/" . $this->formID . "/" . $this->submissionID . "/";

        # The local server ip
        # Create upload folder
        Utils::recursiveMkdir($uploadTarget);

        # Move file to the correct place
        $uploadFileTarget = $uploadTarget . Utils::fixUploadName($this->fileName);
        if (is_uploaded_file($this->fileTmpName)) {
            if (!@move_uploaded_file($this->fileTmpName, $uploadFileTarget)) {
                throw new JotFormException ("Cannot upload file.");
            }
        } else {
            if (!@rename($this->fileTmpName, $uploadFileTarget)) {
                throw new JotFormException ("Cannot upload reqular file.");
            }
        }
    }

    public function deleteSubmissionFiles()
    {

        $dir = UPLOAD_FOLDER . $this->username . "/" . $this->formID . "/" . $this->submissionID;
        if (file_exists($dir)) {
            $request = new RequestServer(array(
                "action" => 'removeSubmissionUpload',
                "toAll" => "yes",
                "formID" => $this->formID,
                "username" => $this->username,
                "submissionID" => $this->submissionID
            ), true);
        }

    }

    public static function getUploadUrl($username, $formID, $submissionID, $fileName)
    {
        return UPLOAD_URL . $username . "/" . $formID . "/" . $submissionID . "/" . Utils::fixUploadName($fileName);
    }

}