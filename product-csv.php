<?php 
/* This file is divided in sections.
 * 1. include files 
 * 2. handling input variables
 * 3. make field declarations and build infofields array of displayed fields
 * 4. retrieve system settings and build php blocks for select fields 
 * 5. build and execute the product query 
 * 6. write http header
 * 7. write csv headers
 * 8. write csv content
*/
/* section 1: includes */
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");

/* section 2: handling input variables */
$input = $_GET;

for($x=1; $x<=8; $x++)
{ if(!isset($_GET["search_txt".$x]))
  { if($x > 3) 
	  continue;
    else
	{ $input['search_txt'.$x] = "";
      $input['search_cmp'.$x] = "in";
	  $input['search_fld'.$x] = "main fields";
	}
  }
  $GLOBALS["search_txt".$x] = trim(mysqli_real_escape_string($conn,$input['search_txt'.$x]));
  $GLOBALS["search_cmp".$x] = trim(mysqli_real_escape_string($conn,$input['search_cmp'.$x]));
  $GLOBALS["search_fld".$x] = trim(mysqli_real_escape_string($conn,$input['search_fld'.$x]));  
}

$startrec = intval($input['startrec']);
$numrecs = intval($input['numrecs']);
$id_category = intval($input['id_category']);
$id_shop = intval($input["id_shop"]);
$id_lang = intval($input["id_lang"]);
$def_lang = get_configuration_value('PS_LANG_DEFAULT');
if(!isset($input['startdate'])) $input['startdate']="";
if(!isset($input['enddate'])) $input['enddate']="";
if((!isset($input['rising'])) || ($input['rising'] == "ASC")) {$rising = "ASC";} else {$rising = "DESC";}
if(!isset($input['order']))  /* sorting order */
{ if($id_category == 0) {$input['order']="id_product";} else {$input['order']="position";}
}

if(!isset($input['imgformat'])) {$input['imgformat']="";}
if(!isset($input['extralangfields'])) {$extralangfields = array();} else $extralangfields = $input['extralangfields'];
$extralangfields = array_diff($extralangfields,[$id_lang]); /* prevent errors */

if(empty($input["fields"])) // if not set, set default set of active fields
  $input["fields"] = $prestools_settings["default_product_fields"]; /* this is set in settings1.php */
					/* validation of fields follows after features have been collected */
					
