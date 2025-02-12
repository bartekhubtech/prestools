<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";
if($_POST["separator1"]=="comma") $separator1 = ","; else $separator1 = ";";
if($_POST["separator2"]=="comma") $separator2 = ","; else $separator2 = ";";
 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }

	if(!isset($_FILES["upload1"]) || !isset($_FILES["upload2"])) colordie("You must select two csv files"); 
   
    /* upload file 1 */
    $res = dbquery('DROP TABLE IF EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.'customermail');
    if (($handle = fopen($_FILES["upload1"]["tmp_name"], "r")) === FALSE) colordie("Error opening file");
    $csvvalues = fgetcsv($handle, 1000, $separator1);
	if(sizeof($csvvalues)==1)
	{ if($separator1 == ";") $sep=","; else $sep = ";";
	  fseek($handle,0);
      $csvvaluesx = fgetcsv($handle, 1000, $sep);
	  if(sizeof($csvvaluesx)>1) colordie("Your first separator is wrong. It should be ".$sep);
	}
	$data = fread($handle,1000);
	$data = fread($handle,1000); /* it is important to use double quotes in the next line. Otherwise it won't work */
	if(strpos(substr($data,1),"\r\n") > 0) $term = "\r\n"; ELSE $term = "\n";
    $fields1 = array();
	foreach($csvvalues AS $csvvalue)
	{ $fields1[] = strtolower(preg_replace('/[\s\"]/', '',$csvvalue));
	}
    $creater = 'CREATE TABLE `'._DB_PREFIX_._PRESTOOLS_PREFIX_.'customermail` (';
    foreach($fields1 AS $field)
    { $creater .= $field." VARCHAR(200),";
    }
//    $creater = substr_replace($creater,")",-1);
    $creater .= " PRIMARY KEY(email))";
    if(!$res = dbquery($creater)) colordie("Error creating datatable");
    $tmpname = str_replace("\\","/",$_FILES["upload1"]["tmp_name"]);
    $query = "LOAD DATA INFILE '".$tmpname."' IGNORE"; /* ignore means that duplicates are skipped; alternative is REPLACE */
    $query .= " INTO TABLE `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail`";
    $query .= " FIELDS TERMINATED BY '".$separator1."' ENCLOSED BY '\"' LINES TERMINATED BY '".$term."' IGNORE 1 ROWS";
    dbquery($query);
	dbquery("DELETE FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail` WHERE email=''");

     /* upload file 2 */
    $res = dbquery('DROP TABLE IF EXISTS '._DB_PREFIX_._PRESTOOLS_PREFIX_.'customerduplo');
    if (($handle = fopen($_FILES["upload2"]["tmp_name"], "r")) === FALSE) colordie("Error opening file2");
    $csvvalues = fgetcsv($handle, 1000, $separator2);
	if(sizeof($csvvalues)==1)
	{ if($separator2 == ";") $sep=","; else $sep = ";";
	  fseek($handle,0);
      $csvvaluesx = fgetcsv($handle, 1000, $sep);
	  if(sizeof($csvvaluesx)>1) colordie("Your second separator is wrong. It should be ".$sep);
	}
	$data = fread($handle,1000); /* it is important to use double quotes in the next line. Otherwise it won't work */
	if(strpos(substr($data,1),"\r\n") > 0) $term = "\r\n"; ELSE $term = "\n";
	fclose($handle);
    $fields2 = array();
	foreach($csvvalues AS $csvvalue)
		 $fields2[] = strtolower(preg_replace('/[\s\"]/', '',$csvvalue));
    $creater = 'CREATE TABLE `'._DB_PREFIX_._PRESTOOLS_PREFIX_.'customerduplo` (';
    foreach($fields2 AS $field)
    { $creater .= $field." VARCHAR(200),";
    }
//    $creater = substr_replace($creater,")",-1);
    $creater .= " PRIMARY KEY(email))";
    if(!$res = dbquery($creater)) colordie("Error creating datatable");
    $tmpname = str_replace("\\","/",$_FILES["upload2"]["tmp_name"]);
    $query = "LOAD DATA INFILE '".$tmpname."' IGNORE"; /* ignore means that duplicates are skipped; alternative is REPLACE */
    $query .= " INTO TABLE `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customerduplo`";
    $query .= " FIELDS TERMINATED BY '".$separator2."' ENCLOSED BY '\"' LINES TERMINATED BY '".$term."' IGNORE 1 ROWS";
    dbquery($query);
	dbquery("DELETE FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customerduplo` WHERE email=''");
	
	/* find matching keys */
	$commonfields = array_intersect($fields1,$fields2);
	
	if($_POST["operation"] == "add")
	{ $query = "SELECT * FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customerduplo`";
	  $query .= " WHERE email NOT IN";
      $query .= " (SELECT email FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail`)";
	  $res = dbquery($query);
	  while ($row=mysqli_fetch_assoc($res))
	  { $squery = "INSERT INTO `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail` SET ";
        foreach($commonfields AS $field)
		  $squery.= $field."='".mescape($row[$field])."',";
		$squery = substr_replace($squery,"",-1);
		dbquery($squery);
	  }
	}
	else if($_POST["operation"] == "subtract")
	{ $query = "DELETE FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail`";
	  $query .= " WHERE email IN";
      $query .= " (SELECT email FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customerduplo`)";
	  $res = dbquery($query);
	}
	else if($_POST["operation"] == "intersection")
	{ $query = "DELETE FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail`";
	  $query .= " WHERE email NOT IN";
      $query .= " (SELECT email FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customerduplo`)";
	  $res = dbquery($query);
	}
	
	/* now we make the csv file */
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=customer-'.date('Y-m-d-Gis').'.csv');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	
    $csvline = array();
	foreach($fields1 AS $field)
	  $csvline[] = $field;
    $out = fopen('php://output', 'w');
    publish_csv_line($out, $csvline, $separator1);
	$query = "SELECT * FROM `"._DB_PREFIX_._PRESTOOLS_PREFIX_."customermail`";
	$res = dbquery($query);
	while ($row=mysqli_fetch_assoc($res))
	{ $csvline = array();
	  foreach($fields1 AS $field)
		$csvline[] = $row[$field];
	  publish_csv_line($out, $csvline, $separator1);
	}	
    fclose($out); 
	mysqli_close($conn);

function publish_csv_line($out, $csvline, $separator)
{ fputcsv3($out, $csvline, $separator);
}
