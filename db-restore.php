<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Database Restore</title>
<style>
.comment {background-color:#aabbcc}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function checkrestore()
{ if (restoreform.restoredb.selectedIndex == 0)
  { restoreform.savesql.checked = true;	
  }
  if (restoreform.restorefiles.selectedIndex == 0)
  { alert("You must select a backupped fileset to restore!")
	return false;	
  }
  restoreform.verbose.value = configform.verbose.checked;
  return true;
}

function checkimport()
{ if (importform.importdb.selectedIndex == 0)
  { alert("You must select a database in which to import!")
	return false;	
  }
  if (importform.importfile.selectedIndex == 0)
  { alert("You must select a sql file to import!")
	return false;	
  }
  importform.verbose.value = configform.verbose.checked;
  return true;
}

</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<h1>Restore database and files backup</h1>
This page is about importing data:<br>
- The first function imports a database backup that was produced by Prestashop's 1-click upgrade module into an 
empty table. Useful when your update failed and you find it hard to restore the database. It only works 
when your Prestools in under your admin directory.<br>
 - The second functions unzips the files backup that was produced by Prestashop's 1-click upgrade module 
 into the tmp2 directory below your Prestools directory.<br>
 - The third function imports data from a sql file in your Prestools directory. Useful when a 
 normal import fails - for example because the file is too large.<br>
 Note that these operations take a lot of time. As they happen in the frame at the right-top is easy to get the
 impression that nothing happens. Be patient.
<p>
<form name=configform><input type=checkbox name=verbose> verbose</form>
<hr><table class="triplemain"><tr><td style="text-align:center"><b>Restore and/or decompress backup database</b></td></tr><tr><td>
<?php
  echo "This function retrieves a database backup from an upgrade into an empty database.<br>";
  echo "The unzipped file will be stored in a tmp subdirectory below your Prestools directory<br>";
  echo "To avoid time-outs the processing goes in steps. You will see the frame at the righttop renew regularly.";
  echo '<form name="restoreform" action="shop-rescue-proc.php" method=post target=tank 
onsubmit="return checkrestore();">';
  echo '<table>';
  echo '<tr><td>Select a backup to restore: </td><td>';
  $backuppath = "../autoupgrade/backup";
  /* now check for an arbitrary file in the admin directory */
  $backupfound = false;
  if(!file_exists("../ajax.php")) echo "You can only run this function from a directory below your admin!</td></tr></table></form>";
  else if(!($files = scandir($backuppath))) echo "No backup directory found</td></tr></table></form>";
  else if(sizeof($files) <= 3) echo "Backup directory is empty</td></tr></table></form>";
  else 
  { echo '<select name="restorefiles"><option>select a backupset</option>';
    $backupfound = true;
	foreach($files AS $file)
	{ if(($file == ".") || ($file=="..")) continue;
	  if(!is_dir($backuppath."/".$file)) continue;
	  if((substr($file,0,2) != "V1") && (substr($file,0,2) != "V8")) continue;
	  echo '<option>'.$file.'</option>';
	}
	echo '</select><input type=hidden name="subject" value="dbrestore"></td></tr>';
  }
  
  if($backupfound)
  { echo '<tr><td>Select an empty database in which to restore the backup:<br>
     If no database is selected or available the backup is only decompressed.</td>';
    /* look for empty databases */
    /* Note: would this be faster with "show databases" and "show tables"? */
    $res = dbquery("use information_schema");  
    $cquery = "select schema_name from `schemata` s";
    $cquery.=" left join `tables` t on s.schema_name = t.table_schema";
    $cquery.=" where t.table_name is null";
    $equery = "select table_schema, sum(data_length) Z from information_schema.tables";
    $equery .= " where table_schema not in ('information_schema','performance_schema')";
    $equery .= " group by table_schema having z=0;";
    $cres=dbquery($cquery);
    echo '</td><td><select name="restoredb"><option value="none">No database selected</option>';
    while($crow = mysqli_fetch_array($cres))
		echo '<option>'.$crow["schema_name"].'</option>';
    echo '</select><input type=hidden name=verbose></td></tr>';
	echo '<tr><td>Timeout</td><td><input name="timeout" value="1200" size=5>secs</td></tr>';
	echo '<tr><td>Skip content of statistics tables (connections,connections_source,page_viewed,guest)?';
	echo '</td><td><input type=checkbox name="skipstats"></td></tr>';
	echo '<tr><td>Preserve unzipped sql files after completion?</td><td><input type=checkbox name="savesql"></td></tr></table>';
	echo '</td></tr>';
	echo '<tr><td style="text-align:center"><input type=submit value="Restore database"></form></td></tr></table>';
  }
?>
</td></tr></table><hr>

<table class="triplemain"><tr><td style="text-align:center"><b>Restore backup files</b></td></tr><tr><td>
<?php
  echo "This function decompresses the backup files created by a Prestashop 1-click upgrade.<br>
  The resulting files will be stored under the tmp2 subdirectory under the Prestools directory.<br>";
  echo "This will take a long time. Open the tmp2 directory in a file explorer if you want to see the progress.";
  echo '<form name="filesform" action="shop-rescue-proc.php" method=post target=tank 
onsubmit="return checkfiles();">';
  echo '</td></tr><tr><td>Select a backup to restore: ';
  $backuppath = "../autoupgrade/backup";
  /* now check for an arbitrary file in the admin directory */
  $backupfound = false;
  if(!file_exists("../ajax.php")) echo "You can only run this function from a directory below your admin!</td></tr></table></form>";
  else if(!($files = scandir($backuppath))) echo "No backup directory found</td></tr></table></form>";
  else if(sizeof($files) <= 3) echo "Backup directory is empty</td></tr></table></form>";
  else 
  { echo '<select name="restorefile"><option>select a backupset</option>';
    $backupfound = true;
	foreach($files AS $file)
	{ if(($file == ".") || ($file=="..")) continue;
	  if(is_dir($backuppath."/".$file)) continue;
	  if(substr($file,0,4) != "auto") continue;
	  echo '<option>'.$file.'</option>';
	}
	echo '</select><input type=hidden name="subject" value="filesrestore"></td></tr>';
	echo '<tr><td style="text-align:center"><input type=submit value="Restore files"></form></td></tr>';
  }
?>
</table>


<hr><table class="triplemain"><tr><td style="text-align:center" colspan=2><b>Import SQL file</b></td></tr><tr><td colspan=2>
<?php
  echo "This function imports a sql file exported by phpmyadmin or a similar program that you have uploaded to your Prestools directory.<br>";
  echo "As long as your timeout is long enough there is no limit to the size of the file.<br>";
  echo "It is your responsibility to make sure that the import doesn't clash with existing files.<br>";
  echo "This function is still experimental.<br>";
  echo '</td></tr><tr><td><form name="importform" action="shop-rescue-proc.php" method=post target=tank onsubmit="return checkimport();">';
  echo 'Select a database in which to import: </td><td><select name="importdb"><option>Select database</option>';
    
  $dres = dbquery("SHOW DATABASES");
  while($drow = mysqli_fetch_array($dres))
  { if(in_array($drow[0],array("information_schema","mysql","performance_schema","phpmyadmin")))
	  continue;
    echo '<option>'.$drow[0].'</option>';
  }
  echo '</select><input type=hidden name=verbose></td></tr>';
  echo '<tr><td>Select a sql file to import: </td><td>';
  if(!($files = scandir('.'))) echo "No sql files found</td></tr></table>";
  else
  { echo '<select name="importfile"><option>select sql import file</option>';
    foreach($files AS $file)
    { if(($file == ".") || ($file=="..")) continue;
	  $pos = strrpos($file, ".");
	  if(substr($file,$pos+1) != "sql") continue;
	  echo '<option>'.$file.'</option>';
    }
	echo '</select><input type=hidden name="subject" value="dbimport"></td></tr>';
	echo '<tr><td>Timeout</td><td><input name="timeout" value="1200" size=5>secs</td></tr>';
	echo '<tr><td>Skip content of statistics tables (connections,connections_source,page_viewed,guest)?';
	echo '</td><td><input type=checkbox name="skipstats"></td></tr>';
	echo '<tr><td style="text-align:center" colspan=2><input type=submit value="Import"></form></td></tr></table>';
  }
echo '<p>';
  include "footer1.php";
echo '</body></html>';