/* section 3: make field declarations and build infofields array of displayed fields */
  $infofields = array(); /* this will hold the displayed fields with a copy of the field_array entries */
  $if_index = 0;
   /* [0]always same as key, [1]language specific texts, [2]source, [3]display(0=not;1=yes;2=edit;), [4]fieldwidth(0=not set), 
      [5]align(0=default;1=right), [6]not used, [7]Editable, [8]tablefield (used in search block) */
  define("HIDE", 0); define("DISPLAY", 1); define("EDIT", 2);  // display
  define("LEFT", 0); define("RIGHT", 1); // align
  define("NOT_EDITABLE", 0); define("INPUT", 1); define("TEXTAREA", 2); define("DROPDOWN", 3); define("BINARY", 4); define("EDIT_BTN", 5);  /* title, keyover, source, display(0=not;1=yes;2=edit), fieldwidth(0=not set), align(0=default;1=right), sortfield */
   /* sortfield => 0=no escape removal; 1=escape removal; 2 and higher= escape removal and n lines textarea */
  $infofields[$if_index++] = array("",array("","",""), "", DISPLAY, 0, LEFT, 0,0);
  
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
   "price" => array("price",null, "price", DISPLAY, 200, LEFT, null, INPUT, "ps.price"),
   "priceVAT" => array("priceVAT",null, "priceVAT", DISPLAY, 0, LEFT, null, INPUT, "priceVAT"),
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
   "visits" => array("visits",null, "visitcount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "visitcount"),
   "visitz" => array("visitz",null, "visitedpages", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "visitedpages"),
   "salescnt" => array("salescnt",null, "salescount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "salescount"),
   "revenue" => array("revenue",null, "revenue", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "revenue"),
   "refunds" => array("refunds",null, "refunds", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "refunds"),
   "orders" => array("orders",null, "ordercount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ordercount"),
   "buyers" => array("buyers",null, "buyercount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "buyercount")
   ); 
   
$query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang WHERE id_lang=".$id_lang;
$res=dbquery($query);
$row=mysqli_fetch_array($res); 
$iso_code = $row['iso_code'];

$extralangs = array(); 
$langids = array();
$langcodes = array();
$query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang WHERE active=1 ORDER BY id_lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ if($def_lang == $row["id_lang"])
  { $def_langname = $row['name'];
    $def_iso_code = $row['iso_code'];
  }
  if($id_lang == $row["id_lang"])
  { $iso_code = $row['iso_code'];
  }
  else
  { $extralangs[$row['id_lang']] = $row['iso_code'];
  } 
  $langids[] = $row["id_lang"];
  $langcodes[$row["id_lang"]] = $row["iso_code"];
}
if(!isset($iso_code))
{ $iso_code = $def_iso_code;
}
  
  /* put the language specific data at position 1 in the field array */
  /* $screentext_pe is defined in the language file */
  foreach($field_array as $key => $value)
  { if(isset($screentext_pe[$key]) && isset($screentext_pe[$key][1]))
	{ if($screentext_pe[$key][1] == "")
	    $screentext_pe[$key][1] = $screentext_pe[$key][0];
      $field_array[$key][1] = $screentext_pe[$key];
	}
	else /* when users use old translation files that don't support this entry */
	  $field_array[$key][1] = array($key,$value[2],$key);
  }

  /* get the infofields array with the active fields. Put the fields pre-sorted in the fieldsorder array in Settings1.php first */
  $infofields[$if_index++] = $field_array["id_product"];
  $myfields = array("id_product"); /* myfields is used for verifying bought plugins */
  $langfields = array("name","link_rewrite","description","description_short","meta_title","meta_keywords","meta_description","available_now","available_later","tags");

  foreach($prestools_settings["fieldsorder"] AS $ofield)
  { if (in_array($ofield, $input["fields"]))
    { 	$infofields[$if_index++] = $field_array[$ofield];
		if(in_array($ofield,$langfields) && (sizeof($extralangfields) > 0))
		{ foreach($extralangfields AS $extralangfield)
		  { $tmp = $field_array[$ofield];
		    $tmp[0] = $tmp[0]."_".$extralangs[$extralangfield];
			$tmp[1][0] = $tmp[1][0]."_".$extralangs[$extralangfield];
		    $tmp[2] = $tmp[2]."_".$extralangs[$extralangfield];
		    $infofields[$if_index++] = $tmp;
		  }
		}
		$myfields[] = $ofield;
	}
  }
  
  foreach($field_array AS $key => $value)
  { if ((in_array($key, $input["fields"])) && (!in_array($key, $prestools_settings["fieldsorder"])))
    { $infofields[$if_index++] = $value;
	  $myfields[] = $key;
	  if(in_array($key,$langfields) && (sizeof($extralangfields) > 0))
	  { foreach($extralangfields AS $extralangfield)
		{ $tmp = $field_array[$key];
		  $tmp[0] = $tmp[0]."_".$extralangs[$extralangfield];
		  $tmp[1][0] = $tmp[1][0]."_".$extralangs[$extralangfield];
		  $tmp[2] = $tmp[2]."_".$extralangs[$extralangfield];
		  $infofields[$if_index++] = $tmp;
		}
	  }	  
	}
  }
  
/* section 4: retrieve system settings and build php blocks for select fields */

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

/* Make blocks for features */
$query = "SELECT fl.id_feature, name FROM ". _DB_PREFIX_."feature_lang fl";
$query .= " LEFT JOIN ". _DB_PREFIX_."feature_shop fs ON fs.id_feature = fl.id_feature";
$query .= " WHERE id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
$query .= " ORDER BY id_feature";
$res = dbquery($query);
$features = array();
while($row = mysqli_fetch_array($res))
{ $features[$row['id_feature']] = $row['name'];
}

$statfields = array("salescnt", "revenue","refunds","orders","buyers","visits","visitz");

$taxfields = array("priceVAT","t.rate","ps.id_tax_rules_group");

/* now get multi-language status. If true you get product urls like www.myshop.com/en/mycat/123-prod.html */
$query='SELECT COUNT(DISTINCT l.id_lang) AS langcount FROM `'._DB_PREFIX_.'lang` l
				JOIN '._DB_PREFIX_.'lang_shop ls ON (ls.id_lang = l.id_lang AND ls.id_shop = '.(int)$id_shop.')
				WHERE l.`active` = 1';
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$langcount = (int)$row['langcount'];
$langinsert = "";
if($langcount>1)
	$langinsert = $iso_code."/";

/* get shop group and its shared_stock status */
$query="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
$query .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_shop_group = $row['id_shop_group'];
$share_stock = $row["share_stock"];
$shop_group_name = $row["name"];

/* make tax block */
/* note: behavior in ps_tax_rule is the computationMethod when there is more than one tax */
$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');
$ps_tax = get_configuration_value('PS_TAX'); /* flag that taxes are enabled */
$taxrates = [];
$taxrates[0] = 0;
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
  while($row = mysqli_fetch_array($res))
  { $taxrates[$row['id_tax_rules_group']] = $row['rate'];
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
  { $taxrates[$row['id_tax_rules_group']] = 0;
  }
}

/* look for double category names */
  $duplos = array();
  $query = "select name,count(*) AS duplocount from ". _DB_PREFIX_."category_lang WHERE id_lang='".$id_lang."' AND id_shop='".$id_shop."' GROUP BY name HAVING duplocount > 1";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  {  $duplos[] = $row["name"];
  }
  
/* make category block */
  $query = "select c.id_category,name,link_rewrite, id_parent from "._DB_PREFIX_."category c";
  $query .= " left join "._DB_PREFIX_."category_lang cl ON c.id_category=cl.id_category AND id_lang='".$id_lang."'";
  $query .= " AND id_shop='".$id_shop."' ORDER BY name";
  $res=dbquery($query);
  $category_names = $category_rewrites = $category_parents = array();
  $x=0;
  while ($row=mysqli_fetch_array($res)) 
  { if(in_array($row['name'], $duplos))
	  $name = $row['name'].$row['id_category'];
	else
	  $name = $row['name'];
    $category_names[$row['id_category']] = $name;
	$category_rewrites[$row['id_category']] = $row['link_rewrite'];
    $category_parents[$row['id_category']] = $row['id_parent'];	
  } 
  
  /* make supplier names list */
  $query = "select id_supplier,name from ". _DB_PREFIX_."supplier ORDER BY id_supplier";
  $res=dbquery($query);
  $supplier_names = array();
  while ($row=mysqli_fetch_array($res)) 
  { $supplier_names[$row['id_supplier']] = $row['name'];
  } 
  

  
/* section 5: build and execute the product query */
$idfields = array("accessories","p.id_product","cl.id_category","ps.id_category_default","m.id_manufacturer","p.id_supplier","ps.id_tax_rules_group"); /* in these fields you can place comma-separated id numbers */

$wheretext = "";
$manufacturer_needed = $searchword_needed = $taxinfo_needed = false;
$visitcount_needed = $visitedpages_needed = false;
for($x=1; $x<=8; $x++)
{ if(!isset($input["search_txt".$x])) continue;
  $search_cmp = $input["search_cmp".$x];
  $search_txt = $input["search_txt".$x]; /* using $GLOBALS here doesn't work */
  $search_fld = $input["search_fld".$x];
//  if($search_txt == "") && (!in_array($search_fld, array("su.name","combinations","cr.name","m.name","pl.description","pl.description_short","discount","custFlds","virtualp"))) && (substr($search_fld,0,7) != "sattrib") && (substr($search_fld,0,7) != "sfeatur")) continue;
  
  if(($search_fld == "m.name") || (($search_fld == "main fields") && ($search_txt !="")) || in_array("manufacturer",$keywords)) 
    $manufacturer_needed = true;
  $nottext = "";
  if($search_cmp == "not_eq") $cmp = "!=";
  else if($search_cmp == "eq") $cmp = "=";
  else if($search_cmp == "gt") $cmp = ">";
  else if($search_cmp == "gte") $cmp = ">=";
  else if($search_cmp == "lt") $cmp = "<";
  else if($search_cmp == "lte") $cmp = "<="; 
  if(($search_cmp == "not_in") || ($search_cmp == "not_eq"))
	$nottext = "NOT ";
  if ($search_txt != "")
  { if($search_cmp == "gt")
	  $inc = "< '".$search_txt."'";
    else if($search_cmp == "gte")
	  $inc = "<= '".$search_txt."'";   
    else if((($search_cmp == "eq") || ($search_cmp == "not_eq")) && !in_array($search_fld,$idfields))
	  $inc = "= '".$search_txt."'"; 
    else if($search_cmp == "lte")
	  $inc = ">= '".$search_txt."'";
    else if($search_cmp == "lt")
	  $inc = "> '".$search_txt."'";
    else if(in_array($search_fld,$idfields) || (substr($search_fld,0,9) == "sidattrib") || (substr($search_fld,0,9) == "sidfeatur"))
	{ if($search_fld == "cl.id_category")
	  { $search_txt = preg_replace('/[^0-9,s]*/',"",$search_txt); /* an "s" behind the category number means "with subcategories" */
		if(strpos($search_txt,"s"))
		{ $frags = explode(",",$search_txt);
		  $catfrags = array();
		  foreach($frags AS $frag)
	      { if(stripos($frag,'s')) /* "6s" means category 6 with subcategories */
		      get_subcats(str_replace('s','',$frag), $catfrags); /* this function will place the results in the categories array */
	        else if($frag != 0)
		      $catfrags[] = $frag;
	      }
	      $search_txt = implode(",",$catfrags);
		}
	  }
	  else
	    $search_txt = preg_replace('/[^0-9,]*/',"",$search_txt);
	  if(strlen($search_txt) > 0) /* avoid mysql error */
	    $inc = " IN (".$search_txt.")";
	  else 
		 $inc = " IN (0)"; 
	}
    else   /* default = "in": also for "not_in" */
	  $inc = "like '%".$search_txt."%'";
    if($search_fld == "main fields")
    { $wheretext .= " AND ".$nottext." (p.reference ".$inc." or pl.name ".$inc." or pl.description ".$inc."  or pl.description_short ".$inc;
      $wheretext .= " OR (m.name IS NOT NULL AND m.name ".$inc.")";
      if(is_numeric($search_txt) && (intval($search_txt) !=0)) $wheretext .= " or p.id_product='".$search_txt."' ";
	  $wheretext .= ") ";
    }  
    else if($search_fld == "accessories")
    { $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."accessory ac WHERE ac.id_product_1 = p.id_product AND ac.id_product_2 ".$inc.")";
    }
    else if(($search_fld == "ps.id_category_default") || ($search_fld == "p.id_product"))
      $wheretext .= " AND ".$nottext.$search_fld." ".$inc." ";
    else if ($search_fld == "cr.name")
	  $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_carrier pc LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0 WHERE pc.id_product = p.id_product AND cr.name ".$inc.")";
    else if ($search_fld == "tg.name")
    { $wheretext .= " AND ".$nottext." EXISTS (SELECT NULL FROM ". _DB_PREFIX_."tag tg";
      $wheretext .= " LEFT JOIN ". _DB_PREFIX_."product_tag pt ON pt.id_tag=tg.id_tag WHERE tg.name ".$inc." AND p.id_product=pt.id_product AND tg.id_lang='".$id_lang."') ";
    }
    else if ($search_fld == "p.indexed") /* the "indexes" search term: look for keywords */
    { /* NB: the "indexed" search term has as $search_fld ps.indexed and can be 0 or 1 */
	  $wheretext .= " AND sw.word ".$nottext." ".$inc;
      $searchword_needed = true;
    }
    else if($search_fld == "su.name")
	  $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu LEFT JOIN ". _DB_PREFIX_."supplier su ON psu.id_supplier=su.id_supplier WHERE psu.id_product = p.id_product AND su.name ".$inc.")";
    else if($search_fld == "su.product_supplier_reference")
	  $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu WHERE psu.id_product = p.id_product AND psu.product_supplier_reference ".$inc.")";
    else if($search_fld == "priceVAT")
	  $wheretext .= " AND ".$nottext." (ROUND(((rate/100)+1)*ps.price,2) ".$inc.")";
    else if($search_fld == "w.id_warehouse")
    { $wheretext .= " AND (".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."warehouse_product_location wpl LEFT JOIN ". _DB_PREFIX_."warehouse w ON wpl.id_warehouse=w.id_warehouse WHERE wpl.id_product = p.id_product AND w.name ".$inc.")";
	 $wheretext .= " OR ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."stock st LEFT JOIN ". _DB_PREFIX_."warehouse w ON st.id_warehouse=w.id_warehouse WHERE st.id_product = p.id_product AND w.name ".$inc."))";
    }
    else if($search_fld == "cl.id_category")
    { $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."category_product cpp WHERE cpp.id_product = p.id_product AND cpp.id_category ".$inc.")";
    }
    else if($search_fld == "combinations")  /* combinations */
    { $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM 
   ". _DB_PREFIX_."product_attribute pa LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pac 
   ON pa.id_product_attribute=pac.id_product_attribute LEFT JOIN ". _DB_PREFIX_."attribute_lang al
   ON pac.id_attribute = al.id_attribute WHERE pa.id_product=p.id_product AND al.name ".$inc.")";
    }
	else if(in_array($search_fld, $taxfields))
	{ $wheretext .= " AND ".$nottext.$search_fld." ".$inc." ";
	  $taxinfo_needed = true;
	}
	else if($search_fld == "visitcount")
	{ $visitcount_needed = true;
	}
	else if($search_fld == "visitedpages")
	{ $visitedpages_needed = true;
	}
    else if($search_fld == "p.reference") /* check here also for supplier reference */
    { $wheretext .= " AND ".$nottext." (".$search_fld." ".$inc." ";
   	  $wheretext .= " OR EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu WHERE psu.id_product = p.id_product AND psu.product_supplier_reference ".$inc."))";
    }
    else if(substr($search_fld,0,7) == "sattrib") /* attribute search */
    { $id_attribute_group = substr($search_fld,7);
      $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pa 
   LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pac ON pa.id_product_attribute=pac.id_product_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute_lang al ON pac.id_attribute = al.id_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute atr ON pac.id_attribute = atr.id_attribute 
   WHERE pa.id_product=p.id_product AND atr.id_attribute_group=".$id_attribute_group." AND ".$nottext." (al.name ".$inc."))";
    }
    else if(substr($search_fld,0,9) == "sidattrib") /* attribute search */
    { $id_attribute_group = substr($search_fld,9);
      $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pa 
   LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pac ON pa.id_product_attribute=pac.id_product_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute atr ON pac.id_attribute = atr.id_attribute 
   WHERE pa.id_product=p.id_product AND atr.id_attribute_group=".$id_attribute_group." AND ".$nottext."  (atr.id_attribute ".$inc."))";
    }
    else if(substr($search_fld,0,7) == "sfeatur") /* feature search */
    { $id_feature = substr($search_fld,7);
      $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."feature_product fp 
   LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value 
   LEFT JOIN ". _DB_PREFIX_."feature_value_lang fvl ON fv.id_feature_value=fvl.id_feature_value
   WHERE fp.id_product=p.id_product AND fp.id_feature=".$id_feature." AND ".$nottext." (fvl.value ".$inc."))";
    }
    else if(substr($search_fld,0,9) == "sidfeatur") /* feature search */
    { $id_feature = substr($search_fld,9);
      $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."feature_product fp 
   LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value 
   WHERE fp.id_product=p.id_product AND fp.id_feature=".$id_feature." AND ".$nottext." (fv.id_feature_value ".$inc."))";
    }
    else if(($search_fld != "discount") && ($search_fld != "virtualp") && ($search_fld != "custFlds"))
    {// $extra_options = ["p.visibility","p.available_for_order","p.show_price"];
	 // if(in_array($search_fld, $searchtabfields) || in_array($search_fld, $extra_options))
	    $wheretext .= " AND ".$nottext.$search_fld." ".$inc." ";
	}
   }
   else /* $search_txt == "" */
   { 
     $nottexta = ""; /* here we use a different logic - so a different variable */
     if(($search_cmp == "not_eq") || ($search_cmp == "!in"))
	   $nottexta = "NOT";
     if($search_fld == "su.name")      /* supplier: this works when search_txt field is empty */
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu WHERE psu.id_product = p.id_product)";
     else if($search_fld == "accessories")
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."accessory acc WHERE acc.id_product_1 = p.id_product)";
     else if($search_fld == "tg.name")
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_tag ptg WHERE ptg.id_product = p.id_product)";
     else if($search_fld == "su.product_supplier_reference")
   	  $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu WHERE psu.id_product = p.id_product AND psu.product_supplier_reference ='')";
     else if($search_fld == "combinations") /* combinations: this works when search_txt field is empty */
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pax WHERE pax.id_product = p.id_product)";
     else if($search_fld == "cr.name")      /* carriers: this works when search_txt field is empty */
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_carrier pcx WHERE pcx.id_product = p.id_product)";
     else if($search_fld == "m.name")      /* manufacturers: this works when search_txt field is empty */
	 { $wheretext .= " AND ".$nottexta." p.id_manufacturer=0";
	 }
     else if($search_fld == "w.id_warehouse")  
	 { $wheretext .= " AND (".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."warehouse_product_location wpl LEFT JOIN ". _DB_PREFIX_."warehouse w ON wpl.id_warehouse=w.id_warehouse WHERE wpl.id_product = p.id_product)";
	 $wheretext .= " OR ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."stock st LEFT JOIN ". _DB_PREFIX_."warehouse w ON st.id_warehouse=w.id_warehouse WHERE st.id_product = p.id_product))";
	 }
     else if($search_fld == "pl.description") 
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT REGEXP_REPLACE(description,'<[^>]*>','') AS descra FROM ". _DB_PREFIX_."product_lang plx WHERE plx.id_product = p.id_product AND plx.id_lang=".$id_lang." HAVING descra='')";
	//   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_lang plx WHERE plx.id_product = p.id_product AND plx.id_lang=".$id_lang." AND plx.id_shop=".$id_shop." AND plx.description='')";
     else if($search_fld == "pl.description_short") 
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT REGEXP_REPLACE(description_short,'<[^>]*>','') AS descra FROM ". _DB_PREFIX_."product_lang plx WHERE plx.id_product = p.id_product AND plx.id_lang=".$id_lang." AND plx.id_shop=".$id_shop." HAVING descra='')";
     else if(substr($search_fld,0,7) == "sattrib")  /* attribute search */
     { $id_attribute_group = substr($search_fld,7);
       $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pa 
   LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pac ON pa.id_product_attribute=pac.id_product_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute_lang al ON pac.id_attribute = al.id_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute atr ON pac.id_attribute = atr.id_attribute 
   WHERE pa.id_product=p.id_product AND atr.id_attribute_group=".$id_attribute_group.")";
     }
    else if(substr($search_fld,0,7) == "sfeatur") /* feature search */
    { $id_feature = substr($search_fld,7);
      $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."feature_product fp 
   LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value 
   LEFT JOIN ". _DB_PREFIX_."feature_value_lang fvl ON fv.id_feature_value=fvl.id_feature_value
   WHERE fp.id_product=p.id_product AND fp.id_feature=".$id_feature.")";
    }
	else if((($search_cmp == "not_eq") || ($search_cmp == "eq")) && !in_array($search_fld, array("discount","virtualp","custFlds"))) 
	{ if($search_cmp == "not_eq")
	  { $wheretext .= " AND ".$search_fld."!=''";
	    if($search_fld == "p.ean13")
		  $wheretext .= " AND ".$search_fld."!='0'";
	  }
	  else if($search_cmp == "eq")
	  { if($search_fld == "p.ean13")		
		  $wheretext .= " AND (".$search_fld."='' OR ".$search_fld."='0')";
	    else
		  $wheretext .= " AND ".$search_fld."=''";	
	  }	  
	}	
  }
  
  /* finally three fields where it doesn't matter whether search_txt is filled */
  if($search_fld == "discount") /* this works also when search_txt field is empty */
	 $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."specific_price sp WHERE sp.id_product = p.id_product)";
  if($search_fld == "custFlds") /* this works also when search_txt field is empty */ 
	 $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."customization_field cf WHERE cf.id_product = p.id_product)";
  if($search_fld == "virtualp")
	 $wheretext .= " AND ".$nottext." (p.is_virtual = 1)";
} /* end of for x=1; x<=8; x++; search block loop */

  if(isset($_GET["conda_fld1"]) && ($_GET["conda_fld1"] != '0') && isset($_GET["condb_fld1"]) && ($_GET["condb_fld1"] != '0'))
  { $conda_fld = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET["conda_fld1"]);
	$condb_fld = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET["condb_fld1"]);
	$cond_cmp = $_GET["cond_cmp1"];
	if($cond_cmp == "not_eq") $cmp = "!=";
	else if($cond_cmp == "eq") $cmp = "=";
    else if($cond_cmp == "gt") $cmp = ">";
    else if($cond_cmp == "gte") $cmp = ">=";
    else if($cond_cmp == "lt") $cmp = "<";
    else if($cond_cmp == "lte") $cmp = "<="; 
    else if($cond_cmp == "not_in") $cmp = "NOT IN"; 
	else $cmp = "IN"; 
    $wheretext .= " AND ".$conda_fld." ".$cmp." ".$condb_fld;
  }

