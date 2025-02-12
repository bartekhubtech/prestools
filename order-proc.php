<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if (isset($_GET['id_order']))
	$id_order = (int)$_GET['id_order'];
else if (isset($_POST['id_order']))
{	$id_order = (int)$_POST['id_order'];
	$_GET = $_POST;
}
else
    colordie("No order ID provided!");
if(!isset($_GET['action']))
    colordie("No action provided!");
$query="SELECT id_order_invoice FROM "._DB_PREFIX_."order_invoice";
$query.=" where id_order=".(int)$id_order;
$res = dbquery($query);
if(mysqli_num_rows($res) >1)
  colordie("This order has more than one invoice. This program cannot handle that.");
else
  list($id_order_invoice) = mysqli_fetch_row($res);
	
$stock_management_conf = get_configuration_value('PS_STOCK_MANAGEMENT');
$tax_address_type = get_configuration_value('PS_TAX_ADDRESS_TYPE');
$advanced_stock_management_conf = get_configuration_value('PS_ADVANCED_STOCK_MANAGEMENT');
/* ASM: changes in stock are processed at delivery and so not relevant AS oe assumes that the order is not yet delivered */
if($advanced_stock_management_conf) $stock_mvt_reason = get_configuration_value('PS_STOCK_MVT_REASON_DEFAULT');
$round_type = get_configuration_value('PS_ROUND_TYPE');  /* item, line or total */
$price_round_mode = get_configuration_value('PS_PRICE_ROUND_MODE'); /* up or down */
if(!$price_round_mode) $price_round_mode = PS_ROUND_HALF_UP;
$precision = get_configuration_value('PS_PRICE_DISPLAY_PRECISION');
if($precision === false) $precision = 2;
/* PS_ATCP_SHIPWRAP is a flag from the TB module "Advanced EU compliance". When this flag is 1 wrapping and shipping should have the same VAT as the products average */
$ps_atcp_shipwrap = get_configuration_value('PS_ATCP_SHIPWRAP');

$eu_countries = array("AT","BE","BG","CY","CZ","DE","DK","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PL","PT","RO","SE","SI","SK");

$query=" select cu.name, cu.id_currency,cu.conversion_rate from "._DB_PREFIX_."configuration cf, "._DB_PREFIX_."currency cu";
$query.=" WHERE cf.name='PS_CURRENCY_DEFAULT' AND cf.value=cu.id_currency";
$res=dbquery($query);
$row=mysqli_fetch_array($res);
$cur_name = $row['name'];
$cur_rate = $row['conversion_rate'];
$id_currency = $row['id_currency'];

$query="select o.id_shop, o.id_lang, oi.id_order_invoice, a.vat_number, a2.vat_number AS vat_invoice,";
if($tax_address_type == 'id_address_invoice')
	$query .= " a2.id_country, a2.id_state,";
else
	$query .= " a.id_country, a.id_state,";
$query .= " cu.id_currency, cu.name AS currname, cu.conversion_rate AS currrate,o.id_customer";
$query .= " from "._DB_PREFIX_."orders o";
$query .= " left join "._DB_PREFIX_."order_invoice oi on o.id_order=oi.id_order";
$query .= " left join "._DB_PREFIX_."address a on o.id_address_delivery=a.id_address";
$query .= " left join "._DB_PREFIX_."address a2 on o.id_address_invoice=a2.id_address";
$query .= " left join "._DB_PREFIX_."currency cu on cu.id_currency=o.id_currency";
$query.=" WHERE o.id_order ='".$id_order."'";
$res=dbquery($query);
$row=mysqli_fetch_array($res);
$id_country = intval($row['id_country']);
$id_state = intval($row['id_state']);
$id_shop = intval($row['id_shop']);
$id_order_invoice = $row['id_order_invoice'];
$id_customer = $row['id_customer'];
if($row["vat_invoice"] != "")
  $vat_number = $row["vat_invoice"];
else
  $vat_number = $row["vat_number"];	
$order_currency = $row['id_currency'];
$order_currname = $row['currname'];
$id_lang = (int)$row['id_lang'];
$conversion_rate = $row['currrate'] / $cur_rate;

/* get shop group and its shared_stock status */
$query="select s.id_shop_group, g.share_stock, g.name from "._DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
$query .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_shop_group = $row['id_shop_group'];
$share_stock = $row["share_stock"];

 /* handle intra-EU sales with VAT number => no tax */
$zero_vat = false;
if($vat_number != "")
{ $vquery="select id_country from "._DB_PREFIX_."country WHERE iso_code IN ('".implode("','",$eu_countries)."')";
  $vres=dbquery($vquery);
  $eu_country_ids = array();
  while($vrow=mysqli_fetch_array($vres))
    $eu_country_ids[] = $vrow["id_country"];
  $shop_country = get_configuration_value('PS_COUNTRY_DEFAULT');
  if((in_array($id_country,$eu_country_ids)) && (in_array($shop_country,$eu_country_ids)) && ($id_country != $shop_country))
  { $zero_vat = true;
  }
}

  $wrap_tax_rules_group = get_configuration_value('PS_GIFT_WRAPPING_TAX_RULES_GROUP');
  $wrap_tax_rate = gettaxgrouprate($wrap_tax_rules_group);
  
/* change-order can change the carrier. So there we get the data from the tax tables */
if ($_GET['action']!='change-order') 
{ $query="select carrier_tax_rate FROM "._DB_PREFIX_."orders WHERE id_order=".(int)$id_order;
  $res = dbquery($query);
  $trow=mysqli_fetch_array($res);
  $carrier_tax_rate = (float)$trow["carrier_tax_rate"];
  if($zero_vat)
	$carrier_tax_rate = 0;
}

if($prestools_settings["demo_mode"])
   echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
