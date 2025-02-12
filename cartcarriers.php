<?php 
if(!@include 'approve.php') die( "approve.php was not found!");

$error = $message = "";
if(!isset($_GET["cart_id"]))
{ $error = "No cart id!";
  $cart = 0;
}
else
{ $cart = intval($_GET["cart_id"]);
  if($cart==0) $error = "No valid cart provided!";
}

$id_lang = get_configuration_value('PS_LANG_DEFAULT'); 


$query="SELECT * FROM "._DB_PREFIX_."cart c WHERE c.id_cart=".$cart;
$res=dbquery($query);
if(mysqli_num_rows($res) == 0) 
	$error = $cart." is not a valid cart number!";
else if(1 == get_configuration_value('PS_DETECT_COUNTRY'))
{ $error = $cart." has no address assigned and this script cannot see the geodetected country!";	
}
else 
{ $row = mysqli_fetch_array($res);
  $id_shop = $row["id_shop"];
  $res = dbquery("SELECT name FROM "._DB_PREFIX_."shop WHERE id_shop=".$id_shop);
  list($shopname) = mysqli_fetch_array($res);
  $id_customer = $row["id_customer"];
  $id_guest = $row["id_guest"];
  if($row["id_address_delivery"] == 0)
  {	// Cart doesn't have a delivery address yet 
    $id_country = get_configuration_value('PS_COUNTRY_DEFAULT',NULL,$id_shop);
    $shop_country = get_configuration_value('PS_SHOP_COUNTRY_ID',NULL,$id_shop);
    $id_state = get_configuration_value('PS_STATE_DEFAULT',NULL,$id_shop);
    $qry = "SELECT cl.name AS country, z.name AS zonename,co.id_zone,co.contains_states,";
    $qry .= "co.need_identification_number,co.need_zip_code FROM "._DB_PREFIX_."country co";
    $qry .= " LEFT JOIN "._DB_PREFIX_."country_lang cl on cl.id_country=co.id_country AND cl.id_lang=".$id_lang;
    $qry .= " LEFT JOIN "._DB_PREFIX_."zone z on co.id_zone=z.id_zone";
	$qry .= " WHERE co.id_country=".$id_country;
    $rs = dbquery($qry);
    $rw = mysqli_fetch_array($rs);
    $id_zone = $rw["id_zone"];
    $zonename = $rw["zonename"];
    $countryname = $rw["country"];
    $contains_states = $rw["contains_states"];
    $need_identification_number = $rw["need_identification_number"];
    $need_zip_code = $rw["need_zip_code"];
	$id_address = 0;
	
	if($id_customer != 0)
	{ $qury = "SELECT cg.id_group, gl.name AS groupname FROM "._DB_PREFIX_."customer_group cg";
      $qury .= " LEFT JOIN "._DB_PREFIX_."group_lang gl on gl.id_group=cg.id_group AND gl.id_lang=".$id_lang;
	  $qury .= " WHERE cg.id_customer=".$id_customer;
      $rus = dbquery($qury);
      $ruw = mysqli_fetch_array($rus);
      $id_group = $ruw["id_group"];
      $groupname = $ruw["groupname"];
	}
  }
  else

  { $query="SELECT c.id_shop,id_address_delivery,c.id_customer,id_guest,a.id_country,a.postcode";
    $query .= ",cl.name AS country,cg.id_group,gl.name AS groupname, z.name AS zonename";
    $query .= ",s.name AS shopname,co.id_zone,co.contains_states,co.need_identification_number,co.need_zip_code";
    $query .= ",a.id_state, st.id_zone AS statezone,st.name AS statename";
    $query .= " FROM "._DB_PREFIX_."cart c";
    $query .= " LEFT JOIN "._DB_PREFIX_."address a on a.id_address=c.id_address_delivery";
    $query .= " LEFT JOIN "._DB_PREFIX_."country co on co.id_country=a.id_country";
    $query .= " LEFT JOIN "._DB_PREFIX_."country_lang cl on cl.id_country=a.id_country AND cl.id_lang=".$id_lang;
    $query .= " LEFT JOIN "._DB_PREFIX_."state st on st.id_state=a.id_state";
    $query .= " LEFT JOIN "._DB_PREFIX_."customer_group cg on cg.id_customer=c.id_customer";
    $query .= " LEFT JOIN "._DB_PREFIX_."group_lang gl on cg.id_group=gl.id_group AND gl.id_lang=".$id_lang;
    $query .= " LEFT JOIN "._DB_PREFIX_."shop s on c.id_shop=s.id_shop";
    $query .= " LEFT JOIN "._DB_PREFIX_."zone z on co.id_zone=z.id_zone";
    $query .= " WHERE c.id_cart=".$cart;
    $res=dbquery($query);
    $row = mysqli_fetch_array($res);
    $id_shop = $row["id_shop"];
    $shopname = $row["shopname"];
    $id_country = $row["id_country"];
    $id_state = $row["id_state"];
    $id_zone = $row["id_zone"];
    $zonename = $row["zonename"];
    $statezone = $row["statezone"];
    $statename = $row["statename"];
    $postcode = $row["postcode"];
    $countryname = $row["country"];
    $id_customer = $row["id_customer"];
    $id_guest = $row["id_guest"];
    $id_group = $row["id_group"];
    $groupname = $row["groupname"];
    $contains_states = $row["contains_states"];
    $need_identification_number = $row["need_identification_number"];
    $need_zip_code = $row["need_zip_code"];
    $id_address = $row["id_address_delivery"];
  }
}
?>
<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Cart Carriers</title>
<style>
table.ranger {margin-top: 0; }
table.ranger, table.ranger td { border-collapse: collapse;padding:4px; border: 1px solid #434343;}
table.ranger td {text-align: center}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93></iframe></div>
<center><h1 style="margin-bottom:1px;">Prestashop Cart Carriers</h1>
<?php
if($cart == 0) $cart = "";
echo '<form>for cart <input name="cart_id" value="'.$cart.'" size=4> &nbsp; &nbsp; <input type=submit>';
echo "<p></center>
Sometimes people are surprised by the carriers that are offered to a customer. A quite common problem is no carriers at all.
This function tries to make the process transparent by showing the criteria and how they work out.<p>";
if($error != "")
{ echo '<p><b>ERROR: '.$error.'<p></b>';
  echo 'The last carts with an address in your shop are:';
  $query = "SELECT c.id_cart,cl.name AS country, CONCAT(cu.firstname,' ',cu.lastname) AS customer FROM "._DB_PREFIX_."cart c";
  $query .= " LEFT JOIN "._DB_PREFIX_."address a on a.id_address=c.id_address_delivery";
  $query .= " LEFT JOIN "._DB_PREFIX_."country co on co.id_country=a.id_country";
  $query .= " LEFT JOIN "._DB_PREFIX_."country_lang cl on cl.id_country=a.id_country AND cl.id_lang=".$id_lang;
  $query .= " LEFT JOIN "._DB_PREFIX_."customer cu on cu.id_customer=c.id_customer";
  $query .= " WHERE co.id_country IS NOT NULL ORDER BY id_cart DESC LIMIT 10";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
    echo "<br>".$row["id_cart"]." (".$row["customer"]." - ".$row["country"].")";
  die();
}

$carriernames = array();
$res = dbquery("SELECT name, id_carrier FROM "._DB_PREFIX_."carrier ORDER BY id_carrier");
while($row = mysqli_fetch_array($res))
{ $carriernames[$row["id_carrier"]] = $row["name"];
}

$query="SELECT pl.name AS pname, ca.name AS cname, ca.id_carrier FROM "._DB_PREFIX_."product_carrier pc";
$query .= " LEFT JOIN "._DB_PREFIX_."cart_product cp on cp.id_product=pc.id_product";
$query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl on pl.id_product=pc.id_product AND pl.id_shop=".$id_shop." AND pl.id_lang=".$id_lang;
$query .= " LEFT JOIN "._DB_PREFIX_."carrier ca on ca.id_reference=pc.id_carrier_reference";
$query .= " WHERE cp.id_cart=".$cart." AND cp.id_shop=".$id_shop." AND ca.deleted=0 ORDER BY pc.id_product";
$res=dbquery($query);
if(mysqli_num_rows($res) > 0)
{ echo "<p>You have product-assigned carriers (these will increase the costs):<br>";
  while($row = mysqli_fetch_array($res))
  { if($row["cname"] == '0') $row["cname"] = $shopname;
    echo "<b>".$row["pname"].":</b> ".$row["id_carrier"]."-".$row["cname"]."<br>";
  }
}
else 
  echo "<p>You have no product-assigned carriers";

$allowed_carriers = array();
echo "<p>Carriers allowed for country <b>".$id_country."-".$countryname." in zone ".$id_zone."-".$zonename."</b>:<br>";
$query="SELECT ca.name AS cname, ca.id_carrier FROM "._DB_PREFIX_."carrier ca";
$query .= " INNER JOIN "._DB_PREFIX_."carrier_zone cz on ca.id_carrier=cz.id_carrier";
$query .= " LEFT JOIN "._DB_PREFIX_."country c on cz.id_zone=c.id_zone";
$query .= " WHERE c.id_country=".$id_country." AND ca.deleted=0 AND ca.active=1";
$res=dbquery($query);
if(mysqli_num_rows($res) == 0)
	echo "No carriers for this country<br>";
while($row = mysqli_fetch_array($res))
{ if($row["cname"] == '0') $row["cname"] = $shopname;
  $allowed_carriers[] = $row["id_carrier"];
  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
}

if(!$id_zone)
{ echo "Your country has no zone set. That is a problem.<br>";
}

if($contains_states && (!$statezone))
{ echo "Your state has no zone set. This may give problems.<br>";
}

if($contains_states)
{ if($id_state == 0)
  { echo "You have no state set in a country with states. This may give problems.<br>";
  }
  else
  { $res=dbquery("SELECT * FROM "._DB_PREFIX_."state WHERE id_country=".$id_country);
    if(mysqli_num_rows($res) == 0)
	{ echo "You send to country with states. But the database contains no states for it. This may give problems.<br>";
	}
	else
	{ $res=dbquery("SELECT * FROM "._DB_PREFIX_."state WHERE id_state=".$id_state);
      if(mysqli_num_rows($res) == 0)
	  { echo "The state you selected was not found in the database. This may give problems.<br>";
	  }
	  else
	  { $stcarriers = array();
		echo "<p>Carriers allowed for state <b>".$statename."</b>:<br>";
	    $query="SELECT ca.name AS cname, ca.id_carrier,st.id_zone FROM "._DB_PREFIX_."carrier ca";
	    $query .= " INNER JOIN "._DB_PREFIX_."carrier_zone cz on ca.id_carrier=cz.id_carrier";
	    $query .= " LEFT JOIN "._DB_PREFIX_."state st on cz.id_zone=st.id_zone";
	    $query .= " WHERE st.id_state=".$id_state." AND ca.deleted=0 AND ca.active=1";
	    $res=dbquery($query);
	    if(mysqli_num_rows($res) == 0)
	    echo "No carriers for this state<br>";
	    while($row = mysqli_fetch_array($res))
		{ if($row["cname"] == '0') $row["cname"] = $shopname;
		  $stcarriers[] = $row["id_carrier"];
		  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
		}
		
		$allowed_carriers = array_intersect($allowed_carriers,$stcarriers);
	  }
	}
  }
}

if($need_zip_code && ($id_address != 0) && ($postcode == ""))
	echo "You did not specify a zip code for a country where that is needed.<br>";

/* this script doesn*t handle customers with several group, but it shows them here so that you are informed */
if($id_customer != 0)
{ $query="SELECT cg.id_group, gl.name AS groupname FROM "._DB_PREFIX_."customer_group cg";
  $query .= " LEFT JOIN "._DB_PREFIX_."group_lang gl ON cg.id_group=gl.id_group AND gl.id_lang=".$id_lang;
  $query .= " WHERE cg.id_customer=".$id_customer;
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 1)
  { echo "<p>This customer belongs to the groups: ";
    while($row = mysqli_fetch_array($res))
	  echo $row['id_group']."-".$row['groupname'].", ";
  }
}

