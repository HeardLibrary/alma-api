<?php
/*****************************************************************
 this script will extend a list of users' loan due dates
 ** remember to change apikey as needed
 ** new due date is hard coded in api function at this time -- to do in the future 

******************************************************************/

set_time_limit(0);
//include("api_loans.inc"); 

//decide which key to use for this script 
include("api_keys.inc"); 
$server = "production";
$keytype = "user"; 
$apikey = $apikeys[$server][$keytype];
echo "<p><strong> you are running the script on $server </strong></p>";

//output outcome as it is generated
ob_end_flush();
ob_implicit_flush();
$eol = "<br/>"; 

/*******************************************
  renew loans by user IDs  
********************************************/
// //$users = array('user1', 'user2');
// foreach ($users as $uid) {
// 	echo "<br/>", $uid, "<br/>"; 
// 	$loansdata = curl_get_loans_from_user( $uid, $apikey); 
// 	$loans = new simpleXMLElement($loansdata);
// 	foreach ($loans->item_loan as $l){
// 		$lid = $l->loan_id;
// 		$lduedate = $l->due_date; 
		
// 		if ( strpos($lduedate, "2020-05-04")  === false) {

// 			echo "<br/>", $lid, "---", $lduedate;

// 			$rr = curl_update_loans_due_dates($uid, $lid, $apikey); 

// 			$errors = new SimpleXMLElement($rr);
// 			$xmlerrors = $errors->errorsExist;
// 			if  ($xmlerrors) {
// 				$message = $errors->errorList->error->errorMessage[0];  
// 				echo " --- error: $message </p> $eol";
// 			} 
// 			else echo " --- done </p> $eol"; 
// 		}	
// 	}
// 	echo "<br/>Done<br/>"; 
// } 

/*************************************************************************
 renew loans by reading a list of user_id and loan_id from Analytics report 
 *************************************************************************/ 
$rowCount = 0;

//read analytic report 
if (($handle = fopen("user_data/loans_catchup_to_sept.csv", "r")) !== FALSE) {
/**	Primary Identifier,Item Loan Id,Due Date,Library Name,Process Status,Barcode,User Group,Expiry Date,Loan Date  **/
	echo "<p>Analytics report opened successfully. </p>$eol"; 
	$fpsuccess = fopen('logs/loans_to_sept_done.log', 'a'); 
	$fperror = fopen('logs/loans_to_sept_errored.log', 'a'); 

    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
 		
 		//control which row to process   
    	//if ($rowCount < 22000) { $rowCount ++; continue; }
    	//if ($rowCount > 22000) {break; }
        $fieldCount = count($row);
        //echo "<p> $fieldCount fields in line $rowCount: <br /></p>\n";
        //print_r($row); 
        $pid = $row[0]; 
        $loanid = $row[2]; 
        $barcode = $row[6];

        // renew loans 
        echo "<p>$rowCount --- $pid --- $loanid --- $barcode --- to be updated ";   
        $log = " $rowCount | $pid | $loanid | $barcode| ";

        $rr = curl_update_loans_due_dates($pid, $loanid, $apikey, "2020-09-04"); 
        $errors = new SimpleXMLElement($rr);
		//print_r($errors); 
		$xmlerrors = $errors->errorsExist;
		if  ($xmlerrors) {
			$message = $errors->errorList->error->errorMessage[0];  
			echo " --- error: $message </p>";
			$log .= " error: $message". PHP_EOL;
			fwrite($fperror, $log);
		} 
		else {
			echo " ---  done </p>";
			$log .= " done". PHP_EOL;
			fwrite($fpsuccess, $log);
		 }	 

        $rowCount ++;
	}
	fclose($handle);
	fclose($fperror);
	fclose($fpsuccess); 
} 
else echo "Failed to open analytics report! $eol"; 
echo "done"; 


//API functions 
function curl_get_loans_from_user($user_id, $apikey)
{
	
	$ch = curl_init();
	//turnning off SSL verification on localhost   
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	//remove those on production  

	$url = 'https://api-eu.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}/loans';
	$templateParamNames = array('{user_id}');
	$templateParamValues = array(urlencode($user_id),urlencode('100'));
	$url = str_replace($templateParamNames, $templateParamValues, $url);
	$queryParams = '?' . urlencode('limit') . '=' . urlencode('100') . '&'. urlencode('apikey') . '=' . urlencode($apikey);
	echo $url;
	
	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);
	curl_close($ch);
	//print($response); 
	return $response; 
}
echo "Done!";

function curl_update_loans_due_dates($user_id, $loan_id, $apikey, $newdate) {
//$newdate is a date string in the format of "YYYY-mm-dd"

	$ch = curl_init();
	//turnning off SSL verification on localhost   
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	//remove those on production  

	$url = 'https://api-eu.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}/loans/{loan_id}';
	$templateParamNames = array('{user_id}', '{loan_id}');
	$templateParamValues = array(urlencode($user_id), urlencode($loan_id) );
	$url = str_replace($templateParamNames, $templateParamValues, $url);
	$queryParams = '?' . urlencode('limit') . '=' . urlencode('100') . '&'. urlencode('apikey') . '=' . urlencode($apikey);
	//echo $url;
	
	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);

	$postArgs = '<?xml version="1.0" encoding="UTF-8"?>
					<item_loan>
						<due_date>'. $newdate. 'T23:59:00.000Z</due_date>
					</item_loan>';

	// For xml, change the content-type.
	curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml"));

	//curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postArgs);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	
	$response = curl_exec($ch);
	curl_close($ch);
	//print($response); 	
	return $response; 
} 	


?>  