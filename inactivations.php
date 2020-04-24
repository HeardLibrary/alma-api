<?php
require __DIR__ . '/vendor/autoload.php';

use phpseclib\Net\SFTP;

// Grab SFTP info
include('ftp_info.php');

// create date variable to be used for archiving
$datevar=date("Ymd");

// find day of week - run VU FTP once a week on Tuesday
$dayofweek=date("D");

if ($dayofweek == "Tue") {
  // VU FTP connector
  $vu=vu();

  //change directory on VUIT FTP server
  $vu->chdir('inbound/prd/alma-inactivations');

  //get zip file of inactives
  $vu->get('ils_student_inactive_export.zip','vu_inactives.zip');
}

// VUMC FTP connector
$vumc=vumc();

//change directory on VUMC FTP server
$vumc->chdir('inactivate');

//get zip file of inactives
$vumc->get('en_library_inactivate.medc.xml.zip','vumc_inactives.zip');

?>
