<?php 
/* For better understanding this file is divided in sections.
 * 1. include files 
 * 2. handling input variables
 * 3. make field declarations and build infofields array of displayed fields
 * 4. retrieve system settings and build php blocks for select fields 
 * 5. publish header block of html page, including javascript version of select blocks
 * 6. publish page header: menu and diagnostic information
 * 7. publish search block
 * 8. Publish the block where you can select your product fields
 * 9. Publish the block where you can select your combination fields
 * 10. Mass edit and CSV export
 * 13. switchform: hide/show/edit fields 
 * 16. build and execute the product query 
 * 17. Next and Prev
 * 18. warnings and explanations
 * 19. The main form: the multishop option
 * 20. The main table: the column headers 
 * 21. the main table: the products
 * 22. the main table: the combination data
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
   "unitPRatio" => array("unitPRatio",null, "unit_price_ratio", DISPLAY, 0, LEFT, null, INPUT, "ps.unit_price_ratio"),  
   "upc" => array("upc",null, "upc", DISPLAY, 200, LEFT, null, INPUT, "p.upc"),   
   "VAT" => array("VAT",null, "id_tax_rules_group", DISPLAY, 0, LEFT, null, DROPDOWN, "t.rate"),
   "virtualp" => array("virtualp",null, "virtualp", DISPLAY, 0, LEFT, null, INPUT, "virtualp"), /* virtual product */
   "visibility" => array("visibility",null, "visibility", DISPLAY, 0, LEFT, null, INPUT, "ps.visibility"), 
   "warehousing" => array("warehousing",null, "quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "w.id_warehouse"),
   "wholesaleprice" => array("wholesaleprice",null, "wholesale_price", DISPLAY, 0, LEFT, null, INPUT, "ps.wholesale_price")
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
	array("ean",RIGHT,HIDE,"",1,"pa.ean13"),
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
  
/* Make the discount blocks */
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
  if($discount_included)
  { $countryblock = "";
	$query=" select id_country,name from ". _DB_PREFIX_."country_lang WHERE id_lang='".$id_lang."' ORDER BY name";
	$res=dbquery($query);
	while ($country=mysqli_fetch_array($res)) {
		$countryblock .= '<option value="'.$country['id_country'].'" >'.$country['id_country']."-".$country['name'].'</option>';
	}

	$groupblock = "";
	$query=" select id_group,name from ". _DB_PREFIX_."group_lang WHERE id_lang='".$id_lang."' ORDER BY id_group";
	$res=dbquery($query);
	while ($group=mysqli_fetch_array($res)) {
		$groupblock .= '<option value="'.$group['id_group'].'" >'.$group['id_group']."-".$group['name'].'</option>';
	}
  }
  
  /* make supplier names list */
if(in_array('supplier', $input["fields"]))
{ $query = "select id_supplier,name from ". _DB_PREFIX_."supplier ORDER BY name";  $res=dbquery($query);
  $supplier_names = array();
  $supplierblock0 = '<input type=hidden name="supplier_defaultCQX"><input type=hidden name="mysupsCQX">';
  $supplierblock0 .= '<table><tr><td><select id="supplierlistCQX" size=3>';
  $supplierblock1 = "";
  while ($row=mysqli_fetch_array($res)) 
  { $supplier_names[$row['id_supplier']] = $row['name'];
    $supplierblock1 .= '<option value="'.$row['id_supplier'].'">'.str_replace("'","\'",$row['name']).'</option>';
  }
  $supplierblock1 .= '</select>';
  $supplierblock2 = '</td><td><nobr><a href=# onClick=" Addsupplier(\\\'CQX\\\',1); reg_change(this); return false;"><img src=add.gif border=0></a> &nbsp; &nbsp; ';
  $supplierblock2 .= '<a href=# onClick="Removesupplier(\\\'CQX\\\'); reg_change(this); return false;"><img src=remove.gif border=0></a></nobr></td><td><select size=3 id="supplierselCQX"></select>';
  $supplierblock2 .= '</td><td style="vertical-align:middle"><a href="#" onclick="MakeSupplierDefault(\\\'CQX\\\'); reg_change(this); return false;"><img src="starr.jpg" border="0"></a></td></tr></table>';
}
else 
  $supplierblock0 = $supplierblock1 = $supplierblock2 = "";
 

  /* currency block: for discount and suppliers */
    $currencyblock = "";
	$def_currency = "0";
    $currencies = array();
	$query="SELECT c.id_currency,c.iso_code,";
    if(version_compare(_PS_VERSION_ , "1.5.3", "<"))
      $query .= "c.conversion_rate";
    else
      $query .= "cs.conversion_rate";
	$query .= " FROM ". _DB_PREFIX_."currency c";
	$query .= " LEFT JOIN ". _DB_PREFIX_."currency_shop cs ON c.id_currency=cs.id_currency AND cs.id_shop='".$id_shop."'";	
	$query .= " WHERE deleted='0' AND active='1' ORDER BY name";
	$res=dbquery($query);
	while ($currency=mysqli_fetch_array($res)) {
		$currencyblock .= '<option value="'.$currency['id_currency'].'" >'.$currency['iso_code'].'</option>';
		$currencies[] = $currency['iso_code'];
		if($currency['conversion_rate'] == 1) 
			$def_currency = $currency['iso_code'];
	}
	if($def_currency == "0")
		$def_currency = $currencies[0];
    
/* Make image format block */
  $query = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1 ORDER BY width";
  $res=dbquery($query);
  $imgformatblock = '<select name="imgformat" style="width:100px">';
  $row = mysqli_fetch_array($res); /* take small as the default */
  $selected_img_extension = "-".$row["name"].".jpg";
  $prod_imgwidth = $row["width"];
  $prod_imgheight = $row["height"];
  mysqli_data_seek($res,0);
  while($row = mysqli_fetch_array($res))
  { if($row["name"] == $input["imgformat"]) 
    { $selected = "selected"; 
	  $selected_img_extension = "-".$row["name"].".jpg";
	  $prod_imgwidth = $row["width"];
	  $prod_imgheight = $row["height"];
	}
    else $selected = "";
    $imgformatblock .= '<option value="'.$row['name'].'" '.$selected.'>'.$row['name'].' ('.$row['width'].'x'.$row['height'].')</option>';
  }
  $imgformatblock .= '</select>';
  
  $prodfields = array_diff(array_keys($infofields), array("id_product","salescnt", "revenue","orders","buyers"));
  
/* section 5: publish header block of html page, including javascript version of select blocks */
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false))
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<title>Prestashop ProdCombi Multiedit</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
<?php for($i=0; $i<sizeof($prodfields); $i++)
	    echo "table#Maintable td:nth-child(".($i+1).") { background-color: #EEEEEE; }
";
	    
?>
}

</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var prestashop_version = '<?php echo _PS_VERSION_ ?>';
var product_fields = new Array();
var max_input_vars = '<?php echo ini_get('max_input_vars'); ?>';
var imgwidth = '<?php echo $prod_imgwidth ?>';
var imgheight = '<?php echo $prod_imgheight ?>';
var allow_accented_chars = '<?php echo $allow_accented_chars ?>';
var supplierblock0 = '<?php echo $supplierblock0 ?>';
var supplierblock1 = '<?php echo $supplierblock1 ?>';
var supplierblock2 = '<?php echo $supplierblock2 ?>';
var currencyblock = '<?php echo $currencyblock ?>';
var langcopyselblock = '<?php echo $langcopyselblock ?>';
var catselectortype = '<?php echo $prestools_settings["catselectortype"] ?>';
var deliverytimesblock = '<select name="aDeliveryTCQX"  onchange="reg_change(this);"><option value="0">none</option><option value="1">default info</option><option value="2">product info</option></select>';
var shop_ids = '<?php echo implode(",",$shop_ids); ?>';
var featurelist = [<?php 
$tmp = 0;
foreach($featurelist AS $featureitem)
{ if($tmp++ != 0) echo ",";
  echo "'".str_replace("'","\'",$featureitem)."'";
}
?>];
<?php
  if((in_array("discount", $input["fields"])) || (in_array("supplier", $input["fields"])))
  { echo "currencyblock='".$currencyblock."';
";	  
  }  
  if($discount_included)
  { echo "countryblock='".str_replace("'","\'",$countryblock)."';
	groupblock='".str_replace("'","\'",$groupblock)."';
	shopblock='".str_replace("'","\'",$shopblock)."';
";
    echo 'currencies=["'.implode('","', $currencies).'"]; 
'; 
  }
  
  echo 'numrecs='.$input["numrecs"].';
  startdate="'.$input["startdate"].'";
  enddate="'.$input["enddate"].'";
  id_shop='.$id_shop.';
  triplepath="'.$triplepath.'";
  fields = ["'.implode('","', $input["fields"]).'"];
';
?>

var showfieldflag = 0;
function ShowFields()
{ var tab = document.getElementById("fieldstable"); 
  if(showfieldflag)
    tab.style.display = "none";
  else
    tab.style.display = "table";
  showfieldflag = 1-showfieldflag;
}

function get_product_id(row)
{ var prod_base = eval("document.Mainform.id_product"+row);
  if(!prod_base) return 0;
  return prod_base.value;
}

function get_product_attribute_id(row)
{ var prat_base = eval("document.Mainform.id_product_attribute"+row);
  if(!prat_base) return 0;
  return prat_base.value;
}

parts_stat = 0;
desc_stat = 0;
var trioflag = false;
function switchDisplay(id, elt, fieldno, val)  // collapse(field)
{ var tmp, tmp2, val, checked;
  /* we have mixed up the fieldno's compared to other pages with switchDisplay. So we need to get the correct column/fieldno) */
  var row = document.getElementById('Maintable').tHead.rows[0];
  var len = row.cells.length;
  for(var i=0; i<len; i++)
  { var myid = "hdr"+fieldno;
	if(row.cells[i].id==myid) { fieldno=i; break; }
  }
  var advanced_stock = false;
  if(val == '0') /* hide */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++)
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='none';
  }
  if((val == '1') || (val=='2')) /* 1 = show */
  { var tbl= document.getElementById(id).parentNode;
    for (var i = 0; i < tbl.rows.length; i++) 
	  if(tbl.rows[i].cells[fieldno])
	    tbl.rows[i].cells[fieldno].style.display='table-cell';
  }
  if(val=='2') /* 2 = edit */
  { tab = document.getElementById('Maintable');
    var tblEl = document.getElementById(id);
    field = tab.tHead.rows[0].cells[fieldno].children[0].innerHTML;
    if((trioflag == true) && ((field == "price") || (field == "priceVAT")))
    { alert("You may edit only one of the two fields at a time: price and priceVAT");
      return;
    }
    if((field == "price") || (field == "priceVAT"))
      trioflag = true;
	if(field == "default_on")
	{ var fieldnr = tbl.rows[1].cells.length - 1;
	  for (var i = 0; i < tbl.rows.length; i++)
		if(tbl.rows[i].cells[fieldnr])
			tbl.rows[i].cells[fieldnr].style.display='none';
	  alert("Please use the Submit All button to submit changes to the default field!");
	}
	var colipa = getColumn('combination');
    for(var i=0; i<tblEl.rows.length; i++)
    { if(!tblEl.rows[i].cells[fieldno]) continue; 
	  tmp = tblEl.rows[i].cells[fieldno].innerHTML;
      tmp2 = tmp.replace("'","\'");
      row = tblEl.rows[i].cells[0].childNodes[1].name.substring(5); /* Note that for sorted tables row != i; 5=length of "price" */
	  if(field=="priceVAT") 
      { tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" onchange="priceVAT_change(this)" />';
		priceVAT_editable = true;
	  }
      else if(field=="price") 
      { tblEl.rows[i].cells[fieldno].innerHTML = '<input name="showprice'+row+'" value="'+tmp2+'" onchange="price_change(this)" />';
		price_editable = true;
	  }
	  else if(field=="discount")
      { /* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
	    /* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
		var tab = document.getElementById('discount'+row); /* this is the table */
	    if(tab)
		{ blob = "";
	      var z = 0;
		  for(var y=0; y<tab.rows.length; y++)
		  { if(tab.rows[y].getAttribute("rule")== 0)
			{ blob += "<div>";
			  var newprices = tab.rows[y].cells[13].innerHTML.split('/ ');
		      blob += fill_discount(row,z,tab.rows[y].getAttribute("specid"),"update",tab.rows[y].cells[0].innerHTML,tab.rows[y].cells[1].innerHTML,tab.rows[y].cells[2].innerHTML,tab.rows[y].cells[3].innerHTML,tab.rows[y].cells[4].innerHTML,tab.rows[y].cells[5].innerHTML,tab.rows[y].cells[6].innerHTML,tab.rows[y].cells[7].innerHTML,tab.rows[y].cells[8].innerHTML,tab.rows[y].cells[9].innerHTML,tab.rows[y].cells[10].innerHTML,tab.rows[y].cells[11].innerHTML,tab.rows[y].cells[12].innerHTML,newprices[0],newprices[1]);
		      blob += "</div>";
			  tab.rows[y].innerHTML = "";
			  z++;
			}
			else
				has_catalogue_rules = true;
		  }
		  var blob = '<input type=hidden name="discount_count'+row+'" value="'+z+'">' + blob;
		  blob += '<a href="#" onclick="return add_discount('+row+');" class="TinyLine" id="discount_adder'+row+'">Add discount rule</a>';
		  tblEl.rows[i].cells[fieldno].innerHTML += blob;
		}
	  }	  
      else if(field=="suppliers") 	  
	  { var trow = document.getElementById("trid"+row).parentNode;
  	    var sups = trow.cells[fieldno].getAttribute("sups");
		var attribute = eval('Mainform.id_product_attribute'+row+'.value');
	  
		  var tab = document.getElementById("suppliers"+row);
		  blob = '<input type=hidden name="suppliers'+row+'" value='+sups+'>'; 
		  blob += '<table id="suppliertable'+row+'" class="suppliertable" title="'+tab.title+'">';
		  if(tab)
		  { var first = 0;
	        for(var y=0; y<tab.rows.length; y++)
		    { blob += '<tr><td class="'+tab.rows[y].cells[0].className+'">'+tab.rows[y].cells[0].innerHTML+'</td>';
			  blob += '<td><input name="supplier_reference'+tab.rows[y].title+'s'+row+'" value="'+tab.rows[y].cells[1].innerHTML.replace(/"/g, '&quot;')+'" onchange="reg_change(this);"></td>';
			  blob += '<td><input name="supplier_price'+tab.rows[y].title+'s'+row+'" value="'+tab.rows[y].cells[2].innerHTML.replace(/"/g, '&quot;')+'" onchange="reg_change(this);"></td>';
			  blob += '<td><select name="supplier_currency'+tab.rows[y].title+'s'+row+'" onchange="reg_change(this);">'+currencyblock.replace(">"+tab.rows[y].cells[3].innerHTML+"<"," selected>"+tab.rows[y].cells[3].innerHTML+"<")+'</select></td>';
			  blob += '</tr>';
			}
		  blob += '</table>';
		}
		trow.cells[fieldno].innerHTML = blob;
	  }
      else if(field=="default_on") 	  
	  { if(tmp==1) checked="checked"; else checked="";
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input type=hidden name="'+field+row+'" id="'+field+row+'" value="0" /><input type=checkbox name="'+field+row+'" id="'+field+row+'" onchange="default_change(this);" value="1" '+checked+' />';
	  }	  
      else if(field=="ls_alert") 	  
	  { if(tmp==1) checked="checked"; else checked="";
	    tblEl.rows[i].cells[fieldno].innerHTML = '<input type=hidden name="'+field+row+'" id="'+field+row+'" value="0" /><input type=checkbox name="'+field+row+'" id="'+field+row+'" onchange="reg_change(this);" value="1" '+checked+' />';
	  }
	  else if((field=="quantity") && (tblEl.rows[i].cells[fieldno].style.backgroundColor == "yellow"))
	  { advanced_stock = true;
		continue;
	  }
      else
	  { tblEl.rows[i].cells[fieldno].innerHTML = '<input name="'+field+row+'" value="'+tmp2+'" onchange="reg_change(this);" />';
	  }
	}
    tmp = elt.parentElement.innerHTML;
    tmp = tmp.replace(/<br.*$/,'');
    elt.parentElement.innerHTML = tmp+"<br><br>Edit";
  }
  var warning = "";
  if(advanced_stock)
    warning += "Quantity fields of combinations with warehousing - marked in yellow - cannot be changed.";
  var tmp = document.getElementById("warning");
  tmp.innerHTML = warning;
  return;
}

var price_editable = false;
var priceVAT_editable = false;
function price_change(elt)
{ var tblEl = document.getElementById("offTblBdy");
  var price = elt.value;
  var thisrow = elt.name.substring(9);
  var VAT = elt.parentNode.parentNode.cells[0].dataset.vatrate;
  var pvcol = getColumn("priceVAT");
  var newprice = price * (1 + (VAT / 100));
  newprice = newprice.toFixed(2); /* round to 2 decimals */
  elt.parentNode.parentNode.cells[pvcol].innerHTML = newprice;
  if(document.search_form.base_included.checked)
  { base_price = parseFloat(document.Mainform.base_price.value);
    price = price - base_price;
  }
  var pricefield = eval("document.Mainform.price"+thisrow);
  pricefield.value = price;
  reg_change(elt);
}

function priceVAT_change(elt)
{ var tblEl = document.getElementById("offTblBdy");
  var priceVAT = elt.value;
  var VAT = elt.parentNode.parentNode.cells[0].dataset.vatrate;
  var thisrow = elt.name.substring(8);
  var pcol = getColumn("price");
  var newprice = priceVAT / (1 + (VAT / 100));
  elt.parentNode.parentNode.cells[pcol].innerHTML = newprice.toFixed(6);
  if(document.search_form.base_included.checked)
  { base_price = parseFloat(elt.parentNode.parentNode.cells[0].dataset.prodprice);
    newprice = newprice - base_price;
  }
  newprice = newprice.toFixed(6); /* round to 6 decimals */
  var pricefield = eval("document.Mainform.price"+thisrow);
  pricefield.value = newprice;
  reg_change(elt);
}

/* switch between showing prices with and without the main price of the product included */
function switch_pricebase(elt)
{ var VAT;
  var tbl = document.getElementById("Maintable");
  var len = tbl.tBodies[0].rows.length;
  var pvcol = getColumn("priceVAT");
  var pcol = getColumn("price");
  var tbl = document.getElementById("Maintable"); 

  for(var i=0;i<len; i++)
  { if(tbl.tBodies[0].rows[i].innerHTML == "<td></td>") continue;
	VAT = tbl.tBodies[0].rows[i].cells[0].dataset.vatrate;
    if(elt.checked == false) 
		base_price = 0;
	else
	{ var base_price = parseFloat(tbl.tBodies[0].rows[i].cells[0].dataset.prodprice);
	}
	var netprice = base_price + parseFloat(tbl.tBodies[0].rows[i].cells[0].childNodes[1].value);
	netprice = netprice.toFixed(6); 
	if(price_editable)
	   tbl.tBodies[0].rows[i].cells[pcol].childNodes[0].value = netprice;
	else
		tbl.tBodies[0].rows[i].cells[pcol].innerHTML = netprice;
	var VATprice = (netprice * (1 + VAT/100)).toFixed(2);
	if(priceVAT_editable)
	   tbl.tBodies[0].rows[i].cells[pvcol].childNodes[0].value = VATprice;
	else
		tbl.tBodies[0].rows[i].cells[pvcol].innerHTML = VATprice;
  } 
}


function add_discount(row)
{ var count_root = eval('Mainform.discount_count'+row);
  var dcount = parseInt(count_root.value);
  var attribute = eval('Mainform.id_product_attribute'+row+'.value');
/* function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,to,newpricex,newpricei)             */
  var blob = fill_discount(row,dcount,"","new","",	attribute,	"",		"0",	"0",	"0",	"",		"1",	"",			"1",		"",			"","",   0,         0);
  var new_div = document.createElement('div');
  new_div.innerHTML = blob;
  var adder = document.getElementById("discount_adder"+row);
  adder.parentNode.insertBefore(new_div,adder);
  count_root.value = dcount+1;
  return false;
}

/* clicking on the pencil calls this function to create the dhtml window: copy all the fields from the main window */
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
	}
	else /* insert */
	{	blob += '<td><select name="discount_shop" onchange="changed = 1;">';
		blob += '<option value="0">All</option>'+(((shop == "") || (shop == 0))? shopblock : shopblock.replace(">"+shop+"-", " selected>"+shop+"-"))+'</select></td></tr>';
	}
	
	blob += '<tr><td><input type=hidden name="discount_attribute" value="'+eval('Mainform.discount_attribute'+entry+'s'+row+'.value')+'">';
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
	
	blob += '<tr><td><b>Price</b></td><td><input name="discount_price" value="'+eval('Mainform.discount_price'+entry+'s'+row+'.value')+'" onchange="changed = 1; discount_change(this,0,0);" style="width:70px"> &nbsp; From price ex Vat. Leave empty when equal to normal price.</td></tr>';
	blob += '<tr><td><b>Quantity</b></td><td><input name="discount_quantity" value="'+eval('Mainform.discount_quantity'+entry+'s'+row+'.value')+'" onchange="changed = 1;"> &nbsp; Threshold for reduction.</td></tr>';
	blob += '<tr><td><b>Reduction</b></td><td><input name="discount_reduction" value="'+eval('Mainform.discount_reduction'+entry+'s'+row+'.value')+'" onchange="changed = 1; discount_change(this,0,0);"></td></tr>';
	var reductiontax = eval('Mainform.discount_reductiontax'+entry+'s'+row);
	blob += '<tr><td><b>Red. tax</b></td><td><select name="discount_reductiontax" onchange="changed = 1; discount_change(this,0,0);">';
	if(prestashop_version >= "1.6.0.11")	/* for PS >= 1.6.0.11 */
	{ if(reductiontax.value == 1)
	     blob += '<option value=0>excl tax</option><option value=1 selected>incl tax</option>';
	  else
	     blob += '<option value=0 selected>excl tax</option><option value=1>incl tax</option>';
	}
	else
	   blob += '<option value=1>incl tax</option>';		
	blob += '</select> &nbsp; only relevant with amounts and PS > 1.6.0.11</td></tr>';	
	blob += '<td><b>Red. type</b></td><td><select name="discount_reductiontype" onchange="changed = 1; discount_change(this,0,0);">';
    if(eval('Mainform.discount_reductiontype'+entry+'s'+row+'.selectedIndex') == 1)
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select></td></tr>';
	blob += '<tr><td><nobr><b>From date</b></nobr></td><td><input name="discount_from" value="'+eval('Mainform.discount_from'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"> &nbsp; format: yyyy-mm-dd</td></tr>';
	blob += '<tr><td><b>To date</b></td><td><input name="discount_to" value="'+eval('Mainform.discount_to'+entry+'s'+row+'.value')+'" class="datum" onchange="changed = 1;"> &nbsp; format: yyyy-mm-dd</td></tr>';
    var newpricex_fld = document.getElementById("discount_newprice_excl"+entry+"s"+row);
    var newpricei_fld = document.getElementById("discount_newprice_incl"+entry+"s"+row);
	blob += '<tr><td><b>New Price</b></td><td><input id="discount_newprice_excl" value="'+newpricex_fld.value+'" onchange="discount_change(this,0,0)" style="width:60px;" class="calculated"> Excl';
	blob += ' &nbsp; <input id="discount_newprice_incl" value="'+newpricei_fld.value+'" onchange="discount_change(this,0,0)" style="width:60px;" class="calculated"> Incl VAT - (calculated values)</td></tr>';
	blob += '<tr><td></td><td align="right"><input type=button value="submit" onclick="submit_dh_discount()"></td></tr></table></form>'; 
    googlewin=dhtmlwindow.open("Edit_discount", "inline", blob, "Edit discount for product "+get_product_id(row)+" - product attribute "+get_product_attribute_id(row), "width=580px,height=425px,resize=1,scrolling=1,center=1", "recal");
  return false;
}

