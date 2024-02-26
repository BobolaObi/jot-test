<?php
/**
 * Static class for logging. It also has a method for e-mailing unhandled exceptions.
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

class Console {

    # Default log level ERROR by default.
    public static $logLevel = E_ERROR;
    public static $logFile = 'jotform.all';
    public static $logFolder = '/tmp/';
    public static $emailAddresses = array();
    public static $backtrace = false;
    public static $useColors = true;
    public static $fh;
    public static $defaultTitle = "Log Entry";
    public static $oneLine = false;
    public static $logFiles = array(
        "all"       => "jotform.all", 
        "log"       => "jotform.log",
        "error"     => "jotform.error",
        "warn"      => "jotform.warn",
        "info"      => "jotform.info",
        "long"      => "jotform.long",
        "admin"     => "jotform.admin",
        "redis"     => "jotform.redis",
        "feedback"  => "jotform.feedback",
        "overlimit" => "jotform.overlimit",
        "temp"      => "jotform.temp"
    ); 
    
    # Error Type hash for printing errors.
    private static $errorType = array (
        E_ERROR              => 'ERROR',    // Integer 1
        E_WARNING            => 'WARNING',  // 2
        E_PARSE              => 'PARSE',    // 4
        E_NOTICE             => 'NOTICE',   // 8 etc.
        E_CORE_ERROR         => 'CORE ERROR',
        E_CORE_WARNING       => 'CORE WARNING',
        E_COMPILE_ERROR      => 'COMPILE ERROR',
        E_COMPILE_WARNING    => 'COMPILE WARNING',
        E_USER_ERROR         => 'USER ERROR',
        E_USER_WARNING       => 'USER WARNING',
        E_USER_NOTICE        => 'USER NOTICE',
        E_STRICT             => 'STRICT',   // 2048
        E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR', // 4096
        // With PHP 5.3
        // E_DEPRECATED        => 'DEPRECATED',      // 8192
        // E_USER_DEPRECATED   => 'USER DEPRECATED', // 16384
        E_ALL                => 'ALL'
    );
    
    /**
     * Opens a console file and keeps it open
     * @return 
     */
    static function openConsole() {
        //self::$fh = @fopen(self::$logFile, 'a');
    }
    /**
     * Closes the console
     * @return 
     */
    static function closeConsole() {
        /*@fclose(self::$fh);
        self::$fh = false;*/
    }
    
    /**
     * Important parameters are E_ERROR, E_WARNING, E_NOTICE and E_ALL.
     * You can also nest levels like in PHP, 
     * ie. Use E_ERROR || E_WARNING to log both error and warning messages.
     * or E_ALL ^ E_INFO to log all messages except for info level messages
     * (also except for E_STRICT as defined by PHP core).
     * @param  $level  // // error level
     * @return null 
     */
    public static function setLogLevel($level) {
        self::$logLevel = $level;
    }
    
    /**
     * Sets the log file path
     * @param  $filename  // // path of the log file
     * @return null
     */
    public static function setLogFolder($folder) {
        if(!file_exists($folder)){
            if(!@mkdir($folder, 0777)){
                echo "Cannot create log folder";
            }
        }
        self::$logFolder = $folder;
    }
    
    /**
     * Sets the email addresses for error reporting
     * @param array $emails array of email addresses
     * @return 
     */
    public static function setEmailAddresses($emails) {
        self::$emailAddresses  = $emails;
    }
    
    /**
     * tries to set log folder writable
     */
    public static function makeWritable($file){
        $pos = strrpos($file, "/");
        $folder = substr($file, 0, $pos+1);
        if(!is_writable($folder)){
            @chmod($folder, 0777);
        }
    } 
    
    /**
     * Sets $backtrace.
     * @param  $backtrace
     * @see Console::$backtrace
     */
    public static function setBacktrace($backtrace) {
        self::$backtrace = $backtrace;
    }
    
    /**
     * Forces console to print ourput into oneline
     * for cleaner log outputs 
     * @param  $oneLine
     * @return 
     */
    public static function setOneLine($oneLine){
        self::$oneLine = $oneLine;
    }
    
    /**
     * Make a decent log entry. Used by self::error, self::warning and self:info.
     * @param  $text  // // Log entry
     * @param  $title  // // [optional] title of the log entry
     * @param  $messageLevel  // // [optional] message's log level. Default E_ALL.
     * @return mixed returns the $text itself
     */
    public static function log($obj, $title = false, $messageLevel = E_USER_NOTICE, $type="log"){
        // If the defined log level is lower than the message level, don't log, return.
        if(!(self::$logLevel & $messageLevel)) {
            return;
        }
        
        $grey    = "\033[1;30m";
        $red     = "\033[1;31m";
        $green   = "\033[1;32m";
        $yellow  = "\033[1;33m";
        $blue    = "\033[1;34m";
        $magenda = "\033[1;35m";
        $cyan    = "\033[1;36m";
        $white   = "\033[1;37m";
        
        $textColor   = $white;
        $headerColor = $blue;
        $finishColor = $white;
        $titleColor  = $green;
        
        if($messageLevel == E_ERROR){
            $textColor  = $red;
            $titleColor = $red;
        }
        
        if($messageLevel == E_NOTICE){
            $textColor  = $magenda;
            $titleColor = $cyan;
        }
        
        if($messageLevel == E_USER_WARNING){
            $textColor  = $grey; 
            $titleColor = $green;
        }
        
        if(!self::$useColors){
            $textColor   = "";
            $headerColor = "";
            $finishColor = "";
            $titleColor  = "";
        }
        
        // If the first argument is an exception, set title etc.
        if ($obj instanceof \Exception) {
            $title = get_class($obj);
            $text = "\nUncaught Exception " . $obj->getMessage() . " at \n";
            $text .= $obj->getFile() . " on line " . $obj->getLine();
            $text .= "\n\nStack Trace:\n";
            $text .= $obj->getTraceAsString();
        } else if(!is_string($obj)) { # if text is not a string, then convert it to text.
            $text = print_r($obj, true);
        } else { // it is a string, print it directly.
            $text = $obj;
        }
        
        if(self::$backtrace && $messageLevel == E_ERROR){
            // $backtrace = print_r(debug_backtrace());
            
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_contents();
            $backtrace = str_replace(ROOT, "", $backtrace);
            
            ob_clean();
            
            $text .= "\n-----\nBack Trace:\n\n".$backtrace;
            
        }
        
        if( self::$oneLine == true ){
            $text = trim(''.preg_replace("/[\n\r\s]+/"," ",$text));
            $toLogFile = $grey."[".($title? $title : self::$defaultTitle)." - ".@date('F d, Y \a\t H:i:s')."]: ".$finishColor.$text."\n"; 
        }else{
            $line = str_repeat('#', 80);
            
            $title = "#Type[ " . $titleColor. self::$errorType[$messageLevel] . $headerColor . " ] #Title[ ". $titleColor . ($title? $title : self::$defaultTitle). $headerColor . " ] #Date[ ". $titleColor . @date('F d, Y \a\t H:i:s') . $headerColor . " ]\n" . $line. ":\n\n";
            
            $toLogFile = $headerColor . $line."\n".$title . $textColor. $text . "\n\n" . $headerColor . str_repeat('_', 80)."\n" . $finishColor;
        }
        
        self::writeToLog(self::$logFile, $toLogFile);
        self::writeToLog(self::$logFiles[$type], $toLogFile);
        
        return $text;
    }
    
    /**
     * Writes the content given file
     * Check the file existance and file size before writing
     * @param  $logFile
     * @param  $content
     * @return 
     */
    static function writeToLog($logFile, $content){
        // if log file is bigger than given MB then remove the contents. In order to prevent any hardisk failure.
        // @TODO: Do this using logrotate. Don't check filesize everytime a log message 
        // is written.
        
        $file = self::$logFolder.$logFile;
        
        if(file_exists($file) && filesize($file) >= 100*MB){
           file_put_contents($file, "");
        }
        
        self::makeWritable($file); // Try to make it writable
        
        if (self::$fh) {
            @fwrite(self::$fh, $content);
        } else {
            if($fh = @fopen($file, 'a')){
                @fwrite($fh, $content);
                @fclose($fh);
            }else{
                throw new \Exception("cannot open log file:".($file)."\n");
            }
        }
    }
    
    
    /**
     * Convenience method for logging messages with E_ERROR level.
     * @param  $text
     * @param  $title  // [optional]
     * @return mixed $text itself 
     */
    static function error($text, $title = false) {
        return self::log($text, $title, E_ERROR, "error");
    }
    
    /**
     * Convenience method for logging messages with E_NOTICE level.
     * @param  $text
     * @param  $title  // [optional]
     * @return  mixed $text itself
     */
    static function info($text, $title = false) {
        return self::log($text, $title, E_NOTICE, "info");
    }
    
    /**
     * Convenience method for logging messages with E_WARNING level.
     * @param  $text
     * @param  $title  // [optional]
     * @return  mixed $text itself
     */
    static function warn($text, $title = false) {
        return self::log($text, $title, E_WARNING, "warn");
    }
    
    /**
     * Log to keep very long queries
     * @param  $text
     * @param  $title  // [optional]
     * @return 
     */
    static function long($text, $title = false){
        return self::log($text, $title, E_USER_WARNING, "long");
    }
    
    /**
     * 
     * @param $text
     * @param $title
     * @return 
     */
    static function customLog($fileName = "log", $text = "", $title = false){
        return self::log($text, $title, E_ALL, $fileName);
    }
    
    /**
     * Catches the uncought exceptions
     * @param  $e  // \Exception
     * @return null
     */
    static public function exceptionHandler($e) {
        // Log exception.
        $message = self::error($e);
        // Also send an e-mail only if there's an e-mail address defined.
        if (!empty(self::$emailAddresses)) {
            Utils::sendEmail(array('from' => array("exception@jotform.com", "JotForm Support"), 
                    'to' => self::$emailAddresses, 'subject' => "JotForm App Exception", 'body' => $message));
        }
        Utils::redirect(HTTP_URL . "page.php?p=error");
    }
    /**
     * Parses the log file and converts into an array
     * Groups logs by their types
     * @return array log entries
     */
    static public function parseLog($file){
        /**
         * Catches the whole log block by a given type
         * ex1: \#{80}\s\#Type\[\sERROR\s\].*\s\#{80}\:(\s.*[^\#])+\_{80}
         * ex2: \#{80}\s\#Type\[\sWARNING\s\].*\s\#{80}\:(\s.*[^\#])+\_{80}
         * ex3: \#{80}\s\#Type\[\sALL\s\].*\s\#{80}\:(\s.*[^\#])+\_{80}
         */
        
        
        $log = join("", file($file)); # get log file
        # Match all log blocks
        preg_match_all("/^\#{80}\s\#(.*)\s\#{80}\:\s((.|[\r\n])*?)\s\_{80}/m", $log, $matches);
        
        foreach($matches[1] as $index => $line){
            # Match title block
            preg_match("/Type\[\s(?P<type>.*)\]\s\#Title\[\s(?P<title>.*)\s]\s\#Date\[\s(?P<date>.*)\s\]/", $line, $m);
            $parsedLog[trim(''.$m['type'])][$index] = array(
                "title"=> trim(''.$m["title"]),
                "date" => trim(''.$m["date"]),
                "message"=> $matches[2][$index]
            );
        }
        
        # Normalize arrays
        foreach($parsedLog as $key => $value){ $parsedLog[$key] = array_values($value); }
        
        return $parsedLog;
    }
    
    /**
     * Remove the color codes from log entries
     * @param  $str
     * @return 
     */
    static function clearColors($str){
        $str = preg_replace("/\033\[\d+;\d+m/", "", $str);
        $str = preg_replace("/\\\\033\[\d+;\d+m/", "", $str);
        return $str;
    }
    
    static function logAdminOperation($text){
        Console::$useColors = false;
        Console::customLog("admin", $text);
        Console::$useColors = true;
    }
    
    static function readLinesFromLog($file, $lines){
        $content = shell_exec("tail -n $lines $file");
        return $content;
    }
    
    /**
     * Will read given count of lines from log and print it on the screen
     * @param  $file  // [optional]
     * @return 
     */
    static function readConsole($lines = 1000, $file = "all"){
        
        $file    = self::$logFolder.self::$logFiles[$file];
        $logCont = self::readLinesFromLog($file, $lines);
        $logCont = htmlentities($logCont);
        $logCont = preg_replace("/\033\[\d+;(\d+)m/", '</span><span class="color-$1">', $logCont);
        $logCont = preg_replace("/\\\\033\[\d+;(\d+)m/", '</span><span class="color-$1">', $logCont);
        $logCont = preg_replace("/\#{80}/", str_repeat("#", "60"), $logCont);
        $logCont = preg_replace("/\_{80}/", str_repeat("_", "60"), $logCont);
        $output  = "<style>\n";
        $output .= ".color-30{ color:grey;    }\n";
        $output .= ".color-31{ color:red;     }\n";
        $output .= ".color-32{ color:green;   }\n";
        $output .= ".color-33{ color:yellow;  }\n";
        $output .= ".color-34{ color:blue;    }\n";
        $output .= ".color-35{ color:magenta; }\n";
        $output .= ".color-36{ color:cyan;    }\n";
        $output .= ".color-37{ color:white;   }\n";
        $output .= ".log{ background:#111; padding:10px; font-size:10px; white-space:pre-wrap; color:white; font-weight:bold; font-family:Verdana; }\n";
        $output .= ".log-info{ font-family:Verdana; font-size:10px; }";
        $output .= "</style>\n";
        $output .= "<span class='log-info'> <b>Last $lines lines of:</b> <span>$file</span></span><br><br>";
        $output .= '<div class="log">'."\n\n<span style='display:none;'>";
        $output .= $logCont;
        $output .= '</span></div>'."\n";
        
        return $output;
    }
}
