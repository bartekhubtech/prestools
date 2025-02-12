<?php
$embedded = "1";
$prestools_starttime = time();
include("login1.php");
include("functions1.php");

/* check values from settings1.php file */
$initwarnings = "";
foreach($prestools_settings["users"] AS $usrname => $usrvalues)
{ if(($usrname == "demo@demo.com") && !$prestools_settings["demo_mode"])
	$initwarnings .= "Change the username in the file \\'settings1.php\\'!\\n\\n";
  if($usrvalues[0] == "opensecret") 
	$initwarnings .= "Change the password in the file \\'settings1.php\\'!\\n\\n";
}
if(rand(0,3)!=2)		/* show this 1 in 3 times */
	$initwarnings = "";
if((sizeof($prestools_settings["ipaddresses"])==0) && (rand(0,10)==2))
	$initwarnings .= "For your safety is recommended to set safe IP addresses in the file \\'settings1.php\\'! You can use wildcards (\\'*\\').";
if($prestools_settings["keep_log_file"])
{ if($prestools_settings["log_directory"] == "")
	colordie("You must specify a directory when you enable logging!");
  if(!file_exists($prestools_settings["log_directory"]))
  { if(!mkdir($prestools_settings["log_directory"],0777))
	  colordie("Directory ".$prestools_settings["log_directory"]." does not exist and cannot be created!");
  }
  if(!is_dir($prestools_settings["log_directory"]))
	colordie($prestools_settings["log_directory"]." is not a directory!");
  if(!is_writable($prestools_settings["log_directory"]))
	colordie("You have no permission to write in directory ".$prestools_settings["log_directory"]);
  if(substr($prestools_settings["log_directory"], -1) == "/") /* remove traling slash when present for consistency */
	$prestools_settings["log_directory"] = substr($prestools_settings["log_directory"],0,-1);
  if($prestools_settings["users"][$username][1] == "")
	  $logfileuser = $username;
  else
	  $logfileuser = $prestools_settings["users"][$username][1];
}
