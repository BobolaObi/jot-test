<fieldset class="accountField" id="accountinfo" style="padding:5px; padding-left:0">
    <legend class="locale">Welcome</legend>
    <div style="float:left;max-width:160px">
        <a style="color:#fff" href="myforms/"><? echo (Session::$name)? Session::$name : Session::$username; ?></a>
    </div>
    <div style="float:right; text-align:right">
        <? /* Links start with slash otherwise IE don't understand them. Don't do the fix for localhost */  ?>
        <a href="myaccount/" style="text-decoration:none;" class="big-button buttons buttons-dark locale">Account Settings</a>
        <a href="logout.php" style="text-decoration:none;" class="big-button buttons buttons-dark locale">Logout</a>
    </div>
</fieldset>
 
<fieldset class="accountField" id="accountinfo" style="padding:5px; padding-left:0">
    <legend class="locale">My Forms</legend>
    <ul class="recent-forms">
        <?
        
            $recentForms = FormViews::getRecentForms(Session::$username, 5);
            foreach($recentForms as $form){
                $count = "";
                if($form['new'] > 0){
                    $count = '<div class="notify" title="'.$form['new'].' new submissions"><div class="arrow"></div>'.$form['new']."</div>";
                }
                $buttons = ''.
                '<button title="Edit" type="button" onclick="location.href=\''.HTTP_URL.'?formID='.$form['id'].'\'" class="big-button buttons buttons-red edit"><img align="absmiddle" class="index-pencil" src="images/blank.gif"></button>'.
                '<button title="Submissions" type="button" onclick="Utils.checkSubmissionsEnabled(function(){ location.href=\''.HTTP_URL.'submissions/'.$form['id'].'\'; });" class="big-button buttons buttons-red submission"><img align="absmiddle" src="images/blank.gif" class="index-submissions"></button>';
                
                echo '<li>'.
                     '<span class="short-name" style="display:none">'.Utils::shorten($form['title'], 37).'</span>'.
                     '<span class="long-name">'.Utils::shorten($form['title'], 45).'</span>'. 
                      ' '.$count.$buttons.'</li>';
            }
            
        ?>
    </ul>
</fieldset>
