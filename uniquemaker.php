<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(isset($input['mytable'])) $mytable = str_replace("<","",$input['mytable']); else $mytable = "";
if(!isset($input['mycols'])) $mycols = array(); else $mycols = $input['mycols'];
if(!isset($input['startrec'])) $startrec=0; else $startrec = intval($input['startrec']);
if(!isset($input['numrecs'])) $numrecs = 100; else $numrecs=intval($input['numrecs']);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Unique Maker</title>
<style>
</style>
<script type="text/javascript" src="utils8.js"></script>
<script>
function tablesubmit()
{ var tab = document.getElementById("doublestable");
  if(tab) tab.innerHTML = "";
  tab = document.getElementById("colstable");
  if(tab) tab.innerHTML = "";
}

function colssubmit()
{ var tab = document.getElementById("doublestable");
  if(tab) tab.innerHTML = "";
}

function doublessubmit()
{ mainform.method="post";
  mainform.action="uniquemaker-proc.php";
  mainform.urlsrc.value = location.href;
  mainform.submit();
}

function selectall()
{ var myval = mainform.selall.checked;
  var checkboxes = document.getElementsByName('mrows[]');
  for (var checkbox of checkboxes) 
    checkbox.checked = myval;
}
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<h1>Make a set of database fields unique</h1>
In old shops often some indexes have been deleted. And often it is not possible to restore unique indexes as the data have become poluted and the concerned set of fields contains now duplicates. This page offers a solution. It works as follows:<br>
 - First select a table and click on Submit.<br>
 - You will now be shown list of of the fields in this table.<br>
 - Select the set of fields that should be unique and click on Submit.<br>
 - Now you will be shown transgressing sets of database rows.<br>
 - You can now chose to fix a set - after which the system will decide which rows will be deleted.<br>
 - In both approaches the system will always preserve one row in each set.<br>
 - Note that this solution deletes the extra rows. That may not in all circumstances be the best solution. But even when it is not the overview that this page provides can help you determine the best solution.<p>
 
<?php

  $alltables = [];
  echo "<form name='mainform'>Table &nbsp; <select name=mytable><option>Select a table</option>";
  $query = "SHOW FULL TABLES";
  $res = dbquery($query); 
  while($row = mysqli_fetch_row($res))
  { if($row[1] != "BASE TABLE") continue;
    $alltables[] = $row[0];
	$selected = "";
	if($row[0] == $mytable)
	  $selected = " selected";
    echo "<option ".$selected.">".$row[0]."</option>";
  }
  echo "</select> &nbsp; <input type=submit onclick='tablesubmit();'><p>";
  if($mytable == "")
  { include "footer1.php";
	die('</form></body></html>');
  }
  if(($mytable != "") && (!in_array($mytable, $alltables)))
  { echo "Unknown table ".$mytable."</p>";
	include "footer1.php";
	die('</form></body></html>');
  }
  
  echo "<hr><b>Select fields from table ".$mytable." that together must be unique</b><br>";
  $selectedcols = $allcols = array();
  echo '<div style="display:inline-block; padding-top:5px"><table class="triplemain" id="colstable">';
  $query = "SHOW COLUMNS FROM ".$mytable;
  $res = dbquery($query);
  while($row = mysqli_fetch_array($res))
  { $checked = "";
	$allcols[] = $row[0];
	if(in_array($row[0], $mycols))
	{ $checked = " checked";
	  $selectedcols[] = $row[0];
	}
	echo '<tr><td><input type=checkbox name="mycols[]" value="'.$row[0].'" '.$checked.'></td>';
	echo '<td>'.$row[0]."</td></tr>";
  }
  echo '</table></div> &nbsp; &nbsp; <div style="padding-top:7px; display:inline-block; vertical-align:top">
  Start set  <input name=startrec value='.$startrec.' size=5><br>
  Nr of sets <input name=numrecs size=5 value='.$numrecs.'></div> &nbsp; &nbsp;
  <div style="padding-top:7px; display:inline-block; vertical-align:top">
  <input type=submit onclick="colssubmit()"></div>';
  if(count($mycols) == 0)
  { include "footer1.php";
	die('</form></body></html>');
  }
//  else print_r($mycols);
  
  echo '<hr>';
  
  $query = "SELECT SQL_CALC_FOUND_ROWS `".implode("`,`",$selectedcols)."`, count(*) AS cntr";
  $query .= " FROM ".$mytable." GROUP BY `".implode("`,`",$selectedcols)."`";
  $query .= " HAVING cntr > 1";
  $query .= " LIMIT ".$startrec.",".$numrecs;
  $res = dbquery($query);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);
  $numrecs2 = $row2['foundrows'];
  echo "<b>Showing ".mysqli_num_rows($res)." (of ".$numrecs2.") sets of rows containing the same fields '".implode(",",$selectedcols)."' that were found</b><br>";

  echo '<div style="padding-top:5px; display:inline-block; vertical-align:top">';
  echo '<table class="triplemain" id="doublestable"><tr><td></td>';
  foreach($selectedcols AS $col)
	echo '<td>'.$col.'</td>';
  foreach($allcols AS $col)
	if(!in_array($col, $selectedcols))
	  echo '<td>'.$col.'</td>';
  echo '<td></td></tr>';
  $trbgflag = 0;
  while($row = mysqli_fetch_array($res))
  { if($trbgflag)
	  $trbg = " style='background-color: #eeffee'";
    else
	  $trbg = "";
    $trbgflag = 1-$trbgflag;
	$firstinset = true;
	$values = [];
	$squery = "SELECT * FROM ".$mytable." WHERE 1";
    foreach($selectedcols AS $col)
	{ $squery .= " AND `".$col."`='".$row[$col]."'";
	  $values[] = bin2hex($row[$col]);
	}
    $sres = dbquery($squery);
	echo "<tr ".$trbg."><td rowspan=".mysqli_num_rows($sres).">";
	echo "<input type=checkbox name=mrows[] value='".implode(",",$values)."'>";
	echo '</td>';
	while($srow = mysqli_fetch_array($sres))
	{ if($firstinset)
		$firstinset = false;
	  else
		echo "<tr ".$trbg.">";
	  foreach($selectedcols AS $col)
		echo '<td style="background-color:#99ee99">'.htmlspecialchars($srow[$col]).'</td>';
	  foreach($allcols AS $col)
	    if(!in_array($col, $selectedcols))
		  echo '<td>'.htmlspecialchars($srow[$col]).'</td>';
	  echo '<td></td></tr>'; /* space for row delete checkbox */
	}
  }
  echo '</table></div>';
  echo '<div style="padding-top:5px; display:inline-block; vertical-align:top">';
  echo '<input type=checkbox name=verbose> verbose<br>';
  echo '<input type=checkbox name=selall onchange="selectall()"> select all<br>';
  echo '<input type=hidden name="urlsrc" value="">';
  echo '<input type=submit value="Purge selected" onclick="doublessubmit()">';
  
  
  echo '</div></form>';
  
  include "footer1.php";
  die('</body></html>');

