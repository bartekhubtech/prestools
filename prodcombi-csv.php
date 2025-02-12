<?php 
/* For better understanding this file is divided in sections.
 * 1. include files 
 * 2. handling input variables
 * 3. make field declarations and build infofields array of displayed fields
 * 4. retrieve system settings and build php blocks for select fields 
 * 5. build and execute the product query 
 * 6a. The main table: the column headers for product
 * 6b. The main table: the column headers for combination
 * 7. data collection : the product data
 * 8. data collection : the combination data
 * 9. csv output
*/
/* section 1: includes */
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
if(!include $prestools_settings["prestoolslanguage"].".php") colordie( "Language file ".$prestools_settings["prestoolslanguage"].".php was not found!");

/* section 2: handling input variables */
$input = $_GET; 
$verbose = "false";
if(!isset($input['search_txt1'])) $input['search_txt1'] = "";
$search_txt1 = trim(mysqli_real_escape_string($conn,$input['search_txt1']));
if(!isset($input['search_txt2'])) $input['search_txt2'] = "";
$search_txt2 = trim(mysqli_real_escape_string($conn,$input['search_txt2']));
if(!isset($input['search_txt3'])) $input['search_txt3'] = "";
$search_txt3 = trim(mysqli_real_escape_string($conn,$input['search_txt3']));
if(!isset($input['search_cmp1'])) $input['search_cmp1'] = "in";
if(!isset($input['search_cmp2'])) $input['search_cmp2'] = "in";
if(!isset($input['search_cmp3'])) $input['search_cmp3'] = "in";
if(!isset($input['search_fld1']) || ($input['search_fld1'] == "")) $input['search_fld1'] = "main product fields";
if(!isset($input['search_fld2']) || ($input['search_fld2'] == "")) $input['search_fld2'] = "main product fields";
if(!isset($input['search_fld3']) || ($input['search_fld3'] == "")) $input['search_fld3'] = "main product fields";
if(!isset($input['combisearch_txt1'])) $input['combisearch_txt1'] = "";
$combisearch_txt1 = mysqli_real_escape_string($conn,$input['combisearch_txt1']);
if(!isset($input['combisearch_txt2'])) $input['combisearch_txt2'] = "";
$combisearch_txt2 = mysqli_real_escape_string($conn,$input['combisearch_txt2']);
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
$startrec = intval($input['startrec']);
if(!isset($input['numrecs'])) $input['numrecs']="50";
$numrecs = intval($input['numrecs']);
if(!isset($input['maxrows'])) $input['maxrows']="500";
$maxrows = intval($input['maxrows']);
if(!isset($input['id_category'])) {$id_category=0;} else {$id_category = intval($input['id_category']);}

if(isset($input['id_shop']) && (intval($input['id_shop'])!=0)) $id_shop = intval($input['id_shop']);
else $id_shop = get_configuration_value('PS_SHOP_DEFAULT');
$def_lang = get_configuration_value('PS_LANG_DEFAULT');
if(isset($input['id_lang']) && (intval($input['id_lang'])!=0)) $id_lang = intval($input['id_lang']);
else $id_lang = $def_lang;

if(!isset($input['startdate'])) $input['startdate']="";
if(!isset($input['enddate'])) $input['enddate']="";
if((!isset($input['rising'])) || ($input['rising'] == "ASC")) {$rising = "ASC";} else {$rising = "DESC";}
if(!isset($input['order'])) /* sorting order */
{ if($id_category == 0) {$input['order']="id_product";} else {$input['order']="position";}
}

if(!isset($input['imgformat'])) {$input['imgformat']="";}
if(!isset($input['groupa'])) $groupa="0"; else $groupa=intval($input['groupa']); 
if(!isset($input['groupb'])) $groupb="0"; else $groupb=intval($input['groupb']); 
if(!isset($input['attributea'])) $attributea="0"; else $attributea=intval($input['attributea']); 
if(!isset($input['attributeb'])) $attributeb="0"; else $attributeb=intval($input['attributeb']);
if(isset($input["discount_included"])) $discount_included = 1; else $discount_included=0;
if(isset($input["suppliers_included"])) $suppliers_included = 1; else $suppliers_included=0;
if(isset($input["stats_included"])) $stats_included = 1; else $stats_included=0;

if(empty($input['fields'])) // if not set, set default set of active fields
  $input['fields'] = array("name","priceVAT");

