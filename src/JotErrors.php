<?php

namespace Legacy\Jot;
class JotErrors{
    
    public static $MIGRATION_USER_NOT_FOUND = array("User cannot be found in old database.", "1000");
    public static $MIGRATED_USER_NOT_FOUND = array("This user has not been migrated yet.", "1001");
    /*
     * SPRITE errors
     */
    public static $SPRITE_GD_NOT_FOUND = array("GD Library is not installed on your server!", "1002");
    public static $SPRITE_FOLDER_NOT_FOUND = array("%s folder cannot be found.", "1003");
    public static $SPRITE_FOLDER_NOT_CREATED = array("%s folder cannot be created", "1004");
    public static $SPRITE_CANNOT_WRITE = array("Cannot write to the css file.", "1005");
    public static $SPRITE_CANNOT_SAVE_NONEREPEAT = array("Cannot save none-repeat image.", "1006");
    public static $SPRITE_CANNOT_SAVE_Y_REPEAT = array("Cannot save y-repeat image.", "1007");
    public static $SPRITE_CANNOT_SAVE_X_REPEAT = array("Cannot save x-repeat image.", "1008");
    /*
     * DB Errors
     */
    public static $DB_CANNOT_SELECT = array('Cannot Select Database<br> <i><b>Error Returned:</b>%s</i>', "1009");
    public static $DB_CANNOT_CONNECT = array('Cannot connect to database, username, password or host information is wrong <br><i><b> Error Returned:</b> %s</i>', "1010");
    public static $DB_QUERY_ERROR = array("Error on query: %s<br>Error returned:%s", "1011");
    public static $DB_REPLACE_NOT_ALLOWED = array("REPLACE INTO in %s table is not allowed.<br>Query: %s", "1012");
    public static $DB_UPDATE_WITHOUT_WHERE = array("Update without WHERE clause is not allowed", "1013");
    
    /*
     * Form errors
     */
    public static $FORM_AUTH_ERROR = array("You don't have permission to view this form's submissions", "1014");
    
    
    public static function get(){
        $args = func_get_args();
        $err = self::$$args[0];
        $args[0] = $err[0];
        $err[0] = call_user_func_array("sprintf", $args);
        return $err;
    }
}
