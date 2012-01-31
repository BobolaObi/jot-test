<?
    if(DISABLE_SUBMISSON_PAGES){
        Utils::errorPage("JotForm is currently under a maintenance mode. Our first priority is to keep your forms working. That's why Submissions page and Reports are <b>temporarily unavailable today between 9am-5pm EST</b>. Please check back later. We are sorry for the inconvenience.", "Temporarily Unavailable", "put Copyright text", 200); 
    }
    $errorMessage = "You can't see the submission data for this form. Please login first";
    if (empty(Session::$username)) {
        Utils::errorPage($errorMessage);
    }
    try{
        $formID = Utils::get('formID');
        $form = new Form($formID);
        # This password must come from submision details
        $askPassword = Settings::getValue("public_submission_password", $formID);
        $public = false; # ot every submission page is public
        if(!$form->isLoggedInNow){
            if($askPassword !== false){
                
                # If it's an application and no SSL support use regular URL
                $base = APP && !Configs::HAVESSL? HTTP_URL : SSL_URL;
                
                # If correct password is entered and stored in session then show submissions
                if(isset($_SESSION['public']) && $_SESSION['public'] == $formID){
                    # If logout was sent in the URL, then remove the session and log user out
                    if(Utils::get('logout') !== false){
                        unset($_SESSION['public']);
                        Utils::redirect(str_replace('&logout', '', Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI']))));
                    }
                    # Set public true to restrict some options on the page
                    $public = true;
                }else if( md5(":jotform:".Utils::get('passKey') ) == $askPassword){ # Check the password if correct store it in session and redirect user to correct page
                    $_SESSION['public'] = $formID;
                    Utils::setCurrentID("form", $formID);                    
                    Utils::redirect(Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI'])));
                }else{ # Display ask password page to user
                    if(!IS_SECURE){
                        Utils::redirect(Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI'])));
                    }
                    $loginForm  = '<h4>Enter Password to Access Submissions</h4>';
                    $loginForm .= '<form method="post" action="'.Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI'])).'">';
                    $loginForm .= '<input type="hidden" name="p" value="submissions"/>';
                    $loginForm .= '<input type="hidden" name="formID" value="'.$formID.'"/>';
                    $loginForm .= '<label>Password: <input type="password" name="passKey" style="width:200px; font-size:16px; padding:5px;"></label><br>';
                    # If there is a password and still seeing the ask password page
                    # then it's a wrong password
                    if(Utils::get('passKey') !== false){
                        $loginForm .= '<div style="font-size:11px; color:red">Password did not match!!</div>';
                    }
                    $loginForm .= '<br><button style="padding:5px; font-size:14px;" type="submit">Show Submissions</button>';
                    $loginForm .= '</form>';
                    Utils::errorPage($loginForm, "Restricted Access", "Unauthorized access");
                }
                
            }else{
                Utils::errorPage($errorMessage, "Login Required", "Unauthorized access");
            }
        }
    }catch(Exception $e){
        Utils::errorPage($errorMessage, "Login Required", "Form not found: ".$formID, 200);
    }
    if($public){
        echo '<script>document.publicListing = true;</script>';
    }
?>

<div id="tool_bar" class="index-grad4">
    <div class="toolbar-set" id="group-submissions">
        <button class="big-button" onclick="Submissions.print()">
            <img src="images/toolbar/general/print.png" />
            <br>
            <span class="big-button-text locale">Print</span>
        </button>
        <button class="big-button" id="replyButton">
            <img src="images/toolbar/general/mail.png" />
            <br>
            <span class="big-button-text locale">Reply</span>
        </button>
        <button class="big-button" id="forwardButton">
            <img src="images/notification.png" />
            <br>
            <span class="big-button-text locale">Forward</span>
        </button>
        <button class="big-button" id="pendingButton" style="display:none">
            <div class="button-img-wrap">
                <img src="images/toolbar/general/wait.png" />
            </div>
            <span class="big-button-text locale">Pending Submissions</span>
        </button>
    </div>
    <div class="toolbar-set" id="group-submissions-edit" style="float:right;<?=($public)? 'display:none' : '';?>">
        <button id="edit-button" class="big-button" onclick="Submissions.editForm();">
            <img src="images/toolbar/general/edit.png" />
            <br>
            <span class="big-button-text locale">Edit</span>
        </button>
        <button id="delete-button" class="big-button" onclick="Submissions.deleteSubmission();">
            <img src="images/toolbar/general/delete.png" />
            <br>
            <span class="big-button-text locale">Delete</span>
        </button>
        <button id="cancel-button" style="display:none" class="big-button" onclick="Submissions.cancelEdit();">
            <img src="images/blank.gif" class="toolbar-undo" />
            <br>
            <span class="big-button-text locale">Cancel Edit</span>
        </button>
    </div>
    <? if($public){ ?>
        <div class="toolbar-set" style="float:right">
            <button id="logout-button" class="big-button" onclick="Submissions.logout();">
                <img alt="" src="images/toolbar-admin/logout.png"/>
                <br>
                <span class="big-button-text locale">Logout</span>
            </button>
        </div>
    <? } ?>
    <div id="toolbox_handler" style="float:right; padding-top:50px;">
    </div>
</div>

<div id="content-wrapper" style="display:none" tabindex="1">
    <div id="settings"></div>
    <div class="flip-holder">
        <img onclick="Submissions.prevRow();" src="images/left.png" />
    </div>
    <div id="notification" style="display:none;"></div>
    <div id="sub-content">
        
    </div>    
    <div class="flip-holder">
        <img onclick="Submissions.nextRow();" src="images/right.png" />
    </div>
</div>

<div style="clear:both; margin-top:69px;"></div>
<div id="submissions-grid" style="width:900px;height:450px;"></div>