$langselblock = ""; /* used in the searchblock */
$langcopyselblock = ""; /* used in "copy from other lang" mass edit option; Note: this becomes javascript */
$langids = array();
$langcodes = array();
$query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ if($def_lang == $row["id_lang"])
  { $def_langname = $row['name'];
    $def_iso_code = $row['iso_code'];
  }
  if($id_lang == $row["id_lang"])
  { $iso_code = $row['iso_code'];
    $languagename = $row['name'];
  }
  $selected='';
  if ($row['id_lang']==$id_lang) 
    $selected=' selected="selected" ';
  else 
    $langcopyselblock .= '<option value='.$row['id_lang'].'>'.$row['language_code'].'</option>';
  $langselblock .= '<option  value="'.$row['id_lang'].'" '.$selected.'>'.$row['name'].'</option>';
  $langids[] = $row["id_lang"];
  $langcodes[$row["id_lang"]] = $row["iso_code"];
}
if(!isset($iso_code))
{ $iso_code = $def_iso_code;
  $languagename = $def_langname;
  echo "<b>Illegal value for default or selected language!</b> Using ".$languagename;
}

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
   "accessories" => array("accessories",null, "accessories", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "accessories"),
   "active" => array("active",null, "active", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.active"),
   "aDeliveryT" => array("aDeliveryT",null, "additional_delivery_times", DISPLAY, 1, LEFT, null, NOT_EDITABLE, "p.additional_delivery_times"),   
   "aShipCost" => array("aShipCost",null, "additional_shipping_cost", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.additional_shipping_cost"),
   "attachmnts" => array("attachmnts",null, "attachmnts", DISPLAY, 0, LEFT, null, NOT_EDITABLE, ""), 
   "available_now" => array("available_now",null, "available_now", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.available_now"),
   "available_later" => array("available_later",null, "available_later", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.available_later"), 
   "available_date" => array("available_date",null, "available_date", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.available_date"),
   /* availorder combines two of Prestashop's datafields: available_for_order and show_price */
   "availorder" => array("availorder",null, "available_for_order", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.available_for_order"),
   "cache_is_pack" => array("cache_is_pack",null, "cache_is_pack", DISPLAY, 0, LEFT, 0, NOT_EDITABLE, "p.cache_is_pack"),
   "carrier" => array("carrier",null, "carrier", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "cr.name"),
   "category" => array("category",null, "id_category_default", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.id_category_default"),
   "combinations" => array("combinations",null, "combinations", DISPLAY, 0, LEFT, 0, INPUT, "combinations"),
   "condition" => array("condition",null, "condition", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.condition"),
   "customizations" => array("customizations",null, "custFlds", DISPLAY, 0, LEFT, null, INPUT, "custFlds"),
   "date_add" => array("date_add",null, "date_add", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.date_add"),  
   "date_upd" => array("date_upd",null, "date_upd", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.date_upd"),
   "deliInStock" => array("deliInStock",null, "delivery_in_stock", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.delivery_in_stock"),
   "deliOutStock" => array("deliOutStock",null, "delivery_out_stock", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.delivery_out_stock"),   
   "description" => array("description",null, "description", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.description"),
   "description_short" => array("description_short",null, "description_short", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.description_short"),
   "discount" => array("discount",null, "discount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "discount"),
   "ean" => array("ean",null, "ean13", DISPLAY, 200, LEFT, null, NOT_EDITABLE, "p.ean13"),
   "ecotax" => array("ecotax",null, "ecotax", DISPLAY, 200, LEFT, null, NOT_EDITABLE, "ps.ecotax"),
   "featureEdit" => array("featureEdit",null, "name", DISPLAY, 0, LEFT, null, NOT_EDITABLE, ""), // name here is a dummy that is not used
   "isbn" => array("isbn",null, "isbn", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.isbn"),
   "id_product" => array("id_product",array("id","id","id"), "id_product", DISPLAY, 0, RIGHT, null,NOT_EDITABLE, "p.id_product"),
   "image" => array("image",null, "name", DISPLAY, 0, LEFT, 0, EDIT_BTN, ""), // name here is a dummy that is not used
   "indexed" => array("indexed",null, "indexed", DISPLAY, 0, LEFT, 0, NOT_EDITABLE, "ps.indexed"),
   "indexes" => array("indexes",null, "indexes", DISPLAY, 0, LEFT, 0, NOT_EDITABLE, "p.indexed"),
   "link_rewrite" => array("link_rewrite",null, "link_rewrite", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.link_rewrite"),
   "location" => array("location",null, "location", DISPLAY, 0, LEFT, null, INPUT, "s.location"),
   "ls_alert" => array("ls_alert",null, "low_stock_alert", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.low_stock_alert"), 
   "ls_threshold" => array("ls_threshold",null, "low_stock_threshold", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.low_stock_threshold"),
   "manufacturer" => array("manufacturer",null, "manufacturer", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "m.name"),
   "meta_description" => array("meta_description",null, "meta_description", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.meta_description"),
   "meta_keywords" => array("meta_keywords",null, "meta_keywords", DISPLAY, 0, RIGHT, null, TEXTAREA, "pl.meta_keywords"),
   "meta_title" => array("meta_title",null, "meta_title", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.meta_title"),
   "minimal_quantity" => array("minimal_quantity",null, "minimal_quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.minimal_quantity"),
   "mpn" => array("mpn",null, "mpn", DISPLAY, 0, LEFT, null, INPUT, "p.mpn"),
   "name" => array("name",null, "name", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "pl.name"),
   "online_only" => array("online_only",null, "online_only", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.online_only"),
   "on_sale" => array("on_sale",null, "on_sale", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.on_sale"),
   "out_of_stock" => array("out_of_stock",null, "out_of_stock", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "s.out_of_stock"),
   "pack_stock_type" => array("pack_stock_type",null, "pack_stock_type", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.pack_stock_type"),
/* pro memory: s.physical_quantity in PS 1.7: this is automatically set when an order is placed */
   "position" => array("position",null, "position", DISPLAY, 0, RIGHT, null, NOT_EDITABLE, "cp.position"),  
   "price" => array("price",null, "price", DISPLAY, 200, LEFT, null, NOT_EDITABLE, "ps.price"),
   "priceVAT" => array("priceVAT",null, "priceVAT", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "priceVAT"),
   "product_type" => array("product_type",null, "product_type", DISPLAY, 0, RIGHT, null, NOT_EDITABLE, "p.product_type"),  
   "quantity" => array("quantity",null, "quantity", DISPLAY, 0, LEFT, null, TEXTAREA, "s.quantity"),
   "redirect" => array("redirect",null, "redirect_type", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.redirect_type"),
   "reference" => array("reference",null, "reference", DISPLAY, 200, LEFT, null, INPUT, "p.reference"),
   "reserved" => array("reserved",null, "reserved_quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "s.reserved_quantity"),
   "shipdepth" => array("shipdepth",null, "depth", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.depth"), 
   "shipheight" => array("shipheight",null, "height", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.height"),
   "shipweight" => array("shipweight",null, "weight", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.weight"),
   "shipwidth" => array("shipwidth",null, "width", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.width"),
   "shopz" => array("shopz",null, "id_shop", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.id_shop"),
   "show_condition" => array("show_condition",null, "show_condition", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.show_condition"),
   /* stockflags combines two of Prestashop's datafields: depends_on_stock and advanced_stock_management */
   "stockflags" => array("stockflags",null, "depends_on_stock", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "s.depends_on_stock"),
   "state" => array("state",null, "state", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "p.state"),   /* STATE_TEMP=0 STATE_SAVED=1 */
   "supplier" => array("supplier",null, "supplier", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "su.name"),
   "supplRef" => array("supplRef",null, "reference", DISPLAY, 200, LEFT, null, NOT_EDITABLE, "p.supplier_reference"),
   "tags" => array("tags",null, "tags", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "tg.name"),
   "unity" => array("unity",null, "unity", DISPLAY, 0, LEFT, null, INPUT, "ps.unity"),
   "unitPrice" => array("unitPrice",null, "unit_price", DISPLAY, 0, LEFT, null, INPUT, "ps.unit_price"),
   "unitPRatio" => array("unitPRatio",null, "unit_price_ratio", DISPLAY, 0, LEFT, null, INPUT, "ps.unit_price_ratio"), 
   "upc" => array("upc",null, "upc", DISPLAY, 200, LEFT, null, NOT_EDITABLE, "p.upc"),   
   "VAT" => array("VAT",null, "id_tax_rules_group", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.id_tax_rules_group"),
   "virtualp" => array("virtualp",null, "virtualp", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "virtualp"), /* virtual product */
   "visibility" => array("visibility",null, "visibility", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.visibility"), 
   "warehousing" => array("warehousing",null, "quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "w.id_warehouse"),
   "wholesaleprice" => array("wholesaleprice",null, "wholesale_price", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.wholesale_price")
   ); 

  /* the fields for combinations */
  /* 0=showname; 1=left/right; 2=hide/display; 3=fieldname; 4=searchable, 5=fieldname */
  $combifields = array(
	array("combination", RIGHT,DISPLAY,"",0,"combination"),
	array("ids", RIGHT,HIDE,"",0,"ids"),
//    array("id_product_attribute",RIGHT,HIDE,"id",1), /* pro memory; hardcoded in cell 1 */
	array("wholesale_price",RIGHT,HIDE,"ws_price",1,"pas.wholesale_price"),
	array("price",RIGHT,DISPLAY,"",1,"pas.price"),
	array("priceVAT",RIGHT,DISPLAY,"",1,"priceVAT"),
	array("ecotax",RIGHT,HIDE,"",1,"pas.ecotax"),
	array("weight", RIGHT,DISPLAY,"",1,"pa.weight"),
	array("unit_price_impact",RIGHT,HIDE,"uprice_impact",1,"pas.unit_price_impact"),
	array("default_on", RIGHT,DISPLAY,"",1,"pas.default_on"),
	array("minimal_quantity",RIGHT,HIDE,"",1,"pas.minimal_quantity"),
	array("available_date",RIGHT,HIDE,"avail_date",1,"pas.available_date"),
	array("reference",RIGHT,DISPLAY,"",1,"pa.reference"),
	array("supplRef",RIGHT,HIDE,"supplRef",1,"pa.supplier_reference"),
	array("location", RIGHT,HIDE,"",1,"sa.location"),
	array("ean",RIGHT,HIDE,"",1,"pa.ean"),
	array("upc",RIGHT,HIDE,"",1,"pa.upc"),
	array("quantity", RIGHT,DISPLAY,"",1,"sa.quantity"),
	array("image",RIGHT,DISPLAY,"",0,"pai.image"));
    if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	  $combifields[] = array("isbn",RIGHT,HIDE,"",1,"pa.isbn");
    if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	{ $combifields[] = array("low_stock_threshold",RIGHT,HIDE,"ls_threshold",1,"pas.low_stock_threshold");
	  $combifields[] = array("low_stock_alert",RIGHT,HIDE,"ls_alert",1,"pas.low_stock_alert");
	}
    if (version_compare(_PS_VERSION_ , "1.7.7", ">="))
	  $combifields[] = array("mpn",RIGHT,HIDE,"",1,"pa.mpn",);
	if($discount_included)
	  $combifields[] = array("discount", RIGHT,DISPLAY,"",1,"discount");
	if($suppliers_included)
	  $combifields[] = array("suppliers", RIGHT,DISPLAY,"",1,"suppliers");
    if (in_array("salescnt", $input["fields"]))
	  $combifields[] = array("salescnt",RIGHT,DISPLAY,"",1,"salescnt");
    if (in_array("revenue", $input["fields"]))
	  $combifields[] = array("revenue",RIGHT,DISPLAY,"",1,"revenue");
    if (in_array("orders", $input["fields"]))
	  $combifields[] = array("orders",RIGHT,DISPLAY,"",1,"orders");
    if (in_array("buyers", $input["fields"]))
	  $combifields[] = array("buyers",RIGHT,DISPLAY,"",1,"buyers");	  
  
  $numfields = sizeof($combifields); /* number of fields */
  for($i=0; $i<$numfields; $i++)
  { if($combifields[$i][3] == "") 
	{ $pos =strpos(".", $combifields[$i][0]);
	  if($pos <= 0)
		  $combifields[$i][3] = $combifields[$i][0]; 
	  else
		  $combifields[$i][3] = substr($combifields[$i][0],$pos+1); 	
	}
  }
  
/* remove fields from the field_array that are not present in the used Prestashop version */
$x = 0;
$deleters = array();
foreach($field_array AS $key => $farray)
{ if((($key=="pack_stock_type") && (version_compare(_PS_VERSION_ , "1.6.0.12", "<")))
		 || ($key == "fearureEdit")
		 || (in_array($key, array("isbn", "show_condition","state")) && (version_compare(_PS_VERSION_ , "1.7.0", "<")))
		 || (in_array($key, array("reserved")) && (version_compare(_PS_VERSION_ , "1.7.2", "<")))
		 || (in_array($key, array("ls_threshold", "aDeliveryT", "deliInStock","deliOutStock","ls_alert")) && (version_compare(_PS_VERSION_ , "1.7.3", "<")))
		 || (in_array($key, array("location")) && (version_compare(_PS_VERSION_ , "1.7.5", "<")))
		 || (in_array($key, array("mpn")) && (version_compare(_PS_VERSION_ , "1.7.7", "<")))
		 || (in_array($key, array("product_type")) && (version_compare(_PS_VERSION_ , "1.7.8", "<"))) 
		 || (in_array($key, array("unitPrice")) && (version_compare(_PS_VERSION_ , "8.0", "<"))))
		 $deleters[] = $x;
  $x++;
}
rsort($deleters); /* we have to work from high to low as deleting the low numbers first we would need to adapt the higher ones */
foreach($deleters AS $deleter)
  array_splice($field_array,$deleter,1);
  
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
  foreach($prestools_settings["fieldsorder"] AS $ofield)
  { if (in_array($ofield, $input["fields"]))
    { 	$infofields[$if_index++] = $field_array[$ofield];
	}
  }
  foreach($field_array AS $key => $value)
  { if ((in_array($key, $input["fields"])) && (!in_array($key, $prestools_settings["fieldsorder"])))
    { $infofields[$if_index++] = $value;
	}
  }

/* section 4: retrieve system settings and build php blocks for select fields */

$statfields = array("salescnt", "revenue","orders","buyers");


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

/* making shop block */
    $shopblock = "";
	$shops = $shop_ids = array();
	$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	while ($row=mysqli_fetch_array($res)) 
	{ $selected='';
      if ($row['id_shop']==$id_shop) 
		$selected=' selected="selected" ';
      $shopblock .= '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['id_shop']."-".$row['name'].'</option>';
	  $shops[] = $row['name'];
	  $shop_ids[] = $row['id_shop'];
	}
	
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

  $availordertypes = array(0=>"available", 1=>"show price only",2=>"not available");
  $packstocktypes = array(0=>"Decrement pack only", 1=>"Decrement products in pack only",2=>"Decrement both",3=>"Default");
  $stockflagsarray = array("Manual","Adv Stock Management","ASM with Warehousing");

/* look for double category names */
  $duplos = array();
  $query = "select name,count(*) AS duplocount from ". _DB_PREFIX_."category_lang WHERE id_lang='".$id_lang."' AND id_shop='".$id_shop."' GROUP BY name HAVING duplocount > 1";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  {  $duplos[] = $row["name"];
  }
 
/* Get category names and rewrites */
  $query = "select c.id_category,name,link_rewrite, id_parent from "._DB_PREFIX_."category c";
  $query .= " left join "._DB_PREFIX_."category_lang cl ON c.id_category=cl.id_category AND id_lang='".$id_lang."'";
  $query .= " AND id_shop='".$id_shop."' ORDER BY name";
  $res=dbquery($query);
  $category_names = $category_rewrites = $category_parents = array();
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
if(in_array('supplier', $input["fields"]) || $suppliers_included)
{ $query = "select id_supplier,name from ". _DB_PREFIX_."supplier ORDER BY name";  
  $res=dbquery($query);
  $supplier_names = array();
  $supplierblock0 = '<input type=hidden name="supplier_defaultCQX"><input type=hidden name="mysupsCQX">';
  $supplierblock0 .= '<table><tr><td><select id="supplierlistCQX" size=2>';
  $supplierblock1 = "";
  while ($row=mysqli_fetch_array($res)) 
  { $supplier_names[$row['id_supplier']] = $row['name'];
    $supplierblock1 .= '<option value="'.$row['id_supplier'].'">'.str_replace("'","\'",$row['name']).'</option>';
  }
  $supplierblock1 .= '</select>';
  $supplierblock2 = '</td><td><nobr><a href=# onClick=" Addsupplier(\\\'CQX\\\',1); reg_change(this); return false;"><img src=add.gif border=0></a> &nbsp; &nbsp; ';
  $supplierblock2 .= '<a href=# onClick="Removesupplier(\\\'CQX\\\'); reg_change(this); return false;"><img src=remove.gif border=0></a></nobr></td><td><select size=2 id="supplierselCQX"></select>';
  $supplierblock2 .= '</td><td style="vertical-align:middle"><a href="#" onclick="MakeSupplierDefault(\\\'CQX\\\'); reg_change(this); return false;"><img src="starr.jpg" border="0"></a></td></tr></table>';
}
else 
  $supplierblock0 = $supplierblock1 = $supplierblock2 = "";

/* Make blocks for features */
$query = "SELECT fl.id_feature, name FROM ". _DB_PREFIX_."feature_lang fl";
$query .= " LEFT JOIN ". _DB_PREFIX_."feature_shop fs ON fs.id_feature = fl.id_feature";
$query .= " WHERE id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
$query .= " ORDER BY id_feature";
$res = dbquery($query);
$features = array();
$featurelist = array();
while($row = mysqli_fetch_array($res))
{ $features[$row['id_feature']] = $row['name'];
  if(in_array(str_replace('"','_',$row['name']), $input["fields"]))
  { $featurelist[$row['id_feature']] = $row['name'];
    $featurekeys[] = $row['id_feature'];
  }
}
$featurecount = 0;
  foreach($features AS $key => $feature)
  {	$cleanfeature = str_replace('"','_',$feature);
    if (in_array($cleanfeature, $input["fields"]))
		$featurecount++;
  }



/* section 5: build and execute the product query */

$idfields = array("accessories","p.id_product","cl.id_category","ps.id_category_default","m.id_manufacturer","p.id_supplier","ps.id_tax_rules_group"); /* in these fields you can place comma-separated id numbers */

$wheretext = "";
$manufacturer_needed = false;
for($x=1; $x<=3; $x++)
{ $search_cmp = $input["search_cmp".$x];
  $search_txt = $GLOBALS["search_txt".$x];
  $search_fld = $input["search_fld".$x];
  
//  if($search_txt == "") && (!in_array($search_fld, array("su.name","combinations","cr.name","m.name","pl.description","pl.description_short","discount","custFlds","virtualp"))) && (substr($search_fld,0,7) != "sattrib") && (substr($search_fld,0,7) != "sfeatur")) continue;
  
  if(($search_fld == "m.name") || (($search_fld == "main product fields") && ($search_txt !=""))) $manufacturer_needed = true;
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
    if($search_fld == "main product fields")
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
    else if ($search_fld == "p.indexed")
    { $wheretext .= " AND sw.word ".$nottext." ".$inc;
    }
    else if($search_fld == "su.name")
	  $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu LEFT JOIN ". _DB_PREFIX_."supplier su ON psu.id_supplier=su.id_supplier WHERE psu.id_product = p.id_product AND su.name ".$inc.")";
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
    else if($search_fld == "p.reference") /* check here also for supplier reference */
    { $wheretext .= " AND (".$nottext.$search_fld." ".$inc." ";
   	  $wheretext .= " OR ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu WHERE psu.id_product = p.id_product AND psu.product_supplier_reference ".$inc."))";
    }
    else if(substr($search_fld,0,7) == "sattrib") /* attribute search */
    { $id_attribute_group = substr($search_fld,7);
      $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pa 
   LEFT JOIN ". _DB_PREFIX_."product_attribute_combination pac ON pa.id_product_attribute=pac.id_product_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute_lang al ON pac.id_attribute = al.id_attribute 
   LEFT JOIN ". _DB_PREFIX_."attribute atr ON pac.id_attribute = atr.id_attribute 
   WHERE pa.id_product=p.id_product AND atr.id_attribute_group=".$id_attribute_group." AND ".$nottext." (al.name ".$inc."))";
    }
    else if(substr($search_fld,0,9) == "sidattrib") /* attribute id search */
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
      $wheretext .= " AND ".$nottext.$search_fld." ".$inc." ";
   }
   else /* $search_txt == "" */
   { 
     $nottexta = ""; /* here we use a different logic - so a different variable */
     if(($search_cmp == "not_eq") || ($search_cmp == "!in"))
	   $nottexta = "NOT";
     if($search_fld == "su.name")      /* supplier: this works when search_txt field is empty */
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu WHERE psu.id_product = p.id_product)";
     else if($search_fld == "combinations") /* combinations: this works when search_txt field is empty */
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_attribute pax WHERE pax.id_product = p.id_product)";
     else if($search_fld == "cr.name")      /* carriers: this works when search_txt field is empty */
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_carrier pcx WHERE pcx.id_product = p.id_product)";
     else if($search_fld == "m.name")      /* manufacturers: this works when search_txt field is empty */
	 { $wheretext .= " AND ".$nottexta." p.id_manufacturer=0";
	 }
     else if($search_fld == "pl.description") 
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_lang plx WHERE plx.id_product = p.id_product AND plx.id_lang=".$id_lang." AND plx.description='')";
     else if($search_fld == "pl.description_short") 
	   $wheretext .= " AND ".$nottexta." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_lang plx WHERE plx.id_product = p.id_product AND plx.id_lang=".$id_lang." AND plx.description_short='')";
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
  }
  /* finally three fields where it doesn't matter whether search_txt is filled */
  if($search_fld == "discount") /* this works also when search_txt field is empty */
	 $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."specific_price sp WHERE sp.id_product = p.id_product)";
  if($search_fld == "custFlds") /* this works also when search_txt field is empty */ 
	 $wheretext .= " AND ".$nottext." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."customization_field cf WHERE cf.id_product = p.id_product)";
  /* at the moment virtual products cannot have combinations; This is for future use */
  if($search_fld == "virtualp")
	 $wheretext .= " AND ".$nottext." (p.is_virtual = 1)";
} /* end of for x=1; x<=3; x++; search block loop */

/* Note: we start with the query part after "from". First we count the total and then we take 100 from it */
/* DISTINCT is for when "with subcats" results in more than one occurence */
$queryterms = "DISTINCT p.*,pl.*,ps.*, cl.name AS catname,p.id_product AS ptest,pl.id_product AS pltest";
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

if (($order=="cp.position") && (($id_category == 0) || (sizeof($categories)>1)))
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

if((($input["search_fld1"]=="p.indexed") && ($input["search_txt1"]!=""))
  OR (($input["search_fld2"]=="p.indexed") && ($input["search_txt2"]!=""))
  OR (($input["search_fld3"]=="p.indexed") && ($input["search_txt3"]!="")))
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
{ $queryterms .= ", position";
}
$taxfields = array("priceVAT","t.rate","ps.id_tax_rules_group");
if(($discount_included == "1") || in_array($input["order"],$taxfields) || (in_array($input["search_fld1"],$taxfields)  == "VAT") || (in_array($input["search_fld2"],$taxfields)  == "VAT") || (in_array($input["search_fld3"],$taxfields)  == "VAT") || ((isset($input['combisearch_fld1'])) AND ($input['combisearch_fld1'] == "priceVAT")) || ((isset($input['combisearch_fld2'])) AND ($input['combisearch_fld2'] == "priceVAT")))
{ $queryterms .= ", t.rate";
  $query.=" LEFT JOIN ". _DB_PREFIX_."tax_rule tr ON tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state='0'";
  $query.=" LEFT JOIN ". _DB_PREFIX_."tax t ON t.id_tax=tr.id_tax";
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

$ckseg = "";
if((($input['combisearch_txt1'] != "") && ($input['combisearch_fld1'] !="")) 
|| (isset($input['combisearch_txt2']) && ($input['combisearch_txt2'] != "") && ($input['combisearch_fld2'] !=""))
|| (isset($input['konda_fld1']) && ($input["konda_fld1"] != "0")))
{ 
  $ckseg .= ' AND EXISTS (SELECT NULL FROM '._DB_PREFIX_.'product_attribute pa
	LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pas ON pa.id_product_attribute=pas.id_product_attribute AND pas.id_shop='.$id_shop.'
    LEFT JOIN '. _DB_PREFIX_.'product_supplier psu ON psu.id_product = pa.id_product AND psu.id_product_attribute = pa.id_product_attribute
	LEFT JOIN '._DB_PREFIX_.'stock_available sa ON pas.id_product_attribute=sa.id_product_attribute AND pas.id_shop=sa.id_shop WHERE pa.id_product=p.id_product';

  
/* first filter: only relevant products */
  for($x=1; $x<=2; $x++)
  { if((!isset($input["combisearch_fld".$x])) || ($input["combisearch_fld".$x]=="0"))
      continue;

	$combisearch_txt = trim(mescape($input['combisearch_txt'.$x]));
    $combisearch_cmp = trim(mescape($input['combisearch_cmp'.$x]));
    $combisearch_fld = trim(mescape(preg_replace('/[^a-zA-Z0-9_\-\.]/', "", $input['combisearch_fld'.$x])));  

    if(($combisearch_txt != "") && ($combisearch_fld !="") && ($combisearch_fld !="0"))
  { $csnottext = "";
	  if ($combisearch_fld == "priceVAT")
	    $fld = "(ROUND((((t.rate/100)+1)*pas.price),2))";
	else
        $fld = preg_replace('/\s/',"",$combisearch_fld);
      if($combisearch_cmp == "gt")
	    $csinc[$x] = "< '".$combisearch_txt."'";
      else if($combisearch_cmp == "gte")
	    $csinc[$x] = "<= '".$combisearch_txt."'";   
      else if(($combisearch_cmp == "eq") || ($combisearch_cmp == "not_eq"))
	    $csinc[$x] = "= '".$combisearch_txt."'"; 
      else if($combisearch_cmp == "lte")
	    $csinc[$x] = ">= '".$combisearch_txt."'";
      else if($combisearch_cmp == "lt")
	    $csinc[$x] = "> '".$combisearch_txt."'";
    else   /* default = "in": also for "not_in" */
	    $csinc[$x] = " like '%".$combisearch_txt."%'";
      if(($combisearch_cmp == "not_in") || ($combisearch_cmp == "not_eq"))
	   $csnottext = " NOT ";
	  if ($combisearch_fld == "reference")
		$ckseg .= ' AND ('.$csnottext.'psu.product_supplier_reference'.$csinc[$x].
			' OR '.$csnottext.$fld.$csinc[$x].")";
	  else
        $ckseg .= ' AND '.$csnottext.$fld.$csinc[$x];
  }
  }
  
  if(isset($input['konda_fld1']) && ($input["konda_fld1"] != "0"))
  { $konda_fld = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET["konda_fld1"]);
	$kondb_fld = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET["kondb_fld1"]);
	$kond_cmp = $_GET["kond_cmp1"];
	if($kond_cmp == "not_eq") $cmp = "!=";
	else if($kond_cmp == "eq") $cmp = "=";
    else if($kond_cmp == "gt") $cmp = ">";
    else if($kond_cmp == "gte") $cmp = ">=";
    else if($kond_cmp == "lt") $cmp = "<";
    else if($kond_cmp == "lte") $cmp = "<="; 
    else if($kond_cmp == "not_in") $cmp = "NOT IN"; 
	else $cmp = "IN"; 
    $ckseg .= " AND ".$konda_fld." ".$cmp." ".$kondb_fld;
  }
  $ckseg .= ')';
}

$agseg = "";
if(($attributea !="0") || ($attributeb !="0") || ($groupa !="0") || ($groupb !="0"))
{
  if($attributea !="0")
  { $agseg .= ' AND EXISTS(SELECT NULL FROM '._DB_PREFIX_.'product_attribute_combination paca';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute paa ON paa.id_product_attribute=paca.id_product_attribute';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pasa ON paca.id_product_attribute=pasa.id_product_attribute';
	$agseg .= ' WHERE paca.id_attribute='.$attributea.' AND paa.id_product=p.id_product AND pasa.id_shop="'.$id_shop.'")';
  }
  else if($groupa != "0")
  { $agseg .= ' AND EXISTS(SELECT NULL FROM '._DB_PREFIX_.'attribute a';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute_combination paca ON a.id_attribute=paca.id_attribute';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute paa ON paa.id_product_attribute=paca.id_product_attribute';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pasa ON paca.id_product_attribute=pasa.id_product_attribute';
	$agseg .= ' WHERE a.id_attribute_group='.$groupa.' AND paa.id_product=p.id_product AND pasa.id_shop="'.$id_shop.'")';
  }
  if($attributeb !="0")
  { $agseg .= ' AND EXISTS(SELECT NULL FROM '._DB_PREFIX_.'product_attribute_combination pacb';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute pab ON pab.id_product_attribute=pacb.id_product_attribute';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pasb ON pacb.id_product_attribute=pasb.id_product_attribute';
	$agseg .= ' WHERE pacb.id_attribute='.$attributeb.' AND pab.id_product=p.id_product AND pasb.id_shop="'.$id_shop.'")';
  }
  else if($groupb != "0")
  { $agseg .= ' AND EXISTS(SELECT NULL FROM '._DB_PREFIX_.'attribute a';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute_combination paca ON a.id_attribute=paca.id_attribute';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute paa ON paa.id_product_attribute=paca.id_product_attribute';
	$agseg .= ' LEFT JOIN '._DB_PREFIX_.'product_attribute_shop pasa ON paca.id_product_attribute=pasa.id_product_attribute';
	$agseg .= ' WHERE a.id_attribute_group='.$groupb.' AND paa.id_product=p.id_product AND pasa.id_shop="'.$id_shop.'")';
  }
}

if(($ckseg != "") || ($agseg != ""))
  $wheretext .= $ckseg.$agseg;
else
{ /* check whether the product has attribute combinations */
  $wheretext .= ' AND exists(SELECT * FROM '._DB_PREFIX_.'product_attribute pa';
  $wheretext .= ' LEFT JOIN '. _DB_PREFIX_.'product_attribute_shop pas on pa.id_product_attribute=pas.id_product_attribute AND pas.id_shop="'.$id_shop.'"';
  $wheretext .= ' WHERE pa.id_product=p.id_product)';
}

$query.=" WHERE ps.id_shop='".$id_shop."' ".$wheretext;

  $stattotals = array("salescnt" => 0, "revenue"=>0,"orders"=>0,"buyers"=>0); /* store here totals for stats */
  if(in_array($order, $statfields))
  { $ordertxt = $statz[array_search($order, $statfields)];
  }
  else
    $ordertxt = str_replace(" ","",$order);
  /* GROUP BY p.id_product is for "With subcats" when the products is in more than one of the involved categories */
  $query .= " GROUP BY p.id_product ORDER BY ".$ordertxt." ".$rising." LIMIT ".$startrec.",".$numrecs;
  $query= "select SQL_CALC_FOUND_ROWS ".$queryterms.$query; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */
  $res=dbquery($query);
  $numrecs3 = mysqli_num_rows($res);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);
  $numrecs2 = $row2['foundrows'];
//  echo $query;
  
/* section 6: write http header */
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=product-'.date('Y-m-d-Gis').'.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

/* section 7: write csv headers */
  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }
  if(isset($input['psfields'])) $psfields = 1; else $psfields=0;
  $csvline = array();  // array for the fputcsv function
  
/* section 6a: The main table: the column headers for product */
 	$csvline[] = "id_product";
	$csvline[] = "id_product_attribute";

    for($i=2; $i<sizeof($infofields); $i++)
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
      { $csvline[] = "discount amount";
	    $csvline[] = "discount pct";
	    $csvline[] = "discount from";
	    $csvline[] = "discount to";
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
        $csvline[] = $infofields[$i][0]; // or $infofields[$i][1][0];
    }

    foreach($features AS $key => $feature)
    { $cleanfeature = str_replace('"','_',$feature);
      if (in_array($cleanfeature, $input["fields"]))
	  { $csvline[] = $feature;
	  } 	
	}
	
/* section 6b: The main table: the column headers for combination */
    if($psfields) /* add fields according to Prestashop specification (csv import sample) */
	{ $csvline[] = "Attribute (Name:Type:Position)*";
	  $csvline[] = "Value (Value:Position)*";
	}

    for($j=0; $j<$numfields; $j++)
    { if($combifields[$j][3] == "suppliers")
      { $csvline[] = "supplier"; 
	    $csvline[] = "supplier reference";
	    $csvline[] = "supplier price"; 
	  }
	  else if($combifields[$j][3] == "discount")
      { $csvline[] = "discount amount";
	    $csvline[] = "discount pct";
	    $csvline[] = "discount from";
	    $csvline[] = "discount to";
	  }
	  else
        $csvline[] = $combifields[$j][3];
    }

  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);

/* section 7: data collection : the product data */
  $x=0;
  $rowcnt = 0;
  $prodcount = 0;
  $legacy_images = get_configuration_value('PS_LEGACY_IMAGES');
  $haserrors = false;
  while (($datarow=mysqli_fetch_array($res)) && ($rowcnt < $maxrows)) 
  { $csvprodline = array();
    if(($datarow['name'] == "") || ($datarow['pltest'] === NULL) || ($datarow['ptest'] === NULL))
	{ $haserrors = true;
	  continue;
	}
	
	for($i=1; $i< sizeof($infofields); $i++)
    { 	  
      if($infofields[$i][0] == "priceVAT")
		$myvalue =  number_format(((($taxrates[$datarow["id_tax_rules_group"]]/100) +1) * $datarow['price']),2, '.', '');
	  else if($infofields[$i][0] == "VAT")
		$myvalue = $taxrates[$datarow['id_tax_rules_group']];
      else if ((!in_array($infofields[$i][0], array("attachmnts","carrier", "combinations"
	    ,"customizations","discount","indexes", "supplier","tags","virtualp")))
		  && (!in_array($infofields[$i][0],array("revenue","orders","salescnt","buyers"))))
        $myvalue = $datarow[$infofields[$i][2]];
	
	  /**************************************************************************************************/
	  /* Below the fields are listed alphabetically. Those missing get the default treatment at the end */
      /* id_product is skipped here */
	  if ($infofields[$i][0] == "id_product") continue;
	  else if ($infofields[$i][0] == "accessories")
	  { if($separator == ",")
		  $myvalue = str_replace(',',';',$myvalue);
        $csvprodline[] = $myvalue;
	  }  /* end of accessories */
	  
	  else if ($infofields[$i][0] == "aDeliveryT")  // additional_delivery_times
	  { $squery = "SELECT additional_delivery_times FROM ". _DB_PREFIX_."product WHERE id_product='".$datarow['id_product']."'";
		$sres=dbquery($squery);
		$srow=mysqli_fetch_array($sres);
		if($datarow["additional_delivery_times"] == "1")
			$csvprodline[] = "default info";	
		else if($datarow["additional_delivery_times"] == "2")
			$csvprodline[] = "product info";
		else 
			$csvprodline[] = "none";	
	  }		/* end of aDeliveryT */
	  
	  else if ($infofields[$i][0] == "attachmnts")
      { $cquery = "SELECT a.file_name, a.file, a.mime, l.name, p.id_attachment FROM ". _DB_PREFIX_."product_attachment p";
		$cquery .= " LEFT JOIN ". _DB_PREFIX_."attachment a ON a.id_attachment=p.id_attachment";
	    $cquery .= " LEFT JOIN ". _DB_PREFIX_."attachment_lang l ON a.id_attachment=l.id_attachment AND l.id_lang='".$id_lang."'";
	    $cquery .= " WHERE id_product='".$datarow['id_product']."'";
		$cres=dbquery($cquery);
		$prodline = "";
		$z=0;
		while ($crow=mysqli_fetch_array($cres)) 
		{	if($z++ > 0) $prodline .=  $subseparator;
			$prodline .=  $crow['name'];
	        if(!file_exists($download_dir."/".$crow["file"])) $prodline .=  " (missing)";
			}
	    $csvprodline[] = $prodline;
		mysqli_free_result($cres);
      }	 /* end of attachmnts */
	  
	  else if ($infofields[$i][0] == "availorder")
	  { $available_for_order = $datarow['available_for_order'];
		$show_price = $datarow['show_price'];
		if($available_for_order == 1)
		  $csvprodline[] = $availordertypes[0];
		else if($show_price == 1)
		  $csvprodline[] = $availordertypes[1];
		else
		  $csvprodline[] = $availordertypes[2];
	  }		/* end of availorder */

	  else if ($infofields[$i][0] == "cache_is_pack")
	  { if($myvalue == 1) /* check for parts it it is pack */
		{ $cquery = "SELECT p.*, ps.active  FROM ". _DB_PREFIX_."pack p";
		  $cquery .= " LEFT JOIN ". _DB_PREFIX_."product_shop ps ON p.id_product_item=ps.id_product AND ps.id_shop=".$id_shop;
		  $cquery .= " WHERE id_product_pack='".$datarow['id_product']."'";
		  $cres=dbquery($cquery);
		  $tmp = " (";
		  $first = true;
		  while($crow=mysqli_fetch_array($cres))
		  { if($first)
				$first = false;
			else
				$tmp .= ",";
			if($crow["active"] == "0") $tmp .= "<s>";
			if(is_null($crow["active"])) $tmp .= "<i>";
			$tmp .= $crow["quantity"]."*".$crow["id_product_item"];
			if($crow["id_product_attribute_item"] != "0")
				$tmp .= ":".$crow["id_product_attribute_item"];
			if(is_null($crow["active"])) $tmp .= "</i>";
			if($crow["active"] == "0") $tmp .= "</s>";
		  }
		  $tmp .= ")";
		  $csvprodline[] = $tmp;
		}
        else
		  $csvprodline[] = $myvalue;
	  }
	  
	  else if ($infofields[$i][0] == "carrier")
      { $cquery = "SELECT id_carrier_reference FROM ". _DB_PREFIX_."product_carrier WHERE id_product='".$datarow['id_product']."' AND id_shop='".$id_shop."' LIMIT 1";
		$cres=dbquery($cquery);
		if(mysqli_num_rows($cres) != 0)
		{ $cquery = "SELECT id_reference, cr.name FROM ". _DB_PREFIX_."product_carrier pc";
		  $cquery .= " LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0";
		  $cquery .= " WHERE id_product='".$datarow['id_product']."' AND id_shop='".$id_shop."' ORDER BY cr.name";
		  $cres=dbquery($cquery);
		  $carriers = array();
		  while ($crow=mysqli_fetch_array($cres)) 
		  { $carriers[] = $crow['name'];
		  }
		  $csvprodline[] = implode($subseparator,$carriers);
		}
		else
		  $csvprodline[] = "";
		mysqli_free_result($cres);
	  }	  /* end of carrier */
	  
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
	    $csvprodline[] = $catnames;
	    $csvprodline[] = $catids;
		mysqli_free_result($cres);
	  }
	  else if ($infofields[$i][0] == "combinations")
      { $cquery = "SELECT count(*) AS counter FROM ". _DB_PREFIX_."product_attribute";
	    $cquery .= " WHERE id_product='".$datarow['id_product']."'";
		$cres=dbquery($cquery);
		$crow=mysqli_fetch_array($cres);
		$csvprodline[] = $crow["counter"];
		mysqli_free_result($cres);
      }
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
		$csvprodline[] = $tmp;
		mysqli_free_result($dres);
	  }  /* end of customizations */
	  else if ($infofields[$i][0] == "discount")
     { $dquery = "SELECT sp.reduction,sp.reduction_type,sp.from,sp.to";
		$dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
	    $dquery .= " WHERE sp.id_product='".$datarow['id_product']."' AND (sp.id_shop='".$id_shop."' OR sp.id_shop='0') AND (sp.to >= NOW() OR sp.to = '0000-00-00 00:00:00' ) AND sp.id_product_attribute='0'";
		$dquery .= " ORDER BY sp.id_country, sp.id_group, sp.id_customer,sp.id_currency LIMIT 1"; /* order by should put zero's (=all) first */
		$dres=dbquery($dquery);
		if(mysqli_num_rows($dres) > 0)
		{ $drow=mysqli_fetch_array($dres);
		  if($drow["reduction_type"] == "pct")
		  { $csvprodline[] = "0";
		    $csvprodline[] = $drow['reduction'];
		  }
		  else
		  { $csvprodline[] = $drow['reduction'];
			$csvprodline[] = "0";
		  }
		  $csvprodline[] = $drow['from'];
		  $csvprodline[] = $drow['to'];
		}
		else
		{ $csvprodline[] = "";
		  $csvprodline[] = "";
		  $csvprodline[] = "";
		  $csvprodline[] = "";
		}
		mysqli_free_result($dres);
      }  /* end of discount */
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
		$csvprodline[] = $tmp;
	  }
      else if ($infofields[$i][0] == "image")
      { $iquery = "SELECT id_image,cover FROM ". _DB_PREFIX_."image WHERE id_product='".$datarow['id_product']."' ORDER BY cover DESC, position";
		$ires=dbquery($iquery);
		$id_image = 0;
		$xx = 0;
		$imsize = mysqli_num_rows($ires);
		$tmp = "";
		while ($irow=mysqli_fetch_array($ires)) 
		{ $tmp .= $imagebase.'img/p'.getpath($irow['id_image']).'/'.$irow['id_image'].'.jpg';
		  $xx++;
		  if($xx < $imsize)
		    $tmp .= $subseparator; // the space is necessary
		}
		$csvprodline[] = $tmp;
		mysqli_free_result($ires);
      }
	  else if ($infofields[$i][0] == "out_of_stock")
	  { $myvalue = $datarow["out_of_stock"];
		if($myvalue == "0") $csvprodline[] = "Deny orders";
		else if($myvalue == "1") $csvprodline[] = "Allow orders";
		else $csvprodline[] = "Default";		
	  }		/* end of out_of_stock */
	  
      else if ($infofields[$i][0] == "pack_stock_type")
      { $csvprodline[] = $packstocktypes[(int)$myvalue];
      }  	/* end of pack_stock_type */
	  
	  else if ($infofields[$i][0] == "position")
	  { $csvprodline[] = $datarow["position"]; 
	  }		/* end of position */
	  
	  else if ($infofields[$i][0] == "quantity")
	  { if($myvalue == "") $myvalue = "0"; /* handle cases when there are no entries in stock_available table */
		$csvprodline[] = $myvalue;
	  }		/* end of quantity */
	  else if ($infofields[$i][0] == "reserved")
	  { $aquery = "SELECT count(*) FROM `". _DB_PREFIX_."product_attribute` WHERE id_product='".$datarow['id_product']."'";
		$ares=dbquery($aquery);
		list($combination_count) = mysqli_fetch_row($ares); 

		if($combination_count == 0)
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
        $csvprodline[] = $rrow['reserved'];	  
	  }
	  else if ($infofields[$i][0] == "revenue")
      { $csvprodline[] = $datarow['revenue'];
      }	  	/* end of revenue */
	  else if ($infofields[$i][0] == "state")
	  { if($datarow["state"] == "0") $csvprodline[] = "STATE_TEMP";
		else if($datarow["state"] == "1") $csvprodline[] = "STATE_SAVED";
		else $csvprodline[] = "INVALID";
	  }
	  else if ($infofields[$i][0] == "shopz")
      { $squery = "SELECT GROUP_CONCAT(id_shop) AS shops FROM ". _DB_PREFIX_."product_shop";
	    $squery .= " WHERE id_product = '".$datarow['id_product']."' GROUP BY id_product";
		$sres=dbquery($squery);
		$srow=mysqli_fetch_array($sres);
		$csvprodline[] = $srow["shops"];
      }	  	/* end of shops */
	  
	  else if ($infofields[$i][0] == "stockflags")
	  { $csvprodline[] = $datarow['depends_on_stock'];
		$csvprodline[] = $datarow['advanced_stock_management'];
	  }		/* end of stockflags */
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
		  $csvprodline[] = $supplier_names[$srow['id_supplier']];
		  $csvprodline[] = $srow['reference'];
		  $csvprodline[] = $srow['price'];	  
		}
		else
		{ $csvprodline[] = "";
		  $csvprodline[] = "";
		  $csvprodline[] = "";
		}
		mysqli_free_result($sres);
      }
	  
	  else if ($infofields[$i][0] == "tags")
      { $tquery = "SELECT pt.id_tag,name FROM ". _DB_PREFIX_."product_tag pt";
		$tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag AND t.id_lang='".$id_lang."'";
	    $tquery .= " WHERE pt.id_product='".$datarow['id_product']."'";
		$tres=dbquery($tquery);
		$idx = 0;
		$tmp = "";
		while ($trow=mysqli_fetch_array($tres)) 
		{ if($idx++ > 0) $tmp .= $subseparator;
		  $tmp .= $trow["name"];
		  $idx++;
		}
		$csvprodline[] = $tmp;
		mysqli_free_result($tres);
	  } /* end of tags */
	  
      else if ($infofields[$i][0] == "VAT")
      { $csvprodline[] = $datarow["id_tax_rules_group"];
		$csvprodline[] = (float)$myvalue;
      }	/* end of VAT */
	  
      else if ($infofields[$i][0] == "virtualp")
	  {	$bgcolor = $visvirtual= $visfile = '';
	    if($datarow['is_virtual'] == 0)
		{ $csvprodline[] = "";
		  $csvprodline[] = "";
	      $csvprodline[] = "";
	      $csvprodline[] = "";
	      $csvprodline[] = "0";	 
		}	
		else if($datarow['filename'] != "") 
	    { if(!file_exists($download_dir."/".$datarow["filename"])) 
		    $csvprodline[] = "file missing";
		  else
		    $csvprodline[] = $datarow["display_filename"];		  
		  $csvprodline[] = $datarow["date_expiration"];
	      $csvprodline[] = $datarow["nb_days_accessible"];
	      $csvprodline[] = $datarow["nb_downloadable"];
	      $csvprodline[] = "1";	 
		}
	  }	  	/* end of virtualp */
	  
      else if ($infofields[$i][0] == "warehousing")
      { $wquery = "SELECT w.id_warehouse, w.name AS whname, physical_quantity, usable_quantity, price_te, location";
		$wquery .= " FROM ". _DB_PREFIX_."warehouse w";
	    $wquery .= " LEFT JOIN ". _DB_PREFIX_."stock st on w.id_warehouse=st.id_warehouse AND st.id_product='".$datarow['id_product']."'";
	    $wquery .= " LEFT JOIN ". _DB_PREFIX_."warehouse_product_location wpl on w.id_warehouse=wpl.id_warehouse AND wpl.id_product='".$datarow['id_product']."'";		  
		$wquery .= " WHERE st.id_warehouse IS NOT NULL OR wpl.id_warehouse IS NOT NULL";
		$wquery .= " ORDER BY w.name";
 		$wres=dbquery($wquery);
		$wids = $wnames = array();
		while ($wrow=mysqli_fetch_array($wres)) 
		{ $wnames[] = $wrow["whname"];
		  $wids[] = $wrow["id_warehouse"];
		  $locations[] = $wrow["location"];		  
		}
//		$csvprodline[] = implode(",",$wids);
		$csvprodline[] = implode(",",$wnames);
		$csvprodline[] = implode(",",$locations);		
		mysqli_free_result($wres);
	  }
      else
         $csvprodline[] = $myvalue;
	}
	
	foreach($features AS $key => $feature)
    { if (in_array($feature, $input["fields"]))
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
		$csvprodline[] = implode(",",$feats);	
	  }
	}

/* 8. data collection : the combination data   */
    $aqueryterms = "pas.*, pa.reference, psu.product_supplier_reference,pa.ean13, pa.supplier_reference";
    if (version_compare(_PS_VERSION_ , "1.7.0.0", ">="))
	  $aqueryterms .= ",pa.isbn";
    if (version_compare(_PS_VERSION_ , "1.7.3.0", ">="))
	  $aqueryterms .= ",pas.low_stock_threshold,pas.low_stock_alert";
    if (version_compare(_PS_VERSION_ , "1.7.7.0", ">="))
	  $aqueryterms .= ",pa.mpn";
    if (version_compare(_PS_VERSION_ , "1.7.5", ">=")) 
	  $aqueryterms .= ",sa.location"; 
    else
	  $aqueryterms .= ",pa.location"; 
    $aqueryterms .= " ,pi.id_image,pa.upc,sa.quantity,GROUP_CONCAT(pi.id_image) AS images";
    $aqueryterms .= " ,sa.depends_on_stock, il.legend, positions";
    $aquery = " FROM ". _DB_PREFIX_."product_attribute pa";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_shop pas on pa.id_product_attribute=pas.id_product_attribute AND pas.id_shop='".$id_shop."'";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_image pi on pa.id_product_attribute=pi.id_product_attribute ";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."image_lang il on il.id_image=pi.id_image AND il.id_lang='".$id_lang."'";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."product_supplier psu ON psu.id_product = pa.id_product AND psu.id_product_attribute = pa.id_product_attribute";
    if($share_stock == 0)
	  $aquery .=" left join ". _DB_PREFIX_."stock_available sa on pa.id_product=sa.id_product AND sa.id_product_attribute=pa.id_product_attribute AND sa.id_shop = '".$id_shop."'";
	else
      $aquery .=" left join ". _DB_PREFIX_."stock_available sa on pa.id_product=sa.id_product AND sa.id_product_attribute=pa.id_product_attribute AND sa.id_shop_group = '".$id_shop_group."'";
	
    $aquery .= " LEFT JOIN (SELECT pac.id_product_attribute, GROUP_CONCAT(LPAD(at.position,4,'0')) AS positions";
	$aquery .= ", GROUP_CONCAT(CONCAT('x',at.id_attribute,'-')) AS attributes FROM ". _DB_PREFIX_."product_attribute_combination pac";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute at on pac.id_attribute=at.id_attribute";
    $aquery .= " GROUP BY pac.id_product_attribute";
	$aquery .= ") px ON px.id_product_attribute=pas.id_product_attribute";

if(in_array("revenue", $input["fields"]) OR in_array("salescnt", $input["fields"]) OR in_array("orders", $input["fields"]) OR in_array("buyers", $input["fields"])
	OR ($order=="revenue")OR ($order=="orders") OR ($order=="salescnt") OR ($order=="buyers"))
{ $aquery .= " LEFT JOIN ( SELECT product_id, product_attribute_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ";
  $aquery .= " ROUND(SUM(total_price_tax_incl),2) AS revenue, ";
  $aquery .= " COUNT(DISTINCT d.id_order) AS ordercount, ";
  $aquery .= " count(DISTINCT o.id_customer) AS buyercount FROM ". _DB_PREFIX_."order_detail d";
  $aquery .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order AND o.id_shop=d.id_shop";
  $aquery .= " WHERE d.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $aquery .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $aquery .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".$input['enddate']."')";
  $aquery .= " AND o.valid=1";
  $aquery .= " GROUP BY d.product_id,d.product_attribute_id ) r ON pa.id_product=r.product_id AND pa.id_product_attribute=r.product_attribute_id";
  $aqueryterms .= ", revenue, r.quantity AS salescount, ordercount, buyercount ";
}
    $aqueryfull = "SELECT SQL_CALC_FOUND_ROWS ".$aqueryterms.$aquery;
    $aqueryfull .= " WHERE pa.id_product='".$datarow['id_product']."'";
	for($y=1; $y <=2; $y++)
	{ if(isset($input['combisearch_txt'.$y]) && ($input['combisearch_txt'.$y] != "") && ($input['combisearch_fld'.$y] !="0"))
	  { if ($input['combisearch_fld'.$y] == "priceVAT")
	      $fld = "(ROUND(((".$datarow['rate']."/100)+1)*pas.price,2))";
	    else
          $fld = preg_replace('/\s/',"",$input['combisearch_fld'.$y]);
	    if ($input['combisearch_fld'.$y] == "reference")
		  $aqueryfull .= ' AND ('.$csnottext.'psu.product_supplier_reference'.$csinc[$y].
			' OR '.$csnottext.$fld.$csinc[$y].')';
	    else
          $aqueryfull .= ' AND '.$csnottext.$fld.$csinc[$y];
	  }
	}
  if(isset($input['konda_fld1']) && ($input["konda_fld1"] != "0"))
  { $konda_fld = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET["konda_fld1"]);
	$kondb_fld = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_GET["kondb_fld1"]);
	$kond_cmp = $_GET["kond_cmp1"];
	if($kond_cmp == "not_eq") $cmp = "!=";
	else if($kond_cmp == "eq") $cmp = "=";
    else if($kond_cmp == "gt") $cmp = ">";
    else if($kond_cmp == "gte") $cmp = ">=";
    else if($kond_cmp == "lt") $cmp = "<";
    else if($kond_cmp == "lte") $cmp = "<="; 
    else if($kond_cmp == "not_in") $cmp = "NOT IN"; 
	else $cmp = "IN"; 
    $aqueryfull .= " AND ".$konda_fld." ".$cmp." ".$kondb_fld;
  }
	
	if($attributea !="0")
	  $aqueryfull .= ' AND attributes LIKE "%x'.$attributea.'-%"';
    if($attributeb !="0")
	  $aqueryfull .= ' AND attributes LIKE "%x'.$attributeb.'-%"';
    if(isset($blockers) && (sizeof($blockers) > 0))
      $aqueryfull .= " AND NOT EXISTS (SELECT * FROM ". _DB_PREFIX_."product_attribute_combination pac2 WHERE pa.id_product_attribute=pac2.id_product_attribute AND pac2.id_attribute IN ('".implode("','", $blockers)."'))";
    $aqueryfull .= " GROUP BY pas.id_product_attribute";
    $aqueryfull .= " ORDER BY positions";
    $ares=dbquery($aqueryfull);
// echo $aqueryfull."-----<p>";
    
  $VAT_rate = $taxrates[$datarow['id_tax_rules_group']];
  $prodprice = $datarow["price"];
  if(mysqli_num_rows($ares) > 0)
    $prodcount++;

  /* 9. csv output */
  while (($row=mysqli_fetch_assoc($ares)) && ($rowcnt < $maxrows))
  { $csvline = array();
    $csvline[] = $datarow['id_product'];
	$csvline[] = $row['id_product_attribute'];
	$csvline = array_merge($csvline, $csvprodline);
	
	if($psfields) /* add fields according to Prestashop specification (csv import sample) */
	{ $paquery = "SELECT gl.name AS gname, al.name AS aname, group_type from ". _DB_PREFIX_."product_attribute pa";
	  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
	  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
	  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang al on al.id_attribute=c.id_attribute AND al.id_lang='".$id_lang."'";
	  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group g on a.id_attribute_group=g.id_attribute_group";
	  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on  gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
	  $paquery .= " WHERE pa.id_product_attribute='".$row['id_product_attribute']."'";
	  $pares=dbquery($paquery);
	  $psattribute = $psvalue = [];
	  $pos = 0;
	  while($parow = mysqli_fetch_assoc($pares))
	  { $psattribute[] = $parow["gname"].":".$parow["group_type"].":".$pos; 
		$psvalue[] = $parow["aname"].":".$pos; 
		$pos++;
	  }
	  $csvline[] = implode($subseparator,$psattribute);
	  $csvline[] = implode($subseparator,$psvalue);
	}
	
    for($j=0; $j< sizeof($combifields); $j++)
	{   if($combifields[$j][0] == "combination")
		{ $paquery = "SELECT GROUP_CONCAT(CONCAT(gl.name,':',l.name) SEPARATOR '=') AS nameblock, GROUP_CONCAT(CONCAT(gl.id_attribute_group,':',a.id_attribute) SEPARATOR '=') AS idblock from ". _DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product_attribute='".$row['id_product_attribute']."' GROUP BY pa.id_product_attribute";
		  $pares=dbquery($paquery);
		  $parow = mysqli_fetch_assoc($pares);
		  if(is_null($parow['nameblock']))
		  { $csvline[] = '';
			$csvline[] = '';
		  }
		  else
		  { $labels = explode("=", $parow['nameblock']);
		    sort($labels);
		    $csvline[] = implode($subseparator, $labels);
		    $ids = explode("=", $parow['idblock']);
		    $csvline[] = " ".implode($subseparator, $ids); /* the space in front prevents Excel from doing strange things like converting to floats */
		  }
		  
/* // Alternative code if you want the attribute groups sorted by name
		  { $labels = explode("=", $parow['nameblock']);
		    asort($labels); // asort retains keys 
		    $csvline[] = implode($subseparator, $labels);
		    $ids = explode("=", $parow['idblock']);
			$idsorted = [];
			foreach($labels AS $key => $value) // put ids in same order as labels 
			  $idsorted[] = $ids[$key];
		    $csvline[] = " ".implode($subseparator, $idsorted); // the space in front prevents Excel from doing strange things like converting to floats 
		  }
*/
		}
		else if($combifields[$j][0] == "ids")
		{/* handled under combination */}
		else if($combifields[$j][0] == "price")
		  $csvline[] = $row['price'];
		else if($combifields[$j][0] == "priceVAT")
		  $csvline[] = number_format(($row['price']*(100+$VAT_rate)/100),2);
		else if($combifields[$j][0] == "weight")
		  $csvline[] = $row['weight'];
		else if($combifields[$j][0] == "wholesale_price")
		  $csvline[] = $row['wholesale_price'];
		else if($combifields[$j][0] == "ecotax")
		  $csvline[] = $row['ecotax'];
		else if($combifields[$j][0] == "isbn")
		  $csvline[] = $row['isbn'];
		else if($combifields[$j][0] == "unit_price_impact")
		  $csvline[] = $row['unit_price_impact'];
		else if($combifields[$j][0] == "default_on")
		  $csvline[] = $row['default_on'];
		else if($combifields[$j][0] == "minimal_quantity")
		  $csvline[] = $row['minimal_quantity'];
		else if($combifields[$j][0] == "available_date")
		  $csvline[] = $row['available_date'];
		else if($combifields[$j][0] == "quantity")
		{ if($datarow["depends_on_stock"] == "1")
            $csvline[] = $row['quantity'];	
		  else
		    $csvline[] = $row['quantity'];
		}
		/* below the ps_product_attribute fields */
		else if($combifields[$j][0] == "reference")
		  $csvline[] = $row['reference'];
		else if($combifields[$j][0] == "supplRef")
		  $csvline[] = $row['supplier_reference'];
		else if($combifields[$j][0] == "location")
		  $csvline[] = $row['location'];
		else if($combifields[$j][0] == "ean")
		  $csvline[] = $row['ean13'];
		else if($combifields[$j][0] == "upc")
		  $csvline[] = $row['upc'];
		else if($combifields[$j][0] == "quantity")
		  $csvline[] = $row['quantity'];
		else if($combifields[$j][0] == "ls_alert")
		  $csvline[] = $row['low_stock_alert'];	  
		else if($combifields[$j][0] == "ls_threshold")
		  $csvline[] = $row['low_stock_threshold'];	
	    else if($combifields[$j][0] == "discount")
        { $dquery = "SELECT sp.reduction,sp.reduction_type,sp.from,sp.to";
		  $dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
	      $dquery .= " WHERE sp.id_product='".$datarow['id_product']."' AND (sp.id_shop='".$id_shop."' OR sp.id_shop='0') AND (sp.to >= NOW() OR sp.to = '0000-00-00 00:00:00' ) AND sp.id_product_attribute='".$row['id_product_attribute']."'";
		  $dquery .= " ORDER BY sp.id_country, sp.id_group, sp.id_customer,sp.id_currency LIMIT 1"; /* order by should put zero's (=all) first */
		  $dres=dbquery($dquery);
		  if(mysqli_num_rows($dres) > 0)
		  { $drow=mysqli_fetch_array($dres);
		    if($drow["reduction_type"] == "pct")
		    { $csvline[] = "0";
		      $csvline[] = $drow['reduction'];
		    }
		    else
		    { $csvline[] = $drow['reduction'];
			  $csvline[] = "0";
		    }
		    $csvline[] = $drow['from'];
		    $csvline[] = $drow['to'];
		  }
		  else
		  { $csvline[] = "";
		    $csvline[] = "";
		    $csvline[] = "";
		    $csvline[] = "";
		  }
		  mysqli_free_result($dres);
        }  /* end of discount */
		else if($combifields[$j][0] == "suppliers")
        { $squery = "SELECT p.id_supplier,product_supplier_reference AS reference, 	product_supplier_price_te AS price FROM "._DB_PREFIX_."product_supplier ps";
		$squery .= " LEFT JOIN "._DB_PREFIX_."product p on p.id_supplier=ps.id_supplier";
		$squery .= " WHERE ps.id_product=".$datarow['id_product']." AND ps.id_product_attribute='".$row['id_product_attribute']."'";
		  $sres=dbquery($squery);
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
        }
		/* image */
		else if($combifields[$j][0] == "image")
		{ if(!$row["id_image"]) $row["id_image"]="";
	      if($separator == ",")
			$row["id_image"] = str_replace(',',';',$row["id_image"]);
		  $csvline[] = $row["id_image"];
		}
		else if($combifields[$j][0] == "buyers")
	    { $csvline[] = $row['buyercount'];
	    }
	    else if($combifields[$j][0] == "orders")
	    { $csvline[] = $row['ordercount'];
	    }
	    else if($combifields[$j][0] == "revenue")		  
	    { $csvline[] = $row['revenue'];
	    }
	    else if($combifields[$j][0] == "salescnt")	  
	    { $csvline[] = $row['salescount'];
	    }
		else 
		{ $csvline[] = $row[$combifields[$j][0]];
		}
	}

    $x++;
	$rowcnt++;
    publish_csv_line($out, $csvline, $separator);
  }
  }
  fclose($out);

function publish_csv_line($out, $csvline, $separator)
{ fputcsv3($out, $csvline, $separator);
}
  
/* if fputcsv doesn't work this can be used as alternative. It is one of the options mentioned in the comment section of php.net for fputcsv() */
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