/* Note: we start with the query part after "from". First we count the total and then we take 100 from it */
/* DISTINCT is for when "with subcats" results in more than one occurence */
$queryterms = "p.*,pl.*,ps.*, cl.name AS catname,p.id_product AS ptest,pl.id_product AS pltest";
$queryterms .=  ", cl.link_rewrite AS catrewrite, pld.name AS originalname, s.quantity, s.depends_on_stock";

$query = " FROM ". _DB_PREFIX_."product_shop ps LEFT JOIN ". _DB_PREFIX_."product p ON p.id_product=ps.id_product";
$query.=" LEFT JOIN ". _DB_PREFIX_."product_lang pl ON pl.id_product=p.id_product and pl.id_lang='".$id_lang."' AND pl.id_shop='".$id_shop."'";
$query.=" LEFT JOIN ". _DB_PREFIX_."product_lang pld ON pld.id_product=p.id_product and pld.id_lang='".$def_lang."' AND pld.id_shop='".$id_shop."'"; /* This gives the name in the shop language instead of the selected language */
$query.=" LEFT JOIN ". _DB_PREFIX_."category_lang cl ON cl.id_category=ps.id_category_default AND cl.id_lang='".$id_lang."' AND cl.id_shop = '".$id_shop."'";
if($share_stock == 0)
  $query.=" LEFT JOIN ". _DB_PREFIX_."stock_available s ON s.id_product=p.id_product AND s.id_shop = '".$id_shop."' AND id_product_attribute='0'";