if(isset($id_group))
{ $gcarriers = $gids = array();
	echo "<p>Carriers from above allowed for customers from group <b>".$id_group."-".$groupname."</b>:<br>";
	$query="SELECT ca.name AS cname,ca.id_carrier FROM "._DB_PREFIX_."carrier_group cg";
	$query .= " LEFT JOIN "._DB_PREFIX_."carrier ca on ca.id_carrier=cg.id_carrier";
	$query .= " WHERE cg.id_group=".$id_group." AND ca.id_carrier IN (".implode(",", $allowed_carriers).")";
	$query .= " ORDER BY id_carrier";
	$res=dbquery($query);
	if(mysqli_num_rows($res) == 0)
		echo "No carriers for this group<br>";
	while($row = mysqli_fetch_array($res))
	{ if($row["cname"] == '0') $row["cname"] = $shopname;
	  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
	  $gcarriers[] = $row["id_carrier"];
	}

	$allowed_carriers = array_intersect($allowed_carriers,$gcarriers);
}	

$scarriers = array();
echo "<p>Carriers from above allowed for orders from shop <b>".$id_shop."-".$shopname."</b>:<br>";
$query="SELECT ca.name AS cname, ca.id_carrier FROM "._DB_PREFIX_."carrier_shop cs";
$query .= " LEFT JOIN "._DB_PREFIX_."carrier ca on ca.id_carrier=cs.id_carrier";
$query .= " WHERE cs.id_shop=".$id_shop." AND ca.id_carrier IN (".implode(",", $allowed_carriers).")";
$query .= " ORDER BY id_carrier";
$res=dbquery($query);
if(mysqli_num_rows($res) == 0)
	echo "No carriers for this shop<br>";
