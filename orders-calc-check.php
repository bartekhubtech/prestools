<?php 
/* This script - part of Prestools - checks the calculations */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['maxnum'])) $maxnum=25; else $maxnum = intval($input["maxnum"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";
$inclcount = $exclcount = 0;
$eucountrynames = array("Belgium", "Bulgaria", "Croatia", "Cyprus (the Greek part)", "Denmark", "Germany", "Estonia", "Finland", "France", "Greece", "Hungary", "Ireland", "Italy", "Latvia", "Lithuania", "Luxembourg", "Malta", "The Netherlands", "Austria", "Poland", "Portugal", "Romania", "Slovenia", "Slovakia", "Spain", "Czech Republic", "Sweden");
/*							3			236			74			  76					20		  1
	86			7		8			9		 143			26		  10		125		131
	12			139				13			2			14			15			36			193			37			6		16				18		*/
$eucountries = array("3", "236", "74", "76", "20", "1", "86", "7", "8", "9", "143", "26", "10", "125", "131", "12", "139", "13", "2", "14", "15", "36", "193", "37", "6", "16", "18");
$maxshown=100;
$fixedexchangerates = false;

$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_assoc($res);
$id_lang = $row['value'];

$query="select c.value,l.name from ". _DB_PREFIX_."configuration c";
$query .= " LEFT JOIN "._DB_PREFIX_."country_lang l ON c.value=l.id_country AND l.id_lang='".$id_lang."'";
$query .= " WHERE c.name='PS_COUNTRY_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_assoc($res);
$id_country_default = $row["value"];
$homecountry = $row["name"];

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


?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order Calculation checks</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
.datums { text-align: right; }
td { text-align: right; }
table#warningtable td { text-align: left; }
table#maintable tr td:nth-child(2) { text-align: left; background: #ccc; }
table#totaltable tr td:nth-child(1) { text-align: left; background: #ccc; }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">


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

</script>
</head><body>
<?php
print_menubar();
echo '<a href="orders-calc-check.php" style="text-decoration:none"><center><h3>Prestashop Orders Calculation Checks</h3></center></a>';

echo '<form name="searchform" method="get" onsubmit="return check_data()">
Period (yyyy-mm-dd): <input size=7 name=startdate value="'.$input['startdate'].'" class
="datums"> till <input size=7 name=enddate value="'.$input['enddate'].'" class="datums"> &nbsp;';

/* making shop block */
	$query= "select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	echo " &nbsp; Shop: <select name=id_shop><option value=0>All shops</option>";
	while ($shop=mysqli_fetch_assoc($res)) 
	{   $selected = "";
	    if($shop["id_shop"] == $id_shop) $selected = " selected";
        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}
    echo '</select> &nbsp; &nbsp; Max examples to show <input name=maxnum size=4 value='.$maxnum.'>';
	echo ' &nbsp; &nbsp; <input type="submit">';
	echo '<p>';

    echo "Orders involve lots of complex calculations, mainly related to VAT and discounts.
	Prestashop uses a variety of methods and has in the course of time changed some things.
	As a results errors do happen.<br>
	This can be seen as Integrity Checks for orders. But the main difference is that there
	are no repair options. When a VAT rate doesn't match its difference between the amounts
	with and without VAT it is impossible to say where the error is. The same applies when
	some addition doesn't match with the total field.<br>
	All orders are checked, including those that are incomplete or cancelled.<br>
	The orders are shown most recent first, so that you ";
	
	echo "<h2>Order checks for invoice date between ".$input["startdate"]." and ".$input["enddate"]." for ";
	if($id_shop == 0)
		echo "all shops";
	else 
		echo "shop nr. ".$id_shop;
	echo '</h2>';


if(!isset($_GET['id_shop']))
{ include "footer1.php";
  echo '</body></html>';
  exit();
}
	


$homeincl = $homeexcl  = $hometaxes = array(); /* home country */
$euincl = $euexcl  = $eutaxes = array(); /* other countries inside EU */
$outincl = $outexcl  = $outtaxes = array(); /* countries outside EU */
$totalincl = $totalexcl = $totaltaxes = array(); /* total values */
foreach($currencies AS $curr => $value)
{ $homeincl[$curr] = $homeexcl [$curr] = $hometaxes[$curr] = 0;
  $euincl[$curr] = $euexcl [$curr] = $eutaxes[$curr] = 0;
  $outincl[$curr] = $outexcl [$curr] = $outtaxes[$curr] = 0;
  $totalincl[$curr] = $totalexcl[$curr] = $totaltaxes[$curr] = 0;
}

  echo "<br><b>Orders without discounts where the difference total_products and total_products_wt does not match the sum of the sum of the total_amount fields in ps_order_detail_tax: </b>";
  $query= "select o.id_order, o.total_products_wt, o.total_products, SUM(odt.total_amount) AS odttotal, SUM(od.total_price_tax_incl) AS incls, SUM(od.total_price_tax_excl) AS excls";
  $query .= " FROM "._DB_PREFIX_."orders o";
  $query .= " LEFT JOIN "._DB_PREFIX_."order_detail od ON o.id_order=od.id_order";
  $query .= " LEFT JOIN "._DB_PREFIX_."order_detail_tax odt ON od.id_order_detail=odt.id_order_detail";
  $query .= " WHERE (o.total_discounts_tax_excl=0) AND (o.total_products_wt != o.total_products)";
  $query .= " GROUP BY o.id_order";
  $query .= " HAVING (odttotal - (total_products_wt-total_products)) >0.03";
  $query .= " ORDER BY o.id_order DESC";
  $res=dbquery($query);
  $num = 0;
  echo "<br>".mysqli_num_rows($res)." orders found with a differnce > 0.03:<br>";
  while(($row = mysqli_fetch_assoc($res)) && ($num++ < $maxnum))
  { $diff1 = abs($row['odttotal']-($row['total_products_wt']-$row['total_products']));
    $diff2 = abs($row['odttotal']-($row['incls']-$row['excls']));
	$diff3 = abs(($row['total_products_wt']-$row['total_products'])-($row['incls']-$row['excls']));
	
    echo "<b><a href='order-edit.php?id_order=".$row['id_order']."' target=_blank>".$row['id_order']."</a> (<a href='order-dbinfo.php?id_order=".$row['id_order']."' target=_blank>".max($diff1,$diff2,$diff3)."</a>):</b> ";
    echo "SUM(odt.total_amount): ".$row['odttotal']."; ";
    echo "DIFF(o.total_products(_wt)): (".$row['total_products_wt']."-".$row['total_products'].")=".($row['total_products_wt']-$row['total_products'])."; ";
    echo "DIFF(SUM(od.total_price_tax(excl/incl))): (".$row['incls']."-".$row['excls'].")=".($row['incls']-$row['excls']).";<br>";
  }
  
  
  echo "<br><b>Orders where orderlines have different rates in ps_order_detail and ps_order_detail_tax. The version that fits with ps_orders is printed fat: </b>";
  $query= "select DISTINCT(od.id_order),o.total_discounts_tax_excl, tax_rate, rate ";
  $query .= " ,total_products, total_products_wt, SUM(odt.total_amount) AS odttotal";
  $query .= " ,SUM(od.total_price_tax_incl) AS incls, SUM(od.total_price_tax_excl) AS excls";
  $query .= " ,SUM(od.total_price_tax_excl*(100+od.tax_rate)/100) AS calcincls";
  $query .= " ,SUM(od.total_price_tax_excl*(100+t.rate)/100) AS odtcalcs";
  $query .= " FROM "._DB_PREFIX_."order_detail od";
  $query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
  $query .= " LEFT JOIN "._DB_PREFIX_."order_detail_tax odt ON od.id_order_detail=odt.id_order_detail";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON odt.id_tax=t.id_tax";
  $query .= " WHERE od.tax_rate != t.rate";
  $query .= " GROUP BY o.id_order";
  $query .= " ORDER BY o.id_order DESC";
  $res=dbquery($query);
  $num = 0;
  echo "<br>".mysqli_num_rows($res)." orders:<br>";
  echo '<table class="triplemain">';
  echo '<tr><td>id_order</td><td>discount</td><td>od.tax_rate</td><td>odt->tax</td></tr>';
  while(($row = mysqli_fetch_assoc($res)) && ($num++ < $maxnum))
  { echo "<tr><td><b><a href='order-edit.php?id_order=".$row['id_order']."' target=_blank>".$row['id_order']."</a>: </b></td> ";
    if($row['total_discounts_tax_excl'] > 0)
      echo '<td>Y</td>';
    else
      echo '<td></td>';
    $totVAT = $row['total_products_wt'] - $row['total_products'];
	$trdiff = $row['calcincls'] - $row['excls'];
	$tdiff = $row['odtcalcs'] - $row['excls'];
	if(abs($trdiff - $totVAT) < 0.02)
	  echo '<td><b>'.$row['tax_rate'].'</b></td>';
    else
	  echo '<td>'.$row['tax_rate'].'</td>';
	if(abs($tdiff - $totVAT) < 0.02)
	  echo '<td><b>'.$row['rate'].'</b></td>';
    else
	  echo '<td>'.$row['rate'].'</td>';
    echo "</tr>";
  }
  echo '</table>';
  
  
  echo "<p><b>Orders the combination odt.where orderlines have different rates in ps_order_detail and ps_order_detail_tax.id_tax and ps_order_detail.od.total_price_tax(excl/incl) don't match: </b>";
  $query= "select t.rate, od.id_order ";
  $query .= " ,total_products, total_products_wt, SUM(odt.total_amount) AS odttotal";
  $query .= " ,SUM(od.total_price_tax_incl) AS incls, SUM(od.total_price_tax_excl) AS excls";
  $query .= " ,SUM(od.total_price_tax_excl*(100+t.rate)/100) AS odtcalcs";
  $query .= " ,ABS(SUM(od.total_price_tax_excl*(100+t.rate)/100) - SUM(od.total_price_tax_incl)) AS calcs";
  $query .= " ,total_discounts_tax_excl";
  $query .= " FROM "._DB_PREFIX_."order_detail od";
  $query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
  $query .= " LEFT JOIN "._DB_PREFIX_."order_detail_tax odt ON od.id_order_detail=odt.id_order_detail";
  $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON odt.id_tax=t.id_tax";
  $query .= " GROUP BY o.id_order, odt.id_tax";
  $query .= " HAVING calcs >0.02";
  $query .= " ORDER BY o.id_order DESC, rate ASC";
  $res=dbquery($query);
  $num = 0;
  echo "<br>".mysqli_num_rows($res)." orders:<br>";
  echo '<table class="triplemain">';
  echo '<tr><td>id_order</td><td>discount</td><td>rate</td><td>excls</td><td>incls</td><td>calc</td></tr>';
  while(($row = mysqli_fetch_assoc($res)) && ($num++ < $maxnum))
  { echo "<tr><td><b><a href='order-edit.php?id_order=".$row['id_order']."' target=_blank>".$row['id_order']."</a>: </b></td> ";
    if($row['total_discounts_tax_excl'] > 0)
      echo '<td>Y</td>';
    else
      echo '<td></td>';
    echo '<td><a href="order-dbinfo.php?id_order='.$row['id_order'].'" target=_blank>'.$row['rate'].'</a></td>';
    echo '<td>'.$row['excls'].'</td>';
    echo '<td>'.$row['incls'].'</td>';	
    echo '<td>'.$row['odtcalcs'].'</td>';
    echo '<td>'.$row['calcs'].'</td>';
    echo "</tr>";
  }
  echo '</table>';
  
  

  echo "<p><b>Orders where the sum of the ps_order_detail.od.total_price_tax(excl/incl)
  doesn't match ps_orders.total_products(_wt):</b>";
  $query= "select o.id_order ";
  $query .= " ,total_products, total_products_wt";
  $query .= " ,SUM(od.total_price_tax_incl) AS incls, SUM(od.total_price_tax_excl) AS excls";
  $query .= " ,ABS(SUM(od.total_price_tax_excl) - total_products) AS exclat";
  $query .= " ,ABS(SUM(od.total_price_tax_incl) - total_products_wt) AS inclat";
  $query .= " ,total_discounts_tax_excl";
  $query .= " FROM "._DB_PREFIX_."order_detail od";
  $query .= " LEFT JOIN "._DB_PREFIX_."orders o ON o.id_order=od.id_order";
  $query .= " GROUP BY o.id_order";
  $query .= " HAVING (exclat>0.02) OR (inclat>0.02)";
  $query .= " ORDER BY o.id_order DESC";
  $res=dbquery($query);
  $num = 0;
  echo "<br>".mysqli_num_rows($res)." orders:<br>";
  echo '<table class="triplemain">';
  echo '<tr><td>id_order</td><td>discount</td><td></td><td>totprods</td><td>totprods_wt</td><td>excls</td><td>incls</td><td>exclat</td><td>inclat</td></tr>';
  while(($row = mysqli_fetch_assoc($res)) && ($num++ < $maxnum))
  { echo "<tr><td><b><a href='order-edit.php?id_order=".$row['id_order']."' target=_blank>".$row['id_order']."</a>: </b></td> ";
    if($row['total_discounts_tax_excl'] > 0)
      echo '<td>Y</td>';
    else
      echo '<td></td>';
    echo '<td><a href="order-dbinfo.php?id_order='.$row['id_order'].'" target=_blank>dbinfo</a></td>';
    echo '<td>'.$row['total_products'].'</td>';
    echo '<td>'.$row['total_products_wt'].'</td>';	
    echo '<td>'.$row['excls'].'</td>';
    echo '<td>'.$row['incls'].'</td>';	
    echo '<td>'.$row['exclat'].'</td>';
    echo '<td>'.$row['inclat'].'</td>';
    echo "</tr>";
  }
  echo '</table>';
  
  echo "<p><b>Orders where the values within ps_orders don't match:</b>";
 $query= "select((o.total_paid_tax_excl+o.total_discounts_tax_excl-o.total_products- o.total_shipping_tax_excl-o.total_wrapping_tax_excl)/o.conversion_rate) AS difftot, 
  ((o.total_paid_tax_incl+o.total_discounts_tax_incl-o.total_products_wt- o.total_shipping_tax_incl-o.total_wrapping_tax_incl)/o.conversion_rate) AS difftotincl, 
a.id_country, o.id_order, total_discounts_tax_excl,";
  $query .= "total_paid_tax_excl,total_discounts_tax_excl,total_products, total_shipping_tax_excl,total_wrapping_tax_excl,";
  $query .= "total_paid_tax_incl,total_discounts_tax_incl,total_products_wt, total_shipping_tax_incl,total_wrapping_tax_incl";
  $query .= " FROM "._DB_PREFIX_."orders o";
  $query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
  $query .= " WHERE o.valid=1";
  if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
  if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
  if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
  $query .= " HAVING (abs(difftot)>0.03) OR (abs(difftotincl)>0.03)";
//  $query .= " OR id_order=14452";
  $query .= " ORDER BY id_order DESC";
  $res=dbquery($query);
  $num = 0;
  echo "<br>".mysqli_num_rows($res)." orders:<br>";
  echo '<table class="triplemain">';
  echo '<tr><td>id_order</td><td>discount</td><td></td><td>difftot</td><td>diffincl</td>';
  echo '<td>total_paid</td><td>total_discounts</td><td>total_products</td>';
  echo '<td> total_shipping</td><td>total_wrapping</td></tr>';
  while(($row = mysqli_fetch_assoc($res)) && ($num++ < $maxnum))
  { echo "<tr><td><b><a href='order-edit.php?id_order=".$row['id_order']."' target=_blank>".$row['id_order']."</a>: </b></td> ";
    if($row['total_discounts_tax_excl'] > 0)
      echo '<td>Y</td>';
    else
      echo '<td></td>';
    echo '<td><a href="order-dbinfo.php?id_order='.$row['id_order'].'" target=_blank>dbinfo</a></td>';
    echo '<td>';
	echo round($row['difftot'],2).' (';
    echo round($row['total_paid_tax_excl'],2);
	echo '-';
	echo (round($row['total_discounts_tax_excl'],2)+
	round($row['total_products'],2)+round($row['total_shipping_tax_excl'],2)+round($row['total_wrapping_tax_excl'],2));
	echo ')</td>';
    echo '<td>'.round($row['difftotincl'],2).' (';
    echo round($row['total_paid_tax_incl'],2);
	echo '-';
	echo (round($row['total_discounts_tax_incl'],2)+
	round($row['total_products_wt'],2)+round($row['total_shipping_tax_incl'],2)+round($row['total_wrapping_tax_incl'],2));
	echo ')</td>';

    echo '<td>'.round($row['total_paid_tax_excl'],2);
    echo '/'.round($row['total_paid_tax_incl'],2).'</td>';
	echo '<td>'.round($row['total_discounts_tax_excl'],2);
	echo '/'.round($row['total_discounts_tax_incl'],2).'</td>';
	echo '<td>'.round($row['total_products'],2);
	echo '/'.round($row['total_products_wt'],2).'</td>';
	echo '<td>'.round($row['total_shipping_tax_excl'],2);
	echo '/'.round($row['total_shipping_tax_incl'],2).'</td>';
	echo '<td>'.round($row['total_wrapping_tax_excl'],2);
	echo '/'.round($row['total_wrapping_tax_incl'],2).'</td>';
    echo "</tr>";
  }
  echo '</table>';
  

  include "footer1.php";
  echo '</body></html>';
