<?php
/*************************************************************************
 This script read inactive user xml load provided by VUIT, check again Alma to update user records status as "inactive", update user "expiry date" and "purge date" in Alma

    read inactive_users.xml file, grab user primary ID;
    foreach (primaryID) {
        retrieve full user_json using user API get
        update user_json {
            status: inactive
            expiry_date: today
            purge_date: today + 2 years
        } 
        post user_json back to user record using user API PUT
    }

*************************************************************************/
set_time_limit(0); //avoid php timeout 

//output outcome as it is generated
ob_end_flush();
ob_implicit_flush();

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 

include("api_users_json.inc"); 

//decide which key to use for this script 
include("api_keys.inc"); 

$server=$argv[1];
//if (isset($_GET['server']))  $server = $_GET['server']; 
//else $server = "sandbox";

echo "Server: ".$server."\n";

$keytype = "user"; 

$apikey = $apikeys[$server][$keytype];
echo "<p><strong> you are running the script on $server </strong></p>";

$logfile = 'logs/inactiveusers_'.date('Ymd').".log"; 
$flog = fopen($logfile, 'a'); 
$log = "expire inactive user records in $server on ". date('Y-m-d'). PHP_EOL; 
fwrite($flog, $log); 

//read primary_id file
//$input_fname = "user_data/inactive_primary_ids"; 
//$infile = fopen($input_fname, 'rt'); 
//if (!$infile) { echo "cannot open input file"; exit; } 

$infile=$argv[2];

//or read xml file and getting user primary_id
if (isset($infile)) {
    if ($infile == 'vu' ){
        //$inpath = "user_data/vu_inactives/";
        $filename = "ils_student_inactive_export.xml";
        $input_fname = $filename;
    }
    elseif ($infile == 'vumc' ){
        //$inpath = "user_data/vumc_inactives/";
        $filename = "en_library_inactivate.medc.xml";
        $input_fname = $filename;
    }
}
//else $input_fname = "ils_student_inactive_export.xml"; 

echo "Input Filename: ". $input_fname."\n";

$xmlfile = file_get_contents($input_fname); 
if (!$xmlfile) { 
    echo "cannot open input file:", $input_fname."<br/>"; 
    $log = "cannot open input file:".$input_fname. PHP_EOL; 
    fwrite($flog, $log);
    exit; 
} 

// Convert xml string into an object 
$inactive_users = simplexml_load_string($xmlfile); 

$cnt_total = 0; $cnt_updated = 0; $cnt_created = 0; $cnt_errored = 0;  

foreach ($inactive_users as $u ) {
    //testing control 
    //if ($cnt_total < 0) {$cnt_total++; continue;}  
    //if ($cnt_total > 2) break;

    $primary_id = $u->primary_id;  

    $r_get = curl_get_user_details($primary_id, $apikey);

    if ( !isset( json_decode($r_get)->errorsExist) ) { // user retrieved successfully 
       
        $user = json_decode($r_get); 
        //var_dump($user);  

        $ugroup = $user->user_group; 
        echo $primary_id, " -- ", $ugroup->value;
        $log .= $primary_id. " -- ". $ugroup->value;

        $user = expire_inactive_user($user); 
        
        $r_update = curl_update_user($primary_id, $user, $apikey); 
        //var_dump($r_update); 

        if ( isset(json_decode($r_update)->web_service_result->errorsExist) ) { 
            $cnt_errored ++; 
            echo " --- error \n";
            $log .= " --- error". PHP_EOL;  
        } 
        else {
            $cnt_updated ++; 
            echo " -- Done \n";
            $log .= " -- Done ". PHP_EOL; 
        }
    }
    else { // user retrieve unsuccessful

        $cnt_errored ++;  
        echo $primary_id, " -- something wrong with the user record\n"; 
        $log .=  $primary_id. " -- something wrong with the user record". PHP_EOL; 
    }
$cnt_total ++;         
}
    
echo "$cnt_updated records expired; $cnt_errored records errored out;\n"; 
$log .= $cnt_updated ." records expired; ". $cnt_errored ." records errored out;". PHP_EOL; 
fwrite($flog, $log);
fclose($flog); 


function expire_inactive_user( &$user) {
/****expire inactive user records **************
* status_date: today
* expiry_date: today
* purge_date: today + 2 years
* status: INACTIVE
************************************************/  

    $status_date = date("Y-m-d")."Z"; 
    update_user_json($user, "status_date", $status_date); 

    $expiry_date = date('Y-m-d');
    $purge_date = date('Y-m-d', strtotime('+2 years'));

    update_user_json($user, "expiry_date", $expiry_date);
    update_user_json($user, "purge_date", $purge_date);

    $status = array("value" => "INACTIVE", "desc" => "Inactive"); 
    update_user_json($user, "status", $status); 

   
    $notes = $user->user_note; 
    $inactive_user_notes =
         array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
               'note_text' => "Inactive user expired by LTDS on " . date("Y-m-d"),  
               'user_viewable'=> false, 
               'popup_note' => false
         ); 
    array_push($notes, $inactive_user_notes); 
    update_user_json($user, "user_note", $notes);

    return $user;   
}

?>