while($row = mysqli_fetch_array($res))
{ if($row["cname"] == '0') $row["cname"] = $shopname;
  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
  $scarriers[] = $row["id_carrier"];
}
$allowed_carriers = array_intersect($allowed_carriers,$scarriers);

echo "<p>";

/* look whether the order contains out of stock products */
if(get_configuration_value('PS_STOCK_MANAGEMENT'))
{ $val = get_configuration_value('PS_ORDER_OUT_OF_STOCK');
  if(!$val)
  { /* get shop group and its shared_stock status */
    $query="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
    $query .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
    $res=dbquery($query);
    $row = mysqli_fetch_array($res);
    $id_shop_group = $row['id_shop_group'];
    $share_stock = $row["share_stock"];
    $shop_group_name = $row["name"];
    if($share_stock)
	{ $query="SELECT cp.quantity AS cartquantity, name, sa.quantity AS stockquantity FROM "._DB_PREFIX_."product p";
	  $query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl on pl.id_product=p.id_product AND pl.id_shop=".$id_shop." AND pl.id_lang=".$id_lang;
	  $query .= " INNER JOIN "._DB_PREFIX_."cart_product cp on cp.id_product=p.id_product";
	  $query .= " LEFT JOIN "._DB_PREFIX_."stock_available sa on cp.id_product=sa.id_product AND cp.id_product_attribute=sa.id_product_attribute";
	  $query .= " WHERE cp.id_cart=".$cart." AND sa.id_shop_group=".$id_shop_group." AND cp.quantity>sa.quantity";
	}
	else
	{ $query="SELECT cp.quantity AS cartquantity, name, sa.quantity AS stockquantity FROM "._DB_PREFIX_."product p";
	  $query .= " LEFT JOIN "._DB_PREFIX_."product_lang pl on pl.id_product=p.id_product AND pl.id_shop=".$id_shop." AND pl.id_lang=".$id_lang;
	  $query .= " INNER JOIN "._DB_PREFIX_."cart_product cp on cp.id_product=p.id_product";
	  $query .= " LEFT JOIN "._DB_PREFIX_."stock_available sa on cp.id_product=sa.id_product AND cp.id_product_attribute=sa.id_product_attribute";
	  $query .= " WHERE cp.id_cart=".$cart." AND sa.id_shop=".$id_shop." AND cp.quantity>sa.quantity";
	}
	$res=dbquery($query);
	if(mysqli_num_rows($res)==0)
	  echo "No products out of stock";
	else
	  echo "Products that are out of stock:";
	while($row = mysqli_fetch_array($res))
	{ if(intval($row["cartquantity"]) > intval($row["stockquantity"]))
	  { if($row["name"] == '0') $row["name"] = $shopname;
	    echo "<br><b>".$row["name"]."</b>: ".$row["cartquantity"]." ordered, ".$row["stockquantity"]." in stock";
      }
	}
	if(mysqli_num_rows($res)>0)
		die("<br>This cart cannot be sent because not all products are in stock");
	echo "<p>";
	
  }
} 