else if ($_GET['action']=='add-product') 
{ $id_product_attribute = intval($_GET['attribute']);
  $id_product = intval($_GET['id_product']);
  
  $query = "SELECT id_category_default FROM "._DB_PREFIX_."product_shop";
  $query .= " WHERE id_product=".$id_product." AND id_shop=".$id_shop;
  $res=dbquery($query);
  $row = mysqli_fetch_assoc($res);
  $id_category_default = $row["id_category_default"];
  
  $query = "SELECT * FROM "._DB_PREFIX_."order_detail";
  $query .= " WHERE id_order=".$id_order." AND product_id=".$id_product." AND product_attribute_id=".$id_product_attribute;
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 0) /* added product already in order: increase with 1 */
  { $row = mysqli_fetch_assoc($res);
    $unit_price_excl = (float)$row["unit_price_tax_excl"];
	$id_order_detail = $row["id_order_detail"];
	$product_quantity = intval($row["product_quantity"]);
	$product_quantity_in_stock = $row["product_quantity_in_stock"]; /* stock before this order */
	$product_quantity++;
  
    $tquery  = "SELECT rate from "._DB_PREFIX_."order_detail_tax ot";
    $tquery .= " LEFT JOIN "._DB_PREFIX_."tax t ON t.id_tax=ot.id_tax";
    $tquery .= " WHERE id_order_detail = '".$id_order_detail."'";
    $tres = dbquery($tquery);
    $trow=mysqli_fetch_array($tres);
  
    $unit_price_incl = round(($unit_price_excl*(floatval($trow['rate'])+100)/100),6);
  
    $query = "update "._DB_PREFIX_."order_detail set";
    $query .= " product_quantity=".$product_quantity;
//    $query .= ", product_quantity_in_stock=".$product_quantity_in_stock;
    $query .= ", total_price_tax_incl = '".($product_quantity*$unit_price_incl)."'";
    $query .= ", total_price_tax_excl = '".($product_quantity*$unit_price_excl)."'";
    $query .= ", unit_price_tax_incl = '".$unit_price_incl."'";
    $query .= ", unit_price_tax_excl = '".$unit_price_excl."'";
    $query .= "  where id_order_detail='".$id_order_detail."'";
    dbquery($query);
  
    $net_tax = $unit_price_excl*(floatval($trow['rate']))/100;
	if($round_type == ROUND_ITEM)
	  $net_tax = ps_round($net_tax,$precision,$round_type);
	if ($round_type == ROUND_LINE)
	  $total_tax = ps_round($net_tax*$product_quantity,$precision,$round_type);
	else 
	  $total_tax = $net_tax*$product_quantity;
  
    $tquery ="UPDATE "._DB_PREFIX_."order_detail_tax ";
    $tquery.="SET unit_amount = '".$net_tax."'";
    $tquery.=" ,total_amount = '".$total_tax."'"; 
    $tquery .= "  where id_order_detail='".$id_order_detail."'"; 
    dbquery($tquery);
	  
    update_stock($id_order, $id_product,$id_product_attribute,-1);
	update_total($id_order);
  }
  else /* product not yet in order */
  {   
    /* calculate group reduction */
    $query = "SELECT gr.reduction AS catreduction, g.reduction FROM "._DB_PREFIX_."group g";
    $query .= " LEFT JOIN "._DB_PREFIX_."group_reduction gr ON g.id_group=gr.id_group AND gr.id_category=".$id_category_default;
    $query .= " LEFT JOIN "._DB_PREFIX_."customer c ON c.id_default_group=g.id_group";
    $query .= " WHERE c.id_customer=".$id_customer;
    $res=dbquery($query);
    $row = mysqli_fetch_assoc($res);
    if($row["catreduction"] != 0)	/* category specific groupreductions prevail */
      $groupreduction = $row["catreduction"]*100; /* this is as part: like 0.15 */
    else if($row["reduction"] != 0)
      $groupreduction = $row["reduction"];	/* this is as percent: like 15 */
    else
      $groupreduction = 0;
	
    $fields = " p.weight,p.ean13,p.upc,p.reference,p.supplier_reference,s.quantity, ps.*,pl.name,pl.id_lang,tr.behavior";
    $fields .= ",l.iso_code,ps.advanced_stock_management,s.depends_on_stock,t.rate as tax_rate,t.id_tax";
    $fields .= ", tl.name as tax_name,p.id_tax_rules_group,p.is_virtual";
    $query="select ".$fields." from "._DB_PREFIX_."product_shop ps";
    $query.=" left join "._DB_PREFIX_."product p on p.id_product=ps.id_product";
    $query.=" left join "._DB_PREFIX_."product_lang pl on p.id_product=pl.id_product AND pl.id_lang='".$id_lang."'";
    $query.=" left join "._DB_PREFIX_."lang l on pl.id_lang=l.id_lang";
    $query.=" left join "._DB_PREFIX_."stock_available s on s.id_product=ps.id_product";
    if($share_stock)
	  $query.=" AND s.id_shop_group=".$id_shop_group; 
    else
	  $query.=" AND s.id_shop=".$id_shop; 
    $query.=" left join "._DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".$id_country."'  AND tr.id_state='0'";
    $query.=" left join "._DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
    $query.=" left join "._DB_PREFIX_."tax_lang tl on t.id_tax=tl.id_tax AND tl.id_lang='".$id_lang."'";
    $query.=" WHERE ps.id_shop='".$id_shop."' AND p.id_product='".$id_product."' ";
    $res=dbquery($query);
    $products=mysqli_fetch_array($res);
	/* the following line is for products that have both country and state taxes */
	$products["tax_rate"] = gettaxgrouprate($products["id_tax_rules_group"]);
 
   /* handle intra-EU sales with VAT number => no tax */
    if($zero_vat)
    { $products['tax_rate'] = 0;
	  $products['id_tax'] = 0;
	  $products['tax_computation_method'] = 0;
    }

    $name = $products['name'];
    $price = $products['price'];
    $weight = $products['weight'];
    $quantity_in_stock = $products['quantity'];
    $reference = $products['reference'];
    $supplier_reference = $products['supplier_reference'];
    $ean13 = $products['ean13'];
    $upc = $products['upc'];
	$tax_computation_method = $products['behavior'];

    if (is_null($products['tax_rate'])) $products['tax_rate']=0;
 
    if($id_product_attribute!=0)
    { $price = $price+(float)$_GET['attprice'];
      $weight = $weight+(float)$_GET['attweight'];
      $gquery = "SELECT public_name,l.name,s.quantity,pa.reference,pa.ean13,pa.upc,pa.supplier_reference FROM "._DB_PREFIX_."product_attribute_combination c LEFT JOIN "._DB_PREFIX_."attribute a on c.id_attribute=a.id_attribute";
      $gquery .= " LEFT JOIN "._DB_PREFIX_."attribute_group_lang g on a.id_attribute_group=g.id_attribute_group AND g.id_lang='".$id_lang."'";
      $gquery .= " LEFT JOIN "._DB_PREFIX_."attribute_lang l on a.id_attribute=l.id_attribute AND l.id_lang='".$id_lang."'";
      $gquery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa on pa.id_product_attribute=c.id_product_attribute";
      $gquery .= " LEFT JOIN "._DB_PREFIX_."stock_available s on s.id_product_attribute=c.id_product_attribute";
      $gquery .= " WHERE c.id_product_attribute='".$id_product_attribute."'";
      $gres = dbquery($gquery);
      $grow=mysqli_fetch_array($gres);
      $quantity_in_stock = $grow['quantity'];
	  if($grow["reference"] != "") $reference = $grow["reference"];
      if($grow["ean13"] != "") $ean13 = $grow['ean13'];
      if($grow["upc"] != "") $upc = $grow['upc'];
      if($grow["supplier_reference"] != "") $supplier_reference = $grow['supplier_reference'];
      $name .= " - ".$grow['public_name']." : ".$grow['name'];
      while ($grow=mysqli_fetch_array($gres))  /* products with multiple attributes */
        $name .= ", ".$grow['public_name']." : ".$grow['name'];
    }
  
    /* Fill the id_warehouse field of an added product in the order_detail table. This is provisional.  */
    /* We may allocate the product to a different warehouse as the rest of the order. But we will not split the order here */
    $wquery ="SELECT wpl.id_warehouse, usable_quantity, id_shop FROM "._DB_PREFIX_."warehouse_product_location wpl";
    $wquery .= " LEFT JOIN "._DB_PREFIX_."warehouse_carrier wc on wpl.id_warehouse=wc.id_warehouse";
    $wquery .= " LEFT JOIN "._DB_PREFIX_."order_carrier oc on oc.id_carrier=wc.id_carrier AND oc.id_order=".$id_order;
    $wquery .= " LEFT JOIN "._DB_PREFIX_."stock s on s.id_warehouse=wpl.id_warehouse AND wpl.id_product=s.id_product AND wpl.id_product_attribute=s.id_product_attribute";
    $wquery .= " LEFT JOIN "._DB_PREFIX_."warehouse_shop ws on wpl.id_warehouse=ws.id_warehouse";
    $wquery .= " WHERE wpl.id_product='".$products['id_product']."' AND wpl.id_product_attribute='".$id_product_attribute."'";
    $wquery .= " ORDER BY usable_quantity DESC";
    $wres = dbquery($wquery);
    if(mysqli_num_rows($wres) == 0)
	  $id_warehouse = "0";
    else 
    { $wrow=mysqli_fetch_array($wres);
      $id_warehouse = $wrow["id_warehouse"];
    }
    if(mysqli_num_rows($wres) > 1) /* two more optional determinants: id_shop and the warehouse of the rest of the order */
    { /* note that we have already allocated a value so it is no problem if this section gives no results */
      $cquery ="SELECT id_warehouse FROM "._DB_PREFIX_."order_detail WHERE id_order=".$id_order;
      $cres = dbquery($cquery);
	  if(mysqli_num_rows($cres) == 0)
	  { $crow=mysqli_fetch_array($cres);
	    while($wrow=mysqli_fetch_array($wres))
	    { if(($crow["id_warehouse"] == $wrow["id_warehouse"]) && ($wrow["id_shop"] == $id_shop))
		  { $id_warehouse = $wrow["id_warehouse"];
		    break;
		  } 
	    }
	  }
    }
  
	/* correction for supplier_reference */
    $query = "SELECT product_supplier_reference FROM "._DB_PREFIX_."product_supplier";
    $query .= " WHERE id_product=".$id_product." AND id_product_attribute=".$id_product_attribute;
    $res = dbquery($query);
    if(mysqli_num_rows($res)!=0)
    { $row=mysqli_fetch_array($res);
	  $supplier_reference = $row["product_supplier_reference"];
    }
	
	if($quantity_in_stock > 1)  /* $quantity_in_stock can be negative */
	  $quantity_in_stock = 1;
	
	/* prices */
	$product_price = $price*$conversion_rate*(1-($groupreduction/100));
    $unit_price_excl = $product_price;
    $unit_price_incl = round(($unit_price_excl*(floatval($products['tax_rate'])+100)/100),6);

    if($ean13 == '0') $ean13='';
    $query ="insert into "._DB_PREFIX_."order_detail ";
    $query.=" SET id_order = '".$id_order."'";
    $query.=" ,id_order_invoice = '".$id_order_invoice."'";
    $query.=" ,id_shop = '".$id_shop."'";
    $query.=" ,product_id = '".$products['id_product']."'";
    $query.=" ,product_attribute_id = '".$id_product_attribute."'";
    $query.=" ,product_name = '".mysqli_real_escape_string($conn, $name)."'";
    $query.=" ,product_quantity = '1'";
    $query.=" ,id_warehouse = '".$id_warehouse."'";
    $query.=" ,product_quantity_in_stock = '".$quantity_in_stock."'";
    $query.=" ,product_price = '".$product_price."'";
    $query.=" ,product_ean13 = '".$ean13."'";
    $query.=" ,product_upc = '".$upc."'";
    $query.=" ,product_reference = '".$reference."'";
    $query.=" ,product_supplier_reference = '".$supplier_reference."'";
    $query.=" ,product_weight = '".$weight."'";
	$query.=" ,tax_computation_method = '".$tax_computation_method."'";
    $query.=" ,group_reduction = '".$groupreduction."'";	
	
    if (version_compare(_PS_VERSION_ , "1.6.0.10", ">="))
      $query.=" ,id_tax_rules_group = '".$products['id_tax_rules_group']."'";	
    else
    { $query.=" ,tax_name = '".$products['tax_name']."'";
      $query.=" ,tax_rate = '".$products['tax_rate']."'";
    }
    if($products['is_virtual'] == 0)
      $query.=" ,download_hash = ''"; /* it is not clear how this is calculated; it is not the filename */
    else
    { do
	  { $hash = sha1(microtime().$id_product);
	    $hres = dbquery('SELECT * FROM `'._DB_PREFIX_.'order_detail` WHERE `download_hash` = \''.mescape(strval($hash)).'\'');
	  } while(mysqli_num_rows($hres)> 0);
	  $query .= " ,download_hash = '".mescape($hash)."'"; 
    }
    $query.=" ,download_deadline = '0000-00-00 00:00:00'";

    $query.=" ,total_price_tax_incl = '".$unit_price_incl."'";
    $query.=" ,total_price_tax_excl = '".$unit_price_excl."'";
    $query.=" ,unit_price_tax_incl = '".$unit_price_incl."'";
    $query.=" ,unit_price_tax_excl = '".$unit_price_excl."'";
    $query.=" ,original_product_price = '".$product_price."'"; 
    dbquery($query);
	
    $net_tax = $unit_price_excl*(floatval($products['tax_rate']))/100;
	if(($round_type == ROUND_ITEM) || ($round_type == ROUND_LINE))
	{ $net_tax = round($net_tax,$precision); 
	}
	else
	{ $net_tax = round($net_tax,6);	/* note that Prestashop always rounds to $precision */
	}
	
    $tquery ="insert into "._DB_PREFIX_."order_detail_tax ";
    $tquery.=" SET id_order_detail = LAST_INSERT_ID()";
    $tquery.=" ,id_tax = '".$products['id_tax']."'";
    $tquery.=" ,unit_amount = '".$net_tax."'";
    $tquery.=" ,total_amount = '".$net_tax."'"; /* always one product here */
    dbquery($tquery);
  
    update_stock($id_order, $id_product,$id_product_attribute,-1);
    update_total($id_order);
  } /* end else (=product not yet in order) */
} /* end if($_GET['action']=='add-product') */

