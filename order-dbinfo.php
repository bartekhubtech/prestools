<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_order'])) $id_order=""; else $id_order = intval($input['id_order']);
if(!isset($input['id_order_invoice'])) $id_order_invoice=""; else $id_order_invoice = intval($input['id_order_invoice']);
if($id_order == 0) $id_order = "";
if($id_order_invoice == 0) $id_order_invoice = "";
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Order Database Information</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
table.orderinfo 
{ margin: 5px 0 5px 0; 
  border: 1px;
  border-spacing: 0; border-collapse: collapse;
  padding: 33px;
}
table.orderinfo td
{ padding: 4px;
  border: 2px solid #c3c3c3;	
}

.found {background-color:#909000; }
.notfound {color:#FF3333; }
.inserted, table.inserted { margin-left: 30px; !important }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>
<body>';
print_menubar();

/* optionals are tables that are not present in all installations */
$optionals = ["order_detail_pack"];
foreach($optionals AS $optional)
{ $res = dbquery('show tables like "'._DB_PREFIX_.$optional.'"');
  if(mysqli_num_rows($res) > 0)
	$optionals[$optional] = true;
  else
	$optionals[$optional] = false;  
}

if(($id_order =="") && ($id_order_invoice !=""))
{ $res = dbquery('SELECT id_order FROM '._DB_PREFIX_.'order_invoice WHERE id_order_invoice='.intval($id_order_invoice));
  if(mysqli_num_rows($res) > 0)
  { list($id_order) = mysqli_fetch_row($res);
  }
}

echo '<center><a href="order-dbinfo.php" style="text-decoration:none;"><b><font size="+2">Order Information</font></b></a></center>';
echo '<br><center>This rather technical page shows the complete presence of the order in the database.</center>';
echo '<form name="searchform" >';
echo '<table><tr><td>Order id: <input name=id_order value="'.$id_order.'">';
echo '<br>Order invoice id: <input name=id_order_invoice value="'.$id_order_invoice.'">';
echo '</td><td> &nbsp; &nbsp; <input type=submit></td></tr></table></form><p>';
if($id_order == "")
{ include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'orders WHERE id_order='.intval($id_order));
if(mysqli_num_rows($res)==0)
{ echo "<h2>Order not found!</h2>";
  include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}

echo 'The following table shows in which tables the order is present. You can click some of the links.
<table class="orderinfo"><tr><td class="found">'._DB_PREFIX_.'orders</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_carrier WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_carrier</td>';
else
  echo '<td class="found"><a href="#carrier">'._DB_PREFIX_.'order_carrier</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_cart_rule WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_cart_rule</td>';
else
  echo '<td class="found"><a href="#cart_rule">'._DB_PREFIX_.'order_cart_rule</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_detail WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_detail</td>';
else
  echo '<td class="found"><a href="#detail">'._DB_PREFIX_.'order_detail</a></td>';

if ($optionals["order_detail_pack"])
{ $res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_detail_pack odp 
LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail=odp.id_order_detail WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_detail_pack</td>';
else
  echo '<td class="found"><a href="#detail_pack">'._DB_PREFIX_.'order_detail_pack</a></td>';
}

echo '</tr><tr>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_detail_tax odt 
LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail=odt.id_order_detail WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_detail_tax</td>';
else
  echo '<td class="found"><a href="#detail_tax">'._DB_PREFIX_.'order_detail_tax</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_history WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_history</td>';
else
  echo '<td class="found"><a href="#history">'._DB_PREFIX_.'order_history</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_invoice WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_invoice</td>';
else
  echo '<td class="found"><a href="#invoice">'._DB_PREFIX_.'order_invoice</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_invoice_payment WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_invoice_payment</td>';
else
  echo '<td class="found"><a href="#invoice_payment">'._DB_PREFIX_.'order_invoice_payment</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_invoice_tax oit 
LEFT JOIN '._DB_PREFIX_.'order_invoice oi ON oi.id_order_invoice=oit.id_order_invoice WHERE oi.id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_invoice_tax</td>';
else
  echo '<td class="found"><a href="#invoice_tax">'._DB_PREFIX_.'order_invoice_tax</a></td>';

echo '</tr><tr>';
/*  These are standard messages that you can send to the customer for example for delay.
	$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_message WHERE id_order='.$id_order);
	if(mysqli_num_rows($res)==0)
	  echo '<td>'._DB_PREFIX_.'order_message</td>';
	else
	  echo '<td class="found">'._DB_PREFIX_.'order_message</td>';
	$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_message_lang oml 
	LEFT JOIN '._DB_PREFIX_.'order_message om ON om.id_order_message=oml.id_order_message WHERE om.id_order='.$id_order);
	if(mysqli_num_rows($res)==0)
	  echo '<td>'._DB_PREFIX_.'order_message_lang</td>';
	else
	  echo '<td class="found">'._DB_PREFIX_.'order_message_lang</td>';
*/
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_payment op 
LEFT JOIN '._DB_PREFIX_.'order_invoice_payment oip ON op.id_order_payment=oip.id_order_payment
 WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_payment</td>';
else
  echo '<td class="found"><a href="#payment">'._DB_PREFIX_.'order_payment</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_return WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_return</td>';
else
  echo '<td class="found"><a href="#return">'._DB_PREFIX_.'order_return</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_return_detail ord 
LEFT JOIN '._DB_PREFIX_.'order_return orr ON orr.id_order_return=ord.id_order_return WHERE orr.id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_return_detail</td>';
else
  echo '<td class="found"><a href="#return_detail">'._DB_PREFIX_.'order_return_detail</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_slip WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_slip</td>';
else
  echo '<td class="found"><a href="#slip">'._DB_PREFIX_.'order_slip</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_slip_detail osd 
LEFT JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail=osd.id_order_detail WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'order_slip_detail</td>';
else
  echo '<td class="found"><a href="#slip_detail">'._DB_PREFIX_.'order_slip_detail</a></td>';
/* ps_order_slip_detail_tax seems not to be used. It has been removed in Prestashop 8
  (possibly earlier). It used an id_order_slip_detail field that wasn't present in any 
  other table. So it didn't link in 
 */
 
echo '</tr><tr>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer c 
  LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_customer=c.id_customer WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
    echo '<td>'._DB_PREFIX_.'customer</td>';
else
    echo '<td class="found"><a href="#customer">'._DB_PREFIX_.'customer</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'address a 
  LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_address_delivery=a.id_address OR o.id_address_invoice=a.id_address WHERE id_order='.$id_order);
if(mysqli_num_rows($res)==0)
    echo '<td>'._DB_PREFIX_.'address</td>';
else
    echo '<td class="found"><a href="#address">'._DB_PREFIX_.'address</a></td>';
 
echo '</tr></table>';

echo "<br><b>"._DB_PREFIX_.'orders</b>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'orders WHERE id_order='.$id_order);
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { if($fld == 'id_address_delivery')
	{ $rs = dbquery('SELECT iso_code, c.id_country FROM '._DB_PREFIX_.'country c
	  LEFT JOIN '._DB_PREFIX_.'address a ON a.id_country=c.id_country
	  WHERE a.id_address='.$value);
	  $rw = mysqli_fetch_assoc($rs);
	  $iso_code = $rw['iso_code'];
	  $id_country = $rw['id_country']; /* save for later tax calculations */
	}
	echo '<td>'.$fld."<br>";
	if($fld == "id_order")
	{ echo '<a href=order-edit.php?id_order='.$value.' target=_blank>'.$value.'</a>';
	}
	else if($fld == "id_cart")
	{ $rs = dbquery('SELECT id_cart FROM '._DB_PREFIX_.'cart WHERE id_cart='.$value);
      if(mysqli_num_rows($rs) == 0)
		echo '<span class="notfound">'.$value.'</span>'; 
	  else
		echo '<a href=cart-dbinfo.php?id_cart='.$value.' target=_blank>'.$value.'</a>';
	}
	else if(($fld == "id_customer") && ($value != 0))
	{ $rs = dbquery('SELECT firstname, lastname FROM '._DB_PREFIX_.'customer WHERE id_customer='.$value);
      if(mysqli_num_rows($rs) == 0)
		echo '<span class="notfound">'.$value.'</span>'; 
	  else
	  { $rw = mysqli_fetch_assoc($rs);
		echo '<a href=customer-dbinfo.php?id_customer='.$value.' target=_blank>'.$value.' ('.$rw['firstname'].' '.$rw['lastname'].')</a>';
	  }
	}
	else
	  echo $value;
	echo '</td>';
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

echo "<span id=detail><b>"._DB_PREFIX_.'order_detail</b></span>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_detail WHERE id_order='.$id_order);
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
	if($fld == "id_order_detail")
	{ echo '<b>'.$value.'</b>';
	  $id_order_detail = $value;
	}
	else if($fld == "id_tax_rules_group")
	{ $query = "SELECT rate,name,tr.id_tax_rule,g.id_tax_rules_group";
	  $query .= " FROM "._DB_PREFIX_."tax_rule tr";
      $query .= " LEFT JOIN "._DB_PREFIX_."tax t ON (t.id_tax = tr.id_tax)";
      $query .= " LEFT JOIN "._DB_PREFIX_."tax_rules_group g ON (tr.id_tax_rules_group = g.id_tax_rules_group)";
      $query .= " WHERE tr.id_country = '".$id_country."' AND tr.id_state='0' AND NOT g.id_tax_rules_group=".$value;
	  $rs = dbquery($query);
	  $rw = mysqli_fetch_assoc($rs);
	  echo $rw['id_tax_rules_group'];
	  echo ' ('.$rw['name'].')<br><b>';
	  echo $iso_code.': </b>'.$rw['rate'];
	}
	else if($fld == "product_id")
	{ echo '<a href=product-dbinfo.php?id_product='.$value.' target=_blank>'.$value.'</a>';
	}
	else
	  echo $value;
	echo '</td>';
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
  
  $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'order_detail_tax WHERE id_order_detail='.$id_order_detail);
  if(mysqli_num_rows($ret) > 0)
	echo '<span class="inserted" id="detail_tax"><b>'._DB_PREFIX_.'order_detail_tax</span></b>';
  while ($rowt=mysqli_fetch_assoc($ret))
  { echo "<br><table class='triplemain inserted'><tr>";
	$ctr = 1;
	foreach($rowt AS $fld=>$value)
	{ echo '<td>'.$fld."<br>";
	  if(($fld == "id_tax") && ($value != 0))
	  { $rex = dbquery('SELECT * FROM '._DB_PREFIX_.'tax WHERE id_tax='.$value);
		$rowx=mysqli_fetch_assoc($rex);
		echo '<b>'.$value.' ('.$rowx['rate'].'%)</b>';
	  }
	  else
		echo $value;
	  if(!($ctr++ % 8))
		echo '</tr><tr>';
	}
	echo '</tr></table>';
  }
  
  if ($optionals["order_detail_pack"])
  {
    $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'order_detail_pack WHERE id_order_detail='.$id_order_detail);
    if(mysqli_num_rows($ret) > 0)
	  echo '<span class="inserted" id="detail_pack"><b>'._DB_PREFIX_.'order_detail_pack</b></span>';
    while ($rowt=mysqli_fetch_assoc($ret))
    { echo "<br><table class='triplemain inserted'><tr>";
  	  $ctr = 1;
	  foreach($rowt AS $fld=>$value)
	  { echo '<td>'.$fld."<br>".$value.'</td>';
	    if(!($ctr++ % 8))
	  	  echo '</tr><tr>';
	  }
	  echo '</tr></table>';
	}
  }
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_carrier WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=carrier><b>'._DB_PREFIX_.'order_carrier</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_cart_rule WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=cart_rule><b>'._DB_PREFIX_.'order_cart_rule</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_history WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=history><b>'._DB_PREFIX_.'order_history</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
	if($fld == "id_order_state")
	{ $rex = dbquery('SELECT * FROM '._DB_PREFIX_.'order_state_lang
	  WHERE id_order_state='.$value.' AND id_lang='.$id_lang);
		$rowx=mysqli_fetch_assoc($rex);
		echo '<b>'.$value.' ('.$rowx['name'].')</b>';
	}
	else
	  echo $value;
    echo '</td>';
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_invoice WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=invoice><b>'._DB_PREFIX_.'order_invoice</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
    if($fld == "id_order_invoice")
	  $id_order_invoice = $value;
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
  
  $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'order_invoice_payment WHERE id_order_invoice='.$id_order_invoice);
  if(mysqli_num_rows($ret) > 0)
	echo '<span class="inserted" id="invoice_payment"><b>'._DB_PREFIX_.'order_invoice_payment</b></span>';
  while ($rowt=mysqli_fetch_assoc($ret))
  { echo "<br><table class='triplemain inserted'><tr>";
	$ctr = 1;
	foreach($rowt AS $fld=>$value)
	{ echo '<td>'.$fld."<br>".$value.'</td>';
	  if(!($ctr++ % 8))
		echo '</tr><tr>';
	}
	echo '</tr></table>';
  }
  
  $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'order_invoice_tax WHERE id_order_invoice='.$id_order_invoice);
  if(mysqli_num_rows($ret) > 0)
	echo '<span class="inserted" id="invoice_tax"><b>'._DB_PREFIX_.'order_invoice_tax</b></span>';
  while ($rowt=mysqli_fetch_assoc($ret))
  { echo "<br><table class='triplemain inserted'><tr>";
	$ctr = 1;
	foreach($rowt AS $fld=>$value)
	{ echo '<td>'.$fld."<br>".$value.'</td>';
	  if(!($ctr++ % 8))
		echo '</tr><tr>';
	}
	echo '</tr></table>';
  }
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_return WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=return><b>'._DB_PREFIX_.'order_return</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
    if($fld == "id_order_return")
	  $id_order_return = $value;
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
  
  $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'order_return_detail WHERE id_order_return='.$id_order_return);
  if(mysqli_num_rows($ret) > 0)
	echo '<span class="inserted" id="return_detail"><b>'._DB_PREFIX_.'order_return_detail</b></span>';
  while ($rowt=mysqli_fetch_assoc($ret))
  { echo "<br><table class='triplemain inserted'><tr>";
	$ctr = 1;
	foreach($rowt AS $fld=>$value)
	{ echo '<td>'.$fld."<br>".$value.'</td>';
	  if(!($ctr++ % 8))
		echo '</tr><tr>';
	}
	echo '</tr></table>';
  }
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'order_slip WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=slip><b>'._DB_PREFIX_.'order_slip</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
    if($fld == "id_order_slip")
	  $id_order_slip = $value;
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
  
  $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'order_slip_detail WHERE id_order_slip='.$id_order_slip);
  if(mysqli_num_rows($ret) > 0)
	echo '<span class="inserted" id="slip_detail"><b>'._DB_PREFIX_.'order_slip_detail</b></span>';
  while ($rowt=mysqli_fetch_assoc($ret))
  { echo "<br><table class='triplemain inserted'><tr>";
	$ctr = 1;
	foreach($rowt AS $fld=>$value)
	{ echo '<td>'.$fld."<br>".$value.'</td>';
	  if(!($ctr++ % 8))
		echo '</tr><tr>';
	}
	echo '</tr></table>';
  }
}

