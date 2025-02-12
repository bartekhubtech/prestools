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
if(!isset($input['grouper']) || (!in_array($input['grouper'], array("email","id")))) $grouper="email"; else $grouper = $input['grouper'];
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
  { if(!ctype_digit($parts[$i])) return "";
  }
  if(sizeof($parts) == 3) 
  { if(check_mysql_date($val)) return $val;
    return "";
  }
  if(!check_mysql_date("2004-".$val)) return ""; /* check for a random leap year */
  return $val;
}

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Customer Search</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
body {font-family:arial; font-size:13px}
</style>
<script type="text/javascript" src="utils8.js"></script>
<script>
function show_products(row, id_order)
{ if(id_order == 0)
  { var myspan = document.getElementById("prodspan"+row);
    myspan.style.display = "inline";
    var mylink = document.getElementById("prodlink"+row);
    mylink.innerHTML = "<a href=\"#\" onclick=\"hide_products("+row+"); return false;\">Hide products</a>";
  }
  else
    LoadPage("order-search2.php?row="+row+"&id_order="+id_order,dynamo3);
}

function hide_products(row)
{ var myspan = document.getElementById("prodspan"+row);
  myspan.style.display = "none";
  
  var mylink = document.getElementById("prodlink"+row);
  mylink.innerHTML = "<a href=\"#\" onclick=\"show_products("+row+", 0); return false;\">Show products</a>";
}

function checksubmit()
{ if(searchform.csvexport.checked)
  { searchform.action = "customer-csv.php";
    searchform.target="_blank";
    searchform.submit();
    searchform.target = "";
	searchform.action = "customer-search.php";
	return false;
  }
  else
  { searchform.action = "customer-search.php";
	searchform.submit();
	return true;
  }
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


function check_date()
{ var flds = [];
  flds[0] = searchform.startdate.value;
  flds[1] = searchform.enddate.value;
  for(var i=0; i<=1; i++)
  { if(flds[i] == "") continue;
    var parts = flds[i].split("-");
	if((parts.length != 3) || (parts[0]<1990) || (parts[0]>2030) || (parts[1]<=0) || (parts[1]>12) || (parts[2]<=0) || (parts[2]>31))
	{ alert("Invalid date");
	  return false;
	}	
  }
  if(searchform.csvexport.checked)
  { searchform.action="order-search-csv.php";
    searchform.target="_blank";
    searchform.submit();
    searchform.target = "";
	searchform.action = "order-search.php";
	return false;
  }
  return true;
}

/* change DESC/ASC depending on the sort order */
function sortorder_change()
{ var myval = searchform.sortorder.value;
  var rising = 1;
  if((myval=="name") || (myval=="email")) rising = 0;
  searchform.rising.selectedIndex = rising;	
}

</script>
</head>
<body>';
print_menubar();

echo '<table><tr><td style="width:80%; text-align:center;"><a href="customer-search.php" style="text-decoration:none;"><b><font size="+2">Customer Search</font></b></a>';
echo '<p>Look up your customers in a variety of ways and export them to a csv file.
Customer search is primarily aimed at sales data. Its search produces only one address. 
Addresses in italics are an indication that there are more addresses. 
If you want a thorough search on addresses you should use order search.
For the customer language the iso_code is used.
Due to guest checkouts customers can have more than one id.';

echo '</td><td><iframe name=tank width="230" height="88"></iframe></td></tr></table>';

echo '<form name="searchform" onsubmit="return checksubmit();">';
echo '<table><tr><td>';
echo '<table style="display:inline-block">';

if($id_lang == "")
	$id_lang = get_configuration_value('PS_LANG_DEFAULT');
echo '<td>Language: <select name="id_lang" value="'.$id_lang.'">';
$query = "SELECT id_lang, name FROM ". _DB_PREFIX_."lang";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $selected = '';
  if ($row['id_lang']==$id_lang) 
    $selected=' selected="selected" ';
  echo '<option  value="'.$row['id_lang'].'" '.$selected.'>'.$row['name'].'</option>';
}
echo '</select> &nbsp; &nbsp;';

