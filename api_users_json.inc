<?php 

/*********************************************************** 
Functions related to Alma user api calls json data 
Created by: Tao You at Vanderbilt University Library
Last updated: 4/2020

**** list of functions ****


update_user_json()

curl_get_user_details()
curl_update_user()

************************************************************/


function udpate_user_json($user, $path, $newValue){
//$user is a user record in json format    
//$path is the index to the element; 
//$newValue usrally is an array 
    unset($user->$path); 
    $user->$path = $newValue;
    //var_dump($user); 
    return $user; 
}

function curl_get_user_details($userid, $apikey)
{
    $ch = curl_init();
    //turnning off SSL verification on localhost   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //remove those on production  

    $baseurl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}?';
    $templateParamNames = array('{user_id}');
    $templateParamValues = array(urlencode($userid) );
    $baseurl = str_replace($templateParamNames, $templateParamValues, $baseurl);
 
    $queryParams = array(
        'user_id_type' => 'all_unique',
        'view' => 'full',
        'apikey' => urlencode($apikey)
    );

    $url = $baseurl . http_build_query($queryParams);
    echo $url, "<br/>"; 

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


function curl_update_user($user_id, $user_json, $apikey) {
    //this will be "swap-all" replacement";

    $ch = curl_init();
    //turnning off SSL verification on localhost   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //remove those on production  

    $baseurl = 'https://api-eu.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}?';
    $templateParamNames = array('{user_id}');
    $templateParamValues = array(urlencode($user_id) );
    $baseurl = str_replace($templateParamNames, $templateParamValues, $baseurl);
    $queryParams = array(
        'user_id_type' => 'all_unique',
        'override' => 'user_group',
        'send_pin_number_letter' => 'false',
        'apikey' => urlencode($apikey)
    );

    $url = $baseurl . http_build_query($queryParams);
   //echo $url, "<br/>";

    $postArgs = json_encode($user_json);

    // For xml, change the content-type.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));

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