<?php

$local =  [
	'type' 			=> 'mysql',
	'host' 			=> 'localhost',
	'port'			=> 3306,
	'database' 		=> 'jotform_new',
	'user' 			=> 'root',
	'password' 		=> ''
];

$production =  [
	'type' 			=> 'mysql',
	'host' 			=> '10.202.1.156',
	'port'			=> 3306,
	'database' 		=> 'jotform_new',
	'user' 			=> DB_USER,
	'password' 		=> DB_PASS
];

if(defined('DB_NAME') && defined('APP') && APP){
    $local = $production =  [
        'type'          => 'mysql',
        'host'          => DB_HOST,
        'port'          => 3306,
        'database'      => DB_NAME,
        'user'          => DB_USER,
        'password'      => DB_PASS
    ];
}

//----------------------------
// DATABASE CONFIGURATION
//----------------------------
$ruckusing_db_config = [
	'development' 	=> $local,
	'test' 		  	=> $local,
	'production'  	=> $production
];

?>
