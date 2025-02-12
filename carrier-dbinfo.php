<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_carrier'])) $id_carrier=""; else $id_carrier = intval($input['id_carrier']);
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Carrier Database Information</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
table.carrierinfo 
{ margin: 5px 0 5px 0; 
  border: 1px;
  border-spacing: 0; border-collapse: collapse;
  padding: 33px;
}
table.carrierinfo td
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

echo '<center><a href="carrier-dbinfo.php" style="text-decoration:none;"><b><font size="+2">Carrier Information</font></b></a></center>';
echo '<br><center>This rather technical page shows the complete presence of a carrier in the database.</center>';
echo '<form name="searchform" >';
echo 'Carrier id: <input name=id_carrier value="'.$id_carrier.'">';
echo ' &nbsp; &nbsp; <input type=submit></form><p>';
if($id_carrier == "")
{ include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier WHERE id_carrier='.intval($id_carrier));
if(mysqli_num_rows($res)==0)
{ echo "<h2>Carrier not found!</h2>";
  include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}


echo 'The following table shows in which tables the carrier is present. You can click some of the links.
<table class="carrierinfo"><tr><td class="found">'._DB_PREFIX_.'carrier</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_group WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'carrier_group</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'carrier_group</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_lang WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'carrier_lang</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'carrier_lang</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_shop WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'carrier_shop</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'carrier_shop</td>';

echo '</tr><tr>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_tax_rules_group_shop WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'carrier_tax_rules_group_shop</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'carrier_tax_rules_group_shop</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_zone WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'carrier_zone</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'carrier_zone</td>';
echo '</tr></table>';

echo "<br><b>"._DB_PREFIX_.'carrier</b>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier WHERE id_carrier='.$id_carrier);
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_group WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'carrier_group</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_lang WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'carrier_lang</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_shop WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'carrier_shop</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_tax_rules_group_shop WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'carrier_tax_rules_group_shop</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value;
    if(($fld == "id_tax_rules_group") && ($value != 0))
    { $rt = dbquery('SELECT name FROM '._DB_PREFIX_.'tax_rules_group WHERE id_tax_rules_group='.$value);
	  if(mysqli_num_rows($rt) > 0)
	  { $rwt = mysqli_fetch_assoc($rt);
		echo " (".$rwt['name'].")";
	  }
	}
    echo '</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'carrier_zone WHERE id_carrier='.$id_carrier);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'carrier_zone</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value;
	if(($fld == "id_zone") && ($value != 0))
    { $rt = dbquery('SELECT name FROM '._DB_PREFIX_.'zone WHERE id_zone='.$value);
	  if(mysqli_num_rows($rt) > 0)
	  { $rwt = mysqli_fetch_assoc($rt);
		echo " (".$rwt['name'].")";
	  }
	}
    echo '</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

include "footer1.php";
echo '</body></html>';
  
  