<?php
require __DIR__ . '/vendor/autoload.php';

use phpseclib\Net\SFTP;

// Grab SFTP info
include('ftp_info.php');

// FTP connector
$vu=vu();
$vumc=vumc();

$vu->chdir('inbound/prod/alma-inactivations');
$vu->get('ils_student_inactive_export.zip','vu_inactives.zip');

?>
