<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(!@include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
$input = $_GET;
if(!isset($input['id_category']))
   $id_category="1"; /* 1=root */
else 
   $id_category = intval($input['id_category']);
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['fields'])) $input['fields']="";
if(!isset($input['startdate'])) $input['startdate']="";
if(!isset($input['enddate'])) $input['enddate']="";
if(!isset($input['imgformat'])) {$input['imgformat']="";}

  if(empty($input['fields']))
    $input['fields'] = array("name","VAT","price", "category", "ean", "active", "shortdescription", "image");
  else
    $input['fields'][] = "active"; /* you are not allowed to remove this */

  $infofields = array();
  $if_index = 0;
   /* [0]title, [1]keyover, [2]source, [3]display(0=not;1=yes;2=edit;), [4]fieldwidth(0=not set), [5]align(0=default;1=right), [6]sortfield, [7]Editable, [8]table */
  define("HIDE", 0); define("DISPLAY", 1); define("EDIT", 2);  // display
  define("LEFT", 0); define("RIGHT", 1); // align
  define("NO_SORTER", 0); define("SORTER", 1); /* sortfield => 0=no escape removal; 1=escape removal; */
  define("NOT_EDITABLE", 0); define("INPUT", 1); define("TEXTAREA", 2); define("DROPDOWN", 3);   /* title, keyover, source, display(0=not;1=yes;2=edit), fieldwidth(0=not set), align(0=default;1=right), sortfield */
   /* sortfield => 0=no escape removal; 1=escape removal; 2 and higher= escape removal and n lines textarea */
  $infofields[$if_index++] = array("","", "", DISPLAY, 0, LEFT, 0,0,"");
  $infofields[$if_index++] = array("id","", "id_product", DISPLAY, 0, RIGHT, NO_SORTER,NOT_EDITABLE, "p.id_product");
  $infofields[$if_index++] = array("position","", "position", DISPLAY, 0, RIGHT, NO_SORTER,EDIT, "cp.position");
  
  $field_array = array(
   "name" => array("name","", "name", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "pl.name"),
   "active" => array("active","", "active", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "p.active"),
   "reference" => array("reference","", "reference", DISPLAY, 200, LEFT, NO_SORTER, INPUT, "pl.reference"),
   "ean" => array("ean","", "ean13", DISPLAY, 200, LEFT, NO_SORTER, INPUT, "p.ean"),
   "category" => array("category","", "id_category_default", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "p.id_category_default"),
   "price" => array("price","", "price", DISPLAY, 200, LEFT, NO_SORTER, INPUT, "p.price"),
   "VAT" => array("VAT","", "rate", DISPLAY, 0, LEFT, NO_SORTER, DROPDOWN, ""),
   "priceVAT" => array("priceVAT","", "priceVAT", DISPLAY, 0, LEFT, NO_SORTER, INPUT, ""),
   "quantity" => array("qty","", "quantity", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA, "p.quantity"),
   "shortdescription" => array("description_short","", "description_short", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA, "pl.short_description"),
   "description" => array("description","", "description", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA, "pl.description"),
   "manufacturer" => array("manufacturer","", "manufacturer", DISPLAY, 0, LEFT, NO_SORTER, DROPDOWN, "m.name"),
   "linkrewrite" => array("link_rewrite","", "link_rewrite", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "p.link_rewrite"),
   "metatitle" => array("meta_title","", "meta_title", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "pl.meta_title"),
   "metakeywords" => array("meta_keywords","", "meta_keywords", DISPLAY, 0, RIGHT, NO_SORTER, TEXTAREA, "pl.meta_keywords"),
   "metadescription" => array("meta_description","", "meta_description", DISPLAY, 0, LEFT, NO_SORTER, TEXTAREA, "pl.meta_description"),
   "shipweight" => array("shipweight","", "weight", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "p.weight"),
   "date_add" => array("date_add",null, "date_add", DISPLAY, 0, LEFT, null, INPUT, "ps.date_add"),  
   "image" => array("image","", "id_image", HIDE, 0, LEFT, 0, 0, ""),
   
   "visits" => array("visits","", "visitcount", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "visitcount"),
   "visitz" => array("visitz","", "visitedpages", DISPLAY, 0, LEFT, NO_SORTER, NOT_EDITABLE, "visitedpages"),
   "salescnt" => array("salescnt","", "salescount", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "salescount"),
   "revenue" => array("revenue","", "revenue", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "revenue"),
   "orders" => array("orders","", "ordercount", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "ordercount"),
   "buyers" => array("buyers","", "buyercount", DISPLAY, 0, LEFT, NO_SORTER, INPUT, "buyercount")
   );
/*
  if(in_array("priceVAT", $input["fields"]))
  { $input["fields"] = array_diff($input["fields"], array("priceVAT"));
    if(!in_array("price", $input["fields"]))
	  array_push($input["fields"], "price");
  }
  */
  foreach($field_array AS $key => $value)
  { if (in_array($key, $input["fields"]))
    { 	$infofields[$if_index++] = $value;
	}
  }

