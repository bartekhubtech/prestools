<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
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
if(!isset($input['startrec'])) $startrec="0"; else $startrec = intval($input['startrec']);
if(!isset($input['numrecs'])) $numrecs="100"; else $numrecs = intval($input['numrecs']);
if($numrecs < 1) $numrecs=1;
if(!isset($input['id_lang'])) $input['id_lang']="";
$id_lang = $input['id_lang'];
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = $input['id_shop'];
if(isset($input['sortorder']) && in_array($input['sortorder'], array("email","id","name","sales","ordercount"))) $sortorder = $input['sortorder']; else $sortorder="id";
if((!isset($input['rising'])) || ($input['rising'] == "DESC")) {$rising = "DESC";} else {$rising = "ASC";}
if(!isset($input['startlastpurchase'])) $startlastpurchase=""; else $startlastpurchase = dateval($input['startlastpurchase']);
if(!isset($input['endlastpurchase'])) $endlastpurchase=""; else $endlastpurchase = dateval($input['endlastpurchase']);
if(!isset($input['startregis'])) $startregis=""; else $startregis = dateval($input['startregis']);
if(!isset($input['endregis'])) $endregis=""; else $endregis = dateval($input['endregis']);
if(!isset($input['startpcode']) || ($input['startpcode']=="")) $startpcode=""; else $startpcode = floatval($input['startpcode']);
if(!isset($input['endpcode']) || ($input['endpcode']=="")) $endpcode=""; else $endpcode = floatval($input['endpcode']);
if(!isset($input['startamt']) || ($input['startamt']=="")) $startamt=""; else $startamt = floatval($input['startamt']);
if(!isset($input['endamt']) || ($input['endamt']=="")) $endamt=""; else $endamt = floatval($input['endamt']);
if(!isset($input['startcnt']) || ($input['startcnt']=="")) $startcnt=""; else $startcnt = intval($input['startcnt']);
if(!isset($input['endcnt']) || ($input['endcnt']=="")) $endcnt=""; else $endcnt = intval($input['endcnt']);
if($endcnt < 1) $endcnt = "";
if(!isset($input['startcntperiod'])) $startcntperiod=""; else $startcntperiod = dateval($input['startcntperiod']);
if(!isset($input['endcntperiod'])) $endcntperiod=""; else $endcntperiod = dateval($input['endcntperiod']);
if(!isset($input['gender']) || (!in_array($input['gender'], array("all","man","woman","unknown")))) $gender="all"; else  $gender = $input['gender'];
if(!isset($input['optin']) || (!in_array($input['optin'], array("all","optin","nope")))) $optin="all"; else  $optin = $input['optin'];
if(!isset($input['grouper']) || (!in_array($input['grouper'], array("email","id")))) $grouper="email"; else  $grouper = $input['grouper'];
if(!isset($input['newssubscribe']) || (!in_array($input['newssubscribe'], array("all","subscribed","nope")))) $newssubscribe="all"; else  $newssubscribe = $input['newssubscribe'];
if(!isset($input['custcompany']) || (!in_array($input['custcompany'], array("all","companies","private")))) $custcompany="all"; else  $custcompany = $input['custcompany'];
if(!isset($input['startbd']) || ($input['startbd']=="")) $startbd=""; else $startbd = birthdayval($input['startbd']);
if(!isset($input['endbd']) || ($input['endbd']=="")) $endbd=""; else $endbd = birthdayval($input['endbd']);
if(!isset($input['sproducts'])) $sproducts=""; else $sproducts = preg_replace("/^[,c0-9]/","",$input['sproducts']);
if(!isset($input['spstart'])) $spstart=""; else $spstart = dateval($input['spstart']);
if(!isset($input['spend'])) $spend=""; else $spend = dateval($input['spend']);

/* for birthday we accept both full dates ("yyyy-mm-dd") and month-days ("mm-dd") */
function birthdayval($val)
{ $parts = explode("-",$val);
  if((sizeof($parts) <2) || (sizeof($parts) >3)) return "";
  for($i=0; $i<sizeof($parts); $i++)
	  if(!ctype_digit($val))
		  return "";
  if(sizeof($parts) == 3) return(check_mysql_date($val));
  if(!check_mysql_date("2004-".$val)) return ""; /* check for a random leap year */
  return $val;
}

