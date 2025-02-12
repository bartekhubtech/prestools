 <?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$mode = "background";

 /* Get the arguments */
if(!isset($_POST['id_lang']))
{ echo "No language";
  return;
}
$id_lang = strval(intval($_POST['id_lang']));

if(isset($_POST['id_shop']))
  $id_shop = strval(intval($_POST['id_shop']));
else	
  colordie("No shop provided");

if(isset($_POST['id_product']))
  $id_product = strval(intval($_POST['id_product']));
else	
  colordie("No product provided");
  
if(isset($_SERVER['HTTP_REFERER']))
  $refscript = $_SERVER['HTTP_REFERER'];
else
{ $refscript = str_replace("image-proc","image-edit",$_SERVER['REQUEST_URI']);
  if($refscript == "")
    $refscript = "image-edit.php";
}
  
echo '<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<script>
function newwin()
{ nwin = window.open("","_blank", "scrollbars,menubar,toolbar, status,resizable,location");
  content = document.body.innerHTML;
  if(nwin != null)
  { nwin.document.write("<html><head><meta http-equiv=\'Content-Type\' content=\'text/html; charset=utf-8\' /></head><body>"+content+"</body></html>");
    nwin.document.close();
  }
}
</script></head><body>';

extract($_POST);

/* flag that ps_image_shop table has cover field */
$cover_in_imgshop = $product_in_imgshop = false;
$res = dbquery("SHOW COLUMNS FROM "._DB_PREFIX_."image_shop");
while($row = mysqli_fetch_array($res))
{	if($row[0] == "cover") $cover_in_imgshop = true;
	if($row[0] == "id_product") $product_in_imgshop = true;
}

$prodshops = array();
$squery="select id_shop FROM ". _DB_PREFIX_."product_shop WHERE id_product=".$id_product;
$sres=dbquery($squery);
while($srow = mysqli_fetch_assoc($sres))
	$prodshops[] = $srow["id_shop"];

if(isset($GLOBALS['attrgroups']))
{ $langs = array();
  $squery="select id_lang FROM ". _DB_PREFIX_."lang";
  $sres=dbquery($squery);
  while($srow = mysqli_fetch_assoc($sres))
	$langs[] = $srow["id_lang"];

  /* image legends are not shop specific. So we need only one product name. */
  $prodnames = [];
  $squery="select id_lang, id_shop, name FROM ". _DB_PREFIX_."product_lang WHERE id_product=".$id_product;
  $sres=dbquery($squery);
  while($srow = mysqli_fetch_assoc($sres))
  { if(!isset($prodnames[$srow["id_lang"]]))
	  $prodnames[$srow["id_lang"]] = $srow["name"];
  }

  $attrnames = [];
  $atgroups = explode(",", $GLOBALS['attrgroups']);
  foreach($atgroups AS $group)
  { $squery="select al.id_attribute, id_lang, name FROM "._DB_PREFIX_."attribute_lang al";
    $squery .= " LEFT JOIN "._DB_PREFIX_."attribute at on al.id_attribute=at.id_attribute";
	$squery .= " WHERE at.id_attribute_group=".$group;
    $sres=dbquery($squery);
    while($srow = mysqli_fetch_assoc($sres))
	{ if(!isset($attrnames[$srow["id_attribute"]]))
	    $attrnames[$srow["id_attribute"]] = [];
	  $attrnames[$srow["id_attribute"]][$srow["id_lang"]] = $srow["name"];
	}
  }

  $attrshops = [];
  $keys = array_keys($attrnames);
  foreach($keys as $key)
  { $squery="select id_attribute, id_shop FROM ". _DB_PREFIX_."attribute_shop WHERE id_attribute=".$key;
    $sres=dbquery($squery);
    while($srow = mysqli_fetch_assoc($sres))
    { if(!isset($attrshops[$srow["id_attribute"]]))
	    $attrshops[$srow["id_attribute"]] = [];
      $attrshops[$srow["id_attribute"]][$srow["id_shop"]] = 1;
	}
  }
}
 
/* get shop group and its shared_stock status */
$query="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
$query .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_shop_group = $row['id_shop_group'];
$share_stock = $row["share_stock"];
  
echo $reccount." Records<br/>";
 if($prestools_settings["demo_mode"])
   echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
 else if(isset($_POST['id_image']))
 { echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';
   change_rec("");
 }
 else
 { echo "<br>Go back to the <a href='".$refscript."'>Image-edit page</a><p/>";
   echo $reccount." Records<br/>";
   $newcover = 0;
   for($i=0; $i<$reccount; $i++)
   { change_rec($i);
     if((isset($GLOBALS['cover'.$i])) && ($GLOBALS['cover'.$i] == 1))
	     $newcover = $i;
   }
 }
 
