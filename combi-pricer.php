<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_product'])) $id_product = intval($_GET['id_product']); else $id_product = "";
if(isset($_GET['id_shop'])) $id_shop = intval($_GET['id_shop']); else $id_shop = "";
if(isset($_GET['id_lang'])) $id_lang = intval($_GET['id_lang']); else $id_lang = "";
if(isset($_GET["discount_included"])) $discount_included = 1; else $discount_included=0;
if(!isset($_GET['imgformat'])) {$_GET['imgformat']="";}

$input = $_GET;
if(empty($input['fields'])) // if not set, set default set of active fields
  $input['fields'] = array("price");
  
$product_name = "";
$product_exists = false;
$error = "";

if(intval($id_lang) == 0) 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');

if(intval($id_shop) == 0)
  $id_shop = get_configuration_value('PS_SHOP_DEFAULT');
  
if($id_product != "")
{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."product WHERE id_product=".$id_product);
  if(mysqli_num_rows($res) == 0) 
	$error = "There is no product with id ".$id_product;
  else
  { $product_exists = true;
    $res = dbquery("SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product=".$id_product." AND id_shop=".$id_shop);
    if(mysqli_num_rows($res) > 0) 
	{ $row = mysqli_fetch_array($res);
	  $product_price = $row["price"];
	}
    else
	  $error = "Product ".$id_product." is not present in shop ".id_shop;
  }
}

$shops = $shop_ids = array();
$shopblock = "";
$query = "SELECT s.id_shop,name from "._DB_PREFIX_."shop s";
if(($id_product != "") && $product_exists)
{ $query .= " LEFT JOIN "._DB_PREFIX_."product_shop ps ON ps.id_shop=s.id_shop";
  $query .= " WHERE ps.id_product=".$id_product;
}
$query .= " ORDER BY id_shop";
$res=dbquery($query);
while ($shop=mysqli_fetch_assoc($res))
{ if (isset($id_shop) && ($shop['id_shop']==$id_shop)) {$selected=' selected="selected" ';} else $selected="";
  $shopblock .= '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
  $shops[] = $shop['name'];
  $shop_ids[] = $shop['id_shop'];
}
if(!isset($input['shops'])) $input['shops'] = $shop_ids;
  
if($product_exists)
{ $nquery="select name from "._DB_PREFIX_."product_lang";
  $nquery .= " WHERE id_product='".$id_product."' AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
  $resn=dbquery($nquery);
  $row = mysqli_fetch_array($resn);
  $product_name = $row["name"];
}

if(($error == "") && ($id_product != ""))
{ $aquery="select * from ". _DB_PREFIX_."product_attribute";
  $aquery .= " WHERE id_product='".$id_product."'";
  $resa=dbquery($aquery);
  if(mysqli_num_rows($resa) == 0)
    $error = $id_product." has no attribute combinations";
}


?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Combination Price and Weight Setter</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
table.triplemain input {
	width:75px;
	text-align: right;
}

table.triplemain td {
	max-width: 150px;
}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
shop_ids = '<?php echo implode(",",$shop_ids); ?>';
trioflag = false; /* check that only one of price, priceVAT and VAT is editable at a time */
var prestashop_version = '<?php echo _PS_VERSION_ ?>';

function check_shopz(rowno)
{	var shopz_arr = document.getElementsByName("shopz"+rowno+"[]");
	if(shopz_arr.length > 0)          
	{ var found = false;
      for(var x=0; x<shopz_arr.length; x++)
		  if(shopz_arr[x].checked)
			  found=true;
	  if(!found)
	  { alert("At least one shop must be selected for a product!");
		return false;		  
	  }
	}
	return true;
}

function submitform()
{ MainForm.id_product.value=prodform.id_product.value;
  var tmp = prodform.id_product.value;
  if(parseInt(tmp) <= 0)
  { alert("Product id is not set!");
	return false;
  }
  MainForm.id_shop.value=prodform.id_shop.value;
  var tmp = document.getElementById("shopblock");
  var tmp2 = document.getElementById("mainshopblock");
  tmp2.innerHTML = tmp.innerHTML;
  
  if(MainForm.verbose.checked)
     MainForm.target="_blank";
  else
    MainForm.target="tank";
  
  return true;
}

