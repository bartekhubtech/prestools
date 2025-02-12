<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
$input = $_GET;
if(isset($input['id_shop']) && (intval($input['id_shop'])!=0)) $id_shop = intval($input['id_shop']);
else $id_shop = get_configuration_value('PS_SHOP_DEFAULT');
if(isset($input['id_lang']) && (intval($input['id_lang'])!=0)) $id_lang = intval($input['id_lang']);
else $id_lang = get_configuration_value('PS_LANG_DEFAULT');
$def_lang = get_configuration_value('PS_LANG_DEFAULT');
if(!isset($input['id_product'])) $id_product="";
else $id_product = intval($input["id_product"]);
if(!isset($input["textshow"]) || (!in_array($input["textshow"], array("allshops","alllangs","langshops","shoplangs"))))
	$textshow = "activeonly";
else
	$textshow = $input["textshow"];
$startdate = $enddate = "0000-00-00";


   /* [0]title, [1]keyover, [2]source, [3]display(0=not;1=yes;2=edit;), [4]fieldwidth(0=not set), [5]align(0=default;1=right), [6]sortfield, [7]Editable, [8]table */
  define("HIDE", 0); define("DISPLAY", 1); define("EDIT", 2);  // display
  define("LEFT", 0); define("RIGHT", 1); // align
  define("NO_SORTER", 0); define("SORTER", 1); /* sortfield => 0=no escape removal; 1=escape removal; */
  define("NOT_EDITABLE", 0); define("INPUT", 1); define("TEXTAREA", 2); define("DROPDOWN", 3); define("BINARY", 4); define("EDIT_BTN", 5);  /* title, keyover, source, display(0=not;1=yes;2=edit), fieldwidth(0=not set), align(0=default;1=right), sortfield */

  $field_array = array(
   "accessories" => array("accessories",null, "accessories", DISPLAY, 0, LEFT, null, INPUT, "accessories"),
   "active" => array("active",null, "active", DISPLAY, 0, LEFT, null, BINARY, "ps.active"),
   "aDeliveryT" => array("aDeliveryT",null, "additional_delivery_times", DISPLAY, 1, LEFT, null, INPUT, "p.additional_delivery_times"),   
   "aShipCost" => array("aShipCost",null, "additional_shipping_cost", DISPLAY, 0, LEFT, null, INPUT, "ps.additional_shipping_cost"),
   "attachmnts" => array("attachmnts",null, "attachmnts", DISPLAY, 0, LEFT, null, INPUT, ""), 
   "available_now" => array("available_now",null, "available_now", DISPLAY, 0, LEFT, null, INPUT, "pl.available_now"),
   "available_later" => array("available_later",null, "available_later", DISPLAY, 0, LEFT, null, INPUT, "pl.available_later"), 
   "available_date" => array("available_date",null, "available_date", DISPLAY, 0, LEFT, null, BINARY, "ps.available_date"),
   /* availorder combines two of Prestashop's datafields: available_for_order and show_price */
   "availorder" => array("availorder",null, "available_for_order", DISPLAY, 0, LEFT, null, BINARY, "ps.available_for_order"),
   "cache_is_pack" => array("cache_is_pack",null, "cache_is_pack", DISPLAY, 0, LEFT, 0, NOT_EDITABLE, "p.cache_is_pack"),
   "carrier" => array("carrier",null, "carrier", DISPLAY, 0, LEFT, null, DROPDOWN, "cr.name"),
   "category" => array("category",null, "id_category_default", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.id_category_default"),
   "combinations" => array("combinations",null, "combinations", DISPLAY, 0, LEFT, 0, INPUT, "combinations"),
   "condition" => array("condition",null, "condition", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.condition"),
   "customizations" => array("customizations",null, "custFlds", DISPLAY, 0, LEFT, null, INPUT, "custFlds"),
   "date_add" => array("date_add",null, "date_add", DISPLAY, 0, LEFT, null, BINARY, "ps.date_add"),  
   "date_upd" => array("date_upd",null, "date_upd", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.date_upd"),
   "deliInStock" => array("deliInStock",null, "delivery_in_stock", DISPLAY, 0, LEFT, null, INPUT, "pl.delivery_in_stock"),
   "deliOutStock" => array("deliOutStock",null, "delivery_out_stock", DISPLAY, 0, LEFT, null, INPUT, "pl.delivery_out_stock"),   
   "description" => array("description",null, "description", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.description"),
   "description_short" => array("description_short",null, "description_short", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.description_short"),
   "discount" => array("discount",null, "discount", DISPLAY, 0, LEFT, null, INPUT, "discount"),
   "ean" => array("ean",null, "ean13", DISPLAY, 200, LEFT, null, INPUT, "p.ean13"),
   "ecotax" => array("ecotax",null, "ecotax", DISPLAY, 200, LEFT, null, INPUT, "ps.ecotax"),
   "featureEdit" => array("featureEdit",null, "name", DISPLAY, 0, LEFT, null, NOT_EDITABLE, ""), // name here is a dummy that is not used
   "isbn" => array("isbn",null, "isbn", DISPLAY, 0, LEFT, null, INPUT, "p.isbn"),
   "id_product" => array("id_product",array("id","id","id"), "id_product", DISPLAY, 0, RIGHT, null,NOT_EDITABLE, "p.id_product"),
   "image" => array("image",null, "name", DISPLAY, 0, LEFT, 0, EDIT_BTN, ""), // name here is a dummy that is not used
   "indexed" => array("indexed",null, "indexed", DISPLAY, 0, LEFT, 0, NOT_EDITABLE, "ps.indexed"),
   "indexes" => array("indexes",null, "indexes", DISPLAY, 0, LEFT, 0, NOT_EDITABLE, "p.indexed"),
   "link_rewrite" => array("link_rewrite",null, "link_rewrite", DISPLAY, 0, LEFT, null, INPUT, "pl.link_rewrite"),
   "location" => array("location",null, "location", DISPLAY, 0, LEFT, null, INPUT, "s.location"),
   "ls_alert" => array("ls_alert",null, "low_stock_alert", DISPLAY, 0, LEFT, null, INPUT, "ps.low_stock_alert"), 
   "ls_threshold" => array("ls_threshold",null, "low_stock_threshold", DISPLAY, 0, LEFT, null, INPUT, "ps.low_stock_threshold"),
   "manufacturer" => array("manufacturer",null, "manufacturer", DISPLAY, 0, LEFT, null, DROPDOWN, "m.name"),
   "meta_description" => array("meta_description",null, "meta_description", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.meta_description"),
   "meta_keywords" => array("meta_keywords",null, "meta_keywords", DISPLAY, 0, RIGHT, null, TEXTAREA, "pl.meta_keywords"),
   "meta_title" => array("meta_title",null, "meta_title", DISPLAY, 0, LEFT, null, INPUT, "pl.meta_title"),
   "minimal_quantity" => array("minimal_quantity",null, "minimal_quantity", DISPLAY, 0, LEFT, null, INPUT, "ps.minimal_quantity"),
   "mpn" => array("mpn",null, "mpn", DISPLAY, 0, LEFT, null, INPUT, "p.mpn"),
   "name" => array("name",null, "name", DISPLAY, 0, LEFT, null, INPUT, "pl.name"),
   "online_only" => array("online_only",null, "online_only", DISPLAY, 0, LEFT, null, BINARY, "ps.online_only"),
   "on_sale" => array("on_sale",null, "on_sale", DISPLAY, 0, LEFT, null, BINARY, "ps.on_sale"),
   "out_of_stock" => array("out_of_stock",null, "out_of_stock", DISPLAY, 0, LEFT, null, DROPDOWN, "s.out_of_stock"),
   "pack_stock_type" => array("pack_stock_type",null, "pack_stock_type", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.pack_stock_type"),
/* pro memory: s.physical_quantity in PS 1.7: this is automatically set when an order is placed */
   "position" => array("position",null, "position", DISPLAY, 0, RIGHT, null, NOT_EDITABLE, "cp.position"),  
   "price" => array("price",null, "price", DISPLAY, 200, RIGHT, null, INPUT, "ps.price"),
   "priceVAT" => array("priceVAT",null, "priceVAT", DISPLAY, 0, RIGHT, null, INPUT, "priceVAT"),
   "product_type" => array("product_type",null, "product_type", DISPLAY, 0, RIGHT, null, NOT_EDITABLE, "p.product_type"),  
   "quantity" => array("quantity",null, "quantity", DISPLAY, 0, LEFT, null, TEXTAREA, "s.quantity"),
   "redirect" => array("redirect",null, "redirect_type", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.redirect_type"),
   "reference" => array("reference",null, "reference", DISPLAY, 200, LEFT, null, INPUT, "p.reference"),
   "reserved" => array("reserved",null, "reserved_quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "s.reserved_quantity"), 
   "shipdepth" => array("shipdepth",null, "depth", DISPLAY, 0, LEFT, null, INPUT, "p.depth"), 
   "shipheight" => array("shipheight",null, "height", DISPLAY, 0, LEFT, null, INPUT, "p.height"),
   "shipweight" => array("shipweight",null, "weight", DISPLAY, 0, LEFT, null, INPUT, "p.weight"),
   "shipwidth" => array("shipwidth",null, "width", DISPLAY, 0, LEFT, null, INPUT, "p.width"),
   "shopz" => array("shopz",null, "id_shop", DISPLAY, 0, LEFT, null, BINARY, "ps.id_shop"),
   "show_condition" => array("show_condition",null, "show_condition", DISPLAY, 0, LEFT, null, BINARY, "ps.show_condition"),
   /* stockflags combines two of Prestashop's datafields: depends_on_stock and advanced_stock_management */
   "stockflags" => array("stockflags",null, "depends_on_stock", DISPLAY, 0, LEFT, null, BINARY, "s.depends_on_stock"),
   "state" => array("state",null, "state", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.state"),   /* STATE_TEMP=0 STATE_SAVED=1 */
   "supplier" => array("supplier",null, "supplier", DISPLAY, 0, LEFT, null, INPUT, "su.name"),
   "supplRef" => array("supplRef",null, "reference", DISPLAY, 200, LEFT, null, NOT_EDITABLE, "p.supplier_reference"),
   "tags" => array("tags",null, "tags", DISPLAY, 0, LEFT, null, TEXTAREA, "tg.name"),
   "unity" => array("unity",null, "unity", DISPLAY, 0, LEFT, null, INPUT, "ps.unity"),
   "unitPrice" => array("unitPrice",null, "unit_price", DISPLAY, 0, LEFT, null, INPUT, "ps.unit_price"),
   "unitPRatio" => array("unitPRatio",null, "unit_price_ratio", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.unit_price_ratio"),   
   "upc" => array("upc",null, "upc", DISPLAY, 200, LEFT, null, INPUT, "p.upc"),   
   "VAT" => array("VAT",null, "id_tax_rules_group", DISPLAY, 0, LEFT, null, DROPDOWN, "t.rate"),
   "virtualp" => array("virtualp",null, "virtualp", DISPLAY, 0, LEFT, null, INPUT, "virtualp"), /* virtual product */
   "visibility" => array("visibility",null, "visibility", DISPLAY, 0, LEFT, null, INPUT, "ps.visibility"), 
   "warehousing" => array("warehousing",null, "quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "w.id_warehouse"),
   "wholesaleprice" => array("wholesaleprice",null, "wholesale_price", DISPLAY, 0, LEFT, null, INPUT, "ps.wholesale_price"),
	  
	/* statistics */
   "visits" => array("visits","", "visitcount", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "visitcount"),
   "visitz" => array("visitz","", "visitedpages", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "visitedpages"),
   "salescnt" => array("salescnt","", "salescount", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "salescount"),
   "revenue" => array("revenue","", "revenue", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "revenue"),
   "orders" => array("orders","", "ordercount", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "ordercount"),
   "buyers" => array("buyers","", "buyercount", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "buyercount")
   ); 

$input["fields"] = $fields = array("id_product","name","VAT","price","priceVAT", "quantity", "active",
"category","ean", "upc", "isbn", "mpn","reference", "shopz","image", "description", "description_short", "link_rewrite","meta_title",
"meta_keywords","meta_description","wholesaleprice","manufacturer", "on_sale","online_only","date_upd",
"date_add","minimal_quantity","shipweight","shipheight","shipwidth","shipdepth", "aShipCost",
"available_now" , "available_later", "available_date", "availorder", "attachmnts",
"tags","carrier","accessories","combinations","discount","supplier");

/* get default language data: we use this for the categories, manufacturers */
$query="select name, iso_code from ". _DB_PREFIX_."lang";
$query .= " WHERE id_lang=".$def_lang;
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$def_langname = $row['name'];
$iso_code = $row['iso_code'];

/* Get selected language data if different */
if($id_lang != $def_lang)
{ $query="select name, iso_code from ". _DB_PREFIX_."lang WHERE id_lang='".$id_lang."'";
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $languagename = $row['name'];
  $iso_code = $row['iso_code'];
}

/* get language codes */
/* now get multi-language status. If true you get product urls like www.myshop.com/en/mycat/123-prod.html */
$query='SELECT l.* FROM `'._DB_PREFIX_.'lang` l
		JOIN '._DB_PREFIX_.'lang_shop ls ON (ls.id_lang = l.id_lang AND ls.id_shop = '.(int)$id_shop.')';
$res=dbquery($query);
$langcount = mysqli_num_rows($res);
$langinsert = "";
if($langcount>1)
	$langinsert = $iso_code."/";
$langcodes = array();
while($row = mysqli_fetch_array($res))
	$langcodes[$row["id_lang"]] = $row["iso_code"];

/* Get default country for the VAT tables and calculations */
$query="select l.name, id_country from ". _DB_PREFIX_."configuration f, "._DB_PREFIX_."country_lang l";
$query .= " WHERE f.name='PS_COUNTRY_DEFAULT' AND f.value=l.id_country ORDER BY id_lang IN('".$def_lang."','1') DESC"; /* the construction with the languages should select all languages with def_lang and '1' first */
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$countryname = $row['name'];
$id_country = $row["id_country"];

/* get shop group and its shared_stock status */
$query="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
$query .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_shop_group = $row['id_shop_group'];
$share_stock = $row["share_stock"];
$shop_group_name = $row["name"];

/* make tax block */
$ps_tax = get_configuration_value('PS_TAX'); /* flag that taxes are enabled */
$taxrates = array();
$taxrates[0] = 0;
$missing_taxgroups = array();
$done = array();
if($ps_tax)
{ $query = "SELECT rate,name,tr.id_tax_rule,g.id_tax_rules_group,g.active,";
  if(version_compare(_PS_VERSION_ , "1.6.0.10", "<"))
    $query .= "0 AS deleted";
  else
    $query .= "g.deleted";
  $query .= " FROM "._DB_PREFIX_."tax_rule tr";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON (t.id_tax = tr.id_tax)";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax_rules_group g ON (tr.id_tax_rules_group = g.id_tax_rules_group)";
  $query .= " WHERE tr.id_country = '".$id_country."' AND tr.id_state='0' AND NOT g.id_tax_rules_group IS NULL";
  $res=dbquery($query);
  $taxblock = '<option value="0">Select VAT</option>';
  while($row = mysqli_fetch_array($res))
  { if(($row["active"]==0) ||($row["deleted"]==1)) 
	  $missing_taxgroups[$row['id_tax_rules_group']] = '<option value="'.$row['id_tax_rules_group'].'" rate="'.(float)$row['rate'].'" selected>'.str_replace("'","\'",$row['name']).'</option>';
    else
      $taxblock .= '<option value="'.$row['id_tax_rules_group'].'" rate="'.(float)$row['rate'].'">'.str_replace("'","\'",$row['name']).'</option>';
    $taxrates[$row['id_tax_rules_group']] = $row['rate'];
	$done[] = $row['id_tax_rules_group'];
  }
  /* now the taxrule groups that have no rate defined for this country */
  $query = "SELECT g.id_tax_rules_group,name,g.active,";
  if(version_compare(_PS_VERSION_ , "1.6.0.10", "<"))
    $query .= "0 AS deleted";
  else
    $query .= "g.deleted";
  $query .= " FROM "._DB_PREFIX_."tax_rules_group g";
  if(sizeof($done) > 0)
    $query .= " WHERE NOT g.id_tax_rules_group IN (".implode(",",$done).")";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
  { if(($row["active"]==0) ||($row["deleted"]==1)) 
	  $missing_taxgroups[$row['id_tax_rules_group']] = '<option value="'.$row['id_tax_rules_group'].'" rate="0" selected>'.str_replace("'","\'",$row['name']).'</option>';
    else
	  $taxblock .= '<option value="'.$row['id_tax_rules_group'].'" rate="0">'.str_replace("'","\'",$row['name']).'</option>';
    $taxrates[$row['id_tax_rules_group']] = 0;
  }
  $taxblock .= "</select>";
}
else
{ $taxblock = '<option value="0">Select VAT</option></select>';
  $query = "SELECT DISTINCT id_tax_rules_group FROM "._DB_PREFIX_."product_shop";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
  { $missing_taxgroups[$row['id_tax_rules_group']] = '<option value="'.$row['id_tax_rules_group'].'" rate="0" selected>TAX rules group '.$row['id_tax_rules_group'].'</option>';
    $taxrates[$row['id_tax_rules_group']] = 0;
  }
}  
  
$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');

/* the following deals with the case of customized product urls (used for the links on the product name) */
$route_product_rule = get_route('product', $id_lang);
$regexp = preg_quote($route_product_rule, '#');
$keywordlist = array("id", "rewrite","ean13","category","categories","reference","meta_keywords","meta_title","manufacturer","supplier","price","tags");
$keywords = array();
foreach($keywordlist AS $key)
{ if(strpos($route_product_rule, $key) > 0)
    $keywords[] = $key;
}
if (sizeof($keywords)>0) 
{ $transform_keywords = array();
  preg_match_all('#\\\{(([^{}]*)\\\:)?('.implode('|', $keywords).')(\\\:([^{}]*))?\\\}#', $regexp, $m);
  for ($i = 0, $total = count($m[0]); $i < $total; $i++) 
  { $prepend = $m[2][$i];
    $keyword = $m[3][$i];
    $append = $m[5][$i];
    $transform_keywords[$keyword] = array(
                    'prepend' =>    stripslashes($prepend),
                    'append' =>     stripslashes($append),
                );
  }
}

/* look for double category names */
  $duplos = array();
  $query = "select name,count(*) AS duplocount from ". _DB_PREFIX_."category_lang WHERE id_lang='".$def_lang."' AND id_shop='".$id_shop."' GROUP BY name HAVING duplocount > 1";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  {  $duplos[] = $row["name"];
  }
  
/* make category block */
  $query = "select c.id_category,name,link_rewrite, id_parent from "._DB_PREFIX_."category c";
  $query .= " left join "._DB_PREFIX_."category_lang cl ON c.id_category=cl.id_category";
  $query .= " AND id_lang='".$id_lang."' AND id_shop='".$id_shop."' ORDER BY name";
  $res=dbquery($query);
  $category_names = $category_rewrites = $category_parents = array();
  $allcats = array();
  $x=0;
  /* solo-mod: variables (mycats and category_default) are not declared in block but in loop */
  $categoryblock0 = '<table cellspacing=8><tr><td><select id="categorylistCQX" size=4 multiple onchange="cat_change(\'CQX\')">';
  $categoryblock1 = "";
  while ($row=mysqli_fetch_array($res)) 
  { if($row["name"]!="")
	{ if(in_array($row['name'], $duplos))
	    $name = $row['name'].$row['id_category'];
	  else
	    $name = $row['name'];
      $categoryblock1 .= '<option value="'.$row['id_category'].'">'.str_replace("'","\'",$name).'</option>';
      $category_names[$row['id_category']] = $name;
      $category_rewrites[$row['id_category']] = $row['link_rewrite'];
      $category_parents[$row['id_category']] = $row['id_parent'];	  
	}
	else /* this happens when category is not present in the chosen shop */
	{ $cres = dbquery("SELECT name, link_rewrite FROM "._DB_PREFIX_."category_lang WHERE id_category=".$row["id_category"]." AND id_lang=".$id_lang);
	  $crow=mysqli_fetch_array($cres);
	  $category_names[$row['id_category']] = $crow["name"];
	  $category_rewrites[$row['id_category']] = $crow['link_rewrite'];
      $category_parents[$row['id_category']] = $row['id_parent'];	  
	}
  } 
  /* solo-mod: variables (mycats and category_default) are not declared in block but in loop */
  $categoryblock1 .= '</select>';
  $categoryblock2 = '</td><td><a href=# onClick="Addcategory(\'CQX\'); reg_change(this); return false;"><img src=add.gif border=0></a>';
  $categoryblock2 .= '<input id="category_numberCQX" class="catselnum" onkeyup="change_category_number(\'CQX\')">';
  $categoryblock2 .= '<a href=# onClick="Removecategory(\'CQX\'); reg_change(this); return false;"><img src=remove.gif border=0></a></td><td><select id=categoryselCQX size=3 onchange="catsel_change(\'CQX\')"></select></td>';
  $categoryblock2 .= '<td><a href=# onClick="MakeCategoryDefault(\'CQX\'); reg_change(this); return false;"><img src="starr.jpg" border=0></a></td></td></tr></table>';
  
/* make carrier block */
if(in_array('carrier', $input["fields"]))
{ $query = "select id_reference,name from ". _DB_PREFIX_."carrier WHERE deleted='0' ORDER BY name";
  $res=dbquery($query);
  $carrierblock0 = '<input type=hidden name="carrier_defaultCQX"><input type=hidden name="mycarsCQX">';
  $carrierblock0 .= '<table cellspacing=8><tr><td><select id="carrierlistCQX" size=4 multiple>';
  $carrierblock1 = "";
  while ($row=mysqli_fetch_array($res)) 
  { $carrierblock1 .= '<option value="'.$row['id_reference'].'">'.str_replace("'","\'",$row['name']).'</option>';
  } 
  $carrierblock1 .= '</select>';
  $carrierblock2 = '</td><td><a href=# onClick=" Addcarrier(\\\'CQX\\\'); reg_change(this); return false;"><img src=add.gif border=0></a><br><br>';
  $carrierblock2 .= '<a href=# onClick="Removecarrier(\\\'CQX\\\'); reg_change(this); return false;"><img src=remove.gif border=0></a></td><td><select id=carrierselCQX size=3><option>none</option></select></td></tr></table>';
}  
else 
  $carrierblock0 = $carrierblock1 = $carrierblock2 = ""; 
  
  /* make supplier names list */
if(in_array('supplier', $input["fields"]))
{ $query = "select id_supplier,name from ". _DB_PREFIX_."supplier ORDER BY name";
  $res=dbquery($query);
  $supplier_names = array();
  $supplierblock0 = '<input type=hidden name="mysupsCQX">';
  $supplierblock0 .= '<table><tr><td><select id="supplierlistCQX">';
  $supplierblock1 = "";
  while ($row=mysqli_fetch_array($res)) 
  { $supplier_names[$row['id_supplier']] = $row['name'];
    $supplierblock1 .= '<option value="'.$row['id_supplier'].'">'.str_replace("'","\'",$row['name']).'</option>';
  }
  $supplierblock1 .= '</select>';
  $supplierblock2 = '</td><td><nobr><a href=# onClick=" Addsupplier(\\\'CQX\\\',1); reg_change(this); return false;"><img src=add.gif border=0></a> &nbsp; &nbsp; ';
  $supplierblock2 .= '<a href=# onClick="Removesupplier(\\\'CQX\\\'); reg_change(this); return false;"><img src=remove.gif border=0></a></nobr></td><td><select id="supplierselCQX"></select></td></tr></table>';
}
else 
  $supplierblock0 = $supplierblock1 = $supplierblock2 = "";
  
/* make attachment attachmnts block */
if(in_array('attachmnts', $input["fields"]))
{ $query = "SELECT a.file_name, l.name, a.id_attachment FROM ". _DB_PREFIX_."attachment a";
  $query .= " LEFT JOIN ". _DB_PREFIX_."attachment_lang l ON a.id_attachment=l.id_attachment AND l.id_lang='".$id_lang."'";
  $res = dbquery($query);
  $attachmentblock0 = '<input type=hidden name="attachment_defaultCQX"><input type=hidden name="myattachmentsCQX">';
  $attachmentblock0 .= '<table cellspacing=8><tr><td><select id="attachmentlistCQX" size=4 multiple>';
  $attachmentblock1 = "";
  while ($row=mysqli_fetch_array($res)) 
  { $attachmentblock1 .= '<option value="'.$row['id_attachment'].'">'.str_replace("'","\'",$row['name']).'</option>';
  } 
  $attachmentblock1 .= '</select>';
  $attachmentblock2 = '</td><td><a href=# onClick=" Addattachment(\\\'CQX\\\'); reg_change(this); return false;"><img src=add.gif border=0></a><br><br>';
  $attachmentblock2 .= '<a href=# onClick="Removeattachment(\\\'CQX\\\'); reg_change(this); return false;"><img src=remove.gif border=0></a></td><td><select id=attachmentselCQX size=3></select></td></tr></table>';
  $currentDir = dirname(__FILE__);
  $download_dir = $currentDir."/".$triplepath."download/";
}
else 
  $attachmentblock0 = $attachmentblock1 = $attachmentblock2 = "";
  
/* Make blocks for features */
$query = "SELECT fl.id_feature, name FROM ". _DB_PREFIX_."feature_lang fl";
$query .= " LEFT JOIN ". _DB_PREFIX_."feature_shop fs ON fs.id_feature = fl.id_feature";
$query .= " WHERE id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
$query .= " ORDER BY id_feature";
$res = dbquery($query);
$features = array();
$featureblocks = array();
$featurecount = 0;
$featurelist = array();
$featurekeys = array();
while($row = mysqli_fetch_array($res))
{ $features[$row['id_feature']] = $row['name'];
  if(in_array($row['name'], $input["fields"]))
  { $featurelist[$row['id_feature']] = $row['name'];
    $featurekeys[] = $row['id_feature'];
	$block = '<option value="">Select '.str_replace("'","\'",$row['name']).'</option>';
    $fquery = "SELECT v.id_feature_value, value FROM ". _DB_PREFIX_."feature_value v";
	$fquery .= " LEFT JOIN ". _DB_PREFIX_."feature_value_lang vl ON v.id_feature_value = vl.id_feature_value AND vl.id_lang='".$id_lang."'";
	$fquery .= " WHERE v.id_feature='".$row['id_feature']."' AND v.custom='0'";
	$fres = dbquery($fquery);
	if(mysqli_num_rows($fres) == 0)
		$featureblocks[$featurecount++] = "";
	else
	{ $fvalues = array();
	  while($frow = mysqli_fetch_array($fres))
	  {  $fvalues[$frow['id_feature_value']] = $frow['value'];
	  }
	  natsort($fvalues);
	  foreach($fvalues AS $key => $value)
	  { $block .= '<option value="'.$key.'">'.str_replace("'","\'",$value).'</option>';
	  }
	  $featureblocks[$featurecount++] = $block."</select>";
	}
  }
}

/* making shop block */
    $shopblock = "";
	$shops = $shopids = array();
	$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	while ($shop=mysqli_fetch_array($res)) 
	{	if (isset($id_shop) && ($shop['id_shop']==$id_shop)) {$selected=' selected="selected" ';} else $selected="";
		$shopblock .= '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
		$shops[] = $shop['name'];
		$shopids[] = $shop['id_shop'];
	}	

/* Make the discount blocks */
/* 						0				1		2		3		  4			5			6		7				8			9	 		10	11*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_type, from, to */
  if(in_array("discount", $input["fields"]))
  { $currencyblock = "";
    $currencies = array();
	$query=" select id_currency,iso_code from ". _DB_PREFIX_."currency WHERE deleted='0' AND active='1' ORDER BY name";
	$res=dbquery($query);
	while ($currency=mysqli_fetch_array($res)) {
		$currencyblock .= '<option  value="'.$currency['id_currency'].'" >'.$currency['iso_code'].'</option>';
		$currencies[] = $currency['iso_code'];
	}
	
	$countryblock = "";
	$query=" select id_country,name from ". _DB_PREFIX_."country_lang WHERE id_lang='".$id_lang."' ORDER BY name";
	$res=dbquery($query);
	while ($country=mysqli_fetch_array($res)) {
		$countryblock .= '<option  value="'.$country['id_country'].'" >'.$country['id_country']."-".str_replace("'","\'",$country['name']).'</option>';
	}

	$groupblock = "";
	$query=" select id_group,name from ". _DB_PREFIX_."group_lang WHERE id_lang='".$id_lang."' ORDER BY id_group";
	$res=dbquery($query);
	while ($group=mysqli_fetch_array($res)) {
		$groupblock .= '<option  value="'.$group['id_group'].'" >'.$group['id_group']."-".$group['name'].'</option>';
	}
  }
  
  $force_friendly_product = get_configuration_value('PS_FORCE_FRIENDLY_PRODUCT'); /* automatically regenerate link-rewrite when name changed? */
  
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Solo Edit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script src="tinymce/tinymce.min.js"></script> <!-- Prestashop settings can be found at /js/tinymce.inc.js -->
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var psdlimit = '<?php echo get_configuration_value("PS_PRODUCT_SHORT_DESC_LIMIT"); ?>';
var allow_accented_chars = '<?php echo $allow_accented_chars ?>';
var force_friendly_product = '<?php echo $force_friendly_product ?>';
var product_fields = new Array();
var taxblock = '<?php echo $taxblock ?>';
var catselectortype = '<?php echo $prestools_settings["catselectortype"] ?>';
var supplierblock0 = '<?php echo $supplierblock0 ?>';
var supplierblock1 = '<?php echo $supplierblock1 ?>';
var supplierblock2 = '<?php echo $supplierblock2 ?>';
var attachmentblock0 = '<?php echo $attachmentblock0 ?>';
var attachmentblock1 = '<?php echo $attachmentblock1 ?>';
var attachmentblock2 = '<?php echo $attachmentblock2 ?>';
var carrierblock0 = '<?php echo $carrierblock0 ?>';
var carrierblock1 = '<?php echo $carrierblock1 ?>';
var carrierblock2 = '<?php echo $carrierblock2 ?>';
var featurelist = ['<?php echo implode("','", $featurelist); ?>'];
var featurekeys = ['<?php echo implode("','", $featurekeys); ?>'];
var featureblocks = new Array();
<?php 
  for ($i=0; $i<$featurecount; $i++)
  { echo "featureblocks[".$i."]='".$featureblocks[$i]."';
";
  }
  if(in_array("discount", $input["fields"]))
  { echo "currencyblock='".$currencyblock."';
    countryblock='".$countryblock."';
	groupblock='".$groupblock."';
	shopblock='".str_replace("'","\'",$shopblock)."';
";
    echo 'currencies=["';
	$currs = implode('","', $currencies);
	echo $currs.'"]; 
'; 

    echo 'shops=["';
	$shopz = implode('","', $shops);
	echo $shopz.'"]; 
'; 
  }  
?>
function checkPrices()
{ rv = document.getElementsByClassName("price");
  for(var i in rv) { 
    if(rv[i].value.indexOf(',') != -1) { 
      alert("Please use dots instead of comma's for the prices!");
      rv.focus();
      return false;
    }
  }
  return true;
}

function RemoveRow(row)
{ var tblEl = document.getElementById("offTblBdy");
  var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
}

function price_change(elt)
{ var val, rate;
  if(Mainform.VAT.selectedIndex == 0)
	  rate = 0;
  else 
    rate = parseFloat(Mainform.VAT.options[Mainform.VAT.selectedIndex].getAttribute("rate"));
  if(elt.name == "price")
  { val = parseFloat(elt.value);
	newprice = val*(1+ (rate/100));
	Mainform.priceVAT.value = Math.round(newprice*100)/100; /* round to 2 decimals */
  }
  else if(elt.name == "priceVAT")
  { val = parseFloat(elt.value);
	newprice = val/(1+ (rate/100));
	Mainform.price.value = Math.round(newprice*100)/100; /* round to 2 decimals */
  }
}

function VAT_change(elt)
{ if(elt.selectedIndex==0) 
	VAT = 0;
  else
    var VAT = elt.options[elt.selectedIndex].getAttribute("rate");
  var newpriceVAT = ((100+parseFloat(VAT))/100)*parseFloat(Mainform.price.value);
  newpriceVAT = Math.round(newpriceVAT*100)/100; /* round to 6 decimals */
  Mainform.priceVAT.value = newpriceVAT;
}

function tidy_html(html) {
    var d = document.createElement('div');
    d.innerHTML = html;
    return d.innerHTML;
}

function check_string(myelt,taboos)
{ var patt = new RegExp( "[" + taboos + "]" );
  if(myelt.value.search(patt) == -1)
    return true;
  else
  { alert("The following characters are not allowed and have been removed: "+taboos);
    myelt.value = myelt.value.replace(patt,"");
    return false;
  }
}

function name_change(elt)
{ var suffix = elt.name.substring(4);
  var link = eval('Mainform.link_rewrite'+suffix);
  if(link && force_friendly_product) /* if field is editable */
  { var name = eval('Mainform.name'+suffix);
    var nameval = name.value.replace(/<[^>]*>/g,'');
	link.value = str2url(nameval);
  }
}

/* take care that only one option is active at the same time */
function feature_change(elt)
{ var myform = elt;
  while (myform.nodeName != "FORM" && myform.parentNode) // find form (either massform or Mainform) 
  { myform = myform.parentNode;
  }
  if(!myform) alert("error finding form");
  if(elt.name.indexOf("_sel")>0)
  { var input = elt.name.replace("_sel","");
	myform[input].value="";
  }
  else
  { if(!check_string(elt,"<>;=#{}"))
      return;
    var patt1=/([0-9]*)$/;
    var sel = elt.name.replace(patt1, "_sel$1");
	if(myform[sel])
	  myform[sel].selectedIndex = 0;
  }
  if(myform.name == "Mainform")
    reg_change(elt);
}

function add_discount(row)
{ var count_root = eval('Mainform.discount_count'+row);
  var dcount = parseInt(count_root.value);
  var blob = fill_discount(row,dcount,"","new","","","","0","0","0","","1","","","","");
  var new_div = document.createElement('div');
  new_div.innerHTML = blob;
  var adder = document.getElementById("discount_adder"+row);
  adder.parentNode.insertBefore(new_div,adder);
  count_root.value = dcount+1;
  return false;
}

function edit_discount(row, entry)
{ var changed = 0;
  var status = eval('Mainform.discount_status'+entry+'s'+row+'.value');
  var shop = eval('Mainform.discount_shop'+entry+'s'+row+'.value');
  var currency = eval('Mainform.discount_currency'+entry+'s'+row+'.value');
  var group = eval('Mainform.discount_group'+entry+'s'+row+'.value');
  var country = eval('Mainform.discount_country'+entry+'s'+row+'.value');
  
  var blob = '<form name="dhform"><input type=hidden name=row value="'+row+'"><input type=hidden name=entry value="'+entry+'">';
  	blob += '<input type=hidden name="discount_status" value="'+status+'">';	
  	blob += '<input type=hidden name="discount_id" value="'+eval('Mainform.discount_id'+entry+'s'+row+'.value')+'">';			
	blob += '<table id="discount_table" cellpadding="2"';
	blob += '<tr><td><b>Shop id</b></td>';
	if(status == "update")
	{	blob += '<td><input type=hidden name="discount_shop" value="'+eval('Mainform.discount_shop'+entry+'s'+row+'.value')+'">';
		if(shop == "") blob += 'all</td></tr>';
		else blob+=''+shop+'</td></tr>';
		blob += '<tr><td><b>Attribute</b></td><td><input type=hidden name="discount_attribute" value="'+eval('Mainform.discount_attribute'+entry+'s'+row+'.value')+'">';
	}
	else /* insert */
	{	blob += '<td><select name="discount_shop" onchange="changed = 1;">';
		blob += '<option value="0">All</option>'+(((shop == "") || (shop == 0))? shopblock : shopblock.replace(">"+shop+"-", " selected>"+shop+"-"))+'</select></td></tr>';
		blob += '<tr><td><b>Attribute</b></td><td><input name="discount_attribute" value="'+eval('Mainform.discount_attribute'+entry+'s'+row+'.value')+'" onchange="changed = 1;"></td></tr>';
	}
	
	blob += '<tr><td><b>Currency</b></td>';
	blob += '<td><select name="discount_currency" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select></td></tr>';

	blob += '<tr><td><b>Country</b></td>';
	blob += '<td><select name="discount_country" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((country == "")? countryblock : countryblock.replace(">"+country+"-", " selected>"+country+"-"))+'</select></td></tr>';
	
	blob += '<tr><td><b>Group</b></td>';
	blob += '<td><select name="discount_group" onchange="changed = 1;">';
	blob += '<option value="0">All</option>'+((group == "")? groupblock : groupblock.replace(">"+group+"-", " selected>"+group+"-"))+'</select></td></tr>';

	blob += '<tr><td><b>Customer id</b></td><td><input name="discount_customer" value="'+eval('Mainform.discount_customer'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; 0=all customers</td></tr>';
	
	blob += '<tr><td><b>Price</b></td><td><input name="discount_price" value="'+eval('Mainform.discount_price'+entry+'s'+row+'.value')+'" class="prijs" onchange="changed = 1;"> &nbsp; From price. Leave empty when equal to normal price.</td></tr>';
	blob += '<tr><td><b>Quantity</b></td><td><input name="discount_quantity" value="'+eval('Mainform.discount_quantity'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; Threshold for reduction.</td></tr>';
	blob += '<tr><td><b>Reduction</b></td><td><input name="discount_reduction" value="'+eval('Mainform.discount_reduction'+entry+'s'+row+'.value')+'" onchange="changed = 1;"></td></tr>';

	blob += '<tr><td><b>Red. type</b></td><td><select name="discount_reductiontype" onchange="changed = 1;">';
    if(eval('Mainform.discount_reductiontype'+entry+'s'+row+'.selectedIndex') == 1)
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select></td></tr>';
	blob += '<tr><td><nobr><b>From date</b></nobr></td><td><input name="discount_from" value="'+eval('Mainform.discount_from'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"></td></tr>';
	blob += '<tr><td><b>To date</b></td><td><input name="discount_to" value="'+eval('Mainform.discount_to'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"></td></tr>';
	blob += '<tr><td></td><td align="right"><input type=button value="submit" onclick="submit_dh_discount()"></td></tr></table></form>'; 
    googlewin=dhtmlwindow.open("Edit_discount", "inline", blob, "Edit discount", "width=550px,height=425px,resize=1,scrolling=1,center=1", "recal");
  return false;
}

function submit_dh_discount()
{ /*					row				entry				id					status					shop			attribute			*/
  var currency = dhform.discount_currency.options[dhform.discount_currency.selectedIndex].text;
  var country = dhform.discount_country.options[dhform.discount_country.selectedIndex].text;
  country = country.substring(0,country.indexOf('-'));
  var group = dhform.discount_group.options[dhform.discount_group.selectedIndex].text;
  group = group.substring(0,group.indexOf('-'));
  var reductiontype = dhform.discount_reductiontype.options[dhform.discount_reductiontype.selectedIndex].text;
  
  var blob = fill_discount(dhform.row.value,dhform.entry.value,dhform.discount_id.value,dhform.discount_status.value,dhform.discount_shop.value,dhform.discount_attribute.value,currency,country,group,dhform.discount_customer.value,dhform.discount_price.value,dhform.discount_quantity.value,dhform.discount_reduction.value,reductiontype,dhform.discount_from.value,dhform.discount_to.value);
  var eltname = 'discount_table'+dhform.entry.value+'s'+dhform.row.value;
  var target = document.getElementById(eltname);
  target = target.parentNode;
  target.innerHTML = blob;
  
//function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontype,from,to)
  googlewin.close();
}

function del_discount(row, entry)
{ var tab = document.getElementById("discount_table"+entry+"s"+row);
  tab.innerHTML = "";
  var statusfield = eval('Mainform.discount_status'+entry+'s'+row);
  statusfield.value = "deleted";
  reg_change(tab);
  return false;
}

/* dummy function */
function reg_change(elt)
{}

/* the ps_specific_prices table has two unique keys that forbid that two too similar reductions are inserted.
 * This function - called before submit - checks for them. 
 * Without this check you get errors like: 
 *   Duplicate entry '113-0-0-0-0-0-0-0-15-0000-00-00 00:00:00-0000-00-00 00:00:00' for key 'id_product_2'
 * This key contains the following fields: id_product, id_shop,id_shop_group,id_currency,id_country,id_group,id_customer,id_product_attribute,from_quantity,from,to */
function check_discounts(rowno)
{ var field = eval("Mainform.discount_count"+rowno);
  if (!field || (field.value == 0))
    return true;
  var keys2 = new Array();
  for(var i=0; i< field.value; i++)
  { if(eval("Mainform.discount_status"+i+"s"+rowno+".value") == "deleted")
      continue;
    var key = eval("Mainform.id_product"+rowno+".value")+"-"+eval("Mainform.discount_shop"+i+"s"+rowno+".value")+"-0-"+eval("Mainform.discount_currency"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_country"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_group"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_customer"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_attribute"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_quantity"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_from"+i+"s"+rowno+".value")+"-"+eval("Mainform.discount_to"+i+"s"+rowno+".value");
    for(var j = 0; j < keys2.length; j++) {
        if(keys2[j] == key) 
		{ var tbl= document.getElementById("offTblBdy");
		  var productno = tbl.rows[rowno].cells[1].childNodes[0].text;
		  alert("You have two or more price rules for a product that are too similar for product "+productno+" on row "+rowno+"! Please correct this!");
		  return false;
		}
    }
	keys2[j] = key;
  }
  return true;
}

/* 					0			1				2		3		4		5			6		7				8			9	 		 10  	11	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_type, from, to */
function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontype,from,to)
{ 	var blob = '<input type=hidden name="discount_id'+entry+'s'+row+'" value="'+id+'">';
	blob += '<input type=hidden name="discount_status'+entry+'s'+row+'" value="'+status+'">';		
	blob += '<table id="discount_table'+entry+'s'+row+'"><tr><td rowspan=3><a href="#" onclick="return edit_discount('+row+','+entry+')"><img src="pen.png"></a></td>';
	
	if(customer == "") customer = 0;
	if(country == "") country = 0;
	if(group == "") group = 0;
	if(attribute == "") attribute = 0;
	if(quantity == "") quantity = 1;
	if(shop == "") shop = 0;
	
	if(status == "update")
	{	blob += '<td><input type=hidden name="discount_shop'+entry+'s'+row+'" value="'+shop+'">';
		if(shop == "") blob += "all";
		else blob+=shop;
		blob += '-<input type=hidden name="discount_attribute'+entry+'s'+row+'" value="'+attribute+'">';
		if(attribute == "") blob += "all";
		else blob+=attribute;
	}
	else /* insert */
	{	blob += '<td><input name="discount_shop'+entry+'s'+row+'" value="'+shop+'" title="shop id" onchange="reg_change(this);"> &nbsp;';
		blob += '<input name="discount_attribute'+entry+'s'+row+'" value="'+attribute+'" title="product_attribute id" onchange="reg_change(this);"> &nbsp;';
	}
	
	blob += '<select name="discount_currency'+entry+'s'+row+'" value="'+currency+'" title="currency" onchange="reg_change(this);">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select> &nbsp;';

	blob += '<input name="discount_country'+entry+'s'+row+'" value="'+country+'" title="country id" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_group'+entry+'s'+row+'" value="'+group+'" title="group id" onchange="reg_change(this);"></td>';
	
	blob += '<td rowspan=3><a href="#" onclick="return del_discount('+row+','+entry+')"><img src="del.png"></a></td></tr><tr>';
	blob += '<td><input name="discount_customer'+entry+'s'+row+'" value="'+customer+'" title="customer id" onchange="reg_change(this);"> &nbsp; ';

	blob += '<input name="discount_price'+entry+'s'+row+'" value="'+price+'" title="From Price" class="prijs" onchange="reg_change(this);"> &nbsp; ';
	blob += '<input name="discount_quantity'+entry+'s'+row+'" value="'+quantity+'" title="From Quantity" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_reduction'+entry+'s'+row+'" value="'+reduction+'" title="Reduction" onchange="reg_change(this);">';
	blob += '</tr><tr>';
	blob += '<td><select name="discount_reductiontype'+entry+'s'+row+'" title="Reduction Type" onchange="reg_change(this);">';
	if(reductiontype == "pct")
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select> &nbsp;';
	blob += '<input name="discount_from'+entry+'s'+row+'" value="'+from+'" title="From Date" class="datum" onchange="reg_change(this);"> &nbsp; ';
	blob += '<input name="discount_to'+entry+'s'+row+'" value="'+to+'" title="To Date" class="datum" onchange="reg_change(this);"></td>';	
	blob += "</tr></table><hr/>";
	return blob;
}

function useTinyMCE(elt, field)
{ while (elt.nodeName != "TD")
  {  elt = elt.parentNode;
  }
  elt.childNodes[0].cols="125";
  elt.childNodes[1].style.display = "none";  /* hide the links */
  tinymce.init({
//	content_css: "http://localhost/css/my_tiny_styles.css",
//    fontsize_formats: "8pt 9pt 10pt 11pt 12pt 26pt 36pt",	
	selector: "#"+field, 
//	width:500
//	setup: function (ed) {
//  	ed.on("change", function () {
//        })
//	}
  });		// Note: onchange_callback was for TinyMCE 3.x and doesn't work in 4.x
}

/* the arguments for this version were derived from source code of the "classic" example on the TinyMCE website */
/* some buttons were removed bu all plugins were maintained */
function useTinyMCE2(elt, field)
{ while (elt.nodeName != "TD")
  {  elt = elt.parentNode;
  }
  elt.childNodes[0].cols="125";
  elt.childNodes[1].style.display = "none";  /* hide the links */
  tinymce.init({
  	selector: "#"+field, 
	plugins: [
		"advlist autolink autosave link image lists charmap print preview hr anchor pagebreak spellchecker",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
		"table contextmenu directionality emoticons template textcolor paste fullpage textcolor colorpicker textpattern"
	],
	toolbar1: "bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
	toolbar2: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink anchor image media code | insertdatetime preview",
	toolbar3: "forecolor backcolor | table | hr removeformat | subscript superscript | charmap emoticons | print fullscreen | ltr rtl | spellchecker | visualchars visualblocks nonbreaking",
	menubar: false,
	toolbar_items_size: 'small',
	style_formats: [
		{title: 'Bold text', inline: 'b'},
		{title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
		{title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
		{title: 'Example 1', inline: 'span', classes: 'example1'},
		{title: 'Example 2', inline: 'span', classes: 'example2'},
		{title: 'Table styles'},
		{title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
	],
	width: 640,
	autosave_ask_before_unload: false
  });
}  


function Addcarrier(plIndex)
{ var list = document.getElementById('carrierlist'+plIndex); /* available carriers */
  var sel = document.getElementById('carriersel'+plIndex);	/* selected carriers */
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  carrier = list.options[listindex].text;
  car_id = list.options[listindex].value;
  list.options[listindex]=null;		/* remove from available carriers list */
  if(sel.options[0].value == "none")
  { sel.options.length = 0;
    max = 0;
  }
  i=0;
  var base = sel.options;
  while((i<max) && (carrier > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(carrier);
  else
  { newOption = new Option(carrier);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(carrier));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = car_id;
  var mycars = eval("document.Mainform.mycars"+plIndex);
  mycars.value = mycars.value+','+car_id;
}

function Removecarrier(plIndex)
{ var list = document.getElementById('carrierlist'+plIndex);
  var sel = document.getElementById('carriersel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  carrier = sel.options[selindex].text;
  if(carrier == "none") return; /* none selected */
  car_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (carrier > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(carrier);
  else
  { newOption = new Option(carrier);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(carrier));
    list.insertBefore(newOption, list.options[i]);
  }
  if(sel.options.length == 0)
    sel.options[0] = new Option("none");
  list.options[i].value = car_id;
  var mycars = eval("document.Mainform.mycars"+plIndex);
  mycars.value = mycars.value.replace(','+car_id, '');
}

function fillCarriers(idx,cars)
{ var list = document.getElementById('carrierlist'+idx);
  var sel = document.getElementById('carriersel'+idx);
  for(var i=0; i< cars.length; i++)
  { for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == cars[i])
	  { list.selectedIndex = j;
		Addcarrier(idx);
	  }
	}
  }
}

function Addsupplier(plIndex, init)
{ var list = document.getElementById('supplierlist'+plIndex);
  var sel = document.getElementById('suppliersel'+plIndex);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  supplier = list.options[listindex].text;
  sup_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  while((i<max) && (supplier > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(supplier);
  else
  { newOption = new Option(supplier);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(supplier));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = sup_id;
  if(init == 1)
  { var attributes = eval("document.Mainform.supplier_attribs"+plIndex+".value");
    var myattribs = attributes.split(",");
    for(i=0; i < myattribs.length; i++)
    { var tab = document.getElementById("suppliertable"+myattribs[i]+"s"+plIndex);
	  if(tab.rows[0])
		tab.rows[0].deleteCell(3);
      for (j=0; j<= tab.rows.length; j++)
	  { if(!tab.rows[j] || tab.rows[j].cells[0].innerHTML > supplier)
	    { var newRow = tab.insertRow(j);
		  newRow.innerHTML='<td>'+supplier+'</td><td><input name="supplier_reference'+myattribs[i]+'t'+sup_id+'s'+plIndex+'" value="" onchange="reg_change(this);" /></td><td><input name="supplier_price'+myattribs[i]+'t'+sup_id+'s'+plIndex+'" value="0.000000" onchange="reg_change(this);" /></td>';
		  break;
		}
	  }
	  tab.rows[0].innerHTML += '<td rowspan="'+tab.rows.length+'">'+tab.title+'</td>';
	}
  }  
  var mysups = eval("document.Mainform.mysups"+plIndex);
  mysups.value = mysups.value+','+sup_id;
}

function Removesupplier(plIndex)
{ var list = document.getElementById('supplierlist'+plIndex);
  var sel = document.getElementById('suppliersel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, j, max = list.options.length;
  var supplier = sel.options[selindex].text;
  sup_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (supplier > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(supplier);
  else
  { newOption = new Option(supplier);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(supplier));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = sup_id;
  var attributes = eval("document.Mainform.supplier_attribs"+plIndex+".value");
  var myattribs = attributes.split(",");
  for(i=0; i < myattribs.length; i++)
  { var tab = document.getElementById("suppliertable"+myattribs[i]+"s"+plIndex);
    tab.rows[0].deleteCell(3);
    for (j=0; j< tab.rows.length; j++)
	{ if(tab.rows[j].cells[0].innerHTML == supplier)
	  { tab.deleteRow(j);
	  }
	}
	if(tab.rows.length > 0)
		tab.rows[0].innerHTML += '<td rowspan="'+tab.rows.length+'">'+tab.title+'</td>';	
  }
  var mysups = eval("document.Mainform.mysups"+plIndex);
  mysups.value = mysups.value.replace(','+sup_id, '');
}

function Addcategory(plIndex)
{ var list = document.getElementById('categorylist'+plIndex);
  var sel = document.getElementById('categorysel'+plIndex);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  category = list.options[listindex].text;
  cat_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  while((i<max) && (category > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(category);
  else
  { newOption = new Option(category);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(category));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = cat_id;
  var mycats = eval("document.Mainform.mycats"+plIndex);
  mycats.value = mycats.value+','+cat_id;
}

function Removecategory(plIndex)
{ var list = document.getElementById('categorylist'+plIndex);
  var sel = document.getElementById('categorysel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  category = sel.options[selindex].text;
  cat_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  if(sel.options.length == 1)
  { alert('There must always be at least one selected category!');
    return; /* leave selection not empty */
  }
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (category > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(category);
  else
  { newOption = new Option(category);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(category));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = cat_id;
  if(classname == 'defcat')
  { sel.options[0].className = 'defcat';
    var default_cat = eval("document.Mainform.category_default"+plIndex);
	default_cat.value = sel.options[0].value;
  }
  var mycats = eval("document.Mainform.mycats"+plIndex);
  mycats.value = mycats.value.replace(','+cat_id, '');
}

function cat_change(num)
{ var list = eval("Mainform.categorylist"+num);
  var fld = document.getElementById('category_number'+num);
  fld.value = list.options[list.selectedIndex].value;
}

function catsel_change(num)
{ var list = eval("Mainform.categorysel"+num);
  var fld = document.getElementById('category_number'+num);
  fld.value = list.options[list.selectedIndex].value;
}

function change_category_number(num)
{ if(num=='x') /* in the searchbox */
  { var tmp = document.getElementById('category_number');
    var mysel = search_form.id_category;
  }
  else /* selecting in one of the edit rows (num = rownum) */
  { var tmp = document.getElementById('category_number'+num);
	var mysel = eval('Mainform.categorylist'+num);
  } 
  var mysellen = mysel.length;
  var myoptions = mysel.options;
  var val = tmp.value;
  if(isNaN(val))  /* if it is not non-numeric we do a text search among the categories on the value */
  { val = val.toLowerCase();
    if(catselectortype == 3)
	{ let found = false;
	  for(var i=1; i<mysellen; i++)
	    myoptions[i].className = "";
	  for(var i=1; i<mysellen; i++)
	  { let key = /^[- ]*/;
	    let txt = myoptions[i].text.replace(key,'').toLowerCase();
	    if (txt.substr(0, val.length) == val)
	    { if(!found)
	      { found = true;
		    mysel.selectedIndex = i;
		  }
		  myoptions[i].className = "selcat";			
	    }
	  }
	}
	else
    { for(var i=1; i<mysellen; i++)
	  { if (myoptions[i].text.substr(0, val.length).toLowerCase() == val)
	    { mysel.selectedIndex = i;
		  break;			
	    }
	  }
	}
  }  
  else
  { var found = false;
    for(var i=1; i<mysellen; i++)
	{ if(myoptions[i].value == val)
	  { mysel.selectedIndex = i;
		found = true;	
	  }
	}
	if(!found)
	  mysel.selectedIndex = 0;
  }
  if(num=='x') /* in the searchbox */
  { var tmp = document.getElementById('cat_order');
    if(mysel.selectedIndex == 0)
    { tmp.style.display = 'none';
	  document.search_form.order.selectedIndex = 1;
	  document.search_form.subcats.checked = false;
    }
    else
    { if (tmp.style.display == 'none')
	  { tmp.style.display = 'inline';
        document.search_form.order.selectedIndex = 0;		  
	  }
	}
  }
}

function fillCategories(idx,tmp)
{ var cats = tmp.split(',');
  var list = document.getElementById('categorylist'+idx);
  var sel = document.getElementById('categorysel'+idx);
  var defcatvalue = Mainform.category_default.value; /* modification for solo */
  for(var i=0; i< cats.length; i++)
  { cats[i]= striptags(cats[i]);
    for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == cats[i])
	  { list.selectedIndex = j;
		Addcategory(idx);
	  }
	  
	}
  }
  var defcat = -1;
  for(var k=0; k< sel.length; k++)
  { if(sel.options[k].value == defcatvalue)
    { defcat = k; break; 
	}
  }
  if(defcat >= 0)
  { sel.options[defcat].className = 'defcat';
  }
  else
  { alert("No default category found. First available taken.");
	sel.options[0].className = 'defcat';
	var default_cat = eval("document.Mainform.category_default");
    default_cat.value = sel.options[0].value;
  }
}

function striptags(mystr) /* remove html tags from text */
{ var regex = /(<([^>]+)>)/ig;
  return mystr.replace(regex, "");
}

function MakeCategoryDefault(idx)
{ var sel = document.getElementById('categorysel'+idx);
  for(var j=0; j< sel.length; j++)
	sel.options[j].className = '';
  sel.options[sel.selectedIndex].className = 'defcat';
  var default_cat = eval("document.Mainform.category_default"+idx);
  default_cat.value = sel.options[sel.selectedIndex].value;
}

function Addattachment(plIndex)
{ var list = document.getElementById('attachmentlist'+plIndex);
  var sel = document.getElementById('attachmentsel'+plIndex);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  attachment = list.options[listindex].text;
  attach_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  while((i<max) && (attachment > base[i].text)) i++;
  if(i==max)
    base[max] = new Option(attachment);
  else
  { newOption = new Option(attachment);
    if (document.createElement && (newOption = document.createElement('option'))) 
    { newOption.appendChild(document.createTextNode(attachment));
	}
    sel.insertBefore(newOption, base[i]);
  }
  base[i].value = attach_id;
  var myattachments = eval("document.Mainform.myattachments"+plIndex);
  myattachments.value = myattachments.value+','+attach_id;
}

function Removeattachment(plIndex)
{ var list = document.getElementById('attachmentlist'+plIndex);
  var sel = document.getElementById('attachmentsel'+plIndex);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  attachment = sel.options[selindex].text;
  attach_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  sel.options[selindex]=null;
  i=0;
  while((i<max) && (attachment > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(attachment);
  else
  { newOption = new Option(attachment);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(attachment));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = attach_id;
  var myattachments = eval("document.Mainform.myattachments"+plIndex);
  myattachments.value = myattachments.value.replace(','+attach_id, '');
}

function fillAttachments(idx,attas)
{ var list = document.getElementById('attachmentlist'+idx);
  var sel = document.getElementById('attachmentsel'+idx);
//  alert("PPP "+attas[0]);
  for(var i=0; i< attas.length; i++)
  { for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == attas[i])
	  { list.selectedIndex = j;
		Addattachment(idx);
	  }
	}
  }
}

/* this slightly modified function comes from admin.js in PS 1.6.11 */
function str2url(str)
{   str = str.toUpperCase();
	str = str.toLowerCase();
	if (allow_accented_chars)
		str = str.replace(/[^a-z0-9\s\'\:\/\[\]-]\\u00A1-\\uFFFF/g,'');
	else
	{
		/* Lowercase */
		str = str.replace(/[\u00E0\u00E1\u00E2\u00E3\u00E5\u0101\u0103\u0105\u0430]/g, 'a');
        str = str.replace(/[\u0431]/g, 'b');
		str = str.replace(/[\u00E7\u0107\u0109\u010D\u0446]/g, 'c');
		str = str.replace(/[\u010F\u0111\u0434]/g, 'd');
		str = str.replace(/[\u00E8\u00E9\u00EA\u00EB\u0113\u0115\u0117\u0119\u011B\u0435\u044D]/g, 'e');
        str = str.replace(/[\u0444]/g, 'f');
		str = str.replace(/[\u011F\u0121\u0123\u0433\u0491]/g, 'g');
		str = str.replace(/[\u0125\u0127]/g, 'h');
		str = str.replace(/[\u00EC\u00ED\u00EE\u00EF\u0129\u012B\u012D\u012F\u0131\u0438\u0456]/g, 'i');
		str = str.replace(/[\u0135\u0439]/g, 'j');
		str = str.replace(/[\u0137\u0138\u043A]/g, 'k');
		str = str.replace(/[\u013A\u013C\u013E\u0140\u0142\u043B]/g, 'l');
        str = str.replace(/[\u043C]/g, 'm');
		str = str.replace(/[\u00F1\u0144\u0146\u0148\u0149\u014B\u043D]/g, 'n');
		str = str.replace(/[\u00F2\u00F3\u00F4\u00F5\u00F8\u014D\u014F\u0151\u043E]/g, 'o');
        str = str.replace(/[\u043F]/g, 'p');
		str = str.replace(/[\u0155\u0157\u0159\u0440]/g, 'r');
		str = str.replace(/[\u015B\u015D\u015F\u0161\u0441]/g, 's');
		str = str.replace(/[\u00DF]/g, 'ss');
		str = str.replace(/[\u0163\u0165\u0167\u0442]/g, 't');
		str = str.replace(/[\u00F9\u00FA\u00FB\u0169\u016B\u016D\u016F\u0171\u0173\u0443]/g, 'u');
        str = str.replace(/[\u0432]/g, 'v');
		str = str.replace(/[\u0175]/g, 'w');
		str = str.replace(/[\u00FF\u0177\u00FD\u044B]/g, 'y');
		str = str.replace(/[\u017A\u017C\u017E\u0437]/g, 'z');
		str = str.replace(/[\u00E4\u00E6]/g, 'ae');
        str = str.replace(/[\u0447]/g, 'ch');
        str = str.replace(/[\u0445]/g, 'kh');
		str = str.replace(/[\u0153\u00F6]/g, 'oe');
		str = str.replace(/[\u00FC]/g, 'ue');
        str = str.replace(/[\u0448]/g, 'sh');
        str = str.replace(/[\u0449]/g, 'ssh');
        str = str.replace(/[\u044F]/g, 'ya');
        str = str.replace(/[\u0454]/g, 'ye');
        str = str.replace(/[\u0457]/g, 'yi');
        str = str.replace(/[\u0451]/g, 'yo');
        str = str.replace(/[\u044E]/g, 'yu');
        str = str.replace(/[\u0436]/g, 'zh');

		/* Uppercase */
		str = str.replace(/[\u0100\u0102\u0104\u00C0\u00C1\u00C2\u00C3\u00C4\u00C5\u0410]/g, 'A');
        str = str.replace(/[\u0411]/g, 'B');
		str = str.replace(/[\u00C7\u0106\u0108\u010A\u010C\u0426]/g, 'C');
		str = str.replace(/[\u010E\u0110\u0414]/g, 'D');
		str = str.replace(/[\u00C8\u00C9\u00CA\u00CB\u0112\u0114\u0116\u0118\u011A\u0415\u042D]/g, 'E');
        str = str.replace(/[\u0424]/g, 'F');
		str = str.replace(/[\u011C\u011E\u0120\u0122\u0413\u0490]/g, 'G');
		str = str.replace(/[\u0124\u0126]/g, 'H');
		str = str.replace(/[\u0128\u012A\u012C\u012E\u0130\u0418\u0406]/g, 'I');
		str = str.replace(/[\u0134\u0419]/g, 'J');
		str = str.replace(/[\u0136\u041A]/g, 'K');
		str = str.replace(/[\u0139\u013B\u013D\u0139\u0141\u041B]/g, 'L');
        str = str.replace(/[\u041C]/g, 'M');
		str = str.replace(/[\u00D1\u0143\u0145\u0147\u014A\u041D]/g, 'N');
		str = str.replace(/[\u00D3\u014C\u014E\u0150\u041E]/g, 'O');
        str = str.replace(/[\u041F]/g, 'P');
		str = str.replace(/[\u0154\u0156\u0158\u0420]/g, 'R');
		str = str.replace(/[\u015A\u015C\u015E\u0160\u0421]/g, 'S');
		str = str.replace(/[\u0162\u0164\u0166\u0422]/g, 'T');
		str = str.replace(/[\u00D9\u00DA\u00DB\u0168\u016A\u016C\u016E\u0170\u0172\u0423]/g, 'U');
        str = str.replace(/[\u0412]/g, 'V');
		str = str.replace(/[\u0174]/g, 'W');
		str = str.replace(/[\u0176\u042B]/g, 'Y');
		str = str.replace(/[\u0179\u017B\u017D\u0417]/g, 'Z');
		str = str.replace(/[\u00C4\u00C6]/g, 'AE');
        str = str.replace(/[\u0427]/g, 'CH');
        str = str.replace(/[\u0425]/g, 'KH');
		str = str.replace(/[\u0152\u00D6]/g, 'OE');
		str = str.replace(/[\u00DC]/g, 'UE');
        str = str.replace(/[\u0428]/g, 'SH');
        str = str.replace(/[\u0429]/g, 'SHH');
        str = str.replace(/[\u042F]/g, 'YA');
        str = str.replace(/[\u0404]/g, 'YE');
        str = str.replace(/[\u0407]/g, 'YI');
        str = str.replace(/[\u0401]/g, 'YO');
        str = str.replace(/[\u042E]/g, 'YU');
        str = str.replace(/[\u0416]/g, 'ZH');

		str = str.toLowerCase();

		str = str.replace(/[^a-z0-9\s\'\:\/\[\]-]/g,'');
	}
	str = str.replace(/[\u0028\u0029\u0021\u003F\u002E\u0026\u005E\u007E\u002B\u002A\u002F\u003A\u003B\u003C\u003D\u003E]/g, '');
	str = str.replace(/[\s\'\:\/\[\]-]+/g, ' ');

	// Add special char not used for url rewrite
	str = str.replace(/[ ]/g, '-');
	str = str.replace(/[\/\\"'|,;%]*/g, '');

	str = str.replace(/-$/,""); /* added */

	return str;
}
	  
function salesdetails(product)
{ window.open("product-sales.php?product="+product+"&startdate=<?php echo $startdate;?>&enddate=<?php echo $enddate;?>&id_shop=<?php echo $id_shop;?>","", "resizable,scrollbars,location,menubar,status,toolbar");
  return false;
}
	
function SubmitForm()
{ var res;
  var areas = Mainform.getElementsByTagName('textarea');
  for(var k=0;k<areas.length;k++)  
  { if(areas[k].name.substring(0,11) == "description") /* catches description_short too */
	{ if(areas[k].parentNode.childNodes[0].tagName == "DIV") 
      { //alert(tinyMCE.get(areas[k].name).getContent());
		tmp = tinyMCE.get(areas[k].name).getContent();
	    Mainform[areas[k].name].value = tidy_html(tmp).trim();
	  }
	  else /* no TinyMCE: check for integrity */
	  { tmp = tidy_html(areas[k].value).trim();
	    Mainform[areas[k].name].value = tmp;
	  }
	  if(areas[k].name.substring(0,17) == "description_short")
		  res = check_description("description_short", tmp, psdlimit, -1);
	  else
		  res = check_description("description", tmp, 21844, -1);
	  if(!res)
		throw new Error("Description field too long!"); 
	}
  } 

  if(Mainform.verbose.value == 'on') Mainform.verbose.value = 'true'; /* make same as product_edit */
  Mainform.action = 'product-proc.php?c=1&d=1';
  Mainform.submit();
}

function check_description(field, content, maxlength, line)
{ if((content.length > maxlength) && (maxlength > 0))
  { var linetext = "";
	if(line >= 0)
	  linetext = ' on line '+line+' for product '+get_product_id(line)+'. See mass edit function.';
    if(field == "description_short")
	  return confirm("The description_short field is too long. The length is "+content.length+". The maximum allowed length is "+psdlimit+linetext+". This can cause problems. If you continue there will be no further warnings. Are you sure you want to continue?");
	else
	  return confirm("The description field is too long. The length is "+content.length+". The maximum allowed length is 21844"+linetext+". This can cause problems. If you continue there will be no further warnings. Are you sure you want to continue?");
  }
  return -1;
}

/* remove unmatched closing tags; close unmatched opening tags (like <div> and <b>) */
/* every time you submit a field that can contain html tags it is moved through this function */
function tidy_html(html) {
    var d = document.createElement('div');
    d.innerHTML = html;
    return d.innerHTML;
}

/* getpath() takes a string like '189' and returns something like '/1/8/9' */
function getpath(name)
{ str = '';
  for (var i=0; i<name.length; i++)
  { str += '/'+name[i];
  }
  return str;
}

function init()
{ if(document.getElementsByName("Mainform")) /* will not run when product is empty and second half of page isn't created */
    fillCategories('', Mainform.mycats_orig.value);
}

</script>
</head>

<body onload="init()">
<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<div id="dhtmlwindowholder"><span style="display:none">.</span></div>
<?php
print_menubar();
echo '
<form name="search_form" method="get" action="product-solo.php">
<table class="triplehome" cellpadding=0 cellspacing=0>
<tr><td><table class="triplehome" cellpadding=0 cellspacing=0>
<tr><td colspan="3" class="headline"><a href="product-solo.php">Solo Product Edit</a></td></tr>
<tr><td>Product id: </td><td><input size=2 name=id_product value="'.$id_product.'"></td>
<td><input type=submit value="Search" rowspan="3"></td></tr>
<tr><td>Language: </td><td><select name="id_lang" style="margin-top:5px">';
	  $query=" select * from ". _DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  $langnum = mysqli_num_rows($res);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
echo '</select></td><td>Default='.$def_lang.'-'.$def_langname.'.</td></tr>';
$sres = dbquery("select GROUP_CONCAT(id_shop ORDER BY id_shop) AS shops from ". _DB_PREFIX_."product_shop WHERE id_product='".$id_product."' GROUP BY id_product");
list($prodshops) = mysqli_fetch_row($sres); 
echo '<tr><td>Shop: </td><td><select name="id_shop">'.$shopblock.'</select> &nbsp; &nbsp; ('.$prodshops.')</td>';
if((sizeof($shops) > 1) || ($langnum > 1))
{ echo '<td>Show texts for <select name=textshow><option value="activeonly">selected language</option>';
  if(sizeof($shops) > 1)
  { if($textshow == "allshops") $selected = "selected"; else $selected = "";
	echo '<option value="allshops" '.$selected.'>all shops</option>';
  }
  if($langnum > 1)
  { if($textshow == "alllangs") $selected = "selected"; else $selected = "";
	echo '<option value="alllangs" '.$selected.'>all languages</option>';
  }
  if((sizeof($shops) > 1) && ($langnum > 1))
  { if($textshow == "langshops") $selected = "selected"; else $selected = "";
    echo '<option value="langshops" '.$selected.'>all languages and shops</option>';
	if($textshow == "shoplangs") $selected = "selected"; else $selected = "";
	echo '<option value="shoplangs" '.$selected.'>all shops and languages</option>';
  }
  echo '</select></td></tr>';
}
else
  echo '<td></td></tr>';	
echo '</table></form></td><td><iframe name=tank width="230" height="95"></iframe></td></tr></table>';

if($id_product == "") die("</body></html>");
if($prodshops == "") die("<b>This product doesn't exist</b></body></html>");


echo "You are editing product data for shop nr ".$id_shop;
if($share_stock == 1) echo " - stock group ".$shop_group_name;
echo ". Country=".$countryname." (used for VAT grouping and calculations)<hr>";

 // "*********************************************************************";

$queryterms = "p.*,ps.*,pl.*,t.id_tax,t.rate,m.name AS manufacturer, cl.name AS catname, cl.link_rewrite AS catrewrite, pld.name AS originalname, s.quantity, s.depends_on_stock";

$query = " from ". _DB_PREFIX_."product_shop ps left join ". _DB_PREFIX_."product p on p.id_product=ps.id_product";
$query.=" left join ". _DB_PREFIX_."product_lang pl on pl.id_product=p.id_product and pl.id_lang='".$id_lang."' AND pl.id_shop='".$id_shop."'";
$query.=" left join ". _DB_PREFIX_."product_lang pld on pld.id_product=p.id_product and pld.id_lang='".$def_lang."' AND pld.id_shop='".$id_shop."'"; /* This gives the name in the shop language instead of the selected language */
$query.=" left join ". _DB_PREFIX_."manufacturer m on m.id_manufacturer=p.id_manufacturer";
$query.=" left join ". _DB_PREFIX_."category_lang cl on cl.id_category=ps.id_category_default AND cl.id_lang='".$id_lang."' AND cl.id_shop = '".$id_shop."'";
if($share_stock == 0)
  $query.=" left join ". _DB_PREFIX_."stock_available s on s.id_product=p.id_product AND s.id_shop = '".$id_shop."' AND id_product_attribute='0'";
else
  $query.=" left join ". _DB_PREFIX_."stock_available s on s.id_product=p.id_product AND s.id_shop_group = '".$id_shop_group."' AND id_product_attribute='0'";
$query.=" left join ". _DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state='0'";
$query.=" left join ". _DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
$query.=" left join ". _DB_PREFIX_."tax_lang tl on t.id_tax=tl.id_tax AND tl.id_lang='".$def_lang."'";

if(in_array("accessories", $input["fields"]))
{ $query.=" LEFT JOIN ( SELECT GROUP_CONCAT(id_product_2) AS accessories, id_product_1 FROM "._DB_PREFIX_."accessory GROUP BY id_product_1 ) a ON a.id_product_1=p.id_product";
  $queryterms .= ", accessories";
}

if(in_array("visits", $input["fields"]))
{ $query .= " LEFT JOIN ( SELECT pg.id_object, count(*) AS visitcount FROM ". _DB_PREFIX_."connections c LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type='1' AND pg.id_page = c.id_page AND c.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(c.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) <= TO_DAYS('".$input['enddate']."')";
  $queryterms .= ", visitcount ";
  $query .= " GROUP BY pg.id_object ) v ON p.id_product=v.id_object";
}
if(in_array("visitz", $input["fields"]))
{ $query .= " LEFT JOIN ( SELECT pg.id_object, sum(counter) AS visitedpages FROM ". _DB_PREFIX_."page_viewed v LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type='1' AND pg.id_page = v.id_page AND v.id_shop='".$id_shop."'";
  $query .= " LEFT JOIN ". _DB_PREFIX_."date_range d ON d.id_date_range = v.id_date_range";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(d.time_start) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(d.time_end) <= TO_DAYS('".$input['enddate']."')";
  $queryterms .= ", visitedpages ";
  $query .= " GROUP BY v.id_page ) w ON p.id_product=w.id_object";
}
if(in_array("revenue", $input["fields"]) OR in_array("salescnt", $input["fields"]) OR in_array("orders", $input["fields"]))
{ $query .= " LEFT JOIN ( SELECT product_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ";
  $query .= " ROUND(SUM(total_price_tax_incl),2) AS revenue, ";
  $query .= " count(DISTINCT d.id_order) AS ordercount, count(DISTINCT o.id_customer) AS buyercount FROM ". _DB_PREFIX_."order_detail d";
  $query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order = d.id_order AND o.id_shop=d.id_shop";
  $query .= " WHERE d.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".$input['enddate']."')";
  $query .= " AND o.valid=1";
  $query .= " GROUP BY d.product_id ) r ON p.id_product=r.product_id";
  $queryterms .= ", revenue, r.quantity AS salescount, ordercount, buyercount ";
}

$query.=" WHERE ps.id_shop='".$id_shop."' AND p.id_product='".$id_product."'";

  $statfields = array("salescnt", "revenue","orders","buyers","visits","visitz");
//  $statz = array("salescount", "revenue","ordercount","buyercount","visitcount","visitedpages"); /* here pro memori: moved up to search_fld definition */
 
  $query= "select SQL_CALC_FOUND_ROWS ".$queryterms.$query; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */
  $res=dbquery($query);

// echo $query;


  // "*********************************************************************";
  echo '<div id="dhwindow" style="display:none"></div>';
  echo '<form name="Mainform" method=post><input type=hidden name=reccount value="1"><input type=hidden name=id_lang value="'.$id_lang.'">';
  echo '<input type=hidden name=id_shop value='.$id_shop.'><input type=hidden name=submittedrow>';
  if((($textshow == "alllangs") || ($textshow == "langshops") || ($textshow == "shoplangs")) && (sizeof($langcodes) > 1))
  { $langkeys = $langisos = array();
	foreach($langcodes AS $langkey => $langcode)
	{ $langkeys[] = $langkey;
	  $langisos[] = $langcode;
	}
	echo '<input type=hidden name=extralangfields value="'.implode(",",$langkeys).'">
	<input type=hidden name=extralangcodes value="'.implode(",",$langisos).'">';
  }
  if((($textshow == "allshops") || ($textshow == "langshops") || ($textshow == "shoplangs")) && (sizeof($shopids) > 1))
  { $temparr = array_diff($shopids, array($id_shop));
    echo '<input type=hidden name=extrashopfields value="'.implode(",",$temparr).'">';
  }
  
  echo '<input type=hidden name=featuresset>';
  $myprodshops = explode(",",$prodshops);
  if(sizeof($shops)>1)
  { echo '<table class="triplemain"><tr><td>You have more than one shop. Do you want to apply your changes to other shops too?<br>
	<input type="radio" name="allshops" value="0" '.($prestools_settings["updateallshops"]==0 ? 'checked': '').' onchange="change_allshops(\'0\')"> No ';
	if($share_stock == 1)
	{ echo ' &nbsp; <input type="radio" name="allshops" value="2" '.($prestools_settings["updateallshops"]==1 ? 'checked': '').' onchange="change_allshops(\'2\')"> Yes, to the shop group';
	}
	else if($prestools_settings["updateallshops"]==1) echo '<script>alert("You set an invalid value for $updateallshops!!!");</script>';
	echo ' &nbsp; <input type="radio" name="allshops" value="1" '.($prestools_settings["updateallshops"]==2 ? 'checked': '').' onchange="change_allshops(\'1\')"> Yes, to all shops<br>
		(some stock related fields cannot be shared this way)
	</td></tr></table> ';
  }
  else
	echo '<input type=hidden name=allshops value=0>';
  echo '<input type=checkbox name=verbose>verbose &nbsp; &nbsp; &nbsp; <input type=button value="Submit all" onClick="return SubmitForm();" style="display:inline-block">';
  
  if(mysqli_num_rows($res) == 0)
  { $res = dbquery("SELECT * FROM "._DB_PREFIX_."product_shop WHERE id_product='".$id_product."'");
    if(mysqli_num_rows($res) > 0)
	{ echo "<p>This product number exist, but not in this shop! It is present in shops ";
	  while($row = mysqli_fetch_array($res))
		  echo '<a href="product-solo.php?id_product='.$id_product.'&id_lang='.$id_lang.'&id_shop='.$row['id_shop'].'">'.$row['id_shop'].'</a> ';
	}
    else
	  echo "<p>This is an unknown product number!";
    return;
  }
  $datarow=mysqli_fetch_array($res);
  
    /* compose url */
	if ($rewrite_settings == '1')
	{ // customized link. Prestashop code in getProductLink() in classes\Link.php that refers to classes\Dispatcher.php
		$produrl = $route_product_rule;
		if($datarow["ean13"] == "0") $datarow["ean13"] = "";
		foreach ($keywords as $key) 
		{ if($key == "id") 	$keyvalue = $datarow["id_product"];
		  else if($key == "rewrite") 	$keyvalue = $datarow["link_rewrite"];				  
		  else if($key == "category") 	$keyvalue = $datarow["catrewrite"];
		  else if($key == "categories") /* multilevel cats like in /cat1/cat2/cat3/product */
		  { $tmp = array();
			$tid = $datarow["id_category_default"];
			while($category_parents[$category_parents[$tid]] !=0)
			{ $tmp[] = $category_rewrites[$tid];
			  $tid = $category_parents[$tid];
			}
			$keyvalue = implode('/', array_reverse($tmp));
		  }
		  else if($key == "ean13") 		$keyvalue = $datarow["ean13"];
		  else if($key == "manufacturer")	$keyvalue = $datarow["manufacturer"];
		  else if($key == "meta_keywords") 	$keyvalue = $datarow["meta_keywords"];
		  else if($key == "meta_title")	$keyvalue = $datarow["meta_title"];
		  else if($key == "price")	$keyvalue = $datarow["price"];			  
		  else if($key == "reference") 	$keyvalue = $datarow["reference"];				  
		  else if($key == "rewrite") 	$keyvalue = $datarow["link_rewrite"];

		  if($key != "categories")
			$keyvalue = str2url($keyvalue);
		  if ($keyvalue != "") 
		  { $replace = $transform_keywords[$key]['prepend'].$keyvalue.$transform_keywords[$key]['append'];
		  }
		  else 
		  { $replace = '';
		  }
		  $produrl = preg_replace('#\{([^{}]*:)?'.$key.'(:[^{}]*)?\}#', $replace, $produrl);
		}
		$produrl = preg_replace('#\{([^{}]*:)?[a-z0-9_]+?(:[^{}]*)?\}#', '', $produrl);
		$link = get_base_uri().$langinsert.$produrl;
	}
	else
      $link = get_base_uri()."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang;
	   /* end of name */
	   
	/* query conditions for multilang/multishop display fields */
	if($textshow == "activeonly") $multicond = " AND id_shop=".$id_shop." AND id_lang=".$id_lang;
	else if($textshow == "allshops") $multicond = " AND id_lang=".$id_lang;
	else if($textshow == "alllangs") $multicond = " AND id_shop=".$id_shop;		
	else if($textshow == "shoplangs") $multicond = " ORDER BY id_shop";
	else if($textshow == "langshops") $multicond = " ORDER BY id_lang";

  echo '<a href='.$link.' style="margin-left: 303px" target="_blank">View</a>';
  echo '<div id="testdiv"><table id="Maintable" class="triplemain">';

	$x = 0;
    for($i=0; $i< sizeof($fields); $i++)
    { if($fields[$i]=="active") continue; /* will be next to id */ 
	  if(($fields[$i] == "shopz") && (sizeof($shops) == 1)) continue;
	  if(($fields[$i]=="mpn") && (version_compare(_PS_VERSION_ , "1.7.7", "<"))) continue;
	  if(($fields[$i]=="isbn") && (version_compare(_PS_VERSION_ , "1.7.0", "<"))) continue;  

	  $sorttxt = "";
      $color = "";
	  if (!in_array($fields[$i], array("meta_description","meta_keywords","link_rewrite","meta_title","description","description_short","name","available_later","available_now")))
	    echo "<tr><td>".$fields[$i]."</td>";
 
      if($fields[$i] == "priceVAT")
		$myvalue =  number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '');
	  else if ($i == 0)
	    $myvalue  = $id_product;
      else if (($fields[$i] != "carrier") && ($fields[$i] != "tags") && ($fields[$i] != "discount") && ($fields[$i] != "combinations") && ($fields[$i] != "supplier") && ($fields[$i] != "attachmnts"))
        $myvalue = $datarow[$field_array[$fields[$i]][2]];
      
	  if($i == 0) /* id */
      {   echo "<td>";
	      if(file_exists("../grider.php")) /* check that we are in subdir of the admin directory */
			echo "<a href='".preg_replace("/^..\//","",$triplepath)."index.php?controller=AdminProducts&id_product=".$myvalue."&updateproduct' title='".$datarow['originalname']."' target='_blank'>".addspaces($id_product)."</a>";
		  else
			echo $id_product;
		  echo '<input type=hidden name="id_product" value="'.$id_product.'">';
		  if($datarow["active"]==1) $checked="checked"; else $checked="";
	      echo '<div style="float: right; ">active <input type=hidden name="active" id="active" value="0" /><input type=checkbox name="active" id="active" value="1" '.$checked.' /></div>';
		  echo '</td>';
	  } 
	  else if ($fields[$i] == "accessories")
	  { echo "<td srt='".$myvalue."'>";
        if($myvalue === NULL) $myvalue="";
	    $accs = explode(",",$myvalue);
		$z=0;
	    foreach($accs AS $acc)
		{ if($acc == "") continue;
		  if($z++ > 0) echo ",";
		  echo '<nobr><a title="'.$acc.'" href="product-solo.php?id_product='.$acc.'&id_lang='.$id_lang.'&id_shop='.$id_shop.'" target=_blank>'.get_product_name($acc)."</a></nobr>";
//		  echo "<a title='".get_product_name($acc)."' href='#' onclick='return false;' style='text-decoration: none;'>".$acc."</a>";
		}
	    echo "</td>";
	  }
	  else if ($fields[$i] == "attachmnts")
      { $cquery = "SELECT a.file_name, a.file, a.mime, l.name, p.id_attachment FROM ". _DB_PREFIX_."product_attachment p";
		$cquery .= " LEFT JOIN ". _DB_PREFIX_."attachment a ON a.id_attachment=p.id_attachment";
	    $cquery .= " LEFT JOIN ". _DB_PREFIX_."attachment_lang l ON a.id_attachment=l.id_attachment AND l.id_lang='".$id_lang."'";
	    $cquery .= " WHERE id_product='".$datarow['id_product']."'";
		echo "<td>";
		$cres=dbquery($cquery);
		$z=0;
		while ($crow=mysqli_fetch_array($cres)) 
		{	if($z++ > 0) echo "<br>";
			echo "<a class='attachlink' title='".$crow['id_attachment']."' href='downfile.php?filename=".$crow["file_name"]."&filecode=".$crow["file"]."&download_dir=".$download_dir."&mime=".$crow["mime"]."' target=_blank'>".$crow['name']."</a>";
		}
	    echo "</td>";
		mysqli_free_result($cres);
      }
	  else if ($fields[$i] == "availorder")
	  { $available_for_order = $datarow['available_for_order'];
		$show_price = $datarow['show_price'];
	    echo '<td><select name="availorder">';
		if($available_for_order == 1)
		{ echo '<option value="0" selected>available</option><option value="1">show price only</option>
		   <option value="2">not available</option>';
		}
		else
		{ echo '<option value="0" selected>available</option>';
		  if($show_price == 1)
		    echo '<option value="1" selected>show price only</option><option value="2">not available</option>';
		  else 
		    echo '<option value="1">show price only</option><option value="2" selected>not available</option>';
		}
		echo '</select></td>';
	  }
	  else if ($fields[$i] == "carrier")
      { $cquery = "SELECT id_carrier_reference FROM ". _DB_PREFIX_."product_carrier WHERE id_product=".$datarow['id_product']." AND id_shop='".$id_shop."' LIMIT 1";
		$cres=dbquery($cquery);
		if(mysqli_num_rows($cres) != 0)
		{ $cquery = "SELECT id_reference, cr.name FROM ". _DB_PREFIX_."product_carrier pc";
		  $cquery .= " LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0";
		  $cquery .= " WHERE id_product='".$datarow['id_product']."' AND id_shop='".$id_shop."' ORDER BY cr.name";
		  $cres=dbquery($cquery);
		  echo "<td><table border=1 id='carriers".$x."'>";
		  while ($crow=mysqli_fetch_array($cres)) 
		  { echo "<tr><td id='".$crow['id_reference']."'>".$crow['name']."</td></tr>";
		  }
		  echo "</table></td>";
		}
		else
		  echo "<td></td>";
		mysqli_free_result($cres);
	  }
      else if ($fields[$i] == "category")
	  { echo "<td ".$sorttxt.">";
		echo str_replace("CQX","",$categoryblock0).$categoryblock1.str_replace("CQX","",$categoryblock2);
	    $cquery = "select GROUP_CONCAT(id_category ORDER BY id_category) AS categories from ". _DB_PREFIX_."category_product WHERE id_product='".$datarow['id_product']."'";
		$cres=dbquery($cquery);
		$crow=mysqli_fetch_array($cres);
		echo '<input type=hidden name=mycats>';
		echo '<input type=hidden name=mycats_orig value="'.$crow["categories"].'">';
		echo '<input type=hidden name=category_default value="'.$myvalue.'">';
		mysqli_free_result($cres); 
	    echo "</td>";		
	  }
	  else if ($fields[$i] == "combinations")
      { $cquery = "SELECT count(*) AS counter FROM ". _DB_PREFIX_."product_attribute";
	    $cquery .= " WHERE id_product='".$datarow['id_product']."'";
		$cres=dbquery($cquery);
		$crow=mysqli_fetch_array($cres);
		echo "<td>";
		if($crow["counter"] != 0)
			echo '<a href="combi-edit.php?id_product='.$datarow['id_product'].'&id_shop='.$id_shop.'" title="Click here to edit combinations in separate window" target="_blank" style="background-color:#99aaee; text-decoration:none">&nbsp; '.$crow["counter"].' &nbsp;</a>';
		echo "</td>";
		mysqli_free_result($cres);
      }
	  else if (($fields[$i] == "description")||($fields[$i] == "description_short"))
	  {  /* the useTinyMCE functions will not work when there is a space between </textarea> and <div> */

		$dquery = "SELECT ".$field_array[$fields[$i]][0].",id_shop,id_lang FROM ". _DB_PREFIX_."product_lang WHERE id_product=".$id_product.$multicond;
		$dres=dbquery($dquery);
		while ($drow=mysqli_fetch_array($dres)) 
		{ $comment = "";
		  if($textshow == "allshops") $comment = $drow["id_shop"];
		  if($textshow == "alllangs") $comment = $langcodes[$drow["id_lang"]];	  
		  if($textshow == "shoplangs") $comment = $drow["id_shop"]."-".$langcodes[$drow["id_lang"]];
		  if($textshow == "langshops") $comment = $langcodes[$drow["id_lang"]]."-".$drow["id_shop"];
		  if(($drow["id_shop"] == $id_shop) && ($drow["id_lang"] == $id_lang))		  
			$xname = $field_array[$fields[$i]][0];
		  else if($drow["id_shop"] == $id_shop)
			$xname=$field_array[$fields[$i]][0].'_'.$langcodes[$drow["id_lang"]];
		  else if($drow["id_lang"] == $id_lang)
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].'xx';
		  else /* both different */
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].$langcodes[$drow["id_lang"]];
		  echo "<tr><td>".$fields[$i].'<br>'.$comment.'</td><td><textarea rows=4 cols=40 name="'.$xname.'" id="'.$xname.'">'.$drow[$field_array[$fields[$i]][0]].'</textarea>';
		  echo '<div class="TinyLine"><a href="#" onclick="useTinyMCE(this, \''.$xname.'\'); return false;">TinyMCE</a>&nbsp;<a href="#" onclick="useTinyMCE2(this,\''.$xname.'\'); return false;">TinyMCE-deluxe</a></div></td>';
		}
	  }
	  else if ($fields[$i] == "discount")
      { $dquery = "SELECT id_specific_price, id_product_attribute, sp.id_currency,sp.id_country, sp.id_group, sp.id_customer, sp.price, sp.from_quantity,sp.reduction,sp.reduction_type,sp.from,sp.to, id_shop, cu.iso_code AS currency";
//	    $dquery .= ", c.name AS country,g.name AS groupname, cu.name AS currency";
		$dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
//		$dquery.=" left join ". _DB_PREFIX_."group_lang g on g.id_group=sp.id_group AND g.id_lang='".$id_lang."'";
//		$dquery.=" left join ". _DB_PREFIX_."country_lang c on sp.id_country=c.id_country AND c.id_lang='".$id_lang."'";
		$dquery.=" left join ". _DB_PREFIX_."currency cu on sp.id_currency=cu.id_currency";		
	    $dquery .= " WHERE sp.id_product='".$datarow['id_product']."'";
//		$dquery .= " AND (sp.id_shop='".$id_shop."' OR sp.id_shop='0')";
		$dres=dbquery($dquery);
		echo "<td><table border=1 id='discount".$x."'>";
		while ($drow=mysqli_fetch_array($dres)) 
		{ echo '<tr specid='.$drow["id_specific_price"].'>';
/* 						0				1		2		3		  4			5			6		7				8			9	 		10	11*/
 /* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_type, from, to */
		  if($drow["id_shop"] == "0") $drow["id_shop"] = "";
		  echo "<td>".$drow["id_shop"]."</td>";
		  if($drow["id_product_attribute"] == "0") $drow["id_product_attribute"] = "";
		  echo "<td>".$drow["id_product_attribute"]."</td>";
		  echo "<td>".$drow["currency"]."</td>";
		  echo "<td>".$drow["id_country"]."</td>";
		  echo "<td>".$drow["id_group"]."</td>";

		  if($drow["id_customer"] == "0") $drow["id_customer"] = "";
		  echo "<td>".$drow["id_customer"]."</td>";
		  if($drow["price"] == -1) $drow["price"] = "";
		  else $drow["price"] = $drow["price"] * 1; /* remove trailing zeroes */
		  echo "<td>".$drow["price"]."</td>";
		  echo "<td style='background-color:#FFFFAA'>".$drow["from_quantity"]."</td>";
		  if($drow["reduction_type"] == "percentage")
			$drow["reduction"] = $drow["reduction"] * 100;
		  else 
		    $drow["reduction"] = $drow["reduction"] * 1;
		  echo "<td>".$drow["reduction"]."</td>";
		  if($drow["reduction_type"] == "amount") $drow["reduction_type"] = "amt"; else $drow["reduction_type"] = "pct";
		  echo "<td>".$drow["reduction_type"]."</td>"; 
		  if($drow["from"] == "0000-00-00 00:00:00") $drow["from"] = "";
		  else if(substr($drow["from"],11) == "00:00:00") $drow["from"] = substr($drow["from"],0,10);
		  echo "<td>".$drow["from"]."</td>";
		  if($drow["to"] == "0000-00-00 00:00:00") $drow["to"] = ""; 
		  else if(substr($drow["to"],11) == "00:00:00") $drow["to"] = substr($drow["to"],0,10);
		  echo "<td>".$drow["to"]."</td>";
		  echo "</tr>";
		}
		echo "</table></td>";
		mysqli_free_result($dres);
      }
      else if ($fields[$i] == "image")
      { $iquery = "SELECT id_image,cover FROM ". _DB_PREFIX_."image WHERE id_product='".$datarow['id_product']."' ORDER BY position";
		$ires=dbquery($iquery);
		$id_image = 0;
		$imagelist = "";
		$first=0;
		echo "<td>";
		while ($irow=mysqli_fetch_array($ires)) 
		{	$border = '';
		    if($irow['cover'] == 1)
		      $border = ' style="border:1px"';
			echo get_product_image($datarow['id_product'],$irow['id_image'],$irow['id_image'])." ";
		}
		echo "</td>";
		mysqli_free_result($ires);
      }
      else if (in_array($fields[$i], array("link_rewrite","meta_title")))
	  { $dquery = "SELECT ".$field_array[$fields[$i]][0].",id_shop,id_lang FROM ". _DB_PREFIX_."product_lang WHERE id_product=".$id_product.$multicond;
		$dres=dbquery($dquery);
		while ($drow=mysqli_fetch_array($dres)) 
		{ $comment = "";
		  if($textshow == "allshops") $comment = $drow["id_shop"];
		  if($textshow == "alllangs") $comment = $langcodes[$drow["id_lang"]];	  
		  if($textshow == "shoplangs") $comment = $drow["id_shop"]."-".$langcodes[$drow["id_lang"]];
		  if($textshow == "langshops") $comment = $langcodes[$drow["id_lang"]]."-".$drow["id_shop"];
		  if(($drow["id_shop"] == $id_shop) && ($drow["id_lang"] == $id_lang))		  
			$xname = $field_array[$fields[$i]][0];
		  else if($drow["id_shop"] == $id_shop)
			$xname=$field_array[$fields[$i]][0].'_'.$langcodes[$drow["id_lang"]];
		  else if($drow["id_lang"] == $id_lang)
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].'xx';
		  else /* both different */
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].$langcodes[$drow["id_lang"]];
		  echo "<tr><td>".$fields[$i].'<br>'.$comment.'</td><td><input name="'.$xname.'" value="'.htmlentities($drow[$field_array[$fields[$i]][0]]).'" style="width:400px">';
		}
	  }
      else if ($fields[$i] == "manufacturer") 
 	  { $mquery = "SELECT id_manufacturer,name FROM "._DB_PREFIX_."manufacturer ORDER BY name";
		$mres=dbquery($mquery);
		echo '<td><select name="manufacturer"><option value="0">No manufacturer</option>';
		while($mrow = mysqli_fetch_array($mres))
		{ $selected = '';
		  if($myvalue == $mrow['name']) $selected = ' selected';
		  echo '<option value="'.$mrow['id_manufacturer'].'"'.$selected.'>'.$mrow['name'].'</option>';
		}   
		echo "</select></td>";
	  }
	  else if (in_array($fields[$i], array("meta_description","meta_keywords")))
	  { $dquery = "SELECT ".$field_array[$fields[$i]][0].",id_shop,id_lang FROM ". _DB_PREFIX_."product_lang WHERE id_product=".$id_product.$multicond;
		$dres=dbquery($dquery);
		while ($drow=mysqli_fetch_array($dres)) 
		{ $comment = "";
		  if($textshow == "allshops") $comment = $drow["id_shop"];
		  if($textshow == "alllangs") $comment = $langcodes[$drow["id_lang"]];	  
		  if($textshow == "shoplangs") $comment = $drow["id_shop"]."-".$langcodes[$drow["id_lang"]];
		  if($textshow == "langshops") $comment = $langcodes[$drow["id_lang"]]."-".$drow["id_shop"];
		  if(($drow["id_shop"] == $id_shop) && ($drow["id_lang"] == $id_lang))		  
			$xname = $field_array[$fields[$i]][0];
		  else if($drow["id_shop"] == $id_shop)
			$xname=$field_array[$fields[$i]][0].'_'.$langcodes[$drow["id_lang"]];
		  else if($drow["id_lang"] == $id_lang)
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].'xx';
		  else /* both different */
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].$langcodes[$drow["id_lang"]];
		  echo "<tr><td>".$fields[$i].'<br>'.$comment.'</td><td><textarea rows=4 cols=40 name="'.$xname.'">'.$drow[$field_array[$fields[$i]][0]].'</textarea>';
		}
	  }
	  else if (in_array($fields[$i], array("name", "available_now", "available_later")))
	  { $dquery = "SELECT ".$field_array[$fields[$i]][0].",id_shop,id_lang FROM ". _DB_PREFIX_."product_lang WHERE id_product=".$id_product.$multicond;
		$dres=dbquery($dquery);
		while ($drow=mysqli_fetch_array($dres)) 
		{ $comment = "";
		  echo "
		  ";
		  if($textshow == "allshops") $comment = $drow["id_shop"];
		  if($textshow == "alllangs") $comment = $langcodes[$drow["id_lang"]];	  
		  if($textshow == "shoplangs") $comment = $drow["id_shop"]."-".$langcodes[$drow["id_lang"]];
		  if($textshow == "langshops") $comment = $langcodes[$drow["id_lang"]]."-".$drow["id_shop"];
		  if(($drow["id_shop"] == $id_shop) && ($drow["id_lang"] == $id_lang))		  
			$xname = $field_array[$fields[$i]][0];
		  else if($drow["id_shop"] == $id_shop)
			$xname=$field_array[$fields[$i]][0].'_'.$langcodes[$drow["id_lang"]];
		  else if($drow["id_lang"] == $id_lang)
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].'xx';
		  else /* both different */
			$xname=$field_array[$fields[$i]][0].'_'.$drow["id_shop"].$langcodes[$drow["id_lang"]];
		  if($fields[$i] == "name")
		    echo "<tr><td>".$fields[$i].'<br>'.$comment.'</td><td><textarea rows=4 cols=40 name="'.$xname.'" id="'.$xname.'" onchange="name_change(this);">'.$drow[$field_array[$fields[$i]][0]].'</textarea>';
		  else 
			echo "<tr><td>".$fields[$i].'<br>'.$comment.'</td><td><textarea rows=4 cols=40 name="'.$xname.'" id="'.$xname.'">'.$drow[$field_array[$fields[$i]][0]].'</textarea>';
		  
		}
	  }
      else if (($fields[$i] == "price") || ($fields[$i] == "priceVAT"))
	  {  echo '<td><input name="'.$field_array[$fields[$i]][0].'" onkeyup=price_change(this) value="'.$myvalue.'"></td>';
	  }
	  else if(($fields[$i]=="on_sale") || ($fields[$i]=="online_only"))
	  { if($myvalue==1) $checked="checked"; else $checked="";
		if($fields[$i]=="active") $field = "active";
		else if($fields[$i]=="on_sale") $field = "on_sale";
		else if($fields[$i]=="online_only") $field = "online_only";		
	    echo '<td><input type=hidden name="'.$field.'" id="'.$field.'" value="0" /><input type=checkbox name="'.$field.'" id="'.$field.'" value="1" '.$checked.' /></td>';
	  }
	  else if ($fields[$i] == "quantity")
	  { if($datarow["depends_on_stock"] == "1")
          echo '<td style="background-color:yellow">'.$myvalue.' (depends on stock)</td>';	  
		else 
		{ $aquery = "SELECT id_product_attribute FROM ". _DB_PREFIX_."product_attribute WHERE id_product=".$datarow['id_product'];
		  $ares=dbquery($aquery);
		  $attrs = array();	
		  if(mysqli_num_rows($ares) != 0)
            echo '<td style="background-color:#FF8888">'.$myvalue.'</td>';	 
		  else
            echo '<td><input name="'.$field_array[$fields[$i]][0].'" value="'.$myvalue.'"></td>';
		}
	  }
	  else if ($fields[$i] == "revenue")
      { echo "<td><a href onclick='return salesdetails(".$datarow['id_product'].")' title='show salesdetails'>".$datarow['revenue']."</a></td>";
      }
	  else if ($fields[$i] == "shopz")
      { echo "<td>".$prodshops."</td>";
      }
	  else if ($fields[$i] == "supplier")
      { $squery = "SELECT id_product_supplier,ps.id_supplier,id_product_attribute FROM ". _DB_PREFIX_."product_supplier ps";
	    $squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
		$squery .= " WHERE id_product=".$datarow['id_product']." AND id_product_attribute=0 ORDER BY s.name";
		$sres=dbquery($squery);
	    $sups = array();
		while ($srow=mysqli_fetch_array($sres))
		    $sups[] = $srow["id_supplier"];

	    $aquery = "SELECT id_product_attribute FROM ". _DB_PREFIX_."product_attribute WHERE id_product=".$datarow['id_product'];
		$ares=dbquery($aquery);
		$attrs = array();	
		if(mysqli_num_rows($ares) == 0)
		   $attrs[] = 0;
		else
		{ while ($arow=mysqli_fetch_array($ares))
		    $attrs[] = $arow["id_product_attribute"];
		}

		echo '<td sups="'.implode(",",$sups).'" attrs="'.implode(",",$attrs).'">';
			
		if($attrs[0] == 0)
		{ $has_combinations = false;
		  echo '<table border=1 class="supplier" id="suppliers0s'.$x.'" title="">';
		  $squery = "SELECT ps.id_product_supplier,s.id_supplier,ps.id_product_attribute,product_supplier_reference AS reference,product_supplier_price_te AS supprice FROM ". _DB_PREFIX_."product_supplier ps";
		  $squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
		  $squery .= " WHERE id_product=".$datarow['id_product']." AND (ps.id_supplier != 0) ORDER BY s.name";
		  $sres=dbquery($squery); /* in 1.6 product_supplier has */
		  $rowcount = mysqli_num_rows($sres);
		  $xx=0;
		  while ($srow=mysqli_fetch_array($sres)) 
		  { echo "<td >".$supplier_names[$srow['id_supplier']]."</td><td>".$srow['reference']."</td><td>".$srow['supprice']."</td>";
			if($xx++ == 0) echo '<td rowspan="'.$rowcount.'">';
			echo "</tr>";
		  }
		  echo "</table>";
		  mysqli_free_result($sres);
		}
		else /* note that a product with attributes can have a row for the product (id_product_attribute=0) but not for the attributes */
			 /* So we create the $sups array that contains all the fields and set them to zero/empty when there are no values for them */
		{ $has_combinations = true;
	
		  $paquery = "SELECT pa.id_product_attribute, GROUP_CONCAT(CONCAT(gl.name,': ',l.name)) AS nameblock from ". _DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product='".$datarow['id_product']."' GROUP BY pa.id_product_attribute ORDER BY pa.id_product_attribute";
		  $pares=dbquery($paquery);
		  
		  while ($parow=mysqli_fetch_array($pares))
		  { echo '<table border=1 class="supplier" id="suppliers'.$parow['id_product_attribute'].'s'.$x.'" title="'.$parow["nameblock"].'">';
			$suppls = array();
			$squery = "SELECT ps.id_product_supplier,ps.id_supplier,s.name as suppliername, ps.id_product_attribute,product_supplier_reference AS reference,product_supplier_price_te AS supprice FROM ". _DB_PREFIX_."product_supplier ps";
			$squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
			$squery .= " WHERE ps.id_product_attribute=".$parow['id_product_attribute']." ORDER BY suppliername";
		    $sres=dbquery($squery);
			while ($srow=mysqli_fetch_array($sres))
			{ $suppls[$srow["id_supplier"]] = array($srow["id_product_supplier"],$srow['reference'], $srow['supprice']);
			}
			$xx = 0;
			foreach($sups AS $sup)
			{ if(isset($suppls[$sup]))
			  { echo "<tr title='".$sup."'>";
			    echo "<td >".$supplier_names[$sup]."</td><td>".$suppls[$sup][1]."</td><td>".$suppls[$sup][2]."</td>";
			  }
			  else 		/* this is the situation initially: when the supplier has just been added for the product */
			  { echo "<tr title='0'>"; 
			    echo "<td>".$supplier_names[$sup]."</td><td></td><td>0.000000</td>";
			  }
			  if($xx++ == 0)
			    echo '<td rowspan="'.sizeof($sups).'">'.$parow["nameblock"].'</td>';
			  echo "</tr>";
			}
		    echo "</table>";
		  }
		  mysqli_free_result($sres);
		  mysqli_free_result($pares);
		}
		echo "</td>";
      }
	  else if ($fields[$i] == "tags")
      { $tquery = "SELECT pt.id_tag,name FROM ". _DB_PREFIX_."product_tag pt";
		$tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag AND t.id_lang='".$id_lang."'";
	    $tquery .= " WHERE pt.id_product='".$datarow['id_product']."'";
		$tres=dbquery($tquery);
		$idx = 0;
		echo "<td>";
		while ($trow=mysqli_fetch_array($tres)) 
		{ if($idx++ > 0) echo "<br/>";
		  echo "<nobr>".$trow["name"]."</nobr>";
 	    }
		echo "</td>";
		mysqli_free_result($tres);
      }
      else if ($fields[$i] == "VAT")
      { $sorttxt = "idx='".$datarow['id_tax_rules_group']."'";
		echo "<td ".$sorttxt.">";
		if(isset($missing_taxgroups[$datarow['id_tax_rules_group']]))
		{ echo '<select name="VAT" onchange="VAT_change(this)">'.str_replace('</select>','',$taxblock).$missing_taxgroups[$datarow['id_tax_rules_group']].'</select>';
		}
		else
		  echo '<select name="VAT" onchange="price_change(this)">'.str_replace('value="'.$datarow['id_tax_rules_group'].'"','value="'.$datarow['id_tax_rules_group'].'" selected',$taxblock);
		if(!isset($taxrates[$datarow['id_tax_rules_group']])) 
		  echo " ".$datarow['id_tax_rules_group']." is an invalid tax rules group.";
		echo "</td>";
      }
      else
	  { if($field_array[$fields[$i]][7] == NOT_EDITABLE)
           echo '<td>'.$myvalue.'</td>';
	     else
           echo '<td><input name="'.$field_array[$fields[$i]][0].'" size=50 value="'.$myvalue.'"></td>';
	  }
	  echo "</tr>";
	}
	echo '<tr><td colspan=2 style="text-align:center">features</td></tr>';
	foreach($features AS $key => $feature)
	{ $xquery = "SELECT fv.custom AS custom,fl.value AS value, fp.id_feature_value FROM ". _DB_PREFIX_."feature_product fp";
	  $xquery.=" left join ". _DB_PREFIX_."feature_value fv on fp.id_feature_value=fv.id_feature_value";
	  $xquery.=" left join ". _DB_PREFIX_."feature_value_lang fl on fp.id_feature_value=fl.id_feature_value AND fl.id_lang='".$id_lang."'";
	  $xquery .= " WHERE fp.id_product = '".$id_product."' AND fp.id_feature='".$key."'";
	  $xres=dbquery($xquery);
	  $tmp = "";
	  while ($xrow=mysqli_fetch_array($xres)) /* mag maar één keer gebeuren */
	  { if($tmp != "") $tmp .= ", ";
	    if($xrow["custom"] == "1") $tmp .= $xrow["id_feature_value"]."-".$xrow["value"];
		else $tmp .= $xrow["id_feature_value"]."-<b>".$xrow["value"]."</b>";
	  }
	  if($tmp != "")
		echo "<tr><td>".$key."-".$feature."</td><td>".$tmp."</td></tr>";
	}
  echo '</table></form></div>';
  
  /* statistics */
  echo '<table class=triplemain><td colspan=2 style="text-align:center">Totals</td>';
  for($i=0; $i< sizeof($fields); $i++)
  { if (in_array($fields[$i], $statfields))
	  echo '<tr><td>'.$fields[$i].'</td><td>'.$stattotals[$fields[$i]].'</td></tr>';
  }
  echo '</table>';

  include "footer1.php";
  echo '</body></html>';


$product_list = array();
function get_product_name($id)
{ global $product_list,$id_lang;
  if(isset($product_list[$id]))
    return $product_list[$id];
  $query = "select name from ". _DB_PREFIX_."product_lang WHERE id_product='".$id."' AND id_lang='".$id_lang."'";
  $res = dbquery($query);
  if(mysqli_num_rows($res) == 0)
	  return "Unknown ".$id;
  $row=mysqli_fetch_array($res);
  $product_list[$id] = $row["name"];
  return $row["name"];
}