<?php

include "../../lib/init.php";
Session::checkAdminPages();

$paging = 50;

# Set the from
if ( isset($_GET['from']) ){
    $from = $_GET['from'] * $paging;
}else{
    $from = 0;
}

# Get all users
$users = DB::read("SELECT `username`, `email` ".
                "FROM `users` ".
                "WHERE ".
                "`account_type` = 'PREMIUM' ".
                "OR `account_type` = 'OLDPREMIUM' ".
                "OR `account_type` = 'PROFESSIONAL'");

$ignoreArray = array(   "rossg","timcoxjr","YNN","SUPERIA70","plumtucker","sealkc","knuivert",
                        "contemps","cngc-revadmin","johnfunk","alnairc","visitglendale","glendaleweb",
                        "pmayberry","shelley","jaybudhram","dbark","StyleWeekly","saschav","formor","prcalves",
                        "VizionDesign","datheatre","the707","mhick","markever","John1957","kkochsy","guard12n",
                        "zaamush","lovministries","gel4948","Karen Edwards","Sanjeet007","dnicholas878","kellygubser",
                        "danielchapterone","mp3leak","goldcoastah","cpcarey1225","MHYC_CO_SPRINGS","darnold953 ","sbrunson","sehdtech",
                        "bryangarnier","Dagbladet","tribejj",'www.livingbeyond.us', 'wjbkwebteam', 'namechangeorguk', 'andycharrington',
                        'giamma72', 'savicomm', 'jodiferus', 'yourenglishsolution', 'jasnet34',
                        'bronteprize', 'mimasua', 'edrrxllc', 'VRadio', 'vsbrazil', 'Ben Sap', 'sebdu30', 'tahee22', 'etptv', 'domesa', 'simonpage');

$faultCount = 0;
$successCount = 0;
$results = array();

foreach ($users->result as $row){
    
    if ( in_array(trim($row['username']), $ignoreArray) ) {
        continue;
    }
    
    $result = new stdClass();
    $result->username = $row['username'];
    $result->email = $row['email'];
    
    # Contruct the JotFormSubscriptions from username
    $jotformSubscriptions = new JotFormSubscriptions();
    
    try{
        $jotformSubscriptions->setUser ($result->username);
        $jotformSubscriptions->calculateEOT();
        
        if ( $jotformSubscriptions->eotDate !== false && strtotime($jotformSubscriptions->eotDate) > strtotime("2010-04-14") ){
            $successCount++;
            continue;
        }else{
            $result->eot = $jotformSubscriptions->eotDate;
            $faultCount++;
        }
        
    }catch (Exception $exception){
        list ($result->eot, $_details) = Utils::generateErrorMessage ($exception);
        $result->eot = str_replace ("<hr>", "", $result->eot);
        $faultCount++;
        
    }
    
    array_push($results, $result);
    
    unset($jotformSubscriptions);
    unset($userLog);
}

function flog($message = ""){
    print($message."<hr/>");
    ob_flush();
    flush();
}
?>
Success: <?=$successCount?><br/>
Misses: <?=$faultCount?><br/>
<hr/>

<?php
foreach ($results as $result):
?>
    <?=$result->username?>,<?=$result->email?>,<?=$result->eot?><br/>
<?php 
endforeach;
?>





