<?php
/*************************************************************************
 This script read inactive user xml load provided by VUIT and VUMC, check again Alma to update user records, update user group for students, add "expiry date" and "purge date" in Alma

    read inactive_users.xml file, grab user primary ID;
    foreach (primaryID) {
        if (primaryID in_array do_not_expire_users) 
            continue; 
        retrieve full user_json using user API get method
        //process user record based on user_group
        
        $flag_to_skip = 0; 
        switch (user_group) 
            case 'undergraduate':
            case 'graduate student': 
                expire_student_user {
                    user_group: alumni
                    status: active
                    expiry_date: today + 1 year
                    purge_date: today + 3 years
                    notes: "Gradudated student changed to alumni by LTDS via VU inactive feed on today"
                    notes: "Gradudated student changed to alumni, please update contact info and remove this note after contact updates"  - popup and internal note that can be deleted by circ staff  
                }
                break; 
            case 'alumni': 
                $flag_to_skip = 1
                break;  
            case 'faculty':
            case 'staff': 
            case 'staff-vumc': 
            default:  
                if isset(expiry_date) {
                    $flag_to_skip = 1; 
                } else {
                    expire_inactive_fac_staff {
                        expiry_date: today
                        purge_date: today + 2 years
                        status: active
                        notes: "Inactive faculty/staff expired by LTDS via VU inactive feed on today"
                    }
                }    
                break;     
        }                
        if ($flag_to_skip) { process log, cnt}
        else {
            post user_json back to user record using user API PUT
            process log, cnt; 
        }
    }

** To run the script in command line: 
   expire_inactive_users.php [server: production/sandbox] [infile: vu/vumc]
** To run the script in browser: 
   expire_inactive_users.php?server=[production/sandbox]&infile=[vu/vumc]
** make sure the infile is located in the same folder as the script

*************************************************************************/
set_time_limit(0); //avoid php timeout 

//output outcome as it is generated, only used for local testing
//ob_end_flush();
//ob_implicit_flush();

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 
$html_eol = "<br/>"; 

include("api_users_json.inc"); 

//decide which key to use for this script 
include("api_keys.inc"); 

//command line running
$server=$argv[1];  
//running from a browser
//if (isset($_GET['server']))  $server = $_GET['server']; //running from a browser
//else $server = "sandbox";
//echo "Server: ". $server. $html_eol;

$keytype = "user"; 
$apikey = $apikeys[$server][$keytype];

//Prepare log file
$logfile = "logs/inactiveusers_".date('Ymd').".log"; 
$flog = fopen($logfile, 'a'); 
$log = "Expire inactive user records in $server on ". date('Y-m-d'); 

//get do-not-expire-users array from file
$do_not_expire_file = "user_data/do-not-expire-list.csv";
$csvhead = 1; 
$do_not_expire_users = array(); 
$do_not_expire_fp = fopen( $do_not_expire_file, 'r');
while (($line = fgetcsv($do_not_expire_fp)) !== FALSE) {
  //$line is an array of the csv elements
    if ($csvhead) {$csvhead = 0; continue; }
    $do_not_expire_users[] = $line[0];
}
fclose($do_not_expire_fp); 
//print_r($do_not_expire_users); 

// Prepare email to system admin 
$esubject = "Expire Inactive Users Log -- ". $server. " -- ". date('Y-m-d'); 
$eto = "libils@vanderbilt.edu,jamen.mcgranahan@vanderbilt.edu";
$eheaders = "From: tao.you@vanderbilt.edu\r\n";
$eheaders  .= 'MIME-Version: 1.0' . "\r\n";
$eheaders .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$ebody = "<p><strong>". $log. "</strong></p>". $html_eol;
//echo $ebody; 

fwrite($flog, $log.PHP_EOL); 

//read primary_id file
//$input_fname = "user_data/inactive_primary_ids"; 
//$infile = fopen($input_fname, 'rt'); 
//if (!$infile) { echo "cannot open input file"; exit; } 

//command line running
$infile=$argv[2];  
//running from a broswer
//if (isset($_GET['infile']))  $infile = $_GET['infile'];  

//or read xml file and getting user primary_id
if (isset($infile)) {
    if ($infile == 'vu' ){
       $input_fname = "ils_student_inactive_export.xml";
	}
    elseif ($infile == 'vumc' ){
        $input_fname = "en_library_inactivate.medc.xml";
    }
}

//echo "Input Filename: ". $input_fname. $html_eol; 

$xmlfile = file_get_contents($input_fname); 
if (!$xmlfile) { 
    $log = "cannot open input file:".$input_fname; 
    fwrite($flog, $log.PHP_EOL);
    
    $ebody .= $log.$html_eol; 
    mail($eto,$esubject,$ebody,$eheaders);
    fclose($flog);

    exit; 
} 

// Convert xml string into an object 
$inactive_users = simplexml_load_string($xmlfile); 

$cnt_total = 0; $cnt_skipped = 0; $cnt_updated = 0; $cnt_created = 0; $cnt_errored = 0;  

