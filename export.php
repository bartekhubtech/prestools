<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

  if(version_compare(_PS_VERSION_ , "1.7", "<"))
  { $rootdirs = array("Adapter","cache","classes","config","controllers","Core","css","docs","img","js",
    "localization","log","mails","modules","override","pdf","themes","tools",
    "translations","vendor","webservice");
  }
  else
  { $rootdirs = array("app","bin","cache","classes","config","controllers","docs","img","js",
    "localization","mails","modules","override","pdf","src","themes","tools",
    "translations","var","vendor","webservice");
  }

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Export functions</title>
<style>
.comment {background-color:#aabbcc}
table.spacer td {
	padding:12px;
	border: 1px solid #c3c3c3;
	border-collapse: collapse;
}

</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function formprepare(formname)
{ var field = eval(formname+".verbose");
  field.value = configform.verbose.checked;  
}

</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<h1>Export functions</h1>
This page provides a set of functions to export data from your webshop. Note that csv export can be done from the product-edit page.<p>
<?php

  echo '<form name=configform><input type=checkbox name=verbose> verbose</form>';
  echo '<table class="spacer" style="width:100%">';
  
echo '<tr><td><b>Export Shop File Tree</b><br>';
if($prestools_settings["allow_filelist_export"])
{ echo 'Export a list of all the files of your shop with their size. 
  This file can be compared to a similar file from another installation in programs like Winmerge.
  That way anomalies, like being hacked and zero length and missing files, can be detected.';
  echo '<br><form name=filetreeform action="export-proc.php" method=post target="_blank" onsubmit=formprepare("filetreeform")>';
  echo "Basedir: <select name=basedir>";
  print_dirtree($triplepath, 0);
  echo "</select><br>";
  echo '<input type=checkbox name="skipimg" checked> Skip image directory &nbsp &nbsp;';
  echo '<input type=checkbox name="skipcache" checked> Skip cache directory &nbsp &nbsp;';
  echo '<input type=checkbox name="skipukroots" checked> Skip unknown root directories (this includes admin) &nbsp;<br>';
  echo '<input type=checkbox name="fullpath"> Use full paths &nbsp &nbsp;';
  echo '<input type=checkbox name="showfilesize" checked > Show file size &nbsp; &nbsp;';
  echo '<input type=checkbox name="showdatetime" > Show date/time &nbsp; &nbsp;';
  echo '<input type=checkbox name="showrights" > Show rights &nbsp; &nbsp;';
  echo '<input type=checkbox name="showowner" > Show owner &nbsp;';
  echo '</td><td>';
  echo '<input type=hidden name=verbose><input type=hidden name=task value="filetree">';
  if(!$prestools_settings["demo_mode"])
    echo '<input type=submit value="Export file tree">';
  echo '</form></td></tr>';
}
else
{ echo 'This function is disabled in the settings file.</td></tr>';
}
echo '<tr><td><b>Export Shop Changed File Tree Extract</b><br>';
if($prestools_settings["allow_filelist_export"])
{ echo 'Export a list of all the files that were last change in a specific time period. 
  This function allows you to quickly get an overview of files that were changed recently.
  This function always shows full paths. Use the format yyyy-mm-dd for dates. Mind the zeroes: March 1 2022 will become 2022-03-01. You can add the time to get yyyy-mm-dd HH:ii:ss. When you omit parts the lowest values are assumed. So just "2023" will be translated as "2023-01-01 00:00:00"';
  echo '<br><form name=filechangedform action="export-proc.php" method=post target="_blank" onsubmit=formprepare("filechangedform")>';
  echo '<input type=checkbox name="skipimg" checked> Skip image directory &nbsp &nbsp;';
  echo '<input type=checkbox name="skipcache" checked> Skip cache directory &nbsp &nbsp;';
  echo '<input type=checkbox name="showfilesize" > Show file size &nbsp; &nbsp;';
  echo '<br>Show files changed between <input name=startdate size=7> and <input name=enddate size=7>';
  echo '</td><td>';
  echo '<input type=hidden name=verbose><input type=hidden name=task value="changetree">';
  if(!$demo_mode)
    echo '<input type=submit value="Export change tree">';
  echo '</form></td></tr>';
}
else
{ echo 'This function is disabled in the settings file.</td></tr>';
}
  echo '<tr><td><b>Export Category Tree</b><br>';
  echo '<form name=cattreeform action="export-proc.php" method=post target="_blank" onsubmit=formprepare("cattreeform")>';
  echo '<input type=checkbox name="showid" checked > Show id &nbsp; &nbsp;';
  echo '<input type=checkbox name="shownumbers" > Show nr of products &nbsp; &nbsp;';
  echo '<input type=hidden name=verbose><input type=hidden name=task value="categorytree">';
  echo '</td><td>';
  echo '<input type=submit value="Export category tree"></form></td></tr>';
  
  echo '<tr><td><b>Export table list</b><br>';
  echo 'Exported database table lists provide a quick way to see whether shops have missing or extra tables compared to another installation.';
  echo '</td><td>';
  echo '<form name=tablelistform action="export-proc.php" method=post target="_blank" onsubmit=formprepare("tablelistform")>';
  echo '<input type=hidden name=verbose><input type=hidden name=task value="tablelist">';
  echo '<input type=submit value="Export table list"></form></td></tr>';
  
  echo '</table>';
  
  include "footer1.php";	  
  echo '</body></html>';
  
/* analyze folder: this is a recursive function */
function print_dirtree($path, $level)
{ global $triplepath,$fullpath,$rootdirs,$demo_mode;
  if($level==3)
  { return;
  }
  $basepath = "/".substr($path,strlen($triplepath));
  if(strlen($basepath) > 1)
	  $basepath .= "/";
  echo "<option>".$basepath."</option>";
  $subdirs = array();
  $files = scandir($path);
  $cleanPath = rtrim($path, '/'). '/';
  natcasesort($files);
  foreach($files as $t) 
  {     if (($t==".") || ($t=="..")) continue;
        $currentFile = $cleanPath . $t;
        if (!is_dir($currentFile)) continue;
		$str = "";
		if($fullpath) 
			$str .= $basepath;
		$str .= $t;
		if(strlen($str) < 30)
			$str = str_pad($str, 30);
		$str .= "  ";
        if (is_dir($currentFile))
		{	$subdirs[] = $currentFile;
		}

		// echo $str."</option>";
    }

	foreach($subdirs AS $subdir)
    { $pos = strrpos($subdir,"/");
	  if($demo_mode && ($level ==0) && !in_array(substr($subdir,$pos+1),$rootdirs))
		{ echo "XXX".$subdir."-".substr($subdir,$pos+1)." ";	continue; } 
	  print_dirtree($subdir, $level+1);
	}
}


