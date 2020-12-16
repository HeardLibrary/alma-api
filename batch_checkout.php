<?php
/*************************************************************************
 This script read a list of users/items, batch checkout items to the users 

 read loanlist.csv file, grab user primary ID and item barcode
 foreach (primaryID, barcode) {
    curl_checkout; 
    if success, log userid -- barcode -- due date
    else log userid - barcode -- errormessage 
 }

Developer: Tao You @vanderbilt
Date: 12/2020 
*************************************************************************/
set_time_limit(0); //avoid php timeout 

//output outcome as it is generated
ob_end_flush();
ob_implicit_flush();

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 


//decide which key to use for this script 
include("api_keys.inc"); 
$server = "sandbox";
$keytype = "user"; 
$apikey = $apikeys[$server][$keytype];
echo "<p><strong> you are running the script on $server </strong></p>";

$input_fname = "user_data/LawFacultyLoanlist.csv"; 
$logfile = 'logs/batchcheckouts_'.date('Ymd'). ".log"; 
$flog = fopen($logfile, 'a'); 
$log = "Update User records from $input_fname at $server on ". date('Y-m-d'). PHP_EOL;  

//open csv to read
$infile = fopen($input_fname, 'rt'); 
if (!$infile) { echo "cannot open input file: $input_fname"; exit; } 

//get the headers of the file 
$headers = fgetcsv($infile);  //the csv file has header, not real user data

$cnt_total = 0; $cnt_updated = 0; $cnt_created = 0; $cnt_errored = 0;  

$loan_string = '{"circ_desk":{"value":"DEFAULT_CIRC_DESK"},"library":{"value":"LAW"}} '; 
$loan_json = json_decode($loan_string);   

while (($row = fgetcsv($infile)) !== FALSE) {
    //testing control 
    //if ($cnt_total < 15) {$cnt_total++; continue;}  
    //if ($cnt_total > 18) break;

    $primary_id = $row[5];
    $barcode = $row[1]; 

    $response = curl_checkout($primary_id, $barcode, $loan_json, $apikey);
    $rr = json_decode($response, FALSE); 
 
    if ( isset($rr->errorsExist) ) { // checkout unsuccessfully 

        //var_dump($rr); 
        $cnt_errored ++;  
        $errmsg = $rr->errorList->error->errorMessage; 

        echo $primary_id. " -- ". $barcode. " -- ". $errmsg. "<br/>"; 
        $log .=  $primary_id. " -- ". $barcode. " -- ". $errmsg. PHP_EOL; 
    }
    else { // checkout successful

        $cnt_updated ++; 
        $duedate = substr($rr->due_date, 0, 10); 
        echo $primary_id. " -- ". $barcode. " -- due on ". $duedate. "<br/>";
        $log .= $primary_id. " -- ". $barcode.  " -- due on ". $duedate. PHP_EOL; 
    }    
   
    $cnt_total ++; 
}          

echo "<p>$cnt_updated records updated; $cnt_errored records errored out; </p>"; 
$log .= $cnt_updated ." records updated; ". $cnt_errored ." records errored out;". PHP_EOL; 
fwrite($flog, $log);
fclose($flog); 
fclose($infile); 

function curl_checkout($user_id, $item_id, $loan_json, $apikey) {
// retrun XML string 
 
    $ch = curl_init();
    //turnning off SSL verification on localhost   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //remove those on production  

    $baseurl = 'https://api-eu.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}/loans?';
    $templateParamNames = array('{user_id}');
    $templateParamValues = array(urlencode($user_id) );
    $baseurl = str_replace($templateParamNames, $templateParamValues, $baseurl);
    $queryParams = array(
        'item_barcode' => urlencode($item_id),
        'apikey' => urlencode($apikey)
    );

    $url = $baseurl . http_build_query($queryParams);
    //echo $url, "<br/>";
    curl_setopt($ch, CURLOPT_URL, $url);

    $postArgs = json_encode($loan_json);

    // For xml, change the content-type.
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Accept: application/json"));
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));

    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postArgs);
    //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
    $response = curl_exec($ch);
    curl_close($ch);

    //var_dump($response);  // not sure why, but it was returned as xml string 
    $simpleXML = simplexml_load_string($response); 
    $json_response = json_encode($simpleXML); 
    
    return $json_response; 
}   

