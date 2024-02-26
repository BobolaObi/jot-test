<?php



namespace Quarantine;
error_reporting(E_ALL);

class TestUniqueID {
    
    /**
     * Will generate a form ID
     * @return 
     */
    public static function generate() {
    	srand(self::makeSeed());
        $z = sprintf("%03d", date('z'));
        $y = substr(date('y'), 1);
        if($y == 0){ $y = 1; }
        
        $baseid = ($y. $z . date('is')) + rand(1, 999);
        
        $sum = self::getSum($baseid);
        
        return $baseid.$sum;
        
    }
    
    /**
     * Gets the sum of the ID for validation
     * @param  $base  // First digits of the ID except last 2 digit 
     * @return sum of the ID 
     */
    public static function getSum($base){
        $sum = 7; # Dont you ever change this number or you'll break the space time continium
        foreach(str_split($base) as $num){ $sum+=$num; }
        $sum = str_pad($sum, 2,'0', STR_PAD_LEFT); # make sure the sum is always 2 digit number
        return $sum;
    }
    
    /**
     * Splits the id to base and sum
     * @param  $id
     * @return (array) base, sum
     */
    public static function splitID($id){
        $sum = substr($id, -2, 2);
        $base = preg_replace("/..$/", "", $id);
        
        return ["base"=>$base, "sum"=>$sum];
    }
    
    /**
     * Validates the number if it's a JotForm ID or not
     * @param  $id
     * @return 
     */
    public static function validateID($id){
        $sid = self::splitID($id);
        
        $sum = self::getSum($sid["base"]);
        
        return $sum == $sid["sum"];
    }
    
    /**
     * Encodes given ID
     * @param  $id
     * @return 
     */
    public static function encodeID($id){
        $split = 6;
        
        $val = substr($id, 0, $split);
        $val2 = substr($id, $split, strlen($id)-1);
        return base_convert($val, 10, 36).base_convert($val2, 10, 36);
    }
    /**
     * Decodes the encoded IDs
     * @param  $encoded
     * @return 
     */
    public static function decodeID($encoded){
        $split = 4;
        
        $first = substr($encoded, 0, $split);
        $last = substr($encoded, $split, strlen($encoded)-1);        
        return intval($first, 36).intval($last, 36);
    }
    
    /**
     * Will generate a submission ID
     * @return 
     */
    public static function generateSubmissionID(){
        return (time()-1134190800) . substr(preg_replace("/\D/", "", $_SERVER['REMOTE_ADDR']), 0, 9);
    }
    
    /**
     * Make seed for random function
     * @return 
     */
    public static function makeSeed(){
       list($usec, $sec) = explode(' ', microtime());
       return (float) $sec + ((float) $usec * 100000);
    }
}

echo "ss";

// Old form id generation:
function oldv2_random_id() {
    srand(oldv2_make_seed());
    $z = sprintf("%03d", date('z'));
    $y = substr(date('y'), 1);
    if($y == 0){ $y = 1; }
    return $y . $z . date('is') . substr(preg_replace("/\D/", "", "192.168.1.66", -5, 3) + rand(1, 999));
}

function oldv2_make_seed() {
   list($usec, $sec) = explode(' ', microtime());
	return (float) $sec + ((float) $usec * 100000);
}
// END OLD GENERATION

// */
$numberOfIterations = 10;

$passHash = [];
for ($i = 0; $i < $numberOfIterations; $i++) {
	//$pass = ID::generate();
	$pass = oldv2_random_id();
	if (isset($passHash[$pass])) {
		$passHash[$pass]++;
	} else {
		$passHash[$pass] = 1;
	}
}
// echo "Finished password creation.\n\n";
$noCollision = true;
$numberOfCollisions = 0;
foreach($passHash as $pass => $count) {
	if ($count > 1) {
		$noCollision = false;
		echo "Password '$pass' had '$count' number of hits.\n";
		$numberOfCollisions++;
	}
}



if( $noCollision ) {
	echo "\nThere were NO collisions in $numberOfCollisions.\n\n";
} else {
	echo "\nThere were $numberOfCollisions collisions in $numberOfCollisions.\n\n";
}
?>
