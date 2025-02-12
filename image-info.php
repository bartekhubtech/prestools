<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_image']))
  $id_image = intval($_GET['id_image']);
else 
  $id_image = "";

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');
$id_lang = get_configuration_value('PS_LANG_DEFAULT');
$id_shop = get_configuration_value('PS_SHOP_DEFAULT');

$shops = array();
$query = "SELECT id_shop,name from "._DB_PREFIX_."shop";
$query .= " WHERE active=1 AND deleted=0";
$query .= " ORDER BY id_shop";
$res=dbquery($query);
while ($shop=mysqli_fetch_assoc($res))
{ $shops[] = $shop['id_shop'];
}

$langs = array();
$query = "SELECT id_lang,name from "._DB_PREFIX_."lang";
$query .= " WHERE active=1";
$query .= " ORDER BY id_lang";
$res=dbquery($query);
while ($lang=mysqli_fetch_assoc($res))
{ $langs[] = $lang['id_lang'];
}
  
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Image Multiedit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
input.posita {width: 50px; text-align:right}
table td {vertical-align: top; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
function showme(spanid)
{ var tmp = document.getElementById(spanid);
  if(!tmp) alert("Not found!");
  tmp.style.display = "inline";
}
</script>
</head><body>
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2 style="text-align:center; ">
<a href="imginfo.php" style="text-decoration:none;"><h1 style="display: inline-block;">Image info</h1></a></td>
<td align=right rowspan=2><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td>
<?php 
  echo "<form name=imageform action='imginfo.php' method=get><table><tr><td>Image id: </td>
  <td><input name=id_image value='".$id_image."' size=3> </td></tr>";
   echo '</table></td><td><p><input type=submit></td></tr></table>';
  echo "</form>";
  
  if(($id_image == "") || ($id_image == 0))
  { echo "</body></html>";
    die();
  }
  
  $base_uri = get_base_uri();
  $path = getpath($id_image);
  echo '<table style="margin-left:15px"><tr><td><img src="'.$base_uri.'img/p'.$path.'/'.$id_image.'.jpg" height="150px">
  </td><td>';
  $imgformats = [];
  $query = "SELECT * FROM "._DB_PREFIX_."image_type WHERE products=1";
  $res=dbquery($query);
  while($row = mysqli_fetch_assoc($res))
  { $imgformats[] = $row["name"];
  }
  
  $imageroot = $triplepath."img/p/".$path.'/';
  $basepresent = $indexpresent = 0;
  $otherimages = $otherfiles = $subdirs = $webps = $derived = $missingderived = $notusedderived = [];
  $files = scandir($imageroot);
  $len = strlen($id_image);
  $myfiles = [];
  foreach($files AS $fil)
  { if(($fil == ".") || ($fil == ".."))
	  continue;
    if(is_dir(realpath($imageroot.$fil))) $subdirs[] = $fil;
	else if($fil == $id_image.'.jpg') $basepresent++;
	else if(strcasecmp($fil, 'index.php') === 0) $indexpresent++;
	else if((substr($fil, 0,$len+1) == $id_image."-") && (strtolower(substr($fil,-4)) == ".jpg"))
	{ $derived[] = substr($fil, $len+1, -4);
	}
	else if(strtolower(substr($fil,-4)) == ".jpg")
	{	$otherimages[] = $fil;
	}
	else if(strtolower(substr($fil,-5)) == ".webp")
	{	$webps[] = $fil;
	}
	else
	{	$otherfiles[] = $fil;
	}
  }
  
  $notusedderived = array_diff($derived, $imgformats);
  $missingderived = array_diff($imgformats, $derived);
  $derived = array_intersect($imgformats, $derived);

  echo "base image present: ";
  if($basepresent) echo "yes"; else echo "no";
  echo "<br>index.php present: ";
  if($indexpresent) echo "yes"; else echo "no";
  echo "<br>number of subdirectories: ".sizeof($subdirs); 
  echo "<br>number of derived images: ".sizeof($derived);
  echo "<br>number of missing derived images: ".sizeof($missingderived); 
  echo "<br>number of outdated derived images: ".sizeof($notusedderived);
  echo "<br>number of webp images: ".sizeof($webps);
  echo "<br>number of other images: ".sizeof($otherimages);
  echo "<br>number of other files: ".sizeof($otherfiles);
  echo '</td><td>';

  
  echo '</td></tr></table>';
  
  echo "<br><b>Image ".$id_image." in the database</b>";
  $product_ids = [];
  $query = "SELECT * FROM "._DB_PREFIX_."image WHERE id_image=".$id_image;
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0)
  { echo '<table class="triplemain"><tr><td>'._DB_PREFIX_.'image</td></tr><tr><td>Not in table</td></tr></table>';
  }
  else
  { $row = mysqli_fetch_assoc($res);
	$fieldnames = $fields = [];
	foreach($row AS $key => $content)
	{ $fieldnames[] = $key;
	  $fields[] = $content;
	}
    echo '<table class="triplemain"><tr><td colspan='.sizeof($fields).'>'._DB_PREFIX_.'image</td></tr><tr>';
	foreach($fieldnames AS $name)
	  echo '<td>'.$name.'</td>';
	echo '</tr><tr>';
	foreach($fields AS $field)
	{ echo '<td>'.$field.'</td>';
	}
    echo '</tr></table>
	';
	$product_ids[] = $row['id_product'];
  }

  $query = "SELECT * FROM "._DB_PREFIX_."image_shop WHERE id_image=".$id_image;
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0)
  { echo '<table class="triplemain"><tr><td>'._DB_PREFIX_.'image_shop</td></tr><tr><td>Not in table</td></tr></table>';
  }
  else
  { $row = mysqli_fetch_assoc($res);
	$fieldnames = $fields = [];
	foreach($row AS $key => $content)
	{ $fieldnames[] = $key;
	  $fields[] = $content;
	}
    echo '<table class="triplemain"><tr><td colspan='.sizeof($fields).'>'._DB_PREFIX_.'image_shop</td></tr><tr>';
	foreach($fieldnames AS $name)
	  echo '<td>'.$name.'</td>';
	echo '</tr><tr>';
	foreach($fields AS $field)
	  echo '<td>'.$field.'</td>';
    echo '</tr>';
	$product_ids[] = $row['id_product'];
	while($row = mysqli_fetch_assoc($res))
	{ echo '<tr>';
	  foreach($row AS $field)
	    echo '<td>'.$field.'</td>';
      echo '</tr>';
	  $product_ids[] = $row['id_product'];
	}
	echo '</table>
	';
  }
  
  /* image_lang */
  $query = "SELECT * FROM "._DB_PREFIX_."image_lang WHERE id_image=".$id_image;
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0)
  { echo '<table class="triplemain"><tr><td>'._DB_PREFIX_.'image_lang</td></tr><tr><td>Not in table</td></tr></table>';
  }
  else
  { $row = mysqli_fetch_assoc($res);
	$fieldnames = $fields = [];
	foreach($row AS $key => $content)
	{ $fieldnames[] = $key;
	  $fields[] = $content;
	}
    echo '<table class="triplemain"><tr><td colspan='.sizeof($fields).'>'._DB_PREFIX_.'image_lang</td></tr><tr>';
	foreach($fieldnames AS $name)
	  echo '<td>'.$name.'</td>';
	echo '</tr><tr>';
	foreach($fields AS $field)
	  echo '<td>'.$field.'</td>';
    echo '</tr>';
	while($row = mysqli_fetch_assoc($res))
	{ echo '<tr>';
	  foreach($row AS $field)
	    echo '<td>'.$field.'</td>';
      echo '</tr>';
	}
	echo '</table>
	';
  }
  
  $product_ids = array_unique($product_ids);
  if(sizeof($product_ids) > 1)
	echo "<h1>Error: linking to different products</h1>";
  foreach($product_ids AS $prod)
  { $query = "SELECT * FROM "._DB_PREFIX_."product WHERE id_product=".$prod;
    $res = dbquery($query);
    if(mysqli_num_rows($res) == 0)
    { echo '<table class="triplemain"><tr><td>'._DB_PREFIX_.'product</td></tr><tr><td>Not in table</td></tr></table>';
    }
    else
    { $row = mysqli_fetch_assoc($res);
	  $fieldnames = $fields = [];
	  foreach($row AS $key => $content)
	  { $fieldnames[] = $key;
	    $fields[] = $content;
	  }
      echo '<table class="triplemain"><tr><td colspan='.sizeof($fields).'>'._DB_PREFIX_.'product</td></tr><tr>';
	  foreach($fieldnames AS $name)
	    echo '<td>'.$name.'</td>';
	  echo '</tr><tr>';
	  foreach($fields AS $field)
	  { echo '<td>'.$field.'</td>';
	  }
      echo '</tr></table>
	';
	  $product_ids[] = $row['id_product'];
    }

    $query = "SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product=".$prod;
    $res = dbquery($query);
    if(mysqli_num_rows($res) == 0)
    { echo '<table class="triplemain"><tr><td>'._DB_PREFIX_.'product_shop</td></tr><tr><td>Not in table</td></tr></table>';
    }
    else
    { $row = mysqli_fetch_assoc($res);
	  $fieldnames = $fields = [];
	  foreach($row AS $key => $content)
	  { $fieldnames[] = $key;
	    $fields[] = $content;
	  }
      echo '<table class="triplemain"><tr><td colspan='.sizeof($fields).'>'._DB_PREFIX_.'product_shop</td></tr><tr>';
	  foreach($fieldnames AS $name)
	    echo '<td>'.$name.'</td>';
	  echo '</tr><tr>';
	  foreach($fields AS $field)
	    echo '<td>'.$field.'</td>';
      echo '</tr>';
	  while($row = mysqli_fetch_assoc($res))
	  { echo '<tr>';
	    foreach($row AS $field)
	      echo '<td>'.$field.'</td>';
        echo '</tr>';
	  }
	  echo '</table>
	  ';
    }

    $query = "SELECT * FROM "._DB_PREFIX_."product_lang WHERE id_product=".$prod;
    $res = dbquery($query);
    if(mysqli_num_rows($res) == 0)
    { echo '<table class="triplemain"><tr><td>'._DB_PREFIX_.'product_lang</td></tr><tr><td>Not in table</td></tr></table>';
    }
    else
    { $row = mysqli_fetch_assoc($res);
	  $fieldnames = $fields = [];
	  foreach($row AS $key => $content)
	  { $fieldnames[] = $key;
	    $fields[] = $content;
	  }
      echo '<table class="triplemain"><tr><td colspan='.sizeof($fields).'>'._DB_PREFIX_.'product_lang</td></tr><tr>';
	  foreach($fieldnames AS $name)
	    echo '<td>'.$name.'</td>';
	  echo '</tr><tr>';
	  foreach($fields AS $field)
	    echo '<td>'.minimize($field, $row).'</td>';
      echo '</tr>';
	  while($row = mysqli_fetch_assoc($res))
	  { echo '<tr>';
	    foreach($row AS $field)
	      echo '<td>'.minimize($field, $row).'</td>';
        echo '</tr>';
	  }
	  echo '</table>
	  ';
    }
  }

