<?php 
/* This script - part of Prestools - gives a list of all the products bought within a certain period */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";
if(!isset($input['search_fld']))
	$search_fld="0";
else 
	$search_fld = mysqli_real_escape_string($conn, $input['search_fld']);
if(!isset($input['search_txt']))
	$search_txt="";
else 
	$search_txt = mysqli_real_escape_string($conn, $input['search_txt']);
$orderoptions = array("sales", "product_name","product_id","quantity");
if(!isset($input['order']) || (!in_array($input['order'], $orderoptions)))
  $input['order']="sales";
if(!isset($input['rising'])) 
{ if(($input['order']=="sales") || ($input['order']=="quantity"))
	$input['rising'] = "DESC";
  else
	$input['rising'] = "ASC";
}
if(!isset($input['fields'])) /* if no extra fields */
  $input['fields'] = array();
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0"; else $input['startrec'] = intval(trim($input['startrec']));
if(!isset($input['numrecs']) || (intval(trim($input['numrecs']) == '0'))) $input['numrecs']="1000";
if((!isset($input['paystatus'])) || ($input['paystatus'] == "valid")) {$paystatus = "valid";} else {$paystatus = $input['paystatus'];}

  $query = "SELECT DISTINCT current_state FROM ". _DB_PREFIX_."orders ORDER BY current_state";
  $res = dbquery($query);
  $myorderstates = array();
  while($row = mysqli_fetch_array($res))
  { $currentstate = $row['current_state'];
	if(!isset($_GET["orderstate"]) || isset($_GET["orderstate"][$currentstate]))
	{	$myorderstates[] = $currentstate;
	}
  }

$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];

	
	if($input['order'] == "sales")
		$order = "pricetotal";
	else if($input['order'] == "product_name")		
		$order = "product_name";
	else if($input['order'] == "product_id")			
		$order = "product_id";
	else if($input['order'] == "quantity")			
		$order = "quantitytotal";
		
$scond = "";
if($search_fld != "0")
{ $ids = explode(',',$search_txt);
  $sids = array();
  $ranges = array();
  $subcats = array();
  foreach($ids AS $id)
  { if(strpos($id, '-') !== false)
	{ $parts = explode("-", $id);
	  if(!is_numeric($parts[0]) || !is_numeric($parts[1])) continue;
	  $ranges[] = array($parts[0],$parts[1]);
	}
	else if(strpos($id, 's') !== false)
	{ $id = str_replace('s', '', $id);
	  if($id != "")
	    $subcats[] = $id;
	}
	else
	  $sids[] = $id;
  }
  if(($search_fld == "id_product") && ((sizeof($sids)>0)||(sizeof($ranges)>0)))
  { $scond .= " AND (";
    $first = true;
    if(sizeof($sids) > 0)
	{ $scond .= " ps.id_product IN (".implode(",",$sids).")";
	  $first = false;
	}
    foreach($ranges AS $range)
	{ if($first) $first=false; else $qcond .= " OR ";
	  $scond .=  " (ps.id_product > ".$range[0]." AND ps.id_product < ".$range[1].")";
	}
	$scond .= ")";
  }
  if((($search_fld == "id_category") || ($search_fld == "cat_default")) && ((sizeof($sids)>0)||(sizeof($subcats)>0)))
  { $scond .= " AND (";
	foreach($subcats AS $subcat)
	{ get_subcats($subcat, $sids);
	}
    if(sizeof($sids) > 0)
	{ if($search_fld == "id_category")
	    $scond .= " cp.id_category IN (".implode(",",$sids).")";
	  else
	    $scond .= " ps.id_category_default IN (".implode(",",$sids).")";
	}
	$scond .= ")";
  }  
}

