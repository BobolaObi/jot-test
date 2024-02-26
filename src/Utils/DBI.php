<?php
/**
 * Database connection object.
 * first thing to do is creating the connection parameters. Call DB::setDB() it will automatically connect the database when needed
 * Can connect to one database server ONLY at a time.
 * @package JotForm_Utils
 * @author Serkan Yerşen <serkan@interlogy.com>
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

use Legacy\Jot\Configs;
use Legacy\Jot\Exceptions\DBException;
use Legacy\Jot\JotErrors;

class DBI
{
    # Private veraibles
    private static $database, # Database Name
        $username,                # Database Username
        $password,                # Database Password
        $hostname,                # Host Address or Name
        $dlink = NULL,     # PHP - MySQL resource ID
        $queryTime,        # For keeping query time.
        $timers = array(), # Query times, for benchmarking
        $connections = array(),
        $currentDB;

    public static $syncQueries = array();  # Query that will be executed for syncronization of the database.

    /**
     * Will log the queries slower than this
     */
    const slowQueryLimit = 3.000; # Log queries took longer than this (in seconds);

    /**
     * Creates aconnection with given parameters and keeps it open
     * @param  $database // Database name
     * @param  $username // [optional] DB user username
     * @param  $password // [optional] DB User password
     * @param  $hostname // [optional] DB hostname
     * @return
     */
    static function setConnection($name, $database, $username = "root", $password = "", $hostname = "localhost")
    {

        self::$connections[$name] = array(
            "database" => $database,
            "username" => $username,
            "password" => $password,
            "hostname" => $hostname
        );

    }

    static function useConnection($name)
    {
        self::$currentDB = $name;
        self::$dlink = false;
    }

    /**
     * Sanitize database input
     * @link http://xkcd.com/327/
     * @param  $str
     * @return
     */
    static function escape($str)
    {
        if (!$str) {
            return $str;
        }
        if (!is_string($str)) {
            return $str;
        }

        if (!self::$dlink) {
            return mysql_escape_string($str);
        }

        return @mysqli_real_escape_string(self::$dlink, $str);
    }

    /**
     * Sets the connection to a database
     * @return // object database link referance
     */
    static function connect()
    {

        if (self::$dlink && self::$connections[self::$currentDB]['link'] == self::$dlink) {
            return self::$dlink;
        }

        $connect = self::$connections[self::$currentDB];

        self::$dlink = mysqli_init();

        if (Configs::DB_USE_SSL) {
            mysqli_ssl_set(self::$link, null, null, null, null, Configs::SSL_CIPHER);
        }

        if (@mysqli_real_connect(self::$dlink, $connect['hostname'], $connect['username'], $connect['password'])) {
            if (!@mysqli_select_db(self::$dlink, $connect['database'])) {
                Utils::errorPage("JotForm is under maintenance for providing better service. Please bear with us.", "Maintenance", mysqli_connect_error());
                # throw new DBException(JotErrors::get("DB_CANNOT_SELECT", mysql_error()));
            }
        } else {
            Utils::errorPage("JotForm is under maintenance for providing better service. Please bear with us.", "Maintenance", mysqli_connect_error());
            # throw new DBException(JotErrors::get("DB_CANNOT_CONNECT", mysql_error()));
        }

        self::$connections[self::$currentDB]['link'] = self::$dlink;

        return self::$dlink;
    }

    /**
     * Getter for link
     * @return // object database link reference
     */
    static function getLink()
    {
        return self::$dlink;
    }

    /**
     * Gets the function arguments as an array then parses the first one like printf, it also sanitzes the inputs
     * Use <b>:name</b> for strings
     * Use <b>#name</b> for numbers
     * @param  $args
     * @return // string Parsed Query
     */
    public static function parseQuery($args)
    {
        # If there's no connection, do a connection first.
        if (!self::$dlink) {
            self::connect();
        }

        global $arguments, $i;
        $arguments = $args;
        $i = 1;
        $query = $arguments[0];

        $query = preg_replace_callback("/([\:\#]\w+)/",
            create_function('$matches',
                'global $arguments; global $i; 
                            if($matches[0][0] == "#"){
                                return $arguments[$i++]+0;
                            }else{
                                return ' . __CLASS__ . '::escape($arguments[$i++]);
                            }'),
            $query);

        return $query;
    }

    /**
     * Parses the query from a given hash
     * @param  $query
     * @param  $args
     * @return // string Parsed Query
     */
    public static function parseQueryHashedArgs($query, $args)
    {
        # If there's no connection, do a connection first.
        if (!self::$dlink) {
            self::connect();
        }

        global $gArgs;
        $gArgs = $args;
        $myFun = create_function('$matches',
            'global $gArgs; 
                             if(!array_key_exists($matches[2], $gArgs)){
                                 return $matches[0];
                             }
                             
                             if($matches[1] == "#"){
                                 return $gArgs[$matches[2]]+0;
                             }else{
                                 return ' . __CLASS__ . '::escape($gArgs[$matches[2]]);
                             }');
        $query = preg_replace_callback("/([\:\#])(\w+)/",
            $myFun,
            $query);
        return $query;
    }

    /**
     * Starts innoDB transaction
     * @return
     */
    public static function beginTransaction()
    {
        /*self::query("SET AUTOCOMMIT=1");        
        Console::log('BEGIN');
        self::query("BEGIN");*/
    }

    /**
     * Takes all changes back if something went wrong
     * @return
     */
    public static function rollbackTransaction()
    {
        /*Console::log('ROLLBACK');
        self::query("ROLLBACK");*/
    }

    /**
     * commit changes to the database and make them saved.
     * @return
     */
    public static function commitTransaction()
    {
        /*Console::log('COMMIT');
        self::query("COMMIT");*/
    }

    /**
     * This is an unsafe straight forward query function
     * @warning All queries passed to this function must be secured
     * @param  $query
     * @returnobject  mysql response resource
     */
    static function query($query)
    {

        # If there's no connection, do a connection first.
        if (!self::$dlink) {
            self::connect();
        }
        # Take query time
        self::profileStart("Query");

        # Never allow replace into in users and forms table because
        # Replace into first deletes the entry then inserts it back
        # this will cause foreign keys to cascade then delete everything related
        # to this entry such as when you replace into users table you will lose
        # all forms, submissions, ansers and all. So don't change this ever.
        if (Utils::startsWith(trim($query), "replace", false)) {
            if (preg_match("/replace\s*into\s*\`?(users|forms)\`?/i", $query, $m)) {
                throw new DBException(JotErrors::get('DB_REPLACE_NOT_ALLOWED', strtoupper($m[1]), $query));
            }
        }

        # For data security never allow updates without WHERE clause
        /*
        if(Utils::startsWith(trim($query), "update", false)){
            $parsed = Utils::parseSQL($query);
            if(!array_key_exists('WHERE', $parsed['UPDATE'])){
                throw new DBException(JotErrors::$DB_UPDATE_WITHOUT_WHERE);                
            }
        }
        */

        $result = @mysqli_query(self::$dlink, $query);
        self::$queryTime = self::profileEnd("Query");

        $info = "";
        if ($myinfo = mysqli_info(self::$dlink)) {
            $info = "\nInfo: " . $myinfo;
        }

        Console::info($query . "\n\nTook: " . self::$queryTime . ", Affected: " . mysqli_affected_rows(self::$dlink) . ", Rows" . $info . " Used Connection: " . self::$currentDB, "Query");

        # Log if this query took more in should
        if (self::$queryTime > self::slowQueryLimit) {
            Console::warn("Query Took " . self::$queryTime . "ms \n--\n\n " . $query, "Long Query");
        }
        # Log errors if happens
        if (mysqli_error(self::$dlink)) {
            Console::error(mysqli_error(self::$dlink) . "\n\t" . $query, "Error On Query");
            throw new DBException(JotErrors::get('DB_QUERY_ERROR', $query, mysqli_error(self::$dlink)));
            return false;
        }

        return $result;
    }

    /**
     * Use for the read queries to database, gets the query as a first parameter then the veraibles from the other parameters
     * <code> DB::read("SELECT * FROM users WHERE accountType=':type' AND id=#id", $type, $id) </code>
     * Arguments can either be a query string and a hash of (column_name => value) pairs
     * or a query and the values to be replaced one by one.
     * @return // object response an object consists of :
     *      - response: (success=>true if query is successfull),
     *      - rows: (mysql_affected_rows),
     *      - query: (parsed query),
     *      - time: (Execution time in seconds),
     *      - result: (Array of results to be used in your code)
     *      - first:  (first result as a single node)
     */
    static function read()
    {
        # If there's no connection, do a connection first.
        if (!self::$dlink) {
            self::connect();
        }

        $args_arr = func_get_args();

        if (count($args_arr) > 1) {
            if (gettype($args_arr[1]) == 'array') {
                # Parse the array
                $query = self::parseQueryHashedArgs($args_arr[0], $args_arr[1]);
            } else {
                $query = self::parseQuery($args_arr);
            }
        } else {
            $query = $args_arr[0];
        }

        if ($result = self::query($query)) {
            $data = array();
            while ($line = @mysqli_fetch_assoc($result)) {
                array_push($data, $line);
            }
            if (is_resource($result)) {
                mysqli_free_result($result);
            }
        } else {
            return (object)array("success" => false, "error" => mysqli_error(self::$dlink), "time" => self::$queryTime, "query" => $query);
        }

        return (object)array("success" => true, "rows" => mysqli_affected_rows(self::$dlink), "time" => self::$queryTime, "first" => @$data[0], "result" => $data, "query" => $query);
    }

    /**
     * Use for the write queries to database, gets the query as a first parameter then the veraibles from the other parameters
     * <code> DB::write("DELETE FROM users WHERE accountType=':type' AND id=#id", $type, $id) </code>
     * @return // object response an object consists of :
     *      - response: (success=>true if query is successfull),
     *      - rows: (mysql_affected_rows),
     *      - time: (Execution time in seconds),
     *      - query: (parsed query)
     */
    static function write()
    {
        # If there's no connection, do a connection first.
        if (!self::$dlink) {
            self::connect();
        }
        $args_arr = func_get_args();

        if (count($args_arr) > 1) {
            if (gettype($args_arr[1]) == 'array') {
                # Parse the array
                $query = self::parseQueryHashedArgs($args_arr[0], $args_arr[1]);
            } else {
                $query = self::parseQuery($args_arr);
            }
        } else {
            $query = $args_arr[0];
        }

        if ($result = self::query($query)) {
            $response = (object)array("success" => true, "rows" => mysqli_affected_rows(self::$dlink), "time" => self::$queryTime, "insert_id" => mysqli_insert_id(self::$dlink), "query" => $query);
        } else {
            $response = (object)array("success" => false, "error" => mysqli_error(self::$dlink), "time" => self::$queryTime, "query" => $query);
        }
        if (is_resource($result)) {
            mysqli_free_result($result);
        }
        return $response;
    }

    /**
     * Active Record insert
     * @return // string Query
     */
    static function insert($table_name, $data, $insert = false)
    {
        # If there's no connection, do a connection first.
        if (!self::$dlink) {
            self::connect();
        }
        foreach ($data as $key => $value) {
            # if false skip this value and don't add into database
            if ($value === false) {
                contiune;
            }

            $values[self::escape($key)] = self::escape($value);
        }

        $replace = "REPLACE";
        if ($insert) {
            $replace = 'INSERT';
        }

        $query = $replace . " INTO `" . self::escape($table_name) . "` (`" . join('`, `', array_keys($values)) . "`) VALUES ('" . join("', '", array_values($values)) . "') ";

        if ($result = self::query($query)) {
            $response = (object)array("success" => true, "rows" => mysqli_affected_rows(self::$dlink), "time" => self::$queryTime, "query" => $query);
        } else {
            $response = (object)array("success" => false, "error" => mysqli_error(self::$dlink), "time" => self::$queryTime, "query" => $query);
        }
        if (is_resource($result)) {
            mysqli_free_result($result);
        }
        return $response;
    }

    /**
     * Return the array of columns (with table name index) with details
     * @param  $table
     * @return // array list of table columns with column details
     */
    static function getTableColumns($table)
    {

        $response = self::read("SHOW FULL COLUMNS FROM `:table`", $table);
        if ($response->rows < 1) {
            return array();
        }
        $fields = array();
        foreach ($response->result as $line) {
            $fields[$line["Field"]] = $line;
        }
        return $fields;
    }

    /**
     * Return table names in the database
     * @return // array list of tables in database
     */
    static function getTables()
    {
        $response = self::read("SHOW TABLES");
        $tables = array();
        foreach ($response->result as $line) {
            array_push($tables, $line['Tables_in_' . self::$database]);
        }
        return $tables;
    }

    /**
     * Check if the table exists or not
     * @param  $table
     * @return // bool
     */
    static function tableExists($table)
    {
        $tables = self::getTables();
        return Utils::in_arrayi($table, $tables);
    }

    /**
     * Starts the timer for given title
     * @param  $title
     * @return // null
     */
    static function profileStart($title)
    {
        self::$timers[$title] = microtime(true);
    }

    /**
     * Brings back the result of time spending in seconds
     * Title must be exact same of the start functon
     * @param  $title
     * @return // float time in seconds
     */
    static function profileEnd($title)
    {
        $end = microtime(true);
        return (float)sprintf("%01.3f", ($end - self::$timers[$title]));
    }
}
