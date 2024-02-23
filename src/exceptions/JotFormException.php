<?php

 
/**
 * A Simple Class which also prints it's message.
 * @package JotForm_Exceptions
 */

namespace Legacy\Jot\Exceptions;

use Legacy\Jot\Utils\Console;

class JotFormException extends Exception {
    public $errno;
    
    public function __construct($error=false){
        if(is_array($error)){
            $this->message = $error[0];
            $this->errno   = $error[1];
            $this->code    = $error[1];
        } else if (is_string($error)) {
            $this->message = $error;
        }
        
        if(APP && function_exists("get_called_class")){
            # Log all LDAP errors
            if(get_called_class() == 'LDAPException'){
                Console::error($error, "LDAP Exception");
            }
        }
    }
    
    public function __toString() {
        
        if($this->errno){
            return "Error: '".get_class($this)."'\n\nMessage: '".$this->message."'\nError No: ".$this->errno."\nFile: ".$this->getFile()."\nLine:".$this->getLine()."\n\nStack trace:\n".$this->getTraceAsString();            
        }
        
        return "exception '".__CLASS__ ."' with message '".$this->getMessage()."' in ".$this->getFile().":".$this->getLine()."\nStack trace:\n".$this->getTraceAsString();
  
    }
}