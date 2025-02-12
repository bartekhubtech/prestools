<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");

if(isset($_GET['subject']) && ($_GET['subject'] == "repairautoincrease"))
{ $_POST['subject'] = "repairautoincrease";
  $_POST['table'] = $_GET['table'];
}
if(!isset($_POST['subject'])) colordie("No argument provided!");

 if($prestools_settings["demo_mode"])
 { echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
   return;
 }

echo '<!DOCTYPE html> 
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
</script></head><body>
<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';

$shops = array();
$res = dbquery("SELECT id_shop FROM "._DB_PREFIX_."shop ORDER BY active,id_shop");
if(mysqli_num_rows($res)==0) colordie("No active shops available!");
while($row = mysqli_fetch_array($res))
	$shops[] = $row['id_shop'];

$groups = array();
$res = dbquery("SELECT id_group FROM "._DB_PREFIX_."group ORDER BY id_group");
while($row = mysqli_fetch_array($res))
   $groups[] = $row['id_group'];
  
$languages = array();
$res = dbquery("SELECT id_lang FROM "._DB_PREFIX_."lang ORDER BY id_lang");
while($row = mysqli_fetch_array($res))
   $languages[] = $row['id_lang'];

$shoplangs = array();
$resx = dbquery("SELECT concat(id_shop,'-',id_lang) AS ident FROM "._DB_PREFIX_."lang_shop ORDER BY id_shop,id_lang");
while ($rowx=mysqli_fetch_array($resx)) 
	$shoplangs[] = $rowx["ident"];

if((($_POST['subject']) == "repairhomes") && $prestools_settings["integrity_repair_allowed"])
{ $query = "SELECT ps.id_product, ps.id_shop FROM "._DB_PREFIX_."product_shop ps";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on ps.id_category_default=c.id_category";
  $query .= " WHERE c.id_category is null ORDER BY ps.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $squery = "SELECT id_category FROM "._DB_PREFIX_."category_product";
	$squery .= " WHERE id_product=".$row['id_product'];
    $sres=dbquery($squery);
	if(mysqli_num_rows($sres) == 0) continue;
	$srow=mysqli_fetch_array($sres);
	$tquery = "UPDATE "._DB_PREFIX_."product_shop SET id_category_default='".$srow['id_category']."', date_upd=NOW(), indexed=0";
	$tquery .= " WHERE id_product=".$row['id_product']." AND id_shop=".$row['id_shop'];
    $tres=dbquery($tquery);
	if(!$tres) echo "Error xyz";
  }
}

if((($_POST['subject']) == "repairallhomes") && $prestools_settings["integrity_repair_allowed"])
{ $query = "SELECT ps.id_product, ps.id_shop FROM "._DB_PREFIX_."product_shop ps";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category_product cp ON ps.id_product=cp.id_product AND ps.id_category_default=cp.id_category";
  $query .= " WHERE cp.id_category is null ORDER BY ps.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $squery = "SELECT id_category FROM "._DB_PREFIX_."category_product";
	$squery .= " WHERE id_product=".$row['id_product'];
    $sres=dbquery($squery);
	if(mysqli_num_rows($sres) == 0) continue;
	$srow=mysqli_fetch_array($sres);
	$tquery = "UPDATE "._DB_PREFIX_."product_shop SET id_category_default='".$srow['id_category']."', date_upd=NOW(), indexed=0";
	$tquery .= " WHERE id_product=".$row['id_product']." AND id_shop=".$row['id_shop'];
    $tres=dbquery($tquery);
	if(!$tres) echo "Error axyz";
  }
}

if((($_POST['subject']) == "productrepair") && $prestools_settings["integrity_repair_allowed"])
{ if(!isset($_POST['products']) || ($_POST['products']=="")) colordie("No products provided!");
  else $productz = preg_replace('/[^0-9,]/','',$_POST['products']);
  $productz = preg_replace('/,$/','',$productz);
  $productz = preg_replace('/^,/','',$productz); 
  $productz = preg_replace('/,,+/',',',$productz);
  
  if (version_compare(_PS_VERSION_ , "1.7.1.0", ">="))
	  $redirect_value = "id_type_redirected";
  else
	  $redirect_value = "id_product_redirected";
  
  // Products in "._DB_PREFIX_."product that are not in "._DB_PREFIX_."product_shop
  $query = "SELECT p.id_product, p.id_shop_default FROM "._DB_PREFIX_."product p";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on p.id_product=ps.id_product";
  $query .= " WHERE ps.id_shop is null AND p.id_product IN (".$productz.") ORDER BY p.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { if(in_array($row["id_shop_default"],$shops))
		$shop = $row["id_shop_default"];
    else 
		$shop = $shops[0];
    $iquery = "INSERT INTO "._DB_PREFIX_."product_shop";
    $iquery .= "            (id_product,id_shop,id_category_default,id_tax_rules_group, on_sale,online_only,ecotax,minimal_quantity,price,wholesale_price, unity,unit_price_ratio,additional_shipping_cost,customizable, uploadable_files,text_fields, active,redirect_type,".$redirect_value.", available_for_order,available_date,`condition`,show_price,indexed, visibility,cache_default_attribute,advanced_stock_management,date_add, date_upd,pack_stock_type)";
    $iquery .= " SELECT id_product,'".$shop."',id_category_default,id_tax_rules_group, on_sale,online_only,ecotax,minimal_quantity,price,wholesale_price, unity,unit_price_ratio,additional_shipping_cost,customizable, uploadable_files,text_fields, active,redirect_type,".$redirect_value.", available_for_order,available_date,`condition`,show_price,0, visibility,cache_default_attribute,advanced_stock_management,date_add, NOW(),pack_stock_type";
    $iquery .= " FROM "._DB_PREFIX_."product WHERE id_product=".$row["id_product"];
    $ires=dbquery($iquery);
  }
  
