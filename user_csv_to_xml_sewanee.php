<?php
/************************************
Loading Sewanee Students and faculty records to Alma 
.csv file: Name | Patron Type | Barcode | Address| Telephone| Email
Patron types are 1 - College students, 2 - Seminary Students  3 – College Faculty,  4 – Seminary Faculty, 5 – Seminary Faculty, 9 - Seminary Advanced Degree Students, and 10 - School of Letters Students.   
*************************************/

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 
include("api_users_xml.inc"); 

$input_fname = "sewanee2019.csv"; 
$output_fname = "sewanee2019.xml"; 

/* csv file format: Name, barcode, type, address, telephone, email */

$this_user_group = "SEWANEE"; 
$this_user_group_desc = "Sewanee Users";

$student_expiry_date = "2019-12-18Z"; 
$faculty_expiry_date = "2020-01-15Z"; 
$this_purge_date = "2021-12-30Z" ;
$this_load_date = date("Y-m-d")."Z"; 

$this_load_notes = array (
        array("content" => "Sewanee User Load on ".  date("Y-m-d"), 
              "viewable" => "false", 
              "popup" => "false" ) ,
        array("content" => "please reset user password and remove this note! ", 
              "viewable" => "false", 
              "popup" => "true" ) 
    ); 

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
	//control which records to load 
    if ($i < 2000) { $i++; continue;} 
    if ($i > 2003) break;

	$user = $doc->createElement('user');

    $record_type = createAnElement($doc, "record_type", "PUBLIC", "desc", "Public");
    $user->appendChild($record_type); 
    
    $primary_id = $doc->createElement("primary_id", $row[2]);  
    $user->appendChild($primary_id); 

    // process name colume to get fname, lname, mname in names array  
    $names = get_names($row[0]); 

    $first_name = $doc->createElement("first_name", $names['fname']); 
    $user->appendChild($first_name); 

    $middle_name = $doc->createElement("middle_name", $names['mname']); 
    $user->appendChild($middle_name); 

    $last_name = $doc->createElement("last_name", $names['lname']); 
    $user->appendChild($last_name); 

    $full_name = $doc->createElement("full_name", $names['fulname']); 
    $user->appendChild($full_name); 

//    $pin_num = $doc->createElement("pin_number"); 
//    $user->appendChild($pin_num); 

//    $user_title = createAnElement($doc, "user_title", "", "desc", ""); 
//    $user->appendChild($user_title); 

//    $job_category = createAnElement($doc, "job_category", "", "desc", ""); 
//    $user->appendChild($job_category); 

//    $job_description = $doc->createElement("job_description"); 
//    $user->appendChild($job_description);

//	$gender = createAnElement($doc, "gender", "", "desc", ""); 
//    $user->appendChild($gender); 

    $user_group = createAnElement($doc, "user_group", $this_user_group, "desc", $this_user_group_desc); 
    $user->appendChild($user_group); 

//    $campus_code = createAnElement($doc, "campus_code", "", "desc", ""); 
//    $user->appendChild($campus_code); 

//    $web_site_url = $doc->createElement("web_site_url"); 
//    $user->appendChild($web_site_url);
    
//    $cataloger_level = createAnElement($doc, "cataloger_level", "00", "desc", "[00] Default Level"); 
//    $user->appendChild($cataloger_level); 

    $preferred_language = createAnElement($doc, "preferred_language", "en", "desc", "English"); 
    $user->appendChild($preferred_language); 

    if (in_array( $row[1], array(3,4,5)) )  $this_expiry_date = $faculty_expiry_date;
    else $this_expiry_date = $student_expiry_date; 
    
    $expiry_date = createAnElement($doc, "expiry_date", $this_expiry_date, "", ""); 
    $user->appendChild($expiry_date); 

    $purge_date = createAnElement($doc, "purge_date", $this_purge_date, "", ""); 
    $user->appendChild($purge_date); 

    $account_type = createAnElement($doc, "account_type", "INTERNAL", "desc", "Internal"); 
    $user->appendChild($account_type); 

//    $external_id = $doc->createElement("external_id"); 
//    $user->appendChild($external_id);
    
//    $password = $doc->createElement("password"); 
//    $user->appendChild($password);

//    $force_password_change = $doc->createElement("force_password_change"); 
//    $user->appendChild($force_password_change);    

    $status = createAnElement($doc, "status", "ACTIVE", "desc", "Active"); 
    $user->appendChild($status);

    $status_date = $doc->createElement("status_date", $this_load_date); 
    $user->appendChild($status_date); 

    $emails = array(
                array( "type"=>"school", "desc"=>"school", "value"=>$row[5]) 
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

    //create userID tab info  
//    $this_load_ids = array( array("type"=>"BARCODE", "desc"=>"Barcode", "value"=>$row[2]) ); 
//    $user_identifiers = createIds($doc, $this_load_ids); 
//    $user->appendChild($user_identifiers);

    //create patron role
    $roles = createRoles($doc); 
    $user->appendChild($roles); 

//    $user_blocks = $doc->createElement("user_blocks"); 
//    $user->appendChild($user_blocks);

    //create user load notes 
    $user_notes = createUserNotes($doc, $this_load_notes); 
    $user->appendChild($user_notes); 

    
    //create user statistical categories
    $this_user_stats = array ("SEWANEE" => "University Of South");

    if ( in_array( $row[1], array(3,4,5)) ) {
        $this_user_stats['FAUCLTY'] = "Faculty"; 
    }
    else $this_user_stats['STUDENT'] = 'Student'; 

    $user_statistics = createUserStatistics($doc,$this_user_stats); 
    $user->appendChild($user_statistics);  

//    $proxy_for_users = $doc->createElement("proxy_for_users"); 
//    $user->appendChild($proxy_for_users);

    $users->appendChild($user); 
    echo $i, "/"; 
    $i ++ ; 
}

fclose($infile); 

$strxml = $doc->saveXML();
echo "<pre>", htmlentities($strxml), "</pre>"; 

$outfile = fopen($output_fname, "w");
fwrite($outfile, $strxml);
fclose($outfile);


/*****  SEWANEE Related Functions  *******/
function get_names( $name_col){
//process name colume, to get fname, mname, lname in names array    
    $outNames = array(); 
    $names = explode(",", $name_col, 2);
    //print_r($names);
    $outNames['lname'] = $names[0];
    $names2 = explode(" ", ltrim($names[1]), 2);
    //print_r($names2); 
    $outNames['fname'] = $names2[0];
    if (isset($names2[1])) $outNames['mname'] = $names2[1];
    else $outNames['mname'] = ""; 
    $outNames['fulname'] = $outNames['fname']. " ". $outNames['lname']; 
    // print_r($outNames);
    return $outNames; 
}


?>