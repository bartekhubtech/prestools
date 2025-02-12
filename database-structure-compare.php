<?php
if(!@include 'approve.php') die( "approve.php was not found!");

if(!isset($_POST["calibratedb"])) colordie("You didn't provide a database to compare with!");
$parts = explode(" (",$_POST["calibratedb"]);
$compdb = preg_replace('/[\(\)\s]/','',$parts[0]);
$compprefix = preg_replace('/[\(\)\s]/','',$_POST["prefix"]);
$comppos = strlen($compprefix);

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Database Structure Compare</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
</style>
<script src="utils8.js"></script>
<script src="sorter.js"></script>
<script>
function updat()
{ var tab = document.getElementById("Maintable");
  for(var i=0; i<tab.rows.length; i++)
  { tab.rows[i].style.display = "table-row";
    if(tab.rows[i].cells[1].innerHTML == "table not in remote shop")
	{ if (!compform.comptables.checked)
	    tab.rows[i].style.display = "none";
	}
    else if(tab.rows[i].cells[1].innerHTML == "table not in present shop")
	{ if (!compform.activetables.checked)
	    tab.rows[i].style.display = "none";
	}
	else if(!compform.commontables.checked)
	    tab.rows[i].style.display = "none";
	else
	{ var ftab = tab.rows[i].cells[1].childNodes[0];
	  for(var j=0; j<ftab.rows.length; j++)
	  { ftab.rows[j].style.display = "table-row";
        if(ftab.rows[j].cells[0].innerHTML.substring(0,6) == "index ")
	    { if (!compform.showindexes.checked)
	        ftab.rows[j].style.display = "none";
	    }
        else if(ftab.rows[j].cells[0].innerHTML.indexOf("not in remote shop") >0)
	    { if (!compform.compfields.checked)
	        ftab.rows[j].style.display = "none";
	    }
        else if(ftab.rows[j].cells[0].innerHTML.indexOf("not in active shop") >0)
	    { if (!compform.activefields.checked)
	        ftab.rows[j].style.display = "none";
	    }
        else if (!compform.commonfields.checked)
	        ftab.rows[j].style.display = "none";
	  }
	}
  }
	
}
</script>
</head><body>
<h1>Prestashop Structure Compare</h1>
This script shows the differences between two databases. It compares the structure of the database under which you are working now with that of another that you can select in shop_rescue.<br>
The present database is shown with an "a:". The database to which the comparison runs is shown with a "c:". Missing properties as shown as "[missing]".<br/><br/>

<?php echo "Comparing present database "._DB_NAME_." (a) with remote database ".$compdb." (c)"; ?>

<br><br><form name=compform>
Show tables: <input type=checkbox name=comptables onchange="updat()" checked> only remote &nbsp;
<input type=checkbox name=activetables onchange="updat()" checked> only local &nbsp;
<input type=checkbox name=commontables onchange="updat()" checked> common &nbsp;<br>
Show fields: <input type=checkbox name=compfields onchange="updat()" checked> only remote &nbsp;
<input type=checkbox name=activefields onchange="updat()" checked> only local &nbsp;
<input type=checkbox name=commonfields onchange="updat()" checked> common &nbsp;<br>
Show indexes: <input type=checkbox name=showindexes onchange="updat()" checked><p>

</form>
<?php
echo "<table id=Maintable class='triplemain'>";

$comptables = [];
$compexttables = [];
$query = "SHOW TABLES FROM ".mescape($compdb);
$res=dbquery($query);
while ($row=mysqli_fetch_array($res))
{ if(substr($row[0],0,$comppos) == $compprefix)
    $comptables[] = substr($row[0],$comppos);
  else 
	$compexttables = $row[0];	
}	

$activetables = [];
$activeexttables = [];
$len = strlen(_DB_PREFIX_);
$query = "SHOW TABLES";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res))
{ if(substr($row[0],0,$len) == _DB_PREFIX_)
    $activetables[] = substr($row[0],$len);
  else 
	$activeexttables = $row[0];	
}

$alltables = array_unique(array_merge($comptables, $activetables));
sort($alltables);