echo "<br><b>Directory content</b>";
echo '<table class="triplemain"><tr>';
echo '<td>Subdirectories:<br>'.implode('<br>',$subdirs).'</td>';
echo '<td>Derived images:';
foreach($derived AS $drv) echo "<br>".$id_image."-".$drv;
echo '</td><td>Missing derived images:';
foreach($missingderived AS $drv) echo "<br>".$id_image."-".$drv;
echo '</td><td>Outdated derived images:';
foreach($notusedderived AS $drv) echo "<br>".$id_image."-".$drv;
echo '</td><td>Webp images:<br>'.implode('<br>',$webps).'</td>';
echo '<td>Other jpg images:<br>'.implode('<br>',$otherimages).'</td>';
echo '<td>Other files:<br>'.implode('<br>',$otherfiles).'</td>';
echo '</tr></table>';



  include "footer1.php";
?>
</body>
</html>

<?php

function minimize($str, $row)
{ $id = "Hider".$row["id_product"]."x".$row["id_shop"]."y".$row["id_lang"];
  if(strlen($str) < 22)
	return htmlentities($str);
  else
  { $tmp = htmlentities(substr($str, 0, 18));
	$tmp .= '<span id="'.$id.'" style="display:none">'.htmlentities(substr($str,19)).'</span>';
	$tmp .= '<a href="javascript:showme(\''.$id.'\');">...</a>';
	return $tmp;
  }
}