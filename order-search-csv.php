<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['term'])) $input['term']="";
if(!isset($input['fields'])) $input['fields']=array();
if(!isset($input['search_txt1'])) $input['search_txt1']="";
$search_txt1 = mysqli_real_escape_string($conn, $input['search_txt1']);
if(!isset($input['search_txt2'])) $input['search_txt2']="";
$search_txt2 = mysqli_real_escape_string($conn, $input['search_txt2']);
if(!isset($input['search_txt3'])) $input['search_txt3']="";
$search_txt3 = mysqli_real_escape_string($conn, $input['search_txt3']);
if(!isset($input['search_fld1'])) $input['search_fld1']="";
$search_fld1 =  $input['search_fld1'];
if(!isset($input['search_fld2'])) $input['search_fld2']="";
$search_fld2 =  $input['search_fld2'];
if(!isset($input['search_fld3'])) $input['search_fld3']="";
$search_fld3 =  $input['search_fld3'];
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0";
if(!isset($input['numrecs'])) $input['numrecs']="100";
if(!isset($input['carrier'])) $carrier = "All carriers"; else $carrier = mysqli_real_escape_string($conn, $input['carrier']);
if(!isset($input['payoption'])) $payoption = "All payoptions"; else $payoption = mysqli_real_escape_string($conn, $input['payoption']);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate']))) $startdate=""; else $startdate = $input['startdate'];
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate']))) $enddate=""; else $enddate = $input['enddate'];
if(!isset($input['newcust'])) $input['newcust']="all";
if(!isset($input['id_lang'])) $input['id_lang']="";
$id_lang = $input['id_lang'];
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = $input['id_shop'];
if((!isset($input['paystatus'])) || ($input['paystatus'] == "valid")) {$paystatus = "valid";} else {$paystatus = $input['paystatus'];}
if(isset($input['sortorder']) && in_array($input['sortorder'], array("date","amount","customer","best customer"))) $sortorder = $input['sortorder']; else $sortorder="date";
if((!isset($input['rising'])) || ($input['rising'] == "DESC") || ($sortorder=="date") || ($sortorder=="amount")) {$rising = "DESC";} else {$rising = "ASC";}

  $query = "SELECT DISTINCT current_state FROM ". _DB_PREFIX_."orders ORDER BY current_state";
  $res = dbquery($query);
  $orderstates = array();
  while($row = mysqli_fetch_array($res))
  { $squery = "SELECT name, id_order_state FROM ". _DB_PREFIX_."order_state_lang WHERE id_order_state=".$row['current_state']." AND id_lang=".$id_lang;
	$sres = dbquery($squery);
	$srow = mysqli_fetch_array($sres);
	$currentstate = $row['current_state'];
	if(!isset($_GET["orderstate"]) || isset($_GET["orderstate"][$currentstate]))
	{	$orderstates[] = $currentstate;
	}
  }

  $default_country = get_configuration_value('PS_COUNTRY_DEFAULT');
  
  $qfields = "o.id_order,o.date_add,o.invoice_number,o.delivery_number,o.reference, o.id_customer, o.id_address_delivery, refunds, oc.tracking_number";
  $qfields .= ",o.id_address_invoice,ROUND(o.total_paid,2) AS total_paid, ROUND(o.total_paid_tax_excl,2) AS total_paid_tax_excl, ROUND(o.payment,2) AS payment";
  $qfields .= ",ROUND(total_products_wt,2) AS total_products_wt,ROUND(total_shipping,2) AS total_shipping,ROUND(total_wrapping,2) AS total_wrapping,s.name AS order_state,o.current_state";
  $qfields .= ",ROUND(total_products,2) AS total_products, ROUND(total_discounts_tax_excl,2) AS total_discounts_tax_excl, o.invoice_date,c.firstname,c.lastname,c.company";
  $qfields .= ",a1.firstname AS firstname1,a1.lastname AS lastname1,a1.company AS company1,a1.address1,a1.address2,a1.postcode,a1.city,a1.id_country,a1.phone,a1.phone_mobile";
  $qfields .= ",a2.firstname AS firstname2,a2.lastname AS lastname2,a2.company AS company2,a2.address1 AS address12,a2.address2 AS address22,a2.postcode AS postcode2,a2.city AS city2,a2.id_country AS id_country2,a2.phone AS phone2,a2.phone_mobile AS phone_mobile2";  
  $qfields .= ",cl1.name AS country, cl2.name AS country2, st1.name AS state, st2.name AS state2, c.email, ct.id_customer_thread, c.is_guest, cu.iso_code AS currency"; 
  $qfields .= ', IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = c.id_customer AND so.id_order < o.id_order LIMIT 1) > 0, 0, 1) as newcust'; /* this line is copied from Prestashop */
  $qfields .= ", GROUP_CONCAT(DISTINCT(ca.name)) AS carriers";
  $qbody = " FROM ". _DB_PREFIX_."orders o";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."customer c ON c.id_customer=o.id_customer";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."customer_thread ct ON ct.id_order=o.id_order"; 
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."address a1 ON o.id_address_invoice=a1.id_address";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."state st1 ON st1.id_state=a1.id_state";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."country_lang cl1 ON cl1.id_country=a1.id_country AND cl1.id_lang=".$id_lang; 
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."address a2 ON o.id_address_delivery=a2.id_address"; 
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."state st2 ON st2.id_state=a2.id_state";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."country_lang cl2 ON cl2.id_country=a2.id_country AND cl2.id_lang=".$id_lang; 
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."order_state_lang s ON o.current_state=s.id_order_state AND s.id_lang=".$id_lang; 
  $qbody .= " LEFT JOIN (SELECT id_order, SUM(amount+shipping_cost_amount) AS refunds";
  $qbody .= " FROM "._DB_PREFIX_."order_slip GROUP BY id_order) os on os.id_order=o.id_order";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."order_carrier oc ON oc.id_order=o.id_order";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."carrier ca ON oc.id_carrier=ca.id_carrier";
  $qbody .= " LEFT JOIN ". _DB_PREFIX_."currency cu ON cu.id_currency=o.id_currency";

  $qconditions = " WHERE 1";
  $product_searched = false;
  for($i=1; $i<=3; $i++)
  { $stext = mescape($GLOBALS["search_txt".$i]);
    $sfield = $GLOBALS["search_fld".$i];
    if(($stext == "") && ($sfield != 0)) continue;
	$subconditions = array();
	if(($sfield == "main fields") && (is_numeric($stext))) /* note that 'id_order='4rtsr' will evaluate as 'id_order=4' */
	  $subconditions[] = "o.id_order='".$stext."'";
	if($sfield == "order id")
	{ $stext = preg_replace('/[^0-9 ,]/', "x", $stext);
      $stext = str_replace(" ",",",$stext);
      $subconditions[] = "o.id_order IN (".$stext.")";
	}
    if(($sfield == "main fields") && (strlen($stext)==10)) /* now check if it is a valid date */
    { $parts = explode("-",$stext);
      if((sizeof($parts)==3) && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2]) && checkdate($parts[1],$parts[2],$parts[0]))
		  $subconditions[] = 'DATE(o.date_add) LIKE "%'.$stext.'%"';
	}
	if($sfield == "order date")
	  $subconditions[] = 'DATE(o.date_add) LIKE "%'.$stext.'%"';
	if(($sfield == "invoice number") || ($sfield == "main fields"))
	 	$subconditions[] = "CAST(o.invoice_number AS char)='".$stext."'"; /* without CAST 0 matches with any string */
	if(($sfield == "order reference") || ($sfield == "main fields"))
	 	$subconditions[] = "o.reference='".$stext."'";
	if(($sfield == "delivery number") || ($sfield == "main fields"))
	 	$subconditions[] = "CAST(o.delivery_number AS char)='".$stext."'"; /* without CAST 0 matches with any string */
 	if(($sfield == "customer email") || ($sfield == "main fields"))
	 	$subconditions[] = "c.email LIKE '%".$stext."%'";
	if(($sfield == "customer name") || ($sfield == "main fields"))
	 	$subconditions[] = "a1.firstname LIKE '%".$stext."%' OR a1.lastname LIKE '%".$stext."%' OR a2.firstname LIKE '%".$stext."%' OR a2.lastname LIKE '%".$stext."%' OR a1.company LIKE '%".$stext."%' OR a2.company LIKE '%".$stext."%'";
	if(($sfield == "customer id") || ($sfield == "main fields"))
	 	$subconditions[] = "c.id_customer='".$stext."'";
	if($sfield == "customer state")
	 	$subconditions[] = "st1.name LIKE '%".$stext."%' OR st2.name LIKE '%".$stext."%'";
	if($sfield == "customer country")
	 	$subconditions[] = "cl1.name LIKE '%".$stext."%' OR cl2.name LIKE '%".$stext."%'";
 	if(($sfield == "customer address") || ($sfield == "main fields"))
	{ $tmp = "a1.address1 LIKE '%".$stext."%' OR a1.address2 LIKE '%".$stext."%' OR a1.postcode LIKE '%".$stext."%' OR a1.city LIKE '%".$stext."%'";
      $tmp .= " OR a2.address1 LIKE '%".$stext."%' OR a2.address2 LIKE '%".$stext."%' OR a2.postcode LIKE '%".$stext."%' OR a2.city LIKE '%".$stext."%'";
	  $subconditions[] = $tmp;
    }
 	if(($sfield == "customer phone") || ($sfield == "main fields"))
	 	$subconditions[] = "a1.phone LIKE '%".$stext."%' OR a1.phone_mobile LIKE '%".$stext."%' OR a2.phone LIKE '%".$stext."%' OR a2.phone_mobile LIKE '%".$stext."%'";
 	if(($sfield == "total paid") || (($sfield == "main fields") AND (is_numeric($stext))))
	 	$subconditions[] = "o.total_paid_tax_incl='".$stext."'";
 	if(($sfield == "amount products with tax") || (($sfield == "main fields") AND (is_numeric($stext))))
	 	$subconditions[] = "o.total_products_wt='".$stext."'";
 	if(($sfield == "amount products excl tax") || (($sfield == "main fields") AND (is_numeric($stext))))
	 	$subconditions[] = "o.total_products='".$stext."'";
	if($sfield == "product id")
		$subconditions[] = "EXISTS (SELECT NULL FROM ". _DB_PREFIX_."order_detail od".$i." WHERE o.id_order=od".$i.".id_order AND od".$i.".product_id='".$stext."')";
	if($sfield == "product name")
		$subconditions[] = "EXISTS (SELECT NULL FROM ". _DB_PREFIX_."order_detail od".$i." WHERE o.id_order=od".$i.".id_order AND od".$i.".product_name LIKE '%".$stext."%')";
	if($sfield == "product manufacturer")
		$subconditions[] = "EXISTS (SELECT NULL FROM "._DB_PREFIX_."order_detail od".$i." 
	    LEFT JOIN "._DB_PREFIX_."product p".$i." ON p".$i.".id_product=od".$i.".product_id
		LEFT JOIN "._DB_PREFIX_."manufacturer m".$i." ON p".$i.".id_manufacturer=m".$i.".id_manufacturer
		WHERE o.id_order=od".$i.".id_order AND m".$i.".name LIKE '%".$stext."%')";
	if($sfield == "product supplier")
		$subconditions[] = "EXISTS (SELECT NULL FROM "._DB_PREFIX_."order_detail od".$i." 
	    LEFT JOIN "._DB_PREFIX_."product_supplier pu".$i." ON pu".$i.".id_product=od".$i.".product_id
		LEFT JOIN "._DB_PREFIX_."supplier s".$i." ON pu".$i.".id_supplier=s".$i.".id_supplier
		WHERE o.id_order=od".$i.".id_order AND s".$i.".name LIKE '%".$stext."%')";
	if($sfield == "carrier")
		$subconditions[] = "ca.name LIKE '%".$stext."%'";
	if($sfield == "currency")		
		$subconditions[] = "cu.iso_code LIKE '%".$stext."%'";
	if($sfield == "refunds")
		$subconditions[] = "refunds > 0"; 
	
	$qconditions .= " AND (".implode(" OR ",$subconditions).")";
  }
  if(isset($_GET["orderstate"]))
	  $qconditions .= " AND (o.current_state IN (".implode(",",$orderstates)."))";
  if($startdate != "")
	  $qconditions .= " AND o.date_add>='".$startdate."'"; 
  if($enddate != "")
	  $qconditions .= " AND o.date_add<='".$enddate."'";
  if($payoption != "All payoptions")
	  $qconditions .= " AND o.payment='".mysqli_real_escape_string($conn, $payoption)."'";
  if($paystatus == "valid")
	  $qconditions .= " AND o.valid=1";
  else if($paystatus == "not paid")
	  $qconditions .= " AND o.valid=0"; 
  if($carrier != "All carriers")
  { $cquery = "SELECT GROUP_CONCAT(id_carrier SEPARATOR '\',\'') AS ids FROM ". _DB_PREFIX_."carrier";
	$cquery .= " WHERE name='".$carrier."' GROUP BY name";
	$cres = dbquery($cquery);
	$crow = mysqli_fetch_array($cres);
	$qconditions .= " AND oc.id_carrier IN ('".$crow['ids']."')";  
  }
  $having = "";
  if($input["newcust"] == "new")
	  $having .= " HAVING newcust=1";
  else if($input["newcust"] == "old")
	  $having .= " HAVING newcust=0";
  
  if(($qconditions == " WHERE 1") && !isset($_GET["orderstate"])) return; /* we don't want to list all orders */
  
  /* we group by id_order because an order can have more than one carrier */
  $query = "SELECT SQL_CALC_FOUND_ROWS ".$qfields.$qbody.$qconditions." GROUP BY id_order ";

  $srtorder = "id_order";
  if($sortorder == "amount") $srtorder = "(total_products/o.conversion_rate)";
  if($sortorder == "customer") $srtorder = "c.lastname,c.firstname,c.company,c.id_customer"; 
  $query .= $having." ORDER BY ".$srtorder." ".$rising;
