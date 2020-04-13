<?php
/** This script retrive and update user record in Alma **/ 

/*************************************************************************
* read exception_staff csv file, grab user primary ID;
*    foreach (primaryID) {
*        retrieve full user_json using user API get
*        update user_json
*        post user_json back to user record using user API PUT  
*   }
*************************************************************************/

//output outcome as it is generated
ob_end_flush();
ob_implicit_flush();

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 

include("api_users_json.inc"); 

//decide which key to use for this script 
include("api_keys.inc"); 
$server = "sandbox";
$keytype = "user"; 
$apikey = $apikeys[$server][$keytype];
echo "<p><strong> you are running the script for $server </strong></p>";

$input_fname = "user_data/staff_exceptions_2020.csv"; 
/* staff_exceptions.CSV file format is: CardID,Name,EPID,VUnetID,user group,library, */ 
/* staff_exception_2020.CSV file format is: UserID,User Name,User Group */ 

//open csv to read
$infile = fopen($input_fname, 'rt'); 
if (!$infile) { echo "cannot open input file"; exit; } 

//get the headers of the file 
$headers = fgetcsv($infile);  //not processing csv header at this time


$i = 0; 
while (($row = fgetcsv($infile)) !== FALSE) {
    //testing control 
    //if ($i < 3) {$i++; continue;}  
    //if ($i > 3) break;

    $primary_id = $row[0]; 

    $user_json = curl_get_user_details($primary_id, $apikey); 
    $user = json_decode($user_json); 
    //var_dump($user->user_group);  

    $ugroup = $user->user_group; 
    echo $primary_id, " -- ", $ugroup->value;

    // if ($ugroup->nodeValue == "FACULTY") { echo "<br/>"; $i++; continue; }

    $new_ugroup = array("value" => "FACULTY", "desc" => "Faculty");
    $updated_user = udpate_user_json($user, "user_group", $new_ugroup);
    //var_dump($updated_user); 

    $rr = curl_update_user($primary_id, $updated_user, $apikey); 
    
    if ( isset(json_decode($rr)->errorsExist) )  echo " --- error <br/>"; 
    else echo "-- Done <br/>"; 
 
    $i ++; 
}    
echo "$i user records updated"; 
return; 



?>