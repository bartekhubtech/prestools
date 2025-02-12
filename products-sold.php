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
if(!isset($input['startrec']) || (trim($input['startrec']) == '')) $input['startrec']="0"; else $input['startrec'] = intval(trim($input['startrec']));
if(!isset($input['numrecs']) || (intval(trim($input['numrecs']) == '0'))) $input['numrecs']="1000";
if((!isset($input['paystatus'])) || ($input['paystatus'] == "valid")) {$paystatus = "valid";} else {$paystatus = $input['paystatus'];}

$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_lang = $row['value'];

/* get shop group and its shared_stock status */
if($id_shop != 0)
{ $query="select s.id_shop_group, g.share_stock, g.name from ". _DB_PREFIX_."shop s, "._DB_PREFIX_."shop_group g";
  $query .= " WHERE s.id_shop_group=g.id_shop_group and id_shop='".$id_shop."'";
  $res=dbquery($query);
  $row = mysqli_fetch_array($res);
  $id_shop_group = $row['id_shop_group'];
  $share_stock = $row["share_stock"];
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Ordered Products</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
table.cellspace tr td { padding:7px; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<?php echo '<script type="text/javascript">
function salesdetails(product, pattribute)
{ window.open("product-sales.php?id_product="+product+"&id_product_attribute="+pattribute+"&startdate='.$input["startdate"].'&enddate='.$input["enddate"].'&id_shop='.$id_shop.'","", "resizable,scrollbars,location,menubar,status,toolbar");
  return false;
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
  { searchform.action="products-sold-csv.php";
    searchform.target="_blank";
    searchform.submit();
    searchform.target = "";
	searchform.action = "products-sold.php";
	return false;
  }
  return true;
}

function orderstates_change()
{ var chk = searchform.allorderstates.checked;
  var mydiv = document.getElementById("orderstatediv");
  if(chk)
    mydiv.style.display = "none";
  else 
    mydiv.style.display = "block";
}
</script>
</head><body>';
print_menubar();
echo '<table><tr><td style="width:70%" class="headline"><a href="products-sold.php">Prestashop Ordered Products</a><br>';
echo "For products you can enter comma separated id's and ranges like '6,8-12,15'.
For categories you can enter comma separated values and add an 's' for 'with subcategories' like '6,8s,11'.
<br>Order amounts are converted to the default currency.
<br>P.price is average product price incl. VAT</td></tr><tr><td>";

echo '<form name="searchform" method="get" onsubmit="return check_date();"><table class="triplemain cellspace"><tr><td>
Period (yyyy-mm-dd): <input size=7 name=startdate value='.$input['startdate'].'> till <input size=7 name=enddate value='.$input['enddate'].'> <p>';

echo 'Find <select name=search_fld><option value=0>All</option>';
if($search_fld == "id_product") $selected = "selected"; else $selected="";
echo '<option value="id_product" '.$selected.'>product id</option>';
if($search_fld == "id_category") $selected = "selected"; else $selected="";
echo '<option value="id_category" '.$selected.'>category id</option>';
if($search_fld == "cat_default") $selected = "selected"; else $selected="";
echo '<option value="cat_default" '.$selected.'>default category id</option>';
echo '</select> &nbsp; &nbsp; ';
if($search_fld == "id_product")
  $search_txt = preg_replace('/[^0-9,-]*/','', $search_txt);
else
  $search_txt = preg_replace('/[^0-9,s]*/','', $search_txt);
echo '<input name="search_txt" value='.$search_txt.'>';

echo '<p>Sort by: <select name=order>';
foreach($orderoptions AS $option)
{ $selected = "";
  if($input['order'] == $option)
    $selected = "selected";
  echo '<option '.$selected.'>'.$option.'</option>';
}
echo '</select>';

    $checked = "";
	if((isset($input['rising'])) && ($input['rising'] == 'DESC'))
	  $checked = "selected";
    echo ' &nbsp; <SELECT name=rising><option>ASC</option><option '.$checked.'>DESC</option></select>';

/* making shop block */
	$query= "select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	echo " &nbsp; Shop: <select name=id_shop><option value=0>All shops</option>";
	while ($shop=mysqli_fetch_array($res)) 
	{   $selected = "";
	    if($shop["id_shop"] == $id_shop) $selected = " selected";
        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}
    echo '</select>';
	echo '<p/>Extra fields: ';
	if(!isset($input["fields"])) $input["fields"] = array();
	$checked = in_array("ean", $input["fields"]) ? "checked" : "";
	echo '<input type=checkbox name="fields[]" value="ean" '.$checked.'> ean &nbsp; ';
	$checked = in_array("upc", $input["fields"]) ? "checked" : "";
	echo '<input type=checkbox name="fields[]" value="upc" '.$checked.'> upc &nbsp; ';
	if (version_compare(_PS_VERSION_ , "1.7.7", ">="))
	{ $checked = in_array("mpn", $input["fields"]) ? "checked" : "";
	  echo '<input type=checkbox name="fields[]" value="mpn" '.$checked.'> mpn &nbsp; ';
	}	
	$checked = in_array("ref", $input["fields"]) ? "checked" : "";
	echo '<input type=checkbox name="fields[]" value="ref" '.$checked.'> reference &nbsp; ';
	$checked = in_array("supplref", $input["fields"]) ? "checked" : "";
	echo '<input type=checkbox name="fields[]" value="supplref" '.$checked.'> suppl.ref &nbsp; ';
	$checked = in_array("stock", $input["fields"]) ? "checked" : "";
	echo '<input type=checkbox name="fields[]" value="stock" '.$checked.'> stock &nbsp; ';
	echo '<p/>Startrec: <input size=3 name=startrec value="'.$input['startrec'].'">';
	echo ' &nbsp &nbsp; Number of recs: <input size=3 name=numrecs value="'.$input['numrecs'].'"></td>';

    echo '<td><b>Order statuses</b><br>';
	if(isset($_GET["allorderstates"]) || !isset($_GET["orderstate"]))
	{ $checked = "checked";
	  $hidden = 'style="display:none"';
	}
	else
	  $checked = $hidden = "";
	
	echo '<input type=checkbox name=allorderstates onchange="orderstates_change()" '.$checked.'> all order states<hr>';
	echo '<div id="orderstatediv" '.$hidden.'>';
	if(!isset($_GET["orderstate"]))
	{ $allorderstates = array();
	}
	else
	  $allorderstates = $_GET["orderstate"];

    $query = "SELECT DISTINCT current_state FROM ". _DB_PREFIX_."orders ORDER BY current_state";
    $res = dbquery($query);
    $myorderstates = array();
    while($row = mysqli_fetch_array($res))
    { $squery = "SELECT name, id_order_state FROM ". _DB_PREFIX_."order_state_lang WHERE id_order_state=".$row['current_state']." AND id_lang=".$id_lang;
	  $sres = dbquery($squery);
	  $srow = mysqli_fetch_array($sres);
	  $currentstate = $row['current_state'];
	  echo "<input type=checkbox name=orderstate[".$row['current_state']."] ";
	  if(isset($allorderstates[$currentstate]))
	  {	echo "checked";
		$myorderstates[] = $currentstate;
	  }
	  echo "> ";
	  if($srow)
		echo $srow["name"]."<br>";
	  else
		echo "orderstate ".$row['current_state']."<br>";
    }
	
	echo "</div><p/><b>Paystatus</b><br>";
	if($paystatus == 'all') $checked = "selected"; else $checked = "";
	echo ' &nbsp; &nbsp; <SELECT name=paystatus><option >valid</option><option '.$checked.'>all</option>';
	if($paystatus == 'not paid') $checked = "selected"; else $checked = "";
	echo '<option '.$checked.'>not paid</option>';
	echo '</select>';
  
	echo '</td>';

    echo '<td style="text-align:center; vertical-align:top; padding-top:10px;"><input type=submit><br>';
	echo '<input type=checkbox name=csvexport> Export as CSV<br>';
	echo 'Separator &nbsp;<input type="radio" name="separator" value="semicolon" 
checked>; <input type="radio" name="separator" value="comma"><p>';
	$checked = "";
	if(isset($input["verbose"])) $checked = "checked";
	echo '<input type=checkbox name=verbose '.$checked.'> verbose';
	echo '</td></tr></table>';

  echo '</td></tr></table>';
	
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
// echo "<p>".$query."<p>";

$query2="SELECT FOUND_ROWS() AS rowcount";
$res2=dbquery($query2);
$row2 = mysqli_fetch_array($res2);

if($input['startdate']!="")
{ if($input['enddate']=="")
	$input['enddate'] = date("Y-m-d");
  $datetime1 = new DateTime($input['startdate']);
  $datetime2 = new DateTime($input['enddate']);
  $interval = $datetime1->diff($datetime2);
  $numberDays = 1 + $interval->days; /* we include both start and end day: 1-5 march is 5 days */
}


echo '<p>'.mysqli_num_rows($res).' (of '.$row2["rowcount"].') ordered products shown for period: '.$input['startdate'].' - '.$input['enddate'];
if(isset($numberDays))
  echo " (".$numberDays." days)";
if($id_shop == 0)
  echo " for all shops";
else 
  echo " for shop nr. ".$id_shop;

$infofields = array("id","Attr","Name","category","Quant","p.price","Sales","Sales/tax","returns","refunds", "refunded", "orders");
if(in_array("ean", $input["fields"])) $infofields[] = "ean";
if(in_array("upc", $input["fields"])) $infofields[] = "upc";
if(in_array("mpn", $input["fields"])) $infofields[] = "mpn";
if(in_array("ref", $input["fields"])) $infofields[] = "reference";
if(in_array("supplref", $input["fields"])) $infofields[] = "suppl.ref";
if(in_array("stock", $input["fields"])) $infofields[] = "stock";

echo '<div id="testdiv"><table id="Maintable" border=1><colgroup id="mycolgroup">';
for($i=0; $i<sizeof($infofields); $i++)
  echo "<col id='col".$i."'></col>";
echo '</colgroup><thead><tr>';
for($i=0; $i<sizeof($infofields); $i++)
{ $reverse = "false";
  echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i.', '.$reverse.');">'.$infofields[$i].'</a></th
>';
}
$total = 0;
$sumquantity = $sumtotal = $sumtotalex = 0;
echo "</tr></thead><tbody id='offTblBdy'>";
while($datarow = mysqli_fetch_array($res))
  { echo '<tr>';
	echo '<td>'.$datarow["product_id"].'</td>';
	echo '<td>'.$datarow["product_attribute_id"].'</td>';
	echo '<td>'.$datarow["product_name"].'</td>';
    echo '<td>'.$datarow["id_category_default"].'</td>';
    echo '<td>'.$datarow["quantitytotal"].'</td>';
	$sumquantity += intval($datarow["quantitytotal"]);
	echo '<td>'.number_format(($datarow["pricetotal"]/$datarow["quantitytotal"]),2,".","").'</td>';
	$sumtotal += $datarow["pricetotal"];
	echo "<td><a href onclick='return salesdetails(".$datarow['product_id'].",".$datarow['product_attribute_id'].")' title='show salesdetails'>".number_format($datarow["pricetotal"],2,".","")."</a></td>";
	$sumtotalex += $datarow["pricetotalex"];
    echo '<td>'.number_format($datarow["pricetotalex"],2,".","").'</td>';
    echo '<td>'.$datarow["returns"].'</td>';
	echo '<td>'.$datarow["refunds"].'</td>';
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
	  echo '<td>'.number_format($trow["refunded"],2).'</td>';
    else
	  echo '<td></td>';
    echo '<td>'.$datarow["ordercount"].'</td>';
	if($datarow["product_attribute_id"] == 0)
	{ if(in_array("ean", $input["fields"]))  echo '<td>'.$datarow["ean13"].'</td>';	
      if(in_array("upc", $input["fields"]))  echo '<td>'.$datarow["upc"].'</td>';	
      if(in_array("mpn", $input["fields"]))  echo '<td>'.$datarow["mpn"].'</td>';		
      if(in_array("ref", $input["fields"]))  echo '<td>'.$datarow["reference"].'</td>';
      if(in_array("supplref", $input["fields"]))  echo '<td>'.$datarow["supplier_reference"].'</td>';
	}
	else 
	{ if(in_array("ean", $input["fields"]))  echo '<td>'.$datarow["pa_ean13"].'</td>';	
      if(in_array("upc", $input["fields"]))  echo '<td>'.$datarow["pa_upc"].'</td>';	
      if(in_array("mpn", $input["fields"]))  echo '<td>'.$datarow["pa_mpn"].'</td>';		
      if(in_array("ref", $input["fields"]))  echo '<td>'.$datarow["pa_reference"].'</td>';
      if(in_array("supplref", $input["fields"]))  echo '<td>'.$datarow["pa_supplier_reference"].'</td>';
	}
    if(in_array("stock", $input["fields"]))
    { echo '<td>';
	  if($id_shop == 0)
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
	  echo $strow['quantity'].'</td>';
	}
	echo "</tr
>";

  }
  echo "</tbody></table></div>";
  echo $sumquantity." copies sold of ".mysqli_num_rows($res)." products for in total ".number_format($sumtotal,2)." (".number_format($sumtotalex,2)." without VAT)";
echo '<p>';
  include "footer1.php";
echo '</body></html>';