function change_rec($x)
{ global $id_lang, $id_shop, $id_shop_group, $errstring, $verbose, $id_product, $conn, $cover_in_imgshop, $product_in_imgshop;
  global $langs, $prodnames, $attrnames, $attrshops;
  echo "*";
  if((!isset($GLOBALS['id_image'.$x])) || (!is_numeric($GLOBALS['id_image'.$x]))) {if ($verbose=="true") echo "No changes"; return;}
  echo $x.": ";
    
  $id_image = $GLOBALS['id_image'.$x];
  
  if(isset($GLOBALS['position'.$x]))  
  { $position = $GLOBALS['position'.$x];
    if(!is_numeric($position)) colordie("invalid position for ".$x);
    $query = "UPDATE ". _DB_PREFIX_."image SET position='".mysqli_real_escape_string($conn, $position)."' WHERE id_product='".$id_product."' AND id_image='".$id_image."'";
    dbquery($query);
  }
  
  if ((_PS_VERSION_ < "1.6.1") && (isset($GLOBALS['cover'.$x])) && ($GLOBALS['cover'.$x] != '1'))  
  { $query = "UPDATE "._DB_PREFIX_."image SET cover='0' WHERE id_product='".$id_product."' AND id_image='".$id_image."'";
	dbquery($query);
	$query = "UPDATE "._DB_PREFIX_."image_shop SET cover='0' WHERE id_image='".$id_image."'";
	dbquery($query);
  }
  
  if(isset($GLOBALS['legend'.$x]))  
  { $legend = $GLOBALS['legend'.$x];
    $legend = preg_replace('/[<>={}]+/', '', $legend);
    $query = "UPDATE ". _DB_PREFIX_."image_lang SET legend='".mysqli_real_escape_string($conn, $legend)."' WHERE id_image='".$id_image."' AND id_lang='".$id_lang."'";
    dbquery($query);
  }
  
  if(isset($GLOBALS['shopz'.$x]))
  { /* get the old shops for this image */
	$shopsquery = "SELECT GROUP_CONCAT(id_shop) AS shops FROM ". _DB_PREFIX_."image_shop";
	$shopsquery .= " WHERE id_image = '".$id_image."' GROUP BY id_image";
	$shopsres=dbquery($shopsquery);
	$shopsrow=mysqli_fetch_array($shopsres);
	$oldshops = explode(",",$shopsrow["shops"]);
		
	$newshops = $GLOBALS['shopz'.$x];
	if(sizeof($newshops)==0)
		colordie("You are not allowed to delete a image for all shops!");
	foreach($newshops AS $newshop)
	{ if(!in_array($newshop, $prodshops))
		colordie("Illegal shop number found: ".$newshop);
	}

	$extrashops = array_diff($newshops,$oldshops);
	if(sizeof($extrashops)>0)
	  echo "<br><b>Adding shops ".implode(",",$extrashops)." for image ".$id_image."</b><br>"; 
	foreach($extrashops AS $extrashop)	
	{ $prod = ""; /* cover will get the default for not true */
	  if($product_in_imgshop) $prod = "id_product=".$id_product.", ";
	  $query = "INSERT INTO "._DB_PREFIX_."image_shop SET ".$prod." id_image=".$id_image.", id_shop=".$extrashop;
	  $res = dbquery($query);
	}
	
	$deletedshops = array_diff($oldshops,$newshops);
	if(sizeof($deletedshops)>0)
	  echo "<br><b>Removing shops ".implode(",",$deletedshops)." for image ".$id_image."</b><br>"; 
	foreach($deletedshops AS $deletedshop)
	{ $query = "DELETE FROM "._DB_PREFIX_."image_shop WHERE id_image=".$id_image." AND id_shop=".$deletedshop;
	  $res = dbquery($query);
	}
	
    if (_PS_VERSION_ >= "1.6.1")  /* look for shops without cover */
	{ $iquery = "SELECT SUM(cover) AS csum,id_shop FROM "._DB_PREFIX_."image_shop";
      $iquery .= " WHERE id_product='".$id_product."' GROUP BY id_shop";
	  $ires=dbquery($iquery);
	  while($irow=mysqli_fetch_array($ires))
	  { if($irow["csum"] == '1') continue;
        $uquery = "UPDATE "._DB_PREFIX_."image_shop SET cover=1 WHERE id_product='".$id_product."' AND id_shop=".$irow["id_shop"]." LIMIT 1";
		$ures=dbquery($uquery);
	  }
	}
  }
  
  if(isset($GLOBALS['attrgroups']))
  { $atgroups = explode(",", $GLOBALS['attrgroups']);
	$sups = []; /* supplements for every language */
	foreach($langs AS $lang)
	  $sups[$lang] = "";
	$attrs = [];
    foreach($atgroups AS $group)
	{ $attr = $GLOBALS['atgroup'.$group.'s'.$x];
	  if($attr != 0)
	  { foreach($langs AS $lang)
	      $sups[$lang] .= " ".$attrnames[$attr][$lang];
		$attrs[] = $attr;
	  }
	}
	if(sizeof($attrs) > 0)
	{ foreach($langs AS $lang)
	  { $legend = $prodnames[$lang].$sups[$lang];
	    $uquery = "UPDATE "._DB_PREFIX_."image_lang SET legend='".mescape($legend)."' WHERE id_image='".$id_image."' AND id_lang=".$lang;
		$ures=dbquery($uquery);
	  }
	  /* now get the id_product_attribute */
	  $query = "SELECT id_product_attribute FROM "._DB_PREFIX_."product_attribute pa";
	  $query .= " WHERE pa.id_product=".$id_product;
	  foreach($attrs AS $attr)
	    $query .= " AND EXISTS (SELECT NULL FROM "._DB_PREFIX_."product_attribute_combination pac WHERE pac.id_product_attribute=pa.id_product_attribute AND pac.id_attribute=".$attr.")";
	  $res=dbquery($query);
	  while($row = mysqli_fetch_array($res))
	  { $squery = "SELECT * FROM "._DB_PREFIX_."product_attribute_image";
	    $squery .= " WHERE id_product_attribute=".$row["id_product_attribute"]." AND id_image=".$id_image;
	    $sres=dbquery($squery);
		if(mysqli_num_rows($sres) == 0)
		{ $uquery = "INSERT INTO "._DB_PREFIX_."product_attribute_image";
	      $uquery .= " SET id_product_attribute=".$row["id_product_attribute"].", id_image=".$id_image;
	      $ures=dbquery($uquery);
		}
	  }	  
	}
  }
  
}