// Products in "._DB_PREFIX_."product that are not in "._DB_PREFIX_."product_lang
  $dummyctr = 0;
  $query = "SELECT id_lang FROM "._DB_PREFIX_."lang ORDER BY id_lang";
  $res = dbquery($query);
  $languages = array();
  while($row = mysqli_fetch_array($res))
	$languages[] = $row['id_lang'];

  foreach($languages AS $id_lang)
  { foreach($shops AS $id_shop)
    { if(!in_array($id_shop."-".$id_lang, $shoplangs)) continue;
      $query = "SELECT p.id_product FROM "._DB_PREFIX_."product p";
      $query .= " INNER JOIN "._DB_PREFIX_."product_shop ps on p.id_product=ps.id_product AND ps.id_shop=".$id_shop;
      $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_lang pl on p.id_product=pl.id_product AND pl.id_shop=ps.id_shop AND pl.id_lang=".$id_lang;
      $query .= " WHERE pl.id_shop is null AND p.id_product IN (".$productz.") ORDER BY p.id_product";
      $res=dbquery($query);
      while ($row=mysqli_fetch_array($res)) 
      { /* first check if the same language is in a different shop, then any language. if not fill in dummy data */
		$squery = "SELECT * FROM "._DB_PREFIX_."product_lang WHERE id_product=".$row["id_product"]." AND id_lang=".$id_lang." LIMIT 1";
        $sres=dbquery($squery);
		if(mysqli_num_rows($sres)==0) /* if there is another language that we can copy */
		{ $squery = "SELECT * FROM "._DB_PREFIX_."product_lang WHERE id_product=".$row["id_product"]." LIMIT 1";
          $sres=dbquery($squery);
		}
		if(mysqli_num_rows($sres)>0) /* if there is another language that we can copy */
		{ $srow = mysqli_fetch_array($sres);
		  $iquery = "INSERT INTO "._DB_PREFIX_."product_lang";
		  $iquery .= " (id_product,id_shop,id_lang,description,description_short,link_rewrite,meta_description,meta_keywords,meta_title,name,available_now,available_later)";
		  $iquery .= " SELECT id_product,'".$id_shop."','".$id_lang."',description,description_short,link_rewrite,meta_description,meta_keywords,meta_title,name,available_now,available_later";
		  $iquery .= " FROM "._DB_PREFIX_."product_lang";
		  $iquery .= " WHERE id_product=".$row["id_product"]." AND id_lang=".$srow["id_lang"]." AND id_shop=".$srow["id_shop"];
          $ires=dbquery($iquery);
		}
		else
		{ /* try first if there is a legend */
		  $squery = "SELECT legend AS name FROM "._DB_PREFIX_."image i";
		  $squery .= " INNER JOIN "._DB_PREFIX_."image_lang il on i.id_image=il.id_image";
		  $squery .= " WHERE TRIM(legend)!='' AND i.id_product=".$row["id_product"];
		  $sres=dbquery($squery);
		  if(mysqli_num_rows($sres)>0)
		  { $srow = mysqli_fetch_array($sres);
			$dummyname = $srow["name"];
		  }
		  else
		    $dummyname = "dummy".$dummyctr++;
		  $iquery = "Insert into "._DB_PREFIX_."product_lang";
		  $iquery .= " (id_product,id_shop,id_lang,description,description_short,link_rewrite,meta_description,meta_keywords,meta_title,name,available_now,available_later)";
		  $iquery .= " VALUES (".$row["id_product"].",".$id_shop.",".$id_lang.",'','','".$dummyname."','','','','".$dummyname."','','')";
          $ires=dbquery($iquery);			
		}
		$uquery = "UPDATE "._DB_PREFIX_."product_shop SET";
		$uquery .= " date_upd=NOW(), indexed=0";
		$uquery .= " WHERE id_product=".$row["id_product"]." AND id_shop=".$id_shop;
        $ures=dbquery($uquery);
      }
	}
  }
  
  // Products in "._DB_PREFIX_."product_shop that are not in "._DB_PREFIX_."product
  $query = "SELECT DISTINCT ps.id_product,ps.id_shop FROM "._DB_PREFIX_."product_shop ps";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=ps.id_product";
  $query .= " WHERE p.id_shop_default is null AND ps.id_product IN (".$productz.") ORDER BY ps.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $iquery = "INSERT INTO "._DB_PREFIX_."product";
    $iquery .= "       (id_product,id_category_default,id_tax_rules_group, on_sale,online_only,ecotax,minimal_quantity,price,wholesale_price, unity,unit_price_ratio,additional_shipping_cost,customizable, uploadable_files,text_fields, active,redirect_type,".$redirect_value.", available_for_order,available_date,`condition`,show_price,indexed, visibility,cache_default_attribute,advanced_stock_management,date_add, date_upd,pack_stock_type,id_shop_default,reference,supplier_reference,location,width,height,depth,weight,out_of_stock,quantity_discount,cache_is_pack,cache_has_attachments,is_virtual,id_supplier,id_manufacturer,ean13,upc)";
    $iquery .= " SELECT id_product,id_category_default,id_tax_rules_group, on_sale,online_only,ecotax,minimal_quantity,price,wholesale_price, unity,unit_price_ratio,additional_shipping_cost,customizable, uploadable_files,text_fields, active,redirect_type,".$redirect_value.", available_for_order,available_date,`condition`,show_price,indexed, visibility,cache_default_attribute,advanced_stock_management,date_add, date_upd,pack_stock_type,".$row["id_shop"].",'',		'',				'',		0,		0,		0,	0,		2,			0,					0,			0,						0,			0,			0,			0,		0";
    $iquery .= " FROM "._DB_PREFIX_."product_shop WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
    $ires=dbquery($iquery);
	$uquery = "UPDATE "._DB_PREFIX_."product_shop SET";
	$uquery .= " date_upd=NOW(), indexed=0";
	$uquery .= " WHERE id_product=".$row["id_product"];
	$ures=dbquery($uquery);
  }
  
  //echo "<p>Products in "._DB_PREFIX_."product_lang that are not in the connected "._DB_PREFIX_."product_shop: ";
  $query = "SELECT pl.id_product,pl.id_shop FROM "._DB_PREFIX_."product_lang pl";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on ps.id_shop=pl.id_shop AND ps.id_product=pl.id_product";
  $query .= " WHERE ps.id_shop is null AND pl.id_product IN (".$productz.") GROUP BY pl.id_product,pl.id_shop ORDER BY pl.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $resx = dbquery("SELECT id_shop FROM "._DB_PREFIX_."product_shop WHERE id_product=".$row["id_product"]);
    $rowx = mysqli_fetch_array($resx);
    $iquery = "INSERT INTO "._DB_PREFIX_."product_shop";
    $iquery .= "                     (id_product,id_shop,id_category_default,id_tax_rules_group, on_sale,online_only,ecotax,minimal_quantity,price,wholesale_price, unity,unit_price_ratio,additional_shipping_cost,customizable, uploadable_files,text_fields, active,redirect_type,".$redirect_value.", available_for_order,available_date,`condition`,show_price,indexed, visibility,cache_default_attribute,advanced_stock_management,date_add, date_upd,pack_stock_type)";
    $iquery .= " SELECT id_product,'".$row["id_shop"]."',id_category_default,id_tax_rules_group, on_sale,online_only,ecotax,minimal_quantity,price,wholesale_price, unity,unit_price_ratio,additional_shipping_cost,customizable, uploadable_files,text_fields, active,redirect_type,".$redirect_value.", available_for_order,available_date,`condition`,show_price,0, visibility,cache_default_attribute,advanced_stock_management,date_add, NOW(),pack_stock_type";
    $iquery .= " FROM "._DB_PREFIX_."product_shop WHERE id_product=".$row["id_product"]." AND id_shop=".$rowx["id_shop"];
    $ires=dbquery($iquery);
  }

  // echo "<p>Products with an empty name: ";
  $query = "SELECT * FROM "._DB_PREFIX_."product_lang";
  $query .= " WHERE TRIM(name)='' AND id_product IN (".$productz.") ORDER BY id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { /* first mark the product_shop as updated */
	$uquery = "UPDATE "._DB_PREFIX_."product_shop SET date_upd=NOW(), indexed=0";
    $uquery .= " WHERE id_shop=".$row["id_shop"]." AND id_product=".$row["id_product"];
    $ures=dbquery($uquery);
    /* first check if the same language is in a different shop, then link_rewrite, then legend, then any language. if not fill in dummy data */
	$squery = "SELECT * FROM "._DB_PREFIX_."product_lang WHERE id_product=".$row["id_product"]." AND id_lang=".$id_lang." AND TRIM(name)!='' LIMIT 1";
    $sres=dbquery($squery);
	if(mysqli_num_rows($sres)==0) /* if there is another language that we can copy */
	{ if($row["link_rewrite"] != "")
	  { $uquery = "UPDATE "._DB_PREFIX_."product_lang SET name='".mescape(str_replace('_',' ',$row['link_rewrite']))."'";
        $uquery .= " WHERE id_lang=".$row["id_lang"]." AND id_shop=".$row["id_shop"]." AND id_product=".$row["id_product"];
        $ures=dbquery($uquery);
	    continue;
	  }
	}
	/* check if there is an image legend */
    if(mysqli_num_rows($sres)==0) 
    { $squery = "SELeCT legend AS name FROM "._DB_PREFIX_."image i";
      $squery .= " INNER JOIN "._DB_PREFIX_."image_lang il on i.id_image=il.id_image AND il.id_lang=".$row["id_lang"];
      $squery .= " WHERE TRIM(legend)!='' AND i.id_product=".$row["id_product"];
      $sres=dbquery($squery);
    }
	/* see if there is another language that we can copy */
	if(mysqli_num_rows($sres)==0)
	{ $squery = "SELECT name, description, description_short FROM "._DB_PREFIX_."product_lang";
      $squery .= " WHERE TRIM(name)!='' AND id_product=".$row["id_product"];
      $sres=dbquery($squery);
	}
	/* if anything found insert the name */
    if(mysqli_num_rows($sres)!=0)
    { $srow = mysqli_fetch_assoc($sres);
      $uquery = "UPDATE "._DB_PREFIX_."product_lang SET name='".mescape($srow["name"])."'";
	  $uquery .= ", link_rewrite='".str2url($srow["name"])."'";
	  if(($row["description"]=="") && ($row["description_short"]==""))
	  { if(isset($srow["description"]) && $srow["description"] != "") 
		  $uquery .= ", description='".mescape($srow["description"])."'";
	    if(isset($srow["description_short"]) && $srow["description_short"] != "") 
		  $uquery .= ", description_short='".mescape($srow["description_short"])."'";
	  }
      $uquery .= " WHERE id_lang=".$row["id_lang"]." AND id_shop=".$row["id_shop"]." AND id_product=".$row["id_product"];
       $ures=dbquery($uquery);
    }
    else
    { $srow = mysqli_fetch_assoc($sres);
      $uquery = "UPDATE "._DB_PREFIX_."product_lang SET name='Dummy".$dummyctr++."'";
      $uquery .= " WHERE id_lang=".$row["id_lang"]." AND id_shop=".$row["id_shop"]." AND id_product=".$row["id_product"];
      $ures=dbquery($uquery);
    } 
  }
  
  // echo "<p>Products with an empty link_rewrite: ";
  $query = "SELECT id_product,name,id_lang,id_shop FROM "._DB_PREFIX_."product_lang";
  $query .= " WHERE TRIM(link_rewrite)='' AND id_product IN (".$productz.") ORDER BY id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $newname = str2url($row["name"]);
    $uquery = "UPDATE "._DB_PREFIX_."product_lang SET link_rewrite='".mysqli_real_escape_string($conn,$newname)."'";
	$uquery .= " WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"]." AND id_lang=".$row["id_lang"];
	$uquery .= " AND id_product IN (".$productz.")";
	$ures = dbquery($uquery);
  }
  
  // echo "<br>Products where the friendly url generation failed in at least one language/shop combination: ";
  $query = "SELECT name,id_product,id_shop,id_lang FROM "._DB_PREFIX_."product_lang";
  $query .= " WHERE link_rewrite='friendly-url-autogeneration-failed' AND id_product IN (".$productz.") ORDER BY id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { /* first mark the product_shop as updated */
	$uquery = "UPDATE "._DB_PREFIX_."product_shop SET date_upd=NOW(), indexed=0";
    $uquery .= " WHERE id_shop=".$row["id_shop"]." AND id_product=".$row["id_product"];
    $ures=dbquery($uquery);
	$newname = str2url($row["name"]);
    $uquery = "UPDATE "._DB_PREFIX_."product_lang SET link_rewrite='".mysqli_real_escape_string($conn,$newname)."'";
	$uquery .= " WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"]." AND id_lang=".$row["id_lang"];
	$uquery .= " AND id_product IN (".$productz.")";
	$ures = dbquery($uquery);
  }
 
  //echo "<p>Products in ps_product that are not in ps_category_product:";
  $query = "SELECT DISTINCT p.id_product, p.id_category_default FROM "._DB_PREFIX_."product p";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category_product cp on p.id_product=cp.id_product";
  $query .= " WHERE cp.id_product is null AND p.id_product IN (".$productz.") ORDER BY p.id_product";
  $res=dbquery($query);
  if(mysqli_num_rows($res) != 0)
  { $squery = "SELECT s.id_category FROM "._DB_PREFIX_."shop s";
    $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on s.id_category=c.id_category";
    $squery .= " WHERE c.id_category is NOT null ORDER BY s.active DESC LIMIT 1";
    $sres=dbquery($squery);
    if(mysqli_num_rows($sres) == 0) colordie("No shop found with a valid home category");
    $srow=mysqli_fetch_assoc($sres);
    $homecat = $srow["id_category"];
  }
  while ($row=mysqli_fetch_array($res)) 
  { $squery = "SELECT * FROM "._DB_PREFIX_."category";
    $squery .= " WHERE id_category = '".$row["id_category_default"]."'";
    $sres=dbquery($squery);
    if(mysqli_num_rows($sres) == 0) 
	  $newcat = $homecat;
    else
  	  $newcat = $row["id_category_default"];
    $position = rand(0,100);
    $iquery = "Insert into "._DB_PREFIX_."category_product";
    $iquery .= " (id_category,id_product,position)";
    $iquery .= " VALUES ('".$newcat."','".$row["id_product"]."','".$position."')";
    $ires=dbquery($iquery);
	
	$uquery = "UPDATE "._DB_PREFIX_."product_shop SET";
	$uquery .= " date_upd=NOW(), indexed=0, id_category_default=".$newcat;
	$uquery .= " WHERE id_product=".$row["id_product"];
	$ures=dbquery($uquery);
	
	$uquery = "UPDATE "._DB_PREFIX_."product SET";
	$uquery .= " date_upd=NOW(), indexed=0, id_category_default=".$newcat;
	$uquery .= " WHERE id_product=".$row["id_product"];
	$ures=dbquery($uquery);
  }
  
  // Products in ps_category_product that are not in ps_product
  $query = "SELECT DISTINCT cp.id_product FROM "._DB_PREFIX_."category_product cp";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=cp.id_product";
  $query .= " WHERE p.id_product is null AND cp.id_product IN (".$productz.") ORDER BY cp.id_product ";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $resx = dbquery("DELETE FROM "._DB_PREFIX_."category_product WHERE id_product=".$row["id_product"]);
  }
  
// echo "<br>Images from which the product id is not in "._DB_PREFIX_."product: ";
// This option is not active and refers to the similar option under images
/*
$query = "SELECT DISTINCT i.id_product,id_image FROM "._DB_PREFIX_."image i";
$query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=i.id_product";
$query .= " WHERE p.id_shop_default is null AND i.id_product IN (".$productz.") ORDER BY i.id_product";
$res=dbquery($query);
$backupdir = $triplepath.'img/archive';
if(!is_dir($backupdir) && !mkdir($backupdir))
  $backupdir = $triplepath.'img/tmp';
$x=0;
while ($row=mysqli_fetch_array($res)) 
{ dbquery("DELETE FROM "._DB_PREFIX_."image_shop WHERE id_image=".$row["id_image"]);
  dbquery("DELETE FROM "._DB_PREFIX_."image_lang WHERE id_image=".$row["id_image"]);
  dbquery("DELETE FROM "._DB_PREFIX_."image WHERE id_image=".$row["id_image"]." AND id_product=".$row["id_product"]);
  // now deleting the image itself 
  $hasdirs = false; // when a directory has subdirectories we cannot delete it 
  $id_image = $row["id_image"];
  $ipath =  $triplepath.'img/p'.getpath($id_image)."/";
  if(!is_dir($ipath)) continue; // image was already missing: do nothing 
  $files = scandir($ipath);
  foreach ($files as $file)
  { if (($file == ".") || ($file == "..")) continue;
    if (is_dir($ipath.$file)) 
	{ $hasdirs = true;
	  continue;		
	}
	if (preg_match('/^[0-9]+\.[a-zA-Z]+$/', $file)) // the main image: move to \img\tmp 
	{ rename($ipath.$file, $backupdir.'/'.$file);
	  continue;
	}
	unlink($ipath.$file); // delete all other files - including index.php 
  }
  if(!$hasdirs)
    rmdir($ipath);
}
*/
  
  if(version_compare(_PS_VERSION_ , "1.7", ">="))
  { $query = "UPDATE "._DB_PREFIX_."product SET state = 1 WHERE id_product IN (".$productz.")";
    $res=dbquery($query);
  }
  
  /* some product problems still need a fix */
}

