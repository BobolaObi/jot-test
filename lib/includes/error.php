<!-- Error: <?=is_array($error_notes)? join("::", $error_notes) : $error_notes?> -->
<!-- There is no better way to place a div in the middle of the screen. I'm not a fan of tables but this works perfectly fine -->

<div style="text-align:left">
    <div style="text-align:center; display:inline-block;">
        <img src="<?=HTTP_URL?>images/warning.png" align="left">
        <h1 style=" margin-left:70px; margin-top:5px; margin-bottom:20px;text-align:left"><?=$error_title?></h1>
    </div>
    <div style="text-align:center;">
        <?=$error_message?>
    </div>
</div>