$fields = "d.product_id, d.product_attribute_id, d.product_name, ps.id_category_default, d.product_price";
$fields .= ", SUM(total_price_tax_incl/conversion_rate) AS pricetotal, SUM(total_price_tax_excl/conversion_rate) AS pricetotalex";
$fields .= ", SUM(product_quantity) AS quantitytotal, count(o.id_order) AS ordercount ";
$fields .= ",SUM(product_quantity_return) AS returns,SUM(product_quantity_refunded) AS refunds";
$qtables = " FROM ". _DB_PREFIX_."order_detail d";
$qtables .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_order = d.id_order";
$qtables .= " LEFT JOIN ". _DB_PREFIX_."product_shop ps ON ps.id_product = d.product_id AND ps.id_shop=o.id_shop";
if(in_array("ean", $input["fields"]) || in_array("upc", $input["fields"]) || in_array("ref", $input["fields"]) || in_array("mpn", $input["fields"]) || in_array("supplref", $input["fields"]))
{ $qtables .= " LEFT JOIN ". _DB_PREFIX_."product p ON d.product_id = p.id_product";
  $qtables .= " LEFT JOIN ". _DB_PREFIX_."product_attribute pa ON d.product_attribute_id = pa.id_product_attribute";
  $fields .= ",p.ean13,p.upc,p.reference,p.supplier_reference,pa.ean13 AS pa_ean13,pa.upc AS pa_upc,pa.reference AS pa_reference,pa.supplier_reference AS pa_supplier_reference";
  if(version_compare(_PS_VERSION_ , "1.7.7", ">="))
    $fields .= ",p.mpn, pa.mpn AS pa_mpn";
}
if(($search_fld == "id_category") && ((sizeof($sids)>0)||(sizeof($subcats)>0)))
{ $qtables .= " LEFT JOIN ". _DB_PREFIX_."category_product cp ON cp.id_product=ps.id_product";
}	
if(isset($_GET["allorderstates"]) || !isset($_GET["orderstate"]))
  $qcond = " WHERE 1";
else if(sizeof($myorderstates) > 0)
  $qcond = " WHERE o.current_state IN (".implode(",",$myorderstates).")";
else
  $qcond = " WHERE 0";
  
if($paystatus == "valid")
  $qcond .= " AND o.valid=1";
else if ($paystatus == "not paid")
  $qcond .= " AND o.valid=0";

