<?php 
/* This script - part of Prestools - gives a list of all the order completed within a certain period */
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_shop'])) $input['id_shop']="0";
$id_shop = intval($input["id_shop"]);
if(!isset($input['startdate']) || (!check_mysql_date($input['startdate'])))
	$input['startdate']="";
if(!isset($input['enddate']) || (!check_mysql_date($input['enddate'])))
	$input['enddate']="";

echo '
<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order and tax list for EU tax</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>
</style>
<script>
</script>
</head><body>';
	
$eucountrynames = array("Belgium", "Bulgaria", "Croatia", "Cyprus (the Greek part)", "Denmark", "Germany", "Estonia", "Finland", "France", "Greece", "Hungary", "Ireland", "Italy", "Latvia", "Lithuania", "Luxembourg", "Malta", "The Netherlands", "Austria", "Poland", "Portugal", "Romania", "Slovenia", "Slovakia", "Spain", "Czech Republic", "Sweden");
/*							3			236			74			  76						20			1			86			7		8			9			17				143			26		10		125			131				12			139				13			2		14			15			36			193			37			6		16				18		*/
$eucountries = array("3", "236", "74", "76", "20", "1", "86", "7", "8", "9", "143", "26", "10", "125", "131", "	12", "139", "13", "2", "14", "15", "36", "193", "37", "6", "16", "18");
$maxshown=105;

	$query="select value from ". _DB_PREFIX_."configuration WHERE name='PS_LANG_DEFAULT'";
	$res=dbquery($query);
	$row = mysqli_fetch_array($res);
	$id_lang = $row['value'];

/*	
$query = "select id_country,name from ". _DB_PREFIX_."country_lang";
$query .= " WHERE id_country IN (".implode(",",$eucountries).")";
$res=dbquery($query);
$x=0;
while($row = mysqli_fetch_array($res))
  echo ++$x." ".$row["id_country"]." ".$row["name"]."<br>";
*/

$query="select c.value,l.name from ". _DB_PREFIX_."configuration c";
$query .= " LEFT JOIN "._DB_PREFIX_."country_lang l ON c.value=l.id_country AND l.id_lang='".$id_lang."'";
$query .= " WHERE c.name='PS_COUNTRY_DEFAULT'";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
$id_country_default = $row["value"];
$owncountry = $row["name"];
$euxcountries = array_diff($eucountries, array($id_country_default));

$query="SELECT SUM(total_paid_tax_excl) AS total_paid_excl, SUM(total_paid_tax_incl) AS total_paid_incl, GROUP_CONCAT(id_order) AS orders, c.firstname, c.lastname, invoice_date, (total_products_wt-total_products) AS product_tax";
$query .= ", b1.vat_number AS vatnum, cy.iso_code AS countrycode";
$query .= ", (LENGTH(b1.vat_number) != 0) AS isCompany FROM ". _DB_PREFIX_."orders o";
 $query .= " LEFT JOIN ". _DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."address b1 ON o.id_address_invoice = b1.id_address";
$query .= " LEFT JOIN ". _DB_PREFIX_."address b2 ON o.id_address_delivery = b2.id_address";
$query .= " LEFT JOIN ". _DB_PREFIX_."country cy ON b2.id_country = cy.id_country";
$query .= " WHERE o.valid=1 AND b2.id_country IN (".implode(",",$euxcountries).") AND b1.vat_number!=''";
$query .= " AND total_paid_tax_excl=total_paid_tax_incl";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY vatnum";
$query .= " ORDER BY vatnum";
$res=dbquery($query);

echo '<table class="triplemain" id="vatlist"><tr><td></td><td>VAT number</td><td>excl</td><td>incl</td><td>orders</td></tr>';
while($row = mysqli_fetch_array($res))
{ $orders = $row['orders'];
  if(strlen($orders) > 20)
	$orders = substr($orders,0,17)."...";
  echo '<tr><td>'.$row['countrycode'].'</td>';
  echo '<td><a target=_blank href="order-search.php?search_txt1='.$row['vatnum'].'&search_fld1=VAT+number';
  if($input['startdate'] != "")
    echo '&startdate='.$input['startdate'];
  if($input['enddate'] != "")
    echo '&enddate='.$input['enddate'];
  echo '">'.$row['vatnum'].'</a></td>';
  echo '<td>'.number_format($row['total_paid_excl'],2).'</td>';
  echo '<td>'.number_format($row['total_paid_incl'],2).'</td>';
  echo '<td>'.$orders.'</td></tr>';
}
echo '</table><p><p>';

$query="SELECT SUM(total_paid_tax_excl) AS total_paid_excl, SUM(total_paid_tax_incl) AS total_paid_incl, GROUP_CONCAT(id_order) AS orders, c.firstname, c.lastname, invoice_date, (total_products_wt-total_products) AS product_tax";
$query .= ", b1.vat_number AS vatnum, cy.iso_code AS countrycode";
$query .= ", (LENGTH(b1.vat_number) != 0) AS isCompany FROM ". _DB_PREFIX_."orders o";
 $query .= " LEFT JOIN ". _DB_PREFIX_."customer c ON o.id_customer = c.id_customer";
$query .= " LEFT JOIN ". _DB_PREFIX_."address b1 ON o.id_address_invoice = b1.id_address";
$query .= " LEFT JOIN ". _DB_PREFIX_."address b2 ON o.id_address_delivery = b2.id_address";
$query .= " LEFT JOIN ". _DB_PREFIX_."country cy ON b2.id_country = cy.id_country";
$query .= " WHERE o.valid=1 AND b2.id_country IN (".implode(",",$euxcountries).") AND b1.vat_number!=''";
$query .= " AND total_paid_tax_excl != total_paid_tax_incl";
if($id_shop !=0)
	$query .= " AND o.id_shop=".$id_shop;
if($input['startdate'] != "")
    $query .= " AND TO_DAYS(o.date_add) >= TO_DAYS('".mysqli_real_escape_string($conn, $input['startdate'])."')";
if($input['enddate'] != "")
    $query .= " AND TO_DAYS(o.date_add) <= TO_DAYS('".mysqli_real_escape_string($conn, $input['enddate'])."')";
$query .= " GROUP BY vatnum";
$query .= " ORDER BY vatnum";
$res=dbquery($query);

if(mysqli_num_rows($res) == 0) return;

echo 'Some of your customers entered invalid VAT numbers or the approval website didn\'t work at the time of their order. It concerns the following orders:';
echo '<table class="triplemain" id="vatlist"><tr><td></td><td>VAT number</td><td>excl</td><td>incl</td><td>orders</td></tr>';
while($row = mysqli_fetch_array($res))
{ $orders = $row['orders'];
  if(strlen($orders) > 20)
	$orders = substr($orders,0,17)."...";
  echo '<tr><td>'.$row['countrycode'].'</td>';
  echo '<td><a target=_blank href="order-search.php?search_txt1='.$row['vatnum'].'&search_fld1=VAT+number';
  if($input['startdate'] != "")
    echo '&startdate='.$input['startdate'];
  if($input['enddate'] != "")
    echo '&enddate='.$input['enddate'];
  echo '">'.$row['vatnum'].'</a></td>';
  echo '<td>'.number_format($row['total_paid_excl'],2).'</td>';
  echo '<td>'.number_format($row['total_paid_incl'],2).'</td>';
  echo '<td>'.$orders.'</td></tr>';
}
echo '</table><p><p>';

echo '</body></html>';

