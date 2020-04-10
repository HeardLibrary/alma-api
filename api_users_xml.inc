<?php

/*********************************************************** 
Functions related to Alma user api calls using XML data structure
Created by: Tao You at Vanderbilt University Library
Last updated: 4/2020

**** list of functions ****

createAnElement()
createContacts()
createEmails()
createIds()
createRoles()
createUserStatistics()
createUserNotes()

update_user_xml()

curl_get_user_details()
curl_update_user()

************************************************************/


function createAnElement($doc, $name, $value, $attrName, $attrValue) {

    $node = $doc->createElement($name, $value);
    if ($attrName) { 
        $node_attribute = $doc->createAttribute($attrName); 
        $node_attribute->value= $attrValue; 
        $node->appendChild($node_attribute);
    }   
    return $node; 

} 

//currently only create emails element in Contacts element 
function createContacts($doc, $emailArray) {
// $emailArray = array( array( "type" => , "desc"=> , "value"=>  ))
// the first one will be preferred email address 

    $contactsNode = $doc->createElement("contact_info"); 

    $emailsNode = createEmails($doc, $emailArray);
    
    $addressesNode = $doc->createElement("addresses"); 
    $phonesNode = $doc->createElement("phones"); 

    $contactsNode -> appendChild($addressesNode); 
    $contactsNode -> appendChild($emailsNode); 
    $contactsNode -> appendChild($phonesNode); 

    return $contactsNode; 
}


function createEmails($doc, $emailArray){
//accept emailArray to create mulitple emails
// $emailArray = array( array( "type" => , "desc"=> , "value"=>  ))
// the first one will be preferred email address 
    
    $emailsNode = $doc->createElement("emails");

    $i = 1; 
    foreach ($emailArray as $email ) {
        if ($i == 1 ) $preferredValue = "true"; 
        else $preferredValue = "false"; 

        $emailNode = $doc->createElement("email"); 
        $emailAttr1 = $doc->createAttribute("preferred"); 
        $emailAttr1 -> value = $preferredValue; 
        $emailAttr2 = $doc->createAttribute("segment_type");
        $emailAttr2 -> value = "External"; 
        $emailNode -> appendChild($emailAttr1);
        $emailNode -> appendChild($emailAttr2);

        $emailAddressNode = $doc->createElement("email_address", $email['value']); 

        $emailTypesNode = $doc->createElement("email_types");   
        $emailTypeNode = createAnElement($doc, "email_type", $email['type'], "desc", $email['desc']); 
        $emailTypesNode -> appendChild($emailTypeNode); 

        $emailNode -> appendChild($emailAddressNode);   
        $emailNode -> appendChild($emailTypesNode); 

        $emailsNode -> appendChild($emailNode);
        $i ++;
    }    
    return $emailsNode;
}

function createIds($doc, $idArray){
//accept idArray to create mulitple IDs
// $idArray = array( array( "type" => , "desc"=> , "value"=>  ))

    $userIdsNode = $doc->createElement("user_identifiers"); 

    foreach  ($idArray as $id ) {
        $idNode = createAnElement($doc, "user_identifier", "", "segment_type", "Internal");
        $id_type = createAnElement($doc, "id_type", $id['type'], "desc", $id['desc']); 
        $value = $doc->createElement("value", $id['value']); 
        $status = $doc->createElement("status", "ACTIVE"); 
        
        $idNode -> appendChild($id_type); 
        $idNode -> appendChild($value); 
        $idNode -> appendChild($status);

        $userIdsNode -> appendChild($idNode); 
    }
    return $userIdsNode; 
}


function createRoles($doc){
//currently only created patron role, maybe expanded to accept array to creat multiple roles     
    $rolesNode = $doc->createElement("user_roles"); 

    $roleNode = $doc->createElement('user_role');
    
    $status = $doc->createElement("status", "ACTIVE"); 
    $scope = createAnElement($doc, "scope", "01VAN_INST", "desc", "Vanderbilt University"); 
    $role_type = $doc->createElement("role_type", 200);

    $roleNode -> appendChild($status); 
    $roleNode -> appendChild($scope); 
    $roleNode -> appendChild($role_type); 

    $rolesNode -> appendChild($roleNode);  

    return $rolesNode; 
}


function createUserStatistics($doc, $user_stats) {
//accepts $user_stats Array to create multiple user_statistics
//$user_stats = array ("stats_name" => "$stats_desc")

    $statsNode = $doc->createElement("user_statistics"); 
    foreach ($user_stats as $stats_name => $stats_desc) {

        $statNode = createAnElement($doc, "user_statistic", "", "segment_type", "Internal");
        $statCat = createAnElement($doc, "statistic_category", $stats_name, 'desc', $stats_desc);
        $statNode -> appendChild($statCat); 
        $statsNode -> appendChild($statNode); 
    }
    return $statsNode; 
}

function createUserNotes($doc, $load_notes) {
//accept $load_notes array to create multiple notes 
// $user_notes = array( array("content", "viewable", "popup") )

    $this_load_time = date("Y-m-d"). "T". date("h:i:s"). "487Z"; 
    $notesNode = $doc->createElement("user_notes"); 

    foreach ($load_notes as $lnote) {
        $noteNode = createAnElement($doc, "user_note", "", "segment_type", "Internal");
        $note_type = createAnElement($doc, "note_type", "LIBRARY", 'desc', "Library");
        $note_text = $doc->createElement("note_text", $lnote['content']); 
        $viewable = $doc->createElement("user_viewable", $lnote['viewable']); 
        $popup = $doc->createElement("popup_note", $lnote['popup']); 
        $created_by = $doc->createElement("created_by", "LTDS");
        $created_date = $doc->createElement("created_date", $this_load_time);

        $noteNode -> appendChild($note_type); 
        $noteNode -> appendChild($note_text); 
        $noteNode -> appendChild($viewable); 
        $noteNode -> appendChild($popup); 
        $noteNode -> appendChild($created_by);   
        $noteNode -> appendChild($created_date); 

        $notesNode -> appendChild($noteNode);
    }     
    return $notesNode; 

}

function udpate_user_xml($user, $user_element, $new_element) {

    $old_elements = $user->getElementsByTagName($user_element);
    $i = $old_elements->length-1; 
    while( $i > -1 ) {

        $old_element = $old_elements->item($i); 

        $old_element->parentNode->replaceChild($new_element, $old_element);

        $i --; 
    }   

    return $user->saveXML(); 
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
    //echo $url, "<br/>"; 

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $response = curl_exec($ch);
    curl_close($ch);
    //print($response); 
    return $response; 
}


function curl_update_user($user_id, $user_xml, $apikey) {
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

    $postArgs = $user_xml;

    // For xml, change the content-type.
    curl_setopt($ch, CURLOPT_URL, $url);
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