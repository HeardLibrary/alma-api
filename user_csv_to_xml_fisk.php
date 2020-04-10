<?php
/** This script read csv file then generate user XML for Alma to load  **/

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 

include("api_users_xml.inc"); 

$input_fname = "fisk2020spring.csv"; 
$output_fname = "fisk2020spring.xml";

/* CSV file format is: term, ID, first name, last name, collegelevel, email */ 
/* CSV file format is: ID, first name, last name, middlename, email1, email2 */ 

$this_user_group = "FISK"; 
$this_user_group_desc = "Fisk Students and Faculty";

$this_expiry_date = "2020-04-24Z"; 
$this_purge_date = "2022-04-24Z"; 
$this_load_date = date("Y-m-d")."Z"; 

$this_load_notes = array (
        array("content" => "Fisk User Load on ". date("Y-m-d"), 
              "viewable" => "false", 
              "popup" => "false" ) ,
        array("content" => "Please reset user password and remove this note! ", 
              "viewable" => "false", 
              "popup" => "true" ) 
    ); 

$this_user_stats = array ("FISKORG" => "Fisk University", "STUDENT" => "Student");

//open csv to read
$infile = fopen($input_fname, 'rt'); 

//get the headers of the file 
$headers = fgetcsv($infile);  //not processing csv header at this time

//create a new dom document 
if ( !($doc = new DOMDocument("1.0", "utf-8"))) 
	echo "Something wrong!"; 
$doc->formatOutput = true; 

//add a root node to the document 
$users = $doc->createElement('users');
$doc->appendChild($users); 

// Loop through each row creating a <row> node with the correct data

$i = 0; 
while (($row = fgetcsv($infile)) !== FALSE)
{
	//control how many and what records to generate XML 
    if ($i < 0) {$i ++; continue; }
    if ($i > 4) break; 

	$user = $doc->createElement('user');

    $record_type = createAnElement($doc, "record_type", "PUBLIC", "desc", "Public");
    $user->appendChild($record_type); 
    
    $primary_id = $doc->createElement("primary_id", $row[0]);  
    $user->appendChild($primary_id); 

    $first_name = $doc->createElement("first_name", $row[1]); 
    $user->appendChild($first_name); 

    $middle_name = $doc->createElement("middle_name", $row[3]); 
    $user->appendChild($middle_name); 

    $last_name = $doc->createElement("last_name", $row[2]); 
    $user->appendChild($last_name); 

   // $full_name = $doc->createElement("full_name", $row[2]." ".$row[3]); 
   // $user->appendChild($full_name); 

//  $pin_num = $doc->createElement("pin_number"); 
//  $user->appendChild($pin_num); 

    $user_title = createAnElement($doc, "user_title", "", "desc", ""); 
    $user->appendChild($user_title); 

//  $job_category = createAnElement($doc, "job_category", "", "desc", ""); 
//  $user->appendChild($job_category); 

//  $job_description = $doc->createElement("job_description"); 
//  $user->appendChild($job_description);

//  $gender = createAnElement($doc, "gender", "", "desc", ""); 
//  $user->appendChild($gender); 

    $user_group = createAnElement($doc, "user_group", $this_user_group, "desc", $this_user_group_desc); 
    $user->appendChild($user_group); 

    $campus_code = createAnElement($doc, "campus_code", "", "desc", ""); 
    $user->appendChild($campus_code); 

//    $web_site_url = $doc->createElement("web_site_url"); 
//    $user->appendChild($web_site_url);
    
//    $cataloger_level = createAnElement($doc, "cataloger_level", "00", "desc", "[00] Default Level"); 
//    $user->appendChild($cataloger_level); 

    $preferred_language = createAnElement($doc, "preferred_language", "en", "desc", "English"); 
    $user->appendChild($preferred_language); 

    $expiry_date = createAnElement($doc, "expiry_date", $this_expiry_date, "", ""); 
    $user->appendChild($expiry_date); 

    $purge_date = createAnElement($doc, "purge_date", $this_purge_date, "", ""); 
    $user->appendChild($purge_date); 

    $account_type = createAnElement($doc, "account_type", "EXTERNAL", "desc", "EXTERNAL"); 
    $user->appendChild($account_type); 

    //$external_id = $doc->createElement("external_id"); 
    //$user->appendChild($external_id);
    
//    $password = $doc->createElement("password"); 
//    $user->appendChild($password);

//    $force_password_change = $doc->createElement("force_password_change"); 
//    $user->appendChild($force_password_change);    

    $status = createAnElement($doc, "status", "ACTIVE", "desc", "Active"); 
    $user->appendChild($status);

    $status_date = $doc->createElement("status_date", $this_load_date); 
    $user->appendChild($status_date); 

    $emails = array(
                array( "type"=>"school", "desc"=>"school", "value"=>$row[4]), 
                array( "type"=>"alternative", "desc"=>"alternative", "value"=>$row[5])
                );  
    $contacts = createContacts($doc, $emails); 
    $user->appendChild($contacts);

//    $pref_first_name = $doc->createElement("pref_first_name"); 
//    $user->appendChild($pref_first_name);
    
//    $pref_middle_name = $doc->createElement("pref_middle_name"); 
//    $user->appendChild($pref_middle_name);

//    $pref_last_name = $doc->createElement("pref_last_name"); 
//    $user->appendChild($pref_last_name);

//    $pref_name_suffix = $doc->createElement("pref_name_suffix"); 
//    $user->appendChild($pref_name_suffix);

    //start to load barcode 
    $barcode = substr($row[0], -6); 
    $this_load_ids = array( array("type"=>"BARCODE", "desc"=>"Barcode", "value"=>$barcode) ); 
    $user_identifiers = createIds($doc, $this_load_ids); 
    $user->appendChild($user_identifiers);

    $roles = createRoles($doc); 
    $user->appendChild($roles); 

    $user_blocks = $doc->createElement("user_blocks"); 
    $user->appendChild($user_blocks);

    $user_notes = createUserNotes($doc, $this_load_notes); 
    $user->appendChild($user_notes); 

    $user_statistics = createUserStatistics($doc, $this_user_stats); 
    $user->appendChild($user_statistics);  

//    $proxy_for_users = $doc->createElement("proxy_for_users"); 
//    $user->appendChild($proxy_for_users);

    $users->appendChild($user); 

    $i ++ ; 	
}

fclose($infile); 

$strxml = $doc->saveXML();
echo "<pre>", htmlentities($strxml), "</pre>"; 

$outfile = fopen($output_fname, "w");
fwrite($outfile, $strxml);
fclose($outfile);


?>
