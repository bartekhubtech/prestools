<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
$mode = "background";
if(isset($_POST["subject"]))
  $input = $_POST;
else
  $input = $_GET;
if(isset($input['id_lang']))
  $id_lang = strval(intval($_GET['id_lang']));
else
  $id_lang = get_configuration_value('PS_LANG_DEFAULT');
$errstring = "";
if($prestools_settings["demo_mode"])
{ echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
  die();
}

if(($input['subject'] != "sqlcutter") && ($input['subject'] != "backup") && ($input['subject'] != "bkuptable"))
{ echo '<!DOCTYPE html> 
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
  echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';
  if(!isset($input['subject'])) die("No subject specified!");
} 

/* making shop block */
$shops = array();
$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $shop_ids[] = $row['id_shop'];
}

  /* Note on the use of '(SELECT * from "._DB_PREFIX_."product_shop)' on the second lines of the queries
   * This is necessary because otherwise some (not all) mysql installation produce the error:
   *   1093: Table 'ps_product_shop' is specified twice
   * See: https://stackoverflow.com/questions/44970574/table-is-specified-twice-both-as-a-target-for-update-and-as-a-separate-source
   */
  if($input['subject'] == "cartconvert")
  { /* convert cart into order */
    $id_cart = intval($input['id_cart']);
    $id_customer = intval($input['id_customer']);

    $query="select * from "._DB_PREFIX_."cart";
    $query .= " WHERE id_cart=".$id_cart;
    $res=dbquery($query);
	if(mysqli_num_rows($res) == 0)
		colordie("There is no cart with id_cart=".$id_cart);
	$row = mysqli_fetch_array($res);
	
	if(($row["id_customer"] != 0) && ($id_customer != 0))
		colordie("You cannot provide a customer id when the cart has a valid customer!");
	if(($row["id_customer"] == 0) && ($id_customer == 0))
		colordie("This cart has no customer and you didn't provide a customer id either!");
	if($id_customer == 0)
		$id_customer = $row["id_customer"];
	
	$cquery="select * from "._DB_PREFIX_."customer";
    $cquery .= " WHERE id_customer=".$id_customer;
    $cres=dbquery($cquery);
    if(mysqli_num_rows($cres) == 0)
		colordie("There is no customer with id_customer=".$id_customer);
	$crow = mysqli_fetch_array($cres);
	
	$aquery="select * from "._DB_PREFIX_."orders";
    $aquery .= " WHERE id_cart=".$id_cart;
    $ares=dbquery($aquery);
    if(mysqli_num_rows($ares) > 0)
	{ $arow = mysqli_fetch_array($ares);
	  colordie("There is already an order for cart number ".$id_cart.". This is order number ".$arow['id_order'].".");
	}
	
	$aquery="select * from "._DB_PREFIX_."cart_product";
    $aquery .= " WHERE id_cart=".$id_cart;
    $ares=dbquery($aquery);
    if(mysqli_num_rows($ares) == 0)
	{ colordie("The cart with number ".$id_cart." does not contain any products.");
	}
	
    require_once($triplepath.'/config/config.inc.php');
//    include($triplepath.'init.php');
	include($triplepath.'header.php');
//    require_once($triplepath.'/index.php');
	$cart = new Cart((int)$id_cart);
	$cart->id_customer = $id_customer;
	$order_status = (int)Configuration::get('PS_OS_PAYMENT');
	$order_total = $cart->getOrderTotal(true, Cart::BOTH);
	$paymentModule = Module::getInstanceByName('bankwire');
    if ($paymentModule) 
	  $paymentModule->validateOrder($cart->id,  //  $idCart,
		$order_status, // $idOrderState,
		$order_total, // $amountPaid,
		"Bankwire Module", // $paymentMethod = 'Unknown',
		null,  // $message = null,
		array(), // $extraVars = [],
		null, // $currencySpecial = null,
		false, // $dontTouchAmount = false,
		$cart->secure_key); // $secureKey = false,
		// skipped:  Shop $shop = null
//	$this->module->validateOrder($cart->id, $order_status, $order_total, "Bankwire Module", null, array(), null, false, $cart->secure_key);

    // This conversion is handled in /classes/module/PaymentModule.php
	echo "time=".time()."<br>";
    echo '<script>alert("Finished");</script>';
		echo "time=".time()."<br>";
 }

  if($input['subject'] == "stockdeactivate")
  { /* get shop group and its shared_stock status */
    $cnt1 = 0;
	echo "time=".time()."<br>";
    $query="select s.id_shop,s.id_shop_group, g.share_stock from "._DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
    $query .= " WHERE s.id_shop_group=g.id_shop_group ORDER BY id_shop";
    $res=dbquery($query);
    while($row = mysqli_fetch_array($res))
    { /* first products without combinations */
	  if($row["share_stock"] == 0)
	  { 
		$squery = " UPDATE "._DB_PREFIX_."product_shop ps";
		$squery .= " JOIN "._DB_PREFIX_."stock_available sa ON ps.id_product=sa.id_product AND ps.id_shop=sa.id_shop";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON sa.id_product=pa.id_product";
		$squery .= " SET ps.active=0, ps.indexed=0";
		$squery .= " WHERE ps.id_shop=".$row["id_shop"]." AND ps.active=1 AND pa.id_product_attribute is null"; 
		$squery .= " AND sa.id_shop=".$row["id_shop"]." AND sa.quantity <= 0";
	  }
	  else
      {
		$squery = " UPDATE "._DB_PREFIX_."product_shop ps";
		$squery .= " JOIN "._DB_PREFIX_."stock_available sa ON ps.id_product=sa.id_product AND ps.id_shop=sa.id_shop";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON sa.id_product=pa.id_product";
		$squery .= " SET ps.active=0, ps.indexed=0";
		$squery .= " WHERE ps.id_shop=".$row["id_shop"]." AND ps.active=1 AND pa.id_product_attribute is null"; 
		$squery .= " AND sa.id_shop_group=".$row["id_shop_group"]." AND sa.quantity <= 0";
	  }
      $sres=dbquery($squery);
	  $cnt1 += mysqli_affected_rows($conn);

	  /* now the products with combinations */
	  /* note that on older PS versions the ps_product_attribute_shop table doesn't contain an id_product field */
	  if($row["share_stock"] == 0)
	  { $squery = "UPDATE "._DB_PREFIX_."product_shop SET active=0,indexed=0 WHERE id_shop=".$row["id_shop"];
	    $squery .= " AND id_product IN (select ps.id_product from 	
		(SELECT * from "._DB_PREFIX_."product_shop) AS ps";
		$squery .= " WHERE ps.active=1 AND ps.id_shop=".$row["id_shop"]." AND EXISTS";
		$squery .= " (select null FROM "._DB_PREFIX_."product_attribute_shop pas";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pas.id_product_attribute";
		$squery .= " WHERE pa.id_product=ps.id_product AND pas.id_shop=".$row["id_shop"].")";
		$squery .= " AND NOT EXISTS (select null FROM "._DB_PREFIX_."stock_available sa WHERE ps.id_product=sa.id_product AND sa.id_shop=".$row["id_shop"]." AND sa.quantity > 0))";
	  }
	  else
	  { $squery = "UPDATE "._DB_PREFIX_."product_shop SET active=0,indexed=0 WHERE id_shop=".$row["id_shop"];
	    $squery .= " AND id_product IN (select ps.id_product from (SELECT * from "._DB_PREFIX_."product_shop) AS ps";
		$squery .= " WHERE ps.active=1 AND ps.id_shop=".$row["id_shop"]." AND EXISTS";
		$squery .= " (select null FROM "._DB_PREFIX_."product_attribute_shop pas";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pas.id_product_attribute";
		$squery .= " WHERE pa.id_product=ps.id_product AND pas.id_shop=".$row["id_shop"].")";
		$squery .= " AND NOT EXISTS (select null FROM "._DB_PREFIX_."stock_available sa WHERE ps.id_product=sa.id_product AND sa.id_shop_group=".$row["id_shop_group"]." AND sa.quantity > 0))";
	  }
	echo "time=".time()."<br>";
      $sres=dbquery($squery);
	  $cnt1 += mysqli_affected_rows($conn);
    }
	/* now disable ps_product for product where none of the ps_product_shop entries is active */
/*	$query="UPDATE "._DB_PREFIX_."product SET active=0 WHERE id_product IN (select p.id_product from (SELECT * from "._DB_PREFIX_."product) AS p";
	$query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps ON p.id_product=ps.id_product AND  ps.active=1";
    $query .= " WHERE p.active=1 AND ps.id_shop is null)";
*/	
	$query="UPDATE "._DB_PREFIX_."product p";
	$query .= " LEFT JOIN "._DB_PREFIX_."product_shop ps ON p.id_product=ps.id_product AND ps.active=1";
	$query .= " SET p.active=0, p.indexed=0";
    $query .= " WHERE p.active=1 AND ps.id_shop is null";
    $res=dbquery($query);
	$cnt2 = mysqli_affected_rows($conn);

    echo '<script>alert("Finished: '.$cnt2.' products AND '.$cnt1.' product_shops were deactivated!");</script>';
		echo "time=".time()."<br>";
 }
 else if($input['subject'] == "stockactivate")
  { /* get shop group and its shared_stock status */
  	echo "time=".time()."<br>";
    $query="select s.id_shop,s.id_shop_group, g.share_stock from "._DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
    $query .= " WHERE s.id_shop_group=g.id_shop_group ORDER BY id_shop";
    $res=dbquery($query);
	$cnt1 = 0;
    while($row = mysqli_fetch_array($res))
    { /* first products without combinations */
	  if($row["share_stock"] == 0)
	  { 
		$squery = " UPDATE "._DB_PREFIX_."product_shop ps";
		$squery .= " JOIN "._DB_PREFIX_."stock_available sa ON ps.id_product=sa.id_product AND ps.id_shop=sa.id_shop";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON sa.id_product=pa.id_product";
		$squery .= " SET ps.active=1, ps.indexed=0";
		$squery .= " WHERE ps.id_shop=".$row["id_shop"]." AND ps.active=0 AND pa.id_product_attribute is null"; 
		$squery .= " AND sa.id_shop=".$row["id_shop"]." AND sa.quantity > 0";
		
	  }
	  else
      { 
		$squery = " UPDATE "._DB_PREFIX_."product_shop ps";
		$squery .= " JOIN "._DB_PREFIX_."stock_available sa ON ps.id_product=sa.id_product AND ps.id_shop=sa.id_shop";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON sa.id_product=pa.id_product";
		$squery .= " SET ps.active=1, ps.indexed=0";
		$squery .= " WHERE ps.id_shop=".$row["id_shop"]." AND ps.active=1 AND pa.id_product_attribute is null"; 
		$squery .= " AND sa.id_shop_group=".$row["id_shop_group"]." AND sa.quantity > 0";
	  }
      $sres=dbquery($squery);
	  $cnt1 += mysqli_affected_rows($conn);
	  echo "time=".time()."<br>";

	  /* now the products with combinations */
	  /* note that on older PS versions the ps_product_attribute_shop table doesn't contain an id_product field */
	  if($row["share_stock"] == 0)
	  { $squery = "UPDATE "._DB_PREFIX_."product_shop SET active=1,indexed=0 WHERE id_shop=".$row["id_shop"];
	    $squery .= " AND id_product IN (select ps.id_product from (SELECT * from "._DB_PREFIX_."product_shop) AS ps";
		$squery .= " WHERE ps.active=0 AND ps.id_shop=".$row["id_shop"];
		$squery .= " AND EXISTS (select null FROM "._DB_PREFIX_."stock_available sa WHERE ps.id_product=sa.id_product AND sa.id_shop=".$row["id_shop"]." AND sa.quantity > 0))";
	  }
	  else
	  { $squery = "UPDATE "._DB_PREFIX_."product_shop SET active=1,indexed=0 WHERE id_shop=".$row["id_shop"];
	    $squery .= " AND id_product IN (select ps.id_product from (SELECT * from "._DB_PREFIX_."product_shop) AS ps";
		$squery .= " WHERE ps.active=1 AND ps.id_shop=".$row["id_shop"]." AND EXISTS";
		$squery .= " (select null FROM "._DB_PREFIX_."product_attribute_shop pas";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pas.id_product_attribute";
		$squery .= " WHERE pa.id_product=ps.id_product AND pas.id_shop=".$row["id_shop"].")";
		$squery .= " AND NOT EXISTS (select null FROM "._DB_PREFIX_."stock_available sa WHERE ps.id_product=sa.id_product AND sa.id_shop_group=".$row["id_shop_group"]." AND sa.quantity > 0))";
	  }
      $sres=dbquery($squery);
	  $cnt1 += mysqli_affected_rows($conn);
	  echo "time=".time()."<br>";
	}
	
	/* now enable ps_product for product where at least one ps_product_shop entry is active */
/*	$query = "UPDATE "._DB_PREFIX_."product SET active=1 WHERE id_product IN (";
	$query .= "select p.id_product from (SELECT * from "._DB_PREFIX_."product) AS p";
	$query .= " INNER JOIN "._DB_PREFIX_."product_shop ps ON p.id_product=ps.id_product AND ps.active=1";
    $query .= " WHERE p.active=0 GROUP BY p.id_product ORDER BY p.id_product)";
*/	
	$query = "UPDATE "._DB_PREFIX_."product p";
	$query .= " INNER JOIN "._DB_PREFIX_."product_shop ps ON p.id_product=ps.id_product AND ps.active=1";
	$query .= " SET p.active=1,p.indexed=0 ";
    $query .= " WHERE p.active=0";
	
    $res=dbquery($query);
	$cnt2 = mysqli_affected_rows($conn);
    echo '<script>alert("Finished: '.$cnt2.' products AND '.$cnt1.' product_shops were activated!");</script>';
	echo "time=".time()."<br>";
 }
 
 else if($input['subject'] == "manufactureractivate")
 { $query = "SELECT m.id_manufacturer FROM "._DB_PREFIX_."manufacturer m";
   $query .= " WHERE m.active=0 AND EXISTS ";
   $query .= " (SELECT NULL FROM "._DB_PREFIX_."product p WHERE active=1 AND p.id_manufacturer=m.id_manufacturer)";
   $res=dbquery($query);
   $cnt = mysqli_num_rows($res);
   while($row = mysqli_fetch_array($res))
   { $uquery = "UPDATE "._DB_PREFIX_."manufacturer SET active=1 WHERE id_manufacturer=".$row["id_manufacturer"];
	 $ures=dbquery($uquery);
   }
   echo '<script>alert("Finished: '.$cnt.' manufacturers were activated!");</script>';
 }
 else if($input['subject'] == "manufacturerdeactivate")
 { $query = "SELECT m.id_manufacturer FROM "._DB_PREFIX_."manufacturer m";
   $query .= " WHERE m.active=1 AND NOT EXISTS ";
   $query .= " (SELECT NULL FROM "._DB_PREFIX_."product p WHERE active=1 AND p.id_manufacturer=m.id_manufacturer)";
   $res=dbquery($query);
   $cnt = mysqli_num_rows($res);
   while($row = mysqli_fetch_array($res))
   { $uquery = "UPDATE "._DB_PREFIX_."manufacturer SET active=0 WHERE id_manufacturer=".$row["id_manufacturer"];
	 $ures=dbquery($uquery);
   }
   echo '<script>alert("Finished: '.$cnt.' manufacturers were deactivated!");</script>';
 }
 else if($input['subject'] == "nullremover")
 {   /* Prestashop 8 rejects some ps_product values when NULL; Yet that is the default */
  /* borrowed from https://github.com/PrestaShop/autoupgrade/pull/605/files */
   if(version_compare(_PS_VERSION_ , "1.6.0.9", ">="))
   {	/* Normalize some older database records that should not be NULL, prevents errors in new code. */
	/* Mainly concerns people coming all the way from 1.6, but will fix many inconsitencies. */
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `address2`='' WHERE `address2` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `company`='' WHERE `company` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `dni`='' WHERE `dni` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `id_state`='0' WHERE `id_state` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `other`='' WHERE `other` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `phone_mobile`='' WHERE `phone_mobile` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `phone`='' WHERE `phone` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `postcode`='' WHERE `postcode` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."address` SET `vat_number`='' WHERE `vat_number` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."attachment_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."carrier_lang` SET `delay`='' WHERE `delay` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."carrier` SET `external_module_name`='' WHERE `external_module_name` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."carrier` SET `url`='' WHERE `url` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cart_rule` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cart` SET `gift_message`='' WHERE `gift_message` IS NULL");
	if(version_compare(_PS_VERSION_ , "8.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."category_lang` SET `additional_description`='' WHERE `additional_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."category_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."category_lang` SET `meta_description`='' WHERE `meta_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."category_lang` SET `meta_keywords`='' WHERE `meta_keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."category_lang` SET `meta_title`='' WHERE `meta_title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_category_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_category_lang` SET `meta_description`='' WHERE `meta_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_category_lang` SET `meta_keywords`='' WHERE `meta_keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_category_lang` SET `meta_title`='' WHERE `meta_title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_lang` SET `content`='' WHERE `content` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.5", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."cms_lang` SET `head_seo_title`='' WHERE `head_seo_title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_lang` SET `meta_description`='' WHERE `meta_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."cms_lang` SET `meta_keywords`='' WHERE `meta_keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."configuration_kpi_lang` SET `date_upd`=CURRENT_TIMESTAMP WHERE `date_upd` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."configuration_kpi_lang` SET `value`='' WHERE `value` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."configuration_lang` SET `date_upd`=CURRENT_TIMESTAMP WHERE `date_upd` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."configuration_lang` SET `value`='' WHERE `value` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."connections_source` SET `http_referer`='' WHERE `http_referer` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."connections_source` SET `keywords`='' WHERE `keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."connections` SET `http_referer`='' WHERE `http_referer` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."connections` SET `ip_address`='0' WHERE `ip_address` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."contact_lang` SET `description`='' WHERE `description` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.6", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."currency_lang` SET `pattern`='' WHERE `pattern` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer_message` SET `file_name`='' WHERE `file_name` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer_message` SET `id_employee`='0' WHERE `id_employee` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer_message` SET `ip_address`='' WHERE `ip_address` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer_message` SET `user_agent`='' WHERE `user_agent` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer_thread` SET `id_order`='0' WHERE `id_order` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer_thread` SET `id_product`='0' WHERE `id_product` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer` SET `birthday`='0000-00-00' WHERE `birthday` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."customer` SET `newsletter_date_add`='0000-00-00 00:00:00' WHERE `newsletter_date_add` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."customer` SET `reset_password_validity`='0000-00-00 00:00:00' WHERE `reset_password_validity` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."employee` SET `bo_css`='' WHERE `bo_css` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."employee` SET `reset_password_validity`='0000-00-00 00:00:00' WHERE `reset_password_validity` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."employee` SET `stats_compare_from`='0000-00-00' WHERE `stats_compare_from` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."employee` SET `stats_compare_to`='0000-00-00' WHERE `stats_compare_to` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."employee` SET `stats_date_from`=CURRENT_TIMESTAMP WHERE `stats_date_from` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."employee` SET `stats_date_to`=CURRENT_TIMESTAMP WHERE `stats_date_to` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."feature_value` SET `custom`='0' WHERE `custom` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `accept_language`='' WHERE `accept_language` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `adobe_director`='0' WHERE `adobe_director` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `adobe_flash`='0' WHERE `adobe_flash` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `apple_quicktime`='0' WHERE `apple_quicktime` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `id_customer`='0' WHERE `id_customer` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `id_operating_system`='0' WHERE `id_operating_system` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `id_web_browser`='0' WHERE `id_web_browser` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `real_player`='0' WHERE `real_player` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `screen_color`='0' WHERE `screen_color` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `screen_resolution_x`='0' WHERE `screen_resolution_x` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `screen_resolution_y`='0' WHERE `screen_resolution_y` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `sun_java`='0' WHERE `sun_java` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."guest` SET `windows_media`='0' WHERE `windows_media` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."hook` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."image_lang` SET `legend`='' WHERE `legend` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."log` SET `error_code`='0' WHERE `error_code` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."log` SET `id_employee`='0' WHERE `id_employee` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.8", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."log` SET `id_lang`='0' WHERE `id_lang` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."log` SET `object_id`='0' WHERE `object_id` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."log` SET `object_type`='' WHERE `object_type` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."manufacturer_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."manufacturer_lang` SET `meta_description`='' WHERE `meta_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."manufacturer_lang` SET `meta_keywords`='' WHERE `meta_keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."manufacturer_lang` SET `meta_title`='' WHERE `meta_title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."manufacturer_lang` SET `short_description`='' WHERE `short_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."message` SET `id_employee`='0' WHERE `id_employee` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."meta_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."meta_lang` SET `keywords`='' WHERE `keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."meta_lang` SET `title`='' WHERE `title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_carrier` SET `id_order_invoice`='0' WHERE `id_order_invoice` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_carrier` SET `shipping_cost_tax_excl`='0' WHERE `shipping_cost_tax_excl` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_carrier` SET `shipping_cost_tax_incl`='0' WHERE `shipping_cost_tax_incl` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_carrier` SET `tracking_number`='' WHERE `tracking_number` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_carrier` SET `weight`='0' WHERE `weight` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `download_deadline`='0000-00-00 00:00:00' WHERE `download_deadline` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `download_hash`='' WHERE `download_hash` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `id_order_invoice`='0' WHERE `id_order_invoice` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_attribute_id`='0' WHERE `product_attribute_id` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_ean13`='' WHERE `product_ean13` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_isbn`='' WHERE `product_isbn` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_mpn`='' WHERE `product_mpn` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_reference`='' WHERE `product_reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_supplier_reference`='' WHERE `product_supplier_reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_detail` SET `product_upc`='' WHERE `product_upc` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_invoice` SET `delivery_date`='0000-00-00 00:00:00' WHERE `delivery_date` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_invoice` SET `note`='' WHERE `note` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.6.1.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_invoice` SET `shop_address`='' WHERE `shop_address` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_payment` SET `card_brand`='' WHERE `card_brand` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_payment` SET `card_expiration`='' WHERE `card_expiration` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_payment` SET `card_holder`='' WHERE `card_holder` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_payment` SET `card_number`='' WHERE `card_number` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_payment` SET `transaction_id`='' WHERE `transaction_id` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_return_state` SET `color`='' WHERE `color` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_slip_detail` SET `amount_tax_excl`='0' WHERE `amount_tax_excl` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_slip_detail` SET `amount_tax_incl`='0' WHERE `amount_tax_incl` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.6.0.10", ">="))
	{ $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip_detail` SET `total_price_tax_excl`='0' WHERE `total_price_tax_excl` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip_detail` SET `total_price_tax_incl`='0' WHERE `total_price_tax_incl` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip_detail` SET `unit_price_tax_excl`='0' WHERE `unit_price_tax_excl` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip_detail` SET `unit_price_tax_incl`='0' WHERE `unit_price_tax_incl` IS NULL");
	}
	if(version_compare(_PS_VERSION_ , "1.6.0.10", ">="))
	{ $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip` SET `total_products_tax_excl`='0' WHERE `total_products_tax_excl` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip` SET `total_products_tax_incl`='0' WHERE `total_products_tax_incl` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip` SET `total_shipping_tax_excl`='0' WHERE `total_shipping_tax_excl` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."order_slip` SET `total_shipping_tax_incl`='0' WHERE `total_shipping_tax_incl` IS NULL");
	}
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_state` SET `color`='' WHERE `color` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."order_state` SET `module_name`='' WHERE `module_name` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."orders` SET `gift_message`='' WHERE `gift_message` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.8", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."orders` SET `note`='' WHERE `note` IS NULL");
	if(version_compare(_PS_VERSION_ , "8.1", ">="))
	{ $res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute_lang` SET `available_later`='' WHERE `available_later` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute_lang` SET `available_now`='' WHERE `available_now` IS NULL");
	}
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute_shop` SET `available_date`='0000-00-00' WHERE `available_date` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute_shop` SET `low_stock_threshold`='0' WHERE `low_stock_threshold` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `available_date`='0000-00-00' WHERE `available_date` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `ean13`='' WHERE `ean13` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `isbn`='' WHERE `isbn` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `low_stock_threshold`='0' WHERE `low_stock_threshold` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `mpn`='' WHERE `mpn` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `reference`='' WHERE `reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `supplier_reference`='' WHERE `supplier_reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_attribute` SET `upc`='' WHERE `upc` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_download` SET `date_expiration`='0000-00-00 00:00:00' WHERE `date_expiration` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_download` SET `nb_days_accessible`='0' WHERE `nb_days_accessible` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `available_later`='' WHERE `available_later` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `available_now`='' WHERE `available_now` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
	{ $res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `delivery_in_stock`='' WHERE `delivery_in_stock` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `delivery_out_stock`='' WHERE `delivery_out_stock` IS NULL");
	}
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `description_short`='' WHERE `description_short` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `meta_description`='' WHERE `meta_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `meta_keywords`='' WHERE `meta_keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_lang` SET `meta_title`='' WHERE `meta_title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_sale` SET `date_upd`=CURRENT_TIMESTAMP WHERE `date_upd` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_shop` SET `available_date`='0000-00-00' WHERE `available_date` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_shop` SET `cache_default_attribute`='0' WHERE `cache_default_attribute` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_shop` SET `id_category_default`='0' WHERE `id_category_default` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product_shop` SET `low_stock_threshold`='0' WHERE `low_stock_threshold` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_shop` SET `unity`='' WHERE `unity` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product_supplier` SET `product_supplier_reference`='' WHERE `product_supplier_reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `available_date`='0000-00-00' WHERE `available_date` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `cache_default_attribute`='0' WHERE `cache_default_attribute` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `ean13`='' WHERE `ean13` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `id_category_default`='0' WHERE `id_category_default` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `id_manufacturer`='0' WHERE `id_manufacturer` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `id_supplier`='0' WHERE `id_supplier` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `isbn`='' WHERE `isbn` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `low_stock_threshold`='0' WHERE `low_stock_threshold` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `mpn`='' WHERE `mpn` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `reference`='' WHERE `reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `supplier_reference`='' WHERE `supplier_reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `unity`='' WHERE `unity` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."product` SET `upc`='' WHERE `upc` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."risk` SET `color`='' WHERE `color` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."stock` SET `ean13`='' WHERE `ean13` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."stock` SET `isbn`='' WHERE `isbn` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."stock` SET `mpn`='' WHERE `mpn` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."stock` SET `upc`='' WHERE `upc` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
	{ $res = dbquery("UPDATE `"._DB_PREFIX_."store_lang` SET `address2`='' WHERE `address2` IS NULL");
	  $res = dbquery("UPDATE `"._DB_PREFIX_."store_lang` SET `note`='' WHERE `note` IS NULL");
	}
	$res = dbquery("UPDATE `"._DB_PREFIX_."store` SET `email`='' WHERE `email` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."store` SET `fax`='' WHERE `fax` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."store` SET `id_state`='0' WHERE `id_state` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."store` SET `phone`='' WHERE `phone` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supplier_lang` SET `description`='' WHERE `description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supplier_lang` SET `meta_description`='' WHERE `meta_description` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supplier_lang` SET `meta_keywords`='' WHERE `meta_keywords` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supplier_lang` SET `meta_title`='' WHERE `meta_title` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supply_order_detail` SET `ean13`='' WHERE `ean13` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."supply_order_detail` SET `isbn`='' WHERE `isbn` IS NULL");
	if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $res = dbquery("UPDATE `"._DB_PREFIX_."supply_order_detail` SET `mpn`='' WHERE `mpn` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supply_order_detail` SET `upc`='' WHERE `upc` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supply_order_state` SET `color`='' WHERE `color` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."supply_order` SET `date_delivery_expected`='0000-00-00 00:00:00' WHERE `date_delivery_expected` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."warehouse_product_location` SET `location`='' WHERE `location` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."warehouse` SET `reference`='' WHERE `reference` IS NULL");
	$res = dbquery("UPDATE `"._DB_PREFIX_."webservice_account` SET `description`='' WHERE `description` IS NULL");
   }

   echo '<script>alert("Null converter finished!");</script>';
 }
 
 
 else if($input['subject'] == "indexate")
 { $products = $input["id_product"];
   $productset=preg_replace('/[^0-9, \-]+/','',$products);
   $id_shop = intval($input["id_shop"]);
   $id_lang = intval($input["id_lang"]);
   if(isset($input["reset"])) $reset = true; else $reset = false;

   update_shop_index(400, $productset, $id_lang, $id_shop, $reset);
   echo '<script>alert("Indexation finished!");</script>';
 }
 else if($input['subject'] == "analyzewords")
 { $words = $input["id_word"];
   $wordset=preg_replace('/^0-9,/','',$words);
   if($wordset=="")
     colordie('No words!<script>alert("No words!");</script>');
   $answers = $invalids = array();
   $wrds = explode(",",$wordset);
   foreach($wrds AS $wrd)
   { if(!is_numeric($wrd))
	 { $invalids[] = $wrd;
	   continue;
	 }
     $query = 'SELECT * FROM '._DB_PREFIX_.'search_word WHERE id_word="'.mysqli_real_escape_string($conn,$wrd).'"';
     $res=dbquery($query);
	 if(mysqli_num_rows($res) == 0)
	   $invalids[] = $wrd;
	 else
	 { $row = mysqli_fetch_array($res);
	   $answers[] = $wrd."=".$row["word"]."-".$row["id_shop"]."-".$row["id_lang"];
	 }
   }
   if(sizeof($invalids) > 0)
   { echo '<script>alert("The following are no valid word id\'s: '.implode(",",$invalids).'!");</script>';
   }
   echo "<script>alert('The following words were found: ".implode(",",$answers)."');</script>!";
 } 
 else if($input['subject'] == "inactivata")
 { if(sizeof($shops) > 1) return;

	$cquery = "select c.id_category,name from ". _DB_PREFIX_."category c, ". _DB_PREFIX_."category_lang l WHERE c.id_category=l.id_category AND id_lang='".$id_lang."' AND ("; 
	$cquery .= rangetosql($input["categories"],"c.id_category");
	$cquery .= ") ORDER BY id_category";
	$cres=dbquery($cquery);
	$z=0;
	$zerocount = 0;
	while ($crow=mysqli_fetch_array($cres)) 
	{	
		echo "<br>".$crow['id_category']." - ".$crow['name'];
		$pquery = "select cp.id_product,position,active,price from ". _DB_PREFIX_."category_product cp LEFT JOIN ". _DB_PREFIX_."product p ON cp.id_product=p.id_product";
		$pquery .= " WHERE id_category='".$crow['id_category']."' ORDER BY active DESC, position";
		$pres=dbquery($pquery);
		echo " - ".mysqli_num_rows($pres)." products: ";
		$pos = 0;
		$changed = 0;
		$inactives = []; // array with inactive articles
		while ($prow=mysqli_fetch_array($pres)) 
		{	if ($prow["active"]==0)
				$inactives[$prow['id_product']] = $prow['position'];
			else
			{	if($prow["position"] != $pos)
				{	
					$uquery = "UPDATE ". _DB_PREFIX_."category_product SET position = '".$pos."'";
					$uquery .= " WHERE id_category='".$crow['id_category']."' AND id_product='".$prow['id_product']."'";
					$ures=dbquery($uquery);
					$changed++;
				}
				$pos++;
			}
		}

		foreach($inactives AS $key => $oldpos)
		{	if($oldpos != $pos) 
			{	$uquery = "UPDATE ". _DB_PREFIX_."category_product SET position = '".$pos."'";
				$uquery .= " WHERE id_category='".$crow['id_category']."' AND id_product='".$key."'";
				$ures=dbquery($uquery);
				$changed++;
			}
			$pos++;
		}
		echo " ".$changed." changed";
		if($crow['id_category'] == 12) die("PPP");
	}
 }
 else if($input['subject'] == "backup")
 { 
   /* the following was borrowed from Prestashop 1.6.1.24 (function add() in /classes/PrestaShopBackup.php) */
   $outputmode = $_POST['outputmode'];
   if(($outputmode != 'zip') && ($outputmode != 'sql')) 
	 $outputmode = 'bz2';
   if(!isset($_POST['skiptables']))
	 $skiptables = [];
   else
	 $skiptables = $_POST['skiptables']; 
   if(in_array('referrer', $skiptables))
     $skiptables = array_merge($skiptables, array('referrer_cache', 'referrer_shop'));
   if(in_array('searchword', $skiptables))
   { $skiptables[] = 'search_index';
	 $skiptables[] = 'search_word';
   }
   
    /* use Prestools tmp directory */
	if(!file_exists("tmp") || !is_dir("tmp"))
	  if(!mkdir('tmp'))
		colordie("error creating tmp directory!");
	  
    $backupfile = "tmp/tmp".time();
    $zipstr = "";
	if ($outputmode == "bz2") 
	  $fp = @bzopen($backupfile, 'w');
	elseif ($outputmode == "gz") 
	  $fp = @gzopen($backupfile, 'w');
	elseif ($outputmode == "zip") 
	{ $zip = new ZipArchive();
	  if ($zip->open($backupfile, ZipArchive::CREATE)!==TRUE) 
        colordie("cannot open <$backupfile>\n");
	} else
	  $fp = @fopen($backupfile, 'w');

   if (($outputmode != "zip") && ($fp === false))
	   colordie("Error creating file ");
   
   $shopname = get_configuration_value('PS_SHOP_NAME');
   $str = '/* Backup for '.$shopname."\n *  at ".date("Y-m-d H:i:s")."\n */\n";
   $str .= "\n".'SET NAMES \'utf8\';';
   $str .= "\n".'SET FOREIGN_KEY_CHECKS = 0;';
   $str .= "\n".'SET SESSION sql_mode = \'\';'."\n\n";
   
   if ($outputmode == "zip")
	   $zipstr .= $str;
   else
	   fwrite($fp, $str);
   
   $len = strlen(_DB_PREFIX_);
   $res = dbquery('SHOW FULL TABLES'); /* FULL provides an extra field to filter out views and system tables */
   while($row = mysqli_fetch_row($res))
   { if($row[1] != "BASE TABLE") continue;
     $table = $row[0];
	 if(substr($table,0,$len) == _DB_PREFIX_)
	   $tabroot = substr($table, $len);
     else
	   $tabroot = $table;
   
     if(!isset($_POST['skipmode']) && in_array($tabroot, $skiptables))
		 continue;

	 $sres = dbquery('SHOW CREATE TABLE `'.$table.'`');
	 $srow = mysqli_fetch_assoc($sres);
     if ((mysqli_num_rows($sres) != 1) || !isset($srow['Table']) || !isset($srow['Create Table']))
	 { if ($outputmode == "zip")
	     $zip->close();
       else
	     fclose($fp);
	   unlink(realpath($backupfile));
	   colordie('<br>Invalid schema for table '.$table.'!');
	 }

     $str = "\n".'/* Scheme for table '.$srow['Table']." */\n";
     if(isset($_POST['delfirst']))
       $str .= 'DROP TABLE IF EXISTS `'.$srow['Table'].'`;'."\n";
	 $str .= $srow['Create Table'].";\n\n";
     if ($outputmode == "zip")
	   $zipstr .= $str;
     else
	   fwrite($fp, $str);
   
     if(in_array($tabroot, $skiptables))
		 continue;
	 
	 if(isset($_POST['structonly']))
		 continue;
   
     /* add table content */
     $dquery = "SELECT * FROM ".$table;
	 $dres = dbquery($dquery);
	 $sizeof = mysqli_num_rows($dres);
	 $lines = explode("\n", $srow['Create Table']);

	 if ($sizeof > 0) 
	 {	// Export the table data
		$str = 'INSERT INTO `'.$table."` VALUES\n";
		$i = 1;
		while($drow = mysqli_fetch_assoc($dres))
		{	$str .= '(';
			foreach ($drow as $field => $value) 
			{	if (is_null($value)) 
				{ 
					$str .= 'NULL,';
				}
				else
				{	$str.= "'".mescape($value)."',";
				}
			}
			$str = rtrim($str, ',');

			if ($i % 200 == 0 && $i < $sizeof) 
			{ $str .= ");\nINSERT INTO `".$table."` VALUES\n";
			} elseif ($i < $sizeof) 
			{ $str .= "),\n";
			} else 
			{ $str.= ");\n";
			}

			if ($outputmode == "zip")
			   $zipstr .= $str;
			else
			   fwrite($fp, $str);
		    $str = "";
//		   if($i > 5)  {  fclose($fp);  die("WWoWW");  }
			++$i;
		}
	 }
   }
   if ($outputmode == "zip")
   { $zip->addFromString('backup.sql',$zipstr);
	 $zip->close();
   }
   else
	 fclose($fp);
 
   if ($outputmode == "zip")
	   $type = "zip";
   elseif($outputmode == "gz")
	   $type = "gzip";
   elseif($outputmode == "sql")
	   $type = "sql";
   else
	   $type = "octet-stream";
   
   $res = dbquery("SELECT * FROM "._DB_PREFIX_."shop_url");
   $row = mysqli_fetch_assoc($res);
   $filename = "backup_".mynamestrip($row['domain'])."_".mynamestrip($row['physical_uri'])."_".time();
   
   if(!isset($_POST['storelocal']))
   { header('Content-Description: File Transfer');
     header('Content-Type: application/'.$type);
     header('Content-Disposition: attachment; filename='.$filename.'.'.$outputmode);
     header('Expires: 0');
     header('Cache-Control: must-revalidate');
     header('Pragma: public');
     readfile($backupfile);
     unlink(realpath($backupfile));
   }
 }
