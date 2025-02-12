<?php 
/* This script - part of Prestools - gives a list of all the order completed within a certain period */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['maxnum'])) $maxnum=100; else $maxnum = intval($input["maxnum"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";
$eucountrynames = array("Belgium", "Bulgaria", "Croatia", "Cyprus (the Greek part)", "Denmark", "Germany", "Estonia", "Finland", "France", "Greece", "Hungary", "Ireland", "Italy", "Latvia", "Lithuania", "Luxembourg", "Malta", "The Netherlands", "Austria", "Poland", "Portugal", "Romania", "Slovenia", "Slovakia", "Spain", "Czech Republic", "Sweden");
/*							3			236			74			  76					20	  1			86			7		8			9			143			26		10		125			131				12			139				13			2		14			15			36			193			37			6		16			18		*/
$eucountries = array("3", "236", "74", "76", "", "20", "1", "86", "7", "8", "9", "143", "26", "10", "125", "131", "	12", "139", "13", "2", "14", "15", "36", "193", "37", "6", "16", "18");

	$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];

$query="select c.value,l.name from ". _DB_PREFIX_."configuration c";
$query .= " LEFT JOIN "._DB_PREFIX_."country_lang l ON c.value=l.id_country AND l.id_lang='".$id_lang."'";
$query .= " WHERE c.name='PS_COUNTRY_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_country_default = $row["value"];
$owncountry = $row["name"];
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order and tax list for EU tax</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
.datums { text-align: right; }
td { text-align: right; }
table#maintable tr td:nth-child(3) { text-align: left; background: #ccc; }
table#maintable tr td:nth-child(8) { text-align: left; background: #ccc; }


</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script type="text/javascript">
var rowsremoved = 0;
function RemoveRow(row)
{ var tblEl = document.getElementById("offTblBdy");
  var trow = document.getElementById("trid"+row).parentNode;
  trow.innerHTML = "<td></td>";
  rowsremoved++;
}

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

function viewTaxes()
{ var page = document.getElementById("floater");
  var tmp = '<iframe width=360px; height='+(window.innerHeight-100)+'  src="orders-eu-taxlist.php';
  tmp += '?startdate='+searchform.startdate.value+'&enddate='+searchform.enddate.value;
  tmp += '&id_shop='+searchform.id_shop.value+'"></iframe';
  page.innerHTML = tmp;
}

</script>
</head><body>
<?php
print_menubar();
echo '<div id="floater" style="float:right; margin-right:30px">space for VAT numbers</div>';
echo '<a href="orders-eu-tax.php" style="text-decoration:none"><center><h3>Prestashop Orders in a period for EU Tax</h3></center></a>';

echo '<form name="searchform" method="get" onsubmit="return check_data()">
<table><tr><td>Period (yyyy-mm-dd): <input size=7 name=startdate value="'.$input['startdate'].'" class
="datums"> till <input size=7 name=enddate value="'.$input['enddate'].'" class="datums"> &nbsp;';

/* making shop block */
	$query= "select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	echo " &nbsp; Shop: <select name=id_shop><option value=0>All shops</option>";
	while ($shop=mysqli_fetch_array($res)) 
	{   $selected = "";
	    if($shop["id_shop"] == $id_shop) $selected = " selected";
        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}
    echo '</select> &nbsp; &nbsp; Max shown <input name=maxnum size=4 value='.$maxnum.'>';
	echo '</td><td rowspan=2> &nbsp; &nbsp; <input type="submit">';
	echo '</td></tr><tr><td>';
	echo '</td></tr></table><p>';

	echo "For EU countries orders with and without VAT number are mentioned seperately as those with VAT number don't have VAT.<br/>";
	echo "You can see the VAT numbers of those who didn't pay VAT due to entering their VAT number by clicking in view in the Totals table at the bottom. They will appear at the right top.<br>";

	
	echo "<br/>Orders with an invoice date within the follow period have been included: startdate=".$input["startdate"]." - enddate=".$input["enddate"]." for ";
	if($id_shop == 0)
		echo "all shops";
	else 
		echo "shop nr. ".$id_shop;

	echo "<p> - This list shows 'valid' orders: that is orders that have been paid. So take care when your customers pay afterwards.
	<br> - You are advised to run the script for a period when no more changes for its orders are expected. Changes happening later (incoming payments, cancellations and modifications) will otherwise be missed.
	<br> - Restitutions are never included. 
	<br> - By moving your mouse over an order number you can see for which figures it was included.
	<br/> - Orders without VAT still can pay VAT on shipping.";
	
echo "<p>If you have both products with high and with low tariff you can find an experimental version <a href='orders-eu-tax2.php'>here</a>. <br>Note that it may be slower and that intra-EU without VAT is on this page. An alternative is the VAT splitter on the <a href='utilities.php' target=_blank>utilities page</a>.";

if(!isset($_GET['id_shop']))
{ include "footer1.php";
  echo '</body></html>';
  exit();
}
	
$default_currency = get_configuration_value('PS_CURRENCY_DEFAULT');
$query="SELECT conversion_rate FROM ". _DB_PREFIX_."currency WHERE id_currency=".$default_currency;
$res=dbquery($query);
$row = mysqli_fetch_array($res);
if($row["conversion_rate"] != 1) colordie("Currency problem; this page cannot work for you.");

$currencies  = array();
$query="SELECT c.* FROM "._DB_PREFIX_."orders o";
$query .= " LEFT JOIN "._DB_PREFIX_."currency c ON c.id_currency=o.id_currency";
$query .= " GROUP BY o.id_currency";
$res=dbquery($query);
while($row = mysqli_fetch_array($res))
{ $currencies[$row["id_currency"]] = $row["iso_code"];
}
	
	$query="SELECT a.id_country, cl.name AS countryname, total_paid_tax_excl, total_paid_tax_incl, id_order, c.firstname, c.lastname, invoice_date, (total_products_wt-total_products) AS product_tax";
	$query .= ", b.vat_number, ROUND(((total_paid_tax_incl-total_paid_tax_excl)/total_paid_tax_excl)*100,1) AS taxrate";
	$query .= ", (LENGTH(b.vat_number) != 0) AS isCompany, o.id_currency FROM ". _DB_PREFIX_."orders o";
	 $query .= " LEFT JOIN "._DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
	$query .= " LEFT JOIN ". _DB_PREFIX_."address a ON o.id_address_delivery = a.id_address";
	$query .= " LEFT JOIN ". _DB_PREFIX_."country_lang cl ON cl.id_country = a.id_country AND cl.id_lang='".$id_lang."'";
	$query .= " LEFT JOIN ". _DB_PREFIX_."address b ON o.id_address_invoice = b.id_address";
	$query .= " WHERE o.valid=1";
	if($id_shop !=0)
		$query .= " AND o.id_shop=".$id_shop;
	if($input['startdate'] != "")
		$query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
	if($input['enddate'] != "")
		$query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
	$query .= " ORDER BY countryname, taxrate > 0, id_order";
	$res=dbquery($query);

	$infofields = array("","id","Country","Sales/incl","Sales/excl","Tax","Pct", "Orders");
	echo '<div id="testdiv"><table id="maintable" border=1><colgroup id="mycolgroup">';
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
	$x=0;
	$incl = $excl = $taxes = $euincl = $euexcl  = $eutaxes = $euxincl = $euxexcl  = $euxtaxes = $exincl = $exexcl  = $extaxes = $ownincl = $ownexcl  = $owntaxes = 0;
	$oldcountry = 0;
	$oldtaxrate = -1;
	$refunders = array();
	$total_incl = $total_excl = 0;
	$myorders = "";
	$ordercnt = 0;
	while($datarow = mysqli_fetch_array($res))
	  { if($datarow['taxrate'] > 0) /* if this switch is on we see all different taxrates separately; if it is off there are only two lines: all orders with taxes and all orders without */
		   $datarow["taxrate"] = 1;
		else
		   $datarow["taxrate"] = 0;   
		if(($datarow['id_country'] != $oldcountry) || ($datarow['taxrate'] != $oldtaxrate))
		{ if($oldcountry != 0) 
		  { if($ordercnt > $maxnum)
			  $myorders .= "<br>".$maxnum." of ".($ordercnt)." shown";
			print_line($oldrow, $total_incl, $total_excl, $myorders);
		  }
		  $total_incl = $total_excl = 0;
		  $myorders = "";
		  $oldcountry = $datarow['id_country'];
		  $oldtaxrate = $datarow['taxrate'];
		  $ordercnt = 0;
		}
		
		if($datarow['id_currency'] != $default_currency)
		{ $xquery = "SELECT op.* FROM "._DB_PREFIX_."order_invoice_payment oip";
		  $xquery .= " LEFT JOIN "._DB_PREFIX_."order_payment op ON op.id_order_payment=oip.id_order_payment";
		  $xquery .= " WHERE oip.id_order=".$datarow["id_order"];
		  $xres=dbquery($xquery);
		  $totx = $totd = 0;
		  if(mysqli_num_rows($xres) > 0)
		  { while($xrow = mysqli_fetch_array($xres))
			{ $totx += $xrow["amount"];
			  $totd += floatval($xrow["amount"])/floatval($xrow["conversion_rate"]);
			}
			$rate = $totx/$totd;
			$xtotpaidincl = $datarow["total_paid_tax_incl"];
			$xtotpaidexcl = $datarow["total_paid_tax_excl"]; 
		  
			$datarow["total_paid_tax_incl"] = floatval($datarow["total_paid_tax_incl"])/$rate;
			$datarow["total_paid_tax_excl"] = floatval($datarow["total_paid_tax_excl"])/$rate;	  
		  }
		}
		$incl += $datarow["total_paid_tax_incl"];
		$excl += $datarow["total_paid_tax_excl"];
		$tax = $datarow["total_paid_tax_incl"] - $datarow["total_paid_tax_excl"];
		$taxes += $tax;
		$total_incl += $datarow["total_paid_tax_incl"];
		$total_excl += $datarow["total_paid_tax_excl"];	
		if(in_array($datarow["id_country"], $eucountries) && ($datarow["id_country"] != $id_country_default))
		{ if($datarow["product_tax"] == 0)
		  {	$euxincl += $datarow["total_paid_tax_incl"];
			$euxexcl += $datarow["total_paid_tax_excl"];
			$euxtaxes += $tax;
		  }
		  else
		  {	$euincl += $datarow["total_paid_tax_incl"];
			$euexcl += $datarow["total_paid_tax_excl"];
			$eutaxes += $tax;
		  }
		}
		else if($datarow["id_country"] == $id_country_default)
		{   $ownincl += $datarow["total_paid_tax_incl"];
			$ownexcl += $datarow["total_paid_tax_excl"];
			$owntaxes += $tax;
			$owncountry = $datarow["countryname"];
		}
		else
		{ 	$exincl += $datarow["total_paid_tax_incl"];
			$exexcl += $datarow["total_paid_tax_excl"];
			$extaxes += $tax;
		}
		$oldrow = $datarow;
		if($ordercnt++ >= $maxnum)
		  continue;
		if(strlen($myorders) > 0) $myorders .= ",";
		if(!($ordercnt % 3)) $myorders .= " ";
		$myorders .= '<a title="'.$datarow['firstname'].' '.$datarow['lastname'].' - '.$datarow['countryname'].' - '.$datarow['vat_number'].' : '.number_format($datarow['total_paid_tax_incl'],2).' / '.number_format($datarow['total_paid_tax_excl'],2);
		if(($datarow['id_currency'] != $default_currency) && (mysqli_num_rows($xres) > 0))
		  $myorders .= "(".$currencies[$datarow["id_currency"]]." ".$xtotpaidincl."/".$xtotpaidexcl.")";
		$myorders .= ' - '.substr($datarow['invoice_date'],0,10).' '.'" href="order-edit.php?id_order='.$datarow['id_order'].'" target=_blank>'.$datarow["id_order"].'</a>';
		
		/* look for refunds and returns */
		$odquery="SELECT id_order";
		$odquery .= " FROM ". _DB_PREFIX_."order_detail";
		$odquery .= " WHERE id_order=".$datarow["id_order"]." AND ((product_quantity_refunded != 0) OR (product_quantity_return != 0))";
		$odres=dbquery($odquery);
		if(mysqli_num_rows($odres) > 0)
			$refunders[] = $datarow["id_order"];
	  }
	  if(isset($oldrow))
	  { if($ordercnt > $maxnum)
		  $myorders .= "<br>".$maxnum." of ".($ordercnt)." shown";
		print_line($oldrow, $total_incl, $total_excl, $myorders);
		if(sizeof($refunders) > 0)
		{ echo "<br>The following orders contain refunds or returns that you will need to process separately: ".implode(",", array_unique($refunders));	  
		}
	  }
	  else
		echo "<p><b>No sales in this period!</b><p>";
	  echo "</tbody></table></div>";

  echo "<table name='totaltable' border=1;><tr><th colspan=4>Totals</th></tr>";
  echo "<tr><th></th><th>Sales/incl</th><th>Sales/excl</th><th>Tax</th></tr>";
  echo "<tr><td>".$owncountry."</td><td>".number_format($ownincl,2)."</td><td>".number_format($ownexcl,2)."</td><td>".number_format($owntaxes,2)."</td></tr>";
  echo "<tr><td>Within EU with VAT</td><td>".number_format($euincl,2)."</td><td>".number_format($euexcl,2)."</td><td>".number_format($eutaxes,2)."</td></tr>";
  echo "<tr><td>Within EU excl VAT</td><td>".number_format($euxincl,2)."</td><td>".number_format($euxexcl,2)."</td><td>".number_format($euxtaxes,2)."</td><td><a href='#' onclick='viewTaxes(); return false;'>view</a></td></tr>";
  echo "<tr><td>Outside EU</td><td>".number_format($exincl,2)."</td><td>".number_format($exexcl,2)."</td><td>".number_format($extaxes,2)."</td></tr>";
  echo "<tr><td>Total</td><td>".number_format($incl,2)."</td><td>".number_format($excl,2)."</td><td>".number_format($taxes,2)."</td></tr>";
  echo "</table>";
  
  function print_line($datarow, $total_incl, $total_excl, $myorders)
  { global $eucountries, $id_country_default, $x;
    $bgcolor = "";
    if(!in_array($datarow["id_country"], $eucountries))
      $bgcolor = 'style="background-color: yellow"';
	if($datarow["id_country"] == $id_country_default)
       $bgcolor = 'style="background-color: #EFCCEF"';	
    echo '<tr '.$bgcolor.'>';
	echo '<td id="trid'.$x.'"><input type="button" value="X" style="width:4px" onclick="RemoveRow('.$x.')" title="Hide line from display" /></td>';
	echo '<td>'.$datarow["id_country"].'</td>';
	echo '<td>'.$datarow["countryname"].'</td>';
	echo '<td>'.number_format($total_incl,2).'</td>';
    echo '<td>'.number_format($total_excl,2).'</td>';
	$tax = $total_incl- $total_excl;
    echo '<td>'.number_format($tax,2).'</td>';
	if($total_excl != 0)
	  echo '<td>'.number_format(($tax*100)/$total_excl,2).'</td>';
    else 
	  echo '<td>0</td>';
//	if($datarow["id_country"] == $id_country_default)
//	  echo "<td></td>";
//	else	
	  echo '<td>'.$myorders.'</td>';
	echo '<tr
	  >';
	$x++;
  }
  echo '</table>';
  
include "footer1.php";
echo '</body></html>';
 
?>
