<? 
/**
 * Calculates and coverts times into given timezone
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class TimeZone {
    static $serverDTZ;
    
    /**
     * Sets the servers time zone
     * @param object $zone
     * @return 
     */
    static function setServerTimeZone($zone){
        self::$serverDTZ = new DateTimeZone($zone);
        date_default_timezone_set($zone);
    }
    
    static function getOffset(){
        
    }
    
    /**
     * Creates the list for dropdowns
     * @return 
     */
    static function createTimeZoneList() {
        $list = array();
        $zlist = DateTimeZone::listIdentifiers();
        foreach ($zlist as $zone) {
            if(!strstr($zone, "/")){
                continue; // Don't add values without a continent
            }
            
            list($cont, $city) = explode('/', $zone, 2);
            
            if($cont == 'Etc' || $cont == 'SystemV'){
                continue; // These are not continents
            }
            
            $city = str_replace("_", " ", $city);
            if(strstr($city, "/")){
                
                list($country, $ncity) = explode("/", $city);
                $city = $ncity.", ".$country;
                
            }else{
               $city = str_replace("/", ", ", $city);
            }
            
            
            $d = new DateTimeZone($zone);
            $gmt = new DateTimeZone('GMT');
            $s = new DateTime('now', $gmt);
            
            $off = $d->getOffset($s);
            
            $sign = "";
            if($off > 0){
                $sign = "+";
            }else if($off < 0){
                $sign = "-";
                $off = -$off;
            }            
            
            
            $hour = str_pad(floor(($off / 3600)), 2, 0, STR_PAD_LEFT);
            $min  = str_pad(($off % 3600) / 60, 2, 0, STR_PAD_LEFT);
            
            $str = "GMT";
            if($sign){
                $str .= $sign.$hour.":".$min;
            }
            
            if (!isset($list[$cont]) || !is_array($list[$cont])) {
                $list[$cont] = array();
            }
            
            $list[$cont][] = array($zone, $city." (".$str.")");
        }
        return $list;
    }
    
    /**
     * Creates the options for dropdown
     * @param object $selected [optional]
     * @return 
     */
    static function createDropdownOptions($selected = ""){
        $list = self::createTimeZoneList();
        
        $options = '<option disabled="disabled" value="UTC" selected="selected">Please Select</option>';
        foreach($list as $continent => $cities){
            
            $options .= '<optgroup label="'.$continent.'">'; 
            
            foreach($cities as $city){
                
                $s = ($selected == $city[0])? ' selected="selected"' : "";
                
                $options .= '<option value="'.$city[0].'"'.$s.'>'.$city[1].'</option>';
            }
            $options .= '</optgroup>';
        }
        return $options;
    }
    
    /**
     * Converts given time to given timezone
     * @param object $time
     * @param object $zone [optional]
     * @param object $format [optional]
     * @return 
     */
    static function convert($time, $userZone = 'EST', $format = false){
        
        if(empty($userZone) || $userZone == "00:00:00"){
            $userZone = 'EST';
        }
        
        $userDTZ = new DateTimeZone($userZone);
  
        $off1 = $userDTZ->getOffset(new DateTime('now'));
        $off2 = self::$serverDTZ->getOffset(new DateTime('now'));
        
        $off = $off1 - $off2;
        $epoc = strtotime($time. $off. " Seconds");
        
        if($format){
            return date($format, $epoc);
        }
        return $epoc;
    }
}