if($id_lang == "")
	$id_lang = get_configuration_value('PS_LANG_DEFAULT');

$default_country = get_configuration_value('PS_COUNTRY_DEFAULT');

$sfields = array("main fields", "cust id","name","address","state", "country","email","phone",
"product id(s)", "category id(s)");
  
  if(empty($input['fields'])) // if not set, set default set of active fields
    $input['fields'] = array("gender","firstname","lastname","email"); /* this is set in settings1.php */
  $custfields = array(
    array("id","c.id_customer"),
	array("firstname", "firstname"),
	array("lastname","lastname"),
	array("company","company"),
	array("email","email"),
	array("gender", "id_gender"),
	array("registrationdate","registrationdate"),
	array("birthday","birthday"),
	array("siret","siret"),
	array("ape","ape"),
	array("optin","optin"),
	array("note","note"),
	array("vat_nr","VAT_number"),
	array("phone","phone"),	
	array("addresses","addresses"),
	array("orders","orders"),
	array("newsletter","newsletter"),
	array("lastpurchasedate","lastpurchasedate"),
	array("ordercount","ordercount"),
	array("lang","lang"),
	array("is_guest","is_guest"),
	array("sales","sales"));
  
  $searchfields = array();
  if($search_txt1 != "") $searchfields[] = $search_fld1;
  if($search_txt2 != "") $searchfields[] = $search_fld2;
  if($search_txt3 != "") $searchfields[] = $search_fld3;
    
  $needorders = $needaddresses = 0;
  if(($startcnt > 0) || (floatval($startamt)>0) || ($endcnt !="") || ($endamt!="")
	  || ($sortorder == "sales") || ($sortorder == "ordercount") 
	  || ($startlastpurchase != "") || ($endlastpurchase != "")
	  || in_array("sales",$input["fields"])|| in_array("ordercount",$input["fields"])
	  || in_array("orders",$input["fields"])|| in_array("lastpurchasedate",$input["fields"])) 
	  $needorders = 1;
	  
  if(in_array("vat_nr",$input["fields"])
	  || in_array("phone",$searchfields) || in_array("main fields",$searchfields)
	  || in_array("address",$searchfields)
	  || in_array("state",$searchfields) || in_array("country",$searchfields)  
	  || in_array("addresses",$input["fields"])
  	  || in_array("phone",$input["fields"]))
	  $needaddresses = 1;
  
  /* here we start building the query */
  $qfields = "c.date_add AS registrationdate";
  $qbody = " FROM ". _DB_PREFIX_."customer c";
  $qconditions = " WHERE 1";
  $qhaving = " HAVING 1";
  
  if($grouper == "email")
  { $qtail = " GROUP BY email";
	$qfields .= ",GROUP_CONCAT(DISTINCT(c.id_customer)) AS cust_ids";
  }
  else
  { $qtail = " GROUP BY c.id_customer";
    $qfields .= ",c.id_customer AS cust_ids"; /* make sure id_customer is always valid (after left joins */
  }

  if($needorders)
  {	if(($startcnt >= 1) || (floatval($startamt)>0) || ($endcnt !="") || ($endamt!="")|| ($sortorder == "ordercount")
	  || ($sortorder == "sales") || in_array("sales",$input["fields"]) || in_array("ordercount",$input["fields"])
	  || in_array("lastpurchasedate",$input["fields"]) || in_array("orders",$input["fields"]))
	  { $qfields .= ",o.*,MAX(id_order) AS lastorder,SUM(o.total_paid) AS totsales,GROUP_CONCAT(id_order) AS orders";
		$qfields .= ",IF(id_order IS NULL, 0 , COUNT(*)) AS ordercount";
		$qfields .= ",MIN(o.date_add) AS firstpurchase,MAX(o.date_add) AS lastpurchase";
		$qbody .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_customer=c.id_customer AND valid='1'";
	  }
    else
	{ $qfields .= ",o.*";
      $qbody .= " LEFT JOIN ". _DB_PREFIX_."orders o ON o.id_customer=c.id_customer";
	  $qbody .= " AND o.valid=1";
	}
  }
  if($needaddresses)
  { $qfields .= ",a.*,st.name AS state";
    $qbody .= " LEFT JOIN ". _DB_PREFIX_."address a ON a.id_customer=c.id_customer AND a.deleted=0";
    $qbody .= " LEFT JOIN ". _DB_PREFIX_."address a2 ON a.id_customer=a2.id_customer AND a.id_address < a2.id_address AND a2.deleted=0";
    $qbody .= " LEFT JOIN ". _DB_PREFIX_."state st ON a.id_state=st.id_state";
	$qconditions .= " AND a2.id_address IS NULL";
  }
  
  if(in_array("vat_nr",$input["fields"]))
  { $qfields .= ",aa.vat_number AS vat_number";
   $qbody .= " LEFT JOIN ". _DB_PREFIX_."address aa ON aa.id_customer=c.id_customer AND aa.deleted=0 AND LENGTH(aa.vat_number)>3";
  }
  
  if(in_array("lang",$input["fields"]) || in_array("lang",$searchfields))
  { $qfields .= ", l.iso_code AS lang";
    $qbody .= " LEFT JOIN ". _DB_PREFIX_."lang l ON l.id_lang=c.id_lang";
  }
  
  $sfields = array("main fields", "customer id","name","address","state", "country","email","phone",
"product id");
  $countryfound = 0;
  for($i=1; $i<=3; $i++)  // search_txt1, search_txt2 and search_txt3 
  { $stext = mescape($GLOBALS["search_txt".$i]);	
    if($stext == "") continue;
    $sfield = $GLOBALS["search_fld".$i];
	$subconditions = array();
	if(($sfield == "customer id") || ($sfield == "main fields"))
	  $subconditions[] = "c.id_customer='".$stext."'";
    if(($sfield == "main fields") && (strlen($stext)==10)) // now check if it is a valid date 
    { $parts = explode("-",$stext);
      if(checkdate($parts[1],$parts[2],$parts[0]))
		  $subconditions[] = "DATE(c.date_add) = '".$stext."'";	  
	}
	if(($sfield == "name") || ($sfield == "main fields"))
	 	$subconditions[] = "c.firstname LIKE '%".$stext."%' OR c.lastname LIKE '%".$stext."%' OR c.company LIKE '%".$stext."%'";
	if($sfield == "state")
	{ $subconditions[] = "st.name LIKE '%".$stext."%'";
	}
	if($sfield == "country")
	{ if(!$countryfound)
	  { $countryfound = 1;
		$qbody .= " LEFT JOIN "._DB_PREFIX_."country_lang cl ON cl.id_country=a.id_country AND cl.id_lang=".$id_lang;
	  }
	  $subconditions[] = "cl.name LIKE '%".$stext."%'";
	}
 	if(($sfield == "address") || ($sfield == "main fields"))
	{ $tmp = "a.address1 LIKE '%".$stext."%' OR a.address2 LIKE '%".$stext."%' OR a.postcode LIKE '%".$stext."%' OR a.city LIKE '%".$stext."%'";
	  $subconditions[] = $tmp;
    }
 	if(($sfield == "email") || ($sfield == "main fields"))
	 	$subconditions[] = "c.email LIKE '%".$stext."%'";

 	if(($sfield == "phone") || ($sfield == "main fields"))
	 	$subconditions[] = "a.phone LIKE '%".$stext."%' OR a.phone_mobile LIKE '%".$stext."%'";
 	if($sfield == "lang")
	 	$subconditions[] = "l.iso_code='".$stext."'";
	if($sfield == "product id")
	{ $tmp = "c.id_customer IN (SELECT DISTINCT id_customer FROM ". _DB_PREFIX_."orders o4";
	  $tmp .= " LEFT JOIN ". _DB_PREFIX_."order_detail od on o4.id_order=od.id_order WHERE product_id='".intval($stext)."')";
      $subconditions[] = $tmp;
	}
    $qconditions .= " AND (".implode(" OR ",$subconditions).")";
  }

  if($startregis != "")
  { $qconditions .= " AND c.date_add >= '".$startregis."'";
  }
  if($endregis != "")
  { $qconditions .= " AND c.date_add <= '".$endregis."'";
  }
  if($startlastpurchase != "")
  { $qhaving .= " AND lastpurchase >= '".$startlastpurchase."'";
  }
  if($endlastpurchase != "")
  { $qhaving .= " AND lastpurchase <= '".$endlastpurchase."'";
  }
  if($startpcode != "")
  { $qconditions .= " AND a.postcode >= '".$startpcode."'";
  }
  if($endpcode != "")
  { $qconditions .= " AND a.postcode <= '".$endpcode."'";
  }
  if($custcompany != "all")
  { if($custcompany == "companies")
      $qconditions .= " AND c.company != ''";
    else 
      $qconditions .= " AND c.company == ''";
  }
  if($startamt != "")
  { $qhaving .= " AND totsales >= '".$startamt."'";
  }
  if($endamt != "")
  { $qhaving .= " AND totsales <= '".$endamt."'";
  }
  if($startcnt != "")
  { $qhaving .= " AND ordercount >= '".$startcnt."'";
  }
  if($endcnt != "")
  { $qhaving .= " AND ordercount <= '".$endcnt."'";
  }
  if($startcntperiod != "")
  { $qconditions .= " AND o.date_add >= '".$startcntperiod."'";
  }
  if($endcntperiod != "")
  { $qconditions .= " AND o.date_add <= '".$endcntperiod."'";
  }
  if($startbd != "")   /* birthday */
  { if(strlen($startbd) > 5)
      $qconditions .= " AND c.birthday >= '".$startbd."'";
    else
      $qconditions .= " AND SUBSTRING(c.birthday,6) >= '".$startbd."'";
  }
  if($endbd != "")   /* birthday */
  { if(strlen($endbd) > 5)
      $qconditions .= " AND c.birthday <= '".$endbd."'";
    else
      $qconditions .= " AND SUBSTRING(c.birthday,6) <= '".$endbd."'";
  }
  if($gender == "man") $qconditions .= " AND c.id_gender = '1'";
  if($gender == "woman") $qconditions .= " AND c.id_gender = '2'";
  if($gender == "unknown") $qconditions .= " AND (c.id_gender = '0' OR c.id_gender='9')";
  if($id_shop != "0") $qconditions .= " AND c.id_shop = '".$id_shop."'";
  
  if($optin != "all")
  { if($optin == "optin")
      $qconditions .= " AND c.optin = '1'";
    else
      $qconditions .= " AND c.optin = '0'";	
  }
  
  if($newssubscribe != "all")
  { if($newssubscribe == "subscribed")
      $qconditions .= " AND c.newsletter = '1'";
    else
      $qconditions .= " AND c.newsletter = '0'";	
  }
 
  $qfields .= ",c.*";
  
  $query = "SELECT SQL_CALC_FOUND_ROWS ".$qfields.$qbody.$qconditions.$qtail.$qhaving;

  if($sortorder == "id") $srtorder = "c.id_customer"; 
  else if($sortorder == "name") $srtorder = "c.lastname,c.firstname";
  else if($sortorder == "sales") $srtorder = "salesin";
  else if($sortorder == "ordercount") $srtorder = "ordercount";
  else if($sortorder == "email") $srtorder = "email";
  $query .= " ORDER BY ".$srtorder." ".$rising." LIMIT ".$startrec.",".$numrecs;
  $res = dbquery($query);
  
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=customer-'.date('Y-m-d-Gis').'.csv');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
  