/* all orderline fields are arrays with as index the line's id_order_detail */
else if ($_GET['action']=='change-products')
{ // First check that refunds and returns have enough products
   $qquery  = "SELECT product_quantity_refunded,product_quantity_return,id_order_detail,product_id, product_attribute_id";
  $qquery .= " FROM "._DB_PREFIX_."order_detail";
  $qquery .= " WHERE id_order = '".$id_order."' AND (product_quantity_refunded>0 OR product_quantity_return>0)";
  $qres = dbquery($qquery);
  while($qrow = mysqli_fetch_array($qres))
  { if(isset($_GET['product_delete'][$qrow["id_order_detail"]]))
	  colordie("You are not allowed to delete order lines that have refunds or returns!");
    $total_refunded = $qrow["product_quantity_refunded"] + $qrow["product_quantity_return"];
	if($_GET['product_quantity'][$qrow["id_order_detail"]] < $total_refunded)
	  colordie("In the order lines the quantities should cover the refunds and returns!<br>Problem with ".$qrow["product_id"]."[".$qrow["product_attribute_id"]."].");	
  }

  //delete product
  if (isset($_GET['product_delete']))
  { 
	foreach ($_GET['product_delete'] as $id_order_detail=>$value)
	{ $res = dbquery("SELECT product_id,product_attribute_id,product_quantity from "._DB_PREFIX_."order_detail where id_order_detail=".$id_order_detail);
	  if(mysqli_num_rows($res) == 0) continue;
	  $drow=mysqli_fetch_array($res);
  
      update_stock($id_order, $drow['product_id'],$drow['product_attribute_id'],$drow['product_quantity']);

      dbquery("delete from "._DB_PREFIX_."order_detail where id_order_detail=".$id_order_detail);
	  dbquery("delete from "._DB_PREFIX_."order_detail_tax where id_order_detail=".$id_order_detail);
	  unset($_GET['unit_price'][$id_order_detail]); /* take care that this row doesn't count in the next loop */
	}
  }

  $total_products_excl_VAT = 0;
  if ($_GET['unit_price']) 
  { foreach ($_GET['unit_price'] as $id_order_detail=>$unit_price)
    { $unit_price = (float)$unit_price;
	  $qty_difference=$_GET['product_quantity_old'][$id_order_detail]-$_GET['product_quantity'][$id_order_detail];
      $name=$_GET['product_name'][$id_order_detail];
      $attribute = (int)$_GET['product_attribute'][$id_order_detail];
	  $id_product = (int)$_GET['product_id'][$id_order_detail];
	  $base_price = (float)$_GET['product_price'][$id_order_detail];
	  $reduction = (float)$_GET['reduction'][$id_order_detail];
	  $reductiontype = $_GET['reduction_type'.$id_order_detail];
	  $taxtype = $_GET['tax_type'.$id_order_detail];
	  $VAT = (float)$_GET['VAT'][$id_order_detail];
	  
      $tquery  = "SELECT rate from "._DB_PREFIX_."order_detail_tax ot";
      $tquery .= " LEFT JOIN "._DB_PREFIX_."tax t ON t.id_tax=ot.id_tax";
      $tquery .= " WHERE id_order_detail = '".$id_order_detail."'";
      $tres = dbquery($tquery);
      $trow=mysqli_fetch_array($tres);
	  if(abs($trow['rate'] - $VAT) > 0.4) /* handle old tax rates */
		 $trow['rate'] = $VAT;
	  
	  if($reductiontype == 'pct')
      { $reduction_amount = 0;
	    $reduction_amount_tax_incl = 0;
		$reduction_amount_tax_excl = 0;
	    $reduction_percent = $reduction;
		$calcprice = $base_price*(100-$reduction)/100;
	  }
	  else
      { $reduction_amount = $reduction;
	    if($taxtype == 'incl')
		{ $reduction_amount_tax_incl = $reduction;
		  $reduction_amount_tax_excl = $reduction*100/(floatval($trow['rate'])+100);
		}
		else
		{ $reduction_amount_tax_excl = $reduction;
		  $reduction_amount_tax_incl = $reduction*(floatval($trow['rate'])+100)/100;
		}
	    $reduction_percent = 0;
		
		$gquery  = "SELECT group_reduction from "._DB_PREFIX_."order_detail";
        $gquery .= " WHERE id_order_detail = '".$id_order_detail."'";
        $gres = dbquery($gquery);
        $grow=mysqli_fetch_array($gres);
		$realreduction = $reduction_amount_tax_excl*(100-floatval($grow['group_reduction']))/100;
		
		$calcprice = $base_price-$realreduction;
			  echo $calcprice." ### ".$base_price." *** ".$realreduction." == ".$reduction_amount_tax_excl."<br>";
	  }

	  /* validity check */
/*	  if(($unit_price!=$calcprice) 
		&& (abs(($unit_price-$calcprice)/$unit_price) > 0.001)) 
		colordie("Calculation error for product ".$name.": ".$unit_price." != ".$calcprice);
		  echo (($unit_price-$calcprice)/$unit_price)."<br>";
*/
      $unit_price_excl = $unit_price;
	  $unit_price_incl = $unit_price*(floatval($trow['rate'])+100)/100;

      $product_quantity = (int)$_GET['product_quantity'][$id_order_detail];
	  
	  if($round_type == ROUND_ITEM)
	    $unit_price_incl = ps_round($unit_price_incl,$precision,$round_type);
	  if ($round_type == ROUND_LINE)
	    $total_price_incl = ps_round($unit_price_incl*$product_quantity,$precision,$round_type);
	  else 
	    $total_price_incl = $unit_price_incl*$product_quantity;

	/* the value for product_quantity_discount seems nonsensical and not used. So we don't set it */
      $query = "UPDATE "._DB_PREFIX_."order_detail SET";
      $query .= " product_quantity='".$product_quantity."'";
//      $query .= ", product_quantity_in_stock='".intval($quantity_in_stock)."'"; /* stock before order */
      $query .= ", product_name='".mysqli_real_escape_string($conn, $name)."'";
      $query .= ", total_price_tax_incl = '".$total_price_incl."'";
	  if($trow['rate'] == 0)
	    $query .= ", total_price_tax_excl = '".$total_price_incl."'"; /* avoid rounding trouble */
	  else
        $query .= ", total_price_tax_excl = '".($product_quantity*$unit_price_excl)."'";
      $query .= ", unit_price_tax_incl = '".$unit_price_incl."'";
      $query .= ", unit_price_tax_excl = '".$unit_price_excl."'";
      $query .= ", product_price = '".$base_price."'";
      $query .= ", reduction_percent = '".$reduction_percent."'";
      $query .= ", reduction_amount = '".$reduction_amount."'";
      $query .= ", reduction_amount_tax_incl = '".$reduction_amount_tax_incl."'";
      $query .= ", reduction_amount_tax_excl = '".$reduction_amount_tax_excl."'";
      $query .= "  where id_order_detail='".$id_order_detail."'";
      dbquery($query);
	  
	  $net_tax = $unit_price*(floatval($trow['rate']))/100; 
	  if($round_type == ROUND_ITEM)
	    $net_tax = ps_round($net_tax,$precision,$round_type);
	  if ($round_type == ROUND_LINE)
	    $total_tax = ps_round($net_tax*$product_quantity,$precision,$round_type);
	  else 
	    $total_tax = $net_tax*$product_quantity;
	  
      $tquery ="UPDATE "._DB_PREFIX_."order_detail_tax ";
      $tquery.="SET unit_amount = '".$net_tax."'";
      $tquery.=" ,total_amount = '".$total_tax."'"; /* always one product here */
      $tquery .= "  where id_order_detail='".$id_order_detail."'"; 
      dbquery($tquery);
	  
      update_stock($id_order, $id_product,$attribute,$qty_difference);
	  
      $total_products_excl_VAT+=$_GET['product_quantity'][$id_order_detail]*price($unit_price);
    }
    update_total($id_order);
  }
} // end if ($_GET['action']=='change-products')