if((($_POST['subject']) == "charsetcollation") && $prestools_settings["integrity_repair_allowed"])
{ $systemtables = array("access","accessory","address","address_format","alias","attachment","attachment_lang","attribute","attribute_group","attribute_group_lang","attribute_group_shop","attribute_impact","attribute_lang","attribute_shop","authorization_role","carrier","carrier_group","carrier_lang","carrier_shop","carrier_tax_rules_group_shop","carrier_zone","cart","cart_cart_rule","cart_product","cart_rule","cart_rule_carrier","cart_rule_combination","cart_rule_country","cart_rule_group","cart_rule_lang","cart_rule_product_rule","cart_rule_product_rule_group","cart_rule_product_rule_value","cart_rule_shop","category","category_group","category_lang","category_product","category_shop","cms","cms_category","cms_category_lang","cms_category_shop","cms_lang","cms_role","cms_role_lang","cms_shop","configuration","configuration_kpi","configuration_kpi_lang","configuration_lang","connections","connections_page","connections_source","contact","contact_lang","contact_shop","country","country_lang","country_shop","currency","currency_lang","currency_shop","customer","customer_group","customer_message","customer_message_sync_imap","customer_session","customer_thread","customization","customization_field","customization_field_lang","customized_data","date_range","delivery","employee","employee_session","employee_shop","feature","feature_lang","feature_product","feature_shop","feature_value","feature_value_lang","gender","gender_lang","group","group_lang","group_reduction","group_shop","guest","hook","hook_alias","hook_module","hook_module_exceptions","image","image_lang","image_shop","image_type","import_match","lang","lang_shop","log","mail","manufacturer","manufacturer_lang","manufacturer_shop","memcached_servers","message","message_readed","meta","meta_lang","module","module_access","module_country","module_currency","module_group","module_preference","module_shop","operating_system","orders","order_carrier","order_cart_rule","order_detail","order_detail_tax","order_history","order_invoice","order_invoice_payment","order_invoice_tax","order_message","order_message_lang","order_payment","order_return","order_return_detail","order_return_state","order_return_state_lang","order_slip","order_slip_detail","order_slip_detail_tax","order_state","order_state_lang","pack","page","pagenotfound","page_type","page_viewed","product","product_attachment","product_attribute","product_attribute_combination","product_attribute_image","product_attribute_shop","product_carrier","product_country_tax","product_download","product_group_reduction_cache","product_lang","product_sale","product_shop","product_supplier","product_tag","profile","profile_lang","quick_access","quick_access_lang","range_price","range_weight","referrer","referrer_cache","referrer_shop","request_sql","required_field","risk","risk_lang","search_engine","search_index","search_word",
"shop","shop_group","shop_url","smarty_cache","smarty_last_flush","smarty_lazy_cache","specific_price","specific_price_priority","specific_price_rule","specific_price_rule_condition","specific_price_rule_condition_group","state","stock","stock_available","stock_mvt","stock_mvt_reason","stock_mvt_reason_lang","store","store_lang","store_shop","supplier","supplier_lang","supplier_shop","supply_order","supply_order_detail","supply_order_history","supply_order_receipt_history","supply_order_state","supply_order_state_lang","tab","tab_lang","tab_module_preference","tag","tag_count","tax","tax_lang","tax_rule","tax_rules_group","tax_rules_group_shop","timezone","warehouse","warehouse_carrier","warehouse_product_location","warehouse_shop","webservice_account","webservice_account_shop","webservice_permission","web_browser","zone","zone_shop");
  if(_PS_VERSION_ >= "1.7.0.0")
	$systemtables[] = "module_carrier";

  if((defined('_TB_VERSION_')||(version_compare(_PS_VERSION_ , "1.7.7.0", ">="))))
  { $charset = "utf8mb4";
	if(defined('_TB_VERSION_'))
		$collation = "utf8mb4_unicode_ci";
	else
	    $collation = "utf8_general_ci";  
    $ures = dbquery('ALTER DATABASE `'._DB_NAME_.'` CHARACTER SET '.$charset.' COLLATE '.$collation.';');
  }
  $len = strlen(_DB_PREFIX_);
  $res = dbquery('SHOW TABLES LIKE "'._DB_PREFIX_.'%"');
  while($row = mysqli_fetch_row($res))
  { if(!in_array(substr($row[0],$len), $systemtables)) continue;
	$query = "SELECT CCSA.CHARACTER_SET_NAME ,T.TABLE_COLLATION
FROM information_schema.`TABLES` T,information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
WHERE CCSA.COLLATION_NAME = T.TABLE_COLLATION
AND T.table_schema = '"._DB_NAME_."'
AND T.table_name = '".$row[0]."'";
    $res3 = dbquery($query);
  
    if((!$res3) || (mysqli_num_rows($res3)==0)) colordie("Error reviewing database charset.");
    $row3 = mysqli_fetch_array($res3);
	
	$diff = false;
    if(($row3["CHARACTER_SET_NAME"]!="utf8") && ((!defined('_TB_VERSION_')&&(version_compare(_PS_VERSION_ , "1.7.7.0", "<")))))
	{ $diff = true;
	  $charset = "utf8";
	  $collation = "utf8_general_ci";
	}
    else if(($row3["CHARACTER_SET_NAME"]!="utf8mb4") && ((defined('_TB_VERSION_')||(version_compare(_PS_VERSION_ , "1.7.7.0", ">=")))))
	{ $diff = true;
	  $charset = "utf8mb4";
	  if(defined('_TB_VERSION_'))
		$collation = "utf8mb4_unicode_ci";
	  else
	    $collation = "utf8mbt4_general_ci";  
	}
	else 
	  $charset = $row3["CHARACTER_SET_NAME"];
  
	if($diff) /* convert to will also change the fields */
	{ /* first prevent Mysql 1071 error (specified key was too long; max length= 767 bytes */
		  if(($row[0] == _DB_PREFIX_.'shop_url') && ($charset == "utf8mb4")) continue;
	  if(($row[0] == _DB_PREFIX_.'alias') && ($charset == "utf8mb4"))
	  { $xres = dbquery("UPDATE `"._DB_PREFIX_."alias` SET `alias` = SUBSTRING(`alias`, 1, 191)");
	    $xres = dbquery("ALTER table `"._DB_PREFIX_."alias` modify alias varchar(191)"); /* was 255 */
	  }
	  if(($row[0] == _DB_PREFIX_.'authorization_role') && ($charset == "utf8mb4"))
	  { $xres = dbquery("UPDATE `"._DB_PREFIX_."authorization_role` SET `slug` = SUBSTRING(`slug`, 1, 191)");
	    $xres = dbquery("ALTER table `"._DB_PREFIX_."authorization_role` modify slug varchar(191)"); /* was 255 */
	  }
	  if(($row[0] == _DB_PREFIX_.'module_preference') && ($charset == "utf8mb4"))
	  { $xres = dbquery("UPDATE `"._DB_PREFIX_."module_preference` SET `module` = SUBSTRING(`module`, 1, 191)");
	    $xres = dbquery("ALTER table `"._DB_PREFIX_."module_preference` modify module varchar(191)"); 
	  }  
	  if(($row[0] == _DB_PREFIX_.'smarty_lazy_cache') && ($charset == "utf8mb4"))
	  { $xres = dbquery("UPDATE `"._DB_PREFIX_."smarty_lazy_cache` SET `cache_id` = SUBSTRING(`cache_id`, 1, 191)");
	    $xres = dbquery("ALTER table `"._DB_PREFIX_."smarty_lazy_cache` modify cache_id varchar(191)"); 
	  }
	  if(($row[0] == _DB_PREFIX_.'tab_module_preference') && ($charset == "utf8mb4"))
	  { $xres = dbquery("UPDATE `"._DB_PREFIX_."tab_module_preference` SET `module` = SUBSTRING(`module`, 1, 191)");
	    $xres = dbquery("ALTER table `"._DB_PREFIX_."tab_module_preference` modify module varchar(191)"); 
	  }

	  $ures = dbquery('ALTER TABLE `'.$row[0].'` CONVERT TO CHARACTER SET '.$charset.' COLLATE '.$collation.';');
	}
  }
  echo '<script>alert("Finished!");</script>';
}

if((($_POST['subject']) == "productremove") && $prestools_settings["integrity_delete_allowed"])
{ if(!isset($_POST['delproducts'])) colordie("No products provided for deletion!");
  else $delproductz = preg_replace('/[^0-9,]/','',$_POST['delproducts']);
  $delproducts = explode(",",$delproductz);
  
  foreach($delproducts AS $delproduct)
  { if(!ctype_digit($delproduct)) colordie("Invalid product number ".htmlspecialchars($delproduct));
    delProductProperties($delproduct);
    $res = dbquery("DELETE FROM "._DB_PREFIX_."product_lang WHERE id_product='".mescape($delproduct)."'");
    $res = dbquery("DELETE FROM "._DB_PREFIX_."product_shop WHERE id_product='".mescape($delproduct)."'");
    $res = dbquery("DELETE FROM "._DB_PREFIX_."product WHERE id_product='".mescape($delproduct)."'");
  }
}

// echo '<h2>Product_attributes - 1</h2>';
if((($_POST['subject']) == "prodattrfix") && $prestools_settings["integrity_repair_allowed"])
{ if(!isset($_POST['prodattrs']) ) colordie("No products attributes provided for fixing!");
  else $prodattrs = preg_replace('/[^0-9,]/','',$_POST['prodattrs']);


  // first get a list of the ps_product_attribute fields  
  $fquery = "SELECT group_concat(COLUMN_NAME) AS columns FROM information_schema.`COLUMNS` WHERE table_schema = '"._DB_NAME_."' AND table_name = '"._DB_PREFIX_."product_attribute'";
  $fres=dbquery($fquery);
  list($columns) = mysqli_fetch_row($fres);
  $mycols = explode(",", $columns);

  $fixed = 0;
  $squery = "SELECT DISTINCT pas.id_product_attribute, pas.id_product, pas.default_on FROM "._DB_PREFIX_."product_attribute_shop pas";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pas.id_product_attribute";
  $squery .= " WHERE pa.id_product_attribute is null";
  $squery .= " AND pas.id_product_attribute IN (".$prodattrs.")";
  $squery .= " ORDER BY pas.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	if($srow["default_on"] == 1)
	{ $cquery = "UPDATE "._DB_PREFIX_."product_attribute";
	  $cquery .= " SET default_on=0 WHERE id_product=".$srow["id_product"];
	  $cres = dbquery($cquery);
	}
	$uquery = "INSERT INTO "._DB_PREFIX_."product_attribute SET id_product=".$srow["id_product"];
	foreach($mycols AS $col)
	{ if(!in_array($col, array("id_product", "reference", "supplier_reference", "location", "ean13", "upc", "quantity", "date_upd")) && isset($srow[$col]))
	    $uquery .= ", ".$col."='".mescape($srow[$col])."'";
	}
	$ures=dbquery($uquery);
	$fixed++;
  }  
  
  $squery = "SELECT DISTINCT pa.id_product_attribute, pa.id_product, pa.default_on FROM "._DB_PREFIX_."product_attribute pa";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product_attribute_shop pas ON pa.id_product_attribute=pas.id_product_attribute";
  $squery .= " WHERE pas.id_product_attribute is null";
  $squery .= " AND pa.id_product_attribute IN (".$prodattrs.")";
  $squery .= " ORDER BY pa.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	foreach($shops AS $shop)
	{ if($srow["default_on"] == 1)
	  { $cquery = "UPDATE "._DB_PREFIX_."product_attribute_shop";
	    $cquery .= " SET default_on=0 WHERE id_product=".$srow["id_product"]." AND id_shop=".$shop;
	    $cres = dbquery($cquery);
	  }
	  $uquery = "INSERT INTO "._DB_PREFIX_."product_attribute_shop SET id_shop=".$shop;
	  foreach($mycols AS $col)
	  { if(!in_array($col, array("reference", "supplier_reference", "location", "ean13", "upc","quantity", "date_upd")) && isset($srow[$col]))
	      $uquery .= ", ".$col."='".mescape($srow[$col])."'";
	  }
	  $ures=dbquery($uquery);
	}
	$fixed++;
  }  
  
  /* product_attribute and product_attribute_shop have different products: fix it only when one of them is zero */
  $squery = "SELECT pas.id_product_attribute, pa.id_product AS baseproduct, pas.id_product AS shopproduct, pas.default_on AS pasdefault_on, pa.default_on AS padefault_on, id_shop FROM "._DB_PREFIX_."product_attribute_shop pas";
  $squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pas.id_product_attribute";
  $squery .= " WHERE pa.id_product!=pas.id_product ";
  $squery .= " AND pas.id_product_attribute IN (".$prodattrs.")";
  $squery .= " AND pas.id_product=0";
  $squery .= " ORDER BY pas.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $extra = "";
    if($srow["pasdefault_on"] == 1)
	{ $cquery = "SELECT SUM(default_on) AS default_ons FROM "._DB_PREFIX_."product_attribute_shop";
	  $cquery .= " WHERE id_product=".$srow["id_product"]." AND id_shop=".$srow["id_shop"];
	  $cres = dbquery($cquery);
	  list($default_ons) = mysqli_fetch_row($cres);
	  if($default_ons > 0)
		$extra = ", default_on=0";
	}
    $uquery = "UPDATE "._DB_PREFIX_."product_attribute_shop SET id_product=".$srow["baseproduct"].$extra;
	$uquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"]." AND id_shop=".$srow["id_shop"];
	$ures=dbquery($uquery);
	$fixed++;
  }
  
  $squery = "SELECT pas.id_product_attribute, pa.id_product AS baseproduct, pas.id_product AS shopproduct, pas.default_on AS pasdefault_on, pa.default_on AS padefault_on, id_shop FROM "._DB_PREFIX_."product_attribute_shop pas";
  $squery .= " LEFT JOIN "._DB_PREFIX_."product_attribute pa ON pa.id_product_attribute=pas.id_product_attribute";
  $squery .= " WHERE pa.id_product!=pas.id_product ";
  $squery .= " AND pas.id_product_attribute IN (".$prodattrs.")";
  $squery .= " AND pa.id_product=0";
  $squery .= " ORDER BY pas.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $extra = "";
    if($srow["padefault_on"] == 1)
	{ $cquery = "SELECT SUM(default_on) AS default_ons FROM "._DB_PREFIX_."product_attribute_shop";
	  $cquery .= " WHERE id_product=".$srow["id_product"]." AND id_shop=".$srow["id_shop"];
	  $cres = dbquery($cquery);
	  list($default_ons) = mysqli_fetch_row($cres);
	  if($default_ons > 0)
		$extra = ", default_on=0";
	}
    $uquery = "UPDATE "._DB_PREFIX_."product_attribute SET id_product=".$srow["shopproduct"].$extra;
	$uquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
	$ures=dbquery($uquery);
	$fixed++;
  }
}
 
