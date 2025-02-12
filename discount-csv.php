<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(isset($input['startrec'])) $startrec = intval($input['startrec']);
else $startrec = 0;
if(isset($input['numrecs'])) $numrecs = intval($input['numrecs']);
else $numrecs = 1000;
if($numrecs == 0) $numrecs = 1000;
if(!isset($input['id_lang'])) $input['id_lang']="";
if(!isset($input['id_shop'])) $id_shop=0; else $id_shop = intval($input['id_shop']);
if(!isset($input['pricerule']) || ($input['pricerule']=="all")) $pricerule="all"; else $pricerule = intval($input["pricerule"]);

if(!isset($input['searchtxt'])) $searchtxt=""; else $searchtxt = preg_replace('/[<>\'\"\&]+/','',$input['searchtxt']);
if(!isset($input['idblock'])) $idblock=""; else $idblock = preg_replace('/[^0-9,cv\-]+/','',$input['idblock']);
if(!isset($input['startdate'])) $startdate = ""; else $startdate = preg_replace('/[^0-9\-]+/','',$input['startdate']);
if(!isset($input['enddate'])) $enddate = ""; else $enddate = preg_replace('/[^0-9\-]+/','',$input['enddate']);
if(!isset($input['startstats'])) $startstats = ""; else $startstats = preg_replace('/[^0-9\-]+/','',$input['startstats']);
if(!isset($input['endstats'])) $endstats = ""; else $endstats = preg_replace('/[^0-9\-]+/','',$input['endstats']);

$fields = array("id_product", "attrib", "rule", "name", "reference","ean13", "category", "active", "VAT", "price", "fromprice", "change","newprice","Min_Qu" ,"country","from", "to","group","shop","customer","orders","sold","refunded");
$hiders = array("reference","ean13","shop","group");

/* get default language: we use this for the categories, manufacturers */
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

/* Get default country for the VAT tables and calculations */
$id_country = get_configuration_value('PS_COUNTRY_DEFAULT');

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=discounts-'.date('Y-m-d-Gis').'.csv');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }

$query = "SELECT s.*,s.price AS fromprice,c.name AS country, g.name AS groupname,cu.firstname,cu.lastname";
$query .= " FROM "._DB_PREFIX_."specific_price s";
$query.=" left join "._DB_PREFIX_."image i on i.id_product=s.id_product and i.cover=1";
$query.=" left join "._DB_PREFIX_."country_lang c on s.id_country=c.id_country AND c.id_lang='".$id_lang."'";
$query.=" left join "._DB_PREFIX_."group_lang g on g.id_group=s.id_group AND g.id_lang='".$id_lang."'";
$query.=" left join "._DB_PREFIX_."customer cu on cu.id_customer=s.id_customer";
$query .= " WHERE 1";
if(strval($pricerule) != "all")
  $query .= " AND id_specific_price_rule=".$pricerule;
if($searchtxt != "")
{ $query .= " AND EXISTS(SELECT name FROM "._DB_PREFIX_."product_lang pl";
  $query .= " WHERE id_lang=".$id_lang." AND pl.name LIKE '%".mysqli_real_escape_string($conn,$searchtxt)."%'";
  if($id_shop != 0)
	$query .= " AND pl.id_shop=".$id_shop;
  $query .= " AND pl.id_product=s.id_product)";
}
if($idblock != "")
{ $segments = array();
  $categories = array();
  $parts = explode(",", $idblock);
  foreach($parts AS $part)
  { $first = substr($part,0,1);
    if(is_numeric($first))
	{ if(strpos($part, '-') > 0)
	  { $elts = explode('-', $part);
        $segments[] = "s.id_product >= ".$elts[0]." AND s.id_product <= ".$elts[1];  
	  }
	  else
		$segments[] = "s.id_product=".$part;  
	}
	else if($first == 'c')
	{ $part = str_replace('c', '', $part);
	  if(strpos($part, '-') > 0)
	  { $elts = explode('-', $part);
        $segments[] = "EXISTS(SELECT NULL FROM "._DB_PREFIX_."category_product WHERE id_product=s.id_product AND id_category >= ".$elts[0]." AND id_category <= ".$elts[1].")";  
	  }
	  else
        $categories[] = $part;
	}
	else if($first == 'v')
	{ $part = str_replace('v', '', $part);
	  if(strpos($part, '-') > 0)
	  { $elts = explode('-', $part);
        $segments[] = "s.id_product_attribute >= ".$elts[0]." AND s.id_product_attribute <= ".$elts[1];  
	  }
	  else
        $segments[] = "s.id_product_attribute=".$part;
	}
	else die("First error ".$first);
  }
  if(sizeof($categories) > 0)
    $segments[] = "EXISTS(SELECT NULL FROM "._DB_PREFIX_."category_product WHERE id_product=s.id_product AND id_category IN (".implode(",",$categories)."))";
  $query .= " AND ((".implode(") OR (",$segments)."))";
}
if($startdate != "")
  $query .= " AND ((s.to >= '".$startdate."') OR (s.to = '0000-00-00 00:00:00'))";
