<?
    include_once "../lib/init.php";
    
    if(!Session::isAdmin() && !Session::isSupport()){
        # Utils::errorPage("You should be loggedin as an admin", "Authentication Error.");
        Utils::show404("admin");
    }
    
    $_GET["p"] = "admin";
    include "../page.php";
?>