else if($input['subject'] == "bkuptable")
 { /* the following was borrowed from Prestashop 1.6.1.24 (function add() in /classes/PrestaShopBackup.php) */
   if(!isset($_POST['separatefiles']))
   { $outputmode = $_POST['outputmode'];
     if(($outputmode != 'zip') && ($outputmode != 'sql')) 
	   $outputmode = 'bz2';
 
     $dbtables = $input['dbtables'];
     $size = sizeof($dbtables);
     for($i=0; $i< $size; $i++)
	   $dbtables[$i] = preg_replace('/[^a-z0-9_\-]/','', $dbtables[$i]);

      /* use Prestools tmp directory */
	  if(!file_exists("tmp") || !is_dir("tmp"))
	    if(!mkdir('tmp'))
		  colordie("error creating tmp directory!");
	  
      $backupfile = "tmp/tmp".time();
      $zipstr = "";
	  if ($outputmode == "bz2") 
	    $fp = @bzopen($backupfile, 'w');
	  elseif ($outputmode == "gz") 
	    $fp = @gzopen($backupfile, 'w');
	  elseif ($outputmode == "zip") 
	  { $zip = new ZipArchive();
	    if ($zip->open($backupfile, ZipArchive::CREATE)!==TRUE) 
          colordie("cannot open <$backupfile>\n");
	  } else
	    $fp = @fopen($backupfile, 'w');

     if (($outputmode != "zip") && ($fp === false))
	   colordie("Error creating file ");
   
     $shopname = get_configuration_value('PS_SHOP_NAME');
     $str = '/* Backup for '.$shopname."\n *  at ".date("Y-m-d H:i:s")."\n */\n";
     $str .= "\n".'SET NAMES \'utf8\';';
     $str .= "\n".'SET FOREIGN_KEY_CHECKS = 0;';
     $str .= "\n".'SET SESSION sql_mode = \'\';'."\n\n";
   
     if ($outputmode == "zip")
	   $zipstr .= $str;
     else
	   fwrite($fp, $str);
   
     $len = strlen(_DB_PREFIX_);
     $res = dbquery('SHOW FULL TABLES');  /* FULL provides an extra field to filter out views and system tables */
     while($row = mysqli_fetch_row($res))
     { if($row[1] != "BASE TABLE") continue;
	   $table = $row[0];

       if(!in_array($table, $dbtables))
		 continue;

	   $sres = dbquery('SHOW CREATE TABLE `'.$table.'`');
	   $srow = mysqli_fetch_assoc($sres);
       if ((mysqli_num_rows($sres) != 1) || !isset($srow['Table']) || !isset($srow['Create Table']))
	   { if ($outputmode == "zip")
	       $zip->close();
         else
	       fclose($fp);
	     unlink(realpath($backupfile));
	     colordie('<br>Invalid Schema for table '.$table.'!');
	   }

       $str = "\n".'/* Scheme for table '.$srow['Table']." */\n";
	   $str .= $srow['Create Table'].";\n\n";
       if ($outputmode == "zip")
	     $zipstr .= $str;
       else
	     fwrite($fp, $str);
   
       /* add table content */
       $dquery = "SELECT * FROM ".$table;
	   $dres = dbquery($dquery);
	   $sizeof = mysqli_num_rows($dres);
	   $lines = explode("\n", $srow['Create Table']);

	   if ($sizeof > 0) 
	   {	// Export the table data
		  $str = 'INSERT INTO `'.$table."` VALUES\n";
		  $i = 1;
		  while($drow = mysqli_fetch_assoc($dres))
		  {	$str .= '(';
			foreach ($drow as $field => $value) 
			{	if (is_null($value)) 
				{ 
					$str .= 'NULL,';
				}
				else
				{	$str.= "'".mescape($value)."',";
				}
			}
			$str = rtrim($str, ',');

			if ($i % 200 == 0 && $i < $sizeof) 
			{ $str .= ");\nINSERT INTO `".$table."` VALUES\n";
			} elseif ($i < $sizeof) 
			{ $str .= "),\n";
			} else 
			{ $str.= ");\n";
			}

			if ($outputmode == "zip")
			   $zipstr .= $str;
			else
			   fwrite($fp, $str);
		    $str = "";
//		   if($i > 5)  {  fclose($fp);  die("WWoWW");  }
			++$i;
		  }
	   }
	 }
     if ($outputmode == "zip")
     { $zip->addFromString('backup.sql',$zipstr);
	   $zip->close();
     }
     else
	   fclose($fp);
 
     if ($outputmode == "zip")
	   $type = "zip";
     elseif($outputmode == "gz")
	   $type = "gzip";
     elseif($outputmode == "sql")
	   $type = "sql";
     else
	   $type = "octet-stream";
   
     $res = dbquery("SELECT * FROM "._DB_PREFIX_."shop_url");
     $row = mysqli_fetch_assoc($res);
     $filename = "backup_".mynamestrip($row['domain'])."_".mynamestrip($row['physical_uri'])."_".$dbtables[0].'_'.$size.'_'.time();
   
     header('Content-Description: File Transfer');
     header('Content-Type: application/'.$type);
     header('Content-Disposition: attachment; filename='.$filename.'.'.$outputmode);
     header('Expires: 0');
     header('Cache-Control: must-revalidate');
     header('Pragma: public');
     readfile($backupfile);
     unlink(realpath($backupfile));
   }
   else if (0) // isset($_POST['separatefiles'])
   { $shopname = get_configuration_value('PS_SHOP_NAME');
     $headstr = '/* Backup for '.$shopname."\n *  at ".date("Y-m-d H:i:s")."\n */\n";
     $headstr .= "\n".'SET NAMES \'utf8\';';
     $headstr .= "\n".'SET FOREIGN_KEY_CHECKS = 0;';
     $headstr .= "\n".'SET SESSION sql_mode = \'\';'."\n\n";
	 
     $dbtables = $input['dbtables'];
     $key = rand(10000,99999);
     $size = sizeof($dbtables);
     for($i=0; $i< $size; $i++)
	   $dbtables[$i] = preg_replace('/[^a-z0-9_\-]/','', $dbtables[$i]);

      /* use Prestools tmp directory */
	  if(!file_exists("tmp") || !is_dir("tmp"))
	    if(!mkdir('tmp'))
		  colordie("error creating tmp directory!");
	  
     $len = strlen(_DB_PREFIX_);
     $res = dbquery('SHOW FULL TABLES');  /* FULL provides an extra field to filter out views and system tables */
     while($row = mysqli_fetch_row($res))
     { if($row[1] != "BASE TABLE") continue;
	   $table = $row[0];

       if(!in_array($table, $dbtables))
		 continue;

	   $sres = dbquery('SHOW CREATE TABLE `'.$table.'`');
	   $srow = mysqli_fetch_assoc($sres);
       if ((mysqli_num_rows($sres) != 1) || !isset($srow['Table']) || !isset($srow['Create Table']))
	   { colordie('<br>invalid schema for table '.$table.'!');
	   }

	   $fp = @fopen("tmp/".$table.$key.".sql", 'w');
	   fwrite($fp, $headstr);
       $str = "\n".'/* Scheme for table '.$srow['Table']." */\n";
	   $str .= $srow['Create Table'].";\n\n";
       fwrite($fp, $str);
   
       /* add table content */
       $dquery = "SELECT * FROM ".$table;
	   $dres = dbquery($dquery);
	   $sizeof = mysqli_num_rows($dres);
	   $lines = explode("\n", $srow['Create Table']);

	   if ($sizeof > 0) 
	   {	// Export the table data
		  $str = 'INSERT INTO `'.$table."` VALUES\n";
		  $i = 1;
		  while($drow = mysqli_fetch_assoc($dres))
		  {	$str .= '(';
			foreach ($drow as $field => $value) 
			{	if (is_null($value)) 
				{ 
					$str .= 'NULL,';
				}
				else
				{	$str.= "'".mescape($value)."',";
				}
			}
			$str = rtrim($str, ',');

			if ($i % 200 == 0 && $i < $sizeof) 
			{ $str .= ");\nINSERT INTO `".$table."` VALUES\n";
			} elseif ($i < $sizeof) 
			{ $str .= "),\n";
			} else 
			{ $str.= ");\n";
			}

			fwrite($fp, $str);
		    $str = "";
//		   if($i > 5)  {  fclose($fp);  die("WWoWW");  }
			++$i;
		  }
	   }
	   fclose($fp);
	 }
   }
 }
 
 else if($input['subject'] == "sqlcutter")
 { header('Content-Description: File Transfer');
   header('Content-Type: application/octet-stream');
   header('Content-Disposition: attachment; filename=clean'.time().'.sql');
   header('Expires: 0');
   header('Cache-Control: must-revalidate');
   header('Pragma: public');
   if(isset($_FILES["sqlfile"]) && isset($_FILES["sqlfile"]["tmp_name"]) && ($_FILES["sqlfile"]["tmp_name"] != ""))
	 $fp = fopen($_FILES["sqlfile"]["tmp_name"], "r");
   else
   { $filepath = $_POST["filepath"];
	 if(strtolower(substr($filepath, -4)) != ".sql")
		 colordie("Only sql files can be used for this procedure!");
	 $fp = fopen($filepath,"r");
   }
   $out = fopen('php://output', 'w');
   $extables = explode(",",str_replace(' ','',$input['extables']));
   $writing = true;
   $keys = ["CREATE TABLE","ALTER TABLE","DROP TABLE IF EXISTS","DROP TABLE","INSERT INTO"];
   $lastline = "";
   while (($line = fgets($fp, 40960)) !== false)
   { $seg = trim($line);
	 $pos=0;
	 foreach($keys AS $key)
	 { if(substr($seg,0,strlen($key)) == $key)
	   { $pos = 1+ strlen($key);
		 break;
	   }
	 }
	 if($pos > 0)
	 { if ($key != "INSERT INTO")
		 $writing = true;
	   else // "INSERT INTO "
	   { $pos = strpos($line, '(');
         $lineseg = substr($line, 12, $pos-12);
	     $pos2 = strrpos($lineseg,"`", 1);
 /* before the "(" is the word VALUES. we need to skip that */
	     $tabname = trim(str_replace('`','',substr($lineseg, 0, $pos2)));
	     if(in_array($tabname, $extables))
	     { $writing = false;
	     }
	   }
	 }
	 if($writing)
		 fwrite($out, $line);
	 $lastline = $line;
   }
   fclose($out);
 }
 else if($input['subject'] == "sqlexploder")
 { if(!file_exists('tmp/db.sql'))
	 colordie("No file with the name db.sql was found in you Prestools tmp subdirectory!");
   $fp = fopen('tmp/db.sql', 'r');
   $keys = ["CREATE TABLE","ALTER TABLE","DROP TABLE IF EXISTS","DROP TABLE","INSERT INTO"];
   $lasttabname = "";
   $fo = NULL;
   $header =[];
   while (($line = fgets($fp, 40960)) !== false)
   { $seg = trim($line);
	 $pos=0;
	 foreach($keys AS $key)
	 { if(substr($seg,0,strlen($key)) == $key)
	   { $pos = 1+ strlen($key);
		 break;
	   }
	 }
	 if($pos > 0)
	 { if ($key != "INSERT INTO")
		 $tabname = preg_replace("/[`;\(\r\n ]*/", "", substr($seg,$pos));
	   else // "INSERT INTO "
	   { $pos = strpos($line, '(');
	     if($pos)
         { $lineseg = substr($line, 12, $pos-12);
	       $pos2 = strrpos($lineseg,"`", 1);
 /* before the "(" is the word VALUES. we need to skip that */
	       $tabname = trim(str_replace('`','',substr($lineseg, 0, $pos2)));
		 }
		 else /* no fieldnames between (); "VALUES" may still be there */
		 { $lineseg = substr($line, 12);
		   preg_match('/`([^`]+)`/',$lineseg, $matches);
		   $tabname = $matches[1];
		 }
	   }
	   if($tabname != $lasttabname)
	   { if($fo) 
		   fclose($fo);
	     if(file_exists('tmp/'.$tabname.'.sql'))
	       $include_header = false;
	     else
		   $include_header = true;
	     $fo = fopen('tmp/'.$tabname.'.sql', "a");
		 if(!$fo)
		   colordie("Error creating output file ".$tabname);
	     if($include_header)
		 { foreach($header AS $head)
		     fputs($fo, $head);
		 }
		 $lasttabname = $tabname;
	   }
	 }
	 if(!$fo)
	   $header[] = $line;
	 else
	   fputs($fo, $line);
   }
   fclose($fo);
   fclose($fp);
   echo "<script>alert('Finished');</script>";
 }
 else if($input['subject'] == "dbcopy")
 { $src = preg_replace("/[^a-zA-Z0-9\_\-]+/", "", $_POST["origdb"]);
   if(in_array(strtolower($src), ["information_schema","mysql","phpmyadmin","performance_schema"])) colordie("Illegal!!!");
   $res = dbquery("SHOW DATABASES LIKE '".mescape($src)."'");
   if(mysqli_num_rows($res) == 0) 
	   colordie("Invalid source database!!!");
   $target = preg_replace("/[^a-zA-Z0-9\_\-]+/", "", $_POST["targetdb"]);
   if(in_array(strtolower($target), ["information_schema","mysql","phpmyadmin","performance_schema"])) colordie("Illegal!!!");
   $res = dbquery("SHOW DATABASES LIKE '".mescape($target)."'");
   if(mysqli_num_rows($res) == 0) 
	   colordie("Invalid target database!!!");
   $excl = preg_replace("/[^a-zA-Z0-9\_\-,]+/", "", $_POST["excluders"]);
   $excluders = [];
   if(strlen($excl) > 0)
	   $excluders = explode(",", $excl);
   $structs = preg_replace("/[^a-zA-Z0-9\_\-,]+/", "", $_POST["structurs"]);
   $structurs = [];
   if(strlen($excl) > 0)
	   $structurs = explode(",", $structs);
   
   $res = dbquery("SHOW TABLES FROM `".mescape($src)."`");
   while($row = mysqli_fetch_array($res))
   { $qry = "SELECT count(*) AS cnt FROM information_schema.tables WHERE table_schema = '".mescape($target)."' AND table_name = '".mescape($row[0])."'";
	 $rs = mysqli_query($conn, $qry);
	 $rw = mysqli_fetch_array($rs);
	 if($rw["cnt"] > 0) 
		 continue;
	 if(in_array($row[0], $excluders)) continue;
	 $rr = dbquery("create table ".$target.".`".mescape($row[0])."` like  ".$src.".`".mescape($row[0])."`");
	 if(in_array($row[0], $structurs)) continue;
	 $rt = dbquery("INSERT INTO ".$target.".`".mescape($row[0])."` SELECT * FROM  ".$src.".`".mescape($row[0])."`");
   }

 }
 else
 { echo "<script>alert('Unknown subject ".$input['subject']."');</script>!";
   die("Unknown subject ".$input['subject']);
 }
// echo "Finished!"; /* outcommented as it appeared in the output file */
 mysqli_close($conn);
 
function mynamestrip($str)
{ $str = replaceAccentedChars($str);
  $str = preg_replace("/[^a-zA-Z0-9]/", "", $str);
  return $str;
}
 
