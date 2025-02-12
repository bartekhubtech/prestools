<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
if(isset($_GET["source"]))
	$input = $_GET;
else
	$input = $_POST;
/*  set_time_limit(0); /* use this if you get a timeout; the value is in seconds. 0=unlimited */
/* in Prestashop the table ps_attribute_impact is set too. But I don't see any function for that table and its content isn't logical */
$source = $input["source"];
if(!isset($input["combis"])) colordie("You need to select at least one attribute combination");
$combis = $input["combis"];
$combis_array = implode(",",$combis);
$targettype = $input["targettype"];
$prodtarget = $input["mytargets"];
if($input["standard_quantity"] == "")
  $input["standard_quantity"] = "0";
$prodctr = 0;
$modified_products_for_cpr = array(); /* for catalogue price rules */

$colquery = "SHOW COLUMNS FROM "._DB_PREFIX_."product_attribute_shop";
$res = dbquery($colquery);
$numrows = mysqli_num_rows($res);
$pasfields = array("id_product_attribute","id_shop","wholesale_price","price","ecotax","weight","unit_price_impact","default_on","minimal_quantity","available_date");
if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
{ $pasfields[] = "low_stock_threshold";
  $pasfields[] =  "low_stock_alert";
}

$fields = array(); 
while($row = mysqli_fetch_assoc($res))
{ $fields[] = $row["Field"];
  if(in_array($row["Field"], array("id_product")))
  { $pasfields[] = $row["Field"];
    continue;
  }
  if(!in_array($row["Field"], $pasfields))
    echo " Extra field ".$row["Field"].". ";
}
$diff = array_diff($pasfields, $fields);
if(sizeof($diff)>0) 
{ echo " Missing fields: ".implode(", ",$diff);
  echo "<br>All fields: ".implode(", ",$fields);
  colordie("<br>Your version of Prestashop has ".$numrows." product_attribute_shop fields! Consult the Prestools helpdesk."); 
}

$colquery = "SHOW COLUMNS FROM "._DB_PREFIX_."product_attribute";
$res = dbquery($colquery);
$numrows = mysqli_num_rows($res);
$pafields = array("id_product_attribute","id_product","reference","supplier_reference","location","ean13","upc","wholesale_price","price","ecotax","quantity","weight","unit_price_impact","default_on","minimal_quantity","available_date");
if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
  $pafields[] = "isbn";
if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
{ $pafields[] = "low_stock_threshold";
  $pafields[] =  "low_stock_alert";
}
if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
  $pafields[] = "mpn";

if(version_compare(_PS_VERSION_ , "8.0", ">="))
  $pafields = array_diff($pafields, array("location", "quantity"));

$fields = array(); 
while($row = mysqli_fetch_assoc($res))
{ $fields[] = $row["Field"];
  if(in_array($row["Field"], array("date_upd")))
  { $pafields[] = $row["Field"];
    continue;
  }
  if(!in_array($row["Field"], $pafields))
	echo " Extra Field ".$row["Field"].". ";
}
$diff = array_diff($pafields, $fields);
if(sizeof($diff)>0) 
{ echo " Missing Fields: ".implode(", ",$diff);
  echo "<br>All Fields: ".implode(", ",$fields);
  colordie("<br>Your version of Prestashop has ".$numrows." product_attribute fields! Consult the Prestools helpdesk.");
}