else if ($_GET['action']=='change-order')
{ 
  $query="update "._DB_PREFIX_."order_carrier set ";
  $query.=" id_carrier=".(int)$_GET['id_carrier'];
  $query.=" where id_order=".$id_order;
  dbquery($query);
  $query="update "._DB_PREFIX_."orders set ";
  $query.=" id_carrier=".(int)$_GET['id_carrier'];
  $query.=" where id_order=".$id_order;
  dbquery($query);
  
  $query="SELECT id_cart FROM "._DB_PREFIX_."orders WHERE id_order=".$id_order;
  $res = dbquery($query);
  $row = mysqli_fetch_assoc($res);
  $query="update "._DB_PREFIX_."cart set ";
  $query.=" id_carrier=".(int)$_GET['id_carrier'];
  $query.=" where id_cart=".$row['id_cart'];
  dbquery($query); 

  $query="select SUM(t.rate) AS carriertax FROM "._DB_PREFIX_."carrier_tax_rules_group_shop ct ";
  $query.=" left join "._DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=ct.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state IN ('0','".$id_state."')";
  $query.=" left join "._DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
  $query .= " where ct.id_carrier=".(int)$_GET['id_carrier']." AND ct.id_shop=".$id_shop;
  $res = dbquery($query);
  $trow=mysqli_fetch_array($res);
  $carrier_tax_rate = (float)$trow["carriertax"];
  if($zero_vat)
	  $carrier_tax_rate = 0;
  
  /* wrapping and shipping incl are tentative. They can be corrected later when $ps_atcp_shipwrap is 1 */
  $total=price($_GET['total_products_wt']) + price($_GET['total_shipping']) + price($_GET['total_wrapping']) - price($_GET['total_discounts']);
  $total_shipping_tax_excl = ($_GET['total_shipping']/($carrier_tax_rate+100))*100;
  $total_wrapping_tax_excl = ($_GET['total_wrapping']/($wrap_tax_rate+100))*100;
  /* the discount tax rate is calculated from total products tax excl/tax incl */
  if($_GET['total_products_excl_VAT'] > 0)
    $discount_tax_rate = 100*($_GET['total_products_wt'] - $_GET['total_products_excl_VAT']) / $_GET['total_products_excl_VAT'];
  else if($_GET['total_products_wt'] > 0)
	  colordie("Impossible situation: with tax > 0; without tax=0!!!");
  else 
	$discount_tax_rate = $carrier_tax_rate;   

  $total_discount_tax_excl = $_GET['total_discounts']*100/(100+$discount_tax_rate);  
  $total_paid_tax_excl = price($_GET['total_products_excl_VAT']) + $total_shipping_tax_excl + $total_wrapping_tax_excl - price($total_discount_tax_excl);
  
  if(isset($_GET['diff_excl']))
  { $total_paid_tax_excl += $_GET['diff_excl'];
	$total += $_GET['diff_excl']*(100+$_GET['diff_tax'])/100;
  }

  $query="update "._DB_PREFIX_."orders set ";
  $query.=" total_discounts=".(float)price($_GET['total_discounts']);
  $query.=" ,total_discounts_tax_incl=".(float)price($_GET['total_discounts']);
  $query.=" ,total_wrapping=".(float)price($_GET['total_wrapping']);
  $query.=" ,total_wrapping_tax_excl=".(float)price($total_wrapping_tax_excl);
  $query.=" ,total_wrapping_tax_incl=".(float)price($_GET['total_wrapping']);
  $query.=" ,total_shipping=".(float)price($_GET['total_shipping']);
  $query.=" ,total_shipping_tax_excl=".(float)price($total_shipping_tax_excl);
  $query.=" ,total_shipping_tax_incl=".(float)price($_GET['total_shipping']);
//  $query.=" ,delivery_number=".(int)price($_GET['delivery_number']);
  $query.=" ,id_carrier=".(int)$_GET['id_carrier'];
  $query.=" ,carrier_tax_rate=".$carrier_tax_rate;
  $query.=" ,total_paid_tax_incl=".$total;
  $query.=" ,total_paid_tax_excl=".(float)price($total_paid_tax_excl);
  $query.=" ,total_paid=".$total;
  $query.=" ,total_paid_real=".$total;
  $query.=", date_upd=NOW()";
  $query.=" where id_order=".mysqli_real_escape_string($conn, $id_order);
  $query.=" limit 1";
  dbquery($query);

  if($_GET['id_carrier']!=0) /* when we don't have downloads ... */
  { $query="update "._DB_PREFIX_."order_carrier set ";
    $query.=" shipping_cost_tax_excl=".price($total_shipping_tax_excl);
    $query.=" ,shipping_cost_tax_incl=".price($_GET['total_shipping']);
    $query.=" where id_order=".mysqli_real_escape_string($conn, $id_order);
    $query.=" limit 1";
    dbquery($query);
  }

  $query="update "._DB_PREFIX_."order_invoice set ";
  $query.=" total_discount_tax_incl=".price($_GET['total_discounts']);
  $query.=" ,total_wrapping_tax_incl=".price($_GET['total_wrapping']);
  $query.=" ,total_wrapping_tax_excl=".price($total_wrapping_tax_excl);
  $query.=" ,total_shipping_tax_excl='".price($total_shipping_tax_excl)."'";
  $query.=" ,total_shipping_tax_incl='".price($_GET['total_shipping'])."'";
  $query.=" ,total_paid_tax_excl='".price($total_paid_tax_excl)."'";
  $query.=" ,total_paid_tax_incl='".$total."'";
  $query.=" where id_order=".mysqli_real_escape_string($conn, $id_order);
  $query.=" limit 1";
  dbquery($query);
  update_total($id_order);
}  // end if ($_GET['action']=='change-order')


