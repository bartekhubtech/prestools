<?php 
/* This script - part of Prestools - lists the revenues for each category within a certain period */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Shipping costs for Prestashop</title>
<?php  // for security reasons the location of Prestools should be secret. So we dont give referer when you click on Prestools.com 
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS') !== false) 
  echo '<meta name="referrer" content="no-referrer">';
else
  echo '<meta name="referrer" content="none">';	
?>
<link rel="stylesheet" href="style1.css" type="text/css" />
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<?php echo '<script type="text/javascript">
	function salesdetails(product)
	{ window.open("product-sales.php?product="+product+"&startdate='.$input["startdate"].'&enddate='.$input["enddate"].'&id_shop='.$id_shop.'","", "resizable,scrollbars,location,menubar,status,toolbar");
      return false;
    }
</script>
<style>
  table#Maintable2 td,
  table#Maintable3 td {text-align: right;}
</style>
</head><body>';
print_menubar();
echo '<h1>Prestashop Shipping costs</h1>';

echo '<form name="search_form" method="get">
Period (yyyy-mm-dd): <input size=5 name=startdate value='.$input['startdate'].'> till <input size=5 name=enddate value='.$input['enddate'].'> &nbsp; ';

/* making shop block */
	$query= "select id_shop,name from ". _DB_PREFIX_."shop ORDER BY id_shop";
	$res=dbquery($query);
	echo " &nbsp; Shop: <select name=id_shop><option value=0>All shops</option>";
	while ($shop=mysqli_fetch_array($res)) 
	{   $selected = "";
	    if($shop["id_shop"] == $id_shop) $selected = " selected";
        echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['id_shop']."-".$shop['name'].'</option>';
	}	
    echo '</select><input type=submit></form>';

	$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];
	echo "<p>Only valid (=paid) orders are included.";
	
/*	$order_states = array(2,3,4,5);
	echo "<p>Orders with the following states have been included: ";
	$comma = "";
	foreach($order_states AS $order_state)
	{ $osquery="select name from ". _DB_PREFIX_."order_state_lang WHERE id_order_state='".$order_state."'";
	  $osres=dbquery($osquery);
	  $osrow = mysqli_fetch_array($osres);
	  echo $comma.$osrow['name'];
	  $comma = ", ";
	}
*/
$query="SELECT c.name AS carrier, c.id_carrier, total_shipping, SUM(total_shipping) AS total_shippings, SUM(total_shipping_tax_excl) AS total_shipping_tax_excls";
$query .= ", total_shipping_tax_excl, total_shipping_tax_incl, SUM(total_shipping_tax_incl) AS total_shipping_tax_incls, count(*) AS shipcount";
$query .= " FROM ". _DB_PREFIX_."orders o";
$query .= " LEFT JOIN ". _DB_PREFIX_."carrier c ON c.id_carrier=o.id_carrier";
$query .= " WHERE o.valid=1";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) > TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) < TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY carrier,total_shipping";
$query .= " ORDER BY carrier,total_shipping";
$res=dbquery($query);

$infofields = array("Carrier","Amount","Count","Amount excl","Amount incl","Total","Total excl","Total incl");
echo '<p><b>Shipping costs, accumulated by carrier and amount</b>';
echo '<br>Note that Paypal with Fee books its costs as shipping costs.';
echo '<div id="testdiv"><table id="Maintable2" border=1><colgroup id="mycolgroup2">';
for($i=0; $i<sizeof($infofields); $i++)
  echo "<col id='col".$i."'></col>";
echo '</colgroup><thead><tr>';
for($i=0; $i<sizeof($infofields); $i++)
{ $reverse = "false";
  if($i != 1) $reverse = "1";
  echo '<th><a href="" onclick="this.blur(); return sortTable(\'shipTblBdy\', '.$i.', '.$reverse.');">'.$infofields[$i].'</a></th
>';
}

$tot_shipcount = $tot_total_shippings = $tot_total_shipping_tax_excls = $tot_total_shipping_tax_incls = array();
echo "</tr></thead><tbody id='shipTblBdy'>";
  while($datarow = mysqli_fetch_array($res))
  { echo '<tr>';
	echo '<td>'.$datarow["carrier"].'</td>';
	echo '<td>'.number_format($datarow["total_shipping"],2, '.', '').'</td>';
	echo '<td>'.$datarow["shipcount"].'</td>';
	if(!isset($tot_shipcount[$datarow["carrier"]]))
	{  $tot_shipcount[$datarow["carrier"]] = $tot_total_shippings[$datarow["carrier"]] = $tot_total_shipping_tax_excls[$datarow["carrier"]] = $tot_total_shipping_tax_incls[$datarow["carrier"]] = 0;
	}
	$tot_shipcount[$datarow["carrier"]] += intval($datarow["shipcount"]);
	echo '<td><a href="order-search.php?carrier='.$datarow["carrier"].'&search_fld1=shipping+excl+VAT&search_txt1='.$datarow["total_shipping_tax_excl"].'" target=_blank>'.number_format($datarow["total_shipping_tax_excl"],2, '.', '').'</a></td>';
	echo '<td>'.number_format($datarow["total_shipping_tax_incl"],2, '.', '').'</td>';
	echo '<td>'.number_format($datarow["total_shippings"],2, '.', '').'</td>';
	$tot_total_shippings[$datarow["carrier"]] += floatval($datarow["total_shippings"]);
	echo '<td>'.number_format($datarow["total_shipping_tax_excls"],2, '.', '').'</td>';
	$tot_total_shipping_tax_excls[$datarow["carrier"]] += floatval($datarow["total_shipping_tax_excls"]);	
	echo '<td>'.number_format($datarow["total_shipping_tax_incls"],2, '.', '').'</td>';
	$tot_total_shipping_tax_incls[$datarow["carrier"]] += floatval($datarow["total_shipping_tax_incls"]);	
	echo '</tr>';
  }
  echo '</table>';
  echo '<p><b>Shipping costs, accumulated by carrier</b>';
  $cquery = "SELECT name FROM ". _DB_PREFIX_."carrier c GROUP BY name";
  $cres=dbquery($cquery);
  echo '<table border=1 style="border-collapse: collapse;" id="Maintable3">';
  echo '<tr><td>Carrier</td><td>Count</td><td>Total shipping</td><td>Total shipping tax excl</td><td>Total shipping tax incl</td></tr>';
  while($crow = mysqli_fetch_array($cres))
  { if(!isset($tot_shipcount[$crow["name"]])) continue;
	echo '<tr><td>'.$crow["name"].'</td>';
	echo '<td>'.$tot_shipcount[$crow["name"]].'</td>';
	echo '<td>'.number_format($tot_total_shippings[$crow["name"]],2, '.', '').'</td>';
	echo '<td>'.number_format($tot_total_shipping_tax_excls[$crow["name"]],2, '.', '').'</td>';
	echo '<td>'.number_format($tot_total_shipping_tax_incls[$crow["name"]],2, '.', '').'</td></tr>'; 
  }
  echo '</table>';
echo '<p>';
  include "footer1.php";
echo '</body></html>';