echo 'Shop: <select name="id_shop" value="'.$id_shop.'"><option value=0>all shops</option>';
$query = "SELECT id_shop, name FROM ". _DB_PREFIX_."shop";
$res=dbquery($query);
while ($row=mysqli_fetch_array($res)) 
{ $selected = "";
  if ($row['id_shop'] == $id_shop) 
    $selected=' selected="selected" ';
  echo '<option  value="'.$row['id_shop'].'" '.$selected.'>'.$row['name'].'</option>';
}
echo '</select> &nbsp; ';

if($sortorder == "name") $selected = " selected"; else $selected = "";
echo 'Sort by <select name=sortorder onchange="sortorder_change()"><option>id</option><option '.$selected.'>name</option>';
if($sortorder == "sales") $selected = " selected"; else $selected = "";
echo '<option '.$selected.'>sales</option>';
if($sortorder == "ordercount") $selected = " selected"; else $selected = "";
echo '<option '.$selected.'>ordercount</option>';
if($sortorder == "email") $selected = " selected"; else $selected = "";
echo '<option '.$selected.'>email</option></select>';

$checked = "";
if($rising == 'DESC')
  $checked = "selected";
echo ' &nbsp; <SELECT name=rising><option>ASC</option><option '.$checked.'>DESC</option></select></td>';

echo '</td>';

$default_country = get_configuration_value('PS_COUNTRY_DEFAULT');

echo '<tr><td colspan=2>Registration (yyyy-mm-dd): 
after <input size=7 name=startregis value='.$startregis.'> 
and before <input size=7 name=endregis value='.$endregis.'></td></tr>'; 
echo '<tr><td colspan=2>Last purchase (yyyy-mm-dd): 
after <input size=7 name=startlastpurchase value='.$startlastpurchase.'> 
and before <input size=7 name=endlastpurchase value='.$endlastpurchase.'></td></tr>'; 
echo '<tr><td colspan=2>Postal code between: 
 <input size=7 name=startpcode value='.$startpcode.'> 
and <input size=7 name=endpcode value='.$endpcode.'>';

if($custcompany == "companies") $selected = " selected"; else $selected = "";
echo '&nbsp; &nbsp; <select name=custcompany><option value="all">all customers</option>
<option value="companies" '.$selected.'>companies</option>';
if($custcompany == "private") $selected = " selected"; else $selected = "";
echo '<option value="private" '.$selected.'>private people</option></select>
</td></tr>'; 
echo '<tr><td colspan=2>Order amount between 
<input size=5 name=startamt value='.$startamt.'> 
and <input size=5 name=endamt value='.$endamt.'> and/or 
</td></tr>'; 
echo '<tr><td colspan=2>Nr of orders between 
<input size=2 name=startcnt value='.$startcnt.'> 
and <input size=2 name=endcnt value='.$endcnt.'> in period 
<input size=7 name=startcntperiod value='.$startcntperiod.'> - <input size=7 name=endcntperiod value='.$endcntperiod.'>
</td></tr>'; 
echo '<tr><td colspan=2>Birthday between <input size=7 name=startbd value="'.$startbd.'"> 
and <input size=7 name=endbd value="'.$endbd.'"> &nbsp; &nbsp;';

if($gender == "woman") $selected = " selected"; else $selected = "";
echo '<select name=gender><option value="all">all genders</option>';
echo '<option '.$selected.'>woman</option>';
if($gender == "man") $selected = " selected"; else $selected = "";
echo '<option '.$selected.'>man</option>';
if($gender == "unknown") $selected = " selected"; else $selected = "";
echo '<option '.$selected.'>unknown</option></select> &nbsp; &nbsp;';

