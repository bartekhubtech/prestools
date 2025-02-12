<?php 
error_reporting(E_ALL|E_STRICT);
ini_set( 'display_errors', 1);
if (!file_exists('settings1.php'))
{ echo "Settings file not found!";
  exit(0);
}
include 'settings1.php';

  $ip_address = $_SERVER['REMOTE_ADDR'];
  if(isset($_SERVER['SERVER_ADDR']) && ($ip_address == $_SERVER['SERVER_ADDR']))
  { if(isset($_SERVER['HTTP_X-Forwarded-For'])) $ip_address = $_SERVER['HTTP_X-Forwarded-For'];
    else if(isset($_SERVER['HTTP_X-Client-IP'])) $ip_address = $_SERVER['HTTP_X-Client-IP'];
    else if(isset($_SERVER['HTTP_X_REAL_IP'])) $ip_address = $_SERVER['HTTP_X_REAL_IP'];
  }

if((sizeof($prestools_settings["ipaddresses"]) > 0) && (!checkIPs($prestools_settings["ipaddresses"])) && (!isset($embedded) || !$prestools_settings["noboipcheck"] ))
{ echo "You may not use this script from IP Adress: ".$_SERVER['REMOTE_ADDR']; exit();}
if (!function_exists('mysqli_connect'))
  die("Your server does not support the PHP MySQLi functions. Ask your hosting provider to add them to your installation! This is essential software - also for other applications.<br>If you control your own installation: remove the comment sign before the php_mysqli module in php.ini.");

date_default_timezone_set(@date_default_timezone_get()); // Suppress DateTime warnings
//  ini_set('date.timezone', @date_default_timezone_get());  //alternative: if(!ini_get('date.timezone')) {date_default_timezone_set('GMT');} 
$sname = str_replace(".","", $_SERVER['SERVER_NAME']);
connect_to_database();

