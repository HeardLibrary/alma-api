<?php
use phpseclib\Net\SFTP;

// Grab SFTP info
include('ftp_info.php');

// FTP connector
$vu=vu();
$vumc=vumc();

$sftp->chdir('inbound/prod/alma-inactivations');
$sftp->get('ils_student_inactive_export.zip','vu_inactives.zip');

?>