if($newssubscribe == "subscribed") $selected = " selected"; else $selected = "";
echo '<select name=newssubscribe><option value="all">all</option>';
echo '<option '.$selected.'>subscribed</option>';
if($newssubscribe == "nope") $selected = " selected"; else $selected = "";
echo '<option value="nope" '.$selected.'>no newsletter</option></select> &nbsp; &nbsp;';

if($optin == "optin") $selected = " selected"; else $selected = "";
echo '<select name=optin><option value="all">all</option>';
echo '<option '.$selected.'>optin</option>';
if($optin == "nope") $selected = " selected"; else $selected = "";
echo '<option value="nope" '.$selected.'>no optin</option></select> &nbsp; &nbsp;';

echo '</td></tr>'; 
echo '<tr><td colspan=2>Startrec: <input size=3 name=startrec value="'.$startrec.'"> &nbsp; ';
echo ' &nbsp; Nr of recs: <input size=3 name=numrecs value="'.$numrecs.'"> &nbsp; &nbsp; ';

if($grouper == "id") $selected = " selected"; else $selected = "";
echo ' &nbsp; &nbsp; &nbsp; Group by <select name=grouper><option>email</option><option value="id" '.$selected.'>customer id</option></select>';

echo '</tr></table></td><td><table>';

echo '<tr><td><input type=submit></td></tr>';
echo '<tr><td><input type=checkbox name=csvexport> Export as CSV</td></tr>';
echo '<tr><td>Separator &nbsp;<input type="radio" name="separator" value="semicolon" checked>;';
echo '<input type="radio" name="separator" value="comma">,</td></tr>';

$checked = "";
if(isset($input["verbose"])) $checked = "checked";
echo '<tr><td><input type=checkbox name=verbose '.$checked.'> verbose</td></tr>';
//$checked = "";
//if(isset($input["unique_id"])) $checked = "checked";
//echo '<tr><td><input type=checkbox name=unique_id '.$checked.'> unique id</td></tr>';
echo '</table></td></tr>';

$sfields = array("main fields", "customer id","name","address","address id","state", "country","email","phone", "lang",
"product id");
  echo '<tr><td colspan=2><table style="text-align:center"><td>Find 
  <input name="search_txt1" type="text" value="'.$search_txt1.'" size="20"  /><br>
in <select name="search_fld1" style="width:10em">';
  foreach($sfields as $sfield)
  { $selected = '';
    if ($input["search_fld1"] == $sfield) 
      $selected=' selected="selected" ';
    echo '<option '.$selected.'>'.$sfield.'</option>';
  }
  echo '</select></td>';

  echo '<td>and <input name="search_txt2" type="text" value="'.$search_txt2.'" size="20"  /><br>
in <select name="search_fld2" style="width:10em">';
  foreach($sfields as $sfield)
  { $selected = '';
    if ($input["search_fld2"] == $sfield) 
      $selected=' selected="selected" ';
    echo '<option '.$selected.'>'.$sfield.'</option>';
  }
  echo '</select>';
  echo '</select></td>';

  echo '<td>and <input name="search_txt3" type="text" value="'.$search_txt3.'" size="20"  /><br>
in <select name="search_fld3" style="width:10em">';
  foreach($sfields as $sfield)
  { $selected = '';
    if ($input["search_fld3"] == $sfield) 
      $selected=' selected="selected" ';
    echo '<option '.$selected.'>'.$sfield.'</option>';
  }
  echo '</select></td>';
  echo '</tr></table></td></tr>';
  
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

  echo '<table ><tr>';
  $x = 0;
  foreach($custfields AS $fieldrow)
  { $checked = in_array($fieldrow[0], $input["fields"]) ? "checked" : "";
		echo '<td><input type="checkbox" name="fields[]" value="'.$fieldrow[0].'" '.$checked.' />'.$fieldrow[0].'</td>';
	if($x==10) echo "</tr><tr>";
    $x++;
  }	
  echo '</tr>';
  
