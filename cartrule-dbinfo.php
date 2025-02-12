<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_cart_rule'])) $id_cart_rule=""; else $id_cart_rule = intval($input['id_cart_rule']);
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Cart rule Database Information</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
table.cartruleinfo 
{ margin: 5px 0 5px 0; 
  border: 1px;
  border-spacing: 0; border-collapse: collapse;
  padding: 33px;
}
table.cartruleinfo td
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

echo '<center><a href="cartrule-dbinfo.php" style="text-decoration:none;"><b><font size="+2">cart_rule Information</font></b></a></center>';
echo '<br><center>This rather technical page shows the complete presence of the cart rule in the database.</center>';
echo '<form name="searchform" >';
echo 'Cart rule id: <input name=id_cart_rule value="'.$id_cart_rule.'">';
echo ' &nbsp; &nbsp; <input type=submit></form><p>';
if($id_cart_rule == "")
{ include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
{ echo "<h2>Cart rule not found!</h2>";
  include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}

echo 'The following table shows in which tables the cart rule is present. You can click some of the links.
<table class="cartruleinfo"><tr><td class="found">'._DB_PREFIX_.'cart_rule</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_carrier WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_carrier</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_carrier</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_combination WHERE id_cart_rule_1='.$id_cart_rule.' OR id_cart_rule_2='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_combination</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_combination</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_country WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_country</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_country</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_group WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_group</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_group</td>';

echo '</tr><tr>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_lang WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_lang</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_lang</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_product_rule cpr 
LEFT JOIN '._DB_PREFIX_.'cart_rule_product_rule_group cprg ON cpr.id_product_rule_group=cprg.id_product_rule_group  WHERE cprg.id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_product_rule</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_product_rule</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_product_rule_group WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_product_rule_group</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_product_rule_group</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_product_rule_value cpv
LEFT JOIN '._DB_PREFIX_.'cart_rule_product_rule cpr ON cpv.id_product_rule = cpr.id_product_rule
LEFT JOIN '._DB_PREFIX_.'cart_rule_product_rule_group cprg ON cpr.id_product_rule_group=cprg.id_product_rule_group  WHERE cprg.id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_product_rule_value</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_product_rule_value</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_shop WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'cart_rule_shop</td>';
else
  echo '<td class="found">'._DB_PREFIX_.'cart_rule_shop</td>';
echo '</tr></table>';


echo "<br><b>"._DB_PREFIX_.'cart_rule</b>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule WHERE id_cart_rule='.$id_cart_rule);
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
    echo '</td>';
	if(!($ctr++ % 8))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_carrier WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_rule_carrier</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_combination WHERE id_cart_rule_1='.$id_cart_rule.' OR id_cart_rule_2='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_rule_combination</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>";
    if($value == $id_cart_rule)
       echo $value;
    else
		echo '<a href="cartrule-dbinfo.php?id_cart_rule='.$value.'" target=_blank>'.$value.'</a>';
    echo '</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_country WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'order_country</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_group WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_rule_group</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_lang WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_rule_lang</b>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_product_rule_group WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_rule_product_rule_group</b>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
    if($fld == "id_product_rule_group")
		$id_product_rule_group = $value;
		
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
  
	$rres = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_product_rule 
	WHERE id_product_rule_group='.$id_product_rule_group);
	if(mysqli_num_rows($rres) > 0)
		echo '<span class="inserted"><b>'._DB_PREFIX_.'cart_rule_product_rule</span></b>';
	while ($rrow=mysqli_fetch_assoc($rres))
	{ echo "<br><table class='triplemain inserted'><tr>";
	  $ctr = 1;
	  foreach($rrow AS $fld=>$value)
	  { echo '<td>'.$fld."<br>".$value.'</td>';
	    if($fld == "id_product_rule")
		  $id_product_rule = $value;
	    if($fld == "type")
		  $cartruletype = $value;
		if(!($ctr++ % 10))
			echo '</tr><tr>';
	  }
	  echo '</tr></table>';
	  
	  $vres = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_product_rule_value 
	  WHERE id_product_rule='.$id_product_rule);
	  if(mysqli_num_rows($vres) > 0)
		echo '<span class="insertedx"><b>'._DB_PREFIX_.'cart_rule_product_rule</span></b>';
	  while ($vrow=mysqli_fetch_assoc($vres))
	  { echo "<br><table class='triplemain insertedx'><tr>";
	    $ctr = 1;
	    foreach($vrow AS $fld=>$value)
	    { echo '<td>'.$fld."<br>";
		  if($fld == "id_item")
		  { if($cartruletype == 'attributes')
			{ $rs = dbquery('SELECT agl.name AS aglname, al.name AS alname
				FROM '._DB_PREFIX_.'attribute a 
				LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON a.id_attribute=al.id_attribute AND al.id_lang='.$id_lang.'
				LEFT JOIN '._DB_PREFIX_.'attribute_group_lang agl ON a.id_attribute_group=agl.id_attribute_group AND agl.id_lang='.$id_lang.'
				WHERE a.id_attribute='.$value);
			  if(mysqli_num_rows($rs) > 0)
	          { $rw = mysqli_fetch_assoc($rs);
				echo $value.' (attribute: '.$rw['aglname'].': '.$rw['alname'].')';
			  }
			  else 
				echo '<span class="notfound">'.$value.'</span>'; 
			}
		    else if($cartruletype == 'categories')
			{ $rs = dbquery('SELECT name FROM '._DB_PREFIX_.'category_lang 
				WHERE id_category='.$value." AND id_lang=".$id_lang);
			  if(mysqli_num_rows($rs) > 0)
	          { $rw = mysqli_fetch_assoc($rs);
				echo $value.' (category: '.$rw['name'].')';
			  }
			  else 
				echo '<span class="notfound">'.$value.'</span>'; 
			}
		    else if($cartruletype == 'manufacturers')
			{ $rs = dbquery('SELECT name FROM '._DB_PREFIX_.'manufacturer 
				WHERE id_manufacturer='.$value);
			  if(mysqli_num_rows($rs) > 0)
	          { $rw = mysqli_fetch_assoc($rs);
				echo $value.' (manufacturer: '.$rw['name'].')';
			  }
			  else 
				echo '<span class="notfound">'.$value.'</span>'; 
			}
		    else if($cartruletype == 'products')
			{ $rs = dbquery('SELECT name FROM '._DB_PREFIX_.'product_lang 
				WHERE id_product='.$value." AND id_lang=".$id_lang);
			  if(mysqli_num_rows($rs) > 0)
	          { $rw = mysqli_fetch_assoc($rs);
				echo $value.' (product: <a href=product-dbinfo.php?id_product='.$value.' target=_blank>'.$rw['name'].'</a>)';
			  }
			  else 
				echo '<span class="notfound">'.$value.'</span>'; 
			}
		    else if($cartruletype == 'suppliers')
			{ $rs = dbquery('SELECT name FROM '._DB_PREFIX_.'supplier 
				WHERE id_supplier='.$value);
			  if(mysqli_num_rows($rs) > 0)
	          { $rw = mysqli_fetch_assoc($rs);
				echo $value.' (supplier: '.$rw['name'].')';
			  }
			  else 
				echo '<span class="notfound">'.$value.'</span>'; 
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
	}
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'cart_rule_shop WHERE id_cart_rule='.$id_cart_rule);
if(mysqli_num_rows($res) > 0)
	echo '<b>'._DB_PREFIX_.'cart_rule_shop</b>';
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



include "footer1.php";
echo '</body></html>';
  
  