echo "<p>Product properties as Num*Id(Width*Height*Depth-Weight):<br> ";
$query="SELECT width, height, depth, cp.quantity, weight, p.id_product FROM "._DB_PREFIX_."product p";
$query .= " INNER JOIN "._DB_PREFIX_."cart_product cp on cp.id_product=p.id_product";
$query .= " WHERE cp.id_cart=".$cart;
$totweight=$maxwidth=$maxheight=$maxdepth=0;
$res=dbquery($query);
while($row = mysqli_fetch_array($res))
{ echo $row["quantity"]."*".$row["id_product"]."(".$row["width"]."*".$row["height"]."*";
  echo $row["depth"]."-".$row["weight"]."), ";
  $totweight += $row["quantity"]*$row["weight"];
  if($row["height"] > $maxheight) $maxheight = $row["height"];
  if($row["width"] > $maxwidth) $maxwidth = $row["width"];
  if($row["depth"] > $maxdepth) $maxdepth = $row["depth"];
}
echo "<br><b>Total weight: </b>".$totweight."; <b>Max height:</b> ".$maxheight."; <b>Max width:</b> ".$maxwidth."; <b>Max depth:</b> ".$maxdepth.".";
echo "<p>";

$forbidden_carriers = array();
$query="SELECT max(width) AS maxwidth,max(height) AS maxheight,max(depth) AS maxdepth,SUM(cp.quantity*weight) AS weightsum";
$query .= " ,SUM(cp.quantity*ps.price) AS totprice FROM "._DB_PREFIX_."product p";
$query .= " LEFT JOIN "._DB_PREFIX_."product_shop ps on ps.id_product=p.id_product AND ps.id_shop=".$id_shop;
$query .= " INNER JOIN "._DB_PREFIX_."cart_product cp on cp.id_product=p.id_product";
$query .= " WHERE cp.id_cart=".$cart;
$mres=dbquery($query);
$mrow = mysqli_fetch_array($mres);
$maxwidth = $mrow["maxwidth"];
$maxheight = $mrow["maxheight"];
$maxdepth = $mrow["maxdepth"];
$weightsum = $mrow["weightsum"];
$totprice = $mrow["totprice"];

