<?php
/*************************************************************************
 This script read a list of MMSID from a .csv file, then generate a list of item barcodes 

*************************************************************************/
set_time_limit(0); //avoid php timeout 

//output outcome as it is generated, only used for local testing
ob_end_flush();
ob_implicit_flush();

error_reporting(E_ALL | E_STRICT);
ini_set("display_error", true); 
ini_set("auto_detect_line_ending", true); 
$html_eol = "<br/>"; 

//getting apikey from api_keys file
include ("api_keys.inc"); 
$apikey = $apikeys['production']['bib']; 

//prepare infile and output file
$bibfile = "test_non_ht_titles.csv"; 
$fp = fopen( $bibfile, 'r');

$itemp = fopen("test_non_ht_items.csv", 'a');

//read MMSID from .csv file 
$i = 0; 
while ( ($line = fgetcsv($fp, 0, "\t")) !== FALSE) {
  //$line is an array of the csv elements

    //testing control 
    if ($i < 1) {$i++; continue;}  
    if ($i > 4 ) break;

    $i++; 
    $mmsid = $line[1];

   //call Alma API to get list of items for each MMSID 
    $rr = curl_get_bib_items( $mmsid, $apikey);
    $items = json_decode($rr, true); 
    //echo "<pre>"; print_r($items['item']); echo "</pre>";

    foreach ( $items['item'] as $item) {
        //echo "<pre>"; print_r($item['item_data']); echo "</pre>";
        $dd = $item['item_data']; 
        echo $mmsid, " ---- ". $dd['barcode']. $html_eol;
        fwrite($itemp, $mmsid. ", ". $dd['barcode']. PHP_EOL); 
    }

}

fclose($fp);
fclose($itemp); 

function curl_get_bib_items($mmsid, $apikey)
{
    $ch = curl_init();
    //turnning off SSL verification on localhost   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //remove those on production  

    $baseurl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/{mms_id}/holdings/ALL/items?';
    $templateParamNames = array('{mms_id}');
    $templateParamValues = array(urlencode($mmsid) );
    $baseurl = str_replace($templateParamNames, $templateParamValues, $baseurl);
 
    $queryParams = array(
        'limit' => '100',
        'view' => 'brief',
        'apikey' => urlencode($apikey)
    );

    $url = $baseurl . http_build_query($queryParams);
    //echo $url, "<br/>"; 

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Accept: application/json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $response = curl_exec($ch);
    curl_close($ch);
    //print($response); 
    return $response; 
}
?>
