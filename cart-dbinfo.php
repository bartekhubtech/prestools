<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_cart'])) $id_cart=""; else $id_cart = intval($input['id_cart']);
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Cart Database Information</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
table.cartinfo 
{ margin: 5px 0 5px 0; 
  border: 1px;
  border-spacing: 0; border-collapse: collapse;
  padding: 33px;
}
table.cartinfo td
{ padding: 4px;
  border: 2px solid #c3c3c3;	
}

.found {background-color:#909000; }
.notfound {color:#FF3333; }
.inserted, table.inserted { margin-left: 30px; !important }
.insertedx, table.insertedx { margin-left: 60px; !important }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>
<body>';
print_menubar();

echo '<center><a href="cart-dbinfo.php" style="text-decoration:none;"><b><font size="+2">Cart Information</font></b></a></center>';
echo '<br><center>This rather technical page shows the presence of a cart in the database.</center>';
echo '<form name="searchform" >';
echo 'cart id: <input name=id_cart value="'.$id_cart.'">';
echo ' &nbsp; &nbsp; <input type=submit></form><p>';
if($id_cart == "")
{ include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart WHERE id_cart='.intval($id_cart));
if(mysqli_num_rows($res)==0)
{ echo "<h2>Cart not found!</h2>";
  include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}


echo 'The following table shows in which tables the cart is present. You can click some of the links.
<table class="cartinfo"><tr><td class="found">'._DB_PREFIX_.'cart</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart='.$id_cart);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_cart_rule</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_cart_rule/td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_product WHERE id_cart='.$id_cart);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_product</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_product</td>';

echo '</tr></table>';


echo "<br><b>"._DB_PREFIX_.'cart</b>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart WHERE id_cart='.$id_cart);
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
	if(($fld == "id_customer") && ($value != 0))
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
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_cart_rule WHERE id_cart='.$id_cart);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_cart_rule</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
    if(($fld == "id_cart_rule") && ($value != 0))
	{ $rs = dbquery('SELECT id_cart_rule FROM '._DB_PREFIX_.'cart_rule WHERE id_cart_rule='.$value);
      if(mysqli_num_rows($rs) == 0)
		echo '<span class="notfound">'.$value.'</span>'; 
	  else
		echo '<a href=cartrule-dbinfo.php?id_cart_rule='.$value.' target=_blank>'.$value.'</a>';
	}
	else
	  echo $value;
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_product WHERE id_cart='.$id_cart);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_product</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
    if($fld == "id_product")
	{ echo '<a href=product-dbinfo.php?id_product='.$value.' target=_blank>'.$value.'</a>';
	}
	else
	  echo $value;
	
	echo '</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

include "footer1.php";
echo '</body></html>';
  
  