$query="SELECT name AS cname, id_carrier FROM "._DB_PREFIX_."carrier";
$query .= " WHERE max_width !=0 AND max_width<'".$maxwidth."'";
$query .= " AND id_carrier IN (".implode(",", $allowed_carriers).")";
$res=dbquery($query);
if(mysqli_num_rows($res) == 0)
	echo "No relevant width restrictions";
else
	echo "Carriers that cannot handle this width (".$maxwidth."): ";
while($row = mysqli_fetch_array($res))
{ if($row["cname"] == '0') $row["cname"] = $shopname;
  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
  $forbidden_carriers[] = $row["id_carrier"];
}
echo "<br>";

$query="SELECT name AS cname, id_carrier FROM "._DB_PREFIX_."carrier";
$query .= " WHERE max_height !=0 AND max_height<'".$maxheight."'";
$query .= " AND id_carrier IN (".implode(",", $allowed_carriers).")";
$res=dbquery($query);
if(mysqli_num_rows($res) == 0)
	echo "No relevant height restrictions";
else
	echo "Carriers that cannot handle this height (".$maxheight."): ";
while($row = mysqli_fetch_array($res))
{ if($row["cname"] == '0') $row["cname"] = $shopname;
  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
  $forbidden_carriers[] = $row["id_carrier"];
}
echo "<br>";

$query="SELECT name AS cname, id_carrier FROM "._DB_PREFIX_."carrier";
$query .= " WHERE max_depth !=0 AND max_depth<'".$maxdepth."'";
$query .= " AND id_carrier IN (".implode(",", $allowed_carriers).")";
$res=dbquery($query);
if(mysqli_num_rows($res) == 0)
	echo "No relevant depth restrictions";
else
	echo "Carriers that cannot handle this depth (".$maxdepth."): ";
while($row = mysqli_fetch_array($res))
{ if($row["cname"] == '0') $row["cname"] = $shopname;
  echo "<b>".$row["id_carrier"]."-".$row["name"]."</b>, ";
  $forbidden_carriers[] = $row["id_carrier"];
}
echo "<br>";

$query="SELECT name AS cname, id_carrier FROM "._DB_PREFIX_."carrier";
$query .= " WHERE max_weight !=0 AND max_weight<'".$weightsum."'";
$query .= " AND id_carrier IN (".implode(",", $allowed_carriers).")";
$res=dbquery($query);
if(mysqli_num_rows($res) == 0)
	echo "No relevant weight restrictions";
else
{ echo "Carriers that cannot handle this weight (".$weightsum."): ";
}
while($row = mysqli_fetch_array($res))
{ if($row["cname"] == '0') $row["cname"] = $shopname;
  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b>, ";
  $forbidden_carriers[] = $row["id_carrier"];
}
if(mysqli_num_rows($res) > 0)
  echo "<br>(this limit does not take into account that an order could be sent as more than one package)";
echo "<p>";

$allowed_carriers = array_diff($allowed_carriers,$forbidden_carriers);

/* when range_behavior=1 you cannot use a carrier when the weight/price is above the highest range */
/* shipping method: 1=price; 2=weight */
$query="SELECT name AS cname, id_carrier,shipping_method FROM "._DB_PREFIX_."carrier";
$query .= " WHERE range_behavior=1 AND id_carrier IN (".implode(",",$allowed_carriers).")";
$res=dbquery($query);
while($row = mysqli_fetch_array($res))
{ if($row["shipping_method"] == 2) /* price */
  { $rquery = "SELECT max(r.delimiter2) AS maxprice,r.id_carrier FROM "._DB_PREFIX_."delivery d";
    $rquery .= " LEFT JOIN "._DB_PREFIX_."range_price r ON d.id_range_price=r.id_range_price";
    $rquery .= " WHERE d.id_carrier=".$row["id_carrier"]." AND d.id_zone=".$id_zone." AND r.id_carrier=".$row["id_carrier"];
	$rquery .= " HAVING maxprice < ".$totprice;
    $rres=dbquery($rquery); 
	if(mysqli_num_rows($rres) > 0)
	{ $rrow = mysqli_fetch_array($rres);
	  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b> has max price ".$rrow["maxprice"].". Your cart contains ".$totprice."<br>";
 	  $forbidden_carriers[] = $row["id_carrier"];
	}
  }
  else /* $row["shipping method"] == 1 = weight */
  { $rquery = "SELECT MAX(r.delimiter2) AS maxweight,d.id_carrier FROM "._DB_PREFIX_."delivery d";
    $rquery .= " LEFT JOIN "._DB_PREFIX_."range_weight r ON d.id_range_weight=r.id_range_weight";
    $rquery .= " WHERE d.id_carrier=".$row["id_carrier"]." AND d.id_zone=".$id_zone." AND r.id_carrier=".$row["id_carrier"];
	$rquery .= " HAVING maxweight < ".$weightsum;
    $rres=dbquery($rquery);
	if(mysqli_num_rows($rres) > 0)
	{ $rrow = mysqli_fetch_array($rres);
	  echo "<b>".$row["id_carrier"]."-".$row["cname"]."</b> has max weight ".$rrow["maxweight"].". Your cart contains ".$weightsum."<br>";
 	  $forbidden_carriers[] = $row["id_carrier"];
	}
  }
}

