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

  //rename & put zip file back on SFTP server
  $vu->put('ils_student_inactive_export.zip.'.$datevar,'vu_inactives.zip');
}

// VUMC FTP connector
$vumc=vumc();

//change directory on VUMC FTP server
$vumc->chdir('inactivate');

//rename & put zip file of inactives back on SFTP server
$vumc->put('en_library_inactivate.medc.xml.zip.'.$datevar,'vumc_inactives.zip');
?>
