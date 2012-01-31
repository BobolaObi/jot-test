<?

if(!function_exists("get_called_class")){
    function get_called_class() {
        $backtrace = debug_backtrace();
        $backtrace = $backtrace[sizeof($backtrace)-1];
        if ($backtrace["function"] = "eval" || $backtrace["type"] == "::") {
            // static method call, get the line from the file
            $file = file_get_contents($backtrace["file"]);
            $file = explode("\n", $file);
            for($line = $backtrace["line"] - 1; $line > 0; $line--) {
                preg_match("/(?P<class>\w+)::(.*)/", trim($file[$line]), $matches);
                if (isset($matches["class"]))
                    return $matches["class"];
            }
            throw new Exception("Could not find class in get_called_class()");
        }
    }
}

if(!function_exists('mb_convert_encoding')){
    function mb_convert_encoding($str){
        return $str;
    }
}
