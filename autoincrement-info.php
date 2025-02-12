<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
$extrachecked = "";
if(isset($input['showextra'])) 
  $extrachecked = "checked";

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Auto-increment Info</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<style>
option.defcat {background-color: #ff2222;}
input.posita {width: 50px; text-align:right}
span.cntr {font-size: 70%; color:#777777}
table.lister 
{ margin: 1px solid #c3c3c3;
  border-collapse: collapse;
}
table.lister td
{ border: 1px solid #e3e3e3;
  padding: 0px;
  empty-cells:show;
}

span.bgc 
{ background-color: #ffa500;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
function CollapseAll()
{ var tab = document.getElementById('ictable');
  var len = tab.rows.length;
  for(let i=1; i<len; i++)
  { if(tab.rows[i].childNodes.length > 3) /* skip tables without checked fields */
	{ var fld = tab.rows[i].childNodes[2].innerHTML;
      if(fld.substr(0,3) == "id_") /* if first of table */
		colno = 3;
	  else
		colno = 2;
	  tab.rows[i].childNodes[colno].childNodes[0].style.display="none"; 
	  tab.rows[i].childNodes[colno].childNodes[1].style.display="inline";
      tab.rows[i].childNodes[0].childNodes[0].value = '+';
	}
  }
}

function ShowAll()
{ var tab = document.getElementById('ictable');
  var len = tab.rows.length;
  for(let i=1; i<len; i++)
  { if(tab.rows[i].childNodes.length > 3) /* skip tables without checked fields */
	{ var fld = tab.rows[i].childNodes[2].innerHTML;
      if(fld.substr(0,3) == "id_") /* if first of table */
		colno = 3;
	  else
		colno = 2;
	  tab.rows[i].childNodes[colno].childNodes[0].style.display="inline"; 
	  tab.rows[i].childNodes[colno].childNodes[1].style.display="none";
      tab.rows[i].childNodes[0].childNodes[0].value = 'X';
	}
  }
}

function MinimizeRow(elt)
{ var fld, colno;
  var fld = elt.parentNode.parentNode.childNodes[2].innerHTML;
  if(fld.substr(0,3) == "id_") /* if first of table */
     colno = 3;
  else
	 colno = 2;
  if(elt.parentNode.parentNode.childNodes[colno].childNodes[0].style.display=="none")
  { elt.parentNode.parentNode.childNodes[colno].childNodes[0].style.display="inline";
	elt.parentNode.parentNode.childNodes[colno].childNodes[1].style.display="none";
	elt.value = "X";
  }
  else
  { elt.parentNode.parentNode.childNodes[colno].childNodes[0].style.display="none"; 
	elt.parentNode.parentNode.childNodes[colno].childNodes[1].style.display="inline";
    elt.value = '+';
  }
}

</script>
</head>

<body>
<?php
print_menubar();
echo '<table style="width:100%" ><tr><td class="headline"><a href="autoincrement-info.php">Prestashop Auto-increment Info</a>
<p>Part one shows you the auto-increment values and the position the auto-increment pointer. Part 2 shows the highest value for those auto-increment fields in tables other than their own. An orange background indicates that the value is higher than the auto-increment for that variable.<br>
<b>Show integrity checks</b> enables an optional third part. It does an integrity check for all relationships shown in the second table. This may take some time. Be careful interpreting the results of this operation. Zero values are in some contexts normal and order tables may link to deleted products. 
</td></tr></table>
<form name=extraform>
<input type=checkbox name=showextra '.$extrachecked.'> Show integrity checks &nbsp;
<input type=submit></form><p>';

$aivalues = array();    /* id_product => 1234 */
$aitables = array();  /* id_product => ps_product */
echo "<h2>Autoincrement keys and values</h2>";
echo '<table class=lister>';
$query = "SHOW TABLES";
$res = dbquery($query); 
while($row = mysqli_fetch_row($res))
{ $rx = dbquery("SHOW COLUMNS FROM ".$row[0]." WHERE extra LIKE '%AUTO_INCREMENT%'");
  if($rw = mysqli_fetch_row($rx))
  { $aiquery = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES";
	$aiquery .= " WHERE TABLE_SCHEMA = '"._DB_NAME_."' AND TABLE_NAME = '".$row[0]."'";
	$aires = dbquery($aiquery); 
	list($ai) = mysqli_fetch_row($aires);
	echo "<tr><td>".$row[0]."</td><td>".$rw[0]."</td><td>".$ai."</td></tr>";
	$len = strlen(_DB_PREFIX_);
	/* note that the paypal module uses id_order as primary key */
	if($rw[0] == "id_".substr($row[0],$len))  /* use only substr when key has module name */
	{ $aitables[$rw[0]] = $row[0];
	  $aivalues[$rw[0]] = $ai;
	}
  }
}
echo '</table>';

/* synonyms are ignored for the moment. They are in the order* tables where we don't want to check */
$synonyms = ["product_id"=>"id_product","product_attribute_id", "id_product_attribute"];

echo "<h2>Max id values used in other tables</h2>";
echo '<table class=lister><tr><td><b>table</b></td><td><b>field</b></td><td><b>max found &nbsp; </b></td><td><b>auto-increment pointer</b></td></tr>';
$query = "SHOW TABLES";
$res = dbquery($query); 
$x=1;
while($row = mysqli_fetch_row($res))
{ $table = $row[0];
  $rx = dbquery("SHOW COLUMNS FROM ".$table);
  $lines = [];
  while($rw = mysqli_fetch_row($rx))
  { $field = $rw[0];
    if(isset($aivalues[$field]) && ($aitables[$field] != $table))
	{ $mquery = "SELECT max(".$field.") AS maxi FROM ".$table;
	  $mres = dbquery($mquery);
	  list($maxi) = mysqli_fetch_row($mres);
	  if($maxi > $aivalues[$field])
		$bg = ' style="background-color:#ffa500"';
	  else
		$bg='';
	  $lines[] = '<td>'.$field.'</td><td '.$bg.'>'.$maxi.'</td><td>'.$aivalues[$field].'</td></tr>';
	}
  }
  if(sizeof($lines) == 0)
    echo '<tr><td>'.$table.'</td></tr>';
  else
    echo '<tr><td rowspan='.sizeof($lines).'>'.$table.'</td>';
  $first = true;
  foreach($lines AS $line)
  { if($first) $first=false; else echo '<tr>';
    echo $line;
  }
  $x++;
}
echo '</table>';

if($extrachecked == "checked")
 {echo "<h2>Integrity checks for all relationships  </h2>";
  echo '<input type="button" value="Collapse All" onclick="CollapseAll()" title="Collapse All">
  &nbsp; &nbsp; <input type="button" value="Show All" onclick="ShowAll()" title="Show All">';
  echo '<table class=lister id="ictable"><tr><td></td><td><b>table</b></td><td><b>field</b></td><td><b>not connected values &nbsp; </b></td><td><b>a-i pointer</b></td></tr>';
  $query = "SHOW TABLES";
  $res = dbquery($query); 
  while($row = mysqli_fetch_row($res))
  { $table = $row[0];
    $rx = dbquery("SHOW COLUMNS FROM ".$table);
    $lines = [];
    while($rw = mysqli_fetch_row($rx))
    { $field = $rw[0];
      if(isset($aivalues[$field]) && ($aitables[$field] != $table))
	  { $iquery = "SELECT DISTINCT a.".$field." FROM ".$table." a";
		$iquery .= " LEFT OUTER JOIN ".$aitables[$field]." b on a.".$field."=b.".$field;
		$iquery .= " WHERE b.".$field." is null ORDER BY a.".$field;
		$ires=dbquery($iquery);
		$tmp = "";
		while(list($val) = mysqli_fetch_row($ires))
		{ if($val > $aivalues[$field])
			$tmp .= '<span class="bgc">'.$val.'</span>, ';
		  else 
			$tmp .= $val.", ";
		}
		$size = mysqli_num_rows($ires);
	    $lines[] = '<td>'.$field.'</td><td><span>'.$tmp.'</span><span style="display:none">'.$size.' mismatches</span</td><td>'.$aivalues[$field].'</td></tr>';
	  }
    }
    if(sizeof($lines) == 0)
      echo '<tr><td></td><td>'.$table.'</td></tr>';
    else
      echo '<tr><td><input type="button" value="X" style="width:4px" onclick="MinimizeRow(this)" title="Minimize/show" tabindex="-1"></td><td rowspan='.sizeof($lines).'>'.$table.'</td>';
    $first = true;
    foreach($lines AS $line)
    { if($first) $first=false; else echo '<tr><td><input type="button" value="X" style="width:4px" onclick="MinimizeRow(this)" title="Minimize/show" tabindex="-1"></td>';
      echo $line;
    }
  }
  echo '</table>';
}

  include "footer1.php";
  echo '</body></html>';

?>