echo "<p><b>Modules check</b> ";
$badmodules = array();
$query = "SELECT DISTINCT(external_module_name) AS modname FROM "._DB_PREFIX_."carrier WHERE active=1 AND is_module=1 AND id_carrier IN (".implode(",",$allowed_carriers).")";
$res=dbquery($query);
while($row = mysqli_fetch_array($res))
{ $mres = dbquery("SELECT name FROM "._DB_PREFIX_."module WHERE active=1 AND name='".$row["modname"]."'");
  if(mysqli_num_rows($mres) > 0)
  { if(is_dir($triplepath.'modules/'.$row["modname"]))
      continue;
  }
  
  $fres = dbquery("SELECT name AS cname, id_carrier FROM "._DB_PREFIX_."carrier WHERE active=1 AND external_module_name='".$row["modname"]."'");
  $badmodules[$row["modname"]] = array();
  while($frow = mysqli_fetch_array($fres))
  { $forbidden_carriers[] = $frow["id_carrier"];
	$badmodules[$row["modname"]][] = $frow["id_carrier"];
  }
}

if(sizeof($badmodules) > 0)
{ echo "<br>The following carriers belong to modules that are inactive or not present: ";
  foreach($badmodules AS $module => $carriers)
  { foreach($carriers AS $carrier)
      echo $carriernames[$carrier]."(".$module."), ";
  }
}
else
  echo "<br>No problems found";
	
$forbidden_carriers = array_unique($forbidden_carriers);
$allowed_carriers = array_diff($allowed_carriers,$forbidden_carriers);

echo "<p>According to the calculations the following ".sizeof($allowed_carriers)." carrier(s) can be used for this cart. If carriers are connected to a module the name of that module is shown between brakets. Note that bugs in modules can still cause a non-display:<br> ";
foreach($allowed_carriers AS $carrier) 
{ echo "<b>".$carrier."-".$carriernames[$carrier]."</b>";
  if($carrier == $shopname)
	echo " (pickup in shop)";
  else 
  { $cres = dbquery("SELECT external_module_name AS modname FROM "._DB_PREFIX_."carrier WHERE id_carrier='".$carrier."'");
    $crow = mysqli_fetch_array($cres);
	if($crow["modname"] != "")
	  echo " (".$crow["modname"].")";
  }
  echo ", ";
}

$default_carrier = get_configuration_value('PS_CARRIER_DEFAULT');
$cres = dbquery("SELECT name FROM "._DB_PREFIX_."carrier WHERE id_carrier='".$default_carrier."'");
list($defname) = mysqli_fetch_array($cres);
echo '<p>You <b>default carrier</b> is <b>'.$defname.'</b>';

/* the following list comes from classes/Carrier.php */
    const PS_CARRIERS_ONLY = 1;
    const CARRIERS_MODULE = 2;
    const CARRIERS_MODULE_NEED_RANGE = 3;
    const PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE = 4;
    const ALL_CARRIERS = 5;

    const SHIPPING_METHOD_DEFAULT = 0;
    const SHIPPING_METHOD_WEIGHT = 1;
    const SHIPPING_METHOD_PRICE = 2;
    const SHIPPING_METHOD_FREE = 3;

    const SHIPPING_PRICE_EXCEPTION = 0;
    const SHIPPING_WEIGHT_EXCEPTION = 1;
    const SHIPPING_SIZE_EXCEPTION = 2;

    const SORT_BY_PRICE = 0;
    const SORT_BY_POSITION = 1;
	
    const SORT_BY_ASC = 0;
    const SORT_BY_DESC = 1;
	
