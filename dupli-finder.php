<?php 
if(!@include 'approve.php') die( "approve.php was not found!");
if(isset($_GET['id_shop'])) $id_shop = intval($_GET['id_shop']); else $id_shop = "";
if(isset($_GET['id_lang'])) $id_lang = intval($_GET['id_lang']); else $id_lang = "";
if(isset($_GET['startrec'])) $startrec = intval($_GET['startrec']); else $startrec = "0";
if(!isset($_GET['numrecs']) || (intval($_GET['numrecs'])==0)) $numrecs = "1000"; else $numrecs = intval($_GET['numrecs']);

if(intval($id_lang) == 0) 
{ $id_lang = get_configuration_value('PS_LANG_DEFAULT');
}

if(intval($id_shop) == 0)
{ $id_shop = get_configuration_value('PS_SHOP_DEFAULT');
}

?><!DOCTYPE html> 
<html lang="en"><head><meta charset="utf-8">
<title>Prestashop Duplicate Product Name Finder</title>
<link rel="stylesheet" href="style1.css" type="text/css" />
<style>

</style>
<script type="text/javascript" src="utils8.js"></script>
<script type="text/javascript">
</script>
</head>
<body>
<?php 
  print_menubar();

  echo '<table style="width:100%"><tr><td style="width:70%"><a href="dupli-finder.php" style="text-decoration:none;"><h3 style="text-align:center; margin-bottom:5px;">Duplicate Product Name Finder</h3></a></center><br>';
  echo 'Product id\'s in fat have (not necessarily valid) orders in at least one shop. Product id\'s in italics are inactive in all shops. After the product id its category id\'s and price ex VAT are listed between brackets.';

//  echo '</td><td style="width:50%; text-align:right"><iframe name=tank width="300" height="70"></iframe>'; 
  echo '</td></tr></table>';

  echo '<table style="width:100%" border=1><tr><td style="width:100%"><form name=searchform">';

  echo ' &nbsp; Language: <select name="id_lang">';
  $query="select * from ". _DB_PREFIX_."lang";
  $res=dbquery($query);
  while ($language=mysqli_fetch_assoc($res)) 
  { $selected='';
	if ($language['id_lang']==$id_lang) $selected=' selected="selected" ';
	    echo '<option  value="'.$language['id_lang'].'" '.$selected.'>'.$language['name'].'</option>';
  }
  echo '</select>';

  echo ' &nbsp; shop: <select name="id_shop">';
  $query="select * from ". _DB_PREFIX_."shop";
  $res=dbquery($query);
  while ($shop=mysqli_fetch_assoc($res)) 
  { $selected='';
	if ($shop['id_shop']==$id_shop) $selected=' selected="selected" ';
	    echo '<option  value="'.$shop['id_shop'].'" '.$selected.'>'.$shop['name'].'</option>';
  }
  echo '</select>';
  
	echo '&nbsp; Startrec:&nbsp;<input size=3 name=startrec value="'.$startrec.'">';
	echo ' &nbsp; Nr of recs:&nbsp;<input size=3 name=numrecs value="'.$numrecs.'">';
	if(isset($_GET["showimg"])) $checked = "checked"; else $checked="";
	echo ' &nbsp; Show image:&nbsp;<input type=checkbox name=showimg '.$checked.'>';
	
	echo ' &nbsp; <input type=submit>';
	echo '</td></tr></table>';
	
	$query = "SELECT name, GROUP_CONCAT(id_product) AS products, COUNT(*) AS quant";
	$query .= " FROM ". _DB_PREFIX_."product_lang";
	$query .= " WHERE id_shop=".$id_shop." AND id_lang=" .$id_lang;
	$query .= " GROUP BY name";

	$query .= " HAVING quant > 1";
	$query .= " ORDER BY id_product";
	$query .= " LIMIT ".$startrec.",".$numrecs;
  $res=dbquery($query);
  echo '<table><tr><td>name</td><td>id\'s</td></tr>';
  while ($row=mysqli_fetch_assoc($res))
  { echo '<tr><td>'.$row["name"].'</td><td>';
    $products = explode(",",$row["products"]);
	foreach($products AS $product)
	{ $squery = "SELECT price FROM ". _DB_PREFIX_."product_shop ";
	  $squery .= " WHERE id_product=".$product." AND id_shop=".$id_shop;
	  $sres=dbquery($squery);
	  $srow=mysqli_fetch_assoc($sres);
	
	  $pquery = "SELECT MAX(active) AS active, GROUP_CONCAT(id_category) AS categories, ordercnt, id_image";
	  $pquery .= " FROM ". _DB_PREFIX_."product_shop ps";
	  $pquery .= " LEFT JOIN ". _DB_PREFIX_."category_product cp ON ps.id_product=cp.id_product";
	  $pquery .= " LEFT JOIN (SELECT product_id, COUNT(*) AS ordercnt FROM ". _DB_PREFIX_."order_detail GROUP BY product_id) od ON od.product_id=ps.id_product";
	  $pquery .= " LEFT JOIN ". _DB_PREFIX_."image i ON ps.id_product=i.id_product AND i.cover=1";
	  $pquery .= " WHERE ps.id_product=".$product;
	  $pquery .= " GROUP BY ps.id_product";
      $pres=dbquery($pquery);
	  while ($prow=mysqli_fetch_assoc($pres))
	  { if($prow["active"] == 1) $act = ''; else $act = 'font-style: italic;';
	    if($prow["ordercnt"] == 0) $bld = ''; else $bld = 'font-weight: bold;';
	    echo '<a href="product-solo.php?id_product='.$product.'&id_shop='.$id_shop.'&id_lang='.$id_lang.'" target=_blank style="'.$act.$bld.'">'.$product.'</a>-('.$prow["categories"].'; '.number_format($srow["price"],2).')';
		if((isset($_GET["showimg"])) && ($prow["id_image"] > 0))
		{ $id_image = $prow["id_image"];
		  echo get_product_image($product, $id_image,'');
		}		
		echo ', ';
	  }
	} 
	echo '</td></tr>';
  }
  echo '</table>';

echo '<p>';
  include "footer1.php";
echo '</body></html>';
