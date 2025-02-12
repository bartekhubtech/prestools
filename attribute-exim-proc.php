<?php
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_POST;
 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }
// var_dump($_POST); die("dsa");
if(!isset($input["actione"])) die("No data");
if(($input["actione"] == "import") || ($input["actione"] == "list"))
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script>
function newwin()
{ nwin = window.open("","_blank", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script></head><body>
<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';


  /* get default language */
  $query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $default_lang = $row['value'];

  $languages = array();
  $query = "SELECT id_lang,iso_code FROM ". _DB_PREFIX_."lang WHERE active=1";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $languages[] = $row["id_lang"];
  }
  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }

if($input["actione"] == "export")
{ $fields = array("name","id_attribute","color","position");
  $query = "SELECT name FROM ". _DB_PREFIX_."attribute_group_lang WHERE id_attribute_group=".intval($input["attribute_group"])." AND id_lang=".$default_lang;
  $res=dbquery($query);
  $row=mysqli_fetch_array($res);
//  var_dump($row); die("dsa ".$query." ".mysqli_num_rows($res));
  foreach($languages AS $lang)
	$fields[] = "name-".$lang;
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=attributes-'.$row["name"].'-'.date('Y-m-d-Gis').'.csv');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');

// According to a comment on php.net the following can be added here to solve Chinese language problems
// fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

  // "*********************************************************************";
  $csvline = array();  // array for the fputcsv function
  for($i=0; $i<sizeof($fields); $i++)
  { $csvline[] = $fields[$i];
  }
  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);
  
  $query = "SELECT a.id_attribute, color, position, name FROM ". _DB_PREFIX_."attribute a";
  $query .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l ON a.id_attribute=l.id_attribute AND l.id_lang=".$default_lang;
  $query .= " WHERE id_attribute_group=".intval($input["attribute_group"]);
  $query .= " ORDER BY position";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $csvline = array();
	$csvline[] = $row["name"]; 
	$csvline[] = $row["id_attribute"]; 	
	$csvline[] = $row["color"]; 
	$csvline[] = $row["position"];
	foreach($languages AS $lang)
	{ $dquery = "SELECT name, id_lang FROM ". _DB_PREFIX_."attribute_lang";
      $dquery .= " WHERE id_attribute=".$row["id_attribute"];
	  $dquery .= " AND id_lang=".$lang;
      $dres=dbquery($dquery);
	  if(mysqli_num_rows($dres) == 0)
		 $csvline[] = '';  
	   else
	   { $drow=mysqli_fetch_array($dres);
         $csvline[] = $drow["name"]; 
	   }
	}
    publish_csv_line($out, $csvline, $separator);
  }
  fclose($out);
}
else if($input["actione"] == "import") /* import */
{ // var_dump($_FILES);
  $uploadOk = 1;
  $FileType = pathinfo($_FILES["fileToUpload"]["name"],PATHINFO_EXTENSION);

  if(isset($_POST["submit"])) 
	 echo "Uploading ".$_FILES["fileToUpload"]["name"]."<br>";
  else
     colordie("Error: No file provided");

  if ($_FILES["fileToUpload"]["size"] > 500000) // Check file size
	  colordie("Sorry, your file is too large.");
  if($FileType != "csv") 	
      colordie("Sorry, only CSV files are allowed.".$FileType);
  if ($uploadOk == 0) 
     colordie("Sorry, your file was not uploaded.");

  if(!($fp = fopen($_FILES["fileToUpload"]["tmp_name"], "r")))
	 colordie("Error opening csv file");
  $header = fgetcsv($fp, 10000, $separator);
  if($header[0] != "name")
  { if(substr($header[0],0,4) == "name")
      colordie('It looks like you chose the wrong separator.');
    else
      colordie('The first column must contain the "name" column.');
  }
  $csvblock = array();
  while (($row = fgetcsv($fp, 10000, $separator)) !== FALSE)
  {  $csvblock[] = array_combine_special($header, $row);
  }
  fclose($fp);
  ini_set('auto_detect_line_endings',FALSE);
  print_r($csvblock);
  
  $atgroup = (int)$_POST["attribute_group"];
  if($atgroup == 0) colordie("No attribute group");
  
  $query = "SELECT is_color_group FROM ". _DB_PREFIX_."attribute_group";
  $query .= " WHERE id_attribute_group=".$atgroup;
  $res = dbquery($query);
  $row=mysqli_fetch_assoc($res);
  $is_color_group = $row["is_color_group"];
  echo "colorgroup=".$is_color_group."<br>";
  
  $attributes = array();
  $query = "SELECT id_attribute, color,position FROM ". _DB_PREFIX_."attribute";
  $query .= " WHERE id_attribute_group=".$atgroup." ORDER BY position";
  $res = dbquery($query);
  $poscount = $attcount = mysqli_num_rows($res); /* used for positon with inserts */
  while ($row=mysqli_fetch_assoc($res)) 
  { $lquery = "SELECT id_lang, name FROM ". _DB_PREFIX_."attribute_lang";
    $lquery .= " WHERE id_attribute=".$row["id_attribute"]." ORDER BY id_lang";
    $lres = dbquery($lquery);
    while ($lrow=mysqli_fetch_assoc($lres))
	  $row["name-".$lrow['id_lang']] = $lrow['name'];
    if(!isset($row["name-".$default_lang])) colordie("Default name was not defined for lang ".$default_lang."-".print_r($row));
    if($row["name-".$default_lang] != "")
      $name = $row["name-".$default_lang];
    else
      $name = "id--".$row["id_attribute"];		
    $attributes[$name] = $row;
  }

  $shops = array();
  foreach($_POST["id_shop"] AS $shoppost)
    $shops[] = $shoppost;
  if(sizeof($shops)==0)
	  colordie("No shops");
  
  foreach($csvblock AS $line)
  {   $id_attribute = 0;
	  if(isset($attributes[$line["name"]]))
		$id_attribute = $attributes[$line["name"]]["id_attribute"];
	  else if((isset($line["id_attribute"])) && isset($attributes["ID--".$line["id_attribute"]]))
		$id_attribute = $line["id_attribute"];
	  if($id_attribute != 0)  /* if existing entry */
	  { if((isset($line["color"])) || (isset($line["position"])))
		{ $uquery = "UPDATE "._DB_PREFIX_."attribute SET ";
		  if(isset($line["color"])) $uquery .= " color='".mysqli_real_escape_string($conn, $line["color"])."'";
		  if((isset($line["color"])) && (isset($line["position"]))) $uquery .= ",";
		  if(isset($line["position"])) $uquery .= " position='".mysqli_real_escape_string($conn, $line["position"])."'";
		  $uquery .= " WHERE id_attribute=".$id_attribute;
		  $ures = dbquery($uquery);
		}
		foreach($languages AS $lang)
		{ $query = "SELECT * FROM ". _DB_PREFIX_."attribute_lang";
		  $query .= " WHERE id_attribute=".$id_attribute." AND id_lang=".$lang;
		  $res = dbquery($query);
		  if(mysqli_num_rows($res) > 0)
		  { if(isset($line["name-".$lang]) && ($line["name-".$lang] != ""))
			{ $uquery = "UPDATE ". _DB_PREFIX_."attribute_lang";
			  $uquery .= " SET name='".mysqli_real_escape_string($conn, $line["name-".$lang])."'";
			  $uquery .= " WHERE id_attribute=".$id_attribute." AND id_lang=".$lang;
			  $ures = dbquery($uquery);	
			}
		  }
		  else /* shouldn't happen */
		  { $iquery = "INSERT IntO ". _DB_PREFIX_."attribute_lang SET id_lang=".$lang;
			$iquery .= ", id_attribute=".$id_attribute.", name=";
			if(isset($line["name-".$lang]) && ($line["name-".$lang]!="")) $iquery .= "'".mysqli_real_escape_string($conn, $line["name-".$lang])."'";
		    else $iquery .= "'".mysqli_real_escape_string($conn, $line["name"])."'";
		    $ires = dbquery($iquery);		  
		  }
		}
	  }
	  else /* new attribute */
	  { 
	    $iquery = "INSERT INTO ". _DB_PREFIX_."attribute SET color=";
		if(isset($line["color"]) && ($line["color"] != "")) $iquery .= "'".mysqli_real_escape_string($conn, $line["color"])."'";
		else if($is_color_group) $iquery .= "'#000000'";
		else $iquery .= "''";
		$iquery .= ", id_attribute_group=".$atgroup.", position=";
		if(isset($line["position"]) && ($line["position"]!="")) $iquery .= "'".mysqli_real_escape_string($conn, $line["position"])."'";
		else $iquery .= "'".$poscount++."'";
		$ires = dbquery($iquery);
		$newid = mysqli_insert_id($conn);
		
		foreach($languages AS $lang)
		{ $iquery = "INSERT INTO ". _DB_PREFIX_."attribute_lang SET id_lang=".$lang;
		  $iquery .= ", id_attribute=".$newid.", name=";
	 	  if(isset($line["name-".$lang]) && ($line["name-".$lang]!="")) $iquery .= "'".mysqli_real_escape_string($conn, $line["name-".$lang])."'";
		  else $iquery .= "'".mysqli_real_escape_string($conn, $line["name"])."'";
		  $ires = dbquery($iquery);		  
		}
		foreach($shops AS $shop)
		{ $iquery = "INSERT INTO ". _DB_PREFIX_."attribute_shop SET id_shop=".$shop;
		  $iquery .= ", id_attribute=".$newid;
		  $ires = dbquery($iquery);		  
		}
	  }
  }
  $query = "SELECT * FROM ". _DB_PREFIX_."attribute WHERE id_attribute_group=".$atgroup;
  $query .= " ORDER BY position,id_attribute";
  $res = dbquery($query);
  $x=0;
  while ($row=mysqli_fetch_assoc($res)) 
  { if($row["position"] != $x)
	{ $uquery = "UPDATE ". _DB_PREFIX_."attribute";
  	  $uquery .= " SET position='".$x."'";
	  $uquery .= " WHERE id_attribute=".$row["id_attribute"];
   	  $ures = dbquery($uquery);	
	}
	$x++;
  }
}
else if($input["actione"] == "list")
{ $query = "SELECT a.id_attribute, color, position, name FROM ". _DB_PREFIX_."attribute a";
  $query .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l ON a.id_attribute=l.id_attribute AND l.id_lang=".$default_lang;
  $query .= " WHERE id_attribute_group=".intval($input["attribute_group"]);
  $query .= " ORDER BY position";
  $res = dbquery($query);
  $tmp = "";
  while ($row=mysqli_fetch_assoc($res)) 
  { $tmp .= $row["name"];
    $dquery = "SELECT name, id_lang FROM ". _DB_PREFIX_."attribute_lang";
    $dquery .= " WHERE id_attribute=".$row["id_attribute"];
    $dquery .= " ORDER BY id_lang";
    $dres=dbquery($dquery);
    while ($drow=mysqli_fetch_array($dres))
    { if($drow["id_lang"] != $default_lang)
		$tmp .= " - ".$drow["name"];
	}
    $tmp .= "<br>";
  }
  echo "<script>parent.dynamo2('".str_replace("'","\\'",$tmp)."');</script>";
}
mysqli_close($conn);

function publish_csv_line($out, $csvline, $separator)
{ fputcsv3($out, $csvline, $separator);
}
  
/* a version of this function was found here: http://php.net/manual/en/function.array-combine.php */
function array_combine_special($a, $b) 
{   $acount = count($a);
    $bcount = count($b);
	// more headers than row fields
	if ($acount > $bcount) {
		$more = $acount - $bcount;
		// how many fields are we missing at the end of the second array?
		// Add empty strings to ensure arrays $a and $b have same number of elements
		$more = $acount - $bcount;
		for($i = 0; $i < $more; $i++) {
			$b[] = "";
		}
	}
    return array_combine($a, $b);
}