$carrier_sort = get_configuration_value('PS_CARRIER_DEFAULT_SORT');
if($carrier_sort == SORT_BY_PRICE) 
	echo "<P>Carriers are sort by price ";
else if($carrier_sort == SORT_BY_POSITION) 
	echo "<P>Carriers are sort by position ";
else
	echo "<P>Unknown carrier sort method ";
$carrier_order = get_configuration_value('PS_CARRIER_DEFAULT_ORDER');
if($carrier_order == SORT_BY_ASC) 
	echo "Ascending";
else if($carrier_order == SORT_BY_DESC) 
	echo "Descending";
else
	echo "Unknown sorting order";

if($carrier_sort == SORT_BY_POSITION) 
{ echo '<br>The positions of the selected carriers are: '; 
  $cres = dbquery("SELECT name,position FROM "._DB_PREFIX_."carrier WHERE id_carrier IN (".implode(',',$allowed_carriers).")");
  while($crow = mysqli_fetch_array($cres))
    echo $crow["name"]."-".$crow["position"].", ";
}

echo "<p><b>Tariffs per carrier:</b>";
foreach($allowed_carriers AS $id_carrier)
{ $query = "SELECT shipping_method, need_range FROM `"._DB_PREFIX_."carrier` WHERE id_carrier=".$id_carrier;
  $res = dbquery($query);
  $row = mysqli_fetch_array($res);
  if($row["shipping_method"] == SHIPPING_METHOD_FREE)
  { echo "<br>Free shipping method for carrier ".$id_carrier;  
  }
  else if($row["need_range"] == 0)
    echo "<br>".$id_carrier."-".$carriernames[$id_carrier]." has no ranges";
  else if($row["shipping_method"] == SHIPPING_METHOD_PRICE)
  { $qry = "SELECT SQL_NO_CACHE d.`price`, cr.name AS carriername, cr.id_carrier AS carrierid
	FROM `"._DB_PREFIX_."delivery` d
	LEFT JOIN `"._DB_PREFIX_."carrier` cr ON d.id_carrier=cr.id_carrier
	LEFT JOIN `"._DB_PREFIX_."range_price` r ON d.`id_range_price` = r.`id_range_price`
	WHERE d.`id_zone` = ".$id_zone."
	AND ".$totprice." > r.`delimiter1`
	AND ".$totprice." < r.`delimiter2`
	AND d.`id_carrier` = ".$id_carrier."
	AND d.id_delivery = (
	SELECT d2.id_delivery
	FROM "._DB_PREFIX_."delivery d2
	WHERE d2.id_carrier = `d`.id_carrier
	AND d2.id_zone = `d`.id_zone
	AND d2.`id_range_price` = `d`.`id_range_price`
	AND (d2.id_shop = 4 OR (d2.id_shop_group = 1
	AND d2.id_shop IS NULL) OR (d2.id_shop_group IS NULL AND d2.id_shop IS NULL))
	ORDER BY d2.id_shop DESC, d2.id_shop_group DESC
	LIMIT 1
	)
	ORDER BY r.`delimiter1` ASC LIMIT 1";
    $rs = dbquery($qry);
	$rw = mysqli_fetch_array($rs);
	if(mysqli_num_rows($rs) == 0)
		echo "<br>".$id_carrier."-".$carriernames[$id_carrier].": NOT FOUND in delivery table - has no ranges";
	else
	  echo "<br>".$id_carrier."-".$rw["carriername"].": ".$rw["price"]." (total price: ".$totprice.")";
  }
  else if($row["shipping_method"] == SHIPPING_METHOD_WEIGHT)
  { $qry = "SELECT SQL_NO_CACHE d.`price`, cr.name AS carriername, cr.id_carrier AS carrierid
	FROM `"._DB_PREFIX_."delivery` d
	LEFT JOIN `"._DB_PREFIX_."carrier` cr ON d.id_carrier=cr.id_carrier
	LEFT JOIN `"._DB_PREFIX_."range_weight` r ON d.`id_range_weight` = r.`id_range_weight`
	WHERE d.`id_zone` = ".$id_zone."
	AND ".$totweight." > r.`delimiter1`
	AND ".$totweight." < r.`delimiter2`
	AND d.`id_carrier` = ".$id_carrier."
	AND d.id_delivery = (
	SELECT d2.id_delivery
	FROM "._DB_PREFIX_."delivery d2
	WHERE d2.id_carrier = `d`.id_carrier
	AND d2.id_zone = `d`.id_zone
	AND d2.`id_range_weight` = `d`.`id_range_weight`
	AND (d2.id_shop = 4 OR (d2.id_shop_group = 1
	AND d2.id_shop IS NULL) OR (d2.id_shop_group IS NULL AND d2.id_shop IS NULL))
	ORDER BY d2.id_shop DESC, d2.id_shop_group DESC
	LIMIT 1
	)
	ORDER BY r.`delimiter1` ASC LIMIT 1";
    $rs = dbquery($qry);
	if(mysqli_num_rows($rs) == 0)
		echo "<br>".$id_carrier."-".$carriernames[$id_carrier].": NOT FOUND in delivery table - has no ranges";
	else
	{ $rw = mysqli_fetch_array($rs);
	  echo "<br>".$id_carrier."-".$rw["carriername"].": ".$rw["price"]." (total weight: ".$totweight.")";
	}
  }
  else
	echo "<br>Default shipping method for carrier ".$id_carrier;
}