function submit_dh_discount()	/* submit dhtml window and enter data in main page */
{ /*					row				entry				id					status					shop			attribute			*/
  var currency = dhform.discount_currency.options[dhform.discount_currency.selectedIndex].text;
  var country = dhform.discount_country.options[dhform.discount_country.selectedIndex].text;
  country = country.substring(0,country.indexOf('-'));
  var group = dhform.discount_group.options[dhform.discount_group.selectedIndex].text;
  group = group.substring(0,group.indexOf('-'));
  var reductiontype = dhform.discount_reductiontype.options[dhform.discount_reductiontype.selectedIndex].text;
  var reductiontax = dhform.discount_reductiontax.options[dhform.discount_reductiontax.selectedIndex].value;
  var newpricex_fld = document.getElementById("discount_newprice_excl");
  var newpricei_fld = document.getElementById("discount_newprice_incl");
  var blob = fill_discount(dhform.row.value,dhform.entry.value,dhform.discount_id.value,dhform.discount_status.value,dhform.discount_shop.value,dhform.discount_attribute.value,currency,country,group,dhform.discount_customer.value,dhform.discount_price.value,dhform.discount_quantity.value,dhform.discount_reduction.value,reductiontax,reductiontype,dhform.discount_from.value,dhform.discount_to.value,newpricex_fld.value,newpricei_fld.value);
  var eltname = 'discount_table'+dhform.entry.value+'s'+dhform.row.value;
  var target = document.getElementById(eltname);
  target = target.parentNode;
  target.innerHTML = blob;
  reg_change(target);
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

/* the ps_specific_prices table has two unique keys that forbid that two too similar reductions are inserted.
 * This function - called before submit - checks for them. 
 * Without this check you get errors like: 
 *   Duplicate entry '113-0-0-0-0-0-0-0-15-0000-00-00 00:00:00-0000-00-00 00:00:00' for key 'id_product_2'
 * This key contains the following fields: id_product, id_shop,id_shop_group,id_currency,id_country,id_group,id_customer,id_product_attribute,from_quantity,from,to 
 * Note that this key has changed over different PS versions. So the check here may be too strong for some versions and too weak for others. */
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

/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
function fill_discount(row,entry,id,status, shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,to,newpricex,newpricei)
{ 	var blob = '<input type=hidden name="discount_id'+entry+'s'+row+'" value="'+id+'">';
	blob += '<input type=hidden name="discount_status'+entry+'s'+row+'" value="'+status+'">';		
	blob += '<table id="discount_table'+entry+'s'+row+'" class="discount_table"><tr><td rowspan=3><a href="#" onclick="return edit_discount('+row+','+entry+')"><img src="pen.png"></a></td>';
	
	if(customer == "") customer = 0;
	if(country == "") country = 0;
	if(group == "") group = 0;
	if(attribute == "") attribute = 0;
	if(quantity == "") quantity = 1;
	if(shop == "") shop = 0;
	
	if(status == "update")
	{	blob += '<td class="nobr"><input type=hidden name="discount_shop'+entry+'s'+row+'" value="'+shop+'">';
		if(shop == "0") blob += "all";
		else blob+=shop;
	}
	else /* insert */
	{	blob += '<td class="nobr"><input name="discount_shop'+entry+'s'+row+'" style="width:20px" value="'+shop+'" title="shop id" onchange="reg_change(this);"> &nbsp;';
	}
	
	blob += '<input type=hidden name="discount_attribute'+entry+'s'+row+'" value="'+attribute+'">';
	blob += '<select name="discount_currency'+entry+'s'+row+'" value="'+currency+'" title="currency" onchange="reg_change(this);">';
	blob += '<option value="0">All</option>'+((currency == "")? currencyblock : currencyblock.replace(">"+currency+"<", " selected>"+currency+"<"))+'</select> &nbsp;';

	blob += '<input name="discount_country'+entry+'s'+row+'" style="width:20px" value="'+country+'" title="country id" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_group'+entry+'s'+row+'" style="width:20px" value="'+group+'" title="group id" onchange="reg_change(this);"></td>';
	
	blob += '<td rowspan=3><a href="#" onclick="return del_discount('+row+','+entry+')"><img src="del.png"></a></td></tr><tr>';
	blob += '<td class="nobr"><input style="width:15px" name="discount_customer'+entry+'s'+row+'" value="'+customer+'" title="customer id" onchange="reg_change(this);"> &nbsp; ';

	blob += '<input name="discount_price'+entry+'s'+row+'" style="width:40px" value="'+price+'" title="From Price Excl" onchange="reg_change(this); discount_change(this,'+row+','+entry+')"> &nbsp; ';
	blob += '<input name="discount_quantity'+entry+'s'+row+'" style="width:30px" value="'+quantity+'" title="From Quantity" onchange="reg_change(this);"> &nbsp;';
	blob += '<input name="discount_reduction'+entry+'s'+row+'" style="width:40px" value="'+reduction+'" title="Reduction" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	blob += '</tr><tr><td>';
	
	blob += '<select name="discount_reductiontax'+entry+'s'+row+'" title="Reduction Tax status" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	if(prestashop_version >= "1.6.0.11")	/* for PS >= 1.6.0.11 */
	{ if(reductiontax == "1")
	    blob += '<option value=0>Excl</option><option value=1 selected>Incl</option>';
	  else
	    blob += '<option value=0 selected>Excl</option><option value=1>Incl</option>';
	}
	else
	    blob += '<option value=1>Incl</option>';	  
	blob += '</select> ';	
	
	blob += '<select name="discount_reductiontype'+entry+'s'+row+'" title="Reduction Type" onchange="reg_change(this); discount_change(this,'+row+','+entry+')">';
	if(reductiontype == "pct")
	   blob += '<option>amt</option><option selected>pct</option>';
	else
	   blob += '<option selected>amt</option><option>pct</option>';
	blob += '</select>';
	blob += ' <input name="discount_from'+entry+'s'+row+'" style="width:65px" value="'+from+'" title="From Date" class="datum" onchange="reg_change(this);">';
	blob += ' <input name="discount_to'+entry+'s'+row+'" style="width:65px" value="'+to+'" title="To Date" class="datum" onchange="reg_change(this);">';

	blob += ' <input id="discount_newprice_excl'+entry+'s'+row+'" style="width:40px" value="'+newpricex+'" onchange="discount_change(this,'+row+','+entry+')" title="calculated price excl VAT" class="calculated">';
    blob += ' <input id="discount_newprice_incl'+entry+'s'+row+'" style="width:40px" value="'+newpricei+'" onchange="discount_change(this,'+row+','+entry+')" title="calculated price incl VAT" class="calculated">';
	blob += "</td></tr></table><hr/>";
	return blob;
}

/* when you add a discount block you cannot immediately execute javascript on it. For that reason the discount_change
 * function that generates the calculated resulting prices for them is executed with a delay. 
 * All the discount blocks that need such an calculation are collected in an array (discount_delayed) that is then 
 * processed by this function. As discount_change() needs to know which field's change is guiding we provide a 
 * "target". For the "add" function this is always "reduction". For the "add fixed target discount" function 
 * it is either "newprice_excl" or "newprice_incl". */
function delayed_discount_change(target)
{ var len = discount_delayed.length;
  for(var i=0; i<len; i++)
  { var elta = eval("Mainform.discount_"+target+discount_delayed[i][1]+"s"+discount_delayed[i][0]);
	if(!elta) alert("Delayed discount target not found "+target);
    discount_change(elta, discount_delayed[i][0],discount_delayed[i][1]);
  }
}

/* discount_change is called when one of the fields is changed. It calculates the new discounted price */
function discount_change(elt,row,entry)
{ var name = elt.name;
  var myform = elt.form.name;
  var suffix = "";
  if(myform == "Mainform")
	  suffix = entry+"s"+row;
  var tblEl = document.getElementById("offTblBdy");
  var baseprice = eval(myform+".discount_price"+suffix+".value");
  if(!baseprice)
  { var prodprice = parseFloat(tblEl.rows[row].cells[0].dataset.prodprice); /* product price */
    var baseprice = prodprice + parseFloat(tblEl.rows[row].cells[0].childNodes[1].value); /* add combination price */
  }
  else
    baseprice = parseFloat(baseprice);
  var VAT = parseFloat(tblEl.rows[row].cells[0].dataset.vatrate);
  var reductionfield = eval(myform+".discount_reduction"+suffix);
  if(reductionfield.value=="") reductionfield.value="0";
  var reduction = parseFloat(reductionfield.value);
  var reductiontype = eval(myform+".discount_reductiontype"+suffix+".value");
  var reductiontax = parseFloat(eval(myform+".discount_reductiontax"+suffix+".value"));
  
  if(elt.id.substring(0,17) == "discount_newprice") /* if the newprice was changed: change the reduction */
  { if(elt.id.substring(0,22) == "discount_newprice_incl")
	{ var newpricei = parseFloat(eval(myform+".discount_newprice_incl"+suffix+".value"));
	  var newpricex = newpricei * (100/(VAT+100));
	}
	else
	{ var newpricex = parseFloat(eval(myform+".discount_newprice_excl"+suffix+".value"));
	}
	if(reductiontype == "pct")
	  var reduction = Math.round((baseprice - newpricex) / (baseprice * 100));
    else
    { var reduction = baseprice - newpricex;
      if(reductiontax)
		reduction = (reduction * ((100+VAT)/100)).toFixed(2);
    }
	reductionfield.value = reduction;
  }
  if(reductiontype == "pct")
	var newpricex = baseprice * (1 - (reduction/100));
  else
  { if(reductiontax)
		reduction = reduction *(100/(VAT+100));
    var newpricex = baseprice - reduction;
  }
  var newpricei = newpricex * (1 + VAT/100);
  var newpricex_fld = document.getElementById("discount_newprice_excl"+suffix);
  newpricex_fld.value = newpricex.toFixed(2);
  var newpricei_fld = document.getElementById("discount_newprice_incl"+suffix);
  newpricei_fld.value = newpricei.toFixed(2);
}

	/* for massedit discount remove: gives subfield options */
	function dc_field_optioner()
	{ var base = eval("document.massform.fieldname");
	  var fieldname = base.options[base.selectedIndex].text;
	  var tmp = "";
	  if (fieldname == "shop") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All shops').'</option>"+shopblock+"</select>";
	  else if (fieldname == "currency") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All currencies').'</option>"+currencyblock+"</select>";	
	  else if (fieldname == "country") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All countries').'</option>"+countryblock+"</select>";
	  else if (fieldname == "group") 
	    tmp = "<select name=subfield style=\"width:100px\"><option value=0>'.t('All groups').'</option>"+groupblock+"</select>";	
	  else if (fieldname == "reductiontype") 
	    tmp = "<select name=subfield style=\"width:100px\"><option>amt</option><option>pct</option></select>";		
	  else 
	    tmp = "<input name=subfield size=40>";
	  var fld = document.getElementById("dc_options");
	  fld.innerHTML = " = "+tmp;
	}
	
function fixed_target_discount_change(elt)
{ if(elt.name == "targetprice")
    massform.targetpriceVAT.value = "";
  else
    massform.targetprice.value = "";    	  
}

function fillImages(idx,tmp)
{ var list = document.getElementById('imagelist'+idx);
  var sel = document.getElementById('imagesel'+idx);
  var imgs = tmp.split(','); 
  for(var i=0; i< imgs.length; i++)
  { for(var j=0; j< list.length; j++)
	{ if(list.options[j].value == imgs[i])
	  { list.selectedIndex = j;
		Addimage(idx);
	  }
	}
  }
}

function Addimage(idx)
{ var list = document.getElementById('imagelist'+idx);
  var sel = document.getElementById('imagesel'+idx);
  var listindex = list.selectedIndex;
  if(listindex==-1) return; /* none selected */
  var i, max = sel.options.length;
  img = list.options[listindex].text;
  img_id = list.options[listindex].value;
  list.options[listindex]=null;
  i=0;
  var base = sel.options;
  if(base[0].text=='none')
    base[0] = new Option(img);
  else
  { while((i<max) && (img > base[i].text)) i++;
    if(i==max)
      base[max] = new Option(img);
    else
    { newOption = new Option(img);
      if (document.createElement && (newOption = document.createElement('option'))) 
      { newOption.appendChild(document.createTextNode(img));
	  }
      sel.insertBefore(newOption, base[i]);
    }
  }
  base[i].value = img_id;
  var myimgs = eval("document.Mainform.cimages"+idx);
  myimgs.value = myimgs.value+','+img_id;
}

function Removeimage(idx)
{ var list = document.getElementById('imagelist'+idx);
  var sel = document.getElementById('imagesel'+idx);
  var selindex = sel.selectedIndex;
  if(selindex==-1) return; /* none selected */
  var i, max = list.options.length;
  img = sel.options[selindex].text;
  img_id = sel.options[selindex].value;
  classname = sel.options[selindex].className;
  if(img=='none') return;
  if(sel.options.length == 1)
    sel.options[0] = new Option('none');
  else
    sel.options[selindex]=null;
  i=0;
  while((i<max) && (img > list.options[i].text)) i++;
  if(i==max)
    list.options[max] = new Option(img);
  else
  { newOption = new Option(img);
    if (document.createElement && (newOption = document.createElement('option'))) 
      newOption.appendChild(document.createTextNode(img));
    list.insertBefore(newOption, list.options[i]);
  }
  list.options[i].value = img_id;
  
  var myimgs = eval("document.Mainform.cimages"+idx);
  myimgs.value = myimgs.value.replace(','+img_id, '');
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

  /* swapStats adds/removes a row with statistics fields to the field names block in the search block */
  function swapStats(elt)
  { var myrow = document.getElementById("statsblock");
	if(elt.checked)
	  myrow.style.display = "table-row";
	else
	{ myrow.style.display = "none";
	  var elts = myrow.getElementsByTagName("input");
	  for(j=0; j<elts.length; j++)
		elts[j].checked = false;
	}
  }

function getColumn(name)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tHead.rows[0].cells.length;
  for(var i=0;i<len; i++)
  { if(tbl.tHead.rows[0].cells[i].firstChild.getAttribute("fieldname") == name)
      return i;
  }
}

function RowSubmit(elt)
{ var subtbl = document.getElementById("subtable");
  subtbl.innerHTML = "";
  var row = elt.parentNode.parentNode;
  var p = row.cloneNode(true);
  var subrow = subtbl.appendChild(p);
  var rowno = row.childNodes[0].id.substr(4);
  // field contents are not automatically copied
  var inputs = row.getElementsByTagName('input');
  for(var k=0;k<inputs.length;k++)
  { if(inputs[k].name=="") continue;
    if((inputs[k].name.substring(0,6) == "active") || (inputs[k].name.substring(0,7) == "default_on"))
	{ elt = document.rowform[inputs[k].name][0]; /* the trick with the hidden field works not with the rowsubmit so we delete it */
	  elt.parentNode.removeChild(elt);
	  continue;
	}
    else if(inputs[k].type != "button")
    { if(((inputs[k].name.substring(0,6) == "default_on")))
	  { document.rowform[inputs[k].name].type = "text";
	    if(!inputs[k].checked) document.rowform[inputs[k].name].value = "0"; /* value will initially always be "1" */
	  }
	  else
	  {	document.rowform[inputs[k].name].value = inputs[k].value;
	  }
      var temp = document.rowform[inputs[k].name].name;
      document.rowform[inputs[k].name].name = temp;
    }
  }

  var selects = row.getElementsByTagName('select');
  for(var k=0;k<selects.length;k++)  
  { if(!selects[k].name) continue;
    document.rowform[selects[k].name].selectedIndex = selects[k].selectedIndex;
    var temp = document.rowform[selects[k].name].name;
    document.rowform[selects[k].name].name = temp;
  }
  rowform.verbose.value = SwitchForm.verbose.checked;
  if(Mainform.verbose.checked)
     rowform.target="_blank";
  else
    rowform.target="tank";
  rowform.allshops.value = Mainform.allshops.value;  
  rowform.submittedrow.value = rowno;
  rowform.submit();
  subtbl.removeChild(subrow);
}

var tabchanged = 0;
function reg_change(elt)	/* register changed row so that it will be colored and only changed rows will be submitted */
{ var elts = Array();
  elts[0] = elt;
  elts[1] = elts[0].parentNode;
  var i=1;
  while (elts[i] && (!elts[i].id || (elts[i].id != 'Maintable')))
  { elts[i+1] = elts[i].parentNode;
	i++;
  }
  elts[i-2].cells[0].setAttribute("changed", "1");
  elts[i-2].style.backgroundColor="#DDD";
  tabchanged = 1;
}

function reg_unchange(num)	/* change status of row back to unchanged after it has been submitted */
{ var elt = document.getElementById('trid'+num);
  var row = elt.parentNode;
  row.cells[0].setAttribute("changed", "0");
  row.style.backgroundColor="#AAF";
}


/* swapfeatures adds/removes a row with feature field names to the field names block in the search block */
function swapFeatures(elt)
{ var myrow, i;
  for(i=0; i<9; i++)
  { if(myrow = document.getElementById("featureblock"+i))
	{ if(elt.checked)
		myrow.style.display = "table-row";
	  else
	  { myrow.style.display = "none";
		var elts = myrow.getElementsByTagName("input");
		for(j=0; j<elts.length; j++)
			elts[j].checked = false;
	  }
	}
  }
}

var rowsremoved = 0;
function RemoveRow(row)
{ var tblEl = document.getElementById("offTblBdy");
  var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
  rowsremoved++;
}

function SubmitForm()
{ var reccount = Mainform.reccount.value;
  var tbl = document.getElementById("Maintable");
  for(var i=0; i<reccount; i++)
  { divje = document.getElementById('trid'+i); /* check for lines that we clicked away */
    if(!divje)
      continue;
    var chg = divje.getAttribute('changed');
    if(chg == 0)
    { divje.parentNode.innerHTML='';
      continue;
    }
	if(!price_editable && !priceVAT_editable)
	{ divje.innerHTML = ""; /* empty name field that contains hidden price */
	}
	else
	{ if(priceVAT_editable)
	  { var elt = eval("Mainform.priceVAT"+i);
	    if(elt)	/* if(elt) is always true */
	      elt.parentNode.removeChild(elt);
	  }
	  else  /* price_editable */
	  { var elt = eval("Mainform.showprice"+i);
	    if(elt)
	      elt.parentNode.removeChild(elt);
	  }
	}

//	document.getElementsByTagName("input")[i].value.length == 0;

	if((fields.indexOf("discount") !== -1) && (!check_discounts(i))) return false;
  }
  
  /* Note: there is a more elegant solution with Jquery's serialize where you compact all variables */
  var sels = Mainform.getElementsByTagName('select').length;
  var inps = Mainform.getElementsByTagName('input').length;
  var txas = Mainform.getElementsByTagName('textarea').length;
  var tots = sels+inps+txas;

  if(tots > max_input_vars)
  { alert("You are trying to submit "+tots+" fields where your server's max_input_vars allows only "+max_input_vars+"!\nPlease reduce your number of fields.");
	return false;
  }

  /* one could add here code to remove the showprice fields */
  Mainform.verbose.value = SwitchForm.verbose.checked;
  Mainform.urlsrc.value = location.href;
  Mainform.action = 'combi-proc.php';
  Mainform.submit();
}

function change_allshops(flag)
{ if(flag == '1')
	document.body.style.backgroundColor = '#ff7';
  else if(flag == '2')
	document.body.style.backgroundColor = '#fc1';
  else
	document.body.style.backgroundColor = '#fff';
}

function gather_prod_ids()
{ var products = [];
  var rows = document.getElementById("Maintable").rows.length - 1;
  for(var i=0; i < rows; i++)
  { var prod_id = eval("Mainform.id_product"+i);
	if(!prod_id) continue;
	for(var j = 0; j < products.length; j++) 
        if(products[j] == prod_id.value) break;
	if(products[j] == prod_id.value) continue;
	products.push(prod_id.value);
  }
  var gfield = document.getElementById("gatherer");
  gfield.value=products.join();
}

function gather_attr_ids()
{ var prodattrs = [];
  var rows = document.getElementById("Maintable").rows.length - 1;
  for(var i=0; i < rows; i++)
  { var prod_id = eval("Mainform.id_product_attribute"+i);
	if(!prod_id) continue;
	prodattrs.push(prod_id.value);
  }
  var gfield = document.getElementById("attrgatherer");
  gfield.value=prodattrs.join();
}

  function changeMfield()  /* change input fields for mass update when field is selected */
  { base = eval("document.massform.field");
	fieldtext = base.options[base.selectedIndex].value;
    myarr = myarray[fieldtext];

	var muspan = document.getElementById("muval");
	for(i=0; i<myarray["price"].length; i++) /* use here .length to prepare for extra elements */
	{	if(myarr[i] == 0)
		{	document.massform.action.options[i+1].style.display = "none";
			document.massform.action.options[i+1].disabled = true;
		}
		else
		{	document.massform.action.options[i+1].style.display = "block";
			document.massform.action.options[i+1].disabled = false;
		}
	}
	document.massform.action.selectedIndex = 0;
	muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea>";
  }
  
	function changeMAfield()
	{ var base = eval("document.massform.action");
	  var action = base.options[base.selectedIndex].text;
	  base = eval("document.massform.field");
	  var fieldname = base.options[base.selectedIndex].value;
	  var muspan = document.getElementById("muval");
	  if((fieldname=="ls_alert") && (action=="set"))
		muspan.innerHTML = "value: <input type=\"checkbox\" name=\"myvalue\">";
	  else if ((action == "copy from field") || (action == "replace from field"))
	  { tmp = document.massform.field.innerHTML;
	    tmp = tmp.replace("Select a field","Select field to copy from");
		tmp = tmp.replace("<option value=\""+fieldname+"\">"+fieldname+"</option>","");
		tmp = tmp.replace("<option value=\"active\">active</option>","");
		tmp = tmp.replace("<option value=\"category\">category</option>","");
		tmp = tmp.replace("<option value=\"image\">image</option>","");
		tmp = tmp.replace("<option value=\"accessories\">accessories</option>","");
		tmp = tmp.replace("<option value=\"combinations\">combinations</option>","");
		tmp = tmp.replace("<option value=\"discount\">discount</option>","");
		tmp = tmp.replace("<option value=\"carrier\">carrier</option>","");
		if (action == "copy from field")
	       muspan.innerHTML = "<select name=copyfield>"+tmp+"</select>";
		else /* replace from field */
			muspan.innerHTML = "text to replace <textarea name=\"oldval\" class=\"masstarea\"></textarea> <select name=copyfield>"+tmp+"</select>";
		if(fieldname == "image")
			muspan.innerHTML += " &nbsp; covers only <input type=radio name=coverage value=cover> &nbsp; <input type=radio name=coverage value=all checked> all images &nbsp; &nbsp; <input type=checkbox name=emptyonly> Empty only";
	  }
	  else if (action == "replace") muspan.innerHTML = "old: <textarea name=\"oldval\" class=\"masstarea\"></textarea> new: <textarea name=\"myvalue\" class=\"masstarea\"></textarea> regexp <input type=checkbox name=myregexp>";
	  else if (action == "increase%") muspan.innerHTML = "Percentage (can be negative): <input name=\"myvalue\">";
	  else if (action == "increase amount") muspan.innerHTML = "Amount (can be negative): <input name=\"myvalue\">";
	  else if (action == "copy from other lang") muspan.innerHTML = "Select language to copy from: <select name=copylang>"+langcopyselblock+"</select>. This affects name, description and meta fields.";
	  else if ((fieldname=="discount") &&(action=="add"))
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
	  { tmp = "<br/>";
	    tmp += "<select name=shop style=\"width:100px\"><option value=0>All shops</option>"+shopblock.replace(" selected","")+"</select>";
		tmp += " &nbsp; ";
	    tmp += "<select name=currency><option value=0>All cs</option>"+currencyblock+"</select>";
	    tmp += " &nbsp; <select name=country style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
		tmp += " &nbsp; <select name=group style=\"width:90px\"><option value=0>All groups</option>"+groupblock+"</select>";
		tmp += " &nbsp; Cust.id<input name=customer style=\"width:30px\">";
		tmp += " &nbsp; FromPrice<input name=price style=\"width:50px\">";
		tmp += " &nbsp; Min.Qu.<input name=quantity style=\"width:20px\" value=\"1\">";
		tmp += " &nbsp; discount<input name=reduction style=\"width:50px\">";
		if (prestashop_version >= "1.6.0.11")
			tmp += "<select name=reductiontax><option value=0>excl tax</option><option value=1 selected>incl tax</option></select>";
		else
			tmp += "<select name=reductiontax><option value=1 selected>incl tax</option></select>";
		tmp += " &nbsp; <select name=reductiontype><option>amt</option><option>pct</option></select>";
		tmp += " &nbsp;period:<input name=datefrom style=\"width:70px\">";
		tmp += "-<input name=dateto style=\"width:70px\"> (yyyy-mm-dd)";
		tmp += "<br/>";
	    muspan.innerHTML = tmp;
	  }
	  else if ((fieldname=="discount") &&(action=="add fixed target discount"))
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
	  { tmp = "<select name=shop style=\"width:100px\"><option value=0>All shops</option>"+shopblock.replace(" selected","")+"</select>";
		tmp += "<select name=currency><option value=0>All cs</option>"+currencyblock+"</select>";
	    tmp += " &nbsp; <select name=country style=\"width:100px\"><option value=0>All countries</option>"+countryblock+"</select>";
		tmp += " &nbsp; <select name=group style=\"width:90px\"><option value=0>All groups</option>"+groupblock+"</select>";
		tmp += " &nbsp; Cust.id<input name=customer style=\"width:30px\">";
		tmp += " &nbsp; FromPrice<input name=price style=\"width:50px\">";
		tmp += " &nbsp; Min.Qu.<input name=quantity style=\"width:20px\" value=\"1\">";
		tmp += " &nbsp;period:<input name=datefrom style=\"width:70px\">";
		tmp += "-<input name=dateto style=\"width:70px\"> (yyyy-mm-dd)";
		tmp += " &nbsp; Target price: <input name=targetprice style=\"width:30px\" onkeyup=\"fixed_target_discount_change(this)\"> Excl VAT";
		tmp += " &nbsp; <input name=targetpriceVAT style=\"width:30px\" onkeyup=\"fixed_target_discount_change(this)\"> Incl VAT<br/>";
		tmp += "This specialized function creates discounts with the same outcome.<br/>If you";
	    tmp += " set a target of 10 and a product costs 12 it gets a discount of 2. If it"; 
		tmp += " costs 40 its discount will be 30. If its price is below 10 no discount is added.<br/>";
	    muspan.innerHTML = tmp;
	  }	  
	  else if ((fieldname=="discount") &&(action=="remove"))
	  { tmp = " &nbsp; where &nbsp; ";
	    tmp += "<select name=fieldname style=\"width:150px\" onchange=\"dc_field_optioner()\"><option>Select subfield</option><option>shop</option><option>currency</option><option>country</option><option>group</option>";
	    tmp += "<option>price</option><option>quantity</option><option>reduction</option><option>reductiontype</option><option>date_from</option><option>date_to</option></select>";
		tmp += "<span id=\"dc_options\">";
	    muspan.innerHTML = tmp;
	  }
	  else if (document.massform.action.options[3].style.display == "block")
		muspan.innerHTML = "value: <textarea name=\"myvalue\" class=\"masstarea\"></textarea>";
	  else 
		muspan.innerHTML = "value: <input name=\"myvalue\">";
	}

  
  function massUpdate()
  { var i, j, k, x, tmp, base, changed;
	base = eval("document.massform.field");
	/* fieldtext is the recognizer. fieldname is the formfield that is to be updated */
	fieldname = fieldtext = base.options[base.selectedIndex].value;
	var tbl= document.getElementById("offTblBdy");
	if(fieldtext.substr(1,8) == "elect a "){ alert("You must select a fieldname!"); return;}
	base = eval("document.massform.action");
	action = base.options[base.selectedIndex].text;
	if(action.substr(1,8) == "elect an") { alert("You must select an action!"); return;}
	if(action == "copy from other lang")
	{ var potentials = new Array("name");
	  var products = new Array();
	  var fields = new Array();
	  var fields_checked = false;
	  j=0; k=0;
	  for(i=0; i < numrecs; i++) 
	  { prod_base = eval("document.Mainform.id_product"+i);
		if(!prod_base) continue;
		id_product = prod_base.value;
		if(!fields_checked)
		{ for(x=0; x<potentials.length; x++)
		  { field = eval("document.Mainform."+potentials[x]+i);
			if(field) fields[j++] = potentials[x];
		  }
		  if(fields.length == 0) return;
		  fields_checked = true;
		}
		products[k++] = id_product;
	  }
	  document.copyForm.products.value = products.join(",");
	  document.copyForm.fields.value = fields.join(",");
	  document.copyForm.id_lang.value = massform.copylang.value;		  
	  document.copyForm.submit(); /* copyForm comes back with the prepare_update() function */
	  return;
	}
	if((action != "copy from field") && (action != "replace from field") && (fieldtext != "discount"))
	   myval = document.massform.myvalue.value;
	if(((fieldtext == "price") || (fieldtext == "priceVAT")) && !isNumber(myval)) { alert("Only numeric prices are allowed!\nUse decimal points!"); return;}
	if(fieldtext == "image")
	{	var emptyonly = document.massform.emptyonly.checked;
		var coverage = document.massform.coverage.value;
		var imagecol = getColumn("image");
	}		
	if((action == "copy from field") || (action == "replace from field"))
	{	copyfield = document.massform.copyfield.options[document.massform.copyfield.selectedIndex].value;
		cellindex = getColumn(copyfield);
		if(action == "replace from field")
			oldval = document.massform.oldval.value;
		tmp = eval("SwitchForm.disp"+cellindex);
		if(!tmp) 
		{ alert("The field which you copy or replace from should not be in editable mode!");
		  return;
		}
	}

	if((action == "add") && (fieldtext == "discount"))
	{	shop = massform.shop.options[massform.shop.selectedIndex].value;
		currency = massform.currency.options[massform.currency.selectedIndex].value;
		country = massform.country.options[massform.country.selectedIndex].value;
		group = massform.group.options[massform.group.selectedIndex].value;			
		price = massform.price.value;
		quantity = massform.quantity.value;
		reduction = massform.reduction.value;
		reductiontax = massform.reductiontax.value;
		reductiontype = massform.reductiontype.options[massform.reductiontype.selectedIndex].text;
		datefrom = massform.datefrom.value;
		dateto = massform.dateto.value;
		discount_delayed = [];
		setTimeout(function(){delayed_discount_change("reduction");}, 100);
	}

	if((action == "remove") && (fieldtext == "discount"))
	{	var subfieldname = massform.fieldname.options[massform.fieldname.selectedIndex].text;
		var subfield = massform.subfield.value;
	}
	if((action == "add fixed target discount") && (fieldtext == "discount"))
	{	shop = massform.shop.options[massform.shop.selectedIndex].value;
		currency = massform.currency.options[massform.currency.selectedIndex].value;
		country = massform.country.options[massform.country.selectedIndex].value;
		group = massform.group.options[massform.group.selectedIndex].value;	
		quantity = massform.quantity.value;
		datefrom = massform.datefrom.value;
		dateto = massform.dateto.value;
		targetprice = massform.targetprice.value;
		targetpriceVAT = massform.targetpriceVAT.value;
		discount_delayed = [];
		if(targetprice != "")
		{ reductiontax = "0";
		  setTimeout(function(){delayed_discount_change("newprice_excl");}, 100);
		}
		else if(targetpriceVAT != "")
		{ reductiontax = "1";
		  setTimeout(function(){delayed_discount_change("newprice_incl");}, 100);
	    }
		else return; /* neither field had a value */
		reductiontype = "amt";
		/* the following is set pro forma. It's real setting will happen in delayed_discount_change() */
		reduction = 0; 
	}
	if((action=="set") && (fieldtext=="ls_alert"))
	{	myval = document.massform.myvalue.checked;
	}
	for(i=0; i < numrecs; i++) 
	{ 	changed = false;
	  	if(fieldname == "discount")
		   fieldname = "discount_count";
		if(fieldname == "image")
		  field = eval("document.Mainform.image_list"+i);
		else if(fieldname == "price")
		  field = eval("document.Mainform.showprice"+i);
		else
		  field = eval("document.Mainform."+fieldname+i);
		if(!field) { continue; } /* deal with clicked away lines */
		if(fieldname == "image")	
		{ myval2 = striptags(tbl.rows[i].cells[cellindex].innerHTML);
		  var images = tbl.rows[i].cells[imagecol].getElementsByTagName("img");
		  var textareas = tbl.rows[i].cells[imagecol].getElementsByTagName("textarea");
		  for(j=0; j<images.length; j++)
		  { var border = images[j].border;
			var legend = textareas[j].value;
			if(((legend=="") || (!emptyonly)) && ((border) || (coverage == "all")))
			{ textareas[j].value = myval2;
			  if(legend != myval2) changed = true;
			}
		  }
		}
		else if(action == "insert before")
		{	if((fieldname == "description") || (fieldtext == "description_short"))
			{   if(myval.substring(0,3) == "<p>")
				{ myval2 = myval+field.value;
				}
				else
				{ orig = field.value.replace(/^<p>/, "");
				  myval2 = "<p>"+myval+orig;
				}
			}
			else
				myval2 = myval+field.value;
			changed = true;
		}

		else if(action == "increase%")
		{ tmp = field.value * (parseFloat(myval)+100);
		  myval2 = tmp / 100;
		  if(myval2 != 0)
			changed = true;
		}
		else if(action == "increase amount")
		{ myval2 = parseFloat(field.value) + parseFloat(myval);
		  if(fieldname == "qty")
			myval2 = parseInt(myval2);
		  if(myval2 != field.value)
			changed = true;
		}
		
		else if(action == "insert after")
		{	if((fieldname == "description") || (fieldtext == "description_short"))
			{	if( myval.charAt(0) == "<") /* new alinea */
				{	myval2 = field.value+myval;
				}
				else	/* insert in last alinea */
				{	orig = field.value.replace(/<\/p>$/, "");
					myval2 = orig+myval+"</p>";
				}
			}
			else
				myval2 = field.value+myval;
			changed = true;
		}
		else if(fieldtext=="ls_alert")
		{   field = field[1];
			oldvalue = field.checked;
			field.checked = myval;
			if(oldvalue != myval) changed = true;
		}
		else if(action == "replace")
		{ src = document.massform.oldval.value;
		  if(!document.massform.myregexp.checked)
		  { src2 = src.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
			oldvalue = field.value;
			myval2 = field.value.replace(src2, myval);
		  }
		  else
		  { evax = new RegExp(src,"g");
			oldvalue = field.value;
			myval2 = field.value.replace(evax, myval);
		  }
		  if(oldvalue != myval2)
			changed = true;		
		}
		else if((action == "add") && (fieldtext == "discount"))
		{  	var count_root = eval("Mainform.discount_count"+i);
			var dcount = parseInt(count_root.value);
			var attribute = eval('Mainform.id_product_attribute'+i+'.value');
/* function 			 fill_discount(row,entry, id,status,shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,	to,newpricex,newpricei)             */
			var blob = fill_discount(i,dcount,"","new", shop,attribute,	   currency,country,group,"",	   price,quantity,reduction,reductiontax,reductiontype,datefrom,dateto,0,     0);
			var new_div = document.createElement("div");
			new_div.innerHTML = blob;
			var adder = document.getElementById("discount_adder"+i);
			adder.parentNode.insertBefore(new_div,adder);
			discount_delayed.push([i, dcount]);
			count_root.value = dcount+1;
			changed = true;
		}
		else if ((action=="add fixed target discount") && (fieldtext == "discount"))
		{  	/* first we need to know the old price */
			var pricecol = getColumn("price");
			if(pricecol == -1) {alert("Price column must be present!"); return;}
		    var baseprice = eval("massform.price.value");
		    if(!baseprice)
		    { var prodprice = parseFloat(tbl.rows[i].cells[0].dataset.prodprice); /* product price */
			  var baseprice = prodprice + parseFloat(tbl.rows[i].cells[0].childNodes[1].value); /* add combination price */
		    }
		    baseprice = baseprice.toFixed(6);
		    var VAT = parseFloat(tbl.rows[i].cells[0].dataset.vatrate);
			if((targetprice != "") && (parseFloat(targetprice) >= baseprice)) continue;
			if((targetpriceVAT != "") && (parseFloat(targetpriceVAT) >= (baseprice * (1 + (VAT/100))))) continue;
			var count_root = eval("Mainform.discount_count"+i);
			var dcount = parseInt(count_root.value);
			var attribute = eval('Mainform.id_product_attribute'+i+'.value');
/* function 		 fill_discount(row,entry, id,status,shop,attribute,currency,country,group,customer,price,quantity,reduction,reductiontax,reductiontype,from,	to,newpricex,newpricei)             */
			var blob = fill_discount(i,dcount,"","new", shop,attribute,	   currency,country,group,"",	   	"",	quantity,reduction,reductiontax,reductiontype,datefrom,dateto,targetprice, targetpriceVAT);
			var new_div = document.createElement("div");
			new_div.innerHTML = blob;
			var adder = document.getElementById("discount_adder"+i);
			adder.parentNode.insertBefore(new_div,adder);
			discount_delayed.push([i, dcount]);
			count_root.value = dcount+1;
			changed = true;
		}
		else if((action == "remove") && (fieldtext == "discount"))	/* discount remove */
	    { var count_root = eval("Mainform.discount_count"+i);
		  var dcount = parseInt(count_root.value);
		  for(x=0; x<dcount; x++)
		  { if((subfieldname == "shop") || (subfieldname == "currency") ||(subfieldname == "reductiontype"))
		    { var subroot = eval("Mainform.discount_"+subfieldname+x+"s"+i);
			  var subvalue = subroot.value;
		    }
		    else
			  var subvalue = eval("Mainform.discount_"+subfieldname+x+"s"+i+".value");
		    if(subvalue == subfield)
		    { del_discount(i,x);
		    }
		  }
		}
		else myval2 = myval;
		
		/* now implement the new values */
		if((action != "add") && (action != "remove") && (action != "add fixed target discount") && (fieldtext!="ls_alert"))
		{ oldvalue = field.value;
		  field.value = myval2;
  		  if(oldvalue != field.value)
				changed = true;
		}
		if((fieldname == "price") && changed)
			price_change(field);
		else if((fieldname == "priceVAT") && changed)
			priceVAT_change(field);
		else if((fieldname == "VAT") && changed)
			VAT_change(field);
		else if(fieldname == "image")
		{ 
		}
		if(changed) /* we flag only those really changed */
			reg_change(field);
	}
  }
  
function salesdetails(product,attribute)
{ window.open("product-sales.php?product="+product+"&attribute="+attribute+"&startdate="+startdate+"&enddate="+enddate+"&id_shop="+id_shop,"", "resizable,scrollbars,location,menubar,status,toolbar");
  return false;
}
  
function newwin_check()
{ if(search_form.newwin.checked)
	search_form.target = "_blank";
  else
	search_form.target = "";
  if((search_form.combisearch_txt1.value != "") && (search_form.combisearch_fld1.selectedIndex == 0))
  { alert("You provided a search value for the combination but didn't choose a field!");
    return false;
  }
  if((search_form.combisearch_txt2.value != "") && (search_form.combisearch_fld2.selectedIndex == 0))
  { alert("You provided a search value for the combination but didn't choose a field!");
    return false;
  }
  return true;
}

function ChangeGroup(mygroup)
{ if(mygroup == 'A')
	 var myid = document.search_form.groupa.value;
  else if(mygroup == 'B')
	 var myid = document.search_form.groupb.value; 
  else return;
  if(myid == "0")
  { var basis = '<option value="0">No Options</option>';
    if(mygroup=='A')
      var elt = document.search_form.attributea;
    else 
      var elt = search_form.attributeb;
    elt.innerHTML = basis;
    return;
  }
  var query = "ajaxdata.php?myids="+myid+"&task=getattributeoptions&group="+mygroup+"&id_lang=<?php echo $id_lang; ?>";
  LoadPage(query,dynamo2);
}

  function submitCSV()
  { var div = document.getElementById("csvsearchdiv");
    var block = document.getElementById("searchblock");
	var p = block.cloneNode(true);
    div.appendChild(p);
	csvform.verbose.value = SwitchForm.verbose.checked;
	
    var selects = block.getElementsByTagName('select');
    for(var k=0;k<selects.length;k++)  
    { if(!selects[k].name) continue;
      document.csvform[selects[k].name].selectedIndex = selects[k].selectedIndex;
    }
	
	csvform.submit();
	div.innerHTML = "";
  }

function LoadPage(url, callback)
{ var request =  new XMLHttpRequest("");
  request.open("GET", url, true); /* delaypage must be a global var; changed from POST to GET */
  request.onreadystatechange = function() 
  { if (request.readyState == 4 && request.status == 404) /* done = 4, ok = 200 */
	alert("ERROR "+request.status+" "+request.responseText) 
    if (request.readyState == 4 && request.status == 200) /* done = 4, ok = 200 */
    { if (request.responseText) 
        callback(request.responseText);
    };
  }
  request.send(null);
}

function dynamo2(data)  /* add text to copy list at the bottom of the page */
{ var lines = data.split("\n");
  var basis = '<option value="0">Select an attribute</option>';
  if(lines[0]=='A')
  { var elt = document.search_form.attributea;
    elt.innerHTML = basis+lines[1];
  }
  else if(lines[0]=='B')
  { var elt = search_form.attributeb;
    elt.innerHTML = basis+lines[1];
  }
}

function change_sfield(elt)
{ 
}

/* this function adds a row with five search units to the search block */
function addselectrow(elt)
{ var row = document.getElementById('selextra');
  row.style.display = "block";
//  elt.style.display = "none";
  elt.outerHTML = "";
}

function setStocker()
{ search_form.konda_fld1.value='sa.quantity';
  search_form.kond_cmp1.value='lte';
  search_form.kondb_fld1.value='pas.low_stock_threshold';
}
  
function init()
{ 
}

</script>
</head>
<body onload="init()">
<link rel="stylesheet" href="windowfiles/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="windowfiles/dhtmlwindow.js"></script>
<div id="dhtmlwindowholder"><span style="display:none">.</span></div>
<?php

/* section 6: publish page header: menu and diagnostic information */
print_menubar();
echo '<table class="triplehome" cellpadding=0 cellspacing=0><tr>';
echo '<td width="80%" class="headline"><a href="prodcombi-edit.php">Product Combis</a><br>';
echo 'This page allows you to edit combinations of more than one product. You can provide the number of products and the maximum number of combinations that should be shown.<br>';
echo "The top lines let you search in product properties. The bottom section lets you search in attribute combination properties.<br>";
echo "When you search for attributes in the product section matching products will be shown with all their attributes.<br>";
echo "Default lang=".$def_langname." (used for names)";
if($id_lang != $def_lang)
  echo " - Active lang=".$languagename;

echo ". Country=".$countryname." (used for VAT).";
/* The following was added to have diagnostics when problems happen */
$cquery="select count(*) from `". _DB_PREFIX_."shop`";
$cres=dbquery($cquery);
list($shop_count) = mysqli_fetch_row($cres);
$cquery="select count(*) from `". _DB_PREFIX_."shop_group`";
$cres=dbquery($cquery);
list($shop_group_count) = mysqli_fetch_row($cres);
$cquery="select count(*) from `". _DB_PREFIX_."group`";
$cres=dbquery($cquery);
list($group_count) = mysqli_fetch_row($cres);
$cquery="select count(*) from `". _DB_PREFIX_."lang`";
$cres=dbquery($cquery);
list($lang_count) = mysqli_fetch_row($cres);
$stock_management_conf = get_configuration_value('PS_STOCK_MANAGEMENT');
$advanced_stock_management_conf = get_configuration_value('PS_ADVANCED_STOCK_MANAGEMENT');
echo " ".$shop_count." shop(s); ".$shop_group_count." shopgroup(s); ".$group_count." group(s); ".$lang_count." language(s).";
echo ' Stock: ';
if($stock_management_conf == 0) 	echo 'No';
  else if($advanced_stock_management_conf == 0)	echo 'Yes';
  else echo 'ASM';
echo '; Shared: ';
if(!$share_stock) echo 'No';
  else echo $id_shop_group;
echo ";";
echo "<br>Colored discount fields stand for product level discounts.";
echo "</td>";

echo '<td style="text-align:right; width:30%" rowspan=3><iframe name=tank width="230" height="95"></iframe></td></tr>';
echo '</tr><tr><td id="notpaid"></td></tr></table>';

/* section 7: publish search block */
$x=0;
$sortfields = array();
$statz = array("salescount", "revenue","ordercount","buyercount");
foreach($field_array AS $key => $field_row)
{ if(in_array($key, array("pack_stock_type"))) continue;
  if(($field_row[8] == "") || (in_array($field_row[8], $statz))) continue;
  if($key == "accessories") 
	$name=$field_row[1][1]." id";
  else if($key == "availorder")
    $name="available for order";
  else if($key == "category")
    $name=$field_row[1][1]." default id";
  else 
	$name = $field_row[1][1];
  $sortfields[$x++] = array($name,$field_row[8]);
  if($key == "availorder")
  { $sortfields[$x++] = array("show price","ps.show_price");
  }
  else if($key == "category")
  { $sortfields[$x++] = array($field_row[1][1]." name","cl.name"); 
    $sortfields[$x++] = array($field_row[1][1]." id","cl.id_category");
  }
  else if($key == "manufacturer")
  {	$sortfields[$x++] = array($field_row[1][1]." id","m.id_manufacturer");
  }
  else if($key == "supplier")
  { $sortfields[$x++] = array($field_row[1][1]." id","p.id_supplier");
  }
  else if($key == "VAT")
  { $sortfields[$x++] = array("VAT group id","ps.id_tax_rules_group");
  }
}

function sortcmp($a, $b)
{ if ($a[0] == $b[0]) return 0;
  return (strcasecmp($a[0],$b[0]));
}
usort($sortfields, "sortcmp"); 

?>

<table class="triplesearch"><tr><td>
<form name="search_form" method="get" action="prodcombi-edit.php" onsubmit="return newwin_check()"><div id="searchblock">
<table class="tripleminimal"><tr><td>

<?php /* now the three select blocks */
$comparer_values = array("in"=>"in","not_in"=>"!in","gt"=>"&gt;","gte"=>"&gt;=","eq"=>"=","not_eq"=>"!=","lte"=>"&lt;=","lt"=>"&lt;");
for($x=1; $x<=3; $x++)
{ $search_cmp = $input["search_cmp".$x];
  $search_txt = $GLOBALS["search_txt".$x];
  $search_fld = $input["search_fld".$x];
  if($x==1) $txt = "find"; else $txt = "and";
  echo '<td><nobr>'.$txt.'<input name="search_txt'.$x.'" type="text" value="'.str_replace('"','&quot;',$search_txt).'" size="6" />
<select name="search_cmp'.$x.'" class="comparer">';
  $found = false;
  foreach($comparer_values AS $key => $value)
  { $selected = "";
    if($key == $search_cmp)
	{ $selected = " selected";
	  $found = true;
	}
    echo '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
  }
  echo "</select></nobr>";
  echo '<br><select name="search_fld'.$x.'" style="width:10em" onchange="change_sfield(this);"><option>main product fields</option>';
  if(!$found) $input["search_cmp".$x] = "in";

  $found = false;
  foreach($sortfields AS $sortfield)
  { $selected = "";
	if($search_fld == $sortfield[1])
	{ $selected = "selected";
	  $found = true;
	}
    echo "<option value='".$sortfield[1]."' ".$selected.">".$sortfield[0]."</option>"; 
  }
/* the following fields are named sattrib and sfeatur. The "s" stands for search and at the end 
 * something has been removed from the words feature and attribute. Aim is to make it easy to
 * search for those fields without getting confused by other attribute or feature related code */
  $query = "SELECT id_attribute_group, name FROM `". _DB_PREFIX_."attribute_group_lang` WHERE id_lang=".$id_lang;
  $query .= " ORDER BY name";
  $res = dbquery($query);
  while($row=mysqli_fetch_assoc($res))
  { $selected = "";
	if($search_fld == "sattrib".$row["id_attribute_group"])
	{ $selected = "selected";
	  $found = true;
	}
    echo "<option value='sattrib".$row["id_attribute_group"]."' ".$selected.">a-".$row["name"]."</option>"; 
	$selected = "";
	if($search_fld == "sidattrib".$row["id_attribute_group"])
	{ $selected = "selected";
	  $found = true;
	}
    echo "<option value='sidattrib".$row["id_attribute_group"]."' ".$selected.">a:".$row["name"]." id</option>"; 
  }
  
  $query = "SELECT id_feature, name FROM `". _DB_PREFIX_."feature_lang` WHERE id_lang=".$id_lang;
  $query .= " ORDER BY name";
  $res = dbquery($query);
  while($row=mysqli_fetch_assoc($res))
  { $selected = "";
	if($search_fld == "sfeatur".$row["id_feature"])
	{ $selected = "selected";
	  $found = true;
	}
    echo "<option value='sfeatur".$row["id_feature"]."' ".$selected.">f-".$row["name"]."</option>"; 
	$selected = "";
	if($search_fld == "sidfeatur".$row["id_feature"])
	{ $selected = "selected";
	  $found = true;
	}
    echo "<option value='sidfeatur".$row["id_feature"]."' ".$selected.">f:".$row["name"]." id</option>"; 
  }
  echo '</select></td>';
}

	echo '<td><nobr>category: <select name="id_category" onchange="change_categories();" style="width:12em">
	<option value="0">All categories</option>';

	if(!in_array($prestools_settings["catselectortype"], [2,3]))
	{ $query=" select DISTINCT id_category,name from ". _DB_PREFIX_."category_lang WHERE id_lang=".$def_lang." ORDER BY name";
	  $res=dbquery($query);
	  while ($category=mysqli_fetch_array($res)) {
		if ($category['id_category']==$id_category) {$selected=' selected="selected" ';} else $selected="";
	    echo '<option  value="'.$category['id_category'].'" '.$selected.'>'.$category['name'].'</option>';
	  }
	  echo '</select>';
	}
	else if($prestools_settings["catselectortype"] == 2) /* cat with parent like "category <- parent" */
	{ $query= "select DISTINCT cl.id_category,cl.name,cl2.name AS parent from ". _DB_PREFIX_."category_lang cl";
	  $query.= " LEFT JOIN "._DB_PREFIX_."category c on c.id_category=cl.id_category";
	  $query.= " LEFT JOIN "._DB_PREFIX_."category_lang cl2 on cl2.id_category=c.id_parent";	  
	  $query.= " WHERE cl.id_lang=".$def_lang." AND cl2.id_lang=".$def_lang." ORDER BY cl.name";
	  $res=dbquery($query);
	  while ($category=mysqli_fetch_array($res)) {
		if ($category['id_category']==$id_category) {$selected=' selected="selected" '; } else $selected="";
		{ echo '<option value="'.$category['id_category'].'" '.$selected.'>'.$category['name'];
		  echo ' &lt;- '.$category['parent'].'</option>';
		}
	  }
	  echo '</select>';
	}
	else if($prestools_settings["catselectortype"] == 3) /* tree view  */
	{ $query = "SELECT id_category FROM ". _DB_PREFIX_."category WHERE id_parent=0";
	  $res=dbquery($query);
	  $datarow = mysqli_fetch_assoc($res);
	  mysqli_free_result($res);
	  $root = $datarow["id_category"];
	  $mycontent = ""; /* will contain the table */
	  $data = print_cat3($root, 0, $id_category);
	  echo $data;
	}
		
function print_cat3($base, $level, $selector)
{ global $myresults, $mycontent,$triplepath, $id_lang;
  $query = "SELECT c.id_category FROM ". _DB_PREFIX_."category c";
  $query .= " WHERE c.id_parent=".$base." ORDER BY nleft";
  $res=dbquery($query);
  $subcats = array();
  while($row = mysqli_fetch_assoc($res))
    $subcats[] = $row["id_category"];
  mysqli_free_result($res);
  $block = "";
  foreach($subcats AS $subcat)
  { $block .= print_cat3($subcat, $level+1, $selector);
  }
  if($level == 0) return $block;
  $nquery = "SELECT name FROM "._DB_PREFIX_."category_lang cl";
  $nquery .= " WHERE id_category=".$base." AND id_lang=".$id_lang;
  $nres=dbquery($nquery);
  $nrow = mysqli_fetch_assoc($nres);
  $xx = " ";
    for($i=1; $i<$level; $i++)
	  $xx .= "- ";
  
  if($base == $selector) $selected=" selected"; else $selected = "";
  $tmp = '<option value="'.$base.'"'.$selected.'>'.$xx.$nrow["name"].'</option>';
  return $tmp.$block;
}
	$catvalue = "";
	if($id_category!=0)
		$catvalue = $id_category;
	echo '<input id="category_number" style="width:26px; height:13px; color:#888888" onkeyup="change_category_number(\'x\')" value="'.$catvalue.'">';
	
	$checked = "";
	if(isset($_GET["subcats"]) && $_GET["subcats"] == "on") $checked = "checked";
    echo ' &nbsp;With subcats<input type="checkbox" name="subcats" '.$checked.' onchange="change_subcats()"></nobr>';
 	/* there are problems with <nobr> inside a table cell */

	echo ' &nbsp &nbsp; <nobr>Language: <select name="id_lang" style="margin-top:5px">';
	echo $langselblock;
	echo '</select></nobr>';

	echo ' &nbsp; <nobr>shop: <select name="id_shop">'.$shopblock.'</select></nobr>';

	
	echo '</td></tr></table>';
	
    echo '&nbsp; Sort by <select name="order" onchange="change_order()">';

	  if ($input['order']=="position") {$selected=' selected="selected" ';} else $selected="";
	  if (($id_category == 0) ||(isset($input['subcats']))) {$catdisp=' style="display:none"';} else $catdisp="";
	  echo '<option '.$selected.$catdisp.' id="cat_order">position</option>';
	  if ($input['order']=="id_product") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>id_product</option>';
	  if ($input['order']=="name") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>name</option>';
	  if ($input['order']=="price") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>price</option>';
	  if ($input['order']=="VAT") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>VAT</option>';
	  if ($input['order']=="shipweight") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>shipweight</option>';
	  if ($input['order']=="image") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>image</option>';
	  if ($input['order']=="active") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>active</option>';
	  if ($input['order']=="availorder") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>availorder</option>';
	  if ($input['order']=="pack_stock_type") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>pack_stock_type</option>';	  
	  if ($input['order']=="revenue") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>revenue</option>';
	  if ($input['order']=="orders") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>orders</option>';
	  if ($input['order']=="buyers") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>buyers</option>';
	  if ($input['order']=="date_upd") {$selected=' selected="selected" ';} else $selected="";
	  echo '<option '.$selected.'>date_upd</option>';
	  echo '</select>';

	$checked = "";
	if($rising == 'DESC')
	  $checked = "selected";
    echo ' <SELECT name=rising><option>ASC</option><option '.$checked.'>DESC</option></select>';

    echo ' &nbsp; img '.$imgformatblock;
	
	echo '&nbsp; Startrec:&nbsp;<input size=3 name=startrec value="'.$startrec.'">';
	echo '&nbsp; Prods:&nbsp;<input size=3 name=numrecs value="'.$numrecs.'">';
	
  echo ' max&nbsp;rows&nbsp;<input name=maxrows size=2 value="'.$maxrows.'">';
  echo ' &nbsp; <button onclick="ShowFields(); return false;">Show fields</button>';
  
/* Section 8: Publish the block where you can select your product fields */
	echo '<table id="fieldstable" style="display:none"><tr>';
	foreach($prestools_settings["productedit_fieldblock"] AS $fieldrow)
	{ echo '<tr>';
	  foreach($fieldrow AS $fieldcel)
	  { $checked = in_array($fieldcel, $input["fields"]) ? "checked" : "";
	    if(($fieldcel == "") || (($fieldcel=="pack_stock_type") && (version_compare(_PS_VERSION_ , "1.6.0.12", "<"))
		 || (in_array($fieldcel, array("isbn", "show_condition","state")) && (version_compare(_PS_VERSION_ , "1.7.0", "<")))
		 || (in_array($fieldcel, array("reserved")) && (version_compare(_PS_VERSION_ , "1.7.2", "<")))
		 || (in_array($fieldcel, array("ls_threshold", "aDeliveryT", "deliInStock","deliOutStock","ls_alert")) && (version_compare(_PS_VERSION_ , "1.7.3", "<"))))
		 || (in_array($fieldcel, array("location")) && (version_compare(_PS_VERSION_ , "1.7.5", "<")))
		 || (in_array($fieldcel, array("mpn")) && (version_compare(_PS_VERSION_ , "1.7.7", "<")))
		 || (in_array($fieldcel, array("product_type")) && (version_compare(_PS_VERSION_ , "1.7.8", "<")))
		 || (in_array($fieldcel, array("unitPrice")) && (version_compare(_PS_VERSION_ , "8.0", "<"))))
			echo "<td></td>";
		else if($fieldcel == "features")
			echo "<td></td>";  
//			echo '<td><input type="checkbox" name="fields[]" value="features" '.$checked.' onchange="swapFeatures(this)" />Features</td>';
		else if(!in_array($fieldcel, array("combinations","statistics")))
			echo '<td><input type="checkbox" name="fields[]" value="'.$fieldcel.'" '.$checked.' />'.$field_array[$fieldcel][1][2].'</td>';
	  }
	  echo '</tr>';
	}
	echo '</table>';
	/* end of Section 8: Publish the block where you can select your product fields */
  
    /* Section 9: Publish the block where you can select your combination fields */
  echo '<hr style="background-color:#666; height:1px; ">';
  /*==============================*/
  $disp = "";
  if((!isset($_GET["combisearch_txt2"]) || ($_GET["combisearch_txt2"]==''))
	  && (!isset($_GET["konda_fld1"]) || ($_GET["konda_fld1"]=='')))
     $disp = ' style="display:none"';
  echo '<div id=selextra '.$disp.'>
  <table><tr><td>';
  print_combisearch(2);
  echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td>';
  print_compareunit(1);
  echo '
  </tr></table></div>';

  /*==========================================*/
  
  echo '<table class="tripleminimal"><tr><td>';
  if(!isset($_GET["combisearch_txt2"]) || ($_GET["combisearch_txt2"]==''))
  { echo '<img src=ander.png onclick="addselectrow(this); return false;"></td><td>';
  }
  print_combisearch(1);
  
  echo '</td><td>&nbsp;&nbsp;&nbsp;</td>';
  
  echo '<td>Show<br>only&nbsp;for:</td><td>';
  echo '<select name=groupa onchange="ChangeGroup(\'A\')"><option value=0>Select attribute group</option>';
  $query="select id_attribute_group, name from `". _DB_PREFIX_."attribute_group_lang` WHERE id_lang=".$id_lang." ORDER BY NAME";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
  { if($row['id_attribute_group'] == $groupa) $selected="selected"; else $selected="";
    echo '<option value="'.$row['id_attribute_group'].'" '.$selected.'>'.$row['name'].'</option>';
  }
  echo '</select><br>';
  echo '<select name=attributea>';
  if($groupa != "0")
  { echo '<option value="0">Select an option</option>';
	$aquery = "SELECT a.id_attribute, l.name";
    $aquery .= " FROM ". _DB_PREFIX_."attribute a";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=a.id_attribute AND l.id_lang='".$id_lang."'";
    $aquery .= " WHERE a.id_attribute_group=".$groupa." ORDER BY position";
    $ares=dbquery($aquery);
    while($arow = mysqli_fetch_assoc($ares))
    { if($arow['id_attribute'] == $attributea) $selected="selected"; else $selected="";
      echo '<option value="'.$arow['id_attribute'].'" '.$selected.'>'.$arow['name'].'</option>';
    }
  }
  else 
    echo '<option value="0">No Options</option>';
  echo '</select></td><td>';
  echo 'and</td><td>';
  echo '<select name=groupb onchange="ChangeGroup(\'B\')"><option value=0>Select attribute group</option>';
  $query="select id_attribute_group, name from `". _DB_PREFIX_."attribute_group_lang` WHERE id_lang=".$id_lang." ORDER BY name";
  $res=dbquery($query);
  while($row = mysqli_fetch_array($res))
  { if($row['id_attribute_group'] == $groupb) $selected="selected"; else $selected="";
    echo '<option value="'.$row['id_attribute_group'].'" '.$selected.'>'.$row['name'].'</option>';
  }
  echo '</select></nobr><br>';
  echo '<select name=attributeb>';
  if($groupb != "0")
  { echo '<option value="0">Select an option</option>';
	$aquery = "SELECT a.id_attribute, l.name";
    $aquery .= " FROM ". _DB_PREFIX_."attribute a";
    $aquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=a.id_attribute AND l.id_lang='".$id_lang."'";
    $aquery .= " WHERE a.id_attribute_group=".$groupb." ORDER BY position";
    $ares=dbquery($aquery);
    while($arow = mysqli_fetch_assoc($ares))
    { if($arow['id_attribute'] == $attributeb) $selected="selected"; else $selected="";
      echo '<option value="'.$arow['id_attribute'].'" '.$selected.'>'.$arow['name'].'</option>';
    }
  }
  else 
    echo '<option value="0">No Options</option>';
  echo '</select></td><td width="65%" style="text-align:left">';
  echo ' &nbsp; <input type=checkbox name="base_included" onclick="switch_pricebase(this)"> include baseprice<br>';
  echo ' &nbsp; Extra fields: ';
  if($discount_included) $checked = "checked"; else $checked = "";
  echo ' &nbsp;<input type=checkbox name="discount_included" '.$checked.'> discount';
  if($suppliers_included) $checked = "checked"; else $checked = "";
  echo ' &nbsp;<input type=checkbox name="suppliers_included" '.$checked.'> suppliers';
  if($stats_included) $checked = "checked"; else $checked = "";
  echo ' &nbsp;<input type=checkbox name="stats_included" '.$checked.' onchange="swapStats(this)"> statistics</td>'; 
  
  echo '</td></tr>';
	$disped = $stats_included ? "" : "style='display:none'";
	echo '<tr id=statsblock '.$disped.'">';
	echo '<td colspan=8>Stats: Period (yyyy-mm-dd): <input size=5 name=startdate value='.$input['startdate'].'> till <input size=5 name=enddate value='.$input['enddate'].'><img src="ea.gif" title="Statistics here are per shop."> &nbsp; &nbsp;';
	$checked = in_array("salescnt", $input["fields"]) ? "checked" : "";
	echo '<input type="checkbox" name="fields[]" value="salescnt" '.$checked.' />'.$screentext_pe["salescnt"][2];
	$checked = in_array("revenue", $input["fields"]) ? "checked" : "";
	echo '&nbsp; &nbsp;<input type="checkbox" name="fields[]" value="revenue" '.$checked.' />'.$screentext_pe["revenue"][2];
	$checked = in_array("orders", $input["fields"]) ? "checked" : "";
	echo ' &nbsp; &nbsp;<input type="checkbox" name="fields[]" value="orders" '.$checked.' />'.$screentext_pe["orders"][2];	
	$checked = in_array("buyers", $input["fields"]) ? "checked" : "";
	echo '&nbsp; &nbsp;<input type="checkbox" name="fields[]" value="buyers" '.$checked.' />'.$screentext_pe["buyers"][2].'</td>';
	echo '</tr>';
  
    echo '</table>';
	
	echo '</div></td><td>
	<input type=checkbox name=newwin>new<br/>window<p/>
	<input type="submit" value="search" />';
	echo '</td></tr></table></form>';
	/* end of section 9: Publish the block where you can select your combination fields */

/* section 10: Mass Edit and csv export */
/* first build the array that defines which mass edit functions are available for which fields */
	echo '<script type="text/javascript">
	  var myarray = [];'; /* define which actions to show for which fields */
	  /* indices: 0=Set; 1=insert before 2=insert after 3=replace 4=increase% 5=increase amount
	  6=copy from field 7=add 8=remove 9=add fixed target discount */
  
	echo '							/*   0 1 2 3 4 5 6 7 8 9 */
	  myarray["wholesale_price"] = 		[1,0,0,1,1,1,0,0,0,0];
	  myarray["price"] = 				[1,0,0,1,1,1,0,0,0,0];	  
	  myarray["priceVAT"] = 			[1,0,0,1,1,1,0,0,0,0];
	  myarray["ecotax"] = 				[1,0,0,1,1,1,0,0,0,0];	  
	  myarray["weight"] = 				[1,0,0,0,0,0,0,0,0,0];
	  myarray["isbn"] = 				[1,0,0,0,0,0,0,0,0,0];  
	  myarray["unit_price_impact"] =	[1,0,0,1,1,1,0,0,0,0];
	  myarray["minimal_quantity"] = 	[1,0,0,0,0,0,0,0,0,0];
	  myarray["available_date"] = 		[1,0,0,0,0,0,0,0,0,0];
	  myarray["reference"] = 			[1,1,1,1,0,0,0,0,0,0];	  
//	  myarray["supplier_reference"]=	[1,1,1,1,0,0,0,0,0,0];
	  myarray["location"] = 			[1,1,1,1,0,0,0,0,0,0];
	  myarray["ean"] = 					[1,0,0,0,0,0,0,0,0,0];
	  myarray["upc"] = 					[1,0,0,0,0,0,0,0,0,0];
	  myarray["discount"] = 			[0,0,0,0,0,0,0,1,1,1];
	  myarray["ls_threshold"] = 		[1,0,0,0,0,0,0,0,0,0];
	  myarray["ls_alert"] = 			[1,0,0,0,0,0,0,0,0,0];	  
	  myarray["quantity"] = 			[1,0,0,0,0,1,0,0,0,0];';
								    /*   0 1 2 3 4 5 6 7 */

echo '  
	</script>';
	echo '<hr/><table style="background-color:#CCCCCC; width:100%"><tr><td style="width:90%">'.t('Mass update').'<form name="massform" onsubmit="massUpdate(); return false;">
	<select name="field" onchange="changeMfield()"><option value="Select a field">'.t('Select a field').'</option>';
	foreach($combifields AS $field)
	{	if(($field[0] != "id_product_attribute") && ($field[0] != "name") && ($field[0] != "combination") && ($field[0] != "image") && ($field[0] != "default_on"))
			echo '<option value="'.$field[3].'">'.$field[0].'</option>';
	}
	echo '</select>';
	echo '<select name="action" onchange="changeMAfield()" style="width:135px"><option>Select an action</option>';
	echo '<option>set</option>';
	echo '<option>insert before</option>';
	echo '<option>insert after</option>';
	echo '<option>replace</option>';
	echo '<option>increase%</option>';
	echo '<option>increase amount</option>';
	echo '<option>copy from field</option>';
	echo '<option>add</option>';
	echo '<option>remove</option>';
	echo '<option>add fixed target discount</option>';
	echo '</select>';
	echo '&nbsp; <span id="muval">value: <textarea name="myvalue" class="masstarea"></textarea></span>';
	echo ' &nbsp; &nbsp; <input type="submit" value="'.t('update all editable records').'"></form>';
	echo t('NB: Prior to mass update you need to make the field editable. Afterwards you need to submit the records');
	echo '</td><td style="text-align:right;">';
/* section: csv generation */
/* the searchblock will be copied to csvsearchdiv before submission */
	echo '<form name=csvform target=_blank action="prodcombi-csv.php"><table><tr>';
	echo '<td style="text-align:center"><nobr>Add PS fields</nobr><br> <input type="checkbox" name="psfields"<input type=hidden name=verbose></td>';
	echo '<td>Separator<br> <input type="radio" name="separator" value="semicolon" checked>; <input type="radio" name="separator" value="comma">, </td>';
	echo '<td><div style="display:none" id="csvsearchdiv"></div>';
	echo '<nobr> &nbsp; <button onclick="submitCSV(); return false;" style="margin-top:4px">Export&nbsp;CSV</button></nobr></td></tr></table></form>';
	echo '</tr></table>';
	
/* end section 10: Mass Edit and csv export */
	
/* section 13: the switchform: hide/show fields and submit button */
  $combistats = array("salescnt", "revenue","orders","buyers");
  echo '<form name=SwitchForm><table class="tripleswitch" style="empty-cells: show;"><tr><td><br>Hide<br>Show</td>';
  
  for($i=2; $i< sizeof($infofields); $i++)
  { /* standard the start mode of fields is "DISPLAY"(=1). But you could specify in $infofields[$i][3] that the field is initially hidden or in edit mode */
	$checked0 = $checked1 = $checked2 = "";
    if($infofields[$i][3] == 0) $checked0 = "checked"; 
    if($infofields[$i][3] == 1) $checked1 = "checked";

    echo '<td>'.$infofields[$i][1][0].'<br>';
    echo '<input type="radio" '.$checked0.' name="disp'.$i.'" id="disp'.$i.'_off" value="0" onClick="switchDisplay(\'offTblBdy\', this,'.$i.',0)" /><br>';
    echo '<input type="radio" '.$checked1.' name="disp'.$i.'" id="disp'.$i.'_on" value="1" onClick="switchDisplay(\'offTblBdy\', this,'.$i.',1)" /><br>';
    echo "</td>";
  }

  foreach($features AS $key => $feature)
  {	$cleanfeature = str_replace('"','_',$feature);
    if (in_array($cleanfeature, $input["fields"]))
	{   echo '<td><nobr>'.$feature.'</nobr><br>';
		echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_off" value="0" onClick="switchDisplay(\'offTblBdy\', this,'.$i.',0)" /><br>';
		echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_on" value="1" checked onClick="switchDisplay(\'offTblBdy\', this,'.$i.',1)" /><br>';
		echo '</td>';
		$i++;
	}
  }
  echo "<td colspan=16 align=center><input type=checkbox name=verbose>verbose &nbsp; &nbsp; ";
  echo "<input type=button value='Submit all' onClick='return SubmitForm();'></td></tr>";
  echo '<tr><td>&nbsp;<br>Hide<br>Show<br>Edit</font></td>';
  $i++; /* skip id */
  for($j=1; $j< sizeof($combifields); $j++)
  { $checked0 = $checked1 = $checked2 = "";
    if($combifields[$j][2] == 0) $checked0 = "checked"; 
    if($combifields[$j][2] == 1) $checked1 = "checked"; 
    echo '<td>'.$combifields[$j][3].'<br>';
    echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_off" value="0" '.$checked0.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',0)" /><br>';
    echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_on" value="1" '.$checked1.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',1)" /><br>';
    if(!in_array($combifields[$j][0], array("default_on","image","ids","id_product_attribute")))
	  echo '<input type="radio" name="disp'.$i.'" id="disp'.$i.'_edit" value="2" '.$checked2.' onClick="switchDisplay(\'offTblBdy\', this,'.$i.',2)" /><br>';
    else
		echo "&nbsp;";
    echo "</td>";
	$i++;
  }
  echo "</tr></table></form>";
/* end of section 13: the switchform: hide/show fields and submit button */

/* section 16: build and execute the product query */

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
    { $wheretext .= " AND ".$nottext." EXISTS(SELECT NuLL FROM 
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
// echo $query;
 
 /* end of section 16: build and execute the product query */
  
/* section 17: Next and Prev */
  echo '<table class="tripleminimal"><tr><td style="width:5%">';
  $nextlink = preg_replace("/&?startrec=[0-9]*/","",$_SERVER['REQUEST_URI']);
  if(!strpos($nextlink, "?")) $nextlink .= "?1=1";
  $startrec = $startrec;
  $numrecs4 = $numrecs;  
  if($startrec == 0) echo "PREV ";
  else
  { $prevrec = $startrec-$numrecs4; 
	if($prevrec > $numrecs2) $prevrec = $numrecs2-1;
    if($prevrec < 0) $prevrec = 0;
	echo '<a href="'.$nextlink.'&startrec='.$prevrec.'">PREV</a>';
  }
  echo '</td><td style="width:30%; text-align:center"><span id="countlist"></span> in shop '.$id_shop;
  if($share_stock == 1) echo " - stock group ".$shop_group_name;
  echo '</td><td style="text-align:right; width:5%">';
  if($startrec + $numrecs4 >= $numrecs2) echo " NEXT";
  else
  { $nextrec = $startrec + $numrecs4; 
	echo '<a href="'.$nextlink.'&startrec='.$nextrec.'">NEXT</a>';
  }
  echo "</td></tr></table>";
  
/* section 18: warnings and explanations */
  $warning = "";
  if(in_array("accessories", $input["fields"]))
    $warning .= "For accessories fill in comma separated article numbers like '233,467'. Non-existent articles numbers will be ignored!<br/>";
  if(in_array("carrier", $input["fields"]))
    $warning .= "Standard all carriers are assigned to all products. You need to assign carriers here only in special cases.<br/>";
  if(in_array("shopz", $input["fields"]))
    $warning .= "No stock will be assigned if you add a product to more shops.<br/>";
  echo '<span id="warning" style="background-color: #FFAAAA">'.$warning.'</span>';
echo "<script>
var numrecs3 = ".$numrecs3.";
</script>";
  // "*********************************************************************";
  echo '<div id="dhwindow" style="display:none"></div>';
  
/* section 19: The main form: the multishop option */
  if($prestools_settings["avoid_iframes"])
	 echo '<form name="Mainform" method=get>';
  else
	echo '<form name="Mainform" method=post>';
  echo '<input type=hidden name=id_lang value="'.$id_lang.'">';
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
  echo '<input type=hidden name=id_shop value='.$id_shop.'>';
  echo '<input type=hidden name=verbose><input type=hidden name=featuresset><input type=hidden name=urlsrc>';

/* section 20: The main table: the column headers */
    echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
    for($i=0; $i<sizeof($infofields); $i++)
    { $align = $namecol = "";
      if($infofields[$i][5] == 1)
        $align = ' style="text-align:right"';
	  if($infofields[$i][0] == "name")
        $namecol = ' class="namecol"';
      echo "<col id='col".$i."'".$align.$namecol."></col>";
    }
    for(;$i<sizeof($infofields)+$featurecount+sizeof($combifields); $i++)
	  echo "<col id='col".$i."'></col>";
    echo "</colgroup><thead><tr>";
	
    for($i=0; $i<sizeof($infofields); $i++)
    { $reverse = "false";
      $id="";
      if (in_array($infofields[$i][0], $statfields))
	  { $reverse = 1;
	    $id = 'id="stat_'.$infofields[$i][0].'"'; /* assign id for filling in totals */
	  }
	  $ins = "";
	  if(in_array($infofields[$i][0],array("price","priceVAT","image"))) $ins="p";
      echo '<th id="hdr'.$i.'"><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i.', '.$reverse.');" '.$id.' fieldname="'.$ins.$infofields[$i][0].'" title="'.$infofields[$i][1][1].'">'.$infofields[$i][1][0].'</a></th
>';
    }
	
    foreach($features AS $key => $feature)
    { $cleanfeature = str_replace('"','_',$feature);
      if (in_array($cleanfeature, $input["fields"]))
	  { $fieldname = "feature".$key."field";
		echo '<th id="hdr'.$i.'"><a href="" onclick="this.blur(); return sortTheTable(\'offTblBdy\', '.$i++.', false);" fieldname="'.$fieldname.'" title="'.str_replace('"','\"',$feature).'">'.$feature.'</a></th>';
	  } 	
	}
    for($j=0; $j<$numfields; $j++)
    { if($combifields[$j][2]==HIDE) $vis='style="display:none"'; else $vis="";
	  echo '<th id="hdr'.$i.'" '.$vis.'><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i++.', false);" fieldname="'.$combifields[$j][0].'" title="'.$combifields[$j][0].'">'.$combifields[$j][3].'</a></th
>';
    }
    echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
    echo "</tr></thead
  ><tbody id='offTblBdy'>"; /* end of header */

/* section 21: the main table: the products */
  $x=0;
  $rowcnt = 0;
  $prodcount = 0;
  $legacy_images = get_configuration_value('PS_LEGACY_IMAGES');
  $haserrors = false;
  while (($datarow=mysqli_fetch_array($res)) && ($rowcnt < $maxrows)) 
  { 
    if(($datarow['name'] == "") || ($datarow['pltest'] === NULL) || ($datarow['ptest'] === NULL))
	{ $haserrors = true;
	  continue;
	}
	
	$combination_count = 0;
	if(!empty(array_intersect($input["fields"], array("quantity", "stockflags", "warehousing","supplier", "combinations"))))
	{ $aquery = "SELECT count(*) FROM `". _DB_PREFIX_."product_attribute` WHERE id_product='".$datarow['id_product']."'";
	  $ares=dbquery($aquery);
	  list($combination_count) = mysqli_fetch_row($ares); 
	}
    
    /* Note that trid (<tr> id) cannot be an attribute of the tr as it would get lost with sorting */
    $prodline = "";
	$prodfieldcount =0;
	for($i=1; $i< sizeof($infofields); $i++)
    { $prodfieldcount++;
      $sorttxt = "";
      $color = "";
	  
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
	  { $prodline .=  "<td srt='".$myvalue."'>";
	    $accs = explode(",",$myvalue);
		$z=0;
	    foreach($accs AS $acc)
		{ if($z++ > 0) $prodline .=  ",";
		  $prodline .=  "<a title='".get_product_name($acc)."' href='#' onclick='return false;' style='text-decoration: none;'>".$acc."</a>";
		}
	    $prodline .=  "</td>";
	  }  /* end of accessories */
	  
	  else if ($infofields[$i][0] == "aDeliveryT")  // additional_delivery_times
	  { $prodline .=  "<td>";
		$squery = "SELECT additional_delivery_times FROM ". _DB_PREFIX_."product WHERE id_product='".$datarow['id_product']."'";
		$sres=dbquery($squery);
		$srow=mysqli_fetch_array($sres);
		if($datarow["additional_delivery_times"] == "1")
			$prodline .=  "default info";	
		else if($datarow["additional_delivery_times"] == "2")
			$prodline .=  "product info";
		else 
			$prodline .=  "none";	
	    $prodline .=  "</td>";
	  }		/* end of aDeliveryT */
	  
	  else if ($infofields[$i][0] == "attachmnts")
      { $cquery = "SELECT a.file_name, a.file, a.mime, l.name, p.id_attachment FROM ". _DB_PREFIX_."product_attachment p";
		$cquery .= " LEFT JOIN ". _DB_PREFIX_."attachment a ON a.id_attachment=p.id_attachment";
	    $cquery .= " LEFT JOIN ". _DB_PREFIX_."attachment_lang l ON a.id_attachment=l.id_attachment AND l.id_lang='".$id_lang."'";
	    $cquery .= " WHERE id_product='".$datarow['id_product']."'";
		$prodline .=  "<td>";
		$cres=dbquery($cquery);
		$z=0;
		while ($crow=mysqli_fetch_array($cres)) 
		{	if($z++ > 0) $prodline .=  "<br>";
			$prodline .=  "<a class='attachlink' title='".$crow['id_attachment']."' href='downfile.php?filename=".$crow["file_name"]."&filecode=".$crow["file"]."&download_dir=".$download_dir."&mime=".$crow["mime"]."' target=_blank'>".$crow['name']."</a>";
	        if(!file_exists($download_dir."/".$crow["file"])) $prodline .=  " <b>missing</b>";
			}
	    $prodline .=  "</td>";
		mysqli_free_result($cres);
      }	 /* end of attachmnts */
	  
	  else if ($infofields[$i][0] == "availorder")
	  { $prodline .=  "<td srt='".$myvalue."'>";
	    $available_for_order = $datarow['available_for_order'];
		$show_price = $datarow['show_price'];
		if($available_for_order == 1)
		  $prodline .=  $availordertypes[0];
		else if($show_price == 1)
		  $prodline .=  $availordertypes[1];
		else
		  $prodline .=  $availordertypes[2];
	    $prodline .=  "</td>";
	  }		/* end of availorder */
	  
	  else if ($infofields[$i][0] == "cache_is_pack")
	  { $prodline .=  "<td srt='".$myvalue."'>".$myvalue;
		if($myvalue == 1) /* check for parts it it is pack */
		{ $cquery = "SELECT p.*, ps.active  FROM ". _DB_PREFIX_."pack p";
		  $cquery .= " LEFT JOIN ". _DB_PREFIX_."product_shop ps ON p.id_product_item=ps.id_product AND ps.id_shop=".$id_shop;
		  $cquery .= " WHERE id_product_pack='".$datarow['id_product']."'";
		  $cres=dbquery($cquery);
		  $prodline .= " (";
		  $first = true;
		  while($crow=mysqli_fetch_array($cres))
		  { if($first)
				$first = false;
			else
				$prodline .= ",";
			if($crow["active"] == "0") $prodline .= "<s>";
			if(is_null($crow["active"])) $prodline .= "<i>";
			$prodline .= $crow["quantity"]."*".$crow["id_product_item"];
			if($crow["id_product_attribute_item"] != "0")
				$prodline .= ":".$crow["id_product_attribute_item"];
			if(is_null($crow["active"])) $prodline .= "</i>";
			if($crow["active"] == "0") $prodline .= "</s>";
		  }
		  $prodline .= ")";
		}
        $prodline .= "</td>";
	  }
	  
	  else if ($infofields[$i][0] == "carrier")
      { $cquery = "SELECT id_carrier_reference FROM ". _DB_PREFIX_."product_carrier WHERE id_product='".$datarow['id_product']."' AND id_shop='".$id_shop."' LIMIT 1";
		$cres=dbquery($cquery);
		if(mysqli_num_rows($cres) != 0)
		{ $cquery = "SELECT id_reference, cr.name FROM ". _DB_PREFIX_."product_carrier pc";
		  $cquery .= " LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0";
		  $cquery .= " WHERE id_product='".$datarow['id_product']."' AND id_shop='".$id_shop."' ORDER BY cr.name";
		  $cres=dbquery($cquery);
		  $prodline .=  "<td><table border=1 id='carriers".$x."'>";
		  while ($crow=mysqli_fetch_array($cres)) 
		  { $prodline .=  "<tr><td id='".$crow['id_reference']."'>".$crow['name']."</td></tr>";
		  }
		  $prodline .=  "</table></td>";
		}
		else
		  $prodline .=  "<td></td>";
		mysqli_free_result($cres);
	  }	  /* end of carrier */
	  
      else if ($infofields[$i][0] == "category")
	  { $prodline .=  "<td ".$sorttxt.">";
	    $cquery = "select id_category from ". _DB_PREFIX_."category_product WHERE id_product='".$datarow['id_product']."' ORDER BY id_category";
		$cres=dbquery($cquery);
		$z=0;
		$default_found = false;
		while ($crow=mysqli_fetch_array($cres)) 
		{	if($z++ > 0)
			{ $prodline .=  ",";
			  if(!(($z-1)%3)) $prodline .=  " "; /* put in a space so that the browser can break the line */
			}
			$catlink = get_base_uri().$langinsert.$crow['id_category']."-".$category_rewrites[$crow['id_category']];
			if ($crow['id_category'] == $myvalue)
			{	$prodline .=  "<a title='".$category_names[$myvalue]."' href='".$catlink."' target='_blank'>".$myvalue."</a>";
				$default_found = true;
			}
			else 
				$prodline .=  "<a title='".$category_names[$crow['id_category']]."' href='".$catlink."' target='_blank' style='text-decoration: none;'>".$crow['id_category']."</a>";
		}
	    $prodline .=  "</td>";
		mysqli_free_result($cres);
	  }  /* end of category */
      else if ($infofields[$i][0] == "customizations")
	  { $prodline .= "<td ".$sorttxt.">";
	  	$prodline .= '<table id="customizations'.$x.'">';
		$dquery = "SELECT * FROM ". _DB_PREFIX_."customization_field";
	    $dquery .= " WHERE id_product='".$datarow['id_product']."'";
		if (version_compare(_PS_VERSION_ , "1.7.3", ">="))
		  $dquery .= " AND is_deleted=0";
		$dres=dbquery($dquery);
		while ($drow=mysqli_fetch_array($dres))
		{ $prodline .= '<tr data-custid="'.$drow["id_customization_field"].'"><td style="padding:0;"><table>';
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
		  { $prodline .= '<tr><td data-src="'.$langid.'-'.$langcodes[$langid].'">';
		    if((isset($custlangs[$langid])) && ($custlangs[$langid]!=""))
			  $prodline .= $custlangs[$langid];
		    else
			  $prodline .= "&nbsp;";
			$prodline .= '</td></tr>';
		  }
		  $prodline .= '</table></td><td>';

	      if($drow["type"]==0) $prodline .= 'uploadfile';
			else $prodline .= 'textfield';
	      $prodline .= '</td><td>';
		  if($drow["required"]) $prodline .= 'req';
		  $prodline .= '</td></tr>';
		}
	    $prodline .= "</table>";
		if(mysqli_num_rows($dres) > 0)
		  $prodline .= '<center><a href="customizations.php?id_product='.$datarow['id_product'].'" target=_blank>See values</a></center>';
		$prodline .= "</td>";
		mysqli_free_result($dres);
	  } /* end of customizations */
	  else if ($infofields[$i][0] == "discount")
      { $dquery = "SELECT sp.*, cu.iso_code AS currency";
		$dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
		$dquery.=" left join ". _DB_PREFIX_."currency cu on sp.id_currency=cu.id_currency";		
	    $dquery .= " WHERE sp.id_product='".$datarow['id_product']."'";
		$dres=dbquery($dquery);
		$prodline .=  "<td><table border=1>";
		while ($drow=mysqli_fetch_array($dres)) 
		{ $bgcolor = "";
		  if($drow["id_specific_price_rule"] != 0)
			$bgcolor = ' style="background-color:#dddddd"';
		  $prodline .=  '<tr>';
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, product_attribute, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
		  if($drow["id_shop"] == "0") $drow["id_shop"] = "";
		  $prodline .=  "<td>".$drow["id_shop"]."</td>";
		  if($drow["id_product_attribute"] == "0") $drow["id_product_attribute"] = "";
		  $prodline .=  "<td>".$drow["id_product_attribute"]."</td>";
		  $prodline .=  "<td>".$drow["currency"]."</td>";
		  $prodline .=  "<td>".$drow["id_country"]."</td>";
		  $prodline .=  "<td>".$drow["id_group"]."</td>";

		  if($drow["id_customer"] == "0") $drow["id_customer"] = "";
		  $prodline .=  "<td>".$drow["id_customer"]."</td>";
		  $rate = $taxrates[$datarow["id_tax_rules_group"]]/100;
		  if($drow["price"] == -1)
		  {	$frompriceVAT = number_format(((($rate) +1) * $datarow['price']),2, '.', '');
		    $fromprice = $datarow['price'];
			$drow["price"] = "";
		  }
		  else /* the prices mentioned here are excl VAT */
		  { $frompriceVAT = (($datarow['rate']/100) +1) * $drow['price'];
		    $drow["price"] = $drow["price"] * 1; /* remove trailing zeroes */
		  }
		  $prodline .=  "<td>".$drow["price"]."</td>";
		  $prodline .=  "<td style='background-color:#FFFF77'>".$drow["from_quantity"]."</td>";
		  if($drow["reduction_type"] == "percentage")
			$drow["reduction"] = $drow["reduction"] * 100;
		  else 
		    $drow["reduction"] = $drow["reduction"] * 1;
		  $prodline .=  "<td>".$drow["reduction"]."</td>";
		  $reduction_tax = "1";
		  if (version_compare(_PS_VERSION_ , "1.6.0.11", ">="))
		  { $prodline .=  "<td>".$drow["reduction_tax"]."</td>"; /* 0=excl; 1=incl before 1.6.0.11 there was only incl */
			$reduction_tax = $drow["reduction_tax"];
		  }
		  else 
		    $prodline .=  "<td></td>";
		  if($drow["reduction_type"] == "amount") $drow["reduction_type"] = "amt"; else $drow["reduction_type"] = "pct";
		  $prodline .=  "<td>".$drow["reduction_type"]."</td>"; 
		  if($drow["from"] == "0000-00-00 00:00:00") $drow["from"] = "";
		  else if(substr($drow["from"],11) == "00:00:00") $drow["from"] = substr($drow["from"],0,10);
		  $prodline .=  "<td>".$drow["from"]."</td>";
		  if($drow["to"] == "0000-00-00 00:00:00") $drow["to"] = ""; 
		  else if(substr($drow["to"],11) == "00:00:00") $drow["to"] = substr($drow["to"],0,10);
		  $prodline .=  "<td>".$drow["to"]."</td>";
		  if ($drow['reduction_type'] == "amt")
		  { if($reduction_tax == 1)
			  $newprice = $frompriceVAT - $drow['reduction'];
			else
			  $newprice = $frompriceVAT - ($drow['reduction']*(1+($datarow['rate']/100)));
		  }
		  else 
		    $newprice = $frompriceVAT*(1-($drow['reduction']/100));
		  $newpriceEX = (1/(($taxrates[$datarow['id_tax_rules_group']]/100) +1)) * $newprice;
	      $newprice = number_format($newprice,2, '.', '');
          $newpriceEX = number_format($newpriceEX,2, '.', '');
		  
		  $prodline .= '<td>'.$newpriceEX.'/ '.$newprice.'</td>';
		  $prodline .= "</tr>";
		}
		$prodline .= "</table></td>";
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
		{ $tmp .= "<b>".$frow["feature"]."</b>: ";
		  if($frow["custom"] == '1')
		    $tmp .= "<b>".$frow["fvvalue"]."</b><br>";
		  else
		    $tmp .= $frow["fvvalue"]."<br>";
		}
		if($tmp == "") 
		  $tmp="FE";
        $prodline .= '<td style="text-align:center"><a href="feature-edit.php?id_product='.$datarow["id_product"].'&id_lang='.$id_lang.'&id_shop='.$id_shop.'" target="_blank" style="text-decoration:none;  ">'.$tmp.'</a></td>';
	  }
      else if ($infofields[$i][0] == "image")
      { $iquery = "SELECT i.id_image,cover,legend FROM ". _DB_PREFIX_."image i";
		$iquery .= " LEFT JOIN ". _DB_PREFIX_."image_lang il ON i.id_image=il.id_image AND il.id_lang='".$id_lang."'";
		$iquery .= " WHERE id_product='".$datarow['id_product']."' ORDER BY position";
		$ires=dbquery($iquery);
		$id_image = 0;
		$imagelist = "";
		$first=0;
		while ($irow=mysqli_fetch_array($ires)) 
		{	if($irow['cover'] == 1)
			{ $id_image=$irow['id_image'];
			}
			$imagelist .= "|".$irow['id_image']."|".$irow['legend'];
		}
		$prodline .= "<td>".get_product_image($datarow["id_product"], $id_image,$imagelist)."</td>";
		mysqli_free_result($ires);
      }  /* end of image */	
	  else if ($infofields[$i][0] == "indexes")
	  { $prodline .= '<td>';
	    if($datarow['active'] == 0) 
		  $prodline .= 'not active';
		else if(($datarow['visibility'] != 'both') && ($datarow['visibility'] != 'search'))
		  $prodline .= 'visibility='.$datarow['visibility'];
		else if($datarow['indexed'] == 0) 
		  $prodline .= 'not indexed';
		else
		{ $iquery = "SELECT count(word) AS indexes FROM "._DB_PREFIX_."search_word sw";
		  $iquery .= " LEFT JOIN "._DB_PREFIX_."search_index si ON sw.id_word=si.id_word";
		  $iquery .= " WHERE sw.id_shop=".$id_shop." AND sw.id_lang=".$id_lang." AND si.id_product=".$datarow["id_product"];
		  $ires=dbquery($iquery);
		  $irow = mysqli_fetch_array($ires);
		  $prodline .= '<a href="prodwords.php?id_product='.$datarow["id_product"].'&id_shop='.$id_shop.'&id_lang='.$id_lang.'" target=_blank>'.$irow["indexes"].'</a>';
		}
		$prodline .= '</td>';
	  }
	  else if ($infofields[$i][0] == "name")
	  { // customized link. Prestashop code in getProductLink() in classes\Link.php that refers to classes\Dispatcher.php
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
			  $tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag AND t.id_lang='".$id_lang."'";
			  $tquery .= " WHERE pt.id_product='".$datarow['id_product']."' GROUP BY id_product";
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
	      $prodline .= "<td><a href='".get_base_uri().$langinsert.$produrl."' title='".$datarow['originalname']."' target='_blank' class='redname'>".$myvalue."</a></td>";
		}
		else
          $prodline .=  "<td><a href='".get_base_uri()."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang."' title='".$datarow['originalname']."' target='_blank' class='redname'>".$myvalue."</a></td>";
	  }  /* end of name */
	  
	  else if ($infofields[$i][0] == "out_of_stock")
	  { $prodline .=  '<td>';
		$myvalue = $datarow["out_of_stock"];
		if($myvalue == "0") $prodline .=  "Deny orders";
		else if($myvalue == "1") $prodline .=  "Allow orders";
		else $prodline .=  "Default";		
		$prodline .=  '</td>'; 
	  }		/* end of out_of_stock */
	  
      else if ($infofields[$i][0] == "pack_stock_type")
      { $prodline .=  "<td>".$packstocktypes[(int)$myvalue]."</td>";
      }  	/* end of pack_stock_type */
	  
	  else if ($infofields[$i][0] == "position")
	  { $prodline .=  '<td>'.$datarow["position"].'</td>'; 
	  }		/* end of position */
	  
	  else if ($infofields[$i][0] == "quantity")
	  { if($myvalue == "") $myvalue = "0"; /* handle cases when there are no entries in stock_available table */
		if($combination_count != 0)
            $prodline .=  '<td style="background-color:#FF8888"><a href="combi-edit.php?id_product='.$datarow['id_product'].'&id_shop='.$id_shop.'" target=_blank>'.$myvalue.'</a></td>';	 
		else if($datarow["depends_on_stock"] == "1")
          $prodline .=  '<td style="background-color:yellow">'.$myvalue.'</td>';	  
		else
            $prodline .=  "<td>".$myvalue."</td>";
	  }		/* end of quantity */
	  else if ($infofields[$i][0] == "redirect")
	  { $prodline .= '<td>'.$myvalue;
		if($myvalue != "404")
		  $prodline .= "<br>".$datarow["id_redirected"];
		$prodline .= '</td>';
	  }	
	  else if ($infofields[$i][0] == "reserved")
	  { if($combination_count == 0)
		{ $rquery = "SELECT reserved_quantity AS reserved FROM "._DB_PREFIX_."stock_available";
		  $rquery .= " WHERE id_product='".$datarow['id_product']."'";
		  $rres=dbquery($rquery);
		  $rrow=mysqli_fetch_array($rres);
		}
		else
		{ $rquery = "SELECT SUM(reserved_quantity) AS reserved FROM "._DB_PREFIX_."stock_available";
		  $rquery .= " WHERE id_product='".$datarow['id_product']."' AND id_product_attribute !=0";
		  $rres=dbquery($rquery);
		  $rrow=mysqli_fetch_array($rres);
		}
        $prodline .= "<td>".$rrow['reserved']."</td>";	  
	  }
	  else if ($infofields[$i][0] == "revenue")
      { $prodline .= "<td><a href onclick='return salesdetails(".$datarow['id_product'].")' title='show salesdetails'>".$datarow['revenue']."</a></td>";
      }	  	/* end of revenue */
	  else if ($infofields[$i][0] == "state")
	  { $prodline .=  "<td>";
		if($datarow["state"] == "0") $prodline .=  "STATE_TEMP";
		else if($datarow["state"] == "1") $prodline .=  "STATE_SAVED";
		else $prodline .=  "INVALID";
		$prodline .=  "</td>";
	  }
	  else if ($infofields[$i][0] == "shopz")
      { $prodline .=  "<td>";
		$squery = "SELECT GROUP_CONCAT(id_shop) AS shops FROM ". _DB_PREFIX_."product_shop";
	    $squery .= " WHERE id_product = '".$datarow['id_product']."' GROUP BY id_product";
		$sres=dbquery($squery);
		$srow=mysqli_fetch_array($sres);
		$prodline .=  $srow["shops"]."</td>";
      }	  	/* end of shops */
	  
	  else if ($infofields[$i][0] == "stockflags")
	  { $prodline .=  "<td srt='".$myvalue."' haswarehouses=";
	    $depends_on_stock = $datarow['depends_on_stock'];
		$advanced_stock_management = $datarow['advanced_stock_management'];
		if(($advanced_stock_management == 0) || ($depends_on_stock == 0))
		{ $squery = "SELECT id_product_attribute FROM ". _DB_PREFIX_."stock s";
	      $squery .= " LEFT JOIN ". _DB_PREFIX_."warehouse_shop ws on ws.id_warehouse=s.id_warehouse";
	      $squery .= " LEFT JOIN ". _DB_PREFIX_."warehouse w on ws.id_warehouse=w.id_warehouse";	  
	      $squery .= " WHERE s.id_product = '".$datarow['id_product']."' AND ws.id_shop ='".$id_shop."' AND w.deleted=0";
		  $sres=dbquery($squery);
		  if(mysqli_num_rows($sres) > 0)
			 $prodline .=  '1';
		  else $prodline .=  '0';
		}
		else 
		  $prodline .=  '0';
		$prodline .=  " >";
		$squery = "SELECT * FROM ". _DB_PREFIX_."pack WHERE id_product_pack='".$datarow['id_product']."'";
		$sres=dbquery($squery);
		if(mysqli_num_rows($sres) != 0)
		  $prodline .=  "For packs this field cannot be edited.<br>";
		if($advanced_stock_management == 0)
		  $prodline .=  $stockflagsarray[0];  // "Manual"
		else if($depends_on_stock == 0)
		  $prodline .=  $stockflagsarray[1];  // "Adv Stock Management"
		else
		  $prodline .=  $stockflagsarray[2];  // "ASM with Warehousing"
	    /* now we need to set a flag whether the product already has stock in warehouses set */
	    $prodline .=  "</td>";
	  }		/* end of stockflags */
	  else if ($infofields[$i][0] == "supplier")
      { $dquery = "SELECT id_supplier FROM ". _DB_PREFIX_."product WHERE id_product='".$datarow['id_product']."'";
		$dres=dbquery($dquery);
		$drow=mysqli_fetch_array($dres);
		$default_supplier = $drow["id_supplier"];
  
        $squery = "SELECT id_product_supplier,ps.id_supplier,id_product_attribute FROM ". _DB_PREFIX_."product_supplier ps";
	    $squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
		$squery .= " WHERE id_product='".$datarow['id_product']."' AND id_product_attribute=0";
		$squery .= " ORDER BY s.name";
		$sres=dbquery($squery);
	    $sups = array();
		while ($srow=mysqli_fetch_array($sres))
		    $sups[] = $srow["id_supplier"];

		$attrs = array();	
		if(1)
		{ $aquery = "SELECT id_product_attribute FROM ". _DB_PREFIX_."product_attribute WHERE id_product='".$datarow['id_product']."'";
		  $ares=dbquery($aquery);
		  while ($arow=mysqli_fetch_array($ares))
		    $attrs[] = $arow["id_product_attribute"];
		  $prodline .=  '<td>';
		  
		  $paquery = "SELECT pa.id_product_attribute, GROUP_CONCAT(CONCAT(gl.name,': ',l.name)) AS nameblock";
		  $paquery .= " FROM ". _DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product='".$datarow['id_product']."' GROUP BY pa.id_product_attribute ORDER BY pa.id_product_attribute";
		  $pares=dbquery($paquery);
		  
		  while ($parow=mysqli_fetch_array($pares))
		  { $prodline .=  '<table border=1>';
			$suppls = array();
			$squery = "SELECT ps.id_product_supplier,ps.id_supplier,s.name as suppliername, ps.id_product_attribute,product_supplier_reference AS reference,product_supplier_price_te AS supprice,c.id_currency,c.iso_code";
			$squery .= " FROM ". _DB_PREFIX_."product_supplier ps";
			$squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
			$squery .= " LEFT JOIN ". _DB_PREFIX_."currency c on c.id_currency=ps.id_currency";		  
			$squery .= " WHERE ps.id_product_attribute='".$parow['id_product_attribute']."' AND (ps.id_supplier != 0) ORDER BY suppliername";
		    $sres=dbquery($squery);
			while ($srow=mysqli_fetch_array($sres))
			{ $suppls[$srow["id_supplier"]] = array($srow["id_product_supplier"],$srow['reference'], $srow['supprice'], $srow['iso_code']);
			}
			$xx = 0;
			foreach($sups AS $sup)
			{ if(isset($suppls[$sup]))
			  { $prodline .=  "<tr title='".$sup."'>";
				if($sup == $default_supplier)
					$prodline .=  '<td class="defcat">'.$supplier_names[$sup].'</td>';
				else
					$prodline .=  "<td >".$supplier_names[$sup]."</td>";
				$prodline .=   "<td>".$suppls[$sup][1]."</td><td>".$suppls[$sup][2]."</td>";
				if($suppls[$sup][3] != "")
				  $prodline .=  "<td >".$suppls[$sup][3]."</td>";
			    else
				  $prodline .=  "<td >".$def_currency."</td>";
			  }
			  else 		/* this is the situation initially: when the supplier has just been added for the product */
			  { $prodline .=  "<tr title='".$sup."'>"; 
				if($sup == $default_supplier)
					$prodline .=  '<td class="defcat">'.$supplier_names[$sup].'</td>';
				else
					$prodline .=  "<td >".$supplier_names[$sup]."</td>";
				$prodline .=  "<td></td><td>0.000000</td><td>".$def_currency."</td>";
			  }
			  if($xx++ == 0)
			    $prodline .=  '<td rowspan="'.sizeof($sups).'">'.$parow["nameblock"].'</td>';
			  $prodline .=  "</tr>";
			}
		    $prodline .=  "</table>";
		  }
		  mysqli_free_result($sres);
		  mysqli_free_result($pares);
		}
		$prodline .=  "</td>";
      }		/* end of supplier */
	  
	  else if ($infofields[$i][0] == "tags")
      { $tquery = "SELECT pt.id_tag,name FROM ". _DB_PREFIX_."product_tag pt";
		$tquery .= " LEFT JOIN ". _DB_PREFIX_."tag t ON pt.id_tag=t.id_tag AND t.id_lang='".$id_lang."'";
	    $tquery .= " WHERE pt.id_product='".$datarow['id_product']."'";
		$tres=dbquery($tquery);
		$idx = 0;
		$prodline .=  "<td>";
		while ($trow=mysqli_fetch_array($tres)) 
		{ if($idx++ > 0) $prodline .=  "<br/>";
		  $prodline .=  "<nobr>".$trow["name"]."</nobr>";
		}
		$prodline .=  "</td>";
		mysqli_free_result($tres);
      }	  /* end of tags */
	  
      else if ($infofields[$i][0] == "VAT")
      { $sorttxt = "idx='".$datarow['id_tax_rules_group']."'";
		$prodline .=  "<td ".$sorttxt.">".(float)$taxrates[$datarow['id_tax_rules_group']]."</td>";
      }		/* end of VAT */
	  
	  else if ($infofields[$i][0] == "virtualp")	  
	  {	$bgcolor = $visvirtual= $visfile = '';
	    if($datarow['is_virtual'] == 0)
		  $visvirtual = ' style="display:none;"';
	    if($datarow['filename'] == "")
		  $visfile = ' style="display:none;"';
		$prodline .=  "<td ".$bgcolor."><table><tr><td>On</td><td".$visvirtual.">File</td><td style='display:none'>Name</td><td"
		.$visfile.">exp_date</td><td".$visfile.">nb_days</td><td".$visfile.">nb_downloads</td></tr>";
		$prodline .=  "<tr><td>".$datarow['is_virtual']."</td><td".$visvirtual.">";
		if($datarow['filename'] != "") 
	    { if(!file_exists($download_dir."/".$datarow["filename"])) $prodline .=  " <b>file missing</b>";
		  $prodline .=  "<a class='attachlink' title='".$datarow['display_filename']."' href='downfile.php?filename=".$datarow["display_filename"]."&filecode=".$datarow["filename"]."&download_dir=".$download_dir."&mime=application/zip' target=_blank'>".$datarow['display_filename']."</a>";
		}
		$prodline .=  "</td><td style='display:none'>".$datarow["display_filename"];
		$prodline .=  "</td><td".$visfile.">".$datarow["date_expiration"]."</td><td".$visfile.">".$datarow["nb_days_accessible"]."</td>";
		$prodline .=  "</td><td".$visfile.">".$datarow["nb_downloadable"]."</td></tr></table></td>";	  
	  }		/* end of virtualp */
	  
	  else if ($infofields[$i][0] == "warehousing")
	  { $prodline .=  "<td>";
	    if(1)
		{ $aquery = "SELECT id_product_attribute FROM ". _DB_PREFIX_."product_attribute WHERE id_product='".$datarow['id_product']."'";
		  $ares=dbquery($aquery);
		  while ($arow=mysqli_fetch_array($ares))
		    $attrs[] = $arow["id_product_attribute"];
		  
		  $paquery = "SELECT pa.id_product_attribute, GROUP_CONCAT(CONCAT(gl.name,': ',l.name)) AS nameblock from ". _DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product='".$datarow['id_product']."' GROUP BY pa.id_product_attribute ORDER BY pa.id_product_attribute";
		  $pares=dbquery($paquery);
		  
		  while ($parow=mysqli_fetch_array($pares))
		  { $prodline .=  '<table border=1  title="'.$parow["nameblock"].'">';
		    $wquery = "SELECT w.id_warehouse, w.name AS whname, physical_quantity, usable_quantity, price_te, location";
		    $wquery .= " FROM ". _DB_PREFIX_."warehouse w";
	        $wquery .= " LEFT JOIN ". _DB_PREFIX_."stock st on w.id_warehouse=st.id_warehouse AND st.id_product='".$datarow['id_product']."' AND st.id_product_attribute='".$parow['id_product_attribute']."'";
	        $wquery .= " LEFT JOIN ". _DB_PREFIX_."warehouse_product_location wpl on w.id_warehouse=wpl.id_warehouse AND wpl.id_product='".$datarow['id_product']."' AND wpl.id_product_attribute='".$parow['id_product_attribute']."'";		  
		    $wquery .= " WHERE st.id_warehouse IS NOT NULL OR wpl.id_warehouse IS NOT NULL";
		    $wquery .= " ORDER BY w.name";
			$wres=dbquery($wquery);
			$size = mysqli_num_rows($wres);
			
			$xx = 0;
			while ($wrow=mysqli_fetch_array($wres))
			{ $prodline .=  "<tr><td>".$wrow["whname"]."</td><td>".$wrow["location"]."</td>";
			  $prodline .=  "<td>".$wrow["physical_quantity"]."</td><td>".$wrow["usable_quantity"]."</td>";
			  if($wrow["price_te"] == "")
				$prodline .=  "<td></td>";
			  else
				$prodline .=  "<td>".number_format($wrow["price_te"],2)."</td>";
			  if($xx++ == 0)
			    $prodline .=  '<td rowspan="'.$size.'">'.$parow["nameblock"].'</td>';
			  $prodline .=  "</tr>";
			}
		    $prodline .=  "</table>";
		  }
		  mysqli_free_result($wres);
		  mysqli_free_result($pares);
		}
		$prodline .=  "</td>";
	  }		/* end of warehousing */

	  else if($infofields[$i][6] == 1)
      { $sorttxt = "srt='".str_replace("'", "\'",$myvalue)."'";
        $prodline .=  "<td ".$sorttxt.">".$myvalue."</td>";
      }
      else
         $prodline .=  "<td>".$myvalue."</td>";
    }

    foreach($featurelist AS $key => $feature)
    {
	  $fquery = "SELECT fv.custom,fl.value, fp.id_feature_value FROM ". _DB_PREFIX_."feature_product fp";
	  $fquery.=" LEFT JOIN ". _DB_PREFIX_."feature_value fv ON fp.id_feature_value=fv.id_feature_value";
	  $fquery.=" LEFT JOIN ". _DB_PREFIX_."feature_value_lang fl ON fp.id_feature_value=fl.id_feature_value AND fl.id_lang='".$id_lang."'";
	  $fquery.=" WHERE fp.id_product='".$datarow['id_product']."' AND fv.id_feature='".$key."'";
	  $fres=dbquery($fquery);
	  $prodline .=  "<td>";
	  $first = true;
	  while ($frow=mysqli_fetch_array($fres))
	  { if($first) $first = false; else $prodline .=  "<br>";
	    if(version_compare(_PS_VERSION_ , "1.7.3", "<"))
		{ if($frow['custom'] == "0")
	  	    $prodline .=  "<b>".$frow['value']."</b>";
		  else // custom = 1
	  	    $prodline .=  $frow['value'];
		}
	    else
		{ if($frow['custom'] == "0")
	        $prodline .=  '<b title="'.$frow['id_feature_value'].'">'.$frow['value'].'</b>';
		  else // custom = 1
	  	    $prodline .=  '<span title="'.$frow['id_feature_value'].'">'.$frow['value']."</span>";
		}
	  }
	  $prodline .=  "</td>";
	}
	
/* 22. the main table: the combination data   */
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
  while (($row=mysqli_fetch_assoc($ares)) && ($rowcnt < $maxrows))
  { /* the first td will only be submitted when price or priceVAT was editable. Otherwise it will be deleted. The editable price field is called showprice */
    echo '<tr
><td id="trid'.$x.'" changed="0" data-prodprice="'.$prodprice.'" data-vatrate="'.$VAT_rate.'">';
    echo '<input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide row '.$x.' from display" />';
 	echo '<input type=hidden name="price'.$x.'" value="'.$row['price'].'" class="remprice"></td>';   
	echo "<td><a href='product-solo.php?id_product=".$datarow['id_product']."&id_lang=".$id_lang."&id_shop=".$id_shop."' title='".$datarow['originalname']."' target='_blank'>".addspaces($datarow['id_product'])."</a>";
	echo "<br><input type=hidden name=id_product_attribute".$x." value='".$row['id_product_attribute']."' id=id_product_attribute".$x.">";
	echo '<a href="combi-edit.php?id_product='.$datarow['id_product'].'&id_shop='.$id_shop.'" target="_blank">'.$row['id_product_attribute'].'</a>';
	echo '<input type=hidden name=id_product'.$x.' value="'.$datarow['id_product'].'"></td>';
	echo $prodline;
	
    for($j=0; $j< sizeof($combifields); $j++)
	{   if($combifields[$j][2]==HIDE) $vis='style="display:none"'; else $vis="";
		if($combifields[$j][0] == "combination")
		{ $paquery = "SELECT GROUP_CONCAT(CONCAT('<b>',gl.name,':</b> ',l.name) SEPARATOR '=') AS nameblock, GROUP_CONCAT(CONCAT(gl.id_attribute_group,':',a.id_attribute) SEPARATOR '=') AS idblock from ". _DB_PREFIX_."product_attribute pa";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
		  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
		  $paquery .= " WHERE pa.id_product_attribute='".$row['id_product_attribute']."' GROUP BY pa.id_product_attribute";
		  $paquery .= " ORDER BY gl.name, l.name";
		  $pares=dbquery($paquery);
		  $parow = mysqli_fetch_assoc($pares);
		  if(is_null($parow['nameblock']))
			  echo '<td></td><td></td>';
		  else
		  { $labels = explode("=", $parow['nameblock']);
		    echo "<td ".$vis.">";
		    foreach($labels AS $label)
		      echo $label."<br>";
		    echo "</td>";
		    echo '<td style="display: none">';
		    $ids = explode("=", $parow['idblock']);
		    foreach($ids AS $id)
		      echo $id."<br>";
		    echo '</td>';
		  }
		}
		else if($combifields[$j][0] == "ids")
		{/* handled under name */}
		else if($combifields[$j][0] == "price")
		  echo "<td ".$vis.">".$row['price']."</td>";
		else if($combifields[$j][0] == "priceVAT")
		  echo "<td ".$vis.">".number_format(($row['price']*(100+$VAT_rate)/100),2)."</td>";
		else if($combifields[$j][0] == "weight")
		  echo "<td ".$vis.">".$row['weight']."</td>";
		else if($combifields[$j][0] == "wholesale_price")
		  echo "<td ".$vis.">".$row['wholesale_price']."</td>";
		else if($combifields[$j][0] == "ecotax")
		  echo "<td ".$vis.">".$row['ecotax']."</td>";
		else if($combifields[$j][0] == "isbn")
		  echo "<td ".$vis.">".$row['isbn']."</td>";
		else if($combifields[$j][0] == "unit_price_impact")
		  echo "<td ".$vis.">".$row['unit_price_impact']."</td>";
		else if($combifields[$j][0] == "default_on")
		  echo "<td ".$vis.">".$row['default_on']."</td>";
		else if($combifields[$j][0] == "minimal_quantity")
		  echo "<td ".$vis.">".$row['minimal_quantity']."</td>";
		else if($combifields[$j][0] == "available_date")
		  echo "<td ".$vis.">".$row['available_date']."</td>";
		else if($combifields[$j][0] == "quantity")
		{ if($datarow["depends_on_stock"] == "1")
            echo '<td style="background-color:yellow" ".$vis.">'.$row['quantity'].'</td>';	
		  else
		    echo "<td ".$vis.">".$row['quantity']."</td>";
		}
		/* below the ps_product_attribute fields */
		else if($combifields[$j][0] == "reference")
		  echo "<td ".$vis.">".$row['reference']."</td>";
		else if($combifields[$j][0] == "supplRef")
		  echo "<td ".$vis.">".$row['supplier_reference']."</td>";
		else if($combifields[$j][0] == "location")
		  echo "<td ".$vis.">".$row['location']."</td>";
		else if($combifields[$j][0] == "ean")
		  echo "<td ".$vis.">".$row['ean13']."</td>";
		else if($combifields[$j][0] == "upc")
		  echo "<td ".$vis.">".$row['upc']."</td>";
		else if($combifields[$j][0] == "quantity")
		  echo "<td ".$vis.">".$row['quantity']."</td>";
		else if($combifields[$j][0] == "ls_alert")
		  echo "<td ".$vis.">".$row['low_stock_alert']."</td>";	  
		else if($combifields[$j][0] == "ls_threshold")
		  echo "<td ".$vis.">".$row['low_stock_threshold']."</td>";	
	    else if($combifields[$j][0] == "discount")
        { /* first check whether there is a discount for the product as a whole. If so color the field background */
		  $dquery = "SELECT * FROM ". _DB_PREFIX_."specific_price";		
	      $dquery .= " WHERE id_product='".$row['id_product']."' AND id_product_attribute='0'";
		  $dres=dbquery($dquery);
		  $bg = "";
		  if(mysqli_num_rows($dres) > 0)
			 $bg = 'style="background-color: #7FFFD4"';
		  /* we can leave $vis here out as the field will always initially be visible */
		  echo "<td ".$bg."><table border=1 id='discount".$x."'>";
	
		  $dquery = "SELECT sp.*, cu.iso_code AS currency";
		  $dquery .= " FROM ". _DB_PREFIX_."specific_price sp";
		  $dquery.=" left join ". _DB_PREFIX_."currency cu on sp.id_currency=cu.id_currency";		
	      $dquery .= " WHERE sp.id_product='".$row['id_product']."' AND sp.id_product_attribute='".$row['id_product_attribute']."'";
		  $dres=dbquery($dquery);

		  while ($drow=mysqli_fetch_array($dres)) 
		  { $bgcolor = "";
		    if($drow["id_specific_price_rule"] != 0)
			  $bgcolor = ' style="background-color:#dddddd"';
		    echo '<tr specid='.$drow["id_specific_price"].' rule='.$drow["id_specific_price_rule"].$bgcolor.'>';
/* 					0			1				2		3		4			5		6			7			8			9	 			10			11	  12	*/
/* discount fields: shop, currency, country, group, id_customer, price, from_quantity, reduction, reduction_tax, reduction_type, from, to */
		   if($drow["id_shop"] == "0") $drow["id_shop"] = "";
		    echo "<td>".$drow["id_shop"]."</td>";
		    if($drow["id_product_attribute"] == "0") $drow["id_product_attribute"] = "";
		    echo "<td style=hidden>".$drow["id_product_attribute"]."</td>";
		    echo "<td>".$drow["currency"]."</td>";
		    echo "<td>".$drow["id_country"]."</td>";
		    echo "<td>".$drow["id_group"]."</td>";

		    if($drow["id_customer"] == "0") $drow["id_customer"] = "";
		    echo "<td>".$drow["id_customer"]."</td>";
		    if($drow["price"] == -1)
		    { $frompriceVAT = number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '');
		      $fromprice = $datarow['price'];
			  $drow["price"] = "";
		    }
		    else /* the prices mentioned here are excl VAT */
		    { $frompriceVAT = (($datarow['rate']/100) +1) * $drow['price'];
		      $drow["price"] = $drow["price"] * 1; /* remove trailing zeroes */
		    }
		    echo "<td>".$drow["price"]."</td>";
		    echo "<td style='background-color:#FFFF77'>".$drow["from_quantity"]."</td>";
		    if($drow["reduction_type"] == "percentage")
			  $drow["reduction"] = $drow["reduction"] * 100;
		    else 
		      $drow["reduction"] = $drow["reduction"] * 1;
		    echo "<td>".$drow["reduction"]."</td>";
		    $reduction_tax = "1";
		    if (version_compare(_PS_VERSION_ , "1.6.0.11", ">="))
		    { echo "<td>".$drow["reduction_tax"]."</td>"; /* 0=excl; 1=incl before 1.6.0.11 there was only incl */
			  $reduction_tax = $drow["reduction_tax"];
		    }
		    else 
		      echo "<td></td>";
		    if($drow["reduction_type"] == "amount") $drow["reduction_type"] = "amt"; else $drow["reduction_type"] = "pct";
		    echo "<td>".$drow["reduction_type"]."</td>"; 
		    if($drow["from"] == "0000-00-00 00:00:00") $drow["from"] = "";
		    else if(substr($drow["from"],11) == "00:00:00") $drow["from"] = substr($drow["from"],0,10);
		    echo "<td>".$drow["from"]."</td>";
		    if($drow["to"] == "0000-00-00 00:00:00") $drow["to"] = ""; 
		    else if(substr($drow["to"],11) == "00:00:00") $drow["to"] = substr($drow["to"],0,10);
		    echo "<td>".$drow["to"]."</td>";
		    if ($drow['reduction_type'] == "amt")
		    { if($reduction_tax == 1)
			    $newprice = $frompriceVAT - $drow['reduction'];
		   	  else
			    $newprice = $frompriceVAT - ($drow['reduction']*(1+($datarow['rate']/100)));
		    }
		    else 
		      $newprice = $frompriceVAT*(1-($drow['reduction']/100));
		    $newpriceEX = (1/(($datarow['rate']/100) +1)) * $newprice;
	        $newprice = number_format($newprice,2, '.', '');
            $newpriceEX = number_format($newpriceEX,2, '.', '');
		  
		    echo '<td>'.$newpriceEX.'/ '.$newprice.'</td>';
		    echo "</tr>";
		  }
		  echo "</table></td>";
		  mysqli_free_result($dres);
        }  /* end of discount */
		/* image */
		else if($combifields[$j][0] == "image")
		{ if(($row["id_image"] == "") || ($row["id_image"] == "0"))
		    echo "<td ".$vis.">X</td>";
		  else
		  { $images = explode(",",$row["images"]);
		    echo "<td ".$vis.">";
		    foreach($images AS $id_image)
			{ get_image_extension($datarow['id_product'], $id_image, "product");
			  $legacy_images = get_configuration_value('PS_LEGACY_IMAGES');
			  if($legacy_images)
				 $imglink = $triplepath.'img/p/'.id_product.'-'.$id_image; 
			  else
				 $imglink = $triplepath.'img/p'.getpath($id_image).'/'.$id_image;
			  echo "<a href='".$imglink.".jpg' title='".$row['legend']."' target='_blank'><img src='".$imglink.$selected_img_extension."'></a>";
			}
			echo "</td>";
		  }
		}
		else if($combifields[$j][0] == "buyers")
	    { echo "<td>".$row['buyercount']."</td>";
		  $stattotals["buyers"] += $row['buyercount'];
	    }
	    else if($combifields[$j][0] == "orders")
	    { echo "<td>".$row['ordercount']."</td>";
		  $stattotals["orders"] += $row['ordercount'];
	    }
	    else if($combifields[$j][0] == "revenue")		  
	    { echo "<td><a href onclick='return salesdetails(".$datarow['id_product'].",".$row['id_product_attribute'].")' title='show salesdetails'>".$row['revenue']."</a></td>";
  		  $stattotals["revenue"] += $row['revenue'];
	    }
	    else if($combifields[$j][0] == "salescnt")	  
	    { echo "<td>".$row['salescount']."</td>";
  		  $stattotals["salescnt"] += $row['salescount'];
	    }
	    else if($combifields[$j][0] == "suppliers")	  
	    { $dquery = "SELECT id_supplier FROM ". _DB_PREFIX_."product WHERE id_product='".  $datarow['id_product']."'";
		  $dres=dbquery($dquery);
		  $drow=mysqli_fetch_array($dres);
		  $default_supplier = $drow["id_supplier"];
		  
          $squery = "SELECT DISTINCT(ps.id_supplier), c.name AS currency";
		  $squery .= " FROM ". _DB_PREFIX_."product_supplier ps";
	      $squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
		  $squery .= " LEFT JOIN ". _DB_PREFIX_."currency c on c.id_currency=ps.id_currency";
		  $squery .= " WHERE id_product='".$datarow['id_product']."'";
		  $squery .= " GROUP BY ps.id_supplier ORDER BY s.name";
		  $sres=dbquery($squery);
	      $sups = $supcurrencies = array();
		  while ($srow=mysqli_fetch_array($sres))
		  { $sups[] = $srow["id_supplier"];
			$supcurrencies[$srow["id_supplier"]] = $srow["currency"];
		  }
  
		  $attrs = array();	
/* Prestashop makes ps_product_supplier entries in two steps. 
 * In the first step you only assign the supplier. The reference field 
 * will then stay empty and the price and id_currency fields will 
 *	become zero. Only in the second step a currency is assigned. 
 */
		  echo '<td sups="'.implode(",",$sups).'">';
		  echo '<table border=1 class="supplier" id="suppliers'.$x.'" title="">';
		  $squery = "SELECT ps.id_product_supplier,s.id_supplier,ps.id_product_attribute,product_supplier_reference AS reference,product_supplier_price_te AS supprice,c.id_currency,c.iso_code";
		  $squery .= " FROM ". _DB_PREFIX_."product_supplier ps";
		  $squery .= " LEFT JOIN ". _DB_PREFIX_."supplier s on s.id_supplier=ps.id_supplier";
		  $squery .= " LEFT JOIN ". _DB_PREFIX_."currency c on c.id_currency=ps.id_currency";		  
		  $squery .= " WHERE id_product='".$datarow['id_product']."' AND id_product_attribute='".$row['id_product_attribute']."' AND (ps.id_supplier != 0) ORDER BY s.name";
		  $sres=dbquery($squery);
		  $rowcount = mysqli_num_rows($sres);
		  $xx=0;
		  $foundsups = array();
		  while ($srow=mysqli_fetch_array($sres))
		  { echo "<tr title='".$srow["id_supplier"]."'>";
			if($srow['id_supplier'] == $default_supplier)
				echo "<td class='defcat'>".$supplier_names[$srow['id_supplier']]."</td>";
			else
				echo "<td >".$supplier_names[$srow['id_supplier']]."</td>";		
			echo "<td>".$srow['reference']."</td><td>".$srow['supprice']."</td>";
			if($srow['iso_code'] != "")
			  echo "<td >".$srow['iso_code']."</td>";
			else
			  echo "<td >".$def_currency."</td>";
			echo "</tr>";
			$foundsups[] = $srow['id_supplier'];
		  }
		  $diff = array_diff($sups, $foundsups);
		  foreach ($diff AS $sup) /* handle missing supplier entries */
		  { echo "<tr title='".$sup."'>";
			if($sup == $default_supplier)
				echo "<td class='defcat'>".$supplier_names[$sup]."</td>";
			else
				echo "<td >".$supplier_names[$sup]."</td>";		
			echo "<td></td><td>0.000000</td>";
			echo "<td >".$supcurrencies[$sup]."</td>"; 
			echo "</tr>";
		  }
		  echo "</table>";
		  mysqli_free_result($sres);
        }		/* end of supplier */
		else 
		{ echo "<td ".$vis.">".$row[$combifields[$j][0]]."</td>";
		}
	}

    echo '<td><img src="enter.png" title="submit row '.$x.'" onclick="RowSubmit(this)"></td>';
    $x++;
	$rowcnt++;
	echo "</tr>";
  }
  }
	echo '<script>numrecs='.$x.';
	var tmp=document.getElementById("countlist");
	tmp.innerHTML = "Showing <b>'.$prodcount.'</b> (of '.$numrecs2.') products and <b>'.$rowcnt.'</b> combinations";</script>';
  
  if(mysqli_num_rows($res) == 0)
	echo "<strong>products not found</strong>";
  echo '</table><input type=hidden name=reccount value="'.$x.'"></form></div>';
  
  if(in_array("salescnt", $input["fields"]) || in_array("revenue", $input["fields"])
    || in_array("orders", $input["fields"]) || in_array("buyers", $input["fields"]))
  { echo '<table class=triplemain><td colspan=2 style="text-align:center">Totals</td>';
    for($i=0; $i< sizeof($combifields); $i++)
	{ if (in_array($combifields[$i][0], $statfields))
	    echo '<tr><td>'.$combifields[$i][0].'</td><td>'.$stattotals[$combifields[$i][0]].'</td></tr>';
	}
	echo '</table>';
  }
  
  echo '<form style="display:inline"><input id="attrgatherer"><input type=button value="gather product_attribute id\'s" onclick="gather_attr_ids(); return false"></form>';
  
  echo '<form style="display:inline"><input id="gatherer"><input type=button value="gather product id\'s" onclick="gather_prod_ids(); return false"></form>';
  
  echo '<div style="display:block;">
  ';
  if($prestools_settings["avoid_iframes"])
    echo '<form name=rowform action="combi-proc.php" method=post target=_blank>';
  else
    echo '<form name=rowform action="combi-proc.php" method=post target=tank>';
  echo '<table id=subtable></table>';
  echo '<input type=hidden name=submittedrow><input type=hidden name=id_lang value="'.$id_lang.'">';
  echo '<input type=hidden name=allshops><input type=hidden name=reccount value="1">';
  echo '<input type=hidden name=id_shop value="'.$id_shop.'"><input type=hidden name=verbose>';
  echo '<input type=hidden name=featuresset></form></div>';

  include "footer1.php";
  echo '</body></html>';


