#!/usr/bin/php
<?PHP

/*
takesnapshot.php
Is a small script which can create and save a Teamspeak 3 server snapshot utilising ts3admin.class
by Jeremy Paton - Forge Media

Requires: ts3admin.class.php (http://ts3admin.info)

Please note this script uses CRON. Please settup a cron-job to call this script as often as possible,
ideally 24hr intervals.

0 0 * * * /usr/bin/php /home/directory/public_html/directory/takesnapshot.php >/dev/null 2>&1

/*-------SETTINGS-------*/
$ts3_ip = '';
$ts3_queryport = 10011;
$ts3_user = '' #Avoid serveradmin;
$ts3_pass = '';
$ts3_port = 9987;
$mode = 3; #1: send to client | 2: send to channel | 3: send to server
$target = 1; #serverID
$botName = 'Forge Media Backup Bot';
$filedir = "backups/snapshot-".date('m-d-Y_hia').".file";
#backup Retention Settings
$days = 7; # No. days to store backups
$path = 'backups/';
$counter = 0;
/*----------------------*/


#Include ts3admin.class.php
require("library/ts3admin.class.php");


#remove backups older than 7 days
// Open the directory  
if ($handle = opendir($path))  
{  
    // Loop through the directory  
    while (false !== ($file = readdir($handle)))  
    {  

        // Check the file we're doing is actually a file  
        if (is_file($path.$file))  
        {  

            // Check if the file is older than X days old  
            if (filemtime($path.$file) < ( time() - ( $days * 24 * 60 * 60 ) ) )  
            {  
            	$counter++;	
                // Do the deletion  
                unlink($path.$file);  
            }  
        }  
    }
}  


#build a new ts3admin object
$tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);

if($tsAdmin->getElement('success', $tsAdmin->connect())) {

	#login as serveradmin
	$tsAdmin->login($ts3_user, $ts3_pass);

	#select teamspeakserver
	$tsAdmin->selectServer($ts3_port);

	#set bot name
	$tsAdmin->setName($botName);

	#perform snapshot
	$snapshotarray = $tsAdmin->serverSnapshotCreate();
	$snapshot = $snapshotarray['data'];
	$strsnapshot = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $snapshot);

	#save snapshot to ID-File temporary (not best solution)
	file_put_contents($filedir, $strsnapshot);
	
	#Final-Save snapshot to ID-File
	file_put_contents($filedir, implode(PHP_EOL, file($filedir, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));

	#Set message for TS & Web
	$tsmessage = 'Backup successful. File saved as: '.$filedir."\r\n".'With ['.$counter.'] - 7 day old backups successfully deleted';
  	echo $tsmessage;
    
  	#send message to Teamspeak
	$tsAdmin->sendMessage($mode, $target, $tsmessage);
  }

  else{

	 echo 'Connection could not be established.';

}


#This code retuns all errors from the debugLog
if(count($tsAdmin->getDebugLog()) > 0) {

	foreach($tsAdmin->getDebugLog() as $logEntry) {

		echo '<script>alert("'.$logEntry.'");</script>';
	}
}

?>