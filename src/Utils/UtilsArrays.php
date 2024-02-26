<?php
/**
 * Array handling Utilities for JotForm
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;
use Legacy\Jot\Submission;

class UtilsArrays extends UtilsStrings{
    /**
     * Case-INsensitive in_array implementation.
     * @param object $needle
     * @param object $haystack
     * @return 
     */ 
    static function in_arrayi( $needle, $haystack ) {
        $found = false;
        foreach( $haystack as $index => $value ) {
            if( strtolower( $value ) == strtolower( $needle ) ) {
                $found = true;
            }
        }   
        return $found;
    }
    
    /**
     * Prints the element correctly
     * @param object $array
     * @param boolean $exit exits the probram after printing
     * @return 
     */
    static function print_r($array, $exit = false){
        # if string print regularly
        if(is_string($array)){
            echo $array."<br>\n";
        }else{
            # if array use print_r to print array and 
            # wrap it with pre tags for readibility
            echo "<pre>";
            print_r($array);
            echo "</pre>";
        }
        # quit programme if exit signal is sent
        if($exit){ exit(); }
        # return the value
        return $array;
    }
    /**
     * Strip slashes each element of an array
     * @param object $value array to be stripped
     * @return 
     */
    static function stripslashesDeep($value){
        $value = is_array($value) ?
                    array_map(array('Utils', 'stripslashesDeep'), $value) :
                    stripslashes($value);

        return $value;
    }
    
    /**
     * Convert array key to camel case literal
     * @param  $arr
     * @param  $capitalizeFirst [optional]
     * @param  $allowed [optional]
     * @return 
     */
    static function arrayKeysToCamel($arr, $capitalizeFirst = false, $allowed = 'A-Za-z0-9') {
        foreach($arr as $key => $value) {
            // Add camel case version.
            $camelCaseKey = Utils::stringToCamel($key, $capitalizeFirst, $allowed);
            if ($camelCaseKey != $key) {
                $arr[$camelCaseKey] = $value;
                // Remove the old one.
                unset($arr[$key]);
            }
        }
        return $arr;
    }
    /**
     * Converts all numbers in an array to string recursively
     * @param object $array
     * @return 
     */
    static function arrayNumbersToString($array){
        foreach($array as $key => $value){
            if(is_array($value)){
                $array[$key] = Utils::arrayNumbersToString($value);
            }
            if(is_numeric($value)){
                $array[$key] = (string) $value;
            }
        }
        return $array;
    }
    /**
     * Walks through an array of objects and bring the first occurance of the key value pair.
     * @param object $key
     * @param object $value
     * @param object $array
     * @return 
     */
    static function getArrayValue($key, $value, $array){
        if(!is_array($array)){ return false; }
        foreach($array as $valuesArray){
            if(isset($valuesArray[$key]) && $valuesArray[$key] == $value){
                return $valuesArray;
            }
        }
        return false;
    }
    /**
     * Convert an array to object recursively
     * @param object $array
     * @return 
     */
    static function toObject($array){

        foreach($array as $key => $value){
            if(is_array($value)){
                $value = Utils::toObject($value);
                $array[$key] = $value;
            }
        }
        return (object) $array;
    }
    
    /**
     * Convert an object to array recursively
     * @param object $array
     * @return 
     */
    static function toArray($object){
         if(!is_array($object) && !is_object($object)){
             return $object;
         }
         foreach($object as $key => $value){
             $value = Utils::toArray($value);
             if(is_array($object)){
                $object[$key] = $value;
             }else{
                $object->$key = $value;
             }  
         }   
         
         return (array) $object;
     }
     /**
     * Check all values of an array and returns false if any of the values are falsy (false, 0, "", null)
     * otherwise return true
     * @param object $array
     * @return 
     */
    static function arrayAny($array){
        foreach($array as $n){
            if(!empty($n)){ return true; }
        }
        return false;
    }
    
    /**
     * Check all values of an array and returns false if all of the values are not truty
     * otherwise return false
     * @param object $array
     * @return 
     */
    static function arrayAll($array){
        foreach($array as $n){
            if(empty($n)){ return false; }
        }
        return true;
    }
    
    /**
     * Checks if given array is associative or not
     * @param object $arr
     * @return 
     */
    static function isAssoc($arr){
        if(!is_array($arr)){return false; }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
    /**
     * Unset array key and return it's value
     * @param object $arr
     * @return 
     */
    static function u(&$arr, $key){
        $val = $arr[$key];
        unset($arr[$key]);
        return $val;
    }
    /**
     * Creates an array from print_r output
     * @param object $string
     * @return 
     */
    static function convertPrintrStringToArray ($string){
        $resultArray = array();
        $lines = preg_split ('/\n/', $string);
        
        foreach ($lines as $line){
            if (trim($line) ){
                list ($key, $value) = preg_split('/\=\>/', $line);
                $key = preg_replace_callback( '/\[(.*)\]/',  create_function(
                    '$matches',
                    'return $matches[1];'
                ), trim($key));
                $resultArray[trim($key)] = trim($value);
            }
        }
        return $resultArray;
    }
    /**
     * Serializes PHP Objects, then GZIP the output
     * @param object $value
     * @return string
     */
    public static function serialize($value){
        # serialize the given object
        $serialized = serialize($value);
        # deflate the string to save some space
        $deflated = gzdeflate($serialized, 9);
        return $deflated;
    }
    
    /**
     * Unserializes GZIPPED strings to PHP objects
     * @param object $str
     * @return Submission
     */
    public static function unserialize($str){
        # inflate the deflated string to get serialized object
        $inflated = @gzinflate($str);
        if($inflated === false){
            return unserialize($str);
        }
        # convert back to old value
        return unserialize($inflated);
    }
}