// echo '<h2>Product_attributes - 2</h2>';
if((($_POST['subject']) == "prodattrdelete") && $prestools_settings["integrity_repair_allowed"])
{ if(!isset($_POST['prodattrs']) ) colordie("No products attributes provided for deletion!");
  else $prodattrs = preg_replace('/[^0-9,]/','',$_POST['prodattrs']);

  $squery = "SELECT DISTINCT pa.id_product_attribute FROM "._DB_PREFIX_."product_attribute pa";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on pa.id_product=p.id_product";
  $squery .= " WHERE p.id_product is null";
  $squery .= " AND pa.id_product_attribute IN (".mescape($prodattrs).")";
  $squery .= " ORDER BY pa.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $dquery = "DELETE FROM "._DB_PREFIX_."product_attribute";
    $dquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
    $dres=dbquery($dquery);
	$dquery = "DELETE FROM "._DB_PREFIX_."product_attribute_shop";
    $dquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
    $dres=dbquery($dquery);
	$dquery = "DELETE FROM "._DB_PREFIX_."product_attribute_combination";
    $dquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
    $dres=dbquery($dquery);
  }
  
  $squery = "SELECT DISTINCT pas.id_product_attribute FROM "._DB_PREFIX_."product_attribute_shop pas";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on pas.id_product=p.id_product";
  $squery .= " WHERE p.id_product is null";
  $squery .= " AND pas.id_product_attribute IN (".mescape($prodattrs).")";
  $squery .= " ORDER BY pas.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $dquery = "DELETE FROM "._DB_PREFIX_."product_attribute_shop";
    $dquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
    $dres=dbquery($dquery);
	$dquery = "DELETE FROM "._DB_PREFIX_."product_attribute_combination";
    $dquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
    $dres=dbquery($dquery);
  }
  
  $squery = "SELECT DISTINCT pac.id_product_attribute FROM "._DB_PREFIX_."product_attribute_combination pac";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product_attribute pa on pac.id_product_attribute=pa.id_product_attribute";
  $squery .= " WHERE pa.id_product_attribute is null"; 
  $squery .= " AND pac.id_product_attribute IN (".mescape($prodattrs).")";
  $squery .= " ORDER BY pac.id_product_attribute";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $cquery = "SELECT id_product_attribute FROM "._DB_PREFIX_."product_attribute_shop";
    $cquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
    $cres=dbquery($cquery);
    if(mysqli_num_rows($cres) == 0)
	{ $dquery = "DELETE FROM "._DB_PREFIX_."product_attribute_combination";
      $dquery .= " WHERE id_product_attribute=".$srow["id_product_attribute"];
      $dres=dbquery($dquery);
	}
  }
}

// echo '<h2>Covers/Defaults</h2>';
if((($_POST['subject']) == "coverrepair") && $prestools_settings["integrity_repair_allowed"])
{ if(!isset($_POST['covers']) ) colordie("No covers/defaults provided for repair!");
  else $covers = preg_replace('/[^0-9,]/','',$_POST['covers']);

  $squery = "SELECT DISTINCT i.id_product FROM "._DB_PREFIX_."image i";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p ON p.id_product=i.id_product";
  $squery .= " WHERE i.id_product NOT IN (SELECT id_product FROM "._DB_PREFIX_."image";
  $squery .= " WHERE cover=1)"; 
  $squery .= " AND i.id_product IN (".mescape($covers).") AND NOT p.id_product is NULL";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $tquery = "SELECT id_image FROM "._DB_PREFIX_."image_shop";
    $tquery .= " WHERE id_product=".$srow['id_product']." AND cover=1"; 
	$tres=dbquery($tquery);
	$uquery = "UPDATE "._DB_PREFIX_."image";
    $uquery .= " SET cover=1 WHERE id_product=".$srow['id_product'];
	if(mysqli_num_rows($tres) > 0)
	{ list($id_image) = mysqli_fetch_row($tres);
	  $uquery .= " AND id_image=".$id_image;
	}
	else
	  $uquery .= " LIMIT 1";
    $ures=dbquery($uquery);
  }
  
  foreach($shops AS $shop)
  { $squery = "SELECT DISTINCT i.id_product FROM "._DB_PREFIX_."image_shop i";
    $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p ON p.id_product=i.id_product";
    $squery .= " WHERE id_shop=".$shop." AND i.id_product NOT IN (SELECT id_product FROM "._DB_PREFIX_."image_shop";
    $squery .= " WHERE cover=1) AND id_shop=".$shop.""; 
    $squery .= " AND i.id_product IN (".mescape($covers).") AND NOT p.id_product is NULL";
    $sres=dbquery($squery);
    while ($srow=mysqli_fetch_array($sres)) 
    { $tquery = "SELECT id_image FROM "._DB_PREFIX_."image";
      $tquery .= " WHERE id_product=".$srow['id_product']." AND cover=1"; 
	  $tres=dbquery($tquery);
	  $uquery = "UPDATE "._DB_PREFIX_."image_shop";
      $uquery .= " SET cover=1 WHERE id_product=".$srow['id_product']." AND id_shop=".$shop;
	  if(mysqli_num_rows($tres) > 0)
	  { list($id_image) = mysqli_fetch_row($tres);
	    $uquery .= " AND id_image=".$id_image;
	  }
	  else
	    $uquery .= " LIMIT 1";
      $ures=dbquery($uquery);
    }
  }
//================
  $squery = "SELECT DISTINCT i.id_product FROM "._DB_PREFIX_."product_attribute i";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p ON p.id_product=i.id_product";
  $squery .= " WHERE i.id_product NOT IN (SELECT id_product FROM "._DB_PREFIX_."product_attribute";
  $squery .= " WHERE default_on=1)"; 
  $squery .= " AND i.id_product IN (".mescape($covers).") AND NOT p.id_product is NULL";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $tquery = "SELECT id_product_attribute FROM "._DB_PREFIX_."product_attribute_shop";
    $tquery .= " WHERE id_product=".$srow['id_product']." AND default_on=1"; 
	$tres=dbquery($tquery);
	$uquery = "UPDATE "._DB_PREFIX_."product_attribute";
    $uquery .= " SET default_on=1 WHERE id_product=".$srow['id_product'];
	if(mysqli_num_rows($tres) > 0)
	{ list($id_product_attribute) = mysqli_fetch_row($tres);
	  $uquery .= " AND id_product_attribute=".$id_product_attribute;
	}
	else
	  $uquery .= " LIMIT 1";
    $ures=dbquery($uquery);
  }
  
  foreach($shops AS $shop)
  { $squery = "SELECT DISTINCT i.id_product FROM "._DB_PREFIX_."product_attribute_shop i";
    $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p ON p.id_product=i.id_product";
    $squery .= " WHERE id_shop=".$shop." AND i.id_product NOT IN (SELECT id_product FROM "._DB_PREFIX_."product_attribute_shop";
    $squery .= " WHERE default_on=1) AND id_shop=".$shop.""; 
    $squery .= " AND i.id_product IN (".mescape($covers).") AND NOT p.id_product is NULL";
    $sres=dbquery($squery);
    while ($srow=mysqli_fetch_array($sres)) 
    { $tquery = "SELECT id_product_attribute FROM "._DB_PREFIX_."product_attribute";
      $tquery .= " WHERE id_product=".$srow['id_product']." AND default_on=1"; 
	  $tres=dbquery($tquery);
	  $uquery = "UPDATE "._DB_PREFIX_."product_attribute_shop";
      $uquery .= " SET default_on=1 WHERE id_product=".$srow['id_product']." AND id_shop=".$shop;
	  if(mysqli_num_rows($tres) > 0)
	  { list($id_product_attribute) = mysqli_fetch_row($tres);
	    $uquery .= " AND id_product_attribute=".$id_product_attribute;
	  }
	  else
	    $uquery .= " LIMIT 1";
      $ures=dbquery($uquery);
    }
  }
}