$product_list = array();
function get_product_name($id)
{ global $product_list,$id_lang;
  if(isset($product_list[$id]))
    return $product_list[$id];
  $query = "select name from ". _DB_PREFIX_."product_lang WHERE id_product='".$id."' AND id_lang='".$id_lang."'";
  $res = dbquery($query);
  $row=mysqli_fetch_array($res);
  $product_list[$id] = $row["name"];
  return $row["name"];
}

/* display combisearch fields */
function print_combisearch($x)
{ global $comparer_values, $combifields, $input, $conn;
  if(isset($input['combisearch_fld'.$x]))
  { $combisearch_txt = trim(mysqli_real_escape_string($conn,$input['combisearch_txt'.$x]));
    $combisearch_cmp = trim(mysqli_real_escape_string($conn,$input['combisearch_cmp'.$x]));
    $combisearch_fld = trim(mysqli_real_escape_string($conn,$input['combisearch_fld'.$x])); 
  }
  else  
	$combisearch_txt = $combisearch_cmp = $combisearch_fld = '';  
  
  echo '<nobr>Find <input name="combisearch_txt'.$x.'" type="text" size="8" value="'.str_replace('"','&quot;',$combisearch_txt).'">
<select name="combisearch_cmp'.$x.'" class="comparer">';
  foreach($comparer_values AS $key => $value)
  { $selected = "";
    if(isset($input["combisearch_cmp".$x]) && ($key == $input["combisearch_cmp".$x]))
	  $selected = " selected";
    echo '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
  }
  echo '</select></nobr><br><select name="combisearch_fld'.$x.'" onchange="change_sfield(this);">';
  echo '<option value="0">Select combination field</option>';
  foreach($combifields AS $combifield)
  { $selected = "";
    if(isset($input["combisearch_fld".$x]) && ($combifield[5] == $input["combisearch_fld".$x]))
	  $selected = " selected";
    if($combifield[4] == '1')  /* searchable */
	  echo '<option value="'.$combifield[5].'" '.$selected.'>'.$combifield[0].'</option>';
  }
  echo '</select>';
}