$url = $_SERVER['REQUEST_URI'];
$validated = false;
if(isset($embedded))
{ check_logincount();
  if(1)  /* this used to offer a choice between cookies and sessions */
  { $seed = get_seed();
    foreach($prestools_settings["users"] AS $username => $uservalues)
	{ $userkey = preg_replace('/[^a-zA-Z0-9]/','', $username);
	  if(!isset($_COOKIE["tripleedit".$userkey])) continue;
      $encseed = hash('sha256', $seed.$uservalues[0]);
	  $encseed2 = stripslashes(stripslashes(stripslashes(convert_uuencode(base64_encode($encseed)))));  // some servers escape cookies
      if(stripslashes(stripslashes(stripslashes($_COOKIE["tripleedit".$userkey]))) == $encseed2)
// Note: cookie may be replaced with localStorage.setItem('key', 'value') and localStorage.getItem('key'));
	  { $validated = true;
	    break;
	  }
	}
  } 
  if (!$validated) 
  { header('Location: login1.php?url='.urlencode($url)); //Replace that if login1.php is somewhere else
  }
  reset_logincount();
}
else  /* when login1.php is called stand-alone ( = not from approve.php) */
{ 
  if(isset($_POST['username']) && isset($_POST['pswd']))
  { $pswd = $_POST['pswd'];
	check_logincount();
	$username = $_POST['username'];
	if(isset($prestools_settings["users"][$_POST['username']]) && (($prestools_settings["md5hashed"] 
	&& (md5($pswd) == $prestools_settings["users"][$_POST['username']][0] )) 
	|| (!$prestools_settings["md5hashed"] && ($pswd == $prestools_settings["users"][$_POST['username']][0]))))
    { if(1)  /* this was a choice between cookies and sessions */
	  { $userkey = preg_replace('/[^a-zA-Z0-9]/','', $_POST['username']);
		$seed = get_seed();
		$encseed = hash('sha256', $seed.$pswd);
		$encseed = stripslashes(stripslashes(stripslashes(convert_uuencode(base64_encode($encseed)))));  // some servers escape cookies
		setcookie("tripleedit".$userkey, $encseed,  time()+3600*24*365);
		foreach($prestools_settings["users"] AS $usrname => $usrvalues) /* erase other user cookies */
		{ $usrkey = preg_replace('/[^a-zA-Z0-9]/','', $usrname);
		  if($usrkey != $userkey)
			setcookie("tripleedit".$usrkey, '',  time()+3600*24*365);
		}
	  }
	  reset_logincount();
      if(isset($_GET['url']))
        header('Location: '.urldecode($_GET['url'])); //Replace index.php with what page you want to go to after succesful login
      else
        header('Location: product-edit.php'); //Replace index.php with what page you want to go to after succesful login
	  if(isset($_GET['url']))
	    echo "Redirection problem for ".$_GET['url'];
	  else 
	    echo "Redirection to edit pages is impossible.";
      exit;
    } 
	else 
	{ echo '<script type="text/javascript">
         alert(\'Wrong Username or Password, Please Try Again!\');
     </script>';
    }
  }

  echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestools Login</title>
</head>
<body>'; 
  echo "<br/>";
  echo "<br/>IP address = ".$ip_address;
  if(!isset($_POST['username'])) $_POST['username'] = "";
  if(!isset($_POST['pswd'])) $_POST['pswd'] = "";
  echo '
<p/>&nbsp;<p/>&nbsp;<p/><p/><p/><p/>
<center>
<form method="post" action="">
<table>
<tr><td>User:</td><td><input type="input" name="username" value = "'.$_POST['username'].'"></td></tr>
<tr><td>Password:</td><td><input type="password" name="pswd" value = "'.$_POST['pswd'].'"></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="login" value="Login"></td></tr>';
// echo '<tr><td colspan=2><br>User: demo@demo.com<br>Pw: demodemo</td></tr>';
echo '</table>
</form>
</center>
</body>
</html>';
}

function check_logincount()
{	global $conn;
    $day = date("Ymd");
    $hour = date("H");
    $minute = date("i");
    /* check for too many login attempts */
	/* $parts looks like 20171205_17_30_5_3_1: this means that the last login attempt was 
	  at 17:30 on 5 dec 2017 and that that day there had been 5 login attempts, that hour 3 and that minute 1 */
	$query = "select value FROM `"._DB_PREFIX_."configuration` WHERE name='"._PRESTOOLS_PREFIX_."LOGIN_FREQUENCY'";
	$res = mysqli_query($conn, $query);
	if($row = mysqli_fetch_array($res))
	{ $parts = explode("_",$row["value"]); 
	  if(intval($parts[3]) > 900) die("Too many login attempts for this day!");
	  if(intval($parts[4]) > 200) die("Too many login attempts for this hour!");
	  if(intval($parts[5]) > 25) die("Too many login attempts for this minute!");
	  if($day != $parts[0]) $newval = "1_1_1";
	  else if ($hour != $parts[1]) $newval = (1+intval($parts[3]))."_1_1";
	  else if ($minute != $parts[2]) $newval = (1+intval($parts[3]))."_".(1+intval($parts[4]))."_1";
	  else $newval = (1+intval($parts[3]))."_".(1+intval($parts[4]))."_".(1+intval($parts[5]));
	  $query = "UPDATE `"._DB_PREFIX_."configuration` SET value='".$day."_".$hour."_".$minute."_".$newval."' WHERE name='"._PRESTOOLS_PREFIX_."LOGIN_FREQUENCY'";
	  $res = mysqli_query($conn, $query);
    }
	else
	{ $query = "INSERT INTO `"._DB_PREFIX_."configuration` SET value='".$day."_".$hour."_".$minute."_1_1_1', name='"._PRESTOOLS_PREFIX_."LOGIN_FREQUENCY'";
	  $res = mysqli_query($conn, $query);
	}
}

function reset_logincount()
{ global $conn;
  $day = date("Ymd");
  $hour = date("H");
  $minute = date("i");
  $query = "UPDATE `"._DB_PREFIX_."configuration` SET value='".$day."_".$hour."_".$minute."_0_0_0' WHERE name='"._PRESTOOLS_PREFIX_."LOGIN_FREQUENCY'";
  $res = mysqli_query($conn, $query);
}

function checkIPs($allowedaddresses)
{ $myips = array();
  if(isset($_SERVER['HTTP_X-Forwarded-For'])) $myips[] = $_SERVER['HTTP_X-Forwarded-For'];
  if(isset($_SERVER['HTTP_X-Client-IP'])) $myips[] = $_SERVER['HTTP_X-Client-IP'];
  if(isset($_SERVER['HTTP_X_REAL_IP'])) $myips[] = $_SERVER['HTTP_X_REAL_IP'];
  if(isset($_SERVER['REMOTE_ADDR'])) $myips[] = $_SERVER['REMOTE_ADDR'];
  if(sizeof($myips) == 0)
  { echo "No IP address found for your access point. Filtering with this IP address impossible.";
	return false;
  }
  foreach($myips AS $myip) 
  { $separator = "";
    if(strpos($myip,":") > 0)
    { $myparts = explode(":",$myip);
	  $separator = ":";
	}
    else if(strpos($myip,".") > 0)
	{ $myparts = explode(".",$myip);
	  $separator = ".";
	}
    foreach($allowedaddresses AS $ip)
    { if($myip == $ip)
	    return true;
	  else if($separator == ".")
      { if(strpos($ip,".") <= 0) continue;
		$parts = explode(".",$ip);
	    $allowed = true;
	    for($i=0; $i<4; $i++)
	      if(($myparts[$i]!= $parts[$i]) && ($parts[$i] != "*"))
		    $allowed = false;
	    if($allowed == true)
	      return true;
	  }
	  else if($separator == ":")
      { if(strpos($ip,":") <= 0) continue;
		$parts = explode(":",$ip);
	    $allowed = true;
	    for($i=0; $i<8; $i++)
	    { if(($myparts[$i]!= $parts[$i]) && ($parts[$i] != "*"))
		    $allowed = false;
		}
	    if($allowed == true)
	      return true;
	  }
//	  else echo "mizz".$separator."-".$myip." ".$ip." ";
	}
  }
  echo "<p>No access allowed for IP addresses: "; print_r($myips); echo "<br>";
  die();
  return false;
}

function get_seed()
{ $key = get_config_value('PS_PRESTOOLS_KEY');
  if(!$key)
  { $key = "";
    $len = 30;
	$rawkey = openssl_random_pseudo_bytes($len);
	for($i=0; $i<$len; $i++) /* make sure that all bytes have ascii value between 32 and 126 */
	{ $x = ord($rawkey[$i]);
	  if($x > 126) $x -= 126;
	  if($x <32) $x += 32;
	  if($x==97) $x= 42; /* remove slashes, ampersand and quotes */
	  if($x==32) $x= 63;
	  if($x==34) $x= 37;
	  if($x==39) $x= 68;
	  if($x==38) $x= 72;
	  $key .= chr($x);
	}
    set_config_value('PS_PRESTOOLS_KEY', $key);
  }

  return @date("mY").$key;
}

function check_for_BOM()
{ 	global $session;
	if($session)
	{   $file = @fopen("approve.php", "r"); 
		$bom = fread($file, 3); 
		if ($bom == b"\xEF\xBB\xBF") 
		{ echo '<script type="text/javascript">
				alert(\'BOM header found! Use another text editor!\');
			</script>';
		  exit;
		}
    } 
}

/* in PS 1.5 and 1.6 all constants are in config/settings.inc.php */
/* in PS 1.7 _PS_VERSION_ is in /config/autoload.php and the other constants are in app/config/parameters.php */
/* PS 8.1 defines VERSION in /src/Core/Version.php */
/* the file /modules/autoupgrade/classes/PrestashopConfiguration.php contains a function getPrestaShopVersion() that has solutions for the different versions */
/* other places where the version number is mentioned:
     /app/AppKernel.php
	 in the /config/xml directory is a file with the version nr followed by .xml, like 1.7.8.9.xml
     in the file /docs/CHANGELOG.txt the latest version is on top.
*/
function connect_to_database()
{ global $triplepath, $conn, $headermsgs, $localpath, $shoppath, $allow_accented_chars;
  global $shoproots;
  if(is_dir("../themes") && is_dir("../modules"))
  { $triplepath = "../";
	$level = 1;
  }
  else if(is_dir("../../themes") && is_dir("../../modules"))
  { $triplepath = "../../";
	$level = 2;
  }
  else if(is_dir("../../../themes") && is_dir("../../../modules")) /* this is a file in the root */
  { $triplepath = "../../../";
	$level = 3;
  }
  else if(is_dir("../../../../themes") && is_dir("../../../../modules")) /* this is a file in the root */
  { $triplepath = "../../../../";
	$level = 4;
  }
  else if(is_dir("../../../../../themes") && is_dir("../../../../../modules")) /* this is a file in the root */
  { $triplepath = "../../../../../";
	$level = 5;
  }
  else
    die( "<p><b>Your files should be in a subdirectory of the admin directory of your shop!</b>");
  $localpath = realpath($triplepath);
  $localpath = str_replace("\\", "/", $localpath); /* windows */
  $selfs = explode("/",$_SERVER['PHP_SELF']);
  $shoppath = "/";
  for($i=1; $i<(sizeof($selfs)-$level-1); $i++)
	  $shoppath .= $selfs[$i]."/";

//  if(is_dir($triplepath."app/config/")) /* if version 1.7 or higher */
/* note that in config/bootstrap.php on line 88 all fields have "str_replace('%%', '%' " */
  if(file_exists($triplepath."app/config/parameters.php")) /* if version 1.7 or higher */
  { $config = require($triplepath."app/config/parameters.php");
    define('_DB_SERVER_', $config['parameters']['database_host']);
    define('_DB_NAME_', $config['parameters']['database_name']);
    define('_DB_USER_', $config['parameters']['database_user']);
    define('_DB_PASSWD_', str_replace('%%', '%', $config['parameters']['database_password']));
    define('_DB_PREFIX_',  $config['parameters']['database_prefix']);
    define('_COOKIE_KEY_',  $config['parameters']['cookie_key']);
	
    /* now get version */
    $data = file($triplepath."config/autoload.php");
    if(!$data) die("Error getting version number.");
    $version = "";
    foreach($data AS $line)
    { if(substr($line,0,22) == "define('_PS_VERSION_',")
	  { $subline = substr($line,22);
	    $version = preg_replace("/[\';\)\r\n ]*/", "", $subline);
	  }
    }
    if($version == "") die("Error analysing version number");
	if($version == "AppKernel::VERSION")
	{ $data = file($triplepath."app/AppKernel.php");
      if(!$data) die("Error getting version number.");
      $version = "";
      foreach($data AS $line)
      { $parts = explode("=",$line);
	    if((sizeof($parts) == 2) && (trim($parts[0]) == "const VERSION"))
		{ $tmp = trim($parts[1]);
		  $version = substr($tmp,1,strlen($tmp)-3);
		  break;
		}
      }
	}
	if($version == "Version::VERSION")  /* PS 8.1 */
	{ $vdata = file($triplepath."src/Core/Version.php");
      foreach($vdata AS $line)
      { $line = trim($line);
	    if(substr($line,0,22) == "public const VERSION =")
	    { $subline = substr($line,22);
	      $version = preg_replace("/[\';\r\n ]*/", "", $subline);
		}
	  }
	}
	if(version_compare($version, "9.9", ">=") || version_compare($version, "1.6", "<"))
	{ die("Automatic determination of the Prestashop version failed (".$version."). Please alert the Prestools maintainer.");
	}
	define('_PS_VERSION_',$version);
	if (_PS_VERSION_ >= "8.3")
	  die("Prestashop 8.3 is not yet supported by Prestools.");
  }
  else /* version 1.5/1.6 */
  { if(!@include $triplepath."config/settings.inc.php")
      die("Error loading 1.5/1.6 config file!");
    if (_PS_VERSION_ < "1.5.0")
	  die("This version of Prestools Suite is for Prestashop 1.5, 1.6 and 1.7!<p>There is a separate 1.4 version available.");
    if (version_compare(_PS_VERSION_, "1.5.0.10", "<"))
	  die("Prestools doesn't work with versions lower than 1.5.0.10.");
  }

  /* with mysqli_connect you cannot keep the socket in the first argument - 1and1 issue */
  if(substr(_DB_SERVER_,(strlen(_DB_SERVER_)-5)) == ".sock")
  { $parts = explode(":",_DB_SERVER_);
    $conn = mysqli_connect($parts[0], _DB_USER_, _DB_PASSWD_, _DB_NAME_, null, $parts[1]);
  }
  else
  { if (_PS_VERSION_ < "1.7")
    { $parts = explode(":",_DB_SERVER_);
	  $server = $parts[0];
	  if(isset($parts[1])) $port = $parts[1]; else $port = "";
	}
	else
	{ $server = $config['parameters']['database_host'];
	  $port = $config['parameters']['database_port'];
	}
    if($port != "")  /* port number specified? */
      $conn = mysqli_connect($server, _DB_USER_, _DB_PASSWD_, _DB_NAME_,$port);
    else
	{ $conn = mysqli_connect($server, _DB_USER_, _DB_PASSWD_, _DB_NAME_);
	}
  }
  if(!$conn)
  { echo "Error connecting to server ";
    if(substr(_DB_SERVER_,(strlen(_DB_SERVER_)-5)) == ".sock") echo "with socket ".$parts[1];
    else if((sizeof($parts)>1) && is_numeric($parts[1])) echo $server." with port ".$port;
	else echo $server;
	die("<br>Error: " . mysqli_connect_errno().": " . mysqli_connect_error());
  }

  $headermsgs = ""; /* will be printed together with menu */
  // mysqli_select_db($conn, _DB_NAME_) or die ("Error selecting database");
  //$res1 = mysqli_query($conn, "SET NAMES 'utf8'");
  $res = mysqli_set_charset($conn, "utf8");
  if(!$res) $headermsgs .= "Error setting charset...";
  /* the following line should prevent MySQL 5.7.5 (and higher) 'ONLY_FULL_GROUP_BY' errors */
  // See http://johnemb.blogspot.nl/2014/09/adding-or-removing-individual-sql-modes.html
//  $res4 = mysqli_query($conn, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))"); 
//  
  /* the following line should prevent MySQL 5.7.5 (and higher) 'STRICT_TRANS_TABLES' errors */
  // dbquery("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'STRICT_TRANS_TABLES',''))");
  $res4 = mysqli_query($conn, 'SET SESSION sql_mode = \'\'');  
  if(!$res4) $headermsgs .= "Error setting session mode";
  $query = "select * from "._DB_PREFIX_."configuration WHERE name='PS_ALLOW_ACCENTED_CHARS_URL'";
  $res5 = mysqli_query($conn, $query);
  if(!$res5) /* this is the first table access. Wrong prefix will show here */
  { $error = mysqli_error($conn);
    die("<p>MySQL error ".mysqli_errno($conn).": ".$error."<br>Generated by URL '".$_SERVER["PHP_SELF"]."'<br>with query '".$query."' <p>");  
  }
  if(mysqli_num_rows($res5) == 0)
    $allow_accented_chars = 0;
  else
  { $row = mysqli_fetch_assoc($res5);
    $allow_accented_chars = $row["value"];
  }
  
  /* get shop urls */
  $https = false;
  if(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
	  $https = true;
  $shoproots = [];
  $query = "SELECT * FROM "._DB_PREFIX_."shop_url";
  $res = mysqli_query($conn, $query);
  
  while($row = mysqli_fetch_assoc($res))
	if($https)
	  $shoproots[$row['id_shop']]= "https://".$row['domain_ssl'].$row['physical_uri'];
    else
	  $shoproots[$row['id_shop']]= "http://".$row['domain'].$row['physical_uri'];
}


function get_config_value($name)
{ global $conn;
  $query = "select value from "._DB_PREFIX_."configuration WHERE name='".mysqli_real_escape_string($conn,$name)."'";
  $res = mysqli_query($conn, $query);
  if(mysqli_num_rows($res) ==0)
	  return "";
  else
  { $row = mysqli_fetch_assoc($res);
    return $row["value"];
  }
}

function set_config_value($name, $value)
{ global $conn;
  $query = "select value from "._DB_PREFIX_."configuration WHERE name='".mysqli_real_escape_string($conn,$name)."'";
  $res = mysqli_query($conn, $query);
  if(mysqli_num_rows($res) ==0)
  { $squery = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."' 
AND TABLE_NAME='"._DB_PREFIX_."configuration'";
    $sres = mysqli_query($conn, $squery); 
	if(mysqli_num_rows($sres) == 0) 
    { echo "Configuration table is missing. ";
	}
    $srow = mysqli_fetch_array($sres);
    if(intval($srow['AUTO_INCREMENT']) == 0) /* the configuration file has no auto-increment */
	{ $query = "SELECT MAX(id_configuration) AS maxi FROM `"._DB_PREFIX_."configuration`";
	  $res = mysqli_query($conn, $query); 
	  if(!$res)
      { $full_error = "<p>MySQL error ".mysqli_errno($conn).": ".mysqli_error($conn)."<br>Generated by URL '".$_SERVER["PHP_SELF"]."'<br>with Query '".$query."' <p>";
		die($full_error);
	  }
	  list($maxi) = mysqli_fetch_row($res);
	  $query = "INSERT INTO "._DB_PREFIX_."configuration SET value='".mysqli_real_escape_string($conn,$value)."', 
	  name='".mysqli_real_escape_string($conn,$name)."', id_configuration=".($maxi+1);
	  $res = mysqli_query($conn, $query);
	}
	else
	{ $query = "INSERT INTO "._DB_PREFIX_."configuration SET value='".mysqli_real_escape_string($conn,$value)."', name='".mysqli_real_escape_string($conn,$name)."'";
	  $res = mysqli_query($conn, $query);
	}
  }	  
  else
  { $query = "UPDATE "._DB_PREFIX_."configuration SET value='".mysqli_real_escape_string($conn,$value)."' WHERE name='".mysqli_real_escape_string($conn,$name)."'";
    $res=mysqli_query($conn, $query);
  }
  if(!$res)
  { $error = mysqli_error($conn);
    $full_error = "<p>MySQL error ".mysqli_errno($conn).": ".$error."<br>Generated by URL '".$_SERVER["PHP_SELF"]."'<br>with Query '".$query."' <p>";
    if(mysqli_errno($conn) == 1062)
		echo "Most likely no auto-increment was set for the id of the configuration table.<br>";
	die($full_error);
  }
}