else if ($_GET['action']=='update-addons') /* addons are normally invisible and only enabled on demand */
{ if (isset($_GET['order_date']) && strtotime($_GET['order_date']) && 1 === preg_match('~[0-9]~', $_GET['order_date']))
  { $query = "UPDATE "._DB_PREFIX_."orders SET date_add='".mysqli_real_escape_string($conn, $_GET['order_date'])."',date_upd='".mysqli_real_escape_string($conn, $_GET['order_date'])."' ";
	$query.=" WHERE id_order ='".mysqli_real_escape_string($conn, $id_order)."'";
	$res=dbquery($query);
	echo "<br>Order date was updated.<br>";
  }
  else
	 echo "<p/><b>Invalid order date: ".$_GET['order_date']."</b><p/>";

  if (isset($_GET['id_customer']))
  { $query = "SELECT * FROM "._DB_PREFIX_."customer WHERE id_customer ='".mysqli_real_escape_string($conn, $_GET['id_customer'])."'";
    $res=dbquery($query);
    if(mysqli_num_rows($res) == 0)
    { echo "<p><u><b>You provided an invalid customer number. The order was not updated.</u></b><p>";
    }
    else
    { $query = "select id_address from "._DB_PREFIX_."address WHERE id_customer='".mysqli_real_escape_string($conn, $_GET['id_customer'])."'";
      $res=dbquery($query);
	  $row=mysqli_fetch_array($res);
	  $query = "UPDATE "._DB_PREFIX_."orders SET id_customer='".mysqli_real_escape_string($conn, $_GET['id_customer'])."', ";
	  $query .= " id_address_delivery='".$row['id_address']."', id_address_invoice='".$row['id_address']."'";
	  $query.=" WHERE id_order ='".mysqli_real_escape_string($conn, $id_order)."'";
	  $res=dbquery($query);	   
	  echo "<br>Customer id was updated.<br>";
    }
  }

  if (isset($_GET['reference']))
  { $query = "UPDATE "._DB_PREFIX_."orders SET reference='".mysqli_real_escape_string($conn, $_GET['reference'])."' ";
    $query.=" WHERE id_order ='".mysqli_real_escape_string($conn, $id_order)."'";
    $res=dbquery($query);
	echo "<br>Reference was updated.<br>";
  }
}

