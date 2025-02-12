<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_customer'])) $id_customer=""; else $id_customer = intval($input['id_customer']);
if(!isset($input['id_address'])) $id_address=""; else $id_address = intval($input['id_address']);
if($id_address == 0) $id_address = "";
if($id_customer == 0) $id_customer = "";

$id_lang = get_configuration_value('PS_LANG_DEFAULT');

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Customer Database Information</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
table.customerinfo 
{ margin: 5px 0 5px 0; 
  border: 1px;
  border-spacing: 0; border-collapse: collapse;
  padding: 33px;
}
table.customerinfo td
{ padding: 4px;
  border: 2px solid #c3c3c3;	
}

.found {background-color:#909000; }
.inserted, table.inserted { margin-left: 30px; !important }
.insertedx, table.insertedx { margin-left: 60px; !important }
</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript" src="sorter.js"></script>
</head>
<body>';
print_menubar();

echo '<center><a href="customer-dbinfo.php" style="text-decoration:none;"><b><font size="+2">Customer Information</font></b></a></center>';
echo '<br><center>This rather technical page shows the presence of a customer in the database.</center>';

if(($id_customer =="") && ($id_address !=""))
{ $res = dbquery('SELECT id_customer FROM '._DB_PREFIX_.'address WHERE id_address='.intval($id_address));
  if(mysqli_num_rows($res) > 0)
  { list($id_customer) = mysqli_fetch_row($res);
  }
}

echo '<form name="searchform" >';
echo '<table><tr><td>customer id: <input name=id_customer value="'.$id_customer.'">';
echo '<br>address id: <input name=id_address value="'.$id_address.'">';
echo '</td><td> &nbsp; &nbsp; <input type=submit></td></tr></table></form><p>';

if($id_customer == "")
{ include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer WHERE id_customer='.intval($id_customer));
if(mysqli_num_rows($res)==0)
{ echo "<h2>Customer not found!</h2>";
  include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}

echo 'The following table shows in which tables the customer is present. You can click some of the links.
<table class="customerinfo"><tr><td class="found">'._DB_PREFIX_.'customer</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'address WHERE id_customer='.$id_customer);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'address</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'address</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer_group WHERE id_customer='.$id_customer);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'customer_group</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'customer_group</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer_message cm 
LEFT JOIN '._DB_PREFIX_.'customer_thread ct ON cm.id_customer_thread=ct.id_customer_thread 
WHERE ct.id_customer='.$id_customer);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'customer_message</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'customer_message</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer_thread WHERE id_customer='.$id_customer);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'customer_thread</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'customer_thread</td>';

echo '</tr></table>';

echo "<br><b>"._DB_PREFIX_.'customer</b>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer WHERE id_customer='.$id_customer);
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'address WHERE id_customer='.$id_customer);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'address</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer_group WHERE id_customer='.$id_customer);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'customer_group</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
	if($fld == "id_group")
	{ $rs = dbquery('SELECT name FROM '._DB_PREFIX_.'group_lang WHERE id_group='.$value." AND id_lang=".$id_lang);
      if(mysqli_num_rows($rs) == 0)
		echo '<span class="notfound">'.$value.'</span>'; 
	  else
	  { $rw = mysqli_fetch_assoc($rs);
		echo $value.' ('.$rw['name'].')';
	  }
	}
	else
      echo $value;
	echo '</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'customer_thread WHERE id_customer='.$id_customer);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'customer_thread</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
  
  $ret = dbquery('SELECT * FROM '._DB_PREFIX_.'customer_message cm 
  LEFT JOIN '._DB_PREFIX_.'customer_thread ct ON cm.id_customer_thread=ct.id_customer_thread 
  WHERE ct.id_customer='.$id_customer);
  if(mysqli_num_rows($ret) > 0)
	echo '<span class="inserted"><b>'._DB_PREFIX_.'customer_message</span></b>';
  while ($rowt=mysqli_fetch_assoc($ret))
  { echo "<br><table class='triplemain inserted'><tr>";
	$ctr = 1;
	foreach($rowt AS $fld=>$value)
	{ echo '<td>'.$fld."<br>";
	  echo $value;
	  if(!($ctr++ % 8))
		echo '</tr><tr>';
	}
	echo '</tr></table>';
  }
}

include "footer1.php";
echo '</body></html>';
  
  