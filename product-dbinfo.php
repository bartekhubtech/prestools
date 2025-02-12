<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
$input = $_GET;
if(!isset($input['id_product'])) $id_product=""; else $id_product = intval($input['id_product']);
$id_lang = get_configuration_value('PS_LANG_DEFAULT');

echo 
'<!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Product Database Information</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style type="text/css">
table.productinfo 
{ margin: 5px 0 5px 0; 
  border: 1px;
  border-spacing: 0; border-collapse: collapse;
  padding: 33px;
}
table.productinfo td
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

echo '<center><a href="product-dbinfo.php" style="text-decoration:none;"><b><font size="+2">Product Information</font></b></a></center>';
echo '<br><center>This rather technical page shows the presence of a product in the database.</center>';
echo '<form name="searchform" >';
echo 'product id: <input name=id_product value="'.$id_product.'">';
echo ' &nbsp; &nbsp; <input type=submit></form><p>';
if($id_product == "")
{ include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product WHERE id_product='.intval($id_product));
if(mysqli_num_rows($res)==0)
{ echo "<h2>product not found!</h2>";
  include "footer1.php";
  echo '</body></html>'; 
  exit(0);
}


echo 'The following table shows in which tables the product is present. You can click some of the links.
<table class="productinfo"><tr><td class="found">'._DB_PREFIX_.'product</td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_attachment WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_attachment</td>';
else
  echo '<td class="found"><a href="#attachment">'._DB_PREFIX_.'product_attachment</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_attribute WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_attribute</td>';
else
  echo '<td class="found"><a href="#attribute">'._DB_PREFIX_.'product_attribute</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_carrier WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_carrier</td>';
else
  echo '<td class="found"><a href="#carrier">'._DB_PREFIX_.'product_carrier</a></td>';

echo '</tr><tr>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_country_tax WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_country_tax</td>';
else
  echo '<td class="found"><a href="#country_tax">'._DB_PREFIX_.'product_country_tax</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_download WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_download</td>';
else
  echo '<td class="found"><a href="#download">'._DB_PREFIX_.'product_download</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_group_reduction_cache WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_group_reduction_cache</td>';
else
  echo '<td class="found"><a href="#group_reduction_cache">'._DB_PREFIX_.'product_group_reduction_cache</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_lang WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_lang</td>';
else
  echo '<td class="found"><a href="#lang">'._DB_PREFIX_.'product_lang</a></td>';

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_sale WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_sale</td>';
else
  echo '<td class="found"><a href="#sale">'._DB_PREFIX_.'product_sale</a></td>';

echo '</tr><tr>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_shop WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_shop</td>';
else
  echo '<td class="found"><a href="#shop">'._DB_PREFIX_.'product_shop</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_supplier WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_supplier</td>';
else
  echo '<td class="found"><a href="#supplier">'._DB_PREFIX_.'product_supplier</a></td>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_tag WHERE id_product='.$id_product);
if(mysqli_num_rows($res)==0)
  echo '<td>'._DB_PREFIX_.'product_tag</td>';
else
  echo '<td class="found"><a href="#tag">'._DB_PREFIX_.'product_tag</a></td>';

echo '</tr></table>';

echo "<br><b>"._DB_PREFIX_.'product</b>';
$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product WHERE id_product='.$id_product);
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_attachment WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=attachment><b>'._DB_PREFIX_.'product_attachment</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_attribute WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=attribute><b>'._DB_PREFIX_.'product_attribute</b></span>';
while ($row=mysqli_fetch_assoc($res))
{ echo "<br><table class='triplemain'><tr>";
  $paquery = "SELECT GROUP_CONCAT(CONCAT(gl.name,': <i>',l.name,'</i>') SEPARATOR ', ') AS nameblock, GROUP_CONCAT(CONCAT(gl.id_attribute_group,':',a.id_attribute) SEPARATOR '=') AS idblock from ". _DB_PREFIX_."product_attribute pa";
  $paquery .= " LEFT JOIN ". _DB_PREFIX_."product_attribute_combination c on pa.id_product_attribute=c.id_product_attribute";
  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute a on a.id_attribute=c.id_attribute";
  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_lang l on l.id_attribute=c.id_attribute AND l.id_lang='".$id_lang."'";
  $paquery .= " LEFT JOIN ". _DB_PREFIX_."attribute_group_lang gl on gl.id_attribute_group=a.id_attribute_group AND gl.id_lang='".$id_lang."'";
  $paquery .= " WHERE pa.id_product_attribute='".$row['id_product_attribute']."' GROUP BY pa.id_product_attribute";
  $paquery .= " ORDER BY gl.name, l.name";
  $pares=dbquery($paquery);
  $parow = mysqli_fetch_assoc($pares);
  $labels = explode("=", $parow['nameblock']);
  echo '<td colspan=10>'.$parow['nameblock'].'</td></tr><tr>';
  $ctr = 1;
  foreach($row AS $fld=>$value)
  { echo '<td>'.$fld."<br>".$value.'</td>';
	if(!($ctr++ % 10))
		echo '</tr><tr>';
  }
  echo '</tr></table>';
}

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_carrier WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=carrier><b>'._DB_PREFIX_.'product_carrier</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_country_tax WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=country_tax><b>'._DB_PREFIX_.'product_country_tax</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_download WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=download><b>'._DB_PREFIX_.'product_download</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_group_reduction_cache WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=group_reduction_cache><b>'._DB_PREFIX_.'product_group_reduction_cache</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_lang WHERE id_product='.$id_product." ORDER BY id_lang");
if(mysqli_num_rows($res) > 0)
	echo '<span id=lang><b>'._DB_PREFIX_.'product_lang</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_sale WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=sale><b>'._DB_PREFIX_.'product_sale</b></sale>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_shop WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=shop><b>'._DB_PREFIX_.'product_shop</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_supplier WHERE id_product='.$id_product);
if(mysqli_num_rows($res) > 0)
	echo '<span id=supplier><b>'._DB_PREFIX_.'product_supplier</b></span>';
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

$res = dbquery('SELECT * FROM '._DB_PREFIX_.'product_tag WHERE id_product='.$id_product." ORDER BY id_lang");
if(mysqli_num_rows($res) > 0)
	echo '<span id=tag><b>'._DB_PREFIX_.'product_tag</b></span>';
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
  
  