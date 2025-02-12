<?php 
/* This script - part of Prestools - gives a list of all the order completed within a certain period */
/* There is no clear way to get the tax rate. So we calculate it from the incl/excl values when
   the price is above 1. Below 1 such calculations could be inaccurate. That depends on how you
   calculated your price. If you set the price incl VAT and allowed the software to calculate the
   price excl the accuracy is high. but in the opposite direction it will be low. Discounts may also
   lower the accuracy. To avoid problems we use the id_tax rate field here instead although
   that is sometimes incorrect. */
/* at the bottom the function normalize_tax($tax) rounds the calculated tax. */

/* STEPS: 
   1. check that ps_order_detail totals match ps_orders total_products 
   2. Get the product VAT rates 
   3. Difftot: does total in ps_orders match the sum of products,shipping, wrapping and discount?
   4. We continue per country as we need to assign carrier, wrapping and discount VAT between rates
   5. Calculate the shipping VAT rates
   6. Calculate the wrapping VAT rates
   7. Handle the discounts
   8. Start output of table. Differences first
   9. Now output the other rates
   */

if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['maxnum'])) $maxnum=25; else $maxnum = intval($input["maxnum"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";
$vatroundings = ['0.0', '0.1','0.2','0.3'];
if(!isset($input['vatrounding']) || (!in_array($input['vatrounding'], $vatroundings)))
	$vatrounding = '0.1';
else
	$vatrounding = $input['vatrounding'];
if(!isset($input['shiprate']) || ($input['shiprate']!='proportional'))
	$shiprate = 'fixed';
else
	$shiprate = 'proportional';
if(!isset($input['wraprate']) || ($input['wraprate']!='proportional'))
	$wraprate = 'fixed';
else
	$wraprate = 'proportional';
$eucountrynames = array("Belgium", "Bulgaria", "Croatia", "Cyprus (the Greek part)", "Denmark",
 "Germany", "Estonia", "Finland", "France", "Greece", "Hungary", "Ireland", "Italy", "Latvia",
 "Lithuania", "Luxembourg", "Malta", "The Netherlands", "Austria", "Poland", "Portugal", "Romania",
 "Slovenia", "Slovakia", "Spain", "Czech Republic", "Sweden");
/*							3			236			74			  76					20		
    1       	86			7		  8			9		 143			26		10		125	
	131   	    12			139				13			  2			14			15		36	
	193			37			6		16				18		*/
$eucountries = array("3", "236", "74", "76", "20", "1", "86", "7", "8", "9", "143", "26", "10", "125", "131", "12", "139", "13", "2", "14", "15", "36", "193", "37", "6", "16", "18");

/* not yet implemented: some shops need to use fixed exchange rates for tax purposes */
$fixedexchangerates = false; 

$id_lang = get_configuration_value('PS_LANG_DEFAULT');
$round_type = get_configuration_value('PS_ROUND_TYPE');
$id_country_default = get_configuration_value('PS_COUNTRY_DEFAULT');
$wrap_tax_rules_group = get_configuration_value('PS_GIFT_WRAPPING_TAX_RULES_GROUP');
$ps_atcp_shipwrap = get_configuration_value('PS_ATCP_SHIPWRAP'); /* Thirty Bees specific: determines that wrapping VAT is average VAT when 1 */
if($ps_atcp_shipwrap)
{ echo "<h2>Your Thirty Bees specific averaging method of calculating the wrapping VAT rate is not yet supported! You may see varying VAT rates.</h2>";
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order and tax list for EU tax advanced</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
.datums { text-align: right; }
td { text-align: right; }
table#searchtable td { text-align: left; }
table#warningtable td { text-align: left; }
table#maintable tr td:nth-child(2) { text-align: left; background: #ccc; }
table#totaltable tr td:nth-child(1) { text-align: left; background: #ccc; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
function normalize_date(thedate)
{ var parts = thedate.split('-');
  if(parts.length != 3) { return -1; }
  if((parts[0] < 1900) || (parts[0] > 2100)) { return -1; }
  if((intval(parts[1]) < 1) || (intval(parts[1]) > 12)) { return -1; }  
  if((intval(parts[2]) < 1) || (intval(parts[2]) > 31)) { return -1; } 
  if(parts[1].length == 1) parts[1] = "0"+parts[1];
  if(parts[2].length == 1) parts[2] = "0"+parts[2];
  return parts.join("-");
}

/* check datums before submit */
/* "new Date" treats "2021-03-03" different from "2021-03-3". In one case it sets the time at 0:00. In the other at 1:00. So we need to normalize. */
function check_data()
{ var startdate = searchform.startdate.value;
  var enddate = searchform.enddate.value;
  if(startdate != "")
  { startdate = normalize_date(startdate);
    var sd = new Date(startdate);
	if((!isValidDate(sd)) || (!isValidDate2(startdate)))
	{ alert("invalid startdate! Format must be yyyy-mm-dd.");
	  return false;
	}
  }
  if(enddate != "")
  { enddate = normalize_date(enddate);
    var ed = new Date(enddate);
	if((!isValidDate(ed)) || (!isValidDate2(enddate)))
	{ alert("invalid enddate! Format must be yyyy-mm-dd.");
	  return false;
	}
  } 
  if(sd > ed)
  { alert("Enddate must be equal to or after startdate! "+sd+" -- "+ed);
	return false;
  }
  return true;
}

function isValidDate(d) 
{ if(!(d.getTime() === d.getTime()))
	return false; /* NaN === NaN returns false */
  return true;
}

function isValidDate2(datestring)
{ var parts = datestring.split("-");
  if((parseInt(parts[0])<2000)||(parseInt(parts[0])>2100)||(parseInt(parts[1])>12)|| (parseInt(parts[2])>31)) /* check for valid dates that don't respect the yyyy-mm-dd format */
	  return false;
  return true;
}

function makeVATlist()
{ check_data(); /* check that dates are valid */
  if(searchform.csvvat.checked)
  {
  }
}

function viewTaxes()
{ var page = document.getElementById("floater");
  var tmp = '<iframe width=290px; height='+(window.innerHeight-100)+'  src="orders-eu-taxlist.php';
  tmp += '?startdate='+searchform.startdate.value+'&enddate='+searchform.enddate.value;
  tmp += '&id_shop='+searchform.id_shop.value+'"></iframe';
  page.innerHTML = tmp;
}

</script>
</head><body>
<?php
print_menubar();
echo '<div id="floater" style="float:right; margin-right:30px">space for VAT numbers</div>';
echo '<a href="orders-eu-tax2.php" style="text-decoration:none"><center><h3>Prestashop Orders in a period for EU Tax sorted by tax rate</h3></center></a>';

echo '<form name="searchform" method="get" onsubmit="return check_data()">
<table id="searchtable"><tr><td>Period (yyyy-mm-dd): <input size=7 name=startdate value="'.$input['startdate'].'" class
="datums"> till <input size=7 name=enddate value="'.$input['enddate'].'" class="datums">';

/* making shop block */
	$query= "select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	echo " &nbsp; Shop: <select name=id_shop><option value=0>All shops</option>";
	while ($shop=mysqli_fetch_assoc($res)) 
	{   $selected = "";
	    if($shop["id_shop"] == $id_shop) $selected = " selected";
        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}
    echo '</select></td>';
	echo '<td rowspan=4 style="text-align:right; width:120px;"><input type="submit"></td></tr>';
	echo '<tr><td>Max examples linked with ordercount <input name=maxnum size=4 value='.$maxnum.'>';
	echo ' &nbsp; Rounding margin <select name="vatrounding">';
    foreach($vatroundings AS $rounding)
	{ $selected = "";
	  if($vatrounding == $rounding)
		  $selected = "selected";
	  echo '<option '.$selected.'>'.$rounding.'</option>';
	}	
	echo '</select></td></tr>';
/*	echo '<tr><td>Shipping VAT rate calculation: ';
	if($shiprate == 'fixed') $sel='selected'; else $sel='';
	echo '<input name=shiprate value="fixed" type=radio '.$sel.'> fixed';
	if($shiprate == 'proportional') $sel='selected'; else $sel='';
	echo '<input name=shiprate value="proportional" type=radio '.$sel.'> proportional</td></tr>';
	echo '<tr><td>Wrapping VAT rate calculation: ';
	if($wraprate == 'fixed') $sel='selected'; else $sel='';
	echo '<input name=wraprate value="fixed" type=radio '.$sel.'> fixed';
	if($wraprate == 'proportional') $sel='selected'; else $sel='';
	echo '<input name=wraprate value="proportional" type=radio '.$sel.'> proportional</td></tr>';
*/
    echo '</table>';

	echo '<p>';

    echo "To make this page a lot of rounding is done. So expect small differences. At the bottom you find a table where you can compare the totals calculated here and the totals that you achieve when you just add all total_paid fields in the ps_orders table of the database. Small differences are almost inevitable. Big differences should be investigated.<br/>";
/*	echo "Some countries have the VAT rate for shipping and wrapping set according to the products. So if you send only 6% products the VAT rate for shipping will be 6% too. When you have a mixed set of products the average of the products is taken. This is what the 'Adapt VAT rate to products VAT' option is for. Without it you will see those average rates. With it the shipping is split up in high and low level VAT.<br>";
*/	echo "The diff field shows the difference between the total_paid field and the amount that you achieve when you combine products, discounts, shipping and wrapping. When you have a module for payment provider fees you may find the costs there. The VAT rate for this diff is calculated by comparing this with similarly calculated amount with VAT. Note that when this field just contains rounding errors this can result in strange VAT rates. To avoid confusion diff values &lt; 0.03 are ignored.<br>";
	echo "This script tries both the taxrate field in "._DB_PREFIX_."order_detail and the id_tax field in "._DB_PREFIX_."order_detail_tax. If neither produces a correct result for the selected period you will see an 'incorrect settings' warning.";
	
	echo "<br/>Orders with an invoice date within the follow period have been included: startdate=".$input["startdate"]." - enddate=".$input["enddate"]." for ";
	if($id_shop == 0)
		echo "all shops";
	else 
		echo "shop nr. ".$id_shop;
	echo "<p> - You are advised to run the script for a period when no more changes for its orders are expected. Changes happening later (incoming payments, cancellations and modifications) will otherwise be missed.
	<br> - Restitutions are never included. 
	<br/> - Orders without VAT still can pay VAT on shipping.
	
	<p>The lines with '-' at the place of the rates contain calculation differences. As the figures are clickable you can check the orders for yourself. These are often errors that should be checked and repaired. There are three types of error:
	<br> - Numbers in product fields: the sum of the products in the ps_order_detail table doesn't match the matching fields in ps_orders.
	<br> - Numbers in the discount fields: somehow the discount doesn't fit with the order lines. Note that order level discounts should be evenly spread between high and low level VAT products.
	<br> - Numbers in the Diff fields: the numbers in the ps_orders table don't match. The total paid fields should match the sum of products, discounts, shipping and wrapping. Sometimes these are legitimate differences - for example created by a module that charges for Paypal or credit card use.	
	";
	$round_type = get_configuration_value('PS_ROUND_TYPE'); /* ROUND_ITEM, ROUND_LINE or ROUND_TOTAL */
	if($round_type != ROUND_LINE)
	{ if($round_type == ROUND_ITEM) $rounding="item"; else $rounding="order total";
	  echo '<br> - This script does the rounding at line level. You shop uses the '.$rounding.' level. This may cause small differences!';
    }
	
  /* 1. check that ps_order_detail totals match ps_orders total_products */
  $query= "select o.id_order ";
  $query .= " ,total_products, total_products_wt";
  $query .= " ,SUM(od.total_price_tax_incl) AS incls, SUM(od.total_price_tax_excl) AS excls";
  $query .= " ,(SUM(od.total_price_tax_excl) - total_products) AS exclat";
  $query .= " ,(SUM(od.total_price_tax_incl) - total_products_wt) AS inclat";
  $query .= " ,total_discounts_tax_excl, id_country";
  $query .= " FROM "._DB_PREFIX_."order_detail od";
  $query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
  $query .= " LEFT JOIN "._DB_PREFIX_."address a ON o.id_address_delivery=a.id_address";
  $query .= " WHERE o.valid=1";
  if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
  $query .= " GROUP BY o.id_order";
  $query .= " HAVING (ABS(exclat)>0.05) OR (ABS(inclat)>0.05)";
  $query .= " ORDER BY o.id_order DESC";
  $res=dbquery($query);
  $proddiffs = [];
  echo "<p>This script uses the total_price_tax_incl/total_price_tax_excl fields in 
    your "._DB_PREFIX_."order_detail table to calculate the VAT amounts and rates. 
	This assumes that their total matches the total_products/total_products_wt fields in 
	your "._DB_PREFIX_."orders table. When the amount involved is smaller than 1 the id_tax
	field in the "._DB_PREFIX_."order_detail_tax table is used instead to get the rate. ";
  if(mysqli_num_rows($res) > 0)
  { echo "<b> That is not the case for the following 
	".mysqli_num_rows($res)." orders. The numbers between brackets are the differences 
	excl and incl VAT: ";
	while($row = mysqli_fetch_assoc($res))
	{ echo '<a href="order-edit.php?id_order='.$row['id_order'].'" target=_blank>'.$row['id_order']."</a>(".round($row['exclat'],2)."/".round($row['inclat'],2)."), ";
	  if(!isset($proddiffs[$row['id_country']]))
		$proddiffs[$row['id_country']] = [0,0,[]];
	  $proddiffs[$row['id_country']][0] -= round($row['exclat'],2);
	  $proddiffs[$row['id_country']][1] -= round($row['inclat'],2);
	  $proddiffs[$row['id_country']][2][] = $row['id_order'];
	}
	echo "</b>";
  }

if(!isset($_GET['id_shop']))
{ include "footer1.php";
  echo '</body></html>';
  exit();
}
	
$default_currency = get_configuration_value('PS_CURRENCY_DEFAULT');
$query="SELECT conversion_rate FROM "._DB_PREFIX_."currency WHERE id_currency=".$default_currency;
$res=dbquery($query);
$row = mysqli_fetch_assoc($res);
if($row["conversion_rate"] != 1) colordie("Currency problem; this page cannot work for you.");

$currencies  = array();
$currencies[0] = "merged"; /* all the currencies together after conversion */
$query="SELECT * FROM "._DB_PREFIX_."currency ORDER BY iso_code";
$res=dbquery($query);
while($row = mysqli_fetch_assoc($res))
{ $curr = $row["id_currency"];
  $currencies[$curr] = $row["iso_code"];
}

/* 2. Get the product VAT rates */
/* $VATproducts[$id_country][$taxrate] => ['excls'],['incls'],['ordercount'],['orderlist'] */
/* $round_type: We always use ROUND_LINE here */
$VATproducts = $VATcarriers = $VATdiscounts = $VATwrappers = [];
$countrytotals = [];
$query= "select SUM(od.total_price_tax_incl/o.conversion_rate) AS incls,  SUM(od.total_price_tax_excl/o.conversion_rate) AS excls, a.id_country,";
/* we used calculated tax rates, except for prices < 1 as that gives inaccuracies */
$query .= " IF(od.unit_price_tax_excl < 1.0, rate,  ROUND(((od.total_price_tax_incl-od.total_price_tax_excl)*100/od.total_price_tax_excl),1)) AS taxrate,";
$query .= " COUNT(DISTINCT o.id_order) AS ordercount,
GROUP_CONCAT(DISTINCT o.id_order ORDER BY o.id_order DESC) AS myorders FROM "._DB_PREFIX_."order_detail od";
$query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
/* when odt.total_amount=0 we take taxrate as 0 */
$query .= " LEFT JOIN "._DB_PREFIX_."order_detail_tax odt ON od.id_order_detail=odt.id_order_detail";
$query .= " LEFT JOIN "._DB_PREFIX_."tax t ON odt.id_tax=t.id_tax";
$query .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
$query .= " WHERE o.valid=1 AND od.total_price_tax_excl!=0";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY a.id_country, taxrate";
$query .= " ORDER BY id_country, taxrate";
$res=dbquery($query);
while($row = mysqli_fetch_assoc($res))
{ $row['taxrate'] = normalize_tax($row['taxrate']);

  if(!isset($VATproducts[$row['id_country']]))
    $VATproducts[$row['id_country']] = [];
  if(!isset($VATproducts[$row['id_country']][$row['taxrate']]))
  { $VATproducts[$row['id_country']][$row['taxrate']] = [];
    $VATproducts[$row['id_country']][$row['taxrate']]['excls'] = 0;
    $VATproducts[$row['id_country']][$row['taxrate']]['incls'] = 0;
    $VATproducts[$row['id_country']][$row['taxrate']]['ordercount'] = 0;
	$VATproducts[$row['id_country']][$row['taxrate']]['orderlist'] = '';
  }
  $VATproducts[$row['id_country']][$row['taxrate']]['excls'] += $row['excls'];
  $VATproducts[$row['id_country']][$row['taxrate']]['incls'] += $row['incls'];
  $VATproducts[$row['id_country']][$row['taxrate']]['ordercount'] += $row['ordercount'];
  $VATproducts[$row['id_country']][$row['taxrate']]['orderlist'] .= ",".$row['myorders'];
}

/* 3. Difftot: does total in ps_orders match the sum of products,shipping, wrapping and discount? */
  $query= "select ((o.total_paid_tax_excl+o.total_discounts_tax_excl-o.total_products- o.total_shipping_tax_excl-o.total_wrapping_tax_excl)/o.conversion_rate) AS difftot, 
  ((o.total_paid_tax_incl+o.total_discounts_tax_incl-o.total_products_wt- o.total_shipping_tax_incl-o.total_wrapping_tax_incl)/o.conversion_rate) AS difftotincl, 
  a.id_country, o.id_order";
  $query .= " FROM "._DB_PREFIX_."orders o";
  $query .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
  $query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
  $query .= " WHERE o.valid=1";
  if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
  $query .= " HAVING (abs(difftot)>0.03) OR (abs(difftotincl)>0.03)";
  $res=dbquery($query);
  $totdiffs = [];
  while($row = mysqli_fetch_assoc($res))
  { if(!isset($VATproducts[$row['id_country']]))
      $VATproducts[$row['id_country']] = [];
    if(!isset($totdiffs[$row['id_country']]))
		$totdiffs[$row['id_country']] = [0,0,[]];
	$totdiffs[$row['id_country']][0] += round(($row['difftot']),2);
	$totdiffs[$row['id_country']][1] += round(($row['difftotincl']),2);	
	$totdiffs[$row['id_country']][2][] = $row['id_order'];
  }

$infofields = array("id","Country","Tax rate", "Products excl","Products incl", "Carriers excl","Carriers incl","Wrapping excl","Wrapping incl","Order count","Prod Discount","Ship Discount","Diff excl","Diff incl", "Total excl", "Total incl", "Total VAT","Country excl","Country incl","Country VAT");
echo '<div id="testdiv"><table id="maintable" border=1 class="triplemain"><colgroup id="mycolgroup">';
for($i=0; $i<sizeof($infofields); $i++)
  echo "<col id='col".$i."'></col>";
echo '</colgroup><thead><tr>';
for($i=0; $i<sizeof($infofields); $i++)
{ $reverse = "false";
  echo '<th><a href="" onclick="this.blur(); return sortTable(\'offTblBdy\', '.$i.', '.$reverse.');">'.$infofields[$i].'</a></th
>';
}

/* 4. We continue per country as we need to assign carrier, wrapping and discount VAT between rates */
$countrynames = [];
$qry = "SELECT id_country, name FROM "._DB_PREFIX_."country_lang";
$qry .= " WHERE id_lang=".$id_lang." AND id_country IN (".implode(",",array_keys($VATproducts)).")";
$qry .= " ORDER BY name";
$rs=dbquery($qry);
while($rw = mysqli_fetch_assoc($rs))
  $countrynames[$rw["name"]] = $rw["id_country"];

foreach($countrynames AS $country => $id_country)
{ $countryblock = $VATproducts[$id_country];

  /* 5. Calculate the shipping VAT rates */
  if($shiprate == 'fixed')
  {/* carrier_tax_rate in ps_orders is not reliable as it is not changed when it should be zero */
    $query= "select SUM(o.total_shipping_tax_incl/o.conversion_rate) AS incls, SUM(o.total_shipping_tax_excl/o.conversion_rate) AS excls, a.id_country, o.carrier_tax_rate, 
	ROUND(((o.total_shipping_tax_incl-o.total_shipping_tax_excl)*100/o.total_shipping_tax_excl),1) AS taxrate, GROUP_CONCAT(DISTINCT o.id_order ORDER BY o.id_order DESC) AS myorders";
	$query .= " FROM "._DB_PREFIX_."orders o";
	$query .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
	$query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
	$query .= " WHERE a.id_country=".$id_country." AND o.valid=1 AND total_shipping_tax_excl!=0";
	if($id_shop !=0)
		$query .= " AND o.id_shop=".$id_shop;
	if($input['startdate'] != "")
		$query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
	if($input['enddate'] != "")
		$query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
	$query .= " GROUP BY taxrate";
	$res=dbquery($query);
	while($row = mysqli_fetch_assoc($res))
	{ $row['taxrate'] = normalize_tax($row['taxrate']);
	  check_countryVAT($countryblock, $row['taxrate']);
	  $countryblock[$row['taxrate']]['ship_excl'] += $row['excls'];
	  $countryblock[$row['taxrate']]['ship_incl'] += $row['incls'];
	  $countryblock[$row['taxrate']]['ship_products'] .= ",".$row['myorders'];
	}
  }

  /* 6. Calculate the wrapping VAT rates */
  if($wraprate == 'fixed')
  { $query= "select SUM(o.total_wrapping_tax_incl/o.conversion_rate) AS incls, 
    SUM(o.total_wrapping_tax_excl/o.conversion_rate) AS excls, a.id_country, t.rate, 
    ROUND(((o.total_wrapping_tax_incl - o.total_wrapping_tax_excl)*100/o.total_wrapping_tax_excl),1) AS taxrate";
    $query .= " FROM "._DB_PREFIX_."orders o";
    $query .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
    $query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
    $query .= " LEFT JOIN "._DB_PREFIX_."tax_rule tr ON tr.id_tax_rules_group =".$wrap_tax_rules_group." AND tr.id_country=a.id_country";
    $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON tr.id_tax=t.id_tax";
    $query .= " WHERE a.id_country=".$id_country." AND o.valid=1 AND total_wrapping_tax_excl!=0";
    if($id_shop !=0)
	  $query .= " AND o.id_shop=".$id_shop;
    if($input['startdate'] != "")
      $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
    if($input['enddate'] != "")
      $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
    $query .= " GROUP BY t.rate";
    $res=dbquery($query);
    while($row = mysqli_fetch_assoc($res))
    { $row['taxrate'] = normalize_tax($row['taxrate']);
	  check_countryVAT($countryblock, $row['taxrate']);
	  $countryblock[$row['taxrate']]['wrap_excl'] += $row['excls'];
	  $countryblock[$row['taxrate']]['wrap_incl'] += $row['incls'];
	}
  }

  /* 7. Handle the discounts */
  /* Discounts present two problems: 
      - Prestashop combines product and shipping discounts in one field
	  - the VAT of product discounts must be proportionally distributed. This means that we need 
		to make the calculation for every order separately */
  /*  first look for discounts in ps_orders. */
  $dquery= "select o.id_order,total_discounts_tax_excl/conversion_rate AS total_discounts_tax_excl
  , total_discounts_tax_incl/conversion_rate AS  total_discounts_tax_incl, total_products/conversion_rate AS total_products
  , total_products_wt/conversion_rate AS total_products_wt, conversion_rate
  , total_paid_tax_incl/conversion_rate AS total_paid_tax_incl, total_paid_tax_excl/conversion_rate AS total_paid_tax_excl
  , total_shipping_tax_incl/conversion_rate AS total_shipping_tax_incl, total_shipping_tax_excl/conversion_rate AS total_shipping_tax_excl
  , total_wrapping_tax_incl/conversion_rate AS total_wrapping_tax_incl, total_wrapping_tax_excl/conversion_rate AS total_wrapping_tax_excl, SUM(od.total_price_tax_excl/conversion_rate) AS exclsum, SUM(od.total_price_tax_incl/conversion_rate) AS inclsum";
  $dquery .= " FROM "._DB_PREFIX_."orders o";
  $dquery .= " LEFT JOIN "._DB_PREFIX_."order_detail od ON od.id_order=o.id_order";
  $dquery .= " LEFT JOIN "._DB_PREFIX_."order_detail_tax odt ON od.id_order_detail=odt.id_order_detail";
  $dquery .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
  $dquery .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
  $dquery .= " WHERE o.valid=1 AND a.id_country=".$id_country;
  $dquery .= " AND o.total_discounts>0";
  if($id_shop !=0)
	$dquery .= " AND o.id_shop=".$id_shop;
  if($input['startdate'] != "")
    $dquery .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
  if($input['enddate'] != "")
    $dquery .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
  $dquery .= " GROUP BY o.id_order";
  $dres=dbquery($dquery);
  while($drow = mysqli_fetch_assoc($dres))
  { $conversion_rate = $drow["conversion_rate"];

    if($drow["total_discounts_tax_excl"]>($drow["total_shipping_tax_excl"]+$drow["total_products"]))
	{ echo "<br><b>Discount excl VAT for order ".$drow['id_order']." is larger than shipping and products combined</b>";
	  if(!isset($discdiffs[$id_country]))
		  $discdiffs[$id_country] = [0,0,[]];
	  $discdiffs[$id_country][0] += round(($drow["total_discounts_tax_excl"]-($drow["total_shipping_tax_excl"]+$drow["total_products"])),2);
	  $discdiffs[$id_country][2][] = $drow['id_order'];
	  $drow["total_discounts_tax_excl"]=($drow["total_shipping_tax_excl"]+$drow["total_products"]);
	}
    if($drow["total_discounts_tax_incl"]>($drow["total_shipping_tax_incl"]+$drow["total_products_wt"]))
	{ echo "<br><b>Discount incl VAT for order ".$drow['id_order']." is larger than shipping and products combined</b>";
	  if(!isset($discdiffs[$id_country]))
		  $discdiffs[$id_country] = [0,0,[]];
	  $discdiffs[$id_country][1] += round(($drow["total_discounts_tax_incl"]-($drow["total_shipping_tax_incl"]+$drow["total_products_wt"])),2);
	  $discdiffs[$id_country][2][] = $drow['id_order'];
	  $drow["total_discounts_tax_incl"]=($drow["total_shipping_tax_incl"]+$drow["total_products_wt"]);
	}
   
    if((abs($drow["total_shipping_tax_excl"] - $drow["total_discounts_tax_excl"]) <0.01) &&
	    (abs($drow["total_shipping_tax_incl"] - $drow["total_discounts_tax_incl"]) <0.01))
	{ $rate = (($drow["total_shipping_tax_incl"]/$drow["total_shipping_tax_excl"])-1)*100;
	  $rate = normalize_tax($rate);
	  check_countryVAT($countryblock, $rate);
	  $countryblock[$rate]["shipdiscount_excl"]+=$drow["total_discounts_tax_excl"]; 
	  $countryblock[$rate]["shipdiscount_incl"]+=$drow["total_discounts_tax_incl"]; 
	}
/* Thirty Bees sometimes leaves a record with the full price in ps_order_detail_tax when the products have been discounted to zero.  */
/*	elseif (($drow["total_products"] == $drow["total_discounts_tax_excl"]) &&
	    ($drow["total_products_wt"] == $drow["total_discounts_tax_incl"]))
	{ $countryproducts[$rate]["proddiscount_excl"]+=$drow["total_discounts_tax_excl"]; 
	  $countryproducts[$rate]["proddiscount_incl"]+=$drow["total_discounts_tax_incl"]; 
	}
*/
  /* now comes the difficult part. There are two possibilities: free shipping or not. 
   * There is no database field that flags this.
   * So we just try which fits */
	else
	{	$success = false;
		$proddiscount_excl = $drow["total_discounts_tax_excl"];
		$proddiscount_incl = $drow["total_discounts_tax_incl"];
		$discountVAT = $drow["total_discounts_tax_incl"]-$drow["total_discounts_tax_excl"];
		
		$pquery= "select SUM(od.total_price_tax_incl/".$conversion_rate.") AS incls
		,od.tax_rate AS od_rate, SUM(od.total_price_tax_excl/".$conversion_rate.") AS excls,";
		$pquery .= " IF(od.unit_price_tax_excl < 1.0, tr.trate,  ROUND(((od.total_price_tax_incl-od.total_price_tax_excl)*100/od.total_price_tax_excl),1)) AS taxrate";
		$pquery .= " FROM "._DB_PREFIX_."order_detail od";
	    $pquery .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
  /* when odt.total_amount=0 we take taxrate as 0 */
	    $pquery .= " LEFT JOIN (SELECT IF(odt.total_amount>0.0001,t.rate,0) AS trate, id_order_detail FROM "._DB_PREFIX_."order_detail_tax odt";
	    $pquery .= " LEFT JOIN "._DB_PREFIX_."tax t ON odt.id_tax=t.id_tax";
	    $pquery .= " ) AS tr ON od.id_order_detail=tr.id_order_detail";
	    $pquery .= " WHERE od.id_order=".$drow['id_order'];
	    $pquery .= " GROUP BY taxrate";
        $pres=dbquery($pquery);
		
		/* first try without free shipping */
		/* note that free shipping is all or nothing */
		if($drow["total_discounts_tax_excl"] <= $drow["total_products"])
		{ $totVAT = 0;
	      while($prow = mysqli_fetch_assoc($pres))		  
		  { $prodVAT = ($prow["incls"]-$prow["excls"])*($prow["excls"]/$drow["exclsum"])*($proddiscount_excl/$drow["exclsum"]);
			$totVAT += $prodVAT;
		  }
		  if((abs($discountVAT-$totVAT) < 0.03) || ($drow["total_discounts_tax_excl"] < $drow["total_shipping_tax_excl"]))
	      { mysqli_data_seek($pres,0);
	        $odisc_excl = $odisc_incl = 0;
			while($prow = mysqli_fetch_assoc($pres))
            { $prow['taxrate'] = normalize_tax($prow['taxrate']);
			  check_countryVAT($countryblock, $rate);
	          $countryblock[$prow['taxrate']]["proddiscount_excl"] += $proddiscount_excl*$prow["excls"]/$drow["exclsum"];
			  $odisc_excl += $proddiscount_excl*$prow["excls"]/$drow["exclsum"];
		      $countryblock[$prow['taxrate']]["proddiscount_incl"] += $proddiscount_incl*$prow["excls"]/$drow["exclsum"];
			  $odisc_incl += $proddiscount_incl*$prow["excls"]/$drow["exclsum"];
			}
			if(abs($discountVAT-$totVAT) > 0.03)
			{ if(!isset($discdiffs[$id_country]))
			    $discdiffs[$id_country] = [0,0,[]];
			  $discdiffs[$id_country][0] += round(($proddiscount_excl - $odisc_excl),2);	
			  $discdiffs[$id_country][1] += round(($proddiscount_incl - $odisc_incl),2);	
			  $discdiffs[$id_country][2][] = $drow['id_order'];
			}
			$success = true;
		  }
		}
		if(!$success) /* now try with free shipping */
		{ if($drow["total_shipping_tax_excl"] != 0) /* prevent partition by zero */
		  { $rate = (($drow["total_shipping_tax_incl"]/$drow["total_shipping_tax_excl"])-1)*100;
	 	    $rate = normalize_tax($rate);
		    check_countryVAT($countryblock, $rate);
		    $countryblock[$rate]["shipdiscount_excl"]+=$drow["total_shipping_tax_excl"];
		    $countryblock[$rate]["shipdiscount_incl"]+=$drow["total_shipping_tax_incl"]; 
		    $proddiscount_excl -= $drow["total_shipping_tax_excl"];
		    $proddiscount_incl -= $drow["total_shipping_tax_incl"];
		  }
		  $totVAT2 = $drow["total_shipping_tax_incl"] - $drow["total_shipping_tax_excl"];
		  mysqli_data_seek($pres,0);
	      $odisc_excl = $odisc_incl = 0;
	      while($prow = mysqli_fetch_assoc($pres))		  
		  { $prow['taxrate'] = normalize_tax($prow['taxrate']);
			check_countryVAT($countryblock, $rate);
	        $countryblock[$prow['taxrate']]["proddiscount_excl"] += $proddiscount_excl*$prow["excls"]/$drow["exclsum"];
			$odisc_excl += $proddiscount_excl*$prow["excls"]/$drow["exclsum"];
		    $countryblock[$prow['taxrate']]["proddiscount_incl"] += $proddiscount_incl*$prow["excls"]/$drow["exclsum"];
			$odisc_incl += $proddiscount_incl*$prow["excls"]/$drow["exclsum"];
			$prodVAT = ($prow["incls"]-$prow["excls"])*($prow["excls"]/$drow["exclsum"])*($proddiscount_excl/$drow["exclsum"]);
			
			$totVAT2 += $prodVAT;
		  }
		  
		  if(abs($discountVAT-$totVAT2) > 0.03)
		  { if(!isset($discdiffs[$id_country]))
			    $discdiffs[$id_country] = [0,0,[]];
			$discdiffs[$id_country][0] += round(($proddiscount_excl - $odisc_excl),2);
			$discdiffs[$id_country][1] += round(($proddiscount_incl - $odisc_incl),2);	
			$discdiffs[$id_country][2][] = $drow['id_order'];
		  }
		}
	}
  }

  /* 8. Start output of table. Differences first */
  $ctotalexcl = $ctotalincl = 0;
  $tmp = [];
  $x=0;
  
  /* make a Diff row first: this has a '-' instead of a tax rate */
  if(isset($proddiffs[$id_country]) || isset($discdiffs[$id_country]) || isset($totdiffs[$id_country]))
  { $cptotalexcl = $cptotalincl = 0;
    $tmp[$x] = '<tr><td>'.$id_country.'</td><td>'.$country.'</td><td>-</td><td>';
    if(isset($proddiffs[$id_country]))
	{ $tmp[$x] .= '<a href="order-search.php?search_txt1='.implode(",",$proddiffs[$id_country][2]).'&search_fld1=order+id" target=_blank>'.$proddiffs[$id_country][0];
	  $tmp[$x] .= '</td><td>'.$proddiffs[$id_country][1].'</td>';
	  $cptotalexcl += $proddiffs[$id_country][0];
	  $cptotalincl += $proddiffs[$id_country][1];
	}
    else
	  $tmp[$x] .= '</td><td></td>';
				/*    carriers         wrapping      ordercount  */
    $tmp[$x] .= '<td></td><td></td><td></td><td></td><td></td>';
    if(isset($discdiffs[$id_country]))
	{ $discdiffs[$id_country][2] = array_unique($discdiffs[$id_country][2]);
      $tmp[$x] .= '<td><a href="order-search.php?search_txt1='.implode(",",$discdiffs[$id_country][2]).'&search_fld1=order+id" target=_blank>'.$discdiffs[$id_country][0];
	  $tmp[$x] .= '/'.$discdiffs[$id_country][1].'</td>';
	  $cptotalexcl += $discdiffs[$id_country][0];
	  $cptotalincl += $discdiffs[$id_country][1];
	}
    else
	  $tmp[$x] .= '<td></td>';
	$tmp[$x] .= '<td></td><td>';
    if(isset($totdiffs[$id_country]))
	{ rsort($totdiffs[$id_country][2]);
      $tmp[$x] .= '<a href="order-search.php?search_txt1='.implode(",",$totdiffs[$id_country][2]).'&search_fld1=order+id" target=_blank>'.$totdiffs[$id_country][0];
	  $tmp[$x] .= '</td><td>'.$totdiffs[$id_country][1].'</td>';
	  $cptotalexcl += $totdiffs[$id_country][0];
	  $cptotalincl += $totdiffs[$id_country][1];
	}
    else
	  $tmp[$x] .= '</td><td></td>';

    $tmp[$x] .= '<td>'.$cptotalexcl.'</td><td>'.$cptotalincl.'</td><td>'.$cptotalincl-$cptotalexcl.'</td>';
	$ctotalexcl += $cptotalexcl;
	$ctotalincl += $cptotalincl;
	$x++;
  }
   
  /* 9. Now output the other rates */
  foreach($countryblock AS $rate => $line)
    check_countryVAT($countryblock, $rate); /* make sure all fields have a value */
  
  /* we cannot immediately output the lines as we must collect the countrytotals for the last
   * columns. So we output to a $tmp array first. */
   
  foreach($countryblock AS $rate => $line)
  { $tmp[$x] = "";
    $cptotalexcl = $cptotalincl = 0;
    $tmp[$x] = '<tr><td>'.$id_country.'</td><td>'.$country.'</td>';
//	$erroneous_calculation=0;
//	if(isset($line['excls']) && ($line['excls']!=0) && $erroneous_calculation)
//	{ $calc_rate = 100*($line['incls']-$line['excls'])/$line['excls'];
//	}
//    if(isset($line['excls']) && ($line['excls']!=0) && $erroneous_calculation && (($calc_rate - $rate) > 0.01))
//	{ 
//	  $tmp[$x] .= '<td>'.$line['od_rate'].'/'.$rate.'/'.number_format($calc_rate,3).'</td>';
//	}
//	else
	$tmp[$x] .= '<td>'.$rate.'</td>';
    $tmp[$x] .= '<td>'.number_format($line['excls'],2).'</td><td>'.number_format($line['incls'],2).'</td>';
    $cptotalexcl += round($line['excls'],2);
	$cptotalincl += round($line['incls'],2);
	if(strlen($countryblock[$rate]['ship_products']) >= 1024)
	{ $pos = strrpos($countryblock[$rate]['ship_products'],",");
	  $countryblock[$rate]['ship_products'] = substr($countryblock[$rate]['ship_products'],0,$pos);
	}
    $orderlist = explode(",",$countryblock[$rate]['ship_products']);
	$orderlist = array_filter($orderlist); /* remove empty values */
	rsort($orderlist, SORT_NUMERIC);
	array_splice($orderlist,$maxnum);
	$tmp[$x] .= '<td><a href="order-search.php?search_txt1='.implode(",",$orderlist).'&search_fld1=order+id" target=_blank>'.number_format($countryblock[$rate]['ship_excl'],2).'</a></td>';
	$tmp[$x] .= '<td>'.number_format($countryblock[$rate]['ship_incl'],2).'</td>';
	$cptotalexcl += round($countryblock[$rate]['ship_excl'],2);
	$cptotalincl += round($countryblock[$rate]['ship_incl'],2);


	$tmp[$x] .= '<td>'.number_format($countryblock[$rate]['wrap_excl'],2).'</td><td>'.number_format($countryblock[$rate]['wrap_incl'],2).'</td>';
	$cptotalexcl += round($countryblock[$rate]['wrap_excl'],2);
	$cptotalincl += round($countryblock[$rate]['wrap_incl'],2);

    if(($maxnum == 0) || ($line['ordercount']==0))
	  $tmp[$x] .= '<td>'.$line['ordercount'].'</td>';
	else
	{ /* MYSQL has default maxsize for GROUP_CONCAT of 1024. If that size is exceeded
		 the last item may be broken, so we remove it. See group_concat_max_len. */
	  if(strlen($line['orderlist']) >= 1024)
	  { $pos = strrpos($line['orderlist'],",");
		$line['orderlist'] = substr($line['orderlist'],0,$pos);
	  }
      $orderlist = explode(",",$line['orderlist']);
	  $orderlist = array_filter($orderlist);  /* remove empty values */
	  rsort($orderlist, SORT_NUMERIC);
	  array_splice($orderlist,$maxnum);
	  $tmp[$x] .= '<td><a href="order-search.php?search_txt1='.implode(",",$orderlist).'&search_fld1=order+id" target=_blank>'.$line['ordercount'].'</a></td>';
	}

    $tmp[$x] .= '<td>'.number_format($line['proddiscount_excl'],2).'/'.number_format($line['proddiscount_incl'],2).'</td>';
    $tmp[$x] .= '<td>'.number_format($line['shipdiscount_excl'],2).'/'.number_format($line['shipdiscount_incl'],2).'</td>';
	$cptotalexcl -= round($line['proddiscount_excl'],2);
	$cptotalincl -= round($line['proddiscount_incl'],2);
	$cptotalexcl -= round($line['shipdiscount_excl'],2);
	$cptotalincl -= round($line['shipdiscount_incl'],2);
    $tmp[$x] .= '<td>0</td><td>0</td>'; 
    $tmp[$x] .= '<td>'.number_format($cptotalexcl,2).'</td>';
    $tmp[$x] .= '<td>'.number_format($cptotalincl,2).'</td>';
    $tmp[$x] .= '<td>'.number_format(($cptotalincl-$cptotalexcl),2).'</td>';
	$ctotalexcl += $cptotalexcl;
	$ctotalincl += $cptotalincl;
	$x++;
  }
  /* now add totals at the end of the first line */
  $tmp[0] .= '<td rowspan='.sizeof($tmp).'>'.number_format($ctotalexcl,2).'</td>';
  $tmp[0] .= '<td rowspan='.sizeof($tmp).'>'.number_format($ctotalincl,2).'</td>';
  $tmp[0] .= '<td rowspan='.sizeof($tmp).'>'.number_format(($ctotalincl-$ctotalexcl),2).'</td>';
  /* with the totals we are complete and can echo */
  foreach($tmp AS $line)
    echo $line.'</tr>';
  add_to_countrytotals($id_country,$ctotalexcl,$ctotalincl); /* put totals in array for later comparison */
}
echo '</table>';

echo "<h2>Country totals</h2>";
echo "The sum fields contain the totals from the previous block";
echo "<br>The totals are what you actually received according to the orders table.";
echo "<br>The sum fields and the total fields should contain the same values. If not you should look what caused the difference.";
echo "<br>You can get a list of orders with VAT exemption for this period <a href='#' onclick='viewTaxes(); return false;'>here</a>. It will appear at the top right of this window.";
echo "<table id='totaltable' border=1><tr><td>country</td><td>sum excl</td><td>sum incl</td><td>total excl</td><td>total incl</td></tr>";
$query= "select SUM(o.total_paid_tax_incl/o.conversion_rate) AS incls,   SUM(o.total_paid_tax_excl/o.conversion_rate) AS excls,SUM(o.total_discounts_tax_incl/o.conversion_rate) AS disincls,   SUM(o.total_discounts_tax_excl/o.conversion_rate) AS disexcls, a.id_country, cl.name AS country ";
$query .= " FROM "._DB_PREFIX_."orders o";
$query .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
$query .= " LEFT JOIN ". _DB_PREFIX_."country_lang cl ON cl.id_country = a.id_country AND cl.id_lang='".$id_lang."'";
$query .= " WHERE o.valid=1";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY a.id_country";
$query .= " ORDER BY country";
$res=dbquery($query);
while($row = mysqli_fetch_assoc($res))
{ if((abs($row['excls'] - $countrytotals[$row['id_country']][0])>0.01) || (abs($row['incls'] - $countrytotals[$row['id_country']][1])>0.01))
  { echo '<tr style="background-color:#EEAA33">';
//    echo '<td>'.$row['excls'].'=='.$countrytotals[$row['id_country']][0].'</td>';
//   echo '<td>'.$row['incls'].'=='.$countrytotals[$row['id_country']][1].'</td>';
  }
  else
    echo '<tr>';

  echo '<td>'.$row['country'].'</td>';
  echo '<td>'.number_format($countrytotals[$row['id_country']][0],2).'</td><td>'.number_format($countrytotals[$row['id_country']][1],2).'</td>'; 
  echo '<td>'.number_format($row['excls'],2).'</td><td>'.number_format($row['incls'],2).'</td>';
}
echo '</table>';

  include "footer1.php";
  echo '</body></html>';
  
  /**************************************************************/

function add_to_countrytotals($id_country, $excl, $incl)
{ global $countrytotals;
  if(!isset($countrytotals[$id_country]))
  { $countrytotals[$id_country] = [];
	$countrytotals[$id_country][0]=$countrytotals[$id_country][1] = 0;
  }
  $countrytotals[$id_country][0] += $excl;
  $countrytotals[$id_country][1] += $incl;
}

function normalize_tax($tax)
{ global $vatrounding;
  if($tax=="")
	$tax = "0.0";
  $taxrate = $tax;
  $diff = abs($taxrate - round($taxrate));
  if(($diff > 0.0001) && ($diff < ($vatrounding+0.01))) /* $diff will not exactly be 0.1 !!! */
  { $tax = round($taxrate).".0"; 
  }
  else
	$tax = number_format($taxrate,1)."";  
  return $tax;
}

function check_countryVAT(&$countryblock, $rate)
{ 
  if(!isset($countryblock[$rate]))
  { $countryblock[$rate] = [];
    $countryblock[$rate]['excls'] = 0;
    $countryblock[$rate]['incls'] = 0;
    $countryblock[$rate]['ordercount'] = 0;
    $countryblock[$rate]['orderlist'] = '';
  }
  
  if(!isset($countryblock[$rate]["proddiscount_excl"]))
  {
	$countryblock[$rate]["ship_excl"]=0;
	$countryblock[$rate]["ship_incl"]=0;
	$countryblock[$rate]["ship_products"]=''; 

	$countryblock[$rate]["wrap_excl"]=0;
	$countryblock[$rate]["wrap_incl"]=0;
	$countryblock[$rate]["wrap_products"]=''; 
	
	$countryblock[$rate]["proddiscount_excl"]=0;
	$countryblock[$rate]["proddiscount_incl"]=0;
	$countryblock[$rate]["shipdiscount_excl"]=0; 
	$countryblock[$rate]["shipdiscount_incl"]=0; 
  }	
}