if(isset($GLOBALS['cover0']))
{ echo "New cover = ".$newcover."<br>";
  if (_PS_VERSION_ < "1.6.1")
  { $query = "UPDATE ". _DB_PREFIX_."image SET cover='1' WHERE id_product='".$id_product."' AND id_image='".$GLOBALS['id_image'.$newcover]."'";
	dbquery($query);
	$query = "UPDATE ". _DB_PREFIX_."image_shop SET cover='1' WHERE id_image='".$GLOBALS['id_image'.$newcover]."'";
    dbquery($query);
  }
  else
  { $query = "UPDATE ". _DB_PREFIX_."image SET cover=NULL WHERE id_product='".$id_product."'";
    dbquery($query);
    $query = "UPDATE ". _DB_PREFIX_."image SET cover='1' WHERE id_product='".$id_product."' AND id_image='".$GLOBALS['id_image'.$newcover]."'";
    dbquery($query);
    if(!isset($_POST["allshops"]) || ($_POST["allshops"] == "0")) /* not set for product-sort */
		$shopmask = " AND id_shop='".$id_shop."' ";
	else 
		$shopmask = "";
	$query = "UPDATE ". _DB_PREFIX_."image_shop SET cover=NULL WHERE id_product='".$id_product."'".$shopmask;
    dbquery($query);
    $query = "UPDATE ". _DB_PREFIX_."image_shop SET cover='1' WHERE id_product='".$id_product."' AND id_image='".$GLOBALS['id_image'.$newcover]."'".$shopmask;
    dbquery($query);
  } 
}

if($errstring != "")
{ echo "<script>alert('There were errors: ".$errstring."');</script>!";
  echo str_replace("\n","<br>",$errstring);
}

echo "<br>Finished successfully!";
if(!isset($_POST['id_image'])) /* if submit all */
  echo "<p>Go back to <a href='".$refscript."'>Product Image Edit page</a></body></html>";
  
if($verbose!="true")
{ echo "<script>location.href = '".$refscript."';</script>";
}
  
function strip($txt)
{ if (get_magic_quotes_gpc()) 
   $txt = stripslashes($txt);
  return $txt;
}

?>
