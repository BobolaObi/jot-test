<?php
    include_once "../lib/init.php";
    
    $uploadController = new WaitingUploadsController();
    $uploadController->loopAllUploads();
    