<?php 
  error_reporting(E_ALL); 
if(!include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");

if (isset($_GET['id_product']))
{ $products = preg_replace('/[^0-9,\-]+/',"",$_GET['id_product']);
}
else
  $products='';

$id_lang=$id_shop =0;
if (isset($_GET['id_shop']))
  $id_shop = intval($_GET['id_shop']);
if (isset($_GET['id_lang']))
  $id_lang = intval($_GET['id_lang']);
if($id_lang==0) 
  $id_lang = get_configuration_value('PS_LANG_DEFAULT'); 
if($id_shop==0) 
  $id_shop = get_configuration_value('PS_SHOP_DEFAULT'); 

/* section 2: page header */
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Search Word Check</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
</style>
<script type="text/javascript">
function reindexate(ispan)
{ //var prod = selectform.id_product.value;
  var elt = document.getElementById(ispan+'span');
  if(!elt) { alert('Not recognized!'); return; }
  var txt = elt.innerHTML;
  txt = txt.replace( /(<([^>]+)>)/ig, '');
  txt = txt.substring(0,txt.length-1); /* remove trailing comma */
  var shop = selectform.id_shop.value;
  var lang = selectform.id_lang.value;
  var verbose = "";
  if(selectform.verbose.checked)
	verbose = "&verbose=on";
  tank.location = "utilities-proc.php?subject=indexate&id_product="+txt+"&id_lang="+lang+"&id_shop="+shop+"&reset=1"+verbose;
}
</script>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>
<body>
<?php print_menubar(); 
  echo '<table width="100%"><tr><td  class="headline" style="width:90%">
  <a href="prodwordcheck.php">Product Search Word Check</a><br>';
  echo 'This page compares the search words found by Prestools with those presently implemented in the database.<br> 
  It is an easy way to find deviations that you can then visually check and correct.<br>
  You can enter product id\'s and ranges like: 1,4,7,8-28,44-55,89<br>
  Many differences concern only weights. For that reason there is a separate list with products that have different keywords.<br>
  Note that the check happens for one shop-language combination while re-indexation applies to all of them.</td>
<td align=right rowspan=3><iframe name="tank" id="tank" height="95" width="230"></iframe></td></tr></table>';
  echo '<form name="selectform">';
  if($products == "")
    echo 'Product id\'s <input name=id_product value="1-9999" size=15 > &nbsp; &nbsp; ';
  else
    echo 'Product id\'s <input name=id_product value="'.$products.'" size=15 > &nbsp; &nbsp; ';
  
  echo 'shop <select name=id_shop>';
	$query=" select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	while ($row=mysqli_fetch_array($res)) 
	{ $selected='';
      if ($row['id_shop']==$id_shop) 
		$selected=' selected="selected" ';
      echo '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['id_shop']."-".$row['name'].'</option>';
	}
  echo '</select> &nbsp; &nbsp; ';

  echo 'language <select name=id_lang>';
  $query = "SELECT id_lang, name, language_code, iso_code FROM ". _DB_PREFIX_."lang";
  $res=dbquery($query);
  while ($row=mysqli_fetch_array($res)) 
  { $selected='';
    if ($row['id_lang']==$id_lang) 
      $selected=' selected="selected" ';
    echo '<option  value="'.$row['id_lang'].'" '.$selected.'>'.$row['name'].'</option>';
  }
  echo '</select> &nbsp; &nbsp; ';
  echo '<input type=submit>';
  echo '<br><input type=checkbox name=verbose> verbose</form><p>';
  
  if(($products == '') || ($id_shop == 0) || ($id_lang == 0))
  { include "footer1.php";
    echo '</body></html>';
	return;
  }
  
   $productset=preg_replace('/[^0-9,\-]+/','',$products);
//   echo "===".$productset."---";
   if($productset=="")
     colordie('No products!<script>alert("No products!");</script>');
   $invalids = $validprods = $ranges = array();
   $prods = explode(",",$productset);
   foreach($prods AS $prod)
   { if(strpos($prod, '-') !== false)
	 { $parts = explode("-", $prod);
	   if(!is_numeric($parts[0]))
	   { $invalids[] = $parts[0];
	     continue;
	   }
	   if(!is_numeric($parts[1]))
	   { $invalids[] = $parts[1];
	     continue;
	   }
	   $ranges[] = array($parts[0],$parts[1]);
	 }
     else if(is_numeric($prod))
	 { $validprods[] = $prod;
	 }
	 else
	   $invalids[] = $prod;
   }

   if(sizeof($invalids) > 0)
   { echo '<script>alert("The following are not valid product id\'s: '.implode(",",$invalids).'!");</script>';
   }
   if((sizeof($validprods) == 0) && (sizeof($ranges) == 0))
     return;
   $prodquery = "SELECT ps.id_product FROM "._DB_PREFIX_."product_shop ps";
   $prodquery .= " LEFT JOIN "._DB_PREFIX_."product_lang pl on ps.id_product=pl.id_product and ps.id_shop=pl.id_shop";
   $prodquery .= " WHERE visibility IN ('both','search') AND active=1 AND ps.id_shop=".$id_shop." AND id_lang=".$id_lang." AND indexed=1 AND (";
   $first = true;
   if(sizeof($validprods) > 0)
   { $prodquery .= "ps.id_product IN (".implode(",",$validprods).")";
     $first = false;
   }
   foreach($ranges AS $range)
   { if($first) $first=false; else $query .= " OR ";
     $prodquery .= "(ps.id_product >= ".$range[0]." AND ps.id_product <= ".$range[1].")";
   }
   $prodquery .= ")";
   $prodres=dbquery($prodquery);
   echo mysqli_num_rows($prodres)." products found that are both active, have visibility and are indexed.";
   if(sizeof($invalids) > 0)
   { echo '<script>alert("The following products are either inactive or have a wrong value for the visibility field: '.implode(",",$invalids).'!");</script>';
   }
   if(mysqli_num_rows($prodres) == 0)
   { echo '<script>alert("No indexable products found!");</script>';
     return;
   }
   
  /* prepare fixed parts of loop */
  $weights = array(
    'pname' => get_configuration_value('PS_SEARCH_WEIGHT_PNAME'),
    'reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'supplier_references' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
//	'pa_supplier_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'), // included in supplier_references
	'ean13' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_ean13' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'upc' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_upc' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'description_short' => get_configuration_value('PS_SEARCH_WEIGHT_SHORTDESC'),
    'description' => get_configuration_value('PS_SEARCH_WEIGHT_DESC'),
    'cname' => get_configuration_value('PS_SEARCH_WEIGHT_CNAME'),	
	'cnames' => $prestools_settings["PS_SEARCH_WEIGHT_CATNAMES"],  /* all categories */
    'mname' => get_configuration_value('PS_SEARCH_WEIGHT_MNAME'),
    'tags' => get_configuration_value('PS_SEARCH_WEIGHT_TAG'),
    'attributes' => get_configuration_value('PS_SEARCH_WEIGHT_ATTRIBUTE'),
    'features' => get_configuration_value('PS_SEARCH_WEIGHT_FEATURE'));
	
  $p_fields = "p.id_product, pl.id_lang, pl.id_shop, l.iso_code";
  if ((int)$weights['pname'])     			 $p_fields .= ', pl.name AS pname';  
  if ((int)$weights['reference'])    		 $p_fields .= ', p.reference';
  if ((int)$weights['ean13'])     			 $p_fields .= ', p.ean13';
  if ((int)$weights['upc'])     			 $p_fields .= ', p.upc';
  if ((int)$weights['description_short'])    $p_fields .= ', pl.description_short';
  if ((int)$weights['description'])		     $p_fields .= ', pl.description';
  if ((int)$weights['cname'])			     $p_fields .= ', cl.name AS cname';
  if ((int)$weights['cnames'])			     $p_fields .= ', GROUP_CONCAT(" ",cl2.name) AS cnames';  
  if ((int)$weights['mname'])			     $p_fields .= ', m.name AS mname';  
	
  $pa_fields = "";
  if ((int)$weights['pa_reference'])	     $pa_fields .= ', pa.reference AS pa_reference';
  if ((int)$weights['pa_ean13'])		     $pa_fields .= ', pa.ean13 AS pa_ean13';
  if ((int)$weights['pa_upc'])			     $pa_fields .= ', pa.upc AS pa_upc';
  
  $isFeaturesActive = get_configuration_value('PS_FEATURE_FEATURE_ACTIVE');
  $isCombinationsActive = get_configuration_value('PS_COMBINATION_FEATURE_ACTIVE');  
  
  $alldiffers = array();
  $worddiffers = array();
  $megadiffers = array();
  echo '<p>Product id\'s where the indexation differs: ';
  while ($prodrow=mysqli_fetch_array($prodres)) 
  { $id_product = $prodrow["id_product"];
  
    $dbkeys = array();
    $pquery = 'SELECT si.id_word, weight, word FROM '._DB_PREFIX_.'search_index si';
    $pquery .= ' LEFT JOIN '._DB_PREFIX_.'search_word sw ON (sw.id_word=si.id_word)';
    $pquery .= ' WHERE si.id_product = '.$id_product.' AND sw.id_shop = '.$id_shop.' AND sw.id_lang = '.$id_lang;
    $pquery .= ' ORDER BY word';
    $pres = dbquery($pquery);
   
    while($prow = mysqli_fetch_assoc($pres))
    { $dbkeys[$prow["word"]] = $prow["weight"];
    }
  
    $query = 'SELECT '.$p_fields.'
			FROM '._DB_PREFIX_.'product p
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
			LEFT JOIN '._DB_PREFIX_.'product_shop ps ON p.id_product = ps.id_product AND pl.id_shop=ps.id_shop
			LEFT JOIN '._DB_PREFIX_.'category_lang cl
				ON (cl.id_category = ps.id_category_default AND pl.id_lang = cl.id_lang AND cl.id_shop = ps.id_shop)
			LEFT JOIN '._DB_PREFIX_.'category_product cp ON cp.id_product = ps.id_product';
	if($weights['cnames'] > 0)
		$query .= '	LEFT JOIN '._DB_PREFIX_.'category_lang cl2
				ON (cp.id_category = cl2.id_category AND pl.id_lang = cl2.id_lang AND cl2.id_shop = ps.id_shop)';
	$query .= ' LEFT JOIN '._DB_PREFIX_.'manufacturer m ON m.id_manufacturer = p.id_manufacturer
			LEFT JOIN '._DB_PREFIX_.'lang l ON l.id_lang = pl.id_lang
			WHERE pl.`id_product` = '.$id_product.' AND pl.`id_shop` = '.$id_shop.' 
			AND pl.`id_lang` = '.$id_lang.'
			GROUP BY id_product,id_shop,id_lang
			ORDER BY l.active DESC, ps.id_product, ps.id_shop, pl.id_lang';

    $res=dbquery($query);
	$product = mysqli_fetch_assoc($res);

	  if ((int)$weights['tags'])
	  { $tquery = 'SELECT GROUP_CONCAT(" ",t.name) AS ptags FROM '._DB_PREFIX_.'product_tag pt
		LEFT JOIN '._DB_PREFIX_.'tag t ON (pt.id_tag = t.id_tag AND t.id_lang = '.(int)$product['id_lang'].')
		WHERE pt.id_product = '.(int)$product['id_product'].'
		GROUP BY pt.id_product';
		$tres = dbquery($tquery);
		if(mysqli_num_rows($tres) == 0)
		{}
		else
		{ $trow = mysqli_fetch_assoc($tres);
		  $product['tags'] = $trow['ptags'];
		}
	  } 
      if (((int)$weights['attributes']) && $isCombinationsActive)
	  { $aquery = 'SELECT GROUP_CONCAT(" ",al.name) AS atnames FROM '._DB_PREFIX_.'product_attribute pa
		INNER JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
		INNER JOIN '._DB_PREFIX_.'attribute_lang al ON (pac.id_attribute = al.id_attribute AND al.id_lang = '.(int)$product['id_lang'].')
		INNER JOIN '._DB_PREFIX_.'product_attribute_shop pas ON (pa.id_product_attribute = pas.id_product_attribute AND id_shop='.(int)$product['id_shop'].')
		WHERE pa.id_product = '.(int)$product['id_product'].'
		GROUP BY pa.id_product';
		$ares = dbquery($aquery);
		if(mysqli_num_rows($ares) == 0)
		{}
		else
		{ $arow = mysqli_fetch_assoc($ares);
		  $product['attributes'] = $arow['atnames'];
		}
      }
      if (((int)$weights['features']) && $isFeaturesActive)
	  { $fquery = 'SELECT GROUP_CONCAT(" ",fvl.value) AS fvlvalues FROM '._DB_PREFIX_.'feature_product fp
		LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = '.(int)$product['id_lang'].')
		WHERE fp.id_product = '.(int)$product['id_product'].'
		GROUP BY fp.id_product';
		$fres = dbquery($fquery);
		if(mysqli_num_rows($fres) == 0)
		{}
		else
		{ $frow = mysqli_fetch_assoc($fres);
		 $product['features'] = $frow['fvlvalues'];
		}
      }
      if ((int)$weights['supplier_references']) 
	  { $srquery = 'SELECT GROUP_CONCAT(" ",product_supplier_reference) AS srvalues FROM '._DB_PREFIX_.'product_supplier
		WHERE id_product = '.(int)$product['id_product'].'
		GROUP BY id_product';
 		$srres = dbquery($srquery);
		if(mysqli_num_rows($srres) == 0)
		{}
		else
		{ $srrow = mysqli_fetch_assoc($srres);
		  $product['supplier_references'] = $srrow['srvalues'];
		}		  
	  }
	  if ($pa_fields != "") 
	  { $afquery = 'SELECT id_product '.$pa_fields.'
		FROM '._DB_PREFIX_.'product_attribute pa WHERE pa.id_product = '.(int)$product['id_product'];
		$afres = dbquery($afquery);
		if(mysqli_num_rows($afres)>0)
		{ while($afrow = mysqli_fetch_assoc($afres))
		  {	$product['attributes_fields'][] = $afrow;
		  }
		}
	  }

	  // Data must be cleaned of html, bad characters, spaces and anything, then if the resulting words are long enough, they're added to the array
	  $product_array = array();
	  foreach ($product as $key => $value) 
	  {	if ($key == 'attributes_fields')
	    { foreach ($value as $pa_array)
		  {	foreach ($pa_array as $pa_key => $pa_value) 
		    { fillProductArray($product_array, $weights, $pa_key, $pa_value, $product['id_lang'], $product['iso_code']);
			}
		  }
		} 
		else 
		{ fillProductArray($product_array, $weights, $key, $value, $product['id_lang'], $product['iso_code']);
		}
	  }
	  
	  ksort($product_array, SORT_STRING);
	  $different = false;
	  if(sizeof($product_array) != sizeof($dbkeys))
	  { $different = true;
	    $worddiffers[] = $id_product;
		$diff = sizeof($product_array) - sizeof($dbkeys);
		if(abs($diff) >= 4)
		  $megadiffers[] = $id_product;
	  }
	  else 
	  { foreach($product_array AS $word => $weight)
		{ if(!isset($dbkeys[$word]))
		  { $different = true; 
		    $worddiffers[] = $id_product;
			break;
		  }
		  else if($dbkeys[$word] != $weight)
		  { $different = true; 
		    break;
		  }
		}
	  }
	  if($different) $alldiffers[]= $id_product;
  }
  
  if(sizeof($alldiffers) > 0)
  { echo "<p>".sizeof($alldiffers)." products where the indexation weights differ: <span id=allspan>";
    foreach($alldiffers AS $alldiffer)
	{ echo "<a href=prodwords.php?id_product=".$alldiffer."&id_shop=".$id_shop."&id_lang=".$id_lang." target=_blank>".$alldiffer."</a>,";
	}
	echo "</span><br>";
    echo '<button onclick="reindexate(\'all\'); return false;">Re-index</button>';
  }

  if(sizeof($worddiffers) > 0)
  { echo "<p>".sizeof($worddiffers)." products with different keywords: <span id=wordspan>";
    foreach($worddiffers AS $worddiffer)
	{ echo "<a href=prodwords.php?id_product=".$worddiffer."&id_shop=".$id_shop."&id_lang=".$id_lang." target=_blank>".$worddiffer."</a>,";
	}
	echo "</span><br>";
    echo '<button onclick="reindexate(\'word\'); return false;">Re-index</button>';
  }

  if(sizeof($megadiffers) > 0)
  { echo "<p>".sizeof($megadiffers)." products with 4 or more different keywords: <span id=megaspan>";
    foreach($megadiffers AS $megadiffer)
	{ echo "<a href=prodwords.php?id_product=".$megadiffer."&id_shop=".$id_shop."&id_lang=".$id_lang." target=_blank>".$megadiffer."</a>,";
	}
	echo "</span><br>";
    echo '<button onclick="reindexate(\'mega\'); return false;">Re-index</button>';
  } 

  echo "<p>Finished";
	  
	  
  include "footer1.php";
  echo '</body></html>';