// echo '<h2>Images</h2>'; 
if(($_POST['subject'] == "imagecleanse") && $prestools_settings["integrity_repair_allowed"])
 { $imgs = explode(",", preg_replace('/[^0-9,]/','',$_POST['problemimages']));

  $fixed = 0;
  $squery = "SELECT ims.id_image, i.id_product AS baseproduct, ims.id_product AS shopproduct, ims.cover AS imscover, i.cover AS icover, id_shop FROM "._DB_PREFIX_."image_shop ims";
  $squery .= " LEFT JOIN "._DB_PREFIX_."image i ON i.id_image=ims.id_image";
  $squery .= " WHERE i.id_product!=ims.id_product ";
  $squery .= " AND ims.id_image IN (".mescape($_POST['problemimages']).")";
  $squery .= " AND ims.id_product=0";
  $squery .= " ORDER BY ims.id_image";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  { $extra = "";
    if($srow["imscover"] == 1)
	{ $cquery = "SELECT SUM(cover) AS covers FROM "._DB_PREFIX_."image_shop";
	  $cquery .= " WHERE id_product=".$srow["id_product"]." AND id_shop=".$srow["id_shop"];
	  $cres = dbquery($cquery);
	  list($covers) = mysqli_fetch_row($cres);
	  if($covers > 0)
		$extra = ", cover=0";
	}
    $uquery = "UPDATE "._DB_PREFIX_."image_shop SET id_product=".$srow["baseproduct"].$extra;
	$uquery .= " WHERE id_image=".$srow["id_image"]." AND id_shop=".$srow["id_shop"];
	$ures=dbquery($uquery);
	$fixed++;
  }
  
  $squery = "SELECT DISTINCT ims.id_image, i.id_product AS baseproduct, ims.id_product AS shopproduct, id_shop FROM "._DB_PREFIX_."image_shop ims";
  $squery .= " LEFT JOIN "._DB_PREFIX_."image i ON i.id_image=ims.id_image";
  $squery .= " WHERE i.id_product!=ims.id_product ";
  $squery .= " AND ims.id_image IN (".mescape($_POST['problemimages']).")";
  $squery .= " AND i.id_product=0";
  $squery .= " ORDER BY ims.id_image";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	$extra = "";
    if($srow["imscover"] == 1)
	{ $cquery = "SELECT SUM(cover) AS covers FROM "._DB_PREFIX_."image";
	  $cquery .= " WHERE id_product=".$srow["id_product"];
	  $cres = dbquery($cquery);
	  list($covers) = mysqli_fetch_row($cres);
	  if($covers > 0)
		$extra = ", cover=0";
	}

	$uquery = "UPDATE "._DB_PREFIX_."image SET id_product=".$srow["shopproduct"].$extra;
	$uquery .= " WHERE id_image=".$srow["id_image"]." AND id_shop=".$srow["id_shop"];
	$ures=dbquery($uquery);
	$fixed++;
  }

   $problemimages = array();
   $squery = "SELECT DISTINCT i.id_image FROM "._DB_PREFIX_."image i";
   $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p ON p.id_product=i.id_product";
   $squery .= " WHERE p.id_product is null ORDER BY i.id_image";
   $sres=dbquery($squery);
   while ($srow=mysqli_fetch_array($sres)) 
     $problemimages[] = $srow["id_image"];

   $squery = "SELECT DISTINCT il.id_image FROM "._DB_PREFIX_."image_lang il";
   $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."image i ON i.id_image=il.id_image";
   $squery .= " WHERE i.id_image is null ORDER BY il.id_image";
   $sres=dbquery($squery);
   while ($srow=mysqli_fetch_array($sres)) 
     $problemimages[] = $srow["id_image"];

   $squery = "SELECT DISTINCT ims.id_image FROM "._DB_PREFIX_."image_shop ims";
   $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."image i ON i.id_image=ims.id_image";
   $squery .= " WHERE i.id_image is null ORDER BY ims.id_image";
   $sres=dbquery($squery);
   while ($srow=mysqli_fetch_array($sres)) 
     $problemimages[] = $srow["id_image"];
  
   $problemimages = array_unique($problemimages);
   $fixers = array_intersect($imgs,$problemimages);
   sort($fixers);
   
   $backupdir = $triplepath.'img/archive';
   if(!is_dir($backupdir) && !mkdir($backupdir))
     $backupdir = $triplepath.'img/tmp';
   
   foreach($fixers AS $id_image)
   { if(intval($id_image) == 0) continue;
     $rs = dbquery("DELETE FROM "._DB_PREFIX_."image WHERE id_image=".$id_image);
     $rs = dbquery("DELETE FROM "._DB_PREFIX_."image_lang WHERE id_image=".$id_image);
     $rs = dbquery("DELETE FROM "._DB_PREFIX_."image_shop WHERE id_image=".$id_image);
     $ipath =  $triplepath.'img/p'.getpath($id_image)."/";
	 if(!is_dir($ipath))
 	    continue; /* there is no image */
	 $files = scandir($ipath);
	 $hasdirs = false;
	 foreach ($files as $file)
	 { if (($file == ".") || ($file == "..")) continue;
	   if (is_dir($ipath.$file)) 
	   { $hasdirs = true;
		  continue;		
	   }
	   if (preg_match('/^[0-9]+\.[a-zA-Z]+$/', $file)) /* the main image: move to \img\tmp */
	   { rename($ipath.$file, $backupdir.'/'.$file);
		 continue;
	   }
	   unlink($ipath.$file); /* delete all other files - including index.php */
	 }
	 if(!$hasdirs)
	   rmdir($ipath);
   }
  echo '<script>alert("Data for '.sizeof($fixers).' images were deleted. <br>'.$fixed.' images were fixed.");</script>';
 }
 
// echo '<h2>Features</h2>'; 
if(($_POST['subject'] == "featurefix") && $prestools_settings["integrity_repair_allowed"])
{ $fixed = 0;
  $squery = "SELECT DISTINCT fp.id_product, fp.id_feature, fp.id_feature_value FROM "._DB_PREFIX_."feature_product fp";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p ON p.id_product=fp.id_product";
  $squery .= " WHERE p.id_product is null ORDER BY fp.id_product";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	$dquery = "DELETE FROM "._DB_PREFIX_."feature_product";
	$dquery .= " WHERE id_product=".$srow['id_product']." AND id_feature=".$srow['id_feature']." AND  id_feature_value=".$srow['id_feature_value'];
    $dres=dbquery($dquery);
	$fixed += mysqli_affected_rows($conn);
  }
  
  $squery = "SELECT DISTINCT fp.id_product, fp.id_feature, fp.id_feature_value FROM "._DB_PREFIX_."feature_product fp";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."feature_value fv ON fv.id_feature_value=fp.id_feature_value";
  $squery .= " WHERE fv.id_feature_value is null ORDER BY fp.id_product";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	$dquery = "DELETE FROM "._DB_PREFIX_."feature_product";
	$dquery .= " WHERE id_product=".$srow['id_product']." AND id_feature=".$srow['id_feature']." AND id_feature_value=".$srow['id_feature_value'];
    $dres=dbquery($dquery);
	$fixed += mysqli_affected_rows($conn);
  }
 
  $squery = "SELECT DISTINCT fv.id_feature_value, fv.id_feature FROM "._DB_PREFIX_."feature_value fv";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."feature f ON fv.id_feature=f.id_feature";
  $squery .= " WHERE f.id_feature is null ORDER BY fv.id_feature";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	$dquery = "DELETE FROM "._DB_PREFIX_."feature_value";
	$dquery .= " WHERE id_feature=".$srow['id_feature']." AND id_feature_value='".mescape($srow['id_feature_value'])."'";
    $dres=dbquery($dquery);
	$fixed += mysqli_affected_rows($conn);
  }
  
  $squery = "SELECT DISTINCT fv.id_feature_value, fv.id_feature FROM "._DB_PREFIX_."feature_value fv";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."feature_product fp ON fv.id_feature=fp.id_feature AND fv.id_feature_value=fp.id_feature_value";
  $squery .= " WHERE fv.custom=1 AND fp.id_product is null ORDER BY fv.id_feature";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	$dquery = "DELETE FROM "._DB_PREFIX_."feature_value";
	$dquery .= " WHERE id_feature=".$srow['id_feature']." AND id_feature_value=".$srow['id_feature_value']." AND custom=1";
    $dres=dbquery($dquery);
	$fixed += mysqli_affected_rows($conn);
  }
  
  $squery = "SELECT DISTINCT fvl.id_feature_value, fvl.value, fvl.id_lang FROM "._DB_PREFIX_."feature_value_lang fvl";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."feature_value fv ON fv.id_feature_value=fvl.id_feature_value";
  $squery .= " WHERE fv.id_feature is null ORDER BY fvl.id_feature_value";
  $sres=dbquery($squery);
  while ($srow=mysqli_fetch_array($sres)) 
  {	$dquery = "DELETE FROM "._DB_PREFIX_."feature_value_lang";
	$dquery .= " WHERE id_feature_value=".$srow['id_feature_value']." AND  value='".mescape($srow['value'])."' AND id_lang=".$srow['id_lang'];
    $dres=dbquery($dquery);
	$fixed += mysqli_affected_rows($conn);
  }
  
  echo '<script>alert("'.$fixed.' feature database rows were deleted.");</script>';
}