else
  $query.=" LEFT JOIN ". _DB_PREFIX_."stock_available s ON s.id_product=p.id_product AND s.id_shop_group = '".$id_shop_group."' AND id_product_attribute='0'";

if ($input['order']=="id_product") $order="p.id_product";
else if ($input['order']=="name") $order="pl.name";
else if ($input['order']=="position") $order="cp.position"; /* later to be refined */
else if ($input['order']=="VAT") $order="t.rate";
else if ($input['order']=="price") $order="ps.price";
else if ($input['order']=="active") $order="ps.active";
else if ($input['order']=="availorder") $order="ps.available_for_order,ps.show_price";
else if ($input['order']=="pack_stock_type") $order="ps.pack_stock_type";
else if ($input['order']=="shipweight") $order="p.weight";
else if ($input['order']=="date_upd") $order="p.date_upd";
else if ($input['order']=="image")  /* sorting on image makes only sense to get the products without an image */
{  $order="i.cover";
   $queryterms .= ",i.id_image, i.cover";
   $query.=" LEFT JOIN ". _DB_PREFIX_."image i ON i.id_product=p.id_product and i.cover=1";
}
else $order = $input['order'];

if ($id_category != 0)
{ $categories = array();
  if(isset($input['subcats']))
    get_subcats($id_category, $categories);
  else 
    $categories = array($id_category);
  $cats = join(',',$categories);
  
  if(sizeof($categories) == 1)
  { $query .= " LEFT JOIN "._DB_PREFIX_."category_product cp ON p.id_product=cp.id_product";
	$wheretext .= " AND cp.id_category='".$cats."'";
  }
  else
  { 
   $query .= " LEFT JOIN "._DB_PREFIX_."category_product cp ON p.id_product=cp.id_product";
   $query .= " INNER JOIN (SELECT MIN(id_category) AS mincat,id_product,position FROM ". _DB_PREFIX_."category_product WHERE id_category IN (".$cats.") GROUP BY id_product) cpx ON cpx.mincat=cp.id_category and cpx.id_product=p.id_product";
  }
}
else
{ $query .= " LEFT JOIN ". _DB_PREFIX_."category_product cp ON ps.id_category_default=cp.id_category AND p.id_product=cp.id_product";
}

if(in_array("position", $input["fields"]) || (($order=="cp.position") && (($id_category == 0) || (sizeof($categories)>1))))
{ $query .= " LEFT JOIN ". _DB_PREFIX_."category c ON c.id_category=cp.id_category";
  $order = "c.nleft,cp.position";
}

if(in_array("virtualp", $input["fields"]))
{ $query.=" LEFT JOIN ". _DB_PREFIX_."product_download pd ON pd.id_product=p.id_product";
  $queryterms .= ", filename, display_filename, date_expiration, nb_days_accessible, nb_downloadable, pd.active AS dl_active, is_shareable";
}

if(in_array("manufacturer", $input["fields"]) || $manufacturer_needed)
{ $query.=" LEFT JOIN ". _DB_PREFIX_."manufacturer m ON m.id_manufacturer=p.id_manufacturer";
  $queryterms .= ",m.name AS manufacturer ";
}

if($searchword_needed)
{ $query .= " LEFT JOIN "._DB_PREFIX_."search_index si ON si.id_product=ps.id_product";
  $query .= " LEFT JOIN "._DB_PREFIX_."search_word sw ON si.id_word=sw.id_word AND ";
  $query .= " sw.id_shop=".$id_shop." AND sw.id_lang=".$id_lang;
}

