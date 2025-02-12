<?php 
if(!@include 'approve.php')
   die( "approve.php was not found");
$input = $_GET;
if(!isset($input['id_lang'])) $input['id_lang']="";
set_time_limit ( 60 );
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Remove Extra Language Data</title>
</head>
<body>
<h1>Prestashop Remove Extra Language Data</h1>
<h2>This script removed translation data for languages that had been previously removed!</h2>
<?php

$query = "SELECT id_lang FROM ". _DB_PREFIX_."lang";
$res=dbquery($query);
$languages = array();
while ($row=mysqli_fetch_array($res)) 
  $languages[] = $row["id_lang"];

$language_tables = array();
$query = "SHOW TABLES";
$res = db($query); 
while($row = mysqli_fetch_row($res))
{ $squery = "SHOW COLUMNS FROM ".$row[0]; /* look whether this table has an id_lang field */
  $sres = dbquery($squery); 
  if(!$sres) continue;
  while($srow = mysqli_fetch_array($sres))
  { if($srow[0] == "id_lang")
	{ $dquery = "DELETE FROM ".$row[0]." WHERE id_lang NOT IN (".implode(",",$languages).")"; /* if so delete the rows for non-exsting languages */
	  echo "<br>".$dquery;
	  $dres = dbquery($dquery); 
	} 
  }
}

?>