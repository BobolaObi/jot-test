<?php

use Legacy\Jot\Configs;

?>
<h2>Welcome Aboard!</h2>
<h3 style="color:#999">Your account has been created on <?=Configs::COMPANY_TITLE?>.</h3>
<h2>Your account information</h2>
<p>
    You username is: <b><?php echo $u->username; ?></b><br><br>
    
    Login to your account now:<br>
    <a href="<?=HTTP_URL?>login/"><?=HTTP_URL?>login/</a><br><br>
    
    Forgot your password? Request a reset:<br>
    
    <a href="<?=HTTP_URL?>server.php?action=sendPasswordReset&resetData=<?=$u->username?>&showMessage=true">Reset your password</a>
</p>