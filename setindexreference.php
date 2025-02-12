<?php
if(!@include 'approve.php') die( "approve.php was not found!");

if(!isset($_GET["alpha"]) || ($_GET["alpha"] != "Gamma")) die("Nothing to do");

$fp = fopen("indexreference.php", "w");
if(!$fp) die("Error opening file");

fwrite($fp, "<?php ");
$tmp = '$indexlist = array(
';
$first = 1;
$prefixlen = strlen(_DB_PREFIX_);
$res = dbquery("SHOW FULL TABLES");
while($row = mysqli_fetch_row($res))
{ if($row[1] != "BASE TABLE") continue;
  if(substr($row[0],0,$prefixlen) != _DB_PREFIX_) 
	  continue;
  $tablename = substr($row[0],$prefixlen);
  $ifirst = 1;
  if($first) $first=0; else $tmp .= ","; 
  $tmp .= '"'.$tablename.'" => array(';
  $ires = dbquery("SHOW INDEXES FROM ".$row["0"]);
  $tabkeys = array();
  while($irow = mysqli_fetch_assoc($ires))
  { if(!isset($tabkeys[$irow["Key_name"]]))
	  $tabkeys[$irow["Key_name"]] = array();
    $tabkeys[$irow["Key_name"]][] = $irow["Column_name"];
  }	  
  foreach($tabkeys AS $tabkey => $tabfields)
  { if($ifirst) $ifirst=0; else $tmp .= ","; 
	$tmp .=  '"'.$tabkey.'" => array(';
	$ffirst = 1;
	foreach($tabfields AS $tabfield)
	{ if($ffirst) $ffirst=0; else $tmp .= ","; 
      $tmp .= '"'.$tabfield.'"';
	}
    $tmp .= ")";
  }
  $tmp .= ")
";
}
$tmp .= ");";
fwrite($fp, $tmp);
fclose($fp);
  
  