if((($_POST['subject']) == "categoryrepair") && $prestools_settings["integrity_repair_allowed"])
{ if(!isset($_POST['categorys'])) colordie("No category's provided!");
  else $categorys = preg_replace('/[^0-9,]/','',$_POST['categorys']);
  
/* only Thirty Bees has date_add and date_upd fields */
  if(!isset($catshopfields))
  { $catshopfields = [];
	$resx = dbquery('SHOW COLUMNS FROM '._DB_PREFIX_.'category_shop');
	while($rowx = mysqli_fetch_array($resx))
	  $catshopfields[] = $rowx[0];
  }
  
  $dummyctr = 1; /* for dummy names */
  $cats = explode(",",$categorys);
  $home_cat =0;
  foreach($cats AS $cat)
  { /* first check that this category exists in at least one of the four tables */
    $id_parent = 0;
    $res = dbquery("SELECT id_category,id_parent FROM "._DB_PREFIX_."category WHERE id_category=".$cat);
	$cnt = mysqli_num_rows($res);
    $res2 = dbquery("SELECT id_category FROM "._DB_PREFIX_."category_shop WHERE id_category=".$cat);
	$cnt2 = mysqli_num_rows($res2);
	$res3 = dbquery("SELECT id_category FROM "._DB_PREFIX_."category_lang WHERE id_category=".$cat);
	$cnt3 = mysqli_num_rows($res3);
  	$res4 = dbquery("SELECT id_category FROM "._DB_PREFIX_."category_group WHERE id_category=".$cat);
	$cnt4 = mysqli_num_rows($res4);
	if((mysqli_num_rows($res) == 0) && (mysqli_num_rows($res2) == 0) && (mysqli_num_rows($res3) == 0) && (mysqli_num_rows($res4) == 0))
	  continue;
  
    /* flag all products connected to this category for re-indexation  */
	$uquery = "UPDATE "._DB_PREFIX_."product_shop ps SET";
	$uquery .= " date_upd=NOW(), indexed=0";
 	$uquery .= " WHERE EXISTS ";
	$uquery .= "(SELECT NULL FROM "._DB_PREFIX_."category_product cp";
	$uquery .= " WHERE cp.id_category=".$cat." AND cp.id_product=ps.id_product)";
	$ures=dbquery($uquery);
	
	/* now check for products and subcategories. If not: delete */
	$res5 = dbquery("SELECT id_category FROM "._DB_PREFIX_."category_product WHERE id_category=".$cat);
    if((mysqli_num_rows($res5) == 0) && (($cnt3 == 0) || (($cnt+$cnt2+$cnt3+$cnt4)<3)))
	{ $res6 = dbquery("SELECT id_category FROM "._DB_PREFIX_."category WHERE id_parent=".$cat);
	  if(mysqli_num_rows($res6) == 0)
	  { dbquery("DELETE FROM "._DB_PREFIX_."category WHERE id_category=".$cat);
		dbquery("DELETE FROM "._DB_PREFIX_."category_shop WHERE id_category=".$cat);
		dbquery("DELETE FROM "._DB_PREFIX_."category_lang WHERE id_category=".$cat);
		dbquery("DELETE FROM "._DB_PREFIX_."category_group WHERE id_category=".$cat);
		continue;
	  }
	}

	if(mysqli_num_rows($res) == 0) /* if no record in ps_category */
	{ if($home_cat == 0)
      { $resp = dbquery("SELECT id_category FROM "._DB_PREFIX_."category_lang WHERE name='Home'");
		if(mysqli_num_rows($resp) == 0)
		{ $resp = dbquery("SELECT id_category FROM "._DB_PREFIX_."category WHERE is_root_category=1");
		}
		if(mysqli_num_rows($resp) == 0) colordie("Impossible to determine home parent");
	    $rowp=mysqli_fetch_array($resp);
		$home_cat = $rowp["id_category"];
	  }
	  $id_parent = $home_cat;
	  $id_shop_default = $shops[0];
	  $resp = dbquery("SELECT MAX(position) AS maxposition FROM "._DB_PREFIX_."category WHERE id_parent=".$home_cat);
	  $rowp=mysqli_fetch_array($resp);
	  $position = intval($rowp["maxposition"]);
	  
	  $query = "INSERT INTO "._DB_PREFIX_."category SET id_category=".$cat.", id_parent=".$id_parent.",active=1, date_upd=NOW(), date_add=NOW(),";
	  $query .= " id_shop_default=".$id_shop_default.",level_depth=2,is_root_category=0,position=".($position+1);
	  $resx = dbquery($query);
	}
	else
	{ $row=mysqli_fetch_array($res);
	  $id_parent = $row["id_parent"];
	}
	
	
	if(mysqli_num_rows($res2) == 0) /* if no record in ps_category_shop */
	{ 	
	  foreach($shops AS $shop)
	  { $query = "SELECT MAX(cs.position) AS maxposition FROM "._DB_PREFIX_."category_shop cs";
	    $query .= " LEFT JOIN "._DB_PREFIX_."category c ON c.id_category=cs.id_category";
	    $query .= " WHERE id_parent=".$id_parent." AND id_shop=".$shop;
	    $resp = dbquery($query);
		$rowp=mysqli_fetch_array($resp);
	    $position = intval($rowp["maxposition"]);
	    $query = "INSERT INTO "._DB_PREFIX_."category_shop SET id_category=".$cat.",id_shop=".$shop.",position=".($position+1);
		if(in_array("date_add", $catshopfields))
		  $query = ", date_add=NOW()";
		if(in_array("date_upd", $catshopfields))
		  $query = ", date_upd=NOW()";
		$resx = dbquery($query);
	  }
	  $shopz= $shops;
	}
	else  /* at least some shops; we won't add more */
	{ $shopz = array();
      $resp = dbquery("SELECT DISTINCT id_shop FROM "._DB_PREFIX_."category_shop WHERE id_category=".$cat);
	  while ($rowp=mysqli_fetch_array($resp))
		$shopz[] = $rowp["id_shop"];
	}

	$idents = array();
	$resx = dbquery("SELECT concat(id_shop,'-',id_lang) AS ident FROM "._DB_PREFIX_."category_lang WHERE id_category=".$cat);
	while ($rowx=mysqli_fetch_array($resx)) 
		$idents[] = $rowx["ident"];
    foreach($languages AS $id_lang)
    { foreach($shopz AS $id_shop)
      { if(!in_array($id_shop."-".$id_lang, $shoplangs)) continue;
	    if(in_array($id_shop."-".$id_lang, $idents)) continue;

	    /* first check if the same language is in a different shop, then any language. if not fill in dummy data */
		$squery = "SELECT * FROM "._DB_PREFIX_."category_lang WHERE id_category=".$cat." AND id_lang=".$id_lang." LIMIT 1";
        $sres=dbquery($squery);
		if(mysqli_num_rows($sres)==0) /* if there is another language that we can copy */
		{ $squery = "SELECT * FROM "._DB_PREFIX_."category_lang WHERE id_category=".$cat." LIMIT 1";
          $sres=dbquery($squery);
		}
		if(mysqli_num_rows($sres)>0) /* if there is another language that we can copy */
		{ $srow = mysqli_fetch_array($sres);
		  $iquery = "Insert into "._DB_PREFIX_."category_lang";
		  $iquery .= " (id_category,id_shop,id_lang,name,description,link_rewrite,meta_title,meta_keywords,meta_description)";
		  $iquery .= " select id_category,'".$id_shop."','".$id_lang."',name,description,link_rewrite,meta_title,meta_keywords,meta_description";
		  $iquery .= " FROM "._DB_PREFIX_."category_lang";
		  $iquery .= " WHERE id_category=".$cat." AND id_lang=".$srow["id_lang"]." AND id_shop=".$srow["id_shop"];
          $ires=dbquery($iquery);
		}
		else
		{ $dummyname = "dummycat".$dummyctr++;
		  $iquery = "Insert into "._DB_PREFIX_."category_lang";
		  $iquery .= " 				(id_category,		id_shop,	id_lang,			name,description,link_rewrite,meta_title,meta_keywords,meta_description)";
		  $iquery .= " VALUES (".$cat.",".$id_shop.",".$id_lang.",'".$dummyname."','','".$dummyname."','','','')";
          $ires=dbquery($iquery);			
		}
      }
	}
	
	if((mysqli_num_rows($res4) == 0) && ($id_parent != 0)) /* if no record in ps_category_group */
	{ foreach($groups AS $group)
	  { $query = "INSERT INTO "._DB_PREFIX_."category_group SET id_category=".$cat.",id_group=".$group; 
		$resx = dbquery($query);
	  }
	}
	
	//echo "<p>Category in "._DB_PREFIX_."category_lang that is not in the connected "._DB_PREFIX_."category_shop: ";
	$query = "SELECT cl.id_shop FROM "._DB_PREFIX_."category_lang cl";
	$query .= " LEFT OUTER JOIN "._DB_PREFIX_."category_shop cs on cs.id_shop=cl.id_shop AND cs.id_category=cl.id_category";
	$query .= " WHERE cs.id_shop is null AND cl.id_category=".$cat." GROUP BY cl.id_shop ORDER BY cl.id_category";
	$res=dbquery($query);
	while ($row=mysqli_fetch_array($res)) 
	{   $query = "SELECT MAX(cs.position) AS maxposition FROM "._DB_PREFIX_."category_shop cs";
	    $query .= " LEFT JOIN "._DB_PREFIX_."category c ON c.id_category=cs.id_category";
	    $query .= " WHERE id_parent=".$id_parent." AND id_shop=".$row["id_shop"];
	    $resp = dbquery($query);
		$rowp=mysqli_fetch_array($resp);
	    $position = intval($rowp["maxposition"]);
	    $query = "INSERT INTO "._DB_PREFIX_."category_shop SET id_category=".$cat.",id_shop=".$row["id_shop"].",position=".($position+1); 
		if(in_array("date_add", $catshopfields))
		  $query = ", date_add=NOW()";
		if(in_array("date_upd", $catshopfields))
		  $query = ", date_upd=NOW()";
		$resx = dbquery($query);
	}
  }
  
  // Categories in ps_category_product that are not in ps_category
  $query = "SELECT DISTINCT cp.id_category FROM "._DB_PREFIX_."category_product cp";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on c.id_category=cp.id_category";
  $query .= " WHERE c.id_category is null ORDER BY cp.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $resx = dbquery("DELETE FROM "._DB_PREFIX_."category_product WHERE id_category=".$row["id_category"]);
  }
  
  regenerateEntireNtree();
  $res = dbquery("SELECT id_category FROM "._DB_PREFIX_."category WHERE id_parent=0");
  if(mysqli_num_rows($res) != 1) colordie("More than one or no root found.");
  $row = mysqli_fetch_assoc($res);
  $rootcat = intval($row["id_category"]);
  if($rootcat == 0) colordie("Problem finding root");
  recalculateLevelDepth($rootcat);
}

if((($_POST['subject']) == "modulerepair") && $prestools_settings["integrity_repair_allowed"])
{   /* we will not delete module entries that are also in the following three tables */
	/* module_carrier ()1.7 only, module_country and module_currency */
  if(_PS_VERSION_ >= "1.7.0.0")
  { $rs1 = dbquery("SELECT GROUP_CONCAT(DISTINCT id_module) AS modules FROM "._DB_PREFIX_."module_carrier"); 
    $rw1 = mysqli_fetch_array($rs1);
  }
  else
	$rw1["modules"] = "";
  $rs2 = dbquery("SELECT GROUP_CONCAT(DISTINCT id_module) AS modules FROM "._DB_PREFIX_."module_country"); 
  $rw2 = mysqli_fetch_array($rs2);
  $rs3 = dbquery("SELECT GROUP_CONCAT(DISTINCT id_module) AS modules FROM "._DB_PREFIX_."module_currency");
  $rw3 = mysqli_fetch_array($rs3);
  
  if($rw1["modules"] == "") $r1 = array(); else $r1 = explode(",",$rw1["modules"]);
  if($rw2["modules"] == "") $r2 = array(); else $r2 = explode(",",$rw2["modules"]);
  if($rw3["modules"] == "") $r3 = array(); else $r3 = explode(",",$rw3["modules"]);
  $r = array_merge($r1,$r2,$r3);
  $r = array_unique($r);

  /* modules not on disk in the modules directory */
  $query="SELECT id_module,name,version FROM "._DB_PREFIX_."module";
  if(sizeof($r) > 0)
    $query.=" WHERE NOT id_module IN (".implode(",",$r).")";
  $query .= " GROUP BY id_module";
  $query .= " ORDER BY name";
  $res=dbquery($query);
  $cnt1 = 0;
  while($row = mysqli_fetch_array($res))
  { if(!is_dir($triplepath.'modules/'.$row['name']))
	{ $rs = dbquery("DELETE FROM "._DB_PREFIX_."module WHERE id_module=".$row["id_module"]);
	  $rs = dbquery("DELETE FROM "._DB_PREFIX_."module_shop WHERE id_module=".$row["id_module"]);
	  $rs = dbquery("DELETE FROM "._DB_PREFIX_."module_group WHERE id_module=".$row["id_module"]);
	  $rs = dbquery("DELETE FROM "._DB_PREFIX_."hook_module WHERE id_module=".$row["id_module"]);
	  $rs = dbquery("DELETE FROM "._DB_PREFIX_."hook_module_exceptions WHERE id_module=".$row["id_module"]);
	  echo "xxx".$row["name"];
	  $cnt1++;
	}
  }
  
  /* Modules in module_shop but not module table */
  $query = "SELECT ms.* FROM "._DB_PREFIX_."module_shop ms";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."module m on m.id_module=ms.id_module";
  $query .= " WHERE m.id_module is null ORDER BY ms.id_module";
  $res=dbquery($query);
  $cnt4 = mysqli_num_rows($res);
  if($cnt4 > 0)
  { while($row = mysqli_fetch_array($res))
      $rs = dbquery("DELETE FROM "._DB_PREFIX_."module_shop WHERE id_module=".$row["id_module"]);
  }
  
  /* Modules in module_group but not module table */
  $query = "SELECT mg.* FROM "._DB_PREFIX_."module_group mg";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."module m on m.id_module=mg.id_module";
  $query .= " WHERE m.id_module is null ORDER BY mg.id_module";
  $res=dbquery($query);
  $cnt5 = mysqli_num_rows($res);
  if($cnt5 > 0)
  { while($row = mysqli_fetch_array($res))
      $rs = dbquery("DELETE FROM "._DB_PREFIX_."module_group WHERE id_module=".$row["id_module"]);
  }
  
  /* module hooks not in ps_module */
  $query = "SELECT hm.* FROM "._DB_PREFIX_."hook_module hm";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."module m on m.id_module=hm.id_module";
  $query .= " WHERE m.id_module is null ORDER BY hm.id_module";
  $res=dbquery($query);
  $cnt2 = mysqli_num_rows($res);
  if($cnt2 > 0)
  { while($row = mysqli_fetch_array($res))
      $rs = dbquery("DELETE FROM "._DB_PREFIX_."hook_module WHERE id_module=".$row["id_module"]);
  }

  /* module hook exceptions not in ps_hook_module */
  $query = "SELECT hme.* FROM "._DB_PREFIX_."hook_module_exceptions hme";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."hook_module hm on hme.id_module=hm.id_module AND hme.id_hook=hm.id_hook";
  $query .= " WHERE hm.id_module is null ORDER BY hme.id_module,hme.id_hook";
  $res=dbquery($query);
  $cnt3 = mysqli_num_rows($res);
  if($cnt3 > 0)
  { while($row = mysqli_fetch_array($res))
      $rs = dbquery("DELETE FROM "._DB_PREFIX_."hook_module_exceptions WHERE id_module=".$row["id_module"]);
  }
  
   echo '<script>alert("Incomplete data for '.$cnt1.' modules, '.$cnt4.' mod_shop, '.$cnt5.' mod_group, '.$cnt2.' hooks and '.$cnt3.' exceptions were deleted");</script>';
}

