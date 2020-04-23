<?php 

/*********************************************************** 
Functions related to Alma user api calls json data 
Created by: Tao You at Vanderbilt University Library
Last updated: 4/2020

**** list of functions ****
functions to create/update user_json or user element 
- create_emails_json()
- create_ids_json()
- create_one_address()
- create_addresses()
- update_user_json()

direct API calls:
- curl_get_user_details()
- curl_update_user()
- curl_create_user()
************************************************************/
function create_emails_json($emailsArray) {
//$emailsArray = array( array( "email_value"=> , "email_desc"=> , "email_types" => array('value'=>'desc', ...) ))

    $alma_emails_type_desc = array( "work" => "Work", "school" => "School", "personal" => "Personal", "alternative" => "Alternative"); 
    
    $user_emails  = array(); 

    $preferred_flag = true; 
    foreach($emailsArray as $email){
        if ( $email['email_value'] ) {
            $j = array(); 
            $j['email_address'] = $email['email_value']; 
            if (isset($email['email_desc'])) $j['description'] = $email['email_desc']; 
            
            if ($preferred_flag) { $j['preferred'] = $preferred_flag; $preferred_flag = false; }
            else $j['preferred'] = $preferred_flag; 

            if (isset($email['segment_type'])) $j['segment_type'] = $email['segment_type'];

            $j['email_type'] = array(); 
            foreach($email['email_types'] as $v => $d) {
                $k = array(); 
                $k['value'] = $v; 
                //$k['desc'] = $alma_emails_type_desc[$v];
                $k['desc'] = $d;    
                $j['email_type'][] = $k;  
            }
            $user_emails[] = $j;
        }    
    }   

    return $user_emails; 
}   


function create_ids_json($ids) {
//$ids = array( array( "value" => "id_value", "type" => "id_type", "desc" => "type_desc") ) 

    $user_ids = array(); 
    foreach ($ids as $id ) {
        $j['value'] = $id['value']; 
        $j['id_type'] = array ("value" =>$id['type'] , "desc"=> $id['desc']); 
        $j['status'] = "ACTIVE"; 
        $j['segment_type'] = "External"; 

        $user_ids[] = $j;
    }   
    return $user_ids;
}    


function create_one_address($address) {
//address = array('line1' => ,'line2'=>) 
    $j = array(); 
    $j['line1'] = $address['line1']; 
    if (isset($address['line2'])){
         $j['line2'] = $address['line2']; 
    }
    $j['address_type'] = array( array("value"=>"work", "desc"=>"Work") ); 
    return $j; 
} 

function create_addresses_json($addresses) {
    $user_addresses = array(); 
    foreach ($addresses as $address){
        $j = create_one_address($address);
        $user_addresses[] = $j;
    }
    return $user_addresses;     
}


function update_user_json(&$user, $path, $newValue){
//$user is an user json object decoded into array format    
//$path is the index to the element; 
//$newValue usrally is an array     
    
    $p = explode("->", $path);
    //print_r($p); 

    //now the path limits to 2 levels, will find a better way to deal with multiple levels

    if (count($p) == 1) {
        unset($user->{$p[0]}); 
        $user->{$p[0]} = $newValue;
    }
    elseif (count($p) == 2 ) {
        unset($user->{$p[0]}->{$p[1]}); 
        $user->{$p[0]}->{$p[1]} = $newValue;
    }

    //var_dump($user); 
    //return $user; 
}

function curl_get_user_details($userid, $apikey)
{
    $ch = curl_init();
    //turnning off SSL verification on localhost   
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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

function curl_update_user($user_id, $user_json, $apikey) {
    //this will be "swap-all" replacement";

    $ch = curl_init();
    //turnning off SSL verification on localhost   
    ////curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    ////curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
    curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Accept: application/json"));
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


function curl_create_user($user_json, $apikey) {
    //this will be "swap-all" replacement";

    $ch = curl_init();
    //turnning off SSL verification on localhost   
    ///curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    ///curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //remove those on production  

    $baseurl = 'https://api-eu.hosted.exlibrisgroup.com/almaws/v1/users/?';
    //$templateParamNames = array('{user_id}');
    //$templateParamValues = array(urlencode($user_id) );
    //$baseurl = str_replace($templateParamNames, $templateParamValues, $baseurl);
    $queryParams = array(
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
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
    $response = curl_exec($ch);
    curl_close($ch);
    //print($response);     
    return $response; 
}   
?>