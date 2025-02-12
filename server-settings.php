<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
$base_uri = get_base_uri();

/* get language iso_code */
$query = "select iso_code from ". _DB_PREFIX_."configuration c";
$query .= " LEFT JOIN ". _DB_PREFIX_."lang l ON c.value=l.id_lang";
$query .= " WHERE c.name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$iso_code = $row['iso_code'];

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Server requirements for Prestashop and Thirty Bees</title>
<style>
option.defcat {background-color: #ff2222;}
td.colored {background-color: #ff9999;}
td.lcolored {background-color: #ffdddd;}
</style>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>

<body>
<?php
print_menubar();
echo '<center><b><font size="+1">Server requirements for Prestashop and Thirty Bees</font></b></center>';
echo "This page provides an easy overview to see whether the modules and settings of your server are adequate for your webshop.";
echo "<br>Note that even if a module is loaded its settings may be wrong.";
echo "<br>Not following the requirements will not always give problems. Many settings are only needed for a small part of the functions.";

/* the array has three fields: ps1.7 - PS 1.6 - TB */
/* value options are: 0=not applicable; 1=recommended; 2 required */
$reqvalues = array(0=>"", 1=>"recommended",2=>"required");
$reqmodules = array( /* ps1.7 - PS 1.6 - TB - comment */
"bcmath" => array(1,0,2,''),
"curl" => array(2,2,1,''),
"dom" => array(2,2,2,''),
"fileinfo" => array(2,0,0,''),
"gd" => array(2,2,1,''),
"iconv" => array(2,0,0,'for converting character sets'),
"imagick" => array(1,1,1,''),
"imap" => array(0,0,1,''),
"intl" => array(2,0,0,''),
"json" => array(2,0,2,''),
"mbstring" => array(2,0,1,''),
"mcrypt" => array(0,2,0,'Only for older PS versions(<1.6.1.15?)'),
"opcache" => array(0,2,1,''),
"openssl" => array(2,2,0,'Openssl is built in Ubuntu - not a separate module'),
"pdo_mysql" => array(2,2,2,''),
"simpleXML" => array(2,2,2,'the file is just named xml'),
"SOAP" => array(2,2,1,''),
"zip" => array(2,2,2,''),
);

echo '<table class="triplemain"><tr><td colspan=6 align="center">Required PHP Modules</td></tr>';
echo '<tr><td></td><td>You</td><td>PS 8/1.7</td><td>PS 1.6</td><td>30bees</td><td></td></tr>';
foreach($reqmodules AS $key => $reqs)
{   if($key == "opcache") /* although this module is installed as opcache its name as php module is different, probably "Zendcache". check phpinfo() */
	{ $flag = "&#x2716";
	  if(function_exists('opcache_get_status'))
	  { $opcstat = opcache_get_status();
        if($opcstat['opcache_enabled'] == "1")
		  $flag = "&#x2714";
	    else
		  $flag = "inactive";	
	  }
	}
	else if(class_exists("curl"))
	{ if(extension_loaded($key))
  	    $flag = "&#x2714";
      else
  	    $flag = "&#x2716";	
	}
	else if(($key == "mcrypt") && (version_compare(_PS_VERSION_ , "1.6.0.17", ">")))
		continue;
	else if($key == "imagick") 
	{ if(class_exists("Imagick"))
      { $imformats = Imagick::queryformats(); /* if ImageMagick is not correctly installed this returns zero formats */
        if(sizeof($imformats)==0) 
			$reqs[3] = "Fails: No connection with ImageMagick";
		$flag = "&#x2714";		
	  }
	  else
  	    $flag = "&#x2716";
	}
	else
	{ if(extension_loaded($key))
  	    $flag = "&#x2714";
      else
  	    $flag = "&#x2716";	
	}	
	$bg0 = $bg1 = $bg2 = "";
	if($flag != "&#x2714")
	{ if($reqs[0]==2) $bg0 = 'class="colored";'; /* if required */
	  if($reqs[1]==2) $bg1 = 'class="colored";';  /* if required */
	  if($reqs[2]==2) $bg2 = 'class="colored";';  /* if required */
	}
    echo '<tr><td>'.$key.'</td><td>'.$flag.'</td><td '.$bg0.'>'.$reqvalues[$reqs[0]].'</td><td '.$bg1.'>'.$reqvalues[$reqs[1]].'</td>';
	echo '<td '.$bg2.'>'.$reqvalues[$reqs[2]].'</td><td>'.$reqs[3].'</td></tr>';
}
echo '</table><p>';

/* now the Apache modules */
/* note that these cannot be retrieved with CGI */
$apvalues = array(0=>"",1=>"recommended",2=>"required",3=>"rather not",4=>"forbidden");
$apmodules = array( /* ps8 - ps1.7 - PS 1.6 - TB */
"mod_auth_basic" => array(0,3,3,4,''),
"mod_rewrite" => array(1,1,1,2,''),
"mod_env" => array(1,0,0,0,''),
"mod_alias" => array(1,0,0,0,''),
"mod_headers" => array(1,0,0,0,''),
"mod_security" => array(3,3,3,4,''));

echo '<table class="triplemain"><tr><td colspan=6 align="center">Apache Modules</td></tr>';
echo '<tr><td></td><td>You</td><td>PS 8</td><td>PS 1.7</td><td>PS 1.6</td><td>30bees</td></tr>';
if(function_exists('apache_get_modules'))
  $loadedmodules = apache_get_modules();
else 
  $loadedmodules = array();
if(sizeof($loadedmodules)>0)
{ foreach($apmodules AS $key => $reqs)
  { $bg0 = $bg1 = $bg2 = $bg3 = "";
    if(in_array($key,$loadedmodules))
    { $flag = "loaded";
	  if($reqs[0]==4) $bg0 = 'class="colored";'; /* if required */
	  if($reqs[1]==4) $bg1 = 'class="colored";';  /* if required */
	  if($reqs[2]==4) $bg2 = 'class="colored";';  /* if required */
	  if($reqs[3]==4) $bg3 = 'class="colored";';  /* if required */
	  if($reqs[0]==3) $bg0 = 'class="lcolored";'; /* if required */
	  if($reqs[1]==3) $bg1 = 'class="lcolored";';  /* if required */
	  if($reqs[2]==3) $bg2 = 'class="lcolored";';  /* if required */
	  if($reqs[3]==3) $bg3 = 'class="lcolored";';  /* if required */
    }
    else
    { $flag = "not loaded";
	  if($reqs[0]==2) $bg0 = 'class="colored";'; /* if required */
	  if($reqs[1]==2) $bg1 = 'class="colored";';  /* if required */
	  if($reqs[2]==2) $bg2 = 'class="colored";';  /* if required */
	  if($reqs[3]==2) $bg3 = 'class="colored";';  /* if required */
    }
    echo '<tr><td>'.$key.'</td><td>'.$flag.'</td><td '.$bg0.'>'.$apvalues[$reqs[0]].'</td><td '.$bg1.'>'.$apvalues[$reqs[1]].'</td>';
    echo '<td '.$bg2.'>'.$apvalues[$reqs[2]].'</td><td '.$bg3.'>'.$apvalues[$reqs[3]].'</td><td>'.$reqs[4].'</td></tr>';
  }
}
else
{ $bg0 = $bg1 = $bg2 = "";
  if ((isset($_SERVER['HTTP_MOD_REWRITE']) AND ($_SERVER['HTTP_MOD_REWRITE'] == 'On')) || isset($_SERVER['IIS_UrlRewriteModule']))
  { $flag = "loaded";
  }
  else
  { $flag = "???";
  }
  echo '<tr><td>mod_rewrite</td><td>'.$flag.'</td><td '.$bg0.'>recommended</td><td '.$bg1.'>recommended</td>';
  echo '<td '.$bg2.'>required</td><td></td></tr>';
  echo '<tr><td colspan=5>You are running PHP with CGI, so you will need to run phpinfo() to find out</td></tr>';
}  
echo '</table><p>';


/* now the PHP.INI settings */
$inilist = array( /* ps8 - ps1.7 - PS 1.6 - TB */
"allow_url_fopen" => array("On","On","On","On",''),
"allow_url_include" => array("","","","Off",''),
"expose_php" => array("","","","Off",''),
"max_input_vars" => array("","","","10000",''),
"post_max_size" => array("","","","32M",''),
"realpath_cache_size" => array("5M","5M","","",'Recommended for Windows'),
"register_globals" => array("Off","Off","Off","",''),
"safe_mode" => array("","","Off","",''),
"upload_max_filesize" => array(">=16M",">=16M",">=16M",">=16M",''),
"xdebug.max_nesting_level" => array("256","256","","",'When you use Xdebug'));

echo '<table class="triplemain"><tr><td colspan=6 align="center">Recommended PHP.INI Settings</td></tr>';
echo '<tr><td></td><td>You</td><td>PS 8</td><td>PS 1.7</td><td>PS 1.6</td><td>30bees</td></tr>';
foreach($inilist AS $key => $reqs)
{ $bg0 = $bg1 = $bg2 = $bg3 = "";
  $val = ini_get($key);
  if($key=="max_input_vars")
  { if($reqs[0] > $val) $bg0 = 'class="colored";'; /* if required */
	if($reqs[1] > $val) $bg1 = 'class="colored";';  /* if required */
	if($reqs[2] > $val) $bg2 = 'class="colored";';  /* if required */
	if($reqs[3] > $val) $bg3 = 'class="colored";';  /* if required */
	if(($reqs[0]=="") && ($val < 5000)) $bg0 = 'class="lcolored";'; /* if required */
	if(($reqs[1]=="") && ($val < 5000)) $bg1 = 'class="lcolored";';  /* if required */
	if(($reqs[2]=="") && ($val < 5000)) $bg2 = 'class="lcolored";';  /* if required */
	if(($reqs[3]=="") && ($val < 5000)) $bg3 = 'class="lcolored";';  /* if required */
  }
  if(($key=="post_max_size") && (strpos($val,"M") > 0))
  { $valx = str_replace("M","",$val);
    if(str_replace("M","",$reqs[0]) > $valx) $bg0 = 'class="colored";'; /* if required */
	if(str_replace("M","",$reqs[1]) > $valx) $bg1 = 'class="colored";';  /* if required */
	if(str_replace("M","",$reqs[2]) > $valx) $bg2 = 'class="colored";';  /* if required */
	if(str_replace("M","",$reqs[3]) > $valx) $bg3 = 'class="colored";';  /* if required */
	if(($reqs[0]=="") && ($valx < 32)) $bg0 = 'class="lcolored";'; /* if required */
	if(($reqs[1]=="") && ($valx < 32)) $bg1 = 'class="lcolored";';  /* if required */
	if(($reqs[2]=="") && ($valx < 32)) $bg2 = 'class="lcolored";';  /* if required */
	if(($reqs[3]=="") && ($valx < 32)) $bg3 = 'class="lcolored";';  /* if required */
  }
  if(($key=="upload_max_filesize") && (strpos($val,"M") > 0))
  { $valx = str_replace("M","",$val);
    if(preg_replace("/[^0-9]+/","",$reqs[0]) > $valx) $bg0 = 'class="colored";'; /* if required */
	if(preg_replace("/[^0-9]+/","",$reqs[1]) > $valx) $bg1 = 'class="colored";';  /* if required */
	if(preg_replace("/[^0-9]+/","",$reqs[2]) > $valx) $bg2 = 'class="colored";';  /* if required */
	if(preg_replace("/[^0-9]+/","",$reqs[3]) > $valx) $bg3 = 'class="colored";';  /* if required */
	if(($reqs[0]=="") && ($valx < 16)) $bg0 = 'class="lcolored";'; /* if required */
	if(($reqs[1]=="") && ($valx < 16)) $bg1 = 'class="lcolored";';  /* if required */
	if(($reqs[2]=="") && ($valx < 16)) $bg2 = 'class="lcolored";';  /* if required */
	if(($reqs[3]=="") && ($valx < 16)) $bg3 = 'class="lcolored";';  /* if required */
  }
  echo '<tr><td>'.$key.'</td><td>'.$val.'</td><td '.$bg0.'>'.$reqs[0].'</td><td '.$bg1.'>'.$reqs[1].'</td>';
  echo '<td '.$bg2.'>'.$reqs[2].'</td><td '.$bg3.'>'.$reqs[3].'</td><td>'.$reqs[4].'</td></tr>';
}
echo '</table>';
echo '&nbsp; &nbsp; &nbsp; <i>You php.ini is located at '.php_ini_loaded_file()."</i>";
echo '<p>';

$ch = curl_init('https://www.howsmyssl.com/a/check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); /* can be used if everything else fails */
$data = curl_exec($ch);
curl_close($ch);
$json = json_decode($data);
echo "Your SSL/TLS max supported version is ".$json->tls_version."<p>";

//phpinfo();
  include "footer1.php";
echo '</body></html>';