//  echo $query."<br>";
  if(($sortorder == "date") || ($sortorder == "amount"))
	  $res = dbquery($query." LIMIT ".$input['startrec'].",".$input['numrecs']);
  else 
	  $res = dbquery("CREATE TEMPORARY TABLE "._DB_PREFIX_."ordertemp (".$query.")");
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);
  $numrecs2 = $row2['foundrows'];
  $total_excl = $total_incl = $total_shipping = 0;
  $total_excl_new = $total_incl_new = $total_shipping_new = $newrecs = 0;
  if(($sortorder == "customer") || ($sortorder == "best customer"))
  { $xquery = "SELECT NULL FROM "._DB_PREFIX_."ordertemp";
	$res = dbquery($xquery); 
  }
  $numrecs = mysqli_num_rows($res);
  
  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }
  
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=order-search-'.date('Y-m-d-Gis').'.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
	
  if(($sortorder == "date") || ($sortorder == "amount"))
  { $fields = array("id_order","reference","delivery_number","invoice_number","payment","carriers"
	,"id_customer","firstname","lastname","company","date_add","currency","invoice_date","total_paid","total_paid_tax_excl"
	,"total_products_wt","total_shipping","total_wrapping","refunds","order_state","address1","address2"
	,"postcode","city","state","country","phone","phone_mobile","email","newcust","tracking_number",
	"address12","address22","postcode2","city2","state2","country2","phone2","phone_mobile2");
	
	$out = fopen('php://output', 'w');
    publish_csv_line($out, $fields, $separator);

    while ($row=mysqli_fetch_array($res))
    { $csvline = array();
      for($i=0; $i< sizeof($fields); $i++)
	    $csvline[] = $row[$fields[$i]];
	  publish_csv_line($out, $csvline, $separator);
    }
    fclose($out);
  }
  else 	/* if sortorder is customer or best customer */
  { $fields = array("id_customer","firstname","lastname","company","email","currency",
	"customer_excl","customer_products","customer_shipping","customer_incl","refunds",
	"num_orders","orders","phone","phone_mobile","address1","city","country");
	$out = fopen('php://output', 'w');
    publish_csv_line($out, $fields, $separator);
   
    $x = 0;
    $aquery = "SELECT firstname, lastname, company, id_customer, email,GROUP_CONCAT(DISTINCT currency) AS currency,SUM(total_paid_tax_excl) AS customer_excl,"; 
	$aquery .= " SUM(total_products-total_discounts_tax_excl) AS customer_products, ";
	$aquery .= " phone, phone_mobile,address1,city,state,country,refunds,";
    $aquery .= " SUM(total_shipping) AS customer_shipping, SUM(total_paid) AS customer_incl,";
    $aquery .= " COUNT(*) AS num_orders, GROUP_CONCAT(id_order) AS orders";
	$aquery .= " FROM "._DB_PREFIX_."ordertemp";
	$aquery .= " GROUP BY id_customer";
	if($sortorder == "customer")
	  $aquery .= " ORDER BY lastname ".$rising.",firstname ".$rising.",company ".$rising.",id_customer ".$rising;
    else /* best customer */
	  $aquery .= " ORDER BY customer_products ".$rising;
	$aquery .= " LIMIT ".$input['startrec'].",".$input['numrecs'];
	$ares=dbquery($aquery);
	
	while ($arow=mysqli_fetch_array($ares))
    { $csvline = array();
      for($i=0; $i< sizeof($fields); $i++)
	  { $csvline[] = $arow[$fields[$i]];
	  }
	  publish_csv_line($out, $csvline, $separator);
    }
    fclose($out);
  }
  
function publish_csv_line($out, $csvline, $separator)
{ fputcsv($out, $csvline, $separator);
}
  
$countries = array();
function get_country($id_country)
{ global $countries, $id_lang;
  if(!isset($countries[$id_country]))
  { $query = "select name from ". _DB_PREFIX_."country_lang";
    $query .= " WHERE id_country='".$id_country."' AND id_lang=".$id_lang;
	$res = dbquery($query);
	$row = mysqli_fetch_array($res);
	$countries[$id_country] = $row["name"];
  }
  return $countries[$id_country];
}