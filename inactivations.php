<?php
require __DIR__ . '/vendor/autoload.php';

use phpseclib\Net\SFTP;

// Grab SFTP info
include('ftp_info.php');

// create date variable to be used for archiving
$datevar=date("Ymd");

// VU FTP connector
$vu=vu();

$vu->chdir('inbound/prd/alma-inactivations');
$vu->get('ils_student_inactive_export.zip','vu_inactives.zip');
exec('unzip vu_inactives.zip');
exec('cat ils_student_inactive_export.xml|grep primary_id|sed "s/<primary_id>//g"|sed "s/<\/primary_id>//g"|sed -e "s/ //g" > inactives.txt');
rename('vu_inactives.zip','user_data/Archive/vu_inactives_$datevar.zip');

// VUMC FTP connector
//$vumc=vumc();

//$vumc->chdir('files/inactivate');
//$vumc->get('en_library_inactivate.medc.xml.zip','vumc_inactives.zip');
//exec('unzip vumc_inactives.zip');
?>