function init()
{ 
}

</script>
</head><body onload="init()">
<?php print_menubar(); ?>
<table width="100%"><tr><td colspan=2 class="headline">
<a href="combi-pricer.php">Product combination price and weight setter</a><br>
This function allows you to set the prices and weights of product-attribute combinations by setting them
for the individual attributes - like you can do in Prestashop when you create attribute combinations. 
You can set per shop but standard all attributes are shown.<br>
</td>
<td align=right rowspan=4><iframe name="tank" height="95" width="230"></iframe></td>
</tr><tr><td id="notpaid" class="notpaid" colspan=2></td>
</tr><tr><td>
<?php
  echo "<b>".$error."</b>";
  echo "<form name=prodform action='combi-pricer.php' method=get>Product id: <input name=id_product value='".$id_product."' size=3>";

  if(in_array('price', $input["fields"])) $checked = "checked"; else $checked = "";
  echo ' &nbsp; &nbsp; fields: <input type=checkbox name="fields[]" value="price" '.$checked.'> price &nbsp; &nbsp;';
  if(in_array('wsprice', $input["fields"])) $checked = "checked"; else $checked = "";
  echo '<input type=checkbox name="fields[]" value="wsprice" '.$checked.'> wholesale_price &nbsp; &nbsp;';
  if(in_array('unitprice', $input["fields"])) $checked = "checked"; else $checked = "";
  echo '<input type=checkbox name="fields[]" value="unitprice" '.$checked.'> unit_price_impact &nbsp; &nbsp;';
  if(in_array('ecotax', $input["fields"])) $checked = "checked"; else $checked = "";
  echo '<input type=checkbox name="fields[]" value="ecotax" '.$checked.'> ecotax &nbsp; &nbsp;';
  if(in_array('weight', $input["fields"])) $checked = "checked"; else $checked = "";
  echo '<input type=checkbox name="fields[]" value="weight" '.$checked.'> weight';

  echo '</td></tr><tr><td>';
  if(sizeof($shops) > 1)
  { $len = sizeof($shops);
    echo 'Base shop: <select name="id_shop">'.$shopblock.'</select>';
    echo ' &nbsp; &nbsp; Shops: <span id="shopblock">';
    for($i=0; $i<$len; $i++)
	{ if(in_array($shop_ids[$i], $input["shops"])) $checked = "checked"; else $checked = "";
	  echo ' <input type=checkbox name="shops[]" value='.$shop_ids[$i].' '.$checked.'> '.$shops[$i];
	}
	echo "</span>";
  }
  else
  { echo '<span id="shopblock"><input type=hidden name=id_shop value='.$id_shop.'>';
    echo '<input type=hidden name="shops[]" value='.$id_shop.'></span>';
  }
  echo '</td>';
  echo '<td><p> &nbsp; <input type=submit value="Search"></form></td></tr></table>';
  
  if(($error != "") || ($id_product == ""))
  { echo "</body></html>";
    return;
  }
    
  echo '<form name="MainForm" action="combipricer-proc.php" onsubmit="submitform()" target=tank>';
  echo '<input type=hidden name=id_product><input type=hidden name=id_shop>';
