<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_POST;
 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }

if(!isset($input['mytable']) || !isset($input['mycols']) || !isset($input['mrows']))
  colordie("Insufficient arguments");
$mytable = preg_replace('/[^A-Za-z0-9\-\_]+/','',$input['mytable']);
$mycols = $input['mycols'];
$query = "SHOW COLUMNS FROM ".$mytable;
$res = dbquery($query);
if(!$res) colordie("Illegal table ".$mytable);
$cols = [];
while($row = mysqli_fetch_array($res))
	$cols[] = $row[0];
foreach($mycols AS $col)
{ if(!in_array($col, $cols))
	 colordie("Illegal col ".$col);
}
$mrows = $input['mrows'];
foreach($mrows AS $mrow)
{ $mrow = preg_replace('/[^a-z0-9,]+/','',$mrow);
  $mflds = explode(",",$mrow);
  $combis = array_combine($mycols,$mflds); /* mycols become keys */
  $query = "SELECT *, count(*) AS cntr FROM ".$mytable." WHERE 1"; 
  foreach($combis AS $key => $val)
  { $val = hex2bin($val);
	$query .= " AND `".$key."`='".mescape($val)."'";
  }
  $query .= " GROUP BY `".implode("`,`",$mycols)."`";
  $query .= " HAVING cntr > 1";
  $res = dbquery($query);
  if(mysqli_num_rows($res) != 1)
	colordie("Invalid subquery ".$query);
  $row = mysqli_fetch_array($res);
  $cntr = intval($row["cntr"]);

  $tquery = "DELETE FROM ".$mytable." WHERE 1";
  foreach($combis AS $key => $val)
  { $val = hex2bin($val);
	$tquery .= " AND `".$key."`='".mescape($val)."'";
  }
  $tquery .= " LIMIT ".($cntr-1);
  $tres = dbquery($tquery);
}

if(isset($_POST['urlsrc']) && ($_POST['urlsrc'] != "")) // note that for security reason we disabled the referrer [for some browsers] in product-edit
{ $refscript = $_POST['urlsrc'];
}
else if((isset($_SERVER['HTTP_REFERER'])) && ($_SERVER['HTTP_REFERER'] != ""))
  $refscript = $_SERVER['HTTP_REFERER'];
else
  $refscript = "";
if($verbose!="true")
{ if($refscript != "")
    echo "<script>location.href = '".$refscript."';</script>";
}
if($refscript != "")
    echo "<a href='".$refscript."'>Go back to edit page</a>";
