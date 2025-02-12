<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;

/* get default language: we use this for the categories, manufacturers */
$id_lang = get_configuration_value('PS_LANG_DEFAULT');
?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Shop Rescue</title>
<style>
.comment {background-color:#aabbcc}
table.spacer td {
	padding:12px;
	border: 1px solid #c3c3c3;
	border-collapse: collapse;
}

table.alterna tr:nth-of-type(4n+3) {
  background: #f5f5f5;
}

</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
<script>
</script>
<link rel="stylesheet" href="style1.css" type="text/css" />
</head><body>
<?php print_menubar(); ?>
<div style="float:right; "><iframe name=tank width=230 height=93 sandbox="allow-same-origin"></iframe></div>
<h1>Database info</h1>
The functions on this page show you all database fields belonging to certain elements. These are functions for real nerds.
<p>
<?php

  echo '<table class="spacer" style="width:100%">';
  
  echo '<tr><td>';
  echo '<form name="analyzecarrierform" target=_blank action="carrier-dbinfo.php">';
  echo '<b>See the database fields for a carrier</b><br>';
  echo '<br>Carrier id <input size=2 name=id_carrier>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Analyze carrier"></form>';
  echo '</td></tr>';
    
  echo '<tr><td>';
  echo '<form name="analyzecartform" target=_blank action="cart-dbinfo.php">';
  echo '<b>See the database fields for a cart</b><br>';
  echo '<br>Cart id <input size=2 name=id_cart>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Analyze cart"></form>';
  echo '</td></tr>';   
  
  echo '<tr><td>';
  echo '<form name="analyzecartruleform" target=_blank action="cartrule-dbinfo.php">';
  echo '<b>See the database fields for a cart rule</b><br>';
  echo '<br>Cart rule id <input size=2 name=id_cart_rule>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Analyze Cart rule"></form>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<form name="analyzecustomerform" target=_blank action="customer-dbinfo.php">';
  echo '<b>See the database fields for a customer</b><br>';
  echo '<br>Customer id <input size=2 name=id_customer>';
  echo ' or Address id <input size=2 name=id_address>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Analyze Customer"></form>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<form name="analyzeorderform" target=_blank action="order-dbinfo.php">';
  echo '<b>See the database fields for an order</b><br>';
  echo '<br>Showing all database fields belonging to an order may help you find the cause of problems.';
  echo '<br>Order id <input size=2 name=id_order>';
  echo ' or Order invoice id <input size=2 name=id_order_invoice>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Analyze order"></form>';
  echo '</td></tr>';
  
  echo '<tr><td>';
  echo '<form name="analyzeproductform" target=_blank action="product-dbinfo.php">';
  echo '<b>See the database fields for a product</b><br>';
  echo '<br>Product id <input size=2 name=id_product>';
  echo '</td><td>';
  echo ' &nbsp; <input type=submit value="Analyze product"></form>';
  echo '</td></tr>';  
  
  echo '</table>';
  
  include "footer1.php";	  
  echo '</body></html>';


