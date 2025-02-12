<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
if(isset($_GET["id_product"]))
	$input = $_GET;
else
	$input = $_POST;
 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   die();
 }

/*  set_time_limit(0); /* use this if you get a timeout; the value is in seconds. 0=unlimited */
/* in Prestashop the table ps_attribute_impact is set too. But I don't see any function for that table and its content isn't logical */
if(!isset($input["id_product"])) colordie("No product defined");
$id_product = intval($input["id_product"]);
if($id_product == 0) colordie("Invalid id_shop value ".str_replace("<", "&lt;",$input["id_product"]));
$id_shop = intval($input["id_shop"]);
if($id_shop == 0) colordie("Invalid id_shop value ".str_replace("<", "&lt;",$input["id_shop"]));
if(!isset($input["shops"])) colordie("No shops defined");
$shops = $input["shops"];
foreach($shops AS $shop)
{ if(!is_numeric($shop)) colordie("Illegal value for shop ".str_replace("<", "&lt;",$shop));
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
 if($verbose == "false")
   echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a><span id="addimgr"></span> ';
 else
 { $refscript = $_SERVER['HTTP_REFERER'];
   if($refscript == "") $refscript = "combi-pricer.php?id_product=".$id_product;
   echo "<br>Go back to <a href='".$refscript."'>".$refscript." page</a><span id='addimgr'></span><br>";
 }
$query = "SELECT id_product FROM "._DB_PREFIX_."product_shop WHERE id_product=".$id_product." AND id_shop=".$id_shop;
$res=dbquery($query);
if(mysqli_num_rows($res) == 0) colordie("product not found!");

/* find which values are set by chosing one example */
$priceflag = $wspriceflag = $unitpriceflag = $ecotaxflag = $weightflag = false;
$query = "SELECT DISTINCT pc.id_attribute";
$query .= " FROM "._DB_PREFIX_."product_attribute_shop pa";
$query .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
$query .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop;
$res=dbquery($query);
$row=mysqli_fetch_assoc($res);
$att = $row["id_attribute"];
if(isset($input["price".$att]))
  $priceflag = true;
if(isset($input["wsprice".$att]))
  $wspriceflag = true;
if(isset($input["unitprice".$att]))
  $unitpriceflag = true;
if(isset($input["ecotax".$att]))
  $ecotaxflag = true;
if(isset($input["weight".$att]))
  $weightflag = true;
  
/* data validity check */
mysqli_data_seek($res,0);
while($row=mysqli_fetch_assoc($res))
{ $att = $row["id_attribute"];
  if($priceflag)
    if(!isset($input["price".$att]) || !is_numeric($input["price".$att]))
	  colordie("Illegal price value ".str_replace("<", "&lt;",$input["price".$att])." for attribute ".$att);
  if($wspriceflag)
    if(!isset($input["wsprice".$att]) || !is_numeric($input["wsprice".$att]))
	  colordie("Illegal wsprice value ".str_replace("<", "&lt;",$input["wsprice".$att])." for attribute ".$att);
  if($unitpriceflag)
    if(!isset($input["unitprice".$att]) || !is_numeric($input["unitprice".$att]))
	  colordie("Illegal unitprice value ".str_replace("<", "&lt;",$input["unitprice".$att])." for attribute ".$att);
  if($ecotaxflag)
    if(!isset($input["ecotax".$att]) || !is_numeric($input["ecotax".$att]))
	  colordie("Illegal ecotax value ".str_replace("<", "&lt;",$input["ecotax".$att])." for attribute ".$att);
  if($weightflag)
    if(!isset($input["weight".$att]) || !is_numeric($input["weight".$att]))
	  colordie("Illegal weight value ".str_replace("<", "&lt;",$input["weight".$att])." for attribute ".$att);
}

/* The main loop: calculate and implement the values for all combinations */
$aquery = "SELECT pc.id_product_attribute, GROUP_CONCAT(pc.id_attribute) AS attributes";
$aquery .= " FROM "._DB_PREFIX_."product_attribute_shop pa";
$aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
$aquery .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop;
$aquery .= " GROUP BY pa.id_product_attribute";
$ares=dbquery($aquery);
while($arow=mysqli_fetch_assoc($ares))
{ $atts = explode(",",$arow["attributes"]);
  $price = $unitprice = $wsprice = $ecotax = $weight = 0;
  foreach($atts AS $att)
  { if($priceflag)
	{ $price += floatval($input["price".$att]);
	}
	if($wspriceflag)
	{ $wsprice += floatval($input["wsprice".$att]);
	}
	if($unitpriceflag)
	{ $unitprice += floatval($input["unitprice".$att]);
	}
	if($ecotaxflag)
	{ $ecotax += floatval($input["ecotax".$att]);
	}
	if($weightflag)
	{ $weight += floatval($input["weight".$att]);
	}
  }
  $args = array();
  if($priceflag)
  { $args[] = 'price="'.$price.'"';
  }
  if($wspriceflag)
  { $args[] = 'wholesale_price="'.$wsprice.'"';
  }
  if($unitpriceflag)
  { $args[] = 'unit_price_impact="'.$unitprice.'"';
  }
  if($ecotaxflag)
  { $args[] = 'ecotax="'.$ecotax.'"';
  }
  if($weightflag)
  { $args[] = 'weight="'.$weight.'"';
  }
  $myargs = implode(",", $args);
  $uquery = 'UPDATE '._DB_PREFIX_.'product_attribute SET '.$myargs;
  $uquery .= ' WHERE id_product_attribute='.$arow["id_product_attribute"];
  $ures=dbquery($uquery);
  
  foreach($shops AS $shop)
  { $uquery = 'UPDATE '._DB_PREFIX_.'product_attribute_shop SET '.$myargs;
    $uquery .= ' WHERE id_product_attribute='.$arow["id_product_attribute"].' AND id_shop='.$shop;
    $ures=dbquery($uquery);
  }
}

/* handle the impacts */
if($priceflag || $weightflag)
{ mysqli_data_seek($res,0);
  while($row=mysqli_fetch_assoc($res))  /* foreach attribute */
  { $att = $row["id_attribute"];
	$cquery = 'SELECT * FROM '._DB_PREFIX_.'attribute_impact WHERE id_product='.$id_product.' AND id_attribute='.$att;
    $cres=dbquery($cquery);
	if(mysqli_num_rows($cres) == 0)
	{ $rquery = 'INSERT INTO '._DB_PREFIX_.'attribute_impact SET id_product='.$id_product.',id_attribute='.$att;
	  if($priceflag) 
	     $rquery .= ',price='.floatval($input["price".$att]);
	  if($weightflag) 
	     $rquery .= ',weight='.floatval($input["weight".$att]); 
      $rres=dbquery($rquery);
	}
	else
	{ $rquery = 'UPDATE '._DB_PREFIX_.'attribute_impact SET ';
	  if($priceflag) 
	     $rquery .= 'price='.floatval($input["price".$att]);
	  if($priceflag && $weightflag) 
	     $rquery .= ', ';
	  if($weightflag) 
	     $rquery .= 'weight='.floatval($input["weight".$att]); 
	  $rquery .= ' WHERE id_product='.$id_product.' AND id_attribute='.$att; 
      $rres=dbquery($rquery);
	}
  }
}

echo "<script>parent.dynamo2('Finished');</script>Finished";
mysqli_close($conn);


