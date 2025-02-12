<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
if(isset($_GET["source"]))
	$input = $_GET;
else
	$input = $_POST;
$source = $input["source"];
$sourcetype = $input["sourcetype"]; /* "all" or "selector" */
if(isset($input["combis"]))
   $combis = $input["combis"];
else
   $combis = array();
$targettype = $input["targettype"];
$prodtarget = $input["mytargets"];
$prodctr = 0;
$modified_products_for_cpr = array(); /* for catalogue price rules */

$timelimit = 300; /* in seconds */
$starttime = time();
set_time_limit($timelimit+10); /* we add a little extra for gracious ending the script */

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

if($prestools_settings["demo_mode"])
   echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
else if(file_exists("TE_plugin_combi_delete.php"))
  include "TE_plugin_combi_delete.php";
else 
{ if(($targettype != "products") || (strpos($prodtarget,",") > 0))
	colordie("With the free version of Combination Copy you can only copy or delete the attribute combinations of one product at a time!");
  else 
    deleteCombis($prodtarget);
}

function deleteCombis($id_product)
{ global $combis, $id_lang, $conn, $prodctr, $sourcetype, $starttime, $timelimit,$modified_products_for_cpr;
  $modified_products_for_cpr[] = $id_product = intval($id_product);

  if((time() - $starttime) > $timelimit)
  { echo "<script>parent.dynamo2('The script encountered a timeout before it had processed all records. Please update the time limit in the combidelete_proc.php file and run it again.');</script>";
	echo "<script>alert('The script encountered a timeout before it had processed all records. Please update the time limit in the combidelete_proc.php file and run it again.');</script>";
	colordie("The script encountered a timeout before it had processed all records. Please update the time limit in the combidelete_proc.php file and run it again.");
  }

  $query = "SELECT name FROM "._DB_PREFIX_."product_lang WHERE id_product='".mescape($id_product)."' AND id_lang='".mescape($id_lang)."'";
  $res=dbquery($query);
  if(mysqli_num_rows($res) == 0)
  { echo "<script>parent.dynamo2('Product ".$id_product." is not a valid product id');</script>";
    colordie("Illegal product id provided");
  }
  $data = mysqli_fetch_array($res);
  $productname = $data['name'];
  
  $old_default = -1; /* when old_default==0 default must be re-assigned */
  if($sourcetype != "all")
  { $rquery = "SELECT pa.id_product_attribute, pas.default_on, id_shop FROM `"._DB_PREFIX_."product_attribute` pa";
    $rquery .= " LEFT JOIN `"._DB_PREFIX_."product_attribute_shop` pas on pa.id_product_attribute=pas.id_product_attribute";
    $rquery .= " WHERE pa.id_product='".$id_product."' AND pas.default_on='1'";
	$rres = dbquery($rquery);
	$old_default = 0;
    if(mysqli_num_rows($rres) > 0)
	{ $rrow = mysqli_fetch_assoc($rres);
	  $old_default = $rrow["id_product_attribute"];
	}
  }

  $bquery = "SELECT GROUP_CONCAT(id_attribute) AS attr_block, pa.id_product_attribute FROM `". _DB_PREFIX_."product_attribute` pa";
  $bquery .= " LEFT JOIN `"._DB_PREFIX_."product_attribute_combination` pac on pa.id_product_attribute=pac.id_product_attribute";
  $bquery .= " WHERE id_product='".$id_product."'";
  $bquery .= " GROUP BY id_product_attribute";
  $bres=dbquery($bquery);
  echo "<br>Target ".$id_product.". Deleting blocks: ";
  $blocks = "";
  $remainders = array();
  $affected_shops = array();
  while($brow = mysqli_fetch_assoc($bres))
  { $attr_block = mysort($brow["attr_block"]);
    if(($sourcetype != "all") && (!in_array($attr_block, $combis)))
	{	$remainders[] = $brow["id_product_attribute"];
		continue;
	}
		
	if($old_default == $brow["id_product_attribute"])
	  $old_default = 0;
	  
	$pacquery = "DELETE FROM `"._DB_PREFIX_."product_attribute_combination` WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($pacquery); 
	
    $paiquery = "DELETE FROM `"._DB_PREFIX_."product_attribute_image` WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($paiquery);
	
	$pssquery = "SELECT id_shop from `"._DB_PREFIX_."product_attribute_shop` WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
    $pssres = dbquery($pssquery); 
	while($pssrow = mysqli_fetch_assoc($pssres))
	{  $affected_shops[] = $pssrow["id_shop"];
	}
	
    $pasquery = "DELETE FROM `"._DB_PREFIX_."product_attribute_shop` WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($pasquery); 
	
    $paquery = "DELETE FROM `"._DB_PREFIX_."product_attribute` WHERE id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($paquery);
	
    $spquery = "DELETE FROM `"._DB_PREFIX_."specific_price` WHERE id_product='".$id_product."' AND id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($spquery);

    $psquery = "DELETE FROM `"._DB_PREFIX_."product_supplier` WHERE id_product='".$id_product."' AND id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($psquery);	
	
    $squery = "DELETE FROM `"._DB_PREFIX_."stock` WHERE id_product='".$id_product."' AND id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($squery);
	
    $saquery = "DELETE FROM `"._DB_PREFIX_."stock_available` WHERE id_product='".$id_product."' AND id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($saquery);
	
    $wplquery = "DELETE FROM `"._DB_PREFIX_."warehouse_product_location` WHERE id_product='".$id_product."' AND id_product_attribute='".$brow["id_product_attribute"]."'";
    $res = dbquery($wplquery);
	 
    echo $attr_block.", ";
	$blocks .= $brow["id_product_attribute"]." (".$attr_block."), ";
  }
  
  if(layered_is_installed())
  { /* handle the ps_layered_product_attribute table */
    /* we make two lists (in this table and real), compare them and delete the difference */
    $lpaquery = "SELECT GROUP_CONCAT(DISTINCT id_attribute) AS attribs FROM `"._DB_PREFIX_."layered_product_attribute` WHERE id_product='".$id_product."' GROUP BY id_product";
    $lpares = dbquery($lpaquery);
    $lparow = mysqli_fetch_assoc($lpares);
    if($lparow["attribs"] == "")
      $lpaarr = array();
    else
      $lpaarr = explode(",", $lparow["attribs"]);
  }
  
  $attquery = "SELECT GROUP_CONCAT(DISTINCT id_attribute) AS attribs FROM `"._DB_PREFIX_."product_attribute` pa";
  $attquery .= " LEFT JOIN `"._DB_PREFIX_."product_attribute_combination` pac on pa.id_product_attribute=pac.id_product_attribute";
  $attquery .= " WHERE id_product='".$id_product."'";
  $attquery .= " GROUP BY id_product";
  $attres = dbquery($attquery);
  if(mysqli_num_rows($attres) > 0)
  { $attrow = mysqli_fetch_assoc($attres);
    $attarr = explode(",", $attrow["attribs"]);
  }
  else
	$attarr = [];
  
  if(layered_is_installed())
  { $extras = array_diff($lpaarr, $attarr);
    if(sizeof($extras) > 0)
    { $dxquery = "DELETE FROM `"._DB_PREFIX_."layered_product_attribute` WHERE id_product='".$id_product."' AND id_attribute IN (".implode(",",$extras).")";
      $res = dbquery($dxquery);
	}
  }
  
  /* we may have left the product without default attribute. If so, we will repair that */
  if ($old_default == 0)
  { if(sizeof($remainders) > 0)
    { $rrow = mysqli_fetch_array($rres);
	  $ruquery = "UPDATE `"._DB_PREFIX_."product_attribute_shop` SET default_on='1' WHERE id_product_attribute='".$remainders[0]."'";
      $rures = dbquery($ruquery);	
	  $ruquery = "UPDATE `"._DB_PREFIX_."product_attribute` SET default_on='1' WHERE id_product_attribute='".$remainders[0]."'";
      $rures = dbquery($ruquery);
	  $ruquery = "UPDATE "._DB_PREFIX_."product SET cache_default_attribute='".$remainders[0]."' WHERE id_product=".$id_product;
	  $res = dbquery($ruquery); 
	  $ruquery = "UPDATE "._DB_PREFIX_."product_shop SET cache_default_attribute='".$remainders[0]."' WHERE id_product=".$id_product;
	  $res = dbquery($ruquery);  	  
	}
  }
  
  if(sizeof($remainders) == 0)  /* when "all" attribute combinations are deleted */
  { /* clear cache_default_attribute. Not doing this may cause faulty prices */
    /* See http://stackoverflow.com/questions/21694442/prestashop-product-showing-wrong-price-in-category-page-but-right-in-the-produc */
    $query = "UPDATE "._DB_PREFIX_."product SET cache_default_attribute='0' WHERE id_product=".$id_product;
    $res = dbquery($query); 
    $query = "UPDATE "._DB_PREFIX_."product_shop SET cache_default_attribute='0' WHERE id_product=".$id_product;
    $res = dbquery($query);
	if(_PS_VERSION_ >= "1.7.8")
	{ $res = dbquery("UPDATE "._DB_PREFIX_."product SET product_type='standard' WHERE id_product=".$id_product);
	}	
  }
  
  /* set product ready for re-indexing */
  $affected_shops = array_unique($affected_shops);
  if(sizeof($affected_shops)>0)
  { $query = "UPDATE "._DB_PREFIX_."product_shop SET indexed='0' WHERE id_product=".$id_product." AND id_shop IN (".implode(",",$affected_shops).")";
    $res = dbquery($query);
  }
  echo "<br>";
  echo "<script>parent.dynamo2('".++$prodctr." ".$id_product." ".$productname.". Deleting ".$blocks."');</script>";
}

  /* different attributes means that different specific price rules may apply. */
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
  
  echo "Updating index: ";
  update_shop_index(6);  /* in ps_sourced_code.php. The number is the number of seconds that it is allowed to run. */

echo "<script>parent.dynamo2('Finished');</script>";
mysqli_close($conn);

function mysort($myarr)
{ $tmp = explode(",", $myarr);
  natsort($tmp);
  return implode("-",$tmp);
}