function print_compareunit($x)
{ global $comparer_values, $sortfields, $input, $id_lang, $combifields;
  if(isset($input["konda_fld".$x]))
  { $konda_fld = $input["konda_fld".$x];
	$kondb_fld = $input["kondb_fld".$x];
    $kond_cmp = $input["kond_cmp".$x];
  }
  else
	$konda_fld = $kondb_fld = $kond_cmp = "0";  
  
  echo '<td><select name="konda_fld'.$x.'" style="width:10em"><option value=0>select a field</option>';
  $found = false;
  foreach($combifields AS $combifield)
  { $selected = "";
	if($konda_fld == $combifield[5])
	{ $selected = "selected";
	}
    echo "<option value='".$combifield[5]."' ".$selected.">".$combifield[0]."</option>"; 
  }
  echo '</select> ';
  
  echo '<select name="kond_cmp'.$x.'" class="comparer">';
  $found = false;
  foreach($comparer_values AS $key => $value)
  { $selected = "";
    if($key == $kond_cmp)
	{ $selected = " selected";
	}
    echo '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
  }
  echo "</select></nobr>";

  echo ' <select name="kondb_fld'.$x.'" style="width:10em"><option value=0>select a field</option>';
  $found = false;
  foreach($combifields AS $combifield)
  { $selected = "";
	if($kondb_fld == $combifield[5])
	{ $selected = "selected";
	}
    echo "<option value='".$combifield[5]."' ".$selected.">".$combifield[0]."</option>"; 
  }
  echo '</select> ';
  if(version_compare(_PS_VERSION_ , "1.7.3", ">="))
    echo '<input type=button onclick="setStocker(); return false;" value="Stocker">';
  echo '</td>';
}