if($id_shop !=0)
	$qcond .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $qcond .= " AND TO_DAYS(o.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $qcond .= " AND TO_DAYS(o.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$qcond .= $scond;

$qtail = " GROUP BY product_id,product_attribute_id";
$qtail .= " ORDER BY ".$order." ".$input['rising'];
$qtail .= " LIMIT ".$input['startrec'].",".$input['numrecs'];
//$verbose=true;
$res=dbquery("SELECT SQL_CALC_FOUND_ROWS ".$fields.$qtables.$qcond.$qtail);

/* section 6: write http header */
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=products-sold-'.date('Y-m-d-Gis').'.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
	
/* section 7: write csv headers */
$infofields = array("id","Attr","Name","category","Quant","p.price","Sales","Sales/tax","returns","refunds", "refunded", "orders");
if(in_array("ean", $input["fields"])) $infofields[] = "ean";
if(in_array("upc", $input["fields"])) $infofields[] = "upc";
if(in_array("mpn", $input["fields"])) $infofields[] = "mpn";
if(in_array("ref", $input["fields"])) $infofields[] = "reference";
if(in_array("supplref", $input["fields"])) $infofields[] = "suppl.ref";
if(in_array("stock", $input["fields"])) $infofields[] = "stock";

  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }
  $csvline = array();  // array for the fputcsv function
  for($i=0; $i<sizeof($infofields); $i++)
  { $csvline[] = $infofields[$i];
  }
  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);

  while($datarow = mysqli_fetch_array($res))
  { $csvline = array();
	$csvline[] = $datarow["product_id"];
	$csvline[] = $datarow["product_attribute_id"];
	$csvline[] = $datarow["product_name"];
    $csvline[] = $datarow["id_category_default"];
    $csvline[] = $datarow["quantitytotal"];
//	$sumquantity += intval($datarow["quantitytotal"]);
	$csvline[] = number_format(($datarow["pricetotal"]/$datarow["quantitytotal"]),2,".","");
//	$sumtotal += $datarow["pricetotal"];
	$csvline[] = number_format($datarow["pricetotal"],2,".","");
//	$sumtotalex += $datarow["pricetotalex"];
    $csvline[] = number_format($datarow["pricetotalex"],2,".","");
    $csvline[] = $datarow["returns"];
	$csvline[] = $datarow["refunds"];
	$tquery = "SELECT SUM(amount_tax_incl/conversion_rate) AS refunded";
	$tquery .= " FROM "._DB_PREFIX_."order_slip_detail osd";
	$tquery .= " LEFT JOIN "._DB_PREFIX_."order_detail od ON osd.id_order_detail=od.id_order_detail";
	$tquery .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
	$tquery .= " WHERE od.product_id=".$datarow["product_id"];
	if($datarow["product_attribute_id"] > 0) 
	  $tquery .= " AND od.product_attribute_id=".$datarow["product_attribute_id"];
	$tres=dbquery($tquery);
	$trow = mysqli_fetch_array($tres);
	if($trow["refunded"] > 0)
	  $csvline[] = number_format($trow["refunded"],2);
    else
	  $csvline[] = '';
    $csvline[] = $datarow["ordercount"];
	if($datarow["product_attribute_id"] == 0)
	{ if(in_array("ean", $input["fields"])) $csvline[] = $datarow["ean13"];	
      if(in_array("upc", $input["fields"])) $csvline[] = $datarow["upc"];	
      if(in_array("mpn", $input["fields"])) $csvline[] = $datarow["mpn"];		
      if(in_array("ref", $input["fields"])) $csvline[] = $datarow["reference"];
      if(in_array("supplref", $input["fields"])) $csvline[] = $datarow["supplier_reference"];
	}
	else 
	{ if(in_array("ean", $input["fields"])) $csvline[] = $datarow["pa_ean13"];	
      if(in_array("upc", $input["fields"])) $csvline[] = $datarow["pa_upc"];	
      if(in_array("mpn", $input["fields"])) $csvline[] = $datarow["pa_mpn"];		
      if(in_array("ref", $input["fields"])) $csvline[] = $datarow["pa_reference"];
      if(in_array("supplref", $input["fields"])) $csvline[] = $datarow["pa_supplier_reference"];
	}
    if(in_array("stock", $input["fields"]))
    { if($id_shop == 0)
	  { $stquery = "SELECT SUM(quantity) AS quantity FROM "._DB_PREFIX_."stock_available";
		$stquery .= " WHERE id_product=".$datarow["product_id"].' AND id_product_attribute='.$datarow["product_attribute_id"];
	  }
	  else
	  { $stquery = "SELECT quantity FROM "._DB_PREFIX_."stock_available";
		$stquery .= " WHERE id_product=".$datarow["product_id"].' AND id_product_attribute='.$datarow["product_attribute_id"];
		if($share_stock == 0)
		  $stquery.=" AND sa.id_shop = ps.id_shop";
		else
		  $stquery.=" AND sa.id_shop_group = '".$id_shop_group."'";
	  }
	  $stres=dbquery($stquery);
	  $strow = mysqli_fetch_array($stres);
	  $csvline[] =  $strow['quantity'];
	}
	
    publish_csv_line($out, $csvline, $separator);
  }
  fclose($out);

/* fputcsv doesn't work here correctly. It will not put in quotes strings with a comma but without a space. */
/* As a result spreadsheets will take this as a second separator and fill several cells instead of just one */
/* The code of fputcsv3 comes from a forum where it was claimed to be the source for the PHP function. */
/* I have added the functionality that it will always escape strings with a semicolon or comma */
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
