<?php
if(!@include 'approve.php') die( "approve.php was not found!");
if(!include 'ps_sourced_code.php') die( "ps_sourced_code.php was not found!");
if(isset($_POST["batchsize"]))
  $input = $_POST;
else if(isset($_GET["batchsize"]))
  $input = $_GET;
else
{ echo "No Action";
  exit(0);
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
</script></head><body>';
 echo '<a href="#" title="Show the content of this frame in a New Window" onclick="newwin(); return false;">NW</a> ';
 
if($prestools_settings["demo_mode"])
{ echo '<script>alert("The script is in demo mode. Nothing is changed!");</script>';
  exit(0);
}

$product_ids = preg_replace('/[^0-9\-,]/','',$input['product_ids']);
if(trim($product_ids)=="") exit(0);
$shops = implode(",",$input['shops']);
$shops = preg_replace('/[^0-9,]/','',$shops);
if(trim($shops)=="") exit(0);
$batchsize = intval($input['batchsize']);
$startprod = intval($input['startprod']);
if(isset($input['reindexr']))
  $reindexr = true;
else
  $reindexr = false;
shop_reindex($product_ids, $shops, $startprod, $batchsize, $reindexr);


/* shop_reindex() does the same thing what "Add missing products to the index" in the backoffice
 * of Prestashop (Preferences->Search->Indexes) does. Many of the changes you make with product-edit
 * (for example changing names or descriptions) can create the need to redo the indexing for the 
 * affected products. As we can't count on the users doing it timely we do it here.
 * The relevant Prestashop code can be found in the function indexation() in the file classes\Search.php. Similar code can also be found in the function update_shop_index() in the Prestools file ps_sourced_code.
 * This code is deliberately less optimized than in Prestashop in order to make the code better readable.
 * When $langs is not 0 it is assumed that the mentioned langs are the only ones that have been changed. After the indexes for those languages are updated the shop will be set as indexed.
 * $maxtime is in seconds. For all product $productset=''; For all languages and all shops the value is 0. Reset is by default false.
 */
if(!defined('PS_SEARCH_MAX_WORD_LENGTH'))
  define('PS_SEARCH_MAX_WORD_LENGTH', 15);
function shop_reindex($productset, $shops, $startprod, $batchsize, $reset)
{ global $conn, $verbose;
  
  /* calculate the number of shops and langs that are asked. Shops x langs is the minimum number of entries that should be retrieved in one run */
  $beginproduct=0;
  $prodsprocessed = 0;
  $shoparray = explode(",", $shops);
  $completedprodshops = array();
  foreach($shoparray AS $i)
    $completedprodshops[$i] = [];
	
  $weights = array(
    'pname' => get_configuration_value('PS_SEARCH_WEIGHT_PNAME'),
    'reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'supplier_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_supplier_reference' => get_configuration_value('PS_SEARCH_WEIGHT_REF'), 
	'ean13' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_ean13' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'upc' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_upc' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'isbn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'pa_isbn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'mpn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
	'pa_mpn' => get_configuration_value('PS_SEARCH_WEIGHT_REF'),
    'description_short' => get_configuration_value('PS_SEARCH_WEIGHT_SHORTDESC'),
    'description' => get_configuration_value('PS_SEARCH_WEIGHT_DESC'),
    'cname' => "1", /* default category: extra above cnames  */
    'cnames' => get_configuration_value('PS_SEARCH_WEIGHT_CNAME'),	/* all categories */
    'mname' => get_configuration_value('PS_SEARCH_WEIGHT_MNAME'),
    'tags' => get_configuration_value('PS_SEARCH_WEIGHT_TAG'),
    'attributes' => get_configuration_value('PS_SEARCH_WEIGHT_ATTRIBUTE'),
    'features' => get_configuration_value('PS_SEARCH_WEIGHT_FEATURE'));
	
  if ((int)$weights['cname'])	 
	  $weights["cnames"] = ((int)$weights["cnames"])-1;
	
  $p_fields = "p.id_product, pl.id_lang, pl.id_shop, l.iso_code";
  if ((int)$weights['pname'])     			 $p_fields .= ', pl.name AS pname';  
  if ((int)$weights['reference'])    		 $p_fields .= ', p.reference';
  if ((int)$weights['supplier_reference'])   $p_fields .= ', p.supplier_reference';
  if ((int)$weights['ean13'])     			 $p_fields .= ', p.ean13';
  if ((int)$weights['upc'])     			 $p_fields .= ', p.upc';
  if ((int)$weights['description_short'])    $p_fields .= ', pl.description_short';
  if ((int)$weights['description'])		     $p_fields .= ', pl.description';
  if ((int)$weights['cname'])			     $p_fields .= ', cl.name AS cname';
  if ((int)$weights['cnames'])			     $p_fields .= ', GROUP_CONCAT(" ",cl2.name) AS cnames';  
  if ((int)$weights['mname'])			     $p_fields .= ', m.name AS mname';  
	
  $pa_fields = "";
  if ((int)$weights['pa_reference'])	     $pa_fields .= ', pa.reference AS pa_reference';
  if ((int)$weights['pa_supplier_reference']) $pa_fields .= ', pa.supplier_reference AS pa_supplier_reference';
  if ((int)$weights['pa_ean13'])		     $pa_fields .= ', pa.ean13 AS pa_ean13';
  if ((int)$weights['pa_upc'])			     $pa_fields .= ', pa.upc AS pa_upc';
  if(version_compare(_PS_VERSION_ , "1.7.0", ">="))
  { if ((int)$weights['isbn'])   			 $p_fields .= ', p.isbn';
    if ((int)$weights['pa_isbn'])			 $pa_fields .= ', pa.isbn AS pa_isbn';
  }
  if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
  { if ((int)$weights['mpn'])   			 $p_fields .= ', p.mpn';
    if ((int)$weights['pa_mpn'])			 $pa_fields .= ', pa.mpn AS pa_mpn';
  }
  
  
  
  $isFeaturesActive = get_configuration_value('PS_FEATURE_FEATURE_ACTIVE');
  $isCombinationsActive = get_configuration_value('PS_COMBINATION_FEATURE_ACTIVE');  
  $count_words = 0;
  $query_array3 = array();
  $starttime = time();
  /* note that */
  
  if($reset)
  { $query = 'UPDATE '._DB_PREFIX_.'product_shop SET indexed=0';
	$query .= ' WHERE visibility IN ("both", "search")';
	$query .= ' AND `active` = 1';
    $query .= ' AND ('.rangetosql($productset, 'id_product').')';
    $query .= " AND id_shop IN (".$shops.')';
	$query .= " AND id_product >=".$startprod;
	$query .= " ORDER BY id_product";
	$query .= " LIMIT ".$batchsize;
    $res=dbquery($query);
  }
  
  $query = 'SELECT GROUP_CONCAT(DISTINCT p.id_product) AS prods
			FROM '._DB_PREFIX_.'product p
			LEFT JOIN '._DB_PREFIX_.'product_shop ps ON p.id_product = ps.id_product
			WHERE ps.visibility IN ("both", "search")
			AND ps.`active` = 1
			AND ps.indexed = 0
			AND ('.rangetosql($productset, 'p.id_product').')
			AND ps.id_shop IN ('.$shops.')
			AND p.id_product >='.$startprod.'
			ORDER BY p.id_product
			LIMIT '.$batchsize;
  $res=dbquery($query);
  list($myproducts) = mysqli_fetch_row($res);
  if($myproducts === NULL)
  { $pblocks = [];
  }
  else
  { $myprods = explode(",",$myproducts);
	$prodsprocessed = sizeof($myprods);
    $pblocks = array_chunk($myprods, 50); /* Prestashop handles indexation in blocks of 50 */
  }
  $last_product = $last_shop = 0; 
  foreach($pblocks AS $pblock)
  { echo "<br>Time=".date("H:i:s")." [starttime was ".date("H:i:s",$starttime)."]<br>";
  
    $prodlist = implode(",", $pblock);
    $query = 'DELETE si, sw FROM `'. _DB_PREFIX_.'search_index` si
				INNER JOIN `' . _DB_PREFIX_ . 'search_word` sw ON (sw.id_word = si.id_word)
				WHERE si.id_product IN ('.$prodlist.') AND sw.id_shop IN ('.$shops.')';
    $res=dbquery($query);

    $query = 'SELECT '.$p_fields.'
			FROM '._DB_PREFIX_.'product p
			LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
			LEFT JOIN '._DB_PREFIX_.'product_shop ps ON p.id_product = ps.id_product
			LEFT JOIN '._DB_PREFIX_.'category_lang cl
				ON (cl.id_category = ps.id_category_default AND pl.id_lang = cl.id_lang AND cl.id_shop = ps.id_shop)
			LEFT JOIN '._DB_PREFIX_.'category_product cp ON cp.id_product = ps.id_product	
			LEFT JOIN '._DB_PREFIX_.'category_lang cl2
				ON (cp.id_category = cl2.id_category AND pl.id_lang = cl2.id_lang AND cl2.id_shop = ps.id_shop)
			LEFT JOIN '._DB_PREFIX_.'manufacturer m ON m.id_manufacturer = p.id_manufacturer
			LEFT JOIN '._DB_PREFIX_.'lang l ON l.id_lang = pl.id_lang
			WHERE ps.visibility IN ("both", "search")
			AND ps.`active` = 1
			AND pl.`id_shop` = ps.`id_shop`
			AND ps.indexed = 0
			AND p.id_product IN ('.$prodlist.')
			AND ps.id_shop IN ('.$shops.')
			GROUP BY id_product,id_shop,id_lang
			ORDER BY l.active DESC, ps.id_product, ps.id_shop, pl.id_lang';

    $res=dbquery($query);
	$numrecs = mysqli_num_rows($res);
	if($numrecs==0) return update_unindexed_counter(0); /* quit the eternal loop */
    if($verbose == "true")
       echo "<br>The query delivered ".mysqli_num_rows($res)." rows</br>";
    $x = 0; /* our record number */

    $last_product = $last_shop = 0;
    while($product = mysqli_fetch_assoc($res))
	{ /* Prestashop assumes that we finish indexing everything. In Prestools
		however, we index only a few seconds in order to keep the user 
		experience acceptable. This means some extra code: */
	  if($beginproduct==0) $beginproduct = $product["id_product"];
	  if($verbose == "true") 
	    echo "<br><b>prod=".$product["id_product"]."-shop=".$product["id_shop"]."-lang=".$product["id_lang"]."</b>,";
	  else
	  { echo $product["id_product"]."-".$product["id_shop"]."-".$product["id_lang"].",";
	    if(!(($x-1)%16)) echo " <br>";
	  }
  	  if((($product["id_product"] != $last_product) || ($product["id_shop"] != $last_shop)))
	  { if(($last_product != 0) && ($last_shop != 0))
		  $completedprodshops[(int)$last_shop][] = (int)$last_product;
		$last_product = $product["id_product"];
		$last_shop = $product["id_shop"]; 
	  }
	  $x++;

	  if ((int)$weights['tags'])
	  { $tquery = 'SELECT GROUP_CONCAT(" ",t.name) AS ptags FROM '._DB_PREFIX_.'product_tag pt
		LEFT JOIN '._DB_PREFIX_.'tag t ON (pt.id_tag = t.id_tag AND t.id_lang = '.(int)$product['id_lang'].')
		WHERE pt.id_product = '.(int)$product['id_product'].'
		GROUP BY pt.id_product';
		$tres = dbquery($tquery);
		if(mysqli_num_rows($tres) > 0)
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
		if(mysqli_num_rows($ares) > 0)
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
		if(mysqli_num_rows($fres) > 0)
		{ $frow = mysqli_fetch_assoc($fres);
		  $product['features'] = $frow['fvlvalues'];
		}
      }
      if ((int)$weights['supplier_reference']) 
	  { $srquery = 'SELECT GROUP_CONCAT(" ",product_supplier_reference) AS srvalues FROM '._DB_PREFIX_.'product_supplier
		WHERE id_product = '.(int)$product['id_product'].'
		GROUP BY id_product';
 		$srres = dbquery($srquery);
		if(mysqli_num_rows($srres) > 0)
		{ $srrow = mysqli_fetch_assoc($srres);
		  $product['supplier_reference'] = $srrow['srvalues'];
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
	  $product_array = array(); /* storage for the keywords and their weights */
	  foreach ($product as $key => $value) 
	  {	if ($key == 'attributes_fields')
	    { foreach ($value as $pa_array)
		  {	foreach ($pa_array as $pa_key => $pa_value) 
		    { fillMyProductArray($product_array, $weights, $pa_key, $pa_value, $product['id_lang'], $product['iso_code']);
			}
		  }
		} 
		else 
		{ fillMyProductArray($product_array, $weights, $key, $value, $product['id_lang'], $product['iso_code']);
		}
	  }
  
	  // If we find words that need to be indexed, they're added to the word table in the database
	  if (is_array($product_array) && !empty($product_array)) 
	  {	$query_array = $query_array2 = array();
		foreach ($product_array as $word => $weight) 
		{ if ($weight) 
		  { $query_array[$word] = '('.(int)$product['id_lang'].', '.(int)$product['id_shop'].', \''.mysqli_real_escape_string($conn,$word).'\')';
			$query_array2[] = '\''.mysqli_real_escape_string($conn,$word).'\'';
		  }
		}

		if (is_array($query_array) && !empty($query_array)) 
		{	// The words are inserted...
			$swquery = 'INSERT IGNORE INTO '._DB_PREFIX_.'search_word (id_lang, id_shop, word)
			VALUES '.implode(',', $query_array);
			$swres = dbquery($swquery);
		}
		$word_ids_by_word = array();
		if (is_array($query_array2) && !empty($query_array2)) 
		{	// ...then their IDs are retrieved
			$added_words = '';
			$wquery = 'SELECT sw.id_word, sw.word
			FROM '._DB_PREFIX_.'search_word sw
			WHERE sw.word IN ('.implode(',', $query_array2).')
			AND sw.id_lang = '.(int)$product['id_lang'].'
			AND sw.id_shop = '.(int)$product['id_shop'];
			$wres = dbquery($wquery);	
			while($wrow = mysqli_fetch_assoc($wres))
			{ $word_ids_by_word['_'.$wrow['word']] = (int)$wrow['id_word'];
			}
		}
	  } 
	  foreach ($product_array as $word => $weight) 
	  {	if (!$weight) continue;
		if (!isset($word_ids_by_word['_'.$word])) continue;
		$id_word = $word_ids_by_word['_'.$word];
		if (!$id_word) 	continue;
		$query_array3[] = '('.(int)$product['id_product'].','.
			(int)$id_word.','.(int)$weight.')';
		// Force save every 200 words in order to avoid overloading MySQL
		if (++$count_words % 200 == 0)
		{	saveMyIndex($query_array3);
		}
	  }
	} /* end while fetch product */
	
	if(($numrecs != $batchsize) && ($last_product != 0) && ($last_shop != 0))
	  $completedprodshops[(int)$last_shop][] = (int)$last_product;
  
	// One last save is done at the end in order to save what's left
	saveMyIndex($query_array3);
	
	/* we update here only the indexed field for ps_product_shop */
	/* updating the outdated indexed field for ps_product happens in update_unindexed_counter() */
	foreach($shoparray AS $id_shop)
	{ if (count($completedprodshops[$id_shop]) > 0) 
	  { $uquery = 'UPDATE '._DB_PREFIX_.'product_shop SET indexed = 1 WHERE id_product IN ('.implode(",", $completedprodshops[$id_shop]).') AND id_shop='.$id_shop;
	    $ures = dbquery($uquery);
		$completedprodshops[$id_shop] = [];
	  }
    }
  } /* end while true */
  
  echo '<script>parent.dynamo2("'.$beginproduct.'-'.$last_product.'-'.$prodsprocessed.'");</script>';
  return;
}


    function fillMyProductArray(&$product_array, $weight_array, $key, $value, $id_lang, $iso_code)
    {
        if (strncmp($key, 'id_', 3) && isset($weight_array[$key])) {
            $words = explode(' ', sanitize_index_text($value, (int)$id_lang, true, $iso_code));
            foreach ($words as $word) {
                if (!empty($word)) {
                    $word = tools_substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH);

                    if (!isset($product_array[$word])) {
                        $product_array[$word] = 0;
                    }
                    $product_array[$word] += $weight_array[$key];
                }
            }
        }
	}
	
    /** $queryArray3 is automatically emptied in order to be reused immediatly */
	/* saveIndex is a modified copy of Prestashops Search::saveIndex */
    function saveMyIndex(&$queryArray3)
    { if (is_array($queryArray3) && !empty($queryArray3)) 
	  { $query = 'INSERT INTO '._DB_PREFIX_.'search_index (id_product, id_word, weight)
				VALUES '.implode(',', $queryArray3).'
				ON DUPLICATE KEY UPDATE weight = VALUES(weight)';
		$res = dbquery($query);
      }
      $queryArray3 = array();
    }

	