//  echo '<tr><td colspan=10>Sales for <input name=sproducts value='.$sproducts.'>';
//  echo ' (product id\'s or cat id\'s precede by c) between <input name=spstart size=7 value='.$spstart.'>';
//  echo ' and <input name=spend size=7 value='.$spend.'>';
//  echo '</td></tr>';
  
  echo '</table></td></tr></table> &nbsp; ';
  echo '</form>';
  
/* NOW BUILD THE QUERY */
  
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
	  
  if(in_array("phone",$searchfields) || in_array("main fields",$searchfields)
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
	$qfields .= ",GROUP_CONCAT(DISTINCT(c.id_customer) ) AS cust_ids";
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
	if($sfield == "address id")
	{ $subconditions[] = "a.id_address='".$stext."'";
	}
	if($sfield == "state")
	{ $subconditions[] = "st.name LIKE '%".$stext."%'";
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
  
  /* with GROUP BY email we show is_guest for each account */
  $qfields .= ",c.*, GROUP_CONCAT(is_guest  SEPARATOR '<br>') AS is_guest"; 
  
  $query = "SELECT SQL_CALC_FOUND_ROWS ".$qfields.$qbody.$qconditions.$qtail.$qhaving;

  if($sortorder == "id") $srtorder = "c.id_customer"; 
  else if($sortorder == "name") $srtorder = "c.lastname,c.firstname";
  else if($sortorder == "sales") $srtorder = "totsales";
  else if($sortorder == "ordercount") $srtorder = "ordercount";
  else if($sortorder == "email") $srtorder = "email";
//  $query .= " GROUP BY c.id_customer".$having;
  $query .= " ORDER BY ".$srtorder." ".$rising." LIMIT ".$startrec.",".$numrecs;
  $res = dbquery($query);
  $res2=dbquery("SELECT FOUND_ROWS() AS foundrows");
  $row2 = mysqli_fetch_array($res2);
  $numrecs2 = $row2['foundrows'];
  if(($numrecs2-$startrec) < $numrecs) $numrecs = $numrecs2-$startrec;
  if($numrecs <0) $numrecs=0;
  
//  echo $query;
  echo "<br>Your search delivered ".$numrecs2." records. ".$numrecs." (".$startrec."-".($startrec+$numrecs).") displayed. ";
  
  echo '<table class="orderlister"><tr><td>id</td>';
  foreach($custfields AS $custrow)
  { $custfield = $custrow[0];
    if($custfield == "id") continue; // is always shown
    if(!in_array($custfield,$input["fields"])) continue;
    if($custfield == "firstname")
      echo '<td>Firstname</td>';
    else if($custfield == "lastname") 
      echo '<td>Lastname</td>';
    else if($custfield == "company") 
      echo '<td>Company</td>';
    else if($custfield == "addresses")
       echo '<td>Address</td>';
    else if($custfield == "email")
      echo '<td>Email</td>';
    else if($custfield == "phone")
      echo '<td>Phone</td>';
    else if($custfield == "gender")
      echo '<td>Gender</td>';
    else if($custfield == "birthday")
      echo '<td>Birthday</td>';
    else if($custfield == "siret")
      echo '<td>Siret</td>';
    else if($custfield == "ape")
      echo '<td>Ape</td>';
    else if($custfield == "sales")
      echo '<td>Sales</td>';
    else if($custfield == "ordercount")
      echo '<td>Ordercount</td>';
    else if($custfield == "is_guest")
	  echo '<td>guest</td>';
    else if($custfield == "vat_nr")
	  echo '<td>VAT</td>';
    else 
	  echo '<td>'.$custfield.'</td>';
  }
  echo '</tr>';

    $x=0;
    while ($row=mysqli_fetch_array($res))
    { echo '<tr><td>';
      $cust_ids = explode(",",$row['cust_ids']);
	  $first = true;
	  foreach($cust_ids AS $cust_id)
	  { if($first) $first=false;
		else echo '<br>';
	    echo '<a href="order-search.php?search_txt1='.$cust_id.'&search_fld1=customer+id&sortorder=customer" target="_blank">'.$cust_id.'</a>';
	  }	  
	  echo '</td>';
	  
	  foreach($custfields AS $custrow)
      { $custfield = $custrow[0];
	    if($custfield == "id") continue;
        if(!in_array($custfield,$input["fields"])) continue;
        else if($custfield == "addresses")
	    { echo '<td>';
		  if($needorders && (($row["id_address_delivery"] != $row["id_address_invoice"]) || ($row["id_address_delivery"] != $row["id_address"])))
			  echo '<i>';
	      echo $row["address1"]." ".$row["address2"]." ".$row["postcode"]." ".$row["city"]." ".$row["state"];
	      if(($row["id_country"]!= $default_country) && ($row["id_country"]!=0))
		     echo " ".get_country($row["id_country"]);
		  if($needorders && (($row["id_address_delivery"] != $row["id_address_invoice"]) || ($row["id_address_delivery"] != $row["id_address"])))
			  echo '</i>';
          echo '</td>';
	    }
        else if($custfield == "ape")
          echo '<td>'.$row['ape'].'</td>';
        else if($custfield == "birthday")
          echo '<td>'.$row['birthday'].'</td>';
        else if($custfield == "company")
	      echo '<td>'.$row["company"]."</td>";
        else if($custfield == "email")
		  echo '<td><a href="order-search.php?search_txt1='.$row['email'].'&search_fld1=customer+email&sortorder=customer" target="_blank">'.$row['email'].'</a></td>';
        else if($custfield == "firstname")
	      echo '<td>'.$row["firstname"]."</td>";
        else if($custfield == "gender")
          echo '<td>'.$row['id_gender'].'</td>';
        else if($custfield == "is_guest")
         echo '<td>'.$row['is_guest'].'</td>';
        else if($custfield == "lang")
	      echo '<td>'.$row["lang"]."</td>";
        else if($custfield == "lastname")
	      echo '<td>'.$row["lastname"]."</td>";
        else if($custfield == "lastpurchasedate")
          echo '<td>'.substr($row["lastpurchase"],0,10).'</td>'; 
        else if($custfield == "newsletter")
          echo '<td>'.$row["newsletter"].'</td>'; 
 	    else if($custfield == "note")
          echo '<td>'.$row["note"].'</td>'; 
        else if($custfield == "optin")
          echo '<td>'.$row["optin"].'</td>'; 
        else if($custfield == "ordercount")
		{ if($row["ordercount"] == "") $row["ordercount"] = 0;
          echo '<td>'.$row["ordercount"].'</td>';
		}
        else if($custfield == "orders")
          echo '<td>'.$row["orders"].'</td>'; 
        else if($custfield == "phone")
		{ echo '<td>';	
		  if($needorders && (($row["id_address_delivery"] != $row["id_address_invoice"]) || ($row["id_address_delivery"] != $row["id_address"])))
			  echo '<i>';
	      echo $row["phone"]." ".$row["phone_mobile"];
		  if($needorders && (($row["id_address_delivery"] != $row["id_address_invoice"]) || ($row["id_address_delivery"] != $row["id_address"])))
			  echo '</i>';
		  echo '</td>';
		}
        else if($custfield == "registrationdate")
          echo '<td>'.substr($row["registrationdate"],0,10).'</td>'; 
        else if($custfield == "sales")
          echo '<td>'.number_format($row["totsales"],2).'</td>';
        else if($custfield == "siret")
          echo '<td>'.$row['siret'].'</td>';
        else if($custfield == "vat_nr")
         echo '<td>'.$row['vat_number'].'</td>';
        else 
	      echo '<td>'.$custfield.'</td>';
	  }
	  echo '<td>'.$x.'</td>';
	  echo '</tr>';
	  $x++;
    } 
	
echo '</table>';
include "footer1.php";
echo '</body></html>';


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
