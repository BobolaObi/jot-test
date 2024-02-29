<?php

include "../../lib/init.php";
Session::checkAdminPages();

# Exit because this script does not need to work.
die("Not need to work for now.");

# Get all users
$users = DB::read   (  
                    "SELECT * " .
                    "FROM `monthly_usage` " .
                    "WHERE `username` IN ( SELECT `username` FROM `users` WHERE `accountType` = 'FREE' ) " .
                    "AND `username` IN ( SELECT `username` FROM `monthly_usage` ) "
                    );

$freeAccountType = AccountType::find("FREE");

foreach ($users->result as $row){
    
    if ( !trim(''.$row['username']) ){
        continue;
    }

    $user = User::find($row['username']);
    
    if ( isset($user->accountType) && ( trim(''.$user->accountType) !== "FREE" ) ){
        continue;
    }
    
    $monthlyUsage = MonthlyUsage::find($user);
    $monthlyUsage->sendEmails(['submissions',
                'sslSubmissions', 
                'payments', 
                'uploads'], true, true, true);

    unset($user);
}
