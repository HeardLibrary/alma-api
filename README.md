# alma-api

A collection of scripts that make Alma API calls to process user records, loan records, or bib records. 

## setup 
Alma api keys are not stored in this repo for security, in order to run those api applications locally, you need to create a file named api_keys.inc in the root directory, the file should look like this: 

```
<?php 
$apikeys = array(
	"sandbox" => array(
		"user" => "sandbox_user_api_key", 
		"bib" => "sandbox_bib_api_key", 
		"bibread" => "sandbox_bib_read_only_api_key", 
		"acqusition" => "sandbox_acqusition_api_key" 
	), 
	"production" => array(
		"user" => "production_user_api_key",
		"bib" => "production_bib_api_key", 
		"bibread" => "production_bib_read_only_api_key", 
		"acqusition" => "production_acqusition_api_key"
	)
)
?>
```

In the API script, user should identify which key to use for their particular application.

```
<?php 
	include("api_keys.inc"); 
	$server = "sandbox";
	$keytype = "user"; 
	$apikey = $apikeys[$server][$keytype];
	echo "<p><strong> you are running the script for $server </strong></p>"; 
?>
```

All user data, item data are stored in user_data, item_data sub directory, are not made availabble in this repo to protect user privacy.  You need to create those sub directories and store institutional user data there. 


