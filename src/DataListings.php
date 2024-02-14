<?

namespace Legacy\Jot;

use Legacy\Jot\Configs;

class DataListings{
    /**
     * Gets the listing from database
     * @param object $id
     * @return 
     */
    public static function getListing($id){
        $res = DB::read("SELECT * FROM `listings` WHERE `id` = #id", $id);
        if($res->rows < 1){
            return false;
        }
        
        if(strstr($res->first['fields'], ":")){
            $newFieldList = array();
            foreach(explode(",", $res->first['fields']) as $line){
                @list($key, $value) = explode(":", $line);
                if(!isset($value)){ continue;  }
                @$newFieldList[$key] = $value;
            }
        }else{
            $res->first['fields'] = explode(",", $res->first['fields']);
            $newFieldList = array();
            
            foreach($res->first['fields'] as $field){
                if($field == 'ip'){
                    $newFieldList[] = -1;
                }else if($field == 'dt'){
                    $newFieldList[] = -2;
                }else if($field == 'id'){
                    $newFieldList[] = -3;
                }else{
                    $newFieldList[] = $field;
                }
            }
        }
        
        $res->first['fields'] = $newFieldList;
        return $res->first;
    }
    
    /**
     * Gets a listing id and displays it accordin to the type
     * @return 
     * @param object $id
     */
    public static function showListing($id){
        if(DISABLE_SUBMISSON_PAGES){
            Utils::errorPage("JotForm is currently under a maintenance mode. Our first priority is to keep your forms working. That's why Submissions page and Reports are <b>temporarily unavailable today between 9am-5pm EST</b>. Please check back later. We are sorry for the inconvenience.", "Temporarily Unavailable", "", 200); 
        }
        if( ! ( $list = self::getListing($id) ) ){
            Utils::show404($id);
            //Utils::errorPage("This listing cannot be found.", "Error");
            return; // Listing cannot be found
        }
        
        if($list['status'] != 'ENABLED' && !empty($list['status'])){
            Utils::errorPage("This listing is disabled by the owner.", "Error");
        }
        # If it's an application and no SSL support use regular URL
        $base = APP && !Configs::HAVESSL? HTTP_URL : SSL_URL;
        
        if(!empty($list['password'])){
            session_start();
            if(Utils::get('logout') !== false){
                unset($_SESSION["passwordListing_".$id]);
                Utils::redirect(str_replace('?logout', '', Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI']))));
            }
            
            if(isset($_SESSION["passwordListing_".$id]) && $_SESSION["passwordListing_".$id] != $list['password']){
                unset($_SESSION["passwordListing_".$id]);
            }
        }
        
        if(!empty($list['password']) && !isset($_SESSION["passwordListing_".$id])){
            if(Utils::get('passKey') && Utils::get('passKey') == $list['password']){
                $_SESSION["passwordListing_".$id] = $list['password'];
                Utils::redirect(Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI'])));
            }else{
                if(!APP && !IS_SECURE){
                    Utils::redirect(Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI'])));                
                }
                $loginForm  = '<h4>Enter Password to Access Submissions</h4>';
                $loginForm .= '<form method="post" action="'.Utils::path($base.str_replace(Configs::SUBFOLDER, "",$_SERVER['REQUEST_URI'])).'">';
                $loginForm .= '<label>Password: <input type="password" name="passKey" style="width:200px; font-size:16px; padding:5px;"></label><br>';
                # If there is a password and still seeing the ask password page
                # then it's a wrong password
                if(Utils::get('passKey') !== false){
                    $loginForm .= '<div style="font-size:11px; color:red">Password did not match!!</div>';
                }
                $loginForm .= '<br><button style="padding:5px; font-size:14px;" type="submit">Show Listing</button>';
                $loginForm .= '</form>';
                Utils::errorPage($loginForm, "Restricted Access", "Unauthorized access");
            }
        }
        
        switch($list['list_type']){
            case "grid":
                $_GET['formID'] = $list['form_id'];
                $_GET['listID'] = $id;
                include_once "grid.php";
                break;
            case "excel":
                $form = new Form($list['form_id']);
                $form->getExcel($list['fields'], 'include');
                break;
            case "cal":
                $form = new Form($list['form_id']);
                $form->getCalendar($list['fields']['title'], $list['fields']['datetime']);
                break;
            case "rss":
                $rss = new RSSHelper($list['form_id'], $list['fields']);
                $rss->show();
                break;
            case "csv":
            	$form = new Form($list['form_id']);
                $form->getCSV($list['fields'], 'include');
                break;
            case "table":
            	$form = new Form($list['form_id']);
                $form->getTable($list['fields'], 'include');
            	break;
        }
        
        exit;
    }
    
    /**
     * Creates a listing
     */
    public static function createListing($formID, $title, $type, $fields, $password = false){
        $id = ID::generate();
        
        if($password == "%%removepassword%%"){
            $password = true;
        }
        
        DB::insert('listings', array(
            "id"        => $id,
            "form_id"   => $formID,
            "title"     => $title,
            "fields"    => $fields,
            "list_type" => $type,
            "status"    => "ENABLED",
            "password"  => $password
        ));
        return $id;
    }
    /**
     * Updates a listing on database
     * @param object $listID
     * @param object $title
     * @param object $type
     * @param object $fields
     * @return 
     */
    public static function updateListing($listID, $title, $type, $fields, $password = false){
        DB::write("UPDATE `listings` SET `title`=':title', `list_type`=':type', `fields`=':fields' WHERE `id`=#id", 
                  $title, $type, $fields, $listID);
        
        if($password !== false){
            if($password == "%%removepassword%%"){
                DB::write("UPDATE `listings` SET `password`='' WHERE `id`=#id", $listID);
            }else{
                DB::write("UPDATE `listings` SET `password`=':password' WHERE `id`=#id", $password, $listID);
            }
        }
        
        return $listID;
    }
    
    /**
     * Deletes a listing from database
     * @param object $listID
     * @return 
     */
    public static function deleteListing($listID){
        DB::write("DELETE FROM `listings` WHERE `id`=#id", $listID);
        return $listID;
    }
    
    /**
     * get all listings by formID
     * @param object $formID
     * @return 
     */
    public static function getAllByFormID($formID, $noConfig = false){
        
        $res = DB::read("SELECT * FROM `listings` WHERE `form_id` = #id", $formID);
        $listings = array();
        foreach($res->result as $line){
            $listings[] = array(
                 "id" => $line["id"],
                 "title" => $line["title"],
                 "configuration" => $noConfig? "" : explode(",", $line["fields"]),
                 "hasPassword" => !empty($line['password']),
                 "type" => $line['list_type']
            );
        }

        return $listings;
    }
}