//id_category 	id_parent 	level_depth 	nleft 	nright 	active 	date_add 	date_upd 	position
//id_category  id_lang 	name 	description 	link_rewrite 	meta_title 	meta_keywords 	meta_description

/* section 4: retrieve system settings and build php blocks for select fields */
/* Get default language if none provided */
/* get default language: we use this for the categories, manufacturers */
$def_lang = get_configuration_value('PS_LANG_DEFAULT');
if($input['id_lang'] == "") 
  $id_lang = $def_lang;
else 
  $id_lang = $input['id_lang'];

$rewrite_settings = get_configuration_value('PS_REWRITING_SETTINGS');
$allow_accented_chars = get_configuration_value('PS_ALLOW_ACCENTED_CHARS_URL');

/* the following deals with customized product urls (used for the links on the product name) */
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

if(!isset($input['id_category'])) /* get home category */
{ $query="select c.id_category from ". _DB_PREFIX_."category c";
  $query .= " LEFT JOIN ". _DB_PREFIX_."category c2 ON c2.id_category=c.id_parent";
  $query .= " WHERE c2.id_parent=0";
  $res=dbquery($query);
  if(mysqli_num_rows($res) > 0)
  { $row = mysqli_fetch_array($res);
    $id_category=$row['id_category'];
  }
}

$query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ if($def_lang == $row["id_lang"])
	$def_langname = $row['name'];
  if($id_lang == $row["id_lang"])
  { $iso_code = $row['iso_code'];
    $languagename = $row['name'];
  }
}

/* now get multi-language status. If true you get product urls like www.myshop.com/en/mycat/123-prod.html */
$query='SELECT COUNT(DISTINCT l.id_lang) AS langcount FROM `'._DB_PREFIX_.'lang` l
				JOIN '._DB_PREFIX_.'lang_shop ls ON (ls.id_lang = l.id_lang)
				LEFT JOIN '._DB_PREFIX_.'shop s ON (ls.id_shop = s.id_shop)
				WHERE l.`active` = 1 ORDER BY s.active DESC limit 1';
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$langcount = (int)$row['langcount'];
$langinsert = "";
if($langcount>1)
	$langinsert = $iso_code."/";

/* Get default country for the VAT tables and calculations */
$query="select l.name, id_country from ". _DB_PREFIX_."configuration f, "._DB_PREFIX_."country_lang l";
$query .= " WHERE f.name='PS_COUNTRY_DEFAULT' AND f.value=l.id_country ORDER BY id_lang IN('".$id_lang."','1') DESC"; /* the construction with the languages should select all languages with def_lang and '1' first */
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$countryname = $row['name'];
$id_country = $row["id_country"];

/* Make image format block */
  $query = "SELECT name,width,height from ". _DB_PREFIX_."image_type WHERE products=1 ORDER BY width";
  $res=dbquery($query);
  $imgformatblock = '<select name="imgformat" style="width:108px">';
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


