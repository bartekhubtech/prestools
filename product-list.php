<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
$input = $_GET; 
if(!isset($input['search_txt1'])) $input['search_txt1'] = "";
$search_txt1 = mysqli_real_escape_string($conn,$input['search_txt1']);
if(!isset($input['search_txt2'])) $input['search_txt2'] = "";
$search_txt2 = mysqli_real_escape_string($conn,$input['search_txt2']);
if(!isset($input['search_cmp1'])) $input['search_cmp1'] = "in";
if(!isset($input['search_cmp2'])) $input['search_cmp2'] = "in";
if(!isset($input['search_fld1']) || ($input['search_fld1'] == "")) $input['search_fld1'] = "main fields";
$search_fld1 = $input['search_fld1'];
if(!isset($input['search_fld2']) || ($input['search_fld2'] == "")) $input['search_fld2'] = "main fields";
$search_fld2 = $input['search_fld2'];
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
if(!isset($input['numrecs'])) $input['numrecs']="100";
if(!isset($input['id_category'])) {$id_category=0;} else {$id_category = intval($input['id_category']);}
if(!isset($input['id_shop'])) $input['id_shop']="1";
$id_shop = intval($input["id_shop"]);
if(!isset($input['startdate'])) $input['startdate']="";
if(!isset($input['enddate'])) $input['enddate']="";
if((!isset($input['rising'])) || ($input['rising'] == "ASC")) {$rising = "ASC";} else {$rising = "DESC";}