if(in_array("out_of_stock", $input["fields"]))
{ $queryterms .= ", s.out_of_stock AS out_of_stock";
}

if(in_array("accessories", $input["fields"]))
{ $query.=" LEFT JOIN ( SELECT GROUP_CONCAT(id_product_2) AS accessories, id_product_1 FROM "._DB_PREFIX_."accessory GROUP BY id_product_1 ) a ON a.id_product_1=p.id_product";
  $queryterms .= ", accessories";
}

if(in_array("position", $input["fields"]))
{ if(isset($input['subcats']))
    $queryterms .= ", c.nleft,cp.position";
  else
    $queryterms .= ", cp.position";
}

if($taxinfo_needed || in_array($input["order"],$taxfields) || (in_array("VAT",$input["fields"])))  /* VAT check only needed for product-csv */
{ $queryterms .= ", t.rate";
// the following is reserve for the case there is more than one tax_rule row
//  $query.=" LEFT JOIN (SELECT * FROM ". _DB_PREFIX_."tax_rule tar WHERE tar.id_country='".$id_country."' AND tar.id_state='0' LIMIT 1) tr ON tr.id_tax_rules_group=ps.id_tax_rules_group";
  $query.=" LEFT JOIN ". _DB_PREFIX_."tax_rule tr ON tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state='0'";
  $query.=" LEFT JOIN ". _DB_PREFIX_."tax t ON t.id_tax=tr.id_tax";
}

$extralangcodes = array();
foreach($extralangfields AS $extralangfield)
{ $extralangcodes[] = $isocode = $extralangs[$extralangfield];
  $query.=" LEFT JOIN (SELECT id_product, name AS name_".$isocode.", link_rewrite AS link_rewrite_".$isocode.",";
  $query .= "description AS description_".$isocode.",description_short AS description_short_".$isocode.",";
  $query .= "meta_title AS meta_title_".$isocode.",meta_keywords AS meta_keywords_".$isocode.",";
  $query .= "available_now AS available_now_".$isocode.",available_later AS available_later_".$isocode.",";
  $query .= "meta_description AS meta_description_".$isocode." FROM "._DB_PREFIX_."product_lang";
  $query .= " WHERE id_lang=".$extralangfield." AND id_shop=".$id_shop;
  $query .= ") pl".$isocode." ON pl".$isocode.".id_product=p.id_product";
  $queryterms .= ",name_".$isocode.", link_rewrite_".$isocode.",description_".$isocode;
  $queryterms .= ",description_short_".$isocode.",meta_title_".$isocode.",meta_keywords_".$isocode;
  $queryterms .= ",available_now_".$isocode.",meta_description_".$isocode;
  $queryterms .= ",available_later_".$isocode;
}

$statres = dbquery("SELECT id_page_type FROM ". _DB_PREFIX_."page_type WHERE name='product' OR name='product.php'");
$pagetypes = array();
while ($statrow=mysqli_fetch_array($statres))
	$pagetypes[] = $statrow['id_page_type'];

if(in_array("redirect", $input["fields"]))
{ if(version_compare(_PS_VERSION_ , "1.7.1", "<"))
	$queryterms .= ", ps.id_product_redirected AS id_redirected";
  else
	$queryterms .= ", ps.id_type_redirected AS id_redirected";  
}

if(in_array("visits", $input["fields"]) OR ($order=="visits") OR ($visitcount_needed))
{ $query .= " LEFT JOIN ( SELECT pg.id_object, count(*) AS visitcount FROM ". _DB_PREFIX_."connections c";
  $query .= " LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type IN ('".implode(",",$pagetypes)."') AND pg.id_page = c.id_page AND c.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(c.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) <= TO_DAYS('".$input['enddate']."')";
  $queryterms .= ", visitcount ";
  $query .= " GROUP BY pg.id_object ) v ON p.id_product=v.id_object";
}

if(in_array("visitz", $input["fields"]) OR ($order=="visitz") OR ($visitedpages_needed))
{ /* for mysql 5.7.5 compatibility "SELECT pg.id_object" was replaced by "SELECT MAX(pg.id_object) AS id_object" */
  $query .= " LEFT JOIN ( SELECT MAX(pg.id_object) AS id_object, sum(counter) AS visitedpages ";
  $query .= " FROM ". _DB_PREFIX_."page_viewed v LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type IN ('".implode(",",$pagetypes)."') AND pg.id_page = v.id_page AND v.id_shop='".$id_shop."'";
  $query .= " LEFT JOIN ". _DB_PREFIX_."date_range d ON d.id_date_range = v.id_date_range";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(d.time_start) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(d.time_end) <= TO_DAYS('".$input['enddate']."')";
  $queryterms .= ", visitedpages ";
  $query .= " GROUP BY v.id_page ) w ON p.id_product=w.id_object";
}
if((!empty(array_intersect($input["fields"], array("revenue","salescnt","orders")))) OR ($order=="revenue")OR ($order=="orders")OR ($order=="buyers"))
{ $query .= " LEFT JOIN ( SELECT product_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ";
  $query .= " ROUND(SUM(total_price_tax_incl/conversion_rate),2) AS revenue, ";
  $query .= " COUNT(DISTINCT d.id_order) AS ordercount, count(DISTINCT o.id_customer) AS buyercount FROM ". _DB_PREFIX_."order_detail d";
  $query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order AND o.id_shop=d.id_shop";
  $query .= " WHERE d.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".$input['enddate']."')";
  $query .= " AND o.valid=1";
  $query .= " GROUP BY d.product_id ) r ON p.id_product=r.product_id";
  $queryterms .= ", revenue, r.quantity AS salescount, ordercount, buyercount ";
}
if(in_array("refunds", $input["fields"]))
{ $query .= " LEFT JOIN (SELECT product_id AS id_product, ROUND(SUM(amount_tax_incl/conversion_rate),2) AS refunds";
  $query .= " FROM "._DB_PREFIX_."order_slip_detail osd";
  $query .= " INNER JOIN "._DB_PREFIX_."order_detail od ON osd.id_order_detail=od.id_order_detail";
  $query .= " INNER JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
  $query .= " WHERE o.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".$input['enddate']."')";
  $query .= " AND o.valid=1";
  $query .= " GROUP BY id_product) rf on rf.id_product=p.id_product";
  $queryterms .= ", refunds";
}

$activequery = "SELECT COUNT(DISTINCT p.id_product) AS rcount ".$query." WHERE ps.active='1' AND ps.id_shop='".$id_shop."' ".$wheretext;
$res=dbquery($activequery);
$row = mysqli_fetch_array($res);
$activerecs = $row['rcount'];

$query.=" WHERE ps.id_shop='".$id_shop."' ".$wheretext;

  $stattotals = array("salescnt" => 0, "revenue"=>0, "refunds"=>0,"orders"=>0,"buyers"=>0,"visits"=>0,"visitz"=>0); /* store here totals for stats */
  if(in_array($order, $statfields))
  { $ordertxt = $statz[array_search($order, $statfields)];
  }
  else
    $ordertxt = str_replace(" ","",$order);
  $query .= " ORDER BY ".$ordertxt." ".$rising;
  
  $query= "select ".$queryterms.$query; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */
  $res=dbquery($query);
//  echo $query;

/* section 6: write http header */
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=product-'.date('Y-m-d-Gis').'.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

// According to a comment on php.net the following can be added here to solve Chinese language problems
// fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

  // "*********************************************************************";