//  echo $query;
//  echo "<br>Your search delivered ".$numrecs2." records. ".$numrecs." displayed. ";
// According to a comment on php.net the following can be added here to solve Chinese language problems
// fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

  // "*********************************************************************";
  if($input['separator'] == "comma")
  { $separator = ",";
	$subseparator = ";";
  }
  else 
  { $separator = ";";
	$subseparator = ",";
  }
  $csvline = array();  // array for the fputcsv function
  
  foreach($custfields AS $custrow)
  { $custfield = $custrow[0];
    if(!in_array($custfield,$input["fields"])) continue;
    else if($custfield == "id") $csvline[] = 'id';	
    else if($custfield == "firstname") $csvline[] = 'firstname';
    else if($custfield == "lastname") $csvline[] = 'lastname';
    else if($custfield == "company") $csvline[] = 'company';
    else if($custfield == "addresses") $csvline[] = 'address';	
    else if($custfield == "email") $csvline[] = 'email';
    else if($custfield == "phone") $csvline[] = 'phone';	
    else if($custfield == "gender") $csvline[] = 'gender';
    else if($custfield == "birthday") $csvline[] = 'birthday';
    else if($custfield == "siret") $csvline[] = 'siret';
    else if($custfield == "ape") $csvline[] = 'ape';
    else if($custfield == "sales") $csvline[] = 'sales';
    else if($custfield == "ordercount") $csvline[] = 'ordercount';
    else if($custfield == "is_guest") $csvline[] = 'guest';	
    else if($custfield == "orders") $csvline[] = 'orders';	
    else if($custfield == "vat_nr") $csvline[] = 'vat_nr';
    else if($custfield == "registrationdate") $csvline[] = 'registrationdate';
    else if($custfield == "lastpurchasedate") $csvline[] = 'lastpurchasedate';
    else $csvline[] = $custfield;
  }
  $out = fopen('php://output', 'w');
  publish_csv_line($out, $csvline, $separator);
  

    while ($row=mysqli_fetch_array($res))
    { $csvline = array();
	  foreach($custfields AS $custrow)
      { $custfield = $custrow[0];
        if(!in_array($custfield,$input["fields"])) continue;
        else if($custfield == "id") $csvline[] = $row["id_customer"];		
        else if($custfield == "firstname") $csvline[] = $row["firstname"];
        else if($custfield == "lastname") $csvline[] = $row["lastname"];
        else if($custfield == "company") $csvline[] = $row["company"];
        else if($custfield == "email") $csvline[] = $row["email"];
        else if($custfield == "newsletter") $csvline[] = $row["newsletter"];
        else if($custfield == "optin") $csvline[] = $row["optin"];		
        else if($custfield == "note") $csvline[] = $row["note"];		
        else if($custfield == "addresses")
	    { $tmp = $row["address1"]." ".$row["address2"]." ".$row["postcode"]." ".$row["city"]." ".$row["state"];
	      if(($row["id_country"]!= $default_country) && ($row["id_country"]!=0))
		     $tmp .= " ".get_country($row["id_country"]);
		  $csvline[] = $tmp;
	    }
        else if($custfield == "sales") $csvline[] = number_format($row["totsales"],2);
        else if($custfield == "orders") $csvline[] = $row["orders"];
        else if($custfield == "ordercount") $csvline[] = $row["ordercount"];	
        else if($custfield == "phone") $csvline[] = $row["phone"]." ".$row["phone_mobile"];
        else if($custfield == "gender") $csvline[] = $row["id_gender"];
        else if($custfield == "is_guest") $csvline[] = $row["is_guest"];		
        else if($custfield == "birthday") $csvline[] = $row["birthday"];
        else if($custfield == "siret") $csvline[] = $row["siret"];
        else if($custfield == "ape") $csvline[] = $row["ape"];
        else if($custfield == "lang") $csvline[] = $row["lang"];		
        else if($custfield == "vat_nr") $csvline[] = $row["vat_number"];
		else if($custfield == "registrationdate") $csvline[] = substr($row["registrationdate"],0,10); 
		else if($custfield == "lastpurchasedate") $csvline[] = substr($row["lastpurchase"],0,10); 
	  }
      publish_csv_line($out, $csvline, $separator);
	}
    fclose($out);
	/* end if sortorder is id or name */

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

function publish_csv_line($out, $csvline, $separator)
{ fputcsv3($out, $csvline, $separator);
}
