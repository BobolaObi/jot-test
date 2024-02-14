Dear <?php use Legacy\Jot\Configs;

echo (empty($userArr['name']))? $userArr['username'] : $userArr['name'] ?>,<br />
<br />
A password reset request was issued at <a href="<?=HTTP_URL?>"><?=HTTP_URL?></a><br />
<br />
To reset your password visit <br /><?php echo "<a href='$passResetURL'>$passResetURL</a>"; ?> .<br />
<br />
If you have not requested resetting your password, please ignore this e-mail.<br />
<br />
Thanks for using <?=Configs::COMPANY_TITLE?>,<br />
<br />
<?=Configs::COMPANY_TITLE?> Support<br />
<a href="<?=HTTP_URL?>"><?=HTTP_URL?></a>