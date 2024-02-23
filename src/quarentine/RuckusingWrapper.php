<?php

use Legacy\Jot\Utils\Server;


/***
 * Ruckusing wrapper so that we are protected from changes made to the
 * upstream code. Also makes us more flexible.
 */


define('RUCKUSING_BASE', ROOT . "/opt/db/ruckusing/");
require_once RUCKUSING_BASE . '/config/config.inc.php';
require_once RUCKUSING_BASE  . '/lib/classes/util/class.Ruckusing_NamingUtil.php';
require_once RUCKUSING_BASE  . '/lib/classes/util/class.Ruckusing_VersionUtil.php';

/*
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_Logger.php';
require_once RUCKUSING_BASE . '/config/database.inc.php';
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_FrameworkRunner.php';
*/

class RuckusingWrapper {

    /**
     * Returns a hash with success value and the related message.
     * @param $name
     * @return unknown_type
     */
    public static function createNewMigration($name, $code) {
        
        if(!is_dir(RUCKUSING_MIGRATION_DIR)) {
            return array('success' => false, 'message' => "ERROR: migration directory '" . 
                RUCKUSING_MIGRATION_DIR . "' does not exist. Specify MIGRATION_DIR in config/config.inc.php and try again.");
        }

        $highestVersion  = Ruckusing_VersionUtil::get_highest_migration(RUCKUSING_MIGRATION_DIR);
        $nextVersion     = Ruckusing_VersionUtil::to_migration_number($highestVersion + 1);
        $klass            = Ruckusing_NamingUtil::camelcase($name);
        $fileName        = $nextVersion . '_' . $klass . '.php';
        $fullPath        = realpath(RUCKUSING_MIGRATION_DIR) . '/' . $fileName;
        if (empty($code)) {
            $code = <<<TPL
<?php\n
class $klass extends Ruckusing_BaseMigration {\n\n\tpublic function up() {\n\n\t}//up()
\n\tpublic function down() {\n\n\t}//down()
}
TPL;
        }
        
        //check to make sure our destination directory is writable
        if(!is_writable(RUCKUSING_MIGRATION_DIR . '/')) {
            return array('success' => false, 'message' => "ERROR: migration directory '" . 
                RUCKUSING_MIGRATION_DIR . "' is not writable by the current user. Check permissions and try again.");
        }

        //write it out!
        $file_result = file_put_contents($fullPath, $code);
        if($file_result === FALSE) {
            return array('success' => false, 'message' => 
                "Error writing to migrations directory/file. Do you have sufficient privileges?");
        } else {
            return array('success' => true, 'message' =>
                "Migration $fileName successfully created.");
        }
    }
    
    public static function getLatestVersionNumber() {
        return Ruckusing_VersionUtil::get_highest_migration(RUCKUSING_MIGRATION_DIR);
    }
    
    public static function migrateLatest() {
        // It seems that ruckusing is pretty complex to be used on it's own for migrating
        // to the latest version, unlike createNewMigration. Thus, reverting to use a hack.
        ob_start();
        $argv  = array("main.php", "db:migrate");
        if (isset(Server::$servers->db) && DB_HOST == Server::$servers->db->local->yunus) {
            array_push($argv, "ENV=production");
        }
        include ROOT . "/opt/db/ruckusing/main.php";
        $output = ob_get_contents();
        ob_end_clean();
        // Is the migration to latest really successful? I do not know. 
        // Return "success" nonetheless. 
        return array('success' => true, 'message' => $output);
    }
    
    public static function migrateToVersion($version) {
        // It seems that ruckusing is pretty complex to be used on it's own for migrating
        // to the latest version, unlike createNewMigration. Thus, reverting to use a hack.
        ob_start();
        $argv  = array("main.php", "db:migrate", "VERSION=" . $version);
        if (DB_HOST == Server::$servers->db->local->yunus) {
            array_push($argv, "ENV=production");
        }
        include ROOT . "/opt/db/ruckusing/main.php";
        $output = ob_get_contents();
        ob_end_clean();
        // Is the migration to latest really successful? I do not know. 
        // Return "success" nonetheless. 
        return array('success' => true, 'message' => $output);
    }
}