if(!isset($input['order']))
{ if($id_category == 0) {$input['order']="id_product";} else {$input['order']="position";}
}
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['active'])) {$input['active']="";}
if(!isset($input['imgformat'])) {$input['imgformat']="";}

  if(empty($input['fields'])) // if not set, set default set of active fields
    $input['fields'] = $prestools_settings["default_product_fields"]; /* this is set in settings1.php */
  $infofields = array();
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
   "aShipCost" => array("aShipCost",null, "additional_shipping_cost", DISPLAY, 0, LEFT, null, INPUT, "ps.additional_shipping_cost"),
   "attachmnts" => array("attachmnts",null, "attachmnts", DISPLAY, 0, LEFT, null, INPUT, ""), 
   "available_now" => array("available_now",null, "available_now", DISPLAY, 0, LEFT, null, INPUT, "pl.available_now"),
   "available_later" => array("available_later",null, "available_later", DISPLAY, 0, LEFT, null, INPUT, "pl.available_later"), 
   "available_date" => array("available_date",null, "available_date", DISPLAY, 0, LEFT, null, BINARY, "ps.available_date"),
   /* available combines two of Prestashop's datafields: available_for_order and show_price */
   "availorder" => array("availorder",null, "available_for_order", DISPLAY, 0, LEFT, null, BINARY, "ps.available_for_order"),
   "carrier" => array("carrier",null, "carrier", DISPLAY, 0, LEFT, null, DROPDOWN, "cr.name"),
   "category" => array("category",null, "id_category_default", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.id_category_default"),
   "combinations" => array("combinations",null, "combinations", DISPLAY, 0, LEFT, 0, 0, ""),
   "condition" => array("condition",null, "condition", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.condition"),
   "date_add" => array("date_add",null, "date_add", DISPLAY, 0, LEFT, null, BINARY, "ps.date_add"),  
   "date_upd" => array("date_upd",null, "date_upd", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ps.date_upd"),
   "description" => array("description",null, "description", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.description"),
   "description_short" => array("description_short",null, "description_short", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.description_short"),
   "discount" => array("discount",null, "discount", DISPLAY, 0, LEFT, null, INPUT, "discount"),
   "ean" => array("ean",null, "ean13", DISPLAY, 200, LEFT, null, INPUT, "p.ean13"),
   "ecotax" => array("ecotax",null, "ecotax", DISPLAY, 200, LEFT, null, INPUT, "ps.ecotax"),
   "isbn" => array("isbn",null, "isbn", DISPLAY, 0, LEFT, null, INPUT, "p.isbn"),
   "id_product" => array("id_product",array("id","id","id"), "id_product", DISPLAY, 0, RIGHT, null,NOT_EDITABLE, "p.id_product"),
   "image" => array("image",null, "name", DISPLAY, 0, LEFT, 0, EDIT_BTN, ""), // name here is a dummy that is not used
   "link_rewrite" => array("link_rewrite",null, "link_rewrite", DISPLAY, 0, LEFT, null, INPUT, "pl.link_rewrite"),
   "manufacturer" => array("manufacturer",null, "manufacturer", DISPLAY, 0, LEFT, null, DROPDOWN, "m.name"),
   "meta_description" => array("meta_description",null, "meta_description", DISPLAY, 0, LEFT, null, TEXTAREA, "pl.meta_description"),
   "meta_keywords" => array("meta_keywords",null, "meta_keywords", DISPLAY, 0, RIGHT, null, TEXTAREA, "pl.meta_keywords"),
   "meta_title" => array("meta_title",null, "meta_title", DISPLAY, 0, LEFT, null, INPUT, "pl.meta_title"),
   "minimal_quantity" => array("minimal_quantity",null, "minimal_quantity", DISPLAY, 0, LEFT, null, INPUT, "ps.minimal_quantity"),
   "name" => array("name",null, "name", DISPLAY, 0, LEFT, null, INPUT, "pl.name"),
   "online_only" => array("online_only",null, "online_only", DISPLAY, 0, LEFT, null, BINARY, "ps.online_only"),
   "on_sale" => array("on_sale",null, "on_sale", DISPLAY, 0, LEFT, null, BINARY, "ps.on_sale"),
   "out_of_stock" => array("out_of_stock",null, "out_of_stock", DISPLAY, 0, LEFT, null, DROPDOWN, "s.out_of_stock"),
   "pack_stock_type" => array("pack_stock_type",null, "pack_stock_type", DISPLAY, 0, LEFT, null, DROPDOWN, "ps.pack_stock_type"),
   "position" => array("position",null, "position", DISPLAY, 0, RIGHT, null, NOT_EDITABLE, "cp.position"),  
   "price" => array("price",null, "price", DISPLAY, 200, LEFT, null, INPUT, "ps.price"),
   "priceVAT" => array("priceVAT",null, "priceVAT", DISPLAY, 0, LEFT, null, INPUT, "priceVAT"),
   "quantity" => array("quantity",null, "quantity", DISPLAY, 0, LEFT, null, TEXTAREA, "s.quantity"),
   "reference" => array("reference",null, "reference", DISPLAY, 200, LEFT, null, INPUT, "p.reference"),
   /* stockflags combines two of Prestashop's datafields: depends_on_stock and advanced_stock_management */
   "shipdepth" => array("shipdepth",null, "depth", DISPLAY, 0, LEFT, null, INPUT, "p.depth"), 
   "shipheight" => array("shipheight",null, "height", DISPLAY, 0, LEFT, null, INPUT, "p.height"),
   "shipweight" => array("shipweight",null, "weight", DISPLAY, 0, LEFT, null, INPUT, "p.weight"),
   "shipwidth" => array("shipwidth",null, "width", DISPLAY, 0, LEFT, null, INPUT, "p.width"),
   "shops" => array("shops",null, "id_shop", DISPLAY, 0, LEFT, null, BINARY, "ps.id_shop"),
   "show_condition" => array("show_condition",null, "show_condition", DISPLAY, 0, LEFT, null, BINARY, "ps.show_condition"),
   "show_price" => array("show_price",null, "show_price", DISPLAY, 0, LEFT, null, BINARY, "ps.show_price"),   
   "stockflags" => array("stockflags",null, "depends_on_stock", DISPLAY, 0, LEFT, null, BINARY, "sa.depends_on_stock"),   
   "supplier" => array("supplier",null, "supplier", DISPLAY, 0, LEFT, null, INPUT, "su.name"),
   "tags" => array("tags",null, "tags", DISPLAY, 0, LEFT, null, TEXTAREA, "tg.name"),
   "unit" => array("unit",null, "unity", DISPLAY, 0, LEFT, null, INPUT, "ps.unity"),
   "unitPrice" => array("unitPrice",null, "unit_price_ratio", DISPLAY, 0, LEFT, null, INPUT, "ps.unit_price_ratio"),   
   "upc" => array("upc",null, "upc", DISPLAY, 200, LEFT, null, INPUT, "p.upc"),   
   "VAT" => array("VAT",null, "rate", DISPLAY, 0, LEFT, null, DROPDOWN, "t.rate"),
   "virtualp" => array("virtualp",null, "filename", DISPLAY, 0, LEFT, null, NOT_EDITABLE, ""), /* virtual product */
   "visibility" => array("visibility",null, "visibility", DISPLAY, 0, LEFT, null, INPUT, "ps.visibility"), 
   "warehousing" => array("warehousing",null, "quantity", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "w.id_warehouse"),
   "wholesaleprice" => array("wholesaleprice",null, "wholesale_price", DISPLAY, 0, LEFT, null, INPUT, "ps.wholesale_price"),
  
	/* statistics */
   "visits" => array("visits",null, "visitcount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "visitcount"),
   "visitz" => array("visitz",null, "visitedpages", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "visitedpages"),
   "salescnt" => array("salescnt",null, "salescount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "salescount"),
   "revenue" => array("revenue",null, "revenue", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "revenue"),
   "orders" => array("orders",null, "ordercount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "ordercount"),
   "buyers" => array("buyers",null, "buyercount", DISPLAY, 0, LEFT, null, NOT_EDITABLE, "buyercount")
   ); 

  /* put the language specific data at position 1 in the field array */
  foreach($field_array as $key => $value)
  { if(isset($screentext_pe[$key]) && isset($screentext_pe[$key][1]))
	{ if($screentext_pe[$key][1] == "")
	    $screentext_pe[$key][1] = $screentext_pe[$key][0];
      $field_array[$key][1] = $screentext_pe[$key];
	}
	else /* when users use old translation files that don't support this entry */
	 $field_array[$key][1] = array($key,$key,"");
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
    
/* get default language: we use this for the categories, manufacturers */
$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$def_lang = $row['value'];
if($input['id_lang'] == "") 
  $id_lang = $def_lang;
else 
  $id_lang = $input['id_lang'];

$query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ if($id_lang == $row["id_lang"])
  { $iso_code = $row['iso_code'];
  }
}
if(!isset($iso_code))
{ mysqli_data_seek($res, 0);
  $row=mysqli_fetch_array($res);
  $iso_code = $row['iso_code']; 
  $id_lang == $row["id_lang"];
  echo "<b>Illegal value for default or selected language!</b> Using ".$languagename;
}

/* Make category table for names */
$query="select name, c.id_category from ". _DB_PREFIX_."category c, ". _DB_PREFIX_."category_lang l WHERE c.id_category=l.id_category AND l.id_lang='".$id_lang."'";
$res=dbquery($query);
$category_names = array();
while($row = mysqli_fetch_array($res))
  $category_names[$row['id_category']] = $row['name'];

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');

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
$shops = array();
$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $shops[] = $row['name'];
}

/* Make blocks for features */
$query = "SELECT fl.id_feature, name FROM ". _DB_PREFIX_."feature_lang fl";
$query .= " LEFT JOIN ". _DB_PREFIX_."feature_shop fs ON fs.id_feature = fl.id_feature";
$query .= " WHERE id_lang='".$id_lang."' AND id_shop='".$id_shop."'";
$query .= " ORDER BY id_feature";
$res = dbquery($query);
$features = array();
$featurecount = 0;
$featurelist = array();
$featurekeys = array();
while($row = mysqli_fetch_array($res))
{ $features[$row['id_feature']] = $row['name'];
  if(in_array(str_replace('"','_',$row['name']), $input["fields"]))
  { $featurelist[$row['id_feature']] = $row['name'];
    $featurekeys[] = $row['id_feature'];
  }
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product-List</title>
<style>
<?php 
  if ((isset($input['listdefault'])) && ($input['listdefault']=="true"))
  { echo "body{ font:normal 90%, Verdana, sans-serif; line-height: 100%; }
  td {vertical-align: top; padding:-15px;}
 table { border-spacing:0; border-collapse:collapse;}
table tr td { vertical-align: top}
table tr td table tr td table { width: 65px; overflow: hidden; } ";
  }
  else
  { echo "td {vertical-align: top; padding:0;}
table { border-spacing:0; border-collapse:collapse;} ";
  }
?>
table.lister table table td { border-bottom: 1px solid #CAC; }
table.lister table table td.fxwidth {
	width:210px; 
	display:inline-block; 
	overflow: hidden; 
	white-space: nowrap;
}

</style>
</head>
<body>
<?php

$x=0; $col=1; $page=1;
echo '<table class="lister"><tr><td style="width:33%">';
echo 'Criteria: ';
if(($input['search_txt1'] != '') || ($input['search_txt2'] != ''))
  echo "(".$input['search_txt1'].",".$input['search_txt2'].")";
echo $input['startrec'].",".$input['numrecs'];
echo "<br>";

$categories = array();
if(isset($input['subcats']))
  get_subcats($id_category, $categories);
else 
  $categories = array($id_category);
$cats = join(',',$categories);

$wheretext = $nottext1 = "";
if ($search_txt1 != "")
{  if($input['search_cmp1'] == "gt")
	 $inc = "< '".$search_txt1."'";
   else if($input['search_cmp1'] == "gte")
	 $inc = "<= '".$search_txt1."'";   
   else if(($input['search_cmp1'] == "eq") || ($input['search_cmp1'] == "not_eq"))
	 $inc = "= '".$search_txt1."'"; 
   else if($input['search_cmp1'] == "lte")
	 $inc = ">= '".$search_txt1."'";
   else if($input['search_cmp1'] == "lt")
	 $inc = "> '".$search_txt1."'";
   else   /* default = "in": also for "not_in" */
	 $inc = "like '%".$search_txt1."%'";
   if(($input['search_cmp1'] == "not_in") || ($input['search_cmp1'] == "not_eq"))
	   $nottext = "NOT ";
   if($search_fld1 == "main fields") 
     $wheretext .= " AND ".$nottext1." (p.reference ".$inc." or p.supplier_reference ".$inc." or pl.name ".$inc." or pl.description ".$inc."  or pl.description_short ".$inc." or m.name ".$inc." or p.id_product='".$search_txt1."') ";
   else if(($search_fld1 == "ps.id_category_default") || ($search_fld1 == "p.id_product"))
     $wheretext .= " AND ".$nottext1.$search_fld1." ".$inc." ";
   else if ($search_fld1 == "cr.name")
	 $wheretext .= " AND ".$nottext1." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_carrier pc LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0 WHERE pc.id_product = p.id_product AND cr.name ".$inc.")";
   else if ($search_fld1 == "tg.name")
   { $wheretext .= " AND ".$nottext1." EXISTS (SELECT NULL FROM ". _DB_PREFIX_."tag tg";
     $wheretext .= " LEFT JOIN ". _DB_PREFIX_."product_tag pt ON pt.id_tag=tg.id_tag WHERE tg.name ".$inc." AND p.id_product=pt.id_product AND tg.id_lang='".$id_lang."') ";
   }
   else if($search_fld1 == "su.name")
	 $wheretext .= " AND ".$nottext1." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu LEFT JOIN ". _DB_PREFIX_."supplier su ON psu.id_supplier=su.id_supplier WHERE psu.id_product = p.id_product AND su.name ".$inc.")";
   else if($search_fld1 == "priceVAT")
	 $wheretext .= " AND ".$nottext1." (ROUND(((rate/100)+1)*ps.price,2) ".$inc.")";
   else if($search_fld1 == "w.id_warehouse")
	 $wheretext .= " AND ".$nottext1." EXISTS(SELECT NULL FROM ". _DB_PREFIX_."stock st LEFT JOIN ". _DB_PREFIX_."warehouse w ON st.id_warehouse=w.id_warehouse WHERE st.id_product = p.id_product AND w.name ".$inc.")";
   else if($search_fld1 == "cl.id_category")
     $wheretext .= " AND cp1.id_category ".$inc." ";		
   else if($search_fld1 != "discount")
     $wheretext .= " AND ".$nottext1.$search_fld1." ".$inc." ";
}
if($search_fld1 == "discount") /* this works also when search_txt field is empty */
	 $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."specific_price sp WHERE sp.id_product = p.id_product)";

$catfrags = array(); 
if (($input['search_txt2'] != "") && ($search_fld2 != "discount"))
{  $nottext2 = "";
   if(($input['search_cmp2'] == "not_in") || ($input['search_cmp2'] == "not_eq"))
	   $nottext2 = "NOT ";
   if(in_array($input['search_cmp2'], array("eq","not_eq","in","not_in")))
   { $frags = explode(",",$search_txt2);
     if(($search_fld2 == "cl.id_category") || ($search_fld2 == "ps.id_category_default"))
     { 
	   foreach($frags AS $clp)
	   { if(stripos($clp,'s')) /* "6s" means category 6 with subcategories */
		   get_subcats(str_replace('s','',$clp), $catfrags); /* this function will place the results in the categories array */
	     else if($clp != 0)
		   $catfrags[] = $clp;
	   }
	   $frags = $catfrags;
	 }
   }
   else
	   $frags = array($search_txt2);
   
   $wheretext .= " AND ".$nottext2." (";
   $first = true;
   foreach($frags AS $frag)
   { if($input['search_cmp2'] == "gt")
	   $inc = "< '".$frag."'";
     else if($input['search_cmp2'] == "gte")
	   $inc = "<= '".$frag."'";
     else if(($input['search_cmp2'] == "eq") || ($input['search_cmp2'] == "not_eq"))
	   $inc = "= '".$frag."'"; 
     else if($input['search_cmp2'] == "lte")
	   $inc = ">= '".$frag."'";
     else if($input['search_cmp2'] == "lt")
	   $inc = "> '".$frag."'";
     else   /* default = "in": also for "not_in" */
	   $inc = "like '%".$frag."%'";

     if($first) $first = false; else $wheretext .= " OR ";

     if($search_fld2 == "main fields") 
       $wheretext .= "p.reference ".$inc." or p.supplier_reference ".$inc." or pl.name ".$inc." or pl.description ".$inc."  or pl.description_short ".$inc." or m.name ".$inc." or p.id_product='".$search_txt2."' ";
     else if(($search_fld2 == "ps.id_category_default") || ($search_fld2 == "p.id_product"))
       $wheretext .= $search_fld2." ".$inc." ";
     else if ($search_fld2 == "cr.name")
	   $wheretext .= " EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_carrier pc LEFT JOIN ". _DB_PREFIX_."carrier cr ON cr.id_reference=pc.id_carrier_reference AND cr.deleted=0 WHERE pc.id_product = p.id_product AND cr.name ".$inc.")";
     else if ($search_fld2 == "tg.name")
     { $wheretext .= " EXISTS (SELECT NULL FROM ". _DB_PREFIX_."tag tg";
       $wheretext .= " LEFT JOIN ". _DB_PREFIX_."product_tag pt ON pt.id_tag=tg.id_tag WHERE tg.name ".$inc." AND p.id_product=pt.id_product AND tg.id_lang='".$id_lang."') ";
     }
     else if($search_fld2 == "su.name")
	   $wheretext .= " EXISTS(SELECT NULL FROM ". _DB_PREFIX_."product_supplier psu LEFT JOIN ". _DB_PREFIX_."supplier su ON psu.id_supplier=su.id_supplier WHERE psu.id_product = p.id_product AND su.name ".$inc.")";
     else if($search_fld2 == "priceVAT")
	   $wheretext .= " (ROUND(((rate/100)+1)*ps.price,2) ".$inc.")";
     else if($search_fld2 == "w.id_warehouse")
	   $wheretext .= " EXISTS(SELECT NULL FROM ". _DB_PREFIX_."stock st LEFT JOIN ". _DB_PREFIX_."warehouse w ON st.id_warehouse=w.id_warehouse WHERE st.id_product = p.id_product AND w.name ".$inc.")";
     else if($search_fld2 == "cl.id_category")
       $wheretext .= "cp2.id_category ".$inc." ";		   
	 else 
       $wheretext .= $search_fld2." ".$inc." ";
   }
   $wheretext .= ") ";
}
if($search_fld2 == "discount")
	 $wheretext .= " AND EXISTS(SELECT NULL FROM ". _DB_PREFIX_."specific_price sp WHERE sp.id_product = p.id_product)";


/* Note: we start with the query part after "from". First we count the total and then we take 100 from it */
/* DISTINCT is for when "with subcats" results in more than one occurence */
$queryterms = "DISTINCT p.*,ps.*,pl.*,t.id_tax,t.rate,m.name AS manufacturer, cl.name AS catname, cl.link_rewrite AS catrewrite";
$queryterms .= ", pld.name AS originalname, s.quantity, s.depends_on_stock";

$query = " FROM ". _DB_PREFIX_."product_shop ps LEFT JOIN ". _DB_PREFIX_."product p ON p.id_product=ps.id_product";
$query.=" LEFT JOIN ". _DB_PREFIX_."product_lang pl ON pl.id_product=p.id_product and pl.id_lang='".$id_lang."' AND pl.id_shop='".$id_shop."'";
$query.=" LEFT JOIN ". _DB_PREFIX_."product_lang pld ON pld.id_product=p.id_product and pld.id_lang='".$def_lang."' AND pld.id_shop='".$id_shop."'"; /* This gives the name in the shop language instead of the selected language */
$query.=" LEFT JOIN ". _DB_PREFIX_."manufacturer m ON m.id_manufacturer=p.id_manufacturer";
$query.=" LEFT JOIN ". _DB_PREFIX_."category_lang cl ON cl.id_category=ps.id_category_default AND cl.id_lang='".$id_lang."' AND cl.id_shop = '".$id_shop."'";
if($share_stock == 0)
  $query.=" LEFT JOIN ". _DB_PREFIX_."stock_available s ON s.id_product=p.id_product AND s.id_shop = '".$id_shop."' AND id_product_attribute='0'";
else
  $query.=" LEFT JOIN ". _DB_PREFIX_."stock_available s ON s.id_product=p.id_product AND s.id_shop_group = '".$id_shop_group."' AND id_product_attribute='0'";
$query.=" LEFT JOIN ". _DB_PREFIX_."tax_rule tr ON tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state='0'";
$query.=" LEFT JOIN ". _DB_PREFIX_."tax t ON t.id_tax=tr.id_tax";
$query.=" LEFT JOIN ". _DB_PREFIX_."tax_lang tl ON t.id_tax=tl.id_tax AND tl.id_lang='".$def_lang."'";

if ($input['order']=="id_product") $order="p.id_product";
else if ($input['order']=="name") $order="pl.name";
else if ($input['order']=="position") $order="cp.position"; /* later to be refined */
else if ($input['order']=="VAT") $order="t.rate";
else if ($input['order']=="price") $order="ps.price";
else if ($input['order']=="active") $order="ps.active";
else if ($input['order']=="shipweight") $order="p.weight";
else if ($input['order']=="date_upd") $order="p.date_upd";
else if ($input['order']=="image")  /* sorting on image makes only sense to get the products without an image */
{  $order="i.cover";
   $queryterms .= ",i.id_image, i.cover";
   $query.=" LEFT JOIN ". _DB_PREFIX_."image i ON i.id_product=p.id_product and i.cover=1";
}
else $order = $input['order'];

if ($input['active']=="active")
	$wheretext = " AND ps.active=1";
else if ($input['active']=="inactive")
	$wheretext = " AND ps.active=0";

if(($search_txt1 != "")&&($search_fld1 == "cl.id_category"))
{	$query .= " LEFT JOIN ". _DB_PREFIX_."category_product cp1 on p.id_product=cp1.id_product";
}
if(($search_txt2 != "")&&($search_fld2 == "cl.id_category"))
{	$query .= " LEFT JOIN ". _DB_PREFIX_."category_product cp2 on p.id_product=cp2.id_product";
}
if ($id_category != 0)
{	$query .= " LEFT JOIN ". _DB_PREFIX_."category_product cp on p.id_product=cp.id_product";
	$wheretext .= " AND cp.id_category IN ($cats)";
}
else
{	$query .= " LEFT JOIN ". _DB_PREFIX_."category_product cp on ps.id_category_default=cp.id_category AND p.id_product=cp.id_product";
}

if (($order=="cp.position") && ((sizeof($categories)>1) || ($id_category == 0)))
{ $query .= " LEFT JOIN ". _DB_PREFIX_."category c on c.id_category=cp.id_category";
  $order = "c.nleft,cp.position";
}

if(in_array("virtualp", $input["fields"]))
{ $query.=" LEFT JOIN ". _DB_PREFIX_."product_download pd ON pd.id_product=p.id_product";
  $queryterms .= ", filename, display_filename";
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

/***************** The following lines are not in product-edit.php *****************************/
$query .= " LEFT JOIN ". _DB_PREFIX_."image i ON i.id_product=p.id_product AND i.cover=1";
$queryterms .= ",id_image, cp.id_category";

foreach($features AS $key => $feature)
{ $cleanfeature = str_replace('"','_',$feature);
  if (in_array($cleanfeature, $input["fields"]))
  { $query.=" LEFT JOIN ". _DB_PREFIX_."feature_product fp".$key." ON fp".$key.".id_product=p.id_product AND fp".$key.".id_feature='".$key."'";
	$query.=" LEFT JOIN ". _DB_PREFIX_."feature_value fv".$key." ON fp".$key.".id_feature_value=fv".$key.".id_feature_value";
	$query.=" LEFT JOIN ". _DB_PREFIX_."feature_value_lang fl".$key." ON fp".$key.".id_feature_value=fl".$key.".id_feature_value AND fl".$key.".id_lang='".$id_lang."'";
	$queryterms .= ",fv".$key.".custom AS custom".$key.",fl".$key.".value AS value".$key;
  }
}

if(in_array("visits", $input["fields"]) OR ($order=="visits") OR ($input["search_fld1"]=="visitcount") OR ($input["search_fld2"]=="visitcount"))
{ $query .= " LEFT JOIN ( SELECT pg.id_object, count(*) AS visitcount FROM ". _DB_PREFIX_."connections c LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type='1' AND pg.id_page = c.id_page AND c.id_shop='".$id_shop."'";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(c.date_add) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) <= TO_DAYS('".$input['enddate']."')";
  $queryterms .= ", visitcount ";
  $query .= " GROUP BY pg.id_object ) v ON p.id_product=v.id_object";
}
if(in_array("visitz", $input["fields"]) OR ($order=="visitz") OR ($input["search_fld1"]=="visitedpages") OR ($input["search_fld2"]=="visitedpages"))
{ /* for mysql 5.7.5 compatibility "SELECT pg.id_object" was replaced by "SELECT MAX(pg.id_object) AS id_object" */
  $query .= " LEFT JOIN ( SELECT MAX(pg.id_object) AS id_object, sum(counter) AS visitedpages FROM ". _DB_PREFIX_."page_viewed v LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type='1' AND pg.id_page = v.id_page AND v.id_shop='".$id_shop."'";
  $query .= " LEFT JOIN ". _DB_PREFIX_."date_range d ON d.id_date_range = v.id_date_range";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(d.time_start) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(d.time_end) <= TO_DAYS('".$input['enddate']."')";
  $queryterms .= ", visitedpages ";
  $query .= " GROUP BY v.id_page ) w ON p.id_product=w.id_object";
}
if(in_array("revenue", $input["fields"]) OR in_array("salescnt", $input["fields"]) OR in_array("orders", $input["fields"]) OR ($order=="revenue")OR ($order=="orders")OR ($order=="buyers"))
{ $query .= " LEFT JOIN ( SELECT product_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ";
  $query .= " ROUND(SUM(total_price_tax_incl),2) AS revenue, ";
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

$res=dbquery("SELECT COUNT(*) AS rcount ".$query." WHERE ps.active='1' AND ps.id_shop='".$id_shop."' ".$wheretext);
$row = mysqli_fetch_array($res);
$numrecs = $row['rcount'];

$query.=" WHERE ps.id_shop='".$id_shop."' ".$wheretext;

  $statfields = array("salescnt", "revenue","orders","buyers","visits","visitz");
  $stattotals = array("salescnt" => 0, "revenue"=>0,"orders"=>0,"buyers"=>0,"visits"=>0,"visitz"=>0); /* store here totals for stats */
//  $statz = array("salescount", "revenue","ordercount","buyercount","visitcount","visitedpages"); /* here pro memori: moved up to search_fld definition */
  if(in_array($order, $statfields))
  { $ordertxt = $statz[array_search($order, $statfields)];
  }
  else
    $ordertxt = str_replace(" ","",$order);
  /* GROUP BY p.id_product is for "With subcats" when the products is in more than one of the involved categories */
  $query .= " GROUP BY p.id_product ORDER BY ".$ordertxt." ".$rising." LIMIT ".$input['startrec'].",".$input['numrecs'];
  
  $query= "select SQL_CALC_FOUND_ROWS ".$queryterms.$query; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */
  $res=dbquery($query);
  $numrecs3 = mysqli_num_rows($res);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);
  $numrecs2 = $row2['foundrows'];
  
  $listlines = intval($input['listlines']);
  $listcols = intval($input['listcols']);
  $listseps = intval($input['listseps']);
  $page = 1;
  $showheads = false;
  if(($order == "c.nleft,cp.position") && ((sizeof($categories)>1) || (sizeof($catfrags)>1)))
	  $showheads = true; 

  echo '<table>';
  $x=99999;
  $page = 0;
  $col = 999;
  $cat = -1;
  while ($datarow=mysqli_fetch_assoc($res))
  { 
    if($x > $listlines)
	{ $x = 0; 
	  if($col <=($listcols+1))
	    echo "</table></td>";
	  $col++;
	  if($col > $listcols)
	  { $col = 1; 
		$page++;
		if($page != 1)
		{ echo "</tr></table>";
		  for($i=0; $i< $input['listseps']; $i++)
		    echo '<br/>';
		}
		echo '<table><tr><td style="width:33%">';
		if($showheads)
		{ echo "Page ".$page." cat ".$datarow["id_category"]."-".$category_names[$datarow["id_category"]]."<br>";
		}
		echo "<table>";
	  }
	  else
	  { echo '<td style="width:33%"><table>';
	  }
	}
	if(($showheads) && ($cat != $datarow["id_category"]))
	{ $cat = $datarow["id_category"];
	  if($x != 0)
		echo "<tr><td>cat ".$datarow["id_category"]."-".$category_names[$datarow["id_category"]]."</td></tr>";
	}
    echo '<tr>';
	if(isset($input['listdefault'])) 
	{ echo '<td><table><tr><td class="fxwidth"><nobr>'.substr($datarow['name'],0,26).'</nobr><br>';
	  echo '<nobr>';
	  $len = strlen(substr($datarow['name'],26));	  
	  if($len > 0)
	  { echo substr($datarow['name'],26)." ";
	    $len++;
	  }
	  echo substr(strip_tags($datarow['description_short']),0,(26-$len)).'</nobr><br>';
	  echo '<nobr>';
	  if ($rewrite_settings == '1')
        echo "<a href='../".$datarow['catrewrite']."/".$datarow['id_product']."-demo.html' target='_blank'>".$datarow['id_product']."</a>";
	  else
        echo "<a href='".get_base_uri()."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang."' target='_blank'>".$datarow['id_product']."</a>";
	  echo ' '.$datarow['active'].' '.number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '').'</nobr></td>';
	  echo '<td>'.get_product_image($datarow['id_product'],$datarow['id_image'], $datarow['id_image']).'</td></tr>';

	  echo '</table></td>';
	  echo '</tr>';
	  $x++;
	} /* Not default fields */
	else
	{ for($i=1; $i< sizeof($infofields); $i++)
      { $sorttxt = "";
        $color = "";
        if($infofields[$i][2] == "priceVAT")
	 	  $myvalue =  number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '');
        else
          $myvalue = $datarow[$infofields[$i][2]];
	    if($i == 1) /* id */
	    { $start = "";
	      $xdebug = ""; //"-".$x;
	      if($col > 0) $start= '&nbsp;';
	      if ($rewrite_settings == '1')
            echo "<td>".$start."<a href='../".$datarow['catrewrite']."/".$myvalue."-demo.html' target='_blank'>".addspaces($myvalue).$xdebug."</a></td>";
		  else
            echo "<td>".$start."<a href='".get_base_uri()."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang."' title='".$datarow['originalname']."' target='_blank'>".addspaces($myvalue).$xdebug."</a></td>";
	    }
	    else if($infofields[$i][6] == 1)
        { $sorttxt = "srt='".str_replace("'", "\'",$myvalue)."'";
          echo "<td ".$sorttxt.">".$myvalue."</td>";
        }
        else if ($infofields[$i][0] == "category")
		  echo "<td ".$sorttxt."><a title='".$datarow['catname']."' href='#' onclick='return false;'>".$myvalue."</a></td>";
        else if ($infofields[$i][0] == "VAT")
        { $sorttxt = "srt='".$datarow['id_tax_rules_group']."'";
		  echo "<td>".$datarow['rate']."</td>";
        }
        else if ($infofields[$i][0] == "price")
        { echo "<td>".number_format($datarow['price'],2, '.', '')."</td>";
	    }
        else if ($infofields[$i][0] == "image")
        { echo "<td>".get_product_image($datarow['id_product'],$datarow['id_image'], $datarow['id_image'])."</td>";
        }
        else
          echo "<td>&nbsp;".strip_tags($myvalue)."</td>";
      }
      echo '</tr>';
	  $x++;
	}
  }
  echo '</table>';

echo '</table></td></tr></table>';
echo '</body></html>';

?>