foreach ($inactive_users as $u ) {
    //testing control 
    //if ($cnt_total < 25) {$cnt_total++; continue;}  
    //if ($cnt_total > 15 ) break;

    $primary_id = $u->primary_id;  

    //if user is in do_not_expire_list, do nothing
    if (in_array( $primary_id, $do_not_expire_users)) {
        $cnt_total ++; 
        $cnt_skipped ++;
        $log = $primary_id . " -- do not expire, skipped";

        //echo " --- do not expire, skipped" .$html_eol;
             
        fwrite($flog, $log.PHP_EOL);
        $ebody .= $log.$html_eol; 

        continue; 
    }

    $r_get = curl_get_user_details($primary_id, $apikey);
   
    if ( !isset( json_decode($r_get, FALSE)->errorsExist) ) { // user retrieved successfully 
       
        $user = json_decode($r_get, FALSE); //return the json string as an object 
 
        // process user records based on user_group value
        if ($user->user_group) { 
          $ugroup = $user->user_group;
        } 

        if ($user->status) {
           $ustatus = $user->status;
        } 

        $log = $primary_id. " -- ". $ugroup->value. " -- ". $ustatus->value;
        //echo $log; 

        $flag_to_skip = 0; 
        switch ($ugroup->value) {
            case "ALUMNI":   //already processed, skip 
                $flag_to_skip = 1; 
                break; 
            case "UNDERGRAD": 
            case "GRADUATE": 
                //turn to alumni, set up expiry date 
                $user = expire_student_user($user); 
                break; 
            case "FACULTY": 
            case "STAFF": 
            case "STAFF-VUMC": 
            default: 
                if ( isset($user->expiry_date) ) {
                    $flag_to_skip = 1;  
                }
                $user = expire_inactive_fac_staff($user); 
                break; 
        }        
 
        if ( $flag_to_skip ) { //already expired user record, no need to expire it again
            $cnt_total ++; 
            $cnt_skipped ++;
            $log .= " -- skipped";
            // echo " -- skipped" .$html_eol;
        }
        else {     
            // update user record using API PUT 
            $r_update = curl_update_user($primary_id, $user, $apikey); 
 
            if ( isset(json_decode($r_update, FALSE)->web_service_result->errorsExist) ) { 
                $cnt_errored ++; 
                $log .= " -- error";  
                //echo " -- error". $html_eol; 
            }
            else {
                $cnt_updated ++; 
                $log .= " -- Done ";
                //echo " -- Done" . $html_eol; 
            }
        }   
    }
    else { // user retrieve unsuccessful
        $cnt_errored ++;  
        $log =  $primary_id. " -- something wrong with the user record"; 
        //echo $log.$html_eol; 
    }

    fwrite($flog, $log.PHP_EOL);
    $ebody .= $log.$html_eol;  
    $cnt_total ++;         
}
    
$log = $cnt_updated . " records expired; "; 
$log .= $cnt_skipped . " records skipped; "; 
$log .= $cnt_errored . " records errored out;"; 
fwrite($flog, $log.PHP_EOL);
fclose($flog);

//echo $html_eol. $log. $html_eol; 
$ebody = $html_eol. $log. $html_eol .$ebody; 
mail($eto,$esubject,$ebody,$eheaders);


function expire_inactive_fac_staff( &$user) {
/****expire inactive faculty/staff records **************
* status_date: today
* expiry_date: today
* purge_date: today + 2 years
* keep status as "active"  - Jamen requested 
************************************************/  

    $status_date = date("Y-m-d")."Z"; 
    update_user_json($user, "status_date", $status_date); 

    $expiry_date = date('Y-m-d');
    $purge_date = date('Y-m-d', strtotime('+2 years'));

    update_user_json($user, "expiry_date", $expiry_date);
    update_user_json($user, "purge_date", $purge_date);

    $status = array("value" => "ACTIVE", "desc" => "active"); 
    update_user_json($user, "status", $status); 
   
    $notes = $user->user_note; 
    $inactive_user_notes =
         array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
               'note_text' => "Inactive faculty/staff expired by LTDS via VU inactive feed on " . date("Y-m-d"),  
               'user_viewable'=> false, 
               'segment_type' => "External",
               'popup_note' => false
         ); 
    array_push($notes, $inactive_user_notes); 
    update_user_json($user, "user_note", $notes);

    return $user;   
}

function expire_student_user( &$user) {
/****expire inactive student records **************
* status_date: today
* update user group to Alumni
* set expiry_date: today + 1 year
* set purge_date: today + 3 years
* make sure status: active
************************************************/  

    $status_date = date("Y-m-d")."Z"; 
    update_user_json($user, "status_date", $status_date); 

    $expiry_date = date('Y-m-d', strtotime('+1 year'));
    $purge_date = date('Y-m-d', strtotime('+3 years'));

    update_user_json($user, "expiry_date", $expiry_date);
    update_user_json($user, "purge_date", $purge_date);

    $user_group = array("value" => "ALUMNI", "desc" => "Alumni"); 
    update_user_json($user, "user_group", $user_group);
    
    $status = array("value" => "ACTIVE", "desc" => "Active"); 
    update_user_json($user, "status", $status); 
   
    $notes = $user->user_note; 
    $inactive_user_notes =
         array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
               'note_text' => "Gradudated student changed to alumni by LTDS via VU inactive feed on " . date("Y-m-d"),  
               'user_viewable'=> false, 
               'segment_type' => "External",
               'popup_note' => false
         ); 
    array_push($notes, $inactive_user_notes); 
    update_user_json($user, "user_note", $notes);

    $alumni_user_notes =
         array('note_type'=> array("value"=>"OTHER", "desc"=>"Other"), 
               'note_text' => "Gradudated student changed to alumni, please update contact info and remove this note after contact updates --" . date("Y-m-d"),  
               'user_viewable'=> false, 
               'segment_type' => "Internal",
               'popup_note' => true
         ); 
    array_push($notes, $alumni_user_notes); 
    update_user_json($user, "user_note", $notes);

    return $user;   
}

?>