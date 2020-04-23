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
rename('vu_inactives.zip','user_data/Archive/vu_inactives_'.$datevar.'.zip');
exec('php expire_inactive_users.php?server=sandbox&infile=ils_student_inactive_export.xml');
unlink('ils_student_inactive_export.xml');

// VUMC FTP connector
$vumc=vumc();

$vumc->chdir('inactivate');
$vumc->get('en_library_inactivate.medc.xml.zip','vumc_inactives.zip');
exec('unzip vumc_inactives.zip');
rename('vumc_inactives.zip','user_data/Archive/vumc_inactives_'.$datevar.'.zip');
exec('php expire_inactive_users.php?server=sandbox&infile=en_library_inactivate.medc.xml');
unlink('en_library_inactivate.medc.xml');

?>