//  echo 'Extra products for which this should be applied: <input name="extraproducts" >';
  echo '<input type=checkbox name=verbose>verbose &nbsp; &nbsp; ';
  echo ' &nbsp; &nbsp; &nbsp; &nbsp; <input type=submit>';
  echo '<br>';
	
  /* first get all the attributes and their groups for this product */
  $aquery = "SELECT DISTINCT pc.id_attribute, l.name, a.id_attribute_group,gl.name AS groupname";
  $aquery .= " FROM ". _DB_PREFIX_."product_attribute_shop pa";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=pc.id_attribute";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=pc.id_attribute AND l.id_lang='".$id_lang."'";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
  $aquery .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop;
  $aquery .= " ORDER BY a.id_attribute_group,a.position";
  $ares=dbquery($aquery);

  $groups = array();
  $groupnames = array();
  $prices = array();
  $attrnames = array();
  $weights = array();
  while ($arow=mysqli_fetch_assoc($ares))
  { $attrnames[$arow["id_attribute"]] = $arow["name"];
    if(!isset($groups[$arow["id_attribute_group"]]))
	  $groups[$arow["id_attribute_group"]] = array();
	$groups[$arow["id_attribute_group"]][] = $arow["id_attribute"];
	$groupnames[$arow["id_attribute_group"]] = $arow["groupname"];
    $prices[$arow["id_attribute"]] = -1;
    $weights[$arow["id_attribute"]] = 0;	
  }
  
  // we try to find the attribute price values in two ways: first we try to derive them from the
  //  combination prices. If that doesn't work we import from the ps_attribute_impact table.
  // The first option is only possible when a base combination with price zero can be found.
  $query = "SELECT pc.id_product_attribute, GROUP_CONCAT(id_attribute) AS attributes, price";
  $query .= " FROM ". _DB_PREFIX_."product_attribute_shop pa";
  $query .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
  $query .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop." GROUP BY id_product_attribute ORDER BY price LIMIT 1";
  $res=dbquery($query);
  $row=mysqli_fetch_assoc($res);
  
  /* optional: calculate attribute prices - only when a combination with zero price can be found */
  $preloaded = false;
  if($row["price"] == 0)
  { $battribs = explode(",", $row["attributes"]);
    $len = sizeof($battribs);
	
	/* start with a 0 price combination and take then combinations that differ on one attribute */
	for($i=0; $i<$len; $i++)
	{ $rest = array_diff($battribs,array($battribs[$i]));
	  $aquery = "SELECT pc.id_attribute, pa.price, GROUP_CONCAT(pc.id_attribute) AS attributes";
      $aquery .= " FROM "._DB_PREFIX_."product_attribute_shop pa";
      $aquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
      $aquery .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop;
	  $x = 0;
	  foreach($rest AS $res)
	  { $aquery .= " AND EXISTS (SELECT NULL FROM "._DB_PREFIX_."product_attribute_combination pac".$x." WHERE pa.id_product_attribute = pac".$x.".id_product_attribute AND pac".$x.".id_attribute=".$res.")";
	    $x++;
	  }
	  $aquery .= " GROUP BY pa.id_product_attribute";
      $ares=dbquery($aquery);
	  while ($arow=mysqli_fetch_assoc($ares))
	  { $pattribs = explode(",", $arow["attributes"]);
	    $newattribs = array_values(array_diff($pattribs, $battribs)); /* array_values makes the keys again zero-based */
		if(sizeof($newattribs) == 0)
		{ $prices[$battribs[$i]] = '0.00000';
		  continue;
		}
		$prices[$newattribs[0]] = $arow["price"];
	  }
	}

	/* now check for attributes that were not set */
	foreach($prices AS $key => $value)
	{ if($value == -1)
	  { $query = "SELECT pc.id_attribute, pa.price, GROUP_CONCAT(pc.id_attribute) AS attributes";
        $query .= " FROM "._DB_PREFIX_."product_attribute_shop pa";
        $query .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
        $query .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop;
	    $query .= " AND EXISTS (SELECT NULL FROM "._DB_PREFIX_."product_attribute_combination pac1 WHERE pa.id_product_attribute = pac1.id_product_attribute AND pac1.id_attribute=".$key.")";
  	    $query .= " GROUP BY pa.id_product_attribute";
        $res=dbquery($query);
	    $row=mysqli_fetch_assoc($res);
		
		$atts = explode(",",$row["attributes"]);
		if($row["price"] != 0)
		{ $price = 0;
		  foreach($atts AS $att)
		  { if($att == $key) continue;
		    $price = $price - $prices[$att];
		  }
		  $prices[$key] = $price;
		}
	  }	
	}
	
	/* now do a validity check. This check will fail when not all combinations fit the pattern */
	$preloaded = true;
	$query = "SELECT pc.id_attribute, pa.price, GROUP_CONCAT(pc.id_attribute) AS attributes";
	$query .= " FROM "._DB_PREFIX_."product_attribute_shop pa";
	$query .= " LEFT JOIN "._DB_PREFIX_."product_attribute_combination pc on pa.id_product_attribute=pc.id_product_attribute";
	$query .= " WHERE pa.id_product='".$id_product."' AND pa.id_shop=".$id_shop;
	$query .= " GROUP BY pa.id_product_attribute";
	$res=dbquery($query);
	while ($preloaded && ($row=mysqli_fetch_assoc($res)))
	{ $atts = explode(",",$row["attributes"]);
	  $price = 0;
	  foreach($atts AS $att)
	    $price = $price + $prices[$att];
	  if($price != $row["price"])
	    $preloaded = false;
	}
  }
  
  if(!$preloaded)
  { foreach($prices AS $key => $value)
	  $prices[$key] = 0;
	$query = "SELECT id_attribute,price,weight FROM "._DB_PREFIX_."attribute_impact";
	$query .= " WHERE id_product='".$id_product."'";
	$res=dbquery($query);
	while ($row=mysqli_fetch_assoc($res))
	  $prices[$row["id_attribute"]] = $row["price"];
  }
  
  /* now set the weight from the ps_attribute_ipact table */
  $query = "SELECT id_attribute,price,weight FROM "._DB_PREFIX_."attribute_impact";
  $query .= " WHERE id_product='".$id_product."'";
  $res=dbquery($query);
  while ($row=mysqli_fetch_assoc($res))
	$weights[$row["id_attribute"]] = $row["weight"];
  
  /* now print the form: one attribute group at a time */
  foreach($groups AS $key => $elts)
  { echo "<hr><center><b>Attribute group ".$key.": ".$groupnames[$key]."</b></center>";
   	$eltlines = array(); /* prepared for max 10 per line(=table) */
	$x = 0;
	$line = -1;
	foreach($elts AS $elt)
    { if(!($x % 10))
	  { $eltlines[++$line] = array();
	  }
	  $eltlines[$line][] = $elt;
	  $x++;
	}

    foreach($eltlines AS $eltline)
	{ echo '<table class="triplemain" style="margin-top:5px"><tr><td></td>';
	  foreach($eltline AS $elt)
	    echo '<td>'.$elt.' '.$attrnames[$elt].'</td>';
	  echo '</tr>';
	  if(in_array('price', $input["fields"])) 
	  { echo '<tr><td>price</td>';
	    foreach($eltline AS $elt)
	    { if($prices[$elt] == -1)
	        $val = 0;
	      else
	        $val = $prices[$elt];
	      echo '<td><input name="price'.$elt.'" value="'.$val.'"></td>';
	    }
	    echo '</tr>';
	  }
	  if(in_array('wsprice', $input["fields"])) 
	  { echo '<tr><td>wsprice</td>';
	    foreach($eltline AS $elt)
	    echo '<td><input name="wsprice'.$elt.'" value="0"></td>';
	  echo '</tr>';
	  }
	  if(in_array('unitprice', $input["fields"])) 
	  { echo '<tr><td>unitprice</td>';
	    foreach($eltline AS $elt)
	      echo '<td><input name="unitprice'.$elt.'" value="0"></td>';
	    echo '</tr>';
	  }
	  if(in_array('ecotax', $input["fields"])) 
	  { echo '<tr><td>ecotax</td>';
	    foreach($eltline AS $elt)
	      echo '<td><input name="ecotax'.$elt.'" value="0"></td>';
	    echo '</tr>';
	  }
	  if(in_array('weight', $input["fields"])) 
	  { echo '<tr><td>weight</td>';
	    foreach($eltline AS $elt)
	      echo '<td><input name="weight'.$elt.'" value="'.$weights[$elt].'"></td>';
	    echo '</tr>';
	  }
	  echo '</table>';
	}
  }

  echo '<span id="mainshopblock"></span></form><hr>';
  include "footer1.php";
?>
</body>
</html>