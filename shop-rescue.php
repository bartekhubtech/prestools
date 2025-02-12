<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$id_lang = get_configuration_value('PS_LANG_DEFAULT');
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Shop Rescue</title>
<style>
.comment {background-color:#aabbcc}
table.spacer td {
	padding:12px;
	border: 1px solid #c3c3c3;
	border-collapse: collapse;
}

table.alterna tr:nth-of-type(4n+3) {
  background: #f5f5f5;
}

</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function RowSubmit(idx)
{ rowform.id_shop.value = eval("configform.id_shop"+idx+".value");
  rowform.id_shop_group.value = eval("configform.id_shop_group"+idx+".value");
  rowform.cvalue.value = eval("configform.cvalue"+idx+".value");
  rowform.fieldname.value = eval("configform.fieldname"+idx+".value");
  rowform.id_configuration.value = eval("configform.id_configuration"+idx+".value"); 
  rowform.verbose.value = configform.verbose.checked;  
  rowform.id_row.value = idx;
  document.rowform.submit();
}

function formprepare(formname)
{ var myform = eval(formname);
  if(formname == "calibrateform")
  { var idx = calibrateform.calibratedb.selectedIndex;
	if(idx == 0) return false;
	calibrateform.prefix.value = calibrateform.calibratedb.options[idx].dataset.prefix;
  }
  if(formname == "dbupgradeform")
  { if(dbupgradeform.newversion.value == "")
	  return false;
  }
  myform.verbose.value = configform.verbose.checked; 
  myform.submit();
  return false;
}

function dbcompareprepare()
{ if(dbcompareform.origdb.value == dbcompareform.targetdb.value)
  { alert("The source database should be dfferent from the target!");
    return false;
  }
  if(dbcompareform.origdb.value == "select a database")
  { alert("Select a source database!");
    return false;
  }
  if(dbcompareform.targetdb.value == "select a database")
  { alert("Select a target database!");
    return false;
  }
  dbcompareform.submit();
}

function prepcompare(elt)
{ if(elt.name == "compdb")
  { configcompform.compfile.value = "";
  }
  else if(elt.name == "compfile")
  {configcompform.compdb.selectedIndex = 0;
  }
}

function compareconfigs()
{ if((configcompform.compfile.value == "") && (configcompform.compdb.selectedIndex == 0))
  { alert("You must first select an (elsewhere exported) file or a database to compare your present shop with.");
    return false;
  }
  var mytab = document.getElementById('Maintable');
  configcompform.firstcolsize.value = "375";
  configcompform.windowsize.value = window.innerWidth;
  var idx = configcompform.compdb.selectedIndex;
  configcompform.prefix.value = configcompform.compdb.options[idx].dataset.prefix;
  configcompform.submit();
  return false;
}

function fillcachelocation(txt)
{ var location = document.getElementById("cachelocation");
  location.innerHTML = txt;
} 

function ipaddress2long()
{ var ip = document.getElementById("ipaddress");
  var long = document.getElementById("ipaddresslong");
  var parts = ip.value.split('.');
  var result = parseInt(parts[3]) + 256*parseInt(parts[2]) + 65536*parseInt(parts[1]) + 16777216*parseInt(parts[0]);
  long.value = result;
}

function long2ipaddress()
{ var ip = document.getElementById("ipaddress");
  var long = document.getElementById("ipaddresslong");
  var longv = long.value;
  var result = [longv >>> 24 & 0xFF, longv >>> 16 & 0xFF, longv >>> 8 & 0xFF, longv & 0xFF].join('.');
  ip.value = result;
}

</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93 strandbox="allow-same-origin allow-scripts allow-popups"></iframe></div>
<h1>Shop Rescue</h1>
This page provides a set of functions to help you when your shop is stuck behind a PHP or javascript error 
and have no longer access to your back office. The main section deals with some configuration flags. 
These are very powerful options - so take care to set them back to their original values when your 
problem is solved.<p>
<?php
   /* check that the physical_uri field in ps_shop_url refers to the actual path; if not there will be a redirect for which we must warn */
   $query = "SELECT su.*,s.active, s.name FROM "._DB_PREFIX_."shop_url su";
   $query .= " LEFT JOIN "._DB_PREFIX_."shop s ON s.id_shop = su.id_shop";
   $query .= " ORDER BY s.active DESC";
   $res=dbquery($query);
   $foundshop = false;
   if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != "off"))
   { $https = true;
   }
   else
   { $https = false;
   }
   $block = '';
   while ($row=mysqli_fetch_array($res))
   { if($https) $domain = $row["domain_ssl"]; else $domain = $row["domain"];
	 $block .= '
<br> &nbsp; &nbsp; &nbsp; &nbsp; Shop '.$row["id_shop"].': Domain=<b style="color:red;">'.$domain.
'</b> AND path=<b style="color:red;">'.$row["physical_uri"].'</b>';
	 if(($row["physical_uri"] == $shoppath) && ($_SERVER['SERVER_NAME'] = $domain))
	 { $foundshop = $row["id_shop"];
	   $foundshopname = $row["name"];
	   $active = $row["active"];
	 }
   }
   if(!$foundshop)
   { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	 echo '<h1>Warning</h1>';
	 echo 'The database redirects to a different url than where your webshop is now:<br>';
	 echo ' &nbsp; &nbsp; &nbsp; &nbsp; Present shop has domain=<b style="color:red;">'.$_SERVER['SERVER_NAME'].'</b> AND path=<b style="color:red;">'.$shoppath.'</b><br>';
	 echo 'The database contains the following shop urls:';
	 echo $block.'</div>
';
     $query = "SELECT id_shop, active, name FROM "._DB_PREFIX_."shop";
     $query .= " ORDER BY deleted, active DESC, id_shop LIMIT 1";
     $res=dbquery($query);
	 list($foundshop, $active, $foundshopname) = mysqli_fetch_row($res);
   }
   
   /* the exact minimum PHP versionfor later PS 1.7 versions can be found in the file /install/install_version.php
      in the constant _PS_INSTALL_MINIMUM_PHP_VERSION_
	*/
   if(version_compare(_PS_VERSION_ , "1.6.1", "<") || defined('_TB_VERSION_'))
   { 
   }
   else if(version_compare(_PS_VERSION_ , "1.7.0", "<"))
   { if ((version_compare(phpversion(), "5.2", "<")) || (version_compare(phpversion(), "7.2", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 5.2 and at most 7.1<p/></div>';
	 }
   }
   else if(version_compare(_PS_VERSION_ , "1.7.4", "<")) /* 1.7.3.x */
   { if ((version_compare(phpversion(), "5.4", "<")) || (version_compare(phpversion(), "7.2", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 5.4 and at most 7.1<p/></div>';
	 }
   }
   else if(version_compare(_PS_VERSION_ , "1.7.5", "<")) /* 1.7.4.x */
   { if ((version_compare(phpversion(), "5.6", "<")) || (version_compare(phpversion(), "7.2", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 5.6 and at most 7.1<p/></div>';
	 }
   }
   else if(version_compare(_PS_VERSION_ , "1.7.7", "<")) /* 1.7.5.x or 1.7.6.x */
   { if ((version_compare(phpversion(), "5.6", "<")) || (version_compare(phpversion(), "7.3", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 5.6 and at most 7.2<p/></div>';
	 }
   }
   else if(version_compare(_PS_VERSION_ , "1.7.8", "<")) /* 1.7.7.x */
   { if ((version_compare(phpversion(), "7.1.3", "<")) || (version_compare(phpversion(), "7.4", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 7.1.3 and at most 7.3<p/></div>';
	 }
   }
   else if(version_compare(_PS_VERSION_ , "1.7.9", "<")) /* 1.7.8.x */
   { if ((version_compare(phpversion(), "7.1", "<")) || (version_compare(phpversion(), "7.5", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 7.1 and at most 7.4<p/></div>';
	 }
   }
   else if ((version_compare(_PS_VERSION_, "8.0", ">=")) && (version_compare(_PS_VERSION_, "8.2", "<")))
   { if ((version_compare(phpversion(), "7.2", "<")) || (version_compare(phpversion(), "8.2", ">=")))
	 { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'You are running Prestashop version '._PS_VERSION_.' with PHP version '.phpversion();
	   echo '<br>The requirements for your Prestashop version are that the PHP version is at least 7.2 and at most 8.1<p/></div>';
	 }
   }
     
   if(function_exists('posix_getpwuid'))
   { $stat = stat($triplepath);
     $owner = posix_getpwuid($stat['uid']);
     if($owner["name"] == "root")
     { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	   echo '<h1>Warning</h1>';
	   echo 'The owner of shop\'s root directory is root instead of the usual www-data. This may cause problems.<p/></div>';
	 }
   }
   
 if (version_compare(_PS_VERSION_ , "1.5.0.1", ">=")) /* table is created in 1.5.0.1 */
 { $query = "SELECT count(*) AS xcount FROM "._DB_PREFIX_."cart_rule_combination";
   $res=dbquery($query);
   $row=mysqli_fetch_array($res);
   if(intval($row["xcount"]) > 1000000)
   { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	 echo '<h1>Warning</h1>';
	 echo 'You have a very big '._DB_PREFIX_.'cart_rule_combination table that could slow down your shop. 
	 If this is a deliberate choice it is ok. 
	 However, it may be related to <a href="https://github.com/PrestaShop/PrestaShop/issues/10003" target=_blank>this bug</a>. So you may consult that discussion.<p/></div>';
   }
 }
   
   
/* This value is not or not always updated
   $ps_version_db = get_configuration_value('PS_VERSION_DB');
   if(!defined('_TB_VERSION_') && ($ps_version_db != _PS_VERSION_))
   { echo '<div style="width:100%; min-height:60px; background-color:yellow; padding-left:50px; color:#666666;">';
	 echo '<h1>Warning</h1>';
	 echo 'The version of your Prestashop files ('._PS_VERSION_.') does not match with that stored in the database('.$ps_version_db.'). This may have been caused by a problematic upgrade.<p/></div>';
   }
*/  
   if (version_compare(_PS_VERSION_ , "1.7", ">="))
   { $tquery = "SELECT theme_name AS name FROM "._DB_PREFIX_."shop";
     $tquery .= " WHERE id_shop=".$foundshop;
     $tres=dbquery($tquery);
     $trow=mysqli_fetch_array($tres);
   }
   else
   { $tquery = "SELECT t.name FROM "._DB_PREFIX_."theme t";
	 $tquery .= " LEFT JOIN "._DB_PREFIX_."shop s ON t.id_theme=s.id_theme";
	 $tquery .= " WHERE id_shop=".$foundshop;
     $tres=dbquery($tquery);
     $trow=mysqli_fetch_array($tres);
   }
   
   echo 'You are now using database <b>'._DB_NAME_.'</b> for shop <b>'.$foundshopname.
   '</b> (<b>'.$_SERVER['SERVER_NAME'].$shoppath.'</b>; id_shop='.$foundshop.') with theme <b>'.$trow["name"].'</b>';
   if (version_compare(_PS_VERSION_ , "1.7", "<"))
   { if(file_exists($triplepath."themes/".$trow["name"]."/config.xml"))
	   $contents = file_get_contents($triplepath."themes/".$trow["name"]."/config.xml");
	 else if(file_exists($triplepath."themes/".$trow["name"]."/Config.xml"))
	   $contents = file_get_contents($triplepath."themes/".$trow["name"]."/Config.xml");
	 else echo " <b>No config.xml found</b>";
	 if(isset($contents))
     { $elts = new SimpleXMLElement($contents);
	   $array = json_decode(json_encode($elts), true);
	   if($elts->version['value'])
	     $version = $elts->version['value'];
	   else 
	     $version = $elts['version'];
	   echo " (version ".$version."; ";
	   echo $array['author']['@attributes']['name']."; ";
	   echo $array['author']['@attributes']['url'].")";
	 }
   }
   else
   { $contents = file($triplepath."themes/".$trow["name"]."/config/theme.yml");
     $parent = "";
     foreach($contents AS $content)
	 { $content = trim($content);
	   if(substr($content,0,7) == "parent:")
	   { $parent = trim(substr($content,7));
	   }
	 }
	 if($parent != "")
	 { $contents = file($triplepath."themes/".$parent."/config/theme.yml");
	 }
	 $version = "";
     foreach($contents AS $content)
	 { $content = trim($content);
	   if(substr($content,0,8) == "version:")
	   { $version = trim(substr($content,8));
	   }
	 }
	 if($parent != "")
	   echo " (childtheme of ".$parent." version ".$version.")";
	 else
	 { echo " (version ".$version;
	   for($i=0; $i<100; $i++)
	   { if(substr($contents[$i],0,7) == "author:")
		 { if(substr(trim($contents[$i+1]),0,5) == "name:")
			 echo "; ".substr(trim($contents[$i+1]),5);
		   if(substr(trim($contents[$i+3]),0,4) == "url:")
			 echo "; ".substr(trim($contents[$i+3]),4);
		 }
	   }
	   echo ")";
	 }
   }  
   echo '. ';
   if($active == 0) echo " Shop is <b>not active</b>.";
   $phpinilocation = php_ini_loaded_file();
   echo '<br>You php.ini is located at '.$phpinilocation.". ";
   $fp = fopen($phpinilocation,"r");
   if($fp)
   { while($row = fgets($fp))
	 { $row = trim($row);
	   if(substr($row, 0, 9) == "error_log")
	   { $eloc = trim(substr($row,10));
         if(strlen($eloc) > 2)
		   echo "Your error log is at ".substr($row,10);
	   }
	   if(substr($row, 0, 10) == "log_errors")
	   { $eloc = strtolower(trim(substr($row,11)));
         if(strpos($eloc,"off") !== false)
		   echo "Error logging is swichted off in your php.ini file. ";
	   }
	 } 
	 fclose($fp);
   }	   

$index = 0;
echo '<form name=configform><p>';
echo '<input type=checkbox name=verbose> verbose';
echo '<table class="triplemain alterna" style="margin-top:-15px">';
echo '<thead><tr style="background-color:#fff"><td colspan=6><b>Configuration flags</b></td></tr>';
echo '<tr><td colspan=6>At the top of this page you can set some configuration settings. 
By default only settings for all shops/shopgroups (NULL-NULL) are shown. 
More specific settings will only be shown when they are already in the database.
The default options are underlined.<br>
Lines with a yellow background are showstoppers for a functioning shop.
<p>The bottom half of this page offers some tools that can help fixing a faulty webshop.
</td></tr>';
echo '<tr style="background-color:#fff"><td>Field</td><td>shopgroup</td><td>shop</td><td>Value</td><td></td><td>Comment</td></tr></thead><tbody>';

  print_config_row("PS_SHOP_ENABLE","",1,"Enable shop vs maintenance mode");
  $index++;
  print_config_row("PS_REWRITING_SETTINGS","",1,"Friendly url");
  $index++;
  print_config_row("PS_SSL_ENABLED","",0,"Enable SSL");
  $index++;  
  print_config_row("PS_SSL_ENABLED_EVERYWHERE","",0,"SSL everywhere");
  $index++;  
  print_config_row("PS_ALLOW_ACCENTED_CHARS_URL","",0,"Accented url");
  $index++;
  print_config_row("PS_DISABLE_OVERRIDES","",0,"Disable all overrides");  
  $index++;
  print_config_row("PS_DISABLE_NON_NATIVE_MODULE","",0,"Disable non PrestaShop modules");  
  $index++;
  print_config_row("PS_CSS_THEME_CACHE","",0,"Smart cache for CSS");  
  $index++;
  print_config_row("PS_JS_THEME_CACHE","",0,"Smart cache for JavaScript");  
  $index++;
  print_config_row("PS_JS_DEFER","",0,"Move JavaScript to the end");  
  $index++;
  print_config_row("PS_HTML_THEME_COMPRESSION","",0,"Minify HTML");  
  $index++;
  print_config_row("PS_JS_HTML_THEME_COMPRESSION","",0,"Compress inline JavaScript in HTML");
  $index++;
  print_config_row("PS_HTACCESS_CACHE_CONTROL","",0,"Apache optimization"); 
  $index++;
  print_config_row("PS_SMARTY_CACHE","",1,"enable cache"); 
  $index++;  
  print_config_row("PS_SMARTY_CLEAR_CACHE",array("everytime"=>"Clear cache everytime something has been modified","never"=>"Never clear cache files"),"everytime",""); 
  $index++;  
  print_config_row("PS_SMARTY_FORCE_COMPILE",array("0"=>"Never recompile template files","1"=>"Recompile templates if the files have been updated","2"=>"Force compilation"),0,""); 
  $index++; 
  print_config_row("PS_HTACCESS_DISABLE_MULTIVIEWS","",0,"Disable Apache's MultiViews option");
  $index++;
  print_config_row("PS_HTACCESS_DISABLE_MODSEC","",0,"Disable Apache's mod_security module"); 
  $index++;
  print_config_row("PS_COOKIE_CHECKIP","",1,"Check the cookie's IP address"); 
  $index++;
  print_config_row("PRESTASTORE_LIVE","",1,"Automatically check for module updates"); 

  echo '</tbody></table></form>';
  
  echo '<p>';

  echo '<table class="spacer" style="width:100%">';
  
  echo '<tr><td>';
  echo '<form name=cacheflagsform action="shop-rescue-proc.php" method=post target=tank onsubmit=formprepare("cacheflagsform")>';
  echo '<b>Reset Cacheflags</b><br>';
  echo 'Prestashop maintains cache flags in the product tables that check whether a product has attachments and what its default attribute is.
  This function will check and correct those values.';
  echo '<input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return formprepare("cacheflagsform")\'>reset cacheflags </button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name=cookieform action="cleanup-proc.php" method=post target=tank onsubmit=formprepare("cookieform")>';
  echo '<b>Clear Prestashop Cookies</b><br>';
  echo 'Empty Prestashop cookies on this browser.';
  echo '<input type=hidden name="subject" value="clearPScookies" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return formprepare("cookieform")\'>clear Prestashop Cookies</button></td></tr>';
  
  echo '<tr><td>';
  echo '<b>Cookie Info</b><br>';
  echo 'Cookie Info shows all cookies of your domain and their content.</td>';
  echo '<td><a href="cookie-info.php" target=_blank><button>Cookie Info</button></a></td></tr>';
  
  echo '<tr><td>';
  echo '<form name=zerofileform action="shop-rescue-proc.php" method=post target=_blank onsubmit=formprepare("zerofileform")>';
  echo '<b>Look for zero length files</b><br>';
  echo 'A common source of trouble is file corruption. Many of those concern zero length files. Possible cause: ftp errors.';
  echo '<br><input type=checkbox name="includeimgs"> include image directory ';
  echo '<input type=hidden name="subject" value="zerolengthcheck" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=\'return formprepare("zerofileform")\'>check for zerolengths</button></td></tr>';
  
  echo '<tr><td>';
  echo '<b>Server settings</b><br>';
  echo 'Check the server settings: Are the recommended php and Apache modules loaded? Are the recommended php.ini settings 
  matched?';
  echo '</td><td>';
  echo '<form name=requireform action="server-settings.php" method=post target=_blank>';
  echo '<input type=hidden name=verbose>';
  echo '<input type=submit value="server settings"></form></td></tr>';
  /*
  echo '<tr><td><b>Database version check</b><br>';
  echo 'Some people use dubious upgrade methods that leave the database in some intermediate state.';
  echo 'This function helps to find out whether a shop has been abused in such a way.';
  echo '</td><td>';
  echo '<a href="dbversioncheck.php?dbname='._DB_NAME_.'" target=_blank style="background-color:#2c82c9; color:white; text-decoration:none; padding:5px;">db version check</a>';
  echo '</td></tr>';
  /*
  echo '<tr><td><b>Excess tables and fields</b><br>';
  echo 'This function shows you a list of the database tables that do not belong to Prestashop itself
  and thus likely belong to one of the modules. It searches the modules to determine to which one.<br>';
  echo 'This function is meant as a tool for old shops to find out which tables belong to deleted modules
  and thus might be deleted themselves too.<br>';
  echo '<b>At the moment the list of fields comes from PS 1.6. So under 1.7 you may see some normal fields listed as excess</b><br>';
  echo 'As this script does a text search on the disk it may take some time.<br>';
  echo 'Excess fields list looks for fields that are not present in any Prestashop version. So it will overlook fields that belong in newer versions.<br>';
  echo 'This tool is not perfect. Use your common sense too.';
  echo '</td><td>';
  echo '<a href="excess-tables.php" target=_blank style="color:white; text-decoration:none;"><button style="background-color:#2c82c9;">excess tables<br>and fields</button></a>';
  echo '</td></tr>';
*/
  echo '<tr><td>';
  echo '<form name="calibrateform" action="database-structure-compare.php" method=post target=_blank onsubmit=\'return formprepare("calibrateform")\'>';
  echo '<b>Database Structure Compare</b><br>';
  echo 'This script shows the differences between two databases. It compares the structure of the database under which you are working now with that of another that you can select below. The list mentions the version numbers between brackets.';
  echo '<br>This is most useful when you compare two databases of the same version. Consider comparing your shop with a fresh Prestashop installation of the same version.<br>';
  echo '<input type=hidden name=prefix value="">';
  echo '<input type=hidden name=verbose>';
  echo "Compare structure with <select name='calibratedb'><option>Select a database</option>";
  $squery = "SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%configuration_lang'";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if($srow["TABLE_SCHEMA"]==_DB_NAME_) continue;
	$prefix = str_replace("configuration_lang","",$srow["TABLE_NAME"]);

    // modules may have similarly named tables. So we test for another table with this prefix 
    $xres = dbquery("SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='".$prefix."product_supplier' AND TABLE_SCHEMA='".mescape($srow["TABLE_SCHEMA"])."'");
    if(mysqli_num_rows($xres) == 0) continue;
	
    $vquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'configuration WHERE name="PS_VERSION_DB"';
	$vres = dbquery($vquery); 
	$vrow=mysqli_fetch_array($vres);
	if($vrow["value"] == "1.6.1.999")
		$vrow["value"] = "Thirty Bees";
	echo "<option data-prefix='".$prefix."'>".$srow["TABLE_SCHEMA"]." (".$vrow["value"].")</option>";
  }
  echo "</select>";
  echo '</form></td><td>';
  if($numrows > 1)
	echo '<button onclick=\'return formprepare("calibrateform")\'>database structure<br> compare</button></td></tr>';

  echo '<tr><td> 
        <b>Config Info</b><br>';
  echo 'Config Info offers a versatile look at the settings in your configuration table.</td>';
  echo '<td><a href="config-info.php" target=_blank><button>Config Info</button></a></td>';
  echo '</tr>';
  
  echo '<tr><td><form name=configcompform action="config-compare.php" method=post target=_blank onsubmit="compareconfigs()">
        <b>Config compare</b><br>';
  echo 'Compare the configuration table of the present shop with the same table of another shop or an imported file:
  <table class=tripletight><tr><td>
  <select name="compdb" onchange="prepcompare(this)"><option>Select a database</option>';
  $squery = "SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%configuration_lang'";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if($srow["TABLE_SCHEMA"]==_DB_NAME_) continue;
	$prefix = str_replace("configuration_lang","",$srow["TABLE_NAME"]);

    // modules may have similarly named tables. So we test for another table with this prefix 
    $xres = dbquery("SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='".$prefix."product_supplier' AND TABLE_SCHEMA='".mescape($srow["TABLE_SCHEMA"])."'");
    if(mysqli_num_rows($xres) == 0) continue;
	
    $vquery = 'SELECT value FROM `'.$srow["TABLE_SCHEMA"].'`.'.$prefix.'configuration WHERE name="PS_VERSION_DB"';
	$vres = dbquery($vquery); 
	$vrow=mysqli_fetch_array($vres);
	if($vrow["value"] == "1.6.1.999")
		$vrow["value"] = "Thirty Bees";
	echo "<option data-prefix='".$prefix."'>".$srow["TABLE_SCHEMA"]." (".$vrow["value"].")</option>";
  }
  echo '</select></td><td>or</td><td><input type=hidden name="prefix">
  <input type=hidden name="firstcolsize"><input type=hidden name="windowsize">';
  echo '<input name="compfile" type=file accept=".csv" onchange="prepcompare(this)"></td></tr></table></td>';
  echo '<td><input type=submit value="Config compare"></form></td></tr>';

  
if(version_compare(_PS_VERSION_ , "1.7.6", ">="))
{ 
  echo '<tr><td>';
  echo '<b>Repair ps_currency_lang issues</b><br>';
  echo 'Prestashop introduced in version 1.7.6 a new table ps_currency_lang. In version 1.7.7 it added 
  a field "pattern" to this table. Many errors are related to this table not being correctly filled.
  This function checks and corrects that.';
  echo '</td><td>';
  echo '<form name=zerofileform action="shop-rescue-proc.php" method=post target=_blank>';
  echo '<input type=hidden name="subject" value="currencyrepair" ><input type=hidden name=verbose>';
  echo '<input type=submit value="Repair ps_currency_lang"></form></td></tr>';
  echo '</td></tr>';
} 
  
/*
  echo '<tr><td>';
  echo '<form name="dbcompareform" action="dbcompare.php" method=post target=_blank onsubmit=\'return dbcopyprepare()\'>';
  echo '<b>Compare the tables of two databases</b><br>';
  echo '<input type=hidden name=verbose>';
  echo '<input type=hidden name="subject" value="dbcopy">';
  echo "Compare from <select name='origdb'><option>Select a database</option>";
  $squery = "SHOW DATABASES";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if(in_array(strtolower($srow[0]), ["information_schema","mysql","phpmyadmin","performance_schema"])) continue;
    echo "<option>".$srow[0]."</option>";
  }
  echo "</select>";
  echo " to <select name='targetdb'><option>Select a database</option>";
  $squery = "SHOW DATABASES";
  $sres = dbquery($squery); 
  $numrows = mysqli_num_rows($sres);
  while ($srow=mysqli_fetch_array($sres))   
  { if(in_array(strtolower($srow[0]), ["information_schema","mysql","phpmyadmin","performance_schema"])) continue;
    echo "<option>".$srow[0]."</option>";
  }
  echo "</select><br>";
  echo '</form></td><td>';
  echo '<button onclick="return dbcompareprepare()">Compare<br>Databases</button>';
  echo '</td></tr>';
*/
  
  echo '<tr><td>';
  echo '<b>File list export</b><br>';
  echo 'On the <a href="export.php">Export page</a> you will find a tool to export a file list so that you can compare the files of two shops.</td><td>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<b>Restore the database and files from Prestashop\'s 1-click upgrade backup</b><br>';
  echo 'After a failed upgrade it is often impossible to restore the backup that Prestashop made during the upgrade process. This function takes the backup files in which a copy of the database was stored and restores it to an empty database that you indicate.';
  echo ' There is also an option to just decompress the .bz2 files.';
  echo '</td><td>';
  echo '<a href="db-restore.php" target=_blank style="background-color:#2c82c9; color:white; text-decoration:none; padding:5px;">restore database</a>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<b>Auto-increment info</b><br>';
  echo 'Information about the auto-increment values. Out of range id\'s are highlighted.
    An optional advanced function does integrity checks for all auto-increment variables. </td><td>';
  echo '<a href="autoincrement-info.php" target=_blank style="background-color:#2c82c9; color:white; text-decoration:none; padding:5px;">Auto-increment info</a>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<b>Unique Maker</b><br>';
  echo 'In old shops sometimes some unique indexes have been deleted and re-imposing 
  them is impossible because doing so will produce a duplicate key error. This function
  allows you to see which values are double and to delete those doubles.';
  echo '</td><td>';
  echo '<a href="uniquemaker.php" target=_blank style="background-color:#2c82c9; color:white; text-decoration:none; padding:5px;">Unique Maker</a>';
  echo '</td></tr>';
  
if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
{ echo '<tr><td>';
  echo '<form name=cachesearchform action="shop-rescue-proc.php" method=post target=tank onclick=formprepare("cachesearchform")>';
  echo '<b>Cache searcher</b><br>';
  echo 'Prestashop 1.7 sometimes shows a cache filename without mentioning in which directory it is located.
  This function seaches them for you.<br>
  File name: <input name=cachefile size=80><br>
  <span id=cachelocation></span>';
  echo '<input type=hidden name="subject" value="cachesearch" ><input type=hidden name=verbose>';
  echo '</td><td>';
  echo '<button onclick=formprepare("cachesearchform")>Search cache</button></form></td></tr>';
  echo '</td></tr>';
}
  
  echo '<tr><td>';
  echo '<form name=errorlogform action="export-proc.php" method=post target=_blank enctype="multipart/form-data" onsubmit=formprepare("errorlogform")>';
  echo '<b>Filter PHP error log</b><br>';
  echo 'PHP error logs often contain the same error many times. ';
  echo 'This function processes your error log outputs a file where each error message is listed once - with the last datetime when it happened. Note that when your error file is very big you may experience problems with either a timeout or the upload size limit setting of your server. Optional you can put the last first and you can sort the messages by process (module, class or controller). Sorting by process is always in reverse order. The pid and client tags are usually useless but they prevent showing each message once. When there are several referring pages it can be useful to remove the referrer for the same reason.';
  echo '<br>Select the errorlog: <input type=file name="logfile">';
  echo ' &nbsp; &nbsp; <input type=checkbox name=reversed checked> reverse order';
  echo ' &nbsp; &nbsp; <input type=checkbox name=sorted> sorted by process';
  echo ' &nbsp; &nbsp; <input type=checkbox name=removerefer> remove referrer';
  echo ' &nbsp; &nbsp; <input type=checkbox name=removetags checked> remove pid and client tags';
  echo '<input type=hidden name="task" value="errorlog" ><input type=hidden name=verbose>';
  echo '</form></td><td>';
  echo '<button onclick=formprepare("errorlogform")>Filter PHP<br>error log</button></td></tr>';
  
  echo '<tr><td>';
  echo '<form name="cartcarrierform" target=_blank action="cartcarriers.php">';
  echo '<b>Which Carriers belong to this cart?</b><br>';
  echo '<br>This function helps analyze why a customer sees certain carriers and others not.';
  echo '<br>Cart id <input size=2 name=cart_id>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Get carrier settings for cart"></form>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<form name="downloadform" target=_blank action="downloadpage.php">';
  echo '<b>Download page</b><br>';
  echo '<br>This function will download a page from your shop. So in the case of redirects you will get the original. Also when http messages such as redirects are returned you will this message. This is experimental. I am open for suggestions for improvement.';
  echo '<br>Full url (including http(s)) <input size=80 name=fullurl>';
  echo '<br><input type=radio name=openmode value=fsock> fsockopen &nbsp; ';
  echo '<input type=radio name=openmode value=getcontents> file_get_contents &nbsp; ';
  echo '<input type=radio name=openmode value=fopen> fopen';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Download page"></form>';
  echo '</td></tr>';
    
  echo '</table>';
  
  echo '<p><center><b>Translate IP addresses</b><br>';
  echo 'The database stores IP addresses in the connections table as long integers.<br>';
  echo 'IP address <input size=12 id="ipaddress"> &nbsp; <img src="add.gif" onclick="ipaddress2long()" style="vertical-align:middle">';
  echo ' &nbsp; <img src="remove.gif" onclick="long2ipaddress()" style="vertical-align:middle"> &nbsp; <input size=12 id="ipaddresslong"> long';
  
  


  /* rowform is used for setting the configuration flags */
  echo '<div style="display:block;"><form name=rowform action="shop-rescue-proc.php" method=post target=tank>
  <table id=subtable></table>';
  echo '<input type=hidden name=fieldname><input type=hidden name=id_shop><input type=hidden name=id_shop_group>';
  echo '<input type=hidden name=cvalue><input type=hidden name=id_configuration>'; 
  echo '<input type=hidden name="subject" value="configuration">';
  echo '<input type=hidden name=id_row><input type=hidden name=verbose>';
  echo '</form></div><p>';
  
  include "footer1.php";	  
  echo '</body></html>';

function print_options($index, $options, $default, $value)
{ if($options == "") /* no options => no/yes */
    $options = array("No","Yes");
  if(isset($options[$default]))
    $options[$default] = "<u>".$options[$default]."</u>"; /* default should be underlined */
  if((sizeof($options)>2) || (strlen(implode(",", $options)) > 17))  /* note that underline code takes 7 positions */
  { $tmp = "";
    $i = 0;
    foreach($options AS $key => $option)
	{ if($i>0) $tmp .= "<br>";
	  $tmp .= '<input type="radio" name="cvalue'.$index.'" value='.$key;
      if($value == $key) $tmp .= " checked";
	  $tmp .= '> '.$option;
	  $i++;
	}
  }
  else
  { $tmp = $options[0].' <input type="radio" name="cvalue'.$index.'" value=0';
    if($value == "0") $tmp .= " checked";
    $tmp .= '> &nbsp; &nbsp; <input type="radio" name="cvalue'.$index.'" value=1';
    if($value == "1") $tmp .= " checked";
	$tmp .= '> '.$options[1];
  }
  return $tmp;
}

function print_config_row($configfield, $options, $default, $comment)
{ global $index;
  $cquery="select id_configuration,id_shop_group,id_shop,value FROM ". _DB_PREFIX_."configuration";
  $cquery .= " WHERE name='".$configfield."' ORDER BY id_shop_group,id_shop";
  $cres=dbquery($cquery);
  $nnfound = false;
  if(mysqli_num_rows($cres) > 0)
  { $crow = mysqli_fetch_array($cres);
	if (($crow["id_shop_group"] == NULL) && ($crow["id_shop"] == NULL))
		$nnfound = true;
	else
		mysqli_data_seek($cres, 0);
  }
  $bgcolor = "";
  if($nnfound && ((($configfield == "PS_SHOP_ENABLE") && ($crow["value"]==0))
	|| (($configfield == "PS_DISABLE_OVERRIDES") && ($crow["value"]==1))
	|| (($configfield == "PS_SMARTY_FORCE_COMPILE") && ($crow["value"]!=1))
	|| (($configfield == "PS_DISABLE_NON_NATIVE_MODULE") && ($crow["value"]==1))))
    $bgcolor = 'style="background-color: #FFFF11"';
  
  echo '<tr '.$bgcolor.'><td>'.$configfield.'</td><td>NULL</td><td>NULL</td>';
  if($nnfound)
  { echo '<td>'.print_options($index, $options, $default, $crow["value"]);
    echo '<input type=hidden name="id_configuration'.$index.'" value="'.$crow["id_configuration"].'"></td>';
  }
  else
  { echo '<td>'.print_options($index, $options, $default, "0");
    echo '<input type=hidden name="id_configuration'.$index.'" value="0"></td>';
  }
  echo '<td><input type=hidden name="fieldname'.$index.'" value="'.$configfield.'">';
  echo '<input type=hidden name="id_shop_group'.$index.'" value="NULL"><input type=hidden name="id_shop'.$index.'" value="NULL">';

  echo '<img src="enter.png" title="submit row '.$index.'" onclick="RowSubmit('.$index.')"></td><td>'.$comment.'</td></tr>';
  $lastshop = $lastshopgroup = "NULL";
  while($crow = mysqli_fetch_array($cres))
  { if(($crow["id_shop"] == $lastshop) && ($crow["id_shop_group"] == $lastshopgroup))
	{ /* prevent doubles. */
	  dbquery("DELETE from ". _DB_PREFIX_."configuration WHERE id_configuration='".$crow["id_configuration"]."'");
	  continue;
	}
	$index++;
    $lastshop = $crow["id_shop"];
    $lastshopgroup = $crow["id_shop_group"];	
	echo '<tr><td>'.$configfield.'</td><td>'.$crow["id_shop_group"].'</td><td>'.$crow["id_shop"].'</td><td>'.print_options($index, $options, $default, $crow["value"]).'</td>';
    echo '<td><input type=hidden name="fieldname'.$index.'" value="'.$configfield.'">';
    echo '<input type=hidden name="id_configuration'.$index.'" value="'.$crow["id_configuration"].'">';
    echo '<input type=hidden name="id_shop_group'.$index.'" value="'.$crow["id_shop_group"].'"><input type=hidden name="id_shop'.$index.'" value="'.$crow["id_shop"].'">';
    echo '<img src="enter.png" title="submit row '.$index.'" onclick="RowSubmit('.$index.')"></td></tr>';
  }
}