foreach($alltables AS $tabname)
{ $line = "<tr><td>".$tabname."</td>"; 
  if(!in_array($tabname, $activetables))
  { $line .="<td colspan=2>table not in present shop</td></tr>";
    echo $line;
	continue;
  }
  if(!in_array($tabname, $comptables))
  { $line .="<td colspan=2>table not in remote shop</td></tr>";
    echo $line;
	continue;
  }

  $fields = [];
  $cquery = "SHOW COLUMNS FROM `".$compdb."`.`".$compprefix.$tabname."`"; 
  $cres = dbquery($cquery);
  $complist = $compfields =  array();
  while ($crow=mysqli_fetch_assoc($cres))
  { $complist[$crow["Field"]] = $crow; 
    $compfields[] = $crow["Field"];
  }

  $equery = "SHOW COLUMNS FROM `"._DB_PREFIX_.$tabname."`";
  $eres = dbquery($equery);
  $activelist = $activefields = array();
  while ($erow=mysqli_fetch_assoc($eres))
  {	$activelist[$erow["Field"]] = $erow;
    $activefields[] = $erow["Field"];
  }
  
  $fdiffs = array_diff($activefields, $compfields);
  foreach($fdiffs AS $fdiff)
	$fields[] = "<tr><td>field ".$fdiff." not in remote shop</td></tr>";
	
  foreach($complist AS $field => $compprops)
  { if(!isset($activelist[$field]))
	{ 
	  $fields[] = "<tr><td>field ".$field." not in present shop</td></tr>";
	  continue;
	}
	
	$tprops = $activelist[$field];
	$fieldprops = "";
    foreach($compprops AS $name => $value)
	{ if(!isset($tprops[$name]) && ($compprops[$name]!=""))
		$fieldprops .= $name." a: [missing] c:".$compprops[$name]."<br>";
	  else if($tprops[$name] != $compprops[$name])
		$fieldprops .= $name." a:".$tprops[$name]." c:".$compprops[$name]."<br>";
	}
    foreach($tprops AS $name => $value)
	{ if(!isset($compprops[$name]) && ($tprops[$name]!=""))
		$fieldprops .= $name." a: ".$tprops[$name]." c:[missing]<br>";
	}
    if($fieldprops != "")
	  $fields[] = "<tr><td>".$field."&nbsp;&nbsp;</td><td>".$fieldprops."</td></tr>";
  }
 
  $vquery = "SHOW INDEXES FROM ".$compprefix.$tabname." FROM ".$compdb;
  $vres = dbquery($vquery);
  $compindexes = $compindexrows = array();
  while ($vrow=mysqli_fetch_assoc($vres))
  { unset($vrow["Cardinality"]);
    if(!in_array($vrow["Key_name"], $compindexes))
	{ $compindexes[] = $vrow["Key_name"];
	  $compindexrows[$vrow["Key_name"]] = $vrow;
	}
	$compindexrows[$vrow["Key_name"]]["Column_name"] .= ",".$vrow["Column_name"];
  }

  $yquery = "SHOW INDEXES FROM `"._DB_PREFIX_.$tabname."`";
  $yres = dbquery($yquery);
  $tindexes = $tindexrows = array();
  while ($yrow=mysqli_fetch_assoc($yres))
  { unset($yrow["Cardinality"]);
    if(!in_array($yrow["Key_name"], $tindexes))
	{ $tindexes[] = $yrow["Key_name"];
	  $tindexrows[$yrow["Key_name"]] = $yrow;
	}
	$tindexrows[$yrow["Key_name"]]["Column_name"] .= ",".$yrow["Column_name"];
  }

  $idiffs = array_diff($tindexes, $compindexes);
  foreach($idiffs AS $idiff)
	$fields[] = "<tr><td>index ".$idiff." not in remote shop (".$tindexrows[$idiff]["Column_name"].")</td></tr>";

  foreach($compindexes AS $compindex)
  { if(!in_array($compindex, $tindexes))
	{ $fields[] = "<tr><td>index ".$compindex." not in present shop (".$compindexrows[$compindex]["Column_name"].")</td></tr>";
	  continue;
	}  
  
    $ivals = "";
    foreach($compindexrows[$compindex] AS $fld => $content)
	{ if((!isset($tindexrows[$compindex][$fld])) && ($content != ""))
	    $ivals .= $fld.": a:[missing]; c:".$content."<br>";
	  else if ($content != $tindexrows[$compindex][$fld])
	    $ivals .= $fld.": a:".$tindexrows[$compindex][$fld]."; c:".$content."<br>";
	}

    if($ivals != "")
	  $fields[] = "<tr><td>index ".$compindex."</td><td> ".$ivals."</td></tr>";
  }

  if(sizeof($fields) > 0)
  {  echo $line."<td><table>";
     foreach($fields AS $field)
	   echo $field;
	 echo "</table></td></tr>";
  }
}
echo "</table>";

// Array ( [Field] => id_advice [Type] => int(11) [Null] => NO [Key] => PRI [Default] => [Extra] => auto_increment ) 




