<?php
    include "lib/init.php";
    
    if(isset($_GET['sid'])){
        # Complete the submission pending redirect
        Submission::completeRedirect($_GET['sid']);
        exit;
    }
    
    # IF no submission ID was provided then redirect to thank you page
    Utils::redirect(HTTP_URL."thankyou.html");
?>