<?php
/** This script generate user json file, sync and update user record in Alma **/ 

/*************************************************************************
* read postdoc.csv file, grab user primary ID;
*    foreach (primaryID) {
*        if loaded in Alma {
            retrieve full user_json using user API get
            update user_json
*           post user_json back to user record using user API PUT
         } else {
            generate user_json based on csv content
            create user record using usesr API POST 
         }

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
echo "<p><strong> you are running the script on $server </strong></p>";

$input_fname = "user_data/active_VUMC_postdocs.csv"; 
/* file format is: Full Name,Last Name,First Name,VUMC ID,School,Unit,Title,Is Active?,Appt Start Date,Appt End Date,E-mail Address (VU),E-mail Address (VUMC),Entity,Hire Date  */

//open csv to read
$infile = fopen($input_fname, 'rt'); 
if (!$infile) { echo "cannot open input file"; exit; } 

//get the headers of the file 
$headers = fgetcsv($infile);  //not processing csv header at this time

$t = 0; $i = 0; $j = 0; $k = 0;  
while (($row = fgetcsv($infile)) !== FALSE) {
    //testing control 
    if ($t < 3) {$t++; continue;}  

    if ($t >7) break;

    $primary_id = $row[3]; 

    $rr = curl_get_user_details($primary_id, $apikey);

    if ( !isset( json_decode($rr)->errorsExist) ) { // user retrieved successfully 
       
        echo $rr, "<br/>"; 
        $user = json_decode($rr); 
        //var_dump($user);  

        $ugroup = $user->user_group; 
        echo $primary_id, " -- ", $ugroup->value;

        $new_ugroup = array("value" => "FACULTY", "desc" => "Faculty");
        update_user_json($user, "user_group", $new_ugroup);
        //var_dump($updated_user->user_group); 

        $rr = curl_update_user($primary_id, $user, $apikey); 

        if ( isset(json_decode($rr)->errorsExist) ) {
            $k++; echo " --- error <br/>"; 
        } 
        else {
            $i++; echo "-- Done <br/>"; 
        }
    }
    else { // user retrieve unsuccessful

        if (json_decode($rr)->errorList->error[0]->errorCode == "401861") {
            echo $primary_id, " -- user not in the system, create one; ";
        
            $user_json = create_postdoc_json_from_csv($row); 
            $rr = curl_create_user($user_json, $apikey);
            
            if ( isset(json_decode($rr)->errorsExist) ) {
                $k++; echo " --- error <br/>"; 
            } else {
                $j++; echo "-- Done <br/>"; 
            }

        }
        else{
            $k ++;  
            echo $primary_id, " -- something wrong with the user record<br/>"; 
        }    
    }
    
    $t ++; 
}          

echo "<p>$i records updated; $j records created; $k records errored out; </p>"; 


function create_postdoc_json_from_csv($row){
//update user_json string using row data read from .csv file 

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

    $postdoc_addresses = array( array('line1'=>$row[6], 'line2'=>$row[5]) );
    $addresses = create_addresses_json($postdoc_addresses); 
    update_user_json($user, "contact_info->address", $addresses);

    $postdoc_emails = array( 
        array("email_value" => $row[10], "email_desc" => "vu email address", "email_types" => array("school"=>"School")), 
        array("email_value" => $row[11], "email_desc" => "VUMC email", "email_types"=> array("work"=>"Work"))
    ); 
    $emails = create_emails_json($postdoc_emails); 
    //print_r($emails);
    update_user_json($user, "contact_info->email", $emails);

    $postdoc_user_stats = array( 
        array("statistic_category" => array( "value"=>"POSTDOC", "desc"=>"Postdocs"),
              "segment_type" => "Internal" ) ); 
    update_user_json($user, "user_statistics", $postdoc_user_stats); 

    $postdoc_user_notes = array(
        array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
              'note_text' => "VUMC postdoc user loaded by LTDS", 
              'note_viewable'=> true, 
              'popup_note' => false, 
              'create_by'=> "LTDS", 
              'segment_type' => "Internal", 
              'create_date' => "" )
    ); 
    // foreach($postdoc_user_notes as $note) {
    //     $note['create_date'] = date('Y-m-d')."T11:00:00Z"; 
    // }
    update_user_json($user, "user_note", $postdoc_user_notes);  
    
    $user_json = json_encode($user);
    return $user_json; 
}

?>