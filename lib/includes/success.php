<?php



?>
<!-- Success: <?=is_array($success_notes)? join("::", $success_notes) : $success_notes?> -->
<!-- There is no better way to place a div in the middle of the screen. I'm not a fan of tables but this works perfectly fine -->

<div style="text-align:left">
    <img src="<?=HTTP_URL?>images/success.png" align="left">
    <h1 style=" margin-left:70px; margin-top:5px; margin-bottom:20px;text-align:left"><?=$success_title?></h1>
    <div style="text-align:center;">
        <?=$success_message?>
    </div>
</div>