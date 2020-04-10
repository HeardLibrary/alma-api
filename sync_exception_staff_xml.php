<?php
/** This script read exception _staff csv file, generate user xml file, sync and update user record in Alma **/


error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 

include("api_user_xml.inc"); 


$input_fname = "staff_exceptions_2020.csv"; 
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
    if ($i > 8) break;

    $primary_id = $row[0]; 

    $user_xml = curl_get_user_details($primary_id, $apikey_sandbox); 
    // echo "<pre>",  htmlentities($user_xml), "</pre>";  

    $user = new DOMDocument("1.0", "utf-8");
    $user->loadXML($user_xml);

    $ugroups = $user->getElementsByTagName('user_group'); 
    $ugroup = $ugroups->item(0); 
    echo $primary_id, "--", $ugroup->nodeValue;

    if ($ugroup->nodeValue == "FACULTY") { echo "<br/>"; $i++; continue; }

    $new_ugroup = createAnElement($user, "user_group", "FACULTY", "desc", "Faculty"); 
    $updated_user_xml = udpate_user_group($user, "user_group", $new_ugroup); 
    // echo "<pre>",  htmlentities($updated_user_xml), "</pre>";

   $rr = curl_update_user($primary_id, $updated_user_xml, $apikey_sandbox); 

    echo "-- Done <br/>"; 
 
    $i ++; 
}    
echo "$i user records updated"; 

?>