if($enddate != "")
  $query .= " AND (s.from <= '".$enddate." 23:59:59')";
 
$query.=" ORDER BY s.id_specific_price DESC";
$query .= " LIMIT ".$startrec.",".$numrecs;
  $res=dbquery($query);
  
  $csvline = array();  // array for the fputcsv function
  
  foreach($fields AS $field)
  { if($input[$field]!=1) continue;
  	if($field == "attrib") $csvline[] = 'id_product_attribute';
  	else if($field == "rule") $csvline[] = 'id_specific_price_rule';
	else if($field == "Min_Qu") $csvline[] = 'From quantity';
    else $csvline[] = $field;
	if($field == "category") $csvline[] = 'id_category_default';
	if($field == "customer") $csvline[] = 'id_customer';	
  }
  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);
 
  $x=0;
  $ordercnt = $prodcnt = $refundcnt = 0;
  while ($datarow=mysqli_fetch_array($res))
  { $csvline = array();

	$prodquery = "SELECT p.id_product,p.price, p.id_category_default, p.active, cl.name AS catname, pl.name, p.reference, p.ean13, t.rate";
	$prodquery.=" FROM "._DB_PREFIX_."product_shop ps";
    $prodquery.=" left join "._DB_PREFIX_."product p on p.id_product=ps.id_product";
    $prodquery.=" left join "._DB_PREFIX_."product_lang pl on ps.id_product=pl.id_product AND pl.id_lang='".$id_lang."' AND pl.id_shop=ps.id_shop";
    $prodquery.=" left join "._DB_PREFIX_."category_lang cl on cl.id_category=ps.id_category_default AND cl.id_lang='".$id_lang."'";
    $prodquery.=" left join "._DB_PREFIX_."tax_rule tr on tr.id_tax_rules_group=p.id_tax_rules_group AND tr.id_country='".$id_country."' AND tr.id_state='0'";
	$prodquery.=" left join "._DB_PREFIX_."tax t on t.id_tax=tr.id_tax";
	$prodquery.=" left join "._DB_PREFIX_."tax_lang tl on t.id_tax=tl.id_tax AND tl.id_lang='".$id_lang."'";
    $prodquery.=" WHERE ps.id_product='".$datarow["id_product"]."'";
	if($datarow["id_shop"]=="0")
        $prodquery.=" LIMIT 1";
	else
	    $prodquery.=" AND ps.id_shop='".$datarow["id_shop"]."'";
    $prodres=dbquery($prodquery);
	$prodrow=mysqli_fetch_array($prodres);


    $priceVAT = (($prodrow['rate']/100) +1) * $prodrow['price'];
    if($datarow['fromprice'] > 0)
	{ $frompriceVAT = (($prodrow['rate']/100) +1) * $datarow['fromprice'];
	  $fpv_text = number_format($datarow['fromprice'],2, '.', '').' / '.number_format($frompriceVAT,2, '.', '');
	}
	else 
	{ $fpv_text = "";
	  $frompriceVAT = $priceVAT;
	  $fromprice = $prodrow['price'];
	}
  	if ($datarow['reduction_type'] == "amount")
	{ if ((version_compare(_PS_VERSION_ , "1.6.0.11", ">=")) && ($datarow["reduction_tax"]==0))
		$reduction = $datarow['reduction']*(1+($prodrow["rate"]/100));
	  else
		$reduction = $datarow['reduction'];
	  $newprice = $frompriceVAT - $reduction;
	  $change = number_format($reduction,2,".","");
    }
	else 
	{ $newprice = $frompriceVAT*(1-$datarow['reduction']);
	  $change = ($datarow['reduction']*100).'%';
	}
	$newpriceEX = (1/(($prodrow['rate']/100) +1)) * $newprice;
    $newprice = number_format($newprice,2, '.', '');
    $newpriceEX = number_format($newpriceEX,2, '.', '');
  
    if($input['id_product']==1)
    { if($datarow['id_product'] == 0)
	    $csvline[] =  'All'; 
	  else
	    $csvline[] = $datarow['id_product'];
	}
	if($input['attrib']==1)
	  $csvline[] = $datarow['id_product_attribute'];
    if($input['rule']==1)
	  $csvline[] = $datarow['id_specific_price_rule'];
    if($input['name']==1)
	  $csvline[] = $prodrow['name'];
    if($input['reference']==1)
	  $csvline[] = $prodrow['reference'];
    if($input['ean13']==1)
	  $csvline[] = $prodrow['ean13'];    
    if($input['category']==1)
	{ $csvline[] = $prodrow['id_category_default'];
	  $csvline[] = $prodrow['catname'];
	}
    if($input['active']==1)
	  $csvline[] = $prodrow['active'];
    if($input['VAT']==1)
	  $csvline[] = $prodrow['rate']+0;
    if($input['price']==1)
	  $csvline[] = number_format($prodrow['price'],2, '.', '').' / '.number_format($priceVAT,2, '.', '');
	if($input['fromprice']==1)
	  $csvline[] = $fpv_text;
    if($input['change']==1)
	  $csvline[] = $change;
	if($input['newprice']==1)
	  $csvline[] = $newpriceEX.' / '.$newprice;
    if($input['Min_Qu']==1)
	  $csvline[] = $datarow['from_quantity'];
    if($input['country']==1)
	  $csvline[] = $datarow['country'];
    if($input['from']==1)
	{ if($datarow['from'] == "0000-00-00 00:00:00")
	    $csvline[] = '';
	  else if(substr($datarow['from'], 11) == "00:00:00")
	    $csvline[] = substr($datarow['from'],0,10);
	  else
	    $csvline[] = $datarow['from'];
	}
	if($input['to']==1)
	{ if($datarow['to'] == "0000-00-00 00:00:00")
	    $csvline[] = '';
	  else if(substr($datarow['to'], 11) == "00:00:00")
	    $csvline[] = substr($datarow['to'],0,10);
	  else
	    $csvline[] = $datarow['to'];
	}
	if($input['group']==1)
	  $csvline[] = $datarow['groupname'];
    if($input['id_shop']==1)
	{ if($datarow['id_shop'] == '0')
	    $csvline[] = 'All';
	  else
	    $csvline[] = $datarow['id_shop'];
	}
	if($input['customer'] == 1)
    { $csvline[] = $datarow['firstname'].' '.$datarow['lastname'];
	  $csvline[] = $datarow["id_customer"];
	}
	
	if(($input['orders']==1) || ($input['sold']==1) || ($input['refunded']==1))
	{ $query = "SELECT COUNT(*) AS orders, SUM(product_quantity) AS quant, SUM(product_quantity_refunded) AS refund, SUM(product_quantity_return) AS returned";
	  $query.= " FROM "._DB_PREFIX_."order_detail od";
	  $query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
	  $query .= " WHERE product_id=".$datarow['id_product'];
	  if($datarow['id_product_attribute'] > 0)
	    $query .= " AND product_attribute_id = ".$datarow['fid_product_attribute'];
	  if($datarow['from_quantity'] > 1)
	    $query .= " AND product_quantity >=".$datarow['from_quantity'];
	  if(($datarow['from'] != "0000-00-00 00:00:00") || ($startstats != ""))
	  { if($startstats > $datarow['from'])
          $query .= " AND date_add >='".$startstats."'";
	    else
          $query .= " AND date_add >='".$datarow['from']."'";
	  }
	  if(($datarow['to'] != "0000-00-00 00:00:00") || ($endstats != ""))
	  { if($endstats > $datarow['to'])
		  $query .= " AND date_add <='".$endstats."'";
	    else
		  $query .= " AND date_add <='".$datarow['to']."'";
	  }
	  if($datarow['id_country'] != "0")
	    $query .= " AND o.id_country = ".$datarow['id_country'];
	  if($datarow['id_group'] != "0")
	    $query .= " AND o.id_group = ".$datarow['id_group'];		
	  if($datarow['id_shop'] != "0")
	    $query .= " AND o.id_shop = ".$datarow['id_shop'];
	  if($datarow['id_shop_group'] != "0")
	    $query .= " AND o.id_shop_group = ".$datarow['id_shop_group'];
	  if($datarow['id_currency'] != "0")
	    $query .= " AND o.id_currency = ".$datarow['id_currency'];
	  if($datarow['id_customer'] != "0")
	    $query .= " AND o.id_customer = ".$datarow['id_customer'];
	  if($datarow['reduction_type'] == "amount")
	  { if($datarow['reduction_tax'] == "0")
		  $query .= " AND ABS(od.reduction_amount_tax_excl/o.conversion_rate - ".$datarow['reduction'].") < 0.001";
	    else
		  $query .= " AND ABS(od.reduction_amount_tax_incl/o.conversion_rate - ".$datarow['reduction'].") < 0.001";
	  } 
	  else  /* percent */
	  { $query .= " AND od.reduction_percent = ".(100*$datarow['reduction']);
	  }
	  $qres=dbquery($query);
	  $qrow=mysqli_fetch_array($qres);
	  if($input['orders']==1)
	  { if($qrow['orders'] == 0)
	      $qrow['orders'] = "";
        else
	      $ordercnt += $qrow['orders'];
	    $csvline[] = $qrow['orders'];
	  }
	  if($input['sold']==1)
	  { if($qrow['quant'] > 0)
	      $prodcnt += $qrow['quant'];
	    $csvline[] = $qrow['quant'];
	  }
	  if($input['refunded']==1)
	  { $refunded = intval($qrow['refund'])+intval($qrow['returned']);
	    if($refunded == 0)
	      $refunded = "";
        else
	      $refundcnt += $qrow['refunded'];
	    $csvline[] = $refunded;
	  }
	}
    $x++;
    publish_csv_line($out, $csvline, $separator);
  }
  fclose($out);

function publish_csv_line($out, $csvline, $separator)
{ fputcsv3($out, $csvline, $separator);
}

