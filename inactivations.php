<?php
// Grab SFTP info
include('ftp_info.php');

// FTP connector
$vu=vu();
$vumc=vumc();

if (ftp_chdir($vu, "inbound/prd/alma-inactivations")) {
	if (ftp_get($vu, "inactives.zip", "ils_student_inactive_export.zip", FTP_BINARY)) {
		echo "Successfully downloaded file.";
	} else {
		echo "There was a problem downloading the file.";
	}
}
?>