$colquery = "SHOW COLUMNS FROM "._DB_PREFIX_."product_attribute_combination";
$res = dbquery($colquery);
$numrows = mysqli_num_rows($res);
$pacfields = array("id_attribute","id_product_attribute");
$fields = $optional_pacfields = array(); 
while($row = mysqli_fetch_assoc($res))
{ $fields[] = $row["Field"];
  if(in_array($row["Field"], array()))
  { $optional_pacfields[] = $row["Field"];
    continue;
  }
  if(!in_array($row["Field"], $pacfields))
	echo " Extra field ".$row["Field"].". ";
  else
	array_splice($pacfields, array_search($row["Field"], $pacfields), 1);
}
if(sizeof($pacfields)>0) 
{ echo " Missing fields: ".implode(", ",$pacfields);
  echo "<br>All fields: ".implode(", ",$fields);
  colordie("<br>Your version of Prestashop has ".$numrows." product_attribute_combination fields! Consult the Prestools helpdesk."); 
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
</script></head><body>
<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';

$id_lang = $input["id_lang"];
if($id_lang == "") die("No language specified!");

$combiblock = array(); /* rows from product_attribute */
$combis = array();		/* an array with "attribute blocks" to compare if they are already present */
$shoprows = array();   /* rows from product_attribute_shop */
$stockrows = array();	/* rows from stock_available */
$affected_shops = array();
$whrows = array();	/* rows from warehouse */
$bquery = "SELECT GROUP_CONCAT(id_attribute) AS attr_block, pa.* FROM ". _DB_PREFIX_."product_attribute pa";
$bquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pac on pa.id_product_attribute=pac.id_product_attribute";
$bquery .= " WHERE id_product='".$source."' AND pa.id_product_attribute IN (".$combis_array.")";
$bquery .= " GROUP BY id_product_attribute";
$bquery .= " ORDER BY id_attribute";
echo $bquery;
$bres=dbquery($bquery);
echo "<br>Originals: ";
if(mysqli_num_rows($bres) == 0)
	colordie("Nothing to copy!");
$default_on_found = false; /* make sure that there is a default row so that products that didn't have attributes will have a default */
$default_on_found_shop = array();
$default_on_found_shopblocks = array();
while($brow = mysqli_fetch_assoc($bres))
{ $attr_block = mysort($brow["attr_block"]); /* an attribute block looks like "15,62,89" what translates like "color:red,size:large,pattern:striped" */
  echo $attr_block."; ";
  if($brow["default_on"])
	  $default_on_found = true;
  $combiblock[$attr_block] = $brow;
  $combis[] = $attr_block;
  $shoprows[$attr_block] = array();
  $squery = "SELECT * FROM ". _DB_PREFIX_."product_attribute_shop";
  $squery .= " WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
  $sres=dbquery($squery);
  while($srow = mysqli_fetch_assoc($sres))
  { $shoprows[$attr_block][$srow["id_shop"]] = $srow;
	$affected_shops[] = $srow["id_shop"];
    if($srow["default_on"])
	  $default_on_found_shop[$srow["id_shop"]] = 1;
    $default_on_found_shopblocks[$srow["id_shop"]] = $attr_block;
  }
  $saquery = "SELECT * FROM ". _DB_PREFIX_."stock_available";
  $saquery .= " WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
  $sares=dbquery($saquery);
  while($sarow = mysqli_fetch_assoc($sares))
  { $stockrows[$attr_block][$sarow["id_shop"]."-".$sarow["id_shop_group"]] = $sarow;
  }
  $whrows[$attr_block] = array();
  $whquery = "SELECT * FROM ". _DB_PREFIX_."warehouse_product_location";
  $whquery .= " WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
  $whres=dbquery($whquery);
  while($whrow = mysqli_fetch_assoc($whres))
  { $whrows[$attr_block][$whrow["id_warehouse"]] = $whrow;
  }
}
$affected_shops = array_unique($affected_shops);

$impacters = array();
if(($input["sourcetype"] == "all") && (isset($input["OW_price"]) || isset($input["OW_weight"])))
{ $imquery = "SELECT * FROM "._DB_PREFIX_."attribute_impact";
  $imquery .= " WHERE id_product='".$source."'";
  $imres=dbquery($imquery);
  while($imrow = mysqli_fetch_assoc($imres))
  { if(isset($input["OW_price"])) $imprice=$imrow["price"]; else $imprice=0;
    if(isset($input["OW_weight"])) $imweight=$imrow["weight"]; else $imweight=0;
    $impacters[$imrow["id_attribute"]] = array($imprice,$imweight);
  }
}

/* now take care that there is at least one default combi */
if(!$default_on_found)   /* set the last one */
	$combiblock[$attr_block]["default_on"] = "1";
foreach($affected_shops AS $afshop)
{ if(!isset($default_on_found_shop[$afshop]))
	  $shoprows[$default_on_found_shopblocks[$afshop]][$afshop]["default_on"] = "1";
}

echo "<br>";

if($prestools_settings["demo_mode"])
{ echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
  die();
}
else if(file_exists("TE_plugin_combi_copy.php"))
  include "TE_plugin_combi_copy.php";
else 
{ if(($targettype != "products") || (strpos($prodtarget,",") > 0) || (strpos($prodtarget,"-") > 0))
	colordie("With the free version of Combination Copy you can only copy or delete the attribute combinations of one product at a time!");
  else 
    copyCombis($prodtarget);
}

function copyCombis($id_product)
{ global $combiblock, $combis, $shoprows, $stockrows, $whrows, $id_lang, $conn, $prodctr;
  global $input, $affected_shops, $timelimit, $modified_products_for_cpr, $starttime, $impacters;
  global $optional_pacfields, $pafields, $pasfields;
  $modified_products_for_cpr[] = $id_product = intval($id_product); /* this is the target product that will receive the combinations */

  if(isset($starttime) && ((time() - $starttime) > $timelimit))
  { echo "<script>parent.dynamo2('The script encountered a timeout before it had processed all records. Please update the time limit in the combicopy_proc.php file and run it again.');</script>";
	colordie("THE script encountered a timeout before it had processed all records. Please update the time limit in the combicopy_proc.php file and run it again.");
  }

  $query = "SELECT depends_on_stock FROM "._DB_PREFIX_."stock_available WHERE id_product='".mescape($id_product)."' AND id_product_attribute='0'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) != 0)
  { $row = mysqli_fetch_assoc($res);
    $depends_on_stock = $row["depends_on_stock"];
  }
  else
	$depends_on_stock = 0;
  
  $query = "SELECT name FROM "._DB_PREFIX_."product_lang WHERE id_product='".mescape($id_product)."' AND id_lang='".mescape( $id_lang)."'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
  { echo "<script>parent.dynamo2('Product ".$id_product." is not a valid target product id');</script>";
    colordie("Illegal target product id provided");
  }
  
  $data = mysqli_fetch_array($res);
  $productname = $data['name'];
  echo "prod ".$id_product." ".$productname;

  /* Look which combinations the target product already has. These won't be copied again - although they might be updated if specified so */
  $prodblock = array();
  $prodcombis = array();
  $bquery = "SELECT GROUP_CONCAT(id_attribute) AS attr_block, pa.id_product_attribute FROM ". _DB_PREFIX_."product_attribute pa";
  $bquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pac on pa.id_product_attribute=pac.id_product_attribute";
  $bquery .= " WHERE id_product='".$id_product."'";
  $bquery .= " GROUP BY id_product_attribute";
  $bquery .= " ORDER BY id_attribute"; 
  $bres=dbquery($bquery);
  echo "<br>Target ".$id_product." has blocks:";
  while($brow = mysqli_fetch_assoc($bres))
  { $attr_block = mysort($brow["attr_block"]);
    echo $attr_block,"; ";
    $prodblock[$attr_block] = $brow["id_product_attribute"];
    $prodcombis[] = $attr_block;
  }
  echo "<br>";
//  print_r($combiblock);  echo "<p>";
  
  foreach($combis AS $combi)
  { if(in_array($combi, $prodcombis))
    { $paupdates = "";
	  if(isset($input["OW_location"]) && version_compare(_PS_VERSION_ , "8.1", "<"))
		$paupdates .= "location='".$combiblock[$combi]["location"]."',";
	  if(isset($input["OW_wholesale_price"]))
		$paupdates .= "wholesale_price='".$combiblock[$combi]["wholesale_price"]."',";
	  if(isset($input["OW_price"]))
	    $paupdates .= "price='".$combiblock[$combi]["price"]."',";
	  if(isset($input["OW_ecotax"]))
	    $paupdates .= "ecotax='".$combiblock[$combi]["ecotax"]."',";
	  if(isset($input["OW_weight"]))
		$paupdates .= "weight='".$combiblock[$combi]["weight"]."',";	  
	  if(isset($input["OW_unit_price_impact"]))
		$paupdates .= "unit_price_impact='".$combiblock[$combi]["unit_price_impact"]."',";
	  if(isset($input["OW_minimal_quantity"]))
		$paupdates .= "minimal_quantity='".$combiblock[$combi]["minimal_quantity"]."',"; 
	  if(isset($input["OW_available_date"]))
		$paupdates .= "available_date='".$combiblock[$combi]["available_date"]."',";  
	  if(isset($input["OW_supplier_reference"]))
		$paupdates .= "supplier_reference='".$combiblock[$combi]["supplier_reference"]."',";
	  if(isset($input["OW_ean"]))
		$paupdates .= "ean13='".$combiblock[$combi]["ean13"]."',";
	  if(isset($input["OW_isbn"]))
		$paupdates .= "isbn='".$combiblock[$combi]["isbn"]."',";
	  if(isset($input["OW_mpn"]))
		$paupdates .= "mpn='".$combiblock[$combi]["mpn"]."',";
	  if(isset($input["OW_upc"]))
		$paupdates .= "upc='".$combiblock[$combi]["upc"]."',";
	  if(isset($input["OW_low_stock_threshold"]))
	  { if(is_null($combiblock[$combi]["low_stock_threshold"]))
		  $paupdates .= "low_stock_threshold=NULL,";
		else
		  $paupdates .= "low_stock_threshold='".$combiblock[$combi]["low_stock_threshold"]."',";
	  }
	  if(isset($input["OW_low_stock_alert"]))
		$paupdates .= "low_stock_alert='".$combiblock[$combi]["low_stock_alert"]."',";
      if($paupdates != "")
	  { $query = "UPDATE ". _DB_PREFIX_."product_attribute SET ".substr($paupdates,0,strlen($paupdates)-1)." WHERE id_product='".$id_product."' AND id_product_attribute='".$prodblock[$combi]."'";
		$res=dbquery($query);
	  }
		
	  foreach($shoprows[$combi] AS $shoprow)
	  { $pasupdates = "";
		if(in_array("date_upd",$pafields)) // Vivianne fix
		  $pasupdates .= "date_upd=now(),";
	    if(isset($input["OW_wholesale_price"]))
		  $pasupdates .= "wholesale_price='".$combiblock[$combi]["wholesale_price"]."',";
	    if(isset($input["OW_price"]))
		  $pasupdates .= "price='".$combiblock[$combi]["price"]."',";
	    if(isset($input["OW_ecotax"]))
		  $pasupdates .= "ecotax='".$combiblock[$combi]["ecotax"]."',";
	    if(isset($input["OW_weight"]))
		  $pasupdates .= "weight='".$combiblock[$combi]["weight"]."',";	  
	    if(isset($input["OW_unit_price_impact"]))
		  $pasupdates .= "unit_price_impact='".$combiblock[$combi]["unit_price_impact"]."',";
	    if(isset($input["OW_minimal_quantity"]))
		  $pasupdates .= "minimal_quantity='".$combiblock[$combi]["minimal_quantity"]."',";  
	    if(isset($input["OW_available_date"]))
		  $pasupdates .= "available_date='".$combiblock[$combi]["available_date"]."',";  
	    if(isset($input["OW_low_stock_threshold"]))
		{ if(is_null($combiblock[$combi]["low_stock_threshold"]))
			$pasupdates .= "low_stock_threshold=NULL,";
		  else
		    $pasupdates .= "low_stock_threshold='".$combiblock[$combi]["low_stock_threshold"]."',";
		}
	    if(isset($input["OW_low_stock_alert"]))
		  $pasupdates .= "low_stock_alert='".$combiblock[$combi]["low_stock_alert"]."',";
	    if($pasupdates != "")
		{ $query = "UPDATE ". _DB_PREFIX_."product_attribute_shop SET ".substr($pasupdates,0,strlen($pasupdates)-1)." WHERE id_product_attribute='".$prodblock[$combi]."' AND id_shop='".$shoprow["id_shop"]."'";
	      if(in_array("id_product",$pasfields))
		    $query .= " AND id_product='".$id_product."'";
		  $res=dbquery($query);
		}
	  }
  
	  if((isset($input["OW_quantity"]) && ($depends_on_stock == 0)) || (isset($input["OW_location"])))
	  { foreach($stockrows[$combi] AS $stockrow)
	    { $query = "UPDATE "._DB_PREFIX_."stock_available SET ";
		  if(isset($input["OW_quantity"]) && ($depends_on_stock == 0))
			$query .= "quantity='".$input["standard_quantity"]."'";
		  if((isset($input["OW_quantity"]) && ($depends_on_stock == 0)) && (isset($input["OW_location"])))
			$query .= ",";
		  if(isset($input["OW_location"]))
			$query .= "location='".$stockrow["location"]."'";
		  $query .= " WHERE id_product_attribute='".$prodblock[$combi]."' AND id_shop='".$stockrow["id_shop"]."'";
		  $query .= " AND id_shop_group='".$stockrow["id_shop_group"]."' AND id_product='".$id_product."'";	
		  $res=dbquery($query);
		}
	  }
	}
	else   /* not in_array($combi, $prodcombis)), so we can insert */
	{ 	$query = "INSERT INTO "._DB_PREFIX_."product_attribute SET id_product='".$id_product."',";
		$query .= "reference='".$input["standard_reference"]."',";
		if(isset($input["OW_supplier_reference"]))
		  $query .= "supplier_reference='".$combiblock[$combi]["supplier_reference"]."',";
		else
		  $query .= "supplier_reference='',";
	    if(version_compare(_PS_VERSION_ , "8.1", "<"))
	    { if(isset($input["OW_location"]))
		    $query .= "location='".$combiblock[$combi]["location"]."',";
		  else
		    $query .= "location='',";
		  $query .= "quantity='".$input["standard_quantity"]."',";
		}
	    if(isset($input["OW_ean"]))
		  $query .= "ean13='".$combiblock[$combi]["ean13"]."',";
		else
		  $query .= "ean13='',";
	    if(isset($input["OW_isbn"]))
		  $query .= "isbn='".$combiblock[$combi]["isbn"]."',";
		else if(in_array("isbn",$pafields))  // _PS_VERSION_ >= "1.7.0"
		  $query .= "isbn='',";
	    if(isset($input["OW_mpn"]))
		  $query .= "mpn='".$combiblock[$combi]["mpn"]."',";
		else if(in_array("mpn",$pafields))  // _PS_VERSION_ >= "1.7.0"
		  $query .= "mpn='',";
	    if(isset($input["OW_upc"]))
		  $query .= "upc='".$combiblock[$combi]["upc"]."',";
		else
		  $query .= "upc='',";
		if(in_array("date_upd",$pafields)) // Vivianne fix
			$query .= "date_upd=now(),";
	    $query .= "wholesale_price='".$combiblock[$combi]["wholesale_price"]."',";
	    $query .= "price='".$combiblock[$combi]["price"]."',";
  	    $query .= "ecotax='".$combiblock[$combi]["ecotax"]."',";
	    $query .= "weight='".$combiblock[$combi]["weight"]."',";
  	    $query .= "unit_price_impact='".$combiblock[$combi]["unit_price_impact"]."',";
		if((sizeof($prodcombis) == 0) && ($combiblock[$combi]["default_on"]==1)) /* ignore copied default when there already were attributes */
		  $query .= "default_on='1',";
		else if (_PS_VERSION_ < "1.6.1")
		  $query .= "default_on='0',";
		else 
		  $query .= "default_on=NULL,";	
	    if(isset($input["OW_low_stock_threshold"]))
		{ if(is_null($combiblock[$combi]["low_stock_threshold"]))
			$query .= "low_stock_threshold=NULL,";
		  else
		    $query .= "low_stock_threshold='".$combiblock[$combi]["low_stock_threshold"]."',";
		}
	    if(isset($input["OW_low_stock_alert"]))
		  $query .= "low_stock_alert='".$combiblock[$combi]["low_stock_alert"]."',";
	    $query .= "minimal_quantity='".$combiblock[$combi]["minimal_quantity"]."',";
  	    $query .= "available_date='".$combiblock[$combi]["available_date"]."'";
		$res=dbquery($query);
		$id_product_attribute = mysqli_insert_id($conn);
		
		foreach($shoprows[$combi] AS $shoprow)
		{ $query = "INSERT INTO "._DB_PREFIX_."product_attribute_shop SET id_product_attribute='".$id_product_attribute."',";
		  if(in_array("id_product",$pasfields))
		    $query .= "id_product='".$id_product."', ";
		  $query .= "id_shop='".$shoprow["id_shop"]."',";
	      if(isset($input["OW_wholesale_price"]))
		    $query .= "wholesale_price='".$shoprow["wholesale_price"]."',";
		  else
		    $query .= "wholesale_price='0',";
	      if(isset($input["OW_price"]))		
		    $query .= "price='".$shoprow["price"]."',";
		  else
		    $query .= "price='0',";
	      if(isset($input["OW_ecotax"]))
		    $query .= "ecotax='".$shoprow["ecotax"]."',";
		  else
		    $query .= "ecotax='0',";
	      if(isset($input["OW_weight"]))
		     $query .= "weight='".$shoprow["weight"]."',"; 
		  else
		     $query .= "weight='0',"; 
	      if(isset($input["OW_unit_price_impact"]))	
		    $query .= "unit_price_impact='".$shoprow["unit_price_impact"]."',";
		  else
		    $query .= "unit_price_impact='0',";			  
		  if((sizeof($prodcombis) == 0) && ($combiblock[$combi]["default_on"]==1))
		    $query .= "default_on='".$shoprow["default_on"]."',";
		  else if (_PS_VERSION_ < "1.6.1")
		    $query .= "default_on='0',";
		  else 
		    $query .= "default_on=NULL,";
	      if(isset($input["OW_minimal_quantity"]))
		    $query .= "minimal_quantity='".$shoprow["minimal_quantity"]."',";
		  else 
		    $query .= "minimal_quantity='1',";	
	      if(isset($input["OW_low_stock_threshold"]))
		  { if(is_null($combiblock[$combi]["low_stock_threshold"]))
			  $query .= "low_stock_threshold=NULL,";
		    else
		      $query .= "low_stock_threshold='".$combiblock[$combi]["low_stock_threshold"]."',";
		  }
	      if(isset($input["OW_low_stock_alert"]))
		    $query .= "low_stock_alert='".$shoprow["low_stock_alert"]."',";
		  if(isset($input["OW_available_date"]))
		    $query .= "available_date='".$shoprow["available_date"]."'";
		  else
		    $query .= "available_date='0000-00-00'";
		  $res=dbquery($query);
		}
		
		$query = "SELECT id_supplier, id_currency FROM "._DB_PREFIX_."product_supplier";
		$query .= " WHERE id_product='".$id_product."' AND id_product_attribute=0";
		$res=dbquery($query);
	    while($row = mysqli_fetch_assoc($res))
		{ $iquery = "INSERT INTO "._DB_PREFIX_."product_supplier";
		  $iquery .= " SET id_product_attribute='".$id_product_attribute."',id_product='".$id_product."', id_currency=".$row["id_currency"];
		  $iquery .= ",id_supplier='".$row["id_supplier"]."'";
		  $ires=dbquery($iquery);
		}
		
		foreach($stockrows[$combi] AS $stockrow)
		{ $query = "INSERT INTO "._DB_PREFIX_."stock_available SET id_product_attribute='".$id_product_attribute."',";
		  $query .= "id_product='".$id_product."', ";
		  $query .= "id_shop='".$stockrow["id_shop"]."',";
		  $query .= "id_shop_group='".$stockrow["id_shop_group"]."',";
		  if($depends_on_stock == 0)
		    $query .= "quantity='".$input["standard_quantity"]."',";
		  else
		    $query .= "quantity='0',";	
		  if(isset($input["OW_location"]))
		    $query .= "location='".$stockrow["location"]."',";
		  $query .= "depends_on_stock='".$stockrow["depends_on_stock"]."',";		  
		  $query .= "out_of_stock='".$stockrow["out_of_stock"]."'";
		  $res=dbquery($query);
		}
		
		foreach($whrows[$combi] AS $whrow)
		{ $query = "INSERT INTO "._DB_PREFIX_."warehouse_product_location";
		  $query .= " SET id_product_attribute='".$id_product_attribute."',";
		  $query .= "id_product='".$id_product."', ";
		  $query .= "id_warehouse='".$whrow["id_warehouse"]."',";
		  $query .= "location='".$whrow["location"]."'";
		  $res=dbquery($query);
		}
	
		$attributes = explode(",", $combi);
		foreach($attributes AS $attribute)
		{ $aquery = "INSERT INTO "._DB_PREFIX_."product_attribute_combination SET id_attribute='".$attribute."',id_product_attribute='".$id_product_attribute."'";
		  $ares=dbquery($aquery);
		  
		  $gres=dbquery("SELECT id_attribute_group FROM "._DB_PREFIX_."attribute WHERE id_attribute=".$attribute);
		  $grow = mysqli_fetch_assoc($gres);
		  
		  if(layered_is_installed())
		  { foreach($affected_shops AS $afshop)
		    { $aquery = "INSERT IGNORE INTO "._DB_PREFIX_."layered_product_attribute SET id_attribute='".$attribute."',id_product='".$id_product."',id_attribute_group=".$grow['id_attribute_group'].",id_shop=".$afshop;
		      $ares=dbquery($aquery);
			}
		  }
		}
	}
  }
  if(sizeof($prodcombis)==0) /* if the product didn't have attributes before we set the cache_default_attribute */
  { $query = "SELECT id_product_attribute FROM "._DB_PREFIX_."product_attribute ";
	$query .= " WHERE id_product='".$id_product."' AND default_on=1";
	$res=dbquery($query);	
	$row = mysqli_fetch_assoc($res);
	$ruquery = "UPDATE "._DB_PREFIX_."product SET cache_default_attribute='".$row["id_product_attribute"]."' WHERE id_product=".$id_product;
	$res = dbquery($ruquery);
	if(_PS_VERSION_ >= "1.7.8")
	{ $res = dbquery("UPDATE "._DB_PREFIX_."product SET product_type='combinations' WHERE id_product=".$id_product);
	}	
	  
	foreach($affected_shops AS $afshop)
	{ $query = "SELECT pas.id_product_attribute FROM "._DB_PREFIX_."product_attribute_shop pas ";
	  $query .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa on pa.id_product_attribute=pas.id_product_attribute";
	  $query .= " WHERE pa.id_product='".$id_product."' AND pas.default_on=1 AND pas.id_shop=".$afshop;
	  $res=dbquery($query);	
	  $row = mysqli_fetch_assoc($res);
	  $ruquery = "UPDATE "._DB_PREFIX_."product_shop SET cache_default_attribute='".$row["id_product_attribute"]."' WHERE id_product=".$id_product." AND id_shop=".$afshop;	    
	  $res = dbquery($ruquery);	  
	}
  }
  foreach($stockrows[$combi] AS $key => $stockrow)  /* make a total stock entry for the product as a whole (id_product_attribute = '0') */
  {		$parts = explode("-", $key);
		$query = "SELECT SUM(quantity) AS scount FROM "._DB_PREFIX_."stock_available";
		$query .= " WHERE id_product_attribute != '0' AND id_shop='".$parts[0]."' AND id_shop_group='".$parts[1]."'";
		$query .= " AND id_product='".$id_product."'";
		$res = dbquery($query);
		$row = mysqli_fetch_assoc($res);
		
		/* check whether entry already exists. Accordingly use insert or update */
		$query2 = "SELECT id_product_attribute FROM "._DB_PREFIX_."stock_available";
		$query2 .= " WHERE id_product_attribute = '0' AND id_shop='".$parts[0]."' AND id_shop_group='".$parts[1]."'";
		$query2 .= " AND id_product='".$id_product."'";
		$res2 = dbquery($query2);
		if(mysqli_num_rows($res2) == 0)
		{ $query = "INSERT INTO "._DB_PREFIX_."stock_available SET quantity='".$row["scount"]."', id_product_attribute = '0'";
		  $query .= ", id_shop='".$parts[0]."', id_shop_group='".$parts[1]."', id_product='".$id_product."'";
		  $res = dbquery($query);
		}
		else
		{ $query = "UPDATE "._DB_PREFIX_."stock_available SET quantity='".$row["scount"]."'";
		  $query .= " WHERE id_product_attribute = '0' AND id_shop='".$parts[0]."' AND id_shop_group='".$parts[1]."'";
		  $query .= " AND id_product='".$id_product."'";
		  $res = dbquery($query);
		}
  }
  
  if((sizeof($impacters) > 0) && (sizeof($prodcombis) == 0))
  { $imres = dbquery("DELETE FROM "._DB_PREFIX_."attribute_impact WHERE id_product=".$id_product);
    foreach($impacters AS $key => $impacter)
    { $imquery = "INSERT INTO "._DB_PREFIX_."attribute_impact SET id_product=".$id_product.",id_attribute='".$key."',price='".$impacter[0]."', weight='".$impacter[1]."'";
	  $imres = dbquery($imquery);
    }
  }
  
  /* set product ready for re-indexing */
  $query = "UPDATE "._DB_PREFIX_."product_shop SET indexed='0' WHERE id_product=".$id_product." AND id_shop IN (".implode(",",$affected_shops).")";
  $res = dbquery($query);
  echo "<br>";
  echo "<script>parent.dynamo2('".++$prodctr." ".$id_product." ".$productname."');</script>";
}

/* custom price rules */
  $query = "SELECT DISTINCT r.id_specific_price_rule FROM `". _DB_PREFIX_."specific_price_rule` r";
  $query .= " LEFT JOIN `". _DB_PREFIX_."specific_price_rule_condition_group` g ON g.id_specific_price_rule=r.id_specific_price_rule";
  $query .= " LEFT JOIN `". _DB_PREFIX_."specific_price_rule_condition` c ON c.id_specific_price_rule_condition_group=g.id_specific_price_rule_condition_group";
  $query .= " WHERE c.type='attribute'";
  $res=dbquery($query);
  $rules = array();
  while ($row=mysqli_fetch_array($res))
  { $rules[] = $row["id_specific_price_rule"];
/* if a rule has no conditions this routine will not select it. That is ok, as a rule with no condition applies to all products */
/* such a rule applies to product 0 in the database. There is no need to change it */
  }
  if(sizeof($rules)>0)
  { $rules = array_unique($rules);
    apply_catalogue_rules($rules, $modified_products_for_cpr);
  }

update_shop_index(6);  /* in ps_sourced_code.php. The number is the number of seconds that it is allowed to run. */
echo "<script>parent.dynamo2('Finished');</script>";
mysqli_close($conn);

function mysort($myarr)
{ $tmp = explode(",", $myarr);
  natsort($tmp);
  return implode(",",$tmp);
}