?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Sort</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<style>
option.defcat {background-color: #ff2222;}
input.posita {width: 50px; text-align:right}
</style>
<script>
function getColumn(name)
{ var tbl = document.getElementById("Maintable");
  var len = tbl.tHead.rows[0].cells.length;
  for(var i=0;i<len; i++)
  { if(tbl.tHead.rows[0].cells[i].innerHTML.replace(/(<([^>]+)>)/ig,"") == name) /* the replace strips html tags */
	  return i;
  }
  alert("Not Found "+name+" -- "+tbl.tHead.rows[0].cells[3].firstChild.innerHTML);
}

function setrows(val)
{ var thebody = document.getElementById("offTblBdy");
  var start = parseInt(slctform.startrow.value);
  var end = parseInt(slctform.endrow.value);
  if(isNaN(start)) { start = 0; slctform.startrow.value = 0;}
  if(isNaN(end)) { end = (thebody.rows.length)-1; slctform.endrow.value = end; }
  if(start > end)
  { alert("Startvalue out of range");
	return;
  }
  if(end >= thebody.rows.length) end = (thebody.rows.length)-1;
  for(var i=start; i<=end; i++)
  { var idx = thebody.rows[i].cells[0].id.substring(4);
    var fld = eval("Mainform.slct"+idx);
	if(val==1)
	  fld.checked = true;
    else
	  fld.checked = false;		
  }
  listselectedrows();
}

function moverows(num)
{ var movesize = parseInt(slctform.movesize.value);
  if(isNaN(movesize) || (movesize <= 0))
  { alert("Move distance must be a positive number");
	return false;
  }
  var thebody = document.getElementById("offTblBdy");
  var len = thebody.rows.length;  
  if((checkedcount == 0) || (checkedcount == len))
  { alert("No rows (or all rows) have been selected");
	return false;
  }
  var targets = [];
  var notchecked = [];
  var translats = []; /* when the table has been sorted the row number are no longer consecutive. This rectifies that */
  var targetindex;
  var surplus = len - checkedcount; /* you cannot move the rows more than there are unchecked rows that can be displaced */
  if(num == 0)
  { nidx = 0;
	for(var i=0; i<len; i++)
    { var idx = thebody.rows[i].cells[0].id.substring(4);
	  translats[i] = idx;
	  var fld = eval("Mainform.slct"+idx);
	  if(fld.checked)
	  { if(movesize>surplus) movesize = surplus;
		targets[i+movesize] = i;
	  }
	  else
	  { notchecked[nidx++] = i;
		surplus--;
	  }
	}
	var j =0;
	for(var i=0; i<len; i++)
	{ if (typeof targets[i] === 'undefined')
	  { targets[i]=notchecked[j++];
	  } 
	}
	for(var i=0; i<len; i++)
    { var fld = eval("Mainform.position"+translats[targets[i]]);
	  fld.value = i;
    }
    CatSort();
  }
  else
  { nidx = 0;
	for(var i=len-1; i>=0; i--)
    { var idx = thebody.rows[i].cells[0].id.substring(4);
	  translats[i] = idx;
	  var fld = eval("Mainform.slct"+idx);
	  if(fld.checked)
	  { if(movesize>surplus) movesize = surplus;
		targets[i-movesize] = i;
	  }
	  else
	  { notchecked[nidx++] = i;
		surplus--;
	  }
	}
	var j =0;
	for(var i=len-1; i>=0; i--)
	{ if (typeof targets[i] === 'undefined')
	  { targets[i]=notchecked[j++];
	  } 
	}
	for(var i=0; i<len; i++)
    { var fld = eval("Mainform.position"+translats[targets[i]]);
	  fld.value = i;
    }
    CatSort();
  }
}

checkedcount = 0;
function listselectedrows()
{ var len = document.getElementById("offTblBdy").rows.length;
  checkedcount = 0;
  var active = 0;
  var laststart = -1;
  var textstring = "";
  for(var i=0; i<len; i++)
  { var fld = eval("Mainform.slct"+i);
	if(fld.checked) 
	{ checkedcount++;
	  if(!active)
	  { active = true;
		if(textstring.length > 0)
			textstring += ",";
		textstring += i;
		laststart = i;
	  }
	}
	else
	{ if(active)
	  { active = false;
		if((i-laststart) > 1)
		{ textstring += "-"+(i-1);
		}
	  }
	}
  }
  if((active) && ((i-laststart) > 1))
	  textstring += "-"+(i-1);
  var fld = document.getElementById("selectedrows");
  fld.innerHTML = textstring;
}

function sort_on_column(elt, rownum, reverse)
{ elt.blur(); 
  var thebody = document.getElementById("offTblBdy");
  var len = thebody.rows.length;
  if(checkedcount == len) return;
  if(checkedcount != 0)
  { targets = [];
	origins = [];
    for(var i=0; i<len; i++)
    { var idx = thebody.rows[i].cells[0].id.substring(4);
	  var fld = eval("Mainform.slct"+idx);
	  if(fld.checked)
	  { origins[idx] = i;
	  }
	}
  }
  sortTable('offTblBdy', rownum, reverse);
  CatNumber();
  if(checkedcount != 0)
  { tidx = 0; /* index in targets */
	var nidx = 0;
    targets = [];
	notchecked = [];
	translats = [];
    for(var i=0; i<len; i++)
    { var idx = thebody.rows[i].cells[0].id.substring(4);
	  translats[i] = idx;
	  if (typeof origins[idx] !== 'undefined')
	  { targets[i] = origins[idx]; /* put back to old i */
	  }
	}
	for(var i=0; i<len; i++)
	{ if(targets.indexOf(i) == -1) /* note that we look here in the values - not the indexes - of targets */
		notchecked[nidx++] = i;		
	}
	var j =0;
	for(var i=0; i<len; i++)
	{ if (typeof targets[i] === 'undefined')
	  { targets[i]=notchecked[j++];
	  } 
	}
//	alert("FFF "+notchecked[0]+" "+notchecked[1]+" "+notchecked[2]);
//	alert("HHHF "+targets[0]+" "+targets[1]+" "+targets[2]+" "+targets[3]);
	for(var i=0; i<len; i++)
    { var fld = eval("Mainform.position"+translats[i]);
	  fld.value = targets[i];
    }
	/* we need to preserve the sort status so that alternating reverse and straight sorting keeps working */
    var divEl = document.getElementById('testdiv');
	var colm = divEl.lastColumn;
	var valx = divEl.reverseSort[colm];
    CatSort();
	divEl.reverseSort[colm] = valx;
    divEl.lastColumn = colm; 	
  }
  return false;
}

/* mark_columns puts a black line under each row */
function mark_columns()
{ var prodrow = parseInt(search_form.prodrow.value);
  var tbl = document.getElementById("Maintable");
  var len = tbl.rows.length;
  var activecol = getColumn("active");
  var activ = 0;
  for(var i=1; i<len; i++)  /* skip headrow */
  { var subbar = false;
    if(tbl.rows[i].cells[activecol].innerHTML == '1')
	{ tbl.rows[i].style.backgroundColor = '#FFFFFF';
	  activ++;
	  if(!(activ % prodrow))
		subbar = true;
	}
    else
	  tbl.rows[i].style.backgroundColor = '#DDDDDD';
    if(subbar)
	  tbl.rows[i].style.borderBottom = '3pt solid black';
    else
	  tbl.rows[i].style.borderBottom = '';
  }
  var mytable = document.getElementById('Maintable');
}

function enable_advanced_mode()
{ var column = getColumn("Select");
  var len = Maintable.rows.length;
  for(var i=0; i<len; i++)
	Maintable.rows[i].cells[column].style.display = "table-cell";
  var elt = document.getElementById("advanceddiv");
  elt.style.display="block";
  return false;
}
</script>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>
<body>
<?php /* you cannot add an onload to the <body> tag above as it is already done later with window.onload = function() */
print_menubar();
echo '<table style="width:100%" ><tr><td width="70%" valign="top">';
echo '<a href="product-sort.php" style="text-decoration:none"><center><b><font size="+1">Product Sort</font></b></center></a>';
echo "Config:";
if($input['id_lang'] == "")
  echo " lang: ".$languagename." (used for names),";
echo " country: ".$countryname." (used for VAT)<br/>";
echo "Prestashop stores only one sorting order for a category. In multishop not all informative data may come from the same shop.<br/>";
echo "By default the product will move immediately after you entered the number. You can switch that off with the 'autosort' checkbox. Don't forget to submit to implement your changes!<br>";
echo 'If you have more than one product on a row - like on the standard homepage - you may also try <a href="product-vissort.php">Visual Sort</a><br>
If you don\'t see an effect after you submit you should refresh your page with ctrl-F5.<br>
<input type=button value="Enable advanced mode" onclick="return enable_advanced_mode();"> for block operation and partial sort.</td>';
echo '<td style="text-align:right; width:30%"><iframe name=tank width="230" height="95"></iframe></td></tr></table>';
?>

<table class="triplesearch"><tr><td>
<form name="search_form" method="get" action="product-sort.php">
Select the category in which you want to sort the products: <select name="id_category">
<?php 

    $query = "select c.id_category,name,link_rewrite, id_parent from "._DB_PREFIX_."category c";
    $query .= " left join "._DB_PREFIX_."category_lang cl ON c.id_category=cl.id_category AND id_lang='".$id_lang."'";
    $query .= " GROUP BY c.id_category ORDER BY name";
    $res=dbquery($query);
    $category_names = $category_rewrites = $category_parents = array();
    while ($row=mysqli_fetch_array($res)) 
    { $category_parents[$row['id_category']] = $row['id_parent'];	
      if($row["name"]=="")
	  { $cres = dbquery("SELECT id_category, name, link_rewrite FROM "._DB_PREFIX_."category_lang WHERE id_category=".$row["id_category"]." AND id_lang=".$id_lang);
	    $row=mysqli_fetch_array($cres);
	  }
      $name = str_replace("\\","\\\\",$row['name']);
      $category_names[$row['id_category']] = $name;
      $category_rewrites[$row['id_category']] = $row['link_rewrite'];
	  if ($row['id_category']==$id_category) {$selected=' selected="selected" ';} else $selected="";
	    echo '<option  value="'.$row['id_category'].'" '.$selected.'>'.$row['name'].'</option>';
    } 
	echo '</select>';
	
	$res=dbquery("SELECT COUNT(*) AS rcount from ". _DB_PREFIX_."product");
	$row = mysqli_fetch_array($res);
	$totcount = $row["rcount"];
	echo '<br/>Total '.$totcount.' products.';
	echo ' &nbsp &nbsp; Img '.$imgformatblock;	
	echo ' &nbsp &nbsp; Language: <select name="id_lang" style="margin-top:5px">';
	  $query=" select * from ". _DB_PREFIX_."lang ";
	  $res=dbquery($query);
	  while ($language=mysqli_fetch_array($res)) {
		$selected='';
	  	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	        echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
	  }
	echo '</select>';
	if(isset($_GET["prodrow"]))
	  $prodrow = $_GET["prodrow"];
	else 
	  $prodrow = 4;
	echo ' &nbsp; No of products on a row: <input size=1 name=prodrow value="'.$prodrow.'" onchange="mark_columns();">';
	echo '<hr/>';

	echo '<table><tr>';
	$checked = in_array("name", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="name" '.$checked.' /> Name</td>';
	$checked = in_array("VAT", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="VAT" '.$checked.' /> VAT</td>';
	$checked = in_array("priceVAT", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="priceVAT" '.$checked.' /> priceVAT</td>';
	$checked = in_array("reference", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="reference" '.$checked.' /> Reference</td>';
	$checked = in_array("category", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="category" '.$checked.' /> Category</td>';
	$checked = in_array("description", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="description" '.$checked.' /> Description</td>';
	$checked = in_array("shortdescription", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="shortdescription" '.$checked.' /> Short Desc</td>';
	$checked = in_array("onsale", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="onsale" '.$checked.' /> On Sale</td>';
	$checked = in_array("image", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="image" '.$checked.' /> Image</td>';	
	$checked = in_array("ean", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="ean" '.$checked.' /> ean</td>';
	echo '</tr><tr>';
	$checked = in_array("quantity", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="quantity" '.$checked.' /> Qty</td>';	
	$checked = in_array("price", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="price" '.$checked.' /> Price</td>';
	$checked = in_array("linkrewrite", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="linkrewrite" '.$checked.' /> Link-rewrite</td>';
	$checked = in_array("shipweight", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="shipweight" '.$checked.' />Sh. Weight</td>';
	$checked = in_array("manufacturer", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="manufacturer" '.$checked.' /> Manufacturer</td>';
	$checked = in_array("metatitle", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="metatitle" '.$checked.' /> Metatitle</td>';
	$checked = in_array("metakeywords", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="metakeywords" '.$checked.' /> Metakeys</td>';
	$checked = in_array("metadescription", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="metadescription" '.$checked.' /> Meta Desc</td>';
	$checked = in_array("date_add", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="date_add" '.$checked.' /> Date added</td>';
	$checked = in_array("features", $input["fields"]) ? "checked" : "";
	echo '<!--td><input type="checkbox" name="fields[]" value="features" '.$checked.' onchange="swapFeatures()" /> Features</td-->';
	echo '</tr><tr>';
	
	echo '<td colspan=4>Statistics: Period (yyyy-mm-dd): <input size=5 name=startdate value='.$input['startdate'].'> till <input size=5 name=enddate value='.$input['enddate'].'></td>';
	$checked = in_array("visits", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="visits" '.$checked.' /> Visits</td>';
	$checked = in_array("visitz", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="visitz" '.$checked.' /> Visitz</td>';
	$checked = in_array("salescnt", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="salescnt" '.$checked.' /> Sold</td>';
	$checked = in_array("revenue", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="revenue" '.$checked.' /> Revenue</td>';
	$checked = in_array("orders", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="orders" '.$checked.' /> Orders</td>';	
	$checked = in_array("buyers", $input["fields"]) ? "checked" : "";
	echo '<td><input type="checkbox" name="fields[]" value="buyers" '.$checked.' /> Buyers</td>';
	echo '</tr></table></td><td><input type="submit" value="search" /></form></td>';
	echo '</tr></table></form>';
	
  // "*********************************************************************";
  
  echo '<form name=slctform><table id="advanceddiv" style="border: 1px solid #c3c3c3; width:100%; display:none"><tr><td>Select Rows <input name=startrow size=2> - <input name=endrow size=2>';
  echo ' <input type=button value="Set" onclick="return setrows(1)"> <input type=button value="UnSet" onclick="return setrows(0)">';
  echo ' &nbsp; &nbsp; Move selected rows <input name=movesize size=2> positions';
  echo ' <input type=button value="Up" onclick="return moverows(1)"> <input type=button value="Down" onclick="return moverows(0)"></td></tr>';
  echo '<tr><td>Selected rows: <span id=selectedrows></span></td></tr></table></form>';
  // "*********************************************************************";
  echo '<form name=ListForm><table class="tripleswitch"><tr><td>';
  echo "<input type=button value='Sort' onClick='return CatSort();' title='Sort on number in position field'>";
  echo " &nbsp; &nbsp; <input type=button value='Number' onClick='return CatNumber();' title='Give new position numbers'>";
  echo " &nbsp; &nbsp; <input type=button value='Randomize' onClick='return Randomize();' title='Give random position numbers'>";
  $checked = "";
  if($prestools_settings["autosort"])
    $checked = "checked";
  echo ' &nbsp; &nbsp; autosort <input type=checkbox name=autosort '.$checked.' onchange="switch_autosort()"></td>';
  echo "<td width='40%' align=center><input type=checkbox name=verbose>verbose &nbsp; &nbsp; <input type=button value='Submit all' onClick='return SubmitForm();'></td></tr></table></form>";
  // "*********************************************************************";
  
  dbquery("SET SESSION sql_mode = 'NO_UNSIGNED_SUBTRACTION'"); /* in prod-vissort.php there is some documentation for this */
  
/* Note: we start with the query part after "from". */
$statterms = "";
$querysel = "select DISTINCT p.*,ps.*,pl.*,t.id_tax,t.rate,i.id_image,m.name AS manufacturer, cl.name AS catname";
$querysel .= ", cl.link_rewrite AS catrewrite, pld.name AS originalname, cp.position"; /* note: you cannot write here t.* as t.active will overwrite p.active without warning */

$query = " from ". _DB_PREFIX_."product p";
$query.=" left join ". _DB_PREFIX_."product_lang pl on p.id_product=pl.id_product AND pl.id_lang='".(int)$id_lang."'";
$query.=" LEFT JOIN ". _DB_PREFIX_."product_lang pld ON pld.id_product=p.id_product and pld.id_lang='".$def_lang."'"; /* This gives the name in the shop language instead of the selected language */
$query.=" inner join ". _DB_PREFIX_."product_shop ps on ps.id_product=p.id_product";
$query.=" left join ". _DB_PREFIX_."image i on i.id_product=p.id_product and i.cover=1";
$query.=" left join ". _DB_PREFIX_."manufacturer m on m.id_manufacturer=p.id_manufacturer";
$query.=" left join ". _DB_PREFIX_."category_lang cl on cl.id_category=ps.id_category_default AND cl.id_lang='".(int)$id_lang."'";
$query.=" left join ". _DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=ps.id_tax_rules_group AND tr.id_country='".(int)$id_country."' AND tr.id_state='0'";
$query.=" left join ". _DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
$query.=" left join ". _DB_PREFIX_."tax_lang tl on t.id_tax=tl.id_tax AND tl.id_lang='".(int)$id_lang."'";
$query.=" left join ". _DB_PREFIX_."category_product cp on p.id_product=cp.id_product";
if(in_array("visits", $input["fields"]))
{ $query .= " LEFT JOIN ( SELECT pg.id_object, count(*) AS visitcount FROM ". _DB_PREFIX_."connections c LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type='1' AND pg.id_page = c.id_page";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(c.date_add) > TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(c.date_add) < TO_DAYS('".$input['enddate']."')";
  $statterms .= ", visitcount ";
  $query .= " GROUP BY pg.id_object ) v ON p.id_product=v.id_object";
}
if(in_array("visitz", $input["fields"]))
{ $query .= " LEFT JOIN ( SELECT pg.id_object, sum(counter) AS visitedpages FROM ". _DB_PREFIX_."page_viewed v LEFT JOIN ". _DB_PREFIX_."page pg ON pg.id_page_type='1' AND pg.id_page = v.id_page";
  $query .= " LEFT JOIN ". _DB_PREFIX_."date_range d ON d.id_date_range = v.id_date_range";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(d.time_start) >= TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(d.time_end) <= TO_DAYS('".$input['enddate']."')";
  $statterms .= ", visitedpages ";
  $query .= " GROUP BY v.id_page ) w ON p.id_product=w.id_object";
}
if(in_array("revenue", $input["fields"]) OR in_array("salescnt", $input["fields"]) OR in_array("orders", $input["fields"]))
{ $query .= " LEFT JOIN ( SELECT product_id, SUM(product_quantity)-SUM(product_quantity_return) AS quantity, ";
  $query .= " ROUND(SUM(total_price_tax_incl),2) AS revenue";
  $query .= ", count(DISTINCT d.id_order) AS ordercount, count(DISTINCT o.id_customer) AS buyercount FROM ". _DB_PREFIX_."order_detail d";
  $query .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order";
  $query .= " WHERE true";
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) > TO_DAYS('".$input['startdate']."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) < TO_DAYS('".$input['enddate']."')";
  $query .= " AND o.valid=1";
  $query .= " GROUP BY d.product_id ) r ON p.id_product=r.product_id";
  $statterms .= ", revenue, r.quantity AS salescount, ordercount, buyercount ";
}
  $query .= " WHERE cp.id_category=".$id_category;
  $query.= " GROUP BY ps.id_product";
  $query .= " ORDER BY cp.position";
  $query = $querysel.$statterms.$query;
  $res=dbquery($query);
  $numrecs2 = mysqli_num_rows($res);
  echo "This category contains ".$numrecs2." products.<br/>";
  $previous_order = array();
  $highestposition = 0;
// echo $query;

echo "<script>
function SubmitForm()
{ var reccount = ".$numrecs2.";
  formSubmitting = true; /* prevent error message for leaving page with unsaved changes */
  CatSort();
  var tabbody = document.getElementById('offTblBdy');
  for(var i=0; i<reccount; i++)
  { if(previous_order[i] == tabbody.childNodes[i].childNodes[0].childNodes[0].value) /* remove unchanged row positions */
	{ tabbody.childNodes[i].innerHTML = '';
	  continue;
	}
    tabbody.childNodes[i].childNodes[0].childNodes[0].name = 'id_product'+i; /* change hidden input field names */
    tabbody.childNodes[i].childNodes[2].innerHTML = ''; /* remove position field: we get position from fieldnames like id_product15 */
  }							/* reducing the number of fields doubles the number of records that can be updated until nearly 1000 */
  Mainform.verbose.value = ListForm.verbose.checked;
  Mainform.urlsrc.value = location.href;
  Mainform.action = 'product-proc.php';
  Mainform.submit();
}

function CatNumber()
{ rv = document.getElementsByClassName('posita');
  var length = rv.length;
  for(var i=0; i<length; i++)
  { rv[i].value = i;
  }
  mark_columns();
}

function Randomize()
{ rv = document.getElementsByClassName('posita');
  var length = rv.length;
  var arr = new Array;
  for(var i=0; i<length; i++)
    arr[i] = i;
  shuffle(arr);
  for(var i=0; i<length; i++)
  { rv[i].value = arr[i];
  }
  sortTable('offTblBdy', 2, 2);
}

function CatSort()
{ sortTable('offTblBdy', 2, 2);
  CatNumber();
}

function ChangePosition(elt)
{ if(ListForm.autosort.checked)
  { var tmp = elt.parentNode.parentNode.innerHTML;
  	var mytable = document.getElementById('Maintable');
	if(isNaN(elt.value))
	  var newrow = 1;
	else
	  var newrow = parseInt(elt.value) + 1;
	if(newrow >= mytable.rows.length)
	  newrow = mytable.rows.length-1;
    var row = elt.parentNode.parentNode.rowIndex;
	mytable.deleteRow(row);
	var myrow = mytable.insertRow(newrow);
	myrow.innerHTML = tmp;
	CatNumber();
  }
}

function moveup(elt)
{ var pos = elt.parentNode.parentNode.rowIndex;
  if(pos > 1)
  { var tmp = elt.parentNode.parentNode.innerHTML;
  	var mytable = document.getElementById('Maintable');
	mytable.rows[pos].innerHTML = mytable.rows[pos-1].innerHTML;
	mytable.rows[pos-1].innerHTML = tmp;
	CatNumber();
  }
}

function movedown(elt)
{ var pos = elt.parentNode.parentNode.rowIndex;
  var mytable = document.getElementById('Maintable');
  if(pos <= (mytable.rows.length - 1))
  { var tmp = elt.parentNode.parentNode.innerHTML;
	mytable.rows[pos].innerHTML = mytable.rows[pos+1].innerHTML;
	mytable.rows[pos+1].innerHTML = tmp;
	CatNumber();
  }
}

function switch_autosort()
{
}

function salesdetails(product)
{ window.open('product-sales.php?product='+product+'&startdate=".$input['startdate']."&enddate=".$input['enddate']."','', 'resizable,scrollbars,location,menubar,status,toolbar');
  return false;
}

function allowDrop(ev) 
{   ev.preventDefault();
}

function drag(elt,ev) 
{   ev.dataTransfer.setData('text', elt.parentNode.parentNode.rowIndex);
}

function drop(elt,ev) 
{   ev.preventDefault();
    var oldrow = ev.dataTransfer.getData('text') - 1; /* mind the header row */
    var newrow = elt.rowIndex - 1;					/* mind the header row */
	rv = document.getElementsByClassName('posita');
	if(newrow < oldrow)
	  rv[oldrow].value=newrow-1;
    else 
	  rv[oldrow].value=newrow+1;
    CatSort();  
}

/* the following functions shows a warning when you leave the page while there are unsaved changes */
var formSubmitting = false;
window.onload = function() {
    window.addEventListener('beforeunload', function (e) {
		var reccount = ".$numrecs2.";
        var confirmationMessage = 'You did not submit your changes. ';
        confirmationMessage += 'If you leave before saving, your changes will be lost.';
		
		if (formSubmitting) {
            return undefined;
        }

		var tabbody = document.getElementById('offTblBdy');
		var changed = 0;
		for(var i=0; i<reccount; i++)
		{ if(previous_order[i] != tabbody.childNodes[i].childNodes[0].childNodes[0].value) /* anything changed? */
			changed = 1;
		}
		if(changed == 0)
			return;

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    });
	mark_columns();
};

</script>";
  // "*********************************************************************";

  echo '<form name="Mainform" method=post><input type=hidden name=reccount value="'.$numrecs2.'"><input type=hidden name=id_lang value="'.$id_lang.'">';
  echo '<input type="hidden" name="id_category" value="'.$id_category.'">';
  echo '<input type=hidden name=verbose><input type=hidden name=urlsrc>';
  echo '<div id="testdiv"><table id="Maintable" class="triplemain"><colgroup id="mycolgroup">';
  for($i=0; $i<sizeof($infofields); $i++)
  { $align = $visibility = $classname = "";
    if($infofields[$i][5] == 1)
      $align = ' style="text-align:right"';
	if($infofields[$i][0] == "position")
	  $classname = ' class="numeric"';
	if($infofields[$i][0] == "name")
      $classname = ' class="namecol"';
    echo "<col id='col".$i."'".$align.$visibility.$classname."></col>";
  }

  echo "</colgroup><thead><tr>";

  $statsfound = false; /* flag whether we should create an extra stats totals line */
  $statfields = array("salescnt", "revenue","orders","buyers","visits","visitz");
  $stattotals = array();
  for($i=0; $i<sizeof($infofields); $i++)
  { $reverse = "false";
    if($infofields[$i][0] == "active")
	  $reverse = 1;
    else if (in_array($infofields[$i][0], $statfields))
	{ $reverse = 1;
      $statsfound = true;
	  $stattotals[$infofields[$i][0]] = floatval(0);
	}
    echo '<th><a href="" onclick="return sort_on_column(this, '.$i.', '.$reverse.');" title="'.$infofields[$i][1].'">'.$infofields[$i][0].'</a></th
>';
  }


echo '<th><a href="" onclick="this.blur(); return upsideDown(\'offTblBdy\');" title="Upside down: reverse table order"><img src="upsidedown.jpg"></a></th>';
echo '<th style="display:none">Select</th>';
  echo "</tr></thead><tbody id='offTblBdy'>"; /* end of header */
 
  $x=0;
  while ($datarow=mysqli_fetch_array($res)) { 
    /* Note that trid (<tr> id) cannot be an attribute of the tr as it would get lost with sorting */
    echo '<tr ondrop="drop(this,event)" ondragover="allowDrop(event)">';
	echo '<td id="trid'.$x.'" changed="0"><input type=hidden name="id_product'.$x.'" value="'.$datarow['id_product'].'"></td>';
	$previous_order[$datarow["position"]] = $datarow['id_product'];
	$infofieldsize = sizeof($infofields);
    for($i=1; $i< $infofieldsize; $i++)
    { $sorttxt = "";
      $color = "";
      if($infofields[$i][2] == "priceVAT")
		$myvalue =  number_format(((($datarow['rate']/100) +1) * $datarow['price']),2, '.', '');
      else
        $myvalue = $datarow[$infofields[$i][2]];
      if($i==0)
		echo "<td></td>";
      else if($i == 1) /* id */
	  { if ($rewrite_settings == '1')
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
	      echo "<td><a href='".get_base_uri().$langinsert.$produrl."' title='".$datarow['originalname']."' target='_blank'>".$myvalue."</a></td>";
		}  
		else
          echo "<td><a href='".get_base_uri()."index.php?id_product=".$datarow['id_product']."&controller=product&id_lang=".$id_lang."' title='".$datarow['originalname']."' target='_blank'>".addspaces($myvalue)."</a></td>";
	  }
	  else if($infofields[$i][6] == 1)
      { $sorttxt = "srt='".str_replace("'", "\'",$myvalue)."'";
        echo "<td ".$sorttxt.">".$myvalue."</td>";
      }
      else if ($infofields[$i][0] == "category")
	  { echo "<td ".$sorttxt.">";
	    $cquery = "select id_category from ". _DB_PREFIX_."category_product WHERE id_product='".$datarow['id_product']."' ORDER BY id_category";
		$cres=dbquery($cquery);
		$z=0;
		while ($crow=mysqli_fetch_array($cres)) 
		{	if($z++ > 0) echo ",";
			if ($crow['id_category'] == $myvalue)
				echo "<a title='".$category_names[$myvalue]."' href='#' onclick='return false;'>".$myvalue."</a>";
			else 
				echo "<a title='".$category_names[$crow['id_category']]."' href='#' onclick='return false;' style='text-decoration: none;'>".$crow['id_category']."</a>";
		}
	    echo "</td>";
	  }
      else if ($infofields[$i][0] == "date_add")
		echo "<td ".$sorttxt.">".substr($datarow["date_add"],0,10)."</td>";
      else if ($infofields[$i][0] == "VAT")
      { $sorttxt = "idx='".$datarow['id_tax_rules_group']."'";
		echo "<td ".$sorttxt.">".floatval($myvalue)."</td>";
      }
      else if ($infofields[$i][0] == "image")
      { echo "<td>".get_product_image($datarow['id_product'],$datarow['id_image'],htmlspecialchars($datarow['name']))."</td>";
      }
	  else if ($infofields[$i][0] == "position")
      { echo "<td><input name='position".$x."' value='".$myvalue."' class='posita' onchange='ChangePosition(this)'></td>";
	    $highestposition = $myvalue;
      }
	  else if ($infofields[$i][0] == "revenue")
      { echo "<td><a href onclick='return salesdetails(".$datarow['id_product'].")' title='show salesdetails'>".$datarow['revenue']."</a></td>";
      }
      else
         echo "<td>".$myvalue."</td>";
		 
	  if ( (in_array($infofields[$i][0], $statfields)))
		$stattotals[$infofields[$i][0]] += floatval($myvalue);
    }

    echo '<td><img src=up.png onclick="moveup(this);"><br><img src=mid.png draggable="true" ondragstart="drag(this,event)" style="cursor:move">';
	echo '<br><img src=down.png onclick="movedown(this);"></td>';
	echo '<td style="display:none"><input type=checkbox name="slct'.$x.'" onchange="listselectedrows();"></td>';
    $x++;
    echo '</tr>';
  }
  
  if(mysqli_num_rows($res) == 0)
	echo "<strong>products not found</strong>";
  echo '</table></form></div>
  <script>var previous_order = [';
  for($i=0; $i<=$highestposition; $i++) /* note that PS positions are not always continguous numbers. Note also that Javascript doesn't allow associative arrays */
  { if(isset($previous_order[$i]))
	  echo $previous_order[$i];
	else
	  echo "0";
	if($i != $highestposition) 
	  echo ",";
  }
  echo "];</script> ";
  
  if($statsfound)
  { echo '<table class=triplemain><td colspan=2 style="text-align:center">Totals</td>';
    for($i=0; $i< sizeof($infofields); $i++)
	{ if (in_array($infofields[$i][0], $statfields))
	    echo '<tr><td>'.$infofields[$i][0].'</td><td>'.$stattotals[$infofields[$i][0]].'</td></tr>';
	}
	echo '</table>';
  }
 
  include "footer1.php";
  echo '</body></html>';

?>