/* section 7: write csv headers */
  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }
  $csvline = array();  // array for the fputcsv function
  for($i=1; $i<sizeof($infofields); $i++)
  { if($infofields[$i][0] == "supplier")
    { $csvline[] = "supplier";
	  $csvline[] = "supplier reference";
	  $csvline[] = "supplier price";  
	}
	else if($infofields[$i][0] == "category")	
    { $csvline[] = "categories";
	  $csvline[] = "category ids";
	}
	else if($infofields[$i][0] == "discount")
    { $csvline[] = "discount price";
	  $csvline[] = "discount amount";
	  $csvline[] = "discount pct";
	  $csvline[] = "discount from";
	  $csvline[] = "discount to";
	  $csvline[] = "discount calculated excl";
	  $csvline[] = "discount calculated incl";
	}
	else if($infofields[$i][0] == "virtualp")
    { $csvline[] = "vp_file";
	  $csvline[] = "vp_exp_date";
	  $csvline[] = "vp_nb_days";
	  $csvline[] = "vp_nb_downloads";
	  $csvline[] = "vp_on";
	  $currentDir = dirname(__FILE__);
      $download_dir = $currentDir."/".$triplepath."download/";
	}
	else if($infofields[$i][0] == "redirect")	
    { $csvline[] = "redirect type";
	  $csvline[] = "redirect id";
	}
	else if($infofields[$i][0] == "stockflags")	
    { $csvline[] = "depends_on_stock";
	  $csvline[] = "advanced_stock_management";
	}
	else if($infofields[$i][0] == "link_rewrite")
    { $csvline[] = "link_rewrite";
	  $csvline[] = "url";
	}
	else if($infofields[$i][0] == "VAT")
    { $csvline[] = "VAT group";
	  $csvline[] = "VAT perc";
	}
	else if($infofields[$i][0] == "warehousing")
    {// $csvline[] = "id_warehouse";
	  $csvline[] = "warehouse";
	  $csvline[] = "whlocation";	  
	}
	else
      $csvline[] = $infofields[$i][0];
  }

  foreach($features AS $key => $feature)
  { if (in_array("featr".$key, $input["fields"]))
    { $csvline[] = $feature;
	  foreach($extralangfields AS $extralangfield)
	  { $extralangcodes[] = $isocode = $extralangs[$extralangfield];
	    $csvline[] = $feature."_".$isocode;
	  }
	}	  
  }	
  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);

