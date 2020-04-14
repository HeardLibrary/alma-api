<?php
/*************************************************************************
 This script read a list of users provided by Office of Postdoctroal Affair, check again Alma to update or create user records to reflect their postdoc status in Alma 

read postdoc.csv file, grab user primary ID;
    foreach (primaryID) {
        if already loaded in Alma {
            retrieve full user_json using user API get
            update user_json to posdoc record 
           post user_json back to user record using user API PUT
        } else {
            generate user_json based on csv content
            create user record using usesr API POST 
        }
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
$server = "production";
$keytype = "user"; 
$apikey = $apikeys[$server][$keytype];
echo "<p><strong> you are running the script on $server </strong></p>";

$input_fname = "user_data/active_VUMC_postdocs.csv"; 

//open csv to read
$infile = fopen($input_fname, 'rt'); 
if (!$infile) { echo "cannot open input file"; exit; } 

$flog = fopen('logs/postdoc.log', 'a'); 
$log = "process postdoc records on $server on ". date('Y-m-d'). PHP_EOL;  

//get the headers of the file 
$headers = fgetcsv($infile);  //not processing csv header at this time

$cnt_total = 0; $cnt_updated = 0; $cnt_created = 0; $cnt_errored = 0;  

while (($row = fgetcsv($infile)) !== FALSE) {
    //testing control 
    //if ($cnt_total < 0) {$cnt_total++; continue;}  
    //if ($cnt_total > 4) break;

    $primary_id = $row[3]; 

    $r_get = curl_get_user_details($primary_id, $apikey);

    if ( !isset( json_decode($r_get)->errorsExist) ) { // user retrieved successfully 
       
        //echo $rr, "<br/>"; 
        $user = json_decode($r_get); 
        //var_dump($user);  

        $ugroup = $user->user_group; 
        echo $primary_id, " -- ", $ugroup->value;
        $log .= $primary_id. " -- ". $ugroup->value;

        $user = update_postdoc_user($user); 
        
        $r_update = curl_update_user($primary_id, $user, $apikey); 

        if ( isset(json_decode($r_update)->web_service_result->errorsExist) ) { 
            $cnt_errored ++; 
            echo " --- error <br/>";
            $log .= " --- error". PHP_EOL;  
        } 
        else {
            $cnt_updated ++; 
            echo " -- Done <br/>";
            $log .= " -- Done ". PHP_EOL; 
        }
    }
    else { // user retrieve unsuccessful

        if (json_decode($r_get)->errorList->error[0]->errorCode == "401861") {
            echo $primary_id, " -- user not in the system, create one... ";
            $log .= $primary_id. " -- user not in the system, create one... ";
        
            $user_new = create_postdoc_json_from_csv($row);

            $r_create = curl_create_user($user_new, $apikey);
            //echo "<br/>r_create", $r_create; 
            
            if ( isset(json_decode($r_create)->web_service_result->errorsExist) ) {
                $cnt_errored ++; 
                echo " --- error <br/>"; 
                $log .= " --- error ".PHP_EOL; 
            } else {
                $cnt_created ++; 
                echo " -- Done <br/>"; 
                $log .= " --- Done ".PHP_EOL; 
            }

        }
        else{
            $cnt_errored ++;  
            echo $primary_id, " -- something wrong with the user record<br/>"; 
            $log .=  $primary_id. " -- something wrong with the user record"; 
        }    
    }
    
    $cnt_total ++; 
}          

echo "<p>$cnt_updated records updated; $cnt_created records created; $cnt_errored records errored out; </p>"; 
$log .= $cnt_updated ." records updated; ". $cnt_created . " records created; ". $cnt_errored ." records errored out;". PHP_EOL; 
fwrite($flog, $log);
fclose($flog); 
fclose($infile); 

function create_postdoc_json_from_csv($row){
/**** update user_json using row data read from .csv file 
file format is: Full Name,Last Name,First Name,VUMC ID,School,Unit,Title,Is Active?,Appt Start Date,Appt End Date,E-mail Address (VU),E-mail Address (VUMC),Entity,Hire Date  

* status_date: today
* first_name, last_name, full_name, primary_id (vumc_id)
* address: unit from .csv file
* emails: vu_email, vumc_email (first empty is preferred)
* user_group: FACULTY
* user_statistics: POSTDOC
* user_note: VUMC postdoc user loaded by LTDS
**********************************************************/

    $postdoc_string = file_get_contents("user_patron.json"); 
    $user = json_decode($postdoc_string); 

    $status_date = date("Y-m-d")."Z";
    update_user_json($user, "status_date", $status_date); 

    $primary_id = $row[3]; 
    $first_name = $row[2]; 
    $last_name = $row[1];
    $full_name = $row[0];
    update_user_json($user, "primary_id", $primary_id);
    update_user_json($user, "first_name", $first_name); 
    update_user_json($user, "last_name", $last_name);
    update_user_json($user, "full_name", $full_name); 


    $external_id = "vumc_patron_load"; 
    update_user_json($user, "external_id", $external_id);

    $postdoc_user_group = array("value" => "FACULTY", "desc" => "Faculty"); 
    update_user_json($user, "user_group", $postdoc_user_group);

    $postdoc_addresses = array( array('line1'=>$row[5], 'line2'=>$row[4]) );
    $addresses = create_addresses_json($postdoc_addresses); 
    update_user_json($user, "contact_info->address", $addresses);

    $postdoc_emails = array( 
        array("email_value" => $row[11], "email_desc" => "VUMC email address", "email_types" => array("school"=>"School")), 
        array("email_value" => $row[10], "email_desc" => "VU email", "email_types"=> array("alternative"=>"Alternative"))
    ); 
    $emails = create_emails_json($postdoc_emails); 
    //print_r($emails);
    update_user_json($user, "contact_info->email", $emails);

    $postdoc_user_stats = array( 
        array("statistic_category" => array( "value"=>"POSTDOC", "desc"=>"Postdocs"),
              "segment_type" => "Internal" ) ); 
    update_user_json($user, "user_statistic", $postdoc_user_stats); 

    $postdoc_user_notes = array(
        array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
              'note_text' => "VUMC postdoc user loaded by LTDS", 
              'user_viewable'=> true, 
              'popup_note' => false
          ) ); 
    update_user_json($user, "user_note", $postdoc_user_notes);  
    
    return $user; 
}


function update_postdoc_user( &$user) {
/****update postdoc user records **************
* status_date: today
* user_group: Faculty
* user_statistic: POSTDOC
* user_note: VUMC postdoc user updated by LTDS 
************************************************/  

    $status_date = date('Y-m-d'). "Z"; 
    update_user_json($user, "status_date", $status_date); 

    $new_ugroup = array("value" => "FACULTY", "desc" => "Faculty");
    update_user_json($user, "user_group", $new_ugroup);

    $postdoc_user_stats = array( 
        array("statistic_category" => array( "value"=>"POSTDOC", "desc"=>"Postdocs"),
              "segment_type" => "Internal" ) ); 
    update_user_json($user, "user_statistic", $postdoc_user_stats); 

    $postdoc_user_notes = array(
        array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
              'note_text' => "VUMC postdoc user updated by LTDS", 
              'user_viewable'=> true, 
              'popup_note' => false
        ) ); 
    update_user_json($user, "user_note", $postdoc_user_notes);

    return $user;   
}

?>