echo "<br>Finished successfully!<p>Go back to <a href='order-edit.php?id_order=".$id_order."'>Order-edit page page</a>";
if($verbose!="true")
{ echo "<script>location.href = 'order-edit.php?id_order=".$id_order."';</script>";
}

function update_stock($id_order, $id_product, $id_product_attribute, $quantchange)
{ global $conn, $stock_management_conf, $advanced_stock_management_conf, $id_shop, $id_shop_group,$share_stock;
  global $stock_mvt_reason;
  $query = "SELECT depends_on_stock,advanced_stock_management FROM "._DB_PREFIX_."product_shop ps";
  if($share_stock)
	$query.=" left join "._DB_PREFIX_."stock_available s on s.id_product=ps.id_product AND s.id_shop_group=".$id_shop_group; 
  else
	$query.=" left join "._DB_PREFIX_."stock_available s on s.id_product=ps.id_product AND s.id_shop=".$id_shop; 		
  $query .= " where ps.id_product=".$id_product;
  $res=dbquery($query);
  $row=mysqli_fetch_array($res);
  if($row["depends_on_stock"])
	return;
  
/* supporting ASM here would be hard. 
 * You cannot have negative stock in warehouses and Order-edit doesn't check whether there is enough stock.
 * There is also the multi-warehouse problem: what to do when someone orders 10 pieces and you have 5 in one warehouse and 5 in another?
 */
 /* Stock_available in PS 1.7.6:
 Without attributes:
						`quantity`, `physical_quantity`, `reserved_quantity` 
Before order creation: 		80			80					0
After order creation:		65			80					15
After payment:				65			80					15
After shipment				65			65					0	
  
 With attributes						id_attribute=0									id_attribute=x
						`quantity`, `physical_quantity`, `reserved_quantity` `quantity`, `physical_quantity`, `reserved_quantity`
Before order creation: 		100			100					0					50				50					0
After order creation:		 97			 97					0					47				50					3
After payment:				 97			 97					0					47				50					3	
After shipment				 97			 97					0					47				47					0
 
  */
 
 
  /* sometimes stock_available is not set */
  if($share_stock)
  { $res = dbquery("SELECT * FROM "._DB_PREFIX_."stock_available where id_product=".$id_product." AND id_product_attribute=0 AND id_shop_group=".$id_shop_group);
    if(mysqli_num_rows($res)==0) dbquery("INSERT INTO "._DB_PREFIX_."stock_available SET quantity=0, id_product='".$id_product."', id_product_attribute='0',id_shop_group=".$id_shop_group.",id_shop=0,depends_on_stock='0',out_of_stock='2'");
	if($id_product_attribute != 0)
	{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."stock_available where id_product=".$id_product." AND id_product_attribute='".$id_product_attribute."' AND id_shop_group=".$id_shop_group);
      if(mysqli_num_rows($res)==0) dbquery("INSERT INTO "._DB_PREFIX_."stock_available SET quantity=0, id_product='".$id_product."', id_product_attribute='".$id_product_attribute."',id_shop_group=".$id_shop_group.",id_shop=0,depends_on_stock='0',out_of_stock='2'");
	}
  }
  else
  { $res = dbquery("SELECT * FROM "._DB_PREFIX_."stock_available where id_product=".$id_product." AND id_product_attribute=0 AND id_shop=".$id_shop);
    if(mysqli_num_rows($res)==0) dbquery("INSERT INTO "._DB_PREFIX_."stock_available SET quantity=0, id_product='".$id_product."', id_product_attribute='0',id_shop=".$id_shop.",id_shop_group=0,depends_on_stock='0',out_of_stock='2'");
	if($id_product_attribute != 0)
	{ $res = dbquery("SELECT * FROM "._DB_PREFIX_."stock_available where id_product=".$id_product." AND id_product_attribute='".$id_product_attribute."' AND id_shop=".$id_shop);
      if(mysqli_num_rows($res)==0) dbquery("INSERT INTO "._DB_PREFIX_."stock_available SET quantity=0, id_product='".$id_product."', id_product_attribute='".$id_product_attribute."',id_shop=".$id_shop.",id_shop_group=0,depends_on_stock='0',out_of_stock='2'");
	}
  }
  
  $reserved = "";
  if (version_compare(_PS_VERSION_ , "1.7.2", ">="))
	  $reserved = ',reserved_quantity=reserved_quantity-'.$quantchange; /* reserved is processed into physical_quantity at delivery */
  if(($id_product_attribute==0))
  { if($share_stock)
	  dbquery("UPDATE "._DB_PREFIX_."stock_available SET quantity=quantity+".$quantchange.$reserved." where id_product=".$id_product." AND id_product_attribute=0 AND id_shop_group=".$id_shop_group);
    else
	  dbquery("UPdate "._DB_PREFIX_."stock_available SET quantity=quantity+".$quantchange.$reserved." where id_product=".$id_product." AND id_product_attribute=0 AND id_shop=".$id_shop);
  }
  else	 /* $id_product_attribute!=0 */
  { $physquant = "";
    if (version_compare(_PS_VERSION_ , "1.7.2", ">="))
	  $physquant = ',physical_quantity=physical_quantity+'.$quantchange;
    if($share_stock)
	  dbquery("UPDATE "._DB_PREFIX_."stock_available SET quantity=quantity+".$quantchange.$physquant." where id_product=".$id_product." AND id_product_attribute=0 AND id_shop_group=".$id_shop_group);
    else
	  dbquery("UPdate "._DB_PREFIX_."stock_available SET quantity=quantity+".$quantchange.$physquant." where id_product=".$id_product." AND id_product_attribute=0 AND id_shop=".$id_shop);
    if($share_stock)
	  dbquery("UPdaTE "._DB_PREFIX_."stock_available SET quantity=quantity+".$quantchange.$reserved." where id_product=".$id_product." AND id_product_attribute=".$id_product_attribute." AND id_shop_group=".$id_shop_group);
    else
	  dbquery("UpdaTE "._DB_PREFIX_."stock_available SET quantity=quantity+".$quantchange.$reserved." where id_product=".$id_product." AND id_product_attribute=".$id_product_attribute." AND id_shop=".$id_shop);
  }
}