$res = dbquery('SELECT c.* FROM '._DB_PREFIX_.'customer c 
  LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_customer=c.id_customer WHERE id_order='.$id_order);
if(mysqli_num_rows($res) > 0)
	echo '<span id=customer><b>'._DB_PREFIX_.'customer</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { $span=1;
    if($fld == "passwd") { $span = 3; $ctr++; $ctr++; }
    echo '<td colspan='.$span.'>'.$fld."<br>".$value.'</td>';
	if($ctr++ >= 8)
	{  echo '</tr><tr>';
	  $ctr=1;
	}
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT a.*,o.id_address_delivery FROM '._DB_PREFIX_.'address a 
  LEFT JOIN '._DB_PREFIX_.'orders o ON o.id_address_delivery=a.id_address OR o.id_address_invoice=a.id_address WHERE id_order='.$id_order);
if(mysqli_num_rows($res) == 1)
{ echo '<span id=address><b>'._DB_PREFIX_.'address</b></span>';
}
while ($row=mysqli_fetch_assoc($res))
{ if(mysqli_num_rows($res) > 1)
  { if($row['id_address'] == $row['id_address_delivery'])
	  echo '<b>'._DB_PREFIX_.'address_delivery</b>';
    else
	  echo '<b>'._DB_PREFIX_.'address_invoice</b>';
  }
  echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { if($fld == 'id_address_delivery') continue;
    echo '<td>'.$fld."<br>".$value;
	if($fld == 'id_country')
	{ $rs = dbquery('SELECT name FROM '._DB_PREFIX_.'country_lang 
	WHERE id_country='.$value.' AND id_lang='.$id_lang);
	  $rw=mysqli_fetch_assoc($rs);
	  echo ' ('.$rw['name'].')';
	}
	echo '</td>';
	if(!($ctr++ % 8))
	  echo '</tr><tr>';
  }
  echo '</tr></table>';
}
include "footer1.php";
echo '</body></html>';
  
  