$myzones = [];
echo "<p>Carrier price ranges: ";
foreach($allowed_carriers AS $id_carrier)
{ $query = "SELECT shipping_method, need_range FROM `"._DB_PREFIX_."carrier` WHERE id_carrier=".$id_carrier;
  $res = dbquery($query);
  $row = mysqli_fetch_array($res);
  if($row["shipping_method"] == SHIPPING_METHOD_FREE)
    echo "<br>Free shipping method for carrier ".$id_carrier;  
  else if($row["need_range"] == 0)
    echo "<br>".$id_carrier."-".$carriernames[$id_carrier]." has no ranges";
  else if($row["shipping_method"] == SHIPPING_METHOD_PRICE)
  { echo "<br>".$id_carrier."-".$carriernames[$id_carrier]." (price ranges): <table class=ranger>";
    $qry = "SELECT d.`price`, delimiter1, delimiter2, id_zone
	FROM `"._DB_PREFIX_."delivery` d
	LEFT JOIN `"._DB_PREFIX_."range_price` r ON d.`id_range_price` = r.`id_range_price`
	WHERE d.id_carrier=".$id_carrier."
	ORDER BY id_zone, delimiter1";
    $rs = dbquery($qry);
    $rw = mysqli_fetch_array($rs);
	echo "<tr><td>zone ".$rw["id_zone"]."</td><td>".number_format($rw['delimiter1'], 2)."-".number_format($rw['delimiter2'],2)."<br>".$rw['price']."</td>";
	$myzones[] = $zone = $rw["id_zone"];
	while($rw = mysqli_fetch_array($rs))
	{ if($zone != $rw["id_zone"])
	  { echo "</tr><tr><td>zone ".$rw["id_zone"]."</td><td>".number_format($rw['delimiter1'],2)."-".number_format($rw['delimiter2'],2)."<br>".$rw['price']."</td>";
		$myzones[] = $zone = $rw["id_zone"];
	  }
	  else
		echo "<td>".number_format($rw['delimiter1'],2)."-".number_format($rw['delimiter2'],2)."<br>".$rw['price']."</td>";
	}
	echo "</tr></table>";
  }
  else if($row["shipping_method"] == SHIPPING_METHOD_WEIGHT)
  { echo "<br>".$id_carrier."-".$carriernames[$id_carrier]." (weight ranges): <table class=ranger>";
    $qry = "SELECT d.`price`, delimiter1, delimiter2, id_zone
	FROM `"._DB_PREFIX_."delivery` d
	LEFT JOIN `"._DB_PREFIX_."range_weight` r ON d.`id_range_weight` = r.`id_range_weight`
	WHERE d.id_carrier=".$id_carrier."
	ORDER BY id_zone, delimiter1";
    $rs = dbquery($qry);
    $rw = mysqli_fetch_array($rs);
	echo "<tr><td>zone ".$rw["id_zone"]."</td><td>".number_format($rw['delimiter1'], 3)."-".number_format($rw['delimiter2'],3)."<br>".$rw['price']."</td>";
	$zone = $rw["id_zone"];
	while($rw = mysqli_fetch_array($rs))
	{ if($zone != $rw["id_zone"])
	  { echo "</tr><tr><td>zone ".$rw["id_zone"]."</td><td>".number_format($rw['delimiter1'],3)."-".number_format($rw['delimiter2'],3)."<br>".$rw['price']."</td>";
		$zone = $rw["id_zone"];
	  }
	  else
		echo "<td>".number_format($rw['delimiter1'],3)."-".number_format($rw['delimiter2'],3)."<br>".$rw['price']."</td>";
	}
	echo "</tr></table>";
  }
}

echo "<br>";
$myzones = array_unique($myzones);
foreach($myzones AS $zone)
{ echo "<br>Zone ".$zone.": ";
  $query = "SELECT iso_code FROM "._DB_PREFIX_."country WHERE id_zone=".$zone." ORDER BY iso_code";
  $res = dbquery($query);
  while($row = mysqli_fetch_array($res))
	  echo $row['iso_code'].", ";
}