function update_total($id_order) 
{ global $conn,$round_type,$price_round_mode,$precision,$carrier_tax_rate, $wrap_tax_rate,$verbose,$ps_atcp_shipwrap, $id_order_invoice, $id_country, $id_state, $zero_vat;
  $query = "select sum(total_price_tax_incl) as total_products_wt,sum(total_price_tax_excl) as total_products_notax";
  $query .= ",SUM(product_quantity*product_weight) AS total_weight";
  $query .= " FROM "._DB_PREFIX_."order_detail where id_order=".$id_order;
  $res2=dbquery($query);
  $products=mysqli_fetch_array($res2);
  if($products['total_products_wt']=="")
    $products['total_products_wt'] = $products['total_products_notax'] = 0; /* no products present */

  $query="select * from  "._DB_PREFIX_."orders where id_order=".(int)$id_order;
  $res3=dbquery($query);
  $order=mysqli_fetch_array($res3);
  $id_shop = $order["id_shop"];
  
  /* the discount tax rate is calculated from total products tax excl/tax incl */
  if($products['total_products_notax'] > 0)
    $discount_tax_rate = 100*($products['total_products_wt'] - $products['total_products_notax']) / $products['total_products_notax'];
  else if($products['total_products_wt'] > 0)
	  colordie("Impossible situation: with tax > 0; without tax=0!!!");
  else 
	$discount_tax_rate = $carrier_tax_rate;
  
  $total_products_wt = ps_round($products['total_products_wt'],$precision,$round_type);
  $total_products_notax = ps_round($products['total_products_notax'],$precision,$round_type);
  $total_shipping = ps_round($order['total_shipping'],$precision,$round_type);
  if($ps_atcp_shipwrap)
    $total_shipping_tax_excl = ps_round(($order['total_shipping_tax_incl']*100/(100+$discount_tax_rate)),$precision,$round_type);
  else
    $total_shipping_tax_excl = ps_round($order['total_shipping_tax_excl'],$precision,$round_type);
  $total_shipping_tax_incl = ps_round($order['total_shipping_tax_incl'],$precision,$round_type);
  $total_wrapping = ps_round($order['total_wrapping'],$precision,$round_type);
  if($ps_atcp_shipwrap)
    $total_wrapping_tax_excl = ps_round(($order['total_wrapping_tax_incl']*100/(100+$discount_tax_rate)),$precision,$round_type);	  
  else 
    $total_wrapping_tax_excl = ps_round($order['total_wrapping_tax_excl'],$precision,$round_type);
  $total_wrapping_tax_incl = ps_round($order['total_wrapping_tax_incl'],$precision,$round_type);
  $total_discount = ps_round($order['total_discounts'],$precision,$round_type);
  $total_discount_tax_excl = ps_round($order['total_discounts']*100/(100+$discount_tax_rate),$precision,$round_type);  
  $total_discount_tax_incl = ps_round($order['total_discounts'],$precision,$round_type);  
  $total_paid=$total_products_wt+$total_shipping_tax_incl+$total_wrapping_tax_incl-$total_discount_tax_incl;
  $total_ex=$total_products_notax+$total_shipping_tax_excl+$total_wrapping_tax_excl-$total_discount_tax_excl;
  
  if(isset($_GET['diff_excl']))
  { $total_ex += $_GET['diff_excl'];
	$total_paid += $_GET['diff_excl']*(100+$_GET['diff_tax'])/100;
  }
  
  /* total amount should not sink below zero */
  if($total_paid < 0)
  { $total_discount_tax_incl = $total_discount_tax_incl + $total_paid;
    $total_paid = 0;
	$total_discount_tax_excl = $total_discount_tax_excl + $total_ex;
    $total_ex = 0;
	$total_discount = ps_round($total_discount_tax_incl,$precision,$round_type);
  }
  
  /* handle discount tax when subtraction happens from shipping or wrapping */
  /* this can probably be done better but prevents the most extreme cases */
  if(($total_discount_tax_incl>0) && ($total_paid < $total_ex))
  { $diff = $total_ex-$total_paid;
    $total_ex = $total_paid;
	$total_discount_tax_excl += $diff;
  }
  
  $query="update "._DB_PREFIX_."orders set ";
  $query.=" total_products=".$total_products_notax;
  $query.=" ,total_products_wt=".$total_products_wt;
  $query.=" ,total_paid_tax_excl=".$total_ex;
  $query.=" ,total_paid_tax_incl=".$total_paid;
  $query.=" ,total_paid_real=".$total_paid; /* not set by Prestashop */
  $query.=" ,total_paid=".$total_paid;
  $query.=" ,total_discounts_tax_excl=".$total_discount_tax_excl;
  $query.=" ,total_discounts_tax_incl=".$total_discount_tax_incl;
  $query.=" ,total_discounts=".$total_discount;
  if($ps_atcp_shipwrap == 1)
  { $query.=" ,carrier_tax_rate=".$discount_tax_rate;
	$query.=" ,total_wrapping_tax_excl=".$total_wrapping_tax_excl;
	$query.=" ,total_shipping_tax_excl=".$total_shipping_tax_excl;
  }
  $query.=" ,date_upd=NOW()";
  $query.=" where id_order=".(int)$id_order;
  $query.=" limit 1";
  dbquery($query);
  
  $query="update "._DB_PREFIX_."order_invoice set ";
  $query.=" total_discount_tax_excl=".$total_discount_tax_excl;
  $query.=", total_discount_tax_incl=".$total_discount_tax_incl;
  if($ps_atcp_shipwrap == 1)
  { $query.=", total_wrapping_tax_excl=".$total_wrapping_tax_excl;
	$query.=", total_shipping_tax_excl=".$total_shipping_tax_excl;
  }
  $query.=", total_paid_tax_excl=".$total_ex;
  $query.=", total_paid_tax_incl=".$total_paid;
  $query.=", total_products=".$total_products_notax;
  $query.=", total_products_wt=".$total_products_wt;
  $query.=" where id_order=".(int)$id_order;
  $query.=" limit 1";
  dbquery($query);
  
  $query="UPDATE "._DB_PREFIX_."order_carrier SET";
  $query .= " weight=".$products['total_weight'];
  $query.=" ,shipping_cost_tax_excl=".$total_shipping_tax_excl;
  $query.=" ,shipping_cost_tax_incl=".$total_shipping_tax_incl;
  $query.=" WHERE id_order=".(int)$id_order;
  dbquery($query); 

  /* 
   * Normally ps_order_invoice_tax is only used to store the shipping VAT
   * But when $ps_atcp_shipwrap is enabled Thirty Bees uses it also for wrapping
   * This is done to get standard taxes in such circumstances. 
   * Example: Shipping is 10 euro. Order is 20 euro high tax (20%) and 20 euro low tax (4%)
   * So the average tax is 12%. In this case you see two entries in ps_order_invoice_tax
   * one for 5 euro with 4% tax and one for 5 euro with 20% tax. 
   * On the invoice you will see a low tax part and a high tax part shipping too.
   * The same applies to wrapping.
   * For discounts - that always uses average VAT - a different solution is used.
   * Nothing is changed in the database. But on the invoice the summary values are changed.
   * So when in the above order there was a general discount of 5 euro excl VAT 
   * you would see: total products low tax: 17,50 and total products high tax 17,50
   * with the taxes calculated over those values.
   */
  if(intval($id_order_invoice) > 0)
  { $query="DELETE FROM "._DB_PREFIX_."order_invoice_tax WHERE id_order_invoice=".$id_order_invoice;
    $res = dbquery($query);
    if(($total_products_notax == $total_products_wt) || $zero_vat)
    { /* do nothing */
    }
    else if($ps_atcp_shipwrap)
    { $query = "select sum(total_price_tax_incl) as total_products_wt,sum(total_price_tax_excl) as total_products_notax";
      $query .= ",tr.id_tax,rate";
      $query .= " FROM "._DB_PREFIX_."order_detail od";
	  $query .= " LEFT JOIN ". _DB_PREFIX_."product p ON p.id_product=od.product_id";
      $query .= " LEFT JOIN "._DB_PREFIX_."tax_rule tr ON (p.id_tax_rules_group = tr.id_tax_rules_group)";
      $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON (tr.id_tax = t.id_tax)";
	  $query .= " WHERE tr.id_country = '".$id_country."' AND tr.id_state='0' AND id_order=".$id_order;
      $query .= " GROUP BY id_tax";
      $res=dbquery($query);
	  $taxes = array();
	  while($row = mysqli_fetch_array($res))
	  { echo "<br>XX: "; print_r($row); echo "  <br>";
	    if($row["total_products_wt"] != $row["total_products_notax"])
	    { $pct = $row["total_products_notax"]/$total_products_notax;
		  $taxes[$row["id_tax"]] = array($pct, $row["rate"]);
	    }
	  }

	  if($total_shipping_tax_excl != $total_shipping_tax_incl)
	  { $cnt = 0;
	    $total = 0;
	    foreach($taxes AS $id_tax => $sub)
	    { if($cnt++ == sizeof($taxes))
		    $amount = $total_shipping_tax_excl - $total;
	      else
		    $amount = $sub[0]*$total_shipping_tax_excl;
	      $iquery = "INSERT INTO "._DB_PREFIX_."order_invoice_tax";
	      $iquery .= " SET id_order_invoice=".$id_order_invoice.",id_tax=".$id_tax;
	      $iquery .= ",type='shipping',amount='".($amount*$sub[1]/100)."'";
          $ires = dbquery($iquery);
	      $total += $amount;
	    }
	  }
	  if($total_wrapping_tax_excl != $total_wrapping_tax_incl)
	  { $totalWrapVAT = $total_wrapping_tax_incl - $total_wrapping_tax_excl;
	    $cnt = 0;
	    $total = 0;
	    foreach($taxes AS $id_tax => $sub)
	    { if($cnt++ == sizeof($taxes))
		    $amount = $total_wrapping_tax_excl - $total;
	      else
		    $amount = $sub[0]*$total_wrapping_tax_excl;
	      $iquery = "INSERT INTO "._DB_PREFIX_."order_invoice_tax";
	      $iquery .= " SET id_order_invoice=".$id_order_invoice.",id_tax=".$id_tax;
	      $iquery .= ",type='wrapping',amount='".($amount*$sub[1]/100)."'";
          $ires = dbquery($iquery);
	      $total += $amount;
	    }
	  } 
    }
    else
    { $query="select t.id_tax, rate, id_carrier, ct.id_tax_rules_group FROM "._DB_PREFIX_."carrier_tax_rules_group_shop ct ";
      $query.=" left join "._DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=ct.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state IN ('0','".$id_state."')";
      $query.=" left join "._DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
      $query .= " where ct.id_carrier=".(int)$order['id_carrier']." AND ct.id_shop=".$id_shop;
      $res = dbquery($query);
	  $row = mysqli_fetch_assoc($res);
	  if($row["id_tax_rules_group"] == 0)
	    $id_tax = 0;
      else
        $id_tax=$row["id_tax"];
	  $iquery = "INSERT INTO "._DB_PREFIX_."order_invoice_tax";
	  $iquery .= " SET id_order_invoice=".$id_order_invoice.",id_tax=".$id_tax;
	  $iquery .= ",type='shipping',amount='".($total_shipping_tax_incl-$total_shipping_tax_excl)."'";
      $ires = dbquery($iquery);
	}
  }   

  if(isset($_GET["updatepayment"]) && (($_GET["updatepayment"]=="true")||($_GET["updatepayment"]=="on") ))
  { echo "<br><b>Updating payment.</b><br>";
	$query="SELECT * FROM "._DB_PREFIX_."order_payment WHERE order_reference='".$order["reference"]."'";
    $res4=dbquery($query);
	if(mysqli_num_rows($res4) != 1)
	{ colordie("<p>Payments cannot be updated when there is more than one payment for an order!");
	  return; /* shouldn't happen */
	}
	$row4=mysqli_fetch_assoc($res4);
	if($row4["transaction_id"] != "")
	{ colordie("<p>Payments with a transaction id cannot be changed!");
	  return; /* shouldn't happen: is filtered in order-edit */
	}
    $query="update "._DB_PREFIX_."order_payment set amount='".$total_paid."' WHERE order_reference='".$order["reference"]."'";
    $res5=dbquery($query);
  }
  
  if($verbose=="true")
  { echo '<table border=1><tr><td></td><td>excl. VAT</td><td>VAT in %</td><td>incl. VAT</td></tr>';
	if($total_products_notax > 0)
	  echo '<tr><td>Products</td><td>'.$total_products_notax.'</td><td>'.ps_round((100*($total_products_wt-$total_products_notax)/$total_products_notax),2,$round_type).'</td><td>'.$total_products_wt.'</td></tr>';
    else
	  echo '<tr><td>Products</td><td>'.$total_products_notax.'</td><td>????</td><td>'.$total_products_wt.'</td></tr>';
    echo '<tr><td>Discounts</td><td>'.$total_discount_tax_excl.'</td><td>'.ps_round($discount_tax_rate,2,$round_type).'</td><td>'.$total_discount_tax_incl.'</td></tr>';
    echo '<tr><td>Shipping</td><td>'.$total_shipping_tax_excl.'</td><td>'.$carrier_tax_rate.'</td><td>'.$total_shipping_tax_incl.'</td></tr>';
    echo '<tr><td>Wrapping</td><td>'.$total_wrapping_tax_excl.'</td><td>'.$wrap_tax_rate.'</td><td>'.$total_wrapping_tax_incl.'</td></tr>';
    echo '<tr><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><td>Total</td><td>'.$total_ex.'</td><td>'.ps_round((100*($total_paid-$total_ex)/$total_ex),2,$round_type).'</td><td>'.$total_paid.'</td></tr>';
  }
}

function price($price) {
$price=str_replace(",",".",$price);
return $price;
}

$taxgrouprates = [];
function gettaxgrouprate($id_tax_rules_group)
{ global $taxgrouprates, $zero_vat, $id_country, $id_state;
  if($zero_vat) return 0;
  if(!$id_tax_rules_group) return 0; /* zero or empty string */
  if(isset($taxgrouprates[$id_tax_rules_group]))
	return $taxgrouprates[$id_tax_rules_group];
  
  $query="select SUM(t.rate) AS tax FROM "._DB_PREFIX_."tax_rule tr";
  $query.=" left join "._DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
  $query .= " where tr.id_tax_rules_group=".$id_tax_rules_group." AND tr.id_country='".$id_country."' AND tr.id_state IN ('0','".$id_state."')";
  $res = dbquery($query);
  if(mysqli_num_rows($res) > 0)
  { $row=mysqli_fetch_array($res);
    $rate = (float)$row["tax"];
  }
  else
	$rate = 0; 
  $taxgrouprates[$id_tax_rules_group] = $rate;
  return $rate;
}