if((($_POST['subject']) == "categorytreerepair") && $prestools_settings["integrity_repair_allowed"])
{ if(!isset($_POST['categorys'])) colordie("No category's provided!");
  else $categorys = preg_replace('/[^0-9,]/','',$_POST['categorys']);
  
  /* the following check is also in integrity-checks.php and shouldn't be give a result here */
  $squery = "SELECT s.id_category, s.name FROM "._DB_PREFIX_."shop s";
  $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on s.id_category=c.id_category";
  $squery .= " WHERE c.id_category is null ORDER BY s.id_shop";
  $sres=dbquery($squery);
  if(mysqli_num_rows($sres) > 0)
  { echo "<p>Shops without valid home category found: ";
    $x=0;
    while($srow=mysqli_fetch_assoc($sres))
    { if($x++ > 0) echo ", ";
      echo '<a href="#" title="'.$srow["name"].'" onclick="return false;">'.$srow["id_category"].'</a>';
    }
    die("<br>This cannot be repaired with this script.");
  }

  if(!($id_shop = get_configuration_value('PS_SHOP_DEFAULT')))
	  colordie("No default shop found!");
  $query = "SELECT s.id_category FROM "._DB_PREFIX_."shop s";
  $query .= " LEFT JOIN "._DB_PREFIX_."category c on s.id_category=c.id_category WHERE id_shop=".$id_shop;
  $res = dbquery($query);
  if(mysqli_num_rows($res) != 0)
  { $row = mysqli_fetch_assoc($res);
    $homecat = $row["id_category"];
  }
  else
  { $squery = "SELECT s.id_category FROM "._DB_PREFIX_."shop s";
    $squery .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on s.id_category=c.id_category";
    $squery .= " WHERE c.id_category is NOT null ORDER BY s.active DESC LIMIT 1";
    $sres=dbquery($squery);
    if(mysqli_num_rows($sres) == 0) colordie("No valid home category could be found!");
    $srow=mysqli_fetch_assoc($sres);
    $homecat = $srow["id_category"];
  }
  
//  echo "<p>Categories without valid parent: "; 
  $query = "SELECT DISTINCT c.id_category FROM "._DB_PREFIX_."category c";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category c2 on c.id_parent=c2.id_category";
  $query .= " WHERE c2.id_category is null AND c.id_parent!='0' AND c.id_category IN (".$categorys.") ORDER BY c.id_category";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $uquery = "UPDATE "._DB_PREFIX_."category SET id_parent='".$homecat."'";
    $uquery .= " WHERE id_category=".$row["id_category"];
    $ures=dbquery($uquery);
  }

  $res = dbquery("SELECT id_category FROM "._DB_PREFIX_."category WHERE id_parent=0");
  if(mysqli_num_rows($res) != 1) colordie("More than one or no root found.");
  $row = mysqli_fetch_assoc($res);
  $rootcat = intval($row["id_category"]);
  if($rootcat == 0) colordie("Problem finding root");

  $ctr = 0;
  while($cats = regenerateEntireNtree())
  { /* look for most subcats on lowest level */
    $thecat = $cats[0];
	$lowestlevel = 99;
	$subcatcount = 0;
    foreach($cats AS $cat)
	{ if($levels[$cat] < $lowestlevel)
	  { $lowestlevel = $levels[$cat];
		$subcatcount = sizeof($categoriesArray[$cat]['subcategories']);
		$thecat = $cat;
	  }
	  else if(($levels[$cat] == $lowestlevel) && isset($categoriesArray[$cat]) && (sizeof($categoriesArray[$cat]['subcategories']) > $subcatcount))
	  { $subcatcount = sizeof($categoriesArray[$cat]['subcategories']);
		$thecat = $cat;
	  }
	}
	$uquery = "UPDATE "._DB_PREFIX_."category SET id_parent='".$homecat."'";
	$uquery .= " WHERE id_category=".$thecat;
	$ures=dbquery($uquery);
	if($ctr++ > 10)  
		  colordie("<p>There was some problem regenerating your tree");
  }
  recalculateLevelDepth($rootcat);
}

 if(($_POST['subject'] == "inactivecategory") && $prestools_settings["integrity_repair_allowed"])
 { $cnt = 0;
   $query = "SELECT ps.id_product, ps.id_shop FROM "._DB_PREFIX_."product_shop ps";
   $query .= " LEFT OUTER JOIN ";
   $query .= "(SELECT cp.* FROM "._DB_PREFIX_."category_product cp";
   $query .= " LEFT JOIN "._DB_PREFIX_."category c on cp.id_category=c.id_category WHERE c.active=1) cx";
   $query .= " ON ps.id_product=cx.id_product";
   $query .= " WHERE cx.id_category is null AND ps.active=1 ORDER BY ps.id_product";
   $res=dbquery($query);
   while ($row=mysqli_fetch_array($res))
   { $dquery = "update "._DB_PREFIX_."product_shop SET active=0 WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
	 $dres=dbquery($dquery);
	 $cnt++;
   }
   echo '<script>alert("Data for '.$cnt.' products were made inactive");</script>';
 } 
 
 if($_POST['subject'] == "fixdefault")
 { $cnt = $icnt = $mcnt = 0;
   $query = "SELECT ps.id_product, ps.id_shop, ps.id_category_default FROM "._DB_PREFIX_."product_shop ps";
   $query .= " LEFT JOIN "._DB_PREFIX_."category c on ps.id_category_default=c.id_category";
   $query .= " LEFT JOIN "._DB_PREFIX_."category_shop cs on c.id_category=cs.id_category AND cs.id_shop=ps.id_shop";
   $query .= " WHERE (c.active=0 OR c.active is NULL OR cs.id_category is NULL) AND ps.active=1 ORDER BY ps.id_product";
   $res=dbquery($query);
   while ($row=mysqli_fetch_array($res))
   { $qquery = "SELECT cp.id_category FROM "._DB_PREFIX_."category_product cp";
     $qquery .= " LEFT JOIN "._DB_PREFIX_."category c on c.id_category=cp.id_category";
     $qquery .= " LEFT JOIN "._DB_PREFIX_."category_shop cs on c.id_category=cp.id_category";
	 $qquery .= " WHERE cp.id_product=".$row["id_product"]." AND c.active=1 AND cs.id_shop=".$row["id_shop"];
	 $qquery .= " ORDER BY level_depth DESC, cp.id_category";
	 $qres=dbquery($qquery);
	 if(mysqli_num_rows($qres) > 0)
	 { $qrow=mysqli_fetch_array($qres);
	   $uquery = "update "._DB_PREFIX_."product_shop SET id_category_default=".$qrow["id_category"].", date_upd=NOW(), indexed=0 WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
	   $ures=dbquery($uquery);
	   $cnt++;
	 }
	 else /* no active category found for this product in this shop */
	 { $cquery = "SELECT cp.id_category FROM "._DB_PREFIX_."category_product cp";
       $cquery .= " LEFT JOIN "._DB_PREFIX_."category c on c.id_category=cp.id_category";
       $cquery .= " LEFT JOIN "._DB_PREFIX_."category_shop cs on c.id_category=cp.id_category";
	   $cquery .= " WHERE cp.id_product=".$row["id_product"]." AND cs.id_shop=".$row["id_shop"];
	   $cquery .= " ORDER BY level_depth DESC, cp.id_category";
	   $cres=dbquery($cquery);
	   if(mysqli_num_rows($cres) > 0)
	   { $cats = array();
         while ($crow=mysqli_fetch_array($cres))
			 $cats[] = $crow["id_category"];
		 if(!in_array($row["id_category_default"],$cats))
		 { $dquery = "update "._DB_PREFIX_."product_shop SET id_category_default=".$cats[0].", date_upd=NOW(), indexed=0 WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
		   $dres=dbquery($dquery);
		   $mcnt++;
		 }
	   }		   
	   $dquery = "update "._DB_PREFIX_."product_shop SET active=0 WHERE id_product=".$row["id_product"]." AND id_shop=".$row["id_shop"];
	   $dres=dbquery($dquery);
	   $icnt++;
	 }
   }
   echo '<script>alert("'.$cnt.' products got an active category. '.$icnt.' products were deactivated. of the latter '.$mcnt.' were assigned to another category because the present default doesn\'t exist.");</script>';
 } 

 if((($_POST['subject']) == "repairautoincrease") && $prestools_settings["integrity_repair_allowed"])
 {  $subtables = array("address" => array("warehouse"),
	"attribute" => array("attribute_impact","attribute_lang","attribute_shop", "layered_indexable_attribute_lang_value", "layered_product_attribute","product_attribute_combination"),
	"carrier" => array("carrier_group","carrier_lang","carrier_shop","carrier_tax_rules_group_shop","carrier_zone","cart_rule_carrier","delivery","range_price","range_weight","warehouse_carrier"),
	"cart" => array("cart_cart_rule","cart_product"),
	"category" => array("category_group","category_lang","category_shop","layered_category"),
	"cms" => array("cms_lang","cms_role","cms_shop"),
	"configuration" => array("configuration_lang"),
	"connections" => array("connections_page","connections_source"),
	"contact" => array("contact_lang","contact_shop"),
	"country" => array("address","address_format","cart_rule_country","country_lang","country_shop","module_country","product_country_tax","state","store","tax_rule"),
	"currency" => array("currency_module","currency_shop"),
	"customer" => array("compare","customer_group"),
	"customization" => array("customization_field","customized_data"),
	"employee" => array("employee_shop"),
	"feature" => array("feature_lang","feature_product","feature_shop","feature_value","layered_indexable_feature","layered_indexable_feature_lang_value"),
	"feature_value" => array("feature_product","feature_value_lang","layered_indexable_feature_value_lang_value"),
	"group" => array("carrier_group","cart_rule_group","category_group","customer_group","group_lang","group_reduction","group_shop","module_group","product_group_reduction_cache"),
	"guest" => array("connections"),
	"hook" => array("hook_module","hook_module_exceptions"),
	"image" => array("image_lang","image_lang","product_attribute_image"),
	"manufacturer" => array("manufacturer_lang", "manufacturer_shop"),
	"message" => array("message_readed"),
	"meta" => array("meta_lang","theme_meta"),
	"module" => array("currency_module","hook_module","hook_module_exceptions","module_access","module_carrier","module_country","module_currency","module_group","module_shop"),
	"orders" => array("order_carrier","order_cart_rule","order_detail","order_history","order_invoice","order_invoice_payment","order_return","order_slip"),
	"page" => array("page_viewed"),
	"product" => array("attribute_impact","cart_product","category_product","compare_product","customization","customization_field","feature_product","image","image_shop","product_attachment","product_attribute","product_attribute_shop","product_carrier","product_country_tax","product_download","product_lang","product_sale","product_shop","product_supplier","product_tag","search_index","stock","stock_available")
	);

	$table = preg_replace("/[^0-9a-zA-Z\-_]/","",$_POST['table']);
	$len = strlen(_DB_PREFIX_);
	if(substr($table,0,$len) != _DB_PREFIX_)
		colordie("Illegal table prefix!");
	$rs = autoincrementfix($table);
	if($rs != '1')
	  colordie($rs);
 }
 
  if((($_POST['subject']) == "autoincrementfixall") && $prestools_settings["integrity_repair_allowed"])
  { $query = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."' 
    AND TABLE_NAME LIKE '"._DB_PREFIX_."%'";
	/* for some reason adding " and auto_increment=0" did not work. Need further research. */ 
	$res= dbquery($query);
    $len = strlen(_DB_PREFIX_);
	echo mysqli_num_rows($res)." FIELDS<br>";
	echo "Fixing";
	while($row = mysqli_fetch_row($res))
	{ $table = $row[0];
	  $rs = autoincrementfix($table);
      if($rs == '1') echo " ".$table;
	}
  }
  
  if((($_POST['subject']) == "autoincrementfixwi") && $prestools_settings["integrity_repair_allowed"])
  { $fixtables = explode(",",$_POST["aifixtables"]);
    foreach($fixtables AS $tab)
    { $tab = trim($tab);
	  if($tab == "") continue;
	  $tab = preg_replace("[^a-zA_Z0-9\-_]", "", $tab);
	  $query = "SELECT `TABLE_NAME` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."' 
      AND TABLE_NAME='".mescape($tab)."'";
	  $res= dbquery($query);
	  if(mysqli_num_rows($res) == 0) colordie("Table ".$tab." was not found!");
	  $tabname = substr($tab,strlen(_DB_PREFIX_));
      $query = "ALTER TABLE `".$tab."` ADD PRIMARY KEY (`id_".$tabname."`)";
	  $res= dbquery($query);
	  $rs = autoincrementfix($tab);
      if($rs != '1') colordie($rs);
	}
  }
  
 if((($_POST['subject']) == "delfaultyprods") && $prestools_settings["integrity_delete_allowed"])
{ $cnt=0;
  $query = "SELECT DISTINCT p.id_product FROM "._DB_PREFIX_."product p";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_shop ps on p.id_product=ps.id_product";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product_lang pl on p.id_product=pl.id_product";
  $query .= " WHERE (ps.id_product IS NULL OR pl.id_product IS NULL) ORDER BY p.id_product";
  $res=dbquery($query);
  echo mysqli_num_rows($res)." rows";
  while ($row=mysqli_fetch_array($res))
  { delProductProperties($row["id_product"]);
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product_lang WHERE id_product='".mescape($row["id_product"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product_shop WHERE id_product='".mescape($row["id_product"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product WHERE id_product='".mescape($row["id_product"])."'");
	$cnt++;
  }
  
  $query = "SELECT DISTINCT ps.id_product FROM "._DB_PREFIX_."product_shop ps";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=ps.id_product";
  $query .= " WHERE p.id_product IS NULL ORDER BY ps.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { delProductProperties($row["id_product"]);
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product_lang WHERE id_product='".mescape($row["id_product"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product_shop WHERE id_product='".mescape($row["id_product"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product WHERE id_product='".mescape($row["id_product"])."'");
	$cnt++;
  }
  
  $query = "SELECT DISTINCT pl.id_product FROM "._DB_PREFIX_."product_lang pl";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=pl.id_product";
  $query .= " WHERE p.id_product IS NULL ORDER BY pl.id_product";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { delProductProperties($row["id_product"]);
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product_lang WHERE id_product='".mescape($row["id_product"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product_shop WHERE id_product='".mescape($row["id_product"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."product WHERE id_product='".mescape($row["id_product"])."'");
	$cnt++;
  }
   echo '<script>alert("data for '.$cnt.' products were deleted");</script>';
 }

 if(($_POST['subject'] == "delfaultycats") && $prestools_settings["integrity_delete_allowed"])
 {$cnt = 0;
  $query = "SELECT DISTINCT cs.id_category FROM "._DB_PREFIX_."category_shop cs";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on c.id_category=cs.id_category";
  $query .= " WHERE c.id_category IS NULL ORDER BY cs.id_category";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $cquery = "SELECT id_product FROM "._DB_PREFIX_."category_product";
    $cquery .= " WHERE id_category=".$row["id_category"];
    $cres=dbquery($cquery);
    while ($crow=mysqli_fetch_array($cres))
	{ $aquery = "SELECT id_category FROM "._DB_PREFIX_."category_product";
      $aquery .= " WHERE id_product=".$crow["id_category"]." BY cs.id_category";
      $ares=dbquery($aquery);
	  if(mysqli_num_rows($ares) ==1) continue 2; /* don't delete the category if it is the only one for a product */
	}
	$dres = dbquery("DELETE FROM "._DB_PREFIX_."category_lang WHERE id_category='".mescape($row["id_category"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."category_shop WHERE id_category='".mescape($row["id_category"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."category WHERE id_category='".mescape($row["id_category"])."'");
	$cnt++;
  }
  
  $query = "SELECT DISTINCT cl.id_category FROM "._DB_PREFIX_."category_lang cl";
  $query .= " LEFT OUTER JOIN "._DB_PREFIX_."category c on c.id_category=cl.id_category";
  $query .= " WHERE c.id_category IS NULL ORDER BY cl.id_category";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res))
  { $cquery = "SELECT id_product FROM "._DB_PREFIX_."category_product";
    $cquery .= " WHERE id_category=".$row["id_category"];
    $cres=dbquery($cquery);
    while ($crow=mysqli_fetch_array($cres))
	{ $aquery = "SELECT id_category FROM "._DB_PREFIX_."category_product";
      $aquery .= " WHERE id_product=".$crow["id_category"]." BY cs.id_category";
      $ares=dbquery($aquery);
	  if(mysqli_num_rows($ares) ==1) continue 2; /* don't delete the category if it is the only one for a product */
	}
	$dres = dbquery("DELETE FROM "._DB_PREFIX_."category_lang WHERE id_category='".mescape($row["id_category"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."category_shop WHERE id_category='".mescape($row["id_category"])."'");
    $dres = dbquery("DELETE FROM "._DB_PREFIX_."category WHERE id_category='".mescape($row["id_category"])."'");
	$cnt++;
  }

  echo '<script>alert("Data for '.$cnt.' categories were deleted");</script>';
 }
 
 if(($_POST['subject'] == "delcomments") && $prestools_settings["integrity_delete_allowed"])
 { $res = dbquery('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA="'._DB_NAME_.'" AND TABLE_NAME="'._DB_PREFIX_."product_comment".'"');
   if(mysqli_num_rows($res) == 0) 
	 colordie("You don't have the Prestashop comments installed");
 
  $pcnt = $ccnt = 0;
  $cquery = "SELECT DISTINCT pc.id_product FROM "._DB_PREFIX_."product_comment pc";
  $cquery .= " LEFT OUTER JOIN "._DB_PREFIX_."product p on p.id_product=pc.id_product";
  $cquery .= " WHERE p.id_product IS NULL ORDER BY p.id_product";
  $cres = dbquery($cquery);
  while ($crow=mysqli_fetch_array($cres)) 
  {	$delproduct = $crow["id_product"];  
    $query = "SELECT id_product_comment FROM "._DB_PREFIX_."product_comment";
    $query .= " WHERE id_product='".mescape($delproduct)."'";
    $res = dbquery($query);
	$pcnt++;
    while ($row=mysqli_fetch_array($res)) 
    { dbquery("DELETE FROM "._DB_PREFIX_."product_comment_grade WHERE id_product_comment='".mescape($row["id_product_comment"])."'");
	  dbquery("DELETE FROM "._DB_PREFIX_."product_comment_report WHERE id_product_comment='".mescape($row["id_product_comment"])."'");
	  dbquery("DELETE FROM "._DB_PREFIX_."product_comment_usefulness WHERE id_product_comment='".mescape($row["id_product_comment"])."'");
	  $ccnt++;
    }
    $res = dbquery("DELETE FROM "._DB_PREFIX_."product_comment WHERE id_product='".mescape($delproduct)."'");
    $res = dbquery("DELETE FROM "._DB_PREFIX_."product_comment_criterion_product WHERE id_product='".mescape($delproduct)."'");
  }
   echo '<script>alert("'.$ccnt.' comments for '.$pcnt.' products were deleted");</script>';
 } 
 
 if((!$prestools_settings["integrity_repair_allowed"]) && (in_array($_POST['subject'], array("autoincrementfixall","autoincrementfixwi",
 "categoryrepair", "categorytreerepair", "charsetcollation", "coverrepair", "imagecleanse",
 "inactivecategory", "modulerepair",  "productrepair", "repairallhomes", "repairautoincrease",
 "repairhomes"))))
	echo '<script>alert("This operation cannot be done because of your security settings in settings1.php");</script>';

 if((!$prestools_settings["integrity_delete_allowed"]) && (in_array($_POST['subject'], array("delcomments","delfaultycats", "delfaultyprods","productremove"))))
	echo '<script>alert("This operation cannot be done because of your security settings in settings1.php");</script>';

 echo "<br>Finished!";
 
	
/* the following functions were based on the version of Thirty Bees 1.08 */
function regenerateEntireNtree()
{   global $ucparents, $uccategories, $categoriesArray, $levels, $verbose;
    if(!($id_shop = get_configuration_value('PS_SHOP_DEFAULT')))
	  colordie("No default shop found!");
    
	$query = 'SELECT c.`id_category`, c.`id_parent`, c.`level_depth` FROM '._DB_PREFIX_.'category c';
	$query .= ' LEFT JOIN '._DB_PREFIX_.'category_shop cs ON c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.$id_shop;
	$query .= ' ORDER BY c.`id_parent`, cs.`position` ASC';
	$res = dbquery($query);
	
	$uccategories = array();
	$levels = array();
	$categoriesArray = array();
	while($category = mysqli_fetch_array($res))
	{	$categoriesArray[$category['id_parent']]['subcategories'][] = $category['id_category'];
		$uccategories[] = $category['id_category'];
		$levels[$category['id_category']] = $category['level_depth'];
	}
	$ucparents = array_keys($categoriesArray);
	$pos = array_search('0', $ucparents);
	array_splice($ucparents, $pos,1);
	if($verbose) {echo "<br>Before: "; print_r($categoriesArray); echo "<br>"; }
	$n = 0;
	if (isset($categoriesArray[0]) && $categoriesArray[0]['subcategories']) {
		_subTree($categoriesArray, 0, $n);
	}
	if($verbose) {echo "<br>After: "; print_r($categoriesArray); echo "<br>"; }
	if((sizeof($ucparents) == 0) && (sizeof($uccategories)==0)) return false;
	else if(sizeof($ucparents) != 0) return $ucparents;
	return $uccategories;
}

/**
 * @param $categories
 * @param $idCategory
 * @param $n
 *
 * @deprecated 1.0.0
 * @throws PrestaShopException
 */
function _subTree(&$categories, $idCategory, &$n)
{   global $ucparents, $uccategories;
	$left = $n++;
	if (isset($categories[(int) $idCategory]['subcategories'])) {
		foreach ($categories[(int) $idCategory]['subcategories'] as $idSubcategory) 
		{	_subTree($categories, (int) $idSubcategory, $n);
			$pos = array_search($idSubcategory, $ucparents);
			if($pos!== false) 
				array_splice($ucparents, $pos,1);
		}
	}
	$right = (int) $n++;
	
	$pos = array_search($idCategory, $uccategories);
	if($pos!== false) 
		array_splice($uccategories, $pos,1);
	if($idCategory == 0) return;
	
	$query = 'UPDATE '._DB_PREFIX_.'category SET nleft = '.(int) $left.', nright = '.(int) $right.'
	WHERE id_category = '.(int) $idCategory.' LIMIT 1';
	$res = dbquery($query);
}

function recalculateLevelDepth($idCategory)
{
	if (!is_numeric($idCategory))
	{	colordie('id category is not numeric');
	}
	
	/* Gets all children */
	$categories = array();
	$res = dbquery('SELECT `id_category`, `id_parent` FROM '._DB_PREFIX_.'category WHERE id_parent='.intval($idCategory));
    while($crow = mysqli_fetch_assoc($res))
		$categories[] = $crow;
	
	/* Gets level_depth */
	$res = dbquery('SELECT `level_depth` FROM '._DB_PREFIX_.'category WHERE id_category='.intval($idCategory));
	$crow = mysqli_fetch_assoc($res);
	$level = $crow["level_depth"];
	
	/* Updates level_depth for all children */
	foreach ($categories as $subCategory) 
	{	$query = 'UPDATE '._DB_PREFIX_.'category SET level_depth = '.intval($level + 1).'
			WHERE id_category = '.intval($subCategory['id_category']);
		dbquery($query);
		
		/* Recursive call */
		recalculateLevelDepth($subCategory['id_category']);
	}
}

function autoincrementfix($table)
{ $tabname = substr($table, strlen(_DB_PREFIX_));
  if(in_array($tabname, array("page_cache","smarty_cache"))) 
	 return "This tables has no auto-increment";

  $squery = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='"._DB_NAME_."' AND TABLE_NAME='".mescape($table)."'";
  $sres = dbquery($squery); 
  if(mysqli_num_rows($sres) == 0)
	return "Illegal table name ".$table;
  $srow = mysqli_fetch_array($sres);
  if(intval($srow['AUTO_INCREMENT']) > 0)
	return "Table ".$table." has an auto-increment";
  $colname = "id_".$tabname;
  $iquery = "SELECT * FROM information_schema.`COLUMNS` WHERE table_schema = '"._DB_NAME_."' AND table_name = '".mescape($table)."' AND column_name='".$colname."'";  
  $ires = dbquery($iquery); 
  if(mysqli_num_rows($ires) == 0)
    return "Table ".$table." has no id column";
  $irow = mysqli_fetch_array($ires);
	
  $ires = dbquery("SHOW INDEXES FROM `".mescape($table)."`");
  if(mysqli_num_rows($ires) == 0)
  { return "table ".$table." has no indexes"; 
  }
	
  $query = "DELETE FROM `".$table."` WHERE `".$colname."`=0";
  $res = dbquery($query);

  $query = "ALTER TABLE `".$table."`";
  $query .= " MODIFY `".$colname."`"; // int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
  $query .= " ".$irow["COLUMN_TYPE"];
  if($irow["IS_NULLABLE"] == "NO")
    $query .= " NOT NULL";
  if($irow["COLUMN_DEFAULT"] != "")
	$query .= " default '".$irow["COLUMN_DEFAULT"]."'";
  $query .= " AUTO_INCREMENT";
  $res = dbquery($query); 
  return '1';
}

echo "<script>alert('Finished');</script>";