/* section 8: write csv content */
  $x=0;
  $ress = dbquery("SELECT domain,physical_uri FROM ". _DB_PREFIX_."shop_url WHERE id_shop='".$id_shop."'");
  $rows = mysqli_fetch_array($ress);
  $imagebase = "http://".$rows["domain"].$rows["physical_uri"];
  $linkbase = "http://".$rows["domain"];
  $csvspecials = array("carrier","combinations","custFlds","depends_on_stock","discount","supplier","tags","virtualp");
  while ($datarow=mysqli_fetch_array($res))
  { $csvline = array();
    $aquery = "SELECT count(*) FROM "._DB_PREFIX_."product_attribute WHERE id_product=".$datarow['id_product'];
    $ares=dbquery($aquery);
    list($combination_count) = mysqli_fetch_row($ares); 
    for($i=1; $i< sizeof($infofields); $i++)
    { if(substr($infofields[$i][2],0,5) == "tags_")
		continue; // are handled with tags field
	  $sorttxt = "";
      $color = "";
      if($infofields[$i][2] == "priceVAT")
		$myvalue =  number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '');
      else if (!in_array($infofields[$i][2],$csvspecials))
        $myvalue = $datarow[$infofields[$i][2]];
      if($i == 1) /* id */
	  { $csvline[] = $myvalue;
	  }
	  else if ($infofields[$i][0] == "carrier")		/* niet beschikbaar in CSV */
      { $tmp = ""; 
	    $cquery = "SELECT id_carrier_reference FROM ". _DB_PREFIX_."product_carrier WHERE id_product=".$datarow['id_product']." AND id_shop='".$id_shop."' LIMIT 1";
		$cres=dbquery($cquery);
		if(mysqli_num_rows($cres) != 0)
		{ $cquery = "SELECT id_reference, cr.name FROM ". _DB_PREFIX_."product_carrier pc";
		  $cquery .= " LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0";
		  $cquery .= " WHERE id_product='".$datarow['id_product']."' AND id_shop='".$id_shop."' ORDER BY cr.name";
		  $cres=dbquery($cquery);
		  $idx = 0;
		  while ($crow=mysqli_fetch_array($cres)) 
		  { if($idx++ > 0) $tmp .= $subseparator;
		    $tmp .= $crow["name"];
		    $idx++;
		  }
		}
		$csvline[] = $tmp;
		mysqli_free_result($cres);
	  }
      else if ($infofields[$i][0] == "category")
	  { $cquery = "select cp.id_category from ". _DB_PREFIX_."category_product cp";
		$cquery .= " LEFT JOIN ". _DB_PREFIX_."category_lang cl on cp.id_category=cl.id_category AND id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
		$cquery .= " WHERE cp.id_product='".$datarow['id_product']."' ORDER BY id_category";
		$cres=dbquery($cquery);
		$z=0;
		$catnames = $category_names[$myvalue];
		$catids = $myvalue;
		while ($crow=mysqli_fetch_array($cres)) 
		{	if ($crow['id_category'] == $myvalue)
				continue;
			$catnames .= $subseparator.$category_names[$crow['id_category']]; // without the space this won't work: the categories become different fields
			$catids .= $subseparator.$crow['id_category'];
		}
	    $csvline[] = $catnames;
	    $csvline[] = $catids;
		mysqli_free_result($cres);
	  }   /* end of category */
	  else if ($infofields[$i][0] == "combinations")
      { $cquery = "SELECT count(*) AS counter FROM ". _DB_PREFIX_."product_attribute";
	    $cquery .= " WHERE id_product='".$datarow['id_product']."'";
		$cres=dbquery($cquery);
		$crow=mysqli_fetch_array($cres);
		$csvline[] = $crow["counter"];
		mysqli_free_result($cres);
      }  /* end of combinations */
      else if ($infofields[$i][0] == "customizations")
	  { $tmp = "";
	    $dquery = "SELECT * FROM ". _DB_PREFIX_."customization_field";
	    $dquery .= " WHERE id_product='".$datarow['id_product']."'";
		if (version_compare(_PS_VERSION_ , "1.7.3", ">="))
		  $dquery .= " AND is_deleted=0";
		$dres=dbquery($dquery);
		while ($drow=mysqli_fetch_array($dres))
		{ if($tmp != "") $tmp .= $subseparator;
		  $equery = "SELECT * FROM ". _DB_PREFIX_."customization_field_lang";
		  $equery .= " WHERE id_customization_field=".$drow["id_customization_field"];
		  if (version_compare(_PS_VERSION_ , "1.6.0.12", ">="))
			$equery .= " AND id_shop=".$id_shop;
		  $eres=dbquery($equery);
		  $custlangs = array();
		  while ($erow=mysqli_fetch_array($eres)) 
		  { $custlangs[$erow["id_lang"]] = $erow["name"];
		  }
		  foreach($langids AS $langid)
		  { if((isset($custlangs[$langid])) && ($custlangs[$langid]!=""))
			  $tmp .= $custlangs[$langid]."-";
		  }
	      if($drow["type"]==0) $tmp .=  'uploadfile';
			else $tmp .=  'textfield';
		  if($drow["required"]) $tmp .=  '-req';
		}
		$csvline[] = $tmp;
		mysqli_free_result($dres);
	  }  /* end of customizations */
	  else if ($infofields[$i][0] == "discount")
      { $dquery = "SELECT sp.price, sp.reduction,sp.reduction_type,sp.from,sp.to,sp.reduction_tax";
		$dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
	    $dquery .= " WHERE sp.id_product='".$datarow['id_product']."' AND (sp.id_shop='".$id_shop."' OR sp.id_shop='0') AND (sp.to >= NOW() OR sp.to = '0000-00-00 00:00:00' ) AND sp.id_product_attribute='0'";
		$dquery .= " ORDER BY sp.id_country, sp.id_group, sp.id_customer,sp.id_currency LIMIT 1"; /* order by should put zero's (=all) first */
		$dres=dbquery($dquery);
		if(mysqli_num_rows($dres) > 0)
		{ $drow=mysqli_fetch_array($dres);
		  $csvline[] = floatval($drow['price']);
		  if($drow["price"] == -1)
		  {	$frompriceVAT = number_format(((($taxrates[$datarow['id_tax_rules_group']]/100) +1) * $datarow['price']),2, '.', '');
		  }
		  else /* the prices mentioned here are excl VAT */
		  { $frompriceVAT = (($taxrates[$datarow['id_tax_rules_group']]/100) +1) * $drow['price'];
		  }
		  if($drow["reduction_type"] == "percentage")
		  { $csvline[] = "0";
		    $csvline[] = $drow['reduction'];
		  }
		  else
		  { $csvline[] = floatval($drow['reduction']);
			$csvline[] = "0";
		  }
		  $csvline[] = $drow['from'];
		  $csvline[] = $drow['to'];
		  $reduction_tax = "1";
		  if (version_compare(_PS_VERSION_ , "1.6.0.11", ">="))
		    $reduction_tax = $drow["reduction_tax"];
		  if ($drow['reduction_type'] == "amount")
		  { if($reduction_tax == 1)
			  $newprice = $frompriceVAT - $drow['reduction'];
			else
			  $newprice = $frompriceVAT - ($drow['reduction']*(1+($taxrates[$datarow['id_tax_rules_group']]/100)));
		  }
		  else 
		    $newprice = $frompriceVAT*(1-$drow['reduction']);
		  $newpriceEX = (1/(($taxrates[$datarow['id_tax_rules_group']]/100) +1)) * $newprice;
		  $csvline[] = number_format($newpriceEX,2, '.', '');
		  $csvline[] = number_format($newprice,2, '.', '');
		}
		else
		{ $csvline[] = "";
		  $csvline[] = "";
		  $csvline[] = "";
		  $csvline[] = "";
		  $csvline[] = "";
		  $csvline[] = "";
		  $csvline[] = "";		  
		}
		mysqli_free_result($dres);
      }  /* end of discount */
	  /* featureEdit gives an overview of all features of a productin one field. Useful when 
	     you have many different features and want to see which ones a product has. 
	   */
      else if ($infofields[$i][0] == "featureEdit")
	  { $fquery = "SELECT fp.*,custom,fvl.value AS fvvalue, fl.name AS feature FROM "._DB_PREFIX_."feature_product fp";
		$fquery .= " LEFT JOIN "._DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value";
		$fquery .= " LEFT JOIN "._DB_PREFIX_."feature_value_lang fvl ON fp.id_feature_value=fvl.id_feature_value AND fvl.id_lang=".$id_lang;
		$fquery .= " LEFT JOIN "._DB_PREFIX_."feature_lang fl ON fv.id_feature=fl.id_feature AND fl.id_lang=".$id_lang;
		$fquery .= " WHERE fp.id_product='".$datarow['id_product']."'";
		$fres=dbquery($fquery);
		$tmp = "";
		while($frow = mysqli_fetch_assoc($fres))
		{ if($tmp != "") $tmp .= $subseparator;
		  $tmp .= $frow["feature"].": ";
		  if($frow["custom"] == '1')
		    $tmp .= "--".$frow["fvvalue"]."--";
		  else
		    $tmp .= $frow["fvvalue"];
		}
		$csvline[] = $tmp;
	  }
      else if ($infofields[$i][0] == "image")
      { $iquery = "SELECT id_image,cover FROM ". _DB_PREFIX_."image WHERE id_product='".$datarow['id_product']."' ORDER BY cover DESC, position";
		$ires=dbquery($iquery);
		$xx = 0;
		$imsize = mysqli_num_rows($ires);
		$tmp = "";
		while ($irow=mysqli_fetch_array($ires)) 
		{ $tmp .= $imagebase.'img/p'.getpath($irow['id_image']).'/'.$irow['id_image'].'.jpg';
		  $xx++;
		  if($xx < $imsize)
		    $tmp .= $subseparator; // the space is necessary
		}
		$csvline[] = $tmp;
		mysqli_free_result($ires);
      }  /* end of image */	
      else if ($infofields[$i][0] == "link_rewrite")
      { $csvline[] = $datarow["link_rewrite"];
		// customized link. Prestashop code in getProductLink() in classes\Link.php that refers to classes\Dispatcher.php
		if ($rewrite_settings == '1')
		{ $produrl = $route_product_rule;
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
			else if($key == "supplier")
			{ $sqry = "SELECT name FROM "._DB_PREFIX_."supplier WHERE id_supplier=".$datarow["id_supplier"];
		      $srs = dbquery($sqry);
			  $srw = mysqli_fetch_assoc($srs);
			  $keyvalue = $srw["name"];
			}
			else if($key == "tags")
			{ $tquery = "SELECT GROUP_CONCAT(name SEPARATOR '-') AS tags FROM ". _DB_PREFIX_."product_tag pt";
		      $tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag";
		      if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
		         $tquery .= " AND t.id_lang=pt.id_lang";
			  $tquery .= " WHERE pt.id_product='".$datarow['id_product']."' AND t.id_lang=".$id_lang." AND length(name)>0";
			  $tquery .= " GROUP BY id_product";
			  $tres=dbquery($tquery);
			  $trow = mysqli_fetch_assoc($tres);
			  $keyvalue = $trow["tags"];
			}			  
			else $keyvalue = "xxxx"; // Should not happen

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
          $url = $linkbase.get_base_uri().$langinsert.$produrl;
		}
		else
          $url= $linkbase.get_base_uri()."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang;
		$csvline[] = $url;
      }
	  else if ($infofields[$i][0] == "redirect")
	  { $csvline[] = $datarow['redirect_type'];
		$csvline[] = $datarow['id_redirected'];
	  }
	  else if ($infofields[$i][0] == "reserved")
	  { if($combination_count == 0)
		{ $rquery = "SELECT reserved_quantity AS reserved FROM ". _DB_PREFIX_."stock_available";
		  $rquery .= " WHERE id_product='".$datarow['id_product']."'";
		  $rres=dbquery($rquery);
		  $rrow=mysqli_fetch_array($rres);
		}
		else
		{ $rquery = "SELECT SUM(reserved_quantity) AS reserved FROM ". _DB_PREFIX_."stock_available";
		  $rquery .= " WHERE id_product='".$datarow['id_product']."' AND id_product_attribute !=0";
		  $rres=dbquery($rquery);
		  $rrow=mysqli_fetch_array($rres);
		}
        $csvline[] = $rrow['reserved'];	  
	  }
	  
	  else if ($infofields[$i][0] == "revenue")
      { $csvline[] = $datarow['revenue'];
      }	  	/* end of revenue */
	  else if ($infofields[$i][0] == "stockflags")
	  { $csvline[] = $datarow['depends_on_stock'];
		$csvline[] = $datarow['advanced_stock_management'];
	  }
	  else if ($infofields[$i][0] == "supplier")
      { $squery = "SELECT p.id_supplier,product_supplier_reference AS reference, 	product_supplier_price_te AS price FROM "._DB_PREFIX_."product_supplier ps";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product p on p.id_supplier=ps.id_supplier";
		$squery .= " WHERE ps.id_product=".$datarow['id_product']." AND ps.id_product_attribute='0' LIMIT 1";
		$sres=dbquery($squery);
		if(mysqli_num_rows($sres) == 0) /* shouldn't happen */
		{ $squery = "SELECT id_supplier,product_supplier_reference AS reference, 	product_supplier_price_te AS price FROM ". _DB_PREFIX_."product_supplier WHERE id_product=".$datarow['id_product']." AND id_product_attribute='0' LIMIT 1";
		  $sres=dbquery($squery);
		}
		if(mysqli_num_rows($sres) > 0)
		{ $srow=mysqli_fetch_array($sres); 
		  $csvline[] = $supplier_names[$srow['id_supplier']];
		  $csvline[] = $srow['reference'];
		  $csvline[] = $srow['price'];	  
		}
		else
		{ $csvline[] = "";
		  $csvline[] = "";
		  $csvline[] = "";
		}
		mysqli_free_result($sres);
      }		/* end of supplier */
	  else if ($infofields[$i][0] == "tags")
      { $tquery = "SELECT pt.id_tag,name  FROM "._DB_PREFIX_."product_tag pt";
		$tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag";
		if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
		    $tquery .= " AND t.id_lang=pt.id_lang";
		$tquery .= " WHERE pt.id_product='".$datarow['id_product']."' AND t.id_lang=".$id_lang." AND length(name)>0";
		$tres=dbquery($tquery);
		$idx = 0;
		$tmp = "";
		while ($trow=mysqli_fetch_array($tres)) 
		{ if($idx++ > 0) $tmp .= $subseparator;
		  $tmp .= $trow["name"];
		  $idx++;
		}
		$csvline[] = $tmp;
		mysqli_free_result($tres);
		
		foreach($extralangfields AS $extralangfield)
		{ $extralangcodes[] = $isocode = $extralangs[$extralangfield];
		  $tquery = "SELECT pt.id_tag,name  FROM "._DB_PREFIX_."product_tag pt";
		  $tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag";
		  if(version_compare(_PS_VERSION_ , "1.6.1", ">="))
		    $tquery .= " AND t.id_lang=pt.id_lang";
		  $tquery .= " WHERE pt.id_product='".$datarow['id_product']."' AND t.id_lang=".$extralangfield." AND length(name)>0";
		  $tres=dbquery($tquery);
		  $idx = 0;
		  $tmp = "";
		  while ($trow=mysqli_fetch_array($tres)) 
		  { if($idx++ > 0) $tmp .= $subseparator;
		    $tmp .= $trow["name"];
		  }
		  $csvline[] = $tmp;
		  mysqli_free_result($tres);
		}
	  }
      else if ($infofields[$i][0] == "VAT")
      { $csvline[] = $datarow["id_tax_rules_group"];
		$csvline[] = (float)$myvalue;
      }
      else if ($infofields[$i][0] == "virtualp")
	  {	
	    if($datarow['is_virtual'] == 0)
		{ $csvline[] = "";
		  $csvline[] = "";
	      $csvline[] = "";
	      $csvline[] = "";
	      $csvline[] = "0";	 
		}	
		else if($datarow['filename'] != "") 
	    { if(!file_exists($download_dir."/".$datarow["filename"])) 
		    $csvline[] = "file missing";
		  else
		    $csvline[] = $datarow["display_filename"];		  
		  $csvline[] = $datarow["date_expiration"];
	      $csvline[] = $datarow["nb_days_accessible"];
	      $csvline[] = $datarow["nb_downloadable"];
	      $csvline[] = "1";	 
		}
	  }		/* end of virtualp */
      else if ($infofields[$i][0] == "warehousing")
      { 
        $wquery = "SELECT w.id_warehouse, w.name AS whname, physical_quantity, usable_quantity, price_te, location";
		$wquery .= " FROM ". _DB_PREFIX_."warehouse w";
	    $wquery .= " LEFT JOIN ". _DB_PREFIX_."stock st on w.id_warehouse=st.id_warehouse AND st.id_product='".$datarow['id_product']."'";
	    $wquery .= " LEFT JOIN ". _DB_PREFIX_."warehouse_product_location wpl on w.id_warehouse=wpl.id_warehouse AND wpl.id_product='".$datarow['id_product']."'";		  
		$wquery .= " WHERE st.id_warehouse IS NOT NULL OR wpl.id_warehouse IS NOT NULL";
		$wquery .= " ORDER BY w.name";
 		$wres=dbquery($wquery);
		$wids = $wnames = $locations = array();
		while ($wrow=mysqli_fetch_array($wres)) 
		{ $wnames[] = $wrow["whname"];
		  $wids[] = $wrow["id_warehouse"];
		  $locations[] = $wrow["location"];		  
		}
//		$csvline[] = implode(",",$wids);
		$csvline[] = implode(",",$wnames);
		$csvline[] = implode(",",$locations);		
		mysqli_free_result($wres);
	  }
      else
         $csvline[] = $myvalue;
	  if(in_array($infofields[$i][0], $statfields))
	    $stattotals[$infofields[$i][0]] += $myvalue;
    }
	
	foreach($features AS $key => $feature)
    { if (in_array("featr".$key, $input["fields"]))
	  { $fquery = "SELECT fv.custom,fl.value, fp.id_feature_value FROM ". _DB_PREFIX_."feature_product fp";
	    $fquery.=" LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value";
	    $fquery.=" LEFT JOIN ". _DB_PREFIX_."feature_value_lang fl ON fp.id_feature_value=fl.id_feature_value AND fl.id_lang='".$id_lang."'";
	    $fquery.=" WHERE fp.id_product='".$datarow['id_product']."' AND fv.id_feature='".$key."'";
	    $fres=dbquery($fquery);
	    $first = true;
		$feats = array();
	    while ($frow=mysqli_fetch_array($fres))
	    { $feats[] = $frow['value'];
		}
		$csvline[] = implode($subseparator,$feats);	
		
		foreach($extralangfields AS $extralangfield)
		{ $extralangcodes[] = $isocode = $extralangs[$extralangfield];
		  $flquery = "SELECT fv.custom,fl.value, fp.id_feature_value FROM ". _DB_PREFIX_."feature_product fp";
	      $flquery.=" LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value";
	      $flquery.=" LEFT JOIN ". _DB_PREFIX_."feature_value_lang fl ON fp.id_feature_value=fl.id_feature_value AND fl.id_lang='".$extralangfield."'";
	      $flquery.=" WHERE fp.id_product='".$datarow['id_product']."' AND fv.id_feature='".$key."'";
	      $flres=dbquery($flquery);
	      $first = true;
		  $feats = array();
	      while ($flrow=mysqli_fetch_array($flres))
	      { $feats[] = $flrow['value'];
		  }
		  $csvline[] = implode($subseparator,$feats);	
		}
	  }
	}

    $x++;
    publish_csv_line($out, $csvline, $separator);
  }
  fclose($out);
  
function publish_csv_line($out, $csvline, $separator)
{ fputcsv3($out, $csvline, $separator);
}
  
/* if fputcsv3 doesn't work this can be used as alternative. It is one of the options mentioned in the comment section of php.net for fputcsv() */
function fputcsv2 ($fh, array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false) { 
    $delimiter_esc = preg_quote($delimiter, '/'); 
    $enclosure_esc = preg_quote($enclosure, '/'); 

    $output = array(); 
    foreach ($fields as $field) { 
        if ($field === null && $mysql_null) { 
            $output[] = 'NULL'; 
            continue; 
        } 

        $output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? ( 
            $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure 
        ) : $field; 
    } 

    fwrite($fh, join($delimiter, $output) . "\n"); 
} 

