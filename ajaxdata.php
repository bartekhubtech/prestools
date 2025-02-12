<?php
if(!@include 'approve.php') die( "approve.php was not found!");
/* Task can have the following values:
	prodname
	prodcombis
	prodcopycombis
	copycombifilter
	deletecombifilter
	checkproducts
	checkcategories
	checkmanufacturers
	getgroupattributes
*/
$task = $_GET['task'];
$myids = preg_replace('/[^0-9,-]*/',"",$_GET['myids']);
if(in_array($task, array("getattrfeat","getattributeoptions")))
{ if(isset($_GET['group']))
	$group = preg_replace('/[^0-9afAB]*/',"",$_GET['group']); 
  else die("No group provided!");
}
$id_lang = intval($_GET['id_lang']);
if(isset($_GET['startrec'])) $startrec=intval($_GET['startrec']); else $startrec="0";
if(isset($_GET['numrecs'])) $numrecs=intval($_GET['numrecs']); else $numrecs="500";

if($task == "prodname")
{ $query = "SELECT name FROM "._DB_PREFIX_."product_lang WHERE id_product='".mysqli_real_escape_string($conn, $myids)."' AND id_lang='".mysqli_real_escape_string($conn, $id_lang)."'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0) { echo "Product not found"; exit(0); }
//Not true in multishop  if(mysqli_num_rows($res) != 1) { echo "Rowcount error. This shouldn't happen."; exit(0); } 
  $data = mysqli_fetch_array($res);
  $tmp = $data['name'];
  $query = "SELECT count(*) FROM "._DB_PREFIX_."product_attribute WHERE id_product='".mysqli_real_escape_string($conn, $myids)."'";
  $res=dbquery($query);
  list($combination_count) = mysqli_fetch_row($res); 
  echo $tmp." (this product has ".$combination_count." combinations)";
}
else if(($task == "prodcombis") || ($task == "prodcopycombis"))
{ $query = "SELECT name FROM "._DB_PREFIX_."product_lang WHERE id_product='".mysqli_real_escape_string($conn, $myids)."' AND id_lang='".mysqli_real_escape_string($conn, $id_lang)."'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0) { echo "Product not found\n\n"; exit(0); }
//Not true in multishop  if(mysqli_num_rows($res) != 1) { echo "Rowcount error. This shouldn't happen."; exit(0); } 
  $data = mysqli_fetch_array($res);
  $tmp = $data['name']."\n"; /* newline serves as separator */
  
  $bquery = "SELECT id_product_attribute FROM ". _DB_PREFIX_."product_attribute";
  $bquery .= " WHERE id_product='".mysqli_real_escape_string($conn, $myids)."'";
  $bres=dbquery($bquery);
  if(mysqli_num_rows($bres)==0) {echo $tmp."\n"; exit(0);}
  if($task == "prodcopycombis")
  { $tmp .= ' Copy <input type=radio name=sourcetype value="all" checked onchange="changeMethod(0)"> all '.mysqli_num_rows($bres).' combinations. ';
	$tmp .= ' &nbsp; &nbsp; <input type=radio name=sourcetype value="1" onchange="changeMethod(1)"> a selection<br>';
	$tmp .= '<span id=combinationlist style="display:none">(un)select all <input type=checkbox checked onchange="flipcheckboxes(this);"><br>';
  }
  else
  { $tmp.=" ".mysqli_num_rows($bres)." combinations<br>";
  	$tmp .= ' &nbsp; (un)select all <input type=checkbox onchange="flipcheckboxes(this);"><br>';
  }
  $sortblock = array();
  while($brow = mysqli_fetch_assoc($bres))
  { $paquery = "SELECT GROUP_CONCAT(CONCAT(gl.name,': ',l.name)) AS nameblock, GROUP_CONCAT(c.id_attribute) AS attr_block, pa.id_product_attribute, pa.default_on";
    $paquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
    $paquery .= " WHERE pa.id_product_attribute='".$brow['id_product_attribute']."' GROUP BY pa.id_product_attribute";
    $paquery .= " LIMIT ".$startrec.",".$numrecs;
    $pares=dbquery($paquery);
    $sortblock[] = mysqli_fetch_array($pares);
  }
  sort($sortblock);
  foreach ($sortblock AS $parow)
  { $star = "";
	/* note that for the copy we use the id_product_attribute and for the delete the attribute block. */
	/* the attribute block is a comma separated list of attributes */
    if($parow["default_on"]=="1") $star = "*";
    if($task == "prodcopycombis")	
       $tmp .= '<input type=checkbox name="combis[]" value="'.mysort($parow["id_product_attribute"]).'" onclick="checker(this,event)" checked>'.$parow["nameblock"]." ".$parow["id_product_attribute"].$star."<br>";
	else   /* $task == "prodcombis" */
       $tmp .= '<input type=checkbox name="combis[]" value="'.mysort($parow["attr_block"]).'" onclick="checker(this,event)">'.$parow["nameblock"]." ".$parow["id_product_attribute"].$star."<br>";
  }
  if($task == "prodcopycombis")	
    $tmp .= '</span>';
  $tmp = $tmp."\n";
  
  $aquery = "SELECT pc.id_attribute, l.name, a.id_attribute_group,gl.name AS groupname";
  $aquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
//  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
  $aquery .= " WHERE pa.id_product='".mysqli_real_escape_string($conn, $myids)."'";
  $aquery .= " GROUP BY pc.id_attribute ORDER BY a.id_attribute_group,a.position";
  
  $ares=dbquery($aquery);
  $group = "0";
  $groups = array();
  $tmp .= '<table>';
  while ($arow=mysqli_fetch_array($ares))
  { if($arow["id_attribute_group"]!= $group)
	{ if($group != 0) $tmp .= "</select></td>";
	  if(sizeof($groups)==1) $tmp .= '<td><button onclick="filtercombis(); return false;">Filter</button></td>';
	  if($group != 0) $tmp .= "</tr>";
  	  $group = $arow["id_attribute_group"];
	  $groups[] = $group;
	  $active = 0;
	  $tmp .= "<tr><td>".$arow["groupname"]."</td><td><select name=group".$arow["id_attribute_group"]."><option value=0>All</option>";
	}
	$tmp .= '<option value="'.$arow["id_attribute"].'">'.$arow["name"].'</option>';
  }
  $tmp .= "</select></td>";
  if(sizeof($groups)>0) $tmp .= '<td><button onclick="filtercombis(); return false;">Apply filter</button></td>';
  $tmp .= "</tr></table>";
  $tmp .= '<input type=hidden name=groups value="'.implode(",",$groups).'">';
  
  echo $tmp;
}
else if(($task == "copycombifilter") || ($task == "deletecombifilter"))
{ /* first get all the attributes and their groups for this product */
  $aquery = "SELECT pc.id_attribute, l.name, a.id_attribute_group,gl.name AS groupname";
  $aquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pc.id_attribute=at.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
  $aquery .= " WHERE pa.id_product='".intval($myids)."' GROUP BY pc.id_attribute ORDER BY a.id_attribute_group";
  $ares=dbquery($aquery);
  
  $group = "0";
  $groups = array();
  $active =0;
  $blockers = array();
  /* compare these attributes with the filters of the previous submit: */
  /*  rebuild the selects and fill the blockers list */ 
  /* this is a simplified version of the routine in combi-edit.php */
  while ($arow=mysqli_fetch_array($ares))
  { if($arow["id_attribute_group"]!= $group)
	{ if($group != 0) echo "</select></td></tr>";
  	  $group = $arow["id_attribute_group"];
	  $groups[] = $group;
	  $active = 0;
	  if(isset($_GET["group".$arow["id_attribute_group"]]))
	  { $active = $_GET["group".$arow["id_attribute_group"]];
	  }
	}
	if(($active != $arow["id_attribute"]) && ($active != 0))
	  $blockers[] = $arow["id_attribute"];
  }
  
  /* now get the list of combinations for this product - implementing the blockers */
  $bquery = "SELECT pa.id_product_attribute FROM ". _DB_PREFIX_."product_attribute pa";
  $bquery .= " WHERE pa.id_product='".mysqli_real_escape_string($conn, $myids)."'";
  if(sizeof($blockers) > 0)
    $bquery .= " AND NOT EXISTS (SELECT * FROM ". _DB_PREFIX_."product_attribute_combination pc2 WHERE pa.id_product_attribute=pc2.id_product_attribute AND pc2.id_attribute IN ('".implode("','", $blockers)."'))";
  $bres=dbquery($bquery);
  $tmp = "";
  if($task == "copycombifilter")
  { $tmp .= ' Copy <input type=radio name=sourcetype value="all" checked onchange="changeMethod(0)"> all '.mysqli_num_rows($bres).' combinations. ';
	$tmp .= ' &nbsp; &nbsp; <input type=radio name=sourcetype value="1" onchange="changeMethod(1)"> a selection<br>';
	$tmp .= '<span id=combinationlist style="display:none">(un)select all <input type=checkbox checked onchange="flipcheckboxes(this);"><br>';
  }
  else
  { $tmp.=" ".mysqli_num_rows($bres)." combinations<br>";
   	$tmp .= ' &nbsp; (un)select all <input type=checkbox onchange="flipcheckboxes(this);"><br>';
  }
  $sortblock = array();
  while($brow = mysqli_fetch_assoc($bres))
  { $paquery = "SELECT GROUP_CONCAT(CONCAT(gl.name,': ',l.name)) AS nameblock,";
	$paquery .= " GROUP_CONCAT(c.id_attribute) AS attr_block, pa.id_product_attribute, pa.default_on";
    $paquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
    $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
    $paquery .= " WHERE pa.id_product_attribute='".$brow['id_product_attribute']."' GROUP BY pa.id_product_attribute";
    $paquery .= " LIMIT ".$startrec.",".$numrecs;
    $pares=dbquery($paquery);
    $sortblock[] = mysqli_fetch_array($pares);
  }
  sort($sortblock);
  foreach ($sortblock AS $parow)
  { $star = "";
    if($parow["default_on"]=="1") $star = "*";
	/* note that for the copy we use the id_product_attribute and for the delete the attribute block. */
	/* the attribute block is a comma separated list of attributes */
    if($task == "copycombifilter")
       $tmp .= '<input type=checkbox name="combis[]" value="'.mysort($parow["id_product_attribute"]).'" onclick="checker(this,event)" checked>'.$parow["nameblock"]." ".$parow["id_product_attribute"].$star."<br>";
	else
       $tmp .= '<input type=checkbox name="combis[]" value="'.mysort($parow["attr_block"]).'" onclick="checker(this,event)">'.$parow["nameblock"]." ".$parow["id_product_attribute"].$star."<br>";
  }
  if($task == "copycombifilter")	
    $tmp .= '</span>';
  echo $tmp;
}
else if($task == "checkproducts")
{ $errorsfound = 0;
  $products = explode(",",$myids);
  $i=0;
  $listproducts = array();
  foreach($products AS $product)
  { if(strpos($product, "-") > 0)
    { array_splice($products,$i,1);
      $parts = explode("-", $product);
	  $start = intval($parts[0]);
	  $end = intval($parts[1]);
      $squery = "SELECT id_product FROM "._DB_PREFIX_."product WHERE id_product >=".$start." AND id_product <=".$end;
      $sres=dbquery($squery);
      while($srow = mysqli_fetch_assoc($sres))
	    $listproducts[] = $srow["id_product"];  
	}
    $i++;
  }
  $products=array_merge($products,$listproducts);
  foreach($products AS $product)
  { $query = "SELECT name,is_virtual FROM "._DB_PREFIX_."product_lang pl";
	$query .= " LEFT JOIN "._DB_PREFIX_."product p ON p.id_product=pl.id_product";
    $query .= " WHERE pl.id_product='".mysqli_real_escape_string($conn, $product)."' AND id_lang='".mysqli_real_escape_string($conn, $id_lang)."'";
    $res=dbquery($query);
    if(mysqli_num_rows($res) == 0) 
	{ echo $product." product not found!!!<br>";
	}
    else 
	{ $data = mysqli_fetch_array($res);
	  if($data["is_virtual"] == "1") 
	    echo $product." (".$data['name'].") is a virtual product and cannot have combinations!!!<br>";
	  else
	    echo $product." ".$data['name']."<br>";
    }
  }
}
else if($task == "checkcategories")
{ $errorsfound = 0;
  $categories = explode(",",$myids);
  foreach($categories AS $category)
  { $query = "SELECT name FROM "._DB_PREFIX_."category_lang WHERE id_category='".mysqli_real_escape_string($conn, $category)."' AND id_lang='".mysqli_real_escape_string($conn, $id_lang)."'";
    $res=dbquery($query);
    if(mysqli_num_rows($res) == 0) { echo $category." category not found!!!<br>"; continue; }
    else
	{ $data = mysqli_fetch_array($res);
	  echo "Category ".$category." (".$data['name'].")";
	  $cquery = "SELECT cp.id_product, name FROM "._DB_PREFIX_."category_product cp ";
	  $cquery .= " LEFT JOIN "._DB_PREFIX_."product_lang pl on cp.id_product=pl.id_product AND id_lang='".mysqli_real_escape_string($conn, $id_lang)."'"; 
	  $cquery .= " WHERE cp.id_category='".$category."'";
      $cres=dbquery($cquery);
	  $numproducts = mysqli_num_rows($cres);
	  echo " contains ".$numproducts." products of which a few are shown:<br>";
	  if($numproducts > 2) $numproducts = 2;
	  for($i=0; $i<$numproducts; $i++)
	  { $row = mysqli_fetch_assoc($cres);
	    echo "&nbsp; &nbsp; &nbsp ".$row["id_product"]." ".$row["name"]."<br>";
	  }
    }
  }
}
else if($task == "checkmanufacturers")
{ $errorsfound = 0;
  $manufacturers = explode(",",$myids);
  foreach($manufacturers AS $manufacturer)
  { $query = "SELECT name FROM "._DB_PREFIX_."manufacturer WHERE id_manufacturer='".mysqli_real_escape_string($conn, $manufacturer)."'";
    $res=dbquery($query);
    if(mysqli_num_rows($res) == 0) { echo $manufacturer." manufacturer not found!!!<br>"; continue; }
    else
	{ $data = mysqli_fetch_array($res);
	  echo $manufacturer." (".$data['name'].")";
	  $cquery = "SELECT p.id_product, name FROM "._DB_PREFIX_."product p ";
	  $cquery .= " LEFT JOIN "._DB_PREFIX_."product_lang pl on p.id_product=pl.id_product AND id_lang='".mysqli_real_escape_string($conn, $id_lang)."'"; 
	  $cquery .= " WHERE p.id_manufacturer='".$manufacturer."'";
      $cres=dbquery($cquery);
	  $numproducts = mysqli_num_rows($cres);
	  echo " contains ".$numproducts." products of which a few are shown:<br>";
	  if($numproducts > 2) $numproducts = 2;
	  for($i=0; $i<$numproducts; $i++)
	  { $row = mysqli_fetch_assoc($cres);
	    echo "&nbsp; &nbsp; &nbsp ".$row["id_product"]." ".$row["name"]."<br>";
	  }
	}
  }
}
else if($task == "getattributeoptions")
{ $aquery = "SELECT a.id_attribute, l.name";
  $aquery .= " FROM ". _DB_PREFIX_."attribute a";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=a.id_attribute AND l.id_lang='".$id_lang."'";
  $aquery .= " WHERE a.id_attribute_group=".$myids." ORDER BY position";
  $ares=dbquery($aquery);
  if(!in_array($group, array('A','B'))) die("Mysterious error"); /* choose between the two select boxes */
  $tmp = $group."\n";
  while($arow = mysqli_fetch_assoc($ares))
  { $tmp .= '<option value="'.$arow['id_attribute'].'">'.$arow['name'].'</a>';
  }
  echo $tmp;
}
else if($task == "getattrfeat")
{   if(substr($group,0,1) == "a")
    { $query = "SELECT a.id_attribute AS id, l.name";
      $query .= " FROM ". _DB_PREFIX_."attribute a";
      $query .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=a.id_attribute AND l.id_lang='".$id_lang."'";
      $query .= " WHERE a.id_attribute_group=".substr($group,1)." ORDER BY position";
    }
    else if(substr($group,0,1) == "f")
	{ $query = "SELECT fvl.id_feature_value AS id, fvl.value AS name FROM `". _DB_PREFIX_."feature_value_lang` fvl";
	  $query .= " LEFT JOIN `". _DB_PREFIX_."feature_value` fv ON fv.id_feature_value=fvl.id_feature_value";
	  $query .= " WHERE id_feature='".substr($group,1)."' AND id_lang=".$id_lang." AND custom=0";
      $query .= " ORDER BY name";
	}
	else return;
    $res = dbquery($query);
	$tmp = $myids.'
';
    while($row=mysqli_fetch_assoc($res))
    { $tmp .= '<option value="'.$row["id"].'" >'.$row["name"].'</option>';
	}
    echo $tmp;
}
mysqli_close($conn);


function mysort($myarr)
{ $tmp = explode(",", $myarr);
  natsort($tmp);
  return implode("-",$tmp);
}
?>

