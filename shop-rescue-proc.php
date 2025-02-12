<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";

if(isset($_POST['id_lang']))
  $id_lang = strval(intval($_POST['id_lang']));

$errstring = "";

echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<style>
span.lineblock 
{ display: block;
}

span.linehide
{ display: none;
}
</style>
<script>
function newwin()
{ nwin = window.open("","NewWindow", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}

function dispchange()
{ var values = [];
  var keuzes = kiesform.kiezer;
  for (var i=0, len=keuzes.length; i<len; i++) 
  { if (keuzes[i].checked) 
    { values.push(keuzes[i].value);
    }
  }
  var spans = document.getElementsByTagName("SPAN");
  for (var i=0, len=spans.length; i<len; i++) 
  { var spantype = spans[i].dataset.type;
    
    if(values.indexOf(spantype) == -1)
	  spans[i].className = "linehide";
	else 
	  spans[i].className = "lineblock";
  }
}
</script></head><body>';
   echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';

 if($prestools_settings["demo_mode"])
   echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
 else if(isset($_POST['subject']) && ($_POST['subject'] == "configuration"))
 { $id_shop = intval($_POST['id_shop']); 
   if($id_shop == 0) $id_shop = "NULL";
   $id_shop_group = intval($_POST['id_shop_group']);
   if($id_shop_group == 0) $id_shop_group = "NULL"; 
   $allowed_fieldnames = array("PS_SHOP_ENABLE","PS_REWRITING_SETTINGS","PS_ALLOW_ACCENTED_CHARS_URL","PS_DISABLE_OVERRIDES",
     "PS_SSL_ENABLED","PS_SSL_ENABLED_EVERYWHERE",
	 "PS_DISABLE_NON_NATIVE_MODULE","PS_CSS_THEME_CACHE","PS_JS_THEME_CACHE","PS_JS_DEFER","PS_HTML_THEME_COMPRESSION",
     "PS_JS_HTML_THEME_COMPRESSION","PS_HTACCESS_CACHE_CONTROL","PS_SMARTY_CACHE","PS_SMARTY_CLEAR_CACHE","PS_SMARTY_FORCE_COMPILE",
     "PS_HTACCESS_DISABLE_MULTIVIEWS","PS_HTACCESS_DISABLE_MODSEC","PS_COOKIE_CHECKIP","PRESTASTORE_LIVE");
   if(!in_array($_POST['fieldname'], $allowed_fieldnames))
	   colordie("Illegal fieldname ".$_POST['fieldname']);
   $cvalue = mysqli_real_escape_string($conn, $_POST['cvalue']);
   $fieldname = mysqli_real_escape_string($conn, $_POST['fieldname']); 
   $id_configuration = intval($_POST['id_configuration']);   
   if($id_configuration == "0")
   { /* the user may press enter more than once without refreshing the shop_rescue page */
	 /* the second time there will be a value and we need to update it */
     $gquery = "SELECT id_configuration FROM ". _DB_PREFIX_."configuration";
     $gquery .= ' WHERE name="'.$fieldname.'" ORDER BY id_shop_group,id_shop';
     $gres = dbquery($gquery);
	 if(mysqli_num_rows($gres)> 0)
	 { $grow = mysqli_fetch_array($gres);
	   $id_configuration = $grow["id_configuration"];
	 }
	 else
	 { $cquery="INSERT INTO ". _DB_PREFIX_."configuration";
       $cquery .= ' SET id_shop=NULL, id_shop_group=NULL, name="'.$fieldname.'", value="'.$cvalue.'", date_add=NOW(), date_upd=NOW()';
	 }
   }
   if($id_configuration != "0")
   { $cquery="UPDATE ". _DB_PREFIX_."configuration";
     $cquery .= ' SET value="'.$cvalue.'", date_upd=NOW() WHERE id_configuration='.$id_configuration;
   }
   dbquery($cquery);
   if(($fieldname == "PS_DISABLE_OVERRIDES") && ($cvalue=="1")) /* the class index should be rebuilt to exclude the overrides */
	 del_class_index();
   echo "<script>parent.change_rec('".$_POST['id_row']."');</script>Finished";
 }
 else if(isset($_POST['subject']) && ($_POST['subject'] == "resetcacheflags"))
 { $fquery = "SELECT id_product FROM ". _DB_PREFIX_."product p";
   $fquery .= ' WHERE p.cache_has_attachments=1 ';
   $fquery .= ' AND id_product NOT IN (SELECT DISTINCT id_product FROM '. _DB_PREFIX_.'product_attachment)';
   $fres = dbquery($fquery);
   if(mysqli_num_rows($fres)>0)
     echo "Products with attachement cacheflags set to 0:";
   while($frow = mysqli_fetch_array($fres))
   { $query = "UPDATE "._DB_PREFIX_."product SET cache_has_attachments='0' WHERE id_product='".$frow["id_product"]."'";
	 $res = dbquery($query); 
	 echo $frow["id_product"].", ";
   }
   $fquery = "SELECT id_product FROM ". _DB_PREFIX_."product p";
   $fquery .= ' WHERE p.cache_has_attachments=0 ';
   $fquery .= ' AND id_product IN (SELECT DISTINCT id_product FROM '. _DB_PREFIX_.'product_attachment)';
   $fres = dbquery($fquery);
   if(mysqli_num_rows($fres)>0)
     echo "Products with attachement cacheflags set to 1:";
   while($frow = mysqli_fetch_array($fres))
   { $query = "UPDATE "._DB_PREFIX_."product SET cache_has_attachments='1' WHERE id_product='".$frow["id_product"]."'";
	 $res = dbquery($query); 
	 echo $frow["id_product"].", ";
   } 
   /* now handle the cache_default_attribute: first check for products without attribute that have a value for this */
   $squery = "SELECT id_shop FROM ". _DB_PREFIX_."shop";
   $squery .= ' WHERE deleted=0 AND active=1';
   $sres = dbquery($squery);
   $shops = array();
   while($srow = mysqli_fetch_array($sres))  
      $shops[] = $srow["id_shop"];
   foreach($shops AS $shop)
   { $fquery = "SELECT id_product FROM ". _DB_PREFIX_."product_shop p";
     $fquery .= " WHERE p.cache_default_attribute!=0 AND id_shop='".$shop."'";
     $fquery .= " AND id_product NOT IN (SELECT DISTINCT id_product FROM ". _DB_PREFIX_."product_attribute WHERE id_shop='".$shop."')";
     $fres = dbquery($fquery);
     if(mysqli_num_rows($fres)>0)
       echo "Products with default attribute cacheflags set to 0:";
     while($frow = mysqli_fetch_array($fres))
     { $query = "UPDATE "._DB_PREFIX_."product_shop SET cache_default_attribute='0' WHERE id_product='".$frow["id_product"]."' AND id_shop='".$shop."'";
	   $res = dbquery($query); 
	   echo $frow["id_product"].", ";
	   if($shop == "1")
	   { $query = "UPDATE "._DB_PREFIX_."product SET cache_default_attribute='0' WHERE id_product='".$frow["id_product"]."'";
	     $res = dbquery($query); 
	   }
	 }
   }
   /* cache_default_attribute part 2: check that same default is in cache as in ps_product_attribute table */
   foreach($shops AS $shop)
   { $fquery = "SELECT id_product,cache_default_attribute FROM ". _DB_PREFIX_."product_shop";
     $fquery .= " WHERE cache_default_attribute!=0 AND id_shop='".$shop."'";
     $fres = dbquery($fquery);
     while($frow = mysqli_fetch_array($fres))
     { $query = "SELECT pas.id_product_attribute, pas.default_on FROM "._DB_PREFIX_."product_attribute_shop pas";
       $query .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa on pas.id_product_attribute=pa.id_product_attribute";
       $query .= " WHERE id_shop='".$shop."' AND pa.id_product='".$frow["id_product"]."'";
	   $query .= " ORDER BY pas.default_on DESC";
	   $res = dbquery($query); 
	   if(mysqli_num_rows($res)==0)
	   { echo "unexpected end"; exit();  /* this should not happen */
	   }   
	   $row = mysqli_fetch_array($res);
	   if($row["default_on"]==0) /* nothing selected */
       { $squery = "UPDATE "._DB_PREFIX_."product_attribute_shop SET default_on='1'";
	     $squery .= " WHERE id_product_attribute='".$row["id_product_attribute"]."' AND id_shop='".$shop."'";
	     $sres = dbquery($squery); 
	     echo "attr-".$row["id_product_attribute"].", ";
	     if($shop == "1")
	     { $squery = "UPDATE "._DB_PREFIX_."product_attribute SET default_on='1'";
	       $squery .= " WHERE id_product_attribute='".$row["id_product_attribute"]."'";
		   $sres = dbquery($squery); 
	     }
	   }
	   if($frow["cache_default_attribute"] != $row["id_product_attribute"])
       { $squery = "UPDATE "._DB_PREFIX_."product_shop SET cache_default_attribute='".$row["id_product_attribute"]."'";
		 $squery .= " WHERE id_product='".$frow["id_product"]."' AND id_shop='".$shop."'";
	     $sres = dbquery($squery); 
	     echo $frow["id_product"].", ";
	     if($shop == "1")
	     { $squery = "UPDATE "._DB_PREFIX_."product SET cache_default_attribute='".$row["id_product_attribute"]."'";
		   $squery .= " WHERE id_product='".$frow["id_product"]."'";
	       $sres = dbquery($squery); 
	     }
	   }
	   echo $frow["id_product"].", ";
	 }  /* END while($frow) */
   } /* end foreach shops */
 }  /* end resetcacheflags */
 

 else if(isset($_POST['subject']) && ($_POST['subject'] == "filesrestore"))
 { // borrowed from /modules/autoupgrade/classes/ZipAction.php
   $restorefile = '../autoupgrade/backup/'.preg_replace('[^a-zA-Z0-9\-_\.]','',$_POST['restorefile']);
   $zip = new \ZipArchive();
   if(!file_exists($restorefile)) colordie("File doesn't exist!");
   if ($zip->open($restorefile) !== true || empty($zip->filename)) 
	 colordie("Error opening zip-file ".$restorefile);

   echo "Extracting ".$zip->numFiles." files<br>";
   for ($i = 0; $i < $zip->numFiles; ++$i) 
   { if (!$zip->extractTo('tmp2', [$zip->getNameIndex($i)])) 
	     colordie('Could not extract '.$zip->statIndex($i)['name'].' from backup, the destination might not be writable.');
	 if(!($i%100)) echo $i." ";
   }
   $zip->close();
   echo '<script>alert("Finished");</script>';
 }
 
 else if(isset($_POST['subject']) && ($_POST['subject'] == "dbrestore"))
 {  $timeout = intval($_POST['timeout']);
    if($timeout < 20) $timeout = 20;
	set_time_limit($timeout);
	if(!isset($_POST['restoredb'])) die("No database provided");
    if(!isset($_POST['restorefiles'])) die("No files provided");
	if(isset($_POST['skipstats'])) $skipstats = true; else $skipstats = false;
	if(isset($_POST['savesql'])) $savesql = true; else $savesql = false;	
    if(file_exists("tmp"))
    { if(!is_dir("tmp")) colordie("Cannot create tmp directory in Prestools directory as there is a file with the same name");
      $files = glob('tmp/*'); // get all file names
      foreach($files as $file)
		unlink($file); // delete file
    }
    else 
	   $newdir = mkdir("tmp");
    $db = preg_replace('[^a-zA-Z0-9\-_\.]','',$_POST['restoredb']);
    dbquery("USE ".$db);
	$res = dbquery("SHOW TABLES");
	if(mysqli_num_rows($res) != 0) colordie("Table ".$db." is not empty!");    $filesdir = $_POST['restorefiles'];
    
    $backuppath = "../autoupgrade/backup";
    $files = scandir($backuppath."/".$filesdir);
	$filecount = 0;
    foreach($files AS $file)
    { if(($file == ".") || ($file=="..")) continue;
	  if(is_dir($backuppath."/".$filesdir."/".$file)) continue;
	  if(substr($file,0,4) != "auto") continue;
	 
	  $dot_pos = strrpos($file, '.');
	  $fileext = substr($file, $dot_pos+1);
	  $newfilename = substr($file, 0, $dot_pos);
	  $content = '';
	  echo "<br>".$file." ";
	 /* Note: I tried bzopen() and bzread() but at least on Windows it doesn't work. */
//	  if ($fp = fopen($backuppath."/".$filesdir."/".$file, 'r'))
//	  { while(!feof($fp))
//		  $content .= fread($fp, 4096);
//        fclose($fp);
//	  }
//	  if (empty($content)) colordie("Error reading file");
	  /* the following decompress routines were copied from /modules/autoupgrade/classes/TaskRunner/Rollback/RestoreDb.php from PS 1.7.8.9 */
	  /* the previously used version is outcommented - it no longer worked */
	  $data = "";
	  switch ($fileext)
	  { case 'bz': case 'bz2':
			$fp = bzopen($backuppath."/".$filesdir."/".$file, 'r');
			if (is_resource($fp)) {
				while (!feof($fp)) {
					$data .= bzread($fp, 4096);
				}
				bzclose($fp);
			}
	      // $data = bzdecompress($content);
	      break;
        case 'gz':
			$fp = gzopen($backuppath."/".$filesdir."/".$file, 'r');
			if (is_resource($fp)) {
				while (!feof($fp)) {
					$data .= gzread($fp, 4096);
				}
				gzclose($fp);
			}
			// $data = gzuncompress($content);
			break;
		default:
		  colordie("Error decompressing ".$file."<br>Please report this to Prestools.");
	  }
	  file_put_contents("tmp/".$newfilename, $data);
	  $filecount++;
    }
	if($db!= "none")
    { $fname = $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
      if(isset($_SERVER['HTTPS'])) $prfx = "https://"; else $prfx = "http://";
      $link = $prfx.$fname.'?subject=tablerestore&fileno=0&filecount='.$filecount.'&restoredb='.$db;
	  if($skipstats)
	    $link .= '&skipstats=on';
	  if($savesql)
	    $link .= '&savesql=on';
	  if($verbose == "true")
	    $link .= '&verbose=on';
	  echo '<script>location.href="'.$link.'";</script>';
	}
 }
 else if(isset($_GET['subject']) && ($_GET['subject'] == "tablerestore"))
 {  if(!isset($_GET['restoredb'])) die("No Database provided");
	if(isset($_GET['skipstats'])) $skipstats = true; else $skipstats = false;
	if(isset($_GET['savesql'])) $savesql = true; else $savesql = false;
	if(!isset($_GET['fileno'])) die("No fileno provided"); else $fileno = intval($_GET['fileno']);
	if(!isset($_GET['filecount'])) die("No filecount provided"); else $filecount = intval($_GET['filecount']);
	if(isset($_GET['prefix'])) $prefix = preg_replace('[^a-zA-Z0-9\-_\.]','',$_GET['prefix']);
	echo "Importing file ".($fileno+1)." of ".$filecount;
	//." ".$_SERVER['REQUEST_URI'];
	
	$db = preg_replace('[^a-zA-Z0-9\-_\.]','',$_GET['restoredb']);
	
    dbquery("USE ".$db);
    $files = scandir("tmp"); /* scandir is defined to keep alphabetical order */
	$fn = 0;
    foreach($files AS $file)
    { if(($file == ".") || ($file=="..")) continue;
	  if(is_dir($file)) colordie('Your tmp directory shouldn\'t have subdirectories!');
	  if($fn == $fileno)
	  { $data = file_get_contents("tmp/".$file);
        $listQuery = preg_split('/;[\n\r]+/Usm', $data);
        foreach($listQuery AS $cmd)
        { if(trim($cmd) == "") continue;
	      $cmd = ltrim($cmd); /* sometimes there are spaces before "insert into" */
	      if($skipstats)
		  { if(substr($cmd, 0, 12) == "INSERT INTO ")
			{ if(!isset($prefix))
		      { $pos = strpos($cmd, " ", 13);
		        if(substr($cmd, $pos-7, 6) == "access")
		        { $prefix = substr($cmd, 13, $pos-20);
		        }
		      }
		  	  $start = 13+strlen($prefix);
		      if(((substr($cmd, $start, strlen("connections`")) == "connections`")
			    || (substr($cmd, $start, strlen("connections_source`")) == "connections_source`")
			    || (substr($cmd, $start, strlen("guest`")) == "guest`")
			    || (substr($cmd, $start, strlen("page_viewed`")) == "page_viewed`")))
		      { 
		        continue;
		      }
			}
		  }
		  dbquery($cmd);
		}
	  }
	  $fn++;
    }

	if($fileno >= ($filecount-1))
	{ if(!$savesql)
	  { $files = glob('tmp/*'); // get all file names
        foreach($files as $file)
		  unlink($file); // delete file
	  }
	  echo '<script>alert("Finished");</script>';
	}
	else
	{ if(isset($_SERVER['HTTPS'])) $prfx = "https://"; else $prfx = "http://";
	  $fname = $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
      $link = $prfx.$fname.'?subject=tablerestore&fileno='.($fileno+1).'&filecount='.$filecount.'&restoredb='.$db;
	  if($skipstats)
	    $link .= '&skipstats=on';
	  if($savesql)
	    $link .= '&savesql=on';
	  if($verbose == "true")
	    $link .= '&verbose=on';
	  $link .= '&prefix='.$prefix;
	  echo '<script>location.href="'.$link.'";</script>';
	}
 }
 
  else if(isset($_POST['subject']) && ($_POST['subject'] == "dbimport"))
  { $timeout = intval($_POST['timeout']);
    if($timeout < 20) $timeout = 20;
	set_time_limit($timeout);
	if(!isset($_POST['importdb'])) die("No Database Provided");
    if(!isset($_POST['importfile'])) die("No file provided");
	if(isset($_POST['skipstats'])) $skipstats = true; else $skipstats = false;
    $db = $_POST['importdb'];
    if(($db=="") || (substr($db,0,8)=="select a")) colordie("No database selected");
    dbquery("USE ".mescape($db));
	$startpos = 0;
	$blocksize = 200000;
	$line=0;
	$block = 0;
	$importfile = mescape($_POST['importfile']);
    if(($importfile=="") || (substr($importfile,0,8)=="select a")) colordie("No sql file selected");	
	while($content = file_get_contents($importfile,true,NULL,$startpos, $blocksize))
	{ $list = preg_split('/;[\n\r]+/Usm', $content);
	  $listsize = sizeof($list);
	  echo "<p>block ".$block++." has ".$listsize." entries. Read ".strlen($content)." bytes<br>";
	  for($ii=0; $ii<($listsize-1); $ii++)
	  { $cmd = $list[$ii];
		if(trim($cmd) == "")
		{ echo "Skipping empty line ".$line++."<p>";
		  continue; 
		}
	    if($skipstats && (!isset($prefix)) && (substr($cmd, 0, 12) == "INSERT INTO "))
		{ $pos = strpos($cmd, " ", 13);
		  if(substr($cmd, $pos-7, 6) == "access")
		  { $prefix = substr($cmd, 13, $pos-20);
			$start = 13+strlen($prefix);
		  }
		}
		if($skipstats && (substr($cmd, 0, 12) == "INSERT INTO ") 
			&& ((substr($cmd, $start, strlen("connections`")) == "connections`")
			|| (substr($cmd, $start, strlen("connections_source`")) == "connections_source`")
			|| (substr($cmd, $start, strlen("guest`")) == "guest`")
			|| (substr($cmd, $start, strlen("page_viewed`")) == "page_viewed`")))
		{ 
		  continue;
		}

		echo "Submitting line ".$line++." (".strlen($cmd).") <br>";
		// echo $list[$ii]."<p>";
		dbquery(ltrim($cmd));
	  }
	  if(strlen($content)<$blocksize)
		break;
	  $pos = strpos($content, $list[$listsize-2]);
	  $startpos = $startpos + $pos + strlen($list[$listsize-2]);  
	}
	echo "FINISHING";
	if(strlen($list[$ii])!=0)
	  dbquery($list[$ii]);	
 }
  else if(isset($_POST['subject']) && ($_POST['subject'] == "zerolengthcheck"))
  { echo "<h1>Files with zero length</h1>";
    $phpversion = phpversion();
    if($phpversion < 6)
	  echo "<b>Under PHP 5 this function will give errors with filenames that contain non-latin characters</b><p>";
    $fileclasses = array("css","image","js","other","php","scss","tpl","translation");
	echo '<form name="kiesform">Show ';
	foreach($fileclasses AS $fclass)
	  echo ' &nbsp; <input name=kiezer type=checkbox onchange="dispchange()" checked value="'.$fclass.'"> '.$fclass;
    if(!isset($_POST["includeimgs"]))
		$includeimgs = 0;
	else
		$includeimgs = 1;
	echo '</form><p>';
    analyze_folder($triplepath, 0);
  }
  else if(isset($_POST['subject']) && ($_POST['subject'] == "cachesearch"))
  { if(!isset($_POST['cachefile']) || ($_POST['cachefile'] == "")) return;
    $cachefile = $_POST['cachefile'];
	search_folder($triplepath."var/cache", $cachefile, 0);
	for($i=0; $i < sizeof($filesearchresults); $i++)
		$filesearchresults[$i] = str_replace("\\", "/", $filesearchresults[$i]); /* handle Windows */
	if(empty($filesearchresults)) 
	  $result = "not found";
    else
	{ $len = strlen($triplepath);
	  $result = "";
	  for($i=0; $i < sizeof($filesearchresults); $i++)
	  { $viewlink = substr($filesearchresults[0], $len);
        if($i > 0) echo "<br>";
	    $result = "<a href=downfile.php?mode=cache&filename=".substr($filesearchresults[0], $len)." target=_blank>/".substr($filesearchresults[0], $len)."</a>";
	    $result .= " (<a href=showfile.php?filename=".substr($filesearchresults[$i], $len)." target=_blank>View</a>)";
	  }
	}
	echo "HHH".$result."SDS";
	echo "<script>if(parent && parent.fillcachelocation) parent.fillcachelocation('".$result."');</script>";
  }  
  else if(isset($_POST['subject']) && ($_POST['subject'] == "currencyrepair") && (version_compare(_PS_VERSION_ , "1.7.6", ">=")))
  { echo "<h1>Repairing currency_lang table</h1>";
    $res = dbquery('show tables like "'._DB_PREFIX_.'currency_lang"');
    if(mysqli_num_rows($res) == 0)
	{ $query = 'CREATE TABLE `'._DB_PREFIX_.'currency_lang` (
    `id_currency` int(10) unsigned NOT NULL,
    `id_lang` int(10) unsigned NOT NULL,
    `name` varchar(255) NOT NULL,
    `symbol` varchar(255) NOT NULL,';
	  if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
		$query .= ' `pattern` varchar(255) DEFAULT NULL,';
	  $query .= ' PRIMARY KEY (`id_currency`,`id_lang`))';
	  $res = dbquery($query);
	}

  	$symbols = array("ALL"=>"L","CAD"=>"CA$","CHF"=>"fr","CZK"=>"Kč","EUR"=>"€","GBP"=>"£",
	"HRK"=>"kn","HUF"=>"Ft","IDR"=>"Rp","ILS"=>"₪","INR"=>"₹","IRR"=>"﷼","MXN"=>"MX$","NOK"=>"kr",
	"PLN"=>"zł","RON"=>"L","RSD"=>"din","RUB"=>"₽","SEK"=>"kr","TRY"=>"₺","USD"=>"$");  /* ISO 4217-code */
    $verbose = "true";
    $query = "SELECT * FROM "._DB_PREFIX_."currency ORDER BY id_currency"; 
    $res = dbquery($query);
    while ($row=mysqli_fetch_array($res))
	{ $name = $symbol = "";
	  $clquery = "SELECT * FROM "._DB_PREFIX_."currency_lang";
	  $clquery .= " WHERE id_currency=".$row["id_currency"]." AND name != '' ORDER by symbol DESC"; 
      $clres = dbquery($clquery);
	  if(mysqli_num_rows($clres) > 0)
	  { $clrow=mysqli_fetch_array($clres);
		$name = $clrow["name"]; 
		$symbol = $clrow["symbol"];
	  }
	  if($name == '')
	  { if($row['name'] != '')
		  $name = $row['name'];
	    else 
		  $name = $row["iso_code"];
	  }
	  if($symbol == '')
	  { if(isset($symbols[$row["iso_code"]])) 
		  $symbol = $symbols[$row["iso_code"]];
	    else 
		  $symbol = $row["iso_code"];
	  }  
	  $lquery = "SELECT * FROM "._DB_PREFIX_."lang ORDER BY id_lang"; 
      $lres = dbquery($lquery);
      while ($lrow=mysqli_fetch_array($lres))
	  { $clquery = "SELECT * FROM "._DB_PREFIX_."currency_lang";
		$clquery .= " WHERE id_currency=".$row["id_currency"]." AND id_lang=".$lrow["id_lang"];
        $clres = dbquery($clquery);
		if(mysqli_num_rows($clres) == 0)
		{ if($name == "") $name = $row["iso_code"];
   		  $iquery = 'INSERT INTO '._DB_PREFIX_.'currency_lang SET id_currency="'.$row["id_currency"];
		  $iquery .= '",id_lang="'.$lrow["id_lang"].'",name="'.$name.'", symbol="'.$symbol.'"';
	  	  $ires = dbquery($iquery);
		}
		else
		{ $clrow=mysqli_fetch_array($clres);
		  if($clrow["name"] == "")
		  { $uquery = 'UPDATE '._DB_PREFIX_.'currency_lang SET name="'.$name.'"';
			$uquery .= " WHERE id_currency=".$row["id_currency"]." AND id_lang=".$lrow["id_lang"]; 
			$ures = dbquery($uquery);
		  }
		  if($clrow["symbol"] == "")
		  { $uquery = 'UPDATE '._DB_PREFIX_.'currency_lang SET symbol="'.$symbol.'"';
			$uquery .= " WHERE id_currency=".$row["id_currency"]." AND id_lang=".$lrow["id_lang"]; 
			$ures = dbquery($uquery);
		  }
		}
	  }
	}
  }

/* search folder for zero length files */
function analyze_folder($path, $level)
{ global $dirroot, $triplepath, $includeimgs;
  if($level==20)
  { // echo "Too many levels ".$path."<br>";
    return;
//	die("END");
  }
  for($i=0; $i<$level; $i++)
		echo "  ";
  $subdirs = array();
  $files = scandir($path);
  $cleanPath = rtrim($path, '/'). '/';
//  natcasesort($files);
  foreach($files as $t) 
  {     if (($t==".") || ($t=="..")) continue;
        $currentFile = $cleanPath . $t;
		$str = "";
		for($i=0; $i<$level; $i++)
			$str .= "  ";
		$str .= $t;
		if(strlen($str) < 30)
			$str = str_pad($str, 30);
        if (is_dir($currentFile))
		{	$subdirs[] = $currentFile;
		}
		else 
		{   clearstatcache();
			if(filesize($currentFile)==0)
			{ $type = "";
			  $pos = strrpos($t, '.');
			  if(!$pos) 
			    $type = "other";
			  else
			  { $suffix = substr($t,$pos+1);
			    if($suffix == "css")
				  $type = "css";
				else if($suffix == "js")
				  $type = "js";
				else if(stripos($cleanPath, "translation") > 0)
				  $type = "translation";
				else if($suffix == "php")
				  $type = "php";
				else if($suffix == "tpl")
				  $type = "tpl";
				else if($suffix == "scss")
				  $type = "scss";
				else if(in_array(strtolower($suffix), array("jpg","png","gif")))
				  $type = "image";				
				else 
				  $type = "other";
			  }
			  echo '<span class="lineblock" data-type="'.$type.'">';
			  echo $currentFile." ".filesize($currentFile)."</span>";
			}
        }
    }
	foreach($subdirs AS $subdir)
    {// if(($level == 0) && !$includeimgs && (substr($subdir,-4)=="/img")) continue;
	  analyze_folder($subdir, $level+1);
	}
}

 function del_class_index()
 {   global $triplepath;
     $rootlink = realpath($triplepath."cache/class_index.php");
    /* Note: do we need here something like PS's normalizeDirectory($directory) {return rtrim($directory, '/\\').DIRECTORY_SEPARATOR;} from Prestashopautoload.php? */
	 if($rootlink && file_exists($rootlink))
	 { @chmod($rootlink, 0777); // is this needed?
	   if(unlink($rootlink))
	     echo "cleaned class index<br>";
	   else
	     echo "error cleaning the class index. Try manually deleting /cache/class_index.php<br>";
	 }
 }
 


/* search cache for file */
$filesearchresults = [];
function search_folder($path, $filename, $level)
{ global $dirroot, $triplepath, $includeimgs, $filesearchresults;
  if($level == 0)
  { $filesearchresults = []; 
  }
  if($level==20)
  { echo "Too many levels ".$path."<br>";
//    return;
	die("END");
  }
  $subdirs = [];
  $files = scandir($path);
  $cleanPath = rtrim($path, '/'). '/';
  foreach($files as $t) 
  {     if (($t==".") || ($t=="..")) continue;
        $currentFile = $cleanPath . $t;
		if($t == $filename)
		{ $filesearchresults[] = $currentFile; 
		}
        if (is_dir($currentFile))
		{	$subdirs[] = $currentFile;
		}
    }
	foreach($subdirs AS $subdir)
    {// if(($level == 0) && !$includeimgs && (substr($subdir,-4)=="/img")) continue;
	  search_folder($subdir, $filename, $level+1);
	}
}
