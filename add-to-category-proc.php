<?php 
/* custommade for Litouwen */
if(isset($_GET["scriptsrun"]) &&($_GET["scriptsrun"] == "all"))
{ $dfjhgjfsdj = "jsdfhkdsjfswqep";
  include("approve.php");
  $task = "run";
  $_POST['submittedrow'] = "all";
  $verbose = "true";
}
else if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";

echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<script>
function newwin()
{ nwin = window.open("","NewWindow", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script></head><body>';

extract($_POST);

 if(!isset($task)) exit("Nothing to do!");
 if(isset($_POST['submittedrow']))
   echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';
 else
   echo "<br>Go back to <a href='add-to-category.php'>add-to-category page</a><p/>".$reccount." Records submitted.<br/>";

 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }
 else if(isset($_POST['submittedrow']) && is_numeric($_POST['submittedrow']))
 { if($task == "save")
      change_rec($_POST['submittedrow']); 
   else if($task == "run")
      run_rec($GLOBALS['id_catcreator'.$_POST['submittedrow']]);    
 }
 else if($task == "save")
 { for($i=0; $i<$reccount; $i++)
   { change_rec($i); 
   }
 }
 else if(($task == "run") && ($_POST['submittedrow'] == "all"))
 { $tquery = "SELECT id_catcreator FROM "._DB_PREFIX_._PRESTOOLS_PREFIX_."catcreator ORDER BY id_catcreator";
   $tres=dbquery($tquery);
   while($trow=mysqli_fetch_array($tres))
	 run_rec($trow["id_catcreator"]);    
 }
 
if(isset($_GET["scriptsrun"]) &&($_GET["scriptsrun"] == "all"))
{ mysqli_close($conn);
}

function change_rec($x)
{ global $id_lang, $errstring, $verbose, $conn,$deleter, $saved_id;
  $saved_id = 0;
  echo "*";
  if((!isset($GLOBALS['id_catcreator'.$x])) || (!is_numeric($GLOBALS['id_catcreator'.$x]))) 
    { if ($verbose=="true") echo "No changes"; return;}
  echo $x.": ";
  $id_catcreator = intval($GLOBALS['id_catcreator'.$x]);
  
  $deleter = intval($GLOBALS['deleter'.$x]);
  if($deleter)
  { if($id_catcreator)
	{ $dquery = "DELETE FROM "._DB_PREFIX_._PRESTOOLS_PREFIX_."catcreator WHERE id_catcreator='".$id_catcreator."'";
      $dres=dbquery($dquery);
	}
	return;
  }
  $ccupdates = "";  	 /* catcreator table */
  if(isset($GLOBALS['attrfeat'.$x]))
  { $type = substr($GLOBALS['attrfeat'.$x],0,1);
    $group = substr($GLOBALS['attrfeat'.$x],1);
	if($type == "a") $type = "attribute"; 
	else if($type=="f") $type = "feature"; 
	else colordie("Illegal type: ".$type." for ".$x);
    $ccupdates .= " cond_type='".$type."',";
    $ccupdates .= " cond_group='".intval($group)."',";
  }
  if(isset($GLOBALS['cond_value'.$x]))
    $ccupdates .= " meta_title='".intval($GLOBALS['attrfeatvalue'.$x])."',";
  if(isset($GLOBALS['base_category'.$x]))
    $ccupdates .= " base_category='".intval($GLOBALS['base_category'.$x])."',";
  if(isset($GLOBALS['attrfeatvalue'.$x]))
    $ccupdates .= " cond_value='".intval($GLOBALS['attrfeatvalue'.$x])."',";
  if(isset($GLOBALS['subcats'.$x])) $subcats='1'; else $subcats='0';
    $ccupdates .= " subcats='".$subcats."',";
  if(isset($GLOBALS['target_category'.$x]))
    $ccupdates .= " target_category='".intval($GLOBALS['target_category'.$x])."',";
  if($ccupdates != "")
  { if($id_catcreator)
	{ $query = "UPDATE ". _DB_PREFIX_._PRESTOOLS_PREFIX_."catcreator SET ".substr($ccupdates,0,strlen($ccupdates)-1)." WHERE id_catcreator='".$id_catcreator."'";
      dbquery($query); echo "IUIUIU".$query;
	}
	else
	{ $query = "INSERT INTO ". _DB_PREFIX_._PRESTOOLS_PREFIX_."catcreator SET ".substr($ccupdates,0,strlen($ccupdates)-1);
      dbquery($query);
	  $saved_id = mysqli_insert_id ($conn);
	}
  } 
}

function run_rec($id_catcreator)
{ global $id_lang, $errstring, $verbose, $conn;
  echo "#";
  $query = 'SELECT * FROM `'._DB_PREFIX_._PRESTOOLS_PREFIX_.'catcreator` WHERE id_catcreator='.$id_catcreator;
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0) colordie("Record not found!");
  $row=mysqli_fetch_array($res);
  $target = $row["target_category"];
  $base = $row["base_category"];
  if($base == "0")
  { $aquery = 'SELECT cp.id_product FROM `'._DB_PREFIX_.'product` cp';
    $awhere = ' WHERE 1';
  }
  else if($row["subcats"])
  { $categories = array();
    get_subcats($base, $categories);
    $aquery = 'SELECT cp.id_product FROM `'._DB_PREFIX_.'category_product` cp';
	$awhere = ' WHERE cp.id_category in ('.join(',',$categories).')';
  }
  else
  { $aquery = 'SELECT cp.id_product FROM `'._DB_PREFIX_.'category_product` cp';
    $awhere = ' WHERE cp.id_category='.$base;
  }
  if($row["cond_type"] == "attribute")
  { $awhere .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pa 
   LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pac ON pa.id_product_attribute=pac.id_product_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute_lang al ON pac.id_attribute = al.id_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute atr ON pac.id_attribute = atr.id_attribute 
   WHERE pa.id_product=cp.id_product AND atr.id_attribute_group='".$row["cond_group"]."'";
   if($row["cond_value"]) /* if value chosen */
     $awhere .= " AND atr.id_attribute = '".$row["cond_value"]."')";
   else  /* any value allowed */
     $awhere .= ")";
  } 
  else if($row["cond_type"] == "feature")
  { $awhere .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."feature_product fp 
   LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value 
   LEFT JOIN ". _DB_PREFIX_."feature_value_lang fvl ON fv.id_feature_value=fvl.id_feature_value
   WHERE fp.id_product=cp.id_product AND fp.id_feature=".$row["cond_group"];
   if($row["cond_value"]) /* if value chosen */
     $awhere .= " AND fv.id_feature_value = ".$row["cond_value"].")";
   else  /* any value allowed */
     $awhere .= ")";
  }
  $awhere .= " AND NOT EXISTS(SELECT NULL FROM "._DB_PREFIX_."category_product cx 
    WHERE cx.id_product=cp.id_product AND cx.id_category=".$target.")";
  $res = dbquery($aquery.$awhere);
  echo "\r\n".mysqli_num_rows($res)." Found";
  $mquery = "SELECT MAX(position) AS maxposition FROM "._DB_PREFIX_."category_product WHERE id_category=".$target;
  $mres = dbquery($mquery);
  $mrow = mysqli_fetch_array($mres);
  $position = $mrow["maxposition"];
  while($row = mysqli_fetch_array($res))
  { $iquery = 'INSERT IGNORE INTO '. _DB_PREFIX_.'category_product SET id_category="'.$target.'"';
    $iquery .= ',id_product="'.$row["id_product"].'",position="'.++$position.'"';
    $ires = dbquery($iquery);
  }
  echo "RUNNING";
}

echo "<br>Finished successfully!<p>Go back to <a href='add-to-category.php'>add-to-category page</a>";

if($task == "save")
{ if(isset($_POST['submittedrow']))
  { echo "<script>if(parent) parent.reg_unchange(".intval($_POST['submittedrow']).",".$deleter.",".$saved_id.");</script>";
  }
  else if($verbose!="true")
  { echo "<script>location.href = 'add-to-category.php';</script>";
  }
}
mysqli_close($conn);
echo "</body></html>";


